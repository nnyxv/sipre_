<?php
	@session_start();
    require_once ("../connections/conex.php");
	require_once("../inc_sesion.php");
    
    require ('controladores/xajax/xajax_core/xajax.inc.php');

    $xajax = new xajax();
    $xajax->configure('javascript URI', 'controladores/xajax/');
	
	define("PAGE_PRIV","sa_enlace_garantia");
	
	if(!(validaAcceso(PAGE_PRIV))) {
	echo "
	<script>
		alert('Acceso Denegado');
		window.location.href = 'index.php';
	</script>";
}
	 include("controladores/ac_iv_general.php");
    include("controladores/ac_sa_enlace_garantia.php");
    
    $xajax->processRequest();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        
        <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Enlace de Garantias</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
        
        <?php $xajax->printJavascript('controladores/xajax/'); ?>

        <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
        <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/>
                <link rel="stylesheet" type="text/css" media="all" href="../js/domDragServicios.css"/>

        <script type="text/javascript" language="javascript" src="../js/mootools.js"></script>
        <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
        <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
        
        <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
        <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
        <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
        <script type="text/javascript" language="javascript">
            function abrirVentana(acc){
                if($('divFlotante').style.display == 'none'){
                    $('divFlotante').style.display = '';
                    centrarDiv($('divFlotante'));
                }
                $('txtNumeroReferencia').readOnly= false;
                $('buttonAgregarVale').disabled= false;
                $('buttonAgregarOrden').disabled= false;
                $('btnAceptar').value= "Guardar";
                $('btnAceptar').style.display= '';
                $('btnAceptar').setAttribute('onclick', "validar('"+acc+"');");

                if(acc == 1){
                    $('tdFlotanteTitulo').innerHTML= "Nuevo Enlace Garantia";
                    $('tbodyVale').innerHTML= "";
                    $('formEnlace').reset();
                    $('idEnlace').value= "";
                }else if(acc == 2){
                    $('tdFlotanteTitulo').innerHTML= "Editar Enlace Garantia";
                }else if(acc == 3){
                    $('tdFlotanteTitulo').innerHTML= "Ver Enlace Garantia";
                    $('btnAceptar').style.display= 'none';
                    $('txtNumeroReferencia').readOnly= true;
                    $('buttonAgregarVale').disabled= true;
                    $('buttonAgregarOrden').disabled= true;
                }
            }

            function validar(acc){
                if(validarCampo("txtNumeroReferencia","t", "") == true
                    && validarCampo("txtIdOrden","t", "") == true){
                        result= true;
                }else{
                    result= false;
                }

                if($('idVale[]') == undefined){
                    alert('Debe agregar por lo menos un vale de salida');
                    return false;
                }

                if(result == false){
                    alert('Debe completar los campos en rojo');
                }else{
                    if(acc == 1){
                        xajax_insertEnlaceGarantia(xajax.getFormValues('formEnlace'));
                    }else if(acc == 2){
                        xajax_updateEnlaceGarantia(xajax.getFormValues('formEnlace'));
                    }
                }
            }

            function buscarOrden(){
                $('formListOrdenes').reset();
                $('tdFlotanteTituloOrdenes').innerHTML= "Listado de Ordenes de Servicios";
                $('campoBusq').innerHTML= "N&ordm; Orden:";
                $('btn_busq_ordenes').setAttribute('onclick', "xajax_buscarOrden(xajax.getFormValues('formListOrdenes'));");

                xajax_listadoOrdenes(0,'numero_orden','DESC',$('txtCriterio').value+'|'+$('fechaDesde').value+'|'+$('fechaHasta').value);
            }

            function buscarVale(){
                $('formListOrdenes').reset();
                $('tdFlotanteTituloOrdenes').innerHTML= "Listado de Vales de Salida";
                $('campoBusq').innerHTML= "N&ordm; Vale:";
                $('btn_busq_ordenes').setAttribute('onclick', "xajax_buscarVale(xajax.getFormValues('formEnlace'), xajax.getFormValues('formListOrdenes'));");
                
                var idVale= $('formEnlace').getElements('input[name*=idVale]').getProperty('value');
                
                xajax_listadoVales(0,'numero_vale','ASC', idVale+'|'+$('txtCriterio').value+'|'+$('fechaDesde').value+'|'+$('fechaHasta').value+'|'+$('idEnlace').value);
            }

            function asignarOrden(id, nombre, monto, numeroOrden){
                $('txtIdOrden').value= id;
				$('numeroOrdenMostrar').value= numeroOrden;
                $('txtNombreCliente').value= nombre;
                $('txtMontoTotalOrden').value= monto;
                $('divFlotanteOrdenes').style.display='none';
            }

            function asignarVale(id, numero, nombre, monto, btn, list){
                centrarDiv($('divFlotante'));

                var a = document.getElementById("tableVale").rows.length -1;

                var tabla = document.getElementById("tableVale").getElementsByTagName("TBODY")[0];
                var tr = document.createElement("TR");

                tr.id= 'tr'+a;
                
                var td = document.createElement("TD");
                if(btn == true){
                    td.innerHTML= "<button id='buttonVale' name='buttonVale' type='button' title='Eliminar Vale' onclick=\"borrarVale($('tr"+a+"'));calculoMonto(2, '"+monto+"');\" style='cursor:default'><img src='../img/iconos/ico_quitar.gif' alt='agregar' /></button>";
                }
                tr.appendChild(td);
                
                var td = document.createElement("TD");
                td.align= "center";
                td.innerHTML= numero+"<input type='hidden' id='idVale[]' name='idVale[]' value='"+id+"'>";
                tr.appendChild(td);

                var td = document.createElement("TD");
                td.innerHTML= nombre;
                tr.appendChild(td);

                var td = document.createElement("TD");
                td.align= "right";
                td.innerHTML= monto;
                tr.appendChild(td);

                tabla.appendChild(tr);
                controlBoton(true);
                calculoMonto(1, monto);
                
                var idVale= $('formEnlace').getElements('input[name*=idVale]').getProperty('value');
                if(list == true){
                    xajax_listadoVales(0,'numero_vale','ASC', idVale+'|');
                }
            }

            function borrarVale(tr) {
                centrarDiv($('divFlotante'));
                var tabla = document.getElementById("tableVale").getElementsByTagName("TBODY")[0];
                tabla.removeChild(tr);
            }

            function controlBoton(bool){
                f =document.forms.formListOrdenes;
                total = f.buttonOrden.length;
                for (i = 0; i < total; i ++){
                    f.buttonOrden[i].disabled = bool;
                }
            }

            function calculoMonto(acc, monto){
                monto= monto.replace(",", "");
                total= $('txtMontoTotalVale').value.replace(",", "");
                if(acc == 1){
                    $('txtMontoTotalVale').value= formatCurrency(parseFloat(total) + parseFloat(monto));
                }else{
                    $('txtMontoTotalVale').value= formatCurrency(parseFloat(total) - parseFloat(monto));
                }
            }

            function formatCurrency(num) {
                num = num.toString().replace(/$|,/g,'');
                if(isNaN(num))
                num = "0";
                sign = (num == (num = Math.abs(num)));
                num = Math.floor(num*100+0.50000000001);
                cents = num%100;
                num = Math.floor(num/100).toString();
                if(cents<10)
                cents = "0" + cents;
                for (var i = 0; i < Math.floor((num.length-(1+i))/3); i++)
                num = num.substring(0,num.length-(4*i+3))+','+
                num.substring(num.length-(4*i+3));
                return (((sign)?'':'-') + num + '.' + cents);
            }

            function aprobar(id, montoOrden, montoVale){
                if(montoOrden != montoVale){
                    if(confirm("El monto de la orden de servicio ("+montoOrden+") no conincide con el monto total de vales ("+montoVale+")\n\nSeguro desea aprobar el enlace?")){
                        xajax_aprobarEnlaceGarantia(id);
                    }
                }else{
                    if(confirm("Seguro desea aprobar el enlace?")){
                        xajax_aprobarEnlaceGarantia(id);
                    }
                }
            }
        </script>
        <style type="text/css">
