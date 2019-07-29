
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
function areYouSure(title, meld, afterOk) {
    $('<div></div>').appendTo('body').html('<div>'+meld+'</div>').dialog({
        modal: true, title: title, zIndex: 10000, autoOpen: true,
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

function depententSelect(el,afhankelijk_van,data,default_waarde,index_blank) { // index_blank == false => geen lege regel
	$('#'+afhankelijk_van).on('change', function() {
		sepentenSelectSet(el,$('#'+afhankelijk_van).val(),data,default_waarde,index_blank);
	});
	sepentenSelectSet(el,$('#'+afhankelijk_van).val(),data,default_waarde,index_blank);
}
function sepentenSelectSet(el,waarde,data,default_waarde,index_blank) {
	var opts=(index_blank===false?'':'<option value="'+index_blank+'"></option>'), t;
	
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
	
	location.href='/geo-packages.php'+(s==''?'':'?')+s;
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
					url: "/fileupload.php",
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
			}
		});
		for (t=0;t<els.length;t++) {
			el=$(els[t]);
			el.attr('no',no).click(function(e) {
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
	$(el).find('.bar').removeClass('hidden').css('width',parseInt(progressPercent,10)+'%');
	$(el).find('.msg').removeClass('hidden').html(msg);
	if (progressPercent==100) {
		data=JSON.parse(data);
		$(el).find('.bar').addClass('hidden');
		if (!error) {$(el).find('.msg').html('').addClass('hidden');}
		var el=$($(el).parent()).find('[uploadFile]');
		console.log(data);
		switch (data.uploadtype) {
			case 'geo-package':
				$('#brongeopackage').val(data['filenaam']);
				$('#brongeopackage1').html(data['filenaam']);
				break;
			case 'sld':
				$('#opmaak-file').val(data['filenaam']);
				$('#opmaak1').html(data['filenaam']);
				break;
		}
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
			$('#'+el).html('Error');
		}          
	});
}

function monitorPod(id) {
	var counter=$('#counter'), c=parseInt(counter.attr('c'),10);
	c++; counter.attr('c',c); counter.html(c);
	podFunctions(false,'monitor',id);
	setTimeout(function(){monitorPod(id)},1000);
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
function podFunctions(knop,func,id) {
	if (knop) {
		$(knop).hide();
		$('#podfunc').html('Commando wordt uitgevoerd. Dit kan enige tijd in beslag nemen.');
	}
	$.ajax({
		url: './pod-functions.php?func='+func+'&id='+id,
		type: "GET",
		contentType: false,
		cache: false,
		processData:false,
		success: function(data) {
			data=JSON.parse(data);
			if (data.monitor===true) {
				data=JSON.parse(data.msg);
				$('#monitor').html(objectToString(data));
				$('#pod-name').html(data.metadata.name);
				$('#pod-phase').html(data.status.phase);
				var s='<table>',t,c;
				for (t=0;t<data.status.conditions.length;t++) {c=data.status.conditions[t]; s+='<tr><td>'+c.type+'</td><td>&nbsp;&nbsp;&nbsp;&nbsp;</td><td>'+c.status+'</td></tr>';}
				$('#pod-status').html(s+'</table>');
			} else {
				if (knop) {$(knop).show();}
				if (data.error) {
					$('#podfunc').html('Error:<br>'+e.responseText);
				} else {
					$('#podfunc').html('Succes:<br>'+data.msg);
				}
			}
		},
		error: function(e) {
			$('#podfunc').html('Error:<br>'+e.responseText);
			if (knop) {$(knop).show();}
		}          
	});
}