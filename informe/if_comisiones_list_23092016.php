<?php
require_once("../connections/conex.php");
include('../js/libGraficos/Code/PHP/Includes/FusionCharts.php');
include('../js/libGraficos/Code/PHP/Includes/FC_Colors.php');

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("if_comisiones_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_if_comisiones_list.php");

require_once("../controladores/ac_pg_calcular_comision.php");
require_once("../controladores/ac_pg_calcular_comision_servicio.php");

//$xajax->setFlag('debug',true); 
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Informe Gerencial - Comisiones</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleInforme.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragInforme.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script src="../js/login-modernizr/modernizr.custom.63321.js"></script>
    <link rel="stylesheet" type="text/css" href="../js/login-modernizr/font-awesome.css" />
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblListaComisionDetalle').style.display = 'none';
		byId('tblComisionProduccion').style.display = 'none';
		byId('tblListaNumCuenta').style.display = 'none';
		
		if (verTabla == "tblListaComisionDetalle") {
			xajax_formComisionDetalle(valor);
			
			tituloDiv1 = 'Editar Unidad Básica';
		} else if (verTabla == "tblComisionProduccion") {
			xajax_formComisionProduccion(xajax.getFormValues('frmBuscar'));
			
			tituloDiv1 = 'Comisión por Producción';
		} else if (verTabla == "tblListaNumCuenta") {
			xajax_cargarLstBanco(); 
			xajax_cargarLstTipoCuenta();
			xajax_cargarLstMoneda();
			xajax_listaCuenta(0, "nombreBanco", "ASC", "");
			
			tituloDiv1 = 'Selecciones el numero de cuenta para la carta de aprobación';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblUnidadBasica") {
			byId('txtNombreUnidadBasica').focus();
			byId('txtNombreUnidadBasica').select();
		}
	}
	
	function validarEliminar(idComisionEmpleado){
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarComision(idComisionEmpleado,xajax.getFormValues('frmListaComisiones'));
		}
	}
	
	function validarFrmComisionProduccion() {
		error = false;
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (confirm('¿Seguro desea guardar el(los) registro(s)?') == true) {
				byId('btnGuardarComisionProduccion').disabled = true;
				byId('btnCancelarComisionProduccion').disabled = true;
				xajax_guardarComisionProduccion(xajax.getFormValues('frmComisionProduccion'), xajax.getFormValues('frmBuscar'));
			}
		}
	}
	</script>
    
    <style>
	body {
		background: #365A96 url(../img/login/blurred<?php echo rand(1, 13); ?>.jpg) no-repeat center top;
		background-attachment:fixed;
		-webkit-background-size: cover;
		-moz-background-size: cover;
		background-size: cover;
		padding:10px;
	}
	</style>
</head>

<body>
<div>
	<div><?php include("banner_informe.php"); ?></div>
    
    <div id="divInfo" style="text-align:center">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaInforme">Comisiones</td>
        </tr>
        <tr>
            <td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    	<button type="button" onclick="xajax_imprimirComisiones(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaNumCuenta');">
                        <button type="button" >
                            <table align="center" cellpadding="0" cellspacing="0">
                            <tr>
                                <td>&nbsp;</td>
                                <td><img src="../img/iconos/page_white_acrobat.png"/></td>
                                <td>&nbsp;</td>
                                <td>PDF Resumen Comisiones</td>
                            </tr>
                            </table>
                        </button>
                    </a>
                    	<button type="button" onclick="xajax_exportarComisiones(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblComisionProduccion');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/application_view_columns.png" title="Nuevo"/></td><td>&nbsp;</td><td>Comisión por Producción</td></tr></table></button>
                    </a>
					</td>
				</tr>
				</table>
                
            <form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right">
                <tr align="left">
                    <td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa"></td>
                    <td align="right" class="tituloCampo">Mes - Año:</td>
                    <td><input type="text" id="txtFecha" name="txtFecha" autocomplete="off" size="10" style="text-align:center"/></td>
                </tr>
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Cargo:</td>
                    <td id="tdlstCargo"></td>
                	<td align="right" class="tituloCampo" width="120">Empleado:</td>
                    <td id="tdlstEmpleado"></td>
                	<td align="right" class="tituloCampo" width="120">Módulo:</td>
                    <td id="tdlstModulo"></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarComision(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); $('btnBuscar').click();">Limpiar</button>
					</td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaComisiones" name="frmListaComisiones" class="form-3" style="margin:0">
            	<div id="divListaComisiones" style="width:100%">
                    <table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                    <tr>
                        <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                        <td align="center">Ingrese los datos de las Comisiones a Buscar</td>
                    </tr>
                    </table>
                </div>
            </form>
            </td>
        </tr>
        </table>
	</div>
	
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
    
    <div id="tblListaComisionDetalle" style="max-height:500px; overflow:auto; width:960px">
        <table border="0" width="100%">
        <tr>
            <td>
                <form id="frmListaComisionDetalle" class="form-3" style="margin:0"></form>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <button type="button" id="btnCancelar" name="btnCancelar" class="close">Cerrar</button>
            </td>
        </tr>
        </table>
	</div>
    
