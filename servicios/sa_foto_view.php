<?php

//lo usa fotos fallas sa_inventario_frame.php sa_inventario_foto.php

	if(isset($_GET['error'])){
		$error=$_GET ['error'];
		if($error==1){
			echo '<body style="margin-bottom:2px;background:#000000;color:#FFFFFF;">Cancelado: El archivo enviado no es del tipo *.jpg / *.png o supera los 2 MB</body>';
		}else{
			echo '<body style="margin-bottom:2px;background:#000000;color:#FFFFFF;">Cancelado: El archivo enviado no es del tipo *.jpg / *.png o supera los 2 MB</body>';
		}
		exit;
	}
	if($_GET['name']==''){
		echo '<body style="margin-bottom:2px;background:#000000;color:#FFFFFF;">No hay foto cargada</body>';
		exit;
	}
?>

<html>
<body style="margin-bottom:2px;background:#000000;" onload="parent.expande_foto();">
<img border="0" id="cfoto" name="cfoto" src="fotos/<?php echo ($_GET['name']); ?>" style="width:100%;position:absolute;z-order:-1;" alt="Foto no cargada" title="" onmousedown="return parent.expander(event);" onmouseup="parent.mouseup=true;" />
</body>
</html>