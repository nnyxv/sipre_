<?php session_start();?>
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

<?php
include_once('FuncionesPHP.php');
$con = ConectarBD();	
$SqlStr = "SELECT
	a.ct
	,max(b.descripcion)
FROM
	movenviarcontabilidad a
	,transacciones b
WHERE
	a.ct = b.codigo
	AND cc = '$idcc'
GROUP BY a.ct
ORDER BY b.descripcion";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
?>
<!--<div id="divGeneralPorcentaje">-->
<!--<div class="noprint"><?//php include("banner_contabilidad2.php"); ?></div> -->
<?php if(mysql_num_rows($exc) > 0 ){?>			
<!--		 	Transacci&oacute;n:--> 
		 	<select name="idct" onclick='LimpiaDetalle()'>
				<option value="">Seleccione...</option>
				<?php 
			
				while ($row=ObtenerFetch($exc)){ 
							$ct = $row[0];
							$des = $row[1];
							echo "<option value=$ct>$des</option>";
				}
			}else{ ?>
<!--			 	Transacción: -->
			<select name="idct" onchange="LimpiaDetalle();">
					<option value="">Seleccione...</option>
			</select>
<?php } ?>