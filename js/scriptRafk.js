// JavaScript Document

// VARIABLE DE SESSION
var popupSession = null;

function byId(id) {
	return document.getElementById(id);
}

function byName(name) {
	return document.getElementsByName(name);
}

function byClass(name) {
	return document.getElementsByClass(name);
}

function mueveReloj() {
    momentoActual = new Date();
	
    hora = momentoActual.getHours();
    minuto = momentoActual.getMinutes();
    segundo = momentoActual.getSeconds();
	
	tiempo = "a.m."
	if (parseInt(hora) == 0) {
		hora = 12;
	} else if (parseInt(hora) > 12) {
		hora = hora - 12;
		tiempo = "p.m."
	}
	
	if (parseInt(minuto) >= 0 && parseInt(minuto) <= 9)
		minuto = "0" + minuto;
		
	if (parseInt(segundo) >= 0 && parseInt(segundo) <= 9)
		segundo = "0" + segundo;

    horaImprimible = hora + ":" + minuto + ":" + segundo + " " + tiempo;

    document.getElementById('tdHoraSistema').innerHTML = horaImprimible

    setTimeout("mueveReloj()",1000)
}

// SELECCIONA UNA OPCION DEL OBJETO SELECT
function selectedOption(idLst, value) {
	var lista = document.getElementById(idLst);
	for (i = 0; i <= lista.options.length; i++){
		if (lista.options[i] != null) {
			if (lista.options[i].value == value || lista.options[i].text == value){
				lista.options[i].selected = true;
				break;
			}
		}
	}
}

// VERIFICA SI EL VALOR EXISTE EN EL ARREGLO
function inArray(needle, haystack) {
    var length = haystack.length;
    for (var i = 0; i < length; i++) {
        if (haystack[i] == needle) return true;
    }
    return false;
}

// SELECCIONA TODOS LOS OBJETOS INDICADOS
function selecAllChecks(chkVal, idVal, form) {
	var frm = document.forms[form];
	for (i = 0; i < frm.length; i++){
		if ((frm.elements[i].id == idVal || frm.elements[i].id.match(idVal + '*')) && frm.elements[i].readOnly == false){
			if (chkVal == true && frm.elements[i].disabled == false){
				frm.elements[i].checked = true;
			} else {
				frm.elements[i].checked = false;
			}
		}
	}
}

// DEVUELVE LOS VALORES SELECCIONADOS DE UN OBJETO SELECT MULTIPLE
function getSelectValues(select) {
	var result = [];
	var options = select && select.options;
	var opt;
	
	for (var i = 0, iLen = options.length; i < iLen; i++) {
		opt = options[i];
		
		if (opt.selected) {
			result.push(opt.value || opt.text);
		}
	}
	
	return result;
}

// SELECCIONA UNA POSICION ESPECIFICA EN EL OBJETO INPUT
function sel(idObjeto, inicio, fin){
	input = document.getElementById(idObjeto);
	if (typeof document.selection != 'undefined' && document.selection) {
		tex = input.value;
		input.value = '';
		input.focus();
		
		var str = document.selection.createRange();
		input.value = tex;
		str.move('character', inicio);
		str.moveEnd("character", fin-inicio);
		str.select();
	} else if (typeof input.selectionStart != 'undefined') {
		input.setSelectionRange(inicio,fin);
		input.focus();
	}
}

// DEVUELVE LA POSICION DEL CURSOS EN EL OBJETO INPUT
function devPos(idObjeto){
	input = document.getElementById(idObjeto);
	if (typeof document.selection != 'undefined' && document.selection && typeof input.selectionStart == 'undefined') {
		var str = document.selection.createRange();
		stored_range = str.duplicate();
		stored_range.moveToElementText(input);
		stored_range.setEndPoint('EndToEnd', str );
		input.selectionStart = stored_range.text.length - str.text.length;
		input.selectionEnd = input.selectionStart + str.text.length;
		return input.selectionStart;
	} else if (typeof input.selectionStart != 'undefined') {
		return input.selectionStart;
	}
}

function color2gray(src, habilitado) {
	var canvas = document.createElement('canvas');
	var ctx = canvas.getContext('2d');
	var imgObj = new Image();
	imgObj.src = src;
	canvas.width = imgObj.width;
	canvas.height = imgObj.height;
	ctx.drawImage(imgObj, 0, 0);
	
	var imgPixels = ctx.getImageData(0, 0, canvas.width, canvas.height);
	for (var y = 0; y < imgPixels.height; y++) {
		for (var x = 0; x < imgPixels.width; x++) {
			var i = (y * 4) * imgPixels.width + x * 4;
			var avg = (imgPixels.data[i] + imgPixels.data[i + 1] + imgPixels.data[i + 2]) / 3;
			imgPixels.data[i] = avg; 
			imgPixels.data[i + 1] = avg; 
			imgPixels.data[i + 2] = avg;
		}
	}
	ctx.putImageData(imgPixels, 0, 0, 0, 0, imgPixels.width, imgPixels.height);
	
	if (habilitado == true) {
		return src;
	} else {
		return canvas.toDataURL();
	}
}

