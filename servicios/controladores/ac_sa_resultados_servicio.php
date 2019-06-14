<?php
//error_reporting(E_ALL);
//ini_set("display_errors", 1);

function buscar($valForm) {
	
	$objResponse = new xajaxResponse();
	
	$objResponse->loadCommands(listadoResumen($valForm));
	
	return $objResponse;
}





function cargaLstEmpresa($selId = ""){
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY nombre_empresa",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstEmpresa\" name=\"lstEmpresa\" onchange=\"xajax_cargaLstCargo(this.value);\">";
		$html .="<option value=\"-1\">[ Todos ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$nombreSucursal = "";
		if ($row['id_empresa_padre_suc'] > 0)
			$nombreSucursal = " - ".$row['nombre_empresa_suc']." (".$row['sucursal'].")";

		$selected = "";
		if ($selId == $row['id_empresa_reg'])
			$selected = "selected='selected'";

		$html .= "<option ".$selected." value=\"".$row['id_empresa_reg']."\">".utf8_encode($row['nombre_empresa'].$nombreSucursal)."</option>";
	}
	$html .= "</select>";

	$objResponse->assign("tdlstEmpresa","innerHTML",$html);

	return $objResponse;
}




function listadoResumen($valForm) {
	$objResponse = new xajaxResponse();
	
	if ($valForm["lstEmpresa"] == ""){
			$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		}else{
			$idEmpresa = $valForm["lstEmpresa"];
		}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// RESUMEN SERVICIO
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

	

	/////////// ENTRADA DE VEHICULOS
	$sql0 = "SELECT count(id_recepcion) as entrada_vehiculo FROM sa_recepcion WHERE id_empresa = ".$idEmpresa." AND DATE_FORMAT(fecha_entrada,'%Y/%m/%d') BETWEEN '".date("Y/m/d",strtotime($valForm['txtFechaDesde']))."' AND '".date("Y/m/d",strtotime($valForm['txtFechaHasta']))."'";

	$rs0 = mysql_query($sql0);
	if (!$rs0) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row0 = mysql_fetch_array($rs0);
	
	
	
	/////////// ENTRADA POR RAPID SERVICE
	$sql1 = "SELECT count(id_recepcion) as rapid_service FROM sa_recepcion WHERE id_empresa = ".$idEmpresa." AND serviexp = 0 AND DATE_FORMAT(fecha_entrada,'%Y/%m/%d') BETWEEN '".date("Y/m/d",strtotime($valForm['txtFechaDesde']))."' AND '".date("Y/m/d",strtotime($valForm['txtFechaHasta']))."'";

	$rs1 = mysql_query($sql1);
	if (!$rs1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row1 = mysql_fetch_array($rs1);
	
	
	/////////// NUMERO DE CITAS
	$sql2 = "SELECT count(id_cita) as citas FROM sa_cita WHERE id_empresa = ".$idEmpresa." AND DATE_FORMAT(fecha_cita,'%Y/%m/%d') BETWEEN '".date("Y/m/d",strtotime($valForm['txtFechaDesde']))."' AND '".date("Y/m/d",strtotime($valForm['txtFechaHasta']))."'";
	
	$rs2 = mysql_query($sql2);
	if (!$rs2) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row2 = mysql_fetch_array($rs2);
	
	
	/////////// VEHICULOS INSPECCIONADOS
	$sql3 = "SELECT count(id_recepcion) as puente FROM sa_recepcion WHERE id_empresa = ".$idEmpresa." AND puente = 0 AND DATE_FORMAT(fecha_entrada,'%Y/%m/%d') BETWEEN '".date("Y/m/d",strtotime($valForm['txtFechaDesde']))."' AND '".date("Y/m/d",strtotime($valForm['txtFechaHasta']))."'";

	$rs3 = mysql_query($sql3);
	if (!$rs3) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row3 = mysql_fetch_array($rs3);
	
	
	/////////// PRESUPUESTOS ENTREGADOS
	$sql4= "SELECT count(id_presupuesto) as presupuesto FROM sa_presupuesto WHERE id_empresa = ".$idEmpresa." AND DATE_FORMAT(fecha_presupuesto,'%Y/%m/%d') BETWEEN '".date("Y/m/d",strtotime($valForm['txtFechaDesde']))."' AND '".date("Y/m/d",strtotime($valForm['txtFechaHasta']))."'";
	
	$rs4 = mysql_query($sql4);
	if (!$rs4) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row4 = mysql_fetch_array($rs4);
	
	
	/////////// REPARACIONES REPETIDAS
	$sql5= "SELECT count(id_orden) as retrabajo FROM sa_orden WHERE id_empresa = ".$idEmpresa." AND id_tipo_orden= 6 AND DATE_FORMAT(tiempo_orden,'%Y/%m/%d') BETWEEN '".date("Y/m/d",strtotime($valForm['txtFechaDesde']))."' AND '".date("Y/m/d",strtotime($valForm['txtFechaHasta']))."'";
	
	$rs5 = mysql_query($sql5);
	if (!$rs5) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row5 = mysql_fetch_array($rs5);
	
	
	/////////// HORAS FACTURADAS
	$sql6= "SELECT 
  SUM(sa_det_orden_tempario.ut) / 100 AS horas,
  pg_empleado.nombre_empleado
FROM
  sa_orden
  INNER JOIN sa_det_orden_tempario ON (sa_orden.id_orden = sa_det_orden_tempario.id_orden)
  INNER JOIN sa_mecanicos ON (sa_det_orden_tempario.id_mecanico = sa_mecanicos.id_mecanico)
  INNER JOIN pg_empleado ON (sa_mecanicos.id_empleado = pg_empleado.id_empleado) WHERE sa_orden.id_empresa = ".$idEmpresa." AND DATE_FORMAT(sa_orden.tiempo_orden,'%Y/%m/%d') BETWEEN '".date("Y/m/d",strtotime($valForm['txtFechaDesde']))."' AND '".date("Y/m/d",strtotime($valForm['txtFechaHasta']))."' GROUP BY pg_empleado.id_empleado";
	
	$rs6 = mysql_query($sql6);
	if (!$rs6) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row6 = mysql_fetch_array($rs6);
	
		
	// ENTRADA VEHICULOS
	
//	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
//	$contFila++;
//	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
//		$htmlTb .= "<td align=\"left\">"."Entrada de Vehiculos:"."</td>";
//		$htmlTb .= "<td align=\"rigth\">".$row0['entrada_vehiculo']."</td>";
//		
//		
//	$htmlTb .= "</tr>";
//	
//	// ENTRADA RAPID SERVICE
//	
//	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
//	$contFila++;
//	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
//		$htmlTb .= "<td align=\"left\">"."Entrada por Rapid Service:"."</td>";
//		$htmlTb .= "<td align=\"rigth\">".$row1['rapid_service']."</td>";
//		
//		
//	$htmlTb .= "</tr>";
//	
//	// CITAS
//
//	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
//	$contFila++;
//	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
//		$htmlTb .= "<td align=\"left\">"."Numero de Citas:"."</td>";
//		$htmlTb .= "<td align=\"rigth\">".$row2['citas']."</td>";
//		
//		
//	$htmlTb .= "</tr>";
//
//	// CITAS REALIZADAS
//	
//	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
//	$contFila++;
//	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
//		$htmlTb .= "<td align=\"left\">"."Citas Realizadas:"."</td>";
//		$htmlTb .= "<td align=\"rigth\">".$row2['citas']."</td>";
//		
//		
//	$htmlTb .= "</tr>";
//		
//		
//	// VEHICULOS INSPECCIONADOS
//
//	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
//	$contFila++;
//	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
//		$htmlTb .= "<td align=\"left\">"."Vehiculos Inspeccionados:"."</td>";
//		$htmlTb .= "<td align=\"rigth\">".$row3['puente']."</td>";
//		
//		
//	$htmlTb .= "</tr>";
//	
//	
//	
//	// PRESUPUESTOS ENTREGADOS
//	
//	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
//	$contFila++;
//	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
//		$htmlTb .= "<td align=\"left\">"."Presupuestos Entregados:"."</td>";
//		$htmlTb .= "<td align=\"rigth\">".$row4['presupuesto']."</td>";
//		
//		
//	$htmlTb .= "</tr>";
//	
//	
//	// REPARACIONES REPETIDAS
//	
//	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
//	$contFila++;
//	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
//		$htmlTb .= "<td align=\"left\">"."Reparaciones Repetidas:"."</td>";
//		$htmlTb .= "<td align=\"rigth\">".$row5['retrabajo']."</td>";
//		
//		
//	$htmlTb .= "</tr>";
//        
//        ////GREGOR
//        
//        //consulta 6 - Horas Facturadas ya estaba la consulta pero no lo imprimieron
//        $clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
//	$contFila++;
//	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
//		$htmlTb .= "<td align=\"left\">"."Horas Facturadas:"."</td>";
//		$htmlTb .= "<td align=\"rigth\">".ceil($row6['horas'])."</td>";     
//        
        
        //Consulta extra ORDENES
        
        $query_tipo_ordenes = "SELECT COUNT(sa_orden.id_tipo_orden) as cantidad_tipo, nombre_tipo_orden FROM sa_orden LEFT JOIN sa_tipo_orden ON sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden WHERE sa_orden.id_empresa = ".$idEmpresa." AND DATE_FORMAT(tiempo_orden,'%Y/%m/%d') BETWEEN '".date("Y/m/d",strtotime($valForm['txtFechaDesde']))."' AND '".date("Y/m/d",strtotime($valForm['txtFechaHasta']))."' GROUP BY nombre_tipo_orden";
        $seleccion_tipo_ordenes = mysql_query($query_tipo_ordenes);        
        if (!$seleccion_tipo_ordenes) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
        $cantidad_tipo_ordenes = mysql_num_rows($seleccion_tipo_ordenes);
		
        //envio
        while($row = mysql_fetch_array($seleccion_tipo_ordenes)){
            $clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
                $contFila++;
                $htmlTb .= "<tr align=\"right\" class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
		$htmlTb .= "<td align=\"left\">"."Ordenes Registradas(".$row['nombre_tipo_orden']."):</td>";
		$htmlTb .= "<td align=\"rigth\">".$row['cantidad_tipo']."</td>";
        }

        //Consulta 7 - Cantidad de ordenes cerradas en magnetoplano
        $sql7 = "SELECT DISTINCT sa_magnetoplano.id_orden FROM sa_magnetoplano 
				LEFT JOIN sa_orden ON sa_magnetoplano.id_orden = sa_orden.id_orden
				WHERE sa_orden.id_empresa = ".$idEmpresa." AND activo = 0 AND DATE_FORMAT(fecha_creada,'%Y/%m/%d') BETWEEN '".date("Y/m/d",strtotime($valForm['txtFechaDesde']))."' AND '".date("Y/m/d",strtotime($valForm['txtFechaHasta']))."'";
                
        $rs7 = mysql_query($sql7);        
	if (!$rs7) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$cantidad_ordenes_cerradas = mysql_num_rows($rs7);
        
        // Envio 7        
       $clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
		$htmlTb .= "<td align=\"left\">"."Ordenes Terminadas (magnetoplano):"."</td>";
		$htmlTb .= "<td align=\"rigth\">".$cantidad_ordenes_cerradas."</td>";
        
        //Consulta 8 - Cantidad de ordenes cerradas en magnetoplano
        $sql8 =  "SELECT DISTINCT  sa_magnetoplano.id_orden FROM sa_magnetoplano 
		LEFT JOIN sa_orden ON sa_magnetoplano.id_orden = sa_orden.id_orden
		WHERE id_empresa = ".$idEmpresa." AND activo = 1 AND DATE_FORMAT(fecha_creada,'%Y/%m/%d') BETWEEN '".date("Y/m/d",strtotime($valForm['txtFechaDesde']))."' AND '".date("Y/m/d",strtotime($valForm['txtFechaHasta']))."'";
        $rs8 = mysql_query($sql8);        
	if (!$rs8) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$cantidad_ordenes_abiertas = mysql_num_rows($rs8);
        
        // Envio 8
       $clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
		$htmlTb .= "<td align=\"left\">"."Ordenes en Proceso (magnetoplano):"."</td>";
		$htmlTb .= "<td align=\"rigth\">".$cantidad_ordenes_abiertas."</td>";
	   
        //consulta 9 - Total horas estimadas en magnetoplano
   
        $sql9 =  " SELECT SUM(duracion) as duracion
                    FROM (SELECT sa_magnetoplano.id_orden, duracion
                    FROM sa_magnetoplano
					LEFT JOIN sa_orden ON sa_magnetoplano.id_orden = sa_orden.id_orden
                    WHERE id_empresa = ".$idEmpresa." AND DATE_FORMAT(fecha_creada,'%Y/%m/%d') BETWEEN '".date("Y/m/d",strtotime($valForm['txtFechaDesde']))."' AND '".date("Y/m/d",strtotime($valForm['txtFechaHasta']))."' GROUP BY id_orden)sub  
                     ";
        $rs9 = mysql_query($sql9);
	if (!$rs9) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
        $row9 = mysql_fetch_array($rs9);
        
        // Envio 9
        $clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
		$htmlTb .= "<td align=\"left\">"."Horas Estimadas (magnetoplano)"."</td>";
		$htmlTb .= "<td align=\"rigth\">".ceil($row9["duracion"]/60)."</td>";
	        
//        //consulta 10 - Total de horas reales en magnetoplano
//        $sql10 = " SELECT SUM(tiempo_real) as tiempo_real
//                    FROM (SELECT id_orden, tiempo_real
//                    FROM sa_magnetoplano
//                    WHERE DATE_FORMAT(fecha_creada,'%Y/%m/%d') BETWEEN '".date("Y/m/d",strtotime($valForm['txtFechaDesde']))."' AND '".date("Y/m/d",strtotime($valForm['txtFechaHasta']))."' GROUP BY id_orden)sub  
//                     ";
//        $rs10= mysql_query($sql10);
//        if (!$rs10) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); 
//        $row10 = mysql_fetch_array($rs10);
//        
//        //Envio 10
//        $clase = (fmod($contFila,2) == 0) ? "trResaltar4" : "trResaltar5";
//        $contFila++;
//        $htmlTb .= "<tr align=\"right\" class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
//                $htmlTb .= "<td align=\"left\">"."Horas Trabajadas (magnetoplano)"."</td>";
//                $htmlTb .= "<td align=\"right\">".ceil($row10["tiempo_real"]/60)."</td>";
//                
//        //consulta 11 - Horas presencia real magnetoplano
//        $sql11 = "SELECT SUM(minutos_presencia) as minutos_presencia 
//                  FROM sa_presencia_mecanicos
//                  WHERE DATE_FORMAT(fecha_creada,'%Y/%m/%d') BETWEEN '".date("Y/m/d",strtotime($valForm['txtFechaDesde']))."' AND '".date("Y/m/d",strtotime($valForm['txtFechaHasta']))."'";
//        $rs11 = mysql_query($sql11);
//        if (!$rs11) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
//        $row11 = mysql_fetch_array($rs11);
//        
//        //envio 11
//        $clase = (fmod($contFila,2) == 0) ? "trResaltar4" : "trResaltar5";
//        $contFila++;
//        $htmlTb .= "<tr align=\"right\" class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
//                $htmlTb .= "<td align=\"left\">"."Horas Presencia Real"."</td>";
//                $htmlTb .= "<td align=\"right\">".ceil($row11["minutos_presencia"]/60)."</td>";
//                
//        //consulta 12 - consulta de 
//        $sql12 = " SELECT SUM(minutos_presencia) as minutos_presencia
//            FROM sa_presencia_mecanicos
//            WHERE DATE_FORMAT(tiempo_entrada, '%Y/%m/%d') BETWEEN '".date("Y/m/d",strtotime($valForm['txtFechaDesde']))."' AND '".date("Y/m/d",strtotime($valForm['txtFechaHasta']))."'";
//        $rs12 = mysql_query($sql12);
//        if (!$rs12) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
//        $row12 = mysql_fetch_array($rs12);
//        
//        $clase = (fmod($contFila,2) == 0) ? "trResaltar4" : "trResaltar5";
//        $contFila++;
//        
//        $htmlTb .= "<tr align=\"right\" class = \"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
//                $htmlTb .= "<td align=\"left\">"."Horas Presencia Real"."</td>";
//                $htmlTb .= "<td align=\"right\">".ceil($row12["minutos_presencia"]/60)."</td>";
//        
        ////FIN GREGOR
                
	$htmlTb .= "<tr align=\"right\" class=\"tituloColumna\" height=\"22\">";
	$htmlTb .= "<td colspan=\"3\"></td>";
	$htmlTb .= "</tr>";
	
	$htmlTb .= "</tr>";
	
	
	
	$htmlTh .= "<thead class=\"tituloColumna\" height=\"22\">";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\" >";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td width=\"70%\">Resultados Servicio</td>";
				$htmlTh .= "<td width=\"30%\">Num</td>";
				
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$htmlTableFin .= "</table>";

	$objResponse->assign("divListaResumen","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	return $objResponse;
}




$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresa");
$xajax->register(XAJAX_FUNCTION,"listadoResumen");


?>