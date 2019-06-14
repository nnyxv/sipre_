<?php
@session_start();
//NOTA sino se abre alguna session buscara por todos = "NULL" en sql no con IS NULL
//implementando xajax;
	require_once("control/main_control.inc.php");
	require_once("control/funciones.inc.php");
	
	function load_page($id_calendario){
		$r= getResponse();
		//$calendario=setCalendar($id_calendario,'xajax_cargar_dia');
		//$r->loadCommands($calendario);
		return $r;
	}
	include("control/xajax_dialogo_cliente.inc.php"); //incluir la busqueda/registro del cliente

	function buscar_cliente($cedula,$lci=''){
		$cedula=($cedula);
		$r= getResponse();
		$c= new connection();
		$c->open();
		$tiempo_efectivo_pre=intval(getParam($_SESSION['idEmpresaUsuarioSysGts'],"'TIEMPO EFECTIVO PRE'",$c));
		$tiempo_efectivo_post=intval(getParam($_SESSION['idEmpresaUsuarioSysGts'],"'TIEMPO EFECTIVO POST'",$c));
		
		$rec_clienteq=$c->cj_cc_cliente->doQuery($c,new criteria(sqlEQUAL,'ci',"'".$cedula."'"));
		//$r->alert($rec_clienteq->getSelect());//
		$rec_cliente=$rec_clienteq->doSelect();
		if($rec_cliente){
			if($rec_cliente->getNumRows()==1){
				//buscando las citas de ese d�a
				$reccitaq=$c->sa_cita->doQuery($c);
				
				$reccitaq->where(new criteria(sqlEQUAL,'id_empresa',$_SESSION['idEmpresaUsuarioSysGts']));
				$reccitaq->where(new criteria(sqlEQUAL,'fecha_cita','CURRENT_DATE()'));
				$reccount=$c->execute("select max(numero_entrada)+1 as num from sa_cita ".$reccitaq->getCriteria().";");
				//$r->alert($reccount[num]. ' '."select max(numero_entrada)+1 as num from sa_cita ".$reccitaq->getCriteria().";");
				$reccitaq->where(new criteria(sqlOR,array(
					new criteria(sqlEQUAL,'estado_cita',"'PENDIENTE'"),
					new criteria(sqlEQUAL,'estado_cita',"'CONFIRMADA'")
				)));
				//$reccitaq->where(new criteria(sqlIS,'tiempo_llegada_cliente',sqlNULL));
				$reccitaq->where(new criteria(sqlEQUAL,'id_cliente_contacto',$rec_cliente->id));
				//$r->alert($reccitaq->getSelect());
				$rec=$reccitaq->doSelect();
				if($rec){
					if($rec->getNumRows()==0){					
						$r->assign('info',inner,'No tiene cita para el d&iacute;a de hoy, dirijase a nuestros Asesores de Servicio para solicitar una cita.');
						$r->script('clear();');
					}else{
						setLocaleMode();
						$tiempo_llegada_cliente=time();
						$numero=$reccount['num'];
						if($numero==''){
							$numero=1;
						}
						$recdc=$c->sa_v_datos_cita->doSelect($c,new criteria(sqlEQUAL,'id_cita',$rec->id_cita));
						if($rec->tiempo_llegada_cliente==''){//numero_entrada
							$sa_cita = new table('sa_cita');
							$sa_cita->add(new field('id_cita','',field::tInt,$rec->id_cita,true));
							$sa_cita->add(new field('tiempo_llegada_cliente','',field::tFunction,'NOW()',true));
							$sa_cita->add(new field('numero_entrada','',field::tInt,$numero,true));
							$c->begin();
							$result=$sa_cita->doUpdate($c,$sa_cita->id_cita);
							if($result!==true){
								$c->rollback();
								$r->alert(( $result[0]->getObject()->getName()));
								return $r;
							}else{
								$c->commit();
							}
						}else{
							//$numero=$rec->numero_entrada;
							$tiempo_llegada_cliente=str_tiempo($rec->tiempo_llegada_cliente);
						}
						//evaluando tiempos
						$tiempo_cita=str_datetime($rec->fecha_cita,$rec->hora_inicio_cita);
						
						$mindiff= ($tiempo_llegada_cliente-$tiempo_cita);
						if($mindiff>$tiempo_efectivo_post){
							//$r->alert('llego muy tarde'.$mindiff .'>'.$tiempo_efectivo_post);
							$text='Le recordamos que la hora pautada para su cita es: '.adodb_date('h:i A',$tiempo_cita).', le agradecemos que se dirija a recepci&oacute;n para evaluar la posibilidad de que su veh&iacute;culo sea atendido o para solicitar una nueva cita.';
						}elseif($mindiff<$tiempo_efectivo_pre){
							//$r->alert('llego muy temprano'.$mindiff .'<'.$tiempo_efectivo_pre);
							$text='Le recordamos que la hora pautada para su cita es: '.adodb_date('h:i A',$tiempo_cita).'.<br />Nuestros asesores en estos momentos se encuentran ocupados, le agradecemos su espera.';
						}else{
							$text='Le agradecemos su puntualidad.<br />Ser&aacute; atendido por nuestro Asesor: '.$recdc->asesor;
							//$r->alert('llego justo'.$mindiff);
						}
						//$r->alert(parseDateTime($tiempo_cita).' '.parseDateTime($tiempo_llegada_cliente));
						
						$r->assign('info',inner,'<span>Bienvenido Sr(a) '.$recdc->apellido.' '.$recdc->nombre.' <br />'.utf8_encode($text).'</span>');
						//<br />Su n&uacute;mero de entrada es: <span class="res">'.$numero.'</span><br />
						$r->script('clear();');
						$r->script('//print();');

					}
				}
				
			}elseif($rec_cliente->getNumRows()>1){
			}else{
				$r->assign('info',inner,'No se encuentra el n&uacute;mero de identificaci&oacute;n: '.$cedula);
				$r->script('clear();');
			}
		}
		$c->close();
		//$r->script('//print();');
		return $r;
	}

	xajaxRegister('buscar_cliente');
	
	xajaxProcess();
	
	$c= new connection();
	$c->open();
	/*$tipos_orden=$c->sa_tipo_orden->doSelect($c)->getAssoc('id_tipo_orden','descripcion_tipo_orden');
	$prioridades=array(
		1=>'ALTA',
		2=>'MEDIA',
		3=>'BAJA'
	);*/
	//cargando datos de la empresa=
	$recemp=$c->sa_v_empresa_sucursal->doSelect($c,new criteria(sqlEQUAL,$c->sa_v_empresa_sucursal->id_empresa,$_SESSION['idEmpresaUsuarioSysGts']));
	
	
	includeDoctype();
		
