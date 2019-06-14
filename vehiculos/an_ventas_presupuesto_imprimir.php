<?php
require_once "../connections/conex.php";

@session_start();

/* Validación del Módulo */
require_once("../inc_sesion.php");
if (!isset($_GET['id'])) {
	validaModulo("an_presupuesto_venta_list",insertar);
}
/* Fin Validación del Módulo */

function viewmode(){
	return ($_GET['view']=="");
}

function onlyviewmode(){
	if ($_GET['view']=="print"){
		return false;
	} else {
		return true;
	}
}

function blockprint($v){
	echo ' value="'.htmlentities($v).'"';
	if (!viewmode()){
		echo ' readonly="readonly" ';
	}
}

function blockselect($name,$default,$sqli,$sqlfalse,$events=""){
	if (!viewmode()){
		conectar();
		echo htmlentities(getmysql($sqlfalse));
	} else {
		echo '<select name="'.$name.'" id="'.$name.'" '.$events.' ><option value="">[ Seleccione ]</option>';
		generar_select($default,$sqli);
		echo '</select>';
	}
}

conectar();
// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT *,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM vw_iv_empresas_sucursales vw_iv_emp_suc
WHERE vw_iv_emp_suc.id_empresa_reg = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp, $conex) or die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

(strlen($rowEmp['telefono1']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono1'] : "";
(strlen($rowEmp['telefono2']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono2'] : "";
(strlen($rowEmp['telefono_taller1']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono_taller1'] : "";
(strlen($rowEmp['telefono_taller2']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono_taller2'] : "";
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<!--para compatibilidad de campos con acentos: iso-8859-1 -->
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Nuevo Presupuesto</title>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
    
	<link href="an_ventas_presupuesto_insertar_style.css" rel="stylesheet"/>
	<script src="anajax.js"></script>
	<script src="alpha.js"></script>
    <script src="vehiculos.inc.js"></script>
    
    <script language="javascript" type="text/javascript">
	var rep_val;
	var rep_tipo;
	function reputacion(valor, tipo, most, tipo_cuenta_cliente) {
		if(valor == null) return true;
		var m = most || false;
		var obj = document.getElementById('cedula');
		rep_val = valor; 
		rep_tipo = tipo;
		obj.style.background = valor;
		if (tipo_cuenta_cliente == 2) {
			if (tipo != '' && most){
				utf8alert("ATENCI&Oacute;N: Usted a Seleccionado un Cliente, el cual tiene una reputaci&oacute;n de: " + tipo);
			}
		} else if (tipo_cuenta_cliente == 1) {
			utf8alert("ATENCI&Oacute;N: Usted a Seleccionado un Prospecto");
		}
	}
	
	function imprimir(){
		var obj = objeto('txtIdPresupuesto');
		setpopup("an_ventas_presupuesto_editar.php?view=print&id="+obj.value,"viewp",1050,600);
	}
	
	function guardar(){
		var f = objeto('presupuesto');
		var mode = objeto('mode');
		if (validar()) {
			mode.value=1;
			f.submit();
		}
	}
	
	function validar(){
		if (!validarReputacion(rep_val, rep_tipo)){
			return false;
		}
		var p=1;
		var cedula = objeto('txtIdCliente');
		if (cedula.value=="") {
			alert('No se ha seleccionado un cliente');
			var cedula = objeto('cedula');
			cedula.focus();
			p=0;
			return false;
		}
		var vehiculo = objeto('txtIdUnidadBasica');
		if (vehiculo.value=="") {
			alert('No se ha asignado un Vehículo');
			objeto('modelo').focus();
			p=0;
			return false;
		}
		var pv = objeto('precio_venta');
		if (pv.value=="") {
			alert('No ha especificado el precio de venta');
			pv.focus();
			p=0;
			return false;
		} else if (isNaN(parsenumstring(pv.value))){
			alert('Valor Incorrecto en precio de venta');
			pv.focus();
			p=0;
			return false;
		}
			
		var pv = objeto('eporcentaje_inicial');
		//alert(((pv.value!="") && !isNaN(pv.value)) +" "+parsenum(pv.value));return false;
		if ((pv.value=="") || isNaN(pv.value)) {
			alert('No ha especificado el porcentaje de venta');
			pv.focus();
			p=0;
			return false;
		}
		if((pv.value<0) || (pv.value>100)){
			alert('Cantidad de porcentaje incorrecta: '+pv.value);
			pv.focus();
			p=0;
			return false;		
		}
		if(pv.value!=100){
			var o = objeto('lstBancoFinanciar');
			if (o.value=="") {
				alert('No se ha especificado entidad bancaria');
				//var cedula = objeto('cedula');
				o.focus();
				p=0;
				return false;
			}
			var o = objeto('lstMesesFinanciar');
			if (o.value=="") {
				alert('No se ha especificado mes');
				//var cedula = objeto('cedula');
				o.focus();
				p=0;
				return false;
			}
			var o = objeto('txtCuotasFinanciar');
			if (o.value=="") {
				alert('No se ha especificado');
				//var cedula = objeto('cedula');
				o.focus();
				p=0;
				return false;
			}
		}
		
		/*
		var o = objeto('id_poliza');
		if (o.value=="") {
			alert('No se ha especificado poliza');
			//var cedula = objeto('cedula');
			o.focus();
			p=0;
			return false;
		}
		var o = objeto('inicial_poliza');
		if (o.value=="") {
			alert('No se ha especificado inicial poliza');
			//var cedula = objeto('cedula');
			o.focus();
			p=0;
			return false;
		}
		var o = objeto('cuotas_poliza');
		if (o.value=="") {
			alert('No se ha especificado cuotas poliza');
			//var cedula = objeto('cedula');
			o.focus();
			p=0;
			return false;
		}
		var pv = objeto('meses_poliza');
		//alert(((pv.value!="") && !isNaN(pv.value)) +" "+parsenum(pv.value));return false;
		if ((pv.value=="") || isNaN(pv.value)) {
			alert('No ha especificado los meses de la poliza');
			pv.focus();
			p=0;
			return false;
		}
		var pv = objeto('monto_seguro');
		if (pv.value=="") {
			alert('No ha especificado el monto seguro');
			pv.focus();
			p=0;
			return false;
		} else if (isNaN(parsenumstring(pv.value))){
			alert('Valor Incorrecto en monto seguro');
			pv.focus();
			p=0;
			return false;
		}
		*/
		var a1 = document.getElementById("exacc1");
		var a2 = document.getElementById("exacc2");
		var a3 = document.getElementById("exacc3");
		var a4 = document.getElementById("exacc4");
		var va1 = document.getElementById("vexacc1");
		var va2 = document.getElementById("vexacc2");
		var va3 = document.getElementById("vexacc3");
		var va4 = document.getElementById("vexacc4");
		var empresa_accesorio = document.getElementById("empresa_accesorio");
		//alert((parsenum(va1.value)<=0));
		if (a1.value!="" || va1.value!=""){
			
			if (a1.value==""){
				if (parsenum(va1.value)!=0){
				alert("No ha especificado el accesorio, para ignorar elimine el monto.");
				a1.focus();
				return false;}
			}else
			if (isNaN(parsenum(va1.value)) || parsenum(va1.value)<=0){
				alert("Valor incorrecto: "+va1.value);
				va1.focus();
				return false;
			}
		}
		if (a2.value!="" || va2.value!=""){
			if (a2.value==""){
				if (parsenum(va2.value)!=0){
				alert("No ha especificado el accesorio, para ignorar elimine el monto.");
				a2.focus();
				return false;}
			}else
			if (isNaN(parsenum(va2.value)) || parsenum(va2.value)<=0){
				alert("Valor incorrecto: "+va2.value);
				va2.focus();
				return false;
			}
		}
		if (a3.value!="" || va3.value!=""){
			if (a3.value==""){
				if (parsenum(va3.value)!=0){
				alert("No ha especificado el accesorio, para ignorar elimine el monto.");
				a3.focus();
				return false;}
			}else
			if (isNaN(parsenum(va3.value)) || parsenum(va3.value)<=0){
				alert("Valor incorrecto: "+va3.value);
				va3.focus();
				return false;
			}
		}
		if (a4.value!="" || va4.value!=""){
			if (a4.value==""){
				if (parsenum(va4.value)!=0){
				alert("No ha especificado el accesorio, para ignorar elimine el monto.");
				a4.focus();
				return false;}
			}else
			if (isNaN(parsenum(va4.value)) || parsenum(va4.value)<=0){
				alert("Valor incorrecto: "+va4.value);
				va4.focus();
				return false;
			}
		}
		if ((a1.value!="" || a2.value!="" || a3.value!="" || a4.value!="") && empresa_accesorio.value=="") {
			alert("No ha especificado la entidad en cheque a favor de los accesorios.");
			empresa_accesorio.focus();
			return false;
		}
		
		var o = objeto('asesor_ventas');
		if (o.value=="") {
			alert('No se ha especificado el asesor');
			//var cedula = objeto('cedula');
			o.focus();
			p=0;
			return false;
		}
		
		/*var pv = objeto('monto_flat');
		if (pv.value=="") {
			alert('No ha especificado Comision monto_flat');
			pv.focus();
			p=0;
			return false;
		} else if (isNaN(parsenumstring(pv.value))){
			alert('Valor Incorrecto en Comision monto_flat');
			pv.focus();
			p=0;
			return false;
		}*/
		
		//alert("Pendiente: validaciones, demás campos y almacenar en base de datos");
		if (p==0){
			return false;
		}
		return true;
	}
	
	// PENDIENTE: UBICAR ESTOS FACTORES EN UNA TABLA
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
				// round(floatval($txtSaldoFinanciar) * floatval($rowFactor['factor']), 2) == round($txtCuotasFinanciar, 2) && 
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

	/*=null;={6:0.17867646,
	12:0.09470484,
	18:0.06684881,
	24:0.05302095,
	30:0.04480355,
	36:0.03939054,
	48:0.03276791,
	60:0.02894236};*/
	
	function redondear(cantidad, decimales) {
		var cantidad = parseFloat(cantidad);
		var decimales = parseFloat(decimales);
		decimales = (!decimales ? 2 : decimales);
		return Math.round(cantidad * Math.pow(10, decimales)) / Math.pow(10, decimales);
	} 
	
	function valfocus(){
		var ep_porcentaje=objeto('eporcentaje_inicial');
		var p_inicial=objeto('p_inicial');
		var precio_venta=objeto('precio_venta');
		if((parsenum(p_inicial.value)>parsenum(precio_venta.value)) || (parsenum(ep_porcentaje.value)>100)){
			utf8alert("NOTA: la inicial supera el monto");
			p_inicial.focus();
		} else if (parsenum(ep_porcentaje.value)<0){
			utf8alert("NOTA: la inicial no puede ser inferior o igual a 0");
			ep_porcentaje.focus();		
		}
	}
	
	//busqueda AJAX Cliente
	function buscarcliente(campo){
		var _obj=objeto(campo);
		//_obj.disabled=false;
		_obj.readOnly=false;
		if (_obj.value=="") {
			var lista= document.getElementById("listacliente");
			lista.style.visibility="hidden";
			_obj.focus();
			return;
		}
		var a= new Ajax();
		//a.loading=carga;
		//a.error=er;
		a.load= function(texto){
			var lista= document.getElementById("listacliente");
			lista.style.visibility="visible";
			var obj= document.getElementById("cedula");
			lista.style.left=getOffsetLeft(obj)+"px";
			lista.style.top=getOffsetTop(obj)+"px";
			lista.style.margin=obj.offsetHeight+"px 0px 0px 0px";
			lista.innerHTML=texto;
		};
		a.sendget("an_ventas_presupuesto_ajax.php","ajax_cedula="+_obj.value,false);
	}
	
	var lp=-1;
	function ku_buscarcliente(e,obj){
		var slista= document.getElementById("overclientes");
		var tecla = (document.all) ? e.keyCode : e.which;
		if (tecla==40 || tecla==38){
			if (slista==null){ 
				return;
			}
			if (tecla==40){
				lp++;
			} else {
				lp--;
			}
			if(lp >= slista.childNodes.length){
				lp=0;
			} else if (lp<=-1){
				lp=slista.childNodes.length-1;
			}
			for (i=0;i<slista.childNodes.length;i++){
				if (lp!=i){
					var item = slista.childNodes.item(i);
					if (item.lastcolor!=null)
						item.style.background=item.lastcolor;
				}
			}
			var item = slista.childNodes.item(lp);
			if (item!=null)
				item.style.background="#FFCC66";
		} else if ((tecla==13) || ((objeto(obj).value.toString().length>0) && (objeto(obj).readOnly==false))){
			if ((lp!=-1) && (tecla==13)){
				var item = slista.childNodes.item(lp);
				cargarXML("cliente", item.accion, '');
			} else {
				buscarcliente(obj);
			}
			lp=-1;
		}
	}

	/*function cargarcliente(id){
		var obj= document.getElementById("txtIdCliente");
		obj.value=id;
		var obj= document.getElementById("presupuesto");
		//if (id==""){
		//	var ced= document.getElementById("cedula");
		//	obj.value="+"+ced.value;
		//}
		obj.submit();
	}*/

	//Busqueda AJAX vehiculo:
	function buscarvehiculo(campo){
		var _obj=objeto(campo);
		//_obj.disabled=false;
		_obj.readOnly=false;
		if (_obj.value=="") {
			var lista= document.getElementById("listavehiculo");
			lista.style.visibility="hidden";
			_obj.focus();
			return;
		}
		var a= new Ajax();
		//a.loading=carga;
		//a.error=er;
		a.load= function(texto){
			var lista= document.getElementById("listavehiculo");
			
			lista.style.visibility="visible";
			var obj= document.getElementById("modelo");
			lista.style.left=getOffsetLeft(obj)+"px";
			lista.style.top=getOffsetTop(obj)+"px";
			lista.style.margin=obj.offsetHeight+"px 0px 0px 0px";
			lista.innerHTML=texto;
		};
		a.sendget("an_ventas_presupuesto_ajax.php","ajax_vehiculo=" + _obj.value
			+ "&idEmpresa=" + objeto('txtIdEmpresa').value
			+ "&idCliente=" + objeto('txtIdCliente').value,false);
	}
	function ku_buscarvehiculo(e,obj){
		//if (e.keyCode==13){
		/*if ((e.keyCode==13) || ((objeto(obj).value.toString().length>0) && (objeto(obj).readOnly==false))){
			buscarvehiculo(obj);
		}*/
		var slista= document.getElementById("overvehiculo");
		var tecla = (document.all) ? e.keyCode : e.which;
		if (tecla==40 || tecla==38){
			if (slista==null){ 
				return;
			}
			if (tecla==40){
				lp++;
			} else {
				lp--;
			}
			if(lp >= slista.childNodes.length){
				lp=0;
			} else if (lp<=-1){
				lp=slista.childNodes.length-1;
			}
			for (i=0;i<slista.childNodes.length;i++){
				if (lp!=i){
					var item = slista.childNodes.item(i);
					if (item.lastcolor!=null)
						item.style.background=item.lastcolor;
				}
			}
			var item = slista.childNodes.item(lp);
			if (item!=null)
				item.style.background="#FFCC66";
		} else if ((tecla==13) || ((objeto(obj).value.toString().length>0) && (objeto(obj).readOnly==false))){
			if ((lp!=-1) && (tecla==13)){
				var item = slista.childNodes.item(lp);
				cargarXML("vehiculo", item.accion, '');
			} else {
				buscarvehiculo(obj);
			}
			lp=-1;
		}
	}
	
	function ku_buscaraccesorio(e,obj){
		//if (e.keyCode==13){
		if ((e.keyCode==13) || ((objeto(obj).value.toString().length>0) && (objeto(obj).readOnly==false))){
			buscarAdicional(obj);
		}
	}
	
	//funciones ACCESORIOS:
	var acc=[];
	
	//objeto accesotrio para la colección
	function accesorio(aid,apq,avalue,aname,vaccion){
		this.iddet = "null";
		this.id = aid;
		this.pq = apq;
		this.nombre = aname; // Descripcion Adicional
		this.civa = "";
		this.piva = "";
		this.iva = "";
		this.value = avalue; // Precio Adicional + Iva
		this.hddTipoAccesorio = "";
		this.accion = vaccion; // 1 = Agregar, 2 = Eliminar, 3 = Modificar
		this.capa = null;
		this.inbase = false;
	}
	
	function newacc(aid, apq, avalue, aname, vaccion, iva, civa, piva, hddTipoAccesorio, cbxCondicion, iddet) {
		var na = new accesorio(aid, apq, avalue, aname, vaccion);
		na.iddet = iddet;
		na.civa = civa;
		na.piva = piva;
		na.iva = iva;
		na.hddTipoAccesorio = hddTipoAccesorio;
		na.cbxCondicion = cbxCondicion;
		na.inbase = true;
		insertarItemAdicional(na);
	}
	
	function asignarBanco(idBanco, valores){
		loadxml("an_ventas_presupuesto_ajax.php", 'banco', idBanco, '', '', valores);
	}
	
	function asignarPoliza(idPoliza){
		loadxml("an_ventas_presupuesto_ajax.php", 'poliza', idPoliza, '', '');
	}
	
	function asignarPrecio(obj){
		if (obj > 0) {
			var txtPrecioBase = objeto('precio' + obj).innerHTML;
		} else {
			var txtPrecioBase = objeto('txtPrecioBase').value;
		}
		var txtDescuento = objeto('txtDescuento');
		var precioVentaNeto = parsenum(txtPrecioBase) - parsenum(txtDescuento.value);
		
		var txtPorcIva = parsenum(objeto('porcentaje_iva').value);
		if (txtPorcIva > 0 && objeto('hddPagaImpuesto').value == 1) {
			montoIva = (precioVentaNeto * txtPorcIva / 100);
		} else {
			objeto('eviva').innerHTML = "Exento";
			txtPorcIva = 0;
			var montoIva = 0;
		}
		
		var txtPorcIvaLujo = parsenum(objeto('porcentaje_impuesto_lujo').value);
		if (txtPorcIvaLujo > 0 && objeto('hddPagaImpuesto').value == 1) {
			montoIvaLujo = (precioVentaNeto * txtPorcIvaLujo / 100);
		} else {
			txtPorcIvaLujo = 0;
			var montoIvaLujo = 0;
		}
		precioVenta = precioVentaNeto + montoIva + montoIvaLujo;
		
		objeto('txtPrecioBase').value = formato(txtPrecioBase);
		objeto('porcentaje_iva').value = txtPorcIva;
		objeto('porcentaje_impuesto_lujo').value = txtPorcIvaLujo;
		objeto('precio_venta').value = formato(precioVenta);
		
		percent();
		objeto('porcentaje_inicial').focus();
		
		cancelarListaPrecio();
		closetooltip('toolprecioventa');
	}
	
	//Busqueda AJAX accesorios:
	function buscarAdicional(campo){
		var _obj=objeto(campo);
		//_obj.disabled=false;
		_obj.readOnly=false;
		if (_obj.value=="") {
			var lista= document.getElementById("listaaccesorio");
			lista.style.visibility="hidden";
			_obj.focus();
			return;
		}
		var a= new Ajax();
		//a.loading=carga;
		//a.error=er;
		a.load= function(texto){
			var lista= document.getElementById("listaaccesorio");
			lista.style.visibility="visible";
			var obj= document.getElementById("addacc");
			lista.style.left=getOffsetLeft(obj)+"px";
			lista.style.top=getOffsetTop(obj)+"px";
			lista.style.margin=obj.offsetHeight+"px 0px 0px 0px";
			lista.innerHTML=texto;
			lista.focus();
			_obj.focus();
		};
		a.sendget("an_ventas_presupuesto_ajax.php","ajax_acc="+_obj.value,false);
	}
	
	function cancelarAdicional() {
		var lista = document.getElementById("listaaccesorio");
		lista.style.visibility = "hidden";
		idlista = 0;
		/*var obj1 = document.getElementById("modeloc");
		var obj2 = document.getElementById("modelo");
		if (obj1.value != "") {
			obj1.value = "";
		}*/
	}
	
	function cancelarCliente() {
		var lista = document.getElementById("listacliente");
		lista.style.visibility = "hidden";
		//idlista = 0;
		
		var obj1 = document.getElementById("clientec");
		var obj2 = document.getElementById("cedula");
		//if (obj1.value != "") {
			obj2.value = obj1.value;
		//}
		//var obj3 = document.getElementById("nombre");
		//obj3.focus();
	}
	
	function cancelarListaPrecio(){
		//var lista= document.getElementById("listaprecios");
		//lista.style.visibility="hidden";
		alphahide("listaprecios",10,1);
	}
	
	function cancelarVehiculo() {
		var lista = document.getElementById("listavehiculo");
		lista.style.visibility = "hidden";
		idlista = 0;
		var obj1 = document.getElementById("modeloc");
		var obj2 = document.getElementById("modelo");
		//if (obj1.value != "") {
			obj2.value = obj1.value;
		//}
	}
	
	function cargarXML(cmd, id, idEmpresa, idCliente){
		loadxml("an_ventas_presupuesto_ajax.php", cmd, id, idEmpresa, idCliente);
	}
	
	function insertarAdicional(aid, avalue, aname, iva, civa, piva, hddTipoAccesorio) {
		var inc = true;
		for (var j = 0; j < acc.length; j++) {
			if (acc[j] != null) {
				if (acc[j].id == aid) {
					if (acc[j].accion == 2) {
						var accs = document.getElementById(acc[j].capa);
						if (acc[j].inbase == true) {
							acc[j].accion = 3;
						} else {
							acc[j].accion = 1;
						}
						var aca = document.getElementById('aca'+acc[j].id);
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
			var add = new accesorio(aid,'',avalue,aname,1);
			add.civa = civa;
			add.piva = piva;
			add.iva = iva;
			add.hddTipoAccesorio = hddTipoAccesorio;
			insertarItemAdicional(add);
			//cancelarAdicional();
		}
	}
	
	function insertarPaquete(idp){
		var f = document.getElementById("paq" + idp);
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
									var accs = document.getElementById(acc[j].capa);
									accs.style.display = "inline";
									if (acc[j].inbase == true) {
										acc[j].accion = 3;
									} else {
										acc[j].accion = 1;
									}
									var aca = document.getElementById('aca'+acc[j].id);
									aca.value = acc[j].accion;
									inc = false;
									cancelarAdicional();
									percent();
									break;
								}
								msg += acc[j].nombre+" ya está incluido\n";
								inc = false;
								break;
							}
						}
					}
					
					if (inc) {
						var na = new accesorio(e.id,f.elements['p'+e.id].value,e.value,e.name,1);
						na.iva = f.elements['iva'+e.id].value;
						na.piva = f.elements['piva'+e.id].value;//nuevo
						na.civa = f.elements['civa'+e.id].value;//nuevo
						//na.accion=1;
						insertarItemAdicional(na);
					}
				}
			}
		}
		if (!checks){
			alert("Debe elegir al menos un elemento de la lista");
		} else {
			if(msg!=""){
				alert(msg);
				return;
			}
			cancelarAdicional();
		}
	}
	
	function insertarItemAdicional(ac){
		var p= acc.length;
		acc[p] = ac;
		
		var t = "<table border=\"0\" cellpadding=\"0\" width=\"100%\">"+
		"<tr align=\"right\">"+
			"<td width=\"50%\"><div class=\"petiqueta\">" + ac.nombre + ":</div></td>"+
			"<td width=\"50%\">"+
				"<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" cellspacing=\"0\">"+
				"<tr>"+
					"<td>"+
						<?php if (viewmode()) { ?>
							"<a href=\"javascript:validarEliminarAdicional("+p+")\"><img border=\"0\" src=\"../img/iconos/minus.png\" alt=\"Quitar\" style=\"margin-right:2px;\"/></a>"+
						<?php } ?>
					"</td>"+
					"<td>"+
						"<input type=\"text\" id=\"v"+ac.id+"\" name=\"acv[]\" onchange=\"percent(); setformato(this);\" onkeyup=\"percent();\" style=\"width:250px;\" value=\""+formato(ac.value)+"\" <?php if (!viewmode()) { echo 'readonly=\"readonly\"'; } ?>/>"+
					"</td>"+
				"</tr>"+
				"</table>"+
				"<input type=\"hidden\" name=\"ac[]\" value=\"" + ac.id.substring(3) + "\"/>"+
				"<input type=\"hidden\" name=\"acp[]\" value=\"" + ac.pq + "\"/>"+
				"<input type=\"hidden\" id=\"iddetacc" + ac.piva + "\" name=\"iddetacc[]\" value=\"" + ac.iddet + "\"/>"+
				"<input type=\"hidden\" id=\"civaacc" + ac.civa + "\" name=\"civaacc[]\" value=\"" + ac.civa + "\"/>"+
				"<input type=\"hidden\" id=\"pivaacc" + ac.piva + "\" name=\"pivaacc[]\" value=\"" + ac.piva + "\"/>"+
				"<input type=\"hidden\" id=\"ivaacc" + ac.iva + "\" name=\"ivaacc[]\" value=\"" + ac.iva + "\"/>"+
				"<input type=\"hidden\" id=\"hddTipoAccesorio" + ac.hddTipoAccesorio + "\" name=\"hddTipoAccesorio[]\" value=\"" + ac.hddTipoAccesorio + "\"/>"+
				"<input type=\"hidden\" id=\"aca" + ac.id + "\" name=\"acaccion[]\" value=\"" + ac.accion + "\"/>"+
			"</td>"+
		"</tr>"+
		"</table>";
		
		var accs = document.getElementById("accesorios");
		var ne = document.createElement("div");
		ac.capa = 'capa'+ac.id;
		ne.setAttribute('id',ac.capa);
		ne.innerHTML = t;
		accs.appendChild(ne);
		percent();
	}
	
	function listaPrecio(objToolTip) {
		var obj = objToolTip;
		var lista = document.getElementById("listaprecios");
		//lista.style.visibility="visible";
		
		lista.style.left = getOffsetLeft(obj)+obj.offsetWidth+"px";
		lista.style.top = getOffsetTop(obj)+"px";
		lista.style.margin = "0px 0px 0px 2px";
		alphashow("listaprecios",10,1);
		//recorrerListaPrecios(null);
	}
	
	function nuevoProspecto() {
		window.location = "an_prospecto_list.php";
	}
	
	function percent() {
		var precio_venta = objeto('precio_venta');
		var p_porcentaje = objeto('porcentaje_inicial');
		var p_inicial = objeto('p_inicial');
		var mesesFinanciar = objeto('lstMesesFinanciar');
		var porcentaje_flat = objeto('porcentaje_flat');
		montoSeguro = parsenum(objeto('monto_seguro').value);
		
		var ep_porcentaje = objeto('eporcentaje_inicial');
		var tinicial = parsenum(p_inicial.value);
		
		if (objeto('hddTipoInicial').value == 0 && objeto('rbtInicialPorc').style.visibility != "hidden") {
			if (tinicial == 0) {
				var tinicial = (parsenum(precio_venta.value) * parsenum(p_porcentaje.value)) / 100;
				p_porcentaje.value = parsenum(ep_porcentaje.value);
				p_inicial.value = formato(tinicial);
			}
			var porcentaje = (parsenum(p_inicial.value) * 100) / parsenum(precio_venta.value);
			p_porcentaje.value = (porcentaje);
			ep_porcentaje.value = formato(porcentaje);
			p_inicial.value = formato(p_inicial.value);
			
			objeto('rbtInicialPorc').checked = true;
			objeto('rbtInicialPorc').click();
		} else if (objeto('hddTipoInicial').value == 1 && objeto('rbtInicialMonto').style.visibility != "hidden") {
			var porcentaje = (parsenum(p_inicial.value) * 100) / parsenum(precio_venta.value);
			
			p_porcentaje.value = (porcentaje);
			ep_porcentaje.value = formato(porcentaje);
			p_inicial.value = formato(p_inicial.value);
			
			objeto('rbtInicialMonto').checked = true;
			objeto('rbtInicialMonto').click();
		} else {
			objeto('eporcentaje_inicial').readOnly = true;
			objeto('p_inicial').readOnly = true;
		}
		
		var totalAdicionales = 0;
		var totalContrato = 0;
		for (i = 0; i < acc.length; i++) {
			if (acc[i].accion != 2) {
				var obj = objeto('v' + acc[i].id);
				if (acc[i].hddTipoAccesorio == 1) {
					totalAdicionales += parsenum(obj.value);
				} else if (acc[i].hddTipoAccesorio == 3) {
					totalContrato += parsenum(obj.value);
				}
			}
		}
		
		var a2,a3,a4;
		a2 = objeto('vexacc2').value;
		a3 = objeto('vexacc3').value;
		a4 = objeto('vexacc4').value;
		
		var totalAccesorios = formato(parsenum(a2) + parsenum(a3) + parsenum(a4));
		var saldoFinanciar = parsenum(precio_venta.value) - tinicial;
		
		if (parsenum(ep_porcentaje.value) == 100) {
			var montoFlat = 0;
		} else {
			var montoFlat = (parsenum(saldoFinanciar) * parsenum(porcentaje_flat.value)) / 100;
		}
		
		var totalInicialGastos = tinicial + totalAdicionales;
		var totalGeneral = totalInicialGastos + montoSeguro + parsenum(totalAccesorios) + montoFlat + parsenum(totalContrato);
		objeto('totalinicial').value = formato(totalInicialGastos);
		objeto('monto_flat').value = formato(montoFlat);
		objeto('totalinicialflat').value = formato(totalInicialGastos + montoFlat);
		objeto('txtSaldoFinanciar').value = formato(saldoFinanciar);
		objeto('total_general').value = formato(totalGeneral);
		
		var cuotasFinanciar = objeto('txtCuotasFinanciar');
		if (mesesFinanciar != null && factor != null) {
			var interes = parsenum(saldoFinanciar.value) * factor[mesesFinanciar.value];
		}
		
		if (interes) {
			cuotasFinanciar.value = formato(interes);
		}
		
		objeto('capatotalinicial').innerHTML = formato(totalInicialGastos);
		objeto('capatotalinicialflat').innerHTML = formato(totalInicialGastos + montoFlat);
		objeto('txtTotalAdicionalContrato').value = formato(totalContrato);
		objeto('capatotalgeneral').innerHTML = formato(totalGeneral);
		
		objeto('txtMontoAccesorio').value = formato(objeto('txtMontoAccesorio').value);
		objeto('total_accesorio').value = totalAccesorios;
	}
	
	function percent2(){
		/*var p_inicial=objeto('p_inicial');
		var p_porcentaje=objeto('porcentaje_inicial');
		var porcentaje = (parsenum(p_inicial.value) * 100) / (parsenum(precio_venta.value));
		//alert(redondear(porcentaje,2));
		p_porcentaje.value=(formatNumber(porcentaje));
		percent();*/
		
		var precio_venta = objeto('precio_venta');
		var p_porcentaje = objeto('porcentaje_inicial');
		var ep_porcentaje = objeto('eporcentaje_inicial');
		var p_inicial = objeto('p_inicial');
	
		p_porcentaje.value = parsenum(ep_porcentaje.value);
		
		var tinicial = ((parsenum(precio_venta.value)*parsenum(p_porcentaje.value))/100);
		p_inicial.value = formato(tinicial);
		percent();
	}
	
	//lista precio
	var maxlp = 3
	var lppos = maxlp + 1;
	function recorrerListaPrecios(e) {
		if (e != null) {
			var tecla = (document.all) ? e.keyCode : e.which;
			if (tecla == 40){ // ABAJO
				lppos++;
			} else if (tecla == 38) { // ARRIBA
				lppos--;
			}
		}
		//restingiendo:
		if (lppos <= 0) {
			lppos = 3;
		} else if (lppos > maxlp) {
			lppos = 1;
		}
		for (var i = 1; i <= maxlp; i++) {
			var li = document.getElementById('lp'+i);
			if (i == lppos) {
				li.style.background='#FFCC66';
			} else {
				var backc = '#F1F1F1';
				if (i % 2 == 0) {
					backc = '#DBDBDB';
				}					
				li.style.background = backc;
			}
		}
		if (e != null) {
			if (tecla == 13) {
				asignarPrecio(lppos);
			}
		}
		//onclick="asignarPrecio('precio1')" onmouseover="this.style.background='';" onmouseout="this.style.background=';"
		
	}
	
	function validarEliminarAdicional(pos){
		var ac = acc[pos];
		if (confirm('Desea quitar ' + ac.nombre)){
			/*var accs = document.getElementById("accesorios");
			accs.removeChild(document.getElementById(ac.capa));*/
			var accs = document.getElementById(ac.capa);
			accs.style.display = "none";
			//delete acc[pos];
			acc[pos].accion = 2;
			var aca = document.getElementById('aca' + acc[pos].id);
			aca.value = acc[pos].accion;
			percent();
		}
	}
	
	function validarReputacion(valor, tipo) {
		if (valor == null) return true;
		var obj = document.getElementById('cedula');
		rep_val = valor; 
		rep_tipo = tipo;
		obj.style.background = valor;
		if (tipo != '') {
			return utf8confirm('ATENCI&Oacute;N el cliente tiene una reputaci&oacute;n de: ' + tipo);
		} else {
			return true;
		}
	}
	
	/*function select_meses(val){
		var o=document.getElementById('capameses_financiar');
		o.innerHTML=val;
	}*/
	var popPoliza=null;
	function npoliza(){
		if (popPoliza==null || popPoliza.closed){
			popPoliza=setestaticpopup('an_mantenimiento_poliza_insertar.php?popup','npolizaw',400,400);
		}
		popPoliza.focus();	
	}
	
	function reloadPoliza(poliza){
		cargarXML('polizas', poliza, '');
	}
</script>

</head>

<body <?php echo $loadscript ?> >
<!--AYUDAS -->
<div id="toolcliente" class="tooltip" onclick="helptip('cedula','helpcliente');">
	<strong>Ingrese el n&uacute;mero de c&eacute;dula del cliente para buscar.</strong><br />
	Haga clic en un elemento de la lista para cargar el cliente.<br />
	(tambi&eacute;n puede ingresar el nombre o apellido y el sistema buscar&aacute; autom&aacute;ticamente)
</div>
<div id="toolvehiculo" class="tooltip"  onclick="helptip('modelo','helpvehiculo');">
	<strong>Ingrese el <em>C&oacute;digo, Modelo o Marca</em> del veh&iacute;culo para buscar</strong><br />
	(tambi&eacute;n puede ingresar la marca, modelo o versi&oacute;n y el sistema buscar&aacute; autom&aacute;ticamente)
</div>
<div id="toolacc" class="tooltip" onclick="helptip('addacc','helpacc');">
	<strong>Ingrese el c&oacute;digo del paquete para buscar</strong><br />
	puede elegir el paquete completo o solo algunos elementos.
</div>
<div id="toolinicial" class="tooltip" >
	Ingrese el procentaje de inicial para realizar el c&aacute;lculo.
</div>
<div id="toolprecioventa" class="tooltip" >
	Ingrese o modifique el precio de venta del veh&iacute;culo.<em>Para reestablecer el precio vuelva a cargar el veh&iacute;culo.</em>
</div>
<div id="helpcliente" class="helptip" style="left:50%; top:30%;" onclick="closetooltip('helpcliente');">
<h1>Ayuda con clientes</h1>
<h2>Agregar un cliente:</h2>
<p>Para agregar un cliente necesita</p>

<h2>Agregar un NUEVO cliente:</h2>
<p>Para agregar un nuevo cliente necesita</p>
<a href="javascript:closetooltip('helpcliente');">Cerrar</a>
</div>
<div id="helpvehiculo" class="helptip" style="left:50%; top:30%;" onclick="closetooltip('helpvehiculo');">
<h1>Ayuda con Vehiculos</h1>
<h2>Agregar un cliente:</h2>
<p>Para agregar un cliente necesita</p>

<h2>Agregar un NUEVO cliente:</h2>
<p>Para agregar un nuevo cliente necesita</p>
<a href="javascript:closetooltip('helpvehiculo');">Cerrar</a>
</div>
<div id="helpacc" class="helptip" style="left:50%; top:30%;" onclick="closetooltip('helpacc');">
<h1>Ayuda con Paquetes y Accesorios</h1>
<h2>Agregar un Paquete:</h2>
<p>Para agregar un paquete completo introduzca el código y haga click en "Agregar"</p>
<h2>Agregar un s&oacute;lo accesorio del paquete:</h2>
<p>Para agregar un accesorio desmarque todos los checks de la lista<br /> menos el del accesorio que quiere agregar y luego haga click en "Agregar"</p>
<h2>Quitar un accesorio:</h2>
<p>Haga click en la X del acesorio correspondiente</p>
<a href="javascript:closetooltip('helpvehiculo');">Cerrar</a>
</div>

<!--Objetos ocultos <input value="" type="text" readonly="readonly" name="precio1" id="precio1" class="capacampo"/>-->
<div id="listacliente" class="ajaxlist"></div>
<div id="listavehiculo" class="ajaxlist"></div>
<div id="listaaccesorio" class="ajaxlist"></div>

<div id="listaprecios" style="visibility:hidden;background:#F1F1F1; position:absolute; padding:2px; width:360px; border:2px solid #CCC;	font-family:Verdana, Arial, Helvetica, sans-serif;	font-size:12px;">
    <table border="0" width="100%">
    <tr>
        <td class="tituloArea" colspan="3">
        	<table cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td align="center" width="100%">Lista Referencial de Precios</td>
                <td><img src="../img/iconos/cross.png" onclick="cancelarListaPrecio(); closetooltip('toolprecioventa');" style="cursor:pointer"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr id="lp1" onmouseover="this.style.background='#FFCC66';" onmouseout="this.style.background='#F1F1F1';" height="24">
        <td><button type="button" onclick="asignarPrecio(1);" title="Seleccionar"><img src="../img/iconos/tick.png"/></button></td>
        <td width="40%">Precio 1:</td>
        <td align="right" width="60%"><div id="precio1"><?php echo $precio1; ?></div></td>
    </tr>
    <tr id="lp2" onmouseover="this.style.background='#FFCC66';" onmouseout="this.style.background='#DBDBDB';" style="background:#DBDBDB;" height="24">
        <td><button type="button" onclick="asignarPrecio(2);" title="Seleccionar"><img src="../img/iconos/tick.png"/></button></td>
        <td>Precio 2:</td>
        <td align="right"><div id="precio2"><?php echo $precio2; ?></div></td>
    </tr>
    <tr id="lp3" onmouseover="this.style.background='#FFCC66';" onmouseout="this.style.background='#F1F1F1';" height="24">
        <td><button type="button" onclick="asignarPrecio(3);" title="Seleccionar"><img src="../img/iconos/tick.png"/></button></td>
        <td>Precio 3:</td>
        <td align="right"><div id="precio3"><?php echo $precio3; ?></div></td>
    </tr>
    </table>
</div>
    
<div id="divGeneralVehiculos">
	<div class="noprint"><?php if(onlyviewmode()){ include("banner_vehiculos.php");} ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr class="solo_print">
        	<td align="left" id="tdEncabezadoImprimir"></td>
        </tr>
        <tr>
        	<td class="tituloPaginaVehiculos">
            <?php echo ($modeform == "") ? "AGREGAR" : strtoupper($modeform); ?>
            </td>
        </tr>
        </table>
    <form id="presupuesto" action="an_ventas_presupuesto_guardar.php" method="post" >
        <input type="hidden" id="txtIdEmpresa" name="txtIdEmpresa" value="<?php echo $idEmpresa; ?>"/>
        <input type="hidden" id="txtIdPresupuesto" name="txtIdPresupuesto" value="<?php echo $idPresupuesto; ?>"/>
        <input type="hidden" id="txtIdCliente" name="txtIdCliente" value="<?php echo $idCliente; ?>"/>
        <input type="hidden" id="txtIdUnidadBasica" name="txtIdUnidadBasica" value="<?php echo $idUnidadBasica; ?>"/>
        <input type="hidden" id="clientec" name="clientec" value="<?php echo $cedula; ?>"/>
        <input type="hidden" id="modeloc" name="modeloc" value="<?php echo $v_modelo; ?>"/>
        <input type="hidden" id="mode" name="mode" value="0"/>
        
        <input type="hidden" id="hddPagaImpuesto" name="hddPagaImpuesto" value="<?php echo $hddPagaImpuesto; ?>"/>
        <input type="hidden" id="porcentaje_iva" name="porcentaje_iva" readonly="readonly" value="<?php echo $porcentaje_iva; ?>"/>
        <input type="hidden" id="porcentaje_impuesto_lujo" name="porcentaje_impuesto_lujo" value="<?php echo $porcentaje_impuesto_lujo; ?>"/>
       
        <div id="listacliente" class="ajaxlist"></div>
        <div id="listavehiculo" class="ajaxlist"></div>
        
		<table border="0" width="100%">
        <tr>
        	<td align="center">
				<?php
                if (isset($_GET['view'])) {
                    if ($_GET['view'] != 'print'){
                        echo "<strong>(MODO VISTA)</strong>";
                    }
                    
                    if($_GET['view'] != 'print') { ?>
                    <div>
                        <button type="button" onclick="window.location.href='../financiamientos/fi_form_financiamiento.php?idPresupuestoVehiculo=<?php echo $idPresupuesto; ?>';"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/coins.png"/></td><td>Generar Financiamiento</td></tr></table></button>
                        
                        <button type="button" onclick="window.location.href='an_ventas_pedido_generar.php?idPresupuesto=<?php echo $idPresupuesto; ?>';"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/book_next.png"/></td><td>Generar Pedido</td></tr></table></button>
                        
                        <?php
						$presupuestoVentaForm = (in_array(idArrayPais,array(1,2,3))) ?
							sprintf("an_ventas_presupuesto_editar.php?id=%s",
								$idPresupuesto) :
							sprintf("an_presupuesto_venta_form.php?id=%s",
								$idPresupuesto); ?>
                        <button type="button" onclick="window.location.href='<?php echo $presupuestoVentaForm; ?>';"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/pencil.png"/></td><td>Editar</td></tr></table></button>
                        
                     	<?php
						$presupuestoVentaForm = (in_array(idArrayPais,array(1,2,3))) ?
							sprintf("imprimir();") :
							sprintf("verVentana('reportes/an_presupuesto_venta_pdf.php?id=%s', 960, 550);",
								$idPresupuesto); ?>
                        <button type="button" onclick="<?php echo $presupuestoVentaForm; ?>"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_print.png"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button>
                        
                        <button type="button" onclick="window.location.href='an_presupuesto_venta_list.php';"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/return.png"/></td><td>Regresar</td></tr></table></button>
					</div>
                <?php
                    }
                } ?>
            </td>
		</tr>
        <tr>
        	<td>
                <table border="0" width="100%">
                <tr>
                    <td width="80%">
                        <table>
                        <tr>
                            <td><img src="../<?php echo $rowEmp['logo_familia'];?>" height="90"></td>
                            <td align="left" class="textoNegrita_10px">
                            	<p style="font-size:11px;">
                                    <?php echo utf8_encode($rowEmp['nombre_empresa']); ?><br>
                                    <?php echo $spanRIF.": ".utf8_encode($rowEmp['rif']); ?> <?php echo $spanNIT.": ".utf8_encode($rowEmp['nit']); ?><br>
                                    <?php echo (count($arrayTelefonos) > 0) ? "Telf.: ".implode(" / ", $arrayTelefonos): ""; ?><br>
                                    <?php echo utf8_encode($rowEmp['web']); ?>
                                </p>
                            </td>
                        </tr>
                        </table>
                    </td>
                    <td align="right" valign="bottom" width="20%">
                    	<table>
                        <tr>
                        	<td colspan="2"><?php if ($idPresupuesto > 0) { ?><img border="0" src="../clases/barcode128.php?type=B&bw=2&codigo=<?php echo $idPresupuesto; ?>&pc=0"/><?php } ?></td>
                        </tr>
                        <tr align="left">
                        	<td align="right" width="40%">Número:</td>
                        	<td width="60%"><b><?php echo $numeracionPresupuesto; ?></b></td>
                        </tr>
                        </table>
					</td>
                </tr>
                </table>
            </td>
		</tr>
        <tr>
        	<td class="tituloArea">Datos Personales del Cliente</td>
        </tr>
        <tr>
        	<td>
            	<table border="0" width="100%">
                <tr>
                	<td><div class="etiqueta" id="ec"><label for="cedula" accesskey="c" <?php if(viewmode()) { ?> onclick="helptip('cedula','helpcliente');" <?php } ?> >C&eacute;dula:</label></div></td>
                	<td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="cedula" name="cedula" autocomplete="off" <?php if(viewmode()) { ?> onkeyup="ku_buscarcliente(event,this);" onfocus="tooltip(this,'toolcliente',5);" onblur="closetooltip('toolcliente');" <?php } ?> <?php blockprint($cedula); ?> style="width:320px;"/></td>
                            <td>&nbsp;</td>
                            <td>
                            <?php if(viewmode()) { ?>
                                <a href="javascript:nuevoProspecto();" title="Insertar Nuevo Cliente"><img border="0" src="../img/iconos/ico_new.png" alt="Nuevo Prospecto / Cliente"/></a>
                            <?php } ?>
                            </td>
                        </tr>
                        </table>
                    </td>
                    <td colspan="2"><strong><?php echo utf8_encode($spanCiudadLocal); ?>, <?php echo ($fecha == "") ? date(spanDateFormat) : $fecha; ?></strong></td>
				</tr>
                <tr>
                	<td width="10%"><div class="etiqueta">Nombre:</div></td>
                	<td width="50%"><div id="nombre" class="capacampo1"><?php echo $nombre; ?></div></td>
                    <td width="10%"><div class="etiqueta">Apellido:</div></td>
                    <td width="30%"><div id="apellido" class="capacampo1"><?php echo $apellido; ?></div></td>
                </tr>
                <tr>
                	<td><div class="etiqueta">Teléfono:</div></td>
                	<td><div id="thab" class="capacampo1"><?php echo $telefono; ?></div></td>
                	<td><div class="etiqueta">Otro Teléfono:</div></td>
                	<td><div id="celular" class="capacampo1"><?php echo $otroTelefono; ?></div></td>
                </tr>
                <tr>
                	<td><div class="etiqueta">Direcci&oacute;n:</div></td>
                	<td><div id="direccion" class="capacampo1" style="height:40px;"><?php echo $direccion; ?></div></td>
                	<td><div class="etiqueta">Ciudad:</div></td>
                	<td><div id="ciudad" class="capacampo1"><?php echo $ciudad; ?></div></td>
                </tr>
                <tr>
                	<td><div class="etiqueta">E-mail:</div></td>
                	<td><div id="email" class="capacampo1"><?php echo $correo; ?></div></td>
                	<td><div class="etiqueta">Sexo:</div></td>
                	<td><div id="sexo" class="capacampo1"><?php echo $sexo; ?></div></td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
        	<td class="tituloArea">Datos del Vehículo</td>
        </tr>
        <tr>
        	<td>
                <table border="0" width="100%">
                <tr align="right">
                    <td width="10%"><div class="etiqueta"><label for="modelo" accesskey="m">Veh&iacute;culo Modelo:</label></div></td>
                    <td width="50%"><div class="pcampo"><input type="text" id="modelo" name="modelo" autocomplete="off" <?php blockprint($v_modelo); ?> <?php if(viewmode()) { ?> onkeyup="ku_buscarvehiculo(event,this);" onfocus="tooltip(this,'toolvehiculo',5);" onblur="closetooltip('toolvehiculo');" <?php } ?> style="width:400px;"/></div></td>
                    <td width="10%"><div class="etiqueta">Año:</div></td>
                    <td width="30%"><div id="version" class="capacampo" style="width:316px;"><?php echo $txtAno; ?></div></td>
                </tr>
                <tr align="right">
                	<td>
                        <div class="etiqueta">Precio Venta:<br>
                            <span id="eviva" class="textoNegrita_10px"><?php echo $eviva; ?></span>
                            <input type="hidden" id="viva" name="viva" value="1"/>
                        </div>
                    </td>
                	<td>
                        <input type="text" id="txtPrecioBase" name="txtPrecioBase" autocomplete="off" onchange="asignarPrecio(); setformato(this);" onkeydown="recorrerListaPrecios(event);" onkeypress="return inputnum(event);" <?php if(viewmode()) { ?> ondblclick="listaPrecio(this); tooltip(this,'toolprecioventa',3);" <?php } ?> style="display:none; width:250px;" <?php blockprint(numformat($txtPrecioBase,2)); ?>/>
                        <input type="text" id="txtDescuento" name="txtDescuento" onchange="asignarPrecio(); setformato(this);" onkeypress="return inputnum(event);" style="display:none; width:250px;" <?php blockprint(numformat($txtDescuento,2)); ?>/>
                    	<div class="pcampo">
							<?php echo $abrevMonedaLocal; ?>
                            <input type="text" id="precio_venta" name="precio_venta" onkeypress="return inputnum(event);" onkeydown="recorrerListaPrecios(event);" onchange="percent(); setformato(this);" <?php if(viewmode()) { ?> ondblclick="listaPrecio(this); tooltip(this,'toolprecioventa',3);" <?php } ?> style="width:250px;" <?php blockprint(numformat($precio_venta,2)); ?>/>
                        </div>
                    </td>
                    <td colspan="2" rowspan="3" valign="top">
                    	<table style="visibility:hidden" width="100%">
                        <tr>
                            <td align="center" colspan="2" class="tituloArea">Accesorios del Veh&iacute;culo</td>
                        </tr>
                        <tr align="center">
                            <td class="tituloCampo">Descripción</td>
                            <td class="tituloCampo">Monto Total</td>
                        </tr>
                        <tr>
                            <td width="80%"><input type="text" id="txtDescripcionAccesorio" name="txtDescripcionAccesorio" style="text-align:left; width:98%;" value="<?php echo $txtDescripcionAccesorio; ?>"/></td>
                            <td width="20%"><input type="text" id="txtMontoAccesorio" name="txtMontoAccesorio" onblur="percent();" style="text-align:right; width:98%;" value="<?php echo $txtMontoAccesorio; ?>"/></td>
                        </tr>
                        </table>
                        	
                        <table border="0" style="display:none" width="100%">
                        <tr height="22">
                            <td align="center" colspan="3" class="tituloCampo">Accesorios del Veh&iacute;culo</td>
                        </tr>
                        <tr>
                            <td class="tdaex" colspan="2"><input <?php blockprint($exacc2); ?>  name="exacc2" id="exacc2" class="iaex" type="text" style="text-align:left;border:0px;margin:0px;height:20px;padding:0px"/></td>
                            <td class="tdaex"><input <?php blockprint(numformat($vexacc2,2)); ?>  onchange="percent();setformato(this);" name="vexacc2" id="vexacc2" class="iaex" type="text" style="border:0px;margin:0px;height:20px;padding:0px"/></td>
                        </tr>
                        <tr>
                            <td class="tdaex" colspan="2"><input <?php blockprint($exacc3); ?>  name="exacc3" id="exacc3" class="iaex" type="text" style="text-align:left;border:0px;margin:0px;height:20px;padding:0px"/></td>
                            <td class="tdaex" colspan="2"><input <?php blockprint(numformat($vexacc3,2)); ?>  onchange="percent();setformato(this);" name="vexacc3" id="vexacc3" class="iaex" type="text" style="border:0px;margin:0px;height:20px;padding:0px"/></td>
                        </tr>
                        <tr>
                            <td class="tdaex" colspan="2"><input <?php blockprint($exacc4); ?>  name="exacc4" id="exacc4" class="iaex" type="text" style="text-align:left;border:0px;margin:0px;height:20px;padding:0px"/></td>
                            <td class="tdaex"><input <?php blockprint(numformat($vexacc4,2)); ?>  onchange="percent();setformato(this);" name="vexacc4" id="vexacc4" class="iaex" type="text" style="border:0px;margin:0px;height:20px;padding:0px"/></td>
                        </tr>
                        <tr>
                            <td class="tdaex" colspan="2">Total Accesorios</td>
                            <td class="tdaex" style="background:#F2F2F2;"><input <?php blockprint(numformat($total_accesorio,2)); ?>  type="text" name="total_accesorio" id="total_accesorio" readonly="readonly" style="border:0px;margin:0px;height:20px;padding:0px;background:#F2F2F2;width:98%;"/></td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo">Cheque a Favor de:</td>
                            <td colspan="2"><input type="text" id="empresa_accesorio" name="empresa_accesorio" class="iaex" <?php blockprint($empresa_accesorio); ?>/></td>
                        </tr>
                        <tr>
                        	<td width="25%"></td>
                        	<td width="30%"></td>
                        	<td width="25%"></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr align="right">
	                <td>
                    	<div class="etiqueta"><?php echo $spanInicial; ?>:
                            <input type="hidden" name="porcentaje_inicial"  id="porcentaje_inicial" value="<?php echo $porcentaje_inicial; ?>"/>
                            <input type="hidden" id="hddTipoInicial" name="hddTipoInicial" value="<?php echo $hddTipoInicial; ?>">
                        </div>
					</td>
                    <td>
                    	<div class="pcampo">
                        	<table cellpadding="0" cellspacing="0">
                            <tr>
                                <td><input id="rbtInicialPorc" name="rbtInicial" class="noprint" onclick="objeto('hddTipoInicial').value = 0; objeto('eporcentaje_inicial').readOnly = false; objeto('p_inicial').readOnly = true;" type="radio" value="1" <?php if(!viewmode()) { echo " style=\"visibility:hidden\""; }?>/></td>
                                <td><input type="text" id="eporcentaje_inicial" name="eporcentaje_inicial" onkeypress="return inputnum(event);" onblur="valfocus(); closetooltip('toolinicial');" onchange="percent2();" <?php if(viewmode()) { ?> onfocus="tooltip(this,'toolinicial',3);" <?php } ?> maxlength="5" style="text-align:right; width:50px;" <?php blockprint(numformat($porcentaje_inicial)); ?>/></td>
                                <td>%</td>
                                <td><input id="rbtInicialMonto" name="rbtInicial" class="noprint" onclick="objeto('hddTipoInicial').value = 1; objeto('eporcentaje_inicial').readOnly = true; objeto('p_inicial').readOnly = false;" type="radio" value="2" <?php if(!viewmode()) { echo " style=\"visibility:hidden\""; }?>/></td>
                            	<td><?php echo $abrevMonedaLocal; ?>&nbsp;</td>
                            	<td><input type="text" id="p_inicial" name="p_inicial" onblur="valfocus();" onkeypress="return inputnum(event);" style="width:250px;" onchange="percent();setformato(this);" <?php blockprint(numformat($p_inicial,2)); ?>/></td>
                            </tr>
                            </table>
                        </div>
                    </td>
                </tr>
                <tr align="right">
                	<td colspan="2">
                    	<div id="accesorios">
						<?php if(viewmode()) { ?>
                            <div class="campo">
                                <div class="etiqueta">
                                    <label for="addacc" accesskey="a" onclick="helptip('addacc','helpacc');">Agregar Paquete:</label>
                                </div>
                                
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="addacc" name="addacc" autocomplete="off" onkeyup="ku_buscaraccesorio(event,this);" onfocus="tooltip(this,'toolacc',5);" onblur="closetooltip('toolacc');" style="width:250px;"/></td>
                                    <td>&nbsp;</td>
                                    <td><a href="javascript:verif('addacc'); buscarAdicional('addacc');"><img border="0" src="../img/iconos/ico_buscar.png" title="Buscar Paquetes" alt="Buscar Paquetes"/></a></td>
                                </tr>
                                </table>
                            </div>
                        <?php } ?>
                            <!--array de campos 
                            <div class="campo">
                                <div class="etiqueta"><label for="ac[1]" accesskey="m">Costo de Placas:</label></div>
                                <div>
                                    <input type="text" id="ac[1]" name="ac[]" readonly="readonly" style="width:240px;"/>
                                </div>
                            </div>
                            
                            <div class="campo">
                                <div class="etiqueta"><label for="ac[2]" accesskey="m">Gasto administrativo:</label></div>
                                <div>
                                    <input type="text" id="ac[2]" name="ac[]" readonly="readonly" style="width:240px;"  />
                                </div>
                            </div>
                            FIN DE INSERCIÓN -->
                        </div>
                    </td>
                </tr>
                <tr align="right">
                	<td><div class="etiqueta"><label for="totalinicial" accesskey="t">Total <?php echo $spanInicial; ?>, Gastos:</label></div>
						<input type="hidden" id="totalinicial" name="totalinicial" <?php blockprint(numformat($txtTotalInicialGastos,2)); ?>/>
					</td>
                	<td>
                    	<div class="pcampo">
                    		<table>
                            <tr>
                                <td><?php echo $abrevMonedaLocal; ?></td>
                                <td><div id="capatotalinicial" class="capacampo" style="font-weight:800; width:250px;"></div></td>
                            </tr>
                            </table>
                        </div>
					</td>
                    <td align="justify" colspan="2" rowspan="3" valign="top"><div><?php echo $txtObservacion; ?></div></td>
                </tr>
                <tr align="right">
                	<td><div class="etiqueta"><label for="totalinicialflat" accesskey="t">Total <?php echo $spanInicial; ?>, Gastos, FLAT:</label></div>
						<input type="hidden" id="totalinicialflat" name="totalinicialflat" value="<?php echo numformat($totalinicialflat,2); ?>"/>
					</td>
                	<td>
                        <div class="pcampo">
                    		<table>
                            <tr>
                                <td><?php echo $abrevMonedaLocal; ?></td>
                                <td><div id="capatotalinicialflat" class="capacampo" style="font-weight:800; width:250px;"></div></td>
                            </tr>
                            </table>
                        </div>
					</td>
                </tr>
                <tr>
                	<td colspan="2"><b>Cheque de Gerencia a favor de: <?php echo $rowEmp['nombre_empresa']; ?></b></td>
                </tr>
                <tr>
                	<td><div class="etiqueta">Saldo a Financiar:</div></td>
                	<td>
                        <table border="0" class="tdcampo" cellpadding="0" cellspacing="0" width="100%">
                        <tr align="left">
                        	<td colspan="3">
                            <?php
							if (viewmode()) { ?>
								<select id="lstBancoFinanciar" name="lstBancoFinanciar" onchange="asignarBanco(this.value);" value="<?php echo $lstBancoFinanciar; ?>">
									<option>[ Entidad Bancaria ]</option>
									<?php generar_select($lstBancoFinanciar,"SELECT idBanco, nombreBanco FROM bancos WHERE nombreBanco !=  '-' ORDER BY nombreBanco ASC;"); ?>
								</select>
							<?php
							} else if ($lstBancoFinanciar > 0) {
								echo "<b>".utf8_encode(getmysql("SELECT nombreBanco FROM bancos WHERE idBanco = ".valTpDato($lstBancoFinanciar, "int").";"))."</b>";
							} ?>
                            </td>
                        </tr>
                        <tr>
                            <td width="40%">
                                <table border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><?php echo $abrevMonedaLocal; ?></td>
                                    <td><input type="text" id="txtSaldoFinanciar" name="txtSaldoFinanciar" class="inputSinFondo" style="border:0px" readonly="readonly"/></td>
                                </tr>
                                </table>
                            </td>
                            <td style="font-size:8px;" width="30%">MONTO REFERENCIAL DE LAS CUOTAS MENSUALES</td>
                            <td width="30%">
                                <table border="0" cellpadding="0" cellspacing="0">
                                <tr align="center">
                                    <td colspan="2" style="font-size:10px;">
                                    	<table border="0" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><input type="text" id="lstMesesFinanciar" name="lstMesesFinanciar" class="inputSinFondo" readonly="readonly" style="border:0px; width:40px;" value="<?php echo $lstMesesFinanciar; ?>"/></td>
                                            <td>Meses</td>
                                        <?php if ($txtInteresCuotaFinanciar > 0) { ?>
                                        	<td>&nbsp;/&nbsp;</td>
                                            <td><input type="text" id="txtInteresCuotaFinanciar" name="txtInteresCuotaFinanciar" class="inputSinFondo" readonly="readonly" style="border:0px; width:60px;" value="<?php echo $txtInteresCuotaFinanciar; ?>"/></td>
                                            <td>%</td>
                                        </tr>
                                        <?php } ?>
                                        </table>
                                    </td>
                                </tr>
                                <tr align="center">
                                    <td colspan="2" style="font-size:10px;">
                                        <table border="0" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><?php echo $abrevMonedaLocal; ?></td>
                                            <td><input type="text" id="txtCuotasFinanciar" name="txtCuotasFinanciar" class="inputSinFondo" onchange="setformato(this);" readonly="readonly" style="border:0px;" value="<?php echo numformat($txtCuotasFinanciar,2); ?>"/></td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
					</td>
                    <td colspan="2" rowspan="3" valign="top">
                        <table border="0" class="tdcampo" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                            <td width="20%">Comisi&oacute;n<br />FLAT (<span id="capaporcentaje_flat"><?php echo numformat($txtPorcFLAT); ?></span>%):</td>
                            <td width="18%">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><?php echo $abrevMonedaLocal; ?></td>
                                    <td><input type="text" id="monto_flat" name="monto_flat" class="inputSinFondo" onchange="percent();setformato(this);" readonly="readonly" style="border:0px;" value="<?php echo numformat($txtMontoFLAT,2); ?>"/></td>
                                </tr>
                                </table>
                                <input type="hidden" id="porcentaje_flat" name="porcentaje_flat" value="<?php echo $txtPorcFLAT; ?>"/>
                            </td>
                            <td style="font-size:8px;" width="62%">
                                <?php
                                $queryBancosFLAT = sprintf("SELECT nombreBanco FROM bancos
                                WHERE porcentaje_flat > 0;");
                                $rsBancosFLAT = mysql_query($queryBancosFLAT);
                                if (!$rsBancosFLAT) die(mysql_error()."<brError Nro: ".mysql_errno()."<brLine: ".__LINE__);
                                while ($rowBancosFLAT = mysql_fetch_assoc($rsBancosFLAT)) {
                                    $arrayBancos[] = $rowBancosFLAT['nombreBanco'];
                                }
                                echo (count($arrayBancos) > 0) ? "ESTA COMISI&Oacute;N SOLO SE CANCELA AL CONCESIONARIO, EN EL CASO DE OPERACIONES FINANCIADAS A TRAV&Eacute;S DE <b>".implode(", ", $arrayBancos)."</b>" : ""; ?>
                            </td>
                        </tr>
                        </table>
					</td>
                </tr>
                <tr align="right">
                    <td><label for="totalOtros" accesskey="t">Total Otros Adicionales:</label></td>
                    <td>
                    	<table>
                        <tr>
                        	<td><?php echo $abrevMonedaLocal; ?></td>
                        	<td><input type="text" id="txtTotalAdicionalContrato" name="txtTotalAdicionalContrato" class="capacampo" readonly="readonly" style="font-weight:800; width:250px;" <?php blockprint(numformat($txtTotalAdicionalContrato,2)); ?>/></td>
                        </tr>
                        </table>
					</td>
                </tr>
                <tr align="right">
                	<td><div class="etiqueta"><b>Total General:</b></div>
						<input type="hidden" name="total_general" id="total_general"/>
					</td>
                	<td>
                        <div class="pcampo">
                    		<table>
                            <tr>
                                <td><?php echo $abrevMonedaLocal; ?></td>
                                <td><div id="capatotalgeneral" class="capacampo" style="font-weight:800; width:250px;"></div></td>
                            </tr>
                            </table>
                        </div>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr style="display:none">
        	<td>
            	<table border="0" width="100%">
                <tr>
                    <td class="tituloArea" colspan="7">Póliza de Seguro</td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">P&oacute;liza de Seguros:</td>
                    <td colspan="6">
                    <?php
                    if (viewmode()) { ?>
                        <div id="capapoliza">
                            <select id="id_poliza" name="id_poliza" onchange="asignarPoliza(this.value);" style="width:128px;" value="<?php echo $id_poliza; ?>">
                                <option value="">[ P&oacute;liza ]</option>
                                <?php if($id_poliza!="") {generar_select($id_poliza,"SELECT id_poliza, nombre_poliza FROM an_poliza;");} ?>
                            </select>
                        </div>
                        <br>
                        <a href="javascript:npoliza()">Agregar</a>
                    <?php
                    } else if ($id_poliza != "") {
                        $quer = "SELECT nombre_poliza FROM an_poliza where id_poliza=".$id_poliza.";";
                        echo htmlentities(getmysql($quer));
                    }					
                    ?>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="14%">Monto Seguro:</td>
                	<td class="tdcampo" style="padding:0px;background:#FFFFFF;">
                        <input <?php blockprint(numformat($monto_seguro,2)); ?> name="monto_seguro" id="monto_seguro" style="margin:0px;border:0px;width:100px;" type="text" onchange="percent();setformato(this);"/>
                    </td>
                    <td align="right" class="tituloCampo" width="14%"><?php echo $spanInicial; ?>:</td>
                    <td class="tdcampo" style="border-left:0px;padding:0px;background:#FFFFFF;">
                        <input <?php blockprint(numformat($inicial_poliza,2)); ?> name="inicial_poliza" id="inicial_poliza" style="margin:0px;border:0px;width:100px;" type="text" onchange="percent();setformato(this);"/>
                    </td>
                    <td align="right" class="tituloCampo" width="14%">Nro. Cuotas:</td>
                    <td><font style="font-size:9px; border-right:0px;"><input type="text" name="meses_poliza" id="meses_poliza" <?php blockprint($meses_poliza); ?> style="width:20px; border:0px; padding:1px;"/> cuotas</font></td>
                    <td class="tdcampo" style="border-top:0px;border-left:0px;padding:0px;background:#FFFFFF;">
                        <input <?php blockprint(numformat($cuotas_poliza,2)); ?> name="cuotas_poliza" id="cuotas_poliza" style="margin:0px;border:0px;width:100px;" type="text" onchange="percent();setformato(this);"/>
                    </td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo">Cheque a Favor de:</td>
                    <td class="tdinfo"><div id="cheque_poliza"><?php echo $cheque_poliza;?></div></td>
                    <td align="right" class="tituloCampo">Financiada:</td>
                    <td class="tdinfo" colspan="2"><div id="financiada"><?php echo $financiada;?></div></td>
                </tr>
                </table>
			</td>
		</tr>
        <tr align="left" height="28">
        	<td><strong>Nota Importante:</strong> Precios Calculados a la Tasa Cambiaria e Impuestos Actuales. Cualquier variaci&oacute;n en los anteriores, podrá dar lugar a la reconsideraci&oacute;n en los precios y condiciones sin previo aviso.</td>
        </tr>
        <tr>
        	<td class="tituloArea">Datos del Vehículo</td>
        </tr>
        <tr>
        	<td id="descripcion" align="justify"><?php echo $v_des; ?></td>
        </tr>
        <tr>
        	<td>
                <table border="0" class="tabla" width="100%">
                <tr>
                    <td align="left" id="tdRecaudosProforma" width="45%" rowspan="2"><?php echo utf8_encode($tdRecaudosProforma); ?></td>
                    <td align="center" width="35%">
                        <div id="foto"><?php echo $img; ?></div>
                    </td>
                    <td width="20%" valign="bottom" align="center" style="border-bottom:1px solid #000000;">&nbsp;</td>
                </tr>
                <tr align="center">
                    <td>Foto Referencial</td>
                    <td>
                        <?php blockselect("asesor_ventas",$idAsesorVentas,"SELECT id_empleado, asesor FROM vw_an_asesor_ventas WHERE id_empresa=".$idEmpresa." AND activo = 1 ORDER BY asesor ASC;","SELECT CONCAT_WS(' ', nombre_empleado, apellido) FROM pg_empleado WHERE id_empleado=".$idAsesorVentas.";"); ?>
                        <br>
                        <?php
                        if ($idAsesorVentas > 0) {
                            $queryEmpleado = sprintf("SELECT *, 
								(SELECT codigo_empleado FROM pg_empleado
								WHERE id_empleado = vw_pg_empleado.id_empleado) AS codigo_empleado
							FROM vw_pg_empleados vw_pg_empleado
								LEFT JOIN pg_usuario usuario ON (vw_pg_empleado.id_empleado = usuario.id_empleado)
							WHERE vw_pg_empleado.id_empleado = %s;",
								valTpDato($idAsesorVentas, "int"));
							$rsEmpleado = mysql_query($queryEmpleado);
							if (!$rsEmpleado) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
							
							echo $rowEmpleado['nombre_cargo']."<br>";
							echo ((strlen($rowEmpleado['celular']) > 0) ? "Móvil: ".$rowEmpleado['celular']."<br>" : "");
							echo $rowEmpleado['email']."<br>";
                        } ?>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
        	<td>
                <hr>
                <span><?php echo htmlentities($rowEmp['nombre_empresa'].' ('.$rowEmp['sucursal'].') '.$rowEmp['direccion'].' Tlfs: '.$rowEmp['telefono1'].' '.$rowEmp['telefono2'].' '.$rowEmp['telefono3'].' '.$rowEmp['telefono4'].' / Fax: '.$rowEmp['fax']); ?></span>
            </td>
        </tr>
        <tr>
        	<td align="right">
			<?php if(viewmode()) { ?>
                <hr>
                <button type="button" id="btnGuardar" name="btnGuardar" onclick="guardar();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td><?php if ($modeform == "") { $save= "Agregar"; } else { $save= $modeform; }	echo $save; ?></td></tr></table></button>
                <button type="button" id="btnCancelar" name="btnCancelar" onclick="window.location.href='an_presupuesto_venta_list.php';" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
            <?php } ?>
            </td>
        </tr>
        </table>
    </form>
    </div>
    
    <div class="noprint">
	<?php if(onlyviewmode()){ include("pie_pagina.php");} ?>
    </div>
</div>
</body>
</html>
<script type="text/javascript" language="javascript">
	enfoca('cedula');
</script>   