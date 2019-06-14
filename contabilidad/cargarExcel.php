<?php session_start();
include_once('FuncionesPHP.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Documento sin título</title>
<script>
	function maximizar(){
		window.moveTo(0,0);
		window.resizeTo(screen.availWidth, screen.availHeight);
		}
</script>

<style type="text/css">
.trResaltar1{
	background-color:#EBEBEB;
}
.trResaltar2{
	background-color:#FFFFFF;
}

.trSobre{
	/*background-color:#CCCCCC;
	color:#FFFFFF;*/

}
<!--
@import url("estilosite.css");
-->
</style>
<style type="Text/css">
</style>

</head>
<body >
<?php 
// Error reporting 
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);/**/

//INCLUIMOS LAS CLASE EXCEL
require_once('PHPExcel1.8.0/Classes/PHPExcel/IOFactory.php');	

//PARA CARGAR EL ARCHIVO AL SERVIDOR
	if(!$_FILES['archivo']['size'] > 0 ){ //compruebo que se ha cargado
	
		echo "<script>
				alert('Tienes que elegir un archivo \\n Error: ". $_FILES['archivo']['error']."');
					window.location='frmImportarExcel.php';
			</script>";
	
		//echo 'Ha habido un error, tienes que elegir un archivo<br/>';
		//echo '<a href="frmImportarExcel.php">Cargar archivo</a>';
	} else {	
	$nombreArchivo = $_FILES['archivo']['name'];
	//$nombreArchivo ="activos";
	$nombreTmp = $_FILES['archivo']['tmp_name'];
	$tipoArchivo = $_FILES['archivo']['type'];
	$tamanoArchivo = $_FILES['archivo']['size'];
	
	//las extenciones permitidas
	$ext_permitidas = array('xls','xlsx','xlsm');
	$partes_nombre = explode('.', $nombreArchivo);
	$extension = end( $partes_nombre );
	$ext_correcta = in_array($extension, $ext_permitidas);
	
	//$tipo_correcto = preg_match('/^application/vnd.openxmlformats-officedocument.spreadsheetml.sheet\/(xls|xlsx|xlsm)$/', $tipo);
	
	$limite = 500 * 2048;
	
	if($ext_correcta /*&& $tipo_correcto*/ && $tamanoArchivo <= $limite){
		if( $_FILES['archivo']['error'] > 0 ){ //manejo de posibles errores
			echo "<script>
				alert('Error: ". $_FILES['archivo']['error']."');
					window.location='frmImportarExcel.php';
			</script>";
			
		} else {
			//echo "
			//echo 'Nombre: ' . $nombreArchivo . '<br/>';
			//echo 'Tipo: ' . $tipo . '<br/>';
			//echo utf8_decode('Tamaño: ') . ($tamanoArchivo / 2048) . ' Kb<br/>';
			//echo 'Guardado en: ' . $nombreTmp;
			
				//if(file_exists('uploaded/'.$nombreArchivo)){ //compruebo si existe el archivo en la carpeta uploaded
					//move_uploaded_file($nombreTmp,"uploaded/" . $nombreArchivo."_".date("Y-m-d h:i:s"));
					//echo '<br/>El archivo ya existe: ' . $nombreArchivo;
				//} else {
					move_uploaded_file($nombreTmp,"uploaded/" . $nombreArchivo); //mueve el archivo a la carpeta destino
					//echo "Guardado en: " . "uploaded/" . $nombreArchivo;
				//}
				echo "<br>";
		echo leerExcel("uploaded/".$nombreArchivo, $nombreArchivo, $tamanoArchivo);
	}
	} else {
		echo "<script>
			alert('Archivo no valido \\n Error: ". $_FILES['archivo']['error']."');
				window.location='frmImportarExcel.php';
		</script>";
	}
}

