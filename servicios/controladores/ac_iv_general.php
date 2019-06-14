<?php
set_time_limit(0);

include("../controladores/ac_iv_general.php");

/**
* Modificado por Gregor =)
* Esta es la funcion que se encarga de hacer el listado de empresas asignadas al usuario.
*
* @setId Int -Debe ser el id del usuario logueado $_SESSION["idEmpresaUsuarioSysGts"] solo para que aparesca "selected" el actual
* @accion String -Debe ser javascript onchange, onclick, etc por defecto tiene el onchange ese
* @id_etiqueta String -Debe ser el id para el <select> es opcional sino agarra "lstEmpresa"
* @id_objetivo String -Debe ser el id donde se va a asignar el select con assing, por defecto es tdlstEmpresa
* @todos Bool -Cualquiera que aplique a bool, trae un extra <option value= "" >[ Todos ]</option> al inicio del select
* @soloSeleccionada Bool -Cualquiera que cuente como bool, muestra solo el option selected <option selected>nombre empresa</option>
*
* return Objeto xajax
*/

function cargaLstEmpresaFinal($selId = "", $accion = "onchange=\"xajax_objetoCodigoDinamico('tdCodigoArt',this.value); byId('btnBuscar').click();\"" , $id_etiqueta = false, $id_objetivo = false, $todos = false, $soloSeleccionada = false) {
	$objResponse = new xajaxResponse();
	
	// EMPRESAS PRINCIPALES
	$queryUsuarioSuc = sprintf("SELECT DISTINCT
		id_empresa_reg,
		nombre_empresa
	FROM vw_iv_usuario_empresa
	WHERE id_usuario = %s
		AND id_empresa_padre_suc IS NULL
	ORDER BY nombre_empresa_suc ASC",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"));
	$rsUsuarioSuc = mysql_query($queryUsuarioSuc);
	if (!$rsUsuarioSuc) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	while ($rowUsuarioSuc = mysql_fetch_assoc($rsUsuarioSuc)) {
		$selected = ($selId == $rowUsuarioSuc['id_empresa_reg']) ? "selected=\"selected\"" : "";
	
		$htmlOption .= "<option ".$selected." value=\"".$rowUsuarioSuc['id_empresa_reg']."\">".htmlentities($rowUsuarioSuc['nombre_empresa'])."</option>";	
		
		if($selected != ""){
                    $opcionSeleccionada = "<option ".$selected." value=\"".$rowUsuarioSuc['id_empresa_reg']."\">".htmlentities($rowUsuarioSuc['nombre_empresa'])."</option>";				
		}
		
	}
	
	// EMPRESAS CON SUCURSALES
	$query = sprintf("SELECT DISTINCT
		id_empresa,
		nombre_empresa
	FROM vw_iv_usuario_empresa
	WHERE id_usuario = %s
		AND id_empresa_padre_suc IS NOT NULL
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
			AND id_empresa_padre_suc = %s
		ORDER BY nombre_empresa_suc ASC",
			valTpDato($_SESSION['idUsuarioSysGts'], "int"),
			valTpDato($row['id_empresa'], "int"));
		$rsUsuarioSuc = mysql_query($queryUsuarioSuc);
		if (!$rsUsuarioSuc) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		while ($rowUsuarioSuc = mysql_fetch_assoc($rsUsuarioSuc)) {
			$selected = ($selId == $rowUsuarioSuc['id_empresa_reg']) ? "selected=\"selected\"" : "";
		
			$htmlOption .= "<option ".$selected." value=\"".$rowUsuarioSuc['id_empresa_reg']."\">".htmlentities($rowUsuarioSuc['nombre_empresa_suc'])."</option>";	
			
			if($selected != ""){
				$opcionSeleccionada = "<option ".$selected." value=\"".$rowUsuarioSuc['id_empresa_reg']."\">".htmlentities($rowUsuarioSuc['nombre_empresa_suc'])."</option>";	
			}
		
		}
	
		$htmlOption .= "</optgroup>";
	}
	
	if($id_etiqueta){
		$html = "<select id=\"$id_etiqueta\" name=\"$id_etiqueta\" class=\"inputHabilitado\" ".$accion." style=\"width:200px\">";
	}else{
		$html = "<select id=\"lstEmpresa\" name=\"lstEmpresa\" class=\"inputHabilitado\" ".$accion." style=\"width:200px\">";
	}
	if($todos){
		$html .= "<option value=\"\">[ Todos ]</option>";
	}
	
	if($soloSeleccionada){
		$htmlOption = $opcionSeleccionada;	
	}
	
		$html .= $htmlOption;
	$html .= "</select>";
	
	if($id_objetivo){
		$objResponse->assign("$id_objetivo","innerHTML",$html);                
	}else{
		$objResponse->assign("tdlstEmpresa","innerHTML",$html);		
	}
	return $objResponse;
}

