<?php

//lo coloque aqui para que funcionara de una vez en facturacion
// MODIFICADO ERNESTO
if(file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")){
	include("../contabilidad/GenerarEnviarContabilidadDirecto.php");
}
// MODIFICADO ERNESTO

function reconversionArticulosTempario($idOrden){
	$objResponse = new xajaxResponse();
	$id_usuario = $_SESSION['idUsuarioSysGts'];

	$queryValidacion = sprintf("SELECT * FROM sa_orden_reconversion WHERE id_orden = %s",valTpDato($idOrden, "int"));
	$rsValidacion = mysql_query($queryValidacion);
	$bandera = mysql_num_rows($rsValidacion);
	// return $objResponse->alert("$queryValidacion    $bandera");
	if($bandera == 0){		
		$queryArticulo = sprintf("UPDATE sa_det_orden_articulo SET precio_unitario = precio_unitario/100000, costo = costo/100000 WHERE id_orden = %s",valTpDato($idOrden, "int"));
		$rsArticulo = mysql_query($queryArticulo);
		if (!$rsArticulo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryArticulo);

		$queryTempario = sprintf("UPDATE sa_det_orden_tempario SET precio = precio/100000, precio_tempario_tipo_orden = precio_tempario_tipo_orden/100000 WHERE id_orden = %s",valTpDato($idOrden, "int"));
		$rsTempario = mysql_query($queryTempario);
		if (!$rsTempario) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryTempario);

		$queryReconversion = sprintf("INSERT INTO sa_orden_reconversion(id_orden,id_usuario) VALUES (%s,%s)",valTpDato($idOrden, "int"),valTpDato($id_usuario, "int"));
		$rsReconversion = mysql_query($queryReconversion);
		if (!$rsReconversion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryReconversion);

		$mensaje = "Items Actualizados, Por favor haga click en 'Guardar'";
		$objResponse->alert("$mensaje");
		$objResponse->script("location.reload()");
		return $objResponse;
	}else{
		return $objResponse->alert("Los items esta orden ya fueron convertidos a Bolivares Soberanos, no puede repetir el proceso");
	}

}

//MUESTRA INFORMACION DEL ARTICULO AL SELECCIONARLO EN EL LISTADO DE ARTICULOS EN AGREGAR ARTICULOS DE LA ORDEN
function asignarArticulo($idArticulo, $valFormDcto, $hddNumeroArt = "", $valFormListaArticulo = "") {
	$objResponse = new xajaxResponse();
	
	//$objResponse->alert("Asignar articulo - idArticulo: ".$idArticulo." valFormDcto: ".$valFormDcto." hddnumeroArt: ".$hddNumeroArt." valformlistaarticulo: ".$valFormListaArticulo);//alerta
	
	$objResponse->script("
	if ($('rbtBuscarCodBarra').checked == true) {
	} else {
		document.forms['frmDatosArticulo'].reset();
		$('txtDescripcionArt').innerHTML = '';
	}");
	
	$idEmpresa = $valFormDcto['txtIdEmpresa'];
	$idCliente = $valFormDcto['txtIdCliente'];
	
	$idTipoOrden = $valFormDcto['lstTipoOrden'];
	// BUSQUEDA DEL ARTICULO POR EL ID
	$queryArticulo = sprintf("SELECT *,
		#(CASE (SELECT id_impuesto FROM iv_articulos_impuesto WHERE id_impuesto = 1 AND id_articulo = vw_iv_art.id_articulo LIMIT 1)
		#	WHEN '1' THEN
		#		(SELECT idIva FROM pg_iva WHERE estado = 1 AND (tipo = 6) ORDER BY iva)
		#END) AS id_iva,
		#
		#(CASE (SELECT id_impuesto FROM iv_articulos_impuesto WHERE id_impuesto = 1 AND id_articulo = vw_iv_art.id_articulo LIMIT 1)
		#	WHEN '1' THEN
		#		(SELECT iva FROM pg_iva WHERE estado = 1 AND (tipo = 6) ORDER BY iva)
		#END) AS iva,
		
		(SELECT fecha FROM iv_articulos_costos
		WHERE id_articulo = vw_iv_art.id_articulo
		ORDER BY id_articulo_costo DESC LIMIT 1) AS fecha_ultima_compra,
		
		(SELECT fecha_movimiento
			FROM iv_kardex
			WHERE iv_kardex.id_articulo = vw_iv_art.id_articulo
			AND iv_kardex.tipo_movimiento =3
			ORDER BY id_kardex DESC
			LIMIT 1) AS fecha_ultima_venta
		
	FROM vw_iv_articulos vw_iv_art
	INNER JOIN vw_iv_articulos_empresa ON vw_iv_art.id_articulo = vw_iv_articulos_empresa.id_articulo
	WHERE vw_iv_art.id_articulo = %s AND vw_iv_articulos_empresa.id_empresa = %s ",
		valTpDato($idArticulo, "int"),
		valTpDato($idEmpresa, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery:".$queryArticulo);
	
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	// BUSCA EL ULTIMO COSTO DEL ARTICULO
	$queryCostoArt = sprintf("SELECT * FROM iv_articulos_costos
	WHERE id_articulo = %s
	ORDER BY fecha_registro DESC
	LIMIT 1;",
		valTpDato($idArticulo, "int"));
	$rsCostoArt = mysql_query($queryCostoArt);
	if (!$rsCostoArt) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowCostoArt = mysql_fetch_assoc($rsCostoArt);
	
	$fechaUltimaCompra = ($rowArticulo['fecha_ultima_compra'] != "") ? date("d-m-Y",strtotime($rowArticulo['fecha_ultima_compra'])) : "----------";
	$fechaUltimaVenta = ($rowArticulo['fecha_ultima_venta'] != "") ? date("d-m-Y",strtotime($rowArticulo['fecha_ultima_venta'])) : "----------";
	
	//$cantidadDisponibleReal = $rowArticulo['cantidad_disponible_logica'] + $rowArticulo['cantidad_bloqueada'];
	$cantidadDisponibleReal = $rowArticulo['cantidad_disponible_logica'];
	
	$objResponse->assign("hddIdEmpresa","value",$idEmpresa);
	$objResponse->assign("hddIdArt","value",$idArticulo);
	$objResponse->assign("txtCodigoArt","value",elimCaracter($rowArticulo['codigo_articulo'],";"));
	$objResponse->assign("txtDescripcionArt","innerHTML",htmlentities($rowArticulo['descripcion']));
	$objResponse->assign("txtUnidadArt","value",htmlentities($rowArticulo['unidad']));
	$objResponse->assign("txtFechaUltCompraArt","value",$fechaUltimaCompra);
	$objResponse->assign("txtSeccionArt","value",utf8_encode($rowArticulo['descripcion_seccion']));
	$objResponse->assign("txtFechaUltVentaArt","value",$fechaUltimaVenta);
	$objResponse->assign("txtTipoPiezaArt","value",utf8_encode($rowArticulo['tipo_articulo']));
	$objResponse->assign("txtCantDisponible","value",$cantidadDisponibleReal);
	
        //si el cliente no es excento y el tipo de orden posee iva
        if(!clienteExento($idCliente) && tipoOrdenPoseeIva($idTipoOrden)){// proviene del ac_iv_general servicios
//            $objResponse->assign("hddIdIvaRepuesto","value",$rowArticulo['id_iva']);
//            $objResponse->assign("txtIvaRepuesto","value",$rowArticulo['iva']."%");
            
            $arrayIvas = ivaServicios(); //proviene de ac_iv_general devuelve array de arrays
            $ivasActivos = implode(",",array_keys($arrayIvas)); //devuelve id de ivas activos separados por coma
            
            if($ivasActivos){//para el query
                $queryIvasArticulo = sprintf("SELECT id_impuesto 
                                              FROM iv_articulos_impuesto 
                                              WHERE id_articulo = %s AND id_impuesto IN (%s)",
                                        valTpDato($idArticulo, "int"),
                                        $ivasActivos);
                $rsIvasArticulo = mysql_query($queryIvasArticulo);
                if (!$rsIvasArticulo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nQuery: ".$queryIvasArticulo); }
                
                
                $tablaImpuesto = "";
                while($rowIvasArticulo = mysql_fetch_assoc($rsIvasArticulo)){
                    
                    $arrayIdIvaDisponible[] = $rowIvasArticulo["id_impuesto"];
                    
                    $tablaImpuesto .= "<td align=\"right\" class=\"tituloCampo\">";
                        $tablaImpuesto .= $arrayIvas[$rowIvasArticulo["id_impuesto"]]["observacion"];
                    $tablaImpuesto .= "</td>";
                    
                    $tablaImpuesto .= "<td>";
                        $tablaImpuesto .= sprintf("<input size=\"6\" type=\"text\" readonly=\"readonly\" value=\"%s\" >",
                                $arrayIvas[$rowIvasArticulo["id_impuesto"]]["iva"]."%");
                    $tablaImpuesto .= "</td>";
                    
                }
                $ivaDisponible = implode(",",$arrayIdIvaDisponible);
                $inputDisponible = sprintf("<input type=\"hidden\" name=\"hddIdIvaRepuesto\" id=\"hddIdIvaRepuesto\" value=\"%s\" >",
                            $ivaDisponible);
                $tablaImpuesto .= "<td>".$inputDisponible."</td>";
                
                $objResponse->script("document.getElementById('impuestoPorArticulo').innerHTML = '".$tablaImpuesto."';");
                
            }
            
        }
	
	
	// VERIFICA VALORES DE CONFIGURACION (Manejar Costo de Repuesto) ROGER
			$queryConfig12 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
									  INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
									  WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
										valTpDato($idEmpresa, "int"));
			$rsConfig12 = mysql_query($queryConfig12);
			if (!$rsConfig12) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsConfig12 = mysql_num_rows($rsConfig12);
			$rowConfig12 = mysql_fetch_assoc($rsConfig12);
			
			if($rowConfig12["valor"] == 1){//es el costo a comprobar en la insercion
				$costoComparar = $rowCostoArt['costo'];
			}elseif($rowConfig12["valor"] == 2){
				$costoComparar = $rowCostoArt['costo_promedio'];
			}
			
	//$objResponse->assign("hddCostoArtRepuesto","value",$rowCostoArt['costo']);//ANTES este costo se evalua si se cambia el precio del listado, editado, subir,bajar, seguros
	  $objResponse->assign("hddCostoArtRepuesto","value",$costoComparar);
	
	
	if ($rowArticulo['decimales'] == 0) {
		$objResponse->script("
		if (navigator.appName == 'Netscape') {
			$('txtCantidadArt').onkeypress = function(e){ return validarSoloNumeros(e); }
		} else if (navigator.appName == 'Microsoft Internet Explorer') {
			$('txtCantidadArt').onkeypress = function(e){ return validarSoloNumeros(event); }
		}");
	} else if ($rowArticulo['decimales'] == 1) {
		$objResponse->script("
		if (navigator.appName == 'Netscape') {
			$('txtCantidadArt').onkeypress = function(e){ return validarSoloNumerosReales(e); }
		} else if (navigator.appName == 'Microsoft Internet Explorer') {
			$('txtCantidadArt').onkeypress = function(e){ return validarSoloNumeros(event); }
		}");
	}
	
	if ($hddNumeroArt == "") { // NO EXISTE EL ARTICULO EN LA LISTA DEL PRESUPUESTO
		$objResponse->assign("hddNumeroArt","value","");
		$objResponse->assign("txtCantidadArt","value","0");
		
		if ($cantidadDisponibleReal > 0) {
			$objResponse->script("$('txtCantDisponible').className = 'inputCantidadDisponible'");
			$objResponse->script("$('tdMsjArticulo').style.display = 'none';");
		} else {
			$objResponse->script("$('txtCantDisponible').className = 'inputCantidadNoDisponible'");
		}
		
		$objResponse->script("
		if ($('rbtBuscarCodBarra').checked == true) {
			$('txtCantidadArt').value++;
		}
		
		if ($('hddNumeroArt').value != '') {
			$('btnInsertarArticulo').click();
		} else {
			$('txtCantidadArt').focus();
			$('txtCantidadArt').select();
		}
		");
		
		
		// VERIFICACION PARA EL MANEJO DEL PRECIO SEGUN TIPO DE ORDEN
		$queryTipoOrden = sprintf("SELECT *	FROM sa_tipo_orden
		WHERE id_tipo_orden = %s;",
			valTpDato($idTipoOrden, "int"));
		$rsTipoOrden = mysql_query($queryTipoOrden);
		if (!$rsTipoOrden) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowTipoOrden = mysql_fetch_assoc($rsTipoOrden);
		
		$idPrecioSelec = $rowTipoOrden['id_precio_repuesto'];
		
		// VERIFICACION PARA EL MANEJO DEL PRECIO ESPECIAL POR ARTICULO SEGUN TIPO DE ORDEN
		$queryPrecioArticuloTipoOrden = sprintf("SELECT * FROM iv_articulos_precios_tipo_orden
		WHERE id_tipo_orden = %s
			AND id_articulo = %s;",
			valTpDato($idTipoOrden, "int"),
			valTpDato($idArticulo, "int"));
		$rsPrecioArticuloTipoOrden = mysql_query($queryPrecioArticuloTipoOrden);
		if (!$rsPrecioArticuloTipoOrden) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		while ($rowPrecioArticuloTipoOrden = mysql_fetch_assoc($rsPrecioArticuloTipoOrden)) {
			if ($rowPrecioArticuloTipoOrden['id_articulo'] == $rowArticulo['id_articulo'] && $asigPrecioArt != true) {
				$idPrecioSelec = $rowPrecioArticuloTipoOrden['id_precio'];
				$asigPrecioArt = true;
			} else if (!isset($asigPrecioArt)) {
				$idPrecioSelec = $rowTipoOrden['id_precio_repuesto'];
			}
		}
		
		// VERIFICACION PARA EL MANEJO DEL PRECIO ESPECIAL PARA CLIENTES
		$queryPrecioArticuloCliente = sprintf("SELECT * FROM iv_articulos_precios_cliente
		WHERE id_cliente = %s
			OR (id_cliente = %s AND id_articulo = %s);",
			valTpDato($idCliente, "int"),
			valTpDato($idCliente, "int"),
			valTpDato($idArticulo, "int"));
		$rsPrecioArticuloCliente = mysql_query($queryPrecioArticuloCliente);
		if (!$rsPrecioArticuloCliente) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		while ($rowPrecioArticuloCliente = mysql_fetch_assoc($rsPrecioArticuloCliente)) {	
			if ($rowPrecioArticuloCliente['id_articulo'] == $rowArticulo['id_articulo'] && $asigPrecioArt != true) {
				$idPrecioSelec = $rowPrecioArticuloCliente['id_precio'];
				$precioArt = true;
			} else if ($rowPrecioArticuloCliente['id_articulo'] == "" && !isset($precioArt)) {
				$idPrecioSelec = $rowPrecioArticuloCliente['id_precio'];
			}
		}
		
		
		$objResponse->loadCommands(asignarPrecio($idArticulo, $idPrecioSelec, true, "", $idEmpresa));
		
		// CARGA LOS PRECIOS DEL ARTICULO
		$query = sprintf("SELECT * FROM pg_precios WHERE estatus IN (1) OR (porcentaje = 0 AND estatus IN(2))");
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$html = "";
		while ($row = mysql_fetch_assoc($rs)) {
			$seleccion = "";
			if (($selId == $row['id_precio'] && $selId != "") || $row['id_precio'] == $idPrecioSelec) {
				$seleccion = "selected='selected'";
				
				if ($row['id_precio'] == $idPrecioSelec)
					$valorSelecPred = $row['id_precio'];
			}
			
			$html .= "<option value=\"".$row['id_precio']."\" ".$seleccion.">".htmlentities($row['descripcion_precio'])."</option>";
		}
		
		$onChange = sprintf("
		if(this.value != 6 && this.value != 7 && this.value != 12 && this.value != %s){
			xajax_formValidarPermisoEdicion('iv_catalogo_venta_precio_venta');
			selectedOption(this.id,'%s');
		}
		xajax_asignarPrecio('%s',this.value,'','',%s);",
			$idPrecioSelec,
			$valorSelecPred,
			$idArticulo,
			$idEmpresa);
		
		$htmlLstIni = "<select id=\"lstPrecioArt\" name=\"lstPrecioArt\" onchange=\"".$onChange."\" style=\"width:200px\">";
		$htmlLstFin = "</select>";
		
		$objResponse->assign("tdlstPrecioArt","innerHTML",$htmlLstIni.$html.$htmlLstFin);
		
	} else { // SI EL ARTICULO YA ESTA AGREGADO EN LA LISTA
		$objResponse->assign("hddNumeroArt","value",$hddNumeroArt);
		
		$objResponse->assign("txtCantidadArt","value",$valFormListaArticulo['hddCantArt'.$hddNumeroArt]+1);
		
		$objResponse->assign("hddIdIvaRepuesto","value",$valFormListaArticulo['hddIdIvaArt'.$hddNumeroArt]);
		$objResponse->assign("txtIvaRepuesto","value",$valFormListaArticulo['hddIvaArt'.$hddNumeroArt]."%");
		$objResponse->assign("hddCostoArtRepuesto","value",$valFormListaArticulo['hddCostoArt'.$hddNumeroArt]);
		
		$selId = $valFormListaArticulo['hddIdPrecioArt'.$hddNumeroArt];
		$precioUnitario = $valFormListaArticulo['hddPrecioArt'.$hddNumeroArt];
		
		
		$objResponse->loadCommands(asignarPrecio($idArticulo, $selId, false, $precioUnitario, $idEmpresa));
		
		// VERIFICACION PARA EL MANEJO DEL PRECIO ESPECIAL PARA CLIENTES
		$idPrecioSelec = $rowArticulo['id_precio_predeterminado'];
		$queryPrecioArtCliente = sprintf("SELECT * FROM iv_articulos_precios_cliente
		WHERE id_cliente = %s
			OR (id_cliente = %s AND id_articulo = %s);",
			valTpDato($idCliente, "int"),
			valTpDato($idCliente, "int"), valTpDato($idArticulo, "int"));
		$rsPrecioArtCliente = mysql_query($queryPrecioArtCliente);
		if (!$rsPrecioArtCliente) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		while ($rowPrecioArtCliente = mysql_fetch_assoc($rsPrecioArtCliente)) {	
			if ($rowPrecioArtCliente['id_articulo'] == $idArticulo) {
				$idPrecioSelec = $rowPrecioArtCliente['id_precio'];
				$asigPrecioArt = true;
			} else if ($rowPrecioArtCliente['id_articulo'] == "" && !isset($asigPrecioArt)) {
				$idPrecioSelec = $rowPrecioArtCliente['id_precio'];
			}
		}
		
		// CARGA LOS PRECIOS DEL ARTICULO ligado a pg_claves_modulos
		$query = sprintf("SELECT * FROM pg_precios WHERE estatus IN (1) OR (porcentaje = 0 AND estatus IN(2))");
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$html = "";
		while ($row = mysql_fetch_assoc($rs)) {
			$seleccion = "";
			if (($selId == $row['id_precio'] && $selId != "") || $row['id_precio'] == $idPrecioSelec) {
				$seleccion = "selected='selected'";
				
				if ($row['id_precio'] == $idPrecioSelec)
					$valorSelecPred = $row['id_precio'];
			}
			
			$html .= "<option value=\"".$row['id_precio']."\" ".$seleccion.">".htmlentities($row['descripcion_precio'])."</option>";
		}
		
		
		$seleccionAuto = ($selId != "") ? $selId : $valorSelecPred;
		
		$onChange = sprintf("
		if (this.value != 6 && this.value != 7 && this.value != 12 && this.value != %s) {
			xajax_formValidarPermisoEdicion('iv_catalogo_venta_precio_venta');
			selectedOption(this.id,'%s');
		}
		xajax_asignarPrecio('%s',this.value,'%s');",
			$idPrecioSelec,
			$seleccionAuto,
			$idArticulo,
			$precioUnitario);
		
		$htmlLstIni = "<select id=\"lstPrecioArt\" name=\"lstPrecioArt\" onchange=\"".$onChange."\">";
		$htmlLstFin = "</select>";
		
		$objResponse->assign("tdlstPrecioArt","innerHTML",$htmlLstIni.$html.$htmlLstFin);
		
		$objResponse->script("xajax_insertarArticulo(xajax.getFormValues('frmDatosArticulo'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalPresupuesto'));");
	}
	
	$objResponse->script("$('divFlotante2').style.display = 'none';");
	
	return $objResponse;
}


//ASIGNA EL PRECIO SEGUN EL ARTICULO Y SEGUN EL TIPO DE PRECIO
function asignarPrecio($idArticulo, $idPrecio, $precioPredet = false, $precio = "", $idEmpresa) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $_GET['ide'];
        
	//$objResponse->alert("ANTES idarticulos: ". $idArticulo ." idprecio: ". $idPrecio ." preciopredet: ". $precioPredet ." precio: ". $precio);//alerta articulo
	
	$query = sprintf("SELECT * FROM iv_articulos_precios
	WHERE id_articulo = %s AND id_precio = %s AND id_articulo_costo = (SELECT vw_iv_articulos_almacen_costo.id_articulo_costo FROM vw_iv_articulos_almacen_costo 
                                                                           WHERE id_articulo = %s AND id_empresa = %s AND estatus_almacen_venta = 1 AND cantidad_disponible_logica > 0 AND id_articulo_costo IS NOT NULL ORDER BY id_articulo_costo LIMIT 1)",
		valTpDato($idArticulo,"int"),		
		valTpDato($idPrecio,"int"),
                valTpDato($idArticulo,"int"),
		valTpDato($idEmpresa,"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("hddIdArtPrecioRepuesto","value",$row['id_articulo_precio']);
	$precio = ($row['precio'] > 0 && $row['id_precio'] != 6 && $row['id_precio'] != 7) ? $row['precio'] : $precio;
	$objResponse->assign("txtPrecioArtRepuesto","value",$precio);
	$objResponse->assign("hddBajarPrecio","value","");
	$objResponse->script("$('txtPrecioArtRepuesto').readOnly = true;");
	
	if ($precioPredet == true) {//predeterminado
		$objResponse->assign("hddPrecioArtAsig","value",$precio);
	}
	
	if ($idPrecio == 6) {//Precio Editado (Subir)
		$objResponse->assign("txtPrecioArtRepuesto","value",$precio);
		
		$objResponse->assign("tdDesbloquearPrecio","innerHTML","<img id=\"imgDesbloquearPrecio\" src=\"../img/iconos/lock_go.png\" onclick=\"xajax_formValidarPermisoEdicion('iv_catalogo_venta_precio_editado');\" style=\"cursor:pointer\" title=\"Desbloquear\"/>");
	} else if ($idPrecio == 7) {//Precio Editado (Bajar)
		$objResponse->assign("txtPrecioArtRepuesto","value",$precio);
		
		$objResponse->assign("tdDesbloquearPrecio","innerHTML","<img id=\"imgDesbloquearPrecio\" src=\"../img/iconos/lock_go.png\" onclick=\"xajax_formValidarPermisoEdicion('iv_catalogo_venta_precio_editado_bajar');\" style=\"cursor:pointer\" title=\"Desbloquear\"/>");
		} else if ($idPrecio == 12) {//Precio Editado (Debajo de costo)
		$objResponse->assign("txtPrecioArtRepuesto","value",$precio);
		
		$objResponse->assign("tdDesbloquearPrecio","innerHTML","<img id=\"imgDesbloquearPrecio\" src=\"../img/iconos/lock_go.png\" onclick=\"xajax_formValidarPermisoEdicion('iv_precio_editado_debajo_costo');\" style=\"cursor:pointer\" title=\"Desbloquear\"/>");
	} else {//Cualquier otro precio
		$precio = ($row['precio'] > 0) ? $row['precio'] : "";
		$objResponse->assign("txtPrecioArtRepuesto","value",$precio);
		
		$objResponse->script("$('txtPrecioArtRepuesto').readOnly = true;");
		$objResponse->assign("tdDesbloquearPrecio","innerHTML","");
		
		
		// SINO POSEE PRECIO ALGUNO SE PONDRA POR DEFECTO EL PREDETERMINADO DEL ARTICULO gregor
		if($precio == NULL || $precio == ""){
			$sql = sprintf("SELECT precio, (SELECT id_precio_predeterminado FROM iv_articulos WHERE id_articulo = %s LIMIT 1) as predeterminado_articulo
							FROM iv_articulos_precios WHERE id_articulo = %s
												AND id_precio = (SELECT id_precio_predeterminado FROM iv_articulos WHERE id_articulo = %s LIMIT 1) LIMIT 1",
												$idArticulo, $idArticulo, $idArticulo);
			$query = mysql_query($sql);
			if(!$query) { return $objResponse->alert("Error seleccionando precio predeterminado: ".mysql_error()."\n\n sql: ".$sql."\n\n Linea: ".__LINE__); }
			$row = mysql_fetch_assoc($query);
			$precio = $row['precio'];
			$idPredeterminadoArticulo = $row['predeterminado_articulo'];
			
			$objResponse->assign("txtPrecioArtRepuesto","value",$precio);
			$objResponse->assign("lstPrecioArt","value",$idPredeterminadoArticulo);
			$objResponse->alert("El articulo no posee tipo de precio definido, se usara el predeterminado");
		}
	}
	

	return $objResponse;
}

//MUESTRA INFORMACION DE LA MANO DE OBRA SELECCIONADA EN EL LISTADO DE MANOS DE OBRAS, EN AGREGAR MANOS DE OBRA ORDEN
function asignarTempario($idTemparioDet, $valFormDcto) {
	$objResponse = new xajaxResponse();
	
	$queryTipoOrden = sprintf("SELECT 
		id_tipo_orden,
		precio_tempario
	FROM sa_tipo_orden
	WHERE id_tipo_orden = %s",
		valTpDato($valFormDcto['lstTipoOrden'], "int"));
	$rsTipoOrden = mysql_query($queryTipoOrden);
	if (!$rsTipoOrden) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowTipoOrden = mysql_fetch_assoc($rsTipoOrden);
	 
	$queryBaseUt = sprintf("SELECT 
		valor_parametro
	FROM pg_parametros_empresas
	WHERE descripcion_parametro = 1
		AND id_empresa = %s",
		valTpDato($valFormDcto['txtIdEmpresa'],"int"));	
	$rsBaseUt = mysql_query($queryBaseUt);
	if (!$rsBaseUt) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowBaseUt = mysql_fetch_assoc($rsBaseUt);
	
	$queryTempario = sprintf("SELECT 
		id_tempario_det,
		id_tempario,
		codigo_tempario,
		descripcion_tempario,
		id_modo,
		id_unidad_basica,
		importe_por_tipo_orden,
		(CASE id_modo
			WHEN '1' THEN precio_por_tipo_orden * %s / %s
			WHEN '2' THEN precio_por_tipo_orden
			WHEN '3' THEN precio_por_tipo_orden
			WHEN '4' THEN precio_por_tipo_orden
		END) AS total_por_tipo_orden,
		precio_por_tipo_orden,
		base_ut,
		ut,
		precio,
		total_importe,
		descripcion_modo,
		operador,
		descripcion_operador,
		costo,
		id_seccion,
		id_subseccion,
		descripcion_seccion,
		descripcion_subseccion
	FROM vw_sa_temparios_por_unidad
	WHERE id_tempario_det = %s",
		$rowTipoOrden['precio_tempario'], $rowBaseUt['valor_parametro'],
		valTpDato($idTemparioDet,"int"));
	$rsTempario = mysql_query($queryTempario);
	if (!$rsTempario) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowTempario = mysql_fetch_assoc($rsTempario);
	
	if ($rowTempario['id_tempario'] != "") {
		$objResponse->assign("txtCodigoTemp","value", utf8_encode($rowTempario['codigo_tempario']));
		$objResponse->assign("txtDescripcionTemp","value",utf8_encode($rowTempario['descripcion_tempario']));
		$objResponse->assign("hddIdTemp","value",$rowTempario['id_tempario']);
		$objResponse->assign("txtIdModoTemp","value",$rowTempario['id_modo']);
		$objResponse->assign("txtOperador","value", utf8_encode($rowTempario['operador']));
		$objResponse->assign("txtDescripcionOperador","value",utf8_encode($rowTempario['descripcion_operador']));
		$objResponse->assign("txtModoTemp","value",utf8_encode($rowTempario['descripcion_modo']));
		$objResponse->assign("hddOrigenTempario","value","ORDEN");
		$objResponse->assign("txtPrecio","value",$rowTempario['precio_por_tipo_orden']);
		$objResponse->assign("txtPrecioTemp","value",number_format($rowTempario['total_por_tipo_orden'],2,".",","));
		$objResponse->assign("hddIdDetTemp","value",$rowTempario['id_tempario_det']);
		$objResponse->assign("txtSeccionTempario","value", utf8_encode($rowTempario['descripcion_seccion']));
		$objResponse->assign("hddSeccionTempario","value",$rowTempario['id_seccion']);
		$objResponse->assign("txtSubseccionTempario","value", utf8_encode($rowTempario['descripcion_subseccion']));
		$objResponse->assign("hddIdSubseccionTempario","value",$rowTempario['id_subseccion']);
		
		$objResponse->script("
		$('txtPrecioTemp').focus();
		$('txtPrecioTemp').select();");
	}
	
	$query = sprintf("SELECT 
		pg_empleado.nombre_empleado,
		pg_empleado.apellido,
		sa_mecanicos.id_mecanico
	FROM pg_empleado
		INNER JOIN sa_mecanicos ON (pg_empleado.id_empleado = sa_mecanicos.id_empleado)
		INNER JOIN pg_cargo_departamento ON (pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento)
		INNER JOIN pg_cargo ON (pg_cargo_departamento.id_cargo = pg_cargo.id_cargo)
		INNER JOIN pg_departamento ON (pg_cargo_departamento.id_departamento = pg_departamento.id_departamento)
	WHERE pg_departamento.id_empresa = %s",
		valTpDato($valFormDcto['txtIdEmpresa'],"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
	$html = "<select id=\"lstMecanico\" name=\"lstMecanico\" >";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<option value=\"".$row['id_mecanico']."\">".utf8_encode($row['nombre_empleado'])." ".utf8_encode($row['apellido'])."</option>";
	}
	$html .= "</select>";

	$objResponse->assign("tdlstMecanico","innerHTML",$html);	
	
	return $objResponse;
}

//MUESTRA LA INFORMACION DEL TOT SELECCIONADO EN EL LISTADO DE TOT
function asignarTot($id_tot, $idListTipoOrden = false) {
	$objResponse = new xajaxResponse();

	$queryTot = sprintf("SELECT * FROM sa_orden
		INNER JOIN vw_sa_orden_tot ON (sa_orden.id_orden = vw_sa_orden_tot.id_orden_servicio)
	WHERE vw_sa_orden_tot.id_orden_tot = %s",
		valTpDato($id_tot,"int"));
	$rsTot = mysql_query($queryTot);
	if (!$rsTot) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowTot = mysql_fetch_assoc($rsTot);
	
        if($idListTipoOrden == false || $idListTipoOrden == ""){
            return $objResponse->alert("No se ha seleccionado tipo de orden");
        } 
        
	$queryPorcentajeTot = sprintf("SELECT 
		sa_tipo_orden.id_tipo_orden,
		sa_tipo_orden.porcentaje_tot
	FROM sa_tipo_orden
	WHERE sa_tipo_orden.id_tipo_orden = %s",
		$idListTipoOrden);
	$rsPorcentajeTot =mysql_query($queryPorcentajeTot);
	if (!$rsPorcentajeTot) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowPorcentajeTot = mysql_fetch_assoc($rsPorcentajeTot);
	
	//SELECT PORCENTAJE DEL TOT PARA EL TIPO DE ORDEN
	if ($rowTot['id_orden_tot'] != "") {
		$objResponse->assign("numeroTotMostrar", "value", $rowTot['numero_tot']);//nuevo gregor
		$objResponse->assign("txtNumeroTot","value",$rowTot['id_orden_tot']);
		$objResponse->assign("txtProveedor","value",utf8_encode($rowTot['nombre']));
		$objResponse->assign("txtMonto","value",number_format($rowTot['monto_subtotal'],2,".",","));
		$objResponse->assign("txtFechaTot","value",$rowTot['fecha_factura_proveedor']);
		$objResponse->assign("txtTipoPagoTot","value", utf8_encode($rowTot['tipo_pago']));
		$objResponse->assign("hddIdPorcentajeTot","value",$rowPorcentajeTot['id_tipo_orden']);
		$objResponse->assign("txtPorcentaje","value",number_format($rowPorcentajeTot['porcentaje_tot'],2,".",","));
		$objResponse->assign("txtMontoTotalTot","value",number_format($rowTot['monto_subtotal'] + ($rowPorcentajeTot['porcentaje_tot']*$rowTot['monto_subtotal']/100),2,".",","));
	}	
	
        
        $queryPreciosTot = "SELECT id_precio_tot, descripcion, porcentaje FROM sa_precios_tot WHERE activo = 1";
	$rsPreciosTot = mysql_query($queryPreciosTot);
        
        if (!$rsPreciosTot) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
        
        $listadoPorcentajesTot = "<select id=\"listadoPorcentajesTot\" onChange= \"cambiarPrecioTot(this.value);\">";
        $listadoPorcentajesTot .= "<option value ='ORDENx".$rowPorcentajeTot['porcentaje_tot']."'>ORDEN ".$rowPorcentajeTot['porcentaje_tot']."%</option>";
        $listadoPorcentajesTot .= "<option value ='MANUALx'>MANUAL</option>";
        while($rowPreciosTot = mysql_fetch_assoc($rsPreciosTot)){
            //$listadoPorcentajesTot .= "<option value ='".$rowPreciosTot['id_precio_tot']."x".$rowPreciosTot['porcentaje']."'>".($rowPreciosTot["descripcion"])." ".$rowPreciosTot['porcentaje']."%</option>";
        }
        $listadoPorcentajesTot .= "</select>";
        
	$objResponse->script("$('divFlotante2').style.display='none';");
	$objResponse->assign("cambioPorcentajeTot","innerHTML", $listadoPorcentajesTot);
        
	return $objResponse;
}

//ASIGNA Y MUESTRA LA INFORMACION AL SELECCIONARLO EN EL LISTADO DE RECEPCION, LO AGREGA A LA ORDEN
function asignarValeRecepcion($idRecepcion, $accion = "", $valFormTotalDcto, $provieneListado = NULL) {
	$objResponse = new xajaxResponse();
		
	if ($accion == "" || $accion == 1) {
		$queryVerificarSiTieneUnaOrdenSinAsignar = sprintf("SELECT
			COUNT(*) AS nr_items
		FROM sa_orden
		WHERE sa_orden.id_recepcion = %s
			AND sa_orden.id_tipo_orden = 5",
			$idRecepcion);
		$rsVerificarSiTieneUnaOrdenSinAsignar = mysql_query($queryVerificarSiTieneUnaOrdenSinAsignar);
		if (!$rsVerificarSiTieneUnaOrdenSinAsignar) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowVerificarSiTieneUnaOrdenSinAsignar = mysql_fetch_assoc($rsVerificarSiTieneUnaOrdenSinAsignar);
		
		if ($rowVerificarSiTieneUnaOrdenSinAsignar['nr_items'] > 0) {
			$objResponse->alert("No puede escoger este vale, debido a que tiene asociado un tipo de Orden SIN ASIGNAR.");
			return $objResponse;
		}
	}
	
	if ($valFormTotalDcto['hddItemsCargados'] > 0) {
		$objResponse->alert('La orden tiene items cargados. Si desea escoger otro vale de recepcion, elimine los items cargados e intente nuevamente.');
	} else {
		$queryRecepcion = sprintf("SELECT *,
			(IFNULL(id_cliente_pago, id) ) AS id_cliente_pago_oden
		FROM vw_sa_vales_recepcion
		WHERE id_recepcion = %s",
			valTpDato($idRecepcion,"text"));
		$rsRecepcion = mysql_query($queryRecepcion);
		if (!$rsRecepcion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowRecepcion = mysql_fetch_assoc($rsRecepcion);
		
		$queryCliente = "SELECT *, CONCAT_WS('-',lci, ci) AS ci_cliente FROM cj_cc_cliente
		WHERE id =".$rowRecepcion['id_cliente_pago_oden'];
		$rsCliente = mysql_query($queryCliente);
		if (!$rsCliente) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowCliente = mysql_fetch_assoc($rsCliente);

		if ($rowCliente['credito'] == "si") {
			$sqlCreditoDisponible = sprintf("SELECT cliente_cred.creditodisponible
			FROM cj_cc_credito cliente_cred
				INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente_cred.id_cliente_empresa = cliente_emp.id_cliente_empresa)
			WHERE cliente_emp.id_cliente = %s
			AND cliente_emp.id_empresa = %s",
			valTpDato($rowCliente['id'], "int"),
			valTpDato($idEmpresa, "int"));// ERROR no existe $idEmpresa
			$rsCreditoDisponible= mysql_query($sqlCreditoDisponible);
			$rowCreditoDisponible= mysql_fetch_array($rsCreditoDisponible);
			
			$objResponse->assign("hddCreditoDisponible", "value", $rowCreditoDisponible['creditodisponible']);//no se usa en ningun lado el hddCreditoDisponible
		}
		
		
		$objResponse->assign("txtIdValeRecepcion","value",$rowRecepcion['id_recepcion']);
		$objResponse->assign("numeracionValeRecepcionMostrar","value",$rowRecepcion['numeracion_recepcion']);//nuevo gregor numeracion
		$objResponse->assign("txtFechaRecepcion","value",$rowRecepcion['fecha_entrada']);
		$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
		$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre']." ".$rowCliente['apellido']));
		$objResponse->assign("txtDireccionCliente","innerHTML",utf8_encode($rowCliente['direccion']));
		$objResponse->assign("txtTelefonosCliente","value",utf8_encode($rowCliente['telf']));
		$objResponse->assign("txtChasisVehiculo","value",utf8_encode($rowRecepcion['chasis']));
		$objResponse->assign("txtPlacaVehiculo","value",utf8_encode($rowRecepcion['placa']));	
		$objResponse->assign("hddIdUnidadBasica","value",$rowRecepcion['id_uni_bas']);
		$objResponse->assign("txtUnidadBasica","value",utf8_encode($rowRecepcion['nom_uni_bas']));
		$objResponse->assign("txtMarcaVehiculo","value",utf8_encode($rowRecepcion['nom_marca']));
		$objResponse->assign("hddIdModelo","value",utf8_encode($rowRecepcion['id_modelo']));
		$objResponse->assign("txtModeloVehiculo","value",utf8_encode($rowRecepcion['des_modelo']));
		$objResponse->assign("txtAnoVehiculo","value",utf8_encode($rowRecepcion['ano_uni_bas']));
		$objResponse->assign("txtColorVehiculo","value",utf8_encode($rowRecepcion['color']));
		$objResponse->assign("txtRifCliente","value",utf8_encode($rowCliente['ci_cliente']));
		$objResponse->assign("txtKilometrajeVehiculo","value",utf8_encode($rowRecepcion['kilometraje_recepcion']));
				
		$anio=substr($rowRecepcion['fecha_venta'],0,4);
		$mes=substr($rowRecepcion['fecha_venta'],5,2);
		$dia=substr($rowRecepcion['fecha_venta'],8,2);
		$fecha=$dia."-".$mes."-".$anio; 
		
		$objResponse->assign("txtFechaVentaVehiculo","value",$fecha);
		
		if ($_GET["acc"] == 1) {
			if($rowCliente['descuento'] > 0)
				$objResponse->script(sprintf("
				if(confirm('El Cliente tiene %s%s de Descuento Directo. Desea agregarlo?'))
					$('txtDescuento').value = %s;", $rowCliente['descuento'], "%", $rowCliente['descuento']));
		}
		
		/*
		if ($_GET['ret'] != 5){//este es el listado que molesta, gregor carga al iniciar y siempre elimina 
			if($_GET['doc_type'] != 3) {// 3 es facturacion			
				$objResponse->script("
				$('lstTipoOrden').value = '-1';
				$('lstTipoOrden').focus();");
			}
		}
		*/
		if ($_GET['idv'] == 1) {
			$objResponse->script("
			$('btnInsertarCliente').disabled = true;");
		}
			
		$objResponse->script("
		$('lstTipoOrden').focus();
		$('tblListados').style.display = 'none';
		$('tblArticulo').style.display = 'none';
		$('divFlotante2').style.display = 'none';
		$('divFlotante').style.display = 'none';");
		
                
                //filtro tipo de vale
                if($provieneListado == "SI"){
                    $queryTipoVale = sprintf("SELECT id_tipo_vale 
                                       FROM sa_recepcion 
                                       WHERE id_recepcion = %s LIMIT 1",
                            valTpDato($idRecepcion,"int"));
                    $rsTipoVale = mysql_query($queryTipoVale);
                    if(!$rsTipoVale) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
                    
                    $rowTipoVale = mysql_fetch_assoc($rsTipoVale);
                    if($rowTipoVale["id_tipo_vale"] != NULL){
                        $idTipoValeRecepcion = $rowTipoVale["id_tipo_vale"];
                        $objResponse->script("xajax_cargaLstTipoOrden('','".$idTipoValeRecepcion."');");
                    }                    
                }
                
		$objResponse->script("xajax_calcularTotalDcto();");
	}	
	
	return $objResponse;
}

//ES EL BUSCADOR PARA EL LISTADO DE ARTICULOS
function buscarArticulo($valForm, $valFormDcto, $valFormListaArticulo, $valFormTotalDcto){
	$objResponse = new xajaxResponse();

	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayVal = explode("|",$valFormTotalDcto['hddObj']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObj[] = $valor;
	}
	
	$codArticulo = "";
	for ($cont = 0; $cont <= $valForm['hddCantCodigo']; $cont++) {
		$codArticulo .= $valForm['txtCodigoArticulo'.$cont].";";
	}

	$auxCodArticulo = $codArticulo;
	$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
	$codArticulo = codArticuloExpReg($codArticulo);
	
        //SIEMPRE BUSQUEDA DE LISTADO GREGOR
        $valBusq = sprintf("%s|%s|%s|%s|%s",
                                $valFormDcto['txtIdEmpresa'],
                                $codArticulo,
                                $valForm['rbtBuscar'],
                                $valForm['txtDescripcionBusq'],
                                $valFormDcto['hddIdUnidadBasica']);

        $objResponse->loadCommands(listadoArticulos(0,"","",$valBusq));

        return $objResponse;
	
        
        
        
	$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
	$sqlBusq = $cond.sprintf("SELECT COUNT(id_articulo) FROM vw_iv_articulos_modelos_compatibles
	WHERE id_articulo = vw_iv_articulos_empresa_datos_basicos.id_articulo AND id_uni_bas = %s) > 0",
		valTpDato($valFormDcto['hddIdUnidadBasica'], "int"));
	
	if (strlen($valFormDcto['txtIdEmpresa']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valFormDcto['txtIdEmpresa'], "int"));
	}
	
	if ($auxCodArticulo != "---") {
		if ($codArticulo != "-1" && $codArticulo != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("codigo_articulo REGEXP %s",
				valTpDato($codArticulo, "text"));
		}
	}
	
	if (strlen($valForm['txtDescripcionBusq']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if ($valForm['rbtBuscar'] == 6) {
			$sqlBusq .= $cond.sprintf("id_articulo = %s", valTpDato($valForm['txtDescripcionBusq'], "int"));
		} else if ($valForm['rbtBuscar'] == 5) {
			$sqlBusq .= $cond.sprintf("descripcion LIKE %s", valTpDato("%".$valForm['txtDescripcionBusq']."%", "text"));
			$sqlBusq .= sprintf("OR codigo_articulo_prov LIKE %s", valTpDato("%".$valForm['txtDescripcionBusq']."%", "text"));
		} else if ($valForm['rbtBuscar'] == 4) {
			$sqlBusq .= $cond.sprintf("descripcion_subseccion LIKE %s", valTpDato($valForm['txtDescripcionBusq'], "text"));
		} else if ($valForm['rbtBuscar'] == 3) {
			$sqlBusq .= $cond.sprintf("descripcion_seccion LIKE %s", valTpDato($valForm['txtDescripcionBusq'], "text"));
		} else if ($valForm['rbtBuscar'] == 2) {
			$sqlBusq .= $cond.sprintf("tipo_articulo LIKE %s", valTpDato($valForm['txtDescripcionBusq'], "text"));
		} else if ($valForm['rbtBuscar'] == 1) {
			$sqlBusq .= $cond.sprintf("marca LIKE %s", valTpDato($valForm['txtDescripcionBusq'], "text"));
		}
	}
		
	$objResponse->assign("tdListadoArticulos","innerHTML","");
	
	if ($auxCodArticulo != "---" || strlen($valForm['txtDescripcionBusq']) > 0) {
		$query = sprintf("SELECT id_articulo FROM vw_iv_articulos_empresa_datos_basicos %s", $sqlBusq);
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		
		if ($totalRows == 1) {
			$row = mysql_fetch_assoc($rs);
			
			// VERIFICA SI ALGUN ARTICULO YA ESTA INCLUIDO EN EL DOCUMENTO
			$existe = false;
			if (isset($arrayObj)) {
				foreach($arrayObj as $indice => $valor) {
					if ($valFormListaArticulo['hddIdArt'.$valor] == $row['id_articulo']) {//frmPresupuesto
						$objResponse->script(sprintf("xajax_asignarArticulo('%s',xajax.getFormValues('frmPresupuesto'),'%s',xajax.getFormValues('frmListaArticulo'));",
							$row['id_articulo'],
							$valor));
						$existe = true;
						break;//agregue para que solo duplique la primera repetencia y no se valla en un loop
					}
				}
			}
			
			if ($existe == false) {//si es nuevo en el documento, correcto
				$objResponse->loadCommands(asignarArticulo($row['id_articulo'],$valFormDcto));
			}
			
			$objResponse->script("
			$('txtDescripcionBusq').value = '';
			"); 
		} else if ($totalRows > 1) {
			$valBusq = sprintf("%s|%s|%s|%s|%s",
						$valFormDcto['txtIdEmpresa'],
						$codArticulo,
						$valForm['rbtBuscar'],
						$valForm['txtDescripcionBusq'],
						$valFormDcto['hddIdUnidadBasica']);
			
			$objResponse->loadCommands(listadoArticulos(0,"","",$valBusq));
		} else {
			$htmlTblIni .= "<table border=\"1\" class=\"tabla texto_9px\" cellpadding=\"2\" width=\"100%\">";
			$htmlTb .= "<td colspan=\"11\">";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
					$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTblFin .= "</table>";
			
			$objResponse->assign("tdListadoArticulos","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		}
	}
	return $objResponse;	
}



//EN EL LISTADO DE SELECCION DE PAQUETES, BOTON ABRIR PAQUETE PARA LUEGO ASIGNAR
function buscar_mano_obra_repuestos_por_paquete($idPaquete,$codigoPaquete,$descripcionPaquete,$precio_paquete,$origen, $itemManoObraAprob, $itemRepAprob, $accionVistaPaquete){
	$objResponse = new xajaxResponse();
		
	$itemManoObraAprob2 = $itemManoObraAprob;
	$itemRepAprob2 = $itemRepAprob;
		
	if ($itemManoObraAprob == "") {
		//CONTROLAR SI SE VA AGREGAR UN PAQ O SE VA A VISUALIZAR
		$objResponse->script("
		$('btnCancelarDivSecPaq').style.display = '';
		$('btnCancelarDivPpalPaq').style.display = 'none';
		$('btnAsignarPaquete').style.display = '';");		
	} else {
		$objResponse->script("
		$('btnCancelarDivSecPaq').style.display = 'none';
		$('btnCancelarDivPpalPaq').style.display = '';
		$('btnAsignarPaquete').style.display = 'none';");		
	}	

	if($itemManoObraAprob2!="") {
		$itemManoObraAprob2[0] = ' ';
		$itemManoObraAprob2 = str_replace("|",",",$itemManoObraAprob2);
	} else {
		$itemManoObraAprob2 = 0;
	}
	if($itemRepAprob2!="") {
		$itemRepAprob2[0] = ' ';
		$itemRepAprob2 = str_replace("|",",",$itemRepAprob2);
	} else {
		$itemRepAprob2 = 0;
	}
		
	$objResponse->script("	
	$('tdListadoTempario').style.display = '';
	$('tdListadoRepuestos').style.display = '';	
	
	$('tdListadoPaquetes').style.display='none';
	$('tblBuscarPaquete').style.display='none';

	var arrayTempaPaq = $('hddObjTemparioPaq').value;
	arrayTempaPaq = arrayTempaPaq.substring(1);
	var arrayTempaPaqComp = arrayTempaPaq.split('|');
	
	var arrayRepPaq = $('hddObjRepuestoPaq').value;
	arrayRepPaq = arrayRepPaq.substring(1);
	var arrayRepPaqComp = arrayRepPaq.split('|');
	
	var arrayMaObAp = [".$itemManoObraAprob2."];
	var arrayReAp = [".$itemRepAprob2."];
	
	$('tblBuscarPaquete').style.display = 'none';
	$('tdListadoPaquetes').style.display = 'none';
	$('tblListadoTemparioPorPaquete').style.display = '';
	$('tdListadoTempario').style.display = '';
	$('tblListadoRepuestosPorPaquete').style.display = '';
	$('tdListadoRepuestos').style.display = '';
	$('trPieTotalPaq').style.display = '';

	$('txtDescripcionPaquete').style.display='';
	$('txtCodigoPaquete').style.display='';
	$('tblListadoTemparioPorPaquete').style.display='';
	$('tblListadoRepuestosPorPaquete').style.display='';
	$('txtDescripcionPaquete').value='".$descripcionPaquete."';
	$('txtCodigoPaquete').value='".$codigoPaquete."';
	$('hddEscogioPaquete').value='".$idPaquete."';
	
        xajax.call('listado_tempario_por_paquetes', {mode:'synchronous', parameters:['0','','', $('txtIdEmpresa').value + '|".$idPaquete."|' + $('lstTipoOrden').value + '|' + $('hddAccionTipoDocumento').value + '|".$origen."|' + $('txtIdPresupuesto').value + '|' + arrayTempaPaqComp + '|' + arrayMaObAp + '|".$accionVistaPaquete."|' + $('hddIdUnidadBasica').value]});
        xajax.call('listado_repuestos_por_paquetes', {mode:'synchronous', parameters:['0','','', $('txtIdEmpresa').value + '|".$idPaquete."|' + $('lstTipoOrden').value + '|' + $('hddAccionTipoDocumento').value + '|".$origen."|' + $('txtIdPresupuesto').value  + '|' + arrayRepPaqComp + '|' + arrayReAp + '|".$accionVistaPaquete."|' + $('txtIdCliente').value]});
        xajax.call('calcularTotalPaquete', {mode:'synchronous', parameters:[xajax.getFormValues('frmDatosPaquete'), $('txtIdCliente').value, $('lstTipoOrden').value]});
        
//	xajax_listado_tempario_por_paquetes('0','','', $('txtIdEmpresa').value + '|".$idPaquete."|' + $('lstTipoOrden').value + '|' + $('hddAccionTipoDocumento').value + '|".$origen."|' + $('txtIdPresupuesto').value + '|' + arrayTempaPaqComp + '|' + arrayMaObAp + '|".$accionVistaPaquete."|' + $('hddIdUnidadBasica').value);
	
	//xajax_listado_repuestos_por_paquetes('0','','', $('txtIdEmpresa').value + '|".$idPaquete."|' + $('lstTipoOrden').value + '|' + $('hddAccionTipoDocumento').value + '|".$origen."|' + $('txtIdPresupuesto').value  + '|' + arrayRepPaqComp + '|' + arrayReAp + '|".$accionVistaPaquete."|' + $('txtIdCliente').value);");
	
	return $objResponse;	
}


//BUSCA LOS MOVIMIENTOS DEL ARTICULO, OJO EN REPUESTO EN ORDEN Y LUEGO MUESTRA EL LISTADO
function buscarMtoArticulo($idDetalleOrden, $codigoArticulo, $descripcionArticulo){
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s",
		$idDetalleOrden,
		$codigoArticulo,
		$descripcionArticulo);
	
	$objResponse->script("xajax_verMovimientosArticulo('0','id_solicitud','DESC','".$valBusq."')");
	
	return $objResponse;	
}

//BOTON VER TODOS LOS PAQUETES DESDE ORDEN
function buscarPaquete($valForm){
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s",
		$valForm['txtDescripcionBusq']);
	
	$objResponse->script("xajax_listado_paquetes_por_unidad('0','','',$('txtIdEmpresa').value + '|' + $('hddIdUnidadBasica').value + '|' + $('lstTipoOrden').value + '|".$valBusq."');");
	return $objResponse;	
}

//BOTON VER TEMPARIOS DESDE ORDEN
function buscarTempario($valForm, $valFormTotalDcto){
	$objResponse = new xajaxResponse();
		
	if ($valFormTotalDcto['hddTipoDocumento'] == 1) {
		$objResponse->script("
		$('tdlstMecanico').style.display = 'none';
		$('tdMecanico').style.display = 'none';");
	} else if ($valFormTotalDcto['hddMecanicoEnOrden'] == 1) {
		$objResponse->script("
		$('tdlstMecanico').style.display = '';
		$('tdMecanico').style.display = '';");
	} else {
		$objResponse->script("
		$('tdlstMecanico').style.display = 'none';
		$('tdMecanico').style.display = 'none';");
	}
	
	$valBusq = sprintf("%s|%s|%s",
		$valForm['txtCriterioTemp'],
		$valForm['lstSeccionTemp'],
		$valForm['lstSubseccionTemp']);
	
	$objResponse->script("xajax_listadoTempario('0','','',$('txtIdEmpresa').value + '|' + $('hddIdUnidadBasica').value + '|' + $('lstTipoOrden').value + '|".$valBusq."');");
	
	return $objResponse;	
}

//BOTON VER TOT DESDE ORDEN
function buscarTot($valForm,$valFormTotalDcto){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['txtDescripcionBusq']);
	
	$objResponse->script("xajax_listado_tot('0','','',$('txtIdEmpresa').value + '|' + $('txtIdPresupuesto').value + '|' + $('lstTipoOrden').value + '|".$valBusq."');");
	return $objResponse;		
}

//BOTON BUSCAR VALE DESDE ORDEN
function buscarValeRecepcion($valForm, $valFormVale){
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s",
		$valForm['txtIdEmpresa'],
		$valFormVale['txtPalabra']);
	
	$objResponse->script("xajax_listadoValeRecepcion('0','numeracion_recepcion','DESC','".$valBusq."')");
	return $objResponse;	
}

//SE EJECUTA 2 VECES, NO CUANDO ES NUEVO Y CALCULA EL TOTAL AL ABRIR LA ORDEN
function calcularDcto($valFormDcto, $valForm, $valFormTotalDcto, $valFormPaq, $valFormTemp, $valFormNotas, $valFormTot){
	$objResponse = new xajaxResponse();
        
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	for ($cont = 0; $cont <= strlen($valFormTotalDcto['hddObj']); $cont++) {
		$caracter = substr($valFormTotalDcto['hddObj'], $cont, 1);
		
		if ($caracter != "|" && $caracter != "")
			$cadena .= $caracter;
		else {
			$arrayObj[] = $cadena;
			$cadena = "";
		}	
	}
	for ($contPaq = 0; $contPaq <= strlen($valFormTotalDcto['hddObjPaquete']); $contPaq++) {
		$caracterPaq = substr($valFormTotalDcto['hddObjPaquete'], $contPaq, 1);
		
		if ($caracterPaq != "|" && $caracterPaq != "")
			$cadenaPaq .= $caracterPaq;
		else {
			$arrayObjPaq[] = $cadenaPaq;
			$cadenaPaq = "";
		}	
	}
	
	for ($contTemp = 0; $contTemp <= strlen($valFormTotalDcto['hddObjTempario']); $contTemp++) {
		$caracterTemp = substr($valFormTotalDcto['hddObjTempario'], $contTemp, 1);
		
		if ($caracterTemp != "|" && $caracterTemp != "")
			$cadenaTemp .= $caracterTemp;
		else {
			$arrayObjTemp[] = $cadenaTemp;
			$cadenaTemp = "";
		}	
	}
	
	for ($contTot = 0; $contTot <= strlen($valFormTotalDcto['hddObjTot']); $contTot++) {
		$caracterTot = substr($valFormTotalDcto['hddObjTot'], $contTot, 1);
		
		if ($caracterTot != "|" && $caracterTot != "")
			$cadenaTot .= $caracterTot;
		else {
			$arrayObjTot[] = $cadenaTot;
			$cadenaTot = "";
		}	
	}
	
	for ($contNota = 0; $contNota <= strlen($valFormTotalDcto['hddObjNota']); $contNota++) {
		$caracterNota = substr($valFormTotalDcto['hddObjNota'], $contNota, 1);
		
		if ($caracterNota != "|" && $caracterNota != "")
			$cadenaNota .= $caracterNota;
		else {
			$arrayObjNota[] = $cadenaNota;
			$cadenaNota = "";
		}	
	}
	
	for ($contDcto = 0; $contDcto <= strlen($valFormTotalDcto['hddObjDescuento']); $contDcto++) {
		$caracterDcto = substr($valFormTotalDcto['hddObjDescuento'], $contDcto, 1);
		
		if ($caracterDcto != "|" && $caracterDcto != "")
			$cadenaDcto .= $caracterDcto;
		else {
			$arrayObjDcto[] = $cadenaDcto;
			$cadenaDcto = "";
		}	
	}
        
        $arrayIvasOrden = $valFormTotalDcto["ivaActivo"];//array con los checkbox activos ivas
        foreach($arrayIvasOrden as $indice => $idIvaActivo){//lleno array con los ivas disponibles
            $arrayIvasBaseImponible[$idIvaActivo] = 0;
            $arrayIvasSubTotal[$idIvaActivo] = 0;
        }
        
	$subTotal = 0;
	$totalExento = 0;
//	$totalExonerado = 0;
//	$arrayIva = NULL;
//	$arrayDetalleIva = NULL;
	$montoExento = 0;
	$baseImponible = 0;
	
	$totalTempTotal = 0;
	$totalArtTotal = 0;
	
        //PAQUETES
	if (isset($arrayObjPaq)) {
		foreach($arrayObjPaq as $indicePaq => $valorPaq) {
			$objResponse->assign(sprintf("hddValorCheckAprobPaq%s",$valorPaq),"value", 0);
			
			if (isset($valFormPaq['cbxItmPaqAprob'])) {
				foreach($valFormPaq['cbxItmPaqAprob'] as $indiceAprob => $valorAprob) {
					if ($valorPaq == $valorAprob) {
						$objResponse->assign(sprintf("hddValorCheckAprobPaq%s",$valorPaq),"value",1);
						
						$subTotalPaq = $valFormPaq['hddPrecPaq'.$valorPaq];//$valForm['hddCantArt'.$valor]*
						$descuentoPaq = ($valFormTotalDcto['txtDescuento'] * $subTotalPaq) / 100;
						$subTotalPaq = $subTotalPaq - $descuentoPaq;
						
						
						$subTotal += doubleval($valFormPaq['hddPrecPaq'.$valorPaq]);
						
						$totalTempTotal += doubleval($valFormPaq['hddTotalTempPqte'.$valorPaq]);
						$totalArtTotal += doubleval($valFormPaq['hddTotalRptoPqte'.$valorPaq]);
						
						$totalExento += doubleval($valFormPaq['hddTotalExentoRptoPqte'.$valorPaq]);
						
						$baseImponible += doubleval($valFormPaq['hddTotalTempPqte'.$valorPaq]);
                                                //$baseImponible += doubleval($valFormPaq['hddTotalConIvaRptoPqte'.$valorPaq]);//anterior directo
                                                
                                                //Nuevo por ivas
                                                $arrayIdIvasRepuestoPaquete = explode(",",$valFormPaq['hddIdIvasRepuestoPaquete'.$valorPaq]);
                                                $arrayIvasRepuestoPaquete = explode(",",$valFormPaq['hddIvasRepuestoPaquete'.$valorPaq]);//base imponible por iva
                                                $arrayPorcentajesRepuestoPaquete = explode(",",$valFormPaq['hddPorcentajesIvasRepuestoPaquete'.$valorPaq]);
                                                foreach($arrayIdIvasRepuestoPaquete as $key => $idIva){

                                                    $subTotalArtPaquete = $arrayIvasRepuestoPaquete[$key];
                                                    $descuentoArtPaquete = ($valFormTotalDcto['txtDescuento']*$subTotalArtPaquete)/100;
                                                    $subTotalArtPaquete = $subTotalArtPaquete - $descuentoArtPaquete;
                                                    $subTotalIvaArtPaquete = ($subTotalArtPaquete*$arrayPorcentajesRepuestoPaquete[$key])/100;//rollo devuelve 4 decimales y hace la orden 1 decimal de mas
                                                    

                                                    $arrayIvasBaseImponible[$idIva] += $subTotalArtPaquete;
                                                    $arrayIvasSubTotal[$idIva] += $subTotalIvaArtPaquete;
                                                }
						
						
					}	
				}
			}
		}
	}	
	
	//return $objResponse->alert($subTotal);
	//TOT
	if (isset($arrayObjTot)) {
		foreach($arrayObjTot as $indiceTot => $valorTot) {
			$objResponse->assign(sprintf("hddValorCheckAprobTot%s",$valorTot),"value", 0);
			if (isset($valFormTot['cbxItmTotAprob'])) {
				foreach($valFormTot['cbxItmTotAprob'] as $indiceAprob => $valorAprob) {
					if($valorTot == $valorAprob) {	
							$objResponse->assign(sprintf("hddValorCheckAprobTot%s",$valorTot),"value",1);
							$baseImponible = $baseImponible + $valFormTot['hddMontoTotalTot'.$valorTot];

							$subTotalPaq = $valFormTot['hddMontoTotalTot'.$valorTot];//$valForm['hddCantArt'.$valor]*
							$descuentoPaq = ($valFormTotalDcto['txtDescuento']*$subTotalPaq)/100;
							$subTotalPaq = $subTotalPaq - $descuentoPaq;
							
							//$totalExento += $subTotalPaq;
							$subTotal += doubleval($valFormTot['hddMontoTotalTot'.$valorTot]);
						}	
					}
				}
			}
		}		
                
        //NOTAS
	if (isset($arrayObjNota)) {
		foreach($arrayObjNota as $indiceNota => $valorNota) {
		
			//SE INICIALIZAN EN 0 Y DESPUES SE LES COLOCA EL CHECK
			$objResponse->assign(sprintf("hddValorCheckAprobNota%s",$valorNota),"value", 0);
			if (isset($valFormNotas['cbxItmNotaAprob'])) {
				foreach($valFormNotas['cbxItmNotaAprob'] as $indiceAprob => $valorAprob) {
					if($valorNota == $valorAprob) {
						$objResponse->assign(sprintf("hddValorCheckAprobNota%s",$valorNota),"value", 1);
						$baseImponible = $baseImponible + $valFormNotas['hddPrecNota'.$valorNota];

						$subTotalNota= ($valFormNotas['hddPrecNota'.$valorNota]);
						$descuentoNota = ($valFormTotalDcto['txtDescuento']*$subTotalNota)/100;
						$subTotalNota = $subTotalNota - $descuentoNota;
						//$totalExento += $subTotalNota;
						$subTotal += doubleval($valFormNotas['hddPrecNota'.$valorNota]);
					}
				}
			}
		}
	}	
        
        //TEMPARIOS MANOS DE OBRA
	if (isset($arrayObjTemp)) {
		foreach($arrayObjTemp as $indiceTemp => $valorTemp) {
			$objResponse->assign(sprintf("hddValorCheckAprobTemp%s",$valorTemp),"value", 0);

			if (isset($valFormTemp['cbxItmTempAprob'])) {
				foreach($valFormTemp['cbxItmTempAprob'] as $indiceAprob => $valorAprob) {
					if($valorTemp == $valorAprob) {
						$objResponse->assign(sprintf("hddValorCheckAprobTemp%s",$valorTemp),"value", 1);

							$baseImponible = $baseImponible + $valFormTemp['hddPrecTemp'.$valorTemp];
							$subTotalTemp = $valFormTemp['hddPrecTemp'.$valorTemp];
							$descuentoTemp = ($valFormTotalDcto['txtDescuento']*$subTotalTemp)/100;
							$subTotalTemp = $subTotalTemp - $descuentoTemp;
							
							//$totalExento += $subTotalTemp;
							$subTotal += doubleval($valFormTemp['hddPrecTemp'.$valorTemp]);
						
						$totalTempTotal += doubleval($valFormTemp['hddPrecTemp'.$valorTemp]);
					}
				}
			}
		}
	}	
		
        //REPUESTOS
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
		$objResponse->assign(sprintf("hddValorCheckAprobRpto%s", $valor),"value", 0);
		
		if (isset($valForm['cbxItmAprob'])) {

			foreach($valForm['cbxItmAprob'] as $indiceAprob => $valorAprob) {
				if($valor == $valorAprob) {//solo calcular los parobados
					$objResponse->assign(sprintf("hddValorCheckAprobRpto%s", $valor),"value", 1);

                                        //YA NO REQUERIDO COMENTADO
//					$queryIva = sprintf("SELECT * FROM pg_iva WHERE tipo= %s AND activo= %s AND estado= %s", 6, 1, 1);
//					$rsIva = mysql_query($queryIva);
//					if (!$rsIva) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
//					$rowIva = mysql_fetch_assoc($rsIva);
					
					if ($valForm['hddIdIvaArt'.$valor] == 0 && $valForm['hddIvaArt'.$valor] == "") {//SIN IVA EXENTO
						$subTotalArt = ($valForm['hddCantArt'.$valor]*$valForm['hddPrecioArt'.$valor]);
						$totalExento += doubleval($subTotalArt);
						
						$montoExento = $montoExento + $valForm['hddPrecioArt'.$valor];
						$descuentoArt = ($valFormTotalDcto['txtDescuento']*$subTotalArt)/100;
						$subTotalArt = $subTotalArt - $descuentoArt;
						
						
					} else {//CON IVA CALCULAR
                                        
                                            $arrayIdIvasRepuesto = explode(",",$valForm['hddIdIvaArt'.$valor]);
                                            $arrayIvasRepuesto = explode(",",$valForm['hddIvaArt'.$valor]);
                                            foreach($arrayIdIvasRepuesto as $key => $idIva){
                                                
                                                $subTotalArt = ($valForm['hddCantArt'.$valor]*$valForm['hddPrecioArt'.$valor]);
                                                $descuentoArt = ($valFormTotalDcto['txtDescuento']*$subTotalArt)/100;
                                                $subTotalArt = $subTotalArt - $descuentoArt;
                                                $subTotalIvaArt = ($subTotalArt*$arrayIvasRepuesto[$key])/100;//otro rollo decimales
                                                
                                                $arrayIvasBaseImponible[$idIva] += $subTotalArt;
                                                $arrayIvasSubTotal[$idIva] += $subTotalIvaArt;
                                            }
                                            
                                            
                                            //COMENTADO GREGOR
//						$ivaArt =($valFormDcto['txtIdPresupuesto'] != "") ? $valForm['hddIvaArt'.$valor] : $rowIva['iva'];
//						
//						$existIva = "NO";
//						if (isset($arrayIva)) {
//							foreach($arrayIva as $indiceIva => $valorIva) {
//								if($arrayIva[$indiceIva][0] == $valForm['hddIdIvaArt'.$valor]) {
//									$subTotalArt = ($valForm['hddCantArt'.$valor]*$valForm['hddPrecioArt'.$valor]);
//									$descuentoArt = ($valFormTotalDcto['txtDescuento']*$subTotalArt)/100;
//									$subTotalArt = $subTotalArt - $descuentoArt;
//									$subTotalIvaArt = ($subTotalArt*$ivaArt)/100;
//									
//									$arrayIva[$indiceIva][1] += $subTotalArt;
//									$arrayIva[$indiceIva][2] += $subTotalIvaArt;
//									$existIva = "SI";
//                                                                        $objResponse->alert("array iva ejecucion?");
//								}
//							}
//						}
//						
//						if ($rowIva['idIva'] != "" && $existIva == "NO" && ($valForm['hddCantArt'.$valor]*$valForm['hddPrecioArt'.$valor]) > 0) {
//							$subTotalArt = ($valForm['hddCantArt'.$valor]*$valForm['hddPrecioArt'.$valor]);
//							$descuentoArt = ($valFormTotalDcto['txtDescuento']*$subTotalArt)/100;
//							$subTotalArt = $subTotalArt - $descuentoArt;
//							$subTotalIvaArt = ($subTotalArt*$ivaArt)/100;
//							
//							$arrayDetalleIva[0] = $valForm['hddIdIvaArt'.$valor];							
//							$arrayDetalleIva[1] = $subTotalArt;
//							$arrayDetalleIva[2] = $subTotalIvaArt;
//							$arrayDetalleIva[3] = $ivaArt;
//							$arrayIva[] = $arrayDetalleIva;
//						}
						//COMENTADO, creo que debe usarse el del array
                                            //$baseImponible = $baseImponible + ($valForm['hddPrecioArt'.$valor] * $valForm['hddCantArt'.$valor]) ;
					}
					
					$subTotal += doubleval($valForm['hddTotalArt'.$valor]);
					$totalArtTotal += doubleval($valForm['hddCantArt'.$valor]*$valForm['hddPrecioArt'.$valor]);
                                        
					}
				}
			}
		}
	}	
        
	//print_r("-----------------------------".$totalArtTotal."-----------------------------------");
	if (isset($arrayObjDcto)) {
		foreach($arrayObjDcto as $indiceDcto => $valorDcto) {
			
			if($valFormTotalDcto['hddIdDcto'.$valorDcto] != "")
			{
				if($valFormTotalDcto['hddIdDcto'.$valorDcto] == 1)//DCTO MANO OBRA
					$descuentoAdicional = ($valFormTotalDcto['hddPorcDcto'.$valorDcto] * $totalTempTotal)/100;
				else 
					if($valFormTotalDcto['hddIdDcto'.$valorDcto] == 2)// DCTO REPUESTO
						$descuentoAdicional = ($valFormTotalDcto['hddPorcDcto'.$valorDcto] * $totalArtTotal)/100;
						
				$objResponse->assign(sprintf("txtTotalDctoAdcl%s", $valorDcto),"value", number_format($descuentoAdicional,2,".",","));
			}
			$totalDescuentoAdicional += doubleval($descuentoAdicional);
		}
	}
		
	if ($valFormTotalDcto['hddAccionTipoDocumento'] != 2) {
		$query = sprintf("SELECT 
			sa_tipo_orden.posee_iva
		FROM sa_tipo_orden
		WHERE sa_tipo_orden.id_tipo_orden = %s",
			$valFormDcto['lstTipoOrden']);
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$poseeIva = $row['posee_iva'];
		
		//revalidar que agarre iva gregor
		if($valFormDcto['lstTipoOrden'] == NULL || $valFormDcto['lstTipoOrden'] == "" || $valFormDcto['lstTipoOrden'] == "-1"){//valido si se envio el tipo de orden sino la busco
			if($_GET["id"] != NULL || $_GET["id"] != ""){//busco el tipo de orden por el id_orden sino se envio, y asi recuperar el iva
				$query = sprintf("SELECT sa_tipo_orden.posee_iva
								  FROM sa_orden
								  LEFT JOIN sa_tipo_orden ON sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden
								  WHERE sa_orden.id_orden = %s LIMIT 1",
								  $_GET["id"]);
				$rs = mysql_query($query);
				if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				$row = mysql_fetch_assoc($rs);
				$poseeIva = $row['posee_iva'];//nuevo iva buscado
			}
		}
		
		if($poseeIva == 1) {//si posee que registre
			$iva_venta = 1;
		} else {
			$id_iva_venta = "NULL";
			$iva_venta = 0;
		}
	} else {
//		$id_iva_venta = $valFormTotalDcto['hddIdIvaVenta'];
//		$iva_venta = $valFormTotalDcto['txtIvaVenta'];
            if(isset($valFormTotalDcto["ivaActivo"])){
                $iva_venta = 1;
            }
	}
        
        //validando si el cliente es libre de impuesto
        if(clienteExento($valFormDcto['txtIdCliente'])){
            $id_iva_venta = 0;
            $iva_venta = 0;
        }
        
	//print_r("************************************".$totalArtTotal."**********************************************");
	
        //descuento total, ya estaba
	//$subTotalDescuento = round($totalArtTotal * ($valFormTotalDcto['txtDescuento']/100),2);//round por el decimal, trae 3
        //total descuento
        
        //$nuevoPorcentajeDescuento = round((($totalArtTotal * ($valFormTotalDcto['txtDescuento']/100)) * 100) / $subTotal,2);	        
	//$subTotalDescuento = round($subTotal * ($nuevoPorcentajeDescuento/100),2);
              
//	$subTotalDescuento = round($subTotal * ($valFormTotalDcto['txtDescuento']/100),2);
//        $nuevoPorcentaje = round(($subTotalDescuento *100) / $subTotal,2);
        $subTotalDescuento = ($subTotal * ($valFormTotalDcto['txtDescuento']/100));
        //$subTotalDescuento = ($subTotalDescuento*100/$subTotal);
        
        
        //contiene las bases imponibles de todos los items exceptuando repuestos que se calculan a parte
        $baseImponible -= $baseImponible*($valFormTotalDcto['txtDescuento']/100);//segun impresora
        
        if($iva_venta != 0 && $iva_venta != ""){//si tiene iva tomar repuestos con descuento
            $totalExento -= $totalExento*($valFormTotalDcto['txtDescuento']/100);//segun impresora            
        }else{//sino tiene iva todo seva a la base imponible
        //anterior
//            $baseImponible += $totalExento;
//            $totalExento = 0;
            //ahora
            $totalExento +=$baseImponible;
            $totalExento -= $totalExento*($valFormTotalDcto['txtDescuento']/100);//segun impresora 2
            $baseImponible = 0;
        }
               
        //resumando los items restantes, a los repuestos de las bases imponibles
        foreach($arrayIvasBaseImponible as $keyIdIva => $baseImponibleIvas){
            $arrayIvasBaseImponible[$keyIdIva] += $baseImponible;
            $arrayIvasSubTotal[$keyIdIva] += ($baseImponible*$valFormTotalDcto["txtIvaVenta".$keyIdIva])/100;            
        }
        
        $recalculoIvas = array();

	foreach($arrayIvasBaseImponible as $keyIdIva => $baseImponibleIvas){
            $recalculoIvas[$keyIdIva] += round(($baseImponibleIvas*$valFormTotalDcto["txtIvaVenta".$keyIdIva])/100,2);            
        }
        
        //$objResponse->script("console.log(".json_encode($arrayIvasSubTotal).")");
        
	//$totalIva = $baseImponible * ($iva_venta/100);
        $totalIva = array_sum($recalculoIvas);          //redondeo subtotal descuento sino da 1 decimal de mas
	$totalPresupuesto = doubleval($subTotal) - doubleval(round($subTotalDescuento,2)) - doubleval($totalDescuentoAdicional) + doubleval($gastosConIva) + doubleval($subTotalIva) +  doubleval($gastosSinIva) + doubleval($totalIva);
	
        
        //se le resta el % de descuento
//        $descuentoExento = round($totalExento*($nuevoPorcentajeDescuento/100),2);
//        $totalExento = round($totalExento-$descuentoExento,2);
	
        function totalesOrden($total){
            //return round($total,2);
            return number_format(round($total,2),2,".",",");
        }
	//$objResponse->assign("hddIdIvaVenta","value",$id_iva_venta);
	$objResponse->assign("txtSubTotal","value",totalesOrden($subTotal));
	$objResponse->assign("txtSubTotalDescuento","value",totalesOrden($subTotalDescuento));
	//$objResponse->assign("txtIvaVenta","value", $iva_venta);
	//$objResponse->assign("txtTotalIva","value",totalesOrden($totalIva));
	$objResponse->assign("txtTotalPresupuesto","value",totalesOrden($totalPresupuesto));	
	$objResponse->assign('txtGastosConIva',"value",totalesOrden($gastosConIva));
	$objResponse->assign('txtMontoExento',"value",totalesOrden($totalExento));	
	
        foreach($arrayIvasBaseImponible as $keyIdIva => $baseImponibleIvas){
            $objResponse->assign('txtBaseImponibleIva'.$keyIdIva,"value",totalesOrden($baseImponibleIvas));
            $objResponse->assign('txtTotalIva'.$keyIdIva,"value",totalesOrden($recalculoIvas[$keyIdIva]));            
			
			$objResponse->assign('txtBaseImponible',"value",totalesOrden($baseImponibleIvas));//solo rrellenar, usado en vzla simple iva
        }
        
	$cadena = "";
	foreach($arrayObj as $indice => $valor) {
		if (isset($valForm['hddIdArt'.$valor]))
			$cadena .= "|".$valor;
	}
	$objResponse->assign("hddObj","value",$cadena);

	$cadenaPaq = "";
	foreach($arrayObjPaq as $indicePaq => $valorPaq) {
		if (isset($valFormPaq['hddIdPaq'.$valorPaq]))
			$cadenaPaq .= "|".$valorPaq;
	}
	$objResponse->assign("hddObjPaquete","value",$cadenaPaq);
	
	$cadenaTemp = "";
	foreach($arrayObjTemp as $indiceTemp => $valorTemp) {
		if (isset($valFormTemp['hddIdTemp'.$valorTemp]))
			$cadenaTemp .= "|".$valorTemp;
	}
	$objResponse->assign("hddObjTempario","value",$cadenaTemp);
	
	$cadenaNota = "";
	foreach($arrayObjNota as $indiceNota => $valorNota) {
		if (isset($valFormNotas['hddIdNota'.$valorNota]))
			$cadenaNota .= "|".$valorNota;
	}
	$objResponse->assign("hddObjNota","value",$cadenaNota);
	
	$cadenaDcto = "";
	foreach($arrayObjDcto as $indiceDcto => $valorDcto) {
		if (isset($valFormTotalDcto['hddIdDcto'.$valorDcto]))
			$cadenaDcto .= "|".$valorDcto;
	}
	$objResponse->assign("hddObjDescuento","value", $cadenaDcto);
	
	$objResponse->script("xajax_contarItemsDcto(xajax.getFormValues('frmTotalPresupuesto'), xajax.getFormValues('frm_agregar_paq'));");
	
	return $objResponse;
}

//SE EJECUTA 2 VECES, NO CUANDO ES NUEVO, LLAMA CALCULARDCTO DE ARRIBA OTRA VEZ
function calcularTotalDcto() {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmPresupuesto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalPresupuesto'),xajax.getFormValues('frm_agregar_paq'),xajax.getFormValues('frmListaManoObra'),xajax.getFormValues('frmListaNota'), xajax.getFormValues('frmListaTot'));");
		
	return $objResponse;
}

//CALCULA EL TOTAL DEL PAQUETE AL ABRIR EL PAQUETE SELECCIONADO DEL LISTADO DE PAQUETES
function calcularTotalPaquete($valFormPaquete,$idCliente,$idTipoOrden) {
	
	$objResponse = new xajaxResponse();
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayVal = explode("|",$valFormPaquete['hddObjTemparioPaq']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObjTemp[] = $valor;
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayVal = explode("|",$valFormPaquete['hddObjRepuestoPaq']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObjRep[] = $valor;
	}
	
	$nroManoObraAprobPaq = 0;
	$totalManoObraAprob = 0;
	$cadenaMo = NULL;
	if (isset($arrayObjTemp)) {
		foreach($arrayObjTemp as $indiceTemp => $valorTemp) {
			if (isset($valFormPaquete['chk_tempPaq'])) {
				foreach($valFormPaquete['chk_tempPaq'] as $indiceAprob => $valorAprob) {
					if ($valorTemp == $valorAprob) {	
						$nroManoObraAprobPaq ++;
						
						$cadenaMo = $cadenaMo."|".$valFormPaquete['txtIdeTempPaq'.$valorTemp];
						$totalManoObraAprob += doubleval($valFormPaquete['txtPrecTempPaq'.$valorTemp]);
					}
				}
			}
		}
	}		
        $totalConIvaRepuestos = 0;
        $totalSinIvaRepuestos = 0;
	
	$nroRepAprobPaq = 0;
	$totalRepuestoAprob = 0;
	$cadenaRe = NULL;
        
        if(!clienteExento($idCliente) && tipoOrdenPoseeIva($idTipoOrden)){
            // para calcular montos paquetes
            $arrayIvas = ivaServicios(); //proviene de ac_iv_general devuelve array de arrays
            $ivasActivos = implode(",",array_keys($arrayIvas)); //devuelve id de ivas activos separados por coma
            $arrayMontoPorIvasRep = array();//key sera id impuesto y el valor el monto        
        }
        
	if (isset($arrayObjRep)) {
		foreach($arrayObjRep as $indiceRep => $valorRep) {
			if (isset($valFormPaquete['chk_repPaq'])) {
				foreach($valFormPaquete['chk_repPaq'] as $indiceApro => $valorApro) {
                                    //var_dump("indR: ".$indiceRep." indA: ".$indiceApro." valR: ".$valorRep." valA: ".$valorApro);
					if ($valorRep == $valorApro) {    
                                            //var_dump("SIIIII indR: ".$indiceRep." indA: ".$indiceApro." valR: ".$valorRep." valA: ".$valorApro);
						if ($j != $valFormPaquete['txtIdeRepPaq'.$valorRep]) { 						
							$nroRepAprobPaq ++;
							
							$cadenaRe = $cadenaRe."|".$valFormPaquete['txtIdeRepPaq'.$valorRep];	
							$totalRepuestoAprob += doubleval($valFormPaquete['txtPrecIndvRepPaq'.$valorRep] * $valFormPaquete['txtRepCantTotal'.$valorRep]);
							$j = $valFormPaquete['txtIdeRepPaq'.$valorRep];//auxiliar para indice tamb
                                                        
                                                        //si el articulo posee iva: YA NOSE VA A USAR
//                                                        $QueryPoseeIva = sprintf("SELECT id_impuesto FROM iv_articulos_impuesto WHERE id_impuesto = 1 AND id_articulo = %s LIMIT 1",
//                                                                                valTpDato($valorRep, "int"));
//                                                        
//                                                        $rsPoseeIva = mysql_query($QueryPoseeIva);
//                                                        if(!$rsPoseeIva) { return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__."\n\nQuery:".$QueryPoseeIva);}
//                                                        $poseeIva = mysql_num_rows($rsPoseeIva);
                                                        
                                                        if($ivasActivos){
                                                            //$totalConIvaRepuestos += $totalRepuestoAprob;                                                            
                                                            $totalConIvaRepuestos += doubleval($valFormPaquete['txtPrecIndvRepPaq'.$valorRep] * $valFormPaquete['txtRepCantTotal'.$valorRep]);
                                                            
                                                            $queryIvasArticulo = sprintf("SELECT id_impuesto 
                                                                                            FROM iv_articulos_impuesto 
                                                                                            WHERE id_articulo = %s AND id_impuesto IN (%s)",
                                                                                      valTpDato($valorRep, "int"),
                                                                                      $ivasActivos);
                                                              $rsIvasArticulo = mysql_query($queryIvasArticulo);
                                                              if (!$rsIvasArticulo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nQuery: ".$queryIvasArticulo); }

                                                              while($rowIvasArticulo = mysql_fetch_assoc($rsIvasArticulo)){

                                                                  $arrayMontoPorIvasRep[$rowIvasArticulo["id_impuesto"]] += doubleval($valFormPaquete['txtPrecIndvRepPaq'.$valorRep] * $valFormPaquete['txtRepCantTotal'.$valorRep]);

                                                              }
                                                        }
                                                        if($ivasActivos == "" || empty($arrayMontoPorIvasRep)){
                                                            //$totalSinIvaRepuestos += $totalRepuestoAprob;
                                                            $totalSinIvaRepuestos += doubleval($valFormPaquete['txtPrecIndvRepPaq'.$valorRep] * $valFormPaquete['txtRepCantTotal'.$valorRep]);
                                                        }
						}	
					}
				}
			}
		}
	}
        
        foreach($arrayMontoPorIvasRep as $key => $montoPorIvas){
            $idIvasActivosRepuestosPaq[] = $key;
            $porcentajesActivosRepuestosPaq[] = $arrayIvas[$key]["iva"];
        }
                
        $objResponse->assign("idIvasRepuestosPaquete","value", implode(",",$idIvasActivosRepuestosPaq));
        $objResponse->assign("montoIvasRepuestosPaquete","value", implode(",",$arrayMontoPorIvasRep));
        $objResponse->assign("porcentajesIvasRepuestosPaquete","value", implode(",",$porcentajesActivosRepuestosPaq));
	
	$objResponse->assign("hddRepAproXpaq","value", $cadenaRe);
	
	$objResponse->assign("txtNroManoObraAprobPaq","value",$nroManoObraAprobPaq);
	$objResponse->assign("txtNroRepuestoAprobPaq","value",$nroRepAprobPaq);
	$objResponse->assign("txtTotalItemAprobPaq","value", $nroRepAprobPaq + $nroManoObraAprobPaq);
	
	$objResponse->assign("txtTotalManoObraPaq","value",number_format($totalManoObraAprob,2,".",","));
	$objResponse->assign("hddManObraAproXpaq","value", $cadenaMo);
	
	$objResponse->assign("txtTotalRepPaq","value",number_format($totalRepuestoAprob,2,".",","));
	$objResponse->assign("hddTotalArtExento","value",number_format($totalSinIvaRepuestos,2,".",","));//."AQUI EXCENTO"
	$objResponse->assign("hddTotalArtConIva","value",number_format($totalConIvaRepuestos,2,".",","));
	
	$objResponse->assign("txtPrecioPaquete","value",number_format(($totalManoObraAprob + $totalRepuestoAprob),2,".",","));
	
	return $objResponse;
}


//CARGA UN LIST CON LOS TIPOS DE DESCUENTOS A APLICAR, TABLA SIEMPRE ESTA VACIA
function cargaLstDescuentos() {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM sa_porcentaje_descuento
	WHERE sa_porcentaje_descuento.activo = 1
		AND sa_porcentaje_descuento.idModulo = 1
		AND sa_porcentaje_descuento.id_empresa = %s
	ORDER BY sa_porcentaje_descuento.descripcion",
		valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$html = "<select id=\"lstTipoDescuentos\" name=\"lstTipoDescuentos\" onchange=\"xajax_cargarPorcAdicional(this.value);\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<option value=\"".$row['id_porcentaje_descuento']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdLstTipoDescuentos","innerHTML",$html); 
	
	return $objResponse;
}


//LISTADO SECCION CUANDO ABRES EL LISTADO DE TEMPARIOS DESDE ORDEN
function cargaLstSeccionTemp($selId = "") {
	$objResponse = new xajaxResponse();

	$query = "SELECT * FROM sa_seccion ORDER BY sa_seccion.descripcion_seccion";
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstSeccionTemp\" name=\"lstSeccionTemp\" onchange=\"xajax_cargaLstSubseccionTemp(this.value); $('btnBuscarTempario').click();\">";
		$html .= "<option value=\"\">[ Todos ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<option value=\"".$row['id_seccion']."\">".utf8_encode($row['descripcion_seccion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdListSeccionTemp","innerHTML",$html);
	
	return $objResponse;
}

//LISTADO SUB-SECCION CUANDO ABRES EL LISTADO DE TEMPARIOS DESDE ORDEN
function cargaLstSubseccionTemp($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM sa_subseccion
	WHERE sa_subseccion.id_seccion = %s
	ORDER BY sa_subseccion.descripcion_subseccion",
		valTpDato($selId,"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstSubseccionTemp\" name=\"lstSubseccionTemp\" onchange=\"$('btnBuscarTempario').click();\">";
		$html .= "<option value=\"\">[ Todos ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<option value=\"".$row['id_subseccion']."\">".utf8_encode($row['descripcion_subseccion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdListSubseccionTemp","innerHTML",$html);
	
	return $objResponse;
}

//ES EL LISTADO DE TIPOS DE ORDEN, ES EL UNICO QUE HAY
function cargaLstTipoOrden($selId = "", $idTipoValeRecepcion = NULL) {
	
	$objResponse = new xajaxResponse();
	
	if ($selId != "") {
		$query = sprintf("SELECT 
			sa_tipo_orden.orden_retrabajo,
 			sa_tipo_orden.nombre_tipo_orden
		FROM sa_tipo_orden
		WHERE sa_tipo_orden.id_tipo_orden = %s",
			$selId);
		$rs = mysql_query($query);
		$row = mysql_fetch_assoc($rs);	
				
		if ($row['orden_retrabajo'] == 1) {//NINGUNA ORDEN LO POSEE
			if ($_GET['doc_type'] == 1) {
				$cond = sprintf(" INNER JOIN sa_presupuesto ON (sa_orden.id_orden = sa_presupuesto.id_orden)
				WHERE sa_presupuesto.id_presupuesto = %s",
					$_GET['id']);
			} else {
				$cond = sprintf(" WHERE sa_retrabajo_orden.id_orden = %s",
					$_GET['id']);
			}
			
			$query_ret = sprintf("SELECT sa_tipo_orden.id_tipo_orden
			FROM sa_orden
				INNER JOIN sa_retrabajo_orden ON (sa_orden.id_orden = sa_retrabajo_orden.id_orden)
				INNER JOIN sa_orden sa_orden_ref ON (sa_orden_ref.id_orden = sa_retrabajo_orden.id_orden_retrabajo)
				INNER JOIN sa_tipo_orden ON (sa_orden_ref.id_tipo_orden = sa_tipo_orden.id_tipo_orden) %s", $cond);
			$rs_ret = mysql_query($query_ret);
			$row_ret = mysql_fetch_assoc($rs_ret);	
			
			//$objResponse->alert($selId_w);//alerta
			
			$selId_w = $row_ret['id_tipo_orden'];
			
			//$objResponse->alert($selId_w);//alerta
		} else {
			$selId_w = 	$selId;
		}
	} else {
		$selId_w = 	$selId;
	}
        
        
        if($idTipoValeRecepcion == NULL){//si es null, no eligio vale, sino que carga orden
            if($_GET["doc_type"] ==2 && $_GET["id"] !=''){//Orden de servicio y no es nueva
                $queryTipoVale = sprintf("SELECT sa_recepcion.id_tipo_vale, sa_tipo_vale.filtros_orden
                                   FROM sa_orden
                                   INNER JOIN sa_recepcion ON sa_orden.id_recepcion = sa_recepcion.id_recepcion
                                   INNER JOIN sa_tipo_vale ON sa_recepcion.id_tipo_vale = sa_tipo_vale.id_tipo_vale
                                   WHERE id_orden = %s LIMIT 1",
                        valTpDato($_GET["id"],"int"));
                $rsTipoVale = mysql_query($queryTipoVale);
                if(!$rsTipoVale) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

                $rowTipoVale = mysql_fetch_assoc($rsTipoVale);
                if($rowTipoVale["id_tipo_vale"] != NULL){
                    $idTipoValeRecepcion = $rowTipoVale["id_tipo_vale"];
                    $filtrosOrden = $rowTipoVale["filtros_orden"];
                }
            }
        }else{//seleccion de vale de recepcion
            $queryTipoVale = sprintf("SELECT filtros_orden
                                   FROM sa_tipo_vale
                                   WHERE id_tipo_vale = %s LIMIT 1",
                        valTpDato($idTipoValeRecepcion,"int"));
            $rsTipoVale = mysql_query($queryTipoVale);
            if(!$rsTipoVale) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
            
            $rowTipoVale = mysql_fetch_assoc($rsTipoVale);
            $filtrosOrden = $rowTipoVale["filtros_orden"];
        }
        
        //filtro tipo de vale:
        if($idTipoValeRecepcion != NULL && $filtrosOrden != NULL){
            $filtroTipoVale = sprintf(" AND sa_tipo_orden.id_filtro_orden IN(%s) ", $filtrosOrden);
        }
			
	$query = sprintf("SELECT 
		sa_tipo_orden.id_tipo_orden,
		sa_tipo_orden.nombre_tipo_orden,
		sa_tipo_orden.tipo_movimiento,
		sa_tipo_orden.id_clave_movimiento,
		sa_tipo_orden.id_precio_tempario_porcentaje,
		sa_tipo_orden.id_precio_repuesto,
		sa_tipo_orden.id_precio_tot_porcentaje,
		sa_tipo_orden.modo_factura,
		sa_tipo_orden.modo_cliente_factura,
		sa_tipo_orden.precio_tempario,
		sa_tipo_orden.orden_generica,
		sa_tipo_orden.orden_retrabajo,
		sa_tipo_orden.porcentaje_tot,
		sa_tipo_orden.tipo_garantia,
		sa_tipo_orden.posee_iva,
		sa_tipo_orden.pago_comision,
		sa_tipo_orden.id_empresa
	FROM pg_usuario
		INNER JOIN sa_permiso_tipo_orden_usuario ON (pg_usuario.id_usuario = sa_permiso_tipo_orden_usuario.id_usuario)
		INNER JOIN sa_tipo_orden ON (sa_permiso_tipo_orden_usuario.id_tipo_orden = sa_tipo_orden.id_tipo_orden)
	WHERE pg_usuario.id_usuario = %s
		AND sa_permiso_tipo_orden_usuario.id_empresa = ".$_SESSION['idEmpresaUsuarioSysGts']."
		AND sa_permiso_tipo_orden_usuario.permiso = 1
		AND sa_tipo_orden.nombre_tipo_orden <> 'SIN ASIGNAR'
		AND sa_tipo_orden.orden_retrabajo <> 1 %s
	ORDER BY sa_tipo_orden.nombre_tipo_orden",
		$_SESSION['idUsuarioSysGts'],
                $filtroTipoVale);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery:".$query);
	//$soloTipoOrden = mysql_fetch_array($rs);
	//$selId_w = $soloTipoOrden['id_tipo_orden'];
	//mysql_data_seek($rs, 0);
	
	$objResponse->script("$('hddTipoOrdenAnt').value= ".$selId_w);
	//$objResponse->alert($selId_w);//alerta //SIN ASIGNAR AQUI contado 1, 9 ,24 gregor importante
	$html = "<select id=\"lstTipoOrden\" name=\"lstTipoOrden\" onchange=\"
		xajax_verificarTipoOrdenGarantia(xajax.getFormValues('frmPresupuesto'),this.value);		
	
		if ($('hddItemsCargados').value > 0) {
			if ($('hddOrdenEscogida').value != this.value) {
				var exists = false; 
				var anterior = $('hddTipoOrdenAnt').value;
					for (i = 0; i < this.length; ++i){
						if (this.options[i].value == anterior){
						  exists = true;
						}
					}
				if(exists){
					selectedOption('lstTipoOrden', $('hddTipoOrdenAnt').value);
				}else{
					selectedOption('lstTipoOrden', '-1');
				}
				
				alert('El Documento tiene items cargados con un tipo de orden. Si desea cambiarla, elimine los items cargados');
				
			}
		} else {
			//antes solo $('lstTipoOrden').value != 1
			var filtroOrdenSelect = xajax.call('buscarFiltroOrden', {mode:'synchronous', parameters:[$('lstTipoOrden').value]});
			if (isNaN(parseInt(filtroOrdenSelect))){//valido que no hubo error
				return false;
			}
			if ($('hddTipoOrdenAnt').value > 0 || ((filtroOrdenSelect != 1 /*&& $('lstTipoOrden').value != 9 && $('lstTipoOrden').value != 24*/))) {
				abrir2();
				//alert($('lstTipoOrden').value);
				//alert($('hddTipoOrdenAnt').value);				
			}
		}
		
		if ($('txtTotalPresupuesto').value == 0) {
			xajax_calcularDcto(xajax.getFormValues('frmPresupuesto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalPresupuesto'),xajax.getFormValues('frm_agregar_paq'),xajax.getFormValues('frmListaManoObra'),xajax.getFormValues('frmListaNota'), xajax.getFormValues('frmListaTot'));
		}\">"; //onchange=\"
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($rowTipoOrden = mysql_fetch_assoc($rs)) {
		$seleccion = "";
		if ($selId_w == $rowTipoOrden['id_tipo_orden']) {
			$seleccion = "selected='selected'";
			$tipo_orden = $rowTipoOrden['nombre_tipo_orden'];
		}
		
		$html .= "<option value=\"".$rowTipoOrden['id_tipo_orden']."\" ".$seleccion.">".utf8_encode($rowTipoOrden['nombre_tipo_orden'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstTipoOrden","innerHTML",$html);
	
	
	if ($selId_w != "") {
		if($_GET['doc_type'] == 1) {
			$cond = sprintf(" INNER JOIN sa_orden ON (sa_tipo_orden.id_tipo_orden = sa_orden.id_tipo_orden)
				INNER JOIN sa_presupuesto ON (sa_orden.id_orden = sa_presupuesto.id_orden)
			WHERE sa_presupuesto.id_presupuesto = %s",
					$_GET['id']);
		} else {
			$cond = sprintf(" INNER JOIN sa_orden ON (sa_tipo_orden.id_tipo_orden = sa_orden.id_tipo_orden)
			WHERE sa_orden.id_orden = %s",
				$_GET['id']);
		}
				
		$query = sprintf("SELECT 
			sa_tipo_orden.orden_retrabajo,
			sa_tipo_orden.nombre_tipo_orden,
			sa_orden.id_orden
		FROM sa_tipo_orden %s", $cond);
				
		$rs = mysql_query($query);
		$row = mysql_fetch_assoc($rs);	
					
		if ($row['orden_retrabajo'] == 1) {
			if ($_GET['doc_type'] == 1) {
				$cond = sprintf(" INNER JOIN sa_presupuesto ON (sa_orden.id_orden = sa_presupuesto.id_orden)
				WHERE sa_presupuesto.id_presupuesto = %s", $_GET['id']);
			} else {
				$cond = sprintf(" WHERE sa_retrabajo_orden.id_orden = %s", $_GET['id']);
			}
			
			$query_ret = sprintf("SELECT 
				sa_retrabajo_orden.id_orden_retrabajo,
				sa_tipo_orden.id_tipo_orden,
				sa_tipo_orden.nombre_tipo_orden
			FROM sa_orden
				INNER JOIN sa_retrabajo_orden ON (sa_orden.id_orden = sa_retrabajo_orden.id_orden)
				INNER JOIN sa_orden sa_orden_ref ON (sa_orden_ref.id_orden = sa_retrabajo_orden.id_orden_retrabajo)
				INNER JOIN sa_tipo_orden ON (sa_orden_ref.id_tipo_orden = sa_tipo_orden.id_tipo_orden) %s", $cond);
			$rs_ret = mysql_query($query_ret);
			$row_ret = mysql_fetch_assoc($rs_ret);
							
			$objResponse->script(sprintf("
			$('tdTxtNroOrdenRetrabajo').style.display = '';
			$('tdLabelNroOrdenRetrabajo').style.display = '';
			$('txtNumeroOrdenRetrabajo').value = '%s';
			$('txtTipoOrdenRetrabajo').value = '%s';",
				$row_ret['id_orden_retrabajo'],
				utf8_encode($row_ret['nombre_tipo_orden'])));//+'  TIPO: %s' , $row_ret['nombre_tipo_orden']
		}
		
		//////OJO//////
		/*$objResponse->assign("txtDescripcionTipoOrden","value", utf8_encode($row['nombre_tipo_orden']));
		
		$objResponse->script("
		if($('hddAccionTipoDocumento').value != 1) {
			$('tdlstTipoOrden').style.display='none';
			$('tdDescripcionTipoOrden').style.display='';
		}");*/
	} else {
		$objResponse->script("
		$('tdlstTipoOrden').style.display = '';
		$('tdDescripcionTipoOrden').style.display = 'none';");
	}
	return $objResponse;
}

//CARGA TODA LA INFORMACION DEL DOCUMENTO, SI ES NUEVO DOCUMENTO NO SE USA
function cargarDcto($idDocumento, $valFormTotalDcto) {
	$objResponse = new xajaxResponse();

	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	for ($contRep = 0; $contRep <= strlen($valFormTotalDcto['hddObj']); $contRep++) {
		$caracterRep = substr($valFormTotalDcto['hddObj'], $contRep, 1);
		
		if ($caracterRep != "|" && $caracterRep != "")
			$cadenaRep .= $caracterRep;
		else {
			$arrayObj[] = $cadenaRep;
			$cadenaRep = "";
		}	
	}
	
	for ($contPaq = 0; $contPaq <= strlen($valFormTotalDcto['hddObjPaquete']); $contPaq++) {
		$caracterPaq = substr($valFormTotalDcto['hddObjPaquete'], $contPaq, 1);
		
		if ($caracterPaq != "|" && $caracterPaq != "")
			$cadenaPaq .= $caracterPaq;
		else {
			$arrayObjPaq[] = $cadenaPaq;
			$cadenaPaq = "";
		}	
	}
	
	for ($contTot = 0; $contTot <= strlen($valFormTotalDcto['hddObjTot']); $contTot++) {
		$caracterTot = substr($valFormTotalDcto['hddObjTot'], $contTot, 1);
		
		if ($caracterTot != "|" && $caracterTot != "")
			$cadenaTot .= $caracterTot;
		else {
			$arrayObjTot[] = $cadenaTot;
			$cadenaTot = "";
		}	
	}
	
	for ($contNota = 0; $contNota <= strlen($valFormTotalDcto['hddObjNota']); $contNota++) {
		$caracterNota = substr($valFormTotalDcto['hddObjNota'], $contNota, 1);
		
		if ($caracterNota != "|" && $caracterNota != "")
			$cadenaNota .= $caracterNota;
		else {
			$arrayObjNota[] = $cadenaNota;
			$cadenaNota = "";
		}
	}

	for ($contTemp = 0; $contTemp <= strlen($valFormTotalDcto['hddObjTempario']); $contTemp++) {
		$caracterTemp = substr($valFormTotalDcto['hddObjTempario'], $contTemp, 1);
		
		if ($caracterTemp != "|" && $caracterTemp != "")
			$cadenaTemp .= $caracterTemp;//caracterTempario
		else {
			$arrayObjTemp[] = $cadenaTemp;
			$cadenaTemp = "";
		}
	}
	
	for ($contDcto = 0; $contDcto <= strlen($valFormTotalDcto['hddObjDescuento']); $contDcto++) {
		$caracterDcto = substr($valFormTotalDcto['hddObjDescuento'], $contDcto, 1);
		
		if ($caracterDcto != "|" && $caracterDcto != "")
			$cadenaDcto .= $caracterDcto;
		else {
			$arrayObjDcto[] = $cadenaDcto;
			$cadenaDcto = "";
		}
	}
		
	foreach($arrayObj as $indiceItmRep=>$valorItmRep) {
		$objResponse->script(sprintf("
			fila = document.getElementById('tdItmRep:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItmRep));
	}
	
	foreach($arrayObjTot as $indiceItmTot=>$valorItmTot) {
		$objResponse->script(sprintf("
			fila = document.getElementById('tdItmTot:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItmTot));
	}
	
	foreach($arrayObjPaq as $indiceItmPaq=>$valorItmPaq) {
		$objResponse->script(sprintf("
			fila = document.getElementById('tdItmPaq:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItmPaq));
	}
	
	foreach($arrayObjNota as $indiceItmNota=>$valorItmNota) {
		$objResponse->script(sprintf("
			fila = document.getElementById('trItmNota:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItmNota));
	}
	
	foreach($arrayObjTemp as $indiceItmTemp=>$valorItmTemp) {
		$objResponse->script(sprintf("
			fila = document.getElementById('trItmTemp:%s');	
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItmNota));
	}
	
	foreach($arrayObjDcto as $indiceItmDcto=>$valorItmDcto) {
		$objResponse->script(sprintf("
			fila = document.getElementById('trItmDcto:%s');	
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItmDcto));
	}

	$queryUsuario = sprintf("SELECT * FROM vw_iv_usuarios WHERE id_usuario = ".$_SESSION['idUsuarioSysGts']);
	$rsUsuario = mysql_query($queryUsuario);
	if (!$rsUsuario) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowUsuario = mysql_fetch_assoc($rsUsuario);
	
	if ($valFormTotalDcto['hddTipoDocumento'] == 1) { //PRESUPUESTO
		$query = sprintf("SELECT * FROM vw_sa_presupuesto
		WHERE vw_sa_presupuesto.id_presupuesto = %s",
			valTpDato($idDocumento,"int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
			
		$numeroPresupuestoMostrar = $row["numero_presupuesto"];
		$idEmpresa = $row["id_empresa"];
				
		$tablaEnc = "sa_presupuesto";
		$campoIdEnc = "id_presupuesto";
		$tablaDocDetArt = "sa_det_presup_articulo";
		$campoTablaIdDetArt = "id_det_presup_articulo";
		$tablaDocDetTemp = "sa_det_presup_tempario";
		$campoTablaIdDetTemp = "id_det_presup_tempario";
		
		$tablaDocDetTot = "sa_det_presup_tot";
		$campoTablaIdDetTot = "id_det_presup_tot";
		
		$tablaDocDetNota = "sa_det_presup_notas";
		$campoTablaIdDetNota = "id_det_presup_nota";
		
		$tablaDocDetDescuento = "sa_det_presup_descuento";
		$campoTablaIdDetDescuento = "id_det_presup_descuento";
		
		$campoTablaIdDetNotaRelacOrden = "id_det_orden_nota AS id_det_orden_nota_ref";
		$campoTablaIdDetTotRelacOrden = "id_det_orden_tot AS id_det_orden_tot_ref";
		$campoTablaIdDetTempRelacOrden = "id_det_orden_tempario AS id_det_orden_tempario_ref";
		$campoTablaIdDetArtRelacOrden = "id_det_orden_articulo AS id_det_orden_articulo_ref";
		
		$fechaDocumento = $row['fecha_presup'];
		if ($row['porcentaje_descuento'] != NULL || $row['porcentaje_descuento'] != "")
			$descuento = $row['porcentaje_descuento'];
		else
			$descuento = 0;
		
		$idTipoOrden = $row['id_tipo_orden'];
		$estado_orden = $row['nombre_estado'];
		$id_iva = $row['idIva'];
		$iva = $row['iva'];
	} else {
		$query = sprintf("SELECT * FROM vw_sa_orden WHERE id_orden = %s", valTpDato($idDocumento,"int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$numeroOrdenMostrar = $row["numero_orden"];
                $idEmpresa = $row["id_empresa"];
		
		$tablaEnc = "sa_orden";
		$campoIdEnc = "id_orden";
		$tablaDocDetArt = "sa_det_orden_articulo";
		$campoTablaIdDetArt = "id_det_orden_articulo";
		$tablaDocDetTemp = "sa_det_orden_tempario";
		$campoTablaIdDetTemp = "id_det_orden_tempario";

		$tablaDocDetTot = "sa_det_orden_tot";
		$campoTablaIdDetTot = "id_det_orden_tot";
		
		$tablaDocDetNota = "sa_det_orden_notas";
		$campoTablaIdDetNota = "id_det_orden_nota";
		
		$tablaDocDetDescuento = "sa_det_orden_descuento";
		$campoTablaIdDetDescuento = "id_det_orden_descuento";
		
		$campoTablaIdDetNotaRelacOrden = "id_det_orden_nota AS id_det_orden_nota_ref";
		$campoTablaIdDetTotRelacOrden = "id_det_orden_tot AS id_det_orden_tot_ref";
		$campoTablaIdDetTempRelacOrden = "id_det_orden_tempario AS id_det_orden_tempario_ref";
		$campoTablaIdDetArtRelacOrden = "id_det_orden_articulo AS id_det_orden_articulo_ref";

		$fechaDocumento = $row['fecha_orden'];
		
		if ($row['porcentaje_descuento'] != NULL || $row['porcentaje_descuento'] != "")
			$descuento = $row['porcentaje_descuento'];
		else
			$descuento = 0;
			
		$idTipoOrden = $row['id_tipo_orden'];
		$estado_orden = $row['nombre_estado'];
		$id_iva = $row['idIva'];
		$iva = $row['iva'];
	}
		
	$itemsNoChecados = 0;
	
	if ($_GET['acc_ret'] == 2 || $_GET['ret'] != 5) { //RETRABAJO
		$objResponse->script(sprintf("xajax_asignarValeRecepcion(%s, %s, xajax.getFormValues('frmTotalPresupuesto'))", $row['id_recepcion'], $_GET['acc']));
	} else {
		$objResponse->script("xajax_calcularTotalDcto();");
	}
	
	if($valFormTotalDcto['hddAccionTipoDocumento'] == 2)
		$check_disabled = "disabled='disabled'";
	else
		$check_disabled = "";
		
	if($_GET['dev'] == 1)
		$condicionMostrarArticulosFacturados = sprintf(" AND %s.aprobado = 1", $tablaDocDetArt);
	else
		$condicionMostrarArticulosFacturados = "";
		
	if($_GET['acc_ret'] != 1) { //CUANDO SE GENERA LA ORDEN RETRABAJO
		$queryRepuestosGenerales = sprintf("SELECT
			%s.%s,                        
			%s.cantidad,
			%s.id_articulo_costo,
			%s.id_articulo_almacen_costo,
			%s.id_precio,
			%s.precio_unitario,
			(SELECT GROUP_CONCAT(id_iva) FROM sa_det_orden_articulo_iva WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = %s.%s) as id_iva,
			(SELECT GROUP_CONCAT(iva) FROM sa_det_orden_articulo_iva WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = %s.%s) as iva,
			%s.id_articulo,
			%s.%s,
			%s.aprobado,
			%s.costo,			
			iv_tipos_articulos.descripcion AS descripcion_tipo,
			iv_articulos.descripcion AS descripcion_articulo,
			iv_secciones.descripcion AS descripcion_seccion,
			iv_subsecciones.id_subseccion,
			iv_articulos.codigo_articulo
		FROM iv_articulos
			INNER JOIN %s ON (iv_articulos.id_articulo = %s.id_articulo)
			INNER JOIN iv_subsecciones ON (iv_articulos.id_subseccion = iv_subsecciones.id_subseccion)
			INNER JOIN iv_tipos_articulos ON (iv_articulos.id_tipo_articulo = iv_tipos_articulos.id_tipo_articulo)
			INNER JOIN iv_secciones ON (iv_subsecciones.id_seccion = iv_secciones.id_seccion)
		WHERE %s.%s = %s
			AND %s.id_paquete IS NULL %s
			AND %s.estado_articulo <> 'DEVUELTO'",
			$tablaDocDetArt, $campoTablaIdDetArtRelacOrden,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt, $campoTablaIdDetArt,//id ivas
			$tablaDocDetArt, $campoTablaIdDetArt,//porc ivas
			$tablaDocDetArt,
			$tablaDocDetArt, $campoTablaIdDetArt,
			$tablaDocDetArt, //costo no estaba y se lo agregue
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,
			
			$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento,"int"),
			$tablaDocDetArt, $condicionMostrarArticulosFacturados,
			$tablaDocDetArt);	
		$rsDetRep = mysql_query($queryRepuestosGenerales);
		
		if (!$rsDetRep) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$sigValor = 1;
		$arrayObj = NULL;
		
		$readonly_check_ppal_list_repuesto = 0;
		$tieneRptoSinSolicitud = 0;
		while ($rowDetRep = mysql_fetch_assoc($rsDetRep)) {
			$repuestosTomadosEnSolicitud2 = 0;
			
			$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
			$caracterIva = ($rowDetRep['id_iva'] != "") ? str_replace(",","% - ",$rowDetRep['iva'])."%" : "NA";
			
                        
			if ($_GET['cons'] == 1) { // FACTURACION
				// SI ESTAN EN SOLICITUD Y APROBADO
				$queryCont = sprintf("SELECT COUNT(*) AS nro_rpto_desp FROM sa_det_solicitud_repuestos
				WHERE sa_det_solicitud_repuestos.id_det_orden_articulo = %s
					AND (sa_det_solicitud_repuestos.id_estado_solicitud = 3 OR sa_det_solicitud_repuestos.id_estado_solicitud = 5) ", 
				$rowDetRep['id_det_orden_articulo_ref']);
				$rsCont = mysql_query($queryCont);
				$rowCont = mysql_fetch_assoc($rsCont);
                                
                                //OJO SE USA TAMBIEN PARA CALCULAR TOTALES
				if (!$rsCont)
					return $objResponse->alert("Error cargarDcto \n".mysql_error().$rsCont."\n Linea: ".__LINE__);
				else {
					if ($rowCont['nro_rpto_desp'] != NULL || $rowCont['nro_rpto_desp'] != "")
						$cantidad_art = $rowCont['nro_rpto_desp'];
					else
						$cantidad_art = 0;
				}
				
				$queryContTotal = sprintf("SELECT COUNT(*) AS nro_rpto_desp FROM sa_det_solicitud_repuestos
				WHERE sa_det_solicitud_repuestos.id_det_orden_articulo = %s", 
					$rowDetRep['id_det_orden_articulo_ref']);
				$rsContTotal = mysql_query($queryContTotal);
				$rowContTotal = mysql_fetch_assoc($rsContTotal);
				
				$cantidad_art_total = $rowContTotal['nro_rpto_desp'];
			} else {
				$cantidad_art_total = $rowDetRep['cantidad'];
				$cantidad_art = $rowDetRep['cantidad'];
			}	
			
			$query = sprintf("SELECT *
			FROM sa_det_orden_articulo
				INNER JOIN sa_det_solicitud_repuestos ON (sa_det_orden_articulo.id_det_orden_articulo = sa_det_solicitud_repuestos.id_det_orden_articulo)
				INNER JOIN sa_solicitud_repuestos ON (sa_det_solicitud_repuestos.id_solicitud = sa_solicitud_repuestos.id_solicitud)
			WHERE sa_det_orden_articulo.id_det_orden_articulo = %s
				AND sa_det_solicitud_repuestos.id_estado_solicitud IS NOT NULL
				AND sa_det_orden_articulo.estado_articulo <> 'DEVUELTO'",
				valTpDato($rowDetRep['id_det_orden_articulo_ref'],"int"));//AND sa_solicitud_repuestos.estado_solicitud != 0
			$rs = mysql_query($query);
                        
			if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$row = mysql_fetch_assoc($rs);
					
			if ($row['id_det_solicitud_repuesto'] != '')
				$repuestosTomadosEnSolicitud2 = 1;
				
			if ($rowDetRep['aprobado'] == 1) {
				//si hay por lo menos uno aprobado que desabilite directamente el check principal, ya q los valores tienden a reemplazarlos
				$checkedArt = "checked='checked'";
				$value_checkedArt = 1;

				if($repuestosTomadosEnSolicitud2 == 1)
					$disabledArt = "disabled='disabled'";
					
					//gregor valido si solo el repuesto esta en solicitud sino esta, se puede eliminar simplemente.
				if($row['id_det_solicitud_repuesto'] == NULL || $row['id_det_solicitud_repuesto'] == ""){
					$disabledArt = "";
				}else{
					$disabledArt = "disabled='disabled'";	
				}
				
				if ($valFormTotalDcto['hddAccionTipoDocumento']!=4) {
					$readonly_check_ppal_list_repuesto = 1;
					$displayArt = "style='display:none;'";
					$imgCheckDisabledArt = sprintf("<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' value='%s' checked='checked' />", $sigValor);
				} else {
					if($repuestosTomadosEnSolicitud2 == 1) {
						$readonly_check_ppal_list_repuesto = 1;
						$displayArt = "style='display:none;'";
						$imgCheckDisabledArt = sprintf("<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' value='%s' checked='checked' />", $sigValor);
					} else {
						$displayArt = "";
						$imgCheckDisabledArt = "";
						$tieneRptoSinSolicitud = 1;
					}
				}
			} else {
				$itemsNoChecados = 1;
				$checkedArt = " ";
				$value_checkedArt = 0;
				$disabledArt = "";
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$displayArt = "style='display:none;'";
					$imgCheckDisabledArt = "<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>";
					$readonly_check_ppal_list_repuesto = 1;
				} else {
					$displayArt = "";
					$imgCheckDisabledArt = "";
					$tieneRptoSinSolicitud = 1;
				}
			}

			$solicitudRep = "";
                        
                        if ($rowDetRep['id_det_orden_articulo_ref']){//presupuesto puede que no tenga
                            $sqlSolicitudRep= "SELECT * FROM sa_det_solicitud_repuestos
                            WHERE id_det_orden_articulo = ".$rowDetRep['id_det_orden_articulo_ref']."
                                    AND id_estado_solicitud IN (1,2)";
                            $rsSolicitudRep= mysql_query($sqlSolicitudRep);
                            if (!$rsSolicitudRep) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
                            $rowSolicitudRep= mysql_fetch_assoc($rsSolicitudRep);

                            if ($rowSolicitudRep) {
                                    $solicitudRep= "<span style='color:red'> (S) </span>";
                            }
                        }
                        
                        
                        //CALCULOS MULTIPLES IVAS
                        $arrayIdIvasRep = explode(",",$rowDetRep['id_iva']);
                        $arrayPorcIvasRep = explode(",",$rowDetRep['iva']);
                        $montoMultipleIva = 0;
                        foreach($arrayIdIvasRep as $key => $idIvasRep){
                            $montoMultipleIva += ($cantidad_art * $rowDetRep['precio_unitario'] * $arrayPorcIvasRep[$key] / 100);
                        }
                        $montoMultipleIva = $montoMultipleIva + $cantidad_art * $rowDetRep['precio_unitario'];

					//imprime lo de solicitud, costo promedio tambien gregor
			$objResponse->script(sprintf("			
			var elemento = new Element('tr', {'id':'trItm:%s', 'class':'textoGris_11px %s', 'title':'trItm:%s'}).adopt([
				new Element('td', {'align':'right', 'id':'tdItmRep:%s', 'class':'color_column_insertar_eliminar_item'
}).setHTML(\"<input id='cbxItm' name='cbxItm[]' type='checkbox' value='%s' %s />\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'center', 'id':'tdCantidadArtSol%s'}).setHTML(\"%s\"), 
				new Element('td', {'align':'center', 'id':'tdCantidadArtDesp%s'}).setHTML(\"%s\"), 
				
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'center', 'class':'noRomper'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'right'}).setHTML(\"<b>%s</b>\"),
				new Element('td', {'align':'center'}).setHTML(\"<img id='imgVerMtoArticulo:%s' src='../img/iconos/ico_view.png' class='puntero noprint'/>".
					"<input type='hidden' id='hddIdPedDet%s' name='hddIdPedDet%s' value='%s'/>".
					"<input type='hidden' id='hddIdArt%s' name='hddIdArt%s' value='%s'/>".
					"<input type='hidden' id='hddIdArtCosto%s' name='hddIdArtCosto%s' value='%s'/>".
					"<input type='hidden' id='hddIdArtAlmacenCosto%s' name='hddIdArtAlmacenCosto%s' value='%s'/>".
					"<input type='hidden' id='hddCantArt%s' name='hddCantArt%s' value='%s'/>".
					"<input type='hidden' id='hddIdPrecioArt%s' name='hddIdPrecioArt%s' value='%s'/>".
					"<input type='hidden' id='hddPrecioArt%s' name='hddPrecioArt%s' value='%s'/>".
					"<input type='hidden' id='hddCostoArt%s' name='hddCostoArt%s' value='%s'/>".//costo
					"<input type='hidden' id='hddIdIvaArt%s' name='hddIdIvaArt%s' value='%s'/>".
					"<input type='hidden' id='hddIvaArt%s' name='hddIvaArt%s' value='%s'/>".
					"<input type='hidden' id='hddTotalArt%s' name='hddTotalArt%s' value='%s'/>\"),
				new Element('td', {'align':'center', 'id':'tdItmRepAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"<input id='cbxItmAprob' name='cbxItmAprob[]' type='checkbox' value='%s' %s onclick='xajax_calcularTotalDcto();' %s/> %s".
					"<input type='hidden' id='hddValorCheckAprobRpto%s' name='hddValorCheckAprobRpto%s' value='%s'/>".
					"<input type='hidden' id='hddRptoEnSolicitud%s' name='hddRptoEnSolicitud%s' value='%s'/>".
					"<input type='hidden' id='hddRptoTomadoSolicitud2%s' name='hddRptoTomadoSolicitud2%s' value='%s'/>\")	
			]);
			elemento.injectBefore('trItmPie');
			
			$('imgVerMtoArticulo:%s').onclick=function(){
				xajax_buscarMtoArticulo('%s', '%s', '%s');
			}",
			$sigValor, $clase, $sigValor,
				$sigValor, $sigValor, $disabledArt,
				elimCaracter($rowDetRep['codigo_articulo'], ";"),
				utf8_encode($solicitudRep.$rowDetRep['descripcion_articulo']),
				$rowDetRep['id_articulo_costo'],
				$cantidad_art_total,
				$sigValor, cantidadSolicitada($rowDetRep['id_det_orden_articulo_ref']), // Articulos Solicitados total
				$sigValor, cantidadDespachada($rowDetRep['id_det_orden_articulo_ref']), // Articulos Despachados antes $cantidad_art
				//$sigValor, cantidadDisponibleActual($rowDetRep['id_articulo'],$idEmpresa), // Articulos Disponible actual
				number_format($rowDetRep['precio_unitario'],2,".",","),
				$caracterIva,
				//number_format(($cantidad_art * $rowDetRep['precio_unitario']),2,".",","),//anterior ahora:
				//agregado iva nuevo gregor
				number_format(($cantidad_art * $rowDetRep['precio_unitario']),2,".",","),
				number_format($montoMultipleIva,2,".",","),
				
				$sigValor, 
					$sigValor, $sigValor, $rowDetRep['id_det_orden_articulo_ref'],
					$sigValor, $sigValor, $rowDetRep['id_articulo'],
					$sigValor, $sigValor, $rowDetRep['id_articulo_costo'],
					$sigValor, $sigValor, $rowDetRep['id_articulo_almacen_costo'],
					$sigValor, $sigValor, $cantidad_art,
					$sigValor, $sigValor, $rowDetRep['id_precio'],
					$sigValor, $sigValor, $rowDetRep['precio_unitario'],
					$sigValor, $sigValor, $rowDetRep['costo'],					
					$sigValor, $sigValor, $rowDetRep['id_iva'],
					$sigValor, $sigValor, $rowDetRep['iva'],
					$sigValor, $sigValor, ($cantidad_art*$rowDetRep['precio_unitario']),
				$sigValor, $sigValor, $checkedArt, $displayArt, $imgCheckDisabledArt,
					$sigValor, $sigValor, $value_checkedArt,
					$sigValor, $sigValor, $row['id_det_solicitud_repuesto'],
					$sigValor, $sigValor, $repuestosTomadosEnSolicitud2,
					
				$sigValor,
					$rowDetRep['id_det_orden_articulo_ref'], $rowDetRep['codigo_articulo'], utf8_encode($rowDetRep['descripcion_articulo'])));
			
			if ($valFormTotalDcto['hddAccionTipoDocumento'] == 1)
				$objResponse->script(sprintf("
				$('tdInsElimRep').style.display = '';
				$('tdItmRep:%s').style.display = '';
				$('tdItmRepAprob:%s').style.display='none';",
					$sigValor,
					$sigValor));	
			else if ($valFormTotalDcto['hddAccionTipoDocumento']==2 )
				$objResponse->script(sprintf("
				$('tdInsElimRep').style.display = 'none';
				$('tdItmRep:%s').style.display = 'none';
				$('tdItmRepAprob:%s').style.display='';
				$('cbxItmAprob').disabled = true;",
					$sigValor,
					$sigValor));	
			else if ($valFormTotalDcto['hddAccionTipoDocumento']==4)
				$objResponse->script(sprintf("
				$('tdInsElimRep').style.display = 'none'; 
				$('tdItmRep:%s').style.display = 'none'; 
				$('tdItmRepAprob:%s').style.display='';",
					$sigValor,
					$sigValor));
			else if ($valFormTotalDcto['hddAccionTipoDocumento']==3)
				$objResponse->script(sprintf("
				$('tdInsElimRep').style.display = '';
				$('tdItmRep:%s').style.display = '';
				$('tdItmRepAprob:%s').style.display='';",
					$sigValor,
					$sigValor));
			
//			if($_GET['cons'] == 1) {
//				$objResponse->script(sprintf("$('tdCantidadArtDesp%s').style.display = '';",
//					$sigValor));
//			} else {
//				$objResponse->script(sprintf("$('tdCantidadArtDesp%s').style.display = 'none';",
//					$sigValor));
//			}
			
			$arrayObj[] = $sigValor;
			$sigValor++;
		}
		
//		if ($_GET['cons'] == 1) {
//			$objResponse->script(sprintf("$('tdDespachado').style.display = '';"));
//		} else {
//			$objResponse->script(sprintf("$('tdDespachado').style.display = 'none';"));
//		}
			
		if ($repuestosTomadosEnSolicitud2 == 1) {
			$objResponse->assign("tdInsElimRep","innerHTML","<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");
		}
		
		
		if($readonly_check_ppal_list_repuesto == 1) {
			$objResponse->script("$('cbxItmAprob').style.display = 'none';");
			$objResponse->assign("tdRepAprob","innerHTML","<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' checked='checked' />");
			//$objResponse->assign("tdInsElimRep","innerHTML","<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");
		}
				
		if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2 || $valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
			if ($sigValor == 1) {
				$objResponse->script("
				$('frmListaArticulo').style.display='none';
				$('tblListaArticulo').style.display='none';");
			}
			
			/*if($tieneRptoSinSolicitud == 1)
				$objResponse->script("
					$('lstMecSolRptoBusq').disabled = false;");*/
		} else if ($valFormTotalDcto['hddTipoDocumento'] == 1) { //PRESUPUESTO
			if ($sigValor == 1) {
				$objResponse->script("
				$('frmListaArticulo').style.display='none';
				$('tblListaArticulo').style.display='none';");
			}
		}
		
		if ($_GET['cons'] == 1) { // CONTROL DE ARTICULOS DESPACHADOS (LOS QUE SE VAN A FACTURAR)
			$adnlQuery = sprintf("SELECT 
				SUM(sa_det_orden_articulo.precio_unitario) AS TOTAL
			FROM sa_paquetes
				INNER JOIN sa_det_orden_articulo ON (sa_paquetes.id_paquete = sa_det_orden_articulo.id_paquete)
				INNER JOIN sa_det_solicitud_repuestos ON (sa_det_orden_articulo.id_det_orden_articulo = sa_det_solicitud_repuestos.id_det_orden_articulo)
			WHERE sa_det_orden_articulo.id_orden = %s
				AND sa_det_orden_articulo.id_paquete = idPaq
				AND sa_det_orden_articulo.estado_articulo <> 'DEVUELTO'
				AND (sa_det_solicitud_repuestos.id_estado_solicitud = 3
					OR sa_det_solicitud_repuestos.id_estado_solicitud = 5) ",
				valTpDato($idDocumento,"int"));
		} else {
			$adnlQuery = sprintf("SELECT 
				SUM(%s.cantidad * %s.precio_unitario) AS TOTAL
			FROM sa_paquetes
				INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
			WHERE %s.%s = %s
				AND %s.id_paquete = idPaq
				AND %s.estado_articulo <> 'DEVUELTO'", 
			$tablaDocDetArt, $tablaDocDetArt,
			$tablaDocDetArt, $tablaDocDetArt,
			$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento,"int"), $tablaDocDetArt, $tablaDocDetArt, $tablaDocDetArt);
		}
				
		$queryDetPaq = sprintf("SELECT 
			sa_paquetes.id_paquete AS idPaq,
			sa_paquetes.codigo_paquete,
			sa_paquetes.descripcion_paquete,
			
			(CASE (SELECT id_estado_orden FROM %s WHERE %s = %s LIMIT 1)
				WHEN 13 THEN
					(SELECT SUM(%s.precio_unitario) AS TOTAL
					FROM sa_paquetes
						INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
						INNER JOIN sa_det_solicitud_repuestos ON (%s.id_det_orden_articulo = sa_det_solicitud_repuestos.id_det_orden_articulo)
					WHERE %s.%s = %s
						AND %s.id_paquete = idPaq
						AND (SELECT COUNT(*) FROM sa_det_orden_articulo_iva WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = %s.id_det_orden_articulo) = 0
						AND %s.estado_articulo <> 'DEVUELTO'
						AND (sa_det_solicitud_repuestos.id_estado_solicitud = 3
							OR sa_det_solicitud_repuestos.id_estado_solicitud = 5))
				ELSE
					(SELECT SUM(%s.cantidad * %s.precio_unitario) AS TOTAL
					FROM sa_paquetes
						INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
					WHERE %s.%s = %s
						AND %s.id_paquete = idPaq
						AND (SELECT COUNT(*) FROM sa_det_orden_articulo_iva WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = %s.id_det_orden_articulo) = 0
						AND %s.estado_articulo <> 'DEVUELTO')
			END) AS total_art_exento,
			
			(CASE (SELECT id_estado_orden FROM %s WHERE %s = %s LIMIT 1)
				WHEN 13 THEN
					(SELECT SUM(%s.precio_unitario) AS TOTAL
					FROM sa_paquetes
						INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
						INNER JOIN sa_det_solicitud_repuestos ON (%s.id_det_orden_articulo = sa_det_solicitud_repuestos.id_det_orden_articulo)
					WHERE %s.%s = %s
						AND %s.id_paquete = idPaq
						AND (SELECT COUNT(*) FROM sa_det_orden_articulo_iva WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = %s.id_det_orden_articulo) > 0
						AND %s.estado_articulo <> 'DEVUELTO'
						AND (sa_det_solicitud_repuestos.id_estado_solicitud = 3
							OR sa_det_solicitud_repuestos.id_estado_solicitud = 5))
				ELSE
					(SELECT SUM(%s.cantidad * %s.precio_unitario) AS TOTAL
					FROM sa_paquetes
						INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
					WHERE %s.%s = %s
						AND %s.id_paquete = idPaq
						AND (SELECT COUNT(*) FROM sa_det_orden_articulo_iva WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = %s.id_det_orden_articulo) > 0
						AND %s.estado_articulo <> 'DEVUELTO')
			END) AS total_art_con_iva,
				
			(SELECT SUM(
				CASE %s.id_modo
					when '1' then %s.ut * %s.precio_tempario_tipo_orden/ %s.base_ut_precio 
					when '2' then %s.precio
					when '3' then %s.costo end) AS total
			FROM sa_paquetes
				INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
			WHERE %s.%s = %s
				AND %s.id_paquete = idPaq
				AND %s.estado_tempario <> 'DEVUELTO') AS total_tmp,
			(%s) AS total_rpto,
			
			(IFNULL((SELECT SUM(
				CASE %s.id_modo
					when '1' then %s.ut * %s.precio_tempario_tipo_orden/ %s.base_ut_precio 
					when '2' then %s.precio
					when '3' then %s.costo
				END) AS total
			FROM sa_paquetes
				INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
			WHERE %s.%s = %s
				AND %s.id_paquete = idPaq
				AND %s.estado_tempario <> 'DEVUELTO'),0)
			+
			IFNULL((%s),0)) AS precio_paquete 
		FROM sa_paquetes
			LEFT JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
			LEFT JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
			#LEFT JOIN %s ON (%s.%s = %s.%s) OR (%s.%s = %s.%s) #donde va OR antes iba AND, cambiado para que tome solo 1, cambiado 3inner por left ELIMINADO
		#WHERE %s.%s = %s #ELIMINADO
		WHERE sa_paquetes.id_paquete IN (
			SELECT id_paquete FROM %s WHERE %s.%s = %s AND %s.id_paquete IS NOT NULL
			UNION
			SELECT id_paquete FROM %s WHERE %s.%s = %s AND %s.id_paquete IS NOT NULL
			)
		GROUP BY sa_paquetes.id_paquete",
		//select id_estado_orden
		$tablaEnc, $campoIdEnc, valTpDato($idDocumento,"int"),
		
		
			$tablaDocDetArt,
				$tablaDocDetArt, $tablaDocDetArt,
				$tablaDocDetArt,
			$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento,"int"),
		 		$tablaDocDetArt, 
				$tablaDocDetArt,
				$tablaDocDetArt,
				
			$tablaDocDetArt, $tablaDocDetArt,
				$tablaDocDetArt, $tablaDocDetArt,
			$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento,"int"),
		 		$tablaDocDetArt, 
				$tablaDocDetArt,
				$tablaDocDetArt,
		
		//select id_estado_orden
		$tablaEnc, $campoIdEnc, valTpDato($idDocumento,"int"),
		
				
			$tablaDocDetArt,
				$tablaDocDetArt, $tablaDocDetArt,
				$tablaDocDetArt,
			$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento,"int"),
				$tablaDocDetArt,
				$tablaDocDetArt,
				$tablaDocDetArt,
			
			$tablaDocDetArt, $tablaDocDetArt,
				$tablaDocDetArt, $tablaDocDetArt,
			$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento,"int"),
				$tablaDocDetArt,
				$tablaDocDetArt,
				$tablaDocDetArt,
			
			
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento,"int"), $tablaDocDetTemp, $tablaDocDetTemp,
			$adnlQuery,
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento,"int"), $tablaDocDetTemp,  $tablaDocDetTemp,
			$adnlQuery,
			$tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetArt, $tablaDocDetArt,
			$tablaEnc, $tablaDocDetTemp, $campoIdEnc, $tablaEnc, $campoIdEnc,
			$tablaEnc, $campoIdEnc, $tablaDocDetArt, $campoIdEnc,
			$tablaEnc, $campoIdEnc, valTpDato($idDocumento,"int"),
			//aqui nuevo gregor UNION 
			$tablaDocDetArt, $tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento,"int"), $tablaDocDetArt,
			$tablaDocDetTemp, $tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento,"int"), $tablaDocDetTemp
			);
			
		$rsDetPaq = mysql_query($queryDetPaq);
		if (!$rsDetPaq) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryDetPaq);
		//return $objResponse->alert($queryDetPaq);//alerta
		$sigValor = 1;
		$arrayObjPaq = NULL;
		
		$readonly_check_ppal_list_paquete = 0;
		$tieneRptoSinSolicitudPaq = 0;
		while ($rowDetPaq = mysql_fetch_assoc($rsDetPaq)) {//de cada paquete se consulta sus items
			
			//$objResponse->alert($rowDetPaq['precio_paquete']);
			$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
				
			$sqlManoObraPaq = sprintf("SELECT 
				%s.id_tempario,
				%s.aprobado,
				%s.id_det_orden_tempario
			FROM %s
			WHERE %s.%s = %s
				AND %s.id_paquete = %s
				AND %s.estado_tempario <> 'DEVUELTO'",
				$tablaDocDetTemp,
				$tablaDocDetTemp,
				$tablaDocDetTemp,
				$tablaDocDetTemp,
				$tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento,"int"), $tablaDocDetTemp, valTpDato($rowDetPaq['idPaq'],"int"),  $tablaDocDetTemp);
			$rsManoObraPaq = mysql_query($sqlManoObraPaq);
			if (!$rsManoObraPaq) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					
			//VALIDACION DE APROBADO POR MANO DE OBRA. DE ESTO DEPENDE SI EL PAQUETE ES O NO APROBADO
			
			// SI LA ACCION ES APROBAR QUE NO LO MUESTRE ASI
			$sqlNroManoObraPaq = sprintf("SELECT 
				COUNT(*) AS numeroMo,
				%s.id_det_orden_tempario
			FROM %s
			WHERE %s.%s = %s
				AND %s.id_paquete = %s
				AND %s.aprobado = 1
				AND %s.estado_tempario <> 'DEVUELTO'
			GROUP BY id_det_orden_tempario",
				$tablaDocDetTemp,
				$tablaDocDetTemp,
				$tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento,"int"),
				$tablaDocDetTemp, valTpDato($rowDetPaq['idPaq'],"int"),
				$tablaDocDetTemp,
				$tablaDocDetTemp);				
			$rsNroManoObraPaq = mysql_query($sqlNroManoObraPaq);
			if (!$rsNroManoObraPaq) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$rowNroManoObraPaq = mysql_fetch_array($rsNroManoObraPaq);
							
			// CUANDO LO VAYA A GUARDAR NO LO PUEDE VOLVER A TOMAR PUESTO QUE YA ESTA GUARDADO, ESTA MISMA CONSULTA COLOCARLA EN GUARDAR 
			
			$repuestosTomadosEnSolicitud = 0;
				
			$sqlRepuestoPaq = sprintf("SELECT 
				%s.id_articulo,
				%s.%s
			FROM %s
			WHERE %s.%s = %s
				AND %s.id_paquete = %s
				AND estado_articulo <> 'DEVUELTO'",
				$tablaDocDetArt,
				$tablaDocDetArt, $campoTablaIdDetArtRelacOrden,
				$tablaDocDetArt, 
				$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento,"int"),
				$tablaDocDetArt, valTpDato($rowDetPaq['idPaq'],"int"));
			$rsRepuestoPaq = mysql_query($sqlRepuestoPaq);
			if (!$rsRepuestoPaq) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
						
			$tieneRepuestos = mysql_num_rows($rsRepuestoPaq);
						
			$cadenaRepPaq = "";
			while ($valorRepuestoPaq = mysql_fetch_array($rsRepuestoPaq)) {
				$cadenaRepPaq .= "|".$valorRepuestoPaq['id_articulo'];
				
				$query = sprintf("SELECT det_sol_rep.id_det_solicitud_repuesto
				FROM sa_det_orden_articulo det_orden_art
					INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (det_orden_art.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo)
					INNER JOIN sa_solicitud_repuestos sol_rep ON (det_sol_rep.id_solicitud = sol_rep.id_solicitud)
				WHERE det_orden_art.id_det_orden_articulo = %s
					AND sol_rep.estado_solicitud != 4;",
					valTpDato($valorRepuestoPaq['id_det_orden_articulo_ref'],"int"));
				$rs = mysql_query($query);
				if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				$row = mysql_fetch_assoc($rs);
													
				if ($row['id_det_solicitud_repuesto'] != '') {
					$repuestosTomadosEnSolicitud = 1;
				}
			}
										
			if($rowNroManoObraPaq['numeroMo'] > 0 || $tieneRepuestos > 0) {
				$checkedPaq = "checked='checked'";  // o se coloca vacio?
				$value_checkedPaq = 1;
				if($repuestosTomadosEnSolicitud == 1){
					$disabledPaq = "disabled='disabled'";   
				}else{//else sino el while toma el ultimo establecido
                                    $disabledPaq = "";
                                }
						
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$readonly_check_ppal_list_paquete = 1;
					$displayPaq = "style='display:none;'";
					$imgCheckDisabledPaq = sprintf("<input id='cbxItmAprobDisabled%s' name='cbxItmAprobDisabled%s' disabled='disabled' type='checkbox' value='%s' checked='checked' />", $sigValor, $sigValor, $sigValor);
				} else {
					if($repuestosTomadosEnSolicitud == 1) {
						$readonly_check_ppal_list_paquete = 1;
						$displayPaq = "style='display:none;'";
						$imgCheckDisabledPaq = sprintf("<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' value='%s' checked='checked' />", $sigValor);
					} else {
						$displayPaq = "";
						$imgCheckDisabledPaq = "";
						$tieneRptoSinSolicitudPaq = 1;
					}
				}
			} else {
				$itemsNoChecados = 1;
				$checkedPaq = " ";
				$value_checkedPaq = 0;
				
				if($repuestosTomadosEnSolicitud == 1){
					$disabledPaq = "disabled='disabled'";   
				}else{//else sino el while toma el ultimo establecido
                                    $disabledPaq = "";
                                }
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$displayPaq = "style='display:none;'";
					$imgCheckDisabledPaq = "<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>";
					$readonly_check_ppal_list_paquete = 1;
				} else {
					$displayPaq = "";
					$imgCheckDisabledPaq = "";
					$tieneRptoSinSolicitudPaq = 1;
				}
			}
						
			// HACER CONSULTA SI MUESTRA O NO EL PAQUETE DESAPROBADO
			$cadenaManoPaq = "";
			while ($valorManoObraPaq = mysql_fetch_array($rsManoObraPaq)) {
				$cadenaManoPaq .= "|".$valorManoObraPaq['id_tempario'];
			}
                        
                        //verifico si tiene repuestos en solicitud para deshabilitar el eliminado
                        if($repuestosTomadosEnSolicitud == 1){
                            $bloqueaEliminacionRepuestos = 1;
                        }else{
                            $bloqueaEliminacionRepuestos = 0;
                        }
					
			if ($value_checkedPaq == 1) {
                            
                            $sqlIvasIdPorcPaq = sprintf("SELECT GROUP_CONCAT(sa_det_orden_articulo_iva.id_iva) AS id_iva_paquete, GROUP_CONCAT(sa_det_orden_articulo_iva.iva) AS porc_iva_paquete, GROUP_CONCAT(sa_det_orden_articulo.precio_unitario*sa_det_orden_articulo.cantidad) AS base_imponible_paquete
                                                        FROM sa_det_orden_articulo
                                                        INNER JOIN sa_det_orden_articulo_iva ON sa_det_orden_articulo.id_det_orden_articulo = sa_det_orden_articulo_iva.id_det_orden_articulo
                                                        WHERE id_orden = %s AND id_paquete = %s AND estado_articulo != 'DEVUELTO'",
                                                valTpDato($idDocumento,"int"),
                                                valTpDato($rowDetPaq['idPaq'],"int"));
                            $rsIvasIdPorcPaq = mysql_query($sqlIvasIdPorcPaq);
                            if (!$rsIvasIdPorcPaq) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sqlIvasIdPorcPaq);
                            
                            $rowIvasIdPorcPaq = mysql_fetch_assoc($rsIvasIdPorcPaq);
                            
				$objResponse->script(sprintf("
				var elemento = new Element('tr', {'id':'trItmPaq:%s', 'class':'textoGris_11px %s' , 'title':'trItmPaq:%s'}).adopt([
					new Element('td', {'align':'center', 'id':'tdItmPaq:%s', 'class':'color_column_insertar_eliminar_item'}).setHTML(\"<input id='cbxItmPaq' name='cbxItmPaq[]' type='checkbox' value='%s' %s /> \"),
					new Element('td', {'align':'left'}).setHTML(\"%s\"),
					new Element('td', {'align':'left'}).setHTML(\"%s\"),
					new Element('td', {'align':'right'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s".
						"<img class='puntero noprint' id='img:%s' src='../img/iconos/ico_view.png' title='Paquete:%s'/>".
						"<input type='hidden' id='hddIdPedDetPaq%s' name='hddIdPedDetPaq%s' readonly='readonly' value='%s'/>".
						"<input type='hidden' id='hddIdPaq%s' name='hddIdPaq%s' readonly='readonly' value='%s'/>".
						"<input type='hidden' id='hddRepPaqAsig%s' name='hddRepPaqAsig%s' readonly='readonly' value='%s'/>".
						"<input type='hidden' id='hddTempPaqAsig%s' name='hddTempPaqAsig%s' readonly='readonly' value='%s'/>".
						"<input type='hidden' id='hddRepPaqAsigEdit%s' name='hddRepPaqAsigEdit%s' readonly='readonly' value='%s'/>".
						"<input type='hidden' id='hddTempPaqAsigEdit%s' name='hddTempPaqAsigEdit%s' readonly='readonly' value='%s'/>".
						"<input type='hidden' id='hddPrecPaq%s' name='hddPrecPaq%s' readonly='readonly' value='%s'/>\"),
					new Element('td', {'align':'center', 'id':'tdItmPaqAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"%s".
						"<input type='checkbox' id='cbxItmPaqAprob%s' name='cbxItmPaqAprob[]' value='%s' %s %s onchange='xajax_validarSiTieneAlmacenAsignado(%s, %s, %s, this.id)'>".
						
						"<input type='hidden' id='hddValorCheckAprobPaq%s' name='hddValorCheckAprobPaq%s' value='%s'/>".
						"<input type='hidden' id='hddRptoPaqEnSolicitud%s' name='hddRptoPaqEnSolicitud%s' value='%s'/>".
						"<input type='hidden' id='hddTotalRptoPqte%s' name='hddTotalRptoPqte%s' value='%s'/>".
						"<input type='hidden' id='hddTotalTempPqte%s' name='hddTotalTempPqte%s' value='%s'/>".
						"<input type='hidden' id='hddRptoTomadoSolicitud%s' name='hddRptoTomadoSolicitud%s' value='%s'/>".
						"<input type='hidden' id='hddTotalExentoRptoPqte%s' name='hddTotalExentoRptoPqte%s' value='%s'/>".
						"<input type='hidden' id='hddIdIvasRepuestoPaquete%s' name='hddIdIvasRepuestoPaquete%s' value='%s'/>".
						"<input type='hidden' id='hddIvasRepuestoPaquete%s' name='hddIvasRepuestoPaquete%s' value='%s'/>".
						"<input type='hidden' id='hddPorcentajesIvasRepuestoPaquete%s' name='hddPorcentajesIvasRepuestoPaquete%s' value='%s'/>".
						"<input type='hidden' id='hddTotalConIvaRptoPqte%s' name='hddTotalConIvaRptoPqte%s' value='%s'/>\")
				]);	
				elemento.injectBefore('trm_pie_paquete');
				
				$('img:%s').onclick = function() {
					$('tblPermiso').style.display = 'none';
					$('tblListados').style.display = 'none';
					$('tblGeneralPaquetes').style.display = '';
					$('tblListadoTempario').style.display = 'none';
					$('tblArticulo').style.display = 'none';
					$('tblNotas').style.display = 'none';
					$('tblListadoTot').style.display = 'none';
                                        limpiarPaquetes();
                                        eliminacionPaqueteIndividual(%s,%s);                                        
					xajax_buscar_mano_obra_repuestos_por_paquete('%s','%s','%s','%s','%s','%s','%s','');
				}",
				$sigValor, $clase, $sigValor,
					$sigValor, $sigValor, $disabledPaq,
					utf8_encode($rowDetPaq['codigo_paquete']),
					utf8_encode($rowDetPaq['descripcion_paquete']),
					number_format($rowDetPaq['precio_paquete'],2,".",","),
					$estado_paquete,
						$sigValor, $rowDetPaq['idPaq'],
						$sigValor, $sigValor, $sigValor,
						$sigValor, $sigValor, $rowDetPaq['idPaq'],
						$sigValor, $sigValor, $cadenaRepPaq,
						$sigValor, $sigValor, $cadenaManoPaq,
						$rowDetPaq['idPaq'], $rowDetPaq['idPaq'], $cadenaRepPaq,
						$rowDetPaq['idPaq'], $rowDetPaq['idPaq'], $cadenaManoPaq,
						$sigValor, $sigValor, round($rowDetPaq['precio_paquete'], 2),
					$sigValor, $imgCheckDisabledPaq,
						$sigValor, $sigValor, $checkedPaq, $displayPaq, $sigValor, $rowDetPaq['idPaq'], $idDocumento,
						$sigValor, $sigValor, $value_checkedPaq,
						$sigValor, $sigValor, $row['id_det_solicitud_repuesto'],
						$sigValor, $sigValor, round($rowDetPaq['total_rpto'], 2),
						$sigValor, $sigValor, round($rowDetPaq['total_tmp'], 2),
						$sigValor, $sigValor, $repuestosTomadosEnSolicitud,
						$sigValor, $sigValor, round($rowDetPaq['total_art_exento'], 2),
						$sigValor, $sigValor, $rowIvasIdPorcPaq['id_iva_paquete'],
						$sigValor, $sigValor, $rowIvasIdPorcPaq['base_imponible_paquete'],
						$sigValor, $sigValor, $rowIvasIdPorcPaq['porc_iva_paquete'],
						$sigValor, $sigValor, round($rowDetPaq['total_art_con_iva'], 2),
				
				$sigValor, $sigValor, $bloqueaEliminacionRepuestos,
					$rowDetPaq['idPaq'], utf8_encode($rowDetPaq['codigo_paquete']), utf8_encode($rowDetPaq['descripcion_paquete']),$rowDetPaq['precio_paquete'], 1, '0',''));
				// EL CERO EN LA SEXTA POSICION INDICA LA VISUALIZACION DE LOS BOTONES ACEPTAR Y CANCELAR EN LOS PAQUETES GUARDADOS...
		
				/*if($imgCheckDisabled != " ")
					$objResponse->script(sprintf("$('imgCheckDisabled:%s').style.display='';",$sigValor));*/
			
				if ($valFormTotalDcto['hddAccionTipoDocumento'] == 1) { // || $valFormTotalDcto['hddAccionTipoDocumento']==3
					$objResponse->script(sprintf("
					$('tdInsElimPaq').style.display = '';
					$('tdItmPaq:%s').style.display = '';
					$('tdItmPaqAprob:%s').style.display = 'none'",
						$sigValor,
						$sigValor));	
				} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2) {
					$objResponse->script(sprintf("
					$('tdInsElimPaq').style.display = 'none';
					$('tdItmPaq:%s').style.display = 'none';
					$('tdItmPaqAprob:%s').style.display = '';",
						$sigValor,
						$sigValor));	
				} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
					$objResponse->script(sprintf("
					$('tdInsElimPaq').style.display = 'none';
					$('tdItmPaq:%s').style.display = 'none';
					$('tdItmPaqAprob:%s').style.display = ''",
						$sigValor,
						$sigValor));	
				} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 3) {
					$objResponse->script(sprintf("
					$('tdInsElimPaq').style.display = '';
					$('tdItmPaq:%s').style.display = '';
					$('tdItmPaqAprob:%s').style.display = '';",
						$sigValor,
						$sigValor));
				}
			}
				
			$arrayObjPaq[] = $sigValor;
			$sigValor++;
		}
	
		if ($readonly_check_ppal_list_paquete == 1) {
			$objResponse->script("
			$('cbxItmPaqAprob').style.display = 'none';");
			
			$objResponse->assign("tdPaqAprob","innerHTML","<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' checked='checked' />");
			$objResponse->assign("tdInsElimPaq","innerHTML","<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");
		}
		
		if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2 || $valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
			if($sigValor == 1) {
				$objResponse->script("
				$('frm_agregar_paq').style.display='none';");
			}
			/*if($tieneRptoSinSolicitudPaq == 1)
				$objResponse->script("
					$('lstMecSolRptoBusq').disabled = false;");*/
		} else {
			if ($valFormTotalDcto['hddTipoDocumento' ]== 1) { //PRESUPUESTO
				if ($sigValor == 1) {
					$objResponse->script("
					$('frmListaArticulo').style.display='none';
					$('tblListaArticulo').style.display='none';");
				}
			}
		}
		
		if($_GET['dev'] == 1)
			$condicionMostrarTotFacturados = sprintf(" AND %s.aprobado = 1", $tablaDocDetTot);
		else
			$condicionMostrarTotFacturados = "";
	
		$queryDetalleTot = sprintf("SELECT *,
			%s.%s
		FROM sa_orden_tot
			INNER JOIN %s ON (sa_orden_tot.id_orden_tot = %s.id_orden_tot)
			INNER JOIN cp_proveedor ON (sa_orden_tot.id_proveedor = cp_proveedor.id_proveedor)
                        LEFT JOIN sa_precios_tot ON (%s.id_precio_tot = sa_precios_tot.id_precio_tot)
		WHERE %s.%s = %s %s",
			$tablaDocDetTot, $campoTablaIdDetTotRelacOrden,
			$tablaDocDetTot, $tablaDocDetTot,
                        $tablaDocDetTot,
			$tablaDocDetTot, $campoIdEnc, valTpDato($idDocumento,"int"), $condicionMostrarTotFacturados);
		$rsDetalleTot = mysql_query($queryDetalleTot);
		if (!$rsDetalleTot) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			
		$sigValor = 1;
		$arrayObjTot = NULL;
		while ($rowDetalleTot = mysql_fetch_assoc($rsDetalleTot)) {
			$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
			
			if($rowDetalleTot['aprobado'] == 1) {
				$checkedTot = "checked='checked'";
				$value_checkedTot = 1;
				//$disabledTot = "disabled='disabled'";
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$readonly_check_ppal_list_tot = 1;
					$displayTot = "style='display:none;'";
					$imgCheckDisabledTot = sprintf("<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' value='%s' checked='checked' />", $sigValor);
				} else {
					$displayTot = "";
					$imgCheckDisabledTot = "";
				}
			} else {
				$itemsNoChecados = 1;
				$checkedTot = " ";
				$value_checkedTot = 0;
				$disabledTot = "";
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$displayTot = "style='display:none;'";
					$imgCheckDisabledTot = "<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>";
					$readonly_check_ppal_list_tot = 1;
				}
			}
			
			$objResponse->script(sprintf("
			var elemento = new Element('tr', {'id':'trItmTot:%s', 'class':'textoGris_11px %s', 'title':'trItmTot:%s'}).adopt([
				new Element('td', {'align':'center', 'id':'tdItmTot:%s', 'class':'color_column_insertar_eliminar_item'}).setHTML(\"<input id='cbxItmTot' name='cbxItmTot[]' type='checkbox' value='%s' onclick='xajax_calcularTotalDcto();' %s />\"),
				new Element('td', {'align':'right'}).setHTML(\"%s\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'right'}).setHTML(\"%s\"),
				new Element('td', {'align':'right'}).setHTML(\"%s\"),
				new Element('td', {'align':'right'}).setHTML(\"%s".
					"<input type='hidden' id='hddIdPedDetTot%s' name='hddIdPedDetTot%s' value='%s'/>".
					"<input type='hidden' id='hddIdTot%s' name='hddIdTot%s' value='%s'/>".
					"<input type='hidden' id='hddIdPorcTot%s' name='hddIdPorcTot%s' value='%s'/>".
					"<input type='hidden' id='hddIdPrecioTotAccesorio%s' name='hddIdPrecioTotAccesorio%s' value='%s'/>".
					"<input type='hidden' id='hddPrecTot%s' name='hddPrecTot%s' value='%s'/>".
					"<input type='hidden' id='hddPorcTot%s' name='hddPorcTot%s' value='%s'/>".
					"<input type='hidden' id='hddMontoTotalTot%s' name='hddMontoTotalTot%s' value='%s'/>\"),
				new Element('td', {'align':'center', 'id':'tdItmTotAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"<input id='cbxItmTotAprob' name='cbxItmTotAprob[]' type='checkbox' value='%s' %s onclick='xajax_calcularTotalDcto();' %s/>".
					"%s".
					"<input type='hidden' id='hddValorCheckAprobTot%s' name='hddValorCheckAprobTot%s' value='%s'/>\")
			]);
			elemento.injectBefore('trm_pie_tot');",
			$sigValor, $clase, $sigValor,
				$sigValor,  $sigValor, $disabledTot,
				"<idtotoculta style='display:none'>".$rowDetalleTot['id_orden_tot']."</idtotoculta>".$rowDetalleTot['numero_tot'],
				utf8_encode($rowDetalleTot['nombre']),
				$rowDetalleTot['tipo_pago'],
				number_format($rowDetalleTot['monto_subtotal'],2,".",","),
				number_format($rowDetalleTot['porcentaje_tot'],2,".",",")."% ".$rowDetalleTot['descripcion'],
				number_format($rowDetalleTot['monto_subtotal']+($rowDetalleTot['monto_subtotal']*$rowDetalleTot['porcentaje_tot']/100),2,".",","),
					$sigValor, $sigValor, $rowDetalleTot['id_det_orden_tot_ref'],//sprintf('%s', $campoTablaIdDetTot)
					$sigValor, $sigValor, $rowDetalleTot['id_orden_tot'],
					$sigValor, $sigValor, $rowDetalleTot['id_porcentaje_tot'],
					$sigValor, $sigValor, $rowDetalleTot['id_precio_tot'],
					$sigValor, $sigValor, str_replace(",","", $rowDetalleTot['monto_subtotal']),
					$sigValor, $sigValor, str_replace(",","", $rowDetalleTot['porcentaje_tot']),
					$sigValor, $sigValor, str_replace(",","", $rowDetalleTot['monto_subtotal']+($rowDetalleTot['monto_subtotal']*$rowDetalleTot['porcentaje_tot']/100)),
			$sigValor, $sigValor, $checkedTot, $displayTot,
				$imgCheckDisabledTot,
				$sigValor, $sigValor, $value_checkedTot));
			
			if ($valFormTotalDcto['hddAccionTipoDocumento'] == 1) {
				$objResponse->script(sprintf("$('tdInsElimTot').style.display = '';
				$('tdItmTot:%s').style.display = '';
				$('tdItmTotAprob:%s').style.display = 'none'",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2) {
				$objResponse->script(sprintf("$('tdInsElimTot').style.display = 'none';
				$('tdItmTot:%s').style.display = 'none';
				$('tdItmTotAprob:%s').style.display = '';
				$('cbxItmTotAprob').disabled = true;",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
				$objResponse->script(sprintf("$('tdInsElimTot').style.display = 'none';
				$('tdItmTot:%s').style.display = 'none';
				$('tdItmTotAprob:%s').style.display = ''",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 3) {
				$objResponse->script(sprintf("
				$('tdInsElimTot').style.display = '';
				$('tdItmTot:%s').style.display = '';
				$('tdItmTotAprob:%s').style.display = '';",
					$sigValor,
					$sigValor));
			}
							
			$arrayObjTot[] = $sigValor;
			$sigValor++;
		}
		
		if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2 || $valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
			if ($sigValor == 1) {
				$objResponse->script("
				$('frmListaTot').style.display = 'none';");
			}
		} else {
			if ($valFormTotalDcto['hddTipoDocumento'] == 1) { //PRESUPUESTO
				if ($sigValor == 1) {
					$objResponse->script("
					$('frmListaTot').style.display = 'none';");
				}
			}
		}
		
		if ($readonly_check_ppal_list_tot == 1) {
			$objResponse->script("
			$('cbxItmTotAprob').style.display = 'none';");
			$objResponse->assign("tdTotAprob","innerHTML","<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' checked='checked' />");
			//$objResponse->assign("tdInsElimTot","innerHTML","<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");	
		}
		
		if ($_GET['dev'] == 1)
			$condicionMostrarTemparioFacturados = sprintf(" AND %s.aprobado = 1", $tablaDocDetTemp);
		else
			$condicionMostrarTemparioFacturados = "";		
		
		$queryDetTemp = sprintf("SELECT 
			%s.%s,
			sa_modo.descripcion_modo,
			sa_tempario.codigo_tempario,
			sa_tempario.descripcion_tempario,
			%s.operador,
			sa_operadores.descripcion_operador,
			%s.id_tempario,
			%s.precio,
			%s.base_ut_precio,
			%s.id_modo,
			(case %s.id_modo
				when '1' then
					%s.ut * %s.precio_tempario_tipo_orden/%s.base_ut_precio
				when '2' then
					%s.precio
				when '3' then
					%s.costo
				when '4' then
					'4'
			end) AS total_por_tipo_orden,
			(case %s.id_modo
				when '1' then
					%s.ut
				when '2' then
					%s.precio
				when '3' then
					%s.costo
				when '4' then
					'4'
			end) AS precio_por_tipo_orden,
			%s.%s,
			pg_empleado.nombre_empleado,
			pg_empleado.apellido,
			pg_empleado.id_empleado,
			sa_mecanicos.id_mecanico,
			sa_seccion.descripcion_seccion,
			sa_subseccion.id_seccion,
			sa_subseccion.descripcion_subseccion,
			sa_seccion.id_seccion,
			%s.aprobado,
			%s.origen_tempario,
			%s.origen_tempario + 0 AS idOrigen
		FROM %s
			INNER JOIN sa_tempario ON (%s.id_tempario = sa_tempario.id_tempario)
			INNER JOIN sa_operadores ON (%s.operador = sa_operadores.id_operador)
			INNER JOIN sa_modo ON (%s.id_modo = sa_modo.id_modo)
			LEFT JOIN sa_mecanicos ON (%s.id_mecanico = sa_mecanicos.id_mecanico)
			LEFT JOIN pg_empleado ON (sa_mecanicos.id_empleado = pg_empleado.id_empleado)
			INNER JOIN sa_subseccion ON (sa_tempario.id_subseccion = sa_subseccion.id_subseccion)
			INNER JOIN sa_seccion ON (sa_subseccion.id_seccion = sa_seccion.id_seccion)
		WHERE %s.id_paquete IS NULL
			AND %s.%s = %s %s",
			$tablaDocDetTemp, $campoTablaIdDetTempRelacOrden,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp, $campoTablaIdDetTemp, 
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento,"int"), $condicionMostrarTemparioFacturados);
		$rsDetTemp = mysql_query($queryDetTemp);
		if (!$rsDetTemp) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			
		$sigValor = 1;
		$arrayObjTemp = NULL;
		while ($rowDetTemp = mysql_fetch_assoc($rsDetTemp)) {
			$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
				
			if ($rowDetTemp['aprobado'] == 1) {
				$checkedTemp = "checked='checked'";
				$value_checkedTemp = 1;
				//$disabledTemp = "disabled='disabled'";
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$readonly_check_ppal_list_tempario = 1;
					$displayTemp = "style='display:none;'";
					$imgCheckDisabledTemp = sprintf("<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' value='%s' checked='checked' />", $sigValor);
				} else {
					$displayTemp = "";
					$imgCheckDisabledTemp = "";							       
				}
			} else {
				$itemsNoChecados = 1;
				$checkedTemp = " ";
				$value_checkedTemp = 0;
				$disabledTemp = "";
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$displayTemp = "style='display:none;'";
					$imgCheckDisabledTemp = "<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>";
					$readonly_check_ppal_list_tempario = 1;
				}/*else {
					$display = "";
					$imgCheckDisabled = "";
				}*/
			}
		
			if ($rowDetTemp['origen_tempario'] == 0) {
				$origen = "ORDEN";
			} else {
				$origen = "CONTROL TALLER";
			}
		
			$objResponse->script(sprintf("
			var elemento = new Element('tr', {'id':'trItmTemp:%s', 'class':'textoGris_11px %s', 'title':'trItmTemp:%s'}).adopt([
				new Element('td', {'align':'center', 'id':'tdItmTemp:%s', 'class':'color_column_insertar_eliminar_item'}).setHTML(\"<input id='cbxItmTemp' name='cbxItmTemp[]' type='checkbox' value='%s' %s />\"),
				new Element('td', {'align':'center', 'id':'tdItmCodMecanico:%s'}).setHTML(\"%s\"),
				new Element('td', {'align':'center', 'id':'tdItmNomMecanico:%s'}).setHTML(\"%s\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'right'}).setHTML(\"%s\"),
				new Element('td', {'align':'right'}).setHTML(\"%s".
					"<input type='hidden' id='hddIdPedDetTemp%s' name='hddIdPedDetTemp%s' value='%s'/>".
					"<input type='hidden' id='hddIdTemp%s' name='hddIdTemp%s' value='%s'/>".
					"<input type='hidden' id='hddIdMec%s' name='hddIdMec%s' value='%s'/>".
					"<input type='hidden' id='hddIvaTemp%s' name='hddIvaTemp%s' value='%s'/>".
					"<input type='hidden' id='hddPrecTemp%s' name='hddPrecTemp%s' value='%s'/>".
					"<input type='hidden' id='hddIdModo%s' name='hddIdModo%s' value='%s'/>\"),
				new Element('td', {'align':'center', 'id':'tdItmTempAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"<input id='cbxItmTempAprob' name='cbxItmTempAprob[]' 'title':'cbxItmTempAprob:%s' type='checkbox' value='%s' %s %s onclick='xajax_calcularTotalDcto();' />".
					"%s".
					"<input type='hidden' id='hddValorCheckAprobTemp%s' name='hddValorCheckAprobTemp%s' value='%s'/>".
					"<input type='hidden' id='hddIdOrigen%s' name='hddIdOrigen%s' value='%s'/>\")
			]);
			elemento.injectBefore('trm_pie_tempario');",
			$sigValor, $clase, $sigValor,
				$sigValor, $sigValor, $disabledTemp,
				$sigValor, $rowDetTemp['id_mecanico'],
				$sigValor, utf8_encode($rowDetTemp['nombre_empleado']." ".$rowDetTemp['apellido']),
				utf8_encode($rowDetTemp['descripcion_seccion']),
				utf8_encode($rowDetTemp['descripcion_subseccion']),
				$rowDetTemp['codigo_tempario'],
				utf8_encode($rowDetTemp['descripcion_tempario']),
				$origen,
				utf8_encode($rowDetTemp['descripcion_modo']),
				$rowDetTemp['descripcion_operador'],
				$rowDetTemp['precio_por_tipo_orden'],
				number_format($rowDetTemp['total_por_tipo_orden'],2,".",","),
					$sigValor, $sigValor, $rowDetTemp['id_det_orden_tempario_ref'],//sprintf('%s', $campoTablaIdDetTemp), $campoTablaIdDetTempRelacOrden)
					$sigValor, $sigValor, $rowDetTemp['id_tempario'],
					$sigValor, $sigValor, $rowDetTemp['id_mecanico'],
					$sigValor, $sigValor, utf8_encode($rowDetTemp['nombre_empleado']." ".$rowDetTemp['apellido']),
					$sigValor, $sigValor, $rowDetTemp['total_por_tipo_orden'],
					$sigValor, $sigValor, utf8_encode($rowDetTemp['descripcion_modo']),
				$sigValor, $sigValor, $sigValor, $checkedTemp, $displayTemp,
					$imgCheckDisabledTemp,
					$sigValor, $sigValor, $value_checkedTemp,
					$sigValor, $sigValor, $rowDetTemp['idOrigen']));
			
			//dependiendo si se muestra o no el mecanico por parametros generales coloco la validacion  $valFormTotalDcto['hddTipoDocumento']==2

			if ($valFormTotalDcto['hddTipoDocumento'] == 1) {
				$objResponse->script(sprintf("$('tdItmCodMecanico:%s').style.display='none'",$sigValor));
				$objResponse->script(sprintf("$('tdItmNomMecanico:%s').style.display='none'",$sigValor));
			} else {
				if($valFormTotalDcto['hddMecanicoEnOrden'] == 1) {	
					$objResponse->script(sprintf("$('tdItmCodMecanico:%s').style.display=''",$sigValor));
					$objResponse->script(sprintf("$('tdItmNomMecanico:%s').style.display=''",$sigValor));
				} else {
					$objResponse->script(sprintf("$('tdItmCodMecanico:%s').style.display='none'",$sigValor));
					$objResponse->script(sprintf("$('tdItmNomMecanico:%s').style.display='none'",$sigValor));
				}
			}
		
			if ($valFormTotalDcto['hddAccionTipoDocumento'] == 1) {
				$objResponse->script(sprintf("
				$('tdInsElimManoObra').style.display = '';
				$('tdItmTemp:%s').style.display = '';
				$('tdItmTempAprob:%s').style.display = 'none'",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2) {
				$objResponse->script(sprintf("
				$('tdInsElimManoObra').style.display = 'none';
				$('tdItmTemp:%s').style.display = 'none';
				$('tdItmTempAprob:%s').style.display = '';
				$('cbxItmTempAprob').disabled = true;",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
				$objResponse->script(sprintf("
				$('tdInsElimManoObra').style.display = 'none';
				$('tdItmTemp:%s').style.display = 'none';
				$('tdItmTempAprob:%s').style.display = ''",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 3) {
				$objResponse->script(sprintf("
				$('tdInsElimTot').style.display = '';
				$('tdItmTot:%s').style.display = '';
				$('tdItmTotAprob:%s').style.display='';",
					$sigValor,
					$sigValor));
			}
					
			$arrayObjTemp[] = $sigValor;
			$sigValor++;
		}
	
		if ($readonly_check_ppal_list_tempario == 1) {
			$objResponse->script("
			$('cbxItmTempAprob').style.display = 'none';");
			$objResponse->assign("tdTempAprob","innerHTML","<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' checked='checked' />");
			//$objResponse->assign("tdInsElimManoObra","innerHTML","<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");
				
		}
			
		if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2 || $valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
			if($sigValor==1) {
				$objResponse->script("
				$('frmListaManoObra').style.display='none';");
			}
		} else {
			if($valFormTotalDcto['hddTipoDocumento']==1) { //PRESUPUESTO
				if($sigValor==1) {
					$objResponse->script("
					$('frmListaManoObra').style.display='none';");
				}
			}
		}
	
		if($_GET['dev'] == 1) {
			$condicionMostrarNotaFacturados = sprintf(" AND %s.aprobado = 1", $tablaDocDetNota);
		} else {
			$condicionMostrarNotaFacturados = "";
		}
	
		$queryDetTipoDocNotas = sprintf("SELECT
			%s.%s,
			%s AS idDetNota,
			%s AS idDoc,
			descripcion_nota,
			precio, 
			aprobado
		FROM %s
		WHERE %s = %s %s",
			$tablaDocDetNota, $campoTablaIdDetNotaRelacOrden, 
			$campoTablaIdDetNota,
			$campoIdEnc,
			$tablaDocDetNota,
			$campoIdEnc, valTpDato($idDocumento,"int"), $condicionMostrarNotaFacturados);
		$rsDetTipoDocNotas = mysql_query($queryDetTipoDocNotas);
		if (!$rsDetTipoDocNotas) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);

		$sigValor = 1;
		$arrayObjNota = NULL;
		while ($rowDetTipoDocNotas = mysql_fetch_assoc($rsDetTipoDocNotas)) {
			$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
				
			//$caracterIva = ($rowPresupuestoDet['id_iva'] != "" && $rowPresupuestoDet['id_iva'] != "0") ? $rowPresupuestoDet['iva']."%" : "NA";
			if ($rowDetTipoDocNotas['aprobado'] == 1) {
				$checkedNota = "checked='checked'"; 
				$value_checkedNota = 1;
				//$disabledNota = "disabled='disabled'";
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$readonly_check_ppal_list_nota = 1;
					$displayNota = "style='display:none;'";
					$imgCheckDisabledNota = sprintf("<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' value='%s' checked='checked' />", $sigValor);
				} else {
					$displayNota = "";
					$imgCheckDisabledNota = "";
				}
			} else {
				$itemsNoChecados = 1;
				$checkedNota = " ";
				$value_checkedNota = 0;
				$disabledNota = "";
				/*$display = "style='display:none;'";
				$imgCheckDisabled = "<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>";*/
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$displayNota = "style='display:none;'";
					$imgCheckDisabledNota = "<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>";
					$readonly_check_ppal_list_nota = 1;
				}/*else {
					$display = "";
					$imgCheckDisabled = "";
					$display = "style='display:none;'";
					$imgCheckDisabled = "<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>";
				}*/
			}
					
			$objResponse->script(sprintf("
			var elemento = new Element('tr', {'id':'trItmNota:%s', 'class':'textoGris_11px %s', 'title':'trItmNota:%s'}).adopt([
				new Element('td', {'id':'tdItmNota:%s', 'class':'color_column_insertar_eliminar_item'}).setHTML(\"<input id='cbxItmNota' name='cbxItmNota[]' type='checkbox' value='%s' %s />\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'right'}).setHTML(\"%s".
					"<input type='hidden' id='hddIdPedDetNota%s' name='hddIdPedDetNota%s' value='%s'/>".
					"<input type='hidden' id='hddIdNota%s' name='hddIdNota%s' value='%s'/>".
					"<input type='hidden' id='hddDesNota%s' name='hddDesNota%s' value='%s'/>".
					"<input type='hidden' id='hddPrecNota%s' name='hddPrecNota%s' value='%s'/>\"),
				new Element('td', {'align':'center' , 'id':'tdItmNotaAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"<input id='cbxItmNotaAprob' name='cbxItmNotaAprob[]' type='checkbox' value='%s' %s %s onclick='xajax_calcularTotalDcto();'/>".
					"%s".
					"<input type='hidden' id='hddValorCheckAprobNota%s' name='hddValorCheckAprobNota%s' value='%s'/>\")
			]);
			elemento.injectBefore('trm_pie_nota');",
			$sigValor, $clase, $sigValor,
				$sigValor, $sigValor, $disabledNota,
				utf8_encode($rowDetTipoDocNotas['descripcion_nota']),
				number_format($rowDetTipoDocNotas['precio'],2,".",","),
					$sigValor, $sigValor, $rowDetTipoDocNotas['id_det_orden_nota_ref'],//'idDetNota'
					$sigValor, $sigValor, $sigValor,
					$sigValor, $sigValor, utf8_encode($rowDetTipoDocNotas['descripcion_nota']),
					$sigValor, $sigValor, $rowDetTipoDocNotas['precio'],
				$sigValor, $sigValor, $checkedNota, $displayNota,
					$imgCheckDisabledNota,
					$sigValor, $sigValor, $value_checkedNota));
					
			if ($valFormTotalDcto['hddAccionTipoDocumento'] == 1) {
				$objResponse->script(sprintf("
				$('tdInsElimNota').style.display = '';
				$('tdItmNota:%s').style.display = '';
				$('tdItmNotaAprob:%s').style.display = 'none'",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2) {
				$objResponse->script(sprintf("
				$('tdInsElimNota').style.display = 'none';
				$('tdItmNota:%s').style.display = 'none'; 
				$('tdItmNotaAprob:%s').style.display = '';
				$('cbxItmNotaAprob').disabled = true;",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
				$objResponse->script(sprintf("
				$('tdInsElimNota').style.display = 'none';
				$('tdItmNota:%s').style.display = 'none';
				$('tdItmNotaAprob:%s').style.display = '';",
					$sigValor,
					$sigValor));
			}
					
			$arrayObjNota[] = $sigValor;
			$sigValor++;
		}
	
		if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2 || $valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
			if ($sigValor == 1) {
				$objResponse->script("
				$('frmListaNota').style.display='none';");
			}
		} else {
			if ($valFormTotalDcto['hddTipoDocumento'] == 1) {
				if ($sigValor == 1) {
					$objResponse->script("
					$('frmListaNota').style.display='none';");
				}
			}
		}
		
		if ($readonly_check_ppal_list_nota == 1) {
			$objResponse->script("
			$('cbxItmNotaAprob').style.display = 'none';");
			$objResponse->assign("tdNotaAprob","innerHTML","<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' checked='checked'/>");
			//$objResponse->assign("tdInsElimNota","innerHTML","<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");
		}
	
		$queryDetPorcentajeAdicional = sprintf("SELECT 
			%s.%s,
			%s.id_porcentaje_descuento,
			%s.porcentaje,
			%s.%s AS idDetDcto,
			sa_porcentaje_descuento.descripcion
		FROM sa_porcentaje_descuento
			INNER JOIN %s ON (sa_porcentaje_descuento.id_porcentaje_descuento = %s.id_porcentaje_descuento)
		WHERE %s.%s = %s",
			$tablaDocDetDescuento, $campoIdEnc,
			$tablaDocDetDescuento,
			$tablaDocDetDescuento,
			$tablaDocDetDescuento, $campoTablaIdDetDescuento,
			$tablaDocDetDescuento, $tablaDocDetDescuento,
			$tablaDocDetDescuento, $campoIdEnc, valTpDato($idDocumento,"int"));
		$rsDetPorcentajeAdicional = mysql_query($queryDetPorcentajeAdicional);
		if (!$rsDetPorcentajeAdicional) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
		$sigValor = 1;
		$arrayObjDcto = NULL;
		while ($rowDetPorcentajeAdicional = mysql_fetch_assoc($rsDetPorcentajeAdicional)) {
			$objResponse->script(sprintf("
			var elemento = new Element('tr', {'id':'trItmDcto:%s', 'class':'textoGris_11px', 'title':'trItmDcto:%s'}).adopt([
				new Element('td', {'align':'right', 'id':'tdItmDcto:%s', 'class':'tituloCampo'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'right'}).setHTML(\"<input type='text' id='hddPorcDcto%s' name='hddPorcDcto%s' size='6' style='text-align:right' readonly='readonly' value='%s'/>%s\"),
				new Element('td', {'align':'right'}).setHTML(\"<img id='imgElimDcto:%s' name='imgElimDcto:%s' src='../img/iconos/ico_quitar.gif' class='puntero noprint' title='Porcentaje Adcl:%s' />".
					"<input type='hidden' id='hddIdDetDcto%s' name='hddIdDetDcto%s' value='%s'/>".
					"<input type='hidden' id='hddIdDcto%s' name='hddIdDcto%s' value='%s'/>\"),
				new Element('td', {'align':'right', 'id':'tdItmNotaAprob:%s'}).setHTML(\"<input type='text' id='txtTotalDctoAdcl%s' name='txtTotalDctoAdcl%s' readonly='readonly' style='text-align:right' size='18'/>\")]);
			elemento.injectBefore('trm_pie_dcto');
			
			$('imgElimDcto:%s').onclick = function() {
				xajax_eliminarDescuentoAdicional(%s);			
			}",
			$sigValor, $sigValor,
				$sigValor, $rowDetPorcentajeAdicional['descripcion'].":",
				"",
				$sigValor, $sigValor, $rowDetPorcentajeAdicional['porcentaje'], "%",
				$sigValor, $sigValor, $sigValor,
					$sigValor, $sigValor, $rowDetPorcentajeAdicional['idDetDcto'],
					$sigValor, $sigValor, $rowDetPorcentajeAdicional['id_porcentaje_descuento'],
				$sigValor, $sigValor, $sigValor,
			
			$sigValor,
				$sigValor));
			
			if ($valFormTotalDcto['hddAccionTipoDocumento'] == 1 || $valFormTotalDcto['hddAccionTipoDocumento'] == 3) {
				$objResponse->script(sprintf("
				$('imgElimDcto:%s').style.display = '';
				$('imgAgregarDescuento').style.display = '';",
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento']==2 || $valFormTotalDcto['hddAccionTipoDocumento']==4) {
				$objResponse->script(sprintf("
				$('imgElimDcto:%s').style.display = 'none';
				$('imgAgregarDescuento').style.display = 'none';",
					$sigValor));
			}
		
			$arrayObjDcto[] = $sigValor;
			$sigValor++;
		}
	
		$cadenaRep = "";
		if(isset($arrayObj)){
			foreach($arrayObj as $indiceRep => $valorRep) {
				$cadenaRep .= "|".$valorRep;
			}
			$objResponse->assign("hddObj","value",$cadenaRep);
		}	
		
		$cadenaPaq = "";
		if(isset($arrayObjPaq)){
			foreach($arrayObjPaq as $indicePaq => $valorPaq) {
				$cadenaPaq .= "|".$valorPaq;
			}
			$objResponse->assign("hddObjPaquete","value",$cadenaPaq);
		}	
	
		$cadenaTot = "";
		if(isset($arrayObjTot)){
			foreach($arrayObjTot as $indiceTot => $valorTot) {
				$cadenaTot .= "|".$valorTot;
			}
			$objResponse->assign("hddObjTot","value",$cadenaTot);
		}	
	
		$cadenaTemp = "";
		if(isset($arrayObjTemp)){
			foreach($arrayObjTemp as $indiceTemp => $valorTemp) {
				$cadenaTemp .= "|".$valorTemp;
			}
			$objResponse->assign("hddObjTempario","value",$cadenaTemp);
		}	
		
		$cadenaNota = "";
		if(isset($arrayObjNota)){
			foreach($arrayObjNota as $indiceNota => $valorNota) {
				$cadenaNota .= "|".$valorNota;
			}
			$objResponse->assign("hddObjNota","value",$cadenaNota);
		}

		$cadenaDcto = "";
		if(isset($arrayObjDcto)){
			foreach($arrayObjDcto as $indiceDcto => $valorDcto) {
				$cadenaDcto .= "|".$valorDcto;
			}
			$objResponse->assign("hddObjDescuento", "value", $cadenaDcto);
		}
	}
	
	$sqlOrdenRetrabajo = "SELECT sa_tipo_orden.id_tipo_orden FROM sa_tipo_orden
	WHERE sa_tipo_orden.orden_retrabajo = 1";
	$rs = mysql_query($sqlOrdenRetrabajo);
	$row = mysql_fetch_assoc($rs);
	
	$id_tipo_orden = $row['id_tipo_orden'];
	
	if ($idTipoOrden == $id_tipo_orden) { //RETRABAJO
		$objResponse->script("
		$('tblLeyendaOrden').style.display='none';
		$('tblMotivoRetrabajo').style.display = '';
		$('txtMotivoRetrabajo').readOnly = true;");
		
		$query = sprintf("SELECT sa_retrabajo_orden.motivo_retrabajo
		FROM sa_orden
			INNER JOIN sa_retrabajo_orden ON (sa_orden.id_orden = sa_retrabajo_orden.id_orden)
		WHERE sa_orden.id_orden = %s",
			$idDocumento);
		$rs = mysql_query($query);
		$row = mysql_fetch_array($rs);
		
		$objResponse->assign("txtMotivoRetrabajo","value", utf8_encode($row['motivo_retrabajo']));
	}
	
	if (isset($_GET['dev'])) {
		if ($_GET['dev'] == 1) {
			$query = sprintf("SELECT 
				cj_cc_encabezadofactura.idFactura,
				cj_cc_encabezadofactura.numeroControl,
				cj_cc_encabezadofactura.numeroFactura,
				cj_cc_encabezadofactura.fechaRegistroFactura
			FROM cj_cc_encabezadofactura
			WHERE cj_cc_encabezadofactura.idFactura = %s",
				$_GET['idfct']);
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$row = mysql_fetch_assoc($rs);
			
			$objResponse->script(sprintf("
			$('tdNroFacturaVenta').style.display='';
			$('tdTxtNroFacturaVenta').style.display='';
			
			$('tblMotivoRetrabajo').style.display = '';
			$('tblLeyendaOrden').style.display='none';
			
			$('txtNroFacturaVentaServ').readOnly = true;
			
			$('txtNroFacturaVentaServ').value='%s';
			$('tdNroControl').style.display='';
			$('tdTxtNroControl').style.display='';",
				$row['numeroFactura']));
		} else {//cargar vale de salida
			$query = sprintf("SELECT 
				sa_vale_salida.id_vale_salida,
				sa_vale_salida.numero_vale,
				sa_vale_salida.fecha_vale,
				sa_vale_salida.id_orden,
				sa_vale_salida.estado_vale,
				sa_vale_salida.motivo_vale,
				sa_vale_salida.id_empleado,
				sa_vale_salida.id_empresa
			FROM sa_vale_salida
			WHERE sa_vale_salida.id_vale_salida = %s",
				$_GET['idVale']);
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$row = mysql_fetch_assoc($rs);
			
			$objResponse->script(sprintf("
			$('tblMotivoRetrabajo').style.display = '';
			$('tblLeyendaOrden').style.display = 'none';
			
			$('tdNroPresupuesto').style.display = '';
			$('tdNroPresupuesto').innerHTML = 'Nro. Vale:';
			$('tdTxtNroPresupuesto').style.display = '';
			$('txtNroPresupuesto').value = '%s';
			$('txtFechaPresupuesto').value = '%s';
							
			$('tdNroControl').style.display = '';
			$('tdTxtNroControl').style.display = '';",
				$row['numero_vale'],
				date("d-m-Y",strtotime($row['fecha_vale']))));
		}
	}
	
	
	$objResponse->assign("txtFechaPresupuesto","value", $fechaDocumento);
	$objResponse->assign("txtIdPresupuesto","value",utf8_encode($idDocumento));
	
	//numeracion de orden y presupuesto a mostrar:
	
	if($valFormTotalDcto['hddTipoDocumento'] == 1){//1 es Presupuesto
		$numeroOrdenPresupuestoMostrar = $numeroPresupuestoMostrar;
	}else{ //Cualquier otra cosa es Orden
		$numeroOrdenPresupuestoMostrar = $numeroOrdenMostrar;
	}
	$objResponse->assign("numeroOrdenPresupuestoMostrar","value",$numeroOrdenPresupuestoMostrar);
	//
	
	$objResponse->assign("txtDescuento","value", $descuento);
	
	$objResponse->assign("hddItemsNoAprobados","value", $itemsNoChecados);
	
        
	//$objResponse->assign("hddIdIvaVenta","value", $id_iva);
	//$objResponse->assign("txtIvaVenta","value", $iva);
		
	/*$id_iva = $row['idIva'];
	$iva = $row['iva'];
	$objResponse->alert($row['idIva']."".$row['iva']);*/
	
	//OJO CON ESTO... PUEDO ESTAR HACIENDO ALGUNA ACTUALIZACION QUE SEA DE ORDEN Y LA HAGA DE PRESUPUESTO O VICEVERSA...
	$objResponse->assign("hddIdOrden","value",utf8_encode($idDocumento));
	
	$objResponse->assign("txtEstadoOrden","value", utf8_encode($estado_orden));
	$objResponse->assign("txtFechaVencimientoPresupuesto","value", $fechaDocumento);
		
	if($idTipoOrden == ""){
		return $objResponse->alert("El vale no posee tipo de vale");
	}
		
	$objResponse->script(sprintf("
	$('fldPresupuesto').style.display='';
	xajax_cargaLstClaveMovimiento($('lstTipoClave').value, %s);",
		$idTipoOrden));
		
	$query = "SELECT id_filtro_orden FROM sa_tipo_orden WHERE id_tipo_orden = ".$idTipoOrden." LIMIT 1";
    $rs = mysql_query($query);
    if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
    $row = mysql_fetch_assoc($rs);
	$idFiltroOrden = $row["id_filtro_orden"];
		
	if ($idFiltroOrden != 5 /*&& $idTipoOrden != 13 && $idTipoOrden != 28*/) { //SIN ASIGNAR AQUI 5, 13, 28 gregor
		$objResponse->script(sprintf("
		xajax_cargaLstTipoOrden('%s');",$idTipoOrden));
	}
	
	$objResponse->assign("hddIdEmpleado","value",$rowUsuario['id_empleado']);
		
	$objResponse->script("xajax_calcularTotalDcto();");
		
	return $objResponse;
}

//Si se usa cargalstdescuentos() aunque la tabla siempre esta vacia
function cargarPorcAdicional($idPorcDctoAdnl) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT 
		sa_porcentaje_descuento.porcentaje,
		sa_porcentaje_descuento.descripcion
	FROM sa_porcentaje_descuento
	WHERE sa_porcentaje_descuento.id_porcentaje_descuento = %s",
		valTpDato($idPorcDctoAdnl, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtPorcDctoAdicional", "value", $row["porcentaje"]);
	$objResponse->assign("txtDescripcionPorcDctoAdicional", "value", utf8_encode($row["descripcion"]));
	
	return $objResponse;
}


//CUENTA ITEMS AGREGADOS AL DOCUMENTO
function contarItemsDcto($valFormTotalDcto, $valFormPaq) {
	$objResponse = new xajaxResponse();
	
	$cont = 0;
	
	$arrayObjPaq = explode("|",$valFormTotalDcto['hddObjPaquete']);
	
	if (isset($arrayObjPaq)) {
		foreach($arrayObjPaq as $indice => $valor){
			if ($valor > 0 && $valor != "") {
				$array = explode("|",$valFormPaq['hddTempPaqAsig'.$valor]);
				$arrayTemparioPaq = NULL;
				if (isset($array)) {
					foreach ($array as $indice2 => $valor2) {
						if ($valor2 > 0 && $valor2 != "")
							$arrayTemparioPaq[] = $valor2;
					}
				}
				
				$array = explode("|",$valFormPaq['hddRepPaqAsig'.$valor]);
				$arrayRepuestosPaq = NULL;
				if (isset($array)) {
					foreach ($array as $indice2 => $valor2) {
						if ($valor2 > 0 && $valor2 != "")
							$arrayRepuestosPaq[] = $valor2;
					}
				}
				
				$arrayDetPaq[0] = $arrayTemparioPaq;
				$arrayDetPaq[1] = $arrayRepuestosPaq;
				
				$arrayPaquete[] = $arrayDetPaq;
				
				$cont += intval(count($arrayTemparioPaq));
				$cont += intval(count($arrayRepuestosPaq));
			}
		}
	}
	
	$arrayObj = explode("|", $valFormTotalDcto['hddObj']);
	
	if (isset($arrayObj)) {
		$arrayRepuestos = NULL;
		foreach($arrayObj as $indice => $valor){
			if ($valor > 0 && $valor != "")
				$arrayRepuestos[] = $valor;
		}
		$cont += intval(count($arrayRepuestos));
	}
	
	$arrayObjTemp = explode("|", $valFormTotalDcto['hddObjTempario']);
	
	if (isset($arrayObjTemp)) {
		$arrayTempario = NULL;
		foreach($arrayObjTemp as $indice => $valor){
			if ($valor > 0 && $valor != "")
				$arrayTempario[] = $valor;
		}
		$cont += intval(count($arrayTempario));
	}
	
	$arrayObjTot = explode("|", $valFormTotalDcto['hddObjTot']);
	
	if (isset($arrayObjTot)) {
		$arrayTot = NULL;
		foreach($arrayObjTot as $indice => $valor){
			if ($valor > 0 && $valor != "")
				$arrayTot[] = $valor;
		}
		$cont += intval(count($arrayTot));
	}
	
	$arrayObjNota = explode("|", $valFormTotalDcto['hddObjNota']);
	
	if (isset($arrayObjNota)) {
		$arrayNota = NULL;
		foreach($arrayObjNota as $indice => $valor){
			if ($valor > 0 && $valor != "")
				$arrayNota[] = $valor;
		}
		$cont += intval(count($arrayNota));
	}
	$objResponse->assign("hddItemsCargados","value", $cont);
			
	return $objResponse;
}

//DESBLOQUEA LA ORDEN AL DAR CLIC EN "CANCELAR" corregido con xajax sincrono para que devuelva true si lo hizo
function desbloquearOrden($id_orden){
	$objResponse = new xajaxResponse();

	mysql_query("START TRANSACTION;");
	
    $updateSQL = "UPDATE sa_orden SET
		id_usuario_bloqueo = null
	WHERE id_orden = ".$id_orden;
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
			
	return $objResponse->setReturnValue(true);
}

//DEVUELVE VALE DE SALIDA, Y GENERA DOCUMENTO VALE DE ENTRADA
function devolverValeSalida($valForm, $valFormTotalDcto) {
	$objResponse = new xajaxResponse();
	
	//compruebo que el tipo de orden genera vale de salida
	$idTipoOrden = $valForm["hddTipoOrdenAnt"];
			
	mysql_query("START TRANSACTION;");
	
	
	$idValeSalida = valTpDato($_GET['idVale'],"int");//lo necesita el kardex
	$idEmpresa =  valTpDato($_GET['ide'],"int");
	
	// BUSCA LA CLAVE DE MOVIMIENTO DE LA DEVOLUCION SEGUN TIPO DE ORDEN
	$queryClaveMov = sprintf("SELECT
		tipo_orden.id_clave_movimiento_dev,
		clave_mov.tipo
	FROM sa_tipo_orden tipo_orden
		INNER JOIN pg_clave_movimiento clave_mov ON (tipo_orden.id_clave_movimiento_dev = clave_mov.id_clave_movimiento)
	WHERE id_tipo_orden = %s;",
		valTpDato($idTipoOrden, "int"));//antes $valForm['lstTipoOrden'] a veces no se envia sino tiene el permiso del tipo de orden.
	$rsClaveMov = mysql_query($queryClaveMov);
	if (!$rsClaveMov) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$rsClaveMov);
	$rowClaveMov= mysql_fetch_assoc($rsClaveMov);
	
	$idClaveMovimiento = $rowClaveMov['id_clave_movimiento_dev'];
	$idTipoMovimiento = $rowClaveMov['tipo']; //gregor
	
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT * FROM pg_empresa_numeracion
	WHERE id_numeracion = (SELECT clave_mov.id_numeracion_documento FROM pg_clave_movimiento clave_mov
							WHERE clave_mov.id_clave_movimiento = %s)
		AND (id_empresa = %s OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																		WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC
	LIMIT 1;",
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$numeroActual = $rowNumeracion['numero_actual'];
	if($numeroActual == NULL) { return $objResponse->alert("Verifique que la empresa tenga numeracion Vale de Entrada".$queryNumeracion); }
	
	// ACTUALIZA LA NUMERACION DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$sqlVerificar = sprintf("SELECT * FROM sa_vale_entrada 
						WHERE id_orden = %s LIMIT 1",
		valTpDato($valForm["txtIdPresupuesto"],"int"));
	$rsVerificar = mysql_query($sqlVerificar);
	if (!$rsVerificar) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	if(mysql_num_rows($rsVerificar) != 0){
		return $objResponse->alert("El vale ya fue devuelto");	
	}
	
	//AQUI2 CORREGIR GREGOR alerta2
	$totalIvas = 0;
	foreach($valFormTotalDcto["ivaActivo"] as $key => $idIvaActivo){
		$totalIvas += $valFormTotalDcto["txtTotalIva".$idIvaActivo];
	}
                
	$queryInsertarValeEntrada = sprintf("INSERT INTO sa_vale_entrada (numero_vale_entrada, id_vale_salida, numero_vale_salida, fecha_vale_salida, id_orden, subtotal, descuento, baseimponible, porcentajeiva, calculoiva, monto_exento, monto_total, estado_vale, motivo_vale, id_empleado, id_empresa, fecha_creada) 
	VALUES ('%s','%s','%s',%s,'%s','%s','%s','%s','%s','%s','%s','%s','%s',%s,'%s','%s',now())",
	valTpDato($numeroActual,"int"),
	valTpDato($idValeSalida,"int"),
	valTpDato($valForm["txtNroPresupuesto"],"int"),
	valTpDato(date("Y-m-d", strtotime($valForm["txtFechaPresupuesto"])),"text"),
	valTpDato($valForm["txtIdPresupuesto"],"int"),
	str_replace(",","", $valFormTotalDcto['txtSubTotal']),
	str_replace(",","", $valFormTotalDcto['txtSubTotalDescuento']),
	str_replace(",","", $valFormTotalDcto['txtBaseImponible']),
	str_replace(",","", $valFormTotalDcto['txtIvaVenta']),
	str_replace(",","", $totalIvas),
	str_replace(",","", $valFormTotalDcto['txtMontoExento']),
	str_replace(",","", $valFormTotalDcto['txtTotalPresupuesto']),
	1,
	valTpDato($valFormTotalDcto['txtMotivoRetrabajo'],"text"),
	valTpDato($valForm["hddIdEmpleado"],"int"),
	valTpDato($idEmpresa,"int")
	);
	
	$insertarHistoricoValeEntrada = mysql_query($queryInsertarValeEntrada);
	if(!$insertarHistoricoValeEntrada) { return $objResponse->alert("Error guardando vale de entrada. \n Erorr: ".mysql_error()." \n N-Error: ".mysql_errno()." \n Archivo: ".__FILE__."\n Linea: ".__LINE__); }
	$idValeEntrada = mysql_insert_id();
	
//	$queryActualizarVale = sprintf("UPDATE sa_vale_salida SET
//		subtotal = %s,
//		descuento = %s,
//		baseImponible = %s,
//		porcentajeIva = %s,
//		calculoIva = %s,
//		monto_exento = %s,
//		monto_total = %s
//	WHERE id_vale_salida = %s",
//		str_replace(",","", $valFormTotalDcto['txtSubTotal']),
//		str_replace(",","", $valFormTotalDcto['txtSubTotalDescuento']),
//		str_replace(",","", $valFormTotalDcto['txtBaseImponible']),
//		str_replace(",","", $valFormTotalDcto['txtIvaVenta']),
//		str_replace(",","", $valFormTotalDcto['txtTotalIva']),
//		str_replace(",","", $valFormTotalDcto['txtMontoExento']),
//		str_replace(",","", $valFormTotalDcto['txtTotalPresupuesto']),
//		$idValeSalida);
//	mysql_query("SET NAMES 'utf8';");
//	$consultaActualizarVale = mysql_query($queryActualizarVale);
//	if (!$consultaActualizarVale) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$consultaActualizarVale);
//	mysql_query("SET NAMES 'latin1';");
	
	$queryFact = sprintf("SELECT 
		vw_sa_orden.id_cliente_pago_orden,
		vw_sa_orden.id_orden,
		vw_sa_orden.id_tipo_orden	
	FROM vw_sa_orden
		INNER JOIN sa_vale_salida ON (vw_sa_orden.id_orden = sa_vale_salida.id_orden)
	WHERE sa_vale_salida.id_vale_salida = %s",
		$idValeSalida);
	$rsFact = mysql_query($queryFact);
	if (!$rsFact) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$rsFact);
	$rowFact = mysql_fetch_assoc($rsFact);

	$queryArt = sprintf("SELECT 
		sa_det_solicitud_repuestos.id_det_orden_articulo,
		COUNT(sa_det_solicitud_repuestos.id_det_orden_articulo) AS nro_rpto_desp,
		sa_det_solicitud_repuestos.id_casilla,
  		sa_det_orden_articulo.id_articulo,
  		sa_det_orden_articulo.id_articulo_costo,
  		sa_det_orden_articulo.id_articulo_almacen_costo,
		sa_orden.id_empresa
	FROM sa_solicitud_repuestos
		INNER JOIN sa_det_solicitud_repuestos ON (sa_solicitud_repuestos.id_solicitud = sa_det_solicitud_repuestos.id_solicitud)
		INNER JOIN sa_orden ON (sa_solicitud_repuestos.id_orden = sa_orden.id_orden)
		INNER JOIN sa_det_orden_articulo ON (sa_det_solicitud_repuestos.id_det_orden_articulo = sa_det_orden_articulo.id_det_orden_articulo)
	WHERE sa_solicitud_repuestos.estado_solicitud = 5
		AND sa_det_solicitud_repuestos.id_estado_solicitud = 5
		AND sa_orden.id_orden = %s
	GROUP BY sa_det_solicitud_repuestos.id_det_orden_articulo,
		sa_det_solicitud_repuestos.id_casilla",
		$rowFact['id_orden']);
	$rsArt = mysql_query($queryArt);
	if (!$rsArt) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$rsArt);
	
        //gregor
        //COMPRUEBO QUE TENGA REPUESTO EN SOLICITUD COMO DESPACHADO, POR LO MENOS 1                                
        $queryComprobacion = sprintf("SELECT 		
		COUNT(sa_det_solicitud_repuestos.id_det_orden_articulo) AS nro_rpto_desp
	FROM sa_solicitud_repuestos
		INNER JOIN sa_det_solicitud_repuestos ON (sa_solicitud_repuestos.id_solicitud = sa_det_solicitud_repuestos.id_solicitud)
		INNER JOIN sa_orden ON (sa_solicitud_repuestos.id_orden = sa_orden.id_orden)
		INNER JOIN sa_det_orden_articulo ON (sa_det_solicitud_repuestos.id_det_orden_articulo = sa_det_orden_articulo.id_det_orden_articulo)
	WHERE sa_solicitud_repuestos.estado_solicitud = 5
		AND sa_det_solicitud_repuestos.id_estado_solicitud = 5
		AND sa_orden.id_orden = %s
	GROUP BY sa_det_solicitud_repuestos.id_det_orden_articulo,
		sa_det_solicitud_repuestos.id_casilla HAVING nro_rpto_desp > 0", //3 DESPACHADO
                            valTpDato($rowFact['id_orden'], "int"));

        $rsComprobacion = mysql_query($queryComprobacion);
        if(!$rsComprobacion){ return $objResponse->alert(mysql_error()."sele comprob repst \n\nLine: ".__LINE__); }

        if(mysql_num_rows($rsComprobacion)){//si tiene despachado, crear movimiento

            $insertSQL = sprintf("INSERT INTO iv_movimiento (id_tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, tipo_costo, fecha_captura, id_usuario, credito) 
            VALUES (%s, %s, %s, %s, NOW(), %s, %s, NOW(), %s, %s)",
                    //valTpDato($idTipoMovimiento, "int"),//anterior estaba por salidas del tipo de orden, y deveria ser entradas de 
                    //valTpDato($valForm['lstClaveMovimiento'], "int"),//anterior lo tomaba desde el formulario, ahora usa el de abajo directo
                    valTpDato(2, "int"), /* 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida */
                    valTpDato($idClaveMovimiento,"int"),
                    valTpDato(1, "int"), /*tipo_documento_movimiento 1= VALE ENTRADA, 2= NOTA DE CREDITO */
                    valTpDato($idValeEntrada, "int"),
                    valTpDato($rowFact['id_cliente_pago_orden'], "int"),
                    valTpDato(0, "boolean"),
                    valTpDato($_SESSION['idUsuarioSysGts'], "int"),
                    valTpDato($rowFact['id_tipo_orden'], "boolean"));
            mysql_query("SET NAMES 'utf8';");
            $Result1 = mysql_query($insertSQL);
            if (!$Result1) return $objResponse->alert(mysql_error()."\n ".__LINE__);
            $idMovimiento = mysql_insert_id();
            mysql_query("SET NAMES 'latin1';");
            
        }
	
	
	
	while($rowArt = mysql_fetch_array($rsArt)) {
		if($rowArt['nro_rpto_desp'] > 0) {
			
			$idCasilla = $rowArt['id_casilla'];
			$idArticulo = $rowArt['id_articulo'];
			
			// VERIFICA EL ESTATUS DE LA CASILLA CON LA CUAL SE HABIA DESPACHADO EL ARTICULO
			$estatusCasillaSQL = sprintf("SELECT estatus FROM iv_articulos_almacen
			WHERE id_articulo = %s
				AND id_casilla = %s",
				$idArticulo,
				$idCasilla);
			$rsEstatusCasillaSQL = mysql_query($estatusCasillaSQL);
			if (!$rsEstatusCasillaSQL) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$estatusCasillaSQL);
			$rowEstatusCasillaSQL = mysql_fetch_assoc($rsEstatusCasillaSQL);
			if ($rowEstatusCasillaSQL['estatus']) {
				$idCasilla = $rowArt['id_casilla'];
			} else {
				$nuevaCasillaSQL = sprintf("SELECT id_casilla_predeterminada FROM iv_articulos_empresa
				WHERE id_articulo = %s
					AND id_empresa = %s",
					$idArticulo,
					$idEmpresa);
				$rsNuevaCasillaSQL = mysql_query($nuevaCasillaSQL);
				if (!$rsNuevaCasillaSQL) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$nuevaCasillaSQL);
				$rowNuevaCasillaSQL = mysql_fetch_assoc($rsNuevaCasillaSQL);
				
				$idCasilla = $rowNuevaCasillaSQL['id_casilla_predeterminada'];
			}
			
			if ($idCasilla == NULL || $idCasilla == ""){
				$articuloSql = sprintf("SELECT
					codigo_articulo,
					descripcion
				FROM iv_articulos
				WHERE id_articulo = %s",
					$idArticulo);
				$rsArticuloSql = mysql_query($articuloSql);
				if (!$rsArticuloSql) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$articuloSql);
				$rowArticuloSql = mysql_fetch_assoc($rsArticuloSql);
				
				return $objResponse->alert("No se puede realizar Vale de Entrada debido a que Existen Repuestos Sin Ubicacion:\n\tCodigo: ".$rowArticuloSql['codigo_articulo']."\n\tDescripcion: ".$rowArticuloSql['descripcion']);
			}
			
			$sqlIndividual = "";
			if($rowArt['id_articulo_costo'] > 0){
				$sqlIndividual = sprintf(" AND sa_det_vale_salida_articulo.id_articulo_costo = %s 
								AND sa_det_vale_salida_articulo.id_articulo_almacen_costo = %s ",
								$rowArt['id_articulo_costo'],
								$rowArt['id_articulo_almacen_costo']);
			}
			
			//BUSCO COSTO CON EL QUE SALIO                    
			$queryArtValeSalida = sprintf("SELECT 
				sa_det_vale_salida_articulo.precio_unitario,
				sa_det_vale_salida_articulo.costo AS costo_compra,
				sa_det_vale_salida_articulo.id_articulo_costo,
				sa_det_vale_salida_articulo.id_articulo_almacen_costo,
				(SELECT valor FROM pg_configuracion_empresa config_emp
						INNER JOIN pg_configuracion config ON config_emp.id_configuracion = config.id_configuracion
						WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = %s) as modo_costo,
						
					(SELECT costo_promedio FROM iv_articulos_costos 
							WHERE id_articulo = %s ORDER BY id_articulo_costo DESC LIMIT 1) AS costo_promedio
							
				FROM sa_det_vale_salida_articulo 
				WHERE id_vale_salida = %s 
				AND id_articulo = %s %s LIMIT 1",			
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idValeSalida, "int"),
				valTpDato($idArticulo, "int"),
				$sqlIndividual);//COSTO INDIVIDUAL, ARTICULO AGRUPA
			$rsArtValeSalida = mysql_query($queryArtValeSalida);
			if (!$rsArtValeSalida) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\n".$queryArtValeSalida);
			$rowArtValeSalida = mysql_fetch_assoc($rsArtValeSalida);
			
                        //buscar id costo id almacen actual
                        if($rowArtValeSalida["modo_costo"] == 1 || $rowArtValeSalida["modo_costo"] == 2){// 1 = Reposición, 2 = Promedio, 3 = FIFO
                        
                            $queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
                                    WHERE vw_iv_art_almacen_costo.id_articulo = %s
                                            AND vw_iv_art_almacen_costo.id_casilla = %s
                                            AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
                                    ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC;",
									valTpDato($idArticulo, "int"),
									valTpDato($idCasilla, "int"));
                            $rsArtCosto = mysql_query($queryArtCosto);
                            if (!$rsArtCosto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
                            $totalRowsArtCosto = mysql_num_rows($rsArtCosto);
                            $rowArtCosto = mysql_fetch_assoc($rsArtCosto);

                            $idArticuloAlmacenCosto = $rowArtCosto['id_articulo_almacen_costo'];
                            $idArticuloCosto = $rowArtCosto['id_articulo_costo'];                        

                        }else{//si es fifo 3 se mantiene
                            $idArticuloAlmacenCosto = $rowArtValeSalida['id_articulo_almacen_costo'];
                            $idArticuloCosto = $rowArtValeSalida['id_articulo_costo'];       
                        }
                        
                        $queryCostoActual = sprintf("SELECT costo, costo_promedio FROM vw_iv_articulos_almacen_costo 
                                                    WHERE id_articulo_almacen_costo = %s AND id_articulo_costo = %s LIMIT 1",
                                                    $idArticuloAlmacenCosto, 
                                                    $idArticuloCosto);
                        
                        $rsCostoActual = mysql_query($queryCostoActual);
                        if (!$rsCostoActual) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			$rowCostoActual = mysql_fetch_assoc($rsCostoActual);
                        
			if($rowArtValeSalida["modo_costo"] == 1 || $rowArtValeSalida["modo_costo"] == 3){
				$costoPromedioCompra = $rowCostoActual["costo"];
			}else{
				$costoPromedioCompra = $rowCostoActual["costo_promedio"];
			}
				// VERIFICA SI EL LOTE TIENE LA UBICACION ASIGNADA
				$queryArtAlmCosto = sprintf("SELECT *
				FROM iv_articulos_almacen art_almacen
					INNER JOIN iv_articulos_almacen_costo art_almacen_costo ON (art_almacen.id_articulo_almacen = art_almacen_costo.id_articulo_almacen)
				WHERE art_almacen.id_articulo = %s
					AND art_almacen.id_casilla = %s
					AND art_almacen_costo.id_articulo_costo = %s;",
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato($idArticuloCosto, "int"));
				$rsArtAlmCosto = mysql_query($queryArtAlmCosto);
				if (!$rsArtAlmCosto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$totalRowsArtAlm = mysql_num_rows($rsArtAlmCosto);
				$rowArtAlmCosto = mysql_fetch_assoc($rsArtAlmCosto);
				
				$hddIdArticuloAlmacenCosto = $rowArtAlmCosto['id_articulo_almacen_costo'];
			
				if ($totalRowsArtAlm > 0) {
					// ACTUALIZA EL ESTATUS DE LA UBICACION DEL LOTE
					$updateSQL = sprintf("UPDATE iv_articulos_almacen_costo SET
						estatus = 1
					WHERE id_articulo_almacen_costo = %s;",
						valTpDato($hddIdArticuloAlmacenCosto, "int"));
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				} else {
					// LE ASIGNA EL LOTE A LA UBICACION
					$insertSQL = sprintf("INSERT INTO iv_articulos_almacen_costo (id_articulo_almacen, id_articulo_costo, estatus)
					SELECT art_almacen.id_articulo_almacen, %s, 1 FROM iv_articulos_almacen art_almacen
					WHERE art_almacen.id_casilla = %s
						AND art_almacen.id_articulo = %s
						AND art_almacen.estatus = 1;",
							valTpDato($idArticuloCosto, "int"),
							valTpDato($idCasilla, "int"),
							valTpDato($idArticulo, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$hddIdArticuloAlmacenCosto = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
				}
				
				$idArticuloAlmacenCosto = $hddIdArticuloAlmacenCosto;
				 
				if($idArticuloAlmacenCosto == ""){
					return $objResponse->alert("No se pudo tomar la relacion con almacen en la devolucion \nLote: ".$idArticuloCosto." \nAlm Cost: ".$idArticuloAlmacenCosto." \nLinea: ".__LINE__);
				}
                        
			$insertSQLKardex = sprintf("INSERT INTO iv_kardex(id_modulo, id_documento, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, tipo_movimiento, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, id_clave_movimiento, estado, fecha_movimiento, hora_movimiento) 
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW())",
				1,
				valTpDato($idValeEntrada, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idCasilla, "int"),
				valTpDato($idArticuloAlmacenCosto, "int"),
				valTpDato($idArticuloCosto, "int"),
				valTpDato(2, "int"), /* 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida */
				valTpDato(1, "int"), /*tipo_documento_movimiento 1= VALE ENTRADA, 2= NOTA DE CREDITO */
				valTpDato($rowArt['nro_rpto_desp'], "int"),
				valTpDato($rowArtValeSalida['precio_unitario'], "real_inglesa"),
				valTpDato($costoPromedioCompra, "real_inglesa"),
				valTpDato(0, "int"),//costo cargo
				valTpDato(0, "text"),
				valTpDato(0, "real_inglesa"),
				valTpDato($idClaveMovimiento, "int"),
				valTpDato(0, "int")); /* 0 = Entrada, 1 = Salida */
			
			$Result1Kardex = mysql_query($insertSQLKardex);
			if (!$Result1Kardex) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$Result1Kardex);
			$idKardex = mysql_insert_id();
			
			$insertSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, id_articulo_almacen_costo, id_articulo_costo, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, correlativo1, correlativo2, tipo_costo, llave_costo_identificado, promocion, id_moneda_costo, id_moneda_costo_cambio) 
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
				valTpDato($idMovimiento, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idKardex, "int"),
				valTpDato($idArticuloAlmacenCosto, "int"),
				valTpDato($idArticuloCosto, "int"),
				valTpDato($rowArt['nro_rpto_desp'], "int"),
				valTpDato($rowArtValeSalida['precio_unitario'], "real_inglesa"),
				valTpDato($costoPromedioCompra, "real_inglesa"),
				valTpDato(0, "int"),//costo cargo
				valTpDato(0, "text"),
				valTpDato(0, "real_inglesa"),//(($valFormTotalDcto['txtDescuento']*$totaArticulo)/100)
				valTpDato("", "int"),
				valTpDato("", "int"),
				valTpDato(0, "int"),
				valTpDato("", "text"),
				valTpDato(0, "boolean"),
				valTpDato("", "int"),
				valTpDato("", "int"));
				
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert("Erorr guardando iv_movimiento_detalle \n ".mysql_error()." \n Linea: ".__LINE__);
			
			// EDITA LAS CANTIDADES DE ENTRADAS EN LA UBICACION
			// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO - Roger
			$Result1 = actualizarMovimientoTotal($idArticulo, $idEmpresa);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }

			// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS Y ESPERA)
			$Result1 = actualizarSaldos($idArticulo, $idCasilla);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
                        
                    
		}
	}
	
	//empleado vale de salida gregor
	
	$queryActualizarEdoFactura = sprintf("UPDATE sa_vale_salida SET estado_vale = 1, 
                                                    id_empleado_devolucion = %s,
                                                    fecha_devolucion = NOW()
                                                    WHERE sa_vale_salida.id_vale_salida = %s", 
                                                    valTpDato($_SESSION['idEmpleadoSysGts'],"int"),
                                                    $idValeSalida);

	$rsActualizarEdoFactura = mysql_query($queryActualizarEdoFactura);
	if (!$rsActualizarEdoFactura) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$rsActualizarEdoFactura);

	mysql_query("COMMIT;");
	
	$objResponse->alert("Devolucion de vale de salida Guardada Exitosamente");
	$objResponse->script(sprintf("window.location.href = 'sa_formato_vale_salida.php?valBusq=0|%s'", $idValeSalida));
	
	
	return $objResponse;
}

//BOTON ELIMINAR ARTICULO DESDE ORDEN
function eliminarArticuloEnPresupuesto($valForm) {
	$objResponse = new xajaxResponse();	
	
	if (isset($valForm['cbxItm'])) {
		foreach($valForm['cbxItm'] as $indiceItm=>$valorItm) {
			$objResponse->script(sprintf("
				fila = document.getElementById('trItm:%s');
							
				padre = fila.parentNode;
				padre.removeChild(fila);",
			$valorItm));
		}
	}
	$objResponse->script("xajax_calcularTotalDcto();");		

	return $objResponse;
}

//BOTON ELIMINAR DESCUENTO ADICIONAL DESDE ORDEN
function eliminarDescuentoAdicional($idItemDescuento) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script(sprintf("
	fila = document.getElementById('trItmDcto:%s');
	padre = fila.parentNode;
	padre.removeChild(fila);",
		$idItemDescuento));
	
	$objResponse->script("xajax_calcularTotalDcto();");	
	
	return $objResponse;
}

//BOTON ELIMINAR NOTAS DESDE ORDEN
function eliminarNotaEnPresupuesto($valForm) {
	$objResponse = new xajaxResponse();	
	
	if (isset($valForm['cbxItmNota'])) {
		foreach($valForm['cbxItmNota'] as $indiceItm=>$valorItm) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItmNota:%s');
						
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valorItm));
		}
	}
	$objResponse->script("xajax_calcularTotalDcto();");		

	return $objResponse;
}

//BOTON ELIMINAR PAQUETES DESDE ORDEN
function eliminarPaquete($valForm) {
	$objResponse = new xajaxResponse();
	
	if (isset($valForm['cbxItmPaq'])) {
		foreach($valForm['cbxItmPaq'] as $indiceItm=>$valorItm) {
			$objResponse->script(sprintf("
				fila = document.getElementById('trItmPaq:%s');
							
				padre = fila.parentNode;
				padre.removeChild(fila);",
			$valorItm));
		}
	}
	$objResponse->script("xajax_calcularTotalDcto();");
	
	return $objResponse;
}

//BOTON ELIMINAR TEMPARIO DESDE ORDEN
function eliminarTemparioEnPresupuesto($valForm) {
	$objResponse = new xajaxResponse();	
	
	if (isset($valForm['cbxItmTemp'])) {
		foreach($valForm['cbxItmTemp'] as $indiceItm=>$valorItm) {
			$objResponse->script(sprintf("
				fila = document.getElementById('trItmTemp:%s');
							
				padre = fila.parentNode;
				padre.removeChild(fila);",
			$valorItm));
		}
	}
	$objResponse->script("xajax_calcularTotalDcto();");		

	return $objResponse;
}

//BOTON ELIMINAR TOT DESDE ORDEN
function eliminarTot($valForm) {
	$objResponse = new xajaxResponse();
	
	if (isset($valForm['cbxItmTot'])) {
		foreach ($valForm['cbxItmTot'] as $indiceItm => $valorItm) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItmTot:%s');		
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valorItm));
		}
	}
	$objResponse->script("xajax_calcularTotalDcto();");
	
	return $objResponse;
}

//VERIFICACION DE CLAVE CUANDO INTENTAS ELIMINAR ALGUN ITEM QUE ESTE RELACIONADO CON PRESUPUESTO
//ESTA FUNCION SOLO PREPARA EL FORMULARIO DE CLAVE, LA VERDADERA VALIDACION DE CLAVE ESTA EN OTRO
//SON: Eliminacion de T.O.T, Agregar Vale Facturado, Eliminacion Nota, Eliminacion Tempario, Eliminacion Articulo, Tipo Orden GARANTIA
//Duplicar M.O, Duplicar Repuesto
function formClave($valFormTotalDcto, $accForm) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT *
	FROM pg_empleado
		INNER JOIN pg_usuario ON (pg_empleado.id_empleado = pg_usuario.id_empleado)
		INNER JOIN sa_claves ON (pg_empleado.id_empleado = sa_claves.id_empleado)
	WHERE sa_claves.modulo = '%s'
		AND pg_usuario.id_usuario = %s",
		$accForm,
		$_SESSION['idUsuarioSysGts']);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$num_rows = mysql_num_rows($rs);
	
	if ($num_rows > 0) {
		$objResponse->script("document.forms['frmConfClave'].reset();");
		
		$objResponse->assign("hddAccionObj","value", $accForm);
		
		switch($accForm) {
			case 'elim_tot':
				$objResponse->assign("tdFlotanteTitulo2","innerHTML",("Acceso Eliminacion de T.O.T"));
				break;
			case 'agreg_vr_fact':
				$objResponse->assign("tdFlotanteTitulo2","innerHTML",("Acceso Agregar Vale Facturado"));
				$objResponse->script("$('tblClaveDescuento').style.display = '';");
				break;
			case 'elim_not_aprob': 
				$objResponse->assign("tdFlotanteTitulo2","innerHTML",("Acceso a Eliminacion Nota"));
				$objResponse->script("$('tblClaveDescuento').style.display = '';");
				break;
			case 'elim_temp_aprob':	
				$objResponse->assign("tdFlotanteTitulo2","innerHTML",("Acceso a Eliminacion Tempario"));
				$objResponse->script("$('tblClaveDescuento').style.display = '';");
				break;
			case 'elim_art_aprob':	
				$objResponse->assign("tdFlotanteTitulo2","innerHTML",("Acceso a Eliminacion Articulo"));
				$objResponse->script("$('tblClaveDescuento').style.display = '';");
				break;
			case 'acc_ord_gtia':	
				$objResponse->assign("tdFlotanteTitulo2","innerHTML",("Acceso Tipo Orden GARANTIA"));
				$objResponse->script("$('tblClaveDescuento').style.display = '';");
				break;
			case 'acc_dup_mo':	
				$objResponse->assign("tdFlotanteTitulo2","innerHTML",("Acceso Duplicar M.O"));
				$objResponse->script("$('tblClaveDescuento').style.display = '';");
				break;
			case 'acc_dup_rep':	
				$objResponse->assign("tdFlotanteTitulo2","innerHTML",("Acceso Duplicar Repuesto"));
				$objResponse->script("$('tblClaveDescuento').style.display = '';");
				break;
		}
			$objResponse->script("
			$('tblListados1').style.display = 'none';
			$('tblArticulosSustitutos').style.display = 'none';
			$('tblClaveDescuento').style.display = '';
			$('tblMtosArticulos').style.display = 'none';
			$('tblPorcentajeDescuento').style.display = 'none';");
			
			$objResponse->script("
			$('divFlotante2').style.display = '';
			centrarDiv($('divFlotante2'));
			
			$('txtContrasenaAcceso').focus();
			$('txtContrasenaAcceso').select();");
				
		if ($accForm != 'agreg_vr_fact' && $accForm != 'acc_dup_mo' && $accForm != 'acc_dup_rep') {
			$objResponse->script("
			if ($('divFlotante').style.display == '') {
				$('divFlotante').style.display = 'none';
				centrarDiv($('divFlotante'));
			}");
		} else {
		}
	} else {
		$objResponse->alert(("Usted no Tiene Acceso para Realizar esta Accion"));
	}
		
	return $objResponse;
}

//BOTON AGREGAR TEMPARIOS DESDE ORDEN, LISTADO DE TEMPARIO
function formListaTempario($valFormDcto) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $valFormDcto['txtIdEmpresa'];
	
	// VERIFICA VALORES DE CONFIGURACION //id tipo de orden que se pueden editar precio de tempario
	$queryConfigCambioPrecio = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 100
		AND config_emp.status = 1
		AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa,"int"));
	$rsConfigCambioPrecio = mysql_query($queryConfigCambioPrecio);
	if (!$rsConfigCambioPrecio) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowConfigCambioPrecio = mysql_fetch_assoc($rsConfigCambioPrecio);

	$objResponse->script("
	document.forms['frmBuscarTempario'].reset();
	document.forms['frmListadoTempario'].reset();
	document.forms['frmDatosTempario'].reset();
				  
	$('txtCodigoTemp').className = 'inputInicial';");
	
	$objResponse->loadCommands(cargaLstSeccionTemp());
	$objResponse->loadCommands(cargaLstSubseccionTemp());
	
	$query = "SELECT id_filtro_orden FROM sa_tipo_orden WHERE id_tipo_orden = ".$valFormDcto['lstTipoOrden']." LIMIT 1";
    $rs = mysql_query($query);
    if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
    $row = mysql_fetch_assoc($rs);
    $idFiltroOrden = $row["id_filtro_orden"];
	
	$objResponse->assign("tdDesbloquearPrecioTemp","innerHTML","");
	$arrayVal = explode("|",$rowConfigCambioPrecio['valor']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0 && $valor == $idFiltroOrden) {//antes == $valFormDcto['lstTipoOrden']
			$objResponse->assign("tdDesbloquearPrecioTemp","innerHTML","<img id=\"imgDesbloquearPrecioTemp\" src=\"../img/iconos/lock_go.png\" onclick=\"xajax_formValidarPermisoEdicion('sa_precio_editado_tempario');\" style=\"cursor:pointer\" title=\"Desbloquear\"/>");
		}
	}
	
	$objResponse->script("xajax_buscarTempario(xajax.getFormValues('frmBuscarPaquete'),xajax.getFormValues('frmTotalPresupuesto'));");
	
	$objResponse->script("
	$('tblPermiso').style.display = 'none';
	$('tblListados').style.display = 'none';
	$('tblGeneralPaquetes').style.display = 'none';
	$('tblListadoTempario').style.display = '';
	$('tblArticulo').style.display = 'none';
	$('tblNotas').style.display = 'none';
	$('tblListadoTot').style.display = 'none';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Mano de Obra");
	$objResponse->script("
	if ($('divFlotante').style.display == 'none') {
		$('divFlotante').style.display = '';
		centrarDiv($('divFlotante'));
	}
	$('txtCriterioTemp').focus();
	$('txtCriterioTemp').select();
	
	$('divFlotante2').style.display = 'none';");
		
	return $objResponse;
}

//LO USA PARA PERMISOS DE PRECIO, EDITAR PRECIO, PRECIO TEMPARIO, PRECIO DE ARTICULOS EDITAR, BAJAR
function formValidarPermisoEdicion($hddModulo) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	document.forms['frmPermiso'].reset();
	
	$('txtContrasena').className = 'inputInicial';");
	
	$objResponse->assign("hddModulo","value",$hddModulo);
	
	$objResponse->script("
	$('tblPermiso').style.display = '';
	$('tblListados').style.display = 'none';
	$('tblGeneralPaquetes').style.display = 'none';
	$('tblListadoTempario').style.display = 'none';
	$('tblArticulo').style.display = 'none';
	$('tblNotas').style.display = 'none';
	$('tblListadoTot').style.display = 'none';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Ingreso de Clave de Acceso");
	$objResponse->script("		
	$('divFlotante').style.display = '';
	centrarDiv($('divFlotante'));
	
	$('txtContrasena').focus();
	$('txtContrasena').select();");
	
	return $objResponse;
}

//GENERA LOS PRESUPUESTOS DE LA ORDEN, Y LAS ORDENES DE RETRABAJO, ojo orden de retrabajo
function generarDctoApartirDeOrden($valForm, $valFormTotalDcto) {
	$objResponse = new xajaxResponse();
	
	//return $objResponse->alert("ejecutando generarDctoApartirDeOrden"); //alerta
	
	if ($valFormTotalDcto['hddTipoDocumento'] == 4) { //PRESUPUESTO
		if (!xvalidaAcceso($objResponse,PAGE_PRIV,insertar)) {
			//$c->rollback();
			return $objResponse;
		}
		
		mysql_query("START TRANSACTION;");
		
		//numeracion presupuesto gregor
		
		$sqlNumeroOrden= sprintf("SELECT * FROM pg_empresa_numeracion
		WHERE id_numeracion = 34
			AND (id_empresa = %s OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																			WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC
		LIMIT 1",
		valTpDato($valForm['txtIdEmpresa'], "int"),
		valTpDato($valForm['txtIdEmpresa'], "int"));
		$rsSql = mysql_query($sqlNumeroOrden);
		if (!$rsSql) return $objResponse->alert(mysql_error()."\n Error buscando numero de presupuesto \n\nLine: ".__LINE__."\n\n File: ".__FILE__);
		$dtSql = mysql_fetch_assoc($rsSql);
		
		$idEmpresaNumeroPresupuesto = $dtSql["id_empresa_numeracion"];
		$numeroPresupuesto = $dtSql["numero_actual"];
		
		if($numeroPresupuesto == NULL){ return $objResponse->alert("No se pudo crear el numero de presupuesto, compruebe que la empresa tenga numeracion de presupuestos"); }
		
		//INSERCION DE PRESUPUESTO
		
		$insertSQLPresupuesto = sprintf("INSERT INTO sa_presupuesto (numero_presupuesto, fecha_presupuesto, id_orden, id_recepcion, id_tipo_orden, id_empresa, id_estado_orden, id_empleado, subtotal, porcentaje_descuento, subtotal_descuento, tiempo_orden, tiempo_inicio, tiempo_fin, prioridad, tiempo_promesa, id_puesto, id_piramide, id_usuario_bloqueo, id_mecanico_revision, id_empleado_aprobacion_factura, estado_presupuesto, tipo_presupuesto, idIva, iva, id_cliente, base_imponible, subtotal_iva, monto_exento, total_presupuesto) 
		SELECT 
			%s,
			NOW(),	
			%s,
			sa_orden.id_recepcion,
			sa_orden.id_tipo_orden,
			sa_orden.id_empresa,
			sa_orden.id_estado_orden,
			sa_orden.id_empleado,
			sa_orden.subtotal,
			sa_orden.porcentaje_descuento,
			sa_orden.subtotal_descuento,
			sa_orden.tiempo_orden,
			sa_orden.tiempo_inicio,
			sa_orden.tiempo_fin,
			sa_orden.prioridad,
			sa_orden.tiempo_promesa,
			sa_orden.id_puesto,
			sa_orden.id_piramide,
			sa_orden.id_usuario_bloqueo,
			sa_orden.id_mecanico_revision,
			sa_orden.id_empleado_aprobacion_factura,
			0,
			1,
			sa_orden.idIva,
			sa_orden.iva,
			%s,
			sa_orden.base_imponible,
			sa_orden.subtotal_iva,
			sa_orden.monto_exento,
			sa_orden.total_orden
		FROM sa_orden
		WHERE sa_orden.id_empresa = %s
			AND sa_orden.id_orden = %s",
			$numeroPresupuesto,
			valTpDato($valForm['txtIdPresupuesto'], "int"),
			valTpDato($valForm['txtIdCliente'], "int"),
			valTpDato($valForm['txtIdEmpresa'], "int"),
			valTpDato($valForm['txtIdPresupuesto'], "int"));
		mysql_query("SET NAMES 'utf8';");	
		$Result1InsertPresupuesto = mysql_query($insertSQLPresupuesto);
		if (!$Result1InsertPresupuesto) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$Result1InsertPresupuesto);
		$idPresupuesto = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
                //iva orden a iva presupuesto
                $insertIvaPresupuesto = sprintf("INSERT INTO sa_presupuesto_iva (id_presupuesto, base_imponible, subtotal_iva, id_iva, iva) 
		SELECT 
			%s,
                        sa_orden_iva.base_imponible,
                        sa_orden_iva.subtotal_iva,
                        sa_orden_iva.id_iva,
                        sa_orden_iva.iva                        
		FROM sa_orden_iva
		WHERE sa_orden_iva.id_orden = %s",
			valTpDato($idPresupuesto, "int"),//presup			
			valTpDato($valForm['txtIdPresupuesto'], "int"));//id orden		
		$rsIvaPresupuesto = mysql_query($insertIvaPresupuesto);
                if (!$rsIvaPresupuesto) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQyery: ".$insertIvaPresupuesto); }
		                
                
		// ACTUALIZA numeracion presupuesto
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeroPresupuesto, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
		//costo promedio presupuesto
			
		$insertSQLDetPresupuestoArticulo = sprintf("INSERT INTO sa_det_presup_articulo (
			id_presupuesto,
			id_articulo,
			id_paquete,
			cantidad,
			id_precio,
			precio_unitario,
			id_articulo_costo,
			id_articulo_almacen_costo,
			costo,
			id_iva,
			iva,
			aprobado,
			tiempo_asignacion,
			tiempo_aprobacion,
			id_empleado_aprobacion,
			estado_articulo,
			id_det_orden_articulo) 
		SELECT 
			%s,
			sa_det_orden_articulo.id_articulo,
			sa_det_orden_articulo.id_paquete,
			sa_det_orden_articulo.cantidad,
			sa_det_orden_articulo.id_precio,
			sa_det_orden_articulo.precio_unitario,
			sa_det_orden_articulo.id_articulo_costo,
			sa_det_orden_articulo.id_articulo_almacen_costo,
			sa_det_orden_articulo.costo,
			sa_det_orden_articulo.id_iva,
			sa_det_orden_articulo.iva,
			sa_det_orden_articulo.aprobado,
			sa_det_orden_articulo.tiempo_asignacion,
			sa_det_orden_articulo.tiempo_aprobacion,
			sa_det_orden_articulo.id_empleado_aprobacion,
			sa_det_orden_articulo.estado_articulo,
			sa_det_orden_articulo.id_det_orden_articulo
		FROM sa_orden
			INNER JOIN sa_det_orden_articulo ON (sa_orden.id_orden = sa_det_orden_articulo.id_orden)
		WHERE sa_orden.id_empresa = %s
			AND sa_orden.id_orden= %s
			AND sa_det_orden_articulo.aprobado = 1
			AND estado_articulo <> 'DEVUELTO'",
			$idPresupuesto,
			valTpDato($valForm['txtIdEmpresa'], "int"),
			valTpDato($valForm['txtIdPresupuesto'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1InsertDetPresupuestoArticulo = mysql_query($insertSQLDetPresupuestoArticulo);
		if (!$Result1InsertDetPresupuestoArticulo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$Result1InsertDetPresupuestoArticulo);
		mysql_query("SET NAMES 'latin1';");
		
                //iva articulos orden a presupuesto
                $insertIvaArticulosPresupuesto = sprintf("INSERT INTO sa_det_presup_articulo_iva (id_det_presup_articulo, base_imponible, subtotal_iva, id_iva, iva) 
		SELECT 
			sa_det_presup_articulo.id_det_presup_articulo,
                        sa_det_orden_articulo_iva.base_imponible,
                        sa_det_orden_articulo_iva.subtotal_iva,
                        sa_det_orden_articulo_iva.id_iva,
                        sa_det_orden_articulo_iva.iva                        
		FROM sa_det_presup_articulo
                INNER JOIN sa_det_orden_articulo_iva ON sa_det_presup_articulo.id_det_orden_articulo = sa_det_orden_articulo_iva.id_det_orden_articulo
		WHERE sa_det_presup_articulo.id_presupuesto = %s",
			valTpDato($idPresupuesto, "int"));		
		$rsIvaArticulosPresupuesto = mysql_query($insertIvaArticulosPresupuesto);
                if (!$rsIvaArticulosPresupuesto) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQyery: ".$insertIvaArticulosPresupuesto); }
		                
                
		//costo tempario orden presupuesto
		$insertSQLDetPresupuestoTempario = sprintf("INSERT INTO sa_det_presup_tempario (
			id_presupuesto,
			id_paquete,
			id_tempario,
			precio,
			costo,
			costo_orden,
			id_modo,
			base_ut_precio,
			operador,
			aprobado,
			ut,
			tiempo_aprobacion,
			tiempo_asignacion,
			tiempo_inicio,
			tiempo_fin,
			id_mecanico,
			id_empleado_aprobacion,
			origen_tempario,
			estado_tempario,
			precio_tempario_tipo_orden,
			id_det_orden_tempario)
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
			sa_det_orden_tempario.precio_tempario_tipo_orden,
			sa_det_orden_tempario.id_det_orden_tempario
		FROM sa_orden
			INNER JOIN sa_det_orden_tempario ON (sa_orden.id_orden = sa_det_orden_tempario.id_orden)
		WHERE sa_orden.id_empresa = %s
			AND sa_orden.id_orden= %s
			AND sa_det_orden_tempario.aprobado = 1
			AND estado_tempario <> 'DEVUELTO'",
			$idPresupuesto,
			valTpDato($valForm['txtIdEmpresa'], "int"),
			valTpDato($valForm['txtIdPresupuesto'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1InsertDetPresupuestoTempario = mysql_query($insertSQLDetPresupuestoTempario);
		if (!$Result1InsertDetPresupuestoTempario) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$Result1InsertDetPresupuestoTempario);
		mysql_query("SET NAMES 'latin1';");
	
		$insertSQLDetPresupuestoTot = sprintf("INSERT INTO sa_det_presup_tot (
			id_presupuesto,
			id_orden_tot,
			id_porcentaje_tot,
			porcentaje_tot,
			aprobado,
			id_det_orden_tot)
		SELECT 
			%s,
			sa_det_orden_tot.id_orden_tot,
			sa_det_orden_tot.id_porcentaje_tot,
			sa_det_orden_tot.porcentaje_tot,
			sa_det_orden_tot.aprobado,
			sa_det_orden_tot.id_det_orden_tot
		FROM sa_orden
			INNER JOIN sa_det_orden_tot ON (sa_orden.id_orden = sa_det_orden_tot.id_orden)
		WHERE sa_orden.id_empresa = %s
			AND sa_orden.id_orden= %s
			AND sa_det_orden_tot.aprobado = 1",
			$idPresupuesto,
			valTpDato($valForm['txtIdEmpresa'], "int"),
			valTpDato($valForm['txtIdPresupuesto'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1InsertDetPresupuestoTot = mysql_query($insertSQLDetPresupuestoTot);
		if (!$Result1InsertDetPresupuestoTot) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$Result1InsertDetPresupuestoTot);
		mysql_query("SET NAMES 'latin1';");
		
		$insertSQLDetPresupuestoNotas = sprintf("INSERT INTO sa_det_presup_notas (
			id_presupuesto,
			descripcion_nota,
			precio,
			aprobado,
			id_det_orden_nota)
		SELECT 
			%s,
			sa_det_orden_notas.descripcion_nota,
			sa_det_orden_notas.precio,
			sa_det_orden_notas.aprobado,
			sa_det_orden_notas.id_det_orden_nota
		FROM sa_orden
			INNER JOIN sa_det_orden_notas ON (sa_orden.id_orden = sa_det_orden_notas.id_orden)
		WHERE sa_orden.id_empresa = %s
			AND sa_orden.id_orden= %s
			AND sa_det_orden_notas.aprobado = 1",
			$idPresupuesto,
			valTpDato($valForm['txtIdEmpresa'], "int"),
			valTpDato($valForm['txtIdPresupuesto'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1InsertDetPresupuestoNotas = mysql_query($insertSQLDetPresupuestoNotas);
		if (!$Result1InsertDetPresupuestoNotas) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$Result1InsertDetPresupuestoNotas);
		mysql_query("SET NAMES 'latin1';");
		
		$insertSQLDetPrespuestoDescuento = sprintf("INSERT INTO sa_det_presup_descuento(
			id_presupuesto,
			id_porcentaje_descuento,
			porcentaje)
		SELECT 
			%s,
			sa_det_orden_descuento.id_porcentaje_descuento,
			sa_det_orden_descuento.porcentaje
		FROM sa_orden
			INNER JOIN sa_det_orden_descuento ON (sa_orden.id_orden = sa_det_orden_descuento.id_orden)
		WHERE sa_orden.id_empresa = %s
			AND sa_orden.id_orden= %s",
			$idPresupuesto,
			valTpDato($valForm['txtIdEmpresa'], "int"),
			valTpDato($valForm['txtIdPresupuesto'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1InsertDetPrespuestoDescuento = mysql_query($insertSQLDetPrespuestoDescuento);
		if (!$Result1InsertDetPrespuestoDescuento) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$Result1InsertDetPrespuestoDescuento);
		mysql_query("SET NAMES 'latin1';");
		
		$queryUpdateEdoOrden = sprintf("UPDATE sa_orden SET
			sa_orden.id_estado_orden = 3
		WHERE sa_orden.id_orden = %s",
			valTpDato($valForm['txtIdPresupuesto'], "int"));
		mysql_query("SET NAMES 'utf8';");		 
		$Result1UpdateEdoOrden = mysql_query($queryUpdateEdoOrden);
		if (!$Result1UpdateEdoOrden) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$Result1UpdateEdoOrden);
		mysql_query("SET NAMES 'latin1';");	
					
		$objResponse->script("
		bloquearForm();
		$('tdNroPresupuesto').style.display = '';
		$('tdNroPresupuesto').innerHTML = 'Nro. Presupuesto:';

		$('tdTxtNroPresupuesto').style.display = '';
		//$('btnImprimirDoc').style.display = '';//yano existe generara error si se usa
		");
			
		$objResponse->assign("txtNroPresupuesto","value", $idPresupuesto);	
		$objResponse->alert('El presupuesto ha sido Generado');
		
		mysql_query("COMMIT;");
		
		$queryEnvioCorreo = sprintf("SELECT cj_cc_cliente.correo
		FROM cj_cc_cliente
			INNER JOIN vw_sa_orden ON (cj_cc_cliente.id = vw_sa_orden.id_cliente_pago_orden)
		WHERE vw_sa_orden.id_orden = %s",
			valTpDato($valForm['txtIdPresupuesto'], "int"));
		$Result1 = mysql_query($queryEnvioCorreo);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$Result1);
		$row = mysql_fetch_assoc($Result1);
		
		if ($row ['correo'] != '') {
			//sendHtmlMail('adiazq@gmail.com', "ENVIO DE PRESUPUESTO DE SERVICIOS", "VERSION DE PRUEBA DEL CUERPO DE PRESUPUESTO");
		}
		
		$objResponse->script(sprintf("window.location.href = 'sa_formato_orden.php?id=%s&doc_type=1&acc=0'", $idPresupuesto));
	} else { // SINO ES "PRESUPUESTO" - este es el if principal
		$objResponse->alert("Esta opcion esta deshabilitada");
	}
		
	return $objResponse;
}

//GUARDA DOCUMENTO, CAMBIA CON RESPECTO AL FORM PRINCIPAL, GUARDA TODO Y PRESUPUESTO TAMBIEN Y ELIMINA LOS ITEMS SOBRANTES AUTOMATICAMENTE
//APRUEBA PRESUPUESTO, mas no lo genera, el que lo genera es guardardctoapartirdeorden()
function guardarDcto($valFormDcto, $valFormPaq, $valFormListaArt, $valFormListTemp, $valFormListNota, $valFormListTot, $valFormTotalDcto, $vaAmagnetoplano) {
	$objResponse = new xajaxResponse();
	
	$queryEmpresa = sprintf("SELECT paquete_combo FROM pg_empresa WHERE id_empresa = %s",
								valTpDato($valFormDcto['txtIdEmpresa'], "int"));
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowEmpresa = mysql_fetch_array($rsEmpresa);
	
	//$ar=fopen("./log/log_procesos.txt","a");

	//fputs($ar,"---------------------------| fecha: ".date("d-m-Y g:i:s")." | id_orden: ".$valFormDcto['txtIdPresupuesto']." | id_usuario: ".$_SESSION['idUsuarioSysGts']." |---------------------------\n");
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayVal = explode("|",$valFormTotalDcto['hddObjPaquete']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObjPaq[] = $valor;
	}
	
	$arrayVal = explode("|",$valFormTotalDcto['hddObj']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObj[] = $valor;
	}
	
	$arrayVal = explode("|",$valFormTotalDcto['hddObjTempario']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObjTemp[] = $valor;
	}
	
	$arrayVal = explode("|",$valFormTotalDcto['hddObjTot']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObjTot[] = $valor;
	}
	
	$arrayVal = explode("|",$valFormTotalDcto['hddObjNota']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObjNota[] = $valor;
	}
	
	$arrayVal = explode("|",$valFormTotalDcto['hddObjDescuento']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObjDcto[] = $valor;
	}
	
	mysql_query("START TRANSACTION;");
	
	if ($valFormDcto['txtIdPresupuesto'] > 0 && $_GET['acc_ret'] != 1) {
		if ($valFormTotalDcto['hddTipoDocumento'] == 1
		|| $valFormTotalDcto['hddTipoDocumento'] == 4) { // PRESUPUESTO - INSERTAR PRESUPUESTO A PARTIR DE LA ORDEN
			if (!xvalidaAcceso($objResponse,PAGE_PRIV)){
				return $objResponse;
			}
		}
		
		if ($valFormTotalDcto['hddTipoDocumento'] == 2) {
			if ($valFormTotalDcto['hddAccionTipoDocumento'] == 4) { // APROBACION
				if (!xvalidaAcceso($objResponse,PAGE_PRIV)){
					return $objResponse;
				}
			}
			
			if ($valFormTotalDcto['hddAccionTipoDocumento'] == 3) { // EDICION
				if (!xvalidaAcceso($objResponse,PAGE_PRIV,editar)){
					return $objResponse;
				}
			}
		}
		
		if ($valFormTotalDcto['hddTipoDocumento'] == 1) {
			$query = sprintf("SELECT sa_presupuesto.id_orden FROM sa_presupuesto
			WHERE sa_presupuesto.id_presupuesto = %s",
				$valFormDcto['txtIdPresupuesto']);
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$row = mysql_fetch_assoc($rs);
			
			$idDocumentoVenta = $row['id_orden'];
		} else {
			$idDocumentoVenta = $valFormDcto['txtIdPresupuesto'];
		}
		
		if (isset($arrayObjPaq)) {//PAQUETES ORDEN - PRESUPUESTO APROBACION
			foreach($arrayObjPaq as $indicePaq => $valorPaq) {
				if ($valFormPaq['hddIdPedDetPaq'.$valorPaq] != "") {
					$queryActualizarRepPaq = sprintf("UPDATE sa_det_orden_articulo SET
						aprobado = %s
					WHERE id_orden = %s
						AND id_paquete = %s
						AND estado_articulo <> 'DEVUELTO'",
						valTpDato($valFormPaq['hddValorCheckAprobPaq'.$valorPaq],"int"),
						valTpDato($idDocumentoVenta,"int"),
						valTpDato($valFormPaq['hddIdPaq'.$valorPaq],"int"));
					mysql_query("SET NAMES 'utf8';");
					$rsActualizarPaqRepAprob = mysql_query($queryActualizarRepPaq);
					if (!$rsActualizarPaqRepAprob) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					//fputs($ar,__LINE__."---> ".$queryActualizarRepPaq."\n");
						
					$queryActualizarTempPaq = sprintf("UPDATE sa_det_orden_tempario SET
						aprobado = %s
					WHERE id_orden = %s
						AND id_paquete = %s
						AND estado_tempario <> 'DEVUELTO'",
						valTpDato($valFormPaq['hddValorCheckAprobPaq'.$valorPaq],"int"),
						valTpDato($idDocumentoVenta,"int"),
						valTpDato($valFormPaq['hddIdPaq'.$valorPaq],"int"));
					mysql_query("SET NAMES 'utf8';");
					$rsActualizarPaqTempAprob = mysql_query($queryActualizarTempPaq);
					if (!$rsActualizarPaqTempAprob) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					//fputs($ar,__LINE__."---> ".$queryActualizarTempPaq."\n");
						
					if ($valFormTotalDcto['hddTipoDocumento'] == 1) {
						$queryActualizarRepPaq = sprintf("UPDATE sa_det_presup_articulo SET
							aprobado = %s
						WHERE id_presupuesto = %s
							AND id_paquete = %s
							AND estado_articulo <> 'DEVUELTO'",
								valTpDato($valFormPaq['hddValorCheckAprobPaq'.$valorPaq],"int"),
								valTpDato($valFormDcto['txtIdPresupuesto'],"int"),
								valTpDato($valFormPaq['hddIdPaq'.$valorPaq],"int"));
						mysql_query("SET NAMES 'utf8';");
						$rsActualizarPaqRepAprob = mysql_query($queryActualizarRepPaq);
						if (!$rsActualizarPaqRepAprob) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
						mysql_query("SET NAMES 'latin1';");
						//fputs($ar,__LINE__."---> ".$queryActualizarRepPaq."\n");
							
						$queryActualizarTempPaq = sprintf("UPDATE sa_det_presup_tempario SET
							aprobado = %s
						WHERE id_presupuesto = %s
							AND id_paquete = %s
							AND estado_tempario <> 'DEVUELTO'",
							valTpDato($valFormPaq['hddValorCheckAprobPaq'.$valorPaq],"int"),
							valTpDato($valFormDcto['txtIdPresupuesto'],"int"),
							valTpDato($valFormPaq['hddIdPaq'.$valorPaq],"int"));
						mysql_query("SET NAMES 'utf8';");
						$rsActualizarPaqTempAprob = mysql_query($queryActualizarTempPaq);
						if (!$rsActualizarPaqTempAprob) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
						mysql_query("SET NAMES 'latin1';");
						//fputs($ar,__LINE__."---> ".$queryActualizarTempPaq."\n");
					}
				}
			}
		}
		
		if (isset($arrayObj)) {//ARTICULOS ORDEN - PRESUPUESTO APROBACION
			foreach($arrayObj as $indice => $valor) {
				if ($valFormListaArt['hddIdPedDet'.$valor] != "") {
					$queryActualizarArt = sprintf("UPDATE sa_det_orden_articulo SET
						aprobado = %s
					WHERE id_det_orden_articulo = %s",
						valTpDato($valFormListaArt['hddValorCheckAprobRpto'.$valor], "int"),
						valTpDato($valFormListaArt['hddIdPedDet'.$valor], "int"));
					mysql_query("SET NAMES 'utf8';");
					$rsActualizarArt = mysql_query($queryActualizarArt);
					if (!$rsActualizarArt) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					//fputs($ar,__LINE__."---> ".$queryActualizarArt."\n");
					
					if ($valFormTotalDcto['hddTipoDocumento'] == 1) {
						$queryActualizarArt = sprintf("UPDATE sa_det_presup_articulo SET
							aprobado = %s
						WHERE id_det_orden_articulo = %s",
							valTpDato($valFormListaArt['hddValorCheckAprobRpto'.$valor], "int"),
							valTpDato($valFormListaArt['hddIdPedDet'.$valor], "int"));
						mysql_query("SET NAMES 'utf8';");	
						$rsActualizarArt = mysql_query($queryActualizarArt);
						if (!$rsActualizarArt) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
						mysql_query("SET NAMES 'latin1';");
						//fputs($ar,__LINE__."---> ".$queryActualizarArt."\n");
					}
				}
			}
		}
		
		if (isset($arrayObjTemp)) {//TEMPARIO ORDEN - PRESUPUESTO APROBACION
			foreach($arrayObjTemp as $indiceTemp => $valorTemp) {
				if ($valFormListTemp['hddIdPedDetTemp'.$valorTemp] != "") {
					$queryActualizarTemp = sprintf("UPDATE sa_det_orden_tempario SET
						aprobado = %s
					WHERE id_det_orden_tempario = %s",
							valTpDato($valFormListTemp['hddValorCheckAprobTemp'.$valorTemp], "int"), 
							valTpDato($valFormListTemp['hddIdPedDetTemp'.$valorTemp], "int"));
					mysql_query("SET NAMES 'utf8';");
					$rsActualizarTemp = mysql_query($queryActualizarTemp);
					if (!$rsActualizarTemp) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					//fputs($ar,__LINE__."---> ".$queryActualizarTemp."\n");
					
					if ($valFormTotalDcto['hddTipoDocumento'] == 1) {
						$queryActualizarTemp = sprintf("UPDATE sa_det_presup_tempario SET
							aprobado = %s
						WHERE id_det_orden_tempario = %s",
							valTpDato($valFormListTemp['hddValorCheckAprobTemp'.$valorTemp], "int"), 
							valTpDato($valFormListTemp['hddIdPedDetTemp'.$valorTemp], "int"));
						mysql_query("SET NAMES 'utf8';");
						$rsActualizarTemp = mysql_query($queryActualizarTemp);
						if (!$rsActualizarTemp) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
						mysql_query("SET NAMES 'latin1';");
						//fputs($ar,__LINE__."---> ".$queryActualizarTemp."\n");
					}
				}
			}
		}	
			
		if (isset($arrayObjTot)) {//TOT ORDEN - PRESUPUESTO APROBACION
			foreach($arrayObjTot as $indiceTot => $valorTot) {
				if ($valFormListTot['hddIdPedDetTot'.$valorTot] != "") {
					$queryActualizarTot = sprintf("UPDATE sa_det_orden_tot SET
						aprobado = %s
					WHERE id_det_orden_tot = %s",
						valTpDato($valFormListTot['hddValorCheckAprobTot'.$valorTot], "int"),
						valTpDato($valFormListTot['hddIdPedDetTot'.$valorTot], "int"));	
					//fputs($ar,__LINE__."---> ".$queryActualizarTot."\n");
					mysql_query("SET NAMES 'utf8';");
					$rsActualizarTot = mysql_query($queryActualizarTot);
					if (!$rsActualizarTot) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					
					if ($valFormTotalDcto['hddTipoDocumento'] == 1) {
						$queryActualizarTot = sprintf("UPDATE sa_det_presup_tot SET
							aprobado = %s
						WHERE id_det_orden_tot = %s",
							valTpDato($valFormListTot['hddValorCheckAprobTot'.$valorTot], "int"),
							valTpDato($valFormListTot['hddIdPedDetTot'.$valorTot], "int"));
						mysql_query("SET NAMES 'utf8';");	
						$rsActualizarTot = mysql_query($queryActualizarTot);
						if (!$rsActualizarTot) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
						mysql_query("SET NAMES 'latin1';");
						//fputs($ar,__LINE__."---> ".$queryActualizarTot."\n");
					}	
				}
			}
		}
				
		if (isset($arrayObjNota)) {//NOTAS ORDEN - PRESUPUESTO APROBACION
			foreach($arrayObjNota as $indiceNota => $valorNota) {
				if ($valFormListNota['hddIdPedDetNota'.$valorNota] != "") {
					$queryActualizarNota = sprintf("UPDATE sa_det_orden_notas SET
						aprobado = %s
					WHERE id_det_orden_nota = %s",
						valTpDato($valFormListNota['hddValorCheckAprobNota'.$valorNota],"int"),
						valTpDato($valFormListNota['hddIdPedDetNota'.$valorNota],"int"));
					mysql_query("SET NAMES 'utf8';");
					$rsActualizarNota = mysql_query($queryActualizarNota);
					if (!$rsActualizarNota) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					//fputs($ar,__LINE__."---> ".$queryActualizarNota."\n");
						
					if ($valFormTotalDcto['hddTipoDocumento'] == 1) {
						$queryActualizarNota = sprintf("UPDATE sa_det_presup_notas SET
							aprobado = %s
						WHERE id_det_orden_nota = %s",
							valTpDato($valFormListNota['hddValorCheckAprobNota'.$valorNota],"int"),
							valTpDato($valFormListNota['hddIdPedDetNota'.$valorNota],"int"));
						mysql_query("SET NAMES 'utf8';");
						$rsActualizarNota = mysql_query($queryActualizarNota);
						if (!$rsActualizarNota) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
						mysql_query("SET NAMES 'latin1';");
						//fputs($ar,__LINE__."---> ".$queryActualizarNota."\n");
					}
				}								
			}
		}
		
		
		// VERIFICA SI LOS PAQUETES ALMACENADOS EN LA BD DE LA ORDEN AUN ESTAN AGREGADOS EN EL FORMULARIO
		$queryPedidoDet = sprintf("SELECT DISTINCT 
			paq.id_paquete
		FROM sa_det_orden_articulo det_orden_art
			INNER JOIN sa_paquetes paq ON (det_orden_art.id_paquete = paq.id_paquete)
			INNER JOIN sa_orden orden ON (det_orden_art.id_orden = orden.id_orden)
			INNER JOIN sa_det_orden_tempario det_orden_temp ON (paq.id_paquete = det_orden_temp.id_paquete)
		WHERE orden.id_orden = %s
			AND det_orden_art.estado_articulo = 'PENDIENTE'
			AND det_orden_temp.estado_tempario = 'PENDIENTE';",
			valTpDato($idDocumentoVenta, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
                
		if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); 
		while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
			$idPaquete = $rowPedidoDet['id_paquete'];
			
			$existRegDet = false;
			if (isset($arrayObjPaq)) {
				foreach($arrayObjPaq as $indice => $valor) {
					if ($idPaquete == $valFormPaq['hddIdPaq'.$valor]) {
						$existRegDet = true;
						$valorItmPaq = $valor;
					}
				}
			}
			
			if ($existRegDet == false) {
                            
				$deleteSQL = sprintf("DELETE FROM sa_det_orden_articulo
				WHERE id_orden = %s
					AND id_paquete = %s
					AND estado_articulo = 'PENDIENTE';",
					valTpDato($idDocumentoVenta, "int"),
					valTpDato($idPaquete, "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				
				$deleteSQL = sprintf("DELETE FROM sa_det_orden_tempario
				WHERE id_orden = %s
					AND id_paquete = %s
					AND estado_tempario = 'PENDIENTE';",
					valTpDato($idDocumentoVenta, "int"),
					valTpDato($idPaquete, "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			} else {
                            
				// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
				$arrayVal = explode("|",$valFormPaq['hddRepPaqAsig'.$valorItmPaq]);
				foreach ($arrayVal as $indice => $valor) {
					if ($valor > 0)
						$arrayObjArtPaq[] = $valor;
				}
				
				// VERIFICA SI LOS ARTICULOS DEL PAQUETE ESTAN AGREGADOS EN EL FORMULARIO
				$queryArtPaq = sprintf("SELECT * FROM sa_det_orden_articulo
				WHERE id_orden = %s
					AND id_paquete = %s
					AND estado_articulo = 'PENDIENTE';",
					valTpDato($idDocumentoVenta, "int"),
					valTpDato($idPaquete, "int"));
				$rsArtPaq = mysql_query($queryArtPaq);
				if (!$rsArtPaq) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				while ($rowArtPaq = mysql_fetch_assoc($rsArtPaq)) {
					$existArtPaq = false;
					
					if (isset($arrayObjArtPaq)) {
						foreach($arrayObjArtPaq as $indice => $valor) {
							if ($rowArtPaq['id_articulo'] == $valor) {
								$existArtPaq = true;
							}
						}
					}
					
					if ($existArtPaq == false || $valFormPaq['hddIdPedDetPaq'.$valorItmPaq] == "") {
						$deleteSQL = sprintf("DELETE FROM sa_det_orden_articulo
						WHERE id_det_orden_articulo = %s
							AND estado_articulo = 'PENDIENTE';",
							valTpDato($rowArtPaq['id_det_orden_articulo'], "int"));
						$Result1 = mysql_query($deleteSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					}
				}
				
				
				// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
				$arrayVal = explode("|",$valFormPaq['hddTempPaqAsig'.$valorItmPaq]);
				foreach ($arrayVal as $indice => $valor) {
					if ($valor > 0)
						$arrayObjTempPaq[] = $valor;
				}				
				// VERIFICA SI LOS TEMPARIOS DEL PAQUETE ESTAN AGREGADOS EN EL FORMULARIO
				$queryTempPaq = sprintf("SELECT * FROM sa_det_orden_tempario
				WHERE id_orden = %s
					AND id_paquete = %s
					AND estado_tempario = 'PENDIENTE';",
					valTpDato($idDocumentoVenta, "int"),
					valTpDato($idPaquete, "int"));
				$rsTempPaq = mysql_query($queryTempPaq);
				if (!$rsTempPaq) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				while ($rowTempPaq = mysql_fetch_assoc($rsTempPaq)) {
					$existTempPaq = false;
					
					if (isset($arrayObjTempPaq)) {
						foreach($arrayObjTempPaq as $indice => $valor) {
							if ($rowTempPaq['id_tempario'] == $valor) {
								$existTempPaq = true;
							}
						}
					}
					
					if ($existTempPaq == false || $valFormPaq['hddIdPedDetPaq'.$valorItmPaq] == "") {
						$deleteSQL = sprintf("DELETE FROM sa_det_orden_tempario
						WHERE id_det_orden_tempario = %s
							AND estado_tempario = 'PENDIENTE';",
							valTpDato($rowTempPaq['id_det_orden_tempario'], "int"));
						$Result1 = mysql_query($deleteSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					}
				}
			}
		}
		
		
		
		// VERIFICA SI LOS ARTICULOS ALMACENADOS EN LA BD DE LA ORDEN AUN ESTAN AGREGADOS EN EL FORMULARIO
		$queryOrdenDetRep = sprintf("SELECT * FROM sa_det_orden_articulo
		WHERE id_orden = %s
			AND id_paquete IS NULL",
			valTpDato($idDocumentoVenta,"int"));
		$rsOrdenDetRep = mysql_query($queryOrdenDetRep);
		if (!$rsOrdenDetRep) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		while ($rowOrdenDetRep = mysql_fetch_assoc($rsOrdenDetRep)) {
			$existRegDet = false;
			
			if (isset($arrayObj)) {
				foreach($arrayObj as $indice => $valor) {
					if ($rowOrdenDetRep['id_det_orden_articulo'] == $valFormListaArt['hddIdPedDet'.$valor]) {
						$existRegDet = true;
					}
				}
			}
			
			if ($existRegDet == false) {
				if($rowOrdenDetRep['estado_articulo'] != 'DEVUELTO'){
					$deleteSQL = sprintf("DELETE FROM sa_det_orden_articulo WHERE id_det_orden_articulo = %s",
						valTpDato($rowOrdenDetRep['id_det_orden_articulo'], "int"));
					$Result1 = mysql_query($deleteSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					//fputs($ar,__LINE__."---> ".$deleteSQL."\n");
	
					$queryEliminarArticuloDePresupuesto = sprintf("DELETE FROM sa_det_presup_articulo 
					WHERE id_det_orden_articulo = %s
						AND id_presupuesto = %s",
						valTpDato($rowOrdenDetRep['id_det_orden_articulo'], "int"),
						valTpDato($valFormDcto['txtIdPresupuesto'], "int"));
					$Result1 = mysql_query($queryEliminarArticuloDePresupuesto);
					if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					//fputs($ar,__LINE__."---> ".$queryEliminarArticuloDePresupuesto."\n");
				}
			}
		}
		
		
		
		// VERIFICA SI LAS MANOS DE OBRA ALMACENADAS EN LA BD DE LA ORDEN AUN ESTAN AGREGADOS EN EL FORMULARIO
		$queryPresupuestoDet = sprintf("SELECT * FROM sa_det_orden_tempario
		WHERE id_orden = %s
			AND id_paquete IS NULL",
			valTpDato($idDocumentoVenta,"int"));
		$rsPresupuestoDet = mysql_query($queryPresupuestoDet);
		if (!$rsPresupuestoDet) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		while ($rowPresupuestoDet = mysql_fetch_assoc($rsPresupuestoDet)) {
			$existRegDet = false;
			
			if (isset($arrayObjTemp)) {
				foreach($arrayObjTemp as $indice => $valor) {
					if ($rowPresupuestoDet['id_det_orden_tempario'] == $valFormListTemp['hddIdPedDetTemp'.$valor]) {
						$existRegDet = true;
					}
				}
			}
		
			if ($existRegDet == false) {
				if($rowPresupuestoDet['estado_tempario'] != 'DEVUELTO') {
					$deleteSQL = sprintf("DELETE FROM sa_det_orden_tempario WHERE id_det_orden_tempario = %s",
						valTpDato($rowPresupuestoDet['id_det_orden_tempario'], "int"));
					$Result1 = mysql_query($deleteSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					//fputs($ar,__LINE__."---> ".$deleteSQL."\n");
					
					$queryEliminarTemparioDePresupuesto = sprintf("DELETE FROM sa_det_presup_tempario 
					WHERE id_det_orden_tempario = %s
						AND id_presupuesto= %s",
						valTpDato($rowPresupuestoDet['id_det_orden_tempario'], "int"),
						valTpDato($valFormDcto['txtIdPresupuesto'], "int"));
					$Result1 = mysql_query($queryEliminarTemparioDePresupuesto);
					if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					//fputs($ar,__LINE__."---> ".$queryEliminarTemparioDePresupuesto."\n");
				}
			}
		}
		
		
		
		// VERIFICA SI LOS TOT ALMACENADOS EN LA BD DE LA ORDEN AUN ESTAN AGREGADOS EN EL FORMULARIO
		$queryOrdenDetTot = sprintf("SELECT * FROM sa_det_orden_tot WHERE id_orden = %s", valTpDato($idDocumentoVenta,"int"));
		$rsOrdenDetTot = mysql_query($queryOrdenDetTot);
		if (!$rsOrdenDetTot) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		while ($rowOrdenTot = mysql_fetch_assoc($rsOrdenDetTot)) {
			$existRegDet = false;
			
			if (isset($arrayObjTot)) {
				foreach($arrayObjTot as $indice => $valor) {
					if ($rowOrdenTot['id_det_orden_tot'] == $valFormListTot['hddIdPedDetTot'.$valor]) {
						$existRegDet = true;
					}
				}
			}
			
			if ($existRegDet == false) {
				$updateTotSql= "UPDATE sa_orden_tot SET
					estatus = 1
				WHERE id_orden_tot = ".$rowOrdenTot['id_orden_tot'];
				$ResultUpdateTot= mysql_query($updateTotSql);
				if (!$ResultUpdateTot) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				//fputs($ar,__LINE__."---> ".$updateTotSql."\n");
	
				$deleteSQL = sprintf("DELETE FROM sa_det_orden_tot WHERE id_det_orden_tot = %s",
					valTpDato($rowOrdenTot['id_det_orden_tot'], "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				//fputs($ar,__LINE__."---> ".$deleteSQL."\n");
			}
		}
		
		
		
		// VERIFICA SI LAS NOTAS ALMACENADAS EN LA BD DE LA ORDEN AUN ESTAN AGREGADOS EN EL FORMULARIO
		$queryOrdenDet = sprintf("SELECT * FROM sa_det_orden_notas WHERE id_orden = %s",
			valTpDato($idDocumentoVenta,"int"));
		$rsOrdenDet = mysql_query($queryOrdenDet);
		if (!$rsOrdenDet) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		while ($rowOrdenDet = mysql_fetch_assoc($rsOrdenDet)) {
			$existRegDet = false;
			
			if (isset($arrayObjNota)) {
				foreach($arrayObjNota as $indice => $valor) {
					if ($rowOrdenDet['id_det_orden_nota'] == $valFormListNota['hddIdPedDetNota'.$valor]) {
						$existRegDet = true;
					}
				}
			}
			
			if ($existRegDet == false) {
				$deleteSQL = sprintf("DELETE FROM sa_det_orden_notas
				WHERE id_det_orden_nota = %s",
					valTpDato($rowOrdenDet['id_det_orden_nota'], "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				//fputs($ar,__LINE__."---> ".$deleteSQL."\n");
				
				$queryEliminarNotaDePresupuesto = sprintf("DELETE FROM sa_det_presup_notas
				WHERE id_det_orden_nota = %s
					AND id_presupuesto = %s",
					valTpDato($rowOrdenDet['id_det_orden_nota'], "int"),
					valTpDato($valFormDcto['txtIdPresupuesto'], "int"));
				$Result1 = mysql_query($queryEliminarNotaDePresupuesto);
				if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				//fputs($ar,__LINE__."---> ".$queryEliminarNotaDePresupuesto."\n");
			}
		}
		
		
		
		// VERIFICA SI LOS DESCUENTOS ALMACENADAS EN LA BD DE LA ORDEN AUN ESTAN AGREGADOS EN EL FORMULARIO
		$queryOrdenDetDcto = sprintf("SELECT * FROM sa_det_orden_descuento WHERE id_orden = %s",
			valTpDato($idDocumentoVenta,"int"));
		$rsOrdenDetDcto = mysql_query($queryOrdenDetDcto);
		if (!$rsOrdenDetDcto) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		while ($rowOrdenDetDcto = mysql_fetch_assoc($rsOrdenDetDcto)) {
			$existRegDet = false;
			
			if (isset($arrayObjDcto)) {
				foreach($arrayObjDcto as $indice => $valor) {
					if ($rowOrdenDetDcto['id_det_orden_descuento'] == $valFormTotalDcto['hddIdDetDcto'.$valor]) {
						$existRegDet = true;
					}
				}
			}
			
			if ($existRegDet == false) {
				$deleteSQL = sprintf("DELETE FROM sa_det_orden_descuento
				WHERE id_det_orden_descuento = %s
					AND id_presupuesto = %s",
					valTpDato($rowOrdenDetDcto['id_det_orden_descuento'], "int"),
					valTpDato($valFormDcto['txtIdPresupuesto'], "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				//fputs($ar,__LINE__."---> ".$deleteSQL."\n");
			}
		}
		
		////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
		$queryOrdenPaq = sprintf("SELECT id_paquete FROM sa_det_orden_tempario
		WHERE id_orden = %s
			AND id_paquete IS NOT NULL
		GROUP BY id_paquete",
			valTpDato($idDocumentoVenta,"int"));
		$rsOrdenPaq = mysql_query($queryOrdenPaq);
		if (!$rsOrdenPaq) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		while ($rowOrdenPaq = mysql_fetch_assoc($rsOrdenPaq)) { 
			$queryOrdenPaqTemp = sprintf("SELECT
				id_det_orden_tempario,
				id_tempario,
				estado_tempario
			FROM sa_det_orden_tempario
			WHERE id_orden = %s
				AND id_paquete = %s",
				valTpDato($idDocumentoVenta,"int"),
				valTpDato($rowOrdenPaq["id_paquete"],"int"));
			$rsOrdenPaqTemp = mysql_query($queryOrdenPaqTemp);
			if (!$rsOrdenPaqTemp) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$existRegDet = false;
	
			while ($rowOrdenPaqTemp = mysql_fetch_array($rsOrdenPaqTemp)) { 
				// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
				$arrayVal = explode("|",$valFormPaq['hddTempPaqAsigEdit'.$rowOrdenPaq["id_paquete"]]);
				foreach ($arrayVal as $indice => $valor) {
					if ($valor > 0)
						$arrayObjTempPaq[] = $valor;
				}
						
				if (isset($arrayObjTempPaq)) {
					foreach($arrayObjTempPaq as $indice => $valor) {
						if ($rowOrdenPaqTemp['id_tempario'] == $valor) {
							$existRegDet = true;
						}
					}
				}
					
				if ($existRegDet == false) {
					if ($rowOrdenPaqTemp['estado_tempario'] != 'DEVUELTO') {
						$deleteSQL = sprintf("DELETE FROM sa_det_orden_tempario
						WHERE id_det_orden_tempario = %s;",
							valTpDato($rowOrdenPaqTemp['id_det_orden_tempario'], "int"));
						$Result1 = mysql_query($deleteSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
						//fputs($ar,__LINE__."---> ".$deleteSQL."\n");
					}
				}		
				$arrayObjTempPaq = "";
			}
		}
	
		$queryOrdenRepPaq = sprintf("SELECT 
			id_paquete
		FROM sa_det_orden_articulo
		WHERE id_orden = %s
			AND id_paquete IS NOT NULL
		GROUP BY id_paquete",
			valTpDato($idDocumentoVenta,"int"));
		$rsOrdenRepPaq = mysql_query($queryOrdenRepPaq);
		if (!$rsOrdenRepPaq) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		while ($rowOrdenRepPaq = mysql_fetch_array($rsOrdenRepPaq)) {
			$queryOrdenPaqRep = sprintf("SELECT 
				id_det_orden_articulo,
				id_articulo,
				estado_articulo
			FROM sa_det_orden_articulo
			WHERE id_orden = %s
				AND id_paquete = %s;",
				valTpDato($idDocumentoVenta,"int"),
				valTpDato($rowOrdenRepPaq["id_paquete"],"int"));
			$rsOrdenPaqRep = mysql_query($queryOrdenPaqRep);
			if (!$rsOrdenPaqRep) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			
			$existRegDetRep = false;
	
			while ($rowOrdenPaqRep = mysql_fetch_array($rsOrdenPaqRep)) {
				// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
				$arrayVal = explode("|",$valFormPaq['hddRepPaqAsigEdit'.$rowOrdenRepPaq["id_paquete"]]);
				foreach ($arrayVal as $indice => $valor) {
					if ($valor > 0)
						$arrayObjRepPaq[] = $valor;
				}
			
				if (isset($arrayObjRepPaq)) {
					foreach($arrayObjRepPaq as $indiceRep => $valorRep) {
						if ($rowOrdenPaqRep['id_articulo'] == $valorRep) { 
							$existRegDetRep = true;
						}
					}
				}
				
				if ($existRegDetRep == false) {
					if($rowOrdenPaqRep['estado_articulo'] != 'DEVUELTO') {
						$deleteSQLRep = sprintf("DELETE FROM sa_det_orden_articulo WHERE id_det_orden_articulo = %s",
							valTpDato($rowOrdenPaqRep['id_det_orden_articulo'], "int"));
						$Result1 = mysql_query($deleteSQLRep);
						if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
						//fputs($ar,__LINE__."---> ".$deleteSQLRep."\n");
					}
				}	
				$arrayObjRepPaq = "";
			}
		}
                
                //borro temparios individual paquete orden - presupuesto
                $arrayTempPaqEliminar = explode("|", $valFormPaq["temparioPaqueteEliminar"]);//siempre envia "|int|int";
                array_shift($arrayTempPaqEliminar);//elimina el primero que siempre esta vacio
                $idDetOrdenTempPaqEliminar = implode(",",$arrayTempPaqEliminar);
                
                if($idDetOrdenTempPaqEliminar != ""){
                    $query = sprintf("DELETE FROM sa_det_orden_tempario WHERE id_det_orden_tempario IN (%s) AND id_paquete IS NOT NULL",
                                        $idDetOrdenTempPaqEliminar);//IN ()
                    $rs = mysql_query($query);
                    if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query); }
                }
		
		$queryLaOrdenEsSinAsignar = sprintf("SELECT id_tipo_orden FROM sa_orden
		WHERE id_orden = %s",
			valTpDato($idDocumentoVenta, "int"));
		$rsLaOrdenEsSinAsignar = mysql_query($queryLaOrdenEsSinAsignar);
		if (!$rsLaOrdenEsSinAsignar) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowLaOrdenEsSinAsignar = mysql_fetch_assoc($rsLaOrdenEsSinAsignar);
			
//		if ($rowLaOrdenEsSinAsignar['id_tipo_orden'] == 5) {//no se usa
//			$laOrdenEsTipoSinAsignar = 1;
//		} else {
//			$laOrdenEsTipoSinAsignar = 0;
//		}
							
		if ($valFormTotalDcto['hddSeleccionActivo'] == 1) {//comentado tipo activo en cero ya no va
			$valFormTotalDcto['txtSubTotal'] = 0.00;
			$valFormTotalDcto['txtIvaVenta'] = 0.00;
		}
                
                $totalSumatoriaIvas = 0;                        
                foreach($valFormTotalDcto["ivaActivo"] as $key => $idIvas){
                    $totalSumatoriaIvas += str_replace(",","",$valFormTotalDcto["txtTotalIva".$idIvas]);
                }
				
		//del id array de ivas, busco el primero de todos los ivas, lo coloco para averiguar base imponible y total iva
		if($totalSumatoriaIvas > 0){
			$idIvaSimple = array_shift(array_values($valFormTotalDcto["ivaActivo"]));
			$porcentajeIvaSimple = $valFormTotalDcto["txtIvaVenta".$idIvaSimple];
			$baseImponibleSimple = $valFormTotalDcto["txtBaseImponibleIva".$idIvaSimple];
		}
						
		$query = sprintf("UPDATE sa_orden SET
			porcentaje_descuento = %s,
			subtotal_descuento = %s,
			idIva = %s,
			iva = %s,
			subtotal = %s,
			id_tipo_orden= %s,
			base_imponible = %s, 
			subtotal_iva = %s,
			monto_exento = %s,
			total_orden = %s
		WHERE sa_orden.id_orden = %s",
			valTpDato($valFormTotalDcto['txtDescuento'], "real_inglesa"),
			valTpDato($valFormTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
			valTpDato($idIvaSimple, "int"),//solo simple impuestos
			valTpDato($porcentajeIvaSimple, "real_inglesa"),//solo simple impuestos
			valTpDato($valFormTotalDcto['txtSubTotal'], "real_inglesa"),
			valTpDato($valFormDcto['lstTipoOrden'], "int"),
			valTpDato($baseImponibleSimple, "real_inglesa"),//solo simple impuestos
			valTpDato($totalSumatoriaIvas, "real_inglesa"),//El total subivas
                        valTpDato($valFormTotalDcto['txtMontoExento'], "real_inglesa"),//nuevo
                        valTpDato($valFormTotalDcto['txtTotalPresupuesto'], "real_inglesa"),//nuevo
			valTpDato($idDocumentoVenta,"int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($query);
                if (!$Result1) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query); }
		mysql_query("SET NAMES 'latin1';");	
		//fputs($ar,__LINE__."---> ".$query."\n");
                
                foreach($valFormTotalDcto["ivaActivo"] as $key => $idIvas){
                                        
                    //INSERTO POR SI HAY UNO NUEVO Y CON MONTO NO ES CERO
                    if($valFormTotalDcto["txtBaseImponibleIva".$idIvas] != 0 && $valFormTotalDcto["txtTotalIva".$idIvas] != 0 && $valFormTotalDcto["txtIvaVenta".$idIvas] != 0){
                        
                        $ivasSql = sprintf("SELECT * FROM sa_orden_iva 
                                        WHERE id_orden = %s AND id_iva = %s LIMIT 1",
                                    valTpDato($idDocumentoVenta,"int"),
                                    valTpDato($idIvas,"int")
                                );
                        $rsIvas = mysql_query($ivasSql);
                        if (!$rsIvas) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$ivasSql); }
                        $rowExisteElIva = mysql_num_rows($rsIvas);
                        
                        if($rowExisteElIva){//SI EXISTE ACTUALIZO POR SI HUBO CAMBIOS
                            
                            $ivasSql = sprintf("UPDATE sa_orden_iva 
                                                SET base_imponible = %s, subtotal_iva = %s, iva = %s
                                                WHERE id_orden = %s AND id_iva = %s
                                                ",                                    
                                            valTpDato($valFormTotalDcto["txtBaseImponibleIva".$idIvas],"real_inglesa"),
                                            valTpDato($valFormTotalDcto["txtTotalIva".$idIvas],"real_inglesa"),                                    
                                            valTpDato($valFormTotalDcto["txtIvaVenta".$idIvas],"real_inglesa"),
                                            valTpDato($idDocumentoVenta,"int"),
                                            valTpDato($idIvas,"int")
                                        );
                            $rsIvas = mysql_query($ivasSql);
                            if (!$rsIvas) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$ivasSql); }                    
                            
                        }else{//SINO EXISTE LO GUARDO
                            $ivasSql = sprintf("INSERT INTO sa_orden_iva (id_orden, base_imponible, subtotal_iva, id_iva, iva) 
                                                VALUES (%s, %s, %s, %s, %s)",
                                        valTpDato($idDocumentoVenta,"int"),
                                        valTpDato($valFormTotalDcto["txtBaseImponibleIva".$idIvas],"real_inglesa"),
                                        valTpDato($valFormTotalDcto["txtTotalIva".$idIvas],"real_inglesa"),
                                        valTpDato($idIvas,"int"),
                                        valTpDato($valFormTotalDcto["txtIvaVenta".$idIvas],"real_inglesa")
                                    );
                            $rsIvas = mysql_query($ivasSql);
                            if (!$rsIvas) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$ivasSql); }
                        }

                    }else{//SI ESTA EN CERO, NO NECESITA ESE IVA, ELIMINAR
                        $ivasSql = sprintf("DELETE FROM sa_orden_iva 
                                            WHERE id_orden = %s AND id_iva = %s",
                                    valTpDato($idDocumentoVenta,"int"),
                                    valTpDato($idIvas,"int")
                                    );
                        $rsIvas = mysql_query($ivasSql);
                        if (!$rsIvas) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$ivasSql); }
                    }
                }
                
		
		if ($valFormTotalDcto['hddTipoDocumento'] == 1 && $valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
			$query = sprintf("UPDATE sa_presupuesto SET
				estado_presupuesto = 1
			WHERE id_presupuesto = %s",
				valTpDato($valFormDcto['txtIdPresupuesto'], "int"));
			mysql_query("SET NAMES 'utf8';");	
			$Result1 = mysql_query($query);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			//fputs($ar,__LINE__."---> ".$query."\n");
		}
		//FIN ACTUALIZADO Y ELIMINADO DE ORDEN-PRESUPUESTO
	} else {//INICIO DE INSERTAR ORDEN - RETRABAJO
		if (!xvalidaAcceso($objResponse,PAGE_PRIV,insertar)){
			//$c->rollback();
			return $objResponse;
		}
		
		$buscarParametroFechaVencimiento = sprintf("SELECT valor_parametro FROM pg_parametros_empresas
		WHERE descripcion_parametro = 'DIAS DE VENCIMIENTO DEL PRESUPUESTO VENTA SERVICIO'
			AND id_empresa = %s",
			valTpDato($valFormDcto['txtIdEmpresa'], "int"));
		$Result1 = mysql_query($buscarParametroFechaVencimiento);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$parametro_venc = mysql_fetch_array($Result1);
		$fecha_vencimiento = dateadd($valFormDcto['txtFechaPresupuesto'],$parametro_venc['valor_parametro'],0,0);			
			
		if ($valFormTotalDcto['hddTipoDocumento'] == 1) { // PRESUPUESTO
	
		} else { // ORDEN DE SERVICIO
			if ($_GET['ret'] == 5) {
				$sqlOrdenRetrabajo = "SELECT sa_tipo_orden.id_tipo_orden FROM sa_tipo_orden
				WHERE sa_tipo_orden.orden_retrabajo = 1 AND id_empresa = ".valTpDato($valFormDcto['txtIdEmpresa'], "int")." LIMIT 1";
				$rs = mysql_query($sqlOrdenRetrabajo);
                                if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				$row = mysql_fetch_assoc($rs);
				
				$id_tipo_orden = $row['id_tipo_orden'];
                                if($id_tipo_orden == ""){
                                    return $objResponse->alert("No hay un tipo de orden predeterminado para retrabajos");
                                }
                                
			} else {
				$id_tipo_orden = $valFormDcto['lstTipoOrden'];
			}
			
			if ($valFormTotalDcto['hddSeleccionActivo'] == 1) {
				$valFormTotalDcto['txtSubTotal'] = 0.00;
				$valFormTotalDcto['txtIvaVenta'] = 0.00;
				$valFormTotalDcto['txtTotalIva'] = 0.00;
			}
			
                        $totalSumatoriaIvas = 0;                        
                        foreach($valFormTotalDcto["ivaActivo"] as $key => $idIvas){
                            $totalSumatoriaIvas += str_replace(",","",$valFormTotalDcto["txtTotalIva".$idIvas]);
                        }
                        
			
			$sqlNumeroOrden= sprintf("SELECT * FROM pg_empresa_numeracion
			WHERE id_numeracion = 33
				AND (id_empresa = %s OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																				WHERE suc.id_empresa = %s)))
			ORDER BY aplica_sucursales DESC
			LIMIT 1",
			valTpDato($valFormDcto['txtIdEmpresa'], "int"),
			valTpDato($valFormDcto['txtIdEmpresa'], "int"));
			$rsSql = mysql_query($sqlNumeroOrden);
			if (!$rsSql) return $objResponse->alert(mysql_error()."\n Error buscando numero de orden guardarDcto \n\nLine: ".__LINE__."\n\n File: ".__FILE__);
			$dtSql = mysql_fetch_assoc($rsSql);
			
			$idEmpresaNumeroOrden = $dtSql["id_empresa_numeracion"];
			$numeroOrden = $dtSql["numero_actual"];
			
			if($numeroOrden == NULL){ return $objResponse->alert("No se pudo crear el numero de orden, compruebe que la empresa tenga numeracion de ordenes"); }
			
			//del id array de ivas, busco el primero de todos los ivas, lo coloco para averiguar base imponible y total iva
			if($totalSumatoriaIvas > 0){
				$idIvaSimple = array_shift(array_values($valFormTotalDcto["ivaActivo"]));
				$porcentajeIvaSimple = $valFormTotalDcto["txtIvaVenta".$idIvaSimple];
				$baseImponibleSimple = $valFormTotalDcto["txtBaseImponibleIva".$idIvaSimple];
			}else{
				$baseImponibleSimple = 0;
			}
			
			$insertSQL = sprintf("INSERT INTO sa_orden (numero_orden,id_recepcion, id_tipo_orden, id_empresa, id_empleado, id_cliente, tiempo_orden, id_estado_orden, porcentaje_descuento, subtotal_descuento, idIva, iva, subtotal, base_imponible, subtotal_iva, monto_exento, total_orden)
			VALUE (%s, %s, %s, %s, %s, %s, NOW(), %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				$numeroOrden,
				valTpDato($valFormDcto['txtIdValeRecepcion'], "int"),
				valTpDato($id_tipo_orden, "int"),
				valTpDato($valFormDcto['txtIdEmpresa'], "int"),
				valTpDato($valFormDcto['hddIdEmpleado'], "int"),
				valTpDato($valFormDcto['txtIdCliente'], "int"),
				valTpDato(1, "int"), // 1 = Abierta
				valTpDato($valFormTotalDcto['txtDescuento'], "real_inglesa"),
				valTpDato($valFormTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
				valTpDato($idIvaSimple, "int"),//solo simple impuestos
				valTpDato($porcentajeIvaSimple, "real_inglesa"),//solo simple impuestos
				valTpDato($valFormTotalDcto['txtSubTotal'], "real_inglesa"),
				valTpDato($baseImponibleSimple, "real_inglesa"),//solo simple impuestos                               
				valTpDato($totalSumatoriaIvas, "real_inglesa"),//El total subivas
                                valTpDato($valFormTotalDcto['txtMontoExento'], "real_inglesa"),//nuevo
                                valTpDato($valFormTotalDcto['txtTotalPresupuesto'], "real_inglesa")//nuevo
                                );		
			mysql_query("SET NAMES 'utf8';");	
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$idDocumentoVenta = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			//fputs($ar,__LINE__."---> ".$insertSQL."\n");
			
			
			// ACTUALIZA numeracion orden
			$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
			WHERE id_empresa_numeracion = %s;",
				valTpDato($idEmpresaNumeroOrden, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			
			foreach($valFormTotalDcto["ivaActivo"] as $key => $idIvas){
                            if($valFormTotalDcto["txtBaseImponibleIva".$idIvas] != 0 && $valFormTotalDcto["txtTotalIva".$idIvas] != 0 && $valFormTotalDcto["txtIvaVenta".$idIvas] != 0){
                                $ivasSql = sprintf("INSERT INTO sa_orden_iva (id_orden, base_imponible, subtotal_iva, id_iva, iva) 
                                                    VALUES (%s, %s, %s, %s, %s)",
                                            valTpDato($idDocumentoVenta,"int"),
                                            valTpDato($valFormTotalDcto["txtBaseImponibleIva".$idIvas],"real_inglesa"),
                                            valTpDato($valFormTotalDcto["txtTotalIva".$idIvas],"real_inglesa"),
                                            valTpDato($idIvas,"int"),
                                            valTpDato($valFormTotalDcto["txtIvaVenta".$idIvas],"real_inglesa")
                                        );
                                $rsIvas = mysql_query($ivasSql);
                                if (!$rsIvas) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$ivasSql); }
                                
                            }
                        }
                        
                        
			if ($_GET['ret'] == 5) {
                            if($valFormTotalDcto['txtMotivoRetrabajo'] == ""){
                                return $objResponse->alert("Debe escribir el motivo de retrabajo");
                            }
				$insertSQL = sprintf("INSERT INTO sa_retrabajo_orden(id_orden, fecha_retrabajo, motivo_retrabajo, id_orden_retrabajo, id_usuario)
				VALUE (%s, NOW(), %s, %s, %s);",
					valTpDato($idDocumentoVenta, "text"),
					valTpDato($valFormTotalDcto['txtMotivoRetrabajo'], "text"),
					valTpDato($valFormDcto['txtIdPresupuesto'], "int"),
					valTpDato($valFormDcto['hddIdEmpleado'], "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert("Error isrt retrabajo orden: ".mysql_error()."\n sql: ".$Result1."\n Linea:".__LINE__);
				mysql_query("SET NAMES 'latin1';");
				//fputs($ar,$insertSQL."\n");
			}
		}
	}//FIN INSERTAR ORDEN-RETRABAJO
	
	
	//INSERTAR ORDEN - 	SOLICITUD REPUESTOS
	$queryTipoOrden = sprintf("SELECT 
		id_tipo_orden,
		precio_tempario,
		costo_tempario
	FROM sa_tipo_orden
	WHERE id_tipo_orden = %s",
		valTpDato($valFormDcto['lstTipoOrden'], "int"));
	$rsTipoOrden = mysql_query($queryTipoOrden);
	if (!$rsTipoOrden) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowTipoOrden = mysql_fetch_assoc($rsTipoOrden);
	
	$costoTemparioOrden = $rowTipoOrden["costo_tempario"]; // busco costo tempario orden
	
	// MANOS DE OBRA AGREGADAS
	if (isset($arrayObjTemp)) {
		foreach($arrayObjTemp as $indiceTemp => $valorTemp) {
			if (strlen($valFormListTemp['hddIdTemp'.$valorTemp]) > 0) {
				if ($valFormListTemp['hddIdPedDetTemp'.$valorTemp] == "") {
					if ($valFormTotalDcto['hddSeleccionActivo'] == 1) {
						$valorUt = 0.00;
					} else {
						$valorUt = "vw_sa_temparios_por_unidad.base_ut";
					}
				
					// guardado de temparios nuevos primera vez, costo tempario orden guardar	
					$insertSQL = sprintf("INSERT INTO sa_det_orden_tempario (id_orden, id_tempario, precio, costo, costo_orden, id_modo,  base_ut_precio, operador, ut, tiempo_asignacion, id_mecanico, id_empleado_aprobacion, aprobado, precio_tempario_tipo_orden, origen_tempario)
					SELECT 
						".$idDocumentoVenta.",
						vw_sa_temparios_por_unidad.id_tempario,
						".$valFormListTemp['hddPrecTemp'.$valorTemp].",
						vw_sa_temparios_por_unidad.costo,
						".$costoTemparioOrden.",
						".$valFormListTemp['hddIdModo'.$valorTemp].",
						".$valorUt.",
						vw_sa_temparios_por_unidad.operador,
						vw_sa_temparios_por_unidad.ut,
						NOW(),
						%s,
						%s,
						%s,
						%s,
						%s
					FROM vw_sa_temparios_por_unidad
					WHERE vw_sa_temparios_por_unidad.id_tempario_det = %s",
						valTpDato($valFormListTemp['hddIdMec'.$valorTemp], "int"),
						valTpDato($valFormDcto['hddIdEmpleado'], "int"),
						valTpDato($valFormListTemp['hddValorCheckAprobTemp'.$valorTemp], "int"),
						$rowTipoOrden['precio_tempario'],
						valTpDato($valFormListTemp['hddIdOrigen'.$valorTemp], "int"),
						valTpDato($valFormListTemp['hddDetTemp'.$valorTemp], "int"));		
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					$idDocumentoDetalle = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					//fputs($ar,__LINE__."---> ".$insertSQL."\n");
												
					$objResponse->assign("hddIdPedDetTemp".$valorTemp,"value",$idDocumentoDetalle);	
				}
			}
		}
	}
		
	$new_article = 0;
	$new_package = 0;
	
	if ($valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
		if (isset($arrayObjPaq)) {
			foreach($arrayObjPaq as $indicePaq => $valorPaq) {
				if (strlen($valFormPaq['hddIdPaq'.$valorPaq]) > 0) {
					if ($valFormPaq['hddRptoPaqEnSolicitud'.$valorPaq] == "" and $valFormPaq['hddValorCheckAprobPaq'.$valorPaq] == 1) {
						if($valFormPaq['hddRepPaqAsig'.$valorPaq] != "") {//valido que solo cree solicitud de repuesto si el paquete tiene repuestos
							$new_package = 1;	
						} 						
					}
				}
			}
		}
		
		if (isset($arrayObj)) {
			foreach($arrayObj as $indice => $valor) {
				if (strlen($valFormListaArt['hddIdArt'.$valor]) > 0) {
					if ($valFormListaArt['hddRptoEnSolicitud'.$valor] == "" and $valFormListaArt['hddValorCheckAprobRpto'.$valor] == 1) {	
						$new_article = 1;
					}
				}
			}
		}	
	}
	
	$varIngresoEncabezadoSolicitud = 0;
	if ($new_article == 1 || $new_package == 1) {
		
		//numeracion solicitud de repuestos surtido taller gregor
		
		$sqlNumeroSolicitud= sprintf("SELECT * FROM pg_empresa_numeracion
		WHERE id_numeracion = 37
			AND (id_empresa = %s OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																			WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC
		LIMIT 1",
		valTpDato($valFormDcto['txtIdEmpresa'], "int"),
		valTpDato($valFormDcto['txtIdEmpresa'], "int"));
		$rsSql = mysql_query($sqlNumeroSolicitud);
		if (!$rsSql) return $objResponse->alert(mysql_error()."\n Error buscando numero de Solicitud \n\nLine: ".__LINE__."\n\n File: ".__FILE__);
		$dtSql = mysql_fetch_assoc($rsSql);
		
		$idEmpresaNumeroSolicitud = $dtSql["id_empresa_numeracion"];
		$numeroSolicitud = $dtSql["numero_actual"];
		
		if($numeroSolicitud == NULL){ return $objResponse->alert("No se pudo crear el numero de solicitud de repuestos, compruebe que la empresa tenga numeracion de solicitudes de repuestos"); }
		
		$insertSQL = sprintf("INSERT INTO sa_solicitud_repuestos (numero_solicitud, id_orden, estado_solicitud, tiempo_solicitud, id_empresa)
		VALUE (%s, %s, 0, NOW(), %s);",
			$numeroSolicitud,
			valTpDato($idDocumentoVenta, "int"),
			valTpDato($valFormDcto['txtIdEmpresa'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$idSolicitud = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		//fputs($ar,__LINE__."---> ".$insertSQL."\n");
		
		// ACTUALIZA numeracion solicitud de repuestos surtido taller
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeroSolicitud, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }


		$varIngresoEncabezadoSolicitud = 1;
	}
	
	// ARTICULOS AGREGADOS
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			if (strlen($valFormListaArt['hddIdArt'.$valor]) > 0) {
				if ($valFormListaArt['hddIdPedDet'.$valor] == "") {
					if ($valFormTotalDcto['hddSeleccionActivo'] == 1) {
						$valFormListaArt['hddPrecioArt'.$valor] = 0.00;
					}
					
					//guardado de articulos primera vez articulo, guardar orden costo promedio
					
					$insertSQL = sprintf("INSERT INTO sa_det_orden_articulo (id_orden, id_articulo, cantidad, id_precio, precio_unitario, id_articulo_costo, id_articulo_almacen_costo, costo, id_iva, iva, tiempo_asignacion, id_empleado_aprobacion, aprobado)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), %s, %s);",
						valTpDato($idDocumentoVenta, "int"),
						valTpDato($valFormListaArt['hddIdArt'.$valor], "int"),
						valTpDato($valFormListaArt['hddCantArt'.$valor], "int"),
						valTpDato($valFormListaArt['hddIdPrecioArt'.$valor], "int"),
						valTpDato($valFormListaArt['hddPrecioArt'.$valor], "real_inglesa"),
                                                valTpDato($valFormListaArt['hddIdArtCosto'.$valor], "int"),
						valTpDato($valFormListaArt['hddIdArtAlmacenCosto'.$valor], "int"),
						valTpDato($valFormListaArt['hddCostoArt'.$valor], "real_inglesa"),
						valTpDato("", "int"),//ahora no usado
						valTpDato("", "real_inglesa"),//ahora no usado
						valTpDato($valFormDcto['hddIdEmpleado'], "int"),
						valTpDato($valFormListaArt['hddValorCheckAprobRpto'.$valor], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$insertSQL);
					$idDocumentoDetalle = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					//fputs($ar,__LINE__."---> ".$insertSQL."\n");
					
					$objResponse->assign("hddIdPedDet".$valor,"value",$idDocumentoDetalle);
					
                                        //nuevo gregor ivas por articulo
                                        foreach($valFormTotalDcto["ivaActivo"] as $key => $idIvas){
                                            if($valFormTotalDcto["txtBaseImponibleIva".$idIvas] != 0 && $valFormTotalDcto["txtTotalIva".$idIvas] != 0 && $valFormTotalDcto["txtIvaVenta".$idIvas] != 0){
                                                
                                                $arrayIvasArticulo = explode(",", $valFormListaArt['hddIdIvaArt'.$valor]);
                                                $arrayPorcIvasArticulo = explode(",", $valFormListaArt['hddIvaArt'.$valor]);
                                                
                                                foreach($arrayIvasArticulo as $indice => $idIvaRepuesto){
                                                   
                                                    if($idIvaRepuesto == $idIvas){//si el iva del repuesto lo tiene la orden, guardar
                                                    
                                                        $baseImponibleArticulo = $valFormListaArt['hddPrecioArt'.$valor];//siempre uno ya que la cantidad varia con surtido
                                                        $subtotalIvaArticulo =  round(($baseImponibleArticulo*$arrayPorcIvasArticulo[$indice])/100,2);
                                                        
                                                        $ivasSql = sprintf("INSERT INTO sa_det_orden_articulo_iva (id_det_orden_articulo, base_imponible, subtotal_iva, id_iva, iva) 
                                                                        VALUES (%s, %s, %s, %s, %s)",
                                                                valTpDato($idDocumentoDetalle,"int"),
                                                                valTpDato($baseImponibleArticulo,"real_inglesa"),
                                                                valTpDato($subtotalIvaArticulo,"real_inglesa"),
                                                                valTpDato($idIvaRepuesto,"int"),
                                                                valTpDato($arrayPorcIvasArticulo[$indice],"real_inglesa")
                                                            );
                                                        $rsIvas = mysql_query($ivasSql);
                                                        if (!$rsIvas) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$ivasSql); }
                                                                                                        
                                                    }
                                                    
                                                }
                                                
                                            }
                                        }
                                         
                                     
                                        
				} else {
					if ($valFormTotalDcto['hddAccionTipoDocumento'] == 4 && $valFormListaArt['hddValorCheckAprobRpto'.$valor] == 1) {
	
						
						if ($valFormListaArt['hddRptoTomadoSolicitud2'.$valor] != 1) {
							for ($cont = 0; $cont <= $valFormListaArt['hddCantArt'.$valor] - 1; $cont++) {
								$queryInsertarDet = sprintf("INSERT INTO sa_det_solicitud_repuestos (id_solicitud, id_det_orden_articulo, id_estado_solicitud)
								VALUE (%s, %s, 1)",
									valTpDato($idSolicitud, "int"),
									valTpDato($valFormListaArt['hddIdPedDet'.$valor], "int"));
								mysql_query("SET NAMES 'utf8';");
								$rsInsertarDet = mysql_query($queryInsertarDet);
								if (!$rsInsertarDet) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryInsertarDet);
								$idDetalleSolicitud = mysql_insert_id();
								mysql_query("SET NAMES 'latin1';");
								//fputs($ar,__LINE__."---> ".$queryInsertarDet."\n");
								
								$idSolicitudRptoCargaIndividual = $idSolicitud;
								
								$objResponse->assign("hddRptoEnSolicitud".$valor,"value",$idDetalleSolicitud);
							}
						}		
																		
						$consultarEdoSolicitud = sprintf("SELECT 
							sa_solicitud_repuestos.estado_solicitud,
							sa_solicitud_repuestos.id_solicitud
						FROM sa_solicitud_repuestos
							INNER JOIN sa_det_solicitud_repuestos ON (sa_solicitud_repuestos.id_solicitud = sa_det_solicitud_repuestos.id_solicitud)
						WHERE sa_det_solicitud_repuestos.id_det_orden_articulo = %s", 
							valTpDato($valFormListaArt['hddIdPedDet'.$valor], "int"));										
						$rsConsultarEdoSolicitud = mysql_query($consultarEdoSolicitud);
						if (!$rsConsultarEdoSolicitud) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$rsConsultarEdoSolicitud);
						$rowConsultarEdoSolicitud = mysql_fetch_assoc($rsConsultarEdoSolicitud);
						
						if ($rowConsultarEdoSolicitud['estado_solicitud'] == 0) {
							$queryActualizarEdoSolicitud = sprintf("UPDATE sa_solicitud_repuestos SET
								estado_solicitud = 1
							WHERE id_solicitud = %s",
								valTpDato($rowConsultarEdoSolicitud['id_solicitud'], "int"));
							mysql_query("SET NAMES 'utf8';");
							$rsActualizarEdoSolicitud = mysql_query($queryActualizarEdoSolicitud);
							if (!$rsActualizarEdoSolicitud) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$rsActualizarEdoSolicitud);
							mysql_query("SET NAMES 'latin1';");
							//fputs($ar,__LINE__."---> ".$queryActualizarEdoSolicitud."\n");
						}
					}
				}
			}
		}
	}
	
	// NOTAS AGREGADAS
	if (isset($arrayObjNota)) {
		foreach($arrayObjNota as $indiceNota => $valorNota) {
			if (strlen($valFormListNota['hddIdNota'.$valorNota]) > 0) {
				if ($valFormListNota['hddIdPedDetNota'.$valorNota] == "") {
					if ($valFormTotalDcto['hddSeleccionActivo'] == 1) {
						$valFormListNota['hddPrecNota'.$valorNota] = 0.00;
					}
					
					$insertSQL = sprintf("INSERT INTO sa_det_orden_notas (id_orden, descripcion_nota, precio, aprobado)
					VALUE (%s, %s, %s, %s);",
						valTpDato($idDocumentoVenta, "int"),
						valTpDato($valFormListNota['hddDesNota'.$valorNota], "text"),
						valTpDato($valFormListNota['hddPrecNota'.$valorNota], "real_inglesa"),
						valTpDato($valFormListNota['hddValorCheckAprobNota'.$valorNota], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					$idDocumentoDetalleNota = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					//fputs($ar,__LINE__."---> ".$insertSQL."\n");
					
					$objResponse->assign("hddIdPedDetNota".$valorNota,"value",$idDocumentoDetalleNota);
				}
			}
		}
	}
	
	// TOT AGREGADOS
	if (isset($arrayObjTot)) {
		foreach($arrayObjTot as $indiceTot => $valorTot) {
			if (strlen($valFormListTot['hddIdTot'.$valorTot]) > 0) {
				if ($valFormListTot['hddIdPedDetTot'.$valorTot] == "") {
					$insertSQL = sprintf("INSERT INTO sa_det_orden_tot (id_orden, id_orden_tot, id_porcentaje_tot, porcentaje_tot, aprobado, id_precio_tot)
					VALUE (%s, %s, %s, %s, %s, %s);",
						valTpDato($idDocumentoVenta, "int"),
						valTpDato($valFormListTot['hddIdTot'.$valorTot], "int"),
						valTpDato($valFormListTot['hddIdPorcTot'.$valorTot], "int"),
						valTpDato($valFormListTot['hddPorcTot'.$valorTot], "text"),
						valTpDato($valFormListTot['hddValorCheckAprobTot'.$valorTot], "int"),
                                                valTpDato($valFormListTot['hddIdPrecioTotAccesorio'.$valorTot], "int"));
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					$idDocumentoDetalleTot = mysql_insert_id();
					//fputs($ar,__LINE__."---> ".$insertSQL."\n");
					
					$queryTot = sprintf("UPDATE sa_orden_tot SET estatus = 2 WHERE sa_orden_tot.id_orden_tot = %s",
						valTpDato($valFormListTot['hddIdTot'.$valorTot], "int"));
					$rsTotUpd = mysql_query($queryTot);
					if (!$rsTotUpd) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					
					$objResponse->assign("hddIdPedDetTot".$valorTot,"value",$idDocumentoDetalleTot);
				}
			}
		}
	}
	
	// DESCUENTOS AGREGADOS
	if (isset($arrayObjDcto)) {
		foreach($arrayObjDcto as $indiceDcto => $valorDcto) {
			if (strlen($valFormTotalDcto['hddIdDcto'.$valorDcto]) > 0) {
				if ($valFormTotalDcto['hddIdDetDcto'.$valorDcto] == "") {
					$insertSQL = sprintf("INSERT INTO sa_det_orden_descuento (id_orden, id_porcentaje_descuento, porcentaje)
					VALUE (%s, %s, %s);",
						valTpDato($idDocumentoVenta, "int"),
						valTpDato($valFormTotalDcto['hddIdDcto'.$valorDcto], "int"),
						valTpDato($valFormTotalDcto['hddPorcDcto'.$valorDcto], "real_inglesa"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					$idDocumentoDetalleDcto = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					//fputs($ar,__LINE__."---> ".$insertSQL."\n");
					
					$objResponse->assign("hddIdDetDcto".$valorDcto,"value",$idDocumentoDetalleDcto);
				}
			}
		}
	}
	
	$queryTipoOrden = sprintf("SELECT 
		id_tipo_orden,
		precio_tempario,
		costo_tempario
	FROM sa_tipo_orden
	WHERE id_tipo_orden = %s",
		valTpDato($valFormDcto['lstTipoOrden'], "int"));
	$rsTipoOrden = mysql_query($queryTipoOrden);
	if (!$rsTipoOrden) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowTipoOrden = mysql_fetch_assoc($rsTipoOrden);
	$costoTemparioOrden = $rowTipoOrden["costo_tempario"]; // busco costo tempario orden paquete
	
	$queryBaseUt = sprintf("SELECT valor_parametro FROM pg_parametros_empresas
	WHERE descripcion_parametro = 1
		AND id_empresa = %s;",
		valTpDato($valFormDcto['txtIdEmpresa'], "int"));
	$rsBaseUt = mysql_query($queryBaseUt);
	if (!$rsBaseUt) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowBaseUt = mysql_fetch_assoc($rsBaseUt);
	
	if (isset($arrayObjPaq)) {
		foreach($arrayObjPaq as $indicePaq => $valorPaq) {
			$idPaquete = $valFormPaq['hddIdPaq'.$valorPaq];
			$idUnidadBasica = $valFormDcto['hddIdUnidadBasica'];
			
			$contRepPaq = 0;
			if ($idPaquete > 0) {
				if ($valFormPaq['hddIdPedDetPaq'.$valorPaq] == "") {
					$arrayManoObraAprobada = $valFormPaq['hddTempPaqAsig'.$valorPaq];
					$arrayManoObraAprobada[0] = ' ';
					$arrayManoObraAprobada = str_replace("|",",",$arrayManoObraAprobada);	
					
					$arrayRepuestoAprobado = $valFormPaq['hddRepPaqAsig'.$valorPaq];
					$arrayRepuestoAprobado[0] = ' ';
					$arrayRepuestoAprobado = str_replace("|",",",$arrayRepuestoAprobado);			
					
					//Si esta en blanco los array no se convierten a string porque no hay conversion en el reemplazo, si no hay que reemplazo
					if(is_array($arrayManoObraAprobada)){//valido que se le envie texto al query
						$arrayManoObraAprobada = "0";//con cero para que busque vacio y funcione el query
					}
					
					if(is_array($arrayRepuestoAprobado)){//valido que se le envie texto al query
						$arrayRepuestoAprobado = "0";//con cero para que busque vacio y funcione el query
					}
					
										
					$query = sprintf("SELECT *,
						sa_tempario.operador AS id_operador,
						sa_paq_tempario.costo AS costoCombo,
						sa_paq_tempario.ut AS utCombo
					FROM sa_paquetes
						INNER JOIN sa_paq_unidad ON (sa_paquetes.id_paquete = sa_paq_unidad.id_paquete)
						INNER JOIN sa_paq_tempario ON (sa_paquetes.id_paquete = sa_paq_tempario.id_paquete)
						INNER JOIN sa_tempario ON (sa_paq_tempario.id_tempario = sa_tempario.id_tempario)
						INNER JOIN sa_modo ON (sa_tempario.id_modo = sa_modo.id_modo)
					WHERE sa_paquetes.id_empresa = %s
						AND sa_paquetes.id_paquete = %s
						AND sa_paq_unidad.id_unidad_basica = %s
						AND sa_tempario.id_tempario IN (%s)", 
						valTpDato($valFormDcto['txtIdEmpresa'], "int"),
						valTpDato($idPaquete, "int"),
						valTpDato($idUnidadBasica, "int"),
						$arrayManoObraAprobada);
					$rs = mysql_query($query);
					if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					while ($row = mysql_fetch_assoc($rs)) {
						$idTempario = $row['id_tempario'];
						
						$query = sprintf("SELECT * FROM sa_tempario_det
						WHERE id_tempario = %s
							AND id_unidad_basica = %s;", 
							valTpDato($idTempario,"int"),
							valTpDato($idUnidadBasica,"int"));
						$rsValor = mysql_query($query);
						if (!$rsValor) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
						$rowValor = mysql_fetch_assoc($rsValor);
						
						/*CODICION PAQUETES COMBO*/
						if ($rowEmpresa['paquete_combo']==0) {//PAQUETE TEMPARIO
						$insertSQL = sprintf("INSERT INTO sa_det_orden_tempario (
							id_orden,
							id_paquete,
							id_tempario, 
							precio, 
							costo,
							costo_orden,
							id_modo,
							base_ut_precio,
							operador,
							ut, 
							tiempo_asignacion,
							id_mecanico, 
							id_empleado_aprobacion,
							aprobado,
							precio_tempario_tipo_orden,
							origen_tempario)
						VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NULL, %s, %s, %s, %s)", 
							valTpDato($idDocumentoVenta, "int"),
							valTpDato($idPaquete, "int"),
							valTpDato($idTempario, "int"),
							$row['precio'],
							$row['costo'],
							$costoTemparioOrden,
							valTpDato($row['id_modo'], "int"),
							$rowBaseUt['valor_parametro'],
							valTpDato($row['id_operador'], "int"),
							valTpDato($rowValor['ut'], "int"),
							valTpDato($valFormDcto['hddIdEmpleado'], "int"),
							$valFormPaq['hddValorCheckAprobPaq'.$valorPaq],
							$rowTipoOrden['precio_tempario'],
							valTpDato(0, "int")) ; // 0 = ORIGEN TEMPARIO]
							
						}
						
						else {//COMBO TEMPARIO
							$insertSQL = sprintf("INSERT INTO sa_det_orden_tempario (
							id_orden,
							id_paquete,
							id_tempario, 
							precio, 
							costo,
							costo_orden,
							id_modo,
							base_ut_precio,
							operador,
							ut, 
							tiempo_asignacion,
							id_mecanico, 
							id_empleado_aprobacion,
							aprobado,
							precio_tempario_tipo_orden,
							origen_tempario)
						VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NULL, %s, %s, %s, %s)", 
							valTpDato($idDocumentoVenta, "int"),
							valTpDato($idPaquete, "int"),
							valTpDato($idTempario, "int"),
							$row['costoCombo'],
							$row['costoCombo'],
							$costoTemparioOrden,
							valTpDato($row['id_modo'], "int"),
							$rowBaseUt['valor_parametro'],
							valTpDato($row['id_operador'], "int"),
							valTpDato($row['utCombo'], "int"),
							valTpDato($valFormDcto['hddIdEmpleado'], "int"),
							$valFormPaq['hddValorCheckAprobPaq'.$valorPaq],
							$row['costoCombo'],
							valTpDato(0, "int")) ; // 0 = ORIGEN TEMPARIO]
							
							
							}
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
						$idDocumentoDetalleTemp = mysql_insert_id();
						mysql_query("SET NAMES 'latin1';");
						//fputs($ar,__LINE__."---> ".$insertSQL."\n");
					}
					/*CONDICION PAQUETE COMBO*/
                                        
                                        if(!clienteExento($valFormDcto['txtIdCliente']) && tipoOrdenPoseeIva($valFormDcto['lstTipoOrden'])){// proviene del ac_iv_general servicios
                                            $guardarIva = "1";//sino es excento y si tipo de orden posee iva
                                        }else{
                                            $guardarIva = "2";//no guardar iva
                                        }
					
					if ($rowEmpresa['paquete_combo']==0) { // PAQUETE REPUESTOS costo promedio
					$insertSQL = sprintf("INSERT INTO sa_det_orden_articulo (id_orden, id_articulo, id_paquete, cantidad, id_precio, precio_unitario, id_articulo_costo, id_articulo_almacen_costo, costo, tiempo_asignacion, id_empleado_aprobacion, aprobado) 		
					SELECT 
						%s,
						iv_articulos.id_articulo,
						%s,
						sa_paquete_repuestos.cantidad,
                                                1, #PVM venta publico buscar precio actual
						(SELECT iv_articulos_precios.precio FROM iv_articulos_precios WHERE iv_articulos_precios.id_articulo_costo = (IFNULL((SELECT almacen.id_articulo_costo FROM vw_iv_articulos_almacen_costo almacen WHERE almacen.id_articulo = iv_articulos.id_articulo AND almacen.id_empresa = vw_iv_articulos_empresa.id_empresa AND almacen.estatus_almacen_venta = 1 AND cantidad_disponible_logica > 0 ORDER BY almacen.id_articulo_costo ASC LIMIT 1), 
                                                                                                                                                                     (SELECT almacen.id_articulo_costo FROM vw_iv_articulos_almacen_costo almacen WHERE almacen.id_articulo = iv_articulos.id_articulo AND almacen.id_empresa = vw_iv_articulos_empresa.id_empresa AND almacen.estatus_almacen_venta = 1 ORDER BY almacen.id_articulo_costo DESC LIMIT 1)))
                                                AND iv_articulos_precios.id_precio = 1 LIMIT 1) as precio,						

                                                IFNULL((SELECT almacen.id_articulo_costo FROM vw_iv_articulos_almacen_costo almacen WHERE almacen.id_articulo = iv_articulos.id_articulo AND almacen.id_empresa = vw_iv_articulos_empresa.id_empresa AND almacen.estatus_almacen_venta = 1 AND cantidad_disponible_logica > 0 ORDER BY almacen.id_articulo_costo ASC LIMIT 1), 
                                                       (SELECT almacen.id_articulo_costo FROM vw_iv_articulos_almacen_costo almacen WHERE almacen.id_articulo = iv_articulos.id_articulo AND almacen.id_empresa = vw_iv_articulos_empresa.id_empresa AND almacen.estatus_almacen_venta = 1 ORDER BY almacen.id_articulo_costo DESC LIMIT 1)
                                                ) as id_articulo_costo,
                                                                                                                                                   
                                                IFNULL((SELECT almacen.id_articulo_almacen_costo FROM vw_iv_articulos_almacen_costo almacen WHERE almacen.id_articulo = iv_articulos.id_articulo AND almacen.id_empresa = vw_iv_articulos_empresa.id_empresa AND almacen.estatus_almacen_venta = 1 AND cantidad_disponible_logica > 0 ORDER BY almacen.id_articulo_costo ASC LIMIT 1), 
                                                       (SELECT almacen.id_articulo_almacen_costo FROM vw_iv_articulos_almacen_costo almacen WHERE almacen.id_articulo = iv_articulos.id_articulo AND almacen.id_empresa = vw_iv_articulos_empresa.id_empresa AND almacen.estatus_almacen_venta = 1 ORDER BY almacen.id_articulo_costo DESC LIMIT 1)
                                                ) as id_articulo_almacen_costo,

						(SELECT
						if((SELECT valor FROM pg_configuracion_empresa config_emp
						INNER JOIN pg_configuracion config ON config_emp.id_configuracion = config.id_configuracion
						WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = %s) IN(1,3), 
                                                                                                                                            #este if, null sino tiene disponible, busco el ultimo
                                                                                                                                            (IFNULL((SELECT almacen.costo FROM vw_iv_articulos_almacen_costo almacen WHERE almacen.id_articulo = iv_articulos.id_articulo AND almacen.id_empresa = vw_iv_articulos_empresa.id_empresa AND almacen.estatus_almacen_venta = 1 AND cantidad_disponible_logica > 0 ORDER BY almacen.id_articulo_costo ASC LIMIT 1), 
                                                                                                                                                   (SELECT almacen.costo FROM vw_iv_articulos_almacen_costo almacen WHERE almacen.id_articulo = iv_articulos.id_articulo AND almacen.id_empresa = vw_iv_articulos_empresa.id_empresa AND almacen.estatus_almacen_venta = 1 ORDER BY almacen.id_articulo_costo DESC LIMIT 1)
                                                                                                                                            )),  
                                                                                                                                            #este else
                                                                                                                                            (IFNULL((SELECT almacen.costo_promedio FROM vw_iv_articulos_almacen_costo almacen WHERE almacen.id_articulo = iv_articulos.id_articulo AND almacen.id_empresa = vw_iv_articulos_empresa.id_empresa AND almacen.estatus_almacen_venta = 1 AND cantidad_disponible_logica > 0 ORDER BY almacen.id_articulo_costo ASC LIMIT 1), 
                                                                                                                                                   (SELECT almacen.costo_promedio FROM vw_iv_articulos_almacen_costo almacen WHERE almacen.id_articulo = iv_articulos.id_articulo AND almacen.id_empresa = vw_iv_articulos_empresa.id_empresa AND almacen.estatus_almacen_venta = 1 AND almacen.id_articulo_costo IS NOT NULL ORDER BY almacen.id_articulo_costo DESC LIMIT 1)
                                                                                                                                            )) #fin iif principal
						)) AS costo,
										
						#IF(%s = 1,
                                                #    IF ((SELECT id_impuesto FROM iv_articulos_impuesto WHERE id_impuesto = 1 AND id_articulo = iv_articulos.id_articulo LIMIT 1) = 1, (SELECT idIva FROM pg_iva
                                                #    WHERE estado = 1
                                                #            AND tipo = 6), NULL),0) AS id_iva_repuesto,
                                                #
                                                #IF(%s = 1,
                                                #    IF ((SELECT id_impuesto FROM iv_articulos_impuesto WHERE id_impuesto = 1 AND id_articulo = iv_articulos.id_articulo LIMIT 1) = 1, (SELECT iva FROM pg_iva
                                                #    WHERE estado = 1
                                                #            AND tipo = 6), NULL),0) AS iva_repuesto,
						
						NOW(),
						%s,
						%s
					FROM sa_paquetes
						INNER JOIN sa_paquete_repuestos ON (sa_paquetes.id_paquete = sa_paquete_repuestos.id_paquete)
						INNER JOIN iv_articulos ON (sa_paquete_repuestos.id_articulo = iv_articulos.id_articulo)
						#INNER JOIN iv_subsecciones ON (iv_articulos.id_subseccion = iv_subsecciones.id_subseccion)
						#INNER JOIN iv_marcas ON (iv_articulos.id_marca = iv_marcas.id_marca)
						#INNER JOIN iv_articulos_precios ON (iv_articulos.id_articulo = iv_articulos_precios.id_articulo)
						#INNER JOIN sa_tipo_orden ON (iv_articulos_precios.id_precio = sa_tipo_orden.id_precio_repuesto)
						LEFT JOIN vw_iv_articulos_empresa ON (iv_articulos.id_articulo = vw_iv_articulos_empresa.id_articulo)
					WHERE sa_paquetes.id_empresa = %s
						AND vw_iv_articulos_empresa.id_empresa = %s
						AND sa_paquetes.id_paquete = %s
						#AND sa_tipo_orden.id_tipo_orden = %s
						AND iv_articulos.id_articulo IN (%s)",
						valTpDato($idDocumentoVenta, "int"),
						valTpDato($valFormPaq['hddIdPaq'.$valorPaq], "int"),
						valTpDato($valFormDcto['txtIdEmpresa'], "int"),
                                                $guardarIva,
                                                $guardarIva,
						valTpDato($valFormDcto['hddIdEmpleado'], "int"),
						$valFormPaq['hddValorCheckAprobPaq'.$valorPaq],
						valTpDato($valFormDcto['txtIdEmpresa'], "int"),
						valTpDato($valFormDcto['txtIdEmpresa'], "int"), //empresa del documento no session
						valTpDato($valFormPaq['hddIdPaq'.$valorPaq], "int"),
						valTpDato($valFormDcto['lstTipoOrden'], "int"),
						$arrayRepuestoAprobado);
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					
					
					
					if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$insertSQL);
                                        
                                        }else{ //COMBO REPUESTOS costo promedio
					$insertSQL = sprintf("INSERT INTO sa_det_orden_articulo (id_orden, id_articulo, id_paquete, cantidad, id_precio, precio_unitario, id_articulo_costo, id_articulo_almacen_costo, costo, tiempo_asignacion, id_empleado_aprobacion, aprobado) 		
					SELECT 
						%s,
						iv_articulos.id_articulo,
						%s,
						sa_paquete_repuestos.cantidad,
                                                7, #Precio Editado (Bajar) para mantener precio combo
						sa_paquete_repuestos.precio,						
						
						IFNULL((SELECT almacen.id_articulo_costo FROM vw_iv_articulos_almacen_costo almacen WHERE almacen.id_articulo = iv_articulos.id_articulo AND almacen.id_empresa = vw_iv_articulos_empresa.id_empresa AND almacen.estatus_almacen_venta = 1 AND cantidad_disponible_logica > 0 ORDER BY almacen.id_articulo_costo ASC LIMIT 1), 
                                                       (SELECT almacen.id_articulo_costo FROM vw_iv_articulos_almacen_costo almacen WHERE almacen.id_articulo = iv_articulos.id_articulo AND almacen.id_empresa = vw_iv_articulos_empresa.id_empresa AND almacen.estatus_almacen_venta = 1 ORDER BY almacen.id_articulo_costo DESC LIMIT 1)
                                                ) as id_articulo_costo,
                                                                                                                                                   
                                                IFNULL((SELECT almacen.id_articulo_almacen_costo FROM vw_iv_articulos_almacen_costo almacen WHERE almacen.id_articulo = iv_articulos.id_articulo AND almacen.id_empresa = vw_iv_articulos_empresa.id_empresa AND almacen.estatus_almacen_venta = 1 AND cantidad_disponible_logica > 0 ORDER BY almacen.id_articulo_costo ASC LIMIT 1), 
                                                       (SELECT almacen.id_articulo_almacen_costo FROM vw_iv_articulos_almacen_costo almacen WHERE almacen.id_articulo = iv_articulos.id_articulo AND almacen.id_empresa = vw_iv_articulos_empresa.id_empresa AND almacen.estatus_almacen_venta = 1 ORDER BY almacen.id_articulo_costo DESC LIMIT 1)
                                                ) as id_articulo_almacen_costo,

						(SELECT
						if((SELECT valor FROM pg_configuracion_empresa config_emp
						INNER JOIN pg_configuracion config ON config_emp.id_configuracion = config.id_configuracion
						WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = %s) IN(1,3), 
                                                                                                                                            #este if, null sino tiene disponible, busco el ultimo
                                                                                                                                            (IFNULL((SELECT almacen.costo FROM vw_iv_articulos_almacen_costo almacen WHERE almacen.id_articulo = iv_articulos.id_articulo AND almacen.id_empresa = vw_iv_articulos_empresa.id_empresa AND almacen.estatus_almacen_venta = 1 AND cantidad_disponible_logica > 0 ORDER BY almacen.id_articulo_costo ASC LIMIT 1), 
                                                                                                                                                   (SELECT almacen.costo FROM vw_iv_articulos_almacen_costo almacen WHERE almacen.id_articulo = iv_articulos.id_articulo AND almacen.id_empresa = vw_iv_articulos_empresa.id_empresa AND almacen.estatus_almacen_venta = 1 ORDER BY almacen.id_articulo_costo DESC LIMIT 1)
                                                                                                                                            )),  
                                                                                                                                            #este else
                                                                                                                                            (IFNULL((SELECT almacen.costo_promedio FROM vw_iv_articulos_almacen_costo almacen WHERE almacen.id_articulo = iv_articulos.id_articulo AND almacen.id_empresa = vw_iv_articulos_empresa.id_empresa AND almacen.estatus_almacen_venta = 1 AND cantidad_disponible_logica > 0 ORDER BY almacen.id_articulo_costo ASC LIMIT 1), 
                                                                                                                                                   (SELECT almacen.costo_promedio FROM vw_iv_articulos_almacen_costo almacen WHERE almacen.id_articulo = iv_articulos.id_articulo AND almacen.id_empresa = vw_iv_articulos_empresa.id_empresa AND almacen.estatus_almacen_venta = 1 AND almacen.id_articulo_costo IS NOT NULL ORDER BY almacen.id_articulo_costo DESC LIMIT 1)
                                                                                                                                            )) #fin iif principal
						)) AS costo,
						
						#IF(%s = 1,
                                                #    IF ((SELECT id_impuesto FROM iv_articulos_impuesto WHERE id_impuesto = 1 AND id_articulo = iv_articulos.id_articulo LIMIT 1) = 1, (SELECT idIva FROM pg_iva
                                                #    WHERE estado = 1
                                                #            AND tipo = 6), NULL),0) AS id_iva_repuesto,
						#	
                                                #IF(%s = 1,
						#IF ((SELECT id_impuesto FROM iv_articulos_impuesto WHERE id_impuesto = 1 AND id_articulo = iv_articulos.id_articulo LIMIT 1) = 1, (SELECT iva FROM pg_iva
						#WHERE estado = 1
						#	AND tipo = 6), NULL),0) AS iva_repuesto,
						
						NOW(),
						%s,
						%s
					FROM sa_paquetes
						INNER JOIN sa_paquete_repuestos ON (sa_paquetes.id_paquete = sa_paquete_repuestos.id_paquete)
						INNER JOIN iv_articulos ON (sa_paquete_repuestos.id_articulo = iv_articulos.id_articulo)
						INNER JOIN iv_subsecciones ON (iv_articulos.id_subseccion = iv_subsecciones.id_subseccion)
						INNER JOIN iv_marcas ON (iv_articulos.id_marca = iv_marcas.id_marca)
						INNER JOIN iv_articulos_precios ON (iv_articulos.id_articulo = iv_articulos_precios.id_articulo)
						INNER JOIN sa_tipo_orden ON (iv_articulos_precios.id_precio = sa_tipo_orden.id_precio_repuesto)
						LEFT JOIN vw_iv_articulos_empresa ON (iv_articulos.id_articulo = vw_iv_articulos_empresa.id_articulo)
					WHERE sa_paquetes.id_empresa = %s
						AND vw_iv_articulos_empresa.id_empresa = %s
						AND sa_paquetes.id_paquete = %s
						AND sa_tipo_orden.id_tipo_orden = %s
						AND iv_articulos.id_articulo IN (%s)",
						valTpDato($idDocumentoVenta, "int"),						
						valTpDato($valFormPaq['hddIdPaq'.$valorPaq], "int"),
						valTpDato($valFormDcto['txtIdEmpresa'], "int"),
                                                $guardarIva,
                                                $guardarIva,
						valTpDato($valFormDcto['hddIdEmpleado'], "int"),
						$valFormPaq['hddValorCheckAprobPaq'.$valorPaq],
						valTpDato($valFormDcto['txtIdEmpresa'], "int"),
						valTpDato($valFormDcto['txtIdEmpresa'], "int"),//empresa del documento no session
						valTpDato($valFormPaq['hddIdPaq'.$valorPaq], "int"),
						valTpDato($valFormDcto['lstTipoOrden'], "int"),
						$arrayRepuestoAprobado);
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					
					if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery:".$insertSQL);
					
					}//fin else combo-paquetes
					
					$idDocumentoDetalleRep = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					//fputs($ar,__LINE__."---> ".$insertSQL."\n");					
					$objResponse->assign("hddIdPedDetPaq".$valorPaq,"value",$idDocumentoDetalleTemp);
                                       
                                        //nuevo multiples ivas por cada repuesto del paquete
                                        $ivasExistentesOrden = implode(",",$valFormTotalDcto["ivaActivo"]);//separados por coma
                                        if($guardarIva == "1" && $ivasExistentesOrden != ""){
                                            
                                            $sqlIvasRepPaquete = sprintf("SELECT id_det_orden_articulo, id_articulo, cantidad, precio_unitario FROM sa_det_orden_articulo 
                                                                          WHERE id_paquete = %s AND id_orden = %s",
                                                                valTpDato($valFormPaq['hddIdPaq'.$valorPaq], "int"),
                                                                valTpDato($idDocumentoVenta, "int"));
                                            $rsIvasRepPaquete = mysql_query($sqlIvasRepPaquete);
                                            if (!$rsIvasRepPaquete) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sqlIvasRepPaquete); }                                            
                                            
                                            while($rowDetRepPaquete = mysql_fetch_assoc($rsIvasRepPaquete)){
                                                
                                                $sqlIvasPorRepuestoPaquete = sprintf("SELECT id_impuesto 
                                                                                FROM iv_articulos_impuesto                                                                                
                                                                                WHERE id_articulo = %s AND id_impuesto IN (%s)",
                                                                    $rowDetRepPaquete["id_articulo"],
                                                                    $ivasExistentesOrden);
                                                $rsIvasPorRepuestoPaquete = mysql_query($sqlIvasPorRepuestoPaquete);
                                                if (!$rsIvasPorRepuestoPaquete) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sqlIvasPorRepuestoPaquete); }
                                                
                                                while($rowIvasPorRepuestoPaquete = mysql_fetch_assoc($rsIvasPorRepuestoPaquete)){
                                                    $porcIvaGuardar = $valFormTotalDcto["txtIvaVenta".$rowIvasPorRepuestoPaquete["id_impuesto"]];
                                                    
                                                    $ivasSqlPaq = sprintf("INSERT INTO sa_det_orden_articulo_iva (id_det_orden_articulo, base_imponible, subtotal_iva, id_iva, iva) 
                                                                            VALUES (%s, %s, %s, %s, %s)",
                                                                    valTpDato($rowDetRepPaquete["id_det_orden_articulo"],"int"),
                                                                    valTpDato($rowDetRepPaquete["precio_unitario"],"real_inglesa"),
                                                                    valTpDato((($rowDetRepPaquete["precio_unitario"]*$porcIvaGuardar)/100),"real_inglesa"),
                                                                    valTpDato($rowIvasPorRepuestoPaquete["id_impuesto"],"int"),
                                                                    valTpDato($porcIvaGuardar,"real_inglesa")
                                                                );
                                                            $rsIvasPaq = mysql_query($ivasSqlPaq);
                                                            if (!$rsIvasPaq) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$ivasSqlPaq); }
                                                }
                                            }
                                            
                                        }//fin calculo ivas
                                        
				} else {
					if ($varIngresoEncabezadoSolicitud == 1 && $valFormPaq['hddRptoTomadoSolicitud'.$valorPaq] != 1) {
						$array = explode("|", $valFormPaq['hddRepPaqAsig'.$valorPaq]);
						
						if (isset($array)) {
							foreach ($array as $indice2 => $valor2) {
								$idArticulo = $valor2;
									
								if ($idArticulo > 0) {
									$queryCantArtOrden = sprintf("SELECT * FROM sa_det_orden_articulo
									WHERE aprobado = 1
										AND id_orden = %s
										AND id_paquete = %s
										AND id_articulo = %s
										AND estado_articulo <> 'DEVUELTO';",
										valTpDato($idDocumentoVenta, "int"),
										valTpDato($idPaquete, "int"),
										valTpDato($idArticulo, "int"));
									$rsCantArtOrden = mysql_query($queryCantArtOrden);
									if (!$rsCantArtOrden) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
									$rowCantArtOrden = mysql_fetch_assoc($rsCantArtOrden);
									
									if ($rowCantArtOrden['cantidad'] > 0) {
										for ($contRep = 1; $contRep <= $rowCantArtOrden['cantidad']; $contRep++) {
											$queryInsertarEnDetalleSolicitud = sprintf("INSERT INTO sa_det_solicitud_repuestos (id_solicitud, id_det_orden_articulo, id_estado_solicitud)
											SELECT
												%s,
												id_det_orden_articulo,
												%s
											FROM sa_det_orden_articulo
											WHERE aprobado = 1
												AND id_orden = %s
												AND id_paquete = %s
												AND id_articulo = %s
												AND estado_articulo <> 'DEVUELTO';",
												valTpDato($idSolicitud, "int"),
												valTpDato(1, "int"), // 1 = SOLICITADO
												valTpDato($idDocumentoVenta, "int"),
												valTpDato($idPaquete, "int"),
												valTpDato($idArticulo, "int"));
											mysql_query("SET NAMES 'utf8';");
											$rsInsertarEnDetalleSolicitud = mysql_query($queryInsertarEnDetalleSolicitud);
											if (!$rsInsertarEnDetalleSolicitud) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
											mysql_query("SET NAMES 'latin1';");
											//fputs($ar,__LINE__."---> ".$queryInsertarEnDetalleSolicitud."\n");
										}
																								
										$consultarEdoSolicitud = sprintf("SELECT 
											estado_solicitud,
											id_solicitud
										FROM sa_solicitud_repuestos
										WHERE id_solicitud = %s", 
											valTpDato($idSolicitud, "int"));										
										$rsConsultarEdoSolicitud = mysql_query($consultarEdoSolicitud);
										if (!$rsConsultarEdoSolicitud) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
										$rowConsultarEdoSolicitud = mysql_fetch_assoc($rsConsultarEdoSolicitud);

										if ($rowConsultarEdoSolicitud['estado_solicitud'] == 0) {
											// ACTUALIZA EL ESTADO DE LA SOLICITUD
											$updateSQL = sprintf("UPDATE sa_solicitud_repuestos SET
												estado_solicitud = 1
											WHERE id_solicitud = %s;",
												valTpDato($rowConsultarEdoSolicitud['id_solicitud'], "int"));
											mysql_query("SET NAMES 'utf8';");
											$Result1 = mysql_query($updateSQL);
											if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
											mysql_query("SET NAMES 'latin1';");
											//fputs($ar,__LINE__."---> ".$queryActualizarEdoSolicitud."\n");
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}
	
	if ($valFormTotalDcto['hddTipoDocumento'] == 1) {
		if ($varIngresoEncabezadoSolicitud == 1) {// 4 SOLICITUD DE REPUESTOS
		
			$query = sprintf("UPDATE sa_orden SET
				id_estado_orden = 4
			WHERE id_orden = %s;",
				valTpDato($idDocumentoVenta, "int"));
			mysql_query("SET NAMES 'utf8';");	
			$Result1 = mysql_query($query);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			//fputs($ar,__LINE__."---> ".$query."\n");
		} else {// 19 PRESUPUESTO APROBADO
			$query = sprintf("UPDATE sa_orden SET
				id_estado_orden = 19
			WHERE id_orden = %s;",
				valTpDato($idDocumentoVenta, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($query);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			//fputs($ar,__LINE__."---> ".$query."\n");
		}
		
		mysql_query("COMMIT;");
		
		//MOSTRAR MENSAJE DEPENDIENDO DE LA ACCION
		if ($valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
			$objResponse->alert("Presupuesto Aprobado con Exito");
		} else {
			$objResponse->alert("Presupuesto Guardado con Exito");
		}
		
		$objResponse->assign("txtIdPresupuesto","value",$valFormDcto['txtIdPresupuesto']);
		
		$objResponse->script(sprintf("window.location.href = 'sa_presupuesto_list.php'"));
	} else {
		if ($valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
			if ($new_article == 1 || $new_package == 1) {
				
				$queryActualizarEdoOrden = sprintf("UPDATE sa_orden SET
					id_estado_orden = 4
				WHERE id_orden = %s
					AND id_empresa = %s;",
					valTpDato($idDocumentoVenta, "int"),
					valTpDato($valFormDcto['txtIdEmpresa'], "int"));
				mysql_query("SET NAMES 'utf8';");
				$rsActualizarEdoOrden = mysql_query($queryActualizarEdoOrden);
				if (!$rsActualizarEdoOrden) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
				//fputs($ar,__LINE__."---> ".$queryActualizarEdoOrden."\n");
				
				$objResponse->assign("hddIdSolicitudRpto","value",$idSolicitud);
			} else {
				// SI TIENE ALGUN RPTO NO TOMADO EN SOLISITUD Q ACTUALICE EN
				$queryDetallesSolicitudConEdoSolicitado = sprintf("SELECT COUNT(*) AS solicitud_con_edo_solicitado
				FROM sa_det_orden_articulo det_orden_art
					INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (det_orden_art.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo)
				WHERE det_orden_art.id_orden = %s
					AND	det_sol_rep.id_estado_solicitud = 1;", 
					valTpDato($idDocumentoVenta, "int"));
				$rsDetallesSolicitudConEdoSolicitado = mysql_query($queryDetallesSolicitudConEdoSolicitado);
				if (!$rsDetallesSolicitudConEdoSolicitado) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				$rowDetallesSolicitudConEdoSolicitado = mysql_fetch_assoc($rsDetallesSolicitudConEdoSolicitado);
				
				$id_estado_orden = ($rowDetallesSolicitudConEdoSolicitado['solicitud_con_edo_solicitado'] > 0) ? 4 : 6;
			
				$queryActualizarEdoOrden = sprintf("UPDATE sa_orden SET
					id_estado_orden = %s
				WHERE id_orden = %s
					AND id_empresa = %s;",
					valTpDato($id_estado_orden, "int"),
					valTpDato($idDocumentoVenta, "int"),
					valTpDato($valFormDcto['txtIdEmpresa'], "int"));
				mysql_query("SET NAMES 'utf8';");			
				$rsActualizarEdoOrden = mysql_query($queryActualizarEdoOrden);
				if (!$rsActualizarEdoOrden) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
				//fputs($ar,$queryActualizarEdoOrden."\n");
			}
		}
	
		if ($valFormDcto['txtEstadoOrden'] == '-' || $valFormDcto['txtEstadoOrden'] == '') {
			$id_estado_orden_ref = 1;	
			
			$queryActualizarEdoOrden = sprintf("UPDATE sa_orden SET
				id_estado_orden = %s
			WHERE id_orden = %s
				AND id_empresa = %s;",
				valTpDato($id_estado_orden_ref, "int"),
				valTpDato($idDocumentoVenta, "int"),
				valTpDato($valFormDcto['txtIdEmpresa'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$rs = mysql_query($queryActualizarEdoOrden);
			if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			//fputs($ar,__LINE__."---> ".$queryActualizarEdoOrden."\n");
				
			$query = sprintf("SELECT * FROM sa_estado_orden
			WHERE id_estado_orden = %s;",
				valTpDato($id_estado_orden_ref, "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$row = mysql_fetch_assoc($rs);
			
			$objResponse->assign("txtEstadoOrden","value",utf8_encode($row['nombre_estado']));
		} else {
			$queryEstadoOrden = sprintf("SELECT 
				estado_orden.orden
			FROM sa_estado_orden estado_orden
				INNER JOIN sa_orden orden ON (estado_orden.id_estado_orden = orden.id_estado_orden)
			WHERE orden.id_orden = %s;",
				valTpDato($idDocumentoVenta, "int"));
			$rsEstadoOrden = mysql_query($queryEstadoOrden);
			if (!$rsEstadoOrden) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$rowEstadoOrden = mysql_fetch_assoc($rsEstadoOrden);
			
			if ($rowEstadoOrden['orden'] <= 5) {
				if ($valFormTotalDcto['hddObjPaquete'] != '' || $valFormTotalDcto['hddObj'] != '' || $valFormTotalDcto['hddObjTempario'] != ''
				|| $valFormTotalDcto['hddObjTot'] != '' || $valFormTotalDcto['hddObjNota'] != '') {
					$id_estado_orden = 6;
				} else {
					$id_estado_orden = 1;
				}
				
				$queryActualizarEdoOrden = sprintf("UPDATE sa_orden SET
					id_estado_orden = %s,
					id_tipo_orden = %s
				WHERE id_orden = %s
					AND id_empresa = %s;",
					valTpDato($id_estado_orden, "int"),
					valTpDato($valFormDcto['lstTipoOrden'], "int"),
					valTpDato($idDocumentoVenta, "int"),
					valTpDato($valFormDcto['txtIdEmpresa'], "int"));
				mysql_query("SET NAMES 'utf8';");
				$rs = mysql_query($queryActualizarEdoOrden);	
				if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
				//fputs($ar,__LINE__."---> ".$queryActualizarEdoOrden."\n");
			}
		}
	
		$queryValidarSiExisteUnPaquetePendientePorAsignarAlmacen = sprintf("SELECT  
			COUNT(*) AS nroRptoPaqPendiente
		FROM sa_solicitud_repuestos sol_rep
			INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (sol_rep.id_solicitud = det_sol_rep.id_solicitud)
			INNER JOIN sa_det_orden_articulo det_orden_art ON (det_sol_rep.id_det_orden_articulo = det_orden_art.id_det_orden_articulo)
		WHERE sol_rep.estado_solicitud = 0
			AND det_orden_art.id_paquete IS NOT NULL
			AND det_orden_art.id_orden = %s;",
			$idDocumentoVenta);
		$rsPaqPend = mysql_query($queryValidarSiExisteUnPaquetePendientePorAsignarAlmacen);
		if (!$rsPaqPend) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowPaqPend = mysql_fetch_assoc($rsPaqPend);
	

			$queryVerificarSiLaOrdenFueImpresa = sprintf("SELECT impresion_orden FROM sa_orden
			WHERE id_orden = %s",
				$idDocumentoVenta);
			$rsVerificarSiLaOrdenFueImpresa = mysql_query($queryVerificarSiLaOrdenFueImpresa);
			if (!$rsVerificarSiLaOrdenFueImpresa) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$rowVerificarSiLaOrdenFueImpresa = mysql_fetch_assoc($rsVerificarSiLaOrdenFueImpresa);
			
			if ($rowVerificarSiLaOrdenFueImpresa['impresion_orden'] != 1) {
				if ($valFormTotalDcto['hddObjPaquete'] != '' || $valFormTotalDcto['hddObj'] != '' || $valFormTotalDcto['hddObjTempario'] != ''
				|| $valFormTotalDcto['hddObjTot'] != '' || $valFormTotalDcto['hddObjNota'] != '') {
					$queryActualizarEdoImpresionOrden = sprintf("UPDATE sa_orden SET
						impresion_orden = 1
					WHERE id_orden = %s",
						$idDocumentoVenta);
					mysql_query("SET NAMES 'utf8';");
					$rsActualizarEdoImpresionOrden = mysql_query($queryActualizarEdoImpresionOrden);
					if (!$rsActualizarEdoImpresionOrden) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					
					mysql_query("COMMIT;");
				
					$objResponse->alert("Orden de Servicio Guardada con Exito");
					
					$objResponse->assign("hddIdOrden","value",$idDocumentoVenta);
					$objResponse->assign("txtIdPresupuesto","value",$idDocumentoVenta);
					
					$objResponse->script(sprintf("window.location.href = 'sa_formato_orden.php?id=%s&doc_type=2&acc=0'", $idDocumentoVenta));
				} else {
					mysql_query("COMMIT;");
				
					$objResponse->alert("Orden de Servicio Guardada con Exito");
					
					$objResponse->assign("hddIdOrden","value",$idDocumentoVenta);
					$objResponse->assign("txtIdPresupuesto","value",$idDocumentoVenta);
					
					$objResponse->script("window.location.href = 'sa_orden_servicio_list.php'");
				}
			} else {
				mysql_query("COMMIT;");
				
				$objResponse->alert("Orden de Servicio Guardada con Exito");
				
				$objResponse->assign("hddIdOrden","value",$idDocumentoVenta);
				$objResponse->assign("txtIdPresupuesto","value",$idDocumentoVenta);
				
				$objResponse->script("window.location.href = 'sa_orden_servicio_list.php'");
			}
		//}
	}
	//COMENTADA GREGOR
	/*if ($vaAmagnetoplano == 1) {//ES QUIEN FINALIZA DE REPENTE Y REENVIA 
		$query = sprintf("UPDATE sa_orden SET
			id_estado_orden = 21
		WHERE id_orden = %s;",
			valTpDato($idDocumentoVenta, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($query);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		fputs($ar,__LINE__."---> ".$query."\n");
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("La Orden ha sido Finalizada. Ahora se puede Facturar");
		
		$objResponse->script("window.location.href='sa_orden_servicio_list.php'");
	}*/
	
	$objResponse->loadCommands(desbloquearOrden($idDocumentoVenta));
		
	//fclose($ar);
	
	return $objResponse;
}

//INSERTA EL ARTICULO AL FORMULARIO-TABLA DE LA ORDEN AL DARLE EN ACEPTAR EN LISTADO DE ARTICULOS
function insertarArticulo($valForm, $valFormListaArticulo, $valFormTotalDcto) {
	
	$objResponse = new xajaxResponse();
	
	$objResponse->script("$('btnInsertarArticulo').disabled = false;");
	
	if($valForm['txtPrecioArtRepuesto'] == NULL || $valForm['txtPrecioArtRepuesto'] == "" || $valForm['txtPrecioArtRepuesto'] == " " || $valForm['txtPrecioArtRepuesto'] == 0){
		return $objResponse->alert("No se puede agregar, no tiene precio asignado");
	}
	
	$hddNumeroArt = $valForm['hddNumeroArt'];
	$idEmpresa = $valForm['hddIdEmpresa'];
	$idArticulo = $valForm['hddIdArt'];
	//$idCasilla = $valForm['lstCasillaArt'];
	$cantidadArt = round($valForm['txtCantidadArt'],2);
	$idPrecioArt = $valForm['lstPrecioArt'];
	$precioArt = $valForm['txtPrecioArtRepuesto'];
	//$gastosArt = $valFormListaArticulo['hddGastoArt'.$hddNumeroArt];
	$idIvas = $valForm['hddIdIvaRepuesto'];//ahora con comas
        
        if($idIvas != "" && $idIvas != " "){
            $queryIva = sprintf("SELECT idIva, iva, observacion FROM pg_iva WHERE idIva IN (%s);", $idIvas);
            $rsIva = mysql_query($queryIva);
            if (!$rsIva) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
                        
            while($rowIva = mysql_fetch_assoc($rsIva)){
                $arrayIvas[] = $rowIva["idIva"];
                $arrayMontosIvas[] = $rowIva["iva"];
                $arrayIvasDisponibles[$rowIva["idIva"]]= array("idIva" => $rowIva["idIva"],
                                                                "iva" => $rowIva["iva"],
                                                                "observacion" => $rowIva["observacion"]);
            }
        }
        
        $idIvas = implode(",",$arrayIvas);
        $montosIvas = implode(",",$arrayMontosIvas);
        	
	
	if ($hddNumeroArt == "") {
		// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
		$arrayVal = explode("|",$valFormTotalDcto['hddObj']);
		foreach ($arrayVal as $indice => $valor) {
			if ($valor > 0){
                            $arrayObj[] = $valor;
                        }
		}                             
                       
			// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS Y ESPERA)
			$Result1 = actualizarSaldos($idArticulo);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
                //INICIZALIZO ALMACENES DISPONIBILIDADES
                $queryAlmacenes = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo 
                                          WHERE id_articulo = %s AND id_empresa = %s AND estatus_almacen_venta = 1 AND id_articulo_costo IS NOT NULL AND cantidad_disponible_logica > 0
                                          ORDER BY id_articulo_costo ASC", 
                                            valTpDato($idArticulo, "int"),
                                            valTpDato($idEmpresa, "int"));                
                $rsAlmacenes = mysql_query($queryAlmacenes);
                if (!$rsAlmacenes) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
                
				//si no hay disponible busco el ultimo lote para permitir agregacion:
				if(mysql_num_rows($rsAlmacenes) == 0){
					$queryAlmacenes = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo 
                                          WHERE id_articulo = %s AND id_empresa = %s AND estatus_almacen_venta = 1 AND id_articulo_costo IS NOT NULL 
                                          ORDER BY id_articulo_costo ASC", 
                                            valTpDato($idArticulo, "int"),
                                            valTpDato($idEmpresa, "int"));                
					$rsAlmacenes = mysql_query($queryAlmacenes);
					if (!$rsAlmacenes) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				}
				
                $arrayAlmacenes = array();
                
                while($rowAlmacenes = mysql_fetch_assoc($rsAlmacenes)){
				//busco precios por lote sino es manual
				if($idPrecioArt != 6 && $idPrecioArt != 7 && $idPrecioArt != 12){
                        $queryPrecios = sprintf("SELECT * FROM iv_articulos_precios WHERE id_articulo_costo = %s AND id_precio = %s LIMIT 1", 
                                            valTpDato($rowAlmacenes["id_articulo_costo"], "int"),
                                            valTpDato($idPrecioArt, "int"));
                        $rsPrecios = mysql_query($queryPrecios);
                        if (!$rsPrecios) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
                        $rowPrecioLote = mysql_fetch_assoc($rsPrecios);
                        
                        $precioArt = $rowPrecioLote["precio"];                        
                    }                  
                    
                    //creo almacenes con la informacion
                    $arrayAlmacenes[$rowAlmacenes["id_articulo_costo"]] = array(
                                                                        "id_articulo_costo" => $rowAlmacenes["id_articulo_costo"],
                                                                        "id_articulo_almacen_costo" => $rowAlmacenes["id_articulo_almacen_costo"],
                                                                        "costo" => $rowAlmacenes["costo"],
                                                                        "costo_promedio" => $rowAlmacenes["costo_promedio"],
                                                                        "cantidad_disponible_logica" => $rowAlmacenes["cantidad_disponible_logica"],
                                                                        "precio" => $precioArt,
                                                                        "cantidad_articulos" => 0
                                                                        );
                }
                //FIN BUSQUEDA DE ALMACENES
                
                // VERIFICA VALORES DE CONFIGURACION (Manejar Costo de Repuesto) ROGER
                $queryConfig12 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
                                        INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
                                        WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
                                              valTpDato($idEmpresa, "int"));
                $rsConfig12 = mysql_query($queryConfig12);
                if (!$rsConfig12) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
                $totalRowsConfig12 = mysql_num_rows($rsConfig12);
                $rowConfig12 = mysql_fetch_assoc($rsConfig12);
               		
                $arrayDistribucionAlmacen = distribucionArticuloAlmacenes($cantidadArt,$arrayAlmacenes);
                
                $sigValor = $arrayObj[count($arrayObj)-1];//ultimo valor en listado de repuestos
                
                foreach($arrayDistribucionAlmacen as $key => $arrayAlmacenUsar){
                    
			$sigValor++;
			
			$clase = (fmod($sigValor, 2) == 0) ? "trResaltar4" : "trResaltar5";
			
			// BUSCA EL ULTIMO COSTO DEL ARTICULO	
                        $cantidadArt = $arrayAlmacenUsar["cantidad_articulos"];
                        $precioArt = $arrayAlmacenUsar["precio"];
                        $idArticuloCosto = $arrayAlmacenUsar["id_articulo_costo"];
                        $idArticuloAlmacenCosto = $arrayAlmacenUsar["id_articulo_almacen_costo"];
			$costoArt = round($arrayAlmacenUsar["costo"],2);
			$costoPromedioArt = round($arrayAlmacenUsar["costo_promedio"],2);
			                        
			
			if($rowConfig12["valor"] == 1 || $rowConfig12["valor"] == 3){//es el costo a comprobar en la insercion, 1 costo del articulo
				$costoComparar = $costoArt;
			}elseif($rowConfig12["valor"] == 2){// 2 costo promedio del articulo
				$costoComparar = $costoPromedioArt;
			}else{//si es null, no existe la configuracion, tomara por defecto el costo del articulo
				$costoComparar = $costoArt;
			}
			
			//sino existe ningun tipo de costo	
			if($costoComparar == NULL){ return $objResponse->alert("El articulo no posee costo ".$idArticuloCosto." definido"); }			
			
				//al subir precio que este por encima del por defecto, agregado subir para realizar correctamente la validacion
			if ($valForm['hddBajarPrecio'] == "subir" && round($valForm['hddPrecioArtAsig'],2) > round($precioArt,2)) {
				return $objResponse->alert(("El Precio esta por debajo del Precio Asignado por Defecto, el Mismo debe estar por encima de ".$valForm['hddPrecioArtAsig']));
				
				//al bajar precio que este por debajo del por defecto, agregado bajar para la validacion
			} else if ($valForm['hddBajarPrecio'] == "bajar" && round($valForm['hddPrecioArtAsig'],2) < round($precioArt,2)) {
				return $objResponse->alert(("El Precio esta por encima del Precio Asignado por Defecto, el Mismo debe estar por debajo de ".$valForm['hddPrecioArtAsig']));
				
				//Si baja o sube validar el costo				
			} else if (($valForm['hddBajarPrecio'] == "bajar" || $valForm['hddBajarPrecio'] == "subir") && $valForm['hddBajarPrecio'] !='debajo_costo' && $costoComparar > round($precioArt,2)) {
					return $objResponse->alert(utf8_encode("No se puede agregar el Articulo id costo ".$idArticuloCosto." porque el Precio ".$precioArt." se encuentra por debajo del Costo ".$costoComparar));	
					
			} else {//si pasa las validaciones se agrega el articulo
				$caracterIva = ($idIvas != "") ? str_replace(",","% - ",$montosIvas)."%" : "NA";
			
                                //CALCULOS MULTIPLES IVAS
                                $totalIvasCalculados = 0;
                                foreach($arrayIvasDisponibles as $key => $arrayIvaUsar){                                    
                                    $totalIvasCalculados += ($cantidadArt * $precioArt * $arrayIvaUsar["iva"] / 100);
                                }
                                
                                $montoMultipleIva = $totalIvasCalculados + $cantidadArt * $precioArt;
                                
				// INSERTA EL ARTICULO MEDIANTE INJECT //aqui insercion en html del articulo gregor
				$objResponse->script(sprintf("
				var elemento = new Element('tr', {'id':'trItm:%s', 'class':'textoGris_11px %s'}).adopt([
					new Element('td', {'align':'right', 'id':'tdItmRep:%s', 'title':'trItm:%s', 'class':'color_column_insertar_eliminar_item'}).setHTML(\"<input id='cbxItm' name='cbxItm[]' type='checkbox' value='%s' />\"),
					new Element('td', {'align':'left'}).setHTML(\"%s\"),
					new Element('td', {'align':'left'}).setHTML(\"%s\"),
					new Element('td', {'align':'left'}).setHTML(\"%s\"),
					new Element('td', {'align':'center', 'id':'tdCant:%s'}).setHTML(\"%s\"),
					new Element('td', {'align':'center', 'id':'tdCantidadArtSol'}).setHTML(\"\"),
					new Element('td', {'align':'center', 'id':'tdCantidadArtDesp'}).setHTML(\"\"),
					
					new Element('td', {'align':'center', 'id':'tdPrecios:%s'}).setHTML(\"<span id='spanPrecioArt:%s'>%s</span>\"),
					new Element('td', {'align':'center', 'class':'noRomper'}).setHTML(\"%s\"),
					new Element('td', {'align':'right'}).setHTML(\"<span id='spanTotalArtSinIva:%s'>%s</span>\"),
					new Element('td', {'align':'right'}).setHTML(\"<span id='spanTotalArt:%s'><b>%s</b></span>\"),
					new Element('td', {'align':'center'}).setHTML(\"%s".
						"<input type='hidden' id='hddIdPedDet%s' name='hddIdPedDet%s' value='%s'/>".
						"<input type='hidden' id='hddIdArt%s' name='hddIdArt%s' value='%s'/>".
						"<input type='hidden' id='hddIdArtCosto%s' name='hddIdArtCosto%s' value='%s'/>".
						"<input type='hidden' id='hddIdArtAlmacenCosto%s' name='hddIdArtAlmacenCosto%s' value='%s'/>".
						"<input type='hidden' id='hddCantArt%s' name='hddCantArt%s' value='%s'/>".
						"<input type='hidden' id='hddIdPrecioArt%s' name='hddIdPrecioArt%s' value='%s'/>".
						"<input type='hidden' id='hddPrecioArt%s' name='hddPrecioArt%s' value='%s'/>".
						"<input type='hidden' id='hddCostoArt%s' name='hddCostoArt%s' value='%s'/>".//costo
						"<input type='hidden' id='hddIdIvaArt%s' name='hddIdIvaArt%s' value='%s'/>".
						"<input type='hidden' id='hddIvaArt%s' name='hddIvaArt%s' value='%s'/>".
						"<input type='hidden' id='hddTotalArt%s' name='hddTotalArt%s' value='%s'/>\"),
					new Element('td', {'align':'center', 'id':'tdItmRepAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"<input id='cbxItmAprob' name='cbxItmAprob[]' type='checkbox' value='%s' checked='checked' onclick='xajax_calcularTotalDcto();'/>".
						"<input type='hidden' id='hddValorCheckAprobRpto%s' name='hddValorCheckAprobRpto%s'/>".
						"<input type='hidden' id='hddRptoEnSolicitud%s' name='hddRptoEnSolicitud%s' value='%s'/>".
						"<input type='hidden' id='hddRptoTomadoSolicitud2%s' name='hddRptoTomadoSolicitud2%s' value='%s'/>\")
				]);
				elemento.injectBefore('trItmPie');",
				$sigValor, $clase,
					$sigValor, $sigValor, $sigValor,
					elimCaracter($valForm['txtCodigoArt'], ";"),
					$valForm['txtDescripcionArt'],
					$idArticuloCosto,
					$sigValor, $cantidadArt,
					//cantidadDisponibleActual($idArticulo,$idEmpresa), //cantidad articulos disponible
					$sigValor, $sigValor, number_format($precioArt,2,".",","),
					$caracterIva,
					$sigValor, number_format(($cantidadArt * $precioArt),2,".",","),
					//$sigValor, number_format((($cantidadArt * $precioArt * $rowIva['iva'] / 100) + $cantidadArt * $precioArt),2,".",","),//anterior iva uno
                                        $sigValor, number_format($montoMultipleIva,2,".",","),
					'-',
						$sigValor, $sigValor, "",
						$sigValor, $sigValor, $idArticulo,
						$sigValor, $sigValor, $idArticuloCosto,
						$sigValor, $sigValor, $idArticuloAlmacenCosto,
						$sigValor, $sigValor, $cantidadArt,
						$sigValor, $sigValor, $idPrecioArt,
						$sigValor, $sigValor, str_replace(",","", $precioArt),
						$sigValor, $sigValor, $costoComparar, //antes $costoArt ahora el costo que posee la empresa
						$sigValor, $sigValor, $idIvas,
						$sigValor, $sigValor, $montosIvas,
						$sigValor, $sigValor, ($cantidadArt * $precioArt),
					$sigValor, $sigValor,
						$sigValor, $sigValor,
						$sigValor, $sigValor, "",
						$sigValor, $sigValor, 0));
						
                                //eliminado para que no interfiera
//				if ($valForm['txtCantDisponible'] == 0) {
//					$objResponse->script("$('trRepuestosNoDisponibles').style.display = '';");
//						
//					$objResponse->script(sprintf("
//					var elemento = new Element('tr', {'id':'trItmNoDisp:%s', 'class':'textoGris_11px %s', 'title':'trItmNoDisp:%s'}).adopt([
//						new Element('td', {'align':'center'}).setHTML(\"%s\"),
//						new Element('td', {'align':'left'}).setHTML(\"%s\"),
//						new Element('td', {'align':'left'}).setHTML(\"%s\"),
//						new Element('td', {'align':'center'}).setHTML(\"%s\"),
//						new Element('td', {'align':'right'}).setHTML(\"%s\"),
//						new Element('td', {'align':'center'}).setHTML(\"%s\"),
//						new Element('td', {'align':'right'}).setHTML(\"%s".
//							"<input type='hidden' id='hddIdPedDetNoDisp%s' name='hddIdPedDetNoDisp%s' value='%s'/>".
//							"<input type='hidden' id='hddIdArtNoDisp%s' name='hddIdArtNoDisp%s' value='%s'/>".
//							"<input type='hidden' id='hddCantArtNoDisp%s' name='hddCantArtNoDisp%s' value='%s'/>".
//							"<input type='hidden' id='hddIdPrecioArtNoDisp%s' name='hddIdPrecioArtNoDisp%s' value='%s'/>".
//							"<input type='hidden' id='hddPrecioArtNoDisp%s' name='hddPrecioArtNoDisp%s' value='%s'/>".
//							"<input type='hidden' id='hddIdIvaArtNoDisp%s' name='hddIdIvaArtNoDisp%s' value='%s'/>".
//							"<input type='hidden' id='hddIvaArtNoDisp%s' name='hddIvaArtNoDisp%s' value='%s'/>".
//							"<input type='hidden' id='hddTotalArtNoDisp%s' name='hddTotalArtNoDisp%s' value='%s'/>\")
//					]);
//					
//					elemento.injectBefore('trm_pie_rpto_no_disponible');",
//					$sigValor, $clase, $sigValor,
//						'RPTO GENERAL',
//						$valForm['txtCodigoArt'],
//						$valForm['txtDescripcionArt'],
//						$valForm['txtCantidadArt'],
//						number_format($precioArt,2,".",","),
//						$caracterIva,
//						number_format(($valForm['txtCantidadArt'] * $precioArt),2,".",","),
//							$sigValor, $sigValor, "",
//							$sigValor, $sigValor, $valForm['hddIdArt'],
//							$sigValor, $sigValor, $valForm['txtCantidadArt'],
//							$sigValor, $sigValor, $idPrecioArt,
//							$sigValor, $sigValor, $precioArt,
//							$sigValor, $sigValor, $valForm['hddIdIvaRepuesto'],
//							$sigValor, $sigValor, $rowIva['iva'],
//							$sigValor, $sigValor, ($valForm['txtCantidadArt'] * $precioArt)));
//				}
				
                                
				if ($valFormTotalDcto['hddAccionTipoDocumento'] == 1) {
					$objResponse->script(sprintf("
					$('tdInsElimRep').style.display = '';
					$('tdItmRep:%s').style.display = '';
					$('tdItmRepAprob:%s').style.display = 'none';",
						$sigValor,
						$sigValor));	
				} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2) {
					$objResponse->script(sprintf("
					$('tdInsElimRep').style.display = 'none';
					$('tdItmRep:%s').style.display = 'none';
					$('tdItmRepAprob:%s').style.display = 'none';",
						$sigValor,
						$sigValor));	
				} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
					$objResponse->script(sprintf("
					$('tdInsElimRep').style.display = 'none'; 
					$('tdItmRep:%s').style.display = 'none'; 
					$('tdItmRepAprob:%s').style.display = '';",
						$sigValor,
						$sigValor));
				}
						
				$arrayObj[] = $sigValor;
                                              
			}//fin else validacion, agregado
                        
                        
                }//fin while
                        
//                foreach($arrayObj as $indice => $valor) {
//                        $cadena = $valFormTotalDcto['hddObj']."|".$valor;
//                }
                $cadena = implode("|", $arrayObj);
                
                $objResponse->assign("hddObj","value",$cadena);


                $objResponse->script("
                if ($('hddNumeroArt').value != '') {
                } else {
                        if ($('rbtBuscarCodBarra').checked == true) {
                                document.forms['frmDatosArticulo'].reset();
                                $('txtDescripcionArt').innerHTML = '';

                                $('txtDescripcionBusq').focus();
                                $('txtDescripcionBusq').select();
                        } else {
                                document.forms['frmDatosArticulo'].reset();
                                $('txtDescripcionArt').innerHTML = '';

                                document.forms['frmBuscarArticulo'].reset();
                                $('txtCodigoArticulo0').focus();
                                $('txtCodigoArticulo0').select();
                        }
                }");

                $objResponse->assign("tdListadoArticulos","innerHTML","");

                $objResponse->script("xajax_calcularTotalDcto();");
                        
		//} else {
			//$objResponse->alert(utf8_encode("Solo puede agregar un maximo de ".$rowConfigItemVenta['valor']." items por Pedido"));
		//}
	} else {//fin if
		// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
		$arrayVal = explode("|",$valFormTotalDcto['hddObj']);
		foreach ($arrayVal as $indice => $valor) {
			if ($valor > 0)
				$arrayObj[] = $valor;
		}
		
		$sw = 0;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($valFormListaArticulo['hddIdArt'.$valor] != ""){
					if ($valFormListaArticulo['hddIdArt'.$valor] == $valForm['hddIdArt'])
						$sw = 1;
				}
			}
		}
		
		if ($sw == 1 && $valFormTotalDcto['hddDuplicarRepuesto'] != 1) {
			$objResponse->script("
			if (confirm('El Articulo que desea agregar ya se encuentra en la lista. Desea agregarlo?') == true) {
				xajax_formClave(xajax.getFormValues('frmTotalPresupuesto'), 'acc_dup_rep');
			}");
		} else {
			$caracterIva = ($rowIva['idIva'] != "") ? $rowIva['iva']."%" : "NA";
			
			$objResponse->assign("tdCant:".$hddNumeroArt,"innerHTML",$cantidadArt);
			$objResponse->assign("spanPrecioArt:".$hddNumeroArt,"innerHTML",number_format($precioArt,2,".",","));
			$objResponse->assign("tdIva:".$hddNumeroArt,"innerHTML",$caracterIva);
			$objResponse->assign("spanTotalArt:".$hddNumeroArt,"innerHTML",number_format((($cantidadArt * $precioArt * $rowIva['iva'] / 100) + $cantidadArt * $precioArt),2,".",","));
			
			$objResponse->assign("hddCantArt".$hddNumeroArt,"value",$cantidadArt);
			$objResponse->assign("hddIdPrecioArt".$hddNumeroArt,"value",$idPrecioArt);
			$objResponse->assign("hddPrecioArt".$hddNumeroArt,"value",str_replace(",","",$precioArt));
			$objResponse->assign("hddIdIvaArt".$hddNumeroArt,"value",$idIvas);
			$objResponse->assign("hddIvaArt".$hddNumeroArt,"value",$montosIvas);
			$objResponse->assign("hddTotalArt".$hddNumeroArt,"value",str_replace(",","",$cantidadArt * $precioArt));//aqui iva de mas en buscar por codigo, antes tenia como el spantotal arriba
			
			$objResponse->script("
			if ($('hddNumeroArt').value != '') {
				if ($('rbtBuscarCodBarra').checked == true) {
					$('txtDescripcionBusq').focus();
					$('txtDescripcionBusq').select();
				} else {
					document.forms['frmBuscarArticulo'].reset();
					$('txtCodigoArticulo0').focus();
					$('txtCodigoArticulo0').select();
				}
			} else {
				if ($('rbtBuscarCodBarra').checked == true) {
					document.forms['frmDatosArticulo'].reset();
					$('txtDescripcionArt').innerHTML = '';
					
					$('txtDescripcionBusq').focus();
					$('txtDescripcionBusq').select();
				} else {
					document.forms['frmDatosArticulo'].reset();
					$('txtDescripcionArt').innerHTML = '';
					
					document.forms['frmBuscarArticulo'].reset();
					$('txtCodigoArticulo0').focus();
					$('txtCodigoArticulo0').select();
				}
			}");
	
			$objResponse->assign("tdListadoArticulos","innerHTML","");
			
			$objResponse->script("xajax_calcularTotalDcto()");
		}
	}
	
	return $objResponse;
}

//INSERTA LOS DESCUENTOS ADICIONALES INDIVIDUALES POR PORCENTAJE %, NO GENERAL
//AGREGA UN DESCUENTO GENERAL A LA ORDEN
function insertarDescuento($valFormPaq, $valFormArt, $valFormTemp, $valFormTot, $valFormNota, $valFormTotalDcto, $valFormDctoAgregar){
	$objResponse = new xajaxResponse();
	
	for ($cont = 0; $cont <= strlen($valFormTotalDcto['hddObjDescuento']); $cont++) {
		$caracter = substr($valFormTotalDcto['hddObjDescuento'], $cont, 1);
					
		if ($caracter != "|" && $caracter != "")
			$cadena .= $caracter;
		else {
			$arrayObjDcto[] = $cadena;
			$cadena = "";
		}	
	}
		
	$sigValor = $arrayObjDcto[count($arrayObjDcto)-1] + 1;
		
	$sw = 0;
	foreach($arrayObjDcto as $indice => $valor) {
		if ($valFormTotalDcto['hddIdDcto'.$valor] != "")
		{
			if($valFormTotalDcto['hddIdDcto'.$valor] == $valFormDctoAgregar['lstTipoDescuentos'])
				$sw = 1;
		}
	}
	
	if($sw == 1)
		$objResponse ->alert("El Descuento que desea agregar ya se encuentra en la lista. Escoja otro.");
	else {
		$objResponse->script(sprintf("
		var elemento = new Element('tr', {'id':'trItmDcto:%s', 'class':'textoGris_11px', 'title':'trItmDcto:%s'}).adopt([
			new Element('td', {'align':'right', 'id':'tdItmDcto:%s', 'class':'tituloCampo'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'right'}).setHTML(\"<input type='text' id='hddPorcDcto%s' name='hddPorcDcto%s' size='6' style='text-align:right' readonly='readonly' value='%s'/>%s\"),
			new Element('td', {'align':'right'}).setHTML(\"<img id='imgElimDcto:%s' name='imgElimDcto:%s' src='../img/iconos/ico_quitar.gif' class='puntero noprint' title='Porcentaje Adcl:%s' />".
				"<input type='hidden' id='hddIdDetDcto%s' name='hddIdDetDcto%s' value='%s'/>".
				"<input type='hidden' id='hddIdDcto%s' name='hddIdDcto%s' value='%s'/>\"),
			new Element('td', {'align':'right', 'id':'tdItmNotaAprob:%s'}).setHTML(\"<input type='text' id='txtTotalDctoAdcl%s' name='txtTotalDctoAdcl%s' readonly='readonly' style='text-align:right' size='18'/>\")
		]);
		elemento.injectBefore('trm_pie_dcto');
		
		$('imgElimDcto:%s').onclick=function(){
			xajax_eliminarDescuentoAdicional(%s);			
		}
		",
		$sigValor, $sigValor,
			$sigValor, $valFormDctoAgregar['txtDescripcionPorcDctoAdicional'].":",
			"",
			$sigValor, $sigValor, $valFormDctoAgregar['txtPorcDctoAdicional'], "%",
			$sigValor, $sigValor, $sigValor,
				$sigValor, $sigValor, "",
				$sigValor, $sigValor, $valFormDctoAgregar['lstTipoDescuentos'],
			$sigValor, $sigValor, $sigValor,
		$sigValor,
			$sigValor));
		
		$arrayObjDcto[] = $sigValor;
		foreach($arrayObjDcto as $indice => $valor) {
			$cadena = $valFormTotalDcto['hddObjDescuento']."|".$valor;
		}
	
		$objResponse->assign("hddObjDescuento","value",$cadena);
			
		$objResponse->script("
		$('divFlotante2').style.display = 'none';
		$('divFlotante').style.display = 'none';");
			
		$objResponse->script("xajax_calcularTotalDcto();");	
	}
	
	return $objResponse;
}

//INSERTA NOTA EN EL FORMULARIO - TABLA DE LA ORDEN AL DARLE EN ACEPTAR EN EL LISTADO DE TOT
function insertarNota($valForm, $valFormTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("$('btnGuardarNota').disabled = false;");
	
	for ($contNota = 0; $contNota <= strlen($valFormTotalDcto['hddObjNota']); $contNota++) {
		$caracterNota = substr($valFormTotalDcto['hddObjNota'], $contNota, 1);
					
		if ($caracterNota != "|" && $caracterNota != "")
			$cadenaNota.= $caracterNota;
		else {
			$arrayObjNota[] = $cadenaNota;
			$cadenaNota = "";
		}	
	}
	
	$sigValor = $arrayObjNota[count($arrayObjNota)-1] + 1;
	
	$objResponse->script(sprintf("
	var elemento = new Element('tr', {'id':'trItmNota:%s', 'class':'textoGris_11px', 'title':'trItmNota:%s'}).adopt([
		new Element('td', {'id':'tdItmNota:%s', 'class':'color_column_insertar_eliminar_item'}).setHTML(\"<input id='cbxItmNota' name='cbxItmNota[]' type='checkbox' value='%s' />\"),
		new Element('td', {'align':'left'}).setHTML(\"%s\"),
		new Element('td', {'align':'right'}).setHTML(\"%s".
			"<input type='hidden' id='hddIdPedDetNota%s' name='hddIdPedDetNota%s' value='%s'/>".
			"<input type='hidden' id='hddIdNota%s' name='hddIdNota%s' value='%s'/>".
			"<input type='hidden' id='hddDesNota%s' name='hddDesNota%s' value='%s'/>".
			"<input type='hidden' id='hddPrecNota%s' name='hddPrecNota%s' value='%s'/>\"),
		new Element('td', {'align':'center' , 'id':'tdItmNotaAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"<input id='cbxItmNotaAprob' name='cbxItmNotaAprob[]' type='checkbox' value='%s' checked='checked' onclick='xajax_calcularTotalDcto();'/>".
			"<input type='hidden' id='hddValorCheckAprobNota%s' name='hddValorCheckAprobNota%s'/>\")]);
	elemento.injectBefore('trm_pie_nota');",
	$sigValor, $sigValor,
		$sigValor, $sigValor,
		$valForm['txtDescripcionNota'],
		number_format($valForm['txtPrecioNota'],2,".",","),
			$sigValor, $sigValor, "",
			$sigValor, $sigValor, $sigValor,
			$sigValor, $sigValor, $valForm['txtDescripcionNota'],
			$sigValor, $sigValor, $valForm['txtPrecioNota'],
		$sigValor, $sigValor,
			$sigValor, $sigValor));
				
	/*if( $valFormTotalDcto['hddAccionTipoDocumento']==3)//$valFormTotalDcto['hddAccionTipoDocumento']==1 ||
		$objResponse->script(sprintf("$('tdInsElimNota').style.display = ''; $('tdItmNota:%s').style.display = ''; $('tdItmNotaAprob:%s').style.display='none'",$sigValor,$sigValor));	
	else */
	if($valFormTotalDcto['hddAccionTipoDocumento']==1)
		$objResponse->script(sprintf("
		$('tdInsElimNota').style.display = '';
		$('tdItmNota:%s').style.display = '';
		$('tdItmNotaAprob:%s').style.display = 'none';",
			$sigValor,
			$sigValor));	
	else if ($valFormTotalDcto['hddAccionTipoDocumento']==2)
		$objResponse->script(sprintf("
		$('tdInsElimNota').style.display = 'none';
		$('tdItmNota:%s').style.display = 'none';
		$('tdItmNotaAprob:%s').style.display=''",
			$sigValor,
			$sigValor));	
	else if ($valFormTotalDcto['hddAccionTipoDocumento']==4)
		$objResponse->script(sprintf("
		$('tdInsElimNota').style.display = 'none';
		$('tdItmNota:%s').style.display = 'none';
		$('tdItmNotaAprob:%s').style.display = ''",
			$sigValor,
			$sigValor));	
				
	$arrayObjNota[] = $sigValor;
	foreach($arrayObjNota as $indiceNota => $valorNota) {
		$cadenaNota = $valFormTotalDcto['hddObjNota']."|".$valorNota;
	}
	$objResponse->assign("hddObjNota","value",$cadenaNota);
	
	$objResponse->script("
	$('tblListados').style.display = 'none';
	$('tblArticulo').style.display = 'none';
	$('divFlotante2').style.display = 'none';
	$('divFlotante').style.display = 'none';");
	
	$objResponse->script("xajax_calcularTotalDcto();");
	
	return $objResponse;
}


//INSERTA EL PAQUETE EN EL FORMULARIO-TABLA DE LA ORDEN AL DARLE ACEPTAR EN EL LISTADO DE PAQUETES
function insertarPaquete($valForm, $valFormTotalDcto, $valFormListPaq, $valFormDcto) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("$('btnAsignarPaquete').disabled = false;");
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	for ($cont = 0; $cont <= strlen($valFormTotalDcto['hddObjPaquete']); $cont++) {
		$caracter = substr($valFormTotalDcto['hddObjPaquete'], $cont, 1);
					
		if ($caracter != "|" && $caracter != "")
			$cadena .= $caracter;
		else {
			$arrayObj[] = $cadena;
			$cadena = "";
		}	
	}

	$sw = 0;
	
	foreach ($arrayObj as $indice => $valor) {
		if ($valFormListPaq['hddIdPaq'.$valor] != "") {
			if($valFormListPaq['hddIdPaq'.$valor] == $valForm['hddEscogioPaquete'])
				$sw = 1;
		}
	}
		
	if ($sw == 1) {
		$objResponse->alert("El Paquete que desea agregar ya se encuentra en la lista. Escoja otro.");
	} else {
		$sigValor = $arrayObj[count($arrayObj)-1] + 1;
		
		$clase = (fmod($sigValor, 2) == 0) ? "trResaltar4" : "trResaltar5";
		
		if($valForm['hddArticuloSinDisponibilidad'] == 1)			
			$estado_paquete = "<img  src='../img/iconos/ico_alerta.gif' title='Contiene Articulos sin disponibilidad' />";
		else
			$estado_paquete = "<img  src='../img/iconos/ico_aceptar.gif' title='Los repuestos del paquete tienen stock' />";
				
		
		$objResponse->script(sprintf("
		var elemento = new Element('tr', {'id':'trItmPaq:%s', 'class':'textoGris_11px %s' , 'title':'trItmPaq:%s'}).adopt([
			new Element('td', {'align':'center', 'id':'tdItmPaq:%s', 'class':'color_column_insertar_eliminar_item'}).setHTML(\"<input id='cbxItmPaq' name='cbxItmPaq[]' type='checkbox' value='%s' />\"),
			new Element('td', {'align':'left'}).setHTML(\"%s\"),
			new Element('td', {'align':'left'}).setHTML(\"%s\"),
			new Element('td', {'align':'right'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s".
				"<img class='puntero noprint' id='img:%s' src='../img/iconos/ico_view.png' title='Paquete:%s' />".
				"<input type='hidden' id='hddIdPedDetPaq%s' name='hddIdPedDetPaq%s' readonly='readonly' value='%s'/>".
				"<input type='hidden' id='hddIdPaq%s' name='hddIdPaq%s' readonly='readonly' value='%s'/>".
				"<input type='hidden' id='hddPrecPaq%s' name='hddPrecPaq%s' readonly='readonly' value='%s'/>".
				"<input type='hidden' id='hddRepPaqAsig%s' name='hddRepPaqAsig%s' readonly='readonly' value='%s'/>".
				"<input type='hidden' id='hddTempPaqAsig%s' name='hddTempPaqAsig%s' readonly='readonly' value='%s'/>".
				"<input type='hidden' id='hddRepPaqAsigEdit%s' name='hddRepPaqAsigEdit%s' readonly='readonly' value='%s'/>".
				"<input type='hidden' id='hddTempPaqAsigEdit%s' name='hddTempPaqAsigEdit%s' readonly='readonly' value='%s'/>\"),
			new Element('td', {'align':'center', 'id':'tdItmPaqAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"<input id='cbxItmPaqAprob' name='cbxItmPaqAprob[]' type='checkbox' value='%s' checked='checked' onclick='xajax_calcularTotalDcto();'/>".
				"<input type='hidden' id='hddValorCheckAprobPaq%s' name='hddValorCheckAprobPaq%s'/>".
				"<input type='hidden' id='hddRptoPaqEnSolicitud%s' name='hddRptoPaqEnSolicitud%s' value='%s'/>".
				"<input type='hidden' id='hddTotalRptoPqte%s' name='hddTotalRptoPqte%s' value='%s'/>".
				"<input type='hidden' id='hddTotalTempPqte%s' name='hddTotalTempPqte%s' value='%s'/>".
				"<input type='hidden' id='hddRptoTomadoSolicitud%s' name='hddRptoTomadoSolicitud%s' value='%s'/>".
				"<input type='hidden' id='hddTotalExentoRptoPqte%s' name='hddTotalExentoRptoPqte%s' value='%s'/>".
                                "<input type='hidden' id='hddIdIvasRepuestoPaquete%s' name='hddIdIvasRepuestoPaquete%s' value='%s'/>".
                                "<input type='hidden' id='hddIvasRepuestoPaquete%s' name='hddIvasRepuestoPaquete%s' value='%s'/>".
                                "<input type='hidden' id='hddPorcentajesIvasRepuestoPaquete%s' name='hddPorcentajesIvasRepuestoPaquete%s' value='%s'/>".
				"<input type='hidden' id='hddTotalConIvaRptoPqte%s' name='hddTotalConIvaRptoPqte%s' value='%s'/>\")]);	
		elemento.injectBefore('trm_pie_paquete');
				
		$('img:%s').onclick=function(){
                        limpiarPaquetes();
			xajax_buscar_mano_obra_repuestos_por_paquete('%s','%s','%s','%s','%s','%s','%s',1);
			$('tdListadoPaquetes').style.display = 'none';
			$('tblBuscarPaquete').style.display = 'none';
			
			$('tblListadoTempario').style.display = 'none'; 
			$('tdBtnAccionesPaq').style.display = '';
			$('tblNotas').style.display = 'none';
			$('tblListadoRepuestosPorPaquete').style.display = '';
			$('tdListadoArticulos').style.display = 'none';
			$('tblArticulo').style.display = 'none';
			$('tblGeneralPaquetes').style.display = '';
			$('tblListadoTempario').style.display = 'none';  
			$('tblListadoTemparioPorPaquete').style.display = '';
		}",
		$sigValor, $clase, $sigValor,
			$sigValor, $sigValor,
			($valForm['txtCodigoPaquete']),
			($valForm['txtDescripcionPaquete']),
			$valForm['txtPrecioPaquete'],
			$estado_paquete,
				$sigValor, $valForm['hddEscogioPaquete'],
				$sigValor, $sigValor, "",
				$sigValor, $sigValor, $valForm['hddEscogioPaquete'],
				$sigValor, $sigValor,str_replace(",","",$valForm['txtPrecioPaquete']),
				$sigValor, $sigValor, $valForm['hddRepAproXpaq'],
				$sigValor, $sigValor, $valForm['hddManObraAproXpaq'],
				$valForm['hddEscogioPaquete'], $valForm['hddEscogioPaquete'], $valForm['hddRepAproXpaq'],
				$valForm['hddEscogioPaquete'], $valForm['hddEscogioPaquete'], $valForm['hddManObraAproXpaq'],
			$sigValor, $sigValor,
				$sigValor, $sigValor,
				$sigValor, $sigValor, "",
				$sigValor, $sigValor, str_replace(",","",$valForm['txtTotalRepPaq']),
				$sigValor, $sigValor, str_replace(",","",$valForm['txtTotalManoObraPaq']),
				$sigValor, $sigValor, "",
				$sigValor, $sigValor, str_replace(",","",$valForm['hddTotalArtExento']),
				$sigValor, $sigValor, $valForm['idIvasRepuestosPaquete'],
				$sigValor, $sigValor, $valForm['montoIvasRepuestosPaquete'],
				$sigValor, $sigValor, $valForm['porcentajesIvasRepuestosPaquete'],
				$sigValor, $sigValor, str_replace(",","",$valForm['hddTotalArtConIva']),
		
		$sigValor,
			$valForm['hddEscogioPaquete'], ($valForm['txtCodigoPaquete']), ($valForm['txtDescripcionPaquete']), $valForm['txtPrecioPaquete'], 0, $valForm['hddManObraAproXpaq'], $valForm['hddRepAproXpaq']));
				
				
		if ($valFormTotalDcto['hddAccionTipoDocumento'] == 1) {
			$objResponse->script(sprintf("
				$('tdInsElimPaq').style.display = '';
				$('tdItmPaq:%s').style.display = '';
				$('tdItmPaqAprob:%s').style.display='none';",
				$sigValor,
				$sigValor));	
		} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2) {
			$objResponse->script(sprintf("
				$('tdInsElimPaq').style.display = 'none';
				$('tdItmPaq:%s').style.display = 'none';
				$('tdItmPaqAprob:%s').style.display='none';",
				$sigValor,
				$sigValor));	
		} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
			$objResponse->script(sprintf("
				$('tdInsElimPaq').style.display = 'none'; 
				$('tdItmPaq:%s').style.display = 'none'; 
				$('tdItmPaqAprob:%s').style.display='';",
				$sigValor,
				$sigValor));
		}
				
		$arrayObj[] = $sigValor;
		foreach($arrayObj as $indice => $valor) {
			$cadena = $valFormTotalDcto['hddObjPaquete']."|".$valor;
		}
				
		$objResponse->assign("hddObjPaquete","value",$cadena);
		
		//CARGA LOS REPUESTOS NO DISPONIBLES DEL PAQUETE...
		for ($cont = 0; $cont <= strlen($valForm['hddArtNoDispPaquete']); $cont++) {
			$caracter = substr($valForm['hddArtNoDispPaquete'], $cont, 1);
					
			if ($caracter != "|" && $caracter != "")
				$cadena .= $caracter;
			else {
				$arrayObjRepNoDisp[] = $cadena;
				$cadena = "";
			}	
		}
			
                //LISTADO DE REPUESTOS NO DISPONIBLES SE SUPONE QUE SE ELIMINE
//		foreach($arrayObjRepNoDisp as $indice => $valor) {
//			$clase = (fmod($sigValor, 2) == 0) ? "trResaltar4" : "trResaltar5";
//			/*CONDICON PAQUETE COMBO*/
//			
//			
//			$queryArtNoDisp = sprintf("SELECT 
//				sa_paquete_repuestos.id_paquete,
//				iv_articulos.id_articulo,
//				vw_iv_articulos.codigo_articulo,
//				vw_iv_articulos.cantidad_disponible_logica,
//				vw_iv_articulos.cantidad_bloqueada,
//				sa_paquete_repuestos.cantidad,
//				sa_paquete_repuestos.precio AS precioRepuesto,
//				vw_iv_articulos_precios.precio,
//				vw_iv_articulos.descripcion,
//				vw_iv_articulos.marca,
//				vw_iv_articulos.descripcion_seccion,
//				vw_iv_articulos.descripcion_subseccion,
//				vw_iv_articulos.id_subseccion,
//				vw_iv_articulos.id_seccion,
//				vw_iv_articulos.tipo_articulo,
//				vw_iv_articulos_precios.id_articulo_precio,
//				(SELECT id_impuesto FROM iv_articulos_impuesto WHERE id_impuesto = 1 AND id_articulo = iv_articulos.id_articulo LIMIT 1) as posee_iva,
//				(case (SELECT id_impuesto FROM iv_articulos_impuesto WHERE id_impuesto = 1 AND id_articulo = iv_articulos.id_articulo LIMIT 1)
//					when '1' then
//						(SELECT idIva FROM pg_iva WHERE estado = 1 AND tipo = 6)
//					when '0' then
//						'0'
//				end) AS iva_repuesto
//			FROM iv_articulos
//				INNER JOIN vw_iv_articulos ON (iv_articulos.id_articulo = vw_iv_articulos.id_articulo)
//				INNER JOIN sa_paquete_repuestos ON (iv_articulos.id_articulo = sa_paquete_repuestos.id_articulo)
//				INNER JOIN iv_marcas ON (iv_articulos.id_marca = iv_marcas.id_marca)
//				INNER JOIN iv_subsecciones ON (iv_articulos.id_subseccion = iv_subsecciones.id_subseccion)
//				INNER JOIN vw_iv_articulos_precios ON (iv_articulos.id_articulo = vw_iv_articulos_precios.id_articulo)
//				INNER JOIN sa_tipo_orden ON (vw_iv_articulos_precios.id_precio = sa_tipo_orden.id_precio_repuesto)
//				INNER JOIN sa_paquetes ON (sa_paquete_repuestos.id_paquete = sa_paquetes.id_paquete)
//			WHERE sa_paquetes.id_empresa = %s
//				AND sa_paquetes.id_paquete = %s
//				AND sa_tipo_orden.id_tipo_orden = %s
//				AND iv_articulos.id_articulo = %s", 
//				valTpDato($valForm['txtIdEmpresa'], "int"),
//				valTpDato($valForm['hddEscogioPaquete'], "int"),
//				valTpDato($valFormDcto['lstTipoOrden'], "int"),
//				valTpDato($valor, "int"));
//			$rsArtNoDisp = mysql_query($queryArtNoDisp);
//			if (!$rsArtNoDisp) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
//			$rowArtNoDisp = mysql_fetch_assoc($rsArtNoDisp);
//				
//			if ($rowArtNoDisp['id_articulo'] != NULL || $rowArtNoDisp['id_articulo'] != "") {
//				if ($row['iva_repuesto'] != 0) {
//					$queryIva ="SELECT * FROM pg_iva WHERE tipo= 6 AND activo= 1 AND estado= 1";
//					$rsIva = mysql_query($queryIva);
//					if (!$rsIva) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
//					$rowIva = mysql_fetch_assoc($rsIva);
//				} else {
//					$queryIva ="SELECT * FROM pg_iva WHERE tipo= 6 AND activo= 1 AND estado= 1";
//					$rsIva = mysql_query($queryIva);
//					if (!$rsIva) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
//					$rowIva = mysql_fetch_assoc($rsIva);
//				}
//				
//				$caracterIva = ($rowIva['idIva'] != "") ? $rowIva['iva']."%" : "NA";
//				
//				$objResponse->script("$('trRepuestosNoDisponibles').style.display = '';");
//					
//				$objResponse->script(sprintf("
//				var elemento = new Element('tr', {'id':'trItmNoDisp:%s', 'class':'textoGris_11px %s', 'title':'trItmNoDisp:%s'}).adopt([
//					new Element('td', {'align':'center'}).setHTML(\"%s\"),
//					new Element('td', {'align':'left'}).setHTML(\"%s\"),
//					new Element('td', {'align':'left'}).setHTML(\"%s\"),
//					new Element('td', {'align':'center'}).setHTML(\"%s\"),
//					new Element('td', {'align':'right'}).setHTML(\"%s\"),
//					new Element('td', {'align':'center'}).setHTML(\"%s\"),
//					new Element('td', {'align':'right'}).setHTML(\"%s".
//						"<input type='hidden' id='hddIdPedDetNoDisp%s' name='hddIdPedDetNoDisp%s' value='%s'/>".
//						"<input type='hidden' id='hddIdArtNoDisp%s' name='hddIdArtNoDisp%s' value='%s'/>".
//						"<input type='hidden' id='hddCantArtNoDisp%s' name='hddCantArtNoDisp%s' value='%s'/>".
//						"<input type='hidden' id='hddIdPrecioArtNoDisp%s' name='hddIdPrecioArtNoDisp%s' value='%s'/>".
//						"<input type='hidden' id='hddPrecioArtNoDisp%s' name='hddPrecioArtNoDisp%s' value='%s'/>".
//						"<input type='hidden' id='hddIdIvaArtNoDisp%s' name='hddIdIvaArtNoDisp%s' value='%s'/>".
//						"<input type='hidden' id='hddIvaArtNoDisp%s' name='hddIvaArtNoDisp%s' value='%s'/>".
//						"<input type='hidden' id='hddTotalArtNoDisp%s' name='hddTotalArtNoDisp%s' value='%s'/>\")
//				]);
//				
//				elemento.injectBefore('trm_pie_rpto_no_disponible');",
//				$sigValor, $clase, $sigValor,
//					'PAQUETE',
//					elimCaracter($rowArtNoDisp['codigo_articulo'],";"),
//					$rowArtNoDisp['descripcion'],
//					$rowArtNoDisp['cantidad'],
//					number_format($rowArtNoDisp['precioRepuesto'],2,".",","),
//					$caracterIva,
//					number_format(($rowArtNoDisp['cantidad'] * $rowArtNoDisp['precio']),2,".",","),
//						$sigValor, $sigValor, "",
//						$sigValor, $sigValor, $rowArtNoDisp['id_articulo'],
//						$sigValor, $sigValor, $rowArtNoDisp['cantidad'],
//						$sigValor, $sigValor, $rowArtNoDisp['id_articulo_precio'],
//						$sigValor, $sigValor, $rowArtNoDisp['precioRepuesto'],
//						$sigValor, $sigValor, $rowIva['idIva'],
//						$sigValor, $sigValor, $rowIva['iva'],
//						$sigValor, $sigValor, ($rowArtNoDisp['cantidad'] * $rowArtNoDisp['precioRepuesto'])));
//			}
//		}
                
                
		$objResponse->script("
		$('tblListados').style.display = 'none';
		$('tblArticulo').style.display = 'none';
		$('divFlotante2').style.display = 'none';
		$('divFlotante').style.display = 'none';");	
		
		$objResponse->script("xajax_calcularTotalDcto()");			
	}

	return $objResponse;
}


//INSERTA TEMPARIO EN EL FORMULARIO-TABLA DE LA ORDEN AL DARLE ACEPTAR EN EL LISTADO DE TEMPARIOS
function insertarTempario($valForm, $valFormManoObraAgreg, $valFormTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("$('btnAsignarTemp').disabled = false;");

	for ($contTemp = 0; $contTemp <= strlen($valFormTotalDcto['hddObjTempario']); $contTemp++) {
		$caracterTemp = substr($valFormTotalDcto['hddObjTempario'], $contTemp, 1);
		
		if ($caracterTemp != "|" && $caracterTemp != "")
			$cadenaTemp.= $caracterTemp;
		else {
			$arrayObjTemp[] = $cadenaTemp;
			$cadenaTemp = "";
		}	
	}
	
	$sw = 0;
	foreach ($arrayObjTemp as $indice => $valor) {
		if ($valFormManoObraAgreg['hddIdTemp'.$valor] != "") {
			if ($valFormManoObraAgreg['hddIdTemp'.$valor] == $valForm['hddIdTemp'])
				$sw = 1;
		}
	}
		
	if ($sw == 1 && $valFormTotalDcto['hddDuplicarManoObra'] != 1) {	
		$objResponse->script("
		if(confirm('La Mano de Obra que desea agregar ya se encuentra en la lista. Desea agregarla?')) {
			xajax_formClave(xajax.getFormValues('frmTotalPresupuesto'), 'acc_dup_mo');
		}");
	} else {
		$sigValor = $arrayObjTemp[count($arrayObjTemp)-1] + 1;
		
		$clase = (fmod($sigValor, 2) == 0) ? "trResaltar4" : "trResaltar5";

		$queryMecanico = sprintf("SELECT 
			pg_empleado.nombre_empleado,
			pg_empleado.apellido,
			sa_mecanicos.id_mecanico
		FROM pg_empleado
			INNER JOIN sa_mecanicos ON (pg_empleado.id_empleado = sa_mecanicos.id_empleado)
		WHERE sa_mecanicos.id_mecanico = %s",
			valTpDato($valForm['lstMecanico'], "int"));
		$rsMecanico = mysql_query($queryMecanico);
		if (!$rsMecanico) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowMecanico = mysql_fetch_assoc($rsMecanico);
		
		$checked = " ";
		$display = " ";
		$disabled = " ";
		$imgCheckDisabled = " ";
		$readonly_check_ppal_list_tempario = 0;
		$value_checked = 0;
		
		$objResponse->script(sprintf("
		var elemento = new Element('tr', {'id':'trItmTemp:%s', 'class':'textoGris_11px %s', 'title':'trItmTemp:%s'}).adopt([
			new Element('td', {'align':'center', 'id':'tdItmTemp:%s', 'class':'color_column_insertar_eliminar_item'}).setHTML(\"<input id='cbxItmTemp' name='cbxItmTemp[]' type='checkbox' value='%s' onclick='xajax_calcularTotalDcto();' %s />\"),
			new Element('td', {'align':'center', 'id':'tdItmCodMecanico:%s'}).setHTML(\"%s\"),
			new Element('td', {'align':'center', 'id':'tdItmNomMecanico:%s'}).setHTML(\"%s\"),
			new Element('td', {'align':'left'}).setHTML(\"%s\"),
			new Element('td', {'align':'left'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'left'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'right'}).setHTML(\"%s\"),
			new Element('td', {'align':'right'}).setHTML(\"%s".
				"<input type='hidden' id='hddIdPedDetTemp%s' name='hddIdPedDetTemp%s' value='%s'/>".
				"<input type='hidden' id='hddIdTemp%s' name='hddIdTemp%s' value='%s'/>".
				"<input type='hidden' id='hddIdMec%s' name='hddIdMec%s' value='%s'/>".
				"<input type='hidden' id='hddIvaTemp%s' name='hddIvaTemp%s' value='%s'/>".
				"<input type='hidden' id='hddPrecTemp%s' name='hddPrecTemp%s' value='%s'/>".
				"<input type='hidden' id='hddIdModo%s' name='hddIdModo%s' value='%s'/>".
				"<input type='hidden' id='hddDetTemp%s' name='hddDetTemp%s' value='%s'/>\"),
			new Element('td', {'align':'center', 'id':'tdItmTempAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"<input id='cbxItmTempAprob' name='cbxItmTempAprob[]' 'title':'cbxItmTempAprob:%s' type='checkbox' value='%s' checked='checked' onclick='xajax_calcularTotalDcto();' %s/>".
				"<input type='hidden' id='hddValorCheckAprobTemp%s' name='hddValorCheckAprobTemp%s'/>".
				"%s".
				"<input type='hidden' id='hddIdOrigen%s' name='hddIdOrigen%s' value='%s'/>\")
		]); 
		elemento.injectBefore('trm_pie_tempario');",
		$sigValor, $clase, $sigValor,
			$sigValor,  $sigValor, $disabled,
			$sigValor, $rowMecanico['id_mecanico'],
			$sigValor, $rowMecanico['nombre_empleado']." ".$rowMecanico['apellido'],
			$valForm['txtSeccionTempario'],
			$valForm['txtSubseccionTempario'],
			$valForm['txtCodigoTemp'],
			$valForm['txtDescripcionTemp'],
			$valForm['hddOrigenTempario'],
			$valForm['txtModoTemp'],
			$valForm['txtDescripcionOperador'],
			$valForm['txtPrecio'],// antes usaba number_format
			$valForm['txtPrecioTemp'],// antes usaba number_format($valForm['txtPrecioTemp'],2,".",","), pero cuando ya viene 1,200.00 no sabe cual es cual y devuelve 1
				$sigValor, $sigValor, "",
				$sigValor, $sigValor, $valForm['hddIdTemp'],//hddIdDetTemp
				$sigValor, $sigValor, $rowMecanico['id_mecanico'],
				$sigValor, $sigValor, "",
				$sigValor, $sigValor, str_replace(",","", $valForm['txtPrecioTemp']),
				$sigValor, $sigValor, $valForm['txtIdModoTemp'],
				$sigValor, $sigValor, $valForm['hddIdDetTemp'],
			$sigValor, $sigValor, $sigValor, $display,
				$sigValor, $sigValor,
				$imgCheckDisabled,
				$sigValor, $sigValor, 0));
			
		if($valFormTotalDcto['hddAccionTipoDocumento'] == 1)
			$objResponse->script(sprintf("
			$('tdInsElimManoObra').style.display = '';
			$('tdItmTemp:%s').style.display = '';
			$('tdItmTempAprob:%s').style.display = 'none';",
				$sigValor,
				$sigValor));	
		else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2)
			$objResponse->script(sprintf("
			$('tdInsElimManoObra').style.display = 'none';
			$('tdItmTemp:%s').style.display = 'none';
			$('tdItmTempAprob:%s').style.display = ''",
				$sigValor,
				$sigValor));	
		else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 4)
			$objResponse->script(sprintf("
			$('tdInsElimManoObra').style.display = 'none';
			$('tdItmTemp:%s').style.display = 'none';
			$('tdItmTempAprob:%s').style.display = ''",
				$sigValor,
				$sigValor));				
			
			
		if ($valFormTotalDcto['hddTipoDocumento']==1) {
			$objResponse->script(sprintf("$('tdItmCodMecanico:%s').style.display='none'",$sigValor));
			$objResponse->script(sprintf("$('tdItmNomMecanico:%s').style.display='none'",$sigValor));
		} else {
			if ($valFormTotalDcto['hddMecanicoEnOrden'] == 1) {	
				$objResponse->script(sprintf("$('tdItmCodMecanico:%s').style.display=''",$sigValor));
				$objResponse->script(sprintf("$('tdItmNomMecanico:%s').style.display=''",$sigValor));
			} else {
				$objResponse->script(sprintf("$('tdItmCodMecanico:%s').style.display='none'",$sigValor));
				$objResponse->script(sprintf("$('tdItmNomMecanico:%s').style.display='none'",$sigValor));
			}
		}
		
		if ($readonly_check_ppal_list_tempario == 1) {
			$objResponse->script("
			$('cbxItmTempAprob').style.display = 'none';");
			$objResponse->assign("tdTempAprob","innerHTML","<input id='cbxItmTempAprobDisabled' name='cbxItmTempAprobDisabled[]' disabled='disabled' type='checkbox' checked='checked'/>");
			$objResponse->assign("tdInsElimManoObra","innerHTML","<input id='cbxItmTempAprobDisabledNoChecked' name='cbxItmTempAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");
		}
		
		$arrayObjTemp[] = $sigValor;
		foreach($arrayObjTemp as $indiceTemp => $valorTemp) {
			$cadenaTemp = $valFormTotalDcto['hddObjTempario']."|".$valorTemp;
		}
		$objResponse->assign("hddObjTempario","value",$cadenaTemp);
			
		$objResponse->script("
		document.forms['frmDatosTempario'].reset();
		$('txtDescripcionTemp').innerHTML = '';

		document.forms['frmBuscarTempario'].reset();
		$('txtCriterioTemp').focus();
		$('txtCriterioTemp').select();
		
		$('btnBuscarTempario').click();");
		
		$objResponse->script("xajax_calcularTotalDcto()");
	}
	
	return $objResponse;
}


//INSERTA TOT EN EL FORMULARIO-TABLA DE LA ORDEN AL DARLE ACEPTAR EN EL LISTADO DE TOT
function insertarTot($valForm, $valFormTotAgreg, $valFormTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("$('btnAsignarTot').disabled = false;");

	for ($contTot = 0; $contTot <= strlen($valFormTotalDcto['hddObjTot']); $contTot++) {
		$caracterTot = substr($valFormTotalDcto['hddObjTot'], $contTot, 1);
				
		if ($caracterTot != "|" && $caracterTot != "")
			$cadenaTot.= $caracterTot;
		else {
			$arrayObjTot[] = $cadenaTot;
			$cadenaTot = "";
		}	
	}
	
	$sw = 0;
	foreach($arrayObjTot as $indice => $valor) {
		if ($valFormTotAgreg['hddIdTot'.$valor] != "") {
			if($valFormTotAgreg['hddIdTot'.$valor] == $valForm['txtNumeroTot'])
				$sw = 1;
		}
	}
        
        $sqlDescripcion = sprintf("SELECT descripcion FROM sa_precios_tot WHERE id_precio_tot = %s LIMIT 1",
                             valTpDato($valForm['idPrecioTotAccesorio'],"int"));
        $rsDescripcion = mysql_query($sqlDescripcion);
        if (!$rsDescripcion) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
        
        $rowDescripcion = mysql_fetch_assoc($rsDescripcion);
		
	if($sw == 1) {
		$objResponse->alert("El T.O.T que desea agregar ya se encuentra en la lista. Escoja otro.");
	} else {
		$sigValor = $arrayObjTot[count($arrayObjTot)-1] + 1;
		
		$clase = (fmod($sigValor, 2) == 0) ? "trResaltar4" : "trResaltar5";
				
		$objResponse->script(sprintf("
		var elemento = new Element('tr', {'id':'trItmTot:%s', 'class':'textoGris_11px %s', 'title':'trItmTot:%s'}).adopt([
			new Element('td', {'align':'center', 'id':'tdItmTot:%s', 'class':'color_column_insertar_eliminar_item'}).setHTML(\"<input id='cbxItmTot' name='cbxItmTot[]' type='checkbox' value='%s' onclick='xajax_calcularTotalDcto();' />\"),
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'right'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'right'}).setHTML(\"%s".
				"<input type='hidden' id='hddIdPedDetTot%s' name='hddIdPedDetTot%s' value='%s'/>".
				"<input type='hidden' id='hddIdTot%s' name='hddIdTot%s' value='%s'/>".
				"<input type='hidden' id='hddIdPorcTot%s' name='hddIdPorcTot%s' value='%s'/>".
				"<input type='hidden' id='hddIdPrecioTotAccesorio%s' name='hddIdPrecioTotAccesorio%s' value='%s'/>".
				"<input type='hidden' id='hddPrecTot%s' name='hddPrecTot%s' value='%s'/>".
				"<input type='hidden' id='hddPorcTot%s' name='hddPorcTot%s' value='%s'/>".
				"<input type='hidden' id='hddMontoTotalTot%s' name='hddMontoTotalTot%s' value='%s'/>\"),
			new Element('td', {'align':'center', 'id':'tdItmTotAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"<input id='cbxItmTotAprob' name='cbxItmTotAprob[]' 'title':'cbxItmTotAprob:%s' type='checkbox' value='%s' checked='checked' onclick='xajax_calcularTotalDcto();'/>".
				"<input type='hidden' id='hddValorCheckAprobTot%s' name='hddValorCheckAprobTot%s'/>\")
		]); 
		elemento.injectBefore('trm_pie_tot');",
		$sigValor, $clase, $sigValor,
			$sigValor, $sigValor,
			"<idtotoculta style='display:none'>".$valForm['txtNumeroTot']."</idtotoculta>".$valForm['numeroTotMostrar'], //gregor numero tot en orden, solo insertar
			$valForm['txtProveedor'],
			$valForm['txtTipoPagoTot'],
			$valForm['txtMonto'],
			$valForm['txtPorcentaje']." ".$rowDescripcion["descripcion"],
			$valForm['txtMontoTotalTot'],
				$sigValor, $sigValor, "",
				$sigValor, $sigValor, $valForm['txtNumeroTot'],
				$sigValor, $sigValor, $valForm['hddIdPorcentajeTot'],
				$sigValor, $sigValor, $valForm['idPrecioTotAccesorio'],
				$sigValor, $sigValor, str_replace(",","", $valForm['txtMonto']),
				$sigValor, $sigValor, str_replace(",","", $valForm['txtPorcentaje']),
				$sigValor, $sigValor, str_replace(",","", $valForm['txtMontoTotalTot']),
			$sigValor, $sigValor, $sigValor, $sigValor,
				$sigValor, $sigValor));
				
		if ($valFormTotalDcto['hddAccionTipoDocumento']==1)
			$objResponse->script(sprintf("
			$('tdInsElimTot').style.display = '';
			$('trItmTot:%s').style.display = '';
			$('tdItmTotAprob:%s').style.display='none';",
				$sigValor,
				$sigValor));	
		else if ($valFormTotalDcto['hddAccionTipoDocumento']==2)
			$objResponse->script(sprintf("
			$('tdInsElimTot').style.display = 'none';
			$('trItmTot:%s').style.display = 'none';
			$('tdItmTotAprob:%s').style.display = ''",
				$sigValor,
				$sigValor));	
		else if ($valFormTotalDcto['hddAccionTipoDocumento']==4)
			$objResponse->script(sprintf("
			$('tdInsElimTot').style.display = 'none';
			$('trItmTot:%s').style.display = 'none';
			$('tdItmTotAprob:%s').style.display = ''",
				$sigValor,
				$sigValor));
			
		$arrayObjTot[] = $sigValor;
		foreach($arrayObjTot as $indiceTot => $valorTot) {
			$cadenaTot = $valFormTotalDcto['hddObjTot']."|".$valorTot;
		}
		$objResponse->assign("hddObjTot","value",$cadenaTot);
			
		$objResponse->script("
		$('tblListados').style.display ='none';
		$('tblArticulo').style.display ='none';
		$('divFlotante2').style.display = 'none';
		$('divFlotante').style.display = 'none';");
		
		$objResponse->script("xajax_calcularTotalDcto()");
	}
	
	return $objResponse;
}


//ES EL LISTADO DE ARTICULOS AL ABRIR LA PARTE DE AGREGAR ARTICULOS Y BUSCARLO
function listadoArticulos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 6, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if (strlen($valCadBusq[0]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq = $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[1], "text"));
	}

	if (strlen($valCadBusq[3]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if ($valCadBusq[2] == 6) {
			$sqlBusq .= $cond.sprintf("id_articulo = %s", valTpDato($valCadBusq[3], "int"));
		} else if ($valCadBusq[2] == 5) {
			$sqlBusq .= $cond.sprintf("(vw_iv_articulos_empresa.descripcion LIKE %s", valTpDato("%".$valCadBusq[3]."%", "text"));
			$sqlBusq .= sprintf("OR codigo_articulo_prov LIKE %s)", valTpDato("%".$valCadBusq[3]."%", "text"));
		} else if ($valCadBusq[2] == 4) {
			$sqlBusq .= $cond.sprintf("(SELECT subsec.descripcion
			FROM iv_subsecciones subsec
			WHERE subsec.id_subseccion = vw_iv_articulos_empresa.id_subseccion) LIKE %s", valTpDato($valCadBusq[3], "text"));
		} else if ($valCadBusq[2] == 3) {
			$sqlBusq .= $cond.sprintf("(SELECT sec.descripcion
			FROM iv_subsecciones subsec
				INNER JOIN iv_secciones sec ON (subsec.id_seccion = sec.id_seccion)
			WHERE subsec.id_subseccion = vw_iv_articulos_empresa.id_subseccion) LIKE %s", valTpDato($valCadBusq[3], "text"));
		} else if ($valCadBusq[2] == 2) {
			$sqlBusq .= $cond.sprintf("tipo_articulo LIKE %s", valTpDato($valCadBusq[3], "text"));
		} else if ($valCadBusq[2] == 1) {
			$sqlBusq .= $cond.sprintf("marca LIKE %s", valTpDato($valCadBusq[3], "text"));
		}
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("SELECT COUNT(id_articulo) FROM vw_iv_articulos_modelos_compatibles
		WHERE id_articulo = vw_iv_articulos_empresa.id_articulo AND id_uni_bas = %s) > 0",
			valTpDato($valCadBusq[4], "int"));
	}
        
	
	$query = sprintf("SELECT *, vw_iv_articulos_empresa.descripcion as descripcion, 
						iv_tipos_articulos.descripcion as tipo_articulo 
						FROM vw_iv_articulos_empresa
						LEFT JOIN iv_marcas ON vw_iv_articulos_empresa.id_marca = iv_marcas.id_marca
						LEFT JOIN iv_tipos_articulos ON vw_iv_articulos_empresa.id_tipo_articulo = iv_tipos_articulos.id_tipo_articulo
						 %s", $sqlBusq);						 
	
	
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
	
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "12%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, ("C&oacute;digo"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "40%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Descripci&oacute;n"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "10%", $pageNum, "marca", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Marca"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "6%", $pageNum, "existencia", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Saldo"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "5%", $pageNum, "cantidad_reservada", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Reservadas (Servicios)"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "8%", $pageNum, "cantidad_espera", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Espera por Facturar"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "6%", $pageNum, "cantidad_disponible_logica", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Disponible"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "6%", $pageNum, "cantidad_pedida", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Pedida a Proveedor"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "5%", $pageNum, "cantidad_futura", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Futura"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "2%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Clasif."));
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$cantidadDisponibleReal = $row['cantidad_disponible_logica'] + $row['cantidad_bloqueada'];//nuevo bloqueadas por mostrador se pueden agregar a servicio
		
		$srcIcono = "";
		if ($cantidadDisponibleReal == "" || $cantidadDisponibleReal == 0)
			$srcIcono = "../img/iconos/ico_error.gif";
		else if ($cantidadDisponibleReal <= $row['stock_minimo'])
			$srcIcono = "../img/iconos/ico_alerta.gif";
		else if ($cantidadDisponibleReal > $row['stock_minimo'])
			$srcIcono = "../img/iconos/ico_aceptar.gif";
		
		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarArticulo('".$row['id_articulo']."', xajax.getFormValues('frmPresupuesto'));\" title=\"Seleccionar Articulo\"><img src=\"".$srcIcono."\"/></button>"."</td>";
			$htmlTb .= "<td>".elimCaracter($row['codigo_articulo'], ";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['marca']." (".$row['tipo_articulo'].")")."</td>";
			$htmlTb .= "<td align=\"center\">".valTpDato($row['existencia'],"cero_por_vacio")."</td>";
			$htmlTb .= "<td align=\"center\">".valTpDato($row['cantidad_reservada'],"cero_por_vacio")."</td>";
			$htmlTb .= "<td align=\"center\">".valTpDato($row['cantidad_espera'],"cero_por_vacio")."</td>";
			$htmlTb .= "<td align=\"center\" class=\"divMsjInfo\">".valTpDato($cantidadDisponibleReal,"cero_por_vacio")."</td>";
			$htmlTb .= "<td align=\"center\">".valTpDato($row['cantidad_pedida'],"cero_por_vacio")."</td>";
			$htmlTb .= "<td align=\"center\">".valTpDato($row['cantidad_futura'],"cero_por_vacio")."</td>";
			$htmlTb .= "<td align=\"center\">";
				switch($row['clasificacion']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"Clasificaci&oacute;n A\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"Clasificaci&oacute;n B\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"Clasificaci&oacute;n C\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"Clasificaci&oacute;n D\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"Clasificaci&oacute;n E\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"Clasificaci&oacute;n F\"/>"; break;
				}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"12\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"12\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	
	$objResponse->assign("tdListadoArticulos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->script("$('tblListados').style.display = 'none';
$('tblArticulo').style.display = '';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Articulos");
	
	$objResponse->script("
if ($('divFlotante').style.display == 'none') {
$('divFlotante').style.display = '';
centrarDiv($('divFlotante'));
$('txtDescripcionBusq').focus();
$('txtDescripcionBusq').select();
}
$('tdListadoArticulos').style.display = '';
");

	return $objResponse;
}



//ES EL LISTADO DE PAQUETES AL ABRIR PAQUETES DESDE LA ORDEN, BUSCA EN EL LISTADO
function listado_paquetes_por_unidad($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	//$valCadBusq[0] = id empresa txtIdEmpresa
	//$valCadBusq[1] = id unidad hddIdUnidadBasica
	//$valCadBusq[2] = id tipo lstTipoOrden
	//$valCadBusq[3] = string Variable de busqueda txtDescripcionBusq
	
	$valCadBusq = explode("|", $valBusq);
	
	$queryEmpresa = sprintf("SELECT paquete_combo FROM pg_empresa WHERE id_empresa = %s",
									valTpDato($valCadBusq[0],"int"));
	$rsEmpresa = mysql_query($queryEmpresa);

	if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowEmpresa = mysql_fetch_array($rsEmpresa);
	
	
	
	$startRow = $pageNum * $maxRows;
	
	if (strlen($valCadBusq[0]) > 0) {
		$sqlBusq = sprintf(" WHERE sa_paquetes.id_empresa = %s AND sa_paq_unidad.id_unidad_basica= %s",
		valTpDato($valCadBusq[0],"int"),
		valTpDato($valCadBusq[1],"int"));
	}
	
	if ($valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("sa_paquetes.codigo_paquete LIKE %s OR sa_paquetes.descripcion_paquete LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%","text"),
			valTpDato("%".$valCadBusq[3]."%","text"));
	}
	
	$queryTipoOrden = sprintf("SELECT 
		sa_tipo_orden.id_tipo_orden,
		sa_tipo_orden.precio_tempario,
		sa_tipo_orden.id_precio_repuesto
	FROM sa_tipo_orden
	WHERE sa_tipo_orden.id_tipo_orden = %s",
		valTpDato($valCadBusq[2],"int"));
	$rsTipoOrden = mysql_query($queryTipoOrden);
	if (!$rsTipoOrden) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryTipoOrden);
	$rowTipoOrden = mysql_fetch_assoc($rsTipoOrden);
	 
	$queryBaseUt = sprintf("SELECT pg_parametros_empresas.valor_parametro
	FROM pg_parametros_empresas
	WHERE pg_parametros_empresas.descripcion_parametro = 1
		AND pg_parametros_empresas.id_empresa = %s",
		valTpDato($valCadBusq[0],"int"));
	$rsBaseUt = mysql_query($queryBaseUt);
	if (!$rsBaseUt) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryBaseUt);
	$rowBaseUt = mysql_fetch_assoc($rsBaseUt);
	 	
	$sqlBusq .= " GROUP BY sa_paquetes.id_paquete 
	ORDER BY sa_paquetes.codigo_paquete";
	/*CONDICION PAQUETE COMBO*/
	if ($rowEmpresa['paquete_combo']==0){//PAQUETE
	$query = sprintf("SELECT 
		sa_paquetes.id_paquete AS id_paq,
		sa_paquetes.codigo_paquete,
		sa_paquetes.descripcion_paquete,
		(SELECT SUM(vw_sa_temparios.ut) AS duracion_paq FROM vw_sa_temparios
		WHERE vw_sa_temparios.id_paquete = id_paq
			#AND vw_sa_temparios.id_empresa = %s #NO NECESARIO ID ES UNICO
                        ) AS duracion_aprox,
		(IFNULL((SELECT 
				SUM(
				(CASE vw_sa_temparios.id_modo
					WHEN '1' THEN vw_sa_temparios.importe_por_tipo_orden * %s/%s
					WHEN '2' THEN vw_sa_temparios.importe_por_tipo_orden
					WHEN '3' THEN vw_sa_temparios.importe_por_tipo_orden
					WHEN '4' THEN vw_sa_temparios.importe_por_tipo_orden
				END)) AS total_paquete
			FROM vw_sa_temparios
			WHERE vw_sa_temparios.id_paquete = id_paq
				#AND vw_sa_temparios.id_empresa = %s #NO NECESARIO ID ES UNICO
                                ),0) 
		+
		(IFNULL((SELECT
				SUM(
                                                (SELECT iv_articulos_precios.precio * sa_paquete_repuestos.cantidad FROM iv_articulos                                                                              
                                                                             INNER JOIN iv_articulos_precios ON iv_articulos.id_articulo = iv_articulos_precios.id_articulo
                                                                             INNER JOIN vw_iv_articulos_empresa ON iv_articulos.id_articulo = vw_iv_articulos_empresa.id_articulo
                                                WHERE iv_articulos_precios.id_articulo_costo = (IFNULL((SELECT almacen.id_articulo_costo FROM vw_iv_articulos_almacen_costo almacen WHERE almacen.id_articulo = iv_articulos.id_articulo AND almacen.id_empresa = vw_iv_articulos_empresa.id_empresa AND almacen.estatus_almacen_venta = 1 AND cantidad_disponible_logica > 0 ORDER BY almacen.id_articulo_costo ASC LIMIT 1), 
                                                                                                                                                                     (SELECT almacen.id_articulo_costo FROM vw_iv_articulos_almacen_costo almacen WHERE almacen.id_articulo = iv_articulos.id_articulo AND almacen.id_empresa = vw_iv_articulos_empresa.id_empresa AND almacen.estatus_almacen_venta = 1 ORDER BY almacen.id_articulo_costo DESC LIMIT 1)))
                                                AND iv_articulos_precios.id_precio = %s
                                                AND iv_articulos.id_articulo = sa_paquete_repuestos.id_articulo
                                                AND vw_iv_articulos_empresa.id_empresa = %s
                                                LIMIT 1)
                                                ) AS total_repuestos
				FROM sa_paquete_repuestos
				WHERE sa_paquete_repuestos.id_paquete = id_paq
				), 0))) AS total_paquete
	FROM sa_paquetes
		INNER JOIN sa_paq_unidad ON (sa_paquetes.id_paquete = sa_paq_unidad.id_paquete)
		INNER JOIN an_uni_bas ON (sa_paq_unidad.id_unidad_basica = an_uni_bas.id_uni_bas) %s",
		valTpDato($valCadBusq[0],"int"),
		$rowTipoOrden['precio_tempario'],
		$rowBaseUt['valor_parametro'],
		valTpDato($valCadBusq[0],"int"),
                $rowTipoOrden['id_precio_repuesto'],
		valTpDato($valCadBusq[0],"int"),		
		$sqlBusq);
        
        /*$query = sprintf("SELECT 
		sa_paquetes.id_paquete AS id_paq,
		sa_paquetes.codigo_paquete,
		sa_paquetes.descripcion_paquete,
                '-' AS duracion_aprox,
                '-' AS total_paquete
	FROM sa_paquetes
		INNER JOIN sa_paq_unidad ON (sa_paquetes.id_paquete = sa_paq_unidad.id_paquete)
		INNER JOIN an_uni_bas ON (sa_paq_unidad.id_unidad_basica = an_uni_bas.id_uni_bas) %s",
		$sqlBusq);*/
        
		}
		else {//COMBO
	$query = sprintf("SELECT 
		sa_paquetes.id_paquete AS id_paq,
		sa_paquetes.codigo_paquete,
		sa_paquetes.descripcion_paquete,
		(SELECT valor_parametro FROM pg_parametros_empresas WHERE descripcion_parametro = 1 AND id_empresa = sa_paquetes.id_empresa) AS base_ut, #solo comprobar no se usa
		
		(SELECT 
			SUM(sa_paq_tempario.ut) AS duracion_paq 
			FROM sa_paq_tempario
			WHERE sa_paq_tempario.id_paquete = id_paq) AS duracion_aprox,
		
		(IFNULL((SELECT 
				SUM(sa_paq_tempario.costo * (sa_paq_tempario.ut/
(SELECT valor_parametro FROM pg_parametros_empresas WHERE descripcion_parametro = 1 AND id_empresa = sa_paquetes.id_empresa)
)) AS total_paquete 
				FROM sa_paq_tempario
				WHERE sa_paq_tempario.id_paquete = id_paq),0)) 
		+
		(IFNULL((SELECT 
				SUM((sa_paquete_repuestos.cantidad*sa_paquete_repuestos.precio)) AS total_repuestos
				FROM sa_paquete_repuestos
				where sa_paquete_repuestos.id_paquete = id_paq), 0)) AS total_paquete,
				
		#solo comprobar no se usa
		(SELECT SUM((sa_paquete_repuestos.cantidad*sa_paquete_repuestos.precio)) AS total_repuestos
		FROM sa_paquete_repuestos
		where sa_paquete_repuestos.id_paquete = id_paq) AS REPUESTO, 
		
		#solo comprobar no se usa
		(SELECT SUM(sa_paq_tempario.costo) AS total_paquete 
		FROM sa_paq_tempario
		WHERE sa_paq_tempario.id_paquete = id_paq) AS TEMPARIO
	FROM sa_paquetes
		INNER JOIN sa_paq_unidad ON (sa_paquetes.id_paquete = sa_paq_unidad.id_paquete)
		INNER JOIN an_uni_bas ON (sa_paq_unidad.id_unidad_basica = an_uni_bas.id_uni_bas) %s",
		$sqlBusq);}
	
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Paquetes");

	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
	
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listado_paquetes_por_unidad", "20%", $pageNum, "codigo_paquete", $campOrd, $tpOrd, $valBusq, $maxRows, "C&oacute;digo");
		$htmlTh .= ordenarCampo("xajax_listado_paquetes_por_unidad", "50%", $pageNum, "descripcion_paquete", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listado_paquetes_por_unidad", "15%", $pageNum, "duracion_aprox", $campOrd, $tpOrd, $valBusq, $maxRows, "Duracion Aprox.");
		$htmlTh .= ordenarCampo("xajax_listado_paquetes_por_unidad", "15%", $pageNum, "total_paquete", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");	
    $htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
		$contFila++;
                
		$srcIcono = "../img/iconos/ico_aceptar.gif";
		
		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\">";
			$htmlTb .= "<td>";
				$htmlTb .= "<input type=\"hidden\" id=\"hdd_id_paquete\" name=\"hdd_id_paquete\" value=\"".$row['id_paq']."\"/>";
				$htmlTb .= "<button id=\"btnMostrarInfoPaq\" type=\"button\" onclick=\"limpiarPaquetes(); xajax_buscar_mano_obra_repuestos_por_paquete('".$row['id_paq']."','".utf8_encode($row['codigo_paquete'])."','".utf8_encode($row['descripcion_paquete'])."','".$row['total_paquete']."', 0, '".""."', '".""."',0);\" title=\"Seleccionar Paquete\"><img src=\"".$srcIcono."\"/></button>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['codigo_paquete'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_paquete'])."</td>";
			$htmlTb .= "<td align='center'>".utf8_encode($row['duracion_aprox'])."</td>";
			$htmlTb .= "<td align='right'>".number_format($row['total_paquete'],2,'.',',')."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_paquetes_por_unidad(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_paquetes_por_unidad(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listado_paquetes_por_unidad(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_paquetes_por_unidad(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_paquetes_por_unidad(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"10\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoPaquetes","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->script("
	if ($('divFlotante').style.display == 'none') {
		$('divFlotante').style.display='';
		centrarDiv($('divFlotante'));
		$('txtDescripcionBusq').focus();
	}
	$('divFlotante2').style.display='none';
	");
	
	return $objResponse;
}

//ES EL LISTADO DE REPUESTOS DENTRO DEL PAQUETE, AL ABRIR AGREGAR-PAQUETE
function listado_repuestos_por_paquetes($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 50, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	//$valCadBusq[0] $('txtIdEmpresa').value
	//$valCadBusq[1] $idPaquete
	//$valCadBusq[2] $('lstTipoOrden').value
	//$valCadBusq[3] $('hddAccionTipoDocumento').value
	//$valCadBusq[4] $origen
	//$valCadBusq[5] $('txtIdPresupuesto').value
	//$valCadBusq[6] arrayRepPaqComp
	//$valCadBusq[7] arrayReAp 
	//$valCadBusq[8] $accionVistaPaquete."'
	//$valCadBusq[9] $('txtIdCliente').value
	
	$valCadBusq = explode("|", $valBusq);
	
	$queryEmpresa = sprintf("SELECT paquete_combo FROM pg_empresa WHERE id_empresa = %s",
						   valTpDato($valCadBusq[0],"int"));
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowEmpresa = mysql_fetch_array($rsEmpresa);
	
	$startRow = $pageNum * $maxRows;	
	
	for ($contRep = 0; $contRep <= strlen($valCadBusq[6]); $contRep++) {
		$caracterRep = substr($valCadBusq[6], $contRep, 1);
		
		if ($caracterRep != "|" && $caracterRep != "")
			$cadenaRep .= $caracterRep;
		else {
			$arrayObjRep[] = $cadenaRep;
			$cadenaRep = "";
		}
	}
	$checked = "checked=\"checked\"";
	
	if ($valCadBusq[4] == 0) { 
		if (strlen($valCadBusq[0]) > 0) {
			$sqlBusq = sprintf(" WHERE sa_paquetes.id_empresa = %s
			AND sa_paquetes.id_paquete = %s AND vw_iv_articulos_empresa.id_empresa = %s", //agregado gregor
				valTpDato($valCadBusq[0],"int"),
				valTpDato($valCadBusq[1],"int"),
				valTpDato($valCadBusq[0],"int"));
		}
		/*CONDICION PAQUETE COMBO*/
		//este es COMBO
		$query = sprintf("SELECT 
			sa_paquete_repuestos.id_paquete,
			sa_paquete_repuestos.precio AS precioRepuesto,
			iv_articulos.id_articulo,
			vw_iv_articulos_empresa.codigo_articulo,
			vw_iv_articulos_empresa.cantidad_disponible_logica,
			vw_iv_articulos_empresa.cantidad_bloqueada,
			sa_paquete_repuestos.cantidad,
			vw_iv_articulos_empresa.descripcion,
			#vw_iv_articulos_empresa.marca,
			sa_paquete_repuestos.id_paq_repuesto
			#(SELECT id_impuesto FROM iv_articulos_impuesto WHERE id_impuesto = 1 AND id_articulo = iv_articulos.id_articulo LIMIT 1) as posee_iva
		FROM iv_articulos
			INNER JOIN vw_iv_articulos_empresa ON (iv_articulos.id_articulo = vw_iv_articulos_empresa.id_articulo)
			INNER JOIN sa_paquete_repuestos ON (iv_articulos.id_articulo = sa_paquete_repuestos.id_articulo)
			INNER JOIN iv_marcas ON (iv_articulos.id_marca = iv_marcas.id_marca)
			INNER JOIN iv_subsecciones ON (iv_articulos.id_subseccion = iv_subsecciones.id_subseccion)
			INNER JOIN sa_paquetes ON (sa_paquete_repuestos.id_paquete = sa_paquetes.id_paquete) %s", $sqlBusq);
                
	} else {
		if ($_GET['doc_type']==1) {
			$tablaEnc = "sa_presupuesto";
			$campoIdEnc = "id_presupuesto";
			$tablaDocDetArt = "sa_det_presup_articulo";
		} else {
			$tablaEnc = "sa_orden";
			$campoIdEnc = "id_orden";
			$tablaDocDetArt = "sa_det_orden_articulo";
		}
		
		if ($valCadBusq[3] == 4 || $valCadBusq[3] == 2 ||  $valCadBusq[3] == 3) {
			$campoAdnl = "
				sa_det_solicitud_repuestos.id_estado_solicitud,
				sa_det_solicitud_repuestos.id_det_solicitud_repuesto,
				vw_iv_articulos_empresa_ubicacion.id_casilla,
				vw_iv_articulos_empresa_ubicacion.ubicacion,
				vw_iv_articulos_empresa_ubicacion.descripcion_almacen,
				sa_det_solicitud_repuestos.id_solicitud,";
			
			$condAdnl = sprintf(" 
				LEFT JOIN sa_det_solicitud_repuestos ON (%s.id_det_orden_articulo = sa_det_solicitud_repuestos.id_det_orden_articulo) 
				LEFT OUTER JOIN vw_iv_articulos_empresa_ubicacion ON (sa_det_solicitud_repuestos.id_casilla = vw_iv_articulos_empresa_ubicacion.id_casilla)",
				$tablaDocDetArt);
		} else {
			$campoAdnl = "";
			$condAdnl = "";
		}	
		
		$query = sprintf("SELECT DISTINCT
			%s
			%s.id_det_orden_articulo,
			%s.estado_articulo,
			iv_articulos.id_articulo,
			iv_articulos.codigo_articulo,
			iv_marcas.marca,
			iv_marcas.id_marca,
			%s.cantidad,
			#%s.precio_unitario AS precioRepuesto, #agregado para visualizacion de paquete-combo
			%s.precio_unitario AS precio,
			iv_articulos.descripcion
			#(SELECT id_impuesto FROM iv_articulos_impuesto WHERE id_impuesto = 1 AND id_articulo = iv_articulos.id_articulo LIMIT 1) as posee_iva
		FROM iv_marcas
			INNER JOIN iv_articulos ON (iv_marcas.id_marca = iv_articulos.id_marca)
			INNER JOIN %s ON (iv_articulos.id_articulo = %s.id_articulo)
			INNER JOIN %s ON (%s.%s = %s.%s)
			%s
		WHERE %s.%s = %s
			AND %s.id_paquete = %s
			AND %s.estado_articulo <> 'DEVUELTO'",
			$campoAdnl,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt, $tablaDocDetArt,
			$tablaEnc, $tablaEnc, $campoIdEnc, $tablaDocDetArt, $campoIdEnc,
			$condAdnl,
			$tablaDocDetArt, $campoIdEnc, valTpDato($valCadBusq[5],"int"),
			$tablaDocDetArt, valTpDato($valCadBusq[1],"int"),
			$tablaDocDetArt);
	}
	
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
       
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	//$htmlTblIni .= "<table border=\"1\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
	//$htmlTh .= "<tr>";
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	//ojo cambiado a tblini para que saliera el titulo arriba
	$htmlTblIni .= "<tr align=\"center\" class=\"tituloColumna\">";
	$htmlTblIni.=  "<td align=\"center\" class=\"tituloCampo\" colspan=\"12\">Repuestos</td>";
	$htmlTblIni.=  "</tr>";
	
	$htmlTb.=  "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTb.=  "<td width=\"18%\">C&oacute;digo</td>";
		$htmlTb.=  "<td width=\"54%\">Descripci&oacute;n</td>";
	//if($valCadBusq[3] != 4)//PORQ SIEMPRE LA CANTIDAD EN EL LISTADO VA HACER 1
		$htmlTb.=  "<td width=\"8%\">Cantidad</td>";
		$htmlTb.=  "<td width=\"10%\">Precio Unit.</td>";
		$htmlTb.=  "<td width=\"10%\">Total</td>";
	if ($valCadBusq[3] == 1 || $valCadBusq[3] == 3) {
		if ($valCadBusq[4] != 0) {
			$htmlTb .= "<td>Almacen</td>";
			$htmlTb .= "<td>Ubicacion</td>";
		}
		$display_des = ($valCadBusq[4] == 0) ? "style=\"display:none\"" : "";
	} else {
		$htmlTb .= "<td>Almacen</td>";
		$htmlTb .= "<td>Ubicacion</td>";
	}
		$htmlTb.= "<td colspan=\"3\"></td>";
		$htmlTb.= sprintf("<td id=\"tdChkRep\">"."<input type=\"checkbox\" id=\"chk_repPaq\" %s onclick=\"selecAllChecks(this.checked,this.id,'frmDatosPaquete'); xajax_calcularTotalPaquete(xajax.getFormValues('frmDatosPaquete'), $('txtIdCliente').value, $('lstTipoOrden').value);\"/>"."</td>",
			$checked);
    $htmlTb.=  "</tr>";
	
	$articuloSinDisponibilidad=0;
	$sigValor = 1;
	$arrayObjRep = NULL; 
	$cadenaRe = NULL;
	
	if ($valCadBusq[7] > 0) {
		for ($contRepAprob = 0; $contRepAprob <= strlen($valCadBusq[7]); $contRepAprob++) {
			$caracterRepAprob = substr($valCadBusq[7], $contRepAprob, 1);
			
			if ($caracterRepAprob != "," && $caracterRepAprob != "")
				$cadenaRepAprob .= $caracterRepAprob;
			else {
				$arrayObjRepAprob[] = $cadenaRepAprob;
				$cadenaRepAprob = "";
			}
		}
	}
	
	$totalRe = 0;
	
	$montoExentoArt = 0;
	$montoArtConIva = 0;
        
        //si el cliente no es exento y el tipo de orden posee iva
        if(!clienteExento($valCadBusq[9]) && tipoOrdenPoseeIva($valCadBusq[2])){
            // para calcular montos paquetes
            $arrayIvas = ivaServicios(); //proviene de ac_iv_general devuelve array de arrays
            $ivasActivos = implode(",",array_keys($arrayIvas)); //devuelve id de ivas activos separados por coma
            $arrayMontoPorIvasRep = array();//key sera id impuesto y el valor el monto
            
        }
        
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
	//$objResponse->alert("Prcio del query:".$row['precioRepuesto']);//alerta() este es el precio del combo
		if ($valCadBusq[4] == 0) { //NUEVO
//			$query = sprintf("SELECT * FROM vw_iv_articulos_precios
//				INNER JOIN sa_tipo_orden ON (vw_iv_articulos_precios.id_precio = sa_tipo_orden.id_precio_repuesto)
//			WHERE sa_tipo_orden.id_tipo_orden = %s
//				AND vw_iv_articulos_precios.id_articulo = %s",
//				valTpDato($valCadBusq[2],"int"),
//				valTpDato($row['id_articulo'],"int"));
			
                        $query = sprintf("SELECT iv_articulos_precios.precio FROM iv_articulos                                                                              
                                                                             INNER JOIN iv_articulos_precios ON iv_articulos.id_articulo = iv_articulos_precios.id_articulo
                                                                             INNER JOIN vw_iv_articulos_empresa ON iv_articulos.id_articulo = vw_iv_articulos_empresa.id_articulo
                                                WHERE iv_articulos_precios.id_articulo_costo = (IFNULL((SELECT almacen.id_articulo_costo FROM vw_iv_articulos_almacen_costo almacen WHERE almacen.id_articulo = iv_articulos.id_articulo AND almacen.id_empresa = vw_iv_articulos_empresa.id_empresa AND almacen.estatus_almacen_venta = 1 AND cantidad_disponible_logica > 0 ORDER BY almacen.id_articulo_costo ASC LIMIT 1), 
                                                                                                                                                                     (SELECT almacen.id_articulo_costo FROM vw_iv_articulos_almacen_costo almacen WHERE almacen.id_articulo = iv_articulos.id_articulo AND almacen.id_empresa = vw_iv_articulos_empresa.id_empresa AND almacen.estatus_almacen_venta = 1 ORDER BY almacen.id_articulo_costo DESC LIMIT 1)))
                                                AND iv_articulos_precios.id_precio = 1
                                                AND iv_articulos.id_articulo = %s
                                                AND vw_iv_articulos_empresa.id_empresa = %s
                                                LIMIT 1",
				valTpDato($row['id_articulo'],"int"),				
                                valTpDato($valCadBusq[0],"int"));
                        
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$rowPrecioArticulo = mysql_fetch_assoc($rs);
			
			$precio = $rowPrecioArticulo['precio'];
                        
		} else {
			$precio = $row['precio'];
		}
		
		if ($precio == 0) {
			$paqConArtSinPrecio = 1;
		}else{                    
//                    if ($rowEmpresa['paquete_combo'] == 1 ){//si es combo, usar el precio del combo
//                        $precio = $row['precioRepuesto'];                      
//                    }
                }
		
				
		$cons = $_GET['cons'];
		
		if ($cons == 1) { // SI LA ORDEN ES DE RETRABAJO
			// SI ESTAN EN SOLICITUD Y APROBADO
			$queryCont = sprintf("SELECT COUNT(*) AS nro_rpto_desp FROM sa_det_solicitud_repuestos
			WHERE sa_det_solicitud_repuestos.id_det_orden_articulo = %s
				AND (sa_det_solicitud_repuestos.id_estado_solicitud = 3 OR sa_det_solicitud_repuestos.id_estado_solicitud = 5)", 
				valTpDato($row['id_det_orden_articulo'],"int"));
			$rsCont = mysql_query($queryCont);
			$rowCont = mysql_fetch_assoc($rsCont);
			if (!$rsCont) return $objResponse->alert(mysql_error()."\n\nSQL: ".$queryCont);
				
			if ($rowCont['nro_rpto_desp'] != NULL || $rowCont['nro_rpto_desp'] != "") {
				$cantidad_art = $rowCont['nro_rpto_desp'];
			} else {
				$cantidad_art = 0;
			}
		} else {
			$cantidad_art = $row['cantidad'];
		}
	
		$asignacion_precio_articulo = ($precio == 0) ? "<img src='../img/iconos/50.png' width='16' height='16' />" : "";
		
		if ($valCadBusq[4] == 1) {
			$checked = "checked=\"checked\"";
			$display = "style=\"display:none\"";
                        
//                        if(esEditar()){
//                            $botonEliminarIndividual = "<img src=\"../img/iconos/ico_quitar.gif\" class=\"puntero\" title=\"Eliminar\" onClick=\"eliminarRepuestoIndividual(".$row['id_articulo'].");\" >";
//                        }
		} else {			
			if ($valCadBusq[8] == 0 || $valCadBusq[8] == "") {
				$checked = "checked=\"checked\"";
			} else {			
				$checked = " ";
				if (isset($arrayObjRepAprob)){
					foreach ($arrayObjRepAprob as $indiceRepAprob => $valorRepAprob) {
						if ($valorRepAprob == $row['id_articulo']) {
							$checked = "checked=\"checked\"";
						}
					}
				}
			}
			$display = "style=' '";
		}		
                
                //ANTERIOR SE CAMBIO EL IVA BASE IMPONIBLE
//		if($row['posee_iva'] == 1) {
//			$montoArtConIva += doubleval($cantidad_art * $precio);
//			$srcIconoExento = "";
//		} else {
//			$montoExentoArt += doubleval($cantidad_art * $precio);
//			$srcIconoExento =  "<img src='../img/iconos/e_icon.png' />";
//		}
                
                if($ivasActivos){//para el query
                    $queryIvasArticulo = sprintf("SELECT id_impuesto 
                                                  FROM iv_articulos_impuesto 
                                                  WHERE id_articulo = %s AND id_impuesto IN (%s)",
                                            valTpDato($row['id_articulo'], "int"),
                                            $ivasActivos);
                    $rsIvasArticulo = mysql_query($queryIvasArticulo);
                    if (!$rsIvasArticulo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nQuery: ".$queryIvasArticulo); }

                    while($rowIvasArticulo = mysql_fetch_assoc($rsIvasArticulo)){

                        $arrayMontoPorIvasRep[$rowIvasArticulo["id_impuesto"]] += doubleval($cantidad_art * $precio);

                    }
                    
                    $srcIconoExento = "";
                    
                }
                
                if($ivasActivos == "" || empty($arrayMontoPorIvasRep)) {
			$montoExentoArt += doubleval($cantidad_art * $precio);
			$srcIconoExento =  "<img src='../img/iconos/e_icon.png' />";
		}
			//$objResponse->alert("monto art con iva: ".$montoArtConIva." monto excento art ".$montoExentoArt);//alerta2
		$cadenaRe = $cadenaRe."|".$row['id_articulo'];
	
		$cantidadDisponibleReal = $row['cantidad_disponible_logica'] + $row['cantidad_bloqueada'];
		
		if ($cantidadDisponibleReal == "" || $cantidadDisponibleReal <= 0) {
			$srcIcono = "../img/iconos/ico_error.gif";
			$articuloSinDisponibilidad = 1;
			$artPaqNoDisp = $artPaqNoDisp."|".$row['id_articulo'];
		} else if ($cantidadDisponibleReal > 5) {
			$srcIcono = "../img/iconos/ico_aceptar.gif";
		} else if ($cantidadDisponibleReal <= 5) {
			$srcIcono = "../img/iconos/ico_alerta.gif";
		}
		
		$cantidad = ($row['descripcion_almacen'] != "") ? 1 : $cantidad_art;
		
		/*CONDICION PAQUETE COMBO*/
		if ($rowEmpresa['paquete_combo']==0){//paquete
			$total = ($row['descripcion_almacen'] != "") ? $precio : $cantidad_art * $precio;
		}else{//combo
			$total = ($row['descripcion_almacen'] != "") ? $row['precioRepuesto'] : $cantidad_art * $row['precioRepuesto'];
                        
                        if($row['precioRepuesto'] == ""){//si esta vacio tomar el del combo combo
                            $total = ($row['descripcion_almacen'] != "") ? $precio : $cantidad_art * $precio;
                            $row['precioRepuesto'] = $precio;
                        }
		}
                		
                
		
		//$objResponse->alert($valCadBusq[4]." - ". $valCadBusq[8]);//alerta
		//$objResponse->alert($total);//alerta2
		
		$descripcion_almacen = ($row['descripcion_almacen'] == "") ? "-" : $row['descripcion_almacen'];
		$ubicacion_almacen = ($row['ubicacion'] == "") ? "-" : $row['ubicacion'];
	
		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
			$htmlTb .= "<td>".elimCaracter($row['codigo_articulo'], ";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\">".$cantidad."</td>";
			/*AGREGAR CONDICION PAQUETE COMBO*/
			
			if ($rowEmpresa['paquete_combo']==0){//PAQUETE
			
			$htmlTb .= "<td align=\"right\">".number_format($precio,2,".",",")."</td>";
			$htmlTb .= "<td align=\"right\">";
				$htmlTb .= number_format($total,2,".",",");
				$htmlTb .= sprintf("<input type=\"hidden\" id=\"txtPrecRepPaq%s\" name=\"txtPrecRepPaq%s\"	value=\"%s\"/>
				<input type=\"hidden\" id=\"txtPrecIndvRepPaq%s\" name=\"txtPrecIndvRepPaq%s\" value=\"%s\"/>",
					$row['id_articulo'], $row['id_articulo'], $total,
					$row['id_articulo'], $row['id_articulo'], $precio);
			$htmlTb .= "</td>";
				
				}else{//COMBO
				
			$htmlTb .= "<td align=\"right\">".number_format($row['precioRepuesto'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"right\">";
				$htmlTb .= number_format($total,2,".",",");
				$htmlTb .= sprintf("<input type=\"hidden\" id=\"txtPrecRepPaq%s\" name=\"txtPrecRepPaq%s\"	value=\"%s\"/>
				<input type=\"hidden\" id=\"txtPrecIndvRepPaq%s\" name=\"txtPrecIndvRepPaq%s\" value=\"%s\"/>",
					$row['id_articulo'], $row['id_articulo'], $total,
					$row['id_articulo'], $row['id_articulo'], $row['precioRepuesto']);
			$htmlTb .= "</td>";
			
			}
			
			$htmlTb .= sprintf("<td align=\"center\" id=\"tdDescripcionAlmacen\" %s>%s</td>", $display_des, utf8_encode($descripcion_almacen));
			$htmlTb .= sprintf("<td align=\"center\" id=\"tdUbicacionAlmacen\" %s>%s</td>", $display_des, utf8_encode($ubicacion_almacen));
			$htmlTb .= "<td align='center'>".$asignacion_precio_articulo."</td>";
			$htmlTb .= sprintf("<td align='center' id='tdDisponibilidadRep' %s><img src='%s'/></td>",
				$display,
				$srcIcono);
			$htmlTb .= "<td align=\"center\" id=\"tdPoseeIvaRep\">".$srcIconoExento.$botonEliminarIndividual."</td>";
			$htmlTb .= "<td align=\"center\" ".$display.">";
				$htmlTb .= sprintf("
				<input type=\"checkbox\" id=\"chk_repPaq\" name=\"chk_repPaq[]\" onclick=\"xajax_calcularTotalPaquete(xajax.getFormValues('frmDatosPaquete'), $('txtIdCliente').value, $('lstTipoOrden').value);\" %s value=\"%s\"/>
				<input type=\"hidden\" id=\"txtIdeRepPaq%s\" name=\"txtIdeRepPaq%s\" value=\"%s\"/>
				<input type=\"hidden\" id=\"txtRepCantTotal%s\" name=\"txtRepCantTotal%s\" value=\"%s\"/>",
					$checked, $row['id_articulo'],
					$row['id_articulo'], $row['id_articulo'], $row['id_articulo'],
					$row['id_articulo'], $row['id_articulo'], $cantidad_art);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayObjRepAprob[] = $row['id_articulo'];
		$arrayObjRep[] = $row['id_articulo'];
		$totalRe = $totalRe + ($row['cantidad'] * $precio);
		
		$sigValor++;	
	}
                
	//$objResponse->alert("monto2222222 art con iva: ".$montoArtConIva." monto excento art ".$montoExentoArt);//alerta2
	$objResponse->assign("hddTotalArtExento","value", $montoExentoArt);
	$objResponse->assign("hddTotalArtConIva","value", $montoArtConIva);//se supone que no se usa
       
        foreach($arrayMontoPorIvasRep as $key => $montoPorIvas){
            $idIvasActivosRepuestosPaq[] = $key;
            $porcentajesActivosRepuestosPaq[] = $arrayIvas[$key]["iva"];
        }
                
        $objResponse->assign("idIvasRepuestosPaquete","value", implode(",",$idIvasActivosRepuestosPaq));
        $objResponse->assign("montoIvasRepuestosPaquete","value", implode(",",$arrayMontoPorIvasRep));
        $objResponse->assign("porcentajesIvasRepuestosPaquete","value", implode(",",$porcentajesActivosRepuestosPaq));
        
        
	$cadenaRepAprob = "";
	if (isset($arrayObjRepAprob)) {
		foreach($arrayObjRepAprob as $indiceRepAprob => $valorRepAprob) {
			$cadenaRepAprob .= "|".$valorRepAprob;
		}
		$objResponse->assign("hddRepAproXpaq","value",$cadenaRepAprob);
	}
	
	$cadenaRep = "";
	if (isset($arrayObjRep)) {
		foreach($arrayObjRep as $indiceRep => $valorRep) {
			$cadenaRep .= "|".$valorRep;
		}
		$objResponse->assign("hddObjRepuestoPaq","value",$cadenaRep);
	}	
	
	if ($paqConArtSinPrecio == 1) {
		$objResponse->script("$('hddArtEnPaqSinPrecio').value = 1;");
	} else {
		$objResponse->script("$('hddArtEnPaqSinPrecio').value = 0;");
	}

	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"11\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_repuestos_por_paquetes(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_repuestos_por_paquetes(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listado_repuestos_por_paquetes(%s,'%s','%s','%s',%s);\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_repuestos_por_paquetes(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_repuestos_por_paquetes(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"11\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoRepuestos","innerHTML",$htmlTblIni./*$htmlTf.*/$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	if ($valCadBusq[4] == 1) {
		$objResponse->script("
		$('tdDisponibilidadRep').style.display = 'none';
		$('tdChkRep').style.display = 'none';");
	}
						
	$objResponse->script("
	$('hddArticuloSinDisponibilidad').value = '$articuloSinDisponibilidad';
	$('hddArtNoDispPaquete').value = '$artPaqNoDisp';	
	$('tblListados').style.display = 'none';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Paquetes");
	$objResponse->script("
	if ($('divFlotante').style.display == 'none') {
		$('divFlotante').style.display='';
		centrarDiv($('divFlotante'));
		
		$('txtDescripcionBusq').focus();
	}
        
        //xajax_calcularTotalPaquete(xajax.getFormValues('frmDatosPaquete'), $('txtIdCliente').value, $('lstTipoOrden').value);
        ");
	
	return $objResponse;
}


//ES EL LISTADO DE TEMPARIOS AL ABRIR AGREGAR TEMPARIO, SE HACE UNA BUSQUEDA
function listadoTempario($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 6, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);

	$startRow = $pageNum * $maxRows;
	
	$queryTipoOrden = sprintf("SELECT 
		id_tipo_orden,
		precio_tempario
	FROM sa_tipo_orden
	WHERE id_tipo_orden = %s",
	   valTpDato($valCadBusq[2],"int"));
	$rsTipoOrden = mysql_query($queryTipoOrden);
	if (!$rsTipoOrden) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowTipoOrden = mysql_fetch_assoc($rsTipoOrden);
	 
	$queryBaseUt = sprintf("SELECT 
		valor_parametro
	FROM pg_parametros_empresas
	WHERE descripcion_parametro = 1
		AND id_empresa = %s",
		valTpDato($valCadBusq[0],"int"));
	$rsBaseUt = mysql_query($queryBaseUt);
	if (!$rsBaseUt) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowBaseUt = mysql_fetch_assoc($rsBaseUt);
        
        if($rowBaseUt['valor_parametro'] == NULL){
            return $objResponse->alert("La empresa no tiene el parametro BASE UT configurado, contacte con personal de sistemas");
        }
	 
	$queryTempDiagnostico = sprintf("SELECT * FROM pg_parametros_empresas
	WHERE descripcion_parametro = 'MANO OBRA DIAGNOSTICO'
		AND id_empresa = %s",
		valTpDato($valCadBusq[0],"int"));
	$rsTempDiagnostico = mysql_query($queryTempDiagnostico);
	if (!$rsTempDiagnostico) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowTempDiagnostico = mysql_fetch_assoc($rsTempDiagnostico);
	
        //ya no sera necesario por empresa sino por unidad
	/*if (strlen($valCadBusq[0]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0],"int"));
	}*/
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_unidad_basica = %s",
			valTpDato($valCadBusq[1],"int"));
	}
		
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("codigo_tempario LIKE %s
		OR descripcion_tempario LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%","text"),
			valTpDato("%".$valCadBusq[3]."%","text"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_seccion = %s",
			valTpDato($valCadBusq[4],"text"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_subseccion = %s",
			valTpDato($valCadBusq[5],"text"));
	}
	
	$query = sprintf("SELECT 
		id_tempario_det,
		id_tempario,
		codigo_tempario,
		descripcion_tempario,
		garantia,
		id_modo,
		id_unidad_basica,
		importe_por_tipo_orden,
		(CASE id_modo
			WHEN '1' THEN precio_por_tipo_orden * %s / %s
			WHEN '2' THEN precio_por_tipo_orden
			WHEN '3' THEN precio_por_tipo_orden
			WHEN '4' THEN precio_por_tipo_orden
		END) AS total_por_tipo_orden,
		precio_por_tipo_orden,
		base_ut,
		ut,
		precio,
		total_importe,
		descripcion_modo,
		operador,
		descripcion_operador,
		costo,
		descripcion_subseccion,
		descripcion_seccion,
		abreviatura_seccion,
		id_seccion,
		id_subseccion
	FROM vw_sa_temparios_por_unidad %s",
		$rowTipoOrden['precio_tempario'], $rowBaseUt['valor_parametro'],
		$sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
		 		 		
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\nquery:".$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
	
	
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoTempario", "10%", $pageNum, "codigo_tempario", $campOrd, $tpOrd, $valBusq, $maxRows, ("C&oacute;digo"));
		$htmlTh .= ordenarCampo("xajax_listadoTempario", "28%", $pageNum, "descripcion_tempario", $campOrd, $tpOrd, $valBusq, $maxRows, ("Descripci&oacute;n"));
		$htmlTh .= ordenarCampo("xajax_listadoTempario", "2%", $pageNum, "garantia", $campOrd, $tpOrd, $valBusq, $maxRows, ("Garant&iacute;a"));
		$htmlTh .= ordenarCampo("xajax_listadoTempario", "20%", $pageNum, "descripcion_seccion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Secci&oacute;n"));
		$htmlTh .= ordenarCampo("xajax_listadoTempario", "12%", $pageNum, "descripcion_subseccion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Subsecci&oacute;n"));
		$htmlTh .= ordenarCampo("xajax_listadoTempario", "6%", $pageNum, "descripcion_modo", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Modo"));
		$htmlTh .= ordenarCampo("xajax_listadoTempario", "8%", $pageNum, "descripcion_operador", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Operador"));
		$htmlTh .= ordenarCampo("xajax_listadoTempario", "8%", $pageNum, "precio_por_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("UT/Precio"));
		$htmlTh .= ordenarCampo("xajax_listadoTempario", "8%", $pageNum, "total_por_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Total"));
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar5" : "trResaltar4";
		$contFila++;
		
		$garantia = "NO";
		$claseGarantia = "";
		if($row['garantia'] == 1){
			$claseGarantia = "class=\"divMsjInfo\"";
			$garantia = "S&Iacute;";
		}
		
		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
			$htmlTb .= "<td>"."<input type=\"hidden\" id=\"hdd_id_paquete\" name=\"hdd_id_paquete\" value=\"".$row['id_paq']."\"/>"."<button type=\"button\" onclick=\"xajax_asignarTempario('".$row['id_tempario_det']."',xajax.getFormValues('frmPresupuesto'));\" title=\"Seleccionar Tempario\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align='left'>".$row['codigo_tempario']."</td>";
			$htmlTb .= "<td align='left'>".utf8_encode($row['descripcion_tempario'])."</td>";
			$htmlTb .= "<td align='center' ".$claseGarantia.">".($garantia)."</td>";
			$htmlTb .= "<td align='left'>".utf8_encode($row['descripcion_seccion'])."</td>";
			$htmlTb .= "<td align='left'>".utf8_encode($row['descripcion_subseccion'])."</td>";
			$htmlTb .= "<td align='center'>".utf8_encode($row['descripcion_modo'])."</td>";
			$htmlTb .= "<td align='center'>".utf8_encode($row['descripcion_operador'])."</td>";
			$htmlTb .= "<td align='right'>".utf8_encode(number_format($row['precio_por_tipo_orden'],2,".",","))."</td>";
			$htmlTb .= "<td align='right'>".utf8_encode(number_format($row['total_por_tipo_orden'],2,".",","))."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTempario(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTempario(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoTempario(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTempario(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTempario(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"10\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoTemparioPorUnidad","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

//ES EL LISTADO DE TEMPARIOS DENTRO DEL PAQUETE, AL ABRIR AGREGAR-PAQUETE
function listado_tempario_por_paquetes($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 50, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$queryEmpresa = sprintf("SELECT paquete_combo FROM pg_empresa WHERE id_empresa = %s",
										valTpDato($valCadBusq[0], "int"));
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowEmpresa = mysql_fetch_array($rsEmpresa);
	
	
	for ($contTemp = 0; $contTemp <= strlen($valCadBusq[6]); $contTemp++) {
		$caracterTemp = substr($valCadBusq[6], $contTemp, 1);
		
		if ($caracterTemp != "|" && $caracterTemp != "")
			$cadenaTemp .= $caracterTemp;
		else {
			$arrayObjTemp[] = $cadenaTemp;
			$cadenaTemp = "";
		}
	}
	
	if ($valCadBusq[4] == 0) {
		$checked = "checked=\"checked\"";

		if (strlen($valCadBusq[0]) > 0) {
			$sqlBusq = sprintf(" WHERE sa_paquetes.id_empresa = %s AND sa_paquetes.id_paquete = %s AND sa_paq_unidad.id_unidad_basica = %s",
			valTpDato($valCadBusq[0],"int"),
			valTpDato($valCadBusq[1],"int"),
			valTpDato($valCadBusq[9],"int"));
		}
		
		$query = sprintf("SELECT 
			sa_paquetes.id_paquete,
			sa_paquetes.codigo_paquete,
			sa_paquetes.descripcion_paquete,
			sa_paquetes.id_empresa,
			sa_paquetes.fecha_rev,
			sa_paq_unidad.id_paq_unidad,
			sa_paq_unidad.id_unidad_basica,
			sa_paq_tempario.id_paq_tempario,
			sa_paq_tempario.id_tempario,
			sa_paq_tempario.ut AS utCombo,
			sa_paq_tempario.costo AS costoCombo,
			sa_tempario.id_tempario,
			sa_tempario.codigo_tempario,
			sa_tempario.descripcion_tempario,
			sa_tempario.id_modo,
			sa_tempario.operador,
			sa_tempario.precio,
			sa_tempario.costo,
			sa_tempario.garantia,
			sa_tempario.id_subseccion,
			sa_modo.id_modo,
			sa_modo.descripcion_modo,
			sa_operadores.id_operador,
			sa_operadores.nombre_operador,
			sa_operadores.descripcion_operador
		
		 FROM sa_paquetes
			INNER JOIN sa_paq_unidad ON (sa_paquetes.id_paquete = sa_paq_unidad.id_paquete)
			INNER JOIN sa_paq_tempario ON (sa_paquetes.id_paquete = sa_paq_tempario.id_paquete)
			INNER JOIN sa_tempario ON (sa_paq_tempario.id_tempario = sa_tempario.id_tempario)
			INNER JOIN sa_modo ON (sa_tempario.id_modo = sa_modo.id_modo)
			INNER JOIN sa_operadores ON (sa_tempario.operador = sa_operadores.id_operador)").$sqlBusq;
		$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	} else {
		$checked = "checked=\"checked\"";
		
		if($_GET['doc_type']==1) { //TIPO DE DOCUMENTO  PRESUPUESTO
		
			$campoIdEnc = "id_presupuesto";
			$tablaDocDetTemp = "sa_det_presup_tempario";
			$campoTablaIdDetTemp = "id_det_presup_tempario";
		} else {//ORDEN
			$campoIdEnc = "id_orden";
			$tablaDocDetTemp = "sa_det_orden_tempario";
			$campoTablaIdDetTemp = "id_det_orden_tempario";			
		}
			
		if (strlen($valCadBusq[0]) > 0) {
			$sqlBusq = sprintf(" WHERE %s.%s = %s AND %s.id_paquete= %s AND %s.estado_tempario <> 'DEVUELTO' ORDER BY %s.%s",
			$tablaDocDetTemp, $campoIdEnc, valTpDato($valCadBusq[5],"int"), $tablaDocDetTemp, valTpDato($valCadBusq[1],"int"),  $tablaDocDetTemp, $tablaDocDetTemp, $campoTablaIdDetTemp);
		}
			
		$query = sprintf("SELECT 
                        %s.%s,
			sa_tempario.id_tempario,
			sa_tempario.codigo_tempario,
			sa_tempario.descripcion_tempario,
			sa_tempario.id_modo,
			%s.base_ut_precio,
			sa_modo.descripcion_modo,
			%s.operador,
			sa_operadores.descripcion_operador,
			(case sa_tempario.id_modo
				when '1' then %s.ut
				when '2' then %s.precio
				when '3' then %s.costo
				when '4' then '4'
			end) AS precio,
			%s.ut,
			(case sa_tempario.id_modo
				when '1' then round((%s.ut * %s.precio_tempario_tipo_orden/%s.base_ut_precio), 2)
				when '2' then %s.precio
				when '3' then %s.costo
				when '4' then '4'
			end) AS importe_por_tipo_orden,
			(case %s.id_modo
				when '1' then %s.base_ut_precio
				when '2' then '-'
				when '3' then '-'
				when '4' then '-'
			end) AS base_ut
		FROM sa_modo
			INNER JOIN %s ON (sa_modo.id_modo = %s.id_modo)
			INNER JOIN sa_tempario ON (%s.id_tempario = sa_tempario.id_tempario)
			INNER JOIN sa_operadores ON (%s.operador = sa_operadores.id_operador)", 
			$tablaDocDetTemp, $campoTablaIdDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp).$sqlBusq;
		$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);			 
		
	}
        //echo $queryLimit;
	
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Paquetes");
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
	
		$htmlTh .= "<td width=\"10%\">".("C&oacute;digo")."</td>";
		$htmlTh .= "<td width=\"30%\">".("Descripci&oacute;n")."</td>";
		$htmlTh .= "<td width=\"10%\">Modo</td>";
		$htmlTh .= "<td width=\"10%\">Operador</td>";
		$htmlTh .= "<td width=\"10%\">UT</td>";
		$htmlTh .= "<td width=\"10%\">Precio</td>";
		$htmlTh .= "<td width=\"10%\">Base UT</td>";
		$htmlTh .= "<td width=\"10%\">Importe</td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= sprintf("<td id=\"tdChkManoObra\">"."<input type=\"checkbox\" id=\"chk_tempPaq\" %s onclick=\"selecAllChecks(this.checked, this.id, 'frmDatosPaquete'); xajax_calcularTotalPaquete(xajax.getFormValues('frmDatosPaquete'), $('txtIdCliente').value, $('lstTipoOrden').value);\"/>"."</td>", $checked);
	$htmlTh .= "</tr>";
	
	$sigValor = 1;
	$arrayObjTemp = NULL; 
	
	$cadenaMo=NULL;
	
	if ($valCadBusq[7] > 0) {
		for ($contTempAprob = 0; $contTempAprob <= strlen($valCadBusq[7]); $contTempAprob++) {
			$caracterTempAprob = substr($valCadBusq[7], $contTempAprob, 1);
			
			if ($caracterTempAprob != "," && $caracterTempAprob != "")
				$cadenaTempAprob .= $caracterTempAprob;
			else {
				$arrayObjTempAprob[] = $cadenaTempAprob;
				$cadenaTempAprob = "";
			}
		}
	}
	
	$totalMo = 0;
	if ($valCadBusq[4] == 0) {
		$queryTipoOrden = sprintf("SELECT 
			sa_tipo_orden.id_tipo_orden,
			sa_tipo_orden.precio_tempario
		FROM sa_tipo_orden
			WHERE sa_tipo_orden.id_tipo_orden = %s;",
			valTpDato($valCadBusq[2],"int"));
		$rsTipoOrden = mysql_query($queryTipoOrden);
		if (!$rsTipoOrden) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowTipoOrden = mysql_fetch_assoc($rsTipoOrden);
		
		$queryBaseUt = sprintf("SELECT 
			pg_parametros_empresas.valor_parametro
		FROM pg_parametros_empresas
		WHERE pg_parametros_empresas.descripcion_parametro = 1
			AND pg_parametros_empresas.id_empresa = %s",
			valTpDato($valCadBusq[0],"int"));
		$rsBaseUt = mysql_query($queryBaseUt);
		if (!$rsBaseUt) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowBaseUt = mysql_fetch_assoc($rsBaseUt);
	}
	 
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
		$srcIcono = "../img/iconos/ico_aceptar.gif";
		
		if ($valCadBusq[4]==1) { //CARGADO EN BD
                                        
			$checked = "checked=\"checked\"";
			$display = "style=\"display:none\"";
			
			$base_ut = $row['base_ut'];
			$importe_por_tipo_orden = $row['importe_por_tipo_orden'];//IMPORTANTE
			
			if ($row['id_modo'] == 1) {
				//CONDICION PAQUETE COMBO
				if ($rowEmpresa['paquete_combo']==1){
				$precio = "";
				$ut = $row['utCombo'];}
				else{
				$precio = "";
				$ut = $row['ut'];}
		
			} else {
				
				if ($rowEmpresa['paquete_combo']==1){
				$precio = number_format($row['costoCombo'],2,".",",");
				$ut = "";}
				else{
				$precio = number_format($row['precio'],2,".",",");
				$ut = "";}
			
			}
                        
                        if(esEditar()){
                            $botonEliminarIndividual = "<img src=\"../img/iconos/ico_quitar.gif\" class=\"puntero\" title=\"Eliminar\" onClick=\"eliminarTemparioIndividual(".$row['id_tempario'].", this, ".$importe_por_tipo_orden.", ".$valCadBusq[1].", ".$row[$campoTablaIdDetTemp].");\" >";//valcad1 es id del paquete
                        }
		} else { // SI ES NUEVO DOC
			if ($valCadBusq[8] == 0 || $valCadBusq[8] == "") {
				$checked = "checked=\"checked\"";
			} else {
				$checked = " ";
			
				if(isset($arrayObjTempAprob)){
					foreach($arrayObjTempAprob as $indiceTempAprob => $valorTempAprob) {
						if($valorTempAprob == $row['id_tempario']) {
							$checked = "checked=\"checked\"";
						}
					}
				}
			}
			$display = "";
			
			if($row['id_modo']==1) {//CONDICION PAQUETE COMBO ///SAPAQ
			
				if ($rowEmpresa['paquete_combo']==1){
				$query = sprintf("SELECT * FROM sa_tempario_det
				WHERE sa_tempario_det.id_tempario = %s
					AND sa_tempario_det.id_unidad_basica = %s", 
					valTpDato($row['id_tempario'],"int"),
					valTpDato($row['id_unidad_basica'],"int"));
				$rsValor = mysql_query($query);
				if (!$rsValor) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				$rowValor = mysql_fetch_assoc($rsValor);
				
				$ut = $rowValor['ut'];
				$precio = "";
				$importe_por_tipo_orden = $row['utCombo'] * $row['costoCombo'] / $rowBaseUt['valor_parametro'];
				$base_ut = $rowBaseUt['valor_parametro'];
				
				}
				
				else{
				$query = sprintf("SELECT * FROM sa_tempario_det
				WHERE sa_tempario_det.id_tempario = %s
					AND sa_tempario_det.id_unidad_basica = %s", 
					valTpDato($row['id_tempario'],"int"),
					valTpDato($row['id_unidad_basica'],"int"));
				$rsValor = mysql_query($query);
				if (!$rsValor) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				$rowValor = mysql_fetch_assoc($rsValor);
				
				$ut = $rowValor['ut'];
				$precio = "";
				$importe_por_tipo_orden = $rowValor['ut'] * $rowTipoOrden['precio_tempario'] / $rowBaseUt['valor_parametro'];
				$base_ut = $rowBaseUt['valor_parametro'];}
				

				
				if($ut == '')
					$paqConTempSinPrecio = 1;
				
				$asignacion_precio_tempario = ($ut == '') ? "<img src='../img/iconos/50.png' width='16' height='16' />" : "";
			} else {
				if ($row['id_modo'] == 2) { //PRECIO
				
						if ($rowEmpresa['paquete_combo']==1){
							$importe_por_tipo_orden = $row['costoCombo'];
							$precio = number_format($row['costoCombo'],2,".",",");
							$base_ut = $row['base_ut'];
							$ut = "";}
						else{
							$importe_por_tipo_orden = $row['precio'];
							$precio = number_format($row['precio'],2,".",",");
							$base_ut = $row['base_ut'];
							$ut = "";//NO ES MODO UT LE COLOCO VACIO
							}
					
				} else if ($row['id_modo'] == 3) { //COSTO
				
						if ($rowEmpresa['paquete_combo']==1){
							$importe_por_tipo_orden = $row['costoCombo'];
							$precio = number_format($row['costoCombo'],2,".",",");
							$base_ut = $row['base_ut'];
							$ut = "";//NO ES MODO UT LE COLOCO VACIO
							}
						else{
							$importe_por_tipo_orden = $row['costo'];
							$precio = number_format($row['costo'],2,".",",");
							$base_ut = $row['base_ut'];
							$ut = "";//NO ES MODO UT LE COLOCO VACIO
							}
					
				}
			}
		}
		
		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
			$htmlTb .= "<td>".$row['codigo_tempario']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_tempario'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['descripcion_modo'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['descripcion_operador'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($ut)."</td>";
			$htmlTb .= "<td align=\"right\">".$precio."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($base_ut)."</td>";
			$htmlTb .= "<td align=\"right\">";
				$htmlTb .= number_format($importe_por_tipo_orden,2,".",",");
				$htmlTb .= sprintf("<input type=\"hidden\" id=\"txtPrecTempPaq%s\" name=\"txtPrecTempPaq%s\" value=\"%s\"/>",
					$row['id_tempario'], $row['id_tempario'], $importe_por_tipo_orden);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".$asignacion_precio_tempario.$botonEliminarIndividual."</td>";
			$htmlTb .= sprintf("<td %s><input type=\"checkbox\" id=\"chk_tempPaq\" %s name=\"chk_tempPaq[]\" onclick=\"xajax_calcularTotalPaquete(xajax.getFormValues('frmDatosPaquete'), $('txtIdCliente').value, $('lstTipoOrden').value);\" value=\"%s\"/><input type=\"hidden\" id=\"txtIdeTempPaq%s\" name=\"txtIdeTempPaq%s\" value=\"%s\"/></td>",$display, $checked, $row['id_tempario'], $row['id_tempario'], $row['id_tempario'], $row['id_tempario']);
		$htmlTb .= "</tr>";
	
		$arrayObjTempAprob[] = $row['id_tempario'];
		$arrayObjTemp[] = $row['id_tempario'];		
		$totalMo = $totalMo + $importe_por_tipo_orden;
		$sigValor++;
	}

	$cadenaTempAprob = "";
	if(isset($arrayObjTempAprob)){
		foreach($arrayObjTempAprob as $indiceTempAprob => $valorTempAprob) {
			$cadenaTempAprob .= "|".$valorTempAprob;
		}
		$objResponse->assign("hddManObraAproXpaq","value",$cadenaTempAprob);
	}
	
	$cadenaTemp = "";
	if(isset($arrayObjTemp)){
		foreach($arrayObjTemp as $indiceTemp => $valorTemp) {
			$cadenaTemp .= "|".$valorTemp;
		}
		$objResponse->assign("hddObjTemparioPaq","value",$cadenaTemp);
	}
	
	if ($paqConTempSinPrecio == 1)
		$objResponse->script("$('hddTempEnPaqSinPrecio').value = 1;");
	else
		$objResponse->script("$('hddTempEnPaqSinPrecio').value = 0;");

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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_tempario_por_paquetes(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_tempario_por_paquetes(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listado_tempario_por_paquetes(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_tempario_por_paquetes(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_tempario_por_paquetes(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"10\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("tdListadoTempario","innerHTML",$htmlTblIni./*$htmlTf.*/$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	if ($valCadBusq[4] == 1)
		 $objResponse->script("
		$('tdChkManoObra').style.display = 'none';");
			
	//$objResponse->assign("txtTotalMoPaq","value",$totalMo);
	
	$objResponse->script("
	$('tblListados').style.display = 'none';
	$('tblListadoTot').style.display = 'none';");
	
	$objResponse->script("
	if ($('divFlotante').style.display == 'none') {
		$('divFlotante').style.display='';
		centrarDiv($('divFlotante'));
		$('txtDescripcionBusq').focus();
	}
	");

	$objResponse->script("
	$('tdEncabPaquete').innerHTML = 'PAQUETE ' + $('txtCodigoPaquete').value + ' ' + $('txtDescripcionPaquete').value;        
        //xajax_calcularTotalPaquete(xajax.getFormValues('frmDatosPaquete'), $('txtIdCliente').value, $('lstTipoOrden').value);
	");

	return $objResponse;
}

//ES EL LISTADO DE TOT AL ABRIR AGREGAR TOT
function listado_tot($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 6, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	$valCadBusq = explode("|", $valBusq);
	
	$startRow = $pageNum * $maxRows;
	
	if (strlen($valCadBusq[0]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf(" vw_sa_orden_tot.estatus = 1
		AND vw_sa_orden_tot.id_empresa = %s
		AND vw_sa_orden_tot.id_orden_servicio= %s)",
			valTpDato($valCadBusq[0],"int"),
			valTpDato($valCadBusq[1],"int"));
	}

	if ($valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("vw_sa_orden_tot.id_orden_servicio LIKE %s
		OR vw_sa_orden_tot.nombre LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%","text"),
			valTpDato("%".$valCadBusq[3]."%","text"));
	}
	

	$query = sprintf("SELECT * FROM vw_sa_orden_tot %s", $sqlBusq);
		
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Trabajo Otros Talleres (T.O.T)");
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td width=\"8%\">Fecha</td>";
		$htmlTh .= "<td width=\"10%\">Nro. T.O.T</td>";
		$htmlTh .= "<td width=\"12%\">Nro. Factura</td>";
		$htmlTh .= "<td width=\"46%\">Proveedor</td>";
		$htmlTh .= "<td width=\"10%\">Tipo Pago</td>";
		$htmlTh .= "<td width=\"14%\">Monto</td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
		
		$srcIcono = "../img/iconos/ico_aceptar.gif";
		
		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarTot('".$row['id_orden_tot']."', document.getElementById('lstTipoOrden').value);\" title=\"Seleccionar T.O.T\"><img src=\"".$srcIcono."\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fecha_factura_proveedor']))."</td>";
			$htmlTb .= "<td align=\"right\" idtotoculta=\"".$row['id_orden_tot']."\">".$row['numero_tot']."</td>";//nuevo gregor
			$htmlTb .= "<td align='right'>".utf8_encode($row['numero_factura_proveedor'])."</td>";
			$htmlTb .= "<td align='left'>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align='center'>".utf8_encode($row['tipo_pago'])."</td>";
			$htmlTb .= "<td align='right'>".utf8_encode(number_format($row['monto_subtotal'],2,".",","))."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_tot(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_tot(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listado_tot(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_tot(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_tot(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"10\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoTot","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->script("
	$('tblListados').style.display = 'none';
	$('tblBuscarPaquete').style.display = '';");
	
	$objResponse->script("
	if ($('divFlotante').style.display == 'none') {
		$('divFlotante').style.display='';
		centrarDiv($('divFlotante'));
		$('txtDescripcionBusq').focus();
	}
	$('divFlotante2').style.display = 'none';
	");
	return $objResponse;
}

//ES EL LISTADO AL ABRIR AGREGAR VALE DE RECEPCION
function listadoValeRecepcion($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
		
	if($_GET['ret'] == 5) {
		$query = sprintf("SELECT vw_sa_orden.placa FROM vw_sa_orden
		WHERE vw_sa_orden.id_orden = %s",
			$_GET['id']);
		$rs = mysql_query($query);
		$row = mysql_fetch_array($rs);
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_sa_vales_recepcion.placa = %s",
			valTpDato($row['placa'], "text"));
	}
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_sa_vales_recepcion.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("vw_sa_vales_recepcion.nombre LIKE %s
		OR vw_sa_vales_recepcion.apellido LIKE %s
		OR vw_sa_vales_recepcion.nombre_cliente_pago LIKE %s
		OR vw_sa_vales_recepcion.apellido_cliente_pago LIKE %s
		OR vw_sa_vales_recepcion.placa LIKE %s
		OR vw_sa_vales_recepcion.numeracion_recepcion LIKE %s)", //id recepcion numeracion recepcion
		    valTpDato("%".$valCadBusq[1]."%","text"),
		    valTpDato("%".$valCadBusq[1]."%","text"),
		    valTpDato("%".$valCadBusq[1]."%","text"),
		    valTpDato("%".$valCadBusq[1]."%","text"),
			valTpDato("%".$valCadBusq[1]."%","text"),
			valTpDato("%".$valCadBusq[1]."%","text"));
	} 
	
	$query = sprintf("SELECT * FROM vw_sa_vales_recepcion %s", $sqlBusq);
				
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoValeRecepcion", "10%", $pageNum, "fecha_entrada", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Entrada");	
		$htmlTh .= ordenarCampo("xajax_listadoValeRecepcion", "10%", $pageNum, "numeracion_recepcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nro Recepcion"));
		$htmlTh .= ordenarCampo("xajax_listadoValeRecepcion", "5%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo Vale"));
		$htmlTh .= ordenarCampo("xajax_listadoValeRecepcion", "12%", $pageNum, "nom_uni_bas", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cat&aacute;logo"));	
		$htmlTh .= ordenarCampo("xajax_listadoValeRecepcion", "22%", $pageNum, "des_modelo", $campOrd, $tpOrd, $valBusq, $maxRows, "Modelo");
		$htmlTh .= ordenarCampo("xajax_listadoValeRecepcion", "14%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, "Placa");
		$htmlTh .= ordenarCampo("xajax_listadoValeRecepcion", "24%", $pageNum, "chasis", $campOrd, $tpOrd, $valBusq, $maxRows, "Chasis");
		/*$htmlTh .= ordenarCampo("xajax_listadoValeRecepcion", "9%", $pageNum, "ci", $campOrd, $tpOrd, $valBusq, $maxRows, "Cedula / RIF");
		$htmlTh .= ordenarCampo("xajax_listadoValeRecepcion", "19%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente Recepci&oacute;n"));
		$htmlTh .= ordenarCampo("xajax_listadoValeRecepcion", "9%", $pageNum, "ci_cliente_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Cedula / RIF");
		$htmlTh .= ordenarCampo("xajax_listadoValeRecepcion", "19%", $pageNum, "nombre_cliente_pago", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente Pago"));*/
		$htmlTh .= ordenarCampo("xajax_listadoValeRecepcion", "8%", $pageNum, "estado", $campOrd, $tpOrd, $valBusq, $maxRows, ("Estado"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
		$estado = ($row['estado'] == 0) ? "<img src='../img/iconos/ico_verde.gif' width='12' height='12' />" : "<img src='../img/iconos/ico_rojo.gif' width='12' height='12' />";
		
		$htmlTb .= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\">";
			$htmlTb .= "<td>".sprintf("<button type=\"button\" onclick=\"
			if(%s == 0) 
				xajax_asignarValeRecepcion('".$row['id_recepcion']."', '".$_GET['acc']."', xajax.getFormValues('frmTotalPresupuesto'), 'SI');
			else{
				if($('hddAgregarOrdenFacturada').value == '') {
					 if(confirm('El vale no se puede tomar debido a que tiene por lo menos una Orden facturada. Desea agregarlo?')) 
						xajax_formClave(xajax.getFormValues('frmTotalPresupuesto'), 'agreg_vr_fact');
				} else
					xajax_asignarValeRecepcion('".$row['id_recepcion']."', '".$_GET['acc']."', xajax.getFormValues('frmTotalPresupuesto'), 'SI');
			}\" title=\"Seleccionar Vale de Recepcion\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>", $row['estado'])."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fecha_entrada']))."</td>";
			$htmlTb .= "<td align=\"right\" idRecepcionOculta=\"".$row["id_recepcion"]."\" >".$row['numeracion_recepcion']."</td>";
			$htmlTb .= "<td align=\"center\" >".$row['descripcion']."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nom_uni_bas'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['des_modelo'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['placa']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['chasis'])."</td>";
			/*$htmlTb .= "<td align=\"center\">".$row['lci'].$row['ci']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre']." ".$row['apellido'])."</td>";
			$htmlTb .= "<td align=\"right\">".$row['lci_cliente_pago'].$row['ci_cliente_pago']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente_pago']." ".$row['apellido_cliente_pago'])."</td>";*/
			$htmlTb .= "<td align=\"center\">".$estado."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoValeRecepcion(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoValeRecepcion(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoValeRecepcion(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoValeRecepcion(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoValeRecepcion(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "<tr>";
		$htmlTblFin .= "<td align=\"center\" colspan=\"10\" class=\"divMsjInfo\">";
			$htmlTblFin .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTblFin .= "<tr>";
				$htmlTblFin .= "<td width=\"25\"><img src=\"../img/iconos/ico_info2.gif\" width=\"25\"/></td>";
				$htmlTblFin .= "<td width=\"1375\" align=\"center\">";
					$htmlTblFin .= "<table>";
	  	 			$htmlTblFin .= "<tr>";
						$htmlTblFin .= "<td id=\"tdImgAccionVerOrden\"><img src=\"../img/iconos/ico_verde.gif\" /></td>";
						$htmlTblFin .= "<td id=\"tdDescripAccionVerOrden\">Normal</td>";
						$htmlTblFin .= "<td>&nbsp;</td>";
						$htmlTblFin .= "<td id=\"tdImgAccionVerOrdenDesactivado\"><img src=\"../img/iconos/ico_rojo.gif\" /></td>";
						$htmlTblFin .= "<td id=\"tdDescripAccionVerOrdenDesactivado\">Tiene por lo menos una Orden Facturada</td>";
  		 			$htmlTblFin .= "</tr>";
					$htmlTblFin .= "</table>";
				$htmlTblFin .= "</td>";
			$htmlTblFin .= "</tr>";
			$htmlTblFin .= "</table>";
		$htmlTblFin .= "</td>";
	$htmlTblFin .= "</tr>";
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"10\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->script("
		$('trBuscarPresupuesto').style.display='none';
		$('tblArticulo').style.display='none';
		$('tblListados').style.display='';
		$('tblBuscarPaquete').style.display='none';
		$('btnAsignarPaquete').style.display='none';
		$('tdListadoPaquetes').style.display='none';
		$('tblListadoTempario').style.display='none';  
		$('tblGeneralPaquetes').style.display='none';
		$('tblNotas').style.display='none';
		$('tblListadoTot').style.display='none';");
		
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML", htmlentities("Vales de Recepcion"));
	$objResponse->assign("tblListados","width","990px");
	$objResponse->script("
	if ($('divFlotante').style.display == 'none') {
		$('divFlotante').style.display='';
		centrarDiv($('divFlotante'));
	
		$('txtPalabra').focus();
		$('txtPalabra').select();
	}
	$('divFlotante2').style.display='none';");
	
	return $objResponse;
}

//INICIALIZA LA ORDEN, HABILITA LOS BOTONES, ESTABLECE USUARIO - FECHA
function nuevoDcto() {
	$objResponse = new xajaxResponse();
	
	$queryUsuario = sprintf("SELECT * FROM vw_iv_usuarios WHERE id_usuario = ".$_SESSION['idUsuarioSysGts']);
	$rsUsuario = mysql_query($queryUsuario);
	if (!$rsUsuario) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowUsuario = mysql_fetch_assoc($rsUsuario);	 
	
	
	$objResponse->script("
	document.forms['frmPresupuesto'].reset();
	document.forms['frmTotalPresupuesto'].reset();
	$('txtDireccionCliente').innerHTML = '';
	
	$('imgFechaVencimientoPresupuesto').style.visibility = '';
	$('tblLeyendaOrden').style.display = '';
	
	$('txtFechaVencimientoPresupuesto').readOnly = false;
	$('txtNumeroPresupuestoPropio').readOnly = false;
	$('txtNumeroReferencia').readOnly = false;
	
	//$('lstMoneda').disabled = false;
	$('btnInsertarPaq').disabled = false;
	$('btnEliminarPaq').disabled = false;
	$('btnInsertarEmp').disabled = false;
	$('btnInsertarCliente').disabled = false;
	$('btnInsertarTemp').disabled = false;
	$('btnEliminarTemp').disabled = false;
	//$('btnPendiente').disabled = true;
	$('btnInsertarArt').disabled = false;
	$('btnEliminarTemp').disabled = false;
	$('btnInsertarTemp').disabled = false;
	$('btnInsertarNota').disabled = false;
	$('btnEliminarNota').disabled = false;
	$('btnInsertarTot').disabled = false;
	$('btnEliminarTot').disabled = false;
	//$('btnImprimirDoc').disabled = true;//ya no existe, genera error y no permite la continuacion
	$('btnEliminarArt').disabled = false;
	$('btnGuardar').disabled = '';
	$('btnCancelar').disabled = '';");
	
	$objResponse->assign("txtFechaPresupuesto","value",date("d-m-Y"));
	$objResponse->assign("hddIdEmpleado","value",$rowUsuario['id_empleado']);
	$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowUsuario['nombre_empleado']." ".$rowUsuario['apellido']));
	
	//$objResponse->script(sprintf("$('tdDespachado').style.display = 'none';"));
		
	return $objResponse;
}


	
	
	
		
		


//LA CONSTRUYE FORMCLAVE(), LUEGO ESTA ES LA QUE PROCESA LAS CLAVES
function validarClaveDescuento($valFormDescuento, $valFormTotalDcto) {
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT sa_claves.clave AS contrasena
	FROM pg_empleado
		INNER JOIN pg_usuario ON (pg_empleado.id_empleado = pg_usuario.id_empleado)
		INNER JOIN sa_claves ON (pg_empleado.id_empleado = sa_claves.id_empleado)
	WHERE sa_claves.modulo = '%s'
		AND pg_usuario.id_usuario = %s",
		$valFormDescuento['hddAccionObj'],
		$_SESSION['idUsuarioSysGts']);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
		
	if ($row['contrasena'] == md5($valFormDescuento['txtContrasenaAcceso'])) {
		$objResponse->alert('Acceso Concedido');
		
		switch($valFormDescuento['hddAccionObj']) {
			case 'elim_tot': 
				$objResponse->script("
				$('divFlotante2').style.display='none';
				$('btnEliminarTot').readOnly = false;
				$('btnEliminarTot').focus();");
				break;
			
			case 'agreg_vr_fact': 
				$objResponse->script("
				$('divFlotante2').style.display='none';
				$('hddAgregarOrdenFacturada').value = 1;");
				break;
			
			case 'elim_not_aprob': 
				$objResponse->script("
				$('divFlotante2').style.display='none';
				$('btnEliminarNota').readOnly = false;
				$('btnEliminarNota').focus();");
				break;
			
			case 'elim_temp_aprob':	
				$objResponse->script("
				$('divFlotante2').style.display='none';
				$('btnEliminarTemp').readOnly = false;
				$('btnEliminarTemp').focus();");
				break;
			
			case 'elim_art_aprob':	
				$objResponse->script("
				$('divFlotante2').style.display='none';
				$('btnEliminarArt').readOnly = false;
				$('btnEliminarArt').focus();");
				break;
			
			case 'acc_ord_gtia':	
				$objResponse->script("
				$('divFlotante2').style.display='none';
				$('hddAgregarOrdenGarantia').value = 1;");
				break;
			
			case 'acc_dup_mo':	
				$objResponse->script("
				$('divFlotante2').style.display='none';
				$('hddDuplicarManoObra').value = 1;");
				break;
			
			case 'acc_dup_rep':	
				$objResponse->script("
				$('divFlotante2').style.display='none';
				$('hddDuplicarRepuesto').value = 1;");
				break;
		}			
	} else {
		$objResponse->alert(("Clave Invalida"));
		$objResponse->script("$('txtContrasenaAcceso').focus();");
	}
	
	return $objResponse;
}


//VALIDA PERMISO DE VARIAS COSAS, ENTRE ELLAS DESCUENTO GENERAL BUSCA EN PG_CLAVES Y LUEGO EN SA_CLAVES
function validarPermiso($valForm, $valFormDatosArticulo) {
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
	$totalRowsPermiso = mysql_num_rows($rsPermiso);
	
	if ($totalRowsPermiso == 0) {
		$queryPermiso = sprintf("SELECT *
		FROM pg_empleado
			INNER JOIN pg_usuario ON (pg_empleado.id_empleado = pg_usuario.id_empleado)
			INNER JOIN sa_claves ON (pg_empleado.id_empleado = sa_claves.id_empleado)
		WHERE pg_usuario.id_usuario = %s
			AND sa_claves.clave = MD5(%s)
			AND sa_claves.modulo = %s;",
			valTpDato($_SESSION['idUsuarioSysGts'], "int"),
			valTpDato($valForm['txtContrasena'], "text"),
			valTpDato($valForm['hddModulo'], "text"));
		$rsPermiso = mysql_query($queryPermiso);
		if (!$rsPermiso) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRowsPermiso = mysql_num_rows($rsPermiso);

	}
	
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	if ($totalRowsPermiso > 0) {
		if ($valForm['hddModulo'] == "edc_dcto_ord") {
			$objResponse->script("$('txtDescuento').readOnly = false;");
			$objResponse->script("$('divFlotante').style.display = 'none';");
			$objResponse->script("
			$('txtDescuento').focus();
			$('txtDescuento').select();");
			
		} else if ($valForm['hddModulo'] == "agreg_dcto_adnl") {
			$objResponse->script("$('divFlotante').style.display = 'none';");
			
			$objResponse->script("
			$('tblListados1').style.display = 'none';
			$('tblArticulosSustitutos').style.display = 'none';
			$('tblClaveDescuento').style.display = 'none';
			$('tblPorcentajeDescuento').style.display = '';
			");
			
			$objResponse->assign("hddAccionObj","value",$valForm['hddModulo']);
			
			$objResponse->assign("tdFlotanteTitulo2","innerHTML",utf8_encode("Descuentos Adicionales"));
			$objResponse->script("
			if ($('divFlotante2').style.display == 'none') {
				$('divFlotante2').style.display = '';
				centrarDiv($('divFlotante2'));
			}");
			
		} else if ($valForm['hddModulo'] == "iv_catalogo_venta_precio_venta") {
			$objResponse->script("$('btnCancelarPermiso').click();");
			$objResponse->script(sprintf("$('lstPrecioArt').onchange = function(){ xajax_asignarPrecio('%s',this.value); }",
				$valFormDatosArticulo['hddIdArt']));
				
		} else if ($valForm['hddModulo'] == "iv_catalogo_venta_precio_editado") {
			$objResponse->assign("hddBajarPrecio","value","subir");
			$objResponse->script("$('btnCancelarPermiso').click();");
			$objResponse->script("$('txtPrecioArtRepuesto').readOnly = false;");
			$objResponse->script("$('imgDesbloquearPrecio').style.display = 'none';");
			$objResponse->script("
			$('txtPrecioArtRepuesto').focus();
			$('txtPrecioArtRepuesto').select();");
			
		} else if ($valForm['hddModulo'] == "iv_catalogo_venta_precio_editado_bajar") {
			$objResponse->assign("hddBajarPrecio","value","bajar");
			$objResponse->script("$('btnCancelarPermiso').click();");
			$objResponse->script("$('txtPrecioArtRepuesto').readOnly = false;");
			$objResponse->script("$('imgDesbloquearPrecio').style.display = 'none';");
			$objResponse->script("
			$('txtPrecioArtRepuesto').focus();
			$('txtPrecioArtRepuesto').select();");
			
		} else if ($valForm['hddModulo'] == "iv_precio_editado_debajo_costo") {
			$objResponse->assign("hddBajarPrecio","value","debajo_costo");
			$objResponse->script("$('btnCancelarPermiso').click();");
			$objResponse->script("$('txtPrecioArtRepuesto').readOnly = false;");
			$objResponse->script("$('imgDesbloquearPrecio').style.display = 'none';");
			$objResponse->script("
			$('txtPrecioArtRepuesto').focus();
			$('txtPrecioArtRepuesto').select();");
			
		}else if ($valForm['hddModulo'] == "sa_precio_editado_tempario") {
			$objResponse->script("
			$('txtPrecioTemp').onchange = function(){
                                $('txtPrecioTemp').value = $('txtPrecioTemp').value.replace(',', '');
				$('txtPrecio').value = $('txtPrecioTemp').value.replace(',', '');
				$('txtModoTemp').value = 'PRECIO';
				$('txtIdModoTemp').value = 2;
			}");
			$objResponse->script("$('btnCancelarPermiso').click();");
			$objResponse->script("$('txtPrecioTemp').readOnly = false;");
			//$objResponse->script("$('imgDesbloquearPrecioTemp').style.display = 'none';");
			$objResponse->script("
			$('txtPrecioTemp').focus();
			$('txtPrecioTemp').select();");
		}else if($valForm['hddModulo'] == "sa_porcentaje_tot"){
                        $objResponse->script("$('txtPorcentaje').readOnly = false;");
			$objResponse->script("$('divFlotante').style.display = 'none';");
			$objResponse->script("$('tblPermiso').style.display = 'none';");
			$objResponse->script("$('tblListadoTot').style.display = '';");
                        $objResponse->script("$('divFlotante').style.display = '';");
			$objResponse->script("centrarDiv($('divFlotante'));");
			
			$objResponse->script("
			$('txtPorcentaje').focus();");
                }
	} else {
		$objResponse->alert("Permiso No Autorizado");
		$objResponse->script("$('btnCancelarPermiso').click();");
	}
	
	return $objResponse;
}

//SI LA NOTA ESTA EN PRESUPUESTO PREGUNTA SI DEESA ELIMINARLA TAMBIEN
function validarSiLasNotasCargadasEstanRelacionadasConPresupuesto($valFormNotas) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$sw = 0;

	if (isset($valFormNotas['cbxItmNota'])) {
		foreach($valFormNotas['cbxItmNota'] as $indiceItm => $valorItm) {
			$idDetNota = $valFormNotas[sprintf('hddIdPedDetNota%s', $valorItm)];
					
			$queryExistenNotasEnPresupuesto = sprintf("SELECT COUNT(*) AS nro_item
			FROM sa_orden
				INNER JOIN sa_presupuesto ON (sa_orden.id_orden = sa_presupuesto.id_orden)
				INNER JOIN sa_det_presup_notas ON (sa_presupuesto.id_presupuesto = sa_det_presup_notas.id_presupuesto)
			WHERE sa_det_presup_notas.id_det_orden_nota = %s",
				valTpDato($idDetNota, "int"));
			$rsDetNotaEnPresupuesto = mysql_query($queryExistenNotasEnPresupuesto);
			if (!$rsDetNotaEnPresupuesto) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$rsDetNotaEnPresupuesto);
			$row = mysql_fetch_assoc($rsDetNotaEnPresupuesto);
			
			if($row['nro_item'] > 0)
				$sw = 1;
		}
	}
	
	if($sw == 1) {
		$objResponse->script("
		if($('btnEliminarNota').readOnly == true) {
			if(confirm('La(s) nota(s) que desea eliminar esta(n) relacionada(s) con un presupuesto. Desea Eliminarla(s)?')) {
				xajax_formClave(xajax.getFormValues('frmTotalPresupuesto'), 'elim_not_aprob'); 
				$('tblPorcentajeDescuento').style.display='none';
				$('tblClaveDescuento').style.display = '';
			}
		} else
			xajax_eliminarNotaEnPresupuesto(xajax.getFormValues('frmListaNota'));");
	} else
		$objResponse->script("xajax_eliminarNotaEnPresupuesto(xajax.getFormValues('frmListaNota'));");
		
	mysql_query("COMMIT;");
	
	return $objResponse;
}

//SI LOS ARTICULOS ESTAN EN PRESUPUESTO PREGUNTA SI DESEA ELIMINARLOS
function validarSiLosArticulosCargadosEstanRelacionadosConPresupuesto($valFormArt) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$sw = 0;

	if (isset($valFormArt['cbxItm'])) {
		foreach($valFormArt['cbxItm'] as $indiceItm => $valorItm) {
			$idDetArt = $valFormArt[sprintf('hddIdPedDet%s', $valorItm)];
								
			$queryExistenArtEnPresupuesto = sprintf("SELECT COUNT(*) AS nro_item
			FROM sa_orden
				INNER JOIN sa_presupuesto ON (sa_orden.id_orden = sa_presupuesto.id_orden)
				INNER JOIN sa_det_presup_articulo ON (sa_presupuesto.id_presupuesto = sa_det_presup_articulo.id_presupuesto)
			WHERE sa_det_presup_articulo.id_det_orden_articulo = %s",
				valTpDato($idDetArt, "int"));
			$rsDetArtEnPresupuesto = mysql_query($queryExistenArtEnPresupuesto);
			if (!$rsDetArtEnPresupuesto) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$rsDetArtEnPresupuesto);
			$row = mysql_fetch_assoc($rsDetArtEnPresupuesto);
			
			if($row['nro_item'] > 0)
				$sw = 1;
		}
	}
		
	if($sw == 1) {
		$objResponse->script("
		if($('btnEliminarArt').readOnly == true) {
			if(confirm('El(Los) Articulo(s) que desea eliminar esta(n) relacionado(s) con un presupuesto. Desea Eliminarlo(s)?')) {
				xajax_formClave(xajax.getFormValues('frmTotalPresupuesto'), 'elim_art_aprob'); 
				$('tblPorcentajeDescuento').style.display='none';
				$('tblClaveDescuento').style.display = '';
			}
		} else
			xajax_eliminarArticuloEnPresupuesto(xajax.getFormValues('frmListaArticulo'));");
	} else
		$objResponse->script("xajax_eliminarArticuloEnPresupuesto(xajax.getFormValues('frmListaArticulo'));");
		
	mysql_query("COMMIT;");
	
	return $objResponse;
}

//SI LOS TEMPARIOS ESTAN EL PRESUPUESTO PREGUNTA SI DESEA LIMINARLOS
function validarSiLosTempariosCargadosEstanRelacionadosConPresupuesto($valFormTemp) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$sw = 0;

	if (isset($valFormTemp['cbxItmTemp'])) {
		foreach($valFormTemp['cbxItmTemp'] as $indiceItm => $valorItm) {
			$idDetTemp = $valFormTemp[sprintf('hddIdPedDetTemp%s', $valorItm)];
								
			$queryExistenTempEnPresupuesto = sprintf("SELECT COUNT(*) AS nro_item
			FROM sa_orden
				INNER JOIN sa_presupuesto ON (sa_orden.id_orden = sa_presupuesto.id_orden)
				INNER JOIN sa_det_presup_tempario ON (sa_presupuesto.id_presupuesto = sa_det_presup_tempario.id_presupuesto)
			WHERE sa_det_presup_tempario.id_det_orden_tempario = %s",
				valTpDato($idDetTemp, "int"));
			$rsDetTempEnPresupuesto = mysql_query($queryExistenTempEnPresupuesto);
			if (!$rsDetTempEnPresupuesto) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$rsDetTempEnPresupuesto);
			$row = mysql_fetch_assoc($rsDetTempEnPresupuesto);
			
			if($row['nro_item'] > 0)
				$sw = 1;
		}
	}
		
	if($sw == 1) {
		$objResponse->script("
		if($('btnEliminarTemp').readOnly == true) {
			if(confirm('El(Los) tempario(s) que desea eliminar esta(n) relacionado(s) con un presupuesto. Desea Eliminarlo(s)?')) {
				xajax_formClave(xajax.getFormValues('frmTotalPresupuesto'), 'elim_temp_aprob'); 
				$('tblPorcentajeDescuento').style.display='none';
				$('tblClaveDescuento').style.display = '';
			}
		} else
			xajax_eliminarTemparioEnPresupuesto(xajax.getFormValues('frmListaManoObra'));");
	} else
		$objResponse->script("xajax_eliminarTemparioEnPresupuesto(xajax.getFormValues('frmListaManoObra'));");
		
	mysql_query("COMMIT;");
	
	return $objResponse;
}

//AL APROBAR DESAPROBAR UN PAQUETE EN ORDEN, SE ACTIVA DESACTIVA UN CHECKED CON ONCHANGE ESTA FUNCION, VERIFICA QUE NO LO DESAPRUEBE
//SI ESTA EN SOLICITUD O ALGO ADVIERTE QUE NO DEBE TENER SOLICITUD, NO SE USA PORQUE SIEMPRE ESTA DISABLED EL CHECKBOX
function validarSiTieneAlmacenAsignado($valorCheckPaq, $idPaquete, $idOrden, $obj) {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT 
		count(*) AS nro_items_paq
	FROM sa_det_orden_articulo
		INNER JOIN sa_det_solicitud_repuestos ON (sa_det_orden_articulo.id_det_orden_articulo = sa_det_solicitud_repuestos.id_det_orden_articulo)
		INNER JOIN sa_solicitud_repuestos ON (sa_det_solicitud_repuestos.id_solicitud = sa_solicitud_repuestos.id_solicitud)
	WHERE sa_det_orden_articulo.id_orden = %s
		AND sa_det_solicitud_repuestos.id_estado_solicitud = 2
		AND sa_det_orden_articulo.id_paquete = %s", 
		valTpDato($idOrden,"int"),
		$idPaquete);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if ($row['nro_items_paq'] > 0) {		
		$objResponse->script(sprintf("		
		if ($('hddValorCheckAprobPaq%s').value == 1) {
			alert('Este paquete contiene articulos con solicitud aprobada. Si desea desaprobarlo elimine los almacenes escogidos.');
			//lo coloque checkado
			$('cbxItmPaqAprob%s').checked = true;
		} else {
			$('hddValorCheckAprobPaq%s').value = 1;
		}",
			$valorCheckPaq,
			$valorCheckPaq,
			$valorCheckPaq));
		
		$objResponse->script("xajax_calcularTotalDcto()");
	} else {
		$objResponse->script(sprintf("		
		if($('hddValorCheckAprobPaq%s').value == 0) {
			$('hddValorCheckAprobPaq%s').value = 1;
		} else {
			$('hddValorCheckAprobPaq%s').value = 1;
		}",
			$valorCheckPaq,
			$valorCheckPaq,
			$valorCheckPaq));
		
		$objResponse->script("xajax_calcularTotalDcto()");
	}
	
	//$query = sprintf("", );
	//$('hddValorCheckAprobPaq%s').checked = true

	return $objResponse;
}

//NUEVO O EDITAR, SE USA PARA MOSTRAR/DESACTIVAR SECCIONES BOTONES Y TODO DEPENDIENDO DEL DOCUMENTO
function validarTipoDocumento($tipoDocumento, $idDocumento, $idEmpresa, $accion, $valFormDcto) {
	$objResponse = new xajaxResponse();
			
	if ($tipoDocumento == 1) {
		$objResponse->script("
		$('divFlotante2').style.display='none';
		$('divFlotante').style.display='none';
		$('tdCodigoMecanico').style.display='none';
		$('tdNombreMecanico').style.display='none';
		$('btnGuardar').disabled = '';
		$('btnCancelar').disabled = '';
		$('tdEtiqTipoDocumento').innerHTML = 'Neto Presupuesto:';
		$('lydTipoDocumento').innerHTML = 'Datos del Presupuesto:';
		$('tdIdDocumento').innerHTML = 'Nro Presupuesto:';
		$('tdFechaVecDoc').style.display='';
		//$('btnImprimirDoc').style.display = '';//ya no existe generara error si se usa
		$('tdPresupuestosPendientes').style.display = 'none';");//ANTES Id Presupuesto: ahora Nro Presupuesto:
	} else {	
		//dependiendo si se muestra o no el mecanico por parametros generales coloco el display		
		$objResponse->script("
		$('divFlotante2').style.display='none';
		$('divFlotante').style.display='none';
		$('btnGuardar').disabled = '';
		$('btnCancelar').disabled = '';
		$('tdEtiqTipoDocumento').innerHTML = 'Neto Orden:';
		$('lydTipoDocumento').innerHTML = 'Datos de la Orden';
		$('tdIdDocumento').innerHTML = 'Nro Orden:';
		$('tdFechaVecDoc').style.display='none';
		//$('btnImprimirDoc').style.display = ''; //ya no existe
		");//ANTES Id Orden: ahora Nro Orden:
	}
			
	if ($accion==1 || $accion==3) {
		if ($accion==1) {
			$objResponse->script("xajax_nuevoDcto(); ");
			if($tipoDocumento==1)
				$objResponse->script("$('tdTituloPaginaServicios').innerHTML = 'Nuevo Presupuesto';");
			if($tipoDocumento==2)
				$objResponse->script("$('tdTituloPaginaServicios').innerHTML = 'Nueva Orden de Servicio';");
				
			 $objResponse->script("
				$('tdRepAprob').style.display = 'none';
				$('tdPaqAprob').style.display = 'none';
				$('tdNotaAprob').style.display = 'none';
				$('tdTempAprob').style.display = 'none';
				$('tdTotAprob').style.display = 'none';");	
		} else {
			if ($accion==3) {
				$objResponse->script("xajax_cargarDcto('".$idDocumento."', xajax.getFormValues('frmTotalPresupuesto'));	desbloquearForm();");
				
				if ($tipoDocumento==1)
					$objResponse->script("$('tdTituloPaginaServicios').innerHTML = 'Editar Presupuesto de Venta';");
				if ($tipoDocumento==2) {
					if($_GET['ret'] != 5) {
						$objResponse->script("$('tdTituloPaginaServicios').innerHTML = 'Editar Orden de Servicio';
							$('btnInsertarCliente').disabled = true;
							$('tblLeyendaOrden').style.display = '';");
					} else {
						$objResponse->script("$('tdTituloPaginaServicios').innerHTML = 'Retrabajo Orden de Servicio';
							desbloquearForm();
							$('tblMotivoRetrabajo').style.display = '';
							$('tblLeyendaOrden').style.display = 'none';
							
							$('imgAgregarDescuento').style.display = 'none';");
					}
				}
				$objResponse->script("
				$('tdRepAprob').style.display = '';
				$('tdPaqAprob').style.display = '';
				$('tdNotaAprob').style.display = '';
				$('tdTempAprob').style.display = '';
				$('tdTotAprob').style.display = '';");	
			}
		}	

		$objResponse->script("
		$('tdInsElimPaq').style.display = ''; 
		$('tdInsElimRep').style.display = '';
		$('tdInsElimManoObra').style.display = ''; 
		$('tdInsElimNota').style.display = '';");	
	} else if($accion == 2) {				
		if ($tipoDocumento==1)
			$objResponse->script("$('tdTituloPaginaServicios').innerHTML = 'Visualizar Presupuesto de Venta';
			$('btnGuardar').disabled = true;");
			
		if ($tipoDocumento==2)
			$objResponse->script("$('tdTituloPaginaServicios').innerHTML = 'Visualizar Orden de Servicio';
				$('btnGuardar').disabled = true;");
		else
			if ($tipoDocumento==3)
				$objResponse->script("
				if($('hddDevolucionFactura').value != '') {
					if($('hddDevolucionFactura').value == 1){
						$('tdTituloPaginaServicios').innerHTML = 'Devolucion Factura';
					}
					else
						$('tdTituloPaginaServicios').innerHTML = 'Devolucion Vale Salida';
				} else{
					$('tdTituloPaginaServicios').innerHTML = 'Facturacion';
				}
						
				//$('btnImprimirDoc').style.display = 'none';//ya no existe, genera erorr si se usa
				$('tdNroControl').style.display = '';
				$('tdTxtNroControl').style.display = '';
				$('tdTipoMov').style.display = '';
				$('tdLstTipoClave').style.display = '';
				$('tdClave').style.display = '';
				$('tdlstClaveMovimiento').style.display = '';");
							
		if ($tipoDocumento==4)
			$objResponse->script("
			$('tdTituloPaginaServicios').innerHTML = 'Generar Presupuesto';
			//$('btnImprimirDoc').style.display = 'none';//ya no existe, genera error si se usa
			$('tdNroControl').style.display = 'none';
			$('tdTxtNroControl').style.display = 'none';
			$('tdEtiqTipoDocumento').innerHTML = 'Neto Presupuesto:';");
							
		$objResponse->script("
		xajax_cargarDcto('".$idDocumento."',
		xajax.getFormValues('frmTotalPresupuesto'));
		$('tdNotaAprob').style.display = ''; 
		$('tdRepAprob').style.display = '';
		$('tdPaqAprob').style.display = ''; 
		$('tdTempAprob').style.display = '';
		$('tdTotAprob').style.display = '';
		$('tdInsElimPaq').style.display = 'none'; 
		$('tdInsElimRep').style.display = 'none';
		$('tdInsElimManoObra').style.display = 'none'; 
		$('tdInsElimNota').style.display = 'none';
		$('tdInsElimTot').style.display = 'none';
		$('btnInsertarPaq').style.display = 'none';
		$('btnEliminarPaq').style.display = 'none';
		$('btnInsertarArt').style.display = 'none';
		$('btnEliminarArt').style.display = 'none';
		$('btnInsertarTemp').style.display = 'none';
		$('btnEliminarTemp').style.display = 'none';
		$('btnInsertarNota').style.display = 'none';
		$('btnEliminarNota').style.display = 'none';
		$('btnInsertarTot').style.display = 'none';
		$('btnEliminarTot').style.display = 'none';");	
	} else if($accion == 4) {
		if ($tipoDocumento == 1)
			$objResponse->script("
			$('tdTituloPaginaServicios').innerHTML = 'Aprobar Presupuesto de Venta';");
		else
			$objResponse->script("
			$('tdTituloPaginaServicios').innerHTML = 'Aprobar Orden de Servicio';");
							
		$objResponse->script("
		xajax_cargarDcto('".$idDocumento."', 
		xajax.getFormValues('frmTotalPresupuesto'));
		desbloquearForm();
		$('btnInsertarPaq').style.display = 'none';
		$('btnEliminarPaq').style.display = 'none';
		$('btnInsertarArt').style.display = 'none';
		$('btnEliminarArt').style.display = 'none';
		$('btnInsertarTemp').style.display = 'none';
		$('btnEliminarTemp').style.display = 'none';
		$('btnInsertarNota').style.display = 'none';
		$('btnEliminarNota').style.display = 'none';
		$('btnInsertarTot').style.display = 'none';
		$('btnEliminarTot').style.display = 'none';
		$('tdInsElimPaq').style.display = 'none'; 
		$('tdInsElimRep').style.display = 'none';
		$('tdInsElimManoObra').style.display = 'none'; 
		$('tdInsElimNota').style.display = 'none';
		$('tdNotaAprob').style.display = ''; 
		$('tdRepAprob').style.display = '';
		$('tdPaqAprob').style.display = ''; 
		$('tdTempAprob').style.display = '';
		$('tdTotAprob').style.display = '';
		$('btnInsertarCliente').disabled = true;");
	}
	
	return $objResponse;
}

	
	
	
	

//SE USA CADA VEZ QUE SE CAMBIA EL TIPO DE ORDEN EN ONCHANGE, SEA NUEVO O EDITADO
function verificarTipoOrdenGarantia($valForm, $idTipoOrden) {
	$objResponse = new xajaxResponse();	
	
	$queryVerificarSiLaOrdenEsGarantia = sprintf("SELECT sa_tipo_orden.tipo_garantia FROM sa_tipo_orden
	WHERE sa_tipo_orden.id_tipo_orden = %s",
		valTpDato($idTipoOrden,"int"));
	$rsVerificarSiLaOrdenEsGarantia = mysql_query($queryVerificarSiLaOrdenEsGarantia);
	$rowVerificarSiLaOrdenEsGarantia = mysql_fetch_assoc($rsVerificarSiLaOrdenEsGarantia);
	
	if ($rowVerificarSiLaOrdenEsGarantia['tipo_garantia'] == 1) {
		$queryVerificarSiElRegistroSeEncuentraRegistrado = sprintf("SELECT 
			an_uni_bas.anos_de_garantia,
			an_uni_bas.kilometraje AS kilometraje_inicial_registro,
			en_registro_placas.fecha_venta,
			en_registro_placas.kilometraje AS kilometraje_actual
		FROM an_uni_bas
			INNER JOIN en_registro_placas ON (an_uni_bas.id_uni_bas = en_registro_placas.id_unidad_basica)
		WHERE en_registro_placas.placa = %s",
			valTpDato($valForm['txtPlacaVehiculo'],"text"));
		$rsVerificarSiElRegistroSeEncuentraRegistrado = mysql_query($queryVerificarSiElRegistroSeEncuentraRegistrado);
		$rowVerificarSiElRegistroSeEncuentraRegistrado = mysql_fetch_assoc($rsVerificarSiElRegistroSeEncuentraRegistrado);
		
		$date_result1 = dateadd($rowVerificarSiElRegistroSeEncuentraRegistrado['fecha_venta'], 0,0,$rowVerificarSiElRegistroSeEncuentraRegistrado['anos_de_garantia']);		
		
		if(intval(compara_fechas(date("d-m-Y"),$date_result1)) > 0 || $rowVerificarSiElRegistroSeEncuentraRegistrado['kilometraje_actual'] > $rowVerificarSiElRegistroSeEncuentraRegistrado['kilometraje_inicial_registro']) {	
			$objResponse->script("
			if($('hddAgregarOrdenGarantia').value != 1) {	
				if(confirm('La garantia esta vencida. Desea agregar el tipo de orden GARANTIA?')) {
					xajax_formClave(xajax.getFormValues('frmTotalPresupuesto'), 'acc_ord_gtia');
				} else {
					$('lstTipoOrden').value = '-1';
					$('lstTipoOrden').focus();
				}
			}");
		}
	}
	
	$objResponse->script(sprintf("xajax_verificarTipoOrdenPorVale(xajax.getFormValues('frmPresupuesto'), %s);", $idTipoOrden));
		
	return $objResponse;	
}

//VERIFICA SI EL VALE YA TIENE UN TIPO DE ORDEN IGUAL, SE LLAMA LUEGO DEL ONCHANGE EN EL LISTADO DE TIPO DE ORDEN AL 
//VERIFICAR TIPO ORDEN GARANTIA()
function verificarTipoOrdenPorVale($valFormDcto, $idTipoOrden) {
	$objResponse = new xajaxResponse();
				
	if ($valFormDcto['txtIdValeRecepcion'] == '') {
		$objResponse->alert('Escoja el Vale de Recepcion');
		$objResponse->script("
		$('lstTipoOrden').value = '-1';
		$('key_window2').style.display = 'none';
		$('btnInsertarCliente').focus();");
		
		return $objResponse;
	}
	
	$query = sprintf("SELECT pg_parametros_empresas.valor_parametro FROM pg_parametros_empresas
	WHERE descripcion_parametro = 'TIPO ORDEN POR VALE'
		AND id_empresa = %s",
		$valFormDcto['txtIdEmpresa']);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if ($row["valor_parametro"] != "" || $row["valor_parametro"] != NULL) {
		if ($row['valor_parametro'] == 1) { // UNO POR ORDEN
			$queryVerificarSiExisteOrdenConEseTipoOrden = sprintf("SELECT * FROM sa_orden
				INNER JOIN sa_tipo_orden ON (sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden)
			WHERE sa_orden.id_recepcion = %s
				AND sa_orden.id_tipo_orden = %s",
				$valFormDcto['txtIdValeRecepcion'],
				$idTipoOrden);
			$rsVerificarSiExisteOrdenConEseTipoOrden = mysql_query($queryVerificarSiExisteOrdenConEseTipoOrden);
			if (!$rsVerificarSiExisteOrdenConEseTipoOrden) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$numOrdenPorVale = mysql_num_rows($rsVerificarSiExisteOrdenConEseTipoOrden);
			
			if ($numOrdenPorVale > 0) {
				if ($row = mysql_fetch_assoc($rsVerificarSiExisteOrdenConEseTipoOrden)) {
					$objResponse->alert(sprintf("Este Vale ya tiene Asociada un Tipo de Orden %s. Por favor Verifique y escoja otro Tipo de Orden",
						utf8_encode($row['nombre_tipo_orden'])));
					$objResponse->script("
						$('lstTipoOrden').value = '-1';
						$('lstTipoOrden').focus();");
						
					return $objResponse;
				}
			}
		}
	} else {
		$objResponse->alert(utf8_encode("El parametro TIPO ORDEN POR VALE no esta configurado en parametros generales. Por favor Contacte con el personal de sistemas"));
	}
	
	return $objResponse;
}

//ES EL LISTADO DE SOLICITUD - ALMACEN - UBICACION AL ABRIR EL REPUESTO, EL OJO EN LA SECCION DE REPUESTOS EN LA ORDEN
function verMovimientosArticulo($pageNum = 0, $campOrd = "id_solicitud", $tpOrd = "DESC", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if (strlen($valCadBusq[0]) > 0) {
		$sqlBusq = sprintf(" WHERE det_sol_rep.id_det_orden_articulo = %s",
			valTpDato($valCadBusq[0],"int"));	
	}
	
	$query = sprintf("SELECT
		sol_rep.id_solicitud,
		sol_rep.numero_solicitud,
		vw_iv_art_emp_ubic.descripcion_almacen,
		vw_iv_art_emp_ubic.ubicacion,
		estado_sol.descripcion_estado_solicitud
	FROM sa_solicitud_repuestos sol_rep
		INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (sol_rep.id_solicitud = det_sol_rep.id_solicitud)
		INNER JOIN sa_estado_solicitud estado_sol ON (det_sol_rep.id_estado_solicitud = estado_sol.id_estado_solicitud)
		INNER JOIN sa_det_orden_articulo det_orden_art ON (det_sol_rep.id_det_orden_articulo = det_orden_art.id_det_orden_articulo)
		LEFT JOIN vw_iv_articulos_empresa_ubicacion vw_iv_art_emp_ubic ON (det_orden_art.id_articulo = vw_iv_art_emp_ubic.id_articulo)
			AND (vw_iv_art_emp_ubic.id_casilla = det_sol_rep.id_casilla) %s", $sqlBusq);
	
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
	
	
		$htmlTh .= ordenarCampo("xajax_verMovimientosArticulo", "20%", $pageNum, "numero_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro Solicitud");
		$htmlTh .= ordenarCampo("xajax_verMovimientosArticulo", "35%", $pageNum, "descripcion_almacen", $campOrd, $tpOrd, $valBusq, $maxRows, "Almac&eacute;n");
		$htmlTh .= ordenarCampo("xajax_verMovimientosArticulo", "20%", $pageNum, "ubicacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Ubicaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_verMovimientosArticulo", "25%", $pageNum, "descripcion_estado_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
			$htmlTb .= "<td align=\"right\" idSolicitudOculto =\"".$row['id_solicitud']."\">".$row['numero_solicitud']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_almacen'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['ubicacion'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['descripcion_estado_solicitud'])."</td>";
		$htmlTb .= "</tr>";
			
		$id_solicitud = $row['id_solicitud'];
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_verMovimientosArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_verMovimientosArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_verMovimientosArticulo(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_verMovimientosArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_verMovimientosArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"10\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoEstadoMtoArt","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	$objResponse->assign("tdCodigoArticuloMto","innerHTML",utf8_encode(elimCaracter($valCadBusq[1], ";")));
	
	$objResponse->script("
	$('tblListados1').style.display = 'none';
	$('tblArticulosSustitutos').style.display = 'none';
	$('tblClaveDescuento').style.display = 'none';
	$('tblMtosArticulos').style.display = '';
	$('tblPorcentajeDescuento').style.display = 'none';");
		 
	$objResponse->assign("tdFlotanteTitulo2","innerHTML",utf8_encode("Estado Solicitud Articulo"));
	$objResponse->script("
	if ($('divFlotante2').style.display == 'none') {
		$('divFlotante2').style.display = '';
		centrarDiv($('divFlotante2'));
	}");
	
	return $objResponse;
}

//busca el filtro de tipo de orden segun el tipo de orden
function buscarFiltroOrden($idTipoOrden, $nroItem){
$objResponse = new xajaxResponse();

    if($idTipoOrden == ""){
        return $objResponse->alert("No se ha seleccionado tipo de orden");
    }

    $query = "SELECT bloqueo_items 
              FROM sa_tipo_orden 
              INNER JOIN sa_filtro_orden ON sa_tipo_orden.id_filtro_orden = sa_filtro_orden.id_filtro_orden
              WHERE id_tipo_orden = ".$idTipoOrden." LIMIT 1";
    $rs = mysql_query($query);
    if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
    $row = mysql_fetch_assoc($rs);
    
    $arrayBloqueados = explode(",", $row["bloqueo_items"]);
    
    if(in_array($nroItem,$arrayBloqueados)){
        $bloqueado = 1;
    }
    
    return $objResponse->setReturnValue($bloqueado);
}

$xajax->register(XAJAX_FUNCTION,"reconversionArticulosTempario");

$xajax->register(XAJAX_FUNCTION,"buscarFiltroOrden");

$xajax->register(XAJAX_FUNCTION,"actualizarNumeroControl");

$xajax->register(XAJAX_FUNCTION,"asignarArticulo");

$xajax->register(XAJAX_FUNCTION,"asignarPrecio");
$xajax->register(XAJAX_FUNCTION,"asignarTempario");
$xajax->register(XAJAX_FUNCTION,"asignarTot");
$xajax->register(XAJAX_FUNCTION,"asignarValeRecepcion");

$xajax->register(XAJAX_FUNCTION,"buscarArticulo");

$xajax->register(XAJAX_FUNCTION,"buscar_mano_obra_repuestos_por_paquete");
$xajax->register(XAJAX_FUNCTION,"buscarMtoArticulo");
$xajax->register(XAJAX_FUNCTION,"buscarPaquete");
$xajax->register(XAJAX_FUNCTION,"buscarTempario");
$xajax->register(XAJAX_FUNCTION,"buscarTot");
$xajax->register(XAJAX_FUNCTION,"buscarValeRecepcion");

$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"calcularTotalDcto");
$xajax->register(XAJAX_FUNCTION,"calcularTotalPaquete");

$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstDescuentos");

$xajax->register(XAJAX_FUNCTION,"cargaLstSeccionTemp");
$xajax->register(XAJAX_FUNCTION,"cargaLstSubseccionTemp");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoOrden");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"cargarPorcAdicional");

$xajax->register(XAJAX_FUNCTION,"contarItemsDcto");

$xajax->register(XAJAX_FUNCTION,"desbloquearOrden");
$xajax->register(XAJAX_FUNCTION,"devolverFacturaVenta");
$xajax->register(XAJAX_FUNCTION,"devolverValeSalida");

$xajax->register(XAJAX_FUNCTION,"eliminarArticuloEnPresupuesto");
$xajax->register(XAJAX_FUNCTION,"eliminarDescuentoAdicional");
$xajax->register(XAJAX_FUNCTION,"eliminarNotaEnPresupuesto");
$xajax->register(XAJAX_FUNCTION,"eliminarPaquete");
$xajax->register(XAJAX_FUNCTION,"eliminarTemparioEnPresupuesto");
$xajax->register(XAJAX_FUNCTION,"eliminarTot");

$xajax->register(XAJAX_FUNCTION,"formClave");
$xajax->register(XAJAX_FUNCTION,"formListaTempario");
$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion");

$xajax->register(XAJAX_FUNCTION,"generarDctoApartirDeOrden");
$xajax->register(XAJAX_FUNCTION,"guardarAprobacionDesAprobacion");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"guardarFactura");

$xajax->register(XAJAX_FUNCTION,"insertarArticulo");
$xajax->register(XAJAX_FUNCTION,"insertarDescuento");
$xajax->register(XAJAX_FUNCTION,"insertarNota");
$xajax->register(XAJAX_FUNCTION,"insertarPaquete");
$xajax->register(XAJAX_FUNCTION,"insertarTempario");
$xajax->register(XAJAX_FUNCTION,"insertarTot");

$xajax->register(XAJAX_FUNCTION,"listadoArticulos");

$xajax->register(XAJAX_FUNCTION,"listadoDctos");

$xajax->register(XAJAX_FUNCTION,"listado_paquetes_por_unidad");
$xajax->register(XAJAX_FUNCTION,"listado_repuestos_por_paquetes");
$xajax->register(XAJAX_FUNCTION,"listadoTempario");
$xajax->register(XAJAX_FUNCTION,"listado_tempario_por_paquetes");
$xajax->register(XAJAX_FUNCTION,"listado_tot");
$xajax->register(XAJAX_FUNCTION,"listadoValeRecepcion");

$xajax->register(XAJAX_FUNCTION,"nuevoDcto");

$xajax->register(XAJAX_FUNCTION,"validarAperturaCaja");
$xajax->register(XAJAX_FUNCTION,"validarClaveDescuento");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");
$xajax->register(XAJAX_FUNCTION,"validarSiLasNotasCargadasEstanRelacionadasConPresupuesto");
$xajax->register(XAJAX_FUNCTION,"validarSiLosArticulosCargadosEstanRelacionadosConPresupuesto");
$xajax->register(XAJAX_FUNCTION,"validarSiLosTempariosCargadosEstanRelacionadosConPresupuesto");
$xajax->register(XAJAX_FUNCTION,"validarSiTieneAlmacenAsignado");
$xajax->register(XAJAX_FUNCTION,"validarTipoDocumento");

$xajax->register(XAJAX_FUNCTION,"buscarNumeroControl");

$xajax->register(XAJAX_FUNCTION,"verificarTipoOrdenGarantia");
$xajax->register(XAJAX_FUNCTION,"verificarTipoOrdenPorVale");
$xajax->register(XAJAX_FUNCTION,"verMovimientosArticulo");




$xajax->register(XAJAX_FUNCTION,"prueba");
function prueba($formTotales){
    var_dump($formTotales);
}


function compara_fechas($fecha1,$fecha2) {//SOLO LO USA EL VERIFICAR TIPO DE ORDEN GARANTIA
	if (preg_match("/[0-9]{1,2}\/[0-9]{1,2}\/([0-9][0-9]){1,2}/",$fecha1))
		 list($dia1,$mes1,$ano1)=split("/",$fecha1);
		
	if (preg_match("/[0-9]{1,2}-[0-9]{1,2}-([0-9][0-9]){1,2}/",$fecha1))
		 list($dia1,$mes1,$ano1)=split("-",$fecha1);
		  
	if (preg_match("/[0-9]{1,2}\/[0-9]{1,2}\/([0-9][0-9]){1,2}/",$fecha2))
		 list($dia2,$mes2,$ano2)=split("/",$fecha2);
		
	if (preg_match("/[0-9]{1,2}-[0-9]{1,2}-([0-9][0-9]){1,2}/",$fecha2))
         list($dia2,$mes2,$ano2)=split("-",$fecha2);
		 
	$dif = mktime(0,0,0,$mes1,$dia1,$ano1) - mktime(0,0,0, $mes2,$dia2,$ano2);
	
	return ($dif);                                 
}


function dateadd($date, $dd=0, $mm=0, $yy=0){//LO USA AL GUARDAR DOCUMENTO, Y EL VERIFICAR TIPO DE ORDEN GARANTIA
	$date_r = getdate(strtotime($date)); 
	
	$date_result = date("d-m-Y", mktime(($date_r["hours"]+$hh),($date_r["minutes"]+$mn),($date_r["seconds"]+$ss),($date_r["mon"]+$mm),($date_r["mday"]+$dd),($date_r["year"]+$yy)));
	
	return $date_result;
}

	
	

				



//nuevo gregor

function cantidadDespachada($id_det_orden_articulo_ref){
    // SI ESTAN EN SOLICITUD 3 DESPACHADO 5 FACTURADO
    if($id_det_orden_articulo_ref){
        $queryCont = sprintf("SELECT COUNT(*) AS nro_rpto_desp FROM sa_det_solicitud_repuestos
        WHERE sa_det_solicitud_repuestos.id_det_orden_articulo = %s
                AND (sa_det_solicitud_repuestos.id_estado_solicitud = 3 
                        OR sa_det_solicitud_repuestos.id_estado_solicitud = 5
                        OR sa_det_solicitud_repuestos.id_estado_solicitud = 2) ", 
        $id_det_orden_articulo_ref);
        $rsCont = mysql_query($queryCont);
        $rowCont = mysql_fetch_assoc($rsCont);

        //OJO SE USA TAMBIEN PARA CALCULAR TOTALES
        if (!$rsCont){
                return "Error cargarDcto \n".mysql_error().$queryCont."\n Linea: ".__LINE__;
        }else {
                if ($rowCont['nro_rpto_desp'] != NULL || $rowCont['nro_rpto_desp'] != ""){
                        $cantidad_art = $rowCont['nro_rpto_desp'];
                }else{
                        $cantidad_art = 0;
                }
        }

        return $cantidad_art;
    }

}

function cantidadSolicitada($id_det_orden_articulo_ref){
    //TOTAL SOLICITADA
    if($id_det_orden_articulo_ref){
        $queryContTotal = sprintf("SELECT COUNT(*) AS nro_rpto_desp FROM sa_det_solicitud_repuestos
        WHERE sa_det_solicitud_repuestos.id_det_orden_articulo = %s", 
                $id_det_orden_articulo_ref);
        $rsContTotal = mysql_query($queryContTotal);

        if (!$rsContTotal){
            return "Error cargarDcto \n".mysql_error().$queryContTotal."\n Linea: ".__LINE__;
        }
        $rowContTotal = mysql_fetch_assoc($rsContTotal);

        $cantidad_art_total = $rowContTotal['nro_rpto_desp'];

        return $cantidad_art_total;
    
    }
}

function cantidadDisponibleActual($idArticulo, $idEmpresa){
    if($idArticulo){
        $queryDisponible = sprintf("SELECT cantidad_disponible_logica, cantidad_bloqueada "
                . "FROM vw_iv_articulos_empresa "
                . "WHERE id_articulo = '%s' AND id_empresa = '%s' LIMIT 1",
                $idArticulo, $idEmpresa);
        $rs = mysql_query($queryDisponible);
        if(!$rs){ return "Error cargarDcto \n".mysql_error().$queryDisponible."\n Linea: ".__LINE__; }

        $row = mysql_fetch_assoc($rs);
        $cantidadDisponibleReal = $row['cantidad_disponible_logica'] + $row['cantidad_bloqueada'];

        return $cantidadDisponibleReal;
    }
}

function esEditar(){
    if($_GET["doc_type"] == "2" && $_GET["acc"] == "3"){
        return true;
    }else{
        return false;
    }
}

function esNuevo(){
    if($_GET["id"] == ""){
        return true;
    }else{
        return false;
    }
}

/**
 * Se encarga de distribuir la cantidad de articulos solicitados en los distintos
 * almacenes segun disponibilidad, tomando en cuenta los no existentes tambien
 * @param int $cantidadArt cantidad de articulos escritas
 * @param array $arrayAlmacenes array de arrays con los almacenes disponibles
 * @return array Devuelve un array de arrays con los articulos distribuidos por almacen
 */
function distribucionArticuloAlmacenes($cantidadArt,$arrayAlmacenes){
    
    //$arrayAlmacenes
//    "id_articulo_costo" => $rowAlmacenes["id_articulo_costo"],
//    "id_articulo_almacen_costo" => $rowAlmacenes["id_articulo_almacen_costo"],
//    "costo" => $rowAlmacenes["costo"],
//    "costo_promedio" => $rowAlmacenes["costo_promedio"],
//    "cantidad_disponible_logica" => $rowAlmacenes["cantidad_disponible_logica"],
//    "precio" => $precioArt,
//    "cantidad_articulos" => 0
    
    $totalDisponible = 0;
    foreach($arrayAlmacenes as $arrayAlmacen){
        $totalDisponible += $arrayAlmacen["cantidad_disponible_logica"];
    }
    
    //sino hay disponibles, ingresar todo en el ultimo almacen
    if($totalDisponible == 0){
        end($arrayAlmacen);
        $arrayAlmacen["cantidad_articulos"] = $cantidadArt;
        return array($arrayAlmacen["id_articulo_costo"] => $arrayAlmacen);
    }
    
    //distribucion

    $arrayAlmacenLleno = array();
    foreach($arrayAlmacenes as $arrayAlmacen){//recorro todos los almacenes
        if($arrayAlmacen["cantidad_disponible_logica"] > 0){//solo uso los que esten llenos
            
            if($cantidadArt > 0){//solo ejecutar si hay articulos pendientes por distribuir            
                if($cantidadArt > $arrayAlmacen["cantidad_disponible_logica"]){//si es mayor, solo tomo lo que cabe
                    $arrayAlmacen["cantidad_articulos"] = $arrayAlmacen["cantidad_disponible_logica"];
                    $arrayAlmacenLleno[$arrayAlmacen["id_articulo_costo"]] = $arrayAlmacen;
                    $cantidadArt -= $arrayAlmacen["cantidad_disponible_logica"];

                }else{//si no es mayor, entonces ya esta en capacidad de ocupar               
                    $arrayAlmacen["cantidad_articulos"] = $cantidadArt;
                    $arrayAlmacenLleno[$arrayAlmacen["id_articulo_costo"]] = $arrayAlmacen;
                    $cantidadArt -= $arrayAlmacen["cantidad_disponible_logica"];
                }
            }
        }
    }
    
    if($cantidadArt > 0){//si todavia hay pendientes, colocarselos al ultimo lote
        end($arrayAlmacenLleno);
        $ultimoIndice = key($arrayAlmacenLleno);        
        $arrayAlmacenLleno[$ultimoIndice]["cantidad_articulos"] += $cantidadArt;
    }
    
    return $arrayAlmacenLleno;
}

function actualizarOrdenServicio2($idOrden) {
	global $conex;
	
	// RECALCULA LOS MONTOS DE LA ORDEN
	$sqlDetOrden = sprintf("SELECT SUM(precio_unitario * cantidad) AS valor FROM sa_det_orden_articulo
	WHERE id_orden = %s
		AND aprobado = 1
		AND estado_articulo <> 'DEVUELTO';",
		$idOrden);
	$rsDetOrden = mysql_query($sqlDetOrden);
	if (!$rsDetOrden) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$rowDetOrden = mysql_fetch_array($rsDetOrden);
	$valor = $rowDetOrden['valor'];
        
        $sqlDetOrdenExento = sprintf("SELECT SUM(precio_unitario * cantidad) AS valorExento FROM sa_det_orden_articulo
	WHERE id_orden = %s
		AND aprobado = 1
		AND estado_articulo <> 'DEVUELTO'
                AND (iva IS NULL OR iva = 0);",
		$idOrden);
	$rsDetOrdenExento = mysql_query($sqlDetOrdenExento);
	if (!$rsDetOrdenExento) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$rowDetOrdenExento = mysql_fetch_array($rsDetOrdenExento);
	$valorExento = $rowDetOrdenExento['valorExento'];

	        
	$sqlTemp = sprintf("SELECT
	(CASE id_modo
		WHEN 1 THEN
			SUM((precio_tempario_tipo_orden * ut) / base_ut_precio)
		WHEN 2 THEN
			SUM(precio)
	END) AS valorTemp
	FROM sa_det_orden_tempario
	WHERE id_orden = %s AND estado_tempario <> 'DEVUELTO';",
		$idOrden);
	$rsTemp = mysql_query($sqlTemp);
	if (!$rsTemp) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$rowTemp = mysql_fetch_array($rsTemp);
	$valorTemp = $rowTemp['valorTemp'];

	$sqlTOT = sprintf("SELECT
		orden.id_orden,
		orden.id_tipo_orden,
		SUM(orden_tot.monto_subtotal) AS monto_subtotalTot
	FROM sa_orden_tot orden_tot
		INNER JOIN sa_orden orden ON (orden_tot.id_orden_servicio = orden.id_orden)
	WHERE orden.id_orden = %s
	GROUP BY orden.id_orden, orden.id_tipo_orden;",
		$idOrden);
	$rsTOT = mysql_query($sqlTOT);
	if (!$rsTOT) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$rowTOT = mysql_fetch_array($rsTOT);
	
	$queryPorcentajeTot = sprintf("SELECT *
	FROM sa_det_orden_tot
	WHERE id_orden = %s;",
		valTpDato($idOrden, "int"));
	$rsPorcentajeTot = mysql_query($queryPorcentajeTot);
	if (!$rsPorcentajeTot) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$rowPorcentajeTot = mysql_fetch_assoc($rsPorcentajeTot);

	$valorDetTOT = $rowTOT['monto_subtotalTot'] + (($rowPorcentajeTot['porcentaje_tot'] * $rowTOT['monto_subtotalTot']) / 100);

	$sqlNota = sprintf("SELECT SUM(precio) AS precio FROM sa_det_orden_notas
	WHERE id_orden = %s 
	AND aprobado = 1;",
		$idOrden);
	$rsNota = mysql_query($sqlNota);
	if (!$rsNota) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$rowNota = mysql_fetch_array($rsNota);

        $sqlDesc = sprintf("SELECT *
	FROM sa_orden
	WHERE id_orden = %s;",
		$idOrden);
	$rsDesc = mysql_query($sqlDesc);
	if (!$rsDesc) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$rowDesc = mysql_fetch_array($rsDesc);
	
        
        $totalSinDesc = $rowNota['precio'] + $valorDetTOT + $valorTemp + $valor;
        
        //$Desc = ($rowDesc['porcentaje_descuento'] * $valor) / 100;
	//$valorConDesc = $valor - $Desc;
        
	$Desc = $totalSinDesc * ($rowDesc['porcentaje_descuento']/100);
        // SI NO TIENE IMPUESTO LE ASIGNA EL IMPUESTO DE VENTA QUE EXISTE POR DEFECTO
	if ($rowDesc['iva'] != 0 && $rowDesc['iva'] != "") {
            $exento = $valorExento - ($valorExento*($rowDesc['porcentaje_descuento']/100));
	}else{
            $exento = 0;
        }        
        
	$totalConDesc = round($totalSinDesc-$Desc-$exento,2);
	
	$totalIva = round(($totalConDesc * $rowDesc['iva'])/100,2);

        echo ($Desc);
        echo "<br>";
        echo ($exento);
        echo "<br>";
        echo valTpDato($totalSinDesc, "double");
        echo "<br>";
        echo valTpDato($totalConDesc, "double");
        echo "<br>";
        echo valTpDato($rowDesc['idIva'], "int");
        echo "<br>";
        echo valTpDato($rowDesc['iva'], "double");
        echo "<br>";
        echo valTpDato($totalIva, "double");
        echo "<br>";
        echo valTpDato(round($Desc,2), "double");
        
//	$updateSQL = "UPDATE sa_orden SET
//		subtotal = ".valTpDato($totalSinDesc, "double").",
//		base_imponible = ".valTpDato($totalConDesc, "double").",
//		idIva = ".valTpDato($rowDesc['idIva'], "int").",
//		iva = ".valTpDato($rowDesc['iva'], "double").",
//		subtotal_iva = ".valTpDato($totalIva, "double").",
//		subtotal_descuento = ".valTpDato(round($Desc,2), "double")."
//	WHERE id_orden = ".$idOrden;
//	$Result1 = mysql_query($updateSQL);
//	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		
	return array(true, "");
}

//actualizarOrdenServicio2(3242);
?>