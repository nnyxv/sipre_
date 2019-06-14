<?php
require_once "../connections/conex.php";

@session_start();

require_once("../inc_sesion.php");

//leyendo los datos del presupuesto:
$idPresupuesto = $_GET['id'];

//PENDIENTE: validar si se puede modificar el presupuesto, cuando se defina bien la estructura de las tablas y su integracion. ------------------
if (getmysql("SELECT COUNT(*) from an_pedido WHERE id_presupuesto = ".$idPresupuesto.";") != 0 && !isset($_GET['view'])) {
	echo '<script language="javascript" type="text/javascript"> alert("No se puede modificar el presupuesto"); window.location.href="an_presupuesto_venta_list.php"; </script>';
	exit;
}

conectar();
$sql = sprintf("SELECT 
	pres_vent.id_presupuesto,
	pres_vent.numeracion_presupuesto,
	pres_vent.id_empresa,
	pres_vent.id_cliente,
	pres_vent.id_uni_bas,
	pres_vent.asesor_ventas,
	pres_vent.estado,
	pres_vent.fecha,
	pres_vent.precio_venta,
	pres_vent.monto_descuento,
	pres_vent.porcentaje_iva,
	pres_vent.porcentaje_impuesto_lujo,
	pres_vent.tipo_inicial,
	pres_vent.porcentaje_inicial,
	pres_vent.monto_inicial,
	pres_vent.id_banco_financiar,
	pres_vent.saldo_financiar,
	pres_vent.meses_financiar,
	pres_vent.interes_cuota_financiar,
	pres_vent.cuotas_financiar,
	pres_vent.meses_financiar2,
	pres_vent.interes_cuota_financiar2,
	pres_vent.cuotas_financiar2,
	pres_vent.total_accesorio,
	pres_vent.total_inicial_gastos,
	pres_vent.total_adicional_contrato,
	pres_vent.total_general,
	pres_vent.porcentaje_flat,
	pres_vent.monto_flat,
	pres_vent.empresa_accesorio,
	pres_vent.exacc1,
	pres_vent.exacc2,
	pres_vent.exacc3,
	pres_vent.exacc4,
	pres_vent.vexacc1,
	pres_vent.vexacc2,
	pres_vent.vexacc3,
	pres_vent.vexacc4,
	pres_vent.id_poliza,
	poliza.cheque_poliza,
	poliza.financiada,
	pres_vent.monto_seguro,
	pres_vent.contado_poliza,
	pres_vent.inicial_poliza,
	pres_vent.meses_poliza,
	pres_vent.cuotas_poliza,
	pres_vent.observacion
FROM an_presupuesto pres_vent
	LEFT JOIN an_poliza poliza ON (pres_vent.id_poliza = poliza.id_poliza)
WHERE pres_vent.id_presupuesto = %s;",
	valTpDato($idPresupuesto, "int"));
$r = mysql_query($sql,$conex);
if (!$r) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$row = mysql_fetch_assoc($r);

// BUSCA LOS DATOS DE LA MONEDA NACIONAL
$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas WHERE predeterminada = 1;");
$rsMonedaLocal = mysql_query($queryMonedaLocal);
if (!$rsMonedaLocal) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);

$abrevMonedaLocal = $rowMonedaLocal['abreviacion'];

if ($row['estado'] == 3) {
	echo '<script src="vehiculos.inc.js"></script>
	<script language="javascript" type="text/javascript">
		utf8alert("El presupuesto '.$idPresupuesto.' est&aacute; desautorizado");
		window.location.href="an_presupuesto_venta_list.php";
	</script>';
	exit;
}

$idEmpresa = $row['id_empresa'];

$idPresupuesto = $row['id_presupuesto'];
$numeracionPresupuesto = $row['numeracion_presupuesto'];
$idCliente = $row['id_cliente'];
$idUnidadBasica = $row['id_uni_bas'];
$idAsesorVentas  = $row['asesor_ventas'];
$fecha = date(spanDateFormat, strtotime($row['fecha']));
$txtObservacion = utf8_encode($row['observacion']);

