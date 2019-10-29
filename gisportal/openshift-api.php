<?php
class openshift_api_ {
	var $def=[
		'replicationcontroller'=>[
			'type'=>'replicationcontrollers',
			'array'=>'ReplicationControllerList',
			'api'=>'api'
		],	
		'deploymentconfig'=>[
			'type'=>'deploymentconfigs',
			'array'=>'DeploymentConfigList',
			'api'=>'apis/apps.openshift.io/v1'
		],	
		'pod'=>[
			'type' => 'pods',
			'array' => 'PodList',
			'api' =>'api'
		],
		'autoscaler'=>[
			'type' => 'horizontalpodautoscalers',
			'array' => 'HorizontalPodAutoscaler',
			'api' =>'apis/autoscaling/v1'
		],
		'service'=>[
			'type' => 'services',
			'array' => 'ServiceList',
			'api' =>'api'
		],
		'route'=>[
			'type' => 'routes',
			'array' => 'RouteList',
			'api' =>'apis/route.openshift.io/v1'
		],
	];

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
		if ($api!='api' && $api!='oapi') {
			$api_url=$basicPage->endpoint.'/'.$api.'/namespaces/'.$basicPage->namespace.'/';
		} else {
			$api_url=$basicPage->endpoint.'/'.$api.'/v1/namespaces/'.$basicPage->namespace.'/';
		}
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

	function createDeploymentConfig($subpath,$id, $theme, $kaartnaam, $image, $version,$todo_types=['deploymentconfigs','horizontalpodautoscalers','services','routes']) {
		if (array_search('deploymentconfigs',$todo_types)!==false) {
			$jsonString = file_get_contents($subpath.'json-templates/deploymentconfig.json');
			$jsonString = str_replace('$namespace',$basicPage->namespace,$jsonString);
			$jsonString = str_replace('$name','gpid-'.$id,$jsonString);
			$jsonString = str_replace('$image',$image,$jsonString);
			$jsonString = str_replace('$version',$version,$jsonString);
			$jsonString = str_replace('$storage','geo-mappen',$jsonString);
			$this->command('oapi','deploymentconfigs','POST',$jsonString);
		}
		// wacht tot deploymentconfig er is
		if (array_search('horizontalpodautoscalers',$todo_types)!==false) {
			// POST https://portaal.int.ssc-campus.nl:8443/apis/autoscaling/v1/namespaces/sscc-geoweb-co/horizontalpodautoscalers
			$jsonString = file_get_contents($subpath.'json-templates/autoscaler.json');
			$jsonString = str_replace('$namespace',$basicPage->namespace,$jsonString);
			$jsonString = str_replace('$name','gpid-'.$id,$jsonString);
			$this->command('apis/autoscaling/v1','horizontalpodautoscalers','POST',$jsonString);
		}
		if (array_search('services',$todo_types)!==false) {
			$jsonString = file_get_contents($subpath.'json-templates/service.json');
			$jsonString = str_replace('$namespace',$basicPage->namespace,$jsonString);
			$jsonString = str_replace('$name','gpid-'.$id,$jsonString);
			$this->command('api','services','POST',$jsonString);
		}
		if (array_search('routes',$todo_types)!==false) {
			$jsonString = file_get_contents($subpath.'json-templates/route.json');
			$jsonString = str_replace('$map-name',$kaartnaam,$jsonString);
			$jsonString = str_replace('$namespace',$basicPage->namespace,$jsonString);
			$jsonString = str_replace('$name','gpid-'.$id,$jsonString);
			$jsonString = str_replace('$host','acceptatie-data.rivm.nl',$jsonString);
			$jsonString = str_replace('$map-theme',$theme,$jsonString);
			$this->command('oapi','routes','POST',$jsonString);
		}
	}
	function deleteDeploymentConfig($id,$todo_types=['pod','replicationcontroller','service','horizontalpodautoscaler','deploymentconfig','route']) {
		$jsonString = '{"kind":"DeleteOptions","apiVersion":"v1","propagationPolicy":"Foreground","gracePeriodSeconds":0}';
		$checkItems=[];
		foreach ($todo_types as $todo_type) {
			$todo=$this->def[$todo_type];
			$this->command($todo['api'],$todo['type'].'?labelSelector=name=gpid-'.$id);
			if ($this->response->kind==$todo['array']) {
				$items=[];
				for ($t=0;$t<count($this->response->items);$t++) {
					$items[]=$this->response->items[$t]->metadata->name;
					$checkItems[]=['type'=>$todo['type'],'api'=>$todo['api'],'name'=>$this->response->items[$t]->metadata->name];
				}
				for ($t=0;$t<count($items);$t++) {
					$this->command($todo['api'],$todo['type'].'/'.$items[$t],'DELETE',$jsonString);
				}
			}
		}
		// Wacht tot alles weg is
		$maxAant=20; // wacht maximaal 20 seconden
		while (count($checkItems)>0 && $maxAant>0) {
			for ($t=count($checkItems)-1;$t>=0;$t--) {
				$item=$checkItems[$t];
				$this->command($item['api'],$item['type'].'/'.$item['name']);
				if ($this->response->status=='Failure' && $this->response->reason=='NotFound') {
					array_splice($checkItems,$t,1);
				}
				
			}
			$maxAant--;
			// usleep(100000); // 100.000 microseconden is 0.1 seconde
			sleep(1); // 1 seconde
		}


	}
}

$openshift_api = new openshift_api_();
?>