if (eval((typeof('formatNumberRafk') != "undefined")) && typeof('formatNumberRafk') != "function" && !(window.formatNumberRafk)) {
	function formatNumberRafk(num,prefix) {
		prefix = prefix || '';
		num += '';
		var splitStr = num.split('.');
		var splitLeft = splitStr[0];
		var splitRight = splitStr.length > 1 ? '.' + splitStr[1] : '';
		var regx = /(\d+)(\d{3})/;
		while (regx.test(splitLeft)) {
			splitLeft = splitLeft.replace(regx, '$1' + ',' + '$2');
		}
		return prefix + splitLeft + splitRight;
	}
}

if (eval((typeof('formatoRafk') != "undefined")) && typeof('formatoRafk') != "function" && !(window.formatoRafk)) {
	function formatoRafk(num, decimales) {
		var r;
		if (typeof(num) == 'number') {
			r = formatNumberRafk(num.toFixed(decimales));
		} else {
			r = (formatNumberRafk(parseNumRafk(num).toFixed(decimales)));
		}
		
		return r;
	}
}

if (eval((typeof('setFormatoRafk') != "undefined")) && typeof('setFormatoRafk') != "function" && !(window.setFormatoRafk)) {
	function setFormatoRafk(cinput, decimales) {
		cinput.value = formatoRafk(parseNumRafk(cinput.value), decimales);
	}
}
	
if (eval((typeof('parseNumRafk') != "undefined")) && typeof('parseNumRafk') != "function" && !(window.parseNumRafk)) {
	function parseNumRafk(num) {
		var r;
		r = num.toString().replace(',',''); // replace(/,/g, '')
		while (r.indexOf(',') != -1) {
			r = r.replace(',','');
		}
		
		r = parseFloat(r);
		if (isNaN(r)) {
			return 0;
		} else {
			return r;
		}
	}
}

if (eval((typeof('parseNumRafkStringRafk') != "undefined")) && typeof('parseNumRafkStringRafk') != "function" && !(window.parseNumRafkStringRafk)) {
	function parseNumRafkStringRafk(num) {
		var r;
		r = num.toString().replace(',','');
		//alert(r.indexOf(','));
		while(r.indexOf(',') != -1) {
			r = r.replace(',','');
		}
		
		if (isNaN(r)) {
			return 0;
		} else {
			return r;
		}
	}
}
	
if (eval((typeof('unformatNumberRafk') != "undefined")) && typeof('unformatNumberRafk') != "function" && !(window.unformatNumberRafk)) {
	function unformatNumberRafk(num) {
		return num.replace(/([^0-9\.\-])/g,'')*1;
	}
}
	
if (eval((typeof('inputNumberFormatRafk') != "undefined")) && typeof('inputNumberFormatRafk') != "function" && !(window.inputNumberFormatRafk)) {
	function inputNumberFormatRafk(iobj) {
		iobj.value = formatNumberRafk(parseNumRafk(iobj.value).toFixed(3));
	}
}


/********** FUNCIONES DE MAYCOL **********/
if (eval((typeof('formatNumber') != "undefined")) && !typeof('formatNumber')) {
	function formatNumber(num, prefix) {
		prefix = prefix || '';
		num += '';
		var splitStr = num.split('.');
		var splitLeft = splitStr[0];
		var splitRight = splitStr.length > 1 ? '.' + splitStr[1] : '';
		var regx = /(\d+)(\d{3})/;
		while (regx.test(splitLeft)) {
			splitLeft = splitLeft.replace(regx, '$1' + ',' + '$2');
		}
		return prefix + splitLeft + splitRight;
	}
}

if (eval((typeof('formato') != "undefined")) && !typeof('formato')) {
	function formato(num) {
		var r;
		if (typeof(num) == 'number') {
			r = formatNumber(num.toFixed(3));
		} else {
			r = (formatNumber(parsenum(num).toFixed(3)));
		}
		
		return r;
	}
}

if (eval((typeof('setformato') != "undefined")) && !typeof('setformato')) {
	function setformato(cinput) {
		cinput.value = formato(parsenum(cinput.value));
	}
}
	
if (eval((typeof('parsenum') != "undefined")) && !typeof('parsenum')) {
	function parsenum(num) {
		if (num == null){
			return 0;
		}
		
		var r;
		r = num.toString().replace(_charmiles,'');//r = num.toString().replace(',','');
		while(r.indexOf(_charmiles) != -1){//while(r.indexOf(',')!=-1){
			r = r.replace(_charmiles,'');//r = r.replace(',','');
		}
		r = r.replace(_chardecimal,'.');
		
		r = parseFloat(r);
		if (isNaN(r)) {
			return 0;
		} else {
			return r;
		}
	}
}

if (eval((typeof('parsenumstring') != "undefined")) && !typeof('parsenumstring')) {
	function parsenumstring(num) {
		var r;
		r = num.toString().replace(',','');
		//alert(r.indexOf(','));
		while(r.indexOf(',') != -1) {
			r = r.replace(',','');
		}
		
		if (isNaN(r)) {
			return 0;
		} else {
			return r;
		}
	}
}
	
if (eval((typeof('unformatNumber') != "undefined")) && !typeof('unformatNumber')) {
	function unformatNumber(num) {
		return num.replace(/([^0-9\.\-])/g,'')*1;
	}
}
	
if (eval((typeof('inputnumberformat') != "undefined")) && !typeof('inputnumberformat')) {
	function inputnumberformat(iobj) {
		iobj.value = formatNumber(parsenum(iobj.value).toFixed(3));
	}
}

