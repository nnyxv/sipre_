<?php
//para incluir y sólo busca CLIENTES
	
	function dialogo_cliente($page,$maxrows,$order,$ordertype,$capa,$args=''){//$busqueda, $callback, $parent=''
		$r = getResponse();
		
		if($capa==''){
			$capa='xajax_dialogo_cliente';
		}
		$argumentos=paginator::getExplodeArgs($args);
		//$r->alert($args);
		//$argumentos['busqueda']=str_replace('-','',$argumentos['busqueda']);
		$bus="'%".$argumentos['busqueda']."%'";
		$c= new connection();
		$c->open();
		$insertmode=($argumentos['insertmode']!='');
		$cliente=$c->cj_cc_cliente;
		$q= new query($c);
		$q->add($cliente);
		
		$q->where(
			new criteria(sqlOR,array(
				new criteria(' like ',$cliente->ci,$bus),
				new criteria(' like ',$cliente->lci,$bus),
				new criteria(' like ',$cliente->nombre,$bus),
				new criteria(' like ',$cliente->apellido,$bus),
				new criteria(' like ',new _function('concat_ws',array("'-'",$cliente->lci,$cliente->ci)),$bus)
			)));
		/*$q->orderBy($cliente->ci)->
			orderBy($cliente->lci)->
			orderBy($cliente->apellido)->
			orderBy($cliente->nombre);*/
		
		
		$paginator = new paginator('xajax_dialogo_cliente',$capa,$q,$maxrows);
		$rec=$paginator->run($page,$order,$ordertype,$args);
		
		if($rec){
			$nargs=paginator::getArgs(array(
				'callback'=>$argumentos['callback'],
				'parent'=>$argumentos['parent'],
				'insertmode'=>$argumentos['insertmode']
			));
			//$r->alert($nargs);
			//$r->alert(utf8_encode($rec));
			$func='xajax_dialogo_cliente(0,'.$maxrows.',\''.$order.'\',\''.$ordertype.'\',\''.$capa.'\',\''.$nargs.',busqueda=\'+obj(\'xajax_dialogo_cliente_b\').value);';
			$rfunc='xajax_dialogo_cliente(0,'.$maxrows.',\''.$order.'\',\''.$ordertype.'\',\''.$capa.'\',\''.$nargs.',busqueda=\');';
			$html='
			<div>
				<input type="text" id="xajax_dialogo_cliente_b" value="'.$argumentos['busqueda'].'" onkeypress="var e = event || window.event;var tecla = (document.all) ? e.keyCode : e.which;if(tecla==13){'.$func.'}"  />
				
				<button title="buscar" onclick="'.$func.'" ><img border="0" src="'.getUrl('img/find.png').'" alt="buscar" /></button>
				<button title="Volver" onclick="'.$rfunc.'" ><img border="0" src="'.getUrl('img/cc.png').'" alt="Volver" /></button>
				</div>
			';
			if($rec->getNumRows()!=0){
				if($insertmode){
					$th_captions='<td colspan="2" style="font-weight:bold;text-align:center;">Acciones</td>';
				}
				//creando el objeto
				$html.='
				<table class="order_table"><thead><tr>
				<td>&nbsp;</td>
				<td>'.$paginator->get($cliente->ci,'CI/Rif').'</td>
				<td>'.$paginator->get($cliente->nombre,'Nombre').'</td>
				<td>'.$paginator->get($cliente->apellido,'Apellido').'</td>
				<td>'.$paginator->get($cliente->telf,'Tel&eacute;fonos').'</td>
				<td>'.$paginator->get($cliente->correo,'Correo').'</td>
				'.$th_captions.'
				</tr></thead><tbody>';
				$class='';
				foreach ($rec as $reg){
					if(!$insertmode){
						$trclickfunc='onclick="'.$argumentos['callback'].'('.$reg->id.');close_window(\''.$capa.'\');"';
						$but='<td width="1%"><button type="button" '.$trclickfunc.'><img border="0" alt="cargar" src="'.getUrl('img/minselect.png').'" /></button></td>';
					}else{
						$but='<td width="1%"></td>';
						$tdclick='onclick="'.$argumentos['callback'].'('.$reg->id.',';//view");"';
						$td_actions='
							<td align="center"><img '.$tdclick.'\'view\');" border="0" src="'.getURL('img/iconos/view.png').'" alt="Ver" title="Ver" /></td>
							
							<td align="center"><img '.$tdclick.'\'edit\');" border="0" src="'.getURL('img/iconos/edit.png').'" alt="Editar" title="Editar" /></td>
						';//<td><img '.$tdclick.'\'print\');" border="0" src="'.getURL('img/iconos/print.png').'" alt="Imprimir" title="Imprimir" /></td>
					}
					$html.='<tr class="'.$class.'" '.$trclickfunc.'>
					'.$but.'
					<td>'.$reg->lci.'-'.$reg->ci.'</td>
					<td>'.$reg->nombre.'</td>
					<td>'.$reg->apellido.'</td>
					<td nowrap="nowrap">'.$reg->telf.'/'.$reg->otrotelf.'</td>
					<td>'.$reg->correo.'</td>
					'.$td_actions.'
					</tr>';
					if($class==''){
						$class='impar';
					}else{
						$class='';
					}
				}
				$html.='</tbody><tfoot><tr><td colspan="7" align="center"><hr />Mostrando '.$paginator->count.' resultados de un total de '.$paginator->totalrows.' '.$paginator->getPages(false).'</td></tr></tfoot></table>
				';
			}else{
				$html.='<div class="order_empty">No existen coincidencias de "'.$argumentos['busqueda'].'"</div>';
			}
			if(!$insertmode){
				$html.='<img class="close_window" src="../img/close_dialog.png" alt="cerrar" title="Cerrar" style="cursor:pointer;" onclick="close_window(\''.$capa.'\');" border="0" />';
			}
		}
		if(!$insertmode){
			$html='<div class="title" id="xajax_dialogo_cliente_title">Buscar Clientes</div><div class="content">'.$html.'</div>';
			$script="var insertmode=false;";
		}else{
			$script="var insertmode=true;";
		}
		$script.='var fdiv = obj("'.$capa.'");
				if(fdiv==null){
				
					fdiv=document.createElement("div");
					fdiv.setAttribute("id","'.$capa.'");
					fdiv.style.visibility="hidden";
				}
				
				var parente="'.$argumentos['parent'].'";
				if(parente!=""){
					parente = document.getElementById(parente);				
					parente.appendChild(fdiv);					
				}else{
					document.body.appendChild(fdiv);
				}
				if(!insertmode){
				fdiv.style.position="absolute";
				fdiv.style.zIndex="1";
				fdiv.className="window";
				fdiv.style.background="white";
				fdiv.style.display="block";
				}
				
				';
		//$r->alert($capa);
		
		$r->assign($capa,inner,$html);
		$r->script($script);
		
		$r->script('
			var fdiv = obj("'.$capa.'");
			//alert("1+ valor"+fdiv.innerHTML);
			if(fdiv.style.visibility=="hidden"){
				fdiv.style.visibility="";
				setCenter("'.$capa.'",true);		
				setWindow("'.$capa.'","xajax_dialogo_cliente_title");
			}else{
				setWindow("'.$capa.'","xajax_dialogo_cliente_title",false,true);			
			}
			obj("xajax_dialogo_cliente_b").focus();
		');
		//$r->alert(utf8_encode($q->getSelect()));
		$c->close();
		//$r->alert(utf8_encode($q->getSelect()));
		return $r;
	}
	
	xajaxRegister('dialogo_cliente');
?>