<?php  //Start the Session

require('./beheer/extention.php');

/************************************************************************************************************************************
Werking upload en indeling geo-mappen:
**************************************************************************************************************************************/

require('basicPage.php');

$basicPage->writeLog('$_FILES='.var_export($_FILES,true));
$basicPage->writeLog('$_POST='.var_export($_POST,true));
$basicPage->writeLog('$_GET='.var_export($_GET,true));
$basicPage->writeLog('$loggedIn='.var_export($loggedIn,true));
$r=array('error'=>true,'msg'=>'Unauthorised action');
if ($loggedIn){
	if ($_FILES['uploadfile']) {
		$extradata=explode(',',$_POST['extradata']);
		$uploadtype=$extradata[0];
		$id=$extradata[1];
		$fname=$basicPage->getConfig('geo-mappen').'/geo-packages/gpid-'.$id;
		$ext=new extention($id);
		if (!file_exists($fname)) {mkdir($fname);}
		$r['uploadtype']=$uploadtype;
		$r['id']=$id;
		$filename = $_FILES['uploadfile']['name'];
		$ext=$filename; $ext=substr($ext,strripos($ext,'.'));
		$path = $basicPage->getConfig('geo-mappen');
		if (file_exists($path)) {
			$path.='/geo-packages'; // upload directory
			if (!file_exists($path)) {mkdir($path);}
$filename2 = $filename; 
/*			switch ($uploadtype) {
				case 'geo-package':
					$valid_extensions = array('sqlite','gpkg'); 
					$filename2 = $path.'/tmp-'.$_SESSION['user'].$ext; 
					break;
				case 'sld':
					$valid_extensions = array('qgs','map'); 
					$filename2 = $path.'/tmp-'.$_SESSION['user'].$ext; 
					break;
			}*/
			$valid_extensions = array('sqlite','gpkg','map','qgs','qgz','png');
			if ($valid_extensions) {
				$tmp = $_FILES['uploadfile']['tmp_name'];
				$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
				if (in_array($ext, $valid_extensions)) {
					$basicPage->writeLog('Van '.$tmp.' naar '.$filename2);
					if(move_uploaded_file($tmp,$filename2)) {
						$r['error']=false;
						$r['msg']=' Uploaded file: '.$filename;
						$r['filenaam']=$filename;
					}
				} else {
					$r['msg']='Invalid file-extention';
				}
			} else {
					$r['msg']='Unknown uploadfile type: '.$uploadtype;
			}
		} else {
			$r['msg']='Persistent storage /geo-mappen not found.';
		}
	} else {
		$r['msg']='Invalid file '.var_export($_FILES,true);
	}
}
$basicPage->writeLog(var_export($r,true));
echo json_encode($r);
?>