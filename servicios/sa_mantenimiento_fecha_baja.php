<?php

@session_start();
define('PAGE_PRIV','sa_mantenimiento_fecha_baja');//nuevo gregor
//define('PAGE_PRIV','sa_fecha_baja');//anterior
require_once("../inc_sesion.php");

//implementando xajax;
	require_once("control/main_control.inc.php");
	require_once("control/funciones.inc.php");
	
	function load_page($page,$maxrows,$order,$ordertype,$capa,$args=''){
		$r= getResponse();
		if (!xvalidaAcceso($r,PAGE_PRIV)){
			//$r->alert('acceso denegado');
			$r->assign($capa,inner,'Acceso denegado');
			return $r;
		}
		//$r->alert(utf8_encode($args));
		$c = new connection();
		$c->open();
		
		//procesando argumentos:
		$argumentos=paginator::getExplodeArgs($args);
		
		$fechas = $c->sa_v_datos_fecha_baja;
		
		$pg_v_empleado = $c->pg_v_empleado;	
		
		
		$query = new query($c);
		$query->add($fechas);
		//$query->add($pg_v_empleado);
		if($argumentos['fecha']!='null'){
			
	//$r->alert(utf8_encode(new criteria(sqlEQUAL,$fechas->fecha_baja,field::getTransformType($argumentos['fecha'],field::tDate))));
			$query->where(new criteria(sqlEQUAL,$fechas->fecha_baja,field::getTransformType($argumentos['fecha'],field::tDate)));
		}
		
		//gregor empresa empleado		
	   // $join_fechas = $fechas->join($pg_v_empleado,$pg_v_empleado->id_empleado,$fechas->id_empleado);		
		//$query->where(new criteria(sqlEQUAL, $pg_v_empleado->id_empresa,$_SESSION['idEmpresaUsuarioSysGts']));
		
		
		
		$paginador = new paginator('xajax_load_page',$capa,$query,$maxrows);
		
		$rec=$paginador->run($page,$order,$ordertype,$args);
		
		if($rec){
			if($rec->getNumRows()==0){
				$html.='<div class="order_empty">No se han registrado dias no laborables para el d&iacute;a:'.$argumentos['fecha'].'</div>';
			}else{
				$html.='<table class="order_table"><thead><tr class="xajax_order_title">
				
				<td>'.$paginador->get($fechas->fecha_baja,'Fecha').'</td>
				<td nowrap="nowrap">'.$paginador->get($fechas->tipo,'Tipo').'</td>
				<td>'.$paginador->get($fechas->parcial,'Parcial').'</td>
				<td>'.$paginador->get($fechas->descripcion,'Descripci&oacute;n').'</td>
				<td>'.$paginador->get($fechas->hora_inicio_baja_12,'Hora inicio').'</td>
				<td>'.$paginador->get($fechas->hora_fin_baja_12,'Hora fin').'</td>
				<td>'.$paginador->get($fechas->empleado,'Empleado').'</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				
				</tr></thead><tbody>';
				$class='';
				foreach($rec as $v){
					
					
					
					if($rec->id_empleado){
						$busqueda_empresa_empleado = mysql_query("SELECT * FROM pg_v_empleado WHERE id_empleado = ".$rec->id_empleado." LIMIT 1");
						if(!$busqueda_empresa_empleado){
							$r->alert("Error Buscando la empresa al empleado. \n".mysql_error()."\n linea: ".__LINE__);
							}
						while ($row = mysql_fetch_array($busqueda_empresa_empleado)){
								$empresa_empleado = $row["id_empresa"];
							}
						
						}
						
					if($rec->id_empleado == true && $empresa_empleado != $_SESSION['idEmpresaUsuarioSysGts']){
						
						}else{
					
					if ($rec->parcial == '1')
						$parcial = 'Si';
					else
						$parcial = 'No';
					$html.='<tr class="'.$class.'">
					
					<td align=\'center\'>'.$rec->fecha_baja_formato.'</td>
					<td align=\'center\'>'.$rec->tipo.'</td>
					<td align=\'center\'>'.$parcial.'</td>
					<td align=\'center\'>'.$rec->descripcion.'</td>
					<td align=\'center\'>'.$rec->hora_inicio_baja_12.'</td>
					<td align=\'center\'>'.$rec->hora_fin_baja_12.'</td>
					<td align=\'center\' id_empleado='.$rec->id_empleado.' id_empresa = '.$empresa_empleado.'>'.$rec->empleado.'</td>
					<td align=\'center\'><img src=\'../img/iconos/view.png\' width=\'16\' border=\'0\' onClick="xajax_verFechaBaja('.$rec->id_fecha_baja.');"></td>
					<td align=\'center\'><img src=\'../img/iconos/edit.png\' width=\'16\' border=\'0\' onClick="xajax_editarFechaBaja('.$rec->id_fecha_baja.');"></td>
					<td align=\'center\'><img src=\'../img/iconos/delete.png\' width=\'16\' border=\'0\' onClick=" if(confirm(\'Desea Eliminar?\') == true) xajax_eliminarFechaBaja('.$rec->id_fecha_baja.');"></td>
					</tr>';
					if($class==''){
						$class='impar';
					}else{
						$class='';
					}
					
					}//otro if xd
				}
				$html.='</tbody></table>';
			}
			
		}
		
		$r->assign($capa,inner,$html);
		//$r->assign('fecha',inner,$argumentos['fecha_cita']);
		$r->assign('paginador',inner,'<hr>Mostrando '.$paginador->count.' resultados de un total de '.$paginador->totalrows.' '.$paginador->getPages());
		if (ifnull($argumentos['fecha']) == 'null'){
			$fec = "";
			}
		else
			$fec = ifnull($argumentos['fecha']);
		$r->assign('campoFecha','value',$fec);
		$r->script("
		cita_date.page=".$paginator->page.";
		cita_date.maxrows=".$paginator->maxrows.";
		cita_date.order='".$paginator->order."';
		cita_date.ordertype='".$paginator->ordertype."';
		cita_date.fecha='".$argumentos['fecha']."';
		");
		$c->close();
		return $r;
	}
	
	function agregarFechaBaja($valForm){
		$r= getResponse();
		$c = new connection();
		$c->open();
		$aux = true;
		$aux1 = true;
		
		if ($valForm['cbxParcial'] == 'on'){
			$horaInicio = "'".$valForm['selHoraInicio']."'";
			$horaFin = "'".$valForm['selHoraFin']."'";
			$parcial = 1;
			}
		else{
			$horaInicio = "NULL";
			$horaFin = "NULL";
			$parcial = 0;
			}
			
		if ($valForm['selTipo'] == 4)
			$empleado = "'".$valForm['selEmpleado']."'";
		else
			$empleado = "NULL";
		
		if ($valForm['selTipo'] == 1){
			$fecha = explode("-",$valForm['nuevaFecha']);
			$fechaDefinitiva = $fecha[0]."-".$fecha[1]."-0000";
			}
		else
			$fechaDefinitiva = $valForm['nuevaFecha'];
			
			$fecha = explode("-",$fechaDefinitiva);
			$fechaDefinitiva = $fecha[2]."-".$fecha[1]."-".$fecha[0];
			
			if ($valForm['selTipo'] == 1){
				$rsFecha = mysql_query("SELECT * FROM pg_fecha_baja WHERE fecha_baja = '".$fechaDefinitiva."'");
				if (mysql_num_rows($rsFecha) > 0)
					$aux1 = false;
			}
			
			if ($valForm['selEmpleado'] != '-1'){
			$rs = mysql_query("SELECT * FROM pg_fecha_baja 
			WHERE id_empleado = '".$valForm['selEmpleado']."' AND fecha_baja = '".$fechaDefinitiva."'");
				if (mysql_num_rows($rs) > 0 && $aux == true){
					while ($registro = mysql_fetch_array($rs)){
						if ($registro['hora_inicio_baja'] == '' || $registro['hora_fin_baja'] == '' || $horaInicio == "NULL" || $horaFin == "NULL"){
							$aux = false;
						}
						else{
							$arrayHoraInicio1 = explode(":",$registro['hora_inicio_baja']);
							$varHoraInicio1 = ((int)$arrayHoraInicio1[0] * 60) + (int)$arrayHoraInicio1[1];
							
							$arrayHoraInicio2 = explode(":",$valForm['selHoraInicio']);
							$varHoraInicio2 = ((int)$arrayHoraInicio2[0] * 60) + (int)$arrayHoraInicio2[1];
							
							$arrayHoraFin1 = explode(":",$registro['hora_fin_baja']);
							$varHoraFin1 = ((int)$arrayHoraFin1[0] * 60) + (int)$arrayHoraFin1[1];
							
							$arrayHoraFin2 = explode(":",$valForm['selHoraFin']);
							$varHoraFin2 = ((int)$arrayHoraFin2[0] * 60) + (int)$arrayHoraFin2[1];
							
							if (($varHoraInicio2 >= $varHoraInicio1 && $varHoraInicio2 < $varHoraFin1) || ($varHoraFin2 > $varHoraInicio1 && $varHoraFin2 <= $varHoraFin1) || ($varHoraInicio2 <= $varHoraInicio1 && $varHoraFin2 >= $varHoraFin1)){
							$aux = false;
							}
						}
					}
				}
			}
		
		if ($aux == true && $aux1 == true){
			if (!xvalidaAcceso($r,PAGE_PRIV,insertar)){
				//$c->rollback();
				return $r;
			}
			$query = "INSERT INTO pg_fecha_baja (id_fecha_baja, fecha_baja, parcial, tipo, descripcion, hora_inicio_baja, hora_fin_baja, id_empleado) VALUES ('','".$fechaDefinitiva."','".$parcial."','".$valForm['selTipo']."','".utf8_decode($valForm['nuevaDescripcion'])."',".$horaInicio.",".$horaFin.",".$empleado.")";
			mysql_query($query,$c->con) or die(mysql_error());
		
			$r->alert("Fecha de Baja insertada exitosamente");
			$r->script("xajax_load_page(datos.page,datos.maxrows,datos.order,datos.ordertype,'capaTabla','fecha='+datos.fecha);
						obj('divFlotante').style.display = 'none'");
			$r->script("document.forms('frmFechaBaja').reset();");
		}
		else{
			if ($aux == false)
				$r->alert(utf8_encode("El empleado ya tiene una fecha de baja registrada en el mismo per�odo"));
			if ($aux1 == false)
				$r->alert(utf8_encode("Ya hay un feriado registrado en esta fecha"));
		}
		$c->close();
		return $r;
	}
	
	function cargarLstEmpleado($idEmpleado,$accion){
		$r= getResponse();
		$c = new connection();
		$c->open();
		
			$rs1 = mysql_query("SELECT * FROM pg_v_empleado
								 WHERE id_empresa = ".$_SESSION['idEmpresaUsuarioSysGts']." ORDER BY cedula");
			$html = "<select id='selEmpleado' name='selEmpleado'>";
			$html .= "<option value='-1'>Seleccione..</option>";
			
			if(!$rs1){ 
			  return $r->alert("Error lista de empleados: ".mysql_error()."\n Nº de error: ".mysql_errno()." \n Linea: ".__LINE__); 
			}
			
			while ($registro1 = mysql_fetch_array($rs1)){
				if ($registro['id_empleado'] == $idEmpleado)
					$selected = "selected='selected'";
				else
					$selected = "";
				
				$html .= "<option value='".$registro1['id_empleado']."' ".$selected.">CI: ".$registro1['cedula']." ".utf8_encode($registro1['nombre_empleado'])." ".utf8_encode($registro1['apellido'])."</option>";
				}
			$html .= "<\select>";
			$r->assign('tdSelEmpleado',inner,$html);

			$rs2 = mysql_query("SELECT * FROM pg_empleado WHERE id_empleado = '".$idEmpleado."'");
			$registro2 = (mysql_fetch_array($rs2));
			$empleado = $registro2['cedula']." ".utf8_encode($registro2['nombre_empleado'])." ".utf8_encode($registro2['apellido']);

			$r->script("obj('txtEmpleado').size='".(strlen($empleado)+ 5)."'");
			$r->assign('txtEmpleado',value,$empleado);	

		$c->close();
		return $r;
	}
	
	function cargarLstHora($fecha,$tipo,$txtTipo,$horaInicio,$horaFin){
		$r= getResponse();

		$c = new connection();
		$c->open();
		//extrayendo el ultimo intervalo:
		$sa_v_intervalo=$c->sa_v_intervalo;
		$qintervalo=new query($c);
		$qintervalo->add($sa_v_intervalo);
		$fechasql=field::getTransformType($fecha,field::tDate);
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
		if ($interval){
			if ($interval->getNumRows() == 0){
				if ( $tipo != 1 && $txtTipo != "FERIADO")
					$r->alert("no se ha definido un intervalo para esta fecha");
			}
			else{
				//imprimiendo los intervalos:
				$hora_inicio=$interval->hora_inicio_jornada_h;
				$minuto_inicio=$interval->hora_inicio_jornada_m;
				$make_hora=adodb_mktime($hora_inicio,$minuto_inicio);
				$duracion_jornada=$interval->duracion_jornada;
				$intervalo=$interval->intervalo;
				$lapsos = $duracion_jornada / $intervalo;
				$arrayHoraInicio = explode(":",$horaInicio);
				$makeHoraInicio = adodb_mktime($arrayHoraInicio[0],$arrayHoraInicio[1]);
				$arrayHoraFin = explode(":",$horaFin);
				$makeHoraFin = adodb_mktime($arrayHoraFin[0],$arrayHoraFin[1]);
				
				for ($i = 0; $i <= $lapsos; $i++){
					if ($make_hora == $makeHoraInicio){
							$horaIniSel = "selected='selected'";
						}
					else
						$horaIniSel = "";
					
					if ($make_hora == $makeHoraFin){
						$horaFinSel = "selected='selected'";}
					else
						$horaFinSel = "";
						
					$htmlInicio .= '<option value="'.adodb_date('G:i',$make_hora).'" '.$horaIniSel.'>'.adodb_date(DEFINEDphp_TIME,$make_hora).'</option>';
					$htmlFin .= "<option value='".adodb_date('G:i',$make_hora)."' ".$horaFinSel.">".adodb_date(DEFINEDphp_TIME,$make_hora)."</option>";
					$make_hora=adodb_mktime(adodb_date('G',$make_hora),intval(adodb_date('i',$make_hora))+$intervalo);
				}
			}
		}
		
		if ($tipo == 1){
			$disabled = "disabled = true";
		}
		else{
			$disabled = "";
		}
		$r->assign("tdHoraInicio",inner,"<select id='selHoraInicio' name='selHoraInicio' ".$disabled.">".$htmlInicio."</select>");
		$r->assign("tdHoraFin",inner,"<select id='selHoraFin' name='selHoraFin' ".$disabled.">".$htmlFin."</select>");
	return $r;
	}
	
	function cargarLstTipo($tipo){
		$r= getResponse();

		$arrayTipo[1] = "Feriado";
		$arrayTipo[2] = "Baja";
		$arrayTipo[3] = "Otro";
		$arrayTipo[4] = "Empleado";
		$htmlTipo ="<select id='selTipo' name='selTipo' onChange='revisarTipo();'>
					<option value='-1'>Seleccione..</option>";
		for ($i = 1; $i <=4; $i++){
			if(utf8_encode($tipo) == strtoupper($arrayTipo[$i])){
				$selected = "selected='selected'";
				}
			else
				$selected = "";
			$htmlTipo .= "<option value='".$i."' ".$selected.">".$arrayTipo[$i]."</option>";
		}
		$htmlTipo .= "</select>";
		
		$r->assign('tdSelTipo',inner,$htmlTipo);
		
		return $r;
	}
	
	function cargarFechaBaja($id){
		$r= getResponse();
		if (!xvalidaAcceso($r,PAGE_PRIV)){
			//$r->alert('acceso denegado');
			return $r;
		}
		$c = new connection();
		$c->open();
		$tabla = $c->sa_v_datos_fecha_baja;
		$query = new query($c);
		$query->add($tabla);
		$query->where(new criteria("=",$tabla->id_fecha_baja,$id));
		$rec = $query->doSelect();
		
		if ($rec->tipo == "FERIADO"){
			$tipo = 1;
			$disabled = "disabled";
		}
		else if ($rec->tipo == "BAJA"){
			$tipo = 2;
			$disabled = "";
		}
		else if ($rec->tipo == "OTRO"){
			$tipo = 3;
			$disabled = "";
		}
		else if ($rec->tipo == "EMPLEADO"){
			$tipo = 4;
			$disabled = "";
			$r->script("xajax_cargarLstEmpleado(".$rec->id_empleado.",0);");
		}
		if ($tipo == 4)
			$r->script("obj('trEmpleado').style.display = '';");
		else
			$r->script("document.getElementById('trEmpleado').style.display = 'none';");
		
		if ($rec->parcial == 1){
        	$checked = "checked";
			$horaInicio = $rec->hora_inicio_baja;
			$horaFin = $rec->hora_fin_baja;
			}
		else{
			$checked = "";
			$horaInicio = "00:00";
			$horaFin = "00:00";
			$tipo = 1;
			}
		
		$r->assign('txtTipo',value,$rec->tipo);
		$r->assign('nuevaFecha',value,$rec->fecha_baja_formato);
		$r->assign('hddTxtFecha',value,$rec->fecha_baja_formato);
		$r->assign('hddIdFechaBaja',value,$rec->id_fecha_baja);
		$r->assign('hddIdEmpleado',value,$rec->id_empleado);
		$r->assign('tdParcial',inner,"<input type='checkbox' id='cbxParcial' name='cbxParcial' ".$checked." onClick='parcial()' ".$disabled.">");
		$r->assign('nuevaDescripcion',value,$rec->descripcion);
		$r->script("xajax_cargarLstHora('".$rec->fecha_baja_formato."',".$tipo.",'".$rec->tipo."','".$horaInicio."','".$horaFin."');");
		$r->script("obj('divFlotante').style.display = '';
					parcial();
					obj('tdSelEmpleado').style.display = 'none';
					obj('tdTxtEmpleado').style.display = '';");

		$c->close();
		return $r;
	}
	
	function editarFechaBaja($id){
		$r= getResponse();
		if (!xvalidaAcceso($r,PAGE_PRIV,editar)){
			//$r->alert('acceso denegado');
			return $r;
		}

		$r->script("obj('divFlotanteTitulo').innerHTML = 'Editar Fecha Baja'
					obj('trBttGuardarCancelar').style.display = 'none';
					obj('trBttGuardarCancelarModificacion').style.display = '';
					obj('trBttAceptar').style.display = 'none';
					obj('tdSelTipo').style.display = 'none';
					obj('tdTxtTipo').style.display = '';
					setOriginalCenter('divFlotante',true);");

		$r->script("xajax_cargarFechaBaja(".$id.")");
	
		return $r;
	}
	
	function eliminarFechaBaja($id){
		$r= getResponse();
		if (!xvalidaAcceso($r,PAGE_PRIV,eliminar)){
			return $r;
		}
		$c = new connection();
		$c->open();
		
		mysql_query("DELETE FROM pg_fecha_baja WHERE id_fecha_baja = '".$id."'");
		
		$r->alert("Eliminacion realizada exitosamente");
		
		$r->script("xajax_load_page(datos.page,datos.maxrows,datos.order,datos.ordertype,'capaTabla','fecha='+datos.fecha)");
		$c->close();
		return $r;
	}
	
	function verFechaBaja($id){
		$r= getResponse();	
		
		$r->script("obj('divFlotanteTitulo').innerHTML = 'Ver Fecha Baja'
					obj('trBttGuardarCancelar').style.display = 'none';
					obj('trBttGuardarCancelarModificacion').style.display = 'none';
					obj('trBttAceptar').style.display = '';
					obj('tdSelTipo').style.display = 'none';
					obj('tdTxtTipo').style.display = '';
					setOriginalCenter('divFlotante',true);");
					
		$r->script("xajax_cargarFechaBaja(".$id.")");
		
		return $r;
	}
	
	function modificarFechaBaja($valForm,$accion){
		$r= getResponse();
		$c = new connection();
		$c->open();
		
		$arrayFecha = explode("-",$valForm['nuevaFecha']);
		
		if ($valForm['cbxParcial'] == 'on'){
			$horaInicio = "'".$valForm['selHoraInicio']."'";
			$horaFin = "'".$valForm['selHoraFin']."'";
			$parcial = 1;
			}
		else{
			$horaInicio = "NULL";
			$horaFin = "NULL";
			$parcial = 0;
			}
		
		if (!xvalidaAcceso($r,PAGE_PRIV,editar)){
			//$c->rollback();
			return $r;
		}
		if ($accion == 1){
		$fecha = "0000-".$arrayFecha[1]."-".$arrayFecha[0];
		$rs = mysql_query("UPDATE pg_fecha_baja SET fecha_baja = '".$fecha."' , descripcion = '".utf8_decode($valForm['nuevaDescripcion'])."' WHERE id_fecha_baja = '".$valForm['hddIdFechaBaja']."'");
		}
		else{
		$fecha = $arrayFecha[2]."-".$arrayFecha[1]."-".$arrayFecha[0];
		$rs = mysql_query("UPDATE pg_fecha_baja SET fecha_baja = '".$fecha."' , descripcion = '".utf8_decode($valForm['nuevaDescripcion'])."' , hora_inicio_baja = ".$horaInicio." , hora_fin_baja = ".$horaFin." , parcial = ".$parcial." WHERE id_fecha_baja = '".$valForm['hddIdFechaBaja']."'");
		}
		
		
		if ($rs)
			$r->alert("Fecha modificada exitosamente");
		else
			$r->alert("Error al modificar la fecha");
		
		$r->script("xajax_load_page(datos.page,datos.maxrows,datos.order,datos.ordertype,'capaTabla','fecha='+datos.fecha);
					obj('divFlotante').style.display = 'none'");
		$r->script("document.forms('frmFechaBaja').reset();");
		$c->close();
		return $r;
	}
	
	xajaxRegister('load_page');
	xajaxRegister('agregarFechaBaja');
	xajaxRegister('cargarLstEmpleado');
	xajaxRegister('cargarLstHora');
	xajaxRegister('cargarLstTipo');
	xajaxRegister('cargarFechaBaja');
	xajaxRegister('editarFechaBaja');
	xajaxRegister('eliminarFechaBaja');
	xajaxRegister('verFechaBaja');
	xajaxRegister('modificarFechaBaja');
		
	xajaxProcess();
	
	includeDoctype();
		
?>

<html>
	<head>
		<?php 
			includeMeta();
			includeScripts();
			getXajaxJavascript();
			//includeModalBox();
			
		?>
		<link rel="stylesheet" type="text/css" href="css/sa_general.css" />
		<title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Días no laborables</title>
                <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
	   <style type="text/css">
            .root {
                background-color:#FFFFFF;
                border:6px solid #999999;
                font-family:Verdana, Arial, Helvetica, sans-serif;
                font-size:11px;
                max-width:1000px;
                position:absolute;
            }
            
            .handle {
                padding:2px;
                background-color:#000066;
                color:#FFFFFF;
                font-weight:bold;
                cursor:move;
            }
            
			.inputInicial{
			//	background-color:;
			}
			.inputErrado{
				background-color:#ECD7D7;
			}
			button img{
				padding-right:1px;
				padding-bottom:1px;
				vertical-align:middle;
			}
		</style>
		
		<script type="text/javascript">
			//detectEditWindows({edit_window:'guardar'});
			var datos = {
				fecha: 'null',
				date:new Date(),
				page:0,
				maxrows:15,
				order:null,
				ordertype:null
			}
			
			function restablecer(){
				datos.fecha= 'null';
				datos.date=new Date();
				datos.page=0;
				datos.order=null;
				datos.ordertype=null;
				xajax_load_page(datos.page,datos.maxrows,datos.order,datos.ordertype,'capaTabla','fecha='+datos.fecha);
			}
			
			function  calendar_onselect (calendar,date){//DD-MM-AAAA
				if (calendar.dateClicked){
				var dia=date.substr(0,2);
				var mes=parseInt(date.substr(3,2))-1;
				var ano=date.substr(6,4);
				xajax_load_page(datos.page,datos.maxrows,datos.order,datos.ordertype,'capaTabla','fecha='+date);
				datos.date=new Date(ano,mes,dia);
				calendar.hide();
				}
			}			
	
			function  calendar_onclose (calendar){
				calendar.hide();
			}
			
			var cita_calendar = new Calendar(1,null,calendar_onselect,calendar_onclose);
			cita_calendar.setDateFormat("%d-%m-%Y");
			
			function cargar_cita_fecha(_obj){
				cita_calendar.create();
				cita_calendar.setDate(datos.date);
				cita_calendar.showAtElement(_obj);
			}
			
			function nuevaFecha(){
				document.forms['frmFechaBaja'].reset();
				document.getElementById('selTipo').className = "inputInicial";
				document.getElementById('nuevaFecha').className = "inputInicial";
				document.getElementById('nuevaDescripcion').className = "inputInicial";
				document.getElementById('selHoraInicio').className = "inputInicial";
				document.getElementById('selHoraFin').className = "inputInicial";
				document.getElementById('selEmpleado').className = "inputInicial";
				document.getElementById('trEmpleado').style.display = "none";
				document.getElementById('selTipo').disabled = false;
				document.getElementById('nuevaFecha').disabled = false;
				document.getElementById('nuevaDescripcion').disabled = false;
				document.getElementById('selHoraInicio').disabled = false;
				document.getElementById('selHoraFin').disabled = false;
				document.getElementById('selEmpleado').disabled = false;
				document.getElementById('cbxParcial').disabled = false;
				
				obj('tdSelTipo').style.display = '';
				obj('tdTxtTipo').style.display = 'none';
				obj('trBttGuardarCancelar').style.display = '';
				obj('trBttGuardarCancelarModificacion').style.display = 'none';
				obj('trBttAceptar').style.display = 'none';
				obj('tdSelEmpleado').style.display = '';
				obj('tdTxtEmpleado').style.display = 'none';
				document.getElementById('divFlotante').style.display = '';
				setCenter('divFlotante',true);
				document.getElementById('divFlotanteTitulo').innerHTML = 'Nueva Fecha Baja';
				xajax_cargarLstEmpleado('0',1);
				xajax_cargarLstTipo('');
				xajax_cargarLstHora('',0,'FERIADO','','');
			}
			
			function revisarTipo(){
				document.getElementById('trEmpleado').style.display = 'none';
				document.getElementById('selTipo').className = "inputInicial";
				document.getElementById('nuevaFecha').className = "inputInicial";
				document.getElementById('nuevaDescripcion').className = "inputInicial";
				document.getElementById('selHoraInicio').className = "inputInicial";
				document.getElementById('selEmpleado').className = "inputInicial";
				var tipo = document.getElementById('selTipo').value;
				if (tipo == 1){
					document.getElementById('trEmpleado').style.display = 'none';
					document.getElementById('selHoraInicio').disabled = true;
					document.getElementById('selHorafin').disabled = true;
					document.getElementById('cbxParcial').disabled = true;
					document.getElementById('cbxParcial').checked = false;
					document.getElementById('hddFormatoFecha').value = "%d-%m";/**/
				}
				else if (tipo == 2){
					document.getElementById('trEmpleado').style.display = 'none';
					document.getElementById('selHoraInicio').disabled = false;
					document.getElementById('selHorafin').disabled = false;
					document.getElementById('cbxParcial').disabled = false;
					document.getElementById('cbxParcial').checked = true;
				}
				else if (tipo == 3){
					document.getElementById('trEmpleado').style.display = 'none';
					document.getElementById('selHoraInicio').disabled = false;
					document.getElementById('selHorafin').disabled = false;
					document.getElementById('cbxParcial').disabled = false;
					document.getElementById('cbxParcial').checked = true;
				}
				else if (tipo == 4){
					document.getElementById('trEmpleado').style.display = '';
					document.getElementById('selHoraInicio').disabled = false;
					document.getElementById('selHorafin').disabled = false;
					document.getElementById('cbxParcial').disabled = false;
					document.getElementById('cbxParcial').checked = true;
				}
			}
			
			function parcial(){
				if (document.getElementById('cbxParcial').checked){
					document.getElementById('selHoraInicio').disabled = false;
					document.getElementById('selHorafin').disabled = false;
				}
				else{
					document.getElementById('selHoraInicio').disabled = true;
					document.getElementById('selHorafin').disabled = true;
				}
			}
			
			function validarNuevaFecha(){
				document.getElementById('selTipo').className = "inputInicial";
				document.getElementById('nuevaFecha').className = "inputInicial";
				document.getElementById('nuevaDescripcion').className = "inputInicial";
				document.getElementById('selHoraInicio').className = "inputInicial";
				document.getElementById('selHoraFin').className = "inputInicial";
				document.getElementById('selEmpleado').className = "inputInicial";
				
				fec = new Date();
				ano = fec.getFullYear();
				mes = fec.getMonth();
				dia = fec.getDate();
				fecha = new Date(ano,mes,dia)
				
				var arrayHoraInicio = obj('selHoraInicio').value.split(":");
				var horaInicio = parseInt(arrayHoraInicio[0])*60 + parseInt(arrayHoraInicio[1]);
				
				var arrayHoraFin = obj('selHoraFin').value.split(":");
				var horaFin = parseInt(arrayHoraFin[0])*60 + parseInt(arrayHoraFin[1]);
				
				if (/*comparar(fecha,document.getElementById('nuevaFecha').value) ||*/ obj('selTipo').value == 1){
					if (horaInicio < horaFin || obj('cbxParcial').checked == false){
						if (document.getElementById('selTipo').value == 1){
						if (document.getElementById('nuevaFecha').value != "" &&
							document.getElementById('nuevaDescripcion').value != ""){
								xajax_agregarFechaBaja(xajax.getFormValues('frmFechaBaja'));
							}
						else{
							if (document.getElementById('nuevaFecha').value == ""){
								document.getElementById('nuevaFecha').className = "inputErrado";
							}
							if (document.getElementById('nuevaDescripcion').value == ""){
								document.getElementById('nuevaDescripcion').className = "inputErrado";
							}
						_alert('Los campos se&ntilde;alados en rojo son requeridos');
						return false;
						}
					}
						else if (document.getElementById('selTipo').value == 2){
						if (document.getElementById('cbxParcial').checked == true){
							if (document.getElementById('nuevaFecha').value != "" &&
							document.getElementById('nuevaDescripcion').value != "" &&
							document.getElementById('selHoraInicio').value != -1 &&
							document.getElementById('selHoraFin').value != -1){
								xajax_agregarFechaBaja(xajax.getFormValues('frmFechaBaja'));
							}
							else{
								if (document.getElementById('nuevaFecha').value == "")
									document.getElementById('nuevaFecha').className = "inputErrado";
								
								if (document.getElementById('nuevaDescripcion').value == "")
									document.getElementById('nuevaDescripcion').className = "inputErrado";
								
								if (document.getElementById('selHoraInicio').value == -1)
									document.getElementById('selHoraInicio').className = "inputErrado";
								
								if (document.getElementById('selHoraFin').value == -1)
									document.getElementById('selHoraFin').className = "inputErrado";
								_alert('Los campos se&ntilde;alados en rojo son requeridos');
								return false;
							}
						}
						else{
							if (document.getElementById('nuevaFecha').value != "" &&
							document.getElementById('nuevaDescripcion').value != "" ){
								xajax_agregarFechaBaja(xajax.getFormValues('frmFechaBaja'));
							}
							else{
								if (document.getElementById('nuevaFecha').value == "")
									document.getElementById('nuevaFecha').className = "inputErrado";
								
								if (document.getElementById('nuevaDescripcion').value == "")
									document.getElementById('nuevaDescripcion').className = "inputErrado";
									
								_alert('Los campos se&ntilde;alados en rojo son requeridos');
								return false;
							}						
						}
					}
						else if (document.getElementById('selTipo').value == 3){
						if (document.getElementById('cbxParcial').checked == true){
							if (document.getElementById('nuevaFecha').value != "" &&
							document.getElementById('nuevaDescripcion').value != "" &&
							document.getElementById('selHoraInicio').value != -1 &&
							document.getElementById('selHoraFin').value != -1){
								xajax_agregarFechaBaja(xajax.getFormValues('frmFechaBaja'));
							}
							else{
								if (document.getElementById('nuevaFecha').value == "")
									document.getElementById('nuevaFecha').className = "inputErrado";
								
								if (document.getElementById('nuevaDescripcion').value == "")
									document.getElementById('nuevaDescripcion').className = "inputErrado";
								
								if (document.getElementById('selHoraInicio').value == -1)
									document.getElementById('selHoraInicio').className = "inputErrado";
								
								if (document.getElementById('selHoraFin').value == -1)
									document.getElementById('selHoraFin').className = "inputErrado";
								_alert('Los campos se&ntilde;alados en rojo son requeridos');
								return false;
							}
						}
						else{
							if (document.getElementById('nuevaFecha').value != "" &&
							document.getElementById('nuevaDescripcion').value != "" ){
								xajax_agregarFechaBaja(xajax.getFormValues('frmFechaBaja'));
							}
							else{
								if (document.getElementById('nuevaFecha').value == "")
									document.getElementById('nuevaFecha').className = "inputErrado";
								
								if (document.getElementById('nuevaDescripcion').value == "")
									document.getElementById('nuevaDescripcion').className = "inputErrado";
									
								_alert('Los campos se&ntilde;alados en rojo son requeridos');
								return false;
							}						
						}
					}
						else if (document.getElementById('selTipo').value == 4){
						if (document.getElementById('cbxParcial').checked == true){
							if (document.getElementById('nuevaFecha').value != "" &&
							document.getElementById('nuevaDescripcion').value != "" &&
							document.getElementById('selHoraInicio').value != -1 &&
							document.getElementById('selHoraFin').value != -1 &&
							document.getElementById('selEmpleado').value != -1){
								xajax_agregarFechaBaja(xajax.getFormValues('frmFechaBaja'));
							}
							else{
								if (document.getElementById('nuevaFecha').value == "")
									document.getElementById('nuevaFecha').className = "inputErrado";
								
								if (document.getElementById('nuevaDescripcion').value == "")
									document.getElementById('nuevaDescripcion').className = "inputErrado";
								
								if (document.getElementById('selHoraInicio').value == -1)
									document.getElementById('selHoraInicio').className = "inputErrado";
								
								if (document.getElementById('selHoraFin').value == -1)
									document.getElementById('selHoraFin').className = "inputErrado";
								
								if (document.getElementById('selEmpleado').value == -1)
									document.getElementById('selEmpleado').className = "inputErrado";
									
								if (document.getElementById('selEmpleado').value == -1)
									document.getElementById('selEmpleado').className = "inputErrado";
								_alert('Los campos se&ntilde;alados en rojo son requeridos');
								return false;
							}
						}
						else{
							if (document.getElementById('nuevaFecha').value != "" &&
							document.getElementById('nuevaDescripcion').value != "" &&
							document.getElementById('selEmpleado').value != -1){
								xajax_agregarFechaBaja(xajax.getFormValues('frmFechaBaja'));
							}
							else{
								if (document.getElementById('nuevaFecha').value == "")
									document.getElementById('nuevaFecha').className = "inputErrado";
								
								if (document.getElementById('nuevaDescripcion').value == "")
									document.getElementById('nuevaDescripcion').className = "inputErrado";
									
								if (document.getElementById('selEmpleado').value == -1)
									document.getElementById('selEmpleado').className = "inputErrado";								
								_alert('Los campos se&ntilde;alados en rojo son requeridos');
								return false;
							}						
						}
					}
						else {
					if (document.getElementById('selTipo').value == -1)
						document.getElementById('selTipo').className = "inputErrado";
					
					if (document.getElementById('nuevaFecha').value == "")
						document.getElementById('nuevaFecha').className = "inputErrado";
					
					if (document.getElementById('nuevaDescripcion').value == "")
						document.getElementById('nuevaDescripcion').className = "inputErrado";
					
					if (document.getElementById('selHoraInicio').value == -1)
						document.getElementById('selHoraInicio').className = "inputErrado";
					
					if (document.getElementById('selHoraFin').value == -1)
						document.getElementById('selHoraFin').className = "inputErrado";
					
					if (document.getElementById('selEmpleado').value == -1)
						document.getElementById('selEmpleado').className = "inputErrado";
					
					_alert('Los campos se&ntilde;alados en rojo son requeridos');
					return false;	
				}
					}
					else
						alert('La Hora de inicio no puede ser mayor o igual a la hora de fin');
				}
				else{
					document.getElementById('nuevaFecha').className = "inputErrado";
					if (document.getElementById('nuevaFecha').value == ""){
						alert("Introduzca Fecha");
                                        }else{
						//alert("Fecha pasada1");
                                                xajax_agregarFechaBaja(xajax.getFormValues('frmFechaBaja'));
                                        }
					return false;
				}
			}
			
			function comparar(fe1,fe2) {
				dia2 = fe2.substring(0,2);
				d2 = parseInt(dia2,10);
				mes2 = fe2.substring(3,5);
				m2 = parseInt(mes2,10);
				ano2 = fe2.substring(6,10);
				a2 = parseInt(ano2,10);
				
				var fecha2 = new Date(a2,m2-1,d2);
				if (fe1 <= fecha2)
					return 1;
				else
					return 0;
			}
			
			function cargarLista(calendar,date){
			if (calendar.dateClicked){
				xajax_cargarLstHora(date,obj('selTipo').value,obj('txtTipo').value,'00:00','00:00');
				obj("nuevaFecha").value = date;
				calendar.hide();
				}
			}
			
			function validarModificarFecha(){
				document.getElementById('selTipo').className = "inputInicial";
				document.getElementById('nuevaFecha').className = "inputInicial";
				document.getElementById('nuevaDescripcion').className = "inputInicial";
				document.getElementById('selHoraInicio').className = "inputInicial";
				document.getElementById('selHoraFin').className = "inputInicial";
				document.getElementById('selEmpleado').className = "inputInicial";
			
				fec = new Date();
				ano = fec.getFullYear();
				mes = fec.getMonth();
				dia = fec.getDate();
				fecha = new Date(ano,mes,dia);
				
				var arrayHoraInicio = obj('selHoraInicio').value.split(":");
				var horaInicio = parseInt(arrayHoraInicio[0])*60 + parseInt(arrayHoraInicio[1]);
				
				var arrayHoraFin = obj('selHoraFin').value.split(":");
				var horaFin = parseInt(arrayHoraFin[0])*60 + parseInt(arrayHoraFin[1]);

				if (/*comparar(fecha,obj('nuevaFecha').value) ||*/ obj('txtTipo').value == 'FERIADO' || obj('nuevaFecha').value == obj('hddTxtFecha').value){
					if (obj('txtTipo').value == 'FERIADO'){
						if (obj('nuevaDescripcion').value != "")
							xajax_modificarFechaBaja(xajax.getFormValues('frmFechaBaja'),1)
						else{
							document.getElementById('nuevaDescripcion').className = "inputErrado";
							_alert('Los campos se&ntilde;alados en rojo son requeridos');
							return false;	
						}
					}
					else{
						if (horaInicio < horaFin || obj('cbxParcial').checked == false && obj('nuevaDescripcion').value != ""){
							xajax_modificarFechaBaja(xajax.getFormValues('frmFechaBaja'),2)
						}
						else{
							if (obj('nuevaDescripcion').value == ""){
								document.getElementById('nuevaDescripcion').className = "inputErrado";
								_alert('Los campos se&ntilde;alados en rojo son requeridos');	
							}
							if (horaInicio >= horaFin){
								document.getElementById('selHoraInicio').className = "inputErrado";
								document.getElementById('selHoraFin').className = "inputErrado";
								alert('La Hora de inicio no puede ser mayor o igual a la hora de fin');
							}
						}
					}
				}
				else{
					document.getElementById('nuevaFecha').className = "inputErrado";
					alert("Fecha pasada2");
					return false;
				}
			}
		</script>
	</head>
	<body>
<?php include("banner_servicios.php"); ?>
	<div style="width:960px; background:#FFFFFF; margin: auto;">
	<table align="center" border="0" width="100%">
		<tr>
			<td align="right" class="titulo_pagina" ><span >Mantenimiento de Servicios</span><br />
			<span class="subtitulo_pagina" >(D&iacute;as no laborables)</span></td>
		</tr>
	</table>
	
	<br />
	
<!--MARCO PRINCIPAL-->
<div>
	<div>
		<button type="button" value="Nuevo" onClick="nuevaFecha();;" ><img border="0" src="<?php echo getUrl('img/iconos/plus.png') ?>" />Nuevo</button>
		<em>Filtrar por Fecha:</em><span id='capaFecha'><input type="text" id="campoFecha" name="campoFecha" readonly></span><img src="<?php echo getUrl('img/iconos/select_date.png'); ?>" alt="cambiar fecha" title="Cambiar fecha" style="cursor:pointer;" id="fecha_cita_boton" onClick="cargar_cita_fecha(this);" border="0" width="20"/>
		<button type="button" value="reset" onClick="restablecer();" ><img border="0" src="<?php echo getUrl('img/iconos/cc.png') ?>" /></button>
		<hr />
	</div>
	<div id='capaTabla'></div>
	<div align="center" id='paginador'></div>
</div>
<!--MARCO PRINCIPAL-->
<div class="window" id="cuadro_citas" style="width:700px;visibility:hidden;">
	<div class="title" id="titulo_citas">
		Citas		
	</div>
	<div class="content">
		
	</div>
	<img class="close_window" src="../img/iconos/close_dialog.png" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="close_window('cuadro_citas');" border="0" />
</div>	
</div>
<?php include("menu_serviciosend.inc.php"); ?>
	
	<script type="text/javascript" language="javascript">
		xajax_load_page(datos.page,datos.maxrows,datos.order,datos.ordertype,'capaTabla','fecha='+datos.fecha);
	</script>
	</body>
</html>

<div id="divFlotante" class= "root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle" style="background:#018300;"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
<form id="frmFechaBaja" name="frmFechaBaja" style="margin:0">
    <table border="0" id="tblAgregarFechaBaja" width="300px">
        <tr id="trTipo">
            <td>
            	Tipo:
                <input type="hidden" id="hddFormatoFecha" name="hddFormatoFecha" value="%d-%m-%Y">
            </td>
            <td id="tdSelTipo">
                <select id="selTipo" name="selTipo" onChange="revisarTipo();">
                	<option value="-1">Seleccione..</option>
                    <option value='1'>Feriado</option>
                    <option value='2'>Baja</option>
                    <option value='3'>Otro</option>
                    <option value='4'>Empleado</option>
                </select>
			</td>
            <td id="tdTxtTipo">
            	<input type="text" id="txtTipo" name="txtTipo" readonly>
            </td>
        </tr>
        <tr>
        	<td>
            	Fecha:
            </td>
            <td>
                <input type="text" id="nuevaFecha" name="nuevaFecha" readonly><img src="../img/iconos/select_date.png" alt="cambiar fecha" title="Cambiar fecha" style="cursor:pointer;" id="imgCalendario" border="0" width="20"/>
				<script type="text/javascript" language="javascript">
					var formato = document.getElementById('hddFormatoFecha').value;
					Calendar.setup({
					inputField : "nuevaFecha", // id del campo de texto
					ifFormat : formato, // formato de la fecha que se escriba en el campo de texto
					button : "nuevaFecha", 
					onSelect : cargarLista
					});
					Calendar.setup({
					inputField : "nuevaFecha", // id del campo de texto
					ifFormat : formato, // formato de la fecha que se escriba en el campo de texto
					button : "imgCalendario",
					onSelect : cargarLista
					});
				</script>
                <input type="hidden" id="hddTxtFecha" name="hddTxtFecha">
                <input type="hidden" id="hddIdFechaBaja" name="hddIdFechaBaja">
            </td>
        </tr>
        <tr>
        	<td>
            	Parcial:
            </td>
            <td id="tdParcial">
                <input type="checkbox" id="cbxParcial" name="cbxParcial" checked onClick="parcial()">
            </td>
        </tr>
        <tr>
        	<td>
            	Descripci&oacute;n:
            </td>
            <td>
                <input type="text" id="nuevaDescripcion" name="nuevaDescripcion">
            </td>
        </tr>
        <tr id="trHoraInicio">
        	<td>
            	Hora inicio:
            </td>
            <td id="tdHoraInicio">
                <select id="selHoraInicio" name="selHoraInicio">
                	<option value="-1">Seleccione..</option>
                </select>
            </td>
        </tr>
        <tr id="trHoraFin">
        	<td>
            	Hora fin:
            </td>
            <td id="tdHoraFin">
                <select id="selHoraFin" name="selHoraFin">
                	<option value="-1">Seleccione..</option>
                </select>
            </td>
        </tr>
        <tr id="trEmpleado" style="display:none">
        	<td>
            	Empleado: emple
                <input type="hidden" id="hddIdEmpleado" name="hddIdEmpleado">
            </td>
            <td id="tdSelEmpleado">
                <select id="selEmpleado" name="selEmpleado">
                	<option value="-1">Seleccione..</option>
                </select>
            </td>
            <td id="tdTxtEmpleado">
            	<input type="text" id="txtEmpleado" name="txtEmpleado" readonly>
            </td>
        </tr>
        <tr id="trBttGuardarCancelar">
        	<td colspan="2" align="center">
            	<input type="button" value="Guardar" onClick="validarNuevaFecha();">
            	<input type="button" value="Cancelar" onClick="document.getElementById('divFlotante').style.display='none'; document.forms['frmFechaBaja'].reset();">
            </td>
        </tr>
        <tr id="trBttGuardarCancelarModificacion" style="display:none">
        	<td colspan="2" align="center">
            	<input type="button" value="Guardar" onClick="validarModificarFecha();">
            	<input type="button" value="Cancelar" onClick="document.getElementById('divFlotante').style.display='none'; document.forms['frmFechaBaja'].reset();">
            </td>
        </tr>
        <tr id="trBttAceptar" style="display:none">
        	<td colspan="2" align="center">
            	<input type="button" value="Aceptar" onClick="document.getElementById('divFlotante').style.display='none'; document.forms['frmFechaBaja'].reset();">
            </td>
        </tr>
    </table>
</form>
</div>
<script language="javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
</script>