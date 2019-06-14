<?php
$name_result=$_GET['name'];
?>

<html><body style="text-align:center;">

<form name="load_form" id="load_form" method="post" enctype="multipart/form-data" action="cargarvehiculo.php" target="frameload" onsubmit="" >
 <input type="hidden" name="MAX_FILE_SIZE" value="100000" />
    <input id="userfile" name="userfile" type="file" onchange="parent.loadfile(document.getElementById('load_form'));"  />
	<input type="hidden" name="name_result" value="<?php echo $name_result; ?>" />
	<input type="hidden" name="type" value="<?php echo $type; ?>" />
</form>
<div id="loader" style="display:none;text-align:center;margin:auto;"><img border="0" src="loading.gif" /><br />[ Cargando ]</div>
<div>Nombre del veh&iacute;culo a cargar imagen: <?php echo substr($name_result,0,strlen($name_resul)-4); ?></div> <br />
<form onsubmit="return false;">
<button id="baceptar" style="display:none;" type="button" onclick="parent.cerrar();"><img border="0" src="../iconos/select.png" style="padding:2px; vertical-align:middle;" />Aceptar</button>

<button id="cancelar" style="display:none;" type="button" onclick="window.open('cargarvehiculo.php?rollback=<?php echo $name_result; ?>','cambiovehiculo');"><img border="0" src="../iconos/delete.png" style="padding:2px; vertical-align:middle;" /> Cancelar</button>
</form>

</body></html>