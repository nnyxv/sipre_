<?php
require_once ("../connections/conex.php");

session_start();

define('PAGE_PRIV','sa_presupuesto_list');//nuevo gregor
//define('PAGE_PRIV','sa_presupuesto');//anterior

require_once("../inc_sesion.php");

$currentPage = $_SERVER["PHP_SELF"];

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_sa_presupuesto_list.php");

include("../connections/conex.php");//usa el vldtipodato que necesita ac_iv_general
include("controladores/ac_iv_general.php"); //tiene el cargaLstEmpresaFinal


$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	
        <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Presupuestos</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
                
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
	
    <link rel="stylesheet" type="text/css" href="../js/domDragServicios.css">
	<script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/> 
    <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>    
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include("banner_servicios.php"); ?>
    </div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaServicios">Presupuestos de Venta</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left">
                    <tr>
                        <td><input class="noprint" type="button" value="Nuevo" onclick="window.open('sa_orden_form.php?doc_type=1&id=&ide=<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>&acc=1','_self');" style="display:none"/></td>
                        <td><button type="button" id="btnNuevo" name="btnNuevo" class="noprint" onclick="window.open('sa_presupuesto_form.php?doc_type=1&id=&ide=<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>&acc=1','_self');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_white_get.png" alt="new"/></td><td>&nbsp;</td><td>Nuevo Presupuesto</td></tr></table></button></td>
                    </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" onsubmit="xajax_buscarPresupuesto(xajax.getFormValues('frmBuscar')); return false;" style="margin:0">
                <table align="right" border="0">
                    <tr>
                        <td align="right" class="tituloCampo" width="100">Empresa:</td>
                        <td id="tdlstEmpresa" colspan="3">
                          <!--  <select id="lstEmpresa" name="lstEmpresa">
                                <option value="-1">[ Todos ]</option>
                            </select>-->
                            <script>
                                //xajax_cargaLstEmpresa();
                            </script>
                        </td>
                 
                        <td align="right" class="tituloCampo" width="100">Desde:</td>
                        <td>
                            <div style="float:left">
                                <input type="text" id="txtFechaDesde" name="txtFechaDesde" readonly="readonly" size="10" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="../img/iconos/ico_date.png" id="imgFechaDesde" name="imgFechaDesde" class="puntero noprint"/>
                                    <script type="text/javascript">
                                        Calendar.setup({
                                        inputField : "txtFechaDesde",
                                        ifFormat : "%d-%m-%Y",
                                        button : "imgFechaDesde"
                                        });
                                    </script>
                            </div>
                        </td>
                        <td align="right" class="tituloCampo" width="100">Hasta:</td>
                        <td>
                            <div style="float:left">
                                <input type="text" id="txtFechaHasta" name="txtFechaHasta" readonly="readonly" size="10" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="../img/iconos/ico_date.png" id="imgFechaHasta" name="imgFechaHasta" class="puntero noprint"/>
                                    <script type="text/javascript">
                                        Calendar.setup({
                                        inputField : "txtFechaHasta",
                                        ifFormat : "%d-%m-%Y",
                                        button : "imgFechaHasta"
                                        });
                                    </script>
                            </div>
                        </td>
                    </tr>                
                    <tr align="right">                  	
                        <td colspan="4"></td>
                        <td align="right" class="tituloCampo" width="100" >Criterio:</td>
                        <td id="tdlstAno" colspan="2" align="left">
                        	<input type="text" id="txtPalabra" name="txtPalabra" onkeyup="$('btnBuscar').click();"/>
						</td>
                        <td colspan="1">
                            <input type="button" class="noprint" id="btnBuscar" onclick="xajax_buscarPresupuesto(xajax.getFormValues('frmBuscar'));" value="Buscar"/>
							<input type="button" class="noprint" onclick="document.forms['frmBuscar'].reset(); $('btnBuscar').click();" value="Limpiar"/>
                        </td>
                    </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td id="tdListaPresupuestoVenta"></td>
        </tr>
        </table>
    </div>
    
    <div class="noprint">
	<?php include("menu_serviciosend.inc.php"); ?>
    </div>
</div>
</body>
</html>


<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
    <table border="0" id="tblDcto" width="980px">
        <tr>
            <td>
                <table>
                    <tr>
                        <td align="right" class="tituloCampo" width="140">Código:</td>
                        <td><input type="text" id="txtCodigoArticulo" name="txtCodigoArticulo" size="30" readonly="readonly"></td>
                    </tr>
                    <tr>
                        <td align="right" class="tituloCampo">Descripcion:</td>
                        <td><textarea id="txtArticulo" name="txtArticulo" cols="75" rows="3" readonly="readonly"></textarea></td>
                    </tr>
                    <tr>
                        <td align="right" class="tituloCampo" id="tdTituloCampoDcto" width="100"></td>
                        <td><input type="text" id="txtCantidad" name="txtCantidad" size="30" readonly="readonly"></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td id="tdListadoDcto">
                <table width="100%">
                    <tr class="tituloColumna">
                        <td>Código</td>
                        <td>Descripción</td>
                        <td>Marca</td>
                        <td>Tipo</td>
                        <td>Sección</td>
                        <td>Sub-Sección</td>
                        <td>Disponible</td>
                        <td>Reservado</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right">
                <hr>
                <input type="button" onclick="validarFormArt();" value="Aceptar">
                <input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar">
            </td>
        </tr>
    </table>
</div>

<script>
	xajax_listadoPresupuestos(0,'numero_presupuesto','DESC');
</script>

<script language="javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
	
	xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>','','','','','unico'); //buscador
	/*
	function limpiar_select(){			
				var listadoDeEmpresas = document.getElementById("lstEmpresa");				
				var grupo =listadoDeEmpresas.getElementsByTagName('optgroup');				
				
				for(i=0; i<grupo.length; i++){					
					grupo[i].label = "";
				}
					 
				for(i=0; i<listadoDeEmpresas.length; i++){
					 
						if(listadoDeEmpresas[i].selected == true){
							//alert("si");
							}else{
								listadoDeEmpresas[i].style.display="none"; 
								}				
					}					
			}			

*/
</script>
