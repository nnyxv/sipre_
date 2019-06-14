<?php session_start();
include_once('FuncionesPHP.php');
$con = ConectarBD();
$SqlStr = "Select sum(a.debe),sum(a.haber) from  $Cod.movimien a";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);


echo " <table align='center'>
 			<tr>
				<td align='left' colspan=2>Totales:
				</td>
			</tr>";
			
 while ($row=ObtenerFetch($exc)){ 
	if (is_null($row[0])) {
	   $row[0] = 0;
	}
	if (is_null($row[1])) {
		$row[1] = 0;
	}
		
	$debe = number_format($row[0],2);
	$haber = number_format($row[1],2);
	echo " 
			<tr>
				<td align='right'>
					Debe
				</td>
				<td align='right'>
					<input readonly name='TextDebe' type='text' maxlength=23 size=20  value=$debe> 		
				</td>
			</tr>
			<tr>
				<td align='right'>
					Haber
				</td>
				<td align='right'>
				    <input readonly name='TextHaber' type='text' maxlength=23 size=20  value=$haber> 		
				</td>
			</tr>";
		}	 
echo "</table>";
?>