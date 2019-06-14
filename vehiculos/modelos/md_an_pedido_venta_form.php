<?php
class ModeloPedidoVentaVeh {
	
	function guardarCliente($valBusq) {
		$valCadBusq = explode("|", $valBusq);
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(sa_v_inf_final_orden.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_orden.id_empresa))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(sa_v_inf_final_orden_dev.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_orden_dev.id_empresa))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
		}
		
		if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_orden.fecha_filtro,%s) = %s",
				valTpDato("%m-%Y", "text"),
				valTpDato(date("m-Y", strtotime("01-".$valCadBusq[1])), "date"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_orden_dev.fecha_filtro,%s) = %s",
				valTpDato("%m-%Y", "text"),
				valTpDato(date("m-Y", strtotime("01-".$valCadBusq[1])), "date"));
		}
		
		$query = sprintf("SELECT
			q.id_modelo,
			q.nom_modelo,
			SUM(q.cantidad_ordenes) AS cantidad_ordenes
		FROM (
			SELECT 
				vw_iv_modelo.id_modelo,
				vw_iv_modelo.nom_modelo,
				COUNT(sa_v_inf_final_orden.id_orden) AS cantidad_ordenes
			FROM sa_v_informe_final_ordenes sa_v_inf_final_orden
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (sa_v_inf_final_orden.id_unidad_basica = vw_iv_modelo.id_uni_bas) %s
			GROUP BY vw_iv_modelo.id_modelo
			
			UNION
			
			SELECT
				vw_iv_modelo.id_modelo,
				vw_iv_modelo.nom_modelo,
				(-1) * COUNT(sa_v_inf_final_orden_dev.id_orden) AS cantidad_ordenes
			FROM sa_v_informe_final_ordenes_dev sa_v_inf_final_orden_dev
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (sa_v_inf_final_orden_dev.id_unidad_basica = vw_iv_modelo.id_uni_bas) %s
			GROUP BY vw_iv_modelo.id_modelo) AS q
		GROUP BY q.id_modelo", $sqlBusq, $sqlBusq2);
		
		$query .= ($this->campOrd != "") ? sprintf(" ORDER BY %s %s", $this->campOrd, $this->tpOrd) : "";
		
        return $query;
	}
}
?>