var _charmiles = ',';	//separador de miles
var _chardecimal = '.';	//separador de la parte decimal

function obj(objeto){
	return document.getElementById(objeto);
}

function getIsNull(_val){
	return (_val!='null' && _val != undefined);
}

function setDivWindow(window,handle,center){
	var _obj=obj(window);
	if(_obj.style.visibility=='hidden'){
		_obj.style.visibility='visible';
		setWindow(window,handle,center);
	}
}

function close_window(window){
	var _obj=obj(window);
	$('#'+window).fadeOut('medium',function(){
		_obj.style.visibility='hidden';
		_obj.style.display='';
	});
}
//requiere domdrag:
function setWindow(window,handle,center,canceleffect){
	var center = center || false;
	var canceleffect = canceleffect || false;
	//alert(center);
	var manejador = obj(handle);
	var cuadro   = obj(window);
	if (center){
		setCenter(window,true);
	}
	Drag.init(manejador, cuadro);
	if(canceleffect==false){
		$('#'+window).hide();
		cuadro.style.visibility="";
		$('#'+window).fadeIn("slow");
	}
}
function setOpenWindow(window,handle,center,canceleffect){
	var center = center || false;
	var canceleffect = canceleffect || false;
	//alert(center);
	var manejador = obj(handle);
	var cuadro   = obj(window);
	//alert(cuadro.style.left);
	if (center && cuadro.style.left=='0px'){
		setCenter(window,true);
	}
	Drag.init(manejador, cuadro);
	if(canceleffect==false){
		//$('#'+window).hide();
		cuadro.style.visibility="visible";
		//$('#'+window).fadeIn("slow");
	}
}

//requiere domdrag:
function setWindowCenter(window,handle){
	//var center = center || false;
	//alert(center);
	var manejador = obj(handle);
	var cuadro   = obj(window);
	//if (center){
		setOriginalCenter(window,true);
	//}
	Drag.init(manejador, cuadro);
	$('#'+window).hide();
	cuadro.style.visibility="";
	$('#'+window).fadeIn("slow");
}

var altClass = 'impar';
var overClass = 'over';
var checkedClass = 'checked'

function setTableRows(tableid,toggled){
	var toggled = toggled || false;
	//$(document).ready(function() {
		//alert(window);
		$('#'+tableid+' tbody tr:even').addClass(altClass);
		var obj = $('#'+tableid+' tbody tr')
			.hover(
				function() {
					$(this).addClass(overClass);
				},
				function() {
					$(this).removeClass(overClass);
				}
			)
			if(toggled){
				obj.toggle(
				function() {
					$(this).addClass(checkedClass);
				},
				function() {
					$(this).removeClass(checkedClass);
				}
			)
			}
		//});


}

//centra cuadros en posicion absoluta
function setCenter(window, vcenter, nohcenter){
	var vcenter = vcenter || false;
	var nohcenter = nohcenter || false;
	var cuadro  = obj(window);
	var ancho = cuadro.offsetParent.clientWidth;//document.body.clientWidth;
	var anchoobjeto = cuadro.offsetWidth;
	if(!nohcenter){
		cuadro.style.left = ((ancho - anchoobjeto) / 2)+'px';
	}
	if(vcenter){
		var alto = cuadro.offsetParent.clientHeight;//document.body.clientHeight;
		var altoobjeto = cuadro.offsetHeight;
		var th=  ((alto - altoobjeto) / 3);
		if(th<0){
			th=20;
		}
		//alert("ori "+th+" "+getTop()+" alto:"+alto+" altoobj:"+altoobjeto+" bodych:"+window.innerHeight);
		//alert(th);
		th+=(getTop()/2);
		//alert(th);
		cuadro.style.top = th+'px';
	}
}

function getTop(){
//alert(document.body.scrollTop+' '+window.pageYOffset+ ' '+document.documentElement.scrollTop);
	var top = document.body.scrollTop || window.pageYOffset;
	top = top || document.documentElement.scrollTop;
	return top;
}

//centra cuadros en posicion absoluta
function setOriginalCenter(window, vcenter){
	var vcenter = vcenter || false;
	var cuadro  = obj(window);
	var ancho = document.body.clientWidth;
	var anchoobjeto = cuadro.offsetWidth;
	cuadro.style.left = ((ancho - anchoobjeto) / 2)+'px';
	if(vcenter){
		var alto = document.body.clientHeight;
		var altoobjeto = cuadro.offsetHeight;
		//cuadro.style.top = ((alto - altoobjeto) / 2)+'px';
		var th=  ((alto - altoobjeto) / 2);
		if(th<0){
			th=20;
		}
		//alert("1 "+th);
		th+=getTop();
		cuadro.style.top = th+'px';
	}
}
function setVCenter(window){
	var vcenter = vcenter || false;
	var cuadro  = obj(window);

		var alto = document.body.clientHeight;
		var altoobjeto = cuadro.offsetHeight;
		//cuadro.style.top = ((alto - altoobjeto) / 2)+'px';
		var th=  ((alto - altoobjeto) / 2);
		if(th<0){
			th=20;
		}
		//alert("2 "+th);
		th+=getTop();
		cuadro.style.top = th+'px';

}

