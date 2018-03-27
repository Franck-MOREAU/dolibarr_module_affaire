CREATE TABLE llx_volvo_modele_fdd_det (
  rowid INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
  fk_modele_fdd INTEGER NOT NULL,
  name varchar(255) NOT NULL,
  cell varchar(10) NOT NULL,
  type varchar(5) NOT NULL,
  oblig SMALLINT NOT NULL
) ENGINE=InnoDB;
