<?php

function actualizarStatusOrden($id_orden, $id_estado_orden) {
	$objResponse = new xajaxResponse();
	
	$campos1 = "";
	$condicion1 = "";
	$sql1 = "";

	$campos1 .= "id_estado_orden = ".$id_estado_orden;
	$condicion1 = "id_orden = ".$id_orden;

	$sql1 = "UPDATE sa_orden SET ".$campos1." WHERE ".$condicion1;
	$rs1 = mysql_query($sql1);
	if (!$rs1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($rs1) {
		$objResponse->alert('¡El estado de la orden se actualizo con exito!');
		$objResponse->script("location.href='sa_generar_orden_list.php';");
	}
	
	return $objResponse;
}


function aprobarOrden($valForm) {

	$objResponse = new xajaxResponse();
        
	$idOrdenAprobar = $valForm["txtNroOrdenAprob"];
        
	//Clav mov orden y id empresa de la orden -Gregor
	$queryOrden = sprintf("SELECT sa_orden.id_empresa, id_clave_movimiento, sa_tipo_orden.modo_factura
				   FROM sa_orden
				   LEFT JOIN sa_tipo_orden ON sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden
				   WHERE id_orden = %s LIMIT 1",
				   valTpDato($idOrdenAprobar,"int"));
	$rsOrden = mysql_query($queryOrden);
	if (!$rsOrden) return $objResponse->alert(mysql_error()."\nError seleccionando clav mov orden \n\nLine: ".__LINE__);
	
	$datosOrden = mysql_fetch_assoc($rsOrden);
	$idEmpresa = $datosOrden["id_empresa"];
	$idClaveMovimiento = $datosOrden["id_clave_movimiento"];
        $modoFacturaOrden = $datosOrden["modo_factura"];
	
	//-fin
		
	$query = sprintf("SELECT 
		sa_claves.clave AS contrasena,
		pg_empleado.id_empleado
	FROM pg_empleado
		INNER JOIN pg_usuario ON (pg_empleado.id_empleado = pg_usuario.id_empleado)
		INNER JOIN sa_claves ON (pg_empleado.id_empleado = sa_claves.id_empleado)
	WHERE sa_claves.modulo = '%s'
		AND pg_usuario.id_usuario = %s",
		$valForm['txtIdClaveUsuario'],
		$_SESSION['idUsuarioSysGts']);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if ($row['contrasena'] == md5($valForm['txtClaveAprobacion'])) {
		mysql_query("START TRANSACTION;");
		
		if ($valForm['txtIdClaveUsuario'] == 'aprb_fin_ord') { //7
			$query = sprintf("UPDATE sa_orden SET id_empleado_aprobacion_factura = %s WHERE sa_orden.id_orden = %s",
				$row['id_empleado'],
				valTpDato($idOrdenAprobar, "int"));
			mysql_query("SET NAMES 'utf8';");
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
		
		$query = sprintf("SELECT 
			COUNT(*) AS cont_aprob,
			sa_tipo_orden.modo_factura
		FROM sa_tipo_orden
			INNER JOIN sa_orden ON (sa_tipo_orden.id_tipo_orden = sa_orden.id_tipo_orden)
		WHERE sa_orden.id_orden = %s
			AND sa_orden.id_empleado_aprobacion_factura IS NOT NULL
		GROUP BY sa_tipo_orden.modo_factura",
			valTpDato($idOrdenAprobar, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
					
		if ($row['cont_aprob'] > 0) {
			//EN EN LISTADO VALE DE SALIDA VOY A MOSTRAR LOS QUE ESTEN TERMINADOS Y CON LOS QUE TENGAN TIPO DE ORDEN VALE DE SALIDA...
			
                        if($modoFacturaOrden == NULL || $modoFacturaOrden == ""){
                            return $objResponse->alert("No se ha definido el modo factura");
                        }
                    			
			if ($modoFacturaOrden == 'FACTURA') {
                            
							//valido que no este en cero
							$query = sprintf("SELECT * FROM sa_orden
				                              WHERE id_orden = %s 
											  AND (subtotal = 0 OR total_orden = 0)",
                                    valTpDato($idOrdenAprobar, "int"));
							$rs = mysql_query($query);
                            if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);									
							$enCero = mysql_num_rows($rs);
							
							if($enCero){ return $objResponse->alert("No se puede facturar ordenes en Cero"); }
			
							//13 es la que manda a facturacion, sino no se ve
                            $query = sprintf("UPDATE sa_orden SET
                                    sa_orden.id_estado_orden = 13
                            WHERE sa_orden.id_orden = %s AND sa_orden.id_estado_orden != 18", //valido que no este facturado gregor
                                    valTpDato($idOrdenAprobar, "int"));
                            $rs = mysql_query($query);
                            if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
                            
                            mysql_query("COMMIT;");

                            $objResponse->alert("La orden ha sido aprobada como finalizada. Ahora se puede facturar.");		
			} else {
				/******************************************************************************************************/
				$query = sprintf("UPDATE sa_orden SET
					sa_orden.id_estado_orden = 24
				WHERE sa_orden.id_orden = %s",
					 valTpDato($idOrdenAprobar, "int"));
				mysql_query("SET NAMES 'utf8';");
				$rs = mysql_query($query);
				if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				
				
				// NUMERACION DEL DOCUMENTO
				$queryNumeracion = sprintf("SELECT * FROM pg_empresa_numeracion
				WHERE id_numeracion = (SELECT clave_mov.id_numeracion_documento FROM pg_clave_movimiento clave_mov
										WHERE clave_mov.id_clave_movimiento = %s)
					AND (id_empresa = %s OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																					WHERE suc.id_empresa = %s)))
				ORDER BY aplica_sucursales DESC
				LIMIT 1;",
					valTpDato($idClaveMovimiento, "int"), //clave de movimiento del tipo de orden
					valTpDato($idEmpresa, "int"),
					valTpDato($idEmpresa, "int"));
					
				$rsNumeracion = mysql_query($queryNumeracion);
				if (!$rsNumeracion) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()." select numeracion \n\nLine: ".__LINE__); }
				$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
				
				$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
				$numeroActual = $rowNumeracion['numero_actual'];
												
				$insertSQL = sprintf("INSERT INTO sa_vale_salida(numero_vale, fecha_vale, id_orden, estado_vale, subtotal, descuento, baseImponible, porcentajeIva, calculoIva, monto_exento, monto_total, id_empleado, id_empresa)
				SELECT %s,
                                        NOW(),
                                        sa_orden.id_orden,
                                        0,
                                        sa_orden.subtotal,
                                        sa_orden.subtotal_descuento,
                                        sa_orden.base_imponible,
                                        sa_orden.iva,
                                        sa_orden.subtotal_iva,
                                        sa_orden.monto_exento,
                                        sa_orden.total_orden,
                                        %s,
                                        sa_orden.id_empresa                                        
                                FROM sa_orden 
                                WHERE id_orden = %s", 
					valTpDato($numeroActual, "int"),
                                        valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
					valTpDato($idOrdenAprobar, "int"));
				mysql_query("SET NAMES 'utf8';");
				$query = mysql_query($insertSQL);
				if (!$query) {
					if (mysql_errno() == 1062) {
						$objResponse->alert("El vale ya fue creado.");
						return $objResponse->script("location.href='sa_generar_orden_list.php';");
					}
					return $objResponse->alert(mysql_error()."insrt vale salida \n\nLine: ".__LINE__);
				}
				$id_vale_salida = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
						
						
				// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
				$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
				WHERE id_empresa_numeracion = %s;",
					valTpDato($idEmpresaNumeracion, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
					
                                //ivas del vale
                                $query = sprintf("INSERT INTO sa_vale_salida_iva (id_vale_salida, base_imponible, subtotal_iva, id_iva, iva, lujo)
                                    SELECT %s, base_imponible, subtotal_iva, id_iva, iva, lujo
                                    FROM sa_orden_iva 
                                    WHERE id_orden = %s 
                                    ",
                                    $id_vale_salida,
                                    valTpDato($idOrdenAprobar, "int"));
                                $rs = mysql_query($query);
                                if(!$rs) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
                                
				$queryArt = sprintf("SELECT 
					sa_det_orden_articulo.id_articulo,
					sa_det_orden_articulo.id_paquete,
					sa_det_orden_articulo.cantidad,
					sa_det_orden_articulo.id_precio,
					sa_det_orden_articulo.precio_unitario,
					sa_det_orden_articulo.id_articulo_costo,
					sa_det_orden_articulo.id_articulo_almacen_costo,
					
					(SELECT
                                        if((SELECT valor FROM pg_configuracion_empresa config_emp
                                        INNER JOIN pg_configuracion config ON config_emp.id_configuracion = config.id_configuracion
                                        WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = %s) IN(1,3), 
                                                                                                            (SELECT vw_iv_articulos_almacen_costo.costo FROM vw_iv_articulos_almacen_costo WHERE vw_iv_articulos_almacen_costo.id_articulo_costo = sa_det_orden_articulo.id_articulo_costo LIMIT 1), 
                                                                                                            (SELECT vw_iv_articulos_almacen_costo.costo_promedio FROM vw_iv_articulos_almacen_costo WHERE vw_iv_articulos_almacen_costo.id_articulo_costo = sa_det_orden_articulo.id_articulo_costo LIMIT 1)
                                        )) AS costo,
						
					sa_det_orden_articulo.id_iva,
					sa_det_orden_articulo.iva,
					sa_det_orden_articulo.aprobado,
					sa_det_orden_articulo.tiempo_asignacion,
					sa_det_orden_articulo.tiempo_aprobacion,
					sa_det_orden_articulo.id_empleado_aprobacion,
					sa_det_orden_articulo.estado_articulo,
					sa_det_orden_articulo.estado_articulo + 0 AS edo_articulo,
					sa_det_orden_articulo.id_det_orden_articulo,
					sa_orden.porcentaje_descuento,
					((sa_det_orden_articulo.precio_unitario*sa_det_orden_articulo.cantidad)*sa_orden.porcentaje_descuento)/100 AS subtotal_descuento
				FROM sa_orden
					INNER JOIN sa_det_orden_articulo ON (sa_orden.id_orden = sa_det_orden_articulo.id_orden)
				WHERE 
                                        sa_orden.id_orden= %s
					AND sa_det_orden_articulo.aprobado = 1
					AND sa_det_orden_articulo.estado_articulo <> 'DEVUELTO'",
					valTpDato($idEmpresa, "int"),
					valTpDato($idOrdenAprobar, "int"));
				$rsArt = mysql_query($queryArt);
				if (!$rsArt) return $objResponse->alert(mysql_error()." pg_emp_num \n\nLine: ".__LINE__."\n\nQuery: ".$queryArt);

				//*************************************************************************************************************
				if (mysql_num_rows($rsArt) > 0) {
					$credito= 0;

					$queryMov = "SELECT
						o.id_orden,
						(SELECT t.id_clave_movimiento FROM sa_tipo_orden t
							WHERE t.id_tipo_orden= o.id_tipo_orden) AS id_clave_movimiento,
						DATE_FORMAT(o.tiempo_orden, '%Y-%m-%d') as fecha_orden,
						IFNULL(
							(SELECT r.id_cliente_pago FROM sa_recepcion r WHERE r.id_recepcion= o.id_recepcion),
							(SELECT c.id_cliente_contacto FROM sa_cita c
									WHERE c.id_cita= (SELECT r.id_cita FROM sa_recepcion r WHERE r.id_recepcion= o.id_recepcion))
							) AS id_cliente
					FROM sa_orden o
					WHERE o.id_orden = ".valTpDato($idOrdenAprobar, "int");
					$rsMov = mysql_query($queryMov);
					if (!$rsMov) return $objResponse->alert(mysql_error()."sele clav mov tip ord \n\nLine: ".__LINE__);
					$rowMov = mysql_fetch_array($rsMov);
					
                                        
					//gregor
					//COMPRUEBO QUE TENGA REPUESTO EN SOLICITUD COMO DESPACHADO, POR LO MENOS 1                                
					$queryComprobacion = sprintf("SELECT sa_orden.id_orden 
										FROM sa_orden 
										LEFT JOIN sa_solicitud_repuestos ON sa_orden.id_orden = sa_solicitud_repuestos.id_orden
										LEFT JOIN sa_det_solicitud_repuestos ON sa_solicitud_repuestos.id_solicitud = sa_det_solicitud_repuestos.id_solicitud
										WHERE sa_orden.id_orden = %s AND sa_det_solicitud_repuestos.id_estado_solicitud = 3", //3 DESPACHADO
										valTpDato($idOrdenAprobar, "int"));

					$rsComprobacion = mysql_query($queryComprobacion);
					if(!$rsComprobacion){ return $objResponse->alert(mysql_error()."sele comprob repst \n\nLine: ".__LINE__); }

					if(mysql_num_rows($rsComprobacion)){//si tiene despachado, crear movimiento

							$sqlTipoMovimiento = sprintf("SELECT tipo FROM pg_clave_movimiento WHERE id_clave_movimiento = %s LIMIT 1",
														valTpDato($rowMov['id_clave_movimiento'], "int"));
							$queryTipoMovimiento = mysql_query($sqlTipoMovimiento);
							if (!$queryTipoMovimiento) { return $objResponse->alert("Error buscando tipo clave movimiento en Aprobar Orden: ".mysql_error()."\n Linea: ".__LINE__); }
							$rowTipoMovimiento = mysql_fetch_assoc($queryTipoMovimiento);
							$idTipoMovimiento = $rowTipoMovimiento["tipo"];

							$insertMovimientoSQL = sprintf("INSERT INTO iv_movimiento (id_tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, tipo_costo, fecha_captura, id_usuario, credito)
							VALUE (%s, %s, %s, %s, NOW(), %s, %s, NOW(), %s, %s)",
									valTpDato($idTipoMovimiento, "int"),
									valTpDato($rowMov['id_clave_movimiento'], "int"),
									valTpDato(1, "int"), //1 = Vale Entrada / Salida, 2 = Nota Credito
									valTpDato($id_vale_salida, "int"),
									valTpDato($rowMov['id_cliente'], "int"),
									valTpDato(0, "boolean"),
									valTpDato($_SESSION['idUsuarioSysGts'], "int"),
									valTpDato($credito, "boolean"));
							$ResultMovimiento1 = mysql_query($insertMovimientoSQL);
							if (!$ResultMovimiento1) return $objResponse->alert(mysql_error()."ins iv_movimiento \n\nLine: ".__LINE__);
							$idMovimiento = mysql_insert_id();
					}
					
				}
                                
				//*************************************************************************************************************
					
				while ($rowArt = mysql_fetch_array($rsArt)) {
					$idArticulo = $rowArt['id_articulo'];
							
					$queryCont = sprintf("SELECT 
						COUNT(*) AS nro_rpto_desp
					FROM sa_det_solicitud_repuestos
					WHERE sa_det_solicitud_repuestos.id_det_orden_articulo = %s
						AND sa_det_solicitud_repuestos.id_estado_solicitud = 3", 
						$rowArt['id_det_orden_articulo']);
					$rsCont = mysql_query($queryCont);
					if (!$rsCont) return $objResponse->alert(mysql_error()." sol rep \n\nLine: ".__LINE__);
					$rowCont = mysql_fetch_assoc($rsCont);
					
					if ($rowCont['nro_rpto_desp'] > 0) {
						$insertSQLDetFact = sprintf("INSERT INTO sa_det_vale_salida_articulo (id_vale_salida, id_articulo, id_paquete, cantidad, id_precio, precio_unitario, id_articulo_costo, id_articulo_almacen_costo, costo, id_iva, iva, aprobado, tiempo_asignacion,  tiempo_aprobacion, id_empleado_aprobacion, estado_articulo)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, '%s', '%s', %s, %s);",
							$id_vale_salida,
							$rowArt['id_articulo'],
							valTpDato($rowArt['id_paquete'], "int"),
							$rowCont['nro_rpto_desp'],
							valTpDato($rowArt['id_precio'], "int"),
							$rowArt['precio_unitario'],
							$rowArt['id_articulo_costo'],
							$rowArt['id_articulo_almacen_costo'],
							$rowArt['costo'],
							valTpDato($rowArt['id_iva'], "int"),
							valTpDato($rowArt['iva'], "real_inglesa"),
							$rowArt['aprobado'], 
							$rowArt['tiempo_asignacion'],
							$rowArt['tiempo_aprobacion'],
							$rowArt['id_empleado_aprobacion'],
							$rowArt['edo_articulo']);
						mysql_query("SET NAMES 'utf8';");
						$rsDetFact = mysql_query($insertSQLDetFact);
						if (!$rsDetFact) { return $objResponse->alert(mysql_error()." vale salida art \n\nLine: ".__LINE__."\n\nQuery:".$insertSQLDetFact); }
						$idDetValeArticulo = mysql_insert_id();


						$query = sprintf("SELECT base_imponible, subtotal_iva, id_iva, iva, lujo 
										FROM sa_det_orden_articulo_iva 
										WHERE id_det_orden_articulo = %s",
							$rowArt["id_det_orden_articulo"]);
						$rs3 = mysql_query($query);
						if(!$rs3) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery:".$query); }

						while($rowArtIvaOrden = mysql_fetch_assoc($rs3)){
							$query = sprintf("INSERT INTO sa_det_vale_salida_articulo_iva (id_det_vale_salida_articulo, base_imponible, subtotal_iva, id_iva, iva, lujo)
													VALUES(%s, %s, %s, %s, %s, %s)",
							$idDetValeArticulo,
							$rowArtIvaOrden["base_imponible"],
							$rowArtIvaOrden["subtotal_iva"],
							$rowArtIvaOrden["id_iva"],
							$rowArtIvaOrden["iva"],
							valTpDato($rowArtIvaOrden["lujo"],"int")
									);
							$rs4 = mysql_query($query);
							if(!$rs4) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery:".$query); }
						}						
						

						
						
						$queryContCas = sprintf("SELECT 
							sa_det_solicitud_repuestos.id_casilla,
							COUNT(*) AS nro_rpto_desp_cas
						FROM sa_det_solicitud_repuestos
						WHERE sa_det_solicitud_repuestos.id_det_orden_articulo = %s
							AND sa_det_solicitud_repuestos.id_estado_solicitud = 3
						GROUP BY sa_det_solicitud_repuestos.id_casilla", 
							$rowArt['id_det_orden_articulo']);
						$rsContCas = mysql_query($queryContCas);
						if (!$rsContCas) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
						while ($rowContCas = mysql_fetch_assoc($rsContCas)) {
							$idCasilla = $rowContCas['id_casilla'];
							
							$queryClaveMov = "SELECT * FROM pg_clave_movimiento WHERE id_clave_movimiento = ".$rowMov['id_clave_movimiento'].";";
							$rsClaveMov = mysql_query($queryClaveMov);
							if (!$rsClaveMov) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
							$rowClaveMov= mysql_fetch_assoc($rsClaveMov);
														
							$insertSQLKardex = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, tipo_movimiento, tipo_documento_movimiento, cantidad, precio, id_articulo_costo, id_articulo_almacen_costo, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, id_clave_movimiento, estado, fecha_movimiento, hora_movimiento)
							VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW())",
								valTpDato(1, "int"),
								valTpDato($id_vale_salida, "int"),
								valTpDato($idArticulo, "int"),
								valTpDato($idCasilla, "int"),
								valTpDato($rowClaveMov['tipo'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
								valTpDato(1, "int"), //1 = Vale Entrada / Salida, 2 = Nota Credito
								valTpDato($rowContCas['nro_rpto_desp_cas'], "int"),
								valTpDato($rowArt['precio_unitario'], "real_inglesa"),
								valTpDato($rowArt['id_articulo_costo'], "int"),
								valTpDato($rowArt['id_articulo_almacen_costo'], "int"),
								valTpDato($rowArt['costo'], "real_inglesa"),
								valTpDato(0, "int"),//costo cargo
								valTpDato($rowArt['porcentaje_descuento'], "text"),
								valTpDato($rowArt['subtotal_descuento'], "real_inglesa"),
								valTpDato($rowMov['id_clave_movimiento'], "int"), // CLAVE DE MOVIMIENTO
								valTpDato(1, "int")); /* 0 = Entrada, 1 = Salida */
							mysql_query("SET NAMES 'utf8';");		
							$Result1Kardex = mysql_query($insertSQLKardex);
							if (!$Result1Kardex) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
							$idKardex = mysql_insert_id();
							mysql_query("SET NAMES 'latin1';");

						$insertMovimientoDetalleSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, cantidad, precio, id_articulo_costo, id_articulo_almacen_costo, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, correlativo1, correlativo2, tipo_costo, llave_costo_identificado, promocion, id_moneda_costo, id_moneda_costo_cambio)
						VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
							valTpDato($idMovimiento, "int"),
							valTpDato($idArticulo, "int"),
							valTpDato($idKardex, "int"),
							valTpDato($rowContCas['nro_rpto_desp_cas'], "int"),
							valTpDato($rowArt['precio_unitario'], "real_inglesa"),
                                                        valTpDato($rowArt['id_articulo_costo'], "int"),
                                                        valTpDato($rowArt['id_articulo_almacen_costo'], "int"),
							valTpDato($rowArt['costo'], "real_inglesa"),
							valTpDato(0, "int"),//costo cargo
							valTpDato($rowArt['porcentaje_descuento'], "text"),
							valTpDato($rowArt['subtotal_descuento'], "real_inglesa"),
							valTpDato("", "int"),
							valTpDato("", "int"),
							valTpDato(0, "int"),
							valTpDato("", "text"),
							valTpDato(0, "boolean"),
							valTpDato("", "int"),
							valTpDato("", "int"));
						$ResultMovimientoDetalle1 = mysql_query($insertMovimientoDetalleSQL);
						if (!$ResultMovimientoDetalle1) return $objResponse->alert(mysql_error()."iv mov detalle \n\nLine: ".__LINE__);

						}

						//***************************************************************************************************

						//***************************************************************************************************
						
						$query = sprintf("UPDATE sa_det_orden_articulo SET
							estado_articulo = 6
						WHERE sa_det_orden_articulo.id_det_orden_articulo = %s",
							valTpDato($rowArt['id_det_orden_articulo'], "int"));
						mysql_query("SET NAMES 'utf8';");
						$rs1 = mysql_query($query);
						if (!$rs1) return $objResponse->alert(mysql_error()."upd det ord art \n\nLine: ".__LINE__);	
						mysql_query("SET NAMES 'latin1';");																		
						
						
						//ACTUALIZAR SOLICITUD PRIMERO:
						$queryEdoDetSolicitudDespachado = sprintf("SELECT 
							id_det_solicitud_repuesto,
							id_solicitud,
							id_casilla
						FROM sa_det_solicitud_repuestos
						WHERE id_det_orden_articulo = %s
							AND id_estado_solicitud = 3", 
							$rowArt['id_det_orden_articulo']);
						$rsEdoDetSolicitudDespachado = mysql_query($queryEdoDetSolicitudDespachado);
						if (!$rsEdoDetSolicitudDespachado) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
						while ($rowEdoDetSolicitudDespachado = mysql_fetch_assoc($rsEdoDetSolicitudDespachado)) {
							$idCasilla = $rowEdoDetSolicitudDespachado['id_casilla'];
							
							$queryActualizaEdoDetSolicitudAfacturado = sprintf("UPDATE sa_det_solicitud_repuestos SET
								id_estado_solicitud = 5
							WHERE id_det_solicitud_repuesto = %s",
								$rowEdoDetSolicitudDespachado['id_det_solicitud_repuesto']);
								
							$rsActualizaEdoDetSolicitudAfacturado = mysql_query($queryActualizaEdoDetSolicitudAfacturado);
							if (!$rsActualizaEdoDetSolicitudAfacturado) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
							
							$queryActualizarEdoSolicitudAfacturado = sprintf("UPDATE sa_solicitud_repuestos SET
								estado_solicitud = 5
							WHERE id_solicitud = %s",
								 valTpDato($rowEdoDetSolicitudDespachado['id_solicitud'], "int"));
							
							$rsActualizarEdoSolicitudAfacturado = mysql_query($queryActualizarEdoSolicitudAfacturado);
							if (!$rsActualizarEdoSolicitudAfacturado) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
							
							
						}
						
						// BUSCA LOS ARTICULOS DE LA ORDEN POR UBICACION
						$queryContCas = sprintf("SELECT 
							id_casilla,
							COUNT(*) AS nro_rpto_desp_cas
						FROM sa_det_solicitud_repuestos
						WHERE id_det_orden_articulo = %s
						GROUP BY id_casilla", 
							valTpDato($rowArt['id_det_orden_articulo'], "int"));
						$rsContCas = mysql_query($queryContCas);
						if (!$rsContCas) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
						
						while($rowContCas = mysql_fetch_assoc($rsContCas)) {
							$idCasilla = $rowContCas['id_casilla'];
						
							// ACTUALIZA LOS SALDOS DEL ARTICULO (SALIDAS Y RESERVADAS)						
							// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO - Roger
							$Result1 = actualizarMovimientoTotal($idArticulo, $idEmpresa);
							if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
							
							// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS Y ESPERA)
							$Result1 = actualizarSaldos($idArticulo, $idCasilla);
							if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
						}
						
					}//FIN IF si hubo despacho
				}
					
					
				$insertSQLTempario = sprintf("INSERT INTO sa_det_vale_salida_tempario (id_vale_salida, id_paquete, id_tempario, precio, costo, costo_orden, id_modo, base_ut_precio, operador, aprobado, ut, tiempo_aprobacion, tiempo_asignacion, tiempo_inicio, tiempo_fin, id_mecanico, id_empleado_aprobacion, origen_tempario, estado_tempario, precio_tempario_tipo_orden) 
				SELECT 
					%s,
					sa_det_orden_tempario.id_paquete,
					sa_det_orden_tempario.id_tempario,
					sa_det_orden_tempario.precio,
					sa_det_orden_tempario.costo,
					sa_det_orden_tempario.costo_orden,
					sa_det_orden_tempario.id_modo,
					sa_det_orden_tempario.base_ut_precio,
					sa_det_orden_tempario.operador,
					sa_det_orden_tempario.aprobado,
					sa_det_orden_tempario.ut,
					sa_det_orden_tempario.tiempo_aprobacion,
					sa_det_orden_tempario.tiempo_asignacion,
					sa_det_orden_tempario.tiempo_inicio,
					sa_det_orden_tempario.tiempo_fin,
					sa_det_orden_tempario.id_mecanico,
					sa_det_orden_tempario.id_empleado_aprobacion,
					sa_det_orden_tempario.origen_tempario,
					sa_det_orden_tempario.estado_tempario,
					sa_det_orden_tempario.precio_tempario_tipo_orden
				FROM sa_orden
					INNER JOIN sa_det_orden_tempario ON (sa_orden.id_orden = sa_det_orden_tempario.id_orden)
				WHERE 
                                        sa_orden.id_orden= %s
					AND sa_det_orden_tempario.aprobado = 1
					AND sa_det_orden_tempario.estado_tempario <> 'DEVUELTO'",
					$id_vale_salida,
					valTpDato($idOrdenAprobar, "int"));
				mysql_query("SET NAMES 'utf8';");
				$rsTempario = mysql_query($insertSQLTempario);
				if (!$rsTempario) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
						
				$insertSQLTot = sprintf("SELECT 
					sa_det_orden_tot.id_orden_tot,
					sa_det_orden_tot.id_porcentaje_tot,
					sa_det_orden_tot.porcentaje_tot,
					sa_det_orden_tot.aprobado
				FROM sa_orden
					INNER JOIN sa_det_orden_tot ON (sa_orden.id_orden = sa_det_orden_tot.id_orden)
				WHERE 
                                        sa_orden.id_orden= %s
					AND sa_det_orden_tot.aprobado = 1",					
					valTpDato($idOrdenAprobar, "int"));
				$rsTot = mysql_query($insertSQLTot);
				if (!$rsTot) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				while($rowTot = mysql_fetch_assoc($rsTot)) {
					$insertSQLFactTot = sprintf("INSERT INTO sa_det_vale_salida_tot(id_vale_salida, id_orden_tot, id_porcentaje_tot, porcentaje_tot, aprobado)
					VALUE (%s, %s, %s, %s, %s)",
						$id_vale_salida,
						valTpDato($rowTot['id_orden_tot'], "int"),
						valTpDato($rowTot['id_porcentaje_tot'], "int"),
						valTpDato($rowTot['porcentaje_tot'], "int"),
						valTpDato($rowTot['aprobado'], "int"));
					mysql_query("SET NAMES 'utf8';");	
					$rsInsertTot = mysql_query($insertSQLFactTot);
					if (!$rsInsertTot) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					
					$queryTot = sprintf("UPDATE sa_orden_tot SET
						estatus = 3
					WHERE sa_orden_tot.id_orden_tot = %s",
						valTpDato($rowTot['id_orden_tot'], "int"));
					mysql_query("SET NAMES 'utf8';");
					$rsTotUpd = mysql_query($queryTot);
					if (!$rsTotUpd) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
					
				$insertSQLNota = sprintf("INSERT INTO sa_det_vale_salida_notas(id_vale_salida, descripcion_nota, precio, aprobado) 
				SELECT 
					%s,
					sa_det_orden_notas.descripcion_nota,
					sa_det_orden_notas.precio,
					sa_det_orden_notas.aprobado
				FROM sa_orden
					INNER JOIN sa_det_orden_notas ON (sa_orden.id_orden = sa_det_orden_notas.id_orden)
				WHERE 
                                        sa_orden.id_orden= %s
					AND sa_det_orden_notas.aprobado = 1",
					$id_vale_salida,
					valTpDato($idOrdenAprobar, "int"));
				mysql_query("SET NAMES 'utf8';");
				$rsNota = mysql_query($insertSQLNota);
				if (!$rsNota) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
					
				$insertSQLDcto = sprintf("INSERT INTO sa_det_vale_salida_descuento(id_vale_salida, id_porcentaje_descuento, porcentaje)
				SELECT 
					%s,
					sa_det_orden_descuento.id_porcentaje_descuento,
					sa_det_orden_descuento.porcentaje
				FROM sa_orden
					INNER JOIN sa_det_orden_descuento ON (sa_orden.id_orden = sa_det_orden_descuento.id_orden)
				WHERE 
                                        sa_orden.id_orden= %s",
					$id_vale_salida,
					valTpDato($idOrdenAprobar, "int"));
				mysql_query("SET NAMES 'utf8';");
				$rsDcto = mysql_query($insertSQLDcto);
				if (!$rsDcto) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
				
				// CALCULO DE LAS COMISIONES
				$Result1 = calcular_comision_vale_salida($id_vale_salida);
				if ($Result1[0] != true) { return $objResponse->alert($Result1[1]); }
				
				mysql_query("COMMIT;");
				
				//ERNESTO
				if(function_exists("generarValeSe")){
					generarValeSe($id_vale_salida);
				}
				//ERNESTO
				
				$objResponse->alert("La orden ha sido aprobada como finalizada. Se generara el vale de salida.");

				$objResponse->script(sprintf("window.location.href = 'sa_formato_orden.php?id=%s&doc_type=2&acc=3'", valTpDato($id_vale_salida, "int")));
				//$objResponse->script(sprintf("window.location.href = 'sa_imprimir_presupuesto_pdf.php?valBusq=%s|2'", valTpDato($idOrdenAprobar, "int")));
			}
		} else {
			mysql_query("COMMIT;"); //Este envia a facturacion, el de arriba es vale de salida
				
			$objResponse->alert("La orden ha sido aprobada.");	
		}
					
		$objResponse->script("$('divFlotante').style.display='none';");
		$objResponse->loadCommands(listadoOrdenes(0,"orden.numero_orden","DESC",$valForm['hddValBusqAprob']));
	} else {
		$objResponse->alert("Clave Inválida");
		$objResponse->script("$('txtClaveAprobacion').focus();");
	}
	//enviarEmail($idOrdenAprobar);
			
	return $objResponse;
}