/*******************************************************************************************************************/

//AC_IV_GENERAL ANTERIOR FUNCIONES QUE USA 

//LO USA SERVICIOS AL CAMBIAR TIPO DE ORDEN
function verficarPassTipoOrden($pass){
	$objResponse = new xajaxResponse();
	$sql1= "SELECT id_empleado FROM pg_usuario WHERE id_usuario= ".$_SESSION['idUsuarioSysGts'];
	$rsUsuario = mysql_query($sql1) or die(mysql_error());
	$rowUsuario = mysql_fetch_assoc($rsUsuario);

	$sql2= "SELECT * FROM sa_claves WHERE modulo= 'sa_orden_tipo' ";
	$sql2.= "AND id_empleado= ".$rowUsuario['id_empleado']." AND id_empresa= ".$_SESSION['idEmpresaUsuarioSysGts'];
	$rsClaves = mysql_query($sql2) or die(mysql_error());
	$rowClaves = mysql_fetch_assoc($rsClaves);

	if($rowClaves){
		$pass= md5($pass);
		if($pass == $rowClaves['clave']){
			$objResponse->script("$('key_window2').style.display='none';");
			$objResponse->script("$('hddTipoOrdenAnt').value= $('lstTipoOrden').value");
		}else{
			$objResponse->script("$('key2').value= '';");
			$objResponse->script("alert('Clave incorrecta, verifique y vuelva a intentar');");
			//$objResponse->script("$('lstTipoOrden').value= $('hddTipoOrdenAnt').value");
		}
	}else{
		$objResponse->script("$('key2').value= '';");
		$objResponse->script("$('key_window2').style.display='none';");
		$objResponse->script("alert('Usted no posee privilegios para modificar el tipo orden');");
		$objResponse->script("$('lstTipoOrden').value= $('hddTipoOrdenAnt').value");
	}

	return $objResponse;
}


function verficarPass($pass){
	$objResponse = new xajaxResponse();
	$sql1= "SELECT id_empleado FROM pg_usuario WHERE id_usuario= ".$_SESSION['idUsuarioSysGts'];
	$rsUsuario = mysql_query($sql1) or die(mysql_error());
	$rowUsuario = mysql_fetch_assoc($rsUsuario);

	$sql2= "SELECT * FROM sa_claves WHERE modulo= 'sa_ordenes_precio' ";
	$sql2.= "AND id_empleado= ".$rowUsuario['id_empleado']." AND id_empresa= ".$_SESSION['idEmpresaUsuarioSysGts'];
	$rsClaves = mysql_query($sql2) or die(mysql_error());
	$rowClaves = mysql_fetch_assoc($rsClaves);
	
	if($rowClaves){
		$pass= md5($pass);
		if($pass == $rowClaves['clave']){
			$objResponse->script("$('txtPrecioRepuesto').readOnly=false;");
			$objResponse->script("$('txtPrecioRepuesto').focus();");
			$objResponse->script("$('txtPrecioRepuesto').style.background='#FAFAD2'");
			$objResponse->script("$('key_window').style.display='none';");

		}else{
			$objResponse->script("$('key').value= '';");
			$objResponse->script("alert('Clave incorrecta, verifique y vuelva a intentar');");
		}
	}else{
		$objResponse->script("$('key').value= '';");
		$objResponse->script("$('key_window').style.display='none';");
		$objResponse->script("alert('Usted no posee privilegios para modificar el precio');");
	}
	return $objResponse;
}


