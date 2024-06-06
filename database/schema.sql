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

-- Creating the table to store environmental data for different rooms
CREATE TABLE `data` (
  `lab_name` varchar(255) NOT NULL,                 -- Name of the lab
  `url` varchar(255) NOT NULL,                      -- URL associated with the lab
  `r1_temp` varchar(255) NOT NULL,                  -- Temperature in room 1
  `r1_humi` varchar(255) NOT NULL,                  -- Humidity in room 1
  `r1_illu` varchar(255) NOT NULL,                  -- Illumination in room 1
  `r1_pres` varchar(255) NOT NULL,                  -- Pressure in room 1
  `r2_temp` varchar(255) NOT NULL,                  -- Temperature in room 2
  `r2_humi` varchar(255) NOT NULL,                  -- Humidity in room 2
  `r2_illu` varchar(255) NOT NULL,                  -- Illumination in room 2
  `r2_pres` varchar(255) NOT NULL                   -- Pressure in room 2
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

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
  `strain` varchar(255) NOT NULL,                   -- Strain of the animals
  `iacuc` varchar(255) NOT NULL,                    -- IACUC (Institutional Animal Care and Use Committee) approval number
  `user` varchar(255) NOT NULL,                     -- User associated with the record
  `qty` int(11) NOT NULL,                           -- Quantity of animals
  `dob` date NOT NULL,                              -- Date of birth of the animals
  `sex` varchar(255) NOT NULL,                      -- Sex of the animals
  `parent_cg` varchar(255) NOT NULL,                -- Parent cage identifier
  `remarks` text NOT NULL,                          -- Additional remarks
  `mouse_id_1` varchar(255) NOT NULL,               -- Identifier for mouse 1
  `genotype_1` varchar(255) NOT NULL,               -- Genotype of mouse 1
  `notes_1` text NOT NULL,                          -- Notes for mouse 1
  `mouse_id_2` varchar(255) NOT NULL,               -- Identifier for mouse 2
  `genotype_2` varchar(255) NOT NULL,               -- Genotype of mouse 2
  `notes_2` text NOT NULL,                          -- Notes for mouse 2
  `mouse_id_3` varchar(255) NOT NULL,               -- Identifier for mouse 3
  `genotype_3` varchar(255) NOT NULL,               -- Genotype of mouse 3
  `notes_3` text NOT NULL,                          -- Notes for mouse 3
  `mouse_id_4` varchar(255) NOT NULL,               -- Identifier for mouse 4
  `genotype_4` varchar(255) NOT NULL,               -- Genotype of mouse 4
  `notes_4` text NOT NULL,                          -- Notes for mouse 4
  `mouse_id_5` varchar(255) NOT NULL,               -- Identifier for mouse 5
  `genotype_5` varchar(255) NOT NULL,               -- Genotype of mouse 5
  `notes_5` text NOT NULL,                          -- Notes for mouse 5
  PRIMARY KEY (`id`)                                -- Setting the primary key
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Creating the table to store notes data
CREATE TABLE `nt_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,             -- Primary key, auto-incremented unique identifier
  `cage_id` varchar(255) DEFAULT NULL,              -- Identifier for the cage (optional)
  `note_text` text DEFAULT NULL,                    -- Text of the note
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(), -- Timestamp of the note creation
  `user_id` text NOT NULL,                          -- Identifier for the user who created the note
  PRIMARY KEY (`id`)                                -- Setting the primary key
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

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
  PRIMARY KEY (`id`)                                -- Setting the primary key
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Inserting initial data into the users table
INSERT INTO `users` 
(`name`, `username`, `position`, `role`, `password`, `status`, `reset_token`, `reset_token_expiration`, `login_attempts`, `account_locked`, `email_verified`, `email_token`)
VALUES 
('Temporary Admin', 'admin@myvivarium.online', 'Principal Investigator', 'admin', '$2y$10$Y3sGVYIhu2BjpSFh9HA4We.lUhO.hvS9OVPb2Fb82N0BJGVFIXsmW', 'approved', NULL, NULL, 0, NULL, 1, NULL);