?>

<html>
	<head>
		<?php 
			includeMeta();
			includeScripts();
			getXajaxJavascript();
			includeModalBox();
		?>
		<link rel="stylesheet" type="text/css" href="css/sa_general.css" />
		
                <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Llegada de Clientes</title>
                <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
                
		<script>
		
		function teclado(){
			//console.log(document.getElementById('teclado_completo').style.display); 
			//console.log($('#teclado_completo').is(':visible'));
			//var teclado = $('#teclado_completo').isDisplayed(); //isdisplayed no existe en este
			var teclado = $('#teclado_completo').is(':visible');
			if(teclado){
				$('#teclado_completo').hide();
			}else{
				$('#teclado_completo').show();
			}
			 
		}
		
		function validacionTeclas(e){
		
		tecla = (document.all) ? e.keyCode : e.which;
			if (tecla == 0 || tecla == 8){//0 = tab y 8 = delete
				return true;
			}else if(tecla == 13){//al presionar enter = 13
				return buscar();
			}
			
			patron = /[0-9A-Za-z\-]/;//  \s es espacio, y ������������ pero no funciona
			te = String.fromCharCode(tecla);
			return patron.test(te);
    
		}
		
		
			var cita_date = {
				fecha: null,
				date:new Date(),
				page:0,
				maxrows:5,
				order:'sa_v_datos_cita.hora_inicio_cita',
				ordertype:null,
				estado_cita:null,
				origen_cita:null,
				filtro_cliente:null
			}
			function r_dialogo_citas(){
				xajax_listar_citas(cita_date.page,cita_date.maxrows,cita_date.order,cita_date.ordertype,'lista_citas','fecha_cita='+cita_date.fecha+',origen_cita='+cita_date.origen_cita+',estado_cita='+cita_date.estado_cita+',filtro_cliente='+cita_date.filtro_cliente);
			}

			
			function calendar_onselect (calendar,date){//DD-MM-AAAA
				if (calendar.dateClicked) {
					var dia=date.substr(0,2);
					var mes=parseFloat(date.substr(3,2))-1;
					var ano=date.substr(6,4);
					cita_date.fecha=date;
					r_dialogo_citas();
					//xajax_listar_citas(cita_date.page,cita_date.maxrows,cita_date.order,cita_date.ordertype,'lista_citas','fecha_cita='+date+',origen_cita='+cita_date.origen_cita);
					cita_date.date=new Date(ano,mes,dia);
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
				cita_calendar.setDate(cita_date.date);
				cita_calendar.showAtElement(_obj);
			}
			
			function cliente_pago(_obj){
				if(_obj.checked){
					$('#cliente_pago').show();
				}else{
					
					$('#cliente_pago').hide();
				}
				
			}
			
			function buscar_cliente_pago(){
				xajax_dialogo_cliente(0,10,'cj_cc_cliente.ci','','',
						'callback=xajax_cargar_cliente_pago,parent=form_cita');
			}
			var tabla_fallas= new Array();
			var counter_fallas=0;
			
			function fallas_add(datos){
				if(datos==null){
					datos={
						falla:'',
						id_recepcion_falla:'',
						descripcion_falla:''
					}
				}
				var tabla=obj('tbody_fallas');
				var nt = new tableRow("tbody_fallas");
				tabla_fallas[counter_fallas]=nt;
				counter_fallas++;
				nt.setAttribute('id','row_fallas'+counter_fallas);
				nt.$.className='field';
				//var c1= nt.addCell();
					//c1.$.className='field';
					//c1.setAttribute('style','width:30%;');
					//c1.$.innerHTML=counter_fallas;
				var c2 = nt.addCell();
					c2.$.innerHTML='<input type="text" style="width:99%" name="descripcion_falla[]" id="descripcion_falla+'+counter_fallas+'" value="'+datos.descripcion_falla+'" /><input  id="actionf'+counter_fallas+'" type="hidden" name="actionf[]" value="add" /><input  id="id_recepcion_falla'+counter_fallas+'" type="hidden" name="id_recepcion_falla[]" value="'+datos.id_recepcion_falla+'" />';
				var c3 = nt.addCell();
					c3.$.innerHTML='<button type="button" onclick="fallas_quit('+counter_fallas+')"><img border="0" alt="quitar" src="<?php echo getUrl('img/iconos/minus.png');?>" /></button>';
			}
			
			function fallas_quit(cont){
				if(_confirm("&iquest;Desea eliminar la falla?")){
					var fila=obj('row_fallas'+cont);
					fila.style.display='none';
					var action=obj('actionf'+cont);
					//alert(action);
					action.value='delete';
				}
			}
			
			function evento(){
				alert("si");
			}
			
			var t;
			function precarga(){
				obj('info').innerHTML='';
				obj('cedula').value='';
				//alert(t);
				if (t!=null){
					clearTimeout(t);
					t=null;
				}
				obj('cedula').focus();
			}
			
			function addnum(objt){
				if(objt.innerHTML=='R'){
					var s= obj('cedula').value;
					obj('cedula').value=s.substring(0,s.length-1);
				}else{
					obj('cedula').value=obj('cedula').value+objt.innerHTML;
				}				
				obj('cedula').focus();
			}
			
			function buscar(){
				var s= obj('cedula').value;
				if(s=='0' || s==''){
					precarga();
					return;
				}
				if (t!=null){
					clearTimeout(t);
					t=null;
				}
				obj('info').innerHTML="Buscando...";
				xajax_buscar_cliente(s);
			}
			function clear(){
				t=setTimeout('precarga();',10000);
			}
		</script>
		
		<style type="text/css">
			
			@media print{
				.noprint{
					display:none;
				}
				.onlyprint{
					display:auto;
				}				
				#info{
					font-size: 10px;
					max-width:350px;
					text-align:center;
				}
			}
			@media screen{
				#info{
					font-size: 20px;
					max-width:350px;
					text-align:center;
				}
			}
			button img{
				vertical-align:middle;
			}
			#control_llegada caption{
				padding:3px;
				font-size: 16px;
				font-weight:bold;
			}
			
			#control_llegada td{
				font-size: 14px;
			}
			.numpad{
				height:70px;
				width:70px;
				font-size: 36px;
				font-weight:bold;
				margin:0px;
			}
			.numpadTeclado{
				height:45px;
				width:45px;
				font-size: 24px;
				font-weight:bold;
				margin:0px;
			}
			#cedula{
				font-size: 48px;
				width:400px;
				height:64px;
				margin:0px;
				border:0px;
				font-weight:bold;
				text-align:right;
			}
			.numed{
				padding:2px;
				float:left;
			}
			.numelet{
				clear:both;
			}
		
			
			.res{
				color:#FF0000;
				font-weight:bold;
			}
			.onlyprint{
				display:none;
			}
		</style>
	</head>
	<body onload="precarga();" >
