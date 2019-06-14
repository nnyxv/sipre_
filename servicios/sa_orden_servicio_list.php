<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("sa_orden_servicio_list"))) {//sa_orden_servicio_list nuevo gregor //sa_orden anterior
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

include("controladores/ac_iv_general.php");
include("controladores/ac_sa_orden_servicio_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	
        <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Listado de Ordenes de Servicio</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
                
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    <link rel="stylesheet" type="text/css" href="css/sa_general.css" />
    
    <link rel="stylesheet" type="text/css" href="../js/domDragServicios.css">
    
    <script type="text/javascript" language="javascript" src="control/lib/jquery-1.3.2.min.js"></script>
    
    <script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
        
    <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/> 
    <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
    
    <script>
	//var $ = jQuery.noConflict();//IMPORTANTE
	function abrir(id,numeroOrden) {//id = id_orden
		$('key').value= "";
		$('key_window').style.visibility= 'visible';
		$('key_window').style.display= '';
		centrarDiv($('key_window'));
		$('key_title').innerHTML= "Introduzca la clave  (Orden Nro: "+numeroOrden+")";//cambiado antes id
		$('key').focus();
		document.form_orden.id_orden.value= id;
		document.form_orden.ocultoNumeroOrden.value= numeroOrden;
	}

	function abrirMecanico(id_tempario, idDetOrdenTempario, idEmpresaOrden) {
		$('id_tempario').value= id_tempario;
		$('hddIdDetOrdenTempario').value= idDetOrdenTempario;
		
		$('amp_window').style.visibility= 'visible';
		$('amp_window').style.display= '';
		centrarDiv($('amp_window'));
		xajax_listMecanicos(idEmpresaOrden);
	}

	function abrirMo() {
		$('orden_window').style.visibility= 'visible';
		$('orden_window').style.display= '';
		//$('capa_id_orden').innerHTML= document.form_orden.id_orden.value;//id_orden en titulo de temparios sin asignar
		$('capa_id_orden').innerHTML= document.form_orden.ocultoNumeroOrden.value;//numero orden gregor
		centrarDiv($('orden_window'));
		xajax_listMoOrden(document.form_orden.id_orden.value, "PENDIENTE", "lista_posiciones");
		//xajax_listMoOrden(document.form_orden.id_orden.value, "TERMINADO", "lista_posiciones_asignadas");
	}

	function guardarFinalizarOrden() {
		xajax_guardarFinalizarOrden(document.form_orden.id_orden.value);
	}

	function verificar(pass) {
		xajax_verficarPassSinMagnetoplano(pass);
	}

	function verificarAsignarMecanico() {
		if($('id_mecanico_mp').value == -1) {
			alert('Debe seleccionar un mecanico');
			$('id_mecanico_mp').focus();
		} else {
			xajax_asignarMecanico($('id_mecanico_mp').value, document.form_orden.id_orden.value, $('id_tempario').value, $('hddIdDetOrdenTempario').value);
		}
	}
        
        function habilitarBtnFinalizar(idVendedor){
            if(idVendedor === ""){
                $('boton_asignar_mp2').disabled = true;
            }else{
                $('boton_asignar_mp2').disabled = false;
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
        	<td class="tituloPaginaServicios">Ordenes de Servicio</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
		</tr>
        <tr>
        	<td>
            	<table align="left">
                <tr>
                	<td>
                        <button type="button" id="btnNuevo" name="btnNuevo" class="noprint" onclick="window.open('sa_orden_form.php?doc_type=2&id=&ide=<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>&acc=1','_self');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_new.png" alt="new"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </td>
                    <td>
                    	<button type="button" onclick="xajax_exportarOrdenes(xajax.getFormValues('frmBuscar'));" class="noprint" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>Exportar</td></tr></table></button>
					</td>
                </tr>
                </table>
            	
			<form id="frmBuscar" name="frmBuscar" onsubmit="$('btnBuscar').click();  return false;" style="margin:0">
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
                    <td id="tdlstEmpleadoVendedor" colspan="2">
                        <select id="lstEmpleadoVendedor" name="lstEmpleadoVendedor">
                            <option value="-1">[ Todos ]</option>
                        </select>
                    </td>
				</tr>
                <tr align="left">
                	<td></td>
                	<td></td>
                	<td align="right" class="tituloCampo">Tipo de Orden:</td>
                    <td id="tdlstTipoOrden">
                        <select id="lstTipoOrden" name="lstTipoOrden">
                            <option value="-1">[ Todos ]</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo">Estado de Orden:</td>
                    <td id="tdlstEstadoOrden" colspan="2">
                        <select id="lstEstadoOrden" name="lstEstadoOrden">
                            <option value="-1">[ Todos ]</option>
                        </select>
                    </td>
				</tr>
                <tr align="left">
                	<td></td>
                	<td></td>
                	<td></td>
                	<td></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="$('btnBuscar').click();"></td>
                    <td>
                    	<input type="button" class="noprint" id="btnBuscar" onclick="xajax_buscarOrden(xajax.getFormValues('frmBuscar')); //limpiar_select();" value="Buscar" />
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
        <tr class="noprint">
        	<td>
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
					<td align="center">
                        <table border="0">
                        <tr align="left">
                            <td id="tdImgAccionGenerarPresupuesto"><img src="../img/iconos/generarPresupuesto.png"/></td>
                            <td id="tdDescripAccionGenerarPresupuesto">Generar Presupuesto</td>
                            <td>&nbsp;</td>
                            <td id="tdImgAccionAprobacionOrden"><img src="../img/iconos/aprobar_presup.png"/></td>
                            <td id="tdDescripAccionAprobacionOrden">Aprobaci&oacute;n</td>
                            <td>&nbsp;</td>
                            <td id="tdImgAccionVerOrden"><img src="../img/iconos/ico_view.png"/></td>
                            <td id="tdDescripAccionVerOrden">Ver Orden</td>
                            <td>&nbsp;</td>
                            <td id="tdImgAccionEdicionOrden"><img src="../img/iconos/ico_edit.png"/></td>
                            <td id="tdDescripAccionEdicionOrden">Edici&oacute;n Orden</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/time_go.png"/></td>
                            <td>Finalizar Orden</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_print.png"/></td>
                            <td>Imprimir Orden</td>
                        </tr>
                        </table>
                    </td>
				</tr>
				</table>
            </td>
        </tr>
        <tr class="noprint">
        	<td>
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
					<td align="center">
                        <table border="0" id="tblEstadosOrden">
                        <tr align="left" height="22">
                            <td bgcolor="#00FF00" id="tdImgAccionVerOrden" width="18" class="punteadoCelda"></td>
                            <td id="tdDescripAccionVerOrden">Abierta</td>
                            
                            <td bgcolor="#CD4ADF" id="tdImgAccionVerOrdenDesactivado" width="18" class="punteadoCelda"></td>
                            <td id="tdDescripAccionVerOrdenDesactivado">Diagn&oacute;stico</td>
                            
                            <td bgcolor="#57205F" id="tdImgAccionAprobacionOrden" width="18" class="punteadoCelda"></td>
                            <td id="tdDescripAccionAprobacionOrden">Diagn&oacute;stico Finalizado</td>
                            
                            <td bgcolor="#B3BF5F" id="tdImgAccionAprobacionOrdenDesactivado" width="18" class="punteadoCelda"></td>
                            <td id="tdDescripAccionAprobacionOrdenDesactivado">Por Aprobacion de Presupuesto</td>
                            
                            <td bgcolor="#595F2F" id="tdImgAccionEdicionOrden" width="18" class="punteadoCelda"></td>
                            <td id="tdDescripAccionEdicionOrden">Presupuesto Aprobado</td>
                            
                            <td bgcolor="#EFDB00" id="tdImgAccionEdicionOrdenDesactivado" width="18" class="punteadoCelda"></td>
                            <td id="tdDescripAccionEdicionOrdenDesactivado">Solicitud Repuesto</td>
                            
                            <td bgcolor="#00B050" id="tdImgAccionGenerarPresupuesto" width="18" class="punteadoCelda"></td>
                            <td id="tdDescripAccionGenerarPresupuesto">Proceso</td>
                        </tr>
                        <tr align="left" height="22">
                            <td bgcolor="#FF0000" id="tdImgAccionVerOrden" width="18" class="punteadoCelda"></td>
                            <td id="tdDescripAccionVerOrden">Tiempo Operacion Vencido</td>
                            
                            <td bgcolor="#6F4831" id="tdImgAccionVerOrdenDesactivado" width="18" class="punteadoCelda"></td>
                            <td id="tdDescripAccionVerOrdenDesactivado">Detenida por Mano de Obra</td>
                            
                            <td bgcolor="#8F5D3F" id="tdImgAccionAprobacionOrden" width="18" class="punteadoCelda"></td>
                            <td id="tdDescripAccionAprobacionOrden">Detenida por Herramientas</td>
                            
                            <td bgcolor="#AF724D" id="tdImgAccionAprobacionOrdenDesactivado" width="18" class="punteadoCelda"></td>
                            <td id="tdDescripAccionAprobacionOrdenDesactivado">Detenida por Puesto</td>
                            
                            <td bgcolor="#CF865B" id="tdImgAccionEdicionOrden" width="18" class="punteadoCelda"></td>
                            <td id="tdDescripAccionEdicionOrden">Detenida por Repuesto</td>
                            
                            <td bgcolor="#EF9B69" id="tdImgAccionEdicionOrdenDesactivado" width="18" class="punteadoCelda"></td>
                            <td id="tdDescripAccionEdicionOrdenDesactivado">Detenida por TOT</td>
                            
                            <td bgcolor="#CFCFCF" id="tdImgAccionGenerarPresupuesto" width="18" class="punteadoCelda"></td>
                            <td id="tdDescripAccionGenerarPresupuesto">Trabajo Finalizado</td>
                        </tr>
                        <tr align="left" height="22">
                            <td bgcolor="#9F9F9F" id="tdImgAccionVerOrden" height="18" class="punteadoCelda"></td>
                            <td id="tdDescripAccionVerOrden">Control de Calidad</td>
                            
                            <td bgcolor="#6F6F6F" id="tdImgAccionVerOrdenDesactivado" height="18" class="punteadoCelda"></td>
                            <td id="tdDescripAccionVerOrdenDesactivado">Prueba de Carretera</td>
                            
                            <td bgcolor="#5FC6FF" id="tdImgAccionAprobacionOrden" height="18" class="punteadoCelda"></td>
                            <td id="tdDescripAccionAprobacionOrden">Lavado</td>
                            
                            <td height="18">&nbsp;</td>
                            <td></td>
                            
                            <td height="18">&nbsp;</td>
                            <td></td>
                            
                            <td id="tdImgAccionEdicionOrdenDesactivado" height="18">&nbsp;</td>
                            <td id="tdDescripAccionEdicionOrdenDesactivado">&nbsp;</td>
                            
                            <td id="tdImgAccionGenerarPresupuesto" height="18">&nbsp;</td>
                            <td id="tdDescripAccionGenerarPresupuesto">&nbsp;</td>
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

<div class="window" id="key_window" style="z-index:100;top:-1000px;left:0px;max-width:400px;min-width:400px;visibility:hidden;border-color:#FEB300;">
	<div class="title" id="title_key_window" style="background:#FEE8B3;color:#000000;">
		<div class="key_pass" id="key_title" style="padding-left:24px;"></div>
	</div>
	<div class="content">
		<div class="nohover">
			<table class="insert_table">
			<tbody>
				<tr>
					<td width="30%"  class="label">Clave:</td>
					<td class="field" style="text-align:center;">
						<input style="width:95%;border:0px;" type="password" onkeyup="if(event.keyCode == 13) {  verificar($('key').value); }" name="key" id="key" maxlength="30" onkeypress="" />
					</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align:right;padding:2px;">
						<span style="padding:2px;">
							<button onclick="verificar($('key').value);"><img alt="aceptar" src="../img/iconos/select.png" class="image_button" />Aceptar</button>
						</span>
						<span style="padding:2px;">
							<button onclick="$('key_window').style.display='none';"><img alt="cerrar" src="../img/iconos/delete.png" class="image_button" />Cancelar</button>
						</span>
					</td>
				</tr>
			</tbody>
			</table>
		</div>
	</div>
	<img class="close_window" src="../img/iconos/close_dialog.png" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="$('key_window').style.display='none';" border="0" />
</div>

<div class="window" id="orden_window" style="z-index:3;left:0px;top:-1000px;max-width:960px;min-width:960px;visibility:hidden;">
    <div class="title" id="title_orden_window">
    	Orden #<span id="capa_id_orden"></span>
    </div>
    <div class="content">
        <form id="form_orden" name="form_orden" onSubmit="return false;">
            <input type="hidden" id="id_orden" name="id_orden"/>
             <input type="hidden" id="ocultoNumeroOrden" name="ocultoNumeroOrden"/>
        </form>
    	<hr>
        <div id="capa_asignaciones" class="nohover">
            <fieldset  style=""  class="no_estimado">
            	<legend id="tituloLegend">Posiciones de trabajo:</legend>
                <form name="form_mp_group" id="form_mp_group" onSubmit="return false;">
                    <div id="lista_posiciones" style="width:930px;max-height:130px;overflow:auto;margin:auto;padding-left:1px;"></div>
                </form>
            	<div style="clear:both;padding:2px;">
            	<!--<button onClick="fcall_preparar_grupo();" ><img src="../img/mp_asigna.png" class="image_button" />Asignar grupo</button>-->
            	</div>
            </fieldset>
            
<!--            <fieldset  style="" class="no_estimado">
				<legend>Posiciones de trabajo asignadas:</legend>
                <form name="form_mp_group_reanuda" id="form_mp_group_reanuda" onSubmit="return false;">
                    <input type="hidden" id="id_mp_desactivar" name="id_mp_desactivar" />
                    <input type="hidden" id="resta_estimacion" name="resta_estimacion" />
                    <div id="lista_posiciones_asignadas" style="width:930px;max-height:150px;overflow:auto;margin:auto;padding-left:1px;">
                    </div>
                </form>
            </fieldset>-->
        </div>
		<table class="insert_table">
        <tr>
            <td align="right"><hr/>
            <button title="Finalizar Orden" onClick="guardarFinalizarOrden();" id="boton_asignar_mp2" name="boton_asignar_mp2"/><img border="0"  src="../img/iconos/accept.png" class="image_button"/>Finalizar Orden</button>
            </td>
        </tr>
        </table>
	</div>
    <img class="close_window" src="../img/iconos/close_dialog.png" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="$('orden_window').style.display='none';" border="0"/>
</div>

<div class="window" id="amp_window" style="z-index:3;top:-1000px;left:0px;max-width:600px;min-width:600px;visibility:hidden;">
	<div class="title" id="title_amp_window">
		Asignar Mec&aacute;nicos
	</div>
	<div class="content">
        <form id="form_mecanico" name="form_mecanico" onSubmit="return false;">
            <input type="hidden" id="id_tempario" name="id_tempario"/>
            <input type="hidden" id="hddIdDetOrdenTempario" name="hddIdDetOrdenTempario"/>
        </form>
        <div class="nohover">
            <table class="insert_table">
            <tbody>
            <tr>
                <td class="label">Mec&aacute;nico</td>
                <td class="field"><div id="field_mecanico_mp"></div></td>
            </tr>
            <tr>
                <td colspan="2" align="right">
                <button title="Asignar" onClick="verificarAsignarMecanico();" id="boton_asignar_mp2" name="boton_asignar_mp2"/><img border="0" alt="Solo Asignar" src="../img/iconos/people1.png" class="image_button"/>Asignar</button>
                </td>
            </tr>
            </tbody>
            </table>
		</div>
	</div>
	<img class="close_window" src="../img/iconos/close_dialog.png" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="$('amp_window').style.display='none';" border="0" />
</div>
<script>
xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>','onchange="$(\'btnBuscar\').click(); cargarTipoOrdenEmpresa(this.value); cargarEmpleadoEmpresa(this.value); "',0,0,0,"unico"); //buscador
//xajax_cargaLstEmpresaBuscar('<?php //echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstEmpleado('','lstEmpleadoVendedor','tdlstEmpleadoVendedor');
xajax_cargaLstTipoOrden();
xajax_cargaLstEstadoOrden();

function cargarTipoOrdenEmpresa(empresa){
	xajax_cargaLstTipoOrden('',empresa);
}

function cargarEmpleadoEmpresa(empresa){
	xajax_cargaLstEmpleado('','lstEmpleadoVendedor','tdlstEmpleadoVendedor',empresa);
}

xajax_listadoOrdenes(0,'numero_orden','DESC');

	var theHandle = document.getElementById("title_key_window");
	var theRoot   = document.getElementById("key_window");
	Drag.init(theHandle, theRoot);

	var theHandle = document.getElementById("title_orden_window");
	var theRoot   = document.getElementById("orden_window");
	Drag.init(theHandle, theRoot);
	
	var theHandle = document.getElementById("title_amp_window");
	var theRoot   = document.getElementById("amp_window");
	Drag.init(theHandle, theRoot);



	/*
		
	function limpiar_select(){		
	
				//var select_option = $("#lstEmpresa").find("option:not([selected])").hide();				
				//$("#lstEmpresa").find("optgroup").removeAttr('label');
				
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
					
			}		*/	
			
	
			
			
</script>