function modalOpen (dialog) {
	dialog.overlay.fadeIn('slow');
	
		dialog.container.fadeIn('medium', function () {
			dialog.data.hide().slideDown('medium');	 
		});
	
}

function simplemodalclose (dialog) {
	dialog.data.fadeOut('medium', function () {
		dialog.container.fadeOut('medium');
		dialog.overlay.fadeOut('slow', function () {
			$.modal.close();
		});
	});
}
/*
function modalOpen (dialog) {
	dialog.overlay.fadeIn('medium', function () {
		dialog.container.fadeIn('medium', function () {
			dialog.data.hide().slideDown('medium');
		});
	});
}

function simplemodalclose (dialog) {
	dialog.data.fadeOut('medium', function () {
		dialog.container.hide('medium', function () {
			dialog.overlay.fadeOut('medium', function () {
				$.modal.close();
			});
		});
	});
}
*/
function modalWindow(idwin,_width,_height){
	var _height = _height || 400;
	var _width = _width || 600;
	
	$(idwin).modal({
	  overlayCss: {
		backgroundColor: '#000',
		cursor: 'wait'
	  },
	  containerCss: {
		height: _height,
		width: _width,
		backgroundColor: '#fff',
		border: '3px solid #ccc'
	  },
	 onOpen: modalOpen,
	  onClose: simplemodalclose

	});
}

function modalPersistWindow(idwin,_width,_height){
	var _height = _height || 400;
	var _width = _width || 600;
	
	$(idwin).modal({
	  overlayCss: {
		backgroundColor: '#000',
		cursor: 'wait'
	  },
	  containerCss: {
		height: _height,
		width: _width,
		backgroundColor: '#fff',
		border: '3px solid #ccc'
	  },
	  //onOpen: modalOpen,
	 // onClose: callbackclose,
	  persist:true
	  //,
	 // containerId: "loco",
	  //overlayId: "loco2"

	});
}

var _utf8c = {
'&aacute;':'\u00e1',
'&eacute;':'\u00e9',
'&iacute;':'\u00ed',
'&oacute;':'\u00f3',
'&uacute;':'\u00fa',
'&Aacute;':'\u00c1',
'&Eacute;':'\u00c9',
'&Iacute;':'\u00cd',
'&Oacute;':'\u00d3',
'&Uacute;':'\u00da',
'&ntilde;':'\u00f1',
'&Ntilde;':'\u00d1',
'&iquest;':'\u00bf'
};

function _alert(s){
	alert(_jsutf8(s));
}
function _confirm(s){
	return confirm(_jsutf8(s));
}
function _prompt(s,d){
	return prompt(_jsutf8(s),_jsutf8(d));
}

function _jsutf8(s){
	for (var c in _utf8c){
		while(s.indexOf(c)!=-1){
			s=s.replace(c,_utf8c[c]);
		}
		//alert(c+'  '+utf8c[c]);
	}
	return s;
}

function _formatNumber(num,prefix){
    prefix = prefix || '';
    num += '';
    var splitStr = num.split('.');//var splitStr = num.split('.');
    var splitLeft = splitStr[0];
    var splitRight = splitStr.length > 1 ? _chardecimal + splitStr[1] : '';//var splitRight = splitStr.length > 1 ? '.' + splitStr[1] : '';
    var regx = /(\d+)(\d{3})/;
    while (regx.test(splitLeft)) {
    splitLeft = splitLeft.replace(regx, '$1' + _charmiles + '$2');//splitLeft = splitLeft.replace(regx, '$1' + ',' + '$2');
    }
    return prefix + splitLeft + splitRight;
    }