<form id="frmComisionProduccion" name="frmComisionProduccion" onsubmit="return false;" style="margin:0px">
    <div id="tblComisionProduccion" style="max-height:500px; overflow:auto; width:960px">
        <table border="0" width="100%">
        <tr>
            <td><div id="divListaComisionProduccion" style="background-color:#333; width:100%"></div></td>
        </tr>
        <tr>
            <td align="right"><hr>
                <button type="submit" id="btnGuardarComisionProduccion" name="btnGuardarComisionProduccion"  onclick="validarFrmComisionProduccion();">Guardar</button>
                <button type="button" id="btnCancelarComisionProduccion" name="btnCancelarComisionProduccion" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
    </div>
</form>
    
    <div id="tblListaNumCuenta" style="max-height:500px; overflow:auto; width:960px">
        <table border="0" width="100%">
        <tr><td>&nbsp;</td></tr>
        <tr>
            <td align="right">
                <form id="frmBuscarCuenta" name="frmBuscarCuenta" onsubmit="return false;" style="margin:0px">
                    <table border="0">
                        <tr>
                            <td class="tituloCampo" align="right">Banco:</td>
                            <td id="tdLstBanco" colspan="5"></td>
                         </tr>
                         <tr>
                            <td class="tituloCampo" align="right">Tipo Cuenta:</td>
                            <td id="tdLstTipoCuenta"></td>
                            <td class="tituloCampo" align="right">Moneda:</td>
                            <td id="tdLstMoneda"></td>
                            <td class="tituloCampo" align="right">Criterio:</td>
                            <td ><input id="textCriterio" name="textCriterio" size="35"/></td>
                        </tr>
                        <tr>
                            <td colspan="6" align="right">
                                <button id="btnBuscarCuenta" name="btnBuscarCuenta" onclick="xajax_buscarCuenta(xajax.getFormValues('frmBuscarCuenta'))">Buscar</button>
                                <button id="btnLimpiar" name="btnLimpiar" onclick="document.forms['frmBuscarCuenta'].reset();byId('btnBuscarCuenta').click();">Limpiar</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </td>
        </tr>
        <tr><td>&nbsp;</td></tr>
        <tr>
            <td><div id="divListaNumCuenta" style="background-color:#333; width:100%"></div></td>
        </tr>
        <tr>
            <td align="right"><hr>
                <button type="button" id="btnCancelarListNumCuenta" name="btnCancelarListNumCuenta" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
    </div>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <table border="0" width="360">
    <tr>
    	<td align="right" class="tituloCampo">Porcentaje:</td>
        <td><input type="text" id="txtPorcentaje" name="txtPorcentaje"/></td>
    </tr>
    <tr>
    	<td align="right" colspan="2"><hr>
            <button type="button" id="btnCancelar2" name="btnCancelar2" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script type="text/javascript">
byId('txtFecha').className = "inputHabilitado";

byId('txtFecha').value = "<?php echo date("m-Y"); ?>";

window.onload = function(){
	jQuery(function($){
	   $("#txtFecha").maskInput("<?php echo "99-9999"; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFecha",
		dateFormat:"<?php echo "%m-%Y"; ?>"
	});
};

function openImg(idObj) {
	var oldMaskZ = null;
	var $oldMask = $(null);
	
	$(".modalImg").each(function() {
		$(idObj).overlay({
			//effect: 'apple',
			oneInstance: false,
			zIndex: 10100,
			
			onLoad: function() {
				if ($.mask.isLoaded()) {
					oldMaskZ = $.mask.getConf().zIndex; // this is a second overlay, get old settings
					$oldMask = $.mask.getExposed();
					$.mask.getConf().closeSpeed = 0;
					$.mask.close();
					this.getOverlay().expose({
						color: '#000000',
						zIndex: 10090,
						closeOnClick: false,
						closeOnEsc: false,
						loadSpeed: 0,
						closeSpeed: 0
					});
				} else { // ABRE LA PRIMERA VENTANA
					this.getOverlay().expose({
						color: '#000000',
						zIndex: 10090,
						closeOnClick: false,
						closeOnEsc: false
					});
				} // Other onLoad functions
			},
			onClose: function() {
				$.mask.close();
				if ($oldMask != null) { // re-expose previous overlay if there was one
					$oldMask.expose({
						color: '#000000',
						zIndex: oldMaskZ,
						closeOnClick: false,
						closeOnEsc: false,
						loadSpeed: 0
					});
					
					$(".apple_overlay").css("zIndex", oldMaskZ + 2); // Assumes the other overlay has apple_overlay class
				}
			}
		}).load();
	});
}

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', 'byId(\'btnBuscar\').click();\"');
xajax_cargaLstCargo();
xajax_cargaLstVendedor();
xajax_cargaLstModulo();

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>