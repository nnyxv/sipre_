<?php
require_once("../../connections/conex.php");

require_once("../../inc_sesion.php");

$redirect = "nobody.htm";
validaModulo("an_agradecimiento");
conectar();

$idEmpresa = getmysql(sprintf("SELECT id_empresa FROM an_pedido WHERE id_pedido = %s;",
	valTpDato($_GET['id'], "int")));
$idCliente = getmysql(sprintf("SELECT id_cliente FROM an_pedido WHERE id_pedido = %s;",
	valTpDato($_GET['id'], "int")));
$idGteVentas = getmysql(sprintf("SELECT gerente_ventas FROM an_pedido WHERE id_pedido = %s;",
	valTpDato($_GET['id'], "int")));

$queryConfig403 = sprintf("SELECT *
FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
valTpDato($idEmpresa, "int"));
$rsConfig403 = mysql_query($queryConfig403);
if (!$rsConfig403) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$rowConfig403 = mysql_fetch_assoc($rsConfig403);

$queryEmp = sprintf("SELECT empresa.*,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM vw_iv_empresas_sucursales vw_iv_emp_suc
	INNER JOIN pg_empresa empresa ON (vw_iv_emp_suc.id_empresa_reg = empresa.id_empresa)
WHERE vw_iv_emp_suc.id_empresa_reg = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp, $conex) or die(mysql_error());
$rowEmp = mysql_fetch_assoc($rsEmp);

$rowCliente = @mysql_fetch_assoc(@mysql_query(sprintf("SELECT
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
FROM cj_cc_cliente cliente
WHERE cliente.id = %s;",
	valTpDato($idCliente, "int")), $conex));

$join_empleados = "pg_empleado
	INNER JOIN pg_cargo_departamento ON (pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento)
	INNER JOIN pg_cargo ON (pg_cargo_departamento.id_cargo = pg_cargo.id_cargo) ";

