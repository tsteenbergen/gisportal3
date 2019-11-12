<?php
class openshift_api_ {
	var $def=[
		'endpoint'=>[
			'type'=>'endpoints',
			'array'=>'EndpointsList',
			'get-api'=>'api/v1',
		],	
		'persistentvolumeclaim'=>[
			'type'=>'persistentvolumeclaims',
			'array'=>'PersistentVolumeClaimList',
			'get-api'=>'api/v1',
		],
		'replicationcontroller'=>[
			'type'=>'replicationcontrollers',
			'array'=>'ReplicationControllerList',
			'get-api'=>'api/v1',
			'delete-api'=>'api/v1',
		],	
		'deploymentconfig'=>[
			'type'=>'deploymentconfigs',
			'array'=>'DeploymentConfigList',
			'get-api'=>'oapi/v1',
			'post-api'=>'oapi/v1',
			'delete-api'=>'oapi/v1',
		],	
		'pod'=>[
			'type' => 'pods',
			'array' => 'PodList',
			'get-api'=>'api/v1',
			'delete-api'=>'api/v1',
		],
		'autoscaler'=>[
			'type' => 'horizontalpodautoscalers',
			'array' => 'HorizontalPodAutoscalerList',
			'get-api'=>'apis/autoscaling/v1',
			'post-api'=>'apis/autoscaling/v1',
			'delete-api'=>'apis/autoscaling/v1',
		],
		'service'=>[
			'type' => 'services',
			'array' => 'ServiceList',
			'get-api'=>'api/v1',
			'post-api'=>'api/v1',
			'delete-api'=>'api/v1',
		],
		'route'=>[
			'type' => 'routes',
			'array' => 'RouteList',
			'get-api'=>'oapi/v1',
			'post-api'=>'oapi/v1',
			'delete-api'=>'oapi/v1',
		],
	];

	var $allowed;
	var $response;
	
	function __construct() {
		// de globals $loggedIn en $is_admin zijn niet altijd (bij login) geset, daarom diract in $_SESSION cheken of e.e.a. mag
		$this->allowed=($_SESSION['user']>=1 && $_SESSION['is_admin']);
		$this->response=json_decode(json_encode(array('status'=>'Failure','message'=>'Not allowed')),false);
	}
	
