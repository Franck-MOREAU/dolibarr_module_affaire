<?php

$res = @include '../../main.inc.php'; // For root directory
if (! $res)
	$res = @include '../../../main.inc.php'; // For "custom" directory
if (! $res)
	die("Include of main fails");

global $db, $user;

$vehid = GETPOST('vehid');
$new_statut = GETPOST('new_statut');
$action = GETPOST('action');

if ($action=='updatestatus') {
	dol_include_once('/affaires/class/affaires.class.php');
	$objectdet = new Affaires_det($db);
	$res = $objectdet->fetch($vehid);
	if ($res<0) {
		foreach($objectdet->errors as $error) {
			print $error;
			exit;
		}
	}
	$objectdet->fk_status=$new_statut;
	$res = $objectdet->update($user);
	if ($res<0) {
		foreach($objectdet->errors as $error) {
			print $error;
			exit;
		}
	}
	print 1;
}