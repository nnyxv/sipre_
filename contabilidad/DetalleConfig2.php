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
function Quitar(scuenta){
 if (confirm('Desea eliminar la cuenta')){
                document.DetalleConfig2.method='post';
				document.DetalleConfig2.CuentaSelec.value = scuenta;
    			document.DetalleConfig2.StatusSelec.value = 'DE';
				document.DetalleConfig2.action='DetalleConfig2.php';
				document.DetalleConfig2.submit();
   }				
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

</head>


<body>



<?php 
if ($StatusSelec == 'IN'){
	$con = ConectarBD();
	$sCampos= "count(codigocuenta)";
	$SqlStr = "Select " .$sCampos. " from cuentasconfiguradas  where codigocuenta = '$CuentaSelec' 
			and codigoformato= '$T_formato' and numerorenglon= $Tnumero";
	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	$contador=0;
	if (NumeroFilas($exc)>0){
    	$contador=trim(ObtenerResultado($exc,1));
    }
    if ($contador > 0 ){
		echo "<script language='javascript'>
			      alert('La cuenta ya ha sido seleccionada')
			  </script>";
			  
	}else{
		$sTabla='cuentasconfiguradas';
        $sValores='';
        $sCampos='';
        $sCampos.='CodigoFormato';
        $sValores.="'".$T_formato."'";
        $sCampos.=',NumeroRenglon';
        $sValores.=",'".$Tnumero."'";
        $sCampos.=',CodigoCuenta';
        $sValores.=",'".$CuentaSelec."'";
        $sCampos.=',PoN';
        $sValores.=",'$TPoN'";
        $SqlStr='';
        $SqlStr="INSERT INTO ".$sTabla." (".$sCampos.")  values (".$sValores.")";
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	}

}
if ($StatusSelec == 'DE'){
	$con = ConectarBD();
    $SqlStr='';
    $SqlStr="DELETE FROM cuentasconfiguradas where codigocuenta = '$CuentaSelec' 
			and codigoformato= '$T_formato' and numerorenglon= $Tnumero";
    $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
}
?>

<form name="DetalleConfig2">

<table width="450"  align="center"  border="0" class="Acceso">

<?php
$con = ConectarBD();
$sCampos= "a.codigocuenta ";
$sCampos.= ",b.descripcion,a.PoN";   
$SqlStr = "Select " .$sCampos. " from cuentasconfiguradas a, cuenta b  where 	   a.codigocuenta = b.codigo 
 and codigoformato= '$T_formato' and numerorenglon= $Tnumero";

$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

if (NumeroFilas($exc)>0){
	$iFila = -1;
	while ($row = ObtenerFetch($exc)) {
		$iFila++;
		$codigo = trim(ObtenerResultado($exc,1,$iFila));  
		$descripcion= trim(ObtenerResultado($exc,2,$iFila));  
		$PoN = trim(ObtenerResultado($exc,3,$iFila));  
		if ($PoN == '+'){
   			$PoN = '';
		}
?>	
	<tr  id="<?php print('FilaRenglon'.trim($codigo)); ?>" onClick="<?php print("Quitar('".$codigo."')"); ?>"  style="cursor:pointer" bgColor='white' onMouseOut="FueraFila('',this);" onMouseMove="SobreFila('<?=trim($DesCuenta)?>',this);" >
    	<td class='texto_12px' width="98"  align="left" height="16" valign="top"><a><?php print($PoN.$codigo);  ?></a></td> 
    	<td class='texto_12px' width="350"  align="left" height="16" valign="top"><a><?php print($descripcion);  ?></a></td> 
	</tr>
 <?php 	
	}   //while($row = mysql_fetch_row($exc))  
}
?>

<input type="hidden" name="T_formato" value="<?php print($T_formato); ?>">
<input type="hidden" name="Tnumero" value="<?php print($Tnumero); ?>">
<input type="hidden" name="CuentaSelec" value="">
<input type="hidden" name="StatusSelec" value="">

</table>
</form>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>


</body>