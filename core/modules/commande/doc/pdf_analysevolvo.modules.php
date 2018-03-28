<?php
/* Copyright (C) 2003		Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@capnetworks.com>
 * Copyright (C) 2008		Raphael Bertrand (Resultic)	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2011		Fabrice CHERRIER
 * Copyright (C) 2013       Philippe Grand	            <philippe.grand@atoo-net.com>
 * Copyright (C) 2015       Marcos García               <marcosgdf@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/contract/doc/pdf_strato.modules.php
 *	\ingroup    ficheinter
 *	\brief      Strato contracts template class file
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/contract/modules_contract.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
dol_include_once('/affaires/class/affaires.class.php');
dol_include_once('/affaires/volvo/lib/volvo.lib.php');

/**
 *	Class to build contracts documents with model Strato
 */
class pdf_analysevolvo extends ModelePDFContract
{
	var $db;
	var $name;
	var $description;
	var $type;

	var $phpmin = array(4,3,0); // Minimum version of PHP required by module
	var $version = 'dolibarr';

	var $page_largeur;
	var $page_hauteur;
	var $format;
	var $marge_gauche;
	var	$marge_droite;
	var	$marge_haute;
	var	$marge_basse;

	/**
	 * Issuer
	 * @var Societe
	 */
	public $emetteur;

	/**
	 * Recipient
	 * @var Societe
	 */
	public $recipient;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf,$langs,$mysoc;

		$this->db = $db;
		$this->name = 'Analyse';
		$this->description = "Fiche d'analyse commerciale Volvo";

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=isset($conf->global->MAIN_PDF_MARGIN_LEFT)?$conf->global->MAIN_PDF_MARGIN_LEFT:10;
		$this->marge_droite=isset($conf->global->MAIN_PDF_MARGIN_RIGHT)?$conf->global->MAIN_PDF_MARGIN_RIGHT:10;
		$this->marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:10;
		$this->marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:10;

		$this->option_logo = 1;                    // Affiche logo
		$this->option_tva = 0;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 0;                 // Affiche mode reglement
		$this->option_condreg = 0;                 // Affiche conditions reglement
		$this->option_codeproduitservice = 0;      // Affiche code produit-service
		$this->option_multilang = 0;               // Dispo en plusieurs langues
		$this->option_draft_watermark = 1;		   //Support add of a watermark on drafts

		// Get source company
		$this->emetteur=$mysoc;
		if (empty($this->emetteur->country_code)) $this->emetteur->country_code=substr($langs->defaultlang,-2);    // By default, if not defined

