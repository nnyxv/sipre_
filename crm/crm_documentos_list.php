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

$xajax = new xajax();//Instanciando el objeto xajax

$xajax->configure('javascript URI', '../controladores/xajax/');//Configuranto la ruta del manejador de script
$xajax->configure( 'defaultMode', 'synchronous' ); 

include("controladores/ac_crm_documentos_list.php"); //contiene todas las funciones xajax
include("../controladores/ac_iv_general.php");


$xajax->processRequest();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. CRM - Documento de Ventas</title>
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
	/*MOSTAR LOS FORMULARIO*/
	//MUESTRA DE FORM DEL DOCUMENTO NUEVO
	function abrirNuevoDocumento(editar){
		document.getElementById('formNuevoDocumento').reset();
		
		byId('hiddIdDocumento').value = '';
		byId('textDescripcionDocumento').className = 'inputHabilitado';
		//byId('listTipoDocumento').className = 'inputInicial';
		byId('seltEstatu').className = 'inputInicial';
		
		if(editar == true){
			byId('tdFlotanteTitulo').innerHTML = "Editar Documento";
			} else {
				byId('tdFlotanteTitulo').innerHTML = "Nuevo Documento";
				}
			
		//openImg(document.getElementById('divNuevoDocumento')); //BLOQUEA EL FONDO 
		$('#divNuevoDocumento').show();
			optenerEmpresa();
			if(!editar){
				xajax_listTipoDocumento();
			}
		centrarDiv(byId('divNuevoDocumento'));//CENTRAR EL DIV
	}
	//MUESTRA EL LISTADO DE EMPRESA
	function formListaEmpresa() {
		byId('tdFlotanteTitulo2').innerHTML = "Empresas";
			xajax_listadoEmpresas();
				$('#divListEmpresa').show();
					centrarDiv(byId('divListEmpresa'));//CENTRAR EL DIV
	}
	
	/*OCULTAL LOS FORMULARIOS MOSTRADO*/
	//CIERRA EL LISTADO DE EMPRESA
	function cerrarListEmpresa(){
		$('#divListEmpresa').hide();
	}
	
	//OCULTA FORM NUEVO DOCUMENTO 
	function cerrarNuevoDocumento(){
		$('#divNuevoDocumento').hide();
			document.getElementById('formNuevoDocumento').reset();
				byId('cerrarDocumento').click();
	}
	
	/*PARA OBTENER VALORES*/
	//OPTIEN EL VALOR DEL SELECT EMPRESA
	function optenerEmpresa(){
		var idEmpresa = $('#lstEmpresa').val();
			var nombreEmpresa = $('#lstEmpresa option:selected').html();
			 //COLA LOS VALOR A LOS INPUTS
				$('#txtIdEmpresa').val(idEmpresa); 
					$('#txtNombreEmpresa').val(nombreEmpresa);
	}
		
	//ELIMINAR DOCUMENTO
	function validarEliminarDocumento(idDocumento){
		if (confirm("¿Estas seguro que desea eliminar el documento?") == true);
			xajax_eliminarDocumento(idDocumento);
	}
	
	//VALIDAR CAMPO ACTIVIDAD  
	function validarForm() {
		if (validarCampo('textDescripcionDocumento','t','')==true 
		&& validarCampo('listTipoDocumento','t','listaExceptCero')==true
		&& validarCampo('seltEstatu','t','listaExceptCero')==true) {
			
				xajax_guardarDocumento(xajax.getFormValues(formNuevoDocumento));
		} 
		else {
			validarCampo('listTipoDocumento','t','listaExceptCero');
			validarCampo('textDescripcionDocumento','t','');
			validarCampo('seltEstatu','t','listaExceptCero');
			
			alert("Los campos en color rojos son obligatorios");
			return false;
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

    </script>

</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_crm.php"); ?></div> <!--fin del contenedor del menu-->
    
    <div id="divInfo" class="print">
        <table width="100%" border="0">
        <tr>
            <td class="tituloPaginaCrm">Documentos</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
            <td>
                <table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                    <a class="modalImg" id="aNuevo" rel="#divNuevoDocumento" onclick="openImg(this); abrirNuevoDocumento(); ">
                        <button type="button">
                            <table align="center" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>&nbsp;</td>
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
                    <td align="right" class="tituloCampo" width="120">Empresa</td>
                    <td id="tdlstEmpresa"></td>
                    <td>
                        <button type="submit" id="btnBuscar" onclick="xajax_buscarDocumento(xajax.getFormValues('frmBuscar'));">Buscar</button>
                        <button type="button" onclick="this.form.reset(); byId('btnBuscar').click();">Limpiar</button>
                	</td>
                </tr>
                </table>
            </form>
        	</td>
        </tr>
        <tr>
			<td id="tdlistDocumentos"></td>
        </tr>
        </table>
    </div> <!-- fin contenedor interno-->
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div> <!--fin del contenedor general-->
</body>
</html>

<!--Nuevo documento  -->
<div id="divNuevoDocumento" class="root" style="cursor:auto; display:none; left:0px;  position:absolute; top:0px; z-index:0;">
    <div id="divdivNuevoDocumentoTitulo" class="handle">
        <table>
            <tr>
                <td id="tdFlotanteTitulo" width="100%"></td>
            </tr>
        </table>
    </div>
    
<form name="formNuevoDocumento" id="formNuevoDocumento" style="margin:0" onsubmit="return false;">
    <table border="0" width="560">
    <tr align="left">
        <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Empresa:</td>
        <td width="80%">
        	<table cellpadding="0" cellspacing="0">
            <tr>
                <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/></td>
                <td>
                <a class="modalImg" rel="#divFlotante2" onclick="openImg(this); formListaEmpresa('Empresa', 'ListaEmpresa');">
                    <button type="button" id="btnInsertarEmp" name="btnInsertarEmp" style="cursor:default" title="Listar"><img src="../img/iconos/ico_pregunta.gif" /></button>
                </a>
                </td>
                <td><input type="text" id="txtNombreEmpresa" name="txtNombreEmpresa" readonly="readonly" size="45"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr align="left">
        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Descripción:</td>
        <td><input name="textDescripcionDocumento" id="textDescripcionDocumento" type="text" size="45" maxlength="50" /></td>
    </tr>
    <tr align="left">
        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo:</td>
        <td id="tdLstTipoDocumento"></td>
    </tr>
    <tr align="left">
        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estatus:</td>
        <td>
            <select name="seltEstatu" id="seltEstatu">
                <option value="">[ Seleccione ]</option>
                <option value="1">Activo </option>
                <option value="0">Inactivo </option>
            </select>	
        </td>
    </tr>
    <tr>
        <td align="right" colspan="2"><hr>
            <input name="hiddIdDocumento" id="hiddIdDocumento" type="hidden" size="5" maxlength="5"/>
            <button name="guadraDocumento" id="guadraDocumento" type="submit" onclick="validarForm();">
               <table align="center" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>&nbsp;</td>
                        <td><img src="../img/iconos/ico_save.png"/></td>
                        <td>&nbsp;</td>
                        <td>Guardar</td>
                    </tr>
                </table>
            </button>
            <button name="cerrarDocumento" id="cerrarDocumento" type="button" class="close" onclick="cerrarNuevoDocumento()">
                <table align="center" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>&nbsp;</td>
                        <td><img src="../img/iconos/ico_error.gif"/></td>
                        <td>&nbsp;</td>
                        <td>Cancelar</td>
                    </tr>
                </table>
            </button>
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
        <td>
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
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListaEmpresa" name="frmListaEmpresa" style="margin:0" onsubmit="return false;">
            <div id="divListaEmpresa" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaEmpresa" name="btnCancelarListaEmpresa" class="close">
                <table align="center" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>&nbsp;</td>
                        <td><img src="../img/iconos/ico_error.gif"/></td>
                        <td>&nbsp;</td>
                        <td>Cancelar</td>
                    </tr>
                </table>
            </button>
        </td>
    </tr>
    </table>
</div>
<script type="text/x-javascript">
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
xajax_listDocumentos();

var theHandle = document.getElementById("divdivNuevoDocumentoTitulo");
var theRoot   = document.getElementById("divNuevoDocumento");
Drag.init(theHandle, theRoot); //mueve el formulario nuevo documento

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2"); //mueve el formulario lista de empresa
Drag.init(theHandle, theRoot);
</script>
