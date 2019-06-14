<?php
require_once ("../connections/conex.php");

session_start();

define('PAGE_PRIV','sa_historico_orden_list');//nuevo gregor
//define('PAGE_PRIV','sa_retrabajo');//anterior

//define('PAGE_PRIV','sa_devolucion_vale_salida_list');//ni idea

require_once("../inc_sesion.php");

if(!(validaAcceso(PAGE_PRIV))) {
	echo "
	<script>
		alert('Acceso Denegado');
		window.location.href = 'index.php';
	</script>";
}


$currentPage = $_SERVER["PHP_SELF"];

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_sa_historico_orden_list.php");

include("../connections/conex.php");//usa el vldtipodato que necesita ac_iv_general
include("controladores/ac_iv_general.php"); //tiene el cargaLstEmpresaFinal
	
$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	
        <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Listado de Ordenes de Servicios</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
        
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
	
	<script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
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
		background-color:#4E6D12;
		color:#FFFFFF;
		font-weight:bold;
		cursor:move;
	}
	</style>
    <script>
    function validarFormDetenerOrden() {
		if (validarCampo('lstMotivoDetencion','t','lista') == true) {
			if(confirm("Esta seguro de Detener la Orden?"))
			{
				xajax_guardarDetencionOrden(xajax.getFormValues('frmDetenerOrden'));
			}

		} else {
			validarCampo('lstMotivoDetencion','t','lista');
				
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	function validarFormReanudarOrden() {
		if (validarCampo('lstReanudarOrden','t','lista') == true) {
			if(confirm("Esta seguro de Reanudar la Orden?"))
			{
				xajax_guardarReanudoOrden(xajax.getFormValues('frmReanudarOrden'));
			}

		} else {
			validarCampo('lstReanudarOrden','t','lista');
				
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	function validarFormRetrabajoOrden() {
		if (validarCampo('txtMotivoRetrabajo','t','') == true) {
			if(confirm("Esta seguro de hacer el retrabajo a la Orden?"))
			{
				xajax_guardarRetrabajoOrden(xajax.getFormValues('frmRetrabajo'));
			}

		} else {
			validarCampo('txtMotivoRetrabajo','t','');
				
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFormAprobacionOrden()
	{
		if (validarCampo('txtClaveAprobacion','t','') == true) {
			if(confirm("Esta seguro de Aprobar la Orden?"))
			{
				xajax_aprobarOrden(xajax.getFormValues('frmClaveAprobacionOrden'));
			}

		} else {
			validarCampo('txtClaveAprobacion','t','');
				
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include("banner_servicios.php"); ?>
    </div>
    
    <div id="divInfo" class="print">
    <table border="0" width="100%">
        <tr>
        	<td id="tdTituloListado" class="tituloPaginaServicios">Retrabajo Ordenes de Servicio</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="right">
            	<table align="left">
                <tr>
                	<td width="97" id="tdBtnNuevoDoc" style="display:none">
                    <button class="noprint" type="button" id="btnNuevo" name="btnNuevo" onclick="window.open('sa_orden_form.php?doc_type=2&id=&ide=<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>&acc=1','_self');" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td width="10">&nbsp;</td>
                    <td width="17"><img src="../img/iconos/ico_new.png"/></td>
                    <td width="10">&nbsp;</td>
                    <td width="36">Nuevo</td>
                    </tr></table></button>
                    
                  </td>
                  <td width="112"><button class="noprint" type="button" id="btnImprimir" name="btnImprimir" onclick="window.print();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/print.png"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button></td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" onsubmit="xajax_buscarOrden(xajax.getFormValues('frmBuscar')); return false;" style="margin:0">
                <table align="right" border="0">
                <tr>
                	
                	<td align="right" class="tituloCampo" width="100">Empresa:</td>
                    <td id="tdlstEmpresa">
                        <!--<select id="lstEmpresa" name="lstEmpresa">
                            <option value="-1">Todos...</option>
                        </select>-->
                        <script>
                          //xajax_cargaLstEmpresas();
                        </script>                    </td>
                    <td class="tituloCampo">Tipo:</td>
                    <td id="tdlstTipoOrden">
                        <select id="lstTipoOrden" name="lstTipoOrden">
                            <option value="-1">Todos...</option>
                        </select>
                        <script>
                            xajax_cargaLstTipoOrden();
                        </script>                    </td>
                    <td align="right" class="tituloCampo" width="150">Código / Descripción:</td>
                    <td id="tdlstAno"><input type="text" id="txtPalabra" name="txtPalabra" onkeyup="$('btnBuscar').click();"/></td>
                    <td>
                    <button class="noprint" type="button" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarOrden(xajax.getFormValues('frmBuscar'));" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/find.png"/></td><td>&nbsp;</td><td>Buscar</td></tr></table></button>
						<button class="noprint" type="button" onclick="document.forms['frmBuscar'].reset(); $('btnBuscar').click();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/view.png"/></td><td>&nbsp;</td><td>Ver Todo</td></tr></table></button>                    </td>
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
    
   <form id="frmDetenerOrden" name="frmDetenerOrden" style="margin:0">
    <table width="35%" border="0" id="tblDetencionOrden">
      <tr>
        <td colspan="2" id="tdTituloListado">&nbsp;</td>
          </tr>
          <tr>
            <td width="33%">&nbsp;</td>
            <td width="67%">&nbsp;</td>
          </tr>
          <tr align="left">
            	<td align="right" class="tituloCampo" width="140">Nro Orden:</td>
                <td>
                <input type="text" id="txtNroOrden" name="txtNroOrden" size="30" readonly="readonly">
                <input type="hidden" id="hddValBusq" name="hddValBusq" size="30" readonly="readonly">
                <input type="hidden" id="hddPageNum" name="hddPageNum" size="30" readonly="readonly">
                <input type="hidden" id="hddCampOrd" name="hddCampOrd" size="30" readonly="readonly">
                <input type="hidden" id="hddTpOrd" name="hddTpOrd" size="30" readonly="readonly">
                <input type="hidden" id="hddMaxRows" name="hddMaxRows" size="30" readonly="readonly"></td>
            </tr>
          <tr align="left">
            <td class="tituloCampo">Motivo:</td>
            <td id="tdListMotivoDetencionOrden">
                <select id="lstMotivoDetencion" name="lstMotivoDetencion">
                  <option value="-1">Seleccione...</option>
                </select>
                <script>
                     xajax_cargaLstMotivoDetencionOrden();
                </script>    
            </td>
          </tr>
          <tr>
            <td class="tituloCampo">Observacion:</td>
            <td><textarea name="txtAreaObservacionDetencion" id="txtAreaObservacionDetencion" cols="45" rows="5"></textarea></td>
          </tr>
          <tr><td colspan="2">&nbsp;</td></tr>
          <tr>
        <td align="right" colspan="2">
            <hr>
            
            <input type="button" id="btnGuardar" name="btnGuardar" onclick="validarFormDetenerOrden();" value="Guardar" />
            <input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar">
        </td>
    </tr>
  </table> 
  </form>
  <form id="frmRetrabajo" name="frmRetrabajo" style="margin:0">
    <table width="35%" border="0" id="tblRetrabajoOrden">
      <tr>
        <td colspan="2" id="tdTituloListado">&nbsp;</td>
          </tr>
          <tr>
            <td width="33%">&nbsp;</td>
            <td width="67%">&nbsp;</td>
          </tr>
          <tr align="left">
            	<td class="tituloCampo" width="140">Nro Orden:</td>
                <td>
                <input type="text" id="txtNroOrdenRet" name="txtNroOrdenRet" size="30" readonly="readonly">
                <input type="hidden" id="hddValBusqRet" name="hddValBusqRet" size="30" readonly="readonly">
                <input type="hidden" id="hddPageNumRet" name="hddPageNumRet" size="30" readonly="readonly">
                <input type="hidden" id="hddCampOrdRet" name="hddCampOrdRet" size="30" readonly="readonly">
                <input type="hidden" id="hddTpOrdRet" name="hddTpOrdRet" size="30" readonly="readonly">
                <input type="hidden" id="hddMaxRowsRet" name="hddMaxRowsRet" size="30" readonly="readonly"></td>
           </tr>
          <tr align="left">
            <td class="tituloCampo">Motivo:</td>
            <td><textarea name="txtMotivoRetrabajo" id="txtMotivoRetrabajo" cols="45" rows="5"></textarea></td>
          </tr>
          <tr><td colspan="2">&nbsp;</td></tr>
          <tr>
        <td align="right" colspan="2">
            <hr>
            
            <input type="button" id="btnGuardarRetrabajo" name="btnGuardarRetrabajo" onclick="validarFormRetrabajoOrden();" value="Guardar" />
            <input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar">
        </td>
    </tr>
  </table> 
  </form>
  <form id="frmReanudarOrden" name="frmReanudarOrden" style="margin:0">
      <table width="35%" border="0" id="tblReanudarOrden">
          <tr>
            <td colspan="2" id="tdTituloListado">&nbsp;</td>
              </tr>
              <tr>
                <td width="33%">&nbsp;</td>
                <td width="67%">&nbsp;</td>
              </tr>
              <tr align="left">
                    <td align="right" class="tituloCampo" width="140">Nro Orden:</td>
                    <td>
                    <input type="text" id="txtNroOrdenRe" name="txtNroOrdenRe" size="30" readonly="readonly">
                    <input type="hidden" id="hddValBusqRe" name="hddValBusqRe" size="30" readonly="readonly">
                    <input type="hidden" id="hddPageNumRe" name="hddPageNumRe" size="30" readonly="readonly">
                    <input type="hidden" id="hddCampOrdRe" name="hddCampOrdRe" size="30" readonly="readonly">
                    <input type="hidden" id="hddTpOrdRe" name="hddTpOrdRe" size="30" readonly="readonly">
                    <input type="hidden" id="hddMaxRowsRe" name="hddMaxRowsRe" size="30" readonly="readonly"></td>
                </tr>
              <tr align="left">
                <td class="tituloCampo">Motivo:</td>
                <td id="tdListReanudarOrden">
                    <select id="lstReanudarOrden" name="lstReanudarOrden">
                      <option value="-1">Seleccione...</option>
                    </select>
                    <script>
                         xajax_cargaLstReanudarOrden();
                    </script>    
                </td>
              </tr>
              <tr>
                <td class="tituloCampo">Observacion:</td>
                <td><textarea name="txtAreaObservacionReanudo" id="txtAreaObservacionReanudo" cols="45" rows="5"></textarea></td>
              </tr>
              <tr><td colspan="2">&nbsp;</td></tr>
              <tr>
            <td align="right" colspan="2">
                <hr>
                
                <input type="button" id="btnGuardar" name="btnGuardar" onclick="validarFormReanudarOrden();" value="Guardar" />
                <input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar">
            </td>
        </tr>
      </table>
  </form>
<form id="frmClaveAprobacionOrden" name="frmClaveAprobacionOrden" style="margin:0">
      <table width="40%" border="0" id="tblClaveAprobacionOrden">
          <tr>
            <td colspan="2" id="tdTituloListado">&nbsp;</td>
              </tr>
              <tr>
                <td width="50%">&nbsp;</td>
                <td width="50%">&nbsp;</td>
              </tr>
              <tr>
                    <td align="right" class="tituloCampo" width="41%">Nro Orden:</td>
                    <td>
                    <input type="text" id="txtNroOrdenAprob" name="txtNroOrdenAprob"  readonly="readonly">
                    <input type="hidden" id="txtIdClaveUsuario" name="txtIdClaveUsuario"  readonly="readonly">
                    <input type="hidden" id="hddValBusqAprob" name="hddValBusqAprob"  readonly="readonly">
                    <input type="hidden" id="hddPageNumAprob" name="hddPageNumAprob"  readonly="readonly">
                    <input type="hidden" id="hddCampOrdAprob" name="hddCampOrdAprob"  readonly="readonly">
                    <input type="hidden" id="hddTpOrdAprob" name="hddTpOrdAprob"  	readonly="readonly">
                    <input type="hidden" id="hddMaxRowsAprob" name="hddMaxRowsAprob"  readonly="readonly">
                    <input type="hidden" id="hddIdMecanicoAprob" name="hddIdMecanicoAprob"  readonly="readonly">
                    <input type="hidden" id="hddIdJefeTallerAprob" name="hddIdJefeTallerAprob"  readonly="readonly">
                    <input type="hidden" id="hddIdControlTallerAprob" name="hddIdControlTallerAprob"  readonly="readonly">
				</td>
                </tr>
              <tr>
                <td align="right" class="tituloCampo">Clave:</td>
                <td><label>
                  <input name="txtClaveAprobacion" id="txtClaveAprobacion" type="password" class="inputInicial" />
                </label></td>
              </tr>
              <tr><td colspan="2">&nbsp;</td></tr>
              <tr>
            <td align="right" colspan="2">
                <hr>
                
                <input type="button" id="btnGuardar" name="btnGuardar" onclick="validarFormAprobacionOrden();" value="Guardar" />
                <input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar">            </td>
        </tr>
      </table>
</form>

</div>
<script>
	xajax_buscarOrden(xajax.getFormValues('frmBuscar'));
</script>
<script language="javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
	
	xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>',''); //buscador
</script>

