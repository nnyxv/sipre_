<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<title>Servicios - Impresión de Factura de Servicios</title>
<link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />

<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>

<link rel="stylesheet" type="text/css" href="../style/styleRafk.css">

<!--- VERSION IMPRIMIBLE DE TODOS VARIOS -->
<script>
function verificarImpresion($tipo_doc, $esCotizacion)
{
	if(confirm("Desea salir de la impresion?"))
	{
		if($tipo_doc == 0)
		{
			if($esCotizacion == 0 || $esCotizacion == 1)
			{
				window.location.href = "sa_orden_servicio_list.php";
				return true;
			}
			else
			{
				window.location.href = "index.php";
				return true;
			}
		}
		else
		{
			if($esCotizacion == 0)
			{
				window.location.href = "sa_presupuesto_list.php";
				return true;
			}
			else
			{
				window.location.href = "sa_cotizacion_list.php";
				return true;
			
			}	
		}	
	}
	else
		return false;
}
</script>
</head>
<body>
<table width="70%" border="0" cellpadding="0" cellspacing="0" style="margin:auto; ">  
  <tr>
    <td>&nbsp;</td>
    <td align="right">&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td align="right"><input type="button" name="btnSalir" id="btnSalir" value="Salir" onclick="return verificarImpresion(<?php if ($_GET['doc_type'] == 1) echo $var = 1;
	else
		 echo $var = 0;
		?>,<?php echo $_GET['acc'];?>);" /></td>
  </tr>
  <tr>
    <td colspan="2">&nbsp;</td>
  </tr>
  <tr >
    <td colspan="2" ></td>
  </tr>
  <tr>
    <td colspan="2">&nbsp;</td>
  </tr>
  <tr>
    <td colspan="2"><iframe src="<?php echo sprintf("sa_imprimir_presupuesto_pdf.php?valBusq=%s|%s|%s", $_GET['id'], $_GET['doc_type'], $_GET['acc']);?>" width="100%" height="800" ></iframe></td>
  </tr>
</table>
</body>
</html>

