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
$SqlStr = "Select a.fecha from movenviarcontabilidad a where ct ='$idct' and cc = '$idcc' group by fecha order by fecha ";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr); 
?>
<!--<div id="divGeneralPorcentaje">-->
<!--<div class="noprint"><?//php include("banner_contabilidad2.php"); ?></div> -->
	<?php if(mysql_num_rows($exc) > 0 ){?>			
		
	<!--	D&iacute;a: -->
			<select name="idDia" onclick='MostrarDia()'>
				<option value="01-01-1900">Seleccione...</option>
				<?php 
			
				while ($row=ObtenerFetch($exc)){ 
					$fecha = $row[0];
					echo "<option value=$fecha>".date("d-m-Y",strtotime($fecha))."</option>";
				}
			}else{?>


		<!--	D&iacute;a: -->
			<select name="idDia" onclick='MostrarDia()'>
				<option value="01-01-1900">Seleccione...</option>

<?php } ?>	
			</select>
<!--<div class="noprint">
	<?//php include("pie_pagina.php"); ?>
</div>-->