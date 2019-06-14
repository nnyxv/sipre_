<?php session_start();
include("FuncionesPHP.php");
?>
<script language="javascript">
function SobreFila(sDesCuen,obj){
	if (document.frmRenglonesDiarios.sDesactivarColor.value != "SI"){
		obj.style.color='white';
		obj.bgColor='#000066';
	}
}

function FueraFila(sDesCuen,obj){
	if (document.frmRenglonesDiarios.sDesactivarColor.value != "SI"){
		obj.style.color='Black';
		obj.bgColor='white';
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



<form name="frmRenglonesDiarios"> 

<table width="100%" border="0" class="texto_9px"> 

<?php 
$con = ConectarBD();
$SqlStr = "select codigo
		,descripcion
		,debe
		,haber
		,numero
		,DT
		,CT
		,documento
		,fecha
		from movimientemp where debe <> 0 or haber <> 0";

$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr); 
$TotalDebe = 0;
$TotalHaber = 0;

if (NumeroFilas($exec)>0){
	$iFila = -1;
    while ($row = ObtenerFetch($exec)) {
    	$iFila++;
    	$codigo = trim(ObtenerResultado($exec,1,$iFila)) ; 
        $descrip = trim(ObtenerResultado($exec,2,$iFila));				
		$debe = ObtenerResultado($exec,3,$iFila);				
		$haber = ObtenerResultado($exec,4,$iFila);				
		$DT = trim(ObtenerResultado($exec,6,$iFila));				
		$CT = trim(ObtenerResultado($exec,7,$iFila));				
		$documento = trim(ObtenerResultado($exec,8,$iFila));		
		$fecha = trim(ObtenerResultado($exec,9,$iFila));				
		$fechaComp = trim(ObtenerResultado($exec,9,$iFila));				
		$TotalDebe = bcadd($TotalDebe,$debe,2);
		$TotalHaber = bcadd($TotalHaber,$haber,2);
?>
	<tr id="<?php print('FilaRenglon'.trim($iFila)); ?>"   style="cursor:pointer"   onMouseOut="FueraFila('',this);" onMouseMove="SobreFila('',this);" >
    	<td  width="110"  class="RenglonesMov"><?=trim($codigo)?></td>		
        <td width="25"  class="RenglonesMov"><?=trim($CT)?></td>		
		<td width="250"  class="RenglonesMov"><?=trim($descrip)?></td>		
		<td width="150"  align="left" class="RenglonesMov"><?=number_format(trim($debe),2)?></td>		
		<td width="150"   align="left" class="RenglonesMov"><?=number_format(trim($haber),2)?></td>	
		<td width="25"  class="RenglonesMov"><?=trim($DT)?></td>		
		<td width="100"  class="RenglonesMov"><?=trim($documento)?></td>	
	</tr>
<?php 		
		
	}
}			
	
?>
</table>
<script language="javascript">
     parent.document.GenerarAsientosTemporal.TotalDebe.value = "<?=number_format(trim($TotalDebe),2)?>";
	 parent.document.GenerarAsientosTemporal.TotalHaber.value = "<?=number_format(trim($TotalHaber),2)?>";
	 parent.document.GenerarAsientosTemporal.Fecha.value = "<?=obFecha(trim($fechaComp))?>";
</script>

	<input name="sDesactivarColor" type="hidden" value="NO">
</form> 