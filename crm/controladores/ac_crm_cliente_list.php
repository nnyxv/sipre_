<?php
function buscarCliente($frmBuscar) {
	$objResponse = new xajaxResponse();
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['rdbtSexo'],
		$frmBuscar['textFechaDesde'],
		$frmBuscar['textFechaHasta'],
		$frmBuscar['lstEstatus'],
		$frmBuscar['lstTipoCliente'],
		$frmBuscar['lstCliente'],
		$frmBuscar['listModelo'],
		$frmBuscar['txtCriterio']);
		
	$objResponse->loadCommands(listaCliente(0, "id", "ASC", $valBusq));
		
	return $objResponse;
}

function combListModelo(){
	$objResponse = new xajaxResponse();
	
		$query = "SELECT * FROM an_modelo ORDER BY nom_modelo";
		$rs = mysql_query($query);
		
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

		$selectModelo .= "<select style='width:86%' name=\"listModelo\" id=\"listModelo\" class=\"inputHabilitado\"  onchange=\"byId('btnBuscar').click();\">";
			$selectModelo .= "<option value=\"-1\">[ Todo ]</option>";
			
		while($rws = mysql_fetch_array($rs)){
 				
				$selectModelo .= sprintf("<option value=\"%s\">%s</option>",
					$rws['id_modelo'],
					utf8_encode($rws['nom_modelo']));
		}
			$selectModelo .= "</select>";
			
	$objResponse->assign("tdModeloList","innerHTML",$selectModelo);

	return $objResponse;	
}

