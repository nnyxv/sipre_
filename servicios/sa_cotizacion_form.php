<?php
require_once ("../connections/conex.php");

session_start();

define('PAGE_PRIV','sa_cotizacion_list');//nuevo gregor
//define('PAGE_PRIV','sa_cotizacion');//anterior
require_once("../inc_sesion.php");

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_iv_general.php");
include("controladores/ac_sa_cotizacion_form.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);
$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

        <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Cotización Genérica</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
        
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
       
    <link rel="stylesheet" type="text/css" href="../js/domDragServicios.css">
    <link rel="stylesheet" type="text/css" href="css/sa_general.css"/>

    <script type="text/javascript" language="javascript" src="../js/mootools.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
    <script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/> 
    <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
        
    <style type="text/css">
	.tituloArea{
		border:0px;	
	}
	/*.root {
		background-color:#FFFFFF;
		border:6px solid #999999;
		font-family:Verdana, Arial, Helvetica, sans-serif;
		font-size:11px;
		max-width:1000px;
		position:absolute;
	}

	.handle {
		padding:2px;
		background-color:#4E6D12;
		color:#FFFFFF;
		font-weight:bold;
		cursor:move;
	}
	*/
	</style>
    
    <script>
	function validarPaquete(){
		if (validarCampo('hddEscogioPaquete','t','') == true)
		{
                    
                    if(validarCampo('hddManObraAproXpaq','t','') == false && validarCampo('hddRepAproXpaq','t','') == false){//valido que almenos escogio algo
				alert("No puedes agregar paquetes vacios");
						return false;
			}
                    
//			if(validarCampo('hddManObraAproXpaq','t','') == true)
//			{
//				if(validarCampo('hddRepAproXpaq','t','') == true)
//				{
					if($('hddArtEnPaqSinPrecio').value == 1)
					{
						$msgAdvRpto = " Repuesto(s)";
						$sw = 1;
					}
					else
					{
						$msgAdvRpto = "";
						$sw = 0;
					}
					if($('hddTempEnPaqSinPrecio').value == 1)	
					{
						$msgAdvTemp = " Mano(s) de Obra(s)";
						$sw2 = 1;
					}
					else
					{
						$msgAdvTemp = "";
						$sw2 = 0;
					
					}
                                        
                                        //aqui no tenia antes los && y lo que tiene a la derecha del and, el or si lo tenia.
					//valido que solo valide los precios si se escogio, si no eligio repuestos pero si manos de obra, y el repuesto no tiene precio asignado, deje agregar si solo escogio manos de obra
					if (($('hddTempEnPaqSinPrecio').value == 1 && validarCampo('hddManObraAproXpaq','t','') == true) || ($('hddArtEnPaqSinPrecio').value == 1 && validarCampo('hddRepAproXpaq','t','') == true)) {
						if($sw == $sw2)
							$caracter = " y";
						else
							$caracter = "";
							
						alert("El paquete elegido tiene" + $msgAdvRpto + $caracter + $msgAdvTemp + " sin Precio(s). Para poderlo agregar debe asignarle el precio correspondiente por el Tipo de Orden.");
					} else {
						if ( $('hddArticuloSinDisponibilidad').value == 1) {
							if( confirm("El paquete elegido tiene Repuestos sin Disponibilidad. Desea agregarlo?")) {
								xajax_insertarPaquete(xajax.getFormValues('frmDatosPaquete'), xajax.getFormValues('frmTotalPresupuesto'), xajax.getFormValues('frm_agregar_paq'), xajax.getFormValues('frmPresupuesto'));
							} else
								return false;
						} else
							xajax_insertarPaquete(xajax.getFormValues('frmDatosPaquete'), xajax.getFormValues('frmTotalPresupuesto'), xajax.getFormValues('frm_agregar_paq'), xajax.getFormValues('frmPresupuesto'));
					}
                                        
//					if ($('hddArtEnPaqSinPrecio').value == 1 || $('hddTempEnPaqSinPrecio').value == 1)
//					{
//						if($sw == $sw2)
//							$caracter = " y";
//						else
//							$caracter = "";
//							
//						alert("El paquete elegido tiene" + $msgAdvRpto + $caracter + $msgAdvTemp + " sin Precio(s). Para poderlo agregar debe asignarle el precio correspondiente por el Tipo de Orden.");
//					}
//					else
//					{
//						if ( $('hddArticuloSinDisponibilidad').value==1 )
//						{
//							if( confirm("El paquete elegido tiene Repuestos sin Disponibilidad. Desea agregarlo?") )
//							{
//								xajax_insertarPaquete(xajax.getFormValues('frmDatosPaquete'), xajax.getFormValues('frmTotalPresupuesto'), xajax.getFormValues('frm_agregar_paq'), xajax.getFormValues('frmPresupuesto'));
//							}
//							else
//								return false;
//						}
//						else
//							xajax_insertarPaquete(xajax.getFormValues('frmDatosPaquete'), xajax.getFormValues('frmTotalPresupuesto'), xajax.getFormValues('frm_agregar_paq'), xajax.getFormValues('frmPresupuesto'));
//					
//					}
//				}
//				else
//				{
//					alert("Escoja los Repuestos");
//					return false;
//				}
//			}
//			else
//			{
//				alert("Escoja las Manos de Obra");
//				return false;
//			}
		}
		else
		{
			alert("Escoja el paquete.");
			return false;
		}
	
	}
	function validarNota()
	{
		if (validarCampo('txtDescripcionNota','t','') == true && validarCampo('txtPrecioNota','t','') == true)
		{
			
			xajax_insertarNota(xajax.getFormValues('frmDatosNotas'), xajax.getFormValues('frmTotalPresupuesto'));
		}
		else
		{
			validarCampo('txtDescripcionNota','t','');
			validarCampo('txtPrecioNota','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	
	
	}
	
	function validarFormClaveDescuento(){
		if (validarCampo('txtContrasenaAcceso','t','') == true)
		{
			
			xajax_validarClaveDescuento(xajax.getFormValues('frmConfClave'), xajax.getFormValues('frmTotalPresupuesto'));
		}
		else
		{
			validarCampo('txtContrasenaAcceso','t','');			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	
	}
	
	function validarFormClaveDescuentoAdicional(){
		if (validarCampo('lstTipoDescuentos','t','lista') == true && validarCampo('txtPorcDctoAdicional','t','monto'))
		{
			
			xajax_insertarDescuento(xajax.getFormValues('frm_agregar_paq'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmListaManoObra'), xajax.getFormValues('frmListaTot'), xajax.getFormValues('frmListaNota'), xajax.getFormValues('frmTotalPresupuesto'), xajax.getFormValues('frmConfClave'));
			
		}
		else
		{
			validarCampo('lstTipoDescuentos','t','lista');			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarTempario()
	{
	
		if($('hddTipoDocumento').value != 1)
		{
			if($('hddMecanicoEnOrden').value == "")
			{
				alert("El Parámetro ASIGNACION DE MECANICO EN ORDEN no esta configurado.");
				xajax_cargaLstTipoOrden(-1);
				return false;
			}
			if($('hddMecanicoEnOrden').value == 1)
			{
				//dependiendo si se muestra o no el mecanico por parametros generales coloco la validacion
				if (validarCampo('lstMecanico','t','lista') == true && validarCampo('txtCodigoTemp','t','') == true)
				{
					xajax_insertarTempario(xajax.getFormValues('frmDatosTempario'),	xajax.getFormValues('frmListaManoObra'), xajax.getFormValues('frmTotalPresupuesto'));	
				}												
				else
				{
					validarCampo('lstMecanico','t','lista');
					alert("Los campos señalados en rojo son requeridos");
					return false;
				}
			}
			else
			{
				/*if($('hddObjTempario').value == "")
					alert("El diagnóstico se cargará automáticamente.");*/
					
				xajax_insertarTempario(xajax.getFormValues('frmDatosTempario'),	xajax.getFormValues('frmListaManoObra'), xajax.getFormValues('frmTotalPresupuesto'));	
			}													
		} 
		else
		{
			xajax_insertarTempario(xajax.getFormValues('frmDatosTempario'),	xajax.getFormValues('frmListaManoObra'), xajax.getFormValues('frmTotalPresupuesto'));
		}	
	}
	function validarTot(){
		if (validarCampo('txtNumeroTot','t','') == true) {
			xajax_insertarTot(
			xajax.getFormValues('frmDatosTot'),
			xajax.getFormValues('frmListaTot'),
			xajax.getFormValues('frmTotalPresupuesto'));
		}
		else
		{
			validarCampo('txtNumeroTot','t','');
			//validarCampo('txtCodigoTemp','t','');
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	function validarFormArt() {
	/*	var precioNuevo= parseFloat($('txtPrecioRepuesto').value);
            var precioViejo= parseFloat($('hddPrecioRepuestoDB').value);
            
            if(precioNuevo < precioViejo){
                    alert('El Precio del articulo no puede ser menor que '+$('hddPrecioRepuestoDB').value);
            }else{*/
		if (validarCampo('txtCodigoArt','t','') == true
		&& validarCampo('txtCantidadArt','t','cantidad') == true
		//&& validarCampo('lstPrecioArt','t','lista') == true
		//&& validarCampo('lstIvaArt','t','listaExceptCero') == true
		) {
			if($('txtCantDisponible').value==0)
			{
				if(confirm("El articulo que desea agregar no dispone de cantidad disponible. \nDesea cargar la cantidad digitada?"))
					xajax_insertarArticulo(xajax.getFormValues('frmDatosArticulo'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalPresupuesto'));

			}
			else
				xajax_insertarArticulo(xajax.getFormValues('frmDatosArticulo'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalPresupuesto'));

		} else {
			validarCampo('txtCodigoArt','t','');
			validarCampo('txtCantidadArt','t','cantidad');
			//validarCampo('lstPrecioArt','t','lista');
			//validarCampo('lstIvaArt','t','listaExceptCero');
			
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
		
		//	}
	}
	
	
	function validarNroControl()
	{
		if (validarCampo('txtNroControl','t','') == true)
		{
			if($('hddItemsNoAprobados').value == 1)
				$cadena = "La Orden tiene Items No aprobados. Estos mismos no seran reflejados en la Factura.\n";
			else
				$cadena = "";
				
			if(confirm($cadena + "Desea Generar la Factura?"))
				xajax_guardarFactura(xajax.getFormValues('frmPresupuesto'), xajax.getFormValues('frmTotalPresupuesto'));
			else
				return false;
		}
		else
		{
			validarCampo('txtNroControl','t','');
			$('txtNroControl').focus();
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFormPresupuesto() {
	
		if (validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtIdCliente','t','') == true
		&& validarCampo('txtDescuento','t','numPositivo') == true
		&& validarCampo('txtTotalPresupuesto','t','monto') == true
		&& validarCampo('lstTipoOrden','t','lista') == true) {
			if ($('hddObj').value.length > 0 || $('hddObjPaquete').value.length > 0 || $('hddObjTempario').value.length > 0 || $('hddObjRepuestosPaquete').value.length > 0 || $('hddObjNota').value.length > 0 || $('hddObjTot').value.length > 0 ){
				
				if(confirm("Desea generar el Documento?"))
				{
				xajax_guardarDcto(xajax.getFormValues('frmPresupuesto'),  xajax.getFormValues('frm_agregar_paq'), xajax.getFormValues('frmListaArticulo'),  xajax.getFormValues('frmListaManoObra'),  xajax.getFormValues('frmListaNota'),  xajax.getFormValues('frmListaTot'), xajax.getFormValues('frmTotalPresupuesto'));
				}
				else
					return false;
			}
			else {
				alert("Debe agregar Items al Documento");
				return false;
			}
		} else {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtIdCliente','t','');
			validarCampo('txtDescuento','t','numPositivo');
			validarCampo('txtTotalPresupuesto','t','monto');
			validarCampo('lstTipoOrden','t','lista');
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function bloquearForm() {
		$('txtFechaVencimientoPresupuesto').readOnly = true;
		$('imgFechaVencimientoPresupuesto').style.visibility = 'hidden';
		$('txtNumeroPresupuestoPropio').readOnly = true;
		$('txtNumeroReferencia').readOnly = true;
		$('lstMoneda').disabled = true;
		$('btnEliminarPaq').disabled = true;
		$('btnInsertarPaq').disabled = true;
		$('btnInsertarTemp').disabled = true;
		$('btnInsertarEmp').disabled = true;
		$('btnInsertarCliente').disabled = true;
		$('btnPendiente').disabled = false;
		$('btnInsertarArt').disabled = true;
		$('btnInsertarUnidad').disabled = true;
		$('btnEliminarArt').disabled = true;
		if($('hddAccionTipoDocumento').value != 1)
		{
			//$('btnEliminarTot').disabled = true;
			$('btnEliminarTot').readOnly = true;

		}
			
		$('btnInsertarTot').disabled = true;
		$('btnEliminarTemp').disabled = true;
		//$('txtDescuento').readOnly = true;
		$('btnInsertarNota').disabled = true;	
		$('btnEliminarNota').disabled = true;	
		if($('hddTipoDocumento').value == 3 || $('hddTipoDocumento').value == 4)
		{
			$('btnGuardar').disabled = false;
			$('btnCancelar').disabled = false;
		}
	}
	//alert($('hddAccionTipoDocumento').value);
	function desbloquearForm() {
		$('txtFechaVencimientoPresupuesto').readOnly = true;
		$('imgFechaVencimientoPresupuesto').style.visibility = 'hidden';
		$('txtNumeroPresupuestoPropio').readOnly = true;
		$('txtNumeroReferencia').readOnly = true;
		$('lstMoneda').disabled = true;
		$('btnEliminarPaq').disabled = false;
		$('btnInsertarPaq').disabled = false;
		$('btnInsertarTemp').disabled = false;
		$('btnInsertarEmp').disabled = false;
		$('btnInsertarCliente').disabled = false;
		$('btnPendiente').disabled = false;
		$('btnInsertarArt').disabled = false;
		$('btnEliminarArt').disabled = false;
		$('btnInsertarTot').disabled = false;
		$('btnInsertarUnidad').disabled = false;

		if($('hddAccionTipoDocumento').value != 1)
		{
			$('btnEliminarTot').readOnly = true;
		}
		$('btnEliminarTemp').disabled = false;
		//$('txtDescuento').readOnly = false;
		$('btnInsertarNota').disabled = false;	
		$('btnEliminarNota').disabled = false;	
		$('btnGuardar').disabled = false;
		$('btnCancelar').disabled = false;
		
	}
	</script>
</head>

<body class="bodyVehiculos" onload="
xajax_validarTipoDocumento('<?php echo $_GET['doc_type']; ?>','<?php echo $_GET['id']; ?>','<?php echo $_GET['ide']; ?>','<?php echo $_GET['acc']; ?>', xajax.getFormValues('frmTotalPresupuesto'));">
<div id="">
	<?php include("banner_servicios.php"); ?>
    <div id="divGeneralVehiculos" class="print">
    	<table width="100%"  border="0" cellpadding="0" cellspacing="0">
    	<tr>
        	<td id="tdTituloPaginaServicios" class="tituloPaginaServicios"></td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr style="display:none">
        	<td align="right">
            	<table align="left" class="noprint" >
                <tr>
                    <td >
                    	<input type="button" name="btnImprimirDoc" id="btnImprimirDoc" value="Imprimir" onclick="verVentana('sa_imprimir_presupuesto_pdf.php?valBusq=<?php echo $_GET['id']; ?>|<?php echo $_GET['doc_type']; ?>', 950, 600);" style="display:none" />                    </td>
                    <td >
                      <form id="frmSolicitudRpto" name="frmSolicitudRpto" method="post" action="sa_solicitud_repuestos_form.php" style="margin:0" >
                        <input type="hidden" name="hddIdOrden" id="hddIdOrden" value="" />
                        <input type="hidden" name="hddIdSolicitudRpto" id="hddIdSolicitudRpto" value="" />
                      </form>                    </td>
                    <td width="90" style="display:none"><input type="button" id="btnNuevo" name="btnNuevo" onclick="xajax_nuevoDcto();" value="Nuevo"/></td>
                    <td width="206" style="display:none"><input type="button" id="btnPendiente" name="btnPendiente" onclick="xajax_cargaLstEmpresaBusq(); xajax_validarTipoDocumento(<?php echo $_GET['doc_type'];?>,<?php echo $_GET['acc']; ?>, xajax.getFormValues('frmTotalPresupuesto')); xajax_listadoDctos(0,'','','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');" value="Presupuestos Pendientes"/></td>
                </tr>
          </table>          </td>
        </tr>
        <tr>
        	<td align="left">
            <form id="frmPresupuesto" name="frmPresupuesto" style="margin:0">
            	<table border="0" width="100%">
                <tr style="display:none" >
                	<td align="left" class="tituloCampo" width="16%" ><span class="textoRojoNegrita">*</span>Empresa / Sucursal:</td>
              <td colspan="2">
      <table cellpadding="0" cellspacing="0"  >
                        <tr>
                        	<td>
                            	<input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="8" value="<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>"/>
                             	<script>
                                	xajax_objetoCodigoDinamico('tdCodigoArt','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
								</script></td>
                            <td><button type="button" id="btnInsertarEmp" name="btnInsertarEmp" onclick="xajax_listadoEmpresas(0,'','','');" title="Seleccionar Empresa"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
                            <td>&nbsp;<input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="50"/></td>
                        </tr>
                    </table>					</td>
                  </tr>
                <tr style="display:none">
                	<td align="left" class="tituloCampo" >Empleado:</td>
                    <td colspan="2" >
                    <input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="25"/></td>
                  </tr>
                <tr  id="trCodigoBarraPresupuesto" style="display:none">
                  <td align="left" >&nbsp;</td>
                  <td width="68%" >&nbsp;</td>
                  <td width="16%"  id="tdCodigoBarraPresupuesto"  >&nbsp;</td>
                </tr>
                  
                <tr>
                  <td colspan="3" align="left"><fieldset  >
                    	<legend id="lydTipoDocumento"></legend>
                        
                        <table border="0" width="100%">
                        <tr>
                         <td width="70%">
                         <table border="0" style="display:none" width="78%" id="fldPresupuesto"  >
                           <tr>
                            <td align="left" class="tituloCampo" id="tdNroControl" style="display:none">Nro Control</td>
                            <td width="87%" id="tdTxtNroControl" style="display:none"><input type="text" id="txtNroControl" name="txtNroControl" size="25"/></td>
						</tr>
                        <tr>
                            <td align="left" class="tituloCampo" id="tdNroPresupuesto" style="display:none">Nro Presupuesto</td>
                            <td width="87%" id="tdTxtNroPresupuesto" style="display:none"><input type="text" id="txtNroPresupuesto" name="txtNroPresupuesto" size="25" readonly="readonly"/></td>
						</tr>
                        <tr>
                            <td align="left" class="tituloCampo" width="13%" id="tdIdDocumento"></td>
                            <td width="87%">
                            <!-- cotizacion solo -->
                            <input type="hidden" id="txtIdPresupuesto" name="txtIdPresupuesto" readonly="readonly" size="25" value="0"/>
                            <!-- numero cotizacion a mostrar -->
                            <input type="text" id="numeroCotizacionMostrar" readonly="readonly" size="25" />
                            </td>
						</tr>
                        <tr id="tdFechaVecDoc">
                            <td align="left" class="tituloCampo" width="13%" >Fecha Venc</td>
                            <td>
                            <div style="float:left">
                            	<input type="text" id="txtFechaVencimientoPresupuesto" name="txtFechaVencimientoPresupuesto" readonly="readonly" size="20"/>
							</div>
                            <div style="float:left">
                                <img src="../img/iconos/ico_date.png" id="imgFechaVencimientoPresupuesto" name="imgFechaVencimientoPresupuesto" class="puntero noprint"/>
                                <script type="text/javascript">
                                Calendar.setup({
                                inputField : "txtFechaVencimientoPresupuesto",
                                ifFormat : "%d-%m-%Y",
                                button : "imgFechaVencimientoPresupuesto"
                                });
                                </script>
                            </div>                            </td>
                        </tr>
                        <tr style="display:none">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nº Presu:</td>
                            <td><input type="text" id="txtNumeroPresupuestoPropio" name="txtNumeroPresupuestoPropio" size="25"/></td>
                        </tr>
                        <tr style="display:none">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Moneda:</td>
                            <td id="tdlstMoneda">
                            	<select id="lstMoneda" name="lstMoneda">
                                	<option value="-1">Seleccione...</option>
                                </select>                            </td>
                        </tr>
                        <tr style="display:none">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nº Refer:</td>
                            <td><input type="text" id="txtNumeroReferencia" name="txtNumeroReferencia" size="25"/></td>
                        </tr>
                        </table>                         </td>
                         
                         
                         
                         
                         <td width="30%"><table width="100%" border="0">
                      <tr>
                        <td width="4%" >&nbsp;</td>
                        <td width="43%" class="tituloCampo">Fecha</td>
                        <td width="53%"><input type="text" id="txtFechaPresupuesto" name="txtFechaPresupuesto" readonly="readonly"/></td>
                      </tr>
                      <tr>
                        <td rowspan="2" >&nbsp;</td>
                        <td rowspan="2" class="tituloCampo">Tipo Orden:</td>
                        <td id="tdlstTipoOrden" style="display:none">
                            <select id="lstTipoOrden" name="lstTipoOrden">
                              <option value="-1">Seleccione...</option>
                            </select>
							<script>
                                 xajax_cargaLstTipoOrden();
                            </script>                        <label></label></td>
                      </tr>
                      <tr>
                      
                      
                        <td id="tdDescripcionTipoOrden" style="display:none"><input type="text" name="txtDescripcionTipoOrden" id="txtDescripcionTipoOrden" readonly="readonly" /></td>
                      </tr>
                      
                      
                          </table></td>
                        </tr>
                        </table>
                    	
				  </fieldset></td>
                  </tr>
                
                <tr>
                  <td height="152" colspan="3"  valign="top"><fieldset>
                  <legend>Cliente</legend>
                  <table width="100%">
                    
                    <tr>
                      <td align="left" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cliente:</td>
                      <td colspan="4"><table width="342" cellpadding="0" cellspacing="0">
                          <tr>
                            <td width="48"><input name="txtIdCliente" type="text" id="txtIdCliente" size="8" readonly="readonly" /></td>
                            <td width="319"><input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="47"/></td>
                            <td width="319"><table width="25%" border="0" cellpadding="0" cellspacing="0">
                              <tr>
                                <td width="21%"><label></label></td>
                                <td width="79%" class="noprint"><button type="button" id="btnInsertarCliente" name="btnInsertarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmPresupuesto'),xajax.getFormValues('frmBuscarVale'));" title="Seleccionar Vale de Recepcion"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
                              </tr>
                            </table></td>
                          </tr>
                         
                      </table></td>
                      <td width="58%" rowspan="3"><fieldset>
                  <legend>Datos del vehiculo</legend>
                  <table width="100%" border="0">
                    
                     <tr>
                       <td width="45%" class="tituloCampo">Unidad Basica:</td>
                       <td width="55%">
                       <table width="25%" border="0" cellpadding="0" cellspacing="0">
                              <tr>
                                <td width="21%"><input type="text" name="txtUnidadBasica" id="txtUnidadBasica" readonly="readonly" size="9" /></td>
                                <td width="79%" class="noprint"><button type="button" id="btnInsertarUnidad" name="btnInsertarUnidad" onclick="xajax_buscarUnidad(xajax.getFormValues('frmPresupuesto'),xajax.getFormValues('frmBuscarVale'));" title="Seleccionar Unidad Basica"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
                              </tr>
                            </table></td>
                     </tr>
                     <tr>
                      <td class="tituloCampo">A&ntilde;o:
                        <input type="hidden" name="hdd_id_modelo" id="hdd_id_modelo" />
                        <input type="hidden" name="hddIdUnidadBasica" id="hddIdUnidadBasica" /></td>
                      <td><input type="text" id="txt_ano_vehiculo" name="txt_ano_vehiculo" readonly="readonly"/></td>
                    </tr>
                      <tr>
                      <td class="tituloCampo">Modelo:</td>
                      <td><input type="text" id="txtModeloUnidadBasica" name="txtModeloUnidadBasica" readonly="readonly"/></td>
                    </tr>
                      <tr>
                        <td class="tituloCampo">Marca:</td>
                        <td><input type="text" id="txtMarca" name="txtMarca" readonly="readonly"/></td>
                      </tr>
                  </table>
                  </fieldset>     </td>
                    </tr>
                    <tr>
                      <td align="left" class="tituloCampo">Dirección:</td>
                      <td colspan="3">
                      <textarea cols="55" id="txtDireccionCliente" name="txtDireccionCliente" readonly="readonly" rows="3"></textarea>
                      <input type="hidden" id="hddIdEmpleado" name="hddIdEmpleado" readonly="readonly"/>
                      <input type="hidden" id="hddAgregarOrdenFacturada" name="hddAgregarOrdenFacturada" readonly="readonly"/>                      </td>
                      <td>&nbsp;</td>
                      <td>&nbsp;</td>
                    </tr>
                    <tr>
                      <td align="left" class="tituloCampo" width="7%">Teléfono:</td>
                      <td width="8%"><input type="text" id="txtTelefonosCliente" name="txtTelefonosCliente" readonly="readonly" size="25"/></td>
                      <td width="4%" class="tituloCampo">C.I/Rif:</td>
                      <td width="4%"><input type="text" id="txtRifCliente" name="txtRifCliente" readonly="readonly" size="18"/></td>
                      <td width="19%" >&nbsp;</td>
                      </tr>
                    <tr>
                    	<td colspan="6" valign="top" >                    					</td>
                    </tr>
                  </table>
                  <br />
                  </fieldset>                  </td>
                  </tr>
                <tr>
                	<td colspan="3" valign="top">	</td>
                </tr>
                </table>
            </form>            </td>
        </tr>
      
        <tr>
          <td align="left">
          	<form name="frm_agregar_paq" id="frm_agregar_paq" style="margin:0">
 				<table border="0"  width="100%">
                <!-- <col style="width:20px;" /> APLICA A LA PRIMERA COLUMNA ESTA PROPIEDAD SI LE QUIERO COLOCARSELA A LAS DEMAS HAGO LO MISMO-->
				<tr>
                    	<td colspan="9"><table width="100%" border="0" cellpadding="0" cellspacing="0" class="tituloArea">
                          <tr>
                            <td width="44%" height="22" align="left">
                            <button type="button" id="btnInsertarPaq" name="btnInsertarPaq" onclick="
                                if (validarCampo('txtIdCliente','t','') == true
									&& validarCampo('lstTipoOrden','t','lista') == true &&
                               validarCampo('txtUnidadBasica','t','') == true) {
                                    document.forms['frmBuscarArticulo'].reset();
                                    document.forms['frmDatosArticulo'].reset();
                                    document.forms['frmDatosPaquete'].reset();

                                    $('txtDescripcionArt').innerHTML = '';
                                                  
                                    $('txtCodigoArt').className = 'inputInicial';
                                    $('txtCantDisponible').className = 'inputInicial';      
                                    $('txtCantidadArt').className = 'inputInicial';
                                    $('lstPrecioArt').className = 'inputInicial';
                                    $('lstIvaArt').className = 'inputInicial';
                                    $('txtPrecioRepuesto').className = 'inputInicial';

                                    $('tdMsjArticulo').style.display = 'none';
                                    $('trPieTotalPaq').style.display='none';

                                    xajax_cargaLstBusq();
                                    xajax_buscarPaquete(xajax.getFormValues('frmBuscarPaquete'));
                                   
                                    $('tdListadoPaquetes').style.display='';
                                    $('tblGeneralPaquetes').style.display='';
                                    $('tblArticulo').style.display='none';
                                    
                                    $('tblBusquedaPaquete').style.display='';
                                    
                                   
                                    
                                    $('btnAsignarPaquete').style.display='none';
                                    $('btnCancelarDivSecPaq').style.display='none';

                                    $('tdDivMsjInfoRpto').style.display='none';

                                    $('tblListadoTemparioPorPaquete').style.display='none';
                                    $('tblListadoRepuestosPorPaquete').style.display='none';
                                    $('hddEscogioPaquete').value='';
									$('tblNotas').style.display='none';
                                    $('tblListadoTot').style.display='none';
                                    $('tblBusquedaTot').style.display='none';
                                    $('tblListadoTempario').style.display='none';  
                                    $('tblTemparios').style.display='none';  
                                    $('tdHrTblPaquetes').style.display='';

                                    $('tdBtnAccionesPaq').style.display='';
                                    
                                } else {
                                    validarCampo('txtIdCliente','t','');
                                    validarCampo('lstTipoOrden','t','lista');

                                    alert('Los campos señalados en rojo son requeridos');
                                    return false;
                                }" title="Agregar Paquete"><img src="../img/iconos/ico_agregar.gif"/></button>
                               <!--  xajax_listado_paquetes_por_modelo(0,'','',$('txtIdEmpresa').value + '|' + $('hdd_id_modelo').value); -->
                            <button type="button" id="btnEliminarPaq" name="btnEliminarPaq" onclick="xajax_eliminarPaquete(xajax.getFormValues('frm_agregar_paq'));" title="Eliminar Articulo"><img src="../img/iconos/ico_quitar.gif"/></button></td>
                            <td width="56%" align="left">PAQUETES</td>
                          </tr>
                        </table></td>
                    </tr>
                      <tr class="tituloColumna">
                            <td align="center" id="tdInsElimPaq" class="color_column_insertar_eliminar_item" style="width:20px"><input type="checkbox" id="cbxItmPaq" onclick="selecAllChecks(this.checked,this.id,2);"   /> </td>
                            <td width="5%" class="celda_punteada">C&oacute;digo</td>
                            <td width="71%" class="celda_punteada">Descripci&oacute;n</td>
                            <td width="14%" align="center" class="celda_punteada">Total</td>
                            <td width="7%" class="celda_punteada">Acciones</td>
                            <td style="text-align:center; width:20px; display:none" id="tdPaqAprob" class="color_column_aprobacion_item"><input type="checkbox" id="cbxItmPaqAprob" onclick="selecAllChecks(this.checked,this.id,2); xajax_calcularTotalDcto();" checked="checked"   /></td>
                     </tr>
                    <tr id="trm_pie_paquete"></tr>
                </table>
			</form>          </td>
        </tr>
        <tr>
          <td align="left">&nbsp;</td>
        </tr>
        <tr>
        	<td >            				</td>
        </tr>
        <tr>
            <td>
            	 <form id="frmListaArticulo" name="frmListaArticulo" style="margin:0">
                    <table width="100%" border="0" cellpadding="0" cellspacing="0" id="tblListaArticulo">
               		 <tr >
                          <td height="30" colspan="11" class="tituloArea">
                        <table width="100%" border="0" cellpadding="0" cellspacing="0">
                              <tr>
                                <td width="40%" height="22" align="left">
                                  <button type="button" id="btnInsertarArt" name="btnInsertarArt" onclick="
                                        if (validarCampo('txtIdCliente','t','') == true
											&& validarCampo('lstTipoOrden','t','lista') == true && validarCampo('txtUnidadBasica','t','') == true) {
                                            document.forms['frmBuscarArticulo'].reset();
                                            document.forms['frmDatosArticulo'].reset();
                                            $('txtDescripcionArt').innerHTML = '';
                                                          
                                            $('txtCodigoArt').className = 'inputInicial';
                                            $('txtPrecioRepuesto').className = 'inputInicial';
                                            $('txtCantDisponible').className = 'inputInicial';      
                                            $('txtCantidadArt').className = 'inputInicial';
                                            $('lstPrecioArt').className = 'inputInicial';
                                            $('lstIvaArt').className = 'inputInicial';
                                            
                                            $('tdMsjArticulo').style.display = 'none';
                                            
                                            xajax_cargaLstBusq();
                                            xajax_buscarArticulo(xajax.getFormValues('frmBuscarArticulo'), xajax.getFormValues('frmPresupuesto'));
                                            
                                            $('tblGeneralPaquetes').style.display='none';
                                            $('tblNotas').style.display='none';
										    $('tblListadoTot').style.display='none';
                                            $('tblBusquedaTot').style.display='none';

                                            $('tblListadoTempario').style.display='none';  
                                            $('tblTemparios').style.display='none';  
                                            $('tdHrTblPaquetes').style.display='none';
                                 			$('tblArticulo').style.display='';
											$('tdListadoArticulos').style.display='';
                                            
                                        } else {
                                            validarCampo('txtIdCliente','t','');
                                  			validarCampo('lstTipoOrden','t','lista');
                                            
                                            alert('Los campos señalados en rojo son requeridos');
                                            return false;
                                        }" title="Agregar Articulo"><img src="../img/iconos/ico_agregar.gif"/></button>
                         
                                <button type="button" id="btnEliminarArt" name="btnEliminarArt" onclick="xajax_eliminarArticulo(xajax.getFormValues('frmListaArticulo'));" title="Eliminar Articulo"><img src="../img/iconos/ico_quitar.gif"/></button>                                </td>
                                <td width="60%" align="left">REPUESTOS GENERALES                               </td>
                          </tr>
                  </table>                  </td>
                </tr>
                </table>
 
                    <table width="100%" border="0" cellpadding="0">
                    <tr class="tituloColumna">
                            <td style="width:20px" id="tdInsElimRep" class="color_column_insertar_eliminar_item"><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,3);"/>
                            <!-- EL ULTIMO PARAMETRO ES EL ID DEL FORMULARIO ASIGNADO POR EL HTML DINAMICAMENTE.--></td>
                            <td >Sección</td>
                              <td >Tipo</td>
                              <td >Código</td>
                              <td >Descripción</td>
                              <td >Cantidad</td>
                              <td >Precio Unit.</td>
                              <td ><?php echo nombreIva(1); ?></td>
                              <td >Total</td>
                              <td style="text-align:center; width:20px; display:none" id="tdRepAprob" class="color_column_aprobacion_item" ><input type="checkbox" id="cbxItmAprob" onclick="selecAllChecks(this.checked,this.id,3); xajax_calcularTotalDcto();" checked="checked"    /></td>
                    </tr>
                        <tr id="trItmPie"></tr>
                    </table>
			  </form>            </td>
        </tr>
        <tr>
        <td>&nbsp;</td>
        </tr>
         <tr>
            <td>
            <form id="frmListaManoObra" name="frmListaManoObra" style="margin:0">
                <table border="0" width="100%">
                <tr >
                  <td colspan="16" class="tituloArea"><table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                      <td width="40%" height="22" align="left">
                       <button type="button" id="btnInsertarTemp" name="btnInsertarTemp" onclick="
                                if (validarCampo('txtIdCliente','t','') == true
									&& validarCampo('lstTipoOrden','t','lista') == true && validarCampo('txtUnidadBasica','t','') == true
                                ) {
                                    document.forms['frmBuscarArticulo'].reset();
                                    document.forms['frmDatosArticulo'].reset();
                                    document.forms['frmDatosTempario'].reset();
                                    document.forms['frmBuscarTempario'].reset();


                                    $('txtDescripcionArt').innerHTML = '';
                                                  
                                    $('txtCodigoArt').className = 'inputInicial';
                                    $('txtCantDisponible').className = 'inputInicial';      
                                    $('txtCantidadArt').className = 'inputInicial';
                                    $('lstPrecioArt').className = 'inputInicial';
                                    $('lstIvaArt').className = 'inputInicial';
                                    $('txtPrecioRepuesto').className = 'inputInicial';
                                    
                                    xajax_cargaLstBusq();
                                    xajax_buscarTempario(xajax.getFormValues('frmBuscarPaquete'),xajax.getFormValues('frmTotalPresupuesto'));
                                              
                                    $('frmBuscarTempario').style.display = '';                                  
       								$('tdMsjArticulo').style.display = 'none';
                                   	$('tblListadoTempario').style.display='';  
                                    $('tblTemparios').style.display='';  
                                    $('tdHrTblPaquetes').style.display='none';
                                    $('tblArticulo').style.display='none';
                                    $('tblPaquetes').style.display='none';
                                    $('tblPaquetes2').style.display='none';
                                    $('tblNotas').style.display='none';
                                    $('tblListadoTot').style.display='none';
                                    $('tblBusquedaTot').style.display='none';

                                    $('tblGeneralPaquetes').style.display='';
                                    $('tblBusquedaPaquete').style.display='none';
                                    $('tdBtnAccionesPaq').style.display='none';
                                    $('tblListadoRepuestosPorPaquete').style.display='none';
                                    
                                    $('tdListadoTemparioPorUnidad').style.display='';
                                    $('tblTemparios').style.display='';  
                                    $('tdBtnAccionesPaq').style.display='none';  

                                                                                                                                    


                                    $('hddEscogioPaquete').value='';
                                } else {
                                    validarCampo('txtIdCliente','t','');
                                    validarCampo('lstTipoOrden','t','lista');

                                    
                                    alert('Los campos señalados en rojo son requeridos');
                                    return false;
                                }" title="Agregar Articulo"><img src="../img/iconos/ico_agregar.gif"/></button>
                               <!--  xajax_listado_paquetes_por_modelo(0,'','',$('txtIdEmpresa').value + '|' + $('hdd_id_modelo').value); -->
                              <button type="button" id="btnEliminarTemp" name="btnEliminarTemp" onclick="xajax_eliminarTempario(xajax.getFormValues('frmListaManoObra'));" title="Eliminar Tempario"><img src="../img/iconos/ico_quitar.gif"/></button></td>
                      <td width="60%" align="left">MANO DE OBRA GENERAL</td>
                    </tr>
                  </table></td>
                </tr>
                <tr class="tituloColumna">
                    <td style="width:20px" id="tdInsElimManoObra" class="color_column_insertar_eliminar_item"><input type="checkbox" id="cbxItmTemp" onclick="selecAllChecks(this.checked,this.id,4);"   /></td>
                 
                    <td  >Secci&oacute;n</td>
                    <td  >Subsecci&oacute;n</td>
                    <td  >Código Tempario</td>
                    <td  >Descripción</td>
                    <td  >Modo</td>
                    <td  >Operador</td>
                    <td  >Precio</td>
                    <td  >Total</td>
                    <td  >Origen</td>
                    <td style="width:20px; display:none"  id="tdTempAprob" class="color_column_aprobacion_item"><input type="checkbox" id="cbxItmTempAprob" onclick="selecAllChecks(this.checked,this.id,4); xajax_calcularTotalDcto();" checked="checked" /></td>
                </tr>
                <tr id="trm_pie_tempario"></tr>
                </table>
			</form>			</td>
        </tr>
        <tr>
        <td>&nbsp;</td>
        </tr>
        <tr>
          <td>
          <form id="frmListaTot" name="frmListaTot" style="margin:0; display:none">
          <table border="0" width="100%" >
            <tr >
              <td colspan="19" class="tituloArea"><table width="100%" border="0" cellpadding="0" cellspacing="0">
                  <tr>
                    <td width="36%" height="22" align="left"><button type="button" id="btnInsertarTot" name="btnInsertarTot" onclick="
                                    if (validarCampo('txtIdCliente','t','') == true
									&& validarCampo('lstTipoOrden','t','lista') == true
                                ) {
                                    document.forms['frmBuscarArticulo'].reset();
                                    document.forms['frmDatosArticulo'].reset();
                                    document.forms['frmDatosTempario'].reset();

                                    $('txtDescripcionArt').innerHTML = '';
                                                  
                                    $('txtCodigoArt').className = 'inputInicial';
                                    $('txtCantDisponible').className = 'inputInicial';      
                                    $('txtCantidadArt').className = 'inputInicial';
                                    $('lstPrecioArt').className = 'inputInicial';
                                    $('lstIvaArt').className = 'inputInicial';
                                    $('txtPrecioRepuesto').className = 'inputInicial';
                                    
                                    
                                    xajax_cargaLstBusq();
                                    //xajax_buscarTempario(xajax.getFormValues('frmBuscarPaquete'),xajax.getFormValues('frmTotalPresupuesto'));

                                    xajax_buscarTot(xajax.getFormValues('frmBuscarTot'),xajax.getFormValues('frmTotalPresupuesto'));
                                    
                                   $('frmBuscarTempario').style.display = 'none';
                                 

       								$('tdMsjArticulo').style.display = 'none';
                                   	$('tblListadoTempario').style.display='none';  
                                    $('tblTemparios').style.display='none';  
                                    $('tdHrTblPaquetes').style.display='none';
                                    $('tblArticulo').style.display='none';
                                    $('tblPaquetes').style.display='none';
                                    $('tblPaquetes2').style.display='none';
                                    $('tblNotas').style.display='none';
                                    $('tblListadoTot').style.display='';
                                    $('tblBusquedaTot').style.display='';

                                    $('tblGeneralPaquetes').style.display='';
                                    $('tblBusquedaPaquete').style.display='none';
                                    $('tdBtnAccionesPaq').style.display='none';
                                    $('tblListadoRepuestosPorPaquete').style.display='none';
                                    
                                    $('tdListadoTemparioPorUnidad').style.display='';
                                    $('tblTemparios').style.display='';  
                                    $('tdBtnAccionesPaq').style.display='none';
                                    $('hddEscogioPaquete').value='';
                                } else {
                                    validarCampo('txtIdCliente','t','');
                                    validarCampo('lstTipoOrden','t','lista');

                                    
                                    alert('Los campos señalados en rojo son requeridos');
                                    return false;
                                }" title="Agregar Nota"><img src="../img/iconos/ico_agregar.gif"/></button>
                       
                      <!--  xajax_listado_paquetes_por_modelo(0,'','',$('txtIdEmpresa').value + '|' + $('hdd_id_modelo').value); -->
                        <button type="button" id="btnEliminarTot" name="btnEliminarTot" onclick="if($('hddAccionTipoDocumento').value != 1){ if($('btnEliminarTot').readOnly == true){ alert('Usted no tiene acceso para realizar esta acción, debe ingresar la clave de permiso'); xajax_formClave(xajax.getFormValues('frmTotalPresupuesto'), 'elim_tot'); $('tblPorcentajeDescuento').style.display='';
							$('tblClaveDescuento').style.display = 'none'; }else{
                        
                        xajax_eliminarTot(xajax.getFormValues('frmListaTot'));
                        }  }else {alert($('hddAccionTipoDocumento').value); xajax_eliminarTot(xajax.getFormValues('frmListaTot')); }" title="Eliminar T.O.T" readonly="readonly"><img src="../img/iconos/ico_quitar.gif"/></button></td>
                    <td width="100%" align="left">TRABAJOS OTROS TALLERES (T.O.T)</td>
                  </tr>
              </table>              </td>
            </tr>
            <tr class="tituloColumna">
              <td  id="tdInsElimTot" class="color_column_insertar_eliminar_item" style="width:20px"><input type="checkbox" id="cbxItmTot" onclick="selecAllChecks(this.checked,this.id,5);"   /></td>
              <td  >Nro. TOT</td>
              <td  >Proveedor</td>
              <td  >Tipo Pago</td>
              <td  >Monto T.O.T</td>
              <td  >Porcentaje TOT</td>
              <td  >Monto Total</td>
        
              
              <td style="width:20px"  id="tdTotAprob" class="color_column_aprobacion_item"><input type="checkbox" id="cbxItmTotAprob" onclick="selecAllChecks(this.checked,this.id,5);  xajax_calcularTotalDcto();" checked="checked"  /></td>
            </tr>
            <tr id="trm_pie_tot"></tr>
          </table> 
          </form>          </td>
        </tr>
        <tr>
        	<td>
                <form id="frmListaNota" name="frmListaNota" style="margin:0">
                    <table border="0" width="100%">
                    <tr >
                      <td colspan="12" class="tituloArea"><table width="100%" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                          <td width="40%" height="22" align="left">
                           <button type="button" id="btnInsertarNota" name="btnInsertarNota" onclick="
                                    if (validarCampo('txtIdCliente','t','') == true
                                        && validarCampo('lstTipoOrden','t','lista') == true && validarCampo('txtUnidadBasica','t','') == true) {
                                        document.forms['frmBuscarArticulo'].reset();
                                        document.forms['frmDatosArticulo'].reset();
                                        document.forms['frmDatosTempario'].reset();
                                        document.forms['frmDatosNotas'].reset();
    
                                        $('txtDescripcionArt').innerHTML = '';
                                                      
                                        $('txtCodigoArt').className = 'inputInicial';
                                        $('txtCantDisponible').className = 'inputInicial';      
                                        $('txtCantidadArt').className = 'inputInicial';
                                        $('lstPrecioArt').className = 'inputInicial';
                                        $('lstIvaArt').className = 'inputInicial';
                                        $('txtPrecioRepuesto').className = 'inputInicial';
                                                                                
                                       //Lo traje de buscar tempario para mostrar formulario de notas
                                        $('tblListados').style.display='none';
                                        
                                        $('tblLogoGotoSystems').style.display='none';
                                        
                                        if ($('divFlotante').style.display == 'none') {
                                            $('divFlotante').style.display='';
                                            centrarDiv($('divFlotante'));
                                            //$('txtDescripcionBusq').focus();
                                            //$('divFlotante').width= '400px';
                                        }
                                        
										$('tdFlotanteTitulo').innerHTML = 'Notas / Cargos Adicionales';
                                        $('tblNotas').style.display='';
                                       
    									centrarDiv($('divFlotante'));
                                       
                                        $('tdMsjArticulo').style.display = 'none';
                                        $('tdHrTblPaquetes').style.display='none';
                                        $('tblArticulo').style.display='none';
                                        $('tblPaquetes').style.display='none';
                                        $('tblPaquetes2').style.display='none';
                                        $('tblGeneralPaquetes').style.display='';
                                        $('tblBusquedaPaquete').style.display='none';
                                        $('tdBtnAccionesPaq').style.display='none';
                                        $('tblListadoRepuestosPorPaquete').style.display='none';
                                        $('tblListadoTot').style.display='none';
                                   	    $('tblBusquedaTot').style.display='none';

                                        $('tdListadoTemparioPorUnidad').style.display='';
                                        $('tblTemparios').style.display='none';  
                                        $('tblListadoTempario').style.display='none';  
    
                                        $('tdBtnAccionesPaq').style.display='none';  
    
                                        $('hddEscogioPaquete').value='';
                                    } else {
                                        validarCampo('txtIdCliente','t','');
                                        validarCampo('lstTipoOrden','t','lista');
    
                                        
                                        alert('Los campos señalados en rojo son requeridos');
                                        return false;
                                    }" title="Agregar Nota"><img src="../img/iconos/ico_agregar.gif"/></button>
                                   <!--  xajax_listado_paquetes_por_modelo(0,'','',$('txtIdEmpresa').value + '|' + $('hdd_id_modelo').value); -->
                            <button type="button" id="btnEliminarNota" name="btnEliminarNota" onclick="xajax_eliminarNota(xajax.getFormValues('frmListaNota'));" title="Eliminar Nota"><img src="../img/iconos/ico_quitar.gif"/></button></td>
                          <td width="100%" align="left">NOTAS / CARGO ADICIONAL</td>
                        </tr>
                      </table></td>
                    </tr>
                    <tr class="tituloColumna">
                        <td width="38" class="color_column_insertar_eliminar_item" id="tdInsElimNota" style="width:20px">
                      <input type="checkbox" id="cbxItmNota" onclick="selecAllChecks(this.checked,this.id,6);"/></td>
                      <td width="593">Descripción</td>
                      <td width="291">Total</td>
                      <td width="20" class="color_column_aprobacion_item" id="tdNotaAprob" style="width:20px; display:none"><input type="checkbox" id="cbxItmNotaAprob" onclick="selecAllChecks(this.checked,this.id,6);  xajax_calcularTotalDcto();" checked="checked"  /></td>
                    </tr>
                    <tr id="trm_pie_nota"></tr>
                    </table>
                </form>            </td>
        <tr>
        <tr>
        	<td align="right">
            <form id="frmTotalPresupuesto" name="frmTotalPresupuesto" style="margin:0">
                <hr>
                <input type="hidden" id="hddObj" name="hddObj"/>
                <input type="hidden" id="hddObjPaquete" name="hddObjPaquete" readonly="readonly"/>
                <input type="hidden" id="hddObjRepuestosPaquete" name="hddObjRepuestosPaquete" readonly="readonly"/>
                <input type="hidden" id="hddObjTempario" name="hddObjTempario" readonly="readonly"/>
                <input type="hidden" id="hddObjTot" name="hddObjTot" readonly="readonly"/>
                <input type="hidden" id="hddObjNota" name="hddObjNota" readonly="readonly"/>
                <input type="hidden" name="hddTipoDocumento" id="hddTipoDocumento" value="<?php echo $_GET['doc_type'];?>" />  
                <input type="hidden" name="hddAccionTipoDocumento" id="hddAccionTipoDocumento" value="<?php echo $_GET['acc'];?>" />
                <input type="hidden" name="hddMecanicoEnOrden" id="hddMecanicoEnOrden"/>
                <input type="hidden" name="hddItemsCargados" id="hddItemsCargados" />
             	<input type="hidden" name="hddNroItemsPorDcto" id="hddNroItemsPorDcto" value="40"/>     
                <input type="hidden" name="hddObjDescuento" id="hddObjDescuento" />
                <input type="hidden" name="hddItemsNoAprobados" id="hddItemsNoAprobados" />
                <input type="hidden" id="hddOrdenEscogida" name="hddOrdenEscogida"/>
              <table border="0" width="100%">
                <tr>
                    <td  align="right" class="divMsjInfo"  id="tdGastos"  width="40%"  >
                        <table cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                                <td width="25"><img src="../img/iconos/ico_info2.gif" width="25"/></td>
                                <td align="center">
                                    <table>
                                        <tr>
                                            <td><img src="../img/iconos/ico_aceptar.gif" /></td>
                                            <td>Paquete o Repuesto Disponibilidad Suficiente</td>
                                        </tr>
                                        <tr>
                                            <td><img src="../img/iconos/ico_alerta.gif" /></td>
                                            <td>Paquete o Repuesto Poca Disponibilidad</td>
                                        </tr>
                                        <tr>
                                            <td><img src="../img/iconos/ico_error.gif" /></td>
                                            <td>Paquete o Repuesto sin Disponibilidad</td>
                                        </tr>
                                        <tr>
                                          <td class="color_column_insertar_eliminar_item" style="border:1px dotted #999999">&nbsp;</td>
                                          <td>Eliminar Item</td>
                                        </tr>
                              </table></td>
                            </tr>
                        </table></td>
                    <td rowspan="5" width="60%">
                        <table border="0" width="100%">
                            <tr align="right">
                                <td class="tituloCampo" width="38%">Sub-Total:</td>
                                <td width="22%">&nbsp;</td>
                                <td colspan="2"></td>
                                <td align="right" width="23%"><input type="text" id="txtSubTotal" name="txtSubTotal" readonly="readonly" size="18" style="text-align:right"/></td>
                            </tr>
                            <tr align="right">
                                <td class="tituloCampo">Descuento:</td>
                                <td>&nbsp;</td>
                                <td width="14%">
                                <input type="hidden" name="hddPuedeAgregarDescuentoAdicional" id="hddPuedeAgregarDescuentoAdicional" /> 
                              	<input type="text" id="txtDescuento" name="txtDescuento" size="6" style="text-align:right" readonly="readonly" value="0" onkeyup="xajax_calcularDcto(xajax.getFormValues('frmPresupuesto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalPresupuesto'),xajax.getFormValues('frm_agregar_paq'),xajax.getFormValues('frmListaManoObra'),xajax.getFormValues('frmListaNota'),xajax.getFormValues('frmListaTot'),'false');" onclick=" if($('hddAccionTipoDocumento').value != 2){ if($('txtDescuento').readOnly == true) { alert('Usted no tiene acceso para realizar esta acción, debe ingresar la clave de permiso');  xajax_formClave(xajax.getFormValues('frmTotalPresupuesto'), 'edc_dcto_ord'); $('tblPorcentajeDescuento').style.display='none';
					$('tblClaveDescuento').style.display = ''; } } "  />%</td>
                                <td width="3%" style="text-align:center"><img id="imgAgregarPorcAdnl" name="imgAgregarPorcAdnl" src="../img/iconos/ico_agregar.gif" align="absmiddle" onclick="if($('hddAccionTipoDocumento').value != 2){ if($('hddPuedeAgregarDescuentoAdicional').value == '') { alert('Usted no tiene acceso para realizar esta acción, debe ingresar la clave de permiso'); $('tblClaveDescuento').style.display = ''; $('tblPorcentajeDescuento').style.display = 'none'; }else
                                {$('tblClaveDescuento').style.display = 'none'; $('tblPorcentajeDescuento').style.display = ''; } xajax_formClave(xajax.getFormValues('frmTotalPresupuesto'), 'agreg_dcto_adnl'); }" />                                </td>
                                <td align="right"><input type="text" id="txtSubTotalDescuento" name="txtSubTotalDescuento" size="18" readonly="readonly" style="text-align:right"/></td>
                            </tr>
                             <tr id="trm_pie_dcto"></tr>
                            <tr align="right">
                              <td class="tituloCampo">Base Imponible:</td>
                              <td></td>
                              <td colspan="2">&nbsp;</td>
                              <td align="right"><input type="text" id="txtBaseImponible" name="txtBaseImponible" size="18" readonly="readonly" style="text-align:right"/></td>
                            </tr>
                            <tr align="right" style="display:none">
                                <td class="tituloCampo">Items Con <?php echo nombreIva(1); ?>:</td>
                              <td></td>
                                <td colspan="2"></td>
                                <td align="right"><input type="text" id="txtGastosConIva" name="txtGastosConIva" readonly="readonly" size="18" style="text-align:right"/></td>
                            </tr>
                            <!--AQUI SE INSERTAN LAS FILAS PARA EL IVA-->
                            <tr align="right" id="trGastosSinIva">
                                <td class="tituloCampo">Monto Exento:</td>
                                <td></td>
                                <td colspan="2"></td>
                                <td align="right"><input type="text" id="txtMontoExento" name="txtMontoExento" readonly="readonly" size="18" style="text-align:right"/></td>
                            </tr>
                            <tr align="right">
                              <td class="tituloCampo"><?php echo nombreIva(1); ?>:</td>
                              <td><input type="hidden" id="hddIdIvaVenta" name="hddIdIvaVenta"  readonly="readonly" /></td>
                              <td><input type="text" id="txtIvaVenta" name="txtIvaVenta"  readonly="readonly" size="6" style="text-align:right" />%</td>
                              <td>&nbsp;</td>
                              <td align="right"><input type="text" id="txtTotalIva" name="txtTotalIva" readonly="readonly" size="18" style="text-align:right"/></td>
                            </tr>
                            <tr>
                                <td colspan="5"><hr style="border:0.5px dotted #999999"/></td>
                            </tr>
                            <tr align="right" id="trNetoPresupuesto">
                                <td id="tdEtiqTipoDocumento" class="tituloCampo"></td>
                                <td></td>
                                <td colspan="2"></td>
                                <td align="right"><input type="text" id="txtTotalPresupuesto" name="txtTotalPresupuesto" readonly="readonly" size="18" style="text-align:right"/></td>
                            </tr>
                  </table>                     </td>
				</tr>
                <tr>
                	<td>                        </td>
                </tr>
               
                <tr>
                  <td>&nbsp;</td>
                </tr>
                <tr>
                  <td>&nbsp;</td>
                </tr>
                </table>
			</form>			</td>
        </tr>
         <tr>
           <td align="right">&nbsp;</td>
         </tr>
         
         <tr>
           <td align="center" class="divGris">
           <table width="100%" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td width="53%" align="right">Cotizaci&oacute;n v&aacute;lida por</td>
                <td width="2%" id="tdNroDiasVencPresupuesto" align="center">&nbsp;</td>
                <td width="45%" align="left">d&iacute;as</td>
              </tr>
            </table>
            </td>
         </tr>
         <tr>
           <td align="right">&nbsp;</td>
         </tr>
         
         <tr>
           <td align="right"><hr /></td>
         </tr>
         
         <tr>
           <td align="right">&nbsp;</td>
         </tr>
         <tr>
           <td align="right"></td>
         </tr>
        <tr>
        	<td align="right"  class="noprint">
           	  <button class="noprint" type="button" id="btnGuardar" name="btnGuardar" onclick="
                  
                  if($('hddTipoDocumento').value == 3)
                  {
                    validarNroControl(); 
                  }
                  else
                  {
                     if($('hddTipoDocumento').value == 4)
                     {
                         xajax_generarPresupuestoApartirDeOrden(xajax.getFormValues('frmPresupuesto'), xajax.getFormValues('frmTotalPresupuesto'));
                     }
                     else
                     {
                     	if(parseInt($('hddItemsCargados').value) > parseInt($('hddNroItemsPorDcto').value))
                        	alert('La Orden tiene ' + $('hddItemsCargados').value + ' items incluyendo el contenido de Paquetes. El Nro máximo son ' + $('hddNroItemsPorDcto').value + ' items. Si desea continuar elimine items o abra un Nueva Orden.');
                        else
                        	validarFormPresupuesto();
                     }
                  
                  }" style="cursor:default; display:none"><table width="73" align="center" cellpadding="0" cellspacing="0">
           	    <tr><td width="10">&nbsp;</td>
                    <td width="18"><img src="../img/iconos/save.png"/></td>
                    <td width="10">&nbsp;</td>
                    <td width="47">Guardar</td>
                    </tr></table></button>
                     <button class="noprint" type="button" id="btnCancelar" name="btnCancelar" onclick="window.location.href='sa_cotizacion_list.php';" style="cursor:default"><table width="77" align="center" cellpadding="0" cellspacing="0">
                       <tr><td width="10">&nbsp;</td>
                    <td width="18"><img src="../img/iconos/ico_error.gif"/></td>
                    <td width="10">&nbsp;</td>
                    <td width="51">Cancelar</td>
                    </tr></table></button>             </td>
        </tr>
        </table>
  </div>
	<?php include("menu_serviciosend.inc.php"); ?>
</div>
</body>
</html>
<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    <table border="0" id="tblListados" style="display:none" width="100%">
    <tr id="trBuscarPresupuesto">
    	<td>
        	<form id="frmBuscarPresupuesto" name="frmBuscarPresupuesto" method="post" style="margin:0">
            	<table>
                <tr>
                	<td>Empresa / Sucursal:</td>
                	<td id="tdlstEmpresaBusq">
                    	<select id="lstEmpresaBusq" name="lstEmpresaBusq">
                        	<option>Seleccione...</option>
                        </select>
                        <script>
                        xajax_cargaLstEmpresaBusq();
						</script>
					</td>
                    <td><input type="button" id="btnBuscarPresupuesto" name="btnBuscarPresupuesto" onclick="xajax_buscarDcto(xajax.getFormValues('frmBuscarPresupuesto'));" value="Buscar..."/></td>
                </tr>
                </table>
            </form>
        </td>
    </tr>
    <tr>
    	<td>&nbsp;</td>
    </tr>
    <tr>
       <td>
    <form id="frmBuscarVale" name="frmBuscarVale" onsubmit="xajax_buscarCliente(xajax.getFormValues('frmPresupuesto'),xajax.getFormValues('frmBuscarVale')); return false;" style="margin:0">
                <table align="right" border="0">
                <tr>
                	<td align="right" style="visibility:hidden" class="tituloCampo" width="100">Empresa:</td>
                    <td id="tdlstEmpresa" style="visibility:hidden">
                        <select id="lstEmpresa" name="lstEmpresa">
                            <option value="-1">Todos...</option>
                        </select>
                        <script>
                          //xajax_cargaLstEmpresas();
                        </script>                    </td>
                    <td class="tituloCampo" style="visibility:hidden">Tipo de Orden:</td>
                    <td id="tdlstTipoOrden" style="visibility:hidden">
                        <select id="lstTipoOrden" name="lstTipoOrden">
                            <option value="-1">Todos...</option>
                        </select>
                        <script>
                           // xajax_cargaLstTipoOrden();
                        </script>                    </td>
                    <td class="tituloCampo" style="visibility:hidden">Estado Orden:</td>
                    <td id="tdlstEstadoOrden" style="visibility:hidden">
                    	 <select id="lstEstadoOrden" name="lstEstadoOrden">
                            <option value="-1">Todos...</option>
                        </select>
                        <script>
                            //xajax_cargaLstEstadoOrden();
                        </script>   
                    </td></tr>
                    <tr id="trBuscarCliente" style="display:none">
                    <td align="right" class="tituloCampo" width="150">Código / Descripción:</td>
                    <td id="tdlstAno"><input type="text" id="txtBusq" name="txtBusq" onkeyup="$('btnBuscarCliente').click();"/></td>
                    <td>
                        <input type="button" class="noprint" id="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmPresupuesto'),xajax.getFormValues('frmBuscarVale'));" value="Buscar" />
						<input type="button" class="noprint" onclick="document.forms['frmBuscarVale'].reset(); $('btnBuscarCliente').click();" value="Ver Todo" />                    </td>
                    </tr>
                    
                <tr id="trBuscarUnidad" style="display:none">
                  <td align="right" class="tituloCampo" width="150">Código / Descripción:</td>
                    <td id="tdlstAno"><input type="text" id="txtPalabra" name="txtPalabra" onkeyup="$('btnBuscar').click();"/></td>
                    <td>
                        <input type="button" class="noprint" id="btnBuscar" onclick="xajax_buscarUnidad(xajax.getFormValues('frmPresupuesto'),xajax.getFormValues('frmBuscarVale'));" value="Buscar" />
						<input type="button" class="noprint" onclick="document.forms['frmBuscarVale'].reset(); $('btnBuscar').click();" value="Ver Todo" />                    </td>
                </tr>
                </table>
        	</form>
    </td>
    </tr>
    <tr>
    	<td>&nbsp;</td>
    </tr>
    <tr>
 
    	<td id="tdListado">
        	<table width="100%">
            <tr class="tituloColumna">
            	<td>Presupuesto</td>
                <td>Nº Presupuesto Propio</td>
                <td>Nº Referencia</td>
                <td>Fecha</td>
                <td>Cliente</td>
                <td>Articulos</td>
                <td>Presupuestos</td>
                <td>Pendientes</td>
                <td>Total</td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right">
	        <hr>
            <input type="button" onclick="$('divFlotante').style.display='none'; $('tblLogoGotoSystems').style.display='';" value="Cancelar">
        </td>
    </tr>
    </table>
    <table border="0" id="tblListados" style="display:none" width="980px">
    <tr id="trBuscarPresupuesto">
    	<td>
        	<form id="frmBuscarPresupuesto" name="frmBuscarPresupuesto" method="post" style="margin:0">
            	<table>
                <tr>
                	<td>Empresa / Sucursal:</td>
                	<td id="tdlstEmpresaBusq">
                    	<select id="lstEmpresaBusq" name="lstEmpresaBusq">
                        	<option>Seleccione...</option>
                        </select>
                        <script>
                        xajax_cargaLstEmpresaBusq();
						</script>
					</td>
                    <td><input type="button" id="btnBuscarPresupuesto" name="btnBuscarPresupuesto" onclick="xajax_buscarDcto(xajax.getFormValues('frmBuscarPresupuesto'));" value="Buscar..."/></td>
                </tr>
                </table>
            </form>
        </td>
    </tr>
    <tr>
    	<td id="tdListado">
        	<table width="100%">
            <tr class="tituloColumna">
            	<td>Presupuesto</td>
                <td>Nº Presupuesto Propio</td>
                <td>Nº Referencia</td>
                <td>Fecha</td>
                <td>Cliente</td>
                <td>Articulos</td>
                <td>Presupuestos</td>
                <td>Pendientes</td>
                <td>Total</td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right">
	        <hr>
            <input type="button" onclick="$('divFlotante').style.display='none'; $('tblLogoGotoSystems').style.display='';" value="Cancelar">
        </td>
    </tr>
    </table>
	
    <!-- TABLA DE PAQUETES -->     
     <table id="tblGeneralPaquetes" cellpadding="0" border="0" cellspacing="0" >
     <tr>
     <td id="tdHrTblPaquetes">
    <table border="0" id="tblPaquetes" style="display:none" width="800px">
        <tr>
            <td>
            <form id="frmBuscarPaquete" name="frmBuscarPaquete" style="margin:0" onsubmit="xajax_asignarArticulo($('txtDescripcionBusq').value, xajax.getFormValues('frmPresupuesto')); xajax_buscarArticulo(xajax.getFormValues('frmBuscarPaquete'), xajax.getFormValues('frmPresupuesto')); return false;">
                <table border="0" width="100%" id="tblBusquedaPaquete">
                    <tr style="visibility:hidden" align="left">
                        <td align="right" class="tituloCampo" width="6%">&nbsp;</td>
                      <td  width="19%">&nbsp;</td>
                      <td align="right" class="tituloCampo" width="11%">&nbsp;</td>
                      <td width="17%">&nbsp;</td>
                      <td width="16%"></td>
                      <td width="24%"></td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo" style="visibility:hidden">&nbsp;</td>
                        <td style="visibility:hidden">&nbsp;</td>
                        <td align="right" class="tituloCampo" style="visibility:hidden">&nbsp;</td>
                        <td  style="visibility:hidden">&nbsp;</td>
                        <td align="right" class="tituloCampo">Código / Descripci&oacute;n:</td>
                        <td><input type="text" id="txtDescripcionBusq" name="txtDescripcionBusq" onkeyup="$('btnBuscarPaquete').click();" size="30"/></td>
                        <td width="7%">
                        <input type="button" id="btnBuscarPaquete" name="btnBuscarPaquete" onclick="xajax_buscarPaquete(xajax.getFormValues('frmBuscarPaquete'));" value="Buscar..."/></td>
                    </tr>
                </table>
            </form>
            </td>
        </tr>
        </table>
        <form id="frmDatosPaquete" name="frmDatosPaquete" style="margin:0">
            <table border="0" id="tblPaquetes2" style="display:none" width="800px" >

        <tr>

            <!-- xajax_asignarArticulo($('txtDescripcionBusq').value, xajax.getFormValues('frmPresupuesto')); xajax_buscarArticulo(xajax.getFormValues('frmBuscarPaquete'));  -->
            <td id="tdListadoPaquetes">
                <table width="100%">
                    <tr class="tituloColumna">
                        <td width="10%"></td>
                        <td width="30%">Código</td>
                        <td width="60%">Descripción</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td >
               <table width="100%" id="tblListadoTemparioPorPaquete" style="display:none">
                         <input type="hidden" id="txtCodigoPaquete" name="txtCodigoPaquete" class="text_sin_border" style="display:none; text-align:center;" readonly="readonly" />
                         <input type="hidden" name="txtDescripcionPaquete" id="txtDescripcionPaquete" class="text_sin_border" style="display:none" readonly="readonly" />
                         <input type="text" name="hddEscogioPaquete" id="hddEscogioPaquete" class="" style="display:none" readonly="readonly"  />
                    <tr>
                        <td id="tdListadoTempario">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                            	<td colspan="5" class="tituloPaginaServicios" id="tdEncabPaquete"></td>
                       		</tr>
                            <tr>
                            <td colspan="4" class="tituloArea" align="center">Mano de Obra</td>
                            </tr>
                                <tr class="tituloColumna">
                                    <td>Código</td>
                                    <td>Descripción</td>
                                    <td>Modo</td>
                                    <td>Precio</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
               </table>
            </td>
        </tr>
        <tr>
             <td>
                <table width="100%" id="tblListadoRepuestosPorPaquete" style="display:none">
                    
                    <tr>
                        <td>
                        <div id="tdListadoRepuestos" style="overflow:scroll; height:140px;">
                            <table width="98%">
                            <tr>
                       		 <td colspan="5" class="tituloArea" align="center">Repuestos</td>
                   			 </tr>
                                <tr class="tituloColumna">
                                    <td></td>
                                    <td>C&oacute;digo</td>
                                    <td>Descripci&oacute;n</td>
                                    <td>Marca</td>
                                    <td>Cantidad</td>
                                </tr>
                            </table>
                        </div>
                        </td>
                    </tr>
                    <tr >
                         <table width="100%" id="trPieTotalPaq" style="display:none"  >                
                            <tr>
                              <td width="36%" rowspan="2" align="left"><input type="hidden" id="hddManObraAproXpaq" name="hddManObraAproXpaq" readonly="readonly" />
                               <input type="hidden" id="hddTotalArtExento" name="hddTotalArtExento" readonly="readonly" />
                                <input type="hidden" id="hddTotalArtConIva" name="hddTotalArtConIva" readonly="readonly" /></td>
                              <td width="10%" height="26" align="left" class="tituloColumna">M.O Aprob.</td>
                              <td width="12%" align="left"><input type="text" id="txtNroManoObraAprobPaq" name="txtNroManoObraAprobPaq" readonly="readonly" value="0" /></td>
                              <td width="9%" class="tituloColumna" align="right">Rep.Aprob.</td>
                              <td width="13%" align="left" ><input type="text" id="txtNroRepuestoAprobPaq" name="txtNroRepuestoAprobPaq" readonly="readonly" value="0" /></td>
                              <td width="11%" align="right" class="tituloColumna" >Total Aprob.</td>
                              <td width="9%" align="left" ><input type="text" id="txtTotalItemAprobPaq" name="txtTotalItemAprobPaq" readonly="readonly" value="0" /></td>
                            </tr>
                            <tr>
                              <td align="left" class="tituloColumna">Total M.O</td>
                              <td align="left"><input type="text" id="txtTotalManoObraPaq" name="txtTotalManoObraPaq" readonly="readonly" /></td>
                              <td class="tituloColumna" align="right">Total Repto</td>
                              <td align="left" ><input type="text" id="txtTotalRepPaq" name="txtTotalRepPaq" readonly="readonly" /></td>
                              <td align="right" class="tituloColumna" >Total Paq.</td>
                              <td align="left" ><input type="text" id="txtPrecioPaquete" name="txtPrecioPaquete" readonly="readonly" /></td>
                            </tr>
                        </table>
                    </tr>
                    <tr>
        	<td class="divMsjInfo" colspan="8" width="35%" id="tdDivMsjInfoRpto">
                	<table cellpadding="0" cellspacing="0" width="100%">
                    
                    <tr>
                        <td width="25"><img src="../img/iconos/ico_info2.gif" width="25"/></td>
                        <td colspan="8" align="center">
                            <table>
                            <tr>
                                <td width="32"><img src="../img/iconos/ico_aceptar.gif" /></td>
                                <td width="263">Art&iacute;culo con Disponibilidad Suficiente</td>
                                <td width="32"><img src="../img/iconos/ico_alerta.gif" /></td>
                                <td width="238">Art&iacute;culo con Poca Disponibilidad</td>
                                <td width="32"><img src="../img/iconos/ico_error.gif" /></td>
                                <td width="234">Art&iacute;culo sin Disponibilidad<input type="hidden" id="hddRepAproXpaq" name="hddRepAproXpaq" readonly="readonly" />
                                 <input type="hidden" name="hddArticuloSinDisponibilidad" id="hddArticuloSinDisponibilidad" value="" />
                                 <input type="hidden" name="hddArtEnPaqSinPrecio" id="hddArtEnPaqSinPrecio" value="" />
                                 <input type="hidden" name="hddTempEnPaqSinPrecio" id="hddTempEnPaqSinPrecio" value="" />
                                 <input type="hidden" name="hddArtNoDispPaquete" id="hddArtNoDispPaquete" value="" />
                                 <input type="hidden" name="hddObjRepuestoPaq" id="hddObjRepuestoPaq" value="" />
                                 <input type="hidden" name="hddObjTemparioPaq" id="hddObjTemparioPaq" value="" /></td>
                                <td width="41"><img src="../img/iconos/50.png" /></td>
                                <td width="223">Sin Precio Asignado</td>
                            </tr>
                            </table>
                         </td>
                    </tr>
                    </table>                
                    </td>
        </tr>
               </table>
            </td>
        </tr>
        
        <tr>
            <td id="tdBtnAccionesPaq" align="right">
                <hr/>
               
                <input type="button" onclick="validarPaquete();" id="btnAsignarPaquete" value="Aceptar" >
                <input type="button" id="btnCancelarDivPpalPaq" onclick="$('divFlotante2').style.display='none'; $('divFlotante').style.display='none'; $('tblLogoGotoSystems').style.display='';
                 
                 
                " value="Cancelar" >
                <input type="button" id="btnCancelarDivSecPaq" onclick="
                    $('tdListadoTempario').style.display = 'none';
                    $('tdListadoRepuestos').style.display = 'none';	
                    $('tblListadoRepuestosPorPaquete').style.display = 'none';	
                    $('trPieTotalPaq').style.display = 'none';	
                    $('tdDivMsjInfoRpto').style.display = 'none';
                    $('tdListadoPaquetes').style.display='';
                    $('tblBusquedaPaquete').style.display='';
                    $('btnCancelarDivPpalPaq').style.display='';
                    this.style.display = 'none';
                    $('btnAsignarPaquete').style.display='none';

                    
                   " value="Cancelar" >                   
                   
                   
                   
            </td>
        </tr>
    </table>
        </form>
        </td>
        </tr>
     </table>   
     <table border="0" id="tblTemparios" style="display:none" >
        <tr>
            <td>
            <form id="frmBuscarTempario" name="frmBuscarTempario" style="margin:0" onsubmit="xajax_asignarArticulo($('txtDescripcionBusq').value, xajax.getFormValues('frmPresupuesto')); xajax_buscarArticulo(xajax.getFormValues('frmBuscarPaquete'), xajax.getFormValues('frmPresupuesto')); return false;">
                <table border="0" id="tblBusquedaTempario" width="800px">
                 	<tr >
                        <td align="right" width="6%">&nbsp;</td>
                      <td width="19%">&nbsp;</td>
                      <td align="right" width="11%">&nbsp;</td>
                      <td width="17%">&nbsp;</td>
                      <td width="16%"></td>
                      <td width="24%"></td>
                    </tr>
                    <tr>
                        <td align="right" class="tituloCampo">Secci&oacute;n:</td>
                        <td id="tdListSeccionTemp">
                            <select id="lstSeccionTemp" name="lstSeccionTemp">
                                <option>Seleccione...</option>
                            </select>
                            <script>
                           	 xajax_cargaLstSeccionTemp();
                            </script>
                        </td>
                        <td align="right" class="tituloCampo">Subsecci&oacute;n:</td>
                        <td id="tdListSubseccionTemp">
                        <select id="lstSubseccionTemp" name="lstSubseccionTemp">
                            <option>Todos...</option>
                        </select>
						<script>
                       	 	xajax_cargaLstSubseccionTemp();
                        </script>
                        </td>
                        <td align="right" class="tituloCampo">Código / Descripci&oacute;n:</td>
                        <td><input type="text" id="txtDescripcionBusq" name="txtDescripcionBusq" onkeyup="$('btnBuscarTempario').click();" size="30"/></td>
                        <td width="7%">
                        <input type="button" id="btnBuscarTempario" name="btnBuscarTempario" onclick="xajax_buscarTempario(xajax.getFormValues('frmBuscarTempario'),xajax.getFormValues('frmTotalPresupuesto'));" value="Buscar..."/></td>
                    </tr>
                </table>
            </form>
            </td>
        </tr>
        </table> 
      	<table border="0" id="tblListadoTempario" style="display:none" width="1000px" >

             <tr>                   
               <form id="frmListadoTempario" name="frmListadoTempario" style="margin:0">
             <input type="hidden" name="hddEscogioTempario" id="hddEscogioTempario" style="display:none" readonly="readonly"  />

                <td >
                   <div id="tdListadoTemparioPorUnidad" style="overflow:scroll; height:290px; /*width:1020px;*/">

                    <table width="100%">
                        <tr class="tituloColumna">
                            <td>Código</td>
                            <td>Descripción</td>
                            <td>Modo</td>
                            <td>Precio</td>
                        </tr>
                    </table>
					</div>
                </td>                     
                
                   </form>

             </tr>     
       		 <tr>
                <td>
                    <hr>
                    <form id="frmDatosTempario" name="frmDatosTempario" style="margin:0">
                        <table border="0" width="100%">
                            <tr align="left">
                                <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Código:</td>
                                <td width="15%" > 
                                    <input type="text" id="txtCodigoTemp" name="txtCodigoTemp" readonly="readonly" size="25"/>
                                    <input type="hidden" id="hddIdTemp" name="hddIdTemp" readonly="readonly"/>  
                                      <input type="hidden" id="hddIdDetTemp" name="hddIdDetTemp" readonly="readonly"/> 
                                                                                <!-- hddIdDetTemp NO LO ESTOY UTILIZANDO -->              </td>
                                <td width="9%" >&nbsp;</td>
                                <td align="right" class="tituloCampo" width="12%" >Importe:</td>
                                <td width="41%" >
                                    <input type="text" id="txtPrecioTemp" name="txtPrecioTemp" readonly="readonly"/>
                                    <input type="hidden" id="txtPrecio" name="txtPrecio" readonly="readonly"/>

                                    <input type="hidden" id="hddIdPrecioTemp" name="hddIdPrecioTemp" readonly="readonly"/>                                </td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo">Descripci&oacute;n:</td>
                                <td >
                               		 <input name="txtDescripcionTemp" type="text" id="txtDescripcionTemp" size="60" readonly="readonly" />                                </td>
                                <td >&nbsp;</td>
                                <td class="tituloCampo"  align="right">Secci&oacute;n:</td>
                                <td ><label>
                                  <input type="text" name="txtSeccionTempario" id="txtSeccionTempario" readonly="readonly" size="40" />
                                  <input type="hidden" name="hddSeccionTempario" id="hddSeccionTempario" />
                                </label></td>
                            </tr>
                            <tr align="left">
                              <td align="right" class="tituloCampo">Subsecci&oacute;n:</td>
                              <td ><label>
                                <input type="text" name="txtSubseccionTempario" id="txtSubseccionTempario" readonly="readonly" />
                                <input type="hidden" name="hddIdSubseccionTempario" id="hddIdSubseccionTempario" />
                              </label></td>
                              <td >&nbsp;</td>
                              <td class="tituloCampo"  align="right">Operador:</td>
                              <td >
                              <input type="text" id="txtDescripcionOperador" name="txtDescripcionOperador" readonly="readonly"/>
                              <input type="hidden" id="txtOperador" name="txtOperador" readonly="readonly"/>
                              <input type="hidden" id="hddOrigenTempario" name="hddOrigenTempario"  value="" /></td>
                            </tr>
                            <tr align="left">
                              <td align="right" class="tituloCampo">Modo:</td>
                              <td >
                                <input type="text" name="txtModoTemp" id="txtModoTemp" readonly="readonly" />
                                <input type="hidden" name="txtIdModoTemp" id="txtIdModoTemp" readonly="readonly" />                              </td>
                              <td >&nbsp;</td>
                               <td align="right" class="tituloCampo" width="12%" id="tdMecanico" ><span class="textoRojoNegrita">*</span>Mecanico:</td>
                                  <td id="tdlstMecanico" >
                                      <select id="lstMecanico" name="lstMecanico">
                                        <option value="-1">Seleccione...</option>
                                      </select>                                  </td>
                            </tr>
                        </table>
                    </form>
                </td>
            </tr>
            <tr>
                <td align="right">
                    <hr/>
                   
                    <input type="button" onclick="validarTempario();" id="btnAsignarTemp" value="Aceptar">
                    <input type="button" onclick="$('divFlotante2').style.display='none'; $('divFlotante').style.display='none'; $('tblLogoGotoSystems').style.display='';" value="Cancelar">
                </td>
            </tr>
        </table>
    <table border="0" id="tblArticulo" style="display:none">
    <tr>
    	<td>
        <form id="frmBuscarArticulo" name="frmBuscarArticulo" style="margin:0" onsubmit="xajax_asignarArticulo($('txtDescripcionBusq').value, xajax.getFormValues('frmPresupuesto')); xajax_buscarArticulo(xajax.getFormValues('frmBuscarArticulo'), xajax.getFormValues('frmPresupuesto')); return false;">
        	<table border="0" width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="8%">Marca:</td>
                <td id="tdlstMarcaBusq" width="24%">
                	<select id="lstMarcaBusq" name="lstMarcaBusq">
                    	<option value="-1">Todos...</option>
                    </select>
                </td>
                <td align="right" class="tituloCampo" width="10%">Tipo de Articulo:</td>
                <td id="tdlstTipoArticuloBusq" width="24%">
                	<select id="lstTipoArticuloBusq" name="lstTipoArticuloBusq">
                    	<option value="-1">Todos...</option>
                    </select>
                </td>
                <td align="right" class="tituloCampo" width="10%">Código:</td>
                <td id="tdCodigoArt" width="24%"></td>
			</tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Secci&oacute;n:</td>
                <td colspan="3" id="tdlstSeccionBusq">
                	<select id="lstSeccionBusq" name="lstSeccionBusq">
                    	<option value="-1">Todos...</option>
                    </select>
                </td>
                <td align="right" class="tituloCampo">Descripci&oacute;n:</td>
                <td><input type="text" id="txtDescripcionBusq" name="txtDescripcionBusq" onkeyup="$('btnBuscarArticulo').click();" size="30"/>
                </td>
			</tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Sub-Secci&oacute;n:</td>
                <td colspan="4" id="tdlstSubSeccionBusq">
                	<select id="lstSubSeccionBusq" name="lstSubSeccionBusq">
                    	<option value="-1">Todos...</option>
                    </select>
                </td>
                <td align="right">
                	<input type="submit" style="visibility:hidden" value="."/>
                	<input type="button" id="btnBuscarArticulo" name="btnBuscarArticulo" onclick="xajax_buscarArticulo(xajax.getFormValues('frmBuscarArticulo'), xajax.getFormValues('frmPresupuesto'));" value="Buscar..."/>
                    <input type="button" onclick="document.forms['frmBuscarArticulo'].reset(); $('btnBuscarArticulo').click();" value="Ver Todo"/>
				</td>
			</tr>
			</table>
		</form>
        	<hr>
		</td>
    </tr>
    <tr>
    	<td id="tdListadoArticulos">
        	<table width="100%">
            <tr class="tituloColumna">
            	<td>Código</td>
                <td>Descripción</td>
                <td>Marca</td>
                <td>Tipo</td>
                <td>Sección</td>
                <td>Sub-Sección</td>
                <td>Disponible</td>
                <td>Reservado</td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td>
        	<hr>
        <form id="frmDatosArticulo" name="frmDatosArticulo" style="margin:0">
        	<table border="0" width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Código:</td>
                <td width="18%"><input type="text" id="txtCodigoArt" name="txtCodigoArt" readonly="readonly" size="25"/><input type="hidden" id="hddIdArt" name="hddIdArt" readonly="readonly"/></td>
                <td rowspan="3" valign="top" width="48%"><textarea id="txtDescripcionArt" name="txtDescripcionArt" cols="54" rows="3" readonly="readonly"></textarea></td>
                <td align="right" class="tituloCampo" width="12%">Fecha Ult. Compra:</td>
                <td width="10%"><input type="text" id="txtFechaUltCompraArt" name="txtFechaUltCompraArt" readonly="readonly" size="10"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Sección:</td>
                <td><input type="text" id="txtSeccionArt" name="txtSeccionArt" readonly="readonly" size="25"/></td>
                <td align="right" class="tituloCampo">Fecha Ult. Venta:</td>
                <td><input type="text" id="txtFechaUltVentaArt" name="txtFechaUltVentaArt" readonly="readonly" size="10"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Tipo de Pieza:</td>
                <td><input type="text" id="txtTipoPiezaArt" name="txtTipoPiezaArt" readonly="readonly" size="25"/></td>
                <td align="right" class="tituloCampo">Disponible:</td>
                <td><input type="text" id="txtCantDisponible" name="txtCantDisponible" readonly="readonly" size="10"/></td>
            </tr>
            <tr>
            	<td class="divMsjAlerta" colspan="5" id="tdMsjArticulo" style="display:none"></td>
            </tr>
			</table>
            <hr>
            <table border="0" width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Cantidad:</td>
                <td width="20%"><input type="text" id="txtCantidadArt" name="txtCantidadArt" size="25"/></td>
                <td align="right"  width="12%"></td>
                <td width="20%">
                	<table>
                    <tr>
                    	<td>
                            
						</td>
                        <td></td>
					</tr>
                    </table>
                </td>
                <td rowspan="2" width="35%">
                	<table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                    <tr>
                        <td width="25"><img src="../img/iconos/ico_info2.gif" width="25"/></td>
                        <td align="center">
                            <table>
                            <tr>
                                <td><img src="../img/iconos/ico_aceptar.gif" /></td>
                                <td>Artículo con Disponibilidad Suficiente</td>
                            </tr>
                            <tr>
                                <td><img src="../img/iconos/ico_alerta.gif" /></td>
                                <td>Artículo con Poca Disponibilidad</td>
                            </tr>
                            <tr>
                                <td><img src="../img/iconos/ico_error.gif" /></td>
                                <td>Artículo sin Disponibilidad</td>
                            </tr>
                            </table>
                        </td>
                    </tr>
                    </table>
                </td>
			</tr>
            <tr align="left">
            	<td align="right" class="tituloCampo">Precio:</td>
                <td>
                     <div id="selectPrecio">
                        <select>
                            <option>Seleccione</option>
                        </select>
                     </div>


                <input type="hidden" id="hddIdPrecioRepuesto" name="hddIdPrecioRepuesto" readonly="readonly"/>
                 <input type="hidden" id="hddPrecioRepuestoDB" name="hddPrecioRepuestoDB" readonly="readonly"/>
                <!--<input type="text" id="txtPrecioRepuesto" name="txtPrecioRepuesto" onclick="abrir()" readonly="readonly" size="10"/></td>-->
                <input type="text" id="txtPrecioRepuesto" name="txtPrecioRepuesto" readonly="readonly" size="10"/></td>
                <td align="right" class="tituloCampo"><?php echo nombreIva(1); ?>:</td>
                <td>
                  <input type="hidden" id="hddIdIvaRepuesto" name="hddIdIvaRepuesto" readonly="readonly"/>
                <input type="text" id="txtIvaRepuesto" name="txtIvaRepuesto" readonly="readonly"/></td>
            </tr>
            
            <tr style="display:none">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Precio:</td>
                <td id="tdlstPrecioArt">
                	
                	<select id="lstPrecioArt" name="lstPrecioArt">
                    	<option value="-1">Seleccione...</option>
                    </select>
                </td>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>IVA:</td>
                <td id="tdlstIvaArt">
                        <select id="lstIvaArt" name="lstIvaArt">
                    	<option value="-1">Seleccione...</option>
                    </select>
                </td>
            </tr>
            </table>
        </form>
		</td>
	</tr>
    <tr>
    	<td align="right">
	        <hr/>
            <input type="button" onclick="validarFormArt();" value="Aceptar"/>
            <input type="button" onclick="$('divFlotante2').style.display='none'; $('divFlotante').style.display='none';" value="Cancelar"/>
        </td>
    </tr>
    </table>     
        <form id="frmDatosNotas" name="frmDatosNotas" style="margin:0">
             <table width="34%" border="0" id="tblNotas" style="display: none;" >
                <tr>
                    <td width="40%">&nbsp;</td>
                    <td colspan="3">&nbsp;</td>
                </tr>
                <tr align="left">
                    <td class="tituloCampo">Descripcion :</td>
                    <td colspan="3">
                        <textarea name="txtDescripcionNota" id="txtDescripcionNota" cols="45" rows="5"></textarea>
                    </td>
  </tr>
                 <tr align="left">
                    <td class="tituloCampo">Precio :</td>
                    <td colspan="3">
                         <input type="text" name="txtPrecioNota" id="txtPrecioNota" onkeypress="return validarSoloNumeros(event);" />
                    </td>
                 </tr>
                 <tr>
                    <td>&nbsp;</td>
                    <td colspan="3">&nbsp;</td>
                 </tr>
                 <tr>
                    <td colspan="4"><hr/></td>
                 </tr>
                 <tr>
                    <td>&nbsp;</td>
                    <td width="1%"></td>
                    <td width="9%" align="right">&nbsp;</td>
                    <td width="50%" align="right">
                        <input type="button" name="btnGuardarNota" id="btnGuardarNota" value="Guardar" onclick="validarNota();"/>
                        <input type="button" value="Cancelar" onclick="$('divFlotante2').style.display='none'; $('divFlotante').style.display='none'; $('tblLogoGotoSystems').style.display='';"/>            </td>
                 </tr>
</table>
        </form>
          
          <!-- BUSQUEDA TOT -->
          <form id="frmBuscarTot" name="frmBuscarTot" style="margin:0" onsubmit="xajax_buscarTot(xajax.getFormValues('frmBuscarTot')); return false;">
                <table border="0" width="800px" id="tblBusquedaTot">
                 <tr style="visibility:hidden">
                        <td align="right" class="tituloCampo" width="7%">&nbsp;</td>
                      <td width="20%">&nbsp;</td>
                      <td align="right" class="tituloCampo" width="12%">&nbsp;</td>
                      <td width="17%">&nbsp;</td>
                      <td width="18%"></td>
                      <td colspan="2"></td>
                    </tr>
                    <tr>
                        <td align="right" class="tituloCampo" style="visibility:hidden">&nbsp;</td>
                        <td style="visibility:hidden">&nbsp;</td>
                        <td align="right" class="tituloCampo" style="visibility:hidden">&nbsp;</td>
                        <td style="visibility:hidden">&nbsp;</td>
                        <td align="right" class="tituloCampo">Código / Descripci&oacute;n:</td>
                      <td width="17%"><input type="text" id="txtDescripcionBusq" name="txtDescripcionBusq" onkeyup="$('btnBuscarTot').click();" size="30"/></td>
                      <td width="9%"><input type="button" id="btnBuscarTot" name="btnBuscarTot" onclick="xajax_buscarTot(xajax.getFormValues('frmBuscarTot'),xajax.getFormValues('frmTotalPresupuesto'));" value="Buscar..."/></td>
                      
                      

                    </tr>
                </table>
		</form>
          <table border="0" id="tblListadoTot" width="800px">

             <tr>                   
               <form id="frmListadoTot" name="frmListadoTot" style="margin:0">
             <input type="hidden" name="hddEscogioTot" id="hddEscogioTot" style="display:none" readonly="readonly"  />

                <td id="tdListadoTot">

                    <table width="100%">
                        <tr class="tituloColumna">
                            <td>Nro. T.O.T</td>
                            <td>Fecha</td>
                            <td>Proveedor</td>
                            <td>Monto</td>
                        </tr>
                    </table>

                </td>                     
                
                   </form>

             </tr>     
       		 <tr>
                <td>
                    <hr>
                    <form id="frmDatosTot" name="frmDatosTot" style="margin:0">
                        <table border="0" width="100%">
                            <tr>
                                <td align="right" class="tituloCampo" width="13%"><span class="textoRojoNegrita">*</span>Nro T.O.T:</td>
                                <td width="39%" > 
                                    <input type="text" id="txtNumeroTot" name="txtNumeroTot" readonly="readonly"/>
                                    <input type="text" id="hddIdTot" name="hddIdTot" readonly="readonly" style="display:none"/>  
                                                                                <!-- hddIdDetTemp NO LO ESTOY UTILIZANDO -->              </td>
                                <td align="right" class="tituloCampo" width="16%" >Proveedor:</td>
                                <td width="32%" >
                                    <input type="text" id="txtProveedor" name="txtProveedor" readonly="readonly"  size="70"/></td>
                            </tr>
                            <tr>
                                  <td align="right" class="tituloCampo">Fecha:</td>
                                  <td ><label>
                                    <input type="text" name="txtFechaTot" id="txtFechaTot" readonly="readonly" />
                                  </label></td>
                                  <td class="tituloCampo" align="right">Tipo Pago:</td>
                              <td ><label>
                                <input type="text" name="txtTipoPagoTot" id="txtTipoPagoTot" readonly="readonly" />
                                <input type="hidden" name="hddIdPorcentajeTot" id="hddIdPorcentajeTot" readonly="readonly" />

                                
                              </label></td>
                            </tr>
                            <tr>
                              <td align="right" class="tituloCampo">Monto:</td>
                              <td ><label>
                                <input name="txtMonto" type="text" id="txtMonto" readonly="readonly" />
                              </label></td>
                              <td class="tituloCampo" align="right"> Porcentaje T.O.T:</td>
                              <td ><input type="text" id="txtPorcentaje" name="txtPorcentaje" readonly="readonly"/></td>
                            </tr>
                            <tr>
                              <td align="right" class="tituloCampo">Monto Total</td>
                              <td ><input type="text" name="txtMontoTotalTot" id="txtMontoTotalTot" readonly="readonly" /></td>
                              <td >&nbsp;</td>
                              <td >&nbsp;</td>
                            </tr>
                        </table>
                  </form>
                </td>
            </tr>
            <tr>
                <td align="right">
                    <hr/>
                   
                    <input type="button" onclick="validarTot();" id="btnAsignarTot" value="Aceptar"><!-- validarTot(); -->
                    <input type="button" onclick="$('divFlotante2').style.display='none'; $('divFlotante').style.display='none'; $('tblLogoGotoSystems').style.display='';" value="Cancelar">
                </td>
            </tr>
        </table>


    
    
    <table border="0" id="tblLogoGotoSystems">
    <tr>
<!--    	<td align="center"><img src="../img/logo_gotosystems.png"/></td>-->
	</tr>
    </table>
</div>
<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>

    <table border="0" width="700px" id="tblArticulosSustitutos">
    <tr>
    	<td>
        	<table cellpadding="0" cellspacing="0" width="100%">
            <tr>
            	<td align="left">
                	<table cellpadding="0" cellspacing="0">
                    <tr align="center">
                        <td class="rafktabs_title" id="tdArticulosSustitutos" width="120px">Articulos Sustitutos</td>
                        <td class="rafktabs_title" id="tdArticulosAlternos" width="120px">Articulos Alternos</td>
		            </tr>
					</table>
				</td>
            </tr>
            <tr>
				<td class="rafktabs_panel" id="tdContenidoTabs"></td>
            </tr>
            </table>
        </td>
    </tr>
	<tr>
    	<td align="right">
			<hr>
            <input type="button" onclick="validarFormArt();" value="Aceptar">
			<input type="button" onclick="$('divFlotante2').style.display='none';" value="Cancelar">
		</td>
    </tr>
    </table>
   <form id="frmConfClave" name="frmConfClave" style="margin:0">
    <input name="hddAccionObj" id="hddAccionObj" type="hidden" />

    <table width="22%" border="0" id="tblClaveDescuento" style="display:none">
      <tr align="left">
        <td width="54%" class="tituloCampo">Clave:</td>
        <td colspan="2"><label>
        <input type="password" name="txtContrasenaAcceso" id="txtContrasenaAcceso" />
        </label></td>
      </tr>
      <tr>
        <td colspan="3"><hr /></td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td width="22%"><label>
          <input type="button" name="btnAceptarClave" id="btnAceptarClave" value="Aceptar" onclick="validarFormClaveDescuento();" />
        </label></td>
        <td width="24%"><label>
          <input type="button" name="btnCancelar" id="btnCancelar" value="Cancelar" onclick="$('divFlotante2').style.display='none'; $('divFlotante').style.display='none'; $('tblLogoGotoSystems').style.display='';" />
        </label></td>
      </tr>
	</table>
    <table width="70%" border="0" id="tblPorcentajeDescuento" style="display:none" >
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td class="tituloCampo">Descuentos:</td>
        <td id="tdLstTipoDescuentos">
        <select id="lstTipoDescuentos" name="lstTipoDescuentos">
            <option>Todos...</option>
        </select>
        <script>
            xajax_cargaLstDescuentos();
        </script>
        </td>
      </tr>
      <tr align="left">
        <td class="tituloCampo">Porcentaje:</td>
        <td id="tdLstTipoDescuentos"><label>
            <input type="text" name="txtPorcDctoAdicional" id="txtPorcDctoAdicional" size="10" style="text-align:right" readonly="readonly" />
            <input type="hidden" name="txtDescripcionPorcDctoAdicional" id="txtDescripcionPorcDctoAdicional" readonly="readonly" />

        %</label></td>
      </tr>
      <tr>
        <td colspan="2"><hr /></td>
      </tr>
      
      <tr>
      <tr>
        <td style="text-align:right"><input type="button" name="btnAceptarDcto" id="btnAceptarDcto" value="Aceptar" onclick="validarFormClaveDescuentoAdicional();" /></td>
        <td style="text-align:right"><input type="button" name="btnCancelarDcto" id="btnCancelarDcto" value="Cancelar" onclick="$('divFlotante2').style.display='none'; $('divFlotante').style.display='none'; $('tblLogoGotoSystems').style.display='';"/></td>
      </tr>
    </table>
</form>
</div>
<script>
	bloquearForm();
</script>
<script language="javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
	
	var theHandle2 = document.getElementById("divFlotanteTitulo2");
	var theRoot2   = document.getElementById("divFlotante2");
	Drag.init(theHandle2, theRoot2);
</script>

<?php
function nombreIva($idIva){
        //cuando se crea no posee iva, por lo tanto deberia ser el primero id 1 itbms-iva
        if($idIva == NULL || $idIva == "0" || $idIva == "" || $idIva == " "){
            $idIva = 1;
        }    
        $query = "SELECT observacion FROM pg_iva WHERE idIva = ".$idIva."";
        $rs = mysql_query($query);
        if(!$rs){ return ("Error cargarDcto \n".mysql_error().$query."\n Linea: ".__LINE__); }

        $row = mysql_fetch_assoc($rs);

        return $row['observacion'];

}
?>