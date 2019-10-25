<?php
require('../basicPage.php');

$title='Beheer version';
$r='';
if ($loggedIn && $is_admin){
	if (isset($_GET['id'])) {
		$func=$_POST['func'];
		$id=$_GET['id'];
		$velden='id,version,extensions,deflt';
		if ($id>=1) {
			$ver=$db->selectOne('versions',$velden,'id='.$id);
			$r='<h2>Beheer version</h2>';
		} else {
			$id=0;
			$ver=array('id'=>0,'image'=>'');
			$title='Nieuwe versie';
		}
		if ($func=='delete') {
			if ($ver['id']>=1) {
				if ($ver['deflt']!='J') {
					$db->delete('versions','id='.$ver['id']);
					$basicPage->redirect('/geo/portal/beheer/index.php?tab=3',false,'Verwijderen','De versie is verwijderd.');
				} else {
					$basicPage->fout('Verwijderen','De versie kan niet worden verwijderd omdat het de default versie is.');
				}
			} else {
				$basicPage->fout('Internal error','Versie niet gevonden.');
			}
		} else {
			if ($ver) {
				if ($func=='opslaan') {
					$a=array(
						'Qversion'=>$db->validateString($_POST['version'],'version',1,32,'Er is geen versie opgegeven','Het versie is te lang (max 32 tekens)'),
						'Qextensions'=>$db->validateString($_POST['extensions'],'extensions',1,128,'Er zijn geen extensions opgegeven','De extensions zijn te lang (max 4096 tekens)'),
						'image'=>$_GET['iid'],
						'Qdeflt'=>($_POST['deflt']=='J'?'J':'N'),
					);
					if ($a['Qdeflt']=='J') {
						$db->update('versions',array('Qdeflt'=>'N'),'image='.$_GET['iid'].' AND id<>'.$ver['id']);
					} else {
						$d=$db->select('versions','id','image='.$_GET['iid'].' AND deflt=\'J\' AND id<>'.$ver['id']);
						if (!$d) {
							$db->foutMeldingen[]=array('deflt','Deze versie is de default. Kies een andere versie en bewerk deze om die versie als default aan te merken. Dan verdwijnt deze instelling.');
						}
					}
					if (!$db->foutMeldingen) {
						if ($ver['id']==0) {
							$ver['id']=$db->insert('versions',$a);
						} else {
							$db->update('versions',$a,'id='.$ver['id']);
						}
						$basicPage->redirect('/geo/portal/beheer/index.php?tab=3',false,'Opslaan','De versie is opgeslagen.');
					} else {
						$basicPage->add_js_inline('var foutmeldingen='.json_encode($db->foutMeldingen).';');
						// zorg dat de POST waarden weer worden getoond
						$ver['version']=$a['Qversion'];
						$ver['deflt']=$a['Qdeflt'];
					}
				}
				$r.='<form id="form" method="POST"><input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="func" id="func"><table>';
				if ($id>=1) {
					$r.='<tr><td colspan="2" class="button-top"><a class="small-button" style="float: left;" href="/geo/portal/beheer/index.php?tab=3">Annuleren</a><a class="small-button" onclick="areYouSure(\'Verwijderen\',\'Deze versie verwijderen?\',function () {$(\'#func\').val(\'delete\'); $(\'#form\').submit();});">Verwijderen</a></td></tr>';
				}
				$r.='<tr><td>Versie:</td><td><input name="version" value="'.htmlspecialchars($ver['version']).'" size="32"></td></tr>';
				$r.='<tr><td>Upload files:</td><td><textarea name="extensions" rows="6" cols="32">'.$ver['extensions'].'</textarea><br>Format per regel:&nbsp;&nbsp;&nbsp;ext[/ext[/ext...]] [O][K]<table><tr><td>ext/ext</td><td>&eequ;&eequ;n van deze extenties</td></tr><tr><td>O</td><td>Deze extentie is optioneel</td></tr><tr><td>K</td><td>Na upload wordt de KAARTNAAM voor de file gebruikt (de extentie blijft gelijk)</td></tr></table></td></tr>';
				$r.='<tr><td></td><td><input id="deflt" name="deflt" value="J" type="checkbox"'.($ver['deflt']=='J'?' checked="checked"':'').'><label for="deflt"> Default</label></td></tr>';
				$r.='<tr><td colspan="2" class="button-below"><button onclick="$(\'#func\').val(\'opslaan\'); $(\'#form\').submit();">Opslaan</button></td></tr>';
				$r.='</table></form>';
			} else {
				$basicPage->fout('Internal error','Versie niet gevonden.');
			}
		}
	} else {
		$basicPage->fout('Internal error','Er is geen ID opgegeven.');
	}
} else {
	$basicPage->fout('Autorisatie','Je hebt niet de juiste autorisatie.');
}

$basicPage->render($title,$r);
?>