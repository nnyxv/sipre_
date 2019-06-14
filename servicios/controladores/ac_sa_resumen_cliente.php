<?php
set_time_limit(0);
//ini_set("display_errors", 1);

function buscar($valForm) {
	$objResponse = new xajaxResponse();

	$objResponse->loadCommands(resumenLlamadas($valForm));

	return $objResponse;
}


//este es el final anterior antes que yo lo cambiara
/*
function cargaLstEmpresaFinal($selId = "", $accion = "onchange=\"xajax_objetoCodigoDinamico('tdCodigoArt',this.value); byId('btnBuscar').click();\"") {
	$objResponse = new xajaxResponse();
	
	// SELECCIONADA
	$queryEmpresaSuc = sprintf("SELECT * FROM vw_iv_empresas_sucursales
	WHERE id_empresa_reg = %s
		AND id_empresa_suc > 0;",
		valTpDato($selId, "int"));
	$rsEmpresaSuc = mysql_query($queryEmpresaSuc);
	if (!$rsEmpresaSuc) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$totalRowsEmpresaSuc = mysql_num_rows($rsEmpresaSuc);
	$rowEmpresaSuc = mysql_fetch_assoc($rsEmpresaSuc);
	
	// SI NO TIENE SUCURSAL
	if ($totalRowsEmpresaSuc == 0) {
		$query = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY nombre_empresa",
			valTpDato($_SESSION['idUsuarioSysGts'], "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$selected = ($selId == $row['id_empresa_reg']) ? "selected='selected'" : "";
		
			$htmlOption .= "<option ".$selected." value=\"".$row['id_empresa_reg']."\">".htmlentities($row['nombre_empresa'])."</option>";
		}
	} else {
		$query = sprintf("SELECT DISTINCT
			id_empresa,
			nombre_empresa
		FROM vw_iv_usuario_empresa
		WHERE id_usuario = %s
		ORDER BY nombre_empresa",
			valTpDato($_SESSION['idUsuarioSysGts'], "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$htmlOption .= "<optgroup label=\"".$row['nombre_empresa']."\">";
			
			$queryUsuarioSuc = sprintf("SELECT DISTINCT
				id_empresa_reg,
				nombre_empresa_suc,
				sucursal
			FROM vw_iv_usuario_empresa
			WHERE id_usuario = %s
			ORDER BY nombre_empresa_suc ASC",
				valTpDato($_SESSION['idUsuarioSysGts'], "int"));
			$rsUsuarioSuc = mysql_query($queryUsuarioSuc);
			if (!$rsUsuarioSuc) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			while ($rowUsuarioSuc = mysql_fetch_assoc($rsUsuarioSuc)) {
				$selected = ($selId == $rowUsuarioSuc['id_empresa_reg']) ? "selected='selected'" : "";
			
				$htmlOption .= "<option ".$selected." value=\"".$rowUsuarioSuc['id_empresa_reg']."\">".htmlentities($rowUsuarioSuc['nombre_empresa_suc'])."</option>";	
			}
		
			$htmlOption .= "</optgroup>";
		}
	}
	
	$html = "<select id=\"lstEmpresa\" name=\"lstEmpresa\" class=\"inputHabilitado\" ".$accion." style=\"width:200px\">";
		$html .= "<option value=\"\">[ Todos ]</option>";
		$html .= $htmlOption;
	$html .= "</select>";
	
	$objResponse->assign("tdlstEmpresa","innerHTML",$html);
	
	return $objResponse;
}*/



