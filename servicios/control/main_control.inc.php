<?php
	require_once("main_lib.inc.php");
	require_once("iforms.inc.php");
	require_once("ipaginator.inc.php");
	require_once("model/main.inc.php");
	require_once("adodb-time.inc.php");
	@session_start();
	//cargando sesiones:
	$ses_empresa=$_SESSION['session_empresa'];
	$ses_sucursal=$_SESSION['idEmpresaUsuarioSysGts'];
        global $cancelXajax;
	if($cancelXajax==NULL){
		require_once("lib/xajax/xajax_core/xajax.inc.php");
		//iniciando xajax;
		$xajax = new xajax();
		$xajax->configure('decodeUTF8Input',true);	
	}
//$xajax->setFlag('debug',true);
	//$xajax->configure('javascript URI', 'control/lib/xajax/');
	
	function xajaxRegister($func){
		global $xajax;
		$xajax->register(XAJAX_FUNCTION,$func);
	}
	function xajaxProcess(){
		global $xajax;
		$xajax->processRequest();
	}
	function getResponse(){
		$r = new xajaxResponse();
		$r->setCharacterEncoding('UTF-8');
		//$r->alert($_SESSION['idUsuarioSysGts']);
		return $r;
	}
	function getXajaxJavascript($spath='../'){
		global $xajax;
		//$xajax->printJavascript($spath.'control/lib/xajax/');//anterior
		$xajax->printJavascript('control/lib/xajax/');//gregor
		
	}
	
	function xajaxValue(xajaxResponse $response,recordset $rec,$fieldname,$label=null,$prop='value'){
		if($label==null){
			$label=$fieldname;
		}
		$response->assign($label,$prop,ifnull($rec->field($fieldname)));
	}
	
	function xajaxInner(xajaxResponse $response,recordset $rec,$fieldname,$label=null){
		xajaxValue($response,$rec,$fieldname,$label,'innerHTML');
	}
	
	function getEmpresaList($c,$solo_sucursales=true){
		if($solo_sucursales){
			$crit=new criteria(sqlEQUAL,'sucursales','0');
		}else{
			$crit==null;
		}
		$qemp=$c->sa_v_empresa_sucursal->doQuery($c,$crit);
		$qemp->where(new criteria(sqlNOTEQUAL,'id_empresa',100));
		return $qemp->doSelect()->getAssoc($c->sa_v_empresa_sucursal->id_empresa,$c->sa_v_empresa_sucursal->nombre_empresa_sucursal);
	}
	
	function setLocaleMode(){
		date_default_timezone_set('America/Caracas');
	}
?>