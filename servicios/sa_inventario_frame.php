<?php

@session_start();
	$id_cita=$_GET['id_cita'];
	if($id_cita==''){
		exit;
	}
//implementando xajax;
	require_once("control/main_control.inc.php");
	require_once("control/funciones.inc.php");
	
	function load_page($id_cita){
		$r= getResponse();
		//$r->alert($id_cita);
		$c = new connection();
		$c->open();
		
		
		$sa_art_inventario= new table('sa_art_inventario','',$c);//redefiniendo la tabla para no alterar el caché
		
		$criterio_subconsulta=new criteria(sqlAND,
			array(
				new criteria(sqlEQUAL, $c->sa_recepcion_inventario->id_art_inventario, $sa_art_inventario->id_art_inventario),
				new criteria(sqlEQUAL, $c->sa_recepcion_inventario->id_cita,$id_cita)		
			)
		);
		
		$sa_recepcion_inventario=new table("sa_recepcion_inventario");//redefiniendo la tabla para no alterar el caché
		$sa_recepcion_inventario->estado;	//definiendo explícitamente el campo	
		$subquery = new query($c,'estado');
		$subquery->add($sa_recepcion_inventario);
		$subquery->where($criterio_subconsulta);//compartiendo los objetos
		
		$sa_recepcion_inventario2=new table("sa_recepcion_inventario");
		$sa_recepcion_inventario2->cantidad;//definiendo explícitamente el campo	
		$subquery2 = new query($c,'cantidad');
		$subquery2->add($sa_recepcion_inventario2);
		$subquery2->where($criterio_subconsulta);//compartiendo los objetos
		
		$sa_recepcion_inventario3=new table("sa_recepcion_inventario");
		$sa_recepcion_inventario3->id_recepcion_inventario;//definiendo explícitamente el campo	
		$subquery3 = new query($c,'id_recepcion_inventario');
		$subquery3->add($sa_recepcion_inventario3);
		$subquery3->where($criterio_subconsulta);//compartiendo los objetos
		
		/*$sa_recepcion_inventario4=new table("sa_recepcion_inventario");
		$sa_recepcion_inventario4->url_foto;//definiendo explícitamente el campo	
		$subquery4 = new query($c,'url_foto');
		$subquery4->add($sa_recepcion_inventario4);
		$subquery4->where($criterio_subconsulta);//compartiendo los objetos*/
		
		$q = new query($c);
		$sa_art_inventario->add($subquery);
		$sa_art_inventario->add($subquery2);
		$sa_art_inventario->add($subquery3);
		//$sa_art_inventario->add($subquery4);
		$q->add($sa_art_inventario);
		
		//AGREGADO 29 JULIO 2009
		$reccita=$c->sa_cita->doSelect($c,new criteria(sqlEQUAL,$c->sa_cita->id_cita,$id_cita));
		$id_empresa=$reccita->id_empresa;
		//$q->where(new criteria(sqlEQUAL,$sa_art_inventario->id_empresa,$id_empresa));
		
		//obteniendo empresa de la cita
	//	$rec_cita=$c->sa_cita->doSelect($c,new criteria(sqlEQUAL,$c->sa_cita->id_cita,$id_cita));
		//limitando a loas articulos definidos para la empresa:
		//$q->where(new criteria(sqlEQUAL,$c->sa_art_inventario->id_empresa,$rec_cita->id_empresa));
		$q->where(new criteria(sqlEQUAL,$c->sa_art_inventario->activo,1));
		
		$q->orderBy($sa_art_inventario->descripcion);
		//$r->alert($q->getSelect());return $r;
		
		$rec = $q->doSelect();
		if($rec){
			$html='<table class="insert_table"  style="width:330px;">
					<thead><tr><td class="caption" colspan="6" >Estado del veh&iacute;culo</td></tr></thead>
					<tbody id="tbodyart" class="tbodycenter">
						<tr class="tdsubcaption">
							<td title="Marcar/Desmarcar todos"><input type="checkbox" onclick="checkArt(this);" /></td>
							<td>Descripci&oacute;n</td>
							<td title="Buen Estado">B</td><td title="Estado Regular">R</td><td title="Mal Estado">M</td>
							<td>Cant.</td>
						</tr>';
			foreach($rec as $v){
				$estados['B']='';
				$estados['R']='';
				$estados['M']='';
				$n=$v->id_art_inventario;
				$cantidad=$v->cantidad;
				if($v->id_recepcion_inventario==''){
					$cantidad=$v->cantidad_definida;
					//$r_select='checked="checked"';
					$estados['R']='checked="checked"';
					$checkval='';
					$classrow='';
				}else{
					$estados[$v->estado]='checked="checked"';
					$checkval='checked="checked"';
					$classrow='selectrow';
				}
				$html.='<tr class="'.$classrow.'" id="td_art'.$n.'" title="Marque en la casilla los elementos a inspeccionar, los dem&aacute;s ser&aacute;n ignorados" >
				<td class="field"><input type="checkbox" id="check_art'.$n.'" name="check_art['.$n.']"'.$checkval.' onclick="color_check('.$n.');" /></td>
				<td class="field" style="text-align:left;padding:2px;"><label style="cursor:pointer;" for="check_art'.$n.'" style="width:100%;display:block;">'.$v->descripcion.'</label></td>
				<td class="field" nowrap="nowrap" title="Buen Estado">
					<label><input name="estado['.$n.']" type="radio" '.$estados['B'].' value="B" onclick="art_click_radio('.$n.')" /></label>
				</td>
				<td class="field" nowrap="nowrap" title="Estado Regular">
					<label><input name="estado['.$n.']" type="radio" '.$estados['R'].' value="R" onclick="art_click_radio('.$n.')" /></label>
				</td>
				<td class="field" nowrap="nowrap" title="Mal Estado">
					<label><input name="estado['.$n.']" type="radio" '.$estados['M'].' value="M" onclick="art_click_radio('.$n.')" /></label>
				</td>
				<td class="field" title="Cantidad"><input type="text" maxlength="4" name="cantidad['.$n.']" style="width:30px;" value="'.$cantidad.'" onchange="art_change(this,'.$n.');" onkeypress="return inputInt(event);" /><input type="hidden" name="id_recepcion_inventario['.$n.']" value="'.$v->id_recepcion_inventario.'" /><input type="hidden" name="id_art_inventario['.$n.']" value="'.$v->id_art_inventario.'" /></td>
				</tr>';
			}
			$html.='</tbody></table>';
		}
		$r->assign('tabla_art',inner,$html);
		
		//llenando las incidencias
		
		$sa_recepcion_incidencia = $c->sa_recepcion_incidencia;
		$qi=new query($c);
		$qi->add($sa_recepcion_incidencia);
		$qi->where(new criteria(sqlEQUAL,$sa_recepcion_incidencia->id_cita,$id_cita));
	
		$reci=$qi->doSelect();
                
		if($reci){
			//if($reci->getNumRows()!=0){
				foreach($reci as $art){
                                    
					$script.=sprintf("clicked(%s,%s,null,null,%s,'%s','%s');",
					$art->x,
					$art->y,
					$art->id_recepcion_incidencia,
					$art->tipo_incidencia,
					$art->url_foto
					);
				}
			//}
			if($script!=''){
				$r->script($script);
			}
		}
		
		//$r->alert($html);
		$c->close();
		return $r;
	}
	//include("control/xajax_dialogo_cliente.inc.php"); //incluir la busqueda/registro del cliente
	
	function guardar_incidencias($form,$limpiariframe=false,$lastid=null,$id_orden){
		$r=getResponse();
		$c= new connection();
		$c->open();
		$c->begin();
		$err=false;
		//$r->alert(utf8_encode(var_export($form,true)));
		foreach($form['id_recepcion_inventario'] as $k => $v){
                    
			//verificar seleccionados
			$sql='';
			if($form['check_art'][$k]!=''){
				if($v==''){
					//insert
					$sql=sprintf("INSERT INTO sa_recepcion_inventario (id_recepcion_inventario,id_art_inventario,id_cita,estado,cantidad)VALUES (NULL , '%s', '%s', '%s', '%s');",
					$form['id_art_inventario'][$k],
					$form['id_cita'],
					$form['estado'][$k],
					field::getTransformType($form['cantidad'][$k],field::tFloat)
					);
				}else{
					//update	
					$sql=sprintf("UPDATE sa_recepcion_inventario SET id_art_inventario=%s,estado='%s',cantidad='%s' where id_recepcion_inventario=%s;",
					$form['id_art_inventario'][$k],
					$form['estado'][$k],
					field::getTransformType($form['cantidad'][$k],field::tFloat),
					$form['id_recepcion_inventario'][$k]
					);
				}
			}else{
				if($v!=''){
					//delete
					$sql=sprintf("DELETE FROM sa_recepcion_inventario where id_recepcion_inventario=%s;",
					$form['id_recepcion_inventario'][$k]
					);
				}
				
			}
			if($sql!=''){
				//$sqlt.=$sql;
				$result = $c->soQuery($sql);
				if(!$result){
					$err=true;
				}
			}
		}
		if(isset($form['id_recepcion_incidencia'])){
			foreach($form['id_recepcion_incidencia'] as $k => $v){
				$sql='';
				//verificando por accion
				if($form['rgaccion'][$k]=='a'){
					if($form['id_recepcion_incidencia'][$k]==''){
						$sql=sprintf("INSERT INTO sa_recepcion_incidencia(id_recepcion_incidencia,id_cita,tipo_incidencia,url_foto,x,y) VALUES (NULL , %s, '%s', '%s', '%s', '%s');",
						$form['id_cita'],
						$form['tipo'][$k],
						utf8_decode($form['url'][$k]),
						field::getTransformType($form['x'][$k],field::tFloat),
						field::getTransformType($form['y'][$k],field::tFloat)
						);
					}else{
						$sql=sprintf("UPDATE sa_recepcion_incidencia SET tipo_incidencia='%s',url_foto='%s' where id_recepcion_incidencia=%s;",
						$form['tipo'][$k],
						utf8_decode($form['url'][$k]),
						$form['id_recepcion_incidencia'][$k]
						);
					}
				}else{
					if($form['id_recepcion_incidencia'][$k]!=''){
						$sql=sprintf("DELETE FROM sa_recepcion_incidencia where id_recepcion_incidencia=%s;",
						$form['id_recepcion_incidencia'][$k]
						);
					}
				}
				if($sql!=''){
					$result = $c->soQuery($sql);
					if(!$result){
						//$r->alert('error');
						$err=true;
					}
				}
			}
		}
		//$r->alert($sql);
		if(!$err){
			$c->commit();
			$r->alert('Se han guardado los datos del inventario correctamente');
		}else{
			$c->rollback();
			$r->alert('NO se guardaron lso datos del inventario');
		}
		$c->close();
		
		if($limpiariframe==true){
			$r->script('
			//setPopup("sa_vale_recepcion_imprimir.php?view=print&id='.$lastid.'","recep",{height:700,width:900,center:\'both\',resizable:1,resizeable:1,scrollbars:1});
			//window.location="sa_inventario_frame.php";
			//window.open("sa_historico_recepcion.php","_top");//sa_listado_citas.php
			');
		}else{
			$r->script('window.location="sa_inventario_frame.php?id_cita='.$form['id_cita'].'";');
		}
                $r->script('
                    window.parent.location="sa_orden_form.php?idv='.$lastid.'&doc_type=2&acc=3&ide='.$_SESSION['idEmpresaUsuarioSysGts'].'&id='.$id_orden.'";
                        ');
		return $r;
	}

	
	xajaxRegister('load_page');
	xajaxRegister('guardar_incidencias');
	
	xajaxProcess();
	
	includeDoctype();
		
?>

<html>
	<head>
		<?php 
			includeMeta();
			includeScripts();
			includeMouseTouch();
			includeModalBox();
			getXajaxJavascript();
		?>
		<link rel="stylesheet" type="text/css" href="css/sa_general.css" />
		
                <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Inventario de Entrada Vehículo</title>
                <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
        
		<script>
			
			var counter=0;
			var artablag = Array();
			var canceladd=false;
			var clicked = function(x,y,ox,oy,id_rg,itype,url_foto){
				if (canceladd) return;
				counter++;
				//$('#info').html('down '+x+' '+y);
				var cap=obj('inv_vehiculo');
				if(id_rg==null){
					id_rg='';
				}
				if(itype==null){
					itype=obj('addrg').value;
				}
				if(url_foto==null){
					url_foto='';
				}
				ox= mouseTouch._getOffsetLeft(obj('inv_vehiculo'));
				oy= mouseTouch._getOffsetTop(obj('inv_vehiculo'));
				//alert(document.documentElement.scrollTop);//mouseTouch.getVScrollWindow());
				var dx=x+ox;//mouseTouch._getOffsetLeft(cap);
				var dy=y+oy;//+mouseTouch.getVScrollWindow();//mouseTouch._getOffsetTop(cap);
				
				
				
				var newo= document.createElement('div');
				newo.setAttribute('id','idn'+counter);
				newo.setAttribute('title','Incidencia: '+counter);
				newo.style.position='absolute';
				newo.style.display='block';
				newo.style.left=(dx-5)+'px';
				newo.style.top=(dy-5)+'px';
				newo.style.width='10px';
				newo.style.height='10px';
				newo.style.cursor='pointer';
				newo.style.margin='0px';
				newo.style.border='0px';
				//newo.innerHTML='<img border="0" src="../img/iconos/punto.png"/>';
				cap.appendChild(newo);
				
				//agregar la fila
				var tablag=obj("tablag");
				var nt = new tableRow("tablag");
				artablag[counter]=nt;
				nt.setAttribute('id','idr'+counter);
				$('#idr'+counter).bind('mouseover',function (){
					mouseoverRG(this);});
				$('#idn'+counter).bind('mouseover',function (){
					mouseoverRG(this);});
				
				$('#idr'+counter).bind('mouseout',function (){
					mouseoutRG(this);});
				$('#idn'+counter).bind('mouseout',function (){
					mouseoutRG(this);});
				$('#idn'+counter).bind('click',function (){
					viewPhotoP(this);});
					
				//celdas:
				var c1= nt.addCell();
					c1.$.className='field';
					var selectr,selectg;
					if(itype=='RAYA'){
						selectr='selected="selected"';
						newo.innerHTML='<img border="0" src="<?php echo getUrl('img/iconos/raya.png'); ?>"/>';
					}else{						
						selectg='selected="selected"';
						newo.innerHTML='<img border="0" src="<?php echo getUrl('img/iconos/golpe.png'); ?>"/>';
					}
					c1.$.innerHTML=counter+': <input type="hidden" id="x'+counter+'" name="x['+counter+']" value="'+x+'" /><input type="hidden" id="y'+counter+'" name="y['+counter+']" value="'+y+'" /><input type="hidden" id="url'+counter+'" name="url['+counter+']" value="'+url_foto+'" /><input type="hidden" id="rgaccion'+counter+'" name="rgaccion['+counter+']" value="a" /><input type="hidden" id="id_recepcion_incidencia'+counter+'" name="id_recepcion_incidencia['+counter+']" value="'+id_rg+'" /><select id="tipo'+counter+'" name="tipo['+counter+']" onchange="changeRG(this.value,'+counter+')" ><option '+selectr+' value="RAYA">RAYA</option><option '+selectg+' value="GOLPE" >GOLPE</option></select>';
				var c0= nt.addCell();
					//c0.$.className='field';
					c0.$.innerHTML='<button type="button" title="Ver/Agregar Foto" onclick="viewPhoto('+counter+');" ><img border="0" src="<?php echo getUrl('img/iconos/photo.png'); ?>" style="padding-left:2px;" /></button><button title="Quitar" onclick="removeRG('+counter+');" ><img border="0" src="<?php echo getUrl('img/iconos/minus.png'); ?>" style="padding-left:2px;" /></button>';
				/*var c3= nt.addCell();
					c3.$.className='field';
					c3.$.innerHTML=y;
				var c4= nt.addCell();
					c4.$.className='field';
					c4.$.innerHTML=y;*/
				
			}
			
			function removeRG(c){
				if (_confirm('&iquest;Desea eliminar la incidencia "'+c+'"?')){
					var row= obj('idr'+c);
					row.style.display='none';
					var punto= obj('idn'+c);
					punto.style.display='none';
					var accion= obj('rgaccion'+c);
					accion.value='d';
				}
			}
			
			function viewPhotoP(c){
				viewPhoto(c.id.substring(3));
			}
			function viewPhoto(id){
				//modalWindow('#cuadro_foto_inicidencia',300,300);
				//mejor un POPup
				setPopup('sa_inventario_foto.php?fields=url&id='+id+'&url='+obj('url'+id).value,'popup_art',{
					width:700,
					height:600,
					center:'v',
					dialog:'t',
					left:0
				});
			}
			
			function mouseoverRG(c){
				canceladd=true;
				c = c.id.substring(3);
				//alert(c);
				var row= obj('idr'+c);
				var punto= obj('idn'+c);
				$('#idr'+c).addClass('mouseover');
				$('#idn'+c).addClass('immouseover');
			}
			function mouseoutRG(c){
				canceladd=false;
				c = c.id.substring(3);
				var row= obj('idr'+c);
				var punto= obj('idn'+c);
				$('#idr'+c).removeClass('mouseover');
				$('#idn'+c).removeClass('immouseover');
			}
			function changeRG(val,c){
				if(val=='RAYA'){
					obj('idn'+c).innerHTML='<img border="0" src="<?php echo getUrl('img/iconos/raya.png'); ?>"/>';
				}else{						
					obj('idn'+c).innerHTML='<img border="0" src="<?php echo getUrl('img/iconos/golpe.png'); ?>"/>';
				}
			}
			var mover = function(x,y){
				//$('#moveinfo').html(x);
			}
			/*manejo de articulos*/
			function art_change(art,n){
				if((parseNumber(art.value)<1) || (parseNumber(art.value)!=art.value)){
					_alert('No puede ser menor que uno (1)');
					art.value=1;
				}else{
					art_click_radio(n);
				}
			}
			function art_click_radio(n){
				obj('check_art'+n).checked=true;
				obj('td_art'+n).className='selectrow';
			}
			function checkArt(check){
				var val = check.checked;
			//	alert(val);
				$('#tbodyart input[type=checkbox]').attr('checked',val);
				if(val){
					$('#tbodyart tr').not('#tbodyart tr.tdsubcaption').attr('class','selectrow')
				}else{					
					$('#tbodyart tr').not('#tbodyart tr.tdsubcaption').attr('class','')
				}
			}
			
			function guardar_inventario(limpiar,lastid,id_orden){
				xajax_guardar_incidencias(xajax.getFormValues('form_inventario'),limpiar,lastid,id_orden);
			}
			
			function color_check(n){
				var check= obj('check_art'+n);
				var row=obj('td_art'+n);
				if(check.checked){
					row.className='selectrow';
				}else{
					row.className=''
				}
			}
			
		</script>
	</head>
	<body style="margin:0px; overflow:none;">

	
	
	<form id="form_inventario" name="form_inventario" onsubmit="return false;" >
		<input type="hidden" name="id_cita" id="id_cita" value="<?php echo $id_cita; ?>" />
		<div id="inv_vehiculo" style="width:360px;height:400px;float:right;">
			<img border="0" style="cursor:crosshair;" src="<?php echo getUrl('img/vehiculo_vectorial.png'); ?>" />
		</div>
		<div style="height:400px;overflow:none;">
			<div style="width:350px;float:left;height:400px;overflow:auto;" id="tabla_art">
				<table class="insert_table" style="width:330px;">
					<thead><tr><td class="caption" colspan="2" >Estado del veh&iacute;culo</td></tr></thead>
					<tbody id="tbodyart">
					</tbody>
				
				</table>
			</div>
			<div style="width:220px;height:400px;overflow:auto;">
				<table class="insert_table" style="width:200px;">
					<thead><tr><td class="caption" colspan="4" donclick="xajax_guardar_incidencias(xajax.getFormValues('form_inventario'));" >Agregar Incidencias</td></tr></thead>
					<tbody id="tablag" class="tbodycenter">
						<tr class="tdsubcaption">
							<td colspan="2">
								<select id="addrg" title="Haga click en el &aacute;rea de la im&aacute;gen para agregar">
									<option value="RAYA">RAYA</option>
									<option value="GOLPE">GOLPE</option>
								</select>
								<img border="0" style="margin-right:1px;" src="<?php echo getUrl('img/iconos/golpe.png'); ?>" />Golpe <img border="0" style="margin-right:1px;" src="<?php echo getUrl('img/iconos/raya.png'); ?>" />Raya
							</td>
							<!--<td>X</td>
							<td>Y</td>-->
						</tr>
					</tbody>
				
				</table>
			</div>
		</div>
	</form>
	<!--<div lass="window" id="cuadro_foto_inicidencia" srtyle="visibility:hidden;">
		<div class="title" id="cuadro_foto_inicidencia_titulo">
			Foto		
		</div>
		<div class="content" >
		asdasd
		</div>
		<img class="close_window" src="<?php //echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onclick="close_window('cuadro_citas');" border="0" />
</div>-->
	<script type="text/javascript" language="javascript">
		xajax_load_page(<?php echo $id_cita; ?>);	
	//$(document).ready(function() {
		mouseTouch.init(obj('inv_vehiculo'),clicked,null,mover);
	//}
	</script>
	</body>
</html>