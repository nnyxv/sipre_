<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
// QUITAR EL COMENTARIO SI SE VA A UTILIZAR ESTE ARCHIVO PARA LA EJECUCION EN MASA
require_once("../../connections/conex.php");
require_once("../../controladores/ac_if_generar_cierre_mensual.php");

$idEmpresa = 2;
//$arrayAno = array("01-2015","02-2015","03-2015","04-2015","05-2015","06-2015","07-2015","08-2015","09-2015","10-2015","11-2015","12-2015");
$arrayAno = array("10-2016");
//$arrayAno = array("06-2015");

mysql_query("START TRANSACTION;");

if (isset($arrayAno)) {
	foreach ($arrayAno as $indiceAno => $valorAno) {
		$valFecha = explode("-", $valorAno);
		
		$mesCierre = $valFecha[0];
		$anoCierre = $valFecha[1];
		
		// INSERTA LOS DATOS DE LA FACTURACION DE TECNICOS
		$Result1 = facturacionTecnicos($idEmpresa, $mesCierre, $anoCierre);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]); 
		} else {
			$arrayEquipo = $Result1[1];
			$totalTotalUtsEquipos = $Result1[2];
			$totalTotalBsEquipos = $Result1[3];
		}
		if (isset($arrayEquipo)) {
			foreach ($arrayEquipo as $indiceEquipo => $valorEquipo) {
				$arrayTecnico = $arrayEquipo[$indiceEquipo]['tecnicos'];
				$porcTotalEquipo = 0;
				$arrayMec = NULL;
				if (isset($arrayTecnico)) {
					foreach ($arrayTecnico as $indiceTecnico => $valorTecnico) {
						$arrayTotalTecnico[$valorTecnico['id_empleado']] = array(
							"id_empleado" => $valorTecnico['id_empleado'],
							"id_equipo_mecanico" => $valorTecnico['id_equipo_mecanico'],
							"total_ut" => $arrayTotalTecnico[$valorTecnico['id_empleado']]['total_ut'] + $valorTecnico['total_ut'],
							"total_mo" => $arrayTotalTecnico[$valorTecnico['id_empleado']]['total_mo'] + $valorTecnico['total_mo']);
					}
				}
			}
		}
		
		if (isset($arrayTotalTecnico)) {
			$query = sprintf("SELECT * FROM iv_cierre_mensual
			WHERE id_empresa = %s
				AND mes = %s
				AND ano = %s;",
				valTpDato($idEmpresa, "int"),
				valTpDato($mesCierre, "int"),
				valTpDato($anoCierre, "int"));
			$rs = mysql_query($query);
			if (!$rs) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$idCierreMensual = $row['id_cierre_mensual'];
			
			foreach ($arrayTotalTecnico as $indiceTotalTecnico => $valorTotalTecnico) {
				$insertSQL = sprintf("INSERT INTO iv_cierre_mensual_facturacion (id_cierre_mensual, id_empleado, id_modulo, id_equipo_mecanico, total_ut, total_mano_obra)
				VALUE (%s, %s, %s, %s, %s, %s);",
					valTpDato($idCierreMensual, "int"),
					valTpDato($valorTotalTecnico['id_empleado'], "int"),
					valTpDato(1, "int"), // 0 = Repuestos, 1 = Servicios
					valTpDato($valorTotalTecnico['id_equipo_mecanico'], "int"),
					valTpDato($valorTotalTecnico['total_ut'], "real_inglesa"),
					valTpDato($valorTotalTecnico['total_mo'], "real_inglesa"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			}
		}
		
		
		// INSERTA LAS ORDENES DE SERVICIOS ABIERTAS Y CERRADAS
		/*$Result1 = cierreOrdenesServicio($idEmpresa, $valFecha[0], $valFecha[1]);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]); 
		} else {
			$arrayTipoOrden = $Result1[1];
			$totalTipoOrdenAbierta = $Result1[2];
			$totalTipoOrdenCerrada = $Result1[3];
			$totalFallaTipoOrdenAbierta = $Result1[4];
			$totalFallaTipoOrdenCerrada = $Result1[5];
			$totalUtsTipoOrdenCerrada = $Result1[6];
		}
		if (isset($arrayTipoOrden)) {
			foreach ($arrayTipoOrden as $indice => $valor) {
				$queryCierreMensOrden = sprintf("SELECT 
					cierre_mens_orden.id_cierre_mensual_orden
				FROM iv_cierre_mensual cierre_mens
					INNER JOIN iv_cierre_mensual_orden cierre_mens_orden ON (cierre_mens.id_cierre_mensual = cierre_mens_orden.id_cierre_mensual)
				WHERE cierre_mens.id_empresa = %s
					AND cierre_mens.mes = %s
					AND cierre_mens.ano = %s
					AND cierre_mens_orden.id_tipo_orden = %s;",
					valTpDato($idEmpresa, "int"),
					valTpDato($valFecha[0], "date"),
					valTpDato($valFecha[1], "date"),
					valTpDato($indice, "int"));
				$rsCierreMensOrden = mysql_query($queryCierreMensOrden);
				if (!$rsCierreMensOrden) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsCierreMensOrden = mysql_num_rows($rsCierreMensOrden);
				$rowCierreMensOrden = mysql_fetch_assoc($rsCierreMensOrden);
				
				if ($totalRowsCierreMensOrden > 0) {
					$updateSQL = sprintf("UPDATE iv_cierre_mensual_orden SET
						cantidad_abiertas = %s,
						cantidad_fallas_abiertas = %s
					WHERE id_cierre_mensual_orden = %s;",
						valTpDato($arrayTipoOrden[$indice]['cantidad_abiertas'], "real_inglesa"),
						valTpDato($arrayTipoOrden[$indice]['cantidad_fallas_abiertas'], "real_inglesa"),
						valTpDato($rowCierreMensOrden['id_cierre_mensual_orden'], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					
					echo "<pre>".($updateSQL)."</pre>";
				} else {
					$insertSQL = sprintf("INSERT INTO iv_cierre_mensual_orden (id_cierre_mensual, id_tipo_orden, cantidad_abiertas, cantidad_cerradas, cantidad_fallas_abiertas, cantidad_fallas_cerradas, cantidad_uts_cerradas)
					VALUE (%s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idCierreMensual, "int"),
						valTpDato($indice, "int"),
						valTpDato($arrayTipoOrden[$indice]['cantidad_abiertas'], "real_inglesa"),
						valTpDato($arrayTipoOrden[$indice]['cantidad_cerradas'], "real_inglesa"),
						valTpDato($arrayTipoOrden[$indice]['cantidad_fallas_abiertas'], "real_inglesa"),
						valTpDato($arrayTipoOrden[$indice]['cantidad_fallas_cerradas'], "real_inglesa"),
						valTpDato($arrayTipoOrden[$indice]['cantidad_uts_cerradas'], "real_inglesa"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}*/
		
		
		
		/*
		// INSERTA LOS DATOS DE LA FACTURACION DE ASESORES
		$Result1 = facturacionAsesores($idEmpresa, $valFecha[0], $valFecha[1]);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]); 
		} else {
			$arrayVentaAsesor = $Result1[1];
			//$totalVentaAsesores = $Result1[2];
		}
		
		if (isset($arrayVentaAsesor)) {
			foreach ($arrayVentaAsesor as $indice => $valor) {
				$sqlBusq6 = "";
				$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
				$sqlBusq6 .= $cond.sprintf("orden.id_tipo_orden = tipo_orden.id_tipo_orden
				AND cxc_fact.idDepartamentoOrigenFactura IN (1)
				AND cxc_fact.aplicaLibros = 1");
				
				$sqlBusq7 = "";
				$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
				$sqlBusq7 .= $cond.sprintf("orden.id_tipo_orden = tipo_orden.id_tipo_orden");
				
				$sqlBusq8 = "";
				$cond = (strlen($sqlBusq8) > 0) ? " AND " : " WHERE ";
				$sqlBusq8 .= $cond.sprintf("orden.id_tipo_orden = tipo_orden.id_tipo_orden
				AND cxc_nc.idDepartamentoNotaCredito IN (1)
				AND cxc_fact.aplicaLibros = 1
				AND cxc_nc.tipoDocumento LIKE 'FA'");
				
				$sqlBusq9 = "";
				$cond = (strlen($sqlBusq9) > 0) ? " AND " : " WHERE ";
				$sqlBusq9 .= $cond.sprintf("orden.id_tipo_orden = tipo_orden.id_tipo_orden");
				
				$sqlBusq10 = "";
				
				
				$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
				$sqlBusq6 .= $cond.sprintf("(cxc_fact.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = cxc_fact.id_empresa))",
					valTpDato($idEmpresa, "int"),
					valTpDato($idEmpresa, "int"));
				
				$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
				$sqlBusq7 .= $cond.sprintf("(vale_salida.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = vale_salida.id_empresa))",
					valTpDato($idEmpresa, "int"),
					valTpDato($idEmpresa, "int"));
					
				$cond = (strlen($sqlBusq8) > 0) ? " AND " : " WHERE ";
				$sqlBusq8 .= $cond.sprintf("(cxc_nc.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = cxc_nc.id_empresa))",
					valTpDato($idEmpresa, "int"),
					valTpDato($idEmpresa, "int"));
				
				$cond = (strlen($sqlBusq9) > 0) ? " AND " : " WHERE ";
				$sqlBusq9 .= $cond.sprintf("(vale_entrada.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = vale_entrada.id_empresa))",
					valTpDato($idEmpresa, "int"),
					valTpDato($idEmpresa, "int"));
					
				$cond = (strlen($sqlBusq10) > 0) ? " AND " : " WHERE ";
				$sqlBusq10 .= $cond.sprintf("(tipo_orden.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = tipo_orden.id_empresa))",
					valTpDato($idEmpresa, "int"),
					valTpDato($idEmpresa, "int"));
				
				
				$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
				$sqlBusq6 .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) = %s
				AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
					valTpDato($valFecha[0], "date"),
					valTpDato($valFecha[1], "date"));
				
				$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
				$sqlBusq7 .= $cond.sprintf("MONTH(vale_salida.fecha_vale) = %s
				AND YEAR(vale_salida.fecha_vale) = %s",
					valTpDato($valFecha[0], "date"),
					valTpDato($valFecha[1], "date"));
				
				$cond = (strlen($sqlBusq8) > 0) ? " AND " : " WHERE ";
				$sqlBusq8 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) = %s
				AND YEAR(cxc_nc.fechaNotaCredito) = %s",
					valTpDato($valFecha[0], "date"),
					valTpDato($valFecha[1], "date"));
				
				$cond = (strlen($sqlBusq9) > 0) ? " AND " : " WHERE ";
				$sqlBusq9 .= $cond.sprintf("MONTH(vale_entrada.fecha_creada) = %s
				AND YEAR(vale_entrada.fecha_creada) <= %s",
					valTpDato($valFecha[0], "date"),
					valTpDato($valFecha[1], "date"));
				
				
				$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
				$sqlBusq6 .= $cond.sprintf("orden.id_empleado = %s",
					valTpDato($arrayVentaAsesor[$indice]['id_empleado'], "int"));
				
				$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
				$sqlBusq7 .= $cond.sprintf("orden.id_empleado = %s",
					valTpDato($arrayVentaAsesor[$indice]['id_empleado'], "int"));
				
				$cond = (strlen($sqlBusq8) > 0) ? " AND " : " WHERE ";
				$sqlBusq8 .= $cond.sprintf("orden.id_empleado = %s",
					valTpDato($arrayVentaAsesor[$indice]['id_empleado'], "int"));
				
				$cond = (strlen($sqlBusq9) > 0) ? " AND " : " WHERE ";
				$sqlBusq9 .= $cond.sprintf("orden.id_empleado = %s",
					valTpDato($arrayVentaAsesor[$indice]['id_empleado'], "int"));
					
				
				$cond = (strlen($sqlBusq10) > 0) ? " AND " : " WHERE ";
				$sqlBusq10 .= $cond.sprintf("tipo_orden.id_tipo_orden = %s",
					valTpDato($arrayVentaAsesor[$indice]['id_tipo_orden'], "int"));
					
				
				// BUSCA LAS ORDENES CERRARAS DEL ASESOR EN EL MES
				$queryOrdenServ = sprintf("SELECT
					filtro_orden.id_filtro_orden,
					tipo_orden.id_tipo_orden,
					tipo_orden.nombre_tipo_orden,
					
					IFNULL((SELECT COUNT(orden.id_orden)
							FROM cj_cc_encabezadofactura cxc_fact
								INNER JOIN sa_orden orden ON (cxc_fact.numeroPedido = orden.id_orden) ".$sqlBusq6."), 0)
					+ IFNULL((SELECT COUNT(orden.id_orden)
							FROM sa_orden orden
								INNER JOIN sa_vale_salida vale_salida ON (orden.id_orden = vale_salida.id_orden) ".$sqlBusq7."), 0)
					- IFNULL((SELECT COUNT(orden.id_orden)
							FROM cj_cc_encabezadofactura cxc_fact
								INNER JOIN sa_orden orden ON (cxc_fact.numeroPedido = orden.id_orden)
								INNER JOIN cj_cc_notacredito cxc_nc ON (cxc_fact.idFactura = cxc_nc.idDocumento) ".$sqlBusq8."), 0)
					- IFNULL((SELECT COUNT(orden.id_orden)
							FROM sa_vale_entrada vale_entrada
								INNER JOIN sa_vale_salida vale_salida ON (vale_entrada.id_vale_salida = vale_salida.id_vale_salida)
								INNER JOIN sa_orden orden ON (vale_salida.id_orden = orden.id_orden) ".$sqlBusq9."), 0) AS cantidad_cerradas
				FROM sa_tipo_orden tipo_orden
					INNER JOIN sa_filtro_orden filtro_orden ON (tipo_orden.id_filtro_orden = filtro_orden.id_filtro_orden) %s", $sqlBusq10);
				$rsOrdenServ = mysql_query($queryOrdenServ);
				if (!$rsOrdenServ) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$rowOrdenServ = mysql_fetch_assoc($rsOrdenServ);
				
				$cantOrdenes = $rowOrdenServ['cantidad_cerradas'];
				
				//echo var_dump($queryOrdenServ)."<br><br><br>";
				
				// VERIFICA SI EL DETALLE DE FACTURACION EXISTE
				$queryCierreMensualFact = sprintf("SELECT *
				FROM iv_cierre_mensual cierre_mensual
					INNER JOIN iv_cierre_mensual_facturacion cierre_mensual_fact ON (cierre_mensual.id_cierre_mensual = cierre_mensual_fact.id_cierre_mensual)
				WHERE cierre_mensual.id_empresa = %s
					AND cierre_mensual.mes = %s
					AND cierre_mensual.ano = %s
					AND cierre_mensual_fact.id_empleado = %s
					AND id_modulo = %s
					AND id_tipo_orden = %s;",
					valTpDato($idEmpresa, "int"),
					valTpDato($valFecha[0], "date"),
					valTpDato($valFecha[1], "date"),
					valTpDato($arrayVentaAsesor[$indice]['id_empleado'], "int"),
					valTpDato(1, "int"), // 0 = Repuestos, 1 = Servicios
					valTpDato($arrayVentaAsesor[$indice]['id_tipo_orden'], "int"));
				$rsCierreMensualFact = mysql_query($queryCierreMensualFact);
				if (!$rsCierreMensualFact) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsCierreMensualFact = mysql_num_rows($rsCierreMensualFact);
				$rowCierreMensualFact = mysql_fetch_assoc($rsCierreMensualFact);
				
				if ($totalRowsCierreMensualFact > 0) {
					$updateSQL = sprintf("UPDATE iv_cierre_mensual_facturacion cierre_mensual_fact
						INNER JOIN iv_cierre_mensual cierre_mensual ON (cierre_mensual_fact.id_cierre_mensual = cierre_mensual.id_cierre_mensual) SET
						cantidad_ordenes = %s,
						total_ut = %s,
						total_mano_obra = %s
					WHERE cierre_mensual.id_empresa = %s
						AND cierre_mensual.mes = %s
						AND cierre_mensual.ano = %s
						AND id_empleado = %s
						AND id_modulo = %s
						AND id_tipo_orden = %s;",
						valTpDato($cantOrdenes, "real_inglesa"),
						valTpDato($arrayVentaAsesor[$indice]['total_ut'], "real_inglesa"),
						valTpDato($arrayVentaAsesor[$indice]['total_mo'], "real_inglesa"),
						valTpDato($idEmpresa, "int"),
						valTpDato($valFecha[0], "date"),
						valTpDato($valFecha[1], "date"),
						valTpDato($arrayVentaAsesor[$indice]['id_empleado'], "int"),
						valTpDato(1, "int"), // 0 = Repuestos, 1 = Servicios
						valTpDato($arrayVentaAsesor[$indice]['id_tipo_orden'], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					
					echo "<pre>".($updateSQL)."</pre>";
				} else {
					$insertSQL = sprintf("INSERT INTO iv_cierre_mensual_facturacion (id_cierre_mensual, id_empleado, id_modulo, id_tipo_orden, cantidad_ordenes, total_ut, total_mano_obra, total_tot, total_nota, total_repuesto) 
					SELECT id_cierre_mensual, %s, %s, %s, %s, %s, %s, %s, %s, %s FROM iv_cierre_mensual cierre_mensual
					WHERE cierre_mensual.id_empresa = %s
						AND cierre_mensual.mes = %s
						AND cierre_mensual.ano = %s;",
						valTpDato($arrayVentaAsesor[$indice]['id_empleado'], "int"),
						valTpDato(1, "int"), // 0 = Repuestos, 1 = Servicios
						valTpDato($arrayVentaAsesor[$indice]['id_tipo_orden'], "int"),
						valTpDato($cantOrdenes, "real_inglesa"),
						valTpDato($arrayVentaAsesor[$indice]['total_ut'], "real_inglesa"),
						valTpDato($arrayVentaAsesor[$indice]['total_mo'], "real_inglesa"),
						valTpDato($arrayVentaAsesor[$indice]['total_tot'], "real_inglesa"),
						valTpDato($arrayVentaAsesor[$indice]['total_nota'], "real_inglesa"),
						valTpDato($arrayVentaAsesor[$indice]['total_repuestos'], "real_inglesa"),
						valTpDato($idEmpresa, "int"),
						valTpDato($valFecha[0], "date"),
						valTpDato($valFecha[1], "date"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					
					echo "<pre>".($insertSQL)."</pre>";
				}
			}
		}
		
		
		
		
		// INSERTA LOS DATOS DE LA FACTURACION DE TECNICOS
		$Result1 = facturacionTecnicos($idEmpresa, $valFecha[0], $valFecha[1]);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			errorCierreMensual($objResponse); return $objResponse->alert($Result1[1]); 
		} else {
			$arrayEquipo = $Result1[1];
			$totalTotalUtsEquipos = $Result1[2];
			$totalTotalBsEquipos = $Result1[3];
		}
		
		if (isset($arrayEquipo)) {
			foreach ($arrayEquipo as $indice => $valor) {
				$arrayTecnico = $arrayEquipo[$indice][1];
				$porcTotalEquipo = 0;
				$arrayMec = NULL;
				if (isset($arrayTecnico)) {
					foreach ($arrayTecnico as $indice2 => $valor2) {
						// VERIFICA SI EL DETALLE DE FACTURACION EXISTE
						$queryCierreMensualFact = sprintf("SELECT *
						FROM iv_cierre_mensual cierre_mensual
							INNER JOIN iv_cierre_mensual_facturacion cierre_mensual_fact ON (cierre_mensual.id_cierre_mensual = cierre_mensual_fact.id_cierre_mensual)
						WHERE cierre_mensual.id_empresa = %s
							AND cierre_mensual.mes = %s
							AND cierre_mensual.ano = %s
							AND cierre_mensual_fact.id_empleado = %s
							AND id_modulo = %s
							AND id_equipo_mecanico = %s;",
							valTpDato($idEmpresa, "int"),
							valTpDato($valFecha[0], "date"),
							valTpDato($valFecha[1], "date"),
							valTpDato($arrayTecnico[$indice2]['id_empleado'], "int"),
							valTpDato(1, "int"), // 0 = Repuestos, 1 = Servicios
							valTpDato($arrayTecnico[$indice2]['id_equipo_mecanico'], "int"));
						$rsCierreMensualFact = mysql_query($queryCierreMensualFact);
						if (!$rsCierreMensualFact) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$totalRowsCierreMensualFact = mysql_num_rows($rsCierreMensualFact);
						$rowCierreMensualFact = mysql_fetch_assoc($rsCierreMensualFact);
						
						if ($totalRowsCierreMensualFact > 0) {
							$updateSQL = sprintf("UPDATE iv_cierre_mensual_facturacion cierre_mensual_fact
								INNER JOIN iv_cierre_mensual cierre_mensual ON (cierre_mensual_fact.id_cierre_mensual = cierre_mensual.id_cierre_mensual) SET
								total_ut = %s,
								total_mano_obra = %s
							WHERE cierre_mensual.id_empresa = %s
								AND cierre_mensual.mes = %s
								AND cierre_mensual.ano = %s
								AND id_empleado = %s
								AND id_modulo = %s
								AND id_equipo_mecanico = %s;",
								valTpDato($arrayTecnico[$indice2]['total_uts'], "real_inglesa"),
								valTpDato($arrayTecnico[$indice2]['total_bs'], "real_inglesa"),
								valTpDato($idEmpresa, "int"),
								valTpDato($valFecha[0], "date"),
								valTpDato($valFecha[1], "date"),
								valTpDato($arrayTecnico[$indice2]['id_empleado'], "int"),
								valTpDato(1, "int"), // 0 = Repuestos, 1 = Servicios
								valTpDato($arrayTecnico[$indice2]['id_equipo_mecanico'], "int"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($updateSQL);
							if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							mysql_query("SET NAMES 'latin1';");
							
							echo "<pre>".($updateSQL)."</pre>";
						} else {
							$insertSQL = sprintf("INSERT INTO iv_cierre_mensual_facturacion (id_cierre_mensual, id_empleado, id_modulo, id_equipo_mecanico, total_ut, total_mano_obra)
							SELECT id_cierre_mensual, %s, %s, %s, %s, %s FROM iv_cierre_mensual cierre_mensual
							WHERE cierre_mensual.id_empresa = %s
								AND cierre_mensual.mes = %s
								AND cierre_mensual.ano = %s;",
								valTpDato($arrayTecnico[$indice2]['id_empleado'], "int"),
								valTpDato(1, "int"), // 0 = Repuestos, 1 = Servicios
								valTpDato($arrayTecnico[$indice2]['id_equipo_mecanico'], "int"),
								valTpDato($arrayTecnico[$indice2]['total_uts'], "real_inglesa"),
								valTpDato($arrayTecnico[$indice2]['total_bs'], "real_inglesa"),
								valTpDato($idEmpresa, "int"),
								valTpDato($valFecha[0], "date"),
								valTpDato($valFecha[1], "date"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							mysql_query("SET NAMES 'latin1';");
							
							echo "<pre>".($insertSQL)."</pre>";
						}
					}
				}
			}
		}*/
	}
}

mysql_query("COMMIT;");

echo "<h1>CIERRE MENSUAL ACTUALIZADO CON EXITO</h1>";
?>