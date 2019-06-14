<?php
$valBusq = $_GET['valBusq'];

$valCadBusq = explode("|", $valBusq);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Registro de Compra</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    
    <script>
    function verificarImpresion() {
        if (confirm('Desea salir de la impresion de la Factura?')) {
		<?php if ($valCadBusq[0] == 1 && $valCadBusq[2] > 0) { ?>
			verVentana('../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq=<?php echo $valCadBusq[2]; ?>', 400, 300);
		<?php } ?>
		
		<?php if ($valCadBusq[3] > 0) { ?>
			verVentana('../tesoreria/reportes/te_imprimir_constancia_retencion_pdf.php?id=<?php echo $valCadBusq[3]; ?>&documento=3', 400, 300);
		<?php } ?>
			
			verVentana('reportes/iv_articulo_etiqueta_pdf.php?valBusq=<?php echo $_GET['valBusq2']; ?>', 400, 300);
			window.location.href = "iv_registro_compra_list.php";
            return true;
        } else
            return false;
    }
    </script>
</head>

<body>
<center>
    <table width="75%" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td>&nbsp;</td>
        <td align="right"><button type="button" name="btnSalir" id="btnSalir" onclick="return verificarImpresion();">Salir</button></td>
    </tr>
    <tr>
        <td colspan="2">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="2"><iframe src="<?php echo sprintf("reportes/iv_registro_compra_pdf.php?valBusq=%s", $valCadBusq[1]);?>" width="100%" height="1120" ></iframe></td>
    </tr>
    </table>
</center>
</body>
</html>