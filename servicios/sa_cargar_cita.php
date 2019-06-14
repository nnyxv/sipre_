<?php

require_once ("../connections/conex.php");
session_start();
require_once("../inc_sesion.php");

define('PAGE_PRIV','sa_cargar_cita');

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
include("controladores/ac_sa_cargar_cita.php");

$xajax->processRequest();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!-- saved from url=(0014)about:internet -->
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Agenda de Citas</title>
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

<link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/>
<script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
<script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
<script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>

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
    
    .inputFile{
        height: 28px;
        margin-left:8px;
    }
    
    
    .inputFile:hover{        
        border-color: rgba(82, 168, 236, 0.8);
    }
    
    .bloque{
        height: 28px; 
        width:80px; 
        background-color: #f0f0f0;
        border-radius: 6px 0px 0px 6px;
        border:1px solid #999999;
        border-right:0px;
        color:#000000;   
        position:absolute;
        text-align: center;       
    }
    
    .bloque span{
        display:inline-block; 
        vertical-align:middle;
        line-height: 25px;
    }
    
    .bloque:hover{
        border-color: rgba(82, 168, 236, 0.8);
        -webkit-box-shadow: 0px 0px 2px 0px rgba(50, 50, 50, 0.15);
        -moz-box-shadow:    0px 0px 2px 0px rgba(50, 50, 50, 0.15);
        box-shadow:         0px 0px 2px 0px rgba(50, 50, 50, 0.15);
    }
    
    fieldset{
        width :20%;
        padding : 0;
        margin : 0;
        display : inline-block;
        white-space: nowrap;
        height:50px;
        vertical-align:middle;
        padding: 15px;
    }
    
    .bloque2{
        height: 28px;
        background-color: #f0f0f0;
        border-radius: 6px;
        border:1px solid #999999;
        color:#000000;          
        text-align: center;
        vertical-align:middle;
        display: inline-block;
        padding:0px 15px 0px 15px;
    }
    
    .bloque2{
        display: inline-block;
        line-height: 25px;
    }
    
    .azul{
        color:blue;
    }
    
    .verde{
        color:green;
    }
</style>

<script type="text/javascript">
    
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
                <td align="right" class="titulo_pagina"><span>Agenda de Citas</span></td>
            </tr>
	</tbody>
    </table>
    
    <br>
    <div class="herramientas noprint">
        <fieldset>
            <legend class="legend">Cargar Citas</legend>
            <form name="formularioCita" method="POST" enctype="multipart/form-data">
                <bloque class="bloque puntero" onClick="$('#archivoAgenda').click();" ><span><img border="0" src="../img/iconos/find.png"/>Archivo</span></bloque>        
                <input type="file" class="puntero inputFile" name="archivoAgenda" id="archivoAgenda" />
                <button class="puntero" title="Cargar" value="Cargar" id="btnCargar" name="btnCargar" type="submit" onClick="if($('#archivoAgenda').val() == ''){ return false; }">Cargar<img border="0" src="../img/iconos/page_white_get.png"/></button>
            </form>
        </fieldset>
        
        <fieldset style="float:right">
            <legend class="legend">Descarga Citas/Orden Cerradas</legend>
            <bloque class="bloque2"><span>Fecha Desde-Hasta:</span></bloque>
                <input type="text" name="txtFecha1" id="txtFecha1" readonly="readonly" size="8" style="height:22px; vertical-align: middle;" value = "<?php echo date("d-m-Y"); ?>" />
                <img alt="ico_date" class="puntero noprint" id="imgFecha1" src="../img/iconos/ico_date.png" style="margin-bottom:-4px;"/>
                <input type="text" name="txtFecha2" id="txtFecha2" readonly="readonly" size="8" style="height:22px; vertical-align: middle;" value = "<?php echo date("d-m-Y"); ?>" />
                <img alt="ico_date" class="puntero noprint" id="imgFecha2" src="../img/iconos/ico_date.png" style="margin-bottom:-4px;"/>
            
            <bloque class="bloque2"><span>Generar Archivo:</span></bloque>                
                <button class="puntero" title="Descargar" value="Descargar" id="btnDescargar" onClick ="xajax_descargaFinalizadas(document.getElementById('txtFecha1').value, document.getElementById('txtFecha2').value);" type="button">Descargar<img border="0" src="../img/iconos/page_white_put.png"/></button>            
        </fieldset>
        <br/>
    </div>
                
    <div id="divListadoCitas" style="clear:both;"><?php 
    if(isset($_POST["btnCargar"])){
        //echo "<br>ENVIADO";        
        guardarArchivo("archivoAgenda");
    }else{
        //echo "<br>no se envio";
        ?>
            <script type="text/javascript">
                xajax_listadoCargaCita();
            </script>
        <?php
    }
    ?></div>
   
</div>

<div>
    <?php include("pie_pagina.php"); ?>
</div>    
</body>
</html>

<script type="text/javascript">
    $("#load_animate").hide();
    
    Calendar.setup({
        inputField : "txtFecha1",
        ifFormat : "%d-%m-%Y",
        button : "imgFecha1"
    });
    
    Calendar.setup({
        inputField : "txtFecha2",
        ifFormat : "%d-%m-%Y",
        button : "imgFecha2"
    });
</script>
