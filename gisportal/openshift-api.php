<?php
class openshift_api_ {
	var $allowed;
	var $response;
	
	function __construct() {
		// de globals $loggedIn en $is_admin zijn niet altijd (bij login) geset, daarom diract in $_SESSION cheken of e.e.a. mag
		$this->allowed=($_SESSION['user']>=1 && $_SESSION['is_admin']);
		$this->response=json_decode(json_encode(array('status'=>'Failure','message'=>'Not allowed')),false);
	}
	
	function command($api,$command,$subcommand=false,$data=false) {
		global $basicPage;
		global $is_admin;
		
		$api_url='';
		$bearer='';
		// $_SERVER['HTTP_HOST'] = 'gisportal-proj2.192.168.99.107.nip.io'
		// $_SERVER['HTTP_HOST'] = 'gisportal-sscc-geoweb-co.apps.ssc-campus.nl'
		// $_SERVER['HTTP_HOST'] = 'appname__-namespace__-c?.apps.ssc-campus.nl'
		//   het vraagteken staat voor o=Ontwikkel, t=Test, a=Acceptatie, p=Productie
		$api_url=$basicPage->endpoint.'/'.$api.'/v1/namespaces/'.$basicPage->namespace.'/';
		$bearer=getenv('gisbeheertoken');
		if ($this->allowed) {
//global $basicPage;
//$basicPage->writeLog('$bearer = '.$bearer);
			if ($api_url!='' && $bearer!='') {
				$headers = [
					'Authorization: Bearer '.$bearer,
					'Accept: application/json',
					'Content-Type: application/json',
				];

				$curl = curl_init();
				curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($curl, CURLOPT_URL, $api_url.$command);
				if ($subcommand) {curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $subcommand);}
				curl_setopt($curl, CURLOPT_POST, false);
				if ($data) {curl_setopt($curl, CURLOPT_POSTFIELDS, $data);}
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				$this->response=curl_exec($curl);
				if (curl_errno($curl)) {
					$this->response=json_decode(json_encode(array('status'=>'Failure','message'=>curl_errno($curl).' '.curl_error($curl))),false);
				} else {
					if (substr($this->response,0,1)=='{') {
						$this->response = json_decode($this->response);
					} else {
						$this->response=json_decode(json_encode(array('status'=>'Failure','message'=>$this->response)),false);
					}
				}
			} else {
				$this->response=json_decode(json_encode(array('status'=>'Failure','message'=>'HTTP_HOST cannot be resolved to API URL and Bearer.<br>$api_url='.$api_url.'<br>$bearer='.$bearer.'<br>HTTP_HOST: '.$_SERVER['HTTP_HOST'].'<br>$elements: '.var_export($elements,true))),false);
			}
		} else {
			$this->response=json_decode(json_encode(array('status'=>'Failure','message'=>'Not allowed')),false);
		}
		global $basicPage;
		$basicPage->writeLog($api_url.$command.($subcommand?' : '.$subcommand:'').'<br>'.$data,var_export($this->response,true));
	}
	
	function succes() {
		return true;
		if (property_exists($this->response,'status')) {
			return $this->response.status=='Failure';
		}
		return false;
	}
	
	
	function responseToString() {
		if ($this->response->status=='Failure') {
			$r='Failure: '.$this->response->message;
		} else {
			if ($this->response->kind=='PodList') {
				$r='';
				for ($t=0;$t<count($this->response->items);$t++) {
					$pod=$this->response->items[$t];
					$r.='Pod: '.$pod->metadata->name.' '.$pod->status->phase.'<br>';
				}
			} else {
				$r=var_export($this->response,true);
			}
		}
		return $r;
	}
	
//	function getPodInfo($gpid) {
//		$this->command('api','pods/gpid-'.$gpid);
//	}
	
	function monitorPod($gpid) {
		$this->command('api','pods?labelSelector=name=gpid-'.$gpid);
	}