/*            .root {
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
            }*/

            .close_window{
                    position:absolute;
                    top:-1px;
                    right:1px;
            }
        </style>
    </head>
    <body class="bodyVehiculos">
        <div id="divGeneralPorcentaje">
            <div class="noprint">
                <?php include("banner_servicios.php"); ?>
            </div>

            <div id="divInfo" class="print">
                <table border="0" width="100%">
                    <tr class="solo_print">
                        <td align="left" id="tdEncabezadoImprimir"></td>
                    </tr>
                    <tr>
                        <br/>
                        <td class="tituloPaginaServicios">Enlaces Garantias</td>
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
                                            <button id="buttonNew" name="buttonNew" type="button" onclick="abrirVentana(1);" style="cursor:default">
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
                        <td id="tdListadoEnlaces"></td>
                    </tr>
                </table>
            </div>
            <?php include ('pie_pagina.php'); ?>
        </div>
    </body>
</html>

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
    <div id="divFlotanteTitulo" class="handle">
        <table>
            <tr>
                <td id="tdFlotanteTitulo" width="100%"></td>
            </tr>
        </table>
        <img class="close_window" src="../img/iconos/close_dialog.png" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="$('divFlotante').style.display='none';" border="0" />
    </div>
    <form name="formEnlace" id="formEnlace" method="post" action="">
        <input type="hidden" id="idEnlace" name="idEnlace" value=""/>
        <table border="0" id="tblAlmacen" style="" width="660">
            <tr>
                <td align="right" class="tituloCampo" width="25%">
                    <span class="textoRojoNegrita">*</span>N&ordm; de Referencia:
                </td>
                <td width="25%">
                    <input type="text" id="txtNumeroReferencia" name="txtNumeroReferencia" style="text-align: right" value="" />
                </td>
                <td align="right" class="tituloCampo" width="25%">
                    <span class="textoRojoNegrita">*</span>N&ordm; de Orden:
                </td>
                <td width="25%">
                	<input type="text" id="numeroOrdenMostrar" name="numeroOrdenMostrar" value="" size="8" style="text-align: center;" readonly/>
                    <input type="hidden" id="txtIdOrden" name="txtIdOrden" value="" size="8" style="text-align: center;" readonly/>
                    <button type="button" id="buttonAgregarOrden" name="buttonAgregarOrden" onclick="buscarOrden();" style="cursor:default">
                        <img src="../img/iconos/find.png" alt="buscar" title="buscar" />
                    </button>
                </td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="25%">
                    <span class="textoRojoNegrita">*</span>Nombre Cliente:
                </td>
                <td width="75%" colspan="3">
                    <input type="text" id="txtNombreCliente" name="txtNombreCliente" value="" size="75" readonly/>
                </td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="25%">
                    <span class="textoRojoNegrita">*</span>Monto Total Orden:
                </td>
                <td width="25%">
                    <input type="text" id="txtMontoTotalOrden" name="txtMontoTotalOrden" value="0.00" style="text-align: right" readonly/>
                </td>
                <td align="right" class="tituloCampo" width="25%">
                    <span class="textoRojoNegrita">*</span>Monto Total Vales:
                </td>
                <td width="25%">
                    <input type="text" id="txtMontoTotalVale" name="txtMontoTotalVale" value="0.00" style="text-align: right" readonly/>
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    <table class="tituloArea" border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                            <td align="left" height="22" width="40%">
                                <button type="button" id="buttonAgregarVale" name="buttonAgregarVale" title="Agregar Vale" onclick="buscarVale();" style="cursor:default">
                                    <img src="../img/iconos/ico_agregar.gif" alt="agregar" />
                                </button>
                            </td>
                            <td align="left" width="60%">
                                Vales de Salida
                            </td>
                        </tr>
                    </table>
                    <table border="0" cellpadding="0" width="100%" id="tableVale">
                        <thead>
                            <tr class="tituloColumna">
                                <td style="width: 20px;">&nbsp;</td>
                                <td align="center" width="20%">N&ordm; Vale</td>
                                <td align="center" width="60%">Nombre Cliente</td>
                                <td align="center" width="20%">Monto</td>
                            </tr>
                        </thead>
                        <tbody id="tbodyVale">
                            <tr id="trItmPie">
                                <div id="divVales"></div>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            <tr>
                <td align="right" colspan="4">
                    <hr/>
                    <input type="button" id="btnAceptar" name="btnAceptar" onclick="" value="Aceptar"/>
                    <input type="button" onclick="$('divFlotante').style.display = 'none';" value="Cancelar"/>
                </td>
            </tr>
        </table>
    </form>
