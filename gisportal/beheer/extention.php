<?php
class extention {
	var $gpid;
	var $defs=[];
	var $files=[];
	
	function __construct($gpid,$checkFilePath=false) {
		global $db;
		
		$this->gpid=$gpid;
		$definition=$db->selectOne('geopackages AS a LEFT JOIN versions AS b on b.id=a.version','b.extensions','a.id='.$gpid);
		$definition=$definition['extensions'];
		$d=str_ireplace(chr(13).chr(10),chr(13),$definition);
		$d=str_ireplace(chr(10),chr(13),$d);
		$d=explode(chr(13),$d);
		foreach ($d as $de) {
			$de=trim($de);
			if ($de!='') {
				$opt=false; $krt=false; $file='';
				$pos=stripos($de,' ');
				if ($pos>=1) {
					$exts=trim(substr($de,0,$pos));
					$prms=trim(substr($de,$pos));
					if (strtoupper(substr($prms,0,1))=='O') {$opt=true; $prms=trim(substr($prms,1));}
					if (strtoupper(substr($prms,0,1))=='K') {$krt=true; $prms=trim(substr($prms,1));}
					if (strtoupper(substr($prms,0,1))=='O') {$opt=true; $prms=trim(substr($prms,1));} // zo maakt het niet uit of je OK of KO doet !!!
					if (substr($prms,0,1)=='=') {$file=trim(substr($prms,1));}
				} else {
					$exts=$de;
				}
				$this->defs[]=array(explode('/',$exts),$opt,($file==''?$krt:false),$file);
			}
		}
		if ($checkFilePath) { // Kijk ook of de files bestaan of niet
			global $basicPage;
			
			$checkFilePath=$basicPage->getConfig('geo-mappen').'/geo-packages/gpid-'.$gpid.'/';
			$t=0;
			$files=glob($checkFilePath.'*.*');
			foreach ($this->defs as $def) {
				// $def[0] bevat alle mogelijke extenties op deze regel
				// $def[1] bevat boolean; Is file optioneel
				// $def[2] bevat boolean; Moet je de kaartnaam gebruiken
				// $def[3] bevat vaste filenaam of ''
				$filenaam='';
				if ($def[2]) { // kaartnaam gebruiken
					$filenaam=$db->selectOne('geopackages','kaartnaam','id='.$gpid);
					$filenaam=$filenaam['kaartnaam'];
				} else {
					if ($def[3]!='') { // vaste filenaam gebruiken
						$filenaam=$def[3];
					} else { // oorspronkelijke filenaam gebruiken; Zoek naar bestaande file met de gewenste extentie
						foreach ($def[0] as $d) {
							$l=-1-strlen($d);
							for ($t1=0;$t1<count($files);$t1++) if (substr($files[$t1],$l)=='.'.$d) {
								$filenaam=substr($files[$t1],strlen($checkFilePath));
								$filenaam=substr($filenaam,0,strlen($filenaam)+$l);
								$t1=count($files);
							}
						}
					}
				}
				$this->files[]=$filenaam;
				$t++;
			}
		}
    }
	
	function tabel() {
		global $basicPage;
		
		$pad=$basicPage->getConfig('geo-mappen').'/geo-packages/gpid-'.$this->gpid.'/';
		$r='<table>';
		$t=0;
		foreach ($this->defs as $def) {
			$r.='<tr><td>'.implode('/',$def[0]).'</td><td>';
			$ext='';
			foreach ($def[0] as $d) {
				if (file_exists($pad.$this->files[$t].'.'.$d)) {$ext=$d;}
			}
			if ($def[1]) { // file is optioneel
				if ($ext=='' || $this->files[$t]=='') {
					$r.='File niet geupload (optioneel).';
				} else {
					$r.='\''.$this->files[$t].'.'.$ext.'\' geupload.';
				}
			} else { // file is verplicht
				if ($ext=='' || $this->files[$t]=='') {
					$r.='Fout: File niet geupload.';
				} else {
					$r.='\''.$this->files[$t].'.'.$ext.'\' geupload.';
				}
			}
			$r.='</td></tr>';
			$t++;
		}
		$r.='</table>';
		return $r;
	}
	
	function getRightFilename($filename) {
/*
		global $basicPage;
		$basicPage->writeLog('$filename='.$filename);

		$pos=strripos($filename,'.');
		if ($pos!==false) {
			$ext=strtolower(substr($filename,$pos+1));
			$filename=substr($filename,0,$pos);
			foreach ($this->defs as $def) {
$basicPage->writeLog('$def='.var_export($def,true));
				foreach ($def[0] as $d) {
$basicPage->writeLog('1='.$d);
					if ($d==$ext) {
						// $def[2] bevat boolean; Moet je de kaartnaam gebruiken
						// $def[3] bevat vaste filenaam of ''
						if ($def[2]) {
							global $db;
							$kaart=$db->selectOne('geopackages','kaartnaam','id='.$this->gpid);
							if ($kaart) {return $kaart['kaartnaam'].'.'.$ext;}
$basicPage->writeLog('1');
							return false;
						}
						if ($def[3]!='') {
$basicPage->writeLog('2');
							return $def[3].'.'.$ext;
						}
$basicPage->writeLog('3');
						return filename.'.'.$ext;
					}
				}
			}
		}
$basicPage->writeLog('4');
*/
		return 'hallo';
	}
}
?>