function resumenLLamadas($valForm) {
	$objResponse = new xajaxResponse();
	
	if($valForm["lstEmpresa"] != ""){
		$idEmpresa = $valForm["lstEmpresa"];
	}else{
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];	
	}
	
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// RESUMEN LLAMADAS
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$array = NULL;
	$arrayMov = NULL;
	$strXMLCuerpo = "";
	

	$htmlTblIni .= "<table border=\"1\" class=\"tabla texto_9px\" cellpadding=\"3\" width=\"100%\">";

	

	// NO RESPONDIO = 0
	$sql0 = "SELECT COUNT(id_cita) as Total
	FROM sa_cita
	WHERE id_empresa = ".$idEmpresa." AND DATE_FORMAT(fecha_llamada_fin,'%d-%m-%Y') BETWEEN '".date("d-m-Y",strtotime($valForm['txtFechaDesde']))."' AND '".date("d-m-Y",strtotime($valForm['txtFechaHasta']))."' AND n_respuesta = 0";
	$rs0 = mysql_query($sql0);
	if (!$rs0) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row0 = mysql_fetch_array($rs0);
	
	// EXCELENTE = 1
	$sql1 = "SELECT COUNT(id_cita) as Total
	FROM sa_cita
	WHERE id_empresa = ".$idEmpresa." AND DATE_FORMAT(fecha_llamada_fin,'%d-%m-%Y') BETWEEN '".date("d-m-Y",strtotime($valForm['txtFechaDesde']))."' AND '".date("d-m-Y",strtotime($valForm['txtFechaHasta']))."' AND n_respuesta = 1";
	$rs1 = mysql_query($sql1);
	if (!$rs1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row1 = mysql_fetch_array($rs1);
	
	// BUENO = 2
	$sql2 = "SELECT COUNT(id_cita) as Total
	FROM sa_cita
	WHERE id_empresa = ".$idEmpresa." AND DATE_FORMAT(fecha_llamada_fin,'%d-%m-%Y') BETWEEN '".date("d-m-Y",strtotime($valForm['txtFechaDesde']))."' AND '".date("d-m-Y",strtotime($valForm['txtFechaHasta']))."' AND n_respuesta = 2";
	$rs2 = mysql_query($sql2);
	if (!$rs2) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row2 = mysql_fetch_array($rs2);
	
	// REGULAR = 3
	$sql3 = "SELECT COUNT(id_cita) as Total
	FROM sa_cita
	WHERE id_empresa = ".$idEmpresa." AND DATE_FORMAT(fecha_llamada_fin,'%d-%m-%Y') BETWEEN '".date("d-m-Y",strtotime($valForm['txtFechaDesde']))."' AND '".date("d-m-Y",strtotime($valForm['txtFechaHasta']))."' AND n_respuesta = 3";
	$rs3 = mysql_query($sql3);
	if (!$rs3) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row3 = mysql_fetch_array($rs3);
	
	//MALO = 4
	$sql4 = "SELECT COUNT(id_cita) as Total
	FROM sa_cita
	WHERE id_empresa = ".$idEmpresa." AND DATE_FORMAT(fecha_llamada_fin,'%d-%m-%Y') BETWEEN '".date("d-m-Y",strtotime($valForm['txtFechaDesde']))."' AND '".date("d-m-Y",strtotime($valForm['txtFechaHasta']))."' AND n_respuesta = 4";
	$rs4 = mysql_query($sql4);
	if (!$rs4) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row4 = mysql_fetch_array($rs4);
	
	//MUY MALO = 5
	$sql5 = "SELECT COUNT(id_cita) as Total
	FROM sa_cita
	WHERE id_empresa = ".$idEmpresa." AND DATE_FORMAT(fecha_llamada_fin,'%d-%m-%Y') BETWEEN '".date("d-m-Y",strtotime($valForm['txtFechaDesde']))."' AND '".date("d-m-Y",strtotime($valForm['txtFechaHasta']))."' AND n_respuesta = 5";
	$rs5 = mysql_query($sql5);
	if (!$rs5) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row5 = mysql_fetch_array($rs5);
	
	
	$sqlTotal = "SELECT COUNT(id_cita) as Total
	FROM sa_cita
	WHERE id_empresa = ".$idEmpresa." AND DATE_FORMAT(fecha_llamada_fin,'%d-%m-%Y') BETWEEN '".date("d-m-Y",strtotime($valForm['txtFechaDesde']))."' AND '".date("d-m-Y",strtotime($valForm['txtFechaHasta']))."'";
	$rsTotal = mysql_query($sqlTotal);
	if (!$rsTotal) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowTotal = mysql_fetch_array($rsTotal);

		
	// NO RESPONDIO
        if($rowTotal['Total'] == 0){
            $NoRespondio = 0;
        }else{
            $NoRespondio = ($row0['Total'] / $rowTotal['Total']) * 100;
        }
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
		$htmlTb .= "<td align=\"left\">"."No Respondio:"."</td>";
		$htmlTb .= "<td align=\"rigth\">".$row0['Total']."</td>";
		$htmlTb .= "<td>".number_format($NoRespondio,2,".",",")."%</td>";
		
	$htmlTb .= "</tr>";
	
	// EXCELENTE
        if($rowTotal['Total'] == 0){
            $Excelente = 0;
        }else{
            $Excelente = ($row1['Total'] / $rowTotal['Total']) * 100;
        }
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
		$htmlTb .= "<td align=\"left\">"."Excelente:"."</td>";
		$htmlTb .= "<td align=\"rigth\">".$row1['Total']."</td>";
		$htmlTb .= "<td>".number_format($Excelente,2,".",",")."%</td>";
		
	$htmlTb .= "</tr>";
	
	// BUENO
        if($rowTotal['Total'] == 0){
            $Bueno = 0;
        }else{
            $Bueno = ($row2['Total'] / $rowTotal['Total']) * 100;
        }
        $clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
		$htmlTb .= "<td align=\"left\">"."Bueno:"."</td>";
		$htmlTb .= "<td align=\"rigth\">".$row2['Total']."</td>";
		$htmlTb .= "<td>".number_format($Bueno,2,".",",")."%</td>";
		
	$htmlTb .= "</tr>";

	// REGULAR
        if($rowTotal['Total'] == 0){
            $Regular = 0;
        }else{
            $Regular = ($row3['Total'] / $rowTotal['Total']) * 100;
        }
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
		$htmlTb .= "<td align=\"left\">"."Regular:"."</td>";
		$htmlTb .= "<td align=\"rigth\">".$row3['Total']."</td>";
		$htmlTb .= "<td>".number_format($Regular,2,".",",")."%</td>";
		
	$htmlTb .= "</tr>";
		
		
	// MALO
        if($rowTotal['Total'] == 0){
            $Malo = 0;
        }else{
            $Malo = ($row4['Total'] / $rowTotal['Total']) * 100;
        }
        $clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
		$htmlTb .= "<td align=\"left\">"."Malo:"."</td>";
		$htmlTb .= "<td align=\"rigth\">".$row4['Total']."</td>";
		$htmlTb .= "<td>".number_format($Malo,2,".",",")."%</td>";
		
	$htmlTb .= "</tr>";
	
	
	// MUY MALO
        if($rowTotal['Total'] == 0){
            $Muymalo = 0;
        }else{
            $Muymalo = ($row5['Total'] / $rowTotal['Total']) * 100;
        }
        $clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
		$htmlTb .= "<td align=\"left\">"."Muy Malo:"."</td>";
		$htmlTb .= "<td align=\"rigth\">".$row5['Total']."</td>";
		$htmlTb .= "<td>".number_format($Muymalo,2,".",",")."%</td>";
		
	$htmlTb .= "</tr>";
	
	$htmlTb .= "<tr align=\"right\" class=\"tituloColumna\" height=\"22\">";
	$htmlTb .= "<td colspan=\"3\">Total Clientes:                         ".$rowTotal['Total']."</td>";
	$htmlTb .= "</tr>";

	$strXMLCuerpo .= "<set name='NO RESPONDIO' value='".$NoRespondio."' color='".$bgColor."'/>";
	$strXMLCuerpo .= "<set name='EXCELENTE' value='".$Excelente."' color='".$bgColor."'/>";
	$strXMLCuerpo .= "<set name='BUENO' value='".$Bueno."' color='".$bgColor."'/>";
	$strXMLCuerpo .= "<set name='REGULAR' value='".$Regular."' color='".$bgColor."'/>";
	$strXMLCuerpo .= "<set name='MALO' value='".$Malo."' color='".$bgColor."'/>";
	$strXMLCuerpo .= "<set name='MUY MALO' value='".$Muymalo."' color='".$bgColor."'/>";
	

	$htmlTb .= "</tr>";
	
	$strParam = "baseFontColor='#000000'";
	$strParam .= "bgcolor='#FFFFFF'"; 
		$strParam .= "caption='RESUMEN SATISFACCION CLIENTES'"; // PARA TORTAS
	$strParam .= "decimalPrecision='2'";
	$strParam .= "decimalSeparator='.'";
	$strParam .= "formatNumberScale='0'";
	$strParam .= "outCnvBaseFontColor='#FFFFFF'";
	$strParam .= "pieBorderAlpha='200'";
	$strParam .= "pieFillAlpha='90'";
	$strParam .= "pieSliceDepth='30'";
	$strParam .= "rotateNames='1'";
	$strParam .= "showBorder='0'";
        $strParam .= "shownames='1'"; // PARA TORTAS
	$strParam .= "showPercentageInLabel='1'";
	$strParam .= "showValues='1'";
	$strParam .= "thousandSeparator=','";
	
	$strXML  = "";
	$strXML .= "<graph ".$strParam.">";
		$strXML .= $strXMLCuerpo;
	$strXML .= "</graph>";
	
	$htmlTh .= "<thead class=\"tituloColumna\" height=\"22\">";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\" >";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td width=\"60%\">Nivel de Satisfaccion</td>";
				$htmlTh .= "<td width=\"20%\">Cant</td>";
				$htmlTh .= "<td width=\"20%\">%</td>";
				
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante\" onclick=\"xajax_formGrafico(this.id,'%s','%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						11,
						"RESUMEN SATISFACCION CLIENTES",
						str_replace("'","|*|",$strXML));
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$htmlTableFin .= "</table>";

	$objResponse->assign("divListaResumen","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	return $objResponse;
}

function formGrafico($nomObjeto, $tituloVentana, $strXML) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	openImg(byId('".$nomObjeto."'));");
	
	
		$html = renderChartHTML("../js/libGraficos/Charts/Column3D.swf", "", str_replace("|*|","'",$strXML), "myNext", 800, 500);
	
	$objResponse->assign("tdGrafico","innerHTML",$html);
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML",$tituloVentana);
	/*$objResponse->script("
	centrarDiv(byId('divFlotante'));");*/
	
	return $objResponse;
}

function imprimirResumen($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$valForm['lstEmpresa'],
		$valForm['txtFecha']);
	
	$objResponse->script(sprintf("verVentana('reportes/sa_resumen_clientes_pdf.php?valBusq=%s', 1000, 500);",
		$valBusq));
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresaFinal");
$xajax->register(XAJAX_FUNCTION,"resumenLLamadas");
$xajax->register(XAJAX_FUNCTION,"formGrafico");
$xajax->register(XAJAX_FUNCTION,"imprimirResumen");
?>