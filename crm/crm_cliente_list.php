<?php 
require_once ("../connections/conex.php");

session_start();

 /*Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("crm_cliente_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_crm_cliente_list.php");
include("../controladores/ac_iv_general.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. CRM - Cliente</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css">

	<link rel="stylesheet" type="text/css" href="../js/domDragCrm.css">
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

	//LISTA LAS EMPRESAS  
    function formListaEmpresa(valor, valor2) {
		document.forms['frmBuscarEmpresa'].reset();
		
		byId('hddObjDestino').value = valor;
		byId('hddNomVentana').value = valor2;
		
		byId('btnBuscarEmpresa').click();
		
		tituloDiv1 = 'Empresas';
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv1;
    }
	
	function abreDocumento(documento){
	 var idEmpresa = document.getElementById('lstEmpresa').value;
		switch(documento){
			case "Pdf": window.open('reportes/crm_reporteClientePDF.php'); break;
			case "Excel": window.open('reportes/crm_reporteCleinteExcel.php'+'?idEmpresa= '+idEmpresa); break;	
			//window.open(sP + "?orientacion="+orientacion);			
		}
			
	}
	
	function mostrarOcultatVehiculo(idObj){
	
		if($('#tdModeloCliente'+idObj).is(':visible')){
			$('#tdModeloCliente'+idObj).hide();//muestra td
			$('#butShowModelo'+idObj).show();//oculta but
			$('#butHideModelo'+idObj).hide();//muestra but
			
		}else{
			$('#tdModeloCliente'+idObj).show(); //oculta td
			$('#butHideModelo'+idObj).show();//muestra but
			$('#butShowModelo'+idObj).hide();//oculta but
			
			var idCliente = idObj;
			xajax_listDescriccionVehiculos('0','','',idCliente)
		
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
        	<td class="tituloPaginaCrm">Clientes</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
        		<table border="0" width="100%">
        			<tr>
        				<td valign="top" align="left" width="40%">
        					<table align="left" border="0" cellpadding="0" cellspacing="0">
			                    <tr>
			                        <td>
			                        	<button type="button" onclick="xajax_exportarExcel(xajax.getFormValues('frmBuscar'));" style="cursor:default">
			                                <table align="center" cellpadding="0" cellspacing="0">
			                                    <tr>
			                                        <td>&nbsp;</td>
			                                        <td><img src="../img/iconos/page_excel.png"/></td>
			                                        <td>&nbsp;</td>
			                                        <td>Exportar</td>
			                                    </tr>
			                                </table>
			                            </button>
			                        </td>
			                        <td style="display:none">
			                        	<button type="button" onclick="abrePDFCLiente();" style="cursor:default">
			                                <table align="center" cellpadding="0" cellspacing="0">
			                                    <tr>
			                                        <td>&nbsp;</td>
			                                        <td><img src="../img/iconos/page_white_acrobat.png"></td>
			                                        <td>&nbsp;</td>
			                                        <td>Exportar</td>
			                                    </tr>
			                                </table>
			                            </button>
			                        </td>
			                    </tr>
			                </table>
        				</td>
        				<td align="right" width="60%">
        					<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
			                    <table width="100%" border="0">			
			                        <tr align="left">
			                            <td align="right" width="120" class="tituloCampo">Empresa:</td>
			                            <td id="tdlstEmpresa">
				                            <select style='width:86%' name="lstEmpresa" id="lstEmpresa">
				                           	</select>
			                           	</td>
			                            <td align="right" width="120" class="tituloCampo">Sexo:</td>
			                            <td>
			                            	M<input name="rdbtSexo" id="rdbtSexoM" type="radio" value="M" onclick="byId('btnBuscar').click();" />
			                                F<input name="rdbtSexo" id="rdbtSexoF" type="radio" value="F" onclick="byId('btnBuscar').click();" />
			                            </td>
			                         </tr>
			                         <tr align="left">
			                            <td  align="right" width="120" class="tituloCampo">Modelo</td>
			                            <td id="tdModeloList"></td>
			                            <td align="right" width="120" class="tituloCampo">Estatus:</td>
			                         	<td>
			                            	<select name="lstEstatus" id="lstEstatus" class="inputHabilitado" onchange="byId('btnBuscar').click();">
			                               		<option value="-1">[ Todo ]</option>
			                                    <option value="Activo"> Activo </option>
			                                    <option value="Inactivo"> Inactivo </option>
			                                </select>
			                            </td>
			                         </tr>
			                         
			                         <tr align="left">
			                            <td align="right" width="120" class="tituloCampo">Ver Cliente:</td>
			                            <td>
			                            	<select name="lstCliente" id="lstCliente" class="inputHabilitado" onchange="byId('btnBuscar').click();">
			                               		<option value="-1">[ Todo ]</option>
			                                    <option value="2">Cliente</option>
			                                    <option value="1">Prospecto</option>
			                                </select>
			                            </td>
			                            <td align="right" width="120" class="tituloCampo">Tipo de Cliente:</td>
			                         	<td>
			                            	<select name="lstTipoCliente" id="lstTipoCliente" class="inputHabilitado" onchange="byId('btnBuscar').click();">
			                               		<option value="-1">[ Todo ]</option>
			                                    <option value="Natural"> Natural </option>
			                                    <option value="Juridico"> Juridico </option>
			                                </select>
			                            </td>
			                         </tr>
			                         <tr>
			                         <td align="right" width="120" class="tituloCampo">Fecha:</td>
			                            <td>
			                            	Desde:<input id="textFechaDesde" name="textFechaDesde" style="width:76px" size="15px" class="inputHabilitado" type="text" onkeyup="">
			                            	Hasta:<input id="textFechaHasta" name="textFechaHasta" style="width:76px" size="15px" class="inputHabilitado" type="text" onkeyup="">    
			                            </td>
			                         </tr>
			                         <tr>
			                         	<td  align="right" width="120" class="tituloCampo">Criterio:</td>
			                            <td colspan="3"><input id="txtCriterio" name="txtCriterio" style='width:48%' size="35px"class="inputHabilitado" type="text" onkeyup=""></td>
			                            <td align="right">
			                                <button type="button" id="btnBuscar" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscar'));"> Buscar </button>
			                                <button type="button" onclick="this.form.reset(); byId('btnBuscar').click();"> Limpiar </button>
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
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td >
            <form id="frmListaCliente" name="frmListaCliente" style="margin:0">
            	<div id="divListaCliente" style="width:100%"></div>
            </form>
            </td>
        </tr>
        <tr>
        	<td >
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
<script>
xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaCliente(0,'Id','ASC','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_combListModelo();

	jQuery(function($){
	   $("#textFechaDesde").maskInput("99-99-9999",{placeholder:" "});
	   $("#textFechaHasta").maskInput("99-99-9999",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"textFechaDesde",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"bananasplit"
	});

	new JsDatePick({
		useMode:2,
		target:"textFechaHasta",
		dateFormat:"%d-%m-%Y",
		cellColorScheme:"bananasplit"
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
</script>
<script language="javascript"></script>