// JavaScript Document

// 0 es flechas de direccion
// 8 es backspace
// 13 es enter
// 32 es barra espaciadora
// 44 ,
// 45 es -
// 46 es .
// 48 - 57 números
	// 48 es 0		51 es 3		54 es 6		57 es 9
	// 49 es 1		52 es 4		55 es 7
	// 50 es 2		53 es 5		56 es 8

// 65 - 90 letras MAYUSCULAS
	//	65 A	68 D	71 G	74 J	77 M	80 P	83 S	86 V	89 Y
	//	66 B	69 E	72 H	75 K	78 N	81 Q	84 T	87 W	90 Z
	//	67 C	70 F	73 I	76 L	79 O	82 R	85 U	88 X

// 97 - 122 letras minusculas
	// 97 a		100 d	103 g	106 j	109 m	112 p	115 s	118 v	121 y
	// 98 b		101 e	104 h	107 k	110 n	113 q	116 t	119 w	122 z
	// 99 c		102 f	105 i	108 l	111 o	114 r	117 u	120 x

// 225 es á
// 233 es é
// 237 es í
// 243 es ó
// 250 es ú
// 193 es Á
// 201 es É
// 205 es Í
// 211 es Ó
// 218 es Ú
// 209 es Ñ
// 241 es ñ


function validarCorreo (evento) {
	if (arguments.length > 1)
		color = arguments[1];
	
	if (evento.target)
		idObj = evento.target.id
	else if (evento.srcElement)
		idObj = evento.srcElement.id;
}

function validarFecha (evento) {
	if (arguments.length > 1)
		color = arguments[1];
	
	if (evento.target)
		idObj = evento.target.id
	else if (evento.srcElement)
		idObj = evento.srcElement.id;
	
	teclaCodigo = (document.all) ? evento.keyCode : evento.which;
	
	if ((teclaCodigo != 0)
	&& (teclaCodigo != 8)
	&& (teclaCodigo != 13)
	&& (teclaCodigo != 45)
	&& (teclaCodigo < 48 || teclaCodigo > 57)) {
		return false;
	}
}

function validarTelefono (evento) {
	if (arguments.length > 1)
		color = arguments[1];
	
	if (evento.target)
		idObj = evento.target.id
	else if (evento.srcElement)
		idObj = evento.srcElement.id;
	
	teclaCodigo = (document.all) ? evento.keyCode : evento.which;
	
	if ((teclaCodigo != 0)
	&& (teclaCodigo != 8)
	&& (teclaCodigo != 13)
	&& (teclaCodigo < 48 || teclaCodigo > 57)) {
		return false;
	}
}

function validarRif (evento) {
	if (arguments.length > 1)
		color = arguments[1];
	
	if (evento.target)
		idObj = evento.target.id
	else if (evento.srcElement)
		idObj = evento.srcElement.id;
	
	teclaCodigo = (document.all) ? evento.keyCode : evento.which;
	
	if ((teclaCodigo != 0)
	&& (teclaCodigo != 8)
	&& (teclaCodigo != 13)
	&& (teclaCodigo != 45)
	&& (teclaCodigo != 69) /* E */
	&& (teclaCodigo != 71) /* G */
	&& (teclaCodigo != 74) /* J */
	&& (teclaCodigo != 86) /* V */
	&& (teclaCodigo != 101) /* e */
	&& (teclaCodigo != 103) /* g */
	&& (teclaCodigo != 106) /* j */
	&& (teclaCodigo != 118) /* v */
	&& (teclaCodigo < 48 || teclaCodigo > 57)) {
		return false;
	}
}

function validarUser (evento) {
	if (arguments.length > 1)
		color = arguments[1];
	
	if (evento.target)
		idObj = evento.target.id
	else if (evento.srcElement)
		idObj = evento.srcElement.id;
}

function validarSoloNumeros (evento) {
	if (arguments.length > 1)
		color = arguments[1];
	
	if (evento.target)
		idObj = evento.target.id;
	else if (evento.srcElement)
		idObj = evento.srcElement.id;
	
	teclaCodigo = (document.all) ? evento.keyCode : evento.which;
	
	if ((teclaCodigo != 0)
	&& (teclaCodigo != 8)
	&& (teclaCodigo != 13)
	&& (teclaCodigo != 45) // es -
	&& (teclaCodigo != 44)
	&& (teclaCodigo <= 47 || teclaCodigo >= 58)) {
		return false;
	}
}

