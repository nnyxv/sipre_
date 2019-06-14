<?php
include("FuncionesPHP.php");
$con = ConectarBD(); 

$SqlStr='SELECT fec_proceso FROM parametros ';
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

if(NumeroFilas($exc) > 0){
	$fproceso = trim(ObtenerResultado($exc,1));
}

$dDesde1  = $fproceso;
$anomes = obFecha($fproceso,"A").obFecha($fproceso,"M");

$desAnomes = $_POST["desAno"].$_POST["desMes"];

$anoviene  = $_POST["desAno"];
$mesviene  = $_POST["desMes"];

//se agrego para que tome en cuenta la fecha del formulario:
$ultimodia=cal_days_in_month(CAL_GREGORIAN,$mesviene,$anoviene);
$f = $anoviene."/".$mesviene."/".$ultimodia;

if($anomes == $desAnomes){
	$SqlStr='SELECT MAX(fecha) AS fecha FROM enc_diario';
	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	if(NumeroFilas($exc)>0){
		$FechaDocumento = trim(ObtenerResultado($exc,1));
	}
} else {
	$SqlStr = "SELECT MAX(fecha) AS fecha FROM enc_dif
			WHERE MONTH(fecha) = $mesviene AND YEAR(fecha) = $anoviene";
	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	if(NumeroFilas($exc)>0){
		$FechaDocumento = trim(ObtenerResultado($exc,1));
	}	
}
		
$SqlStr = "DELETE FROM movimientemp ";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$SqlStr= "INSERT INTO movimientemp
		(codigo,descripcion ,debe ,haber,numero ,DT ,CT,documento,fecha,im)               
			SELECT MAX(b.coddebe) AS contable,MAX(b.descripcion) AS descripcion,
				SUM(dep.valordepreciado) AS debe,0 AS haber,1,'00','00','".$FechaDocumento."','".$FechaDocumento."','00'
			FROM  con_depreciacion dep 
				INNER JOIN deprecactivos ac ON ac.codigo = dep.codigoactivos AND ac.nodeprec = 'SI'  
				INNER JOIN tipoactivo b ON b.codigo = ac.tipo 
			WHERE #dep.anomes BETWEEN (".$desAnomes.") AND (".$hasAnomes.")
				 dep.anomes = ".$desAnomes." 
			GROUP BY ac.tipo ORDER BY ac.tipo";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr); 

$SqlStr= "INSERT INTO movimientemp
		(codigo ,descripcion ,debe ,haber  ,numero ,DT ,CT,documento ,fecha,im)               
			SELECT MAX(b.codhaber) AS contable,MAX(b.descripcion) AS descripcion,
				0 AS debe,SUM(dep.valordepreciado) AS haber,2,'00','00','".$FechaDocumento."','".$FechaDocumento."','00'
			FROM  con_depreciacion dep 
				INNER JOIN deprecactivos ac ON ac.codigo = dep.codigoactivos AND ac.nodeprec = 'SI'  
				INNER JOIN tipoactivo b ON b.codigo = ac.tipo 
			WHERE #dep.anomes BETWEEN (".$desAnomes.") AND (".$hasAnomes.")
				 dep.anomes = ".$desAnomes." 
			GROUP BY ac.tipo	ORDER BY ac.tipo";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

//se agrega la variable $f para que sea enviada la fecha 
header("Location:GenerarAsientoTemporal.php?parTipo=D&aniomes=".$f);
?>