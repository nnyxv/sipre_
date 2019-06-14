<?php

@session_start();


//obteniendo informacion del upload

//implementando xajax;
	require_once("control/main_control.inc.php");
	require_once("control/funciones.inc.php");
	includeDoctype();
	
	$nombre=$_GET['url'];
	$desc=$nombre;
	if($nombre==''){
		$desc='No se ha cargado una imagen para la incidencia';
	}
	$id=$_GET['id'];
	$fields=$_GET['fields'];
?>


<html>
	<head>
		<?php
			includeMeta();
			includeScripts();
			
			if (isset($_FILES['foto'])){
				sleep(2);
				echo '<script type="text/javascript" language="javascript">';
				$imagen=$_FILES['foto'];
				set_time_limit(0);
				//sleep(2);
					
					$nombre_archivo = $imagen['name'];
					
					if (!($imagen['type'] == "image/pjpeg" || $imagen['type'] == "image/jpeg"
							|| $imagen['type'] == "image/x-png" || $imagen['type'] == "image/png"
							|| $imagen['type'] == "image/gif" || $imagen['type'] == "image/bmp")) { 
						//echo '_alert("");';
						echo "window.open('sa_foto_view.php?error=1','ffoto');";
					}else{
						if (move_uploaded_file($imagen['tmp_name'],'fotos/'.utf8_decode($imagen['name']))){
							//echo "parent.getMarco_foto().innerHTML='<img onmousedown=\"return expander(event);\"  onmouseup=\"mouseup=true;\" onmouseout=\"mouseup=true;\" style=\"width:95%;\" id=\"cfoto\" border=\"0\" src=\"fotos/".($imagen['name'])."\" />';
							//parent.document.getElementById('urlfoto').value=\"".($imagen['name'])."\";";
							echo "window.open('sa_foto_view.php?name=".$imagen['name']."','ffoto');
							parent.document.getElementById('urlfoto').value=\"".($imagen['name'])."\";";
							echo "parent.document.getElementById('desc').innerHTML=\"".$imagen['name']."\";";
						}else{
							echo "window.open('sa_foto_view.php?error=2,'ffoto');";
						}						
					}
				echo '</script>';
				exit;
			}
			
			
			
		?>
		<link rel="stylesheet" type="text/css" href="css/sa_general.css" />
		<title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Foto de incidencia: <?php echo htmlentities($id);?></title>                
                <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
        
		<script type="text/javascript" language="javascript">
			function cargar_foto(){
				window.frames['ffoto'].document.write('<html><body style="background:#000000;margin:0px;"><div style="text-align:center;margin:auto;color:#FFFFFF;background:#000000;margin:0px;"><div><img border="0" src="<?php echo geturl('img/loader2.gif'); ?>" /></div>Cargando foto...<br /><br /><hr />Dependiendo del tama&ntilde;o del archivo esta operaci&oacute;n puede tardar varios minutos.</div></body></html>');
				obj('formulario_foto').submit();
			}
			
			function getMarco_foto(){
				return obj('marco_foto');
			}
			var percent=95;
			var mouseup=true;
			function disminuir_foto(){
				if(obj('urlfoto').value==""){
					alert("No hay Foto cargada");
					return;
				}
				var f = window.frames['ffoto'].document.getElementById('cfoto');
				
				//return;
				var p = obj('marco_foto');
				mouseup=false;
				
				var pw= p.clientWidth || p.innerWidth;
				var ph= p.clientHeight || p.innerHeight;
				if(percent>10){ 
					var interval = setInterval(function(){
							f.style.width=(percent-5)+'%';
							percent-=5;
							
							var fw= f.clientWidth || f.innerWidth;
							var fh= f.clientHeight || f.innerHeight;
							
							//f.style.top=((ph-fh)/2)+'px';
							//f.style.left=((pw-fw)/2)+'px';
							//window.frames['ffoto'].scroll((fw-pw)/2,(fh-ph)/2);
							
							if((mouseup) || (percent<=10)){
								clearInterval(interval);
							}					
					},5);						
				}
			}
			function aumentar_foto(){
				if(obj('urlfoto').value==""){
					alert("No hay Foto cargada");
					return;
				}
				var f = window.frames['ffoto'].document.getElementById('cfoto');
				var p = obj('marco_foto');
				mouseup=false;
				var pw= p.clientWidth || p.innerWidth;
				var ph= p.clientHeight || p.innerHeight;
				if(percent<500){
					var interval = setInterval(function(){
							f.style.width=(percent+5)+'%';
							percent+=5;
							
							var fw= f.clientWidth || f.innerWidth;
							var fh= f.clientHeight || f.innerHeight;
							//f.style.top=((ph-fh)/2)+'px';
							//f.style.left=((pw-fw)/2)+'px';
							//window.frames['ffoto'].scroll((fw-pw)/2,(fh-ph)/2);
							
							if((mouseup) || (percent>=500)){
								clearInterval(interval);
							}					
					},5);	
				}
			}
			function expande_foto(){
				var f = window.frames['ffoto'].document.getElementById('cfoto');
				if(f==null) {
					alert("No hay Foto cargada");
					return;
				}
				//if(percent<300){
				var p = obj('marco_foto');
				var pw= p.clientWidth || p.innerWidth;
				var ph= p.clientHeight || p.innerHeight;
					f.style.width='100%';
					percent=100;
				var fw= f.clientWidth || f.innerWidth;
				var fh= f.clientHeight || f.innerHeight;
				
				f.style.top=((ph-fh)/2)+'px';
				f.style.left=((pw-fw)/2)+'px';
				//}
			}
			
			function expander(e){
				if(e==null){
					e=event;
				}
				if(e==null){
					e=window.event;
				}
				var boton = (document.all) ? e.button : e.which;
				if(boton==1){
					aumentar_foto();
				}else if(boton==2){
					disminuir_foto();
				}else{
					expande_foto();
				}
			}
			
			function save_foto(){
				var ob = window.opener.document.getElementById('<?php echo $fields.$id; ?>');
				if(obj('urlfoto').value!=""){
					ob.value=obj('urlfoto').value;
					_alert("Se ha guardado la foto correctamente");
				}else{
					if(!_confirm("No ha cargado ninguna foto a&uacute;n &iquest;Desea Salir?")){
						return;
					}
				}
				//alert(ob.value);
				window.close();
				window.opener.focus();
			}
		</script>
		<style type="text/css">
			button img{
				padding-left:1px;
			}
		</style>
	</head>
	<body style="overlow:none;margin:0px;">
		<div style="width:100%;height:100%;" sstyle="width:700px;height:600px;">
			<div id="marco_foto" style="height:530px; width:100%; overflow:none;padding:0px; background:#000000;color:#FFFFFF;">
				<iframe id="ffoto" name="ffoto" style="width:100%;height:100%" src="sa_foto_view.php?name=<?php echo ($nombre); ?>"></iframe>
			</div>
			<div style="height:70px; overflow:none; width:100%; ">
				<form method="post" id="formulario_foto" name="formulario_foto" target="cargador_foto" enctype="multipart/form-data">
					<table class="hidden_table" style="width:100%;">
						<tbody>
							<tr>
								<td>Nombre de la im&aacute;gen:<input type="hidden" id="urlfoto" name="urlfoto" readonly="readonly" value="<?php echo $nombre; ?>" /></td>
								<td id="desc"><?php echo ($desc);?> </td>
								<td align="center">
									<button type="button" title="Aumentar" onmousedown="aumentar_foto();" onmouseup="mouseup=true;" onmouseout="mouseup=true;"><img border="0" src="<?php echo geturl('img/iconos/plus.png'); ?>" /></button>
									<button type="button" title="Disminuir" onmousedown="disminuir_foto();" onmouseup="mouseup=true;" onmouseout="mouseup=true;"><img border="0" src="<?php echo geturl('img/iconos/minus.png'); ?>" /></button>
									<button type="button" title="Expander a pantalla" onmousedown="expande_foto();"><img border="0" src="<?php echo geturl('img/iconos/expand.png'); ?>" /></button>
								</td>
							</tr>
							<tr>
								<td>Cargar imagen</td>
								<td> <input id="foto" name="foto" type="file" onchange="cargar_foto();" /></td>
								<td align="center">
									<button type="button" onclick="save_foto();"><img border="0" src="<?php echo geturl('img/iconos/save.png'); ?>" />Guardar</button>
								</td>
							</tr>
						</tbody>
					</table>
				</form>
			</div>
		</div>
		<iframe name="cargador_foto" id="cargador_foto" style="display:none;"></iframe>
	</body>
</html>