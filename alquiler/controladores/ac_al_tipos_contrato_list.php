<?php

function buscarTipoContrato($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['lstEmpresaBuscar'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaTipoContrato(0, "id_tipo_contrato", "ASC", $valBusq));
	
	return $objResponse;
}

function cargarLstClaveMovimiento($modoFactura, $idClaveMovimiento = NULL, $idClaveMovimientoDev = NULL){
	$objResponse = new xajaxResponse();
	
	if($modoFactura == 1){//FACTURA
	   $filtroTipo = "1";
	   $filtroTipoDev = "3";
	}elseif($modoFactura == 2){//VALE SALIDA
	   $filtroTipo = "5";
	   $filtroTipoDev = "6";
	}else{//ninguno
		$filtroTipo = "1,5";//que muestre todos los de ingreso
		$filtroTipoDev = "3,6";//que muestre todos los de devolucion
	}
	
	$sqlClave = sprintf("SELECT 
							id_clave_movimiento, 
							CONCAT_WS(') ', clave, descripcion) AS descripcion 
						FROM pg_clave_movimiento 
						WHERE id_modulo = 4 
						AND documento_genera IN(%s)",
					$filtroTipo);
	$rsClave = mysql_query($sqlClave);
	if(!$rsClave){ return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__); }
	
	while ($row = mysql_fetch_assoc($rsClave)){
		$arrayClaveMov[$row['id_clave_movimiento']] = $row['descripcion'];
	}
	
	$sqlClaveDev = sprintf("SELECT 
								id_clave_movimiento,
								CONCAT_WS(') ', clave, descripcion) AS descripcion  
							FROM pg_clave_movimiento 
							WHERE id_modulo = 4 
							AND documento_genera IN(%s)",
					$filtroTipoDev);
	$rsClaveDev = mysql_query($sqlClaveDev);
	if(!$rsClaveDev) { return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__); }
	
	while ($row2 = mysql_fetch_assoc($rsClaveDev)){
		$arrayClaveMovDev[$row2['id_clave_movimiento']] = $row2['descripcion'];
	}
	
	//compruebo que la guardada este en el listado, sino es porque se encuentra mal asignada y debo mostrar completo el listado para que se vea
	if($idClaveMovimiento != NULL && $idClaveMovimientoDev != NULL){
		if(!array_key_exists($idClaveMovimiento, $arrayClaveMov) || !array_key_exists($idClaveMovimientoDev, $arrayClaveMovDev)){
			$objResponse->alert("Este tipo de contrato tiene una clave de movimiento que no pertene al modo de factura asigando");
			$objResponse->script("xajax_cargarClaveMovimiento('OTROS',".$idClaveMovimiento.",".$idClaveMovimientoDev.");");
			return $objResponse;
		}
	}

	$htmlClave .= "<select id=\"lstClaveMovimiento\" name=\"lstClaveMovimiento\" class=\"inputHabilitado\">";
	$htmlClave .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach($arrayClaveMov as $idClave => $descripcionClave){
		$htmlClave .= "<option value=\"".$idClave."\">".utf8_encode($descripcionClave)."</option>";
	}
	$htmlClave .= "</select>";
	
	$htmlClaveDev .= "<select id=\"lstClaveMovimientoDev\" name=\"lstClaveMovimientoDev\" class=\"inputHabilitado\">";
	$htmlClaveDev .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach($arrayClaveMovDev as $idClaveDev => $descripcionClaveDev){
		$htmlClaveDev .= "<option value=\"".$idClaveDev."\">".utf8_encode($descripcionClaveDev)."</option>";
	}
	$htmlClaveDev .= "</select>";
	
	$objResponse->assign("tdClaveMovimiento","innerHTML",$htmlClave);
	$objResponse->assign("tdClaveMovimientoDev","innerHTML",$htmlClaveDev);
	
	$objResponse->assign('lstClaveMovimiento','value',$idClaveMovimiento);
	$objResponse->assign('lstClaveMovimientoDev','value',$idClaveMovimientoDev);
	
	return $objResponse;	
}

