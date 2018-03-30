<?php
/* Volvo
 * Copyright (C) 2012-2014 Florian Henry <florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file volvo/core/triggers/interface_90_modVolvo_Volvo.class.php
 * \ingroup Volvo
 */

/**
 * Class of triggers Agefodd
 */
class InterfaceAffaires
{
	var $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db handler
	 */
	function __construct($db) {
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "volvo";
		$this->description = "Volvo Triggers";
		$this->version = 'dolibarr'; // 'development', 'experimental', 'dolibarr' or version
		$this->picto = 'technic';
		$this->errors = array();
	}

	/**
	 * Return name of trigger file
	 *
	 * @return string Name of trigger file
	 */
	function getName() {
		return $this->name;
	}

	/**
	 * Return description of trigger file
	 *
	 * @return string Description of trigger file
	 */
	function getDesc() {
		return $this->description;
	}

	/**
	 * Return version of trigger file
	 *
	 * @return string Version of trigger file
	 */
	function getVersion() {
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development')
			return $langs->trans("Development");
		elseif ($this->version == 'experimental')
			return $langs->trans("Experimental");
		elseif ($this->version == 'dolibarr')
			return DOL_VERSION;
		elseif ($this->version)
			return $this->version;
		else
			return $langs->trans("Unknown");
	}

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
	 *
	 * @param string $action code
	 * @param Object $object
	 * @param User $user user
	 * @param Translate $langs langs
	 * @param conf $conf conf
	 * @return int <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	function run_trigger($action, $object, $user, $langs, $conf) {
		if ($action == 'ORDER_DELETE') {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id, LOG_DEBUG);
			$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'affaires_det SET fk_commande=NULL WHERE fk_commande=' . $object->id;
			$resql = $this->db->query($sql);
			if (! $resql) {
				$this->error = $this->db->lasterror;

				dol_syslog(get_class($this) . '::' . __METHOD__ . ' ERROR :' . $this->error, LOG_ERR);
				return - 1;
			}

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id, LOG_DEBUG);
			$sql = 'SELECT DISTINCT sdet.fk_commande FROM ' . MAIN_DB_PREFIX . 'commandedet_extrafields as dete ';
			$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'commandedet as det ON det.rowid=dete.fk_object AND det.fk_commande=' . $object->id;
			$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseurdet as sdet ON sdet.rowid=dete.fk_supplierorderlineid';
			$resql = $this->db->query($sql);
			if (! $resql) {
				$this->error = $this->db->lasterror;
				dol_syslog(get_class($this) . '::' . __METHOD__ . ' ERROR :' . $this->error, LOG_ERR);
				return - 1;
			} else {
				require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
				while ( $obj = $this->db->fetch_object($resql) ) {
					$cmdsup = new CommandeFournisseur($this->db);
					$result = $cmdsup->fetch($obj->fk_commande);
					if ($result < 0) {
						$this->errors = $cmdsup->error;
						return - 1;
					}
					$result = $cmdsup->setStatus($user, CommandeFournisseur::STATUS_DRAFT);
					if ($result < 0) {
						$this->errors = $cmdsup->error;
						return - 1;
					}
					$result = $cmdsup->delete($user);
					if ($result < 0) {
						$this->errors = $cmdsup->error;
						return - 1;
					}
				}
			}

