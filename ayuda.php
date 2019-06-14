<?php
session_start();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Manuales de Usuario</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    
    <link rel="stylesheet" type="text/css" href="style/styleRafk.css">
    
    <script type="text/javascript" language="javascript" src="js/jquerytools/jquery.tools.min.js"></script>
	<script type="text/javascript" language="javascript" src="js/scriptRafk.js"></script>
    
    <link rel="stylesheet" type="text/css" href="js/jquerytools/tabs-accordion-horizontal.css"/>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("sysgts_menu.inc.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
            <tr>
                <td class="tituloPaginaErp">Manuales de Usuario</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
            </tr>
            <tr>
            	<td><br/><br/><br/><br/><br/><br/>
                    <!-- accordion root -->
                    <div id="accordion1" class="accordion">
                        <!-- 1st header and pane -->
                        <img class="img_transparente" src="img/cuentas_por_cobrar/img_modulo.png"/>
                        <div style="width:200px; display:block"><h3>Cuentas Por Cobrar</h3>
                        <button type="button" onclick="verVentana('manuales/SIPRE-Cuentas Por Cobrar-2014.pdf', 1000, 500);"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>Descargar</td></tr></table></button></div>
                        
                        <!-- 2nd header and pane -->
                        <img class="img_transparente" src="img/cuentas_por_pagar/img_modulo.png"/>
                        <div><h3>Cuentas Por Pagar</h3>
                        <button type="button" onclick="verVentana('manuales/SIPRE-Cuentas Por Pagar-2014.pdf', 1000, 500);"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>Descargar</td></tr></table></button></div>
                        
                        <!-- 3rd header and pane -->
                        <img class="img_transparente" src="img/caja_rs/img_modulo.png"/>
                        <div><h3>Caja de Repuestos y Servicios</h3>
                        <button type="button" onclick="verVentana('manuales/SIPRE-Caja Repuestos y Servicios-2014.pdf', 1000, 500);"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>Descargar</td></tr></table></button></div>
                        
                        <!-- 4rd header and pane -->
                        <img class="img_transparente" src="img/caja_vehiculos/img_modulo.png"/>
                        <div><h3>Caja de Vehículos</h3>
                        <button type="button" onclick="verVentana('manuales/SIPRE-Caja Vehiculos-2014.pdf', 1000, 500);"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>Descargar</td></tr></table></button></div>
                    </div>
                    <br/>
                    <div id="accordion2" class="accordion">
                        <!-- 5st header and pane -->
                        <img class="img_transparente" src="img/tesoreria/img_modulo.png" />
                        <div style="width:200px; display:block"><h3>Tesorería</h3>
                        <button type="button" onclick="verVentana('manuales/SIPRE-Tesoreria-2014.pdf', 1000, 500);"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>Descargar</td></tr></table></button></div>
                        
                        <!-- 6nd header and pane -->
                        <img class="img_transparente" src="img/compras/img_modulo.png"/>
                        <div><h3>Compras</h3>
                        <button type="button" onclick="verVentana('manuales/SIPRE-Compra-2014.pdf', 1000, 500);"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>Descargar</td></tr></table></button></div>
                        
                        <!-- 7nd header and pane -->
                        <img class="img_transparente" src="img/contabilidad/img_modulo.png"/>
                        <div><h3>Contabilidad</h3>
                        <button type="button" onclick="verVentana('manuales/SIPRE-Contabilidad-2017.pdf', 1000, 500);"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>Descargar</td></tr></table></button></div>
                        
                        <!-- 8rd header and pane -->
                        <img class="img_transparente" src="img/informe_gerencial/img_modulo.png"/>
                        <div><h3>Informe Gerencial</h3>
                        <button type="button" onclick="verVentana('manuales/SIPRE-Informe Gerencial-2014.pdf', 1000, 500);"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>Descargar</td></tr></table></button></div>
                    </div>
                    <br/>
                    <div id="accordion3" class="accordion">
                        <!-- 9st header and pane -->
                        <img class="img_transparente" src="img/repuestos/img_modulo.png" />
                        <div style="width:200px; display:block"><h3>Repuestos</h3>
                        <button type="button" onclick="verVentana('manuales/SIPRE-Repuestos-2014.pdf', 1000, 500);"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>Descargar</td></tr></table></button></div>
                        
                        <!-- 10nd header and pane -->
                        <img class="img_transparente" src="img/servicios/img_modulo.png" />
                        <div><h3>Servicios</h3>
                        <button type="button" onclick="verVentana('manuales/SIPRE-Servicios-2014.pdf', 1000, 500);"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>Descargar</td></tr></table></button></div>
                        
                        <!-- 11nd header and pane -->
                        <img class="img_transparente" src="img/vehiculos/img_modulo.png" />
                        <div><h3>Vehículos</h3>
                        <button type="button" onclick="verVentana('manuales/SIPRE-Vehiculos-2014.pdf', 1000, 500);"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>Descargar</td></tr></table></button></div>
                        
                        <!-- 12rd header and pane -->
                        <img class="img_transparente" src="img/crm/img_modulo.png"/>
                        <div><h3>CRM</h3>
                        <button type="button" onclick="verVentana('manuales/SIPRE-CRM-2014.pdf', 1000, 500);"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>Descargar</td></tr></table></button></div>
                    </div>
                </td>
            </tr>
        </table>
	</div>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<script>
$("#accordion1").tabs(".accordion div", {
  tabs: '.img_transparente',
  effect: 'horizontal'
});
$("#accordion2").tabs(".accordion div", {
  tabs: '.img_transparente',
  effect: 'horizontal'
});
$("#accordion3").tabs(".accordion div", {
  tabs: '.img_transparente',
  effect: 'horizontal'
});
</script>