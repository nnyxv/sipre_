<?php
require_once ("../connections/conex.php");

session_start();

define('PAGE_PRIV','sa_listado_comisiones');//sa_listado_comisiones nuevo gregor //sa_comisiones antes no tenia

include ("../inc_sesion.php");

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_sa_listado_comisiones.php");

include("controladores/ac_iv_general.php");//necesario para el listado de empresa final

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

        <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Listados Comisiones</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
        
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
   
    
	<script type="text/javascript" language="javascript" src="../js/mootools.js"></script>
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
		max-width:1000px;
		position:absolute;
	}
	
	.handle {
		padding:2px;
		background-color:#4E6D12;
		color:#FFFFFF;
		font-weight:bold;
		cursor:move;
	}
	
	</style>
    <script language="javascript" type="text/javascript">
		function validar(){
			if (validarCampo('selTipoEmpleado','t','listaExceptCero')
			&&  validarCampo('txtFechaDesde','t','')
			&&  validarCampo('txtFechaHasta','t','')){
					if ($('selTipoEmpleado').value == 0){
						xajax_listarComisionesMecanicos($('selEmpleado').value,$('rdoTipoListado0').checked,$('txtFechaDesde').value, $('txtFechaHasta').value);
					}
					else{
						xajax_listarComisionesGerentes($('selEmpleado').value,$('selTipoEmpleado').value,$('txtFechaDesde').value, $('txtFechaHasta').value);
					}
				}
			else{
				validarCampo('selTipoEmpleado','t','listaExceptCero')
				validarCampo('txtFechaDesde','t','')
				validarCampo('txtFechaHasta','t','')
				alert("Los campos señalados en rojo son requeridos");
			}
		}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	
    <?php include("banner_servicios.php"); ?>
    <div id="divInfo" class="print">
    	<table width="100%" border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td id="tdTituloPaginaServicios" class="tituloPaginaServicios" colspan="2" >Listados Comisiones</td>
            </tr>
            <tr>
               	<td width="100%" align="left">
                	<button class="noprint" type="button" id="btnImprimir" name="btnImprimir" onclick="window.print();" style="cursor:default">
                    	<table align="center" cellpadding="0" cellspacing="0">
                        	<tr>
                            	<td>&nbsp;</td>
                                <td><img src="../img/iconos/print.png"/>
                                </td><td>&nbsp;</td>
                                <td>Imprimir</td>
                            </tr>
                        </table>
                    </button>
                </td>
            </tr>
            <tr>
            	<td>
                    <form id="frmBuscar" name="frmBuscar" style="margin:0">
                        <table align="left" border="0" width="100%">
                        <tr>
                            <td align="right" class="tituloCampo" width="6%">Empresa:</td>
                            <td id="tdlstEmpresa" width="15%" align="left">
                              
							  <script>
                                  //xajax_cargaLstEmpresas(1);
                                </script>
							</td>
                          <td align="right" class="tituloCampo noprint" width="8%">Tipo Empleado:</td>
                            <td width="11%" align="left" class="noprint">
<select id="selTipoEmpleado" name="selTipoEmpleado" onchange="xajax_cargarSelectEmpleados(this.value, document.getElementById('lstEmpresa').value);">
                                	<option value="-1">Seleccione...</option>
                                    <option value="0">Mecanicos</option>
                                    <!--<option value="1">Jefes de Taller</option>
                                    <option value="2">Asesores</option>
                                    <option value="3">Gte. Operacion</option>
                                    <option value="4">Gte. Post-Venta</option>-->
                                </select>
							</td>
                          <td align="right" class="tituloCampo noprint" width="7%" >Empleado:</td>
                            <td width="28%" align="left" id="tdSelEmpleado" class="noprint">
<select id="selEmpleado" name="selEmpleado" disabled="disabled">
                                	<option value="0">Todos...</option>
                                </select>
							</td>
                    
                        <td width="10%" align="right" class="tituloCampo" >Fecha Desde:</td>
                        <td align="left">
                            <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <input type="text" name="txtFechaDesde" id="txtFechaDesde" readonly="readonly"/>
                                    </td>
                                    <td>
                                        <div style="float:left"><img src="../img/iconos/ico_date.png" id="imgFechaDesde" name="imgFechaDesde" class="puntero" /></div>
                                        <script type="text/javascript">
                                            Calendar.setup({
                                            inputField : "txtFechaDesde",
                                            ifFormat : "%d-%m-%Y",
                                            button : "imgFechaDesde"
                                        });
                                        </script>
                                   </td>
                            </tr>
                           </table>
                           
                           
                           
                        </td>
                          <td width="10%" align="right" class="tituloCampo" >Fecha Hasta:</td>
                        <td align="left">
                            <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <input type="text" name="txtFechaHasta" id="txtFechaHasta" readonly="readonly"/>
                                    </td>
                                    <td>
                                        <div style="float:left"><img src="../img/iconos/ico_date.png" id="imgFechaHasta" name="imgFechaHasta" class="puntero" /></div>
                                        <script type="text/javascript">
                                            Calendar.setup({
                                            inputField : "txtFechaHasta",
                                            ifFormat : "%d-%m-%Y",
                                            button : "imgFechaHasta"
                                        });
                                        </script>
                                   </td>
                            </tr>
                           </table>
                           
                           
                           
                        </td>
                       
                       
                            <td width="7%" class="noprint">
                            <button class="noprint" type="button" id="btnBuscar" name="btnBuscar" style="cursor:default" onclick="validar()">Generar</button>
                            </td>             
                        </tr>
                        <tr id="trRadio" style="display:none">
                        	<td colspan="7" align="left">
                            	<table border="0" width="40%">
                                	<tr>
                                        <td align="right" class="tituloCampo noprint" width="10%">
                                            Listado Detallado
                                        </td>
                                        <td class="noprint" width="1%">
                                            <input type="radio" id="rdoTipoListado0" name="rdoTipoListado" checked="checked" value="0"/>
                                        </td>
                                        <td align="right" class="tituloCampo noprint" width="10%">
                                            Listado Resumido
                                        </td>
                                        <td class="noprint" width="1%">
                                            <input type="radio" id="rdoTipoListado1" name="rdoTipoListado" value="1"/>
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
            	<td id="tdComisiones">
                </td>
            </tr>
        </table>
    </div>
	
    <div class="noprint" >
	<?php include("menu_serviciosend.inc.php"); ?>
    </div>
    
</div>
</body>
</html>

<script>
	xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>','onchange="recargarEmpleados();"'); 
	
	function recargarEmpleados(){
		xajax_cargarSelectEmpleados( -1 /* document.getElementById('selTipoEmpleado').value, document.getElementById('lstEmpresa').value*/);
		document.getElementById('selTipoEmpleado').value = -1;
	}

</script>