<?php

function asignarEmpleado($idEmpleado) {
	$objResponse = new xajaxResponse();
	
	$queryEmpleado = sprintf("SELECT *, 
		(SELECT contrasena_especial FROM pg_empleado
		WHERE id_empleado = vw_pg_empleado.id_empleado) AS contrasena_especial
	FROM vw_pg_empleados vw_pg_empleado
		INNER JOIN pg_usuario usuario ON (vw_pg_empleado.id_empleado = usuario.id_empleado)
	WHERE vw_pg_empleado.id_empleado = %s;", 
		valTpDato($idEmpleado, "int"));
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	
	$objResponse->assign("hddIdEmpleado", "value", $rowEmpleado['id_empleado']);
	$objResponse->assign("txtNombreEmpleado", "value", utf8_encode($rowEmpleado['nombre_empleado']));
	$objResponse->assign("hddIdUsuario", "value", $rowEmpleado['id_usuario']);
	$objResponse->assign("txtNombreUsuario", "value", utf8_encode($rowEmpleado['nombre_usuario']));

	$objResponse->loadCommands(asignarPermisosUsuario($rowEmpleado['id_usuario']));

	$objResponse->script("byId('btnCancelarListaEmpleado').click();");	
	
	return $objResponse;
}

function asignarPermisosUsuario($idUsuario){
	$objResponse = new xajaxResponse();
	
	$sql = sprintf("SELECT id_empresa, nombre_empresa FROM pg_empresa");
	$rs = mysql_query($sql);
	if(!$rs){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
	while($row = mysql_fetch_assoc($rs)){
		$arrayEmpresa[$row["id_empresa"]] = $row["nombre_empresa"];
	}
	
	$sql = sprintf("SELECT id_empresa, id_tipo_contrato, nombre_tipo_contrato FROM al_tipo_contrato");
	$rs = mysql_query($sql);
	if(!$rs){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
	while($row = mysql_fetch_assoc($rs)){
		$arrayTiposContrato[] = $row;
	}
	
	$sql = sprintf("SELECT id_empresa, id_tipo_contrato, id_tipo_contrato_usuario 
					FROM al_tipo_contrato_usuario 
					WHERE id_usuario = %s",
				valTpDato($idUsuario,"int"));
	$rs = mysql_query($sql);
	if(!$rs){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	while($row = mysql_fetch_assoc($rs)){
		$arrayTiposContratoUsuario[] = $row;
	}
	
	$html = "";	
	foreach($arrayEmpresa as $idEmpresa => $nombreEmpresa){
		$html .= "<fieldset><legend>".utf8_encode($nombreEmpresa)."</legend>";
			foreach($arrayTiposContrato as $tiposContrato){
				if($idEmpresa == $tiposContrato["id_empresa"]){
					$checked = "";
					foreach($arrayTiposContratoUsuario as $tiposContratoUsuario){
						if($idEmpresa == $tiposContratoUsuario["id_empresa"] && $tiposContrato["id_tipo_contrato"] == $tiposContratoUsuario["id_tipo_contrato"]){
							$checked = "checked=\"checked\"";
						}
					}
					
					$html .= sprintf("<input value=\"%s|%s\" name=\"cbxItm[]\" onclick=\"eliminarPermiso(this.value);\" type=\"checkbox\" class=\"puntero\" %s /> %s<br>",				
						$tiposContrato["id_empresa"],						
						$tiposContrato["id_tipo_contrato"],
						$checked,
						utf8_encode($tiposContrato["nombre_tipo_contrato"]));
				}
			}	
		$html .= "</fieldset>";
	}
	
	$objResponse->assign("divPermisosUsuario","innerHTML",$html);
	
	return $objResponse;
}

function buscarEmpleado($frmBuscarEmpleado) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s", 
		$frmBuscarEmpleado['txtCriterioBuscarEmpleado']);
		
	$objResponse->loadCommands(listaEmpleado(0, "id_empleado", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarTipoContratoUsuario($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['lstEmpresaBuscar'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaTipoContratoUsuario(0, "id_tipo_contrato_usuario", "ASC", $valBusq));
	
	return $objResponse;
}

function eliminarTipoContratoUsuario($idTipoContratoUsuario) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"al_tipos_contrato_usuario_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");

	$deleteSQL = sprintf("DELETE FROM al_tipo_contrato_usuario WHERE id_tipo_contrato_usuario = %s",
		valTpDato($idTipoContratoUsuario, "int"));
	$Result1 = mysql_query($deleteSQL);	
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->script("byId('btnBuscar').click();");
	$objResponse->alert("Eliminado Correctamente");
	
	return $objResponse;
}

function frmTipoContratoUsuario($idUsuario) {
	$objResponse = new xajaxResponse();
	
	if ($idUsuario > 0) {
		if (!xvalidaAcceso($objResponse,"al_tipos_contrato_usuario_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarTipoContrato').click();"); return $objResponse; }
		
		$query = sprintf("SELECT id_empleado FROM pg_usuario WHERE id_usuario = %s;",
			valTpDato($idUsuario, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->loadCommands(asignarEmpleado($row["id_empleado"]));
						
	} else {
		if (!xvalidaAcceso($objResponse,"al_tipos_contrato_usuario_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarTipoContrato').click();"); return $objResponse; }
	}
	
	return $objResponse;
}

function guardarTipoContratoUsuario($frmTipoContratoUsuario) {
	$objResponse = new xajaxResponse();

	if (!xvalidaAcceso($objResponse,"al_tipos_contrato_usuario_list","editar")) { return $objResponse; }

	mysql_query("START TRANSACTION;");
	
	$idUsuario = $frmTipoContratoUsuario['hddIdUsuario'];
	
	if($frmTipoContratoUsuario["hddIdDetalleEliminar"] != ""){//formato: 1|1, 1|2, 1|3
		$itemsEliminar = explode(",", $frmTipoContratoUsuario["hddIdDetalleEliminar"]);
		foreach($itemsEliminar as $eliminar){
			$items = explode("|",$eliminar);
			$idEmpresaEliminar = $items[0];
			$idTipoContratoEliminar = $items[1];
			
			$sql = sprintf("DELETE FROM al_tipo_contrato_usuario 
							WHERE id_tipo_contrato = %s 
							AND id_usuario = %s 
							AND id_empresa = %s",
				valTpDato($idTipoContratoEliminar, "int"),
				valTpDato($idUsuario, "int"),
				valTpDato($idEmpresaEliminar, "int"));
			$rs = mysql_query($sql);
			if(!$rs){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
	}

	$sql = sprintf("SELECT id_empresa, id_tipo_contrato FROM al_tipo_contrato_usuario 
					WHERE id_usuario = %s",
		valTpDato($idUsuario, "int"));
	$rs = mysql_query($sql);
	if(!$rs){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }

	$arrayExistentes = array();
	while($row = mysql_fetch_assoc($rs)){
		$arrayExistentes[] = $row["id_empresa"]."|".$row["id_tipo_contrato"];
	}

	$arrayCbxItm = array_diff($frmTipoContratoUsuario["cbxItm"], $arrayExistentes);

	foreach($arrayCbxItm as $cbxItm){
		$items = explode("|",$cbxItm);
		$idEmpresa = $items[0];
		$idTipoContrato = $items[1];
		
		$sql = sprintf("INSERT INTO al_tipo_contrato_usuario (id_tipo_contrato, id_usuario, id_empresa)
		VALUE (%s, %s, %s);",
			valTpDato($idTipoContrato, "int"),
			valTpDato($idUsuario, "int"),
			valTpDato($idEmpresa, "int"));
		$rs = mysql_query($sql);
		if(!$rs){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Guardado correctamente.");
	
	$objResponse->script("
	byId('btnCancelarTipoContrato').click();
	byId('btnBuscar').click();");
	
	return $objResponse;
}

function listaEmpleado($pageNum = 0, $campOrd = "", $tpOrd = "ASC", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_pg_empleado.cedula LIKE %s
		OR vw_pg_empleado.nombre_empleado LIKE %s
		OR vw_pg_empleado.nombre_cargo LIKE %s)", 
			valTpDato("%".$valCadBusq[0]."%", "text"), 
			valTpDato("%".$valCadBusq[0]."%", "text"), 
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		vw_pg_empleado.id_empleado, 
		vw_pg_empleado.cedula, 
		vw_pg_empleado.nombre_empleado, 
		vw_pg_empleado.nombre_cargo
	FROM vw_pg_empleados vw_pg_empleado %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "10%", $pageNum, "id_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "18%", $pageNum, "cedula", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("C.I. / R.I.F."));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cargo"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$nombreSucursal = ($row['id_empresa_padre_suc'] > 0) ? $row['nombre_empresa_suc']." (".$row['sucursal'].")" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarEmpleado('".$row['id_empleado']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_empleado']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['cedula'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cargo'])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"5\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;", 
						$contFila, 
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s)\">", 
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf.="selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_al.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaEmpleado", "innerHTML", $htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaTipoContratoUsuario($pageNum = 0, $campOrd = "id_tipo_contrato_usuario", $tpOrd = "ASC", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
        
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo_contrato_usuario.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
		
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre_tipo_contrato LIKE %s
									OR nombre_empleado LIKE %s
									OR nombre_usuario LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT 
						tipo_contrato_usuario.id_tipo_contrato_usuario,
						tipo_contrato_usuario.id_usuario,
						tipo_contrato_usuario.id_tipo_contrato,
						tipo_contrato_usuario.id_empresa,
						empresa.nombre_empresa,
						tipo_contrato.nombre_tipo_contrato,
						empleados.nombre_empleado,
						empleados.nombre_cargo,
						usuario.nombre_usuario
						
					FROM al_tipo_contrato_usuario tipo_contrato_usuario
					INNER JOIN al_tipo_contrato tipo_contrato ON tipo_contrato_usuario.id_tipo_contrato = tipo_contrato.id_tipo_contrato
					INNER JOIN pg_empresa empresa ON tipo_contrato_usuario.id_empresa = empresa.id_empresa
					INNER JOIN pg_usuario usuario ON tipo_contrato_usuario.id_usuario = usuario.id_usuario
					INNER JOIN vw_pg_empleados empleados ON usuario.id_empleado = empleados.id_empleado
					%s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
        
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaTipoContratoUsuario", "10%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaTipoContratoUsuario", "15%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, "Empleado");
		$htmlTh .= ordenarCampo("xajax_listaTipoContratoUsuario", "10%", $pageNum, "nombre_usuario", $campOrd, $tpOrd, $valBusq, $maxRows, "Usuario");
		$htmlTh .= ordenarCampo("xajax_listaTipoContratoUsuario", "20%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Cargo");
		$htmlTh .= ordenarCampo("xajax_listaTipoContratoUsuario", "15%", $pageNum, "nombre_tipo_contrato", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Contrato");
		$htmlTh .= "<td colspan=\"2\" width=\"1%\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
				
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_usuario'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cargo'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_tipo_contrato'])."</td>";
			
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblTipoContrato', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_usuario']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a onclick=\"validarEliminar('%s');\"><img class=\"puntero\" src=\"../img/iconos/cross.png\" title=\"Eliminar\"/></a>",
					$row['id_tipo_contrato_usuario']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"10\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTipoContratoUsuario(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTipoContratoUsuario(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaTipoContratoUsuario(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTipoContratoUsuario(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTipoContratoUsuario(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_al.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"10\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaTipoContratoUsuario","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"asignarPermisosUsuario");
$xajax->register(XAJAX_FUNCTION,"buscarEmpleado");
$xajax->register(XAJAX_FUNCTION,"buscarTipoContratoUsuario");
$xajax->register(XAJAX_FUNCTION,"eliminarTipoContratoUsuario");
$xajax->register(XAJAX_FUNCTION,"frmTipoContratoUsuario");
$xajax->register(XAJAX_FUNCTION,"guardarTipoContratoUsuario");
$xajax->register(XAJAX_FUNCTION,"listaEmpleado");
$xajax->register(XAJAX_FUNCTION,"listaTipoContratoUsuario");

?>