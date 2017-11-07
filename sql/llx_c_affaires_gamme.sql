CREATE TABLE IF NOT EXISTS `llx_c_affaires_gamme` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `gamme` varchar(255) NOT NULL,
  `cv` int(2) NOT NULL,
  `Active` int(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB;

