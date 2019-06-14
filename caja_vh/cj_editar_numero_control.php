<?php
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cj_editar_numero_control"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cj_editar_numero_control.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. <?php echo $nombreCajaPpal; ?> - Edición Número de Control</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	
	<link rel="stylesheet" type="text/css" href="../js/domDragCaja.css"/>
	<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
	
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
	
	<script>
	function formListaEmpresa(nomObjeto, objDestino, nomVentana) {
		openImg(nomObjeto);
		
		document.forms['frmBuscarEmpresa'].reset();
		
		byId('hddObjDestino').value = objDestino;
		byId('hddNomVentana').value = nomVentana;
		
		byId('btnBuscarEmpresa').click();
		
		byId('tblListaEmpresa').style.display = '';
		
		byId('tdFlotanteTitulo2').innerHTML = "Empresas";
		
		byId('txtCriterioBuscarEmpresa').focus();
		byId('txtCriterioBuscarEmpresa').select();
	}
		
	function validarGuardar() {
		if (validarCampo('txtNumeroActual','t','') == true) {
			xajax_editarNumero(xajax.getFormValues('frmBuscar'));
		} else {
			validarCampo('txtNumeroActual','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarNro(){
		if (validarCampo('txtNumeroActual','t','') == true){
		 	if (confirm("Seguro que desea Editar el Numero de Control?"))
			 	xajax_editarNumero(xajax.getFormValues('frmNumeroControl'),xajax.getFormValues('frmBuscar'))
		 } else {
			validarCampo('txtNumeroActual','t','');
			
			alert("Los campos señalados en rojo son requeridos");
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cj.php"); ?></div>
    
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaCaja">Edición del Número de Control</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
			<form id="frmBuscar" name="frmBuscar" style="margin:0">
				<table align="right" border="0" width="100%">
				<tr id="trSelEmpresa">
					<td align="left">
						<table cellpadding="0" cellspacing="0">
						<tr>
							<td align="right" class="tituloCampo" width="120">Empresa:</td>
							<td>
								<input type="text" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresa(this.value);" size="6" style="text-align:right;"/></td>
							<td>
								<input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/>
							</td>
						</tr>
						</table>
					</td>
				</tr>
				<tr id="trNroControl">
					<td align="left">
                        <table border="0">
                        <tr id="trEmpresa" align="left">
                            <td align="right" class="tituloCampo" width="120">Empresa:</td>
                            <td id="tdlstEmpresa" colspan="3"></td>
                        </tr>
                        </table>
					</td>
				</tr>
				</table>
			</form>
			</td>
		</tr>
		<tr>
			<td id="tdListadoNroControl"></td>
		</tr>
		<tr>
			<td align="center">
				<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25">
						<img src="../img/iconos/ico_info.gif" width="25"/>
					</td>
					<td align="center">
						<table>
						<tr>
							<td>El formato para el Nro. de control es: 00-000000 y se aplicará para la facturación.</td>
						</tr>
						</table>
					</td>
				</tr>
				</table>
			</td>
		</tr>
		</table>
	</div>
    
	<div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<!--LISTADO DE PLANILLAS-->
<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
<form id="frmNumeroControl" name="frmNumeroControl" onsubmit="return false;" style="margin:0">
	<table border="0" id="tblListaArtPedido" width="500">
    <tr align="left">
        <td align="right" class="tituloCampo" width="120">Descripción:</td>
        <td><input type="text" id="txtDescripcion" name="txtDescripcion" style="text-align:right" size="25" readonly="readonly"/></td>
    </tr>
    <tr align="left">
        <td align="right" class="tituloCampo" width="120">Nro. Inicial:</td>
        <td><input type="text" id="txtNumeroInicial" name="txtNumeroInicial" style="text-align:right" size="12" readonly="readonly"/></td>
        <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Nro. Actual:</td>
        <td><input type="text" id="txtNumeroActual" name="txtNumeroActual" style="text-align:right" size="12" onkeypress="return validarSoloNumeros(event);"/></td>
    </tr>
	<tr>
		<td align="right" colspan="4"><hr>
			<input type="hidden" id="hddIdEmpresaNumeracion" name="hddIdEmpresaNumeracion"/>
			<button type="button" id="btnEditarNro" name="btnEditarNro" onclick="validarNro();" class="puntero"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
			<button type="button" id="btnCancelar" name="btnCancelar" onclick="byId('divFlotante').style.display='none';"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
		</td>
	</tr>
	</table>
</form>
</div>

<!--EDITAR NRO. PLANILLA-->
<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
	<table border="0" width="100%">
    <tr>
        <td align="left">
            <form id="frmNroPlanilla" name="frmNroPlanilla" style="margin:0">
            <table border="0" width="100%">
            <tr>
                <td>
                    <table border="0" width="100%">
                    <tr id="trId">
                        <td align="right" class="tituloCampo">Nro. Planilla a modificar:</td>
                        <td align="left">
                            <input type="text" id="NroPlanilla" name="NroPlanilla" size="26" readonly="readonly"/>
                            <input type="hidden" id="hddIdBanco" name="hddIdBanco"/>
                            <input type="hidden" id="hddIdDeposito" name="hddIdDeposito"/>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" class="tituloCampo">Nro. Planilla:</td>
                        <td align="left"><input type="text" id="EditNroPlanilla" name="EditNroPlanilla" size="26"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnEditar" name="btnEditar" onclick="validarTodoForm2();">Guardar</button>
            <button type="button" id="btnCancelar" name="btnCancelar" onclick="byId('divFlotante2').style.display='none';">Cancelar</button>
        </td>
    </tr>
	</table>
</div>

<script>
xajax_cargarPagina('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstEmpresa();
xajax_asignarEmpresaUsuario('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', 'Empresa', 'ListaEmpresa');
xajax_cargarNumeroControl('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listadoNroControl(0,'nombreNumeraciones','ASC','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|');
</script>