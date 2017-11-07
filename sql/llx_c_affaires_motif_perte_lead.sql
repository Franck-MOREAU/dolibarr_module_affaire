CREATE TABLE IF NOT EXISTS `llx_c_affaires_motif_perte_lead` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `motif` varchar(255) NOT NULL,
  `active` int(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB;
