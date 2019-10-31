<?php
require('basicPage.php');

$title='Administrator reset';
$r='';
if ($loggedIn && $is_admin) {
	switch($_POST['func']) {
		default:
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
			
			$r.='<div id="stap1"><h2>Filter instellen</h2>';
			$r.='<div class="error"></div>';
			$r.='<table>';
			$themas=$db->select('onderwerpen AS a LEFT JOIN afdelingen AS b ON b.id=a.afdeling','a.id,a.naam,a.afkorting,b.naam AS afdeling','a.id>=1');
			$kaarten=$db->select('geopackages','id,naam,kaartnaam,onderwerp','id>=1');
			$js='var themas=['; foreach ($themas as $thema) {$js.='['.$thema['id'].',\''.htmlspecialchars($thema['afdeling'].' '.$thema['afkorting'].':'.$thema['naam']).'\'],';} $js.='];';
			$js='var kaarten=['; foreach ($kaarten as $kaart) {$js.='['.$kaart['id'].',['.$kaart['onderwerp'].',\''.htmlspecialchars($kaart['naam'].' ('.$kaart['kaartnaam'].')').'],';} $js.='];';
			$basicPage->add_js_inline($js);
			$r.='<tr><td>Kies thema:</td><td><select></td></tr>';
			$r.='<tr><td>Kies kaart:</td><td><select></td></tr>';
			$r.='</table>';
			$r.='<button onclick="admin_reset(\'controle\');" class="aknop aknop1">Controle gevolgen</button>';
			$r.='</div>';
			
			$r.='<div id="stap2" style="display: none;"><h2>Controle gevolgen</h2>';
			$r.='<div class="error"></div>';
			$r.='<button onclick="admin_reset(\'\');" class="aknop aknop2">Filter (opnieuw) instellen</button>';
			$r.='<button onclick="admin_reset(\'uitvoeren\');" class="aknop aknop2">Uitvoeren</button>';
			$r.='</div>';
			
			$r.='<div id="stap3" style="display: none;"><h2>Uitvoering</h2>';
			$r.='<div class="error"></div>';
			$r.='<button onclick="admin_reset(\'\');" class="aknop aknop3">Klaar</button>';
			$r.='</div>';
			break;
		case 'controle':
			$r=['msg'=>'Controle oke', 'error'=>false];
			echo json_encode($r);
			exit();
			break;
		case 'uitvoeren':
			$r=['msg'=>'Uitvoring gestart', 'error'=>false];
			echo json_encode($r);
			exit();
			break;
		case 'niets':
			$r=['error'=>false];
			echo json_encode($r);
			exit();
			break;
	}
	
} else {
	$basicPage->fout('Autorisatie','Je hebt niet de juiste autorisatie.');
}

$basicPage->render($title,$r);
?>