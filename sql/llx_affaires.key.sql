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

ALTER TABLE llx_affaires ADD INDEX idx_llx_affaires_fk_c_type (fk_c_type);
ALTER TABLE llx_affaires ADD CONSTRAINT llx_affaires_ibfk_2 FOREIGN KEY (fk_c_type) REFERENCES llx_c_affaires_type (rowid);

ALTER TABLE llx_affaires ADD INDEX idx_llx_affaires_fk_soc (fk_soc);
ALTER TABLE llx_affaires ADD CONSTRAINT llx_affaires_ibfk_3 FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);

ALTER TABLE llx_affaires ADD INDEX idx_llx_affaires_ref (ref);