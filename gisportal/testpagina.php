<?php
require('basicPage.php');


$r.='Deze testpagina checkt of de container waarop dit portal draait een connectie met het internet kan maken.<br><br>';
$fc=file_get_contents('https://www.nu.nl');
$r.='www.nu.nl: '.substr($fc,0,80).'<br><br>';
$fc=file_get_contents('https://www.nunoiitoftenimmer1.nl');
$r.='www.nunoiitoftenimmer1.nl: '.substr($fc,0,80).'<br><br>';
$basicPage->render('Testpagina',$r);
?>