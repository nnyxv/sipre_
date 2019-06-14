/*
ACTUALIZACION: compatibilidad de formatos de moneda
*/

var _charmiles = ',';	//separador de miles
var _chardecimal = '.';	//separador de la parte decimal

/*
si usa coma:
	"flotante":/^-?[\d]+\,\d+$/,
	"numero":/^-?[\d]+(\,\d+)?$/,
*/

var patrones = {
	"email":/^[A-Za-z][A-Za-z0-9_]*@[A-Za-z0-9_]+\.[A-Za-z0-9_.]+[A-za-z]$/,
	"cedula":/^[VvEe]\d+$/,
	"texto":/^[^\d]+$/,
	"telefono":/^[\d|\-|(|)| ]*$/,
	"telefonoeu":/\d{4}\-\d{3}-\d{4}/,
	"entero":/^-?[\d]*$/,
	"flotante":/^-?[\d]+\.\d+$/,
	"floate":/^-?(\d{1,3})?(,?\d{1,3})+(\.\d+)?$/,
	"float":/^-?(\d{1,3})+((,\d{3})+)?(\.\d+)?$/,	
	"numero":/^-?[\d]+(\.\d+)?$/,
	"rif":/^([VEJGvejg][-]\d{8}[-][0-9])$/,
	"nit":/^-?[\d]*$/
};

/*var utf8c = new Array(
	new Array('á','\u00e1'),
	new Array('é','\u00e9'),
	new Array('í','\u00ed'),
	new Array('ó','\u00f3'),
	new Array('ú','\u00fa'),
	new Array('Á','\u00c1'),
	new Array('É','\u00c9'),
	new Array('Í','\u00cd'),
	new Array('Ó','\u00d3'),
	new Array('Ú','\u00da'),
	new Array('ñ','\u00f1'),
	new Array('Ñ','\u00d1'),
	new Array('¿','\u00bf')
);*/

