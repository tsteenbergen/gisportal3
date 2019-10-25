<?php
require('../basicPage.php');
require('../openshift-api.php');
//require ('./beheer/extention.php');

$title='Beheer geopackage';
$r='';
if ($loggedIn){
	if (isset($_GET['id'])) {
		$func=$_POST['func'];
		$id=$_GET['id'];
		$velden='id,afdeling,onderwerp,naam,kaartnaam,soort,brongeopackage,indatalink,datalink,opmaak,wms,wfs,wcs,wmts,version';
		if ($id>=1) {
			$g=$db->selectOne('geopackages',$velden,'id='.$id.($is_admin?'':' AND afdeling='.$my_afd));
			$version=$db->selectOne('versions AS a LEFT JOIN images AS b ON b.id=a.image','b.image, a.version','a.id='.$g['version']);
		} else {
			$id=0;
			$version=$db->selectOne('versions AS a LEFT JOIN images AS b ON b.id=a.image','a.id','a.deflt=\'J\'  AND b.deflt=\'J\'');
			$g=array('id'=>0,'naam'=>'','afdeling'=>$my_afd,'onderwerp'=>0,'version'=>$version['id']);
			$title='Nieuw geopackage';
		}
//file_put_contents('qqq',file_get_contents('qqq').'<br>'.var_export($_POST,true));
		if ($func=='delete') {
			if ($g['id']>=1) {
				$db->delete('geopackages','id='.$g['id']);
				$fname=$basicPage->getConfig('geo-mappen').'/geo-packages/gpid-'.$g['id'].'/'.$g['brongeopackage'];
				if (file_exists($fname)) {unlink($fname);}
				$fname=$basicPage->getConfig('geo-mappen').'/geo-packages/gpid-'.$g['id'].'/source.gqs';
				if (file_exists($fname)) {unlink($fname);}
				$fname=$basicPage->getConfig('geo-mappen').'/geo-packages/gpid-'.$g['id'];
				if (file_exists($fname)) {rmdir($fname);}
				$openshift_api->deleteDeploymentConfig('../',$g['id']);
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
						'Qkaartnaam'=>$db->validateString($_POST['kaartnaam'],'kaartnaam',1,64,'Er is geen naam opgegeven','De naam is te lang (max 64 tekens).'),
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
					if (!($a['onderwerp']>=1)) {$db->foutMeldingen[]=['onderwerp','Er is geen onderwerop gekozen'];}
					//file_put_contents('qqq',file_get_contents('qqq').'<br>'.var_export($_POST,true));
					// velden die niet gespooft mogen worden
					$a['Qbrongeopackage']=$db->validateString($_POST['brongeopackage'],'brongeopackage',0,255,'Er is geen filenaam opgegeven','De filenaam is te lang (max 255 tekens).');
					$a['Qopmaak']=$db->validateString($_POST['opmaak'],'opmaak',0,255,'Er is geen filenaam opgegeven','De filenaam is te lang (max 255 tekens).');
					if (!$db->foutMeldingen) {
						$version=$db->selectOne('versions AS a LEFT JOIN images AS b ON b.id=a.image','b.image,a.version','a.id='.$a['version']);
						$theme=$db->selectOne('onderwerpen','afkorting','id='.$a['onderwerp']);
						if ($g['id']==0) {
							$g['id']=$db->insert('geopackages',$a);
							$openshift_api->createDeploymentConfig('../',$g['id'],$theme['afkorting'],$a['Qkaartnaam'],$version['image'],$version['version']);
						} else {
							$db->update('geopackages',$a,'id='.$g['id']);
							$openshift_api->updateDeploymentConfig('../',$g['id'],$a['Qkaartnaam'],$version['image'],$version['version']);
						}
						$fname=$basicPage->getConfig('geo-mappen').'/geo-packages/gpid-'.$g['id'];
						if (!file_exists($fname)) {mkdir($fname);}
						$fname=$basicPage->getConfig('geo-mappen').'/geo-packages/tmp-'.$_SESSION['user'].'.sqlite';
						if (file_exists) {rename($fname,$basicPage->getConfig('geo-mappen').'/geo-packages/gpid-'.$g['id'].'/'.$a['Qbrongeopackage']);}
						$fname=$basicPage->getConfig('geo-mappen').'/geo-packages/tmp-'.$_SESSION['user'].'.gpkg';
						if (file_exists) {rename($fname,$basicPage->getConfig('geo-mappen').'/geo-packages/gpid-'.$g['id'].'/'.$a['Qbrongeopackage']);}
						$fname=$basicPage->getConfig('geo-mappen').'/geo-packages/tmp-'.$_SESSION['user'].'.qgs';
						if (file_exists) {rename($fname,$basicPage->getConfig('geo-mappen').'/geo-packages/gpid-'.$g['id'].'/source.qgs');}
						$fname=$basicPage->getConfig('geo-mappen').'/geo-packages/tmp-'.$_SESSION['user'].'.map';
						if (file_exists) {rename($fname,$basicPage->getConfig('geo-mappen').'/geo-packages/gpid-'.$g['id'].'/source.map');}
						if ($func=='opslaan') {
							$basicPage->redirect('/geo/portal/geo-packages.php',false,'Opslaan','De geopackage is opgeslagen.');
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
						$g['brongeopackage']=$a['Qbrongeopackage'];
						$g['opmaak']=$a['Qopmaak'];
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
				$basicPage->add_js_ready('depententSelect(\'onderwerp\',\'afdeling\',onderwerpen,\''.$g['onderwerp'].'\','.($id==0 && $t>1?'\'\'':'false').');');
				
				$back=explode(chr(1),base64_decode($_GET['back']));
				if (count($back)==3) {$back='?a='.$back[0].'&ond='.$back[1].'&naam='.$back[2];} else {$back='';}
				$r.='<button onclick="location.href=\'/geo/portal/geo-packages.php'.$back.'\';" style="margin-bottom: 40px;">Terug</button>';
				
				// eerste div
				$tab1.='<div id="tabs-1" style="vertical-align: top;">';
				$a=$db->select('afdelingen','id,naam','id>=1','naam');
				$afds=[];
				if ($a) {foreach ($a as $b) {$afds[]=$b['id'].'='.htmlspecialchars($b['naam']);}}
				$tab1.='<form id="form" method="POST"><input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="func" id="func"><table style="margin-bottom: 20px;">';
				$basicPage->add_js_ready('autoForm(\'form\','.($g['id']==0?'true':'false').',\'#form-edit\',\'#form-delete\',\'#form-save,#opmaak-file-button,#helpindata,#helpdata\');');
//				if ($id>=1) {
					$tab1.='<tr><td colspan="2"><a class="small-button" id="form-save" onclick="$(\'#func\').val(\'opslaan\'); $(\'#form\').submit();" style="margin-right: 20px;">Opslaan</a><a id="form-edit" class="small-button">Bewerken</a><a id="form-delete" style="float: right;" class="small-button" onclick="areYouSure(\'Verwijderen\',\'Dit geopackage verwijderen?<br><br>NB: Dit kan niet ongedaan worden gemaakt.\',function () {$(\'[name=id]\').removeAttr(\'disabled\'); $(\'#func\').removeAttr(\'disabled\').val(\'delete\'); $(\'#form\').submit();});">Verwijderen</a></td></tr>';
					$tab1.='<tr><td colspan="2">&nbsp;</a></td></tr>';
//				}
				$t=time();
				$tab1.='<tr><td>Afdeling:</td><td>'.$basicPage->getSelect('afdeling',$g['afdeling'],$afds,!$is_admin).'</td></tr>';
				$tab1.='<tr><td>Onderwerp:</td><td><select name="onderwerp" id="onderwerp"></select></td></tr>';
				$tab1.='<tr><td>Naam:</td><td><input name="naam" value="'.htmlspecialchars($g['naam']).'" size="32"></td></tr>';
				$tab1.='<tr><td>Kaartaam (in URL):</td><td><input name="kaartnaam" value="'.htmlspecialchars($g['kaartnaam']).'" size="32"></td></tr>';
				$tab1.='<tr><td colspan="2">&nbsp;</a></td></tr>';
				if ($is_admin) {
					$tab1.='<tr><td colspan="2">&nbsp;</a></td></tr>';
					$version2a=array();
					$version2=$db->select('versions AS a LEFT JOIN images AS b ON b.id=a.image','a.id, b.image, a.version','a.id>=1','b.image, a.version');
					if ($version2) foreach ($version2 as $tmp) {$version2a[]=$tmp['id'].'='.$tmp['image'].' '.$tmp['version'];}
					$tab1.='<tr><td>Image:</td><td>'.$basicPage->getSelect('version',$g['version'],$version2a).'</td></tr>';
				} else {
					$tab1.='<tr><td colspan="2">&nbsp;<input type="hidden" value="'.$g['version'].'" name="version"></a></td></tr>';
				}
				$tab1.='<tr><td>Soort:</td><td>'.$basicPage->getSelect('soort',$g['soort'],array('Raster','Vector')).'</td></tr>';
				$tab1.='<tr><td colspan="2">&nbsp;</a></td></tr>';
				$tab1.='<tr><td>Upload een file:</td><td><span id="brongeopackage1" style="margin-right: 20px;"></span><input type="hidden" id="brongeopackage" name="brongeopackage" value=""><a class="small-button" style="float: right;" uploadFile="geo-package,'.$g['id'].'">Upload file</a></td></tr>';
//				$tab1.='<tr><td>qgs file:</td><td><span id="opmaak1" style="margin-right: 20px;">'.htmlspecialchars($g['opmaak']).'</span><input type="hidden" id="opmaak-file" name="opmaak" value="'.$g['opmaak'].'"><a id="opmaak-file-button" class="small-button" style="float: right;" uploadFile="sld,'.$g['id'].'">Upload file</a></td></tr>';
//				$ext=new extention($g['id'],true);
//				$tab1.='<tr><td>Files:</td><td>'.$ext->tabel().'</td></tr>';
				$tab1.='<tr><td colspan="2">&nbsp;</a></td></tr>';
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
								$title = $node->getElementsByTagName('title')->item(0)->nodeValue;
								$ar[]=$title.chr(0).$uuid;
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
								$title = $node->getElementsByTagName('title')->item(0)->nodeValue;
								$ar[]=$title.chr(0).$uuid;
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
				$basicPage->add_js_inline($js);
                $tab1.='<tr><td>Metadata indata.rivm.nl:</td><td><span style="display: inline-block; width: 160px;">Verfijn op zoekterm:</span><input style="width: calc(100% - 230px);" onkeyup="setMetadatalink(false);" id="zoekindata"><a id="helpindata" class="small-button" onclick="metadatalinkhelp(false);" style="float: right;">Help</a></td></tr>';
                $tab1.='<tr><td></td><td><select name="indatalink" style="width: 100%;"><option value="'.$g['indatalink'].'">'.$current_indatalink.'</option></select></td></tr>';
                $tab1.='<tr><td>Metadata data.rivm.nl:</td><td><span style="display: inline-block; width: 160px;">Verfijn op zoekterm:</span><input style="width: calc(100% - 230px);" onkeyup="setMetadatalink(true);" id="zoekdata"><a id="helpdata" class="small-button" onclick="metadatalinkhelp(false);" style="float: right;">Help</a></td></tr>';
                $tab1.='<tr><td></td><td><select name="datalink" style="width: 100%;"><option value="'.$g['datalink'].'">'.$current_datalink.'</option></select></td></tr>';
				$tab1.='<tr><td colspan="2">&nbsp;</a></td></tr>';
                $tab1.='<tr><td>Services:</td><td>'.$basicPage->checkbox('wms',$g['wms']=='J','WMS').'<br>'.$basicPage->checkbox('wfs',$g['wfs']=='J','WFS').'<br>'.$basicPage->checkbox('wcs',$g['wcs']=='J','WCS').'<br>'.$basicPage->checkbox('wmts',$g['wmts']=='J','WMTS').'</td></tr>';
				$tab1.='</table></form></div>';
				
				if ($id>=1) {
					// tweede div
					$tab2.='<div id="tabs-2" style="vertical-align: top;">';
					$tab2.='<table>';
					$tab2.='</tr><td>Sqlite file:</td><td>'.htmlspecialchars($g['brongeopackage']).'</td>';
					if ($g['brongeopackage']=='') {
						$tab2.='<td class="waarde-oranje">- niet gespecificeerd -</td>';
					} else {
						$tab2.=(file_exists($basicPage->getConfig('geo-mappen').'/geo-packages/gpid-'.$g['id'].'/'.$g['brongeopackage'])?'<td class="waarde-groen">Geen bijzonderheden</td>':'<td class="waarde-rood">Bestand niet gevonden</td>');
					}
					$tab2.='</tr>';
					$tab2.='</tr><td>Qgs file:</td><td>'.htmlspecialchars($g['opmaak']).'</td>';
					if ($g['opmaak']=='') {
						$tab2.='<td class="waarde-oranje">- niet gespecificeerd -</td>';
					} else {
						$tab2.=(file_exists($basicPage->getConfig('geo-mappen').'/geo-packages/gpid-'.$g['id'].'/source.qgs')?'<td class="waarde-groen">Geen bijzonderheden</td>':'<td class="waarde-rood">Bestand niet gevonden</td>');
					}
					$tab2.='</tr>';
					$tab2.='<tr><td>&nbsp;</td></tr>';
					$tab2.='<tr><td>Metadata indata.rivm.nl:</td><td><a href="http://indata.rivm.nl/geonetwork/srv/dut/catalog.search#/metadata/'.$g['indatalink'].'" target="from_gisportal">'.$current_indatalink.'</a></td>';
					$meta_indata=@file_get_contents('http://indata.rivm.nl/geonetwork/srv/dut/catalog.search#/metadata/'.$g['indatalink']);
					if ($g['indatalink']=='') {
						$tab2.='<td class="waarde-oranje">- niet gespecificeerd -</td>';
					} else {
						$csw=@file_get_contents('http://indata.rivm.nl/geonetwork/srv/dut/csw?SERVICE=CSW&version=2.0.2&request=GetRecordById&elementSetName=full&outputSchema=http://www.isotc211.org/2005/gmd&ID='.$g['indatalink']);
						if (stripos($csw,$g['indatalink'])>=1) {
							$tab2.='<td class="waarde-groen">Geen bijzonderheden</td>';
						} else {
							$tab2.='<td class="waarde-rood">De link naar indata.rivm.nl bestaat niet (meer)</td>';
						}
					}
					if ($g['indatalink']!='') {
						$tab2.='<tr><td></td><td id="mdata"></td>';
						$basicPage->add_js_ready('add_mdata(\'mdata\',\'https://data.rivm.nl/geonetwork/srv/dut/xml.metadata.get?uuid='.$g['indatalink'].'\');');
					}
					$tab2.='<tr><td>Metadata data.rivm.nl:</td><td><a href="http://data.rivm.nl/geonetwork/srv/dut/catalog.search#/metadata/'.$g['datalink'].'" target="from_gisportal">'.$current_datalink.'</a></td>';
					$meta_data=@file_get_contents('http://data.rivm.nl/geonetwork/srv/dut/catalog.search#/metadata/'.$g['datalink']);
					if ($g['datalink']=='') {
						$tab2.='<td class="waarde-oranje">- niet gespecificeerd -</td>';
					} else {
						$csw=@file_get_contents('http://data.rivm.nl/geonetwork/srv/dut/csw?SERVICE=CSW&version=2.0.2&request=GetRecordById&elementSetName=full&outputSchema=http://www.isotc211.org/2005/gmd&ID='.$g['datalink']);
						if (stripos($csw,$g['datalink'])>=1) {
							$tab2.='<td class="waarde-groen">Geen bijzonderheden</td>';
						} else {
							$tab2.='<td class="waarde-rood">De link naar data.rivm.nl bestaat niet (meer)</td>';
						}
					}
					$tab2.='</table>';
					
					$files=glob($basicPage->getConfig('geo-mappen').'/geo-packages/gpid-'.$g['id'].'/*.*');
					if ($files) {
						foreach ($files as $file) {
							$tab2.=$file.'<br>';
						}
					}
					
					$tab2.='</div>';
					
				} else {
					$tab2=false;
				}
				
				$r.='<div id="tabs">';
				$r.='<ul><li><a href="#tabs-1">Algemene gegevens</a></li>';
				if ($tab2) {$r.='    <li><a href="#tabs-2">Controle</a></li>';}
				$r.='</ul>';
				$r.=$tab1;
				if ($tab2) {$r.=$tab2;}
				$r.='</div>';
				$basicPage->add_js_ready('$( "#tabs" ).tabs({heightStyle: \'auto\'});');
				
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