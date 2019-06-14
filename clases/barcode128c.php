<?php	
//CODIGO DE BARRAS 128C
// 2008, Maycol Alvarez

//expirando el caché:
header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );  // disable IE caching
header( "Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . " GMT" ); 
header( "Cache-Control: no-cache, must-revalidate" ); 
header( "Pragma: no-cache" );
$code = array("212222","222122","222221","121223","121322","131222","122213","122312","132212","221213","221312","231212","112232","122132","122231","113222","123122","123221","223211","221132","221231","213212","223112","312131","311222","321122","321221","312212","322112","322211","212123","212321","232121","111323","131123","131321","112313","132113","132311","211313","231113","231311","112133","112331","132131","113123","113321","133121","313121","211331","231131","213113","213311","213131","311123","311321","331121","312113","312311","332111","314111","221411","431111","111224","111422","121124","121421","141122","141221","112214","112412","122114","122411","142112","142211","241211","221114","413111","241112","134111","111242","121142","121241","114212","124112","124211","411212","421112","421211","212141","214121","412121","111143","111341","131141","114113","114311","411113","411311","113141","114131","311141","411131","211412","211214","211232","2331112");

//definiendo variables:
define(startc,105);
define(stop,106);

//cargando variables:
$codigo=$_GET['codigo'];
$bw=intval($_GET['bw']);	//ancho de la barra
$bh=intval($_GET['bh']);
$pc=intval($_GET['pc']);

//imprimiendo barras:
function drawbar($v){
	global $code,$x,$im,$text_color,$bw,$bh;
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
	//imagestring($im,1,$x-3,50+$x,$v,$text_color);
}
//validando datos:
if($bw<1){
	$bw=1;
}
if($bh<30){
	$bh=30;
}
$qzone=36*$bw;

if (strlen($codigo) % 2 != 0) {
	$codigo='0'.$codigo;
}
$ancho = (((intval(strlen($codigo))/2)+2)*(11*$bw)) +(12*$bw)+ ($qzone*2) +$bw;
$x=$qzone-1;

//definiendo el tipo de salida como imagen //Pendiente hacia archivo*:
header("Content-type: image/png");
if ($pc!=""){
	$pc=$bh+15;
}else{
	$pc= $bh;
}
$im = @imagecreate($ancho, $pc) or die("No se puede crear la imagen");

//estableciendo los colores de la paleta:
$background_color = imagecolorallocate($im, 255, 255, 255);
$text_color = imagecolorallocate($im, 0, 0, 0);

$parada=startc;
//imprimiendo el codigo:
if ($pc!=""){
	
	imagestring($im,4,(intval($ancho-strlen($codigo)*8)/2),$bh+2,$codigo,$text_color);
}
drawbar(startc);//inicio
for ($j=0;$j<(strlen($codigo)/2);$j++){
	$val=intval(substr($codigo,$j*2,2));
	$parada+=$val*($j+1);
	drawbar($val);//caracter
}
drawbar($parada % 103);//validacion
drawbar(stop);//parada

//imagestring($im,1,1,1,$codigo,$text_color);
//volcando la imagen a la salida:
imagepng($im);
//habilitando el recurso:
imagedestroy($im);
?>