<?php if($_GET['view']==''){include("banner_servicios.php");} ?>
<!--MARCO PRINCIPAL-->
	<br><br>
	<div class="nohover" style="max-width:656px;margin:0px auto;" >
		<table id="control_llegada" class="insert_table" style="width:auto;border:1px solid black;">
			<caption>CONTROL DE LLEGADA</caption>
			<tbody>
				<tr >
					<td style="text-align:center;" class="label" colspan="2"><?php echo $recemp->nombre_empresa; ?><br /> Le da la bienvenida al Taller<span  class="noprint">, ingrese su numero de identificaci&oacute;n para verificar los datos:</span></td>
				</tr>
				<tr>
					<td class="field" width="*" style="padding:0px;height:1%;vertical-align:top;text-align:center;" >
						<input class="noprint" maxlength="30" name="cedula" id="cedula" onkeypress="return validacionTeclas(event);" />
					</td>
					<td class="noprint" rowspan="2" style="text-align:center;">
						<div style="margin:auto;width:222px;">
							<div class="numelet">
								<div class="numed"><button class="numpad" onclick="addnum(this);">1</button></div>
								<div class="numed"><button class="numpad" onclick="addnum(this);">2</button></div>
								<div class="numed"><button class="numpad" onclick="addnum(this);">3</button></div>
							</div>
							<div class="numelet">
								<div class="numed"><button class="numpad" onclick="addnum(this);">4</button></div>
								<div class="numed"><button class="numpad" onclick="addnum(this);">5</button></div>
								<div class="numed"><button class="numpad" onclick="addnum(this);">6</button></div>
							</div>
							<div class="numelet">
								<div class="numed"><button class="numpad" onclick="addnum(this);">7</button></div>
								<div class="numed"><button class="numpad" onclick="addnum(this);">8</button></div>
								<div class="numed"><button class="numpad" onclick="addnum(this);">9</button></div>
							</div>
							<div class="numelet">
								<div class="numed"><button title="Borrar" class="numpad" onclick="addnum(this);">R</button></div>
								<div class="numed"><button class="numpad" onclick="addnum(this);">0</button></div>                             
								<div class="numed"><button title="Agregar Guion" class="numpad" onclick="addnum(this);">-</button></div>  
							</div>
                            <div class="numelet">
								<div class="numed"><button title="Limpiar/Borrar Todo" class="numpad" onclick="precarga();">C</button></div>
								<div class="numed"><button title="Teclado" onclick="teclado();" class="numpad"><img  src="<?php echo getUrl('img/iconos/teclado.png'); ?>" border="0" title="Teclado" alt="Teclado" /></button></div>                             
								<div class="numed"><button title="Aceptar" onclick="buscar();" class="numpad"><img  src="<?php echo getUrl('img/iconos/aceptar.png'); ?>" border="0" title="Aceptar" alt="Aceptar" /></button></div>
							</div>
						</div>
					</td>
				</tr>
				<tr>
					<td class="field" style="text-align:center;"><span  id="info"></span></td>
				</tr>
			</tbody>
		</table>