$txtPrecioBase = $row['precio_venta'];
$txtDescuento = $row['monto_descuento'];
$porcentaje_iva = $row['porcentaje_iva'];
$porcentaje_impuesto_lujo = $row['porcentaje_impuesto_lujo'];
$hddTipoInicial = $row['tipo_inicial'];
$porcentaje_inicial = $row['porcentaje_inicial'];
$p_inicial = $row['monto_inicial'];

// FINANCIAMIENTO
$lstBancoFinanciar = $row['id_banco_financiar'];
$txtSaldoFinanciar = $row['saldo_financiar'];
$lstMesesFinanciar = $row['meses_financiar'];
$txtInteresCuotaFinanciar = $row['interes_cuota_financiar'];
$txtCuotasFinanciar = numformat($row['cuotas_financiar'],2);

$total_accesorio = $row['total_accesorio'];
$txtTotalInicialGastos = $row['total_inicial_gastos'];
$txtTotalAdicionalContrato = $row['total_adicional_contrato'];
$total_general = $row['total_general'];
$txtPorcFLAT = $row['porcentaje_flat'];
$txtMontoFLAT = $row['monto_flat'];

// ACCESORIOS
$empresa_accesorio = utf8_encode($row['empresa_accesorio']);
$txtDescripcionAccesorio = utf8_encode($row['exacc1']);
$txtMontoAccesorio = $row['vexacc1'];
$exacc2 = utf8_encode($row['exacc2']);
$vexacc2 = $row['vexacc2'];
$exacc3 = utf8_encode($row['exacc3']);
$vexacc3 = $row['vexacc3'];
$exacc4 = utf8_encode($row['exacc4']);
$vexacc4 = $row['vexacc4'];

// POLIZA
$id_poliza = $row['id_poliza'];
$cheque_poliza = $row['cheque_poliza'];
$financiada = $row['financiada'];
$monto_seguro = $row['monto_seguro'];
$inicial_poliza = $row['inicial_poliza'];
$cuotas_poliza = $row['cuotas_poliza'];
$meses_poliza = $row['meses_poliza'];

// BUSCA LOS DATOS DEL CLIENTE
$queryCliente = sprintf("SELECT
	cliente.id,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	cliente.nombre,
	cliente.apellido,
	CONCAT_WS(': ', CONCAT_WS('-', cliente.lci, cliente.ci), CONCAT_WS(' ', cliente.nombre, cliente.apellido)) AS nombre_cliente,
	cliente.telf,
	cliente.direccion,
	cliente.correo,
	cliente.ciudad,
	cliente.otrotelf,
	IF(cliente.tipo = 'Natural', IF(perfil_prospecto.sexo = 'M', 'Masculino', 'Femenino'),'') AS sexo_cliente,
	cliente.reputacionCliente + 0 AS id_reputacion_cliente,
	cliente.reputacionCliente,
	cliente.tipo_cuenta_cliente,
	cliente.tipo,
	cliente.paga_impuesto
FROM cj_cc_cliente cliente
	LEFT JOIN crm_perfil_prospecto perfil_prospecto ON (cliente.id = perfil_prospecto.id)
	LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cliente.id_empleado_creador = vw_pg_empleado.id_empleado)
WHERE cliente.id = %s;",
	valTpDato($idCliente, "int"));
$rsCliente = mysql_query($queryCliente,$conex);
if (!$rsCliente) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowCliente = mysql_fetch_array($rsCliente, MYSQL_ASSOC);

