<?php
function cargaLstEmpresas($selId = "") {
	$objResponse = new xajaxResponse();
			
	$query = sprintf("SELECT DISTINCT id_empresa, nombre_empresa FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY nombre_empresa",
		valTpDato($_SESSION['idUsuarioSysGts'],"int"));
			
	$rs = mysql_query($query) or die(mysql_error());
	$html = "<select id=\"lstEmpresa\" name=\"lstEmpresa\">";// onchange=\"$('btnBuscar').click();\"
		$html .= "<option value=\"\">Todos...</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['id_empresa'])
			$selected = "selected='selected'";
						
		$html .= "<option ".$selected." value=\"".$row['id_empresa']."\">".utf8_encode($row['nombre_empresa'])."</option>";
		
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEmpresa","innerHTML",$html);
	
	return $objResponse;
}

function cargarSelectEmpleados($tipoEmpleado, $idEmpresa = ""){
	$objResponse = new xajaxResponse();
	
	if($idEmpresa == ""){
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];	
	}
	
	if ($tipoEmpleado > -1){
		//ARREGLO CON LAS CLAVES DE FILTRO DE LOS EMPLEADOS
		$arrayEmpleado = array ("501","6","5","300","4");
				
		if ($tipoEmpleado == 0)
			$objResponse->script("$('trRadio').style.display = '';");
		else
			$objResponse->script("$('trRadio').style.display = 'none';");
		
		$claveFiltro = explode("|",$arrayEmpleado[$tipoEmpleado]);
		
		foreach ($claveFiltro as $indiceClaveFiltro => $valorClaveFiltro){
			if ($condicion == ""){
				$condicion = "WHERE (vw_sa_empleado_clave_filtro.clave_filtro = ".$valorClaveFiltro;
			}
			else{
				$condicion .= " OR vw_sa_empleado_clave_filtro.clave_filtro = ".$valorClaveFiltro;
			}
		}
		
		$condicion.= ")";
		
		$queryEmpleados = sprintf("SELECT * FROM vw_sa_empleado_clave_filtro 
								   LEFT JOIN pg_cargo_departamento ON vw_sa_empleado_clave_filtro.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento
								   LEFT JOIN pg_departamento ON pg_cargo_departamento.id_departamento = pg_departamento.id_departamento
								   
									%s AND activo = 1 AND pg_departamento.id_empresa = ".$idEmpresa." ",$condicion);
		//return $objResponse->alert($queryEmpleados);
		$rsEmpleados = mysql_query($queryEmpleados) or die(mysql_error());
		$html = "<select id=\"selEmpleado\" name=\"selEmpleado\">";
			$html .= "<option value=\"0\">Todos...</option>";
			while($rowEmpleado = mysql_fetch_array($rsEmpleados)){
				$html .= "<option value=\"".$rowEmpleado['id_empleado']."\">".utf8_encode($rowEmpleado['nombre_empleado'])." ".utf8_encode($rowEmpleado['apellido'])."</option>";
			}
		$html .= "</select>";
	}
	else{
		$html = "<select id=\"selEmpleado\" name=\"selEmpleado\" disabled=\"disabled\">";
			$html .= "<option value=\"0\">Todos...</option>";
		$html .= "</select>";
		$objResponse->script("$('trRadio').style.display = 'none';");
	}
	$objResponse->assign("tdSelEmpleado","innerHTML",$html);
	$objResponse->assign("tdComisiones","innerHTML","");
	
	return $objResponse;
}

