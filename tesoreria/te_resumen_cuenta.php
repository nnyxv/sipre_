<?php
require("../connections/conex.php");

session_start();

/* Validación del Módulo */
require('../inc_sesion.php');
if(!(validaAcceso("te_resumen"))) {
	echo "<script>alert('Acceso Denegado'); window.location.href = 'index.php';</script>";
}
/* Fin Validación del Módulo */

require('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

require("../controladores/ac_iv_general.php");
require("controladores/ac_te_resumen_cuenta.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    
    <title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Resumen de Cuentas</title>
    <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
            
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    <link rel="stylesheet" type="text/css" href="../js/domDragTesoreria.css"/>
    
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
    <script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	
    <link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
    <script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    <script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <style type="text/css">
		.seccion{
			-webkit-box-shadow: 0px 10px 5px 0px rgba(50, 50, 50, 0.75);
			-moz-box-shadow:    0px 10px 5px 0px rgba(50, 50, 50, 0.75);
			box-shadow:         0px 10px 5px 0px rgba(50, 50, 50, 0.75);
		}
	</style>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_tesoreria.php"); ?></div>
	
    <table border="0" width="100%">
    <tr>
        <td class="tituloPaginaTesoreria">Resumen de Cuenta</td>
    </tr>
    <tr>
        <td>&nbsp;</td>
    </tr>
    </table>
    
    <input type="hidden" id="hddIdCuenta" name="hddIdCuenta" value="<?php echo $_GET['cast']; ?>" />
    
    <table align="center" width="100%">
    <tr>   
        <td width="50%" valign="top">   
            <div id="divInfo">   
                <fieldset><legend class="legend">Documentos Sin Aplicar</legend>
                <br />
                <table width="100%">
                <tr>
                    <td>
                        <table width="100%" cellspacing="0" cellpadding="0">
						<tr>
                            <td class="tituloCampo" width="25%"  align="right">Disponible Actual:</td>
                            <td id="tdSaldoLibro" width="25%" align="right"></td>
                            <td width="25%"></td>
                        </tr> 
                        <tr>
                            <td class="tituloCampo" width="25%"  align="right">Saldo Anterior:</td>
                            <td id="tdSaldoConciliado" width="25%" align="right"></td>
                            <td width="25%"></td>
                        </tr> 
                        </table>
                        <br />
                        <table border="1" class="tabla" cellspacing="2" width="100%">   
                        <tr>
                            <td class="tituloColumna" width="25%">Tipos de Documentos</td>
                            <td class="tituloColumna" width="25%">Montos Documentos</td>
                            <td class="tituloColumna" width="25%">Cantidad de Documentos</td>
                        </tr>  
                        <tr>
                            <td class="tituloCampo">Cheques:</td>
                            <td id="tdTotalCheques"></td>
                            <td id="tdTCheques" align="center"></td>
                        </tr>
                        <tr>
                            <td class="tituloCampo">Depositos:</td>
                            <td id="tdTotalDeposito"></td>
                            <td id="tdTDeposito" align="center"></td>
                        </tr>
                        <tr>
                            <td class="tituloCampo">Notas de Debitos:</td>
                            <td id="tdTotalDebitos"></td>
                            <td id="tdTDebitos" align="center"></td>
                        </tr>
                        <tr>
                            <td class="tituloCampo">Notas de Credito:</td>
                            <td id="tdTotalCredito"></td>
                            <td id="tdTCredito" align="center"></td>
                        </tr>
                         <tr>
                            <td class="tituloCampo">Transferencias:</td>
                            <td id="tdTotalTransferencia"></td>
                            <td id="tdTTransferencia" align="center"></td>
                        </tr>
                        
                        </table>
                        <br />
                        <table class="tabla" cellpadding="2" cellspacing="2" width="100%">
                        <tr>
                            <td class="tituloCampo" width="25%">Total Movimiento Sin Aplicar:</td>
                            <td id="tdMovNoApli" width="25%" align="right" border="1" class="tabla"></td>
                            <td width="25%"></td>
                        </tr>      
                        
                        </table>
                    </td>    
                </tr>   
                </table>
                </fieldset>  
                
                <br />
                
                 <fieldset><legend class="legend">Documentos Aplicados</legend>
                <br />
                <table width="100%">
                <tr>
                    <td>
                        <table border="1" class="tabla" cellspacing="2" width="100%">   
                        <tr>
                            <td class="tituloColumna" width="25%">Tipos de Documentos</td>
                            <td class="tituloColumna" width="25%">Montos Documentos</td>
                            <td class="tituloColumna" width="25%">Cantidad de Documentos</td>
                        </tr>  
                        <tr>
                            <td class="tituloCampo">Cheques:</td>
                            <td id="tdTotalChequesApl"></td>
                            <td id="tdTChequesApl" align="center"></td>
                        </tr>
                        <tr>
                            <td class="tituloCampo">Depositos:</td>
                            <td id="tdTotalDepositoApl"></td>
                            <td id="tdTDepositoApl" align="center"></td>
                        </tr>
                        <tr>
                            <td class="tituloCampo">Notas de Debitos:</td>
                            <td id="tdTotalDebitosApl"></td>
                            <td id="tdTDebitosApl" align="center"></td>
                        </tr>
                        <tr>
                            <td class="tituloCampo">Notas de Credito:</td>
                            <td id="tdTotalCreditoApl"></td>
                            <td id="tdTCreditoApl" align="center"></td>
                        </tr>
                         <tr>
                            <td class="tituloCampo">Transferencias:</td>
                            <td id="tdTotalTransferenciaApl"></td>
                            <td id="tdTTransferenciaApl" align="center"></td>
                        </tr>
                        
                        </table>
                        <br />
                        <table class="tabla" cellpadding="2" cellspacing="2" width="100%">
                        <tr>
                            <td class="tituloCampo" width="25%">Total Movimientos Aplicados:</td>
                            <td id="tdMovApli" width="25%" align="right" border="1" class="tabla"></td>
                            <td width="25%"></td>
                        </tr>      
                        
                        </table>
                    </td>    
                </tr>   
                </table>
                <br />
                </fieldset>   
            </div>
            
            
    	</td>
    	<td width="50%" valign="top">
            <div class="seccion">
                <div id="divFlotanteTitulo1" class="handle" style="cursor:default;"><table><tr><td>Cheques</td></tr></table></div>
                <table width="100%"><tr><td id="tdCheques"></td></tr></table>
            </div>
            
            <br />
            <div class="seccion">
                <div id="divFlotanteTitulo1" class="handle" style="cursor:default;"><table><tr><td>Depósitos</td></tr></table></div>
				<table width="100%"><tr><td id="tdDeposito"></td></tr></table>
            </div>
            
            <br />
            <div class="seccion">
                <div id="divFlotanteTitulo1" class="handle" style="cursor:default;"><table><tr><td>Notas de Débito</td></tr></table></div>
                <table width="100%"><tr><td id="tdDebito"></td></tr></table>
            </div>
            
            <br />
            <div class="seccion">
                <div id="divFlotanteTitulo1" class="handle" style="cursor:default;"><table><tr><td>Notas de Crédito</td></tr></table></div>
                <table width="100%"><tr><td id="tdCredito"></td></tr></table>
            </div>
            
            <br />
            <div class="seccion">
                <div id="divFlotanteTitulo1" class="handle" style="cursor:default;"><table><tr><td>Transferencias</td></tr></table></div>
                <table width="100%"><tr><td id="tdTransferencia"></td></tr></table>	  
            </div>
        </td>
    </tr>
    </table>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<script>
xajax_listarCheques(0,'','',document.getElementById("hddIdCuenta").value);
xajax_listarNotaDebito(0,'','',document.getElementById("hddIdCuenta").value);
xajax_listarNotaCredito(0,'','',document.getElementById("hddIdCuenta").value);
xajax_listarTransferencia(0,'','',document.getElementById("hddIdCuenta").value);
xajax_listarDeposito(0,'','',document.getElementById("hddIdCuenta").value);
xajax_cargarDatosCuenta(document.getElementById("hddIdCuenta").value);        
</script>