function verficarPassSinMagnetoplano($pass){
	$objResponse = new xajaxResponse();
	$sql1= "SELECT id_empleado FROM pg_usuario WHERE id_usuario= ".$_SESSION['idUsuarioSysGts'];
	$rsUsuario = mysql_query($sql1) or die(mysql_error());
	$rowUsuario = mysql_fetch_assoc($rsUsuario);

	$sql2= "SELECT * FROM sa_claves WHERE modulo= 'sa_ordenes_sin_magnetoplano' ";
	$sql2.= "AND id_empleado= ".$rowUsuario['id_empleado']." AND id_empresa= ".$_SESSION['idEmpresaUsuarioSysGts'];
	$rsClaves = mysql_query($sql2) or die(mysql_error());
	$rowClaves = mysql_fetch_assoc($rsClaves);

	if($rowClaves){
		$pass= md5($pass);
		if($pass == $rowClaves['clave']){
			$objResponse->script("$('key_window').style.display='none';");
			$objResponse->script("abrirMo();");
		}else{
			$objResponse->script("$('key').value= '';");
			$objResponse->script("alert('Clave incorrecta, verifique y vuelva a intentar');");
		}
	}else{
		$objResponse->script("$('key').value= '';");
		$objResponse->script("$('key_window').style.display='none';");
		$objResponse->script("alert('Usted no posee privilegios');");
	}
	return $objResponse;
}


function verficarPassStatusOrden($pass){
	$objResponse = new xajaxResponse();
	$sql1= "SELECT id_empleado FROM pg_usuario WHERE id_usuario= ".$_SESSION['idUsuarioSysGts'];
	$rsUsuario = mysql_query($sql1) or die(mysql_error());
	$rowUsuario = mysql_fetch_assoc($rsUsuario);

	$sql2= "SELECT * FROM sa_claves WHERE modulo= 'sa_ordenes_status' ";
	$sql2.= "AND id_empleado= ".$rowUsuario['id_empleado']." AND id_empresa= ".$_SESSION['idEmpresaUsuarioSysGts'];
	$rsClaves = mysql_query($sql2) or die(mysql_error());
	$rowClaves = mysql_fetch_assoc($rsClaves);

	if($rowClaves){
		$pass= md5($pass);
		if($pass == $rowClaves['clave']){
			$objResponse->script("$('key_window_status').style.display='none';");
			$objResponse->script("abrirCambioEstado();");
		}else{
			$objResponse->script("$('key_status').value= '';");
			$objResponse->script("alert('Clave incorrecta, verifique y vuelva a intentar');");
		}
	}else{
		$objResponse->script("$('key_status').value= '';");
		$objResponse->script("$('key_window_status').style.display='none';");
		$objResponse->script("alert('Usted no posee privilegios');");
	}
	return $objResponse;
}

function verificarPassSobregiro($pass, $id){
	$objResponse = new xajaxResponse();
	$sql1= "SELECT id_empleado FROM pg_usuario WHERE id_usuario= ".$_SESSION['idUsuarioSysGts'];
	$rsUsuario = mysql_query($sql1) or die(mysql_error());
	$rowUsuario = mysql_fetch_assoc($rsUsuario);

	$sql2= "SELECT * FROM sa_claves WHERE modulo= 'sa_sobregiro_cliente' ";
	$sql2.= "AND id_empleado= ".$rowUsuario['id_empleado']." AND id_empresa= ".$_SESSION['idEmpresaUsuarioSysGts'];
	$rsClaves = mysql_query($sql2) or die(mysql_error());
	$rowClaves = mysql_fetch_assoc($rsClaves);

	if($rowClaves){
		$pass= md5($pass);
		if($pass == $rowClaves['clave']){
			$objResponse->script("$('window_credito').style.display='none';");
			$objResponse->script("$('sobregiro').value= 1;");
			$objResponse->script("$('aprobarOrden".$id."').onclick();");
		}else{
			$objResponse->script("$('clave_credito').value= '';");
			$objResponse->script("alert('Clave incorrecta, verifique y vuelva a intentar');");
		}
	}else{
		$objResponse->script("$('clave_credito').value= '';");
		$objResponse->script("$('window_credito').style.display='none';");
		$objResponse->script("alert('Usted no posee privilegios');");
	}
	return $objResponse;
}
	
	
//estas son las funciones registradas para el anterior
$xajax->register(XAJAX_FUNCTION,"verficarPassTipoOrden");
$xajax->register(XAJAX_FUNCTION,"verficarPass");
$xajax->register(XAJAX_FUNCTION,"verficarPassSinMagnetoplano");
$xajax->register(XAJAX_FUNCTION,"verficarPassStatusOrden");
$xajax->register(XAJAX_FUNCTION,"verificarPassSobregiro");


