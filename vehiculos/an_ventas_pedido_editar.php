<?php
require_once("../connections/conex.php");

require_once("../inc_sesion.php");

$idFactura = $_GET['idFactura'];

if ($idFactura > 0) {
	// BUSCA LOS DATOS DE LA FACTURA
	$queryFactura = sprintf("SELECT cxc_fact.*,
		(CASE cxc_fact.estadoFactura
			WHEN 0 THEN 'No Cancelado'
			WHEN 1 THEN 'Cancelado'
			WHEN 2 THEN 'Cancelado Parcial'
		END) AS estado_fact_vent
	FROM cj_cc_encabezadofactura cxc_fact
	WHERE cxc_fact.idFactura = %s
		AND cxc_fact.anulada LIKE 'NO';",
		valTpDato($idFactura, "int"));
	$rsFactura = mysql_query($queryFactura, $conex);
	if (!$rsFactura) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowFactura = mysql_fetch_array($rsFactura);
	
	$_GET['id'] = $rowFactura['numeroPedido'];
	$idEmpresa = $rowFactura['id_empresa'];
		
	// VERIFICA VALORES DE CONFIGURACION (Editar Factura de Venta de Vehículos)
	$queryConfig209 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 209 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa,"int"));
	$rsConfig209 = mysql_query($queryConfig209, $conex);
	if (!$rsConfig209) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsConfig209 = mysql_num_rows($rsConfig209);
	$rowConfig209 = mysql_fetch_assoc($rsConfig209);
		$valor = explode("|",$rowConfig209['valor']);
		$estatus209 = $valor[0];
		$cantDiasMaximo = $valor[1];
		$cantMesesAnteriores = $valor[2];
	
	if ($estatus209 == 1) {
		$txtFechaProveedor = date(str_replace("d","01",spanDateFormat), strtotime($rowFactura['fechaRegistroFactura']));
		if ((date("Y", strtotime($txtFechaProveedor)) == date("Y", strtotime("-".$cantMesesAnteriores." month", strtotime(date(spanDateFormat))))
			&& date("m", strtotime($txtFechaProveedor)) == date("m", strtotime("-".$cantMesesAnteriores." month", strtotime(date(spanDateFormat)))))
		|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") <= $cantMesesAnteriores) { // VERIFICA SI ES DE MESES ANTERIORES
			if (restaFechas(spanDateFormat, date(str_replace("d","01",spanDateFormat)), date(spanDateFormat), "dias") <= $cantDiasMaximo
			|| date("m", strtotime($txtFechaProveedor)) == date("m")) { // VERIFICA SI EL REGISTRO DE COMPRA ESTA ENTRE LOS DIAS PERMITIDOS DEL MES EN CURSO
				if (!($rowFactura['id_pedido_reemplazo'] > 0)) {
					$permitir = true;
				}
			}
		}
	}
	
	if (!($permitir == true)) {
		echo "<script>
			alert('Esta factura no puede ser editada.');
			if (top.history.back()) { top.history.back(); } else { window.location.href='an_factura_venta_historico_list.php'; }
		</script>";
		exit;
	}
}

$idPedido = $_GET['id'];

