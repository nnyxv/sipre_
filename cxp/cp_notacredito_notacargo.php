<?php
require_once("../connections/conex.php");

session_start();

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_cp_notacredito_notacargo.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Cuentas por Pagar - Nota de Credito</title>
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
 
 	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/> 
    <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>

	<style type="text/css">
    .root {
	    background-color:#FFFFFF;
    	border:6px solid #999999;
		font-family:Verdana, Arial, Helvetica, sans-serif;
		font-size:11px;
		max-width:1000px;
		position:absolute;
    }
    
    .handle {
		padding:2px;
		background-color:#000066;
		color:#FFFFFF;
		font-weight:bold;
		cursor:move;
    }
    </style>
    
    <script>
	function validarTodoForm() {
		if (validarCampo('txtNumeroNotaCredito','t','') == true
		&& validarCampo('txtFechaProveedorNotaCredito','t','') == true
		&& validarCampo('txtFechaOrigenNotaCredito','t','') == true
		&& validarCampo('txtObservacionNotaCredito','t','') == true
		&& validarCampo('slctDepartamentoNotaCredito','t','listaExceptCero') == true
		&& validarCampo('slctAplicaLibrosCredito','t','listaExceptCero') == true) {
			xajax_guardarDatos(xajax.getFormValues('frmDatosFactura'),xajax.getFormValues('frmDatosNotaCredito'),xajax.getFormValues('frmProveedor'),xajax.getFormValues('frmTotalNotaCredito'));
		} else {
			validarCampo('txtNumeroNotaCredito','t','');
			validarCampo('txtFechaProveedorNotaCredito','t','');
			validarCampo('txtFechaOrigenNotaCredito','t','');
			validarCampo('txtObservacionNotaCredito','t','');
			validarCampo('slctDepartamentoNotaCredito','t','listaExceptCero')
			validarCampo('slctAplicaLibrosCredito','t','listaExceptCero')

			alert("Los campos señalados en rojo son requeridos");

			return false;
		}
	}
    </script>
</head>