function cargarLstClaveMovimientoSalidaEntrada($idClaveMovimientoSalida = NULL, $idClaveMovimientoEntrada = NULL){
	$objResponse = new xajaxResponse();

	$filtroTipoSalida = "4";//que muestre todos los de ingreso
	$filtroTipoEntrada = "2";//que muestre todos los de devolucion
	
	$sqlClaveSalida = sprintf("SELECT 
							id_clave_movimiento, 
							CONCAT_WS(') ', clave, descripcion) AS descripcion 
						FROM pg_clave_movimiento 
						WHERE id_modulo = 4 
						AND documento_genera = 0
						AND tipo IN(%s)",
					$filtroTipoSalida);
	$rsClaveSalida = mysql_query($sqlClaveSalida);
	if(!$rsClaveSalida){ return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__); }

	while ($row = mysql_fetch_assoc($rsClaveSalida)){
		$arrayClaveMovSalida[$row['id_clave_movimiento']] = $row['descripcion'];
	}
	
	$sqlClaveEntrada = sprintf("SELECT 
								id_clave_movimiento,
								CONCAT_WS(') ', clave, descripcion) AS descripcion  
							FROM pg_clave_movimiento 
							WHERE id_modulo = 4 
							AND documento_genera = 0
							AND tipo IN(%s)",
					$filtroTipoEntrada);
	$rsClaveEntrada = mysql_query($sqlClaveEntrada);
	if(!$rsClaveEntrada) { return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__); }
	
	while ($row2 = mysql_fetch_assoc($rsClaveEntrada)){
		$arrayClaveMovEntrada[$row2['id_clave_movimiento']] = $row2['descripcion'];
	}

	$htmlClaveSalida .= "<select id=\"lstClaveMovimientoSalida\" name=\"lstClaveMovimientoSalida\" class=\"inputHabilitado\">";
	$htmlClaveSalida .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach($arrayClaveMovSalida as $id => $descripcion){
		$htmlClaveSalida .= "<option value=\"".$id."\">".utf8_encode($descripcion)."</option>";
	}
	$htmlClaveSalida .= "</select>";
	
	$htmlClaveEntrada .= "<select id=\"lstClaveMovimientoEntrada\" name=\"lstClaveMovimientoEntrada\" class=\"inputHabilitado\">";
	$htmlClaveEntrada .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach($arrayClaveMovEntrada as $id => $descripcion){
		$htmlClaveEntrada .= "<option value=\"".$id."\">".utf8_encode($descripcion)."</option>";
	}
	$htmlClaveEntrada .= "</select>";
	
	$objResponse->assign("tdClaveMovimientoSalida","innerHTML",$htmlClaveSalida);
	$objResponse->assign("tdClaveMovimientoEntrada","innerHTML",$htmlClaveEntrada);
	
	$objResponse->assign('lstClaveMovimientoSalida','value',$idClaveMovimientoSalida);
	$objResponse->assign('lstClaveMovimientoEntrada','value',$idClaveMovimientoEntrada);
	
	return $objResponse;	
}

function cargarListFiltroContrato($idFiltroContrato){
	$objResponse = new xajaxResponse();
	
	$sql = sprintf("SELECT 
						id_filtro_contrato, 
						CONCAT_WS('.- ', id_filtro_contrato, descripcion) as	descripcion
					FROM al_filtro_contrato");
	$rs = mysql_query($sql);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__); }
	
	$html.= "<select id=\"lstFiltroContrato\" name=\"lstFiltroContrato\" class=\"inputHabilitado\">";
	$html.= "<option value=\"-1\">[ Seleccione ]</option>";
	while($row = mysql_fetch_assoc($rs)){
		$selected = "";
		if($idFiltroContrato == $row["id_filtro_contrato"]){ $selected = "selected = \"selected\""; }
		$html.= "<option value=\"".$row["id_filtro_contrato"]."\" ".$selected.">".utf8_encode($row["descripcion"])."</option>";
	}
	$html.= "</select>";
	
	$objResponse->assign("tdListFiltroContrato","innerHTML",$html);
	
	return $objResponse;
}

function eliminarPrecio($idTipoContrato) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"al_tipos_contrato_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");

	$deleteSQL = sprintf("DELETE FROM al_tipo_contrato WHERE id_tipo_contrato = %s",
		valTpDato($idTipoContrato, "int"));
	$Result1 = mysql_query($deleteSQL);	
	if(mysql_errno() == 1451){ return $objResponse->alert("No se puede eliminar porque ya esta en uso"); }
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->script("byId('btnBuscar').click();");
	$objResponse->alert("Eliminado Correctamente");
	
	return $objResponse;
}