function listarComisionesMecanicos($idMecanico,$tipoListado,$fechaDesde, $fechaHasta){
	$objResponse = new xajaxResponse();
	
	$fechaFormatoDesde = date("Y-m-d",strtotime($fechaDesde));
	$fechaFormatoHasta = date("Y-m-d",strtotime($fechaHasta));
	
	if (!xvalidaAcceso($objResponse,PAGE_PRIV)){
		$objResponse->assign("tdComisiones","innerHTML","Acceso Denegado");
		return $objResponse;
	}
	
	if ($idMecanico == 0)
		$condicionMecanico = "";
	else
		$condicionMecanico = " AND pg_comision_empleado.id_empleado = ".$idMecanico;
	
	//AGRUPAR POR EL TIPO DE ORDEN
	$queryTipoOrden = sprintf("SELECT
									(SELECT id_tipo_orden FROM sa_orden WHERE id_orden = (SELECT numeroPedido FROM cj_cc_encabezadofactura WHERE idFactura = id_factura)) AS id_tipo_orden,
									(SELECT nombre_tipo_orden FROM sa_tipo_orden WHERE sa_tipo_orden. id_tipo_orden =  (SELECT id_tipo_orden FROM sa_orden WHERE id_orden = (SELECT numeroPedido FROM cj_cc_encabezadofactura WHERE idFactura = id_factura))) AS nombre_tipo_orden
								FROM
									pg_comision_empleado
								WHERE
									((SELECT fechaRegistroFactura FROM cj_cc_encabezadofactura WHERE idFactura = id_factura) BETWEEN '%s' AND '%s') 
									AND
								   (SELECT clave_filtro FROM pg_cargo_departamento WHERE pg_cargo_departamento.id_cargo_departamento = (SELECT id_cargo_departamento FROM pg_empleado WHERE pg_empleado.id_empleado = pg_comision_empleado.id_empleado)) = 501
									AND
									((SELECT idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura WHERE idFactura = id_factura) = 1)
									%s
								
								UNION
								
								SELECT
									 (SELECT id_tipo_orden FROM sa_orden WHERE id_orden = (SELECT numeroPedido FROM cj_cc_encabezadofactura WHERE idFactura = (SELECT idDocumento FROM cj_cc_notacredito WHERE cj_cc_notacredito.idNotaCredito = pg_comision_empleado.id_nota_credito))) AS id_tipo_orden,
									(SELECT nombre_tipo_orden FROM sa_tipo_orden WHERE sa_tipo_orden. id_tipo_orden =  (SELECT id_tipo_orden FROM sa_orden WHERE id_orden = (SELECT numeroPedido FROM cj_cc_encabezadofactura WHERE idFactura = (SELECT idDocumento FROM cj_cc_notacredito WHERE cj_cc_notacredito.idNotaCredito = pg_comision_empleado.id_nota_credito)))) AS nombre_tipo_orden
								FROM
									pg_comision_empleado
								WHERE
									 ((SELECT fechaNotaCredito FROM cj_cc_notacredito WHERE cj_cc_notacredito.idNotaCredito = pg_comision_empleado.id_nota_credito) BETWEEN '%s' AND '%s')  
									AND
								   (SELECT clave_filtro FROM pg_cargo_departamento WHERE pg_cargo_departamento.id_cargo_departamento = (SELECT id_cargo_departamento FROM pg_empleado WHERE pg_empleado.id_empleado = pg_comision_empleado.id_empleado)) = 501
									AND
									((SELECT idDepartamentoNotaCredito FROM cj_cc_notacredito WHERE cj_cc_notacredito.idNotaCredito = pg_comision_empleado.id_nota_credito) = 1) 
									%s
								
								UNION
								
								SELECT
									(SELECT id_tipo_orden FROM sa_orden WHERE sa_orden.id_orden = (SELECT id_orden FROM sa_vale_salida WHERE sa_vale_salida.id_vale_salida = pg_comision_empleado.id_vale_salida)) AS id_tipo_orden,
									(SELECT nombre_tipo_orden FROM sa_tipo_orden WHERE sa_tipo_orden. id_tipo_orden =  (SELECT id_tipo_orden FROM sa_orden WHERE sa_orden.id_orden = (SELECT id_orden FROM sa_vale_salida WHERE sa_vale_salida.id_vale_salida = pg_comision_empleado.id_vale_salida))) AS nombre_tipo_orden
								FROM
									pg_comision_empleado
								WHERE
									((SELECT fecha_vale FROM sa_vale_salida WHERE sa_vale_salida.id_vale_salida = pg_comision_empleado.id_vale_salida) BETWEEN '%s' AND '%s') 
									AND
								   (SELECT clave_filtro FROM pg_cargo_departamento WHERE pg_cargo_departamento.id_cargo_departamento = (SELECT id_cargo_departamento FROM pg_empleado WHERE pg_empleado.id_empleado = pg_comision_empleado.id_empleado)) = 501
								   %s
								
								GROUP BY
								1",
						$fechaFormatoDesde,
						$fechaFormatoHasta,
						$condicionMecanico,
						$fechaFormatoDesde,
						$fechaFormatoHasta,
						$condicionMecanico,
						$fechaFormatoDesde."  00:00:00",
						$fechaFormatoHasta."  23:59:59",
						$condicionMecanico);
	$rsLimitTipoOrden = mysql_query($queryTipoOrden);
	
	if (!$rsLimitTipoOrden) return $objResponse->alert(mysql_error());
	
	$htmlTableIni .= "<table border=\"1\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
	
	if (mysql_num_rows($rsLimitTipoOrden) > 0){
		while ($rowTipoOrden = mysql_fetch_assoc($rsLimitTipoOrden)){
			$total_por_tipo_orden = 0;

			if ($tipoListado)
				$html .= "<tr>
							<td align=\"right\" class='trResaltar6 textoNegrita_12px' colspan=\"1\" width='12%'>Tipo Orden:</a></td>
							<td align='left' colspan=\"7\" width='89%'>".utf8_encode($rowTipoOrden['nombre_tipo_orden'])."</td>
						  </tr>";
	
			//AGRUPAR POR MECANICO
			$queryMecanico = sprintf("SELECT
										pg_comision_empleado.id_empleado
									FROM
										pg_comision_empleado
									WHERE
										((SELECT fechaRegistroFactura FROM cj_cc_encabezadofactura WHERE idFactura = id_factura) BETWEEN '%s' AND '%s') 
										AND
										(SELECT clave_filtro FROM pg_cargo_departamento WHERE pg_cargo_departamento.id_cargo_departamento = (SELECT id_cargo_departamento FROM pg_empleado WHERE pg_empleado.id_empleado = pg_comision_empleado.id_empleado)) = 501
										AND
										((SELECT idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura WHERE idFactura = id_factura) = 1)
										AND
										(SELECT id_tipo_orden FROM sa_orden WHERE id_orden = (SELECT numeroPedido FROM cj_cc_encabezadofactura WHERE idFactura = id_factura))  = %s
										%s
									
									
									UNION
									
									SELECT
										pg_comision_empleado.id_empleado
									FROM
										pg_comision_empleado
									WHERE
										((SELECT fechaNotaCredito FROM cj_cc_notacredito WHERE cj_cc_notacredito.idNotaCredito = pg_comision_empleado.id_nota_credito) BETWEEN '%s' AND '%s')  
										AND
										(SELECT clave_filtro FROM pg_cargo_departamento WHERE pg_cargo_departamento.id_cargo_departamento = (SELECT id_cargo_departamento FROM pg_empleado WHERE pg_empleado.id_empleado = pg_comision_empleado.id_empleado)) = 501
										AND
										((SELECT idDepartamentoNotaCredito FROM cj_cc_notacredito WHERE cj_cc_notacredito.idNotaCredito = pg_comision_empleado.id_nota_credito) = 1) 
										AND
										(SELECT id_tipo_orden FROM sa_orden WHERE id_orden = (SELECT numeroPedido FROM cj_cc_encabezadofactura WHERE idFactura = (SELECT idDocumento FROM cj_cc_notacredito WHERE cj_cc_notacredito.idNotaCredito = pg_comision_empleado.id_nota_credito))) = %s
										%s
										
									UNION
									
									SELECT
										pg_comision_empleado.id_empleado
									FROM
										pg_comision_empleado
									WHERE
										((SELECT fecha_vale FROM sa_vale_salida WHERE sa_vale_salida.id_vale_salida = pg_comision_empleado.id_vale_salida) BETWEEN '%s' AND '%s') 
										AND
										(SELECT clave_filtro FROM pg_cargo_departamento WHERE pg_cargo_departamento.id_cargo_departamento = (SELECT id_cargo_departamento FROM pg_empleado WHERE pg_empleado.id_empleado = pg_comision_empleado.id_empleado)) = 501
										AND
										(SELECT id_tipo_orden FROM sa_orden WHERE sa_orden.id_orden = (SELECT id_orden FROM sa_vale_salida WHERE sa_vale_salida.id_vale_salida = pg_comision_empleado.id_vale_salida)) = %s
										%s
										
									GROUP BY
									1",
									 $fechaFormatoDesde,$fechaFormatoHasta,$rowTipoOrden['id_tipo_orden'],$condicionMecanico,
									 $fechaFormatoDesde,$fechaFormatoHasta,$rowTipoOrden['id_tipo_orden'],$condicionMecanico,
									 $fechaFormatoDesde."  00:00:00",$fechaFormatoHasta."  23:59:59",$rowTipoOrden['id_tipo_orden'],$condicionMecanico);
			$rsMecanico = mysql_query($queryMecanico);
			
			if (!$rsMecanico) return $objResponse->alert(mysql_error());
			
			if (mysql_num_rows($rsMecanico) > 0){
				while ($rowMecanico = mysql_fetch_array($rsMecanico)){
					$indiceArreglos = 0;
					
					while ($indiceArreglos < count($arrayIdEmpleado) && $arrayIdEmpleado[$indiceArreglos] != $rowMecanico['id_empleado'])
						$indiceArreglos++;
					
					if ($indiceArreglos == count($arrayIdEmpleado))
						$arrayIdEmpleado[] = $rowMecanico['id_empleado'];
					
					//AGRUPAR POR NUMERO DE ORDEN
					$queryNumeroOrden = sprintf("SELECT
													(SELECT id_empleado FROM sa_orden WHERE id_orden = (SELECT numeroPedido FROM cj_cc_encabezadofactura WHERE idFactura = id_factura)) AS id_asesor,
													'FA' as tipo_documento,
													(SELECT CONCAT_WS(' ', nombre_empleado, apellido) FROM pg_empleado WHERE pg_empleado.id_empleado = pg_comision_empleado.id_empleado) AS nombre_completo_empleado,
													(SELECT CONCAT_WS(' ', nombre, apellido) FROM cj_cc_cliente WHERE cj_cc_cliente.id = (SELECT idCliente FROM cj_cc_encabezadofactura WHERE idFactura = id_factura)) AS nombre_completo_cliente,
													(SELECT numeroPedido FROM cj_cc_encabezadofactura WHERE idFactura = id_factura) AS id_orden,
													(SELECT numero_orden FROM sa_orden WHERE id_orden = (SELECT numeroPedido FROM cj_cc_encabezadofactura WHERE idFactura = id_factura)) AS numero_orden,
													(SELECT tiempo_orden FROM sa_orden WHERE id_orden = (SELECT numeroPedido FROM cj_cc_encabezadofactura WHERE idFactura = id_factura)) AS fecha_orden,
													(SELECT numeroFactura FROM cj_cc_encabezadofactura WHERE idFactura = id_factura) AS id_factura,
													(SELECT fechaRegistroFactura FROM cj_cc_encabezadofactura WHERE idFactura = id_factura) AS fechaRegistroFactura
												FROM
													pg_comision_empleado
												WHERE
													pg_comision_empleado.id_empleado = %s
													AND
													((SELECT fechaRegistroFactura FROM cj_cc_encabezadofactura WHERE idFactura = id_factura) BETWEEN '%s' AND '%s') 
													AND
													(SELECT id_tipo_orden FROM sa_orden WHERE id_orden = (SELECT numeroPedido FROM cj_cc_encabezadofactura WHERE idFactura = id_factura))  = %s
													AND
													((SELECT idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura WHERE idFactura = id_factura) = 1)


												UNION

												SELECT
													(SELECT id_empleado FROM sa_orden WHERE id_orden = (SELECT numeroPedido FROM cj_cc_encabezadofactura WHERE idFactura = id_factura)) AS id_asesor,
													'NC' as tipo_documento,
													(SELECT CONCAT_WS(' ', nombre_empleado, apellido) FROM pg_empleado WHERE pg_empleado.id_empleado = pg_comision_empleado.id_empleado) AS nombre_completo_empleado,
													(SELECT CONCAT_WS(' ', nombre, apellido) FROM cj_cc_cliente WHERE cj_cc_cliente.id = (SELECT idCliente FROM cj_cc_encabezadofactura WHERE idFactura = id_factura)) AS nombre_completo_cliente,
													(SELECT numeroPedido FROM cj_cc_encabezadofactura WHERE idFactura = id_factura) AS id_orden,
													(SELECT numero_orden FROM sa_orden WHERE id_orden = (SELECT numeroPedido FROM cj_cc_encabezadofactura WHERE idFactura = id_factura)) AS numero_orden,
													(SELECT tiempo_orden FROM sa_orden WHERE id_orden = (SELECT numeroPedido FROM cj_cc_encabezadofactura WHERE idFactura = id_factura)) AS fecha_orden,
													(SELECT numeracion_nota_credito FROM cj_cc_notacredito WHERE idNotaCredito = id_nota_credito) AS id_factura,
													(SELECT fechaNotaCredito FROM cj_cc_notacredito WHERE idNotaCredito = id_nota_credito) AS fechaRegistroFactura
												FROM
													pg_comision_empleado
												WHERE
													pg_comision_empleado.id_empleado = %s
													AND
													((SELECT fechaNotaCredito FROM cj_cc_notacredito WHERE cj_cc_notacredito.idNotaCredito = pg_comision_empleado.id_nota_credito) BETWEEN '%s' AND '%s') 
													AND
													(SELECT id_tipo_orden FROM sa_orden WHERE id_orden = (SELECT numeroPedido FROM cj_cc_encabezadofactura WHERE idFactura = (SELECT idDocumento FROM cj_cc_notacredito WHERE cj_cc_notacredito.idNotaCredito = pg_comision_empleado.id_nota_credito))) = %s
													AND
													((SELECT idDepartamentoNotaCredito FROM cj_cc_notacredito WHERE cj_cc_notacredito.idNotaCredito = pg_comision_empleado.id_nota_credito) = 1) 

												UNION

												SELECT
													(SELECT id_empleado FROM sa_orden WHERE sa_orden.id_orden = (SELECT id_orden FROM sa_vale_salida WHERE sa_vale_salida.id_vale_salida = pg_comision_empleado.id_vale_salida)) AS id_asesor,
													'VS' as tipo_documento,
													(SELECT CONCAT_WS(' ', nombre_empleado, apellido) FROM pg_empleado WHERE pg_empleado.id_empleado = pg_comision_empleado.id_empleado) AS nombre_completo_empleado,
													(SELECT CONCAT_WS(' ', nombre, apellido) FROM cj_cc_cliente WHERE cj_cc_cliente.id = (SELECT id_cliente_contacto FROM sa_cita WHERE id_cita = (SELECT id_cita FROM sa_recepcion WHERE id_recepcion = (SELECT id_recepcion FROM sa_orden WHERE sa_orden.id_orden = (SELECT id_orden FROM sa_vale_salida WHERE sa_vale_salida.id_vale_salida = pg_comision_empleado.id_vale_salida))))) AS nombre_completo_cliente,
													(SELECT id_orden FROM sa_vale_salida WHERE sa_vale_salida.id_vale_salida = pg_comision_empleado.id_vale_salida) AS id_orden,
													(SELECT numero_orden FROM sa_orden WHERE id_orden = (SELECT id_orden FROM sa_vale_salida WHERE sa_vale_salida.id_vale_salida = pg_comision_empleado.id_vale_salida)) AS numero_orden,
													(SELECT tiempo_orden FROM sa_orden WHERE id_orden = (SELECT id_orden FROM sa_vale_salida WHERE sa_vale_salida.id_vale_salida = pg_comision_empleado.id_vale_salida)) AS fecha_orden,
													(SELECT numero_vale FROM sa_vale_salida WHERE sa_vale_salida.id_vale_salida = pg_comision_empleado.id_vale_salida) AS id_factura,
													(SELECT fecha_vale FROM sa_vale_salida WHERE sa_vale_salida.id_vale_salida = pg_comision_empleado.id_vale_salida) AS fechaRegistroFactura
												FROM
													pg_comision_empleado
												WHERE
													pg_comision_empleado.id_empleado = %s
													AND
													((SELECT fecha_vale FROM sa_vale_salida WHERE sa_vale_salida.id_vale_salida = pg_comision_empleado.id_vale_salida) BETWEEN '%s' AND '%s') 
													AND
													(SELECT id_tipo_orden FROM sa_orden WHERE sa_orden.id_orden = (SELECT id_orden FROM sa_vale_salida WHERE sa_vale_salida.id_vale_salida = pg_comision_empleado.id_vale_salida)) = %s
												
												GROUP BY
												5",
										$rowMecanico['id_empleado'],
										$fechaFormatoDesde,$fechaFormatoHasta,
										$rowTipoOrden['id_tipo_orden'],
										$rowMecanico['id_empleado'],
										$fechaFormatoDesde,$fechaFormatoHasta,
										$rowTipoOrden['id_tipo_orden'],
										$rowMecanico['id_empleado'],
										$fechaFormatoDesde."  00:00:00",$fechaFormatoHasta."  23:59:59",
										$rowTipoOrden['id_tipo_orden']);
					$rsNumeroOrden = mysql_query($queryNumeroOrden);
					
					if (!$rsNumeroOrden) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					
					while ($rowNumeroOrden = mysql_fetch_array($rsNumeroOrden)){
						$queryAsesor = "SELECT nombre_empleado as nombre_asesor, apellido as apellido_asesor FROM pg_empleado WHERE id_empleado = ".$rowNumeroOrden['id_asesor'];
						$rsAsesor = mysql_query($queryAsesor);
						
						if (!$rsAsesor) return $objResponse->alert(mysql_error());
						
						$rowAsesor = mysql_fetch_array($rsAsesor);
						//YA SE VAN A IMPRIMIR POR ORDEN
							if($tipoListado){
								if ($rowNumeroOrden['tipo_documento'] == "FA")
									$tipoDocumento = "Factura";
									else if ($rowNumeroOrden['tipo_documento'] == "VS")
									$tipoDocumento = "Vale Salida";
								else
									$tipoDocumento = "Nota Credito";
								
								$html .= "<tr>
										<td align=\"right\" class='tituloColumna' width='12%'>Mecanico:</a></td>
										<td align='left' colspan=\"1\" width='25%'>".utf8_encode($rowNumeroOrden['nombre_completo_empleado'])."</td>
										<td align=\"right\" class='tituloColumna'>Asesor:</a></td>
										<td align='left' colspan=\"2\">".utf8_encode($rowAsesor['nombre_asesor'])." ".utf8_encode($rowAsesor['apellido_asesor'])."</td>
										<td align=\"right\" class='tituloColumna'  width='5%'>Cliente:</a></td>
										<td align='left' colspan=\"2\">".utf8_encode($rowNumeroOrden['nombre_completo_cliente'])."</td>
									</tr>
									<tr>
										<td align=\"right\" class='tituloColumna'>Orden:</td>
										<td align='left' idOrdenOculta ='".$rowNumeroOrden['id_orden']."'>".$rowNumeroOrden['numero_orden']."</td>
										<td align=\"right\" class='tituloColumna'>Fecha Orden:</td>
										<td align='left'>".date("d-m-Y",strtotime($rowNumeroOrden['fecha_orden']))."</td>
										<td align=\"right\" class='tituloColumna'>".$tipoDocumento.":</td>
										<td align='left'>".$rowNumeroOrden['id_factura']."</td>
										<td align=\"right\" class='tituloColumna' width='12%'>Fecha ".$tipoDocumento.":</td>
										<td align='left'>".date("d-m-Y",strtotime($rowNumeroOrden['fechaRegistroFactura']))."</td>
									</tr>";
								
									$html .= "<tr class=\"tituloColumna\">
													<td width='12%'>Cod. Tem.</td>
													<td width='25%'>Descripci&oacute;n Tempario</td>
													<td width='10%'>UT</td>
													<td width='10%'>Valor M.O.</td>
													<td width='10%'>Precio</td>
													<td width='5%'>Iva</td>
													<td width='10%'>% Comision</td>
													<td width='10%'>Comision</td>
												</tr>";
							}
						
						if ($rowNumeroOrden['tipo_documento'] == 'FA'){
							$queryDetalle = sprintf("SELECT							
														IFNULL((SELECT sa_tempario.codigo_tempario FROM sa_tempario WHERE sa_tempario.id_tempario = pg_comision_empleado_detalle.id_tempario),'-') AS codigo_tempario,
														IFNULL((SELECT sa_tempario.descripcion_tempario FROM sa_tempario WHERE sa_tempario.id_tempario = pg_comision_empleado_detalle.id_tempario),'-') AS descripcion_tempario,
														pg_comision_empleado_detalle.ut,
														pg_comision_empleado_detalle.precio_tempario,
														pg_comision_empleado_detalle.base_ut_precio,
														'0' AS iva,
														pg_comision_empleado_detalle.porcentaje_comision,
														pg_comision_empleado_detalle.id_tempario,
														pg_comision_empleado_detalle.monto_comision,
														pg_comision_empleado_detalle.precio_venta
													FROM
														pg_comision_empleado_detalle
													WHERE
														pg_comision_empleado_detalle.id_comision_empleado = (SELECT id_comision_empleado FROM pg_comision_empleado WHERE pg_comision_empleado.id_factura = (SELECT idFactura FROM cj_cc_encabezadofactura WHERE cj_cc_encabezadofactura.numeroPedido = %s AND cj_cc_encabezadofactura.idDepartamentoOrigenFactura = 1) AND pg_comision_empleado.id_empleado = %s AND id_nota_credito  IS NULL)",
										$rowNumeroOrden['id_orden'],
										$rowMecanico['id_empleado']);
							
						}
						else if($rowNumeroOrden['tipo_documento'] == 'NC'){
							$queryDetalle = sprintf("SELECT							
														IFNULL((SELECT sa_tempario.codigo_tempario FROM sa_tempario WHERE sa_tempario.id_tempario = pg_comision_empleado_detalle.id_tempario),'-') AS codigo_tempario,
														IFNULL((SELECT sa_tempario.descripcion_tempario FROM sa_tempario WHERE sa_tempario.id_tempario = pg_comision_empleado_detalle.id_tempario),'-') AS descripcion_tempario,
														pg_comision_empleado_detalle.ut,
														pg_comision_empleado_detalle.precio_tempario,
														pg_comision_empleado_detalle.base_ut_precio,
														'0' AS iva,
														pg_comision_empleado_detalle.porcentaje_comision,
														pg_comision_empleado_detalle.id_tempario,
														pg_comision_empleado_detalle.monto_comision,
														pg_comision_empleado_detalle.precio_venta
													FROM
														pg_comision_empleado_detalle
													WHERE
														pg_comision_empleado_detalle.id_comision_empleado = (SELECT id_comision_empleado FROM pg_comision_empleado WHERE pg_comision_empleado.id_nota_credito = (SELECT idNotaCredito FROM cj_cc_notacredito WHERE tipoDocumento LIKE 'FA' AND idDocumento = (SELECT idFactura FROM cj_cc_encabezadofactura WHERE cj_cc_encabezadofactura.numeroPedido = %s AND cj_cc_encabezadofactura.idDepartamentoOrigenFactura = 1)) AND pg_comision_empleado.id_empleado = %s AND id_nota_credito IS NOT NULL )",
													$rowNumeroOrden['id_orden'],
													$rowMecanico['id_empleado']);
						}
						else if($rowNumeroOrden['tipo_documento'] == 'VS'){
							$queryDetalle = sprintf("SELECT							
														IFNULL((SELECT sa_tempario.codigo_tempario FROM sa_tempario WHERE sa_tempario.id_tempario = pg_comision_empleado_detalle.id_tempario),'-') AS codigo_tempario,
														IFNULL((SELECT sa_tempario.descripcion_tempario FROM sa_tempario WHERE sa_tempario.id_tempario = pg_comision_empleado_detalle.id_tempario),'-') AS descripcion_tempario,
														pg_comision_empleado_detalle.ut,
														pg_comision_empleado_detalle.precio_tempario,
														pg_comision_empleado_detalle.base_ut_precio,
														'0' AS iva,
														pg_comision_empleado_detalle.porcentaje_comision,
														pg_comision_empleado_detalle.id_tempario,
														pg_comision_empleado_detalle.monto_comision,
														pg_comision_empleado_detalle.precio_venta
													FROM
														pg_comision_empleado_detalle
													WHERE
														pg_comision_empleado_detalle.id_comision_empleado = (SELECT id_comision_empleado FROM pg_comision_empleado WHERE pg_comision_empleado.id_vale_salida = (SELECT id_vale_salida FROM sa_vale_salida WHERE sa_vale_salida.id_orden = %s) AND pg_comision_empleado.id_empleado = %s)",
										$rowNumeroOrden['id_orden'],
										$rowMecanico['id_empleado']);
						}
						$rsDetalle = mysql_query($queryDetalle);
						if (!$rsDetalle) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryDetalle);
						
						$total_ut_orden = 0;
						$total_mano_obra = 0;
						$iva = 0;
						$total_comision = 0;
						
						while ($rowDetalle = mysql_fetch_array($rsDetalle)){
							$monto_comision = $rowDetalle['monto_comision'];
							if($tipoListado){
										$html .= "<tr >";//class=\"".$clase."\"
											$html .= "<td align='left'>".$rowDetalle['codigo_tempario']."</td>";
											$html .= "<td align='left'>".utf8_encode($rowDetalle['descripcion_tempario'])."</td>";
											$html .= "<td align='right'>".number_format($rowDetalle['ut'],2,".",",")."</td>";
											$html .= "<td align='right'>".number_format($rowDetalle['precio_tempario'],2,".",",")."</td>";
											$html .= "<td align='right'>".number_format($rowDetalle['precio_venta'],2,".",",")."</td>";
											$html .= "<td align='right'>".number_format(($rowDetalle['ut'] * $rowDetalle['precio_tempario_tipo_orden'] / $rowDetalle['base_ut_precio']),2,".",",")."</td>";
											$html .= "<td align='right'>".$rowDetalle['porcentaje_comision']."</td>";
											$html .= "<td align='right'>".$monto_comision."</td>";
										$html .= "</tr>";
									}
									if ($rowNumeroOrden['tipo_documento'] == "NC"){
										$total_ut_orden -= $rowDetalle['ut'];
										$total_mano_obra -= $rowDetalle['precio_venta'];
										$iva -= $rowDetalle['iva'];
										$total_comision -= $monto_comision;
									}
									else{
										$total_ut_orden += $rowDetalle['ut'];
										$total_mano_obra += $rowDetalle['precio_venta'];
										$iva += $rowDetalle['iva'];
										$total_comision += $monto_comision;
									}
						}
						//$iva /= mysql_num_rows($rsDetalle);
						$iva *= $total_mano_obra / 100;
						if($tipoListado)
							$html .= "<tr>
										<td align=\"right\" class='tituloColumna' colspan=\"1\" idOrdenOculta='".$rowNumeroOrden['id_orden']."'>Total Orden ".$rowNumeroOrden['numero_orden'].":</a></td>
										<td align='right'>UT'S:</td>
										<td align='right'>".number_format($total_ut_orden,2,".",",")."</td>
										<td align='right'></td>
										<td align='right'>".number_format($total_mano_obra,2,".",",")."</td>
										<td align='right'>".number_format($iva,2,".",",")."</td>
										<td align='right'></td>
										<td align='right'>".number_format($total_comision,2,".",",")."</td>
									  </tr>";
									  
						$arrayTotalTemparios[$indiceArreglos] += $total_mano_obra;
						$arrayTotalUT[$indiceArreglos] += $total_ut_orden;
						$arrayTotalComision[$indiceArreglos] += $total_comision;
						$total_por_tipo_orden += $total_mano_obra;
					}
				}// FIN CICLO WHILE
			}// FIN CONDICION
			if($tipoListado)
				$html .= "<tr>
							<td align=\"right\" class='trResaltar6 textoNegrita_12px' colspan=\"1\">Total ".utf8_encode($rowTipoOrden['nombre_tipo_orden']).":</a></td>
							<td align='left' colspan=\"7\">".number_format($total_por_tipo_orden,2,".",",")."</td>
						  </tr>";
		}
	}
	
	if(mysql_num_rows($rsLimitTipoOrden) > 0){
		if (mysql_num_rows($rsMecanico) > 0){
			$html .= "<tr>
						<td align=\"center\" class='trResaltar6 textoNegrita_12px' colspan=\"10\">Total Comisiones Por Mecanicos</a></td>
					  </tr>";
					  
			$html .= "<tr class='tituloColumna'>
						<td align=\"right\" colspan=\"3\">Mecanico</a></td>
						<td align=\"right\" colspan=\"2\">Total M.O</a></td>
						<td align=\"right\" colspan=\"2\">Total U.T.</a></td>
						<td align=\"right\" colspan=\"2\">Total Comision</a></td>
					  </tr>";
			
			$total_mano_obra_final = 0;
			
			foreach($arrayIdEmpleado as $indiceIdEmpleado => $valorIdEmpleado){
				$queryNombreMecanico = sprintf("SELECT nombre_empleado, apellido FROM pg_empleado WHERE id_empleado = %s",$valorIdEmpleado);
				$rsNombreMecanico = mysql_query($queryNombreMecanico);
				
				if (!$rsNombreMecanico) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__);
				
				$rowNombreMecanico = mysql_fetch_array($rsNombreMecanico);
				$html .= "<tr>
							<td align=\"left\" colspan=\"3\">".utf8_encode($rowNombreMecanico['nombre_empleado'])." ".utf8_encode($rowNombreMecanico['apellido'])."</a></td>
							<td align=\"right\" colspan=\"2\">".number_format($arrayTotalTemparios[$indiceIdEmpleado],2,'.',',')."</a></td>
							<td align=\"right\" colspan=\"2\">".number_format($arrayTotalUT[$indiceIdEmpleado],2,'.',',')."</a></td>
							<td align=\"right\" colspan=\"2\">".number_format($arrayTotalComision[$indiceIdEmpleado],2,'.',',')."</a></td>
						  </tr>";
			$total_mano_obra_final += $arrayTotalTemparios[$indiceIdEmpleado];
			$total_UT_final += $arrayTotalUT[$indiceIdEmpleado];
			$total_comision_final += $arrayTotalComision[$indiceIdEmpleado];
			}
			
			$html .= "<tr class='tituloColumna'>
							<td align=\"right\" colspan=\"3\">Total:</a></td>
							<td align=\"right\" colspan=\"2\">".number_format($total_mano_obra_final,2,'.',',')."</a></td>
							<td align=\"right\" colspan=\"2\">".number_format($total_UT_final,2,'.',',')."</a></td>
							<td align=\"right\" colspan=\"2\">".number_format($total_comision_final,2,'.',',')."</a></td>
						  </tr>";
			
			$htmlTableFin .= "</table>";
			
			$objResponse->assign("tdComisiones","innerHTML",$htmlTableIni.$html.$htmlTableFin);
		}
		else
			$objResponse->assign("tdComisiones","innerHTML","No Hay Registros");
	}
	else
		$objResponse->assign("tdComisiones","innerHTML","No Hay Registros");
	
	return $objResponse;
}

