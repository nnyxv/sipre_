<?php
require_once("../connections/conex.php");

session_start();

if (in_array(idArrayPais,array(1,2,3))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
	if (isset($_GET['idPresupuesto'])) {
		echo sprintf("<script>window.location.href='an_pedido_venta_form.php?idPresupuesto=".$_GET['idPresupuesto']."';</script>");
	} else if (isset($_GET['vw'])) {
		echo sprintf("<script>window.location.href='an_pedido_venta_form.php?vw=i';</script>");
	} else {
		echo sprintf("<script>window.location.href='an_pedido_venta_form.php';</script>");
	}
}

/* Validación del Módulo */
require_once('../inc_sesion.php');
if((!validaAcceso("an_pedido_venta_list","insertar") && !$_GET['id'])
|| (!validaAcceso("an_pedido_venta_list","editar") && $_GET['id'] > 0)
|| (!validaAcceso("cj_factura_venta_list","insertar") && $_GET['idFactura'] > 0)) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_an_ventas_pedido_insertar.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Generar Pedido a Caja</title>
	<link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
	<link rel="stylesheet" href="an_ventas_pedido_insertar_style.css"/>
	<script src="anajax.js"></script>
    <script src="vehiculos.inc.js"></script>
	
	<script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblPermiso').style.display = 'none';
		
		if (verTabla == "tblPermiso") {
			document.forms['frmPermiso'].reset();
			byId('hddModulo').value = '';
			
			byId('txtContrasena').className = 'inputHabilitado';
			
			xajax_formValidarPermisoEdicion(valor);
			
			tituloDiv1 = 'Ingreso de Clave Especial';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblPermiso") {
			byId('txtContrasena').focus();
			byId('txtContrasena').select();
		}
	}
	
    //funciones ACCESORIOS:
    var acc = [];
        
    var rep_val;
    var rep_tipo;
    
    var factor
    <?php
	$queryFactor = "SELECT
		mes,
		factor,
		CONCAT(mes,' Meses / ',tasa,'%') AS financiamento
	FROM an_banco_factor
	WHERE id_banco = ".$lstBancoFinanciar."
	ORDER BY tasa;";
	$rsFactor = @mysql_query($queryFactor);
	if ($rsFactor) {
		if (mysql_num_rows($rsFactor) != 0) {
			while ($rowFactor = mysql_fetch_assoc($rsFactor)) {
				$selected .= ($rowFactor['mes'] == $lstMesesFinanciar) ? "selected=\"selected\"" : "";
				
				$select .= '<option '.$selected.' value="'.$rowFactor['mes'].'">'.$rowFactor['financiamento'].'</option>';
				
				$factores .= ",".$rowFactor['mes'].":".$rowFactor['factor'];
            }
			$factores[0] = " ";
			echo "={".$factores."};";
        } else {
            echo "=null;";
        }
    } else {
        echo "=null;";
    } ?>
    
    //objeto accesotrio para la colección
    function accesorio(aid, apq, avalue, aname, vaccion) {
        this.iddet = "null";
        this.id = aid;
        this.pq = apq;
        this.nombre = aname; // Descripcion Adicional
        this.civa = "";
        this.piva = "";
        this.iva = "";
        this.value = avalue; // Precio Adicional + Iva
		this.hddTipoAccesorio = "";
		this.cbxCondicion = 2; // 1 = Pagado, 2 = Financiado
        this.accion = vaccion; // 1 = Agregar, 2 = Eliminar, 3 = Modificar
        this.capa = null;
        this.inbase = false;
    }
    
    function asignarPrecio(obj) {
        if (obj > 0) {
            var txtPrecioBase = obj;
        } else {
			var txtPrecioBase = byId('txtPrecioBase').value;
		}
		var txtDescuento = byId('txtDescuento');
		var txtPrecioVenta = byId('txtPrecioVenta');
		
		//calculo de iva e impuesto al lujo
		var precio = parseNumRafk(txtPrecioBase) - parseNumRafk(byId('txtDescuento').value);
		var iva = parseNumRafk(byId('porcentaje_iva').value);
		var piva = 0;
		var plujo = 0;
		if (iva != 0) {
			piva = precio * iva / 100;
		}
		
		var lujo = parseNumRafk(byId('porcentaje_impuesto_lujo').value);
		if (lujo != 0) {
			plujo = precio * lujo / 100;
		}
		precio = precio + piva + plujo;
		
		byId('txtPrecioBase').value = formatoRafk(txtPrecioBase, 2);
		txtPrecioVenta.value = formatoRafk(precio, 2);
		
		percent2();
		if (obj > 0) {
			byId('txtPrecioBase').focus();
			byId('txtPrecioBase').select();
		}
    }
    
    //Busqueda AJAX accesorios:
    function buscarAdicional(campo){
        var _obj = byId(campo);
		
		cancelarCliente();
		cancelarVehiculo();
		
        _obj.readOnly = false;
		/*if (_obj.value == "") {
			var lista = byId("listaaccesorio");
			lista.style.visibility = "hidden";
			_obj.focus();
			return;
		}*/
        var a = new Ajax();
        a.load = function(texto){
            var lista = byId("listaaccesorio");
            lista.style.visibility = "visible";
            var obj = byId("addacc");
            lista.style.left = getOffsetLeft(obj)-(lista.offsetWidth-obj.offsetWidth)+"px";
            lista.style.top = getOffsetTop(obj)+"px";
            lista.style.margin = obj.offsetHeight+"px 0px 0px 0px";
            lista.innerHTML = texto;
            lista.focus();
            _obj.focus();
        };
        a.sendget("an_ventas_presupuesto_ajax.php","ajax_acc=" + _obj.value,false);
    }
    
    //busqueda AJAX Cliente
    function buscarCliente(campo){
        var _obj = byId(campo);
		
		cancelarAdicional();
		cancelarVehiculo();
		
        _obj.readOnly = false;
        if (_obj.value == "") {
            var lista = byId("listacliente");
            lista.style.visibility = "hidden";
            _obj.focus();
            return;
        }
        var a = new Ajax();
        //a.loading=carga;
        //a.error=er;
        a.load = function(texto){
            var lista = byId("listacliente");
            lista.style.visibility = "visible";
            var obj = byId("cedula");
            lista.style.left = getOffsetLeft(obj) + "px";
            lista.style.top = getOffsetTop(obj) + "px";
            lista.style.margin = obj.offsetHeight + "px 0px 0px 0px";
            lista.innerHTML = texto;
        };
        a.sendget("an_ventas_presupuesto_ajax.php","ajax_cedula=" + _obj.value, false);
    }
    
    //Busqueda AJAX vehiculo:
    function buscarVehiculo(campo){
        var _obj = byId(campo);
		
		cancelarAdicional();
		cancelarCliente();
		
        _obj.readOnly = false;
        /*if (_obj.value == "") {
            var lista = byId("listavehiculo");
            lista.style.visibility = "hidden";
            _obj.focus();
            return;
        }*/
        var a = new Ajax();
        a.load = function(texto){
            var lista = byId("listavehiculo");
            
            lista.style.visibility = "visible";
            var obj = byId("modelo");
            lista.style.left = getOffsetLeft(obj)+"px";
            lista.style.top = getOffsetTop(obj)+"px";
            lista.style.margin = obj.offsetHeight+"px 0px 0px 0px";
            lista.innerHTML = texto;
        };
        a.sendget("an_ventas_presupuesto_ajax.php", "ajax_vehiculo=" + _obj.value
			+ "&idEmpresa=" + byId('txtIdEmpresa').value
			+ "&idCliente=" + byId('txtIdCliente').value, false);
    }
    
    function cancelarAdicional() {
        var lista = byId("listaaccesorio");
		
		lista.style.visibility = "hidden";
		idlista = 0;
		/*var obj1 = byId("modeloc");
		var obj2 = byId("modelo");
		if (obj1 != undefined) {
			if (obj1.value != "") {
				obj1.value = "";
			}
		}*/
    }
    
    function cancelarCliente() {
        var lista = byId("listacliente");
		
		lista.style.visibility = "hidden";
		//idlista = 0;
		var obj1 = byId("clientec");
		var obj2 = byId("cedula");
		if (obj1 != undefined) {
        	//if (obj1.value != "") {
            	obj2.value = obj1.value;
	        //}
    	    //var obj3 = byId("nombre");
        	//obj3.focus();
		}
    }
    
    function cancelarVehiculo() {
        var lista = byId("listavehiculo");
		
		lista.style.visibility = "hidden";
		idlista = 0;
		var obj1 = byId("modeloc");
		var obj2 = byId("modelo");
		if (obj1 != undefined) {
			//if (obj1.value != "") {
				obj2.value = obj1.value;
			//}
		}
    }
    
    function cargarCombo(obj){
        loadxml("an_ventas_presupuesto_ajax.php", 'combo', obj.value, '', '', '');
    }
	
	function cargarXML(cmd, id, idEmpresa, idCliente){
		loadxml("an_ventas_pedido_ajax_asignar.php", cmd, id, idEmpresa, idCliente, '');
	}
        
    function insertarAdicional(aid, avalue, aname, iva, civa, piva, hddTipoAccesorio, cbxCondicion) {
		var inc = true;
		for (var j = 0; j < acc.length; j++) {
			if (acc[j] != null) {
				if (acc[j].id == aid) {
					if (acc[j].accion == 2) {
						var accs = byId(acc[j].capa);
						if (acc[j].inbase == true) {
							acc[j].accion = 3;
						} else {
							acc[j].accion = 1;
						}
						var aca = byId('aca'+acc[j].id);
						aca.value = acc[j].accion;
						accs.style.display = "inline";
						inc = false;
						//cancelarAdicional();
						percent();
						break;
					}
					alert(acc[j].nombre + " ya está incluido");
					inc = false;
					break;
				}
			}
		}
		if (inc) {
			var add = new accesorio(aid, '', avalue, aname, 1);
			add.civa = civa;
			add.piva = piva;
			add.iva = iva;
			add.hddTipoAccesorio = hddTipoAccesorio;
			add.cbxCondicion = cbxCondicion;
			insertarItemAdicional(add);
			//cancelarAdicional();
		}
	}
        
    function insertarPaquete(idp){
        var f = byId("paq"+idp);
        var checks = false;
        var msg = "";
        for (var i = 0; i < f.elements.length; i++) {
            if (f.elements[i].type == "checkbox") {
                var e = f.elements[i];
                //alert(e.value+" "+e.id+" "+e.checked);
                if (e.checked) {
                    checks = true;
                    //busca para ver si está agregado
                    var inc = true;
                    for (var j = 0; j < acc.length; j++) {
                        if (acc[j] != null) {
                            if (acc[j].id == e.id) {
                                if (acc[j].accion == 2) {
                                    var accs = byId(acc[j].capa);
                                    accs.style.display = "inline";
                                    if (acc[j].inbase == true) {
                                        acc[j].accion = 3;
                                    } else {
                                        acc[j].accion = 1;
                                    }
                                    var aca = byId('aca'+acc[j].id);
                                    aca.value = acc[j].accion;
                                    inc = false;
                                    cancelarAdicional();
                                    percent();
                                    break;
                                }
                                msg += acc[j].nombre + " ya está incluido\n";
                                inc = false;
                                break;
                            }
                        }
                    }
					
                    if (inc) {
                        var na = new accesorio(e.id, f.elements['p'+e.id].value, e.value, e.name, 1);
                        na.iva = f.elements['iva'+e.id].value;
                        na.piva = f.elements['piva'+e.id].value;//nuevo
                        na.civa = f.elements['civa'+e.id].value;//nuevo
						na.hddTipoAccesorio = f.elements['hddTipoAccesorio'+e.id].value;
						na.cbxCondicion = f.elements['cbxCondicion'+e.id].value;
                        //na.accion=1;
                        insertarItemAdicional(na);
                    }
                }
            }
        }
        if (!checks) {
            alert("Debe elegir al menos un elemento de la lista");
        } else {
            if (msg != "") {
                alert(msg);
                return;
            }
            cancelarAdicional();
        }
    }
    
    function insertarItemAdicional(ac){
        var p = acc.length;
        acc[p] = ac;
		
		var t = "<table border=\"0\" id=\"tblAdicional" + ac.id + "\" width=\"100%\">" +
		"<tr align=\"left\">" +
			"<td width=\"50%\">" + ac.nombre + ":</td>" +
			"<td width=\"15%\">" +
				"<table id=\"tblCondicionItm" + ac.id + "\" cellpadding=\"0\" cellspacing=\"0\">" +
				"<tr>" +
					"<td><input type=\"checkbox\" id=\"cbxCondicionItm" + ac.id + "\" name=\"cbxCondicionItm" + ac.id + "\" " + ((ac.cbxCondicion == 1) ? "checked=\"checked\"" : "") + " onclick=\"percent();\" value=\"1\"/></td>" +
					"<td><label for=\"cbxCondicionItm" + ac.id + "\">Pagado</label></td>" +
				"</tr>" +
				"</table>" +
			"</td>" +
			"<td width=\"15%\">" +
				"<select id=\"lstTipoAccesorioItm" + ac.id + "\" name=\"lstTipoAccesorioItm" + ac.id + "\" class=\"inputCompletoHabilitado\" onchange=\"percent();\">" +
					"<option " + ((ac.hddTipoAccesorio == 1) ? "selected=\"selected\"": "") + " value=\"1\">Adicional</option>" +
					"<option " + ((ac.hddTipoAccesorio == 3) ? "selected=\"selected\"": "") + " value=\"3\">Contrato</option>" +
				"</select>" +
			"</td>" +
			"<td align=\"right\" width=\"20%\">" +
				"<input type=\"text\" id=\"txtPrecioConIvaItm" + ac.id + "\" name=\"txtPrecioConIvaItm" + ac.id + "\" class=\"inputCompletoHabilitado\" onchange=\"percent(); setFormatoRafk(this, 2);\" onkeyup=\"percent();\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right;\" value=\"" + formatoRafk(ac.value, 2) + "\"/>"+
			"</td>" +
			"<td><a href=\"javascript:validarEliminarAdicional(" + p + ")\"><img border=\"0\" src=\"../img/iconos/minus.png\" alt=\"Quitar\" style=\"margin-left:2px;\"/></a>" + 
				"<input type=\"hidden\" name=\"ac[]\" value=\"" + ac.id.substring(3) + "\"/>" + 
				"<input type=\"hidden\" name=\"acp[]\" value=\"" + ac.pq + "\"/>" + 
				"<input type=\"hidden\" id=\"aca" + ac.id + "\" name=\"acaccion[]\" value=\"" + ac.accion + "\"/>" + 
				"<input type=\"hidden\" id=\"hddIdDetItm" + ac.id + "\" name=\"hddIdDetItm" + ac.id + "\" value=\"" + ac.iddet + "\"/>" + 
				"<input type=\"hidden\" id=\"hddCostoUnitarioItm" + ac.id + "\" name=\"hddCostoUnitarioItm" + ac.id + "\" value=\"" + ac.civa + "\"/>" + 
				"<input type=\"hidden\" id=\"hddPorcIvaItm" + ac.id + "\" name=\"hddPorcIvaItm" + ac.id + "\" value=\"" + ac.piva + "\"/>" + 
				"<input type=\"hidden\" id=\"hddAplicaIvaItm" + ac.id + "\" name=\"hddAplicaIvaItm" + ac.id + "\" value=\"" + ac.iva + "\"/>" + 
				"<input type=\"hidden\" id=\"hddTipoAccesorioItm" + ac.id + "\" name=\"hddTipoAccesorioItm" + ac.id + "\" value=\"" + ac.hddTipoAccesorio + "\"/></td>" + 
		"</tr>" + 
		"<tr align=\"left\">" +
			"<td></td>" +
			"<td colspan=\"3\">" +
				"<table id=\"tblMostrarItm" + ac.id + "\" cellpadding=\"0\" cellspacing=\"0\">" +
				"<tr>" +
					"<td><input type=\"checkbox\" id=\"cbxMostrarItm" + ac.id + "\" name=\"cbxMostrarItm" + ac.id + "\" " + ((ac.cbxMostrar == 1) ? "checked=\"checked\"" : "") + " onclick=\"percent();\" value=\"1\"/></td>" +
					"<td><label for=\"cbxMostrarItm" + ac.id + "\">Incluir en el precio de la unidad</label></td>" +
				"</tr>" +
				"</table>" +
			"</td>" +
		"</tr>" + 
		"</table>";
        var accs = byId("accesorios");
        var ne = document.createElement("div");
        ac.capa = 'capa' + ac.id;
        ne.setAttribute('id',ac.capa);
        ne.innerHTML = t;
        accs.appendChild(ne);
        
        percent();
    }
    
    function ku_buscarAdicional(e, obj) {
        if (e.keyCode == 13 || byId(obj).readOnly == false){
            buscarAdicional(obj);
        }
    }
    
    var lp = -1;
    function ku_buscarCliente(e, obj) {
        var slista = byId("overclientes");
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
                cargarXML("cliente", item.accion, '');
            } else {
                buscarCliente(obj);
            }
            lp = -1;
        }
    }
    
    function ku_buscarVehiculo(e, obj){
        var slista = byId("overvehiculo");
        if (e.keyCode == 40 || e.keyCode == 38) {
            if (slista==null){ 
                return;
            }
            if (e.keyCode==40){
                lp++;
            } else {
                lp--;
            }
            if (lp >= slista.childNodes.length){
                lp = 0;
            } else if (lp <= -1){
                lp = slista.childNodes.length-1;
            }
            for (i = 0;i < slista.childNodes.length; i++){
                if (lp != i){
                    var item = slista.childNodes.item(i);
                    if (item.lastcolor != null)
                        item.style.background = item.lastcolor;
                }
            }
            var item = slista.childNodes.item(lp);
            if (item != null)
                item.style.background = "#FFCC66";
        } else if (e.keyCode == 13 || byId(obj).readOnly == false){
            if (lp != -1 && e.keyCode == 13){
                var item = slista.childNodes.item(lp);
                cargarXML("vehiculo", item.accion, '');
            } else {
                buscarVehiculo(obj);
            }
            lp = -1;
        }
    }
	
    function ku_generar(event) {
        if (event.keyCode == 13) {
            validarFrmGenerar();
        }
    }
    
    function newacc(aid, apq, avalue, aname, vaccion, iva, civa, piva, hddTipoAccesorio, cbxCondicion, cbxMostrar, iddet) {
        var na = new accesorio(aid, apq, avalue, aname, vaccion);
        na.iddet = iddet;
        na.civa = civa;
        na.piva = piva;
        na.iva = iva;
		na.hddTipoAccesorio = hddTipoAccesorio;
		na.cbxCondicion = cbxCondicion;
		na.cbxMostrar = cbxMostrar;
        na.inbase = true;
        insertarItemAdicional(na);
    }
    
    function percent() {
        var precioVenta = byId('txtPrecioVenta');
		var porcInicial = byId('txtPorcInicial');
		var montoInicial = byId('txtMontoInicial');
        var mesesFinanciar = byId('lstMesesFinanciar');
        var porcentajeFlat = byId('porcentaje_flat');
		var montoSeguro = parseNumRafk(byId("txtMontoSeguro").value);
		
        var montoAnticipo = byId('txtMontoAnticipo');
		
		byId('fieldsetFormaPago').style.display = 'none';
        
        if (byId('hddTipoInicial').value == 0) {
			var txtPorcInicial = parseNumRafk(unformatNumberRafk(porcInicial.value));
			var txtMontoInicial = (parseNumRafk(unformatNumberRafk(porcInicial.value)) * parseNumRafk(unformatNumberRafk(precioVenta.value))) / 100;
			
			byId('rbtInicialPorc').checked = true;
			byId('rbtInicialPorc').click();
		} else if (byId('hddTipoInicial').value == 1) {
			var txtPorcInicial = (parseNumRafk(unformatNumberRafk(montoInicial.value)) * 100) / parseNumRafk(unformatNumberRafk(precioVenta.value));
			var txtMontoInicial = parseNumRafk(unformatNumberRafk(montoInicial.value));
			
			byId('rbtInicialMonto').checked = true;
			byId('rbtInicialMonto').click();
		}
		porcInicial.value = formatoRafk(txtPorcInicial, 2);
		montoInicial.value = formatoRafk(txtMontoInicial, 2);
        
        var totalAdicionales = 0;
		var totalContrato = 0;
		for (i = 0; i < acc.length; i++) {
			var clase = (i % 2) ? 'trResaltar4' : 'trResaltar5';
			byId('tblAdicional' + acc[i].id).className = clase;
			
			byId("hddTipoAccesorioItm" + acc[i].id).value = byId("lstTipoAccesorioItm" + acc[i].id).value;
			
			acc[i].hddTipoAccesorio = byId("lstTipoAccesorioItm" + acc[i].id).value;
			if (acc[i].accion != 2) {
				var obj = byId("txtPrecioConIvaItm" + acc[i].id);
				
				byId("tblCondicionItm" + acc[i].id).style.display = '';
				byId("tblMostrarItm" + acc[i].id).style.display = 'none';
				
				if (acc[i].hddTipoAccesorio == 1) { // 1 = Adicional
					totalAdicionales += parseNumRafk(obj.value);
					
					byId("tblMostrarItm" + acc[i].id).style.display = '';
					if (byId("cbxCondicionItm" + acc[i].id).checked == true) {
						byId("tblMostrarItm" + acc[i].id).style.display = 'none';
						byId("cbxMostrarItm" + acc[i].id).checked = false;
					}
					byId("tblCondicionItm" + acc[i].id).style.display = '';
					if (byId("cbxMostrarItm" + acc[i].id).checked == true) {
						byId("tblCondicionItm" + acc[i].id).style.display = 'none';
						byId("cbxCondicionItm" + acc[i].id).checked = false;
					}
					
				} else if (acc[i].hddTipoAccesorio == 3) { // 3 = Contrato
					byId("tblCondicionItm" + acc[i].id).style.display = 'none';
					byId("cbxCondicionItm" + acc[i].id).checked = false;
					byId("cbxMostrarItm" + acc[i].id).checked = false;
					
					totalContrato += parseNumRafk(obj.value);
				}
			}
			
			<?php if (in_array(idArrayPais,array(1,2))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico ?>
				byId("tblCondicionItm" + acc[i].id).style.display = 'none';
				byId("tblMostrarItm" + acc[i].id).style.display = 'none';
			<?php } ?>
		}
        
        var a1,a2,a3,a4;
        a1 = byId("vexacc1").value;
        a2 = byId("vexacc2").value;
        a3 = byId("vexacc3").value;
        a4 = byId("vexacc4").value;
		
        var totalAccesorios = formatoRafk(parseNumRafk(a1) + parseNumRafk(a2) + parseNumRafk(a3) + parseNumRafk(a4), 2);
		var saldoFinanciar = parseNumRafk(precioVenta.value) - parseNumRafk(montoInicial.value);
        
        if (parseNumRafk(porcInicial.value) == 100) {
            montoFlat = 0;
			if (byId('lstBancoFinanciar').value > 0) {
				selectedOption('lstBancoFinanciar','-1');
				byId('lstBancoFinanciar').onchange();
			}
		} else {
			byId('fieldsetFormaPago').style.display = '';
        	var montoFlat = (parseNumRafk(saldoFinanciar) * parseNumRafk(porcentajeFlat.value)) / 100; 
        }
		
        var totalInicialGastos = parseNumRafk(montoInicial.value) + totalAdicionales;
        var complementoInicial = parseNumRafk(montoInicial.value) - parseNumRafk(montoAnticipo.value);
        var precioTotal = complementoInicial + totalAdicionales + montoFlat;
        var totalPedido = parseNumRafk(precioVenta.value) + totalAdicionales;
		byId("txtTotalInicialGastos").value = formatoRafk(totalInicialGastos, 2);
		byId("txtSaldoFinanciar").value = formatoRafk(saldoFinanciar, 2);
        byId('txtMontoComplementoInicial').value = formatoRafk(complementoInicial, 2);
		byId('txtMontoFLAT').value = formatoRafk(montoFlat, 2);
        byId('txtPrecioTotal').value = formatoRafk(precioTotal, 2);
		byId('txtTotalPedido').value = totalPedido;
		
		var cuotasFinanciar = byId('txtCuotasFinanciar');
		if (mesesFinanciar != null && factor != null) {
			var interes = parseNumRafk(saldoFinanciar) * factor[mesesFinanciar.value];
		}
		
		if (interes) {
			cuotasFinanciar.value = formatoRafk(interes, 2);
		}
		
		byId("txtTotalAdicionalContrato").value = formatoRafk(totalContrato, 2);
    }
    
    function percent2() {
        var precioVenta = byId('txtPrecioVenta');
		var porcInicial = byId('txtPorcInicial');
        var montoInicial = byId('txtMontoInicial');
		
		if (byId('hddTipoInicial').value == 0) {
			var txtPorcInicial = parseNumRafk(unformatNumberRafk(porcInicial.value));
			var txtMontoInicial = (parseNumRafk(unformatNumberRafk(porcInicial.value)) * parseNumRafk(unformatNumberRafk(precioVenta.value))) / 100;
		} else if (byId('hddTipoInicial').value == 1) {
			var txtPorcInicial = (parseNumRafk(unformatNumberRafk(montoInicial.value)) * 100) / parseNumRafk(unformatNumberRafk(precioVenta.value));
			var txtMontoInicial = parseNumRafk(unformatNumberRafk(montoInicial.value));
		}
		porcInicial.value = formatoRafk(txtPorcInicial, 2);
		montoInicial.value = formatoRafk(txtMontoInicial, 2);
         
        percent();
    }
	
    function reputacion(valor, tipo, most, tipo_cuenta_cliente) {
		if (valor == null) return true;
		var m = most || false;
		var obj = byId('cedula');
		if (obj == null){
			var obj = byId('capadatoscliente');
		}
		obj.style.background = valor;
		rep_val = valor; 
		rep_tipo = tipo;
		if (tipo != '' && most == true) {
			if (tipo_cuenta_cliente == 1) {
				alert("ATENCIÓN: Usted a Seleccionado un Prospecto");
			} else if (tipo_cuenta_cliente == 2) {
				alert("ATENCIÓN: Usted a Seleccionado un Cliente, el cual tiene una reputación de: " + tipo);
			} else {
				alert("ATENCIÓN: el cliente tiene una reputación de: " + tipo);
			}
		}
    }
        
    function validarEliminarAdicional(pos){
		var ac = acc[pos];
		if (confirm('Desea quitar ' + ac.nombre)){
			/*var accs = byId("accesorios");
			accs.removeChild(byId(ac.capa));*/
			var accs = byId(ac.capa);
			accs.style.display = "none";
			//delete acc[pos];
			acc[pos].accion = 2;
			var aca = byId('aca' + acc[pos].id);
			aca.value = acc[pos].accion;
			percent();
		}
	}
	
    function validarFrmGenerar(){
		if (validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtBuscarPresupuesto','t','') == true) {
			window.location.href="an_ventas_pedido_generar.php?txtBuscarPresupuesto=" + byId('txtBuscarPresupuesto').value + "&txtIdEmpresa=" + byId('txtIdEmpresa').value;
		} else {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtBuscarPresupuesto','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
    }
    
    function validarFrmPedido(){
        if (validar()) {
            var f = byId('frmPedido');
            f.submit();
        }
    }
	
	function validarFrmPermiso() {
		if (validarCampo('txtContrasena','t','') == true) {
			xajax_validarPermiso(xajax.getFormValues('frmPermiso'));
		} else {
			validarCampo('txtContrasena','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
    function validar(){// VERIFICA QUE HAYA ELEGIDO EL CLIENTE
        var o = byId('txtIdCliente');
        if (o.value == "") {
            p = 0;
            alert('No se ha especificado el cliente');
            byId('cedula').focus();
            return false;
        }
		
		if (!(validarCampo('lstAsesorVenta', 't', 'lista') == true
		&& validarCampo('txtPorcInicial', 't', 'numPositivo') == true
		&& validarCampo('txtMontoAnticipo', 't', 'numPositivo') == true
		&& validarCampo('lstGerenteVenta', 't', 'lista') == true
		&& validarCampo('txtFechaVenta', 't', 'fecha') == true
		&& validarCampo('lstGerenteAdministracion', 't', 'lista') == true
		&& validarCampo('txtFechaAdministracion', 't', 'fecha') == true
		&& validarCampo('txtFechaReserva', 't', 'fecha') == true
		&& validarCampo('txtFechaEntrega', 't', 'fecha') == true
		&& validarCampo('lstClaveMovimiento', 't', 'lista') == true)) {
			validarCampo('lstAsesorVenta', 't', 'lista');
			validarCampo('txtPorcInicial', 't', 'numPositivo');
			validarCampo('txtMontoAnticipo', 't', 'numPositivo');
			validarCampo('lstGerenteVenta', 't', 'lista');
			validarCampo('txtFechaVenta', 't', 'fecha');
			validarCampo('lstGerenteAdministracion', 't', 'lista');
			validarCampo('txtFechaAdministracion', 't', 'fecha');
			validarCampo('txtFechaReserva', 't', 'fecha');
			validarCampo('txtFechaEntrega', 't', 'fecha');
			validarCampo('lstClaveMovimiento', 't', 'lista');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
		
        // VERIFICA SI EL PEDIDO TIENE ADICIONALES AGREGADOS
        var frm = document.forms['frmPedido'];
        contAdicional = 0;
        for (i = 0; i < frm.length; i++) {
            var cadena = frm.elements[i].id;
            if (cadena.substr(0,6) == 'acaacc' && (frm.elements[i].value == 1 || frm.elements[i].value == 3)) {
                contAdicional++;
            }
        }
        
        if (contAdicional == 0 || byId('txtIdUnidadBasica').value > 0) {
            // VERIFICA QUE HAYA ELEGIDO UNA UNIDAD BASICA
            var uf = byId('frmPedido').elements['rbtUnidadFisica'];
            if (uf == null) {
                if (byId('txtIdUnidadBasica').value > 0) {
                    alert('Debe elegir una unidad básica que tenga unidades físicas disponibles');
                    
                    if (byId('modelo') != null) {
                        byId('modelo').focus();
                    }
                } else {
                    alert('Debe agregar al menos una unidad o un adicional para crear el pedido');
                }
                return false;
            } else {
                var check = false;
                if (uf.length != null) {
                    for (var i = 0; i < uf.length; i++){
                        if (uf[i].checked){
                            check = true;
                            break;
                        }
                    }
                } else {
                    check = uf.checked;
                }
                if (!check) {
                    alert("Debe elegir una Unidad de la lista");
                    return false;
                }
                
                var o = byId('txtPrecioVenta');
                if (o.value == "" || o.value == 0) {
                    p = 0;
                    alert('No se ha especificado el precio de venta');
                    o.focus();
                    return false;
                }
            }
        }
		
		if (byId('txtIdUnidadBasica').value > 0) {
			var o = byId('txtPorcInicial');
			if (formatoRafk(o.value, 2) > 100) {
				p = 0;
				alert('Cantidad de porcentaje incorrecto: '+formatoRafk(o.value, 2));
				o.focus();
				return false;		
			}
			
			if (byId('txtPorcInicial').value < 100 && !(byId('hddSinBancoFinanciar').value == 1)) {
				if (!(validarCampo('lstBancoFinanciar', 't', 'lista') == true
				/*&& validarCampo('lstMesesFinanciar', 't', 'lista') == true*/)) {
					validarCampo('lstBancoFinanciar', 't', 'lista');
					//validarCampo('lstMesesFinanciar', 't', 'lista');
					
					alert("Los campos señalados en rojo son requeridos");
					return false;
				}
			}
		}
        
        var a2 = byId("exacc2");
        var a3 = byId("exacc3");
        var a4 = byId("exacc4");
        var va2 = byId("vexacc2");
        var va3 = byId("vexacc3");
        var va4 = byId("vexacc4");
        var empresa_accesorio = byId("empresa_accesorio");
		
        if (a2.value != "" || va2.value != "") {
            if (a2.value == "") {
                if (parseNumRafk(va2.value) != 0) {
                    alert("No ha especificado el accesorio, para ignorar elimine el monto.");
                    a2.focus();
                    return false;
                }
            } else if (isNaN(parseNumRafk(va2.value)) || parseNumRafk(va2.value) <= 0) {
                alert("Valor incorrecto: "+va2.value);
                va2.focus();
                return false;
            }
        }
		
        if (a3.value != "" || va3.value != "") {
            if (a3.value == "") {
                if (parseNumRafk(va3.value) != 0) {
                    alert("No ha especificado el accesorio, para ignorar elimine el monto.");
                    a3.focus();
                    return false;
                }
            } else if (isNaN(parseNumRafk(va3.value)) || parseNumRafk(va3.value) <= 0) {
                alert("Valor incorrecto: "+va3.value);
                va3.focus();
                return false;
            }
        }
		
        if (a4.value != "" || va4.value != "") {
            if (a4.value == "") {
                if (parseNumRafk(va4.value) != 0) {
                    alert("No ha especificado el accesorio, para ignorar elimine el monto.");
                    a4.focus();
                    return false;
                }
            } else if (isNaN(parseNumRafk(va4.value)) || parseNumRafk(va4.value) <= 0) {
                alert("Valor incorrecto: "+va4.value);
                va4.focus();
                return false;
            }
        }
		
        return true;
    }
    
    function valfocus() {
        var ep_porcentaje = unformatNumberRafk(byId('txtPorcInicial').value);
        var txtMontoInicial = unformatNumberRafk(byId('txtMontoInicial').value);
        var txtPrecioVenta = byId('txtPrecioVenta');
        
        if ((parseNumRafk(txtMontoInicial) > parseNumRafk(txtPrecioVenta.value)) || (parseNumRafk(ep_porcentaje) > 100)) {
            alert("NOTA: la inicial supera el monto");
            byId('txtMontoInicial').focus();
        } else if (parseNumRafk(ep_porcentaje) < 0) {
            alert("NOTA: la inicial no puede ser inferior o igual a 0");
            ep_porcentaje.focus();		
        }
    }
    </script>
</head>

<body class="bodyVehiculos" <?php echo $loadscript;?>>
    <div id="listacliente" class="ajaxlist"></div>
    <div id="listavehiculo" class="ajaxlist"></div>
    <div id="listaaccesorio" class="ajaxlist"></div>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_vehiculos.php");?></div>
	
    <div id="divInfo" class="print">
    <form id="frmPedido" name="frmPedido" method="post">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaVehiculos">Pedido de Venta</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr id="trFrmPerdido">
        	<td>
                <input type="hidden" id="txtIdFactura" name="txtIdFactura"/>
                <input type="hidden" id="txtIdPedido" name="txtIdPedido"/>
                <input type="hidden" id="txtIdPresupuesto" name="txtIdPresupuesto"/>
                <input type="hidden" id="txtIdUnidadFisica" name="txtIdUnidadFisica"/>
                <input type="hidden" id="porcentaje_flat" name="porcentaje_flat"/>
                <input type="hidden" id="porcentaje_iva" name="porcentaje_iva"/>
                <input type="hidden" id="porcentaje_impuesto_lujo" name="porcentaje_impuesto_lujo"/>
                <input type="hidden" id="txtTotalPedido" name="txtTotalPedido"/>
                
                <table border="0" width="100%">
                <tr align="left">
                    <td></td>
                    <td></td>
                    <td align="right" class="tituloCampo">Nro. Pedido:</td>
                    <td><input type="text" id="txtNumeroPedido" name="txtNumeroPedido" readonly="readonly" style="text-align:center"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="15%">Empresa:</td>
                    <td width="45%">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/></td>
                            <td></td>
                            <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                    <td align="right" class="tituloCampo" width="15%">Nro. Presupuesto:</td>
                    <td width="25%"><input type="text" id="txtNumeroPresupuesto" name="txtNumeroPresupuesto" maxlength="50" readonly="readonly" style="text-align:center"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Cliente:</td>
                    <td>
                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr align="left">
                            <td><input type="text" id="txtIdCliente" name="txtIdCliente" readonly="readonly" size="6" style="text-align:right"/></td>
                            <td></td>
                            <td id="capadatoscliente" width="100%">
                                <input type="text" id="cedula" name="cedula" autocomplete="off" class="inputCompletoHabilitado" onkeyup="ku_buscarCliente(event,this.id);"/>
                                <input type="hidden" id="clientec" name="clientec"/>
                            </td>
                        </tr>
                        <tr align="center">
                            <td id="tdMsjCliente" colspan="3"></td>
                        </tr>
                        </table>
                        <input type="hidden" id="hddPagaImpuesto" name="hddPagaImpuesto"/>
                    </td>
                    <td align="right" class="tituloCampo">Asesor de Ventas:</td>
                    <td id="tdlstAsesorVenta"></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Unidad B&aacute;sica:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                            <td><input type="text" id="txtIdUnidadBasica" name="txtIdUnidadBasica" readonly="readonly" size="6" style="text-align:right"/></td>
                            <td id="tdUnidadBasica" width="100%">
                                <input type="text" id="modelo" name="modelo" autocomplete="off" class="inputCompletoHabilitado" onfocus="ku_buscarVehiculo(event,this.id);" onkeyup="ku_buscarVehiculo(event,this.id);"/>
                                <input type="hidden" id="modeloc" name="modeloc"/>
                            </td>
                        </tr>
                        </table>
                    </td>
                    <td align="right" class="tituloCampo"><label for="lstPrecioVenta">Actualizar Precio Venta:</label></td>
                    <td id="tdlstPrecioVenta"></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Clave:</td>
                    <td id="tdlstClaveMovimiento"></td>
                    <td id="tdMsjPedido" colspan="2"></td>
                </tr>
                <tr>
                    <td colspan="4">
                    <fieldset><legend class="legend">Unidades F&iacute;sicas Disponibles</legend>
                        <div id="divListaUnidadFisica" style="max-height:250px; overflow:auto; width:100%;"></div>
                    </fieldset>
                    </td>
                </tr>
                </table>
                
                <table border="0" width="100%">
                <tr>
                    <td colspan="2" class="tituloArea">Datos de la Operaci&oacute;n de Ventas</td>
                </tr>
                <tr>
                    <td valign="top" width="50%">
                    <fieldset id="fieldsetVentaUnidad"><legend class="legend">Venta de la Unidad</legend>
                        <table border="0" width="100%">
                        <tr align="right">
                            <td class="tituloCampo" width="30%">Precio Base:</td>
                            <td width="34%"></td>
                            <td width="6%" id="tdPrecioBaseMoneda"></td>
                            <td width="30%"><input type="text" id="txtPrecioBase" name="txtPrecioBase" class="inputCompletoHabilitado" onchange="asignarPrecio(); setFormatoRafk(this, 2);" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right;"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Descuento:</td>
                            <td></td>
                            <td id="tdDescuentoMoneda"></td>
                            <td><input type="text" id="txtDescuento" name="txtDescuento" class="inputCompletoHabilitado" onchange="asignarPrecio(); setFormatoRafk(this, 2);" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right;"/></td>
                        </tr>
                        <tr align="right" class="trResaltarTotal">
                            <td class="tituloCampo">
                                Precio Venta:
                                <br><span id="eviva" class="textoNegrita_10px"></span>
                                <input type="hidden" name="viva" id="viva" value="1"/>
                            </td>
                            <td></td>
                            <td id="tdPrecioVentaMoneda"></td>
                            <td><input type="text" id="txtPrecioVenta" name="txtPrecioVenta" class="inputSinFondo" readonly="readonly" style="text-align:right;"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo" nowrap="nowrap"><?php echo $spanInicial; ?>:
                                <input type="hidden" id="hddTipoInicial" name="hddTipoInicial">
                            </td>
                            <td>
                                <table border="0" cellpadding="0" cellspacing="0">
                                <tr align="right">
                                    <td valign="top">
                                        <input type="radio" id="rbtInicialPorc" name="rbtInicial" onclick="
                                        byId('hddTipoInicial').value = 0;
                                        byId('txtPorcInicial').readOnly = false;
                                        byId('txtMontoInicial').readOnly = true;
                                        byId('txtPorcInicial').className = 'inputHabilitado';
                                        byId('txtMontoInicial').className = 'inputInicial';" value="1"/>
                                    </td>
                                    <td><input type="text" id="txtPorcInicial" name="txtPorcInicial" onblur="setFormatoRafk(this, 2); percent2(); valfocus();" onkeypress="return validarSoloNumerosReales(event);" maxlength="5" size="6" style="text-align:right"/> %</td>
                                </tr>
                                </table>
                            </td>
                            <td id="tdMontoInicialMoneda"></td>
                            <td>
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr align="right">
                                    <td valign="top">
                                        <input type="radio" id="rbtInicialMonto" name="rbtInicial" onclick="
                                        byId('hddTipoInicial').value = 1;
                                        byId('txtPorcInicial').readOnly = true;
                                        byId('txtMontoInicial').readOnly = false;
                                        byId('txtPorcInicial').className = 'inputInicial';
                                        byId('txtMontoInicial').className = 'inputHabilitado';" value="2"/>
                                    </td>
                                    <td width="100%"><input type="text" id="txtMontoInicial" name="txtMontoInicial" class="inputCompleto" onblur="setFormatoRafk(this, 2); percent(); valfocus();" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right;"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="right" class="trResaltarTotal">
                            <td class="tituloCampo">Total <?php echo $spanInicial; ?>, Adicionales:</td>
                            <td></td>
                            <td id="tdTotalInicialGastosMoneda"></td>
                            <td><input type="text" id="txtTotalInicialGastos" name="txtTotalInicialGastos" class="inputSinFondo" readonly="readonly"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Saldo a Financiar:</td>
                            <td></td>
                            <td id="tdSaldoFinanciarMoneda"></td>
                            <td><input type="text" id="txtSaldoFinanciar" name="txtSaldoFinanciar" class="inputCompleto" readonly="readonly" style="text-align:right;"/></td>
                        </tr>
                        <tr align="right" class="trResaltarTotal">
                            <td class="tituloCampo">Total Otros Adicionales:</td>
                            <td></td>
                            <td id="tdTotalAdicionalContratoMoneda"></td>
                            <td><input type="text" id="txtTotalAdicionalContrato" name="txtTotalAdicionalContrato" class="inputSinFondo" readonly="readonly"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    
                    <fieldset id="fieldsetFormaPago"><legend class="legend">Forma de Pago</legend>
                        <table border="0" width="100%">
                        <tr align="right">
                            <td class="tituloCampo" width="30%"><?php echo $spanAnticipo; ?>:</td>
                            <td width="34%"></td>
                            <td id="tdMontoAnticipoMoneda" width="6%"></td>
                            <td width="30%"><input type="text" id="txtMontoAnticipo" name="txtMontoAnticipo" class="inputCompleto" onblur="setFormatoRafk(this, 2); percent();" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" style="text-align:right;"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Complemento <?php echo $spanInicial; ?>:</td>
                            <td></td>
                            <td id="tdMontoComplementoInicialMoneda"></td>
                            <td><input type="text" id="txtMontoComplementoInicial" name="txtMontoComplementoInicial" class="inputCompleto" readonly="readonly" style="text-align:right;"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Entidad Bancaria:</td>
                            <td colspan="3">
                                <table cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td id="tdlstBancoFinanciar"></td>
                                </tr>
                                <tr>
                                    <td>
                                        <table border="0" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><label id="lblSinBancoFinanciar"><input type="checkbox" id="cbxSinBancoFinanciar" name="cbxSinBancoFinanciar" onclick="xajax_asignarSinBancoFinanciar(xajax.getFormValues('frmPedido'));" value="1"/><?php echo $spanPedidoVentaSinBanco; ?></label></td>
                                            <td>&nbsp;</td>
                                            <td>
                                            <a class="modalImg" id="aDesbloquearSinBancoFinanciar" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblPermiso', 'an_pedido_venta_form_entidad_bancaria');">
                                                <img src="../img/iconos/lock_go.png" style="cursor:pointer" title="Desbloquear"/>
                                            </a>
                                            </td>
                                        </tr>
                                        </table>
                                        <input type="hidden" id="hddSinBancoFinanciar" name="hddSinBancoFinanciar"/>
                                    </td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="right" class="trResaltar5">
                            <td id="tdFinanciamiento" class="tituloCampo">Financiamiento:</td>
                            <td id="capameses_financiar">
                                <select type="text" name="lstMesesFinanciar" id="lstMesesFinanciar" onchange="percent();">
                                    <option value="">-</option>
                                    <?php echo $select;	?>
                                </select>
                            </td>
                            <td id="tdCuotasFinanciarMoneda"></td>
                            <td id="tdtxtCuotasFinanciar"><input type="text" id="txtCuotasFinanciar" name="txtCuotasFinanciar" class="inputCompleto" onchange="setFormatoRafk(this, 2);" readonly="readonly" style="text-align:right;"/></td>
                        </tr>
                        <tr id="trCuotasFinanciar2" align="right" class="trResaltar4" style="display:none">
                            <td id="capameses_financiar2"></td>
                            <td id="tdCuotasFinanciarMoneda2"></td>
                            <td id="tdtxtCuotasFinanciar2"><input type="text" id="txtCuotasFinanciar2" name="txtCuotasFinanciar2" class="inputCompleto" onchange="setFormatoRafk(this, 2);" readonly="readonly" style="text-align:right;"/></td>
                        </tr>
                        <tr id="trCuotasFinanciar3" align="right" class="trResaltar5" style="display:none">
                            <td id="capameses_financiar3"></td>
                            <td id="tdCuotasFinanciarMoneda3"></td>
                            <td id="tdtxtCuotasFinanciar3"><input type="text" id="txtCuotasFinanciar3" name="txtCuotasFinanciar3" class="inputCompleto" onchange="setFormatoRafk(this, 2);" readonly="readonly" style="text-align:right;"/></td>
                        </tr>
                        <tr id="trCuotasFinanciar4" align="right" class="trResaltar4" style="display:none">
                            <td id="capameses_financiar4"></td>
                            <td id="tdCuotasFinanciarMonedaFinal"></td>
                            <td id="tdtxtCuotasFinanciar4"><input type="text" id="txtCuotasFinanciar4" name="txtCuotasFinanciar4" class="inputCompleto" onchange="setFormatoRafk(this, 2);" readonly="readonly" style="text-align:right;"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Comisi&oacute;n FLAT (<span id="capaporcentaje_flat"></span>%):</td>
                            <td></td>
                            <td id="tdMontoFLATMoneda"></td>
                            <td><input type="text" id="txtMontoFLAT" name="txtMontoFLAT" class="inputCompleto" readonly="readonly" style="text-align:right;"/></td>
                        </tr>
                        <tr align="right" class="trResaltarTotal">
                            <td class="tituloCampo">Precio Total:</td>
                            <td></td>
                            <td id="tdPrecioTotalMoneda"></td>
                            <td><input type="text" id="txtPrecioTotal" name="txtPrecioTotal" class="inputSinFondo" readonly="readonly"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    
                    <fieldset><legend class="legend">Póliza de Seguro</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo">Compañía de Seguros:</td>
                            <td id="tdlstPoliza" colspan="3"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Nombre de la Agencia:</td>
                            <td colspan="3"><input type="text" id="txtNombreAgenciaSeguro" name="txtNombreAgenciaSeguro" class="inputCompletoHabilitado"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Dirección de la Agencia:</td>
                            <td colspan="3">
                            	<table width="100%">
                                <tr>
                                	<td colspan="4"><textarea id="txtDireccionAgenciaSeguro" name="txtDireccionAgenciaSeguro" class="inputHabilitado" rows="3" style="width:99%"></textarea></td>
                                </tr>
                                <tr align="right">
                                    <td class="tituloCampo" width="20%"><?php echo utf8_encode($spanCiudad); ?>:</td>
                                    <td width="30%"><input type="text" id="txtCiudadAgenciaSeguro" name="txtCiudadAgenciaSeguro" class="inputCompletoHabilitado"/></td>
                                    <td class="tituloCampo" width="20%">País:</td>
                                    <td width="30%"><input type="text" id="txtPaisAgenciaSeguro" name="txtPaisAgenciaSeguro" class="inputCompletoHabilitado"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Teléfono de la Agencia:</td>
                            <td colspan="3">
                            <div style="float:left">
                                <input type="text" name="txtTelefonoAgenciaSeguro" id="txtTelefonoAgenciaSeguro" class="inputHabilitado" size="16" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                            </div>
                            </td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Numero de Póliza:</td>
                            <td></td>
                            <td></td>
                            <td><input type="text" id="txtNumPoliza" name="txtNumPoliza" class="inputHabilitado"  style="text-align:center;"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo" width="30%">Precio del Seguro (Prima):</td>
                            <td width="34%"></td>
                            <td width="6%"></td>
                            <td width="30%"><input type="text" id="txtMontoSeguro" name="txtMontoSeguro" class="inputCompletoHabilitado" onblur="setFormatoRafk(this, 2);" onchange="percent();" style="text-align:right;"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Periodo:</td>
                            <td></td>
                            <td></td>
                            <td>
                            	<table border="0" cellpadding="0" cellspacing="0">
                                <tr align="right">
                                    <td>Meses:</td>
                                    <td><input type="text" id="txtPeriodoPoliza" name="txtPeriodoPoliza" class="inputHabilitado" size="6" style="text-align:center;"/></td>
                                </tr>
                                </table>
							</td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Deducible:</td>
                            <td></td>
                            <td></td>
                            <td><input type="text" id="txtDeduciblePoliza" name="txtDeduciblePoliza" class="inputCompletoHabilitado" onblur="setFormatoRafk(this, 2);" onchange="percent();" style="text-align:right;"/></td>
                        </tr>
                        <tr align="right">
                            <td align="right" class="tituloCampo">Fecha Efectividad:</td>
                            <td></td>
                            <td></td>
                    		<td><input type="text" id="txtFechaEfect" name="txtFechaEfect" class="inputHabilitado" autocomplete="off" size="10" style="text-align:center"/></td>
                        </tr>
                        <tr align="right">
                          <td align="right" class="tituloCampo">Fecha Expiracion:</td>
                          <td></td>
                          <td></td>                   		 
                   		  <td><input type="text" id="txtFechaExpi" name="txtFechaExpi" class="inputHabilitado" autocomplete="off" size="10" style="text-align:center"/></td>
                       </tr>
                        <tr align="right">
                            <td class="tituloCampo"><?php echo $spanInicial; ?>:</td>
                            <td></td>
                            <td></td>
                            <td><input type="text" id="txtInicialPoliza" name="txtInicialPoliza" class="inputCompletoHabilitado" onblur="setFormatoRafk(this, 2);" onchange="percent();" style="text-align:right;"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Cuotas:</td>
                            <td>
                                <table border="0" cellpadding="0" cellspacing="0">
                                <tr align="right">
                                    <td>Meses:</td>
                                    <td><input type="text" id="txtMesesPoliza" name="txtMesesPoliza" class="inputHabilitado" size="6" style="text-align:center;"/></td>
                                </tr>
                                </table>
                            </td>
                            <td></td>
                            <td>
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr align="right">
                                    <td>Monto:</td>
                                    <td width="100%"><input type="text" id="txtCuotasPoliza" name="txtCuotasPoliza" class="inputCompletoHabilitado" onblur="setFormatoRafk(this, 2);" onchange="percent();" style="text-align:right;"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="left" style="display:none">
                            <td align="right" class="tituloCampo">Cheque a Nombre de:</td>
                            <td id="cheque_poliza" colspan="3" height="22"></td>
                        </tr>
                        <tr align="left" style="display:none">
                            <td align="right" class="tituloCampo">Financiada:</td>
                            <td id="financiada" colspan="3" height="22"></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                    <td valign="top" width="50%">
                    <fieldset id="fieldsetAdicional"><legend class="legend">Adicionales</legend>
                        <table width="100%">
                        <tr>
                            <td>
                                <div id="accesorios">
                                    <div class="campo">
                                        <table width="100%">
                                        <tr align="left">
                                            <td align="right" class="tituloCampo" width="30%"><label for="addacc" accesskey="a" onclick="helptip('addacc','helpacc');">Buscar Adicional:</label></td>
                                            <td width="70%"><input type="text" id="addacc" name="addacc" autocomplete="off" onblur="closetooltip('toolacc');" onfocus="tooltip(this,'toolacc',5); ku_buscarAdicional(event, this.id);" onkeyup="ku_buscarAdicional(event, this.id);" style="width:99%;"/></td>
                                            <td><a href="javascript:verif('addacc'); buscarAdicional('addacc');" title="Buscar Adicional"><img border="0" src="../img/iconos/ico_buscar.png" alt="Buscar Adicional"/></a></td>
                                        </tr>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                    
                    <fieldset><legend class="legend">Presupuesto Accesorios</legend>
                        <table width="100%">
                        <tr>
                            <td valign="top">
                                <table border="0" width="100%">
                                <tr align="right">
                                    <td class="tituloCampo" width="30%">Subtotal:</td>
                                    <td width="70%">
                                        <table>
                                        <tr align="right">
                                            <td id="tdvexacc1Moneda"></td>
                                            <td><input type="text" id="vexacc1" name="vexacc1" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr align="right">
                                    <td class="tituloCampo">Impuesto:</td>
                                    <td>
                                        <table>
                                        <tr align="right">
                                            <td id="tdTotalImpuestoAccesorioMoneda"></td>
                                            <td><input type="text" id="txtTotalImpuestoAccesorio" name="txtTotalImpuestoAccesorio" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr align="right" class="trResaltarTotal">
                                    <td class="tituloCampo">Total:</td>
                                    <td>
                                        <table>
                                        <tr align="right">
                                            <td id="tdTotalPresupuestoAccesorioMoneda"></td>
                                            <td><input type="text" id="txtTotalPresupuestoAccesorio" name="txtTotalPresupuestoAccesorio" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                        
                        <table width="100%">
                        <tr>
                            <td class="tituloCampo">Observaciones</td>
                        </tr>
                        <tr>
                            <td><textarea id="observaciones" name="observaciones" class="inputHabilitado" rows="4" style="width:99%"></textarea></td>
                        </tr>
                        </table>
                        
                        <table border="1" style="visibility:hidden" width="100%">
                        <tr align="right">
                            <td width="20%"><input type="text" id="exacc2" name="exacc2" style="width:99%"/></td>
                            <td width="30%"><input type="text" id="vexacc2" name="vexacc2" onchange="setFormatoRafk(this, 2); percent();" style="width:99%"/></td>
                            <td width="20%"><input type="text" id="exacc3" name="exacc3" style="width:99%"/></td>
                            <td width="30%"><input type="text" id="vexacc3" name="vexacc3" onchange="setFormatoRafk(this, 2); percent();" style="width:99%"/></td>
                        </tr>
                        <tr align="right">
                            <td><input type="text" id="exacc4" name="exacc4" style="width:99%"/></td>
                            <td><input type="text" id="vexacc4" name="vexacc4" onchange="setFormatoRafk(this, 2); percent();" style="width:99%"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Total Accesorios:</td>
                            <td><input type="text" id="txtTotalAccesorio" name="txtTotalAccesorio" readonly="readonly" style="width:99%"/></td>
                            <td class="tituloCampo">Cheque a Favor de:</td>
                            <td><input type="text" id="empresa_accesorio" name="empresa_accesorio" style="width:99%"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
                
                <table border="0" width="100%">
                <tr>
                    <td width="14%"></td>
                    <td width="16%"></td>
                    <td width="10%"></td>
                    <td width="10%"></td>
                    <td width="14%"></td>
                    <td width="36%"></td>
                </tr>
                <tr align="center">
                    <td colspan="4" class="tituloArea">Comprobaci&oacute;n / Validaci&oacute;n del Pedido</td>
                    <td colspan="2" class="tituloArea">Caracteristicas del Autom&oacute;vil Asignado</td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Gerente Ventas:</td>
                    <td id="tdlstGerenteVenta"></td>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha:</td>
                    <td><input type="text" id="txtFechaVenta" name="txtFechaVenta" autocomplete="off" size="10" style="text-align:center"/></td>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Reserva Venta:</td>
                    <td><input type="text" id="txtFechaReserva" name="txtFechaReserva" autocomplete="off" size="10" style="text-align:center"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Gerente Administración:</td>
                    <td id="tdlstGerenteAdministracion"></td>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha:</td>
                    <td valign="middle"><input type="text" id="txtFechaAdministracion" name="txtFechaAdministracion" autocomplete="off" size="10" style="text-align:center"/></td>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha de Entrega:</td>
                    <td id="fet"><input type="text" id="txtFechaEntrega" name="txtFechaEntrega" autocomplete="off" size="10" style="text-align:center"/></td>
                </tr>
                <tr style="display:none">
                    <td colspan="4" class="tituloArea">Retoma Veh&iacute;culo Usado</td>
                </tr>
                <tr align="left" style="display:none">
                    <td align="right" class="tituloCampo">Precio Retoma:</td>
                    <td><input type="text" id="txtPrecioRetoma" name="txtPrecioRetoma" maxlength="50" onchange="percent();" onblur="setFormatoRafk(this, 2);" style="width:90%;"/></td>
                    <td align="right" class="tituloCampo">Fecha Retoma:</td>
                    <td><input type="text" id="txtFechaRetoma" name="txtFechaRetoma" autocomplete="off" size="10" style="text-align:center"/></td>
                </tr>
                <tr>
                    <td align="right" colspan="8"><hr>
                        <button type="button" value="Guardar" onclick="validarFrmPedido();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
                        <button type="button" value="Cancelar" onclick="window.location.href='an_pedido_venta_list.php';"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr id="trBuscarPerdido">
        	<td>
            	<table align="center">
                <tr>
                	<td align="right" class="tituloCampo" width="120">Nro. Presupuesto:</td>
                    <td><input type="text" id="txtBuscarPresupuesto" name="txtBuscarPresupuesto" maxlength="50" onkeyup="ku_generar(event);" style="text-align:center"/></td>
                </tr>
                <tr>
                	<td colspan="2"><button type="button" onclick="validarFrmGenerar();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/plus.png"/></td><td>&nbsp;</td><td>Generar Pedido</td></tr></table></button></td>
                </tr>
                </table>
			</td>
        </tr>
        </table>
        <input type="hidden" id="hddTipoPedido" name="hddTipoPedido"/>
    </form>
    </div>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>

<form id="frmPermiso" name="frmPermiso" onsubmit="return false;" style="margin:0px">
	<table border="0" id="tblPermiso" style="display:none" width="560">
    <tr>
        <td>
            <table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="25%">Acción:</td>
                <td width="75%"><input type="text" id="txtDescripcionPermiso" name="txtDescripcionPermiso" class="inputSinFondo" readonly="readonly" style="text-align:left"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Ingrese Clave:</td>
                <td>
                    <input type="password" id="txtContrasena" name="txtContrasena" size="30"/>
                    <input type="hidden" id="hddModulo" name="hddModulo" readonly="readonly" size="30"/>
                </td>
            </tr>
        	</table>
		</td>
	</tr>
	<tr>
		<td align="right"><hr>
			<button type="submit" id="btnGuardarPermiso" name="btnGuardarPermiso" onclick="validarFrmPermiso();">Aceptar</button>
			<button type="button" id="btnCancelarPermiso" name="btnCancelarPermiso" class="close">Cancelar</button>
			</td>
		</tr>
	</table>
</form>
</div>    

<script>
byId('txtFechaVenta').className = "inputHabilitado";
byId('txtFechaAdministracion').className = "inputHabilitado";
byId('txtFechaRetoma').className = "inputHabilitado";
byId('txtFechaReserva').className = "inputHabilitado";
byId('txtFechaEntrega').className = "inputHabilitado";

byId('hddTipoPedido').value = "<?php echo $_GET['vw']; ?>";

jQuery(function($){
	$("#txtFechaVenta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	$("#txtFechaAdministracion").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	$("#txtFechaRetoma").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	$("#txtFechaReserva").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	$("#txtFechaEntrega").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	$("#txtFechaEfect").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	$("#txtFechaExpi").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
});

new JsDatePick({
	useMode:2,
	target:"txtFechaVenta",
	dateFormat:"<?php echo spanDatePick; ?>", 
	cellColorScheme:"orange"
});

new JsDatePick({
	useMode:2,
	target:"txtFechaAdministracion",
	dateFormat:"<?php echo spanDatePick; ?>", 
	cellColorScheme:"orange"
});

new JsDatePick({
	useMode:2,
	target:"txtFechaRetoma",
	dateFormat:"<?php echo spanDatePick; ?>", 
	cellColorScheme:"orange"
});

new JsDatePick({
	useMode:2,
	target:"txtFechaReserva",
	dateFormat:"<?php echo spanDatePick; ?>", 
	cellColorScheme:"orange"
});

new JsDatePick({
	useMode:2,
	target:"txtFechaEntrega",
	dateFormat:"<?php echo spanDatePick; ?>", 
	cellColorScheme:"orange"
});

new JsDatePick({
	useMode:2,
	target:"txtFechaEfect",
	dateFormat:"<?php echo spanDatePick; ?>", 
	cellColorScheme:"orange"
});

new JsDatePick({
	useMode:2,
	target:"txtFechaExpi",
	dateFormat:"<?php echo spanDatePick; ?>", 
	cellColorScheme:"orange"
});

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

xajax_formPedido('<?php echo $_GET['txtIdEmpresa']; ?>', '<?php echo $_GET['txtBuscarPresupuesto']; ?>', '<?php echo $_GET['idPresupuesto']; ?>', '<?php echo $_GET['id']; ?>', '<?php echo $_GET['idFactura']; ?>', xajax.getFormValues('frmPedido'));
</script>