<?php
require("../connections/conex.php");

session_start();

/* Validación del Módulo */
require('../inc_sesion.php');
if(!(validaAcceso("al_tipos_contrato_usuario_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

require("../controladores/ac_iv_general.php");
require("controladores/ac_al_tipos_contrato_usuario_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Alquiler - Tipos de Contrato por Usuario</title>
	<link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
	
	<link rel="stylesheet" type="text/css" href="../js/domDragAlquiler.css">
	<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblTipoContrato').style.display = 'none';
		byId('divPermisosUsuario').innerHTML = '';
		byId('hddIdDetalleEliminar').value = '';
		
		if (verTabla == "tblTipoContrato") {
			document.forms['frmTipoContratoUsuario'].reset();
			
			xajax_frmTipoContratoUsuario(valor);
			
			if (valor > 0) {				
				tituloDiv1 = 'Editar Tipo de Contrato';
				byId('hddIdEmpleado').className = 'inputInicial';
				byId('hddIdEmpleado').disabled = true;
				byId('btnListarEmpleado').disabled = true;
			} else {
				tituloDiv1 = 'Nuevo Tipo de Contrato';
				byId('hddIdEmpleado').className = 'inputHabilitado';
				byId('hddIdEmpleado').disabled = false;
				byId('btnListarEmpleado').disabled = false;
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor) {
		byId('tblListaEmpleado').style.display = 'none';
		
		if (verTabla == "tblListaEmpleado") {
			document.forms['frmBuscarEmpleado'].reset();
			
			byId('txtCriterioBuscarEmpleado').className = 'inputHabilitado';
			
			byId('btnBuscarEmpleado').click();
				
			tituloDiv2 = 'Empleados';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaEmpleado") {
			byId('txtCriterioBuscarEmpleado').focus();
			byId('txtCriterioBuscarEmpleado').select();
		}
	}
	
	function eliminarPermiso(idDetalleEliminar){		
		if(idDetalleEliminar != ""){		
			hddIdDetalleEliminar = $("#hddIdDetalleEliminar").val();
			if(hddIdDetalleEliminar == ""){
				$("#hddIdDetalleEliminar").val(idDetalleEliminar);
			}else{
				$("#hddIdDetalleEliminar").val(hddIdDetalleEliminar+","+idDetalleEliminar);
			}
		}
	}
	
	function validarfrmTipoContratoUsuario() {
		error = false;
				
		if (!(validarCampo('hddIdUsuario','t','') == true 
		)) {
			validarCampo('hddIdUsuario','t','');			
			
			error = true;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			xajax_guardarTipoContratoUsuario(xajax.getFormValues('frmTipoContratoUsuario'));
		}
	}
	
	function validarEliminar(idPrecio){
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarTipoContratoUsuario(idPrecio);
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_alquiler.php"); ?></div>
	
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaAlquiler">Tipos de Contrato por Usuario</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
				<table align="left" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblTipoContrato');">
							<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
						</a>
					</td>
				</tr>
				</table>
                
            <form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">
                <tr align="left">
					<td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td id="tdlstEmpresaBuscar"></td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarTipoContratoUsuario(xajax.getFormValues('frmBuscar'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td>            
                <div id="divListaTipoContratoUsuario" style="width:100%"></div>
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
    
<form id="frmTipoContratoUsuario" name="frmTipoContratoUsuario" style="margin:0" onsubmit="return false;">
	<table border="0" id="tblTipoContrato" width="460">
    <tr>
        <td>
            <table border="0" width="100%">
            <tr align="left">
            	<td align="right" width="15%" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empleado:</td>
                <td width="85%">
                	<table cellspacing="0" cellpadding="0">
                    <tr>
                    	<td><input type="text" style="text-align:right;" size="6" onblur="xajax_asignarEmpleado(this.value, 'false');" name="hddIdEmpleado" id="hddIdEmpleado" class="inputHabilitado"></td>
                        <td>
                        	<a onclick="abrirDivFlotante2(this, 'tblListaEmpleado')" rel="#divFlotante2" id="aListarEmpleado" class="modalImg"><button title="Listar" name="btnListarEmpleado" id="btnListarEmpleado" formnovalidate="formnovalidate" type="button"><img src="../img/iconos/help.png"></button>
                            </a>
                        </td>
                        <td><input type="text" size="30" readonly="readonly" name="txtNombreEmpleado" id="txtNombreEmpleado"></td>
                    </tr>
                    </table>
				</td>
            </tr>
            <tr align="left">
            	<td align="right" width="25%" class="tituloCampo"><span class="textoRojoNegrita">*</span>Usuario:</td>
                <td width="75%">
                	<table cellspacing="0" cellpadding="0">
                    <tr>
                    	<td><input type="text" style="text-align:right;" size="6" readonly="readonly" name="hddIdUsuario" id="hddIdUsuario" class="inputInicial">
                        </td>
                        <td>&nbsp;</td>
                        <td><input type="text" size="35" readonly="readonly" name="txtNombreUsuario" id="txtNombreUsuario"></td>
                    </tr>
                    </table>
				</td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td colspan="10"><div id="divPermisosUsuario" align="left" style="max-height: 400px; overflow:auto;"></div></td>
    </tr>
    <tr>
        <td align="right"><hr>
        	<input type="hidden" name="hddIdDetalleEliminar" id="hddIdDetalleEliminar" value="">
            <button type="submit" onclick="validarfrmTipoContratoUsuario();">Guardar</button>
            <button type="button" id="btnCancelarTipoContrato" name="btnCancelarTipoContrato" class="close">Cancelar</button>
        </td>
    </tr>
    </table>

</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <table border="0" id="tblListaEmpleado" width="760">
    <tr>
        <td>
        <form id="frmBuscarEmpleado" name="frmBuscarEmpleado" style="margin:0" onsubmit="return false;">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarEmpleado" name="txtCriterioBuscarEmpleado" onkeyup="byId('btnBuscarEmpleado').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarEmpleado" name="btnBuscarEmpleado" onclick="xajax_buscarEmpleado(xajax.getFormValues('frmBuscarEmpleado'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarEmpleado'].reset(); byId('btnBuscarEmpleado').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListaEmpleado" name="frmListaEmpleado" style="margin:0" onsubmit="return false;">
            <div id="divListaEmpleado" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaEmpleado" name="btnCancelarListaEmpleado" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>

byId('txtCriterio').className = 'inputHabilitado';

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

xajax_cargaLstEmpresaFinal("<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>","onChange=\"byId('btnBuscar').click()\"","lstEmpresaBuscar");
xajax_listaTipoContratoUsuario(0, 'id_tipo_contrato_usuario', 'ASC', "<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>");

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>