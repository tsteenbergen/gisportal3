<?php
class extention {
	var $gpid;
	var $defs=[];
	var $files=[];
	var $remove_exts=[];
	
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
				$pos=stripos($de,':');
				if ($pos>=1) {
					$label=trim(substr($de,0,$pos+1));
					$de=trim(substr($de,$pos+1));
				} else {
					$label='';
				}
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
				$this->defs[]=array(explode('/',$exts),$opt,($file==''?$krt:false),$file,$label);
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
		global $db;
		
		$pad=$basicPage->getConfig('geo-mappen').'/geo-packages/gpid-'.$this->gpid.'/';
		$filenamen=explode(chr(13),$db->selectOne('geopackages','brongeopackage','id='.$this->gpid));
		$orgfs=[];
		foreach ($filenamen as $filenaam) {
			$f=explode('=',$filenaam);
			if (count($f)==2) {$orgfs[$f[0]]=$f[1];}
		}
		$r='<table>';
		$t=0;
		foreach ($this->defs as $def) {
			$r.='<tr><td>'.$def[4].'</td><td>'.implode('/',$def[0]).'</td><td>';
			$ext='';
			$cfile=$this->files[$t];
			foreach ($def[0] as $d) {
				if (file_exists($pad.$cfile.'.'.$d)) {
					$ext=$d;
					$op=date('d-m-Y H:i:s',filemtime($pad.$cfile.'.'.$d));
				}
			}
			if (isset($orgfs[$cfile.'.'.$ext])) {$ofile=$orgfs[$cfile.'.'.$ext];} else {$ofile=$cfile.'.'.$ext;}
			if ($def[1]) { // file is optioneel
				if ($ext=='' || $cfile=='') {
					$r.='-- optioneel --';
				} else {
					$r.=$ofile.'</td><td>'.$op;
				}
			} else { // file is verplicht
				if ($ext=='' || $cfile=='') {
					$r.='Fout: File (nog) niet geupload.';
				} else {
					$r.=$ofile.'</td><td>'.$op;
				}
			}
			$r.='</td></tr>';
			$t++;
		}
		$r.='</table>';
		return $r;
	}
	
	function getRightFilename($filename) {
		global $basicPage;

		$pos=strripos($filename,'.');
		if ($pos!==false) {
			$ext=strtolower(substr($filename,$pos+1));
			$filename=substr($filename,0,$pos);
			foreach ($this->defs as $def) {
				foreach ($def[0] as $d) {
					if ($d==$ext) {
						// bewaar welke files weg moeten worden gegooid
						foreach ($def[0] as $d1) if ($d1!=$d) {$this->remove_exts[]=$d1;}
						// $def[2] bevat boolean; Moet je de kaartnaam gebruiken
						// $def[3] bevat vaste filenaam of ''
						if ($def[2]) {
							global $db;
							$kaart=$db->selectOne('geopackages','kaartnaam','id='.$this->gpid);
							if ($kaart) {
								return $kaart['kaartnaam'].'.'.$ext;
							}
							return false;
						}
						if ($def[3]!='') {
							return $def[3].'.'.$ext;
						}
						return $filename.'.'.$ext;
					}
				}
			}
		}
		return false;
	}

	function removeAllButLastUploaded() {
		global $basicPage;
		
		$pad=$basicPage->getConfig('geo-mappen').'/geo-packages/gpid-'.$this->gpid.'/';
		foreach ($this->remove_exts as $ext) {
			$f=glob($pad.'*.'.$ext);
			if ($f) {
				//unlink($f[0]);
				$basicPage->writeLog('Unlink: '.$f[0]);
			}
		}
	}
}
?>