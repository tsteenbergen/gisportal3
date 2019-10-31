<?php
require('basicPage.php');

$title='Administrator reset';
$r='';
if ($loggedIn && $is_admin) {
	$r.='Bij een reset wordt voor elk van geo-packages die voldoet aan het filter, het volgende gedaan:<ol>';
	$r.='<li>Als \'Verwijder uploads\' is aangevinkt, dan worden alle bestanden van de betreffende geo-package verwijderd. Deze zullen dus opnieuw moeten worden geupload.</li>';
	$r.='<li>Op het containerplatform worden de volgende zaken verwijderd:<ul>';
	$r.='<li>replicationcontroller</li>';
	$r.='<li>autoscaler</li>';
	$r.='<li>deploymentconfig</li>';
	$r.='<li>pod(s)</li>';
	$r.='<li>service</li>';
	$r.='<li>route</li>';
	$r.='</ul></li>';
	$r.='<li>Bovenstaande zaken worden vervolgens opnieuw aangemaakt (behalve de pods, die \'ontstaan vanzelf\' door de nieuwe deploymentconfig).</li>';
	$r.='</ol>';
	$r.='<br>Om de reset uit te voeren worden 3 stappen doorlopen:<ol><li>Filter instellen</li><li>Controle gevolgen</li><li>Uitvoering</li></ol>';
	
	$func=$_POST['func'];
	$r.='<div style="display: inline-block;"><form id="form" method="POST">';
	switch ($func) {
		case 'controle':
			$r.='<input type="hidden" name="func" value="uitvoeren">';
			$r.='<h2>Stap 2: Controle gevolgen</h2><table>';
			break;
		case 'uitvoeren':
			$r.='<input type="hidden" name="func" value="">';
			$r.='<h2>Stap 3: Uitvoering</h2><table>';
			break;
		default:
			$r.='<input type="hidden" name="func" value="controle">';
			$r.='<h2>Stap 1: Filter instellen</h2><table>';
			$r.='<tr><td colspan="2"><input type="checkbox" name="delete_files" id="delete_files"><label for="delete_files"> Verwijder uploads.</label></td></tr>';
			break;
	}
	$r.='</table></form>';
	$r.='<div class="button-below"><button onclick="$(\'#form\').submit();">Opslaan</button></div></div>';
	
} else {
	$basicPage->fout('Autorisatie','Je hebt niet de juiste autorisatie.');
}

$basicPage->render($title,$r);
?>