		// Define position of columns
		$this->posxdesc=$this->marge_gauche+1;
	}

	/**
     *  Function to build pdf onto disk
     *
     *  @param		CommonObject	$object				Id of object to generate
     *  @param		object			$outputlangs		Lang output object
     *  @param		string			$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int				$hidedetails		Do not show line details
     *  @param		int				$hidedesc			Do not show desc
     *  @param		int				$hideref			Do not show ref
     *  @return		int									1=OK, 0=KO
	 */
	function write_file($object,$outputlangs,$srctemplatepath='',$hidedetails=0,$hidedesc=0,$hideref=0)
	{
		global $user,$langs,$conf,$hookmanager,$mysoc;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("contracts");

		if ($conf->commande->dir_output)
		{
            $object->fetch_thirdparty();

			// Definition of $dir and $file
			if ($object->specimen)
			{
				$dir = $conf->commande->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->commande->dir_output . "/" . $objectref;
				$file = $dir . "/analyse_" . $objectref . ".pdf";
			}

			if (! file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error=$outputlangs->trans("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}

			if (file_exists($dir))
			{
				// Add pdfgeneration hook
				if (! is_object($hookmanager))
				{
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager=new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('beforePDFCreation',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

                $pdf=pdf_getInstance($this->format);
                $default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
                $heightforinfotot = 50;	// Height reserved to output the info and total part
		        $heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);	// Height reserved to output the free text on last page
	            $heightforfooter = $this->marge_basse + 8;	// Height reserved to output the footer (value include bottom margin)
                $pdf->SetAutoPageBreak(1,0);

                if (class_exists('TCPDF'))
                {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));


				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("commandeCard"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("ContractCard")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
				if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

				// Add Pages from models
				$infile=$conf->affaires->dir_output.'/volvo/modelpdf/ficheanalyse.pdf';
				if (file_exists($infile) && is_readable($infile)) {
					$pagecount = $pdf->setSourceFile($infile);
					for($i = 1; $i <= $pagecount; $i ++) {
						$tplIdx = $pdf->importPage($i);
						if ($tplIdx!==false) {
							$s = $pdf->getTemplatesize($tplIdx);
							$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
							$pdf->useTemplate($tplIdx);
						} else {
							setEventMessages(null, array($infile.' cannot be added, probably protected PDF'),'warnings');
						}
					}
				}

				$pdf->SetPage(1);

 				$extrafields = new ExtraFields($this->db);
				$extralabels = $extrafields->fetch_name_optionals_label($object->table_element, true);

				$x = prepare_array('VOLVO_ANALYSE_X', 'array');
				$z = prepare_array('VOLVO_ANALYSE_Z', 'array');
				$yt = prepare_array('VOLVO_ANALYSE_Y_ENTETE', 'array');
				$yp = prepare_array('VOLVO_ANALYSE_Y_PIED', 'array');

				//Carac client
				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[1], $yt[0]);
				$out = $outputlangs->convToOutputCharset(dol_print_date($object->date,'day'));
				$pdf->MultiCell($z[1], 0, $out,0,'L');

				$object->info($object->id);

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[3], $yt[0]);
				$out = $outputlangs->convToOutputCharset(dol_print_date($object->date_modification,'dayhour'));
				$pdf->MultiCell($z[3]+$z[4], 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[7], $yt[0]);
				$out = $outputlangs->convToOutputCharset(dol_print_date($object->date_livraison,'%W-%Y'));
				$pdf->MultiCell($z[7], 0, $out,0,'L');

				$commercial = new User($this->db);
				$commercial->fetch($object->user_author_id);

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[2], $yt[1]);
				$out = $outputlangs->convToOutputCharset($commercial->firstname . ' ' . $commercial->lastname);
				$pdf->MultiCell($z[2]+$z[3], 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[5], $yt[1]);
				$out = $outputlangs->convToOutputCharset($object->ref);
				$pdf->MultiCell($z[5], 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[7], $yt[1]);
				$out = $outputlangs->convToOutputCharset(substr($object->array_options['options_vin'],-7));
				$pdf->MultiCell($z[7], 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[2], $yt[2]);
				$out = $outputlangs->convToOutputCharset($object->thirdparty->name);
				$pdf->MultiCell($z[2]+$z[3]+$z[4]+$z[5]+$z[6]+$z[7], 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[2], $yt[3]);
				$out = $outputlangs->convToOutputCharset(substr(str_replace("\n",' ',$object->thirdparty->address) . ' - ' . $object->thirdparty->zip . ' '. $object->thirdparty->town,0,74));
				$pdf->MultiCell($z[2]+$z[3]+$z[4]+$z[5]+$z[6]+$z[7]+50, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[1], $yt[5]);
				$out = $outputlangs->convToOutputCharset('1');
				$pdf->MultiCell($z[1], 0, $out,0,'L');

				$lead = new Affaires_det($this->db);
				$lead->fetchAffairesDetLink($object->id, $object->table_element);
				$lead=$lead->doclines['0'];
				$extrafields_lead = new ExtraFields($this->db);
				$extralabels_lead = $extrafields_lead->fetch_name_optionals_label($lead->table_element, true);
				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[3],$yt[5]);
				$out = $outputlangs->convToOutputCharset($extrafields_lead->showOutputField('type', $lead->array_options['options_type']) . ' ' . $extrafields_lead->showOutputField('gamme', $lead->array_options['options_gamme']). ' ' . $extrafields_lead->showOutputField('silouhette', $lead->array_options['options_silouhette']));
				$pdf->MultiCell($z[3]+$z[4]+$z[5], 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[7], $yt[5]);
				$out = $outputlangs->convToOutputCharset($extrafields_lead->showOutputField('specif', $lead->array_options['options_specif']));
				$pdf->MultiCell($z[7], 0, $out,0,'L');

				$object->demand_reason;
				$sql = "SELECT label";
        		$sql.= " FROM ".MAIN_DB_PREFIX.'c_input_reason';
        		$sql.= " WHERE active > 0 AND rowid =" . $object->demand_reason_id;

        		$resql = $this->db->query($sql);
        		if ($resql)
        		{
        			$obj = $this->db->fetch_object($resql);
        			$liv = $obj->label;
        		}
				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[3], $yt[6]);
				$out = $outputlangs->convToOutputCharset($liv);
				$pdf->MultiCell($z[3]+$z[4]+$z[5]+$z[6]+$z[7], 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[2], $yt[7]);
				$out = $outputlangs->convToOutputCharset($object->cond_reglement);
				$pdf->MultiCell($z[2], 0, $out,0,'L');


				$filter=array();
				$filter['com.rowid'] = $object->id;
				$lead2 = new Affaires_det($this->db);

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[7], $yt[7]);
				$out = $outputlangs->convToOutputCharset(Price($object->total_ht) . ' €');
				$pdf->MultiCell($z[7], 0, $out,0,'R');

				$intern = 0;
				$internspace = array();
				for ($i = 1; $i <= $conf->global->VOLVO_ANALYSE_Y_INTERNE_NB; $i++) {
					$internspace[] = $conf->global->VOLVO_ANALYSE_Y_INTERNE_OFFSET +(($i-1)*$conf->global->VOLVO_ANALYSE_Y_INTERNE_PAS);
				}


				$externe = 0;
				$externspace = array();
				for ($i = 1; $i <= $conf->global->VOLVO_ANALYSE_Y_EXTERNE_NB; $i++) {
					$externspace[] = $conf->global->VOLVO_ANALYSE_Y_EXTERNE_OFFSET +(($i-1)*$conf->global->VOLVO_ANALYSE_Y_EXTERNE_PAS);
				}


				$divers = 0;
				$diverspace = array();
				for ($i = 1; $i <= $conf->global->VOLVO_ANALYSE_Y_DIVERS_NB; $i++) {
					$diverspace[] = $conf->global->VOLVO_ANALYSE_Y_DIVERS_OFFSET +(($i-1)*$conf->global->VOLVO_ANALYSE_Y_DIVERS_PAS);
				}

				$vo = 0;
				$vospace = array();
				for ($i = 1; $i <= $conf->global->VOLVO_ANALYSE_Y_VO_NB; $i++) {
					$vospace[] = $conf->global->VOLVO_ANALYSE_Y_VO_OFFSET +(($i-1)*$conf->global->VOLVO_ANALYSE_Y_VO_PAS);
				}

				$totalht =0;
				$totalpa=0;
				$totalreel=0;
				$totalecart=0;
				$marge =0;
				$gold=0;
				$blue=0;
				$silver=0;
				$ppc=0;
				$pcc=0;
				$pvc=0;
				$golds=0;

				foreach($object->lines as $line){
					$extrafieldsline = new ExtraFields($this->db);
					$extralabelsline = $extrafieldsline->fetch_name_optionals_label($line->table_element, true);
					$line->fetch_optionals($line->id, $extralabelsline);

					if($line->fk_product == $conf->global->VOLVO_FORFAIT_LIV){
						$pdf->SetFont('','', $default_font_size);
						$pdf->SetXY($x[4], $yt[8]);
						$out = $outputlangs->convToOutputCharset(Price($line->total_ht) . ' €');
						$pdf->MultiCell($z[4], 0, $out,0,'R');
						$totalht+=$line->total_ht;

						$pdf->SetFont('','', $default_font_size);
						$pdf->SetXY($x[5], $yt[8]);
						$out = $outputlangs->convToOutputCharset(Price($line->pa_ht) . ' €');
						$pdf->MultiCell($z[5], 0, $out,0,'R');
						$totalpa+=$line->pa_ht;

						if(!empty($line->array_options['options_fk_supplier'])){

							$pdf->SetFont('','', $default_font_size);
							$pdf->SetXY($x[6], $yt[8]);
							$out = $outputlangs->convToOutputCharset(Price($line->array_options['options_buyingprice_real']) . ' €');
							$pdf->MultiCell($z[6], 0, $out,0,'R');
							$totalreel+=$line->array_options['options_buyingprice_real'];

							$ecart =$line->total_ht- $line->array_options['options_buyingprice_real'];
						}else{
							$ecart = $line->total_ht- $line->pa_ht;
						}

						if(!empty($ecart)){
							$pdf->SetFont('','', $default_font_size);
							$pdf->SetXY($x[7], $yt[8]);
							$out = $outputlangs->convToOutputCharset(Price($ecart) . ' €');
							$pdf->MultiCell($z[7], 0, $out,0,'R');
							$totalecart+=$ecart;
						}

					}elseif($line->fk_product == $conf->global->VOLVO_SURES){
						$pdf->SetFont('','', $default_font_size);
						$pdf->SetXY($x[4], $vospace[1]);
						$out = $outputlangs->convToOutputCharset(Price($line->total_ht) . ' €');
						$pdf->MultiCell($z[4], 0, $out,0,'R');
						$totalht+=$line->total_ht;

						$pdf->SetFont('','', $default_font_size);
						$pdf->SetXY($x[5], $vospace[1]);
						$out = $outputlangs->convToOutputCharset(Price($line->pa_ht) . ' €');
						$pdf->MultiCell($z[5], 0, $out,0,'R');
						$totalpa+=$line->pa_ht;

						if(!empty($line->array_options['options_fk_supplier'])){

							$pdf->SetFont('','', $default_font_size);
							$pdf->SetXY($x[6], $vospace[1]);
							$out = $outputlangs->convToOutputCharset(Price($line->array_options['options_buyingprice_real']) . ' €');
							$pdf->MultiCell($z[6], 0, $out,0,'R');
							$totalreel+=$line->array_options['options_buyingprice_real'];

							$ecart = $line->total_ht - $line->array_options['options_buyingprice_real'];
						}else{
							$ecart = $line->total_ht-$line->pa_ht;
						}

						if(!empty($ecart)){
							$pdf->SetFont('','', $default_font_size);
							$pdf->SetXY($x[7], $vospace[1]);
							$out = $outputlangs->convToOutputCharset(Price($ecart) . ' €');
							$pdf->MultiCell($z[7], 0, $out,0,'R');
							$totalecart+=$ecart;
						}

						//$reprise = new Reprise($this->db);
						//TODO intitialy from fetchAllfolow now ?
						$pdf->SetFont('','', $default_font_size);
						$pdf->SetXY($x[2], $vospace[0]);
						//$out = $outputlangs->convToOutputCharset(Price($reprise->gettotalestim($lead->id)) . ' €');
						$pdf->MultiCell($z[2]+$z[3], 0, $out,0,'R');

						$pdf->SetFont('','', $default_font_size);
						$pdf->SetXY($x[2], $vospace[1]);
						//$out = $outputlangs->convToOutputCharset(Price($reprise->gettotalrachat($lead->id)) . ' €');
						$pdf->MultiCell($z[2]+$z[3], 0, $out,0,'R');


					}elseif($line->fk_product == $conf->global->VOLVO_COM){
						$pdf->SetFont('','', $default_font_size);
						$pdf->SetXY($x[4], $yp[1]);
						$out = $outputlangs->convToOutputCharset(Price($line->total_ht) . ' €');
						$pdf->MultiCell($z[4], 0, $out,0,'R');
						$marge+=$line->total_ht;

					}else{

						$listcateg = product_all_categ($line->fk_product,'array');

						if(in_array($conf->global->VOLVO_VEHICULE, $listcateg)){

							$pdf->SetFont('','', $default_font_size);
							$pdf->SetXY($x[3], $yt[5]);
							$out = $outputlangs->convToOutputCharset(substr($line->desc,0,46));
							$pdf->MultiCell($z[3]+$z[4]+$z[5]+$z[6]+$z[7], 0, $out,0,'L');
						}


						if(in_array($conf->global->VOLVO_INTERNE, $listcateg)){

							$pdf->SetFont('','', $default_font_size);
							$pdf->SetXY($x[0], $internspace[$intern]);
							$out = $outputlangs->convToOutputCharset($line->product_label);
							$pdf->MultiCell($z[0]+$z[1]+$z[2]+$z[3], 0, $out,0,'L');


							$pdf->SetFont('','', $default_font_size);
							$pdf->SetXY($x[4], $internspace[$intern]);
							$out = $outputlangs->convToOutputCharset(Price($line->total_ht) . ' €');
							$pdf->MultiCell($z[4], 0, $out,0,'R');
							$totalht+=$line->total_ht;

							$pdf->SetFont('','', $default_font_size);
							$pdf->SetXY($x[5], $internspace[$intern]);
							$out = $outputlangs->convToOutputCharset(Price($line->pa_ht * $line->qty) . ' €');
							$pdf->MultiCell($z[5], 0, $out,0,'R');
							$totalpa+=$line->pa_ht * $line->qty;

							if(!empty($line->array_options['options_fk_supplier'])){

								$pdf->SetFont('','', $default_font_size);
								$pdf->SetXY($x[6], $internspace[$intern]);
								$out = $outputlangs->convToOutputCharset(Price($line->array_options['options_buyingprice_real']) . ' €');
								$pdf->MultiCell($z[6], 0, $out,0,'R');
								$totalreel+=$line->array_options['options_buyingprice_real'];

								$ecart =$line->total_ht - $line->array_options['options_buyingprice_real'];

							}else{
								$ecart = $line->total_ht - ($line->pa_ht * $line->qty);
							}

							if(!empty($ecart)){
								$pdf->SetFont('','', $default_font_size);
								$pdf->SetXY($x[7], $internspace[$intern]);
								$out = $outputlangs->convToOutputCharset(Price($ecart) . ' €');
								$pdf->MultiCell($z[7], 0, $out,0,'R');
								$totalecart+=$ecart;
							}
							$intern++;
						}

						if(in_array($conf->global->VOLVO_EXTERNE, $listcateg)){

							$pdf->SetFont('','', $default_font_size);
							$pdf->SetXY($x[0], $externspace[$externe]);
							$out = $outputlangs->convToOutputCharset($line->product_label);
							$pdf->MultiCell($z[0]+$z[1]+$z[2]+$z[3], 0, $out,0,'L');


							$pdf->SetFont('','', $default_font_size);
							$pdf->SetXY($x[4], $externspace[$externe]);
							$out = $outputlangs->convToOutputCharset(Price($line->total_ht) . ' €');
							$pdf->MultiCell($z[4], 0, $out,0,'R');
							$totalht+=$line->total_ht;

							$pdf->SetFont('','', $default_font_size);
							$pdf->SetXY($x[5], $externspace[$externe]);
							$out = $outputlangs->convToOutputCharset(Price($line->pa_ht * $line->qty) . ' €');
							$pdf->MultiCell($z[5], 0, $out,0,'R');
							$totalpa+=$line->pa_ht * $line->qty;

							if(!empty($line->array_options['options_fk_supplier'])){

								$pdf->SetFont('','', $default_font_size);
								$pdf->SetXY($x[6], $externspace[$externe]);
								$out = $outputlangs->convToOutputCharset(Price($line->array_options['options_buyingprice_real']) . ' €');
								$pdf->MultiCell($z[6], 0, $out,0,'R');
								$totalreel+=$line->array_options['options_buyingprice_real'];

								$ecart = $line->total_ht - $line->array_options['options_buyingprice_real'];

							}else{
								$ecart = $line->total_ht - ($line->pa_ht * $line->qty);
							}

							if(!empty($ecart)){
								$pdf->SetFont('','', $default_font_size);
								$pdf->SetXY($x[7], $externspace[$externe]);
								$out = $outputlangs->convToOutputCharset(Price($ecart) . ' €');
								$pdf->MultiCell($z[7], 0, $out,0,'R');
								$totalecart+=$ecart;
							}
							$externe++;
						}

						if(in_array($conf->global->VOLVO_DIVERS, $listcateg) && !in_array($conf->global->VOLVO_SOLTRS, $listcateg)){

							$pdf->SetFont('','', $default_font_size);
							$pdf->SetXY($x[0], $diverspace[$divers]);
							$out = $outputlangs->convToOutputCharset($line->product_label);
							$pdf->MultiCell($z[0]+$z[1]+$z[2]+$z[3], 0, $out,0,'L');


							$pdf->SetFont('','', $default_font_size);
							$pdf->SetXY($x[4], $diverspace[$divers]);
							$out = $outputlangs->convToOutputCharset(Price($line->total_ht) . ' €');
							$pdf->MultiCell($z[4], 0, $out,0,'R');
							$totalht+=$line->total_ht;

							$pdf->SetFont('','', $default_font_size);
							$pdf->SetXY($x[5], $diverspace[$divers]);
							$out = $outputlangs->convToOutputCharset(Price($line->pa_ht * $line->qty) . ' €');
							$pdf->MultiCell($z[5], 0, $out,0,'R');
							$totalpa+=$line->pa_ht * $line->qty;

							if(!empty($line->array_options['options_fk_supplier'])){

								$pdf->SetFont('','', $default_font_size);
								$pdf->SetXY($x[6], $diverspace[$divers]);
								$out = $outputlangs->convToOutputCharset(Price($line->array_options['options_buyingprice_real']) . ' €');
								$pdf->MultiCell($z[6], 0, $out,0,'R');
								$totalreel+=$line->array_options['options_buyingprice_real'];

								$ecart = $line->total_ht - $line->array_options['options_buyingprice_real'];

							}else{
								$ecart = $line->total_ht - ($line->pa_ht * $line->qty);
							}

							if(!empty($ecart)){
								$pdf->SetFont('','', $default_font_size);
								$pdf->SetXY($x[7], $diverspace[$divers]);
								$out = $outputlangs->convToOutputCharset(Price($ecart) . ' €');
								$pdf->MultiCell($z[7], 0, $out,0,'R');
								$totalecart+=$ecart;
							}
							$divers++;
						}

						if(in_array($conf->global->VOLVO_SOLTRS, $listcateg)){
							if($line->fk_product == 28){
								$blue=1;
							}elseif ($line->fk_product == 31){
								$gold=1;
							}elseif ($line->fk_product == 20){
								$pcc=1;
							}elseif ($line->fk_product == 27){
								$ppc=1;
							}elseif ($line->fk_product == 26){
								$pvc=1;
							}elseif ($line->fk_product == 29){
								$silver=1;
							}elseif ($line->fk_product == 30){
								$silver=1;
							}elseif ($line->fk_product == 40){
								$golds=1;
							}

						}

					}

				}

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[4], $yp[0]);
				$out = $outputlangs->convToOutputCharset(Price($totalht) . ' €');
				$pdf->MultiCell($z[4], 0, $out,0,'R');


				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[5], $yp[0]);
				$out = $outputlangs->convToOutputCharset(Price($totalpa) . ' €');
				$pdf->MultiCell($z[5], 0, $out,0,'R');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[6], $yp[0]);
				$out = $outputlangs->convToOutputCharset(Price($totalreel) . ' €');
				$pdf->MultiCell($z[6], 0, $out,0,'R');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[7], $yp[0]);
				$out = $outputlangs->convToOutputCharset(Price($totalecart) . ' €');
				$pdf->MultiCell($z[7], 0, $out,0,'R');

				$margeprev = $marge - ($totalpa-$totalht);

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[6], $yp[1]);
				$out = $outputlangs->convToOutputCharset(Price($margeprev) . ' €');
				$pdf->MultiCell($z[6], 0, $out,0,'R');

				$margereel = $marge +($totalecart);

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[7], $yp[2]);
				$out = $outputlangs->convToOutputCharset(Price($margereel) . ' €');
				$pdf->MultiCell($z[7], 0, $out,0,'R');

				$rep = 'Non';
				if($blue){
					$rep = 'Oui';
				}
				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[1], $yp[3]);
				$out = $outputlangs->convToOutputCharset($rep);
				$pdf->MultiCell($z[1], 0, $out,0,'L');

				$rep = 'Non';
				if($silver){
					$rep = 'Oui';
				}
				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[1], $yp[4]);
				$out = $outputlangs->convToOutputCharset($rep);
				$pdf->MultiCell($z[1], 0, $out,0,'L');

				$rep = 'Non';
				if($gold){
					$rep = 'Oui';
				}
				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[1], $yp[5]);
				$out = $outputlangs->convToOutputCharset($rep);
				$pdf->MultiCell($z[1], 0, $out,0,'L');

				$rep = 'Non';
				if($ppc){
					$rep = 'Oui';
				}
				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[3], $yp[3]);
				$out = $outputlangs->convToOutputCharset($rep);
				$pdf->MultiCell($z[3], 0, $out,0,'L');

				$rep = 'Non';
				if($pcc){
					$rep = 'Oui';
				}
				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[3], $yp[4]);
				$out = $outputlangs->convToOutputCharset($rep);
				$pdf->MultiCell($z[3], 0, $out,0,'L');

				$rep = 'Non';
				if($pvc){
					$rep = 'Oui';
				}
				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[3], $yp[5]);
				$out = $outputlangs->convToOutputCharset($rep);
				$pdf->MultiCell($z[3], 0, $out,0,'L');

				$rep = 'Non';
				if($golds){
					$rep = 'Oui';
				}
				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[2], $yp[6]);
				$out = $outputlangs->convToOutputCharset($rep);
				$pdf->MultiCell($z[2]+$z[3], 0, $out,0,'L');

				$rep = 'Non';
				if($lead->array_options['options_new']){
					$rep = 'Oui';
				}
				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY($x[2], $yp[7]);
				$out = $outputlangs->convToOutputCharset($rep);
				$pdf->MultiCell($z[2]+$z[3], 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size-2);
				$pdf->SetXY($x[0], $yp[8]);
				$out = $outputlangs->convToOutputCharset($object->note_private);
				$pdf->writeHTMLCell(194,35,8,254.5,$out);

				$pdf->Close();

				$pdf->Output($file,'F');

				// Add pdfgeneration hook
				if (! is_object($hookmanager))
				{
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager=new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks

				if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;
			}
			else
			{
				$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}
		else
		{
			$this->error=$langs->trans("ErrorConstantNotDefined","CONTRACT_OUTPUTDIR");
			return 0;
		}
		$this->error=$langs->trans("ErrorUnknown");
		return 0;   // Erreur par defaut
	}
}

