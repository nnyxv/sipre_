<?php session_start();
include_once('FuncionesPHP.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<script language='javascript'>
function SobreFila(sDesCuen,obj){
//		if (document.ConfiguracionReportes.sDesactivarColor.value != "SI"){
			//	if (parent.document.frmDiarios.oNumero.value != ""){
					soCon = obj.id;
					//alert('soCon');
					var objFila = document.all(soCon);
					objFila.bgColor='white';
					objFila.color='Black';
			//	}	
				  
					obj.style.color='white';
					obj.bgColor='#000066';
			//		parent.document.frmDiarios.oDesCuenta.value=sDesCuen;
		//}
}

function FueraFila(sDesCuen,obj){
		//if (document.ConfiguracionReportes.sDesactivarColor.value != "SI"){
				obj.style.color='Black';
				obj.bgColor='white';
         //	    parent.document.frmDiarios.oDesCuenta.value=sDesCuen;
		//}		
}
function Seleccionar(scuenta){
                parent.document.ConfiguracionCuentas.method='post';
				parent.document.ConfiguracionCuentas.target='FrameDetalle2';
				parent.document.ConfiguracionCuentas.CuentaSelec.value = scuenta;
				parent.document.ConfiguracionCuentas.StatusSelec.value = 'IN';
				parent.document.ConfiguracionCuentas.action='DetalleConfig2.php';
				parent.document.ConfiguracionCuentas.submit();
}
</script>
<meta http-equiv="Content-Type"content="text/html; charset=iso-8859-1">
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
<link rel="stylesheet" type="text/css" href="../js/domDragContabilidad.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
<body>


<form name="">

<table width="450" align="center" border="0" class="Acceso">

<?php
$con = ConectarBD();
$sCampos= "codigo ";
$sCampos.= ",descripcion";   
$SqlStr = "Select " .$sCampos. " from cuenta where codigo like '%". $TexBuscar ."%' or descripcion  like '%". $TexBuscar ."%' Order by codigo";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

if (NumeroFilas($exc)>0){
	$iFila = -1;
	while ($row = ObtenerFetch($exc)) {
		$iFila++;
		$codigo = trim(ObtenerResultado($exc,1,$iFila));  
		$descripcion= trim(ObtenerResultado($exc,2,$iFila));  
?>	
	<tr  id="<?php print('FilaRenglon'.trim($codigo)); ?>"   style="cursor:pointer" bgColor='white' onMouseOut="FueraFila('',this);" onMouseMove="SobreFila('<?=trim($DesCuenta)?>',this);" >
    	<td class='texto_12px' width="65"  align="left" height="16" valign="top"  onClick="<?php print("Seleccionar('".$codigo."')"); ?>" ><a><?php print($codigo);  ?></a></td> 
    	<td class='texto_12px' width="340"  align="left" height="16" valign="top" onClick="<?php print("Seleccionar('".$codigo."')"); ?>" ><a><?php print($descripcion);  ?></a></td> 
	</tr>
 <?php 	
	}   //while($row = mysql_fetch_row($exc))  
}
?>
</table>
</form>


</div>

</body>