<?php
$res = @include '../main.inc.php'; // For root directory
if (! $res) {
	$res = @include '../../main.inc.php'; // For "custom" directory
}
if (! $res) {
	die("Include of main fails");
}
	
//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled to increase speed. Language code is found on url.
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);          // File must be accessed by logon page so without login
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // We need top menu content
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');

// Define css type
top_httphead('text/css');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');


$colortopbordertitle1=empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_TOPBORDER_TITLE1)?$colortopbordertitle1:$conf->global->THEME_ELDY_TOPBORDER_TITLE1)   :(empty($user->conf->THEME_ELDY_TOPBORDER_TITLE1)?$colortopbordertitle1:$user->conf->THEME_ELDY_TOPBORDER_TITLE1);
?>
.ficheaddleft table.noborderaffaires {
	margin: 0px 0px 0px 0px;
}
table.noborderaffaires, div.noborderaffaires {
	width: 100%;

	border-collapse: separate !important;
	border-spacing: 0px;

	border-top-width: 2px;
	border-top-color: rgb(<?php echo $colortopbordertitle1;?>);
	border-top-style: solid;

	border-bottom-width: 1px;
	border-bottom-color: #BBB;
	border-bottom-style: solid;

	margin: 0px 0px 5px 0px;
}
table.noborderaffaires:last-of-type {
    border-bottom: 1px solid #aaa;
}
table.noborderaffaires {
    border-bottom: none;
}
table.noborderaffaires:last-of-type {
    border-bottom: 1px solid #aaa;
}
table.noborderaffaires {
    border-bottom: none;
}
.noborderaffaires {
    white-space: nowrap;
}
.noborderaffaires {
	white-space: normal;
}
table.noborderaffaires tr{
	border-top-color: #FEFEFE;
	min-height: 20px;
}
table.noborderaffaires th, table.noborderaffaires tr.liste_titre td, table.noborderaffaires tr.box_titre td {
	padding: 7px 8px 7px 8px;			/* t r b l */
}
table.noborderaffaires td, div.noborderaffaires form div {
	padding: 7px 8px 7px 8px;			/* t r b l */
	line-height: 1.2em;
}
<?php
if (is_object($db)) $db->close();
