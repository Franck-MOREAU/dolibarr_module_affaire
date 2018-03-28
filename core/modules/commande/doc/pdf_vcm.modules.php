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
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
dol_include_once('/affaires/class/affaires.class.php');
dol_include_once('/affaires/volvo/lib/volvo.lib.php');

/**
 *	Class to build contracts documents with model Strato
 */
class pdf_vcm extends ModelePDFContract
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
		$this->name = 'vcm';
		$this->description = "Demande de tarification de solution de maintenace";

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
				$file = $dir . "/vcm_" . $objectref . ".pdf";
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
				$default_font_size = pdf_getPDFFontSize($outputlangs)-2.5;	// Must be after pdf_getInstance
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
				$infile=$conf->affaires->dir_output.'/volvo/modelpdf/vcm.pdf';
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

				$y=array(25.1,37.4,49.6,61.9,66,70.4,97.3,103.4,107.9,112.4,118.3,122.8,127.3,131.8,138.5,143.2,147.8,152.3,156.7,156.9,161.5,167.2,172,192.2,196.6,201.2,205.7,210.2,214.7,219.3,223.8,234,280,284);

				$x = prepare_array('VOLVO_ANALYSELG_X', 'array');
				$z = prepare_array('VOLVO_ANALYSELG_Z', 'array');
				$yt = prepare_array('VOLVO_ANALYSELG_Y_ENTETE', 'array');
				$yp = prepare_array('VOLVO_ANALYSELG_Y_PIED', 'array');

				$commercial = new User($this->db);
				$commercial->fetch($object->user_author_id);

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(38, $y[0]);
				$out = $outputlangs->convToOutputCharset($commercial->firstname . ' ' . $commercial->lastname);
				$pdf->MultiCell(80, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(143.8, $y[0]);
				$out = $outputlangs->convToOutputCharset($object->ref);
				$pdf->MultiCell(30, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(19, $y[1]);
				$out = $outputlangs->convToOutputCharset($object->thirdparty->name);
				$pdf->MultiCell(100, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(93.1, $y[1]);
				$out = $outputlangs->convToOutputCharset($object->thirdparty->town);
				$pdf->MultiCell(80, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(154.2, $y[1]);
				$out = $outputlangs->convToOutputCharset($object->thirdparty->code_client);
				$pdf->MultiCell(80, 0, $out,0,'L');

				$lead = new Affaires_det($this->db);
				$lead->fetchAffairesDetLink($object->id, $object->table_element);
				$lead=$lead->doclines['0'];
				$extrafields_lead = new ExtraFields($this->db);
				$extralabels_lead = $extrafields_lead->fetch_name_optionals_label($lead->table_element, true);

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(25.5, $y[2]);
				$out = $outputlangs->convToOutputCharset($extrafields_lead->showOutputField('specif', $lead->array_options['options_specif']));
				$pdf->MultiCell(100, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(140.1, $y[2]);
				$out = $outputlangs->convToOutputCharset(substr($object->array_options['options_vin'],-7));
				$pdf->MultiCell(50, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(61, $y[3]);
				$out = $outputlangs->convToOutputCharset(yn($object->array_options['options_vcm_deja']));
				$pdf->MultiCell(30, 0, $out,0,'L');


				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(143, $y[3]);
				//TODO intitialy from reprise now ?
				//$out = $outputlangs->convToOutputCharset($reprise->sites[$object->array_options['options_vcm_site']]);
				$pdf->MultiCell(50, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(30.5, $y[4]);
				$out = $outputlangs->convToOutputCharset(yn($object->array_options['options_vcm_deport']));
				$pdf->MultiCell(30, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(65.5, $y[5]);
				$out = $outputlangs->convToOutputCharset(yn($object->array_options['options_vcm_trf_gds']));
				$pdf->MultiCell(30, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(173, $y[5]);
				$out = $outputlangs->convToOutputCharset(yn($object->array_options['options_vcm_trf_dfol']));
				$pdf->MultiCell(30, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(170.5, $y[6]);
				$out = $outputlangs->convToOutputCharset(yn($object->array_options['options_vcm_ppc']));
				$pdf->MultiCell(30, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(170.5, $y[7]);
				$out = $outputlangs->convToOutputCharset(yn($object->array_options['options_vcm_pc']));
				$pdf->MultiCell(30, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(170.5, $y[8]);
				$out = $outputlangs->convToOutputCharset(yn($object->array_options['options_vcm_pcc']));
				$pdf->MultiCell(30, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(170.5, $y[9]);
				$out = $outputlangs->convToOutputCharset(yn($object->array_options['options_vcm_pvc']));
				$pdf->MultiCell(30, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(170.5, $y[10]);
				$out = $outputlangs->convToOutputCharset(yn($object->array_options['options_vcm_blue']));
				$pdf->MultiCell(30, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(170.5, $y[11]);
				$out = $outputlangs->convToOutputCharset(yn($object->array_options['options_vcm_silver']));
				$pdf->MultiCell(30, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(170.5, $y[12]);
				$out = $outputlangs->convToOutputCharset(yn($object->array_options['options_vcm_silverp']));
				$pdf->MultiCell(30, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(170.5, $y[13]);
				$out = $outputlangs->convToOutputCharset(yn($object->array_options['options_vcm_gold']));
				$pdf->MultiCell(30, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$selected = explode(',', $object->array_options['options_vcm_duree']);
				$x = 32;
				foreach ($extrafields->attribute_param['vcm_duree']['options'] as $key => $value){
					if(in_array($key, $selected)){
						$pdf->image(show_picto_pdf(1),$x,$y[14],3,3,'','','M',1);
						$pdf->SetFont('','', $default_font_size);
						$pdf->SetXY($x+3.5, $y[14]);
						$out = $outputlangs->convToOutputCharset($value);
						$pdf->MultiCell(80, 0, $value,0,'L');
						$x=$x+22;
					}else{
						$pdf->image(show_picto_pdf(0),$x,$y[14],3,3,'','','M',1);
						$pdf->SetFont('','', $default_font_size);
						$pdf->SetXY($x+3.5, $y[14]);
						$out = $outputlangs->convToOutputCharset($value);
						$pdf->MultiCell(80, 0, $value,0,'L');
						$x=$x+22;
					}
				}

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(54, $y[15]);
				$out = $outputlangs->convToOutputCharset(price($object->array_options['options_vcm_conso']) . ' l/100km');
				$pdf->MultiCell(30, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(164, $y[15]);
				$out = $outputlangs->convToOutputCharset(price($object->array_options['options_vcm_ptra']) . ' Tonnes');
				$pdf->MultiCell(30, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(33, $y[16]);
				$out = $outputlangs->convToOutputCharset(price($object->array_options['options_vcm_km']) . ' km/an');
				$pdf->MultiCell(30, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(164, $y[16]);
				$out = $outputlangs->convToOutputCharset(price($object->array_options['options_vcm_km_dep']) . ' km');
				$pdf->MultiCell(30, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(58.7, $y[17]);
				$out = $outputlangs->convToOutputCharset(dol_print_date($object->array_options['options_vcm_dt_dem'],'daytext'));
				$pdf->MultiCell(80, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$selected = explode(',', $object->array_options['options_vcm_pto']);
				$x = 37;
				foreach ($extrafields->attribute_param['vcm_pto']['options'] as $key => $value){
					if(in_array($key, $selected)){
						$pdf->image( show_picto_pdf(1),$x,$y[18],3,3,'','','M',1);
						$pdf->image(show_picto_pdf(1),$x,$y[18],3,3,'','','M',1);
						$pdf->SetFont('','', $default_font_size);
						$pdf->SetXY($x+3.5, $y[18]);
						$out = $outputlangs->convToOutputCharset($value);
						$pdf->MultiCell(30, 0, $value,0,'L');
						$x=$x+22;
					}else{
						$pdf->image( show_picto_pdf(0),$x,$y[18],3,3,'','','M',1);
						$pdf->SetFont('','', $default_font_size);
						$pdf->SetXY($x+3.5, $y[18]);
						$out = $outputlangs->convToOutputCharset($value);
						$pdf->MultiCell(30, 0, $value,0,'L');
						$x=$x+22;
					}
				}

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(164, $y[19]);
				$out = $outputlangs->convToOutputCharset(price($object->array_options['options_vcm_pto_nbh']) . ' H/an');
				$pdf->MultiCell(30, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(164, $y[20]);
				$out = $outputlangs->convToOutputCharset(price($object->array_options['options_vcm_pto_hdep']) . ' Heures');
				$pdf->MultiCell(30, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$selected = explode(',', $object->array_options['options_vcm_hydro']);
				$x = 41;
				foreach ($extrafields->attribute_param['vcm_hydro']['options'] as $key => $value){
					if(in_array($key, $selected)){
						$pdf->image( show_picto_pdf(1),$x,$y[21],3,3,'','','M',1);
						$pdf->SetFont('','', $default_font_size);
						$pdf->SetXY($x+3.5, $y[21]);
						$out = $outputlangs->convToOutputCharset($value);
						$pdf->MultiCell(80, 0, $value,0,'L');
						$x=$x+70;
					}else{
						$pdf->image( show_picto_pdf(0),$x,$y[21],3,3,'','','M',1);
						$pdf->SetFont('','', $default_font_size);
						$pdf->SetXY($x+3.5, $y[21]);
						$out = $outputlangs->convToOutputCharset($value);
						$pdf->MultiCell(80, 0, $value,0,'L');
						$x=$x+70;
					}
				}

				if(!empty($object->array_options['options_vcm_carr'])){
					$pdf->SetFont('','', $default_font_size);
					$out = $outputlangs->convToOutputCharset('<B>Carrosserie et equipements: </b>' . $object->array_options['options_vcm_carr']);
					$pdf->writeHTMLCell(194,20,6.8,$y[22],$out);
				}

				$cycle='test';
				if($object->array_options['options_vcm_ld']==1) $cycle='Longue Distance';
				if($object->array_options['options_vcm_50km']==1) $cycle='Distribution Régionnale';
				if($object->array_options['options_vcm_ville']==1) $cycle='Distribution Urbaine';
				if($object->array_options['options_vcm_chant']==1) $cycle='Construction';

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(33.5, $y[23]);
				$out = $outputlangs->convToOutputCharset($cycle);
				$pdf->MultiCell(100, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(58.5, $y[24]);
				$out = $outputlangs->convToOutputCharset(yn($object->array_options['options_vcm_sais']));
				$pdf->MultiCell(30, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(63, $y[25]);
				$out = $outputlangs->convToOutputCharset(yn($object->array_options['options_vcm_chant']));
				$pdf->MultiCell(30, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(67, $y[26]);
				$out = $outputlangs->convToOutputCharset(yn($object->array_options['options_vcm_ville']));
				$pdf->MultiCell(30, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(120, $y[27]);
				$out = $outputlangs->convToOutputCharset(yn($object->array_options['options_vcm_50km']));
				$pdf->MultiCell(30, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(86, $y[28]);
				$out = $outputlangs->convToOutputCharset(yn($object->array_options['options_vcm_ld']));
				$pdf->MultiCell(30, 0, $out,0,'L');

				$list_value = $extrafields->attribute_param['vcm_zone']['options'];
				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(33.5, $y[29]);
				$out = $outputlangs->convToOutputCharset($list_value[$object->array_options['options_vcm_zone']]);
				$pdf->MultiCell(100, 0, $out,0,'L');

				$list_value = $extrafields->attribute_param['vcm_typ_trans']['options'];
				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(122.5, $y[29]);
				$out = $outputlangs->convToOutputCharset($list_value[$object->array_options['options_vcm_typ_trans']]);
				$pdf->MultiCell(100, 0, $out,0,'L');

				$list_value = $extrafields->attribute_param['vcm_roul']['options'];
				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(35.5, $y[30]);
				$out = $outputlangs->convToOutputCharset($list_value[$object->array_options['options_vcm_roul']]);
				$pdf->MultiCell(100, 0, $out,0,'L');

				$list_value = $extrafields->attribute_param['vcm_topo']['options'];
				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(122.5, $y[30]);
				$out = $outputlangs->convToOutputCharset($list_value[$object->array_options['options_vcm_topo']]);
				$pdf->MultiCell(100, 0, $out,0,'L');

				$pdf->SetFont('','', $default_font_size-1);
				$selected = explode(',', $object->array_options['options_vcm_pack']);
				$x = 8;
				$yy = $y[31];
				foreach ($extrafields->attribute_param['vcm_pack']['options'] as $key => $value){
					if(in_array($key, $selected)){
						$pdf->image( show_picto_pdf(1),$x,$yy,2,2,'','','M',1);
						$pdf->SetFont('','', $default_font_size-1);
						$pdf->SetXY($x+2.5, $yy-0.3);
						$out = $outputlangs->convToOutputCharset($value);
						$pdf->MultiCell(80, 0, $value,0,'L');
						$yy=$yy+3.5;
					}else{
						$pdf->image( show_picto_pdf(0),$x,$yy,2,2,'','','M',1);
						$pdf->SetFont('','', $default_font_size-1);
						$pdf->SetXY($x+2.5, $yy-0.3);
						$out = $outputlangs->convToOutputCharset($value);
						$pdf->MultiCell(80, 0, $value,0,'L');
						$yy=$yy+3.5;
					}
				}
				$ysuiv = $yy + 7;

				$pdf->SetFont('','', $default_font_size-1);
				$selected = explode(',', $object->array_options['options_vcm_option']);
				$x = 8;
				$yy = $ysuiv;
				foreach ($extrafields->attribute_param['vcm_option']['options'] as $key => $value){
					if(in_array($key, $selected)){
						$pdf->image( show_picto_pdf(1),$x,$yy,2,2,'','','M',1);
						$pdf->SetFont('','', $default_font_size-1);
						$pdf->SetXY($x+2.5, $yy-0.3);
						$out = $outputlangs->convToOutputCharset($value);
						$pdf->MultiCell(80, 0, $value,0,'L');
						$yy=$yy+3.5;
					}else{
						$pdf->image( show_picto_pdf(0),$x,$yy,2,2,'','','M',1);
						$pdf->SetFont('','', $default_font_size-1);
						$pdf->SetXY($x+2.5, $yy-0.3);
						$out = $outputlangs->convToOutputCharset($value);
						$pdf->MultiCell(80, 0, $value,0,'L');
						$yy=$yy+3.5;
					}
				}


				$pdf->SetFont('','', $default_font_size-1);
				$selected = explode(',', $object->array_options['options_vcm_sup']);
				$x = 65;
				$yy = $y[31];
				foreach ($extrafields->attribute_param['vcm_sup']['options'] as $key => $value){
					if(in_array($key, $selected)){
						$pdf->image( show_picto_pdf(1),$x,$yy,2,2,'','','M',1);
						$pdf->SetFont('','', $default_font_size-1);
						$pdf->SetXY($x+2.5, $yy-0.3);
						$out = $outputlangs->convToOutputCharset($value);
						$pdf->MultiCell(80, 0, $value,0,'L');
						$yy=$yy+3.5;
					}else{
						$pdf->image( show_picto_pdf(0),$x,$yy,2,2,'','','M',1);
						$pdf->SetFont('','', $default_font_size-1);
						$pdf->SetXY($x+2.5, $yy-0.3);
						$out = $outputlangs->convToOutputCharset($value);
						$pdf->MultiCell(80, 0, $value,0,'L');
						$yy=$yy+3.5;
					}
				}

				$pdf->SetFont('','', $default_font_size-1);
				$selected = explode(',', $object->array_options['options_vcm_legal']);
				$x = 130;
				$yy = $y[31];
				foreach ($extrafields->attribute_param['vcm_legal']['options'] as $key => $value){
					if(in_array($key, $selected)){
						$pdf->image( show_picto_pdf(1),$x,$yy,2,2,'','','M',1);
						$pdf->SetFont('','', $default_font_size-1);
						$pdf->SetXY($x+2.5, $yy-0.3);
						$out = $outputlangs->convToOutputCharset($value);
						$pdf->MultiCell(80, 0, $value,0,'L');
						$yy=$yy+3.5;
					}else{
						$pdf->image( show_picto_pdf(0),$x,$yy,2,2,'','','M',1);
						$pdf->SetFont('','', $default_font_size-1);
						$pdf->SetXY($x+2.5, $yy-0.3);
						$out = $outputlangs->convToOutputCharset($value);
						$pdf->MultiCell(80, 0, $value,0,'L');
						$yy=$yy+3.5;
					}
				}

				$pdf->SetFont('','', $default_font_size);
				$pdf->SetXY(46, $y[32]);
				$out = $outputlangs->convToOutputCharset(yn($object->array_options['options_vcm_frigo']));
				$pdf->MultiCell(30, 0, $out,0,'L');

				if($object->array_options['options_vcm_frigo']==1){
					$list_value = $extrafields->attribute_param['vcm_marque']['options'];
					$pdf->SetFont('','', $default_font_size);
					$out = $outputlangs->convToOutputCharset('<b>Marque du groupe froid: </b>' . $list_value[$object->array_options['options_vcm_marque']]);
					$pdf->writeHTMLCell(194,5,65,$y[32],$out);

					$pdf->SetFont('','', $default_font_size);
					$out = $outputlangs->convToOutputCharset('<b>Modèle: </b>' . $object->array_options['options_vcm_model']);
					$pdf->writeHTMLCell(194,5,130,$y[32],$out);

					$pdf->SetFont('','', $default_font_size);
					$out = $outputlangs->convToOutputCharset("<b>Nombre d'heure de fonctionnement: </b>" . price($object->array_options['options_vcm_frigo_nbh']) . ' H/an');
					$pdf->writeHTMLCell(194,5,6.8,$y[33],$out);

					$list_value = $extrafields->attribute_param['vcm_fonct']['options'];
					$pdf->SetFont('','', $default_font_size);
					$out = $outputlangs->convToOutputCharset('<b>Fonctionnement du groupe: </b>' . $list_value[$object->array_options['options_vcm_fonct']]);
					$pdf->writeHTMLCell(194,5,130,$y[33],$out);
				}



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

