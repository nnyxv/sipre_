<?php 
//ASIGNA LOS EMPLEADO AL EQUIPO	 
function agregarIntegrante($fomrEquipo){
	$objResponse = new xajaxResponse();

	if(isset($fomrEquipo['checkItemIntegrante'])){
		foreach($fomrEquipo['checkItemIntegrante'] as $indice => $valor){
			// ACTUALIZA EL ESTADO DEL INTEGRANTE
			$queryInte = sprintf("UPDATE crm_integrantes_equipos 
									SET activo = %s
									WHERE id_integrante_equipo = %s",
							valTpDato(1, "int"), // 0 = INACTIVO; 1 ACTIVO
							valTpDato($fomrEquipo['hddIintegrante'.$valor] , "int"));
			$rsInte = mysql_query($queryInte);
			if (!$rsInte) $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query);
			$objResponse->assign("tdImg".$valor,"innerHTML",sprintf("<img id=\"icorojo%s\" src=\"../img/iconos/ico_verde.gif\">",$valor));
		}
		$objResponse->script("
		if(byId('checkItemIntegrante').checked == true){
			byId('checkItemIntegrante').click();
		}");
	} else {
		$objResponse->script("openImg(divFlotante3);");
		$valBusq = sprintf("%s|%s||addIntegrante",
			$fomrEquipo['txtIdEmpresa'],
			$fomrEquipo['comboxTipoEquipo']
		);
		$objResponse->loadCommands(listaEmpleado("","","",$valBusq));
	}
	
	return $objResponse;
}

//PARA ASIGNAR UN JEFE DE EQUIPO
function asignarJefeEquipo($idEmpleado){
	$objResponse = new xajaxResponse();
	
		$sql = sprintf("SELECT id_empleado ,CONCAT_WS(' ',nombre_empleado,apellido) AS nombre_empleado FROM pg_empleado 
			WHERE id_empleado = %s",
		valTpDato($idEmpleado,"int"));
		$rs = mysql_query($sql);
		if (!$rs) return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rows = mysql_fetch_array($rs);
		
		$objResponse->assign("idHiddJefeEquipo","value",$rows['id_empleado']);
		$objResponse->assign("textJefeEquipo", "value",$rows['nombre_empleado']);
		$objResponse->script("byId('butCancelarJefeEquipo').click();");
		
	return $objResponse;
}
		
//HACE LA BUSQUDA SEGUN LA EMPRESA SELECCIONADO
function buscarEquipo($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['comboxBusTipoEquipo'],
		$valForm['LstEstatusBus'],
		$valForm['textCriterio']);
	$objResponse->loadCommands(listaEquipo(0, "", "", $valBusq));
	
	return $objResponse;
}

