<?php
require_once ("../connections/conex.php");

session_start();

define('PAGE_PRIV','sa_mantenimiento_tipo_orden_usuario');//nuevo gregor
//define('PAGE_PRIV','sa_tipo_orden_usuario');//anterior

require_once("../inc_sesion.php");

if (!validaAcceso(PAGE_PRIV)){
	echo "
	<script type=\"text/javascript\">
		alert('Acceso Denegado');
		window.location='index.php';
	</script>";
}

$currentPage = $_SERVER["PHP_SELF"];

require ('controladores/xajax/xajax_core/xajax.inc.php');

//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_sa_mantenimiento_tipo_orden_usuario.php");
include("controladores/ac_iv_general.php"); //tiene el cargaLstEmpresaFinal

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        
        <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Tipos de Orden Por Usuario</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
        
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="css/sa_general.css" />
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    <link rel="stylesheet" type="text/css" href="../js/domDragServicios.css"/>
    
	<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
      
    
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include("banner_servicios.php"); ?>
    </div>
    
    <div id="divInfo" class="print">
    <table border="0" width="100%">
        <tr>
        	<td id="tdTituloListado" class="tituloPaginaServicios" colspan="2">Mantenimiento de Tipos de Orden Por Usuario</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="left">
                    <button type="button" onclick="nuevo();" class="puntero"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/plus.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
            </td>
        	<td align="rigth">               
			<form id="frmBuscar" name="frmBuscar" onsubmit="$('#btnBuscar').click(); return false;" style="margin:0">
                <table align="left" border="0">
                <tr>
                	<td align="right" class="tituloCampo" width="100">Empresa:</td>
                    <td id="tdlstEmpresa">
                        <select id="lstEmpresa" name="lstEmpresa">
                            
                        </select>
                    </td>
                    <td class="tituloCampo">Tipo Orden:</td>
                    <td id="tdlstTipoOrden">
                        <select id="lstTipoOrden" name="lstTipoOrden">
                            
                        </select>
                    </td>
                    <td class="tituloCampo">Empleado/Usuario:</td>
                    
                    <td id="tdlstUsuario">  
                        <input id="nombreUsuario" name="nombreUsuario" style="height:18px;" /> 
                    </td>
                    <td>
                    <button class="noprint" type="button" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarUsuarioPermiso(xajax.getFormValues('frmBuscar'));" style="cursor:pointer;"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/find.png"/></td><td>&nbsp;</td><td>Buscar</td></tr></table></button>
						<button class="noprint" type="button" onclick="document.forms['frmBuscar'].reset(); $('#btnBuscar').click();" style="cursor:pointer;"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/view.png"/></td><td>&nbsp;</td><td>Ver Todo</td></tr></table></button>                    </td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td id="tdLista" colspan="2"></td>
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
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%">Permisos Usuario</td></tr></table></div>
        <form id="frmNuevoPermiso" name="frmNuevoPermiso" onsubmit="return false;" style="margin:0">            
        <table>
            <tr>
                <td class="tituloCampo">Nombre Empleado:</td>            
                <td>
                    <input type="text" id="nombreEmpleadoNuevo" style="width:200px;" readonly="readonly" />
                    <input type="hidden" id="idUsuarioNuevo" name="idUsuarioNuevo" value="" />
                </td>
                <td><button class="puntero" onclick="xajax_listadoEmpleados();" type="button"><table align="center" cellspacing="0" cellpadding="0"><tbody><tr><td><img src="../img/iconos/plus.png"></td></tr></tbody></table></button></td>
            </tr>
        </table>
            
        <div id ="permisosEmpresa" style="max-height: 300px; overflow:auto;">
            Debe seleccionar un empleado.
        </div>
            
            <input type="hidden" name="idPermisosEliminar" id="idPermisosEliminar" value="" />
        </form>
        
        <div>
            <hr/>            
            <button style="float:right; vertical-align:middle; white-space:nowrap;" onclick="$('#divFlotante').hide(); $('#divFlotante2').hide(); $('#permisosEmpresa').html(''); $('#idUsuarioNuevo').val('');"><img src="../img/iconos/cross.png" />Cerrar</button>
            
            <button style="float:right; vertical-align:middle; white-space:nowrap;" onclick="xajax_agregarPermisosNuevo(xajax.getFormValues('frmNuevoPermiso'));"><img src="../img/iconos/ico_save.png" />Guardar</button>
            
        </div>
