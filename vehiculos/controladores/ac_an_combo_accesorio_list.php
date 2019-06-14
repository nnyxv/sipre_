<?php
function buscarAccesorio($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$valForm['txtCriterio'],
                $valForm['lstActivoBuscar']);
	
	$objResponse->loadCommands(listadoAccesorio(0, "des_accesorio", "ASC", $valBusq));
	
	return $objResponse;
}

function editarAccesorio($nomObjeto, $idAccesorio) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"an_combo_accesorios_list","editar")) {
	
		$objResponse->script("
		openImg(byId('".$nomObjeto."'));");
		
	
		$objResponse->script("
		document.forms['frmAccesorio'].reset();
		byId('hddIdAccesorio').value = '';
		
		byId('txtCodigo').className = 'inputHabilitado';
		byId('txtDescripcion').className = 'inputHabilitado';
		byId('lstPoseeIva').className = 'inputHabilitado';
		byId('lstActivo').className = 'inputHabilitado';
		byId('txtPrecio').className = 'inputHabilitado';
		byId('lstGeneraComision').className = 'inputHabilitado';");
		
		$query = sprintf("SELECT * FROM an_accesorio
		WHERE id_accesorio = %s",
			valTpDato($idAccesorio, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdAccesorio","value",$idAccesorio);
		$objResponse->assign("txtCodigo","value",utf8_encode($row['nom_accesorio']));
		$objResponse->assign("txtDescripcion","value",utf8_encode($row['des_accesorio']));
		$objResponse->call("selectedOption","lstPoseeIva",$row['iva_accesorio']);
		$objResponse->assign("txtPrecio","value",utf8_encode($row['precio_accesorio']));
                $objResponse->assign("lstActivo","value",$row['activo']);
		/*$objResponse->script("byId('imgGeneraComision').style.display = '';");
		$objResponse->script("byId('lstGeneraComision').onchange = function(){ selectedOption(this.id,'".$row['genera_comision']."'); };");
		$objResponse->call("selectedOption","lstGeneraComision",$row['genera_comision']);*/
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Editar Accesorio");
		$objResponse->script("
		if (byId('divFlotante').style.display == 'none') {
			byId('divFlotante').style.display='';
			centrarDiv(byId('divFlotante'));
			
			byId('txtCodigo').focus();
		}");
	}
	
	return $objResponse;
}

function eliminarAccesorio($idAccesorio, $valFormListaAccesorio) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"an_combo_accesorios_list","eliminar")) {
		mysql_query("START TRANSACTION;");
		
		$deleteSQL = sprintf("DELETE FROM an_accesorio WHERE id_accesorio = %s",
			valTpDato($idAccesorio, "int"));
		$Result1 = mysql_query($deleteSQL);									
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
		mysql_query("COMMIT;");
		
		$objResponse->alert("Accesorio para Combo eliminado exitosamente.");
		
		$objResponse->loadCommands(listadoAccesorio(
			$valFormListaAccesorio['pageNum'],
			$valFormListaAccesorio['campOrd'],
			$valFormListaAccesorio['tpOrd'],
			$valFormListaAccesorio['valBusq']));
	}
	
	return $objResponse;
}

function formValidarPermisoEdicion($hddModulo) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	document.forms['frmPermiso'].reset();
	
	byId('txtContrasena').className = 'inputHabilitado';");
	
	$objResponse->assign("hddModulo","value",$hddModulo);
	
	$objResponse->script("
	byId('tblPermiso').style.display = '';
	byId('tblAccesorio').style.display = 'none';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Ingreso de Clave Especial");
	$objResponse->script("		
	byId('divFlotante').style.display = '';
			
	byId('txtContrasena').focus();");
	
	return $objResponse;
}

function guardarAccesorio($valForm, $valFormListaAccesorio) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($valForm['hddIdAccesorio'] > 0) {
		if (xvalidaAcceso($objResponse,"an_combo_accesorios_list","editar")) {
			$updateSQL = sprintf("UPDATE an_accesorio SET
				nom_accesorio = %s,
				des_accesorio = %s,
				iva_accesorio = %s,				
				precio_accesorio = %s,
				genera_comision = %s,
                                activo = %s
			WHERE id_accesorio = %s;",
				valTpDato($valForm['txtCodigo'], "text"),
				valTpDato($valForm['txtDescripcion'], "text"),
				valTpDato($valForm['lstPoseeIva'], "boolean"),				
				valTpDato($valForm['txtPrecio'], "real_inglesa"),
				valTpDato(0, "boolean"),
                                valTpDato($valForm['lstActivo'], "boolean"),
				valTpDato($valForm['hddIdAccesorio'], "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($updateSQL); 
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$updateSQL);
			mysql_query("SET NAMES 'latin1';");			
			// VERIFICA EXISTENCIA DEL CODIGO
			/*if (!$Result1) {
				if (mysql_errno() == 1062) {
					return $objResponse->alert("El Código '".$valForm['txtCodigo']."' ya se encuentra registrado.");
				} else {
					return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$updateSQL);
				}
			}	*/
			
			
			//CONSULTA COMBOS QUE CONTENGAN EL ACCESORIO A EDITAR
			$queryComboDetalle = sprintf("SELECT id_combo, id_accesorio FROM an_combo_detalle
			WHERE an_combo_detalle.id_accesorio = %s GROUP BY id_combo",
				valTpDato($valForm['hddIdAccesorio'], "int"));
			$rsComboDetalle = mysql_query($queryComboDetalle);
			if (!$rsComboDetalle) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryComboDetalle);
			while($rowComboDetalle = mysql_fetch_assoc($rsComboDetalle)) {
				
				// SUMA LOS ACCESORIOS PERTENECIENTES A LOS COMBOS QUE LO CONTENGAN
				$queryCombo = sprintf("SELECT SUM(precio_accesorio) AS precio_accesorio
				FROM an_combo_detalle
					INNER JOIN an_accesorio ON (an_combo_detalle.id_accesorio = an_accesorio.id_accesorio)			
				WHERE id_combo = %s",
					valTpDato($rowComboDetalle['id_combo'], "int")); 
				$rsCombo = mysql_query($queryCombo);
				if (!$rsCombo) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryCombo);
				$rowCombo = mysql_fetch_assoc($rsCombo);
				
				// CONSULTA EL IVA
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1");
				$rsIva = mysql_query($queryIva);
				if (!$rsIva) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryIva);
				$rowIva = mysql_fetch_assoc($rsIva);
										
				$totalSinIva = $rowCombo['precio_accesorio'];
				$iva = ($rowIva['iva'] * $rowCombo['precio_accesorio']) / 100;
				$precioFinal = $totalSinIva + $iva;
				
				// ACTUALIZA EL TOTAL DE LOS COMBOS
				$updateCombo = sprintf("UPDATE an_combo SET
					total_sin_iva = %s,
					total_iva = %s,
					total_con_iva = %s
				WHERE id_combo = %s;",
					valTpDato($totalSinIva, "real_inglesa"),
					valTpDato($iva, "real_inglesa"),
					valTpDato($precioFinal, "real_inglesa"),
					valTpDato($rowComboDetalle['id_combo'], "int"));
				mysql_query("SET NAMES 'utf8'");
				$rsCombo = mysql_query($updateCombo); 
				if (!$rsCombo) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$updateCombo);
				mysql_query("SET NAMES 'latin1';");
				
				// 
				$updateSQL = sprintf("UPDATE an_presupuesto_accesorio_detalle, an_presupuesto_accesorio presto_acc, an_presupuesto presto SET
					precio_unitario = %s,
					iva_unitario = (((CASE (SELECT acc.iva_accesorio FROM an_accesorio acc
											WHERE acc.id_accesorio = an_presupuesto_accesorio_detalle.id_accesorio)
										WHEN 1 THEN
											(SELECT iva FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1 ORDER BY iva)
										ELSE
											0
									END) * precio_unitario) / 100)
				WHERE an_presupuesto_accesorio_detalle.id_presupuesto_accesorio = presto_acc.id_presupuesto_accesorio
					AND presto_acc.id_presupuesto = presto.id_presupuesto
					AND an_presupuesto_accesorio_detalle.id_accesorio = %s
					AND presto.estado = 0;",
					valTpDato($valForm['txtPrecio'], "real_inglesa"),
					valTpDato($valForm['hddIdAccesorio'], "int"));
				mysql_query("SET NAMES 'utf8'");
				$Result = mysql_query($updateSQL); 
				if (!$Result) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$updateSQL);
				mysql_query("SET NAMES 'latin1';");
				
				//
				$updateSQL = sprintf("UPDATE an_presupuesto_accesorio SET
					subtotal = (SELECT SUM(presto_acc_det.precio_unitario) FROM an_presupuesto_accesorio_detalle presto_acc_det
									WHERE presto_acc_det.id_presupuesto_accesorio = an_presupuesto_accesorio.id_presupuesto_accesorio),
					subtotal_iva = (SELECT SUM(presto_acc_det.iva_unitario) FROM an_presupuesto_accesorio_detalle presto_acc_det
										WHERE presto_acc_det.id_presupuesto_accesorio = an_presupuesto_accesorio.id_presupuesto_accesorio)
				WHERE id_presupuesto IN (SELECT presto.id_presupuesto FROM an_presupuesto presto
											WHERE presto.estado = 0);");
				mysql_query("SET NAMES 'utf8'");
				$Result = mysql_query($updateSQL); 
				if (!$Result) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$updateSQL);
				mysql_query("SET NAMES 'latin1';");
			}
		} else {
			return $objResponse;
		}
	} else {
		if (xvalidaAcceso($objResponse,"an_combo_accesorios_list","insertar")) {
			$insertSQL = sprintf("INSERT INTO an_accesorio (id_modulo, id_tipo_accesorio, nom_accesorio, des_accesorio, iva_accesorio, precio_accesorio, costo_accesorio, genera_comision, incluir_costo_compra_unidad, activo)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato(2, "int"), // 2 = Vehiculos
				valTpDato(2, "int"), // 1 = Adicional, 2 = Accesorio, 3 = Contrato
				valTpDato($valForm['txtCodigo'], "text"),
				valTpDato($valForm['txtDescripcion'], "text"),
				valTpDato($valForm['lstPoseeIva'], "boolean"),				
				valTpDato($valForm['txtPrecio'], "real_inglesa"),
				valTpDato(0, "double"),
				valTpDato(0, "boolean"),
				valTpDato(0, "boolean"),
                                valTpDato($valForm['lstActivo'], "boolean"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$insertSQL);
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Accesorio para Combo guardado con éxito.");
	
	$objResponse->script("byId('btnCancelar').click();");
	
	$objResponse->loadCommands(listadoAccesorio(
		$valFormListaAccesorio['pageNum'],
		$valFormListaAccesorio['campOrd'],
		$valFormListaAccesorio['tpOrd'],
		$valFormListaAccesorio['valBusq']));
	
	return $objResponse;
}

function listadoAccesorio($pageNum = 0, $campOrd = "des_accesorio", $tpOrd = "ASC", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_tipo_accesorio = 2");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_modulo IN (2)");
			
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("nom_accesorio LIKE %s
			OR des_accesorio LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
        
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("activo = %s",
			valTpDato($valCadBusq[1], "boolean"));
	}
        
	$query = sprintf("SELECT * FROM an_accesorio %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoAccesorio", "1%", $pageNum, "activo", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listadoAccesorio", "20%", $pageNum, "nom_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listadoAccesorio", "50%", $pageNum, "des_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listadoAccesorio", "10%", $pageNum, "precio_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Precio Sin I.V.A");
		$htmlTh .= ordenarCampo("xajax_listadoAccesorio", "10%", $pageNum, "precio_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "I.V.A 12%");
		$htmlTh .= ordenarCampo("xajax_listadoAccesorio", "10%", $pageNum, "precio_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Precio Final");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		if ($row['iva_accesorio'] == 1) {
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1");
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryIva);
			$rowIva = mysql_fetch_assoc($rsIva);
		}
		
		$iva = ($rowIva['iva'] * $row['precio_accesorio']) / 100;
		$accesorioIva = ($rowIva['iva'] * $row['precio_accesorio']) / 100 + $row['precio_accesorio'];
		$accesorio = $row['precio_accesorio'];
		
                $imgActivo = "<img src=\"../img/iconos/ico_verde.gif\">";
                
                if($row['activo'] == "0"){
                    $imgActivo = "<img src=\"../img/iconos/ico_rojo.gif\">";
                }
                
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgActivo."</td>";
			$htmlTb .= "<td>".htmlentities($row['nom_accesorio'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['des_accesorio'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['precio_accesorio'], 2, ".", ",")."</td>";
		if ($row['iva_accesorio'] == 1) {
			$htmlTb .= "<td align=\"right\">".number_format($iva, 2, ".", ",")."</td>";
		} else {
			$htmlTb .= "<td align=\"right\">".htmlentities("-")."</td>";
		}
		
		if ($row['iva_accesorio'] == 1) {
			$htmlTb .= "<td align=\"right\">".number_format($accesorioIva, 2, ".", ",")."</td>";			
		} else {
			$htmlTb .= "<td align=\"right\">".number_format($accesorio, 2, ".", ",")."</td>";			
		}				
		
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante\" onclick=\"xajax_editarAccesorio(this.id,'%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_accesorio']);
			$htmlTb .= "</td>";
                            if($row['id_filtro_factura'] == ""){
				$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"../img/iconos/ico_delete.png\"/></td>",
				$row['id_accesorio']);
                            }
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoAccesorio(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
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
		$htmlTb .= "<td colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
        $objResponse->assign("tdListaAccesorio","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function nuevoAccesorio($nomObjeto) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"an_combo_accesorios_list","insertar")) {
		$objResponse->script("
		openImg(byId('".$nomObjeto."'));");
		
		$objResponse->script("
		document.forms['frmAccesorio'].reset();
		byId('hddIdAccesorio').value = '';
		
		byId('txtCodigo').className = 'inputHabilitado';
		byId('txtDescripcion').className = 'inputHabilitado';
		byId('lstPoseeIva').className = 'inputHabilitado';
		byId('lstActivo').className = 'inputHabilitado';
		byId('txtPrecio').className = 'inputHabilitado';
		byId('lstGeneraComision').className = 'inputHabilitado';");
		
		$objResponse->script("byId('imgGeneraComision').style.display = '';");
		$objResponse->script("byId('lstGeneraComision').onchange = function(){ selectedOption(this.id,'0'); };");
		$objResponse->call("selectedOption","lstGeneraComision",0);
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Agregar Accesorio para Combo");
		
		$objResponse->script("
		byId('tblPermiso').style.display = 'none';
		byId('tblAccesorio').style.display = '';
		byId('txtCodigo').focus();");
	}
	
	return $objResponse;
}

function validarPermiso($valForm) {
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT * FROM vw_pg_claves_modulos
	WHERE id_usuario = %s
		AND contrasena = %s
		AND modulo = %s;",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($valForm['txtContrasena'], "text"),
		valTpDato($valForm['hddModulo'], "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	if ($rowPermiso['id_clave_usuario'] != "") {
		if ($valForm['hddModulo'] == "an_accesorio_list_genera_comision") {
			$objResponse->script("byId('btnCancelarPermiso').click();");
			
			$objResponse->script("byId('lstGeneraComision').onchange = function(){};");
			$objResponse->script("byId('imgGeneraComision').style.display = 'none';");
			
		} else if ($valForm['hddModulo'] == "an_accesorio_list_incluir_costo_unidad") {
			$objResponse->script("byId('btnCancelarPermiso').click();");
			
		}
	} else {
		$objResponse->alert("Permiso No Autorizado");
		$objResponse->script("byId('btnCancelarPermiso').click();");
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarAccesorio");
$xajax->register(XAJAX_FUNCTION,"editarAccesorio");
$xajax->register(XAJAX_FUNCTION,"eliminarAccesorio");
$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion");
$xajax->register(XAJAX_FUNCTION,"guardarAccesorio");
$xajax->register(XAJAX_FUNCTION,"listadoAccesorio");
$xajax->register(XAJAX_FUNCTION,"nuevoAccesorio");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");
?>