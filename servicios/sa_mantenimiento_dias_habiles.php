<?php
    require_once ("../connections/conex.php");
    include ("../inc_sesion.php");

    session_start();
	
	define('PAGE_PRIV','sa_mantenimiento_dias_habiles'); //nuevo gregor
	//define('PAGE_PRIV','sa_dias_habiles');//anterior

    require ('controladores/xajax/xajax_core/xajax.inc.php');
    $xajax = new xajax();
    $xajax->configure('javascript URI', 'controladores/xajax/');

    include("controladores/ac_iv_general.php");
    include("controladores/ac_sa_mantenimiento_dias_habiles.php");
    
    $xajax->processRequest();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        
        <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Días Hábiles Taller</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
        
        <?php $xajax->printJavascript('controladores/xajax/'); ?>

        <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
        <link rel="stylesheet" type="text/css" href="css/sa_general.css" />
        
        <script type="text/javascript" language="javascript" src="../js/mootools.js"></script>
        <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
        <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>

        <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/>
        <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
        <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
        <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
        <style type="text/css">
            .root{
                background-color:#FFFFFF;
                border:6px solid #999999;
                font-family:Verdana, Arial, Helvetica, sans-serif;
                font-size:11px;
                max-width:1050px;
                position:absolute;
            }

            .handle{
                padding:2px;
                background-color:#009933;
                color:#FFFFFF;
                font-weight:bold;
                cursor:move;
            }
	</style>
        <script type="text/javascript" language="javascript">
            function abrirVentana(acc){
                if($('divFlotante').style.display == 'none'){
                    $('divFlotante').style.display = '';
                    centrarDiv($('divFlotante'));
                }

                $('formDiasHabiles').reset();
                $('btnAceptar').value= "Guardar";
                $('btnAceptar').style.display= '';
                $('btnAceptar').setAttribute('onclick', "validar('"+acc+"');");

                if(acc == 1){
                    $('divFlotanteTitulo').innerHTML= "Nuevos Dias Habiles Taller";
                    $('txtDescripcion').disabled= false;
                    $('txtCantidadDias').disabled= false;
                    $('txtFechaDiaHabil').disabled= false;
                }else if(acc == 2){
                    $('divFlotanteTitulo').innerHTML= "Editar Dias Habiles Taller";
                }else if(acc == 3){
                    $('divFlotanteTitulo').innerHTML= "Ver Dias Habiles Taller";
                    $('btnAceptar').style.display= 'none';
                }
            }

            function validar(acc){
                if(validarCampo("txtDescripcion","t", "") && validarCampo("txtCantidadDias","t", "entero") && validarCampo("txtFechaDiaHabil","t", "")){
                    if(acc == 1){
                        xajax_insertDiasHabiles(xajax.getFormValues('formDiasHabiles'));
                    }else if(acc == 2){
                        xajax_updateDiasHabiles(xajax.getFormValues('formDiasHabiles'), xajax.getFormValues('frmBuscar'));
                    }
                }else{
                    alert('Debe completar los campos en rojo');
                }
            }

            function eliminar(id){
                if(confirm("Seguro desea eliminar el registro?")){
                    xajax_deleteDiasHabiles(id);
                }
            }
        </script>
    </head>
    <body class="bodyVehiculos">
        <div id="divGeneralPorcentaje">
            <div class="noprint">
                <?php include ('banner_servicios.php'); ?>
            </div>

            <div id="divInfo" class="print">
                <table border="0" width="100%">
                    <tr class="solo_print">
                        <td align="left" id="tdEncabezadoImprimir"></td>
                    </tr>
                    <tr>
                        <br/>
                        <td class="tituloPaginaServicios">Dias Habiles Taller</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                    </tr>
                    <tr class="noprint">
                        <td align="right">
                            <form id="frmBuscar" name="frmBuscar" onsubmit="$('btnBuscar').click(); return false;" style="margin:0" action="">
                                <table align="left">
                                    <tr>
                                        <td>
                                            <button type="button" onclick="abrirVentana(1);" style="cursor:default">
                                                <table align="center" cellpadding="0" cellspacing="0">
                                                    <tr>
                                                        <td>&nbsp;</td>
                                                        <td>
                                                            <img src="../img/iconos/ico_new.png" alt="btn_new"/>
                                                        </td>
                                                        <td>&nbsp;</td>
                                                        <td>Nuevo</td>
                                                    </tr>
                                                </table>
                                            </button>
                                        </td>
                                        <td>
                                            <button type="button" onclick="xajax_encabezadoEmpresa(<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>); window.print();" style="cursor:default">
                                                <table align="center" cellpadding="0" cellspacing="0">
                                                    <tr>
                                                        <td>&nbsp;</td>
                                                        <td>
                                                            <img src="../img/iconos/ico_print.png" alt="btn_print"/>
                                                        </td>
                                                        <td>&nbsp;</td>
                                                        <td>Imprimir</td>
                                                    </tr>
                                                </table>
                                            </button>
                                        </td>
                                    </tr>
                                </table>

                                <table align="right" border="0">
                                    <tr align="left">
                                        <td align="right" class="tituloCampo" width="150">C&oacute;digo / Descripci&oacute;n:</td>
                                        <td>
                                            <input type="text" id="txtCriterio" name="txtCriterio" />
                                        </td>
                                        <td>
                                            <input type="submit" class="noprint" id="btnBuscar" onclick="xajax_buscar(xajax.getFormValues('frmBuscar'), 1);" value="Buscar" />
                                            <input type="button" class="noprint" onclick="document.forms['frmBuscar'].reset(); $('btnBuscar').click();" value="Limpiar" />
                                        </td>
                                    </tr>
                                </table>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td id="tdListadoDiasHabiles"></td>
                    </tr>
                </table>
            </div>
            <?php include ('pie_pagina.php'); ?>
        </div>
    </body>