function listarComisionesGerentes($idEmpleado,$tipoEmpleado,$fechaDesde, $fechaHasta){
	$objResponse = new xajaxResponse();
	
	$arrayFechaDesde = explode("-",$fechaDesde);
	$arrayFechaHasta = explode("-",$fechaHasta);
	
	$fechaFormatoDesde = $arrayFechaDesde[2]."-".$arrayFechaDesde[1]."-".$arrayFechaDesde[0];
	$fechaFormatoHasta = $arrayFechaHasta[2]."-".$arrayFechaHasta[1]."-".$arrayFechaHasta[0];
	
	if (!xvalidaAcceso($objResponse,PAGE_PRIV)){
		$objResponse->assign("tdComisiones","innerHTML","Acceso Denegado");
		return $objResponse;
	}
	
	//ARREGLO CON LAS CLAVES DE FILTRO DE LOS EMPLEADOS
	$arrayEmpleado = array ("501","6","5","300","4");
	
	$claveFiltro = explode("|",$arrayEmpleado[$tipoEmpleado]);
	
	foreach ($claveFiltro as $indiceClaveFiltro => $valorClaveFiltro){
		if ($condicionClaveFiltro == ""){
			$condicionClaveFiltro = "WHERE (clave_filtro = ".$valorClaveFiltro;
		}
		else{
			$condicionClaveFiltro .= " OR clave_filtro = ".$valorClaveFiltro;
		}
	}
	$condicionClaveFiltro .= ")";
	
	if ($idEmpleado != 0)
		$condicionClaveFiltro .= " AND id_empleado = ".$idEmpleado;
	
	$htmlTableIni .= "<table border=\"1\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
	
	$queryEmpleado = sprintf("SELECT pg_empleado.id_empleado, pg_empleado.nombre_empleado, pg_empleado.apellido, pg_empleado.id_cargo_departamento, pg_cargo_departamento.clave_filtro FROM pg_empleado INNER JOIN pg_cargo_departamento ON (pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento) %s ",$condicionClaveFiltro);
	$rsEmpleado = mysql_query($queryEmpleado);
	
	if (!$rsEmpleado) return $objResponse->alert(mysql_error());
	
	while ($rowEmpleado = mysql_fetch_array($rsEmpleado)){
		$totalEmpleadoServicio = 0;
		$totalEmpleadoIva = 0;
		$totalEmpleadoMO = 0;
		$totalEmpleadoTOT = 0;
		$totalEmpleadoNotas = 0;
		$totalEmpleadoRepuesto = 0;
		$totalEmpleadoDescuento = 0;
		$porcentajeComision = 0;
		$totalEmpleadoComision = 0;
		
		$queryPorcentajesComisionesManoObra = sprintf("SELECT porcentaje_comision FROM pg_comisiones WHERE (tipo_comision = 1 OR tipo_comision = 6) AND id_cargo_departamamento = %s ",$rowEmpleado['id_cargo_departamento']);
		$rsPorcentajesComisionesManoObra = mysql_query($queryPorcentajesComisionesManoObra);
		if (!$rsPorcentajesComisionesManoObra) return $objResponse->alert(mysql_error());
		$rowPorcentajesComisionesManoObra = mysql_fetch_array($rsPorcentajesComisionesManoObra);
		$porcentajeComisionManoObra = $rowPorcentajesComisionesManoObra['porcentaje_comision'];
		
		$porcentajeComisionServicio = $porcentajeComisionManoObra;
		
		$queryPorcentajesComisionesRepuestos = sprintf("SELECT porcentaje_comision FROM pg_comisiones WHERE tipo_comision = 2 AND id_cargo_departamamento = %s ",$rowEmpleado['id_cargo_departamento']);
		$rsPorcentajesComisionesRepuestos = mysql_query($queryPorcentajesComisionesRepuestos);
		if (!$rsPorcentajesComisionesRepuestos) return $objResponse->alert(mysql_error());
		$rowPorcentajesComisionesRepuestos = mysql_fetch_array($rsPorcentajesComisionesRepuestos);
		$porcentajeComisionRepuestos = $rowPorcentajesComisionesRepuestos['porcentaje_comision'];
		
		$porcentajeComision = $porcentajeComisionRepuestos;
		
		$queryPorcentajesComisionesTOT = sprintf("SELECT porcentaje_comision FROM pg_comisiones WHERE tipo_comision = 3 AND id_cargo_departamamento = %s ",$rowEmpleado['id_cargo_departamento']);
		$rsPorcentajesComisionesTOT = mysql_query($queryPorcentajesComisionesTOT);
		if (!$rsPorcentajesComisionesTOT) return $objResponse->alert(mysql_error());
		$rowPorcentajesComisionesTOT = mysql_fetch_array($rsPorcentajesComisionesTOT);
		$porcentajeComisionTOT = $rowPorcentajesComisionesTOT['porcentaje_comision'];
		
		$queryIva = sprintf("SELECT iva FROM pg_iva WHERE tipo = 6 AND activo = 1 AND estado = 1");
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return $objResponse->alert(mysql_error());
		$rowIva = mysql_fetch_array($rsIva);
		$iva = $rowIva['iva'];
		
		$queryNombreCargo = sprintf("SELECT nombre_filtro FROM pg_cargo_filtro WHERE filtro = %s",$rowEmpleado['clave_filtro']);
		$rsNombreCargo = mysql_query($queryNombreCargo);
		
		if (!$rsNombreCargo) return $objResponse->alert(mysql_error());
		
		$rowNombreCargo = mysql_fetch_array($rsNombreCargo);
		
		$html .= "<tr>
					<td align=\"right\" class='trResaltar6 textoNegrita_12px' colspan='4'>".utf8_encode($rowNombreCargo['nombre_filtro']).":</a></td>
					<td align='left' colspan='7'>".utf8_encode($rowEmpleado['nombre_empleado'])." ".utf8_encode($rowEmpleado['apellido'])."</td>
				  </tr>";
		
		$html .= "<tr class=\"tituloColumna\">
										<td width='11%'>Fecha.</td>
										<td width='11%'>Importe</td>
										<td width='11%'>Iva</td>
										<td width='11%'>Importe M.O.</td>
										<td width='11%'>Importe TOT</td>
										<td width='11%'>Importe Notas</td>
										<td width='11%'>% Comision M.O.</td>
										<td width='11%'>Importe Repuesto</td>
										<td width='11%'>% Comision R</td>
										<td width='11%'>Descuento</td>
										<td width='11%'>Comision</td>
									</tr>";
		
		if ($rowEmpleado['clave_filtro'] == 5){
			$queryFecha = sprintf("SELECT
									date(fecha_factura) AS fecha_factura,
									YEAR(fecha_factura) as ano,
									MONTH(fecha_factura) as mes,
									DAY(fecha_factura) as dia, 
									sa_cita.id_empleado_servicio as id_asesor
									FROM sa_orden
									INNER JOIN sa_recepcion 
									ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
									INNER JOIN sa_cita 
									ON (sa_recepcion.id_cita = sa_cita.id_cita)
									WHERE 
									fecha_factura BETWEEN '%s' AND '%s'
									AND id_empleado_servicio = %s
									UNION
									SELECT
									date(fecha_vale) AS fecha_factura,
									YEAR(fecha_vale) as ano,
									MONTH(fecha_vale) as mes,
									DAY(fecha_vale) as dia, 
									sa_cita.id_empleado_servicio as id_asesor
									FROM sa_vale_salida
									INNER JOIN sa_orden
									on (sa_vale_salida.id_orden = sa_orden.id_orden)
									INNER JOIN sa_recepcion 
									ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
									INNER JOIN sa_cita 
									ON (sa_recepcion.id_cita = sa_cita.id_cita)
									WHERE 
									fecha_vale BETWEEN '%s' AND '%s'
									AND id_empleado_servicio = %s
									GROUP BY dia ORDER BY dia ASC",
									$fechaFormatoDesde,
									$fechaFormatoHasta,
									$rowEmpleado['id_empleado'],
									$fechaFormatoDesde,
									$fechaFormatoHasta,
									$rowEmpleado['id_empleado']);
		} 
		else{
			$queryFecha = sprintf("SELECT
										DATE(fecha_factura) as fecha_factura,
										YEAR(fecha_factura) as ano,
										MONTH(fecha_factura) as mes,
										DAY(fecha_factura) as dia
									FROM sa_orden
									WHERE 
										fecha_factura BETWEEN '%s' AND '%s'
									UNION
									SELECT
										DATE(fecha_vale) as fecha_factura,
										YEAR(fecha_vale) as ano,
										MONTH(fecha_vale) as mes,
										DAY(fecha_vale) as dia
									FROM sa_vale_salida
									WHERE
										fecha_vale BETWEEN '%s' AND '%s'
									ORDER BY dia",
									$fechaFormatoDesde,
									$fechaFormatoHasta,
									$fechaFormatoDesde,
									$fechaFormatoHasta);
		}
		$rsFecha = mysql_query($queryFecha);
		
		if (!$rsFecha) return $objResponse->alert(mysql_error());
		
		while($rowFecha = mysql_fetch_array($rsFecha)){

			$totalServicioDia = 0;
			$totalTOTDia = 0;
			$totalNotasDia = 0;
			$totalRepuestoDia = 0;
			$totalDescuentoDia = 0;
			$totalIvaDia = 0;
			$totalComisionServicio = 0;
			$totalComisionRepuesto = 0;
			$totalComisionTOT = 0;
			$totalComisionNota = 0;
			$totalDescuento = 0;
			
			if ($rowEmpleado['clave_filtro'] == 5){
				$queryDetalleManoObra = sprintf("SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(((precio_tempario_tipo_orden * ut) / base_ut_precio)) as monto_mano_obra,
													'FA' as documento
												FROM
													sa_v_informe_final_tempario
												INNER JOIN sa_orden ON (sa_v_informe_final_tempario.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND id_empleado_servicio = %s
												GROUP BY
													fecha_filtro
												union
												SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(((precio_tempario_tipo_orden * ut) / base_ut_precio)) AS monto_mano_obra,
													'VS' as documento
												FROM
													sa_v_vale_informe_final_tempario
												INNER JOIN sa_orden ON (sa_v_vale_informe_final_tempario.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND 
													id_empleado_servicio = %s
												GROUP BY
													fecha_filtro
												UNION
												SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(((precio_tempario_tipo_orden * ut) / base_ut_precio)) as monto_mano_obra,
													'NC' as documento
												FROM
													sa_v_informe_final_tempario_dev
												INNER JOIN sa_orden ON (sa_v_informe_final_tempario_dev.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND id_empleado_servicio = %s
												GROUP BY
													fecha_filtro",
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado'],
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado'],
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado']);
				
				$queryDetalleRepuesto = sprintf("SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(precio_unitario * cantidad) as monto_repuesto,
													'FA' as documento
												FROM
													sa_v_informe_final_repuesto
												INNER JOIN sa_orden ON (sa_v_informe_final_repuesto.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro
												union
												SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(precio_unitario * cantidad) AS monto_repuesto,
													'VS' as documento
												FROM
													sa_v_vale_informe_final_repuesto
												INNER JOIN sa_orden ON (sa_v_vale_informe_final_repuesto.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND 
													id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro
												UNION
												SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(precio_unitario * cantidad) as monto_repuesto,
													'NC' as documento
												FROM
													sa_v_informe_final_repuesto_dev
												INNER JOIN sa_orden ON (sa_v_informe_final_repuesto_dev.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro",
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado'],
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado'],
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado']);
				
				$queryDetalleTOT = sprintf("SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(monto_total + ((porcentaje_tot * monto_total) / 100)) as monto_tot,
													'FA' as documento
												FROM
													sa_v_informe_final_tot
												INNER JOIN sa_orden ON (sa_v_informe_final_tot.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro
												union
												SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(monto_total + ((porcentaje_tot * monto_total) / 100)) as monto_tot,
													'VS' as documento
												FROM
													sa_v_vale_informe_final_tot
												INNER JOIN sa_orden ON (sa_v_vale_informe_final_tot.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND 
													id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro
												UNION
												SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(monto_total) as monto_tot,
													'NC' as documento
												FROM
													sa_v_informe_final_tot_dev
												INNER JOIN sa_orden ON (sa_v_informe_final_tot_dev.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro",
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado'],
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado'],
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado']);
				
				$queryDetalleNota = sprintf("SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(precio) as monto_nota,
													'FA' as documento
												FROM
													sa_v_informe_final_notas
												INNER JOIN sa_orden ON (sa_v_informe_final_notas.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro
												union
												SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(precio) as monto_nota,
													'VS' as documento
												FROM
													sa_v_vale_informe_final_notas
												INNER JOIN sa_orden ON (sa_v_vale_informe_final_notas.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND 
													id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro
												UNION
												SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(precio) as monto_nota,
													'NC' as documento
												FROM
													sa_v_informe_final_notas_dev
												INNER JOIN sa_orden ON (sa_v_informe_final_notas_dev.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro",
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado'],
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado'],
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado']);
				
				$queryDetalleDescuento = sprintf("SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM((precio_unitario * cantidad) * porcentaje_descuento_orden / 100) AS monto_descuento,
													'FA' as documento
												FROM
													sa_v_informe_final_repuesto
												INNER JOIN sa_orden ON (sa_v_informe_final_repuesto.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro
												union
												SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM((precio_unitario * cantidad) * porcentaje_descuento_orden / 100) AS monto_descuento,
													'VS' as documento
												FROM
													sa_v_vale_informe_final_repuesto
												INNER JOIN sa_orden ON (sa_v_vale_informe_final_repuesto.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND 
													id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro
												UNION
												SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM((precio_unitario * cantidad) * porcentaje_descuento_orden / 100) AS monto_descuento,
													'NC' as documento
												FROM
													sa_v_informe_final_repuesto_dev
												INNER JOIN sa_orden ON (sa_v_informe_final_repuesto_dev.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro",
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado'],
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado'],
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado']);
			}
			else{
				$queryDetalleManoObra = sprintf("SELECT
												SUM(((precio_tempario_tipo_orden * ut) / base_ut_precio)) as monto_mano_obra,
												'FA' as documento
											FROM
											sa_v_informe_final_tempario
											WHERE
												fecha_filtro = '%s'
											UNION
											SELECT 
											  SUM(((sa_v_vale_informe_final_tempario.precio_tempario_tipo_orden * sa_v_vale_informe_final_tempario.ut) / 
													sa_v_vale_informe_final_tempario.base_ut_precio)) AS monto_mano_obra,
												'VS' as documento
											FROM
											  sa_v_vale_informe_final_tempario
											WHERE
											  fecha_filtro = '%s'
											UNION
											SELECT
												SUM(((precio_tempario_tipo_orden * ut) / base_ut_precio)) as monto_mano_obra,
												'NC' as documento
											FROM
												sa_v_informe_final_tempario_dev
											WHERE
											fecha_filtro = '%s'
											GROUP BY
											  fecha_filtro",
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])),
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])),
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])));
				
				$queryDetalleRepuesto = sprintf("SELECT
												SUM(precio_unitario * cantidad) as monto_repuesto,
												'FA' as documento
											FROM
												sa_v_informe_final_repuesto
											WHERE
												fecha_filtro = '%s' AND aprobado = 1
											UNION
											SELECT 
												SUM(precio_unitario * cantidad) AS monto_repuesto,
												'VS' as documento
											FROM
												sa_v_vale_informe_final_repuesto
											WHERE
												fecha_filtro = '%s' AND aprobado = 1
											UNION
											SELECT
												SUM(precio_unitario * cantidad) as monto_repuesto,
												'NC' as documento
											FROM
												sa_v_informe_final_repuesto_dev
											WHERE
												fecha_filtro = '%s' AND aprobado = 1
											GROUP BY
												fecha_filtro",
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])),
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])),
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])));
				
				$queryDetalleTOT = sprintf("SELECT
												SUM(monto_total + ((porcentaje_tot * monto_total) / 100)) as monto_tot,
												'FA' as documento
											FROM
												sa_v_informe_final_tot
											WHERE
												fecha_filtro = '%s' AND aprobado = 1
											UNION
											SELECT 
												SUM(monto_total + ((porcentaje_tot * monto_total) / 100)) as monto_tot,
												'VS' as documento
											FROM
												sa_v_vale_informe_final_tot
											WHERE
												fecha_filtro = '%s' AND aprobado = 1
											UNION
											SELECT
												SUM(monto_total) as monto_tot,
												'NC' as documento
											FROM
												sa_v_informe_final_tot_dev
											WHERE
												fecha_filtro = '%s' AND aprobado = 1
											GROUP BY
												fecha_filtro",
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])),
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])),
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])));
				
				$queryDetalleNota = sprintf("SELECT
												SUM(precio) as monto_nota,
												'FA' as documento
											FROM
												sa_v_informe_final_notas
											WHERE
												fecha_filtro = '%s' AND aprobado = 1
											UNION
											SELECT 
												SUM(precio) as monto_nota,
												'VS' as documento
											FROM
												sa_v_vale_informe_final_notas
											WHERE
												fecha_filtro = '%s' AND aprobado = 1
											UNION
											SELECT
												SUM(precio) as monto_nota,
												'NC' as documento
											FROM
												sa_v_informe_final_notas_dev
											WHERE
												fecha_filtro = '%s' AND aprobado = 1
											GROUP BY
												fecha_filtro",
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])),
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])),
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])));
				
				$queryDetalleDescuento = sprintf("SELECT 
											  SUM((precio_unitario * cantidad) * porcentaje_descuento_orden / 100) AS monto_descuento,
											  'FA' AS documento
											FROM
											  sa_v_informe_final_repuesto
											WHERE
											  fecha_filtro = '%s' AND 
											  aprobado = 1
											UNION
											SELECT 
											  SUM((precio_unitario * cantidad) * porcentaje_descuento_orden / 100) AS monto_descuento,
											  'VS' AS documento
											FROM
											  sa_v_vale_informe_final_repuesto
											WHERE
											  fecha_filtro = '%s' AND 
											  aprobado = 1
											UNION
											SELECT 
											  SUM((precio_unitario * cantidad) * porcentaje_descuento_orden / 100) AS monto_descuento,
											  'NC' AS documento
											FROM
											  sa_v_informe_final_repuesto_dev
											WHERE
											  fecha_filtro = '%s' AND 
											  aprobado = 1",
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])),
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])),
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])));
			}
			
			$rsDetalleManoObra = mysql_query($queryDetalleManoObra);
			$rsDetalleRepuesto = mysql_query($queryDetalleRepuesto);
			$rsDetalleTOT = mysql_query($queryDetalleTOT);
			$rsDetalleNota = mysql_query($queryDetalleNota);
			$rsDetalleDescuento = mysql_query($queryDetalleDescuento);
			
			if (!$rsDetalleManoObra) return $objResponse->alert(mysql_error());
			if (!$rsDetalleRepuesto) return $objResponse->alert(mysql_error());
			if (!$rsDetalleTOT) return $objResponse->alert(mysql_error());
			if (!$rsDetalleNota) return $objResponse->alert(mysql_error());
			if (!$rsDetalleDescuento) return $objResponse->alert(mysql_error());
			
			//COMISIONES POR MANO DE OBRA
			while ($rowDetalleManoObra = mysql_fetch_array($rsDetalleManoObra)){				
				if ($rowDetalleManoObra['documento'] == "NC")
					$totalServicioDia -= $rowDetalleManoObra['monto_mano_obra'];
				else
					$totalServicioDia += $rowDetalleManoObra['monto_mano_obra'];
			}
			$totalComisionServicio = $totalServicioDia * $porcentajeComisionManoObra / 100;
			
			//COMISIONES POR REPUESTO
			while ($rowDetalleRepuesto = mysql_fetch_array($rsDetalleRepuesto)){				
				if ($rowDetalleRepuesto['documento'] == "NC")
					$totalRepuestoDia -= $rowDetalleRepuesto['monto_repuesto'];
				else
					$totalRepuestoDia += $rowDetalleRepuesto['monto_repuesto'];
			}
			$totalComisionRepuesto = $totalRepuestoDia * $porcentajeComisionRepuestos / 100;
			
			//COMISIONES POR TOT
			while ($rowDetalleTOT = mysql_fetch_array($rsDetalleTOT)){				
				if ($rowDetalleTOT['documento'] == "NC")
					$totalTOTDia -= $rowDetalleTOT['monto_tot'];
				else
					$totalTOTDia += $rowDetalleTOT['monto_tot'];
			}
			$totalComisionTOT = $totalTOTDia * $porcentajeComisionTOT / 100;
			
			//COMISIONES POR NOTAS
			while ($rowDetalleNotas = mysql_fetch_array($rsDetalleNota)){				
				if ($rowDetalleNotas['documento'] == "NC")
					$totalNotasDia -= $rowDetalleNotas['monto_nota'];
				else
					$totalNotasDia += $rowDetalleNotas['monto_nota'];
			}
			$totalComisionNota = $totalNotasDia * $porcentajeComisionNota / 100;
			
			//COMISIONES POR DESCUENTOS
			while ($rowDetalleDescuentos = mysql_fetch_array($rsDetalleDescuento)){
				if ($rowDetalleDescuentos['documento'] == "NC")
					$totalDescuentoDia -= $rowDetalleDescuentos['monto_descuento'];
				else
					$totalDescuentoDia += $rowDetalleDescuentos['monto_descuento'];
			}
			
			$totalComisionRepuesto -= $totalDescuentoDia * $porcentajeComisionRepuestos / 100;
								
			$totalOrdenDia = $totalServicioDia + $totalRepuestoDia + $totalTOTDia + $totalNotasDia - $totalDescuentoDia;
			$totalIvaDia = $totalOrdenDia * $iva / 100;
			$totalComisionIva = $totalComisionServicio + $totalComisionRepuesto + $totalComisionTOT + $totalComisionNota;
						
			$html .= "<tr>
						<td >".date("dmY",strtotime($rowFecha['fecha_factura']))."</td>
						<td align='right'>".number_format($totalOrdenDia,"2",".",",")."</td>
						<td align='right'>".number_format($totalIvaDia,"2",".",",")."</td>
						<td align='right'>".number_format($totalServicioDia,"2",".",",")."</td>
						<td align='right'>".number_format($totalTOTDia,"2",".",",")."</td>
						<td align='right'>".number_format($totalNotasDia,"2",".",",")."</td>
						<td align='right'>".number_format($porcentajeComisionServicio,"2",".",",")."</td>
						<td align='right'>".number_format($totalRepuestoDia,"2",".",",")."</td>
						<td align='right'>".number_format($porcentajeComision,"2",".",",")."</td>
						<td align='right'>".number_format($totalDescuentoDia,"2",".",",")."</td>
						<td align='right'>".number_format($totalComisionIva,"2",".",",")."</td>
					</tr>";
			
			$totalEmpleadoServicio += $totalOrdenDia;
			$totalEmpleadoIva += $totalIvaDia;
			$totalEmpleadoMO += $totalServicioDia;
			$totalEmpleadoTOT += $totalTOTDia;
			$totalEmpleadoNotas += $totalNotasDia;
			$totalEmpleadoRepuesto += $totalRepuestoDia;
			$totalEmpleadoDescuento += $totalDescuentoDia;
			$totalEmpleadoComision += $totalComisionIva;
		}
		
		$totalAcumuladoServicio += $totalEmpleadoServicio;
		$totalAcumuladoTOT += $totalEmpleadoTOT;
		$totalAcumuladoNotas += $totalEmpleadoNotas;
		$totalAcumuladoIva += $totalEmpleadoIva;
		$totalAcumuladoMO += $totalEmpleadoMO;
		$totalAcumuladoRepuesto += $totalEmpleadoRepuesto;
		$totalAcumuladoDescuento += $totalEmpleadoDescuento;
		$totalAcumuladoComision += $totalEmpleadoComision;
			
		$html .= "<tr class=\"tituloColumna\">
										<td >Total</td>
										<td align='right'>".number_format($totalEmpleadoServicio,"2",".",",")."</td>
										<td align='right'>".number_format($totalEmpleadoIva,"2",".",",")."</td>
										<td align='right'>".number_format($totalEmpleadoMO,"2",".",",")."</td>
										<td align='right'>".number_format($totalEmpleadoTOT,"2",".",",")."</td>
										<td align='right'>".number_format($totalEmpleadoNotas,"2",".",",")."</td>
										<td align='right'>".number_format($porcentajeComisionServicio,"2",".",",")."</td>
										<td align='right'>".number_format($totalEmpleadoRepuesto,"2",".",",")."</td>
										<td align='right'>".number_format($porcentajeComision,"2",".",",")."</td>
										<td align='right'>".number_format($totalEmpleadoDescuento,"2",".",",")."</td>
										<td align='right'>".number_format($totalEmpleadoComision,"2",".",",")."</td>
									</tr>";
		
		$html .= "<tr class=\"tituloColumna\">
										<td >Total Acumulado</td>
										<td align='right'>".number_format($totalAcumuladoServicio,"2",".",",")."</td>
										<td align='right'>".number_format($totalAcumuladoIva,"2",".",",")."</td>
										<td align='right'>".number_format($totalAcumuladoMO,"2",".",",")."</td>
										<td align='right'>".number_format($totalAcumuladoTOT,"2",".",",")."</td>
										<td align='right'>".number_format($totalAcumuladoNotas,"2",".",",")."</td>
										<td align='right'>".number_format($porcentajeComisionServicio,"2",".",",")."</td>
										<td align='right'>".number_format($totalAcumuladoRepuesto,"2",".",",")."</td>
										<td align='right'>".number_format($porcentajeComision,"2",".",",")."</td>
										<td align='right'>".number_format($totalAcumuladoDescuento,"2",".",",")."</td>
										<td align='right'>".number_format($totalAcumuladoComision,"2",".",",")."</td>
									</tr>";
	}
	
	$htmlTableFin .= "</table>";
	
	$objResponse->assign("tdComisiones","innerHTML",$htmlTableIni.$html.$htmlTableFin);
	
	return $objResponse;
}

