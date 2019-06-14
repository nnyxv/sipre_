<?php 
/*LISTA LAS EMPRESA AL PRESIONAR EL BOTON DEL FORMULARIO*/
function listadoEmpresas($pageNum = 0, $campOrd = "nombre_empresa", $tpOrd = "ASC", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$query = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"));
		
	mysql_query("SET NAMES 'utf8'");
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
		$htmlTh .= ordenarCampo("xajax_listadoEmpresas", "15%", $pageNum, "id_empresa_reg", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listadoEmpresas", "20%", $pageNum, "rif", $campOrd, $tpOrd, $valBusq, $maxRows, "RIF");
		$htmlTh .= ordenarCampo("xajax_listadoEmpresas", "35%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listadoEmpresas", "30%", $pageNum, "nombre_empresa_suc", $campOrd, $tpOrd, $valBusq, $maxRows, "Sucursal");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$idEmpresaReg = $row['id_empresa_reg'];
		$rif = $row['rif'];
		$mombreEmpresa = htmlentities($row['nombre_empresa']);
		$nombreEmpresaSuc = $row['nombre_empresa_suc'];
		$sucuradal = $row['sucursal'];
		
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
	
		$nombreSucursal = "";
		if ($row['id_empresa_padre_suc'] > 0)
			$nombreSucursal = $nombreEmpresaSuc." (".$sucuradal.")";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarEmpresa('".$idEmpresaReg."');\" title=\"Seleccionar Empresa\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td>".$idEmpresaReg."</td>";
			$htmlTb .= "<td align=\"right\">".$rif."</td>";
			$htmlTb .= "<td>".$mombreEmpresa."</td>";
			$htmlTb .= "<td>".$nombreSucursal."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresas(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresas(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoEmpresas(%s, '%s', '%s', '%s', %s)\">", 
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf .="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .="selected=\"selected\"";
									}
									$htmlTf .= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresas(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresas(%s, '%s', '%s', '%s', %s);\">%s</a>", 
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
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListaEmpresa","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
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



/*ASIGNA LA EMPRESA SEGUN LA SELECCIONADO DEL LISTADO DE EMPRESA*/
function asignarEmpresa($idEmpresa) {
	$objResponse = new xajaxResponse();
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_empresa_reg = %s", valTpDato($idEmpresa, "int"));
	mysql_query("SET NAMES 'utf8'");
	
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
	$idEmpresaReg = $rowEmpresa['id_empresa_reg'];
	$nombreEmpresaSuc = $rowEmpresa['nombre_empresa_suc'];
	$sucursal = $rowEmpresa['sucursal'];
	$nombreEmpresa = $rowEmpresa['nombre_empresa'];
	
	$objResponse->assign("txtIdEmpresa","value",$idEmpresaReg);
	
	$nombreSucursal = "";
	if ($rowEmpresa['id_empresa_padre_suc'] > 0)
		$nombreSucursal = " - ".$nombreEmpresaSuc." (".$sucursal.")";
	
	$objResponse->assign("txtNombreEmpresa","value",$nombreEmpresa.$nombreSucursal);
	
	$objResponse->script(
	"byId('btnCancelar2').click();
	");
	
	return $objResponse;
}

//LISTA LOS DOCUMENTOS 
function listDocumentos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL){ 
	
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("crm_documentos_ventas.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
		$query = sprintf("SELECT id_documento_venta, descripcion_documento, id_tipo_documento, idItem, item, activo, crm_documentos_ventas.id_empresa, nombre_empresa
						  FROM crm_documentos_ventas
							  LEFT JOIN pg_empresa ON pg_empresa.id_empresa = crm_documentos_ventas.id_empresa
							  LEFT JOIN grupositems ON grupositems.idItem = crm_documentos_ventas.id_tipo_documento %s",
						  $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);//mysql_query("SET NAMES 'utf8'");
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listDocumentos", "30%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Empresa");
		$htmlTh .= ordenarCampo("xajax_listDocumentos", "40%", $pageNum, "descripcion_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripcion del documento");
		$htmlTh .= ordenarCampo("xajax_listDocumentos", "30%", $pageNum, "item", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de documento");
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) { //AQUI ESPESIFICO EL ESTILO Y EL COLOR AL MOVER EL MOUSE SOBRE LOS REGISTRO
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
	
		$idDocumentoVenta = $row['id_documento_venta'];
		$descripcionDocumento = $row['descripcion_documento'];
		$item = $row['item'];
		$nombreEmpresa = $row['nombre_empresa'];
	
		$imgEstatus = "";
		if ($row['activo'] == 0)
			$imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>";
		else if ($row['activo'] == 1)
			$imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td align=\"center\">".$nombreEmpresa."</td>";
			//Llena la tabla segun el campo solicitado
			$htmlTb .= "<td align=\"left\">".$descripcionDocumento."</td>";
			$htmlTb .= "<td align=\"center\">".$item."</td>";
			$htmlTb .= "<td align=\"center\" class=\"noprint\">";
			$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divNuevoDocumento\" onclick=\"openImg(this); xajax_cargaDatosDocumentos(%s);\"><img class=\"puntero\" src=\"../img/iconos/ico_edit.png\" title=\"Editar equipo\"/></a>", /*editar equipo*/
					$contFila,
					$idDocumento = $idDocumentoVenta);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminarDocumento('%s')\" src=\"../img/iconos/ico_delete.png\" title=\"Eliminar equipo\"/></td>",
				$idDocumento = $idDocumentoVenta);
			
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listDocumentos(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listDocumentos(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listDocumentos(%s, '%s', '%s', '%s', %s)\">", 
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf .="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .="selected=\"selected\"";
									}
									$htmlTf .= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listDocumentos(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listDocumentos(%s, '%s', '%s', '%s', %s);\">%s</a>", 
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
	
	$htmlTblFin = "</table>";
	
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
	
	$objResponse->assign("tdlistDocumentos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	return $objResponse;

	}

//LISTA EL TIPO DE DOCUMENTO
function listTipoDocumento($idItemSected = " "){
	
	$objResponse = new xajaxResponse();
					
		$sqlTipoDocumento = sprintf("SELECT git.idItem AS idItem, git.item AS item
										FROM grupositems git
										LEFT JOIN grupos gps ON git.idGrupo = gps.idGrupo
										WHERE gps.grupo = 'planesDePago'
										ORDER BY item");
										
				mysql_query("SET NAMES 'utf8'");
		$queryTipoDocumento = mysql_query($sqlTipoDocumento);
		
		$selectTipoDocumento = "<select id='listTipoDocumento' name='listTipoDocumento'  onchange=''> 
									<option value=''>[ Seleccione ]</option>";
		
			while($filaTipoDocumento = mysql_fetch_array($queryTipoDocumento)){
				$idItem = $filaTipoDocumento['idItem'] ; 
				$item = $filaTipoDocumento['item'];
				
				/*si tiene un sector*/
				if($idItem == $idItemSected){
					$selected = "selected = 'selected'";
					}else {
						$selected = "";
						}
			$selectTipoDocumento.='<option value="'.$idItem.'" '.$selected.'>'.$item.'</option>';
				}
		$selectTipoDocumento.="</select>";
		
	$objResponse->assign("tdLstTipoDocumento","innerHTML",$selectTipoDocumento);
	
	return $objResponse;
}

function guardarDocumento($datosForm){
	$objResponse = new xajaxResponse();
	
		if(($datosForm['hiddIdDocumento']) > 0){ //edita
			//VALIDA SI TIENE PERMISO DE EDITAR
			if(!xvalidaAcceso($objResponse,"crm_documentos_list","editar")){
				return $objResponse;
			}
			$sqlDocumentoUpdate = sprintf("UPDATE crm_documentos_ventas SET 
												descripcion_documento = %s,
												id_tipo_documento = %s,
												id_empresa = %s,
												activo = %s
												WHERE 
												id_documento_venta = %s ", 
												valTpDato($datosForm['textDescripcionDocumento'], "text"),
												valTpDato($datosForm['listTipoDocumento'], "text"),
												valTpDato($datosForm['txtIdEmpresa'], "int"),
												valTpDato($datosForm['seltEstatu'], "int"),
												$datosForm['hiddIdDocumento']);
				mysql_query("SET NAMER 'utf8'");
					$queryDocumentoUpdate = mysql_query($sqlDocumentoUpdate);
					
						if(!$queryDocumentoUpdate) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							mysql_query("SET NAMES 'latin1'");
								$objResponse->alert("Documento Guardada con Éxito");
			
		}else{ // insert
			//VALIDA SI TIENE PERMISO DE EDITAR
			if(!xvalidaAcceso($objResponse,"crm_documentos_list","insertar")){
				return $objResponse;
				}
			$sqlDocumento = sprintf("INSERT INTO crm_documentos_ventas(descripcion_documento, id_tipo_documento, id_empresa, activo)
			VALUES(%s, %s, %s, %s)", 
			valTpDato($datosForm['textDescripcionDocumento'], "text"),
			valTpDato($datosForm['listTipoDocumento'], "text"),
			valTpDato($datosForm['txtIdEmpresa'], "int"),
			valTpDato($datosForm['seltEstatu'], "int"));
			mysql_query("SET NAMES 'utf8'");
						$queryDocumento= mysql_query($sqlDocumento);
				
			if (!$queryDocumento) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1'");
						$objResponse->alert("Documento Guardada con Éxito");
				
		}
		
		$objResponse->script("cerrarNuevoDocumento(); xajax_listDocumentos()");
		
	return $objResponse;
	}
//ELIMINAR DOCUMENTO
function eliminarDocumento($idDocumento){
	$objResponse = new xajaxResponse();
		//VALIDA SI TIENE PERMISO DE EDITAR
			
			if(!xvalidaAcceso($objResponse,"crm_documentos_list","eliminar")){
				return $objResponse;
				}
				$sqlDocumento = sprintf("DELETE FROM crm_documentos_ventas WHERE id_documento_venta = %s", 
						$idDocumento );
				mysql_query("SET NAMES 'utf8'");
				$queryDocumento = mysql_query($sqlDocumento);
				
					if(!$queryDocumento) return $objResponse->alert(mysql_error()."\n\nLine: " .__LINE__);
						mysql_query("SET NAMES 'latin1'");
							$objResponse->alert("Se elimino el documento con extio");

	$objResponse->script("xajax_listDocumentos();");
	return $objResponse;
	}
//CARGA LOS DATOS DEL FORMULARIO
function cargaDatosDocumentos($idDocumento){
	$objResponse = new xajaxResponse();
				
		$sqlCarga = sprintf("SELECT id_documento_venta, descripcion_documento, id_tipo_documento, activo, crm_documentos_ventas.id_empresa,
							  nombre_empresa FROM crm_documentos_ventas
							  LEFT JOIN pg_empresa ON pg_empresa.id_empresa = crm_documentos_ventas.id_empresa WHERE id_documento_venta = %s;",
							  $idDocumento); 
		//mysql_query("SET NAMES 'utf8'");
		$queryCarga = mysql_query($sqlCarga);
			$filaCarga = mysql_fetch_array($queryCarga);
			
				$descripcionDocumento = $filaCarga['descripcion_documento'];
				$tipoDocumento = $filaCarga['id_tipo_documento'];
				$idEmpresa = $filaCarga['id_empresa'];
				$nombreEmpresa = $filaCarga['nombre_empresa'];
				$activo = $filaCarga['activo'];
		
		$objResponse->script("abrirNuevoDocumento(true)");
		$objResponse->assign("txtIdEmpresa", "value", $idEmpresa);
		$objResponse->assign("txtNombreEmpresa", "value", $nombreEmpresa);
		$objResponse->assign("hiddIdDocumento", "value", $idDocumento);
		$objResponse->assign("textDescripcionDocumento", "value", $descripcionDocumento);
		$objResponse->assign("seltEstatu", "value", $activo);
		
		$objResponse->loadCommands(listTipoDocumento($tipoDocumento));

	return $objResponse;
	}
function buscarDocumento($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['lstEmpresa']);
	
	$objResponse->loadCommands(listDocumentos(0, "", "", $valBusq));
	
	return $objResponse;
}


//REGISTRO LAS FUNCIONES
$xajax->register(XAJAX_FUNCTION, "listadoEmpresas"); 
$xajax->register(XAJAX_FUNCTION, "buscarEmpresa");
$xajax->register(XAJAX_FUNCTION, "asignarEmpresa");
$xajax->register(XAJAX_FUNCTION, "listDocumentos"); 
$xajax->register(XAJAX_FUNCTION, "listTipoDocumento"); 
$xajax->register(XAJAX_FUNCTION, "guardarDocumento");
$xajax->register(XAJAX_FUNCTION, "eliminarDocumento");
$xajax->register(XAJAX_FUNCTION, "cargaDatosDocumentos");
$xajax->register(XAJAX_FUNCTION, "buscarDocumento");

?>