//funciones comunes

//configuracion tempario unico
function temparioUnico($idEmpresa){
    $query = sprintf("SELECT valor_parametro 
						FROM pg_parametros_empresas 
						WHERE descripcion_parametro = 'TEMPARIO UNICO' 
						AND (id_empresa = %s OR id_empresa = (SELECT padre.id_empresa_padre 
                                                    			FROM pg_empresa padre 
																WHERE padre.id_empresa = %s))",
            $idEmpresa,
            $idEmpresa);
    
    $rs = mysql_query($query);
    if(!$rs) { die(mysql_error()." Linea: ".__LINE__); }
    
    $row = mysql_fetch_assoc($rs);
    
    return $row['valor_parametro'];
    
}

function empresasVinculadas($idEmpresa){
    $arrayEmpresas = array();
    $queryEmpresas = sprintf("SELECT id_empresa 
								FROM pg_empresa 
								WHERE id_empresa = %s 
								OR id_empresa_padre = %s 
								OR id_empresa_padre = (SELECT padre.id_empresa_padre 
														FROM pg_empresa padre 
														WHERE padre.id_empresa = %s)
								OR id_empresa = (SELECT padre2.id_empresa_padre 
													FROM pg_empresa padre2 
													WHERE padre2.id_empresa = %s)",
                          $idEmpresa,
                          $idEmpresa,
                          $idEmpresa,
                          $idEmpresa);
    $rs = mysql_query($queryEmpresas);
    if(!$rs) { return die(mysql_error()."\nLinea: ".__LINE__."\nArchivo: ".__FILE__); }

    while($rowEmp = mysql_fetch_assoc($rs)){
        $arrayEmpresas[] = $rowEmp['id_empresa'];
    }
    
    return $arrayEmpresas;
}

function idEmpleado($idUsuario){
    $query = sprintf("SELECT id_empleado FROM pg_usuario WHERE id_usuario = %s LIMIT 1",
            valTpDato($idUsuario,"int"));
    $rs = mysql_query($query);
    if(!$rs) { return die(mysql_error()."\nLinea: ".__LINE__."\nArchivo: ".__FILE__); }
    
    $row = mysql_fetch_assoc($rs);
    
    return $row["id_empleado"];
}

function nombreEmpleado($idEmpleado){
    $query = sprintf("SELECT CONCAT_WS(' ', nombre_empleado, apellido) as nombre_completo 
             			FROM pg_empleado 
						WHERE id_empleado = %s LIMIT 1",
            valTpDato($idEmpleado,"int"));
    $rs = mysql_query($query);
    if(!$rs) { return die(mysql_error()."\nLinea: ".__LINE__."\nArchivo: ".__FILE__); }
    
    $row = mysql_fetch_assoc($rs);
    
    return $row["nombre_completo"];
}

function tiempoComun($tiempo){
    if($tiempo != "" && $tiempo != " "){
        return date("d-m-Y H:i",strtotime($tiempo));
    }
}

function clienteExento($idCliente){//true si no paga impuesto, false si si
	$queryClienteImpuesto = sprintf("SELECT paga_impuesto FROM cj_cc_cliente WHERE id = %s LIMIT 1",
									valTpDato($idCliente,"int"));
	$rsClienteImpuesto = mysql_query($queryClienteImpuesto);
	if (!$rsClienteImpuesto) { return die(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryClienteImpuesto); }
	
	$rowClienteImpuesto = mysql_fetch_assoc($rsClienteImpuesto);
	if($rowClienteImpuesto["paga_impuesto"] == "0" ){
		return true;
	}else{
		return false;
	}
}

function tipoOrdenPoseeIva($idTipoOrden){//true si posee y false sino
	$queryPoseeIva = sprintf("SELECT posee_iva FROM sa_tipo_orden WHERE id_tipo_orden = %s LIMIT 1",
									valTpDato($idTipoOrden,"int"));
	$rsPoseeIva = mysql_query($queryPoseeIva);
	if (!$rsPoseeIva) { return die(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryPoseeIva); }
	
	$rowClienteImpuesto = mysql_fetch_assoc($rsPoseeIva);
	if($rowClienteImpuesto["posee_iva"] == "0" ){
		return false;
	}else{
		return true;
	}
}

