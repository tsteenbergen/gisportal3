<?php
require('basicPage.php');

$title='Administrator reset';
$r='';
if ($loggedIn && $is_admin) {
	switch($_POST['func']) {
		default:
			$r.='Bij een reset wordt voor elk van geo-packages die voldoet aan het filter, het volgende gedaan:<ol>';
			$r.='<li>Op het containerplatform worden de volgende zaken verwijderd:<ul>';
			$r.='<li>replicationcontroller</li>';
			$r.='<li>autoscaler</li>';
			$r.='<li>deploymentconfig</li>';
			$r.='<li>pod(s)</li>';
			$r.='<li>service</li>';
			$r.='<li>route</li>';
			$r.='</ul></li>';
			$r.='<li>Bovenstaande zaken worden vervolgens opnieuw aangemaakt (behalve de pods, die \'ontstaan vanzelf\' door de nieuwe deploymentconfig).</li>';
			$r.='<li>Als \'Verwijder uploads\' is aangevinkt, dan worden alle bestanden van de betreffende geo-package verwijderd. Deze zullen dus opnieuw moeten worden geupload.</li>';
			$r.='</ol>';
			$r.='<br>Om de reset uit te voeren worden 3 stappen doorlopen:<ol><li>Filter instellen</li><li>Controle gevolgen</li><li>Uitvoering</li></ol>';
			
			$r.='<div id="stap1"><h2>Filter instellen</h2>';
			$r.='<div class="error"></div>';
			$r.='<table>';
			$themas=$db->select('onderwerpen AS a LEFT JOIN afdelingen AS b ON b.id=a.afdeling','a.id,a.naam,a.afkorting,b.naam AS afdeling','a.id>=1');
			$kaarten=$db->select('geopackages','id,naam,kaartnaam,onderwerp','id>=1');
			$js='var kaarten=['; foreach ($kaarten as $kaart) {$js.='['.$kaart['id'].','.$kaart['onderwerp'].',\''.htmlspecialchars($kaart['naam'].' ('.$kaart['kaartnaam'].')\'').'],';} $js.='];';
			$basicPage->add_js_inline($js);
			$basicPage->add_js_ready('depententSelect(\'sel_kaarten\',\'sel_themas\',kaarten,0,0,\'-- Alle kaarten --\')');
			$r.='<tr><td>Kies thema:</td><td><select id="sel_themas"><option value="0">-- Alle themas --</option>';
			foreach ($themas as $thema) {$r.='<option value="'.$thema['id'].'">'.htmlspecialchars($thema['afdeling'].' '.$thema['afkorting'].':'.$thema['naam']).'</option>';}
			$r.='</select></td></tr>';
			$r.='<tr><td>Kies kaart:</td><td><select id="sel_kaarten"></select></td></tr>';
			$r.='<tr><td></td><td><input type="checkbox" id="del_uploads"><label for="del_uploads"> Verwijder uploads</label></td></tr>';
			$r.='</table>';
			$r.='<button onclick="admin_reset(\'controle\');" class="aknop aknop1">Controle gevolgen</button>';
			$r.='</div>';
			
			$r.='<div id="stap2" style="display: none;"><h2>Controle gevolgen</h2>';
			$r.='<div class="error"></div>';
			$r.='<div id="stap2msg"></div>';
			$r.='<table>';
			$r.='<tr><td></td><td><input type="checkbox" id="del_uploads_akkoord"><label for="del_uploads_akkoord"> Ja, dit wil ik</label></td></tr>';
			$r.='</table>';
			$r.='<button onclick="admin_reset(\'\');" class="aknop aknop2">Filter (opnieuw) instellen</button>';
			$r.='<button style="margin-left: 40px;" onclick="admin_reset(\'uitvoeren\');" class="aknop aknop2">Uitvoeren</button>';
			$r.='</div>';
			
			$r.='<div id="stap3" style="display: none;"><h2>Uitvoering</h2>';
			$r.='<div class="error"></div>';
			$r.='<button onclick="admin_reset(\'\');" class="aknop aknop3">Klaar</button>';
			$r.='</div>';
			break;
		case 'controle':
			$thema=(int)$_POST['thema']; $kaart=(int)$_POST['kaart']; $del_uploads=$_POST['del_uploads'];
			$error=false;
			if ($thema>=1) {
				if ($kaart>=1) { // 1 kaart
					$kaarten=$db->select('geopackages AS a LEFT JOIN onderwerpen AS b ON b.id=a.onderwerp LEFT JOIN afdelingen AS c ON c.id=a.afdeling', 'a.id,a.naam,a.kaartnaam,b.naam as thema, c.naam as afdeling', 'a.id='.$kaart, 'c.naam,b.naam,a.naam,a.kaartnaam');
				} else { // alle kaarten van dit thema
					$kaarten=$db->select('geopackages AS a LEFT JOIN onderwerpen AS b ON b.id=a.onderwerp LEFT JOIN afdelingen AS c ON c.id=a.afdeling', 'a.id,a.naam,a.kaartnaam,b.naam as thema, c.naam as afdeling', 'a.onderwerp='.$thema, 'c.naam,b.naam,a.naam,a.kaartnaam');
				}
			} else { // alle kaarten
				$kaarten=$db->select('geopackages AS a LEFT JOIN onderwerpen AS b ON b.id=a.onderwerp LEFT JOIN afdelingen AS c ON c.id=a.afdeling', 'a.id,a.naam,a.kaartnaam,b.naam as thema, c.naam as afdeling', 'a.id>=1', 'c.naam,b.naam,a.naam,a.kaartnaam');
			}
			if ($kaarten) {
				$c=count($kaarten);
				$msg='Er '.($c==1?'is 1 kaart die voldoet':'zijn '.$c.' kaarten die voldoen').' aan dit filter:<table>';
				for ($t=0;$t<50;$t++) {
					if ($t<$c) {
						$k=$kaarten[$t];
						$msg.='<tr><td>'.htmlspecialchars($k['afdeling']).'</td><td>'.htmlspecialchars($k['thema']).'</td><td>'.htmlspecialchars($k['naam']).'</td><td>'.htmlspecialchars($k['kaartnaam']).'</td></tr>';
					}
				}
				if ($c>50) {
					$msg.='<tr><td>&hellip;</td><td>&hellip;</td><td>&hellip;</td><td>&hellip;</td></tr>';
				}
				$msg.='</table>';
			} else {
				$msg='Er zijn geen kaarten die voldoen aan dit filter.';
				$error=true;
			}
			$r=['msg'=>$msg, 'error'=>$error, 'thema'=>$thema, 'kaart'=>$kaart, 'del_uploads'=>$del_uploads];
			echo json_encode($r);
			exit();
			break;
		case 'uitvoeren':
			$thema=(int)$_POST['thema']; $kaart=(int)$_POST['kaart']; $del_uploads=$_POST['del_uploads']; $reset_akkoord=$_POST['reset_akkoord'];
			$r=['msg'=>'Uitvoering starten', 'error'=>false, 'thema'=>$thema, 'kaart'=>$kaart, 'del_uploads'=>$del_uploads, 'reset_akkoord'=>$reset_akkoord];
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