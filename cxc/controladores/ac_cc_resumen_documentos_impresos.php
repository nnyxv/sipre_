<?php
function buscarDocumentos($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta']);
		
	$objResponse->loadCommands(resumenDocumentos($valBusq));
	
	return $objResponse;
}

function resumenDocumentos($valBusq = "") {
	$objResponse = new xajaxResponse();
		
	$valCadBusq = explode("|", $valBusq);	
	
        $html = "";
        $filtroEmpresa = "";
        
        if($valCadBusq[1] == "" || $valCadBusq[2] == ""){
            return $objResponse->alert("Debe seleccionar un rango de fechas");
        }
                
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$filtroEmpresa = sprintf("AND id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
        
        
	$filtroFecha = sprintf("AND fecha_impresora BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	
        
        $queryFacturas = sprintf("SELECT COUNT(*) as cantidad,
                                         CONVERT(MIN(consecutivo_fiscal),SIGNED INTEGER) as consecutivo_primero,
                                         CONVERT(MAX(consecutivo_fiscal),SIGNED INTEGER) as consecutivo_ultimo,
                                         MIN(fecha_impresora) as fecha_primera, 
                                         MAX(fecha_impresora) as fecha_ultima, 
                                         MIN(hora_impresora) as hora_primera, 
                                         MAX(hora_impresora) as hora_ultima, 
                                         SUM(baseImponible) as total_base, 
                                         SUM(montoExento) as total_excento,
                                         SUM(montoTotalFactura) as total
                                 FROM cj_cc_encabezadofactura 
                                 WHERE consecutivo_fiscal IS NOT NULL %s %s",
                                $filtroEmpresa,
                                $filtroFecha);
        
        $queryNotas = sprintf("SELECT COUNT(*) as cantidad,
                                      CONVERT(MIN(consecutivo_fiscal),SIGNED INTEGER) as consecutivo_primero,
                                      CONVERT(MAX(consecutivo_fiscal),SIGNED INTEGER) as consecutivo_ultimo,
                                      MIN(fecha_impresora) as fecha_primera, 
                                      MAX(fecha_impresora) as fecha_ultima, 
                                      MIN(hora_impresora) as hora_primera, 
                                      MAX(hora_impresora) as hora_ultima, 
                                      SUM(baseimponibleNotaCredito) as total_base, 
                                      SUM(montoExentoCredito) as total_excento,
                                      SUM(montoNetoNotaCredito) as total
                               FROM cj_cc_notacredito 
                               WHERE consecutivo_fiscal IS NOT NULL %s %s",
                                $filtroEmpresa,
                                $filtroFecha);
            
        
        $rsFacturas = mysql_query($queryFacturas);
        if(!$rsFacturas) { return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__); }
        
        $rsNotas = mysql_query($queryNotas);
        if(!$rsNotas) { return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__); }
        
        $rowFacturas = mysql_fetch_assoc($rsFacturas);
        $rowNotas = mysql_fetch_assoc($rsNotas);
        
        
        $html .= "<table class='tabla-resumen'>";
        $html .= "<tr><th colspan='4'>Facturas</th></tr>";
        
        $html .= "<tr><td>Fecha/Hora Primera Factura</td><td>".formatoFecha($rowFacturas["fecha_primera"])." ".formatoHora($rowFacturas["hora_primera"])."</td>";
        $html .= "<td>Fecha/Hora Última Factura</td><td>".formatoFecha($rowFacturas["fecha_ultima"])." ".formatoHora($rowFacturas["hora_ultima"])."</td></tr>";
        
        $html .= "<tr><td>Cantidad Facturas Emitidas</td><td>".$rowFacturas["cantidad"]."</td>";
        $html .= "<td>Primer y Último Consecutivo Fiscal</td><td>".$rowFacturas["consecutivo_primero"]." - ".$rowFacturas["consecutivo_ultimo"]."</td></tr>";
        
        $html .= "<tr><td></td><td></td><td>Total Base Imponible</td><td>".formatoNumero($rowFacturas["total_base"])."</td></tr>";
        $html .= "<tr><td></td><td></td><td>Total Exento</td><td>".formatoNumero($rowFacturas["total_excento"])."</td></tr>";
        $html .= "<tr><td></td><td></td><td>Monto Total Facturas</td><td>".formatoNumero($rowFacturas["total"])."</td></tr>";
        $html .= "</table>";


        $html .= "<br><br>";
        $html .= "<table class='tabla-resumen'>";
        $html .= "<tr><th colspan='4'>Notas de Crédito</th></tr>";
        
        $html .= "<tr><td>Fecha/Hora Primera Nota de Crédito</td><td>".formatoFecha($rowNotas["fecha_primera"])." ".formatoHora($rowNotas["hora_primera"])."</td>";
        $html .= "<td>Fecha/Hora Última Nota de Crédito</td><td>".formatoFecha($rowNotas["fecha_ultima"])." ".formatoHora($rowNotas["hora_ultima"])."</td></tr>";
        
        $html .= "<tr><td>Cantidad Notas de Crédito Emitidas</td><td>".$rowNotas["cantidad"]."</td>";
        $html .= "<td>Primer y Último Consecutivo Fiscal</td><td>".$rowNotas["consecutivo_primero"]." - ".$rowNotas["consecutivo_ultimo"]."</td></tr>";
        
        $html .= "<tr><td></td><td></td><td>Total Base Imponible</td><td>".formatoNumero($rowNotas["total_base"])."</td></tr>";
        $html .= "<tr><td></td><td></td><td>Total Exento</td><td>".formatoNumero($rowNotas["total_excento"])."</td></tr>";
        $html .= "<tr><td></td><td></td><td>Monto Total Notas de Crédito</td><td>".formatoNumero($rowNotas["total"])."</td></tr>";
        $html .= "</table>";
        
        
        /* TOTAL DE IMPUESTOS POR ITEM DETALLES */
        
        $queryFacturas2 = sprintf("SELECT idFactura, idDepartamentoOrigenFactura
                                 FROM cj_cc_encabezadofactura 
                                 WHERE consecutivo_fiscal IS NOT NULL %s %s",
                                $filtroEmpresa,
                                $filtroFecha);
        
        $queryNotas2 = sprintf("SELECT idNotaCredito, idDepartamentoNotaCredito
                               FROM cj_cc_notacredito 
                               WHERE consecutivo_fiscal IS NOT NULL %s %s",
                                $filtroEmpresa,
                                $filtroFecha);
        
        $rsFacturas2 = mysql_query($queryFacturas2);
        if(!$rsFacturas2) { return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__); }
        
        $rsNotas2 = mysql_query($queryNotas2);
        if(!$rsNotas2) { return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__); }
        
        
        $arrayImpuestosFactura = array();
        $arrayFacturaServicios = array();
        $arrayFacturaRepuestos = array();
        $arrayFacturaVehiculos = array();

        $arrayImpuestosNota = array();
        $arrayNotaServicios = array();
        $arrayNotaRepuestos = array();
        $arrayNotaVehiculos = array();
        //die($queyFacturas2);
        //error_reporting(E_ALL);
        //ini_set("display_errors", 1);
        while ($row = mysql_fetch_assoc($rsFacturas2)){                 
            if($row["idDepartamentoOrigenFactura"] == 0){
                facturaRepuestos($row['idFactura'],$arrayFacturaRepuestos, $arrayImpuestosFactura);
            }elseif($row["idDepartamentoOrigenFactura"] == 1){
                facturaServicios($row['idFactura'],$arrayFacturaServicios, $arrayImpuestosFactura);                
            }elseif($row["idDepartamentoOrigenFactura"] == 2){
                facturaVehiculos($row['idFactura'],$arrayFacturaVehiculos, $arrayImpuestosFactura);
            }            
        }
        
        while ($row = mysql_fetch_assoc($rsNotas2)){
            if($row["idDepartamentoNotaCredito"] == 0){
                notaRepuestos($row['idNotaCredito'],$arrayNotaRepuestos, $arrayImpuestosNota);
            }elseif($row["idDepartamentoNotaCredito"] == 1){
                notaServicios($row['idNotaCredito'],$arrayNotaServicios, $arrayImpuestosNota);
            }elseif($row["idDepartamentoNotaCredito"] == 2){
                notaVehiculos($row['idNotaCredito'],$arrayNotaVehiculos, $arrayImpuestosNota);
            }      
        }
        
        
        $html .= "<br><br>";
        $html .= "<table class='tabla-resumen'>";
        $html .= "<tr><th colspan='7'>Detalles Facturas</th></tr>";      
        $html .= "<tr><td>Servicios</td><td>Total Base Imponible</td><td>".totalBaseImponible($arrayFacturaServicios)."</td><td>Total Exento</td><td>".totalExento($arrayFacturaServicios)."</td><td>Total con Impuesto</td><td>".totalArray($arrayFacturaServicios)."</td></tr>";
        $html .= "<tr><td>Repuestos</td><td>Total Base Imponible</td><td>".totalBaseImponible($arrayFacturaRepuestos)."</td><td>Total Exento</td><td>".totalExento($arrayFacturaRepuestos)."</td><td>Total con Impuesto</td><td>".totalArray($arrayFacturaRepuestos)."</td></tr>";
        $html .= "<tr><td>Vehículos</td><td>Total Base Imponible</td><td>".totalBaseImponible($arrayFacturaVehiculos)."</td><td>Total Exento</td><td>".totalExento($arrayFacturaVehiculos)."</td><td>Total con Impuesto</td><td>".totalArray($arrayFacturaVehiculos)."</td></tr>";
        $html .= "<tr><td></td><td></td><td></td><td></td><td></td><td>Monto Total Facturas</td><td>".totalArray($arrayImpuestosFactura)."</td></tr>";
        $html .= "</table>";
        
        $html .= "<br><br>";
        $html .= "<table class='tabla-resumen'>";
        $html .= "<tr><th colspan='7'>Detalles Notas de Crédito</th></tr>";      
        $html .= "<tr><td>Servicios</td><td>Total Base Imponible</td><td>".totalBaseImponible($arrayNotaServicios)."</td><td>Total Exento</td><td>".totalExento($arrayNotaServicios)."</td><td>Total con Impuesto</td><td>".totalArray($arrayNotaServicios)."</td></tr>";
        $html .= "<tr><td>Repuestos</td><td>Total Base Imponible</td><td>".totalBaseImponible($arrayNotaRepuestos)."</td><td>Total Exento</td><td>".totalExento($arrayNotaRepuestos)."</td><td>Total con Impuesto</td><td>".totalArray($arrayNotaRepuestos)."</td></tr>";
        $html .= "<tr><td>Vehículos</td><td>Total Base Imponible</td><td>".totalBaseImponible($arrayNotaVehiculos)."</td><td>Total Exento</td><td>".totalExento($arrayNotaVehiculos)."</td><td>Total con Impuesto</td><td>".totalArray($arrayNotaVehiculos)."</td></tr>";
        $html .= "<tr><td></td><td></td><td></td><td></td><td></td><td>Monto Total Notas</td><td>".totalArray($arrayImpuestosNota)."</td></tr>";
        $html .= "</table>";
        
        
        /* IMPUESTOS */
        
        $arrayTotalesImpuesto = array(); //Almacena la resta de facturas - notas de credito
        
        $html .= "<br><br>";
        $html .= "<table class='tabla-resumen'>";
        $html .= "<tr><th colspan='5'>Impuestos</th></tr>";      
        $html .= "<tr><td><b>Facturas</b></td><td></td><td></td><td></td><td></td></tr>";
        foreach($arrayImpuestosFactura as $key => $arrayImpuestos){//key es idIva
            if($key != ""){
                $arrayTotalesImpuesto[$key]['porcentaje'] = $arrayImpuestos['porcentaje'];
                $arrayTotalesImpuesto[$key]['total'] += $arrayImpuestos['total'];
                        
                $baseImponible = formatoNumero(round($arrayImpuestos['total'],2));
                $descripcionImpuesto = descripcionIva($key)." ".$arrayImpuestos['porcentaje']."%";            
                $totalImpuesto = formatoNumero(round($arrayImpuestos['total']*($arrayImpuestos['porcentaje']/100),2));

                $html .= "<tr><td>".$descripcionImpuesto."</td><td>Base Imponible</td><td>".$baseImponible."</td><td>Total Impuesto</td><td>".$totalImpuesto."</td></tr>";
            }
        }
        
        $html .= "<tr><td><b>Notas de Crédito</b></td><td></td><td></td><td></td><td></td></tr>";      
        foreach($arrayImpuestosNota as $key => $arrayImpuestos){//key es idIva
            if($key != ""){
                $arrayTotalesImpuesto[$key]['porcentaje'] = $arrayImpuestos['porcentaje'];
                $arrayTotalesImpuesto[$key]['total'] -= $arrayImpuestos['total'];
                
                $baseImponible = formatoNumero(round($arrayImpuestos['total'],2));
                $descripcionImpuesto = descripcionIva($key)." ".$arrayImpuestos['porcentaje']."%";            
                $totalImpuesto = formatoNumero(round($arrayImpuestos['total']*($arrayImpuestos['porcentaje']/100),2));

                $html .= "<tr><td>".$descripcionImpuesto."</td><td>Base Imponible</td><td>".$baseImponible."</td><td>Total Impuesto</td><td>".$totalImpuesto."</td></tr>";
            }
        }
        
        $html .= "<tr><td><b>Total</b></td><td></td><td></td><td></td><td></td></tr>";   
        foreach($arrayTotalesImpuesto as $key => $arrayImpuestos){//key es idIva
            if($key != ""){                
                $baseImponible = formatoNumero(round($arrayImpuestos['total'],2));
                $descripcionImpuesto = descripcionIva($key)." ".$arrayImpuestos['porcentaje']."%";            
                $totalImpuesto = formatoNumero(round($arrayImpuestos['total']*($arrayImpuestos['porcentaje']/100),2));

                $html .= "<tr><td>".$descripcionImpuesto."</td><td>Base Imponible</td><td>".$baseImponible."</td><td>Total Impuesto</td><td>".$totalImpuesto."</td></tr>";
            }
        }
        $html .= "</table>";
                
        
        
        
        
	$objResponse->assign("tdResumen","innerHTML",$html);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarDocumentos");
