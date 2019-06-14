<?php
include("../../connections/conex.php");
//$rutaArch = editarArchivo($_FILES['fleUrlArchivo'],"../img/fotos_repuestos/", "img/fotos_repuestos/","Art");

$archivo = $_FILES['fleUrlArchivo'];
$directorio = "../reportes/tmp/";
$bd = "";
$tpImg = "Alm";

if (isset($archivo) && $archivo['name'] != "") {
	$tipo = explode("/",$archivo['type']);
	
	if ($archivo['size'] > 0 && $archivo['size'] < 1048576) { // 1048576 bytes = 1 megabyte
		if ($archivo['type'] == "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet") { // Firefox
		
			$nomArch = $tpImg.$_SESSION['idPostEmpCL'].date("dmYHis");
			
			$archExcel = $archivo['name'];
			
			move_uploaded_file($archivo['tmp_name'], $directorio.$archExcel);
			chmod($directorio.$archExcel,0777);
		
			$infoArchivo = getimagesize($directorio.$archExcel);
			
			// elimina del servidor la imagen anterior solo si existe
			$arrayArchivo = explode(".", $rowEmpresa['ruta']);
			if (file_exists($arrayArchivo[0].".".$arrayArchivo[1]) && strlen($arrayArchivo[0].".".$arrayArchivo[1]) > 4) {
				unlink($arrayArchivo[0].".".$arrayArchivo[1]);
			}
		} else {
			echo "<script>
			alert('Tipo de Archivo Inválido: ".$archivo['type']."');
			window.parent.errorJquery('divMsjImportar','Tipo de Archivo Inválido','1000');
			</script>";
			exit;
		}
		
		$rutaArch = $bd.$archExcel;
	} else {
		echo "<script>
		alert('Archivo Muy Pesado: ".$archivo['size']."');
		window.parent.errorJquery('divMsjImportar','Archivo Muy Pesado','1000');
		</script>";
		exit;
	}
}

// elimina del servidor la imagen anterior solo si existe
if (file_exists("../".$_POST['hddUrlArchivo']))
	unlink("../".$_POST['hddUrlArchivo']);
?>
<script>
if (window.parent.document.getElementById('hddUrlArchivo').value == '' && window.parent.document.getElementById('fleUrlArchivo').value.length > 0) {
	window.parent.mensajeJquery('divMsjImportar','Archivo Válido','5000');
	window.parent.document.getElementById('hddUrlArchivo').value = "<?php echo $rutaArch; ?>";
	
	if (eval((typeof('window.parent.xajax_vistaPreviaImportarFactura') != "undefined"))
	&& typeof('window.parent.xajax_vistaPreviaImportarFactura') != "function"
	&& !(window.parent.xajax_vistaPreviaImportarFactura)) {
	} else {
		window.parent.xajax_vistaPreviaImportarFactura(window.parent.xajax.getFormValues('frmImportarArchivo'));
	}
	
	if (!(window.parent.document.getElementById('btnGuardarImportarArchivo') == undefined)) {
		setTimeout(function(){
			window.parent.document.getElementById('btnGuardarImportarArchivo').disabled = false;
			window.parent.document.getElementById('btnCancelarImportarArchivo').disabled = false;
		},5000);
	}
}
</script>