function un_formatNumber(num) {
    return num.replace(/([^0-9\.\-])/g,'')*1;
}
function _toNumber(num,fix){
	if(fix==null){
		fix=2;
	}
	//alert(fix+' '+num.toFixed(0));
	var r;
	if(typeof(num)=='number'){
		r= _formatNumber(num.toFixed(fix));
	}else{
		r= (_formatNumber(parseNumber(num).toFixed(fix)));
		//return _formatNumber(parseNumber(num));
	}
	
	/*if (r=="0.00"){
		r="";
	}*/
	//if(isNaN(parseNumber(r))){
	//alert("Numero Incorrecto"+isNaN(r));
	//};
	return r;
}
function set_toNumber(cinput,fix){
//alert (parseNumber(cinput.value));
	if (cinput.value=='0')return;
	var nv=_toNumber(parseNumber(cinput.value),fix);
	if(nv=='0.00'){
		nv='';
	}
	cinput.value=nv;
}
function parseNumber(num){
	if (num==null){
		return 0;
	}
	var r;
	r = num.toString().replace(_charmiles,'');//r = num.toString().replace(',','');
	while(r.indexOf(_charmiles)!=-1){//while(r.indexOf(',')!=-1){
		r = r.replace(_charmiles,'');//r = r.replace(',','');
	}
	r=r.replace(_chardecimal,'.');
	r = parseFloat(r);
	if (isNaN(r)){
		return 0;
	}else{
		return r;
	}
}
function parseNumberstring(num){
	var r;
	r = num.toString().replace(_charmiles,'');//r = num.toString().replace(',','');
	//alert(r.indexOf(','));
	while(r.indexOf(_charmiles)!=-1){//while(r.indexOf(',')!=-1){
		r = r.replace(_charmiles,'');//r = r.replace(',','');
	}
	if (isNaN(r)){
		return 0;
	}else{
		return r;
	}
}


function inputInt(e,minus){
	var menos = minus || false;
	if(e==null){
		e=event;
	}
	if(e==null){
		e=window.event;
	}
	var tecla = (document.all) ? e.keyCode : e.which;
	//48=0,57=9, 45=menos
	if(tecla==8)return true;//backs
	if (tecla==45){
		if (!menos){
			return false;
		}
	}else if(tecla < 48 || tecla > 57){
		return false;//e.keyCode = 0;
	}
	return true;
}
function inputFloat(e,minus){
	var menos = minus || false;
	if(e==null){
		e=event;
	}
	if(e==null){
		e=window.event;
	}
	var tecla = (document.all) ? e.keyCode : e.which;
	//48=0,57=9, 45=menos
	if(tecla==8)return true;//backs
	if(tecla==_chardecimal.charCodeAt(0)) return true; //punto decimal
	if (tecla==45){
		if (!menos){
			return false;
		}
	}else if(tecla < 48 || tecla > 57){
		return false;//e.keyCode = 0;
	}
	return true;
}

function keyEvent(e,evento,key){
	var key = key || 13;
	if(e==null){
		e=event;
	}
	if(e==null){
		e=window.event;
	}
	var tecla = (document.all) ? e.keyCode : e.which;
	if(tecla==key){
		evento();
	}
	
}

function isArray(obj) {
	return !(obj.length==null);
}
//Verifica que el objeto sea un Array, de lo contrario lo fuerza a devolverse como un array bajo el indice 0 (cero)
function getForceArray(objeto){
	if(!isArray(objeto)){
			//lo convierte en un array de 1 sola dimensión
			var temp = objeto;
			objeto = new Array();
			objeto[0] = temp;
			
	}
	return objeto;
}

function tableRow(tbodyObj){
	this.content=document.getElementById(tbodyObj);
	this.trObj = document.createElement('tr');
	this.cells=new Array();
	this.content.appendChild(this.trObj);
	this.addTextCell= function (cellValue){
		var c= new tableCell(document.createTextNode(cellValue),this.trObj);
		this.cells[this.cells.length] = c;
		return c;
	}
	this.addCell= function (cellValue){
		cellValue = cellValue || null;
		var c= new tableCell(cellValue,this.trObj);
		this.cells[this.cells.length] = c;
		return c;
	}
	
	this.setAttribute=function(attr,val){
		this.trObj.setAttribute(attr,val);
	}
	
	this.$=this.trObj;	
	this.style=this.trObj.style;
	this.num;
}


function tableCell(cellValue,trObj){
	this.tdObj = document.createElement('td');
	if(cellValue!=null){
		this.tdObj.appendChild(cellValue);
	}
	trObj.appendChild(this.tdObj);
	
	this.setAttribute=function(attr,val){
		this.tdObj.setAttribute(attr,val);
	}
	this.setId=function(val){
		this.tdObj.setAttribute('id',val);
	}
	this.setClass=function(val){
		this.tdObj.className=val;
		//this.tdObj.setAttribute('class',val);
	}	
	this.$=this.tdObj;	
	this.style=this.tdObj.style;
}

