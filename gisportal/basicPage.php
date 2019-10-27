<?php
session_start();
date_default_timezone_set('Europe/Amsterdam');

class db_ {
	var $dbhost = '';
	var $dbport='';
	var $dbuser = '';
	var $dbname = '';
	var $dbpassword = '';

 	var $tablePrefix = '';
	var $mysqli;

	var $foutMeldingen=false;

	function __destruct() {
		if ($this->mysqli) {$this->mysqli->close();}
	}
	function __construct() {
		$dbhost=getenv('MYSQL_SERVICE_HOST');
		$dbport=getenv('MYSQL_SERVICE_PORT');
		$dbname=getenv('databasename');
		$dbuser=getenv('databaseuser');
		$dbpassword=getenv('databasepassword');
		if ($dbhost!='' && $dbname!='' && $dbuser!='' && $dbpassword!='') {
			$this->dbhost = $dbhost;
			$this->dbport = $dbport;
			$this->dbname = $dbname;
			$this->dbuser = $dbuser;
			$this->dbpassword = $dbpassword;
			$this->mysqli = mysqli_connect($this->dbhost, $this->dbuser, $this->dbpassword, $this->dbname, (int)$this->dbport);
		} else {
			$this->dbname='gisportal';
			$this->dbhost = 'localhost';
			$this->dbuser = 'root';
			$this->dbpassword = 'usbw';
			$this->mysqli = new mysqli($this->dbhost, $this->dbuser, $this->dbpassword);
		}
		if (mysqli_connect_errno()) {
		  $this->mysqli=false;
		  die('Database connect failed: '.mysqli_connect_error().'<br>$dbhost: '.$dbhost.'<br>$dbname: '.$dbname.'<br>$dbuser: '.$dbuser.'<br>$dbpassword: '.$dbpassword.'<br>$this->host: '.$this->dbhost.'<br>$this->port: '.$this->dbport.'<br>$this->dbname: '.$this->dbname.'<br>$this->dbuser: '.$this->dbuser.'<br>$this->dbpassword: '.$this->dbpassword);
		} else {
			$this->query('USE '.$this->dbname);
		}
	}
  
	// $fields is associatief array, string- en datumvelden beginnen met een Q
	// return: 0 (failure) of id (succes)
	function insert($table, $fields) {
		global $loggedIn;
		
		$result=-1;
		if ($loggedIn) {
			$q='INSERT INTO '.$table.' (';
			$keys=array_keys($fields); $f=''; $v='';
			foreach ($keys as $key) {
				if (substr($key,0,1)=='Q') {$key1=substr($key,1); $va='\''.addslashes($fields[$key]).'\'';} else {$key1=$key; $va=$fields[$key];}
				$f.=($f==''?'':',').$key1;
				$v.=($v==''?'':',').$va;
			}
			$q.=$f.') VALUES ('.$v.')';
			$this->mysqli->query('INSERT INTO audit_trail (persoon, query) VALUES ('.$_SESSION['user'].',\''.addslashes($q).'\')');
			$result = $this->mysqli->query($q);
			if ($result) {
				$result=$this->mysqli->insert_id;
			} else {
				$result=0;
			}
		}
		return $result;
	}

	// $fields is associatief array, string- en datumvelden beginnen met een Q
	// return: 0 (failure) of id (succes)
	function update($table, $fields, $where,$loginRequired=true) {
		global $loggedIn;
		
		$result=false;
		if ($loggedIn || $loginRequired===false) {
			$q='UPDATE '.$table.' SET';
			$keys=array_keys($fields);
			$t=0;
			foreach ($keys as $key) {
				if (substr($key,0,1)=='Q') {$key1=substr($key,1); $va='\''.addslashes($fields[$key]).'\'';} else {$key1=$key; $va=$fields[$key];}
				$q.=($t>=1?',':'').' '.$key1.'='.$va;
				$t++;
			}
			$q.=' WHERE '.$where;
			$this->mysqli->query('INSERT INTO audit_trail (persoon, query) VALUES ('.$_SESSION['user'].',\''.addslashes($q).'\')');
			$result = $this->mysqli->query($q);
			if ($result) {
				$result=true;
			}
		}
		return $result;
	}
  
	function affected_rows() {
		return $this->mysqli->affected_rows;
	}

