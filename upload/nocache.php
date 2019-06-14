<?php
header("Content-type: image/jpeg");
header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );  // disable IE caching
header( "Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . " GMT" ); 
header( "Cache-Control: no-cache, must-revalidate" ); 
header( "Pragma: no-cache" );
if (file_exists($_GET['image'])){
 $im = @imagecreatefromjpeg ($_GET['image']); 
}else{
 $im = @imagecreatefromjpeg ('nodisponible.jpg'); 
}
 imagejpeg($im);
?>