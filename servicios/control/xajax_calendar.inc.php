<?php
	$meses=array(1 => "Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
	$semanas=array(1 => "Lunes","Martes","Mi&eacute;rcoles","Jueves","Viernes","S&aacute;bado","Domingo");
//función xajax para manejar un calendario dinámico

	function setCalendar($id_tag,$call_javacript="",$dia=null,$mes=null,$ano=null,$php_notdaylab_func="getDayNotLabForMonth",$php_getIntervalData_func='getIntervalData'){
		global $meses;
		$r=getResponse();
		
		//construyendo el calendario:
		
		if($dia==null){
			$dia=adodb_date('d');
		}	
		if($mes==null){
			$mes=adodb_date('m');
		}	
		if($ano==null){
			$ano=adodb_date('Y');
		}
		
		
		$dias_mes=array(1=>31,28,31,30,31,30,31,31,30,31,30,31);
		
		if(intval($ano) % 4 == 0){
			//año bisiesto
			$dias_mes[2]=29;
		}
		
		if(intval($dia)>$dias_mes[intval($mes)]){
			$dia=1;
		}
		
		$time=adodb_mktime(0,0,0,$mes,$dia,$ano);
		
		$semana=adodb_date('N',adodb_mktime(0,0,0,$mes,1,$ano));//1=lunes 2 3 4 5 6 7domingo
		$finsemana=7;//domingo
		$total_mes=adodb_date('t',$time); //total de dias al mes
			
		
		$anolastm=$ano;
		$anonextm=$ano;
		$lastmonth=(intval($mes)-1);
		if($lastmonth==0){
			$lastmonth=12;
			$anolastm--;
		}
		$nextmont=(intval($mes)+1);
		if($nextmont==13){
			$nextmont=1;
			$anonextm++;
		}
		$html='<table border="0" cellpadding="0" cellspacing="0" class="xajax_calendar">';
			$html.='
			<tr>
				<td colspan="7" class="xajax_calendar_title">'.$meses[intval($mes)].', '.$ano.'</td>
			</tr>
			<tr>
				<td class="xajax_calendar_button" onclick="xajax_setCalendar(\''.$id_tag.'\',\''.$call_javacript.'\',\''.$dia.'\',\''.$mes.'\',\''.(intval($ano)-1).'\',\''.$php_notdaylab_func.'\');" title="Retrocede 1 a&ntilde;o" >|&lt;</td>
				<td class="xajax_calendar_button" onclick="xajax_setCalendar(\''.$id_tag.'\',\''.$call_javacript.'\',\''.$dia.'\',\''.$lastmonth.'\',\''.$anolastm.'\',\''.$php_notdaylab_func.'\');" title="Retrocede 1 mes" >&lt;</td>
				<td class="xajax_calendar_button" colspan="3" onclick="xajax_setCalendar(\''.$id_tag.'\',\''.$call_javacript.'\',\''.adodb_date('d').'\',\''.adodb_date('m').'\',\''.adodb_date('Y').'\',\''.$php_notdaylab_func.'\');"; title="Ir al la fecha de Hoy">Hoy</td>
				<td class="xajax_calendar_button" onclick="xajax_setCalendar(\''.$id_tag.'\',\''.$call_javacript.'\',\''.$dia.'\',\''.$nextmont.'\',\''.$anonextm.'\',\''.$php_notdaylab_func.'\');" title="Avanza 1 mes" >&gt;</td>
				<td class="xajax_calendar_button" onclick="xajax_setCalendar(\''.$id_tag.'\',\''.$call_javacript.'\',\''.$dia.'\',\''.$mes.'\',\''.(intval($ano)+1).'\',\''.$php_notdaylab_func.'\');" title="Avanza 1 a&ntilde;o" >&gt;|</td>
			</tr>
			<tr>
				<td class="xajax_calendar_week">L</td>
				<td class="xajax_calendar_week">M</td>
				<td class="xajax_calendar_week">Mi</td>
				<td class="xajax_calendar_week">J</td>
				<td class="xajax_calendar_week">V</td>
				<td class="xajax_calendar_week">S</td>
				<td class="xajax_calendar_week">D</td>
			</tr><tr>';
			//imprimiendo la primera semana:
			$enblanco=$semana-1;
			for($i=0;$i<$enblanco;$i++){
				$html.='<td>&nbsp;</td>';
			}
			//$html.='</tr><tr>';
			
			//ejecutando funcion para extraer dias de baja:
			if(function_exists($php_notdaylab_func)){
				$bajas=call_user_func($php_notdaylab_func,$mes,$ano);
				//$r->alert(count(call_user_func($php_notdaylab_func,$mes)));
			}
			
			//imprimiendo los dias
			for($d=1;$d<=$total_mes;$d++){
				//marca de tiempo:
				$dayt=adodb_mktime(0,0,0,$mes,$d,$ano);
				$tday=adodb_date('N',$dayt);
				$events='';
				
				if(function_exists($php_getIntervalData_func)){
					$interval=call_user_func($php_getIntervalData_func,$d,$mes,$ano);
					$max_sdays=$interval->dias_semana;
				}else{
					$max_sdays=7;
				}
				
				if($tday>$max_sdays){//sabado y domingo (dependen del intervalo verificar) //original:$tday==7
					$class=' class="xajax_calendar_day_nolab" ';
					$labday=true;
				}else{
					$class=' class="xajax_calendar_day"';
					$labday=true;
				}
				if(isset($bajas[$d])){
					if($bajas[$d]==0){
						$class=' class="xajax_calendar_day_nolab" title="D&iacute;a Feriado" ';
					}else{
						$class=' class="xajax_calendar_day_nolab_parcial" title="D&iacute;a parcial" ';						
					}
				}
				if($d==$dia){
					$class=' class="xajax_calendar_selectday" ';
				}
				if($labday){
					$events=" onclick=\"xajax_setCalendar('".$id_tag."','".$call_javacript."','".$d."','".$mes."','".$ano."','".$php_notdaylab_func."');\"";
				}
				//$func=$call_javacript."('".$d."','".$mes."','".$ano."');";
				//verificando si es domingo:
				$html.='<td align="center" '.$class.$events.'>'.$d.'</td>';
				if($tday==$finsemana){
					$html.='</tr><tr>';
				}				
			}
			$html.='</tr>';
		$html.='</table>';
		//$r->alert($html);
		$func=$call_javacript."('".$dia."','".$mes."','".$ano."');";
		$r->script($func);
		$r->assign($id_tag,"innerHTML",$html);
		
		
		return $r;
	}

	function getDayNotLabForMonth($tmes,$tano){
		//return array(5=>1,10=>1,15=>1,21=>2);
		$c= new connection();
		$c->open();
		$pg_fecha_baja = new table("pg_fecha_baja");
		$dia=new field("fecha_baja",'dia');
		$mes=new field("fecha_baja",'mes');
		$ano=new field("fecha_baja",'ano');
		$fmes=_function::dateMonthFormat($mes);
		$fano=_function::dateYearFormat($ano);
		$dia->setFunction(_function::dateDayFormat());
		$mes->setFunction($fmes);
		$ano->setFunction($fano);
		$pg_fecha_baja->add(new field("parcial"));
		$pg_fecha_baja->forceAdd($dia);
		$pg_fecha_baja->forceAdd($mes);
		$pg_fecha_baja->forceAdd($ano);
		$q= new query($c);
		$q->addCriteria(new criteria(sqlNOTEQUAL,$pg_fecha_baja->tipo,4));
		$q->addCriteria(new criteria(sqlEQUAL,$fmes,intval($tmes)));
		$q->addCriteria(new criteria(sqlOR,new criteria("=",$fano,intval($tano)),new criteria("=",$fano,'0')));
		$q->add($pg_fecha_baja);
		//echo $q->getSelect();
		$r=$q->doSelect();
		if($r!=null){
			foreach($r as $value){
				$ret[$r->dia]=$r->parcial;
			}
			return $ret;
		}
	}
	
	function getIntervalData($d,$mes,$ano){
//-------------------- extraer e implementar
		$c = new connection();
		$c->open();
		//extrayendo el intervalo a la fecha:
		$sa_v_intervalo=$c->sa_v_intervalo;
		$qintervalo=new query($c);
		$qintervalo->add($sa_v_intervalo);
		$ffecha=sprintf("%02s",$d).'-'.sprintf("%02s",$mes).'-'.sprintf("%04s",$ano);
		$fechasql=field::getTransformType($ffecha,field::tDate);
		//$r->alert($fechasql);
		//return $r;
		$qintervalo
		->where(new criteria(sqlEQUAL,$sa_v_intervalo->id_empresa,$_SESSION['idEmpresaUsuarioSysGts']))
		->where(
		new criteria(sqlOR,
		new betweenCriteria($fechasql,$sa_v_intervalo->fecha_inicio,$sa_v_intervalo->fecha_fin),
		new criteria(sqlAND,
				array(
					new criteria(" >= ",$fechasql,$sa_v_intervalo->fecha_inicio),
					new criteria(sqlIS,$sa_v_intervalo->fecha_fin,sqlNULL)
				)
			)
		)
		)->setLimit(1);
		$interval=$qintervalo->doSelect();
		return $interval;
		/*if($interval){
			$max_sdays=$interval->dias_semana;
		}else{
			$max_sdays=7;
		}		*/		
//-------------------- extraer e implementar
	}
	
	function includeXajaxCalendarCss(){
		echo '<link rel="stylesheet" type="text/css" href="control/css/xajax_calendar.css" />';
	}
	
	xajaxRegister('setCalendar');
?>