<?php


$ruta = explode("↓",str_replace(array("/","\\"),"↓",getcwd()));
$ruta = array_reverse($ruta);
$raizPpal = false;
foreach ($ruta as $indice => $valor) {
	$valor2 = explode("_",$valor);
	if ($valor2[0] != "sipre" && $raizPpal == false) {
		$raizSite .= "../";
		break;
	} else if ($valor2[0] == "sipre") {
		$raizPpal = true;
	}
}

define("raizSite", $raizSite);

if (!function_exists("valTpDato")) {
	function valTpDato($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") {
		$theValue = get_magic_quotes_gpc() ? stripslashes($theValue) : $theValue;
		
		$hostname_conex = "localhost";
		$database_conex = DBASE_SIPRE_AUT;
		$username_conex = DUSER_SIPRE_AUT;
		$password_conex = DPASW_SIPRE_AUT;
		
		$conex = mysql_connect($hostname_conex, $username_conex, $password_conex) or trigger_error(mysql_error(),E_USER_ERROR); 
		mysql_select_db($database_conex, $conex);
		
		$theValue = function_exists("mysql_real_escape_string") ? mysql_real_escape_string($theValue) : mysql_escape_string($theValue);
		
		switch ($theType) {
			case "text":
				$theValue = str_replace("\"","",$theValue);
				$theValue = str_replace("\'","",$theValue);
				$theValue = ($theValue != "") ? "'" . trim($theValue) . "'" : "NULL";
				break;
			case "money":			$theValue = ($theValue != "") ? "'" . trim($theValue) . "'" : "NULL"; break;    
			case "long":
			case "int":				$theValue = ($theValue != "") ? intval($theValue) : "NULL"; break;
			case "double":			$theValue = ($theValue != "") ? "'" . doubleval($theValue) . "'" : "NULL"; break;
			case "real_inglesa":
				$theValue = str_replace(",","",$theValue);
				$theValue = ($theValue != "") ? "'" . doubleval($theValue) . "'" : "NULL";
				break;
			case "date":			$theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL"; break;
			case "boolean":			$theValue = ($theValue != "") ? (((bool)$theValue == 1) ? "'" . (bool)$theValue . "'" : "'0'") : "NULL"; break;
			case "defined":			$theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue; break;
			case "campo":			$theValue = ($theValue != "" && strlen($theValue) > 0) ? trim($theValue) : "-1"; break;
			case "cero_por_vacio":	$theValue = ($theValue != "0") ? trim($theValue) : ""; break;
		}
		return $theValue;
	}
}

if (!function_exists("elimCaracter")) {
	function elimCaracter($theValue, $caracter) {
		$theValue = str_replace($caracter, " ", $theValue);
		
		return $theValue;
	}
}

if (!function_exists("codArticuloExpReg")) {
	function codArticuloExpReg($theValue) {
		$valCadBusq = explode(";", $theValue);
		
		$theValue = "^(";
		foreach ($valCadBusq as $indice => $valor) {
			$valor = trim($valor);
			
			if (strlen($valor) > 0)
				$valor = ".*".$valor.".*";
			else
				$valor = "[[:alnum:][:punct:]]*";
			
			$theValue .= $valor."[;]";
		}
		$theValue = substr($theValue,0,strlen($theValue)-3);
		$theValue .= ")$";
		
		return $theValue;
	}
}

if (!function_exists("truncateFloat")) {
	function truncateFloat($theValue, $ndecimales) {
		$raiz = 10;
		$multiplicador = pow($raiz, $ndecimales);
		$theValueFloat = round($theValue * $multiplicador, $ndecimales);
		$resultado = floor($theValueFloat) / $multiplicador;
		return number_format($resultado, $ndecimales, _CHARDECIMAL, "");
		
		/*$dividir = explode(_CHARDECIMAL, $theValue);
		if ($dividir[1] == 0) {
			return number_format($theValue, $ndecimales, _CHARDECIMAL, "");
		} else {
			$monto = number_format($dividir[0]);
			$decimal = substr($dividir[1], 0, $ndecimales);
			return number_format($monto.".".$decimal, $ndecimales, _CHARDECIMAL, ""); // _CHARMILES
		}*/
	}
}

