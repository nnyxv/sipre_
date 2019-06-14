<?php
    require_once ("../connections/conex.php");

    include ("../inc_sesion.php");
    session_start();
    
    define('PAGE_PRIV','sa_mantenimiento_cliente_garantia');//nuevo gregor
	//define('PAGE_PRIV','sa_cliente_garantia');//anterior
    
    if (!validaAcceso(PAGE_PRIV)){
		echo "
		<script type=\"text/javascript\">
			alert('Acceso Denegado');
			window.location='index.php';
		</script>";
	}
	

    require ('controladores/xajax/xajax_core/xajax.inc.php');

    $xajax = new xajax();

    $xajax->configure('javascript URI', 'controladores/xajax/');

    include("controladores/ac_iv_general.php");
    include("controladores/ac_sa_mantenimiento_cliente_garantia.php");
    
    $xajax->processRequest();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        
        <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Cliente Garantia</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
        
        <?php $xajax->printJavascript('controladores/xajax/'); ?>

	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    <link rel="stylesheet" type="text/css" href="../js/domDragServicios.css">
        
        <script type="text/javascript" language="javascript" src="../js/mootools.js"></script>
        <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
        <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>

        <style type="text/css">
           /* .root {
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
        <script type="text/javascript" language="javascript">
            function abrirVentana(acc){
                if($('divFlotante').style.display == 'none'){
                    $('divFlotante').style.display = '';
                    centrarDiv($('divFlotante'));
                }

                $('formClienteGarantia').reset();
                $('btnAceptar').value= "Guardar";
                $('btnAceptar').style.display= '';
                $('btnAceptar').setAttribute('onclick', "validar('"+acc+"');");

                if(acc == 1){
                    $('tdFlotanteTitulo').innerHTML= "Nuevo Cliente Garantia";
                    $('buttonIdentificacion').disabled= false;
                }else if(acc == 2){
                    $('tdFlotanteTitulo').innerHTML= "Editar Cliente Garantia";
                }else if(acc == 3){
                    $('tdFlotanteTitulo').innerHTML= "Ver Cliente Garantia";
                    $('btnAceptar').style.display= 'none';
                }
            }

            function validar(acc){
                if($('id_cliente').value != ""){
                    if(acc == 1){
                        xajax_insertClienteGarantia(xajax.getFormValues('formClienteGarantia'));
                    }else if(acc == 2){
                        xajax_updateClienteGarantia(xajax.getFormValues('formClienteGarantia'));
                    }
                }else{
                    alert('Debe seleccionar un cliente');
                }
            }

            function eliminar(id){
                if(confirm("Seguro desea eliminar el registro?")){
                    xajax_deleteClienteGarantia(id);
                }
            }

            function buscarCliente(){
                $('formListCliente').reset();
                $('tdFlotanteTituloCliente').innerHTML= "Listado de Clientes";
                $('btn_busq_cliente').setAttribute('onclick', "xajax_buscarCliente(xajax.getFormValues('formListCliente'));");

                xajax_listadoCliente(0,'nombre','ASC','');
            }

            function asignarCliente(id, iden, nombre, tipo, estatus){
                $('id_cliente').value= id;
                $('txtIdentificacion').value= iden;
                $('txtNombreCliente').value= nombre;
                $('txtTipoCliente').value= tipo;
                $('txtEstatusCliente').value= estatus;
                $('divFlotanteCliente').style.display='none';
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
                        <td class="tituloPaginaServicios">Cliente Garantia</td>
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
                                            <input type="button" class="noprint" id="btnBuscar" onclick="xajax_buscar(xajax.getFormValues('frmBuscar'), 1);" value="Buscar" />
                                            <input type="button" class="noprint" onclick="document.forms['frmBuscar'].reset(); $('btnBuscar').click();" value="Limpiar" />
                                        </td>
                                    </tr>
                                </table>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td id="tdListadoClienteGarantia"></td>
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
    <form name="formClienteGarantia" id="formClienteGarantia" method="post" action="">
        <input type="hidden" id="id_cliente_garantia" name="id_cliente_garantia" value=""/>
        <input type="hidden" id="id_cliente" name="id_cliente" value=""/>
        
        <table border="0" style="" width="600">
            <tr>
                <td align="right" class="tituloCampo" width="25%">
                    <span class="textoRojoNegrita">*</span><?php echo $spanCI." / ".$spanRIF ?>
                </td>
                <td width="75%" colspan="3">
                    <input type="text" id="txtIdentificacion" name="txtIdentificacion" value="" size="15" style="text-align: center;" readonly/>
                    <button type="button" id="buttonIdentificacion" name="buttonIdentificacion" onclick="buscarCliente();" style="cursor:default">
                        <img src="../img/iconos/find.png" alt="buscar" title="buscar" />
                    </button>
                </td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="25%">
                    <span class="textoRojoNegrita">*</span>Nombre Cliente:
                </td>
                <td width="75%" colspan="3">
                    <input type="text" id="txtNombreCliente" name="txtNombreCliente" value="" size="70" readonly/>
                </td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="25%">
                    <span class="textoRojoNegrita">*</span>Tipo Cliente:
                </td>
                <td width="25%">
                    <input type="text" id="txtTipoCliente" name="txtTipoCliente" value="" readonly/>
                </td>
                <td align="right" class="tituloCampo" width="25%">
                    <span class="textoRojoNegrita">*</span>Estatus:
                </td>
                <td width="25%">
                    <input type="text" id="txtEstatusCliente" name="txtEstatusCliente" value="" readonly/>
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

<div id="divFlotanteCliente" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
    <div id="divFlotanteTituloCliente" class="handle">
        <table>
            <tr>
                <td id="tdFlotanteTituloCliente" width="100%"></td>
            </tr>
        </table>
        <img class="close_window" src="../img/iconos/close_dialog.png" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="$('divFlotanteCliente').style.display='none';" border="0" />
    </div>
    <form name="formListCliente" id="formListCliente"  onsubmit="$('btn_busq_cliente').click(); return false;">
        <table border="0" style="" width="700">
            <tr>
                <td align="right" class="tituloCampo" id="campoBusq">Criterio</td>
                <td>
                    <input type="text" id="txtCriterio" name="txtCriterio" value="" size="30"/>
                
                    <button id="btn_busq_cliente" name="btn_busq_cliente" type="button" onclick="" style="cursor:default">
                        <table align="left" cellpadding="0" cellspacing="0">
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
                    <div id="tdListadoCliente"></div>
                </td>
            </tr>
            <tr>
                <td align="right" colspan="10">
                    <hr/>
                    <input type="button" id="btnAceptar" name="btnAceptar" onclick="" value="Aceptar"/>
                    <input type="button" onclick="$('divFlotanteCliente').style.display = 'none';" value="Cancelar"/>
                </td>
            </tr>
        </table>
    </form>
</div>

<script language="javascript" type="text/javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);

    var theHandle2 = document.getElementById("divFlotanteTituloCliente");
	var theRoot2   = document.getElementById("divFlotanteCliente");
	Drag.init(theHandle2, theRoot2);


	
	
	
</script>

<script type="text/javascript">
    xajax_listadoClienteGarantia(0,'nombre','ASC','');
</script>