function validarSoloNumerosReales (evento) {
	if (arguments.length > 1)
		color = arguments[1];
	
	if (evento.target)
		idObj = evento.target.id;
	else if (evento.srcElement)
		idObj = evento.srcElement.id;
	
	teclaCodigo = (document.all) ? evento.keyCode : evento.which;
	
	if ((teclaCodigo != 0)
	&& (teclaCodigo != 8)
	&& (teclaCodigo != 13)
	&& (teclaCodigo != 45) // es -
	&& (teclaCodigo != 46)
	&& (teclaCodigo <= 47 || teclaCodigo >= 58)) {
		return false;
	}
}

function validarSoloTexto (evento) {
	if (arguments.length > 1)
		color = arguments[1];
	
	if (evento.target)
		idObj = evento.target.id
	else if (evento.srcElement)
		idObj = evento.srcElement.id;
	
	teclaCodigo = (document.all) ? evento.keyCode : evento.which;
	
	if ((teclaCodigo != 0)
	&& (teclaCodigo != 8)
	&& (teclaCodigo != 32)
	&& (teclaCodigo < 65 || teclaCodigo > 90)
	&& (teclaCodigo < 97 || teclaCodigo > 122)
	&& (teclaCodigo != 225) /* á */
	&& (teclaCodigo != 233) /* é */
	&& (teclaCodigo != 237) /* í */
	&& (teclaCodigo != 243) /* ó */
	&& (teclaCodigo != 250) /* ú */
	&& (teclaCodigo != 193) /* Á */
	&& (teclaCodigo != 201) /* É */
	&& (teclaCodigo != 205) /* Í */
	&& (teclaCodigo != 211) /* Ó */
	&& (teclaCodigo != 218) /* Ú */
	&& (teclaCodigo != 209) /* Ñ */
	&& (teclaCodigo != 241) /* ñ */
	) {
		return false;
	}
}

function validarCodigoArticulo (evento) {
	if (arguments.length > 1)
		color = arguments[1];
	
	if (evento.target)
		idObj = evento.target.id
	else if (evento.srcElement)
		idObj = evento.srcElement.id;
	
	teclaCodigo = (document.all) ? evento.keyCode : evento.which;
	
	if ((teclaCodigo != 0)		// tabulador
	&& (teclaCodigo != 8)		// borrar
	&& (teclaCodigo != 9)		// tabulador
	&& (teclaCodigo != 13)		// intro
	&& (teclaCodigo != 35)		// fin
	//&& (teclaCodigo != 32)		// espacio
	&& (teclaCodigo != 36)		// inicio
	&& (teclaCodigo != 37)		// izquierda
	&& (teclaCodigo != 38)		// arriba
	&& (teclaCodigo != 39)		// derecha
	&& (teclaCodigo != 40)		// abajo
	&& (teclaCodigo != 45)		// guion (-)
	//&& (teclaCodigo != 46)		// punto (.)
	&& (teclaCodigo <= 47 || teclaCodigo >= 58)
	&& (teclaCodigo < 65 || teclaCodigo > 90)
	&& (teclaCodigo < 97 || teclaCodigo > 122)) {	// suprimir
		return false;
	}
}

/*-------------------- MASCARAS --------------------*/
function mascaraNumeros(evento) {
	if (evento.target)
		Obj = evento.target
	else if (evento.srcElement)
		Obj = evento.srcElement;
	
	teclaCodigo = (document.all) ? evento.keyCode : evento.which;
                
	if ((teclaCodigo >= 48 && teclaCodigo <= 57)
	|| (teclaCodigo >= 96 && teclaCodigo <= 105)
	|| ((teclaCodigo == 8 || teclaCodigo == 46) && parseNumRafk(Obj.value) >= 0)
	|| (teclaCodigo == 44 || teclaCodigo == 46 || teclaCodigo == 110)) {
		pos = devPos(Obj.id);
		cant_pre = Obj.value.length;
		setFormatoRafk(Obj);
		cant_post = Obj.value.length;
		dif = (cant_post == 4) ? 0 : cant_post - cant_pre;
		sel(Obj.id, pos + dif, pos + dif);
	}
}

