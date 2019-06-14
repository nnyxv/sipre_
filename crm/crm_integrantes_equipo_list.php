<?php
require_once("../connections/conex.php");
	
session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("crm_actividad_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');//clase xajax
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');//Configuranto la ruta del manejador de script

include("controladores/ac_crm_integrantes_equipo_list.php"); //contiene todas las funciones xajax
include("../controladores/ac_iv_general.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. CRM - Equipos</title>
	<link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css" />
    
	<link rel="stylesheet" type="text/css" href="../js/domDragCrm.css" />
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
	<script>
//ABRE NUEVO FORMULARIO
function abrirNuevo(idObj, nanoObj, fomr, valor) {
	document.getElementById(fomr).reset();
	validar = true;
	byId('hddBuscar').value = "";
	byId('txtIdEmpresa').className = 'inputInicial';
	byId('txtNombreEquipo').className = 'inputHabilitado';
	byId('areaEquipoDescripcion').className = 'inputHabilitado'; 
	acciones('btnInsertarEmp','disabled',false);
	switch(nanoObj){
		case "aNuevo":
			byId('hddidEquipo').value = "";
			IdObjTitulo= 'tdFlotanteTitulo';
			titulo = "Agregar Equipo";
			xajax_cargarEquipo();
				break;	
		case "aEditar": 
			IdObjTitulo = 'tdFlotanteTitulo'
			titulo = "Editar Equipo";
			xajax_cargarEquipo(valor);
				break;	
		case "aJefeEquipo"://jefe equipo
			IdObjTitulo = 'tdTituloJefeEquipo'
			titulo = "Seleccion Empleado - Jefe de Equipo";
			byId('hddBuscar').value = "asignarJefeEquipo";
			xajax_listaEmpleado('','','',byId('txtIdEmpresa').value+'|'+byId('comboxTipoEquipo').value+'||'+'asignarJefeEquipo');
				break;
		case "aAgrIntegrante":
			IdObjTitulo = 'tdTituloJefeEquipo'
			titulo = "Seleccion Empleado - Integrante de Equipo";
			byId('hddBuscar').value = "asignarIntegranteEquipo";
			var validar = (validarForm(1) == false) ? false : true;
				break;	
	}

	(validar == false) ? "" : openImg(idObj);
	byId(IdObjTitulo).innerHTML = titulo;
}   

function acciones(idObjeto,accion,valor){
	switch (accion){
		case "disabled":document.getElementById(idObjeto).disabled = valor; break;
		case "show": document.getElementById(idObjeto).style.display = ''; break;
		case "hide": document.getElementById(idObjeto).style.display = 'none'; break;	
		case "checked": document.getElementById(idObjeto).checked = valor; break;	
	}
}

function RecorrerForm(nameFrm,accion,valor,arrayBtn){ 
	var frm = document.getElementById(nameFrm);
	var sAux= "";
	for (i=0; i < frm.elements.length; i++)	{// RECORRE LOS ELEMENTOS DEL FROM
		if(frm.elements[i].type == 'button' || frm.elements[i].type == 'submit'){// SI SON DE TIPO BUTTON Y SUBMIT 
			sAux = frm.elements[i].id;
			if(arrayBtn != "" && arrayBtn != null){// PARA LOS BOTONOES QUE NO DEBE HACER NINGUNA ACCION
				for(a = 0; a < arrayBtn.length; a++){
					if(sAux != arrayBtn[a]){
						acciones(sAux,accion,valor);//ACCION = TRUE DESAHABILITA; ACCION = FALSE HABILITA; 
					}
				}
			}else{
				acciones(sAux,accion,valor);
				//document.getElementById(sAux).disabled = accion; //ACCION = TRUE DESAHABILITA; ACCION = FALSE HABILITA;
			}
		}
	}	
}
	
function validarEliminar(id_equipo){ 
	//LLAMA LA FUNCION ELIMINAR EN XAJAX
	if (confirm('Seguro desea eliminar este registro?') == true) {
		xajax_eliminarEquipo(id_equipo, xajax.getFormValues('frmListaConfiguracion'));
	}
}
    //ELIMINA LOS INTEGRANTES DE UN GRUPO 
function validarEliminarIntegrante(id_integrante_equipo, idEquipo){ 
	//LLAMA LA FUNCION ELIMINAR EN XAJAX
	if (confirm('Esta seguro que desea eliminar este integrante?') == true) {
		xajax_eliminarIntegrante(id_integrante_equipo, idEquipo);
	}
}
		
//LISTA LAS EMPRESAS
function formListaEmpresa(valor, valor2) {
	document.forms['frmBuscarEmpresa'].reset();
	
	byId('hddObjDestino').value = valor;
	byId('hddNomVentana').value = valor2;
	
	byId('btnBuscarEmpresa').click();
	
	tituloDiv1 = 'Empresas';
	byId('tdFlotanteTitulo2').innerHTML = tituloDiv1;
}

    //PARA VALIDAR FORMULARIO
function validarForm(valor) {
	if (validarCampo('txtIdEmpresa','t','') == true 
	&& validarCampo('txtNombreEquipo','t','') == true
	&& validarCampo('comboxTipoEquipo','t','listaExceptCero') == true
	&& validarCampo('idHiddJefeEquipo','t','') == true
	&& validarCampo('textJefeEquipo','t','') == true
	&& validarCampo('listEstatus','t','listaExceptCero') == true) {
		switch (valor){
			case 1: xajax_agregarIntegrante(xajax.getFormValues('fomrEquipo')); break;
			default: xajax_guadarFormEquipo(xajax.getFormValues('fomrEquipo')); break;
		}
	valida = true;
	}  else {
		validarCampo('txtIdEmpresa','t','');
		validarCampo('txtNombreEquipo','t','');
		validarCampo('comboxTipoEquipo','t','listaExceptCero');
		validarCampo('idHiddJefeEquipo','t','');
		validarCampo('textJefeEquipo','t','');
		validarCampo('listEstatus','t','listaExceptCero');
		
		RecorrerForm('fomrEquipo','disabled',false);
		
		alert("Los campos señalados en rojo son requeridos");
		valida = false;
	}
	
	return false;
}
	</script>
</head>

<body class="bodyVehiculos" ><!--onload="byId('btnBuscar').click();"-->
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_crm.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%"> <!--tabla principal-->
            <tr> <td class="tituloPaginaCrm">Equipos</td> </tr>
            <tr align="left"> 
          		<td>&nbsp;</td>
            </tr>
            <tr>
                <td>
                    <table align="left" border="0">
                    <tr>
                        <td valign="top">
                            <a class="modalImg" id="aNuevo" name="aNuevo" rel="#divFlotante" onclick="abrirNuevo(this,this.name,'fomrEquipo','');">
                                <button type="button" style="cursor:default" >
                                    <table align="center" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td><img class="puntero" src="../img/iconos/ico_new.png" title="Editar"/></td>
                                        <td>&nbsp;</td>
                                        <td>Nuevo</td>
                                    </tr>
                                    </table>
                                </button>
                            </a>
                        </td>
                    </tr>
                    </table>
                    
                <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                    <table align="right" border="0">
                    <tr align="left">
                        <td align="right" class="tituloCampo">Empresa:</td>
                        <td id="tdlstEmpresa" colspan="3"></td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo" width="120">Tipo Equipo:</td>
                        <td id="tdBusLstTipaEquipo"></td>
                        <td align="right" class="tituloCampo" width="120">Estatus:</td>
                        <td>
                            <select id="LstEstatusBus" name="LstEstatusBus" class="inputHabilitado">
                                <option value="">[ Seleccione ]</option>
                                <option value="0">Inactivo</option>
                                <option value="1"  selected="selected">Activo</option>
                            </select>
                        </td>
                    </tr>
                    <tr align="left">
                        <td></td>
                        <td></td>
                        <td align="right" class="tituloCampo">Criterio:</td>
                        <td><input type="text" id="textCriterio" name="textCriterio" class="inputHabilitado"/></td>
                        <td>
                            <button type="button" id="btnBuscar" onclick="xajax_buscarEquipo(xajax.getFormValues('frmBuscar'));">Buscar</button>
                            <button type="button" onclick="this.form.reset(); byId('btnBuscar').click();">Limpiar</button>
                        </td>
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
                	<form id="frmListaConfiguracion" name="frmListaConfiguracion" style="margin:0">
                    <div id="divListEquipo" style="width:100%"></div> <!--contien la consulta-->
                </form>
                </td>
            </tr>
            <tr>
                <td>
                    <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%"> <!--cotnie la tabla descripcion-->
                    <tr>
                        <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                        <td align="center">
                            <table > <!--cotnien descripcion -->
                            <tr>
                                <td><img src="../img/iconos/ico_verde.gif" /></td><td>Activo</td>
                                <td>&nbsp;</td>
                                <td><img src="../img/iconos/ico_rojo.gif" /></td><td>Inactivo</td>
                                <td>&nbsp;</td>
                            </tr>
                            </table> <!--fin de descripcion-->
                        </td>
                    </tr>
                    </table> <!--fin de la tabla descripcion-->
                </td>
            </tr>
        </table> <!--fin de la tabla principal-->
    </div> <!--fin del cuerpo de la pag-->
   
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<!--NUEVO EQUIPO-->
<div id="divFlotante" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;" class="root">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    <form id="fomrEquipo" name="fomrEquipo" style="margin:0" onsubmit="return false;">
        <table border="0" width="760">
            <tr>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                <td>
                    <table cellpadding="0" cellspacing="0">
                        <tr>
                        <td>
                            <input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:center;"/>
                        </td>
                        <td><!---->
                            <a class="modalImg" rel="#divFlotante2" onclick="openImg(this); formListaEmpresa('Empresa', 'ListaEmpresa');">
                                <button type="button" id="btnInsertarEmp" name="btnInsertarEmp" style="cursor:default" disabled="disabled" title="Listar">
                                    <img src="../img/iconos/ico_pregunta.gif" />
                                </button>
                            </a>
                        </td>
                            <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nombre:</td>
                <td><input type="text" id="txtNombreEquipo" name="txtNombreEquipo" size="50"/></td>
            </tr>
            <tr>
            </tr><tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Equipo de:</td>
                <td id="tdTipoEquipo"> </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Jefe de Equipo:</td>
                <td>
                    <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <input type="text" id="idHiddJefeEquipo" name="idHiddJefeEquipo" readonly="readonly" size="6" style="text-align:center;"/></td>
                            <td>
                                <a class="modalImg" id="aJefeEquipo" name="aJefeEquipo" rel="#divFlotante3" onclick="abrirNuevo(this,this.name,'frmEmpleado','');">
                                    <button id="buttonJefeEquipo" name="buttonJefeEquipo" title="Listar" type="button" disabled="disabled">
                                        <img src="../img/iconos/ico_pregunta.gif"/>
                                    </button>
                                </a>
                        	</td>
                        	<td><input type="text" id="textJefeEquipo" name="textJefeEquipo" readonly="readonly" size="45"/></td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Descripcion:</td>
                <td><textarea name="areaEquipoDescripcion" id="areaEquipoDescripcion" cols="30" rows="2"></textarea></td>
            </tr>  
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estatus:</td>
                <td>
                    <select name="listEstatus" id="listEstatus" class="inputHabilitado">
                        <option value="">[ Seleccione ]</option>
                        <option value="0">Inactivo</option>
                        <option value="1">Activo</option>
                    </select>
                </td>
            </tr>
            <tr id="trAddIntegrante" style="">
            	 <td colspan="2">
                 	<fieldset>	<legend class="legend">Integrante Equipo</legend>
                    	<table width="100%">
                        	<tr align="left">
                            	<td>
                                <a class="modalImg" id="aAgrIntegrante" name="aAgrIntegrante" rel="#divFlotante3" onclick="abrirNuevo(this, this.name, 'frmEmpleado', '')">
                                    <button type="button" id="btnAgrIntegrante" name="btnAgrIntegrante">
                                        <table align="center" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td><img class="puntero" src="../img/iconos/add.png" title="Agregar Integrante"/></td>
                                                <td>&nbsp;</td>
                                                <td>Agregar</td>
                                            </tr>
                                        </table>
                                    </button>
                                </a>
                                <button type="button" id="btnElimIntegrante" name="btnElimIntegrante" onclick="xajax_eliminarIntegrante(xajax.getFormValues('fomrEquipo'), 1);">
                                    <table align="center" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td>&nbsp;</td>
                                            <td><img class="puntero" src="../img/iconos/delete.png" title="Eliminar Integrante"/></td>
                                            <td>&nbsp;</td>
                                            <td>Eliminar</td>
                                        </tr>
                                    </table>
                                </button>
                            </td></tr>
                            <tr>
                            	<td>
                                	<table border="0" width="100%">
                                    	<tr>
                                        	<td>
                                                <div style="  max-height: 300px; overflow: auto;">
                                                    <table width="100%" cellspacing="0" cellpadding="0">
                                                        <tr align="center" class="tituloColumna">
                                                            <td><input id="checkItemIntegrante" type="checkbox" onclick="selecAllChecks(this.checked,this.id,2);" /></td>
                                                            <td>Id</td>
                                                            <td>Cedula</td>
                                                            <td>Nombre Empleado</td>
                                                            <td>Cargo Empleado</td>
                                                            <td></td>
                                                        </tr>
                                                        <tr id="trItmIntegrante"></tr>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                        	<td>
                                                <table width="100%" class="divMsjInfo2" cellspacing="0" cellpadding="0">
                                                    <tr>
                                                    <td width="25"><img width="25" src="../img/iconos/ico_info.gif" /></td>
                                                    <td align="center">
                                                    <table>
                                                    <tr>
                                                    <td><img src="../img/iconos/ico_verde.gif" /></td>
                                                    <td>Activo</td>
                                                    <td>&nbsp;</td>
                                                    <td><img src="../img/iconos/ico_rojo.gif" /></td>
                                                    <td>Inactivo</td>
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
                        </table>
                    </fieldset>
                 </td>
            </tr>
            <tr align="right">
                <td colspan="2"><hr />
                    <input type="hidden" id="hddidEquipo" name="hddidEquipo" />
                    <button type="button" id="butGuardarEquipo" name="butGuardarEquipo" onclick="validarForm();RecorrerForm('fomrEquipo','disabled',true);">Guardar</button>
                    <button type="button" id="butCerrarEquipo" name="butCerrarEquipo" class="close">Cancelar
                    </button>
                </td>
            </tr>
        </table>
    </form> 
</div>

<!--LISTA EMPRESA-->
<div id="divFlotante2" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;" class="root">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%">lista Empresas</td></tr></table> </div>
    <table border="0" id="tblListaEmpresa" width="760">
    	<tr><td>&nbsp;</td></tr>
        <tr>
            <td>
            <form id="frmBuscarEmpresa" name="frmBuscarEmpresa" style="margin:0" onsubmit="return false;">
                <input type="hidden" id="hddObjDestino" name="hddObjDestino" readonly="readonly" />
                <input type="hidden" id="hddNomVentana" name="hddNomVentana" readonly="readonly" />
                <table align="right">
                    <tr align="left">
                        <td align="right" class="tituloCampo" width="120" >Criterio:</td>
                        <td><input type="text" id="txtCriterioBuscarEmpresa" name="txtCriterioBuscarEmpresa" size="30" class="inputHabilitado" onkeyup="byId('btnBuscarEmpresa').click();"/></td>
                    </tr>
                    <tr align="right">
                        <td colspan="2">
                        <button type="button" id="btnBuscarEmpresa" name="btnBuscarEmpresa" onclick="xajax_buscarEmpresa(xajax.getFormValues('frmBuscarEmpresa'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscarEmpresa'].reset(); byId('btnBuscarEmpresa').click();">Limpiar</button>
                        </td>
                    </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr><td>&nbsp;</td></tr>
        <tr>
            <td>
            <form id="frmListaEmpresa" name="frmListaEmpresa" style="margin:0" onsubmit="return false;">
                <div id="divListaEmpresa" style="width:100%"></div>
            </form>
            </td>
        </tr>
        <tr>
            <td align="right"><hr />
                <button type="button" id="btnCancelarListaEmpresa" name="btnCancelarListaEmpresa" class="close">Cancelar</button>
            </td>
        </tr>
    </table>
</div>

<!--LISTA EMPLEADO-->
<div id="divFlotante3" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo3" class="handle"><table><tr><td id="tdTituloJefeEquipo" width="100%"></td></tr></table></div>
        <table border="0" width="760">
            <tr><td>&nbsp;</td></tr>
            <tr>
                <td>
                    <form id="frmEmpleado" name="frmEmpleado" style="margin:0" onsubmit="return false;">
                        <table align="right" border="0">
                            <tr align="left">
                                <td align="right" class="tituloCampo">Criterio:</td>
                                <td>
                                    <input type="text" id="textCriterioBusEmpleado" name="textCriterioBusEmpleado" class="inputHabilitado" size="40%" onkeyup="byId('butBuscarCriterio').click();"/>
                                </td>
                            </tr>
                            <tr align="right">
                                <td colspan="2">
                                	<input id="hddBuscar" name="hddBuscar" type="hidden" value=""/>
                                    <button type="button" id="butBuscarCriterio" name="butBuscarCriterio" onclick="xajax_buscarEmpleado(xajax.getFormValues('frmEmpleado'),xajax.getFormValues('fomrEquipo'));">Buscar</button>
                                    <button type="button" id="butLimpiaCriterio" name="butLimpiaCriterio" onclick="document.forms['frmEmpleado'].reset(); byId('butBuscarCriterio').click();">Limpiar</button>
                                </td>
                            </tr>
                        </table>
                    </form>
                </td>
            </tr>
            <tr><td>&nbsp;</td></tr>
            <tr>
                <td>
                    <form id="frmLstEmpleado" name="frmLstEmpleado">
                        <div id="tdLisJefeEquipo"></div>
                    </form>
                </td>
            </tr>
            <tr>
                <td align="right">
                    <button type="button" id="butCancelarJefeEquipo" name="butCancelar" class="close">Cerrar</button>
                </td>
            </tr>
        </table>
</div>

<!--AGREGAR INTEGRANTE-->
<div id="divFlotante4" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo4" class="handle"><table><tr><td id="tdNombreGrupo" width="100%">Agregar Integrante</td></tr></table></div>
    <table border="1" id="tblListadoEmpresa" width="760">
    	<tr>
        	<td>das</td>
        </tr>
    <tr>
        <td>
            <div class="wrap">
                <!--the tabs-->
                <ul class="tabs">
                    <li><a href="#">Integrantes del Grupo</a></li>
                    <li><a href="#">Agregar Integrantes</a></li>
                </ul>
                
                <!--tab "panes"-->
                <div class="pane">
                    <div id="divListIntegrantes"></div>
                </div>
                
                <!--tab "panes"-->
                <div class="pane">
                    <table border="0" id="tblListaEmpleado" width="100%">
                    <tr>
                        <td>
                        <form id="frmListaEmpleado" name="frmListaEmpleado" style="margin:0" onsubmit="return false;">
                            <table align="right">
                            <tr align="left">
                                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                                <td><input type="text" name="textCriterio" id="textCriterio" onkeyup="byId('btoBuscar').click();"/></td>
                                <td>
                                    <button type="button" id="btoBuscar" onclick="xajax_buscaEmpleado(xajax.getFormValues('frmListaEmpleado'));">Buscar</button>
                                    <button type="button" onclick="byId('btoBuscar').click();textCriterio.value='';">Limpiar</button>
                                </td>
                            </tr>
                            </table>
                            <input type="hidden" id="hiddIdEquipo" name="hiddIdEquipo"/>
                            <input type="hidden" id="hiddIdEmpresa" name="hiddIdEmpresa"/>
                            <input type="hidden" id="hiddTipoEquipo" name="hiddTipoEquipo"/>
                        </form>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div id="divListEmpleado" style="width:100%"></div>
                        </td>
                    </tr>
                    </table>
                </div>
            </div>
        </td>
    </tr>
    <tr>
        <td align="right">
           <button type="button" id="btnCancelar2" name="btnCancelar2" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', '');
xajax_comboxTipoEquipo("tdBusLstTipaEquipo","<?php $result = buscarTipoUsuario(); echo $result[1]; ?>");
xajax_listaEquipo(0, "", "",'<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>'+"||"+byId('LstEstatusBus').value);

//FUNCIONALIDAD DE LOS TABS
$(function() {
	$("ul.tabs").tabs("> .pane");
});

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
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);//NUEVO EQUIPO

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);//EMPRESA

var theHandle = document.getElementById("divFlotanteTitulo4");
var theRoot   = document.getElementById("divFlotante4");//divFlotante3
Drag.init(theHandle, theRoot);//mueve el formulario

var theHandle = document.getElementById("divFlotanteTitulo3");
var theRoot   = document.getElementById("divFlotante3");
Drag.init(theHandle, theRoot);//mueve el formulario

</script>