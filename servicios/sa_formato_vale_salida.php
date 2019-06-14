<?php //SOLO LO MUESTRA ac_sa_orden_form.php cuando se genera el vale de salida ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Impresión de Vales de Salida</title>
<link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />

<script>
function verificarImpresion()
{
	if(confirm("Desea salir de la impresion?"))
	{
		//window.location.href = "index.php";
		//window.location.href = "index.php";
		window.location.href = "sa_devolucion_vale_salida_list.php";		
		return true;
	}
	else
		return false;
}
</script>
</head>
<body>
<table align="center" width="70%" border="0" cellpadding="0" cellspacing="0">
  
  <tr>
    <td>&nbsp;</td>
    <td align="right"><input type="button" name="btnSalir" id="btnSalir" value="Salir" onclick="return verificarImpresion();" /></td>
  </tr>
  <tr>
    <td colspan="2">&nbsp;</td>
  </tr>
  <tr>
    <td colspan="2"><iframe src="<?php echo sprintf("sa_devolucion_vale_salida_pdf.php?valBusq=%s", $_GET['valBusq']);?>" width="100%" height="800" ></iframe></td>
  </tr>
</table>
</body>
</html>