/*-------------------- FORMATOS --------------------*/
function validarCampo(idObj, requerido, formato) {
	if (document.getElementById(idObj) === null) {
		alert('El elemento con id ' + idObj + ' no existe');
	}
	
	texto = document.getElementById(idObj).value;
	
	if (requerido == 't') {
		if (texto.length > 0 && texto != null && texto != 'null') {
			if ((formato == 'lista' && validarFormSelect(idObj) == false)
			|| (formato == 'listaExceptCero' && validarFormSelectExceptCero(idObj) == false)
			|| (formato == 'email' && formatoEmail(idObj) == false)
			|| (formato == 'fecha' && formatoFecha(idObj) == false)
			|| (formato == 'rif' && formatoRIF(idObj) == false)
			|| (formato == 'ci' && formatoCI(idObj) == false)
			|| (formato == 'user' && formatoUser(idObj) == false)
			|| (formato == 'contrasena' && validarFormContrasena(idObj) == false)
			|| (formato == 'telefono' && formatoTelefono(idObj) == false)
			|| (formato == 'entero' && formatoEntero(idObj) == false)
			|| (formato == 'cantidad' && (parseFloat(texto) <= 0 || isNaN(parseFloat(texto)) == true))
			|| (formato == 'monto' && (parseFloat(texto) <= 0 || isNaN(parseFloat(texto)) == true))
			|| (formato == 'numPositivo' && (parseFloat(texto) < 0 || isNaN(parseFloat(texto)) == true))
			|| (formato == 'numeroControl' && formatoNumeroControl(idObj) == false)) {
				switch (document.getElementById(idObj).className) {
					case 'inputInicial' : 						clase = "inputErrado"; break;
					case 'inputHabilitado' : 					clase = "inputErrado"; break;
					case 'inputSinFondo' : 						clase = "inputSinFondoErrado"; break;
					case 'inputSinFondoHabilitado' : 			clase = "inputSinFondoErrado"; break;
					case 'inputCompleto' : 						clase = "inputCompletoErrado"; break;
					case 'inputCompletoHabilitado' :			clase = "inputCompletoErrado"; break;
					case 'inputCompletoSinFondo' : 				clase = "inputCompletoSinFondoErrado"; break;
					case 'inputCompletoSinFondoHabilitado' :	clase = "inputCompletoSinFondoErrado"; break;
					
					case 'inputTotal': 							clase = "inputErrado"; break;
					case '' : 									clase = "inputErrado"; break;
					default :									clase = document.getElementById(idObj).className; break;
				}
			} else {
				if (document.getElementById(idObj).readOnly == false || document.getElementById(idObj).disabled == false) {
					switch (document.getElementById(idObj).className) {
						case 'inputErrado' : 					clase = "inputHabilitado"; break;
						case 'inputSinFondoErrado' : 			clase = "inputSinFondoHabilitado"; break;
						case 'inputCompletoErrado' : 			clase = "inputCompletoHabilitado"; break;
						case 'inputCompletoSinFondoErrado' : 	clase = "inputCompletoSinFondoHabilitado"; break;
						default : 								clase = document.getElementById(idObj).className; break;
					}
				} else {
					clase = "inputInicial";
				}
			}
		} else {
			switch (document.getElementById(idObj).className) {
				case 'inputInicial' : 						clase = "inputErrado"; break;
				case 'inputHabilitado' : 					clase = "inputErrado"; break;
				case 'inputSinFondo' : 						clase = "inputSinFondoErrado"; break;
				case 'inputSinFondoHabilitado' : 			clase = "inputSinFondoErrado"; break;
				case 'inputCompleto' : 						clase = "inputCompletoErrado"; break;
				case 'inputCompletoHabilitado' :			clase = "inputCompletoErrado"; break;
				case 'inputCompletoSinFondo' : 				clase = "inputCompletoSinFondoErrado"; break;
				case 'inputCompletoSinFondoHabilitado' :	clase = "inputCompletoSinFondoErrado"; break;
				
				case 'inputTotal': 							clase = "inputErrado"; break;
				case '' : 									clase = "inputErrado"; break;
				default :									clase = document.getElementById(idObj).className; break;
			}
		}
	} else {
		if ((formato == 'lista' && validarFormSelect(idObj) == false)
		|| (formato == 'listaExceptCero' && validarFormSelectExceptCero(idObj) == false)
		|| (formato == 'email' && formatoEmail(idObj) == false && texto.length > 0)
		|| (formato == 'fecha' && formatoFecha(idObj) == false && texto.length > 0)
		|| (formato == 'rif' && formatoRIF(idObj) == false && texto.length > 0)
		|| (formato == 'ci' && formatoCI(idObj) == false && texto.length > 0)
		|| (formato == 'user' && formatoUser(idObj) == false && texto.length > 0)
		|| (formato == 'contrasena' && validarFormContrasena(idObj) == false && texto.length > 0)
		|| (formato == 'telefono' && formatoTelefono(idObj) == false && texto.length > 0)
		|| (formato == 'entero' && formatoEntero(idObj) == false && texto.length > 0)
		|| (formato == 'cantidad' && (parseFloat(texto) <= 0 || isNaN(parseFloat(texto)) == true) && texto.length > 0)
		|| (formato == 'monto' && (texto <= 0 || isNaN(parseFloat(texto)) == true) && texto.length > 0)
		|| (formato == 'numPositivo' && (parseFloat(texto) < 0 || isNaN(parseFloat(texto)) == true) && texto.length > 0)
		|| (formato == 'numeroControl' && formatoNumeroControl(idObj) == false && texto.length > 0)) {
			switch (document.getElementById(idObj).className) {
				case 'inputInicial' : 						clase = "inputErrado"; break;
				case 'inputHabilitado' : 					clase = "inputErrado"; break;
				case 'inputSinFondo' : 						clase = "inputSinFondoErrado"; break;
				case 'inputSinFondoHabilitado' : 			clase = "inputSinFondoErrado"; break;
				case 'inputCompleto' : 						clase = "inputCompletoErrado"; break;
				case 'inputCompletoHabilitado' :			clase = "inputCompletoErrado"; break;
				case 'inputCompletoSinFondo' : 				clase = "inputCompletoSinFondoErrado"; break;
				case 'inputCompletoSinFondoHabilitado' :	clase = "inputCompletoSinFondoErrado"; break;
				
				case 'inputTotal': 							clase = "inputErrado"; break;
				case '' : 									clase = "inputErrado"; break;
				default :									clase = document.getElementById(idObj).className; break;
			}
		} else {
			if (document.getElementById(idObj).readOnly == false || document.getElementById(idObj).disabled == false) {
				switch (document.getElementById(idObj).className) {
					case 'inputErrado' : 					clase = "inputHabilitado"; break;
					case 'inputSinFondoErrado' : 			clase = "inputSinFondoHabilitado"; break;
					case 'inputCompletoErrado' : 			clase = "inputCompletoHabilitado"; break;
					case 'inputCompletoSinFondoErrado' : 	clase = "inputCompletoSinFondoHabilitado"; break;
					default : 								clase = document.getElementById(idObj).className; break;
				}
			} else {
				clase = "inputInicial";
			}
		}
	}
	
	if (document.getElementById(idObj) != undefined) {
		document.getElementById(idObj).className = clase;
	}
	
	if (inArray(clase, ["inputInicial","inputHabilitado","inputSinFondo","inputSinFondoHabilitado","inputCompleto","inputCompletoHabilitado","inputCompletoSinFondo","inputCompletoSinFondoHabilitado","inputTotal",""])) {
		return true;
	} else if (inArray(clase, ["inputErrado","inputSinFondoErrado","inputCompletoErrado","inputCompletoSinFondoErrado"])) {
		return false;
	}
}