/********** FUNCIONES PARA LOS POPUPS **********/
function centraPopUp(w,h) {
	var w = w + 10;
	var h = h + 29;
	var ns4 = (document.layers) ? true : false;
	var ie4 = (document.all) ? true : false;

	if (ns4) { // Noteskapes
		window.outerWidth  = w;
		window.outerHeight = h;
		var Xcor = (screen.width - window.outerWidth) / 2 - 5;
		var Ycor = (screen.height - window.outerHeight) / 2 - 14;
	} else { //else if (ie4) // Explorando
		var Ycor = (screen.height - h) / 2 - 14;
		var Xcor = (screen.width - w) / 2 - 5;
	}
	
	return 'top='+Ycor+', left='+Xcor;
}

function verVentana(pagina, width, height) {
	posicion = ', '+centraPopUp(width,height);
	var opciones = "toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=no, width="+width+", height="+height+posicion;
	window.open(pagina,"",opciones);
}



function findAlto(obj) {
	var curheight = 0;
	if (obj.offsetParent) {
		while (obj.offsetParent) {
			curheight += obj.offsetHeight
			obj = obj.offsetParent;
		}
	} else if (obj.style.height)
		curheight += obj.style.height;
	else if (obj.h)
		curheight += obj.h;
	
	return curheight;
}

function findAncho(obj) {
	var curwidth = 0;
	if (obj.offsetParent) {
		while (obj.offsetParent) {
			curwidth += obj.offsetWidth
			obj = obj.offsetParent;
		}
	} else if (obj.style.width)
		curwidth += obj.style.width;
	else if (obj.w)
		curwidth += obj.w;
	
	return curwidth;
}

function findScreenAlto() {
	myHeight = 0;
	if ( typeof( window.innerWidth ) == 'number' ) {
		//Non-IE
		myHeight = window.innerHeight;
	} else if ( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
		//IE 6+ in 'standards compliant mode'
		myHeight = document.documentElement.clientHeight;
	} else if ( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
		//IE 4 compatible
		myHeight = document.body.clientHeight;
	}
	
	return myHeight;
}

function findScreenAncho() {
	var myWidth = 0;
	if ( typeof( window.innerWidth ) == 'number' ) {
		//Non-IE
		myWidth = window.innerWidth;
	} else if ( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
		//IE 6+ in 'standards compliant mode'
		myWidth = document.documentElement.clientWidth;
	} else if ( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
		//IE 4 compatible
		myWidth = document.body.clientWidth;
	}
	return myWidth;
}

function findScrollY() {
	var scrOfY = 0;
	if ( typeof(window.pageYOffset ) == 'number' ) {
		//Netscape compliant
		scrOfY = window.pageYOffset;
	} else if ( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
		//DOM compliant
		scrOfY = document.body.scrollTop;
	} else if ( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
		//IE6 standards compliant mode
		scrOfY = document.documentElement.scrollTop;
	}
	return scrOfY;
}

// -----------------------------------------------------------------------
// PARA CENTRAR UN DIV
// -----------------------------------------------------------------------
function centrarDiv(div) {
	var h  = findAlto(div);
	var w  = findAncho(div);
	var sh = findScreenAlto();
	var sw = findScreenAncho();
	var scy = findScrollY();
	var top = (sh - h) / 2;
	var left = (sw - w) / 2;
	
	//alert("h="+h+", sh="+sh+", scy="+scy+", +"+(parseInt(top)+scy));
	if (document.getElementById && !window.getComputedStyle){// DOM but not Mozilla
		if ((parseInt(top) + scy) < 15) {
			margerSuperior = "15px";
		} else {
			margerSuperior = (parseInt(top)+scy)+ "px";
		}
		
	} else {
		if (parseInt(top) < 15) {
			margerSuperior = "15px";
		} else {
			margerSuperior = (parseInt(top)+scy)+ "px";
		}
	}
	
	/*if (parseInt(margerSuperior) > (parseInt(sh) / 3)) {
		div.style.top = "50px";
	} else {*/
		div.style.top = margerSuperior;
	/*}
	
	if (parseInt(left) > (parseInt(sw) / 3)) {
		div.style.left = "100px";
	} else {*/
		div.style.left = parseInt(left)+ "px";
	//}
}

function centrarDivH(div) {
	var h  = findAlto(div);
	var w  = findAncho(div);
	var sh = findScreenAlto();
	var sw = findScreenAncho();
	var scy = findScrollY();
	var top = (sh - h) / 2;
	var left = (sw - w) / 2;
	
	//alert("h="+h+", sh="+sh+", scy="+scy+", +"+(parseInt(top)+scy));
	if (document.getElementById && !window.getComputedStyle){// DOM but not Mozilla
		if ((parseInt(top) + scy) < 15) {
			margerSuperior = "15px";
		} else {
			margerSuperior = (parseInt(top)+scy)+ "px";
		}
		
	} else {
		if (parseInt(top) < 15) {
			margerSuperior = "15px";
		} else {
			margerSuperior = (parseInt(top)+scy)+ "px";
		}
	}
	
	div.style.left = parseInt(left)+ "px";
}