$xajax->register(XAJAX_FUNCTION,"resumenDocumentos");

function formatoFecha($fecha){
    if($fecha != NULL && $fecha != "" && $fecha != " "){
        return date(spanDateFormat, strtotime($fecha));
    }
}

function formatoHora($hora){
    if($hora != NULL && $hora != "" && $hora != " "){
        return date("H:i:s", strtotime($hora));
    }
}

function descripcionIva($idIva){
    
    $query = sprintf("SELECT observacion FROM pg_iva WHERE idIva = %s",
            valTpDato($idIva, "int"));
    $rs = mysql_query($query) or die(mysql_error().__LINE__);
    $row = mysql_fetch_assoc($rs);
   
    return $row['observacion'];
    
}

function totalArray($array){
    $total = 0;
    foreach($array as $arrayTotales){
        $total += $arrayTotales['total'] + ($arrayTotales['total']*($arrayTotales['porcentaje']/100));
    }
    return formatoNumero(round($total,2));
}

function totalExento($array){
    $total = 0;
    foreach($array as $arrayTotales){
        if($arrayTotales['porcentaje'] == ""){
            $total += $arrayTotales['total'];
        }
    }
    return formatoNumero(round($total,2));
}

function totalBaseImponible($array){
    $total = 0;
    foreach($array as $arrayTotales){
        if($arrayTotales['porcentaje'] != ""){
            $total += $arrayTotales['total'];
        }
    }
    return formatoNumero(round($total,2));
}

