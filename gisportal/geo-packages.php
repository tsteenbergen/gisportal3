<?php  //Start the Session

require('basicPage.php');

if ($loggedIn){
	
	$r.='<div style="background-image: url(css/99669.png); background-repeat: no-repeat; float: right; z-index: -1; margin-top: -64px; width: 450px; height: 300px;"></div><div style="text-align: right; max-width: 500px; margin-top: -52px; margin-bottom: 20px;"><a class="small-button" href="/geo/portal/beheer/geo-package.php?id=0">Nieuw geopackage</a></div>';
	$basicPage->add_js_ready('$(\'.content\').css(\'min-height\',\'320px\');');
	
	if ($is_admin) {
		$afds=array('0=- alle afdelingen -');
		$a=$db->select('afdelingen','id,naam','id>=1','naam');
		if ($a) {foreach ($a as $b) {$afds[]=$b['id'].'='.htmlspecialchars($b['naam']);}}
		if (isset($_GET['afd'])) {$afd=$_GET['afd'];} else {$afd=0;}
	} else {
		$afds=array($my_afd.'=Mijn afdeling');
		$afd=$my_afd;
	}
	$a=$db->select('onderwerpen','id,afkorting','id>=1','afkorting');
	$onds=array('0=- alle onderwerpen -');
	if ($a) {foreach ($a as $b) {$onds[]=$b['id'].'='.htmlspecialchars($b['afkorting']);}}
	if (isset($_GET['ond'])) {$ond=$_GET['ond'];} else {$ond=0;}
	$naam=htmlspecialchars($_GET['naam']);
	$back=base64_encode($a.chr(1).$ond.chr(1).$naam);
	
	$r.='<table>';
	$r.='<tr><td>Filter op &hellip;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>Afdeling:</td><td>'.$basicPage->getSelect('filter-afd',$afd,$afds,!$is_admin,'filter_geo_package_change();').'</td></tr>';
	$r.='<tr><td></td><td>Onderwerp:</td><td>'.$basicPage->getSelect('filter-ond',$ond,$onds,false,'filter_geo_package_change();').'</td></tr>';
	$r.='<tr><td></td><td>Naam:</td><td><input id="filter-naam" onkeyup="filter_geo_package_change();" value="'.$naam.'"></td></tr>';
	$r.='</table>';

	$r.='<div class="seperator"></div>';

	$gs=$db->select('geopackages AS a LEFT JOIN afdelingen AS b ON a.afdeling=b.id LEFT JOIN onderwerpen AS c ON a.onderwerp=c.id','a.id,a.naam,c.naam AS thema,b.naam AS afd_naam','a.id>=1'.($afd>=1?' AND a.afdeling='.$afd:'').($ond>=1?' AND a.onderwerp='.$ond:'').($naam==''?'':' AND a.naam LIKE \'%'.$naam.'%\''));

	
	$r.='<table class="colored">';
	$r.='<tr class="header"><td>Afdeling</td><td>Onderwerp</td><td>Naam</td><td></td></tr>';
	if ($gs) {
		foreach ($gs as $g) {
			$r.='<tr><td>'.htmlspecialchars($g['afd_naam']).'</td><td>'.htmlspecialchars($g['thema']).'</td><td>'.htmlspecialchars($g['naam']).'</td><td><a class="small-button" href="/geo/portal/beheer/geo-package.php?id='.$g['id'].'&back='.$back.'">Bewerk</a><a class="small-button" style="margin-left: 20px;" href="kaart.php?id='.$g['id'].'&back='.$back.'">Kaart</a></td></tr>';
		}
	}
	$r.='</table>';
} else {
    $basicPage->fout('Autorisatie','Je bent niet ingelogd.');
}
$basicPage->render('Geopackages',$r);
?>