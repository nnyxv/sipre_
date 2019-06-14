<?php 

/**
 * Este es el Controlador para las paginas "sa_orden_tot_list.php" y "sa_historico_tot.php"
 */

//PAGINA es una constante definida en cada archivo del tot:
//sa_orden_tot_list.php = 0 y antes era sa_orden_tot_list.php?acc=0
//sa_historico_tot.php = 1 y antes era el mismo, solo que no existia el archivo

function cargaLstEmpresas($selId = ""){
	$objResponse = new xajaxResponse();
	
	if ($selId == 0){
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	}
	else{
		$idEmpresa = $selId;
	}
	
	$query = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY nombre_empresa",
			valTpDato($_SESSION['idUsuarioSysGts'],"int"));
		$rs = mysql_query($query);
		if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$html = "<select id=\"lstEmpresa\" name=\"lstEmpresa\">";
		$html .="<option value=\"0\">Todas</option>";
		while ($row = mysql_fetch_assoc($rs)) {
			$nombreSucursal = "";
			if ($row['id_empresa_padre_suc'] > 0)
				$nombreSucursal = " - ".$row['nombre_empresa_suc']." (".$row['sucursal'].")";
			
			$selected = "";
			if ($idEmpresa == $row['id_empresa_reg'])
				$selected = "selected='selected'";
		
			$html .= "<option ".$selected." value=\"".$row['id_empresa_reg']."\">".utf8_encode($row['nombre_empresa'].$nombreSucursal)."</option>";
		}
		$html .= "</select>";
		
		$objResponse->assign("tdlstEmpresa","innerHTML",$html);
	
	return $objResponse;
}

