<?php
require("../../connections/conex.php");

//$valCadBusq = array_values(json_decode($_GET['valBusq'], true));
//die(var_dump(json_decode($_GET['valBusq'], true)));

header('Content-type: application/vnd.ms-excel');
header("Content-Disposition: attachment; filename=\"Listado Cheques.xls\"");
header("Pragma: no-cache");
header("Expires: 0");

buscarCheque(json_decode($_GET['valBusq'], true));

function buscarCheque($valForm) {
	
	$valBusq = sprintf ("%s|%s|%s|%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['lstEstado'],
		$valForm['txtCriterio'],
		$valForm['txtFechaDesde'],
		$valForm['txtFechaHasta'],
		$valForm['txtIdProv'],
		$valForm['txtConceptoBuscar']);
	//ordenamiento en historico siempre mostrar primero el ultimo
	if($_GET['acc'] == 3){//3 es historico devolucion            
		echo listadoCheques(0,"fecha_registro","DESC",$valBusq);
	}else{
		echo listadoCheques(0,"numero_cheque","ASC",$valBusq);
	}
}

function listadoCheques($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	$acc= $_GET['acc'];
	
	if ($_GET['acc'] == 1){//CHEQUES INDIVIDUALES TIENE IMPRESION
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_propuesta_pago = 0 AND entregado != 1");
		
		$auxTd = true;
	}else if ($_GET['acc'] == 2){//IMPRESION DE CHEQUES PROPUESTA DE PAGO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_propuesta_pago > 0 AND entregado != 1");
		
		$auxTd = true;
	}else{//HISTORICO DE CHEQUES
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("entregado = 1");
		
		$auxTd = false;
	}
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estado_documento = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estado_documento = %s",
			valTpDato($valCadBusq[2], "numero_cheque"));
	}
	
	if ($valCadBusq[3] != "" && $valCadBusq[4] !=""){
		$sqlBusq .= $cond.sprintf("fecha_registro BETWEEN %s AND %s",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[3])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[4])), "date")); 
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_beneficiario_proveedor = %s",
			valTpDato($valCadBusq[5], "int"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("concepto LIKE %s",
			valTpDato("%".$valCadBusq[6]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		vw_te_cheques.*, 
		prov.id_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
		prov.nombre AS nombre_proveedor
	FROM vw_te_cheques
		INNER JOIN cp_proveedor prov ON (vw_te_cheques.id_beneficiario_proveedor = prov.id_proveedor) %s", $sqlBusq);
        
	if($campOrd == "fecha_cheque" OR $campOrd == "fecha_registro"){//agrupar por fecha y luego ordenar por numero
		$campOrd2 = $campOrd." ".$tpOrd.", numero_cheque DESC";
	}else{
		$campOrd2 = $campOrd." ".$tpOrd;
	}
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s ", $campOrd2) : "";
	$queryLimit = sprintf(" %s %s ", $query, $sqlOrd);
	$rsLimit = mysql_query($queryLimit);
	if(!$rsLimit) { return die(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery:".$queryLimit); }	
		
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if(!$rs) { return die(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"1\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">Estado</th>";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">Entregado</th>";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">Empresa</th>";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">Id</th>";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">Proveedor</th>";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">N&uacute;mero Cheque</th>";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">Monto Cheque</th>";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">Fecha Registro</th>";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">Fecha Aplicaci&oacute;n</th>";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">Fecha Conciliaci&oacute;n</th>";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">Concepto</th>";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">Banco Compa&ntilde;ia</th>";
		$htmlTh .= "<th style=\"background-color: #bfbfbf;\">Cuenta Compa&ntilde;ia</th>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
				
		switch($row['estado_documento']){
			case 1: $imgEstado = "Por Aplicar"; break;
			case 2: $imgEstado = "Aplicada"; break;
			case 3: $imgEstado = "Conciliada"; break;
			default : $imgEstado = "";
		}
		
		$imgEntregado = ($row['entregado'])	? "Entregado" : "Sin entregar";
		
		$fechaAplicacion = ($row['fecha_aplicacion']) ? date(spanDateFormat,strtotime($row['fecha_aplicacion'])) : "-" ;
		$fechaConciliacion = ($row['fecha_conciliacion']) ? date(spanDateFormat,strtotime($row['fecha_conciliacion'])) : "-";
		
		$htmlTb.= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".$imgEstado."</td>";
			$htmlTb .= "<td align=\"center\">".$imgEntregado."</td>";
			$htmlTb .= "<td align=\"left\">".($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_proveedor']."</td>";
			$htmlTb .= "<td align=\"left\">".($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_cheque']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['monto_cheque'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($row['fecha_registro']))."</td>";
			$htmlTb .= "<td align=\"center\">".$fechaAplicacion."</td>";
			$htmlTb .= "<td align=\"center\">".$fechaConciliacion."</td>";
			$htmlTb .= "<td align=\"left\">".($row['concepto'])."</td>";
			$htmlTb .= "<td align=\"left\">".($row['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numeroCuentaCompania']."</td>";
		$htmlTb .= "</tr>";	
	}
	
	$htmlTblFin .= "</table>";

	return $htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin;
}

?>