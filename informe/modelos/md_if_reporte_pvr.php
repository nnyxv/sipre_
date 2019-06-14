<?php
class ModeloPVR {
	
	function queryAdicional() {
		$query = sprintf("SELECT acc.*,
			(CASE acc.id_tipo_accesorio
				WHEN 1 THEN	'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
			END) AS descripcion_tipo_accesorio,
			motivo.id_motivo,
			motivo.descripcion AS descripcion_motivo,
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
		FROM an_accesorio acc
			LEFT JOIN cj_cc_cliente cliente ON (acc.id_cliente = cliente.id)
			LEFT JOIN pg_motivo motivo ON (acc.id_motivo = motivo.id_motivo) %s", $sqlBusq);
		
		$query .= ($this->campOrd != "") ? sprintf(" ORDER BY %s %s", $this->campOrd, $this->tpOrd) : "";
		
        return $query;
	}
	
    function queryPVR($valBusq) {
		$valCadBusq = $valBusq;
		
		$idEmpresa = $valCadBusq['idEmpresa'];
		$fechaCierre = $valCadBusq['fechaCierre'];
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)");
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = cxc_fact.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		if ($fechaCierre != "-1" && $fechaCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("DATE_FORMAT(cxc_fact.fechaRegistroFactura, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
		}
		
		$query = sprintf("SELECT DISTINCT
			cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
			cxc_ec.tipoDocumentoN,
			cxc_ec.tipoDocumento,
			cxc_fact.idFactura,
			cxc_fact.id_empresa,
			cxc_fact.fechaRegistroFactura,
			cxc_fact.fechaVencimientoFactura,
			cxc_fact.numeroFactura,
			cxc_fact.numeroControl,
			cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
			cxc_fact.condicionDePago,
			ped_vent.id_pedido,
			ped_vent.numeracion_pedido,
			
			(SELECT an_ped_vent2.id_pedido FROM an_pedido an_ped_vent2
			WHERE an_ped_vent2.id_factura_cxc = cxc_fact.idFactura
				AND an_ped_vent2.estado_pedido IN (0,1,2,3,4)) AS id_pedido_reemplazo,
			
			pres_vent.id_presupuesto,
			pres_vent.numeracion_presupuesto,
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.placa,
			cond_unidad.descripcion AS condicion_unidad,
			ped_comp_det.flotilla,
			cxc_fact.estadoFactura,
			(CASE cxc_fact.estadoFactura
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado'
				WHEN 2 THEN 'Cancelado Parcial'
			END) AS descripcion_estado_factura,
			banco.nombreBanco,
			cxc_fact.aplicaLibros,
			cxc_fact.anulada,
			cxc_fact.estatus_factura,
			cxc_fact.observacionFactura,
			cxc_fact.montoTotalFactura,
			cxc_fact.saldoFactura,
			
			(IFNULL(cxc_fact.subtotalFactura, 0)
				- IFNULL(cxc_fact.descuentoFactura, 0)) AS total_neto,
			
			IFNULL((SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
					WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura), 0) AS total_iva,
			
			(IFNULL(cxc_fact.subtotalFactura, 0)
				- IFNULL(cxc_fact.descuentoFactura, 0)
				+ IFNULL((SELECT SUM(cxc_fact_gasto.monto) FROM cj_cc_factura_gasto cxc_fact_gasto
							WHERE cxc_fact_gasto.id_factura = cxc_fact.idFactura), 0)
				+ IFNULL((SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
							WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura), 0)) AS total,
			
			(SELECT COUNT(fact_det_acc2.id_factura) AS cantidad_accesorios FROM cj_cc_factura_detalle_accesorios fact_det_acc2
			WHERE fact_det_acc2.id_factura = cxc_fact.idFactura) AS cantidad_accesorios,
			
			vw_pg_empleado.nombre_empleado,
			vw_iv_modelo.nom_modelo,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_encabezadofactura cxc_fact
			LEFT JOIN cj_cc_factura_detalle_accesorios cxc_fact_det_acc ON (cxc_fact.idFactura = cxc_fact_det_acc.id_factura)
			LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
			INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
			LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_fact.idFactura = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'FA')
			LEFT JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
			LEFT JOIN an_presupuesto pres_vent ON (ped_vent.id_presupuesto = pres_vent.id_presupuesto)
			LEFT JOIN bancos banco ON (ped_vent.id_banco_financiar = banco.idBanco)
			LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
			LEFT JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			LEFT JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
			LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
			INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
		
		$query .= ($this->campOrd != "") ? sprintf(" ORDER BY %s %s", $this->campOrd, $this->tpOrd) : "";
		
        return $query;
    }
}
?>