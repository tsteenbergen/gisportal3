<?php
require('basicPage.php');


$r='<div style="background-image: url(css/78799.png); background-repeat: no-repeat; background-position: top right; padding-right: 400px; min-height: 310px;">';
$r.='Het GIS-portaal stelt gebruikers in staat om zelf kaarten te publiceren. Hiervoor dienen de volgende zaken te worden ingevoerd:';
$r.='<ol><li>Onderwerp, naam, e.d.</li><li>Een Geopackage (een bestand dat b,v. met ArcGIS of QGIS kan worden gemaakt).</li><li>Een link naar RIVM-data waar de metadata staat.</li></ol>';
$r.='De afdelings-beheerder van deze portal kan gebruikers en onderwerpen aanmaken. Voor ondersteuning kun je mailen naar <a href="mailto:geodata&rivm.nl">GIS beheer</a>.';
$r.='<div style="text-align: right; margin: 20px 0; padding-right: 100px;"><a class="small-button" href="testpagina.php">Testpagina</a></div>';
$r.='</div>';
$basicPage->render('Home',$r);
?>