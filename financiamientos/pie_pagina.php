<div id="load_animate">&nbsp;</div>

<script type="text/javascript">	
var cerrarVentana = true;
window.onbeforeunload = function() {
	if (cerrarVentana == false) {
		return "Se recomienda CANCELAR este cuadro de mensaje\n\nDebe Cerrar la Ventana para efectuar las transacciones efectivamente";
	}
}

var  tiempoSession = false;
 
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
			
			//xajax.$('loading').style.display='none';
			document.getElementById('load_animate').style.display='none';

			//ABRIENDO LA SESSION DEBIDO AL XAJAX
			if(tiempoSession == false){
				aumentarTiempo(0);
			}
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
	<td align="left" width="40%"><img src="../img/financiamientos/imgmodulo.png"/></td>
    <td align="center" width="20%"><img src="<?php echo (strlen($_SESSION['logoEmpresaSysGts']) > 5) ? "../".$_SESSION['logoEmpresaSysGts'] : ""; ?>" height="80"/></td>
    <td align="right" width="40%"><img src="../img/logos/logo_sipre.jpg" height="80"></td>
</tr>
<tr>
	<td colspan="3" height="4"></td>
</tr>
<tr>
	<td align="center" class="textoBlancoNegrita_12px" colspan="3" style="border-radius:6px;" background="../img/financiamientos/header.gif" height="35">Copyright 2008, Goto Systems C.A. All rights reserved | Privacy Policy | Copyrights Information</td>
</tr>
</table>

<!-- Funcion que permite temporizar la sesion -->

<?php 
if(isset($_SESSION['idUsuarioSysGts'])){
?>
<script type="text/javascript">

//LLAMANDO A LA FUNCION QUE DETERMINA LA CADUCACION DE LA SESION
	
	function aumentarTiempo (sesion){
		tiempoSession = true;
		sesion++;
		var maxTiempoSesion = '<?php echo ini_get('session.gc_maxlifetime');?>'
		var tiempoAlerta = maxTiempoSesion * 0.20; //20% DEL TIEMPO DE SESION PARA LA CONFIRMACION
 		var tiempoDiferencia = maxTiempoSesion - tiempoAlerta;
		if(sesion >= tiempoDiferencia){
			verificarPopUpSession('../extender_sesion.php');
			tiempoSession = false;
			popupSession = null;
			return;
		}
		  window.setTimeout(function() {
			  aumentarTiempo(sesion);
			  },1000);
	}

</script>
<?php 
} 
?>