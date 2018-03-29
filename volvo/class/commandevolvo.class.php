<?php
/*
 * Copyright (C) 20016 HENRY Florian <florian.henry@atm-consulting.fr>
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
 * \file volvo/class/commandevolvo.class.php
 * \ingroup volvo
 * \brief Fichier des classes de commandes
 */
include_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';

/**
 * Class to manage customers orders
 */
class CommandeVolvo extends Commande
{
	public $date_cloture;
	public $actiontypecode;
	public $sendtoid;
	public $actionmsg;
	public $date_billed;
	public $total_real_paht;
	public $picto = 'volvo@affaires';

	/**
	 * PAYED
	 */
	const STATUS_PAYED = 4;

	/**
	 * Close order
	 *
	 * @param User $user Objet user that close
	 * @return int <0 if KO, >0 if OK
	 */
	function cloture($user, $notrigger = 0) {
		global $conf;

		$error = 0;

		if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->commande->creer)) || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->commande->order_advance->validate))) {
			$this->db->begin();

			$now = dol_now();

			$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'commande';
			$sql .= ' SET fk_statut = ' . self::STATUS_CLOSED . ',';
			$sql .= ' fk_user_cloture = ' . $user->id . ',';
			$sql .= " date_cloture = '" . $this->db->idate($this->date_cloture) . "'";
			$sql .= ' WHERE rowid = ' . $this->id . ' AND fk_statut > ' . self::STATUS_DRAFT;

			if ($this->db->query($sql)) {
				// Call trigger
				$result = $this->call_trigger('ORDER_CLOSE', $user);
				if ($result < 0)
					$error ++;
				// End call triggers

				if (! $error) {
					$this->createEvent('ORDER_CLOSE');
					if ($result < 0) {
						$error ++;
					}
				}

				if (! $error) {
					$this->statut = self::STATUS_CLOSED;

					$this->db->commit();
					return 1;
				} else {
					$this->db->rollback();
					return - 1;
				}
			} else {
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				$this->db->rollback();
				return - 1;
			}
		}
	}

	/**
	 * Classify the order as invoiced
	 *
	 * @param User $user Object user making the change
	 * @return int <0 if KO, >0 if OK
	 */
	function classifyBilled(User $user, $notrigger = 0) {
		global $user;
		$error = 0;

		$this->db->begin();

		$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'commande SET facture = 1';
		$sql .= ' WHERE rowid = ' . $this->id . ' AND fk_statut > ' . self::STATUS_DRAFT;

		dol_syslog(get_class($this) . "::classifyBilled", LOG_DEBUG);
		if ($this->db->query($sql)) {
			// Call trigger
			$result = $this->call_trigger('ORDER_CLASSIFY_BILLED', $user);
			if ($result < 0)
				$error ++;
			// End call triggers

			if (! $error) {
				$result = $this->createEvent('ORDER_CLASSIFY_BILLED');
				if ($result < 0) {
					$error ++;
				}
			}

			if (! $error) {
				require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
				$extrafields = new ExtraFields($this->db);
				$extralabels = $extrafields->fetch_name_optionals_label($this->table_element, true);
				$this->fetch_optionals($this->id, $extralabels);

				$this->array_options['options_dt_invoice'] = $this->date_billed;
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error ++;
				}
			}

			if (! $error) {
				$this->facturee = 1; // deprecated
				$this->billed = 1;

				$this->db->commit();
				return 1;
			} else {
				foreach ( $this->errors as $errmsg ) {
					dol_syslog(get_class($this) . "::classifyBilled " . $errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
				}
				$this->db->rollback();
				return - 1 * $error;
			}
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return - 1;
		}
	}
	public function createEvent($typeevent = '') {
		global $langs, $user;

		if ($typeevent == 'ORDER_CLOSE') {
			$langs->load("other");
			$langs->load("orders");

			$this->actiontypecode = 'AC_OTH_AUTO';
			if (empty($object->actionmsg2))
				$this->actionmsg2 = $langs->transnoentities("OrderDeliveredInDolibarr", $this->ref);
			$this->actionmsg = $langs->transnoentities("OrderDeliveredInDolibarr", $this->ref);
			$this->actionmsg .= "\n" . $langs->transnoentities("Author") . ': ' . $user->login;

			$this->sendtoid = 0;

			$dateaction = $this->date_cloture;
		} elseif ($typeevent == 'ORDER_CLASSIFY_BILLED') {
			$langs->load("other");
			$langs->load("orders");

			$this->actiontypecode = 'AC_OTH_AUTO';
			if (empty($object->actionmsg2))
				$this->actionmsg2 = $langs->transnoentities("OrderBilledInDolibarr", $this->ref);
			$this->actionmsg = $langs->transnoentities("OrderBilledInDolibarr", $this->ref);
			$this->actionmsg .= "\n" . $langs->transnoentities("Author") . ': ' . $user->login;

			$this->sendtoid = 0;

			$dateaction = $this->date_billed;
		} elseif ($typeevent == 'ORDER_PAYED') {
			$langs->load("other");
			$langs->load("orders");
			$langs->load("volvo@volvo");

			$this->actiontypecode = 'AC_OTH_AUTO';
			if (empty($object->actionmsg2))
				$this->actionmsg2 = $langs->transnoentities("OrderPayedInDolibarr", $this->ref);
			$this->actionmsg = $langs->transnoentities("OrderPayedInDolibarr", $this->ref);
			$this->actionmsg .= "\n" . $langs->transnoentities("Author") . ': ' . $user->login;

			$this->sendtoid = 0;

			$dateaction = $this->date_payed;
		}

		require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
		require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
		$contactforaction = new Contact($this->db);
		$societeforaction = new Societe($this->db);
		if ($object->sendtoid > 0)
			$contactforaction->fetch($this->sendtoid);
		if ($object->socid > 0)
			$societeforaction->fetch($this->socid);

		// Insertion action
		require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
		$actioncomm = new ActionComm($this->db);
		$actioncomm->type_code = $this->actiontypecode; // code of parent table llx_c_actioncomm (will be deprecated)
		$actioncomm->code = 'AC_' . $typeevent;
		$actioncomm->label = $this->actionmsg2;
		$actioncomm->note = $this->actionmsg; // TODO Replace with $actioncomm->email_msgid ? $object->email_content : $object->actionmsg
		$actioncomm->datep = $dateaction;
		$actioncomm->datef = $dateaction;
		$actioncomm->durationp = 0;
		$actioncomm->punctual = 1;
		$actioncomm->percentage = - 1; // Not applicable
		$actioncomm->societe = $societeforaction;
		$actioncomm->contact = $contactforaction;
		$actioncomm->socid = $societeforaction->id;
		$actioncomm->contactid = $contactforaction->id;
		$actioncomm->authorid = $user->id; // User saving action
		$actioncomm->userownerid = $user->id; // Owner of action

		$actioncomm->fk_element = $this->id;
		$actioncomm->elementtype = $this->element;

		$ret = $actioncomm->create($user); // User creating action

		unset($object->actionmsg);
		unset($object->actionmsg2);
		unset($object->actiontypecode); // When several action are called on same object, we must be sure to not reuse value of first action.

		if ($ret < 0) {
			$this->errors = $actioncomm->errors;
			if (! empty($actioncomm->error)) {
				$this->errors[] = $actioncomm->error;
			}
			return - 1;
		} else {
			return 1;
		}
	}

	/**
	 *
	 * @param unknown $orderid
	 * @param unknown $type
	 * @return number
	 */
	public function getCostPriceReal($orderid, $type = 'real') {
		require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

		$extrafieldslines = new Extrafields($this->db);
		$extralabelslines = $extrafieldslines->fetch_name_optionals_label($this->table_element_line);

		$result = $this->fetch($orderid);
		if ($result < 0) {
			return - 1;
		}

		$this->total_real_paht = 0;
		// Calc other margin
		if (is_array($this->lines) && count($this->lines)) {
			foreach ( $this->lines as $line ) {
				$line->fetch_optionals($line->id, $extralabelslines);

				$realpa = price2num($line->array_options['options_buyingprice_real']);
				if ($type == 'real') {
					if (! empty($line->array_options['options_fk_supplier']) || ! empty($line->array_options['options_dt_invoice'])) {
						$this->total_real_paht += $realpa;
					} elseif (! empty($line->pa_ht)) {
						$this->total_real_paht += $line->pa_ht * $line->qty;
					}
				} elseif ($type == 'theo') {
					if (! empty($line->pa_ht)) {
						$this->total_real_paht += $line->pa_ht * $line->qty;
					}
				}
			}
		}

		return 1;
	}

	/**
	 * Payed order
	 *
	 * @param User $user Objet user that close
	 * @return int <0 if KO, >0 if OK
	 */
	public function setpayed($user) {
		global $conf;

		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->commande->creer)) || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->commande->order_advance->validate))) {
			$this->db->begin();

			$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'commande';
			$sql .= ' SET fk_statut = ' . self::STATUS_PAYED;
			$sql .= ' WHERE rowid = ' . $this->id;

			dol_syslog(__METHOD__, LOG_DEBUG);
			if ($this->db->query($sql)) {
				// Call trigger
				$result = $this->call_trigger('ORDER_PAYED', $user);
				if ($result < 0)
					$error ++;
				// End call triggers

				if (! $error) {
					$result = $this->createEvent('ORDER_PAYED');
					if ($result < 0) {
						$error ++;
					}
				}

				if (! $error) {
					$this->statut = self::STATUS_PAYED;

					$this->db->commit();
					return 1;
				} else {
					$this->db->rollback();
					return - 1;
				}
			} else {
				$this->errors[] = $this->db->lasterror();

				$this->db->rollback();
				return - 1;
			}
		}
	}

	/**
	 * Create supplier order from
	 *
	 * @return number
	 */
	public function createSupplierOrder($user, $pricefourn_qty_array = array(), $orderid) {
		global $conf;

		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
		require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';

		$sp = new ProductFournisseur($this->db);

		$result = $this->fetch($orderid);
		if ($result < 0) {
			return - 1;
		}

		$this->db->begin();
		if (is_array($pricefourn_qty_array) && count($pricefourn_qty_array) > 0) {

			$fourn_array = array();
			foreach ( $pricefourn_qty_array as $priceid => $detail ) {
				$result = $sp->fetch_product_fournisseur_price($priceid);
				if ($result < 0) {
					$this->errors[] = 'Error fetch price fourn';
					$error ++;
				}

				$fourn_array[$sp->fourn_id][$detail['suplierorderid']][] = array(
						'productid' => $sp->fk_product,
						'qty' => $detail['qty'],
						'price' => $sp->fourn_price,
						'ref_supplier' => $sp->ref_supplier,
						'tva_tx' => $sp->fourn_tva_tx,
						'desc' => $detail['desc'],
						'px' => $detail['px'],
						'origorderlineid' => $detail['lineid']
				);
			}
			if (count($fourn_array) > 0 && empty($error)) {

				foreach ( $fourn_array as $fournid => $detailorder ) {
					//Find already exist supplier order for the same supplier on customer client
					$sql = 'SELECT DISTINCT so.rowid as supplierorderid ';
					$sql .= ' FROM '.MAIN_DB_PREFIX.'societe as supplier';
					$sql .= ' INNER JOIN  '.MAIN_DB_PREFIX.'commande_fournisseur as so ON so.fk_soc=supplier.rowid AND supplier.rowid='.$fournid;
					$sql .= ' INNER JOIN  '.MAIN_DB_PREFIX.'commande_fournisseurdet as sod ON sod.fk_commande=so.rowid';
					$sql .= ' INNER JOIN  '.MAIN_DB_PREFIX.'commandedet_extrafields as cdetextra ON cdetextra.fk_supplierorderlineid=sod.rowid';
					$sql .= ' INNER JOIN  '.MAIN_DB_PREFIX.'commandedet as cdet ON cdet.rowid=cdetextra.fk_object';
					$sql .= ' INNER JOIN  '.MAIN_DB_PREFIX.'commande as c ON c.rowid=cdet.fk_commande AND c.rowid='.$orderid;

					$resql = $this->db->query($sql);
					if (!$resql) {
						$this->errors[] =$this->db->lasterror();
						$error ++;
					}

					if (empty($error)) {
						while($obj = $this->db->fetch_object($resql)) {
							if (!empty($obj->supplierorderid)) {
								foreach ( $detailorder as $exiintgsupplierorderid => $prodinfo ) {
									if ($exiintgsupplierorderid==0) {
										foreach ( $prodinfo as $keydt=>$data ) {
											//On reaffecte les bons éléments dans le tableau
											$fourn_array[$fournid][$obj->supplierorderid][]=$data;
											unset($fourn_array[$fournid][0][$keydt]);
										}
									}
								}
							}
						}
					}
				}
				foreach ( $fourn_array as $fournid => $detailorder ) {

					if (is_array($detailorder) && count($detailorder) > 0) {
						foreach ( $detailorder as $supplierorderid => $prodinfo ) {

							if (is_array($prodinfo) && count($prodinfo) > 0) {

								$cmdsup = new CommandeFournisseur($this->db);

								// Find if order oring line is already link to supplier order
								if (! empty($supplierorderid)) {
									$result = $cmdsup->fetch($supplierorderid);
									if ($result < 0) {
										$this->errors[] = $cmdsup->error;
										$error ++;
									}
									$result = $cmdsup->setStatus($user, CommandeFournisseur::STATUS_DRAFT);
									if ($result < 0) {
										$this->errors[] = $cmdsup->error;
										$error ++;
									}
									//Update Note puplic
									if (strpos($cmdsup->note_public,'Annule et remplace la précédante')===false) {
										$result=$cmdsup->update_note($cmdsup->note_public."\r\n".'Annule et remplace la précédante','_public');
										if ($result < 0) {
											$this->errors[] = $cmdsup->error;
											$error ++;
										}
									}

									foreach ( $prodinfo as $data ) {
										$ordersuplineid = $cmdsup->addline($data['desc'], $data['px'], $data['qty'], $data['tva_tx'], 0, 0, $data['productid'], 0, $data['ref_supplier'], 0, 'HT', 0, 0, 0, false, null, null, 0, null);
										if ($ordersuplineid < 0) {
											$this->errors[] = $cmdsup->error;
											$error ++;
										}

										//Affecte le rowid de la ligne fourn crée à l'attribut supp de la ligne  de commande client
										$commande_origin_line = new OrderLine($this->db);
										$result = $commande_origin_line->fetch($data['origorderlineid']);
										if ($result < 0) {
											$error ++;
											$this->errors[] = $commande_origin_line->error;
										} else {
											$commande_origin_line->fetch_optionals($data['origorderlineid']);
											if (empty($commande_origin_line->array_options['options_fk_supplierorderlineid'])) {
												$commande_origin_line->array_options['options_fk_supplierorderlineid'] = $ordersuplineid;
												$result = $commande_origin_line->update($user);
												if ($result < 0) {
													$error ++;
													$this->errors[] = $commande_origin_line->error;
												}
											}
										}
									}
								} else {
									$cmdsup->ref_supplier = $this->ref;
									$cmdsup->socid = $fournid;
									$cmdsup->source = $this->id;
									$cmdsup->array_options['options_ctm'] = $this->array_options['options_ctm'];

									$cmdsup->linked_objects["commande"] = $this->id;

									$result = $cmdsup->create($user);
									if ($result < 0) {
										$this->errors[] = $cmdsup->error;
										$error ++;
									}

									foreach ( $prodinfo as $data ) {

										$ordersuplineid = $cmdsup->addline($data['desc'], $data['px'], $data['qty'], $data['tva_tx'], 0, 0, $data['productid'], 0, $data['ref_supplier'], 0, 'HT', 0, 0, 0, false, null, null, 0, null);
										if ($ordersuplineid < 0) {
											$this->errors[] = $cmdsup->error;
											$error ++;
										}
										//Affecte le rowid de la ligne fourn crée à l'attribut supp de la ligne  de commande client
										$commande_origin_line = new OrderLine($this->db);
										$result = $commande_origin_line->fetch($data['origorderlineid']);
										if ($result < 0) {
											$error ++;
											$this->errors[] = $commande_origin_line->error;
										} else {
											$commande_origin_line->fetch_optionals($data['origorderlineid']);
											if (empty($commande_origin_line->array_options['options_fk_supplierorderlineid'])) {
												$commande_origin_line->array_options['options_fk_supplierorderlineid'] = $ordersuplineid;
												$result = $commande_origin_line->update($user);
												if ($result < 0) {
													$error ++;
													$this->errors[] = $commande_origin_line->error;
												}
											}
										}
									}
								}
								// var_dump($cmdsup);
								if (empty($error)) {
									$result = $cmdsup->update_price($user);
									if ($result < 0) {
										$error ++;
										$this->errors[] = $commande_origin_line->error;
									}
								}
							}
						}
					}
				}
			}
		}
		if (! $error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return - 1 * $error;
		}
	}
	function printObjectLines_perso($action, $seller, $buyer, $selected = 0, $dateSelector = 0) {
		global $conf, $hookmanager, $langs, $user;
		// TODO We should not use global var for this !
		global $inputalsopricewithtax, $usemargins, $disableedit, $disablemove, $disableremove;

		print '<tr class="liste_titre nodrag nodrop">';

		// Description
		print '<td class="linecoldescription" colspan="2">' . $langs->trans('Description') . '</td>';

		// Qty
		print '<td class="linecolqty" align="right">' . $langs->trans('Qty') . '</td>';

		// Price HT
		print '<td class="linecoluht" align="right" width="80">' . $langs->trans('PriceUHT') . '</td>';

		// Prix d'achat
		print '<td class="linecolht" align="right">' . $langs->trans('Prix achat') . '</td>';

		// Cout réel
		print '<td class="linecolht" align="right">' . $langs->trans('Cout Réel') . '</td>';

		// Ecart
		print '<td class="linecolht" align="right">' . $langs->trans('Ecart') . '</td>';

		print '<td class="linecoledit"></td>'; // No width to allow autodim

		print '<td class="linecoldelete" width="10"></td>';

		print '<td class="linecolmove" width="10"></td>';

		print "</tr>";
		print '<tr class="liste_titre nodrag nodrop">';

		// fournisseur
		print '<td class="linecolqty" align="left">' . $langs->trans('Fournisseur') . '</td>';

		// date
		print '<td class="linecolqty" align="left" colspan="2">' . $langs->trans('Date de facture') . '</td>';

		print '<td colspan="7"></td>';

		print "</tr>";

		$num = count($this->lines);
		$var = true;
		$i = 0;

		// Line extrafield
		require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
		$extrafieldsline = new ExtraFields($this->db);
		$extralabelslines = $extrafieldsline->fetch_name_optionals_label($this->table_element_line);

		foreach ( $this->lines as $line ) {
			// Line extrafield
			$line->fetch_optionals($line->id, $extralabelslines);

			$var = ! $var;

			// if (is_object($hookmanager) && (($line->product_type == 9 && ! empty($line->special_code)) || ! empty($line->fk_parent_line)))
			if (is_object($hookmanager)) // Old code is commented on preceding line.
			{
				if (empty($line->fk_parent_line)) {
					$parameters = array(
							'line' => $line,
							'var' => $var,
							'num' => $num,
							'i' => $i,
							'dateSelector' => $dateSelector,
							'seller' => $seller,
							'buyer' => $buyer,
							'selected' => $selected,
							'extrafieldsline' => $extrafieldsline
					);
					$reshook = $hookmanager->executeHooks('printObjectLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				} else {
					$parameters = array(
							'line' => $line,
							'var' => $var,
							'num' => $num,
							'i' => $i,
							'dateSelector' => $dateSelector,
							'seller' => $seller,
							'buyer' => $buyer,
							'selected' => $selected,
							'extrafieldsline' => $extrafieldsline
					);
					$reshook = $hookmanager->executeHooks('printObjectSubLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				}
			}
			if (empty($reshook)) {
				$this->printObjectLine_perso($action, $line, $var, $num, $i, $dateSelector, $seller, $buyer, $selected, $extrafieldsline);
			}

			$i ++;
		}
	}

	/**
	 * Return HTML content of a detail line
	 * TODO Move this into an output class file (htmlline.class.php)
	 *
	 * @param string $action GET/POST action
	 * @param CommonObjectLine $line Selected object line to output
	 * @param string $var Is it a an odd line (true)
	 * @param int $num Number of line (0)
	 * @param int $i I
	 * @param int $dateSelector 1=Show also date range input fields
	 * @param string $seller Object of seller third party
	 * @param string $buyer Object of buyer third party
	 * @param int $selected Object line selected
	 * @param int $extrafieldsline Object of extrafield line attribute
	 * @return void
	 */
	function printObjectLine_perso($action, $line, $var, $num, $i, $dateSelector, $seller, $buyer, $selected = 0, $extrafieldsline = 0) {
		global $conf, $langs, $user, $object, $hookmanager;
		global $form, $bc, $bcdd;
		global $object_rights, $disableedit, $disablemove; // TODO We should not use global var for this !

		$object_rights = $this->getRights();

		$element = $this->element;

		$text = '';
		$description = '';
		$type = 0;

		// Show product and description
		$type = (! empty($line->product_type) ? $line->product_type : $line->fk_product_type);
		// Try to enhance type detection using date_start and date_end for free lines where type was not saved.
		if (! empty($line->date_start))
			$type = 1; // deprecated
		if (! empty($line->date_end))
			$type = 1; // deprecated

		// Ligne en mode visu
		if ($action != 'editline' || $selected != $line->id) {
			// Product
			if ($line->fk_product > 0) {
				$product_static = new Product($this->db);
				$product_static->fetch($line->fk_product);

				$product_static->ref = $line->ref; // can change ref in hook
				$product_static->label = $line->label; // can change label in hook
				$text = $product_static->getNomUrl(1);

				// Define output language and label
				if (! empty($conf->global->MAIN_MULTILANGS)) {
					if (! is_object($this->thirdparty)) {
						dol_print_error('', 'Error: Method printObjectLine was called on an object and object->fetch_thirdparty was not done before');
						return;
					}

					$prod = new Product($this->db);
					$prod->fetch($line->fk_product);

					$outputlangs = $langs;
					$newlang = '';
					if (empty($newlang) && GETPOST('lang_id'))
						$newlang = GETPOST('lang_id');
					if (! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE) && empty($newlang))
						$newlang = $this->thirdparty->default_lang; // For language to language of customer
					if (! empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$label = (! empty($prod->multilangs[$outputlangs->defaultlang]["label"])) ? $prod->multilangs[$outputlangs->defaultlang]["label"] : $line->product_label;
				} else {
					$label = $line->product_label;
				}

				$text .= ' - ' . (! empty($line->label) ? $line->label : $label);
				$description .= (! empty($conf->global->PRODUIT_DESC_IN_FORM) ? '' : dol_htmlentitiesbr($line->description)); // Description is what to show on popup. We shown nothing if already into desc.
			}

			$line->pu_ttc = price2num($line->subprice * (1 + ($line->tva_tx / 100)), 'MU');

			// Output template part (modules that overwrite templates must declare this into descriptor)
			// Use global variables + $dateSelector + $seller and $buyer
			$dirtpls = array_merge($conf->modules_parts['tpl'], array(
					'/core/tpl'
			));
			foreach ( $dirtpls as $reldir ) {
				$tpl = dol_buildpath($reldir . '/objectline_view.tpl.php');
				if (empty($conf->file->strict_mode)) {
					$res = @include $tpl;
				} else {
					$res = include $tpl; // for debug
				}
				if ($res)
					break;
			}
		}

		// Ligne en mode update
		if ($action == 'editline' && $selected == $line->id) {
			$label = (! empty($line->label) ? $line->label : (($line->fk_product > 0) ? $line->product_label : ''));
			if (! empty($conf->global->MAIN_HTML5_PLACEHOLDER))
				$placeholder = ' placeholder="' . $langs->trans("Label") . '"';
			else
				$placeholder = ' title="' . $langs->trans("Label") . '"';

			$line->pu_ttc = price2num($line->subprice * (1 + ($line->tva_tx / 100)), 'MU');

			// Output template part (modules that overwrite templates must declare this into descriptor)
			// Use global variables + $dateSelector + $seller and $buyer
			$dirtpls = array_merge($conf->modules_parts['tpl'], array(
					'/core/tpl'
			));
			foreach ( $dirtpls as $reldir ) {
				$tpl = dol_buildpath($reldir . '/objectline_edit.tpl.php');
				if (empty($conf->file->strict_mode)) {
					$res = @include $tpl;
				} else {
					$res = include $tpl; // for debug
				}
				if ($res)
					break;
			}
		}
	}
	function LibStatut($statut, $billed, $mode, $donotshowbilled = 0) {
		global $langs, $conf;

		$billedtext = '';
		if (empty($donotshowbilled))
			$billedtext .= ($billed ? ' - ' . $langs->trans("Billed") : '');

		// print 'x'.$statut.'-'.$billed;
		if ($mode == 0) {
			if ($statut == self::STATUS_CANCELED)
				return $langs->trans('StatusOrderCanceled');
			if ($statut == self::STATUS_DRAFT)
				return $langs->trans('StatusOrderDraft');
			if ($statut == self::STATUS_VALIDATED)
				return $langs->trans('StatusOrderValidated') . $billedtext;
			if ($statut == self::STATUS_ACCEPTED)
				return $langs->trans('StatusOrderSentShort') . $billedtext;
			if ($statut == self::STATUS_CLOSED && (! $billed && empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)))
				return $langs->trans('StatusOrderToBill');
			if ($statut == self::STATUS_CLOSED && ($billed && empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)))
				return $langs->trans('StatusOrderProcessed') . $billedtext;
			if ($statut == self::STATUS_CLOSED && (! empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)))
				return $langs->trans('StatusOrderDelivered');
			if ($statut == self::STATUS_PAYED)
				return $langs->trans('StatusOrderPayed');
		} elseif ($mode == 1) {
			if ($statut == self::STATUS_CANCELED)
				return $langs->trans('StatusOrderCanceledShort');
			if ($statut == self::STATUS_DRAFT)
				return $langs->trans('StatusOrderDraftShort');
			if ($statut == self::STATUS_VALIDATED)
				return $langs->trans('StatusOrderValidatedShort') . $billedtext;
			if ($statut == self::STATUS_ACCEPTED)
				return $langs->trans('StatusOrderSentShort') . $billedtext;
			if ($statut == self::STATUS_CLOSED && (! $billed && empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)))
				return $langs->trans('StatusOrderToBillShort');
			if ($statut == self::STATUS_CLOSED && ($billed && empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)))
				return $langs->trans('StatusOrderProcessed') . $billedtext;
			if ($statut == self::STATUS_CLOSED && (! empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)))
				return $langs->trans('StatusOrderDelivered');
			if ($statut == self::STATUS_PAYED)
				return $langs->trans('StatusOrderPayed');
		} elseif ($mode == 2) {
			if ($statut == self::STATUS_CANCELED)
				return img_picto($langs->trans('StatusOrderCanceled'), 'statut5') . ' ' . $langs->trans('StatusOrderCanceledShort');
			if ($statut == self::STATUS_DRAFT)
				return img_picto($langs->trans('StatusOrderDraft'), 'statut0') . ' ' . $langs->trans('StatusOrderDraftShort');
			if ($statut == self::STATUS_VALIDATED)
				return img_picto($langs->trans('StatusOrderValidated'), 'statut1') . ' ' . $langs->trans('StatusOrderValidatedShort') . $billedtext;
			if ($statut == self::STATUS_ACCEPTED)
				return img_picto($langs->trans('StatusOrderSent'), 'statut3') . ' ' . $langs->trans('StatusOrderSentShort') . $billedtext;
			if ($statut == self::STATUS_CLOSED && (! $billed && empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)))
				return img_picto($langs->trans('StatusOrderToBill'), 'statut7') . ' ' . $langs->trans('StatusOrderToBillShort');
			if ($statut == self::STATUS_CLOSED && ($billed && empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)))
				return img_picto($langs->trans('StatusOrderProcessed') . $billedtext, 'statut6') . ' ' . $langs->trans('StatusOrderProcessed') . $billedtext;
			if ($statut == self::STATUS_CLOSED && (! empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)))
				return img_picto($langs->trans('StatusOrderDelivered'), 'statut6') . ' ' . $langs->trans('StatusOrderDeliveredShort');
			if ($statut == self::STATUS_PAYED)
				return img_picto($langs->trans('StatusOrderPayed'), 'statut6') . ' ' . $langs->trans('StatusOrderPayed');
		} elseif ($mode == 3) {
			if ($statut == self::STATUS_CANCELED)
				return img_picto($langs->trans('StatusOrderCanceled'), 'statut5');
			if ($statut == self::STATUS_DRAFT)
				return img_picto($langs->trans('StatusOrderDraft'), 'statut0');
			if ($statut == self::STATUS_VALIDATED)
				return img_picto($langs->trans('StatusOrderValidated') . $billedtext, 'statut1');
			if ($statut == self::STATUS_ACCEPTED)
				return img_picto($langs->trans('StatusOrderSentShort') . $billedtext, 'statut3');
			if ($statut == self::STATUS_CLOSED && (! $billed && empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)))
				return img_picto($langs->trans('StatusOrderToBill'), 'statut7');
			if ($statut == self::STATUS_CLOSED && ($billed && empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)))
				return img_picto($langs->trans('StatusOrderProcessed') . $billedtext, 'statut6');
			if ($statut == self::STATUS_CLOSED && (! empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)))
				return img_picto($langs->trans('StatusOrderDelivered'), 'statut6');
			if ($statut == self::STATUS_PAYED)
				return img_picto($langs->trans('StatusOrderPayed'), 'statut6');
		} elseif ($mode == 4) {
			if ($statut == self::STATUS_CANCELED)
				return img_picto($langs->trans('StatusOrderCanceled'), 'statut5') . ' ' . $langs->trans('StatusOrderCanceled');
			if ($statut == self::STATUS_DRAFT)
				return img_picto($langs->trans('StatusOrderDraft'), 'statut0') . ' ' . $langs->trans('StatusOrderDraft');
			if ($statut == self::STATUS_VALIDATED)
				return img_picto($langs->trans('StatusOrderValidated') . $billedtext, 'statut1') . ' ' . $langs->trans('StatusOrderValidated') . $billedtext;
			if ($statut == self::STATUS_ACCEPTED)
				return img_picto($langs->trans('StatusOrderSentShort') . $billedtext, 'statut3') . ' ' . $langs->trans('StatusOrderSent') . $billedtext;
			if ($statut == self::STATUS_CLOSED && (! $billed && empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)))
				return img_picto($langs->trans('StatusOrderToBill'), 'statut7') . ' ' . $langs->trans('StatusOrderToBill');
			if ($statut == self::STATUS_CLOSED && ($billed && empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)))
				return img_picto($langs->trans('StatusOrderProcessedShort') . $billedtext, 'statut6') . ' ' . $langs->trans('StatusOrderProcessed') . $billedtext;
			if ($statut == self::STATUS_CLOSED && (! empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)))
				return img_picto($langs->trans('StatusOrderDelivered'), 'statut6') . ' ' . $langs->trans('StatusOrderDelivered');
			if ($statut == self::STATUS_PAYED)
				return img_picto($langs->trans('StatusOrderPayed'), 'statut6') . ' ' . $langs->trans('StatusOrderPayed');
		} elseif ($mode == 5) {
			if ($statut == self::STATUS_CANCELED)
				return '<span class="hideonsmartphone">' . $langs->trans('StatusOrderCanceledShort') . ' </span>' . img_picto($langs->trans('StatusOrderCanceled'), 'statut5');
			if ($statut == self::STATUS_DRAFT)
				return '<span class="hideonsmartphone">' . $langs->trans('StatusOrderDraftShort') . ' </span>' . img_picto($langs->trans('StatusOrderDraft'), 'statut0');
			if ($statut == self::STATUS_VALIDATED)
				return '<span class="hideonsmartphone">' . $langs->trans('StatusOrderValidatedShort') . $billedtext . ' </span>' . img_picto($langs->trans('StatusOrderValidated') . $billedtext, 'statut1');
			if ($statut == self::STATUS_ACCEPTED)
				return '<span class="hideonsmartphone">' . $langs->trans('StatusOrderSentShort') . $billedtext . ' </span>' . img_picto($langs->trans('StatusOrderSent') . $billedtext, 'statut3');
			if ($statut == self::STATUS_CLOSED && (! $billed && empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)))
				return '<span class="hideonsmartphone">' . $langs->trans('StatusOrderToBillShort') . ' </span>' . img_picto($langs->trans('StatusOrderToBill'), 'statut7');
			if ($statut == self::STATUS_CLOSED && ($billed && empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)))
				return '<span class="hideonsmartphone">' . $langs->trans('StatusOrderProcessedShort') . $billedtext . ' </span>' . img_picto($langs->trans('StatusOrderProcessed') . $billedtext, 'statut6');
			if ($statut == self::STATUS_CLOSED && (! empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)))
				return '<span class="hideonsmartphone">' . $langs->trans('StatusOrderDeliveredShort') . ' </span>' . img_picto($langs->trans('StatusOrderDelivered'), 'statut6');
			if ($statut == self::STATUS_PAYED)
				return '<span class="hideonsmartphone">' . $langs->trans('StatusOrderPayed') . ' </span>' . img_picto($langs->trans('StatusOrderPayed'), 'statut6');
		}
	}
	function deleteline($user = NULL, $lineid = 0) {
		global $user, $conf, $lang;
		// if ($this->statut == self::STATUS_DRAFT)
		// {
		$this->db->begin();

		$sql = "SELECT fk_product, qty";
		$sql .= " FROM " . MAIN_DB_PREFIX . "commandedet";
		$sql .= " WHERE rowid = " . $lineid;

		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);

			if ($obj) {
				$product = new Product($this->db);
				$product->id = $obj->fk_product;

				// Delete line
				$line = new OrderLine($this->db);

				// For triggers
				$line->fetch($lineid);

				if ($line->delete() > 0) {
					$result = $this->update_price(1);

					if ($result > 0) {
						if (! $error && ! $notrigger) {
							// Call trigger
							$result = $this->call_trigger('ORDER_LINE_DELETE', $user);
							if ($result < 0)
								$error ++;
							// End call triggers
						}
						$this->db->commit();
						// Call trigger
						$result = $this->call_trigger('ORDER_DELETE_LINE', $user);
						if ($result < 0)
							$error ++;
						// End call triggers
						return 1;
					} else {
						$this->db->rollback();
						$this->error = $this->db->lasterror();
						return - 2;
					}
				} else {
					$this->db->rollback();
					$this->error = $line->error;
					return - 1;
				}
			} else {
				$this->db->rollback();
				return 0;
			}
		} else {
			$this->db->rollback();
			$this->error = $this->db->lasterror();
			return - 3;
		}
		// }
		// else
		// {
		// return -4;
		// }
	}
	function updateline($rowid, $desc, $pu, $qty, $remise_percent, $txtva, $txlocaltax1 = 0.0, $txlocaltax2 = 0.0, $price_base_type = 'HT', $info_bits = 0, $date_start = '', $date_end = '', $type = 0, $fk_parent_line = 0, $skip_update_total = 0, $fk_fournprice = null, $pa_ht = 0, $label = '', $special_code = 0, $array_options = 0, $fk_unit = null, $pu_ht_devise = 0, $notrigger = 0) {
		global $conf, $mysoc, $langs, $user;

		dol_syslog(
				get_class($this) . "::updateline id=$rowid, desc=$desc, pu=$pu, qty=$qty, remise_percent=$remise_percent, txtva=$txtva, txlocaltax1=$txlocaltax1, txlocaltax2=$txlocaltax2, price_base_type=$price_base_type, info_bits=$info_bits, date_start=$date_start, date_end=$date_end, type=$type, fk_parent_line=$fk_parent_line, pa_ht=$pa_ht, special_code=$special_code");
		include_once DOL_DOCUMENT_ROOT . '/core/lib/price.lib.php';

		// if (! empty($this->brouillon))
		// {
		$this->db->begin();

		// Clean parameters
		if (empty($qty))
			$qty = 0;
		if (empty($info_bits))
			$info_bits = 0;
		if (empty($txtva))
			$txtva = 0;
		if (empty($txlocaltax1))
			$txlocaltax1 = 0;
		if (empty($txlocaltax2))
			$txlocaltax2 = 0;
		if (empty($remise))
			$remise = 0;
		if (empty($remise_percent))
			$remise_percent = 0;
		if (empty($special_code) || $special_code == 3)
			$special_code = 0;

		$remise_percent = price2num($remise_percent);
		$qty = price2num($qty);
		$pu = price2num($pu);
		$pa_ht = price2num($pa_ht);
		$txtva = price2num($txtva);
		$txlocaltax1 = price2num($txlocaltax1);
		$txlocaltax2 = price2num($txlocaltax2);

		// Calcul du total TTC et de la TVA pour la ligne a partir de
		// qty, pu, remise_percent et txtva
		// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
		// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

		$localtaxes_type = getLocalTaxesFromRate($txtva, 0, $this->thirdparty, $mysoc);
		$txtva = preg_replace('/\s*\(.*\)/', '', $txtva); // Remove code into vatrate.

		$tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type, $mysoc, $localtaxes_type, 100, $this->multicurrency_tx);

		$total_ht = $tabprice[0];
		$total_tva = $tabprice[1];
		$total_ttc = $tabprice[2];
		$total_localtax1 = $tabprice[9];
		$total_localtax2 = $tabprice[10];

		// MultiCurrency
		$multicurrency_total_ht = $tabprice[16];
		$multicurrency_total_tva = $tabprice[17];
		$multicurrency_total_ttc = $tabprice[18];

		// Anciens indicateurs: $price, $subprice, $remise (a ne plus utiliser)
		$price = $pu;
		if ($price_base_type == 'TTC') {
			$subprice = $tabprice[5];
		} else {
			$subprice = $pu;
		}
		$remise = 0;
		if ($remise_percent > 0) {
			$remise = round(($pu * $remise_percent / 100), 2);
			$price = ($pu - $remise);
		}

		// Fetch current line from the database and then clone the object and set it in $oldline property
		$line = new OrderLine($this->db);
		$line->fetch($rowid);

		if (! empty($line->fk_product)) {
			$product = new Product($this->db);
			$result = $product->fetch($line->fk_product);
			$product_type = $product->type;

			if (! empty($conf->global->STOCK_MUST_BE_ENOUGH_FOR_ORDER) && $product_type == 0 && $product->stock_reel < $qty) {
				$langs->load("errors");
				$this->error = $langs->trans('ErrorStockIsNotEnoughToAddProductOnOrder', $product->ref);
				dol_syslog(get_class($this) . "::addline error=Product " . $product->ref . ": " . $this->error, LOG_ERR);
				$this->db->rollback();
				unset($_POST['productid']);
				unset($_POST['tva_tx']);
				unset($_POST['price_ht']);
				unset($_POST['qty']);
				unset($_POST['buying_price']);
				return self::STOCK_NOT_ENOUGH_FOR_ORDER;
			}
		}

		$staticline = clone $line;

		$line->oldline = $staticline;
		$this->line = $line;
		$this->line->context = $this->context;

		// Reorder if fk_parent_line change
		if (! empty($fk_parent_line) && ! empty($staticline->fk_parent_line) && $fk_parent_line != $staticline->fk_parent_line) {
			$rangmax = $this->line_max($fk_parent_line);
			$this->line->rang = $rangmax + 1;
		}

		$this->line->rowid = $rowid;
		$this->line->label = $label;
		$this->line->desc = $desc;
		$this->line->qty = $qty;
		$this->line->tva_tx = $txtva;
		$this->line->localtax1_tx = $txlocaltax1;
		$this->line->localtax2_tx = $txlocaltax2;
		$this->line->localtax1_type = $localtaxes_type[0];
		$this->line->localtax2_type = $localtaxes_type[2];
		$this->line->remise_percent = $remise_percent;
		$this->line->subprice = $subprice;
		$this->line->info_bits = $info_bits;
		$this->line->special_code = $special_code;
		$this->line->total_ht = $total_ht;
		$this->line->total_tva = $total_tva;
		$this->line->total_localtax1 = $total_localtax1;
		$this->line->total_localtax2 = $total_localtax2;
		$this->line->total_ttc = $total_ttc;
		$this->line->date_start = $date_start;
		$this->line->date_end = $date_end;
		$this->line->product_type = $type;
		$this->line->fk_parent_line = $fk_parent_line;
		$this->line->skip_update_total = $skip_update_total;
		$this->line->fk_unit = $fk_unit;

		$this->line->fk_fournprice = $fk_fournprice;
		$this->line->pa_ht = $pa_ht;

		// Multicurrency
		$this->line->multicurrency_subprice = price2num($subprice * $this->multicurrency_tx);
		$this->line->multicurrency_total_ht = $multicurrency_total_ht;
		$this->line->multicurrency_total_tva = $multicurrency_total_tva;
		$this->line->multicurrency_total_ttc = $multicurrency_total_ttc;

		// TODO deprecated
		$this->line->price = $price;
		$this->line->remise = $remise;

		if (is_array($array_options) && count($array_options) > 0) {
			$this->line->array_options = $array_options;
		}

		$result = $this->line->update($user);
		if ($result > 0) {
			// Reorder if child line
			if (! empty($fk_parent_line))
				$this->line_order(true, 'DESC');

			// Mise a jour info denormalisees
			$this->update_price(1);

			$this->db->commit();
			// Call trigger
			$result = $this->call_trigger('ORDER_CLASSIFY_UNBILLED', $user);
			if ($result < 0)
				$error ++;
			// End call triggers
			return $result;
		} else {
			$this->error = $this->line->error;

			$this->db->rollback();
			return - 1;
		}
		// }
		// else
		// {
		// $this->error=get_class($this)."::updateline Order status makes operation forbidden";
		// $this->errors=array('OrderStatusMakeOperationForbidden');
		// return -2;
		// }
	}
	public function get_cash() {
		$sql = "SELECT";
		$sql .= " c.rowid AS comid,";
		$sql .= " event6.datep AS dt_recep,";
		$sql .= " event5.datep AS dt_pay,";
		$sql .= " DATEDIFF(IFNULL(event5.datep,CURDATE()),event6.datep) AS delai_cash";
		$sql .= " FROM " . MAIN_DB_PREFIX . "commande as c ";
		$sql .= " LEFT JOIN llx_element_element as el ON el.fk_source = c.rowid AND sourcetype = 'commande' AND targettype = 'order_supplier'";
		$sql .= " LEFT JOIN llx_commande_fournisseur as cf on cf.rowid = el.fk_target";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "actioncomm as event5 on event5.fk_element = c.rowid AND event5.elementtype = 'order ' AND event5.label LIKE '%Payée%'";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "actioncomm as event6 on event6.fk_element = cf.rowid AND event6.elementtype = 'order_supplier' AND event6.label LIKE '%reçue%'";
		$sql .= " WHERE c.rowid = " . $this->id . " AND cf.fk_soc = 32553";

		$resql = $this->db->query($sql);
		if ($resql) {
			$res = $this->db->fetch_object($resql);
			return $res->delai_cash;
		} else {
			return null;
		}
	}
	public function find_dt_cmd($type) {
		switch ($type) {
			case 'dt_pay' :
				$sql = "SELECT MAX(datep) as dt ";
				$sql .= "FROM " . MAIN_DB_PREFIX . "actioncomm ";
				$sql .= "WHERE fk_element =" . $this->id . " ";
				$sql .= "AND elementtype = 'order' ";
				$sql .= "AND label LIKE '%Commande V% classée Payée%'";
				break;

			case 'dt_liv' :
				$sql = "SELECT MAX(ac.datep) as dt ";
				$sql .= "FROM " . MAIN_DB_PREFIX . "actioncomm AS ac ";
				$sql .= "INNER JOIN " . MAIN_DB_PREFIX . "commande_fournisseur as c ON c.rowid=ac.fk_element AND elementtype = 'order_supplier' ";
				$sql .= "WHERE c.source =" . $this->id . " ";
				$sql .= "AND c.ref LIKE '%VTFRA%' ";
				$sql .= "AND ac.label LIKE '%Commande fournisseur VTFRA-% reçue%'";
				break;

			case 'dt_ship' :
				$sql = "SELECT MAX(datep) as dt ";
				$sql .= "FROM " . MAIN_DB_PREFIX . "actioncomm ";
				$sql .= "WHERE fk_element =" . $this->id . " ";
				$sql .= "AND elementtype = 'order' ";
				$sql .= "AND label LIKE '%Commande V% classée Livrée%'";
				break;

			case 'dt_bill' :
				$sql = "SELECT MAX(datep) as dt_bill ";
				$sql .= "FROM " . MAIN_DB_PREFIX . "actioncomm ";
				$sql .= "WHERE fk_element =" . $this->id . " ";
				$sql .= "AND elementtype = 'order' ";
				$sql .= "AND label LIKE '%Commande V% classée Payée%'";
				break;
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			return $obj->dt;
		} else {
			return 0;
		}
	}
	public function calcvhprice($prixtot) {
		global $conf;

		$this->fetch_lines(1);

		$cost = 0;
		$costvnc = 0;
		foreach ( $this->lines as $line ) {
			if ($line->fk_product != $conf->global->VOLVO_TRUCK) {
				if ($line->fk_product == $conf->global->VOLVO_SURES) {
					if ($line->total_ht > 0)
						$costvnc += $line->total_ht;
					$cost += $line->total_ht;
				} elseif ($line->fk_product == $conf->global->VOLVO_COM) {
					$cost += $line->total_ht;
				} else {
					$cost += $line->total_ht;
					$costvnc += $line->total_ht;
				}
			}
		}
		$ret = array();
		$ret['prixvh'] = $prixtot - $cost;
		$ret['vnc'] = $prixtot - $costvnc;
		return $ret;
	}
	public function updatevhpriceandvnc($prixtot = 0) {
		global $user, $conf;

		$this->fetch_lines(1);
		if ($prixtot == 0) {
			$prixtot = $this->total_ht;
		}

		$value = array();
		$value = $this->calcvhprice($cmdnum, $prixtot);

		foreach ( $this->lines as $line ) {
			if ($line->fk_product == $conf->global->VOLVO_TRUCK) {
				$res = $this->updateline($line->id, $line->label, $value['prixvh'], $line->qty, $line->remise_percent, $line->tva_tx, 0, 0, 'HT', 0, '', '', 0, 0, 0, 0, $value['prixvh']);
				if ($result < 0) {
					array_push($this->errors, $cmd->error);
					return - 1;
				}
			}
		}

		$this->array_options['options_vnac'] = $value['vnc'];
		$result = $this->updateExtraField('vnac');
		if ($result < 0) {
			array_push($this->errors, $cmd->error);
			return - 2;
		}
		$result = $this->update_price();
		if ($result < 0) {
			array_push($this->errors, $cmd->error);
			return - 3;
		}

		return 1;
	}
}