if (!function_exists("valCrtrEsp")) {
	function valCrtrEsp($theValue) {
		$theValue = htmlentities($theValue, ENT_COMPAT, 'utf-8');
		
		return $theValue;
	}
}

if (!function_exists("suma_fechas")) {
	function suma_fechas($formato, $fecha, $ndias) {
		if ($formato == "d-m-Y" || $formato == "d/m/Y") {
			if (preg_match("/[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4}/",$fecha)) {
				$arrayFecha = explode("/",$fecha);
			}
			
			if (preg_match("/[0-9]{1,2}-[0-9]{1,2}-[0-9]{2,4}/",$fecha)) {
				$arrayFecha = explode("-",$fecha);
			}
			$dia = $arrayFecha[0];
			$mes = $arrayFecha[1];
			$ano = $arrayFecha[2];
		} else if ($formato == "m-d-Y" || $formato == "m/d/Y") {
			if (preg_match("/[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4}/",$fecha)) {
				$arrayFecha = explode("/",$fecha);
			}
			
			if (preg_match("/[0-9]{1,2}-[0-9]{1,2}-[0-9]{2,4}/",$fecha)) {
				$arrayFecha = explode("-",$fecha);
			}
			$dia = $arrayFecha[1];
			$mes = $arrayFecha[0];
			$ano = $arrayFecha[2];
		} else if ($formato == "Y-m-d" || $formato == "Y/m/d") {
			if (preg_match("/[0-9]{2,4}\/[0-9]{1,2}\/[0-9]{1,2}/",$fecha)) {
				$arrayFecha = explode("/",$fecha);
			}
			
			if (preg_match("/[0-9]{2,4}-[0-9]{1,2}-[0-9]{1,2}/",$fecha)) {
				$arrayFecha = explode("-",$fecha);
			}
			$dia = $arrayFecha[2];
			$mes = $arrayFecha[1];
			$ano = $arrayFecha[0];
		}
		
		$nueva = mktime(0,0,0, $mes,$dia,$ano) + ($ndias * (24 * 60 * 60));
		$nuevafecha = date($formato,$nueva);
	
		return ($nuevafecha);
	}
}

if (!function_exists("restaFechas")) {
	function restaFechas($formato, $dFecIni, $dFecFin, $devolver = "meses") {
		if ($formato == "d-m-Y" || $formato == "d/m/Y") {
			if (preg_match("/[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4}/",$dFecIni)) {
				$arrayFecha = explode("/",$dFecIni);
			}
			
			if (preg_match("/[0-9]{1,2}-[0-9]{1,2}-[0-9]{2,4}/",$dFecIni)) {
				$arrayFecha = explode("-",$dFecIni);
			}
			$diaIni = $arrayFecha[0];
			$mesIni = $arrayFecha[1];
			$anoIni = $arrayFecha[2];
			
			if (preg_match("/[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4}/",$dFecFin)) {
				$arrayFecha = explode("/",$dFecFin);
			}
			
			if (preg_match("/[0-9]{1,2}-[0-9]{1,2}-[0-9]{2,4}/",$dFecFin)) {
				$arrayFecha = explode("-",$dFecFin);
			}
			$diaFin = $arrayFecha[0];
			$mesFin = $arrayFecha[1];
			$anoFin = $arrayFecha[2];
		} else if ($formato == "m-d-Y" || $formato == "m/d/Y") {
			if (preg_match("/[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4}/",$dFecIni)) {
				$arrayFecha = explode("/",$dFecIni);
			}
			
			if (preg_match("/[0-9]{1,2}-[0-9]{1,2}-[0-9]{2,4}/",$dFecIni)) {
				$arrayFecha = explode("-",$dFecIni);
			}
			$diaIni = $arrayFecha[1];
			$mesIni = $arrayFecha[0];
			$anoIni = $arrayFecha[2];
			
			if (preg_match("/[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4}/",$dFecFin)) {
				$arrayFecha = explode("/",$dFecFin);
			}
			
			if (preg_match("/[0-9]{1,2}-[0-9]{1,2}-[0-9]{2,4}/",$dFecFin)) {
				$arrayFecha = explode("-",$dFecFin);
			}
			$diaFin = $arrayFecha[1];
			$mesFin = $arrayFecha[0];
			$anoFin = $arrayFecha[2];
		} else if ($formato == "Y-m-d" || $formato == "Y/m/d") {
			if (preg_match("/[0-9]{2,4}\/[0-9]{1,2}\/[0-9]{1,2}/",$dFecIni)) {
				$arrayFecha = explode("/",$dFecIni);
			}
			
			if (preg_match("/[0-9]{2,4}-[0-9]{1,2}-[0-9]{1,2}/",$dFecIni)) {
				$arrayFecha = explode("-",$dFecIni);
			}
			$diaIni = $arrayFecha[2];
			$mesIni = $arrayFecha[1];
			$anoIni = $arrayFecha[0];
			
			if (preg_match("/[0-9]{2,4}\/[0-9]{1,2}\/[0-9]{1,2}/",$dFecFin)) {
				$arrayFecha = explode("/",$dFecFin);
			}
			
			if (preg_match("/[0-9]{2,4}-[0-9]{1,2}-[0-9]{1,2}/",$dFecFin)) {
				$arrayFecha = explode("-",$dFecFin);
			}
			$diaFin = $arrayFecha[2];
			$mesFin = $arrayFecha[1];
			$anoFin = $arrayFecha[0];
		}
		
		$date1 = mktime(0,0,0,$mesIni, $diaIni, $anoIni);
		$date2 = mktime(0,0,0,$mesFin, $diaFin, $anoFin);
		
		if ($devolver == "dias") {
			return round(($date2 - $date1) / (60 * 60 * 24));
		} else if ($devolver == "meses") {
			return round(($date2 - $date1) / (60 * 60 * 24 * 31));
		}
	}
}