function validarFormSelect(idObj) {
	texto = document.getElementById(idObj).value;
	if (texto != '-1' && texto != 'Seleccione...' && texto != '[ Seleccione ]' && texto != '' && texto != 'null' && texto != '0') {
     	return true;
	} else {
		return false;
	}  
}

function validarFormSelectExceptCero(idObj) {
	texto = document.getElementById(idObj).value;
	
	if (texto != '-1' && texto != 'Seleccione...' && texto != '[ Seleccione ]') {
     	return true;
	} else {
		return false;
	}
}

function formatoCI(idObj) {
	texto = document.getElementById(idObj).value;
	// VENEZUELA
	/*var a=/^(\d*[\/]\d*)$/;
	var b=/^(\d{5,8})$/;
	var c=/^(\d{8}-\d{1})$/;*/
	var d=/^([VE]-\d{5,9})$/; // C.I.
	var e=/^([VE]-\d{5,9}-\d{1})$/; // R.I.F. PERSONAL
	
	// PANAMA
	var f=/^(\d{1}-\d{1,6}-\d{1,6})$/; // C.I.V. PANAMEÑO EJEMP : 8-926-1601
	var g=/^(PE-\d{1}-\d{1,6})$/; // C.I.V. PANAMEÑO NACIDO EN EXTRAJERO EJEMP : PE-5-687
	var h=/^(N-\d{1,6}-\d{1,6})$/; // C.I.V. PANAMEÑO NATURALIZADO EJEMP : N-19-473
	var i=/^(E-\d{1}-\d{1,6})$/; // C.I.V. EXTRAJERO DOMICILIADO EJEMP : E-19-473
	
	// INTERNACIONAL
	var j=/^(P-[A-Z0-9]{1,10})$/; // PASAPORTE
	
	if (d.test(texto) || e.test(texto) 
	|| f.test(texto) || g.test(texto) || h.test(texto) || i.test(texto)
	|| j.test(texto)){ 
     	return true;
	} else {
		return false;
	}  
}

