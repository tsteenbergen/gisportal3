<?php
require('basicPage.php');
require('openshift-api.php');

$title='Kaart';
$r='';
if ($loggedIn){
	if (isset($_GET['id'])) {
		$id=$_GET['id'];
		if ($id>=1) {
			$back=explode(chr(1),base64_decode($_GET['back']));
			if (count($back)==3) {$back='?a='.$back[0].'&ond='.$back[1].'&naam='.$back[2];} else {$back='';}
			$r.='<button onclick="location.href=\'/geo-packages.php'.$back.'\';" style="margin-bottom: 40px;">Terug</button>';
			
			$openshift_api->command('oapi','routes/route-gpid-'.$id);
			if ($openshift_api->response->kind=='Route') {
				$spec=$openshift_api->response->spec;
				$host=$spec->host.$spec->path;
				if (substr($host,0,4)!='http') {$host='http://'.$host;}
				$r.='<br><br>Host: '.$host.'<br><br>';
				$capabilities=file_get_contents($host.'?SERVICE=WMS&VERSION=1.3.0&REQUEST=GetCapabilities');
				$xml=simplexml_load_string($capabilities);
				$r.='Getcapabilities: <div style="margin-bottom: 32px;"><code class="language-xml">'.$capabilities.'</code></div>';

				$baselayer=$xml->Capability->Layer->Name;
				$r.='<img src="'.$host.'?SERVICE=WMS&VERSION=1.3.0&REQUEST=GetMap&BBOX=202786,7072992,2158959,7213746&SRS=EPSG:3857&WIDTH=665&HEIGHT=551&LAYERS='.$baselayer.'&FORMAT=image/jpeg">';
				
			} else {
				$basicPage->fout('Route','Route route-gpid-'.$id.' not found.');
			}
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