	function delete($table, $where) {
		global $loggedIn;
		
		$result=false;
		if ($loggedIn) {
			$q='DELETE FROM '.$table.' WHERE '.$where;
			$this->mysqli->query('INSERT INTO audit_trail (persoon, query) VALUES ('.$_SESSION['user'].',\''.addslashes($q).'\')');
			$this->mysqli->query($q);
			if ($this->mysqli->affected_rows>=1) {
				$result=true;
			}
		}
		return $result;
	}

	function query($sql,$singleAnswer=false,$loginRequired=true) {
		global $loggedIn;
		
		$r=false;
		if ($loggedIn || $loginRequired===false) {
			$result = $this->mysqli->query($sql);
			if ($result && $result!==true) {
				if ($singleAnswer) {
					if ($result->fetch_assoc) {
						$r=$result->fetch_assoc();
					} else {
						$r=true;
					}
				} else {
					$r=array();
					while ($row=$result->fetch_assoc()) {
						$r[]=$row;
					}
					$result->free();
				}
			}
		}
		return $r;
	}

  // $tables is een tabel, of een join-clause (inclusief de ON ...)
  // $fields is comma-seperated string
  // return: false of associatief array of array van assosiatieve arrays
	function &selectOne($tables, $fields, $where) {
		return $this->select($tables, $fields, $where, '', false);
	}
	
	function &select($tables, $fields, $where='', $order='', $multiple_answers=true) {
		$q='SELECT '.$fields.' FROM '.$tables.($where==''?'':' WHERE '.$where);
		if ($order!='') {$q.=' ORDER BY '.$order;}
		$result = $this->mysqli->query($q);
		if ($result) {
			if ($multiple_answers) {
				$r=array();
				while ($row=$result->fetch_assoc()) {
					$r[]=$row;
				}
			} else {
				$r=$result->fetch_assoc();
			}
			$result->free();
			return $r;
		} else {
			return false;
		}
	}

	function newPassword() {
		$alfabet='abcdefghijklmnopqrstuvwxyz'; $cijfers='0123456789';
		$pw=substr($alfabet,rand(0,25),1).substr($alfabet,rand(0,25),1).substr($alfabet,rand(0,25),1).substr($cijfers,rand(0,9),1).substr($cijfers,rand(0,9),1).substr($alfabet,rand(0,25),1).substr($alfabet,rand(0,25),1).substr($alfabet,rand(0,25),1);
		return $pw;
	}
  
	function validateStart() {
		$this->foutMeldingen=array();
		$id=intval($_POST['id']);
		return $id;
	}
  
	function validateString($s, $veld, $mi, $ma,$meldKort,$meldLang,$unique=false) {
		if ($unique && !($s=='' && $mi==0)) {
			$u=$this->selectOne($unique[0],'id',$unique[1]);
			if ($u) {$this->foutMeldingen[]=array($veld,$unique[2]);}
		}
		$s=trim($s);
		$l=strlen($s);
		if ($l<$mi) {$this->foutMeldingen[]=array($veld,$meldKort);}
		if ($l>$ma) {$this->foutMeldingen[]=array($veld,$meldLang);}
		return $s;
	}
	
	function validateCheckbox($s) {
		return ($s=='on' || $s=='true' || $s===true?'J':'N');
	}
  
	function validateInt($s, $veld) {
		$s=trim($s);
		return $s;
	}
}

$db = new db_();

$username=$_SESSION['username'];
$loggedIn=($username!='');
$loggedInViaWindowsUser=false;
if (!$loggedIn) {  // login obv windows-user
	$u='';
	if (isset($_SERVER['LOGON_USER'])) {$u=$_SERVER['LOGON_USER'];}
	if (isset($_SERVER['AUTH_USER'])) {$u=$_SERVER['AUTH_USER'];}
	if (isset($_SERVER['REDIRECT_LOGON_USER'])) {$u=$_SERVER['REDIRECT_LOGON_USER'];}
	if (isset($_SERVER['REDIRECT_AUTH_USER'])) {$u=$_SERVER['REDIRECT_AUTH_USER'];}
	if ($u!='') {
		$user=$db->selectOne('personen','id,naam,afdeling,password,afd_admin,admin','ad_account=\''.$u.'\'');
		if ($user) { // Ook in login.php
			$username=$u;
			$_SESSION['username'] = $username;
			$_SESSION['user'] = $user['id'];
			$_SESSION['afdeling'] = $user['afdeling'];
			$_SESSION['is_admin'] = ($user['admin']=='J');
			$_SESSION['is_afd_admin'] = ($user['afd_admin']=='J');
			$loggedInViaWindowsUser=true;
		}
	}
}
$is_afd_admin=$_SESSION['is_afd_admin'];
$is_admin=$_SESSION['is_admin'];
$my_afd=$_SESSION['afdeling'];