function buscarCliente($txtCriterio){
	
    $objResponse = new xajaxResponse();

    $objResponse->script(sprintf("xajax_listadoClientes(0,'','','%s');",
            $txtCriterio));
	
    return $objResponse;
	
}

function listadoClientes($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
    $objResponse = new xajaxResponse();

    $valCadBusq = explode("|", $valBusq);
    $startRow = $pageNum * $maxRows;

    //$valCadBusq[0] criterio
    global $spanCI;
    global $spanRIF;
    
    $span_ci_rif = $spanCI." / ".$spanRIF;

    $sqlBusq = "WHERE status = 'Activo'";
    
    if($valCadBusq[0] != ""){
        $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
        $sqlBusq .= $cond.sprintf("CONCAT_WS(' ',nombre, apellido) LIKE %s
                                       OR CONCAT_WS('-',lci, ci) LIKE %s",
                                valTpDato("%".$valCadBusq[0]."%","text"),
                                valTpDato("%".$valCadBusq[0]."%","text")
                );
    }

    $query = sprintf("SELECT id, CONCAT_WS(' ',nombre, apellido) as nombre_cliente, CONCAT_WS('-',lci, ci) as lci_ci                            
                            FROM cj_cc_cliente
                            
                            %s", $sqlBusq); 

    $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";

    $queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
    $rsLimit = mysql_query($queryLimit);

    if (!$rsLimit) { return $objResponse->alert($queryLimit."\n".mysql_error()."\n\nLine: ".__LINE__); }
    if ($totalRows == NULL) {
            $rs = mysql_query($query);
            if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
            $totalRows = mysql_num_rows($rs);
    }
    $totalPages = ceil($totalRows/$maxRows)-1;

    $htmlTblIni = "<table border=\"0\" width=\"100%\">";
    $htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";            
        $htmlTh .= "<td width=\"1%\"></td>";
        $htmlTh .= ordenarCampo("xajax_listadoClientes", "4%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
        $htmlTh .= ordenarCampo("xajax_listadoClientes", "35%", $pageNum, "lci_ci", $campOrd, $tpOrd, $valBusq, $maxRows, $span_ci_rif);                
        $htmlTh .= ordenarCampo("xajax_listadoClientes", "35%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");                
    $htmlTh .= "</tr>";

    $contFila = 0;
    while ($row = mysql_fetch_assoc($rsLimit)) {
        $clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
        $contFila++;

        $htmlTb.= "<tr class=\"".$clase."\" height=\"22\">";

        $htmlTb .= "<td align=\"center\"><button onclick=\"xajax_cargarCliente(".$row['id'].");\" class=\"puntero\" type=\"button\"><img border=\"0\" src=\"../img/iconos/select.png\"></button></td>";
        $htmlTb .= "<td align=\"center\">".$row['id']."</td>";
        $htmlTb .= "<td align=\"center\">".$row['lci_ci']."</td>";        
        $htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_cliente'])."</td>";        

        $htmlTb.= "</tr>";
    }

    $htmlTf = "<tr>";
            $htmlTf .= "<td align=\"center\" colspan=\"18\">";
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
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
                                                            0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
                                            }
                                            $htmlTf .= "</td>";
                                            $htmlTf .= "<td width=\"25\">";
                                            if ($pageNum > 0) { 
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
                                                            max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
                                            }
                                            $htmlTf .= "</td>";
                                            $htmlTf .= "<td width=\"100\">";

                                                    $htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoClientes(%s,'%s','%s','%s',%s)\">",
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
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
                                                            min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
                                            }
                                            $htmlTf .= "</td>";
                                            $htmlTf .= "<td width=\"25\">";
                                            if ($pageNum < $totalPages) {
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
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

    if (!($totalRows > 0)) {
            $htmlTb .= "<td colspan=\"18\">";
                    $htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
                    $htmlTb .= "<tr>";
                            $htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
                            $htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
                    $htmlTb .= "</tr>";
                    $htmlTb .= "</table>";
            $htmlTb .= "</td>";
    }

    $objResponse->assign("divListadoClientes","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
    $objResponse->script("$('divFlotante3').style.display='';");
    $objResponse->script("centrarDiv($('divFlotante3'));");

    return $objResponse;
    
}

function cargarCliente($id_cliente){
	$objResponse = new xajaxResponse();
        
        $query = sprintf("SELECT id, CONCAT_WS(' ',nombre, apellido) as nombre_cliente, CONCAT_WS('-',lci, ci) as lci_ci,
                                 telf, otrotelf, correo
                            FROM cj_cc_cliente
                            WHERE id = %s LIMIT 1",
                        valTpDato($id_cliente,"int")); 
              
        $rs = mysql_query($query);

        if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query); }
	
        $row = mysql_fetch_assoc($rs);
        
	$objResponse->assign('id_cliente_pago','value',$row['id']);
	$objResponse->assign('cedula_cliente','value',$row['lci_ci']);
	$objResponse->assign('nombre_cliente','innerHTML',  utf8_encode($row['nombre_cliente']));
	$objResponse->assign('telefono_cliente','innerHTML', $row['telf']);
	$objResponse->assign('celular_cliente','innerHTML', $row['otrotelf']);
	$objResponse->assign('email_cliente','innerHTML', $row['correo']);
	
        $objResponse->script("$('divFlotante3').style.display='none';");
        
	return $objResponse;
}


