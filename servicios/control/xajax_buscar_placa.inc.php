<?php
	require_once("iforms.inc.php");
	function buscarPlaca($busqueda,$parent,$php_callback_func='cargar_placa',$unico=''){
		$r=getResponse();                
                                
		$c=new connection();
		$c->open();
		$rec=_buscar_placa($busqueda,$c,$unico);
                
                if($rec == NULL){
                    return null;
                }
		//si no lo encuentra
		//$r->alert($rec);return $r;
		if($rec->getNumRows()==1){	//si lo encuentra
			if(function_exists($php_callback_func)){
				$r->loadCommands(call_user_func($php_callback_func,$rec));
			}
		}else{                    
			//vaciando las dem�s coincidencias
                    
			if($rec->getNumRows()==0){
				$tc='<tr><td>No se han encontrado coincidencias de: <em>'.$busqueda.'</em></td></tr>';
			}else{
				$par='_impar';
				$tc='<table class="xajax_client_div_window" style="border-collapse:collapse;width:96%;margin:auto;"><tr><td colspan="3">Se han encontrado '.$rec->getNumRows().' coincidencias de: <em>'.$busqueda.'</em></td></tr>';
				foreach($rec as $reg){
					$tc.=sprintf('<tr onclick="xajax_buscarPlaca(\'%s\',\'%s\',\'%s\','.$reg->id_registro_placas.');$(\'#xajax_client_div\').fadeOut();" class="xajax_tr%s"><td>'.imageTag(getUrl('img/iconos/select.png')).'</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
					$reg->placa,$parent,$php_callback_func,$par,$reg->placa,$reg->chasis,$reg->nom_modelo,$reg->nom_transmision,$reg->ano);
					$par = ($par!='') ? '' : '_impar';
				}
				$tc.='</table>';
			}
			$html='<table class="xajax_client_div_window" style="border-collapse:collapse;">
				<caption id="xajax_client_div_bar" class="xajax_client_div_bar">Coincidencias de Veh&iacute;culos</caption>
			</table><div class="_xajax_overflow">'.$tc.'</div>';
			
			$marcas=$c->an_marca->doSelect($c)->getAssoc($c->an_marca->id_marca,$c->an_marca->nom_marca);
			
			$intFormat=array('onkeypress'=>'return inputInt(event);');
			
			$html.=startForm('xajax_placa_form','',array('onsubmit'=>'return false;')).
			"<table class=\"xajax_client_div_window\">
				<caption id=\"\">Cargar nuevo Veh&iacute;culo</caption>
				<tr>
					<td class=\"_label\">Placa:*</td>
					<td class=\"_field\">".inputTag('text','xajax_placa_div_placa',$busqueda,array('maxlength'=>'8'))."</td>
					<td class=\"_label\">Chasis:</td>
					<td class=\"_field\">".inputTag('text','xajax_placa_div_chasis','',array('maxlength'=>'19'))."</td>
				</tr>
				<tr>
					<td class=\"_label\">Marca:*</td>
					<td class=\"_field\">".inputSelect('xajax_placa_div_id_marca',$marcas,null,array("onchange"=>"xajax_cargar_modelo(this.value);"))."</td>
					<td class=\"_label\">Modelo:*</td>
					<td class=\"_field\" id=\"capa_xajax_placa_div_id_modelo\">&nbsp;</td>
				</tr>
				<tr>
					<td class=\"_label\">Versi&oacute;n:*</td>
					<td class=\"_field\" id=\"capa_xajax_placa_div_id_version\">&nbsp;</td>
					<td class=\"_label\">Unidad:*</td>
					<td class=\"_field\" id=\"capa_xajax_placa_div_id_unidad_basica\">&nbsp;</td>
				</tr>
				<tr style=\"display:none;\">
					
					<td class=\"_label\">Kilometraje:*</td>
					<td class=\"_field\" colspan=\"3\">".inputTag('text','xajax_placa_div_kilometraje','',$intFormat)."</td>
				</tr>
				<tr>
					<td class=\"_label\">Fecha de Venta:</td>
					<td class=\"_field\" id=\"capa_fecha\">
					<input onclick=\"$('#xajax_fecha_venta_placa_calendario').show();\" name=\"xajax_fecha_venta_placa\" id=\"xajax_fecha_venta_placa\" type=\"text\" style=\"width:80%;\" readonly=\"readonly\" />
					<img onclick=\"$('#xajax_fecha_venta_placa_calendario').show();\" border\"0\" src=\"".getUrl('img/iconos/select_date.png')."\" id=\"fecha_venta_calendario\" />
					<div id=\"xajax_fecha_venta_placa_calendario\" style=\"display:none;\" ></div>
					</td>
					<td class=\"_label\">A&ntilde;o:</td>
					<td class=\"_field\" id=\"capa_ano\">&nbsp;</td>
				</tr>
				<tr>
					<td class=\"_label\">Combustible:</td>
					<td class=\"_field\" id=\"capa_combustible\">&nbsp;</td>
					<td class=\"_label\">Transmisi&oacute;n:</td>
					<td class=\"_field\" id=\"capa_transmision\">&nbsp;</td>
				</tr>
				<tr id=\"_xajax_error\" class=\"_xajax_error\">
					<td colspan=\"2\" ><div id=\"_xajax_leyend\" class=\"inputNOTNULL\"></div> Valor Requerido</td>
					<td colspan=\"2\" ><div id=\"_xajax_leyend\" class=\"inputERROR\"></div> Valor Incorrecto</td>
				</tr>
				<tr>
					<td colspan=\"4\" align=\"center\">".getButton('button',imageTag(getUrl("img/iconos/save.png"))."Guardar",
					array("onclick"=>"xajax_save_placa(xajax.getFormValues('xajax_placa_form'),'".$php_callback_func."');"))
					.getButton('button',imageTag(getUrl("img/iconos/delete.png"))."Cancelar",
					array("onclick"=>"$('#xajax_client_div').fadeOut();"))."
					</td>
				</tr>
			</table>".endForm();
			$script='var fdiv = obj("xajax_client_div");
					if(fdiv==null){
						fdiv=document.createElement("div");
						fdiv.setAttribute("id","xajax_client_div");			
					}
					fdiv.style.position="absolute";
					fdiv.style.zIndex="1";
					fdiv.className="xajax_client_div";
					fdiv.style.display="inline";
					var parente = document.getElementById("'.$parent.'");
					parente.appendChild(fdiv);
					';
					
			$r->script($script);
			$r->assign('xajax_client_div','innerHTML',$html);
			$r->script('setWindow("xajax_client_div","xajax_client_div_bar",true);
					obj("xajax_client_div_ci").focus();');
			$r->script('
			var xajax_placa_callback = function(calendar){
				if (calendar.dateClicked) {
					var y = calendar.date.getFullYear();
					var m = calendar.date.getMonth()+1;
					var d = calendar.date.getDate();
					if(m<10){
						m="0"+m;
					}
					if(d<10){
						d="0"+d;
					}
					obj("xajax_fecha_venta_placa").value=d+"-"+m+"-"+y;
					$("#xajax_fecha_venta_placa_calendario").hide();
				}
			};
			
			Calendar.setup({
					flat : "xajax_fecha_venta_placa_calendario",
					ifFormat : "%d-%m-%Y",
					flatCallback : xajax_placa_callback
					});');
			//$r->script('setWindow("xajax_client_div","xajax_client_div");');
		}
                
		return $r;
	}
	
	function _buscar_placa($busqueda,connection $c,$unico=''){
		//busqueda por c�dula
		if($busqueda==""){
			return null;
		}
		
		$q=new query($c);
		$busqueda=$c->excape($busqueda);
		$placas=$c->en_registro_placas;
		$j=getJoinPlaca($c);//sin unidad basica asociada
		$q->add($j);//$q->add($placas);//usar j si quieres join
		$q->where(new criteria(' like ',$placas->placa,"'%".$busqueda."%'"));
		if($unico!=''){
			//$q->setLimit(1);
			
			$q->where(new criteria(sqlEQUAL,'id_registro_placas',$unico));
		}
                
		$rec=$q->doSelect();
		//return ($rec==null);
		if($rec!=null){
			return $rec;
		}else{
			return null;
		}
	}
	
	function getJoinPlaca($c){
		$placas=$c->en_registro_placas;
		return $j=$placas->join($c->sa_v_unidad_basica,$placas->id_unidad_basica,$c->sa_v_unidad_basica->id_unidad_basica," LEFT JOIN ");
	}
	
	function save_placa($form,$php_callback_func=null){
		$r=getResponse();
		$c= new connection();
		$c->open();
		
		
		//si se ha definido un chasis igual, buscar coincidencias:
		if(isset($form['xajax_placa_div_chasis'])){
			//buscando el chasis:
                    if($form['xajax_placa_div_chasis']==''){
			$form['xajax_placa_div_chasis']=NULL;
                    }else{
                    
                
			$recchasisq=$c->getQuery(getJoinPlaca($c),new criteria(sqlEQUAL,$c->en_registro_placas->chasis,"'".$form['xajax_placa_div_chasis']."'"));//$c->en_registro_placas->doSelect($c, new criteria(sqlEQUAL,$c->en_registro_placas->chasis,"'".$form['xajax_placa_div_chasis']."'"));
			$recchasis=$recchasisq->doSelect();
			if($recchasis){
				if($recchasis->getNumRows()==1){
					//se ha conseguido el veh�culo:
					$r->script('_alert("El Chasis '.$form['xajax_placa_div_chasis'].' coincide con un registro anterior, cuya placa es: '.$recchasis->placa.', debe dirigirse al registro de veh&iacute;culos para cambiar la placa.");');
					$r->loadCommands(call_user_func($php_callback_func,$recchasis));
					//$r->script('$("#xajax_client_div").fadeOut();');
					return $r;
				}
			}
		}
                }
		
		$c->begin();
		
		
		//creando la tabla para insercion
		$placa = new table("en_registro_placas");//xajax_client_div_ci
		$placa->add(new field("id_registro_placas",'',field::tInt,null,true));
		//$placa->add(new field("id_modelo",'',field::tInt,$form['xajax_placa_div_id_modelo'],true));
		$placa->add(new field("chasis",'',field::tString,$form['xajax_placa_div_chasis'],false));
		$placa->add(new field("id_unidad_basica",'',field::tInt,$form['xajax_placa_div_id_unidad_basica'],true));
		$placa->add(new field("placa",'',field::tString,$form['xajax_placa_div_placa'],true));
		//$placa->add(new field("kilometraje",'',field::tInt,$form['xajax_placa_div_kilometraje'],true));//
//CAMBIO 08_03_2010 NO REQUERIDO PLACA
        $placa->add(new field("kilometraje",'',field::tInt,0,false));
		//$placa->add(new field("color",'',field::tString,$form['xajax_placa_div_color'],false));
		if($form['xajax_fecha_venta_placa']!=''){
			$placa->add(new field("fecha_venta",'',field::tDate,$form['xajax_fecha_venta_placa'],false));
		}
		//$placa->add(new field("id_transmision",'',field::tInt,$form['xajax_placa_div_id_transmision'],true));
		//$placa->add(new field("id_combustible",'',field::tInt,$form['xajax_placa_div_id_combustible'],true));
		//$placa->add(new field("ano",'',field::tInt,$form['xajax_placa_div_ano'],true));
		$placa->add(new field("id_empresa",'',field::tInt,$_SESSION['session_empresa'],true));
		if($form['xajax_placa_div_chasis']==''){
			$placa->add(new field("parcial",'',field::tInt,1,true));
		}
	
		$r->script("obj('_xajax_error').style.visibility ='hidden';");
		$campos=$placa->getAttributes();
		foreach($campos as $f){
			$r->script('obj("xajax_placa_div_'.$f->getName().'").className="";');
		}
		$result=$placa->doInsert($c,$placa->id_registro_placas);
		if($result===true){
			$nplaca= $c->soLastInsertId();
			$c->commit();
			$r->alert("El nuevo vehiculo ha sido agregado.");
			if(function_exists($php_callback_func)){
				$q=$c->getQuery(getJoinPlaca($c),new criteria(sqlEQUAL,$c->en_registro_placas->id_registro_placas,$nplaca));
				
				//$r->loadCommands(call_user_func($php_callback_func,$q->doSelect()));
				$r->loadCommands(call_user_func($php_callback_func,$q->doSelect()));
				$r->script('$("#xajax_client_div").fadeOut();');
			}
		}else{
			
			foreach($result as $ex){
				if($ex->type==errorMessage::errorNOTNULL){					
					$r->script('obj("xajax_placa_div_'.$ex->getObject()->getName().'").className="inputNOTNULL";');
				}elseif($ex->type==errorMessage::errorType){
					$r->script('obj("xajax_placa_div_'.$ex->getObject()->getName().'").className="inputERROR";');
				}else{
					if($ex->numero==connection::errorUnikeKey){
						$r->alert('Placa o Chasis Duplicado');
						return $r;
					}else{
						$r->alert($ex->getMessage());
					}
				}
				//$r->alert($ex->getObject()->getName().' '.$ex->getMessage());
			}
			$r->script('obj("xajax_placa_div_id_marca").className="";');
			if($form['xajax_placa_div_id_unidad_basica']==null){
				$r->alert('No se ha seleccionado la unidad');
				$r->script('obj("xajax_placa_div_id_marca").className="inputNOTNULL";');
			}
			$r->script("obj('_xajax_error').style.visibility ='visible';_alert('Revise los datos introducidos para completar la operaci&oacute;n')");
		}
		return $r;
	}
	
	function includeXajaxBuscarPlaca(){
		//echo '<link rel="stylesheet" type="text/css" href="control/css/xajax_client_div.css" />';
	}
	
	function cargar_modelo($id_marca){
		$r=getResponse();
		$c=new connection();
		$c->open();
		if($id_marca==''){
			$modelos=null;
		}else{
			$modelos=$c->an_modelo->doSelect($c,new criteria(sqlEQUAL,$c->an_modelo->id_marca,$id_marca))->getAssoc($c->an_modelo->id_modelo,$c->an_modelo->nom_modelo);
		}
			//$versiones=$c->an_version->doSelect($c)->getAssoc($c->an_version->id_version,$c->an_version->nom_version);
		$select=inputSelect('xajax_placa_div_id_modelo',$modelos,null,array('onchange'=>'xajax_cargar_version(this.value);'));
		$r->assign('capa_xajax_placa_div_id_modelo','innerHTML',$select);
		$r->assign('capa_xajax_placa_div_id_unidad_basica','innerHTML','&nbsp;');
		$r->assign('capa_xajax_placa_div_id_version','innerHTML','&nbsp;');
		$r->assign('capa_ano','innerHTML','&nbsp;');
		$r->assign('capa_transmision','innerHTML','&nbsp;');
		$r->assign('capa_combustible','innerHTML','&nbsp;');
		$c->close();
		return $r;
	}
	
	function cargar_version($id_modelo){
		$r=getResponse();
		$c=new connection();
		$c->open();
		if($id_modelo==''){
			$versiones=null;
		}else{
			$versiones=$c->an_version->doSelect($c,new criteria(sqlEQUAL,$c->an_version->id_modelo,$id_modelo))->getAssoc($c->an_version->id_version,$c->an_version->nom_version);
		}
			//$versiones=$c->an_version->doSelect($c)->getAssoc($c->an_version->id_version,$c->an_version->nom_version);
		$select=inputSelect('xajax_placa_div_id_version',$versiones,null,array('onchange'=>'xajax_cargar_unidad(this.value);'));
		$r->assign('capa_xajax_placa_div_id_version','innerHTML',$select);
		$r->assign('capa_xajax_placa_div_id_unidad_basica','innerHTML','&nbsp;');
		$r->assign('capa_ano','innerHTML','&nbsp;');
		$r->assign('capa_transmision','innerHTML','&nbsp;');
		$r->assign('capa_combustible','innerHTML','&nbsp;');
		$c->close();
		return $r;
	}
	
	function cargar_unidad($id_version){
		$r=getResponse();
		$c=new connection();
		$c->open();
		if($id_version==''){
			$unidades=null;
		}else{
			///////////////////////
			$query = $c->sa_v_unidad_basica;
			$union = $c->sa_unidad_empresa->join($query,$c->sa_unidad_empresa->id_unidad_basica, $query->id_unidad_basica);
			$query2 = new query($c);
		    $query2->add($union);
			$query2->where(new criteria(sqlEQUAL,$c->sa_v_unidad_basica->id_version,$id_version));
		    $query2->where(new criteria(sqlEQUAL,$c->sa_unidad_empresa->id_empresa,$_SESSION['idEmpresaUsuarioSysGts']));
			
			$unidades=$query2->doSelect($c,new criteria(sqlEQUAL,$c->sa_v_unidad_basica->id_version,$id_version))->getAssoc($c->sa_v_unidad_basica->id_unidad_basica,$c->sa_v_unidad_basica->nombre_unidad_basica);
			/////////////////////
			
			//$unidades=$c->sa_v_unidad_basica->doSelect($c,new criteria(sqlEQUAL,$c->sa_v_unidad_basica->id_version,$id_version))->getAssoc($c->sa_v_unidad_basica->id_unidad_basica,$c->sa_v_unidad_basica->nombre_unidad_basica);//original
		}
			//$versiones=$c->an_version->doSelect($c)->getAssoc($c->an_version->id_version,$c->an_version->nom_version);
		$select=inputSelect('xajax_placa_div_id_unidad_basica',$unidades,null,array('onchange'=>'xajax_cargar_datos_unidad(this.value);'));
		$r->assign('capa_xajax_placa_div_id_unidad_basica','innerHTML',$select);
		$r->assign('capa_ano','innerHTML','&nbsp;');
		$r->assign('capa_transmision','innerHTML','&nbsp;');
		$r->assign('capa_combustible','innerHTML','&nbsp;');
		$c->close();
		return $r;
	}
	
	
	function cargar_datos_unidad($id_unidad_basica){
		$r=getResponse();
		if($id_unidad_basica==''){
			$ano='&nbsp;';
			$transmision='&nbsp;';
			$combustible='&nbsp;';
		}else{
			$c=new connection();
			$c->open();
			$rec=$c->sa_v_unidad_basica->doSelect($c,new criteria(sqlEQUAL,$c->sa_v_unidad_basica->id_unidad_basica,$id_unidad_basica));
			
			$ano=$rec->ano;
			$transmision=$rec->nom_transmision;
			$combustible=$rec->nom_combustible;
			$c->close();
		}
		$r->assign('capa_ano','innerHTML',$ano);
		$r->assign('capa_transmision','innerHTML',$transmision);
		$r->assign('capa_combustible','innerHTML',$combustible);
		return $r;
	}

	xajaxRegister('buscarPlaca');
	xajaxRegister('save_placa');
	xajaxRegister('cargar_modelo');
	xajaxRegister('cargar_version');
	xajaxRegister('cargar_unidad');
	xajaxRegister('cargar_datos_unidad');
?>