</html>

<div id="divFlotante" class="window" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;max-width:400px;min-width:400px;">
    <div id="divFlotanteTitulo" class="title"></div>
    <form name="formDiasHabiles" id="formDiasHabiles" method="post" action="">
        <input type="hidden" id="hddIdDiasHabiles" name="hddIdDiasHabiles" value=""/>
        <table border="0" >
            <tr>
                <td align="right" class="tituloCampo" width="60%">
                    <span class="textoRojoNegrita">*</span>Descripci&oacute;n:
                </td>
                <td width="40%">
                    <textarea id="txtDescripcion" name="txtDescripcion" cols="35" rows="4"></textarea>
                </td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="60%">
                    <span class="textoRojoNegrita">*</span>Cantidad de D&iacute;as Habiles:
                </td>
                <td width="40%">
                    <input type="text" id="txtCantidadDias" name="txtCantidadDias" value="" size="8" style="text-align: right"/>
                </td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="60%">
                    <span class="textoRojoNegrita">*</span>Mes y A&ntilde;o:
                </td>
                <td width="40%">
                    <div style="float:left">
                        <input type="text" id="txtFechaDiaHabil" name="txtFechaDiaHabil" value="" size="8" style="text-align: center"/>
                    </div>
                    <div style="float:left;">
                        <img src="../img/iconos/ico_date.png" id="imgFecha" name="imgFecha" alt="[img]"/>
                        <script type="text/javascript">
                            Calendar.setup({
                                inputField : "txtFechaDiaHabil",
                                ifFormat : "%m-%Y",
                                button : "imgFecha"
                            });
                        </script>
                    </div>
                </td>
            </tr>
            <tr>
                <td align="right" colspan="2" width="100%">
                    <hr/>
                    <input type="button" id="btnAceptar" name="btnAceptar" onclick="" value="Aceptar"/>
                    <input type="button" onclick="$('divFlotante').style.display = 'none';" value="Cancelar"/>
                </td>
            </tr>
        </table>
    </form>
    <img class="close_window" src="../img/iconos/close_dialog.png" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="$('divFlotante').style.display='none';" border="0" />
</div>

<script language="javascript" type="text/javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
</script>

<script type="text/javascript">
    xajax_listadoDiasHabiles(0,'id_dias_habiles','ASC','');
</script>