function buscarOrden($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['txtFechaDesde'],
		$valForm['txtFechaHasta'],
		$valForm['lstEmpleadoVendedor'],
		$valForm['lstTipoOrden'],
		$valForm['lstEstadoOrden'],
		$valForm['txtCriterio']);
	
	$objResponse->loadCommands(listadoOrdenes(0, "orden.numero_orden", "DESC", $valBusq));
	
	return $objResponse;
}


function cargaLstEmpleado($selId = "", $nombreObjeto = "", $objetoDestino = "", $idEmpresa = "") {
	$objResponse = new xajaxResponse();
	
	if($idEmpresa == ""){
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];	
	}		
		
	$query = sprintf("SELECT DISTINCT empleado.id_empleado, empleado.nombre_empleado
	FROM sa_orden fact_vent
		INNER JOIN vw_pg_empleados empleado ON (fact_vent.id_empleado = empleado.id_empleado)
		WHERE fact_vent.id_empresa = $idEmpresa
	ORDER BY nombre_empleado");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\">";
		$html .= "<option value=\"\">[ Todos ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['id_empleado'])
			$selected = "selected='selected'";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
		
	}
	$html .= "</select>";
	$objResponse->assign($objetoDestino,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEmpresaBuscar($selId = "") {
	$objResponse = new xajaxResponse();
			
	$query = sprintf("SELECT DISTINCT id_empresa, nombre_empresa FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY nombre_empresa",
		valTpDato($_SESSION['idUsuarioSysGts'],"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstEmpresa\" name=\"lstEmpresa\" onchange=\"$('btnBuscar').click();\">";
		$html .= "<option value=\"\">[ Todos ]</option>";
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


function cargaLstEstadoOrden($selId = "") {
	$objResponse = new xajaxResponse();
			
	$query = "SELECT * FROM sa_estado_orden
	WHERE activo = 1
	ORDER BY sa_estado_orden.orden";
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstEstadoOrden\" name=\"lstEstadoOrden\" onchange=\"$('btnBuscar').click();\">";
		$html .= "<option value=\"\">[ Todos ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['id_estado_orden'])
			$selected = "selected='selected'";
						
		$html .= "<option ".$selected." value=\"".$row['id_estado_orden']."\">".utf8_encode($row['nombre_estado'])."</option>";
		
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEstadoOrden","innerHTML",$html);
	
	return $objResponse;
}


function cargaLstTipoOrden($selId = "", $idEmpresa="") {
	$objResponse = new xajaxResponse();
	
	//si al cargar la orden no se le envia una empresa a cargar (del listado de empresa) que cargue el por defecto.
	if($idEmpresa == ""){
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	}
	
	$query = "SELECT * FROM sa_tipo_orden WHERE sa_tipo_orden.id_empresa = $idEmpresa  ORDER BY sa_tipo_orden.nombre_tipo_orden";
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstTipoOrden\" name=\"lstTipoOrden\" onchange=\"$('btnBuscar').click();\">";
		$html .= "<option value=\"\">[ Todos ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['id_tipo_orden'])
			$selected = "selected='selected'";
						
		$html .= "<option ".$selected." value=\"".$row['id_tipo_orden']."\">".utf8_encode($row['nombre_tipo_orden'])."</option>";
		
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoOrden","innerHTML",$html);
	
	return $objResponse;
}


function formAprobarOrden($idOrden, $sobregiro, $claveModulo, $idEmpleadoAprobFactura, $pageNum, $campOrd, $tpOrd, $valBusq, $maxRows, $soloRepuestos = false) {
	$objResponse = new xajaxResponse();	
        
        //COMPROBAR QUE NO SEA SOLO REPUESTOS SIN MANOS DE OBRA        
        if($soloRepuestos == "SI"){
            //si esta en si saltar validacion de repuestos
        }else{
        
            $queryComprob = sprintf("SELECT sa_orden.id_orden 
                                        FROM sa_orden                                    
                                        LEFT JOIN sa_det_orden_articulo ON sa_det_orden_articulo.id_orden = sa_orden.id_orden
                                        LEFT JOIN sa_det_orden_tempario ON sa_det_orden_tempario.id_orden = sa_orden.id_orden
                                        WHERE sa_orden.id_orden = %s 
                                        AND sa_det_orden_articulo.id_orden IS NOT NULL 
                                        AND sa_det_orden_tempario.id_orden IS NULL
                                        AND sa_det_orden_articulo.estado_articulo !='DEVUELTO' ",
                                        valTpDato($idOrden, "int"));

            $rsComprob = mysql_query($queryComprob);
            if(!$rsComprob){return $objResponse->alert(mysql_error()."sel comprob repst mo \n\nLine: ".__LINE__); }

            if(mysql_num_rows($rsComprob)){
                $objResponse->alert("No se puede facturar ordenes con repuestos sin poseer manos de obra.");
                $objResponse->script("$('divFlotante2').style.display='';
                                        centrarDiv($('divFlotante2'));
                                       ");
                return $objResponse;
            }
        
        }        
         
        
        //COMPROBAR QUE NO TENGA ARTICULOS SIN SOLICITUD
        $queryComprobacion = sprintf("SELECT sa_det_orden_articulo.id_orden 
                                    FROM sa_det_orden_articulo                                    
                                    LEFT JOIN sa_det_solicitud_repuestos ON sa_det_orden_articulo.id_det_orden_articulo = sa_det_solicitud_repuestos.id_det_orden_articulo
                                    WHERE sa_det_orden_articulo.id_orden = %s AND sa_det_solicitud_repuestos.id_det_solicitud_repuesto IS NULL",
                                    valTpDato($idOrden, "int"));
        
        $rsComprobacion = mysql_query($queryComprobacion);
        if(!$rsComprobacion){return $objResponse->alert(mysql_error()."sele comprob repst \n\nLine: ".__LINE__); }
        
        if(mysql_num_rows($rsComprobacion)){
            return $objResponse->alert("La orden posee articulos sin solicitud (".mysql_num_rows($rsComprobacion)."), solicitelos o eliminelos");
        }
        
        
		
	$query = sprintf("SELECT * FROM pg_empleado
		INNER JOIN pg_usuario ON (pg_empleado.id_empleado = pg_usuario.id_empleado)
		INNER JOIN sa_claves ON (pg_empleado.id_empleado = sa_claves.id_empleado)
	WHERE sa_claves.modulo = '%s'
		AND pg_usuario.id_usuario = %s",
		$claveModulo,
		$_SESSION['idUsuarioSysGts']);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$num_rows = mysql_num_rows($rs);
		
	if ($num_rows == 0) {		
		return $objResponse->alert(utf8_encode("Usted no tiene acceso para realizar esta accion."));
	}

	$queryTotalRptosPdte = sprintf("SELECT COUNT(*) as total_rpto
	FROM sa_orden
		INNER JOIN sa_solicitud_repuestos ON (sa_orden.id_orden = sa_solicitud_repuestos.id_orden)
		INNER JOIN sa_det_solicitud_repuestos ON (sa_solicitud_repuestos.id_solicitud = sa_det_solicitud_repuestos.id_solicitud)
	WHERE sa_orden.id_orden = %s
		AND sa_det_solicitud_repuestos.id_estado_solicitud IN(1,2)
		AND sa_solicitud_repuestos.estado_solicitud != 6",
		$idOrden);
	$rsTotalRptoDespachados = mysql_query($queryTotalRptosPdte);
	if (!$rsTotalRptoDespachados) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowTotalRptoDespachados = mysql_fetch_assoc($rsTotalRptoDespachados);
	
	if ($rowTotalRptoDespachados['total_rpto'] > 0) {
		return $objResponse->alert("La Orden no puede terminar, debido a que tiene Solicitud(es) de Repuestos pendiente(s).\nVerifique las Solicitudes de la Orden.");
	}

	$queryFechaEntrega = sprintf("SELECT
		sa_tipo_orden.id_filtro_orden,
		sa_orden.id_cliente,
		sa_orden.id_tipo_orden,
		sa_orden.id_recepcion,
		###((((subtotal - subtotal_descuento) * iva) / 100) + (subtotal - subtotal_descuento)) AS total_orden,
		 total_orden,
		sa_orden.tiempo_entrega
	FROM sa_orden
	INNER JOIN sa_tipo_orden ON sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden
	WHERE id_orden = %s",
		$idOrden);
	$rsFechaEntrega = mysql_query($queryFechaEntrega);
	if (!$rsFechaEntrega) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowFechaEntrega = mysql_fetch_assoc($rsFechaEntrega);
	
	if ($rowFechaEntrega['tiempo_entrega'] == NULL) {
		return $objResponse->alert("Debe actualizar la fecha y hora de entrega del vehiculo");
	}	
	
	if ($rowFechaEntrega['id_filtro_orden'] == 2) {

		$queryClienteCredito = sprintf("SELECT cliente_cred.creditodisponible,
		cliente.credito
		FROM cj_cc_credito cliente_cred
			INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente_cred.id_cliente_empresa = cliente_emp.id_cliente_empresa)
			INNER JOIN cj_cc_cliente cliente ON (cliente_emp.id_cliente = cliente.id)
		WHERE cliente_emp.id_cliente = %s
		AND (UPPER(cliente.credito) = 'SI')",
		valTpDato($rowFechaEntrega['id_cliente'], "int"));

		$rsClienteCredito = mysql_query($queryClienteCredito);
		if (!$rsClienteCredito) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowClienteCredito = mysql_fetch_assoc($rsClienteCredito);
		
		if ($rowClienteCredito['creditodisponible'] == NULL){
			$objResponse->alert("El cliente no tiene credito o no posee asignado un limite de credito y/o credito disponible.");
			return $objResponse;
		} else if ($rowClienteCredito['creditodisponible'] < $rowFechaEntrega['total_orden']) {
			if($sobregiro == 0){
				$objResponse->script("if(confirm('El monto total de la orden de servicio sobrepasa el credito disponible del cliente.\\n\\nDesea Sobregirar al cliente?')){
					$('id_orden_sobregiro').value= ".$idOrden.";
					abrirSobregiro();
				}");
				
				return $objResponse;
			}
		}
	}
						

	$queryValidarSiExistenTemparioSinMecanico = sprintf("SELECT COUNT(id_orden) as cant
	FROM sa_det_orden_tempario
	WHERE sa_det_orden_tempario.aprobado = 1
		AND (sa_det_orden_tempario.id_mecanico is NULL
			AND (sa_det_orden_tempario.estado_tempario <> 'TERMINADO'
				AND sa_det_orden_tempario.estado_tempario <> 'DEVUELTO'))
		AND id_orden=%s
	GROUP by id_orden", $idOrden);
	$rsValidarSiExistenTemparioSinMecanico = mysql_query($queryValidarSiExistenTemparioSinMecanico);
	if (!$rsValidarSiExistenTemparioSinMecanico) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowValidarSiExistenTemparioSinMecanico = mysql_fetch_assoc($rsValidarSiExistenTemparioSinMecanico);

	/*if ($rowValidarSiExistenTemparioSinMecanico['cant'] > 0) {
			$objResponse->alert("La Orden no puede terminar, debido a que tiene Manos de Obras sin asignacion de Mecanicos.");
	} else {*/
		$objResponse->script("
		document.forms['frmClaveAprobacionOrden'].reset();");
		
		//mostrar numero de orden al darle click al boton finalizar para solicitar password gregor
		
		$sql=sprintf("SELECT numero_orden FROM sa_orden WHERE id_orden = %s LIMIT 1",
						valTpDato($idOrden,"int"));
		$query = mysql_query($sql);
		if(!$query) { return $objResponse->alert("Error buscando el numero de orden a mostrar \n".mysql_error()."\n LINE: ".__LINE__); }
		$row = mysql_fetch_assoc($query);
		$numeroOrdenMostrar = $row["numero_orden"];
		
		$objResponse->assign("numeroOrdenMostrar","value",$numeroOrdenMostrar);
		
		$objResponse->assign("txtIdClaveUsuario","value",$claveModulo);
		$objResponse->assign("txtNroOrdenAprob","value",$idOrden);
		$objResponse->assign("hddValBusqAprob","value",$valBusq);
		$objResponse->assign("hddPageNumAprob","value",$pageNum);
		$objResponse->assign("hddCampOrdAprob","value",$campOrd);
		$objResponse->assign("hddTpOrdAprob","value",$tpOrd);
		$objResponse->assign("hddMaxRowsAprob","value",$maxRows);
		$objResponse->assign("hddIdControlTallerAprob","value",$idEmpleadoAprobFactura);
		
		$objResponse->script("
		$('tblDcto').style.display = 'none';
		$('tblDetencionOrden').style.display = 'none';
		$('tblReanudarOrden').style.display = 'none';
		$('tblRetrabajoOrden').style.display = 'none';
		$('tblClaveAprobacionOrden').style.display = '';");
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Aprobar Orden Terminada");
		$objResponse->script("
		if ($('divFlotante').style.display == 'none') {
			$('divFlotante').style.display = '';
			centrarDiv($('divFlotante'));
		}
		$('txtClaveAprobacion').focus();
		$('txtClaveAprobacion').selected();");
		$objResponse->script("$('id_orden_sobregiro').value = 0;");
		$objResponse->script("$('sobregiro').value = 0;");
	//}
	
	
	
		
	return $objResponse;
}


function guardarCliente($id_orden, $id_cliente){
	$objResponse = new xajaxResponse();

	$sql1= sprintf("UPDATE sa_orden SET id_cliente = %s WHERE id_orden = %s",
					$id_cliente,
					$id_orden);
	$rs1 = mysql_query($sql1);
	if (!$rs1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);

	$objResponse->alert('¡Cliente actualizado con exito!');
	$objResponse->script("location.href='sa_generar_orden_list.php';");
	
	return $objResponse;
}


function guardarFechaEntrega($id_orden, $fecha) {
    $objResponse = new xajaxResponse();
    
    $fechaSplit = split(" ", $fecha);

    $fechaSola = split("-", $fechaSplit[0]);
    $horaSola = split(":", $fechaSplit[1]);
    $meridian = $fechaSplit[2];

    $fechaNueva= $fechaSola[2]."-".$fechaSola[1]."-".$fechaSola[0];
    if ($meridian == "AM") {
		if($horaSola[0] == 12){
			$horanueva = ($horaSola[0]+12).":".$horaSola[1];
		}else{
        	$horanueva = $fechaSplit[1];
		}
    } else if ($meridian == "PM") {
		if($horaSola[0] == 12){
        	$horanueva = ($horaSola[0]).":".$horaSola[1];
		}else{
			$horanueva = ($horaSola[0]+12).":".$horaSola[1];
		}
    }

    $fechaGuardar = $fechaNueva." ".$horanueva;
	
	//$fechaManual = $fechaSplit[0]." ".$fechaSplit[1].":00 ".$fechaSplit[2];	
	//$otraFecha = DATE("Y-m-d H:m:s",strtotime($fechaManual));
	
	//return $objResponse->alert($fechaGuardar);
	// arreglado por gregor =)
	
    $sql = "UPDATE sa_orden SET
		tiempo_entrega = '".$fechaGuardar."'
	WHERE id_orden = ".$id_orden;
    $rs = mysql_query($sql);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);

    $objResponse->script("alert('Fecha y Hora de Entrega Actualizada');");
    $objResponse->script("$('hora_window').style.display='none';");
    
    return $objResponse;
}


function listadoOrdenes($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("orden.id_estado_orden = 21");
	
	/*$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_sa_orden.retrabajo IS NULL");*/
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("orden.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(tiempo_orden) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("orden.id_empleado = %s",
			valTpDato($valCadBusq[3],"int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("orden.id_tipo_orden = %s",
			valTpDato($valCadBusq[4], "int"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("orden.id_estado_orden = %s",
			valTpDato($valCadBusq[5], "int"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		
		OR orden.numero_orden LIKE %s
		OR recepcion.numeracion_recepcion LIKE %s
		OR nom_uni_bas LIKE %s
		OR placa LIKE %s
		OR chasis LIKE %s)",
			valTpDato("%".$valCadBusq[6]."%","text"),
			valTpDato("%".$valCadBusq[6]."%","text"),
			valTpDato("%".$valCadBusq[6]."%","text"),
			valTpDato("%".$valCadBusq[6]."%","text"),
			valTpDato("%".$valCadBusq[6]."%","text"),
			valTpDato("%".$valCadBusq[6]."%","text"));
	}
	
	$query = sprintf("SELECT
		orden.tiempo_orden,
		orden.id_orden,
		orden.numero_orden,
		recepcion.id_recepcion,
		recepcion.numeracion_recepcion,
		orden.id_empresa,
		tipo_orden.nombre_tipo_orden,
		
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,

		(IF(cita.id_cliente_contacto != orden.id_cliente,
			(SELECT CONCAT_WS(' ', cj.nombre, cj.apellido) FROM cj_cc_cliente cj WHERE cj.id = cita.id_cliente_contacto),
			NULL)) AS nombre_cliente_anterior,
		
		uni_bas.nom_uni_bas,
		placa,
		chasis,																		
		(estado_orden.tipo_estado + 0) AS id_tipo_estado,
		nombre_estado,
		color_estado,
		color_fuente,
		id_orden_retrabajo,
		(SELECT sa_orden.numero_orden FROM sa_orden WHERE sa_orden.id_orden = orden_retrabajo.id_orden_retrabajo) AS numero_orden_retrabajo,
		
		IFNULL((SELECT SUM(det_orden_art.cantidad * det_orden_art.precio_unitario + (det_orden_art.cantidad * det_orden_art.precio_unitario * iva / 100))
		FROM sa_det_solicitud_repuestos det_solicitud_rep
			RIGHT JOIN sa_det_orden_articulo det_orden_art ON (det_solicitud_rep.id_det_orden_articulo = det_orden_art.id_det_orden_articulo)
		WHERE det_orden_art.id_orden = orden.id_orden
			AND (det_solicitud_rep.id_estado_solicitud = 2 OR det_solicitud_rep.id_estado_solicitud IS NULL)
			AND estado_articulo = 'PENDIENTE'),0) AS total_sin_aprobar,
		
		
		total_orden
		#((((orden.base_imponible) * orden.iva) / 100) + (orden.subtotal - orden.subtotal_descuento)) AS total,
                #((orden.subtotal - orden.subtotal_descuento) + orden.subtotal_iva) AS total_facturar
	FROM sa_orden orden
		INNER JOIN sa_recepcion recepcion ON (orden.id_recepcion = recepcion.id_recepcion)
		INNER JOIN sa_cita cita ON (recepcion.id_cita = cita.id_cita)
		INNER JOIN cj_cc_cliente cliente ON (orden.id_cliente = cliente.id)
		INNER JOIN en_registro_placas reg_placas ON (cita.id_registro_placas = reg_placas.id_registro_placas)
		INNER JOIN an_uni_bas uni_bas ON (reg_placas.id_unidad_basica = uni_bas.id_uni_bas)
		INNER JOIN sa_tipo_orden tipo_orden ON (orden.id_tipo_orden = tipo_orden.id_tipo_orden)
		INNER JOIN sa_estado_orden estado_orden ON (orden.id_estado_orden = estado_orden.id_estado_orden)
		LEFT JOIN sa_retrabajo_orden orden_retrabajo ON (orden.id_orden = orden_retrabajo.id_orden) %s", $sqlBusq);
	
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
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
	
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "6%", $pageNum, "tiempo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("Fecha"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "7%", $pageNum, "numero_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("N° Orden"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "7%", $pageNum, "numeracion_recepcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("N° Recepcion"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "11%", $pageNum, "nombre_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo Orden"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "22%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "6%", $pageNum, "nom_uni_bas", $campOrd, $tpOrd, $valBusq, $maxRows, ("Catálogo"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "7%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, ("Placa"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "11%", $pageNum, "chasis", $campOrd, $tpOrd, $valBusq, $maxRows, ("Chasis"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "7%", $pageNum, "nombre_estado", $campOrd, $tpOrd, $valBusq, $maxRows, ("Estado"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "2%", $pageNum, "numero_orden_retrabajo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Ord. Retrabajo"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "7%", $pageNum, "total_sin_aprobar", $campOrd, $tpOrd, $valBusq, $maxRows, ("Total Sin Despachar"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "7%", $pageNum, "total_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("Total a Facturar"));
		$htmlTh .= "<td colspan=\"6\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		$clienteAnterior = "";
		if($row["nombre_cliente_anterior"]){
			$clienteAnterior = "<br><small><b>Ant: ".utf8_encode($row["nombre_cliente_anterior"])."</b></small>";
		}

		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date("d-m-Y",strtotime($row['tiempo_orden']))."</td>";
			$htmlTb .= "<td align=\"right\" idordenoculta=\"".$row['id_orden']."\">".($row['numero_orden'])."</td>";
			$htmlTb .= "<td align=\"right\" idrecepcionoculta=\"".$row['id_recepcion']."\">".($row['numeracion_recepcion'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_tipo_orden'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_cliente']).$clienteAnterior."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nom_uni_bas'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['placa'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['chasis'])."</td>";
			$htmlTb .= "<td align=\"center\" style=\"background:#".$row['color_estado']."; color:#".$row['color_fuente']."\">".utf8_encode($row['nombre_estado'])."</td>";
			$htmlTb .= "<td align=\"left\" idOrdenRetrabajoOculta=\"".$row['id_orden_retrabajo']."\">".$row['numero_orden_retrabajo']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_sin_aprobar'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_orden'],2,".",",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('sa_orden_form.php?doc_type=2&id=%s&ide=%s&acc=2&cons=1&sinmenu=1','1100','600');\" src=\"../img/iconos/ico_view.png\" title=\"Ver Orden\"/>",
					$row['id_orden'],
					$row['id_empresa']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"abrirHora('%s');\" src=\"../img/iconos/time_add.png\" title=\"Asignar Fecha y Hora de Entrega\"/>",
					$row['id_orden']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"xajax_formAprobarOrden('%s', $('sobregiro').value, 'aprb_fin_ord', '%s', '%s', '%s', '%s', '%s', '%s', $('pasoClave').value);\" src=\"../img/iconos/aprob_mecanico.png\" title=\"Aprobación Control de Calidad\"/>",
					$row['id_orden'],
					$row['id_empleado_aprobacion_factura'],
					$pageNum,
					$campOrd,
					$tpOrd,
					$valBusq,
					$maxRows);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"abrirCliente('%s');\" src=\"../img/iconos/user_suit.png\" title=\"Cambiar Cliente de Pago\"/>",
					$row['id_orden']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"abrirStatus('%s');\" src=\"../img/iconos/page_refresh.png\" title=\"Cambiar Estado de la Orden\"/>",
					$row['id_orden']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('sa_imprimir_presupuesto_pdf.php?valBusq=%s|2|', 1000, 500);\" src=\"../img/iconos/ico_print.png\" title=\"Imprimir Orden\"/>",
					$row['id_orden']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"18\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"18\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaOrdenes","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	//$objResponse->script("limpiar_select();");
	
	return $objResponse;
}


function selectEstadoOrden($id_orden) {
	$objResponse = new xajaxResponse();

	$query1 = "SELECT * FROM sa_orden WHERE id_orden = ".$id_orden;
	$rs1 = mysql_query($query1);
	if (!$rs1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row1 = mysql_fetch_assoc($rs1);

	$query2 = "SELECT * FROM sa_estado_orden WHERE activo = 1 AND id_estado_orden IN (6) ORDER BY sa_estado_orden.orden";
	$rs2 = mysql_query($query2);
	if (!$rs2) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$html = "<select id=\"selectEstadoOrden\" name=\"selectEstadoOrden\">";
		$html .= "<option value=\"\">Seleccione</option>";
	while ($row2 = mysql_fetch_assoc($rs2)) {
            if($row1['id_estado_orden'] == $row2['id_estado_orden']){
                $objResponse->assign("divEstadoActual","innerHTML",utf8_encode($row2['nombre_estado']));
            }else{
                $html .= "<option ".$selected." value=\"".$row2['id_estado_orden']."\">".utf8_encode($row2['nombre_estado'])."</option>";
            }
	}
	$html .= "</select>";
	$objResponse->assign("divEstadoOrden","innerHTML",$html);

	return $objResponse;
}


function accesoSoloRepuesto($clave){
    $objResponse = new xajaxResponse();
    
    $query = sprintf("SELECT sa_claves.clave FROM pg_empleado
		INNER JOIN pg_usuario ON (pg_empleado.id_empleado = pg_usuario.id_empleado)
		INNER JOIN sa_claves ON (pg_empleado.id_empleado = sa_claves.id_empleado)
	WHERE sa_claves.modulo = 'sa_orden_solo_repuestos'
		AND pg_usuario.id_usuario = %s",
		$_SESSION['idUsuarioSysGts']);
	$rs = mysql_query($query);
        if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$num_rows = mysql_num_rows($rs);
		
	if($num_rows){
            while($row = mysql_fetch_assoc($rs)){
                if(md5($clave) == $row["clave"]){
                    $objResponse->alert("Acceso Concedido");
                    $objResponse->assign("pasoClave","value","SI");
                    $objResponse->script("$('divFlotante2').style.display='none';");
                }else{
                    $objResponse->alert("Clave incorrecta");
                }
            }
        }else{
             $objResponse->alert("Usted no posee acceso.");
        }
        
        return $objResponse;
}


$xajax->register(XAJAX_FUNCTION,"actualizarStatusOrden");
$xajax->register(XAJAX_FUNCTION,"aprobarOrden");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarOrden");
$xajax->register(XAJAX_FUNCTION,"cargarCliente");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresaBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoOrden");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoOrden");
$xajax->register(XAJAX_FUNCTION,"formAprobarOrden");
$xajax->register(XAJAX_FUNCTION,"guardarCliente");
$xajax->register(XAJAX_FUNCTION,"guardarFechaEntrega");
$xajax->register(XAJAX_FUNCTION,"listadoOrdenes");
$xajax->register(XAJAX_FUNCTION,"selectEstadoOrden");

$xajax->register(XAJAX_FUNCTION,"accesoSoloRepuesto");
$xajax->register(XAJAX_FUNCTION,"listadoClientes");


//comentado para que no detenga el proceso por si se llama la funcion
function enviarEmail($id_orden = ""){
	/*
    require_once 'PHPMailer/class.phpmailer.php';

    $campos0= "";
    $condicion0= "";
    $sql0= "";

    $campos0= "*";
    $condicion0= "descripcion_parametro= 'EMAIL FACTURACION' ";
    $condicion0.= "AND id_empresa= ".$_SESSION['idEmpresaUsuarioSysGts'];

    $sql0= "SELECT ".$campos0." FROM pg_parametros_empresas WHERE ".$condicion0.";";
    $rs0 = mysql_query($sql0);
	if (!$rs0) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
    $row0 = mysql_fetch_assoc($rs0);
    
    if ($row0['valor_parametro']) {

        $campos1= "";
        $condicion1= "";
        $sql1= "";

        $campos1= "*";
        $condicion1= "id_orden= ".$id_orden;

        $sql1= "SELECT ".$campos1." FROM sa_orden WHERE ".$condicion1.";";
        $rs1 = mysql_query($sql1);
		if (!$rs1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
        $row1 = mysql_fetch_assoc($rs1);



        $campos2= "";
        $condicion2= "";
        $sql2= "";

        $campos2= "*";
        $condicion2= "id_recepcion= ".$row1['id_recepcion'];

        $sql2= "SELECT ".$campos2." FROM sa_recepcion WHERE ".$condicion2.";";
        $rs2 = mysql_query($sql2);
		if (!$rs2) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
        $row2 = mysql_fetch_assoc($rs2);



        $campos3= "";
        $condicion3= "";
        $sql3= "";

        $campos3= "*";
        $condicion3= "id_cita= ".$row2['id_cita'];

        $sql3= "SELECT ".$campos3." FROM sa_cita WHERE ".$condicion3.";";
        $rs3 = mysql_query($sql3);
		if (!$rs3) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
        $row3 = mysql_fetch_assoc($rs3);


        $campos5= "";
        $condicion5= "";
        $sql5= "";

        $campos5= "id_unidad_basica";
        $campos5.= ", placa";
        $condicion5= "id_registro_placas= ".$row3['id_registro_placas'];

        $sql5= "SELECT ".$campos5." FROM en_registro_placas WHERE ".$condicion5.";";
        $rs5= mysql_query($sql5);
		if (!$rs5) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
        $row5= mysql_fetch_assoc($rs5);

        $campos6= "";
        $condicion6= "";
        $sql6= "";

        $campos6= "ub.id_marca,";
        $campos6.= "(SELECT m.nom_marca FROM an_marca m WHERE m.id_marca= ub.id_marca) AS nombre_marca,";
        $campos6.= "ub.id_modelo,";
        $campos6.= "(SELECT mo.nom_modelo FROM an_modelo mo WHERE mo.id_modelo= ub.id_modelo) AS nombre_modelo,";
        $campos6.= "ub.id_version,";
        $campos6.= "(SELECT v.nom_version FROM an_version v WHERE v.id_version= ub.id_version) AS nombre_version";

        $condicion6= "ub.id_unidad_basica= ".$row5['id_unidad_basica'];

        $sql6= "SELECT ".$campos6." FROM sa_v_unidad_basica ub WHERE ".$condicion6.";";
        $rs6= mysql_query($sql6);
		if (!$rs6) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
        $row6= mysql_fetch_assoc($rs6);

        $campos7= "";
        $condicion7= "";
        $sql7= "";

        $campos7= "*";
        $condicion7= "id_empresa= ".$_SESSION['idEmpresaUsuarioSysGts'];

        $sql7= "SELECT ".$campos7." FROM pg_empresa WHERE ".$condicion7.";";
        $rs7= mysql_query($sql7);
		if (!$rs7) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
        $row7= mysql_fetch_assoc($rs7);

        $campos4= "";
        $condicion4= "";
        $sql4= "";
        $rs4= "";
        $row4= "";

        if ($row2['id_cliente_pago']) {
            $campos4= "*";
            $condicion4= "id= ".$row2['id_cliente_pago'];

            $sql4= "SELECT ".$campos4." FROM cj_cc_cliente WHERE ".$condicion4.";";
            $rs4= mysql_query($sql4);
			if (!$rs4) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
            $row4= mysql_fetch_assoc($rs4);
        } else {
            $campos4= "*";
            $condicion4= "id= ".$row3['id_cliente_contacto'];

            $sql4= "SELECT ".$campos4." FROM cj_cc_cliente WHERE ".$condicion4.";";
            $rs4= mysql_query($sql4);
			if (!$rs4) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
            $row4= mysql_fetch_assoc($rs4);
        }

        if ($row4['correo'] != "") {
            date_default_timezone_set('America/Caracas');

            $mail = new PHPMailer();

            $mail->IsSMTP();
            $mail->Host= "mail.cantv.net";
            //$mail->SMTPDebug= 2;
            $mail->SMTPAuth= false;
            $mail->Host= "mail.cantv.net";
            $mail->Port= 25;

            $mail->Username= $row7['correo'];
            $mail->Password= "123456";
            $mail->SetFrom($row0['valor_parametro'], strtoupper($row7['nombre_empresa']));

            $mail->Subject= utf8_decode("Facturación de Servicio");
            $mail->AltBody= "To view the message, please use an HTML compatible email viewer!";

            $fechaSplit= split(" ", $row1['tiempo_entrega']);

            $fechaSola= split("-", $fechaSplit[0]);
            $horaSola= split(":", $fechaSplit[1]);

            $fechaNueva= $fechaSola[2]."-".$fechaSola[1]."-".$fechaSola[0];
            if($horaSola[0] <= 12){
                $horanueva= $fechaSplit[1]." AM";
            }else if($horaSola[0] > 12){
                $horanueva= ($horaSola[0]-12).":".$horaSola[1]." PM";
            }

            $fechaGuardar= $fechaNueva." ".$horanueva;


            $body= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
                        <html>
                            <head>
                                <style type='text/css' media='print'>
                                    @media print{
                                        .noprint{
                                            display:none;
                                        }
                                    }
                                </style>
                                <title>".strtoupper($row7['nombre_empresa'])." - Facturaci&oacute;n de Servicio</title>
                            </head>
                            <body>";
            $body.= "           <h2>Facturaci&oacute;n de Servicio</h2>
                                <hr/>
                                <br/>";
            $body.= "           <b>".strtoupper($row7['nombre_empresa'])."<br>RIF: ".$row7['rif']."</b>
                                <br/><br/>";
            $body.= "           Buenas,
                                <br/>";
            $body.= "           Estimado Cliente, ".$row4['nombre']." ".$row4['apellido']."
                                <br/>
                                <br/>
                                <br/>";
            $body.= "           <p style='text-align: justify'>
                                    Reciba un cordial saludo de parte de su Concesionario Altautos, nos complace dirigirnos a
                                    Usted para informarle que su veh&iacute;culo Marca <b>".strtoupper($row6['nombre_marca'])."</b>,
                                    Modelo <b>".strtoupper($row6['nombre_modelo'])."</b>,
                                    Version <b>".strtoupper($row6['nombre_version'])."</b>,
                                    Placa <b>".strtoupper($row5['placa'])."</b>  se encuentra listo y
                                    puede retirarlo en nuestras instalaciones a partir del <b>".$fechaGuardar."</b>.
                                    El monto a cancelar por el servicio recibido es de Bs. <b>".$row1['subtotal']."</b>, dicho monto no
                                    incluye el I.V.A; le recordamos que puede cancelar con sus  tarjetas de d&eacute;bito, cr&eacute;dito,
                                    efectivo y cheque conformable.
                                </p>";
            $body.= "           <br/>";
            $body.= "           <p style='text-align: justify'>
                                    En Altautos estamos totalmente a su disposici&oacute;n para atender cualquier solicitud que Usted requiera.
                                </p>";
            $body.= "           <br/>
                                <br/>
                                <br/>";
            $body.= "           <h3>".strtoupper($row7['nombre_empresa'])." nacimos con Calidad.</h3>";
            $body.= "           <hr/>";
            $body.= "           <div class='noprint'>
                                    <p style='text-align: justify'>
                                        <strong>Nota:</strong>
                                        Este correo fue enviado por un servicio autom&aacute;tico, no responda a este mensaje<br/><br/>
                                        <span style='color:#FF0000;'>
                                            <strong>ATENCI&Oacute;N:</strong>
                                        </span> puede que su visor de correo est&eacute; configurado para no descargar imagenes
                                        autom&aacute;ticamente, seleccione <strong>\"Descargar Imagenes\"</strong> en su visor
                                        para mostrar el contenido completo del mensaje si su visor no dispone de un medio de
                                        impresi&oacute;n directo como Outlook.
                                    </p>
                                </div>";
            $body.= "       </body>
                        </html>";
            
            $mail->MsgHTML($body);

            $address= $row4['correo'];
            $mail->AddAddress($address, $row4['nombre']." ".$row4['apellido']);

            $mail->AddAttachment("images/phpmailer.gif");

            if(!$mail->Send()){
                return $mail->ErrorInfo;
            }else{
                return "Email enviado a :".$row4['correo'];
            }
        }
    }*/
}
?>