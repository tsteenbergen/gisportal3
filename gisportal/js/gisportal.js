
function setMetadatalink(datarivmnl) {
	var nm=(datarivmnl?'datalink':'indatalink'),recs=(datarivmnl?datarecs:indatarecs),z=(datarivmnl?'data':'indata');
	var el=$('[name='+nm+']'), cur_value=el.val(), t, zoek=$('#zoek'+z).val().toLowerCase(), opts='<option></option>';

	if (zoek!='') {
		for (t=0;t<recs.length;t++) if (recs[t][0]==cur_value || recs[t][1].toLowerCase().indexOf(zoek)>=0) {
			opts+='<option value="'+recs[t][0]+'">'+recs[t][1]+'</option>';
        }
		el.html(opts).val(cur_value);
	}
}

function metadatalinkhelp(datarivmnl) {
	var meld, title, buts;
	
	meld='<div>De metadatalinks verwijzen naar een item op data.rivm.nl en indata.rivm.nl. Omdat daar veel items staan, is<br>het selecteren in de lijst lastig. Door een zoekterm te gebruiken, beperk je het aantal resultaten in de lijst.<br><br><b>Let op:</b> Als het gezochte item niet in de lijst staat omdat deze pas korte tijd op indata.rivm.nl<br>staat, klik dan op <a class="small-button" onclick="$(\'#func\').val(\'cache-legen\'); $(\'.ui-dialog-buttonset\').hide(); $(\'.ui-dialog-titlebar-close\').hide(); $(\'#form\').submit();">Cache legen</a>. Dan worden alle items opnieuw ingelezen.</div>';
	title='Metadata link';
	buts={
		Ok: function () {
			$(this).dialog("close");
		},
	};
	
    $('<div></div>').appendTo('body').html(meld).dialog({
        modal: true, title: title, zIndex: 10000, autoOpen: true,
        width: 'auto', resizable: false,
        buttons: buts,
        close: function (event, ui) {
            $(this).remove();
        }
    });
	$('.ui-icon.ui-icon-closethick').html('X');
}

function meldFormFouten() {
	if (typeof(foutmeldingen)!='undefined') {
		var t, el;
		for (t=0;t<foutmeldingen.length;t++) {
			el=$('[name='+foutmeldingen[t][0]+']');
			if (el.length==1) {
				el.after('<div class="veldFout">'+foutmeldingen[t][1]+'</div>');
			}
		}
	}
}
function formOpslaan() {
	$('#func').val('opslaan');
	$('#form').submit();
}
function formOnderwerpOpslaan() {
	if ($('#afk').val()==$('#afk_oud').val()) { // geen wijziging URL
		formOpslaan();
	} else {
		if ($('#areYouSureCheck').prop('checked')) { // wijziging URL is akkoord
			formOpslaan();
		} else {
			$('#areYouSure').show();
		}
	}
}
function areYouSure(title, meld, afterOk) {
    $('<div></div>').appendTo('body').html('<div>'+meld+'</div>').dialog({
        modal: true, title: title, zIndex: 10000,
        width: 'auto', resizable: false,
        buttons: {
            Ja: function () {
                afterOk();
                $(this).dialog("close");
            },
            Nee: function () {                                                                 
                $(this).dialog("close");
            }
        },
        close: function (event, ui) {
            $(this).remove();
        }
    });
	$('.ui-icon.ui-icon-closethick').html('X');
};

function depententSelect(el,afhankelijk_van,data,default_waarde,index_blank,txt_blank) { // index_blank == false => geen lege regel
	$('#'+afhankelijk_van).on('change', function() {
		sepentenSelectSet(el,$('#'+afhankelijk_van).val(),data,default_waarde,index_blank,txt_blank);
	});
	sepentenSelectSet(el,$('#'+afhankelijk_van).val(),data,default_waarde,index_blank,txt_blank);
}
function sepentenSelectSet(el,waarde,data,default_waarde,index_blank,txt_blank) {
	var opts=(index_blank===false?'':'<option value="'+index_blank+'">'+txt_blank+'</option>'), t;
	
	for (t=0;t<data.length;t++) {
		if (data[t][1]==waarde) {
			opts+='<option '+(data[t][0]==default_waarde?'selected="selected" ':'')+'value="'+data[t][0]+'">'+data[t][2]+'</option>';
		}
	}
	$('#'+el).html(opts);
}

