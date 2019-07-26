<?php
require('basicPage.php');


$r='<div style="background-image: url(css/78799.png); background-repeat: no-repeat; background-position: top right; padding-right: 400px; min-height: 310px;">';
$r.='Het GIS-portaal stelt gebruikers in staat om zelf kaarten te publiceren. Hiervoor dienen de volgende zaken te worden ingevoerd:';
$r.='<ol><li>Onderwerp, naam, e.d.</li><li>Een Geopackage (een bestand dat b,v. met ArcGIS of QGIS kan worden gemaakt).</li><li>Een link naar RIVM-data waar de metadata staat.</li><li>Een XML-file met de opmaak van de kaart (ook deze kan met bv ArcGIS worden gemaaakt).</li></ol>';
$r.='De afdelings-beheerder van deze protal kan gebruikers en onderwerpen aanmaken. Voor ondersteuning kun je mailen naar <a href="mailto:">GIS beheer</a>';
$r.='</div>';
$basicPage->render('Home',$r);
?>