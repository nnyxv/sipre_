<?php
function buscarUsuarioPermiso($valForm,$formulario_nuevo){
	$objResponse = new xajaxResponse();
		
		$objResponse->script(sprintf("xajax_listadoTipoOrdenUsuario('0','','','%s|%s|%s')",$valForm['lstEmpresa'], $valForm['lstTipoOrden'], $valForm['nombreUsuario']));
	
	//actualizar listado de acuerdo a la empresa elegida, LISTADO
	//$objResponse->script("xajax_cargaLstTipoOrden('',".$valForm['lstEmpresa'].",'tdlstTipoOrden','lstTipoOrden')");
	//$objResponse->script("xajax_cargaLstUsuario('',".$valForm['lstEmpresa'].",'tdlstUsuario','lstUsuario')");
	
	//actualizar listado de acuerdo a la empresa elegida, NUEVO		
	//$objResponse->script("xajax_cargaLstTipoOrden('',".$formulario_nuevo['lstEmpresaNueva'].",'tdlstTipoOrdenNuevo','lstTipoOrdenNuevo'),'NO'");
	//$objResponse->script("xajax_cargaLstUsuario('',".$formulario_nuevo['lstEmpresaNueva'].",'tdlstUsuarioUsuario','lstUsuarioNuevo','NO')");
	
	return $objResponse;
}

function cargaLstTipoOrden($selId = "",$idEmpresa = "",$td="tdlstTipoOrden",$sel = "lstTipoOrden",$javascript = 'onchange="$(\'#btnBuscar\').click();"') {
	$objResponse = new xajaxResponse();
	
	if ($idEmpresa == ''){
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	}
	
	if ($idEmpresa > 0){
		$condicion = " WHERE id_empresa = '".$idEmpresa."' ";
        }else{
		$condicion = "";
        }
			
	$query = "SELECT * FROM sa_tipo_orden ".$condicion." ORDER BY  sa_tipo_orden.nombre_tipo_orden";
			
	$rs = mysql_query($query) or die(mysql_error());
	if($javascript != ""){
		$funcion_javascript = $javascript;		
	}else{
		//$funcion_javascript = "onchange=\"$('btnBuscar').click();\"";
	}
	$html = "<select id=\"".$sel."\" name=\"".$sel."\" ".$funcion_javascript." >";
		$html .= "<option value=\"\">Todos...</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['id_tipo_orden']){
			$selected = "selected='selected'";
                }
		$html .= "<option ".$selected." value=\"".$row['id_tipo_orden']."\">".utf8_encode($row['nombre_tipo_orden'])."</option>";
		
	}
	$html .= "</select>";
	$objResponse->assign($td,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstUsuario($selId = "",$idEmpresa,$td,$sel,$javascript = ""){
	$objResponse = new xajaxResponse();
	
	if ($idEmpresa == ''){
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	}
	
	if ($idEmpresa > 0)
		$condicion = " WHERE pg_usuario_empresa.id_empresa = '".$idEmpresa."' ";
	else
		$condicion = "";
	
	$query = "SELECT pg_usuario.id_usuario, nombre_usuario 
			  FROM pg_usuario 
			  LEFT JOIN pg_usuario_empresa ON pg_usuario.id_usuario = pg_usuario_empresa.id_usuario
			   ".$condicion." ORDER BY nombre_usuario";
			
	$rs = mysql_query($query) or die(mysql_error());
	
	if($javascript != ""){
		$funcion_javascript = $javascript;		
	}else{
		//$funcion_javascript = "onchange=\"$('btnBuscar').click();\"";
	}
	
	$html = "<select id=\"".$sel."\" name=\"".$sel."\" ".$funcion_javascript.">";
		$html .= "<option value=\"\">Todos...</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['id_usuario'])
			$selected = "selected='selected'";
						
		$html .= "<option ".$selected." value=\"".$row['id_usuario']."\">".utf8_encode($row['nombre_usuario'])."</option>";
		
	}
	$html .= "</select>";
	$objResponse->assign($td,"innerHTML",$html);
	
	return $objResponse;
}

function editarPermiso($idPermiso,$permiso){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,PAGE_PRIV,'editar')){		
		return $objResponse;
	}
	
	$permiso = ($permiso == 0) ? 1 : 0;
	$update = sprintf("UPDATE sa_permiso_tipo_orden_usuario SET permiso = '%s' WHERE id_permiso = '%s'",$permiso,$idPermiso);
	$rs = mysql_query($update);
	
        if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	return $objResponse;
}

