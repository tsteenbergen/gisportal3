<?php
require('basicPage.php');

$title='Instellingen';
$r='';
if ($loggedIn && $is_admin) {





	$func=$_POST['func'];
	$instellingen=$db->select('instellingen','id,label,var,instelling','id>=1');
	if ($func=='opslaan') {
		foreach ($_POST as $key=>$post) {
			for ($t=0;$t<count($instellingen);$t++) {
				$instelling=$instellingen[$t];
				if ($instelling['var']==$key) {
					$db->update('instellingen',array('Qinstelling'=>$post),'id='.$instelling['id']);
				}
			}
		}
		$basicPage->redirect('admin-instellingen');
	}
	$r.='<div style="display: inline-block;"><form id="form" method="POST"><input type="hidden" name="func" id="func"><table>';
	foreach ($instellingen as $instelling) {
		$r.='<tr><td>'.$instelling['label'].':</td><td><input name="'.$instelling['var'].'" value="'.$instelling['instelling'].'" size="32"></td></tr>';
	}
	$r.='</table></form>';
	$r.='<div class="button-below"><button onclick="formOpslaan();">Opslaan</button></div>';
	$r.='</div>';
	
} else {
	$basicPage->fout('Autorisatie','Je hebt niet de juiste autorisatie.');
}

$basicPage->render($title,$r);
?>