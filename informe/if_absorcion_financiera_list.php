<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("if_absorcion_financiera_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_if_absorcion_financiera_list.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Informe Gerencial - Reporte Post-Venta</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleInforme.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragInforme.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
	<script src="../js/highcharts/js/highcharts.js"></script>
	<script src="../js/highcharts/js/modules/exporting.js"></script>
    
    <script src="../js/login-modernizr/modernizr.custom.63321.js"></script>
    <link rel="stylesheet" type="text/css" href="../js/login-modernizr/font-awesome.css" />
    
   	<link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
    <script src="anajax.js"></script>
    <script src="informe.inc.js"></script>
    <script>

	  //busqueda AJAX Cliente
	    function buscarConcepto(campo){
	        var _obj = byId(campo);
			
	        _obj.readOnly = false;
	        if (_obj.value == "") {
	            var lista = byId("listaConceptos");
	            lista.style.visibility = "hidden";
	            _obj.focus();
	            return;
	        }
	        var a = new Ajax();

	        a.load = function(texto){
	            var lista = byId("listaConceptos");
	            lista.style.visibility = "visible";
	            $('#listaConceptos').show();
	            var obj = byId("textConcepto");
				var leftGet = 0;
				
	            if(536 < getOffsetLeft(obj) && getOffsetLeft(obj) < 546){   	//Pantalla 80%
	            	leftGet = getOffsetLeft(obj) - 388;
	            }
	            if(436 < getOffsetLeft(obj) && getOffsetLeft(obj) < 456){		//Pantalla 90%
	            	leftGet = getOffsetLeft(obj) - 296;
	            }
	            if(366 < getOffsetLeft(obj) && getOffsetLeft(obj) < 386){		//Pantalla 100%
	            	leftGet = getOffsetLeft(obj) - 224;
	            }
	            if(316 < getOffsetLeft(obj) && getOffsetLeft(obj) < 336){		//Pantalla 110%
	            	leftGet = getOffsetLeft(obj) - 170;
	            }
	            if(246 < getOffsetLeft(obj) && getOffsetLeft(obj) < 276){		//Pantalla 120%
	            	leftGet = getOffsetLeft(obj) - 116;
	            }
	            lista.style.left = leftGet + "px";
	            lista.style.top = (getOffsetTop(obj) - 22) + "px";
	            lista.style.margin = obj.offsetHeight + "px 0px 0px 0px";
	            lista.innerHTML = texto;

	        };
	        a.sendget("if_absorcion_financiera_ajax.php","ajax_textConcepto=" + _obj.value, false);
	    }

	    var lp = -1;
	    function ku_buscarConcepto(e, obj) {
	        var slista = byId("overConceptos");
	        if (e.keyCode == 40 || e.keyCode == 38) {
	            if (slista == null) {
	                return;
	            }
	            if (e.keyCode == 40) {
	                lp++;
	            } else {
	                lp--;
	            }
	            if (lp >= slista.childNodes.length) {
	                lp = 0;
	            } else if (lp <= -1){
	                lp = slista.childNodes.length-1;
	            }
	            for (i = 0; i < slista.childNodes.length; i++) {
	                if (lp != i) {
	                    var item = slista.childNodes.item(i);
	                    if (item.lastcolor != null)
	                        item.style.background = item.lastcolor;
	                }
	            }
	            var item = slista.childNodes.item(lp);
	            if (item != null)
	                item.style.background = "#FFCC66";
	        } else if (e.keyCode == 13 || (byId(obj).value.toString().length > 0 && byId(obj).readOnly == false)) {
	            if (lp != -1 && e.keyCode == 13) {
	                var item = slista.childNodes.item(lp);
	            } else {
	                buscarConcepto(obj);
	            }
	            lp = -1;
	        }
	    }

	    function cancelarCliente() {
	        var lista = byId("listaConceptos");
			
			lista.style.visibility = "hidden";
			$('#listaConceptos').hide();
			
			var obj1 = byId("inputConcepto");
			var obj2 = byId("textConcepto");
			if (obj1 != undefined) {
            	obj2.value = obj1.value;
			}
	    }
	    
	    function buscarTipoCuenta(campo){
	        var _obj = byId(campo);
			
	        _obj.readOnly = false;
	        if (_obj.value == "") {
	            var lista = byId("listaTipoCuenta");
	            lista.style.visibility = "hidden";
	            _obj.focus();
	            return;
	        }
	        var a = new Ajax();

	        a.load = function(texto){
	            var lista = byId("listaTipoCuenta");
	            lista.style.visibility = "visible";
	            $('#listaTipoCuenta').show();
	            var obj = byId("textAddTipoCuenta");
				var leftGet = 0;
				
	            if(636 < getOffsetLeft(obj) && getOffsetLeft(obj) < 646){   	//Pantalla 80%
	            	leftGet = getOffsetLeft(obj) - 478;
	            }
	            if(536 < getOffsetLeft(obj) && getOffsetLeft(obj) < 556){		//Pantalla 90%
	            	leftGet = getOffsetLeft(obj) - 380;
	            }
	            if(466 < getOffsetLeft(obj) && getOffsetLeft(obj) < 486){		//Pantalla 100%
	            	leftGet = getOffsetLeft(obj) - 308;
	            }
	            if(416 < getOffsetLeft(obj) && getOffsetLeft(obj) < 456){		//Pantalla 110%
	            	leftGet = getOffsetLeft(obj) - 258;
	            }
	            if(356 < getOffsetLeft(obj) && getOffsetLeft(obj) < 376){		//Pantalla 120%
	            	leftGet = getOffsetLeft(obj) - 202;
	            }
	            
	            lista.style.left = leftGet + "px";
	            lista.style.top = (getOffsetTop(obj) - 22) + "px";
	            lista.style.margin = obj.offsetHeight + "px 0px 0px 0px";
	            lista.innerHTML = texto;

	        };
	        a.sendget("if_absorcion_financiera_ajax.php","ajax_textTipoCuenta=" + _obj.value, false);
	    }

	    var lp = -1;
	    function ku_buscarTipoCuenta(e, obj) {
	        var slista = byId("overTipoCuenta");
	        if (e.keyCode == 40 || e.keyCode == 38) {
	            if (slista == null) {
	                return;
	            }
	            if (e.keyCode == 40) {
	                lp++;
	            } else {
	                lp--;
	            }
	            if (lp >= slista.childNodes.length) {
	                lp = 0;
	            } else if (lp <= -1){
	                lp = slista.childNodes.length-1;
	            }
	            for (i = 0; i < slista.childNodes.length; i++) {
	                if (lp != i) {
	                    var item = slista.childNodes.item(i);
	                    if (item.lastcolor != null)
	                        item.style.background = item.lastcolor;
	                }
	            }
	            var item = slista.childNodes.item(lp);
	            if (item != null)
	                item.style.background = "#FFCC66";
	        } else if (e.keyCode == 13 || (byId(obj).value.toString().length > 0 && byId(obj).readOnly == false)) {
	            if (lp != -1 && e.keyCode == 13) {
	                var item = slista.childNodes.item(lp);
	            } else {
	                buscarTipoCuenta(obj);
	            }
	            lp = -1;
	        }
	    }

	    function cancelarTipoCuenta() {
	        var lista = byId("listaTipoCuenta");
			
			lista.style.visibility = "hidden";
			$('#listaTipoCuenta').hide();
			
			var obj1 = byId("inputTipoCuenta");
			var obj2 = byId("textAddTipoCuenta");
			if (obj1 != undefined) {
            	obj2.value = obj1.value;
			}
	    }
	    
	    function abrirDivFlotante(idObj, forms, IdObjTitulo, valor, valor2, valor3){ 
	
			if (IdObjTitulo == "tdFlotanteTitulo") {
				document.forms[forms].reset();

				$('#ventas').hide();
				$('#postventas').hide();
				$('#consecionario').hide();
				$('#btnAtras').hide();
				$('#trCierreMensual').show();
				$('#btnSiguiente').val(2);
				$('#btnSiguiente').val(2);
				
				xajax_asignarEmpresaUsuario(<?php echo $_SESSION['idEmpresaUsuarioSysGts'] ?>, "Empresa", "ListaEmpresa", "", false);
				xajax_asignarEmpresa(<?php echo $_SESSION['idEmpresaUsuarioSysGts'] ?>, "Empresa", "ListaEmpresa", "", false);
				xajax_cargaLstMesAno(<?php echo $_SESSION['idEmpresaUsuarioSysGts'] ?>);
				xajax_listaCierreMensual(0, 'id_cierre_mensual', 'DESC', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>||<?php echo date("Y"); ?>');
				
				titulo = 'Cierre Mensual <?php echo date("m-Y");?>';
			} else if(IdObjTitulo == "tdFlotanteTitulo2") {
				document.forms[forms].reset();

				$('#imgCerrar').hide();
				$('#imgAdd').show();
				$('#btnAddCuenta').val('1');
				$('.textBtnCuenta').text('Agregar Cuenta');
				$('#agregarCuenta').hide();
				byId('tdFlotanteTitulo2').width = "1100px";
				
				xajax_listaCuenta(0,"id_absorcion","ASC","<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|||||");
				
				titulo = 'Catálogo de Cuentas';
			} else if(IdObjTitulo == "tdFlotanteTitulo3") {
				document.forms[forms].reset();

				$('#addConcepto').hide();
				$('#addCuenta').show();
				
				xajax_listaCuentaContabilidad(0,"codigo","ASC","<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>||||"+"<?php echo date("Y-m-d"); ?>"+"|"+"<?php echo date("Y-m-d"); ?>");

				titulo = 'Asociar Cuenta';
			} else if(IdObjTitulo == "tdFlotanteTitulo4") {
				document.forms[forms].reset();

			 	$('#listaConceptos').hide();
				byId('apConceptos').click();
				xajax_listaCuentaConcepto(0,"id_concepto","ASC","|||||1",10,"");
				
				titulo = 'Conceptos (Gastos Generales)';
			} else if(IdObjTitulo == "tdFlotanteTitulo5") {
				document.forms[forms].reset();
				
				xajax_listaCuentaPorConcepto(0,"id_concepto","ASC",valor,5,"");
				
				titulo = 'Cuentas por Concepto (Gastos Generales)';
			} else if(IdObjTitulo == "tdFlotanteTitulo6") {
				document.forms[forms].reset();
				xajax_listaAddCuentaContabilidad(0,"codigo","ASC","<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>||||"+"<?php echo date("Y-m-d"); ?>"+"|"+"<?php echo date("Y-m-d"); ?>"+"||"+valor);
				titulo = 'Asociar Cuenta';
			} else if(IdObjTitulo == "tdFlotanteTitulo7") {
				document.forms[forms].reset();

				$('#listaTipoCuenta').hide();
				byId('apTiposCuenta').click();
				xajax_listaTiposCuenta(0,"numero_identificador","ASC","<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>||");
				xajax_listaSelectTiposCuenta("1"); //Lista de tipos cuenta
				xajax_listaSelectTiposCuenta("2"); //Crear tipo de cuenta
				xajax_listaSelectTiposCuenta("3"); //Editar tipo de cuenta
				
				titulo = 'Tipos de Cuenta';
			}
	
			openImg(idObj);
			byId(IdObjTitulo).innerHTML = titulo;
		}

		function showViewConceptos(idConcepto, vista){
			if(vista == 'editar'){
				$('#frmConceptoCuenta').hide();
				$('#frmEditConcepto').show();
				$('#id_conceptoEdit').val(idConcepto);
				xajax_frmEditConcepto(idConcepto);
			} else{
				$('#frmEditConcepto').hide();
				$('#frmConceptoCuenta').show();
			}
		}
		
		function showViewTipos(numIdentificador, vista){
			if(vista == 'editar'){
				$('#principalTiposCuenta').hide();
				$('#editarTiposCuenta').show();
				$('#id_editTipoCuenta').val(numIdentificador);
				xajax_frmEditTipoCuenta(numIdentificador);
			} else{
				$('#editarTiposCuenta').hide();
				$('#principalTiposCuenta').show();
			}
		}
		function validarConcepto(){
			if (validarCampo('textConcepto','t','') == true
					&& validarCampo('tipoVentaConcep','t','lista') == true) {
						xajax_guardarConcepto(xajax.getFormValues('frmConceptoCuenta'));
			} else {
				validarCampo('textConcepto','t','');
				validarCampo('tipoVentaConcep','t','lista');
				alert("El campo señalado en rojo es requerido");
				return false;
			}
		}

		function validarEditConcepto(){
			if (validarCampo('editConceptoFrm','t','') == true
					&& validarCampo('editVentaConcep','t','lista') == true) {
						xajax_guardarConcepto(xajax.getFormValues('frmEditConcepto'), 2);
			} else {
				validarCampo('editConceptoFrm','t','');
				validarCampo('editVentaConcep','t','lista');
				alert("El campo señalado en rojo es requerido");
				return false;
			}
		}
		
		function validarCuenta(){
			if (validarCampo('txtCodigoNew','t','') == true
					&& validarCampo('txtDescripcionNew','t','') == true) {
						xajax_guardarCuenta(xajax.getFormValues('frmMantenimiento'));
			} else {
				validarCampo('txtCodigoNew','t','');
				validarCampo('txtDescripcionNew','t','');
				alert("Los campos señalados en rojo son requeridos");
				return false;
			}
		}
		
		function validarBtnCuenta(value){
			$('#agregarCuenta').slideToggle();
			
			if(value == 1) {
				$('#imgAdd').hide();
				$('#imgCerrar').show();
				$('#btnAddCuenta').val('2');
			} else{

				$('#txtCodigoNew').val('')
				$('#txtDescripcionNew').val('')
				
				$('.textBtnCuenta').text('Agregar Cuenta')
				$('#imgCerrar').hide();
				$('#imgAdd').show();
				$('#btnAddCuenta').val('1');
			}
		}

		function validarTipoCuenta(){
			if (validarCampo('textAddTipoCuenta','t','') == true
					&& validarCampo('lstTiposCuenta2','t','lista') == true) {
						xajax_guardarTipoCuenta(xajax.getFormValues('frmTiposCuenta'));
			} else {
				validarCampo('textAddTipoCuenta','t','');
				validarCampo('lstTiposCuenta2','t','lista');
				alert("Los campos señalados en rojo son requeridos");
				return false;
			}
		}
		
		function validarEditTipoCuenta(){
			if (validarCampo('textEditTipoCuenta','t','') == true
					&& validarCampo('lstTiposCuenta3','t','lista') == true) {
						xajax_guardarTipoCuenta(xajax.getFormValues('frmTiposCuenta'), 'editar');
			} else {
				validarCampo('textEditTipoCuenta','t','');
				validarCampo('lstTiposCuenta3','t','lista');
				alert("Los campos señalados en rojo son requeridos");
				return false;
			}
		}
		function validarCuentaNumeros (evento) {
			if (arguments.length > 1)
				color = arguments[1];
			
			if (evento.target)
				idObj = evento.target.id;
			else if (evento.srcElement)
				idObj = evento.srcElement.id;
			
			teclaCodigo = (document.all) ? evento.keyCode : evento.which;
			
			if ((teclaCodigo != 0)
					&& (teclaCodigo != 8)
					&& (teclaCodigo != 13)
					&& (teclaCodigo != 46)
					&& (teclaCodigo != 44)
					&& (teclaCodigo <= 47 || teclaCodigo >= 58)) {
				return false;
			}
		}
		function limpiarFrmMantenimiento(){
			document.forms['frmMantenimiento'].reset()
			byId('btnMantenimiento').click()
		}
		function limpiarfrmNuevoMante(){
			document.forms['frmNuevoMante'].reset()
			byId('btnBuscarCuenta').click()
		}
		function limpiarfrmConcepto(){
			document.forms['frmNuevoMante'].reset()
			byId('btnBuscarCuentaConcep').click()
		}
		function limpiarfrmConceptoCuenta(){
			document.forms['frmConceptoCuenta'].reset()
			byId('btnBusCuentaConcep').click()
		}
		function limpiarFrmTiposCuenta(){
			document.forms['frmTiposCuenta'].reset()
			byId('btnBuscarTiposCuenta').click()
		}
	</script>
    
    <style>
		body {
			background: #365A96 url(../img/login/blurred<?php echo rand(1, 13); ?>.jpg) no-repeat center top;
			background-attachment:fixed;
			-webkit-background-size: cover;
			-moz-background-size: cover;
			background-size: cover;
			padding:10px;
		}
	</style>
</head>

<body>
	<div>
		<div><?php include("banner_informe.php"); ?></div>
	    
	    <div id="divInfo" style="text-align:center">
	    	<table border="0" width="100%">
	        <tr>
	        	<td class="tituloPaginaInforme">Absorción Financiera</td>
	        </tr>
	        <tr>
	            <td>
	            <form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
	            	<table align="left" border="0" cellpadding="0" cellspacing="0">
		                <tr>
		                    <td>
		                    	<table cellpadding="0" cellspacing="0">
		                        <tr>
		                        	<td class="btnReportes" style="display:none;">
		                        		<button type="button" onclick="xajax_cargaLstDecimalPDF();">
		                        			<table align="center" cellpadding="0" cellspacing="0">
		                        				<tr><td>&nbsp;</td>
		                        				<td><img src="../img/iconos/page_white_acrobat.png"/></td>
		                        				<td>&nbsp;</td><td>PDF</td></tr>
		                        			</table>
		                        		</button>
		                        	</td>
			                        	<td class="btnReportes" style="display:none;">
			                        		<button type="button" onclick="xajax_imprimirReporteAFP(xajax.getFormValues('frmBuscar'), xajax.getFormValues('divListaTotalVentas'), xajax.getFormValues('divListaTotalCosto'), xajax.getFormValues('divListaUtilidad'), xajax.getFormValues('divListaIndicador'), xajax.getFormValues('divListaGastoGeneral'), false);">
			                        			<table align="center" cellpadding="0" cellspacing="0">
			                        				<tr><td>&nbsp;</td>
			                        				<td><img src="../img/iconos/page_excel.png"/></td>
		                        					<td>&nbsp;</td><td>EXCEL</td></tr>
		                        				</table>
		                        			</button>
	                        			</td>
		                        	<td>
		                        		<a class="modalImg" id="aMantenimiento" rel="#divFlotante2" onclick="abrirDivFlotante(this,'frmMantenimiento','tdFlotanteTitulo2', 0, 'tblMantenimiento');">
		                        			<button type="button" id='btnMantenimiento'>Mantenimiento</button>
		                        		</a>
		                        	</td>
		                       	</tr>
		                        <tr><td id="tdlstDecimalPDF"></td></tr>
		                        </table>
							</td>
						</tr>
					</table>
	                
	                <table align="right">
		                <tr align="left">
		                    <td align="right" class="tituloCampo" width="120">Empresa:</td>
		                    <td id="tdlstEmpresa"></td>
		                    <td align="right" class="tituloCampo" width="120">Mes - Año:</td>
		                    <td>
		                    	<input type="text" id="txtFecha" name="txtFecha" autocomplete="off" size="10" style="text-align:center"/>
			               	</td>
		                    <td>
		                        <button type="submit" id="btnBuscar" onclick="xajax_buscar(xajax.getFormValues('frmBuscar'));">Buscar</button>
								<button type="button" onclick="document.forms['frmBuscar'].reset();$('.btnReportes').hide();$('#tblInforme').hide();$('#tblMsj').show();">Limpiar</button>
								<input type="hidden" id="btnReset" onclick="$('.btnReportes').hide();$('#tblInforme').hide();$('#tblMsj').show();">
		                    </td>
		                </tr>
	                </table>
	            </form>
	            </td>
	        </tr>
	        <tr>
	        	<td>
	                <table id="tblMsj" cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
		                <tr>
		                    <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
		                    <td align="center">Ingrese Los Datos Para Realizar la Busqueda</td>
		                </tr>
	                </table>
	                
	                <table id="tblInforme" border="0" style="display:none" width="100%">
		                <tr width="50%">
		                	<td>
		                        <div id="divMsjCierre"></div>
		                        <table cellpadding="0" cellspacing="0" width="100%">
					                <tr>
					                	<td width="47%">
					                  		<form id="divListaTotalVentas" name="divListaTotalVentas" onsubmit="return false;" class="form-3"></form>
				                  		</td>
				                  		<td width="2%">&nbsp;</td>
					               		<td width="47%">
					                		<form id="divListaTotalCosto" name="divListaTotalCosto" onsubmit="return false;" class="form-3"></form>
				                		</td>
					                </tr>
				                </table>
		                        <form id="divListaUtilidad" name="divListaUtilidad" onsubmit="return false;" class="form-3"></form>
	                         	<form id="divListaGastoGeneral" name="divListaGastoGeneral" onsubmit="return false;" class="form-3"></form>
	                         	<form id="divListaIndicador" name="divListaIndicador" onsubmit="return false;" class="form-3"></form>
		                    </td>
		                </tr>
	                </table>
	            </td>
	        </tr>
	        </table>
		</div>
		
	    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
	</div>
</body>
</html>

<div id="divFlotante2" class="root2" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0; color: #fff;">
<div id="divFlotanteTitulo2" class="handle2"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante2" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
	<form id="frmMantenimiento" name="frmMantenimiento" style="margin:0;" onsubmit="return false;">
		<div class="pane" style="max-height:600px; overflow:auto; width:100%;">
	  	  <table border="0" width="98%">
		    <tr>
		    	<td>
	    			 <table border="0" width="100%">
	    			 	<tr>
	    			 		<td>
	    			 			<fieldset><legend style="color: #fff;" class="legend">Datos del Catálogo</legend>
		    			 			<table width="100%">
		    			 				<tr>
		    			 					<td align="left" width="34%">
		    			 						<table width="100%">
							            			<tr>
							            				<td align="left" width="4%">
							            					<a class="modalImg" id="aNuevoConcepto" rel="#divFlotante4" onclick="abrirDivFlotante(this,'frmConceptoCuenta','tdFlotanteTitulo4', 0, 'tblConceptoCuenta');">
								            					<button id="guardarConcepto" type="button">
								            						<table cellspacing="0" cellpadding="0" align="center">
								            							<tbody>
								            								<tr>
								            									<td>&nbsp;</td>
								            									<td id="imgAdd"><img src="../img/iconos/add.png"></td>
								            									<td>&nbsp;</td><td>Agregar Concepto</td>
							            									</tr>
						            									</tbody>
					            									</table>
				            									</button>
			            									</a>
							            				</td>
							            				<td align="left" width="3%">
							            					<a class="modalImg" id="aNuevoMante" rel="#divFlotante3" onclick="abrirDivFlotante(this,'frmNuevoMante','tdFlotanteTitulo3', 0, 'tblNuevoMante');">
								            					<button id="guardarCat" type="button">
								            						<table cellspacing="0" cellpadding="0" align="center">
								            							<tbody>
								            								<tr>
								            									<td>&nbsp;</td>
								            									<td id="imgAdd"><img src="../img/iconos/add.png"></td>
								            									<td>&nbsp;</td><td>Asociar Cuenta</td>
							            									</tr>
						            									</tbody>
					            									</table>
				            									</button>
			            									</a>
							            				</td>
							            				<td align="left" width="4%">
							            					<a class="modalImg" id="aTiposCuenta" rel="#divFlotante7" onclick="abrirDivFlotante(this,'frmTiposCuenta','tdFlotanteTitulo7', 0, 'tblTiposCuenta');">
								            					<button id="guardarTiposCuenta" type="button">
								            						<table cellspacing="0" cellpadding="0" align="center">
								            							<tbody>
								            								<tr>
								            									<td>&nbsp;</td>
								            									<td id="imgAdd"><img src="../img/iconos/add.png"></td>
								            									<td>&nbsp;</td><td>Tipos de Cuenta</td>
							            									</tr>
						            									</tbody>
					            									</table>
				            									</button>
			            									</a>
							            				</td>
							            			</tr>
							            		</table>
		    			 					</td>
		    			 					<td align="right" width="60%">
		    			 						<table>
					    			 			 	<tr><td height="12px"></td></tr>
										            <tr align="left">
										            	<td align="right" class="tituloCampo">Código:</td>
										                <td><input type="text" id="txtCodigo" name="txtCodigo" class="inputHabilitado" size="22" onkeyup="byId('btnBuscarCat').click();"/></td>
										           		<td align="right" class="tituloCampo">Concepto:</td>
										                <td><input type="text" id="txtConcepto" name="txtConcepto" class="inputHabilitado" size="22" onkeyup="byId('btnBuscarCat').click();"/></td>
													</tr>
													<tr><td height="5px"></td></tr>
										            <tr align="left">
														<td align="right" class="tituloCampo">Tipo de Gasto:</td>
											            <td>
															<select id='tipoVenta' name='tipoVenta' onChange="xajax_buscarCuenta(xajax.getFormValues('frmMantenimiento'), xajax.getFormValues('frmBuscar'), 1);" class="inputHabilitado"> 
			                                                    <option value=''>[ Seleccione ]</option>
			                                                    <option value='1'>Ventas</option>
			                                                    <option value='2'>Postventa</option>
			                                                    <option value='3'>Generales</option>
			                                                </select>
														</td>
										                <td colspan="2" align="right">
									                        <button type="submit" id="btnBuscarCat" onclick="xajax_buscarCuenta(xajax.getFormValues('frmMantenimiento'), xajax.getFormValues('frmBuscar'), 1);">Buscar</button>
															<button type="button" onclick="limpiarFrmMantenimiento();">Limpiar</button>
									                    </td>
										            </tr>
										         </table>
									    	</td>
										</tr>

							            <tr><td height="16px"></td></tr>
							            <tr>
							            	<td colspan="3" id="divListaCuentas"></td>
							            </tr>
						            </table>
					            </fieldset>
	    			 		</td>
	    			 	</tr>
					    <tr>
					    	<td align="right"><br>
					            <button type="button" id="btnCerrar" name="btnCerrar" class="close">Cerrar</button>
					        </td>
					    </tr>
				    </table>
			    </td>
		    </tr>
	    </table>
		</div>
	</form>
</div>

<div id="divFlotante3" class="root2" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0; color: #fff;">
<div id="divFlotanteTitulo3" class="handle2"><table><tr><td id="tdFlotanteTitulo3" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante2" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
	<form id="frmNuevoMante" name="frmNuevoMante" style="margin:0;" onsubmit="return false;">
		<div class="pane" style="max-height:600px; overflow:auto; width:820px;">
	  	  <table border="0" width="800px">
		    <tr>
		    	<td>
	    			 <table border="0" width="100%">
	    			 	<tr>
	    			 		<td id='addCuenta'>
	    			 			<fieldset><legend style="color: #fff;" class="legend">Agregar Cuenta</legend>
		    			 			<table width="100%">
		    			 				<tr>
		    			 					<td align="right" width="60%">
		    			 						<table>
					    			 			 	<tr><td height="12px"></td></tr>
										            <tr align="left">
										            	<td align="right" class="tituloCampo">Código:</td>
										                <td colspan="2"><input type="text" id="txtCodigo" name="txtCodigo" class="inputHabilitado" size="40"/></td>
										            </tr>
										            <tr align="left">
										            	<td align="right" class="tituloCampo">Descripción:</td>
										                <td><input type="text" id="txtDescripcion" name="txtDescripcion" class="inputHabilitado" size="40"/></td>
										                <td align="right">
									                        <button type="submit" id="btnBuscarCuenta" onclick="xajax_buscarCuenta(xajax.getFormValues('frmNuevoMante'), xajax.getFormValues('frmBuscar'), 2);">Buscar</button>
															<button type="button" onclick="limpiarfrmNuevoMante();">Limpiar</button>
									                    </td>
										            </tr>
										         </table>
									    	</td>
										</tr>

							            <tr><td height="16px"></td></tr>
							            <tr>
							            	<td colspan="3" id="divListaCuentasContables"></td>
							            </tr>
						            </table>
					            </fieldset>
	    			 		</td>
	    			 		<td id='addConcepto' style='display:none;'>
	    			 			<fieldset><legend style="color: #fff;" class="legend">Seleccionar Concepto</legend>
	    			 				<table width="100%">
    			 						<tr><td height="12px"></td></tr>
    			 						<tr align="left">
								        	<td align="right" class="tituloCampo">Codigo:</td>
								            <td><input type="text" id="txtBusCodigo" name="txtBusCodigo" readonly = "readonly" size="40"/></td>
								        </tr>
								        <tr align="left">
								        	<td align="right" class="tituloCampo">Concepto:</td>
								            <td><input type="text" id="txtBusConcepto" name="txtBusConcepto" class="inputHabilitado" size="40"/></td>
								        </tr>
								        <tr align="left">
								        	<td align="right" class="tituloCampo">Tipo de Gasto:</td>
								            <td>
												<select id='tipoVenta' name='tipoVenta' class="inputHabilitado"> 
                                                    <option value=''>[ Seleccione ]</option>
                                                    <option value='1'>Ventas</option>
                                                    <option value='2'>Postventa</option>
                                                    <option value='3'>Generales</option>
                                                </select>
											</td>
								            <td align="right">
							                	<button type="submit" name='btnBuscarCuentaConcep' id="btnBuscarCuentaConcep" onclick="xajax_buscarCuenta(xajax.getFormValues('frmNuevoMante'), xajax.getFormValues('frmBuscar'), 3);">Buscar</button>
												<button type="button" onclick="limpiarfrmConcepto();">Limpiar</button>
						                    </td>
							            </tr>
							            <tr><td height="20px"></td></tr>
		    			 			 	<tr>
							            	<td colspan="3" id="divListaCuentasConceptos"></td>
							            </tr>
						            </table>
	    			 			</fieldset>
	    			 		</td>
	    			 	</tr>
					    <tr>
					    	<td align="right"><br>
					            <button type="button" id="btnCerrarCuentaConta" name="btnCerrarCuentaConta" class="close">Cerrar</button>
					        </td>
					    </tr>
				    </table>
			    </td>
		    </tr>
	    </table>
		</div>
	</form>
</div>

<div id="divFlotante4" class="root2" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0; color: #fff;">
<div id="divFlotanteTitulo4" class="handle2"><table><tr><td id="tdFlotanteTitulo4" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante2" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
									                    
	<form id="frmConceptoCuenta" name="frmConceptoCuenta" style="margin:0;" onsubmit="return false;">
		<div class="pane" style="max-height:600px; overflow:auto; width:820px;">
	  	  <table border="0" width="800px">
		    <tr>
		    	<td>
		    		<div class="wrap">
	                    <!-- the tabs -->
	                    <ul class="tabs">
	                        <li><a id="apConceptos" href="#">Lista de Conceptos</a></li>
	                        <li><a href="#">Crear Conceptos</a></li>
	                    </ul>
	  
	                   <!-- tab "panes" LISTA DE CONCEPTOS-->
	                    <div class="pane">
	                       <table border="0" width="100%">
			    			 	<tr>
			    			 		<td>
			    			 			<fieldset><legend style="color: #fff;" class="legend">Conceptos</legend>
			    			 				<table width="100%">
					    			 			<tr><td height="12px"></td></tr>
										        <tr align="left">
										        	<td align="right" class="tituloCampo">Concepto:</td>
										            <td><input type="text" id="txtBusConcepto2" name="txtBusConcepto2" class="inputHabilitado" size="40"/></td>
										        </tr>
										        <tr align="left">
										        	<td align="right" class="tituloCampo">Tipo de Gasto:</td>
										            <td>
														<select id='tipoVenta2' name='tipoVenta2' class="inputHabilitado"> 
		                                                    <option value=''>[ Seleccione ]</option>
		                                                    <option value='1'>Ventas</option>
		                                                    <option value='2'>Postventa</option>
		                                                    <option value='3'>Generales</option>
		                                                </select>
													</td>
										            <td align="right">
									                	<button type="submit" name='btnBusCuentaConcep' id="btnBusCuentaConcep" onclick="xajax_buscarCuentaConcepto(xajax.getFormValues('frmConceptoCuenta'), xajax.getFormValues('frmBuscar'), 3);">Buscar</button>
														<button type="button" onclick="limpiarfrmConceptoCuenta();">Limpiar</button>
								                    </td>
									            </tr>
									            <tr><td height="20px"></td></tr>
									            <tr>
									            	<td colspan="3">
									            		<br>
				    			 						<div id="divListaCuentasConceptos2"></div>
				    			 					</td>
									            </tr>
			    			 				</table>
							            </fieldset>
			    			 		</td>
			    			 	</tr>
							    <tr>
							    	<td align="right"><br>
							            <button type="button" id="btnCerrarConcep" name="btnCerrarConcep" class="close">Cerrar</button>
							        </td>
							    </tr>
						    </table>
	                    </div>
	  
	                   <!-- tab "panes" CREAR CONCEPTOS-->
	                    <div class="pane">
	                        <table border="0" width="100%">
			    			 	<tr>
			    			 		<td>
			    			 			<fieldset><legend style="color: #fff;" class="legend">Agregar Concepto</legend>
				    			 			<table width="100%">
				    			 				<tr>
				    			 					<td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Concepto:</td>
					                            	<td align="left" id="capaDatosConcepto" width="60%">
					                            		<input type="text" autocomplete="off" id="textConcepto"  name="textConcepto" class="inputCompletoHabilitado" onkeyup="ku_buscarConcepto(event,this.id);" size="70"/>
				                            		</td>
												</tr>
												<tr>
													<td>
														<input type="hidden" id="inputConcepto" name="inputConcepto"/>
						                                <div id="listaConceptos" class="ajaxlist"></div>
													</td>
												</tr>
												<tr>
													<td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Tipo de Gasto:</td>
													<td align="left" width="60%">
														<select id='tipoVentaConcep' name='tipoVentaConcep' class="inputHabilitado"> 
		                                                    <option value=''>[ Seleccione ]</option>
		                                                    <option value='1'>Ventas</option>
		                                                    <option value='2'>Postventa</option>
		                                                    <option value='3'>Generales</option>
		                                                </select>
													</td>
												</tr>
								            </table>
							            </fieldset>
			    			 		</td>
			    			 	</tr>
							    <tr>
							    	<td align="right"><br>
							            <button type="button" id="btnGuardarConcep" name="btnGuardarConcep" onClick='validarConcepto();'>Guardar</button>
							            <button type="button" id="btnCerrarConcep" name="btnCerrarConcep" class="close">Cerrar</button>
							        </td>
							    </tr>
						    </table>
	                    </div>
                    </div>
			    </td>
		    </tr>
	    </table>
		</div>
	</form>
	
	<form id="frmEditConcepto" name="frmEditConcepto" style="margin:0; display: none;" onsubmit="return false;">
		<table border="0" width="100%">
    		<tr>
    			<td>
    				<fieldset><legend style="color: #fff;" class="legend">Editar Concepto</legend>
		    			<table width="100%">
		    				<tr>
		    			 		<td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Concepto:</td>
			                    <td align="left" width="60%"><input type="text" id="editConceptoFrm"  name="editConceptoFrm" class="inputHabilitado" size="90"/></td>
							</tr>
							<tr>
								<td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Tipo de Gasto:</td>
								<td align="left" width="60%">
									<select id='editVentaConcep' name='editVentaConcep' class="inputHabilitado"> 
                                    	<option value=''>[ Seleccione ]</option>
                                        <option value='1'>Ventas</option>
                                       	<option value='2'>Post Venta</option>
                                        <option value='3'>Generales</option>
                                     </select>
								</td>
							</tr>
						</table>
					</fieldset>
	    		</td>
	    	</tr>
			<tr>
				<td align="right"><br>
					<input type="hidden" id="id_conceptoEdit" name="id_conceptoEdit"></input>
					<button type="button" id="btnGuardarConcep" name="btnGuardarConcep" onClick="validarEditConcepto();showViewConceptos('', 'lista');">Guardar</button>
		            <button type="button" id="btnCancelar" name="btnCancelar" onClick="showViewConceptos('', 'lista');">Cancelar</button>
		        </td>
		    </tr>
	    </table>
	</form>
	
</div>

<div id="divFlotante5" class="root2" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0; color: #fff;">
<div id="divFlotanteTitulo5" class="handle2"><table><tr><td id="tdFlotanteTitulo5" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante5" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
	<form id="frmListaCuenta" name="frmListaCuenta" style="margin:0;" onsubmit="return false;">
		<div class="pane" style="max-height:600px; overflow:auto; width:820px;">
	  	  <table border="0" width="800px">
		    <tr>
		    	<td id="divListaCuentaPorConceptos"></td>
		    </tr>
	    </table>
		</div>
	</form>
</div>

<div id="divFlotante6" class="root2" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0; color: #fff;">
<div id="divFlotanteTitulo6" class="handle2"><table><tr><td id="tdFlotanteTitulo6" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante6" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
	<form id="frmAddCuenta" name="frmAddCuenta" style="margin:0;" onsubmit="return false;">
		<div class="pane" style="max-height:600px; overflow:auto; width:820px;">
	  	  <table border="0" width="800px">
		    <tr>
		    	<td>
	    			 <table border="0" width="100%">
	    			 	<tr>
	    			 		<td >
	    			 			<fieldset><legend style="color: #fff;" class="legend">Agregar Cuentas</legend>
		    			 			<table width="100%">
		    			 				<tr>
		    			 					<td align="right" width="60%">
		    			 						<table>
					    			 			 	<tr><td height="12px"></td></tr>
										            <tr align="left">
										            	<td align="right" class="tituloCampo">Código:</td>
										                <td colspan="2"><input type="text" id="txtCodigo" name="txtCodigo" class="inputHabilitado" size="40"/></td>
										            </tr>
										            <tr align="left">
										            	<td align="right" class="tituloCampo">Descripción:</td>
										                <td><input type="text" id="txtDescripcion" name="txtDescripcion" class="inputHabilitado" size="40"/></td>
										                <td align="right">
									                        <button type="submit" id="btnBuscarCuenta" onclick="xajax_buscarCuenta(xajax.getFormValues('frmNuevoMante'), xajax.getFormValues('frmBuscar'), 2);">Buscar</button>
															<button type="button" onclick="limpiarfrmNuevoMante();">Limpiar</button>
									                    </td>
										            </tr>
										         </table>
									    	</td>
										</tr>

							            <tr><td height="16px"></td></tr>
							            <tr>
							            	<td colspan="3" id="divListaAddCuentasContables"></td>
							            </tr>
						            </table>
					            </fieldset>
	    			 		</td>
	    			 	</tr>
					    <tr>
					    	<td align="right"><br>
					            <button type="button" id="btnCerrarCuentaConta" name="btnCerrarCuentaConta" class="close">Cerrar</button>
					        </td>
					    </tr>
				    </table>
			    </td>
		    </tr>
	    </table>
		</div>
	</form>
</div>

<div id="divFlotante7" class="root2" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0; color: #fff;">
<div id="divFlotanteTitulo7" class="handle2"><table><tr><td id="tdFlotanteTitulo7" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante7" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
	<form id="frmTiposCuenta" name="frmTiposCuenta" style="margin:0;" onsubmit="return false;">
		<div class="pane" style="max-height:600px; overflow:auto; width:650px;">
	  		 <table border="0" width="98%">
			    <tr>
			    	<td>
		    			 <table border="0" width="100%">
		    			 	<tr>
		    			 		<td>
		    			 			<table width="100%">
		    			 				<tr>
		    			 					<td id="principalTiposCuenta">
		    			 						<div class="wrap">
								                    <!-- the tabs -->
								                    <ul class="tabs">
								                        <li><a id="apTiposCuenta" href="#">Tipos de Cuenta</a></li>
								                        <li><a href="#">Asociar Cuenta</a></li>
								                    </ul>
								  
								                    <!-- tab "panes" LISTA DE TIPOS DE CUENTA-->
								                    <div class="pane">
				    			 						<table width="100%">
															<tr><td height="5px"></td></tr>
												            <tr align="right">
												            	<td align="left" width="30%"></td>
												            	<td align="right" width="70%">
												            		<table width="100%">
												            			<tr>
												            				<td align="right" class="tituloCampo">Tipo de Cuenta:</td>
																            <td id="listaSelectTiposCuenta1"></td>
															                <td colspan="2" align="right">
														                        <button type="submit" name="btnBuscarTiposCuenta" id="btnBuscarTiposCuenta" onclick="xajax_buscarTiposCuenta(xajax.getFormValues('frmTiposCuenta'));">Buscar</button>
																				<button type="button" onclick="limpiarFrmTiposCuenta();">Limpiar</button>
														                    </td>
												            			</tr>
												            		</table>
												            	</td>
												            </tr>
												            <tr><td height="16px"></td></tr>
												            <tr>
												            	<td colspan="3" id="divTiposCuenta"></td>
												            </tr>
												             <tr>
														    	<td colspan="3" align="right">
														            <button type="button" id="btnCerrar" name="btnCerrar" class="close">Cerrar</button>
														        </td>
														    </tr>
												         </table>
								                    </div>
								                    <!-- tab "panes" AGREGAR TIPOS DE CUENTA-->
								                    <div class="pane">
				    			 						<table border="0" width="100%">
										    			 	<tr>
										    			 		<td>
										    			 			<fieldset><legend style="color: #fff;" class="legend">Agregar Tipo de Cuenta</legend>
											    			 			<table width="100%">
											    			 				<tr><td height="6px"></td></tr>
											    			 				<tr>
											    			 					<td align="right" class="tituloCampo" width="18%"><span class="textoRojoNegrita">*</span> Nombre :</td>
												                            	<td align="left" id="capaDatosTipoCuenta" width="60%">
												                            		<input type="text" autocomplete="off" id="textAddTipoCuenta"  name="textAddTipoCuenta" class="inputCompletoHabilitado" onkeyup="ku_buscarTipoCuenta(event,this.id);" size="70"/>
											                            		</td>
																			</tr>
																			<tr>
																				<td>
																					<input type="hidden" id="inputTipoCuenta" name="inputTipoCuenta"/>
													                                <div id="listaTipoCuenta" class="ajaxlistTipoCuenta"></div>
																				</td>
																			</tr>
																			<tr>
																				<td align="right" class="tituloCampo" width="18%"><span class="textoRojoNegrita">*</span>Tipo de Cuenta:</td>
																				<td align="left" width="60%" id="listaSelectTiposCuenta2"></td>
																			</tr>
															            </table>
														            </fieldset>
										    			 		</td>
										    			 	</tr>
														    <tr>
														    	<td align="right"><br>
														            <button type="button" id="btnGuardarTipoCuenta" name="btnGuardarTipoCuenta" onClick='validarTipoCuenta();'>Guardar</button>
														            <button type="button" id="btnCerrarTipoCuenta" name="btnCerrarTipoCuenta" class="close">Cerrar</button>
														        </td>
														    </tr>
													    </table>
								                    </div>
								            	</div>
		    			 					</td>
		    			 					<td id="editarTiposCuenta" style="display: none;">
	    			 							<table border="0" width="100%">
								    			 	<tr>
								    			 		<td>
								    			 			<fieldset><legend style="color: #fff;" class="legend">Editar Tipo de Cuenta</legend>
									    			 			<table width="100%">
									    			 				<tr><td height="6px"></td></tr>
									    			 				<tr>
									    			 					<td align="right" class="tituloCampo" width="18%"><span class="textoRojoNegrita">*</span>Nombre :</td>
										                            	<td align="left" width="60%"><input type="text" id="textEditTipoCuenta"  name="textEditTipoCuenta" class="inputHabilitado" size="60"/></td>
																	</tr>
																	<tr>
																		<td align="right" class="tituloCampo" width="18%"><span class="textoRojoNegrita">*</span>Tipo de Cuenta:</td>
																		<td align="left" width="60%" id="listaSelectTiposCuenta3"></td>
																	</tr>
													            </table>
												            </fieldset>
								    			 		</td>
								    			 	</tr>
												    <tr>
												    	<td align="right"><br>
												    		<input type="hidden" id="id_editTipoCuenta" name="id_editTipoCuenta"></input>
												            <button type="button" id="btnEditarTipoCuenta" name="btnEditarTipoCuenta" onClick='validarEditTipoCuenta();'>Guardar</button>
												            <button type="button" id="btnCancelarEditTipoCuenta" name="btnCancelarEditTipoCuenta" onClick="showViewTipos('', 'principal');" >Cancelar</button>
												        </td>
												    </tr>
											    </table>
		    			 					</td>
										</tr>
						            </table>
		    			 		</td>
		    			 	</tr>
					    </table>
				    </td>
			    </tr>
		    </table>
		</div>
	</form>
</div>

<script>
byId('txtFecha').className = "inputHabilitado";
byId('txtFecha').value = "<?php echo date("m-Y"); ?>";

window.onload = function(){
	jQuery(function($){
	   $("#txtFecha").maskInput("<?php echo "99-9999"; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFecha",
		dateFormat:"<?php echo "%m-%Y"; ?>"
	});
};

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

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstMes();
xajax_cargaLstAno('<?php echo date("Y"); ?>');

//perform JavaScript after the document is scriptable.
$(function() {
	$("ul.tabs").tabs("> .pane");
});

var thehandle2 = document.getElementById("divFlotanteTitulo2");
var theroot2   = document.getElementById("divFlotante2");
Drag.init(thehandle2, theroot2);

var thehandle2 = document.getElementById("divFlotanteTitulo3");
var theroot2   = document.getElementById("divFlotante3");
Drag.init(thehandle2, theroot2);

var thehandle2 = document.getElementById("divFlotanteTitulo4");
var theroot2   = document.getElementById("divFlotante4");
Drag.init(thehandle2, theroot2);

var thehandle2 = document.getElementById("divFlotanteTitulo5");
var theroot2   = document.getElementById("divFlotante5");
Drag.init(thehandle2, theroot2);

var thehandle2 = document.getElementById("divFlotanteTitulo6");
var theroot2   = document.getElementById("divFlotante6");
Drag.init(thehandle2, theroot2);

var thehandle2 = document.getElementById("divFlotanteTitulo7");
var theroot2   = document.getElementById("divFlotante7");
Drag.init(thehandle2, theroot2);

</script>