function listadoTipoOrdenUsuario($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
        
	if ($valCadBusq[0] != 0 && $valCadBusq[0] != ""){
		$consulta = " WHERE sa_permiso_tipo_orden_usuario.id_empresa = '".$valCadBusq[0]."' ";
        }
	if ($valCadBusq[1] != 0 && $valCadBusq[1] != ""){
		if ($consulta == ""){
			$consulta = " WHERE vw_sa_permiso_tipo_orden_usuario.id_tipo_orden = '".$valCadBusq[1]."' ";
                }else{
			$consulta .= " AND vw_sa_permiso_tipo_orden_usuario.id_tipo_orden = '".$valCadBusq[1]."' ";
                }
        }
        
	if ($valCadBusq[2] != ""){
		if ($consulta == ""){
			$consulta = " WHERE (vw_sa_permiso_tipo_orden_usuario.nombre_usuario LIKE '%".$valCadBusq[2]."%' "
                                . " OR CONCAT_WS(' ',pg_empleado.nombre_empleado, pg_empleado.apellido) LIKE '%".$valCadBusq[2]."%' )";
                }else{
			$consulta .= " AND (vw_sa_permiso_tipo_orden_usuario.nombre_usuario LIKE '%".$valCadBusq[2]."%' "
                                . " OR CONCAT_WS(' ',pg_empleado.nombre_empleado, pg_empleado.apellido) LIKE '%".$valCadBusq[2]."%' )";                        
                }
        }
        
	$query = "SELECT sa_v_empresa_sucursal.nombre_empresa_sucursal,
                            vw_sa_permiso_tipo_orden_usuario.id_permiso,
                            vw_sa_permiso_tipo_orden_usuario.permiso,
                            vw_sa_permiso_tipo_orden_usuario.nombre_tipo_orden,
                            vw_sa_permiso_tipo_orden_usuario.nombre_usuario,
                            pg_usuario.id_empleado,
                            pg_usuario.id_usuario,
                            CONCAT_WS(' ',pg_empleado.nombre_empleado, pg_empleado.apellido) AS nombre_completo
                          FROM vw_sa_permiso_tipo_orden_usuario 
                          LEFT JOIN sa_permiso_tipo_orden_usuario ON vw_sa_permiso_tipo_orden_usuario.id_permiso = sa_permiso_tipo_orden_usuario.id_permiso
                          LEFT JOIN sa_v_empresa_sucursal ON sa_permiso_tipo_orden_usuario.id_empresa = sa_v_empresa_sucursal.id_empresa
                          LEFT JOIN pg_usuario ON pg_usuario.id_usuario = vw_sa_permiso_tipo_orden_usuario.id_usuario
                          LEFT JOIN pg_empleado ON pg_usuario.id_empleado = pg_empleado.id_empleado
                           ".$consulta;	
	
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	//$queryLimit = $query." LIMIT ".$maxRows." OFFSET ".$startRow.";";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
             
	if (!$rsLimit) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\nQuery: ".$queryLimit); }
	
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rs);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
			
	

