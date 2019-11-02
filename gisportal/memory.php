<?php
if (!isset($openshift_api)) {require('openshift_api.php');}

class memory  {
	var $persistent_afk='ERROR-NO-PERSITENT-STORAGE-FOUND';
	var $persistent=0;
	var $used=0;
	var $available=0;
	
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
					case 'Used': $this->used=intval($regels[1][$t]); break;
					case 'Available	': $this->available	=intval($regels[1][$t]); break;
				}
			}
		}
	}
}

$memory=new memory();
?>