<?php

function asignarMecanico($id_mecanico, $idOrden, $id_tempario, $idDetOrdenTempario){
	$objResponse = new xajaxResponse();
	
	//verificacion si mano de obra pertecene a tipo de mecanico, Mecanica -> Mecanica, Latoneria -> Latoneria
	
	$sqlInfoMecanico = sprintf("SELECT tipo_equipo, activo FROM sa_v_mecanicos WHERE id_mecanico = %s LIMIT 1",
						valTpDato($id_mecanico,"int"));
	$queryInfoMecanico = mysql_query($sqlInfoMecanico);
	if (!$queryInfoMecanico) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowInfoMecanico = mysql_fetch_assoc($queryInfoMecanico);
	
	$activo = $rowInfoMecanico["activo"];
	$modoMecanico = $rowInfoMecanico["tipo_equipo"];
	
	if(!$activo){
		return $objResponse->alert("El mecanico seleccionado se encuentra como Personal Inactivo");
	}
	
	$sqlOperadorTempario = sprintf("SELECT operador FROM sa_tempario WHERE id_tempario = %s LIMIT 1",
							valTpDato($id_tempario,"int"));
	$queryOperadorTempario = mysql_query($sqlOperadorTempario);
	if (!$queryOperadorTempario) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowOperadorTempario = mysql_fetch_assoc($queryOperadorTempario);
	
	$operadorTempario = $rowOperadorTempario["operador"];
	
	if($operadorTempario == 2 || $operadorTempario == 3){ //2:latoneria 3:Pintura
		if ($modoMecanico != "LATONERIA"){
			return $objResponse->alert("Mano de obra \"Latoneria/Pintura\", personal de \"Mecanica\"");
		}
	}
	
	if($operadorTempario == 1){ //1:mecanica
		if($modoMecanico != "MECANICA"){
			return $objResponse->alert("Mano de obra \"Mecanica\", personal de \"Latoneria/pintura\"");
		}
	}
	
	//fin validacion tempario-mecanico
	
		
	mysql_query("START TRANSACTION;");
		/*
	$campos= "";
	$valores= "";
	$sql= "";
	$rs= "";
	$row= "";

	$campos.= "id_mecanico";
	$valores.= $id_mecanico;
	$campos.= ", id_orden";
	$valores.= ", ".$idOrden;
	$campos.= ", id_estado_orden";
	$valores.= ", 0";
	$campos.= ", t_inicio";
	$valores.= ", '0000-00-00 00:00:00'";
	$campos.= ", t_fin";
	$valores.= ", '0000-00-00 00:00:00'";
	$campos.= ", d_min";
	$valores.= ", 0";
	$campos.= ", tipo_mp";
	$valores.= ", 1";
	$campos.= ", t_detenida";
	$valores.= ", '0000-00-00 00:00:00'";
	$campos.= ", d_min_real";
	$valores.= ", 0";
	$campos.= ", d_min_stop";
	$valores.= ", 0";
	$campos.= ", mp_inactivo";
	$valores.= ", 0";
	
	$sql= "INSERT INTO sa_mp (".$campos.") VALUES (".$valores.");";
	$rs = mysql_query($sql);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$idMp= mysql_insert_id();
	
	
	$campos2= "";
	$condicion2= "";
	
	$campos2.= "id_det_orden_tempario";
	$campos2.= ", id_orden";
	$campos2.= ", id_tempario";
	$condicion2.= "id_orden= ".$idOrden;
	$condicion2.= " AND id_tempario= ".$id_tempario;
	
	$sql2= "SELECT ".$campos2." FROM sa_det_orden_tempario WHERE ".$condicion2;
	$rs2 = mysql_query($sql2);
	if (!$rs2) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row2 = mysql_fetch_assoc($rs2);
	
	
	$campos3= "";
	$valores3= "";
	$sql3= "";
	$rs3= "";
	
	$campos3= "id_det_orden_tempario";
	$valores3= $row2['id_det_orden_tempario'];
	$campos3.= ", tiempo_aprobacion";
	$valores3.= ", current_timestamp";
	$campos3.= ", tiempo_asignacion";
	$valores3.= ", current_timestamp";
	$campos3.= ", tiempo_inicio";
	$valores3.= ", current_timestamp";
	$campos3.= ", tiempo_fin";
	$valores3.= ", current_timestamp";
	$campos3.= ", id_mecanico";
	$valores3.= ", ".$id_mecanico;
	$campos3.= ", estado_tempario";
	$valores3.= ", 'TERMINADO'";
	$campos3.= ", tiempo_movimiento";
	$valores3.= ", current_timestamp";
	$campos3.= ", id_empleado_movimiento";
	$valores3.= ", ".$_SESSION['idEmpresaUsuarioSysGts'];
	
	$sql3= "INSERT INTO sa_movimiento_ut_mp (".$campos3.") VALUES (".$valores3.");";
	$rs3 = mysql_query($sql3);
	if (!$rs3) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$id_movimiento_ut_mp= mysql_insert_id();


	$campos4= "";
	$valores4= "";
	$sql4= "";
	$rs4= "";

	$campos4= "id_mp";
	$valores4= $idMp;
	$campos4.= ", id_det_orden_tempario";
	$valores4.= ", ".$row2['id_det_orden_tempario'];
	$campos4.= ", inactivo";
	$valores4.= ", 0";
	$campos4.= ", id_mov";
	$valores4.= ", ".$id_movimiento_ut_mp;
	
	$sql4= "INSERT INTO sa_mp_det (".$campos4.") VALUES (".$valores4.");";
	$rs4 = mysql_query($sql4);
	if (!$rs4) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	*/
	$campos5= "";
	$condicion5= "";
	$sql5= "";
	$rs5= "";

	$campos5= "estado_tempario= 'TERMINADO'";
	$campos5.= ", id_mecanico= ".$id_mecanico;
	$condicion5.= "id_det_orden_tempario= ".$idDetOrdenTempario;

	$sql5= "UPDATE sa_det_orden_tempario SET ".$campos5." WHERE ".$condicion5.";";
	$rs5 = mysql_query($sql5);
	if (!$rs5) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
/*
	$campos6.= "count(*) as totalPendiente";
	$condicion6.= "id_orden= ".$idOrden;
	$condicion6.= " AND estado_tempario= 'PENDIENTE'";

	$sql6= "SELECT ".$campos6." FROM sa_det_orden_tempario WHERE ".$condicion6;
	$rs6 = mysql_query($sql6);
	if (!$rs6) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row6 = mysql_fetch_assoc($rs6);
	$objResponse->alert($sql6);
	$cantPendiente = $row6['totalPendiente'];

	if ($cantPendiente == 0) {
		return $objResponse->alert(" finalizando porque esta completo las mano de obra en 0  ".$cantPendiente);
		$campos7= "";
		$condicion7= "";
		$sql7= "";
		$rs7= "";

		$campos7= "id_estado_orden= 21";
		$condicion7= "id_orden= ".$idOrden;

		$sql7= "UPDATE sa_orden SET ".$campos7." WHERE ".$condicion7.";";
		$rs7 = mysql_query($sql7);
		if (!$rs7) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		$sqlUpdateM = "UPDATE sa_magnetoplano SET
			activo = '0',
			fecha_detenida = NOW(),
			finalizado_administrativo = 1,
			id_empleado = ".$_SESSION['idUsuarioSysGts']."
		WHERE sa_magnetoplano.id_orden = ".$idOrden.";";
		$rsUpdateM = mysql_query($sqlUpdateM);
		if (!$rsUpdateM) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);	
		
		
		$objResponse->script("location.href='sa_orden_servicio_list.php'");
		
	}*/
	
	mysql_query("COMMIT;");

	$objResponse->script("$('amp_window').style.display='none';");
	$objResponse->script("xajax_listMoOrden(document.form_orden.id_orden.value, 'PENDIENTE', 'lista_posiciones');");
	//$objResponse->script("xajax_listMoOrden(document.form_orden.id_orden.value, 'TERMINADO', 'lista_posiciones_asignadas');");
	
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


function cargaLstEmpleado($selId = "", $nombreObjeto = "", $objetoDestino = "",$idEmpresa = "") {
	$objResponse = new xajaxResponse();
	
	if($idEmpresa == ""){
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];	
	}
		
	$query = sprintf("SELECT DISTINCT vw_pg_empleados.id_empleado, vw_pg_empleados.nombre_empleado
	FROM sa_orden
		LEFT JOIN vw_pg_empleados ON (sa_orden.id_empleado = vw_pg_empleados.id_empleado)
		WHERE sa_orden.id_empresa = $idEmpresa
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

function exportarOrdenes($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['txtFechaDesde'],
		$valForm['txtFechaHasta'],
		$valForm['lstEmpleadoVendedor'],
		$valForm['lstTipoOrden'],
		$valForm['lstEstadoOrden'],
		$valForm['txtCriterio']);
	
	$objResponse->script("window.open('reportes/sa_orden_servicio_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function guardarFinalizarOrden($idOrden) {
    $objResponse = new xajaxResponse();
	
	$id_estado_orden = 21;
	
	if (!xvalidaAcceso($objResponse,"sa_mp_estado_orden")) {
		return $objResponse;
	}
	
	if ($id_estado_orden == 21) {
		if (!xvalidaAcceso($objResponse,'sa_mp_finalizar_trabajos')) {
			return $objResponse->alert('No tiene permisos para FINALIZAR TRABAJOS en la Orden');
		}
	
		//VERIFICA QUE NO FINALICE ORDEN GENERICA
		$sqlTipoOrden = "SELECT sa_tipo_orden.orden_generica
		FROM sa_orden
			INNER JOIN sa_tipo_orden ON (sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden) 
		WHERE id_orden = ".$idOrden;
		$rsTipoOrden = mysql_query($sqlTipoOrden);
		if (!$rsTipoOrden) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowTipoOrden = mysql_fetch_array($rsTipoOrden);
		
		if($rowTipoOrden['orden_generica']==1){
			return $objResponse->alert('No puede finalizar una orden Generica');
		}
                
                //VERIFICO SI TIPO DE ORDEN ES ACCESORIOS O BLINDAJE Y NECESITA VENDEDOR
                $queryTipoOrden = sprintf("SELECT sa_filtro_orden.tot_accesorio, id_empleado
                                   FROM sa_orden
                                   INNER JOIN sa_tipo_orden ON sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden
                                   INNER JOIN sa_filtro_orden ON sa_tipo_orden.id_filtro_orden = sa_filtro_orden.id_filtro_orden
                                   WHERE id_orden = %s AND sa_filtro_orden.tot_accesorio = 1 LIMIT 1",
                            valTpDato($idOrden, "int"));        
                $rsTipoOrden2 = mysql_query($queryTipoOrden);
                if (!$rsTipoOrden2) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

                if(mysql_num_rows($rsTipoOrden2)){//si es tipo vehiculos
                    
                    $rowTipoOrden = mysql_fetch_assoc($rsTipoOrden2);
                    $idEmpleadoOrden = $rowTipoOrden['id_empleado'];
                    
                    $queryVendedores = sprintf("SELECT id_empleado, CONCAT_WS(' ', nombre_empleado, apellido) as nombre_empleado
                        FROM pg_empleado
                        INNER JOIN pg_cargo_departamento ON pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento
                        INNER JOIN pg_departamento ON pg_cargo_departamento.id_departamento = pg_departamento.id_departamento
                        WHERE pg_empleado.id_empleado = %s AND (clave_filtro = 1 OR clave_filtro = 2) ",
                        valTpDato($idEmpleadoOrden, "int"));
                        $rsVendedores = mysql_query($queryVendedores);        
                        if (!$rsVendedores) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
                        
                    if(!mysql_num_rows($rsVendedores)){
                       return $objResponse->alert("Debe asignarle un vendedor a este tipo de orden");
                    }
                    
                }
		
		//VERIFICA QUE TODOS LOS TEMPARIOS ESTAN FINALIZADOS
		$sqlTotalTempario = "SELECT COUNT(id_orden) as cant 
		FROM sa_det_orden_tempario 
		WHERE sa_det_orden_tempario.aprobado = 1 
			AND sa_det_orden_tempario.id_mecanico IS NULL
			#AND sa_det_orden_tempario.estado_tempario <> 'TERMINADO'
			AND sa_det_orden_tempario.estado_tempario <> 'DEVUELTO'
			AND id_orden =".$idOrden."
		GROUP by id_orden;";
		$rsTotalTempario = mysql_query($sqlTotalTempario);
		if (!$rsTotalTempario) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowTotalTempario = mysql_fetch_array($rsTotalTempario);
		
		if ($rowTotalTempario['cant'] != 0) {
			return $objResponse->alert('No puede finalizar la orden porque existen Manos de Obra sin Mecanico o sin Terminar');
		}
		
		//VERIFICA CUMPLIMIENTO DE REPUESTOS
		$sqlRepuestos = "SELECT COUNT(*) as total_rpto 
		FROM sa_orden 
			INNER JOIN sa_solicitud_repuestos ON (sa_orden.id_orden = sa_solicitud_repuestos.id_orden) 
			INNER JOIN sa_det_solicitud_repuestos ON (sa_solicitud_repuestos.id_solicitud = sa_det_solicitud_repuestos.id_solicitud) 
		WHERE sa_orden.id_orden = ".$idOrden." 
			AND sa_det_solicitud_repuestos.id_estado_solicitud IN (1,2) 
			AND sa_solicitud_repuestos.estado_solicitud != 6;";
		$rsRepuestos = mysql_query($sqlRepuestos);
		if (!$rsRepuestos) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowRepuestos = mysql_fetch_array($rsRepuestos);
		
		if ($rowRepuestos['total_rpto'] != 0) {
			return $objResponse->alert('La Orden no puede terminar, debido a que tiene Solicitud(es) de Repuestos pendiente(s).\nVerifique las Solicitudes de la Orden');
		}
			
		// VERIFICAR TOT
		$sqlTOT = "SELECT COUNT(*) as cant 
		FROM sa_orden_tot 
		WHERE (estatus = 0 OR estatus = 1) 
			AND id_orden_servicio=".$idOrden.";";
		$rsTOT = mysql_query($sqlTOT);
		if (!$rsTOT) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowTOT = mysql_fetch_array($rsTOT);
		
		if ($rowTOT['cant'] != 0) {
			return $objResponse->alert('No puede finalizar la orden porque existen TOT no Asignados');
		}

		$sqlUpdateM = "UPDATE sa_magnetoplano SET
			activo = '0',
			fecha_detenida = NOW(),
			finalizado_administrativo = 1,
			id_empleado = ".$_SESSION['idUsuarioSysGts']."
		WHERE sa_magnetoplano.id_orden = ".$idOrden.";";
		$rsUpdateM = mysql_query($sqlUpdateM);
		if (!$rsUpdateM) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);	
		
		$sqlUpdate = "UPDATE sa_orden SET
			id_estado_orden = '21' 
		WHERE sa_orden.id_orden = ".$idOrden.";";
		$rsUpdate = mysql_query($sqlUpdate);
		if (!$rsUpdate) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
                
                //tiempo finalizado la orden
		$sqlUpdate = "UPDATE sa_orden SET
			tiempo_finalizado = NOW() 
		WHERE sa_orden.id_orden = ".$idOrden." AND tiempo_finalizado IS NULL;";
		$rsUpdate = mysql_query($sqlUpdate);
                if (!$rsUpdate) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
		$objResponse->alert("Orden Finalizada");
		$objResponse->script("location.href='sa_orden_servicio_list.php'");
	}
	
	return $objResponse;
}


function listMecanicos($idEmpresaOrden){//es el id de la orden que abren cuando van a finalizar
	$objResponse = new xajaxResponse();
	
	$sql = sprintf("SELECT * FROM sa_v_mecanicos
	WHERE id_empresa = %s
		AND id_mecanico IS NOT null",
		valTpDato($idEmpresaOrden,"int"));
	$rs = mysql_query($sql);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"id_mecanico_mp\" name=\"id_mecanico_mp\">";
			$html .= "<option value=\"-1\">Seleccione...</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<option value=\"".$row['id_mecanico']."\" ".$seleccion.">".utf8_encode($row['apellido']." ".$row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign('field_mecanico_mp', "innerHTML",$html);
	
	return $objResponse;
}


function listMoOrden($idOrden, $estadoTempario, $capa){
	$objResponse = new xajaxResponse();
        
        $SqlEmpresaOrden = "SELECT id_empresa FROM sa_orden WHERE id_orden = ".$idOrden. " LIMIT 1";
        $rsEmpresaOrden = mysql_query($SqlEmpresaOrden);
        
        if (!$rsEmpresaOrden) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
        $rowEmpresaOrden = mysql_fetch_assoc($rsEmpresaOrden);
        
        $idEmpresaOrden = $rowEmpresaOrden['id_empresa'];
        
        
	
        /////////////////////////vendedores de vehiculos
        $queryTipoOrden = sprintf("SELECT sa_filtro_orden.tot_accesorio, id_empleado
                                   FROM sa_orden
                                   INNER JOIN sa_tipo_orden ON sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden
                                   INNER JOIN sa_filtro_orden ON sa_tipo_orden.id_filtro_orden = sa_filtro_orden.id_filtro_orden
                                   WHERE id_orden = %s AND sa_filtro_orden.tot_accesorio = 1 LIMIT 1",
                            valTpDato($idOrden, "int"));
        $rsTipoOrden = mysql_query($queryTipoOrden);
        if (!$rsTipoOrden) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
        
        if(mysql_num_rows($rsTipoOrden)){
            $rowOrden = mysql_fetch_assoc($rsTipoOrden);
            $idEmpleadoOrden = $rowOrden["id_empleado"];
            
            $queryVendedores = sprintf("SELECT id_empleado, CONCAT_WS(' ', nombre_empleado, apellido) as nombre_empleado
                        FROM pg_empleado
                        INNER JOIN pg_cargo_departamento ON pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento
                        INNER JOIN pg_departamento ON pg_cargo_departamento.id_departamento = pg_departamento.id_departamento
                        WHERE id_empresa = %s AND (clave_filtro = 1 OR clave_filtro = 2) AND pg_empleado.activo = 1",
                        valTpDato($idEmpresaOrden, "int"));
            $rsVendedores = mysql_query($queryVendedores);        
            if (!$rsVendedores) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

            $html = "<select id='idVendedor' name='idVendedor' onChange='habilitarBtnFinalizar(this.value);'>";
            $html .= "<option value=''>SELECCIONE</option>";
            $vendedorYaEnOrden = 0;
            while($rowVendedores = mysql_fetch_assoc($rsVendedores)){
                $selected = "";
                if($rowVendedores['id_empleado'] == $idEmpleadoOrden){
                    $selected = "selected='selected'";
                    $vendedorYaEnOrden++;
                }
                $html .= "<option value='".$rowVendedores['id_empleado']."' ".$selected." >".utf8_encode($rowVendedores['nombre_empleado'])."</option>";
            }
            $html .= "</select>";
            
            $html .= '&nbsp;<button onclick="xajax_asignarVendedorOrden('.$idOrden.',$(\'idVendedor\').value);" style="white-space:nowrap;" type="button">
                        <img class="image_button" src="../img/iconos/people1.png">Asignar Vendedor
                      </button>';
            
            $objResponse->assign($capa, "innerHTML",$html);
            $objResponse->assign("tituloLegend", "innerHTML","Asignaci&oacute;n Vendedor en Orden");
            if($vendedorYaEnOrden){
                $objResponse->script("$('boton_asignar_mp2').disabled = false;");
            }else{
                $objResponse->script("$('boton_asignar_mp2').disabled = true;");
            }
            
            
            return $objResponse;
        }
        ////////////////////////fin vendedores
        
        
        
        
	if ($estadoTempario == "PENDIENTE") {
		$imgSrcEstado= "../img/iconos/pause.png";
		$imgSrcButton= "../img/iconos/people1.png";
		$accion= "Asignar";
	} else {
		$imgSrcEstado= "../img/iconos/select.png";
		$imgSrcButton= "";
		$accion= "";
	}
        //OJO NO AGARRA LA EMPRESA QUE ES
	$sql = "SELECT * FROM sa_v_mo_orden
	WHERE id_orden = ".$idOrden."
		#AND estado_tempario = '".$estadoTempario."'
		AND estado_tempario != 'DEVUELTO'
		AND estado_tempario != 'FACTURADO'
	ORDER BY id_modo;";
	$rs = mysql_query($sql);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<table class='order_table' style='width:910px;'>
			<thead>
				<tr>
					<td>C&oacute;digo</td>
					<td>Descripci&oacute;n</td>
					<td>Modo</td>
					<td>Op.</td>
					<td>Precio</td>
					<td>Costo</td>
					<td>Mec&aacute;nico</td>
					<td>Estado</td>";
	if ($estadoTempario == "PENDIENTE") {
		$html.= "<td>Acci&oacute;n</td>";
	}
	$html.= "</tr>
			</thead>
			<tbody>";

	while ($row = mysql_fetch_assoc($rs)) {
		$html.= "<tr>
					<td>".$row['codigo_tempario']."</td>
					<td>".utf8_encode($row['descripcion_tempario'])."</td>
					<td>".utf8_encode($row['descripcion_modo'])."</td>
					<td>".utf8_encode($row['operador'])."</td>
					<td>".$row['precio']."</td>
					<td>".$row['costo']."</td>
					<td><b>".utf8_encode($row['nombre_completo'])."</b></td>
					<td nowrap='nowrap'>
						<img class='icon_button' style='vertical-align:middle;' src='".$imgSrcEstado."' border='0' alt='".$row['estado_tempario.']."' />"
						.$row['estado_tempario']."
					</td>";
		if ($estadoTempario == "PENDIENTE") {
			$html.= "<td>
						<button  type='button' style='white-space:nowrap;' onclick='abrirMecanico(".$row['id_tempario'].",".$row['id_det_orden_tempario'].",".$idEmpresaOrden.");'>
							<img src='".$imgSrcButton."' class='image_button' />".$accion."
						</button>
					</td>";
		}
		$html.= "</tr>";
	}
	$html.= "</tbody></table>";
        
        $objResponse->script("$('boton_asignar_mp2').disabled = false;");
        $objResponse->assign("tituloLegend", "innerHTML","Posiciones de trabajo:");
	$objResponse->assign($capa, "innerHTML",$html);
	
	return $objResponse;
}


function listadoOrdenes($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	//mysql_query("SET NAMES utf8");//sirve para busquedas con Ñ pero el utf8_encode se daña, y utf8_encode vacio. no usar ninguno si usas set names.
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("orden.id_estado_orden NOT IN (18,21,24)");
	
	//sino se le envia busqueda que agarre la de la session
	if($valCadBusq[0] == ""){
		$valCadBusq[0] = $_SESSION['idEmpresaUsuarioSysGts'];
		}
	
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
	
	$query = sprintf("SELECT *,
		orden.tiempo_orden,
		orden.id_orden,
		recepcion.id_recepcion,
		orden.id_empresa,

		(SELECT nombre_empleado FROM vw_pg_empleados
			WHERE id_empleado = orden.id_empleado) AS nombre_empleado,

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
                total_orden,
		#(SELECT sa_orden.numero_orden FROM sa_orden WHERE sa_orden.id_orden = orden_retrabajo.id_orden_retrabajo) AS numero_orden_retrabajo,
		#((((orden.subtotal - orden.subtotal_descuento) * orden.iva) / 100) + (orden.subtotal - orden.subtotal_descuento)) AS total,
		#ESTE ES EL QUE MUESTRA MONTO REAL SIN ROLL;O DE COMA
                ((subtotal + subtotal_iva) - orden.subtotal_descuento) as total_base
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
	if (!$rsLimit) return $objResponse->alert($queryLimit."\n".mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "6%", $pageNum, "tiempo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("Fecha"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "5%", $pageNum, "numero_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("N° Orden"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "7%", $pageNum, "numeracion_recepcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("N° Recepción"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "10%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, ("Asesor"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "12%", $pageNum, "nombre_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo Orden"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "20%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "6%", $pageNum, "nom_uni_bas", $campOrd, $tpOrd, $valBusq, $maxRows, ("Catálogo"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "7%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, ("Placa"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "11%", $pageNum, "chasis", $campOrd, $tpOrd, $valBusq, $maxRows, ("Chasis"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "7%", $pageNum, "nombre_estado", $campOrd, $tpOrd, $valBusq, $maxRows, ("Estado"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "2%", $pageNum, "numero_orden_retrabajo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Ord. Retrabajo"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "7%", $pageNum, "total_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("Total"));//antes total
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
			$htmlTb .= "<td align=\"right\" idordenoculta=\"".($row['id_orden'])."\" idempresaoculta=\"".($row['id_empresa'])."\">".($row['numero_orden'])."</td>";
			$htmlTb .= "<td align=\"right\" idrecepcionoculto =\"".($row['id_recepcion'])."\">".($row['numeracion_recepcion'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_tipo_orden'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_cliente']).$clienteAnterior."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nom_uni_bas'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['placa'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['chasis'])."</td>";
			$htmlTb .= "<td align=\"center\" style=\"background:#".$row['color_estado']."; color:#".$row['color_fuente']."\">".utf8_encode($row['nombre_estado'])."</td>";
			$htmlTb .= "<td align=\"left\" idordenocultaretrabajo =\"".$row['id_orden_retrabajo']."\" >".$row['numero_orden_retrabajo']."</td>";
			$htmlTb .= "<td align=\"right\" antiguaSumaBase=\"".$row['total_base']."\">".number_format($row['total_orden'],2,".",",")."</td>";//antes total
			
                        $htmlTb .= "<td class=\"noprint\">";
			//Boton generar presupuesto
			if($row['id_tipo_estado'] != 5){//FINALIZADO si esta finalizado o terminado 13 no permitir modificar mas
				$htmlTb .= sprintf("<img class=\"puntero\" title=\"Generar Presupuesto\" onclick=\"window.open('sa_orden_form.php?doc_type=4&id=%s&ide=%s&acc=2','_self');\" src=\"../img/iconos/generarPresupuesto.png\"/>",
					$row['id_orden'],
					$row['id_empresa']);
			}
			$htmlTb .= "</td>";
                        
			//boton aprobar orden para solicitud de repuestos
			$htmlTb .= "<td class=\"noprint\">";
			if (!($row['id_tipo_orden'] == 5)//SIN ASIGNAR
			&& !($row['id_tipo_estado'] == 4)//DETENIDO
			&& !($row['id_tipo_estado'] == 5)//FINALIZADO
			&& !(($row['id_tipo_estado'] == 1 || $row['id_tipo_estado'] == 2 || $row['id_tipo_estado'] == 3) //1 ABIERTO //2 EN ESPERA //3 PROCESO
				&& $row['id_estado_orden'] == 2 || $row['id_estado_orden'] == 22)) {//2 Diagnostico //22 Diagnostico Finalizado
				$htmlTb .= sprintf("<img class=\"puntero\" title=\"Aprobacion de Orden\" onclick=\"xajax_verificarBloqueoSolicitud('%s','%s',4);\" src=\"../img/iconos/aprobar_presup.png\"/></td>",
					$row['id_orden'],
					$row['id_empresa']);
			}
			$htmlTb .= "</td>";
			//Boton ver orden
			$htmlTb .= "<td class=\"noprint\">";
				$htmlTb .= sprintf("<img class=\"puntero\" title=\"Ver Orden\" onclick=\"window.open('sa_orden_form.php?doc_type=2&id=%s&ide=%s&acc=2&cons=0','_self');\" src=\"../img/iconos/ico_view.png\"/>",
					$row['id_orden'],
					$row['id_empresa']);
			$htmlTb .= "</td>";
			//Boton editar orden
			$htmlTb .= "<td class=\"noprint\">";
			if ($row['id_tipo_estado'] != 5) { // TERMINADO
				$htmlTb .= sprintf("<img class=\"puntero\" title=\"Editar Orden\" onclick=\"xajax_verificarBloqueoSolicitud('%s','%s',3);\" src=\"../img/iconos/ico_edit.png\"/>",
					$row['id_orden'],
					$row['id_empresa']);
			}
			$htmlTb .= "</td>";
			//Boton finalizar orden
			$htmlTb .= "<td class=\"noprint\">";
			if ($row['id_tipo_estado'] == 3) { // PROCESO
				$htmlTb .= sprintf("<img class=\"puntero\" title=\"Finalizar Orden\" onclick=\"xajax_verificarAprobacion('%s');\" src=\"../img/iconos/time_go.png\"/>",
						$row['id_orden']);
			}
			$htmlTb .= "</td>";
			//Boton imprimir orden pdf
			$htmlTb .= "<td class=\"noprint\">";
				$htmlTb .= sprintf("<img class=\"puntero\" title=\"Imprimir Orden PDF\" onclick=\"verVentana('sa_imprimir_presupuesto_pdf.php?valBusq=%s|2|0', 1000, 500);\" src=\"../img/iconos/ico_print.png\"/>",
					$row['id_orden']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		/*if ($row['id_orden'] == 1490) {
			$objResponse->alert("Id Tipo Orden: ".$row["id_tipo_orden"]);
			$objResponse->alert("Id Tipo Estado: ".$row["id_tipo_estado"]);
			$objResponse->alert("Id Estado Orden: ".$row["id_estado_orden"]);
		}*/
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


function verificarAprobacion($idOrden){
    $objResponse = new xajaxResponse();
	
	//mostrar numero de orden en recuadro de clave para finalizar orden
	$sqlNumeroOrden = sprintf("SELECT numero_orden FROM sa_orden WHERE id_orden = %s LIMIT 1",
								valTpDato($idOrden,"int"));
	$queryNumeroOrden = mysql_query($sqlNumeroOrden);
	if (!$queryNumeroOrden) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowNumeroOrden = mysql_fetch_assoc($queryNumeroOrden);
	
	$numeroOrden = $rowNumeroOrden["numero_orden"];
	
    $campos = "";
    $condicion = "";
    
    $campos = "COUNT(*) as cant";
    $condicion = "id_det_orden_articulo IN(SELECT id_det_orden_articulo FROM sa_det_orden_articulo s where id_orden= ".$idOrden.")";
    $condicion .= " AND id_solicitud IN (SELECT id_solicitud FROM sa_solicitud_repuestos WHERE id_orden= ".$idOrden." AND estado_solicitud != 6)";

    $sql = "SELECT ".$campos." FROM sa_det_solicitud_repuestos WHERE ".$condicion;
    $rs = mysql_query($sql);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
    $row = mysql_fetch_assoc($rs);

    $campos1 = "";
    $condicion1 = "";
 
    $campos1 = "id_estado_orden";
    $condicion1 = "id_orden = ".$idOrden;

    $sql1 = "SELECT ".$campos1." FROM sa_orden WHERE ".$condicion1;
    $rs1 = mysql_query($sql1);
	if (!$rs1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
    $row1 = mysql_fetch_assoc($rs1);

    if ($row1['id_estado_orden'] != 6) {
        $objResponse->script("alert('Debe Aprobar la orden antes de continuar');");
    } else {
        if ($row['cant'] != 0) {
            $campos2 = "";
            $condicion2 = "";

            $campos2 = "COUNT(*) as cant";
            $condicion2 = "id_det_orden_articulo IN(SELECT id_det_orden_articulo FROM sa_det_orden_articulo s where id_orden= ".$idOrden.") ";
            $condicion2 .= "AND (id_estado_solicitud= 4 OR id_estado_solicitud= 3)";

            $sql2 = "SELECT ".$campos2." FROM sa_det_solicitud_repuestos WHERE ".$condicion2;
            $rs2 = mysql_query($sql2);
			if (!$rs2) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
            $row2 = mysql_fetch_assoc($rs2);
        
            if ($row2['cant'] == 0) {
                $objResponse->script("alert('Debe Solicitar los repuestos de la orden antes de continuar');");
            } else {
                $campos3 = "";
                $condicion3 = "";

                $campos3 .= "COUNT(*) as cant";
                $condicion3 .= "(estatus = 0 or estatus = 1) ";
                $condicion3 .= "AND id_orden_servicio=".$idOrden;

                $sql3 = "SELECT ".$campos3." FROM sa_orden_tot WHERE ".$condicion3;
                $rs3 = mysql_query($sql3);
				if (!$rs3) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
                $row3 = mysql_fetch_assoc($rs3);

                if ($row3['cant'] != 0) {
                    $objResponse->script('alert("No puede finalizar la orden porque existen TOT no Asignados");');
                } else {
                    $objResponse->script("abrir(".$idOrden.",".$numeroOrden.");");
                }
            }
        } else {
            $objResponse->script("abrir(".$idOrden.",".$numeroOrden.");");
        }
    }
    return $objResponse;
}


function verificarBloqueoSolicitud($idOrden, $idEmpresa, $acc) {
    $objResponse = new xajaxResponse();

    $sqlOrden = "SELECT id_usuario_bloqueo FROM sa_solicitud_repuestos WHERE id_orden = ".$idOrden;
    $rsOrden = mysql_query($sqlOrden);
	if (!$rsOrden) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
    $rowOrden = mysql_fetch_array($rsOrden);

    if ($rowOrden['id_usuario_bloqueo'] != NULL) {
        $objResponse->alert('La solicitud de repuestos de esta orden se encuentra en edicion por otro usuario espera que la misma sea terminada.');
    } else {
		// BLOQUEA LA ORDEN //antes usaba idEmpresaUsuarioSysGts
        $sqlOrden = "UPDATE sa_orden SET
			id_usuario_bloqueo = ".$_SESSION['idUsuarioSysGts']."
		WHERE id_orden = ".$idOrden;
		$rsOrden = mysql_query($sqlOrden);
	
        $objResponse->script("window.open('sa_orden_form.php?doc_type=2&id=".$idOrden."&ide=".$idEmpresa."&acc=".$acc."','_self');");
    }

    return $objResponse;
}
$xajax->register(XAJAX_FUNCTION,"asignarMecanico");
$xajax->register(XAJAX_FUNCTION,"buscarOrden");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresaBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoOrden");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoOrden");
$xajax->register(XAJAX_FUNCTION,"exportarOrdenes");
$xajax->register(XAJAX_FUNCTION,"guardarFinalizarOrden");
$xajax->register(XAJAX_FUNCTION,"listMecanicos");
$xajax->register(XAJAX_FUNCTION,"listMoOrden");
$xajax->register(XAJAX_FUNCTION,"listadoOrdenes");
$xajax->register(XAJAX_FUNCTION,"verificarAprobacion");
$xajax->register(XAJAX_FUNCTION,"verificarBloqueoSolicitud");
$xajax->register(XAJAX_FUNCTION,"asignarVendedorOrden");

function asignarVendedorOrden($idOrden, $idEmpleadoVendedor){
    $objResponse = new xajaxResponse();
    
    if($idEmpleadoVendedor == "" || $idEmpleadoVendedor == NULL){
        return $objResponse->alert("Debe seleccionar un vendedor");
    }
    
    $query = sprintf("UPDATE sa_orden SET id_empleado = %s WHERE id_orden = %s",
                valTpDato($idEmpleadoVendedor,"int"),
                valTpDato($idOrden,"int"));
    $rs = mysql_query($query);
    if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
    
    $objResponse->alert("Asignado Correctamente");
    
    return $objResponse;
}

?>