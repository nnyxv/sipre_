<?php


function buscarEmpresa($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaEmpresa(0, "id_empresa_padre", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstEmpresa($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_empresa emp
	WHERE emp.id_empresa_padre IS NULL
		AND emp.id_empresa <> 100
	ORDER BY nombre_empresa ASC;",
		valTpDato($idUsuario, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = sprintf("<select id=\"lstEmpresaPpal\" name=\"lstEmpresaPpal\" class=\"inputHabilitado\" style=\"width:200px\">",
		$idUsuario);
		$html .="<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empresa']) ? "selected=\"selected\"" : "";
	
		$html .= "<option ".$selected." value=\"".$row['id_empresa']."\">".utf8_encode($row['nombre_empresa'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstEmpresaPpal","innerHTML",$html);
	
	return $objResponse;
}

function eliminarEmpresa($idEmpresa, $frmListaEmpresa) {
	$objResponse = new xajaxResponse();

	if (!xvalidaAcceso($objResponse,"pg_empresa_list","eliminar")){ return $objResponse; }
	
	$deleteSQL = sprintf("DELETE FROM pg_empresa WHERE id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");

	$objResponse->alert("Eliminación Realizada con Éxito");
	
	$objResponse->loadCommands(listaEmpresa(
		$frmListaEmpresa['pageNum'],
		$frmListaEmpresa['campOrd'],
		$frmListaEmpresa['tpOrd'],
		$frmListaEmpresa['valBusq']));

	return $objResponse;
}

function formEmpresa($idEmpresa, $frmEmpresa) {
	$objResponse = new xajaxResponse();

	if ($idEmpresa > 0) {
		if (!xvalidaAcceso($objResponse,"pg_empresa_list","editar")) { $objResponse->script("byId('btnCancelarEmpresa').click();"); return $objResponse; }
	
		$query = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;", valTpDato($idEmpresa, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_array($rs);
	
		$objResponse->loadCommands(cargaLstEmpresa($row['id_empresa_padre']));
		$objResponse->assign("txtIDempresa","value",$row['id_empresa']);
		$objResponse->assign("hddIdEmpresa","value",$row['id_empresa']);
		$objResponse->assign("txtEmpresa","value",utf8_encode($row['nombre_empresa']));
		$objResponse->assign("txtContribuyente","value",$row['contribuyente_especial']);
		$objResponse->assign("txtRif","value",$row['rif']);
		$objResponse->assign("txtDireccion","value",utf8_encode($row['direccion']));
		$objResponse->assign("txtTelefono1","value",$row['telefono1']);
		$objResponse->assign("txtTelefono2","value",$row['telefono2']);
		$objResponse->assign("txtTelefono3","value",$row['telefono3']);
		$objResponse->assign("txtTelefono4","value",$row['telefono4']);
		$objResponse->assign("txtCorreo","value",utf8_encode($row['correo']));
		$objResponse->assign("txtWeb","value",utf8_encode($row['web']));
		$objResponse->assign("txtFamiliaEmpresa","value",utf8_encode($row['familia_empresa']));
		$objResponse->assign("txtTelefonoAsistencia","value",$row['telefono_asistencia']);
		$objResponse->assign("txtTelefonoServicio","value",$row['telefono_servicio']);
		$objResponse->assign("txtNombreTaller","value",utf8_encode($row['nombre_taller']));
		$objResponse->assign("txtDireccionTaller","value",utf8_encode($row['direccion_taller']));
		$objResponse->assign("txtTelefonoTaller1","value",$row['telefono_taller1']);
		$objResponse->assign("txtTelefonoTaller2","value",$row['telefono_taller2']);
		$objResponse->assign("txtTelefonoTaller3","value",$row['telefono_taller3']);
		$objResponse->assign("txtTelefonoTaller4","value",$row['telefono_taller4']);
		$objResponse->assign("txtContactosTaller","value",utf8_encode($row['contactos_taller']));
		$objResponse->assign("txtNit","value",$row['nit']);
		$objResponse->assign("txtSucursal","value",utf8_encode($row['sucursal']));
		$objResponse->assign("txtFax","value",$row['fax']);
		$objResponse->assign("txtFaxTaller","value",$row['fax_taller']);
		$objResponse->assign("txtCiudadEmpresa","value",utf8_encode($row['ciudad_empresa']));
		$objResponse->assign("txtNombreAsistencia","value",utf8_encode($row['nombre_asistencia']));
		$objResponse->assign("imgGrupo","src",$row['logo_familia']);
		$objResponse->assign("hddUrlImgGrupo","value",$row['logo_familia']);
		$objResponse->assign("imgEmpresa","src",$row['logo_empresa']);
		$objResponse->assign("hddUrlImgEmpresa","value",$row['logo_empresa']);
		$objResponse->assign("imgFirmaAdmon","src",$row['ruta_firma_digital']);
		$objResponse->assign("hddUrlImgFirmaAdmon","value",$row['ruta_firma_digital']);
		$objResponse->assign("imgFirmaTesoreria","src",$row['ruta_firma_tesoreria']);
		$objResponse->assign("hddUrlImgFirmaTesoreria","value",$row['ruta_firma_tesoreria']);
		$objResponse->assign("imgFirmaSello","src",$row['ruta_firma_sello']);
		$objResponse->assign("hddUrlImgFirmaSello","value",$row['ruta_firma_sello']);
		$objResponse->assign("txtCodigoEmpresa","value",$row['codigo_empresa']);
		$objResponse->assign("txtPaqCombo","value",$row['paquete_combo']);
		
		$objResponse->assign("txtFormatoCodigoRepuestos","value",$row['formato_codigo_repuestos']);
		$objResponse->assign("txtFormatoCodigoCompras","value",$row['formato_codigo_compras']);
	} else {
		if (!xvalidaAcceso($objResponse,"pg_empresa_list","insertar")) { $objResponse->script("byId('btnCancelarEmpresa').click();"); return $objResponse; }
		
		$objResponse->loadCommands(cargaLstEmpresa());
	}
	
	return $objResponse;
}

function guardarEmpresa($frmEmpresa, $frmListaEmpresa) {
	$objResponse = new xajaxResponse();
	
	global $arrayValidarRIF;
	global $arrayValidarNIT;

	mysql_query("START TRANSACTION;");
	
	$arrayValidar = $arrayValidarRIF;
	if (isset($arrayValidar)) {
		$objResponse->script("byId('txtRif').className = 'inputHabilitado'");
		
		$valido = false;
		foreach ($arrayValidar as $indice => $valor) {
			if (preg_match($valor, $frmEmpresa['txtRif'])) {
				$valido = true;
			}
		}
		
		if ($valido == false && strlen($frmEmpresa['txtRif']) > 0) {
			$objResponse->script("byId('txtRif').className = 'inputErrado'");
			errorGuardarEmpresa($objResponse);
			return $objResponse->alert(("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido"));
		}
	}
	
	$arrayValidar = $arrayValidarNIT;
	if (isset($arrayValidar)) {
		$objResponse->script("byId('txtNit').className = 'inputHabilitado'");
		
		$valido = false;
		foreach ($arrayValidar as $indice => $valor) {
			if (preg_match($valor, $frmEmpresa['txtNit'])) {
				$valido = true;
			}
		}
		
		if ($valido == false && strlen($frmEmpresa['txtNit']) > 0) {
			$objResponse->script("byId('txtNit').className = 'inputErrado'");
			errorGuardarEmpresa($objResponse);
			return $objResponse->alert(("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido"));
		}
	}
	
	$txtCiEmpresa = explode("-",$frmEmpresa['txtRif']);
	if (is_numeric($txtCiEmpresa[0]) == true) {
		$txtCiEmpresa = implode("-",$txtCiEmpresa);
	} else {
		$txtLciEmpresa = $txtCiEmpresa[0];
		//array_shift($txtCiEmpresa);
		$txtCiEmpresa = implode("-",$txtCiEmpresa);
	}
	
	$txtNitEmpresa = explode("-",$frmEmpresa['txtNit']);
	if (is_numeric($txtNitEmpresa[0]) == true) {
		$txtNitEmpresa = implode("-",$txtNitEmpresa);
	} else {
		$txtLNitEmpresa = $txtNitEmpresa[0];
		//array_shift($txtNitEmpresa);
		$txtNitEmpresa = implode("-",$txtNitEmpresa);
	}
	
	if ($frmEmpresa['hddIdEmpresa'] > 0) {
		if (!xvalidaAcceso($objResponse,"pg_empresa_list","editar")) { errorGuardarEmpresa($objResponse); return $objResponse; }
		
		$updateSQL = sprintf("UPDATE pg_empresa SET
			id_empresa_padre = %s,
			nombre_empresa = %s,
			contribuyente_especial = %s,
			rif = %s,
			direccion = %s,
			telefono1 = %s,
			telefono2 = %s,
			telefono3 = %s,
			telefono4 = %s,
			correo = %s,
			web = %s,
			familia_empresa = %s,
			telefono_asistencia = %s,
			telefono_servicio = %s,
			nombre_taller = %s,
			direccion_taller = %s,
			telefono_taller1 = %s,
			telefono_taller2 = %s,
			telefono_taller3 = %s,
			telefono_taller4 = %s,
			contactos_taller = %s,
			nit = %s,
			sucursal = %s,
			fax = %s,
			fax_taller = %s,
			ciudad_empresa = %s,
			nombre_asistencia = %s,
			logo_familia = %s,
			logo_empresa = %s,
			codigo_empresa = %s,
			paquete_combo = %s,
			formato_codigo_repuestos = %s,
			formato_codigo_compras = %s,
			ruta_firma_digital = %s,
			ruta_firma_tesoreria = %s,
			ruta_firma_sello = %s
		WHERE id_empresa = %s;",
			valTpDato($frmEmpresa['lstEmpresaPpal'], "int"),
			valTpDato($frmEmpresa['txtEmpresa'], "text"),
			valTpDato($frmEmpresa['txtContribuyente'],"boolean"),
			valTpDato($txtCiEmpresa, "text"),
			valTpDato($frmEmpresa['txtDireccion'], "text"),
			valTpDato($frmEmpresa['txtTelefono1'], "text"),
			valTpDato($frmEmpresa['txtTelefono2'], "text"),
			valTpDato($frmEmpresa['txtTelefono3'], "text"),
			valTpDato($frmEmpresa['txtTelefono4'], "text"),
			valTpDato($frmEmpresa['txtCorreo'], "text"),
			valTpDato($frmEmpresa['txtWeb'], "text"),
			valTpDato($frmEmpresa['txtFamiliaEmpresa'], "text"),
			valTpDato($frmEmpresa['txtTelefonoAsistencia'], "text"),
			valTpDato($frmEmpresa['txtTelefonoServicio'], "text"),
			valTpDato($frmEmpresa['txtNombreTaller'], "text"),
			valTpDato($frmEmpresa['txtDireccionTaller'], "text"),
			valTpDato($frmEmpresa['txtTelefonoTaller1'], "text"),
			valTpDato($frmEmpresa['txtTelefonoTaller2'], "text"),
			valTpDato($frmEmpresa['txtTelefonoTaller3'], "text"),
			valTpDato($frmEmpresa['txtTelefonoTaller4'], "text"),
			valTpDato($frmEmpresa['txtContactosTaller'], "text"),
			valTpDato($txtNitEmpresa, "text"),
			valTpDato($frmEmpresa['txtSucursal'], "text"),
			valTpDato($frmEmpresa['txtFax'], "text"),
			valTpDato($frmEmpresa['txtFaxTaller'], "text"),
			valTpDato($frmEmpresa['txtCiudadEmpresa'], "text"),
			valTpDato($frmEmpresa['txtNombreAsistencia'], "text"),
			valTpDato($frmEmpresa['hddUrlImgGrupo'], "text"),
			valTpDato($frmEmpresa['hddUrlImgEmpresa'], "text"),
			valTpDato($frmEmpresa['txtCodigoEmpresa'], "text"),
			valTpDato($frmEmpresa['txtPaqCombo'],"boolean"),
			valTpDato($frmEmpresa['txtFormatoCodigoRepuestos'], "text"),
			valTpDato($frmEmpresa['txtFormatoCodigoCompras'], "text"),
			valTpDato($frmEmpresa['hddUrlImgFirmaAdmon'], "text"),
			valTpDato($frmEmpresa['hddUrlImgFirmaTesoreria'], "text"),
			valTpDato($frmEmpresa['hddUrlImgFirmaSello'], "text"),
			valTpDato($frmEmpresa['hddIdEmpresa'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) {
			errorGuardarEmpresa($objResponse);
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Registro Duplicado"."\n\nLine: ".__LINE__);
			} else {
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"pg_empresa_list","insertar")) { errorGuardarEmpresa($objResponse); return $objResponse; }
		
		$query = sprintf("SELECT * FROM pg_empresa WHERE id_empresa <> 100 ORDER BY id_empresa DESC LIMIT 1;");
		$rs = mysql_query($query);
		if (!$rs) { errorGuardarEmpresa($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$row = mysql_fetch_assoc($rs);
		
		$idEmpresa = $row['id_empresa'] + 1;
		
		$insertSQL = sprintf("INSERT INTO pg_empresa (id_empresa, id_empresa_padre, nombre_empresa, contribuyente_especial, rif, direccion, telefono1 ,telefono2, telefono3, telefono4, correo, web, familia_empresa, telefono_asistencia, telefono_servicio, nombre_taller, direccion_taller, telefono_taller1, telefono_taller2, telefono_taller3, telefono_taller4, contactos_taller, nit, sucursal, fax, fax_taller, ciudad_empresa, nombre_asistencia, logo_familia, logo_empresa, codigo_empresa, paquete_combo, formato_codigo_repuestos, formato_codigo_compras, ruta_firma_digital, ruta_firma_tesoreria, ruta_firma_sello)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idEmpresa, "text"),
			valTpDato($frmEmpresa['lstEmpresaPpal'], "int"),
			valTpDato($frmEmpresa['txtEmpresa'], "text"),
			valTpDato($frmEmpresa['txtContribuyente'],"boolean"),
			valTpDato($txtCiEmpresa, "text"),
			valTpDato($frmEmpresa['txtDireccion'], "text"),
			valTpDato($frmEmpresa['txtTelefono1'], "text"),
			valTpDato($frmEmpresa['txtTelefono2'], "text"),
			valTpDato($frmEmpresa['txtTelefono3'], "text"),
			valTpDato($frmEmpresa['txtTelefono4'], "text"),
			valTpDato($frmEmpresa['txtCorreo'], "text"),
			valTpDato($frmEmpresa['txtWeb'], "text"),
			valTpDato($frmEmpresa['txtFamiliaEmpresa'], "text"),
			valTpDato($frmEmpresa['txtTelefonoAsistencia'], "text"),
			valTpDato($frmEmpresa['txtTelefonoServicio'], "text"),
			valTpDato($frmEmpresa['txtNombreTaller'], "text"),
			valTpDato($frmEmpresa['txtDireccionTaller'], "text"),
			valTpDato($frmEmpresa['txtTelefonoTaller1'], "text"),
			valTpDato($frmEmpresa['txtTelefonoTaller2'], "text"),
			valTpDato($frmEmpresa['txtTelefonoTaller3'], "text"),
			valTpDato($frmEmpresa['txtTelefonoTaller4'], "text"),
			valTpDato($frmEmpresa['txtContactosTaller'], "text"),
			valTpDato($txtNitEmpresa, "text"),
			valTpDato($frmEmpresa['txtSucursal'], "text"),
			valTpDato($frmEmpresa['txtFax'], "text"),
			valTpDato($frmEmpresa['txtFaxTaller'], "text"),
			valTpDato($frmEmpresa['txtCiudadEmpresa'], "text"),
			valTpDato($frmEmpresa['txtNombreAsistencia'], "text"),
			valTpDato($frmEmpresa['hddUrlImgGrupo'], "text"),
			valTpDato($frmEmpresa['hddUrlImgEmpresa'], "text"),
			valTpDato($frmEmpresa['txtCodigoEmpresa'], "text"),
			valTpDato($frmEmpresa['txtPaqCombo'],"boolean"),
			valTpDato($frmEmpresa['txtFormatoCodigoRepuestos'], "text"),
			valTpDato($frmEmpresa['txtFormatoCodigoCompras'], "text"),
			valTpDato($frmEmpresa['hddUrlImgFirmaAdmon'], "text"),
			valTpDato($frmEmpresa['hddUrlImgFirmaTesoreria'], "text"),
			valTpDato($frmEmpresa['hddUrlImgFirmaSello'], "text"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) {
			errorGuardarEmpresa($objResponse);
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Registro Duplicado"."\n\nLine: ".__LINE__);
			} else {
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	errorGuardarEmpresa($objResponse);
	$objResponse->alert("Empresa Guardado con Éxito");
	
	$objResponse->script("byId('btnCancelarEmpresa').click();");
	
	$objResponse->loadCommands(listaEmpresa(
		$frmListaEmpresa['pageNum'],
		$frmListaEmpresa['campOrd'],
		$frmListaEmpresa['tpOrd'],
		$frmListaEmpresa['valBusq']));

	return $objResponse;
}

function listaEmpresa($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $spanRIF;
	global $spanNIT;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("emp.id_empresa <> 100");

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre_empresa LIKE %s
		OR rif LIKE %s
		OR nit LIKE %s
		OR sucursal LIKE %s
		OR familia_empresa LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}

	$query = sprintf("SELECT emp.*,
		emp_ppal.nombre_empresa AS nombre_empresa_ppal
	FROM pg_empresa emp
		LEFT JOIN pg_empresa emp_ppal ON (emp.id_empresa_padre = emp_ppal.id_empresa) %s", $sqlBusq);

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
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "8%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "16%", $pageNum, "nombre_empresa_ppal", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa Ppal");
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "10%", $pageNum, "rif", $campOrd, $tpOrd, $valBusq, $maxRows, $spanRIF);
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "16%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "16%", $pageNum, "sucursal", $campOrd, $tpOrd, $valBusq, $maxRows, "Sucursal");
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "14%", $pageNum, "familia_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Grupo");
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "10%", $pageNum, "nit", $campOrd, $tpOrd, $valBusq, $maxRows, $spanNIT);
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "10%", $pageNum, "contribuyente_especial", $campOrd, $tpOrd, $valBusq, $maxRows, "Contribuyente Especial");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;

		$contribuyente = ($row['contribuyente_especial'] == 1) ? "SI" : "NO";

		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['id_empresa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa_ppal'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['rif'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['sucursal'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['familia_empresa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nit'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($contribuyente)."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblEmpresa', '%s');\"><img class=\"puntero\" src=\"img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_empresa']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"img/iconos/ico_delete.png\"/>",
					$row['id_empresa']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}

	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"14\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",$contFila,$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}

						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";

							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";

	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"12\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("divListaEmpresa","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresa");
$xajax->register(XAJAX_FUNCTION,"eliminarEmpresa");
$xajax->register(XAJAX_FUNCTION,"formEmpresa");
$xajax->register(XAJAX_FUNCTION,"guardarEmpresa");
$xajax->register(XAJAX_FUNCTION,"listaEmpresa");

function errorGuardarEmpresa($objResponse) {
	$objResponse->script("
	byId('btnGuardarEmpresa').disabled = false;
	byId('btnCancelarEmpresa').disabled = false;");
}
?>