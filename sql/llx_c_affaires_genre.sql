CREATE TABLE IF NOT EXISTS `llx_c_affaires_genre` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `genre` varchar(255) NOT NULL,
  `rep` int(1) NOT NULL DEFAULT '1',
  `cv` int(2) NOT NULL,
  `del_rg` int(10) NOT NULL,
  labelexcel varchar(45),
  `active` int(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB;


