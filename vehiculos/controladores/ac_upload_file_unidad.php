<?php
include("../../connections/conex.php");
$rutaImg = editarImagen($_FILES['fleUrlImagen'],"../../upload/fotos_unidades/", "../upload/fotos_unidades/","Unidad_".$_POST['txtNombreUnidadBasica']);

// elimina del servidor la imagen anterior solo si existe
if(file_exists("../".$_POST['hddUrlImagen']) && $_POST['hddIdUnidadBasica'] == "")
	unlink("../".$_POST['hddUrlImagen']);
?>
<script>
window.parent.document.getElementById('imgArticulo').src = "<?php echo $rutaImg; ?>";
window.parent.document.getElementById('hddUrlImagen').value = "<?php echo $rutaImg; ?>";
</script>