	// $curlrequest GET, POST, of DELETE
	function command($curlrequest,$type,$data=false) {
		global $basicPage;
		global $is_admin;
		
		$api_url='';
		$bearer='';
		// $_SERVER['HTTP_HOST'] = 'gisportal-proj2.192.168.99.107.nip.io'
		// $_SERVER['HTTP_HOST'] = 'gisportal-sscc-geoweb-co.apps.ssc-campus.nl'
		// $_SERVER['HTTP_HOST'] = 'appname__-namespace__-c?.apps.ssc-campus.nl'
		//   het vraagteken staat voor o=Ontwikkel, t=Test, a=Acceptatie, p=Productie

		/*if ($api!='api' && $api!='oapi') {
			$api_url=$basicPage->getConfig('endpoint').'/'.$api.'/namespaces/'.$basicPage->getConfig('namespace').'/';
		} else {
			$api_url=$basicPage->getConfig('endpoint').'/'.$api.'/v1/namespaces/'.$basicPage->getConfig('namespace').'/';
		}*/
		
		$todo=$this->def[$type];
		$api_url=$basicPage->getConfig('endpoint').'/';
		switch ($curlrequest) {
			case 'GET': $api_url.=$todo['get-api']; break;
			case 'POST': $api_url.=$todo['post-api']; break;
			case 'DELETE': $api_url.=$todo['delete-api']; break;
		}
		$api_url.='/namespaces/'.$basicPage->getConfig('namespace').'/'.$todo['type'];
		if (isset($data['name'])) {$api_url.='/'.$data['name'];}
		if (isset($data['labelSelector'])) {$api_url.='?labelSelector='.$data['labelSelector'];}
		
		if ($type===false) {$api_url=$data['apiurl'];}

		$bearer=getenv('gisbeheertoken');
		if ($this->allowed || $type=='endpoint') {
			if ($api_url!='' && $bearer!='') {
				$headers = [
					'Authorization: Bearer '.$bearer,
					'Accept: application/json',
					'Content-Type: application/json',
				];

				$curl = curl_init();
				curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($curl, CURLOPT_URL, $api_url);
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $curlrequest);
				curl_setopt($curl, CURLOPT_POST, false);
				if (isset($data['parms'])) {curl_setopt($curl, CURLOPT_POSTFIELDS, $data['parms']);}
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
		$basicPage->writeLog($curlrequest.' '.$api_url,'Input:'.$data['parms'].'<br><div onclick="$($(this).next()).toggle();" class="logklik">Response</div><div style="display: none;">'.$this->stdClassToString($this->response).'<div>');
	}
	
	function stdClassToString($o,$depth=0) {
		$r='';
		foreach ($o as $k=>$v) {
			switch (gettype($v)) {
				case 'array': case 'object': $r.=$this->stdClassToString($v,$depth+1); break;
				default: $r.='<div style="padding-left: '.(20*$depth).'px;">'.$k.': '.$v.'</div>'; break;
			}
		}
		return $r;
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
	
	// $todo_types   'all' wordt vertaald naar array met types
	//				 je kunt ook zelf array met bepaalde types meegeven
	function createDeploymentConfig($id, $variables, $todo_types='all',$update=false) {
		global $basedir;
		global $db;
		global $basicPage;
		
		if ($todo_types=='all') {$todo_types=['deploymentconfig','autoscaler','service','route'];}
		$persistent_storage=$basicPage->getConfig('persistent_storage');
		foreach ($todo_types as $todo_type) {
			$todo=$this->def[$todo_type];
			$jsonString = file_get_contents($basedir.'json-templates/'.$todo_type.'.json');
			// default replacements
			$jsonString = str_replace('$host',$_SERVER['HTTP_HOST'],$jsonString);
			$jsonString = str_replace('$namespace',$basicPage->getConfig('namespace'),$jsonString);
			$jsonString = str_replace('$storage',$persistent_storage,$jsonString);
			$jsonString = str_replace('$name','gpid-'.$id,$jsonString);
			// aanvullende replacements
			foreach ($variables as $variable=>$value) {
				$jsonString = str_replace('$'.$variable,$value,$jsonString);
			}
			$this->command($update?'PUT':'POST',$todo_type,['parms'=>$jsonString]);
			if ($todo_type=='deploymentconfig') { // wacht tot deploymentconfig er is
				$maxAant=28; // wacht maximaal 28 seconden
				while ($maxAant>0) {
					$this->command('GET',$todo_type,['name'=>'gpid-'.$id]);
					if ($this->response->kind=='DeploymentConfig') {
						$maxAant=0;
					}
					$maxAant--;
					if ($maxAant>0) {
						// usleep(100000); // 100.000 microseconden is 0.1 seconde
						sleep(1); // 1 seconde
					}
				}
				if ($maxAant==0) {
					$basicPage->writeLog('DeploymentConfig en ReplicationController gpid-'.$id.' not properly created.');
				}
				//sleep(10);
			}
		}
	}
	function deleteDeploymentConfig($id,$todo_types=['replicationcontroller','deploymentconfig','autoscaler','pod','service','route']) {
		$jsonString = '{"kind":"DeleteOptions","apiVersion":"v1","propagationPolicy":"Background","gracePeriodSeconds":0,"includeUninitialized":true,"watch":true}';
		$checkItems=[];
		foreach ($todo_types as $todo_type) {
			$todo=$this->def[$todo_type];
			$this->command('GET',$todo_type,['labelSelector'=>'name=gpid-'.$id,'parms'=>'{"includeUninitialized":true}']);
			if ($this->response->kind==$todo['array']) {
				$items=[];
				for ($t=0;$t<count($this->response->items);$t++) {
					$items[]=$this->response->items[$t]->metadata->name;
					$checkItems[]=['type'=>$todo_type,'name'=>$this->response->items[$t]->metadata->name];
				}
				for ($t=0;$t<count($items);$t++) {
					$this->command('DELETE',$todo_type,['name'=>$items[$t],'parms'=>$jsonString]);
				}
			}
		}
		// Wacht tot alles echt verwijderd is
		$maxAant=100; // wacht maximaal 10 seconden
		while (count($checkItems)>0 && $maxAant>0) {
			for ($t=count($checkItems)-1;$t>=0;$t--) {
				$item=$checkItems[$t];
				$this->command('GET',$item['type'],['name'=>$item['name']]);
				if ($this->response->status=='Failure' && $this->response->reason=='NotFound') {
					array_splice($checkItems,$t,1);
				}
				
			}
			$maxAant--;
			usleep(100000); // 100.000 microseconden is 0.1 seconde
			//sleep(1); // 1 seconde
		}
	}
	
	function getStatus($o) {
		foreach ($o as $item) {
			
		}
	}
	function healthChecks($id, $todo_types=['replicationcontroller','deploymentconfig','autoscaler','pod','service','route']) {
		// Met -loglevel 10 werd meegegeven: Accept: application/json;as=Table;v=v1beta1;g=meta.ks8.io, application/json
		$r=[];
		$jsonParms='{"includeUninitialized":true}';
		foreach ($todo_types as $todo_type) {
			$todo=$this->def[$todo_type];
			$r[$todo_type]=['error'=>false,'items'=>[]];
			$this->command('GET',$todo_type,['labelSelector'=>'name=gpid-'.$id,'parms'=>$jsonParms]);
			if ($this->response->kind==$todo['array']) {
				$t1=count($this->response->items);
				if ($t1>=1) {
					$r[$todo_type]['msg']=$t1.($t1==1?' item':' items').' found: ';
					for ($t=0;$t<$t1;$t++) {
						$r[$todo_type]['msg'].=($t==0?'':($t<$t1-1?', ':' and ')).$this->response->items[$t]->metadata->name;
						$r[$todo_type]['items'][]=['name'=>$this->response->items[$t]->metadata->name,'msg'=>''];
					}
					for ($t=0;$t<$t1;$t++) {
						$this->command('GET',$todo_type,['name'=>$r[$todo_type]['items'][$t]['name']]);
						switch($todo_type) {
							case 'replicationcontroller':
								$r[$todo_type]['items'][$t]['msg']=$this->response->metadata->replicas.'<br>';
								$r[$todo_type]['items'][$t]['msg']=$this->response->metadata->readyReplicas.'<br>';
								$r[$todo_type]['items'][$t]['msg']=$this->response->metadata->availableReplicas.'<br>';
								$r[$todo_type]['items'][$t]['msg']=$this->response->metadata->observedGeneration.'<br>';
								$r[$todo_type]['items'][$t]['msg']+=var_export($this->response,true);
								break;
							default:
								$r[$todo_type]['items'][$t]['msg']=var_export($this->response,true);
								break;
						}
					}
				} else {
					switch($todo_type) {
						case 'replicationcontroller':
							$r[$todo_type]['msg']='Error: At least 1 replicationcontroller should be present.<br>This may be coused by a missing/incorrect image name.';
							break;
						case 'deploymentconfig':
							$r[$todo_type]['msg']='Error: At least 1 deploymentconfig should be present.';
							break;
						case 'autoscaler':
							$r[$todo_type]['msg']='Error: At least 1 autoscaler should be present.';
							break;
						case 'pod':
							$r[$todo_type]['msg']='Possible error: No pods found. Pods can be scaled down to 0 to free resources for other pods.';
							break;
						case 'service':
							$r[$todo_type]['msg']='Error: No service found.';
							break;
						case 'route':
							$r[$todo_type]['msg']='Error: No route found.';
							break;
					}
				}
			} else {
				$r[$todo_type]['error']=true;
				if ($this->response->status=='Failure') {
					$r[$todo_type]['msg']='Error: Not found';
				} else {
					$r[$todo_type]['msg']='Error: '.var_export($this->response,true);
				}
			}
		}
		return $r;
	}
}

$openshift_api = new openshift_api_();
?>