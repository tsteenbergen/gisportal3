<?php
require('basicPage.php');

if ($loggedIn && $is_admin){
	switch ($_GET['func']) {
		case 'log':
			$r.=file_get_contents($basicPage->getConfig('logfile'));
			break;
		case 'phpinfo':
			$r.=phpinfo();
			break;
		case 'apicall':
			if (isset($_GET['go'])) {$apicall=explode('|',$_GET['go']); $api=$apicall[0]; $apisub=$apicall[2]; $apicall=$apicall[1];} else {$apicall='';}
			$r.='<p>Api call: <select id="api"><option'.($api=='api'?' selected="selected"':'').'>api</option><option'.($api=='oapi'?' selected="selected"':'').'>oapi</option></select><input id="apicall" style="width: calc(100% - 250px);" value="'.$apicall.'"><select id="apisub"><option value="false"'.($apisub=='false'?' selected="selected"':'').'>(false)</option><option'.($apisub=='POST'?' selected="selected"':'').'>POST</option><option'.($apisub=='PUT'?' selected="selected"':'').'>PUT</option><option'.($apisub=='DELETE'?' selected="selected"':'').'>DELETE</option></select><a onclick="document.location=\'admin.php?func=apicall&go=\'+$(\'#api\').val()+\'|\'+$(\'#apicall\').val()+\'|\'+$(\'#apisub\').val();" class="small-button" style="margin-left: 12px;">Go</a></p>';
			if ($apicall!='') {
				require('openshift-api.php');
				$result=$openshift_api->command($api,$apicall,$apisub==='false'?false:$apisub);
				$r.='<p>'.$openshift_api->responseToString().'</p>';
			}
			break;
		case 'dbdump':
			//$ts=$db->select('information_schema.tables','table_name','table_type=\'base table\' AND table_schema=\'gisportal\'');
			$ts=[]
				['table_name'=>'database_version'],
				['table_name'=>'afdelingen'],
				['table_name'=>'personen'],
				['table_name'=>'onderwerpen'],
				['table_name'=>'images'],
				['table_name'=>'versions'],
				['table_name'=>'geopackages'],
			];
			if ($ts) {
				foreach ($ts as $t) {
					$r.='Table: '.$t['table_name'].'<br>';
					$cs=$db->select('information_schema.columns','column_name,column_type,extra','table_schema=\'gisportal\' AND table_name=\''.$t['table_name'].'\'');
					if ($cs) {
						foreach ($cs as $c) {
							$r.='&nbsp;&nbsp;Field: '.$c['column_name'].' '.$c['column_type'].' '.$c['extra'].'<br>';
						}
					} else {
						$r.='&nbsp;&nbsp;Error: No fields found.<br>';
					}
				}
			} else {
				$r.='No tables found.';
			}
			break;
		default:
			$r.='<p style="margin-bottom: 20px;"><button onclick="document.location=\'admin.php?func=log\';">Logs bekijken</button>';
			$r.='<p style="margin-bottom: 20px;"><button onclick="document.location=\'admin.php?func=phpinfo\';">PHP info</button>';
			$r.='<p style="margin-bottom: 20px;"><button onclick="document.location=\'admin.php?func=apicall\';">API calls uitvoeren</button>';
			$r.='<p style="margin-bottom: 20px;"><button onclick="document.location=\'admin.php?func=dbdump\';">Database dump</button>';
			break;
	}
} else {
    $basicPage->fout('Autorisatie','Je hebt niet de juiste autorisatie.');
}

$basicPage->render('Admin functies',$r);
?>