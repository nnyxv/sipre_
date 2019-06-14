<?php
function cabeceraTxt($ar, $idEmpresa) {
	global $spanRIF;
	
	// BUSCA LOS DATOS DE LA EMPRESA
	$queryEmp = sprintf("SELECT
		id_empresa_reg,
		IF (id_empresa_suc > 0, CONCAT_WS(' - ', nombre_empresa, nombre_empresa_suc), nombre_empresa) AS nombre_empresa,
		rif,
		direccion,
		telefono1,
		telefono2,
		web,
		logo_familia
	FROM vw_iv_empresas_sucursales
	WHERE id_empresa_reg = %s
		OR ((%s = -1 OR %s IS NULL)AND id_empresa_suc IS NULL);",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsEmp = mysql_query($queryEmp);
	if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsEmp = mysql_num_rows($rsEmp);
	$rowEmp = mysql_fetch_assoc($rsEmp);
	
	//Titulo del libro y seguridad
	if ($totalRowsEmp > 0) {
		fputs($ar, str_pad(utf8_encode($rowEmp['nombre_empresa']), 69, " ", STR_PAD_RIGHT)); fputs($ar, " ");
		fputs($ar,chr(13).chr(10));
		fputs($ar, str_pad(utf8_encode($spanRIF.": ".$rowEmp['rif']), 69, " ", STR_PAD_RIGHT)); fputs($ar, " ");
		fputs($ar,chr(13).chr(10));
		fputs($ar, str_pad(utf8_encode($rowEmp['direccion']), 69, " ", STR_PAD_RIGHT)); fputs($ar, " ");
		fputs($ar,chr(13).chr(10));
		fputs($ar, str_pad(utf8_encode("Telf.: ".$rowEmp['telefono1']." ".$rowEmp['telefono2']), 69, " ", STR_PAD_RIGHT)); fputs($ar, " ");
		fputs($ar,chr(13).chr(10));
		fputs($ar, str_pad(utf8_encode($rowEmp['web']), 69, " ", STR_PAD_RIGHT)); fputs($ar, " ");
		fputs($ar,chr(13).chr(10));
		fputs($ar, str_pad("http://".$rowEmp['web'], 69, " ", STR_PAD_RIGHT)); fputs($ar, " ");
	}
}
?>