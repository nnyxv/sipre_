<?php
require_once('connections/conex.php');

@session_start();

// VERIFICA EL DESBLOQUEO DE LA SESION
$rs = @mysql_query(sprintf("SELECT lock_s FROM pg_block_log block_log
WHERE (block_log.usuario LIKE %s
	OR block_log.id_session = %s)
	AND lock_s = 1;",
	valTpDato($_GET['nombreUsuario'], "text"),
	valTpDato(session_id(), "text")),$conex);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRows = mysql_num_rows($rs);
$row = mysql_fetch_assoc($rs);

if ($totalRows == 0) {
	@mysql_query(sprintf("DELETE FROM pg_block_log
	WHERE usuario LIKE %s
		OR id_session = %s;",
		valTpDato($_GET['nombreUsuario'], "text"),
		valTpDato(session_id(), "text")),$conex);
	
	unset($_SESSION['session_error']);
	unset($_SESSION['refer']);
	
	echo "<script>alert('Sistema desbloqueado');</script>";
} else {
	echo "<script>alert('Continua bloqueado, consulte a soporte al usuario.');</script>";
}
echo "<script>window.location='index.php';</script>";
?>