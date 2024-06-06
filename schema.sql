CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'user',
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `email_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiration` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `account_locked` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lab_name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `r1_temp` varchar(255) DEFAULT NULL,
  `r1_humi` varchar(255) DEFAULT NULL,
  `r1_illu` varchar(255) DEFAULT NULL,
  `r1_pres` varchar(255) DEFAULT NULL,
  `r2_temp` varchar(255) DEFAULT NULL,
  `r2_humi` varchar(255) DEFAULT NULL,
  `r2_illu` varchar(255) DEFAULT NULL,
  `r2_pres` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
