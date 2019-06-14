<div id="load_animate">&nbsp;</div>

<script type="text/javascript">	
var cerrarVentana = true;
window.onbeforeunload = function() {
	if (cerrarVentana == false) {
		return "Se recomienda CANCELAR este cuadro de mensaje\n\nDebe Cerrar la Ventana para efectuar las transacciones efectivamente";
	}
}

var maxTiempoSesion = parseInt('<?php echo ini_get('session.gc_maxlifetime');?>') * 1000; // SE MULTIPLICA POR 1000 PARA LLEVARLA A MILISEGUNDOS
var tiempoAlerta = (maxTiempoSesion * 0.80); //20% DEL TIEMPO DE SESION PARA LA CONFIRMACION

//setup before functions
var sesion = 0;
var typingTimer;						//timer identifier
var typingTimer2;    
var typingTimer3;    
var doneTypingInterval = tiempoAlerta;	//time in ms, 5 second for example

// LLAMANDO A LA FUNCION QUE DETERMINA LA CADUCACION DE LA SESION
function alertaCierreSesion (){
	verificarPopUpSession('<?php echo $raiz."extender_sesion.php"; ?>');
	popupSession = null;
	return;
}

function cerrarSesion (){
	document.getElementById('iframeSesion').src = '<?php echo $raiz."evalua_sesion.php"; ?>';
	popupSession = null;
	return;
}

function contadorSesion(sesion) {
	sesion = sesion + 1;
	byId('spnTiempoSesion').innerHTML = sesion;
	
    typingTimer3 = setTimeout("contadorSesion("+sesion+");",1000);
}

function iniciarConteo() {
	clearTimeout(typingTimer);
	clearTimeout(typingTimer2);
	clearTimeout(typingTimer3);
	typingTimer = setTimeout(alertaCierreSesion, doneTypingInterval);
	typingTimer2 = setTimeout(cerrarSesion,maxTiempoSesion);
	typingTimer3 = setTimeout("contadorSesion("+sesion+");",1000);
}

iniciarConteo();

if (typeof(xajax) != 'undefined') {
	if(xajax != null){
		xajax.callback.global.onRequest = function() {
			var x = document.getElementsByTagName("*");
			var i;
			for (i = 0; i < x.length; i++) {
				if (!(x[i] == undefined)) {
					if (inArray(x[i].type, ['button','submit']) && x[i].formNoValidate == false) {
						x[i].disabled = true;
					}
				}
			}
			
			//xajax.$('loading').style.display = 'block';
			document.getElementById('load_animate').style.display='';

			clearTimeout(typingTimer);
			clearTimeout(typingTimer2);
			clearTimeout(typingTimer3);
		}
		xajax.callback.global.beforeResponseProcessing = function() {
			var x = document.getElementsByTagName("*");
			var i;
			for (i = 0; i < x.length; i++) {
				if (!(x[i] == undefined)) {
					if (inArray(x[i].type, ['button','submit']) && x[i].formNoValidate == false) {
						x[i].disabled = false;
					}
				}
			}
			
			//xajax.$('loading').style.display = 'none';
			document.getElementById('load_animate').style.display='none';
			
			iniciarConteo();
		}
	}
}
document.getElementById('load_animate').style.display='none';
</script>

<table cellpadding="0" cellspacing="0" width="100%">
<tr>
	<td colspan="3" height="10"></td>
</tr>
<tr>
	<td align="left" width="40%"><img src="../img/crm/imgmodulo.png"/></td>
    <td align="center" width="20%"><img src="<?php echo (strlen($_SESSION['logoEmpresaSysGts']) > 5) ? "../".$_SESSION['logoEmpresaSysGts'] : ""; ?>" height="80"/></td>
    <td align="right" width="40%"><img src="../img/logos/logo_sipre.jpg" height="80"></td>
</tr>
<tr>
	<td colspan="3" height="4"></td>
</tr>
<tr>
	<td align="center" class="textoBlancoNegrita_12px" colspan="3" style="border-radius:6px;" background="../img/crm/header.gif" height="35">Copyright 2008, <a class="linkBlanco" href="http://www.gotosys.com" target="_blank">Goto Systems C.A.</a> All rights reserved | Privacy Policy</td>
</tr>
</table>
<iframe id="iframeSesion" name="iframeSesion" style="display:none"></iframe>