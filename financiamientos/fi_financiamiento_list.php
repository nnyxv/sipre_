<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
require_once("../inc_sesion.php");
if(!(validaAcceso("fi_financiamiento_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_fi_financiamiento_list.php");
//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Financiamientos</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragFinanciamientos.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script src="../js/highcharts/js/highcharts.js"></script>
	<script src="../js/highcharts/js/modules/exporting.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
	
	<script type="text/javascript">

	
	function abrirDivFlotante(idObj,tituloDiv ,idPedido,hddMostrarNCC){ 	

		document.forms['frmListaMotivos'].reset();
		byId('frmListaMotivos').style.display = 'block';
		titulo = "Asignar Motivos";		
		byId('hddIdPedido').value = idPedido;
		openImg(idObj);
		byId(tituloDiv).innerHTML = titulo;
		xajax_cargarCampos(idPedido,'Motivos','3',hddMostrarNCC,''); //3 = ambos motivos
		
	}	

	function abrirDivFlotante1(nomObjeto, verTabla, valor) {

		 if (valor == "NDC") {
			document.forms['frmBuscarMotivo'].reset();
			
			byId('hddObjDestinoMotivo').value = valor;
			
			byId('btnBuscarMotivo').click();
			
			tituloDiv1 = 'Motivos Notas de Cargo';
			
		} else if (valor == "NCC") {
			document.forms['frmBuscarMotivo'].reset();
			
			byId('hddObjDestinoMotivo').value = valor;
			
			byId('btnBuscarMotivo').click();
			
			tituloDiv1 = 'Motivos Notas de Credito';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		byId('txtCriterioBuscarMotivo').focus();
		byId('txtCriterioBuscarMotivo').select();
		
	}

	function validarLstMotivo(){
		if(byId('hddObjDestinoMotivo').value == 'NCC'){
			xajax_buscarMotivo(xajax.getFormValues('frmBuscarMotivo'),'NCC')
		}else{
			xajax_buscarMotivo(xajax.getFormValues('frmBuscarMotivo'),'NDC')
		}
	}

	function validarInsertarMotivo(idMotivo) {
		if(byId('hddObjDestinoMotivo').value == 'NCC'){
			xajax_insertarMotivo(idMotivo, xajax.getFormValues('frmListaMotivos'),'NCC');//INSERTAR MOTIVO NOTA DE CREDITO
		}else{
			xajax_insertarMotivo(idMotivo, xajax.getFormValues('frmListaMotivos'),'NDC'); //INSERTAR MOTIVO NOTA DE DEBITO
		}
	}
	
	</script> 
</head>

<body>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_financiamiento.php"); ?></div>
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaFinanciamientos">Financiamientos</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    	<button type="button" onclick="window.open('fi_form_financiamiento.php','_self');" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="3"></td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Fecha:</td>
                    <td>
                    	<table cellpadding="0" cellspacing="0">
                        <tr>
                        	<td>&nbsp;Desde:&nbsp;</td>
                        	<td><input type="text" id="txtFechaDesde" name="txtFechaDesde" autocomplete="off" size="10" style="text-align:center"/></td>
                        	<td>&nbsp;Hasta:&nbsp;</td>
                        	<td><input type="text" id="txtFechaHasta" name="txtFechaHasta" autocomplete="off" size="10" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Estatus:</td>
                    <td>
                    	<select id="lstEstatusPedido" name="lstEstatusPedido" onchange="byId('btnBuscar').click();">
                        	<option value="-1">[ Seleccione ]</option>
                            <option value="0">No Aprobado </option>
                            <option value="3">Aprobado</option>
                            <option value="1">Parcialmente Pagado</option>
                            <option value="2">Pagado</option>
                            <option value="4">Atrasado</option>
                        </select>
                    </td>
					<td align="right" class="tituloCampo" width="120">Criterio:</td>
					<td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
					<td>
						<button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarPedido(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
					</td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaPedido" name="frmListaPedido" style="margin:0">
            	<div id="divListaPedido" style="width:100%"></div>
            </form>
            </td>
        </tr>
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
					<td align="center">
                    	<table>
                    	<tr>
                            <td><img src="../img/iconos/pencil.png"/></td><td>Editar Pedido</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/accept.png"/></td><td>Autorizar Pedido</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/cancel.png"/></td><td>Anular Pedido</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page_white_acrobat.png"/></td><td>Imprimir Pedido</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/mp_facturado.png"/></td><td>Imprimir Nota Debito Asociada</td>
                        </tr>
                        </table>
                    </td>
				</tr>
				</table>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
            	<table>
	                <tbody><tr align="right">
	                	<td class="tituloCampo" width="120">Total Inicial(es):</td>
	                    <td width="150"><span id="spnTotalNeto"></span></td>
					</tr>
	                <tr align="right">
	                    <td class="tituloCampo">Total Interes(es):</td>
	                    <td><span id="spnTotalInteres"></span></td>
					</tr>
	                <tr align="right" id="trTotalAdicionales" name="trTotalAdicionales" style="display: none;">
	                    <td class="tituloCampo">Total Adicional(es):</td>
	                    <td><span id="spnTotalAdicionales"></span></td>
					</tr>
	                <tr class="trResaltarTotal" align="right">
	                    <td class="tituloCampo">Total Cuota(s):</td>
	                    <td><span id="spnTotalFacturas"></span></td>
	                </tr>
	                </tbody>
                </table>
            </td>
         </tr>
       </table>
   </div>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>


<!-- MODALES FLOTANTES -->



<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td><td><a onclick="byId('divFlotante').style.display='none';" id="aCerrarDivFlotante"><img title="Cerrar" src="../img/iconos/cross.png" id="imgCerrarDivFlotante" class="close puntero"></a></td></tr></table></div>
	<form id="frmListaMotivos" name="frmListaMotivos" onsubmit="return false;" style="margin:0">
		<div class="pane" style="max-height:520px; overflow:auto; width:960px;">
	        <table border="0" id="tblProspecto" width="100%">
                <tr>
		        	<td>
		            	<table cellpadding="10px" cellspacing="0" class="divMsjInfo2" width="100%">
						<tr>
							<td width="10px"><img src="../img/iconos/ico_info.gif"/></td>
							<td align="left">
								A continuacion se tienen dos pestañas <b>Motivos Nota Debito</b> y <b>Motivos Nota Credito.</b>
								La primera se utiliza para signar motivos asociado al pedido de financiamiento.
								La segunda se utiliza para asignar los motivos de las <b>Facturas de Vehiculos</b> si en dado caso
								Existiese una factura asociada al pedido de financiamiento.
		                    </td>
						</tr>
						</table>
		            </td>
		        </tr>
		        <tr>
		            <td>
		                <div class="wrap">
		                    <!-- the tabs -->
		                    <ul class="tabs">
		                        <li><a class="xl" id="liTabNDC" name="liTabNDC" href="#"><b>Motivos Nota Debito</b></a></li>
		                        <li id="liTabNCC" name="liTabNCC"><a class="xl" href="#"><b>Motivos Nota Credito</b></a></li>
		                    </ul>
		                    
		                    
		                    
		                    <!-- tab "panes" Nota de Cargo-->
		                    <div class="pane">
		                        <table border="0" width="100%">
							        <tr id="trListaMotivoNDC" align="left">
							            <td>
							                <table border="0" width="100%">
								                <tr>
								                    <td align="left" colspan="20">
								                        <a class="modalImg" id="aAgregarMotivoNDC" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaMotivo', 'NDC');">
								                            <button type="button" title="Agregar Motivo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
								                        </a>
								                        <button type="button" id="btnQuitarMotivoNDC" name="btnQuitarMotivoNDC" onclick="xajax_eliminarMotivoLote(xajax.getFormValues('frmListaMotivos'),'NDC');" title="Eliminar Artículo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
								                    </td>
								                </tr>
								                <tr align="center" class="tituloColumna">
								                	<td><input type="checkbox" id="cbxItmNDC" onclick="selecAllChecks(this.checked,this.id,'frmListaMotivos');"/></td>
								                	<td width="4%">Nro.</td>
													<td width="14%">Código</td>
								                    <td width="40%">Descripción</td>
								                    <td width="16%">Módulo</td>
								                    <td width="16%">Tipo Transacción</td>
								                    <td width="10%">Total</td>
								                    <td><input type="hidden" id="hddObjItmMotivoNDC" name="hddObjItmMotivoNDC" readonly="readonly" title="hddObjItmMotivoNDC"/></td>
								                </tr>
					                          	<tr id="trItmPieNDC"></tr>
							                </table>
										</td>
		                          	</tr>
					                <tr width="100%">
										<td width="100%" align="right">
											<input type="hidden" id="hddObjNDC" name="hddObjNDC" readonly="readonly"/>
											<table border="0" width="100%">
											<tr width="100%">
												<td valign="top" width="50%">
							                    	<table width="100%">
								                        <tr align="left">
								                            <td class="tituloCampo">Observación:</td>
								                        </tr>
								                        <tr align="left">
								                            <td><textarea class="inputHabilitado" id="txtObservacionNDC" name="txtObservacionNDC" rows="3" style="width:99%"></textarea></td>
								                        </tr>
							                        </table>
												</td>
												<td valign="top" width="50%">
													<table border="0" width="100%">
														<tr height="35px"></tr>
														<tr align="right" class="trResaltarTotal">
															<td class="tituloCampo" width="36%">Subtotal:</td>
								                            <td style="border-top:1px solid;" id="tdSubTotalMonedaNDC" width="42%"></td>
															<td style="border-top:1px solid;" width="22%"><input type="text" id="txtSubTotalNDC" name="txtSubTotalNDC" class="inputSinFondo" onblur="setFormatoRafk(this,2);" readonly="readonly" style="text-align:right"/></td>
														</tr>
								                        <tr align="right" class="trResaltarTotal3">
								                            <td class="tituloCampo">Saldo Disponible:</td>
								                            <td id="tdTotalSaldoMonedaNDC"></td>
								                            <td><input type="text" id="txtTotalSaldoNDC" name="txtTotalSaldoNDC" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
								                       		<input type="hidden" id="hddTotalSaldoNDC" name="hddTotalSaldoNDC"/>
								                        </tr>
													</table>
												</td>
											</tr>
											</table>
										</td>
									</tr>
		                        </table>
		                    </div>
		                    
		                    <!-- tab "panes" Notas de Creditos-->
		                    <div class="pane" id="divTabNCC" name="divTabNCC">
                        		 <table border="0" width="100%">
							        <tr id="trListaMotivoNCC" align="left">
							            <td>
							                <table border="0" width="100%">
								                <tr>
							                    	<td  style="display:none"><input type="hidden" id="hddMostrarNCC" name="hddMostrarNCC" readonly="readonly" title="hddObjItmMotivoNDC" value="0"/></td>
								                    <td align="left" colspan="20">
								                        <a class="modalImg" id="aAgregarMotivoNCC" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaMotivo', 'NCC');">
								                            <button type="button" title="Agregar Motivo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
								                        </a>
								                        <button type="button" id="btnQuitarMotivoNCC" name="btnQuitarMotivoNCC" onclick="xajax_eliminarMotivoLote(xajax.getFormValues('frmListaMotivos'),'NCC');" title="Eliminar Artículo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
								                    </td>
								                </tr>
								                <tr align="center" class="tituloColumna">
								                	<td><input type="checkbox" id="cbxItmNCC" onclick="selecAllChecks(this.checked,this.id,'frmListaMotivos');"/></td>
								                	<td width="4%">Nro.</td>
													<td width="14%">Código</td>
								                    <td width="40%">Descripción</td>
								                    <td width="16%">Módulo</td>
								                    <td width="16%">Tipo Transacción</td>
								                    <td width="10%">Total</td>
								                    <td><input type="hidden" id="hddObjItmMotivoNCC" name="hddObjItmMotivoNCC" readonly="readonly" title="hddObjItmMotivoNCC"/></td>
								                </tr>
								                <tr id="trItmPieNCC"></tr>
							                </table>
										</td>
		                          	</tr>
					                <tr>
										<td align="right">
											<input type="hidden" id="hddObjNCC" name="hddObjNCC" readonly="readonly"/>
											<table border="0" width="100%">
											<tr>
												<td valign="top" width="50%">
							                    	<table width="100%">
								                        <tr align="left">
								                            <td class="tituloCampo">Observación:</td>
								                        </tr>
								                        <tr align="left">
								                            <td><textarea class="inputHabilitado" id="txtObservacionNCC" name="txtObservacionNCC" rows="3" style="width:99%"></textarea></td>
								                        </tr>
							                        </table>
												</td>
												<td valign="top" width="50%">
													<table border="0" width="100%">
														<tr height="35px"></tr>
														<tr align="right" class="trResaltarTotal">
															<td class="tituloCampo" width="36%">Subtotal:</td>
								                            <td style="border-top:1px solid;" id="tdSubTotalMonedaNCC" width="42%"></td>
															<td style="border-top:1px solid;" width="22%"><input type="text" id="txtSubTotalNCC" name="txtSubTotalNCC" class="inputSinFondo" onblur="setFormatoRafk(this,2);" readonly="readonly" style="text-align:right"/></td>
														</tr>
								                        <tr align="right" class="trResaltarTotal3">
								                            <td class="tituloCampo">Saldo Disponible:</td>
								                            <td id="tdTotalSaldoMonedaNCC"></td>
								                            <td><input type="text" id="txtTotalSaldoNCC" name="txtTotalSaldoNCC" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
								                        	<input type="hidden" id="hddTotalSaldoNCC" name="hddTotalSaldoNCC"/>
								                        </tr>
													</table>
												</td>
											</tr>
											</table>
										</td>
									</tr>
		                        </table>
		                    </div>    
		                  
		                </div>
		            </td>
		        </tr>
		        <tr>
		            <td align="right"><hr>
		    			<input Type="hidden" id="hddIdPedido" name="hddIdPedido" />
		    			<button type="button" id="btnGuardarContrato" name="btnGuardarContrato"  onclick="xajax_validarPedidoListo(byId('hddIdPedido').value,xajax.getFormValues('frmListaMotivos'));">Guardar</button>
		            	<button type="button" id="btnCancelarContrato" name="btnCancelarContrato" class="close" >Cancelar</button> 
		            </td>
		        </tr>
	        </table>
		</div>
	</form>    
</div>


<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
    
    <table border="0" id="tblListaMotivo" width="760">
	    <tr>
	        <td>
	        <form id="frmBuscarMotivo" name="frmBuscarMotivo" style="margin:0" onsubmit="return false;">
	            <input type="hidden" id="hddObjDestinoMotivo" name="hddObjDestinoMotivo" readonly="readonly" />
	            <input type="hidden" id="hddNomVentanaMotivo" name="hddNomVentanaMotivo" readonly="readonly" />
	            <table align="right">
	            <tr align="left">
	                <td align="right" class="tituloCampo" width="120">Criterio:</td>
	                <td><input type="text" id="txtCriterioBuscarMotivo" name="txtCriterioBuscarMotivo" class="inputHabilitado" onkeyup="byId('btnBuscarMotivo').click();"/></td>
	                <td>
	                    <button type="submit" id="btnBuscarMotivo" name="btnBuscarMotivo" onclick="validarLstMotivo();">Buscar</button>
	                    <button type="button" onclick="document.forms['frmBuscarMotivo'].reset(); byId('btnBuscarMotivo').click();">Limpiar</button>
	                </td>
	            </tr>
	            </table>
	        </form>
	        </td>
	    </tr>
	    <tr>
	        <td>
	        <form id="frmListaMotivo" name="frmListaMotivo" style="margin:0" onsubmit="return false;">
	            <div id="divListaMotivo" style="width:100%"></div>
	        </form>
	        </td>
	    </tr>
	    <tr>
	        <td align="right"><hr>
	            <button type="button" id="btnCancelarListaMotivo" name="btnCancelarListaMotivo" class="close">Cerrar</button>
	        </td>
	    </tr>
    </table>
</div>



</body>
</html>

<script>

byId('txtFechaDesde').className = "inputHabilitado";
byId('txtFechaHasta').className = "inputHabilitado";
byId('lstEstatusPedido').className = "inputHabilitado";
byId('txtCriterio').className = "inputHabilitado";

window.onload = function(){
	jQuery(function($){
		$("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesde",
		dateFormat:"<?php echo spanDatePick; ?>", 
		cellColorScheme:"ocean_blue"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"<?php echo spanDatePick; ?>", 
		cellColorScheme:"ocean_blue"
	});
};

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaPedido(0, 'id_pedido_financiamiento', 'DESC', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|||'+byId('lstEstatusPedido').value);


function openImg(idObj) {
	var oldMaskZ = null;
	var $oldMask = $(null);
	
	$(".modalImg").each(function() {
		$(idObj).overlay({
			//effect: 'apple', 
			oneInstance: false, 
			zIndex: 10100, 
			
			onLoad: function() {
				if ($.mask.isLoaded()) {
					oldMaskZ = $.mask.getConf().zIndex; // this is a second overlay, get old settings
					$oldMask = $.mask.getExposed();
					$.mask.getConf().closeSpeed = 0;
					$.mask.close();
					this.getOverlay().expose({
						color: '#000000', 
						zIndex: 10090, 
						closeOnClick: false, 
						closeOnEsc: false, 
						loadSpeed: 0, 
						closeSpeed: 0
					});
				} else { // ABRE LA PRIMERA VENTANA
					this.getOverlay().expose({
						color: '#000000', 
						zIndex: 10090, 
						closeOnClick: false, 
						closeOnEsc: false
					});
				} // Other onLoad functions
			}, 
			onClose: function() {
				$.mask.close();
				if ($oldMask != null) { // re-expose previous overlay if there was one
					$oldMask.expose({
						color: '#000000', 
						zIndex: oldMaskZ, 
						closeOnClick: false, 
						closeOnEsc: false, 
						loadSpeed: 0
					});
					
					$(".apple_overlay").css("zIndex", oldMaskZ + 2); // Assumes the other overlay has apple_overlay class
				}
			}
		}).load();
	});
}

//perform JavaScript after the document is scriptable.
$(function() {
	$("ul.tabs").tabs("> .pane");
});


var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

</script>