<?php if($_GET['view']==''){?>
		<div style="text-align:center;"><br />
	<button onclick="window.location='sa_registro_entrada_clientes.php?view=nomenu';"><img alt="Ocultar Men&uacute;" src="<?php echo getUrl('img/iconos/delete.png'); ?>" /> Ocultar Men&uacute;</button></div>
<?php } ?>


        <div id="teclado_completo"  style="margin: 0; padding: 0; text-align: center; display:none;">
            <!-- PRIMERA LINEA DE TECLADO -->
            <div style="width: 500px; margin: 0 auto;">
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">Q</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">W</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">E</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">R</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">T</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">Y</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">U</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">I</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">O</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">P</button></div>
                <div style="clear:both"></div>
            </div>
            <!-- SEGUNDA LINEA DE TECLADO -->
            <div style="width: 500px; margin: 0 auto;">
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">A</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">S</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">D</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">F</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">G</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">H</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">J</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">K</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">L</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">&Ntilde;</button></div>
            </div>
            <!-- TERCERA LINEA DE TECLADO -->
            <div style="width: 400px; margin: 0 auto;">
            	<div style="clear:both"></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">Z</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">X</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">C</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">V</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">B</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">N</button></div>
                <div class="numed"><button title="Borrar" class="numpadTeclado" onclick="addnum(this);">M</button></div>
            </div>
        </div>
		
        
        
	</div><!-- fin nohover -->


	<script type="text/javascript" language="javascript">
		//xajax_load_page('calendario');	
	</script>
	</body>
</html>