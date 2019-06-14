<?php
/** Error reporting */
/*error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);*/

//Si la variable archivo que pasamos por URL no esta 
//establecida acabamos la ejecucion del script.
//echo $_GET['file'];
if (!isset($_GET['file']) || empty($_GET['file'])) {
	//var_dump($_GET['file']);
   exit();
}

//Utilizamos basename por seguridad, devuelve el 
//nombre del file eliminando cualquier ruta. 
$archivo = basename($_GET['file']);

$ruta = 'uploaded/download/'.$archivo;

if (is_file($ruta)){

   header('Content-Type: application/force-download');
   header('Content-Disposition: attachment; filename='.$archivo);
   header('Content-Transfer-Encoding: binary');
   header('Content-Length: '.filesize($ruta));

   readfile($ruta);
} else {
	//no existe
   exit();
   
   }
?>