class basicPage {
	var $js_ready='';
	var $js_inline='';
	var $fouten=[];
	var $meldingen=[];
	var $appname;
	var $namespace;
	var $otap;
	var $endpoint;
	
	function __construct() {
		if (isset($_SESSION['fouten'])) {$this->fouten=$_SESSION['fouten'];}
		if (isset($_SESSION['meldingen'])) {$this->meldingen=$_SESSION['meldingen'];}
		unset($_SESSION['fouten']);
		unset($_SESSION['meldingen']);
		if (file_exists('/geo-mappen/endpoint.php')) {
			eval(file_get_contents('/geo-mappen/endpoint.php'));
			$this->appname=$appname;
			$this->namespace=$namespace;
			$this->otap=$otap;
			$this->endpoint=$endpoint;
		} else {
			$elements=explode('.',$_SERVER['HTTP_HOST']);
			if ($elements[1]=='apps' && $elements[2]=='ssc-campus' && $elements[3]=='nl') {
				$this->namespace='sscc-geoweb-';
				$pos=stripos($elements[0],'-'.$namespace);
				if ($pos>=1) {
					$this->appname=substr($elements[0],0,$pos);
					$this->namespace.=substr($elements[0],$pos+strlen($this->namespace)+1,2);
					$this->otap=substr($this->namespace,-1);
					$this->endpoint='https://portaal.int.ssc-campus.nl:8443';
				}
			} else { // localhost
				$elements0=explode('-',$elements[0]);
				if (count($elements0)==2) {
					$this->appname=$elements0[0];
					$this->namespace=$elements0[1];
					$this->otap='o';
					$this->endpoint='https://192.168.99.100:8443';
					$adr=explode('.',$_SERVER['SERVER_NAME']); // => 'gisportal-proj3.192.168.99.112.nip.io',
					if ($adr[1]==192 && $adr[2]==168) {
						$this->endpoint='https://192.168.'.$adr[3].'.'.$adr[4].':8443';
					}
				}
			}
			@file_put_contents('/geo-mappen/endpoint.php','$endpoint=\''.$this->endpoint.'\'; $appname=\''.$this->appname.'\'; $namespace=\''.$this->namespace.'\'; $otap=\''.$this->otap.'\';');
		}
	}
	
