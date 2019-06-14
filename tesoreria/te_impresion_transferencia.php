<?php
    require_once ("../connections/conex.php");
    
    session_start();
    
    include ("../inc_sesion.php");
    
    require ('../controladores/xajax/xajax_core/xajax.inc.php');
    //Instanciando el objeto xajax
    $xajax = new xajax();
    //Configuranto la ruta del manejador de scritp
    $xajax->configure('javascript URI', '../controladores/xajax/');
    
    include("controladores/ac_te_impresion_transferencia.php");
    
    //$xajax->setFlag('debug',true);
    //$xajax->setFlag('allowAllResponseTypes', true);
    
    $xajax->processRequest();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Impresion de Transferencias</title>
<link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
<?php $xajax->printJavascript('../controladores/xajax/'); ?>
<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
<script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>
<link rel="stylesheet" type="text/css" href="clases/styleRafkLista.css">
<link rel="stylesheet" type="text/css" href="../style/styleRafk.css">


<script>
function verificarImpresion()
{
	if(confirm("La Transferencia se imprimio correctamente?"))
	{
		xajax_enviar($('hddId').value,$('hddacc').value);
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
    <td align="right"><input type="button" name="btnSalir" id="btnSalir" value="Salir" onclick="return verificarImpresion();" /><input type="hidden" name="hddId" id="hddId" value="<?php echo $_GET['id'];?>"></td>
    <input type="hidden" name="hddId" id="hddacc" value="<?php echo $_GET['acc'];?>"></td>
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
    <td colspan="2"><iframe src="<?php echo sprintf("reportes/te_imprimir_transferencia_pdf.php?id=%s", $_GET['id']);?>" width="100%" height="800" ></iframe></td>
  </tr>
</table>
</body>
</html>