function formatoEmail(idObj) {
	texto = document.getElementById(idObj).value;
    var b=/^[^@\s]+@[^@\.\s]+(\.[^@\.\s]+)+$/;
	if (b.test(texto)) {
     	return true;
	} else {
		return false;
	}  
}

function formatoEntero(idObj) {
	texto = document.getElementById(idObj).value;
	var b=/^\d+$/;
	if (b.test(texto)){
		return true;
	} else {
		return false;
	}
}

function formatoFecha(idObj) {
	texto = document.getElementById(idObj).value;
	var a= /^(0[1-9]|1\d|2\d|3[0-1])-(0[1-9]|1[0-2])-\d{4}$/;	// 31-12-2012
	var b= /^(0[1-9]|1\d|2\d|3[0-1])\/(0[1-9]|1[0-2])\/\d{4}$/;	// 31/12/2012
	var c= /^(0[1-9]|1[0-2])-(0[1-9]|1\d|2\d|3[0-1])-\d{4}$/;	// 12-31-2012
	var d= /^(0[1-9]|1[0-2])\/(0[1-9]|1\d|2\d|3[0-1])\/\d{4}$/;	// 12/31/2012
	if (a.test(texto) || b.test(texto)
	|| c.test(texto) || d.test(texto)){ 
     	return true;
	} else {
		return false;
	}  
}

function formatoNumeroControl(idObj) {
	texto = document.getElementById(idObj).value;
	var a=/^(\w{1,20})$/;
	var b=/^(\d{2}[-]\d*)$/;
	if (a.test(texto) || b.test(texto)){ 
     	return true;
	} else {
		return false;
	}  
}

function formatoRIF(idObj) {
	texto = document.getElementById(idObj).value;
	// VENEZUELA
	//var a=/^(\d{8}-\d{1})$/;
	var b=/^([VEJGD]-\d{8}-\d{1})$/; // R.I.F.
	
	if (b.test(texto)){ 
     	return true;
	} else {
		return false;
	}  
}

function formatoTelefono(idObj) {
	texto = document.getElementById(idObj).value;
	var a=/^([+]?\d{1,4}-)?\d{3,4}-\d{5,7}$/; // VENEZUELA
	var b=/^([+]?\d{1,4}-)?\d{1,4}-\d{1,4}$/; // PANAMA
	var c=/^([+]?\d{1,4}-)?\d{5,8}$/; // URUGUAY
	if (a.test(texto) || b.test(texto) || c.test(texto)){ 
     	return true;
	} else {
		return false;
	}  
}

function formatoUser(idObj) {
	texto = document.getElementById(idObj).value;
    var b=/^[^@\s]+@[^@\.\s]+(\.[^@\.\s]+)+$/;
	var c=/^([VEJGvejg][-]\d{8}[-][0-9])$/;
	if (b.test(texto) || c.test(texto)) {
     	return true;
	} else {
		return false;
	}  
}