function mensaje(padre,texto,tiempo){
	if ($chk($('msjMensaje0001msj'))) {
		fila = $('msjMensaje0001msj');
		padre = fila.parentNode;
		padre.removeChild(fila);
	}
	if ($chk($('msjError0001msj'))) {
		fila = $('msjError0001msj');
		padre = fila.parentNode;
		padre.removeChild(fila);
	}
	if ($chk($('msjAlerta0001msj'))) {
		fila = $('msjAlerta0001msj');
		padre = fila.parentNode;
		padre.removeChild(fila);
	}
	
	var content = new Element('div',{
		'id':'msjMensaje0001msj',
		'class':'divMsjInfo'
	}).setHTML("<table border='0' cellspacing='0' cellpadding='0' width='100%'><tr align='center' height='24'><td style='padding-left:5px; padding-right:5px' width='20'><img src='../img/iconos/tick.png'/></td><td>" + texto + "</td></tr></table>");
	
	content.injectInside(padre);
	
	if (tiempo > 0){
		setTimeout(function(){
			fila = $('msjMensaje0001msj');
			padre = fila.parentNode;
			padre.removeChild(fila);
		},tiempo);
	}
}

function mensajeJquery(padre, texto, tiempo) {
	if (byId('msjMensaje0001msj')) {
		fila = byId('msjMensaje0001msj');
		padre = fila.parentNode;
		padre.removeChild(fila);
	}
	if (byId('msjError0001msj')) {
		fila = byId('msjError0001msj');
		padre = fila.parentNode;
		padre.removeChild(fila);
	}
	if (byId('msjAlerta0001msj')) {
		fila = byId('msjAlerta0001msj');
		padre = fila.parentNode;
		padre.removeChild(fila);
	}
	
	$('#' + padre).prepend("<div id='msjError0001msj' class='divMsjInfo'><table border='0' cellspacing='0' cellpadding='0' width='100%'><tr align='center' height='24'><td style='padding-left:5px; padding-right:5px' width='20'><img src='../img/iconos/tick.png'/></td><td>" + texto + "</td></tr></table></div>");
	
	if (tiempo > 0) {
		setTimeout(function(){
			fila = byId('msjError0001msj');
			padre = fila.parentNode;
			padre.removeChild(fila);
		},tiempo);
	}
}

function error(padre,texto,tiempo){
	if ($chk($('msjMensaje0001msj'))) {
		fila = $('msjMensaje0001msj');
		padre = fila.parentNode;
		padre.removeChild(fila);
	}
	if ($chk($('msjError0001msj'))) {
		fila = $('msjError0001msj');
		padre = fila.parentNode;
		padre.removeChild(fila);
	}
	if ($chk($('msjAlerta0001msj'))) {
		fila = $('msjAlerta0001msj');
		padre = fila.parentNode;
		padre.removeChild(fila);
	}
	
	var content = new Element('div',{
		'id':'msjError0001msj',
		'class':'divMsjError'
	}).setHTML("<table border='0' cellspacing='0' cellpadding='0' width='100%'><tr align='center' height='24'><td style='padding-left:5px; padding-right:5px' width='20'><img src='../img/iconos/ico_fallido.gif'/></td><td>" + texto + "</td></tr></table>");
	
	content.injectInside(padre);
	
	if (tiempo > 0){
		setTimeout(function(){
			fila = $('msjError0001msj');
			padre = fila.parentNode;
			padre.removeChild(fila);
		},tiempo);
	}
}

function errorJquery(padre, texto, tiempo) {
	if (byId('msjMensaje0001msj')) {
		fila = byId('msjMensaje0001msj');
		padre = fila.parentNode;
		padre.removeChild(fila);
	}
	if (byId('msjError0001msj')) {
		fila = byId('msjError0001msj');
		padre = fila.parentNode;
		padre.removeChild(fila);
	}
	if (byId('msjAlerta0001msj')) {
		fila = byId('msjAlerta0001msj');
		padre = fila.parentNode;
		padre.removeChild(fila);
	}
	
	$('#' + padre).prepend("<div id='msjError0001msj' class='divMsjError'><table border='0' cellspacing='0' cellpadding='0' width='100%'><tr align='center' height='24'><td style='padding-left:5px; padding-right:5px' width='20'><img src='../img/iconos/ico_fallido.gif'/></td><td>" + texto + "</td></tr></table></div>");
	
	if (tiempo > 0) {
		setTimeout(function(){
			fila = byId('msjError0001msj');
			padre = fila.parentNode;
			padre.removeChild(fila);
		},tiempo);
	}
}

function alerta(padre,texto,tiempo){
	if ($chk($('msjMensaje0001msj'))) {
		fila = $('msjMensaje0001msj');
		padre = fila.parentNode;
		padre.removeChild(fila);
	}
	if ($chk($('msjError0001msj'))) {
		fila = $('msjError0001msj');
		padre = fila.parentNode;
		padre.removeChild(fila);
	}
	if ($chk($('msjAlerta0001msj'))) {
		fila = $('msjAlerta0001msj');
		padre = fila.parentNode;
		padre.removeChild(fila);
	}
	
	var content = new Element('div',{
		'id':'msjAlerta0001msj',
		'class':'divMsjAlerta'
	}).setHTML("<table border='0' cellspacing='0' cellpadding='0' width='100%'><tr align='center' height='24'><td style='padding-left:5px; padding-right:5px' width='20'><img src='../img/iconos/error.png'/></td><td>" + texto + "</td></tr></table>");
	
	content.injectInside(padre);
	
	if (tiempo > 0){
		setTimeout(function(){
			fila = $('msjAlerta0001msj');
			padre = fila.parentNode;
			padre.removeChild(fila);
		},tiempo);
	}
}

