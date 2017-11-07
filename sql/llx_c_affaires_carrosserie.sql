CREATE TABLE IF NOT EXISTS llx_c_affaires_carrosserie (
rowid 			int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
carrosserie 	varchar(255) NOT NULL,
labelexcel 		varchar(60),
active	 		int(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB;



