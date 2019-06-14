<?php
require_once("../connections/conex.php");

session_start();
define('PAGE_PRIV','sa_historico_recepcion');

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso(PAGE_PRIV))) {
	echo "
	<script>
		alert('Acceso Denegado');
		window.location.href = 'index.php';
	</script>";
}
/* Fin Validación del Módulo */

require('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_iv_general.php");
include("controladores/ac_sa_historico_recepcion.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	
        <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Historico de Recepcion</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
                
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    <link rel="stylesheet" type="text/css" href="css/sa_general.css" />
    
    <link rel="stylesheet" type="text/css" href="../js/domDragServicios.css">
    
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
        
    <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/> 
    <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
    
    <script type="text/javascript" language="javascript" src="../control/lib/mouse_touch.inc.js"></script>
    <script type="text/javascript" language="javascript" src="../control/lib/main.inc.js"></script>
    
    <style type="text/css">
		.tablaFallas{
			width:100%;
		}
		
		.tablaFallas input{
			width:95%
		}
		
		.tablaFallas td{
			white-space:nowrap;
			width:100%;
		}
		
		div.scrollable {
			width: 100%;
			max-height: 500px;
			margin: 0;
			padding: 0;
			overflow: auto;
		}
		
		.fotosIncidencias img{
			padding:15px;
		  
		}
		
		.fotosIncidencias img:hover{
			 -webkit-box-shadow: 0px 1px 5px 0px rgba(50, 50, 50, 0.75);
			 -moz-box-shadow:    0px 1px 5px 0px rgba(50, 50, 50, 0.75);
			 box-shadow:         0px 1px 5px 0px rgba(50, 50, 50, 0.75);
		}
		
		.gris{
			-webkit-filter: grayscale(100%);
			   -moz-filter: grayscale(100%);
				 -o-filter: grayscale(100%);
				-ms-filter: grayscale(100%);
					filter: grayscale(100%);
					
			-webkit-filter: contrast(60%);
			   -moz-filter: contrast(60%);
				 -o-filter: contrast(60%);
				-ms-filter: contrast(60%);
					filter: contrast(60%);
		}
		
		.nroFotos{
			margin-top:-9px; 
			margin-left:-13px;
			position:absolute;
			color:#F00;
			font-weight:bold;
		}		
	
	</style>
    
    <script>
	
	//FUNCIONES JAVASCRIPT
	
	
	function soloNumeros(e) {//OJO teclado numerico no funciona sino usas "onkeypress"
		tecla = (document.all) ? e.keyCode : e.which;
		if (tecla == 8 || tecla == 37 || tecla == 39){//8 = delete, 37 = flecha izq, 39 = flecha dere
			return true;
		}
		patron = /[0-9]/;
		te = String.fromCharCode(tecla);		
		return patron.test(te);
	}
	
	function limpiarKM(){
		document.forms['frmKilometraje'].reset();
		$("#idValeRecepcionKm").val("");
	}
	
	function limpaiarFallas(){
		document.forms['frmFallas'].reset();
		$("#idValeRecepcionFalla").val("");
		$("#idFallasEliminar").val("");
	}
	
	function quitarFalla(idFalla, objBoton){
		if(idFalla != "" && idFalla != "0"){
			idFallasEliminar = $('#idFallasEliminar').val();
			$('#idFallasEliminar').val(idFallasEliminar + '|' + idFalla);
		}
		
		objTr = $(objBoton).parent().parent();//actual

		$(objTr).prev().remove();//arriba
		$(objTr).next().next().remove();//linea separadora
		$(objTr).next().remove();//abajo
		$(objTr).remove();//actual
		
	}
	
	function agregarFalla(){
		
		boton = "<button onclick='quitarFalla(0, this);' type='button'><img border='0' src='../img/iconos/minus.png' width='14' alt='quitar'></button>";
		checkbox = "<input type='checkbox' style='display:none' checked='checked' name='idFalla[]' value='' />";
		
		html = "";		
		html += "<tr><td>" + checkbox + "<b>F: </b><input type='text' name='descripcionFalla[]' value = '' /></td><td></td></tr>";
		html += "<tr><td><b>D: </b><input type='text' name='diagnosticoFalla[]' value = '' /></td><td>" + boton + "</td></tr>";
		html += "<tr><td><b>R: </b><input type='text' name='respuestaFalla[]' value = '' /></td><td></td></tr>";
		html += "<tr><td colspan='3'><br></td></tr>";
		
		$('.tablaFallas').append(html);
	}
	
	function activaCheckBox(objCheckbox){
		if(objCheckbox.checked == true){
			$(objCheckbox).parent().parent().css('background-color', '#b8dcff');
		}else{
			$(objCheckbox).parent().parent().css('background-color', '');
		}		
	}
	
	function activaRadio(objRadio){
		if(objRadio.checked == true){
			var tr = $(objRadio).parent().parent();
			if(tr.find(":checkbox")[0].checked == false){
				tr.find(":checkbox")[0].click();
			}			
		}
	}
	
	function activaCantidad(objCantidad){
		var tr = $(objCantidad).parent().parent();
		if(tr.find(":checkbox")[0].checked == false){
			tr.find(":checkbox")[0].click();
		}
	}
	
	</script>
</head>

<body>
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include("banner_servicios.php"); ?>
    </div>
	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaServicios">Histórico de Recepción</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
		</tr>
        <tr>
        	<td>
            	<table align="left">
                <tr>
                    <td>
                    	<button type="button" onclick="xajax_exportarExcel(xajax.getFormValues('frmBuscar'));" class="noprint" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>Exportar</td></tr></table></button>
					</td>
                </tr>
                </table>
            	
			<form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
            	<table border="0" align="right">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td colspan="3" id="tdlstEmpresa">
                    </td>
                    
                    <td align="right" class="tituloCampo" width="100">Asesor:</td>
                    <td id="tdlstEmpleado" colspan="2">
                    </td>
				</tr>
                <tr align="left">
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
                    
                    <td align="right" class="tituloCampo" width="100">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="$('#btnBuscar').click();"></td>
                    <td>
                    	<button id="btnBuscar" onclick="xajax_buscar(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button onclick="document.forms['frmBuscar'].reset(); $('#btnBuscar').click();">Limpiar</button>
                    </td>
				</tr>
                </table>
        	</form>
            </td>
        </tr>
        <tr>
        	<td>
            <form id="frmLista" name="frmLista" style="margin:0" onsubmit="return false;">
				<div id="divLista"></div>
            </form>
            </td>
        </tr>
        </table>
        
            <div align="center"><br>
                <table width="100%" cellspacing="0" cellpadding="0" class="divMsjInfo2">
                    <tbody><tr>
                            <td width="25"><img width="25" src="../img/iconos/ico_info.gif"></td>
                            <td align="center">
                                <table>
                                    <tbody>
                                        <tr>
                                            <td><img src="../img/iconos/cc.png"></td>
                                            <td>Cambiar Tipo de vale</td>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/edit.png"></td>
                                            <td>Editar Falla y Diagnostico</td>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/time_add.png"></td>
                                            <td>Editar <?php echo $spanKilometraje;?></td>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/view.png"></td>
                                            <td>Ver Vale</td>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/diagnostico.png"></td>
                                            <td>Ver Diagnostico</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
                 <table width="100%" cellspacing="0" cellpadding="0" class="divMsjInfo2">
                    <tbody><tr>
                            <td width="25"><img width="25" src="../img/iconos/ico_info.gif"></td>
                            <td align="center">
                                <table>
                                    <tbody>
                                        <tr>
                                            <td><img src="../img/iconos/print.png"></td>
                                            <td>Imprimir Vale</td>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/photo.png"></td>
                                            <td>Ver Fotos de Incidencias</td>
                                            
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/edit_privilegios.png"></td>
                                            <td>Editar Inventario</td>                                            
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/unidadesAsignadas.png"></td>
                                            <td>Editar Incidencias</td>                                            
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/key.png"></td>
                                            <td>Editar Nro Llaves</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="noprint">
	<?php include("pie_pagina.php"); ?>
    </div>
</div>
</body>
</html>

<div style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;" class="root" id="divFlotante">
    <div class="handle" id="divFlotanteTitulo"><table><tbody><tr><td width="100%" id="tdFlotanteTitulo">Cambio de Tipo de Vale</td></tr></tbody></table></div>
    <form style="margin:0" name="frmTipoVale" id="frmTipoVale" onsubmit="return false;">        
        <table width="300" border="0">
            <tbody>
                <tr>
                    <td>
                        <input type="hidden" id="idValeRecepcion" name="idValeRecepcion" value="" />
                    </td>
                </tr>
                <tr>
                    <td width="33%" align="right" class="tituloCampo">Nro. Vale:</td>
                    <td width="67%">
                        <input type="text" name="numeroVale" maxlength="30" id="numeroVale"/>
                    </td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo de Vale:</td>
                    <td id="tdTipoVale">
                    </td>
                </tr>
                <tr>
                    <td align="right" colspan="2">
                        <hr/>
                        <button type="button" class="puntero" onclick="xajax_guardarTipoVale(xajax.getFormValues('frmTipoVale'));" name="btnGuardar" id="btnGuardar">Guardar</button>
                        <button type="button" class="puntero" onclick="$('#divFlotante').hide();">Cancelar</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</div>


<div style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;" class="root" id="divFlotante2">
    <div class="handle" id="divFlotanteTitulo2"><table><tbody><tr><td width="100%" id="tdFlotanteTitulo2">Cambio de <?php echo $spanKilometraje; ?></td></tr></tbody></table></div>
    <form style="margin:0" name="frmKilometraje" id="frmKilometraje" onsubmit="return false;">        
        <table width="340" border="0">
            <tbody>
                <tr>
                    <td>
                        <input type="hidden" id="idValeRecepcionKm" name="idValeRecepcionKm" value="" />
                    </td>
                </tr>
                <tr>
                    <td width="70%" align="right" class="tituloCampo">Nro. Vale:</td>
                    <td width="67%">
                        <input type="text" readonly="readonly" name="numeroValeKm" maxlength="30" id="numeroValeKm"/>
                    </td>
                </tr>
                <tr>
                    <td width="33%" align="right" class="tituloCampo"><?php echo $spanKilometraje; ?> Actual Vale:</td>
                    <td width="67%">
                        <input type="text" readonly="readonly" name="kmActualValeRecepcion" maxlength="30" id="kmActualValeRecepcion"/>
                    </td>
                </tr>
                <tr>
                    <td width="33%" align="right" class="tituloCampo"><?php echo $spanKilometraje; ?> Actual Vehículo:</td>
                    <td width="67%">
                        <input type="text" readonly="readonly" name="kmActualVehiculo" maxlength="30" id="kmActualVehiculo"/>
                    </td>
                </tr>
                <tr>
                <td colspan="2"><br></td>
                </tr>
                <tr>
                    <td width="33%" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanKilometraje; ?> Nuevo (Vale):</td>
                    <td width="67%">
                        <input type="text" name="kmNuevo" onkeypress="return soloNumeros(event);" maxlength="30" id="kmNuevo"/>
                    </td>
                </tr>
                <td colspan="2" class=""><b>Nota:</b> Para modificar el <?php echo $spanKilometraje; ?> Actual del Vehículo debe dirigirse a la sección "Registro de Vehículo"</td>
                </tr>
                
                <tr>
                    <td align="right" colspan="2">
                        <hr/>
                        <button type="button" class="puntero" onclick="xajax_guardarKilometraje(xajax.getFormValues('frmKilometraje'));" name="btnGuardar" id="btnGuardar">Guardar</button>
                        <button type="button" class="puntero" onclick="$('#divFlotante2').hide();">Cancelar</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</div>


<div style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;" class="root" id="divFlotante3">
    <div class="handle" id="divFlotanteTitulo3"><table><tbody><tr><td width="100%" id="tdFlotanteTitulo3">Editar Fallas</td></tr></tbody></table></div>
    <form style="margin:0" name="frmFallas" id="frmFallas" onsubmit="return false;">        
        <table width="800" border="0">
            <tbody>
                <tr>
                    <td align="right" class="tituloCampo">Nro. Vale:</td>
                    <td>
                        <input type="text" readonly="readonly" name="numeroValeFallas" maxlength="30" id="numeroValeFallas"/>
                        <input type="hidden" id="idValeRecepcionFalla" name="idValeRecepcionFalla" value="" />
                        <input type="hidden" id="idFallasEliminar" name="idFallasEliminar" value="" />
                    </td>
                    <td>
                    	Descripción: <b>F:</b> Falla <b>D:</b> Diagnóstico <b>R:</b> Respuesta
                        <button type="button" onclick="agregarFalla();"><img border="0" width="14" src="../img/iconos/plus.png" alt="agregar"></button>
                    </td>
                </tr>
                <tr>
                    <td align="left" colspan="3" id="tdFallas"></td>
                </tr>
                
                <tr>
                    <td align="right" colspan="3">
                        <hr/>
                        <button type="button" class="puntero" onclick="xajax_guardarFallas(xajax.getFormValues('frmFallas'));" name="btnGuardar" id="btnGuardar">Guardar</button>
                        <button type="button" class="puntero" onclick="$('#divFlotante3').hide();">Cancelar</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</div>

<div style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;" class="root" id="divFlotante4">
    <div class="handle" id="divFlotanteTitulo4"><table><tbody><tr><td width="100%" id="tdFlotanteTitulo4">Fotos Incidencias</td></tr></tbody></table></div>
    <form style="margin:0" name="frmFotos" id="frmFotos" onsubmit="return false;">        
        <table width="800" border="0">
            <tbody>
                <tr>
                    <td align="right" class="tituloCampo">Nro. Vale:</td>
                    <td>
                        <input type="text" readonly="readonly" name="numeroValeFoto" maxlength="30" id="numeroValeFoto"/>
                    </td>
                </tr>
                <tr>
                    <td align="left" colspan="3" id="tdFotosIncidencias" class="fotosIncidencias"></td>
                </tr>
                
                <tr>
                    <td align="right" colspan="3">
                        <hr/>
                        <button type="button" class="puntero" onclick="$('#divFlotante4').hide();">Cancelar</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</div>

<div style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;" class="root" id="divFlotante5">
    <div class="handle" id="divFlotanteTitulo5"><table><tbody><tr><td width="100%" id="tdFlotanteTitulo5">Estado del Vehículo</td></tr></tbody></table></div>
    <form style="margin:0" name="frmInventario" id="frmInventario" onsubmit="return false;">        
        <table border="0">
            <tbody>
                <tr>
                    <td align="right" class="tituloCampo">Nro. Vale:</td>
                    <td>
                        <input type="text" readonly="readonly" name="numeroValeInventario" maxlength="30" id="numeroValeInventario"/>
                        <input type="hidden" id="idValeRecepcionInventario" name="idValeRecepcionInventario" value="" />
                    </td>
                </tr>
                <tr>
                    <td align="left" colspan="3" id="tdInventario"></td>
                </tr>
                
                <tr>
                    <td align="right" colspan="3">
                        <hr/>
                        <button type="button" class="puntero" onclick="xajax_guardarIventario(xajax.getFormValues('frmInventario'));" name="btnGuardar" id="btnGuardar">Guardar</button>
                        <button type="button" class="puntero" onclick="$('#divFlotante5').hide();">Cancelar</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</div>

<div style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;" class="root" id="divFlotante6">
    <div class="handle" id="divFlotanteTitulo6"><table><tbody><tr><td width="100%" id="tdFlotanteTitulo6">Tag Keys/Llaves</td></tr></tbody></table></div>
    <form style="margin:0" name="frmLlaves" id="frmLlaves" onsubmit="return false;">
        <table width="290" border="0">
            <tbody>
                <tr>
                    <td>
                        <input type="hidden" id="idValeRecepcionLlaves" name="idValeRecepcionLlaves" value="" />
                    </td>
                </tr>
                <tr>
                    <td width="70%" align="right" class="tituloCampo">Nro. Vale:</td>
                    <td width="67%">
                        <input type="text" readonly="readonly" name="numeroValeLlaves" maxlength="30" id="numeroValeLlaves"/>
                    </td>
                </tr>
                <tr>
                    <td width="33%" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro Llaves:</td>
                    <td width="67%">
                        <input type="text" name="nroLlaves" maxlength="30" id="nroLlaves"/>
                    </td>
                </tr>
                
                
                <tr>
                    <td align="right" colspan="2">
                        <hr/>
                        <button type="button" class="puntero" onclick="xajax_guardarLlaves(xajax.getFormValues('frmLlaves'));" name="btnGuardar" id="btnGuardar">Guardar</button>
                        <button type="button" class="puntero" onclick="$('#divFlotante6').hide();">Cancelar</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</div>


<script>

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>','onchange="$(\'#btnBuscar\').click(); cargarEmpleadoEmpresa(this.value); "',0,0,0,"unico");
xajax_cargaLstEmpleado('','lstEmpleado','tdlstEmpleado');


function cargarTipoOrdenEmpresa(empresa){
	xajax_cargaLstTipoOrden('',empresa);
}

function cargarEmpleadoEmpresa(empresa){
	xajax_cargaLstEmpleado('','lstEmpleado','tdlstEmpleado',empresa);
}

xajax_listado(0,'id_recepcion','DESC');

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);
			
var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo3");
var theRoot   = document.getElementById("divFlotante3");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo4");
var theRoot   = document.getElementById("divFlotante4");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo5");
var theRoot   = document.getElementById("divFlotante5");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo6");
var theRoot   = document.getElementById("divFlotante6");
Drag.init(theHandle, theRoot);

</script>