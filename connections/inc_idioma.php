<?php
// VERIFICA VALORES DE CONFIGURACION (Formato Cheque Tesoreria)
$queryConfig403 = "SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa IN (SELECT emp.id_empresa
																				FROM pg_empresa emp
																					LEFT JOIN pg_empresa emp_ppal ON (emp.id_empresa_padre = emp_ppal.id_empresa)
																				WHERE emp.id_empresa <> 100
																				ORDER BY emp_ppal.id_empresa_padre ASC)";
$rsConfig403 = mysql_query($queryConfig403);
if (!$rsConfig403){ die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$totalRowsConfig403 = mysql_num_rows($rsConfig403);
$rowConfig403 = mysql_fetch_assoc($rsConfig403);

// BUSCA LOS DATOS DE LA MONEDA POR DEFECTO
$queryMoneda = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 AND predeterminada = 1;");
$rsMoneda = mysql_query($queryMoneda);
if (!$rsMoneda) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$rowMoneda = mysql_fetch_assoc($rsMoneda);

define("idArrayPais", $rowConfig403['valor']); // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
define("cIdMoneda", $rowMoneda['idmoneda']);
define("cAbrevMoneda", $rowMoneda['abreviacion']);


//// VALIDACIONES ////
$arrayValidarCI[] = "/^([PD]-[A-Z0-9]{1,10})$/"; // PASAPORTE

//// TITULOS ////
$titleFormatoCI = "\nPASAPORTE: [P,D]-0X0X0X0X0X0"."\n";
$titleFormatoRIF = "\nPASAPORTE: [P,D]-0X0X0X0X0X0"."\n";
$titleFormatoCorreo = "abc@correo.com";
$titleFormatoCarroceria = "17 Dígitos Alfanuméricos"."\n";
if (in_array(idArrayPais,array(1))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
	//// VALIDACIONES ////
	$arrayValidarCI[] = "/^([VE]-\d{5,9})$/"; // C.I.
	$arrayValidarCI[] = "/^([VE]-\d{5,9}-\d{1})$/"; // R.I.F. PERSONAL
	$arrayValidarCI[] = "/^(\d{1,5}.\d{1,5}.\d{1,5}.\d{1,2})$/"; // COLOMBIA EJEMP : 900.780.755.2
	
	$arrayValidarRIF[] = "/^([VEJGD]-\d{8}-\d{1})$/"; // R.I.F.
	$arrayValidarRIF[] = "/^(\d{1,5}.\d{1,5}.\d{1,5}.\d{1,2})$/"; // COLOMBIA EJEMP : 900.780.755.2
	
	$arrayValidarNIT[] = "/^()$/"; // PERMITE QUE NO AGREGUE NADA
	$arrayValidarNIT[] = "/^([VEJGD]-\d{8}-\d{1})$/"; // N.I.T
	$arrayValidarNIT[] = "/^(\d{1,10})$/"; // N.I.T
	$arrayValidarNIT[] = "/^(\d{1,5}.\d{1,5}.\d{1,5}.\d{1,2})$/"; // COLOMBIA EJEMP : 900.780.755.2
	
	//// TITULOS ////
	$titleFormatoCI .= "\nVENEZUELA:\nC.I.: [V,E]-00000000, R.I.F.: [V,E,J,G,D]-00000000-0"."\n";
	$titleFormatoRIF .= "\nVENEZUELA:\nR.I.F.: [V,E,J,G,D]-00000000-0"."\n";
	$titleFormatoNIT .= "\nVENEZUELA:\nN.I.T.: [V,E,J,G,D]-00000000-0"."\n";
	$titleFormatoTelf .= "\nVENEZUELA:\n+0000-0000-0000000, +0000-0000000, 0000-0000000"."\n";
} else if (in_array(idArrayPais,array(2))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
	//// VALIDACIONES ////
	$arrayValidarCI[] = "/^(\d{1,2}-\d{1,6}-\d{1,6})$/"; // C.I.V. PANAMEÑO EJEMP : 8-926-1601
	$arrayValidarCI[] = "/^(PE-\d{1,4}-\d{1,6})$/"; // C.I.V. PANAMEÑO NACIDO EN EXTRAJERO EJEMP : PE-5-687
	$arrayValidarCI[] = "/^(N-\d{1,6}-\d{1,6})$/"; // C.I.V. PANAMEÑO NATURALIZADO EJEMP : N-19-473
	$arrayValidarCI[] = "/^(E-\d{1,4}-\d{1,8})$/"; // C.I.V. EXTRAJERO DOMICILIADO EJEMP : E-19-473
	$arrayValidarCI[] = "/^(\D{1,4}-\d{1,6})$/"; // C.I.V. EJEMP: AA-67890
$arrayValidarCI[] = "/^(\d{1,2}-\D{1,2}-\d{1,5})$/"; // C.I.V. EJEMP: 13CL52997
	$arrayValidarCI[] = "/^(\d{1,9})$/"; // TEMPORAL EJEMP : 9992011
	
	$arrayValidarRIF[] = "/^(\d{2,7}\d{2,4}\d{4,6})$/"; // R.U.C. PANAMEÑO EJEMP : 12345671234123456
	$arrayValidarRIF[] = "/^(\d{4,7}0001\d{4,6})$/"; // R.U.C. PANAMEÑO EJEMP : 12345670001123456
	$arrayValidarRIF[] = "/^(\d{1,2}-NT-\d{1,4}-\d{1,5})$/"; // R.U.C. PANAMEÑO EJEMP : 8-NT-1-123
	$arrayValidarRIF[] = "/^(\d{1,9}-\d{1,4}-\d{1,8})$/"; // R.U.C. PANAMEÑO EJEMP : 1749747-1-696100 
	$arrayValidarRIF[] = "/^(\d{1,2}-\d{1,6}-\d{1,6})$/"; // C.I.V. PANAMEÑO EJEMP : 8-926-1601
	$arrayValidarRIF[] = "/^(PE-\d{1,4}-\d{1,6})$/"; // C.I.V. PANAMEÑO NACIDO EN EXTRAJERO EJEMP : PE-5-687
	$arrayValidarRIF[] = "/^(N-\d{1,6}-\d{1,6})$/"; // C.I.V. PANAMEÑO NATURALIZADO EJEMP : N-19-473
	$arrayValidarRIF[] = "/^(E-\d{1,4}-\d{1,8})$/"; // C.I.V. EXTRAJERO DOMICILIADO EJEMP : E-19-473
	$arrayValidarRIF[] = "/^(\d{1,3}-\d{1,8})$/"; // R.U.C. EXTRANJERO EJEMP : 101-044561
	$arrayValidarRIF[] = "/^(\d{1,8})$/"; // R.U.C. EJEMP : 9992011
	
	$arrayValidarNIT[] = "/^(\d{2})$/"; // D.V. (DIGITO DE VERIFICACION)
	
	//// TITULOS ////
	$titleFormatoCI .= "\nPANAMA:\nC.I.: 0-000-00000, PE-0-00000, [N,E]-00-00000"."\n";
	$titleFormatoRIF .= "\nPANAMA:\nR.U.C.: 00000000000000000, 00-NT-0000-00000, 00000000-0000-00000000"."\n";
	$titleFormatoNIT .= "\nPANAMA:\nD.V.: 00"."\n";
	$titleFormatoTelf .= "\nPANAMA:\n+0000-0000-0000, +0000-0000000, 0000-0000"."\n";
	
	
	$arrayValidarCI[] = "/^([VE]-\d{5,9})$/"; // C.I.
	$arrayValidarCI[] = "/^([VE]-\d{5,9}-\d{1})$/"; // R.I.F. PERSONAL
	
	$arrayValidarRIF[] = "/^([VEJGD]-\d{8}-\d{1})$/"; // R.I.F.
	
	//// TITULOS ////
	$titleFormatoCI .= "\nVENEZUELA:\nC.I.: [V,E]-00000000, R.I.F.: [V,E,J,G,D]-00000000-0"."\n";
	$titleFormatoRIF .= "\nVENEZUELA:\nR.I.F.: [V,E,J,G,D]-00000000-0"."\n";
} else if (in_array(idArrayPais,array(3))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
	//// VALIDACIONES ////
	$arrayValidarCI[] = "/^(\d{1,10})$/"; // LIBRE EJEMP: 1234567890
	$arrayValidarCI[] = "/^(\d{1,3}-\d{1,2}-\d{1,4})$/"; // SSN EJEMP: 1234-5678-9012
	$arrayValidarCI[] = "/^(\[0-9A-Z]{1,15})$/"; // LIBRE EJEMP: M536641871390
	
	$arrayValidarRIF[] = "/^(\d{1,10})$/"; // LIBRE EJEMP: 1234567890
	$arrayValidarRIF[] = "/^(\d{1,3}-\d{1,2}-\d{1,4})$/"; // SSN EJEMP: 123-45-6789
	
	$arrayValidarNIT[] = "/^()$/"; // PERMITE QUE NO AGREGUE NADA
	$arrayValidarNIT[] = "/^(\d{1,10})$/"; // LIBRE EJEMP: 1234567890
	$arrayValidarNIT[] = "/^(\d{1,3}-\d{1,2}-\d{1,4})$/"; // SSN EJEMP: 1234-5678-9012
	
	//// TITULOS ////
	$titleFormatoCI .= "\nPUERTO RICO:\nLIC: 0000000000, 0A0B0C0D0E0F0G, SSN: 0000, 000-00-0000"."\n";
	$titleFormatoRIF .= "\nPUERTO RICO:\nSSN: 0000, 000-00-0000"."\n";
	$titleFormatoNIT .= "\nPUERTO RICO:\nSSN: 0000, 000-00-0000"."\n";
	$titleFormatoTelf .= "\nPUERTO RICO:\n+0000-0000-0000, +0000-0000000, 0000-0000"."\n";
}

$arrayValidarCarroceria[] = "/^([[:alnum:]]{17})$/"; // EJEMP: KNDJT2A6XD7500111

$arrayTimeZone = array(1 => "America/Caracas", 2 => "America/Panama", 3 => "America/Puerto_Rico");
/*$arrayDateFormat = array(1 => "d-m-Y", 2 => "d-m-Y", 3 => "d-m-Y");
$arrayDateMask = array(1 => "99-99-9999", 2 => "99-99-9999", 3 => "99-99-9999");
$arrayDatePick = array(1 => "%d-%m-%Y", 2 => "%d-%m-%Y", 3 => "%d-%m-%Y");*/
$arrayDateFormat = array(1 => "d-m-Y", 2 => "d-m-Y", 3 => "m/d/Y");
$arrayDateMask = array(1 => "99-99-9999", 2 => "99-99-9999", 3 => "99/99/9999");
$arrayDatePick = array(1 => "%d-%m-%Y", 2 => "%d-%m-%Y", 3 => "%m/%d/%Y");
$arrayCiudadLocal = array(1 => "Caracas", 2 => "Panamá", 3 => "Bayamón");
$arrayDia = array(
	1 => array(1 => "Lunes", 2 => "Martes", 3 => "Miércoles", 4 => "Jueves", 5 => "Viernes", 6 => "Sabado", 7 => "Domingo"),
	2 => array(1 => "Lunes", 2 => "Martes", 3 => "Miércoles", 4 => "Jueves", 5 => "Viernes", 6 => "Sabado", 7 => "Domingo"),
	3 => array(1 => "Monday", 2 => "Tuesday", 3 => "Wednesday", 4 => "Thursday", 5 => "Friday", 6 => "Saturday", 7 => "Sunday"));
$arrayMes = array(
	1 => array(1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril", 5 => "Mayo", 6 => "Junio",
				7 => "Julio", 8 => "Agosto", 9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"),
	2 => array(1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril", 5 => "Mayo", 6 => "Junio",
				7 => "Julio", 8 => "Agosto", 9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"),
	3 => array(1 => "January", 2 => "February", 3 => "March", 4 => "April", 5 => "May", 6 => "June",
				7 => "July", 8 => "August", 9 => "September", 10 => "October", 11 => "November", 12 => "December"));

$arrayCI = array(1 => "C.I.", 2 => "C.I.", 3 => "LIC");
$arrayRIF = array(1 => "R.I.F.", 2 => "R.U.C.", 3 => "SSN");
$arrayNIT = array(1 => "N.I.T", 2 => "D.V.", 3 => "SSN");
$arrayUrbanizacion = array(1 => "Urbanización", 2 => "Urbanización", 3 => "Urbanización / Barrio");
$arrayCalleAv = array(1 => "Calle / Av.", 2 => "Calle / Av.", 3 => "Calle / Av.");
$arrayCasaEdif = array(1 => "Casa / Edif.", 2 => "Casa / Edif.", 3 => "Casa / Edif.");
$arrayMunicipio = array(1 => "Municipio", 2 => "Distrito", 3 => "Municipio / Pueblo");
$arrayCiudad = array(1 => "Ciudad", 2 => "Ciudad", 3 => "Ciudad");
$arrayEstado = array(1 => "Estado", 2 => "Provincia", 3 => "ZIP Code");
$arrayEmail = array(1 => "Correo Electrónico", 2 => "E-mail", 3 => "E-mail");

$arrayPrecioUnitario = array(1 => "PVMP", 2 => "Precio Unit.", 3 => "Precio Unit.");
$arrayAlmAlmacen = array(1 => "Almacén", 2 => "Almacén", 3 => "Almacén");
$arrayAlmCalle = array(1 => "Calle", 2 => "Calle", 3 => "Calle");
$arrayAlmEstante = array(1 => "Estante", 2 => "Estante", 3 => "Estante");
$arrayAlmTramo = array(1 => "Tramo", 2 => "Tramo", 3 => "Tramo");
$arrayAlmCasilla = array(1 => "Casilla", 2 => "Casilla", 3 => "Casilla");

$arraySerialCarroceria = array(1 => "Serial Carroceria", 2 => "Serial Chasis", 3 => "VIN");
$arraySerialMotor = array(1 => "Serial Motor", 2 => "Serial Motor", 3 => "Motor");
$arrayPlaca = array(1 => "Placa", 2 => "Placa", 3 => "Tablilla");
$arrayRegistroLegalizacion = array(1 => "Registro Legalización", 2 => "Registro Legalización", 3 => "Número de Registro");
$arrayKilometraje = array(1 => "Kilometraje", 2 => "Kilometraje", 3 => "Millaje");

$arrayPedidoVentaSinBanco = array(1 => "Sin entidad bancaria", 2 => "Sin entidad bancaria", 3 => "Dealer Trade");
$arrayAnticipo = array(1 => "Anticipo", 2 => "Abono", 3 => "Pronto");
$arrayInicial = array(1 => "Inicial", 2 => "Abono", 3 => "Pronto");


(date_default_timezone_get() != $arrayTimeZone[idArrayPais]) ? date_default_timezone_set($arrayTimeZone[idArrayPais]) : "";
$spanCiudadLocal = $arrayCiudadLocal[idArrayPais];
define("spanDateFormat", $arrayDateFormat[idArrayPais]);
define("spanDateMask", $arrayDateMask[idArrayPais]);
define("spanDatePick", $arrayDatePick[idArrayPais]);
$mes = $arrayMes[1]; // PARA EFECTO DEL CAMPO DEL CIERRE ANUAL
$arrayDia = $arrayDia[idArrayPais];
$arrayMes = $arrayMes[idArrayPais];

$spanCI = $arrayCI[idArrayPais];
$spanRIF = $arrayRIF[idArrayPais];
$spanNIT = $arrayNIT[idArrayPais];
$spanProvCxP = $spanCI." / ".$spanRIF;
$spanClienteCxC = $spanCI." / ".$spanRIF;
$spanUrbanizacion = $arrayUrbanizacion[idArrayPais];
$spanCalleAv = $arrayCalleAv[idArrayPais];
$spanCasaEdif = $arrayCasaEdif[idArrayPais];
$spanMunicipio = $arrayMunicipio[idArrayPais];
$spanCiudad = $arrayCiudad[idArrayPais];
$spanEstado = $arrayEstado[idArrayPais];
$spanEmail = $arrayEmail[idArrayPais];

$spanSerialCarroceria =  $arraySerialCarroceria[idArrayPais];
$spanSerialMotor =  $arraySerialMotor[idArrayPais];
$spanPlaca =  $arrayPlaca[idArrayPais];
$spanRegistroLegalizacion = $arrayRegistroLegalizacion[idArrayPais];
$spanKilometraje =  $arrayKilometraje[idArrayPais];

$spanPedidoVentaSinBanco =  $arrayPedidoVentaSinBanco[idArrayPais];
$spanAnticipo =  $arrayAnticipo[idArrayPais];
$spanInicial =  $arrayInicial[idArrayPais];

$spanPrecioUnitario = $arrayPrecioUnitario[idArrayPais];
$spanAlmAlmacen = $arrayAlmAlmacen[idArrayPais];
$spanAlmCalle = $arrayAlmCalle[idArrayPais];
$spanAlmEstante = $arrayAlmEstante[idArrayPais];
$spanAlmTramo = $arrayAlmTramo[idArrayPais];
$spanAlmCasilla = $arrayAlmCasilla[idArrayPais];
?>