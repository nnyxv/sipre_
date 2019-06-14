<?php


function buscarEmpleado($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstUnipersonal'],
		$frmBuscar['lstEstatusBuscar'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaEmpleado(0, "nombre_cargo", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstCargo($idDepartamento, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT 
		cargo_dep.id_cargo_departamento,
		cargo.nombre_cargo
	FROM pg_cargo_departamento cargo_dep
		INNER JOIN pg_cargo cargo ON (cargo_dep.id_cargo = cargo.id_cargo)
	WHERE cargo_dep.id_departamento = %s
	ORDER BY nombre_cargo",
		valTpDato($idDepartamento, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstCargo\" name=\"lstCargo\" class=\"inputHabilitado\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_cargo_departamento']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_cargo_departamento']."\">".utf8_encode($row['nombre_cargo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCargo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstDepartamento($idEmpresa, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_departamento dep WHERE dep.id_empresa = %s ORDER BY dep.nombre_departamento",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstDepartamento\" name=\"lstDepartamento\" class=\"inputHabilitado\" onchange=\"xajax_cargaLstCargo(this.value);\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_departamento']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_departamento']."\">".utf8_encode($row['nombre_departamento'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstDepartamento","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEmpresa($selId = "") {
	$objResponse = new xajaxResponse();
	
	$queryEmpresaSuc = sprintf("SELECT DISTINCT
		id_empresa_reg,
		nombre_empresa
	FROM vw_iv_empresas_sucursales
	WHERE id_empresa_padre_suc IS NULL
		AND id_empresa_reg <> 100
	ORDER BY nombre_empresa_suc ASC",
		valTpDato($row['id_empresa'], "int"));
	$rsEmpresaSuc = mysql_query($queryEmpresaSuc);
	if (!$rsEmpresaSuc) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowEmpresaSuc = mysql_fetch_assoc($rsEmpresaSuc)) {
		$selected = ($selId == $rowEmpresaSuc['id_empresa_reg']) ? "selected=\"selected\"" : "";
	
		$htmlOption .= "<option ".$selected." value=\"".$rowEmpresaSuc['id_empresa_reg']."\">".utf8_encode($rowEmpresaSuc['nombre_empresa'])."</option>";	
	}
	
	$query = sprintf("SELECT DISTINCT
		id_empresa,
		nombre_empresa
	FROM vw_iv_empresas_sucursales
	WHERE id_empresa_padre_suc IS NOT NULL
		AND id_empresa_reg <> 100
	ORDER BY nombre_empresa");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$htmlOption .= "<optgroup label=\"".$row['nombre_empresa']."\">";
		
		$queryEmpresaSuc = sprintf("SELECT DISTINCT
			id_empresa_reg,
			nombre_empresa_suc,
			sucursal
		FROM vw_iv_empresas_sucursales
		WHERE id_empresa_padre_suc = %s
			AND id_empresa_reg <> 100
		ORDER BY nombre_empresa_suc ASC",
			valTpDato($row['id_empresa'], "int"));
		$rsEmpresaSuc = mysql_query($queryEmpresaSuc);
		if (!$rsEmpresaSuc) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowEmpresaSuc = mysql_fetch_assoc($rsEmpresaSuc)) {
			$selected = ($selId == $rowEmpresaSuc['id_empresa_reg']) ? "selected=\"selected\"" : "";
		
			$htmlOption .= "<option ".$selected." value=\"".$rowEmpresaSuc['id_empresa_reg']."\">".utf8_encode($rowEmpresaSuc['nombre_empresa_suc'])."</option>";	
		}
	
		$htmlOption .= "</optgroup>";
	}
	
	$html = "<select id=\"lstEmpresaEmpleado\" name=\"lstEmpresaEmpleado\" class=\"inputHabilitado\" onchange=\"xajax_cargaLstDepartamento(this.value);\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		$html .= $htmlOption;
	$html .= "</select>";
	
	$objResponse->assign("tdlstEmpresaEmpleado","innerHTML",$html);
	
	return $objResponse;
}

function eliminarEmpleado($idEmpleado, $frmListaEmpleado) {
	$objResponse = new xajaxResponse();
	
	// EL EMPLEADO NO SE DEBE ELIMINAR, SE COLOCA COMO INACTIVO Y SE LE COLOCA FECHA DE EGRESO
	if (xvalidaAcceso($objResponse,"pg_empleado_list","eliminar")) {
		mysql_query("START TRANSACTION;");
		
		/*$deleteSQL = sprintf("DELETE FROM pg_empleado WHERE id_empleado = %s",
			valTpDato($idEmpleado, "int"));*/
		$deleteSQL = sprintf("UPDATE pg_empleado SET activo = 0, fecha_egreso = NOW() WHERE id_empleado = %s;",
			valTpDato($idEmpleado, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
		
		$objResponse->loadCommands(listaEmpleado(
			$frmListaEmpleado['pageNum'],
			$frmListaEmpleado['campOrd'],
			$frmListaEmpleado['tpOrd'],
			$frmListaEmpleado['valBusq']));
	}
	
	return $objResponse;
}

function exportarEmpleados($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscar['lstEstatusBuscar'],
		$frmBuscar['lstUnipersonal'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/pg_empleado_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function formEmpleado($idEmpleado) {
	$objResponse = new xajaxResponse();
	
	if ($idEmpleado > 0) {
		if (!xvalidaAcceso($objResponse,"pg_empleado_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarEmpleado').click();"); return $objResponse; }
		
		$query = sprintf("SELECT empleado.*,
			cargo_dep.id_cargo_departamento,
			dep.id_empresa,
			cargo_dep.id_departamento,
			cargo_dep.id_cargo
		FROM pg_cargo_departamento cargo_dep
			INNER JOIN pg_empleado empleado ON (cargo_dep.id_cargo_departamento = empleado.id_cargo_departamento)
			INNER JOIN pg_cargo cargo ON (cargo_dep.id_cargo = cargo.id_cargo)
			INNER JOIN pg_departamento dep ON (cargo_dep.id_departamento = dep.id_departamento)
		WHERE empleado.id_empleado = %s",
			valTpDato($idEmpleado, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdEmpleado","value",$idEmpleado);
		$objResponse->assign("txtCedula","value",utf8_encode($row['cedula']));
		$objResponse->assign("txtNombre","value",utf8_encode($row['nombre_empleado']));
		$objResponse->assign("txtApellido","value",utf8_encode($row['apellido']));
		$objResponse->assign("txtTelefono","value",utf8_encode($row['telefono']));
		$objResponse->assign("txtCelular","value",utf8_encode($row['celular']));
		$objResponse->assign("txtDireccion","value",utf8_encode($row['direccion']));
		$objResponse->assign("txtCorreo","value",utf8_encode($row['email']));
		$objResponse->loadCommands(cargaLstEmpresa($row['id_empresa']));
		$objResponse->loadCommands(cargaLstDepartamento($row['id_empresa'], $row['id_departamento']));
		$objResponse->loadCommands(cargaLstCargo($row['id_departamento'], $row['id_cargo_departamento']));
		$objResponse->call("selectedOption","lstEstatus",$row['activo']);
		$objResponse->assign("txtCodigo","value",utf8_encode($row['codigo_empleado']));
		$objResponse->assign("txtFechaIngreso","value",date(spanDateFormat,strtotime($row['fecha_ingreso'])));
		if ($row['fecha_egreso'] != '')
			$objResponse->assign("txtFechaEgreso","value",date(spanDateFormat,strtotime($row['fecha_egreso'])));
	} else {
		if (!xvalidaAcceso($objResponse,"pg_empleado_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarEmpleado').click();"); return $objResponse; }
		
		$objResponse->loadCommands(cargaLstEmpresa());
		$objResponse->assign("txtFechaIngreso","value",date(spanDateFormat));
	}
	
	return $objResponse;
}

function guardarEmpleado($frmEmpleado, $frmListaEmpleado) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$idEmpleado = $frmEmpleado['hddIdEmpleado'];
	
	if ($frmEmpleado['lstEstatus'] == "Activo" || $frmEmpleado['lstEstatus'] == 1) {
		// VERIFICA SI EL CARGO ES UNIPERSONAL
		$query = sprintf("SELECT * FROM pg_cargo
			INNER JOIN pg_cargo_departamento ON (pg_cargo_departamento.id_cargo = pg_cargo.id_cargo)
		WHERE id_cargo_departamento = %s
			AND pg_cargo.unipersonal = 1",
			valTpDato($frmEmpleado['lstCargo'], "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		
		// VERIFICA LA CANTIDAD DE EMPLEADOS QUE TIENEN ACTIVO EL CARGO - DEPARTAMENTO
		if ($idEmpleado > 0) {
			$queryEmpCargoAct = sprintf("SELECT * FROM pg_empleado
			WHERE id_cargo_departamento = %s
				AND activo = 1
				AND id_empleado <> %s",
				valTpDato($frmEmpleado['lstCargo'], "int"),
				valTpDato($idEmpleado, "int"));
		} else {
			$queryEmpCargoAct = sprintf("SELECT * FROM pg_empleado
			WHERE id_cargo_departamento = %s
				AND activo = 1",
				valTpDato($frmEmpleado['lstCargo'], "int"));
		}
		$rsEmpCargoAct = mysql_query($queryEmpCargoAct);
		if (!$rsEmpCargoAct) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsEmpCargoAct = mysql_num_rows($rsEmpCargoAct);
		
		if ($totalRows > 0 && $totalRowsEmpCargoAct == 1) {
			return $objResponse->alert("El Cargo seleccionado es Unipersonal, modifique el Estado del Cargo a Inactivo o modifique el Estado del Otro Empleado con el mismo Cargo");
		}
	}
	
	if ($idEmpleado > 0) {
		if (!xvalidaAcceso($objResponse,"pg_empleado_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE pg_empleado SET
			id_cargo_departamento = %s,
			codigo_empleado = %s,
			cedula = %s,
			nombre_empleado = %s,
			apellido = %s,
			direccion = %s,
			telefono = %s,
			celular = %s,
			email = %s,
			activo = %s
		WHERE id_empleado = %s;",
			valTpDato($frmEmpleado['lstCargo'], "int"),
			valTpDato($frmEmpleado['txtCodigo'], "text"),
			valTpDato($frmEmpleado['txtCedula'], "text"),
			valTpDato($frmEmpleado['txtNombre'], "text"),
			valTpDato($frmEmpleado['txtApellido'], "text"),
			valTpDato($frmEmpleado['txtDireccion'], "text"),
			valTpDato($frmEmpleado['txtTelefono'], "text"),
			valTpDato($frmEmpleado['txtCelular'], "text"),
			valTpDato($frmEmpleado['txtCorreo'], "text"),
			valTpDato($frmEmpleado['lstEstatus'], "boolean"),
			valTpDato($idEmpleado, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		$updateSQL = sprintf("UPDATE pg_empleado SET
			contrasena_especial = %s
		WHERE id_empleado = %s
			AND contrasena_especial IS NULL;",
			valTpDato($frmEmpleado['txtCodigo'], "text"),
			valTpDato($idEmpleado, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		if ($frmEmpleado['lstEstatus'] == 0) {
			$updateSQL = sprintf("UPDATE pg_empleado SET
				fecha_egreso = NOW()
			WHERE id_empleado = %s;",
				valTpDato($idEmpleado, "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
	} else {
		if (!xvalidaAcceso($objResponse,"pg_empleado_list","insertar")) { return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO pg_empleado (id_cargo_departamento, codigo_empleado, cedula, nombre_empleado, apellido, direccion, telefono, celular, email, contrasena_especial, fecha_ingreso, activo)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($frmEmpleado['lstCargo'], "int"),
			valTpDato($frmEmpleado['txtCodigo'], "text"),
			valTpDato($frmEmpleado['txtCedula'], "text"),
			valTpDato($frmEmpleado['txtNombre'], "text"),
			valTpDato($frmEmpleado['txtApellido'], "text"),
			valTpDato($frmEmpleado['txtDireccion'], "text"),
			valTpDato($frmEmpleado['txtTelefono'], "text"),
			valTpDato($frmEmpleado['txtCelular'], "text"),
			valTpDato($frmEmpleado['txtCorreo'], "text"),
			valTpDato($frmEmpleado['txtCodigo'], "text"),
			valTpDato("NOW()", "campo"),
			valTpDato($frmEmpleado['lstEstatus'], "boolean"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Empleado Guardado con Éxito");
	
	$objResponse->script("byId('btnCancelarEmpleado').click();");
	
	$objResponse->loadCommands(listaEmpleado(
		$frmListaEmpleado['pageNum'],
		$frmListaEmpleado['campOrd'],
		$frmListaEmpleado['tpOrd'],
		$frmListaEmpleado['valBusq']));
	
	return $objResponse;
}

function listaEmpleado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	//NO MUESTRA LOS EMPLEADOS QUE TENGAN ASIGNADA FECHA DE EGRESO
	//$sqlBusq .= $cond.sprintf("WHERE empleado.fecha_egreso IS NULL");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("dep.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cargo.unipersonal = %s",
			valTpDato($valCadBusq[1], "boolean"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("empleado.activo = %s",
			valTpDato($valCadBusq[2], "boolean"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(empleado.cedula LIKE %s
		OR CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) LIKE %s
		OR dep.nombre_departamento LIKE %s
		OR cargo.nombre_cargo LIKE %s
		OR usu.nombre_usuario LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		empleado.id_empleado,
		empleado.cedula,
		empleado.fecha_ingreso,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
		dep.nombre_departamento,
		cargo.nombre_cargo,
		cargo.unipersonal,
		CONCAT_WS(' / ', empleado.telefono, empleado.celular) AS telefono,
		empleado.email,
		usu.nombre_usuario,
		empleado.activo
	FROM pg_empleado empleado
		INNER JOIN pg_cargo_departamento cargo_dep ON (empleado.id_cargo_departamento = cargo_dep.id_cargo_departamento)
		INNER JOIN pg_cargo cargo ON (cargo_dep.id_cargo = cargo.id_cargo)
		INNER JOIN pg_departamento dep ON (cargo_dep.id_departamento = dep.id_departamento)
		LEFT JOIN pg_usuario usu ON (empleado.id_empleado = usu.id_empleado) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n\nSQL: ".$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "8%", $pageNum, "id_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "8%", $pageNum, "cedula", $campOrd, $tpOrd, $valBusq, $maxRows, "C.I.");
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "16%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, "Empleado");
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "18%", $pageNum, "nombre_departamento", $campOrd, $tpOrd, $valBusq, $maxRows, "Departamento");
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "15%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Cargo");
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "18%", $pageNum, "telefono", $campOrd, $tpOrd, $valBusq, $maxRows, "Teléfono");
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "9%", $pageNum, "nombre_usuario", $campOrd, $tpOrd, $valBusq, $maxRows, "Usuario");
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "8%", $pageNum, "fecha_ingreso", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Ingreso");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		switch($row['activo']) {
			case 0 : $imgEstatus = "<img src=\"img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1 : $imgEstatus = "<img src=\"img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatus = "";
		}
		
		switch($row['unipersonal']) {
			case 1 : $imgEstatusCargo = "<img src=\"img/iconos/user_suit.png\" title=\"Cargo Unipersonal\"/>"; break;
			default : $imgEstatusCargo = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['id_empleado'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['cedula'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_departamento'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombre_cargo'])."</td>";
					$htmlTb .= "<td>".$imgEstatusCargo."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<div align=\"center\">".utf8_encode($row['telefono'])."</div>";
				$htmlTb .= ((strlen($row['email']) > 0) ? "<div><a class=\"linkAzulUnderline\" href=\"mailto:".utf8_encode($row['email'])."\">".utf8_encode($row['email'])."</a></div>" : "");
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_usuario'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($row['fecha_ingreso']))."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aVer%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblEmpleado', '%s');\"><img class=\"puntero\" src=\"img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_empleado']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"img/iconos/ico_delete.png\"/></td>",
				$row['id_empleado']);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"11\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ult_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"11\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaEmpleado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarEmpleado");
$xajax->register(XAJAX_FUNCTION,"cargaLstCargo");
$xajax->register(XAJAX_FUNCTION,"cargaLstDepartamento");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresa");
$xajax->register(XAJAX_FUNCTION,"eliminarEmpleado");
$xajax->register(XAJAX_FUNCTION,"exportarEmpleados");
$xajax->register(XAJAX_FUNCTION,"formEmpleado");
$xajax->register(XAJAX_FUNCTION,"guardarEmpleado");
$xajax->register(XAJAX_FUNCTION,"listaEmpleado");
?>