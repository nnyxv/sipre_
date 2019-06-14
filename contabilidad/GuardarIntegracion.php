<?php session_start();?>

</script>
<style type="text/css">
<!--
@import url("estilosite.css");
-->
</style>
<?php
include_once('FuncionesPHP.php');
$con = ConectarBD();
$idEncabezado = $_REQUEST['pidModulo'];
$cuenta = $_REQUEST['ptcodigoG'];
$sucursal = $_REQUEST['sucursal'];
     		
	$SqlStr = " select cuentageneral from encintegracionsucursal x1 where x1.id_enc_integracion = $idEncabezado and x1.sucursal = $sucursal";
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr);
	if (NumeroFilas($exec)>0){
		$row = ObtenerFetch($exec);
		$SqlStr = " update encintegracionsucursal set cuentaGeneral = '$cuenta'
		where id_enc_integracion = $idEncabezado and sucursal = $sucursal";
		$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	} else {
		$SqlStr ="insert into encintegracionsucursal(id_enc_integracion,sucursal,cuentaGeneral)
		values ('$idEncabezado','$sucursal','$cuenta')";
		$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr);
	}
?>