// DATOS DEL CLIENTE
$cedula = utf8_encode($rowCliente['ci_cliente']);
$nombre = utf8_encode($rowCliente['nombre']);
$apellido = utf8_encode($rowCliente['apellido']);
$telefono = utf8_encode($rowCliente['telf']);
$otroTelefono = $rowCliente['otrotelf'];
$direccion = utf8_encode($rowCliente['direccion']);
$ciudad = utf8_encode($rowCliente['ciudad']);
$sexo = utf8_encode($rowCliente['sexo_cliente']);
$correo = utf8_encode($rowCliente['correo']);
$most = 'false';
$tipoCuentaCliente = $rowCliente['tipo_cuenta_cliente'];
$hddPagaImpuesto = ($rowCliente['paga_impuesto']);
$tdMsjCliente = (($rowCliente['paga_impuesto'] == 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente Exento y/o Exonerado</div>" : "");

// verifica que si tiene iva:
$nporcentaje_iva = 0;
if (getmysql("SELECT isan_uni_bas FROM an_uni_bas WHERE id_uni_bas = ".$idUnidadBasica.";") == 1 && $hddPagaImpuesto == 1) {
	$query = sprintf("SELECT
		iva.iva,
		iva.observacion
	FROM an_unidad_basica_impuesto uni_bas_impuesto
		INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
	WHERE uni_bas_impuesto.id_unidad_basica = %s
		AND iva.tipo IN (6)
		AND uni_bas_impuesto.id_impuesto NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
												WHERE cliente_imp_exento.id_cliente = %s);",
		valTpDato($idUnidadBasica, "int"),
		valTpDato($idCliente, "int"));
	$rs = mysql_query($query);
	if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$nporcentaje_iva = $row['iva'];
	$cond = (strlen($eviva) > 0) ? " e " : " Incluye ";
	$eviva .= $cond.$row['observacion'];
}

//verifica si tien impuesto al lujo:
$nporcentaje_impuesto_lujo = 0;
if (getmysql("SELECT impuesto_lujo FROM an_uni_bas WHERE id_uni_bas = ".$idUnidadBasica.";") == 1 && $hddPagaImpuesto == 1) {
	$query = sprintf("SELECT
		iva.iva,
		iva.observacion
	FROM an_unidad_basica_impuesto uni_bas_impuesto
		INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
	WHERE uni_bas_impuesto.id_unidad_basica = %s
		AND iva.tipo IN (2)
		AND uni_bas_impuesto.id_impuesto NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
												WHERE cliente_imp_exento.id_cliente = %s);",
		valTpDato($idUnidadBasica, "int"),
		valTpDato($idCliente, "int"));
	$rs = mysql_query($query);
	if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$nporcentaje_impuesto_lujo = $row['iva'];
	$cond = (strlen($eviva) > 0) ? " e " : " Incluye ";
	$eviva .= $cond.$row['observacion'];
}

if ($_GET['view'] == "") {
	if ($porcentaje_iva != $nporcentaje_iva) {
		$porcentaje_iva = $nporcentaje_iva;
		$msg = "Se ha actualizado el Impuesto en: ".$porcentaje_iva."%";
	}
	if ($porcentaje_impuesto_lujo != $nporcentaje_impuesto_lujo) {
		$porcentaje_impuesto_lujo = $nporcentaje_impuesto_lujo;
		$msg .= "Se ha actualizado el Impuesto al Lujo en: ".$porcentaje_impuesto_lujo."%";
	}
	if ($msg != "") {
		$msg = "alert('".$msg."');";
	}
}

// RECALCULAR LOS IMPUESTOS
$piva = (($txtPrecioBase - $txtDescuento) * $porcentaje_iva) / 100;
$plujo = (($txtPrecioBase - $txtDescuento) * $porcentaje_impuesto_lujo) / 100;
$precio_venta = ($txtPrecioBase - $txtDescuento) + $piva + $plujo;

if ($rowCliente['id_reputacion_cliente'] == 1) {
	$rep_val = '#FF5F5F';
	$most = 'true';
	$rep_tipo = $rowCliente['reputacionCliente'];
} else if ($rowCliente['id_reputacion_cliente'] == 2) {
	$rep_val = '#5AEF59';
	$rep_tipo = $rowCliente['reputacionCliente'];
} else {
	$rep_val = '#FFFFFF';
}

