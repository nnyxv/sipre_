<?php

function asignarBanco($idBanco){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM bancos WHERE idBanco = %s",
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtNombreBanco","value",utf8_encode($row['nombreBanco']));
	$objResponse->assign("hddIdBanco","value",$row['idBanco']);
	
	$objResponse->script("xajax_cargaLstCuenta(byId('lstEmpresa').value, ".$row['idBanco'].")");
	$objResponse->script("byId('btnCancelarBanco').click();");
	
	return $objResponse;
}

function asignarBanco1($idBanco){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM bancos WHERE idBanco = %s",
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtNombreBanco1","value",utf8_encode($row['nombreBanco']));
	$objResponse->assign("hddIdBanco1","value",$row['idBanco']);
	
	$objResponse->script("xajax_cargaLstCuenta1(byId('hddIdEmpresa').value, ".$row['idBanco'].")");
	$objResponse->script("byId('btnCancelarBanco1').click();");
	
	return $objResponse;
}

function asignarEmpresa($idEmpresa){
	$objResponse = new xajaxResponse();
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = %s",
		valTpDato($idEmpresa, "int"));
	$rsEmpresa = mysql_query($queryEmpresa);
	if(!$rsEmpresa){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
	$nombreSucursal = "";		
	if ($rowEmpresa['id_empresa_padre_suc'] > 0){
		$nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";	
	}
	$empresa = utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal);
	
	$objResponse->assign("txtNombreEmpresa","value",$empresa);
	$objResponse->assign("hddIdEmpresa","value",$rowEmpresa['id_empresa_reg']);
	$objResponse->script("byId('btnCancelarEmpresa').click();");
	
	return $objResponse;
}

function asignarFechaConciliacion($idCuenta){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT MAX(fecha) AS fecha_conciliacion FROM te_conciliacion WHERE id_cuenta = %s",
		valTpDato($idCuenta, "int"));  			
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowConciliacion = mysql_fetch_assoc($rs);
	
	if ($rowConciliacion["fecha_conciliacion"] != "") { // SI TIENE CONCILIACION BUSCAR LA ULTIMA
		$ultimaFecha = date("m-Y",strtotime($rowConciliacion["fecha_conciliacion"]));
		$sumarMes = true;
	} else { // SINO TIENE BUSCAR EL PRIMER MOVIMIENTO A CONCILIAR
		$query = sprintf("SELECT MIN(fecha_registro) AS fecha_movimiento FROM te_estado_cuenta WHERE id_cuenta = %s",
			valTpDato($idCuenta, "int"));  			
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$ultimaFecha = ($row["fecha_movimiento"] != "") ? date("m-Y",strtotime($row["fecha_movimiento"])) : "";
		$sumarMes = false;
	}
	
	if ($ultimaFecha != "") {
		$arrUltFecha = explode("-",$ultimaFecha);
		$mes = $arrUltFecha[0];
		$ano = $arrUltFecha[1];
		
		if ($sumarMes) { //SI VIENE DE LA CONCILIADA SUMAR MES, SI VIENE DEL PRIMER MOVIMIENTO USAR LA DEL MOVIMIENTO
			if ($mes == 12) {
				$mes = "01";
				$ano = $ano+1;
			} else {
				$mes = $mes+1;
			}
		}
		
		$fechaSiguiente = sprintf("%02d",$mes)."-".$ano;
	}
	
	$objResponse->assign("txtFechaConciliacion","value",$fechaSiguiente);
	
	return $objResponse;
}

