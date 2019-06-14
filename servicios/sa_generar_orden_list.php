<?php
require ("../connections/conex.php"); 

session_start();

/* Validación del Módulo */
require('../inc_sesion.php');
if(!(validaAcceso("sa_generar_orden_list"))) {//sa_generar_orden_list nuevo gregor //sa_facturacion_vale_salida antes 
	echo "
	<script>
		alert('Acceso Denegado');
		window.location.href = 'index.php';
	</script>";
}
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

require("controladores/ac_iv_general.php");
require("controladores/ac_sa_generar_orden_list.php");
require("../controladores/ac_pg_calcular_comision_servicio.php");

// MODIFICADO ERNESTO
if(file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")){
	include("../contabilidad/GenerarEnviarContabilidadDirecto.php");
}
// MODIFICADO ERNESTO

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        
        <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Generar A Caja / Vale de Salida</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
        
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    <link rel="stylesheet" type="text/css" href="css/sa_general.css" />
    
    <link rel="stylesheet" type="text/css" href="../js/domDragServicios.css">
    <script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
        
    <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/> 
    <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
    
    <script>
	function abrirCambioEstado() {
		$('estado_window').style.visibility = 'visible';
		$('estado_window').style.display = '';
		$('title_estado_window').innerHTML = "Cambio de Estado  (Orden Nro: "+document.form_estado.id_orden.value+")";
		centrarDiv($('estado_window'));
		
		xajax_selectEstadoOrden(document.form_estado.id_orden.value);
	}

	function abrirCliente(id) {
		$('id_cliente_pago').value = "";
		$('cedula_cliente').value = "";
		$('nombre_cliente').innerHTML = "";
		$('email_cliente').innerHTML = "";
		$('telefono_cliente').innerHTML = "";
		$('celular_cliente').innerHTML = "";
		
		$('cita').style.visibility = 'visible';
		$('cita').style.display = '';
		centrarDiv($('cita'));
		
		document.form_cambio_cliente.id_orden_cliente.value= id;
	}
	
	function abrirHora(id) {
		document.forms['form_entrega'].reset();
		
		$('hora_window').style.visibility = 'visible';
		$('hora_window').style.display = '';
		centrarDiv($('hora_window'));
		
		document.form_entrega.id_orden.value = id;
	}

	function abrirSobregiro() {
		$('clave_credito').value= "";
		
		$('window_credito').style.visibility = 'visible';
		$('window_credito').style.display = '';
		$('title_credito').innerHTML = "Introduzca la clave de sobregiro";
		centrarDiv($('window_credito'));
		
		$('clave_credito').focus();
	}
	
	function verificarSobregiro(pass, id){
		xajax_verificarPassSobregiro(pass, id);
	}

	function abrirStatus(id) {
		$('key_status').value= "";
		
		$('key_window_status').style.visibility = 'visible';
		$('key_window_status').style.display = '';
		$('key_title_status').innerHTML = "Introduzca la clave  (Orden Nro: "+id+")";
		centrarDiv($('key_window_status'));
		
		$('key_status').focus();
		document.form_estado.id_orden.value = id;
	}

	function cambiarEstado() {
		if (confirm("Seguro desea cambiar el estado de la orden?")) {
			xajax_actualizarStatusOrden(document.form_estado.id_orden.value, document.form_estado.selectEstadoOrden.value);
		}
	}
	
	function validarCambioCliente(id_orden, id_cliente) {
		if (id_cliente == "") {
			alert('Debe seleccionar un cliente');
		} else {
			xajax_guardarCliente(id_orden, id_cliente);
		}
	}
	
	function validarFormAprobacionOrden() {
		if (validarCampo('txtClaveAprobacion','t','') == true) {
			if(confirm("Esta seguro de Aprobar la Orden como finalizada?")) {
				xajax_aprobarOrden(xajax.getFormValues('frmClaveAprobacionOrden'));
			}
		} else {
			validarCampo('txtClaveAprobacion','t','');
				
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
        
                
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include("banner_servicios.php"); ?>
    </div>
	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaServicios">Generar A Caja / Vale de Salida</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
		</tr>
        <tr>
        	<td>
			<form id="frmBuscar" name="frmBuscar" onsubmit="$('btnBuscar').click(); return false;" style="margin:0">
            	<table border="0" align="right">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td colspan="6" id="tdlstEmpresa">
                        <!--<select id="lstEmpresa" name="lstEmpresa">
                            <option value="-1">[ Todos ]</option>
                        </select>-->
                    </td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="100">Desde:</td>
                    <td>
                    <div style="float:left">
                        <input type="text" id="txtFechaDesde" name="txtFechaDesde" readonly="readonly" size="10" style="text-align:center"/>
                    </div>
                    <div style="float:left">
                    	<img src="../img/iconos/ico_date.png" id="imgFechaDesde" name="imgFechaDesde" class="puntero noprint"/>
						<script type="text/javascript">
                            Calendar.setup({
                            inputField : "txtFechaDesde",
                            ifFormat : "%d-%m-%Y",
                            button : "imgFechaDesde"
                            });
						</script>
                    </div>
                    </td>
                    <td align="right" class="tituloCampo" width="100">Hasta:</td>
                    <td>
                    <div style="float:left">
                    	<input type="text" id="txtFechaHasta" name="txtFechaHasta" readonly="readonly" size="10" style="text-align:center"/>
					</div>
                    <div style="float:left">
                    	<img src="../img/iconos/ico_date.png" id="imgFechaHasta" name="imgFechaHasta" class="puntero noprint"/>
						<script type="text/javascript">
                            Calendar.setup({
                            inputField : "txtFechaHasta",
                            ifFormat : "%d-%m-%Y",
                            button : "imgFechaHasta"
                            });
						</script>
                    </div>
                    </td>
                    <td align="right" class="tituloCampo" width="100">Vendedor:</td>
                    <td colspan="2" id="tdlstEmpleadoVendedor">
                        <select id="lstEmpleadoVendedor" name="lstEmpleadoVendedor">
                            <option value="-1">[ Todos ]</option>
                        </select>
                    </td>
				</tr>
                <tr align="left">
                	<td></td>
                	<td></td>
                	<td align="right" class="tituloCampo" width="110">Tipo de Orden:</td>
                    <td id="tdlstTipoOrden">
                        <select id="lstTipoOrden" name="lstTipoOrden">
                            <option value="-1">[ Todos ]</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="$('btnBuscar').click();"></td>
                    <td>
                    	<input type="button" class="noprint" id="btnBuscar" onclick="xajax_buscarOrden(xajax.getFormValues('frmBuscar'));" value="Buscar" />
						<input type="button" class="noprint" onclick="document.forms['frmBuscar'].reset(); $('btnBuscar').click();" value="Limpiar" />
                    </td>
                </tr>
                </table>
        	</form>
            </td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaOrdenes" name="frmListaOrdenes" style="margin:0">
				<div id="divListaOrdenes"></div>
            </form>
            </td>
        </tr>
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
					<td align="center">
                    	<table>
                        <tr>
                            <td><img src="../img/iconos/ico_view.png" /></td>
                            <td>Ver Orden</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/aprob_mecanico.png" /></td>
                            <td>Aprobación Control de Calidad</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/time_add.png" /></td>
                            <td>Asignar Fecha y Hora de Entrega</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/user_suit.png" /></td>
                            <td>Cambiar Cliente de Pago</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page_refresh.png" /></td>
                            <td>Cambiar Estado de la Orden</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_print.png" /></td>
                            <td>Imprimir Orden</td>
                        </tr>
                        </table>
                    </td>
				</tr>
				</table>
            </td>
        </tr>
        </table>
    </div>
    
    <div class="noprint">
	<?php include("pie_pagina.php"); ?>
    </div>
</div>
</body>
</html>

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
    <table border="0" id="tblDcto" width="980px">
    <tr>
    	<td>
        	<table>
            <tr>
            	<td align="right" class="tituloCampo" width="140">Código:</td>
                <td><input type="text" id="txtCodigoArticulo" name="txtCodigoArticulo" size="30" readonly="readonly"/></td>
            </tr>
            <tr>
            	<td align="right" class="tituloCampo">Descripcion:</td>
                <td><textarea id="txtArticulo" name="txtArticulo" cols="75" rows="3" readonly="readonly"></textarea></td>
            </tr>
            <tr>
            	<td align="right" class="tituloCampo" id="tdTituloCampoDcto" width="100"></td>
                <td><input type="text" id="txtCantidad" name="txtCantidad" size="30" readonly="readonly"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td id="tdListadoDcto"></td>
    </tr>
    <tr>
    	<td align="right">
	        <hr/>
            <input type="button" onclick="validarFormArt();" value="Aceptar"/>
            <input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar"/>
        </td>
    </tr>
    </table>
    
<form id="frmDetenerOrden" name="frmDetenerOrden" style="margin:0">
    <table border="0" id="tblDetencionOrden" width="300">
    <tr>
    	<td colspan="2" id="tdTituloListado">&nbsp;</td>
    </tr>
    <tr>
        <td width="33%">&nbsp;</td>
        <td width="67%">&nbsp;</td>
    </tr>
    <tr align="left">
        <td align="right" class="tituloCampo" width="140">N° Orden:</td>
        <td>
            <input type="text" id="txtNroOrden" name="txtNroOrden" size="30" readonly="readonly"/>
            <input type="hidden" id="hddValBusq" name="hddValBusq" size="30" readonly="readonly"/>
            <input type="hidden" id="hddPageNum" name="hddPageNum" size="30" readonly="readonly"/>
            <input type="hidden" id="hddCampOrd" name="hddCampOrd" size="30" readonly="readonly"/>
            <input type="hidden" id="hddTpOrd" name="hddTpOrd" size="30" readonly="readonly"/>
            <input type="hidden" id="hddMaxRows" name="hddMaxRows" size="30" readonly="readonly"/>
        </td>
    </tr>
    <tr align="left">
        <td class="tituloCampo">Motivo:</td>
        <td id="tdListMotivoDetencionOrden">
            <select id="lstMotivoDetencion" name="lstMotivoDetencion">
                <option value="-1">[ Seleccione ]</option>
            </select>
            <script type="text/javascript">
            //xajax_cargaLstMotivoDetencionOrden();
            </script>
        </td>
    </tr>
    <tr>
        <td class="tituloCampo">Observacion:</td>
        <td><textarea name="txtAreaObservacionDetencion" id="txtAreaObservacionDetencion" cols="45" rows="5"></textarea></td>
    </tr>
    <tr>
    	<td colspan="2">&nbsp;</td>
	</tr>
    <tr>
        <td align="right" colspan="2">
            <hr/>
            <input type="button" id="btnGuardar" name="btnGuardar" onclick="validarFormDetenerOrden();" value="Guardar" />
            <input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar"/>
    	</td>
    </tr>
    </table> 
</form>

<form id="frmRetrabajo" name="frmRetrabajo" style="margin:0">
    <table width="35%" border="0" id="tblRetrabajoOrden">
    <tr>
    	<td colspan="2" id="tdTituloListado">&nbsp;</td>
    </tr>
    <tr>
        <td width="33%">&nbsp;</td>
        <td width="67%">&nbsp;</td>
    </tr>
    <tr align="left">
        <td class="tituloCampo" width="140">Nro Orden:</td>
        <td>
            <input type="text" id="txtNroOrdenRet" name="txtNroOrdenRet" size="30" readonly="readonly"/>
            <input type="hidden" id="hddValBusqRet" name="hddValBusqRet" size="30" readonly="readonly"/>
            <input type="hidden" id="hddPageNumRet" name="hddPageNumRet" size="30" readonly="readonly"/>
            <input type="hidden" id="hddCampOrdRet" name="hddCampOrdRet" size="30" readonly="readonly"/>
            <input type="hidden" id="hddTpOrdRet" name="hddTpOrdRet" size="30" readonly="readonly"/>
            <input type="hidden" id="hddMaxRowsRet" name="hddMaxRowsRet" size="30" readonly="readonly"/>
        </td>
    </tr>
    <tr align="left">
    	<td class="tituloCampo">Motivo:</td>
    	<td><textarea name="txtMotivoRetrabajo" id="txtMotivoRetrabajo" cols="45" rows="5"></textarea></td>
    </tr>
    <tr>
    	<td colspan="2">&nbsp;</td>
	</tr>
    <tr>
        <td align="right" colspan="2">
            <hr/>
            <input type="button" id="btnGuardarRetrabajo" name="btnGuardarRetrabajo" onclick="validarFormRetrabajoOrden();" value="Guardar" />
            <input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar"/>
    	</td>
    </tr>
    </table> 
</form>

<form id="frmReanudarOrden" name="frmReanudarOrden" style="margin:0">
    <table border="0" id="tblReanudarOrden" width="300">
    <tr>
    	<td colspan="2" id="tdTituloListado">&nbsp;</td>
    </tr>
    <tr align="left">
        <td align="right" class="tituloCampo" width="33%">N° Orden:</td>
        <td width="67%">
            <input type="text" id="txtNroOrdenRe" name="txtNroOrdenRe" size="30" readonly="readonly"/>
            <input type="hidden" id="hddValBusqRe" name="hddValBusqRe" size="30" readonly="readonly"/>
            <input type="hidden" id="hddPageNumRe" name="hddPageNumRe" size="30" readonly="readonly"/>
            <input type="hidden" id="hddCampOrdRe" name="hddCampOrdRe" size="30" readonly="readonly"/>
            <input type="hidden" id="hddTpOrdRe" name="hddTpOrdRe" size="30" readonly="readonly"/>
            <input type="hidden" id="hddMaxRowsRe" name="hddMaxRowsRe" size="30" readonly="readonly"/>
        </td>
    </tr>
    <tr align="left">
        <td class="tituloCampo">Motivo:</td>
        <td id="tdListReanudarOrden">
            <select id="lstReanudarOrden" name="lstReanudarOrden">
                <option value="-1">[ Seleccione ]</option>
            </select>
            <script type="text/javascript">
            //xajax_cargaLstReanudarOrden();
            </script>    
        </td>
    </tr>
    <tr>
        <td class="tituloCampo">Observacion:</td>
        <td><textarea name="txtAreaObservacionReanudo" id="txtAreaObservacionReanudo" cols="45" rows="5"></textarea></td>
    </tr>
    <tr>
        <td align="right" colspan="2">
            <hr/>
            <input type="button" id="btnGuardar" name="btnGuardar" onclick="validarFormReanudarOrden();" value="Guardar" />
            <input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar"/>
        </td>
    </tr>
    </table>
</form>

<form id="frmClaveAprobacionOrden" name="frmClaveAprobacionOrden" style="margin:0">
    <table border="0" id="tblClaveAprobacionOrden" width="300">
    <tr>
        <td colspan="2" id="tdTituloListado">&nbsp;</td>
    </tr>
    <tr>
        <td align="right" class="tituloCampo" width="33%">N° Orden:</td>
        <td width="67%">
            <input type="text" id="numeroOrdenMostrar" name="numeroOrdenMostrar"  readonly="readonly"/>
            <input type="hidden" id="txtNroOrdenAprob" name="txtNroOrdenAprob"  readonly="readonly"/>
            <input type="hidden" id="txtIdClaveUsuario" name="txtIdClaveUsuario"  readonly="readonly"/>
            <input type="hidden" id="hddValBusqAprob" name="hddValBusqAprob"  readonly="readonly"/>
            <input type="hidden" id="hddPageNumAprob" name="hddPageNumAprob"  readonly="readonly"/>
            <input type="hidden" id="hddCampOrdAprob" name="hddCampOrdAprob"  readonly="readonly"/>
            <input type="hidden" id="hddTpOrdAprob" name="hddTpOrdAprob"  	readonly="readonly"/>
            <input type="hidden" id="hddMaxRowsAprob" name="hddMaxRowsAprob"  readonly="readonly"/>
            <input type="hidden" id="hddIdMecanicoAprob" name="hddIdMecanicoAprob"  readonly="readonly"/>
            <input type="hidden" id="hddIdJefeTallerAprob" name="hddIdJefeTallerAprob"  readonly="readonly"/>
            <input type="hidden" id="hddIdControlTallerAprob" name="hddIdControlTallerAprob"  readonly="readonly"/>
        </td>
    </tr>
    <tr>
        <td align="right" class="tituloCampo">Clave:</td>
        <td><input name="txtClaveAprobacion" id="txtClaveAprobacion" type="password" class="inputInicial" /></td>
    </tr>
    <tr>
    	<td align="right" colspan="2">
    		<hr/>
            <input type="button" id="btnGuardar" name="btnGuardar" onclick="validarFormAprobacionOrden();" value="Guardar" />
            <input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar"/>
		</td>
    </tr>
    </table>
</form>
</div>


<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%">Acceso Orden Solo Repuestos</td></tr></table></div>
        
        
        <form id="frmClaveSoloRepuestos" name="frmClaveSoloRepuestos" onsubmit="return false;" style="margin:0">
        <table border="0" id="tblClaveSoloRepuestos" width="300">
        <tr>
            <td colspan="2" id="tdTituloListado2222222">&nbsp;</td>
        </tr>
        <tr>
            <td align="right" class="tituloCampo">Clave:</td>
            <td>
                <input id="claveParaSoloRepuestos" type="password" class="inputInicial" value="" />
                <input id="pasoClave" type="hidden" class="inputInicial" value="" />
            </td>
        </tr>
        <tr>
            <td align="right" colspan="2">
                    <hr/>
                <input type="button" onclick="xajax_accesoSoloRepuesto($('claveParaSoloRepuestos').value);" value="Guardar" />
                <input type="button" onclick="$('divFlotante2').style.display='none';" value="Cancelar"/>
                    </td>
        </tr>
        </table>
    </form>
        
        
</div>


<div class="window" id="hora_window" style="z-index:0;top:-1000px;left:0px;max-width:400px;min-width:400px;visibility:hidden;">
	<div class="title" id="title_hora_window">Asignar Fecha y Hora de Entrega Vehiculo</div>
	<div class="content">
    <form id="form_entrega" name="form_entrega" onSubmit="return false;">
        <input type="hidden" id="id_orden" name="id_orden"/>
            
		<div class="nohover">
            <table class="insert_table">
            <tbody>
            <tr>
                <td class="label" width="100">Hora:</td>
                <td class="field" >
                    <input type="text" name="fechaHoraEntrega" id="fechaHoraEntrega" readonly="readonly"  />
                    <img id="b_fecha_prometida" alt="fecha_entrega" src="../img/iconos/select_date.png" />
                    <script type="text/javascript">
                    Calendar.setup({
                        inputField : "fechaHoraEntrega", // id del campo de texto
                        ifFormat : "%d-%m-%Y %I:%M %p", // formato de la fecha que se escriba en el campo de texto
                        button : "b_fecha_prometida", // el id del bot�n que lanzar� el calendario
                        showsTime: true,
                        timeFormat: '12'
                    });
                    </script>
                </td>
            </tr>
            <tr>
                <td colspan="2" align="right">
                    <button title="Asignar" onClick="xajax_guardarFechaEntrega(document.form_entrega.id_orden.value, document.form_entrega.fechaHoraEntrega.value);" id="boton_asignar" name="boton_asignar" ><img border="0" alt="Solo Asignar" src="../img/iconos/select_date.png" class="image_button" />Asignar</button>
                </td>
            </tr>
            </tbody>
            </table>
        </div>
	</form>
    </div>
    <img class="close_window" src="../img/iconos/close_dialog.png" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="$('hora_window').style.display='none';" border="0" />
</div>

<div class="window" id="window_credito" style="z-index:100;top:-1000px;left:0px;max-width:400px;min-width:400px;visibility:hidden;border-color:#FEB300;">
	<div class="title" id="title_window_credito" style="background:#FEE8B3;color:#000000;">
		<div class="key_pass" id="title_credito" style="padding-left:24px;"></div>
	</div>
	<div class="content">
		<div class="nohover">
			<table class="insert_table">
			<tbody>
				<tr>
					<td width="30%"  class="label">Clave:</td>
					<td class="field" style="text-align:center;">
                        <input style="width:95%;border:0px;" type="password" name="clave_credito" id="clave_credito" maxlength="30" onkeypress="" />
                        <input type="hidden" name="id_orden_sobregiro" id="id_orden_sobregiro" value="0" />
                        <input type="hidden" name="sobregiro" id="sobregiro" value="0" />
					</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align:right;padding:2px;">
                        <span style="padding:2px;">
                            <button onclick="verificarSobregiro($('clave_credito').value, $('id_orden_sobregiro').value);"><img alt="aceptar" src="../img/iconos/select.png" class="image_button" />Aceptar</button>
                        </span>
                        <span style="padding:2px;">
                            <button onclick="$('window_credito').style.display='none';"><img alt="cerrar" src="../img/iconos/delete.png" class="image_button" />Cancelar</button>
                        </span>
					</td>
				</tr>
			</tbody>
			</table>
		</div>
	</div>
	<img class="close_window" src="../img/iconos/close_dialog.png" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="$('window_credito').style.display='none';" border="0" />
</div>

<div class="window" id="cita" style="z-index:0;top:-1000px;left:0px;max-width:600px;min-width:600px;visibility:hidden;">
    <div class="title" id="title_cita_window">Cambiar Cliente</div>
    <div id="cita_add" class="content">
        <div>
        <form id="form_cambio_cliente" name="form_cambio_cliente" onSubmit="return false;">
            <input type="hidden" id="id_cliente_pago" name="id_cliente_pago"/>
            <input type="hidden" id="id_orden_cliente" name="id_orden_cliente"/>
        </form>
            <table class="insert_table">
            <tr>
                <td align="right" class="tituloCampo">C.I. / R.I.F.:</td>
                <td width="30%"><input type="text" id="cedula_cliente" name="cedula_cliente" onkeypress=""/></td>
                <td colspan="2"><button type="button" onclick="xajax_buscarCliente($('cedula_cliente').value); $('criterioCliente').value = ''; "><img src="../img/iconos/find.png" border="0" alt="image" />Buscar..</button></td>                
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="18%">Nombres:</td>
                <td class="form_field" width="32%" align="left"><span id="nombre_cliente" class="td_inner">&nbsp;</span></td>
                <td align="right" class="tituloCampo" width="18%">Email:</td>
                <td class="form_field" width="32%"><span id="email_cliente" class="td_inner">&nbsp;</span></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo">Tel&eacute;fono:</td>
                <td class="form_field"><span id="telefono_cliente" class="td_inner">&nbsp;</span></td>
                <td align="right" class="tituloCampo">Celular:</td>
                <td class="form_field"><span id="celular_cliente" class="td_inner">&nbsp;</span></td>
            </tr>
            <tr>
            	<td align="right" colspan="4">
                	<hr>
                    <input type="button" value="Guardar" onclick="validarCambioCliente(document.form_cambio_cliente.id_orden_cliente.value, document.form_cambio_cliente.id_cliente_pago.value)" />
                </td>
            </tr>
            </table>
        </div>
        <img class="close_window" src="../img/iconos/close_dialog.png" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="$('cita').style.display='none';" border="0" />
    </div>
</div>

<div id="xajax_client_div" class="window" style="z-index:0;top:-1000px;left:0px;max-width:485px;min-width:485px;visibility:hidden;">
    <div class="title" id="title_xajax_client_div">Cambiar Cliente</div>
    <div id="cita_add" class="content"></div>
</div>

<div class="window" id="key_window_status" style="z-index:100;top:-1000px;left:0px;max-width:400px;min-width:400px;visibility:hidden;border-color:#FEB300;">
	<div class="title" id="title_key_window_status" style="background:#FEE8B3;color:#000000;">
		<div class="key_pass" id="key_title_status" style="padding-left:24px;"></div>
	</div>
	<div class="content">
		<div class="nohover">
			<table class="insert_table">
			<tbody>
				<tr>
					<td width="30%"  class="label">Clave:</td>
					<td class="field" style="text-align:center;">
						<input style="width:95%;border:0px;" type="password" name="key_status" id="key_status" maxlength="30" onkeypress="" />
					</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align:right;padding:2px;">
						<span style="padding:2px;">
							<button onclick="xajax_verficarPassStatusOrden($('key_status').value);"><img alt="aceptar" src="../img/iconos/select.png" class="image_button" />Aceptar</button>
						</span>
						<span style="padding:2px;">
							<button onclick="$('key_window_status').style.display='none';"><img alt="cerrar" src="../img/iconos/delete.png" class="image_button" />Cancelar</button>
						</span>
					</td>
				</tr>
			</tbody>
			</table>
		</div>
	</div>
	<img class="close_window" src="../img/iconos/close_dialog.png" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="$('key_window_status').style.display='none';" border="0" />
</div>

<div id="estado_window" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo7" class="handle"><table><tr><td id="title_estado_window" width="100%">Cambio de Estado</td></tr></table></div>
	
<form id="form_estado" name="form_estado" onSubmit="return false;">
    <input type="hidden" id="id_orden" name="id_orden"/>
    <table width="420">
    <tr>
        <td align="right" class="tituloCampo" width="40%">Estado Actual de la Orden: </td>
        <td id="divEstadoActual" width="60%"></td>
    </tr>
    <tr>
        <td align="right" class="tituloCampo">Estados Disponible:</td>
        <td id="divEstadoOrden"></td>
    </tr>
    <tr>
        <td colspan="2" align="right">
            <hr>
            <button id="boton_asignar" name="boton_asignar" onClick="cambiarEstado();" title="Asignar">Actualizar</button>
            <button id="btnCancelar7" name="btnCancelar7" onclick="$('estado_window').style.display='none';" title="Cancelar">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>


<div style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;" class="root" id="divFlotante3">
    <div class="handle" id="divFlotanteTitulo3"><table><tbody><tr><td width="100%" id="tdFlotanteTitulo3">Clientes</td></tr></tbody></table></div>
    <div style="width:500px;">
        <table>
            <tr>
                <td width="33%" align="right" class="tituloCampo">Criterio:</td>
                <td>
                    <input type="text" id="criterioCliente" />
                    <button type="button" id="botonBuscarCliente" class="puntero" onclick="xajax_buscarCliente(document.getElementById('criterioCliente').value);"><img border="0" src="../img/iconos/find.png"></button>
                    <button type="button" onclick="$('criterioCliente').value = '';  $('botonBuscarCliente').click(); ">Limpiar</button>
                </td>
            </tr>
        </table>
    </div>
    
    <div id = "divListadoClientes"></div>
    
    <div align="right">
        <hr/>
        <button type="button" class="puntero" onclick="$('divFlotante3').style.display='none';">Cancelar</button>
    </div>
</div>

<script>

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>','onchange="$(\'btnBuscar\').click(); cargarTipoOrdenEmpresa(this.value); cargarEmpleadoEmpresa(this.value); "','','','','unico'); //buscador

//xajax_cargaLstEmpresaBuscar('<?php // echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstEmpleado('','lstEmpleadoVendedor','tdlstEmpleadoVendedor');
xajax_cargaLstTipoOrden();
xajax_cargaLstEstadoOrden();

xajax_listadoOrdenes(0,'orden.numero_orden','DESC','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|'+$('txtFechaDesde').value+'|'+$('txtFechaHasta').value);
</script>
<script language="javascript" type="text/javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
        
	var theHandle = document.getElementById("divFlotanteTitulo2");
	var theRoot   = document.getElementById("divFlotante2");
	Drag.init(theHandle, theRoot);
        
	var theHandle = document.getElementById("divFlotanteTitulo3");
	var theRoot   = document.getElementById("divFlotante3");
	Drag.init(theHandle, theRoot);
	
	var theHandle = document.getElementById("title_hora_window");
	var theRoot   = document.getElementById("hora_window");
	Drag.init(theHandle, theRoot);
	
	var theHandle = document.getElementById("title_window_credito");
	var theRoot   = document.getElementById("window_credito");
	Drag.init(theHandle, theRoot);
	
	var theHandle = document.getElementById("title_cita_window");
	var theRoot   = document.getElementById("cita");
	Drag.init(theHandle, theRoot);
	
        //movido se genera xajax
//	var theHandle = document.getElementById("xajax_client_div_bar");
//	var theRoot   = document.getElementById("xajax_client_div_window");
//	Drag.init(theHandle, theRoot);
//	
	var theHandle = document.getElementById("title_xajax_client_div");
	var theRoot   = document.getElementById("xajax_client_div");
	Drag.init(theHandle, theRoot);
	
	var theHandle = document.getElementById("title_key_window_status");
	var theRoot   = document.getElementById("key_window_status");
	Drag.init(theHandle, theRoot);
	
	var theHandle = document.getElementById("divFlotanteTitulo7");
	var theRoot   = document.getElementById("estado_window");
	Drag.init(theHandle, theRoot);
		
	function cargarTipoOrdenEmpresa(empresa){
		xajax_cargaLstTipoOrden('',empresa);
	}
	
	function cargarEmpleadoEmpresa(empresa){
		xajax_cargaLstEmpleado('','lstEmpleadoVendedor','tdlstEmpleadoVendedor',empresa);
	}
        
        var tiempoEscritura;     //Tiempo escribiendo
        var tiempoFinal = 700;  //Tiempo en milisegundos 5 seg = 5000 milisegundos

        //en cada tecla reinicia contador
        $('criterioCliente').addEvent('keyup', function(e){  //mootols keyup          
            clearTimeout(tiempoEscritura);
            if ($('criterioCliente').value) {
                tiempoEscritura = setTimeout(callbackCliente, tiempoFinal);                
            }
        });

        //buscar cuando termine de escribir
        function callbackCliente () {
            $('botonBuscarCliente').click();
        }
	
	
	/*
		function limpiar_select(){		
				
				var listadoDeEmpresas = document.getElementById("lstEmpresa");				
				var grupo =listadoDeEmpresas.getElementsByTagName('optgroup');				
				
				for(i=0; i<grupo.length; i++){					
					grupo[i].label = "";
				}
									 
				for(i=0; i<listadoDeEmpresas.length; i++){
					 
						if(listadoDeEmpresas[i].selected == true){
							//alert("si");
							}else{
								listadoDeEmpresas[i].style.display="none"; 
								}				
					}
					
			}			
			*/
</script>
