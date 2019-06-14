<?php

require_once ("../connections/conex.php");
session_start();
require_once("../inc_sesion.php");

define('PAGE_PRIV','sa_mantenimiento_codigo_asesor');

if(!(validaAcceso(PAGE_PRIV))) {
	echo "
	<script>
		alert('Acceso Denegado');
		window.location.href = 'index.php';
	</script>";
}

require('../controladores/xajax/xajax_core/xajax.inc.php');

$xajax = new xajax();
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_iv_general.php");
include("controladores/ac_sa_mantenimiento_codigo_asesor.php");

$xajax->processRequest();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!-- saved from url=(0014)about:internet -->
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Mantenimiento Codigo Asesor</title>
<link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php
	 $xajax->printJavascript('../controladores/xajax/');
?>
<link rel="stylesheet" type="text/css" href="css/sa_general.css" />
<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
<link rel="stylesheet" type="text/css" href="../js/domDragServicios.css"/>
<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>

<style type="text/css">
    .herramientas {
        padding:5px;
        padding-left: 30px;
         vertical-align: middle;
    }
    
    .herramientas button{
        line-height: 18px;        
    }
    
    .herramientas img{
        vertical-align: middle;
    }
    
    .herramientas input{
        height: 18px;
    }
</style>

<script type="text/javascript">
    function nuevoCodigoAsesor(){
        $("#idCodigoAsesor").val("");
        $("#frmCodigoAsesor")[0].reset();
        $("#tdFlotanteTitulo").html("Nuevo C&oacute;digo Asesor");
        $("#divFlotante").show();
        centrarDiv($("#divFlotante")[0]);
    }
    
    function validarFormCodigoAsesor(){
        if (validarCampo('idEmpleado','t','') === true &&
            validarCampo('codigoAsesor','t','') === true){
            
            xajax_guardarCodigoAsesor(xajax.getFormValues('frmCodigoAsesor'));
        } else {
            validarCampo('idEmpleado','t','');
            validarCampo('codigoAsesor','t','');

            alert("Los campos señalados en rojo son requeridos");
            return false;
        }
    }
    
    function eliminarCodigoAsesor(idCodigoAsesor){
        if(confirm("¿Seguro deseas eliminar?")){
            xajax_eliminarCodigoAsesor(idCodigoAsesor);
        }
    }
    
</script>

</head>
<body style="font-size:11px;">
<div>
<?php include("banner_servicios.php"); ?>
</div>

<div id="divInfo" class="print">
    
    <table width="100%" border="0" align="center">
        <tbody>
            <tr>
                <td align="right" class="titulo_pagina"><span>Mantenimiento de C&oacute;digo Asesor</span></td>
            </tr>
	</tbody>
    </table>
    
    <br>
    <div class="herramientas">
        <button onclick="nuevoCodigoAsesor();" class="noprint puntero" id="btnNuevo" type="button"><img src="../img/iconos/ico_new.png"/>Nuevo</button>&nbsp;&nbsp;&nbsp;
        
        <input type="text" onkeyup="$('#btnBuscar').click();" name="txtCriterio" id="txtCriterio" />
        <button onclick="xajax_buscarCodigoAsesor($('#txtCriterio').val());" class="puntero" title="Buscar" value="buscar" id="btnBuscar" type="button"><img border="0" src="../img/iconos/find.png"/></button>
        <button onclick="$('#txtCriterio').val(''); $('#btnBuscar').click();" class="puntero" title="Restablecer" value="reset" type="button"><img border="0" src="../img/iconos/cc.png"/></button>
        
    </div>
                
    <div id="divListadoCodigoAsesor" style="clear:both;"></div>
   
</div>

<div>
    <?php include("pie_pagina.php"); ?>
</div>    
</body>
    
<script> xajax_listadoCodigoAsesor(); </script>
    
</html>

<div style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;" class="root" id="divFlotante">
    <div class="handle" id="divFlotanteTitulo"><table><tbody><tr><td width="100%" id="tdFlotanteTitulo"></td></tr></tbody></table></div>
    <form style="margin:0" name="frmCodigoAsesor" id="frmCodigoAsesor">        
        <table width="400" border="0">
            <tbody>
                <tr>
                    <td>
                        <input type="hidden" id="idCodigoAsesor" name="idCodigoAsesor" value="" />
                    </td>
                </tr>
                <tr>
                    <td width="33%" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empleado</td>
                    <td width="67%">                        
                        <input type="text" name="idEmpleado" readonly="readonly" id="idEmpleado" size="2"/>
                        <input type="text" name="nombreEmpleado" readonly="readonly" id="nombreEmpleado"/>
                        <button onclick="xajax_buscarEmpleado();" class="puntero" type="button"><img border="0" src="../img/iconos/plus.png"/></button>
                    </td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>C&oacute;digo:</td>
                    <td><input type="text" class="inputInicial" name="codigoAsesor" onkeypress="return validarSoloNumeros(event);" id="codigoAsesor" /></td>
                </tr>
                <tr>
                    <td align="right" colspan="2">
                        <hr/>
                        <button type="button" class="puntero" onclick="validarFormCodigoAsesor();" name="btnGuardar" id="btnGuardar">Guardar</button>
                        <button type="button" class="puntero" onclick="$('#divFlotante').hide();">Cancelar</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</div>

<div style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;" class="root" id="divFlotante2">
    <div class="handle" id="divFlotanteTitulo2"><table><tbody><tr><td width="100%" id="tdFlotanteTitulo2">Empleados</td></tr></tbody></table></div>
    <div>
        <table>
            <tr>
                <td width="33%" align="right" class="tituloCampo">Criterio:</td>
                <td>
                    <input type="text" id="criterioEmpleado" />
                    <button type="button" class="puntero" onclick="xajax_buscarEmpleado(document.getElementById('criterioEmpleado').value);"><img border="0" src="../img/iconos/find.png"></button>
                </td>
            </tr>
        </table>
    </div>
    
    <div id = "divListadoEmpleado"></div>
    
    <div align="right">
        <hr/>
        <button type="button" class="puntero" onclick="$('#divFlotante2').hide();">Cancelar</button>
    </div>
</div>

<script>
    var theHandle = document.getElementById("divFlotanteTitulo");
    var theRoot   = document.getElementById("divFlotante");
    Drag.init(theHandle, theRoot);
    
    var theHandle = document.getElementById("divFlotanteTitulo2");
    var theRoot   = document.getElementById("divFlotante2");
    Drag.init(theHandle, theRoot);
</script>