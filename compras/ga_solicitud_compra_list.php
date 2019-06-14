<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("ga_solicitud_compra_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_ga_solicitud_compra_list.php");

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
		function abrirDiv(idObj,nombreObj){
			switch(nombreObj){
				case "aNuevo":
					document.forms['frmNuevaSolicitud'].reset();
					byId('idSolicitudCompras').value = '';
					eliminarTr('btnCancelar');
					byId('idProveedor').className = 'inputHabilitado';
					byId('nombreProveedor').className = 'inputInicial';
					byId('codCentroCosto').className = 'inputInicial';
					byId('nombCentroCosto').className = 'inputInicial';
					byId('codDepartamento').className = 'inputInicial';
					byId('nombDepartamento').className = 'inputInicial';
					byId('justificacionProveedor').className = 'inputHabilitado';
					byId('ObservacionProveedor').className = 'inputHabilitado';
					byId('justificacionCompra').className = 'inputHabilitado';
					byId("tdFlotanteTitulo1").innerHTML ="Nueva Solicitud De Compras y Servicios";
					
					document.getElementById('btnEmpresa').disabled = false;
					document.getElementById('btnDepartamento').disabled = false;
					document.getElementById('btnCentroCosto').disabled = true;
					document.getElementById('btnAgregarProveedor').disabled = false;
					document.getElementById('btnGuardar').disabled = false;
					document.getElementById('btnAgregarArt').disabled = false;
					document.getElementById('btnAgregarElim').disabled = false;
					
					xajax_fromSolicitud("nuevo");
						break;	
				case "abreEmpr":
					document.forms['frmBuscar2'].reset();
					byId("tdFlotanteTitulo2").innerHTML = "Lista de Empresa";		
					xajax_listadoEmpresas(0,"nombre_empresa","ASC");	
					byId("btnBuscarCentroCosto").onclick = function() {
						xajax_BuscarempDepaUnidadCentroCosto(xajax.getFormValues('frmBuscar2'),'Empresa')
					}	
						break;	
				case "abreDepartamento":
					document.forms['frmBuscar2'].reset();
					byId("tdFlotanteTitulo2").innerHTML ="Listado de Departamentos";
					xajax_BuscarempDepaUnidadCentroCosto(xajax.getFormValues('frmNuevaSolicitud'),'Departamento')
					byId("btnBuscarCentroCosto").onclick = function() {
						xajax_BuscarempDepaUnidadCentroCosto(xajax.getFormValues('frmNuevaSolicitud'),'Departamento',xajax.getFormValues('frmBuscar2'))
					}				
						break;	
				case "abreCentroCosto":
					document.forms['frmBuscar2'].reset();
					byId("tdFlotanteTitulo2").innerHTML ="Listado de Centro de Costo";
					xajax_BuscarempDepaUnidadCentroCosto(xajax.getFormValues('frmNuevaSolicitud'),'centroCosto')
					byId("btnBuscarCentroCosto").onclick = function() {
						xajax_BuscarempDepaUnidadCentroCosto(xajax.getFormValues('frmNuevaSolicitud'),'centroCosto',xajax.getFormValues('frmBuscar2'));
					}		
					
						break;	
				case "imgEditarArticulo":
					byId("tdFlotanteTitulo1").innerHTML ="Editar Solicitud De Compras y Servicios";
					document.getElementById('btnEmpresa').disabled = false;
					document.getElementById('btnDepartamento').disabled = false;
					document.getElementById('btnCentroCosto').disabled = false;
					document.getElementById('btnAgregarProveedor').disabled = false;
					
					document.getElementById('btnGuardar').disabled = false;
					document.getElementById('btnAgregarArt').disabled = false;
					document.getElementById('btnAgregarElim').disabled = false;
					xajax_eliminarArticulo(xajax.getFormValues('frmNuevaSolicitud'),1);
						break;
				case "imgVerSolicitud":
					byId("tdFlotanteTitulo1").innerHTML ="Ver Solicitud De Compras y Servicios";
					document.getElementById('btnEmpresa').disabled = true;
					document.getElementById('btnDepartamento').disabled = true;
					document.getElementById('btnCentroCosto').disabled = true;
					document.getElementById('btnAgregarProveedor').disabled = true;
					
					document.getElementById('btnGuardar').disabled = true;
					document.getElementById('btnAgregarArt').disabled = true;
					document.getElementById('btnAgregarElim').disabled = true;
					xajax_eliminarArticulo(xajax.getFormValues('frmNuevaSolicitud'),1);
						break;
				default:
					document.forms['frmBuscarProveedor'].reset();
					byId("tdFlotanteTitulo4").innerHTML ="Listado de Proveedores";	
					xajax_listProveedores(0,'','','');			
						break;	
			}
		openImg(idObj);
		}
		function abrePdf(idSolicituCompras, seccionEmpUsuario){
			window.open('reportes/ga_solicitud_compra_pdf.php?idSolCom='+idSolicituCompras+'&session='+seccionEmpUsuario);
		}
		function asignaMonto(idArt,monto){
			alert('textArtPrecioItem'+idArt);
			document.getElementById('textArtPrecioItem'+idArt).value = 'este';
		}
		
		function RecorrerForm(nameFrm,accion,arrayBtn){ 
 			var frm = document.getElementById(nameFrm);
			var sAux= "";
			for (i=0;i<frm.elements.length;i++)	{//recorre los elementos del form
				if(frm.elements[i].type == 'button' || frm.elements[i].type == 'submit'){//si son tipo text
					sAux = frm.elements[i].id;
					if (accion == 0) {
						document.getElementById(sAux).disabled = true; //habilita
					} else {
						document.getElementById(sAux).disabled = false; //desahabilita
					}
				}
			}
			
			if(arrayBtn != null){
				for(a=0;a<arrayBtn.length;a++){
					document.getElementById(arrayBtn[a]).disabled = true; //desahabilita
				}	
			}
		}
		
		function motivo(condicion){
			//alert(condicion);
			switch(condicion){
				case "condicionar":
					$('#TrMotivoCondRech').show();
					//$('#motivoCondicionamientoRechazo').show();
					document.getElementById('tdMotivo').innerHTML = '<span class="textoRojoNegrita">*</span>Motivo Condicionamiento:';
					document.getElementById('idEstadoSolicitud').value = '';
						break;
				case "rechazar":
					$('#TrMotivoCondRech').show();
					//$('#motivoCondicionamientoRechazo').show();
					document.getElementById('tdMotivo').innerHTML = '<span class="textoRojoNegrita">*</span>Motivo Rechazo:';
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
			if(document.getElementById('codEmpresa').value == '' || document.getElementById('nombEmpresa').value == '' ||
				document.getElementById('codDepartamento').value == '' || document.getElementById('nombDepartamento').value == '' ||
				document.getElementById('codCentroCosto').value == '' || document.getElementById('nombCentroCosto').value == ''
			){
				validarNuevaSolicitud();
				if(document.getElementById('tipoCompra2').checked == true){
					document.getElementById('tipoCompra2').checked = false;
				} else if(document.getElementById('tipoCompra3').checked == true){
					document.getElementById('tipoCompra3').checked = false;
				} else if(document.getElementById('tipoCompra4').checked == true){
					document.getElementById('tipoCompra4').checked = false;
				}	
			}else{
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
		}
		
		function eliminarTr(tipo){//ELIMINA LOS TR DE LOS ARTICULO AGREGADOS
			switch(tipo){
				case "btnCancelar"://AL CANCELAR
					xajax_eliminarArticulo(xajax.getFormValues('frmNuevaSolicitud'),1);
						break;
						
				case "btnAgregarElim":
					if(confirm('¿Estas Seguro que se Desea Eliminar el articulo de la solicitud?') == true)
						xajax_eliminarArticulo(xajax.getFormValues('frmNuevaSolicitud'));
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
			&& validarCampo('nombCentroCosto','t','lista') == true
			&& validarCampo('idProveedor','t','') == true
			&& validarCampo('nombreProveedor','t','') == true) {
				RecorrerForm('frmNuevaSolicitud',0,null);	
				xajax_guardarSolicitud(xajax.getFormValues('frmNuevaSolicitud'));
			} else {
				validarCampo('codEmpresa','t','');
				validarCampo('nombEmpresa','t','');
				validarCampo('codDepartamento','t','');
				validarCampo('nombDepartamento','t','');
				validarCampo('codCentroCosto','t','');
				validarCampo('nombCentroCosto','t','');
				validarCampo('idProveedor','t','');
				validarCampo('nombreProveedor','t','');
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
        <td class="tituloPaginaCompras">Solicitudes de Compra</td>
    </tr>
    <tr>
        <td>&nbsp;</td>
    </tr>
    <tr class="noprint">
        <td>
            <table align="left" border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <a class="modalImg" id="aNuevo" name="aNuevo" rel="#divFlotante1" onclick="abrirDiv(this,name);"> <!--xajax_(this.id);-->
                        <button type="button" id="butNuevo" style="cursor:default" >
                            <table align="center" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_new.png"/></td>
                                    <td>&nbsp;</td>
                                    <td>Nuevo</td>
                                </tr>
                            </table>
                        </button>
                    </a>
                </td>
            </tr>
            </table>
            <form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table border="0" align="right">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="2"></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Fecha Solicitud:</td>
                    <td colspan="2">&nbsp;Desde:&nbsp;<input id="txtFechaDesde"  name="txtFechaDesde" class="inputHabilitado" type="text" style="text-align:center" size="10" autocomplete="off">&nbsp;Hasta:&nbsp;<input id="txtFechaHasta" name="txtFechaHasta" class="inputHabilitado" type="text" style="text-align:center" size="10" autocomplete="off"></td>
                </tr> 
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Tipo De Compra:</td>
                    <td id="tdlsttipCompra">&nbsp;</td>
                    <td align="right" class="tituloCampo" width="120">Estado:</td>
                    <td id="tdLisEstado" colspan=""></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Nro. Solicitud:</td>
                    <td><input type="text" id="txtNroSolicitud" name="txtNroSolicitud" onkeyup="byId('btnBuscar').click();" class="inputHabilitado"/></td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();" class="inputHabilitado"/></td>
                     <td>
                        <button type="button" id="btnBuscar" name="btnBuscar" onclick="xajax_BuscarSolicituComp(xajax.getFormValues('frmBuscar'));" style="cursor:default">Buscar</button>
                        <button type="button" id="buttLimpiar" name="buttLimpiar" onclick="document.forms['frmBuscar'].reset();  byId('btnBuscar').click();" style="cursor:default">Limpiar</button>
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
                                <td><img src="../img/iconos/ico_aceptar.gif"/></td>
                                <td>Aprobado</td>
                                <td>&nbsp;</td>
                                <td><img src="../img/iconos/ico_aceptar_azul.png"/></td>
                                <td> Solicitada </td>
                                <td>&nbsp;</td>
                                <td><img src="../img/iconos/ico_aceptar_amarillo.png"/></td>
                                <td> Conformar </td>
                                <td>&nbsp;</td>
                                <td><img src="../img/iconos/ico_aceptar_naranja.png"/></td>
                                <td>Procesado</td>
                                <td>&nbsp;</td>                                
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
<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" name="tdFlotanteTitulo" width="100%" align="left"></td></tr></table></div>
    
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
                <td width="50%"><textarea id="motivoCondicionamientoRechazo" name="motivoCondicionamientoRechazo" size="25" class="inputHabilitado"></textarea></td>
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
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" name="tdFlotanteTitulo1" width="100%" align="left"></td></tr></table></div>

    <form id="frmNuevaSolicitud" name="frmNuevaSolicitud" onsubmit="return false;" style="margin:0">
    <input id="idSolicitudCompras" name="idSolicitudCompras" type="hidden" value=""/>
    
    <table width="960" border="0">
    <tr>
        <td>
            <table width="100%" border="0">
                <tr> 
                    <td rowspan="2" colspan="2" align="left"><img id="logo" border="0" style="width:150px;" alt="image" src="../img/logos/logo_grupo_automotriz.jpg">
                    </td>
                    <td align="right" class="tituloCampo" width="120">Nro. Solicitud:</td>
                    <td align="left" width="15%"><input type="text" id="numSolicitud" name="numSolicitud" size="35px" readonly="readonly"/></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120">Fecha:</td>
                    <td align="left" width="15%"><input type="text" id="fechaSolicitud" name="fechaSolicitud" size="35px" readonly="readonly"/></td>
                </tr>
                <tr>
                    <td align="right" width="15%" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td  align="left" colspan="3">
                        <table id="tblEmpresa" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td><input type="text" id="codEmpresa" name="codEmpresa" size="10px" style="text-align:center" readonly="readonly"/></td>
                                <td>
                                    <a class="modalImg" id="abreEmpr" name="abreEmpr" rel="#divFlotante2" onclick="abrirDiv(this,name);"> 
                                        <button  id="btnEmpresa"  title="Seleccionar Empresa" style="cursor:default" name="btnEmpresa" type="button">
                                            <img src="../img/iconos/ico_pregunta.gif">
                                        </button>
                                    </a>	
                                </td>
                            <td>
                                <input type="text" id="nombEmpresa" name="nombEmpresa" style="text-align:center" size="65px" readonly="readonly"/>
                                <input type="hidden" id="idEmpresa" name="idEmpresa" readonly="readonly" value=""/>
                            </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td align="right" width="15%" class="tituloCampo"><span class="textoRojoNegrita">*</span>Departamento:</td>
                    <td  align="left" colspan="3">
                        <table id="tblDepartamento" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td><input type="text" id="codDepartamento" name="codDepartamento" style="text-align:center" size="10px" readonly="readonly"/></td>
                                <td>
                                    <a class="modalImg" id="abreDepartamento" name="abreDepartamento" rel="#divFlotante2" onclick="abrirDiv(this,name);"> 
                                        <button  id="btnDepartamento" name="btnDepartamento" title="Seleccionar Departamento" style="cursor:default" disabled="disabled"  type="button">
                                            <img src="../img/iconos/ico_pregunta.gif">
                                        </button>
                                    </a>
                                </td>
                                <td>
                                    <input type="text" id="nombDepartamento" name="nombDepartamento" size="65px" style="text-align:center" readonly="readonly"/>
                                    <input type="hidden" id="idDepartamento" name="idDepartamento" readonly="readonly"/>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td align="right" width="15%" class="tituloCampo"><span class="textoRojoNegrita">*</span>Unidad (Centro de Costo):</td>
                    <td  align="left" colspan="3">
                        <table id="tblDepartamento" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td><input type="text" id="codCentroCosto" name="codCentroCosto" size="10px" style="text-align:center" readonly="readonly"/></td>
                                <td>
                                    <a class="modalImg" id="abreCentroCosto" name="abreCentroCosto" rel="#divFlotante2" onclick="abrirDiv(this,name);"> 
                                        <button  id="btnCentroCosto" name="btnCentroCosto" title="Seleccionar Unidad (Centro de Costo)" style="cursor:default" disabled="disabled" type="button">
                                            <img src="../img/iconos/ico_pregunta.gif">
                                        </button>
                                    </a>
                                </td>
                                <td>
                                    <input type="text" id="nombCentroCosto" name="nombCentroCosto" size="65px" style="text-align:center" readonly="readonly"/>
                                    <input type="hidden" id="idCentroCosto" name="idCentroCosto" readonly="readonly"/>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td align="right" width="15%" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo de Compra:</td>
                    <td align="left" colspan="3">
                    <input id="tipoCompra2" type="radio" onclick="muestratabla(this.id);" value="2" name="tipoCompra">Cargos (Activos Fijo)
                    <input id="tipoCompra4" type="radio" onclick="muestratabla(this.id);" value="4" name="tipoCompra">Gastos / Activos
                    <input id="tipoCompra3" type="radio" onclick="muestratabla(this.id);" value="3" name="tipoCompra">Servicios
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr id="tabTipoCompra">	
        <td >
            <fieldset><legend class="legend">Descripción del Material o Servicio</legend>
            <table width="100%" border="0">
                <tr>
                    <td align="" colspan="4" >
                    
                        <table align="left">
                            <tr>
                                <td>
                                    <a class="modalImg" id="agregarArt" rel="#divFlotante3" onclick="xajax_listadoArticulo();">
                                        <button id="btnAgregarArt" title="Seleccionar Articulo" style="cursor:default" name="btnAgregarArt" type="button">
                                            <table cellpadding="0" cellspacing="0">
                                                <tr><td><img src="../img/iconos/add.png"></td><td>&nbsp;</td><td>Agregar</td></tr>
                                            </table>
                                        </button>
                                    </a>
                                    <button id="btnAgregarElim" title="Eliminar Articulo" onclick="eliminarTr(this.id)" style="cursor:default" name="btnAgregarElim" type="button">
                                        <table cellpadding="0" cellspacing="0">
                                            <tr><td><img src="../img/iconos/delete.png"></td><td>&nbsp;</td><td>Quitar</td></tr>
                                        </table>
                                    </button>
                                </td>
                            </tr>
                        </table>
                        <table width="100%" border="0"><!--style="display:none"-->
                            <tr class="tituloColumna">
                                <td><input id="checkItemArt" type="checkbox" onclick="selecAllChecks(this.checked,this.id,2);"/></td>
                                <td width="5%">Id</td>
                                <td>Unidad</td>
                                <td width="15%">Código</td>
                                <td width="35%" colspan="3">Descripción</td>
                                <td>F Requerida</td>
                                <td>Cantidad</td>
                                <td>Precio</td>
                                <td>Sub-Total</td>
                            </tr>
                            <tr id="trItemsSolicitudArt"></tr>
                        </table>
                    
                    <table width="100%" border="0" id="TabTotal" style="display:none">
                        <tr class="trResaltarTotal">
                            <td colspan="7" align="right" width="100%" class="tituloCampo">Precio Total:</td>
                            <td><input id="totalPrecio" name="totalPrecio" size="10" style="text-align:right; border:0px; color:#007F00" readonly="readonly"/></td>
                        </tr>
                    </table>
                    </td>
                </tr>
            </table>
        </fieldset>
        </td>
    </tr>
    <tr>
        <td>
            <fieldset>
                <legend class="legend">Datos Del Proveedor</legend>
                <table width="100%" border="0">
                    <tr>
                        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Proveedor Sugerido:</td>
                        <td align="left" colspan="3" >
                            <table id="tblProveedores" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td><input id="idProveedor" name="idProveedor" style="text-align:center" size="10" onblur="xajax_asignarEmpDepartamentoCento('Prov', this.value)"/></td>
                                    <td>
                                        <a class="modalImg" id="agregarProveedore" name="agregarProveedore" rel="#divFlotante4" onclick="abrirDiv(this,name);">
                                            <button id="btnAgregarProveedor" title="Seleccionar Proveedor" style="cursor:default" name="btnAgregarProveedor" type="button">
                                                <img src="../img/iconos/ico_pregunta.gif">
                                            </button>
                                        </a>	
                                    </td>
                                    <td><input id="nombreProveedor" name="nombreProveedor" style="text-align:center" size="65" readonly="readonly"/></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" class="tituloCampo">Justificación del Proveedor:</td>
                        <td align="left" colspan="3"><textarea id="justificacionProveedor" name="justificacionProveedor" style="width:90%;height:100%"></textarea></td>
                    </tr>
                    <tr>
                        <td align="right" class="tituloCampo">Observación:</td>
                        <td align="left" colspan="3"><textarea id="ObservacionProveedor" name="ObservacionProveedor" style="width:90%;height:100%"></textarea></td>
                    </tr>	
                </table>
            </fieldset>
        </td>
    </tr>
    <tr>
        <td>
            <fieldset>
                <legend class="legend">Para ser llenado para todo tipo de compra (excepto para las compras de material de stock del almacén)</legend>
                <table width="100%" border="0">
                    <tbody><tr class="">
                        <td align="right" class="tituloCampo">Sustitución o Adición:</td>
                        <td align="left">
                            <input type="radio" name="sustitucion" value="1" id="sustitucion1">Sustitución
                            <input type="radio" name="sustitucion" value="2" id="sustitucion2">Adición
                        </td>
                        <td align="right" class="tituloCampo">Presupuestado (S/N):</td>
                        <td align="left"><input type="checkbox" name="presupuestado" value="0" id="presupuestado0">Presupuestado</td>
                    </tr>
                    <tr>
                        <td align="right" class="tituloCampo">Justificación de la Compra:</td>
                        <td align="left" colspan="3"><textarea style="width:90%;height:100%" name="justificacionCompra" id="justificacionCompra" class="inputHabilitado"></textarea></td>
                    </tr>
                </tbody></table>
            </fieldset>
        </td>
    </tr>
    <tr id="tabAprobacion">
        <td>
            <fieldset>
                <legend class="legend">UNIDAD SOLICITANTE</legend>
                <table width="100%" border="0">
                    <tbody><tr>
                        <td align="center" colspan="2" class="tituloCampo"><b>Solicitado Por</b></td>
                        <td align="center" colspan="2" class="tituloCampo"><b>Aprobado Por</b></td>
                    </tr>            
                    <tr>
                        <td width="15%" align="right" class="tituloCampo">Nombre y Firma:</td>
                        <td width="15%" align="left" id="tdNombFirmaS"></td>
                        <td width="15%" align="right" class="tituloCampo">Nombre y Firma:</td>
                        <td width="15%" align="left" id="tdNombFirmaA"></td>
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
                </tbody></table>
             </fieldset>
        </td>
    </tr>
    <tr id="tabAprobacion2">
        <td>
            <fieldset>
                <legend class="legend">GERENCIA DE COMPRAS</legend>
                <table width="100%" border="0">
                    <tbody><tr>
                        <td align="center" colspan="2" class="tituloCampo"><b>Conformado Por</b></td>
                        <td align="center" colspan="2" class="tituloCampo"><b>Procesado Por</b></td>
                    </tr>            
                    <tr>
                        <td width="25%" align="right" class="tituloCampo">Nombre y Firma:</td>
                        <td width="25%" align="left" id="tdNombFirmaC"></td>
                        <td width="25%" align="right" class="tituloCampo">Nombre y Firma:</td>
                        <td width="25%" align="left" id="tdNombFirmaP"></td>
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
                </tbody></table>
            </fieldset>
        </td>
    </tr>
    </table>
    
    <table width="100%" border="0">
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
            Guardar 
        </button>
        <button  id="btnCancelar" title="Cancelar Solicitud" style="cursor:default" class="close" name="btnCancelar" type="button">
            Cerrar
        </button>
        </td>
    </tr>
    </table>
    </form>
</div> <!--FIN NUEVA SOLICITUD-->

<!--EMPRESA,	DEPARTAMENTO,	UNIDAD CENTRO DE COSTO-->
<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" name="tdFlotanteTitulo2" width="100%" align="left"></td></tr></table></div>
    <table border="0" width="960">
    <tr>
        <td align="right">
            <form id="frmBuscar2" name="frmBuscar2" style="margin:0" onsubmit="return false;">
            <table>
            <tr>
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td>
                    <input id="textCriterio" name="textCriterio" class="inputCompletoHabilitado" onkeyup="byId('btnBuscarCentroCosto').click();"/>
                </td>
                <td>
                    <button id="btnBuscarCentroCosto" name="btnBuscarCentroCosto" title="Buscar" style="cursor:default" type="button">Buscar</button>
                    <button id="btnLimpiar" title="Limpiar" style="cursor:default" name="btnLimpiar" type="button" onclick="document.forms['frmBuscar2'].reset(); byId('btnBuscarCentroCosto').click();">Limpiar</button>
                </td>
            </tr>
            </table>
            </form>
        </td>
    </tr>
    <tr>
        <td id="empDepaUnidadCentroCosto"></td>
    </tr>
    <tr>
        <td align="right"><hr/>
            <button  id="btnCancelar2" title="Cancelar Solicitud" style="cursor:default" class="close" name="btnCancelar2" type="button" onclick="document.forms['frmBuscar2'].reset();">Cerrar</button>            
        </td>
    </tr>
    </table>
</div> <!--FIN EMPRESA DEPARTAMENTO CENTRO COSTO-->

<!--AGREGA ARTICULOS-->
<div id="divFlotante3" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo3" class="handle"><table><tr><td id="tdFlotanteTitulo3" name="tdFlotanteTitulo3" width="100%" align="left"></td></tr></table></div>
    
    <table width="960">
    <tr>
        <td>
            <form id="frmbuscarArt" name="frmbuscarArt" style="margin:0" onsubmit="return false;">
            <table border="0" align="right">
            <tr>
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input id="textCriterioArt" name="textCriterioArt" class="inputHabilitado" onkeyup="byId('btnBuscarArt').click();"/></td>
                <td>
                    <button id="btnBuscarArt" title="Buscar" onclick="xajax_buscarArticulo(xajax.getFormValues('frmbuscarArt'));" style="cursor:default" name="btnBuscarArt" type="button">Buscar</button>
                    <button id="btnLimpiarArt" title="Limpiar" style="cursor:default" name="btnLimpiarArt" type="button" onclick="document.forms['frmbuscarArt'].reset(); byId('btnBuscarArt').click();">Limpiar</button>
                </td>
             </tr>
            </table>
            </form>
        </td>
    </tr>
    <tr>
        <td>
            <form id="frmLstdArt" name="frmLstdArt" style="margin:0" onsubmit="return false;">
            <table width="100%" border="0">
            <tr>
                <td id="" colspan="2">
                    <div id="tdListArticulo"></div>
                </td>
            </tr>
            <tr>
                <td align="right">
                    <hr/>
                
                <button  id="btnCancelar3" name="btnCancelar3" type="button" title="Cancelar Articulo" style="cursor:default" class="close" onclick="document.forms['frmLstdArt'].reset();document.forms['frmbuscarArt'].reset();">Cerrar</button>            
                </td>
            </tr>
            </table>
            </form>
        </td>
    </tr>
    </table>   
</div> <!--FIN ARTICULO-->

<!--LISTADO DE PROVEEDORES-->
<div id="divFlotante4" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo4" class="handle"><table width="700"><tr><td id="tdFlotanteTitulo4" width="100%" align="left">Proveedores</td></tr></table></div>
    
    <table width="960"> <!--CONTIENE EL BUSCADOR Y LISTADO PROVEEDORES-->
    <tr>
        <td>
            <form id="frmBuscarProveedor" name="frmBuscarProveedor" style="margin:0" onsubmit="return false;">
            <table align="right"> <!--CONTIENE EL BUSCADOR DEL LISTADO-->
            <tr>
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="textCriterioProveed" name="textCriterioProveed" class="inputHabilitado" onkeyup="byId('BtnBuscarProvee').click();" /></td>
                <td>
                    <button id="BtnBuscarProvee" name="BtnBuscarProvee" onclick="xajax_buscarProveedor(xajax.getFormValues('frmBuscarProveedor'));">Buscar</button>
                    <button id="BtnLimpiarProvee" name="BtnLimpiarProvee" onclick="document.forms['frmBuscarProveedor'].reset(); byId('BtnBuscarProvee').click();">Limpiar</button>
                </td>
            </tr>
            </table>
            </form>
        </td>
    </tr>
    <tr><td id="tdListProveedores"></td></tr><!--LISTADO PROVEEDORES-->
    <tr>
        <td align="right"><hr />
            <button id="btnCerrarListaProveedor" class="close" >Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>

xajax_listadoSolicitudCompra(0,"","","<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>||||<?php echo date("01-m-Y"); ?>|<?php echo date(spanDateFormat); ?>");
xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_combLstEstCompra();
xajax_combLstTipCompra();

byId('txtFechaDesde').value = "<?php echo date(spanDateFormat,strtotime(date("01-m-Y"))); ?>";
byId('txtFechaHasta').value = "<?php echo date(spanDateFormat); ?>";

$("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
$("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});

new JsDatePick({
	useMode:2,
	target:"txtFechaDesde",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"armygreen"
});

new JsDatePick({
	useMode:2,
	target:"txtFechaHasta",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"armygreen"
});

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

var theHandle = document.getElementById("divFlotanteTitulo3");
var theRoot   = document.getElementById("divFlotante3");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo4");
var theRoot   = document.getElementById("divFlotante4");
Drag.init(theHandle, theRoot);

</script>