function alertaJquery(padre, texto, tiempo) {
	if (byId('msjMensaje0001msj')) {
		fila = byId('msjMensaje0001msj');
		padre = fila.parentNode;
		padre.removeChild(fila);
	}
	if (byId('msjError0001msj')) {
		fila = byId('msjError0001msj');
		padre = fila.parentNode;
		padre.removeChild(fila);
	}
	if (byId('msjAlerta0001msj')) {
		fila = byId('msjAlerta0001msj');
		padre = fila.parentNode;
		padre.removeChild(fila);
	}
	
	$('#' + padre).prepend("<div id='msjAlerta0001msj' class='divMsjAlerta'><table border='0' cellspacing='0' cellpadding='0' width='100%'><tr align='center' height='24'><td style='padding-left:5px; padding-right:5px' width='20'><img src='../img/iconos/error.png'/></td><td>" + texto + "</td></tr></table></div>");
	
	if (tiempo > 0) {
		setTimeout(function(){
			fila = byId('msjAlerta0001msj');
			padre = fila.parentNode;
			padre.removeChild(fila);
		},tiempo);
	}
}


// HIGHCHARTS
function highchartsGray() {
	Highcharts.theme = {
	   colors: ["#DDDF0D", "#7798BF", "#55BF3B", "#DF5353", "#aaeeee", "#ff0066", "#eeaaee", "#55BF3B", "#DF5353", "#7798BF", "#aaeeee"],
	   chart: {
		  backgroundColor: {
			 linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
			 stops: [
				[0, 'rgb(96, 96, 96)'],
				[1, 'rgb(16, 16, 16)']
			 ]
		  },
		  borderWidth: 0,
		  borderRadius: 15,
		  plotBackgroundColor: null,
		  plotShadow: false,
		  plotBorderWidth: 0
	   },
	   title: {
		  style: {
			 color: '#FFF',
			 font: '16px Lucida Grande, Lucida Sans Unicode, Verdana, Arial, Helvetica, sans-serif'
		  }
	   },
	   subtitle: {
		  style: {
			 color: '#DDD',
			 font: '12px Lucida Grande, Lucida Sans Unicode, Verdana, Arial, Helvetica, sans-serif'
		  }
	   },
	   xAxis: {
		  gridLineWidth: 0,
		  lineColor: '#999',
		  tickColor: '#999',
		  labels: {
			 style: {
				color: '#999',
				fontWeight: 'bold'
			 }
		  },
		  title: {
			 style: {
				color: '#AAA',
				font: 'bold 12px Lucida Grande, Lucida Sans Unicode, Verdana, Arial, Helvetica, sans-serif'
			 }
		  }
	   },
	   yAxis: {
		  alternateGridColor: null,
		  minorTickInterval: null,
		  gridLineColor: 'rgba(255, 255, 255, .1)',
		  minorGridLineColor: 'rgba(255,255,255,0.07)',
		  lineWidth: 0,
		  tickWidth: 0,
		  labels: {
			 style: {
				color: '#999',
				fontWeight: 'bold'
			 }
		  },
		  title: {
			 style: {
				color: '#AAA',
				font: 'bold 12px Lucida Grande, Lucida Sans Unicode, Verdana, Arial, Helvetica, sans-serif'
			 }
		  }
	   },
	   legend: {
		  itemStyle: {
			 color: '#CCC'
		  },
		  itemHoverStyle: {
			 color: '#FFF'
		  },
		  itemHiddenStyle: {
			 color: '#333'
		  }
	   },
	   labels: {
		  style: {
			 color: '#CCC'
		  }
	   },
	   tooltip: {
		  backgroundColor: {
			 linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
			 stops: [
				[0, 'rgba(96, 96, 96, .8)'],
				[1, 'rgba(16, 16, 16, .8)']
			 ]
		  },
		  borderWidth: 0,
		  style: {
			 color: '#FFF'
		  }
	   },
	
	
	   plotOptions: {
		  series: {
			 shadow: true
		  },
		  line: {
			 dataLabels: {
				color: '#CCC'
			 },
			 marker: {
				lineColor: '#333'
			 }
		  },
		  spline: {
			 marker: {
				lineColor: '#333'
			 }
		  },
		  scatter: {
			 marker: {
				lineColor: '#333'
			 }
		  },
		  candlestick: {
			 lineColor: 'white'
		  }
	   },
	
	   toolbar: {
		  itemStyle: {
			 color: '#CCC'
		  }
	   },
	
	   navigation: {
		  buttonOptions: {
			 symbolStroke: '#DDDDDD',
			 hoverSymbolStroke: '#FFFFFF',
			 theme: {
				fill: {
				   linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
				   stops: [
					  [0.4, '#606060'],
					  [0.6, '#333333']
				   ]
				},
				stroke: '#000000'
			 }
		  }
	   },
	
	   // scroll charts
	   rangeSelector: {
		  buttonTheme: {
			 fill: {
				linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
				stops: [
				   [0.4, '#888'],
				   [0.6, '#555']
				]
			 },
			 stroke: '#000000',
			 style: {
				color: '#CCC',
				fontWeight: 'bold'
			 },
			 states: {
				hover: {
				   fill: {
					  linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
					  stops: [
						 [0.4, '#BBB'],
						 [0.6, '#888']
					  ]
				   },
				   stroke: '#000000',
				   style: {
					  color: 'white'
				   }
				},
				select: {
				   fill: {
					  linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
					  stops: [
						 [0.1, '#000'],
						 [0.3, '#333']
					  ]
				   },
				   stroke: '#000000',
				   style: {
					  color: 'yellow'
				   }
				}
			 }
		  },
		  inputStyle: {
			 backgroundColor: '#333',
			 color: 'silver'
		  },
		  labelStyle: {
			 color: 'silver'
		  }
	   },
	
	   navigator: {
		  handles: {
			 backgroundColor: '#666',
			 borderColor: '#AAA'
		  },
		  outlineColor: '#CCC',
		  maskFill: 'rgba(16, 16, 16, 0.5)',
		  series: {
			 color: '#7798BF',
			 lineColor: '#A6C7ED'
		  }
	   },
	
	   scrollbar: {
		  barBackgroundColor: {
				linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
				stops: [
				   [0.4, '#888'],
				   [0.6, '#555']
				]
			 },
		  barBorderColor: '#CCC',
		  buttonArrowColor: '#CCC',
		  buttonBackgroundColor: {
				linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
				stops: [
				   [0.4, '#888'],
				   [0.6, '#555']
				]
			 },
		  buttonBorderColor: '#CCC',
		  rifleColor: '#FFF',
		  trackBackgroundColor: {
			 linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
			 stops: [
				[0, '#000'],
				[1, '#333']
			 ]
		  },
		  trackBorderColor: '#666'
	   },
	
	   // special colors for some of the demo examples
	   legendBackgroundColor: 'rgba(48, 48, 48, 0.8)',
	   legendBackgroundColorSolid: 'rgb(70, 70, 70)',
	   dataLabelsColor: '#444',
	   textColor: '#E0E0E0',
	   maskColor: 'rgba(255,255,255,0.3)'
	};
	
	// Apply the theme
	var highchartsOptions = Highcharts.setOptions(Highcharts.theme);
}