	function add_js_inline($js) {
		$js=trim($js);
		if (substr($js,-1)!=';') {$js.=';';}
		$this->js_inline.=$js;
	}
	function add_js_ready($js) {
		$js=trim($js);
		if (substr($js,-1)!=';') {$js.=';';}
		$this->js_ready.=$js;
	}
	function getMenu() {
		global $loggedIn;
		global $is_afd_admin;
		global $is_admin;
		
		$r.='<a href="/geo/portal/index.php" class="mainmenu-item">Home</a>';
		if ($loggedIn) {
			$r.='<a href="/geo/portal/geo-packages.php" class="mainmenu-item">GEO-packages</a>';
			if ($is_admin || $is_afd_admin) {
				$r.='<a href="/geo/portal/beheer/index.php" class="mainmenu-item">Beheer</a>';
				if ($is_admin) {
					$r.='<a href="/geo/portal/admin.php" class="mainmenu-item">Admin functions</a>';
				}
			}
		}
		return $r;
	}
	function fout($onderwerp,$melding) {
		$this->fouten[]=[$onderwerp,$melding];
	}
	function meld($onderwerp,$melding) {
		$this->meldingen[]=[$onderwerp,$melding];
	}
	function getSelect($name, $v, $lijst, $disabled=false, $onchange='') {
        $r='<select id="'.$name.'" name="'.$name.'"'.($disabled?' disabled="disabled"':'').($onchange==''?'':' onchange="'.$onchange.'"').'>';
        $opt_list=''; $replOpt=''; $firstOpt='';
    	foreach ($lijst as $opt) {
			$p=stripos($opt,'='); if ($p===false) {$opt_display=$opt;} else {$opt_display=substr($opt,$p+1); $opt=substr($opt,0,$p);}
			if ($v==$opt) {
				$r.='<option selected="selected" value="'.$opt.'">'.$opt_display.'</option>';
			} else {
				$r.='<option value="'.$opt.'">'.$opt_display.'</option>';
			}
		}
		$r.='</select>';
		return $r;
	}
	function checkbox($id,$checked,$label) {
		return '<input type="checkbox" id="'.$id.'" name="'.$id.'"'.($checked?' checked="checked"':'').'><label for="'.$id.'">'.$label.'</label>';
	}
	function redirect($toPage,$error=false,$onderwerp='',$melding='') {
		if ($onderwerp!='' || $melding!='') {
			if ($error) {$this->fout($onderwerp,$melding);} else {$this->meld($onderwerp,$melding);}
		}
		$_SESSION['fouten']=$this->fouten;
		$_SESSION['meldingen']=$this->meldingen;
		header('Location: '.$toPage);
		exit();
	}
	function getConfig($name) {
		switch($name) {
			case 'geo-mappen': return '/geo-mappen'; break; 						 // De root, waarin alle geo-packages worden opgeslagen
			case 'logfile': // De path/filename van de logfile waarin alle API-calls worden geschreven
				if ($_SESSION['user']>=1) {
					$r='/geo-mappen/api_command_'.$_SESSION['user'].'.html';
				} else {
					$r='/geo-mappen/api_command_1.html';
				}
				return $r;
				break;
		}
	}
	function writeLog($msg,$submsg='',$truncate=false) {
		if ($_SESSION['is_admin']) {
			$logfile=$this->getConfig('logfile');
			$log='<table>';
			if (!$truncate && file_exists($logfile)) {$log=file_get_contents($logfile); $log=substr($log,0,strlen($log)-8);}
			$log.='<tr><td>'.date('j-n-y H:i:s').'</td><td>&nbsp;</td><td>'.$msg.'</td></tr>';
			if ($submsg!='') {$log.='<tr><td></td><td>&nbsp;</td><td>'.$submsg.'</td></tr>';}
			file_put_contents($logfile,$log.'</table>');
		}
	}
	function render($titel,$content) {
		global $loggedIn;
		
		$r='<html>';
		$r.='<head>';
		$r.='<script type="text/javascript" src="/geo/portal/js/jquery-1.10.2.min.js"></script>';
		$r.='<script type="text/javascript" src="/geo/portal/js/jquery-ui-1.9.2.custom.min.js"></script>';
		$r.='<script type="text/javascript" src="/geo/portal/js/gisportal.js"></script>';
		$r.='<link href="/geo/portal/css/gisportal.css" type="text/css" rel="stylesheet">';
		$r.='<link href="/geo/portal/css/jquery.ui.dialog.css" type="text/css" rel="stylesheet">';
		$r.='<link href="/geo/portal/css/jquery.ui.tabs.css" type="text/css" rel="stylesheet">';
		$r.='</head>';
		$r.='<script type="text/javascript">'.$this->js_inline;
		$r.='$(document).ready(function() {meldFormFouten();initFileuploads();'.$this->js_ready.'});';
		$r.='</script>';
		$r.='<body>';
		$r.='<div class="max-width logo"><img alt="Logo SSC-Campus" src="/geo/portal/css/beeldmerk-rijksoverheid.png"></div>';
		$r.='<div class="menu1"><div class="max-width menu1_"><div class="menu1-title">GIS portaal</div>'.($loggedIn?'<a href="/geo/portal/mijn.php" class="inuitloggen">Mijn &hellip;</a>'.($loggedInViaWindowsUser?'':'<a href="/geo/portal/logout.php" class="inuitloggen">Uitloggen</a>'):'<a href="/geo/portal/login.php" class="inuitloggen">Inloggen</a>').'</div></div>';
		$r.='<div class="menu2"><div class="max-width menu2_">'.$this->getMenu().'</div></div>';
		$r.='<div class="max-width"><div class="content">';
		foreach ($this->meldingen as $melding) {
			$r.='<div class="melding"><div class="melding-onderwerp">'.$melding[0].'</div><div class="melding-melding">'.$melding[1].'</div></div>';
		}
		foreach ($this->fouten as $fout) {
			$r.='<div class="fout"><div class="fout-onderwerp">'.$fout[0].'</div><div class="fout-melding">'.$fout[1].'</div></div>';
		}
		$r.='<h1>'.$titel.'</h1>'.$content.'</div></div>';
		$r.='<div class="footer"><div class="max-width">GIS portaal, in beheer bij RIVM/RDG</div></div>';
//$r.='<div>'.var_export($_SERVER,true).'</div>';
		$r.='</body>';
		$r.='</html>';
		echo($r);
	}
}
$basicPage=new basicPage();

set_error_handler(function($errno, $errstr) {
	global $basicPage;
	
	$basicPage->writeLog('PHP error '.$errno.': '.$errstr);
});
?>