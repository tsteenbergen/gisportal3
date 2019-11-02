<?php
if (!isset($openshift_api)) {require($basedir.'openshift_api.php');}

class memory  {
	var $persistent_afk='ERROR-NO-PERSITENT-STORAGE-FOUND';
	var $persistent=0;
	var $persistent_mb='';
	var $used=0;
	var $available=0;
	var $total=0;
	var $used_mb='';
	var $available_mb='';
	var $total_mb='';
	
	function __construct() {
		global $openshift_api;
		global $basicPage;
		
		$openshift_api->command('api','persistentvolumeclaims/'.$basicPage->getConfig('persistent_storage'));
		$this->persistent_afk=$openshift_api->response->spec->resources->requests->storage;
		foreach (['T','Ti','G','Gi','M','Mi','K','Ki'] as $b) if (substr($this->persistent_afk,-strlen($b))==$b) {
			$this->persistent=intval(substr($this->persistent_afk,0,strlen($this->persistent_afk)-strlen($b)));
			switch ($b) {
				case 'T': $this->persistent*=1000000000000; break;
				case 'Ti': $this->persistent*=1024*1024*1024*1024; break;
				case 'G': $this->persistent*=1000000000; break;
				case 'Gi': $this->persistent*=1024*1024*1024; break;
				case 'M': $this->persistent*=1000000; break;
				case 'Mi': $this->persistent*=1024*1024; break;
				case 'K': $this->persistent*=1000; break;
				case 'Ki': $this->persistent*=1024; break;
			}
		}
		$path=$basicPage->getConfig('geo-mappen');
		shell_exec(' df '.$path.' > '.$path.'/df.df');
		$df=file_get_contents($path.'/df.df'); $df=str_ireplace(chr(13).chr(10),chr(13),$df); $df=str_ireplace(chr(10),chr(13),$df); $df=explode(chr(13),$df);
		$regels=[];
		foreach ($df as $regel) {
			$regels[]=explode(' ',preg_replace('/\s+/', ' ',$regel));
		}
		if (count($regels)>=2) {
			for ($t=0;$t<count($regels[0]);$t++) {
				switch ($regels[0][$t]) {
					case 'Used': $this->used=1024*intval($regels[1][$t]); break;
					case 'Available': $this->available	=1024*intval($regels[1][$t]); break;
				}
			}
		}
		$this->total=$this->used+$this->available;
		if ($this->persistent>=1) {$this->persistent_mb=number_format($this->persistent/1000000,0,',','.').' MB';}
		if ($this->used>=1) {$this->used_mb=number_format($this->used/1000000,0,',','.').' MB';}
		if ($this->available>=1) {$this->available_mb=number_format($this->available/1000000,0,',','.').' MB';}
		if ($this->total>=1) {$this->total_mb=number_format($this->total/1000000,0,',','.').' MB';}
	}
	function maxUploadsize() {
		$r=$this->available;
		$spare=1000000;
		if ($r>=$spare) {
			$r-=$spare;
		} else {
			$r=0;
		}
		return $r;
	}
}

$memory=new memory();
?>