function highchartsDarkBlue() {
	Highcharts.theme = {
	   colors: ["#DDDF0D", "#55BF3B", "#DF5353", "#7798BF", "#aaeeee", "#ff0066", "#eeaaee", "#55BF3B", "#DF5353", "#7798BF", "#aaeeee"],
	   chart: {
		  backgroundColor: {
			 linearGradient: { x1: 0, y1: 0, x2: 1, y2: 1 },
			 stops: [
				[0, 'rgb(48, 48, 96)'],
				[1, 'rgb(0, 0, 0)']
			 ]
		  },
		  borderColor: '#000000',
		  borderWidth: 2,
		  className: 'dark-container',
		  plotBackgroundColor: 'rgba(255, 255, 255, .1)',
		  plotBorderColor: '#CCCCCC',
		  plotBorderWidth: 1
	   },
	   title: {
		  style: {
			 color: '#C0C0C0',
			 font: 'bold 16px \"Trebuchet MS\", Verdana, sans-serif'
		  }
	   },
	   subtitle: {
		  style: {
			 color: '#666666',
			 font: 'bold 12px \"Trebuchet MS\", Verdana, sans-serif'
		  }
	   },
	   xAxis: {
		  gridLineColor: '#333333',
		  gridLineWidth: 1,
		  labels: {
			 style: {
				color: '#A0A0A0'
			 }
		  },
		  lineColor: '#A0A0A0',
		  tickColor: '#A0A0A0',
		  title: {
			 style: {
				color: '#CCC',
				fontWeight: 'bold',
				fontSize: '12px',
				fontFamily: 'Trebuchet MS, Verdana, sans-serif'
	
			 }
		  }
	   },
	   yAxis: {
		  gridLineColor: '#333333',
		  labels: {
			 style: {
				color: '#A0A0A0'
			 }
		  },
		  lineColor: '#A0A0A0',
		  minorTickInterval: null,
		  tickColor: '#A0A0A0',
		  tickWidth: 1,
		  title: {
			 style: {
				color: '#CCC',
				fontWeight: 'bold',
				fontSize: '12px',
				fontFamily: 'Trebuchet MS, Verdana, sans-serif'
			 }
		  }
	   },
	   tooltip: {
		  backgroundColor: 'rgba(0, 0, 0, 0.75)',
		  style: {
			 color: '#F0F0F0'
		  }
	   },
	   toolbar: {
		  itemStyle: {
			 color: 'silver'
		  }
	   },
	   plotOptions: {
		  line: {
			 dataLabels: {
				color: '#CCC'
			 },
			 marker: {
				lineColor: '#333'
			 }
		  },
		  spline: {
			 marker: {
				lineColor: '#333'
			 }
		  },
		  scatter: {
			 marker: {
				lineColor: '#333'
			 }
		  },
		  candlestick: {
			 lineColor: 'white'
		  }
	   },
	   legend: {
		  itemStyle: {
			 font: '9pt Trebuchet MS, Verdana, sans-serif',
			 color: '#A0A0A0'
		  },
		  itemHoverStyle: {
			 color: '#FFF'
		  },
		  itemHiddenStyle: {
			 color: '#444'
		  }
	   },
	   credits: {
		  style: {
			 color: '#666'
		  }
	   },
	   labels: {
		  style: {
			 color: '#CCC'
		  }
	   },
	
	   navigation: {
		  buttonOptions: {
			 symbolStroke: '#DDDDDD',
			 hoverSymbolStroke: '#FFFFFF',
			 theme: {
				fill: {
				   linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
				   stops: [
					  [0.4, '#606060'],
					  [0.6, '#333333']
				   ]
				},
				stroke: '#000000'
			 }
		  }
	   },
	
	   // scroll charts
	   rangeSelector: {
		  buttonTheme: {
			 fill: {
				linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
				stops: [
				   [0.4, '#888'],
				   [0.6, '#555']
				]
			 },
			 stroke: '#000000',
			 style: {
				color: '#CCC',
				fontWeight: 'bold'
			 },
			 states: {
				hover: {
				   fill: {
					  linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
					  stops: [
						 [0.4, '#BBB'],
						 [0.6, '#888']
					  ]
				   },
				   stroke: '#000000',
				   style: {
					  color: 'white'
				   }
				},
				select: {
				   fill: {
					  linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
					  stops: [
						 [0.1, '#000'],
						 [0.3, '#333']
					  ]
				   },
				   stroke: '#000000',
				   style: {
					  color: 'yellow'
				   }
				}
			 }
		  },
		  inputStyle: {
			 backgroundColor: '#333',
			 color: 'silver'
		  },
		  labelStyle: {
			 color: 'silver'
		  }
	   },
	
	   navigator: {
		  handles: {
			 backgroundColor: '#666',
			 borderColor: '#AAA'
		  },
		  outlineColor: '#CCC',
		  maskFill: 'rgba(16, 16, 16, 0.5)',
		  series: {
			 color: '#7798BF',
			 lineColor: '#A6C7ED'
		  }
	   },
	
	   scrollbar: {
		  barBackgroundColor: {
				linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
				stops: [
				   [0.4, '#888'],
				   [0.6, '#555']
				]
			 },
		  barBorderColor: '#CCC',
		  buttonArrowColor: '#CCC',
		  buttonBackgroundColor: {
				linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
				stops: [
				   [0.4, '#888'],
				   [0.6, '#555']
				]
			 },
		  buttonBorderColor: '#CCC',
		  rifleColor: '#FFF',
		  trackBackgroundColor: {
			 linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
			 stops: [
				[0, '#000'],
				[1, '#333']
			 ]
		  },
		  trackBorderColor: '#666'
	   },
	
	   // special colors for some of the
	   legendBackgroundColor: 'rgba(0, 0, 0, 0.5)',
	   legendBackgroundColorSolid: 'rgb(35, 35, 70)',
	   dataLabelsColor: '#444',
	   textColor: '#C0C0C0',
	   maskColor: 'rgba(255,255,255,0.3)'
	};
	
	// Apply the theme
	var highchartsOptions = Highcharts.setOptions(Highcharts.theme);
}

