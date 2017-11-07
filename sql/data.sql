-- Manage Lead
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

INSERT INTO llx_c_lead_status(rowid,code,label,position,percent,active) VALUES (3,'S'   ,'Sans Suite', 40, 60,1);
INSERT INTO llx_c_lead_status(rowid,code,label,position,percent,active) VALUES (1,'PENDING','En cours', 50, 50,0);
INSERT INTO llx_c_lead_status(rowid,code,label,position,percent,active) VALUES (2,'WON'    ,'TraitÈe', 60, 100,1);
INSERT INTO llx_c_lead_status(rowid,code,label,position,percent,active) VALUES (4,'LOST'   ,'ManquÈe', 70, 0,1);

INSERT INTO llx_c_lead_type(rowid,code,label,active) VALUES (1,'SUPP','Volvo',1);
INSERT INTO llx_c_lead_type(rowid,code,label,active) VALUES (2,'TRAIN','Nissan',1);
INSERT INTO llx_c_lead_type(rowid,code,label,active) VALUES (3,'ADVI','Schmitz',1);

INSERT INTO `llx_c_affaires_carrosserie` (`rowid`, `carrosserie`,active,labelexcel) VALUES
(1, 'Fourgon', 1, 'FOURGON'),
(2, 'Tautliner', 1, 'PLSC'),
(3, 'Ampiroll', 1, NULL),
(4, 'Frigo', 1, 'FG TD'),
(5, 'Caisse Mobile', 1, NULL),
(6, 'Plateau', 1, 'PLATEAU'),
(7, 'Plateau grue', 1, NULL),
(8, 'Benne TP', 1, 'TRAVAUX,VOIRIE'),
(9, 'Benne TP + Grue', 1, NULL),
(11, 'Caravane', 1, 'CARAVANE'),
(12, 'Porte Container', 1, 'PTE CONT'),
(13, 'Citerne', 1, 'CIT VID,CARB LEG,CIT GAZ,CIT ALIM,CIT ALTD,CIT EAU'),
(14, 'Benne', 1, 'BEN AMO,BENNE,BEN CERE'),
(15, 'DÈpaneuse', 1, 'DEPANNAG,ATELIER'),
(16, 'Tracteur Routier', 1, 'PR SREM,PR REM'),
(17, 'Porte Engins', 1, 'PTE ENG'),
(18, 'Porte voiture', 1, 'PTE VOIT'),
(19, 'Benne a Ordure MÈnagËre', 1, 'BOM'),
(20, 'Savoyarde', 1, 'SAVOYARD'),
(21, 'Toupie a BÈton', 1, 'BETON'),
(22, 'PulvÈ', 1, 'CIT PULV,CIT BETA'),
(23, 'BÈtaillere', 1, 'BETAIL'),
(24, 'Forestier', 1, 'FOREST'),
(25, 'Porte ferraille', 1, 'PTE FER'),
(26, 'Autres types de carrosserie', 1, 'NON SPEC,MAGASIN'),
(27, 'Incendie', 1, NULL);

INSERT INTO `llx_c_affaires_gamme` (`rowid`, `gamme`, `cv`, `Active`) VALUES
(1, 'FH', 4, 1),
(2, 'FM', 4, 1),
(3, 'FL', 4, 1),
(4, 'FE', 4, 1),
(5, 'NP300 - Navarra', 5, 1),
(6, 'NV200', 5, 1),
(7, 'NV200 Electrique', 5, 1),
(8, 'NV300', 5, 0),
(9, 'NV400', 5, 1),
(10, 'NT400', 5, 1),
(11, 'NT500', 5, 1),
(12, 'Fourgon', 6, 1),
(13, 'Frigo', 6, 1),
(14, 'Tautliner', 6, 1),
(15, 'Savoyarde', 6, 1),
(16, 'Benne', 6, 1),
(17, 'Porte Container', 6, 1);

INSERT INTO `llx_c_volvo_genre` (`rowid`, `genre`, `rep`, `cv`, `del_rg`) VALUES
(1, 'Porteur', 1, 4, 54),
(2, 'Tracteur Routier', 1, 4, 24),
(3, 'V√©hicule Utilitaire l√©ger', 1, 5, 15),
(4, 'V√©hicule l√©ger', 1, 5, 15),
(5, 'Remorque', 1, 6, 15),
(6, 'Semie Remorque', 1, 6, 15),
(7, 'Ensemble articul√©', 1, 0, 24);

INSERT INTO `llx_c_volvo_marques` (`rowid`, `marque`, `active`, `labelexcel`) VALUES
(1, 'Volvo', 1, 'VOLVO'),
(2, 'Renault', 1, 'RENAULT'),
(3, 'Mercedes', 1, 'MERCEDES'),
(4, 'MAN', 1, 'M.A.N.'),
(5, 'DAF', 1, 'D.A.F.'),
(6, 'Iveco', 1, 'IVECO'),
(7, 'Nissan', 1, 'NISSAN'),
(8, 'Scania', 1, 'SCANIA'),
(9, 'Citroen', 1, 'CITROEN'),
(10, 'Mitsubishi', 1, 'MITSUBISHI'),
(11, 'Opel', 1, 'OPEL'),
(12, 'Peugeot', 1, 'PEUGEOT'),
(13, 'Kaiser', 1, 'KAISER'),
(14, 'Schmitz', 1, 'SCHMITZ'),
(15, 'Trailor', 1, 'TRAILOR');

INSERT INTO `llx_c_volvo_motif_perte_lead` (`rowid`, `motif`) VALUES
(1, 'Prix'),
(2, 'Fidelit√©'),
(3, 'Achat VO'),
(4, 'D√©lais'),
(5, 'Hors Zone'),
(6, 'Mal Suivis'),
(7, 'Produit'),
(8, 'Abandon'),
(9, 'Location'),
(10, 'PB Garantie'),
(11, 'Financement'),
(12, 'R√©ciprocit√©'),
(13, 'Proximit√©'),
(14, 'SAV'),
(15, 'Relationnel'),
(16, 'Protocoles Flottes'),
(17, 'Autres motifs divers'),
(18, 'Valeur reprise VO');

INSERT INTO `llx_c_volvo_silouhette` (`rowid`, `silouhette`, `cv`, `rep`) VALUES
(1, '4x2', 4, 1),
(2, '4x4', 4, 1),
(3, '6x2', 4, 1),
(4, '6x4', 4, 1),
(5, '6x6', 4, 1),
(6, '8x2', 4, 1),
(7, '8x4', 4, 1),
(8, '8x6', 4, 1),
(9, '8x8', 4, 1),
(10, '4x4', 5, 0),
(11, '1 Essieu', 6, 1),
(12, '2 Essieux', 6, 1),
(13, '3 Essieux', 6, 1),
(14, '4x2', 5, 0);