//	function deletePod($gpid) {
//		$this->command('api','pods/gpid-'.$gpid,'DELETE');
//	}

	function createDeploymentConfig($subpath,$id, $theme, $kaartnaam, $image, $version) {
		global $basicPage;
		$jsonString = file_get_contents($subpath.'json-templates/deploymentconfig.json');
		$jsonString = str_replace('$namespace',$basicPage->namespace,$jsonString);
		$jsonString = str_replace('$name','gpid-'.$id,$jsonString);
		$jsonString = str_replace('$image',$image,$jsonString);
		$jsonString = str_replace('$version',$version,$jsonString);
		$jsonString = str_replace('$storage','geo-mappen',$jsonString);
		$this->command('oapi','deploymentconfigs','POST',$jsonString);
		$jsonString = file_get_contents($subpath.'json-templates/service.json');
		$jsonString = str_replace('$namespace',$basicPage->namespace,$jsonString);
		$jsonString = str_replace('$name','gpid-'.$id,$jsonString);
		$this->command('api','services','POST',$jsonString);
		$jsonString = file_get_contents($subpath.'json-templates/route.json');
		$jsonString = str_replace('$map-name','/'.$kaartnaam,$jsonString);
		$jsonString = str_replace('$namespace',$basicPage->namespace,$jsonString);
		$jsonString = str_replace('$name','gpid-'.$id,$jsonString);
		$jsonString = str_replace('$host','acceptatie-data.rivm.nl',$jsonString);
		$jsonString = str_replace('$map-theme',$theme,$jsonString);
		$this->command('oapi','routes','POST',$jsonString);
	}
	function updateDeploymentConfig($subpath,$id, $kaartnaam, $image, $version) {
		global $basicPage;
		$jsonString = file_get_contents($subpath.'json-templates/deploymentconfig.json');
		$jsonString2= $jsonString;
		$jsonString = str_replace('$namespace',$basicPage->namespace,$jsonString);
		$jsonString = str_replace('$name','gpid-'.$id,$jsonString);
		$jsonString = str_replace('$image',$image,$jsonString);
		$jsonString = str_replace('$version',$version,$jsonString);
		$jsonString = str_replace('$storage','geo-mappen',$jsonString);
		// je mag dit alleen doen als er iets is verandert. Dat moet nog uitgezocht en geprogrammeerd...
		$this->command('oapi','deploymentconfigs/dc-gpid-'.$id,'PUT',$jsonString);
	}
	function deleteDeploymentConfig($subpath,$id) {
		$jsonString = '{}';
		$this->command('api','replicationcontrollers/gpid-'.$id.'-1','DELETE',$jsonString);
		$jsonString = '{}';
		$this->command('oapi','deploymentconfigs/gpid-'.$id,'DELETE',$jsonString);
		$jsonString = '{}';
		$this->command('api','services/gpid-'.$id,'DELETE',$jsonString);
		$jsonString = '{}';
		$this->command('oapi','routes/gpid-'.$id,'DELETE',$jsonString);
		$jsonString = '{}';
		$items=$this->command('api','pods?labelSelector=name=gpid-'.$id);
global $basicPage;
$basicPage->writeLog('$items='.var_export($items,true));
		if ($items) {
$basicPage->writeLog('$items[\'items\']='.var_export($items['items'],true));
$basicPage->writeLog('$items[\'items\'][0]='.var_export($items['items'][0],true));
$basicPage->writeLog('$items[\'items\'][0][\'metadata\']='.var_export($items['items'][0]['metadata'],true));
$basicPage->writeLog('$items[\'items\'][0][\'metadata\'][\'naam\']='.var_export($items['items'][0]['metadata']['naam'],true));
			$items=$items['items'];
			foreach ($items as $item) {
$basicPage->writeLog('POD: '.$item['metadata']['name']);
			}
		}
		//$this->command('api','pods/gpid-'.$id,'DELETE',$jsonString);
		
		
		
		
	}
}

$openshift_api = new openshift_api_();
?>