function formatoNumero($numero){
    return number_format($numero,2,",",".");
}

function facturaServicios($idFactura,&$arrayFacturaServicios,&$arrayImpuestosFactura){

    // BUSCA LOS DATOS DE LA FACTURA
	$query = sprintf("SELECT		
		orden.idIva,
		orden.iva		
	FROM cj_cc_encabezadofactura fact_vent
	LEFT JOIN sa_orden orden ON (fact_vent.numeroPedido = orden.id_orden)		
	WHERE fact_vent.idFactura = %s;",
		valTpDato($idFactura, "int"));
	$rs = mysql_query($query);
	if (!$rs) die(mysql_error()."<br><br>Line: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
    
    /* DETALLES DE LOS REPUESTOS */
	$queryRepuestosGenerales = sprintf("SELECT
		sa_det_fact_articulo.cantidad,
		sa_det_fact_articulo.precio_unitario,
		sa_det_fact_articulo.id_iva,
		sa_det_fact_articulo.iva		
	FROM sa_det_fact_articulo		
	WHERE sa_det_fact_articulo.idFactura = %s
	ORDER BY sa_det_fact_articulo.id_paquete",
		valTpDato($idFactura, "int"));
	$rsOrdenDetRep = mysql_query($queryRepuestosGenerales) or die(mysql_error().__LINE__);
	
	while ($rowOrdenDetRep = mysql_fetch_assoc($rsOrdenDetRep)) {
		$cantidad = $rowOrdenDetRep['cantidad'];
		$precioUnit = ($rowOrdenDetRep['precio_unitario']);
		$total = (($rowOrdenDetRep['cantidad']*$rowOrdenDetRep['precio_unitario']));
	                
                $arrayFacturaServicios[$rowOrdenDetRep['id_iva']]['porcentaje'] = $rowOrdenDetRep['iva'];
                $arrayFacturaServicios[$rowOrdenDetRep['id_iva']]['total'] += $total;
                
                $arrayImpuestosFactura[$rowOrdenDetRep['id_iva']]['porcentaje'] = $rowOrdenDetRep['iva'];
                $arrayImpuestosFactura[$rowOrdenDetRep['id_iva']]['total'] += $total;
                
	}
	
	/* DETALLES DE LOS TEMPARIOS */
	$queryFactDetTemp = sprintf("SELECT
		sa_det_fact_tempario.operador,
		sa_det_fact_tempario.id_tempario,
		sa_det_fact_tempario.precio,
		sa_det_fact_tempario.base_ut_precio,
		sa_det_fact_tempario.id_modo,
		(case sa_det_fact_tempario.id_modo
			when '1' then
				sa_det_fact_tempario.ut * sa_det_fact_tempario.precio_tempario_tipo_orden / sa_det_fact_tempario.base_ut_precio
			when '2' then
				sa_det_fact_tempario.precio
			when '3' then
				sa_det_fact_tempario.costo
			when '4' then
				'4'
		end) AS total_por_tipo_orden,
		(case sa_det_fact_tempario.id_modo
			when '1' then
				sa_det_fact_tempario.ut
			when '2' then
				sa_det_fact_tempario.precio
			when '3' then
				sa_det_fact_tempario.costo
			when '4' then
				'4'
		end) AS precio_por_tipo_orden,	
		sa_det_fact_tempario.precio_tempario_tipo_orden
	FROM sa_det_fact_tempario		
	WHERE sa_det_fact_tempario.idFactura = %s",
		valTpDato($idFactura, "int"));
	$rsFactDetTemp = mysql_query($queryFactDetTemp) or die(mysql_error().__LINE__);
	
	while ($rowFactDetTemp = mysql_fetch_assoc($rsFactDetTemp)) {
				
		$precioUnit = ($rowFactDetTemp['total_por_tipo_orden']);//($caracterPrecioTempario);
		$total = ($rowFactDetTemp['total_por_tipo_orden']);
		                
                
                $arrayFacturaServicios[$row['idIva']]['porcentaje'] = $row['iva'];
                $arrayFacturaServicios[$row['idIva']]['total'] += $total;
                
                $arrayImpuestosFactura[$row['idIva']]['porcentaje'] = $row['iva'];
                $arrayImpuestosFactura[$row['idIva']]['total'] += $total;
		
	}
	
	
	/* DETALLE DE LOS TOT */
	$queryDetalleTot = sprintf("SELECT *, cp_factura.observacion_factura FROM sa_orden_tot
		INNER JOIN cp_proveedor ON (sa_orden_tot.id_proveedor = cp_proveedor.id_proveedor)
		INNER JOIN sa_det_fact_tot ON (sa_orden_tot.id_orden_tot = sa_det_fact_tot.id_orden_tot)
                INNER JOIN cp_factura ON sa_orden_tot.id_factura = cp_factura.id_factura
	WHERE sa_det_fact_tot.idFactura = %s",
		valTpDato($idFactura, "int"));
	$rsDetalleTot = mysql_query($queryDetalleTot) or die(mysql_error().__LINE__);
		
	while ($rowDetalleTot = mysql_fetch_assoc($rsDetalleTot)) {
		
		$precioUnit = ($rowDetalleTot['monto_subtotal']+($rowDetalleTot['monto_subtotal']*$rowDetalleTot['porcentaje_tot']/100));
		$total = ($rowDetalleTot['monto_subtotal']+($rowDetalleTot['monto_subtotal']*$rowDetalleTot['porcentaje_tot']/100));
	
                $arrayFacturaServicios[$row['idIva']]['porcentaje'] = $row['iva'];
                $arrayFacturaServicios[$row['idIva']]['total'] += $total;
                
                $arrayImpuestosFactura[$row['idIva']]['porcentaje'] = $row['iva'];
                $arrayImpuestosFactura[$row['idIva']]['total'] += $total;
                	
	}
	
	
	/* DETALLES DE LAS NOTAS */
	$queryDetTipoDocNotas = sprintf("SELECT id_det_fact_nota AS idDetNota, descripcion_nota, precio
	FROM sa_det_fact_notas
	WHERE idFactura = %s",
		valTpDato($idFactura, "int"));
	$rsDetTipoDocNotas = mysql_query($queryDetTipoDocNotas) or die(mysql_error().__LINE__);
	
	//$xmlItemsFactura .= "<notas>";
	while ($rowDetTipoDocNotas = mysql_fetch_assoc($rsDetTipoDocNotas)) {
		
		$precioUnit = ($rowDetTipoDocNotas['precio']);
		$total = ($rowDetTipoDocNotas['precio']);
                
                $arrayFacturaServicios[$row['idIva']]['porcentaje'] = $row['iva'];
                $arrayFacturaServicios[$row['idIva']]['total'] += $total;
                
                $arrayImpuestosFactura[$row['idIva']]['porcentaje'] = $row['iva'];
                $arrayImpuestosFactura[$row['idIva']]['total'] += $total;
        }
}


function facturaRepuestos($idFactura,&$arrayFacturaRepuestos,&$arrayImpuestosFactura){    
                
	// DETALLES DE LOS REPUESTOS
	$queryDetalle = sprintf("SELECT
		subseccion.id_subseccion,
		art.codigo_articulo,
		tipo_art.descripcion AS descripcion_tipo,
		art.descripcion AS descripcion_articulo,
		seccion.descripcion AS descripcion_seccion,
		fact_vent_det.cantidad,
		fact_vent_det.precio_unitario,
		fact_vent_det.id_iva,
		fact_vent_det.iva,
		fact_vent_det.id_articulo,
		fact_vent_det.id_factura_detalle
	FROM iv_articulos art
		INNER JOIN iv_subsecciones subseccion ON (art.id_subseccion = subseccion.id_subseccion)
		INNER JOIN iv_tipos_articulos tipo_art ON (art.id_tipo_articulo = tipo_art.id_tipo_articulo)
		INNER JOIN iv_secciones seccion ON (subseccion.id_seccion = seccion.id_seccion)
		INNER JOIN cj_cc_factura_detalle fact_vent_det ON (art.id_articulo = fact_vent_det.id_articulo)
	WHERE fact_vent_det.id_factura = %s",
		valTpDato($idFactura, "int"));
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) die(mysql_error()."<br><br>Line: ".__LINE__);
	
	
	while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
		$total = $rowDetalle['cantidad'] * $rowDetalle['precio_unitario'];
		
                $arrayFacturaRepuestos[$rowDetalle['id_iva']]['porcentaje'] = $rowDetalle['iva'];
                $arrayFacturaRepuestos[$rowDetalle['id_iva']]['total'] += $total;
                
                $arrayImpuestosFactura[$rowDetalle['id_iva']]['porcentaje'] = $rowDetalle['iva'];
                $arrayImpuestosFactura[$rowDetalle['id_iva']]['total'] += $total;
                		
	}
}