//FUNCION PARA LEER EL ARCHIVO EXCEL
function leerExcel($rutaArchivo, $nombreArchivo, $tamanoArchivo){
		
	$objThExcel = "";
	$objTdExcel = "";
	$objTabIniExcel ="<table width='100%' border='0' style=\"border: 1px solid;\">";
		$objThExcel .="<caption>Activos Cargados a la Base de datos</caption>";
		
		$objThExcel .="<tr bgcolor=\"#CCCCCC\">";
			$objThExcel .="<th align=\"right\" colspan=\"2\" >Nombre del Archivo:</th>"; //A
			$objThExcel .="<td align=\"left\" colspan=\"5\">".$nombreArchivo."</td>";//C
			$objThExcel .="<th align=\"right\" align=\"right\" colspan=\"3\">Tamaño del Archivo:</th>";//N
			$objThExcel .="<td align=\"left\" colspan=\"6\">".$tamanoArchivo."</td>";		
		$objThExcel .="</tr>";
		
		$objThExcel .="<tr align=\"center\" bgcolor=\"#000099\" style=\"border: 1px solid\">";
			$objThExcel .="<th ><font color=\"#FFFFFF\"> Tipo de Activo</font></th>"; //A
			$objThExcel .="<th><font color=\"#FFFFFF\">Fecha de Compra </font></th>"; //B
			$objThExcel .="<th><font color=\"#FFFFFF\">Fech Inicia Depreciación </font></th>";//C
			$objThExcel .="<th><font color=\"#FFFFFF\">N Factura </font></th>";//D
			$objThExcel .="<th><font color=\"#FFFFFF\">Costo Historico </font></th>";//E
			$objThExcel .="<th><font color=\"#FFFFFF\">DEP Mensual </font></th>";//
			//$objThExcel .="<th>Depresiacion Acumulada</th>";//F
			$objThExcel .="<th><font color=\"#FFFFFF\">Vida Util </font></th>";//G
			$objThExcel .="<th><font color=\"#FFFFFF\">Descripción </font></th>";//H
			$objThExcel .="<th><font color=\"#FFFFFF\">Modelo </font></th>";//I
			$objThExcel .="<th><font color=\"#FFFFFF\">Serial </font></th>";//J
			$objThExcel .="<th><font color=\"#FFFFFF\">Ubicación </font></th>";//K
			$objThExcel .="<th><font color=\"#FFFFFF\">Departamento </font></th>";//L
			$objThExcel .="<th><font color=\"#FFFFFF\">Responsable </font></th>";//M
			$objThExcel .="<th><font color=\"#FFFFFF\">Proveedor </font></th>";//N
			$objThExcel .="<th><font color=\"#FFFFFF\">Observaciones </font></th>";//O
			$objThExcel .="<th><font color=\"#FFFFFF\">Depreciar </font></th>";//P		
		$objThExcel .="</tr>";
	
	//cargo el archivo que se desea leer

	$objPHPExcel = PHPExcel_IOFactory::load($rutaArchivo);

	//obtenemos los datos de la hoja activa (la primera)
	$objHoja = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);

	array_shift($objHoja);
	
	//VALIDA EL CONTENIDO DE LAS CELDAS
	$Resultado = validarDatos($objHoja);
		if ($Resultado[0] == false) {
			echo "<script>
				alert('No debe existir ningun campo en blanco en el archivo:\\n\\n".implode("\\n",$Resultado[1])."');
				window.location='frmImportarExcel.php';
			</script>";
		
	unlink($rutaArchivo);//elimina el archivo despues de hacer todo el proceso
	}
	
	$con = ConectarBD();	
	mysql_query('START TRANSACTION');
	//recorremos las filas obtenidas 
	$contFila= 0;
	foreach($objHoja as $indice => $objCelda){	
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar1" : "trResaltar2";
		$contFila++;

		$depreMensual = number_format($objCelda['E']/ $objCelda['G'], 2,'.',''); //depresiacion mensual
		$campos = "";
		$Valores ="";
			$campos .= "Tipo,";
			$Valores .= "'".$objCelda['A']."',";
			$campos .= "Fecha,";
			$Valores .= "'".$objCelda['B']."',";
			$campos .= "FechaDepre,";
			$Valores .= "'".$objCelda['C']."',";
			$campos .= "Comprobante,";
			$Valores .= $objCelda['D'].",";
			$campos .= "CompAdquisicion,";
			$Valores .= $objCelda['E'].",";
			$campos .= "ValResidual,";
			$Valores .= 0 .",";
			$campos .= "MesesDepre,";
			$Valores .= $objCelda['G'].",";
			$campos .= "ValDeprec,";
			$Valores .= $objCelda['E'].",";
			$campos .= "DepreMensual,";
			$Valores .= $depreMensual.",";
			$campos .= "Descripcion,";
			$Valores .= "'". $objCelda['H']."',";
			$campos .= "modelo,";
			$Valores .= "'". $objCelda['I']."',";
			$campos .= "serial,";
			$Valores .= "'". $objCelda['J']."',";
			$campos .= "Departamento,";
			$Valores .=  $objCelda['L'].",";
			$campos .= "Responsable,";
			$Valores .=  $objCelda['M'].",";
			$campos .= "Ubicacion,";
			$Valores .= $objCelda['K'].",";
			$campos .= "Proveedor,";
			$Valores .= "'".$objCelda['N']."',";
			$campos .= "Observaciones,";
			$Valores .= "'". $objCelda['O']."',";
			$campos .= "Nodeprec,";/*c*/
			$Valores .= "'". strtoupper($objCelda['P'])."',"; 
			$campos .= "estatus";/*c*/
			$Valores .= '0'; 

		 $SqlStrAct="INSERT INTO sipre_contabilidad.deprecactivos (".$campos.") VALUES (".$Valores.");";
			$queryAct = mysql_query($SqlStrAct);  

			if(!$queryAct){ //VALIDAR ERRROES DE SQL
				unlink($rutaArchivo);//elimina el archivo despues de hacer todo el proceso
					if(mysql_errno() == 1062){
						die("<script>
						alert('Error no puede existir articulo con un mismo serial. \\nEl articulo que contiene el serial repetido se encuantra en la Fila Nro: "
						.($indice +2)." del ".$nombreArchivo."');
							window.location='frmImportarExcel.php';
						</script>");
							
						} else { 
						die ("<script>
							alert('Error: ".mysql_error()." \\n Error Nro: ".mysql_errno()." \\n En la linea:".__LINE__.$SqlStrAct."');
								window.location='frmImportarExcel.php';
						</script>");
					}
			} else {
				if(strtoupper($objCelda['P']) == "SI") {
					$Codigo = mysql_insert_id();
					crearDepreciacion($Codigo);//HACE EL CALCULO DE LA DEPRESIACION
				} else {}	 	
	 //comprar con el normal
			$objTdExcel .="<tr class=".$clase.">";
				$objTdExcel .="<td align='center'>".$objCelda['A']."</td>";
				$objTdExcel .="<td align='center'>".$objCelda['B']."</td>";
				$objTdExcel .="<td align='center'>".$objCelda['C']."</td>";
				$objTdExcel .="<td align='center'>".$objCelda['D']."</td>";
				$objTdExcel .="<td align='center'>".$objCelda['E']."</td>";
				$objTdExcel .="<td align='center'>".$depreMensual."</td>";
				//$objTdExcel .="<td align='center'>".$objCelda['F']."</td>";
				$objTdExcel .="<td align='center'>".$objCelda['G']."</td>";
				$objTdExcel .="<td>".$objCelda['H']."</td>";
				$objTdExcel .="<td>".$objCelda['I']."</td>";
				$objTdExcel .="<td>".$objCelda['J']."</td>";
				$objTdExcel .="<td align='center'>".$objCelda['K']."</td>";
				$objTdExcel .="<td align='center'>".$objCelda['L']."</td>";
				$objTdExcel .="<td align='center'>".$objCelda['M']."</td>";
				$objTdExcel .="<td>".$objCelda['N']."</td>";
				$objTdExcel .="<td>".$objCelda['O']."</td>";
				$objTdExcel .="<td align='center'>".strtoupper($objCelda['P'])."</td>";
			$objTdExcel .="</tr>";

		}
				
	}//fin foreach

	$objTabFinExcel ="</table>";
		unlink($rutaArchivo);//elimina el archivo despues de hacer todo el proceso
	mysql_query('COMMIT');
	return $objTabIniExcel.$objThExcel.$objTdExcel.$objTabFinExcel."<script>maximizar();</script>";
	
	
}//fin funcion

//FUNCION PARA VALIDAR QUE NO SE A NULL O VASIO
function validarDatos($arraExcel){

	//para validar las celdas
	$validar = false;
	$errores = array();
		
	foreach($arraExcel as $indice => $objCelda){	
		//var_dump($objCelda['E']);
		if($objCelda['A']=="" || $objCelda['A']== NULL){
			$validar = true;			
			$errores[] = "Fila Nro: ".($indice +2)." Columna: A - Tipo Activo";
		} 
		if($objCelda['B']=="" || $objCelda['B']== NULL){
			$validar = true;
			$errores[] = "Fila Nro: ".($indice +2)." Columna: B - Fecha de Compra";
		}
		if($objCelda['C']=="" || $objCelda['C']== NULL){
			$validar = true;
			$errores[] = "Fila Nro: ".($indice +2)." Columna: C - Fech Inicia Depreciación";
		}
		if($objCelda['D']=="" || $objCelda['D']== NULL){
			$validar = true;
			$errores[] = "Fila Nro: ".($indice +2)." Columna: D - N Factura";
		}
		if($objCelda['E']=="" || $objCelda['E']== NULL){
			$validar = true;
			$errores[] = "Fila Nro: ".($indice +2)." Columna: E - Costo Historico";
		}
//		if($objCelda['F']=="" || $objCelda['F']== NULL){
//			$validar = true;
//			$errores[] = "Fila Nro: ".($indice +2)." Columna: F - Costo Historico";
//		}
		if($objCelda['G']=="" || $objCelda['G']== NULL){
			$validar = true;
			$errores[] = "Fila Nro: ".($indice +2)." Columna: G - Vida Util";
		}
		if($objCelda['H']=="" || $objCelda['H']== NULL){
			$validar = true;
			$errores[] = "Fila Nro: ".($indice +2)." Columna: H - Descripción";
		}
		if($objCelda['I']=="" || $objCelda['I']== NULL){
			$validar = true;
			$errores[] = "Fila Nro: ".($indice +2)." Columna: I - Modelo";
		}
		if($objCelda['J']=="" || $objCelda['J']== NULL){
			$validar = true;
			$errores[] = "Fila Nro: ".($indice +2)." Columna: J - Serial";
		}
		if($objCelda['K']=="" || $objCelda['K']== NULL){
			$validar = true;
			$errores[] = "Fila Nro: ".($indice +2)." Columna: K - Ubicación";
		}
		if($objCelda['L']=="" || $objCelda['L']== NULL){
			$validar = true;
			$errores[] = "Fila Nro: ".($indice +2)." Columna: L - Departamento";
		}
		if($objCelda['M']=="" || $objCelda['M']== NULL){
			$validar = true;
			$errores[] = "Fila Nro: ".($indice +2)." Columna: M - Responsable";
		}
		if($objCelda['N']=="" || $objCelda['N']== NULL){
			$validar = true;
			$errores[] = "Fila Nro: ".($indice +2)." Columna: N - Proveedor";
		}
		/*if($objCelda['O']=="" || $objCelda['O']== NULL){
			$validar = true;
			$errores[] = "Fila Nro: ".($indice +2)." Columna: O - Observaciones";
		}*/
		if($objCelda['P']=="" || $objCelda['P']== NULL){
			$validar = true;
			$errores[] = "Fila Nro: ".($indice +2)." Columna: P - Depreciar";
		}
	}//fin del foreach validar
	$errores = "";
	$erroresTipo = "";
	if($validar){
		return array(false, $errores, $erroresTipo);
	} else {
		return array(true, $errores, $erroresTipo);
	}

}

function parOimpar($numero){ 
	$resto = $numero%2; 
	if (($resto==0) && ($numero!=0)) { 
		 $class = "";
	} else { 
		 $class = "";
	}  
	return $class;
}

?>
</body>
</html>

