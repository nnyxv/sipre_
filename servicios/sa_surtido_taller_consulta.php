<?php
    require_once ("../connections/conex.php");

    session_start();
	
    define('PAGE_PRIV','sa_surtido_taller_consulta');//nuevo gregor
	//define('PAGE_PRIV','sa_solicitud_repuestos');//anterior
	
    require_once("../inc_sesion.php");
	
	if(!(validaAcceso(PAGE_PRIV))) {
	echo "
	<script type=\"text/javascript\">
		alert('Acceso Denegado');
		window.location='index.php';
	</script>";
}
	
    $currentPage = $_SERVER["PHP_SELF"];

    require ('controladores/xajax/xajax_core/xajax.inc.php');

    $xajax = new xajax();
    $xajax->configure('javascript URI', 'controladores/xajax/');

    include("controladores/ac_sa_surtido_taller_consulta.php");
	
include("../connections/conex.php");//usa el vldtipodato que necesita ac_iv_general
include("controladores/ac_iv_general.php"); //tiene el cargaLstEmpresaFinal

    $xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                
        <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Solicitud de Repuestos</title>
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
                        <br/><br/>
                        <td class="tituloPaginaServicios">Solicitud de Repuestos</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                    </tr>
                    <tr class="noprint">
                        <td align="right">
                            <table align="left">
                                <tr>
                                    <td>
                                        
                                    </td>
                                </tr>
                            </table>
                            <form id="frmBuscar" name="frmBuscar" onsubmit="$('btnBuscar').click(); return false;" style="margin:0">
                                <table align="right" border="0">
                                    <tr align="left">
                                        <td align="right" class="tituloCampo" width="100">Empresa:</td>
                                        <td id="tdlstEmpresa">
                                            <!--<select id="lstEmpresa" name="lstEmpresa">
                                                <option value="-1">Todos...</option>
                                            </select>-->
                                            <script type="text/javascript">
                                               // xajax_cargaLstEmpresa('<?php //echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
                                            </script>
                                        </td>
                    
                                        <td align="right" class="tituloCampo" width="100">Estatus:</td>
                                        <td id="tdlstEstatus">
                                            <select id="lstEstatus" name="lstEstatus">
                                                <option value="-1">Todos...</option>
                                            </select>
                                            <script type="text/javascript">
                                                xajax_cargaLstEstatus();
                                            </script>
                                        </td>

                                        <td align="right" class="tituloCampo" width="100">Criterio:</td>
                                        <td>
                                            <input type="text" id="txtCriterio" name="txtCriterio"/>
                                        </td>
                                        <td>
                                            <input type="submit" class="noprint" id="btnBuscar" onclick="xajax_buscar(xajax.getFormValues('frmBuscar'));" value="Buscar" />
                                            <input type="button" class="noprint" onclick="document.forms['frmBuscar'].reset(); $('btnBuscar').click();" value="Limpiar" />
                                        </td>
                                    </tr>
                                </table>
                            </form>
			</td>
                    </tr>
                    <tr>
                        <td id="tdListadoSolicitud"></td>
                    </tr>
                </table>
                <table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                    <tr>
                	<td width="25">
                            <img src="../img/iconos/ico_info2.gif" width="25" alt="info"/>
                        </td>
                	<td align="center">
                            <table>
                                <tr>
                                    <td>
                                        <img src="../img/iconos/ico_azul.gif" alt="Solicitado"/>
                                    </td>
                                    <td>Solicitado</td>
                                    <td>&nbsp;</td>
                                    <td>
                                        <img src="../img/iconos/ico_amarillo_parcial.gif" alt="Aprobada Parcial"/>
                                    </td>
                                    <td>Aprobada Parcial</td>
                                    <td>&nbsp;</td>
                                    <td>
                                        <img src="../img/iconos/ico_amarillo.gif" alt="Aprobado"/>
                                    </td>
                                    <td>Aprobado</td>
                                    <td>&nbsp;</td>
                                    <td>
                                        <img src="../img/iconos/ico_naranja_parcial.gif" alt="Despachada Parcial"/>
                                    </td>
                                    <td>Despachada Parcial</td>
                                    <td>&nbsp;</td>
                                    <td>
                                        <img src="../img/iconos/ico_naranja.gif" alt="Despachado"/>
                                    </td>
                                    <td>Despachado</td>
                                    <td>&nbsp;</td>
                                    <td>
                                        <img src="../img/iconos/ico_gris_parcial.gif" alt="Devuelta Parcial"/>
                                    </td>
                                    <td>Devuelta Parcial</td>
                                    <td>&nbsp;</td>
                                    <td>
                                        <img src="../img/iconos/ico_gris.gif" alt="Devuelto"/>
                                    </td>
                                    <td>Devuelto</td>
                                    <td>&nbsp;</td>
                                    <td>
                                        <img src="../img/iconos/ico_verde.gif" alt="Facturado"/>
                                    </td>
                                    <td>Facturado</td>
                                    <td>&nbsp;</td>
                                    <td>
                                        <img src="../img/iconos/ico_rojo.gif" alt="Anulada"/>
                                    </td>
                                    <td>Anulada</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
            <br/>
            <br/>
            <br/>
            <div class="noprint">
                <?php include("pie_pagina.php"); ?>
            </div>
        </div>
    </body>