function facturaVehiculos($idFactura,&$arrayFacturaVehiculos,&$arrayImpuestosFacturas){
                
    // BUSCA LOS DATOS DE LA UNIDAD
	$queryUnidad = sprintf("SELECT 
            fact_vent_det_vehic.id_factura_detalle_vehiculo,
		uni_bas.nom_uni_bas,
		marca.nom_marca,
		modelo.nom_modelo,
		vers.nom_version,
		ano.nom_ano,
		uni_fis.placa,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		color1.nom_color AS color_externo,
		fact_vent_det_vehic.precio_unitario,
		uni_bas.com_uni_bas,
		codigo_unico_conversion,
		marca_kit,
		marca_cilindro,
		modelo_regulador,
		serial1,
		serial_regulador,
		capacidad_cilindro,
		fecha_elaboracion_cilindro
	FROM cj_cc_factura_detalle_vehiculo fact_vent_det_vehic
		INNER JOIN an_unidad_fisica uni_fis ON (fact_vent_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
		INNER JOIN an_marca marca ON (uni_bas.mar_uni_bas = marca.id_marca)
		INNER JOIN an_modelo modelo ON (uni_bas.mod_uni_bas = modelo.id_modelo)
		INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		INNER JOIN an_ano ano ON (uni_fis.ano = ano.id_ano)
		INNER JOIN an_color color1 ON (uni_fis.id_color_externo1 = color1.id_color)
	WHERE fact_vent_det_vehic.id_factura = %s",
		valTpDato($idFactura, "int"));
	$rsUnidad = mysql_query($queryUnidad);
	if (!$rsUnidad) die(mysql_error()."<br><br>Line: ".__LINE__);
	$totalRowsUnidad = mysql_num_rows($rsUnidad);
	$rowUnidad = mysql_fetch_array($rsUnidad);
	
	
	if ($totalRowsUnidad > 0) {
		
                //1 factura por vehiculo, cada vehiculo puede poseer multiples impuestos al mismo tiempo
                $queryImpuestoVehiculo = sprintf("SELECT id_impuesto, impuesto FROM cj_cc_factura_detalle_vehiculo_impuesto"
                        . " WHERE id_factura_detalle_vehiculo = %s",
                        $rowUnidad['id_factura_detalle_vehiculo']);
                
                $rsImpuestoUnidad = mysql_query($queryImpuestoVehiculo);
                if (!$rsImpuestoUnidad) die(mysql_error()."<br><br>Line: ".__LINE__);
                $tieneImpuesto = mysql_num_rows($rsUnidad);
                 
                if($tieneImpuesto){
                    while ($rowImpuestoUnidad = mysql_fetch_assoc($rsImpuestoUnidad)){
                            $arrayFacturaVehiculos[$rowImpuestoUnidad['id_impuesto']]['porcentaje'] = $rowImpuestoUnidad['impuesto'];
                            $arrayFacturaVehiculos[$rowImpuestoUnidad['id_impuesto']]['total'] += $rowUnidad['precio_unitario'];

                            $arrayImpuestosFactura[$rowImpuestoUnidad['id_impuesto']]['porcentaje'] = $rowImpuestoUnidad['impuesto'];
                            $arrayImpuestosFactura[$rowImpuestoUnidad['id_impuesto']]['total'] += $rowUnidad['precio_unitario'];
                    }                    
                }                
	}
	
	
	$queryDet = sprintf("SELECT
		fact_vent_det_acc.id_factura_detalle_accesorios,
		fact_vent_det_acc.id_accesorio,
		fact_vent_det_acc.costo_compra,
		fact_vent_det_acc.cantidad,
		fact_vent_det_acc.precio_unitario,
                fact_vent_det_acc.id_iva,
                fact_vent_det_acc.iva,
                
		(CASE
			WHEN fact_vent_det_acc.id_iva = 0 THEN
				CONCAT(acc.nom_accesorio, ' (E)')
			ELSE
				acc.nom_accesorio
		END) AS nom_accesorio,
		fact_vent_det_acc.tipo_accesorio
	FROM cj_cc_factura_detalle_accesorios fact_vent_det_acc
		INNER JOIN an_accesorio acc ON (fact_vent_det_acc.id_accesorio = acc.id_accesorio)
	WHERE fact_vent_det_acc.id_factura = %s",
		valTpDato($idFactura, "int"));
	$rsDet = mysql_query($queryDet);
	if (!$rsDet) die(mysql_error()."<br><br>Line: ".__LINE__);
	while ($rowDet = mysql_fetch_array($rsDet)) {                        
                                
                $total = $rowDet['cantidad'] * $rowDet['precio_unitario'];
                   
                $arrayFacturaVehiculos[$rowDet['id_iva']]['porcentaje'] = $rowDet['iva'];
                $arrayFacturaVehiculos[$rowDet['id_iva']]['total'] += $total;

                $arrayImpuestosFactura[$rowDet['id_iva']]['porcentaje'] = $rowDet['iva'];
                $arrayImpuestosFactura[$rowDet['id_iva']]['total'] += $total;                		
		
	}
}



function notaServicios($idNota,&$arrayNotaServicios,&$arrayImpuestosNota){
    
    $query = sprintf("SELECT nota_cred.*
    FROM cj_cc_notacredito nota_cred 
    WHERE nota_cred.idNotaCredito = %s
            AND nota_cred.idDepartamentoNotaCredito = 1",
            valTpDato($idNota, "int"));
    $rs = mysql_query($query);
    if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
    $rowNotaCred = mysql_fetch_assoc($rs);
    
    $idFactura = $rowNotaCred['idDocumento'];
    
    // BUSCA LOS DATOS DE LA FACTURA
    $queryFactura = sprintf("SELECT
            orden.numero_orden,
            orden.idIva,
            orden.iva,             
            fact_vent.idFactura,
            fact_vent.numeroFactura,
            fact_vent.numeroControl,
            fact_vent.fechaRegistroFactura,
            fact_vent.subtotalFactura,
            fact_vent.descuentoFactura,
            fact_vent.baseImponible,
            fact_vent.porcentajeIvaFactura,
            fact_vent.calculoIvaFactura,
            0 AS montoNoGravado,
            fact_vent.porcentajeIvaDeLujoFactura,
            fact_vent.calculoIvaDeLujoFactura,
            fact_vent.montoTotalFactura
    FROM cj_cc_encabezadofactura fact_vent          
            LEFT JOIN sa_orden orden ON (fact_vent.numeroPedido = orden.id_orden)
            
    WHERE fact_vent.idFactura = %s",
            valTpDato($idFactura, "int"));
    $rsFactura = mysql_query($queryFactura);
    if (!$rsFactura) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
   
    $rowFactura = mysql_fetch_assoc($rsFactura);
	// DETALLES DE LOS REPUESTOS
        $queryRepuestosGenerales = sprintf("SELECT 
                
                sa_det_fact_articulo.cantidad,
                sa_det_fact_articulo.precio_unitario,
                sa_det_fact_articulo.id_iva,
                sa_det_fact_articulo.iva,
                sa_det_fact_articulo.id_articulo,
                sa_det_fact_articulo.id_det_fact_articulo,
                sa_det_fact_articulo.aprobado,
                sa_det_fact_articulo.id_paquete
        FROM sa_det_fact_articulo
        WHERE sa_det_fact_articulo.idFactura = %s",
                valTpDato($idFactura, "int"));
        $rsOrdenDetRep = mysql_query($queryRepuestosGenerales);
        if (!$rsOrdenDetRep) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
      
	while ($rowOrdenDetRep = mysql_fetch_assoc($rsOrdenDetRep)) {
		$cantidad = $rowOrdenDetRep['cantidad'];
		$precioUnit = ($rowOrdenDetRep['precio_unitario']);
		$total = (($rowOrdenDetRep['cantidad']*$rowOrdenDetRep['precio_unitario']));
                
                $arrayNotaServicios[$rowOrdenDetRep['id_iva']]['porcentaje'] = $rowOrdenDetRep['iva'];
                $arrayNotaServicios[$rowOrdenDetRep['id_iva']]['total'] += $total;
                
                $arrayImpuestosNota[$rowOrdenDetRep['id_iva']]['porcentaje'] = $rowOrdenDetRep['iva'];
                $arrayImpuestosNota[$rowOrdenDetRep['id_iva']]['total'] += $total;                
	}
	
        // DETALLES DE LOS TEMPARIOS
        $queryFactDetTemp = sprintf("SELECT 
                sa_det_fact_tempario.operador,
                sa_det_fact_tempario.id_tempario,
                sa_det_fact_tempario.precio,
                sa_det_fact_tempario.base_ut_precio,
                sa_det_fact_tempario.id_modo,
                (CASE sa_det_fact_tempario.id_modo
                        WHEN '1' THEN
                                sa_det_fact_tempario.ut * sa_det_fact_tempario.precio_tempario_tipo_orden / sa_det_fact_tempario.base_ut_precio
                        WHEN '2' THEN
                                sa_det_fact_tempario.precio
                        WHEN '3' THEN
                                sa_det_fact_tempario.costo
                        WHEN '4' THEN
                                '4'
                END) AS total_por_tipo_orden,
                (CASE sa_det_fact_tempario.id_modo
                        WHEN '1' THEN
                                sa_det_fact_tempario.ut
                        WHEN '2' THEN
                                sa_det_fact_tempario.precio
                        WHEN '3' THEN
                                sa_det_fact_tempario.costo
                        WHEN '4' THEN
                                '4'
                END) AS precio_por_tipo_orden,
                sa_det_fact_tempario.id_det_fact_tempario,                
                sa_det_fact_tempario.precio_tempario_tipo_orden
        FROM sa_det_fact_tempario                
                INNER JOIN cj_cc_encabezadofactura ON (sa_det_fact_tempario.idFactura = cj_cc_encabezadofactura.idFactura)
                INNER JOIN cj_cc_notacredito ON (cj_cc_encabezadofactura.idFactura = cj_cc_notacredito.idDocumento)
        WHERE cj_cc_notacredito.idNotaCredito = %s
        ORDER BY sa_det_fact_tempario.id_paquete",
                valTpDato($idNota, "int"));
        $rsFactDetTemp = mysql_query($queryFactDetTemp);
        if (!$rsFactDetTemp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
        
	
	while ($rowFactDetTemp = mysql_fetch_assoc($rsFactDetTemp)) {
		
            $precioUnit = ($rowFactDetTemp['total_por_tipo_orden']);
		$total = ($rowFactDetTemp['total_por_tipo_orden']);
		
                $arrayNotaServicios[$rowFactura['idIva']]['porcentaje'] = $rowFactura['iva'];
                $arrayNotaServicios[$rowFactura['idIva']]['total'] += $total;
                
                $arrayImpuestosNota[$rowFactura['idIva']]['porcentaje'] = $rowFactura['iva'];
                $arrayImpuestosNota[$rowFactura['idIva']]['total'] += $total;
		
	}
        
	// DETALLE DE LOS TOT
        $queryDetalleTot = sprintf("SELECT * FROM sa_orden_tot               
                INNER JOIN sa_det_fact_tot ON (sa_orden_tot.id_orden_tot = sa_det_fact_tot.id_orden_tot)
                INNER JOIN cj_cc_encabezadofactura ON (sa_det_fact_tot.idFactura = cj_cc_encabezadofactura.idFactura)
                INNER JOIN cj_cc_notacredito ON (cj_cc_encabezadofactura.idFactura = cj_cc_notacredito.idDocumento)
                INNER JOIN cp_factura ON sa_orden_tot.id_factura = cp_factura.id_factura
        WHERE cj_cc_notacredito.idNotaCredito = %s",
                valTpDato($idNota, "int"));
        
        $rsDetalleTot = mysql_query($queryDetalleTot);
        if (!$rsDetalleTot) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
        
	while ($rowDetalleTot = mysql_fetch_assoc($rsDetalleTot)) {
				
		$total = ($rowDetalleTot['monto_subtotal']+($rowDetalleTot['monto_subtotal']*$rowDetalleTot['porcentaje_tot']/100));
                
                $arrayNotaServicios[$rowFactura['idIva']]['porcentaje'] = $rowFactura['iva'];
                $arrayNotaServicios[$rowFactura['idIva']]['total'] += $total;
                
                $arrayImpuestosNota[$rowFactura['idIva']]['porcentaje'] = $rowFactura['iva'];
                $arrayImpuestosNota[$rowFactura['idIva']]['total'] += $total;
                	
	}
		
	// DETALLES DE LAS NOTAS
        $queryDetTipoDocNotas = sprintf("SELECT 
                sa_det_fact_notas.id_det_fact_nota AS idDetNota,
                sa_det_fact_notas.descripcion_nota,
                sa_det_fact_notas.precio
        FROM cj_cc_encabezadofactura
                INNER JOIN sa_det_fact_notas ON (cj_cc_encabezadofactura.idFactura = sa_det_fact_notas.idFactura)
                INNER JOIN cj_cc_notacredito ON (cj_cc_encabezadofactura.idFactura = cj_cc_notacredito.idDocumento)
        WHERE cj_cc_notacredito.idNotaCredito = %s",
                valTpDato($idDocumento, "int"));

        $rsDetTipoDocNotas = mysql_query($queryDetTipoDocNotas);
        if (!$rsDetTipoDocNotas) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
        $totalRowsDetTipoDocNotas = mysql_num_rows($rsDetTipoDocNotas);
	
	//$xmlItemsNota .= "<notas>";
	while ($rowDetTipoDocNotas = mysql_fetch_assoc($rsDetTipoDocNotas)) {
		
		$precioUnit = ($rowDetTipoDocNotas['precio']);
		$total = ($rowDetTipoDocNotas['precio']);
                
                $arrayNotaServicios[$rowFactura['idIva']]['porcentaje'] = $rowFactura['iva'];
                $arrayNotaServicios[$rowFactura['idIva']]['total'] += $total;
                
                $arrayImpuestosNota[$rowFactura['idIva']]['porcentaje'] = $rowFactura['iva'];
                $arrayImpuestosNota[$rowFactura['idIva']]['total'] += $total;
                	
	}
}

function notaRepuestos($idNota,&$arrayNotaRepuestos,&$arrayImpuestosNota){
    
    // BUSCA LOS DATOS DE LA NOTA DE CREDITO
        $queryNotaCred = sprintf("SELECT nota_cred.*
        FROM cj_cc_notacredito nota_cred 
        WHERE nota_cred.idNotaCredito = %s
                AND nota_cred.idDepartamentoNotaCredito = 0",
                valTpDato($idNota, "int"));
        $rsNotaCred = mysql_query($queryNotaCred);
        if (!$rsNotaCred) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
        $rowNotaCred = mysql_fetch_assoc($rsNotaCred);
        
        $idFactura = $rowNotaCred['idDocumento'];
		
	// DETALLES DE LOS REPUESTOS
	$queryDetalle = sprintf("SELECT
		fact_vent_det.cantidad,
		fact_vent_det.precio_unitario,
		fact_vent_det.id_iva,
		fact_vent_det.iva,
		fact_vent_det.id_articulo,
		fact_vent_det.id_factura_detalle
	FROM cj_cc_factura_detalle fact_vent_det
	WHERE fact_vent_det.id_factura = %s",
		valTpDato($idFactura, "int"));
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) die(mysql_error()."<br><br>Line: ".__LINE__);
	
	
	while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
		$total = $rowDetalle['cantidad'] * $rowDetalle['precio_unitario'];
		
		$arrayNotaRepuestos[$rowDetalle['id_iva']]['porcentaje'] = $rowDetalle['iva'];
                $arrayNotaRepuestos[$rowDetalle['id_iva']]['total'] += $total;
                
                $arrayImpuestosNota[$rowDetalle['id_iva']]['porcentaje'] = $rowDetalle['iva'];
                $arrayImpuestosNota[$rowDetalle['id_iva']]['total'] += $total;
	}
        
}

