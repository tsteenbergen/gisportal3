<?php
require('basicPage.php');
require('openshift-api.php');

// https://acceptatie-data.rivm.nl/geo/rivm/toon-ms02?SERVICE=WMS&VERSION=1.1.1&REQUEST=Getmap&BBOX=2000000,1000000,8000000,6000000&SRS=EPSG:3035&FORMAT=PNG&WIDTH=800&HEIGHT=600&map=/geo-map/source.map&LAYERS=nuts_02_2016
// https://acceptatie-data.rivm.nl/geo/RDG-test/nationaleparken?SERVICE=WMS&VERSION=1.3.0&REQUEST=GetMap&BBOX=202786,7072992,2158959,7213746 &SRS=EPSG:3857&WIDTH=665&HEIGHT=551&LAYERS=&FORMAT=image/jpeg

$title='Kaart';
$r='';
if ($loggedIn){
	if (isset($_GET['id'])) {
		$id=$_GET['id'];
		if ($id>=1) {
			$back=explode(chr(1),base64_decode($_GET['back']));
			if (count($back)==3) {$back='?a='.$back[0].'&ond='.$back[1].'&naam='.$back[2];} else {$back='';}
			$r.='<button onclick="location.href=\'/geo/portal/geo-packages.php'.$back.'\';" style="margin-bottom: 40px;">Terug</button>';
			$r.='<div id="kaart"></div>';
			$kaart=$db->selectOne('geopackages AS a LEFT join onderwerpen AS b ON b.id=a.onderwerp','a.kaartnaam,b.afkorting','a.id='.$id);
			$js='show_kaart(\''.$kaart['afkorting'].'/'.$kaart['kaartnaam'].'\');';
/*			
				$capabilities=file_get_contents($host.'?SERVICE=WMS&VERSION=1.3.0&REQUEST=GetCapabilities');
				$xml=simplexml_load_string($capabilities);
				$r.='Getcapabilities: <div style="margin-bottom: 32px;"><code class="language-xml">'.$capabilities.'</code></div>';

				$baselayer=$xml->Capability->Layer->Name;
				$r.='<img src="'.$host.'?SERVICE=WMS&VERSION=1.3.0&REQUEST=GetMap&BBOX=202786,7072992,2158959,7213746&SRS=EPSG:3857&WIDTH=665&HEIGHT=551&LAYERS='.$baselayer.'&FORMAT=image/jpeg">';
				
			} else {
				$basicPage->fout('Route','Route gpid-'.$id.' not found.');
			} */
			$basicPage->add_js_ready($js);
		} else {
			$basicPage->fout('Internal error','Geopackage niet gevonden.');
		}
	} else {
		$basicPage->fout('Internal error','Er is geen ID opgegeven.');
	}
} else {
	$basicPage->fout('Autorisatie','Je hebt niet de juiste autorisatie.');
}

$basicPage->render($title,$r);
?>