if (!function_exists("redimensionar_imagen")) {
	function redimensionar_imagen($directorio,$nomImagen,$nuevo_ancho=140,$nuevo_alto=140) {
		//establecemos los límites de ancho y alto
		$imagen = $directorio.$nomImagen;
		
		//Recojo información de la imágen
		$info_imagen = getimagesize($imagen);
		$alto = $info_imagen[1];
		$ancho = $info_imagen[0];
		$tipo_imagen = $info_imagen[2];
		
		//Determino las nuevas medidas en función de los límites
		if($ancho > $nuevo_ancho || $alto > $nuevo_alto) {
			if(($alto - $nuevo_alto) > ($ancho - $nuevo_ancho))  {
				$nuevo_ancho = round($ancho * $nuevo_alto / $alto,0) ;       
			} else {
				$nuevo_alto = round($alto * $nuevo_ancho / $ancho,0);   
			}
		} else { //si la imagen es más pequeña que los límites la dejo igual.
			$nuevo_alto = $alto;
			$nuevo_ancho = $ancho;
		}
	
	  // dependiendo del tipo de imagen tengo que usar diferentes funciones
		switch ($tipo_imagen) {
			case 1: //si es gif …
				$imagen_nueva = imagecreate($nuevo_ancho, $nuevo_alto);
				$imagen_vieja = imagecreatefromgif($imagen);
				//cambio de tamaño…
				imagecopyresampled($imagen_nueva, $imagen_vieja, 0, 0, 0, 0, $nuevo_ancho, $nuevo_alto, $ancho, $alto);
				//imagecopyresized($imagen_nueva, $imagen_vieja, 0, 0, 0, 0, $nuevo_ancho, $nuevo_alto, $ancho, $alto);
				if (!imagegif($imagen_nueva, $directorio.$nomImagen)) return false;
				break;
		
			case 2: //si es jpeg …
				$imagen_nueva = imagecreatetruecolor($nuevo_ancho, $nuevo_alto);
				$imagen_vieja = imagecreatefromjpeg($imagen);
				//cambio de tamaño…
				imagecopyresampled($imagen_nueva, $imagen_vieja, 0, 0, 0, 0, $nuevo_ancho, $nuevo_alto, $ancho, $alto);
				//imagecopyresized($imagen_nueva, $imagen_vieja, 0, 0, 0, 0, $nuevo_ancho, $nuevo_alto, $ancho, $alto);
				if (!imagejpeg($imagen_nueva, $directorio.$nomImagen)) return false;
				break;
		
			case 3: //si es png …
				$imagen_nueva = imagecreatetruecolor($nuevo_ancho, $nuevo_alto);
				$imagen_vieja = imagecreatefrompng($imagen);
				//cambio de tamaño…
				imagecopyresampled($imagen_nueva, $imagen_vieja, 0, 0, 0, 0, $nuevo_ancho, $nuevo_alto, $ancho, $alto);
				//imagecopyresized($imagen_nueva, $imagen_vieja, 0, 0, 0, 0, $nuevo_ancho, $nuevo_alto, $ancho, $alto);
				if (!imagepng($imagen_nueva, $directorio.$nomImagen)) return false;
				break;
		}
		return true; //si todo ha ido bien devuelve true
	}
}

