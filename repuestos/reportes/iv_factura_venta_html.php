<?php
require_once("../../connections/conex.php");

$valBusq = $_REQUEST["valBusq"];

$valCadBusq = explode("|", $valBusq);

$idDocumento = $valCadBusq[0];

$queryEncabezado = sprintf("SELECT
	fact_vent.*,
	ped_vent.id_pedido_venta_propio,
	presup_vent.numero_siniestro,
	cliente.id,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	CONCAT_WS(' ', cliente.direccion, CONCAT('Edo. ', cliente.estado)) AS direccion_cliente,
	cliente.telf,
	cliente.otrotelf,
	CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado
FROM cj_cc_cliente cliente
	INNER JOIN cj_cc_encabezadofactura fact_vent ON (cliente.id = fact_vent.idCliente)
	INNER JOIN pg_empleado empleado ON (fact_vent.idVendedor = empleado.id_empleado)
	LEFT JOIN iv_pedido_venta ped_vent ON (fact_vent.numeroPedido = ped_vent.id_pedido_venta)
	LEFT JOIN iv_presupuesto_venta presup_vent ON (ped_vent.id_presupuesto_venta = presup_vent.id_presupuesto_venta)
WHERE fact_vent.idFactura = %s",
	valTpDato($idDocumento,"int"));
