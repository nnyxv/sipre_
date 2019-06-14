// JavaScript Document
function centraPopUp(w,h) {
	var w = w + 10;
	var h = h + 29;
	var ns4 = (document.layers) ? true : false;
	var ie4 = (document.all) ? true : false;

	if(ns4) { // Noteskapes
		window.outerWidth  = w;
		window.outerHeight = h;
		var Xcor = (screen.width - window.outerWidth) / 2 - 5;
		var Ycor = (screen.height - window.outerHeight) / 2 - 14;
	} else	{ //else if(ie4) // Explorando
		var Ycor = (screen.height - h) / 2 - 14;
		var Xcor = (screen.width - w) / 2 - 5;
	}
	
	return 'top='+Ycor+', left='+Xcor;
}

/*function verVentana(pagina, width, height) {
	posicion = ', '+centraPopUp(width,height);
	var opciones="toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=no, width="+width+", height="+height+posicion;
	window.open(pagina,"",opciones);
}*/