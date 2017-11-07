CREATE TABLE IF NOT EXISTS `llx_c_affaires_silouhette` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `silouhette` varchar(255) NOT NULL,
  `cv` int(2) NOT NULL,
  `rep` int(1) NOT NULL DEFAULT '1',
  `active` int(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB;