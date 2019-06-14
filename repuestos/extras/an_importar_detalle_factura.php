<?php
require_once("../../connections/conex.php");

$query = sprintf("SELECT * FROM cj_cc_encabezadofactura fact_vent
WHERE idDepartamentoOrigenFactura = 2
	AND (SELECT COUNT(fact_vent_det_acc.id_factura) FROM cj_cc_factura_detalle_accesorios fact_vent_det_acc
		WHERE fact_vent_det_acc.id_factura = fact_vent.idFactura) = 0;");
$rs = mysql_query($query,$conex) or die(mysql_error()."<br>Line: ".__LINE__);
$cont = 1;
while($row = mysql_fetch_assoc($rs)) {
	//echo $cont;
	
	/* ACCESORIOS AGREGADO POR PAQUETE */
	$query2 = sprintf("SELECT 
		acc_paq.id_accesorio,
		paq_ped.costo_accesorio,
		paq_ped.precio_accesorio,
		paq_ped.iva_accesorio,
		paq_ped.porcentaje_iva_accesorio
	FROM an_paquete_pedido paq_ped
		INNER JOIN an_acc_paq acc_paq ON (paq_ped.id_acc_paq = acc_paq.Id_acc_paq)
	WHERE paq_ped.id_pedido = %s",
		valTpDato($row['numeroPedido'], "int"));
	$rs2 = mysql_query($query2,$conex) or die(mysql_error()."<br>Line: ".__LINE__);
	while($row2 = mysql_fetch_assoc($rs2)) {
		$insertSQL = sprintf("INSERT INTO cj_cc_factura_detalle_accesorios(id_factura, id_accesorio, costo_compra, precio_unitario, id_iva, iva, tipo_accesorio) VALUE (%s, %s, %s, %s, %s, %s, %s);",
			valTpDato($row['idFactura'], "int"),
			valTpDato($row2['id_accesorio'], "int"),
			valTpDato($row2['costo_accesorio'], "double"),
			valTpDato($row2['precio_accesorio'], "double"),
			valTpDato($row2['iva_accesorio'], "int"),
			valTpDato($row2['porcentaje_iva_accesorio'], "double"),
			valTpDato(1,"int"));
		//$Result1 = mysql_query($insertSQL,$conex) or die(mysql_error()."<br><br>Line: ".__LINE__);
		echo $insertSQL."<br>";
	}
	
	/* ACCESORIOS AGREGADO POR ACCESORIO */
	$query3 = sprintf("SELECT 
		acc_ped.id_accesorio,
		acc_ped.costo_accesorio,
		acc_ped.precio_accesorio,
		acc_ped.iva_accesorio,
		acc_ped.porcentaje_iva_accesorio
	FROM an_accesorio_pedido acc_ped
	WHERE acc_ped.id_pedido = %s",
		valTpDato($row['numeroPedido'], "int"));
	$rs3 = mysql_query($query3,$conex) or die(mysql_error()."<br>Line: ".__LINE__);
	while($row3 = mysql_fetch_assoc($rs3)) {
		$insertSQL = sprintf("INSERT INTO cj_cc_factura_detalle_accesorios(id_factura, id_accesorio, costo_compra, precio_unitario, id_iva, iva, tipo_accesorio) VALUE (%s, %s, %s, %s, %s, %s, %s);",
			valTpDato($row['idFactura'], "int"),
			valTpDato($row3['id_accesorio'], "int"),
			valTpDato($row3['costo_accesorio'], "double"),
			valTpDato($row3['precio_accesorio'], "double"),
			valTpDato($row3['iva_accesorio'], "int"),
			valTpDato($row3['porcentaje_iva_accesorio'], "double"),
			valTpDato(2,"int"));
		//$Result1 = mysql_query($insertSQL,$conex) or die(mysql_error()."<br><br>Line: ".__LINE__);
		echo $insertSQL."<br>";
	}
	
	echo "<br><br>";
		
	$cont++;
}
?>