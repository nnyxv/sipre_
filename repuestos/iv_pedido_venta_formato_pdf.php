<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Pedido de Venta</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <script>
    function verificarImpresion() {
        if (confirm('Desea salir de la impresion del Pedido de Venta?')) {
            window.location.href = "iv_pedido_venta_list.php";
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
        <td colspan="2"><iframe src="<?php echo sprintf("reportes/iv_pedido_venta_pdf.php?valBusq=%s", $_GET['valBusq']);?>" width="100%" height="1120" ></iframe></td>
    </tr>
    </table>
</center>
</body>
</html>