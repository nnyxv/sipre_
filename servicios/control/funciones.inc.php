<?php
	function fecha_baja(connection $c,$tdia,$tmes,$tano,$queryreturned=false){
		$pg_fecha_baja = new table("pg_fecha_baja",'',$c);
		$dia=new field("fecha_baja",'dia');
		$mes=new field("fecha_baja",'mes');
		$ano=new field("fecha_baja",'ano');
		$fmes=_function::dateMonthFormat($mes);
		$fano=_function::dateYearFormat($ano);
		$fdia=_function::dateDayFormat($dia);
		$dia->setFunction($fdia);
		$mes->setFunction($fmes);
		$ano->setFunction($fano);
		//$pg_fecha_baja->add(new field("parcial"));
		$pg_fecha_baja->forceAdd($dia);
		$pg_fecha_baja->forceAdd($mes);
		$pg_fecha_baja->forceAdd($ano);
		$q= new query($c);
		$q->addCriteria(new criteria(sqlEQUAL,$fdia,intval($tdia)));
		$q->addCriteria(new criteria(sqlEQUAL,$fmes,intval($tmes)));
		$q->addCriteria(new criteria(sqlOR,new criteria("=",$fano,intval($tano)),new criteria("=",$fano,'0')));
		$q->add($pg_fecha_baja);
		//return $q->getSelect();
		if($queryreturned){
			return $q;
		}
		$recordset=$q->doSelect();
		if($recordset!=null){
			return $recordset;
		}
	}
	
	function phpTime($sqltime){
		return adodb_mktime(adodb_date('H',str_time($sqltime)),adodb_date('i',str_time($sqltime)));
	}
	function phpDate($sqltime){
		if($sqltime==null){
			return null;
		}
		return str_date($sqltime);
	}
	function phpTime2($sqltime){
		if($sqltime==null){
			return null;
		}
		return str_time($sqltime);
	}
	
	//mayor presición en procesamiento de fechas
	function str_date($date){//evaluar AAAA-MM-DD
		$ano=substr($date,0,4);
		$mes=substr($date,5,2);
		$dia=substr($date,-2,2);
		//echo $ano.' '.$mes.' '.$dia;
		return adodb_mktime(0,0,0,$mes,$dia,$ano);
	}
	//mayor presición en procesamiento de horas
	function str_time($time){//evaluar horas HH-MM-SS 24
		$h=substr($time,0,2);
		$m=substr($time,3,2);
		$s=substr($time,-2,2);
		//echo $ano.' '.$mes.' '.$dia;
		return adodb_mktime($h,$m,$s);
	}
	//mayor presición en procesamiento de fechas definidad
	function defined_str_date($date){//evaluar DD-MM-AAAA
		$fecha=explode('-',$date);
		$dia=$fecha[0];
		$mes=$fecha[1];
		$ano=$fecha[2];
		//echo $ano.' '.$mes.' '.$dia;
		return adodb_mktime(0,0,0,$mes,$dia,$ano);
	}
	
	function str_datetime($date,$time){
		$ano=substr($date,0,4);
		$mes=substr($date,5,2);
		$dia=substr($date,-2,2);
		$h=substr($time,0,2);
		$m=substr($time,3,2);
		$s=substr($time,-2,2);
		return adodb_mktime($h,$m,$s,$mes,$dia,$ano);
	}
	function str_tiempo($datetime){
		$arr=explode(" ",$datetime);
		$adate=explode("-",$arr[0]);
		$atime=explode(":",$arr[1]);
		$ano=$adate[0];
		$mes=$adate[1];
		$dia=$adate[2];
		$h=$atime[0];
		$m=$atime[1];
		$s=$atime[2];
		return adodb_mktime($h,$m,$s,$mes,$dia,$ano);
	}
	
	function parseDate($maketime,$devnull=''){
		if($maketime==null){
			return $devnull;
		}else{
			return adodb_date(DEFINEDphp_DATE,$maketime);
		}
	}
	function parseTime($maketime,$devnull=''){
		if($maketime==null){
			return $devnull;
		}else{
			return adodb_date(DEFINEDphp_TIME,$maketime);
		}
	}
	function parseDateTime($maketime,$devnull=''){
		if($maketime==null){
			return $devnull;
		}else{
			return adodb_date(DEFINEDphp_DATETIME,$maketime);
		}
	}
	
	function parseTiempo($maketime,$devnull=''){
		if($maketime==null){
			return $devnull;
		}else{
			return adodb_date('d-m-Y h:i A',$maketime);
		}
	}
	function parseDateToSql($date){
		return parseDate(phpDate($date));
	}
	function parseTimeToSql($date){
		return parseTime(phpTime2($date));
	}
	function parseNullDateToSql($date){
		if($date=='0000-00-00'){
			return '00-00-0000';
		}
		return parseDate(phpDate($date));
	}
	function ifnull($value,$devnull=''){
		if($value==null){
			return $devnull;
		}else{
			return $value;
		}
	}
	
	//devuelve el último intervalo de la empresa
	function getLastInterval($c,$id_empresa){
		$sa_v_intervalo=$c->sa_v_intervalo;
		$qintervalo=new query($c);
		$qintervalo->add($sa_v_intervalo);
		//$fechasql=field::getTransformType($ffecha,field::tDate);
		//$r->alert($fechasql);
		//return $r;
		$qintervalo
			->where(new criteria(sqlEQUAL,$sa_v_intervalo->id_empresa,$id_empresa))
			->where(
				new criteria(' IS ',$sa_v_intervalo->fecha_fin,sqlNULL)
			)->setLimit(1);
		return $qintervalo->doSelect();	
	}
	
	function getBaseUt($id_empresa,$c=null){
		if($c==null){
			$c= new connection();
			$c->open();
		}
		return $c->pg_parametros_empresas->doSelect($c,
			new criteria(sqlAND,
				new criteria(sqlEQUAL,$c->pg_parametros_empresas->descripcion_parametro,1),
				new criteria(sqlAND,$c->pg_parametros_empresas->id_empresa,$id_empresa)
			))->valor_parametro;
	}	
	function getParam($id_empresa,$parametro,$c=null){
		if($c==null){
			$c= new connection();
			$c->open();
		}
		return $c->pg_parametros_empresas->doSelect($c,
			new criteria(sqlAND,
				new criteria(sqlEQUAL,$c->pg_parametros_empresas->descripcion_parametro,$parametro),
				new criteria(sqlAND,$c->pg_parametros_empresas->id_empresa,$id_empresa)
			))->valor_parametro;
	}
	
	function ut_min($ut,$baseut){
		return (($ut*60)/$baseut);
	}
	
	function getOnlyTime($mkdate){
		return adodb_mktime(adodb_date('G',$mkdate),intval(adodb_date('i',$mkdate)),0);
	}
	
	function minToHours($min){
		if ($min==null) return null;
		//extrayendo las horas
		return _formato($min/60);
		
	}
	
	function _textprint($text){
		return str_replace("\n","<br />",htmlspecialchars($text));
	}	
	function _fromtextarea($text,$max=60,$corte=1,$cmax=65){
		//lo separa primero
		$cadena= explode("\n",$text);
		foreach($cadena as $k=>$v){
			if(strlen($v)>$cmax){
				$cadena[$k]=wordwrap($v,$max,"\n",$corte);
			}
		}
		return implode("\n",$cadena);
	}
	/*function mergeDateTime($date,$time){
		return adodb_mktime(adodb_date('G',$time),intval(adodb_date('i',$time)),0,intval(adodb_date('m',$date)),intval(adodb_date('d',$date)),intval(adodb_date('Y',$date)));
	}*/

?>