function filter_geo_package_change() {
	var s='', v;

	v=$('#filter-afd').val();
	if (v>=1) {s+=(s==''?'':'&')+'afd='+v;}
	
	v=$('#filter-ond').val();
	if (v>=1) {s+=(s==''?'':'&')+'ond='+v;}
	
	v=$('#filter-naam').val();
	if (v!='') {s+=(s==''?'':'&')+'naam='+v;}
	
	location.href='/geo/portal/geo-packages.php'+(s==''?'':'?')+s;
}

function autoForm(form,start_enabled,form_edit,form_show,form_hide) {
	var edit=$(form_edit);
	
	autoForm_(form, start_enabled || typeof(foutmeldingen)!='undefined',form_edit,form_show,form_hide);
	edit.click(function(e) {
		if (edit.html()=='Annuleren') {
			autoForm_(form,false,form_edit,form_show,form_hide);
		} else {
			autoForm_(form,true,form_edit,form_show,form_hide);
		}
	});
}

function autoForm_(form,shw,form_edit,form_show,form_hide) {
	var els=$('#'+form).find('input,select,textarea'), el, t;
	var edit=$(form_edit);
	
	if (shw) {
		edit.html('Annuleren');
		$(form_show).each(function(t,el) {$(el).hide();});
		$(form_hide).each(function(t,el) {$(el).show();});
	} else {
		edit.html('Bewerken');
		$(form_show).each(function(t,el) {$(el).show();});
		$(form_hide).each(function(t,el) {$(el).hide();});
	}
	for (t=0;t<els.length;t++) {
		el=$(els[t]);
		if (el.attr('orgdisabled')=='disabled') {
			el.attr('disabled','disabled');
		} else {
			switch (el.attr('type')) {
				case 'checkbox':
					if (shw) {
						el.removeAttr('disabled');
					} else {
						el.attr('disabled','disabled');
					}
					break;
				default:
					if (shw) {
						el.removeAttr('disabled');
					} else {
						el.attr('disabled','disabled');
					}
					break;
			}
		}
	}
}


function initFileuploads() {
	var els,no=100;
	
	els=$('[uploadFile]');
	if (els.length>=1) {
		$('body').append('<form id="fileUploadForm" method="post" enctype="multipart/form-data" style="position: absolute; top: -1000;"><input id="extradata"><input id="fileupload_no"><input id="uploadfile" type="file" name="uploadfile" /></form>');
		$('#uploadfile').change(function(e) {
			var f=$('#uploadfile').val(), form_data=new FormData();
			form_data.append('uploadfile', document.getElementById('uploadfile').files[0]);
			form_data.append('extradata', $('#extradata').val());
			if (f!='') {
				$.ajax({
					url: "/geo/portal/fileupload.php",
					type: "POST",
					data:  form_data,
					context: document.getElementById('progress_'+$('#fileupload_no').val()),
					contentType: false,
					cache: false,
					processData:false,
					success: function(data) {
						if (data.indexOf('<b>Warning</b>')>=1) {
							data={msg:data,error:true};
						} else {
							data=JSON.parse(data);
						}
						fileuploadMessage(this,data['error'],data['msg'],100,JSON.stringify(data));
					},
					uploadProgress: function(event, position, total, percentComplete) {
						fileuploadMessage(this,false,'Uploading',percentComplete,false);
					},
					error: function(e) {
						fileuploadMessage(this,true,e.responseText,100,false);
					}          
				});
				fileuploadMessage(document.getElementById('progress_'+$('#fileupload_no').val()),false,'Uploading',0,false);
			}
		});
		for (t=0;t<els.length;t++) {
			el=$(els[t]);
			el.attr('no',no).click(function(e) {
				$(this).hide();
				$('#fileupload_no').val($(this).attr('no'));
				$('#extradata').val($(this).attr('uploadfile'));
				$('#uploadfile').click();
			});
			el.after('<div id="progress_'+no+'"><div class="bar hidden"></div><div class="msg"></div></div>');
			no++;
		}
	}
}
function fileuploadMessage(el,error,msg,progressPercent,data) {
	if (msg=='') {msg='File upload failed; Unknown error.';}
	$(el).find('.bar').removeClass('hidden').css('width',parseInt(progressPercent,10)+'%');
	$(el).find('.msg').removeClass('hidden').removeClass('error').addClass(error?'error':'noerror').addClass('spinner').html(msg);
	if (progressPercent==100) {
		data=JSON.parse(data);
		$(el).find('.bar').addClass('hidden');
		if (!error) {$(el).find('.msg').html('').addClass('hidden');}
		$(el).find('.msg').removeClass('spinner');
		var el=$($(el).parent()).find('[uploadFile]');
		if (!error) {
			if (typeof(data.tabel)!='undefined') {
				$('#filetabel').html(data.tabel);
			}
		}
		$('#uploadfile').val('');
		$('#uploadknop').show();
	}
}