</div>


<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%">Listado Empleados</td></tr></table></div>
        <table>
            <tr>
                <td  class="tituloCampo">Nombre Empleado: </td>
                <td><input type="text" id="buscarNombre" name="buscarNombre" onkeypress="return buscadorEnter(event);" /></td> 
                <td><button style="cursor:pointer;" onclick="xajax_buscarEmpleado($('#buscarNombre').val());" name="btnBuscarEmpleado" id="btnBuscarEmpleado" type="button" class="noprint"><table align="center" cellspacing="0" cellpadding="0"><tbody><tr><td><img src="../img/iconos/find.png"></td></tr></tbody></table></button></td>
            </tr>
        </table>
        <div id="divEmpleados">
            
        </div>
        <div>
            <hr/>
            <button style="float:right;" onclick="$('#divFlotante2').hide();">Cerrar</button>
        </div>
</div>


<script language="javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
        
	var theHandle2 = document.getElementById("divFlotanteTitulo2");
	var theRoot2   = document.getElementById("divFlotante2");
	Drag.init(theHandle2, theRoot2);
        
        xajax_listadoTipoOrdenUsuario();
        xajax_cargaLstTipoOrden();

	xajax_cargaLstEmpresaFinal('','onchange="document.getElementById(\'lstTipoOrden\').value = \'\'; xajax_cargaLstTipoOrden(\'\',this.value); $(\'#btnBuscar\').click();"','lstEmpresa','tdlstEmpresa','todos');//buscador
	xajax_cargaLstEmpresaFinal('','onchange="$(\'#btnBuscar\').click();"','lstEmpresaNueva','tdlstEmpresaNueva','todos');//nuevo
		
        $('#nombreUsuario').focus();
        
        //buscador de nombres, presionar enter
        function buscadorEnter(e) {
            if (e.keyCode == 13) {
                $('#btnBuscarEmpleado').click();
                return false;
            }
        }
        
        //Marcados para eliminar
        function cambioCheckbox(objeto){
            var valores = objeto.value;            
            arrayValores = valores.split("|");
            
            if(objeto.checked === false){//si lo quita uncheked
                
                if(arrayValores[1] !== "0"){//si ya esta registrado en bd para poder eliminarlo
                    
                   var idEliminar = $('#idPermisosEliminar').val();
                   if(idEliminar !== ""){//sino esta vacio
                       $('#idPermisosEliminar').val(idEliminar+"|"+arrayValores[1]);
                   }else{
                       $('#idPermisosEliminar').val(arrayValores[1]);
                   }
                   //var arrayEliminar = idEliminar.split("|");//no es necesario ya que no busco nada solo agrego

                    //no es necesario si ya pongo uno que limpie
//                    if(arrayEliminar.indexOf(arrayValores[1])==-1){
//                        alert("element doesn't exist");
//
//                    }else{
//                        alert("element found");
//                    }
                }
            }else{//si lo vuelve a agregar lo quito del listado de eliminacion
                if(arrayValores[1] !=="0"){//cuando sea cero no hara nada, 0 es el primer indice siempre borra
                    var idEliminar = $('#idPermisosEliminar').val();
                    var arrayEliminar = idEliminar.split("|");
                    arrayEliminar.splice(arrayEliminar.indexOf(arrayValores[1]), 1);

                    $('#idPermisosEliminar').val(arrayEliminar.join('|'));
                }
            }
        }
        
        //cuando es nuevo limpio el formulario y abro la pantalla (solo abre si tiene permiso, xajax)
        function nuevo(){
            $('#permisosEmpresa').html('Debe seleccionar un empleado.'); 
            $('#frmNuevoPermiso').get(0).reset(); 
            $('#idUsuarioNuevo').val(''); 
            $('#idPermisosEliminar').val('');  
            xajax_nuevo();
        }
        
        function editar(){
            $('#permisosEmpresa').html(''); 
            $('#frmNuevoPermiso').get(0).reset(); 
            $('#idUsuarioNuevo').val(''); 
            $('#idPermisosEliminar').val('');  
        }
        
</script>
