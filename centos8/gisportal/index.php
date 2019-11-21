<?php
require('basicPage.php');

if (isset($_GET['thema']) && isset($_GET['kaart'])) {
	$id=$db->selectOne('geopackages AS a LEFT JOIN onderwerpen AS b ON b.id=a.onderwerp','a.id','a.kaartnaam=\''.$_GET['kaart'].'\' AND b.afkorting=\''.$_GET['thema'].'\'');
	if ($id && $id['id']>=1) {
		if ($loggedIn) {
			$basicPage->redirect('/geo/portal/beheer/geo-package.php?id='.$id['id']);
		} else {
			$basicPage->redirect('/geo/portal/login.php?to=geo-package,'.$id['id']);
		}
	}
}

$r='<div style="background-image: url(css/78799.png); background-repeat: no-repeat; background-position: top right; padding-right: 400px; min-height: 310px;">';
$r.='Het GIS-portaal stelt gebruikers in staat om zelf kaarten te publiceren. Hiervoor dienen de volgende zaken te worden ingevoerd:';
$r.='<ol><li>Onderwerp, naam, e.d.</li><li>Een kaart (een bestand dat b,v. met ArcGIS of QGIS kan worden gemaakt) in de vorm van een .gpkg of .map file.</li><li>Eventueel bestand met opmaak van de kaart (bv. een .qgs of .qgz file).</li></ol>';
$r.='De afdelings-beheerder van deze portal kan gebruikers en onderwerpen aanmaken. Voor ondersteuning kun je mailen naar <a href="mailto:geodata&rivm.nl">GIS beheer</a>.';
$r.='<div style="text-align: right; margin: 20px 0; padding-right: 100px;"><a class="small-button" href="testpagina.php">Testpagina</a></div>';
$r.='</div>';
$basicPage->render('Home',$r);
?>