<body>
<div id="divGeneralVehiculos">
	<div class="noprint">
    </div>
    
    <div id="divInfo" class="print">
        <table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaCuentasPorPagar">Nota de Credito</td>
        </tr>
        <tr>
        	<td id="tdTituloEmpresa">
            </td>
        </tr>
        <tr>
	        <td align="left"><input type="button" id="btnFacturas" name="btnFacturas" onclick="xajax_listadoFacturas(0,'','','' + '|' + -1);" value="Buscar Notas de Débito"/></td>
        </tr>
        <tr>
        	<td align="left">
            <form id="frmProveedor" name="frmProveedor" style="margin:0">
            	<table border="0" width="100%">
                <tr>
                	<td valign="top">
                    	<table border="0" width="100%">
                        <tr>
                            <td align="center" class="tituloArea" colspan="6">Datos del Proveedor</td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nombre / Razón Social:</td>
                          <td align="left" colspan="3"><input type="text" id="txtIdProv" name="txtIdProv" readonly="readonly" size="15"/> <input type="text" id="txtNombreProv" name="txtNombreProv" readonly="readonly" size="40"/></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanProvCxP; ?>:</td>
                            <td align="left"><input type="text" id="txtRifProv" name="txtRifProv" readonly="readonly" size="26"/></td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo" width="17%">Persona Contacto:</td>
                          <td align="left" width="18%"><input type="text" id="txtContactoProv" name="txtContactoProv" readonly="readonly" size="26"/></td>
                          <td align="right" width="11%"></td>
                          <td align="left"  width="27%"></td>
                          <td align="right" class="tituloCampo" width="10%">Email:</td>
                          <td align="left" width="17%"><input type="text" id="txtEmailContactoProv" name="txtEmailContactoProv" readonly="readonly" size="26"/></td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo" rowspan="2"><span class="textoRojoNegrita">*</span>Dirección:</td>
                            <td align="left" colspan="3" rowspan="2"><textarea cols="59" id="txtDireccionProv" name="txtDireccionProv" readonly="readonly" rows="2"></textarea></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Teléfono:</td>
                            <td align="left"><input type="text" id="txtTelefonosProv" name="txtTelefonosProv" readonly="readonly" size="26"/></td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo">Fax:</td>
                            <td align="left"><input type="text" id="txtFaxProv" name="txtFaxProv" readonly="readonly" size="26"/></td>
                        </tr>
                        </table>
					</td>
				</tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td>
              <form id="frmDatosFactura" name="frmDatosFactura">
                <table border="0" width="100%">
                <tr>
                  <td align="center" class="tituloArea" colspan="7">Datos de la Nota de Débito</td>
                </tr>
                <tr>
                  <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Nro. Nota de Débito:</td>
                  <td align="left" width="15%"><input type="text" id="txtNumeroFacturaProveedor" name="txtNumeroFacturaProveedor" readonly="readonly" size="20"/><input type="hidden" id="hddObjIdFactura" name="hddObjIdFactura" readonly="readonly"/></td>
                  <td align="right" class="tituloCampo" width="13%">Nº Control:</td>
                  <td align="left" width="28%"><input type="text" id="txtNumeroControl" name="txtNumeroControl" readonly="readonly" size="20"/></td>
                  <td align="right" class="tituloCampo" width="14%"><span class="textoRojoNegrita">*</span>Fecha Nota de Débito:</td>
              	  <td width="15%">
                    <div style="float:left"><input type="text" id="txtFechaProveedor" name="txtFechaProveedor" readonly="readonly" size="16"/></div>
                    <div style="float:left"><img src="../img/iconos/ico_date.png" id="imgFechaProveedorFactura" name="imgFechaProveedorFactura" class="puntero"></div>
                  </td>
                </tr>
                <tr>
                  <td align="right" class="tituloCampo">Tipo de Pago:</td>
                  <td align="left"><input type="text" id="txtTipoPago" name="txtTipoPago" readonly="readonly" size="16"/></td>
                  <td align="right" class="tituloCampo">Módulo:</td>
                  <td align="left"><label>
                    <input type="text" id="txtDepartamento" name="txtDepartamento" readonly="readonly" size="20"/>
                  </label></td>
                  <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Origen:</td>
                  <td><div style="float:left"><input type="text" id="txtFechaOrigen" name="txtFechaOrigen" readonly="readonly" size="16"/></div>
                        <div style="float:left"><img src="../img/iconos/ico_date.png" id="imgFechaOrigenFactura" name="imgFechaOrigenFactura" class="puntero"></div>
                  </td>
                </tr>
                <tr>
                	<td width="15%" rowspan="2" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Observacion:</td>
                <td colspan="3" rowspan="2" align="left"><label>
                    <textarea name="txtObservacionFactura" id="txtObservacionFactura" readonly="readonly" cols="59" rows="2"></textarea>
                    </label></td>
                    <td align="right" class="tituloCampo">Aplica Libros:</td>
                    <td width="15%" align="left"><label>
                      <input type="text" id="txtAplicaLibros" name="txtAplicaLibros" readonly="readonly" size="16"/>
                      <input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa" />
                    </label></td>
                </tr>
                <tr>
                  <td align="right"><input type="hidden" id="hddObjtxtSubTotal" name="hddObjtxtSubTotal"/> <input type="hidden" id="hddObjtxtSubTotalDescuento" name="hddObjtxtSubTotalDescuento"/> <input type="hidden" id="hddObjtxtMontoExonerado" name="hddObjtxtMontoExonerado"/> <input type="hidden" id="hddObjtxtMontoExento" name="hddObjtxtMontoExento"/></td>
                  <td>&nbsp;</td>
                </tr>
                <hr>
                </table>
            </form>
          </td>
        </tr>
        <tr>
            <td>
              <form id="frmDatosNotaCredito" name="frmDatosNotaCredito">
                <table border="0" width="100%">
                <tr>
                  <td align="center" class="tituloArea" colspan="7">Datos de la Nota de Credito</td>
                </tr>
                <tr>
                  <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Nº Nota de Credito:</td>
                  <td align="left" width="15%"><input type="text" id="txtNumeroNotaCredito" name="txtNumeroNotaCredito"  size="20"/></td>
                  <td align="right" class="tituloCampo" width="13%">Nº Control:</td>
                  <td align="left" width="28%"><input type="text" id="txtNumeroControlNotaCredito" name="txtNumeroControlNotaCredito"  size="20"/></td>
                  <td align="right" class="tituloCampo" width="14%"><span class="textoRojoNegrita">*</span>Fecha Nota Credito:</td>
              	  <td width="15%">
                    <div style="float:left"><input type="text" id="txtFechaProveedorNotaCredito" name="txtFechaProveedorNotaCredito"  size="16"/></div>
                            <div style="float:left"><img src="../img/iconos/ico_date.png" id="imgFechaProveedor" name="imgFechaProveedor" class="puntero"></div>
						<script type="text/javascript">
                        Calendar.setup({
							inputField : "txtFechaProveedorNotaCredito",
							ifFormat : "<?php echo spanDatePick; ?>",
							button : "imgFechaProveedor"
                        });
                        </script>                 
                  </td>
                </tr>
                <tr>
                  <td align="right" class="tituloCampo">Módulo:</td>
                  <td align="left"><select name="slctDepartamentoNotaCredito" id="slctDepartamentoNotaCredito">
                    <option value="" selected="selected">Seleccione</option>
                    <option value="0">Repuestos</option>
                    <option value="1">Servicios </option>
                    <option value="2">Vehiculos</option>
                    <option value="3">Administracion</option>
                  </select></td>
                  <td align="right">&nbsp;</td>
                  <td align="left"><label></label></td>
                  <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Origen:</td>
                  <td><div style="float:left"><input type="text" id="txtFechaOrigenNotaCredito" name="txtFechaOrigenNotaCredito"  size="16"/></div>
                        <div style="float:left"><img src="../img/iconos/ico_date.png" id="imgFechaOrigen" name="imgFechaOrigen" class="puntero"></div>
               		<script type="text/javascript">
                        Calendar.setup({
							inputField : "txtFechaOrigenNotaCredito",
							ifFormat : "<?php echo spanDatePick; ?>",
							button : "imgFechaOrigen"
                        });
                        </script>                 
                  </td>
                </tr>
                <tr>
                	<td width="15%" rowspan="2" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Observacion:</td>
              <td colspan="3" rowspan="2" align="left"><label>
                      <textarea name="txtObservacionNotaCredito" id="txtObservacionNotaCredito"  cols="59" rows="2"></textarea>
                    </label></td>
                    <td align="right" class="tituloCampo">Aplica Libros:</td>
                    <td width="15%" align="left"><label>
                    <select name="slctAplicaLibrosCredito" id="slctAplicaLibrosCredito">
                    	<option value="" selected="selected">Seleccione</option>
                        <option value="1">SI</option>
                        <option value="0">NO</option>
                    </select>
                  </label></td>
                </tr>
                <tr>
                  <td align="right">&nbsp;</td>
                  <td>&nbsp;</td>
                </tr>
                <hr>
                </table>
            </form>
          </td>
        </tr>
        <tr>
          <td align="right">
            <form id="frmTotalNotaCredito" name="frmTotalNotaCredito" style="margin:0">
              <hr />
              <table border="0" width="100%">
                <tr>
                	<td align="right" id="tdGastos" valign="top" width="45%"><br><br><br><br><br><br><br><br><br></td>
                    <td rowspan="2" width="55%">
                      <table border="0" width="100%">
                        
                         
                        <tr align="right">
                            <td class="tituloCampo" width="37%">Sub-Total:</td>
                            <td width="26%"></td>
                            <td width="12%"></td>
                            <td align="right" width="25%"><input type="text" id="txtSubTotal" name="txtSubTotal" readonly="readonly"  size="17" style="text-align:right" /></td>
                        </tr>
                        <tr id="tdDescuento" align="right">
                            <td class="tituloCampo">Descuento:</td>
                            <td></td>
                            <td></td>
                            <td align="right"><input type="text" id="txtSubTotalDescuento" name="txtSubTotalDescuento" readonly="readonly" onkeyup="xajax_sumaMontos(xajax.getFormValues('frmTotalFactura'));" size="17" style="text-align:right"/></td>
                        </tr>
                        <tr id="trIvaFletes" align="right">
                        	<td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <!--AQUI SE INSERTAN LAS FILAS PARA EL IVA-->
                        <tr align="right" id="trGastosSinIva">
                            <td class="tituloCampo">Monto Exonerado:</td>
                          	<td></td>
                            <td></td>
                            <td align="right"><input type="text" id="txtMontoExonerado" name="txtMontoExonerado" readonly="readonly" onkeyup="xajax_sumaMontos(xajax.getFormValues('frmTotalFactura'));" size="17" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                         <td class="tituloCampo">Monto Exento:</td>
                          <td></td>
                            <td></td>
                            <td align="right"><input type="text" id="txtMontoExento" name="txtMontoExento" readonly="readonly" size="17" onkeyup="xajax_sumaMontos(xajax.getFormValues('frmTotalFactura'));" style="text-align:right"/></td>
                        </tr>
                        <tr>
                            <td colspan="4"><hr></td>
                        </tr>
                        <tr align="right" id="trNetoOrden">
                            <td class="tituloCampo">Neto Nota Credito:</td>
                            <td></td>
                            <td></td>
                            <td align="right"><input type="text" id="txtTotalNotaCredito" name="txtTotalNotaCredito" readonly="readonly" size="17" style="text-align:right"/></td>
                        </tr>
                    </table>					</td>
				</tr>
                <tr>
                	<td class="divMsjInfo2">
                    	<table cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                            <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                            <td align="center">
                                <table>
                                <tr>
                                    <td><img src="../img/iconos/accept.png" /></td><td>Gastos que llevan Impuesto</td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                	<td colspan="2"></td>
				</tr>
                <tr>
                	<td colspan="2"><input type="hidden" id="hddObjMontos" name="hddObjMontos" readonly="readonly"/><input type="hidden" id="hddObjGastos" name="hddObjGastos" readonly="readonly"/><input type="hidden" id="hddtxtTotalNotaCredito" name="hddtxtTotalNotaCredito" readonly="readonly"/></td>
                </tr>
              </table>
			</form>
		  </td>
        </tr>
        <tr>
        	<td align="right"><hr>
                <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarTodoForm();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
                <button type="button" id="btnCancelar" name="btnCancelar" onclick="top.history.back();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_error.gif"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
			</td>
        </tr>
        </table>
    </div>
	
    <div class="noprint">
    </div>
</div>
</body>
</html>

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
 <table border="0" id="tblListados" style="display:none" width="980px">
    <tr>
    	<td id="tdListado">
        	<table width="100%">
            <tr class="tituloColumna">
            	<td>Orden</td>
                <td>Nº Orden Propio</td>
                <td>Nº Referencia</td>
                <td>Fecha</td>
                <td>Proveedor</td>
                <td>Articulos</td>
                <td>Pedidos</td>
                <td>Pendientes</td>
                <td>Total</td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right">
	        <hr>
            <input type="button" id="" name="" onclick="$('divFlotante').style.display='none';" value="Cancelar">
        </td>
    </tr>
    </table>
       
   
   
</div>
<script language="javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
</script>