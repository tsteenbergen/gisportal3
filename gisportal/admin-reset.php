<?php
require('basicPage.php');

$title='Administrator reset';
$r='';
if ($loggedIn && $is_admin) {
	$r.='Bij een reset wordt voor elk van geo-packages die voldoet aan het filter, het volgende gedaan:<ol>';
	$r.='<li>Als \'Verwijder uploads\' is aangevinkt, dan worden alle bestanden van de betreffende geo-package verwijderd. Deze zullen dus opnieuw moeten worden geupload.</li>';
	$r.='<li>Op het containerplatform worden de volgende zaken verwijderd:</li>';
	$r.='<li><ul>';
	$r.='<li>replicationcontroller</li>';
	$r.='<li>autoscaler</li>';
	$r.='<li>deploymentconfig</li>';
	$r.='<li>pod(s)</li>';
	$r.='<li>service</li>';
	$r.='<li>route</li>';
	$r.='</ul></li>';
	$r.='<li>Bovenstaande zaken worden vervolgens opnieuw aangemaakt (behalve de pods, die \'ontstaan vanzelf\' door de nieuwe deploymentconfig).</li>';
	$r.='</ol>';
	
	$func=$_POST['func'];
	$id=isset($_POST['id'])?(int)$_POST['id']:1;
	// Betekenis van $id:
	// 1 is toon de pagina waarop met het filter kan samenstellen
	// 2 is toon de pagina opnieuw, maar geef nu aan wat de consequenties zijn
	// 3 is: Voer e.e.a. uit en laat de voortgang zien. Als het gedaan is; Ga naar $id=1
	if ($func=='opslaan') {
		$id++;
	}
	$r.='<div style="display: inline-block;"><form id="form" method="POST"><input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="func" id="func">';
	switch ($id) {
		case 1:
			$r.='Stap 1: Samenstellen filter<table>';
			$r.='<tr><td colspan="2"><input type="checkbox" name="delete_files" id="delete_files"><label for="delete_files"> Verwijder uploads.</label></td></tr>';
			break;
		case 2:
			$r.='Stap 2: Controle gevolgen<table>';
			break;
		case 3:
			$r.='Stap 3: Uitvoeren<table>';
			break;
	}
	$r.='</table></form>';
	$r.='<div class="button-below"><button onclick="formOpslaan();">Opslaan</button></div></div>';
	
} else {
	$basicPage->fout('Autorisatie','Je hebt niet de juiste autorisatie.');
}

$basicPage->render($title,$r);
?>