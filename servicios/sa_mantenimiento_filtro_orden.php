<?php

require_once ("../connections/conex.php");
session_start();
require_once("../inc_sesion.php");

define('PAGE_PRIV','sa_mantenimiento_filtro_orden');

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
include("controladores/ac_sa_mantenimiento_filtro_orden.php");

$xajax->processRequest();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!-- saved from url=(0014)about:internet -->
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Mantenimiento Filtro Orden</title>
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
    function nuevoFiltroOrden(){
        $("#idFiltroOrden").val("");
        $("#frmFiltroOrden")[0].reset();
        $("#tdFlotanteTitulo").html("Nuevo Filtro Orden");
        $("#divFlotante").show();        
        centrarDiv($("#divFlotante")[0]);
    }
    
    function validarFormFiltroOrden(){
        if (validarCampo('descripcion','t','') === true){
            
            xajax_guardarFiltroOrden(xajax.getFormValues('frmFiltroOrden'));
        } else {
            validarCampo('descripcion','t','');

            alert("Los campos señalados en rojo son requeridos");
            return false;
        }
    }
    
    function eliminarFiltroOrden(idFiltroOrden){
        if(confirm("¿Seguro deseas eliminar?")){
            xajax_eliminarFiltroOrden(idFiltroOrden);
        }
    }
    
    function validarNumeroComa(evento){
        teclaCodigo = (document.all) ? evento.keyCode : evento.which;
	
	if ((teclaCodigo != 0)
	&& (teclaCodigo != 8)
	&& (teclaCodigo != 13)
	&& (teclaCodigo != 45) // es -
	&& (teclaCodigo != 44)
	&& (teclaCodigo <= 47 || teclaCodigo >= 58)) {
		return false;
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
                <td align="right" class="titulo_pagina"><span>Mantenimiento de Filtro Orden</span></td>
            </tr>
	</tbody>
    </table>
    
    <br>
    <div class="herramientas">
        <button onclick="nuevoFiltroOrden();" class="noprint puntero" id="btnNuevo" type="button"><img src="../img/iconos/ico_new.png"/>Nuevo</button>&nbsp;&nbsp;&nbsp;
        
        <input type="text" onkeyup="$('#btnBuscar').click();" name="txtCriterio" id="txtCriterio" />
        <button onclick="xajax_buscarFiltroOrden($('#txtCriterio').val());" class="puntero" title="Buscar" value="buscar" id="btnBuscar" type="button"><img border="0" src="../img/iconos/find.png"/></button>
        <button onclick="$('#txtCriterio').val(''); $('#btnBuscar').click();" class="puntero" title="Restablecer" value="reset" type="button"><img border="0" src="../img/iconos/cc.png"/></button>
        
    </div>
                
    <div id="divListadoFiltroOrden" style="clear:both;"></div>
   
</div>

<div>
    <?php include("pie_pagina.php"); ?>
</div>    
</body>
    
<script> xajax_listadoFiltroOrden(); </script>
    
</html>

<div style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;" class="root" id="divFlotante">
    <div class="handle" id="divFlotanteTitulo"><table><tbody><tr><td width="100%" id="tdFlotanteTitulo"></td></tr></tbody></table></div>
    <form style="margin:0" name="frmFiltroOrden" id="frmFiltroOrden">        
        <table width="300" border="0">
            <tbody>
                <tr>
                    <td>
                        <input type="hidden" id="idFiltroOrden" name="idFiltroOrden" value="" />
                    </td>
                </tr>
                <tr>
                    <td width="33%" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Descripci&oacute;n:</td>
                    <td width="67%">
                        <input type="text" name="descripcion" id="descripcion"/>
                    </td>
                </tr>
                <tr>
                    <td width="33%" align="right" class="tituloCampo">Bloqueo de Items:</td>
                    <td width="67%">
                        <input type="text" onkeypress="return validarNumeroComa(event);" name="bloqueoItems" id="bloqueoItems"/>
                        <img class="puntero" src="../img/iconos/find.png" title="<?php echo titleItems(); ?>" />
                    </td>
                </tr>
                <tr>
                    <td colspan="2"> Nota: Bloqueo Items Separados por Coma</td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo">TOT con accesorios:</td>
                    <td>
                        <select name="totList" id="totList">
                            <option value="">Seleccione</option>
                            <option value="1">SI</option>
                            <option value="0">NO</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td align="right" colspan="2">
                        <hr/>
                        <button type="button" class="puntero" onclick="validarFormFiltroOrden();" name="btnGuardar" id="btnGuardar">Guardar</button>
                        <button type="button" class="puntero" onclick="$('#divFlotante').hide();">Cancelar</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</div>

<script>
    var theHandle = document.getElementById("divFlotanteTitulo");
    var theRoot   = document.getElementById("divFlotante");
    Drag.init(theHandle, theRoot);
</script>