function validarFormContrasena(idObj) {
	texto = document.getElementById(idObj).value;
	if (texto.length >= 5) {
     	return true;
	} else {
		return false;
	}  
}

function maxCaracteres (evento, maxCaracteres) {	
	if (evento.target)
		idObj = evento.target.id
	else if (evento.srcElement)
		idObj = evento.srcElement.id;
	
	teclaCodigo = (document.all) ? evento.keyCode : evento.which;
	
	if ((teclaCodigo != 0)
	&& (teclaCodigo != 8)
	&& (teclaCodigo != 13)) {
		if (document.getElementById(idObj).value.length >= maxCaracteres-1)
			return false;
	}
}
function maxCaracteresCortar (evento, maxCaracteres) {	
	if (evento.target)
		idObj = evento.target.id
	else if (evento.srcElement)
		idObj = evento.srcElement.id;
	
	teclaCodigo = (document.all) ? evento.keyCode : evento.which;
	
	
	if (document.getElementById(idObj).value.length >= maxCaracteres-1)
		document.getElementById(idObj).value = document.getElementById(idObj).value.substring(0, maxCaracteres-1);
}

function letrasMayusculas(evento, idObj) {
	if (evento.target)
		idObj = evento.target.id
	else if (evento.srcElement)
		idObj = evento.srcElement.id;
	
	teclaCodigo = (document.all) ? evento.keyCode : evento.which;
	
	if ((teclaCodigo != 8)		// borrar
	&& (teclaCodigo != 9)		// tabulador
	&& (teclaCodigo != 13)		// intro
	&& (teclaCodigo != 16)		// shift
	&& (teclaCodigo != 17)		// control
	&& (teclaCodigo != 18)		// alt
	&& (teclaCodigo != 35)		// fin
	&& (teclaCodigo != 36)		// inicio
	&& (teclaCodigo != 37)		// izquierda
	&& (teclaCodigo != 38)		// arriba
	&& (teclaCodigo != 39)		// derecha
	&& (teclaCodigo != 40)		// abajo
	&& (teclaCodigo != 46)) {	// suprimir
		var texto = document.getElementById(idObj).value;
		document.getElementById(idObj).value = texto.toUpperCase();
	}
}

/**
 * AW Masked Input
 * @version 1.2.1
 * @author Kendall Conrad
 * @url http://www.angelwatt.com/coding/masked_input.php
 * @created 2008-12-16
 * @modified 2012-08-22
 * @license This work is licensed under a Creative Commons
 *  Attribution-Share Alike 3.0 United States License
 *  http://creativecommons.org/licenses/by-sa/3.0/us/
 *
 * Argument pieces {
 * @param elm [req] text input node to apply the mask on
 * @param format [req] string format for the mask
 * @param allowed [opt, '0123456789'] string with chars allowed to be typed
 * @param sep [opt, '\/:-'] string of char(s) used as separators in mask
 * @param typeon [opt, '_YMDhms'] string of chars in mask that can be typed on
 * @param onbadkey [opt, null] function to run when user types a unallowed key
 * @param badkeywait [opt, 0] used with onbadkey. Indicates how long (in ms) to lock text input for onbadkey function to run
 * };
 */
