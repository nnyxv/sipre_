<?php
$name_result=$_GET['name'];
$file=$_GET['file'];
?>

<html>

<body style="text-align:center;" >

<form name="load_form" id="load_form" method="post" enctype="multipart/form-data" action="uploader.php" target="frameload" onsubmit="" >

    <input id="userfile" name="userfile" type="file" onchange="parent.loadfile(document.getElementById('load_form'));"  />
	<input type="hidden" name="name_result" value="<?php echo $name_result; ?>" />
	<input type="hidden" name="type" value="<?php echo $type; ?>" />
	<input type="hidden" name="file" id="file" value="<?php echo $type; ?>" />
</form>
<div id="loader" style="display:none;text-align:center;margin:auto;"><img border="0" src="loading.gif" /><br />[ Cargando ]</div>
<br />
<form onsubmit="return false;">
<button id="baceptar" style="display:none;" type="button" onclick="parent.cerrar(document.getElementById('file').value);"><img border="0" src="../img/iconos/select.png" style="padding:2px; vertical-align:middle;" />Aceptar</button>

<button id="cancelar"  type="button" onclick="window.parent.close();"><img border="0" src="../img/iconos/delete.png" style="padding:2px; vertical-align:middle;" /> Cancelar</button>
</form>

</body></html>