//JavaScript Document
//Realizado por Rafaias Villan
//martes, 02 de diciembre de 2008
//raResponse beta version 4.12.8
var http_request = false;

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

function hacerRequest(url,argumentos) {
	var parametros='';
	http_request = false;
	if (window.XMLHttpRequest) { // Mozilla, Safari,...
		http_request = new XMLHttpRequest();
		if (http_request.overrideMimeType) {
			http_request.overrideMimeType('text/xml');
		}
	} else if (window.ActiveXObject) { // IE
		try {
			http_request = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			try {
				http_request = new ActiveXObject("Microsoft.XMLHTTP");
			} catch (e) {}
		}
	}
	if (!http_request) {
		alert('Giving up :( Cannot create an XMLHTTP instance');
		return false;
	}
	
	http_request.onreadystatechange = procesarResponse;
	http_request.open('POST', url, true);	
	http_request.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=utf-8");
	http_request.send(argumentos);
}

function llamarPhp(url, funcion){
	argumentos = 'funcion='+funcion;
	Miargumentos = llamarPhp.arguments;
	for(i=2; arg=Miargumentos[i]; i++){
		argumentos += '&'+i+'='+urlencode(arg);
	}
	hacerRequest(url,argumentos);
}

function procesarResponse() {
	if (http_request.readyState == 4) {
		if (http_request.status == 200) {
			//alert(http_request.responseText);
			var response = http_request.responseText;
			if(response != "null"){
				var test = response.substring(0,6);
				if(test == "<br />"){
					alert(response);	
				}else{
					var json = eval('(' + http_request.responseText + ')');
					for(i=0; response=json[i];i++){
						if(response.accion=='asignar'){
							eval("document.getElementById('"+response.id+"')."+response.atributo+" = '"+response.valor+"'");
						}else if(response.accion=='alerta'){
							eval("alert('"+response.texto+"');");
						}else if(response.accion=='script'){
							eval(response.codigo);	
						}else if(response.accion=='selected'){
							var lista = document.getElementById(response.id);
							for(j=0; lst=lista[j]; j++){
							if(lst.value == response.valor || lst.text == response.valor)
								lst.selected = true;
							}
						}else if(response.accion=='checked'){
							var lista = document.getElementById(response.id);
							for(j=0; lst=lista[j]; j++){
							if(lst.value == response.valor || lst.text == response.valor)
								lst.selected = true;
							}
						}
					}
				}
			}
		} else {
        	alert('There was a problem with the request.');
        }
	}
}

function findString(array, elemento){
	var encontrado = 0;
	//alert(array.length);
	for(j=0;a=array[j];j++){
		if(a == elemento)
			encontrado = 1;
	}
	return encontrado;
}

function enviarFormulario(id){
	var form = document.getElementById(id);
	var ready = new Array();
	var json='{';
	for(i=0; f=form[i];i++){
		if(!findString(ready, f.name)){
			ready.push(f.name);
			if(f.type=='radio'){
				if(i) json +=',';
				json+='"'+f.name+'":"';
				var ele = form[f.name];
				for(j=0;e=ele[j];j++){
					if(e.checked){
						json+=e.value;
					}
				}
				json+='"';
			}else if(f.type=='checkbox'){
				if(i) json +=',';
				json+='"'+f.name+'":[';
				var ele = form[f.name];
				var chk =0;
				for(j=0;e=ele[j];j++){
					if(e.checked){
						if(chk++) json +=',';
						json+='"'+e.value+'"';
					}
				}
				json+=']';
			}else if(f.type=='text' || f.type=='hidden' || f.type =='textarea'){
				if(i) json +=',';
				if(form[f.name].length){
					//if(i) json +=',';
					json+='"'+f.name.replace(/\[\]/,'')+'":[';
					var ele = form[f.name];
					for(j=0;e=ele[j];j++){
						if(j) json +=',';
						json+='"'+e.value+'"';
					}
					json+=']';
				}else{
					json+='"'+f.name+'":"'+f.value+'"';
				}
			}else if(f.type == 'select-one'){	
				if(i) json +=',';
				json+='"'+f.name+'":"'+f.value+'"';
			}else if(f.type == 'select-multiple'){	
				var ele = form[f.name];
				if(i) json +=',';
				json+='"'+f.name+'":[';
				var lst =0;
				for(j=0;e=ele[j];j++){
					if(e.selected){
						if(lst++) json +=',';
						json+='"'+e.value+'"';
					}
				}
				json+=']';
			}
		}
	}
	json+='}';
	//alert(json);
	//document.body.innerHTML = json;
	return json;
}