$idConfiguracion = ($rowCliente['tipo'] == "Natural") ? 200 : 201;

// VERIFICA VALORES DE CONFIGURACION
$queryConfigRecaudos = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = %s
	AND config_emp.status = 1
	AND config_emp.id_empresa = %s;",
	valTpDato($idConfiguracion, "int"),
	valTpDato($idEmpresa, "int"));
$rsConfigRecaudos = mysql_query($queryConfigRecaudos,$conex);
if (!$rsConfigRecaudos) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowConfigRecaudos = mysql_fetch_assoc($rsConfigRecaudos);

$tdRecaudosProforma = preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",$rowConfigRecaudos['valor']));
	
// BUSCA LOS DATOS DE LA UNIDAD BASICA
$sql = sprintf("SELECT *,
	CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
	(SELECT vers.des_version FROM an_version vers WHERE vers.id_version = uni_bas.ver_uni_bas) AS desc_version
FROM sa_unidad_empresa unidad_emp
	INNER JOIN vw_iv_modelos vw_iv_modelo ON (unidad_emp.id_unidad_basica = vw_iv_modelo.id_uni_bas)
	INNER JOIN an_uni_bas uni_bas ON (unidad_emp.id_unidad_basica = uni_bas.id_uni_bas)
	INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
WHERE uni_bas.id_uni_bas = %s;",
	valTpDato($idUnidadBasica, "int"));
$r = mysql_query($sql,$conex);
if (!$r) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$row = mysql_fetch_array($r, MYSQL_ASSOC);

$v_modelo = ($row['vehiculo']);
$txtAno = utf8_encode($row['nom_ano']);
$v_des = utf8_encode($row['desc_version']);
$precio1 = numformat(floatval($row['pvp_venta1']),2);
$precio2 = numformat(floatval($row['pvp_venta2']),2);
$precio3 = numformat(floatval($row['pvp_venta3']),2);

if (file_exists($row['imagen_auto'])) {
	$img = "<img src=\"".$row['imagen_auto']."\" alt=\"Foto Referencial\" border=\"0\" height=\"200\"/>";
} else {
	$img = "<img src=\"img/nodisponible.jpg\" alt=\"Foto Referencial\" border=\"0\" height=\"200\"/>";
}

