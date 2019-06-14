<?php

require_once ("../connections/conex.php");
session_start();
require_once("../inc_sesion.php");

define('PAGE_PRIV','sa_mantenimiento_articulos_inventario');


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
include("controladores/ac_sa_mantenimiento_articulos_inventario.php");

$xajax->processRequest();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!-- saved from url=(0014)about:internet -->
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Mantenimiento Articulos Inventario</title>
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
    function nuevo(){
        $("#id").val("");
        $("#frm")[0].reset();
        $("#tdFlotanteTitulo").html("Nuevo");
        $("#divFlotante").show();        
        centrarDiv($("#divFlotante")[0]);
    }
    
    function validarForm(){
        if (validarCampo('descripcion','t','') === true &&
            validarCampo('cantidad','t','') === true &&
			validarCampo('fijoList','t','') === true &&
            validarCampo('activoList','t','') === true){
            
            xajax_guardar(xajax.getFormValues('frm'));
        } else {
            validarCampo('descripcion','t','');
            validarCampo('cantidad','t','');
			validarCampo('fijoList','t','');
            validarCampo('activoList','t','');

            alert("Los campos señalados en rojo son requeridos");
            return false;
        }
    }
    
    function eliminar(id){
        if(confirm("¿Seguro deseas eliminar?")){
            xajax_eliminar(id);
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
                <td align="right" class="titulo_pagina"><span>Mantenimiento de Articulos de Inventario</span></td>
            </tr>
	</tbody>
    </table>
    
    <br>
    <div class="herramientas">
    <form name="frmBuscar" id="frmBuscar" onsubmit="return false;">
        <button onclick="nuevo();" class="noprint puntero" id="btnNuevo" type="button"><img src="../img/iconos/ico_new.png"/>Nuevo</button>
        
        <table align="right">
            <tr>
                <td align="right" width="110" class="tituloCampo">Estado:</td>
                <td>
                    <select name="activoBusqList" id="activoBusqList" onchange="$('#btnBuscar').click();">
                        <option value="">Seleccione</option>
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </td>
                <td align="right" width="110" class="tituloCampo">Fijo:</td>
                <td>
                    <select name="fijoBusqList" id="fijoBusqList" onchange="$('#btnBuscar').click();">
                        <option value="">Seleccione</option>
                        <option value="1">SI</option>
                        <option value="0">NO</option>
                    </select>
                </td>
                    
                <td align="right" width="110" class="tituloCampo">Criterio:</td>
                <td>
                    <input type="text" onkeyup="$('#btnBuscar').click();" name="txtCriterio" id="txtCriterio" />
                </td>
                <td>
                    <button onclick="xajax_buscar(xajax.getFormValues('frmBuscar'));" class="puntero" title="Buscar" value="buscar" id="btnBuscar" type="button"><img border="0" src="../img/iconos/find.png"/></button>
                </td>
                <td>
                    <button onclick="$('#txtCriterio').val(''); $('#btnBuscar').click();" class="puntero" title="Restablecer" value="reset" type="button"><img border="0" src="../img/iconos/cc.png"/></button>
                 </td>
            </tr>
        </table>
        
    </form>
    </div>
                
    <div id="divListado" style="clear:both;"></div>
   
</div>

<div>
    <?php include("pie_pagina.php"); ?>
</div>    
</body>
</html>

<div style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;" class="root" id="divFlotante">
    <div class="handle" id="divFlotanteTitulo"><table><tbody><tr><td width="100%" id="tdFlotanteTitulo"></td></tr></tbody></table></div>
    <form style="margin:0" name="frm" id="frm" onsubmit="return false;">        
        <table width="300" border="0">
            <tbody>
                <tr>
                    <td>
                        <input type="hidden" id="id" name="id" value="" />
                    </td>
                </tr>
                <tr>
                    <td width="33%" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Descripci&oacute;n:</td>
                    <td width="67%">
                        <input type="text" name="descripcion" maxlength="30" id="descripcion"/>
                    </td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cantiadad Def.:</td>
                    <td><input type="text" class="inputInicial" name="cantidad" onkeypress="return validarSoloNumeros(event);" id="cantidad" /></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fijo:</td>
                    <td>
                        <select name="fijoList" id="fijoList">
                            <option value="">Seleccione</option>
                            <option value="1">SI</option>
                            <option value="0">NO</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Activo:</td>
                    <td>
                        <select name="activoList" id="activoList">
                            <option value="">Seleccione</option>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td align="right" colspan="2">
                        <hr/>
                        <button type="button" class="puntero" onclick="validarForm();" name="btnGuardar" id="btnGuardar">Guardar</button>
                        <button type="button" class="puntero" onclick="$('#divFlotante').hide();">Cancelar</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</div>

<script>
	xajax_listado();

    var theHandle = document.getElementById("divFlotanteTitulo");
    var theRoot   = document.getElementById("divFlotante");
    Drag.init(theHandle, theRoot);
</script>