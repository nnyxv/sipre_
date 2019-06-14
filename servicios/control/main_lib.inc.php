<?php
	define('inner','innerHTML');
	define('inputvalue','value');
	
	define ('model_CHARMILES',',');
	define ('model_CHARDECIMAL','.');
	
	function includeScripts($ruta="control/"){
		//<script type="text/javascript" language="javascript" src="lib/mootools.v1.11.js"></script>
		//<script type="text/javascript" language="javascript" src="lib/validaciones.js"></script>
		 echo '	
				<script type="text/javascript" language="javascript" src="'.$ruta.'lib/dom-drag.js"></script>
				<script type="text/javascript" language="javascript" src="'.$ruta.'lib/jquery-1.3.2.min.js"></script>
				<script type="text/javascript" language="javascript" src="'.$ruta.'lib/main.inc.js"></script>

				<script type="text/javascript" language="javascript" src="'.$ruta.'lib/calendar.js"></script>
				<script type="text/javascript" language="javascript" src="'.$ruta.'lib/calendar-es.js"></script>
				<script type="text/javascript" language="javascript" src="'.$ruta.'lib/calendar-setup.js"></script>
				<link rel="stylesheet" type="text/css" media="all" href="'.$ruta.'lib/calendar-green.css" /> ';
	}
	
	function includeModalBox($ruta="control/"){
		echo '<script type="text/javascript" language="javascript" src="'.$ruta.'lib/jquery.simplemodal-1.2.3.js"></script>';
	}
	
	function includeMouseTouch($ruta="control/"){
		echo '<script type="text/javascript" language="javascript" src="'.$ruta.'lib/mouse_touch.inc.js"></script>';
	}
	
	function getClassModalButtonClose(){
		return 'simplemodal-close';
	}
	
	function getUrl($file){
		return '../'.$file;
	}
	
	function includeDoctype(){
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
	}
	function includeMeta(){
		echo '
	<link rel="shortcut icon" href="../favicon.ico" /><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
	}
	
	//formatos num�ricos
	function _formato($num,$dec=2,$ar1="",$ar2=""){//ar1 y ar2 se ignoran
		if($num=='NULL' || $num==''){
			return '';
		}
		$r=number_format($num, $dec,model_CHARDECIMAL,model_CHARMILES);
		if ($r=="0.00"){
			return "0";
		}else{
			return $r;
		}
	}
	//devuelve el maketime de la fecha de inicio m�s el intervalod e dias especificaco, ignorando los dias no laborables:
	/*
	* fi: fecha de  partida
	* intervalo_dios: cantidad de dias a sumar
	* sabado_no_lab: incluye al s�bado como dia no laborable
	*/
	function dateAddLab($fi,$intervalo_dias,$sabado_no_lab=false){
		$total_dias_nolab=1;
		if($sabado_no_lab){
			$total_dias_nolab=2;
		}
		$di=adodb_date('j',$fi);
		$mi=adodb_date('n',$fi);
		$yi=adodb_date('Y',$fi);
		$fecha_inicial=adodb_mktime(0,0,0,$mi,$di,$yi);		
		$fecha_final = $fecha_inicial+((60*60*24)*$intervalo_dias);		
		$fechai=$fecha_inicial;
		$nolab=0;
		for ($i=1;$i<=$intervalo_dias;$i++){
			$fechai=$fechai+((60*60*24));//1dia
			$dow=adodb_date('w',$fechai);
			if(($dow==6 && $total_dias_nolab==2) || ($dow==0)){//domingo
				if($dow==6){
					$fechai=$fechai+((60*60*24)*2);//2dia
				}else{
					$fechai=$fechai+((60*60*24));//1dia				
				}
			}
		}
		$fecha_final=$fechai;		
		if(adodb_date('w',$fecha_final)==6 && $total_dias_nolab==2){//domingo
			$fecha_final = $fecha_final+((60*60*24));
		}
		if(adodb_date('w',$fecha_final)==0){//domingo
			$fecha_final = $fecha_final+((60*60*24));
		}
		return $fecha_final;
	}

?>