<?php
	require_once("iforms.inc.php");
	function buscarCliente($busqueda,$parent,$php_callback_func='cargar_cliente'){
		$r=getResponse();
		$c=new connection();
		$c->open();
		$rec=_buscar_cliente($busqueda,$c);
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
					$tc.=sprintf('<tr onclick="xajax_buscarCliente(\'%s\',\'%s\',\'%s\');$(\'#xajax_client_div\').fadeOut();" class="xajax_tr%s"><td>'.imageTag(getUrl('img/iconos/select.png')).'</td><td>%s</td><td>%s</td></tr>',
					$reg->lci.$reg->ci,$parent,$php_callback_func,$par,$reg->lci.$reg->ci,$reg->nombre.' '.$reg->apellido);
					$par = ($par!='') ? '' : '_impar';
				}
				$tc.='</table>';
			}
			$html='<table class="xajax_client_div_window" style="border-collapse:collapse;">
				<caption id="xajax_client_div_bar" class="xajax_client_div_bar">Coincidencias de Clientes</caption>
			</table><div class="_xajax_overflow">'.$tc.'</div>';
			$intFormat=array('onkeypress'=>'return inputInt(event);');
			
			$html.=startForm('xajax_client_form','',array('onsubmit'=>'return false;')).
			"<table class=\"xajax_client_div_window\">
				<caption id=\"\">Cargar nuevo Cliente</caption>
				<tr>
					<td class=\"_label\">Tipo Documento:*</td>
					<td class=\"_field\">".inputSelect('xajax_client_div_lci',array('V'=>'V','E'=>'E','J'=>'J','G'=>'G','D'=>'D'),'V',array('onclick'=>'if(this.value==\'J\' || this.value==\'G\' || this.value==\'D\'){$(\'#xajax_client_div_apellido\').fadeOut();}else{$(\'#xajax_client_div_apellido\').fadeIn();}'))."</td>
					<td class=\"_label\">C&eacute;dula/Rif:*</td>
					<td class=\"_field\">".inputTag('text','xajax_client_div_ci',number_format($busqueda),$intFormat)."</td>
				</tr>
				<tr>
					<td class=\"_label\">Nombre:*</td>
					<td class=\"_field\">".inputTag('text','xajax_client_div_nombre')."</td>
					<td class=\"_label\">Apellido:*</td>
					<td class=\"_field\">".inputTag('text','xajax_client_div_apellido')."</td>
				</tr>
				<tr>
					<td class=\"_label\">Tel&eacute;fono:*</td>
					<td class=\"_field\">".inputTag('text','xajax_client_div_telf','',array('title'=>'Formato: (####-)###-####'))."</td>
					<td class=\"_label\">Celular:*</td>
					<td class=\"_field\">".inputTag('text','xajax_client_div_otrotelf','',array('title'=>'Formato: (####-)###-####'))."</td>
				</tr>
				<tr>
					<td class=\"_label\">Ciudad:*</td>
					<td class=\"_field\">".inputTag('text','xajax_client_div_ciudad')."</td>
					<td class=\"_label\">Email:*</td>
					<td class=\"_field\">".inputTag('text','xajax_client_div_correo')."</td>
				</tr>
				<tr id=\"_xajax_error\" class=\"_xajax_error\">
					<td colspan=\"2\" ><div id=\"_xajax_leyend\" class=\"inputNOTNULL\"></div> Valor Requerido</td>
					<td colspan=\"2\" ><div id=\"_xajax_leyend\" class=\"inputERROR\"></div> Valor Incorrecto</td>
				</tr>
				<tr>
					<td colspan=\"4\" align=\"center\">".getButton('button',imageTag(getUrl("img/iconos/save.png"))."Guardar",
					array("onclick"=>"xajax_save_client(xajax.getFormValues('xajax_client_form'),'".$php_callback_func."');"))
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
					parente.appendChild(fdiv);';
					
			$r->script($script);
			$r->assign('xajax_client_div','innerHTML',$html);
			$r->script('setWindow("xajax_client_div","xajax_client_div_bar",true);
					obj("xajax_client_div_ci").focus();');
			//$r->script('setWindow("xajax_client_div","xajax_client_div");');
		}
		return $r;
	}
	
	function _buscar_cliente($busqueda,connection $c){
		//busqueda por c�dula
		if($busqueda==""){
			return null;
		}
		$busqueda=$c->excape($busqueda);
		$cliente=$c->cj_cc_cliente;
		$q=$c->getQuery($cliente);
		$q->where(new criteria(' like ',new _function('concat',array($cliente->lci,$cliente->ci)),"'%".$busqueda."%'"));
		//return $q->getSelect();
		$rec=$q->doSelect();
		//return ($rec==null);
		if($rec!=null){
			return $rec;
		}else{
			return null;
		}
	}
	
	function save_client($form,$php_callback_func=null){
		$r=getResponse();
		
		if($form['xajax_client_div_ci']==''){
			$r->alert('No ha introducido datos');
			return $r;
		}
		$c= new connection();
		$c->open();
		//verifica si el cliente ya existe
		$cedula=field::getTransformType($form['xajax_client_div_ci'],field::tInt);
		if(!is_numeric($cedula)){
			$r->script('_alert("C&eacute;dula/Rif incorrecto");');
			return $r;
		}
		$reco=$c->getQuery($c->cj_cc_cliente,new criteria(sqlEQUAL,$c->cj_cc_cliente->ci,$cedula))->doSelect();
		if($reco->getNumRows()!=0){
			$r->script('_alert("El N&uacute;mero del documento que introdujo ya existe");');
			return $r;
		}
		$c->begin();
		//creando la tabla para insercion
		$cliente = new table("cj_cc_cliente");//xajax_client_div_ci
		$cliente->add(new field("id",'',field::tInt,null,true));
		$cliente->add(new field("lci",'',field::tString,$form['xajax_client_div_lci'],true));		
		$cliente->add(new field("ci",'',field::tInt,$form['xajax_client_div_ci'],true));		
		$cliente->add(new field("nombre",'',field::tString,$form['xajax_client_div_nombre'],true));
		$cliente->add(new field("telf",'',field::tPhone,$form['xajax_client_div_telf'],true));
		$cliente->add(new field("otrotelf",'',field::tPhone,$form['xajax_client_div_otrotelf'],true));
		$cliente->add(new field("ciudad",'',field::tString,$form['xajax_client_div_ciudad'],true));
		$cliente->add(new field("correo",'',field::tEmail,$form['xajax_client_div_correo'],true));
		
		if(($form['xajax_client_div_lci']=='V')||($form['xajax_client_div_lci']=='E')){
			$cliente->add(new field("apellido",'',field::tString,$form['xajax_client_div_apellido'],true));
		}else{
			$r->script('obj("xajax_client_div_apellido").className="";');
		}
		$r->script("obj('_xajax_error').style.visibility ='hidden';");
		$campos=$cliente->getAttributes();
		foreach($campos as $f){
			$r->script('obj("xajax_client_div_'.$f->getName().'").className="";');
		}
		$cliente->add(new field('status','',field::tString,'Inactivo'));
		
		
		$result=$cliente->doInsert($c,$cliente->id);
		if($result===true){
			$ncliente= $c->soLastInsertId();
			$c->commit();
			$r->alert("El nuevo cliente ha sido agregado.");
			if(function_exists($php_callback_func)){
				$q=$c->getQuery($c->cj_cc_cliente,new criteria(sqlEQUAL,$c->cj_cc_cliente->id,$ncliente));
				
				//$r->loadCommands(call_user_func($php_callback_func,$q->doSelect()));
				$r->loadCommands(call_user_func($php_callback_func,$q->doSelect()));
				$r->script('$("#xajax_client_div").fadeOut();');
			}
		}else{
			foreach($result as $ex){
				if($ex->type==errorMessage::errorNOTNULL){					
					$r->script('obj("xajax_client_div_'.$ex->getObject()->getName().'").className="inputNOTNULL";');
				}elseif($ex->type==errorMessage::errorType){
					//$r->alert($ex->getMessage());
					$r->script('obj("xajax_client_div_'.$ex->getObject()->getName().'").className="inputERROR";');
				}else{
					$r->alert($ex->getMessage());
				}
			}
			$r->script("obj('_xajax_error').style.visibility ='visible';_alert('Revise los datos introducidos para completar la operaci&oacute;n')");
		}
		return $r;
	}
	
	function includeXajaxBuscarCliente(){
		echo '<link rel="stylesheet" type="text/css" href="../control/css/xajax_client_div.css" />';
	}

	xajaxRegister('buscarCliente');
	xajaxRegister('save_client');
?>