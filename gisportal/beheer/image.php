<?php
require('../basicPage.php');

$title='Beheer image';
$r='';
if ($loggedIn && $is_admin){
	if (isset($_GET['id'])) {
		$func=$_POST['func'];
		$id=$_GET['id'];
		$velden='id,image,repo,deflt';
		if ($id>=1) {
			$img=$db->selectOne('images',$velden,'id='.$id);
			$r='<h2>Beheer image</h2>';
		} else {
			$id=0;
			$img=array('id'=>0,'image'=>$_GET['iid']);
			$title='Nieuw image';
		}
		if ($func=='delete') {
			if ($img['id']>=1) {
				if ($img['deflt']!='J') {
					$db->delete('images','id='.$img['id']);
					$db->delete('versions','image='.$img['id']);
					$basicPage->redirect('/beheer/index.php?tab=3',false,'Verwijderen','Het image is verwijderd.');
				} else {
					$basicPage->fout('Verwijderen','De image kan niet worden verwijderd omdat het de default image is.');
				}
			} else {
				$basicPage->fout('Internal error','Image niet gevonden.');
			}
		} else {
			if ($img) {
				if ($func=='opslaan') {
					$a=array(
						'Qimage'=>$db->validateString($_POST['image'],'image',1,32,'Er is geen image opgegeven','Het image is te lang (max 32 tekens)',array('images','image=\''.$_POST['image'].'\' AND id<>'.$img['id'],'Dit image komt al in de database voor')),
						'Qrepo'=>$db->validateString($_POST['repo'],'repo',1,128,'Er is geen repo opgegeven','De repo is te lang (max 128 tekens)',array('images','repo=\''.$_POST['repo'].'\' AND id<>'.$img['id'],'Deze repo komt al in de database voor')),
						'Qdeflt'=>($_POST['deflt']=='J'?'J':'N'),
					);
					if ($a['Qdeflt']=='J') {
						$db->update('images',array('Qdeflt'=>'N'),'id<>'.$img['id']);
					} else {
						$d=$db->select('images','id','deflt=\'J\' AND id<>'.$img['id']);
						if (!$d) {
							$db->foutMeldingen[]=array('deflt','Deze image is de default. Kies een ander image en bewerk deze om dat image als default aan te merken. Dan verdwijt deze instelling.');
						}
					}
					if (!$db->foutMeldingen) {
						if ($img['id']==0) {
							$img['id']=$db->insert('images',$a);
							$db->insert('versions',array('Qversion'=>'v 1.0','image'=>$img['id'],'Qdeflt'=>'J'));
						} else {
							$db->update('images',$a,'id='.$img['id']);
						}
						$basicPage->redirect('/beheer/index.php?tab=3',false,'Opslaan','Het image is opgeslagen.');
					} else {
						$basicPage->add_js_inline('var foutmeldingen='.json_encode($db->foutMeldingen).';');
						// zorg dat de POST waarden weer worden getoond
						$img['image']=$a['Qimage'];
						$img['repo']=$a['Qrepo'];
						$img['deflt']=$a['Qdeflt'];
					}
				}
				$r.='<form id="form" method="POST"><input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="func" id="func"><table>';
				if ($id>=1) {
					$r.='<tr><td colspan="2" class="button-top"><a class="small-button" style="float: left;" href="/beheer/index.php?tab=3">Annuleren</a><a class="small-button" onclick="areYouSure(\'Verwijderen\',\'Dit image verwijderen?\',function () {$(\'#func\').val(\'delete\'); $(\'#form\').submit();});">Verwijderen</a></td></tr>';
				}
				$r.='<tr><td>Image:</td><td><input name="image" value="'.htmlspecialchars($img['image']).'" size="32"></td></tr>';
				$r.='<tr><td>Repo:</td><td><input name="repo" value="'.htmlspecialchars($img['repo']).'" size="64"></td></tr>';
				$r.='<tr><td></td><td><input id="deflt" name="deflt" value="J" type="checkbox"'.($img['deflt']=='J'?' checked="checked"':'').'><label for="deflt"> Default</label></td></tr>';
				$r.='<tr><td colspan="2" class="button-below"><button onclick="$(\'#func\').val(\'opslaan\'); $(\'#form\').submit();">Opslaan</button></td></tr>';
				$r.='</table></form>';
			} else {
				$basicPage->fout('Internal error','Image niet gevonden.');
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