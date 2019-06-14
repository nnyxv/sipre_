<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("ga_orden_compra_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_ga_orden_compra_form.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Compras - Orden de Compra</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCompras.css">
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <!--LIBRERIA PARA COLOCAR CALENDARIOS EN LOS CAMPOS-->
    <link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
	<!--LIBRERIA PARA COLOCAR MASCARAS EN LOS CAMPOS-->
    <script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>

    <script>
	function abrirDivFlotante(abre,nomObjeto){
		switch(abre){
			case "ListProveedores"://list proveedores
				byId('tdFlotanteTitulo').innerHTML = "Agregar Proveedor";
				document.forms['frmBuscarProveedor'].reset();
				byId('txtCriterioBuscarProveedor').focus();
				byId('txtCriterioBuscarProveedor').select();
				xajax_listaProveedor(0,"","","");
					break;	
			case "EditarArt"://editar
				document.forms['frmDatosArticulo'].reset();
				byId('tdFlotanteTitulo2').innerHTML = "Editar Articulo";
				xajax_eliminarImpuesto(xajax.getFormValues('frmDatosArticulo')); //elimianro todo				
					break;	
			case "MostrarImpuesto":
				document.forms['frmImpuesto'].reset();
				byId('tdFlotanteTitulo3').innerHTML = "Lista de Impuesto";
				byId('btsAceptarImpuestoBloque').style.display = 'none';
					break;
			case "MostrarImpuestoBloque":
				xajax_listImpuesto(0,'iva','ASC','impuestoBloque');
				document.forms['frmImpuesto'].reset();
				byId('tdFlotanteTitulo3').innerHTML = "Lista de Impuesto";
				byId('btsAceptarImpuestoBloque').style.display = '';
					break;
			case "MostrarCliente":
				byId('tdFlotanteTitulo4').innerHTML = "Lista de Cliente";
					xajax_listaCliente(0, "nombre_cliente", "ASC", "<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>");
						break;
			case "ListaEmpleadoContacto": 
				byId('tdFlotanteTitulo5').innerHTML = "Lista de Empleado de Contacto";
				xajax_listaEmpleado(0, "", "ASC", "<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>||Contacto");
				byId('txtCriterioBuscarEmpleado').focus();
				document.forms['frmBuscarEmpleado'].reset();
				document.getElementById('txtBuscarEmpleado').value = 'Contacto';
						break;
			case "ListaEmpleadoResponsable": 
				byId('tdFlotanteTitulo5').innerHTML = "Lista de Empleado de Responsable";
				xajax_listaEmpleado(0, "", "ASC", "<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>||Responsable");
				byId('txtCriterioBuscarEmpleado').focus();
				document.forms['frmBuscarEmpleado'].reset();
				document.getElementById('txtBuscarEmpleado').value = 'Responsable';
						break;
		}
	openImg(nomObjeto);
	}

	function seleccionarTodosCheckbox(idObj,clase){
		if ($('#'+idObj).get(0).checked == true){
			$('.'+clase).each(function() { 
				this.checked = true;    //quta el chek
			});
		} else {
			$('.'+clase).each(function() { 
				this.checked = false;    //chek
			});
		}
	}
	
	function RecorrerForm(nameFrm,accion,arrayBtn){ 
		var frm = document.getElementById(nameFrm);
		var sAux= "";
		for (i=0; i < frm.elements.length; i++)	{// RECORRE LOS ELEMENTOS DEL FROM
			if(frm.elements[i].type == 'button' || frm.elements[i].type == 'submit'){// SI SON DE TIPO BUTTON Y SUBMIT 
				sAux = frm.elements[i].id;
				if(arrayBtn != "" && arrayBtn != null){// PARA LOS BOTONOES QUE NO DEBE HACER NINGUNA ACCION
					for(a = 0; a < arrayBtn.length; a++){
						if(sAux != arrayBtn[a]){
							document.getElementById(sAux).disabled = accion; //ACCION = TRUE DESAHABILITA; ACCION = FALSE HABILITA; 
						}
					}
				}else{
					document.getElementById(sAux).disabled = accion; //ACCION = TRUE DESAHABILITA; ACCION = FALSE HABILITA;
				}
			}
		}	
	}
	
	function validarFrmDatosArticulo() {
		if (validarCampo('txtCodigoArt','t','') == true
		&& validarCampo('txtCantidadArt','t','') == true
		&& validarCampo('txtCostoArt','t','') == true) {
			
			if (byId('rbtTipoArtCliente').checked == true
			&& validarCampo('txtNombreClienteArt','t','') != true) {
				alert("Los campos señalados en rojo son requeridos");
				return false;
			} 
		
		RecorrerForm('frmDatosArticulo',true);
		RecorrerForm('frmPedido',true);
		RecorrerForm('frmListaArticulo',true);
		RecorrerForm('frmTotalDcto',true);
		RecorrerForm('frmbtn',true);
		
		xajax_editarArticulo(xajax.getFormValues('frmDatosArticulo'), xajax.getFormValues('frmTotalOrden'));
		} else {
			validarCampo('txtCodigoArt','t','');
			validarCampo('txtCantidadArt','t','');
			validarCampo('txtCostoArt','t','');
			
			if (byId('rbtTipoArtCliente').checked == true){
				validarCampo('txtNombreClienteArt','t','');
			}
				
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmDcto() {
		
		RecorrerForm('frmPedido',true);
		RecorrerForm('frmListaArticulo',true);
		RecorrerForm('frmTotalDcto',true);
		RecorrerForm('frmbtn',true);		
		if (validarCampo('txtIdProv','t','') == true
		&& validarCampo('textIdContacto','t','') == true
		&& validarCampo('textNombreContacto','t','') == true
		&& validarCampo('textIdResponsable','t','') == true
		&& validarCampo('textNombreResponsable','t','') == true
		&& validarCampo('txtFechaEntrega','t','fecha') == true
		&& validarCampo('lstTipoTransporte','t','lista') == true
		&& validarCampo('txtDescuento','t','numPositivo') == true) {
			xajax_guardarOrden(xajax.getFormValues('frmPedido'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		} else {
			validarCampo('txtIdProv','t','');
			validarCampo('textIdContacto','t','');
			validarCampo('textNombreContacto','t','');
			validarCampo('textIdResponsable','t','');
			validarCampo('textNombreResponsable','t','')
			validarCampo('txtFechaEntrega','t','fecha');
			validarCampo('lstTipoTransporte','t','lista');
			validarCampo('txtDescuento','t','numPositivo');
			
			alert("Los campos señalados en rojo son requeridos");
			RecorrerForm('frmPedido',false);
			RecorrerForm('frmListaArticulo',false);
			RecorrerForm('frmTotalDcto',false,{0:"btnQuitarGasto",1:"btnAgregarGasto"});
			RecorrerForm('frmbtn',false);
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_compras.php"); ?></div>
    
<div id="divInfo" class="print">
    <table border="0" width="100%">
        <tr>
            <td class="tituloPaginaCompras">Orden de Compra</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>
                <form id="frmPedido" name="frmPedido" style="margin:0">
                <input type="hidden" name="txtIdUnidadCentroCosto" id="txtIdUnidadCentroCosto" readonly="readonly"/>
                    <table width="100%">
                        <tr>
                            <td align="left"><!--CONTINE LOS DATOS BASICOS DE LA ORD-->
                                <table border="0" width="100%">
                                    <tr>
                                        <td align="left" rowspan="3" width="70%"></td>
                                        <td align="right" class="tituloCampo" width="15%">Nro. Orden de Compra:</td>
                                        <td width="15%"><input type="text" id="txtIdOrdenCompra" name="txtIdOrdenCompra" readonly="readonly" size="20" style="text-align:center"/></td>
                                    </tr>
                                    <tr>
                                        <td align="right" class="tituloCampo">Fecha:</td>
                                        <td><input type="text" id="txtFechaOrdenCompra" name="txtFechaOrdenCompra" readonly="readonly" size="10" style="text-align:center"/></td>
                                    </tr>
                                    <tr>
                                        <td align="right" class="tituloCampo">Nro. Solicitud de Compra:</td>
                                        <td><input type="text" id="txtIdPedido" name="txtIdPedido" readonly="readonly" size="20" style="text-align:center"/></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td><!--CONTINE LOS DATOS PROVEEDOR-->
                            	<fieldset>
                                	<legend class="legend">Datos del Proveedor</legend>
                                    <table border="0" width="100%" >
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Razón Social:</td>
                                            <td colspan="3">
                                                <table cellpadding="0" cellspacing="0">
                                                    <tr>
                                                        <td><input type="text" id="txtIdProv" name="txtIdProv" readonly="readonly" size="6" style="text-align:right"/></td>
                                                        <td>
                                                            <a class="modalImg" id="aListProveed" rel="#divFlotante" 
                                                            onclick="abrirDivFlotante('ListProveedores',this);">
                                                                <button type="button" id="btnInsertarProv" name="btnInsertarProv" title="Listodo de Proveedres">
                                                                    <img src="../img/iconos/ico_pregunta.gif"/>
                                                                </button>
                                                            </a>
                                                        </td>
                                                        <td><input type="text" id="txtNombreProv" name="txtNombreProv" readonly="readonly" size="45"/></td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td align="right" class="tituloCampo"><?php echo $spanProvCxP; ?>:</td>
                                            <td><input type="text" id="txtRifProv" name="txtRifProv" readonly="readonly" size="16" style="text-align:right"/></td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo" width="12%">Persona Contacto:</td>
                                            <td width="24%"><input type="text" id="txtContactoProv" name="txtContactoProv" readonly="readonly" size="26"/></td>
                                            <td align="right" class="tituloCampo" width="12%">Cargo:</td>
                                            <td width="20%"><input type="text" id="txtCargoContactoProv" name="txtCargoContactoProv" readonly="readonly" size="26"/></td>
                                            <td align="right" class="tituloCampo" width="12%">Email:</td>
                                            <td width="20%"><input type="text" id="txtEmailContactoProv" name="txtEmailContactoProv" readonly="readonly" size="26"/></td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo" rowspan="2">Dirección:</td>
                                            <td colspan="3" rowspan="2"><textarea id="txtDireccionProv" name="txtDireccionProv" cols="60" readonly="readonly" rows="2"></textarea></td>
                                            <td align="right" class="tituloCampo">Teléfono:</td>
                                            <td><input type="text" id="txtTelefonosProv" name="txtTelefonosProv" readonly="readonly" size="12" style="text-align:center"/></td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Fax:</td>
                                            <td><input type="text" id="txtFaxProv" name="txtFaxProv" readonly="readonly" size="12" style="text-align:center"/></td>
                                        </tr>
                                    </table>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <td><!--CONTINE LOS DATOS DE LA COMPRA-->
                                <fieldset>
                                    <legend class="legend">Datos de la Compra</legend>
                                        <table border="0" width="100%">
                                            <tr align="left">
                                                <td align="right" class="tituloCampo">Factura a Nombre de:</td>
                                                <td colspan="3">
                                                    <table cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/></td>
                                                            <td>&nbsp;</td>
                                                            <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td align="right" class="tituloCampo"><?php echo $spanProvCxP; ?>:</td>
                                                <td><input type="text" id="txtRif" name="txtRif" readonly="readonly" size="16" style="text-align:right"/></td>
                                            </tr>
                                            <tr align="left">
                                                <td align="right" class="tituloCampo">Dirección:</td>
                                                <td colspan="3"><textarea cols="60" id="txtDireccion" name="txtDireccion" readonly="readonly" rows="2"></textarea></td>
                                                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo de Pago:</td>
                                                <td>
                                                    <input id="rbtTipoPagoCredito" name="rbtTipoPago" type="radio" value="0" checked="checked"/>Crédito
                                                    <input id="rbtTipoPagoContado" name="rbtTipoPago" type="radio" value="1"/>Contado
                                                </td>
                                            </tr>
                                            <tr align="left">
                                                <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Contacto:</td>
                                                <td id="" width="34%">
                                                	<table>
                                                    	<tr>
                                                        	<td><input id="textIdContacto" name="textIdContacto" type="text" size="5" class="inputHabilitado" style="text-align:center"/></td>
                                                            <td>
                                                            	<a id="AgregarCotnato" class="modalImg" rel="#divFlotante5" onclick="abrirDivFlotante('ListaEmpleadoContacto',this)">
                                                                    <button type="button" title="Agregar Contacto" id="btnAgregarContacto" name="btnAgregarContacto">
                                                                        <img src="../img/iconos/add.png" >
                                                                    </button>
                                                                </a>
                                                            </td>
                                                            <td><input id="textNombreContacto" name="textNombreContacto" type="text" size="30" class="inputHabilitado"/></td>
                                                        </tr>
                                                    </table>
                                                    <!--tdlstContacto
                                                    <select id="lstContacto" name="lstContacto">
                                                        <option value="-1">[ Seleccione ]</option>
                                                    </select>-->
                                                </td>
                                                <td align="right" class="tituloCampo" width="12%">Cargo:</td>
                                                <td width="20%"><input type="text" id="txtCargo" name="txtCargo" readonly="readonly" size="26"/></td>
                                                <td align="right" class="tituloCampo" width="12%">Email:</td>
                                                <td width="20%"><input type="text" id="txtEmail" name="txtEmail" readonly="readonly" size="26"/></td>
                                            </tr>
                                            <tr align="left">
                                                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Resp. Recepción:</td>
                                                <td id="">
                                                	<table>
                                                    	<tr>
                                                        	<td><input id="textIdResponsable" name="textIdResponsable" type="text" size="5" class="inputHabilitado" style="text-align:center"/></td>
                                                            <td>
                                                            	<a id="AgregarResponsable" class="modalImg" rel="#divFlotante5" onclick="abrirDivFlotante('ListaEmpleadoResponsable',this)">
                                                                    <button type="button" title="Agregar Responsable" id="btnAgregarResponsable" name="btnAgregarResponsable">
                                                                        <img src="../img/iconos/add.png" >
                                                                    </button>
                                                                </a>
                                                            </td>
                                                            <td><input id="textNombreResponsable" name="textNombreResponsable" type="text" size="30" class="inputHabilitado"/></td>
                                                        </tr>
                                                    </table>
                                                    <!--<select id="lstRespRecepcion" name="lstRespRecepcion">
                                                        <option value="-1">[ Seleccione ]</option>
                                                    </select>-->
                                                </td>
                                                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Entrega:</td>
                                                <td>
                                                    <input type="text" id="txtFechaEntrega" name="txtFechaEntrega" class="inputHabilitado" autocomplete="off" size="10" style="text-align:center"/>
                                                </td>
                                                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Transporte:</td>
                                                <td>
                                                    <select id="lstTipoTransporte" name="lstTipoTransporte" class="inputHabilitado" style="width:150px">
                                                        <option value="-1">[ Seleccione ]</option>
                                                        <option value="1">Propio</option>
                                                        <option value="2">Terceros</option>
                                                    </select>
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
        <tr>
            <td align="left"><!--LISTA DE ART AGREGADOS A LA ORDEN-->
                <form id="frmListaArticulo" name="frmListaArticulo" style="margin:0">
                <a class="modalImg" id="AgregarImpuBloque" onclick="abrirDivFlotante('MostrarImpuestoBloque',this);" rel="#divFlotante3">
                    <button type="button" id="btnImpuestoArt" name="btnImpuestoArt" style="cursor:default" title="Agregar Impuesto">
                        <table align="center" cellpadding="0" cellspacing="0">
                            <tr>
                                <td>&nbsp;</td>
                                <td><img src="../img/iconos/text_signature.png"/></td>
                                <td>&nbsp;</td>
                                <td>Impuesto</td>
                            </tr>
                        </table>
                    </button>
                </a>
                    <table border="0" width="100%">
                        <tr align="center" class="tituloColumna">
                            <td><input type="checkbox" id="cbxItm" onclick="seleccionarTodosCheckbox('cbxItm','cbxArt');"/></td>
                            <td width="4%">Nro.</td>
                            <td></td>
                            <td width="8%">Código</td>
                            <td width="44%">Descripción</td>
                            <td width="8%">Unidad</td>
                            <td width="8%">Cantidad</td>
                            <td width="8%">Costo Unit.</td>
                            <td width="8%">% Impuesto</td>
                            <td width="12%">Sub-Total</td>
                        </tr>
                    	<tr id="trItmPie"></tr>
                    </table>
                </form>
            </td>
        </tr>
        <tr>
            <td align="right">
                <form id="frmTotalDcto" name="frmTotalDcto" style="margin:0">
                <input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/>
                    <table border="0" width="100%">
                        <tr>
                            <td id="" valign="top" width="50%">
                                <fieldset>
                                <legend class="legend">Gastos</legend>
                                    <table width="100%" border="0">
                                        <tr>
                                            <td colspan="6"><!--BOTON AGREGAR GASTOS-->
                                                <a class="modalImg" id="AgregarGastos" rel="#divFlotante6" onclick="">
                                                    <button title="Agregar Gastos" type="button"  name="btnAgregarGasto" id="btnAgregarGasto">
                                                        <table cellspacing="0" cellpadding="0" align="center">
                                                            <tr>
                                                                <td>&nbsp;</td>
                                                                <td><img src="../img/iconos/add.png"></td>
                                                                <td>&nbsp;</td>
                                                                <td>Agregar</td>
                                                            </tr>
                                                        </table>
                                                    </button>
                                                </a>
                                            <!--BOTON DE QUITAR GASTOS-->
                                                <button title="Quitar Gastos" onclick="" name="btnQuitarGasto" id="btnQuitarGasto" type="button">
                                                    <table cellspacing="0" cellpadding="0" align="center">
                                                        <tr>
                                                            <td>&nbsp;</td>
                                                            <td><img src="../img/iconos/delete.png"></td>
                                                            <td>&nbsp;</td>
                                                            <td>Quitar</td>
                                                        </tr>
                                                    </table>
                                                </button>
                                            </td>
                                        </tr>
                                    <tr class="tituloColumna" align="center">
                                        <td><input id="checkGastoItemFactura" type="checkbox" onclick="seleccionarTodosCheckbox('checkGastoItemFactura','checkItemClaseGasto');"></td>
                                        <td>Descripcion Gasto</td>
                                        <td>% Gasto</td>
                                        <td>Monto Gasto</td>
                                        <td>Impuesto</td>
                                    </tr>
                                    <tr id="trItmPieGastos"></tr>
                                    <tr class="trResaltarTotal">
                                        <td colspan="3" class="tituloCampo" align="right">Total Gasto</td>
                                        <td> 
                                        <input id="txtTotalGasto" class="inputSinFondo" type="text" style="text-align:right" readonly="readonly" name="txtTotalGasto">
                                        </td>
                                        <td colspan="2"></td>
                                    </tr>
                                    <tr>
                                    <td class="divMsjInfo2" colspan="6">
                                    <table width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                    <td width="25"><img width="25" src="../img/iconos/ico_info.gif"></td>
                                    <td align="center">
                                    <table>
                                    
                                    <tr>
                                    <td><img src="../img/iconos/accept.png"></td>
                                    <td>Gastos que llevan impuesto</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/stop.png"></td>
                                    <td>No afecta cuenta por pagar</td>
                                    </tr>
                                    </table>
                                    </td>
                                    </tr>
                                    </table>
                                    </td>
                                    </tr>
                                    </table>
                                </fieldset>
                            </td><!--LISTADO DE GASTOS-->
                            <td valign="top" width="50%"><!--CALCULO DE LA ORDEN-->
                                <table width="100%">
                                    <tr align="right">
                                        <td class="tituloCampo" width="36%">Subtotal:</td>
                                        <td style="border-top:1px solid;" width="24%"></td>
                                        <td style="border-top:1px solid;" width="13%"></td>
                                        <td style="border-top:1px solid;" id="tdSubTotalMoneda" width="5%"></td>
                                        <td style="border-top:1px solid;" width="22%"><input type="text" id="txtSubTotal" name="txtSubTotal" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                                    </tr>
                                    <tr align="right">
                                        <td class="tituloCampo">Descuento:</td>
                                        <td></td>
                                        <td nowrap="nowrap">
                                            <input type="text" id="txtDescuento" name="txtDescuento" onfocus="
                                            if (byId('txtDescuento').value <= 0) {
                                            byId('txtDescuento').select();
                                            }" onkeypress="return validarSoloNumerosReales(event);" onkeyup="xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));" readonly="readonly" size="6" style="text-align:right"/>%
                                        </td>
                                        <td id="tdDescuentoMoneda"></td>
                                        <td><input type="text" id="txtSubTotalDescuento" name="txtSubTotalDescuento" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                                    </tr>
                                    <tr align="right">
                                        <td class="tituloCampo">Gastos Con Impuesto:</td>
                                        <td></td>
                                        <td></td>
                                        <td id="tdGastoConIvaMoneda"></td>
                                        <td><input type="text" id="txtGastosConIva" name="txtGastosConIva" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                                    </tr>
                                    <!--AQUI SE INSERTAN LAS FILAS PARA EL IMPUESTO-->
                                    <tr align="right" id="trGastosSinIva">
                                        <td class="tituloCampo">Gastos Sin Impuesto:</td>
                                        <td></td>
                                        <td></td>
                                        <td id="tdGastoSinIvaMoneda"></td>
                                        <td>
                                        <input type="text" id="txtGastosSinIva" name="txtGastosSinIva" class="inputSinFondo" readonly="readonly" style="text-align:right"/>
                                        </td>
                                    </tr>
                                    <tr id="trTotal" align="right" class="trResaltarTotal">
                                        <td class="tituloCampo">Total Orden Compra:</td>
                                        <td></td>
                                        <td></td>
                                        <td id="tdTotalRegistroMoneda"></td>
                                        <td><input type="text" id="txtTotalOrden" name="txtTotalOrden" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top"> <!--OTROS DATOS-->
                            	<fieldset>
                                	<legend class="legend">Otros Datos</legend>
                                    <table border="0" width="100%">
                                        <tr align="left">
                                            <td align="right" class="tituloCampo" width="28%">Según Cotización Nro.:</td>
                                            <td width="32%"><input type="text" id="txtCotizacion" name="txtCotizacion" size="20" style="text-align:center" class="inputHabilitado"/></td>
                                            <td align="right" class="tituloCampo" width="16%">Fecha:</td>
                                            <td width="24%"><input type="text" id="txtFechaCotizacion" name="txtFechaCotizacion" class="inputHabilitado" autocomplete="off" size="10" style="text-align:center"/></td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Condiciones de Pago:</td>
                                            <td colspan="3"><input type="text" id="txtCondicionesPago" name="txtCondicionesPago" size="26" class="inputHabilitado"/></td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Son:</td>
                                            <td colspan="3"><textarea id="txtMontoEnLetras" name="txtMontoEnLetras" cols="40" rows="2" readonly="readonly"></textarea></td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Observaciones:</td>
                                            <td colspan="3"><textarea id="txtObservaciones" name="txtObservaciones" cols="40" rows="2" class="inputHabilitado"></textarea></td>
                                        </tr>
                                    </table>
                                </fieldset>
                                
                            </td>
                            <td valign="top"> <!--DATOS DE APROVACION-->
                           		<fieldset>
                                	<legend class="legend">Datos de Aprobación</legend>
                                        <table border="0" width="100%" cellpadding="0" cellspacing="0">
                                        	<tr class="tituloColumna">
                                            	<td colspan="2"  align="center">Solicitud Preparada por</td>
                                            	<td colspan="2"  align="center">Fecha Preparación</td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" align="center">
                               <input type="text" id="hddIdEmpleadoPreparado" name="hddIdEmpleadoPreparado"  readonly="readonly" size="6" style="text-align:center; border:none"/>
                               <input type="text" id="txtNombreEmpleadoPreparado" name="txtNombreEmpleadoPreparado" readonly="readonly" size="25" style="text-align:center; border:none"/>
                                                </td>
                                                <td colspan="2" align="center">
                                <input type="text" id="txtFechaPreparado" name="txtFechaPreparado" readonly="readonly" size="10" style="text-align:center; border:none"/>
                                                </td>
                                            </tr>
                                            <tr class="tituloColumna">
                                            	<td colspan="2"  align="center">Orden Aprobado por</td>
                                            	<td colspan="2"  align="center">Fecha Aprobación</td>
                                            </tr>
                                            <tr align="left">
                                                <td colspan="2" align="center">
                                <input type="text" id="hddIdEmpleadoAprobado" name="hddIdEmpleadoAprobado" readonly="readonly" size="6" style="text-align:center; border:none"/>
                                <input type="text" id="txtNombreEmpleadoAprobado" name="txtNombreEmpleadoAprobado" readonly="readonly" size="25" style="text-align:center; border:none"/>
                                                </td>
                                                <td colspan="2" align="center"><input type="text" id="txtFechaAprobado" name="txtFechaAprobado" readonly="readonly" style="text-align:center; border:none" size="10"/></td>
                                            </tr>
                                        </table>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                </form>
            </td>
        </tr>
        <tr class="noprint">
            <td align="right"><hr>
            <form id="frmbtn" name="frmbtn">
                <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFrmDcto();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/select.png"/></td><td>&nbsp;</td><td>Aprobar</td></tr></table></button>
                <button type="button" id="btnCancelar" name="btnCancelar" onclick="window.open('ga_orden_compra_list.php','_self');" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_error.gif"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
            </form>
            </td>
        </tr>
    </table>
</div>
    
<div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>
<!--LISTADO DE PROVEEDORES-->
<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
    <div id="divFlotanteTitulo" class="handle">
        <table>
            <tr>
                <td id="tdFlotanteTitulo" width="100%"></td>
            </tr>
        </table>
    </div>
    <table border="0" id="tblListados" width="960">
        <tr id="trBuscarProveedor">
            <td>
                <form id="frmBuscarProveedor" name="frmBuscarProveedor" style="margin:0" onsubmit="return false;">
                    <table align="right">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="100">Criterio:</td>
                            <td><input type="text" id="txtCriterioBuscarProveedor" name="txtCriterioBuscarProveedor" class="inputHabilitado" onkeyup="byId('btnBuscarProveedor').click();"/></td>
                            <td><button type="button" id="btnBuscarProveedor" name="btnBuscarProveedor" onclick="xajax_buscarProveedor(xajax.getFormValues('frmBuscarProveedor'));">Buscar</button></td>
                            <td><button type="button" id="btnLimpiarProveedor" name="btnLimpiarProveedor" onclick="document.forms['frmBuscarProveedor'].reset(); byId('btnBuscarProveedor').click();">Limpiar</button></td>
                    	</tr>
                    </table>
                </form>
            </td>
        </tr>
        <tr>
            <td id="tdListado"></td>
        </tr>
        <tr>
            <td align="right"><hr>
                <button class="close" id="btnCerraLstProvee" name="btnCerraLstProvee" type="button" onclick="">Cerrar</button>
            </td>
        </tr>
    </table>
</div>

<!--EDITAR ART-->
<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
    <div id="divFlotanteTitulo2" class="handle">
        <table>
            <tr>
                <td id="tdFlotanteTitulo2" width="100%"></td>
            </tr>
        </table>
    </div>
    <table border="0" id="tblArticulo" width="960">
        <tr>
            <td>
                <form id="frmDatosArticulo" name="frmDatosArticulo" style="margin:0">
                <input type="hidden" id="hddNumeroArt" name="hddNumeroArt" />
                    <fieldset> <!--DESCRIPCION EL ART-->
                    	<legend class="legend">Datos del Articulo</legend>
                        <table border="0" width="100%">
                            <tr align="left">
                                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Código:</td>
                                <td>
                                    <input type="text" id="txtCodigoArt" name="txtCodigoArt" readonly="readonly" size="25"/>
                                    <input type="hidden" id="hddIdArt" name="hddIdArt" readonly="readonly"/>
                                </td>
                                <td align="center" class="tituloCampo">Descripcion del Articulo</td>
                                <td align="right" class="tituloCampo">Fecha Ult. Compra:</td>
                                <td>
                                <input type="text" id="txtFechaUltCompraArt" name="txtFechaUltCompraArt" readonly="readonly" size="10" style="text-align:center"/>
                                </td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo">Sección:</td>
                                <td><input type="text" id="txtSeccionArt" name="txtSeccionArt" readonly="readonly" size="25"/></td>
                                <td rowspan="2" valign="top" align="center">
                                	<textarea id="txtDescripcionArt" name="txtDescripcionArt" cols="60" rows="3" readonly="readonly"></textarea>
                                </td>
                                <td align="right" class="tituloCampo">Fecha Ult. Venta:</td>
                                <td><input type="text" id="txtFechaUltVentaArt" name="txtFechaUltVentaArt" readonly="readonly" size="10" style="text-align:center"/></td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo">Tipo de Pieza:</td>
                                <td><input type="text" id="txtTipoPiezaArt" name="txtTipoPiezaArt" readonly="readonly" size="25"/></td>
                                <td align="right" class="tituloCampo">Existencia:</td>
                                <td><input type="text" id="txtExistencia" name="txtExistencia" readonly="readonly" size="10"/></td>
                                
                            </tr>
                        </table>
                    </fieldset>
                    <table width="100%" border="0">
                        <tr>
                            <td valign="top"><!--CONTIENE LOS DATOS DEL PEDIDO-->
                            	<table border="0" width="100%">
                                    <tr align="left">
                                        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cantidad:</td>
                                        <td><input type="text" id="txtCantidadArt" name="txtCantidadArt" readonly="readonly" size="25"/></td>
                                    </tr>
                                    <tr align="left">
                                        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Costo:</td>
                                        <td><input type="text" id="txtCostoArt" name="txtCostoArt" readonly="readonly" size="10"/></td>
                                        <!--<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>% Impuesto:</td>
                                        <td id="tdlstIvaArt">
                                            <select id="lstIvaArt" name="lstIvaArt">
                                                <option value="-1">[ Seleccione ]</option>
                                            </select>
                                        </td>-->
                                    </tr>
                                    <tr align="left">
                                        <td align="right" class="tituloCampo">Tipo:</td>
                                        <td>
                                            <input id="rbtTipoArtReposicion" name="rbtTipoArt" onclick="byId('txtIdClienteArt').value = ''; byId('txtNombreClienteArt').value = ''; byId('btnInsertarClienteArt').style.display = 'none';" type="radio" value="0" checked="checked"/> Reposicion
                                        &nbsp;&nbsp;
                                            <input id="rbtTipoArtCliente" name="rbtTipoArt" onclick="byId('txtIdClienteArt').value = ''; byId('txtNombreClienteArt').value = ''; byId('btnInsertarClienteArt').style.display = '';" type="radio" value="1" /> Cliente
                                        </td>
                                        <td align="right" class="tituloCampo">Nombre:</td>
                                        <td colspan="5"> <!--CLIENTE  -->
                                            <table cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td>
                                                        <input type="text" id="txtIdClienteArt" name="txtIdClienteArt" readonly="readonly"  size="6" style="text-align:right"/>
                                                    </td>
                                                    <td>
                                                        <a class="modalImg" id="aListCliente" rel="#divFlotante4" onclick="abrirDivFlotante('MostrarCliente',this);">
                                                            <button type="button" id="btnInsertarClienteArt" name="btnInsertarClienteArt" title="Listar Cliente">
                                                                <img src="../img/iconos/ico_pregunta.gif"/>
                                                            </button>
                                                        </a>
                                                    </td>
                                                    <td><input type="text" id="txtNombreClienteArt" name="txtNombreClienteArt" readonly="readonly" size="25"/></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                    			</table>
                    		</td>
                            <td><!--CONTIENE LOS IMPUESTOS-->
                                <fieldset>
                                <legend class="legend">% Impuesto</legend>
                                    <table width="100%" cellpadding="0" cellspacing="0" align="center" border="0">
                                        <tr>
                                            <td  align="left" colspan="2"> <!--BOTON AGREGAR IMPUESTO-->
                                                <a class="modalImg" id="AgregarIpuesto" rel="#divFlotante3" onclick="abrirDivFlotante('MostrarImpuesto',this);">
                                                    <button id="btnAgregarImpuesto" name="btnAgregarImpuesto" type="button" title="Agregar Impuesto">
                                                        <table cellspacing="0" cellpadding="0" align="center">
                                                            <tr>
                                                                <td>&nbsp;</td>
                                                                <td><img src="../img/iconos/add.png"></td>
                                                                <td>&nbsp;</td>
                                                                <td>Agregar</td>
                                                            </tr>
                                                        </table>
                                                    </button>
                                                </a><!--/**/-->
                                                <button name="btnQuitarImpuesto" id="btnQuitarImpuesto" onclick="xajax_eliminarImpuesto(xajax.getFormValues('frmDatosArticulo'),1);" type="button" title="Quitar Impuesto">
                                                    <table cellspacing="0" cellpadding="0" align="center">
                                                        <tr>
                                                            <td>&nbsp;</td>
                                                            <td><img src="../img/iconos/delete.png"></td>
                                                            <td>&nbsp;</td>
                                                            <td>Eliminar</td>
                                                        </tr>
                                                    </table>
                                                </button>
                                            </td>
                                        </tr>
                                    <tr>
                                        <td colspan="2"><!--TABALA DONDE SE AGRAGAN LOS IMPUESTOS selecAllChecks(chkVal,idVal,form)-->
                                            <table border="0" id="" width="100%">                                       	
                                                <tr class="tituloColumna" align="center">
                                                    <td width="10%" align="center">
                                                    <input id="cbxItmsImpuesto" type="checkbox" onclick="seleccionarTodosCheckbox('cbxItmsImpuesto','cbxItmImpuesto');">
                                                    </td>
                                                    <td width="10%">Id</td>
                                                    <td>Impuesto</td>
                                                </tr>
                                                <tr id="trItemArtIva"></tr>
                                            </table> 
                                        </td>                                    
                                    </tr>
                                    <tr class="trResaltarTotal">
                                        <td align="right" class="tituloCampo" width="30%">Total Impuesto:</td>
                                        <td><input type="text" id="textTotaIva" name="textTotaIva" readonly="readonly" class="inputSinFondo" style="text-align:right; border:0px; color:#007F00" value=""/> </td>
                                    </tr>
                                    </table>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <td align="right" colspan="2"><hr>
                                <button type="button" id="btnAceptarEditarArt" name="btnAceptarEditarArt" onclick="validarFrmDatosArticulo();">Aceptar</button>
                                <button type="button" id="btnCancelarEditarArt" name="btnCancelarEditarArt" class="close" onclick="xajax_eliminarImpuesto(xajax.getFormValues('frmDatosArticulo'));">Cancelar</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </td>
        </tr>
    </table>
</div>

<!--LISTADO DE IMPUESTO-->
<div id="divFlotante3" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo3" class="handle"> <!--TITULO DEL LISTADO-->
    	<table width="100%">
            <tr>
                <td id="tdFlotanteTitulo3" width="100%" align="left"></td>
                <td></td>
            </tr>
        </table>
    </div>
    <form id="frmImpuesto" name="frmImpuesto" style="margin:0" onsubmit="return false;">
        <table width="640" border="0">
            <tr>
                <td id="tdListIpmuesto"></td>
            </tr>
            <tr>
                <td align="right"><hr />
                    <button id="btsAceptarImpuestoBloque" name="btsAceptarImpuestoBloque" style="display:none" onclick="xajax_insertarImpuestoBloque(xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmImpuesto'));">Aceptar</button>
                    <button id="btsCerraImpuesto" name="btsCerraImpuesto" class="close">Cerrar</button>
                </td>
            </tr>
        </table>
    </form>
</div>

<!--LISTADO DE CLIENTE-->
<div id="divFlotante4" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
    <div id="divFlotanteTitulo4" class="handle">
        <table>
            <tr>
                <td id="tdFlotanteTitulo4" width="100%"></td>
            </tr>
        </table>
    </div>
    <table border="0" id="tblListadoCliente4" width="960">
    <tr id="trBuscarCliente">
    	<td>
        	<form id="frmBuscarCliente" name="frmBuscarCliente" style="margin:0" onsubmit="return false;">
            	<table align="right">
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Criterio:</td>
                	<td><input type="text" id="txtCriterioBuscarCliente" name="txtCriterioBuscarCliente" class="inputHabilitado" onkeyup="byId('btnBuscarCliente').click();"/></td>
                    <td>
                    	<button type="submit" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'), xajax.getFormValues('frmDcto'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscarCliente'].reset(); byId('btnBuscarCliente').click();">Limpiar</button>
					</td>
                </tr>
                </table>
            </form>
        </td>
    </tr>
    <tr>
    	<td id="tdListadoClientes"></td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCerraListCliente" name="btnCerraListCliente" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<!--LISTADO DE EMPLEADO-->
<div id="divFlotante5" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
    <div id="divFlotanteTitulo5" class="handle">
        <table>
            <tr>
                <td id="tdFlotanteTitulo5" width="100%"></td>
            </tr>
        </table>
    </div>
    <table border="0" id="tblListadoEmpleado5" width="960">
    <tr id="trBuscarEmpleado">
    	<td>
        	<form id="frmBuscarEmpleado" name="frmBuscarEmpleado" style="margin:0" onsubmit="return false;">
            	<table align="right">
                 <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Criterio:</td>
                	<td><input type="text" id="txtCriterioBuscarEmpleado" name="txtCriterioBuscarEmpleado" class="inputHabilitado"	/></td>
                 	<td colspan="2" align="right">
                    	<button type="button" id="btnBuscarEmpleado" name="btnBuscarEmpleado" onclick="xajax_buscarEmpleado(xajax.getFormValues('frmBuscarEmpleado'), xajax.getFormValues('frmPedido'));">Buscar</button>
                        <button type="button" id="btnLimpiarEmpleado" name="btnLimpiarEmpleado" onclick="document.forms['frmBuscarEmpleado'].reset(); byId('btnBuscarEmpleado').click();">Limpiar</button>
					</td>
                </tr>
                </table>
                <input type="hidden" id="txtBuscarEmpleado" name="txtBuscarEmpleado" class="inputHabilitado"	/>
            </form>
        </td>
    </tr>
    <tr>
    	<td id="tdListadoEmpleado"></td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCerraListEmpleado" name="btnCerraListCliente" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>
<script>
xajax_listImpuesto(0,'iva','ASC','');

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

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo3");
var theRoot = document.getElementById("divFlotante3");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo4");
var theRoot = document.getElementById("divFlotante4");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo5");
var theRoot = document.getElementById("divFlotante5");
Drag.init(theHandle, theRoot);

//FUCNIONES PARA LOS CAMPOS DE FECHAS


$("#txtFechaEntrega").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
$("#txtFechaCotizacion").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});

new JsDatePick({
	useMode:2,
	target:"txtFechaEntrega",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"armygreen"
});

new JsDatePick({
	useMode:2,
	target:"txtFechaCotizacion",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"armygreen"
});

<?php if (!(isset($_GET['id']))) { ?>
	xajax_nuevoDcto();
<?php } else { ?>
	xajax_cargarDcto('<?php echo $_GET['id']; ?>', xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
<?php } ?>

</script>