/*

segunda
function listarComisionesMecanicos($idMecanico,$tipoListado,$fechaDesde, $fechaHasta){
	$objResponse = new xajaxResponse();
	
	$arrayFechaDesde = explode("-",$fechaDesde);
	$arrayFechaHasta = explode("-",$fechaHasta);
	
	$fechaFormatoDesde = $arrayFechaDesde[2]."-".$arrayFechaDesde[1]."-".$arrayFechaDesde[0];
	$fechaFormatoHasta = $arrayFechaHasta[2]."-".$arrayFechaHasta[1]."-".$arrayFechaHasta[0];
	
	
	
	if (!xvalidaAcceso($objResponse,PAGE_PRIV)){
		$objResponse->assign("tdComisiones","innerHTML","Acceso Denegado");
		return $objResponse;
	}
	
	if ($idMecanico == 0)
		$condicionMecanico = " WHERE ";
	else
		$condicionMecanico = " WHERE id_empleado = ".$idMecanico." AND ";
	
	//AGRUPAR POR EL TIPO DE ORDEN
	$queryTipoOrden = sprintf("SELECT 
						id_tipo_orden,
						(SELECT nombre_tipo_orden
							FROM 
								sa_tipo_orden
							WHERE
								sa_tipo_orden.id_tipo_orden = sa_v_informe_final_tempario.id_tipo_orden
							) as nombre_tipo_orden
						FROM 
						  sa_v_informe_final_tempario
						WHERE
						fecha_filtro BETWEEN '%s' AND '%s'
						GROUP BY id_tipo_orden
						UNION
						SELECT 
						id_tipo_orden,
						(SELECT nombre_tipo_orden
							FROM 
								sa_tipo_orden
							WHERE
								sa_tipo_orden.id_tipo_orden = sa_v_informe_final_tempario_dev.id_tipo_orden
							) as nombre_tipo_orden
						FROM 
						  sa_v_informe_final_tempario_dev
						WHERE
						fecha_filtro BETWEEN '%s' AND '%s'
						GROUP BY id_tipo_orden
						UNION
						SELECT 
						id_tipo_orden,
						(SELECT nombre_tipo_orden
							FROM 
								sa_tipo_orden
							WHERE
								sa_tipo_orden.id_tipo_orden = sa_v_vale_informe_final_tempario.id_tipo_orden
							) as nombre_tipo_orden
						FROM 
						  sa_v_vale_informe_final_tempario
						WHERE
						fecha_filtro BETWEEN '%s' AND '%s'
						GROUP BY id_tipo_orden",
						$fechaFormatoDesde,
						$fechaFormatoHasta,
						$fechaFormatoDesde,
						$fechaFormatoHasta,
						$fechaFormatoDesde,
						$fechaFormatoHasta);
	$objResponse->alert($queryTipoOrden);
	$rsLimitTipoOrden = mysql_query($queryTipoOrden);
	
	if (!$rsLimitTipoOrden) return $objResponse->alert(mysql_error());
	
	$htmlTableIni .= "<table border=\"1\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
	
	if (mysql_num_rows($rsLimitTipoOrden) > 0){
		while ($rowTipoOrden = mysql_fetch_assoc($rsLimitTipoOrden)){
			$total_por_tipo_orden = 0;

			if ($tipoListado)
				$html .= "<tr>
							<td align=\"right\" class='trResaltar6 textoNegrita_12px' colspan=\"1\" width='12%'>Tipo Orden:</a></td>
							<td align='left' colspan=\"7\" width='89%'>".utf8_encode($rowTipoOrden['nombre_tipo_orden'])."</td>
						  </tr>";
	
			//AGRUPAR POR MECANICO
			$queryMecanico = sprintf("SELECT 
									   sa_mecanicos.id_empleado
									FROM
									  sa_v_informe_final_tempario
									  INNER JOIN sa_det_orden_tempario ON (sa_v_informe_final_tempario.id_orden = sa_det_orden_tempario.id_orden)
									  INNER JOIN sa_mecanicos ON (sa_det_orden_tempario.id_mecanico = sa_mecanicos.id_mecanico)
									  %s (fecha_filtro BETWEEN '%s' AND '%s') AND id_tipo_orden = %s
									  GROUP BY  sa_mecanicos.id_empleado
									UNION
									SELECT
									  sa_mecanicos.id_empleado
									FROM
									  sa_v_informe_final_tempario_dev
									  INNER JOIN sa_det_orden_tempario ON (sa_v_informe_final_tempario_dev.id_orden = sa_det_orden_tempario.id_orden)
									  INNER JOIN sa_mecanicos ON (sa_det_orden_tempario.id_mecanico = sa_mecanicos.id_mecanico)
									  %s (fecha_filtro BETWEEN '%s' AND '%s') AND id_tipo_orden = %s
									  GROUP BY  sa_mecanicos.id_empleado
									  UNION
									SELECT 
									  sa_mecanicos.id_empleado
									FROM
									  sa_v_vale_informe_final_tempario
									  INNER JOIN sa_det_orden_tempario ON (sa_v_vale_informe_final_tempario.id_orden = sa_det_orden_tempario.id_orden)
									  INNER JOIN sa_mecanicos ON (sa_det_orden_tempario.id_mecanico = sa_mecanicos.id_mecanico)
									  %s (fecha_filtro BETWEEN '%s' AND '%s') AND id_tipo_orden = %s
									  GROUP BY  sa_mecanicos.id_empleado",
									 $condicionMecanico, $fechaFormatoDesde,$fechaFormatoHasta,$rowTipoOrden['id_tipo_orden'],
									 $condicionMecanico, $fechaFormatoDesde,$fechaFormatoHasta,$rowTipoOrden['id_tipo_orden'],
									 $condicionMecanico, $fechaFormatoDesde,$fechaFormatoHasta,$rowTipoOrden['id_tipo_orden']);
			$rsMecanico = mysql_query($queryMecanico);
			
			if (!$rsMecanico) return $objResponse->alert(mysql_error());
			
			if (mysql_num_rows($rsMecanico) > 0){
				while ($rowMecanico = mysql_fetch_array($rsMecanico)){
					$indiceArreglos = 0;
					
					while ($indiceArreglos < count($arrayIdEmpleado) && $arrayIdEmpleado[$indiceArreglos] != $rowMecanico['id_empleado'])
						$indiceArreglos++;
					
					if ($indiceArreglos == count($arrayIdEmpleado))
						$arrayIdEmpleado[] = $rowMecanico['id_empleado'];
					
					//AGRUPAR POR NUMERO DE ORDEN
					$queryNumeroOrden = sprintf("SELECT 
										  sa_cita.id_empleado_servicio AS id_asesor,
										  'FA' AS tipo_documento,
										  pg_empleado.nombre_empleado,
										  pg_empleado.apellido AS apellido_empleado,
										  cj_cc_cliente.nombre AS nombre_cliente,
										  cj_cc_cliente.apellido AS apellido_cliente,
										  sa_v_informe_final_tempario.id_orden,
										  sa_orden.tiempo_orden AS fecha_orden,
										  cj_cc_encabezadofactura.idFactura,
										  sa_v_informe_final_tempario.fecha_filtro AS fechaRegistroFactura
										FROM
										  sa_v_informe_final_tempario
										  INNER JOIN sa_det_orden_tempario ON (sa_v_informe_final_tempario.id_orden = sa_det_orden_tempario.id_orden)
										  INNER JOIN sa_mecanicos ON (sa_det_orden_tempario.id_mecanico = sa_mecanicos.id_mecanico)
										  INNER JOIN sa_orden ON (sa_v_informe_final_tempario.id_orden = sa_orden.id_orden)
										  INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
										  INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
										  INNER JOIN pg_empleado ON (sa_mecanicos.id_empleado = pg_empleado.id_empleado)
										  INNER JOIN cj_cc_encabezadofactura ON (sa_orden.id_orden = cj_cc_encabezadofactura.numeroPedido)
										  AND (cj_cc_encabezadofactura.idDepartamentoOrigenFactura = 1)
										  INNER JOIN cj_cc_cliente ON (cj_cc_encabezadofactura.idCliente = cj_cc_cliente.id)
										WHERE
										  sa_mecanicos.id_empleado = %s AND 
										  fecha_filtro BETWEEN '%s' AND '%s' AND
										  sa_v_informe_final_tempario.id_tipo_orden = %s
										
										UNION
										
										SELECT 
										  sa_cita.id_empleado_servicio AS id_asesor,
										  'NC' AS tipo_documento,
										  pg_empleado.nombre_empleado,
										  pg_empleado.apellido AS apellido_empleado,
										  cj_cc_cliente.nombre AS nombre_cliente,
										  cj_cc_cliente.apellido AS apellido_cliente,
										  sa_v_informe_final_tempario_dev.id_orden,
										  sa_orden.tiempo_orden AS fecha_orden,
										  cj_cc_encabezadofactura.idFactura,
										  sa_v_informe_final_tempario_dev.fecha_filtro AS fechaRegistroFactura
										FROM
										  sa_v_informe_final_tempario_dev
										  INNER JOIN sa_det_orden_tempario ON (sa_v_informe_final_tempario_dev.id_orden = sa_det_orden_tempario.id_orden)
										  INNER JOIN sa_mecanicos ON (sa_det_orden_tempario.id_mecanico = sa_mecanicos.id_mecanico)
										  INNER JOIN sa_orden ON (sa_v_informe_final_tempario_dev.id_orden = sa_orden.id_orden)
										  INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
										  INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
										  INNER JOIN pg_empleado ON (sa_mecanicos.id_empleado = pg_empleado.id_empleado)
										  INNER JOIN cj_cc_encabezadofactura ON (sa_orden.id_orden = cj_cc_encabezadofactura.numeroPedido)
										  AND (cj_cc_encabezadofactura.idDepartamentoOrigenFactura = 1)
										  INNER JOIN cj_cc_cliente ON (cj_cc_encabezadofactura.idCliente = cj_cc_cliente.id)
										WHERE
										  sa_mecanicos.id_empleado = %s AND 
										  fecha_filtro BETWEEN '%s' AND '%s' AND
										  sa_v_informe_final_tempario_dev.id_tipo_orden = %s
										  union
										SELECT 
											sa_cita.id_empleado_servicio AS id_asesor,
											'VS' AS tipo_documento,
											pg_empleado.nombre_empleado AS nombre_empleado,
											pg_empleado.apellido AS apellido_empleado,
											cj_cc_cliente.nombre AS nombre_cliente,
											cj_cc_cliente.apellido AS apellido_cliente,
											sa_v_vale_informe_final_tempario.id_orden,
											sa_orden.tiempo_orden AS fecha_orden,
											sa_vale_salida.id_vale_salida AS idFactura,
											sa_v_vale_informe_final_tempario.fecha_filtro AS fechaRegistroFactura
										FROM
											sa_v_vale_informe_final_tempario
											INNER JOIN sa_det_orden_tempario ON (sa_v_vale_informe_final_tempario.id_orden = sa_det_orden_tempario.id_orden)
											INNER JOIN sa_mecanicos ON (sa_det_orden_tempario.id_mecanico = sa_mecanicos.id_mecanico)
											INNER JOIN sa_orden ON (sa_v_vale_informe_final_tempario.id_orden = sa_orden.id_orden)
											INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
											INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
											INNER JOIN pg_empleado ON (sa_mecanicos.id_empleado = pg_empleado.id_empleado)
											INNER JOIN sa_vale_salida ON (sa_orden.id_orden = sa_vale_salida.id_orden)
											INNER JOIN cj_cc_cliente ON (sa_cita.id_cliente_contacto = cj_cc_cliente.id)
										WHERE
										  sa_mecanicos.id_empleado = %s AND
										  fecha_filtro BETWEEN '%s' AND '%s' AND
										  sa_v_vale_informe_final_tempario.id_tipo_orden = %s
										GROUP BY
										  sa_v_vale_informe_final_tempario.id_orden,
										  tipo_documento",
										$rowMecanico['id_empleado'],
										$fechaFormatoDesde,$fechaFormatoHasta,
										$rowTipoOrden['id_tipo_orden'],
										$rowMecanico['id_empleado'],
										$fechaFormatoDesde,$fechaFormatoHasta,
										$rowTipoOrden['id_tipo_orden'],
										$rowMecanico['id_empleado'],
										$fechaFormatoDesde,$fechaFormatoHasta,
										$rowTipoOrden['id_tipo_orden']);
					$rsNumeroOrden = mysql_query($queryNumeroOrden);
					
					if (!$rsNumeroOrden) return $objResponse->alert(mysql_error());
					
					while ($rowNumeroOrden = mysql_fetch_array($rsNumeroOrden)){
						$queryAsesor = "SELECT nombre_empleado as nombre_asesor, apellido as apellido_asesor FROM pg_empleado WHERE id_empleado = ".$rowNumeroOrden['id_asesor'];
						$rsAsesor = mysql_query($queryAsesor);
						
						if (!$rsAsesor) return $objResponse->alert(mysql_error());
						
						$rowAsesor = mysql_fetch_array($rsAsesor);
						//YA SE VAN A IMPRIMIR POR ORDEN
							if($tipoListado){
								if ($rowNumeroOrden['tipo_documento'] == "FA")
									$tipoDocumento = "Factura";
									else if ($rowNumeroOrden['tipo_documento'] == "VS")
									$tipoDocumento = "Vale Salida";
								else
									$tipoDocumento = "Nota Credito";
								
								$html .= "<tr>
										<td align=\"right\" class='tituloColumna' width='12%'>Mecanico:</a></td>
										<td align='left' colspan=\"1\" width='25%'>".utf8_encode($rowNumeroOrden['nombre_empleado'])." ".utf8_encode($rowNumeroOrden['apellido_empleado'])."</td>
										<td align=\"right\" class='tituloColumna'>Asesor:</a></td>
										<td align='left' colspan=\"2\">".utf8_encode($rowAsesor['nombre_asesor'])." ".utf8_encode($rowAsesor['apellido_asesor'])."</td>
										<td align=\"right\" class='tituloColumna'  width='5%'>Cliente:</a></td>
										<td align='left' colspan=\"2\">".utf8_encode($rowNumeroOrden['nombre_cliente'])." ".utf8_encode($rowNumeroOrden['apellido_cliente'])."</td>
									</tr>
									<tr>
										<td align=\"right\" class='tituloColumna'>Orden:</td>
										<td align='left'>".$rowNumeroOrden['id_orden']."</td>
										<td align=\"right\" class='tituloColumna'>Fecha Orden:</td>
										<td align='left'>".date("d-m-Y",strtotime($rowNumeroOrden['fecha_orden']))."</td>
										<td align=\"right\" class='tituloColumna'>".$tipoDocumento.":</td>
										<td align='left'>".$rowNumeroOrden['id_factura']."</td>
										<td align=\"right\" class='tituloColumna' width='12%'>Fecha ".$tipoDocumento.":</td>
										<td align='left'>".date("d-m-Y",strtotime($rowNumeroOrden['fechaRegistroFactura']))."</td>
									</tr>";
								
									$html .= "<tr class=\"tituloColumna\">
													<td width='12%'>Cod. Tem.</td>
													<td width='25%'>Descripci".utf8_encode("ó")."n Tempario</td>
													<td width='10%'>UT</td>
													<td width='10%'>Valor M.O.</td>
													<td width='10%'>Precio</td>
													<td width='5%'>Iva</td>
													<td width='10%'>% Comision</td>
													<td width='10%'>Comision</td>
												</tr>";
							}
						
						if ($rowNumeroOrden['tipo_documento'] == 'FA'){
							$queryDetalle = sprintf("SELECT
														IFNULL((SELECT sa_tempario.codigo_tempario FROM sa_tempario WHERE sa_tempario.id_tempario = sa_det_orden_tempario.id_tempario),'-') AS codigo_tempario,
														IFNULL((SELECT sa_tempario.descripcion_tempario FROM sa_tempario WHERE sa_tempario.id_tempario = sa_det_orden_tempario.id_tempario),'-') AS descripcion_tempario,
														sa_v_informe_final_tempario.ut,
														sa_v_informe_final_tempario.precio_tempario_tipo_orden,
														sa_v_informe_final_tempario.base_ut_precio,
														sa_v_informe_final_tempario.iva,
														(SELECT sa_mecanicos.porcentaje_comision FROM sa_mecanicos WHERE sa_mecanicos.id_mecanico = sa_det_orden_tempario.id_mecanico) AS porcentaje_comision,
														sa_det_orden_tempario.id_tempario
													FROM
														sa_det_orden_tempario,
														sa_v_informe_final_tempario
													WHERE
														sa_det_orden_tempario.id_det_orden_tempario= sa_v_informe_final_tempario.id_det_orden_tempario
														AND sa_v_informe_final_tempario.id_empresa = 1
														AND sa_v_informe_final_tempario.aprobado = 1
														AND sa_v_informe_final_tempario.id_orden = %s
														AND sa_det_orden_tempario.id_mecanico = (SELECT sa_mecanicos.id_mecanico FROM sa_mecanicos WHERE sa_mecanicos.id_empleado = %s)",
										$rowNumeroOrden['id_orden'],
										$rowMecanico['id_empleado']);
						}
						else if($rowNumeroOrden['tipo_documento'] == 'NC'){
							$queryDetalle = sprintf("SELECT
														sa_tempario.codigo_tempario,
														sa_tempario.descripcion_tempario,
														sa_v_informe_final_tempario_dev.ut,
														sa_v_informe_final_tempario_dev.precio_tempario_tipo_orden,
														sa_v_informe_final_tempario_dev.base_ut_precio,
														sa_v_informe_final_tempario_dev.iva,
														sa_mecanicos.porcentaje_comision,
														'NC' AS tipo_documento
														FROM
														sa_v_informe_final_tempario_dev
														INNER JOIN sa_det_orden_tempario ON (sa_v_informe_final_tempario_dev.id_det_orden_tempario = sa_det_orden_tempario.id_det_orden_tempario)
														INNER JOIN sa_mecanicos ON (sa_det_orden_tempario.id_mecanico = sa_mecanicos.id_mecanico)
														INNER JOIN sa_tempario ON (sa_det_orden_tempario.id_tempario = sa_tempario.id_tempario)
														WHERE
														sa_mecanicos.id_empleado = %s AND 
														sa_v_informe_final_tempario_dev.id_orden = %s",
													$rowMecanico['id_empleado'],
													$rowNumeroOrden['id_orden']);
						}
						else{
							$queryDetalle = sprintf("SELECT
														sa_tempario.codigo_tempario,
														sa_tempario.descripcion_tempario,
														sa_v_vale_informe_final_tempario.ut,
														sa_v_vale_informe_final_tempario.precio_tempario_tipo_orden,
														sa_v_vale_informe_final_tempario.base_ut_precio,
														sa_v_vale_informe_final_tempario.iva,
														sa_mecanicos.porcentaje_comision,
														'VS' AS tipo_documento
													FROM
														sa_v_vale_informe_final_tempario
														INNER JOIN sa_det_vale_salida_tempario ON (sa_v_vale_informe_final_tempario.id_det_vale_salida_tempario = sa_det_vale_salida_tempario.id_det_vale_salida_tempario)
														INNER JOIN sa_mecanicos ON (sa_det_vale_salida_tempario.id_mecanico = sa_mecanicos.id_mecanico)
														INNER JOIN sa_tempario ON (sa_det_vale_salida_tempario.id_tempario = sa_tempario.id_tempario)
													WHERE
														sa_mecanicos.id_empleado = %s AND 
														sa_v_vale_informe_final_tempario.id_orden = %s",
										$rowMecanico['id_empleado'],
										$rowNumeroOrden['id_orden']);
						}
						$rsDetalle = mysql_query($queryDetalle);
						
						if (!$rsDetalle) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
						
						$total_ut_orden = 0;
						$total_mano_obra = 0;
						$iva = 0;
						$total_comision = 0;
						
						while ($rowDetalle = mysql_fetch_array($rsDetalle)){
							$monto_comision = ((($rowDetalle['precio_tempario_tipo_orden'] * $rowDetalle['ut']) / $rowDetalle['base_ut_precio']) * $rowDetalle['porcentaje_comision']) / 100;
							if($tipoListado){
										$html .= "<tr >";//class=\"".$clase."\"
											$html .= "<td align='left'>".$rowDetalle['codigo_tempario']."</td>";
											$html .= "<td align='left'>".utf8_encode($rowDetalle['descripcion_tempario'])."</td>";
											$html .= "<td align='right'>".$rowDetalle['ut']."</td>";
											$html .= "<td align='right'>".$rowDetalle['precio_tempario_tipo_orden']."</td>";
											$html .= "<td align='right'>".$rowDetalle['ut'] * $rowDetalle['precio_tempario_tipo_orden'] / $rowDetalle['base_ut_precio']."</td>";
											$html .= "<td align='right'>".number_format(($rowDetalle['ut'] * $rowDetalle['precio_tempario_tipo_orden'] / $rowDetalle['base_ut_precio']) * $rowDetalle['iva'] / 100,2,".",",")."</td>";
											$html .= "<td align='right'>".$rowDetalle['porcentaje_comision']."</td>";
											$html .= "<td align='right'>".$monto_comision."</td>";
										$html .= "</tr>";
									}
									if ($rowNumeroOrden['tipo_documento'] == "NC"){
										$total_ut_orden -= $rowDetalle['ut'];
										$total_mano_obra -= $rowDetalle['ut'] * $rowDetalle['precio_tempario_tipo_orden'] / $rowDetalle['base_ut_precio'];
										$iva -= $rowDetalle['iva'];
										$total_comision -= $monto_comision;
									}
									else{
										$total_ut_orden += $rowDetalle['ut'];
										$total_mano_obra += $rowDetalle['ut'] * $rowDetalle['precio_tempario_tipo_orden'] / $rowDetalle['base_ut_precio'];
										$iva += $rowDetalle['iva'];
										$total_comision += $monto_comision;
									}
						}
						//$iva /= mysql_num_rows($rsDetalle);
						$iva *= $total_mano_obra / 100;
						if($tipoListado)
							$html .= "<tr>
										<td align=\"right\" class='tituloColumna' colspan=\"1\">Total Orden ".$rowNumeroOrden['id_orden'].":</a></td>
										<td align='right'>UT'S:</td>
										<td align='right'>".number_format($total_ut_orden,2,".",",")."</td>
										<td align='right'></td>
										<td align='right'>".number_format($total_mano_obra,2,".",",")."</td>
										<td align='right'>".number_format($iva,2,".",",")."</td>
										<td align='right'></td>
										<td align='right'>".$total_comision."</td>
									  </tr>";
									  
						$arrayTotalTemparios[$indiceArreglos] += $total_mano_obra;
						$arrayTotalUT[$indiceArreglos] += $total_ut_orden;
						$arrayTotalComision[$indiceArreglos] += $total_comision;
						$total_por_tipo_orden += $total_mano_obra;
					}
				}// FIN CICLO WHILE
			}// FIN CONDICION
			if($tipoListado)
				$html .= "<tr>
							<td align=\"right\" class='trResaltar6 textoNegrita_12px' colspan=\"1\">Total ".utf8_encode($rowTipoOrden['nombre_tipo_orden']).":</a></td>
							<td align='left' colspan=\"7\">".number_format($total_por_tipo_orden,2,".",",")."</td>
						  </tr>";
		}
	}
	
	if(mysql_num_rows($rsLimitTipoOrden) > 0){
		if (mysql_num_rows($rsMecanico) > 0){
			$html .= "<tr>
						<td align=\"center\" class='trResaltar6 textoNegrita_12px' colspan=\"10\">Total Comisiones Por Mecanicos</a></td>
					  </tr>";
					  
			$html .= "<tr class='tituloColumna'>
						<td align=\"right\" colspan=\"3\">Mecanico</a></td>
						<td align=\"right\" colspan=\"2\">Total M.O</a></td>
						<td align=\"right\" colspan=\"2\">Total U.T.</a></td>
						<td align=\"right\" colspan=\"2\">Total Comision</a></td>
					  </tr>";
			
			$total_mano_obra_final = 0;
			
			foreach($arrayIdEmpleado as $indiceIdEmpleado => $valorIdEmpleado){
				$queryNombreMecanico = sprintf("SELECT nombre_empleado, apellido FROM pg_empleado WHERE id_empleado = %s",$valorIdEmpleado);
				$rsNombreMecanico = mysql_query($queryNombreMecanico);
				
				if (!$rsNombreMecanico) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__);
				
				$rowNombreMecanico = mysql_fetch_array($rsNombreMecanico);
				$html .= "<tr>
							<td align=\"left\" colspan=\"3\">".utf8_encode($rowNombreMecanico['nombre_empleado'])." ".utf8_encode($rowNombreMecanico['apellido'])."</a></td>
							<td align=\"right\" colspan=\"2\">".number_format($arrayTotalTemparios[$indiceIdEmpleado],2,'.',',')."</a></td>
							<td align=\"right\" colspan=\"2\">".number_format($arrayTotalUT[$indiceIdEmpleado],2,'.',',')."</a></td>
							<td align=\"right\" colspan=\"2\">".number_format($arrayTotalComision[$indiceIdEmpleado],2,'.',',')."</a></td>
						  </tr>";
			$total_mano_obra_final += $arrayTotalTemparios[$indiceIdEmpleado];
			$total_UT_final += $arrayTotalUT[$indiceIdEmpleado];
			$total_comision_final += $arrayTotalComision[$indiceIdEmpleado];
			}
			
			$html .= "<tr class='tituloColumna'>
							<td align=\"right\" colspan=\"3\">Total:</a></td>
							<td align=\"right\" colspan=\"2\">".number_format($total_mano_obra_final,2,'.',',')."</a></td>
							<td align=\"right\" colspan=\"2\">".number_format($total_UT_final,2,'.',',')."</a></td>
							<td align=\"right\" colspan=\"2\">".number_format($total_comision_final,2,'.',',')."</a></td>
						  </tr>";
			
			$htmlTableFin .= "</table>";
			
			$objResponse->assign("tdComisiones","innerHTML",$htmlTableIni.$html.$htmlTableFin);
		}
		else
			$objResponse->assign("tdComisiones","innerHTML","No Hay Registros");
	}
	else
		$objResponse->assign("tdComisiones","innerHTML","No Hay Registros");
	
	return $objResponse;
}

function listarComisionesGerentes($idEmpleado,$tipoEmpleado,$fechaDesde, $fechaHasta){
	$objResponse = new xajaxResponse();
	
	$arrayFechaDesde = explode("-",$fechaDesde);
	$arrayFechaHasta = explode("-",$fechaHasta);
	
	$fechaFormatoDesde = $arrayFechaDesde[2]."-".$arrayFechaDesde[1]."-".$arrayFechaDesde[0];
	$fechaFormatoHasta = $arrayFechaHasta[2]."-".$arrayFechaHasta[1]."-".$arrayFechaHasta[0];
	
	if (!xvalidaAcceso($objResponse,PAGE_PRIV)){
		$objResponse->assign("tdComisiones","innerHTML","Acceso Denegado");
		return $objResponse;
	}
	
	//ARREGLO CON LAS CLAVES DE FILTRO DE LOS EMPLEADOS
	$arrayEmpleado = array ("501","6","5","300","4");
	
	$claveFiltro = explode("|",$arrayEmpleado[$tipoEmpleado]);
	
	foreach ($claveFiltro as $indiceClaveFiltro => $valorClaveFiltro){
		if ($condicionClaveFiltro == ""){
			$condicionClaveFiltro = "WHERE (clave_filtro = ".$valorClaveFiltro;
		}
		else{
			$condicionClaveFiltro .= " OR clave_filtro = ".$valorClaveFiltro;
		}
	}
	$condicionClaveFiltro .= ")";
	
	if ($idEmpleado != 0)
		$condicionClaveFiltro .= " AND id_empleado = ".$idEmpleado;
	
	$htmlTableIni .= "<table border=\"1\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
	
	$queryEmpleado = sprintf("SELECT pg_empleado.id_empleado, pg_empleado.nombre_empleado, pg_empleado.apellido, pg_empleado.id_cargo_departamento, pg_cargo_departamento.clave_filtro FROM pg_empleado INNER JOIN pg_cargo_departamento ON (pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento) %s ",$condicionClaveFiltro);
	$rsEmpleado = mysql_query($queryEmpleado);
	
	if (!$rsEmpleado) return $objResponse->alert(mysql_error());
	
	while ($rowEmpleado = mysql_fetch_array($rsEmpleado)){
		$totalEmpleadoServicio = 0;
		$totalEmpleadoIva = 0;
		$totalEmpleadoMO = 0;
		$totalEmpleadoTOT = 0;
		$totalEmpleadoNotas = 0;
		$totalEmpleadoRepuesto = 0;
		$totalEmpleadoDescuento = 0;
		$porcentajeComision = 0;
		$totalEmpleadoComision = 0;
		
		$queryPorcentajesComisionesManoObra = sprintf("SELECT porcentaje_comision FROM pg_comisiones WHERE (tipo_comision = 1 OR tipo_comision = 6) AND id_cargo_departamamento = %s ",$rowEmpleado['id_cargo_departamento']);
		$rsPorcentajesComisionesManoObra = mysql_query($queryPorcentajesComisionesManoObra);
		if (!$rsPorcentajesComisionesManoObra) return $objResponse->alert(mysql_error());
		$rowPorcentajesComisionesManoObra = mysql_fetch_array($rsPorcentajesComisionesManoObra);
		$porcentajeComisionManoObra = $rowPorcentajesComisionesManoObra['porcentaje_comision'];
		
		$porcentajeComisionServicio = $porcentajeComisionManoObra;
		
		$queryPorcentajesComisionesRepuestos = sprintf("SELECT porcentaje_comision FROM pg_comisiones WHERE tipo_comision = 2 AND id_cargo_departamamento = %s ",$rowEmpleado['id_cargo_departamento']);
		$rsPorcentajesComisionesRepuestos = mysql_query($queryPorcentajesComisionesRepuestos);
		if (!$rsPorcentajesComisionesRepuestos) return $objResponse->alert(mysql_error());
		$rowPorcentajesComisionesRepuestos = mysql_fetch_array($rsPorcentajesComisionesRepuestos);
		$porcentajeComisionRepuestos = $rowPorcentajesComisionesRepuestos['porcentaje_comision'];
		
		$porcentajeComision = $porcentajeComisionRepuestos;
		
		$queryPorcentajesComisionesTOT = sprintf("SELECT porcentaje_comision FROM pg_comisiones WHERE tipo_comision = 3 AND id_cargo_departamamento = %s ",$rowEmpleado['id_cargo_departamento']);
		$rsPorcentajesComisionesTOT = mysql_query($queryPorcentajesComisionesTOT);
		if (!$rsPorcentajesComisionesTOT) return $objResponse->alert(mysql_error());
		$rowPorcentajesComisionesTOT = mysql_fetch_array($rsPorcentajesComisionesTOT);
		$porcentajeComisionTOT = $rowPorcentajesComisionesTOT['porcentaje_comision'];
		
		$queryIva = sprintf("SELECT iva FROM pg_iva WHERE tipo = 6 AND activo = 1 AND estado = 1");
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return $objResponse->alert(mysql_error());
		$rowIva = mysql_fetch_array($rsIva);
		$iva = $rowIva['iva'];
		
		$queryNombreCargo = sprintf("SELECT nombre_filtro FROM pg_cargo_filtro WHERE filtro = %s",$rowEmpleado['clave_filtro']);
		$rsNombreCargo = mysql_query($queryNombreCargo);
		
		if (!$rsNombreCargo) return $objResponse->alert(mysql_error());
		
		$rowNombreCargo = mysql_fetch_array($rsNombreCargo);
		
		$html .= "<tr>
					<td align=\"right\" class='trResaltar6 textoNegrita_12px' colspan='4'>".utf8_encode($rowNombreCargo['nombre_filtro']).":</a></td>
					<td align='left' colspan='7'>".utf8_encode($rowEmpleado['nombre_empleado'])." ".utf8_encode($rowEmpleado['apellido'])."</td>
				  </tr>";
		
		$html .= "<tr class=\"tituloColumna\">
										<td width='11%'>Fecha.</td>
										<td width='11%'>Importe</td>
										<td width='11%'>Iva</td>
										<td width='11%'>Importe M.O.</td>
										<td width='11%'>Importe TOT</td>
										<td width='11%'>Importe Notas</td>
										<td width='11%'>% Comision M.O.</td>
										<td width='11%'>Importe Repuesto</td>
										<td width='11%'>% Comision R</td>
										<td width='11%'>Descuento</td>
										<td width='11%'>Comision</td>
									</tr>";
		
		if ($rowEmpleado['clave_filtro'] == 5){
			$queryFecha = sprintf("SELECT
									date(fecha_factura) AS fecha_factura,
									YEAR(fecha_factura) as ano,
									MONTH(fecha_factura) as mes,
									DAY(fecha_factura) as dia, 
									sa_cita.id_empleado_servicio as id_asesor
									FROM sa_orden
									INNER JOIN sa_recepcion 
									ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
									INNER JOIN sa_cita 
									ON (sa_recepcion.id_cita = sa_cita.id_cita)
									WHERE 
									fecha_factura BETWEEN '%s' AND '%s'
									AND id_empleado_servicio = %s
									UNION
									SELECT
									date(fecha_vale) AS fecha_factura,
									YEAR(fecha_vale) as ano,
									MONTH(fecha_vale) as mes,
									DAY(fecha_vale) as dia, 
									sa_cita.id_empleado_servicio as id_asesor
									FROM sa_vale_salida
									INNER JOIN sa_orden
									on (sa_vale_salida.id_orden = sa_orden.id_orden)
									INNER JOIN sa_recepcion 
									ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
									INNER JOIN sa_cita 
									ON (sa_recepcion.id_cita = sa_cita.id_cita)
									WHERE 
									fecha_vale BETWEEN '%s' AND '%s'
									AND id_empleado_servicio = %s
									GROUP BY dia ORDER BY dia ASC",
									$fechaFormatoDesde,
									$fechaFormatoHasta,
									$rowEmpleado['id_empleado'],
									$fechaFormatoDesde,
									$fechaFormatoHasta,
									$rowEmpleado['id_empleado']);
		} 
		else{
			$queryFecha = sprintf("SELECT
										DATE(fecha_factura) as fecha_factura,
										YEAR(fecha_factura) as ano,
										MONTH(fecha_factura) as mes,
										DAY(fecha_factura) as dia
									FROM sa_orden
									WHERE 
										fecha_factura BETWEEN '%s' AND '%s'
									UNION
									SELECT
										DATE(fecha_vale) as fecha_factura,
										YEAR(fecha_vale) as ano,
										MONTH(fecha_vale) as mes,
										DAY(fecha_vale) as dia
									FROM sa_vale_salida
									WHERE
										fecha_vale BETWEEN '%s' AND '%s'
									ORDER BY dia",
									$fechaFormatoDesde,
									$fechaFormatoHasta,
									$fechaFormatoDesde,
									$fechaFormatoHasta);
		}
		$rsFecha = mysql_query($queryFecha);
		
		if (!$rsFecha) return $objResponse->alert(mysql_error());
		
		while($rowFecha = mysql_fetch_array($rsFecha)){
			$totalServicioDia = 0;
			$totalTOTDia = 0;
			$totalNotasDia = 0;
			$totalRepuestoDia = 0;
			$totalDescuentoDia = 0;
			$totalIvaDia = 0;
			$totalComisionServicio = 0;
			$totalComisionRepuesto = 0;
			$totalComisionTOT = 0;
			$totalComisionNota = 0;
			$totalDescuento = 0;
			
			if ($rowEmpleado['clave_filtro'] == 5){
				$queryDetalleManoObra = sprintf("SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(((precio_tempario_tipo_orden * ut) / base_ut_precio)) as monto_mano_obra,
													'FA' as documento
												FROM
													sa_v_informe_final_tempario
												INNER JOIN sa_orden ON (sa_v_informe_final_tempario.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND id_empleado_servicio = %s
												GROUP BY
													fecha_filtro
												union
												SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(((precio_tempario_tipo_orden * ut) / base_ut_precio)) AS monto_mano_obra,
													'VS' as documento
												FROM
													sa_v_vale_informe_final_tempario
												INNER JOIN sa_orden ON (sa_v_vale_informe_final_tempario.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND 
													id_empleado_servicio = %s
												GROUP BY
													fecha_filtro
												UNION
												SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(((precio_tempario_tipo_orden * ut) / base_ut_precio)) as monto_mano_obra,
													'NC' as documento
												FROM
													sa_v_informe_final_tempario_dev
												INNER JOIN sa_orden ON (sa_v_informe_final_tempario_dev.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND id_empleado_servicio = %s
												GROUP BY
													fecha_filtro",
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado'],
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado'],
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado']);
				
				$queryDetalleRepuesto = sprintf("SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(precio_unitario * cantidad) as monto_repuesto,
													'FA' as documento
												FROM
													sa_v_informe_final_repuesto
												INNER JOIN sa_orden ON (sa_v_informe_final_repuesto.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro
												union
												SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(precio_unitario * cantidad) AS monto_repuesto,
													'VS' as documento
												FROM
													sa_v_vale_informe_final_repuesto
												INNER JOIN sa_orden ON (sa_v_vale_informe_final_repuesto.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND 
													id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro
												UNION
												SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(precio_unitario * cantidad) as monto_repuesto,
													'NC' as documento
												FROM
													sa_v_informe_final_repuesto_dev
												INNER JOIN sa_orden ON (sa_v_informe_final_repuesto_dev.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro",
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado'],
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado'],
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado']);
				
				$queryDetalleTOT = sprintf("SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(monto_total + ((porcentaje_tot * monto_total) / 100)) as monto_tot,
													'FA' as documento
												FROM
													sa_v_informe_final_tot
												INNER JOIN sa_orden ON (sa_v_informe_final_tot.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro
												union
												SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(monto_total + ((porcentaje_tot * monto_total) / 100)) as monto_tot,
													'VS' as documento
												FROM
													sa_v_vale_informe_final_tot
												INNER JOIN sa_orden ON (sa_v_vale_informe_final_tot.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND 
													id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro
												UNION
												SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(monto_total) as monto_tot,
													'NC' as documento
												FROM
													sa_v_informe_final_tot_dev
												INNER JOIN sa_orden ON (sa_v_informe_final_tot_dev.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro",
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado'],
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado'],
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado']);
				
				$queryDetalleNota = sprintf("SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(precio) as monto_nota,
													'FA' as documento
												FROM
													sa_v_informe_final_notas
												INNER JOIN sa_orden ON (sa_v_informe_final_notas.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro
												union
												SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(precio) as monto_nota,
													'VS' as documento
												FROM
													sa_v_vale_informe_final_notas
												INNER JOIN sa_orden ON (sa_v_vale_informe_final_notas.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND 
													id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro
												UNION
												SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM(precio) as monto_nota,
													'NC' as documento
												FROM
													sa_v_informe_final_notas_dev
												INNER JOIN sa_orden ON (sa_v_informe_final_notas_dev.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro",
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado'],
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado'],
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado']);
				
				$queryDetalleDescuento = sprintf("SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM((precio_unitario * cantidad) * porcentaje_descuento_orden / 100) AS monto_descuento,
													'FA' as documento
												FROM
													sa_v_informe_final_repuesto
												INNER JOIN sa_orden ON (sa_v_informe_final_repuesto.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro
												union
												SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM((precio_unitario * cantidad) * porcentaje_descuento_orden / 100) AS monto_descuento,
													'VS' as documento
												FROM
													sa_v_vale_informe_final_repuesto
												INNER JOIN sa_orden ON (sa_v_vale_informe_final_repuesto.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND 
													id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro
												UNION
												SELECT 
													sa_orden.id_orden,
													sa_cita.id_empleado_servicio,
													SUM((precio_unitario * cantidad) * porcentaje_descuento_orden / 100) AS monto_descuento,
													'NC' as documento
												FROM
													sa_v_informe_final_repuesto_dev
												INNER JOIN sa_orden ON (sa_v_informe_final_repuesto_dev.id_orden = sa_orden.id_orden)
												INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
												INNER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
												WHERE
													fecha_filtro = '%s' AND id_empleado_servicio = %s AND aprobado = 1
												GROUP BY
													fecha_filtro",
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado'],
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado'],
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])), $rowEmpleado['id_empleado']);
			}
			else{
				$queryDetalleManoObra = sprintf("SELECT
												SUM(((precio_tempario_tipo_orden * ut) / base_ut_precio)) as monto_mano_obra,
												'FA' as documento
											FROM
											sa_v_informe_final_tempario
											WHERE
												fecha_filtro = '%s'
											UNION
											SELECT 
											  SUM(((sa_v_vale_informe_final_tempario.precio_tempario_tipo_orden * sa_v_vale_informe_final_tempario.ut) / 
													sa_v_vale_informe_final_tempario.base_ut_precio)) AS monto_mano_obra,
												'VS' as documento
											FROM
											  sa_v_vale_informe_final_tempario
											WHERE
											  fecha_filtro = '%s'
											UNION
											SELECT
												SUM(((precio_tempario_tipo_orden * ut) / base_ut_precio)) as monto_mano_obra,
												'NC' as documento
											FROM
												sa_v_informe_final_tempario_dev
											WHERE
											fecha_filtro = '%s'
											GROUP BY
											  fecha_filtro",
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])),
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])),
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])));
				
				$queryDetalleRepuesto = sprintf("SELECT
												SUM(precio_unitario * cantidad) as monto_repuesto,
												'FA' as documento
											FROM
												sa_v_informe_final_repuesto
											WHERE
												fecha_filtro = '%s' AND aprobado = 1
											UNION
											SELECT 
												SUM(precio_unitario * cantidad) AS monto_repuesto,
												'VS' as documento
											FROM
												sa_v_vale_informe_final_repuesto
											WHERE
												fecha_filtro = '%s' AND aprobado = 1
											UNION
											SELECT
												SUM(precio_unitario * cantidad) as monto_repuesto,
												'NC' as documento
											FROM
												sa_v_informe_final_repuesto_dev
											WHERE
												fecha_filtro = '%s' AND aprobado = 1
											GROUP BY
												fecha_filtro",
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])),
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])),
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])));
				
				$queryDetalleTOT = sprintf("SELECT
												SUM(monto_total + ((porcentaje_tot * monto_total) / 100)) as monto_tot,
												'FA' as documento
											FROM
												sa_v_informe_final_tot
											WHERE
												fecha_filtro = '%s' AND aprobado = 1
											UNION
											SELECT 
												SUM(monto_total + ((porcentaje_tot * monto_total) / 100)) as monto_tot,
												'VS' as documento
											FROM
												sa_v_vale_informe_final_tot
											WHERE
												fecha_filtro = '%s' AND aprobado = 1
											UNION
											SELECT
												SUM(monto_total) as monto_tot,
												'NC' as documento
											FROM
												sa_v_informe_final_tot_dev
											WHERE
												fecha_filtro = '%s' AND aprobado = 1
											GROUP BY
												fecha_filtro",
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])),
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])),
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])));
				
				$queryDetalleNota = sprintf("SELECT
												SUM(precio) as monto_nota,
												'FA' as documento
											FROM
												sa_v_informe_final_notas
											WHERE
												fecha_filtro = '%s' AND aprobado = 1
											UNION
											SELECT 
												SUM(precio) as monto_nota,
												'VS' as documento
											FROM
												sa_v_vale_informe_final_notas
											WHERE
												fecha_filtro = '%s' AND aprobado = 1
											UNION
											SELECT
												SUM(precio) as monto_nota,
												'NC' as documento
											FROM
												sa_v_informe_final_notas_dev
											WHERE
												fecha_filtro = '%s' AND aprobado = 1
											GROUP BY
												fecha_filtro",
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])),
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])),
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])));
				
				$queryDetalleDescuento = sprintf("SELECT 
											  SUM((precio_unitario * cantidad) * porcentaje_descuento_orden / 100) AS monto_descuento,
											  'FA' AS documento
											FROM
											  sa_v_informe_final_repuesto
											WHERE
											  fecha_filtro = '%s' AND 
											  aprobado = 1
											UNION
											SELECT 
											  SUM((precio_unitario * cantidad) * porcentaje_descuento_orden / 100) AS monto_descuento,
											  'VS' AS documento
											FROM
											  sa_v_vale_informe_final_repuesto
											WHERE
											  fecha_filtro = '%s' AND 
											  aprobado = 1
											UNION
											SELECT 
											  SUM((precio_unitario * cantidad) * porcentaje_descuento_orden / 100) AS monto_descuento,
											  'NC' AS documento
											FROM
											  sa_v_informe_final_repuesto_dev
											WHERE
											  fecha_filtro = '%s' AND 
											  aprobado = 1",
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])),
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])),
											date('Y-m-d',strtotime($rowFecha['fecha_factura'])));
			}
			
			$rsDetalleManoObra = mysql_query($queryDetalleManoObra);
			$rsDetalleRepuesto = mysql_query($queryDetalleRepuesto);
			$rsDetalleTOT = mysql_query($queryDetalleTOT);
			$rsDetalleNota = mysql_query($queryDetalleNota);
			$rsDetalleDescuento = mysql_query($queryDetalleDescuento);
			
			if (!$rsDetalleManoObra) return $objResponse->alert(mysql_error());
			if (!$rsDetalleRepuesto) return $objResponse->alert(mysql_error());
			if (!$rsDetalleTOT) return $objResponse->alert(mysql_error());
			if (!$rsDetalleNota) return $objResponse->alert(mysql_error());
			if (!$rsDetalleDescuento) return $objResponse->alert(mysql_error());
			
			//COMISIONES POR MANO DE OBRA
			while ($rowDetalleManoObra = mysql_fetch_array($rsDetalleManoObra)){				
				if ($rowDetalleManoObra['documento'] == "NC")
					$totalServicioDia -= $rowDetalleManoObra['monto_mano_obra'];
				else
					$totalServicioDia += $rowDetalleManoObra['monto_mano_obra'];
			}
			$totalComisionServicio = $totalServicioDia * $porcentajeComisionManoObra / 100;
			
			//COMISIONES POR REPUESTO
			while ($rowDetalleRepuesto = mysql_fetch_array($rsDetalleRepuesto)){				
				if ($rowDetalleRepuesto['documento'] == "NC")
					$totalRepuestoDia -= $rowDetalleRepuesto['monto_repuesto'];
				else
					$totalRepuestoDia += $rowDetalleRepuesto['monto_repuesto'];
			}
			$totalComisionRepuesto = $totalRepuestoDia * $porcentajeComisionRepuestos / 100;
			
			//COMISIONES POR TOT
			while ($rowDetalleTOT = mysql_fetch_array($rsDetalleTOT)){				
				if ($rowDetalleTOT['documento'] == "NC")
					$totalTOTDia -= $rowDetalleTOT['monto_tot'];
				else
					$totalTOTDia += $rowDetalleTOT['monto_tot'];
			}
			$totalComisionTOT = $totalTOTDia * $porcentajeComisionTOT / 100;
			
			//COMISIONES POR NOTAS
			while ($rowDetalleNotas = mysql_fetch_array($rsDetalleNota)){				
				if ($rowDetalleNotas['documento'] == "NC")
					$totalNotasDia -= $rowDetalleNotas['monto_nota'];
				else
					$totalNotasDia += $rowDetalleNotas['monto_nota'];
			}
			$totalComisionNota = $totalNotasDia * $porcentajeComisionNota / 100;
			
			//COMISIONES POR DESCUENTOS
			while ($rowDetalleDescuentos = mysql_fetch_array($rsDetalleDescuento)){
				if ($rowDetalleDescuentos['documento'] == "NC")
					$totalDescuentoDia -= $rowDetalleDescuentos['monto_descuento'];
				else
					$totalDescuentoDia += $rowDetalleDescuentos['monto_descuento'];
			}
			
			$totalComisionRepuesto -= $totalDescuentoDia * $porcentajeComisionRepuestos / 100;
								
			$totalOrdenDia = $totalServicioDia + $totalRepuestoDia + $totalTOTDia + $totalNotasDia - $totalDescuentoDia;
			$totalIvaDia = $totalOrdenDia * $iva / 100;
			$totalComisionIva = $totalComisionServicio + $totalComisionRepuesto + $totalComisionTOT + $totalComisionNota;
						
			$html .= "<tr>
						<td >".date("dmY",strtotime($rowFecha['fecha_factura']))."</td>
						<td align='right'>".number_format($totalOrdenDia,"2",".",",")."</td>
						<td align='right'>".number_format($totalIvaDia,"2",".",",")."</td>
						<td align='right'>".number_format($totalServicioDia,"2",".",",")."</td>
						<td align='right'>".number_format($totalTOTDia,"2",".",",")."</td>
						<td align='right'>".number_format($totalNotasDia,"2",".",",")."</td>
						<td align='right'>".number_format($porcentajeComisionServicio,"2",".",",")."</td>
						<td align='right'>".number_format($totalRepuestoDia,"2",".",",")."</td>
						<td align='right'>".number_format($porcentajeComision,"2",".",",")."</td>
						<td align='right'>".number_format($totalDescuentoDia,"2",".",",")."</td>
						<td align='right'>".number_format($totalComisionIva,"2",".",",")."</td>
					</tr>";
			
			$totalEmpleadoServicio += $totalOrdenDia;
			$totalEmpleadoIva += $totalIvaDia;
			$totalEmpleadoMO += $totalServicioDia;
			$totalEmpleadoTOT += $totalTOTDia;
			$totalEmpleadoNotas += $totalNotasDia;
			$totalEmpleadoRepuesto += $totalRepuestoDia;
			$totalEmpleadoDescuento += $totalDescuentoDia;
			$totalEmpleadoComision += $totalComisionIva;
		}
		
		$totalAcumuladoServicio += $totalEmpleadoServicio;
		$totalAcumuladoTOT += $totalEmpleadoTOT;
		$totalAcumuladoNotas += $totalEmpleadoNotas;
		$totalAcumuladoIva += $totalEmpleadoIva;
		$totalAcumuladoMO += $totalEmpleadoMO;
		$totalAcumuladoRepuesto += $totalEmpleadoRepuesto;
		$totalAcumuladoDescuento += $totalEmpleadoDescuento;
		$totalAcumuladoComision += $totalEmpleadoComision;
			
		$html .= "<tr class=\"tituloColumna\">
										<td >Total</td>
										<td align='right'>".number_format($totalEmpleadoServicio,"2",".",",")."</td>
										<td align='right'>".number_format($totalEmpleadoIva,"2",".",",")."</td>
										<td align='right'>".number_format($totalEmpleadoMO,"2",".",",")."</td>
										<td align='right'>".number_format($totalEmpleadoTOT,"2",".",",")."</td>
										<td align='right'>".number_format($totalEmpleadoNotas,"2",".",",")."</td>
										<td align='right'>".number_format($porcentajeComisionServicio,"2",".",",")."</td>
										<td align='right'>".number_format($totalEmpleadoRepuesto,"2",".",",")."</td>
										<td align='right'>".number_format($porcentajeComision,"2",".",",")."</td>
										<td align='right'>".number_format($totalEmpleadoDescuento,"2",".",",")."</td>
										<td align='right'>".number_format($totalEmpleadoComision,"2",".",",")."</td>
									</tr>";
		
		$html .= "<tr class=\"tituloColumna\">
										<td >Total Acumulado</td>
										<td align='right'>".number_format($totalAcumuladoServicio,"2",".",",")."</td>
										<td align='right'>".number_format($totalAcumuladoIva,"2",".",",")."</td>
										<td align='right'>".number_format($totalAcumuladoMO,"2",".",",")."</td>
										<td align='right'>".number_format($totalAcumuladoTOT,"2",".",",")."</td>
										<td align='right'>".number_format($totalAcumuladoNotas,"2",".",",")."</td>
										<td align='right'>".number_format($porcentajeComisionServicio,"2",".",",")."</td>
										<td align='right'>".number_format($totalAcumuladoRepuesto,"2",".",",")."</td>
										<td align='right'>".number_format($porcentajeComision,"2",".",",")."</td>
										<td align='right'>".number_format($totalAcumuladoDescuento,"2",".",",")."</td>
										<td align='right'>".number_format($totalAcumuladoComision,"2",".",",")."</td>
									</tr>";
	}
	
	$htmlTableFin .= "</table>";
	
	$objResponse->assign("tdComisiones","innerHTML",$htmlTableIni.$html.$htmlTableFin);
	
	return $objResponse;
}


primera
function listarComisionesMecanicos($idMecanico,$tipoListado,$fecha){
	$objResponse = new xajaxResponse();
	
	$arrayFecha = explode("-",$fecha);
	
	$fechaFormatoMysql = $arrayFecha[1]."-".$arrayFecha[0]."-";
	
	$ultimoDiaMes = strftime("%d", mktime(0, 0, 0, $arrayFecha[0]+1, 0, $arrayFecha[1]));
	
	if (!xvalidaAcceso($objResponse,PAGE_PRIV)){
		$objResponse->assign("tdComisiones","innerHTML","Acceso Denegado");
		return $objResponse;
	}
	
	if ($idMecanico == 0)
		$condicionMecanico = " WHERE ";
	else
		$condicionMecanico = " WHERE id_empleado = ".$idMecanico." AND ";
	
	//AGRUPAR POR EL TIPO DE ORDEN
	$queryTipoOrden = "SELECT id_tipo_orden, nombre_tipo_orden, fechaRegistroFactura FROM vw_sa_comisiones_mecanicos WHERE (fechaRegistroFactura BETWEEN \"".$fechaFormatoMysql."01\" AND \"".$fechaFormatoMysql.$ultimoDiaMes."\") GROUP BY id_tipo_orden";
	$rsLimitTipoOrden = mysql_query($queryTipoOrden);
	
	if (!$rsLimitTipoOrden) return $objResponse->alert(mysql_error());
	
	$htmlTableIni .= "<table border=\"1\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
	
	if (mysql_num_rows($rsLimitTipoOrden) > 0){
		while ($rowTipoOrden = mysql_fetch_assoc($rsLimitTipoOrden)){
			$total_por_tipo_orden = 0;
			
			if ($tipoListado)
				$html .= "<tr>
							<td align=\"right\" class='trResaltar6 textoNegrita_12px' colspan=\"1\" width='12%'>Tipo Orden:</a></td>
							<td align='left' colspan=\"7\" width='89%'>".utf8_encode($rowTipoOrden['nombre_tipo_orden'])."</td>
						  </tr>";
	
			//AGRUPAR POR MECANICO
			$queryMecanico = "SELECT * FROM vw_sa_comisiones_mecanicos".$condicionMecanico." id_tipo_orden = ".$rowTipoOrden['id_tipo_orden']." AND (fechaRegistroFactura BETWEEN \"".$fechaFormatoMysql."01\" AND \"".$fechaFormatoMysql.$ultimoDiaMes."\") GROUP BY id_empleado";
			$rsMecanico = mysql_query($queryMecanico);
			
			if (!$rsMecanico) return $objResponse->alert(mysql_error());
			
			if (mysql_num_rows($rsMecanico) > 0){
				while ($rowMecanico = mysql_fetch_array($rsMecanico)){
					$indiceArreglos = 0;
					
					while ($indiceArreglos < count($arrayIdEmpleado) && $arrayIdEmpleado[$indiceArreglos] != $rowMecanico['id_empleado'])
						$indiceArreglos++;
					
					if ($indiceArreglos == count($arrayIdEmpleado))
						$arrayIdEmpleado[] = $rowMecanico['id_empleado'];
						
					//AGRUPAR POR NUMERO DE ORDEN
					$queryNumeroOrden = "SELECT * FROM vw_sa_comisiones_mecanicos WHERE id_empleado = ".$rowMecanico['id_empleado']." AND id_tipo_orden = ".$rowTipoOrden['id_tipo_orden']." AND (fechaRegistroFactura BETWEEN \"".$fechaFormatoMysql."01\" AND \"".$fechaFormatoMysql.$ultimoDiaMes."\") GROUP BY id_orden, tipo_documento";
					$rsNumeroOrden = mysql_query($queryNumeroOrden);
					
					if (!$rsNumeroOrden) return $objResponse->alert(mysql_error());
					
					while ($rowNumeroOrden = mysql_fetch_array($rsNumeroOrden)){
						$queryAsesor = "SELECT nombre_empleado as nombre_asesor, apellido as apellido_asesor FROM pg_empleado WHERE id_empleado = ".$rowNumeroOrden['id_asesor'];
						$rsAsesor = mysql_query($queryAsesor);
						
						if (!$rsAsesor) return $objResponse->alert(mysql_error());
						
						$rowAsesor = mysql_fetch_array($rsAsesor);
						//YA SE VAN A IMPRIMIR POR ORDEN
							if($tipoListado){
								if ($rowNumeroOrden['tipo_documento'] == "FA")
									$tipoDocumento = "Factura";
								else if ($rowNumeroOrden['tipo_documento'] == "VS")
									$tipoDocumento = "Vale Salida";
								else
									$tipoDocumento = "Nota Credito";
								
								$html .= "<tr>
										<td align=\"right\" class='tituloColumna' width='12%'>Mecanico:</a></td>
										<td align='left' colspan=\"1\" width='25%'>".utf8_encode($rowNumeroOrden['nombre_empleado'])." ".utf8_encode($rowNumeroOrden['apellido_empleado'])."</td>
										<td align=\"right\" class='tituloColumna'>Asesor:</a></td>
										<td align='left' colspan=\"2\">".utf8_encode($rowAsesor['nombre_asesor'])." ".utf8_encode($rowAsesor['apellido_asesor'])."</td>
										<td align=\"right\" class='tituloColumna'  width='5%'>Cliente:</a></td>
										<td align='left' colspan=\"2\">".utf8_encode($rowNumeroOrden['nombre_cliente'])." ".utf8_encode($rowNumeroOrden['apellido_cliente'])."</td>
									</tr>
									<tr>
										<td align=\"right\" class='tituloColumna'>Orden:</td>
										<td align='left'>".$rowNumeroOrden['id_orden']."</td>
										<td align=\"right\" class='tituloColumna'>Fecha Orden:</td>
										<td align='left'>".date("d-m-Y",strtotime($rowNumeroOrden['fecha_orden']))."</td>
										<td align=\"right\" class='tituloColumna'>".$tipoDocumento.":</td>
										<td align='left'>".$rowNumeroOrden['id_factura']."</td>
										<td align=\"right\" class='tituloColumna' width='12%'>Fecha ".$tipoDocumento.":</td>
										<td align='left'>".date("d-m-Y",strtotime($rowNumeroOrden['fechaRegistroFactura']))."</td>
									</tr>";
								
									$html .= "<tr class=\"tituloColumna\">
													<td width='12%'>Cod. Tem.</td>
													<td width='25%'>Descripci".utf8_encode("ó")."n Tempario</td>
													<td width='10%'>UT</td>
													<td width='10%'>Valor M.O.</td>
													<td width='10%'>Precio</td>
													<td width='5%'>Iva</td>
													<td width='10%'>% Comision</td>
													<td width='10%'>Comision</td>
												</tr>";
							}
						
						$queryDetalle = "SELECT * FROM vw_sa_comisiones_mecanicos WHERE id_empleado = ".$rowMecanico['id_empleado']." AND id_orden = ".$rowNumeroOrden['id_orden']." AND tipo_documento = '".$rowNumeroOrden['tipo_documento']."'";
						$rsDetalle = mysql_query($queryDetalle);
						
						if (!$rsDetalle) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
						
						$total_ut_orden = 0;
						$total_mano_obra = 0;
						$iva = 0;
						$total_comision = 0;
						while ($rowDetalle = mysql_fetch_array($rsDetalle)){
									if($tipoListado){
										$html .= "<tr >";//class=\"".$clase."\"
											$html .= "<td align='left'>".$rowDetalle['codigo_tempario']."</td>";
											$html .= "<td align='left'>".utf8_encode($rowDetalle['descripcion_tempario'])."</td>";
											$html .= "<td align='right'>".$rowDetalle['ut']."</td>";
											$html .= "<td align='right'>".$rowDetalle['precio_tempario_tipo_orden']."</td>";
											$html .= "<td align='right'>".$rowDetalle['ut'] * $rowDetalle['precio_tempario_tipo_orden'] / $rowDetalle['base_ut_precio']."</td>";
											$html .= "<td align='right'>".number_format(($rowDetalle['ut'] * $rowDetalle['precio_tempario_tipo_orden'] / $rowDetalle['base_ut_precio']) * $rowDetalle['iva'] / 100,2,".",",")."</td>";
											$html .= "<td align='right'>".$rowDetalle['porcentaje_comision']."</td>";
											$html .= "<td align='right'>".$rowDetalle['monto_comision']."</td>";
										$html .= "</tr>";
									}
									if ($rowDetalle['tipo_documento'] == "NC"){
										$total_ut_orden -= $rowDetalle['ut'];
										$total_mano_obra -= $rowDetalle['ut'] * $rowDetalle['precio_tempario_tipo_orden'] / $rowDetalle['base_ut_precio'];
										$iva -= $rowDetalle['iva'];
										$total_comision -= $rowDetalle['monto_comision'];
									}
									else{
										$total_ut_orden += $rowDetalle['ut'];
										$total_mano_obra += $rowDetalle['ut'] * $rowDetalle['precio_tempario_tipo_orden'] / $rowDetalle['base_ut_precio'];
										$iva += $rowDetalle['iva'];
										$total_comision += $rowDetalle['monto_comision'];
									}

						}
						$iva /= mysql_num_rows($rsDetalle);
						$iva *= $total_mano_obra / 100;
						if($tipoListado)
							$html .= "<tr>
										<td align=\"right\" class='tituloColumna' colspan=\"1\">Total Orden ".$rowNumeroOrden['id_orden'].":</a></td>
										<td align='right'>UT'S:</td>
										<td align='right'>".number_format($total_ut_orden,2,".",",")."</td>
										<td align='right'></td>
										<td align='right'>".number_format($total_mano_obra,2,".",",")."</td>
										<td align='right'>".number_format($iva,2,".",",")."</td>
										<td align='right'></td>
										<td align='right'>".$total_comision."</td>
									  </tr>";
						
						$arrayTotalTemparios[$indiceArreglos] += $total_mano_obra;
						$arrayTotalComision[$indiceArreglos] += $total_comision;
						
					}
					$total_por_tipo_orden += $total_mano_obra;
				}// FIN CICLO WHILE
			}// FIN CONDICION
			if($tipoListado)
				$html .= "<tr>
							<td align=\"right\" class='trResaltar6 textoNegrita_12px' colspan=\"1\">Total ".utf8_encode($rowTipoOrden['nombre_tipo_orden']).":</a></td>
							<td align='left' colspan=\"7\">".number_format($total_por_tipo_orden,2,".",",")."</td>
						  </tr>";
		}
	}
	
	if(mysql_num_rows($rsLimitTipoOrden) > 0){
		if (mysql_num_rows($rsMecanico) > 0){
			$html .= "<tr>
						<td align=\"center\" class='trResaltar6 textoNegrita_12px' colspan=\"8\">Total Comisiones Por Mecanicos</a></td>
					  </tr>";
					  
			$html .= "<tr>
						<td align=\"right\" class='tituloColumna' colspan=\"4\">Mecanico</a></td>
						<td align=\"right\" class='tituloColumna' colspan=\"2\">Total M.O</a></td>
						<td align=\"right\" class='tituloColumna' colspan=\"2\">Total Comision</a></td>
					  </tr>";
			
			foreach($arrayIdEmpleado as $indiceIdEmpleado => $valorIdEmpleado){
				$queryNombreMecanico = sprintf("SELECT nombre_empleado, apellido FROM pg_empleado WHERE id_empleado = %s",$valorIdEmpleado);
				$rsNombreMecanico = mysql_query($queryNombreMecanico) or die(mysql_error());
				$rowNombreMecanico = mysql_fetch_array($rsNombreMecanico);
				$html .= "<tr>
							<td align=\"left\" colspan=\"4\">".utf8_encode($rowNombreMecanico['nombre_empleado'])." ".utf8_encode($rowNombreMecanico['apellido'])."</a></td>
							<td align=\"right\" colspan=\"2\">".number_format($arrayTotalTemparios[$indiceIdEmpleado],2,'.',',')."</a></td>
							<td align=\"right\" colspan=\"2\">".number_format($arrayTotalComision[$indiceIdEmpleado],2,'.',',')."</a></td>
						  </tr>";
			}
			
			$htmlTableFin .= "</table>";
			
			$objResponse->assign("tdComisiones","innerHTML",$htmlTableIni.$html.$htmlTableFin);
		}
		else
			$objResponse->assign("tdComisiones","innerHTML","No Hay Registros");
	}
	else
		$objResponse->assign("tdComisiones","innerHTML","No Hay Registros");
		
	return $objResponse;
}
function listarComisionesGerentes($idEmpleado,$tipoEmpleado,$fecha){
	$objResponse = new xajaxResponse();
	
	$arrayFecha = explode("-",$fecha);
	
	$fechaFormatoMysql = $arrayFecha[1]."-".$arrayFecha[0]."-";
	
	$ultimoDiaMes = strftime("%d", mktime(0, 0, 0, $arrayFecha[0]+1, 0, $arrayFecha[1]));
	
	if (!xvalidaAcceso($objResponse,PAGE_PRIV)){
		$objResponse->assign("tdComisiones","innerHTML","Acceso Denegado");
		return $objResponse;
	}
	
	//ARREGLO CON LAS CLAVES DE FILTRO DE LOS EMPLEADOS
	$arrayEmpleado = array ("501","6","5","300","4");
	
	$claveFiltro = explode("|",$arrayEmpleado[$tipoEmpleado]);
	
	foreach ($claveFiltro as $indiceClaveFiltro => $valorClaveFiltro){
		if ($condicionClaveFiltro == ""){
			$condicionClaveFiltro = "WHERE (clave_filtro = ".$valorClaveFiltro;
		}
		else{
			$condicionClaveFiltro .= " OR clave_filtro = ".$valorClaveFiltro;
		}
	}
	$condicionClaveFiltro .= ")";
	
	if ($idEmpleado != 0)
		$condicionClaveFiltro .= " AND id_empleado = ".$idEmpleado;
	
	$htmlTableIni .= "<table border=\"1\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
	
	$queryEmpleado = sprintf("SELECT * FROM vw_sa_comisiones_asesores_gerentes %s GROUP BY id_empleado",$condicionClaveFiltro);
	$rsEmpleado = mysql_query($queryEmpleado);
	
	if (!$rsEmpleado) return $objResponse->alert(mysql_error());
	
	while ($rowEmpleado = mysql_fetch_array($rsEmpleado)){
		$totalEmpleadoServicio = 0;
		$totalEmpleadoIva = 0;
		$totalEmpleadoMO = 0;
		$totalEmpleadoTOT = 0;
		$totalEmpleadoNotas = 0;
		$totalEmpleadoRepuesto = 0;
		$totalEmpleadoDescuento = 0;
		$porcentajeComision = 0;
		$totalEmpleadoComision = 0;
		
		$queryNombreCargo = sprintf("SELECT nombre_filtro FROM pg_cargo_filtro WHERE filtro = %s",$rowEmpleado['clave_filtro']);
		$rsNombreCargo = mysql_query($queryNombreCargo);
		
		if (!$rsNombreCargo) return $objResponse->alert(mysql_error());
		
		$rowNombreCargo = mysql_fetch_array($rsNombreCargo);
		
		$html .= "<tr>
					<td align=\"right\" class='trResaltar6 textoNegrita_12px' colspan='4'>".utf8_encode($rowNombreCargo['nombre_filtro']).":</a></td>
					<td align='left' colspan='7'>".utf8_encode($rowEmpleado['nombre_empleado'])." ".utf8_encode($rowEmpleado['apellido_empleado'])."</td>
				  </tr>";
		
		$html .= "<tr class=\"tituloColumna\">
										<td width='11%'>Fecha.</td>
										<td width='11%'>Importe</td>
										<td width='11%'>Iva</td>
										<td width='11%'>Importe M.O.</td>
										<td width='11%'>Importe TOT</td>
										<td width='11%'>Importe Notas</td>
										<td width='11%'>% Comision M.O.</td>
										<td width='11%'>Importe Repuesto</td>
										<td width='11%'>% Comision R</td>
										<td width='11%'>Descuento</td>
										<td width='11%'>Comision</td>
									</tr>";
		
		$queryFecha = sprintf("SELECT fecha_factura, YEAR(fecha_factura) as ano, MONTH(fecha_factura) as mes, DAY(fecha_factura) as dia FROM vw_sa_comisiones_asesores_gerentes WHERE (fecha_factura BETWEEN \"".$fechaFormatoMysql."01 00:00:00\" AND \"".$fechaFormatoMysql.$ultimoDiaMes." 23:59:59\") AND id_empleado = %s GROUP BY ano, mes, dia ORDER BY fecha_factura ASC",$rowEmpleado['id_empleado']);
		$rsFecha = mysql_query($queryFecha);
		
		if (!$rsFecha) return $objResponse->alert(mysql_error());
		
		while($rowFecha = mysql_fetch_array($rsFecha)){
			$queryDetalle = sprintf("SELECT * FROM vw_sa_comisiones_asesores_gerentes WHERE fecha_factura BETWEEN '%s' AND '%s' AND id_empleado = %s AND id_modulo = 1",date('Y-m-d',strtotime($rowFecha['fecha_factura']))." 00:00:00",date('Y-m-d',strtotime($rowFecha['fecha_factura']))." 23:59:59",$rowEmpleado['id_empleado']);
			$rsDetalle = mysql_query($queryDetalle);

			if (!$rsDetalle) return $objResponse->alert(mysql_error());
			
			$totalServicioDia = 0;
			$totalTOTDia = 0;
			$totalNotasDia = 0;
			$totalRepuestoDia = 0;
			$totalDescuentoDia = 0;
			$totalIvaDia = 0;
			$totalComisionServicio = 0;
			$totalComisionRepuesto = 0;
			$totalDescuento = 0;
			
			$array_id_factura = "";
			
			while ($rowDetalle = mysql_fetch_array($rsDetalle)){
				if ($rowDetalle['tipo_documento'] == "NC"){
					$totalServicioDia -= $rowDetalle['total_por_tempario'];
					$objResponse->alert("totalServicioDia = ".$totalServicioDia." rowDetalle[total_por_tempario] = ".$rowDetalle['total_por_tempario']);
					$totalComisionServicio -= $rowDetalle['total_por_tempario'] * $rowDetalle['porcentaje_comision_servicio'] / 100;
				}
				else{
					$totalServicioDia += $rowDetalle['total_por_tempario'];
					$totalComisionServicio += $rowDetalle['total_por_tempario'] * $rowDetalle['porcentaje_comision_servicio'] / 100;
					$porcentajeComisionServicio = $rowDetalle['porcentaje_comision_servicio'];
				}
				
				$array_id_factura[] = valTpDato($rowDetalle['idFactura'],'int');

				if ($rowDetalle['tipo_documento'] == "VS")
					$queryIva = sprintf("SELECT subtotal_iva FROM sa_orden WHERE id_orden = %s",valTpDato($rowDetalle['id_orden'],'int'));
				else if ($rowDetalle['tipo_documento'] == "FA")
					$queryIva = sprintf("SELECT calculoIvaFactura AS subtotal_iva FROM cj_cc_encabezadofactura WHERE idFactura = %s",valTpDato($rowDetalle['idFactura'],'int'));
				
				$rsIva = mysql_query($queryIva);
								
				if (!$rsIva) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				
				$rowIva = mysql_fetch_array($rsIva);
				
				if ($rowDetalle['tipo_documento'] == "NC")
					$totalIvaDia -= $rowIva['subtotal_iva'];
				else
					$totalIvaDia += $rowIva['subtotal_iva'];
			}
			
			$queryDetalleRepuesto = sprintf("SELECT * FROM vw_sa_comisiones_asesores_gerentes WHERE fecha_factura BETWEEN '%s' AND '%s' AND id_empleado = %s AND id_modulo = 0",date('Y-m-d',strtotime($rowFecha['fecha_factura']))." 00:00:00",date('Y-m-d',strtotime($rowFecha['fecha_factura']))." 23:59:59",$rowEmpleado['id_empleado']);
			$rsDetalleRepuesto = mysql_query($queryDetalleRepuesto);
			
			if (!$rsDetalleRepuesto) return $objResponse->alert(mysql_error());
			
			$totalRepuestoDia = 0;
			
			while ($rowDetalleRepuesto = mysql_fetch_array($rsDetalleRepuesto)){
				$queryDescuento = sprintf("SELECT descuentoFactura FROM cj_cc_encabezadofactura WHERE idFactura = %s",$rowDetalleRepuesto['idFactura']);
				$rsDescuento = mysql_query($queryDescuento);
				
				if (!$rsDescuento) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				
				$rowDescuento = mysql_fetch_array($rsDescuento);
				$descuento = 0;
				if ($rowDetalleRepuesto['tipo_documento'] == "NC"){
					if ($rowDescuento['descuentoFactura'] > 0){
						$descuento = $rowDetalleRepuesto['total_por_tempario'] * $rowDescuento['descuentoFactura'] / 100;
					}
					$totalRepuestoDia -= $rowDetalleRepuesto['total_por_tempario'];
					$totalDescuentoDia -= $descuento;
					$totalComisionRepuesto -= ($rowDetalleRepuesto['total_por_tempario'] - $descuento) * $rowDetalleRepuesto['porcentaje_comision_servicio'] / 100;
				}
				else{
					if ($rowDescuento['descuentoFactura'] > 0){
						$descuento = $rowDetalleRepuesto['total_por_tempario'] * $rowDescuento['descuentoFactura'] / 100;
					}
					$totalRepuestoDia += $rowDetalleRepuesto['total_por_tempario'];
					$totalDescuentoDia += $descuento;
					$totalComisionRepuesto += ($rowDetalleRepuesto['total_por_tempario'] - $descuento) * $rowDetalleRepuesto['porcentaje_comision_servicio'] / 100;
				}
				$porcentajeComision = $rowDetalleRepuesto['porcentaje_comision_servicio'];
				
				$array_id_factura[] = valTpDato($rowDetalleRepuesto['idFactura'],'int');			
			}
			
			$array_id_factura_filtrada = array_unique($array_id_factura);
			
			foreach ($array_id_factura_filtrada as $indice => $valor){
				$queryTOT = sprintf("SELECT sa_orden_tot.monto_subtotal + (sa_orden_tot.monto_subtotal * sa_det_fact_tot.porcentaje_tot / 100) AS monto FROM sa_det_fact_tot INNER JOIN sa_orden_tot ON (sa_det_fact_tot.id_orden_tot = sa_orden_tot.id_orden_tot) WHERE idFactura = %s",valTpDato($valor,'int'));
				
				$rsTOT = mysql_query($queryTOT);
				
				if (!$rsTOT) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				
				while ($rowTOT = mysql_fetch_array($rsTOT)){
					$totalTOTDia += number_format($rowTOT['monto'],"2",".",",");
					if ($tipoEmpleado == 1 || $tipoEmpleado == 3 || $tipoEmpleado == 4){
						$totalComisionServicio += $rowTOT['monto'] * $porcentajeComisionServicio / 100;
					}
				}
				
				$queryNota = sprintf("SELECT precio FROM sa_det_fact_notas WHERE idFactura = %s",valTpDato($valor,'int'));
				
				$rsNota = mysql_query($queryNota);
				
				if (!$rsNota) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				
				while ($rowNota = mysql_fetch_array($rsNota)){
					$totalNotasDia += number_format($rowNota['precio'],"2",".",",");
					if ($tipoEmpleado == 1 || $tipoEmpleado == 3 || $tipoEmpleado == 4){
						$totalComisionServicio += $rowNota['precio'] * $porcentajeComisionServicio / 100;
					}
				}
			}
			
			$totalOrdenDia = $totalServicioDia + $totalRepuestoDia + $totalTOTDia + $totalNotasDia - $totalDescuentoDia;
			$totalComisionIva = $totalComisionServicio + $totalComisionRepuesto;
			
			$html .= "<tr>
						<td >".date("dmY",strtotime($rowFecha['fecha_factura']))."</td>
						<td align='right'>".number_format($totalOrdenDia,"2",".",",")."</td>
						<td align='right'>".number_format($totalIvaDia,"2",".",",")."</td>
						<td align='right'>".number_format($totalServicioDia,"2",".",",")."</td>
						<td align='right'>".number_format($totalTOTDia,"2",".",",")."</td>
						<td align='right'>".number_format($totalNotasDia,"2",".",",")."</td>
						<td align='right'>".number_format($porcentajeComisionServicio,"2",".",",")."</td>
						<td align='right'>".number_format($totalRepuestoDia,"2",".",",")."</td>
						<td align='right'>".number_format($porcentajeComision,"2",".",",")."</td>
						<td align='right'>".number_format($totalDescuentoDia,"2",".",",")."</td>
						<td align='right'>".number_format($totalComisionIva,"2",".",",")."</td>
					</tr>";
			
			//$objResponse->alert("totalEmpleadoServicio: ".$totalEmpleadoServicio." totalServicioDia: ".$totalServicioDia." + totalRepuestoDia: ".$totalRepuestoDia);

			
			$totalEmpleadoServicio += $totalOrdenDia;
			$totalEmpleadoIva += $totalIvaDia;
			$totalEmpleadoMO += $totalServicioDia;
			$totalEmpleadoTOT += $totalTOTDia;
			$totalEmpleadoNotas += $totalNotasDia;
			$totalEmpleadoRepuesto += $totalRepuestoDia;
			$totalEmpleadoDescuento += $totalDescuentoDia;
			$totalEmpleadoComision += $totalComisionIva;
		}
		
		$totalAcumuladoServicio += $totalEmpleadoServicio;
		$totalAcumuladoTOT += $totalEmpleadoTOT;
		$totalAcumuladoNotas += $totalEmpleadoNotas;
		$totalAcumuladoIva += $totalEmpleadoIva;
		$totalAcumuladoMO += $totalEmpleadoMO;
		$totalAcumuladoRepuesto += $totalEmpleadoRepuesto;
		$totalAcumuladoDescuento += $totalEmpleadoDescuento;
		$totalAcumuladoComision += $totalEmpleadoComision;
			
		$html .= "<tr class=\"tituloColumna\">
										<td >Total</td>
										<td align='right'>".number_format($totalEmpleadoServicio,"2",".",",")."</td>
										<td align='right'>".number_format($totalEmpleadoIva,"2",".",",")."</td>
										<td align='right'>".number_format($totalEmpleadoMO,"2",".",",")."</td>
										<td align='right'>".number_format($totalEmpleadoTOT,"2",".",",")."</td>
										<td align='right'>".number_format($totalEmpleadoNotas,"2",".",",")."</td>
										<td align='right'>".number_format($porcentajeComisionServicio,"2",".",",")."</td>
										<td align='right'>".number_format($totalEmpleadoRepuesto,"2",".",",")."</td>
										<td align='right'>".number_format($porcentajeComision,"2",".",",")."</td>
										<td align='right'>".number_format($totalEmpleadoDescuento,"2",".",",")."</td>
										<td align='right'>".number_format($totalEmpleadoComision,"2",".",",")."</td>
									</tr>";
		
		$html .= "<tr class=\"tituloColumna\">
										<td >Total Acumulado</td>
										<td align='right'>".number_format($totalAcumuladoServicio,"2",".",",")."</td>
										<td align='right'>".number_format($totalAcumuladoIva,"2",".",",")."</td>
										<td align='right'>".number_format($totalAcumuladoMO,"2",".",",")."</td>
										<td align='right'>".number_format($totalAcumuladoTOT,"2",".",",")."</td>
										<td align='right'>".number_format($totalAcumuladoNotas,"2",".",",")."</td>
										<td align='right'>".number_format($porcentajeComisionServicio,"2",".",",")."</td>
										<td align='right'>".number_format($totalAcumuladoRepuesto,"2",".",",")."</td>
										<td align='right'>".number_format($porcentajeComision,"2",".",",")."</td>
										<td align='right'>".number_format($totalAcumuladoDescuento,"2",".",",")."</td>
										<td align='right'>".number_format($totalAcumuladoComision,"2",".",",")."</td>
									</tr>";
	}
	
	$htmlTableFin .= "</table>";
	
	$objResponse->assign("tdComisiones","innerHTML",$htmlTableIni.$html.$htmlTableFin);
	
	return $objResponse;
}
*/

$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresas");
$xajax->register(XAJAX_FUNCTION,"cargarSelectEmpleados");
$xajax->register(XAJAX_FUNCTION,"listarComisionesMecanicos");
$xajax->register(XAJAX_FUNCTION,"listarComisionesGerentes");
?>