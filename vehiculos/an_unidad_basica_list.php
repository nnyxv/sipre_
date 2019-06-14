<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_unidad_basica_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_an_unidad_basica_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Unidades Básicas</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png"/>
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblUnidadBasica').style.display = 'none';
		
		if (verTabla == "tblUnidadBasica") {
			document.forms['frmUnidadBasica'].reset();
			byId('hddIdUnidadBasica').value = '';
			byId('txtDescripcion').innerHTML = '';
			byId('hddUrlImagen').value = '';
			byId('txtIdArancelFamilia').value = '';
			
			byId('txtNombreUnidadBasica').className = 'inputHabilitado';
			byId('txtClaveUnidadBasica').className = 'inputHabilitado';
			byId('txtDescripcion').className = 'inputHabilitado';
			byId('lstCatalogo').className = 'inputHabilitado';
			byId('txtNumeroPuertas').className = 'inputHabilitado';
			byId('txtNumeroCilindros').className = 'inputHabilitado';
			byId('txtCilindrada').className = 'inputHabilitado';
			byId('txtCaballosFuerza').className = 'inputHabilitado';
			byId('txtCapacidad').className = 'inputHabilitado';
			byId('txtUnidad').className = 'inputHabilitado';
			byId('txtAnoGarantia').className = 'inputHabilitado';
			byId('txtKmGarantia').className = 'inputHabilitado';
			
			byId('txtFechaLista').className = 'inputHabilitado';
			byId('txtPrecio1').className = 'inputHabilitado';
			byId('txtPrecio2').className = 'inputHabilitado';
			byId('txtPrecio3').className = 'inputHabilitado';
			
			byId('txtCosto').className = 'inputHabilitado';
			
			byId('fleUrlImagen').style.display = '';
			byId('aListarArancelFamilia').style.display = '';
			byId('aNuevoImpuesto').style.display = '';
			byId('btnEliminarImpuesto').style.display = '';
			byId('aNuevoEmpresa').style.display = '';
			byId('btnEliminarEmpresa').style.display = '';
			byId('btnGuardarUnidadBasica').style.display = '';
			
			mensaje_copia(false);
			
			xajax_formUnidadBasica(valor, xajax.getFormValues('frmUnidadBasica'), valor2);
			
			if (valor > 0) {
				byId('opciones_copia_p').style.display = '';
				
				tituloDiv1 = 'Editar Unidad Básica';
			} else {
				byId('opciones_copia_p').style.display = 'none';
				
				tituloDiv1 = 'Agregar Unidad Básica';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblUnidadBasica") {
			byId('txtNombreUnidadBasica').focus();
			byId('txtNombreUnidadBasica').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblListaArancelFamilia').style.display = 'none';
		byId('tblListaImpuesto').style.display = 'none';
		byId('tblListaEmpresa').style.display = 'none';
				
		if (verTabla == "tblListaArancelFamilia") {
			document.forms['frmBuscarArancelFamilia'].reset();
			
			byId('btnBuscarArancelFamilia').click();
			
			tituloDiv2 = 'Familia Arancelaria';
		} else if (verTabla == "tblListaImpuesto") {
			document.forms['frmBuscarImpuesto'].reset();
			
			byId('btnBuscarImpuesto').click();
			
			tituloDiv2 = 'Impuestos';
		} else if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();
			
			byId('hddObjDestino').value = (valor == undefined) ? '' : valor;
			byId('hddNomVentana').value = (valor2 == undefined) ? '' : valor2;
			
			byId('btnBuscarEmpresa').click();
			
			tituloDiv2 = 'Empresas';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaArancelFamilia") {
			byId('txtCriterioBuscarArancelFamilia').focus();
			byId('txtCriterioBuscarArancelFamilia').select();
		} else if (verTabla == "tblListaImpuesto") {
			byId('txtCriterioBuscarImpuesto').focus();
			byId('txtCriterioBuscarImpuesto').select();
		} else if (verTabla == "tblListaEmpresa") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		}
	}

	function mensaje_copia(cobj) {
		if (cobj.checked) {
			cobj.checked = false;
			if (confirm('¿Desea guardar esta unidad como una nueva unidad básica?\n\nDebe de especificar un nombre de catálogo nuevo, de lo contrario no se efectuará la copia.\n\nPuede especificar la copia todos los temparios, repuestos y paquetes de servicio compatibles.\n\nTenga en cuenta que esta es una operación delicada que implica copiar cierta cantidad de registros, por lo que puede tardar varios minutos.\n\nPuede cambiar los otros datos pero en el caso de la Marca, Modelo y Versión debe conservarlos por la compatibilidad de los paquetes de servicio, luego de la copia puede dirigirse al módulo de servicios y modificar individualmente cada opción.\n\n----- AVISO IMPORTANTE -----\n Esta operación no tiene retroceso y no se resuelve eliminando la unidad.') == true) {
				cobj.checked = true;
			}
			byId('opciones_copia').style.display = '';
			$('#opciones_copia input[type=checkbox]').attr('checked','checked');
		}
		if (!cobj.checked || cobj.checked == false) {
			byId('opciones_copia').style.display = 'none';
		}
	}
	
	function validarEliminar(idUnidadBasica){
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarUnidadBasica(idUnidadBasica, xajax.getFormValues('frmListaUnidadBasica'));
		}
	}
	
	function validarFrmUnidadBasica() {
		error = false;
		if (!(validarCampo('txtNombreUnidadBasica','t','') == true
		&& validarCampo('txtClaveUnidadBasica','t','') == true
		&& validarCampo('txtDescripcion','t','') == true
		&& validarCampo('lstMarcaUnidadBasica','t','lista') == true
		&& validarCampo('lstModeloUnidadBasica','t','lista') == true
		&& validarCampo('lstVersionUnidadBasica','t','lista') == true
		&& validarCampo('lstAno','t','lista') == true
		&& validarCampo('lstCatalogo','t','listaExceptCero') == true
		&& validarCampo('lstPaisOrigen','t','lista') == true
		&& validarCampo('lstClase','t','lista') == true
		&& validarCampo('lstUso','t','lista') == true
		&& validarCampo('txtNumeroPuertas','t','') == true
		&& validarCampo('txtNumeroCilindros','t','') == true
		&& validarCampo('txtCilindrada','t','') == true
		&& validarCampo('lstTransmision','t','lista') == true
		&& validarCampo('lstCombustible','t','lista') == true
		&& validarCampo('txtAnoGarantia','t','numPositivo') == true
		&& validarCampo('txtKmGarantia','t','numPositivo') == true)) {
			validarCampo('txtNombreUnidadBasica','t','');
			validarCampo('txtClaveUnidadBasica','t','');
			validarCampo('txtDescripcion','t','');
			validarCampo('lstMarcaUnidadBasica','t','lista');
			validarCampo('lstModeloUnidadBasica','t','lista');
			validarCampo('lstVersionUnidadBasica','t','lista');
			validarCampo('lstAno','t','lista');
			validarCampo('lstCatalogo','t','listaExceptCero');
			validarCampo('lstPaisOrigen','t','lista');
			validarCampo('lstClase','t','lista');
			validarCampo('lstUso','t','lista');
			validarCampo('txtNumeroPuertas','t','');
			validarCampo('txtNumeroCilindros','t','');
			validarCampo('txtCilindrada','t','');
			validarCampo('lstTransmision','t','lista');
			validarCampo('lstCombustible','t','lista');
			validarCampo('txtAnoGarantia','t','numPositivo');
			validarCampo('txtKmGarantia','t','numPositivo');
			
			error = true;
		}
		
		if (byId('lstCatalogo').value == 1) {
			if (!(validarCampo('txtFechaLista','t','fecha') == true
			&& validarCampo('txtPrecio1','t','numPositivo') == true
			&& validarCampo('txtCosto','t','numPositivo') == true)) {
				validarCampo('txtFechaLista','t','fecha');
				validarCampo('txtPrecio1','t','numPositivo');
				validarCampo('txtCosto','t','numPositivo');
				
				error = true;
			}
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			byId('btnGuardarUnidadBasica').disabled = true;
			byId('btnCancelarUnidadBasica').disabled = true;
			xajax_guardarUnidadBasica(xajax.getFormValues('frmUnidadBasica'), xajax.getFormValues('frmListaUnidadBasica'));
		}
	}
	
	function validarInsertarEmpresa(idEmpresa) {
		// BLOQUEA LOS BOTONES DEL LISTADO
		for (cont = 1; cont <= 20; cont++) {
			if (!(byId('btnInsertarEmpresa' + cont) == undefined)) {
				byId('btnInsertarEmpresa' + cont).disabled = true;
			}
		}
		xajax_insertarEmpresa(idEmpresa, xajax.getFormValues('frmUnidadBasica'));
	}
	
	function validarInsertarImpuesto(idImpuesto) {
		// BLOQUEA LOS BOTONES DEL LISTADO
		for (cont = 1; cont <= 20; cont++) {
			if (!(byId('btnInsertarImpuesto' + cont) == undefined)) {
				byId('btnInsertarImpuesto' + cont).disabled = true;
			}
		}
		xajax_insertarImpuesto(idImpuesto, xajax.getFormValues('frmUnidadBasica'));
	}
	</script>