function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
//$objResponse->alert($valBusq);	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cj_cc_cliente_empresa.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("crm_perfil_prospecto.sexo = %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "" && $valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fcreacion BETWEEN %s AND %s ", 
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "text"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "text"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("status = %s ", 
			valTpDato($valCadBusq[4], "text"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo = %s ", 
			valTpDato($valCadBusq[5], "text"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo_cuenta_cliente = %s ", 
			valTpDato($valCadBusq[6], "int"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modelo = %s ", 
			valTpDato($valCadBusq[7], "int"));
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf(" CONCAT_Ws(' ', nombre, apellido) LIKE %s
			OR cj_cc_cliente.correo LIKE %s
			OR CONCAT_WS('-', lci, ci) LIKE %s",
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"));
	}

	$query = sprintf("SELECT DISTINCT cj_cc_cliente.id, cj_cc_cliente_empresa.id_empresa,nombre_empresa,
						tipo,CONCAT_Ws(' ', nombre, apellido) AS nombre_apellido_cliente,
						CONCAT_WS('-', lci, ci) AS ci_cliente, telf, 
						cj_cc_cliente.correo, status, tipo_cuenta_cliente,
						fecha_nacimiento, crm_perfil_prospecto.sexo,
						sa_cita.id_registro_placas,
						en_registro_placas.id_unidad_fisica,
						id_unidad_basica, placa,
						an_uni_bas.id_uni_bas, mar_uni_bas, nom_marca, mod_uni_bas, id_modelo, nom_modelo, cj_cc_cliente.fcreacion 
					FROM cj_cc_cliente
						LEFT JOIN cj_cc_cliente_empresa ON cj_cc_cliente_empresa.id_cliente = cj_cc_cliente.id
						LEFT JOIN pg_empresa ON pg_empresa.id_empresa = cj_cc_cliente_empresa.id_empresa
						LEFT JOIN crm_perfil_prospecto ON crm_perfil_prospecto.id = cj_cc_cliente.id
						LEFT JOIN sa_cita ON cj_cc_cliente.id = sa_cita.id_cliente_contacto
						LEFT JOIN en_registro_placas ON en_registro_placas.id_registro_placas = sa_cita.id_registro_placas
						LEFT JOIN an_uni_bas ON an_uni_bas.id_uni_bas = en_registro_placas.id_unidad_basica
						LEFT JOIN an_marca ON id_marca = mar_uni_bas
						LEFT JOIN an_modelo ON id_modelo = mod_uni_bas 
					%s GROUP BY cj_cc_cliente.id", $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
//$objResponse->alert($queryLimit);	
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
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "5%", $pageNum, "cj_cc_cliente.id", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("id"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "30%", $pageNum, "nombre_apellido_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Nombre / Apellido"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "5%", $pageNum, "fecha_nacimiento", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Fecha Creación"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "5%", $pageNum, "fcreacion", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Fecha Nacimiento"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "5%", $pageNum, "crm_perfil_prospecto.sexo", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Sexo"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "C.I. / R.I.F.");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "telf", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("telf"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "15%", $pageNum, "cj_cc_cliente.correo", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("correo"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "5%", $pageNum, "tipo", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Tipo Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "5%", $pageNum, "nom_modelo", $campOrd, $tpOrd, $valBusq, $maxRows, "");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		$fechaCreacion = ($row['fcreacion'] == "") ? "" : date('d-m-Y',strtotime($row['fcreacion']));
		$fechaNacimiento = ($row['fecha_nacimiento'] == "") ? "" : date('d-m-Y',strtotime($row['fecha_nacimiento']));
		
			switch($row['status']){
				case "Activo": $estado = "<img src=\"../img/iconos/ico_verde.gif\" />"; break;
				case "Inactivo": $estado = "<img src=\"../img/iconos/ico_rojo.gif\" />"; break;
			}
			
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td>".$estado."</td>",
				$row['id'],
				utf8_encode($row['nombre_cliente'])); 
			$htmlTb .= "<td align=\"left\">".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_apellido_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".$fechaCreacion."</td>";
			$htmlTb .= "<td align=\"center\">".$fechaNacimiento."</td>";
			$htmlTb .= "<td align=\"center\">".$row['sexo']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['ci_cliente']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['telf']."</td>";
			$htmlTb .= "<td align=\"left\">".$row['correo']."</td>";
			$htmlTb .= "<td align=\"left\">".$row['tipo']."</td>";
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .= sprintf("<button type=\"button\" id= \"butShowModelo%s\" name=\"butShowModelo\" title=\"Ver Modelo\" onclick=\"mostrarOcultatVehiculo(%s)\" > 
								<img src=\"../img/iconos/plus.png\" align=\"right\" title=\"Ver\" class=\"puntero\"  id=\"imgMostrarCliente%s\"/>
							</button>",$row['id'],$row['id'],$row['id']);
				$htmlTb .= sprintf("<button type=\"button\" id=\"butHideModelo%s\" name=\"butHideModelo\" title=\"Ocultar Modelo\" style=\"display:none\" onclick=\"mostrarOcultatVehiculo(%s)\" > 
								<img src=\"../img/iconos/minus.png\" align=\"right\" title=\"Ver\" class=\"puntero\"  id=\"imgMostrarCliente%s\"/>
							</button>",$row['id'],$row['id'],$row['id']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$htmlTb .= sprintf("<tr>
			<td colspan=\"11\" id=\"tdModeloCliente%s\" style=\"display:none\"></td>
					</tr>",$row['id']);

	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"15\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCliente(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"11\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaCliente","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

//LISTA LOS VHEICULOS DEL CLIENTE
function listDescriccionVehiculos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL){
	
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$idCliente = $valCadBusq[0];
	$meses = $valCadBusq[1];
	

	$startRow = $pageNum * $maxRows;
	
	$query = sprintf("SELECT id_registro_placas,id_cliente_registro, chasis, placa, en_registro_placas.kilometraje,
						  en_registro_placas.id_unidad_basica,
						  id_uni_bas, des_uni_bas,mar_uni_bas, nom_marca, mod_uni_bas,nom_modelo,des_modelo,
						  fecha_venta
						FROM en_registro_placas
						  LEFT JOIN cj_cc_cliente ON cj_cc_cliente.id = en_registro_placas.id_cliente_registro
						  LEFT JOIN an_uni_bas ON an_uni_bas.id_uni_bas = en_registro_placas.id_unidad_basica
						  LEFT JOIN an_marca ON an_marca.id_marca = an_uni_bas.mar_uni_bas
						  LEFT JOIN an_modelo ON an_modelo.id_modelo = an_uni_bas.mod_uni_bas
						WHERE id_cliente_registro = %s
						ORDER BY en_registro_placas.id_cliente_registro",$idCliente);
						  
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
		//$objResponse->alert($queryLimit);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
		$htmlTableIni .= "<table border=\"0\" width=\"75%\" align=\"center\">";
		$htmlTh .= "<tr class=\"tituloColumna\">";
			$htmlTh .= ordenarCampo("xajax_listDescriccionVehiculos", "15%", $pageNum, "nom_marca", $campOrd, $tpOrd, $valBusq, $maxRows, "Marca");
			$htmlTh .= ordenarCampo("xajax_listDescriccionVehiculos", "15%", $pageNum, "nom_modelo", $campOrd, $tpOrd, $valBusq, $maxRows, "Modelo"); 
			$htmlTh .= ordenarCampo("xajax_listDescriccionVehiculos", "10%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, "N Placa"); 
			$htmlTh .= ordenarCampo("xajax_listDescriccionVehiculos", "25%", $pageNum, "chasis", $campOrd, $tpOrd, $valBusq, $maxRows, "N de Chasis");
			$htmlTh .= ordenarCampo("xajax_listDescriccionVehiculos", "25%", $pageNum, "des_uni_bas", $campOrd, $tpOrd, $valBusq, $maxRows, "Descricion de la Unidad Basica");
			$htmlTh .= ordenarCampo("xajax_listDescriccionVehiculos", "10%", $pageNum, "fecha_venta", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha de Venta");
			$htmlTh .= "<td></td>";
		$htmlTh .= "</tr>";
		
		$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$fechaVentas = ($row['fecha_venta'] == "") ? "" : date('d-m-Y',strtotime($row['fecha_venta']));

		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td align=\"left\">%s</td>",utf8_encode($row['nom_marca']));
			$htmlTb .= sprintf("<td align=\"left\">%s</td>",utf8_encode($row['nom_modelo']));
			$htmlTb .= sprintf("<td align=\"center\">%s</td>",utf8_encode($row['placa']));
			$htmlTb .= sprintf("<td align=\"center\">%s</td>",utf8_encode($row['chasis']));
			$htmlTb .= sprintf("<td align=\"left\">%s</td>",utf8_encode($row['des_uni_bas']));
			$htmlTb .= sprintf("<td align=\"left\">%s</td>",utf8_encode($row['fecha_venta']));//
		$htmlTb .= "</tr>";
	}
	
		$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"8\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listDescriccionVehiculos(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listDescriccionVehiculos(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listDescriccionVehiculos(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listDescriccionVehiculos(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listDescriccionVehiculos(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("tdModeloCliente".$idCliente."","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
			
	 return $objResponse;
	}

	function exportarExcel($frmBuscar) {
		$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['rdbtSexo'],
		$frmBuscar['textFechaDesde'],
		$frmBuscar['textFechaHasta'],
		$frmBuscar['lstEstatus'],
		$frmBuscar['lstTipoCliente'],
		$frmBuscar['lstCliente'],
		$frmBuscar['listModelo'],
		$frmBuscar['txtCriterio']);
	
		$objResponse->script("window.open('reportes/crm_reporteCleinteExcel.php?valBusq=".$valBusq."','_self');");
	
		return $objResponse;
	}

$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"combListModelo");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listDescriccionVehiculos");
$xajax->register(XAJAX_FUNCTION, "exportarExcel");
?>