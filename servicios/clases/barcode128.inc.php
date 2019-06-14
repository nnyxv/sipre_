<?php
//CODIGO DE BARRAS 128 2.0 - (A, B, Y C)
// 2008, Maycol Alvarez
// Actualización 25 noviembre 2008
// Actualización 30 de septiembre de 2009 IMPLEMENTADO COMO FUNCIÓN QUE DEVUELVE TRUE SI DUMPEA ARCHIVO

/*
API:
	bool getBarcode(string $codigo, string $namefile,$qzone=0,$bw=1,$bh=30,$type="b",$pc=1)	
*/

$code = array("212222","222122","222221","121223","121322","131222","122213","122312","132212","221213","221312","231212","112232","122132","122231","113222","123122","123221","223211","221132","221231","213212","223112","312131","311222","321122","321221","312212","322112","322211","212123","212321","232121","111323","131123","131321","112313","132113","132311","211313","231113","231311","112133","112331","132131","113123","113321","133121","313121","211331","231131","213113","213311","213131","311123","311321","331121","312113","312311","332111","314111","221411","431111","111224","111422","121124","121421","141122","141221","112214","112412","122114","122411","142112","142211","241211","221114","413111","241112","134111","111242","121142","121241","114212","124112","124211","411212","421112","421211","212141","214121","412121","111143","111341","131141","114113","114311","411113","411311","113141","114131","311141","411131","211412","211214","211232","2331112");
$codea= array(32 => 0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63);
$codeb= array(32 => 0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94);
//definiendo variables:
define(starta,103);
define(startb,104);
define(startc,105);
define(stop,106);
//expirando el caché:
/**/

function drawbar($v,$x,$im,$text_color,$bw,$bh){
	global $code;
	$c=$code[$v];
	$b=0;
	for($i=0;$i<strlen($c);$i++){
		//echo $x."-";
		if ($b==0){
			imagefilledrectangle($im,$x+1,0,$x+(intval($c[$i])*$bw),$bh,$text_color);
			$b=1;
		}else{
			$b=0;
		}
		$x+=(intval($c[$i])*$bw);
	}
	return $x;
	//imagestring($im,1,$x-3,50+$x,$v,$text_color);
}
	
function getBarcode($codigo,$namefile,$qzone=2,$bw=1,$bh=25,$type="c",$pc=1){
	global $code, $codea, $codeb;

	//cargando variables:
	if(!is_string($codigo)){
		return false;
	}
	//$codigo=$codigo;
	$type=strtolower($type);	//tipo A B ó C
	$bw=intval($bw);	//ancho de la barra
	$bh=intval($bh);
	$pc=intval($pc);

	//imprimiendo barras:
	//validando datos:
	if($bw<1){
		$bw=1;
	}
	if($bh<25){
		$bh=25;
	}

	if ($namefile!=''){
		$getqzone=intval($qzone);
		if($getqzone<0){
			$getqzone=0;
		}
		$qzone=$getqzone;
	}else{
		$qzone=36*$bw;
	}

	if (strlen($codigo)<2) {
		$codigo="0".$codigo;
	}
	if (strlen($codigo)<3) {
		$codigo="0".$codigo;
	}
	if ($type=='c'){
		if (strlen($codigo) % 2 != 0) {
			$codigo='0'.$codigo;
		}
		$ancho = (((intval(strlen($codigo))/2)+2)*(11*$bw)) +(12*$bw)+ ($qzone*2) +$bw;
	}else{
		$ancho = (((intval(strlen($codigo)))+2)*(11*$bw)) +(12*$bw)+ ($qzone*2) +$bw;
	}
	$x=$qzone-1;

	if ($pc!=""){
		$pc=$bh+15;
	}else{
		$pc= $bh;
	}
	$im = @imagecreate($ancho, $pc) or die("No se puede crear la imagen");

	//estableciendo los colores de la paleta:
	$background_color = imagecolorallocate($im, 255, 255, 255);
	$text_color = imagecolorallocate($im, 0, 0, 0);

	if ($pc!=""){
		
		imagestring($im,4,(intval($ancho-strlen($codigo)*8)/2),$bh+2,$codigo,$text_color);
	}

	if ($type=='c'){
		$parada=startc;
		//imprimiendo el codigo:
		$x=drawbar(startc,$x,$im,$text_color,$bw,$bh);//inicio
		for ($j=0;$j<(strlen($codigo)/2);$j++){
			$val=intval(substr($codigo,$j*2,2));
			$parada+=$val*($j+1);
			$x=drawbar($val,$x,$im,$text_color,$bw,$bh);//caracter
		}
		$x=drawbar($parada % 103,$x,$im,$text_color,$bw,$bh);//validacion
		$x=drawbar(stop,$x,$im,$text_color,$bw,$bh);//parada
	}else{
		//imprimiendo el codigo:
		if ($type=='a'){
			$parada=starta;
			$x=drawbar(starta,$x,$im,$text_color,$bw,$bh);//inicio
		}else{
			$parada=startb;
			$x=drawbar(startb,$x,$im,$text_color,$bw,$bh);//inicio
		}
		for ($j=0;$j<strlen($codigo);$j++){
			if ($type=='a'){
				$val=$codea[ord($codigo[$j])];
			}else{
				$val=$codeb[ord($codigo[$j])];
			}
			//$val=intval(substr($codigo,$j*2,2));
			$parada+=$val*($j+1);
			$x=drawbar($val,$x,$im,$text_color,$bw,$bh);//caracter
		}
		$x=drawbar($parada % 103,$x,$im,$text_color,$bw,$bh);//validacion
		$x=drawbar(stop,$x,$im,$text_color,$bw,$bh);//parada
	}
	if ($namefile!=''){
		//volcando la imagen al archivo
		$r=imagepng($im,$namefile.'.png');
	}else{
		//definiendo el tipo de salida como imagen //Pendiente hacia archivo*
		header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );  // disable IE caching
		header( "Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . " GMT" ); 
		header( "Cache-Control: no-cache, must-revalidate" ); 
		header( "Pragma: no-cache" );
		header("Content-type: image/png");
		//volcando la imagen a la salida:
		$r=imagepng($im);
	}
	//habilitando el recurso:
	imagedestroy($im);
	if($r){
		return $ancho;
	}else{
		return false;
	}
}

//probando
/*
$ret=getBarcode(1234,'autosx/dos');
var_dump($ret);*/
?>