function listadoTOT($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	if (PAGINA == 0){
		if (!xvalidaAcceso($objResponse,"sa_orden_tot_list")){
		$objResponse->assign("tdListaTOT","innerHTML","Acceso Denegado");
		return $objResponse;
		}
	}
	else{
		if (!xvalidaAcceso($objResponse,"sa_historico_tot")){
		$objResponse->assign("tdListaTOT","innerHTML","Acceso Denegado");
		return $objResponse;
		}
	}
	
	if (PAGINA == 0){
		$filtro = "=";
		$objResponse->assign("tdReferenciaPagina","innerHTML","Ordenes de TOT");
	}else{
		$filtro = ">=";
		$objResponse->assign("tdReferenciaPagina","innerHTML","Historico de Ordenes TOT");
	}
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
/*	if ($valCadBusq[0] > 0){
		$where = "(AND id_empresa = '".$valCadBusq[0]."')";
	}*/
	
	if ($valCadBusq[1] != ""){
		$where = " AND (sa_orden.numero_orden LIKE '%".$valCadBusq[1]."%' OR numero_tot LIKE '%".$valCadBusq[1]."%' )";
	}
	
	$queryTOT = sprintf("SELECT sa_orden.numero_orden, vw_sa_orden_tot.* FROM vw_sa_orden_tot 
						LEFT JOIN sa_orden ON vw_sa_orden_tot.id_orden_servicio = sa_orden.id_orden
						WHERE (estatus %s %s AND vw_sa_orden_tot.id_empresa = '".$valCadBusq[0]."' )",$filtro, PAGINA).$where;
  	
	$rsTOT = mysql_query($queryTOT);
	if(!$rsTOT) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimitTOT = $queryTOT.$sqlOrd." LIMIT ".$maxRows." OFFSET ".$startRow.";";
	$rsLimitTOT = mysql_query($queryLimitTOT);
	if(!$rsLimitTOT) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	//$objResponse->alert($queryLimitTOT);
	
	if ($totalRows == NULL) {
		$rsTOT = mysql_query($queryTOT);
		if(!$rsTOT) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rsTOT);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
	
	if(PAGINA==1){
		$display1 = "style='display:none'";
		$display2 = "";	
		$display3 = "style='display:none'";
	}else{
		$display1 = "";	
		$display2 = "style='display:none'";
		$display3 = "";
	}
			
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";//
	
	$htmlTh .= ordenarCampo("xajax_listadoTOT", "", $pageNum, "sa_orden.id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, ("Empresa/Sucursal"));
		$htmlTh .= ordenarCampo("xajax_listadoTOT", "", $pageNum, "sa_orden.numero_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("Orden Servicio"));
		$htmlTh .= ordenarCampo("xajax_listadoTOT", "", $pageNum, "numero_tot", $campOrd, $tpOrd, $valBusq, $maxRows, ("Orden TOT"));
		$htmlTh .= ordenarCampo("xajax_listadoTOT", "", $pageNum, "estatus", $campOrd, $tpOrd, $valBusq, $maxRows, ("Estatus"));
		$htmlTh .= ordenarCampo("xajax_listadoTOT", "", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, ("Proveedor"));
		$htmlTh .= ordenarCampo("xajax_listadoTOT", "", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, ("Placa"));
		$htmlTh .= ordenarCampo("xajax_listadoTOT", "", $pageNum, "chasis", $campOrd, $tpOrd, $valBusq, $maxRows, ("Chasis"));
		$htmlTh .= ordenarCampo("xajax_listadoTOT", "", $pageNum, "nom_marca", $campOrd, $tpOrd, $valBusq, $maxRows, ("Marca"));
		$htmlTh .= ordenarCampo("xajax_listadoTOT", "", $pageNum, "des_modelo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Modelo"));
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td ".$display2."></td>";
		$htmlTh .= "<td ".$display1."></td>";
		$htmlTh .= "<td ".$display3."></td>";
		$htmlTh .= "<td ".$display2."></td>";
		$htmlTh .= "<td ".$display2."></td>";//retencion iva
		$htmlTh .= "<td ".$display2."></td>";//islr
		$htmlTh .= "<td></td>";//eliminacion tot
	$htmlTh .= "</tr>";
	
	while ($rowTOT = mysql_fetch_assoc($rsLimitTOT)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar7" : $clase = "trResaltar4"; 
		
		$nombreEmpresa = utf8_encode($rowTOT['nombre_empresa']);
		if ($rowTOT['id_empresa_padre_suc'] != NULL)
			$nombreEmpresa .= " / " . $rowTOT['sucursal'];
		
		if ($rowTOT['estatus'] == 0){
			$estatus = 'Creado';
		}else if ($rowTOT['estatus'] == 1){
			$estatus = 'Actualizado';
		}else if ($rowTOT['estatus'] == 2){
			$estatus = 'Asignado';
		}else{
			$estatus = 'Facturado';
		}
			
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>".$nombreEmpresa."</td>";
			$htmlTb .= "<td idordenoculta=\"".$rowTOT['id_orden_servicio']."\">".$rowTOT['numero_orden']."</td>";
			$htmlTb .= "<td idtotoculta=\"".$rowTOT['id_orden_tot']."\">".$rowTOT['numero_tot']."</td>";
			$htmlTb .= "<td>".$estatus."</td>";
			$htmlTb .= "<td>".utf8_encode($rowTOT['nombre'])."</td>";
			$htmlTb .= "<td>".$rowTOT['placa']."</td>";
			$htmlTb .= "<td>".$rowTOT['chasis']."</td>";
			$htmlTb .= "<td>".$rowTOT['nom_marca']."</td>";
			$htmlTb .= "<td>".$rowTOT['des_modelo']."</td>";
			//ver tot
			$htmlTb .= "<td class=\"noprint puntero\"><img src='../img/iconos/ico_view.png' onclick=\"window.open('sa_orden_compra_tot.php?id=".$rowTOT['id_orden_tot']."&accion=0&acc=".PAGINA."','_self');\" title=\"Ver T.O.T\"/></td>";
			//editar tot
			$htmlTb .= sprintf("<td %s class=\"noprint puntero\"><img src='../img/iconos/ico_edit.png' onclick=\"window.open('sa_orden_compra_tot.php?id=".$rowTOT['id_orden_tot']."&accion=1&acc=".PAGINA."','_self');\" title=\"Cargar T.O.T\"/></td>", $display1);
			//ver solicitud pdf
                        $htmlTb .= sprintf("<td %s class=\"noprint puntero\"><img src='../img/iconos/page_white_acrobat.png' onclick=\"verVentana('reportes/sa_solicitud_compra_tot_pdf.php?id=".$rowTOT['id_orden_tot']."',1000,500);\" title=\"Ver Solicitud\"/></td>", $display3);
			//ver pdf
			$htmlTb .= sprintf("<td %s class=\"noprint puntero\" ><img src='../img/iconos/page_white_acrobat.png' onclick=\"verVentana('sa_imprimir_registro_compra_pdf.php?valBusq=".$rowTOT['id_orden_tot']."',900,900);\" title='Ver PDF Registro de Compra' /></td>", $display2);
			
			$botonIslr = "";
			$botonRetencion = "";
			if($rowTOT['id_factura']){
				//RETENCION IVA
				$query = sprintf("SELECT idRetencionCabezera 
									FROM cp_retenciondetalle
									WHERE idFactura = %s 
									AND tipoDeTransaccion = 1",
								valTpDato($rowTOT['id_factura'],"int"));
				$rs = mysql_query($query);
				
				if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				$row = mysql_fetch_assoc($rs);
				
				if(mysql_num_rows($rs)){
					$botonRetencion = "<img src='../img/iconos/ico_print.png' class=\"noprint puntero\" onclick=\"verVentana('../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq=".$row["idRetencionCabezera"]."',900,900);\" title='Comprobante de Retencion' />";
				}
				
				//RETENCION ISLR
				$query = sprintf("SELECT id_retencion_cheque 
									FROM te_retencion_cheque 
									WHERE id_factura = %s 
									AND tipo = 0 
									AND anulado IS NULL",
								valTpDato($rowTOT['id_factura'],"int"));
				$rs = mysql_query($query);
				
				if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				$row = mysql_fetch_assoc($rs);
				
				if(mysql_num_rows($rs)){
					$botonIslr = "<img src='../img/iconos/page_red.png' class=\"noprint puntero\" onclick=\"verVentana('../tesoreria/reportes/te_imprimir_constancia_retencion_pdf.php?id=".$row["id_retencion_cheque"]."&documento=3',900,900);\" title='Comprobante de Retencion ISLR' />";
				}
			}		
			$htmlTb .= "<td ".$display2.">".$botonRetencion."</td>";			
			$htmlTb .= "<td ".$display2.">".$botonIslr."</td>";
			
			if($rowTOT['id_factura'] == NULL){//sino tiene factura eliminar
				$htmlTb .= "<td class=\"noprint puntero\" ><img src='../img/iconos/delete.png' onclick=\" var confirmar = confirm('¿Seguro deseas eliminar el TOT?'); if (confirmar === true){ xajax_eliminarTot(".$rowTOT['id_orden_tot']."); } \" title='Eliminar TOT' /></td>";
			}elseif($rowTOT['estatus'] != 3){//que no este facturado, poner en cero
				if($rowTOT['monto_total'] == "0.00"){
					$htmlTb .= "<td>0</td>";
				}else{
					$htmlTb .= "<td class=\"noprint puntero\" ><img src='../img/iconos/delete.png' onclick=\" var confirmar = confirm('El tot ya tiene una factura y no se eliminará, ¿Seguro deseas colocar en cero el TOT?'); if (confirmar === true){ xajax_claveTotEnCero(".$rowTOT['id_orden_tot'].",".$rowTOT['numero_tot']."); } \" title='Colocar en cero el TOT' /></td>";
				}
			}else{
				$htmlTb .= "<td></td>";//relleno si ninguno
			}

			//ver movimiento contable
			// MODIFICADO ERNESTO
			$sPar = "idobject=".$rowTOT['id_factura'];
			$sPar .= "&ct=01";
			$sPar .= "&dt=01";
			$sPar .= "&cc=03";
			$htmlTb .= "<td ".$display2.">";
				$htmlTb .= "<img class=\"noprint puntero\" onclick=\"verVentana('../contabilidad/RepComprobantesDiariosDirecto.php?".$sPar."', 1000, 500);\" src=\"../img/iconos/new_window.png\" title=\"Ver Movimiento Contable\"/>";
			$htmlTb .= "</td>";
			// MODIFICADO ERNESTO
			
		$htmlTb .= "</tr>";		
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"40\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTOT(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTOT(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoTOT(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTOT(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTOT(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	if (!($totalRows > 0))
		$htmlTb = "<td colspan=\"40\" class=\"divError\">No se encontraron registros.</td>";

		$objResponse->assign("tdListaTOT","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	return $objResponse;
}

function eliminarTot($idTot){
    $objResponse = new xajaxResponse();
    
    if (!xvalidaAcceso($objResponse,'sa_orden_tot_list',eliminar)){
        return $objResponse;
    }
    
    mysql_query("START TRANSACTION");
    $sqlEliminarTot = sprintf("DELETE FROM sa_orden_tot WHERE id_orden_tot = %s",
                                valTpDato($idTot, "int"));
    $rsEliminarTot = mysql_query($sqlEliminarTot);
    if(!$rsEliminarTot) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
            
    $sqlEliminarTotOrden = sprintf("DELETE FROM sa_det_orden_tot WHERE id_orden_tot = %s",
                                valTpDato($idTot, "int"));
    $rsEliminarTotOrden = mysql_query($sqlEliminarTotOrden);
    if(!$rsEliminarTotOrden) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
    
    mysql_query("COMMIT");
    
    $objResponse->alert("TOT eliminado correctamente, debe actualizar la orden si estaba asignado");
    $objResponse->script("$('btnBuscar').click();");
    
    return $objResponse;
    
}

function claveTotEnCero($idTot, $numeroTot){
    
    $objResponse = new xajaxResponse();

$pantallaForm = '<div style="cursor: auto; left: 564px; position: absolute; top: 191px; z-index: 0;" class="root" id="divFlotante">
                    <div class="handle" id="divFlotanteTitulo"><table><tbody><tr><td width="100%" id="tdFlotanteTitulo">Cambiar TOT a Cero</td></tr></tbody></table></div>
                    <form style="margin:0" name="frmClaveTotCero" id="frmClaveTotCero">
                        <table width="300" border="0" id="tblClaveTotCero">
                        <tbody><tr>
                            <td id="tdTituloListado" colspan="2">&nbsp;</td>
                        </tr>
                        <tr>
                            <td width="33%" align="right" class="tituloCampo">N° TOT:</td>
                            <td width="67%">
                                <input type="text" readonly="readonly" name="numeroTotMostrar" id="numeroTotMostrar">
                            </td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo">Clave:</td>
                            <td><input type="password" class="inputInicial" id="txtClaveAprobacion" name="txtClaveAprobacion"></td>
                        </tr>
                        <tr>
                            <td align="right" colspan="2">
                                    <hr>
                                <input type="button" value="Guardar" onclick="xajax_cambiarTotCero('.$idTot.',$(\'txtClaveAprobacion\').value);" name="btnGuardar" id="btnGuardar">
                                <input type="button" value="Cancelar" onclick="$(\'divFlotante\').style.display=\'none\';">
                                    </td>
                        </tr>
                        </tbody></table>
                    </form>
                    </div>';
                    
    $objResponse->assign('divFormulario', 'innerHTML', $pantallaForm);//div formulario esta creado en los 2 listados que usan esta mismo controlador
     $objResponse->assign('numeroTotMostrar', 'value', $numeroTot);
    $objResponse->script("activarMovimiento();");//en los 2 listado existe, es para activar el dom drag
    $objResponse->script("$('divFormulario').style.display = '';");
    
    return $objResponse;
}

function cambiarTotCero($idTot,$clave){
    $objResponse = new xajaxResponse();    
    
    if($clave == "NULL" || $clave == "" || $clave == NULL){//valtpdato text convierte vacio en string "NULL"
         return $objResponse->alert("Debe introducir la clave");
    }
    
        $sql1= "SELECT id_empleado FROM pg_usuario WHERE id_usuario= ".$_SESSION['idUsuarioSysGts'];
        $rsUsuario = mysql_query($sql1);
        if(!$rsUsuario) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
        $rowUsuario = mysql_fetch_assoc($rsUsuario);

        $sql2= "SELECT * FROM sa_claves WHERE modulo= 'sa_tot_cero' ";
        $sql2.= "AND id_empleado= ".$rowUsuario['id_empleado']." AND id_empresa= ".$_SESSION['idEmpresaUsuarioSysGts'];
        $rsClaves = mysql_query($sql2);
        if(!$rsClaves) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
        $rowClaves = mysql_fetch_assoc($rsClaves);
        
        if($rowClaves){
            $clave = md5($clave);
            if($clave == $rowClaves['clave']){
                    mysql_query("START TRANSACTION");
                    $sqlActualizarTot = sprintf("UPDATE sa_orden_tot SET 
                                                monto_exento = 0,
                                                monto_subtotal = 0,
                                                monto_total = 0
                                                WHERE id_orden_tot = %s",
                                                valTpDato($idTot, "int"));
                    $rsActualizarTot = mysql_query($sqlActualizarTot);
                    if(!$rsActualizarTot) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

                    $sqlActualizarTotOrden = sprintf("DELETE FROM sa_det_orden_tot WHERE id_orden_tot = %s",
                                                valTpDato($idTot, "int"));
                    $rsActualizarTotOrden = mysql_query($sqlActualizarTotOrden);
                    if(!$rsActualizarTotOrden) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

                    mysql_query("COMMIT");

                    $objResponse->alert("TOT cambiado correctamente, debe actualizar la orden");
                    $objResponse->script("$('divFlotante').style.display='none';");
                    $objResponse->script("$('txtClaveAprobacion').value= '';");
                    $objResponse->script("$('btnBuscar').click();");

            }else{
                $objResponse->script("$('txtClaveAprobacion').value= '';");
                
                $objResponse->script("alert('Clave incorrecta, verifique y vuelva a intentar');");
            }
        }else{
            $objResponse->script("$('txtClaveAprobacion').value= '';");
            $objResponse->script("$('divFlotante').style.display='none';");
            $objResponse->script("alert('Usted no posee privilegios para cambiar TOT');");
        }              
        
    return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresas");
$xajax->register(XAJAX_FUNCTION,"listadoTOT");
$xajax->register(XAJAX_FUNCTION,"eliminarTot");
$xajax->register(XAJAX_FUNCTION,"claveTotEnCero");
$xajax->register(XAJAX_FUNCTION,"cambiarTotCero");

?>