<?php session_start();
include_once('FuncionesPHP.php');
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE 2.0 :. Contabilidad - Grabar Fecha</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    
	<script language="JavaScript"src="./GlobalUtility.js"></script>
	<script language= "javascript" >
    <!--*****************************************************************************************-->
    <!--************************VER CONFIGURACION DE REPORTE*************************************-->
    <!--*****************************************************************************************-->
    function Entrar(){
        document.ElegirFecha.target='_self';
        document.ElegirFecha.method='post';
        document.ElegirFecha.action='GrabarFecha.php';
        document.ElegirFecha.submit();
    }
    
    function SelTexto(obj){
        if (obj.length != 0){
            obj.select();
        }
    }
    </script>
</head>
<body>
<form name="GrabarFecha" method="post" action="GrabarFecha.php">
	<?php
    registrar("N");
	$con = ConectarBD();
	$sTabla='parametros';
	$sCondicion='';
	$sFecha = $xAFecha.'-'.$xMFecha.'-'.$xDFecha;
	$sCampos="fec_proceso= '$sFecha'";
	$SqlStr='update '.$sTabla.' set '. $sCampos;
	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
    
    echo "<script language='Javascript'>
    document.GrabarFecha.method='post';
    document.GrabarFecha.action='index_contabilidad.php';
    document.GrabarFecha.submit();
    </script>";
    $_SESSION["sFec_Proceso"]=$sFecha; ?>
</form>
</body>
</html>
