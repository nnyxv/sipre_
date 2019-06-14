<?php
require_once ("../connections/conex.php");
require_once ("client_service.php");

session_start();

if($_GET['doc_type'] == 2) {
	if($_GET['acc'] == 4) // APROBACION
		define('PAGE_PRIV','sa_aprobacion_orden');
	else
		define('PAGE_PRIV','sa_orden_servicio_list');//nuevo gregor
		//define('PAGE_PRIV','sa_orden');//anterior
}

if($_GET['doc_type'] == 1 || $_GET['doc_type'] == 4) {
	if($_GET['acc'] == 4) // APROBACION
		define('PAGE_PRIV','sa_aprobacion_presupuesto');
	else		
		define('PAGE_PRIV','sa_presupuesto_list');//nuevo gregor
		//define('PAGE_PRIV','sa_presupuesto');//anterior
}

require_once("../inc_sesion.php");
//no se puede validar por page_priv porque hay modulos que nunca se tomo en cuenta como devolucion_vale
/*
if(!(validaAcceso(PAGE_PRIV))) {
	echo "
	<script>
		alert('Acceso Denegado');
		window.location.href = 'index.php';
	</script>";
	
}
*/
require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_iv_general.php");
include("controladores/ac_sa_orden_form.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Orden de Servicio</title>
<link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    <link rel="stylesheet" type="text/css" href="css/sa_general.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragServicios.css">
    <script type="text/javascript" language="javascript" src="../js/mootools.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
    <script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/> 
    <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
        
    <style type="text/css">
	.key_pass{
		background-image:url(<?php echo getUrl('img/iconos/key.png');?>);
		background-repeat:no-repeat;
		background-position: 1% 50%;
		/*padding-right:18px;*/
		padding-left:18px;
		min-height:16px;
	}
	#informacionGeneralOrden td{ /* limpia quita el border radius de background heredado*/
		border-radius: 0px;
		border-color:gray;
	}
	
	.tituloHerramientas{
		border:0px;	
	}
        
        .noRomper{
            white-space: nowrap;
        }
	
	/*
	.contenidoHerramientas td:not(:first-child){
		padding:2px;
			
	}*/
	
	
	
	</style>
    
    <script type="text/javascript">
	function validarFormPermiso() {
		if (validarCampo('txtContrasena','t','') == true) {
			xajax_validarPermiso(xajax.getFormValues('frmPermiso'), xajax.getFormValues('frmDatosArticulo'));
		} else {
			validarCampo('txtContrasena','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function setestaticpopup(url,marco,_w,_h) {
		var x = (screen.width - _w) / 2;
		var y = (screen.height - _h) / 2;
		var r= window.open(url,marco,"toolbar=1,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=0,width="+_w+",height="+_h+",top="+y+",left="+x+"");
		r.focus();
		return r;
	}
	
	function validarPaquete() {
		if (validarCampo('hddEscogioPaquete','t','') == true) {//valida si escogio un paquete
			if(validarCampo('hddManObraAproXpaq','t','') == false && validarCampo('hddRepAproXpaq','t','') == false){//valido que almenos escogio algo
				alert("No puedes agregar paquetes vacios");
				$('btnAsignarPaquete').disabled = false;
						return false;
			}
			//if(validarCampo('hddManObraAproXpaq','t','') == true) {//valida si escogio manos de obra
			//	if(validarCampo('hddRepAproXpaq','t','') == true) {//valida si escogio repuestos
					if($('hddArtEnPaqSinPrecio').value == 1) {
						$msgAdvRpto = " Repuesto(s)";
						$sw = 1;
					} else {
						$msgAdvRpto = "";
						$sw = 0;
					}
					if($('hddTempEnPaqSinPrecio').value == 1) {
						$msgAdvTemp = " Mano(s) de Obra(s)";
						$sw2 = 1;
					} else {
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
						$('btnAsignarPaquete').disabled = false;
					} else {
						if ( $('hddArticuloSinDisponibilidad').value == 1) {
							if( confirm("El paquete elegido tiene Repuestos sin Disponibilidad. Desea agregarlo?")) {
								xajax_insertarPaquete(xajax.getFormValues('frmDatosPaquete'), xajax.getFormValues('frmTotalPresupuesto'), xajax.getFormValues('frm_agregar_paq'), xajax.getFormValues('frmPresupuesto'));
							} else {
								$('btnAsignarPaquete').disabled = false;
								return false;
							}
						} else {
							xajax_insertarPaquete(xajax.getFormValues('frmDatosPaquete'), xajax.getFormValues('frmTotalPresupuesto'), xajax.getFormValues('frm_agregar_paq'), xajax.getFormValues('frmPresupuesto'));
						}
					}
				/*} else {
					alert("Escoja los Repuestos");
					return false;
				}
			} else {
				alert("Escoja las Manos de Obra");
				return false;
			}*/
		} else {
			alert("Escoja el paquete");
			$('btnAsignarPaquete').disabled = false;
			return false;
		}
	
	}
	
	function validarNota() {
		if (validarCampo('txtDescripcionNota','t','') == true && validarCampo('txtPrecioNota','t','') == true) {
			
			xajax_insertarNota(xajax.getFormValues('frmDatosNotas'), xajax.getFormValues('frmTotalPresupuesto'));
		} else {
			validarCampo('txtDescripcionNota','t','');
			validarCampo('txtPrecioNota','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			$('btnGuardarNota').disabled = false;
			return false;
		}
	
	
	}
	
	function validarFormClaveDescuento() {

		if (validarCampo('txtContrasenaAcceso','t','') == true) {
			xajax_validarClaveDescuento(xajax.getFormValues('frmConfClave'), xajax.getFormValues('frmTotalPresupuesto'));
		} else {
			validarCampo('txtContrasenaAcceso','t','');			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarDevolucion() {
		if (validarCampo('txtMotivoRetrabajo','t','') == true) {
			if(confirm("Desea devolver Vale de Salida y generar Vale de Entrada?"))
				xajax_devolverValeSalida(xajax.getFormValues('frmPresupuesto'), xajax.getFormValues('frmTotalPresupuesto'));
			else
				return false;
		} else {
			validarCampo('txtMotivoRetrabajo','t','');
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFormClaveDescuentoAdicional() {
		if (validarCampo('lstTipoDescuentos','t','lista') == true && validarCampo('txtPorcDctoAdicional','t','monto')) {
			
			xajax_insertarDescuento(xajax.getFormValues('frm_agregar_paq'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmListaManoObra'), xajax.getFormValues('frmListaTot'), xajax.getFormValues('frmListaNota'), xajax.getFormValues('frmTotalPresupuesto'), xajax.getFormValues('frmConfClave'));
			
		} else {
			validarCampo('lstTipoDescuentos','t','lista');			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarTempario() {
		
		if ($('hddTipoDocumento').value != 1) {
			/*if($('hddMecanicoEnOrden').value == "")
			{
				alert("El Parámetro ASIGNACION DE MECANICO EN ORDEN no esta configurado.");
				xajax_cargaLstTipoOrden(-1);
				return false;
			}*/
			if ($('hddMecanicoEnOrden').value == 1) {
				//dependiendo si se muestra o no el mecanico por parametros generales coloco la validacion
				if (validarCampo('lstMecanico','t','lista') == true && validarCampo('txtCodigoTemp','t','') == true) {
					xajax_insertarTempario(xajax.getFormValues('frmDatosTempario'),	xajax.getFormValues('frmListaManoObra'), xajax.getFormValues('frmTotalPresupuesto'));	
				} else {
					validarCampo('lstMecanico','t','lista');
					alert("Los campos señalados en rojo son requeridos");
					$('btnAsignarTemp').disabled = false;
					return false;
				}
			} else {
				/*if($('hddObjTempario').value == "")
					alert("El diagnóstico se cargará automáticamente.");*/
				if (validarCampo('txtCodigoTemp','t','') == true)
					xajax_insertarTempario(xajax.getFormValues('frmDatosTempario'),	xajax.getFormValues('frmListaManoObra'), xajax.getFormValues('frmTotalPresupuesto'));	
				else {
					validarCampo('txtCodigoTemp','t','');
					alert("Los campos señalados en rojo son requeridos");
					$('btnAsignarTemp').disabled = false;
					return false;
				}
				
			}													
		} else {
			xajax_insertarTempario(xajax.getFormValues('frmDatosTempario'),	xajax.getFormValues('frmListaManoObra'), xajax.getFormValues('frmTotalPresupuesto'));
		}	
		
		
	}
	
	function validarTot() {
		if (validarCampo('txtNumeroTot','t','') == true
			&& validarCampo('numeroTotMostrar','t','') == true) {
			xajax_insertarTot(xajax.getFormValues('frmDatosTot'), xajax.getFormValues('frmListaTot'), xajax.getFormValues('frmTotalPresupuesto'));
		} else {
			validarCampo('txtNumeroTot','t','');
			validarCampo('numeroTotMostrar','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			$('btnAsignarTot').disabled = false;
			return false;
		}
	}
	
	function validarFormArt() {
		var precioNuevo = parseFloat($('txtPrecioArtRepuesto').value);
		var precioViejo = parseFloat($('hddCostoArtRepuesto').value);
		var debajoCosto = $('hddBajarPrecio').value; 
		
		
		if (debajoCosto !='debajo_costo' && precioNuevo < precioViejo && precioViejo > 0) {
			alert('El Precio del Artículo No Puede Ser Menor al Costo ' + $('hddCostoArtRepuesto').value);
			$('btnInsertarArticulo').disabled = false;
		} else {
			if (validarCampo('txtCodigoArt','t','') == true
			&& validarCampo('txtCantidadArt','t','cantidad') == true
			&& validarCampo('lstPrecioArt','t','lista') == true
			//&& validarCampo('lstIvaArt','t','listaExceptCero') == true
			) {
				if ($('txtCantDisponible').value == 0) {
					if (confirm("El articulo que desea agregar no dispone de cantidad disponible. \nDesea cargar la cantidad digitada?"))
						xajax_insertarArticulo(xajax.getFormValues('frmDatosArticulo'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalPresupuesto'));
	
				} else if (parseFloat($('txtCantDisponible').value)	< parseFloat($('txtCantidadArt').value)) {
					if (confirm("La cantidad digitada es Mayor que la disponible. \nDesea cargarla?"))
						xajax_insertarArticulo(xajax.getFormValues('frmDatosArticulo'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalPresupuesto'));
				} else
					xajax_insertarArticulo(xajax.getFormValues('frmDatosArticulo'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalPresupuesto'));
			} else {
				validarCampo('txtCodigoArt','t','');
				validarCampo('txtCantidadArt','t','cantidad');
				validarCampo('lstPrecioArt','t','lista');
				//validarCampo('lstIvaArt','t','listaExceptCero');
				
				alert("Los campos señalados en rojo son requeridos");
				$('btnInsertarArticulo').disabled = false;
				return false;
			}
		}
		
	}
	
	function validarNroControl() {
		if (validarCampo('txtNroControl','t','') == true) {
			if ($('hddItemsNoAprobados').value == 1)
				$cadena = "La Orden tiene Items No aprobados. Estos mismos no seran reflejados en la Factura.\n";
			else
				$cadena = "";
				
			if(confirm($cadena + "Desea Generar la Factura?"))
				xajax_guardarFactura(xajax.getFormValues('frmPresupuesto'), xajax.getFormValues('frmTotalPresupuesto'));
			else
				return false;
		} else {
			validarCampo('txtNroControl','t','');
			$('txtNroControl').focus();
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarMotivoRetrabajo() {
		if (validarCampo('txtMotivoRetrabajo','t','') == true
		&& validarCampo('txtIdValeRecepcion','t','') == true) {
			if(confirm("Desea Generar la Orden Tipo Retrabajo?"))
				xajax_guardarDcto(xajax.getFormValues('frmPresupuesto'),  xajax.getFormValues('frm_agregar_paq'), xajax.getFormValues('frmListaArticulo'),  xajax.getFormValues('frmListaManoObra'),  xajax.getFormValues('frmListaNota'),  xajax.getFormValues('frmListaTot'), xajax.getFormValues('frmTotalPresupuesto'));
		} else {
			validarCampo('txtMotivoRetrabajo','t','');
			validarCampo('txtIdValeRecepcion','t','');
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFormPresupuesto() {
		if ($('hddLaOrdenEsRetrabajo').value == 5) {
			$cond = "&& validarCampo('txtMotivoRetrabajo','t','') == true";
		} else {
			$cond = "";
		}
			
		if (validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtIdValeRecepcion','t','') == true
		&& validarCampo('txtDescuento','t','numPositivo') == true
		&& validarCampo('lstTipoOrden','t','lista') == true) {
			/*if ($('hddObj').value.length > 0 || $('hddObjPaquete').value.length > 0 || $('hddObjTempario').value.length > 0 || $('hddObjRepuestosPaquete').value.length > 0 || $('hddObjNota').value.length > 0 || $('hddObjTot').value.length > 0 + $cond ){*/
			if ($('hddObj').value.length <= 0 && $('hddObjPaquete').value.length <= 0 && $('hddObjTempario').value.length <= 0 && ($('hddObjNota').value.length > 0 || $('hddObjTot').value.length > 0 )) {
				//if (confirm("Desea terminar la orden sin pasar por el Magnetoplano?")) {//este era el que finalizaba la orden al guardarla
					//	xajax_guardarDcto(xajax.getFormValues('frmPresupuesto'),  xajax.getFormValues('frm_agregar_paq'), xajax.getFormValues('frmListaArticulo'),  xajax.getFormValues('frmListaManoObra'),  xajax.getFormValues('frmListaNota'),  xajax.getFormValues('frmListaTot'), xajax.getFormValues('frmTotalPresupuesto'), 0);//antes 1 y finalizaba la orden
				//} else {
					if(confirm("Desea Guardar la Orden de Servicio?")) {//cuando no hay repuestos pero si lo demas, notas, tot
                                            //ojo aqui siempre estuvo comentado, se usa abajo tambien
                                                /*var filtroOrden = xajax.call('buscarFiltroOrden', {mode:'synchronous', parameters:[$('lstTipoOrden').value]});                                           
						if(filtroOrden == 4){ // ANTES $('lstTipoOrden').value == 4  donde 4 es activos
							if(confirm("Desea guardar los montos de la orden en 0?")){
								$('hddSeleccionActivo').value= 1;
							}
						}*/
						xajax_guardarDcto(xajax.getFormValues('frmPresupuesto'),  xajax.getFormValues('frm_agregar_paq'), xajax.getFormValues('frmListaArticulo'),  xajax.getFormValues('frmListaManoObra'),  xajax.getFormValues('frmListaNota'),  xajax.getFormValues('frmListaTot'), xajax.getFormValues('frmTotalPresupuesto'), 0);
					} else {
						return false;
					}
				//}
			} else {
				if (confirm("Desea Guardar la Orden de Servicio?")) {
                    //quitado carlos ultimo                    
                    /*var filtroOrden = xajax.call('buscarFiltroOrden', {mode:'synchronous', parameters:[$('lstTipoOrden').value]});                                           
										if (isNaN(parseInt(filtroOrden))){//valido que no hubo error
											return false;	
										}
                                        if(filtroOrden == 4){ // ANTES $('lstTipoOrden').value == 4  donde 4 es activos
                                                if(confirm("Desea guardar los montos de la orden en 0?")){
                                                        $('hddSeleccionActivo').value= 1;
                                                }
                                        }*/
					xajax_guardarDcto(xajax.getFormValues('frmPresupuesto'),  xajax.getFormValues('frm_agregar_paq'), xajax.getFormValues('frmListaArticulo'),  xajax.getFormValues('frmListaManoObra'),  xajax.getFormValues('frmListaNota'),  xajax.getFormValues('frmListaTot'), xajax.getFormValues('frmTotalPresupuesto'), 0);
				} else {
					return false;
				}
			}
				
			/*}
			else {
				alert("Debe agregar Items al Documento");
				return false;
			}*/
		} else {
			if ($('hddLaOrdenEsRetrabajo').value == 5) {
				validarCampo('txtMotivoRetrabajo','t','');
				validarCampo('txtIdValeRecepcion','t','');
			} else {
				validarCampo('txtIdEmpresa','t','');
				validarCampo('txtIdValeRecepcion','t','');
				validarCampo('txtDescuento','t','numPositivo');
				validarCampo('lstTipoOrden','t','lista');
			}
			
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
		$('btnEliminarArt').disabled = true;
		
		if($('hddAccionTipoDocumento').value != 1) {
			$('btnEliminarTot').readOnly = true;
			$('btnEliminarNota').readOnly = true;
			$('btnEliminarTemp').readOnly = true;
			$('btnEliminarArt').readOnly = true;
		}
			
		$('btnInsertarTot').disabled = true;
		$('btnEliminarTemp').disabled = true;
		$('btnInsertarNota').disabled = true;	
		$('btnEliminarNota').disabled = true;	
		
		if($('hddTipoDocumento').value == 3 || $('hddTipoDocumento').value == 4) {
			$('btnGuardar').disabled = false;
		} else {
			$('btnGuardar').disabled = true;
		}
	}

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
		
		if($('hddAccionTipoDocumento').value != 1) {
			$('btnEliminarTot').readOnly = true;
			$('btnEliminarNota').readOnly = true;
			$('btnEliminarTemp').readOnly = true;
			$('btnEliminarArt').readOnly = true;
		}	
			
		$('btnEliminarTemp').disabled = false;
		$('btnInsertarNota').disabled = false;	
		$('btnEliminarNota').disabled = false;	
		$('btnGuardar').disabled = false;
		$('btnCancelar').disabled = false;
	}
	
	function abrir2() {
		$('key2').value= "";
		$('key_window2').style.visibility = 'visible';
		$('key_window2').style.display = '';
		centrarDiv($('key_window2'));
		$('key_title2').innerHTML= "Introduzca La Clave";
		
		$('key2').focus();
	}
	
	function verificar(pass) {
		xajax_verficarPass(pass);
	}
	
	function verificarTipo(pass) {
		xajax_verficarPassTipoOrden(pass);
	}
        
        
        function letras(e) {
            tecla = (document.all) ? e.keyCode : e.which;
            if (tecla == 0 || tecla == 8)
                return true;
            patron = /[A-Za-z\s ]/;
            te = String.fromCharCode(tecla);
            return patron.test(te);
        }

        function numeros(e) {
            tecla = (document.all) ? e.keyCode : e.which;
            if (tecla == 0 || tecla == 8)
                return true;
            patron = /[0-9]/;
            te = String.fromCharCode(tecla);
            return patron.test(te);
        }
        
        function numerosPunto(e){
            tecla = (document.all) ? e.keyCode : e.which;
            if (tecla == 0 || tecla == 8)
                return true;
            patron = /[0-9.]/;
            te = String.fromCharCode(tecla);
            return patron.test(te);
        }
        
        function numerosLetras(e){
            tecla = (document.all) ? e.keyCode : e.which;
            if (tecla == 0 || tecla == 8){//8 = delete
                return true;
            }else if(tecla == 13){
                return false;
            }else{
                patron = /[0-9A-Za-zÑñ\s ]/;
                te = String.fromCharCode(tecla);
                return patron.test(te);
            }
        }
        
        function sinEnterTab(e){
            tecla = (document.all) ? e.keyCode : e.which;
            if (tecla == 13 || tecla == 9){//13 = enter 9 = tab
                return false;
            }
            
        }
        
        function cambiarPrecioTot(valor){// puede ser 1x20.00 con accesorios o MANUALx o ORDENx30.00
            valores = valor.split('x');
            idRef = valores[0];// id numero del accesorio o MANUAL o ORDEN
            porcentaje = valores[1];
            
            if(idRef === "MANUAL"){
                if ($('hddAccionTipoDocumento').value != 2) {
                    if ($('txtPorcentaje').readOnly == true) {
                         xajax_formValidarPermisoEdicion('sa_porcentaje_tot');
                    }
                }
            }else{
                
                if(idRef === "ORDEN"){
                    $('idPrecioTotAccesorio').value = '';//si es orden enviar en vacio
                }else{
                    $('idPrecioTotAccesorio').value = idRef;//si es acc enviar accesorio
                }
                
                $('txtPorcentaje').value = porcentaje;
                $('txtPorcentaje').readOnly = true;                
                calcularTot();
            }
        }
        
        function calcularTot(){
            txtPorcentaje = parseFloat(($('txtPorcentaje').value).replace(",", ""));
            txtMonto = parseFloat(($('txtMonto').value).replace(",", ""));
                        
            txtMontoTotalTot = txtMonto + (txtPorcentaje*txtMonto/100);
            
            $('txtMontoTotalTot').value = format1(txtMontoTotalTot);
        }
        
        //number format en javascript pero redondea
        function format1(n) {
            return n.toFixed(2).replace(/./g, function(c, i, a) {
                return i > 0 && c !== "." && (a.length - i) % 3 === 0 ? "," + c : c;
            });
        }
        
        //verifica si el tipo de orden es accesorios o blindaje
        function tipoOrdenAccesorios(NroItem){        
        var bloqueado = xajax.call('buscarFiltroOrden', {mode:'synchronous', parameters:[$('lstTipoOrden').value, NroItem]});
            if(bloqueado == 1){
                return true;
            }            
        }
        
        function abrirCalculadoraDescuentos(){
            $('divFlotante3').style.display = '';
            centrarDiv($('divFlotante3'));
        }
        
        function seleccionModoDescuento(modo){//"" seleccione 1 repuestos 2 servicios 3 individual
            if(modo === "1" || modo === "2"){   
                $('porcentajeCalculadora').disabled = false;
                $('cantidadCalculadora').disabled = false;
                $('nuevoPorcentajeCalculadora').disabled = false;
                $('radioPorcentaje').disabled = false;
                $('radioMonto').disabled = false;
                
                $('notaDescuento').innerHTML = "Nota: se actualizar&aacute; con cada cambio";
                
            }else{
                $('porcentajeCalculadora').disabled = true;
                $('cantidadCalculadora').disabled = true;
                $('nuevoPorcentajeCalculadora').disabled = true;
                $('radioPorcentaje').disabled = true;
                $('radioMonto').disabled = true;
                
                $('porcentajeCalculadora').value = '';
                $('cantidadCalculadora').value = '';
                $('nuevoPorcentajeCalculadora').value = '';
                $('notaDescuento').innerHTML = "";
            }
        }
        
        function eliminacionPaqueteIndividual(nroPaquete,tieneSolicitud){//activa botones de eliminacion
            $('numeroPaqueteEditar').value = nroPaquete;//nro paquete es el orden de los paquetes que se muestran en la orden
            
            if(tieneSolicitud == "1"){//no permitir
                $('eliminacionRepuestoPaquete').value = 0;                
            }else{
                $('eliminacionRepuestoPaquete').value = 1;
            }
            
        }
        
        function eliminarTemparioIndividual(idTempario, elementoImg, precioTotalItem,idPaquete,idDetOrdenTempario){//elimina el tempario del listado dentro de paquete
            nroPaquete = $('numeroPaqueteEditar').value;//para saber a cual nro (NO ID) mostrado en listado pertenece
                        
            if(nroPaquete != "0" && nroPaquete != null){
                               
                //editamos los temparios asignados
                strTemparios = $('hddTempPaqAsig'+nroPaquete).value;
                arrayTemparios = strTemparios.split("|");                
                index = arrayTemparios.indexOf(idTempario.toString());
                
                if (index > -1) {//si encuentra ejecuta, sino ya habia sido eliminado antes
                    arrayTemparios.splice(index, 1);
                    
                    var precioPaquete = parseFloat($('hddPrecPaq'+nroPaquete).value); //precio paquete, subtotal
                    var precioTempario = parseFloat($('hddTotalTempPqte'+nroPaquete).value); //precio total tempario, base imponible

                    $('hddPrecPaq'+nroPaquete).value = precioPaquete - precioTotalItem;
                    $('hddTotalTempPqte'+nroPaquete).value = precioTempario - precioTotalItem;
                    
                    strNuevo = arrayTemparios.join("|"); 
                    if (strNuevo == ""){ 
                        strNuevo = "|";
                    }
                    $('hddTempPaqAsig'+nroPaquete).value = strNuevo;

                    //editamos los temparios modificados edit
                    $('hddTempPaqAsigEdit'+idPaquete).value = strNuevo;
                    
                    $('temparioPaqueteEliminar').value = $('temparioPaqueteEliminar').value+"|"+idDetOrdenTempario;//para eliminar manual al guardar
                    
                    elementoImg.getParent().getParent().remove();
                    xajax_calcularDcto(xajax.getFormValues('frmPresupuesto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalPresupuesto'), xajax.getFormValues('frm_agregar_paq'), xajax.getFormValues('frmListaManoObra'), xajax.getFormValues('frmListaNota'), xajax.getFormValues('frmListaTot'),'false');
                    
                    alert("Eliminado correctamente, al guardar la orden se actualiza");
                }else{
                    alert("Este item ya ha sido eliminado");
                }
                
            }
        }
        
        function eliminarRepuestoIndividual(idRepuesto){//elimina el repuesto del listado dentro de paquete
            nroPaquete = $('numeroPaqueteEditar').value;//para saber a cual nro (NO ID) mostrado en listado pertenece
            permitirEliminacion = $('eliminacionRepuestoPaquete').value;
            
            if(permitirEliminacion == "1" && nroPaquete != "0" && nroPaquete != null){
                strRepuestos = $('hddRepPaqAsig'+nroPaquete).value;
                arrayRepuestos = strRepuestos.split("|");
                
                index = arrayRepuestos.indexOf(idRepuesto.toString());
                if (index > -1) {
                    arrayRepuestos.splice(index, 1);
                }
                
                strNuevo = arrayRepuestos.join("|"); 
                if (strNuevo == ""){ 
                    strNuevo = "|";
                }
                $('hddRepPaqAsig'+nroPaquete).value = strNuevo;
            }else{
                alert("No se puede eliminar si ya tiene una solicitud de repuestos");
            }
            
        }
        
        /**
         * Se encarga de limpiar el fomulario y seccion de paquete,
         * al abrir un paquete se tarda y muestra el paquete anteriormente abierto
         * @returns {void}
         */
        function limpiarPaquetes(){
            document.getElementById("frmDatosPaquete").reset();
            $('tdListadoTempario').innerHTML = "";
            $('tdListadoRepuestos').innerHTML = "";
            
            $('txtCodigoPaquete').value = "";
            $('txtDescripcionPaquete').value = "";
            $('hddEscogioPaquete').value = "";
            $('hddRepAproXpaq').value = "";
            $('hddArticuloSinDisponibilidad').value = "";
            $('hddArtEnPaqSinPrecio').value = "";
            $('hddTempEnPaqSinPrecio').value = "";
            $('hddArtNoDispPaquete').value = "";
            $('hddObjRepuestoPaq').value = "";
            $('hddObjTemparioPaq').value = "";
            $('numeroPaqueteEditar').value = "";
            $('eliminacionRepuestoPaquete').value = "";
            $('hddManObraAproXpaq').value = "";
            
            $('hddTotalArtExento').value = "";
            $('hddTotalArtConIva').value = "";
            $('idIvasRepuestosPaquete').value = "";
            $('montoIvasRepuestosPaquete').value = "";
            $('porcentajesIvasRepuestosPaquete').value = "";
            $('txtNumeroSolicitud').value = "";
            
        }
        
	</script>
</head>

<body class="bodyVehiculos" onload="
xajax_validarTipoDocumento('<?php echo $_GET['doc_type']; ?>','<?php echo $_GET['id']; ?>','<?php echo $_GET['ide']; ?>','<?php echo $_GET['acc']; ?>', xajax.getFormValues('frmTotalPresupuesto')); 
//if($('hddAccionTipoDocumento').value != 1) 
	//xajax_visualizarMecanicoEnOrden();
    ">
<div id="">
	<?php if($_GET['sinmenu'] !="1"){ include("banner_servicios.php"); } ?>
    
    <div id="divGeneralVehiculos" class="print">
    	<table width="100%" border="0" cellpadding="0" cellspacing="0">
    	<tr>
        	<td id="tdTituloPaginaServicios" class="tituloPaginaServicios"></td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr style="">
        	<td align="right">
            	<table align="left" class="noprint"  >
                <tr>
                    <td>
                     <?php 
					 
					 if($_GET["doc_type"] == 2){ 
					 	if($_GET["id"] != ""){
					 ?>
                     
                    <button style="cursor:default; float:left;" class="noprint" onclick="window.print();" type="button"><table cellspacing="0" cellpadding="0" align="center"><tbody><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_print.png"></td><td>&nbsp;</td><td>Imprimir</td></tr></tbody></table></button>
                    
                 	<button style="cursor:default; float:left;" class="noprint" onclick="verVentana('sa_imprimir_presupuesto_pdf.php?valBusq=<?php echo $_GET['id']; ?>|<?php echo $_GET['doc_type']; ?>', 950, 600);" type="button"><table cellspacing="0" cellpadding="0" align="center"><tbody><tr><td>&nbsp;</td><td><img src="../img/iconos/pdf_ico.png"></td><td>&nbsp;</td><td>Exportar</td></tr></tbody></table></button>
                    
                    <?php } } ?>
                    	<!-- <input type="button" name="btnImprimirDoc" id="btnImprimirDoc" value="Imprimir" onclick="verVentana('sa_imprimir_presupuesto_pdf.php?valBusq=<?php echo $_GET['id']; ?>|<?php echo $_GET['doc_type']; ?>', 950, 600);" style="display:none"/>  -->     
                                     </td>
                    <td>
                    <form id="frmSolicitudRpto" name="frmSolicitudRpto" method="post" action="sa_solicitud_repuestos_form.php" style="margin:0" >
                        <input type="hidden" name="hddIdOrden" id="hddIdOrden" value=""/>
                        <input type="hidden" name="hddIdSolicitudRpto" id="hddIdSolicitudRpto" value=""/>
                    </form>
                    </td>
                    <td width="90" style="display:none"><input type="button" id="btnNuevo" name="btnNuevo" onclick="xajax_nuevoDcto();" value="Nuevo"></td>
                    <td width="206" style="display:none"><input type="button" id="btnPendiente" name="btnPendiente" onclick="xajax_cargaLstEmpresaBusq(); xajax_validarTipoDocumento(<?php echo $_GET['doc_type'];?>,<?php echo $_GET['acc']; ?>, xajax.getFormValues('frmTotalPresupuesto')); xajax_listadoDctos(0,'','','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');" value="Presupuestos Pendientes"></td>
                </tr>
          </table>
          </td>
        </tr>
        <tr>
        	<td align="left">
            <form id="frmPresupuesto" name="frmPresupuesto" style="margin:0">
                <input type="hidden" name="hddTipoOrdenAnt" id="hddTipoOrdenAnt" readonly="readonly" value="-1"/>
            	<table border="0" width="100%">
                <tr align="left" style="display:none">
                    <td align="right" class="tituloCampo" width="17%" ><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td width="47%">
                        <table cellpadding="0" cellspacing="0"  >
                        <tr>
                            <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="8" value="<?php echo $_GET["ide"]; //echo $_SESSION['idEmpresaUsuarioSysGts']; ?>"></td>
                            <td><button type="button" id="btnInsertarEmp" name="btnInsertarEmp" onclick="xajax_listadoEmpresas(0,'','','');" title="Seleccionar Empresa"><img src="../img/iconos/ico_pregunta.gif" alt="seleccionar empresa"></button></td>
                            <td>&nbsp;<input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="50"></td>
                        </tr>
                        </table>
					</td>
					<td colspan="2" rowspan="2" align="left"></td>
				</tr>
                <tr align="left" style="display:none">
                	<td align="right" class="tituloCampo">Empleado:</td>
                    <td><input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="25"></td>
                </tr>
                <tr>
                    <td colspan="4" valign="top">
                    <fieldset>
                    	<legend id="lydTipoDocumento"></legend>
                        
                        <table border="0" width="100%">
                        <tr>
                            <td valign="top" width="75%">
                                <table border="0" id="fldPresupuesto" style="display:none" width="100%">
                                <tr>
                                	<td width="12%"></td>
                                	<td width="88%"></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" id="tdLabelNroOrdenRetrabajo" style="display:none">Nro Orden Retrabajo:</td>
                                    <td id="tdTxtNroOrdenRetrabajo" style="display:none">
                                    	<input type="text" id="txtNumeroOrdenRetrabajo" name="txtNumeroOrdenRetrabajo" readonly="readonly" size="8"/>
                                        <input type="text" id="txtTipoOrdenRetrabajo" name="txtTipoOrdenRetrabajo" readonly="readonly" size="25"/>
									</td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" id="tdNroPresupuesto" style="display:none"></td>
                                    <td id="tdTxtNroPresupuesto" style="display:none"><input type="text" id="txtNroPresupuesto" name="txtNroPresupuesto" readonly="readonly" size="25"></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" id="tdIdDocumento"></td>
                                    <!-- SE USA EN ORDENES Y EN PRESUPUESTO -->
                                    <td><input type="hidden" id="txtIdPresupuesto" name="txtIdPresupuesto" readonly="readonly" size="20" style="text-align:right"> 
                                    <!-- agregado numeracion a mostrar gregor -->
                                    <input type="text" id="numeroOrdenPresupuestoMostrar" readonly="readonly"  size="20" style="text-align:right"  />  </td>
                            
                                </tr>
                                <tr style="display:none" align="left" id="trTipoClave">
                                    <td align="right" class="tituloCampo" id="tdTipoMov" style="display:none">Tipo:</td>
                                    <td style="display:none" id="tdLstTipoClave">
										<?php 
                                        if (isset($_GET['dev'])) {
                                            if ($_GET['dev'] == 1 || $_GET['dev'] == 0) {
                                                $valorSelectEntrada = "selected='selected'";
                                                $valorSelectSalida = "";
                                            } else {
                                                $valorSelectEntrada = "";
                                                $valorSelectSalida = "selected='selected'";
                                            }
                                        } ?>
                                        <select id="lstTipoClave" name="lstTipoClave" onchange="xajax_cargaLstClaveMovimiento(this.value)" >
                                            <option value="-1">[ Seleccione ]</option>
                                            <option value="2" <?php echo $valorSelectEntrada; ?>>Entrada</option>
                                            <option value="3"  <?php echo $valorSelectSalida; ?>>Venta</option>
                                            <option value="4">Salida</option>
                                        </select>
                                    </td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                </tr>
                                    <tr style="display:none;" align="left" id="trClaveMov">
                                    <td align="right" class="tituloCampo" id="tdClave" style="display:none"><span class="textoRojoNegrita">*</span>Clave:</td>
                                    <td colspan="3" id="tdlstClaveMovimiento" style="display:none">
                                        <select id="lstClaveMovimiento" name="lstClaveMovimiento">
                                        <!-- option value="-1">[ Seleccione ]</option>-->
                                        </select>
                                    </td>
                                </tr>
                                <tr style="display:none;" align="left">
                                    <td align="right" class="tituloCampo" id="tdNroControl" style="display:none">Nro Control:</td>
                                    <td colspan="3" id="tdTxtNroControl" style="display:none"><input type="text" id="txtNroControl" name="txtNroControl" size="25"></td>
                                </tr>
                                <tr align="left" id="tdFechaVecDoc">
                                    <td align="right" class="tituloCampo">Fecha Venc.:</td>
                                    <td>
                                    <div style="float:left">
                                        <input type="text" id="txtFechaVencimientoPresupuesto" name="txtFechaVencimientoPresupuesto" readonly="readonly" size="20"/>
                                    </div>
                                    <div style="float:left">
                                        <img src="../img/iconos/ico_date.png" alt="imagen" id="imgFechaVencimientoPresupuesto" name="imgFechaVencimientoPresupuesto" class="puntero noprint"/>
                                        <script type="text/javascript">
                                        Calendar.setup({
                                        inputField : "txtFechaVencimientoPresupuesto",
                                        ifFormat : "%d-%m-%Y",
                                        button : "imgFechaVencimientoPresupuesto"
                                        });
                                        </script>
                                    </div>
                                    </td>
                                </tr>
                                <tr style="display:none">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nº Presu:</td>
                                    <td><input type="text" id="txtNumeroPresupuestoPropio" name="txtNumeroPresupuestoPropio" size="25"></td>
                                </tr>
                                <tr style="display:none">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Moneda:</td>
                                    <td id="tdlstMoneda">
                                        <select id="lstMoneda" name="lstMoneda">
                                            <option value="-1">[ Seleccione ]</option>
                                        </select>
									</td>
                                </tr>
                                <tr style="display:none">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nº Refer:</td>
                                    <td><input type="text" id="txtNumeroReferencia" name="txtNumeroReferencia" size="25"></td>
                                </tr>
                                </table>
							</td>
                            <td width="25%">
                            	<table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="38%">Fecha:</td>
                                    <td width="62%"><input type="text" id="txtFechaPresupuesto" name="txtFechaPresupuesto" readonly="readonly" size="15" style="text-align:center"></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" rowspan="2">Tipo Orden:</td>
                                    <td id="tdlstTipoOrden" >
                                        <select id="lstTipoOrden" name="lstTipoOrden">
                                            <option value="-1">[ Seleccione ]</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td id="tdDescripcionTipoOrden" style="display:none"><input type="text" name="txtDescripcionTipoOrden" id="txtDescripcionTipoOrden" readonly="readonly"></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Estado:</td>
                                    <td><input name="txtEstadoOrden" id="txtEstadoOrden" type="text" readonly="readonly"></td>
                                </tr>
								</table>
							</td>
                        </tr>
                        </table>
					</fieldset>
                    </td>
                </tr>
                <tr>
					<td colspan="2" valign="top">
                    <fieldset>
						<legend>Vale de Recepción</legend>
                        
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo">Nro Vale:</td>
                            <td>
                            	<table border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><!-- nuevo gregor -->
                                    <input type="text" id="numeracionValeRecepcionMostrar" name="numeracionValeRecepcionMostrar" readonly="readonly" size="8" style="text-align:right">
                                    <input type="hidden" id="txtIdValeRecepcion" name="txtIdValeRecepcion" readonly="readonly" size="8" style="text-align:right"></td>
                                    <td class="noprint"><button type="button" id="btnInsertarCliente" name="btnInsertarCliente" onclick="xajax_buscarValeRecepcion(xajax.getFormValues('frmPresupuesto'),xajax.getFormValues('frmBuscarVale'));" title="Seleccionar Vale de Recepcion"><img src="../img/iconos/ico_pregunta.gif" alt="imagen"></button></td>
                                </tr>
                                </table>
                            </td>
                            <td align="right" class="tituloCampo">Fecha Vale:</td>
                            <td><input type="text" id="txtFechaRecepcion" name="txtFechaRecepcion" readonly="readonly" size="15" style="text-align:center"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cliente:</td>
                            <td colspan="3">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdCliente" name="txtIdCliente" readonly="readonly" size="8" style="text-align:right"></td>
                                    <td>&nbsp;</td>
                                    <td><input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="45"></td>
                                </tr>
                                </table>
                        	</td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Dirección:</td>
                            <td colspan="4">
                                <textarea cols="55" id="txtDireccionCliente" name="txtDireccionCliente" readonly="readonly" rows="3"></textarea>
                                <input type="hidden" id="hddIdEmpleado" name="hddIdEmpleado" readonly="readonly"/>
                                <input type="hidden" id="hddAgregarOrdenFacturada" name="hddAgregarOrdenFacturada" readonly="readonly"/>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="21%">Teléfono:</td>
                            <td width="29%"><input type="text" id="txtTelefonosCliente" name="txtTelefonosCliente" readonly="readonly" size="22"></td>
                            <td align="right" width="21%" class="tituloCampo"><?php echo $spanCI."/".$spanRIF?>:</td>
                            <td width="29%"><input type="text" id="txtRifCliente" name="txtRifCliente" readonly="readonly" size="16"></td>
                        </tr>
                        </table>
					</fieldset>
					</td>
                    <td colspan="2" valign="top">
                    <fieldset>
                        <legend>Datos del Vehículo</legend>
                        
                        <table width="100%" border="0">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="21%">Placa:</td>
                            <td width="29%"><input type="text" id="txtPlacaVehiculo" name="txtPlacaVehiculo" readonly="readonly" size="18" style="text-align:center"></td>
                            <td align="right" class="tituloCampo" width="21%">A&ntilde;o:</td>
                            <td width="29%"><input type="text" id="txtAnoVehiculo" name="txtAnoVehiculo" readonly="readonly" size="15" style="text-align:center"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Chasis:</td>
                            <td><input type="text" id="txtChasisVehiculo" name="txtChasisVehiculo" readonly="readonly" size="18" style="text-align:center"></td>
                            <td align="right" class="tituloCampo">Color:</td>
                            <td><input type="text" id="txtColorVehiculo" name="txtColorVehiculo" readonly="readonly" size="18"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Marca:</td>
                            <td><input type="text" id="txtMarcaVehiculo" name="txtMarcaVehiculo" readonly="readonly" size="18"></td>
                            <td align="right" class="tituloCampo">Fecha Venta:</td>
                            <td><input type="text" name="txtFechaVentaVehiculo" id="txtFechaVentaVehiculo" readonly="readonly" size="15" style="text-align:center"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Modelo:</td>
                            <td>
                            	<input type="hidden" id="hddIdModelo" name="hddIdModelo"/>
                            	<input type="text" id="txtModeloVehiculo" name="txtModeloVehiculo" readonly="readonly" size="18"/>
							</td>
                            <td align="right" class="tituloCampo"><?php echo $spanKilometraje; ?>:</td>
                            <td><input type="text" name="txtKilometrajeVehiculo" id="txtKilometrajeVehiculo" readonly="readonly" size="18"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Unidad Básica:</td>
                            <td>
                                <input type="hidden" name="hddIdUnidadBasica" id="hddIdUnidadBasica"/>
                                <input type="text" name="txtUnidadBasica" id="txtUnidadBasica" readonly="readonly" size="18"/>
							</td>
                        </tr>
                        </table>
					</fieldset>
					</td>
                </tr>
                </table>
			</form>
			</td>
        </tr>
        
        <!-- INFORMACION FACTURA PERSONAS HORAS gregor -->
        <?php if($_GET["doc_type"] == 2){ ?>
        <tr id="informacionGeneralOrden">
        	<td align="center"> 
             
            	<?php
					$idOrdenInformacion = $_GET["id"];
					
					$ocultarInformacion = "<img class=\"noprint puntero\" title=\"Ocultar informaci&oacute;n general de la orden\" style=\"float:left;\"  src=\"../img/iconos/ico_view.png\" onclick=\"$('informacionGeneralOrden').style.display = 'none';\">";
				
					$sqlInformacionOrden = sprintf("SELECT 
					CONCAT_WS(' ', pg_empleado.apellido, pg_empleado.nombre_empleado) as nombre_creador, 
 					sa_orden.id_empleado, 
					IF(sa_orden.id_empleado_aprobacion_factura > 0,(SELECT CONCAT_WS(' ', pg_empleado.apellido, pg_empleado.nombre_empleado) FROM pg_empleado WHERE id_empleado = sa_orden.id_empleado_aprobacion_factura LIMIT 1), NULL) as nombre_aprobacion, 
					sa_orden.tiempo_orden, sa_orden.fecha_factura FROM sa_orden 
					LEFT JOIN pg_empleado ON sa_orden.id_empleado = pg_empleado.id_empleado					
					WHERE id_orden = %s LIMIT 1",
					valTpDato($idOrdenInformacion,"int"));
					$queryInformacionOrden = mysql_query($sqlInformacionOrden);
					if(!$queryInformacionOrden) { echo "no se pudo obtener la informacion de control de la orden \n".mysql_error()."\n 
					Linea:".__LINE__; }
					$rowOrdenInformacion = mysql_fetch_array($queryInformacionOrden);
					
					echo "<table class=\"tabla\" width=\"100%\" cellpadding=\"2\" border=\"1\" >";
						echo "<tr class=\"tituloColumna\" align=\"center\"><td colspan=\"4\">".$ocultarInformacion." INFORMACI&Oacute;N ORDEN DE SERVICIO</td></tr>";
						echo "<tr class=\"tituloColumna\" align=\"center\">";
							echo "<td>Empleado Creador Orden</td>";
							echo "<td>Fecha Creaci&oacute;n Orden</td>";
							echo "<td>Empleado Aprobaci&oacute;n Orden</td>";
							echo "<td>Fecha Factura Orden</td>";
						echo "</tr>";
						
						echo "<tr align=\"center\">";
							echo "<td>".$rowOrdenInformacion['nombre_creador']."</td>";
							echo "<td>".fechaComun($rowOrdenInformacion['tiempo_orden'])."</td>";
							echo "<td>".$rowOrdenInformacion['nombre_aprobacion']."</td>";
							echo "<td>".fechaComun($rowOrdenInformacion['fecha_factura'],"solo fecha")."</td>";
						echo "</tr>";
						
					echo "</table>";
					/*----------------FECHAS PARA MOSTRAR O OCULTAR EL BOTON DE RECONVERSION--*/

					$dateTime_fechaOrden = strtotime($rowOrdenInformacion['tiempo_orden']);
					$dateTime_Formato_fechaOrden = date('Y-F-d',$dateTime_fechaOrden);

					// $dateTime_fechaReconversion = strtotime('06/04/2018');
					$dateTime_fechaReconversion = strtotime('08/20/2018');
					$dateTime_Formato_fechaReconversion = date('Y-F-d',$dateTime_fechaReconversion);
											
					$sqlOrdenFactura = sprintf("SELECT numeroFactura, fechaRegistroFactura, fechaVencimientoFactura, anulada, aplicaLibros,
					IF(id_empleado_creador > 0,(SELECT CONCAT_WS(' ', pg_empleado.apellido, pg_empleado.nombre_empleado) FROM pg_empleado WHERE id_empleado = idVendedor LIMIT 1), NULL) as nombre_factura, id_empleado_creador,
					IF(id_empleado_anulacion > 0,(SELECT CONCAT_WS(' ', pg_empleado.apellido, pg_empleado.nombre_empleado) FROM pg_empleado WHERE id_empleado = idVendedor LIMIT 1), NULL) as nombre_anulacion, id_empleado_anulacion,
					fecha_anulacion
					 FROM
					cj_cc_encabezadofactura WHERE numeroPedido = %s AND idDepartamentoOrigenFactura = 1 LIMIT 1",
					valTpDato($idOrdenInformacion,"int"));
					$queryOrdenFactura = mysql_query($sqlOrdenFactura);
					if(!$queryOrdenFactura) { echo "no se pudo obtener informacion de factura \n". mysql_error()."\n Linea:".__LINE__; }
					
					while($row = mysql_fetch_assoc($queryOrdenFactura)){
						
						echo "<br><table class=\"tabla\" width=\"100%\" cellpadding=\"2\" border=\"1\" >";
							echo "<tr class=\"tituloColumna\" align=\"center\"><td colspan=\"10\"> INFORMACI&Oacute;N DE FACTURA</td></tr>";
							echo "<tr class=\"tituloColumna\" align=\"center\">";
								echo "<td>N&uacute;mero de factura</td>";
								echo "<td>Fecha registro factura</td>";
								echo "<td>Fecha vencimiento factura</td>";
								echo "<td>Empleado creador factura</td>";	
								echo "<td>Anulada</td>";
								echo "<td>Aplica libros</td>";	
								echo "<td>Empleado anulaci&oacute;n factura</td>";	
								echo "<td>Fecha anulaci&oacute;n factura</td>";													
							echo "</tr>";
							
							$aplicaLibros = "";
							if($row['aplicaLibros'] == 1){
								$aplicaLibros = "SI";
							}else if($row['aplicaLibros'] == 0){
								$aplicaLibros = "NO";
							}
							
							echo "<tr align=\"center\">";
								echo "<td>".$row['numeroFactura']."</td>";
								echo "<td>".fechaComun($row['fechaRegistroFactura'],"solo fecha")."</td>";
								echo "<td>".fechaComun($row['fechaVencimientoFactura'],"solo fecha")."</td>";
								echo "<td>".$row['nombre_factura']."</td>";
								echo "<td>".$row['anulada']."</td>";
								echo "<td>".$aplicaLibros."</td>";	
								echo "<td>".$row['nombre_anulacion']."</td>";
								echo "<td>".fechaComun($row['fecha_anulacion'])."</td>";							
							echo "</tr>";
							
						echo "</table>";					
					}
					
					
					$sqlValeSalida = sprintf("SELECT numero_vale, fecha_vale, estado_vale,  
									IF(id_empleado > 0,(SELECT CONCAT_WS(' ', pg_empleado.apellido, pg_empleado.nombre_empleado) FROM pg_empleado WHERE id_empleado = sa_vale_salida.id_empleado LIMIT 1), NULL) as nombre_vale, id_empleado,
									IF(id_empleado_devolucion > 0,(SELECT CONCAT_WS(' ', pg_empleado.apellido, pg_empleado.nombre_empleado) FROM pg_empleado WHERE id_empleado = sa_vale_salida.id_empleado_devolucion LIMIT 1), NULL) as nombre_devolucion, id_empleado_devolucion,
									fecha_devolucion								
									FROM sa_vale_salida
									WHERE id_orden = %s LIMIT 1",
									valTpDato($idOrdenInformacion,"int"));
									
					$queryValeSalida = mysql_query($sqlValeSalida);
					if(!$queryValeSalida) { echo "no se pudo obtener informacion de vale de salida \n". mysql_error()."\n Linea:".__LINE__; }
					
					while($row = mysql_fetch_assoc($queryValeSalida)){
						
						echo "<br><table class=\"tabla\" width=\"100%\" cellpadding=\"2\" border=\"1\" >";
						echo "<tr class=\"tituloColumna\" align=\"center\"><td colspan=\"10\"> INFORMACI&Oacute;N DE VALE DE SALIDA</td></tr>";
							echo "<tr class=\"tituloColumna\" align=\"center\">";
								echo "<td>N&uacute;mero de vale de salida</td>";
								echo "<td>Fecha vale de salida</td>";
								echo "<td>Empleado Creador</td>";
								echo "<td>Estado vale de salida</td>";
								echo "<td>Empleado Devoluci&oacute;n</td>";	
								echo "<td>Fecha Devoluci&oacute;n</td>";								
							echo "</tr>";
							
							$estadoVale = "";
							if($row['estado_vale'] == 1){
								$estadoVale = "DEVUELTO";
							}else if($row['estado_vale'] == 0){
								$estadoVale = "GENERADO";
							}
							
							echo "<tr align=\"center\">";
								echo "<td>".$row['numero_vale']."</td>";
								echo "<td>".fechaComun($row['fecha_vale'])."</td>";	
								echo "<td>".$row['nombre_vale']."</td>";							
								echo "<td>".$estadoVale."</td>";
								echo "<td>".$row['nombre_devolucion']."</td>";
								echo "<td>".fechaComun($row['fecha_devolucion'])."</td>";
							echo "</tr>";
							
						echo "</table>";	
										
					}
					
					$sqlOrdenNotaCredito = sprintf("SELECT numeracion_nota_credito, fechaNotaCredito, aplicaLibros, estatus_nota_credito
					 FROM
					cj_cc_notacredito WHERE id_orden = %s AND idDepartamentoNotaCredito = 1 LIMIT 1",
					valTpDato($idOrdenInformacion,"int"));
					$queryOrdenNotaCredito = mysql_query($sqlOrdenNotaCredito);
					if(!$queryOrdenNotaCredito) { echo "no se pudo obtener informacion de nota de credito \n". mysql_error()."\n Linea:".__LINE__; }
					
					while($row = mysql_fetch_assoc($queryOrdenNotaCredito)){
						
						echo "<br><table class=\"tabla\" width=\"100%\" cellpadding=\"2\" border=\"1\" >";
							echo "<tr class=\"tituloColumna\" align=\"center\"><td colspan=\"10\"> INFORMACI&Oacute;N NOTA DE CREDITO</td></tr>";
							echo "<tr class=\"tituloColumna\" align=\"center\">";
								echo "<td>N&uacute;mero de nota de credito</td>";
								echo "<td>Fecha registro Nota de Credito</td>";
								echo "<td>Aplica libros</td>";	
								echo "<td>Estado Nota de Credito</td>";	
							echo "</tr>";
							
							$aplicaLibros = "";
							if($row['aplicaLibros'] == 1){
								$aplicaLibros = "SI";
							}else if($row['aplicaLibros'] == 0){
								$aplicaLibros = "NO";
							}
							
							$estadoNotaCredito = "";
							if($row['estatus_nota_credito'] == 1){
								$estadoNotaCredito = "Aprobada";
							}elseif($row['estatus_nota_credito'] == 2){
								$estadoNotaCredito = "Aplicada";
							}
							
							echo "<tr align=\"center\">";
								echo "<td>".$row['numeracion_nota_credito']."</td>";
								echo "<td>".fechaComun($row['fechaNotaCredito'],"solo fecha")."</td>";
								echo "<td>".$aplicaLibros."</td>";	
								echo "<td>".$estadoNotaCredito."</td>";							
							echo "</tr>";
							
						echo "</table>";					
					}
					
					echo "<br>";
				 ?>
            </td>
        </tr>
        
        <?php } ?>
        
        <tr>
            <td align="left">
          	<form name="frm_agregar_paq" id="frm_agregar_paq" style="margin:0">
            	<!--<col style="width:20px;"/> APLICA A LA PRIMERA COLUMNA ESTA PROPIEDAD SI LE QUIERO COLOCARSELA A LAS DEMAS HAGO LO MISMO-->
            	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tituloArea tituloHerramientas">
                <tr align="left">
                    <td width="12%">
                        <button type="button" class="noprint" id="btnInsertarPaq"  name="btnInsertarPaq" onclick="
                        if (validarCampo('txtIdValeRecepcion','t','') == true
                        && validarCampo('lstTipoOrden','t','lista') == true
                        ) {
                    
                            if(tipoOrdenAccesorios(1) === true){
                               return alert('No disponible por tipo de orden');
                            }
                        	document.forms['frmBuscarPaquete'].reset();
                            document.forms['frmDatosPaquete'].reset();
                            
                            $('hddEscogioPaquete').value = '';
                            
                            $('tblPermiso').style.display = 'none';
                            $('tblListados').style.display = 'none';
                            $('tblGeneralPaquetes').style.display = '';
                            $('tblListadoTempario').style.display = 'none';
                            $('tblArticulo').style.display = 'none';
                            $('tblNotas').style.display = 'none';
                            $('tblListadoTot').style.display = 'none';
                            
                            $('tblBuscarPaquete').style.display = '';
                            $('tdListadoPaquetes').style.display = '';
                            $('tblListadoTemparioPorPaquete').style.display = 'none';
                            $('tblListadoRepuestosPorPaquete').style.display = 'none';
                            
                            $('btnAsignarPaquete').style.display = 'none';
                            $('btnCancelarDivPpalPaq').style.display = '';
                            $('btnCancelarDivSecPaq').style.display = 'none';
							
                            xajax_buscarPaquete(xajax.getFormValues('frmBuscarPaquete'));
                        } else {
                            validarCampo('txtIdValeRecepcion','t','');
                            validarCampo('lstTipoOrden','t','lista');

                            alert('Los campos señalados en rojo son requeridos');
                            return false;
                        }" title="Agregar Paquete"><img src="../img/iconos/ico_agregar.gif" alt="imagen"></button>
                        <button type="button" class="noprint" id="btnEliminarPaq" name="btnEliminarPaq" onclick="xajax_eliminarPaquete(xajax.getFormValues('frm_agregar_paq'));"><img src="../img/iconos/ico_quitar.gif" alt="eliminar articulo"></button>
                    </td>
                    <td align="center" width="76%">PAQUETES</td>
                    <td width="12%"><input type="hidden" id="temparioPaqueteEliminar" name="temparioPaqueteEliminar"></input></td>
                </tr>
                </table>
            
          		<table border="0" cellpadding="0" width="100%" class="contenidoHerramientas">
                <tr align="center" class="tituloColumna">
                    <td id="tdInsElimPaq" class="color_column_insertar_eliminar_item"><input type="checkbox" id="cbxItmPaq" onclick="selecAllChecks(this.checked,this.id,'frm_agregar_paq');"></td>
                    <td width="16%">C&oacute;digo</td>
                    <td width="74%">Descripci&oacute;n</td>
                    <td width="10%">Total</td>
                    <td></td>
                    <td id="tdPaqAprob" class="color_column_aprobacion_item"><input type="checkbox" id="cbxItmPaqAprob" onclick="selecAllChecks(this.checked,this.id,'frm_agregar_paq'); xajax_calcularTotalDcto();" checked="checked"></td>
                </tr>
                <tr id="trm_pie_paquete"></tr>
                </table>
			</form>
			</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>
            <form id="frmListaArticulo" name="frmListaArticulo" style="margin:0">
            	<table border="0" cellpadding="0" cellspacing="0" class="tituloArea tituloHerramientas" width="100%">
                <tr align="left">
                    <td width="12%">
                        <button type="button" class="noprint" id="btnInsertarArt" name="btnInsertarArt" onclick="
                        if (validarCampo('txtIdValeRecepcion','t','') == true
						&& validarCampo('lstTipoOrden','t','lista') == true
                        ) {
                            if(tipoOrdenAccesorios(2) === true){
                               return alert('No disponible por tipo de orden');
                            }
                    
                            document.forms['frmBuscarArticulo'].reset();
                            document.forms['frmDatosArticulo'].reset();
                            $('txtDescripcionArt').innerHTML = '';
                                  
                            $('txtCodigoArt').className = 'inputInicial';
                            $('txtCantDisponible').className = 'inputInicial';      
                            $('txtCantidadArt').className = 'inputInicial';
                            $('lstPrecioArt').className = 'inputInicial';
                            //$('hddIdIvaRepuesto').className = 'inputInicial';
                            //$('txtIvaRepuesto').className = 'inputInicial';
                            
                            $('tdMsjArticulo').style.display = 'none';
                    		
                    		cerrarVentana = false;
                    		
							$('tdListadoArticulos').innerHTML = '';
                            
                            $('tblPermiso').style.display = 'none';
                            $('tblListados').style.display = 'none';
                            $('tblGeneralPaquetes').style.display = 'none';
                            $('tblListadoTempario').style.display = 'none';
                            $('tblArticulo').style.display = '';
                            $('tblNotas').style.display = 'none';
                            $('tblListadoTot').style.display = 'none';
                            
                            $('tdFlotanteTitulo').innerHTML = 'Articulos';
                            if ($('divFlotante').style.display == 'none') {
                                $('divFlotante').style.display = '';
                                centrarDiv($('divFlotante'));
                            }
                            $('txtCodigoArticulo0').focus();
                            $('txtCodigoArticulo0').select();
                        } else {
                            validarCampo('txtIdValeRecepcion','t','');
                            validarCampo('lstTipoOrden','t','lista');
                            
                            alert('Los campos señalados en rojo son requeridos');
                            return false;
                        }" title="Agregar Articulo"><img src="../img/iconos/ico_agregar.gif" alt="imagen"></button>
                        <button type="button"  class="noprint" id="btnEliminarArt" readonly="readonly" name="btnEliminarArt" onclick="
                        if($('hddAccionTipoDocumento').value != 1)
                            xajax_validarSiLosArticulosCargadosEstanRelacionadosConPresupuesto(xajax.getFormValues('frmListaArticulo'));
                        else 
                            xajax_eliminarArticuloEnPresupuesto(xajax.getFormValues('frmListaArticulo'));"><img src="../img/iconos/ico_quitar.gif"></button>
                    </td>
                    <td align="center" width="76%">REPUESTOS GENERALES</td>
                    <td width="12%"></td>
                </tr>
                </table>
 
				<!-- EL ULTIMO PARAMETRO ES EL ID DEL FORMULARIO ASIGNADO POR EL HTML DINAMICAMENTE.-->
                <table border="0"  cellpadding="0" width="100%" class="contenidoHerramientas">
                <tr align="center" class="tituloColumna">
                    <td id="tdInsElimRep" class="color_column_insertar_eliminar_item" width="4"><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,'frmListaArticulo');"></td>
                    <td width="16%">Código</td>
                    <td>Descripción</td>
                    <td width="4%">Lote</td>
                    <td width="8%">Cantidad</td>
                    <td id="tdSolicitado" width="2%">Sol.</td>
                    <td id="tdDespachado" width="2%">Resv.</td>
<!--                    <td id="tdDisponible" width="2%">Dis.</td>-->
                    <td width="10%">Precio Unit.</td>
                    <td width="6%">% Impuesto</td>
                    <td width="4%" title="Total sin Impuestos">Total S/I</td>
                    <td width="10%" align="center">Total</td>
                    <td></td>
                    <td id="tdRepAprob" class="color_column_aprobacion_item" width="4"><input type="checkbox" id="cbxItmAprob" onclick="selecAllChecks(this.checked,this.id,3); xajax_calcularTotalDcto();" checked="checked"></td>
                </tr>
                <tr id="trItmPie"></tr>
                </table>
			  </form>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
            <td>
            <form id="frmListaManoObra" name="frmListaManoObra" style="margin:0">
            	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tituloArea tituloHerramientas" >
                <tr align="left">
                    <td width="12%">
                        <button type="button" class="noprint" id="btnInsertarTemp" name="btnInsertarTemp" onclick="
                        if (validarCampo('txtIdValeRecepcion','t','') == true
						&& validarCampo('lstTipoOrden','t','lista') == true
                        ) {
                            if(tipoOrdenAccesorios(3) === true){
                               return alert('No disponible por tipo de orden');
                            }
                        	xajax_formListaTempario(xajax.getFormValues('frmPresupuesto'));
                        } else {
                            validarCampo('txtIdValeRecepcion','t','');
                            validarCampo('lstTipoOrden','t','lista');
                            
                            alert('Los campos señalados en rojo son requeridos');
                            return false;
                        }" title="Agregar Mano de obra"><img src="../img/iconos/ico_agregar.gif" alt="imagen"></button>
                        <button type="button" class="noprint" id="btnEliminarTemp" readonly="readonly" name="btnEliminarTemp" onclick="
                        if ($('hddAccionTipoDocumento').value != 1)
                            xajax_validarSiLosTempariosCargadosEstanRelacionadosConPresupuesto(xajax.getFormValues('frmListaManoObra'));
                        else 
                            xajax_eliminarTemparioEnPresupuesto(xajax.getFormValues('frmListaManoObra'));"><img src="../img/iconos/ico_quitar.gif" alt="imagen"></button>
                    </td>
                    <td align="center" width="76%">MANO DE OBRA GENERAL</td>
                    <td width="12%"></td>
                </tr>
                </table>
            
                <table border="0" cellpadding="0" width="100%" class="contenidoHerramientas">
                <tr align="center" class="tituloColumna">
                    <td id="tdInsElimManoObra" class="color_column_insertar_eliminar_item"><input type="checkbox" id="cbxItmTemp" onclick="selecAllChecks(this.checked,this.id,'frmListaManoObra');"></td>
                    <td id="tdCodigoMecanico" style="display:none;" nowrap="nowrap">Código Mec&aacute;nico</td>
                    <td id="tdNombreMecanico" style="display:none;" nowrap="nowrap">Nombre Mec&aacute;nico</td>
                    <td width="14%">Secci&oacute;n</td>
                    <td width="12%">Subsecci&oacute;n</td>
                    <td width="8%">Código Tempario</td>
                    <td width="24%">Descripción</td>
                    <td width="6%">Origen</td>
                    <td width="6%">Modo</td>
                    <td width="10%">Operador</td>
                    <td width="10%">UT/Precio</td>
                    <td width="10%">Total</td>
                    <td id="tdTempAprob" class="color_column_aprobacion_item"><input type="checkbox" id="cbxItmTempAprob" onclick="selecAllChecks(this.checked,this.id,4); xajax_calcularTotalDcto();" checked="checked"></td>
                </tr>
                <tr id="trm_pie_tempario"></tr>
                </table>
			</form>
			</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>
            <form id="frmListaTot" name="frmListaTot" style="margin:0">
            	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tituloArea tituloHerramientas">
                <tr align="left">
                    <td width="12%">
                        <button type="button" class="noprint" id="btnInsertarTot" name="btnInsertarTot" onclick="
                        if (validarCampo('txtIdValeRecepcion','t','') == true
                        && validarCampo('lstTipoOrden','t','lista') == true
                        ) {
                    
                            if(tipoOrdenAccesorios(4) === true){
                               return alert('No disponible por tipo de orden');
                            }
                    
                        	document.forms['frmBuscarTot'].reset();
                            document.forms['frmListadoTot'].reset();
                            document.forms['frmDatosTot'].reset();
                                          
                            $('txtNumeroTot').className = 'inputInicial';
                            $('txtNumeroTot').value = '';
                            $('idPrecioTotAccesorio').value = '';
                            $('cambioPorcentajeTot').innerHTML = '';
							
							$('tblPermiso').style.display = 'none';
                            $('tblListados').style.display = 'none';
                            $('tblGeneralPaquetes').style.display = 'none';
                            $('tblListadoTempario').style.display = 'none';
                            $('tblArticulo').style.display = 'none';
                            $('tblNotas').style.display = 'none';
                            $('tblListadoTot').style.display = '';
                            
                            xajax_buscarTot(xajax.getFormValues('frmBuscarTot'),xajax.getFormValues('frmTotalPresupuesto'));
                        } else {
                            validarCampo('txtIdValeRecepcion','t','');
                            validarCampo('lstTipoOrden','t','lista');
                            
                            alert('Los campos señalados en rojo son requeridos');
                            return false;
                        }" title="Agregar T.O.T"><img src="../img/iconos/ico_agregar.gif" alt="imagen"></button>
                        <button type="button" class="noprint" id="btnEliminarTot" name="btnEliminarTot" onclick="
                        if ($('hddAccionTipoDocumento').value != 1) {
							/*if ($('btnEliminarTot').readOnly == true) { 
                                alert('Usted no tiene acceso para realizar esta acción, debe ingresar la clave de permiso'); xajax_formClave(xajax.getFormValues('frmTotalPresupuesto'), 'elim_tot'); 
                                $('tblPorcentajeDescuento').style.display='none';
                                $('tblClaveDescuento').style.display = '';                           
							} else {*/
								xajax_eliminarTot(xajax.getFormValues('frmListaTot'));
							//}
                        } else {
                            xajax_eliminarTot(xajax.getFormValues('frmListaTot'));
                        }" readonly="readonly"><img src="../img/iconos/ico_quitar.gif"></button>
                    </td>
                    <td align="center" width="76%">TRABAJOS OTROS TALLERES (T.O.T)</td>
                    <td width="12%"></td>
                </tr>
                </table>
            	
                <table border="0"  cellpadding="0" width="100%" class="contenidoHerramientas">
                <tr align="center" class="tituloColumna">
                    <td id="tdInsElimTot" class="color_column_insertar_eliminar_item"><input type="checkbox" id="cbxItmTot" onclick="selecAllChecks(this.checked,this.id,5);"></td>
                    <td width="8%">Nro. T.O.T</td>
                    <td width="36%">Proveedor</td>
                    <td width="14%">Tipo Pago</td>
                    <td width="12%">Monto T.O.T</td>
                    <td width="18%">Porcentaje T.O.T</td>
                    <td width="10%">Monto Total</td>
                    <td id="tdTotAprob" class="color_column_aprobacion_item"><input type="checkbox" id="cbxItmTotAprob" onclick="selecAllChecks(this.checked,this.id,5); xajax_calcularTotalDcto();" checked="checked"></td>
                </tr>
                <tr id="trm_pie_tot"></tr>
            	</table> 
            </form>
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>
            <form id="frmListaNota" name="frmListaNota" style="margin:0">
                <table border="0" cellpadding="0" cellspacing="0" class="tituloArea tituloHerramientas" width="100%">
                <tr align="left">
                    <td width="12%">
						<button type="button" class="noprint" id="btnInsertarNota" name="btnInsertarNota" onclick="
                        if (validarCampo('txtIdValeRecepcion','t','') == true
						&& validarCampo('lstTipoOrden','t','lista') == true
                        ) {
                    
                            if(tipoOrdenAccesorios(5) === true){
                               return alert('No disponible por tipo de orden');
                            }
                            document.forms['frmDatosNotas'].reset();
                                          
                            $('txtDescripcionNota').className = 'inputInicial';
                            $('txtPrecioNota').className = 'inputInicial';  
                            
                            $('tblPermiso').style.display = 'none';
                            $('tblListados').style.display = 'none';
                            $('tblGeneralPaquetes').style.display = 'none';
                            $('tblListadoTempario').style.display = 'none';
                            $('tblArticulo').style.display = 'none';
                            $('tblNotas').style.display = '';
                            $('tblListadoTot').style.display = 'none';
                            
                            $('tdFlotanteTitulo').innerHTML = 'Notas / Cargos Adicionales';
                            if ($('divFlotante').style.display == 'none') {
                                $('divFlotante').style.display = '';
                                centrarDiv($('divFlotante'));
                            }
                            $('txtDescripcionNota').focus();
                            $('txtDescripcionNota').select();
                        } else {
                            validarCampo('txtIdValeRecepcion','t','');
                            validarCampo('lstTipoOrden','t','lista');
                            
                            alert('Los campos señalados en rojo son requeridos');
                            return false;
                        }" title="Agregar Nota"><img src="../img/iconos/ico_agregar.gif"></button>
                        <button type="button" class="noprint" id="btnEliminarNota" readonly="readonly" name="btnEliminarNota" onclick="
                        if ($('hddAccionTipoDocumento').value != 1)
                            xajax_validarSiLasNotasCargadasEstanRelacionadasConPresupuesto(xajax.getFormValues('frmListaNota'));
                        else 
                            xajax_eliminarNotaEnPresupuesto(xajax.getFormValues('frmListaNota'));"><img src="../img/iconos/ico_quitar.gif"></button>
                    </td>
                    <td align="center" width="76%">NOTAS / CARGO ADICIONAL</td>
                    <td width="12%"></td>
                </tr>
                </table>
            
                <table border="0"  cellpadding="0" width="100%" class="contenidoHerramientas">
                <tr align="center" class="tituloColumna">
                    <td id="tdInsElimNota" class="color_column_insertar_eliminar_item"><input type="checkbox" id="cbxItmNota" onclick="selecAllChecks(this.checked,this.id,6);"></td>
                    <td width="90%">Descripción</td>
                    <td width="10%">Total</td>
                    <td id="tdNotaAprob" class="color_column_aprobacion_item"><input type="checkbox" id="cbxItmNotaAprob" onclick="selecAllChecks(this.checked,this.id,'frmListaNota'); xajax_calcularTotalDcto();" checked="checked"></td>
                </tr>
                <tr id="trm_pie_nota"></tr>
                </table>
			</form>
			</td>
        </tr>
        <tr>
        	<td align="right">
            <form id="frmTotalPresupuesto" name="frmTotalPresupuesto" style="margin:0">
                <hr/>
                <input type="hidden" name="hddDevolucionFactura" id="hddDevolucionFactura" value="<?php if(isset($_GET['dev'])) echo $_GET['dev'];?>"/>
                <input type="hidden" id="hddObj" name="hddObj"/>
                <input type="hidden" id="hddObjPaquete" name="hddObjPaquete" readonly="readonly"/>
                <input type="hidden" id="hddObjRepuestosPaquete" name="hddObjRepuestosPaquete" readonly="readonly"/>
                <input type="hidden" id="hddObjTempario" name="hddObjTempario" readonly="readonly"/>
                <input type="hidden" id="hddObjTot" name="hddObjTot" readonly="readonly"/>
                <input type="hidden" id="hddObjNota" name="hddObjNota" readonly="readonly"/>
                <input type="hidden" id="hddTipoDocumento" name="hddTipoDocumento" value="<?php echo $_GET['doc_type'];?>"/>  
                <input type="hidden" id="hddAccionTipoDocumento" name="hddAccionTipoDocumento" value="<?php echo $_GET['acc'];?>"/>
                <input type="hidden" id="hddMecanicoEnOrden" name="hddMecanicoEnOrden"/>
                <input type="hidden" id="hddItemsCargados" name="hddItemsCargados"/>
             	<input type="hidden" id="hddNroItemsPorDcto" name="hddNroItemsPorDcto" value="40"/>     
                <input type="hidden" id="hddObjDescuento" name="hddObjDescuento"/>
                <input type="hidden" id="hddItemsNoAprobados" name="hddItemsNoAprobados"/>
                <input type="hidden" id="hddOrdenEscogida" name="hddOrdenEscogida"/>
                <input type="hidden" id="hddLaOrdenEsRetrabajo" name="hddLaOrdenEsRetrabajo" value="<?php echo $_GET['ret'];?>"/>
                <input type="hidden" id="hddAgregarOrdenGarantia" name="hddAgregarOrdenGarantia"/>
                <input type="hidden" id="hddDuplicarManoObra" name="hddDuplicarManoObra"/>
                <input type="hidden" id="hddDuplicarRepuesto" name="hddDuplicarRepuesto"/>
                <input type="hidden" id="hddSeleccionActivo" name="hddSeleccionActivo" value="0"/>
                <table border="0" width="100%">
                <tr>
                    <td align="right" colspan="2" id="tdGastos" valign="top" width="40%">
                        <table cellpadding="0" cellspacing="0" width="100%" class="divMsjInfo" id="tblLeyendaOrden">
                        <tr>
                            <td width="25"><img src="../img/iconos/ico_info2.gif" width="25" alt="imagen"></td>
                            <td align="center">
                                <table>
                                <tr>
                                    <td><img src="../img/iconos/ico_aceptar.gif" alt="imagen"></td>
                                    <td>Paquete o Repuesto Disponibilidad Suficiente</td>
                                </tr>
                                <tr>
                                    <td><img src="../img/iconos/ico_alerta.gif" alt="imagen"></td>
                                    <td>Paquete o Repuesto Poca Disponibilidad</td>
                                </tr>
                                <tr>
                                    <td><img src="../img/iconos/ico_error.gif" alt="imagen"></td>
                                    <td>Paquete o Repuesto sin Disponibilidad</td>
                                </tr>
                                <tr>
                                  <td class="color_column_insertar_eliminar_item" style="border:1px dotted #999999">&nbsp;</td>
                                  <td>Eliminar Item</td>
                                </tr>
                                <tr>
                                  <td class="color_column_aprobacion_item" style="border:1px dotted #999999">&nbsp;</td>
                                  <td>Aprobar Item</td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                        
                        <table id="tblMotivoRetrabajo" style="display:none" cellpadding="0" cellspacing="0" align="center" width="100%">
						<tr>
                            <td colspan="2" class="tituloCampo" height="22">Motivo:</td>
                        </tr>
                        <tr>
                            <td colspan="2"><textarea name="txtMotivoRetrabajo" id="txtMotivoRetrabajo" cols="60" rows="5"></textarea></td>
                        </tr>
                        </table>
					</td>
                    <td rowspan="6" width="60%">
                        <table border="0" width="100%">
                        <tr align="right">
                            <td class="tituloCampo" width="38%">Sub-Total:</td>
                            <td width="22%">&nbsp;</td>
                            <td colspan="2"></td>
                            <td width="23%"><input type="text" id="txtSubTotal" name="txtSubTotal" readonly="readonly" size="18" style="text-align:right"></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Descuento General:</td>
                            <td><img style="display:none;" class="noprint puntero" title="Calculadora de Descuentos" src="../img/iconos/edit_privilegios.png"
                                 onClick = "if ($('hddAccionTipoDocumento').value != 2) {
                                                if ($('txtDescuento').readOnly == true) {
                                                     xajax_formValidarPermisoEdicion('edc_dcto_ord');
                                                }else{
                                                    abrirCalculadoraDescuentos();
                                                }
                                            }"    /></td>
                            <td width="14%">
                                <input type="text" id="txtDescuento" name="txtDescuento" size="6" style="text-align:right" readonly="readonly" value="0"
                                onkeyup="
                                xajax_calcularDcto(xajax.getFormValues('frmPresupuesto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalPresupuesto'), xajax.getFormValues('frm_agregar_paq'), xajax.getFormValues('frmListaManoObra'), xajax.getFormValues('frmListaNota'), xajax.getFormValues('frmListaTot'),'false');"
                                onclick="
                                if ($('hddAccionTipoDocumento').value != 2) {
                                    if ($('txtDescuento').readOnly == true) {
                                         xajax_formValidarPermisoEdicion('edc_dcto_ord');
                                    }
                                }"/>%
							</td>
                            <td width="3%" style="text-align:center">
                                <img id="imgAgregarDescuento" src="../img/iconos/lock_go.png" class="noprint" onclick="
//                                if ($('hddAccionTipoDocumento').value != 2) {
//                                    xajax_formValidarPermisoEdicion('agreg_dcto_adnl');
//                                }
                        
                                if ($('hddAccionTipoDocumento').value != 2) {
                                    if ($('txtDescuento').readOnly == true) {
                                         xajax_formValidarPermisoEdicion('edc_dcto_ord');
                                    }
                                }
                                "style="cursor:pointer" title="Desbloquear"/>
                            </td>
                            <td><input type="text" id="txtSubTotalDescuento" name="txtSubTotalDescuento" size="18" readonly="readonly" style="text-align:right"></td>
                        </tr>
                        <tr id="trm_pie_dcto"></tr>
                        <tr align="right" style="display:none">
                              <td class="tituloCampo">Base Imponible:</td>
                              <td></td>
                              <td colspan="2">&nbsp;</td>
                              <td><input type="text" id="txtBaseImponible" name="txtBaseImponible" size="18" readonly="readonly" style="text-align:right"></td>
                        </tr>
                        <tr align="right" style="display:none">
                            <td class="tituloCampo">Items Con Impuestos</td>
                            <td></td>
                            <td colspan="2"></td>
                            <td><input type="text" id="txtGastosConIva" name="txtGastosConIva" readonly="readonly" size="18" style="text-align:right"></td>
                        </tr>
                        <!--AQUI SE INSERTAN LAS FILAS PARA EL IVA-->
                        <tr align="right" id="trGastosSinIva">
                            <td class="tituloCampo">Monto Exento:</td>
                            <td></td>
                            <td colspan="2"></td>
                            <td><input type="text" id="txtMontoExento" name="txtMontoExento" readonly="readonly" size="18" style="text-align:right"></td>
                        </tr>
                        
                        <?php 
                            if(ordenEnProceso($_GET["id"])){
                                $arrayIvas = ivaServicios(); //proviene de ac_iv_general devuelve array de arrays
                            }else{
                                $arrayIvas = cargarIvasOrden($_GET["id"]);
                            }
                        
                            foreach($arrayIvas as $key => $arrayIva){//funcion en ac_iv_general 
                            
                        ?>
                        <tr class="noRomper" align="right">
                                <td class="tituloCampo"><?php echo $arrayIva["observacion"]; ?></td>
                                <td>
                                    <input style="display:none;" class="puntero" type="checkbox" name="ivaActivo[]" checked="checked" id="ivaActivo<?php echo $key; ?>"  value="<?php echo $key; ?>" onclick="return false"/>                                    
                                    <input type="text" id="txtBaseImponibleIva<?php echo $key; ?>" name="txtBaseImponibleIva<?php echo $key; ?>" readonly="readonly" size="18" style="text-align:right"/>                                    
                                </td>
                                <td>                                    
                                    <input type="hidden" id="hddIdIvaVenta<?php echo $key; ?>" name="hddIdIvaVenta<?php echo $key; ?>" value="<?php echo $key ?>"  readonly="readonly"/>
                                    <input type="text" id="txtIvaVenta<?php echo $key; ?>" name="txtIvaVenta<?php echo $key; ?>" value="<?php echo $arrayIva["iva"]; ?>"  readonly="readonly" size="6" style="text-align:right" value="0"/>%
                                </td>
                                <td>&nbsp;</td>
                                <td><input type="text" id="txtTotalIva<?php echo $key; ?>" name="txtTotalIva<?php echo $key; ?>" readonly="readonly" size="18" style="text-align:right"/></td>
                            </tr>
                        <?php } ?>
                        
                        <tr>
                        	<td colspan="5"><hr style="border:0.5px dotted #999999"></td>
                        </tr>
                        <tr align="right" id="trNetoPresupuesto">
                            <td id="tdEtiqTipoDocumento" class="tituloCampo"></td>
                            <td></td>
                            <td colspan="2"></td>
                            <td><input type="text" id="txtTotalPresupuesto" name="txtTotalPresupuesto" readonly="readonly" size="18" style="text-align:right"></td>
                        </tr>
						</table>
					</td>
				</tr>
                <tr>
                    <td colspan="2">
                        <!-- <fieldset>
                        <legend>Generar a:</legend>
                        <p>&nbsp;</p>
                        </fieldset>-->
                    </td>
                </tr>
                </table>
			</form>
           </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr id="trRepuestosNoDisponibles" style="display:none">
			<td>
            	<table width="100%">
                <tr>
                	<td align="left" class="tituloArea tituloHerramientas">Repuestos No Disponibles</td>
                </tr>
                <tr>
                	<td>
                    	<table border="0" cellpadding="0" width="100%" class="contenidoHerramientas">
                        <tr align="center" class="tituloColumna">
                            <td width="8%">Origen</td>
                            <td width="14%">Código</td>
                            <td width="46%">Descripción</td>
                            <td width="8%">Cantidad</td>
                            <td width="10%">Precio Unit.</td>
                            <td width="4%">% Impuesto</td>
                            <td width="10%">Total</td>
                        </tr>
                        <tr id="trm_pie_rpto_no_disponible"></tr>
                        </table>
                    </td>
                </tr>
                </table>
			</td>
        </tr>
        <tr>
                <td align="right"  class="noprint" <?php if($_GET['sinmenu'] == "1") { echo "style='display:none;'"; } ?> >
            	<hr>
            	<?php
            		if($dateTime_fechaOrden > $dateTime_fechaReconversion){
            			echo "<button style=\"display:none;\" type=\"button\" id=\"btnReconversionMonetaria\" onClick=\"reconversionMonetaria(0)\"><table align=\"center\" cellpadding=\"0\" cellspacing=\"0\"><tr><td>&nbsp;</td><td><img src=\"../img/iconos/ico_cambio.png\" alt=\"imagen\"></td><td>&nbsp;</td><td>Reconversion de Repuestos y Mano de Obra</td></tr></table></button>";
					}else{
						echo "<input type='hidden' value='1' id='muestraBtnReconversion'>";
						echo "<button type=\"button\" id=\"btnReconversionMonetaria\" onClick=\"reconversionMonetaria(1)\"><table align=\"center\" cellpadding=\"0\" cellspacing=\"0\"><tr><td>&nbsp;</td><td><img src=\"../img/iconos/ico_cambio.png\" alt=\"imagen\"></td><td>&nbsp;</td><td>Reconversion de Repuestos y Mano de Obra</td></tr></table></button>";
					}

            	?>
                <button class="noprint" type="button" id="btnGuardar" name="btnGuardar" onclick="
                if ($('hddDevolucionFactura').value != '') {
                    if ($('hddDevolucionFactura').value == 0) {
                        validarDevolucion();
					}
				} else {
                    if ($('hddTipoDocumento').value == 3) {
                        validarNroControl(); 
					} else {
                        if ($('hddTipoDocumento').value == 4) {
                            xajax_generarDctoApartirDeOrden(xajax.getFormValues('frmPresupuesto'), xajax.getFormValues('frmTotalPresupuesto'));
                        } else {
                            if (parseInt($('hddItemsCargados').value) > parseInt($('hddNroItemsPorDcto').value)) {
                                alert('La Orden tiene ' + $('hddItemsCargados').value + ' items incluyendo el contenido de Paquetes. El Nro máximo son ' + $('hddNroItemsPorDcto').value + ' items. Si desea continuar elimine items o abra un Nueva Orden.');
                            } else {
                                validarFormPresupuesto();
							}
						}                  
                    }
				}" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/save.png" alt="imagen"></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
                 <button class="noprint" type="button" id="btnCancelar" name="btnCancelar" onclick=" 
                 /* xajax_desbloquearOrden($('hddIdOrden').value); lo reemplace por este para que esperara: */
                 if($('hddIdOrden').value != ''){//id orden presupuesto
                        
                            //presupuesto
                        if($('hddTipoDocumento').value == 1){
                             window.location.href='sa_presupuesto_list.php';  
                         }
                            //orden control orden y tambien generar a facturacion
                        if($('hddTipoDocumento').value == 2){
                            if($('hddAccionTipoDocumento').value == 3 || $('hddAccionTipoDocumento').value == 4){//3editando orden o facturando //4 aprobando                          
                                    var desbloquear = xajax.call('desbloquearOrden', {mode:'synchronous', parameters:[$('hddIdOrden').value]});
                                    if(desbloquear){
                                        window.location.href='sa_orden_servicio_list.php';
                                    } 
                             }else{//solo viendo
                             		window.location.href='sa_orden_servicio_list.php';
                             }
                         }
                            
                        if($('hddTipoDocumento').value == 3){
                            window.location.href='index.php';//antes apuntaba a devolucion vales de salida; y 3 dice facturacion
                        }
                        
                        if($('hddTipoDocumento').value == 4){
                             window.location.href='sa_orden_servicio_list.php'; //dice Generar presupuesto
                         }
                               
                  }else{//nueva orden
                  		window.location.href='sa_orden_servicio_list.php';
                  }
                         
                         " style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_error.gif" alt="imagen"></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
             </td>
        </tr>
        </table>
    </div>
    
        <?php if($_GET['sinmenu'] !="1"){ include("menu_serviciosend.inc.php"); } ?>
</div>
</body>
</html>


<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
<form id="frmPermiso" name="frmPermiso" style="margin:0px" onsubmit="return false;">
    <table border="0" id="tblPermiso" style="display:none" width="350px">
    <tr>
    	<td>
        	<table width="100%">
            <tr>
            	<td align="right" class="tituloCampo" width="32%"><span class="textoRojoNegrita">*</span>Ingrese Clave:</td>
                <td width="68%">
                	<input type="password" id="txtContrasena" name="txtContrasena" size="30"/>
                    <input type="hidden" id="hddModulo" name="hddModulo" readonly="readonly" size="30"/>
				</td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right">
	        <hr>
            <input type="submit" onclick="validarFormPermiso();" value="Aceptar">
            <input type="button" id="btnCancelarPermiso" onclick="
            if ($('hddModulo').value == 'iv_catalogo_venta_precio_editado'
            || $('hddModulo').value == 'iv_catalogo_venta_precio_editado_bajar'
            || $('hddModulo').value == 'iv_precio_editado_debajo_costo'
            || $('hddModulo').value == 'iv_catalogo_venta_precio_venta') {
            	$('tblPermiso').style.display = 'none';
                $('tblListados').style.display = 'none';
                $('tblGeneralPaquetes').style.display = 'none';
                $('tblListadoTempario').style.display = 'none';
                $('tblArticulo').style.display = '';
                $('tblNotas').style.display = 'none';
                $('tblListadoTot').style.display = 'none';
                
                $('divFlotante').style.display = '';
                centrarDiv($('divFlotante'));
                
                $('txtCantidadArt').focus();
                $('txtCantidadArt').select();
			} else if ($('hddModulo').value == 'sa_precio_editado_tempario') {
            	$('tblPermiso').style.display = 'none';
                $('tblListados').style.display = 'none';
                $('tblGeneralPaquetes').style.display = 'none';
                $('tblListadoTempario').style.display = '';
                $('tblArticulo').style.display = 'none';
                $('tblNotas').style.display = 'none';
                $('tblListadoTot').style.display = 'none';
                
                $('divFlotante').style.display = '';
                centrarDiv($('divFlotante'));
                
                $('txtPrecioTemp').focus();
                $('txtPrecioTemp').select();
			} else {
            	$('divFlotante').style.display = 'none';
			}" value="Cancelar">
        </td>
    </tr>
    </table>
</form>
    
    <table border="0" id="tblListados" style="display:none" width="100%">
    <tr id="trBuscarPresupuesto">
    	<td>
        	<form id="frmBuscarPresupuesto" name="frmBuscarPresupuesto" method="post" style="margin:0">
            	<table>
                <tr>
                	<td>Empresa / Sucursal:</td>
                	<td id="tdlstEmpresaBusq">
                    	<select id="lstEmpresaBusq" name="lstEmpresaBusq">
                        	<option>[ Seleccione ]</option>
                        </select>
                        <script>
                       // xajax_cargaLstEmpresaBusq(); //ELIMINADO gregor
						</script>
					</td>
                    <td><input type="button" id="btnBuscarPresupuesto" name="btnBuscarPresupuesto" onclick="xajax_buscarDcto(xajax.getFormValues('frmBuscarPresupuesto'));" value="Buscar"></td>
                </tr>
                </table>
            </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmBuscarVale" name="frmBuscarVale" onsubmit="return false;" style="margin:0">
            <table align="right" border="0">
            <tr align="left" style="display:none">
                <td align="right" class="tituloCampo" style="visibility:hidden;">Empresa:</td>
                <td id="tdlstEmpresa" style="visibility:hidden">
                    <select id="lstEmpresa" name="lstEmpresa">
                        <option value="-1">[ Todos ]</option>
                    </select>
					<script type="text/javascript">
                    //xajax_cargaLstEmpresas();
                    </script>
				</td>
                <td align="right" class="tituloCampo" style="visibility:hidden">Tipo de Orden:</td>
                <td id="tdlstTipoOrden" style="visibility:hidden">
                    <select id="lstTipoOrden" name="lstTipoOrden">
                        <option value="-1">[ Todos ]</option>
                    </select>
                    <script type="text/javascript">
					//xajax_cargaLstTipoOrden();
                    </script>
				</td>
                <td align="right" class="tituloCampo" style="visibility:hidden">Estado Orden:</td>
                <td id="tdlstEstadoOrden" style="visibility:hidden">
                     <select id="lstEstadoOrden" name="lstEstadoOrden">
                        <option value="-1">[ Todos ]</option>
                    </select>
                    <script type="text/javascript">
					//xajax_cargaLstEstadoOrden();
                    </script>   
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo" width="100">Criterio:</td>
                <td id="tdlstAno"><input type="text" id="txtPalabra" name="txtPalabra" onkeyup="$('btnBuscar').click();"></td>
                <td>
                    <input type="button" class="noprint" id="btnBuscar" onclick="xajax_buscarValeRecepcion(xajax.getFormValues('frmPresupuesto'),xajax.getFormValues('frmBuscarVale'));" value="Buscar"/>
                    <input type="button" class="noprint" onclick="document.forms['frmBuscarVale'].reset(); $('btnBuscar').click();" value="Ver Todo"/>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
    	<td id="tdListado"></td>
    </tr>
    <tr>
    	<td align="right">
	        <hr/>
            <input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar"/>
        </td>
    </tr>
    </table>
	
    <!-- TABLA DE PAQUETES -->     
    <table id="tblGeneralPaquetes" cellpadding="0" border="0" cellspacing="0" >
    <tr>
     	<td>
        <form id="frmBuscarPaquete" name="frmBuscarPaquete" style="margin:0" onsubmit="return false;">
            <table border="0" id="tblBuscarPaquete" style="display:none" width="100%">
            <tr align="left">
            	<td width="86%"></td>
                <td align="right" class="tituloCampo" width="14%">Criterio:</td>
                <td><input type="text" id="txtDescripcionBusq" name="txtDescripcionBusq" onkeyup="$('btnBuscarPaquete').click();"></td>
                <td><input type="button" id="btnBuscarPaquete" name="btnBuscarPaquete" onclick="xajax_buscarPaquete(xajax.getFormValues('frmBuscarPaquete'));" value="Buscar"></td>
            </tr>
            </table>
        </form>
            
        <form id="frmDatosPaquete" name="frmDatosPaquete" style="margin:0">
            <input type="hidden" id="txtCodigoPaquete" name="txtCodigoPaquete" readonly="readonly"/>
            <input type="hidden" id="txtDescripcionPaquete" name="txtDescripcionPaquete" readonly="readonly"/>
            <input type="hidden" id="hddEscogioPaquete" name="hddEscogioPaquete" readonly="readonly"/>
            <table border="0" width="1000">
			<tr>
                <td id="tdListadoPaquetes"></td>
            </tr>
            <tr id="tblListadoTemparioPorPaquete" style="display:none">
                <td>
                    <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="tituloPaginaServicios" id="tdEncabPaquete"></td>
                    </tr>
                    <tr class="tituloColumna">
                        <td align="center" class="tituloCampo">Mano de Obra</td>
                    </tr>
                    <tr>
                    	<td id="tdListadoTempario"></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr id="tblListadoRepuestosPorPaquete" style="display:none">
                <td>
                	<table border="0" width="100%">
                    <tr>
                        <td id="tdListadoRepuestos"></td>
                    </tr>
                    <tr>
                    	<td>
                    		<table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                            <tr>
                                <td width="25"><img src="../img/iconos/ico_info2.gif" width="25" alt="imagen"></td>
                                <td align="center">
                                    <table>
                                    <tr>
                                        <td><img src="../img/iconos/ico_aceptar.gif" alt="imagen"></td>
                                        <td>Art&iacute;culo con Disponibilidad Suficiente</td>
                                        <td><img src="../img/iconos/ico_alerta.gif" alt="imagen"></td>
                                        <td>Art&iacute;culo con Poca Disponibilidad</td>
                                        <td><img src="../img/iconos/ico_error.gif" alt="imagen"></td>
                                        <td>Art&iacute;culo sin Disponibilidad</td>
                                    </tr>
                                    </table>
                                    <table>
                                    <tr>
                                        <td><img src="../img/iconos/50.png" alt="imagen"></td>
                                        <td>Sin Precio Asignado</td>
                                        <td><img src="../img/iconos/e_icon.png" alt="imagen"></td>
                                        <td>Exento de Impuesto</td>
                                    </tr>
                                    </table>
                                </td>
                            </tr>
                            </table>
                            <input type="hidden" id="hddRepAproXpaq" name="hddRepAproXpaq" readonly="readonly"/>
                            <input type="hidden" id="hddArticuloSinDisponibilidad" name="hddArticuloSinDisponibilidad"/>
                            <input type="hidden" id="hddArtEnPaqSinPrecio" name="hddArtEnPaqSinPrecio"/>
                            <input type="hidden" id="hddTempEnPaqSinPrecio" name="hddTempEnPaqSinPrecio"/>
                            <input type="hidden" id="hddArtNoDispPaquete" name="hddArtNoDispPaquete"/>
                            <input type="hidden" id="hddObjRepuestoPaq" name="hddObjRepuestoPaq"/>
                            <input type="hidden" id="hddObjTemparioPaq" name="hddObjTemparioPaq"/>
						</td>
                    </tr>
                    <tr id="trPieTotalPaq" style="display:none">
                        <td>
                            <table align="right" border="0">                
                            <tr align="left">
                                <td align="right" class="tituloColumna" width="140">M.O Aprob.: <input type="hidden" id="numeroPaqueteEditar" /><input type="hidden" id="eliminacionRepuestoPaquete" /> </td>
                                <td><input type="text" id="txtNroManoObraAprobPaq" name="txtNroManoObraAprobPaq" readonly="readonly" size="16" style="text-align:right"></td>
                                <td align="right" class="tituloColumna" width="140">Repuesto(s) Aprob.:</td>
                                <td><input type="text" id="txtNroRepuestoAprobPaq" name="txtNroRepuestoAprobPaq" readonly="readonly" size="16" style="text-align:right"></td>
                                <td align="right" class="tituloColumna" width="140">Total Aprob.:</td>
                                <td><input type="text" id="txtTotalItemAprobPaq" name="txtTotalItemAprobPaq" readonly="readonly" size="16" style="text-align:right"></td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloColumna">Total M.O:</td>
                                <td>
                                	<input type="text" id="txtTotalManoObraPaq" name="txtTotalManoObraPaq" readonly="readonly" size="16" style="text-align:right"/>
                                    <input type="hidden" id="hddManObraAproXpaq" name="hddManObraAproXpaq" readonly="readonly"/>
								</td>
                                <td align="right" class="tituloColumna">Total Repuestos:</td>
                                <td>
                                    <input type="text" id="txtTotalRepPaq" name="txtTotalRepPaq" readonly="readonly" size="16" style="text-align:right">
                                    <input type="hidden" id="hddTotalArtExento" name="hddTotalArtExento" readonly="readonly"/>
                                    <input type="hidden" id="hddTotalArtConIva" name="hddTotalArtConIva" readonly="readonly"/>
                                    
                                    <input type="hidden" id="idIvasRepuestosPaquete" name="idIvasRepuestosPaquete" readonly="readonly"/>
                                    <input type="hidden" id="montoIvasRepuestosPaquete" name="montoIvasRepuestosPaquete" readonly="readonly"/>
                                    <input type="hidden" id="porcentajesIvasRepuestosPaquete" name="porcentajesIvasRepuestosPaquete" readonly="readonly"/>
                                    
				</td>
                                <td align="right" class="tituloColumna">Total Paquete:</td>
                                <td>
                                    <input type="text" id="txtPrecioPaquete" name="txtPrecioPaquete" readonly="readonly" size="16" style="text-align:right"/>
                                    <input type="hidden" id="txtNumeroSolicitud" name="txtNumeroSolicitud" readonly="readonly"/>
                                </td>
							</tr>
							</table>
						</td>
                    </tr>
                    <tr>
                        <td id="tblLeyendaAccAlmacen" style="display:none">
                            <table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                            <tr>
                                <td width="25"><img src="../img/iconos/ico_info2.gif" width="25" alt="imagen"></td>
                                <td align="center">
                                    <table>
                                    <tr>
                                        <td><img src="../img/iconos/ico_aceptar_azul.png" alt="imagen"></td>
                                        <td>Agregar Almacen</td>
                                        <td>&nbsp;</td>
                                        <td><img src="../img/iconos/ico_quitar.gif" alt="imagen"></td>
                                        <td>Eliminar Almacen</td>
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
                    <input type="button" id="btnGuardarAlmacenesPaquete" onclick="xajax_actualizarSolicitud(xajax.getFormValues('frmTotalPresupuesto'), xajax.getFormValues('frmDatosPaquete'));" style="display:none" value="Guardar"/>
                    
                    <input type="button" id="btnAsignarPaquete" onclick="this.disabled = true; validarPaquete();" value="Aceptar"/>
                    <input type="button" id="btnCancelarDivPpalPaq" onclick="$('divFlotante2').style.display='none'; $('divFlotante').style.display='none';" value="Cancelar"/>
                    <input type="button" id="btnCancelarDivSecPaq" onclick="
                    $('tblBuscarPaquete').style.display = '';
                    $('tdListadoPaquetes').style.display = '';
                    $('tblListadoTemparioPorPaquete').style.display = 'none';
                    $('tblListadoRepuestosPorPaquete').style.display = 'none';
                    
                    $('btnAsignarPaquete').style.display = 'none';
                    $('btnCancelarDivPpalPaq').style.display = '';
                    $('btnCancelarDivSecPaq').style.display = 'none';" value="Cancelar"/>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    </table>
	
    <table border="0" id="tblListadoTempario" style="display:none" width="1000">
    <tr>
        <td>
        <form id="frmBuscarTempario" name="frmBuscarTempario" style="margin:0" onsubmit=" return false;">
            <table align="right" border="0" id="tblBusquedaTempario">
            <tr align="left">
                <td align="right" class="tituloCampo" width="100">Secci&oacute;n:</td>
                <td id="tdListSeccionTemp">
                    <select id="lstSeccionTemp" name="lstSeccionTemp">
                        <option>[ Todos ]</option>
                    </select>
                </td>
                <td align="right" class="tituloCampo" width="100">Subsecci&oacute;n:</td>
                <td id="tdListSubseccionTemp">
                    <select id="lstSubseccionTemp" name="lstSubseccionTemp">
                        <option>[ Todos ]</option>
                    </select>
                </td>
			</tr>
            <tr>
            	<td></td>
            	<td></td>
                <td align="right" class="tituloCampo">Criterio:</td>
                <td><input type="text" id="txtCriterioTemp" name="txtCriterioTemp"></td>
                <td>
                	<input type="submit" id="btnBuscarTempario" name="btnBuscarTempario" onclick="xajax_buscarTempario(xajax.getFormValues('frmBuscarTempario'),xajax.getFormValues('frmTotalPresupuesto'));" value="Buscar">
				</td>
            </tr>
            </table>
		</form>
        </td>
    </tr>
	<tr>
        <td>
		<form id="frmListadoTempario" name="frmListadoTempario" style="margin:0">
			<input type="hidden" name="hddEscogioTempario" id="hddEscogioTempario" style="display:none" readonly="readonly"  />
        	<div id="tdListadoTemparioPorUnidad" style="width:100%"></div>    
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmDatosTempario" name="frmDatosTempario" style="margin:0" onsubmit="return false;">
            <table border="0" width="100%">
            <tr>
            	<td width="10%"></td>
            	<td width="20%"></td>
            	<td width="10%"></td>
            	<td width="30%"></td>
            	<td width="10%"></td>
                <td width="20%"></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Código:</td>
                <td> 
                    <input type="text" id="txtCodigoTemp" name="txtCodigoTemp" readonly="readonly" size="25"/>
                    <input type="hidden" id="hddIdTemp" name="hddIdTemp" readonly="readonly"/>  
                    <input type="hidden" id="hddIdDetTemp" name="hddIdDetTemp" readonly="readonly"/> 
                    <!-- hddIdDetTemp NO LO ESTOY UTILIZANDO -->
				</td>
                <td colspan="2" rowspan="3" valign="top" width="50%"><textarea id="txtDescripcionTemp" name="txtDescripcionTemp" cols="54" rows="3" readonly="readonly"></textarea></td>
                <td align="right" class="tituloCampo">Secci&oacute;n:</td>
                <td>
                    <input type="text" name="txtSeccionTempario" id="txtSeccionTempario" readonly="readonly" size="25"/>
                    <input type="hidden" name="hddSeccionTempario" id="hddSeccionTempario"/>
				</td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Operador:</td>
                <td>
                    <input type="text" id="txtDescripcionOperador" name="txtDescripcionOperador" readonly="readonly"/>
                    <input type="hidden" id="txtOperador" name="txtOperador" readonly="readonly"/>
                    <input type="hidden" id="hddOrigenTempario" name="hddOrigenTempario"  value=""/>
				</td>
                <td align="right" class="tituloCampo">Subsecci&oacute;n:</td>
                <td>
                    <input type="text" name="txtSubseccionTempario" id="txtSubseccionTempario" readonly="readonly" size="25"/>
                    <input type="hidden" name="hddIdSubseccionTempario" id="hddIdSubseccionTempario"/>
                </td>
			</tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Modo:</td>
                <td>
                    <input type="text" id="txtModoTemp" name="txtModoTemp" readonly="readonly"/>
                    <input type="hidden" id="txtIdModoTemp" name="txtIdModoTemp" readonly="readonly"/>
				</td>
                <td></td>
                <td></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Importe:</td>
                <td>
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="right">
                            <input type="text" id="txtPrecioTemp" name="txtPrecioTemp" onkeypress="return numerosPunto(event);" readonly="readonly" size="12" style="text-align:right"/>
                            <input type="hidden" id="hddIdPrecioTemp" name="hddIdPrecioTemp" readonly="readonly"/>
                            <input type="hidden" id="txtPrecio" name="txtPrecio" readonly="readonly"/>
						</td>
                        <td>&nbsp;</td>
                        <td id="tdDesbloquearPrecioTemp"></td>
					</tr>
                    </table>
                </td>
                <td align="right" class="tituloCampo" id="tdMecanico"><span class="textoRojoNegrita">*</span>Mecanico:</td>
                <td id="tdlstMecanico">
                    <select id="lstMecanico" name="lstMecanico">
                        <option value="-1">[ Seleccione ]</option>
                    </select>
				</td>
            </tr>
            <tr>
            	<td align="right" colspan="6">
                    <hr/>
                    <input type="submit" id="btnAsignarTemp" onclick="this.disabled = true; validarTempario();" value="Aceptar"/>
                    <input type="button" onclick="$('divFlotante2').style.display='none'; $('divFlotante').style.display='none';" value="Cerrar"/>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    </table>
	
    <table border="0" id="tblArticulo" style="display:none" width="1050">
    <tr>
    	<td>
        <form id="frmBuscarArticulo" name="frmBuscarArticulo" style="margin:0" onsubmit="return false;">
        	<table border="0" width="100%">
            <tr>
            	<td align="right" class="tituloCampo" width="12%">Buscar por:</td>
                <td width="50%">
                	<table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                    	<td><input type="radio" id="rbtBuscarMarca" name="rbtBuscar" value="1"/> Marca</td>
                    	<td><input type="radio" id="rbtBuscarTipoArticulo" name="rbtBuscar" value="2"/> Tipo Articulo</td>
                    	<td><input type="radio" id="rbtBuscarSeccion" name="rbtBuscar" value="3"/> Sección</td>
                    	<td><input type="radio" id="rbtBuscarSubSeccion" name="rbtBuscar" value="4"/> Sub-Sección</td>
                    	<td><input type="radio" id="rbtBuscarDescripcion" name="rbtBuscar" checked="checked" value="5"/> Descripcion</td>
                        <td><input type="radio" id="rbtBuscarCodBarra" name="rbtBuscar" value="6"/> Cód. Barra</td>
					</tr>
                    </table>
                </td>
                <td align="right" class="tituloCampo" width="12%">Criterio:</td>
                <td width="26%"><input type="text" id="txtDescripcionBusq" name="txtDescripcionBusq"></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo">Código:</td>
                <td id="tdCodigoArt"></td>
                <td align="right" colspan="2">
                	<input type="submit" id="btnBuscarArticulo" name="btnBuscarArticulo" onclick="xajax_buscarArticulo(xajax.getFormValues('frmBuscarArticulo'), xajax.getFormValues('frmPresupuesto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalPresupuesto'));" value="Buscar"/>
                    <input type="button" onclick="document.forms['frmBuscarArticulo'].reset(); $('btnBuscarArticulo').click();" value="Limpiar"/>
				</td>
			</tr>
			</table>
		</form>
		</td>
    </tr>
    <tr>
    	<td id="tdListadoArticulos"></td>
    </tr>
    <tr>
    	<td>
        	<hr/>
        <form id="frmDatosArticulo" name="frmDatosArticulo" style="margin:0" onsubmit="return false;">	
        	<input type="hidden" id="hddNumeroArt" name="hddNumeroArt" readonly="readonly"/>
        	<table border="0" width="100%">
            <tr>
                <td width="10%"></td>
                <td width="20%"></td>
                <td width="48%"></td>
                <td width="12%"></td>
                <td width="10%"></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Código:</td>
                <td>
                    <input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa" readonly="readonly"/>
                    <input type="hidden" id="hddIdArt" name="hddIdArt" readonly="readonly"/>
                    <input type="text" id="txtCodigoArt" name="txtCodigoArt" readonly="readonly" size="25"/>
                </td>
                <td rowspan="3" valign="top"><textarea id="txtDescripcionArt" name="txtDescripcionArt" cols="70" rows="3" readonly="readonly"></textarea></td>
                <td align="right" class="tituloCampo">Fecha Ult. Compra:</td>
                <td><input type="text" id="txtFechaUltCompraArt" name="txtFechaUltCompraArt" readonly="readonly" size="10" style="text-align:center;"></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo">Sección:</td>
                <td><input type="text" id="txtSeccionArt" name="txtSeccionArt" readonly="readonly" size="25"></td>
                <td align="right" class="tituloCampo">Fecha Ult. Venta:</td>
                <td><input type="text" id="txtFechaUltVentaArt" name="txtFechaUltVentaArt" readonly="readonly" size="10" style="text-align:center;"></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo">Tipo de Pieza:</td>
                <td><input type="text" id="txtTipoPiezaArt" name="txtTipoPiezaArt" readonly="readonly" size="25"></td>
                <td align="right" class="tituloCampo">Disponible:</td>
                <td><input type="text" id="txtCantDisponible" name="txtCantDisponible" readonly="readonly" size="10" style="text-align:right;"></td>
            </tr>
            </table>
            
            <table border="0" width="100%">
            <tr>
            	<td class="divMsjAlerta" colspan="5" id="tdMsjArticulo" style="display:none"></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cantidad:</td>
                <td>
					<table cellpadding="0" cellspacing="0">
					<tr>
                		<td><input type="text" id="txtCantidadArt" name="txtCantidadArt" maxlength="6" onkeypress="return validarSoloNumeros(event);" size="12" style="text-align:right;"></td>
                        <td>&nbsp;</td>
                    	<td><input type="text" id="txtUnidadArt" name="txtUnidadArt" readonly="readonly" size="15"></td>
					</tr>
                    </table>
				</td>
                <td class="noRomper" rowspan="1" width="28%">
                	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                    <tr>
                        <td width="25"><img src="../img/iconos/ico_info.gif" width="25"></td>
                        <td align="center">
                            <table>
                            <tr>
                                <td><img src="../img/iconos/ico_aceptar.gif"></td>
                                <td>Disponibilidad Suficiente</td>
                                <td><img src="../img/iconos/ico_alerta.gif"></td>
                                <td>Poca Disponibilidad</td>
                                <td><img src="../img/iconos/ico_error.gif"></td>
                                <td>Sin Disponibilidad</td>
                            </tr>
                            </table>
                        </td>
                    </tr>
                    </table>
                </td>
			</tr>
            <tr>
                <td align="right" class="tituloCampo" width="8%"><span class="textoRojoNegrita">*</span>Precio:</td>
                <td width="36%">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                    	<td id="tdlstPrecioArt">
                            <select id="lstPrecioArt" name="lstPrecioArt">
                                <option value="-1">[ Seleccione ]</option>
                            </select>
						</td>
                        <td>&nbsp;</td>
                        <td align="right">
                        	<input type="hidden" id="hddPrecioArtAsig" name="hddPrecioArtAsig" readonly="readonly"/>
                            <input type="hidden" id="hddBajarPrecio" name="hddBajarPrecio" readonly="readonly"/>
                            <input type="hidden" id="hddIdArtPrecioRepuesto" name="hddIdArtPrecioRepuesto" readonly="readonly"/>
                            <input type="hidden" id="hddCostoArtRepuesto" name="hddCostoArtRepuesto" readonly="readonly"/>
                        	<input type="text" id="txtPrecioArtRepuesto" name="txtPrecioArtRepuesto" maxlength="17" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" size="12" style="text-align:right"/>
						</td>
                        <td>&nbsp;</td>
                        <td id="tdDesbloquearPrecio"></td>
					</tr>
                    </table>
                </td>
                <!-- <td align="right" class="tituloCampo" width="8%"><span class="textoRojoNegrita">*AQUI</span><?php //echo nombreIva(1); ?></td> -->
                <td width="20%">
                    <table>
                        <tr id="impuestoPorArticulo" class="noRomper">
                        </tr>
                    </table>
<!--                    <input type="hidden" id="hddIdIvaRepuesto" name="hddIdIvaRepuesto" readonly="readonly"/>
                    <input type="text" id="txtIvaRepuesto" name="txtIvaRepuesto" readonly="readonly" size="12" style="text-align:right"/>-->
                </td>
            </tr>
            <tr>
                <td align="right" colspan="5">
                    <hr>
                    <input type="submit" id="btnInsertarArticulo" name="btnInsertarArticulo" onclick="this.disabled = true; validarFormArt();" value="Aceptar">
                    <input type="button" onclick="$('divFlotante2').style.display = 'none'; $('divFlotante').style.display = 'none';" value="Cerrar">
                </td>
            </tr>
            </table>
        </form>
		</td>
	</tr>
    </table>
    
<form id="frmDatosNotas" name="frmDatosNotas" style="margin:0">
    <table border="0" id="tblNotas" style="display:none;" width="450">
    <tr align="left">
        <td align="right" class="tituloCampo" width="25%">Descripción:</td>
        <td width="75%"><textarea id="txtDescripcionNota" name="txtDescripcionNota" onkeypress="return sinEnterTab(event);" cols="45" rows="5"></textarea></td>
    </tr>
    <tr align="left">
        <td align="right" class="tituloCampo">Precio:</td>
        <td><input type="text" id="txtPrecioNota" name="txtPrecioNota" onkeypress="return validarSoloNumerosReales(event);"></td>
    </tr>
    <tr>
        <td align="right" colspan="2">
        	<hr/>
            <input type="button" id="btnGuardarNota" name="btnGuardarNota" onclick="this.disabled = true; validarNota();" value="Aceptar"/>
            <input type="button" value="Cancelar" onclick="$('divFlotante2').style.display='none'; $('divFlotante').style.display='none';"/>
		</td>
    </tr>
    </table>
</form>
          
	<!-- TOT -->
	<table border="0" id="tblListadoTot" width="800">
    <tr>
    	<td>
        <form id="frmBuscarTot" name="frmBuscarTot" style="margin:0" onsubmit="return false;">
            <table border="0" width="1000">
            <tr align="left">
            	<td width="86%"></td>
                <td align="right" class="tituloCampo" width="14%">Criterio:</td>
                <td><input type="text" id="txtDescripcionBusq" name="txtDescripcionBusq" onkeyup="$('btnBuscarTot').click();"></td>
                <td><input type="button" id="btnBuscarTot" name="btnBuscarTot" onclick="xajax_buscarTot(xajax.getFormValues('frmBuscarTot'),xajax.getFormValues('frmTotalPresupuesto'));" value="Buscar"></td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListadoTot" name="frmListadoTot" style="margin:0">
            <input type="hidden" id="hddEscogioTot" name="hddEscogioTot" readonly="readonly" style="display:none"/>
        	<div id="tdListadoTot" style="width:100%"></div>
        </form>
        </td>  
    </tr>     
    <tr>
        <td>
        <form id="frmDatosTot" name="frmDatosTot" style="margin:0">
            <table border="0" width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Nro T.O.T:</td>
                <td width="38%"> 
                
                	<!-- Numero de tot agregado nuevo gregor -->
                    <input type="text" id="numeroTotMostrar" name="numeroTotMostrar" readonly="readonly"/>
                	<!-- id muestra numero del tot (ya estaba y se usa) lo oculte para mostrar el otro -->
                    <input type="hidden" id="txtNumeroTot" name="txtNumeroTot" readonly="readonly"/>
                    <!-- id tot (ya estaba y se usa) -->
                    <input type="text" id="hddIdTot" name="hddIdTot" readonly="readonly" style="display:none"/>  
                    <!-- hddIdDetTemp NO LO ESTOY UTILIZANDO -->
                </td>
                <td width="12%"></td>
                <td width="38%"></td>
            </tr>
            <tr align="left">
	            <td align="right" class="tituloCampo">Proveedor:</td>
            	<td colspan="3"><input type="text" id="txtProveedor" name="txtProveedor" readonly="readonly" size="70"></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Fecha:</td>
                <td><input type="text" id="txtFechaTot" name="txtFechaTot" readonly="readonly"></td>
                <td class="tituloCampo" align="right">Tipo Pago:</td>
                <td>
                    <input type="text" id="txtTipoPagoTot" name="txtTipoPagoTot" readonly="readonly"/>
                    <input type="hidden" id="hddIdPorcentajeTot" name="hddIdPorcentajeTot" readonly="readonly"/>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Monto:</td>
                <td><input type="text" id="txtMonto" name="txtMonto" readonly="readonly"></td>
                <td class="tituloCampo" align="right">Porcentaje:</td>
                <td><input type="text" id="txtPorcentaje" name="txtPorcentaje" onkeypress="if($('listadoPorcentajesTot').value == 'MANUALx') { return validarSoloNumerosReales(event); } else { return false; }" onkeyup="calcularTot();" readonly="readonly"/> 
                    <tagEspecial id="cambioPorcentajeTot"></tagEspecial>
                    <input type="hidden" id="idPrecioTotAccesorio" name="idPrecioTotAccesorio"/>
                </td>
                
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Monto Total</td>
                <td><input type="text" id="txtMontoTotalTot" name="txtMontoTotalTot" readonly="readonly"></td>
                <td></td>
                <td></td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td align="right">
            <hr/>
            <input type="button" id="btnAsignarTot" onclick="this.disabled = true; validarTot();" value="Aceptar"><!-- validarTot(); -->
            <input type="button" onclick="$('divFlotante2').style.display='none'; $('divFlotante').style.display='none';" value="Cancelar"/>
        </td>
    </tr>
    </table>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <table border="0" id="tblListados1" style="display:none" width="500px">
    <tr>
    	<td id="tdDescripcionArticulo">
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
    	<td align="right" id="tdBotonesDiv">
	        <hr/>
            <input type="button" id="" name="" onclick="$('divFlotante1').style.display='none';" value="Cancelar"/>
        </td>
    </tr>
    </table>

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
			<hr />
            <input type="button" onclick="validarFormArt();" value="Aceptar"/>
			<input type="button" onclick="$('divFlotante2').style.display='none';" value="Cancelar"/>
		</td>
    </tr>
    </table>
    
<form id="frmConfClave" name="frmConfClave" style="margin:0" onsubmit="return false;">
    <input type="hidden" name="hddAccionObj" id="hddAccionObj" readonly="readonly"/>
    <table border="0" id="tblClaveDescuento" style="display:none" width="350">
    <tr align="left">
        <td align="right" class="tituloCampo" width="32%">Clave:</td>
        <td width="68%"><input type="password" id="txtContrasenaAcceso" name="txtContrasenaAcceso" size="30"></td>
    </tr>
    <tr>
        <td align="right" colspan="2">
        	<hr/>
        	<input type="submit" name="btnAceptarClave" id="btnAceptarClave" value="Aceptar" onclick="validarFormClaveDescuento();"/>
            <input type="button" name="btnCancelarDivFlot" id="btnCancelarDivFlot" value="Cancelar" onclick="
            if ($('tdFlotanteTitulo2').innerHTML == 'Acceso Tipo Orden GARANTIA') {
                $('lstTipoOrden').value = '-1';
                $('lstTipoOrden').focus();
                $('divFlotante2').style.display='none';
                $('divFlotante').style.display='none';
            }
            
            if (($('tdFlotanteTitulo2').innerHTML == 'Acceso Duplicar M.O') || ($('tdFlotanteTitulo2').innerHTML == 'Acceso Duplicar Repuesto') || ($('tdFlotanteTitulo2').innerHTML == 'Acceso Agregar Vale Facturado')) {
                $('divFlotante2').style.display='none';
            } else {
                $('divFlotante2').style.display='none';
                $('divFlotante').style.display='none';
            }"/>
            <!--$('lstTipoOrden').value = '-1';-->
        </td>
    </tr>
	</table>
    
    <table border="0" id="tblMtosArticulos" style="display:none" width="500">
    <tr align="left">
        <td align="right" class="tituloCampo" width="30%">Código Artículo:</td>
        <td id="tdCodigoArticuloMto" width="70%">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="2" id="tdListadoEstadoMtoArt"></td>
    </tr>
    <tr>
        <td align="right" colspan="2">
        	<hr>
        	<input type="button" name="btnCancelarMtoArt" id="btnCancelarMtoArt" onclick="$('divFlotante2').style.display = 'none';" value="Cerrar"/>
		</td>
    </tr>
    </table>
    
    <table border="0" id="tblPorcentajeDescuento" style="display:none" width="250">
    <tr align="left">
        <td align="right" class="tituloCampo" width="40%">Descuentos:</td>
        <td id="tdLstTipoDescuentos" width="60%">
            <select id="lstTipoDescuentos" name="lstTipoDescuentos">
            	<option>[ Todos ]</option>
            </select>
            <script type="text/javascript">
            xajax_cargaLstDescuentos();
            </script>
        </td>
    </tr>
    <tr align="left">
        <td align="right" class="tituloCampo">Porcentaje:</td>
        <td id="tdLstTipoDescuentos">
            <input type="text" name="txtPorcDctoAdicional" id="txtPorcDctoAdicional" size="10" style="text-align:right" readonly="readonly"/>
            <input type="hidden" name="txtDescripcionPorcDctoAdicional" id="txtDescripcionPorcDctoAdicional" readonly="readonly"/>%
        </td>
    </tr>
    <tr>
        <td align="right" colspan="2">
        	<hr/>
            <input type="button" name="btnAceptarDcto" id="btnAceptarDcto" value="Aceptar" onclick="validarFormClaveDescuentoAdicional();"/>
            <input type="button" name="btnCancelarDcto" id="btnCancelarDcto" value="Cancelar" onclick="$('divFlotante2').style.display='none'; $('divFlotante').style.display='none';"/>
        </td>
    </tr>
    </table>
</form>
</div>


<div id="divFlotante3" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
    <div id="divFlotanteTitulo3" class="handle"><table><tr><td id="tdFlotanteTitulo3" width="100%">Calcular Descuentos</td></tr></table></div>
    
    <form id="frmCalcDescuentos" name="frmCalcDescuentos" style="margin:0" onsubmit="return false;">
        <table border="0" style="" width="100%">
        <tr align="left">
            <td align="right" class="tituloCampo noRomper" width="120">Modo Descuento:</td>
            <td id="tdLstModoDescuentos"  width="60%" colspan="6">
                <select onChange="seleccionModoDescuento(this.value);">
                    <option value="">SELECCIONE</option>
                    <option value="1">REPUESTOS</option>
                    <option value="2">MANOS DE OBRA</option>
                    <option value="3">ITEM INDIVIDUAL</option>
                </select>
            </td>
        </tr>
        <tr id="trCalculosDescuentos">
            <td align="right" class="tituloCampo noRomper" width="120">% Porcentaje:</td>
            <td>
                <input type="text" name="porcentajeCalculadora" id="porcentajeCalculadora" size="10" onkeypress="return validarSoloNumerosReales(event);" />
                <input type="radio" name="porcentaje_monto" id="radioPorcentaje" value="1" />
            </td>
        </tr>
        <tr id="trCantidadCalculadora">
            <td align="right" class="tituloCampo noRomper" width="120">Monto Descuento:</td>
            <td>
                <input type="text" name="cantidadCalculadora" id="cantidadCalculadora" size="10" onkeypress="return validarSoloNumerosReales(event);" />
                <input type="radio" name="porcentaje_monto" id="radioMonto" value="2" />
            </td>
        </tr>
        <tr>
            <td></td>
        </tr>
        <tr>
            <td><button>Pre-Calcular</button></td>
        </tr>        
        <tr id="trnuevoPorcentajeCalculadora">
            <td align="right" class="tituloCampo noRomper" width="120">% Porcentaje Orden:</td>
            <td><input type="text" name="nuevoPorcentajeCalculadora" readOnly = "readonly" id="nuevoPorcentajeCalculadora" size="10" /></td>
        </tr>
        <tr>
            <td colspan="2" id="notaDescuento"></td>            
        </tr>
        <tr>
            <td align="right" colspan="8">
                    <hr/>
                <input type="button" name="btnAceptarCalculadora" id="btnAceptarCalculadora" value="Aceptar" onclick=""/>
                <input type="button" name="btnCancelarCalculadora" id="btnCancelarCalculadora" value="Cerrar" onclick="$('divFlotante3').style.display='none'; "/>
            </td>
        </tr>
        </table>
    </form>
    
</div>




<div class="window" id="key_window2" style="z-index:100;top:-1000px;left:0px;max-width:400px;min-width:400px;visibility:hidden;border-color:#FEB300;">
	<div class="title" id="title_key_window2" style="background:#FEE8B3;color:#000000;">
		<div class="key_pass" id="key_title2" style="padding-left:24px;"></div>
	</div>
	<div class="content">
		<div class="nohover">
			<table class="insert_table">
			<tbody>
				<tr>
					<td width="30%" class="label">Clave:</td>
					<td class="field" style="text-align:center;">
						<input style="width:95%;border:0px;" type="password" name="key2" id="key2" maxlength="30" onkeypress=""/>
					</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align:right;padding:2px;">
						<span style="padding:2px;">
							<button onclick="verificarTipo($('key2').value);"><img alt="aceptar" src="<?php echo getUrl('img/iconos/select.png'); ?>" class="image_button"/>Aceptar</button>
						</span>
						<span style="padding:2px;">
							<button onclick="$('key_window2').style.display = 'none'; selectedOption('lstTipoOrden', $('hddTipoOrdenAnt').value);"><img alt="cerrar" src="<?php echo getUrl('img/iconos/delete.png'); ?>" class="image_button"/>Cancelar</button>
						</span>
					</td>
				</tr>
			</tbody>
			</table>
		</div>
	</div>
	<img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="$('key_window2').style.display='none'; selectedOption('lstTipoOrden', $('hddTipoOrdenAnt').value);" border="0"/>
</div>


<script type="text/javascript">
xajax_objetoCodigoDinamico('tdCodigoArt','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstTipoOrden();
function reconversionMonetaria(activo){
	if(activo == 0){
		alert("No está permitido reconvertir una orden con fecha posterior al 20-Agosto-2018");
		return false;			
	}else{
		var idOrdenReconversion = <?php echo $_GET['id'] ?>;
		var confirmacion = confirm("¿Desea realizar la reconversión de REPUESTOS Y MANO DE OBRA? Esta acción no se puede revertir");
		if (confirmacion == true){
			mensaje = xajax_reconversionArticulosTempario(idOrdenReconversion);
		}else{
			return false;
		}
	}

	return true;
}
bloquearForm();
</script>
<script language="javascript" type="text/javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
	
	var theHandle2 = document.getElementById("divFlotanteTitulo2");
	var theRoot2   = document.getElementById("divFlotante2");
	Drag.init(theHandle2, theRoot2);
	
	var theHandle3 = document.getElementById("divFlotanteTitulo3");
	var theRoot3   = document.getElementById("divFlotante3");
	Drag.init(theHandle3, theRoot3);
	
	var theHandle3 = document.getElementById("title_key_window2");
	var theRoot3   = document.getElementById("key_window2");
	Drag.init(theHandle3, theRoot3);
	
	<?php 
	if(isset($_GET['idv'])){
		
		$queryConfig403 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
			INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
		WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
			valTpDato($_GET['ide'], "int"));
		$rsConfig403 = mysql_query($queryConfig403);
		if (!$rsConfig403) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$totalRowsConfig403 = mysql_num_rows($rsConfig403);
		$rowConfig403 = mysql_fetch_assoc($rsConfig403);
		if($rowConfig403['valor'] == NULL){
			//$rowConfig403['valor'] = 1; //por defecto venezuela 1
			die("No se ha configurado formato de cheque. 403");
		}
		
		if($rowConfig403['valor'] == 3){//puerto rico
			echo "verVentana('sa_vale_fallas.php?id=".$_GET['idv']."', 700, 800);";
		}
		
		echo "setestaticpopup('sa_vale_recepcion_imprimir.php?view=print&id=".$_GET['idv']."', 'popUp', 900, 700);";
	}
	
		function fechaComun($fecha,$soloFecha = false){
			
			if($fecha != NULL && $fecha != 0 && $fecha != "" && $fecha != " " && $fecha != "31-12-1969"){
			
				if($soloFecha){
					$fechaConvertida = DATE("d-m-Y",strtotime($fecha));
				}else{
					$fechaConvertida = DATE("d-m-Y h:i:s a",strtotime($fecha));
				}
			
			}else{
				$fechaConvertida = "";
			}
			
			return $fechaConvertida;
		}
                
                
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
			
</script>