			return 1;
		}

		if ($action == 'ORDER_SUPPLIER_DELETE') {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id, LOG_DEBUG);
			$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'commandedet_extrafields SET fk_supplierorderlineid=NULL WHERE fk_supplierorderlineid IN ';
			$sql .= ' (SELECT rowid FROM ' . MAIN_DB_PREFIX . 'commande_fournisseurdet WHERE fk_commande =' . $object->id . ')';
			$resql = $this->db->query($sql);
			if (! $resql) {
				$this->error = $this->db->lasterror;

				dol_syslog(get_class($this) . '::' . __METHOD__ . ' ERROR :' . $this->error, LOG_ERR);
				return - 1;
			}

			return 1;
		}

		if ($action == 'LINEORDER_SUPPLIER_DELETE') {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id, LOG_DEBUG);
			$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'commandedet_extrafields SET fk_supplierorderlineid=NULL WHERE fk_supplierorderlineid =' . $object->id;
			$resql = $this->db->query($sql);
			if (! $resql) {
				$this->error = $this->db->lasterror;

				dol_syslog(get_class($this) . '::' . __METHOD__ . ' ERROR :' . $this->error, LOG_ERR);
				return - 1;
			}

			return 1;
		}

		if ($action == 'LINEORDER_SUPPLIER_UPDATE') {
			if (! empty($conf->global->VOLVO_FOURN_NOTREAT)) {
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id, LOG_DEBUG);
				// Find if all lines of this supplier order are solde
				$sql = 'SELECT DISTINCT fk_commande FROM ' . MAIN_DB_PREFIX . 'commande_fournisseurdet WHERE rowid=' . $object->id;
				$resql = $this->db->query($sql);
				if (! $resql) {
					$object->error = $this->db->lasterror;

					dol_syslog(get_class($this) . '::' . __METHOD__ . ' ERROR :' . $object->error, LOG_ERR);
					return - 1;
				} else {
					$num = $this->db->num_rows($resql);
					if ($num > 0) {
						$obj = $this->db->fetch_object($resql);
						$sql1 = 'SELECT DISTINCT rowid FROM ' . MAIN_DB_PREFIX . 'commande_fournisseurdet WHERE fk_commande=' . $obj->fk_commande;
						$resql1 = $this->db->query($sql1);
						if (! $resql1) {
							$object->error = $this->db->lasterror;
							dol_syslog(get_class($this) . '::' . __METHOD__ . ' ERROR :' . $object->error, LOG_ERR);
							return - 1;
						} else {
							$num1 = $this->db->num_rows($resql1);
							if ($num1 > 0) {
								$sql2 = 'SELECT DISTINCT d.rowid FROM ' . MAIN_DB_PREFIX . 'commande_fournisseurdet as d';
								$sql2 .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseurdet_extrafields as e ON e.fk_object=d.rowid ';
								$sql2 .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseur as c ON c.rowid=d.fk_commande';
								$sql2 .= ' AND d.fk_commande=' . $obj->fk_commande . ' AND e.solde=1 AND c.fk_soc NOT IN (' . $conf->global->VOLVO_FOURN_NOTREAT.')';
								$resql2 = $this->db->query($sql2);
								if (! $resql2) {
									$object->error = $this->db->lasterror;
									dol_syslog(get_class($this) . '::' . __METHOD__ . ' ERROR :' . $object->error, LOG_ERR);
									return - 1;
								} else {
									$num2 = $this->db->num_rows($resql2);
									if ($num1==$num2) {
										require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
										$cmdsup = new CommandeFournisseur($this->db);
										$cmdsup->fetch($obj->fk_commande);
										if ($cmdsup->id) {
											$result = $cmdsup->setStatus($user, CommandeFournisseur::STATUS_RECEIVED_COMPLETELY);
											if ($result < 0) {
												$object->error = $cmdsup->error;
												return - 1;
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}

		if ($action=='LINEBILL_SUPPLIER_DELETE') {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id, LOG_DEBUG);
			$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'commande_fournisseurdet_extrafields SET solde=NULL WHERE ';
			$sql .= 'fk_object IN (SELECT fk_supplierorderlineid FROM '.MAIN_DB_PREFIX.'facture_fourn_det_extrafields WHERE fk_object=' . $object->id.')';
			$resql = $this->db->query($sql);
			if (! $resql) {
				$this->error = $this->db->lasterror;

				dol_syslog(get_class($this) . '::' . __METHOD__ . ' ERROR :' . $this->error, LOG_ERR);
				return - 1;
			}

			return 1;

			return 1;
		}

		if ($action=='BILL_SUPPLIER_DELETE') {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id, LOG_DEBUG);
			$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'commande_fournisseurdet_extrafields SET solde=NULL WHERE ';
			$sql .= 'fk_object IN (SELECT ffde.fk_supplierorderlineid FROM '.MAIN_DB_PREFIX.'facture_fourn_det_extrafields as ffde';
			$sql .= ' INNER JOIN  '.MAIN_DB_PREFIX.'facture_fourn_det as ffd ON ffd.rowid=ffde.fk_object ';
			$sql .= ' INNER JOIN  '.MAIN_DB_PREFIX.'facture_fourn as ff ON ff.rowid=ffd.fk_facture_fourn AND ff.rowid=' . $object->id.')';
			$resql = $this->db->query($sql);
			if (! $resql) {
				$this->error = $this->db->lasterror;

				dol_syslog(get_class($this) . '::' . __METHOD__ . ' ERROR :' . $this->error, LOG_ERR);
				return - 1;
			}

			return 1;

			return 1;
		}

		return 0;
	}
}