if (!function_exists("editarImagen")) {
	function editarImagen($imagen, $directorio, $bd, $tpImg) {
		if (isset($imagen) && $imagen['name'] != "") {
			$tipo = explode("/",$imagen['type']);
					
			if ($imagen['size'] > 0 && $imagen['size'] < 5525000) {
				if ($imagen['type'] == "image/pjpeg" || $imagen['type'] == "image/jpeg"
				|| $imagen['type'] == "image/x-png" || $imagen['type'] == "image/png"
				|| $imagen['type'] == "image/gif" || $imagen['type'] == "image/bmp") { // Firefox
					switch($imagen['type']) {
						case "image/pjpeg" : $tipo[1] = "jpg"; break;
						case "image/jpeg" : $tipo[1] = "jpg"; break;
						case "image/x-png" : $tipo[1] = "png"; break;
						case "image/png" : $tipo[1] = "png"; break;
						case "image/gif" : $tipo[1] = "gif"; break;
						case "image/bmp" :$tipo[1] = "bmp";  break;
						default : "jpg";
					}
					
					$nomImg = $tpImg.$_SESSION['idPostEmpCL'].date("_dmY_hisa");
					
					$imgLogo = $nomImg.".".$tipo[1];
					
					move_uploaded_file($imagen['tmp_name'], $directorio.$imgLogo);
				
					redimensionar_imagen($directorio, $imgLogo, 600, 600);
					$infoImagen = getimagesize($directorio.$imgLogo);
					
					// elimina del servidor la imagen anterior solo si existe
					$arrayImagen = explode(".", $rowEmpresa['ruta']);
					if(file_exists($arrayImagen[0].".".$arrayImagen[1]) && strlen($arrayImagen[0].".".$arrayImagen[1]) > 4) {
						unlink($arrayImagen[0].".".$arrayImagen[1]);
					}
				}
				
				return $bd.$imgLogo;
			}
		}
	}
}

if (!function_exists("ordenarCampo")) {
	function ordenarCampo($funcion, $tamanoColumna, $pageNum, $campoBD, $campOrd, $tpOrd, $valBusq, $maxRows, $nombreColumna, $tituloColumna = "") {
		$imgUp = (file_exists("../img/iconos/img_flecha_blanca_up.gif")) ? "../img/iconos/img_flecha_blanca_up.gif" : "img/iconos/img_flecha_blanca_up.gif";
		$imgDown = (file_exists("../img/iconos/img_flecha_blanca_down.gif")) ? "../img/iconos/img_flecha_blanca_down.gif" : "img/iconos/img_flecha_blanca_down.gif";
		
		$html .= sprintf("<td class=\"puntero\" %s %s onclick=\"%s(%s,'%s','%s','%s',%s);\"><table cellpadding=\"0\" cellspacing=\"0\" width=\"%s\"><tr align=\"center\"><td width=\"8px\"></td><td>%s</td><td width=\"8px\">%s</td></tr></table></td>",
			((strlen($tituloColumna) > 0) ? "title=\"".$tituloColumna."\"" : ""),
			((strlen($tamanoColumna) > 0) ? "width=\"".$tamanoColumna."\"" : ""),
			$funcion,
			$pageNum,
			$campoBD,
			($campOrd == $campoBD && $tpOrd == "ASC") ? "DESC" : "ASC",
			$valBusq,
			$maxRows,
			"100%",
			$nombreColumna,
			($campOrd == $campoBD && $tpOrd == "ASC") ? "<img src=\"".$imgUp."\">" :
				(($campOrd == $campoBD && $tpOrd == "DESC") ? "<img src=\"".$imgDown."\">" : ''));
		
		return $html;
	}
}

