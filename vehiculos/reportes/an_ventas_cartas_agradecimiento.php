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
$idEjecutivoVenta = getmysql(sprintf("SELECT asesor_ventas FROM an_pedido WHERE id_pedido = %s;",
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

$rowEjecutivoVenta = @mysql_fetch_assoc(@mysql_query(sprintf("SELECT
	nombre_empleado,
	email,
	nombre_cargo
FROM vw_pg_empleados
WHERE id_empleado = %s;",
	valTpDato($idEjecutivoVenta, "int")), $conex));

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
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Carta de Agradecimiento</title>
    
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
        <p>Nos es grato dirigirnos a usted en la oportunidad de felicitarle por la adquisici&oacute;n de su veh&iacute;culo nuevo <strong><?php echo htmlentities($rowEmp['familia_empresa']); ?></strong>. Esperamos disfrute ampliamente de las prestaciones del citado veh&iacute;culo y los adicionales que se le incluyen.</p>
    
        <p>As&iacute; mismo, ponemos a su disposici&oacute;n nuestras modernas instalaciones y equipos de avanzada para satisfacer sus necesidades de servicios, repuestos y accesorios originales, brindando seguridad y confort para usted y su veh&iacute;culo en nuestro taller autorizado ubicado <?php echo htmlentities($rowEmp['direccion_taller']); ?>. <?php echo (strlen($rowEmp['telefono_taller1']) > 0) ? "Cont&aacute;ctenos a trav&eacute;s de nuestra central telef&oacute;nica <b>".$rowEmp['telefono_taller1']."</b>" : ""; ?> o al correo electr&oacute;nico <b><?php echo htmlentities($rowEmp['correo']); ?></b></p>
        
        <p>Agradecemos tambi&eacute;n el habernos seleccionado como su concesionario de confianza y recordamos que estamos a su entera disposici&oacute;n para aclarar cualquier expectativa.</p>
        
        <p>&nbsp;</p>
        <p style="text-align:left">Atentamente,</p>
        
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p style="text-align:center">
            <?php echo htmlentities(strtoupper($rowEjecutivoVenta['nombre_empleado'])); ?>
            <br />
            <em><?php echo htmlentities($rowEjecutivoVenta['nombre_cargo']); ?></em>
        </p>

<?php } else { ?>

        <p>Señor(es): <?php echo $rowCliente['nombre_cliente']; ?></p>
        <p>Presente.-</p>
    
        <p>Estimado(a) Sr(a)(es): <?php echo $rowCliente['nombre_cliente']; ?></p>
        <p>Nos es grato dirigirnos a usted en la oportunidad de felicitarle por la adquisici&oacute;n de su veh&iacute;culo nuevo <strong><?php echo htmlentities($rowEmp['familia_empresa']); ?></strong>. Esperamos disfrute ampliamente de las prestaciones del citado veh&iacute;culo y los adicionales que se le incluyen.</p>
    
        <p>As&iacute; mismo, ponemos a su disposici&oacute;n nuestras modernas instalaciones y equipos de avanzada para satisfacer sus necesidades de servicios, repuestos y accesorios originales, brindando seguridad y confort para usted y su veh&iacute;culo en nuestro taller autorizado ubicado en la <?php echo htmlentities($rowEmp['direccion_taller']); ?>. <?php echo (count($arrayTelefonos) > 0) ? "Telf.: ".implode(" / ", $arrayTelefonos) : ""; ?>. O <span style="display:none;">a trav&eacute;s de nuestra p&aacute;gina web: <?php echo htmlentities($rowEmp['web']); ?>.</span> al correo electr&oacute;nico <?php echo htmlentities($rowEmp['correo']); ?>.</p>
        
        <p>Agradecemos tambi&eacute;n el habernos seleccionado como su concesionario de confianza y recordamos que estamos a su entera disposici&oacute;n para aclarar cualquier expectativa.</p>
        
        <p>Agradeciendo de antemano la colaboraci&oacute;n dispensada, le(s) saluda;</p>
        
        <p style="text-align:left">Atentamente,</p>
        
        <p>&nbsp;</p>
        <p style="text-align:center">
            <?php echo htmlentities(strtoupper($rowEjecutivoVenta['nombre_empleado'])); ?>
            <br />
            <em><?php echo htmlentities($rowEjecutivoVenta['nombre_cargo']); ?></em>
        </p>
    
<?php } ?>
</div>
</body>
</html>