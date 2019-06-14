<?php
include("../../connections/conex.php");
//var_dump($_FILES['fleUrlImagen']);
$rutaImg = editarImagen($_FILES['fleUrlImagen'],"../img/fotos_repuestos/", "img/fotos_repuestos/","Art");

// elimina del servidor la imagen anterior solo si existe
if(file_exists("../".$_POST['hddUrlImagen']) && $_POST['hddIdArticulo'] == "")
	unlink("../".$_POST['hddUrlImagen']);
?>
<script>
window.parent.document.getElementById('imgArticulo').src = "<?php echo $rutaImg; ?>";
window.parent.document.getElementById('hddUrlImagen').value = "<?php echo $rutaImg; ?>";

</script>