(function(scope) {
'use strict';

scope.MaskedInput = function(args) {
	// Ensure passing in valid argument
	if (!args || !args.elm  || !args.format) {
		return null;
	}
	// Ensure use of 'new'
	if (!(this instanceof scope.MaskedInput)) {
		return new scope.MaskedInput(args);
	}
	// Initialize variables
	var self = this,
		el = args['elm'],
		format = args['format'],
		allowed = args['allowed'] || '0123456789',
		sep = args['separator'] || '\/:-',
		open = args['typeon'] || '_YMDhms',
		onbadkey = args['onbadkey'] || function(){},
		badwait = args['badkeywait'] || 0,
		// ----
		locked = false,
		startText = format;

	/**
	 * Add events to objects.
	 */
	var evtAdd = function(obj, type, fx, capture) {
		if (window.addEventListener) {
			return function (obj, type, fx, capture) {
				obj.addEventListener(type, fx,
					(capture === undefined) ? false : capture);
			};
		}
		if (window.attachEvent) {
			return function (obj, type, fx) {
				obj.attachEvent('on' + type, fx);
			};
		}
		return function (obj, type, fx) {
			obj['on' + type] = fx;
		};
	}();

	/**
	 * Initialize the object.
	 */
	var init = function() {
		// Check if an input or textarea tag was passed in
		if (!el.tagName || (el.tagName.toUpperCase() !== 'INPUT' && el.tagName.toUpperCase() !== 'TEXTAREA')) {
			return null;
		}

		el.value = format;
		// Assign events
		evtAdd(el, 'keydown', function(e) {
			KeyHandlerDown(e);
		});
		evtAdd(el, 'keypress', function(e) {
			KeyHandlerPress(e);
		});
		// Let us set the initial text state when focused
		evtAdd(el, 'focus', function() {
			startText = el.value;
		});
		// Handle onChange event manually
		evtAdd(el, 'blur', function() {
			if (el.value !== startText && el.onchange) {
				el.onchange();
			}
		});
		return self;
	};

	/**
	 * Gets the keyboard input in usable way.
	 * @param code integer character code
	 * @return string representing character code
	 */
	var GetKey = function(code) {
		code = code || window.event;
		var ch = '',
			keyCode = code.which,
			evt = code.type;
		if (keyCode == null) {
			keyCode = code.keyCode;
		}
		// no key, no play
		if (keyCode === null) {
			return '';
		}
		// deal with special keys
		switch (keyCode) {
		case 8:
			ch = 'bksp';
			break;
		case 46: // handle del and . both being 46
			ch = (evt == 'keydown') ? 'del' : '.';
			break;
		case 16:
			ch = 'shift';
			break;
		case 0: /*CRAP*/
		case 9: /*TAB*/
		case 13:/*ENTER*/
			ch = 'etc';
			break;
		case 37: case 38: case 39: case 40: // arrow keys
			ch = (!code.shiftKey &&
					 (code.charCode != 39 && code.charCode !== undefined)) ?
				'etc' : String.fromCharCode(keyCode);
			break;
		// default to thinking it's a character or digit
		default:
			ch = String.fromCharCode(keyCode);
		}
		return ch;
	};

	/**
	 * Stop the event propogation chain.
	 * @param evt Event to stop
	 * @param ret boolean, used for IE to prevent default event
	 */
	var stopEvent = function(evt, ret) {
		// Stop default behavior the standard way
		if (evt.preventDefault) {
			evt.preventDefault();
		}
		// Then there's IE
        evt.returnValue = ret || false;
	};

	/**
	 * Handles the key down events.
	 * @param e Event
	 */
	var KeyHandlerDown = function(e) {
		e = e || event;
		if (locked) {
			stopEvent(e);
			return false;
		}
		var key = GetKey(e);
		// Stop copy and paste
		if ((e.metaKey || e.ctrlKey) && (key == 'X' || key == 'V')) {
			stopEvent(e);
			return false;
		}
		// Allow for OS commands
		if (e.metaKey || e.ctrlKey) {
			return true;
		}
		if (el.value == '') {
			el.value = format;
			SetTextCursor(el,0);
		}
		// Only do update for bksp del
		if (key == 'bksp' || key == 'del') {
			Update(key);
			stopEvent(e);
			return false;
		}
		else {
            return true;
		}
	};

	/**
	 * Handles the key press events.
	 * @param e Event
	 */
	var KeyHandlerPress = function(e) {
		e = e || event;
		if (locked) {
			stopEvent(e);
			return false;
		}
		var key = GetKey(e);
		// Check if modifier key is being pressed; command
		if (key=='etc' || e.metaKey || e.ctrlKey || e.altKey) {
			return true;
		}
		if (key != 'bksp' && key != 'del' && key != 'shift') {
			if (!GoodOnes(key)) {
				stopEvent(e);
				return false;
			}
			if (Update(key)) {
				stopEvent(e, true);
				return true;
			}
			stopEvent(e);
			return false;
		}
		else {
			return false;
		}
	};

	/**
	 * Updates the text field with the given key.
	 * @param key string keyboard input.
	 */
	var Update = function(key) {
		var p = GetTextCursor(el),
			c = el.value,
			val = '';
		// Handle keys now
		switch (true) {
		// Allowed characters
		case (allowed.indexOf(key) != -1):
			// if text cursor at end
			if (++p > format.length) {
				return false;
			}
			// Handle cases where user places cursor before separator
			while (sep.indexOf(c.charAt(p-1)) != -1 && p <= format.length) {
				p++;
			}
			val = c.substr(0, p-1) + key + c.substr(p);
			// Move csor up a spot if next char is a separator char
			if (allowed.indexOf(c.charAt(p)) == -1
					&& open.indexOf(c.charAt(p)) == -1) {
				p++;
			}
			break;
		case (key=='bksp'): // backspace
			// at start of field
			if (--p < 0) {
				return false;
			}
			// If previous char is a separator, move a little more
			while (allowed.indexOf(c.charAt(p)) == -1
					&& open.indexOf(c.charAt(p)) == -1
					&& p > 1) {
				p--;
			}
			val = c.substr(0, p) + format.substr(p,1) + c.substr(p+1);
			break;
		case (key=='del'): // forward delete
			// at end of field
			if (p >= c.length) {
				return false;
			}
			// If next char is a separator and not the end of the text field
			while (sep.indexOf(c.charAt(p)) != -1
					 && c.charAt(p) != '') {
				p++;
			}
			val = c.substr(0, p) + format.substr(p,1) + c.substr(p+1);
			p++; // Move position forward
			break;
		case (key=='etc'):
			// Catch other allowed chars
			return true;
		default:
			return false; // Ignore the rest
		}
		el.value = ''; // blank it first (Firefox issue)
		el.value = val; // put updated value back in
		SetTextCursor(el, p); // Set the text cursor
		return false;
	};

	/**
	 * Gets the current position of the text cursor in a text field.
	 * @param node a input or textarea HTML node.
	 * @return int text cursor position index, or -1 if there was a problem.
	 */
	var GetTextCursor = function(node) {
		try {
			if (node.selectionStart >= 0) {
				return node.selectionStart;
			}
			else if (document.selection) {// IE
				var ntxt = node.value; // getting starting text
				var rng = document.selection.createRange();
				rng.text = '|%|';
				var start = node.value.indexOf('|%|');
				rng.moveStart('character', -3);
				rng.text = '';
				// put starting text back in,
				// fixes issue if all text was highlighted
				node.value = ntxt;
				return start;
			}
			return -1;
		}
		catch(e) {
			return -1;
		}
	};

	/**
	 * Sets the text cursor in a text field to a specific position.
	 * @param node a input or textarea HTML node.
	 * @param pos int of the position to be placed.
	 * @return boolean true is successful, false otherwise.
	 */
	var SetTextCursor = function(node, pos) {
		try {
			if (node.selectionStart) {
				node.focus();
				node.setSelectionRange(pos,pos);
			}
			else if (node.createTextRange) { // IE
				var rng = node.createTextRange();
				rng.move('character', pos);
				rng.select();
			}
		}
		catch(e) {
			return false;
		}
		return true;
	};

	/**
	 * Returns whether or not a given input is valid for the mask.
	 * @param k string of character to check.
	 * @return bool true if it's a valid character.
	 */
	var GoodOnes = function(k) {
		// if not in allowed list, or invisible key action
		if (allowed.indexOf(k) == -1 && k!='bksp' && k!='del' && k!='etc') {
			// Need to ensure cursor position not lost
			var p = GetTextCursor(el);
			locked = true;
			onbadkey();
			// Hold lock long enough for onbadkey function to run
			setTimeout(function() {
				locked = false;
				SetTextCursor(el,p);
			}, badwait);
			return false;
		}
		return true;
	};

	/**
	 * Resets the text field so just the format is present.
	 */
	self.resetField = function() {
		el.value = format;
	};

	/**
	 * Set the allowed characters that can be used in the mask.
	 * @param a string of characters that can be used.
	 */
	self.setAllowed = function(a) {
		allowed = a;
		resetField();
	};

	/**
	 * The format to be used in the mask.
	 * @param f string of the format.
	 */
	self.setFormat = function(f) {
		format = f;
		resetField();
	};

	/**
	 * Set the characters to be used as separators.
	 * @param s string representing the separator characters.
	 */
	self.setSeparator = function(s) {
		sep = s;
		resetField();
	};

	/**
	 * Set the characters that the user will be typing over.
	 * @param t string representing the characters that will be typed over.
	 */
	self.setTypeon = function(t) {
		open = t;
		resetField();
	};

	return init();
}
})(window);