<?php
header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );  // disable IE caching
header( "Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . " GMT" ); 
header( "Cache-Control: no-cache, must-revalidate" ); 
header( "Pragma: no-cache" );
define ('load_title','<html><body style="margin:0px; overflow:hidden;"><img border="0" src="loading.gif" /></body></html>');
//configuraciones

/*if(isset($_GET['rollback'])){
	if(copy("backup".$_GET['rollback'],$_GET['rollback'])){
		//echo '<script>window.open("uploader.php?name='.$_GET['rollback'].'","cambiovehiculo");</script>';
		echo 'Operaci&oacute;n cancelada<script>window.close();</script>';
	}
	exit;
}*/

/*if (isset($_GET['recent'])){
				echo '<html><body style="margin:0px; overflow:hidden;"><img border="0" src="'.$_GET['recent'].'" /></body></html>';
				exit;
}*/
$name_result=$_GET['name'];
if ($path==''){
	$path='autos';
}
if (isset($_POST['name_result'])){
	$name_result=$_POST['name_result'];
}
//if (isset($_POST['type'])){
	$type='jpg';
	$type2='png';
//}
if($name_result==""){
	echo "Configure el include";
}else{




	if(isset($_FILES['userfile'])){
		sleep(1);
		$nombre_archivo = $_FILES['userfile']['name'];
		$tipo_archivo = $_FILES['userfile']['type'];
		$tamano_archivo = $_FILES['userfile']['size'];
		//echo $tipo_archivo,' ',$tamano_archivo, ' ' ,$nombre_archivo ;
		//comprobar el archivo
		//echo strtolower ($nombre_archivo);exit;
		if (!(( stripos($nombre_archivo, $type) || stripos($nombre_archivo, $type2)) && ($tamano_archivo < 100000))) { //(strpos($tipo_archivo, "gif") || strpos($tipo_archivo, "jpeg") || strpos($tipo_archivo, "png")  ||
			echo '<script type="text/javascript" language="javascript">
				//function encarga(){
					parent.hideload(false,"");
				//}
				</script>Cancelado: El archivo enviado no es del tipo *.jpg / *.png o supera los 100 Kb';
		}else{
			//copia de seguridad
			/*if (file_exists($name_result)){
				$backup=true;
				copy($name_result,'backup'.$name_result);
			}*/
			/*if(!){
				echo "Ocurrió algún error al subir el fichero. No pudo guardarse.";	
			}else{*/
			if (move_uploaded_file($_FILES['userfile']['tmp_name'], $nombre_archivo)){
				/*if($backup){
					$cancel="parent.showcancel();";
				}*/
				echo '<html><script type="text/javascript" language="javascript">
				function encarga(){
					parent.hideload(true,"'.$nombre_archivo.'");
					
				}
				</script>';
				echo '<body onload="encarga();" style="margin:0px; overflow:hidden; text-align:center;"><img border="0" src="'.$nombre_archivo.'" /><br />
				';
				/*if (!$backup){
					echo 'Se esta cargando la imagen por primera vez, para cambiarla haga click en "Aceptar" y luego repita el proceso.';
				}*/
				/*if ($backup){
					echo '<form onsubmit="return false;"><button type="button" onclick="window.location=\'uploader.php?rollback='.$name_result.'\';"><img border="0" src="../iconos/delete.png" style="padding:2px; vertical-align:middle;" /> Cancelar</button>
				</form>';
				}*/
				echo '</body></html>';
			}else{
				echo "Ocurrió algún error al subir el fichero. No pudo guardarse.";
			}
			//}
		}
	}else{
?>

<html>
<head>
<title>Cambiar im&aacute;gen</title>
<script type="text/javascript" language="javascript">

var objeto=null;
var _cerrar=false;

function hideload(loaded,file){
	//window.open('pagedown.php?name=<?php echo $name_result; ?>&file='+file,'controles');
	var f = window.frames['controles'].document.getElementById('file');
	f.value=file;
	var loader = window.frames['controles'].document.getElementById('loader');
	loader.style.display="none";
	if (loaded){
		var aa = window.frames['controles'].document.getElementById('baceptar');
		aa.style.display="inline";
	}
}

/*function showcancel(){
	var bc = window.frames['controles'].document.getElementById('cancelar');
	var userfile = window.frames['controles'].document.getElementById('userfile');
	var aa = window.frames['controles'].document.getElementById('baceptar');
	bc.style.display="inline";
	userfile.style.display="none";
	aa.style.display="inline";
}*/


function cerrar(file){
	//window.parent.focus();
	var obj = window.opener.document.getElementById('<?php echo $name_result; ?>');
	obj.value='../upload/'+file;
	var obj2 = window.opener.document.getElementById('<?php echo $_GET['capa']; ?>');
	obj2.innerHTML='<img border="0" src="../upload/'+file+'" />';
	_cerrar=true;
	window.close();

}

function loadfile(load_frm){
	/*var frameload=window.frames['frameload'].document;
	frameload.open();
	frameload.write('<?php echo load_title ;?>');
	frameload.close();*/
	var loader = window.frames['controles'].document.getElementById('loader');
	loader.style.display="inline";
	load_frm.submit();
}

function cancelate(){
	var bc = window.frames['controles'].document.getElementById('cancelar');
	if(bc!=null){
		if (_cerrar==false){
			window.open('uploader.php?rollback=<?php echo $name_result; ?>','cambiovehiculo');
		}
	}
}

</script>
</head>

<frameset rows="*,165" frameborder="NO" border="0" framespacing="0" >
<frame src="wait.htm" src="wait.htm" name="frameload" id="frameload" title="frameload" />
<frame scrolling="no" src="pagedown.php?name=<?php echo $name_result; ?>" name="controles" id="controles" title="controles" />
<!--<frame src="UntitledFrame-2"></frameset>-->
<noframes><body>nf
</body>
</noframes>


</html>
<?php 
}//html
}//include
?>