/**
 * Se encarga de devolver un array de arrays con los ivas disponibles para Venta.
 * Abstraccion utilizada por si se llega a filtrar ivas que aplican solo a servicios
 * @return Array Es un array de arrays con indice como idIva y formato array(array(idIva,iva,observacion))
 */
function ivaServicios(){
    
    $arrayIva = array();
    
    $query = sprintf("SELECT idIva, iva, observacion
                      FROM pg_iva 
                      WHERE tipo = 6 AND activo = 1 AND estado = 1");//activo = predeterminado
    $rs = mysql_query($query);
    if(!$rs) { return die(mysql_error()."\nLinea: ".__LINE__."\nArchivo: ".__FILE__); }
    
    while($row = mysql_fetch_assoc($rs)){
        $arrayIva[$row["idIva"]] = array("idIva" => $row["idIva"],
                                         "iva" => $row["iva"],
                                         "observacion" => $row["observacion"]);
    }
    
    return $arrayIva;
}

/**
 * Busca solo los ivas cargados en la orden ya guardada, por si a futuro cambian los ivas, traer el de la orden
 * tambien se utiliza para todos los historicos o los que no sean nuevos. Vistas de orden.
 * @param int $idOrden Id de la orden a cargar
 * @return Array Es un array de arrays con indice como idIva y formato array(array(idIva,iva,observacion))
 */
function cargarIvasOrden($idOrden){
    $arrayIva = array();
    
    $query = sprintf("SELECT pg_iva.idIva, sa_orden_iva.iva, pg_iva.observacion
                      FROM sa_orden_iva 
                      INNER JOIN pg_iva ON sa_orden_iva.id_iva = pg_iva.idIva
                      WHERE id_orden = %s 
                      /*javier y alexander*/
                      ORDER BY `sa_orden_iva`.`id_orden_iva` DESC limit 1",
                valTpDato($idOrden, "int"));
    $rs = mysql_query($query);
    if(!$rs) { return die(mysql_error()."\nLinea: ".__LINE__."\nArchivo: ".__FILE__); }
    
    while($row = mysql_fetch_assoc($rs)){
        $arrayIva[$row["idIva"]] = array("idIva" => $row["idIva"],
                                         "iva" => $row["iva"],
                                         "observacion" => $row["observacion"]);
    }
    
    return $arrayIva;
}

/**
* Busca el estado de la orden, para verificar si esta en proceso o se finalizo.
* En proceso se activa iva nuevo, de lo contrario es historico y debe traer el de la orden
* @param int $idOrden Id de la orden a evaluar
* @return bool True en proceso o false finalizada
*/
function ordenEnProceso($idOrden){
	
	$query = sprintf("SELECT *
                      FROM sa_orden
                      WHERE id_orden = %s AND id_estado_orden IN (13,18,21,24) LIMIT 1",//13 terminado, 18 facturado, 21 finalizado, 24 vale de salida
                valTpDato($idOrden, "int"));
    $rs = mysql_query($query);
    if(!$rs) { return die(mysql_error()."\nLinea: ".__LINE__."\nArchivo: ".__FILE__); }
	
	if(mysql_num_rows($rs) > 0){
		return false;
	}else{
		return true;
	}
}

/**
* Busca si existe la vista de lotes, si existe usa lotes, sino no.
* @return bool True usa lotes o False no usa
*/
function usaLotes(){
	
	$query = "SHOW TABLES LIKE 'vw_iv_articulos_almacen_costo'";
	$rs = mysql_query($query);
	if(!$rs) { return die(mysql_error()."\nLinea: ".__LINE__."\nArchivo: ".__FILE__); }
	
	$compruebaLote = mysql_num_rows($rs);
	
	if($compruebaLote > 0){//USA LOTES
		return true;
	}else{
		return false;
	}
}

function configPais($idEmpresa){
	// VERIFICA VALORES DE CONFIGURACION (Formato Cheque Tesoreria)
	$queryConfig403 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig403 = mysql_query($queryConfig403);
	if (!$rsConfig403) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowConfig403 = mysql_fetch_assoc($rsConfig403);
	
	if($rowConfig403['valor'] == NULL){	
		die("No se ha configurado formato de cheque. 403");
	}
	
	return $rowConfig403['valor'];
}

?>