// 300 = GERENTE DE OPERACIONES
$rowGteOperaciones = @mysql_fetch_assoc(@mysql_query("SELECT
	CONCAT_WS(' ', nombre_empleado, apellido) AS nombre_empleado,
	email,
	nombre_cargo
FROM ".$join_empleados."
WHERE clave_filtro = 300
	AND activo = 1;", $conex));

// 4 = GERENTE SERVICIOS (POSTVENTA)
$rowGtePostventa = @mysql_fetch_assoc(@mysql_query("SELECT
	CONCAT_WS(' ', nombre_empleado, apellido) AS nombre_empleado,
	email,
	nombre_cargo
FROM ".$join_empleados."
WHERE clave_filtro = 4
	AND activo = 1;", $conex));

// 400 = JEFE REPUESTOS
$rowJefeRepuestos = @mysql_fetch_assoc(@mysql_query("SELECT
	CONCAT_WS(' ', nombre_empleado, apellido) AS nombre_empleado,
	email,
	nombre_cargo
FROM ".$join_empleados."
WHERE clave_filtro = 400
	AND activo = 1;", $conex));

// 6 = JEFE TALLER
$rowJefeTaller = @mysql_fetch_assoc(@mysql_query("SELECT
	CONCAT_WS(' ', nombre_empleado, apellido) AS nombre_empleado,
	email,
	nombre_cargo
FROM ".$join_empleados."
WHERE clave_filtro = 6
	AND activo = 1;", $conex));


$sqlBusq = "";
if ($idGteVentas > 0) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empleado = %s",
		valTpDato($idGteVentas, "int"));
} else {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(id_empresa = %s
	AND clave_filtro = 2
	AND activo = 1",
		valTpDato($idEmpresa, "int"));
}

// 2 = GERENTE VENTAS VEHICULOS
$rowGteVentas = @mysql_fetch_assoc(@mysql_query(sprintf("SELECT
	nombre_empleado,
	email,
	nombre_cargo
FROM vw_pg_empleados %s;", $sqlBusq), $conex));

if ($rowGtePostventa['nombre_empleado'] == '') {
	$mensaje .= "No se ha definido un Gerente de Postventa Activo. ";
}
if ($rowJefeRepuestos['nombre_empleado'] == '') {
	$mensaje .= "No se ha definido un Jefe de Repuestos Activo. ";
}

(strlen($rowEmp['telefono1']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono1'] : "";
(strlen($rowEmp['telefono2']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono2'] : "";
(strlen($rowEmp['telefono3']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono3'] : "";
(strlen($rowEmp['telefono4']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono4'] : "";
(strlen($rowEmp['telefono_taller1']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono_taller1'] : "";
(strlen($rowEmp['telefono_taller2']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono_taller2'] : "";
(strlen($rowEmp['telefono_taller3']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono_taller3'] : "";
(strlen($rowEmp['telefono_taller4']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono_taller4'] : "";
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Carta de Bienvenida</title>
    
    <link rel="stylesheet" type="text/css" href="../../style/styleRafk.css">
    
    <style type="text/css">
	body{
        font-family:Arial, Helvetica, sans-serif;
		font-size:12px;
    }
    p{
        line-height:150%;
        text-align:justify;
        /*PAGE-BREAK-AFTER: always;*/
    }
    .empleado_table tbody tr td{
        padding-top:7px;
        paddign-bottom:7px;
    }
    .parrafo{
        text-indent:1cm;
    }
    .subindice{
        font-size:10px;
    }
    @media print {
        body{
        }
    }
    </style>
</head>

<body <?php if($_GET['view']=='print'){ echo 'onload="window.print();"'; }?> >
<?php 
if ($mensaje != '') {
	echo "<script>
	alert('".$mensaje."');
	</script>";
} ?>
<div class="marco">
    <table>
    <tr>
        <td><img src="../../<?php echo $rowEmp['logo_familia'];?>" height="90"></td>
        <td class="textoNegroNegrita_10px">
            <table width="100%">
            <tr align="left">
                <td><?php echo utf8_encode($rowEmp['nombre_empresa']); ?></td>
            </tr>
            <tr align="left" <?php echo (strlen($rowEmp['rif']) > 0) ? "" : "style=\"display:none\""; ?>>
                <td><?php echo (strlen($rowEmp['rif']) > 0) ? $spanRIF.": ".utf8_encode($rowEmp['rif']) : ""; ?></td>
            </tr>
            <tr align="left" <?php echo (strlen($rowEmp['direccion']) > 0) ? "" : "style=\"display:none\""; ?>>
                <td><?php echo utf8_encode($rowEmp['direccion']); ?></td>
            </tr>
            <tr align="left" <?php echo (count($arrayTelefonos) > 0) ? "" : "style=\"display:none\""; ?>>
                <td><?php echo (count($arrayTelefonos) > 0) ? "Telf.: ".implode(" / ", $arrayTelefonos) : ""; ?></td>
            </tr>
            <tr align="left" <?php echo (strlen($rowEmp['web']) > 0) ? "" : "style=\"display:none\""; ?>>
                <td><?php echo utf8_encode($rowEmp['web']); ?></td>
            </tr>
            </table>
        </td>
    </tr>
    </table>
	
    <p style="text-align:right">
		<?php echo htmlentities($rowEmp['ciudad_empresa']).", ".date("d")." de ".$arrayMes[intval(date("n"))]." de ".date("Y"); ?>
    </p>
	
<?php  if (in_array($rowConfig403['valor'],array(2))) { // 2 = Panama ?>

		<p>Señor(a): <br><b><?php echo $rowCliente['nombre_cliente']; ?></b> <br><?php echo htmlentities($rowEmp['ciudad_empresa']); ?></p>
    
        <p class="parrafo">Es grato para nuestro equipo dirigirnos a usted, para darle la bienvenida y felicitarle por la adquisici&oacute;n de su veh&iacute;culo <strong><?php echo htmlentities($rowEmp['familia_empresa']); ?></strong>, estamos seguros que nuestra pol&iacute;tica de calidad, excelencia profesional y el trato que usted se merece, har&aacute;n que esta experiencia de compra se magnifique en el disfrute de su veh&iacute;culo.</p>
    
        <p class="parrafo">De esta misma forma, le reiteramos nuestra disposici&oacute;n de atenderle en nuestras instalaciones a fines de satisfacer cualquier requerimiento en Servicio, Repuestos y Accesorios Originales donde le ofreceremos seguridad y confort, para usted y su veh&iacute;culo, donde contamos con herramientas de diagn&oacute;stico <b><?php echo htmlentities($rowEmp['familia_empresa']); ?></b> y técnicos formados en los m&eacute;todos de la marca. <?php echo (strlen($rowEmp['telefono_taller1']) > 0) ? "Cont&aacute;ctenos a trav&eacute;s de nuestra central telef&oacute;nica <b>".$rowEmp['telefono_taller1']."</b>" : ""; ?> o a través del correo electr&oacute;nico <b><?php echo htmlentities($rowEmp['correo']); ?></b></p>
        
        <p class="parrafo">Finalmente nos despedimos agradeciendo su elecci&oacute;n al aceptarnos como su concesionario de confianza y reiteramos nuestra disposici&oacute;n en atenderle para satisfacer cualquier requerimiento.</p>
        
        <p>&nbsp;</p>
        <p class="parrafo">Muy Cordialmente,</p>
        
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p style="text-align:center">
            <?php echo htmlentities(strtoupper($rowGteVentas['nombre_empleado'])); ?>
            <br />
            <em><?php echo htmlentities($rowGteVentas['nombre_cargo']); ?></em>            
        </p>
<?php } else { ?>
    
        <p>Estimado(a) Sr(a)(es): <?php echo $rowCliente['nombre_cliente']; ?></p>
        <p>Presente.-</p>
    
        <p class="parrafo">Es grato para nuestro equipo dirigirnos a usted, para darle la bienvenida y felicitarle por la adquisici&oacute;n de su veh&iacute;culo <strong><?php echo htmlentities($rowEmp['familia_empresa']); ?></strong>, estamos seguros que nuestra pol&iacute;tica de calidad, excelencia profesional y el trato que usted se merece, har&aacute;n que esta experiencia de compra se magnifique en el disfrute de su veh&iacute;culo.</p>
    
        <p class="parrafo">De esta misma forma, le reiteramos nuestra disposici&oacute;n de atenderle en nuestras instalaciones a fines de satisfacer cualquier requerimiento en Servicio, Repuestos y Accesorios Originales donde le ofreceremos seguridad y confort, para usted y su veh&iacute;culo, ya que contamos con equipos avanzados y tecnolog&iacute;a de primera. Cont&aacute;ctenos telef&oacute;nicamente a trav&eacute;s de los Nros. <?php echo (count($arrayTelefonos) > 0) ? "Telf.: ".implode(" / ", $arrayTelefonos) : ""; ?> Fax. <?php echo htmlentities($rowEmp['fax']); ?>, o a los correos electr&oacute;nicos:</p>
        
        <table style="width:100%;" class="empleado_table">
        <tr <?php echo (strlen($rowGteOperaciones['nombre_empleado']) > 0) ? "" : "style=\"display:none\""; ?>>
            <td nowrap="nowrap"><?php echo htmlentities($rowGteOperaciones['nombre_cargo']) ?></td>
            <td><?php echo htmlentities($rowGteOperaciones['nombre_empleado']) ?></td>
            <td><?php echo htmlentities($rowGteOperaciones['email']) ?></td>
        </tr>
        <tr <?php echo (strlen($rowGtePostventa['nombre_empleado']) > 0) ? "" : "style=\"display:none\""; ?>>
            <td nowrap="nowrap"><?php echo htmlentities($rowGtePostventa['nombre_cargo']) ?></td>
            <td><?php echo htmlentities($rowGtePostventa['nombre_empleado']) ?></td>
            <td><?php echo htmlentities($rowGtePostventa['email']) ?></td>
        </tr>
        <tr <?php echo (strlen($rowJefeRepuestos['nombre_empleado']) > 0) ? "" : "style=\"display:none\""; ?>>
            <td nowrap="nowrap"><?php echo htmlentities($rowJefeRepuestos['nombre_cargo']) ?></td>
            <td><?php echo htmlentities($rowJefeRepuestos['nombre_empleado']) ?></td>
            <td><?php echo htmlentities($rowJefeRepuestos['email']) ?></td>
        </tr>
        <tr <?php echo (strlen($rowJefeTaller['nombre_empleado']) > 0) ? "" : "style=\"display:none\""; ?>>
            <td nowrap="nowrap"><?php echo htmlentities($rowJefeTaller['nombre_cargo']) ?></td>
            <td><?php echo htmlentities($rowJefeTaller['nombre_empleado']) ?></td>
            <td><?php echo htmlentities($rowJefeTaller['email']) ?></td>
        </tr>
        </table>
    
        <p class="parrafo">Solicite su cita en Servicio con nuestros asesores, quienes est&aacute;n dispuestos a ofrecerles orientaci&oacute;n y atenci&oacute;n a sus necesidades.</p>
        
        <p class="parrafo">Finalmente nos despedimos agradeciendo su elecci&oacute;n al aceptarnos como su concesionario de confianza y reiteramos nuestra disposici&oacute;n en atenderle para satisfacer cualquier necesidad</p>
        
        <p class="parrafo">Muy Cordialmente,</p>
        
        <p>&nbsp;</p>
        <p style="text-align:center">
            <?php echo htmlentities(strtoupper($rowGteVentas['nombre_empleado'])); ?>
            <br />
            <em><?php echo htmlentities($rowGteVentas['nombre_cargo']); ?></em>
            <br />
            <?php echo htmlentities($rowGteVentas['email']); ?>
        </p>
    
    
<?php } ?>
	
</div>
</body>
</html>