function buscarEmpresa($frmBuscarEmpresa) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarEmpresa['hddObjDestino'],
		$frmBuscarEmpresa['hddNomVentana'],
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
		
	$objResponse->loadCommands(listadoEmpresasUsuario(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

//BUSCAR LOS EMPLEADO	
function buscarEmpleado($frmEmpleado,$fomrEquipo){
	$objResponse = new xajaxResponse();
		
		$valBusq = sprintf("%s|%s|%s|%s",
			$fomrEquipo['txtIdEmpresa'],
			$fomrEquipo['comboxTipoEquipo'],
			$frmEmpleado['textCriterioBusEmpleado'],
			$frmEmpleado['hddBuscar']);

		$objResponse->loadCommands(listaEmpleado(0, "", "", $valBusq));
		
	return $objResponse;
}

//CARGAR EL FORMULARIO PARA EDITAR EQUIPO
function cargarEquipo($idEquipo = "") {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("xajax_eliminarIntegrante(xajax.getFormValues('fomrEquipo'));
		$('#comboxTipoEquipo').remove();");

	$query = sprintf("SELECT id_equipo, nombre_equipo, descripcion_equipo, crm_equipo.activo, crm_equipo.id_empresa, nombre_empresa, 
		jefe_equipo, tipo_equipo, CONCAT(nombre_empleado,' ', apellido) AS nombre_empleado
			FROM crm_equipo
		LEFT JOIN pg_empresa ON pg_empresa.id_empresa = crm_equipo.id_empresa
		LEFT JOIN pg_empleado ON pg_empleado.id_empleado = crm_equipo.jefe_equipo
		WHERE id_equipo = %s;",
	valTpDato($idEquipo, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query);
	$row = mysql_fetch_assoc($rs);

	// SE LLENAN LOS CAMPOS SEGUN LO ALMACENADO EN TABLA
	$objResponse->loadCommands(asignarEmpresaUsuario(($row['id_empresa'] != "") ? $row['id_empresa']: $_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa"));
	$objResponse->assign("hddidEquipo","value",$row['id_equipo']);//COTIENE EL QUE VIEN DEL LINK
	$objResponse->assign("txtNombreEquipo","value",utf8_encode($row['nombre_equipo']));
	$objResponse->loadCommands(comboxTipoEquipo("tdTipoEquipo",$row['tipo_equipo']));
	$objResponse->assign("areaEquipoDescripcion","value",utf8_encode($row['descripcion_equipo']));
	$objResponse->assign("textJefeEquipo","value",utf8_encode($row['nombre_empleado']));
	$objResponse->assign("idHiddJefeEquipo","value",$row['jefe_equipo']);
	$objResponse->call("selectedOption","listEstatus",$row['activo']);
	
	// VERIFICA SI TIENE INTEGRANTE EL QUIPO
	$query2 = sprintf("SELECT * FROM crm_integrantes_equipos WHERE id_equipo = %s ORDER BY activo DESC",
	valTpDato($idEquipo, "int"));
	$rs2 = mysql_query($query2);
	if (!$rs2) $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query2);
	$numInte = mysql_num_rows($rs2);
	while($rows2 = mysql_fetch_array($rs2)){
		$Result1 = itemIntegrante($contFila,$rows2['activo'],$rows2['id_empleado'],$rows2['id_integrante_equipo']);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$contFila = $Result1[2];
			$objResponse->script($Result1[1]);
		}
	}

	if($idEquipo == ""){
		$objResponse->script("acciones('buttonJefeEquipo','disabled',true);");
		$result = buscarTipoUsuario(); 
		if($result[0] == true){
			if($result[1] != NULL){// CUANDO SEA UN VENDEDOR O ASESOR
				$objResponse->call("selectedOption","comboxTipoEquipo",$result[1]);
				$objResponse->script(sprintf("
					acciones('buttonJefeEquipo','disabled',(byId('comboxTipoEquipo').value != '') ? false: true); 
					byId('comboxTipoEquipo').onchange = function() { 
						selectedOption(this.id,'%s');	
					}
				",$result[1]));
			}else{
				$objResponse->script(sprintf("
					byId('comboxTipoEquipo').onchange = function() { 
						acciones('buttonJefeEquipo','disabled',(byId('comboxTipoEquipo').value != '') ? false: true); 
						document.getElementById('idHiddJefeEquipo').value = '';
						document.getElementById('textJefeEquipo').value = '';
					}
				"));
			}
		}	
	}else{
		$objResponse->script("acciones('buttonJefeEquipo','disabled',false);");
		if($numInte > 0){
			$objResponse->script(sprintf("
				byId('comboxTipoEquipo').onchange = function() { 
					selectedOption(this.id,'%s');
				}
			",$row['tipo_equipo']));	
		}else{
			$objResponse->script(sprintf("
				byId('comboxTipoEquipo').onchange = function() { 
					document.getElementById('idHiddJefeEquipo').value = '';
					document.getElementById('textJefeEquipo').value = '';
				}"));
		}
	}

	return $objResponse;
}

function comboxTipoEquipo($idObj,$tipo = ""){
	$objResponse = new xajaxResponse();

	$result = buscarTipoUsuario();
	if($result[0] == true){
		if($result[1] != NULL){
			$class = "class=\"inputInicial\"";
			$objResponse->script("acciones('buttonJefeEquipo','disabled',false);");
		}else{
			$class = "class=\"inputHabilitado\"";
		}
	}else{
		return $objResponse->alert($result[1]);	
	}

	$id = ($idObj != "tdBusLstTipaEquipo")? "comboxTipoEquipo" :"comboxBusTipoEquipo";
	$name = ($idObj != "tdBusLstTipaEquipo")? "comboxTipoEquipo" :"comboxBusTipoEquipo";
	$onchange =($idObj != "tdBusLstTipaEquipo") ? "" :"onchange=\"byId('btnBuscar').click();\""; 
	
	$html = sprintf("<select id=\"%s\" name=\"%s\" %s %s>",$id,$name,$class,$onchange);
		$html .= "<option value=\"\">[ Seleccione ]</option>";
		
	$result = mysql_query('SHOW COLUMNS FROM crm_equipo WHERE field="tipo_equipo"');
	while ($row = mysql_fetch_row($result)) {
		foreach(explode("','",substr($row[1],6,-2)) as $option) {
			$checked = ($tipo == $option) ? "selected='selected'" :"";
			$html .= sprintf("<option id=\"%s\" %s>%s</option>", $option, $checked, $option);
		} 
	}
		
	$html .= "</select>";
	
	$objResponse->assign($idObj,"innerHTML",$html);
	
	return $objResponse;
}

//ELIMINAR LOS EQUIPO
function eliminarEquipo($idEquipo ) {
	$objResponse = new xajaxResponse();
	
	//VALIDA SI TIENE PERMISO DE ELIMINAR
	if (!xvalidaAcceso($objResponse,"crm_integrantes_equipo_list","eliminar")) { return $objResponse; }
	
	$query = sprintf("SELECT * FROM crm_integrantes_equipos WHERE id_equipo = %s", $idEquipo);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);

	$num = mysql_num_rows($rs);
	$intEstatus = array();
	while($rows = mysql_fetch_assoc($rs)){
		$intEstatus[0] += ($rows['activo'] == 0) ? 1: ""; 
		$intEstatus[1] += ($rows['activo'] == 1) ? 1: "";
	}

	if($intEstatus[1] > 0){//activo
		$mjs = sprintf("El equipo no puede ser eliminado, contien (%s) integrante activos",$intEstatus[1]);
	}else if ($intEstatus[0] > 0){//inactivo
		$mjs = sprintf("El equipo, contien (%s) integrante inactivos se modificara el estatus del equipo",$intEstatus[0]);
		$deleteSQL = sprintf("UPDATE crm_equipo SET activo = 0 WHERE id_equipo = %s;", 
		valTpDato($idEquipo, "int"));		
	}else{ // sin integrante
		$mjs = sprintf("Se elimino con Éxito");
		$deleteSQL = sprintf("DELETE FROM crm_equipo WHERE id_equipo = %s;", 
		valTpDato($idEquipo, "int"));
	}

	if (($intEstatus[1] == 0 && $intEstatus[0] > 0) || ($intEstatus[1] == "" && $intEstatus[0] == "")) {
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n".$deleteSQL);
	} 
				
	$objResponse->alert($mjs);
	$objResponse->script("byId('btnBuscar').click();");
	
	return $objResponse;
}	

//ELIMINA LOS INTEGRANTES DEL GRUPO SELECCIONADO
function eliminarIntegrante($fomrEquipo, $EliminaTr = NULL){
	$objResponse = new xajaxResponse();

	switch($EliminaTr){
		case 1: 
			if (!xvalidaAcceso($objResponse,"crm_integrantes_equipo_list","eliminar")) { return $objResponse; }
			
			if(isset($fomrEquipo['checkItemIntegrante'])){
				foreach($fomrEquipo['checkItemIntegrante'] as $indice => $valor){
					//COSULTO LA EXISTENCIA DE UNA RECEPCIONISTA O UNA ASESOR DE SERVICIO EN EL EQUIPO 
					$sql = sprintf("SELECT id_integrante_equipo, id_equipo, crm_integrantes_equipos.id_empleado,
							pg_empleado.id_cargo_departamento, clave_filtro, pg_cargo_departamento.id_cargo,nombre_cargo
						FROM crm_integrantes_equipos
							LEFT JOIN pg_empleado ON pg_empleado.id_empleado = crm_integrantes_equipos.id_empleado
							LEFT JOIN pg_cargo_departamento ON pg_cargo_departamento.id_cargo_departamento = pg_empleado.id_cargo_departamento
							LEFT JOIN pg_cargo ON pg_cargo.id_cargo = pg_cargo_departamento.id_cargo
						WHERE 
							(clave_filtro = 25 OR clave_filtro = 5) AND 
							id_equipo = %s AND id_integrante_equipo = %s",
					valTpDato($fomrEquipo['hddidEquipo'], "int"),
					valTpDato($fomrEquipo['hddIintegrante'.$valor], "int"));
					$querySql = mysql_query($sql);
					if (!$querySql) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$sql);
					$rsSql = mysql_fetch_array($querySql);
					$rowsSql = mysql_num_rows($querySql);

					// DEL EQUIPO 1 NO DEBEN SER ELIOMINADO NI LA RECEPCIONISTA NI EL ASISTENTE DE POST VENTAS (USADOS PARA AGENDAR ATIVIDADES AUTOMATIVAS)
					if($fomrEquipo['hddidEquipo'] == 1){
						if($rowsSql != 0){
							switch($rsSql['clave_filtro']){
								case 25: return $objResponse->alert('No se puede eliminar la '.$rsSql['nombre_cargo']); break;
								case 5: return $objResponse->alert('No se puede eliminar el '.$rsSql['nombre_cargo']); break;
							}
						} 		
					}
					
					// CONSULTA LAS ACTIVIDADES ASIGNAS PARA EL INTEGRANTE
					$querySel = sprintf("SELECT * FROM crm_actividades_ejecucion 
						WHERE id_integrante_equipo = %s", 
					valTpDato($fomrEquipo['hddIintegrante'.$valor],"int"));
					$rs = mysql_query($querySel);
					if (!$querySql) return  $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$querySel);
					$rows = mysql_fetch_assoc($rs);
					$nums  = mysql_num_rows($rs);
					
					
					//VALIDO SI EL INTEGRANTE SELECCIONADO TIENE ACTIVIDFAD REGISTRADO
					if($nums > 0) { 
						$deleteSQL = sprintf("UPDATE crm_integrantes_equipos 
								SET activo = %s 
							WHERE 
								id_integrante_equipo = %s", 
						valTpDato(0,"int"),
						valTpDato($fomrEquipo['hddIintegrante'.$valor],"int"));
						$Result1 = mysql_query($deleteSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$querySel);
						$mensaje2 = "Existe Integrantes que Posee Actividad Registrada en la Agenda Se Actulizara Su Estatus A Inactivo";
						$objResponse->assign("tdImg".$valor,"innerHTML",sprintf("<img id=\"icorojo%s\" src=\"../img/iconos/ico_rojo.gif\">",$valor));
					} else {
						$objResponse->script(sprintf("
							fila = document.getElementById('trItmIntegrante%s');
							padre = fila.parentNode;
							padre.removeChild(fila);",
						$valor));
						$mensaje = "Registro Elimionado Con Exito";
					}
				}
				$objResponse->alert($mensaje."\n".$mensaje2);
				$objResponse->script("
					if(byId('checkItemIntegrante').checked == true){
						byId('checkItemIntegrante').click();
				}");
			} else{
				$objResponse->alert("Debe Seleccionar un Integrante si Desea Sacarlos del Equipo de Trabajo");
			}
			
			break;
		default: 
			if(isset($fomrEquipo['checkHddntemIntegrante'])){
				foreach($fomrEquipo['checkHddntemIntegrante'] as $indice => $valor){
					$objResponse->script(sprintf("
							fila = document.getElementById('trItmIntegrante%s');
							padre = fila.parentNode;
							padre.removeChild(fila);",
					$valor));
				}	
			}
			break;
	}

	return $objResponse;
}

//GUARDA Y EDITA LOS DATOS DE EQUIPO
function guadarFormEquipo($datosForm){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$queryIdEmpleado = sprintf("SELECT id_empleado FROM pg_usuario WHERE id_usuario = %s LIMIT 1",
						valTpDato($_SESSION["idUsuarioSysGts"],"int"));
	$rsIdEmpleado = mysql_query($queryIdEmpleado);
	if (!$rsIdEmpleado){
		$objResponse->script("RecorrerForm('fomrEquipo','disabled',false);");	
		return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryIdEmpleado);
	} 
	$rows = mysql_fetch_assoc($rsIdEmpleado);
	$idEmpleado = $rows["id_empleado"];

	if ($datosForm['hddidEquipo'] > 0) {
		if(!xvalidaAcceso($objResponse,"crm_integrantes_equipo_list","editar")){ 
			$objResponse->script("RecorrerForm('fomrEquipo','disabled',false);");	
			return $objResponse; 
		}
		$updateEquipo = sprintf("UPDATE crm_equipo SET
										nombre_equipo = %s,
										descripcion_equipo = %s,
										id_empleado = %s,
										id_empresa = %s,
										activo = %s,
										jefe_equipo = %s,
										tipo_equipo = %s,
										fecha_edicion = now()
									WHERE id_equipo = %s;",
										valTpDato($datosForm['txtNombreEquipo'], "text"),
										valTpDato($datosForm['areaEquipoDescripcion'], "text"),
										valTpDato($idEmpleado, "int"),	//id del usuario que lo crea
										valTpDato($datosForm['txtIdEmpresa'], "int"),
										valTpDato($datosForm['listEstatus'], "boolean"), 
										valTpDato($datosForm['idHiddJefeEquipo'], "int"),
										valTpdato($datosForm['comboxTipoEquipo'], "text"),
										valTpDato($datosForm['hddidEquipo'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$queryEquipo = mysql_query($updateEquipo);
		if (!$queryEquipo){
			$objResponse->script("RecorrerForm('fomrEquipo','disabled',false);");	
			return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$updateEquipo);
		} 
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"crm_integrantes_equipo_list","insertar")) {
			$objResponse->script("RecorrerForm('fomrEquipo','disabled',false);");	
			return $objResponse; 
		}
		$insetEquipo = sprintf("INSERT INTO crm_equipo(nombre_equipo, descripcion_equipo, id_empleado, id_empresa, activo, jefe_equipo, tipo_equipo)
		VALUES (%s,%s,%s,%s,%s,%s,%s)",
			valTpDato($datosForm['txtNombreEquipo'], "text"),
			valTpDato($datosForm['areaEquipoDescripcion'], "text"),
			valTpDato($idEmpleado, "int"),	//id del usuario que lo crea
			valTpDato($datosForm['txtIdEmpresa'], "int"),
			valTpDato($datosForm['listEstatus'], "boolean"), 
			valTpDato($datosForm['idHiddJefeEquipo'], "int"),
			valTpdato($datosForm['comboxTipoEquipo'], "text"));
		mysql_query("SET NAMES 'utf8'");
		$queryEquipo= mysql_query($insetEquipo);
		if (!$queryEquipo){
			$objResponse->script("RecorrerForm('fomrEquipo','disabled',false);");
			return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$insetEquipo);
		} 
		$idEquipo = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	// INSERTAS LOS INTEGRANTE SI EXISTE
	if(isset($datosForm['checkHddntemIntegrante'])){
		//CONSULTA LOS INTEGRANTE DEL EQUIPO 
		$queryInte = sprintf("SELECT * FROM crm_integrantes_equipos WHERE id_equipo = %s",
			($datosForm['hddidEquipo'] > 0) ? valTpDato($datosForm['hddidEquipo'], "int") : valTpDato($idEquipo, "int"));
		$rsInte = mysql_query($queryInte);
		if (!$rsInte) {
			$objResponse->script("RecorrerForm('fomrEquipo','disabled',false);");	
			$objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query);
		}
		$numInte = mysql_num_rows($rsInte);
		while($rows = mysql_fetch_array($rsInte)){
			$existInteg = false;
			if(isset($datosForm['checkHddntemIntegrante'])){
				foreach($datosForm['checkHddntemIntegrante'] as $indice => $valor){
					if($datosForm['hddIintegrante'.$valor] != "" && $rows['id_integrante_equipo'] == $datosForm['hddIintegrante'.$valor]){
						$existInteg = true;
					}
				}	
			}
			//NO EXISTE EN EL FORMULARIO SE ELIMINA DE BD
			if($existInteg == false){
				$query = sprintf("DELETE FROM crm_integrantes_equipos 
					WHERE id_integrante_equipo = %s",
				valTpDato($rows['id_integrante_equipo'],"int"));
				$rs = mysql_query($query);
				if (!$rs){
					$objResponse->script("RecorrerForm('fomrEquipo','disabled',false);");
					return  $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query);
				} 
			}
		}
		
		//INSERTA EL INTEGRANTE 
		if(isset($datosForm['checkHddntemIntegrante'])){
			foreach($datosForm['checkHddntemIntegrante'] as $indice => $valor){
				if($datosForm['hddIintegrante'.$valor] == ""){
					$inserInte = sprintf("INSERT INTO crm_integrantes_equipos (id_equipo,id_empleado,activo) VALUE (%s,%s,%s)",
						($datosForm['hddidEquipo'] > 0) ? valTpDato($datosForm['hddidEquipo'], "int") : valTpDato($idEquipo, "int"),
						valTpDato($datosForm['hddIdEmpleado'.$valor],"int"),
						valTpDato(1,"int"));
					$rsInte = mysql_query($inserInte);
					if (!$rsInte){
						$objResponse->script("RecorrerForm('fomrEquipo','disabled',false);");	
						return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$inserInte);
					} 
				}
			}	
		}
	}else{
		//CONSULTA LOS INTEGRANTE DEL EQUIPO 
		$queryInte = sprintf("SELECT * FROM crm_integrantes_equipos WHERE id_equipo = %s AND activo = %s",
			($datosForm['hddidEquipo'] > 0) ? valTpDato($datosForm['hddidEquipo'], "int") : valTpDato($idEquipo, "int"),
			valTpDato(1, "int"));
		$rsInte = mysql_query($queryInte);
		if (!$rsInte) {
			$objResponse->script("RecorrerForm('fomrEquipo','disabled',false);");	
			$objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query);
		}
		while($rows = mysql_fetch_array($rsInte)){
			$query = sprintf("DELETE FROM crm_integrantes_equipos WHERE id_integrante_equipo = %s",
				valTpDato($rows['id_integrante_equipo'],"int"));
			$rs = mysql_query($query);
			if (!$rs){
				$objResponse->script("RecorrerForm('fomrEquipo','disabled',false);");
				return  $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query);
			} 
		}

	}

	mysql_query("COMMIT;");
	$objResponse->script("RecorrerForm('fomrEquipo','disabled',false);");
	$objResponse->alert("Registro Guardado Con Exito!!!");
	$objResponse->script("byId('butCerrarEquipo').click();");	
	$objResponse->script("byId('btnBuscar').click();");	
	//$objResponse->loadCommands(listaEquipo(0,'',''));
	
	return $objResponse;
}

function insertarIntegrante($idEmpleado,$fomrEquipo){
	$objResponse = new xajaxResponse();
	
	foreach($fomrEquipo['checkHddntemIntegrante'] as $indice => $valor){
		if($fomrEquipo['hddIdEmpleado'.$valor] == $idEmpleado){
			$objResponse->script("RecorrerForm('frmLstEmpleado','disabled',false);");
			return $objResponse->alert("Este Empleado ya Forma Parte del Equipo");	
		}
	}
	
	$contFila = count($fomrEquipo['checkHddntemIntegrante']);
	$Result1 = itemIntegrante($contFila,1,$idEmpleado,"");
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$contFila = $Result1[2];
		$objResponse->script($Result1[1]);
	}
	
	$objResponse->script("RecorrerForm('frmLstEmpleado','disabled',false);");
	
	return $objResponse;
}

//MUESTRA LA CONSULTA DE LOS EQUIPOS REGISTRADOS
function listaEquipo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL){ 
	
	$objResponse = new xajaxResponse();
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("crm_equipo.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	
	$result = buscarTipoUsuario();
	if($result[0] == true){
		if($result[1] != NULL){
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("tipo_equipo = %s",
			valTpDato($result[1], "text"));

		}else{
			if($valCadBusq[1] != "-1" && $valCadBusq[1] != ""){	
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
					$sqlBusq .= $cond.sprintf("tipo_equipo = %s",
				valTpDato($valCadBusq[1], "text"));
			}
		}
	}else{
		return $objResponse->alert($result[1]);	
	}
	
	if($valCadBusq[2] != "-1" && $valCadBusq[2] != ""){	
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("crm_equipo.activo = %s",
		valTpDato($valCadBusq[2], "int"));
	}
	

	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre_equipo LIKE %s
			OR descripcion_equipo LIKE %s
			OR CONCAT_WS(' ',nombre_empleado, apellido) LIKE %s )", 
				valTpDato("%".$valCadBusq[3]."%", "text"), 
				valTpDato("%".$valCadBusq[3]."%", "text"), 
				valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT crm_equipo.*,
		empleado.id_empleado,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_jefe_equipo,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM crm_equipo
		LEFT JOIN pg_empleado empleado ON (empleado.id_empleado = crm_equipo.jefe_equipo)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (crm_equipo.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
		
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
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaEquipo", "8%", $pageNum, "id_equipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaEquipo", "16%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaEquipo", "18%", $pageNum, "nombre_equipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre de Equipo");
		$htmlTh .= ordenarCampo("xajax_listaEquipo", "22%", $pageNum, "descripcion_equipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripcion de Equipo");
		$htmlTh .= ordenarCampo("xajax_listaEquipo", "10%", $pageNum, "tipo_equipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Equipo");
		$htmlTh .= ordenarCampo("xajax_listaEquipo", "22%", $pageNum, "nombre_jefe_equipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Jefe de Equipo");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) { //AQUI ESPESIFICO EL ESTILO Y EL COLOR AL MOVER EL MOUSE SOBRE LOS REGISTRO
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		switch ($row['activo']) {
			case 0 : $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1 : $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatus = ""; break; 
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_equipo']."</td>";	
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_equipo'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_equipo'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['tipo_equipo']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_jefe_equipo'])."</td>";
			$htmlTb .= "<td>";
			$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" name=\"aEditar\" rel=\"#divFlotante\" onclick=\"abrirNuevo(this,this.name,'fomrEquipo',%s) ;\"><img class=\"puntero\" src=\"../img/iconos/ico_edit.png\" title=\"Editar equipo\"/></a>", //EDITAR EQUIPO
					$contFila, $row['id_equipo']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"../img/iconos/ico_delete.png\" title=\"Eliminar equipo\"/></td>",
				$row['id_equipo']);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"50\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEquipo(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEquipo(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEquipo(%s, '%s', '%s', '%s', %s)\">", 
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEquipo(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEquipo(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListEquipo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	return $objResponse;

}

//PARA LISTAR Y SELECCIONAR LOS JEFES DE EQUIPO "SON LOS EMPLEADO QUE PUEDE SER POSIBLES JEFE DE EQUIPO"
function listaEmpleado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("activo = 1");

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s", 
			valTpDato($valCadBusq[0], "int"));
	}
	
	
	$result = buscarTipoUsuario();
	if ($result[0] != true) return $objResponse->alert($result[1]);
	$tipoEmpleado = ($result[1] != NULL) ? $result[1] : $valCadBusq[1];
	
	switch ($tipoEmpleado) {
		case "Ventas":
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("clave_filtro IN (1, 2, 20) AND
			activo = 1");
		break;
			
		case "Postventa":
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("clave_filtro IN (4, 5, 6, 7, 8, 25, 26, 400) AND
			activo = 1");
		break;
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_pg_empleado.cedula LIKE %s
										OR vw_pg_empleado.nombre_empleado LIKE %s
										OR vw_pg_empleado.nombre_cargo LIKE %s)", 
								valTpDato("%".$valCadBusq[2]."%", "text"), 
								valTpDato("%".$valCadBusq[2]."%", "text"), 
								valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_pg_empleado.id_empleado NOT IN (SELECT crm_int.id_empleado FROM crm_integrantes_equipos crm_int)");
	
	$query = sprintf("SELECT
							vw_pg_empleado.id_empleado, 
							vw_pg_empleado.cedula, 
							vw_pg_empleado.nombre_empleado, 
							vw_pg_empleado.nombre_cargo
						FROM vw_pg_empleados vw_pg_empleado %s", 
				$sqlBusq);
						
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
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "10%", $pageNum, "id_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Id"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "18%", $pageNum, "cedula", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("C.I. / R.I.F."));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Nombre Empleado"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "vw_pg_empleado.nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Nombre Cargo"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($valCadBusq[3]){
			case "asignarJefeEquipo":
				$click = sprintf("byId('butCancelarJefeEquipo').click(); xajax_asignarJefeEquipo(%s);",$row['id_empleado']);
			break;
			default: 
				$click = sprintf("xajax_insertarIntegrante(%s,xajax.getFormValues('fomrEquipo')); RecorrerForm('frmLstEmpleado','disabled',true);",$row['id_empleado']);
			break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td>"."<button id=\"btnEmpleado%s\" class=\"close\" type=\"button\" onclick=\"%s \" title=\"Seleccionar Empleado\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>",
				$row['id_empleado'],$click); //EL BOTON PARA SELECCIONAR
			$htmlTb .= "<td align=\"center\">".$row['id_empleado']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['cedula']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cargo'])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"5\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s)\">", 
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
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
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdLisJefeEquipo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

/*REGISTRO LAS FUNCIONES*/
$xajax->register(XAJAX_FUNCTION,"agregarIntegrante"); 
$xajax->register(XAJAX_FUNCTION,"asignarJefeEquipo");
$xajax->register(XAJAX_FUNCTION,"buscarEquipo");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscaEmpleado");
$xajax->register(XAJAX_FUNCTION,"buscarEmpleado");
$xajax->register(XAJAX_FUNCTION,"cargarEquipo"); 
$xajax->register(XAJAX_FUNCTION,"comboxTipoEquipo");
$xajax->register(XAJAX_FUNCTION,"eliminarEquipo");
$xajax->register(XAJAX_FUNCTION,"eliminarIntegrante");
$xajax->register(XAJAX_FUNCTION,"guadarFormEquipo"); 
$xajax->register(XAJAX_FUNCTION,"insertarIntegrante"); 
$xajax->register(XAJAX_FUNCTION,"listaEquipo"); 
$xajax->register(XAJAX_FUNCTION,"listaEmpleado");  
$xajax->register(XAJAX_FUNCTION,"listaEmpleado"); 

function buscarTipoUsuario(){
//AVERIGUAR VENTA O POSTVENTA
	$queryUsuario = sprintf("SELECT id_usuario, nombre_usuario,
        CONCAT_WS(' ', nombre_empleado, apellido) AS nombre,
        clave_filtro,
		(CASE clave_filtro
			  WHEN 1 THEN 'Ventas'		
              WHEN 2 THEN 'Ventas'
			  WHEN 4 THEN 'Postventa'
              WHEN 5 THEN 'Postventa'
              WHEN 6 THEN 'Postventa'
              WHEN 7 THEN 'Postventa'
              WHEN 8 THEN 'Postventa'
              WHEN 26 THEN 'Postventa'
              WHEN 400 THEN 'Postventa'
		END) AS tipo
        
	FROM pg_usuario
		INNER JOIN pg_empleado ON pg_usuario.id_empleado = pg_empleado.id_empleado
		INNER JOIN pg_cargo_departamento ON pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento
	WHERE id_usuario = %s ",
	valTpDato($_SESSION['idUsuarioSysGts'],"int"));
	
	$rsUsuario = mysql_query($queryUsuario);
	if (!$rsUsuario) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryUsuario);
	$row = mysql_fetch_array($rsUsuario);

	return array(true, $row['tipo']);
}

function itemIntegrante($contFila, $activo, $idEmpleado = "", $idIntegrante = ""){
	
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;

	$query = sprintf("SELECT vw_pg_empleado.id_empleado, vw_pg_empleado.cedula, vw_pg_empleado.nombre_empleado, vw_pg_empleado.nombre_cargo
			FROM vw_pg_empleados vw_pg_empleado 
		WHERE vw_pg_empleado.id_empleado = %s",
	valTpDato($idEmpleado,"int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query);
	$rows = mysql_fetch_array($rs);
	
	$img = ($activo == 1) ? sprintf("<img id=\"icoVerde%s\" src=\"../img/iconos/ico_verde.gif\">",$contFila): sprintf("<img id=\"icorojo%s\" src=\"../img/iconos/ico_rojo.gif\">",$contFila);
	
	$htmlItmPie = sprintf("$('#trItmIntegrante').before('".
		"<tr id=\"trItmIntegrante%s\" class=\"%s textoGris_11px\">".
			"<td>".
				"<input id=\"checkItemIntegrante\" name=\"checkItemIntegrante[]\" type=\"checkbox\" value=\"%s\">".
				"<input type=\"checkbox\" id=\"checkHddntemIntegrante\" name=\"checkHddntemIntegrante[]\" checked=\"checked\" style=\"display:none\" value =\"%s\"/>".
				"<input id=\"hddIintegrante%s\" type=\"hidden\" value=\"%s\" name=\"hddIintegrante%s\">".
				"<input id=\"hddIdEmpleado%s\" type=\"hidden\" value=\"%s\" name=\"hddIdEmpleado%s\">".
			"</td>".
			"<td class=\"textoNegrita_9px\">%s</td>".
			"<td>%s</td>".
			"<td align=\"left\">%s</td>".
			"<td align=\"left\">%s</td>".
			"<td id=\"tdImg%s\" align=\"left\">%s</td>".
		"</tr>')",
			$contFila,$clase,
					$contFila,
					$contFila,
					$contFila,$idIntegrante,$contFila,
					$contFila,$rows['id_empleado'],$contFila,
				$contFila,
				$rows['cedula'],
				utf8_encode($rows['nombre_empleado']),
				$rows['nombre_cargo'],
				$contFila,$img);

	return array(true, $htmlItmPie, $contFila,$query);
}


?>