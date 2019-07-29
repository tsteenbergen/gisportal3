<?php  //Start the Session


/************************************************************************************************************************************
Werking upload en indeling geo-mappen:
1.	Bij de upload van een geo-package-file is niet altijd een geo-package-id bekend. Het bestand wordt daarom opgeslagen onder de naam: /geo-mappen/tmp-[user-id].gpkg of /geo-mappen/tmp-[user-id].sld
2.	Bij elk bezoek aan een pagina wordt na het aanroepen van de render-functie het tmp-bestand verwijderd, als deze bestaat. Zo wordt voorkomen dat er (te) veel tmp-files ontstaan.
3.	Bij het opslaan van een geo-package wordt (voor het renderen van de result-pagina) het eventueel geuploade bestand hernoemd en verplaatst naar: /geo-mappen/gpid-[geo-package-id]/source.gpkg of /geo-mappen/gpid-[geo-package-id]/sld.sld
4.	De upload-files kunnen van de volgende types zijn:
    a.	geo-package
    b.	sld
**************************************************************************************************************************************/

require('basicPage.php');

$basicPage->writeLog(var_export($_FILES,true));
$r=array('error'=>true,'msg'=>'Unauthorised action');
if ($loggedIn){
	if ($_FILES['uploadfile']) {
		$extradata=explode(',',$_POST['extradata']);
		$uploadtype=$extradata[0];
		$id=$extradata[1];
		$r['uploadtype']=$uploadtype;
		$r['id']=$id;
		$filename = $_FILES['uploadfile']['name'];
		$path = $basicPage->getConfig('geo-mappen');
		if (file_exists($path)) {
			$path.='/geo-packages'; // upload directory
			if (!file_exists($path)) {mkdir($path);}
			switch ($uploadtype) {
				case 'geo-package':
					$valid_extensions = array('sqlite','gpkg'); 
					$filename2 = $path.'/tmp-'.$_SESSION['user'].'.sqlite'; 
					break;
				case 'sld':
					$valid_extensions = array('qgs'); 
					$filename2 = $path.'/tmp-'.$_SESSION['user'].'.qgs'; 
					break;
			}
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