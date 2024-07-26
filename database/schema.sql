-- Table for storing IACUC (Institutional Animal Care and Use Committee) records
CREATE TABLE `iacuc` (
  `iacuc_id` varchar(255) NOT NULL,
  `iacuc_title` varchar(255) NOT NULL,
  `file_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`iacuc_id`)
);

-- Table for storing user information
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL, 
  `username` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiration` datetime DEFAULT NULL,
  `login_attempts` int DEFAULT 0,
  `account_locked` datetime DEFAULT NULL,
  `email_verified` tinyint DEFAULT 0,
  `email_token` varchar(255) DEFAULT NULL,
  `initials` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id`)
);

-- Table for storing cage information
CREATE TABLE `cages` (
  `cage_id` varchar(255) NOT NULL UNIQUE,
  `pi_name` int DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`cage_id`),
  FOREIGN KEY (`pi_name`) REFERENCES `users` (`id`) ON DELETE SET NULL
);

-- Junction table for associating cages with IACUC records
CREATE TABLE `cage_iacuc` (
  `cage_id` varchar(255) NOT NULL,
  `iacuc_id` varchar(255) NOT NULL,
  PRIMARY KEY (`cage_id`, `iacuc_id`),
  FOREIGN KEY (`cage_id`) REFERENCES `cages` (`cage_id`) ON UPDATE CASCADE,
  FOREIGN KEY (`iacuc_id`) REFERENCES `iacuc` (`iacuc_id`) ON DELETE CASCADE
);

-- Junction table for associating cages with users
CREATE TABLE `cage_users` (
  `cage_id` varchar(255) NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`cage_id`, `user_id`),
  FOREIGN KEY (`cage_id`) REFERENCES `cages` (`cage_id`) ON UPDATE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);

-- Table for storing strain information
CREATE TABLE `strains` (
  `id` int NOT NULL AUTO_INCREMENT,
  `str_id` varchar(255) NOT NULL,
  `str_name` varchar(255) NOT NULL,
  `str_aka` varchar(255) DEFAULT NULL,
  `str_url` varchar(255) DEFAULT NULL,
  `str_rrid` varchar(255) DEFAULT NULL,
  `str_notes` text DEFAULT NULL;
  PRIMARY KEY (`id`),
  KEY `idx_strains_str_id` (`str_id`)
);

-- Table for storing holding information related to cages and strains
CREATE TABLE `holding` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cage_id` varchar(255) NOT NULL,
  `strain` varchar(255) DEFAULT NULL,
  `dob` date NOT NULL,
  `sex` enum('male', 'female') DEFAULT NULL,
  `parent_cg` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`cage_id`) REFERENCES `cages` (`cage_id`) ON UPDATE CASCADE,
  FOREIGN KEY (`strain`) REFERENCES `strains` (`str_id`) ON DELETE SET NULL
);

-- Table for storing breeding information
CREATE TABLE `breeding` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cage_id` varchar(255) NOT NULL,
  `cross` varchar(255) NOT NULL,
  `male_id` varchar(255) NOT NULL,
  `female_id` varchar(255) NOT NULL,
  `male_dob` date NOT NULL,
  `female_dob` date NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`cage_id`) REFERENCES `cages` (`cage_id`) ON UPDATE CASCADE
);

-- Table for storing litter information
CREATE TABLE `litters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cage_id` varchar(255) NOT NULL,
  `dom` date NOT NULL,
  `litter_dob` date DEFAULT NULL,
  `pups_alive` int NOT NULL,
  `pups_dead` int NOT NULL,
  `pups_male` int NOT NULL,
  `pups_female` int NOT NULL,
  `remarks` text NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`cage_id`) REFERENCES `cages` (`cage_id`) ON UPDATE CASCADE
);

-- Table for storing file information related to cages
CREATE TABLE `files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cage_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`cage_id`) REFERENCES `cages` (`cage_id`) ON UPDATE CASCADE
);

-- Table for storing notes related to cages and users
CREATE TABLE `notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cage_id` varchar(255) DEFAULT NULL,
  `note_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`cage_id`) REFERENCES `cages` (`cage_id`) ON UPDATE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
);

-- Table for storing mouse information related to cages
CREATE TABLE `mice` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cage_id` varchar(255) NOT NULL,
  `mouse_id` varchar(255) NOT NULL,
  `genotype` varchar(255) NOT NULL,
  `notes` text NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`cage_id`) REFERENCES `cages` (`cage_id`) ON UPDATE CASCADE
);

-- Table for storing tasks information
CREATE TABLE `tasks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `assigned_by` int DEFAULT NULL,
  `assigned_to` varchar(50) NOT NULL,
  `status` enum('Pending','In Progress','Completed') NOT NULL DEFAULT 'Pending',
  `completion_date` date DEFAULT NULL,
  `cage_id` varchar(255) DEFAULT NULL,
  `creation_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`cage_id`) REFERENCES `cages` (`cage_id`) ON UPDATE CASCADE,
  FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);

-- Table for storing outbox email information
CREATE TABLE `outbox` (
  `id` int NOT NULL AUTO_INCREMENT,
  `recipient` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `scheduled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sent_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `task_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_outbox_recipient` (`recipient`),
  FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL
);

-- Table for storing system settings
CREATE TABLE `settings` (
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`name`)
);

-- Insert initial data into the users table
INSERT INTO `users` (`name`, `username`, `position`, `role`, `password`, `status`, `reset_token`, `reset_token_expiration`, `login_attempts`, `account_locked`, `email_verified`, `email_token`, `initials`)
VALUES ('Temporary Admin', 'admin@myvivarium.online', 'Principal Investigator', 'admin', '$2y$10$Y3sGVYIhu2BjpSFh9HA4We.lUhO.hvS9OVPb2Fb82N0BJGVFIXsmW', 'approved', NULL, NULL, 0, NULL, 1, NULL, 'TAN');

-- Insert initial data into the strains table
INSERT INTO `strains` (`str_id`, `str_name`, `str_aka`, `str_url`, `str_rrid`)
VALUES ('035561', 'STOCK Tc(HSA21,CAG-EGFP)1Yakaz/J', 'B6D2F1 TcMAC21', 'https://www.jax.org/strain/035561', 'IMSR_JAX:035561');
