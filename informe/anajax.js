// (c) 2008, Maycol Alvarez
// Version 2.0
// GNU GPL version 1.1.
function urlencode( str ) {
	
	var histogram = {}, histogram_r = {}, code = 0, tmp_arr = [];
	var ret = str.toString();
	
	var replacer = function(search, replace, str) {
		var tmp_arr = [];
		tmp_arr = str.split(search);
		return tmp_arr.join(replace);
	};
	
	// The histogram is identical to the one in urldecode.
	histogram['!']   = '%21';
	histogram['%20'] = '+';
	
	// Begin with encodeURIComponent, which most resembles PHP's encoding functions
	ret = encodeURIComponent(ret);
	
	for (search in histogram) {
		replace = histogram[search];
		ret = replacer(search, replace, ret) // Custom replace. No regexing
	}
	
	// Uppercase for full PHP compatibility
	return ret.replace(/(\%([a-z0-9]{2}))/g, function(full, m1, m2) {
		return "%"+m2.toUpperCase();
	});
	
	return ret;
} 

/*const readystateloading=1;
const readystatepreload=2;
const readystatepostload=3;
const readystateload=4;
const statecomplete=200;
const statefailurl=404;
const statemaxget=414;*/
// crea una nueva instancia del objeto XMLHttpRequest
function Ajax() {
	var xmlhttpobj;
	function getajax(){
		//alert("W");
		try {
			xmlhttpobj = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (ex) {
			try {
				xmlhttpobj= new ActiveXObject("Microsoft.XMLHTTP");
			} catch (ex2) {
				xmlhttpobj= false;
			}
		}
		if (!xmlhttpobj && typeof XMLHttpRequest!='undefined') {
			xmlhttpobj = new XMLHttpRequest();
		}
		return xmlhttpobj;
	}
	
	this.cancelar= function(){
		xmlhttpobj.abort();
		var ajaxobject = this;
		ajaxobject.error("Cancelado por el usuario");
	}
	
	this.sendget = function(url, value, rxml){
		var xmlhttpobj=getajax();
		/*xmlhttpobj.onprogress=function(){
			alert(xmlhttpobj.responseText);
		}*/
		var ajaxobject = this;
		//alert(urlencode(value));
		xmlhttpobj.open ("GET", (url)+"?"+(value), true);
		xmlhttpobj.onreadystatechange=function(){
			if (xmlhttpobj.readyState==4){
				if (xmlhttpobj.status==200){
					if (rxml==true){
					
						ajaxobject.loadxml(xmlhttpobj.responseXML);
						
						//alert(xmlhttpobj.responseXML);
					} else {
						ajaxobject.load(xmlhttpobj.responseText);					
					}
				}
			} else if (xmlhttpobj.readyState==2){
				ajaxobject.preload();
			} else if (xmlhttpobj.readyState==3){
				ajaxobject.interactive();
			} else if (xmlhttpobj.readyState==1){
				ajaxobject.loading();
			}
		}
		xmlhttpobj.send(null);
	}
	
	this.loading = function cargando(){};
	this.load = function cargado(text){};
	this.error = function error(motivo){};
	this.loadxml = function cargadoxml(xml){};
	this.preload = function precargado(){};
	this.interactive = function iterando(){};
}

//carga los valores de los inputs y capas desde un origen xml
function loadxml(page, cmd, id, idEmpresa, idCliente, valores){
	//var _obj=objeto(campo);
	var a = new Ajax();
	//a.loading=carga;
	//a.error=er;
	a.loadxml = function(xml){
		//leyendo los campos de texto
		if (xml.getElementsByTagName("texto").length != 0){
			var x = xml.getElementsByTagName("texto")[0].childNodes;
			var i = 0,campo;
			for (i = 0; i < x.length; i++){
				campo = document.getElementById(x[i].nodeName);
				if (x[i].childNodes.length > 0) {
					campo.value = x[i].childNodes[0].nodeValue;
				} else {
					campo.value = "";
				}
				if (campo.type == "text") {//&& (!setprint)){
					//campo.disabled = true;
					campo.readOnly = true;
				}/*else{
					campo.readOnly = true;
				}*/
			}
		}
		campo = null;
		// leyendo lista para el cierre:
		if(xml.getElementsByTagName("closelist").length != 0){
			campo = document.getElementById(xml.getElementsByTagName("closelist")[0].childNodes[0].nodeValue);
			campo.style.visibility = "hidden";
		}
		campo = null;
		// leyendo las capas
		if (xml.getElementsByTagName("capa").length != 0){
			var x = xml.getElementsByTagName("capa")[0].childNodes;
			var i = 0,campo;
			for (i = 0; i < x.length; i++){
				campo = document.getElementById(x[i].nodeName);
				if (x[i].childNodes.length > 0) {
					campo.innerHTML = x[i].childNodes[0].nodeValue;
				} else {
					campo.innerHTML = "";
				}
			}
		}
		campo = null;
		// leyendo las llamadas a funcion
		if (xml.getElementsByTagName("function").length != 0){
			var x = xml.getElementsByTagName("function")[0].childNodes;
			var i = 0,funcion;
			for (i = 0; i < x.length; i++){
				funcion = x[i].nodeName+"(";
				if (x[i].childNodes.length > 0) {
					funcion += x[i].childNodes[0].nodeValue;
				}
				eval(funcion + ");");
			}
		}
	};
	a.sendget(page,"ajax_get" + cmd + "=" + id
		+ "&idEmpresa=" + idEmpresa
		+ "&idCliente=" + idCliente
		+ "&valores=" + valores, true);
}


/*
function buscarcedula(obj){
	if (obj.value.toString().length<2) {
		var lista= document.getElementById("lista");
		lista.style.visibility="hidden";
		return;
	}
	var a= new Ajax();
	//a.loading=carga;
	a.load=listacliente;
	//a.error=er;
	a.sendget("an_ventas_presupuesto_ajax.php","ajax_cedula="+obj.value,false);
}
listacliente = function(texto){
	var lista= document.getElementById("lista");
	lista.style.visibility="visible";
	var obj= document.getElementById("bcliente");
	lista.style.left=getOffsetLeft(obj)+"px";
	lista.style.top=getOffsetTop(obj)+"px";
	lista.style.margin=obj.offsetHeight+"px 0px 0px 0px";
	lista.innerHTML=texto;
}
function cerrarlista(){
	var lista= document.getElementById("lista");
	lista.style.visibility="hidden";
}
function mostrarlista(){
	var lista= document.getElementById("lista");
	lista.style.visibility="visible";	
}*/