</head>

<body class="bodyVehiculos">
	<script type="text/javascript" language="javascript" src="../js/wz_tooltip/wz_tooltip.js"></script>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_vehiculos.php"); ?></div>
	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaVehiculos">Unidades Básicas</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblUnidadBasica');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img class="puntero" src="../img/iconos/ico_new.png" title="Nuevo"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                    	<button type="button" onclick="xajax_exportarUnidadBasica(xajax.getFormValues('frmBuscar'));" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
                    </td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="3">
                        <select id="lstEmpresa" name="lstEmpresa">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Marca:</td>
                    <td id="tdlstMarcaBuscar"></td>
                    <td align="right" class="tituloCampo" width="120">Modelo:</td>
                    <td id="tdlstModeloBuscar"></td>
                    <td align="right" class="tituloCampo" width="120">Versión:</td>
                    <td id="tdlstVersionBuscar"></td>
                </tr>
                <tr align="left">
                	<td align="right" class="tituloCampo">Año:</td>
                    <td id="tdlstAnoBuscar"></td>
                    <td align="right" class="tituloCampo">En Catálogo:</td>
                    <td>
                    	<select id="lstCatalogoBuscar" name="lstCatalogoBuscar" onchange="byId('btnBuscar').click();">
                        	<option value="-1">[ Seleccione ]</option>
                        	<option value="0">No</option>
                        	<option value="1">Si</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarUnidadBasica(xajax.getFormValues('frmBuscar'));">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaUnidadBasica" name="frmListaUnidadBasica" style="margin:0">
            	<div id="divListaUnidadBasica" style="width:100%"></div>
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
                        	<td><img src="../img/iconos/money.png"/></td><td>Ver Precios</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_view.png"/></td><td>Ver Unidad Básica</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/pencil.png"/></td><td>Editar Unidad Básica</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/cross.png"/></td><td>Eliminar Unidad Básica</td>
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

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
    