function setPopup(url,marco,options){
	var opciones='';
	for(i in options){
		if(i=='center'){
			if(options[i]=='v' || options[i]=='both'){
				//alert(screen.height+' '+options.height);
				var y = (screen.height - options.height) / 2;
				opciones+='top='+y+',';
			}
			if(options[i]=='h' || options[i]=='both'){
				var x = (screen.width - options.width) / 2;
				opciones+='left='+x+',';
			}
		}else if(i=='dialog'){
			opciones+="toolbar=0,location=0,statusbar=0,menubar=0,";
			if(options[i]!=null){
				opciones+="scrollbars=0,";
			}else{
				opciones+="resizable=0,resizeable=0,scrollbars=yes,";
			}
		}else{
		
			opciones+=i+'='+options[i]+',';
		}
	}
		//alert(opciones);
	//opciones=opciones.substring(0,opciones.length-1);
	//alert(opciones);
	var r= window.open(url,marco,opciones);
	r.focus();
	return r;
}
function isIE(){
	return /msie/i.test(navigator.userAgent) && !/opera/i.test(navigator.userAgent);
}
function parsePaginator(_var){
	if(_var==null)return _var;
	return _var.toString().replace(/,/g,'{1}').replace(/=/g,'{2}');
}
function toPaginator(_jso){
	var ret='';
	for(var i in _jso){
		//alert();
		ret=ret+","+i+'='+parsePaginator(_jso[i]);
	}
	//alert('JS:'+ret);
	return ret.substring(1);
}
function dateDialog(funct){
	this.defDate= new Date();
	//this.varDate=vardate;
	this.defFunction=funct;
	this.showDateDialog= function(_obj){
		this.cita_calendar.create();
		this.cita_calendar.setDate(this.defDate);
		this.cita_calendar.showAtElement(_obj);
		this.cita_calendar.defObjectUser=this;
	};
	this.calendar_onselect= function(calendar,date){//DD-MM-AAAA
		if (calendar.dateClicked) {
			var dia=date.substr(0,2);
			var mes=parseFloat(date.substr(3,2))-1;
			var ano=date.substr(6,4);
			//this.varDate=date;
			//alert(this.varDate+' y '+datos.fecha);
			//alert(defFunction);
			calendar.defObjectUser.defFunction(date);
			//xajax_listar_citas(cita_date.page,cita_date.maxrows,cita_date.order,cita_date.ordertype,'lista_citas','fecha_cita='+date+',origen_cita='+cita_date.origen_cita);
			calendar.defObjectUser.defDate=new Date(ano,mes,dia);
			calendar.hide();
		}
	};
	this.calendar_onclose= function (calendar){
		calendar.hide();
	};
	this.cita_calendar= new Calendar(1,null,this.calendar_onselect,this.calendar_onclose);			
	this.cita_calendar.setDateFormat("%d-%m-%Y");
}
/*
optimizado: {layer:(string 'button' || null) [,layer:button]}, [true || false]
*/
function detectEditWindows(jsonWindows,useDisplay){
	var useDisplay= useDisplay || false;
	window.onbeforeunload=function(){
		for(var i in jsonWindows){
			var rest;
			if(useDisplay){
				rest= (obj(i).style.display!='none');
			}else{
				rest= (obj(i).style.visibility=='' || obj(i).style.visibility=='visible');
			}
			if(rest){
				if(jsonWindows[i]!=null){
					if(obj(jsonWindows[i]).disabled==false){
						return _jsutf8("No se guardar&aacute;n los datos Ingresados");
					}
				}else{
					return _jsutf8("Se Recomienda CANCELAR este Cuadro de mensaje\n\nDebe Cerrar la Ventana del ERP para efectuar las transacciones efectivamente.");
				}
			}
		}
	}
}
/*function detectEditWindowsDisplay(jsonWindows){
	window.onbeforeunload=function(){
		for(var i in jsonWindows){
			//alert(i+' ' +obj(jsonWindows[i]));
			alert(obj(i).style.visibility);
			if(obj(i).style.display==''){
				if(jsonWindows[i]!=null){
					if(obj(jsonWindows[i]).disabled==false){
						return _jsutf8("No se guardar&aacute;n los datos Ingresados");
					}
				}else{
					return _jsutf8("Se Recomienda CANCELAR este Cuadro de mensaje\n\nDebe Cerrar la Ventana para efectuar las transacciones efectivamente");
				}
			}
		}
	}
	//onbeforeunload="return detectEditMode('edit_window','guardar');"
}*/

function detectEditWindow(sWindow,sButton){
	var oed=obj(sButton);
	var wed=obj(sWindow);
	//alert(wed.style.visibility+' '+oed.disabled);
	if(wed.style.visibility=='' && oed.disabled==false){
		return _jsutf8("No se guardar&aacute;n los datos Ingresados");
	}
}
