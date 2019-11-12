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
								$r[$todo_type]['items'][$t]['parms']=['replicas'=>$this->response->status->replicas, 'availableReplicas'=>$this->response->status->availableReplicas, 'readyReplicas'=>$this->response->status->readyReplicas, 'observedGeneration'=>$this->response->status->observedGeneration];
								break;
							case 'deploymentconfig':
								/*stdClass::__set_state(array( 
									'kind' => 'DeploymentConfig', 
									'apiVersion' => 'v1', 
									'metadata' => stdClass::__set_state(array( 'name' => 'gpid-127', 'namespace' => 'sscc-geoweb-co', 'selfLink' => '/oapi/v1/namespaces/sscc-geoweb-co/deploymentconfigs/gpid-127', 'uid' => 'a0601c8e-0477-11ea-8013-0050569da694', 'resourceVersion' => '56939419', 'generation' => 3, 'creationTimestamp' => '2019-11-11T11:37:22Z', 'labels' => stdClass::__set_state(array( 'app' => 'gisportal-map', 'name' => 'gpid-127', )), )), 
									'spec' => stdClass::__set_state(array( 'strategy' => stdClass::__set_state(array( 'type' => 'Rolling', 'rollingParams' => stdClass::__set_state(array( 'updatePeriodSeconds' => 1, 'intervalSeconds' => 1, 'timeoutSeconds' => 600, 'maxUnavailable' => '25%', 'maxSurge' => '25%', )), 'resources' => stdClass::__set_state(array( )), 'activeDeadlineSeconds' => 21600, )), 'triggers' => array ( 0 => stdClass::__set_state(array( 'type' => 'ConfigChange', )), 1 => stdClass::__set_state(array( 'type' => 'ImageChange', 'imageChangeParams' => stdClass::__set_state(array( 'automatic' => true, 'containerNames' => array ( 0 => 'gpid-127', ), 'from' => stdClass::__set_state(array( 'kind' => 'ImageStreamTag', 'namespace' => 'sscc-geoweb-co', 'name' => 'mapserver-sscc:latest', )), 'lastTriggeredImage' => 'docker-registry.default.svc:5000/sscc-geoweb-co/mapserver-sscc@sha256:e51d9ac704739fcf666b9a71a7f903d4cd5dc5ff9761322d5eaa12e8c30a1ec2', )), )), ), 'replicas' => 1, 'test' => false, 'selector' => stdClass::__set_state(array( 'name' => 'gpid-127', )), 'template' => stdClass::__set_state(array( 'metadata' => stdClass::__set_state(array( 'creationTimestamp' => NULL, 'labels' => stdClass::__set_state(array( 'name' => 'gpid-127', )), )), 'spec' => stdClass::__set_state(array( 'volumes' => array ( 0 => stdClass::__set_state(array( 'name' => 'geo-map', 'persistentVolumeClaim' => stdClass::__set_state(array( 'claimName' => 'geo-mappen', )), )), ), 'containers' => array ( 0 => stdClass::__set_state(array( 'name' => 'gpid-127', 'image' => 'docker-registry.default.svc:5000/sscc-geoweb-co/mapserver-sscc@sha256:e51d9ac704739fcf666b9a71a7f903d4cd5dc5ff9761322d5eaa12e8c30a1ec2', 'ports' => array ( 0 => stdClass::__set_state(array( 'containerPort' => 80, 'protocol' => 'TCP', )), ), 'resources' => stdClass::__set_state(array( 'limits' => stdClass::__set_state(array( 'cpu' => '800m', 'memory' => '1200Mi', )), 'requests' => stdClass::__set_state(array( 'cpu' => '80m', 'memory' => '120Mi', )), )), 'volumeMounts' => array ( 0 => stdClass::__set_state(array( 'name' => 'geo-map', 'mountPath' => '/geo-map', 'subPath' => 'geo-packages/gpid-127', )), ), 'terminationMessagePath' => '/dev/termination-log', 'terminationMessagePolicy' => 'File', 'imagePullPolicy' => 'Always', 'securityContext' => stdClass::__set_state(array( 'capabilities' => stdClass::__set_state(array( )), 'privileged' => false, )), )), ), 'restartPolicy' => 'Always', 'terminationGracePeriodSeconds' => 30, 'dnsPolicy' => 'ClusterFirst', 'securityContext' => stdClass::__set_state(array( )), 'schedulerName' => 'default-scheduler', )), )), )), 
									'status' => stdClass::__set_state(array( 
										'latestVersion' => 2, 
										'observedGeneration' => 3, 
										'replicas' => 1, 
										'updatedReplicas' => 1, 
										'availableReplicas' => 1, 
										'unavailableReplicas' => 0, 
										'details' => stdClass::__set_state(array( 'message' => 'manual change', 'causes' => array ( 0 => stdClass::__set_state(array( 'type' => 'Manual', )), ), )), 
										'conditions' => array ( 
											0 => stdClass::__set_state(array( 
												'type' => 'Available', 
												'status' => 'True', 
												'lastUpdateTime' => '2019-11-11T11:37:36Z', 
												'lastTransitionTime' => '2019-11-11T11:37:36Z', 
												'message' => 'Deployment config has minimum availability.', 
											)), 
											1 => stdClass::__set_state(array( 
												'type' => 'Progressing', 
												'status' => 'True', 
												'lastUpdateTime' => '2019-11-12T08:44:44Z', 
												'lastTransitionTime' => '2019-11-12T08:44:41Z', 
												'reason' => 'NewReplicationControllerAvailable', 
												'message' => 'replication controller "gpid-127-2" successfully rolled out', 
											)), 
										), 
										'readyReplicas' => 1, 
									)), 
								))*/
								$r[$todo_type]['items'][$t]['parms']=['replicas'=>$this->response->status->replicas, 'availableReplicas'=>$this->response->status->availableReplicas, 'readyReplicas'=>$this->response->status->readyReplicas, 'observedGeneration'=>$this->response->status->observedGeneration];
								foreach ($this->response->status->conditions as $c) {
									$r[$todo_type]['items'][$t]['parms'][$c->type]=$c->status.' ('.htmlspecialchars($c->message).')';
								}
								break;
							case 'pod':
								/* stdClass::__set_state(array( 
									'kind' => 'Pod', 
									'apiVersion' => 'v1', 
									'metadata' => stdClass::__set_state(array( 'name' => 'gpid-127-2-bhvhd', 'generateName' => 'gpid-127-2-', 'namespace' => 'sscc-geoweb-co', 'selfLink' => '/api/v1/namespaces/sscc-geoweb-co/pods/gpid-127-2-bhvhd', 'uid' => 'a85e2ddc-0528-11ea-a70b-0050569d5aa0', 'resourceVersion' => '56939382', 'creationTimestamp' => '2019-11-12T08:44:36Z', 'labels' => stdClass::__set_state(array( 'deployment' => 'gpid-127-2', 'deploymentconfig' => 'gpid-127', 'name' => 'gpid-127', )), 'annotations' => stdClass::__set_state(array( 'openshift.io/deployment-config.latest-version' => '2', 'openshift.io/deployment-config.name' => 'gpid-127', 'openshift.io/deployment.name' => 'gpid-127-2', 'openshift.io/scc' => 'anyuid', )), 'ownerReferences' => array ( 0 => stdClass::__set_state(array( 'apiVersion' => 'v1', 'kind' => 'ReplicationController', 'name' => 'gpid-127-2', 'uid' => 'a548a998-0528-11ea-a70b-0050569d5aa0', 'controller' => true, 'blockOwnerDeletion' => true, )), ), )), 
									'spec' => stdClass::__set_state(array( 'volumes' => array ( 0 => stdClass::__set_state(array( 'name' => 'geo-map', 'persistentVolumeClaim' => stdClass::__set_state(array( 'claimName' => 'geo-mappen', )), )), 1 => stdClass::__set_state(array( 'name' => 'default-token-p4drb', 'secret' => stdClass::__set_state(array( 'secretName' => 'default-token-p4drb', 'defaultMode' => 420, )), )), ), 'containers' => array ( 0 => stdClass::__set_state(array( 'name' => 'gpid-127', 'image' => 'docker-registry.default.svc:5000/sscc-geoweb-co/mapserver-sscc@sha256:e51d9ac704739fcf666b9a71a7f903d4cd5dc5ff9761322d5eaa12e8c30a1ec2', 'ports' => array ( 0 => stdClass::__set_state(array( 'containerPort' => 80, 'protocol' => 'TCP', )), ), 'resources' => stdClass::__set_state(array( 'limits' => stdClass::__set_state(array( 'cpu' => '800m', 'memory' => '1200Mi', )), 'requests' => stdClass::__set_state(array( 'cpu' => '80m', 'memory' => '120Mi', )), )), 'volumeMounts' => array ( 0 => stdClass::__set_state(array( 'name' => 'geo-map', 'mountPath' => '/geo-map', 'subPath' => 'geo-packages/gpid-127', )), 1 => stdClass::__set_state(array( 'name' => 'default-token-p4drb', 'readOnly' => true, 'mountPath' => '/var/run/secrets/kubernetes.io/serviceaccount', )), ), 'terminationMessagePath' => '/dev/termination-log', 'terminationMessagePolicy' => 'File', 'imagePullPolicy' => 'Always', 'securityContext' => stdClass::__set_state(array( 'capabilities' => stdClass::__set_state(array( 'drop' => array ( 0 => 'MKNOD', ), )), 'privileged' => false, )), )), ), 'restartPolicy' => 'Always', 'terminationGracePeriodSeconds' => 30, 'dnsPolicy' => 'ClusterFirst', 'nodeSelector' => stdClass::__set_state(array( 'node-role.kubernetes.io/compute' => 'true', )), 'serviceAccountName' => 'default', 'serviceAccount' => 'default', 'nodeName' => 'sscc-chhst-l05p.int.ssc-campus.nl', 'securityContext' => stdClass::__set_state(array( 'seLinuxOptions' => stdClass::__set_state(array( 'level' => 's0:c8,c2', )), )), 'imagePullSecrets' => array ( 0 => stdClass::__set_state(array( 'name' => 'default-dockercfg-qj9jg', )), ), 'schedulerName' => 'default-scheduler', 'tolerations' => array ( 0 => stdClass::__set_state(array( 'key' => 'node.kubernetes.io/memory-pressure', 'operator' => 'Exists', 'effect' => 'NoSchedule', )), ), 'priority' => 0, )), 
									'status' => stdClass::__set_state(array( 
										'phase' => 'Running', 
										'conditions' => array ( 
											0 => stdClass::__set_state(array( 
												'type' => 'Initialized', 
												'status' => 'True', 
												'lastProbeTime' => NULL, 'lastTransitionTime' => '2019-11-12T08:44:36Z', 
											)), 
											1 => stdClass::__set_state(array( 
												'type' => 'Ready', 
												'status' => 'True', 
												'lastProbeTime' => NULL, 'lastTransitionTime' => '2019-11-12T08:44:40Z', 
											)), 
											2 => stdClass::__set_state(array( 
												'type' => 'ContainersReady', 
												'status' => 'True', 
												'lastProbeTime' => NULL, 'lastTransitionTime' => NULL,
											)), 
											3 => stdClass::__set_state(array( 
												'type' => 'PodScheduled', 
												'status' => 'True', 
												'lastProbeTime' => NULL, 'lastTransitionTime' => '2019-11-12T08:44:36Z', 
											)), 
										), 
										'hostIP' => '131.224.53.65', 
										'podIP' => '10.131.0.137', 
										'startTime' => '2019-11-12T08:44:36Z', 
										'containerStatuses' => array ( 
											0 => stdClass::__set_state(array( 
												'name' => 'gpid-127', 
												'state' => stdClass::__set_state(array( 'running' => stdClass::__set_state(array( 'startedAt' => '2019-11-12T08:44:39Z', )), )), 
												'lastState' => stdClass::__set_state(array( )), 
												'ready' => true, 'restartCount' => 0, 
												'image' => 'docker-registry.default.svc:5000/sscc-geoweb-co/mapserver-sscc@sha256:e51d9ac704739fcf666b9a71a7f903d4cd5dc5ff9761322d5eaa12e8c30a1ec2', 
												'imageID' => 'docker-pullable://docker-registry.default.svc:5000/sscc-geoweb-co/mapserver-sscc@sha256:e51d9ac704739fcf666b9a71a7f903d4cd5dc5ff9761322d5eaa12e8c30a1ec2', 
												'containerID' => 'docker://b75d9580e39c5712670886f71207d5042d177893b711284e3ac7ccc6e658d422',
											)), 
										), 
										'qosClass' => 'Burstable',
									)), 
								)) */
								foreach ($this->response->status->conditions as $c) {
									$r[$todo_type]['items'][$t]['parms'][$c->type]=$c->status;
								}
								break;
							case 'service':
								/* stdClass::__set_state(array( 
									'kind' => 'Service', 
									'apiVersion' => 'v1', 
									'metadata' => stdClass::__set_state(array( 'name' => 'gpid-127', 'namespace' => 'sscc-geoweb-co', 'selfLink' => '/api/v1/namespaces/sscc-geoweb-co/services/gpid-127', 'uid' => 'a06cef63-0477-11ea-8013-0050569da694', 'resourceVersion' => '56652162', 'creationTimestamp' => '2019-11-11T11:37:22Z', 'labels' => stdClass::__set_state(array( 'name' => 'gpid-127', )), )), 
									'spec' => stdClass::__set_state(array( 'ports' => array ( 0 => stdClass::__set_state(array( 'name' => 'gpid-127', 'protocol' => 'TCP', 'port' => 80, 'targetPort' => 80, )), ), 'selector' => stdClass::__set_state(array( 'name' => 'gpid-127', )), 'clusterIP' => '172.30.24.74', 'type' => 'ClusterIP', 'sessionAffinity' => 'None', )), 
									'status' => stdClass::__set_state(array( 'loadBalancer' => stdClass::__set_state(array( )), )), 
								)) */
								break;
							case 'route':
								/* stdClass::__set_state(array( 
									'kind' => 'Route', 
									'apiVersion' => 'v1', 
									'metadata' => stdClass::__set_state(array( 'name' => 'gpid-127', 'namespace' => 'sscc-geoweb-co', 'selfLink' => '/oapi/v1/namespaces/sscc-geoweb-co/routes/gpid-127', 'uid' => 'a0719218-0477-11ea-8013-0050569da694', 'resourceVersion' => '56652165', 'creationTimestamp' => '2019-11-11T11:37:22Z', 'labels' => stdClass::__set_state(array( 'name' => 'gpid-127', )), )), 
									'spec' => stdClass::__set_state(array( 'host' => 'acceptatie.data.rivm.nl', 'path' => '/geo/uuuuuu12/abc', 'to' => stdClass::__set_state(array( 'kind' => 'Service', 'name' => 'gpid-127', 'weight' => 100, )), 'port' => stdClass::__set_state(array( 'targetPort' => 'gpid-127', )), 'wildcardPolicy' => 'None', )), 
									'status' => stdClass::__set_state(array( 
										'ingress' => array ( 
											0 => stdClass::__set_state(array( 'host' => 'acceptatie.data.rivm.nl', 'routerName' => 'router-shard-dmz', 'conditions' => array ( 0 => stdClass::__set_state(array( 'type' => 'Admitted', 'status' => 'True', 'lastTransitionTime' => '2019-11-11T11:37:22Z', )), ), 'wildcardPolicy' => 'None', )), 
										), 
									)),
								)) */
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