$query = sprintf("SELECT ped_vent.*,
	moneda_local.descripcion AS descripcion_moneda_local,
	moneda_local.abreviacion AS abreviacion_moneda_local,
	moneda_extranjera.descripcion AS descripcion_moneda_extranjera,
	moneda_extranjera.abreviacion AS abreviacion_moneda_extranjera,
	pres_vent.numeracion_presupuesto,
	uni_fis.id_uni_bas,
	IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda
FROM an_pedido ped_vent
	LEFT JOIN an_presupuesto pres_vent ON (ped_vent.id_presupuesto = pres_vent.id_presupuesto)
	LEFT JOIN an_unidad_fisica uni_fis ON (ped_vent.id_unidad_fisica = uni_fis.id_unidad_fisica)
	LEFT JOIN pg_monedas moneda_local ON (ped_vent.id_moneda = moneda_local.idmoneda)
	LEFT JOIN pg_monedas moneda_extranjera ON (ped_vent.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
WHERE ped_vent.id_pedido = %s;",
	valTpDato($idPedido, "int"));
$r = mysql_query($query, $conex);
if (!$r) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowPedido = mysql_fetch_assoc($r);

$idEmpresa = $rowPedido['id_empresa'];
$idUnidadBasica = $rowPedido['id_uni_bas'];
$idUnidadFisica = $rowPedido['id_unidad_fisica'];
$abrevMonedaLocal = $rowPedido['abreviacion_moneda'];

$lstPrecioVenta = $rowPedido['precio_venta'];

if ($_GET['view'] == "") {
	if (in_array(idArrayPais,array(1,2,3))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
		if ($idFactura > 0) {
			echo sprintf("<script>window.location.href='an_pedido_venta_form.php?idFactura=%s';</script>", $idFactura);
		} else {
			echo sprintf("<script>window.location.href='an_pedido_venta_form.php?id=%s';</script>", $idPedido);
		}
	}
	
	$loadscript = "onload=\"";
		$loadscript .= ($_GET['view'] == "print") ? " window.print();" : "";
		$loadscript .= " percent();";
	$loadscript .= "\"";
	
	include "an_ventas_pedido_insertar.php";
} else if (in_array($_GET['view'],array("view","print","import"))) {
	$idCliente = $rowPedido['id_cliente'];
	
	$txtPorcIva = $rowPedido['porcentaje_iva'];
	$txtPorcIvaLujo = $rowPedido['porcentaje_impuesto_lujo'];
	
	// BUSCA LOS DATOS DEL CLIENTE
	$query = sprintf("SELECT
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
	$r = mysql_query($query, $conex);
	if (!$r) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowCliente = mysql_fetch_assoc($r);
	
	$nombreCliente = utf8_encode($rowCliente['nombre_cliente']);
	$hddPagaImpuesto = ($rowCliente['paga_impuesto']);
	
	$most = "false";
	if ($rowCliente['tipo_cuenta_cliente'] == 1) {
		$rep_val = '#FFFFCC'; $rep_tipo = $rowCliente['reputacionCliente'];
	} else {
		switch ($rowCliente['id_reputacion_cliente']) {
			case 1 : $rep_val = '#FFEEEE'; $rep_tipo = $rowCliente['reputacionCliente']; $most = "true"; break;
			case 2 : $rep_val = '#DDEEFF'; $rep_tipo = $rowCliente['reputacionCliente']; break;
			case 3 : $rep_val = '#006500'; $rep_tipo = $rowCliente['reputacionCliente']; break;
		}
	}
	
	// VERIFICA SI TIENE IMPUESTO
	if (getmysql("SELECT UPPER(isan_uni_bas)
	FROM an_uni_bas
		INNER JOIN an_unidad_fisica ON (an_uni_bas.id_uni_bas = an_unidad_fisica.id_uni_bas)
	WHERE id_unidad_fisica = ".valTpDato($idUnidadFisica,"int").";") == 1 && $hddPagaImpuesto == 1) {
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
		
		$txtNuevoPorcIva = $row['iva'];
		$cond = (strlen($eviva) > 0) ? " e " : "";
		$eviva .= $cond.$row['observacion'];
	} else {
		$txtNuevoPorcIva = 0;
		$eviva .= "Exento";
	}
	
	if (getmysql("SELECT impuesto_lujo
	FROM an_uni_bas
		INNER JOIN an_unidad_fisica ON (an_uni_bas.id_uni_bas = an_unidad_fisica.id_uni_bas)
	WHERE id_unidad_fisica = ".valTpDato($idUnidadFisica,"int").";") == 1 && $hddPagaImpuesto == 1) {
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
		
		$txtNuevoPorcIvaLujo = $row['iva'];
		$cond = (strlen($eviva) > 0) ? " e " : "";
		$eviva .= $cond.$row['observacion'];
	} else {
		$txtNuevoPorcIvaLujo = 0;
	}
	
	if ($idPedido > 0) {
		//cargando accesorios:
		$queryA = sprintf("SELECT
			acc_ped.id_accesorio_pedido,
			acc_ped.id_pedido,
			acc_ped.id_accesorio,
			CONCAT(acc.nom_accesorio, IF (acc_ped.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
			acc.des_accesorio,
			acc_ped.id_tipo_accesorio,
			(CASE acc_ped.id_tipo_accesorio
				WHEN 1 THEN	'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
			END) AS descripcion_tipo_accesorio,
			acc_ped.iva_accesorio,
			acc_ped.porcentaje_iva_accesorio,
			(acc_ped.precio_accesorio + (acc_ped.precio_accesorio * acc_ped.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
			acc_ped.costo_accesorio,
			acc_ped.id_condicion_pago,
			acc_ped.id_condicion_mostrar
		FROM an_accesorio_pedido acc_ped
			INNER JOIN an_accesorio acc ON (acc_ped.id_accesorio = acc.id_accesorio)
		WHERE acc_ped.id_pedido = %s;",
			valTpDato($idPedido, "int"));
		$ra = mysql_query($queryA, $conex);
		if (!$ra) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		while ($rowa = mysql_fetch_assoc($ra)) {
			$scriptAdicional .= sprintf("newacc('acc%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');",
				$rowa['id_accesorio'],
				"",
				$rowa['precio_con_iva'],
				utf8_encode($rowa['nom_accesorio']),
				"3",
				$rowa['iva_accesorio'],
				$rowa['costo_accesorio'],
				$rowa['porcentaje_iva_accesorio'],
				$rowa['id_tipo_accesorio'],
				$rowa['id_condicion_pago'],
				$rowa['id_condicion_mostrar'],
				$rowa['id_accesorio_pedido']);
		}
		
		$queryP = sprintf("SELECT
			paq_ped.id_paquete_pedido,
			paq_ped.id_pedido,
			paq_ped.id_acc_paq,
			acc.id_accesorio,
			CONCAT(acc.nom_accesorio, IF (paq_ped.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
			acc.des_accesorio,
			paq_ped.id_tipo_accesorio,
			(CASE paq_ped.id_tipo_accesorio
				WHEN 1 THEN	'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
			END) AS descripcion_tipo_accesorio,
			paq_ped.iva_accesorio,
			paq_ped.porcentaje_iva_accesorio,
			(paq_ped.precio_accesorio + (paq_ped.precio_accesorio * paq_ped.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
			paq_ped.costo_accesorio,
			paq_ped.id_condicion_pago,
			paq_ped.id_condicion_mostrar
		FROM an_paquete_pedido paq_ped
			INNER JOIN an_acc_paq acc_paq ON (paq_ped.id_acc_paq = acc_paq.id_acc_paq)
			INNER JOIN an_accesorio acc on (acc_paq.id_accesorio = acc.id_accesorio)
		WHERE paq_ped.id_pedido = %s;",
			valTpDato($idPedido, "int"));
		$rp = mysql_query($queryP, $conex);
		if (!$rp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		while ($rowa = mysql_fetch_assoc($rp)) {
			$scriptAdicional .= sprintf("newacc('acc%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');",
				$rowa['id_accesorio'],
				$rowa['id_acc_paq'],
				$rowa['precio_con_iva'],
				utf8_encode($rowa['nom_accesorio']),
				3,
				$rowa['iva_accesorio'],
				$rowa['costo_accesorio'],
				$rowa['porcentaje_iva_accesorio'],
				$rowa['id_tipo_accesorio'],
				$rowa['id_condicion_pago'],
				$rowa['id_condicion_mostrar'],
				$rowa['id_paquete_pedido']);
		}
	}
	
	// DATOS DEL PEDIDO
	$numeroPedido = $rowPedido['numeracion_pedido'];
	$idPresupuesto = $rowPedido['id_presupuesto'];
	$numeroPresupuesto = $rowPedido['numeracion_presupuesto'];
	
	$idAsesorVentas = $rowPedido['asesor_ventas'];
	$idClaveMovimiento = $rowPedido['id_clave_movimiento'];
	
	// VENTA DE LA UNIDAD
	$txtPrecioBase = $rowPedido['precio_venta'];
	$txtDescuento = $rowPedido['monto_descuento'];
	$txtPorcIva = $rowPedido['porcentaje_iva'];
	$txtSubTotalIva = (($txtPrecioBase - $txtDescuento) * $txtPorcIva) / 100;
	$txtPorcIvaLujo = $rowPedido['porcentaje_impuesto_lujo'];
	$txtSubTotalIvaLujo = (($txtPrecioBase - $txtDescuento) * $txtPorcIvaLujo) / 100;
	$txtMontoImpuesto = $txtSubTotalIva + $txtSubTotalIvaLujo;
	$txtPrecioVenta = ($txtPrecioBase - $txtDescuento) + $txtMontoImpuesto;
	
	$hddTipoInicial = $rowPedido['tipo_inicial'];
	$txtPorcInicial = $rowPedido['porcentaje_inicial'];
	$txtMontoInicial = $rowPedido['inicial'];
	$txtMontoCashBack = $rowPedido['monto_cash_back'];
	
	$txtTotalInicialGastos = $rowPedido['total_inicial_gastos'];
	$txtSaldoFinanciar = $rowPedido['saldo_financiar'];
	$txtTotalAdicionalContrato = $rowPedido['total_adicional_contrato'];
	
	$txtTotalPedido = $rowPedido['total_pedido'];
	
	// BUSCA LOS DATOS DEL PRESUPUESTO DE ACCESORIOS
	$exacc1 = $rowPedido['exacc1'];
	$vexacc1 = $rowPedido['vexacc1'];
	$exacc2 = $rowPedido['exacc2'];
	$vexacc2 = $rowPedido['vexacc2'];
	$exacc3 = $rowPedido['exacc3'];
	$vexacc3 = $rowPedido['vexacc3'];
	$exacc4 = $rowPedido['exacc4'];
	$vexacc4 = $rowPedido['vexacc4'];
	$total_accesorio = $rowPedido['total_accesorio'];
	$empresa_accesorio = $rowPedido['empresa_accesorio'];
	
	// FORMA DE PAGO
	$txtMontoAnticipo = $rowPedido['anticipo'];
	$txtMontoComplementoInicial = $rowPedido['complemento_inicial'];
	
	// FINANCIAMIENTO
	$lstBancoFinanciar = $rowPedido['id_banco_financiar'];
	$lstMesesFinanciar = $rowPedido['meses_financiar'];
	$txtInteresCuotaFinanciar = $rowPedido['interes_cuota_financiar'];
	$txtCuotasFinanciar = numformat($rowPedido['cuotas_financiar'],2);
	$lstMesesFinanciar2 = $rowPedido['meses_financiar2'];
	$txtInteresCuotaFinanciar2 = $rowPedido['interes_cuota_financiar2'];
	$txtCuotasFinanciar2 = numformat($rowPedido['cuotas_financiar2'],2);
	$txtPorcFLAT = $rowPedido['porcentaje_flat'];
	$txtMontoFLAT = $rowPedido['monto_flat'];
	$valores = array(
		"lstMesesFinanciar*".$lstMesesFinanciar,
		"txtInteresCuotaFinanciar*".$txtInteresCuotaFinanciar,
		"txtCuotasFinanciar*".$txtCuotasFinanciar,
		"lstMesesFinanciar2*".$lstMesesFinanciar2,
		"txtInteresCuotaFinanciar2*".$txtInteresCuotaFinanciar2,
		"txtCuotasFinanciar2*".$txtCuotasFinanciar2);
	
	$txtPrecioTotal = $rowPedido['forma_pago_precio_total'];
	
	// SEGURO
	$idPoliza = $rowPedido['id_poliza'];
	$txtMontoSeguro = $rowPedido['monto_seguro'];
	$txtInicialPoliza = $rowPedido['inicial_poliza'];
	$txtMesesPoliza = $rowPedido['meses_poliza'];
	$txtCuotasPoliza = $rowPedido['cuotas_poliza'];
	
	$txtObservacion = $rowPedido['observaciones'];
	
	$idGerenteVenta = $rowPedido['gerente_ventas'];
	$txtFechaVenta = date(spanDateFormat, strtotime($rowPedido['fecha_gerente_ventas']));
	$idGerenteAdministracion = $rowPedido['administracion'];
	$txtFechaAdministracion = date(spanDateFormat, strtotime($rowPedido['fecha_administracion']));
	
	$txtFechaReserva = date(spanDateFormat, strtotime($rowPedido['fecha_reserva_venta']));
	$txtFechaEntrega = date(spanDateFormat, strtotime($rowPedido['fecha_entrega']));
	
	$txtPrecioRetoma = $rowPedido['precio_retoma'];
	$txtFechaRetoma = ($rowPedido['fecha_retoma'] != "") ? date(spanDateFormat, strtotime($rowPedido['fecha_retoma'])) : "";
	
	if ($_GET['view'] == "" && $idUnidadFisica > 0) {
		if ($txtPorcIva != $txtNuevosPorcIva) {
			$txtPorcIva = $txtNuevoPorcIva;
			$arrayMsg[] = "Se ha actualizado el Impuesto en: ".$txtPorcIva."%";
		}
		if ($txtPorcIvaLujo != $txtNuevoPorcIvaLujo) {
			$txtPorcIvaLujo = $txtNuevoPorcIvaLujo;
			$arrayMsg[] = "Se ha actualizado el Impuesto al lujo en: ".$txtPorcIvaLujo."%";
		}
		(count($arrayMsg) > 0) ? "alert('".implode($arrayMsg,"\n")."');" : "";
	}
	
	$loadscript = "onload=\"";
		$loadscript .= ($scriptAdicional != "") ? $scriptAdicional : "";
		$loadscript .= ($arrayMsg != "") ? $arrayMsg : "";
		$loadscript .= ($_GET['view'] == "print") ? " window.print();" : "";
		$loadscript .= " percent(); reputacion('".$rep_val."','".$rep_tipo."',".$most.",'".$tipoCuentaCliente."');";
		if ($txtPorcInicial < 100 && $_GET['view'] == "") {
			if ($lstBancoFinanciar > 0) {
			} else {
				$loadscript .= "
				byId('cbxSinBancoFinanciar').checked = true;
				byId('aDesbloquearSinBancoFinanciar').style.display = 'none';
				byId('hddSinBancoFinanciar').value = 1;";
			}
		}
		$loadscript .= ($rowPedido['estado_pedido'] == 3) ? "alert('El pedido ".$idPedido." está desautorizado');" : "";
		$loadscript .= ($lstBancoFinanciar > 0) ? "if (eval((typeof('asignarBanco') != 'undefined')) && window.asignarBanco) { asignarBanco('".$lstBancoFinanciar."','".implode("|",$valores)."'); }" : "";
	$loadscript .= "\"";
	
	include "an_ventas_pedido_formato.php";
} ?>