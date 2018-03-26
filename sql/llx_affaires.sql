-- Manage Affaires
-- Copyright (C) 2014  Florian HENRY <florian.henry@atm-consulting.fr>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.


CREATE TABLE IF NOT EXISTS llx_affaires (
rowid 			integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
entity			integer NOT NULL DEFAULT 1,
ref				varchar(50) NOT NULL,
fk_user_resp 	integer NOT NULL,
fk_soc	 		integer NOT NULL,
fk_ctm	 		integer,
fk_c_type 		integer  NOT NULL,
year	 		integer NOT NULL,
description	 	text,
fk_user_author	int(11) NOT NULL,
datec			datetime NOT NULL,
fk_user_mod		int(11) NOT NULL,
tms	 			timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)ENGINE=InnoDB;