$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
	
	$htmlTh .= ordenarCampo("xajax_listadoTipoOrdenUsuario", "", $pageNum, "nombre_empresa_sucursal", $campOrd, $tpOrd, $valBusq, $maxRows, ("Empresa/Sucursal"));
	$htmlTh .= ordenarCampo("xajax_listadoTipoOrdenUsuario", "", $pageNum, "nombre_completo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Empleado"));
	$htmlTh .= ordenarCampo("xajax_listadoTipoOrdenUsuario", "", $pageNum, "nombre_usuario", $campOrd, $tpOrd, $valBusq, $maxRows, ("Usuario"));
	$htmlTh .= ordenarCampo("xajax_listadoTipoOrdenUsuario", "", $pageNum, "nombre_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo Orden"));
	$htmlTh .= ordenarCampo("xajax_listadoTipoOrdenUsuario", "", $pageNum, "vw_sa_permiso_tipo_orden_usuario.permiso", $campOrd, $tpOrd, $valBusq, $maxRows, ("Permiso"));
	
	$htmlTh .= "</tr>";
	
        $contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
                $contFila++;
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar7" : $clase = "trResaltar4";
		if ($row['permiso']){
			$checked = "checked='checked'";
                }else{
			$checked = "";
                }
                
		$htmlTb .= "<tr class=".$clase.">";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa_sucursal'])."</td>";
			$htmlTb .= "<td idEmpleadoOculto =\"".$row['id_empleado']."\">".utf8_encode($row['nombre_completo'])."</td>";
			$htmlTb .= "<td idUsuarioOculto =\"".$row['id_usuario']."\">".utf8_encode($row['nombre_usuario'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_tipo_orden'])."</td>";
			$htmlTb .= "<td>";
                        $htmlTb .="<input class='puntero' type='checkbox' ".$checked." onClick='xajax_editarPermiso(".$row['id_permiso'].",".$row['permiso'].")'>";
                        $htmlTb .="&nbsp;&nbsp;<img class='puntero' type='checkbox' src='../img/iconos/user_suit.png' title='Editar permisos del usuario' onClick='xajax_editarPermisosUsuario(".$row['id_usuario'].");' />";
                        $htmlTb .="</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTipoOrdenUsuario(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTipoOrdenUsuario(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoTipoOrdenUsuario(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTipoOrdenUsuario(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTipoOrdenUsuario(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
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
	
	if (!($totalRows > 0)){
		$htmlTb = "<td colspan=\"10\" class=\"divError\">No se encontraron registros.</td>";
        }
	
	$objResponse->assign("tdLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

//YA NO SE USA, ANTERIOR
function nuevoPermiso($valForm){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("INSERT INTO sa_permiso_tipo_orden_usuario(id_permiso, id_tipo_orden, id_usuario, permiso, id_empresa) VALUES('', '%s', '%s', 1,'%s')",
								$valForm['lstTipoOrdenNuevo'],
								$valForm['lstUsuarioNuevo'],
								$valForm['lstEmpresaNueva']);
	$rs = mysql_query($query);
	if ($rs){
		$objResponse->alert("Permiso agregado");
		$objResponse->script("$('divFlotante').style.display = 'none'; $('btnBuscar').click();");
	}
	else
		$objResponse->alert("Conbinacion de Usuario y Tipo de Orden existente");
								
	return $objResponse;
}

function nuevo(){
		$objResponse = new xajaxResponse();
		
		if (!xvalidaAcceso($objResponse,PAGE_PRIV,'insertar')){
			return $objResponse;
		}
		else{
                    $objResponse->script("$('#divFlotante').show();
                                          centrarDiv($('#divFlotante').get(0));");
		}
		
		return $objResponse;
}


function buscarEmpleado($texto){
    $objResponse = new xajaxResponse();
    $objResponse->script("xajax_listadoEmpleados('0','nombre_completo','ASC','$texto');");
    return $objResponse;
}
        
function listadoEmpleados($pageNum = 0, $campOrd = "nombre_completo", $tpOrd = "ASC", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
        
	if ($valCadBusq[0] != ""){
			$consulta = " AND CONCAT_WS(' ',pg_empleado.nombre_empleado, pg_empleado.apellido) LIKE '%".$valCadBusq[0]."%' ";                
        }
        
	$query = "SELECT    pg_usuario.id_empleado,
                            pg_usuario.id_usuario,
                            CONCAT_WS(' ',pg_empleado.nombre_empleado, pg_empleado.apellido) AS nombre_completo
                            FROM pg_empleado                          
                            LEFT JOIN pg_usuario ON pg_usuario.id_empleado = pg_empleado.id_empleado
                            WHERE pg_usuario.id_usuario IS NOT NULL
                           ".$consulta;	
	
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	//$queryLimit = $query." LIMIT ".$maxRows." OFFSET ".$startRow.";";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
             
	if (!$rsLimit) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\nQuery: ".$queryLimit); }
	
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rs);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
			
	

$htmlTblIni = "<table border=\"0\" width=\"100%\" style=\"padding:5px;\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
	$htmlTh .= "<td width='50px;'></td>";
	$htmlTh .= ordenarCampo("xajax_listadoEmpleados", "", $pageNum, "nombre_completo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Empleado"));	
	$htmlTh .= "</tr>";
	
        $contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
                $contFila++;
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar7" : $clase = "trResaltar4";	
                
		$htmlTb .= "<tr class=".$clase.">";	
                        $htmlTb .= '<td style="text-align:center;"><button onclick="xajax_agregarUsuario('.$row["id_usuario"].');" type="button" style="cursor:pointer;"><img src="../img/iconos/minselect.png"></button></td>';
			$htmlTb .= "<td style='padding-left:15px;' idEmpleadoOculto =\"".$row['id_empleado']."\" idUsuarioOculto =\"".$row['id_usuario']."\">".utf8_encode($row['nombre_completo'])."</td>";			
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpleados(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpleados(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoEmpleados(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpleados(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpleados(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
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
	
	if (!($totalRows > 0)){
		$htmlTb = "<td colspan=\"10\" class=\"divError\">No se encontraron registros.</td>";
        }
	
	$objResponse->assign("divEmpleados","innerHTML",$htmlTblIni./*$htmlTf.*/$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
        $objResponse->script("
                            $('#divFlotante2').show();
            //document.getElementById('divFlotante2').style.display='';
                            centrarDiv($('#divFlotante2').get(0));                            
                            
                            $('#buscarNombre').focus();");
        
	return $objResponse;

        
}

function agregarUsuario($idUsuario){
    $objResponse = new xajaxResponse();
    $query = sprintf("SELECT pg_usuario.id_empleado,
                            pg_usuario.id_usuario,
                            CONCAT_WS(' ',pg_empleado.nombre_empleado, pg_empleado.apellido) AS nombre_completo
                            FROM pg_usuario                         
                            LEFT JOIN pg_empleado ON pg_usuario.id_empleado = pg_empleado.id_empleado
                            WHERE pg_usuario.id_usuario = %s",
                            valTpDato($idUsuario, "int"));
    $rs = mysql_query($query);
    if(!$rs){ return $objResponse->alert(mysql_error()."\n Linea: ".__LINE__);} 
    
    $row = mysql_fetch_assoc($rs);
    
    
    $objResponse->script("editar(); "
            . "$('#divFlotante2').hide();");        
    
    $objResponse->assign("nombreEmpleadoNuevo","value", utf8_encode($row['nombre_completo']));
    $objResponse->assign("idUsuarioNuevo","value",$row['id_usuario']);
    $objResponse->script("xajax_permisosEmpresa(".$idUsuario.")");
    
    return $objResponse;
}

function permisosEmpresa($idUsuario){
    $objResponse = new xajaxResponse();
    
    $arrayEmpresas = array();//contiene todas las empresas
    $arrayEmpresasUsuario = array();//contiene las empresas que posee el usuario
    $arrayTipoOrden = array();//contiene todos los tipos de ordenes
    $arrayPermisos = array();//contine todos los permisos de los tipos de ordenes
    
    
    //Todas las Empresas
    $queryEmpresas = "SELECT id_empresa, nombre_empresa FROM pg_empresa WHERE id_empresa != 100";
    $rsEmpresa = mysql_query($queryEmpresas);
    if(!$rsEmpresa){ return $objResponse->alert(mysql_error()."\n Linea: ".__LINE__); }    
    
    while($rowEmp = mysql_fetch_assoc($rsEmpresa)){
        $arrayEmpresas[$rowEmp['id_empresa']]['idEmpresa'] = $rowEmp['id_empresa'];
        $arrayEmpresas[$rowEmp['id_empresa']]['nombreEmpresa'] = $rowEmp['nombre_empresa'];
    }
    
    //Empresas que posee el usuario
    $queryEmpresasUsuario = "SELECT id_empresa FROM pg_usuario_empresa WHERE id_usuario = ".$_SESSION['idUsuarioSysGts'];
    $rsEmpresaUsuario = mysql_query($queryEmpresasUsuario);
    if(!$rsEmpresaUsuario){ return $objResponse->alert(mysql_error()."\n Linea: ".__LINE__); }    
    
    while($rowEmpUsu = mysql_fetch_assoc($rsEmpresaUsuario)){        
        $arrayEmpresasUsuario[$rowEmpUsu['id_empresa']] = "";
    }
            
    //Todos los tipos de ordenes
    $queryTipoOrden = "SELECT id_tipo_orden, nombre_tipo_orden, id_empresa FROM sa_tipo_orden";
    $rsTipoOrden = mysql_query($queryTipoOrden);
    if(!$rsTipoOrden){ return $objResponse->alert(mysql_error()."\n Linea: ".__LINE__); }
    
    while($rowTipo = mysql_fetch_assoc($rsTipoOrden)){
        $arrayTipoOrden[] = array("idEmpresa" => $rowTipo['id_empresa'], "idTipoOrden" => $rowTipo['id_tipo_orden'], "nombreTipoOrden" =>$rowTipo['nombre_tipo_orden']);        
    }
    
    //Todos los permisos del tipo de orden
    $query = sprintf("SELECT 
                      pg_empresa.nombre_empresa,
                      sa_tipo_orden.nombre_tipo_orden,
                      sa_permiso_tipo_orden_usuario.id_permiso, 
                      sa_permiso_tipo_orden_usuario.permiso, 
                      sa_permiso_tipo_orden_usuario.id_tipo_orden, 
                      sa_permiso_tipo_orden_usuario.id_empresa
                      FROM sa_permiso_tipo_orden_usuario  
                      LEFT JOIN sa_tipo_orden ON sa_permiso_tipo_orden_usuario.id_tipo_orden = sa_tipo_orden.id_tipo_orden
                      LEFT JOIN pg_empresa ON sa_permiso_tipo_orden_usuario.id_empresa = pg_empresa.id_empresa
                      WHERE sa_permiso_tipo_orden_usuario.id_usuario = %s",
                        $idUsuario);
    
    $rs = mysql_query($query);
    if(!$rs){ return $objResponse->alert(mysql_error()."\n Linea: ".__LINE__); }
    
    while($row = mysql_fetch_assoc($rs)){
        $arrayPermisos[] = array("idEmpresa" => $row['id_empresa'], "idPermiso" => $row['id_permiso'], "permiso" => $row['permiso'], "idTipoOrden" => $row['id_tipo_orden']);
    }
    
    
    
    $html = "";
    foreach($arrayEmpresas as $datosEmpresas){//recorro todas las empresas
       
        $html .= "<fieldset><legend>".$datosEmpresas['nombreEmpresa']."</legend>";
        
            //sino tiene el usuario permiso para ver la empresa
            if(!array_key_exists($datosEmpresas["idEmpresa"], $arrayEmpresasUsuario)){//si no tiene
                $html .= "Usted no posee permisos sobre esta empresa.<br>";
                $deshabilitado = "disabled = 'disabled' ";
            }else{//si tiene
                $deshabilitado = "";
            }
        
            $tiene = 0;//si la empresa no tiene tipo de orden
            foreach($arrayTipoOrden as $datosTipoOrden){//recorro todos los tipos de orden
                if($datosEmpresas["idEmpresa"] == $datosTipoOrden["idEmpresa"]){//solo mostrar a los que le pertenezca a la empresa

                    $checked = "";
                    $permiso = "NOTIENE";//no tiene(nunca se agrego, SI (si tiene activo), NO (lo tiene pero no activo)
                    $idPermiso = 0;//si tiene permiso buscar por id para hacer update
                    foreach($arrayPermisos as $datosPermisos){//si existe el permiso
                        if($datosPermisos["idEmpresa"] == $datosEmpresas["idEmpresa"] && $datosTipoOrden["idTipoOrden"] == $datosPermisos["idTipoOrden"]){
                            
                            $idPermiso = $datosPermisos["idPermiso"];
                            if($datosPermisos["permiso"] == "1"){ 
                                $permiso = "SI"; 
                                $checked = "checked = 'checked'";
                            }else{
                                $permiso = "NO";
                            }
                        }
                    }

                    $html .= "<input onClick='cambioCheckbox(this)' name='permisos[]' type='checkbox' ".$deshabilitado." ".$checked." value='".$idUsuario."|".$idPermiso."|".$permiso."|".$datosTipoOrden["idTipoOrden"]."|".$datosEmpresas["idEmpresa"]."' />".$datosTipoOrden['nombreTipoOrden']."<br>";
                    $tiene++;
                }
            }

            //sino tiene tipos de orden la empresa
            if(!$tiene){
                $html .= "Esta empresa no posee tipos de ordenes.";
            }

            
        
        $html .= "</fieldset>";
        
    }    
    
    
// var_dump($arrayEmpresas);    
    $objResponse->assign("permisosEmpresa","innerHTML",$html);
    $objResponse->script("centrarDiv($('#divFlotante').get(0));");//otra vez porque se redimensiona con el listado
    return $objResponse; 
}


function editarPermisosUsuario($idUsuario){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,PAGE_PRIV,'editar')){		
		return $objResponse;
	}
	
        //$objResponse->script("editar();");//movido a agregar usuario
        $objResponse->script("xajax_agregarUsuario(".$idUsuario.");");
        $objResponse->script("$('#divFlotante').show();
                              centrarDiv($('#divFlotante').get(0));");
        	
	return $objResponse;
}

function agregarPermisosNuevo($frmNuevoPermiso){
    $objResponse = new xajaxResponse();    
    if($frmNuevoPermiso['idUsuarioNuevo'] == ""){
        return $objResponse->alert("Debe seleccionar un empleado");
    }
    
    mysql_query("START TRANSACTION");
    
    if($frmNuevoPermiso['permisos'] != "" && !empty($frmNuevoPermiso['permisos'])){//si existe y si no esta vacio

        foreach ($frmNuevoPermiso['permisos'] as $arrayPermisos){
            $valor = explode("|",$arrayPermisos);

            $idUsuario = $valor[0];
            $idPermiso = $valor[1];
            $permiso = $valor[2];
            $idTipoOrden = $valor[3];
            $idEmpresa = $valor[4];

            if($idPermiso){//si tiene permiso actualizo, cero para nuevo
                
                $queryActualizar = "UPDATE sa_permiso_tipo_orden_usuario SET permiso = 1 WHERE id_permiso = ".$idPermiso;
                $rsActualizar = mysql_query($queryActualizar);

                if(!$rsActualizar){
                    return $objResponse->alert(mysql_error()."\nLinea:".__LINE__."\nQuery:".$queryActualizar);
                }
                
            }else{//es 0 hago insert
                $queryAgregar = sprintf("INSERT INTO sa_permiso_tipo_orden_usuario (id_tipo_orden, id_usuario, permiso, id_empresa ) "
                        . "VALUES  (%s, %s, 1, %s)",
                        $idTipoOrden, $idUsuario, $idEmpresa);
                $rsAgregar = mysql_query($queryAgregar);

                if(!$rsAgregar){
                    return $objResponse->alert(mysql_error()."\nLinea:".__LINE__."\nQuery:".$queryAgregar);
                }
            }   
        }            
    }
    
    //Quitamos los permisos que se seleccionaron como no
    if($frmNuevoPermiso['idPermisosEliminar'] != ""){
        $idPermisosQuitar = str_replace("|", ",", $frmNuevoPermiso['idPermisosEliminar']);
        
        $queryQuitar = "UPDATE sa_permiso_tipo_orden_usuario SET permiso = 0 WHERE id_permiso IN (".$idPermisosQuitar.")";
        $rsQuitar = mysql_query($queryQuitar);
        
        if(!$rsQuitar){
            return $objResponse->alert(mysql_error()."\nLinea:".__LINE__."\nQuery:".$queryQuitar);
        }
        
    }
    
    mysql_query("COMMIT");
            
    $objResponse->alert("Permisos actualizados");
    $objResponse->script("xajax_permisosEmpresa(".$frmNuevoPermiso['idUsuarioNuevo'].");");
    return $objResponse;
}


$xajax->register(XAJAX_FUNCTION,"buscarUsuarioPermiso");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresas");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoOrden");
$xajax->register(XAJAX_FUNCTION,"cargaLstUsuario");
$xajax->register(XAJAX_FUNCTION,"editarPermiso");
$xajax->register(XAJAX_FUNCTION,"listadoTipoOrdenUsuario");
$xajax->register(XAJAX_FUNCTION,"nuevoPermiso");
$xajax->register(XAJAX_FUNCTION,"nuevo");

$xajax->register(XAJAX_FUNCTION,"listadoEmpleados");
$xajax->register(XAJAX_FUNCTION,"buscarEmpleado");
$xajax->register(XAJAX_FUNCTION,"agregarUsuario");
$xajax->register(XAJAX_FUNCTION,"permisosEmpresa");
$xajax->register(XAJAX_FUNCTION,"agregarPermisosNuevo");
$xajax->register(XAJAX_FUNCTION,"editarPermisosUsuario");



?>