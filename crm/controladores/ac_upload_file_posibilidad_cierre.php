<?php
include("../../connections/conex.php");

$rutaImg = editarImagen($_FILES['fleUrlImagen'],"../img/foto_posibilidad_cierre/", "img/foto_posibilidad_cierre/","Posible_cierre");

// elimina del servidor la imagen anterior solo si existe
if(file_exists("../".$_POST['hddUrlImagen']) && $_POST['hddIdPosibilidadCierre'] == "")
	unlink("../".$_POST['hddUrlImagen']);
?>
<script>
window.parent.document.getElementById('imgPosibleCierre').src = "<?php echo $rutaImg; ?>";
window.parent.document.getElementById('hddUrlImagen').value = "<?php echo $rutaImg; ?>";
</script>