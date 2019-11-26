<?php  //Start the Session

require('basicPage.php');
require($basedir.'memory.php');
require($basedir.'beheer/extention.php');

$r=array('error'=>true,'msg'=>'Unauthorised action');

set_time_limit(500);

if ($loggedIn){
	if ($_FILES['uploadfile']) {
		$extradata=explode(',',$_POST['extradata']);
		$uploadtype=$extradata[0];
		$id=$extradata[1];
		$r['uploadtype']=$uploadtype;
		$r['id']=$id;
		$filename = $_FILES['uploadfile']['name'];
		$ext=$filename; $ext=substr($ext,strripos($ext,'.'));
		$path = $basicPage->getConfig('geo-mappen');
		if (file_exists($path)) {
			if ($_FILES['uploadfile']['size'] < $memory->maxUploadsize()) {
				$path.='/geo-packages'; // upload directory
				if (!file_exists($path)) {mkdir($path);}
				$path.='/gpid-'.$id;
				if (!file_exists($path)) {mkdir($path);}
				$extention=new extention($id);
				$filename2=$extention->getRightFilename($filename); 
				$tmp = $_FILES['uploadfile']['tmp_name'];
				if ($filename2) {
	//$basicPage->writeLog('Van '.$tmp.' naar '.$filename2);
					$extention->removeAllWithExt();
					if(move_uploaded_file($tmp,$path.'/'.$filename2)) {
						$r['error']=false;
						$r['msg']=' Uploaded file: '.$filename2;
						$r['filenaam']=$filename2;
						

						// opslaan bij geopackage
						global $db;
						$gfs=$db->selectOne('geopackages','brongeopackage','id='.$id);
						$gfs=explode(chr(13),$gfs['brongeopackage']);
						$found=false;
						for ($t=0;$t<count($gfs);$t++) {
							$f=explode('=',$gfs[$t]);
							if ($f[0]==$filename) {$gfs[$t]=$filename.'='.$filename2; $t=count($gfs); $found=true;}
						}
						if (!$found) {
							$gfs[]=$filename.'='.$filename2;
						}
						$db->update('geopackages',array('Qbrongeopackage'=>implode(chr(13),$gfs)),'id='.$id);
						$extention=new extention($id,true);
						$r['tabel']=$extention->tabel();
						
					} else {
						if (file_exists($tmp)) {unlink($tmp);}
						$r['msg']='Error moving tmp file';
					}
				} else {
					if (file_exists($tmp)) {unlink($tmp);}
					$r['msg']='Deze file past niet bij de versie van dit image.';
				}
			} else {
				$r['msg']='Er is onvoldoende ruimte op de persistant storage van het containerplatform.';
				//mail('geodata@rivm.nl','Fout: Onvoldoende Persistent storage op containerplatform',"Beste GIS-beheerders,\n\nEr heeft zojuist iemand geprobeerd een bestand te uploaden naar de persistent storage op het containerplatform. Dit is niet gelukt omdat er onvoldoende ruimte is.\nHet is van belang deze ruimtte ASAP uit te breiden.\n\nDeze email is automatisch verstuurd.");
			}
		} else {
			$r['msg']='Persistent storage '.$path.' not found.';
		}
	} else {
		$r['msg']='Invalid file '.var_export($_FILES,true);
	}
}
//$basicPage->writeLog(var_export($r,true));
echo json_encode($r);
?>