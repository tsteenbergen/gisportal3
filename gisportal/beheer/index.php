<?php
require('basicPage.php');

if ($loggedIn && ($is_admin || $is_afd_admin)){
	$tabA='';
	if ($is_admin) {
		$tabA='<div id="tabs-A" style="vertical-align: top;">';
		$afds=$db->select('afdelingen','id,naam','id>=1','naam');
		$tabA.='<table class="colored">';
		$tabA.='<tr class="top-button"><td colspan="2"><a class="small-button" href="/beheer/afdeling.php?id=0">Nieuwe afdeling</a></td></tr>';
		$tabA.='<tr class="header"><td style="min-width: 160px;">Naam</td><td></td></tr>';
		if ($afds) {
			foreach ($afds as $afd) {
				$tabA.='<tr><td>'.htmlspecialchars($afd['naam']).'</td><td><a class="small-button" href="/beheer/afdeling.php?id='.$afd['id'].'">Bewerk</a></td></tr>';
			}
		}
		$tabA.='</table></div>';

		$tabA.='<div id="tabs-A1" style="vertical-align: top;">';
		$imgs=$db->select('images','id,image,repo,deflt','id>=1','image');
		$tabA.='<table class="colored">';
		$tabA.='<tr class="top-button"><td colspan="4"><a class="small-button" href="/beheer/image.php?id=0">Nieuw image</a></td></tr>';
		$tabA.='<tr class="header"><td></td><td style="min-width: 160px;">Image</td><td>Repo</td><td></td><td style="width: 20px;"></td><td>Versions</td></tr>';
		if ($imgs) {
			foreach ($imgs as $img) {
				$vs=$db->select('versions','id,version,deflt','image='.$img['id']);
				$versions='<table>';
				if ($vs) foreach ($vs as $v) {
					$versions.='<tr><td>'.($v['deflt']=='J'?'Default':'').'</td><td>'.$v['version'].'</td><td><a class="small-button" href="/beheer/version.php?id='.$v['id'].'&iid='.$img['id'].'">Bewerk</a></td></tr>';
				}
				$versions.='</table>';
				$tabA.='<tr><td>'.($img['deflt']=='J'?'Default':'').'</td><td>'.htmlspecialchars($img['image']).'</td><td>'.htmlspecialchars($img['repo']).'</td><td><a class="small-button" href="/beheer/image.php?id='.$img['id'].'">Bewerk image</a><a class="small-button" style="margin-left: 20px;" href="/beheer/version.php?id=0&iid='.$img['id'].'">Nieuwe versie</a></td><td></td><td>'.$versions.'</td></tr>';
			}
		}
		$tabA.='</table></div>';
		
	}
	
	$tabO='<div id="tabs-O" style="vertical-align: top;">';
	$onds=$db->select('onderwerpen AS a LEFT JOIN afdelingen AS b ON a.afdeling=b.id','a.id,a.afkorting,a.naam,b.naam AS afd_naam','a.id>=1'.($is_afd_admin?' AND a.afdeling='.$my_afd:''),'afd_naam,a.naam');
	$tabO.='<table class="colored">';
	$tabO.='<tr class="top-button"><td colspan="4"><a class="small-button" href="/beheer/onderwerp.php?id=0">Nieuw onderwerp</a></td></tr>';
	$tabO.='<tr class="header"><td>Afdeling</td><td>Afkorting</td><td>Naam</td><td></td></tr>';
	if ($onds) {
		foreach ($onds as $ond) {
			$tabO.='<tr><td>'.htmlspecialchars($ond['afd_naam']).'</td><td>'.htmlspecialchars($ond['afkorting']).'</td><td>'.htmlspecialchars($ond['naam']).'</td><td><a class="small-button" href="/beheer/onderwerp.php?id='.$ond['id'].'">Bewerk</a></td></tr>';
		}
	}
	$tabO.='</table></div>';
	
	$tabP='<div id="tabs-P" style="vertical-align: top;">';
	$pers=$db->select('personen AS a LEFT JOIN afdelingen AS b ON a.afdeling=b.id','a.id,a.naam,b.naam AS afd_naam,a.ad_account,a.email,a.afd_admin,a.admin','a.id>=1'.($is_afd_admin?' AND a.afdeling='.$my_afd:''),'afd_naam,a.naam');
	$tabP.='<table class="colored">';
	$tabP.='<tr class="top-button"><td colspan="6"><div style="float: left;">Filter op:<input size="20" style="margin-left: 30px;" onkeyup="filterPersonen(this);"></div><a class="small-button" href="/beheer/persoon.php?id=0">Nieuwe persoon</a></td></tr>';
	$tabP.='<tr class="header"><td>Afdeling</td><td>Naam</td><td>AD account</td><td>E-mail</td><td>Autorisatie</td><td></td></tr>';
	if ($pers) {
		foreach ($pers as $per) {
			$tabP.='<tr class="TRpersoon"><td>'.htmlspecialchars($per['afd_naam']).'</td><td>'.htmlspecialchars($per['naam']).'</td><td>'.htmlspecialchars($per['ad_account']).'</td><td>'.htmlspecialchars($per['email']).'</td><td>'.($per['afd_admin']=='J'?'Afd. beheerder':($per['admin']=='J'?'Administrator':'')).'</td><td><a class="small-button" href="/beheer/persoon.php?id='.$per['id'].'">Bewerk</a></td></tr>';
		}
	}
	$tabP.='</table></div>';
	
	$r.='<div id="tabs">';
	$r.='<ul><li><a href="#tabs-P">Personen</a></li><li><a href="#tabs-O">Onderwerpen</a></li>';
	if ($tabA!='') {$r.='<li><a href="#tabs-A">Afdelingen</a></li><li><a href="#tabs-A1">Images</a></li>';}
	$r.='</ul>';
	$r.=$tabP.$tabO;
	if ($tabA!='') {$r.=$tabA;}
	$r.='</div>';
	$tab=$_GET['tab']; if ($tab=='') {$tab=0;}
	$basicPage->add_js_ready('$( "#tabs" ).tabs({active: '.$tab.',heightStyle: \'auto\'});');
} else {
    $basicPage->fout('Autorisatie','Je hebt niet de juiste autorisatie.');
}

$basicPage->render('Beheer',$r);
?>