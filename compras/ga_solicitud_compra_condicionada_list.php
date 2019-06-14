<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if (!(validaAcceso("ga_solicitud_compra_condicionada_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_ga_solicitud_compra_condicionada_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Compras - Solicitudes de Compra</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    <link rel="stylesheet" type="text/css" href="../js/domDragCompras.css">
    
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
    <script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
    
    <script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    <script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
    
    <script>
		function abrePdf(idSolicituCompras, seccionEmpUsuario){
			window.open('reportes/ga_solicitud_compra_condicionada_pdf.php?idSolCom='+idSolicituCompras+'&session='+seccionEmpUsuario);
		}
		function motivo(condicion){
			//alert(condicion);
			switch(condicion){
				case "condicionar":
					$('#TrMotivoCondRech').show();
					//$('#motivoCondicionamientoRechazo').show();
					document.getElementById('tdMotivo').innerHTML = 'Motivo Condicionamiento:';
					document.getElementById('idEstadoSolicitud').value = '';
						break;
				case "rechazar":
					$('#TrMotivoCondRech').show();
					//$('#motivoCondicionamientoRechazo').show();
					document.getElementById('tdMotivo').innerHTML = 'Motivo Rechazo:';
					document.getElementById('idEstadoSolicitud').value = '';
						break;
				case "apruebaCondicion":
					document.getElementById('idEstadoSolicitud').value = '';
						break;
				default:
					$('#TrMotivoCondRech').hide();
						break;
			}	
		}
		
		function tomarId(idTipo){
			//return alert(idTipo);
			switch(idTipo){
				case "btnDepartamento":
					var idEmp = $("#idEmpresa").val();
					xajax_listadoDepartamento(0,'','',idEmp);
						break;
				case "btnCentroCosto":
					var idDepartamento = $("#idDepartamento").val();
					xajax_listadoCentroCosto(0,'','',idDepartamento);
						break;
			}
			
		}
		
		function valProcesarSolicitud(){
			if (validarCampo('codEmpleado','t','') == true) {
				xajax_procesarSolicitud(xajax.getFormValues('frmAprobarSolicitud'));
			} else {
				validarCampo('codEmpleado','t','');
				alert("Debe Colocar el Código de Empleado");
			return false;
			}
		}
		
    	function valEliminarSolicitud(idSolicitudCompras){
			if(confirm('¿Estas seguro que desea eliminar la solicitud?') == true){
				xajax_eliminarSolicitud(idSolicitudCompras);
			}	
		}
		
		function muestratabla(idObj){
			switch(idObj){
				case 'tipoCompra2':
				$("#tipoCompra4").attr('disabled', true);
				$("#tipoCompra3").attr('disabled', true);
						break;
				case 'tipoCompra3':
				$("#tipoCompra2").attr('disabled', true);
				$("#tipoCompra4").attr('disabled', true);
						break;
				case 'tipoCompra4':
				$("#tipoCompra2").attr('disabled', true);
				$("#tipoCompra3").attr('disabled', true);
						break;	
			}
		$("#tabTipoCompra").show();
		$("#TabTotal").show();
		}
		
		function eliminarTr(tipo){//ELIMINA LOS TR DE LOS ARTICULO AGREGADOS
			switch(tipo){
				case "btnCancelar"://AL CANCELAR
					$('.trEliminar').remove();
						break;
						
				case "btnAgregarElim":
					if(confirm('¿Estas Seguro que se Desea Eliminar?') == true)
						xajax_eliminarArt(xajax.getFormValues('frmNuevaSolicitud'));
							break;
			}
			
		}
		
		function vaciarCampo(){
			if(document.getElementById('codCentroCosto').value != ''){
				byId('codCentroCosto').value = '';
				byId('nombCentroCosto').value = '';
				byId('idCentroCosto').value = '';
			}
		}
		
		function validarNuevaSolicitud(){
			if (validarCampo('codEmpresa','t','') == true
				&& validarCampo('nombEmpresa','t','') == true
				&& validarCampo('codDepartamento','t','') == true
				&& validarCampo('nombDepartamento','t','lista') == true
				&& validarCampo('codCentroCosto','t','lista') == true
				&& validarCampo('nombCentroCosto','t','lista') == true) {
				xajax_guardarSolicitud(xajax.getFormValues('frmNuevaSolicitud'));
			} else {
				validarCampo('codEmpresa','t','');
				validarCampo('nombEmpresa','t','');
				validarCampo('codDepartamento','t','');
				validarCampo('nombDepartamento','t','');
				validarCampo('codCentroCosto','t','');
				validarCampo('nombCentroCosto','t','');
				alert("Los campos señalados en rojo son requeridos");
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
            <td class="tituloPaginaCompras">Solicitudes de Compra Condicionadas</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr class="noprint">
            <td>
                <form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                    <table  border="0" align="right">
                        <tr>
                            <td align="right" class="tituloCampo" width="120">Empresa:</td>
                            <td id="tdlstEmpresa" colspan="2"></td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo" width="120">Tipo De Compra:</td>
                            <td id="tdlsttipCompra">&nbsp;</td>
                            <td align="right" class="tituloCampo" width="120">Estado:</td>
                            <td id="tdLisEstado">&nbsp;</td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo" width="120">Nro. Solicitud:</td>
                            <td><input type="text" id="txtNroSolicitud" name="txtNroSolicitud" onkeyup="byId('btnBuscar').click();" class="inputHabilitado"/></td>
                            <td align="right" class="tituloCampo" width="120">Criterio:</td>
                            <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();" class="inputHabilitado"/></td>
                             <td>
                                <button type="button" id="btnBuscar" name="btnBuscar" onclick="xajax_BuscarSolicituComp(xajax.getFormValues('frmBuscar'));" style="cursor:default">Buscar</button>
                            	<button type="button" id="buttLimpiar" name="buttLimpiar" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();" style="cursor:default">Limpiar</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </td>
        </tr>
        <tr>
            <td id="tdListSolictComp"></td>
        </tr>
        <tr>
            <td>
                <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                    <tr>
                        <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                        <td align="center">
                            <table>
                                <tr>
                                    <td><img src="../img/iconos/ico_aceptar_naranja.png"/></td>
                                    <td>Procesado</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_error.gif"/></td>
                                    <td>Rechazado</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_aceptar_f2.gif"/></td>
                                    <td>Condicionado</td>
                                </tr>
                            </table>
                    	</td>
                    </tr>
                </table>
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
                                    <td><img src="../img/iconos/accept.png"/></td>
                                    <td>Aprobar Solicitud</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_view.png"/></td>
                                    <td>Ver</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/page_white_acrobat.png"/></td>
                                    <td>Archivo PDF</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_edit.png"/></td>
                                    <td>Editar</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_delete.png"/></td>
                                    <td>Eliminar</td>
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
<!--PROCESAR SOLICITUD-->
<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0; width:auto">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" name="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
<form id="frmAprobarSolicitud" onsubmit="return false;" name="frmAprobarSolicitud" style="margin:0">
    <table border="0" id="tblSeccion" width="380">
    <tr>
        <td>
            <table width="100%">
            <tr>
                <td align="right" class="tituloCampo" width="45%"><span class="textoRojoNegrita">*</span>C&oacute;digo de Empleado:</td>
                <td width="55%">
                    <input type="text" id="codEmpleado" name="codEmpleado" size="25" class="inputHabilitado"/>
                </td>
            </tr>
            </table>
            <table width="100%" id="tablaCondicionar">
            <tr>
                <td colspan="2" align="center" class="tituloCampo" width="50%">
                    <label><input type="radio" id="cambiarestado" name="cambiarestado" value="4" checked="checked" onclick="motivo('')"/>Aprobar</label>
                    <label><input type="radio" id="cambiarestado" name="cambiarestado" value="6" onclick="motivo('condicionar')"/>Condicionar</label>
                    <label><input type="radio" id="cambiarestado" name="cambiarestado" value="7" onclick="motivo('rechazar')"/>Rechazar</label>
                </td>
               
            </tr>
            <tr id="TrMotivoCondRech" style="display:none">
                <td align="right" class="tituloCampo" width="50%" id="tdMotivo"></td>
                <td width="50%"><textarea id="motivoCondicionamientoRechazo" name="motivoCondicionamientoRechazo" size="25"></textarea></td>
            </tr>
			</table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <input type="hidden" id="idSolicitudCompra" name="idSolicitudCompra" />
            <input type="hidden" id="idEstadoSolicitud" name="idEstadoSolicitud" />
            <button type="submit" onclick="valProcesarSolicitud();" id="btnProceSolComp" name="btnProceSolComp">
                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td id="tdImg"></td>
                        <td>&nbsp;</td>
                        <td id="tdbtnNomb"></td>
                        <td>&nbsp;</td>
                    </tr>
                </table>
            </button>
            <button type="button" class="close" onclick="document.forms['frmAprobarSolicitud'].reset(); motivo('')" id="btnCanProceSolComp" name="btnCanProceSolComp">
             <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td><img src="../img/iconos/ico_error.gif"/></td>
                        <td>&nbsp;</td>
                        <td>Cancelar</td>
                        <td>&nbsp;</td>
                    </tr>
                </table>
            </button>
        </td>
    </tr>
    </table>
</form>
</div> <!--FIN PROCESO DE SOLICITUD-->
<!--NUEVA SOLICITUD  style="max-height:150px; overflow:auto;-->
<div id="divFlotante1" class="root" style="cursor:auto; max-height:600px; width:980px; overflow:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle">
    	<table>
        	<tr>
            <td id="tdFlotanteTitulo1" name="tdFlotanteTitulo1" width="100%"></td>
            </tr>
        </table>
    </div>
    <form id="frmNuevaSolicitud" name="frmNuevaSolicitud" onsubmit="return false;" style="margin:0">
        <table width="100%" border="0" width="960" >
        <tr> 
            <td rowspan="2" colspan="2" align="left">
                <img id="logo" border="0" style="width:150px;" alt="image" src="../img/logos/logo_grupo_automotriz.jpg">
            </td>
            <td align="right" class="tituloCampo" width="120">Nro. Solicitud:</td>
            <td align="left" width="15%"><input type="text" id="numSolicitud" name="numSolicitud" size="35px" readonly="readonly"/></td>
        </tr>
        <tr>
            <td align="right" class="tituloCampo" width="120">Fecha:</td>
            <td align="left" width="15%"><input type="text" id="fechaSolicitud" name="fechaSolicitud" size="35px" readonly="readonly"/></td>
        </tr>
           </tr>
            <tr>
                <td align="right" width="15%" class="tituloCampo">Empresa:</td>
                <td  align="left" colspan="3">
                	<input type="text" id="codEmpresa" name="codEmpresa" size="5px" readonly="readonly"/>
                    	<!---->
                        <a class="modalImg" id="abreEmpr" rel="#divFlotante2" onclick="xajax_listadoEmpresas();"> <!---->
                            <button  id="btnEmpresa" title="Seleccionar Empresa" style="cursor:default" name="btnEmpresa" type="button">
                                <img src="../img/iconos/ico_pregunta.gif">
                            </button>
                        </a>
                	<input type="text" id="nombEmpresa" name="nombEmpresa" size="60px" readonly="readonly"/>
                    <input type="hidden" id="idEmpresa" name="idEmpresa" readonly="readonly"/>
                </td>
            </tr>
            <tr>
                <td align="right" width="15%" class="tituloCampo">Departamento:</td>
                <td  align="left" colspan="3">
                	<input type="text" id="codDepartamento" name="codDepartamento" size="5px" readonly="readonly"/>
                    	<button  id="btnDepartamento" title="Seleccionar Departamento" style="cursor:default" name="btnDepartamento" type="button"
                        onclick="tomarId(this.id);">
                        	<img src="../img/iconos/ico_pregunta.gif">
                        </button>
                	<input type="text" id="nombDepartamento" name="nombDepartamento" size="60px" readonly="readonly"/>
                	<input type="hidden" id="idDepartamento" name="idDepartamento" readonly="readonly"/>
                </td>
            </tr>
            <tr>
                <td align="right" width="15%" class="tituloCampo">Unidad (Centro de Costo):</td>
                <td  align="left" colspan="3">
                	<input type="text" id="codCentroCosto" name="codCentroCosto" size="5px" readonly="readonly"/>
                    	<button  id="btnCentroCosto" title="Seleccionar Unidad (Centro de Costo)" 
                        style="cursor:default; display:none;" name="btnCentroCosto" type="button"
                        onclick="tomarId(this.id);">
                        	<img src="../img/iconos/ico_pregunta.gif">
                        </button>
                	<input type="text" id="nombCentroCosto" name="nombCentroCosto" size="60px" readonly="readonly"/>
                	<input type="hidden" id="idCentroCosto" name="idCentroCosto" readonly="readonly"/>
                </td>
            </tr>
			<tr>
                <td align="right" width="15%" class="tituloCampo">Tipo de Compra:</td>
                <td align="left" colspan="3">
                	<input id="tipoCompra2" type="radio" onclick="muestratabla(this.id);" value="2" name="tipoCompra">Cargos (Activos Fijo)
                    <input id="tipoCompra4" type="radio" onclick="muestratabla(this.id);" value="4" name="tipoCompra">Gastos / Activos
                    <input id="tipoCompra3" type="radio" onclick="muestratabla(this.id);" value="3" name="tipoCompra">Servicios
                </td>
            </tr>
            </tr>
            <tr>
                <td align="" colspan="4">
                
                    <table width="100%" border="0" id="tabTipoCompra" style="display:none">
                        <tr class="tituloArea">
                        	<td align="center" colspan="8">Descripción del Material o Servicio</td>
                			<td>
                        		<a class="modalImg" id="agregarArt" rel="#divFlotante3" onclick="xajax_listadoArticulo();"> <!---->
                                    <button id="btnAgregarArt" title="Seleccionar Articulo" style="cursor:default" name="btnAgregarArt" type="button">
                                        <img src="../img/iconos/add.png">
                                    </button>
                        		</a>
                            </td>
                        </tr>
                        <tr class="tituloColumna">
                            <td>Cantidad</td>
                            <td>Unidad</td>
                            <td>Código</td>
                            <td colspan="3">Descripción</td>
                            <td>Precio</td>
                            <td>F Requerida</td>
                            	<input id="artAgregadoSolComp" type="hidden" name="artAgregadoSolComp" value="0"/>
                            <td>
                                <button id="btnAgregarElim" title="Eliminar Articulo" onclick="eliminarTr(this.id)" style="cursor:default" name="btnAgregarElim" type="button">
                                    <img src="../img/iconos/delete.png">
                                </button>
                            </td>
                        </tr>
                    </table>
                    <table width="100%" border="0" id="TabTotal" style="display:none">
                      <tr class="trResaltarTotal">
                        <td colspan="7" align="right" width="100%" class="tituloCampo">Precio Total:</td>
                        <td><input id="totalPrecio" name="totalPrecio" size="10" style="text-align:center; border:0px; color:#007F00" readonly="readonly"/></td>
                      </tr>
                    </table>

                </td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo">Proveedor Sugerido</td>
                <td align="left" colspan="3" id="tdProveedor"></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo">Justificación del Proveedor</td>
                <td align="left" colspan="3">
                	<textarea id="justificacionProveedor" name="justificacionProveedor" style="width:90%;height:100%"></textarea>
                </td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo">Observacion</td>
                <td align="left" colspan="3">
                	<textarea id="ObservacionProveedor" name="ObservacionProveedor" style="width:90%;height:100%"></textarea>
                </td>
            </tr>
            <tr class="tituloArea">
                <td align="center" colspan="4">Para ser llenado para todo tipo de compra (excepto para las compras de material de stock del almacén)</td>
            </tr>
            <tr class="">
                <td align="right" class="tituloCampo">Sustitución o Adición</td>
                <td align="left">
                	<input id="sustitucion1" type="radio" value="1" name="sustitucion">Sustitución
                    <input id="sustitucion2" type="radio" value="2" name="sustitucion">Adición
                </td>
                <td align="right" class="tituloCampo">Presupuestado (S/N):</td>
                <td align="left">
                	<input id="presupuestado0" type="checkbox" value="0" name="presupuestado">Presupuestado
                </td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo">Justificación de la Compra:</td>
                <td align="left" colspan="3">
                	<textarea id="justificacionCompra" name="justificacionCompra" style="width:90%;height:100%"></textarea>
                </td>
            </tr>
			<tr>
            	<td colspan="4">
                    <table width="100%" id="tabAprobacion">
                        <tr class="tituloArea">
                            <td align="center"colspan="4">UNIDAD SOLICITANTE</td>
                        </tr>
                        <tr>
                            <td align="center" class="tituloCampo"  colspan="2"><b>Solicitado Por</b></td>
                            <td align="center" class="tituloCampo" colspan="2"><b>Aprobado Por</b></td>
                        </tr>            
                        <tr>
                            <td align="right" class="tituloCampo" width="15%">Nombre y Firma:</td>
                            <td align="left" id="tdNombFirmaS"width="15%"></td>
                            <td align="right" class="tituloCampo" width="15%">Nombre y Firma:</td>
                            <td align="left" id="tdNombFirmaA"width="15%"></td>
                        </tr>            
                        <tr>
                            <td align="right" class="tituloCampo">N° Empleado:</td>
                            <td align="left" id="tdnumEmplS"></td>
                            <td align="right" class="tituloCampo">N° Empleado:</td>
                            <td align="left" id="tdnumEmplA"></td>
                        </tr>            
                        <tr>
                            <td align="right" class="tituloCampo">Fecha:</td>
                            <td align="left" id="tdfechaS"></td>
                            <td align="right" class="tituloCampo">Fecha:</td>
                            <td align="left" id="tdfechaA"></td>
                        </tr>
 						<tr class="tituloArea">
                            <td align="center"colspan="4">GERENCIA DE COMPRAS</td>
                        </tr>
                        <tr>
                            <td align="center" class="tituloCampo" colspan="2"><b>Conformado Por</b></td>
                            <td align="center" class="tituloCampo"  colspan="2"><b>Procesado Por</b></td>
                        </tr>            
                        <tr>
                            <td align="right" class="tituloCampo" width="15%">Nombre y Firma:</td>
                            <td align="left" id="tdNombFirmaC" width="15%"></td>
                            <td align="right" class="tituloCampo">Nombre y Firma:</td>
                            <td align="left" id="tdNombFirmaP"></td>
                        </tr>            
                        <tr>
                            <td align="right" class="tituloCampo">N° Empleado:</td>
                            <td align="left" id="tdnumEmplC"></td>
                            <td align="right" class="tituloCampo">N° Empleado:</td>
                            <td align="left" id="tdnumEmplP"></td>
                        </tr>            
                        <tr>
                            <td align="right" class="tituloCampo">Fecha:</td>
                            <td align="left" id="tdfechaC"></td>
                            <td align="right" class="tituloCampo">Fecha:</td>
                            <td align="left" id="tdfechaP"></td>
                        </tr>                    
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="4">
					<table width="100%" id="tabAprobacion">
                        <tr class="tituloArea">
                            <td align="center"colspan="4" id="tdMotivoCondicional"></td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo" width="15%">Nombre Empleado:</td>
                            <td align="left" id="tdNombConRe"width="15%"></td>
                            <td align="left" rowspan="3" width="30%">
                            	<textarea id="texAreaConRe" name="texAreaConRe" readonly="readonly" style="width:98%;height:100%" ></textarea>
                            </td>
                        </tr>            
                        <tr>
                            <td align="right" class="tituloCampo">N° Empleado:</td>
                            <td align="left" id="tdnumEmplConRe"></td>
                        </tr>            
                        <tr>
                            <td align="right" class="tituloCampo">Fecha:</td>
                            <td align="left" id="tdfechaConRe"></td>
                        </tr>
                    </table>               
                 </td>
            </tr>

            <tr>
                <td align="right" colspan="4"><hr /></td>
            </tr>
            <tr>
                <td align="right" colspan="4">
                   	 <button  id="btnProcesoGuardar" name="btnProcesoGuardar" title="Guardar Solicitud" style="cursor:default; display:none;" onclick="validarNuevaSolicitud();"  type="button">
                        <table border="0" cellpadding="0" cellspacing="0">
                          <tr>
                            <td id="tdimgProceso"></td>
                            <td>&nbsp;</td>
                            <td id="tdNombProceso"></td>
                          </tr>
                        </table>
                    </button>
                    <button  id="btnGuardar" name="btnGuardar" title="Guardar Solicitud" style="cursor:default" onclick="validarNuevaSolicitud();"  type="button">
                        <table border="0" cellpadding="0" cellspacing="0">
                          <tr>
                            <td><img src="../img/iconos/ico_save.png"></td>
                            <td>&nbsp;</td>
                            <td>Guardar</td>
                          </tr>
                        </table>
                    </button>
                    <button  id="btnCancelar" title="Cancelar Solicitud" style="cursor:default" class="close" name="btnCancelar" type="button">
                        <table border="0" cellpadding="0" cellspacing="0">
                          <tr>
                            <td><img src="../img/iconos/ico_error.gif"></td>
                            <td>&nbsp;</td>
                            <td>Cancelar</td>
                          </tr>
                        </table>
                    </button>
                    <input id="idSolicitudCompras" name="idSolicitudCompras" type="hidden"/>
                </td>
            </tr>

        </table>
    </form>
</div> <!--FIN NUEVA SOLICITUD-->
<!--EMPRESA,	DEPARTAMENTO,	UNIDAD CENTRO DE COSTO-->
<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0; width:50%">
	<div id="divFlotanteTitulo2" class="handle">
    	<table>
        	<tr>
            <td id="tdFlotanteTitulo2" name="tdFlotanteTitulo2" width="100%"></td>
            </tr>
        </table>
    </div>
    <form id="frmBuscar2" name="frmBuscar2" style="margin:0" onsubmit="return false;">
        <table border="0" align="right">
            <tr>
            	<td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input id="textCriterio" name="textCriterio" class="inputHabilitado"/></td>
                <td id="tdBtnBuscarCentroCosto"></td>
                <td id="tdBtnLimpiar">
                    <button id="btnLimpiar" title="Limpiar" style="cursor:default" name="btnLimpiar" type="button" 
                    onclick="document.forms['frmBuscar2'].reset(); byId('btnBuscarCentroCosto').click();">
                       Limpiar
                    </button>
                </td>
            </tr>
        </table>
	</form>
    <table width="100%" border="0">
        <tr>
            <td id="empDepaUnidadCentroCosto" colspan="2"></td>
        </tr>
        <tr>
        	<td align="right">
            		<hr/>
                <button  id="btnGuardar2" title="Guardar Solicitud" style="cursor:default" name="btnGuardar2" type="button">
                    <table border="0" cellpadding="0" cellspacing="0">
                      <tr >
                        <td><img src="../img/iconos/ico_save.png"></td>
                        <td>&nbsp;</td>
                        <td>Guardar</td>
                      </tr>
                    </table>
                </button>
                <button  id="btnCancelar2" title="Cancelar Solicitud" style="cursor:default" class="close" name="btnCancelar2" type="button" onclick="document.forms['frmBuscar2'].reset();">
                    <table border="0" cellpadding="0" cellspacing="0">
                      <tr>
                        <td><img src="../img/iconos/ico_error.gif"></td>
                        <td>&nbsp;</td>
                        <td>Cancelar</td>
                      </tr>
                    </table>
                </button>            
            </td>
        </tr>
    </table>

</div> <!--FIN EMPRESA DEPARTAMENTO CENTRO COSTO-->
<!--AGREGA ARTICULOS-->
<div id="divFlotante3" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0; width:50%">
	<div id="divFlotanteTitulo3" class="handle">
    	<table>
        	<tr>
            <td id="tdFlotanteTitulo3" name="tdFlotanteTitulo3" width="100%"></td>
            </tr>
        </table>
    </div>
    <form id="frmbuscarArt" name="frmbuscarArt" style="margin:0" onsubmit="return false;">
        <table border="0" align="right">
            <tr>
            	<td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input id="textCriterioArt" name="textCriterioArt" class="inputHabilitado"/></td>
                <td id="">
                	<button id="btnBuscarArt" title="Buscar" onclick="xajax_buscarArticulo(xajax.getFormValues('frmbuscarArt'));" style="cursor:default" name="btnBuscarArt" type="button">
                    	Buscar
                    </button>
                    <button id="btnLimpiarArt" title="Limpiar" style="cursor:default" name="btnLimpiarArt" type="button" 
                    onclick="document.forms['frmbuscarArt'].reset(); byId('btnBuscarArt').click();">
                       Limpiar
                    </button>
                </td>
            </tr>
        </table>
	</form>
    <table width="100%" border="0">
        <tr>
            <td id="tdListArticulo" colspan="2"></td>
        </tr>
        <tr>
        	<td align="right">
            		<hr/>
                <button  id="btnCancelar3" title="Cancelar Articulo" style="cursor:default" class="close" name="btnCancelar3" type="button" onclick="document.forms['frmbuscarArt'].reset();">
                    <table border="0" cellpadding="0" cellspacing="0">
                      <tr>
                        <td><img src="../img/iconos/ico_error.gif"></td>
                        <td>&nbsp;</td>
                        <td>Cancelar</td>
                      </tr>
                    </table>
                </button>            
            </td>
        </tr>
    </table>
</div> <!--FIN ARTICULO-->

<script>

xajax_listadoSolicitudCompra(0,'','','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_combLstEstCompra();
xajax_combLstTipCompra();

function openImg(idObj){
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
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);

</script>