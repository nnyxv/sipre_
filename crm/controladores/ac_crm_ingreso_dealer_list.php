<?php


function agregarColor($id_dealer = 0, $tipo_entrada = '', $color = '') {
	$objResponse = new xajaxResponse();
		
		if($id_dealer == 0){
			$objResponse->script("$('#txtColor').val('$color')");
			$objResponse->script("$('#imgCerrarDivFlotante3').click()");
		} else{
			$objResponse->alert("Este color ya esta relacionado con el tipo de ingreso: $tipo_entrada"); 
		}
	
	return $objResponse;
}

function buscarEmpresa($frmBuscarEmpresa) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarEmpresa['hddObjDestino'],
		$frmBuscarEmpresa['hddNomVentana'],
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
		
	$objResponse->loadCommands(listadoEmpresasUsuario(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function asignarEmpresa($idEmpresa) {
	$objResponse = new xajaxResponse();
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_usuario_empresa 
								WHERE id_empresa_reg = %s", valTpDato($idEmpresa, "int"));
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
	$objResponse->assign("txtIdEmpresa","value",$rowEmpresa['id_empresa_reg']);
	
	$nombreSucursal = "";
	if ($rowEmpresa['id_empresa_padre_suc'] > 0)
		$nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";
	
	$objResponse->assign("txtEmpresa","value",utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal));
	
	$objResponse->script(
	"byId('btnCancelar2').click();
	");
	
	return $objResponse;
}

function buscarIngreso($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['lstEmpresa']);
	
	$objResponse->loadCommands(listaModoIngreso(0, "", "", $valBusq));
	
	return $objResponse;
}

function cargarIngreso($nomObjeto, $id_ingreso) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"crm_ingreso_dealer_list","editar")) {
		$objResponse->script("openImg(byId('".$nomObjeto."'));");
		
		$objResponse->script("document.forms['frmIngreso'].reset();
							  byId('hddIdIngreso').value = '';
		 					  $('#btnGuardar').val('2');
				
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('txtNombre').className = 'inputHabilitado';
		byId('txtColor').className = 'inputHabilitado';
		byId('lstEstatus').className = 'inputHabilitado';");
	
		$query = sprintf("SELECT * FROM crm_ingreso_prospecto 
							WHERE id_dealer = %s;",
					valTpDato($id_ingreso, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdIngreso","value",$row['id_dealer']);
		$objResponse->loadCommands(asignarEmpresa($row['id_empresa']));
		$objResponse->assign("txtNombre","value",utf8_encode($row['tipo_entrada']));
		$objResponse->assign("txtColor","value",utf8_encode($row['color_identificador']));
		$objResponse->call("selectedOption","lstEstatus",$row['estado']);
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Editar Ingreso al Dealer");
		$objResponse->script("centrarDiv(byId('divFlotante2'));");
	}
	
	return $objResponse;
}

function cargaLstEmpresa($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY nombre_empresa",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstEmpresa\" name=\"lstEmpresa\" onchange=\"$('btnBuscar').click();\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$nombreSucursal = "";
		if ($row['id_empresa_padre_suc'] > 0)
			$nombreSucursal = " - ".$row['nombre_empresa_suc']." (".$row['sucursal'].")";
	
		$selected = "";
		if ($selId == $row['id_empresa_reg'] || $idEmpresa == $row['id_empresa_reg'])
			$selected = "selected='selected'";
		
		$html .= "<option ".$selected." value=\"".$row['id_empresa_reg']."\">".htmlentities($row['nombre_empresa'].$nombreSucursal)."</option>";
		
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEmpresa","innerHTML",$html);
	
	return $objResponse;
}

function eliminarDealer($id_dealer) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"crm_sector_list","eliminar")) {
		mysql_query("START TRANSACTION;");

		$deleteSQL = sprintf("DELETE FROM crm_ingreso_prospecto 
								WHERE id_dealer = %s;",
						valTpDato($id_dealer, "int"));
		$Result = mysql_query($deleteSQL);
		if (!$Result) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
	
		$objResponse->script("byId('btnBuscar').click();");
		$objResponse->alert("Eliminación Realizada con Éxito");
		$objResponse->script("byId('btnCancelar').click();");
	}
	
	return $objResponse;
}

