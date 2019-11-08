<?php
require('../basicPage.php');
require('../openshift-api.php');
require ('./extention.php');
require ('../memory.php');

$title='Beheer geopackage';
$r='';
if ($loggedIn){
	if (isset($_GET['id'])) {
		$func=$_POST['func'];
		$id=$_GET['id'];
		$velden='id,afdeling,onderwerp,naam,kaartnaam,soort,brongeopackage,indatalink,datalink,wms,wfs,wcs,wmts,version';
		if ($id>=1) {
			$g=$db->selectOne('geopackages',$velden,'id='.$id.($is_admin?'':' AND afdeling='.$my_afd));
			$version=$db->selectOne('versions AS a LEFT JOIN images AS b ON b.id=a.image','b.image, a.version','a.id='.$g['version']);
		} else {
			$id=0;
			$version=$db->selectOne('versions AS a LEFT JOIN images AS b ON b.id=a.image','a.id','a.deflt=\'J\'  AND b.deflt=\'J\'');
			$g=array('id'=>0,'naam'=>'','afdeling'=>$my_afd,'onderwerp'=>0,'version'=>$version['id']);
			$title='Nieuw geopackage';
		}
		if ($func=='delete') {
			$basicPage->writeLog('Delete geopackage id='.$g['id']);
			if ($g['id']>=1) {
				$db->delete('geopackages','id='.$g['id']);
				$delfs=glob($basicPage->getConfig('geo-mappen').'/geo-packages/gpid-'.$g['id'].'/*.*');
				if ($delfs) foreach ($delfs as $delf) {
					unlink($delf);
				}
				$fname=$basicPage->getConfig('geo-mappen').'/geo-packages/gpid-'.$g['id'];
				if (file_exists($fname)) {rmdir($fname);}
				$openshift_api->deleteDeploymentConfig($g['id']);
				$basicPage->redirect('/geo/portal/geo-packages.php',true,'Verwijderen','Geopackage is verwijderd.');
			} else {
				$basicPage->fout('Internal error','Geopackage niet gevonden.');
			}
		} else {
			if ($g) {
				if ($func=='opslaan' || $func=='cache-legen') {
					if ($func=='cache-legen') {
						if (file_exists('indata.data')) {unlink('indata.data');}
						if (file_exists('data.data')) {unlink('data.data');}
					}
					$a=array(
						'Qnaam'=>$db->validateString($_POST['naam'],'naam',1,64,'Er is geen naam opgegeven','De naam is te lang (max 64 tekens).'),
						'Qkaartnaam'=>$db->validateString(strtolower($_POST['kaartnaam']),'kaartnaam',1,64,'Er is geen naam opgegeven','De naam is te lang (max 64 tekens).'),
						'afdeling'=>($is_admin?$_POST['afdeling']:$my_afd),
						'onderwerp'=>$_POST['onderwerp'],
						'Qsoort'=>$_POST['soort'],
						'Qindatalink'=>$db->validateString($_POST['indatalink'],'indatalink',0,255,'Er is geen link opgegeven','De link is te lang (max 255 tekens).'),
						'Qdatalink'=>$db->validateString($_POST['datalink'],'datalink',0,255,'Er is geen link opgegeven','De link is te lang (max 255 tekens).'),
						'Qwms'=>$db->validateCheckbox($_POST['wms']),
						'Qwfs'=>$db->validateCheckbox($_POST['wfs']),
						'Qwcs'=>$db->validateCheckbox($_POST['wcs']),
						'Qwmts'=>$db->validateCheckbox($_POST['wmts']),
						'version'=>$_POST['version'],
					);
					if ($a['Qkaartnaam']!='') {
						for ($t=0;$t<strlen($a['Qkaartnaam']);$t++) {
							$c=substr($a['Qkaartnaam'],$t,1);
							if (! (  ($c>='a' && $c<='z') || ($c>='A' && $c<='Z') || ($c>='0' && $c<='9') || $c=='-' || $c=='_'  )  ) {
								$db->foutMeldingen[]=['kaartnaam','De kaartnaam mag alleen letters, cijfers, - of _ bevatten'];
								$t=strlen($a['Qkaartnaam']);
							}
						}
					}
					if (!($a['onderwerp']>=1)) {$db->foutMeldingen[]=['onderwerp','Er is geen onderwerop gekozen'];}
					// velden die niet gespooft mogen worden
					if (!$db->foutMeldingen) {
						$ex=$db->select('geopackages','id','onderwerp='.$a['onderwerp'].' AND kaartnaam=\''.$a['Qkaartnaam'].'\' AND id!='.$g['id']);
						if ($ex) {
							$db->foutMeldingen[]=['kaartnaam','De combinatie onderwerp-kaartnaam is niet uniek'];
						}
					}
					if (!$db->foutMeldingen) {
						$version=$db->selectOne('versions AS a LEFT JOIN images AS b ON b.id=a.image','b.image,a.version','a.id='.$a['version']);
						$theme=$db->selectOne('onderwerpen','afkorting','id='.$a['onderwerp']);
						$variables=[
							'map-theme'=>$theme['afkorting'],
							'map-name'=>$a['Qkaartnaam'],
							'image-name'=>$version['image'],
							'image-version'=>$version['version'],
							'limit-cpu'=>'800m',
							'limit-memory'=>'1200Mi',
							'request-cpu'=>'80m',
							'request-memory'=>'120Mi',
						];
						$toFileTab=false;
						if ($g['id']==0) {
							$a['Qbrongeopackage']=''; // Dit is nodig omdat het veld in de db verplicht is!!!! Dit moet ooit nog weg!!!!
							$a['Qopmaak']='';         // Dit is nodig omdat het veld in de db verplicht is!!!! Dit moet ooit nog weg!!!!
							$g['id']=$db->insert('geopackages',$a);
							$openshift_api->createDeploymentConfig($g['id'],$variables);
							$toFileTab=true;
						} else {
							if ($g['version']!=$a['version']) { // wijziging image? dan alles opneiuw aanmaken
								$openshift_api->deleteDeploymentConfig($g['id']);
							} else { // wijziging thema? dan route opnieuw aanmaken
								if ($g['kaartnaam']!=$a['Qkaartnaam'] || $g['onderwerp']!=$a['onderwerp']) {
									$openshift_api->deleteDeploymentConfig($g['id'],['route']);
								}
							}
							$db->update('geopackages',$a,'id='.$g['id']);
							if ($g['version']!=$a['version']) { // wijziging image? dan alles opneiuw aanmaken
								$openshift_api->createDeploymentConfig($g['id'],$variables);
							} else { // wijziging thema? dan route opnieuw aanmaken
								if ($g['kaartnaam']!=$a['Qkaartnaam'] || $g['onderwerp']!=$a['onderwerp']) {
									$openshift_api->createDeploymentConfig($g['id'],$variables,['route']);
								}
							}
						}
						if ($func=='opslaan') {
							if ($toFileTab) {
								$basicPage->redirect('/geo/portal/beheer/geo-package.php?id='.$g['id'].'&tab=file',false,'Opslaan','De geopackage is opgeslagen.');
							} else {
								$basicPage->redirect('/geo/portal/geo-packages.php',false,'Opslaan','De geopackage is opgeslagen.');
							}
						} else {
							$basicPage->redirect('/geo/portal/beheer/geo-package.php?id='.$g['id'],false,'Opslaan','De geopackage is opgeslagen.');
						}
					} else {
						$basicPage->add_js_inline('var foutmeldingen='.json_encode($db->foutMeldingen).';');
						// zorg dat de POST waarden weer worden getoond
						$g['naam']=$a['Qnaam'];
						$g['kaartnaam']=$a['Qkaartnaam'];
						$g['afdeling']=$a['afdeling'];
						$g['onderwerp']=$a['onderwerp'];
						$g['soort']=$a['Qsoort'];
						$g['indatalink']=$a['Qindatalink'];
						$g['datalink']=$a['Qdatalink'];
						$g['wms']=$a['Qwms'];
						$g['wfs']=$a['Qwfs'];
						$g['wcs']=$a['Qwcs'];
						$g['wmts']=$a['Qwmts'];
						$g['version']=$a['version'];
					}
				}
				$a=$db->select('onderwerpen','id,afdeling,naam','id>=1');
				$js='var onderwerpen=[';
				if ($a) {$t=0; foreach ($a as $b) {$js.=($t==0?'':',').'['.$b['id'].','.$b['afdeling'].',\''.htmlspecialchars($b['naam']).'\']'; $t++;}}
				$basicPage->add_js_inline($js.'];');
				$basicPage->add_js_ready('depententSelect(\'onderwerp\',\'afdeling\',onderwerpen,\''.$g['onderwerp'].'\','.($id==0 && $t>1?'\'\'':'false').',\'\');');
				
				$back=explode(chr(1),base64_decode($_GET['back']));
				if (count($back)==3) {$back='?a='.$back[0].'&ond='.$back[1].'&naam='.$back[2];} else {$back='';}
				$r.='<button onclick="location.href=\'/geo/portal/geo-packages.php'.$back.'\';" style="margin-bottom: 40px;">Terug</button>';
				
				// eerste div
				$tab1.='<div id="tabs-1" style="vertical-align: top;">';
				$a=$db->select('afdelingen','id,naam','id>=1','naam');
				$afds=[];
				if ($a) {foreach ($a as $b) {$afds[]=$b['id'].'='.htmlspecialchars($b['naam']);}}
				$tab1.='<form id="form" method="POST"><input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="func" id="func"><table style="margin-bottom: 20px;">';
				$basicPage->add_js_ready('autoForm(\'form\','.($g['id']==0?'true':'false').',\'#form-edit\',\'#form-delete,#uploadknop\',\'#form-save,#helpindata,#helpdata\');');
				$tab1.='<tr><td colspan="2"><a class="small-button" id="form-save" onclick="$(\'#func\').val(\'opslaan\'); $(\'#form\').submit();" style="margin-right: 20px;">Opslaan</a><a id="form-edit" class="small-button">Bewerken</a><a id="form-delete" style="float: right;" class="small-button" onclick="areYouSure(\'Verwijderen\',\'Dit geopackage verwijderen?<br><br>NB: Dit kan niet ongedaan worden gemaakt.\',function () {$(\'[name=id]\').removeAttr(\'disabled\'); $(\'#func\').removeAttr(\'disabled\').val(\'delete\'); console.log(1234); $(\'#form\').submit(); console.log(12345);});">Verwijderen</a></td></tr>';
				$tab1.='<tr><td colspan="2">&nbsp;</a></td></tr>';
				$t=time();
				$tab1.='<tr><td>Afdeling:</td><td>'.$basicPage->getSelect('afdeling',$g['afdeling'],$afds,!$is_admin).'</td></tr>';
				$tab1.='<tr><td>Onderwerp:</td><td><select name="onderwerp" id="onderwerp" onchange="regel_kaart_url();"></select></td></tr>';
				$tab1.='<tr><td>Naam:</td><td><input name="naam" value="'.htmlspecialchars($g['naam']).'" size="32"></td></tr>';
				$tab1.='<tr><td>Kaartaam (in URL):</td><td><input name="kaartnaam" value="'.htmlspecialchars($g['kaartnaam']).'" size="32" onchange="regel_kaart_url();"></td></tr>';
				$tab1.='<tr><td colspan="2">&nbsp;</td></tr>';
				$tab1.='<tr><td>URL:</td><td><span id="kaart-url" style="margin-right: 20px;"></span><a id="kaart-url-knop" href="#" class="small-button" onclick="copyKaart()">Copi&euml;er URL</a></td></tr>';
				$basicPage->add_js_ready('regel_kaart_url();');
				if ($is_admin) {
					$tab1.='<tr><td colspan="2">&nbsp;</a></td></tr>';
					$version2a=array();
					$version2=$db->select('versions AS a LEFT JOIN images AS b ON b.id=a.image','a.id, b.image, a.version','a.id>=1','b.image, a.version');
					if ($version2) foreach ($version2 as $tmp) {$version2a[]=$tmp['id'].'='.$tmp['image'].' '.$tmp['version'];}
					$tab1.='<tr><td>Image:</td><td>'.$basicPage->getSelect('version',$g['version'],$version2a).'</td></tr>';
				} else {
					$tab1.='<tr><td colspan="2">&nbsp;<input type="hidden" value="'.$g['version'].'" name="version"></a></td></tr>';
				}
/*
				$tab1.='<tr><td>Soort:</td><td>'.$basicPage->getSelect('soort',$g['soort'],array('Raster','Vector')).'</td></tr>';
				$tab1.='<tr><td colspan="2">&nbsp;</td></tr>';
*/			
/*				
				if (file_exists('indata.data')) {
					if (time()-filemtime('indata.data')>60*60) {unlink('indata.data');} // gooi weg als ouder dan een uur
				}
				if (file_exists('data.data')) {
					if (time()-filemtime('data.data')>60*60) {unlink('data.data');} // gooi weg als ouder dan een uur
				}
				if (!file_exists('indata.data')) {
					try {
						$csw=@file_get_contents('http://indata.rivm.nl/geonetwork/srv/dut/csw?SERVICE=CSW&version=2.0.2&request=GetRecords&outputSchema=http://www.opengis.net/cat/csw/2.0.2&typeNames=csw:Record&resultType=results&maxRecords=5000');
						if ($csw!='') {
							$csw=str_replace("\r\n","\r",$csw);
							$csw=str_replace("\n","\r",$csw);
							$csw=str_replace("\r",' ',$csw);
							$rss = new DOMDocument();
							$rss->loadXML($csw);

							$nodes = $rss->getElementsByTagName('SummaryRecord');
							$ar=array();
							foreach ($nodes as $node) {
								$uuid = $node->getElementsByTagName('identifier')->item(0)->nodeValue;
								$title1 = $node->getElementsByTagName('title')->item(0)->nodeValue;
								$ar[]=$title1.chr(0).$uuid;
							}
							sort($ar);
							$file=fopen('indata.data','w');
							foreach ($ar as $a) {
								$a=explode(chr(0),$a);
								fwrite($file,$a[1].','.$a[0]."\n");
							}
							fclose($file);
						}
					} catch (Exception $e) {
					}
				}
				if (!file_exists('data.data')) {
					try {
						$csw=@file_get_contents('http://data.rivm.nl/geonetwork/srv/dut/csw?SERVICE=CSW&version=2.0.2&request=GetRecords&outputSchema=http://www.opengis.net/cat/csw/2.0.2&typeNames=csw:Record&resultType=results&maxRecords=5000');
						if ($csw!='') {
							$csw=str_replace("\r\n","\r",$csw);
							$csw=str_replace("\n","\r",$csw);
							$csw=str_replace("\r",' ',$csw);
							$rss = new DOMDocument();
							$rss->loadXML($csw);

							$nodes = $rss->getElementsByTagName('SummaryRecord');
							$ar=array();
							foreach ($nodes as $node) {
								$uuid = $node->getElementsByTagName('identifier')->item(0)->nodeValue;
								$title1 = $node->getElementsByTagName('title')->item(0)->nodeValue;
								$ar[]=$title1.chr(0).$uuid;
							}
							sort($ar);
							$file=fopen('data.data','w');
							foreach ($ar as $a) {
								$a=explode(chr(0),$a);
								fwrite($file,$a[1].','.$a[0]."\n");
							}
							fclose($file);
						}
					} catch (Exception  $e) {
					}
				}
				if (file_exists('indata.data')) {$csw=file_get_contents('indata.data');} else {$csw=false;}
				$js='var indatarecs=[';
				$current_indatalink='';
				if ($csw) {
				    $csw=explode("\n",$csw);
				    $t=0;
				    foreach ($csw as $c) {
				        $pos=stripos($c,',');
                        $js.=($t==0?'':',').'[\''.substr($c,0,$pos).'\',\''.htmlspecialchars(addslashes(substr($c,$pos+1))).'\']';
						if (substr($c,0,$pos)==$g['indatalink']) {$current_indatalink=htmlspecialchars(addslashes(substr($c,$pos+1)));}
                        $t++;
                    }
                } else {
                    $basicPage->fout('Inlezen metadata records', 'De metadata records kunnen niet van indata.rivm.nl worden ingelezen.');
                }
				$js.='];';
				if (file_exists('data.data')) {$csw=file_get_contents('data.data');} else {$csw=false;}
				$js.='var datarecs=[';
				$current_datalink='';
				if ($csw) {
				    $csw=explode("\n",$csw);
				    $t=0;
				    foreach ($csw as $c) {
				        $pos=stripos($c,',');
                        $js.=($t==0?'':',').'[\''.substr($c,0,$pos).'\',\''.htmlspecialchars(addslashes(substr($c,$pos+1))).'\']';
						if (substr($c,0,$pos)==$g['datalink']) {$current_datalink=htmlspecialchars(addslashes(substr($c,$pos+1)));}
                        $t++;
                    }
                } else {
                    $basicPage->fout('Inlezen metadata records', 'De metadata records kunnen niet van data.rivm.nl worden ingelezen.');
                }
				$js.='];';
*/
$js='var indatarecs=[];var datarecs=[];';
				$basicPage->add_js_inline($js);
/*
                $tab1.='<tr><td>Metadata indata.rivm.nl:</td><td><span style="display: inline-block; width: 160px;">Verfijn op zoekterm:</span><input style="width: calc(100% - 230px);" onkeyup="setMetadatalink(false);" id="zoekindata"><a id="helpindata" class="small-button" onclick="metadatalinkhelp(false);" style="float: right;">Help</a></td></tr>';
                $tab1.='<tr><td></td><td><select name="indatalink" style="width: 100%;"><option value="'.$g['indatalink'].'">'.$current_indatalink.'</option></select></td></tr>';
				if ($id>=1) {
					$tab1.='<tr><td></td><td><a href="http://indata.rivm.nl/geonetwork/srv/dut/catalog.search#/metadata/'.$g['indatalink'].'" target="from_gisportal">'.$current_indatalink.'</a></td>';
					$meta_indata=@file_get_contents('http://indata.rivm.nl/geonetwork/srv/dut/catalog.search#/metadata/'.$g['indatalink']);
					if ($g['indatalink']=='') {
						$tab1.='<td class="waarde-oranje">- niet gespecificeerd -</td>';
					} else {
						$csw=@file_get_contents('http://indata.rivm.nl/geonetwork/srv/dut/csw?SERVICE=CSW&version=2.0.2&request=GetRecordById&elementSetName=full&outputSchema=http://www.isotc211.org/2005/gmd&ID='.$g['indatalink']);
						if (stripos($csw,$g['indatalink'])>=1) {
							$tab1.='<td class="waarde-groen">Geen bijzonderheden</td>';
						} else {
							$tab1.='<td class="waarde-rood">De link naar indata.rivm.nl bestaat niet (meer)</td>';
						}
					}
					if ($g['indatalink']!='') {
						$tab1.='<tr><td></td><td id="mdata"></td>';
						$basicPage->add_js_ready('add_mdata(\'mdata\',\'https://data.rivm.nl/geonetwork/srv/dut/xml.metadata.get?uuid='.$g['indatalink'].'\');');
					}
				}
				
                $tab1.='<tr><td>Metadata data.rivm.nl:</td><td><span style="display: inline-block; width: 160px;">Verfijn op zoekterm:</span><input style="width: calc(100% - 230px);" onkeyup="setMetadatalink(true);" id="zoekdata"><a id="helpdata" class="small-button" onclick="metadatalinkhelp(false);" style="float: right;">Help</a></td></tr>';
                $tab1.='<tr><td></td><td><select name="datalink" style="width: 100%;"><option value="'.$g['datalink'].'">'.$current_datalink.'</option></select></td></tr>';
				if ($id>=1) {
					$tab1.='<tr><td></td><td><a href="http://data.rivm.nl/geonetwork/srv/dut/catalog.search#/metadata/'.$g['datalink'].'" target="from_gisportal">'.$current_datalink.'</a></td>';
					$meta_data=@file_get_contents('http://data.rivm.nl/geonetwork/srv/dut/catalog.search#/metadata/'.$g['datalink']);
					if ($g['datalink']=='') {
						$tab1.='<td class="waarde-oranje">- niet gespecificeerd -</td>';
					} else {
						$csw=@file_get_contents('http://data.rivm.nl/geonetwork/srv/dut/csw?SERVICE=CSW&version=2.0.2&request=GetRecordById&elementSetName=full&outputSchema=http://www.isotc211.org/2005/gmd&ID='.$g['datalink']);
						if (stripos($csw,$g['datalink'])>=1) {
							$tab1.='<td class="waarde-groen">Geen bijzonderheden</td>';
						} else {
							$tab1.='<td class="waarde-rood">De link naar data.rivm.nl bestaat niet (meer)</td>';
						}
					}
				}
				$tab1.='<tr><td colspan="2">&nbsp;</a></td></tr>';
				*/
				/*
                $tab1.='<tr><td>Services:</td><td>'.$basicPage->checkbox('wms',$g['wms']=='J','WMS').'<br>'.$basicPage->checkbox('wfs',$g['wfs']=='J','WFS').'<br>'.$basicPage->checkbox('wcs',$g['wcs']=='J','WCS').'<br>'.$basicPage->checkbox('wmts',$g['wmts']=='J','WMTS').'</td></tr>';
				*/
				$tab1.='</table></form></div>';
				
				if ($id>=1) {
					// tweede div
					$tab2.='<div id="tabs-2" style="vertical-align: top;">';
					$tab2.='<table>';
					if ($memory->uploadAllowed()) {
						$tab2.='<tr><td colspan="2"><button style="margin-bottom: 20px;;" id="uploadknop" uploadFile="geo-package,'.$g['id'].'">Upload file</button></td></tr>';
					} else {
						$tab2.='<tr><td colspan="2"><div class="fout">Fout: Op dit moment is er onvoldoende opslagcapaciteit. Daarom kunnen er geen files worden geupload. Waarschuw de beheersders: <a href="mailto:geodata@rivm.nl">geodata@rivm.nl</a></div></td></tr>';
					}
					$tab2.='<tr><td>&nbsp;</td></tr>';
					$ext=new extention($g['id'],true);
					$tab2.='<tr><td>Benodigde files:</td><td id="filetabel">'.$ext->tabel().'</td></tr>';
					$tab2.='</table>';
					
					$pad=$basicPage->getConfig('geo-mappen').'/geo-packages/gpid-'.$g['id'].'/';
					$files=glob($pad.'*.*');
					if ($files) {
						$tab2.='<br><br><table class="colored">';
						$tab2.='<tr><th>File</th><th>Modification</th><th>Changed</th><th>Accessed</th><th style="text-align: right;">Size</th></tr>';
						foreach ($files as $file) {
							$tab2.='<tr><td>'.substr($file,strlen($pad)).'</td><td>'.date('d-m-Y H:i:s',filemtime($file)).'</td><td>'.date('d-m-Y H:i:s',filectime($file)).'</td><td>'.date('d-m-Y H:i:s',fileatime($file)).'</td><td style="text-align: right;">'.number_format(filesize($file)/1000000,1,',','.').' Mb</td></tr>';
						}
						$tab2.='</table>';
					}
					
					$tab2.='</div>';
					
				} else {
					$tab2=false;
				}
				
				$r.='<div id="tabs">';
				$r.='<ul><li><a href="#tabs-1">Algemene gegevens</a></li>';
				if ($tab2) {$r.='    <li><a href="#tabs-2">Files</a></li>';}
				$r.='</ul>';
				$r.=$tab1;
				if ($tab2) {$r.=$tab2;}
				$r.='</div>';
				$active=$_GET['tab']=='file'?1:0;
				$basicPage->add_js_ready('$( "#tabs" ).tabs({heightStyle: \'auto\',active: '.$active.'});');
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