function filterPersonen(elm) {
	var z=$(elm).val().toLowerCase();
	
	$('.TRpersoon').each(function(t,el) {
		el=$(el);
		var col,w,tds=el.children(),shw=(z=='');
		if (!shw) for (col=1;col<4;col++) {
			w=$(tds[col]).html().toLowerCase();
			if (w!='' && w.indexOf(z)>=0) {shw=true;}
		}
		if (shw) {el.show();} else {el.hide();}
	});
}


function add_mdata(el,src) {
	$.ajax({
		url: src,
		type: "GET",
		contentType: false,
		cache: false,
		processData:false,
		success: function(data) {
			var xmlParsed = $.parseXML(rawXML);
			var xmlDoc = $(xmlParsed).find('document');
			var xmlRow = xmlDoc.find('row');
			$(xmlRow).each(function() {
				for(var i=0; i < 3; i++) {
				  // find the Col + i and append it's text to the #xmlstuff div
				  $('#xmlstuff').append($(this).find('Col' + i).text()).append('<br/>');   
				}
			});
		},
		error: function(e) {
			$('#'+el).html('Error: Metadata cannot be read from \'+src+\'');
		}          
	});
}
function objectToString(o,pre) {
	var r='', elm, t, aant;
	if (typeof(pre)=='undefined') {pre='';}
	if (typeof(o)=='object') {
		if (pre!='') {r+='<br>';}
		t=0; aant=0; for (elm in o) {aant++;}
		for (elm in o) {
			r+=pre+elm+' => '+objectToString(o[elm],pre+'&nbsp;&nbsp;&nbsp;&nbsp;')+(t<aant-1?'<br>':'');
			t++;
		}
	} else {
		if (typeof(o)=='array') {
			if (pre!='') {r+='<br>';}
			for (t=0;t<o.length;t++) {
				r+=pre+t+': '+objectToString(o,pre+' ')+(t<o.length-1?'<br>':'');
			}
		} else {
			r+=o;
		}
	}
	return r;
}

function show_kaart(kaart,kaartnaam) {
	var el=$('#kaart');
	
	$.ajax({
		url: '/geo/'+kaart+'?SERVICE=WMS&VERSION=1.3.0&REQUEST=GetCapabilities',
		type: "GET",
		success: function(data) {
			var t, node, r;
			
			r='Succes: GetCapabilities geeft het volgende terug:';
			for (t=0;t<data.childNodes.length;t++) {
				node=data.childNodes[t];
				r+='<br>'+(t+1)+': '+node['localName'];
			}
			r+='<br><br>Kaart:<br><img style="border: solid 1px black;" src="/geo/'+kaart+'?SERVICE=WMS&VERSION=1.3.0&REQUEST=GetMap&BBOX=0,300000,300000,650000&SRS=EPSG:28992&WIDTH=250&HEIGHT=300&LAYERS='+kaartnaam+'&FORMAT=image/jpeg">';
			el.html(r);
			console.log(data);
		},
		error: function(e) {
			el.html('Fout: GetCapabilities geeft de volgende fout:<br><br>'+e.responseText);
		}          
	});
}

