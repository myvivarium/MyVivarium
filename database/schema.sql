-- Creating the table to store basic information about breeding cages
CREATE TABLE `bc_basic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,             -- Primary key, auto-incremented unique identifier
  `cage_id` varchar(255) NOT NULL,                  -- Identifier for the cage
  `pi_name` varchar(255) NOT NULL,                  -- Name of the principal investigator
  `cross` varchar(255) NOT NULL,                    -- Cross type information
  `iacuc` varchar(255) NOT NULL,                    -- IACUC (Institutional Animal Care and Use Committee) approval number
  `user` varchar(255) NOT NULL,                     -- User associated with the record
  `male_id` varchar(255) NOT NULL,                  -- Identifier for the male animal
  `female_id` varchar(255) NOT NULL,                -- Identifier for the female animal
  `male_dob` date NOT NULL,                         -- Date of birth of the male animal
  `female_dob` date NOT NULL,                       -- Date of birth of the female animal
  `remarks` text NOT NULL,                          -- Additional remarks
  PRIMARY KEY (`id`)                                -- Setting the primary key
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Creating the table to store litter information
CREATE TABLE `bc_litter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,             -- Primary key, auto-incremented unique identifier
  `cage_id` varchar(255) NOT NULL,                  -- Identifier for the cage
  `dom` date NOT NULL,                              -- Date of mating
  `litter_dob` date DEFAULT NULL,                   -- Date of birth of the litter
  `pups_alive` int(4) NOT NULL,                     -- Number of alive pups
  `pups_dead` int(4) NOT NULL,                      -- Number of dead pups
  `pups_male` int(4) NOT NULL,                      -- Number of male pups
  `pups_female` int(4) NOT NULL,                    -- Number of female pups
  `remarks` text NOT NULL,                          -- Additional remarks
  PRIMARY KEY (`id`)                                -- Setting the primary key
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Creating the table to store email queue information
CREATE TABLE `email_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,             -- Primary key, auto-incremented unique identifier
  `recipient` varchar(255) NOT NULL,                -- Recipient email address
  `subject` varchar(255) NOT NULL,                  -- Subject of the email
  `body` text NOT NULL,                             -- Body content of the email
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending', -- Email status
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(), -- Creation timestamp
  `scheduled_at` timestamp NOT NULL DEFAULT current_timestamp(), -- Scheduled timestamp
  `sent_at` timestamp NULL DEFAULT NULL,            -- Sent timestamp
  `error_message` text DEFAULT NULL,                -- Error message if the email failed
  PRIMARY KEY (`id`)                                -- Setting the primary key
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Creating the table to store information about uploaded files
CREATE TABLE `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,             -- Primary key, auto-incremented unique identifier
  `file_name` varchar(255) NOT NULL,                -- Name of the uploaded file
  `file_path` varchar(255) NOT NULL,                -- Path to the uploaded file
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(), -- Timestamp of the upload
  `cage_id` varchar(255) DEFAULT NULL,              -- Identifier for the cage (optional)
  PRIMARY KEY (`id`)                                -- Setting the primary key
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Creating the table to store basic information about housed cages
CREATE TABLE `hc_basic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,             -- Primary key, auto-incremented unique identifier
  `cage_id` varchar(255) NOT NULL,                  -- Identifier for the cage
  `pi_name` varchar(255) NOT NULL,                  -- Name of the principal investigator
  `strain` varchar(255) NOT NULL,                   -- Strain information
  `iacuc` varchar(255) NOT NULL,                    -- IACUC approval number
  `user` varchar(255) NOT NULL,                     -- User associated with the record
  `qty` int(11) NOT NULL,                           -- Quantity of animals
  `dob` date NOT NULL,                              -- Date of birth of the animals
  `sex` varchar(255) NOT NULL,                      -- Sex of the animals
  `parent_cg` varchar(255) NOT NULL,                -- Parent cage identifier
  `remarks` text NOT NULL,                          -- Additional remarks
  PRIMARY KEY (`id`)                                -- Setting the primary key
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Creating the table to store information about individual mice
CREATE TABLE `mouse` (
  `id` int(11) NOT NULL AUTO_INCREMENT,             -- Primary key, auto-incremented unique identifier
  `cage_id` varchar(255) NOT NULL,                  -- Identifier for the cage
  `mouse_id` varchar(255) NOT NULL,                 -- Identifier for the mouse
  `genotype` varchar(255) NOT NULL,                 -- Genotype information
  `notes` text NOT NULL,                            -- Additional notes
  PRIMARY KEY (`id`)                                -- Setting the primary key
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Creating the table to store notes data
CREATE TABLE `nt_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,             -- Primary key, auto-incremented unique identifier
  `cage_id` varchar(255) DEFAULT NULL,              -- Identifier for the cage (optional)
  `note_text` text DEFAULT NULL,                    -- Text of the note
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(), -- Timestamp of the note creation
  `user_id` text NOT NULL,                          -- Identifier for the user who created the note
  PRIMARY KEY (`id`)                                -- Setting the primary key
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Creating the table to store settings
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,             -- Primary key, auto-incremented unique identifier
  `name` varchar(255) NOT NULL,                     -- Name of the setting
  `value` varchar(255) NOT NULL,                    -- Value of the setting
  PRIMARY KEY (`id`)                                -- Setting the primary key
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Creating the table to store strain information
CREATE TABLE `strain` (
  `id` int(11) NOT NULL AUTO_INCREMENT,             -- Primary key, auto-incremented unique identifier
  `str_id` varchar(50) NOT NULL,                    -- Strain identifier
  `str_name` varchar(255) NOT NULL,                 -- Strain name
  `str_aka` varchar(255) DEFAULT NULL,              -- Alternate name for the strain
  `str_url` varchar(255) DEFAULT NULL,              -- URL for the strain information
  `str_rrid` varchar(255) DEFAULT NULL,             -- RRID for the strain
  PRIMARY KEY (`id`)                                -- Setting the primary key
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Creating the table to store tasks
CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,             -- Primary key, auto-incremented unique identifier
  `title` varchar(250) NOT NULL,                    -- Title of the task
  `description` text NOT NULL,                      -- Description of the task
  `assigned_by` varchar(50) NOT NULL,               -- User who assigned the task
  `assigned_to` varchar(50) NOT NULL,               -- User assigned to the task
  `status` enum('Pending','In Progress','Completed') NOT NULL DEFAULT 'Pending', -- Status of the task
  `completion_date` date DEFAULT NULL,              -- Completion date of the task
  `cage_id` varchar(50) DEFAULT NULL,               -- Identifier for the cage (optional)
  `creation_date` timestamp NOT NULL DEFAULT current_timestamp(), -- Creation timestamp
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(), -- Update timestamp
  PRIMARY KEY (`id`)                                -- Setting the primary key
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Creating the table to store user information
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,             -- Primary key, auto-incremented unique identifier
  `name` varchar(255) NOT NULL,                     -- Name of the user
  `username` varchar(255) NOT NULL,                 -- Username
  `position` varchar(255) NOT NULL,                 -- Position of the user
  `role` varchar(255) NOT NULL,                     -- Role of the user
  `password` varchar(255) NOT NULL,                 -- Hashed password
  `status` varchar(255) NOT NULL,                   -- Status of the user account
  `reset_token` varchar(255) DEFAULT NULL,          -- Token for password reset (optional)
  `reset_token_expiration` datetime DEFAULT NULL,   -- Expiration datetime for the reset token (optional)
  `login_attempts` int(11) DEFAULT 0,               -- Count of login attempts
  `account_locked` datetime DEFAULT NULL,           -- Datetime when the account was locked (optional)
  `email_verified` tinyint(1) DEFAULT 0,            -- Email verification status
  `email_token` varchar(255) DEFAULT NULL,          -- Token for email verification (optional)
  `initials` varchar(10) DEFAULT NULL,              -- Name based initials
  PRIMARY KEY (`id`)                                -- Setting the primary key
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Inserting initial data into the users table
INSERT INTO `users` (`name`, `username`, `position`, `role`, `password`, `status`, `reset_token`, `reset_token_expiration`, `login_attempts`, `account_locked`, `email_verified`, `email_token`, `initials`)
VALUES ('Temporary Admin', 'admin@myvivarium.online', 'Principal Investigator', 'admin', '$2y$10$Y3sGVYIhu2BjpSFh9HA4We.lUhO.hvS9OVPb2Fb82N0BJGVFIXsmW', 'approved', NULL, NULL, 0, NULL, 1, NULL, 'TAN');

-- Inserting initial data into the strain table
INSERT INTO `strain` (`str_id`, `str_name`, `str_aka`, `str_url`, `str_rrid`) 
VALUES ('035561', 'STOCK Tc(HSA21,CAG-EGFP)1Yakaz/J', 'B6D2F1 TcMAC21', 'https://www.jax.org/strain/035561', 'IMSR_JAX:035561');