</html>

<script type="text/javascript">
    xajax_listadoSolicitudRepuestos(0,'numero_solicitud','DESC','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
</script>

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
    <div id="divFlotanteTitulo" class="handle">
        <table>
            <tr>
                <td id="tdFlotanteTitulo" width="100%"></td>
            </tr>
        </table>
    </div>
    <table id="tblDatosGeneralesOrden" width="980">
        <tr>
            <td>
                <form id="frmDatosGeneralesSolicitud" name="frmDatosGeneralesSolicitud" style="margin:0">
                    <table border="0" width="100%">
                        <tr>
                            <td colspan="3" valign="top">
                                <fieldset>
                                    <legend>Datos Cliente</legend>
                                    <table width="100%">
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Cliente:</td>
                                            <td colspan="3">
                                                <input type="text" name="txtCliente" id="txtCliente" readonly="readonly" size="45"/>
                                            </td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo" width="10%">Motor:</td>
                                            <td width="23%">
                                                <input type="text" name="txtMotor" id="txtMotor" readonly="readonly" size="25"/>
                                            </td>
                                            <td align="right" class="tituloCampo" width="10%">Chasis:</td>
                                            <td width="23%">
                                                <input type="text" name="txtChasis" id="txtChasis" readonly="readonly" size="25" style="text-align:center"/>
                                            </td>
                                            <td align="right" class="tituloCampo" width="10%">Placa:</td>
                                            <td width="24%">
                                                <input type="text" id="txtPlaca" name="txtPlaca" readonly="readonly" size="25" style="text-align:center"/>
                                            </td>
                                        </tr>
                                    </table>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <td align="center" valign="top" width="32%">
                                <fieldset>
                                    <legend>Foto Unidad</legend>
                                    <table>
                                        <tr>
                                            <td align="center" class="imgBorde">
                                                <img id="imgUnidad" src="../img/logo_gotosystems.jpg" width="240"/>
                                            </td>
                                        </tr>
                                    </table>
                                </fieldset>
                            </td>
                            <td valign="top" width="35%">
                                <fieldset>
                                    <legend>Datos Orden</legend>
                                    <table border="0" id="tblDatosOrden" width="100%">
                                        <tr align="left">
                                            <td align="right" class="tituloCampo" width="40%">Fecha:</td>
                                            <td width="60%">
                                                <input type="text" name="txtFecha" id="txtFecha" readonly="readonly" size="12" style="text-align:center"/>
                                            </td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Numero Orden:</td>
                                            <td>
                                                <input type="text" id="txtNumeroOrden" name="txtNumeroOrden" readonly="readonly" size="25"/>
                                            </td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Tipo Orden:</td>
                                            <td>
                                                <input type="text" id="txtTipoOrden" name="txtTipoOrden" readonly="readonly" size="25"/>
                                            </td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Estado Orden:</td>
                                            <td>
                                                <input type="text" name="txtEstadoOrden" id="txtEstadoOrden" readonly="readonly" size="25"/>
                                            </td>
                                        </tr>
                                    </table>
                                </fieldset>
                            </td>
                            <td valign="top" width="33%">
                                <fieldset>
                                    <legend>Datos Solicitud</legend>
                                    <table width="100%">
                                        <tr align="left">
                                            <td align="right" class="tituloCampo" width="44%">Numero Solicitud:</td>
                                            <td width="56%">
                                                <input type="text" name="txtNumeroSolicitud" id="txtNumeroSolicitud" readonly="readonly" />
                                            </td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Estado Solicitud:</td>
                                            <td>
                                                <input type="text" name="txtEstadoSolicitud" id="txtEstadoSolicitud" readonly="readonly" />
                                            </td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Aprobada Por:</td>
                                            <td>
                                                <input type="text" name="txtEmpleadoAprobo" id="txtEmpleadoAprobo" readonly="readonly"/>
                                            </td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Recibido Por:</td>
                                            <td>
                                                <input type="text" name="txtEmpleadoRecRepuestos" id="txtEmpleadoRecRepuestos" readonly="readonly"/>
                                            </td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Despachado Por:</td>
                                            <td>
                                                <input type="text" name="txtEmpleadoDespachoRepuestos" id="txtEmpleadoDespachoRepuestos" readonly="readonly"/>
                                            </td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Devuelto Por:</td>
                                            <td>
                                                <input type="hidden" name="hddEmpleadoDevolucionRepuestos" id="hddEmpleadoDevolucionRepuestos" readonly="readonly"/>
                                                <input type="text" name="txtEmpleadoDevolucionRepuestos" id="txtEmpleadoDevolucionRepuestos" readonly="readonly"/>
                                                <button type="button" id="btnBuscarEmpleadoDev" name="btnBuscarEmpleadoDev" style="display:none" onclick="xajax_listadoEmpleadosDev(0,'','','');" title="Seleccionar Empleado">
                                                    <img src="../img/iconos/ico_pregunta.gif" align="absmiddle"/>
                                                </button>
                                                <input type="hidden" name="hddIdOrden1" id="hddIdOrden1" readonly="readonly"/>
                                            </td>
                                        </tr>
                                    </table>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                </form>
            </td>
	</tr>
        <tr>
            <td>
                <fieldset>
                    <legend>Repuestos Solicitud</legend>
                    <form id="frmListadoDetalleSolicitud" name="frmListadoDetalleSolicitud" style="margin:0">
                        <input type="hidden" name="hddIdOrden2" id="hddIdOrden2" readonly="readonly"/>
                        <table border="0" id="tblDetalleSolicitud" width="100%">
                            <tr>
                                <td id="tdListadoDetalleSolicitud"></td>
                            </tr>
                        </table>
                    </form>
                </fieldset>
            </td>
        </tr>
        <tr align="right">
            <td>
                <input type="button" onclick="$('divFlotante').style.display = 'none';" value="Cancelar"/>
            </td>
        </tr>
    </table>
</div>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
    <div id="divFlotanteTitulo1" class="handle">
        <table>
            <tr>
                <td id="tdFlotanteTitulo1" width="100%"></td>
            </tr>
        </table>
    </div>
    <table border="0" id="tblListados1" style="display:none" width="500px">
        <tr>
            <td id="tdDescripcionArticulo"></td>
        </tr>
        <tr>
            <td align="right" id="tdBotonesDiv">
                <hr/>
                <input type="button" onclick="$('divFlotante1').style.display='none';" value="Cancelar"/>
            </td>
        </tr>
    </table>
    <table border="0" id="tblAlmacen" style="display:none" width="550">
        <tr>
            <td>
                <form id="frmAlmacen" name="frmAlmacen" style="margin:0">
                    <table width="100%">
                        <tr>
                            <td valign="top" width="40%">
                                <table width="100%">
                                    <tr>
                                        <td align="right" class="tituloCampo" width="26%">
                                            <span class="textoRojoNegrita">*</span>Código:
                                        </td>
                                        <td width="74%">
                                            <input type="hidden" id="hddNumeroArt2" name="hddNumeroArt2" readonly="readonly"/>
                                            <input type="hidden" id="hddIdDetalleSolicitud" name="hddIdDetalleSolicitud" readonly="readonly"/>
                                            <input type="hidden" id="hddIdArticulo" name="hddIdArticulo" readonly="readonly"/>
                                            <input type="text" id="txtCodigoArticulo" name="txtCodigoArticulo" size="30" readonly="readonly"/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right" class="tituloCampo">
                                            <span class="textoRojoNegrita">*</span>Articulo:
                                        </td>
                                        <td>
                                            <textarea id="txtDescripcionArticulo" name="txtDescripcionArticulo" cols="60" rows="3" readonly="readonly"></textarea>
                                        </td>
                                    </tr>
                                    <tr style="display:none">
                                        <td align="right" class="tituloCampo">
                                            <span class="textoRojoNegrita">*</span>Empresa:
                                        </td>
                                        <td id="tdlstEmpresa">
                                            <select id="lstEmpresa" name="lstEmpresa">
                                                <option value="-1">Seleccione...</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right" class="tituloCampo">
                                            <span class="textoRojoNegrita">*</span>Almacen:
                                        </td>
                                        <td id="tdlstAlmacenAct">
                                            <select id="lstAlmacenAct" name="lstAlmacenAct">
                                                <option value="-1">Seleccione...</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right" class="tituloCampo">
                                            <span class="textoRojoNegrita">*</span>Ubicacion:
                                        </td>
                                        <td id="tdlstCasillaAct">
                                            <select id="lstCasillaAct" name="lstCasillaAct">
                                                <option value="-1">Seleccione...</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right" class="tituloCampo">
                                            <span class="textoRojoNegrita">*</span>Disponibilidad:
                                        </td>
                                        <td>
                                            <input name="txtCantidadDisponible" id="txtCantidadDisponible" type="text" readonly="readonly" size="14" style="text-align:right"/>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </form>
            </td>
        </tr>
        <tr>
            <td align="right">
                <hr/>
                <input type="button" onclick="validarFormAlmacen();" value="Aceptar"/>
                <input type="button" onclick="$('divFlotante1').style.display = 'none';" value="Cancelar"/>
            </td>
        </tr>
    </table>
</div>

<script language="javascript" type="text/javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);

        var theHandle = document.getElementById("divFlotanteTitulo1");
	var theRoot   = document.getElementById("divFlotante1");
	Drag.init(theHandle, theRoot);
	
	xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>',''); //buscador
</script>
