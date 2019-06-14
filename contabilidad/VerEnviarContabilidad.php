<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<title>.: SIPRE 2.0 :. Contabilidad - Movimientos Contables</title>
<meta http-equiv="Content-Type"content="text/html; charset=iso-8859-1">

<link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
<link rel="stylesheet" type="text/css" href="../js/domDragContabilidad.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    <link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    <script type="text/javascript" src="notifIt.js"></script>
	<link rel="stylesheet" type="text/css" href="notifIt.css">
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>

</head>
<body>
<?php
include_once('FuncionesPHP.php');
 $con = ConectarBD();
 if($idDia != "01-01-1900"){
 	    $SqlStr="select a.codigo,b.descripcion,a.desripcion,a.debe,a.haber,a.documento,a.tipo_accion,c.descripcion from movenviarcontabilidad a
			left join cuenta b on a.codigo = b.codigo
			left join encabezadointegracion c on a.tipo_accion = c.id
			where fecha = '$idDia'
			and a.ct = '$idct'
			and a.cc = '$idcc'
			order by comprobant,documento,a.tipo ";
			
		$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		$totalrow = mysql_num_rows($exc);
		if ($totalrow>0) {
				echo "<table  width='100%'  name='mitabla'  border='0'  align='center'>";
				echo "<tr class='tituloColumna'><td style='background:#58ACFA' align='center'><b>C&oacute;digo</td>
			 	<td style='background:#58ACFA' align='center' ><b> Cuenta</td>
			 	<td style='background:#58ACFA' align='center'><b> Descripci&oacute;n</td>
			 	<td style='background:#58ACFA' align='center'><b> Debe</td>
			 	<td style='background:#58ACFA' align='center'><b> Haber</td>
			 	<td style='background:#58ACFA' align='center'><b> Documento</td></tr>";
			}
		$documentoAnt = "";
		$color = "#A4A4A4";
		$sumd = 0;
		$sumh = 0;
		while ($row=ObtenerFetch($exc)){
				//$colorb = "background-color:white";
		        $codigo= $row[0];
				$descuenta= $row[1];		
				$desmov = $row[2];
				$debe = number_format($row[3],2);
				$haber =number_format($row[4],2);
				$documento = $row[5];
				$tipo_accion = $row[6];
				$descripcion_accion = $row[7];
				$sumd = $sumd+$row[3];
				$sumh = $sumh+$row[4];
				if ($documento != $documentoAnt){
				      if($color != ""){
							$color = "";
					   }else{
							$color = "#C4F3FF";
                       }
                  $documentoAnt = $documento;					   
				}

				if ($codigo == "") {
		        		$codigo = "00000000000";
		        		$cuenta_cero = 0;
		        		$descuenta = "REVISAR INTEGRACI&Oacute;N";
		        		$colorb = "background-color:#F5A9BC";
		        }else{
		        	$colorb = $color;
		        }

				echo "<tr style='background:$color'>";
				echo "<td style=\"cursor:help;$colorb\">
						<font size=-1 title=\"($tipo_accion) - $descripcion_accion\">
							$codigo
				 		</font>
					</td>
					<td style=\"$colorb\"><font size=-1>
						$descuenta
						</font>
					</td>
					<td style=\"$colorb\"><font size=-1>
						$desmov
						</font>
					</td>
					<td align=right style=\"$colorb\"><font size=-1>
						$debe
						</font>
					</td>
					<td align=right style=\"$colorb\"><font size=-1>
						$haber</font>
					</td>
					<td style=\"$colorb\"><font size=-1>
						$documento</font>
					</td>
					</tr>";
				echo '<input type="hidden" class="debe" value="'.str_replace(",","",$debe).'">';
				echo '<input type="hidden" class="haber" value="'.str_replace(",","",$haber).'">';
				echo '<input type="hidden" class="cuenta_ero" value="'.$cuenta_cero.'">';
		}
		$h = number_format($sumh,2);
		$d = number_format($sumh,2);
		$e = 10;

        if ($totalrow>0) {
/*			echo "<tr class='tituloColumna'><td colspan='3' style='border:0;text-align:right;font-weight:bold'>Total:</td>";*/
			echo "<tr><td class='tituloCampo' colspan='3' style='border:0;text-align:right;font-weight:bold'>Total:</td>";
			echo "<td style='background:$colorh'><font size=-1>
					<input id=\"totald\" type=\"text\" value=\"0\" readonly>
					</font></td>";
			echo "<td  style='background:$colorh'><font size=-1>
					<input id=\"totalh\" type=\"text\" readonly>
					</font></td></tr>";

			echo "</table>";
				
		}
		
}
?>
<?php if ($totalrow>0) { ?>
<script>
   debetotal = 0;
   habertotal = 0;
  
		$(".debe").each(
			function(index, value) {
			debetotal = debetotal + eval($(this).val());
		}
		);

		$(".haber").each(
			function(index, value) {
			habertotal = habertotal + eval($(this).val());
		}
		);

		$("#totald").val(debetotal.toFixed(2));
		$("#totalh").val(habertotal.toFixed(2));

		if(debetotal.toFixed(2)!=habertotal.toFixed(2)) {
	 		$("#totald").css({ color: "#FFFFFF", background: "#FF0000"});
	 		$("#totalh").css({ color: "#FFFFFF", background: "#FF0000" });
	 		$('#totald').css("font-weight", "bold");
	 		$('#totalh').css("font-weight", "bold");
	 		$('#totalh').css('text-align','right');
	 		$('#totald').css('text-align','right');
	 		//$("input[name='btnContabilizar']").attr("disabled", true);
	 		//$( "p" ).append( "<strong>* Notificacion : Verificar totales, asiento descuadrado</strong>" );
	 		notif({
				msg: "<b>Notificacion!</b> : Verificar totales, asiento descuadrado",
				type: "error",
				position: "center",
				color: "black"
			});
			

	 		//alert("Notificacion : Verificar totales, asiento descuadrado")

		}else{

			$("#totald").css({ color: "#black", background: "#9FF781" });
	 		$("#totalh").css({ color: "#black", background: "#9FF781" });
	 		$('#totald').css("font-weight", "bold");
	 		$('#totalh').css("font-weight", "bold");
	 		$('#totalh').css('text-align','right');
	 		$('#totald').css('text-align','right');
		}


		

</script>
<?php } ?>
</body>
</html>