if ($idPresupuesto > 0) {
	// cargando accesorios:
	$sqla = sprintf("SELECT 
		acc_pres.id_accesorio_presupuesto,
		acc_pres.id_presupuesto,
		acc_pres.id_accesorio,
		CONCAT(nom_accesorio, IF(acc_pres.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
		acc.des_accesorio,
		acc.id_tipo_accesorio,
		(CASE acc.id_tipo_accesorio
			WHEN 1 THEN	'Adicional'
			WHEN 2 THEN 'Accesorio'
			WHEN 3 THEN 'Contrato'
		END) AS descripcion_tipo_accesorio,
		acc_pres.iva_accesorio,
		acc_pres.porcentaje_iva_accesorio,
		(acc_pres.precio_accesorio + (acc_pres.precio_accesorio * acc_pres.porcentaje_iva_accesorio / 100)) AS precio_accesorio,
		acc_pres.costo_accesorio
	FROM an_accesorio_presupuesto acc_pres
		INNER JOIN an_accesorio acc ON (acc_pres.id_accesorio = acc.id_accesorio)
	WHERE acc_pres.id_presupuesto = %s;",
		valTpDato($idPresupuesto, "int"));
	$ra = mysql_query($sqla,$conex);
	if (!$ra) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	while ($rowa = mysql_fetch_array($ra)) {
		$echoacc .= sprintf("newacc('acc%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');",
			$rowa['id_accesorio'],
			"",
			$rowa['precio_accesorio'],
			utf8_encode($rowa['nom_accesorio']),
			"3",
			$rowa['iva_accesorio'],
			$rowa['costo_accesorio'],
			$rowa['porcentaje_iva_accesorio'],
			$rowa['id_tipo_accesorio'],
			$rowa['id_condicion_pago'],
			$rowa['id_condicion_mostrar'],
			$rowa['id_accesorio_presupuesto']);
	}
	
	$sqlp = sprintf("SELECT
		id_paquete_presupuesto,
		id_presupuesto,
		paq_pres.id_acc_paq,
		acc.id_accesorio,
		CONCAT(nom_accesorio, IF(paq_pres.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
		acc.des_accesorio,
		acc.id_tipo_accesorio,
		(CASE acc.id_tipo_accesorio
			WHEN 1 THEN	'Adicional'
			WHEN 2 THEN 'Accesorio'
			WHEN 3 THEN 'Contrato'
		END) AS descripcion_tipo_accesorio,
		paq_pres.iva_accesorio,
		paq_pres.porcentaje_iva_accesorio,
		(paq_pres.precio_accesorio + (paq_pres.precio_accesorio * paq_pres.porcentaje_iva_accesorio / 100)) AS precio_accesorio,
		paq_pres.costo_accesorio
	FROM an_paquete_presupuesto paq_pres
		INNER JOIN an_acc_paq acc_paq ON (paq_pres.id_acc_paq = acc_paq.id_acc_paq)
		INNER JOIN an_accesorio acc ON (acc_paq.id_accesorio = acc.id_accesorio)
	WHERE id_presupuesto = %s;",
		valTpDato($idPresupuesto, "int"));
	$rp = mysql_query($sqlp,$conex);
	if (!$rp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	while ($rowp = mysql_fetch_array($rp)) {
		$echoacc .= sprintf("newacc('acc%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');",
			$rowp['id_accesorio'],
			$rowp['id_acc_paq'],
			$rowp['precio_accesorio'],
			utf8_encode($rowp['nom_accesorio']),
			"3",
			$rowp['iva_accesorio'],
			$rowp['costo_accesorio'],
			$rowp['porcentaje_iva_accesorio'],
			$rowp['id_tipo_accesorio'],
			$rowa['id_condicion_pago'],
			$rowa['id_condicion_mostrar'],
			$rowp['id_paquete_presupuesto']);
	}
}
cerrar();

if ($_GET['view'] != "") {
	$loadscript = " onload=\"";
		$loadscript .= ($echoacc != "") ? $echoacc : "";
		$loadscript .= "percent(); reputacion('".$rep_val."','".$rep_tipo."',".$most.",'".$tipoCuentaCliente."'); asignarPrecio();";
		$loadscript .= ($_GET['view'] == "print") ? "print();" : "";
	$loadscript .= "\"";
	$modeform = " ";
	
	include "an_ventas_presupuesto_imprimir.php";
} else {
	$valores = array(
		"lstMesesFinanciar*".$lstMesesFinanciar,
		"txtInteresCuotaFinanciar*".$txtInteresCuotaFinanciar,
		"txtCuotasFinanciar*".$txtCuotasFinanciar);
	
	$loadscript = " onload=\"";
		$loadscript .= ($echoacc != "") ? $echoacc : "";
		$loadscript .= ($msg != "") ? $msg : "";
		$loadscript .= "percent(); reputacion('".$rep_val."','".$rep_tipo."',".$most.",'".$tipoCuentaCliente."'); asignarPrecio();";
		$loadscript .= ($txtInteresCuotaFinanciar > 0) ? "if (eval((typeof('asignarBanco') != 'undefined')) && window.asignarBanco) { asignarBanco('".$lstBancoFinanciar."','".implode("|",$valores)."'); }" : "";
	$loadscript .= "\"";
	$modeform = "Editar Factura Proforma";
	
	//validfacion de sesión
	validaModulo("an_presupuesto_venta_list",editar);
	//validfacion de sesión
	
	include "an_ventas_presupuesto_insertar.php";
}
?>