<form action="controladores/ac_upload_file_unidad.php" enctype="multipart/form-data" id="frmUnidadBasica" name="frmUnidadBasica" method="post" onsubmit="return false;" style="margin:0" target="iframeUpload">
    <div id="tblUnidadBasica" style="max-height:500px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr>
            <td>
                <table border="0" width="100%">
                <tr>
                    <td width="68%"></td>
                    <td width="32%"></td>
                </tr>
                <tr>
                    <td valign="top">
                    <fieldset><legend class="legend">Datos de la Unidad</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Nombre:</td>
                            <td width="34%"><input type="text" id="txtNombreUnidadBasica" name="txtNombreUnidadBasica" size="26"/></td>
                            <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Clave:</td>
                            <td width="34%"><input type="text" id="txtClaveUnidadBasica" name="txtClaveUnidadBasica" size="26"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" rowspan="3"><span class="textoRojoNegrita">*</span>Descripción:</td>
                            <td rowspan="3"><textarea id="txtDescripcion" name="txtDescripcion" rows="3" style="width:99%"></textarea></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Marca:</td>
                            <td id="tdlstMarcaUnidadBasica"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Modelo:</td>
                            <td id="tdlstModeloUnidadBasica"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Versión:</td>
                            <td id="tdlstVersionUnidadBasica"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Año:</td>
                            <td id="tdlstAno"></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                    <td rowspan="2" valign="top">
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="40%"><span class="textoRojoNegrita">*</span>En Catálogo:</td>
                            <td width="60%">
                                <select id="lstCatalogo" name="lstCatalogo" style="width:99%">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="0">No</option>
                                    <option value="1">Si</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td align="center" class="imgBorde" colspan="2"><img id="imgArticulo" width="220"/></td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <input type="file" id="fleUrlImagen" name="fleUrlImagen" class="inputHabilitado" onchange="javascript:submit();"/>
                                <iframe name="iframeUpload" style="display:none"></iframe>
                                <input type="hidden" id="hddUrlImagen" name="hddUrlImagen"/>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                    <fieldset><legend class="legend">Datos para Compra y Venta</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%">Posición Arancelaria:</td>
                            <td width="80%">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="hidden" id="txtIdArancelFamilia" name="txtIdArancelFamilia" onkeyup="xajax_asignarArancelFamilia(this.value, 'false');" readonly="readonly" size="6" style="text-align:right"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarArancelFamilia" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaArancelFamilia');">
                                        <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><input type="text" id="txtCodigoArancelFamilia" name="txtCodigoArancelFamilia" readonly="readonly" size="26"/></td>
                                    <td>&nbsp;</td>
                                    <td><input type="text" id="txtDescripcionArancelFamilia" name="txtArancelFamilia" readonly="readonly" size="36"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                    <fieldset><legend class="legend">Especificaciónes Técnicas</legend>
                        <table width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Origen:</td>
                            <td id="tdlstPaisOrigen"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Clase:</td>
                            <td id="tdlstClase" width="21%"></td>
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Uso:</td>
                            <td id="tdlstUso" width="21%"></td>
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span># Puertas:</td>
                            <td width="22%"><input type="text" id="txtNumeroPuertas" name="txtNumeroPuertas" style="text-align:right"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span># Cilindros:</td>
                            <td><input type="text" id="txtNumeroCilindros" name="txtNumeroCilindros" style="text-align:right"/></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cilindrada Cm3:</td>
                            <td><input type="text" id="txtCilindrada" name="txtCilindrada" style="text-align:right"/></td>
                            <td align="right" class="tituloCampo">Caballos de Fuerza (HP):</td>
                            <td><input type="text" id="txtCaballosFuerza" name="txtCaballosFuerza" style="text-align:right"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Transmisión:</td>
                            <td id="tdlstTransmision"></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Combustible:</td>
                            <td id="tdlstCombustible"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Capacidad (Kg):</td>
                            <td><input type="text" id="txtCapacidad" name="txtCapacidad" style="text-align:right"/></td>
                            <td align="right" class="tituloCampo">Unidad:</td>
                            <td><input type="text" id="txtUnidad" name="txtUnidad"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Años de Garantía:</td>
                            <td><input type="text" id="txtAnoGarantia" name="txtAnoGarantia" style="text-align:right"/></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanKilometraje; ?> de Garantía:</td>
                            <td><input type="text" id="txtKmGarantia" name="txtKmGarantia" maxlength="12" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event)" style="text-align:right"/></td>
                        </tr>
                        <tr>
                            <td colspan="6">
                                <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                                <tr>
                                    <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                                    <td align="center">
                                        Tenga en cuenta que al elegir "GNV" o "DUAL GNV" se le solicitarán al momento de registrar la compra de la unidad física, los datos correspondientes al Sistema GNV
                                    </td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                    <fieldset><legend class="legend">Precios</legend>
                        <table width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Lista:</td>
                            <td><input type="text" id="txtFechaLista" name="txtFechaLista" autocomplete="off" size="10" style="text-align:center"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo" width="14%"><span class="textoRojoNegrita">*</span>Precio 1:</td>
                            <td width="19%"><input type="text" id="txtPrecio1" name="txtPrecio1" maxlength="12" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event)" size="16" style="text-align:right"/></td>
                            <td class="tituloCampo" width="14%">Precio 2:</td>
                            <td width="19%"><input type="text" id="txtPrecio2" name="txtPrecio2" maxlength="12" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event)" size="16" style="text-align:right"/></td>
                            <td class="tituloCampo" width="14%">Precio 3:</td>
                            <td width="20%"><input type="text" id="txtPrecio3" name="txtPrecio3" maxlength="12" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event)" size="16" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                    <td valign="top">
                    <fieldset><legend class="legend">Costos</legend>
                        <table width="100%">
                        <tr align="right">
                            <td class="tituloCampo" width="40%"><span class="textoRojoNegrita">*</span>Costo:</td>
                            <td width="60%"><input type="text" id="txtCosto" name="txtCosto" maxlength="12" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event)" size="16" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                    <fieldset><legend class="legend">Impuestos</legend>
                        <table width="100%">
                        <tr align="left">
                            <td>
                            <a class="modalImg" id="aNuevoImpuesto" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaImpuesto');">
                                <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                            </a>
                                <button type="button" id="btnEliminarImpuesto" name="btnEliminarImpuesto" onclick="xajax_eliminarUnidadBasicaImpuesto(xajax.getFormValues('frmUnidadBasica'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <table border="0" class="texto_9px" width="100%">
                                <tr align="center" class="tituloColumna">
                                    <td><input type="checkbox" id="cbxItmImpuesto" onclick="selecAllChecks(this.checked,this.id,2);"/></td>
                                    <td width="25%%">Tipo Impuesto</td>
                                    <td width="55%">Observación</td>
                                    <td width="20%">% Impuesto</td>
                                </tr>
                                <tr id="trItmPieImpuesto"></tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                    <td valign="top">
                        <table border="0" width="100%">
                        <tr id="opciones_copia_p" align="left">
                            <td class="tituloCampo" colspan="3"><label><input type="checkbox" onclick="mensaje_copia(this);" name="unidad_copy" id="unidad_copy" value="1"/> Guardar como una Unidad Básica Nueva</label></td>
                        </tr>
                        <tr id="opciones_copia" align="left" style="display:none;">
                            <td>
                                <label><input type="checkbox" id="copy_tempario" name="copy_tempario" checked="checked" value="1"/> Copiar Temparios</label>
                                <br>
                                <label><input type="checkbox" id="copy_repuesto" name="copy_repuesto" checked="checked" value="1"/> Copiar Repuestos</label>
                                <br>
                                <label><input type="checkbox" id="copy_paquete" name="copy_paquete" checked="checked" value="1"/> Copiar Paquetes de Servicio</label>
                                <br>
                                <label style="display:none;"><input type="checkbox" id="copy_alterno" name="copy_alterno" checked="checked" value="1"/> Copiar Art&iacute;culos Aternos y Sustitutos</label>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                    <fieldset><legend class="legend">Empresas Asignadas</legend>
                        <table width="100%">
                        <tr align="left">
                            <td>
                            <a class="modalImg" id="aNuevoEmpresa" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaEmpresa', 'Empresa', 'ListaEmpresa');">
                                <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                            </a>
                                <button id="btnEliminarEmpresa" name="btnEliminarEmpresa" onclick="xajax_eliminarUnidadBasicaEmpresa(xajax.getFormValues('frmUnidadBasica'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <table border="0" class="texto_9px" width="100%">
                                <tr align="center" class="tituloColumna">
                                    <td><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,2);"/></td>
                                    <td width="14%"><?php echo $spanRIF; ?></td>
                                    <td width="43%">Empresa</td>
                                    <td width="43%">Sucursal</td>
                                </tr>
                                <tr id="trItmPie"></tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <input type="hidden" id="hddIdUnidadBasica" name="hddIdUnidadBasica" readonly="readonly"/>
                <button type="submit" id="btnGuardarUnidadBasica" name="btnGuardarUnidadBasica"  onclick="validarFrmUnidadBasica();">Guardar</button>
                <button type="button" id="btnCancelarUnidadBasica" name="btnCancelarUnidadBasica" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
    </div>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>

    <div id="tblListaArancelFamilia" style="max-height:500px; overflow:auto; width:960px">
        <table border="0" width="100%">
        <tr>
            <td>
            <form id="frmBuscarArancelFamilia" name="frmBuscarArancelFamilia" onsubmit="return false;" style="margin:0">
                <table align="right">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterioBuscarArancelFamilia" name="txtCriterioBuscarArancelFamilia" class="inputHabilitado" onkeyup="byId('btnBuscarArancelFamilia').click();"/></td>
                    <td>
                        <button type="submit" id="btnBuscarArancelFamilia" name="btnBuscarArancelFamilia" onclick="xajax_buscarArancelFamilia(xajax.getFormValues('frmBuscarArancelFamilia'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscarArancelFamilia'].reset(); byId('btnBuscarArancelFamilia').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td><div id="divListaArancelFamilia" style="width:100%"></div></td>
        </tr>
        <tr>
            <td align="right"><hr>
                <button type="button" id="btnCancelarArancelFamilia" name="btnCancelarArancelFamilia" class="close">Cerrar</button>
            </td>
        </tr>
        </table>
    </div>
    
	<table border="0" id="tblListaImpuesto" width="760">
    <tr>
    	<td>
        <form id="frmBuscarImpuesto" name="frmBuscarImpuesto" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarImpuesto" name="txtCriterioBuscarImpuesto" class="inputHabilitado" onkeyup="byId('btnBuscarImpuesto').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarImpuesto" name="btnBuscarImpuesto" onclick="xajax_buscarImpuesto(xajax.getFormValues('frmBuscarImpuesto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarImpuesto'].reset(); byId('btnBuscarImpuesto').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
    	<td><div id="divListaImpuesto" style="width:100%"></div></td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelarListaImpuesto" name="btnCancelarListaImpuesto" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
    
    <table border="0" id="tblListaEmpresa" width="760">
    <tr>
    	<td>
        <form id="frmBuscarEmpresa" name="frmBuscarEmpresa" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestino" name="hddObjDestino" readonly="readonly"/>
            <input type="hidden" id="hddNomVentana" name="hddNomVentana" readonly="readonly"/>
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
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListaEmpresa" name="frmListaEmpresa" onsubmit="return false;" style="margin:0">
        	<div id="divListaEmpresa" style="width:100%"></div>
		</form>
		</td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaEmpresa" name="btnCancelarListaEmpresa" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>
byId('lstCatalogoBuscar').className = "inputHabilitado";
byId('txtCriterio').className = "inputHabilitado";

window.onload = function(){
	jQuery(function($){
		$("#txtFechaLista").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaLista",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"orange"
	});
};

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

//var lstAnoBuscar = $.map($("#lstAnoBuscar option:selected"), function (el, i) { return el.value; });

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstMarcaModeloVersion("unidad_basica", "lstPadre", "Buscar", "true", "", "", "byId('btnBuscar').click();");
xajax_cargaLstAnoBuscar();
xajax_listaUnidadBasica(0, "uni_bas.id_uni_bas", "DESC", '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>