if (!function_exists("diasHabiles")) {
	function diasHabiles($fecha_inicial, $fecha_final) { 
		list($dia,$mes,$year) = explode("-",$fecha_inicial); 
		$ini = mktime(0, 0, 0, $mes , $dia, $year); 
		list($diaf,$mesf,$yearf) = explode("-",$fecha_final); 
		$fin = mktime(0, 0, 0, $mesf , $diaf, $yearf); 
	
		$r = 0; 
		while($ini != $fin) { 
			$ini = mktime(0, 0, 0, $mes , $dia + $r, $year); 
			$newArray[] .= $ini; 
			$r++; 
		} 
		return $newArray; 
	}
}

if (!function_exists("evaluaFecha")) {
	function evaluaFecha($arreglo) { 
		$queryDiasFeriados = sprintf("SELECT * FROM pg_fecha_baja
		WHERE tipo LIKE 'FERIADO';");
		$rsDiasFeriados = mysql_query($queryDiasFeriados);
		if (!$rsDiasFeriados) return(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		while ($rowDiasFeriados = mysql_fetch_array($rsDiasFeriados)) {
			$fecha = explode("-",$rowDiasFeriados['fecha_baja']);
			$arrayFeriados[] = intval($fecha[2])."-".intval($fecha[1]); // DIA - MES
		}
		
		$totalDiasMes = count($arreglo); 
	
		for ($i = 0; $i < $totalDiasMes; $i++) { 
			$dia = $arreglo[$i]; 
			
			$fecha = explode("-",date("Y-m-d",$dia));
			$feriado = intval($fecha[2])."-".intval($fecha[1]); // DIA - MES
			
			if (in_array(date('N', strtotime(date("Y-m-d",$dia))),array(6,7))) { 
				$totalDiasFeriadosMes++; 
			} else if (in_array($feriado,$arrayFeriados)) {
				$totalDiasFeriadosMes++;
			} 
		}
		$rlt = $totalDiasMes - $totalDiasFeriadosMes; 
		
		return $rlt; 
	}
}

if (!function_exists("ultimoDia")) {
	function ultimoDia($mes,$ano) {
		return strftime("%d", mktime(0, 0, 0, $mes + 1, 0, $ano));
	}
}

/////////////////////////////////////////// MAYCOL ///////////////////////////////////////////

//constantes
define ('_CHARMILES',',');
define ('_CHARDECIMAL','.');

if (!function_exists("getempresa")) {
	function getempresa() {
		$r = $_SESSION['session_empresa'];
		if ($r == ""){
			conectar();
			@session_start();
			$r=getmysql("select id_empresa from pg_usuario where id_usuario=".$_SESSION['idUsuarioSysGts'].";");
		}
		if($r==""){
			$r=1;
		}
		return $r;
	}
}

if (!function_exists("conectar")) {
	function conectar() {
		global $conex,$hostname_conex,$username_conex,$password_conex,$database_conex;
		$conex = mysql_connect($hostname_conex,$username_conex,$password_conex);
		mysql_select_db($database_conex, $conex);
		@mysql_query("SET NAMES 'latin1'", $conex);
		//inputmysqlutf8();
	}
}

if (!function_exists("iniciotransaccion")) {
	function iniciotransaccion() {
		global $conex;
		@mysql_query("START TRANSACTION;", $conex);
	}
}

if (!function_exists("fintransaccion")) {
	function fintransaccion() {
		global $conex;
		@mysql_query("COMMIT;", $conex);
	}
}

if (!function_exists("rollback")) {
	function rollback() {
		global $conex;
		@mysql_query("ROLLBACK;", $conex);
	}
}

if (!function_exists("cerrar")) {
	function cerrar() {
		global $conex;
		mysql_close($conex);
	}
}

if (!function_exists("inputmysqlutf8")) {
	function inputmysqlutf8() {
		global $conex;
		@mysql_query("SET NAMES 'utf8'", $conex);
	}
}

if (!function_exists("mysqlfecha")) {
	function mysqlfecha($campo) {
		return " DATE_FORMAT(".excape($campo).",'%d-%m-%Y') ";
	}
}
	
if (!function_exists("setmysqlfecha")) {
	function setmysqlfecha($f) {
		//return "'".$f."'";
		if ($f == "") {
			return "null";
		}
		return " STR_TO_DATE('".excape($f)."','%d-%m-%Y') ";
	}
}
	
if (!function_exists("getempty")) {
	function getempty($val,$ret) {
		if ($val == "") {
			return excape($ret);
		}
		return excape($val);
	}
}

if (!function_exists("getemptynum")) {
	function getemptynum($val,$ret) {
		$r = getempty($val,"");
		if ($r == "") {
			if (strtoupper($ret) == "NULL"){
				return "NULL";
			}
			return excape(getmysqlnum($ret));
		} else {
			return getmysqlnum($r);
		}
	}
}

if (!function_exists("getmysql")) {
	//devuelve rapidamente el primer campo y registro de una consulta mysql simple:
	function getmysql($s,$def=0){
		global $conex;
		$result = mysql_query($s, $conex);
		$r = mysql_fetch_row($result);
		if (mysql_num_rows($result)) {
			return $r[0];
		} else {
			return $def;
		}
	}
}

if (!function_exists("numformat")) {
	function numformat($num,$dec=2,$ar1="",$ar2=""){//ar1 y ar2 se ignoran
		$r = number_format($num, $dec, _CHARDECIMAL, _CHARMILES);
		if ($r == "0.00"){
			return "";
		} else {
			return $r;
		}
	}
}

if (!function_exists("getmysqlnum")) {
	function getmysqlnum($num) {
		$r=str_replace(_CHARMILES,"",$num);
		$r=str_replace(_CHARDECIMAL,".",$r);
		if (!is_numeric($r)) {
			$r=0;
		}
		return excape($r);
	}
}

if (!function_exists("excape")) {
	function excape($var) {
		return mysql_real_escape_string($var);
	}
}

if (!function_exists("cache_expires")) {
	function cache_expires() {
		header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );  // disable IE caching
		header( "Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . " GMT" ); 
		header( "Cache-Control: no-cache, must-revalidate" ); 
		header( "Pragma: no-cache" );
	}
}

if (!function_exists("generar_select")) {
	//solo 2 datos, el valor por defecto y la cadena sql, donde el primer campo es el id de la tabla y el segundo el valor a colocar en el option
	function generar_select($default,$sqll) {
		global $conex,$hostname_conex,$username_conex,$password_conex,$database_conex;
		conectar();
		$rl = mysql_query($sqll, $conex);
		while($rowl = mysql_fetch_row($rl)) {
			echo '<option value="'.$rowl[0].'"';
			if ($default == $rowl[0]) {
				echo ' selected="selected"';
			}
			echo '>'.utf8_encode($rowl[1]).'</option>';					
		}
		//cerrar();
	}
}

//DESDE PHP 4.0.6 EN ADELANTE ---------------------------------------------------------------------------------------------------------------------------------------
if (!function_exists("url_a_encode")) {
	function url_a_encode($str){
		return str_replace('&','%26',$str);//htmlspecialchars($str);//
		
	}
}

if (!function_exists("tagxml")) {
	function tagxml($tagname,$value){
		if ($value=='') {
			echo '<'.$tagname.'/>';
		} else {
			echo '<'.$tagname.'>'.mb_convert_encoding($value,"UTF-8").'</'.$tagname.'>';
		}
	}
}

if (!function_exists("tagxmlid")) {
	function tagxmlid($tagname,$value,$idt){
		if ($value=='') {
			echo '<'.$tagname.' id="'.$idt.'" />';
		} else {
			echo '<'.$tagname.' id="'.$idt.'">'.mb_convert_encoding($value,"UTF-8").'</'.$tagname.'>';
		}
	}
}

if (!function_exists("xmlstart")) {
	function xmlstart() {
		header('Content-Type: text/xml');
		echo '<?xml version="1.0" encoding="UTF-8" ?>';
		//no hace falta:
		//echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
	}
}
?>