</div>

<div id="divFlotanteOrdenes" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
    <div id="divFlotanteTituloOrdenes" class="handle">
        <table>
            <tr>
                <td id="tdFlotanteTituloOrdenes" width="100%"></td>
            </tr>
        </table>
        <img class="close_window" src="../img/iconos/close_dialog.png" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="$('divFlotanteOrdenes').style.display='none';" border="0" />
    </div>
    <form name="formListOrdenes" id="formListOrdenes" method="post" action="">
        <table border="0" style="" width="600">
            <tr>
                <td align="right" class="tituloCampo" id="campoBusq"></td>
                <td>
                    <input type="text" id="txtCriterio" name="txtCriterio" value="" size="10"/>
                </td>
                <td align="right" class="tituloCampo">Desde:</td>
                <td>
                    <input type="text" id="fechaDesde" name="fechaDesde" value="" size="10"/>
                    <img src="../img/iconos/ico_date.png" id="imgFechaDesde" name="imgFechaDesde" class="puntero noprint" alt=""/>
                    <script type="text/javascript">
                        Calendar.setup({
                        inputField : "fechaDesde",
                        ifFormat : "%d-%m-%Y",
                        button : "imgFechaDesde"
                        });
                    </script>
                </td>
                <td align="right" class="tituloCampo">Hasta:</td>
                <td>
                    <input type="text" id="fechaHasta" name="fechaHasta" value="" size="10"/>
                    <img src="../img/iconos/ico_date.png" id="imgFechaHasta" name="imgFechaHasta" class="puntero noprint" alt=""/>
                    <script type="text/javascript">
                        Calendar.setup({
                        inputField : "fechaHasta",
                        ifFormat : "%d-%m-%Y",
                        button : "imgFechaHasta"
                        });
                    </script>
                </td>
                <td>
                    <button id="btn_busq_ordenes" name="btn_busq_ordenes" type="button" onclick="" style="cursor:default">
                        <table align="center" cellpadding="0" cellspacing="0">
                            <tbody>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td>
                                        <img src="../img/iconos/find.png" alt="buscar" title="buscar" />
                                    </td>
                                    <td>&nbsp;</td>
                                    <td>Buscar</td>
                                </tr>
                            </tbody>
                        </table>
                    </button>
                </td>
            </tr>
            <tr>
                <td colspan="10">
                    <div id="tdListadoOrdenes"></div>
                </td>
            </tr>
            <tr>
                <td align="right" colspan="10">
                    <hr/>
                    <input type="button" id="btnAceptar" name="btnAceptar" onclick="" value="Aceptar"/>
                    <input type="button" onclick="$('divFlotanteOrdenes').style.display = 'none';" value="Cancelar"/>
                </td>
            </tr>
        </table>
    </form>
</div>

<script language="javascript" type="text/javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);

        var theHandle2 = document.getElementById("divFlotanteTituloOrdenes");
	var theRoot2   = document.getElementById("divFlotanteOrdenes");
	Drag.init(theHandle2, theRoot2);
</script>

<script type="text/javascript">
    xajax_listadoEnlacesGarantias(0,'numero_orden','DESC','');
</script>