function highchartsDarkGreen() {
	Highcharts.theme = {
	   colors: ["#DDDF0D", "#55BF3B", "#DF5353", "#7798BF", "#aaeeee", "#ff0066", "#eeaaee",
		  "#55BF3B", "#DF5353", "#7798BF", "#aaeeee"],
	   chart: {
		  backgroundColor: {
			 linearGradient: [0, 0, 250, 500],
			 stops: [
				[0, 'rgb(48, 96, 48)'],
				[1, 'rgb(0, 0, 0)']
			 ]
		  },
		  borderColor: '#000000',
		  borderWidth: 2,
		  className: 'dark-container',
		  plotBackgroundColor: 'rgba(255, 255, 255, .1)',
		  plotBorderColor: '#CCCCCC',
		  plotBorderWidth: 1
	   },
	   title: {
		  style: {
			 color: '#C0C0C0',
			 font: 'bold 16px "Trebuchet MS", Verdana, sans-serif'
		  }
	   },
	   subtitle: {
		  style: {
			 color: '#666666',
			 font: 'bold 12px "Trebuchet MS", Verdana, sans-serif'
		  }
	   },
	   xAxis: {
		  gridLineColor: '#333333',
		  gridLineWidth: 1,
		  labels: {
			 style: {
				color: '#A0A0A0'
			 }
		  },
		  lineColor: '#A0A0A0',
		  tickColor: '#A0A0A0',
		  title: {
			 style: {
				color: '#CCC',
				fontWeight: 'bold',
				fontSize: '12px',
				fontFamily: 'Trebuchet MS, Verdana, sans-serif'
	
			 }
		  }
	   },
	   yAxis: {
		  gridLineColor: '#333333',
		  labels: {
			 style: {
				color: '#A0A0A0'
			 }
		  },
		  lineColor: '#A0A0A0',
		  minorTickInterval: null,
		  tickColor: '#A0A0A0',
		  tickWidth: 1,
		  title: {
			 style: {
				color: '#CCC',
				fontWeight: 'bold',
				fontSize: '12px',
				fontFamily: 'Trebuchet MS, Verdana, sans-serif'
			 }
		  }
	   },
	   tooltip: {
		  backgroundColor: 'rgba(0, 0, 0, 0.75)',
		  style: {
			 color: '#F0F0F0'
		  }
	   },
	   toolbar: {
		  itemStyle: {
			 color: 'silver'
		  }
	   },
	   plotOptions: {
		  line: {
			 dataLabels: {
				color: '#CCC'
			 },
			 marker: {
				lineColor: '#333'
			 }
		  },
		  spline: {
			 marker: {
				lineColor: '#333'
			 }
		  },
		  scatter: {
			 marker: {
				lineColor: '#333'
			 }
		  },
		  candlestick: {
			 lineColor: 'white'
		  }
	   },
	   legend: {
		  itemStyle: {
			 font: '9pt Trebuchet MS, Verdana, sans-serif',
			 color: '#A0A0A0'
		  },
		  itemHoverStyle: {
			 color: '#FFF'
		  },
		  itemHiddenStyle: {
			 color: '#444'
		  }
	   },
	   credits: {
		  style: {
			 color: '#666'
		  }
	   },
	   labels: {
		  style: {
			 color: '#CCC'
		  }
	   },
	
	
	   navigation: {
		  buttonOptions: {
			 symbolStroke: '#DDDDDD',
			 hoverSymbolStroke: '#FFFFFF',
			 theme: {
				fill: {
				   linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
				   stops: [
					  [0.4, '#606060'],
					  [0.6, '#333333']
				   ]
				},
				stroke: '#000000'
			 }
		  }
	   },
	
	   // scroll charts
	   rangeSelector: {
		  buttonTheme: {
			 fill: {
				linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
				stops: [
				   [0.4, '#888'],
				   [0.6, '#555']
				]
			 },
			 stroke: '#000000',
			 style: {
				color: '#CCC',
				fontWeight: 'bold'
			 },
			 states: {
				hover: {
				   fill: {
					  linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
					  stops: [
						 [0.4, '#BBB'],
						 [0.6, '#888']
					  ]
				   },
				   stroke: '#000000',
				   style: {
					  color: 'white'
				   }
				},
				select: {
				   fill: {
					  linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
					  stops: [
						 [0.1, '#000'],
						 [0.3, '#333']
					  ]
				   },
				   stroke: '#000000',
				   style: {
					  color: 'yellow'
				   }
				}
			 }
		  },
		  inputStyle: {
			 backgroundColor: '#333',
			 color: 'silver'
		  },
		  labelStyle: {
			 color: 'silver'
		  }
	   },
	
	   navigator: {
		  handles: {
			 backgroundColor: '#666',
			 borderColor: '#AAA'
		  },
		  outlineColor: '#CCC',
		  maskFill: 'rgba(16, 16, 16, 0.5)',
		  series: {
			 color: '#7798BF',
			 lineColor: '#A6C7ED'
		  }
	   },
	
	   scrollbar: {
		  barBackgroundColor: {
				linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
				stops: [
				   [0.4, '#888'],
				   [0.6, '#555']
				]
			 },
		  barBorderColor: '#CCC',
		  buttonArrowColor: '#CCC',
		  buttonBackgroundColor: {
				linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
				stops: [
				   [0.4, '#888'],
				   [0.6, '#555']
				]
			 },
		  buttonBorderColor: '#CCC',
		  rifleColor: '#FFF',
		  trackBackgroundColor: {
			 linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
			 stops: [
				[0, '#000'],
				[1, '#333']
			 ]
		  },
		  trackBorderColor: '#666'
	   },
	
	   // special colors for some of the
	   legendBackgroundColor: 'rgba(0, 0, 0, 0.5)',
	   legendBackgroundColorSolid: 'rgb(35, 35, 70)',
	   dataLabelsColor: '#444',
	   textColor: '#C0C0C0',
	   maskColor: 'rgba(255,255,255,0.3)'
	};
	
	// Apply the theme
	var highchartsOptions = Highcharts.setOptions(Highcharts.theme);
}

function verificarPopUpSession(URL) {
	
		// SI EL POPUP YA EXISTE LO CERRAMOS
		if(popupSession!=null){
			popupSession.focus();
			return;
		}
		
		//alert("cargando nuevo popup");
		
		// CAPTURAMOS LAS DIMENSIONES DE LA PANTALLA PARA CENTRAR EL POPUP
		altoPantalla = parseInt(screen.availHeight);
		anchoPantalla = parseInt(screen.availWidth);
		
		// CALCULAMOS EL CENTRO DE LA PANTALLA
		centroAncho = parseInt((anchoPantalla/2))
		centroAlto = parseInt((altoPantalla/2))
	
		// DIMENSIONES DEL POPUP
		anchoPopup = 240;
		altoPopup = 145;
	
		// CALCULAMOS LAS COORDENADAS DE COLOCACIÓN DEL POPUP
		laXPopup = centroAncho - parseInt((anchoPopup/2))
		laYPopup = centroAlto - parseInt((altoPopup/2))
				
		popupSession = window.open(URL,"Session","scrollbars=yes,status=no,width=" + anchoPopup + ", height=" + altoPopup + ",left = " + laXPopup + ",top = " + laYPopup);

}