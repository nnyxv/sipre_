<?php
require_once("../connections/conex.php");

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cp_libro_compra_imp.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Cuentas por Pagar - Libro Compra</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	
    <link rel="stylesheet" type="text/css" href="../js/domDragCuentasPorPagar.css"/>
	<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>

</head>
<body>
	<div id="divGeneralPorcentaje">
		<table width="100%">
		<tr>
			<td id="tdEncabezadoImprimir"></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td id="tdLibro"></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td id="tdCuadroLibro"></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td id="tdCuadroLibroResumen"></td>
		</tr>
        <tr class="noprint">
        	<td align="center" colspan="2"><hr>
            	<?php 
				$url = ($_SERVER['REQUEST_URI']);
				$urlId = explode("?", $url);
				$urlExcel = end($urlId);
				
				if (in_array(idArrayPais,array(0))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
					$display = "style=\"display:none\"";
				} else {
					$display = "";
				} ?>
      	 	    <button type="button" id="btnExportar" onclick="window.open('reportes/cp_libro_compra_excel.php?<?php echo $urlExcel; ?>','_blank');" <?php echo $display; ?>><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
				<button type="button" onclick="window.print();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_print.png"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button>
				<button type="button" id="btnCancelar" name="btnCancelar" onclick="xajax_volver();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_error.gif"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
			</td>
		</tr>
		</table>
    </div>

    <div id="load_animate">&nbsp;</div>
    
    <script type="text/javascript">	
    var cerrarVentana = true;
    window.onbeforeunload = function() {
        if (cerrarVentana == false) {
            return "Se recomienda CANCELAR este cuadro de mensaje\n\nDebe Cerrar la Ventana para efectuar las transacciones efectivamente";
        }
    }
    
    if (typeof(xajax) != 'undefined') {
        if(xajax != null){
            xajax.callback.global.onRequest = function() {
                //xajax.$('loading').style.display = 'block';
                document.getElementById('load_animate').style.display='';
            }
            xajax.callback.global.beforeResponseProcessing = function() {
                //xajax.$('loading').style.display='none';
                document.getElementById('load_animate').style.display='none';
            }
        }
    }
    document.getElementById('load_animate').style.display='none';
    </script>
</body>
</html>

<script>
xajax_listaLibro(0,'','','<?php echo $_GET['idEmpresa']; ?>|<?php echo $_GET['f1']; ?>|<?php echo $_GET['f2']; ?>|<?php echo $_GET['idModulo']; ?>|<?php echo $_GET['lstFormatoNumero']; ?>|<?php echo $_GET['lstFormatoTotalDia']; ?>');
</script>