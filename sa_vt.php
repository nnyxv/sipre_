<?php
header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );  // disable IE caching
header( "Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . " GMT" ); 
header( "Cache-Control: no-cache, must-revalidate" ); 
header( "Pragma: no-cache" );
$cadena=$_GET['t'];
$tam=intval($_GET['size']);
if($tam==0){
	$tam=1;
}
if($tam>5){
	$tam=4;
}
$vexp=array(
	1=>5,
	2=>3,
	3=>3,
	4=>3,
	5=>3
);
if($cadena==''){
	echo 'no se ha especificado';
	exit;
}
header("Content-type: image/png");
$alto=(strlen($cadena)*($tam*$vexp[$tam]))+2;

$im = @imagecreate(($tam*8)-1, $alto)
    or die("no se puede crear la imagen");
$background_color = imagecolorallocate($im, 255, 255, 255);
$text_color = imagecolorallocate($im, 0, 0, 0);
$v=imagestringup($im, $tam, 0,$alto-2,  $cadena, $text_color);
imagepng($im);
imagedestroy($im);
//var_dump($v);
?> 