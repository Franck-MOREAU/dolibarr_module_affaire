-- ========================================================================
-- Copyright (C) 2014 Florian HENRY	<florian.henry@atm-consulting.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ========================================================================

CREATE TABLE IF NOT EXISTS llx_affaires_det
(
rowid                     integer AUTO_INCREMENT PRIMARY KEY,
fk_affaires				 integer NOT NULL,
fk_gamme                 integer NOT NULL,
fk_silhouette            integer NOT NULL,
fk_genre                 integer NOT NULL,
fk_carrosserie           integer NOT NULL,
fk_status                integer NOT NULL,
fk_marque_trt            integer,
fk_motifs                varchar(255),
fk_commande              integer,
spec					 varchar(255),
fk_user_author	int(11) NOT NULL,
datec			datetime NOT NULL,
fk_user_mod		int(11) NOT NULL,
tms	 			timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=innodb;

