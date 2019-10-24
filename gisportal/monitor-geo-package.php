<?php
require('basicPage.php');

$title='Monitor geopackage';
$r='';
if ($loggedIn){
	if (isset($_GET['id'])) {
		$id=$_GET['id'];
		$velden='id,afdeling,onderwerp,naam,soort,brongeopackage,indatalink,datalink,opmaak,wms,wfs,wcs,wmts';
		if ($id>=1) {
			$g=$db->selectOne('geopackages',$velden,'id='.$id.($is_admin?'':' AND afdeling='.$my_afd));
			if ($g) {
				$back=explode(chr(1),base64_decode($_GET['back']));
				if (count($back)==3) {$back='?a='.$back[0].'&ond='.$back[1].'&naam='.$back[2];} else {$back='';}
				$r.='<button onclick="location.href=\'/geo/geo-packages.php'.$back.'\';" style="margin-bottom: 40px;">Terug</button>';


				$basicPage->add_js_ready('monitorPod('.$id.');');
				$r.='<div>Monitoring: <span id="counter" c="0"></span></div>';
				$r.='<table>';
				$r.='<tr><td>Naam:</td><td>&nbsp;&nbsp;&nbsp;&nbsp;</td><td id="pod-name"></td></tr>';
				$r.='<tr><td>Status:</td><td>&nbsp;&nbsp;&nbsp;&nbsp;</td><td id="pod-status"></td></tr>';
				$r.='</table>';
				$r.='<div style="text-align: left;"><a class="small-button" href="#" onclick="$(\'#monitor\').toggle();">Toggle all info</a></div>';
				$r.='<div id="monitor" style="font-size: 0.9em; display: none;"></div>';
				
//				$r.='<button onclick="podFunctions(this,\'delete\','.$g['id'].');" style="margin: 20px 40px 20px 0;">Delete POD</button>';
//				$r.='<button onclick="podFunctions(this,\'create\','.$g['id'].');" style="margin: 20px 40px 20px 0;">Create POD</button>';
//				$r.='<button onclick="podFunctions(this,\'regenerate\','.$g['id'].');" style="margin: 20px 40px 20px 0;">(re)Genereer POD</button>';
				$r.='<div id="podfunc"></div>';
				$r.='</div>';
			} else {
				$basicPage->fout('Internal error','Geopackage niet gevonden.');
			}
		}
	} else {
		$basicPage->fout('Internal error','Er is geen ID opgegeven.');
	}
} else {
	$basicPage->fout('Autorisatie','Je hebt niet de juiste autorisatie.');
}

$basicPage->render($title,$r);
?>