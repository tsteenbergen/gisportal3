<?php  //Start the Session

require('./beheer/extention.php');

/************************************************************************************************************************************
Werking upload en indeling geo-mappen:
**************************************************************************************************************************************/

require('basicPage.php');

//$basicPage->writeLog('$_FILES='.var_export($_FILES,true));
//$basicPage->writeLog('$_POST='.var_export($_POST,true));
//$basicPage->writeLog('$_GET='.var_export($_GET,true));
//$basicPage->writeLog('$loggedIn='.var_export($loggedIn,true));

$r=array('error'=>true,'msg'=>'Unauthorised action');

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
			$path.='/geo-packages'; // upload directory
			if (!file_exists($path)) {mkdir($path);}
			$path.='/gpid-'.$id;
			if (!file_exists($path)) {mkdir($path);}
			$extention=new extention($id);
			$filename2=$extention->getRightFilename($filename); 
			$tmp = $_FILES['uploadfile']['tmp_name'];
			if ($filename2) {
//$basicPage->writeLog('Van '.$tmp.' naar '.$filename2);
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
					
				} else {
					if (file_exists($tmp)) {unlink(tmp);}
					$r['msg']='Error moving tmp file';
				}
			} else {
				if (file_exists($tmp)) {unlink(tmp);}
				$r['msg']='Deze file past niet bij de versie van dit image.';
			}
		} else {
			$r['msg']='Persistent storage /geo-mappen not found.';
		}
	} else {
		$r['msg']='Invalid file '.var_export($_FILES,true);
	}
}
//$basicPage->writeLog(var_export($r,true));
echo json_encode($r);
?>