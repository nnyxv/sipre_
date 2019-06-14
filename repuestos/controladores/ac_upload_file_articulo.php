<?php
include("../../connections/conex.php");
	
for ($cont = 0; $cont <= $_POST['hddCantCodigo']; $cont++) {
	$codArticulo[] = str_replace(" ","",$_POST['txtCodigoArticulo'.$cont]);
}
$codArticulo = implode(";",$codArticulo);

$rutaImg = editarImagen($_FILES['fleUrlImagen'],"../../upload/fotos_repuestos/", "../upload/fotos_repuestos/","Art_".$codArticulo);

// elimina del servidor la imagen anterior solo si existe
if(file_exists("../".$_POST['hddUrlImagen']) && $_POST['hddIdArticulo'] == "")
	unlink("../".$_POST['hddUrlImagen']);
?>
<script>
window.parent.document.getElementById('imgArticulo').src = "<?php echo $rutaImg; ?>";
window.parent.document.getElementById('hddUrlImagen').value = "<?php echo $rutaImg; ?>";
</script>