function formIngreso($nomObjeto) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"crm_ingreso_dealer_list","insertar")) {
		$objResponse->script("openImg(byId('".$nomObjeto."'));");
		
		$objResponse->script("document.forms['frmIngreso'].reset();
							  byId('hddIdIngreso').value = '';
							  $('#btnGuardar').val('1')
		
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('txtNombre').className = 'inputHabilitado';
		byId('txtColor').className = 'inputHabilitado';
		byId('lstEstatus').className = 'inputHabilitado';");
		
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
		$objResponse->loadCommands(asignarEmpresa($idEmpresa));
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Agregar Ingreso al Dealer");
		$objResponse->script("centrarDiv(byId('divFlotante2'));");
	}
	
	return $objResponse;
}

function guardarIngreso($valForm, $edit = 1) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($edit == 2) {
		if (xvalidaAcceso($objResponse,"crm_ingreso_dealer_list","editar")) {
			
			$query = sprintf("SELECT * FROM crm_ingreso_prospecto
								WHERE tipo_entrada LIKE %s AND id_dealer <> %s",
					valTpDato($valForm['txtNombre'], "text"),
					valTpDato($valForm['hddIdIngreso'], "int"));
			$Result = mysql_query($query);
			if (!$Result) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$query);
				
			$totalRows = mysql_num_rows($Result);
				
			if($totalRows > 0){
				$objResponse->alert("Este nombre ya se encuentra Registrado");
				return $objResponse;
			}
			
			$updateSQL = sprintf("UPDATE crm_ingreso_prospecto SET
										id_empresa = %s,
										tipo_entrada = %s,
										color_identificador = %s,
										estado = %s
									WHERE id_dealer = %s;",
							valTpDato($valForm['txtIdEmpresa'], "int"),
							valTpDato($valForm['txtNombre'], "text"),
							valTpDato($valForm['txtColor'], "text"),
							valTpDato($valForm['lstEstatus'], "boolean"),
							valTpDato($valForm['hddIdIngreso'], "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$updateSQL);
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	} else {
		if (xvalidaAcceso($objResponse,"crm_ingreso_dealer_list","insertar")) {
			
			$query = sprintf("SELECT * FROM crm_ingreso_prospecto 
								WHERE tipo_entrada LIKE %s",
						valTpDato($valForm['txtNombre'], "text"));
			$Result = mysql_query($query);
			if (!$Result) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$query);
			
			$totalRows = mysql_num_rows($Result);
			
			if($totalRows > 0){
				$objResponse->alert("Este nombre ya se encuentra Registrado");
				return $objResponse;
			}
			
			$insertSQL = sprintf("INSERT INTO crm_ingreso_prospecto (id_empresa, tipo_entrada, color_identificador, estado)
									VALUE (%s, %s, %s, %s);",
							valTpDato($valForm['txtIdEmpresa'], "int"),
							valTpDato($valForm['txtNombre'], "text"),
							valTpDato($valForm['txtColor'], "text"),
							valTpDato($valForm['lstEstatus'], "boolean"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Ingreso al Dealer Guardada con Éxito");
	
	$objResponse->script("$('#btnBuscar').click();");
	$objResponse->script("$('#btnCancelar').click();");
	
	return $objResponse;
}

function listaModoIngreso($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ingreso_prospecto.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	$query = sprintf("SELECT ingreso_prospecto.*,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM crm_ingreso_prospecto ingreso_prospecto
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (ingreso_prospecto.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	
	mysql_query("SET NAMES 'utf8'");
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaActividad", "8%", $pageNum, "id_actividad", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaModoIngreso", "16%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaModoIngreso", "56%", $pageNum, "tipo_entrada", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaModoIngreso", "20%", $pageNum, "color_identificador", $campOrd, $tpOrd, $valBusq, $maxRows, "Color Identificación");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['estado']){
			case 0 : $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1 : $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatus = ""; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['id_dealer'])."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td>".$row['tipo_entrada']."</td>";
			$htmlTb .= "<td align=\"center\" style=\"background-color:".$row['color_identificador']."; color:#FFFFFF\"><b>".$row['color_identificador']."</b></td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante\" onclick=\"xajax_cargarIngreso(this.id,'%s');\"><img class=\"puntero\" src=\"../img/iconos/ico_edit.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_dealer']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"../img/iconos/ico_delete.png\"/>",
					$row['id_dealer']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf .= "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"50\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaModoIngreso(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaModoIngreso(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaModoIngreso(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaModoIngreso(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaModoIngreso(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
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
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaConfiguracion","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

function listadoColor($selId = '') {
	$objResponse = new xajaxResponse();

	$arrayColor = array('#EEEEEE', '#FFFFFF', '#F9F7ED', '#FFFF88', '#CDEB8B', '#C3D9FF', '#36393D', 
						'#FF1A00', '#CC0000', '#FF7400', '#008C00', '#006E2E', '#4096EE', '#FF0084',
						'#B02B2C', '#D15600', '#C79810', '#73880A', '#6BBA70', '#3F4C6B', '#356AA0', '#D01F3C');

	if ($selId != '') {
		$sqlBusq = $cond.sprintf("WHERE id_empresa = %s",
			valTpDato($selId, "int"));
	}

	$query = sprintf("SELECT * FROM crm_ingreso_prospecto %s",
		$sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	
	$html = "<div id='divListColor'></div>";
		$html .= "<br>";
		$html .= "<fieldset><legend class='legend'>Seleccione el Color del Identificador</legend>";
			$html .= "<div style='margin: 8px 0px 30px 0px; width: 100%;'>";
	
			while ($row = mysql_fetch_assoc($rs)){
				$rowColor[] = $row;
			}
	
			$i = 0;
			foreach ($arrayColor as $color){
				$select = '';
				$texto = '';
				if(count($rowColor) > 0 ){
					foreach ($rowColor as $row){
		
						if($row['color_identificador'] == $color){
							$select = "fade-in";
							$texto = "{$row['tipo_entrada']}";
							break;
						} else{
							$texto = "Disponible";
							$row['id_dealer'] = 0;
						}
					}
				} else{
					$texto = "Disponible";
					$row['id_dealer'] = 0;
				}
				
			  	$html .= "<div id='idIngreso_{$row['id_dealer']}' onclick=\"xajax_agregarColor({$row['id_dealer']}, '$texto', '$color');\" class='divColor puntero' style='background: {$color};'>&nbsp;<div class='fade-in'>$texto</div></div>";
	
		  		$i++;
		  		if($i > 6){
		  			$html .= "</div>";
		  			$html .= "<div style='margin: 8px 0px 30px 0px; width: 100%;'>";
		  			$i = 0;
		  		}
			}
		$html .= "</fieldset>";
	$html .= "</div>";
	
	$objResponse->assign("divListColor","innerHTML",$html);

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"agregarColor");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarIngreso");
$xajax->register(XAJAX_FUNCTION,"cargarIngreso");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresa");
$xajax->register(XAJAX_FUNCTION,"eliminarDealer");
$xajax->register(XAJAX_FUNCTION,"formIngreso");
$xajax->register(XAJAX_FUNCTION,"guardarIngreso");
$xajax->register(XAJAX_FUNCTION,"listadoColor");
$xajax->register(XAJAX_FUNCTION,"listaModoIngreso");
$xajax->register(XAJAX_FUNCTION,"listadoEmpresas");
?>