<?php
include("../connections/conex.php");

if ($_POST['accTipoImg'] == 1) {
	$rutaImg = editarImagen($_FILES['fleUrlGrupo'],"../upload/fotos_empresas/", "upload/fotos_empresas/","Grup_".str_replace(array(" ",",","."),"",$_POST['txtEmpresa']));
	
	// elimina del servidor la imagen anterior solo si existe
	/*if(file_exists($_POST['fleUrlGrupo']) && $_POST['hddIdArticulo'] == "")
		unlink($_POST['hddUrlImgGrupo']);*/
} else if ($_POST['accTipoImg'] == 2) {
	$rutaImg = editarImagen($_FILES['fleUrlEmpresa'],"../upload/fotos_empresas/", "upload/fotos_empresas/","Emp_".str_replace(array(" ",",","."),"",$_POST['txtEmpresa']));
	
	// elimina del servidor la imagen anterior solo si existe
	/*if(file_exists($_POST['fleUrlEmpresa']) && $_POST['fleUrlEmpresa'] == "")
		unlink($_POST['hddUrlImgEmpresa']);*/
} else if ($_POST['accTipoImg'] == 3) {
	$rutaImg = editarImagen($_FILES['fleUrlFirmaAdmon'],"../upload/fotos_empresas/", "upload/fotos_empresas/","FirAdmon_".str_replace(array(" ",",","."),"",$_POST['txtEmpresa']));
	
	// elimina del servidor la imagen anterior solo si existe
	/*if(file_exists($_POST['fleUrlFirmaAdmon']) && $_POST['fleUrlFirmaAdmon'] == "")
		unlink($_POST['hddUrlImgFirmaAdmon']);*/
} else if ($_POST['accTipoImg'] == 4) {
	$rutaImg = editarImagen($_FILES['fleUrlFirmaTesoreria'],"../upload/fotos_empresas/", "upload/fotos_empresas/","FirTesoreria_".str_replace(array(" ",",","."),"",$_POST['txtEmpresa']));
	
	// elimina del servidor la imagen anterior solo si existe
	/*if(file_exists($_POST['fleUrlFirmaTesoreria']) && $_POST['fleUrlFirmaTesoreria'] == "")
		unlink($_POST['hddUrlImgFirmaTesoreria']);*/
} else if ($_POST['accTipoImg'] == 5) {
	$rutaImg = editarImagen($_FILES['fleUrlFirmaSello'],"../upload/fotos_empresas/", "upload/fotos_empresas/","FirSello_".str_replace(array(" ",",","."),"",$_POST['txtEmpresa']));
	
	// elimina del servidor la imagen anterior solo si existe
	/*if(file_exists($_POST['fleUrlFirmaSello']) && $_POST['fleUrlFirmaSello'] == "")
		unlink($_POST['hddUrlImgFirmaSello']);*/
}
?>
<script>
if (<?php echo $_POST['accTipoImg']; ?> == 1) {
	window.parent.document.getElementById('imgGrupo').src = "<?php echo $rutaImg; ?>";
	window.parent.document.getElementById('hddUrlImgGrupo').value = "<?php echo $rutaImg; ?>";
} else if (<?php echo $_POST['accTipoImg']; ?> == 2) {
	window.parent.document.getElementById('imgEmpresa').src = "<?php echo $rutaImg; ?>";
	window.parent.document.getElementById('hddUrlImgEmpresa').value = "<?php echo $rutaImg; ?>";
} else if (<?php echo $_POST['accTipoImg']; ?> == 3) {
	window.parent.document.getElementById('imgFirmaAdmon').src = "<?php echo $rutaImg; ?>";
	window.parent.document.getElementById('hddUrlImgFirmaAdmon').value = "<?php echo $rutaImg; ?>";
} else if (<?php echo $_POST['accTipoImg']; ?> == 4) {
	window.parent.document.getElementById('imgFirmaTesoreria').src = "<?php echo $rutaImg; ?>";
	window.parent.document.getElementById('hddUrlImgFirmaTesoreria').value = "<?php echo $rutaImg; ?>";
} else if (<?php echo $_POST['accTipoImg']; ?> == 5) {
	window.parent.document.getElementById('imgFirmaSello').src = "<?php echo $rutaImg; ?>";
	window.parent.document.getElementById('hddUrlImgFirmaSello').value = "<?php echo $rutaImg; ?>";
}
</script>