$rsEncabezado = mysql_query($queryEncabezado, $conex);
if (!$rsEncabezado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEncabezado = mysql_fetch_object($rsEncabezado);

$idEmpresa = $rowEncabezado->id_empresa;

$queryDetalle = sprintf("SELECT
	subseccion.id_subseccion,
	art.codigo_articulo,
	tipo_art.descripcion AS descripcion_tipo,
	art.descripcion AS descripcion_articulo,
	seccion.descripcion AS descripcion_seccion,
	fact_vent_det.cantidad,
	fact_vent_det.precio_unitario,
	fact_vent_det.id_iva,
	fact_vent_det.iva,
	fact_vent_det.id_articulo,
	fact_vent_det.id_factura_detalle
FROM iv_articulos art
	INNER JOIN iv_subsecciones subseccion ON (art.id_subseccion = subseccion.id_subseccion)
	INNER JOIN iv_tipos_articulos tipo_art ON (art.id_tipo_articulo = tipo_art.id_tipo_articulo)
	INNER JOIN iv_secciones seccion ON (subseccion.id_seccion = seccion.id_seccion)
	INNER JOIN cj_cc_factura_detalle fact_vent_det ON (art.id_articulo = fact_vent_det.id_articulo)
WHERE fact_vent_det.id_factura = %s",
	valTpDato($idDocumento,"int"));
$rsDetalle = mysql_query($queryDetalle, $conex);
if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$queryGasto = sprintf("SELECT
	fact_vent_gasto.id_factura_gasto,
	fact_vent_gasto.id_factura,
	fact_vent_gasto.tipo,
	fact_vent_gasto.porcentaje_monto,
	fact_vent_gasto.monto,
	fact_vent_gasto.estatus_iva,
	fact_vent_gasto.id_iva,
	fact_vent_gasto.iva,
	gasto.*
FROM pg_gastos gasto
	INNER JOIN cj_cc_factura_gasto fact_vent_gasto ON (gasto.id_gasto = fact_vent_gasto.id_gasto)
WHERE id_factura = %s;",
	valTpDato($idDocumento, "text"));
$rsGasto = mysql_query($queryGasto, $conex);
if (!$rsGasto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

// VERIFICA VALORES DE CONFIGURACION (Pie Página de Factura de Repuesto)
$queryConfig4 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 4 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfig4 = mysql_query($queryConfig4, $conex);
if (!$rsConfig4) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig4 = mysql_num_rows($rsConfig4);
$rowConfig4 = mysql_fetch_object($rsConfig4);

// VERIFICA VALORES DE CONFIGURACION (Mostrar Numero Control en Impresión de Factura)
$queryConfig11 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 11 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfig11 = mysql_query($queryConfig11, $conex);
if (!$rsConfig11) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig11 = mysql_num_rows($rsConfig11);
$rowConfig11 = mysql_fetch_assoc($rsConfig11);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Factura Detalle</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <style>			
	@media print{
		.botonImprimir{
			display: none;
		}
	}
	body{
		font-family:monospace;
		font-size:8pt;
	}
	.texto{
		font-weight: bold;
	}
    </style>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body style="width:720px; margin-bottom:10px; margin-top:0px">
    <table border="0" cellpadding="0px" width="100%">
    <thead>
        <tr>
            <td align="right" colspan="8">
                <button class="botonImprimir" type="button" id="btnImprimir" name="btnImprimir" style="cursor:default" onclick="window.print();"><table cellpadding="0" cellspacing="0"><tr><td><img src="../../img/iconos/ico_print.png" alt="print"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button>
            </td>
        </tr>
        <tr>
        	<td>
            	<table border="0" cellpadding="0" width="100%">
                <tr height="15px">
                    <td style="width:14%"></td>
                    <td style="width:18%"></td>
                    <td style="width:10%"></td>
                    <td style="width:14%"></td>
                    <td style="width:8%"></td>
                    <td style="width:18%"></td>
                    <td style="width:18%"></td>
                </tr>
                <tr height="15px">
                    <td colspan="4"></td>
                    <td align="center" colspan="3"><strong>Factura Serie R</strong></td>
                </tr>
                <tr align="left" height="15px">
                    <td colspan="4"><?php echo $rowEncabezado->nombre_cliente; ?></td>
                    <td align="right" colspan="2">Nro. Factura:</td>
                    <td><strong><?php echo $rowEncabezado->numeroFactura; ?></strong></td>
                </tr>
                <tr align="left" height="15px">
                    <td><?php echo $spanClienteCxC; ?>:</td>
                    <td><?php echo $rowEncabezado->ci_cliente; ?></td>
                    <td align="right">Código:</td>
                    <td align="right"><?php echo $rowEncabezado->id; ?></td>
				<?php if ($rowConfig11['valor'] == 1) { ?>
                    <td align="right" colspan="2">Nro. Control:</td>
                    <td><?php echo $rowEncabezado->numeroControl; ?></td>
				<?php } ?>
                </tr>
                <tr align="left" height="15px">
                    <td colspan="4" rowspan="3" valign="top"><?php echo htmlentities(strtoupper($rowEncabezado->direccion_cliente)); ?></td>
                    <td align="right" colspan="2">Fecha Emision:</td>
                    <td><?php echo date(spanDateFormat, strtotime($rowEncabezado->fechaRegistroFactura)); ?></td>
                </tr>
                <tr align="left" height="15px">
                    <td align="right" colspan="2">Fecha Venc.:</td>
                    <td><?php echo date(spanDateFormat, strtotime($rowEncabezado->fechaVencimientoFactura)); ?></td>
                </tr>
                <tr align="left" height="15px">
                    <td align="right" colspan="2"></td>
                    <td>
						<?php
                        if ($rowEncabezado->condicionDePago == 0) // 0 = Credito, 1 = Contado
                            echo "Cred. ".$rowEncabezado->diasDeCredito." día(s)"; ?>
                    </td>
                </tr>
                <tr align="left" height="15px">
                    <td colspan="2"></td>
                    <td align="right">TELEF.:</td>
                    <td><?php echo $rowEncabezado->telf; ?></td>
                    <td align="right" colspan="2">Nro. Pedido:</td>
                    <td><?php echo $rowEncabezado->id_pedido_venta_propio; ?></td>
                </tr>
                <tr align="left" height="15px">
                    <td colspan="3"></td>
                    <td><?php echo $rowEncabezado->otrotelf; ?></td>
                    <td align="right" colspan="2">Vendedor:</td>
                    <td><?php echo $rowEncabezado->nombre_empleado; ?></td>
                </tr>
                </table>
            </td>
        </tr> 
    </thead>
    <tbody style="height:600px">
    	<tr>
        	<td valign="top">
            	<table border="0" cellpadding="0px" width="100%">
                <tr align="left">
                    <td colspan="5" style="border-bottom:1px dashed;"></td>
                </tr>
                <tr align="center" height="25px">
                    <td class="texto" style="width:20%">Código</td>
                    <td class="texto" style="width:36%">Descripción</td>
                    <td class="texto" style="width:12%">Cantidad</td>
                    <td class="texto" style="width:16%"><?php echo $spanPrecioUnitario; ?></td>
                    <td class="texto" style="width:16%">Total</td>
                </tr>
                <tr align="left">
                    <td colspan="5" style="border-top:1px dashed;"></td>
                </tr>
				<?php
                if (mysql_num_rows($rsDetalle) > 0) {
                    $iFila = 0;
                    while ($rowDetalle = mysql_fetch_object($rsDetalle)) {
                        $iFila++;
                        $codigo = elimCaracter(htmlentities(utf8_decode(substr($rowDetalle->codigo_articulo), 0, 60)), ";");
                        $descripcion = htmlentities(utf8_decode(substr($rowDetalle->descripcion_articulo, 0, 60)));
                        $cantidad = $rowDetalle->cantidad;
                        $precio = $rowDetalle->precio_unitario;
                        $total = $rowDetalle->precio_unitario * $rowDetalle->cantidad; ?>
                        <tr align="left">
                            <td><?php echo $codigo; ?></td>
                            <td><?php echo $descripcion; ?></td>
                            <td align="right"><?php echo number_format($cantidad, 2); ?></td>
                            <td align="right"><?php echo number_format($precio, 2); ?></td>
                            <td align="right"><?php echo number_format($total, 2); ?></td>
                        </tr>
                    <?php
                    }
                } ?>	
                </table>
            </td>
        </tr>
        <?php
		if ($totalRowsConfig4 > 0) { ?>
        <tr>
        	<td valign="bottom"><?php echo htmlentities(($rowConfig4->valor)); ?></td>
        </tr>
        <?php
		} ?>
    </tbody>
    <tfoot>
    	<tr>
        	<td valign="top">
            	<table border="0" cellpadding="0px" width="100%">
                <tr>
                    <td style="width:20%"></td>
                    <td style="width:30%"></td>
                    <td style="width:18%"></td>
                    <td style="width:16%"></td>
                    <td style="width:16%"></td>
                </tr>
                <tr align="left">
                    <td colspan="5" style="border-bottom:1px dashed;"></td>
                </tr>
                <tr>
                    <td colspan="2" rowspan="15" valign="top">
                    	<table width="100%">
						<?php
						while ($rowGasto = mysql_fetch_object($rsGasto)) {
							if ($rowGasto->estatus_iva == 0) {
								$totalGastosSinIva += $rowGasto->monto;
							} else if ($rowGasto->estatus_iva == 1) {
								$totalGastosConIva += $rowGasto->monto;
							} ?>
                        <tr align="left">
                        	<td width="50%"><?php echo strtoupper($rowGasto->nombre); ?></td>
                        	<td align="right" width="20%"><?php echo number_format($rowGasto->porcentaje_monto, 2)."%"; ?></td>
                        	<td align="right" width="30%"><?php echo number_format($rowGasto->monto, 2); ?></td>
                        </tr>
						<?php
                        } ?>	
                        </table>
                    	<?php
                        if (strlen($rowEncabezado->observacionFactura) > 0)
							echo htmlentities($rowEncabezado->observacionFactura)."<br>"; ?>
                        <?php
                        if (strlen($rowEncabezado->numero_siniestro) > 0)
							echo "Nro. Siniestro: ".$rowEncabezado->numero_siniestro."<br>"; ?>
                    </td>
                    <td align="right" class="texto">Subtotal:</td>
                    <td></td>
                    <td align="right"><strong><?php echo number_format($rowEncabezado->subtotalFactura, 2); ?></strong></td>
                </tr>
                <tr align="right">
                    <td class="texto">Descuento:</td>
                    <td><?php echo number_format(($rowEncabezado->descuentoFactura * 100) / $rowEncabezado->subtotalFactura, 2); ?>%</td>
                    <td><strong><?php echo number_format($rowEncabezado->descuentoFactura, 2); ?></strong></td>
                </tr>
                <?php if ($totalGastosConIva != 0) { ?>
                <tr align="right">
                    <td class="texto">Gastos con Imp:</td>
                    <td></td>
                    <td><strong><?php echo number_format($totalGastosConIva, 2); ?></strong></td>
                </tr>
                <?php } ?>
                <tr align="right">
                    <td class="texto">Base Imponible:</td>
                    <td></td>
                    <td><strong><?php echo number_format($rowEncabezado->baseImponible, 2) ?></strong></td>
                </tr>
                <?php
				// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1;");
				$rsIva = mysql_query($queryIva);
				if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$rowIva = mysql_fetch_object($rsIva); ?>
                <tr align="right">
                    <td class="texto"><?php echo $rowIva->observacion; ?>:</td>
                    <td><?php echo number_format($rowEncabezado->porcentajeIvaFactura, 2); ?>%</td>
                    <td><strong><?php echo number_format($rowEncabezado->calculoIvaFactura, 2); ?></strong></td>
                </tr>
                <?php
				if ($rowEncabezado->calculoIvaDeLujoFactura > 0) {
					// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
					$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (2) AND iva.estado = 1 AND iva.activo = 1;");
					$rsIva = mysql_query($queryIva);
					if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$rowIva = mysql_fetch_object($rsIva); ?>
                <tr align="right">
                    <td class="texto"><?php echo $rowIva->observacion; ?>:</td>
                    <td><?php echo number_format($rowEncabezado->porcentajeIvaDeLujoFactura, 2); ?>%</td>
                    <td><strong><?php echo number_format($rowEncabezado->calculoIvaDeLujoFactura, 2); ?></strong></td>
                </tr>
                <?php } ?>
                <?php if ($totalGastosSinIva != 0) { ?>
                <tr align="right">
                    <td class="texto">Gastos sin Imp:</td>
                    <td></td>
                    <td><strong><?php echo number_format($totalGastosSinIva, 2); ?></strong></td>
                </tr>
                <?php } ?>
                <?php if ($rowEncabezado->montoNoGravado > 0) { ?>
                <tr align="right">
                    <td class="texto">Monto No Gravado:</td>
                    <td></td>
                    <td><strong><?php echo number_format($rowEncabezado->montoNoGravado, 2); ?></strong></td>
                </tr>
                <?php } ?>
                <tr>
                    <td colspan="3" style="border-top:1px dashed;"> </td>
                </tr>
                <tr align="right">
                    <td class="texto">Total:</td>
                    <td></td>
                    <td><strong><?php echo number_format($rowEncabezado->montoTotalFactura, 2); ?></strong></td>
                </tr>
                </table>
			</td>
		</tr>
    </tfoot>
    </table>
</body>
</html>