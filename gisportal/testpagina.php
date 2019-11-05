<?php
require('basicPage.php');
require('memory.php');

function is_connected_fsockopen($www) {
    $connected = @fsockopen($www, 80); 
    if ($connected){
        fclose($connected);
		return 'Connected';
    }
    return '<span class="test-error>"Not connected</span>';
}

function is_connected_ping($www) {
	exec('ping -n 4 '.$www.' 2>&1', $output, $retval);
	if ($retval != 0) {return '<span class="test-error>"Not connected</span>';}
	return 'Connected'; 
}

$r.='Deze testpagina checkt een aantal zaken...<br><br>';

$r.='<h2>Connectie met internet</h2>';
$r.='<table style="margin-bottom: 32px;"><tr><th>Site</th><th>fsockopen, poort 80</th><th>Ping</th></tr>';
$sites_to_check=['www.Site-die-echt-niet-bestaat.nl','www.google.nl','www.github.com','www.nu.nl'];
foreach ($sites_to_check as $site) {
	$r.='<tr><td>'.$site.'</td><td>'.is_connected_fsockopen($site).'</td><td>'.is_connected_ping($site).'</td></tr>';
}
$r.='</table>';

$r.='<h2>Perisisent storage</h2>';
$r.='<table style="margin-bottom: 32px;"><tr><th>Test</th><th>Resultaat</th></tr>';
$r.='<tr><td>Root map /geo-mappen:</td><td>'.(file_exists('/geo-mappen')?'Exists':'Does not exist').'</td></tr>';
$r.='<tr><td colspan="2">Persistent storage vlgs Openshift:</td><td style="text-align: right;">'.$memory->persistent_afk.'</td></tr>';
$r.='<tr><td>Openshift:</td><td style="text-align: right;">'.$memory->persistent.' b</td><td style="text-align: right;">'.$memory->persistent_mb.'</td></tr>';
$r.='<tr><td>Geheugen in gebruik:</td><td style="text-align: right;">'.$memory->used.' b</td></><td style="text-align: right;">'.$memory->used_mb.'</td></tr>';
$r.='<tr><td>Vrij geheugen:</td><td style="text-align: right;">'.$memory->available.' b</td><td style="text-align: right;">'.$memory->available_mb.'</td></tr>';
$r.='<tr><td>Totaal geheugen:</td><td style="text-align: right;">'.$memory->total.' b</td><td style="text-align: right;">'.$memory->total_mb.'</td></tr>';
$r.='</table>';

$r.='<h2>MYSQL</h2>';
$r.='<table style="margin-bottom: 32px;"><tr><th>Test</th><th>Resultaat</th></tr>';
$gps=$db->selectOne('geopackages','count(id) AS aantal','id>=1');
$r.='<tr><td>SELECT count(id) FROM geopackages</td><td>'.($gps?'Error: No result':$gps['aantal'].' records').'</td></tr>';
$r.='</table>';


$basicPage->render('Testpagina',$r);
?>