function buscarBanco($valForm){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['txtCriterioBuscarBanco']);
	
	$objResponse->loadCommands(listaBanco(0, "idBanco", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarBanco1($valForm){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['txtCriterioBuscarBanco1']);
	
	$objResponse->loadCommands(listaBanco1(0, "idBanco", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarConciliacion($valForm) {
	$objResponse = new xajaxResponse();
		
	$valBusq = sprintf("%s|%s|%s|%s|%s",		
		$valForm['lstEmpresa'],
		$valForm['lstCuenta'],
		$valForm['hddIdBanco'],
		$valForm['txtFechaDesde'],
		$valForm['txtFechaHasta']);
	
	$objResponse->loadCommands(listaConciliacion(0, "fecha", "DESC", $valBusq));
		
	return $objResponse;
}

function buscarEmpresa($valForm){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listaEmpresa(0, "id_empresa_reg", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstCuenta($idEmpresa, $idBanco, $selId = ""){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM cuentas WHERE id_empresa = %s AND idBanco = %s",
		valTpDato($idEmpresa, "int"),
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$html = "<select id=\"lstCuenta\" name=\"lstCuenta\" class=\"inputHabilitado\">";
	$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)){
		$selected = ($selId == $row['idCuentas']) ? "selected=\"selected\"" : "";
		$html .= "<option ".$selected." value=\"".$row['idCuentas']."\">".$row['numeroCuentaCompania']."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdLstCuenta","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCuenta1($idEmpresa, $idBanco, $selId = ""){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM cuentas WHERE id_empresa = %s AND idBanco = %s",
		valTpDato($idEmpresa, "int"),
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$html = "<select id=\"lstCuenta1\" name=\"lstCuenta1\" onchange=\"xajax_asignarFechaConciliacion(this.value);\" class=\"inputHabilitado\">";
	$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)){
		$selected = ($selId == $row['idCuentas']) ? "selected=\"selected\"" : "";
		$html .= "<option ".$selected." value=\"".$row['idCuentas']."\">".$row['numeroCuentaCompania']."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdLstCuenta1","innerHTML",$html);
	
	return $objResponse;
}

function formAnularConciliacion($idConciliacion){        
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT 
		te_conciliacion.id_conciliacion,
		te_conciliacion.id_cuenta,
		te_conciliacion.id_banco,                             
		te_conciliacion.id_empresa,
		(SELECT MAX(b.fecha) FROM te_conciliacion b WHERE b.id_cuenta = te_conciliacion.id_cuenta ) as ultima_fecha_conciliada,
		te_conciliacion.fecha,
		cuenta.idCuentas,
		cuenta.idBanco,
		cuenta.numeroCuentaCompania,
		banco.idBanco,
		banco.nombreBanco,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM te_conciliacion
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (te_conciliacion.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		INNER JOIN cuentas cuenta ON (te_conciliacion.id_cuenta = cuenta.idCuentas)
		INNER JOIN bancos banco ON (cuenta.idBanco = banco.idBanco)
	WHERE te_conciliacion.id_conciliacion = %s",
		valTpDato($idConciliacion, "int"));
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }
	$row = mysql_fetch_assoc($rs);
	
	//compruebo que sea el ultima conciliacion:
	if($row["fecha"] != $row["ultima_fecha_conciliada"]){
		$fechaElegida = date("m-Y",strtotime($row["fecha"]));
		$ultFechaConciliada = date("m-Y",strtotime($row["ultima_fecha_conciliada"]));
				
		$objResponse->alert("Solo se puede anular conciliaciones recientes, fecha elegida: ".$fechaElegida." fecha reciente: ".$ultFechaConciliada);
		$objResponse->script("
		setTimeout(function(){
			byId('btnCancelarAnularConciliacion').click();
		},1000);");
	}
	
	$queryTotalReversion = sprintf("SELECT 
		(SELECT SUM(monto) FROM te_estado_cuenta WHERE suma_resta = 1 AND id_conciliacion = %s) as total_credito, 
		(SELECT SUM(monto) FROM te_estado_cuenta WHERE suma_resta = 0 AND id_conciliacion = %s) as total_debito", 
		valTpDato($idConciliacion, "int"),
		valTpDato($idConciliacion, "int"));
	$rsTotalReversion = mysql_query($queryTotalReversion);
	if(!$rsTotalReversion){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }        
	$rowTotal = mysql_fetch_assoc($rsTotalReversion);
			
	$resta = $rowTotal["total_credito"] - $rowTotal["total_debito"];
	
	$queryCuenta = sprintf("SELECT saldo FROM cuentas WHERE idCuentas = '%s'",$row['id_cuenta']);
	$rsCuenta = mysql_query($queryCuenta);
	if(!$rsCuenta){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }   
	$rowCuenta = mysql_fetch_assoc($rsCuenta);
	
	$saldo = $rowCuenta["saldo"];
        
	$montoSaldoTotal = $saldo - $resta; 
        
	$fecha = date("m-Y",strtotime($row['fecha']));
	
	$objResponse->assign("nombreEmpresaAnular","innerHTML",utf8_encode($row['nombre_empresa']));
	$objResponse->assign("fechaConciliacionAnular","innerHTML",$fecha);
	$objResponse->assign("nombreBancoAnular","innerHTML",utf8_encode($row['nombreBanco']));
	$objResponse->assign("cuentaAnular","innerHTML",utf8_encode($row['numeroCuentaCompania']));
	
	$objResponse->assign("restaAnular","innerHTML",number_format($resta,2,".",","));
	$objResponse->assign("saldoCuentaAnular","innerHTML",number_format($saldo,2,".",","));
	$objResponse->assign("nuevoSaldoAnular","innerHTML","<b>".number_format($montoSaldoTotal,2,".",",")."</b>");
	
	$objResponse->assign("hddIdConciliacionEliminar","value",$idConciliacion);
	
	return $objResponse;
}

function formNuevaConciliacion(){
	$objResponse = new xajaxResponse();
		
	$objResponse->loadCommands(asignarEmpresa($_SESSION["idEmpresaUsuarioSysGts"]));
	$objResponse->loadCommands(cargaLstCuenta1());
	
	$objResponse->script("
		byId('txtNombreEmpresa').className = 'inputInicial';
		byId('txtNombreBanco1').className = 'inputInicial';
		byId('txtFechaConciliacion').className = 'inputInicial';
		byId('txtSaldoBanco').className = 'inputHabilitado';");
		
	return $objResponse;
}

function formValidarPermisoEdicion($hddModulo, $idConciliacion) {
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT * FROM pg_claves_modulos WHERE modulo LIKE %s;",
		valTpDato($hddModulo, "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	$objResponse->assign("txtDescripcionPermiso","value",utf8_encode($rowPermiso['descripcion']));
	$objResponse->assign("hddModulo","value",$hddModulo);
	$objResponse->assign("hddIdConciliacion","value",$idConciliacion);
	
	return $objResponse;
}

function guardarConciliacion($valForm){
	$objResponse = new xajaxResponse();	
		
	$query = sprintf("SELECT * FROM te_conciliacion WHERE id_cuenta = %s",
		valTpDato($valForm['lstCuenta1'], "int"));  			
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);	
	
	$cantidadConciliaciones = mysql_num_rows($rs);
	
	$mesAnoSeleccionado = $valForm['txtFechaConciliacion']; // Formato m-Y
	
	$count = 0;
	$countMesAnterior = 0;
	
	$mesAnoAnterior = date("m-Y",strtotime("01-".$mesAnoSeleccionado." -1 MONTH"));//mes año anterior                
	
	while($row = mysql_fetch_assoc($rs)){ // Valida contra la fecha
		$arrayFechaConciliada = split('-',$row['fecha']);
		$mesAnoConciliado = $arrayFechaConciliada[1]."-".$arrayFechaConciliada[0];
		
		if($mesAnoConciliado == $mesAnoSeleccionado){
			$count++;
		}
		
		if($mesAnoConciliado == $mesAnoAnterior){
			$countMesAnterior++;
		}
	}
	
	if ($count != 0) {
		$objResponse->alert("Ya hay una Concilicion para ese Mes");
	} else if ($countMesAnterior == 0 && $cantidadConciliaciones != 0){//si no se concilio mes anterior, y sino es la primera conciliacion
		$objResponse->alert("Ya hay una fecha sin conciliar anterior: ".$mesAnoAnterior);
	} else {
		$objResponse->script("window.open('te_conciliacion_proceso.php?txtFechaConciliacion=".$valForm['txtFechaConciliacion']."&hddIdEmpresa=".$valForm['hddIdEmpresa']."&txtSaldoBanco=".$valForm['txtSaldoBanco']."&lstCuenta1=".$valForm['lstCuenta1']."','_self');");	
	}
	
	return $objResponse;
}

function guardarAnularConciliacion($idConciliacion){
    $objResponse = new xajaxResponse();
    
    mysql_query("START TRANSACTION");
    
    $query = sprintf("SELECT id_cuenta FROM te_conciliacion WHERE id_conciliacion = %s",
		valTpDato($idConciliacion, "int"));
    $rs = mysql_query($query);
    if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }
    $row = mysql_fetch_assoc($rs);

    $queryTotalReversion = sprintf("SELECT 
		(SELECT SUM(monto) FROM te_estado_cuenta WHERE suma_resta = 1 AND id_conciliacion = %s) as total_credito, 
		(SELECT SUM(monto) FROM te_estado_cuenta WHERE suma_resta = 0 AND id_conciliacion = %s) as total_debito", 
		valTpDato($idConciliacion, "int"),
		valTpDato($idConciliacion, "int"));
    $rsTotalReversion = mysql_query($queryTotalReversion);
    if(!$rsTotalReversion){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }        
    $rowTotal = mysql_fetch_assoc($rsTotalReversion);

    $resta = $rowTotal["total_credito"] - $rowTotal["total_debito"];

    $queryCuenta = sprintf("SELECT saldo FROM cuentas WHERE idCuentas = '%s'",$row['id_cuenta']);
    $rsCuenta = mysql_query($queryCuenta);
    if(!$rsCuenta){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }   
    $rowCuenta = mysql_fetch_assoc($rsCuenta);

    $saldo = $rowCuenta["saldo"];

    $montoSaldoTotal = $saldo - $resta;
    
    //BUSCO TODOS LOS DOCUMENTOS CONCILIADOS
    $queryEstadoCuenta = sprintf("SELECT * FROM te_estado_cuenta WHERE id_conciliacion = %s",
		valTpDato($idConciliacion, "int"));
	$rsEstadoCuenta = mysql_query($queryEstadoCuenta);                        
    if (!$rsEstadoCuenta) { return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }    
    
    //SEGUN EL TIPO DE DOCUMENTO LO ACTUALIZO    
    while ($rowEstadoCuenta = mysql_fetch_assoc($rsEstadoCuenta)){
		
        if($rowEstadoCuenta['tipo_documento'] == 'DP' ){
            $queryActualiza = sprintf("UPDATE te_depositos SET estado_documento = '%s', fecha_conciliacion = NULL WHERE id_deposito = '%s'",
			2,
			$rowEstadoCuenta['id_documento']);
            $rsActualiza = mysql_query($queryActualiza);
            if (!$rsActualiza) { return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }     
        }
		
        if($rowEstadoCuenta['tipo_documento'] == 'NC'){
            $queryActualiza = sprintf("UPDATE te_nota_credito SET estado_documento = '%s', fecha_conciliacion = NULL WHERE id_nota_credito = '%s'",
			2,
			$rowEstadoCuenta['id_documento']);
            $rsActualiza = mysql_query($queryActualiza);
            if (!$rsActualiza) { return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }
        }
        
        if($rowEstadoCuenta['tipo_documento'] == 'ND'){
            $queryActualiza = sprintf("UPDATE te_nota_debito  SET estado_documento = '%s', fecha_conciliacion = NULL WHERE id_nota_debito  = '%s'",
			2,
			$rowEstadoCuenta['id_documento']);
            $rsActualiza = mysql_query($queryActualiza);
            if (!$rsActualiza) { return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }
        }
        
        if($rowEstadoCuenta['tipo_documento'] == 'CH'){
            $queryActualiza = sprintf("UPDATE te_cheques  SET estado_documento = '%s', fecha_conciliacion = NULL WHERE id_cheque  = '%s'",
			2,
			$rowEstadoCuenta['id_documento']);
            $rsActualiza = mysql_query($queryActualiza);
            if (!$rsActualiza) { return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }
        }
		
        if($rowEstadoCuenta['tipo_documento'] == 'TR'){
            $queryActualiza = sprintf("UPDATE te_transferencia SET estado_documento = '%s', fecha_conciliacion = NULL WHERE id_transferencia = '%s'",
			2,
			$rowEstadoCuenta['id_documento']);
            $rsActualiza = mysql_query($queryActualiza);
            if (!$rsActualiza) { return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }      
        }    
    }
    
    $queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = '%s' WHERE id_conciliacion = '%s'",2,$idConciliacion);           
    $rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
    if (!$rsEstadoCuentaActualiza){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }   
	
    //CUENTAS IMPORTANTE
    $updateCuenta = sprintf("UPDATE cuentas SET saldo = '%s' WHERE idCuentas = '%s'",$montoSaldoTotal,$row['id_cuenta']);
    $rsUpdateCuenta = mysql_query($updateCuenta);
    if (!$rsUpdateCuenta) { return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); } 
    
    //ELIMINAR CONCILIACION
    $queryEliminar = sprintf("DELETE FROM te_conciliacion WHERE id_conciliacion = %s",
		valTpDato($idConciliacion,"int"));
    $rsEliminar = mysql_query($queryEliminar);
    if (!$rsEliminar) { return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }
	
    mysql_query("COMMIT");
    
    $objResponse->alert("Conciliacion Anulada Correctamente");
    $objResponse->script("byId('btnCancelarAnularConciliacion').click(); 
	byId('btnBuscar').click();");
    
    return $objResponse;
}

function listaBanco($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("banco.idBanco != 1");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("banco.nombreBanco LIKE %s",
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		banco.idBanco, 
		banco.nombreBanco, 
		banco.sucursal 
	FROM bancos banco
		INNER JOIN cuentas cuenta ON (cuenta.idBanco = banco.idBanco) %s GROUP BY banco.idBanco", $sqlBusq);
	
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
		$htmlTh .= "<td width=\"5%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaBanco", "15%", $pageNum, "idBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Banco");
		$htmlTh .= ordenarCampo("xajax_listaBanco", "40%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Banco");
		$htmlTh .= ordenarCampo("xajax_listaBanco", "45%", $pageNum, "sucursal", $campOrd, $tpOrd, $valBusq, $maxRows, "Sucursal");		
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'><button type=\"button\" onclick=\"xajax_asignarBanco('".$row['idBanco']."');\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/select.png\"/></button></td>";
			$htmlTb .= "<td align=\"center\">".$row['idBanco']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['sucursal'])."</td>";
		$htmlTb .= "</tr>";		
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"25\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaBanco(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
				
	$objResponse->assign("tdListaBanco","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaBanco1($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("banco.idBanco != 1");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("banco.nombreBanco LIKE %s",
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		banco.idBanco, 
		banco.nombreBanco, 
		banco.sucursal 
	FROM bancos banco
		INNER JOIN cuentas cuenta ON (cuenta.idBanco = banco.idBanco) %s GROUP BY banco.idBanco", $sqlBusq);
	
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
		$htmlTh .= "<td width=\"5%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaBanco1", "15%", $pageNum, "idBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Banco");
		$htmlTh .= ordenarCampo("xajax_listaBanco1", "40%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Banco");
		$htmlTh .= ordenarCampo("xajax_listaBanco1", "45%", $pageNum, "sucursal", $campOrd, $tpOrd, $valBusq, $maxRows, "Sucursal");		
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'><button type=\"button\" onclick=\"xajax_asignarBanco1('".$row['idBanco']."');\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/select.png\"/></button></td>";
			$htmlTb .= "<td align=\"center\">".$row['idBanco']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['sucursal'])."</td>";
		$htmlTb .= "</tr>";		
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"25\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco1(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco1(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaBanco1(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco1(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco1(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
				
	$objResponse->assign("tdListaBanco1","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaConciliacion($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 25, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("te_conciliacion.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("te_conciliacion.id_cuenta = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("te_conciliacion.id_banco = %s",
			valTpDato($valCadBusq[2], "int"));
	}
		
	if ($valCadBusq[3] != "" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("te_conciliacion.fecha BETWEEN %s AND %s ",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[3])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[4])), "date")); 
	}
	
	$query = sprintf("SELECT 
		te_conciliacion.id_conciliacion,
		te_conciliacion.id_cuenta,
		te_conciliacion.id_banco,
		te_conciliacion.monto_conciliado,
		te_conciliacion.monto_libro,
		te_conciliacion.id_empresa,
		te_conciliacion.fecha,
		cuenta.idCuentas,
		cuenta.idBanco,
		cuenta.numeroCuentaCompania,
		banco.idBanco,
		banco.nombreBanco,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM te_conciliacion
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (te_conciliacion.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		INNER JOIN cuentas cuenta ON (te_conciliacion.id_cuenta = cuenta.idCuentas)
		INNER JOIN bancos banco ON (cuenta.idBanco = banco.idBanco) %s",$sqlBusq);
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);        
	$rsLimit = mysql_query($queryLimit);
	if(!$rsLimit){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaConciliacion", "15%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaConciliacion", "10%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaConciliacion", "18%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Banco");
		$htmlTh .= ordenarCampo("xajax_listaConciliacion", "17%", $pageNum, "numeroCuentaCompania", $campOrd, $tpOrd, $valBusq, $maxRows, "Cuenta");
		$htmlTh .= ordenarCampo("xajax_listaConciliacion", "17%", $pageNum, "observacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Observaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaConciliacion", "10%", $pageNum, "monto_conciliado", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Conciliado");
		$htmlTh .= ordenarCampo("xajax_listaConciliacion", "10%", $pageNum, "monto_libro", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo en Libro");
		$htmlTh .= "<td colspan=\"4\"></td>";
	$htmlTh .= "</tr>";
	
	$conta = 0;
	$contb = 0;
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb.= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date("m-Y",strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['numeroCuentaCompania'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['observacion'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['monto_conciliado'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['monto_libro'],2,".",",")."</td>";
			
			$htmlTb .= sprintf("<td align='center' class=\"puntero\" title=\"Editar Conciliacion\"><img src=\"../img/iconos/pencil.png\" onclick=\"window.open('te_conciliacion_editar.php?id_con=%s','_self');\"/></td>",
				$row['id_conciliacion']);
			
			$htmlTb .= sprintf("<td align=\"center\" title=\"Resumen Conciliaci&oacute;n\"><img class=\"puntero\" src=\"../img/iconos/page_white_acrobat.png\" onclick=\"verVentana('reportes/conciliacion_resumen_pdf.php?valBusq=%s|%s|%s',700,700);\" ></td>",
				$row['id_empresa'],
				$row['id_cuenta'],
				$row['id_conciliacion']);
			
			$htmlTb .= sprintf("<td align=\"center\" title=\"Detalle\"><img class=\"puntero\" src=\"../img/iconos/pdf_ico.png\" onclick=\"verVentana('reportes/conciliacion_detalle_pdf.php?valBusq=%s|%s|%s',700,700);\" ></td>",
				$row['id_empresa'],
				$row['id_cuenta'],
				$row['id_conciliacion']);
			
			$htmlTb .= sprintf("<td align=\"center\" title=\"\"><a class=\"modalImg\" id=\"aDesbloquearAnular\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblPermiso', 'te_anular_conciliacion', %s);\"><img src=\"../img/iconos/ico_quitar.gif\" class=\"puntero\" title=\"Anular Conciliación\"/></a>
			<a class=\"modalImg\" id=\"aAbrirAnular\" rel=\"#divFlotante1\"></a>
			</td>",
				$row['id_conciliacion']);
				
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaConciliacion(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaConciliacion(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaConciliacion(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaConciliacion(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaConciliacion(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		
	$objResponse->assign("tdListaConciliacion","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function listaEmpresa($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("usuario_empresa.id_usuario = %s",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"));

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("nombre_empresa LIKE %s",
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}

	$query = sprintf("SELECT 
		usuario_empresa.id_empresa_reg,
		CONCAT_WS(' - ', usuario_empresa.nombre_empresa, usuario_empresa.nombre_empresa_suc) AS nombre_empresa
		FROM vw_iv_usuario_empresa usuario_empresa %s", $sqlBusq);
	
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
		$htmlTh .= "<td width='5%'></td>";
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "15%", $pageNum, "id_empresa_reg", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Empresa");
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "40%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Empresa");		
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'>"."<button type=\"button\" onclick=\"xajax_asignarEmpresa('".$row['id_empresa_reg']."');\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/select.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$row['id_empresa_reg']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_empresa'])."</td>";
		$htmlTb .= "</tr>";		
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"25\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"3\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}		
		
	$objResponse->assign("tdListaEmpresa","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function validarPermiso($frmPermiso) {
	$objResponse = new xajaxResponse();
	
	$idConciliacion = $frmPermiso["hddIdConciliacion"];
	
	$queryPermiso = sprintf("SELECT * FROM vw_pg_claves_modulos
	WHERE id_usuario = %s
		AND contrasena = %s
		AND modulo = %s",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($frmPermiso['txtContrasena'], "text"),
		valTpDato($frmPermiso['hddModulo'], "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	if ($rowPermiso['id_clave_usuario'] != "") {
		if ($frmPermiso['hddModulo'] == "te_anular_conciliacion") {
			$objResponse->assign("hddPasoClaveAnulacion","value",1);
			$objResponse->script(sprintf("
			setTimeout(function(){
				abrirDivFlotante1(byId('aAbrirAnular'), 'tblAnularConciliacion', %s);
			},1500);", 
			valTpDato($idConciliacion, "int")));
		}
	} else {
		$objResponse->alert("Permiso No Autorizado");
	}
	
	$objResponse->script("byId('btnCancelarPermiso').click();");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarBanco");
$xajax->register(XAJAX_FUNCTION,"asignarBanco1");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarFechaConciliacion");
$xajax->register(XAJAX_FUNCTION,"buscarBanco");
$xajax->register(XAJAX_FUNCTION,"buscarBanco1");
$xajax->register(XAJAX_FUNCTION,"buscarConciliacion");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"cargaLstCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstCuenta1");
$xajax->register(XAJAX_FUNCTION,"formAnularConciliacion");
$xajax->register(XAJAX_FUNCTION,"formNuevaConciliacion");
$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion");
$xajax->register(XAJAX_FUNCTION,"guardarAnularConciliacion");
$xajax->register(XAJAX_FUNCTION,"guardarConciliacion");
$xajax->register(XAJAX_FUNCTION,"listaBanco");
$xajax->register(XAJAX_FUNCTION,"listaBanco1");
$xajax->register(XAJAX_FUNCTION,"listaEmpresa");
$xajax->register(XAJAX_FUNCTION,"listaConciliacion");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");

?>