var utf8c = {
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

	
function setestaticpopup(url,marco,_w,_h){
	var x = (screen.width - _w) / 2;
	var y = (screen.height - _h) / 2;
	var r= window.open(url,marco,"toolbar=0,scrollbars=no,location=0,statusbar=0,menubar=0,resizable=0,width="+_w+",height="+_h+",top="+y+",left="+x+"");
	r.focus();
	return r;
}

function setpopup(url,marco,_w,_h){
	var x = (screen.width - _w) / 2;
	var y = (screen.height - _h) / 2;
	var r= window.open(url,marco,"toolbar=0,scrollbars=yes,location=0,statusbar=0,menubar=0,resizable=0,width="+_w+",height="+_h+",top="+y+",left="+x+"");
	r.focus();
	return r;
}

function utf8alert(s){
	alert(jsutf8(s));
}

function utf8confirm(s){
	return confirm(jsutf8(s));
}

function jsutf8(s){
	for (var c in utf8c){
		while(s.indexOf(c)!=-1){
			s = s.replace(c,utf8c[c]);
		}
		//alert(c+'  '+utf8c[c]);
	}
	return s;
}

function pvalidar(campo,tipo,nombre,requerido){
	var noreq= requerido || false;
	var n = nombre || campo;
	var obj=document.getElementById(campo);
	if (!noreq){
		if (obj.value==""){
			if(obj.readOnly){
				obj.style.background="#FFAFAF";
			}
			utf8alert("Ingrese "+n);
			obj.focus();
			return false;
		}
	}
	if (tipo!="" && obj.value!=""){
		var patron = patrones[tipo];
		if(!patron.test(obj.value)){
				utf8alert(n+": no es V&aacute;lido");
				obj.focus();
				return false;
		}
	}
	return true;
}

function formatNumber(num,prefix){
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

function unformatNumber(num) {
    return num.replace(/([^0-9\.\-])/g,'')*1;
}

function formato(num, decimales){
	decimales = (decimales == undefined) ? 2 : decimales;
	var r;
	if (typeof(num) == 'number'){
		r = formatNumber(num.toFixed(decimales));
	} else {
		r = (formatNumber(parsenum(num).toFixed(decimales)));
		//return formatNumber(parsenum(num));
	}
	
	return r;
}

function setformato(cinput){
	cinput.value=formato(parsenum(cinput.value));
}

function parsenum(num){
	if (num == null) {
		return 0;
	}
	
	var r;
	r = num.toString().replace(_charmiles,'');//r = num.toString().replace(',','');
	while (r.indexOf(_charmiles) != -1) {//while(r.indexOf(',')!=-1){
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

function parsenumstring(num){
	var r;
	r = num.toString().replace(_charmiles,'');//r = num.toString().replace(',','');
	//alert(r.indexOf(','));
	while(r.indexOf(_charmiles)!=-1){//while(r.indexOf(',')!=-1){
		r = r.replace(_charmiles,'');//r = r.replace(',','');
	}
	if (isNaN(r)){
		return 0;
	} else {
		return r;
	}
}

function inputnumberformat(iobj){
	iobj.value = formatNumber(parsenum(iobj.value).toFixed(2));
}

//opcionales:
function getOffsetTop(el) { // IMPORTANTE
	var ot = el.offsetTop;
	while ( ( el = el.offsetParent ) != null )
	{
		ot += el.offsetTop;
	}
	return ot;
}

function getOffsetLeft(el) { // IMPORTANTE
	var ot = el.offsetLeft;
	while ( ( el = el.offsetParent ) != null )
	{
		ot += el.offsetLeft;
	}
	return ot;
}

function objeto(obj){
	if (typeof(obj) == 'string'){
		return document.getElementById(obj);
	} else {
		return obj;
	}
}

var threadtt = [];
function tooltip(campo, tool, time) { // IMPORTANTE
	return;//poster
	var c = objeto(campo);
	var t = objeto(tool);
	t.style.top = getOffsetTop(c)-t.offsetHeight-4+"px";
	t.style.left = getOffsetLeft(c)+c.offsetWidth+"px";
	//t.style.visibility="visible";
	alphashow(tool,9,1);
	
	//threadtt=
	threadtt[tool]=setTimeout("closetooltip('"+tool+"');",time*1000);
}

function helptip(campo,tool) { // IMPORTANTE
	return;//poster
	var c = objeto(campo);
	var t = objeto(tool);
	t.style.top=getOffsetTop(c)+"px";
	t.style.left=getOffsetLeft(c)+c.offsetWidth+2+"px";
	//t.style.visibility="visible";
	alphashow(tool,10,1);
	
	//threadtt=
	//threadtt[tool]=setTimeout("closetooltip('"+tool+"');",time*1000);
}

function closetooltip(tool) { // IMPORTANTE
	return;//poster
	var t = objeto(tool);
	clearTimeout(threadtt[tool]);
	//alert(tool);
	//if (threadtt!=null) {
		clearTimeout(threadtt);
		threadtt=null;
	//}
	onendalpha = function(){
		if (t.style.visibility == "hidden") {
			t.style.top = "0px";
			t.style.left = "0px";
		}
	}
	alphahide(tool,9,1);
	//t.style.visibility="hidden";
}

function enfoca(obj) {
	var o = document.getElementById(obj);
	o.focus();
}

function activa(obj) {
	var o = document.getElementById(obj);
	o.readOnly = false;
}

function verif(obj) { // IMPORTANTE
	_obj = objeto(obj);
	if (_obj.value == "") {
		alert("No se ha especificado algo para buscar");
		_obj.focus();
	}
}

function inputonlyint(e, minus) {
	var menos = minus || false;
	var tecla = (document.all) ? e.keyCode : e.which;
	//48=0,57=9, 45=menos
	if(tecla==8)return true;//backs
	if (tecla==45){
		if (!menos){
			return false;
		}
	} else if (tecla < 48 || tecla > 57){
		return false;//e.keyCode = 0;
	}
	return true;
}

function inputnum(e, minus) {
	var menos = minus || false;
	var tecla = (document.all) ? e.keyCode : e.which;
	// 0 = Tabulador; 8 = Backspace; 48 = 0; 57 = 9; 45 = Menos
	if (tecla == 0 || tecla == 8) return true;
	if (tecla == _chardecimal.charCodeAt(0)) return true; // Punto decimal
	if (tecla == 45) {
		if (!menos) {
			return false;
		}
	} else if (tecla < 48 || tecla > 57) {
		return false;//e.keyCode = 0;
	}
	return true;
}

/*if (eval((typeof('$') != "undefined")) && !typeof('$')) {
	function $(id) {
		return document.getElementById(id);
	}
}*/

if (eval((typeof('isArray') != "undefined")) && !typeof('isArray')) {
	function isArray(obj) {
		return !(obj.length == null);
	}
}

//Verifica que el objeto sea un Array, de lo contrario lo fuerza a devolverse como un array bajo el indice 0 (cero)
if (eval((typeof('getForceArray') != "undefined")) && !typeof('getForceArray')) {
	function getForceArray(objeto){
		if(!isArray(objeto)){
				//lo convierte en un array de 1 sola dimensión
				var temp = objeto;
				objeto = new Array();
				objeto[0] = temp;
				
		}
		return objeto;
	}
}