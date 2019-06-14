<?php
	require_once("iforms.inc.php");
	function buscarCliente($busqueda,$parent,$php_callback_func='cargar_cliente',$unico=''){
		global $titleFormatoCI;
		global $spanCI;
		global $spanRIF;
		
		$iconoFormato = '<img style="vertical-align:middle;" src="../img/iconos/ico_pregunta.gif" title="Formato para busquedas exactas Ej.: '.$titleFormatoCI.'"/>';
		
		$r=getResponse();
		$c=new connection();
		$c->open();
		$rec=_buscar_cliente($busqueda,$c,$unico);
		//$r->alert($rec);return $r;
		//si no lo encuentra
		//$r->alert($rec);return $r;
		if($rec->getNumRows()==1){	//si lo encuentra
			if($rec->status != "Activo"){
				return $r->alert("El cliente se encuentra como 'Inactivo', debe activarlo.");
			}
			
			$query = sprintf("SELECT * FROM cj_cc_cliente_empresa WHERE id_cliente = %s AND id_empresa = %s LIMIT 1",
							valTpDato($rec->id, "int"),
							valTpDato($_SESSION["idEmpresaUsuarioSysGts"], "int"));
			$rs = mysql_query($query);
			if(!$rs) { return $r->alert(mysql_error()."\n\nLine: ".__LINE__); }
			$tieneEstaEmpresa = mysql_num_rows($rs);
			
			if(!$tieneEstaEmpresa){
				return $r->alert("El cliente no tiene asignada la empresa actual");
			}
			
			if(function_exists($php_callback_func)){
				$r->loadCommands(call_user_func($php_callback_func,$rec));
			}
		}else{
			//vaciando las dem�s coincidencias
			if($rec->getNumRows()==0){
				$tc='<tr><td>No se han encontrado coincidencias de: <em>'.$busqueda.'</em></td></tr>';
			}else{
				$par='_impar';
				$tc='<table class="xajax_client_div_window" style="border-collapse:collapse;width:96%;margin:auto;"><tr><td colspan="3">Se han encontrado '.$rec->getNumRows().' coincidencias de: <em>'.$busqueda.'</em> &nbsp;&nbsp;<rojo style="color:red">S/E:</rojo> Sin Empresa Asignada</td></tr>';
				foreach($rec as $reg){
					//valido que el cliente tenga la empresa actual que registra la cita, sino no lo podra agregar hasta que lo active
					$query = sprintf("SELECT * FROM cj_cc_cliente_empresa WHERE id_cliente = %s AND id_empresa = %s LIMIT 1",
									valTpDato($reg->id, "int"),
									valTpDato($_SESSION["idEmpresaUsuarioSysGts"], "int"));
					$rs = mysql_query($query);
					if(!$rs) { return $r->alert(mysql_error()."\n\nLine: ".__LINE__); }
					$tieneEstaEmpresa = mysql_num_rows($rs);
					
					if($tieneEstaEmpresa){
						$imagenAgregarCliente = imageTag(getUrl('img/iconos/select.png')); // es la imagen check en verde
						$funcionDeBusquedaCliente =  sprintf('onclick="xajax_buscarCliente(\'%s\',\'%s\',\'%s\','.$reg->id.'); $(\'#xajax_client_div\').fadeOut();"',
														$reg->lci."-".$reg->ci,
														$parent,
														$php_callback_func);
						$cursor = "";
					}else{
						$imagenAgregarCliente = "<rojo style='color:red'>S/E</rojo>";
						$funcionDeBusquedaCliente = "";
						$cursor = 'style="cursor:not-allowed;"';
					}
					
                                        
                                        if($reg->lci == NULL || $reg->lci == ""){
                                            $cedulaMostrarListado = $reg->ci;
                                        }else{
                                            $cedulaMostrarListado = $reg->lci."-".$reg->ci;
                                        }
					$tc.=sprintf('<tr '.$cursor.' '.$funcionDeBusquedaCliente.' class="xajax_tr%s"><td>'.$imagenAgregarCliente.'</td><td>%s</td><td>%s</td></tr>',
									$par,
									$cedulaMostrarListado,
									$reg->nombre.' '.$reg->apellido);
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
					<td class=\"_label\">Tipo:*</td>
					<td class=\"_field\">". /*ya no va inputSelect('xajax_client_div_lci',array('V'=>'V','E'=>'E','J'=>'J','G'=>'G','D'=>'D'),'V',array('onclick'=>'if(this.value==\'J\' || this.value==\'G\' || this.value==\'D\'){$(\'#xajax_client_div_apellido\').fadeOut();}else{$(\'#xajax_client_div_apellido\').fadeIn();}'))*/ 
									inputSelect('xajax_client_div_tipo',array('Natural' => 'Natural', 'Juridico' => 'Juridico')) ."</td>
					<td class=\"_label\">".$spanCI."/".$spanRIF.":* ".$iconoFormato."</td>
					<td class=\"_field\">".inputTag('text','xajax_client_div_ci',$busqueda,array('title'=>'Formato: '.$titleFormatoCI, 'maxlength'=>'18'))."</td>
				</tr>
				<tr>
					<td class=\"_label\">Nombre:*</td>
					<td class=\"_field\">".inputTag('text','xajax_client_div_nombre')."</td>
					<td class=\"_label\">Apellido:*</td>
					<td class=\"_field\">".inputTag('text','xajax_client_div_apellido')."</td>
				</tr>
				<tr>
					<td class=\"_label\">Tel&eacute;fono:*</td>
					<td class=\"_field\">".inputTag('text','xajax_client_div_telf','',array('class'=>'inputInicial'))."</td>
					<td class=\"_label\">Celular:*</td>
					<td class=\"_field\">".inputTag('text','xajax_client_div_otrotelf','',array('class'=>'inputInicial'))."</td>
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
				<td colspan=\"4\">				
					<div class=\"form_info\">
						<table class=\"form_info_tag\">
							<tr>
								<td>Formato Tel&eacute;fonos</td>
								<td>000-0000, +000-0000-0000000, 0000-0000000</td>
							</tr>
						</table>
					</div>
				</td>
				</tr>
				<tr>
					<td colspan=\"4\" align=\"center\">".getButton('button',imageTag(getUrl("img/iconos/save.png"))."Guardar",
					array("onclick"=>"if(validarTelfono()){ xajax_save_client(xajax.getFormValues('xajax_client_form'),'".$php_callback_func."'); }"))
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
			
			
			//$r->script('$("#xajax_client_div_telf").maskInput("9999-9999999",{placeholder:" "});');		
			//$r->script('$("#xajax_client_div_otrotelf").maskInput("9999-9999999",{placeholder:" "});');
			//$r->script('setWindow("xajax_client_div","xajax_client_div");');
		}
		return $r;
	}
	
	function _buscar_cliente($busqueda,connection $c,$unico=''){
		//busqueda por c�dula
		if($busqueda==""){
			return null;
		}
		//$busqueda=$c->excape($busqueda);
		$cliente=$c->cj_cc_cliente;
		//aqui si quiero filtrar por clientes, faltaria agregar la empresa
		$q=$c->getQuery($cliente);
																		//cambiado gregor para busquedas con guion o sin guion v-19288431
		
               
		if($unico!=''){
			//$q->setLimit(1);
			$q->where(new criteria(sqlEQUAL,'id',$unico));
		}else{
                    $q->where(new criteria(sqlOR, array(new criteria(' like ',new _function('concat_WS',array("''",$cliente->lci,$cliente->ci)),"'%".$busqueda."%'"), 
                            new criteria(' like ',new _function('concat_WS',array("'-'",$cliente->lci,$cliente->ci)),"'%".$busqueda."%'"),
                            new criteria(' like ',new _function('concat_WS',array("' '",$cliente->lci,$cliente->ci)),"'%".$busqueda."%'")
                                )));
                }
                
                
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
		
		global $arrayValidarCI;
		global $arrayValidarRIF;
		
		if($form['xajax_client_div_ci']==''){
			$r->script("byId('xajax_client_div_ci').className = 'inputNOTNULL'");
			$r->alert('No ha introducido datos importantes');
			return $r;
		}
			
		$c= new connection();
		$c->open();
	
		if($form['xajax_client_div_tipo'] == "Natural"){
			$arrayValidar = $arrayValidarCI;
		}elseif($form['xajax_client_div_tipo'] == "Juridico"){
			$arrayValidar = $arrayValidarRIF;
		}
		
		if (isset($arrayValidar)) {
			$valido = false;
			foreach ($arrayValidar as $indice => $valor) {
				if (preg_match($valor, strtoupper($form['xajax_client_div_ci']))) {
					$valido = true;
				}
			}
			
			if ($valido == false) {
				$r->script("byId('xajax_client_div_ci').className = 'inputERROR'");
				return $r->alert("Los campos señalados son requeridos, o no cumplen con el formato establecido");
			}
		}
		
		$r->script("byId('xajax_client_div_ci').className = ''");
		
		$txtCiCliente = explode("-",strtoupper($form['xajax_client_div_ci']));
		if (is_numeric($txtCiCliente[0]) == true) {
			$txtCiCliente = implode("-",$txtCiCliente);
		} else {
			$txtLciCliente = $txtCiCliente[0];
			array_shift($txtCiCliente);
			$txtCiCliente = implode("-",$txtCiCliente);
		}
		
                // VERIFICA QUE NO EXISTA LA CEDULA
                $query = sprintf("SELECT * FROM cj_cc_cliente
                WHERE ((lci IS NULL AND %s IS NULL AND ci LIKE %s)
                        OR (lci IS NOT NULL AND lci LIKE %s AND ci LIKE %s))
                       ",
                        valTpDato($txtLciCliente, "text"),
                        valTpDato($txtCiCliente, "text"),
                        valTpDato($txtLciCliente, "text"),
                        valTpDato($txtCiCliente, "text")
                        );
                $rs = mysql_query($query);
                if (!$rs) { return $r->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
                $totalRows = mysql_num_rows($rs);

                if ($totalRows > 0) {
                        return $r->alert("Ya existe el Nro de documento ingresado");
                }

                //ANTES GREGOR
		//compruebo si ya existe en el registro de clientes para devolver informacion de duplicado GREGOR
		/*$sql = sprintf("SELECT * FROM cj_cc_cliente WHERE lci = %s AND ci = %s",
						  valTpDato($txtLciCliente, "text"),
						  valTpDato($txtCiCliente, "text"));
		$query = mysql_query($sql);
		if (!$query) { return $r->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		if(mysql_num_rows($query) != 0) { return $r->alert("El cliente ya existe"); }
                 */

		
		$c->begin();
		//creando la tabla para insercion
		$cliente = new table("cj_cc_cliente");//xajax_client_div_ci
		$cliente->add(new field("id",'',field::tInt,null,true));    
					
        $cliente->add(new field("tipo",'',field::tString,"Juridico",true));
        $cliente->add(new field("credito",'',field::tString,"no",true));
        $cliente->add(new field("tipocliente",'',field::tString,"Servicios",true));
		$cliente->add(new field("tipo",'',field::tString,$form['xajax_client_div_tipo'],true));
		
                $txtLciCliente = valTpDato($txtLciCliente, "text");
                
                if($txtLciCliente != NULL && $txtLciCliente != ""){
                    $cliente->add(new field("lci",'',field::tString,str_replace("'", "", $txtLciCliente),false));
                }
                
		$cliente->add(new field("ci",'',field::tString,$txtCiCliente,true));		
		$cliente->add(new field("nombre",'',field::tString,$form['xajax_client_div_nombre'],true));
		$cliente->add(new field("telf",'',field::tString,$form['xajax_client_div_telf'],true));
		$cliente->add(new field("otrotelf",'',field::tString,$form['xajax_client_div_otrotelf'],true));
		$cliente->add(new field("ciudad",'',field::tString,$form['xajax_client_div_ciudad'],true));
		$cliente->add(new field("correo",'',field::tEmail,$form['xajax_client_div_correo'],true));
		$cliente->add(new field("reputacionCliente",'',field::tString,'CLIENTE B',false));
		$cliente->add(new field('fcreacion','',null,new _function('CURRENT_DATE',null),true));
		
		$cliente->add(new field("apellido",'',field::tString,$form['xajax_client_div_apellido'],false));
		
		$r->script("obj('_xajax_error').style.visibility ='hidden';");
		$campos=$cliente->getAttributes();
		foreach($campos as $f){
			$r->script('obj("xajax_client_div_'.$f->getName().'").className="";');
		}
		$cliente->add(new field('status','',field::tString,'Inactivo'));
		
		
		$result=$cliente->doInsert($c,$cliente->id);
		if($result===true){
			$ncliente= $c->soLastInsertId();
                        
						$id_empresa_usuario = $_SESSION["idEmpresaUsuarioSysGts"];
                        //GUARDADO EN CLIENTE-EMPRESA
						
						//$tieneEmpresa=$c->getQuery($c->cj_cc_cliente_empresa,new criteria(sqlEQUAL,$c->cj_cc_cliente_empresa->id_cliente,2), new criteria(sqlEQUAL,$c->cj_cc_cliente_empresa->id_empresa,42))->doSelect();
						
			$tieneEmpresa=$c->getQuery($c->cj_cc_cliente_empresa, new criteria(sqlAND,
                                                                                            new criteria(sqlEQUAL,$c->cj_cc_cliente_empresa->id_cliente,$ncliente),
                                                                                            new criteria(sqlEQUAL,$c->cj_cc_cliente_empresa->id_empresa,$id_empresa_usuario)
                                                                                           ))->doSelect();						
						if($tieneEmpresa->id_cliente){
							//$tiene = "si";
						}else{
							//$tiene = "no";
							
                                                    $cliente_empresa = new table("cj_cc_cliente_empresa");
                                                    $cliente_empresa->add(new field("id_cliente_empresa",'',field::tInt,null,true)); 
                                                    $cliente_empresa->add(new field("id_cliente",'',field::tInt,$ncliente,true));
                                                    $cliente_empresa->add(new field("id_empresa",'',field::tInt,$id_empresa_usuario,true));
                                                    $cliente_empresa->doInsert($c,$cliente_empresa->id_cliente_empresa);
							
						}
						//return $r->alert($tiene);
                        //Fin guardado
                        
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
		echo '<link rel="stylesheet" type="text/css" href="control/css/xajax_client_div.css" />';
	}

	xajaxRegister('buscarCliente');
	xajaxRegister('save_client');
?>