function admin_reset(func) {
	var aknop='';
	var form_data=new FormData();
	
	switch(func) {
		case 'controle':
			form_data.append('func', 'controle');
			form_data.append('thema', $('#sel_themas').val());
			form_data.append('kaart', $('#sel_kaarten').val());
			form_data.append('del_uploads', $('#del_uploads').prop('checked')?'Ja':'Nee');
			break;
		case 'uitvoeren':
			//form_data.append('func', 'uitvoeren');
			//form_data.append('thema', $('#sel_themas').val());
			//form_data.append('kaart', $('#sel_kaarten').val());
			//form_data.append('del_uploads', $('#del_uploads').prop('checked')?'Ja':'Nee');
			var reset_akkoord=$('#reset_akkoord').prop('checked')?'Ja':'Nee';
			//form_data.append('reset_akkoord', reset_akkoord);
			if (reset_akkoord!='Ja') {
				$('#jaditwilikerror').html('Geef je akkoord!').addClass('error');
				return;
			}
			$('.aknop').prop('disabled',true); // disable alle knoppen
			$('.aknop2').hide(); // verberg 'Stel filter opnieuw in' en 'Uitvoeren'
			$('.error').html();
			$('#stap2msg').html();
			$('#stap3msg').html();
			$('#jaditwilikerror').html('').removeClass('error');
			$('#stap2h2').html('3. Uitvoering');
			$('#reset_akkoord').prop('disabled',true);
			startGpidReset(0);
			return;
			break;
		default:
			$('.aknop2b').show(); // toon knop 'Uitvoeren'
			$('#reset_akkoord').prop('disabled',false);
			form_data.append('func', 'niets');
			break;
	}
	$('#stap2h2').html('2. Controle gevolgen');
	$('.aknop').prop('disabled',true);
	$('.error').html();
	$('#stap2msg').html();
	$('#stap3msg').html();
	$('#reset_akkoord').prop('checked',false);
	$('#jaditwilikerror').html('').removeClass('error');
	$.ajax({
		url: '/geo/portal/admin-reset.php',
		type: "POST",
		data:  form_data,
		contentType: false,
		cache: false,
		processData:false,
		success: function(data) {
			if (data.indexOf('<b>Warning</b>')>=1) {
				data={msg:data,error:true};
			} else {
				data=JSON.parse(data);
			}
			switch(func) {
				case 'controle':
					$('#stap1').hide();
					$('#stap2').show();
					$('#stap3').hide();
					$('#stap2msg').html(data['msg']);
					break;
				case 'uitvoeren':
					$('#stap1').hide();
					$('#stap2').hide();
					$('#stap3').show();
					$('#stap3msg').html(data['msg']);
					break;
				default:
					$('#stap1').show();
					$('#stap2').hide();
					$('#stap3').hide();
					break;
			}
			$('.aknop').prop('disabled',false);
		},
		error: function(e) {
			$('.error').html(e.responseText);
			$('.aknop').prop('disabled',false);
		}          
	});
}

function startGpidReset(no) {
	var form_data=new FormData(), el=$('#kaart'+no), id;
	
	if (el.length!=1) {
		$('.aknop').prop('disabled',false); // enable alle knoppen
		$('.aknop2a').show(); // toon 'Stel filter opnieuw in'
		return;
	}
	el.html('<img src="/geo/portal/css/progress20x20.gif">');
	id=el.attr('kaartid');
	form_data.append('func', 'uitvoeren');
	form_data.append('kaartid', id);
	form_data.append('del_uploads', $('#del_uploads').prop('checked')?'Ja':'Nee');
	$.ajax({
		url: '/geo/portal/admin-reset.php',
		type: "POST",
		data:  form_data,
		contentType: false,
		cache: false,
		processData:false,
		success: function(data) {
			console.log(data);
			if (data.indexOf('<b>Warning</b>')>=1) {
				data={msg:data,error:true};
			} else {
				data=JSON.parse(data);
			}
			if (data.error===false) {
				startGpidReset(no+1);
				el.html('Reset done');
			} else {
				$('.error').html(data.msg);
				$('.aknop').prop('disabled',false);
				el.html('Error; aborted');
			}
		},
		error: function(e) {
			$('.error').html(e.responseText);
			$('.aknop').prop('disabled',false);
			el.html('Error; aborted');
			console.log(e);
		}          
	});
}

