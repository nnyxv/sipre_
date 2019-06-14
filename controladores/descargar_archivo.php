<?php
$basefichero = basename("../".$_GET['ruta'].".".$_GET['tipo']);

switch($_GET['tipo']) {
	case "ico" : header("Content-Type: image/x-icon"); break;
}
header("Content-Length: ".filesize("../".$_GET['ruta'].".".$_GET['tipo']));
header("Content-Disposition: attachment; filename=".str_replace(" ", "_", $basefichero)."");
readfile("../".$_GET['ruta'].".".$_GET['tipo']);
?>