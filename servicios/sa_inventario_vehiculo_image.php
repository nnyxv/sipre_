<?php
//inventario del vehiculo, geenradod de la imagen PNG
$id_cita=$_GET['id_cita'];
//borrando el cach�

header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );  // disable IE caching
header( "Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . " GMT" ); 
header( "Cache-Control: no-cache, must-revalidate" ); 
header( "Pragma: no-cache" );

//incluyendo el modelo:

	require_once("control/model/main.inc.php");
	require_once("control/main_lib.inc.php");
	
function copiar($dst_im,$src_im,$dst_x,$dst_y){
	imagecopy($dst_im,$src_im,$dst_x-5,$dst_y-5,0,0,10,10);
}

	//creando a partir de imagen

	$im = @imagecreatefrompng (getUrl('img/vehiculo_vectorial.png')); /* Intento de apertura */
	$im_r = @imagecreatefrompng (getUrl('img/iconos/raya.png')); /* Intento de apertura */
	$im_g = @imagecreatefrompng (getUrl('img/iconos/golpe.png')); /* Intento de apertura */

	if ($im && $im_r && $im_g) {
		$background = imagecolorallocate($im, 255,255,255);
		if($id_cita!=''){
			$c=new connection();
			$c->open();
			$rec=$c->sa_recepcion_incidencia->doSelect($c,new criteria(sqlEQUAL,$c->sa_recepcion_incidencia->id_cita,$id_cita));
			if($rec){
				foreach($rec as $r){
					if($rec->tipo_incidencia=='RAYA'){
						$im_i=$im_r;
					}else{
						$im_i=$im_g;
					}
					copiar($im,$im_i,$rec->x,$rec->y);
				}
			}else{
				echo 'error';
			}
			$c->close();
			if (isset($_GET['rotate'])){
				$im=imagerotate($im,intval($_GET['rotate']),$background);
			}
		}else{
			imagedestroy($im);
			$background = imagecolorallocate($im, 0, 0, 0);
			$im=imagecreate(360,400);
			$textor = imagecolorallocate($im, 255, 0, 0);
			imagestring ($im,5, 15, 200, ' NOTA:No se ha definido el vehiculo', $textor);
		}
	}

	header("Content-type: image/png");
	imagepng($im);
	//habilitando el recurso:
	imagedestroy($im);
	imagedestroy($im_r);
	imagedestroy($im_g);
?>