function regel_kaart_url() {
	var thema=jQuery('#onderwerp').val(), kaart=jQuery('[name=kaartnaam]').val(), t, thema_url='';
	for (t=0;t<onderwerpen.length;t++) if (onderwerpen[t][0]==thema) {thema_url=onderwerpen[t][3]; t=onderwerpen.length;}
	thema=jQuery('#onderwerp').find('[value='+thema+']').html();
	if (thema_url!='' && kaart!='') {
		jQuery('#kaart-url').html(location.origin+'/geo/'+thema_url+'/'+kaart);
		jQuery('#kaart-url-knop').show();
	} else {
		jQuery('#kaart-url').html('');
		jQuery('#kaart-url-knop').hide();
	}
}

function copyTextToClipboard(text) {
  var textArea = document.createElement("textarea");
  textArea.style.position = 'fixed';
  textArea.style.top = 0;
  textArea.style.left = 0;
  textArea.style.width = '2em';
  textArea.style.height = '2em';
  textArea.style.padding = 0;
  textArea.style.border = 'none';
  textArea.style.outline = 'none';
  textArea.style.boxShadow = 'none';
  textArea.style.background = 'transparent';
  textArea.value = text;
  document.body.appendChild(textArea);
  textArea.focus();
  textArea.select();
  try {
    var successful = document.execCommand('copy');
    var msg = successful ? 'successful' : 'unsuccessful';
    console.log('Copying text command was ' + msg);
  } catch (err) {
    console.log('Oops, unable to copy');
  }
  document.body.removeChild(textArea);
}


function copyKaart() {
	regel_kaart_url();
	copyTextToClipboard(jQuery('#kaart-url').html());
}

function health_check(id) {
	var form_data=new FormData();

	jQuery('#health-check-knop').hide();
	jQuery('#health-check').html('');
	jQuery('#health-check-msg').html('Performing checks').removeClass('error').addClass('spinner');
	form_data.append('id', id);
	$.ajax({
		url: '/geo/portal/health-check.php',
		type: "POST",
		data:  form_data,
		contentType: false,
		cache: false,
		processData:false,
		success: function(data) {
			jQuery('#health-check-knop').show();
			jQuery('#health-check-msg').html('').removeClass('spinner');
			var tabel='<table>', k, kt=0, i, item, p, parm, pt;
			for (k in data) if (data.hasOwnProperty(k)) {
				if (kt>=1) {tabel+='<tr><td><&nbsp;</td></tr>';}
				tabel+='<tr><td colspan="3"><b>'+k+'</b></td></tr><tr><td colspan="3" class="'+(data[k]['error']?'waarde-rood':(data[k]['error']?'':''))+'">'+data[k]['msg']+'</td></tr>';
				for (i=0;i<data[k]['items'].length; i++) {
					item=data[k]['items'][i];
					pt=0;
					for (p in item['parms']) if (item['parms'].hasOwnProperty(p)) {
						tabel+='<tr><td>'+(pt==0?item['name']:'')+'</td><td>'+p+'</td><td>'+item['parms'][p]+'</td></tr>';
						pt++;
					}
				}
				kt++;
			}
			jQuery('#health-check').html(tabel+'</table>');
		},
		error: function(e) {
			$('.error').html(e.responseText);
			jQuery('#health-check-knop').show();
			jQuery('#health-check-msg').html('<b>Error:</b> '+e.responseText).removeClass('spinner').addClass('error');
		}          
	});
}
