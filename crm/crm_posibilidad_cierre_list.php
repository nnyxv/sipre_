<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("crm_posibilidad_cierre_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_crm_posibilidad_cierre_list.php");
include("../controladores/ac_iv_general.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. CRM - Posibilidad de Cierre</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCrm.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <!--<script type="text/javascript" language="javascript" src="../js/mootools.js"></script>-->
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
<script>
	function abrirFrom(idObj, forms, IdObjTitulo, valor, valor2){
		document.forms[forms].reset();
		verMsj(0);
		if (IdObjTitulo == "tdFlotanteTitulo"){
			byId('hddIdPosibilidadCierre').value = '';
			byId('hddUrlImagen').value = '';
			byId('txtIdEmpresa').className = 'inputHabilitado';
			byId('txtNombre').className = 'inputHabilitado';
			byId('lstEstatus').className = 'inputHabilitado';
			if(valor == 0){
				titulo = "Agregar Posibilidad de Cierre";
				xajax_cargarPosibilidadCierre("");
			} else{
				titulo = "Editar Posibilidad de Cierre";
				xajax_cargarPosibilidadCierre(valor);
			}
		} else if(IdObjTitulo == "tdFlotanteTitulo2"){
			titulo = "Lista de Empresa";
			xajax_listadoEmpresas(0,"id_empresa_reg","ASC");
		}
		
		openImg(idObj);
		byId(IdObjTitulo).innerHTML = titulo ;
	}
	
	function validarForm() {
		if (validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtNombre','t','') == true
		&& validarCampo('lstPosicion','t','') == true
		&& validarCampo('lstEstatus','t','listaExceptCero') == true) {
			xajax_guardarPosibilidadCierre(xajax.getFormValues('frmPosibilidadCierre'), xajax.getFormValues('frmListaConfiguracion'));
		} else {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtNombre','t','');
			validarCampo('lstPosicion','t','');
			validarCampo('lstEstatus','t','listaExceptCero');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarEliminar(idPosibilidadCierre){
		if (confirm('Seguro desea eliminar este registro?') == true) {
			xajax_eliminarPosibilidadCierre(idPosibilidadCierre, xajax.getFormValues('frmListaConfiguracion'));
		}
	}
	function verMsj(valor,idObjAccion){
		switch(idObjAccion){
			case "checkPosibildiadCierre":
				idObjMsj = "divCierraControl";
				Msj = "Todo control de trafico que tenga asignado esta posibilidad de cierre, se considera como finalizada o rechazada";
			break;
			default: 
				idObjMsj = "divEstatusInicial";
				Msj = "Esta sera la posibilidad de cierre que se asignara a cada control de trafico creado";
			break;
		}
		
		if(valor == 1){
			document.getElementById(idObjMsj).style.display= "";
			document.getElementById(idObjMsj).innerHTML= Msj;
		}else{
			document.getElementById(idObjMsj).style.display= "none";
			document.getElementById(idObjMsj).innerHTML= "";
		}
		
	}
	
</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include("banner_crm.php"); ?>
    </div>
	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaCrm">Posibilidad de Cierre</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
        		<table border="0" width="100%">
                    <tr>
	                	 <td valign="top" align="left" width="50%">
		                    <a class="modalImg" id="aNuevo" rel="#divFlotante" onclick="abrirFrom(this, 'frmPosibilidadCierre', 'tdFlotanteTitulo', 0);"><!--xajax_formPosibilidadCierre(this.id);-->
		                    	<button type="button" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/ico_new.png" title="Editar"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
		                    </a>
	                    </td>
	                    <td align="right" width="50%">
	                    	<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
				                <table width="100%">			
				                    <tr align="left">
				                        <td align="right" class="tituloCampo" width="120">Empresa:</td>
				                        <td id="tdlstEmpresa">
				                            <select name="lstEmpresa" id="lstEmpresa">
				                                <option value="-1">[ Seleccione ]</option>
				                            </select>
				                        </td>
				                    </tr>
				                    <tr align="left">
				                        <td align="right" class="tituloCampo" width="120">Estatus:</td>
				                        <td id="tdlstEmpresa">
				                            <select name="lstEstatusBus" id="lstEstatusBus" class="inputHabilitado" onchange="byId('btnBuscar').click();">
				                                <option value="-1">[ Seleccione ]</option>
				                                <option value="1" selected="selected"> Activo </option>
				                                <option value="0"> Inactivo </option>
				                            </select>
				                        </td>
				                    </tr>
				                    <tr align="left">
				                        <td align="right" class="tituloCampo" width="120">Criterio:</td>
				                        <td> <input id="textCriterio" name="textCriterio" class="inputHabilitado" size="45%" style="width:60%;"/>
				                        </td>
				                        <td align="right">
				                        	<button type="submit" id="btnBuscar" onclick="xajax_buscarPosibilidadCierre(xajax.getFormValues('frmBuscar'));">Buscar</button>
				                            <button type="button" onclick="this.form.reset(); byId('btnBuscar').click();">Limpiar</button>
				                        </td>
				                    </tr>
				                </table>
							</form>
	                    </td>
                	</tr>
            	</table>
        	</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaConfiguracion" name="frmListaConfiguracion" style="margin:0">
            	<div id="divListaConfiguracion" style="width:100%"></div>
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
                        	<td><img src="../img/iconos/ico_verde.gif" /></td>
                            <td>Activo</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_rojo.gif" /></td>
                            <td>Inactivo</td>
                            <td><input id="checkPosicibilidadCierre" type="checkbox" checked="checked" disabled="disabled" name="checkPosicibilidadCierre"></td>
                            <td>Estatus Inicial</td>
                            <td><img src="../img/iconos/aprob_jefe_taller.png" /></td>
                            <td>Finaliza control de tr&aacute;fico</td>
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
    
<form 
action="controladores/ac_upload_file_posibilidad_cierre.php" enctype="multipart/form-data" method="post" target="iframeUpload"
id="frmPosibilidadCierre" name="frmPosibilidadCierre" style="margin:0" onsubmit="return false;">
    <table border="0" id="tblClaveEspecial" width="960">
    <tr>
    	<td valign="top">
        	<table width="100%" border="0" >
            <caption id=""></caption>
            <tr align="left">
            	<td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Empresa:</td>
                <td width="85%">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" size="6" style="text-align:center" onkeyup="xajax_asignarEmpresa(this.value);xajax_cargaLstPosicion(this.value);"/></td>
                        <td>
                        <a class="modalImg" id="ListaEmp"  name="ListaEmp" rel="#divFlotante2" onclick="abrirFrom(this, 'frmBuscarEmpresa', 'tdFlotanteTitulo2', 0);">
                            <button type="button" id="btnInsertarEmp" name="btnInsertarEmp" style="cursor:default" title="Seleccionar Empresa"><img src="../img/iconos/ico_pregunta.gif"/></button>
                        </a>
                        </td>
                        <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="50"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nombre:</td>
                <td>
                	<input type="text" id="txtNombre" name="txtNombre" size="45%"/>
                    <input type="checkbox" id="checkPosibildiadCierre" name="checkPosibildiadCierre" title="Finalizar control de trafico" value="1" onclick="verMsj(((this.checked == true)? 1:0),this.id)"/>
                	<div id="divCierraControl" class="divMsjalert" style="float:left; display:none"></div>
                </td>
            </tr>

            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Posicion:</td>
            	<td id="tdLstPosicion"></td>
            </tr>
            <tr id="trPorDefecto" align="left" style="display:none">
            	<td align="right" class="tituloCampo">Estatus Inicial:</td>
            	<td >
                	<div style="float:left">
                        Si<input type="radio" id="rdoPorDefectoSi" name="rdoPorDefecto" value="1" onclick="verMsj(this.value);" />
                        No<input type="radio" checked="checked" id="rdoPorDefectoNo" name="rdoPorDefecto" value="0" onclick="verMsj(this.value);" />
                    </div>
                    <div id="divEstatusInicial" class="divMsjalert" style="float:left"></div>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estatus:</td>
            	<td>
                	<select id="lstEstatus" name="lstEstatus">
                    	<option value="-1">[ Seleccione ]</option>
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </td>
            </tr>
            </table>
        </td>
        <td>
            <table border="0" width="100%">
                <tr><td><img border="0" id="imgCodigoBarra" name="imgCodigoBarra"></td></tr>
                <tr>
                    <td align="center" class="imgBorde"><img id="imgPosibleCierre" src="<?php echo "../".$_SESSION['logoEmpresaSysGts']; ?>" height="100"/></td>
                </tr>
                <tr>
                    <td>
                    <input type="file" id="fleUrlImagen" name="fleUrlImagen" onchange="javascript: submit();"/>
                        <iframe name="iframeUpload" style="display:none"></iframe>
                    <input type="hidden" id="hddUrlImagen" name="hddUrlImagen" value=""/>
                    </td>
                </tr>
            </table>  
        </td>
    </tr>
    <tr>
    	<td align="right" colspan="2">
	        <hr>
            <input type="hidden" id="hddIdPosibilidadCierre" name="hddIdPosibilidadCierre"/>
            <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarForm();">Guardar</button>
            <button type="button" id="btnCancelar" name="btnCancelar" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<!--Listad de empresas-->
<div id="divFlotante2" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;" class="root">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <table border="0" id="tblListaEmpresa" width="760">
    <tr>
        <td>&nbsp;&nbsp;
        <form id="frmBuscarEmpresa" name="frmBuscarEmpresa" style="margin:0" onsubmit="return false;">
            <input type="hidden" id="hddObjDestino" name="hddObjDestino" readonly="readonly" />
            <input type="hidden" id="hddNomVentana" name="hddNomVentana" readonly="readonly" />
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarEmpresa" name="txtCriterioBuscarEmpresa" class="inputHabilitado" onkeyup="byId('btnBuscarEmpresa').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarEmpresa" name="btnBuscarEmpresa" onclick="xajax_buscarEmpresa(xajax.getFormValues('frmBuscarEmpresa'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarEmpresa'].reset(); byId('btnBuscarEmpresa').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>&nbsp;&nbsp;
        </td>
    </tr>
    <tr>
        <td id="divListaEmpresa"> </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaEmpresa" name="btnCancelarListaEmpresa" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>
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

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listadoPosibilidadCierre(0,'posicion_posibilidad_cierre','','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>'+'|'+byId('lstEstatusBus').value);

</script>
<script language="javascript">
var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>