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
			'api'=>'apis/apps.openshift.io/v1',
			'create-api'=>'oapi'
		],	
		'pod'=>[
			'type' => 'pods',
			'array' => 'PodList',
			'api' =>'api'
		],
		'autoscaler'=>[
			'type' => 'horizontalpodautoscalers',
			'array' => 'HorizontalPodAutoscaler',
			'api' =>'apis/autoscaling/v1',
			'create-api'=>'apis/autoscaling/v1'
		],
		'service'=>[
			'type' => 'services',
			'array' => 'ServiceList',
			'api' =>'api',
			'create-api'=>'api'
		],
		'route'=>[
			'type' => 'routes',
			'array' => 'RouteList',
			'api' =>'apis/route.openshift.io/v1',
			'create-api'=>'oapi'
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
		$basicPage->writeLog($api_url.$command.($subcommand?' : '.$subcommand:''),'Input: <div style="display: none;">'.$data.'</div><br>Response: <div style="display: none;">'.$this->prettyPrint(json_encode($this->response)).'<div>');
	}
	
function prettyPrint( $json )
{
    $result = '';
    $level = 0;
    $in_quotes = false;
    $in_escape = false;
    $ends_line_level = NULL;
    $json_length = strlen( $json );

    for( $i = 0; $i < $json_length; $i++ ) {
        $char = $json[$i];
        $new_line_level = NULL;
        $post = "";
        if( $ends_line_level !== NULL ) {
            $new_line_level = $ends_line_level;
            $ends_line_level = NULL;
        }
        if ( $in_escape ) {
            $in_escape = false;
        } else if( $char === '"' ) {
            $in_quotes = !$in_quotes;
        } else if( ! $in_quotes ) {
            switch( $char ) {
                case '}': case ']':
                    $level--;
                    $ends_line_level = NULL;
                    $new_line_level = $level;
                    break;

                case '{': case '[':
                    $level++;
                case ',':
                    $ends_line_level = $level;
                    break;

                case ':':
                    $post = " ";
                    break;

                case " ": case "\t": case "\n": case "\r":
                    $char = "";
                    $ends_line_level = $new_line_level;
                    $new_line_level = NULL;
                    break;
            }
        } else if ( $char === '\\' ) {
            $in_escape = true;
        }
        if( $new_line_level !== NULL ) {
            $result .= "\n".str_repeat( "\t", $new_line_level );
        }
        $result .= $char.$post;
    }

    return $result;	
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
		
		if ($todo_types=='all') {$todo_types=['deploymentconfig','autoscaler','service','route'];}
		$persistent_storage=$db->selectOne('instellingen','instelling','var=\'persistent_storage\'');
		if ($persistent_storage) {$persistent_storage=$persistent_storage['instelling'];} else {$persistent_storage='ERROR-NO-PERSISTENT-STORAGE';}
		foreach ($todo_types as $todo_type) {
			$todo=$this->def[$todo_type];
			$jsonString = file_get_contents($basedir.'json-templates/'.$todo_type.'.json');
			// default replacements
			$jsonString = str_replace('$host',$_SERVER['HTTP_HOST'],$jsonString);
			$jsonString = str_replace('$namespace',$basicPage->namespace,$jsonString);
			$jsonString = str_replace('$storage',$persistent_storage,$jsonString);
			$jsonString = str_replace('$name','gpid-'.$id,$jsonString);
			// aanvullende replacements
			foreach ($variables as $variable=>$value) {
				$jsonString = str_replace('$'.$variable,$value,$jsonString);
			}
			$this->command($todo['create-api'],$todo['type'],$update?'PUT':'POST',$jsonString);
			if ($todo_type=='deploymentconfig') { // wacht tot deploymentconfig er is
				$maxAant=28; // wacht maximaal 28 seconden
				while ($maxAant>0) {
					$this->command($todo['api'],$todo['type'].'/gpid-'.$id);
global $basicPage;
$basicPage->writeLog('createDeploymentConfig '.$maxAant.' '.$this->response->kind);
					if ($this->response->kind=='DeploymentConfig') {
						$maxAant=0;
					} else {
						$maxAant--;
						// usleep(100000); // 100.000 microseconden is 0.1 seconde
						sleep(1); // 1 seconde
					}
				}
				//sleep(10);
			}
		}
	}
	function deleteDeploymentConfig($id,$todo_types=['replicationcontroller','autoscaler','deploymentconfig','pod','service','route']) {
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
		// Wacht tot alles echt verwijderd is
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