function notaVehiculos($idNota,&$arrayNotaVehiculos,&$arrayImpuestosNota){
    $queryDcto = sprintf("SELECT
                nota_cred.id_empresa,
                nota_cred.numeracion_nota_credito,
                nota_cred.idDepartamentoNotaCredito,
                nota_cred.idNotaCredito,
                nota_cred.idDocumento,
                nota_cred.numeroControl,
                fact_vent.numeroFactura,
                fact_vent.idFactura,
                
                fact_vent.consecutivo_fiscal,
                fact_vent.serial_impresora,
                fact_vent.fecha_impresora,
                fact_vent.hora_impresora,
                nota_cred.fechaNotaCredito,
                nota_cred.observacionesNotaCredito,
                nota_cred.subtotalNotaCredito AS subtotal_nota_credito,
                nota_cred.porcentaje_descuento,
                nota_cred.subtotal_descuento,
                nota_cred.baseimponibleNotaCredito AS base_imponible_nota_credito,
                fact_vent.porcentajeIvaFactura AS porcentaje_iva,
                nota_cred.ivaNotaCredito AS subtotal_iva,
                fact_vent.porcentajeIvaDeLujoFactura AS porcentaje_iva_lujo,
                fact_vent.observacionFactura,
                nota_cred.ivaLujoNotaCredito AS subtotal_iva_lujo,
                nota_cred.montoExentoCredito AS monto_exento,
                nota_cred.montoExoneradoCredito AS monto_exonerado
        FROM cj_cc_notacredito nota_cred
                INNER JOIN cj_cc_encabezadofactura fact_vent ON (nota_cred.idDocumento = fact_vent.idFactura)              
        WHERE nota_cred.idNotaCredito = %s
                AND nota_cred.idDepartamentoNotaCredito = 2",
                valTpDato($idNota, "int"));
        $rsDcto = mysql_query($queryDcto);
        if (!$rsDcto) die(mysql_error()."<br><br>Line: ".__LINE__);
       
	// BUSCA LOS DATOS DE LA UNIDAD
        $queryUnidad = sprintf("SELECT 
                uni_bas.nom_uni_bas,
                marca.nom_marca,
                modelo.nom_modelo,
                vers.nom_version,
                ano.nom_ano,
                uni_fis.placa,
                uni_fis.serial_carroceria,
                uni_fis.serial_motor,
                color1.nom_color AS color_externo,
                nota_cred_det_vehic.precio_unitario,
                nota_cred_det_vehic.id_nota_credito_detalle_vehiculo,
                uni_bas.com_uni_bas,
                codigo_unico_conversion,
                marca_kit,
                marca_cilindro,
                modelo_regulador,
                serial1,
                serial_regulador,
                capacidad_cilindro,
                fecha_elaboracion_cilindro
        FROM cj_cc_nota_credito_detalle_vehiculo nota_cred_det_vehic
                INNER JOIN an_unidad_fisica uni_fis ON (nota_cred_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
                INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
                INNER JOIN an_marca marca ON (uni_bas.mar_uni_bas = marca.id_marca)
                INNER JOIN an_modelo modelo ON (uni_bas.mod_uni_bas = modelo.id_modelo)
                INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
                INNER JOIN an_ano ano ON (uni_fis.ano = ano.id_ano)
                INNER JOIN an_color color1 ON (uni_fis.id_color_externo1 = color1.id_color)
        WHERE nota_cred_det_vehic.id_nota_credito = %s",
                valTpDato($idNota, "int"));
        $rsUnidad = mysql_query($queryUnidad);
        if (!$rsUnidad) die(mysql_error()."<br><br>Line: ".__LINE__);
        $totalRowsUnidad = mysql_num_rows($rsUnidad);
        $rowUnidad = mysql_fetch_array($rsUnidad);
        	
	if ($totalRowsUnidad > 0) {
		                
                //1 factura por vehiculo, cada vehiculo puede poseer multiples impuestos al mismo tiempo
                $queryImpuestoVehiculo = sprintf("SELECT id_impuesto, impuesto FROM cj_cc_factura_detalle_vehiculo_impuesto"
                        . " WHERE id_factura_detalle_vehiculo = %s",
                        $rowUnidad['id_nota_credito_detalle_vehiculo']);
                
                $rsImpuestoUnidad = mysql_query($queryImpuestoVehiculo);
                if (!$rsImpuestoUnidad) die(mysql_error()."<br><br>Line: ".__LINE__);
                $tieneImpuesto = mysql_num_rows($rsUnidad);
                 
                if($tieneImpuesto){                   
                    
                    while ($rowImpuestoUnidad = mysql_fetch_assoc($rsImpuestoUnidad)){
                        $arrayNotaVehiculos[$rowImpuestoUnidad['id_impuesto']]['porcentaje'] = $rowImpuestoUnidad['impuesto'];
                        $arrayNotaVehiculos[$rowImpuestoUnidad['id_impuesto']]['total'] += $rowUnidad['precio_unitario'];

                        $arrayImpuestosNota[$rowImpuestoUnidad['id_impuesto']]['porcentaje'] = $rowImpuestoUnidad['impuesto'];
                        $arrayImpuestosNota[$rowImpuestoUnidad['id_impuesto']]['total'] += $rowUnidad['precio_unitario'];
                    }
                    
                }
                    
	}
	
	
	$queryDet = sprintf("SELECT
                nota_cred_det_acc.id_nota_credito_detalle_accesorios,
                nota_cred_det_acc.id_accesorio,
                nota_cred_det_acc.costo_compra,
                nota_cred_det_acc.cantidad,
                nota_cred_det_acc.precio_unitario,
                nota_cred_det_acc.id_iva,
                nota_cred_det_acc.iva,
                (CASE
                        WHEN nota_cred_det_acc.id_iva = 0 THEN
                                CONCAT(acc.nom_accesorio, ' (E)')
                        ELSE
                                acc.nom_accesorio
                END) AS nom_accesorio,
                nota_cred_det_acc.tipo_accesorio
        FROM cj_cc_nota_credito_detalle_accesorios nota_cred_det_acc
                INNER JOIN an_accesorio acc ON (nota_cred_det_acc.id_accesorio = acc.id_accesorio)
        WHERE nota_cred_det_acc.id_nota_credito = %s",
                valTpDato($idNota, "int"));
        $rsDet = mysql_query($queryDet);
        if (!$rsDet) die(mysql_error()."<br><br>Line: ".__LINE__);

	while ($rowDet = mysql_fetch_array($rsDet)) {
		
            $total = $rowDet['cantidad'] * $rowDet['precio_unitario'];
                   
            $arrayNotaVehiculos[$rowDet['id_iva']]['porcentaje'] = $rowDet['iva'];
            $arrayNotaVehiculos[$rowDet['id_iva']]['total'] += $total;

            $arrayImpuestosNota[$rowDet['id_iva']]['porcentaje'] = $rowDet['iva'];
            $arrayImpuestosNota[$rowDet['id_iva']]['total'] += $total;   
            		
	}
}

?>