function frmTipoContrato($idTipoContrato) {
	$objResponse = new xajaxResponse();
	
	if ($idTipoContrato > 0) {
		if (!xvalidaAcceso($objResponse,"al_tipos_contrato_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarTipoContrato').click();"); return $objResponse; }
		
		$query = sprintf("SELECT * FROM al_tipo_contrato WHERE id_tipo_contrato = %s;",
			valTpDato($idTipoContrato, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		
		$objResponse->assign("hddIdTipoContrato","value",$idTipoContrato);
		$objResponse->assign("txtNombre","value",utf8_encode($row['nombre_tipo_contrato']));
		$objResponse->assign("lstModoFactura","value",$row['modo_factura']);
		$objResponse->loadCommands(cargaLstEmpresaFinal($row["id_empresa"],"","lstEmpresa"));
		$objResponse->loadCommands(cargarListFiltroContrato($row["id_filtro_contrato"]));
		$objResponse->loadCommands(cargarLstClaveMovimiento($row["modo_factura"],$row["id_clave_movimiento"],$row["id_clave_movimiento_dev"]));
		$objResponse->loadCommands(cargarLstClaveMovimientoSalidaEntrada($row["id_clave_movimiento_salida"],$row["id_clave_movimiento_entrada"]));
						
	} else {
		if (!xvalidaAcceso($objResponse,"al_tipos_contrato_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarTipoContrato').click();"); return $objResponse; }
		
		$objResponse->loadCommands(cargaLstEmpresaFinal($_SESSION["idEmpresaUsuarioSysGts"],"","lstEmpresa"));
		$objResponse->loadCommands(cargarListFiltroContrato());
		$objResponse->loadCommands(cargarLstClaveMovimiento());
		$objResponse->loadCommands(cargarLstClaveMovimientoSalidaEntrada());
	}
	
	return $objResponse;
}

function guardarTipoContrato($frmTipoContrato) {
	$objResponse = new xajaxResponse();

	mysql_query("START TRANSACTION;");
	
	$idTipoContrato = $frmTipoContrato['hddIdTipoContrato'];
	
	if ($idTipoContrato > 0) {
		if (!xvalidaAcceso($objResponse,"al_tipos_contrato_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE al_tipo_contrato SET			
			nombre_tipo_contrato = %s,
			id_filtro_contrato = %s,
			id_clave_movimiento = %s,
			id_clave_movimiento_dev = %s,
			id_clave_movimiento_salida = %s,
			id_clave_movimiento_entrada = %s,
			modo_factura = %s,
			id_empresa = %s			
		WHERE id_tipo_contrato = %s;",
			valTpDato($frmTipoContrato['txtNombre'], "text"),
			valTpDato($frmTipoContrato['lstFiltroContrato'], "int"),
			valTpDato($frmTipoContrato['lstClaveMovimiento'], "int"),
			valTpDato($frmTipoContrato['lstClaveMovimientoDev'], "int"),
			valTpDato($frmTipoContrato['lstClaveMovimientoSalida'], "int"),
			valTpDato($frmTipoContrato['lstClaveMovimientoEntrada'], "int"),
			valTpDato($frmTipoContrato['lstModoFactura'], "int"),
			valTpDato($frmTipoContrato['lstEmpresa'], "int"),
			valTpDato($idTipoContrato, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
				
	} else {
		if (!xvalidaAcceso($objResponse,"al_tipos_contrato_list","insertar")) { return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO al_tipo_contrato (nombre_tipo_contrato, id_filtro_contrato, id_clave_movimiento, id_clave_movimiento_dev, id_clave_movimiento_salida, id_clave_movimiento_entrada, modo_factura, id_empresa)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($frmTipoContrato['txtNombre'], "text"),
			valTpDato($frmTipoContrato['lstFiltroContrato'], "int"),
			valTpDato($frmTipoContrato['lstClaveMovimiento'], "int"),
			valTpDato($frmTipoContrato['lstClaveMovimientoDev'], "int"),
			valTpDato($frmTipoContrato['lstClaveMovimientoSalida'], "int"),
			valTpDato($frmTipoContrato['lstClaveMovimientoEntrada'], "int"),
			valTpDato($frmTipoContrato['lstModoFactura'], "int"),
			valTpDato($frmTipoContrato['lstEmpresa'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idTipoContrato = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Guardado correctamente.");
	
	$objResponse->script("
	byId('btnCancelarTipoContrato').click();
	byId('btnBuscar').click();");
	
	return $objResponse;
}

function listaTipoContrato($pageNum = 0, $campOrd = "id_tipo_contrato", $tpOrd = "ASC", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
        
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo_contrato.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
		
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre_tipo_contrato LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT 
						tipo_contrato.id_tipo_contrato,
						tipo_contrato.id_empresa,
						tipo_contrato.nombre_tipo_contrato,
						tipo_contrato.id_filtro_contrato,
						tipo_contrato.id_clave_movimiento,
						tipo_contrato.id_clave_movimiento_dev,
						
						(CASE tipo_contrato.modo_factura
							WHEN 1 THEN 'FACTURA'
							WHEN 2 THEN 'VALE SALIDA'
						END) AS descripcion_modo_factura,
						
						empresa.nombre_empresa,
						CONCAT_WS(') ', clave_mov.clave, clave_mov.descripcion) AS descripcion_clave_mov,
						CONCAT_WS(') ', clave_mov_dev.clave, clave_mov_dev.descripcion) AS descripcion_clave_mov_dev,
						CONCAT_WS(') ', clave_mov_salida.clave, clave_mov_salida.descripcion) AS descripcion_clave_mov_salida,
						CONCAT_WS(') ', clave_mov_entrada.clave, clave_mov_entrada.descripcion) AS descripcion_clave_mov_entrada,
						CONCAT_WS('.- ', filtro_contrato.id_filtro_contrato, filtro_contrato.descripcion) AS descripcion_filtro
						
					FROM al_tipo_contrato tipo_contrato
					INNER JOIN pg_empresa empresa ON tipo_contrato.id_empresa = empresa.id_empresa
					LEFT JOIN al_filtro_contrato filtro_contrato ON filtro_contrato.id_filtro_contrato = tipo_contrato.id_filtro_contrato
					LEFT JOIN pg_clave_movimiento clave_mov ON tipo_contrato.id_clave_movimiento = clave_mov.id_clave_movimiento
					LEFT JOIN pg_clave_movimiento clave_mov_dev ON tipo_contrato.id_clave_movimiento_dev = clave_mov_dev.id_clave_movimiento
					LEFT JOIN pg_clave_movimiento clave_mov_salida ON tipo_contrato.id_clave_movimiento_salida = clave_mov_salida.id_clave_movimiento
					LEFT JOIN pg_clave_movimiento clave_mov_entrada ON tipo_contrato.id_clave_movimiento_entrada = clave_mov_entrada.id_clave_movimiento 
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
		$htmlTh .= ordenarCampo("xajax_listaTipoContrato", "1%", $pageNum, "id_tipo_contrato", $campOrd, $tpOrd, $valBusq, $maxRows, "id");
		$htmlTh .= ordenarCampo("xajax_listaTipoContrato", "10%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaTipoContrato", "10%", $pageNum, "nombre_tipo_contrato", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaTipoContrato", "5%", $pageNum, "descripcion_modo_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Modo Factura");
		$htmlTh .= ordenarCampo("xajax_listaTipoContrato", "15%", $pageNum, "descripcion_clave_mov", $campOrd, $tpOrd, $valBusq, $maxRows, "Clave Mov");
		$htmlTh .= ordenarCampo("xajax_listaTipoContrato", "15%", $pageNum, "descripcion_clave_mov_dev", $campOrd, $tpOrd, $valBusq, $maxRows, "Clave Mov Dev");
		$htmlTh .= ordenarCampo("xajax_listaTipoContrato", "15%", $pageNum, "descripcion_clave_mov_salida", $campOrd, $tpOrd, $valBusq, $maxRows, "Clave Mov Salida");
		$htmlTh .= ordenarCampo("xajax_listaTipoContrato", "15%", $pageNum, "descripcion_clave_mov_entrada", $campOrd, $tpOrd, $valBusq, $maxRows, "Clave Mov Entrada");
		$htmlTh .= ordenarCampo("xajax_listaTipoContrato", "10%", $pageNum, "descripcion_filtro", $campOrd, $tpOrd, $valBusq, $maxRows, "Filtro");		
		$htmlTh .= "<td colspan=\"2\" width=\"1%\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
				
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".$row['id_tipo_contrato']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_tipo_contrato'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['descripcion_modo_factura'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_clave_mov'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_clave_mov_dev'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_clave_mov_salida'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_clave_mov_entrada'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['descripcion_filtro'])."</td>";
			
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblTipoContrato', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_tipo_contrato']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a onclick=\"validarEliminar('%s');\"><img class=\"puntero\" src=\"../img/iconos/cross.png\" title=\"Eliminar\"/></a>",
					$row['id_tipo_contrato']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"30\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTipoContrato(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTipoContrato(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaTipoContrato(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTipoContrato(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTipoContrato(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"30\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaTipoContrato","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarTipoContrato");
$xajax->register(XAJAX_FUNCTION,"cargarLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargarLstClaveMovimientoSalidaEntrada");
$xajax->register(XAJAX_FUNCTION,"cargarListFiltroContrato");
$xajax->register(XAJAX_FUNCTION,"eliminarPrecio");
$xajax->register(XAJAX_FUNCTION,"frmTipoContrato");
$xajax->register(XAJAX_FUNCTION,"guardarTipoContrato");
$xajax->register(XAJAX_FUNCTION,"listaTipoContrato");

?>