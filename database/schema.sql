-- Adminer 4.7.8 MySQL dump
CREATE TABLE `bc_basic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cage_id` varchar(255) NOT NULL,
  `pi_name` varchar(255) NOT NULL,
  `cross` varchar(255) NOT NULL,
  `iacuc` varchar(255) NOT NULL,
  `user` varchar(255) NOT NULL,
  `male_id` varchar(255) NOT NULL,
  `female_id` varchar(255) NOT NULL,
  `male_dob` date NOT NULL,
  `female_dob` date NOT NULL,
  `remarks` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `bc_litter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cage_id` varchar(255) NOT NULL,
  `dom` date NOT NULL,
  `litter_dob` date DEFAULT NULL,
  `pups_alive` int(4) NOT NULL,
  `pups_dead` int(4) NOT NULL,
  `pups_male` int(4) NOT NULL,
  `pups_female` int(4) NOT NULL,
  `remarks` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `data` (
  `lab_name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `r1_temp` varchar(255) NOT NULL,
  `r1_humi` varchar(255) NOT NULL,
  `r1_illu` varchar(255) NOT NULL,
  `r1_pres` varchar(255) NOT NULL,
  `r2_temp` varchar(255) NOT NULL,
  `r2_humi` varchar(255) NOT NULL,
  `r2_illu` varchar(255) NOT NULL,
  `r2_pres` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `file_path` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cage_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `hc_basic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cage_id` varchar(255) NOT NULL,
  `pi_name` varchar(255) NOT NULL,
  `strain` varchar(255) NOT NULL,
  `iacuc` varchar(255) NOT NULL,
  `user` varchar(255) NOT NULL,
  `qty` int(11) NOT NULL,
  `dob` date NOT NULL,
  `sex` varchar(255) NOT NULL,
  `parent_cg` varchar(255) NOT NULL,
  `remarks` text NOT NULL,
  `mouse_id_1` varchar(255) NOT NULL,
  `genotype_1` varchar(255) NOT NULL,
  `notes_1` text NOT NULL,
  `mouse_id_2` varchar(255) NOT NULL,
  `genotype_2` varchar(255) NOT NULL,
  `notes_2` text NOT NULL,
  `mouse_id_3` varchar(255) NOT NULL,
  `genotype_3` varchar(255) NOT NULL,
  `notes_3` text NOT NULL,
  `mouse_id_4` varchar(255) NOT NULL,
  `genotype_4` varchar(255) NOT NULL,
  `notes_4` text NOT NULL,
  `mouse_id_5` varchar(255) NOT NULL,
  `genotype_5` varchar(255) NOT NULL,
  `notes_5` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `nt_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cage_id` varchar(255) DEFAULT NULL,
  `note_text` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiration` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `account_locked` datetime DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `email_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `users` (`name`, `username`, `position`, `role`, `password`, `status`, `reset_token`, `reset_token_expiration`, `login_attempts`, `account_locked`, `email_verified`, `email_token`)
VALUES ('Temporary Admin', 'admin@myvivarium.online', 'Lab Manager', 'admin', '$2y$10$Y3sGVYIhu2BjpSFh9HA4We.lUhO.hvS9OVPb2Fb82N0BJGVFIXsmW', 'approved', NULL, NULL, 0, NULL, 1, NULL);
