<?php
    require_once ("../connections/conex.php");

    session_start();
    /* Validación del Módulo */
    include('../inc_sesion.php');
    if (!validaAcceso("sa_control_entrada_citas")){
		echo "
		<script type=\"text/javascript\">
			alert('Acceso Denegado');
			window.location='index.php';
		</script>";
	};
    /* Fin Validación del Módulo */
	
    $currentPage = $_SERVER["PHP_SELF"];

    require ('../controladores/xajax/xajax_core/xajax.inc.php');
    //Instanciando el objeto xajax
    $xajax = new xajax();
    //Configuranto la ruta del manejador de script
    $xajax->configure('javascript URI', '../controladores/xajax/');

    include("controladores/ac_sa_control_entrada_citas.php");
    include("controladores/ac_iv_general.php");

    $xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        
        <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Listado de Citas</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
        
        <title>.: Sistema ERP :. .: Módulo de Repuestos :. - Articulos</title>
        <?php
            $xajax->printJavascript('../controladores/xajax/');
        ?>
        <link rel="stylesheet" type="text/css" href="../style/styleRafk.css" />
        <link rel="stylesheet" type="text/css" media="all" href="../js/domDragServicios.css"/>
        
        <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
        <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>

        <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/>
        <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
        <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
        <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
    
        <style type="text/css">
            .root {
		background-color:#FFFFFF;
		border:6px solid #999999;
		font-family:Verdana, Arial, Helvetica, sans-serif;
		font-size:11px;
		max-width:1050px;
		position:absolute;
            }
            .handle {
		padding:2px;
		background-color:#000066;
		color:#FFFFFF;
		font-weight:bold;
		cursor:move;
            }
	</style>
    </head>

    <body>
        <div id="divGeneralPorcentaje">
            <div class="noprint">
                <?php include ('banner_servicios.php'); ?>
            </div>
            <div id="divInfo" class="print">
                <table border="0" width="100%">
                    <tr>
                        <td class="tituloPaginaServicios">Listado de Citas</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>
                            <table align="left">
                                <tr class="noprint">
                                    <td id="tdEncabezadoImprimir">
                                        <button type="button" onclick="xajax_exportarExcel(xajax.getFormValues('frmBuscar'));" class="noprint" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>Exportar</td></tr></table></button>
                                    </td>
                                </tr>
                            </table>
                            <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                                <table align="right" class="">
                                    <tr align="left">
                                        <td align="right" width="120" class="tituloCampo">
                                            Empresa/Sucursal:
                                        </td>
                                        <td id="tdSelEmpresa">
                                        </td>
                                        <td align="right" width="120" class="tituloCampo">
                                            Asesor:
                                        </td>
                                        <td id="tdSelAsesor" >
                                            <select id="selAsesor" name="selAsesor">
                                                <option value="0">Seleccione</option>
                                            </select>
                                        </td>                                                                               
                                        <td align="right" width="120" class="tituloCampo">
                                            Estatus:
                                        </td> 
                                        <td id="tdSelEstatus" >
                                            <select id="selEstatus" name="selEstatus">
                                                <option value="">TODOS</option>
                                            </select>
                                        </td>
                                    </tr>
                                    
                                    <tr>                                        
                                        <td align="right" class="tituloCampo" width="80">
                                            Fecha Desde:
                                        </td>
                                        <td>
                                            <input type="text" name="txtFecha1" id="txtFecha1" readOnly="readOnly" size="10"/>                                            
                                            <img style="margin-bottom:-4px;" src="../img/iconos/ico_date.png" id="imgFechaProveedor" name="imgFechaProveedor" class="puntero noprint" alt="ico_date"/>                                           
                                        </td>
                                        <td align="right" class="tituloCampo" width="80">
                                            Fecha Hasta:
                                        </td>
                                        <td>
                                            <input type="text" name="txtFecha2" id="txtFecha2" readOnly="readOnly" size="10"/>
                                            <img style="margin-bottom:-4px;" src="../img/iconos/ico_date.png" id="imgFechaProveedor2" name="imgFechaProveedor2" class="puntero noprint" alt="ico_date"/>
                                        </td>
                                        
                                        <td align="right" class="tituloCampo" width="80">
                                            Criterio:
                                        </td>
                                        <td>
                                            <input type="text" onkeyup="document.getElementById('btnBuscar').click();" name="txtCriterio" id="txtCriterio" size="30"/>                                            
                                        </td>
                                        
                                        <td>
                                            <button type="button" class="noprint puntero" name="btnBuscar" id="btnBuscar" onclick="xajax_buscarUnidadFisica(xajax.getFormValues('frmBuscar'));" >Buscar</button>
                                        </td>
                                        <td>
                                            <button type="button" class="noprint puntero" onclick="document.forms['frmBuscar'].reset(); xajax_buscarUnidadFisica(xajax.getFormValues('frmBuscar')); actualizarAsesor();">Limpiar</button>
                                        </td>
                                    </tr>
                                    
                                </table>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td id="tdListadoProveedor"></td>
                    </tr>
                    <tr>
                        <td></td>
                    </tr>
                    
                    <tr>
                        <td></td>
                    </tr>
                    <tr>
                        <td></td>
                    </tr>
                    <tr>
                        <td id="tdResumen"></td>
                    </tr>
                </table>
            </div>
            <div class="noprint">
                <?php
                    include ('pie_pagina.php');
                ?>
            </div>
        </div>
    </body>
</html>
<script type="text/javascript">
    function actualizarAsesor(){
        xajax_comboAsesor(xajax.getFormValues('frmBuscar'));
    }
    
    Calendar.setup({
        inputField : "txtFecha1",
        ifFormat : "%d-%m-%Y",
        button : "imgFechaProveedor"
    });
    
    Calendar.setup({
        inputField : "txtFecha2",
        ifFormat : "%d-%m-%Y",
        button : "imgFechaProveedor2"
    });

    xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>','onchange="xajax_buscarUnidadFisica(xajax.getFormValues(\'frmBuscar\')); actualizarAsesor(); "',"selEmpresa","tdSelEmpresa","todos");
		
    //xajax_comboEmpresa(xajax.getFormValues('frmBuscar'));
    xajax_comboAsesor(xajax.getFormValues('frmBuscar'));
    xajax_comboEstatus(xajax.getFormValues('frmBuscar'));
    //var fecha = new Date();//NO USAR, mejor la del servidor
    xajax_listadoUnidadFisica(0,'hora_inicio_cita','ASC', '|' + '<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>' + '|' + 0 + '|' + '|' + '<?php echo DATE("d-m-Y"); ?>' + '|' + '<?php echo DATE("d-m-Y"); ?>');
    
    //fecha.getDate()+'-'+(fecha.getMonth()+1)+'-'+ fecha.getFullYear();
    //fecha.getDate()+'-'+(fecha.getMonth()+1)+'-'+ fecha.getFullYear();
    
    document.getElementById('txtFecha1').value = '<?php echo DATE("d-m-Y"); ?>';
    document.getElementById('txtFecha2').value = '<?php echo DATE("d-m-Y"); ?>';    
</script>
