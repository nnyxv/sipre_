<?php
require_once("../connections/conex.php");

@session_start();

$query = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = 100");
$rs = mysql_query($query, $conex) or die(mysql_error());
$row = mysql_fetch_assoc($rs);
?>
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
	if (byId('spnTiempoSesion') != undefined) {
		byId('spnTiempoSesion').innerHTML = sesion;
	}
	
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

//iniciarConteo();

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
			
			//iniciarConteo();
		}
	}
}
document.getElementById('load_animate').style.display='none';
</script>

<form class="form-3">
    <table width="100%">
    <tr align="left">
        <td width="40%"></td>
        <td align="center" width="20%">
            <table style="text-align:center; background:#FFF; border-radius:0.4em;">
            <tr>
                <td><img id="imgLogoEmpresa" name="imgLogoEmpresa" src="<?php echo (strlen($row['logo_familia']) > 5) ? "../".$row['logo_familia'] : "../".$_SESSION['logoEmpresaSysGts']; ?>" width="180"></td>
            </tr>
            </table>
        </td>
        <td align="right" width="40%"></td>
    </tr>
    </table>
</form>
<iframe id="iframeSesion" name="iframeSesion" style="display:none"></iframe>
