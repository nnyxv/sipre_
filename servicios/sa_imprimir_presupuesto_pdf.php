<?php
//LO USA SA FORMATO DE ORDEN EN MUCHOS OTROS TAMBIEN
require_once("../connections/conex.php");
require_once('clases/fpdf/fpdf.php');
require_once('clases/fpdf/fpdf_print.inc.php');
require_once('clases/barcode128.inc.php');

$ruta = "clases/temp_codigo/img_codigo.png";

$valCadBusq = explode("|",$_GET['valBusq']);

$idDocumento = $valCadBusq[0];

//anterior gregor
/*
//Numero de Orden
if ($valCadBusq[1] == 1){//presupuesto
$aux = getBarcode("450",'clases/temp_codigo/img_codigo');
}

if ($valCadBusq[1] == 2){//orden de servicio
$sqlNumeroOrden = sprintf("SELECT numero_orden FROM sa_orden WHERE id_orden = %s LIMIT 1",
					valTpDato($idDocumento,"int"));
$queryNumeroOrden = mysql_query($sqlNumeroOrden) or die(mysql_error()."<br><br>Line: ".__LINE__);
$datosNumeroOrden = mysql_fetch_assoc($queryNumeroOrden);
$aux = getBarcode($datosNumeroOrden["numero_orden"],'clases/temp_codigo/img_codigo');

}else{
	$aux = getBarcode($idDocumento,'clases/temp_codigo/img_codigo');
}
*/

//nuevo de prueba

if ($valCadBusq[1] == 1){//presupuesto y cotizacion

	$sqlNumeroPresupuesto = sprintf("SELECT numero_presupuesto FROM sa_presupuesto WHERE id_presupuesto = %s LIMIT 1",
				valTpDato($idDocumento,"int"));
	$queryNumeroPresupuesto = mysql_query($sqlNumeroPresupuesto) or die(mysql_error()."<br><br>Line: ".__LINE__);
	$datosNumeroPresupuesto = mysql_fetch_assoc($queryNumeroPresupuesto);
	$aux = getBarcode($datosNumeroPresupuesto["numero_presupuesto"],'clases/temp_codigo/img_codigo');
	
	/*if ($rowPrincipal['tipo_presupuesto'] == 1){//presupuesto
	
	}else{//cotizacion
		
	}*/
		
}else{//Vale salida y orden

	if($valCadBusq[2] == 3){//vale salida
		$sqlNumeroValeSalida = sprintf("SELECT numero_vale FROM sa_vale_salida WHERE id_vale_salida = %s LIMIT 1",
					valTpDato($idDocumento,"int"));
		$queryNumeroValeSalida = mysql_query($sqlNumeroValeSalida) or die(mysql_error()."<br><br>Line: ".__LINE__);
		$datosNumeroValeSalida = mysql_fetch_assoc($queryNumeroValeSalida);
		$aux = getBarcode($datosNumeroValeSalida["numero_vale"],'clases/temp_codigo/img_codigo');
	}else{//orden
	
		$sqlNumeroOrden = sprintf("SELECT numero_orden FROM sa_orden WHERE id_orden = %s LIMIT 1",
					valTpDato($idDocumento,"int"));
		$queryNumeroOrden = mysql_query($sqlNumeroOrden) or die(mysql_error()."<br><br>Line: ".__LINE__);
		$datosNumeroOrden = mysql_fetch_assoc($queryNumeroOrden);
		$aux = getBarcode($datosNumeroOrden["numero_orden"],'clases/temp_codigo/img_codigo');
		
	}
		
}


if ($valCadBusq[1] == 1){//presupuesto y cotizacion
	$queryPrincipal = sprintf("SELECT tipo_presupuesto FROM sa_presupuesto WHERE id_presupuesto = %s",
		valTpDato($idDocumento,"int"));
	$rsPrincipal = mysql_query($queryPrincipal) or die(mysql_error()."<br><br>Line: ".__LINE__);
	$rowPrincipal = mysql_fetch_array($rsPrincipal);

	if ($rowPrincipal['tipo_presupuesto'] == 1){//presupuesto
		$queryCliente = sprintf("SELECT
			sa_presupuesto.id_presupuesto,
			sa_presupuesto.fecha_presupuesto,
			sa_presupuesto.fecha_vencimiento,
			cj_cc_cliente.nombre AS nombre_cliente,
			cj_cc_cliente.apellido AS apellido_cliente,
			CONCAT_WS('-', cj_cc_cliente.lci, cj_cc_cliente.ci) AS cedula_cliente,
			cj_cc_cliente.direccion AS direccion_cliente,
			cj_cc_cliente.telf AS telf_cliente,
			vw_sa_vales_recepcion.numeracion_recepcion,
			vw_sa_vales_recepcion.nom_uni_bas,
			vw_sa_vales_recepcion.des_uni_bas,
			vw_sa_vales_recepcion.nom_marca,
			vw_sa_vales_recepcion.color,
			vw_sa_vales_recepcion.placa,
			vw_sa_vales_recepcion.chasis,
			vw_sa_vales_recepcion.kilometraje,
			vw_sa_vales_recepcion.kilometraje_recepcion,
			vw_sa_vales_recepcion.des_modelo,
			vw_sa_vales_recepcion.fecha_venta,
			pg_empleado.nombre_empleado,
			pg_empleado.apellido AS apellido_empleado,
			sa_presupuesto.numero_presupuesto AS nro_documento,
			sa_presupuesto.subtotal,
			sa_presupuesto.base_imponible,
			sa_presupuesto.porcentaje_descuento,
			sa_presupuesto.subtotal_descuento,
			sa_presupuesto.id_empresa,
			sa_presupuesto.tipo_presupuesto,
			sa_presupuesto.id_orden,
			sa_presupuesto.iva,
			sa_presupuesto.idIva,
			sa_presupuesto.monto_exento,
			sa_presupuesto.total_presupuesto AS total,
			sa_presupuesto.id_tipo_orden,
			vw_sa_vales_recepcion.ano_uni_bas,
			an_transmision.nom_transmision,
			sa_orden.numero_orden,
			sa_orden.id_recepcion,
			sa_orden.tiempo_finalizado,
			sa_orden.tiempo_entrega,
			vw_sa_vales_recepcion.nro_llaves
		FROM
			sa_presupuesto
			INNER JOIN cj_cc_cliente ON (sa_presupuesto.id_cliente = cj_cc_cliente.id)
			INNER JOIN sa_orden ON (sa_presupuesto.id_orden = sa_orden.id_orden)
			INNER JOIN vw_sa_vales_recepcion ON (sa_orden.id_recepcion = vw_sa_vales_recepcion.id_recepcion)
			INNER JOIN pg_empleado ON (sa_presupuesto.id_empleado = pg_empleado.id_empleado)
			INNER JOIN an_uni_bas ON (vw_sa_vales_recepcion.id_uni_bas = an_uni_bas.id_uni_bas)
			INNER JOIN an_transmision ON (an_uni_bas.trs_uni_bas = an_transmision.id_transmision)
		WHERE
			sa_presupuesto.id_presupuesto = %s",
			valTpDato($idDocumento,"int"));
                
		$sqlMultiplesIvas = sprintf("SELECT 
										pg_iva.observacion,
										sa_presupuesto_iva.base_imponible, 
										sa_presupuesto_iva.subtotal_iva, 
										sa_presupuesto_iva.id_iva, 
										sa_presupuesto_iva.iva 
									FROM sa_presupuesto_iva
									INNER JOIN pg_iva ON sa_presupuesto_iva.id_iva = pg_iva.idIva 
									WHERE id_presupuesto = %s",
				valTpDato($idDocumento,"int"));

		$texto_presupuesto_cotizacion = "PRESUPUESTO";    
		$descDcto = "NRO PRESUPUESTO"; 
                
	}else{//cotizacion
		$queryCliente = sprintf("SELECT
			sa_presupuesto.id_presupuesto,
			sa_presupuesto.fecha_presupuesto,
			sa_presupuesto.fecha_vencimiento,
			cj_cc_cliente.nombre AS nombre_cliente,
			cj_cc_cliente.apellido AS apellido_cliente,
			CONCAT_WS('-', cj_cc_cliente.lci, cj_cc_cliente.ci) AS cedula_cliente,
			cj_cc_cliente.direccion AS direccion_cliente,
			cj_cc_cliente.telf AS telf_cliente,
			pg_empleado.nombre_empleado,
			pg_empleado.apellido AS apellido_empleado,
			sa_presupuesto.numero_presupuesto AS nro_documento,
			sa_presupuesto.subtotal,
			sa_presupuesto.base_imponible,
			sa_presupuesto.subtotal_iva,
			sa_presupuesto.porcentaje_descuento,
			sa_presupuesto.subtotal_descuento,
			sa_presupuesto.id_empresa,
			sa_presupuesto.tipo_presupuesto,
			sa_presupuesto.id_orden,
			sa_presupuesto.iva,
			sa_presupuesto.idIva,
			sa_presupuesto.id_tipo_orden,
			sa_presupuesto.monto_exento,
			sa_presupuesto.total_presupuesto AS total,
			an_uni_bas.nom_uni_bas,
			an_marca.nom_marca,
			an_modelo.des_modelo,
			an_transmision.nom_transmision
		FROM
			sa_presupuesto
			INNER JOIN cj_cc_cliente ON (sa_presupuesto.id_cliente = cj_cc_cliente.id)
			INNER JOIN pg_empleado ON (sa_presupuesto.id_empleado = pg_empleado.id_empleado)
			INNER JOIN an_uni_bas ON (sa_presupuesto.id_unidad_basica = an_uni_bas.id_uni_bas)			  
			INNER JOIN an_marca ON (an_uni_bas.mar_uni_bas = an_marca.id_marca)
			INNER JOIN an_modelo ON (an_uni_bas.mod_uni_bas = an_modelo.id_modelo)
			INNER JOIN an_transmision ON (an_uni_bas.trs_uni_bas = an_transmision.id_transmision)
		WHERE
			sa_presupuesto.id_presupuesto =  %s",
			valTpDato($idDocumento,"int"));
                
		$sqlMultiplesIvas = sprintf("SELECT 
										pg_iva.observacion,
										sa_presupuesto_iva.base_imponible, 
										sa_presupuesto_iva.subtotal_iva, 
										sa_presupuesto_iva.id_iva, 
										sa_presupuesto_iva.iva 
									FROM sa_presupuesto_iva
									INNER JOIN pg_iva ON sa_presupuesto_iva.id_iva = pg_iva.idIva 
									WHERE id_presupuesto = %s",
				valTpDato($idDocumento,"int"));
                        
		$texto_presupuesto_cotizacion = "COTIZACION";
		$descDcto = "NRO COTIZACION";
	}
	
}else{ //Vale salida y orden
	
	if($valCadBusq[2] == 3){//vale de salida
		$queryCliente = sprintf("SELECT
			CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,
			CONCAT_WS('-', cj_cc_cliente.lci, cj_cc_cliente.ci) AS cedula_cliente,
			CONCAT_WS('-', cj_cc_cliente.lci, cj_cc_cliente.ci) AS cedula_cliente,
			cj_cc_cliente.direccion AS direccion_cliente,
			cj_cc_cliente.telf AS telf_cliente,
			vw_sa_vales_recepcion.numeracion_recepcion,
			vw_sa_vales_recepcion.nom_uni_bas,
			vw_sa_vales_recepcion.des_uni_bas,
			vw_sa_vales_recepcion.nom_marca,
			vw_sa_vales_recepcion.color,
			vw_sa_vales_recepcion.placa,
			vw_sa_vales_recepcion.chasis,
			vw_sa_vales_recepcion.des_modelo,
			vw_sa_vales_recepcion.fecha_venta,
			vw_sa_vales_recepcion.kilometraje,
			vw_sa_vales_recepcion.kilometraje_recepcion,
			vw_sa_vales_recepcion.id_recepcion,
			pg_empleado.nombre_empleado,
			pg_empleado.apellido AS apellido_empleado,
			sa_orden.id_empresa,
			sa_orden.numero_orden,
			sa_orden.id_orden,
			sa_orden.id_tipo_orden,
			sa_orden.tiempo_finalizado,
			sa_orden.tiempo_entrega,
			sa_orden.subtotal,
			sa_orden.base_imponible,
			sa_orden.porcentaje_descuento,
			sa_orden.subtotal_descuento,
			sa_orden.id_empresa,
			sa_orden.iva,
			sa_orden.idIva,
			sa_orden.id_tipo_orden,
			vw_sa_vales_recepcion.ano_uni_bas,
			an_transmision.nom_transmision,
			sa_orden.tiempo_orden AS fecha_presupuesto,
			sa_vale_salida.id_vale_salida,
			sa_vale_salida.numero_vale,
			sa_vale_salida.numero_vale AS nro_documento,
			sa_vale_salida.fecha_vale,
			sa_vale_salida.estado_vale,
			sa_vale_salida.motivo_vale,
			sa_vale_salida.monto_total AS total,
			sa_orden.id_recepcion,
			vw_sa_vales_recepcion.nro_llaves
		FROM
			sa_orden
			INNER JOIN vw_sa_vales_recepcion ON (sa_orden.id_recepcion = vw_sa_vales_recepcion.id_recepcion)
			INNER JOIN pg_empleado ON (sa_orden.id_empleado = pg_empleado.id_empleado)
			INNER JOIN an_uni_bas ON (vw_sa_vales_recepcion.id_uni_bas = an_uni_bas.id_uni_bas)
			INNER JOIN an_transmision ON (an_uni_bas.trs_uni_bas = an_transmision.id_transmision)
			INNER JOIN sa_vale_salida ON (sa_orden.id_orden = sa_vale_salida.id_orden)
			INNER JOIN cj_cc_cliente ON (IFNULL(vw_sa_vales_recepcion.id_cliente_pago, vw_sa_vales_recepcion.id) = cj_cc_cliente.id)
		WHERE sa_vale_salida.id_vale_salida = %s",
		valTpDato($idDocumento,"int"));
		
		$sqlMultiplesIvas = sprintf("SELECT 
								pg_iva.observacion,
								sa_vale_salida_iva.base_imponible, 
								sa_vale_salida_iva.subtotal_iva, 
								sa_vale_salida_iva.id_iva, 
								sa_vale_salida_iva.iva 
							FROM sa_vale_salida_iva
							INNER JOIN pg_iva ON sa_vale_salida_iva.id_iva = pg_iva.idIva 
							WHERE id_vale_salida = %s",
		valTpDato($idDocumento,"int"));
		
		$texto_presupuesto_cotizacion = "VALE DE SALIDA";
		$descDcto = "NRO VALE SALIDA";
								
	}else{//orden
		$queryCliente = sprintf("SELECT
			cj_cc_cliente.nombre AS nombre_cliente,
			cj_cc_cliente.apellido AS apellido_cliente,
			CONCAT_WS('-', cj_cc_cliente.lci, cj_cc_cliente.ci) AS cedula_cliente,
			cj_cc_cliente.direccion AS direccion_cliente,
			cj_cc_cliente.telf AS telf_cliente,
			vw_sa_vales_recepcion.numeracion_recepcion,
			vw_sa_vales_recepcion.nom_uni_bas,
			vw_sa_vales_recepcion.des_uni_bas,
			vw_sa_vales_recepcion.nom_marca,
			vw_sa_vales_recepcion.color,
			vw_sa_vales_recepcion.placa,
			vw_sa_vales_recepcion.chasis,
			vw_sa_vales_recepcion.des_modelo,
			vw_sa_vales_recepcion.fecha_venta,
			vw_sa_vales_recepcion.kilometraje,
			vw_sa_vales_recepcion.kilometraje_recepcion,
			vw_sa_vales_recepcion.id_recepcion,
			vw_sa_vales_recepcion.nro_llaves,
			pg_empleado.nombre_empleado,
			pg_empleado.apellido AS apellido_empleado,
			sa_orden.id_empresa,
			sa_orden.id_orden,
			sa_orden.numero_orden,
			sa_orden.numero_orden AS nro_documento,
			sa_orden.id_tipo_orden,
			sa_orden.tiempo_finalizado,
			sa_orden.tiempo_entrega,
			sa_orden.subtotal,
			sa_orden.base_imponible,
			sa_orden.porcentaje_descuento,
			sa_orden.subtotal_descuento,
			sa_orden.id_tipo_orden,
			sa_orden.iva,
			sa_orden.idIva,
			sa_orden.monto_exento,
			sa_orden.total_orden AS total,
			vw_sa_vales_recepcion.ano_uni_bas,
			an_transmision.nom_transmision,
			sa_orden.tiempo_orden as fecha_presupuesto,
			sa_orden.id_recepcion,
			vw_sa_vales_recepcion.nro_llaves
		FROM
			sa_orden
			INNER JOIN vw_sa_vales_recepcion ON (sa_orden.id_recepcion = vw_sa_vales_recepcion.id_recepcion)
			INNER JOIN pg_empleado ON (sa_orden.id_empleado = pg_empleado.id_empleado)
			INNER JOIN an_uni_bas ON (vw_sa_vales_recepcion.id_uni_bas = an_uni_bas.id_uni_bas)
			INNER JOIN an_transmision ON (an_uni_bas.trs_uni_bas = an_transmision.id_transmision)
			INNER JOIN cj_cc_cliente ON (IFNULL(vw_sa_vales_recepcion.id_cliente_pago,vw_sa_vales_recepcion.id) = cj_cc_cliente.id)
		WHERE
			sa_orden.id_orden = %s",
			valTpDato($idDocumento,"int"));

		$sqlMultiplesIvas = sprintf("SELECT 
										pg_iva.observacion,
										sa_orden_iva.base_imponible, 
										sa_orden_iva.subtotal_iva, 
										sa_orden_iva.id_iva, 
										sa_orden_iva.iva 
									FROM sa_orden_iva
									INNER JOIN pg_iva ON sa_orden_iva.id_iva = pg_iva.idIva 
									WHERE id_orden = %s
									/*javier y alexander*/
                      ORDER BY `sa_orden_iva`.`id_orden_iva` DESC limit 1",
				valTpDato($idDocumento,"int"));
                
		$texto_presupuesto_cotizacion = "ORDEN DE SERVICIO";
		$descDcto = "NRO ORDEN";
	}

}

$rsCliente = mysql_query($queryCliente, $conex) or die(mysql_error()."<br><br>Line: ".__LINE__."<br><br>SQL: ".$queryCliente);
$rowCliente = mysql_fetch_assoc($rsCliente);

$idEmpresa = $rowCliente['id_empresa'];

$queryEmpresa = sprintf("SELECT 
							logo_familia, 
							IF (nombre_empresa_suc = '-', nombre_empresa, CONCAT_WS('-',nombre_empresa, nombre_empresa_suc)) as nombre_empresa,
							rif, 
							direccion, 
							telefono1, 
							fax 
						FROM vw_iv_empresas_sucursales 
						WHERE id_empresa_reg = %s",
						$idEmpresa);

$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error()."<br><br>Line: ".__LINE__."<br><br>SQL: ".$queryEmpresa);
$rowEmpresa = mysql_fetch_array($rsEmpresa);

$ruta_logo = $rowEmpresa['logo_familia'];

//$img = @imagecreate(816, 1092) or die("No se puede crear la imagen");
$img = @imagecreate(612, 792) or die("No se puede crear la imagen");
$img2 = @imagecreate(612, 200) or die("No Se Puede Crear la imagen");
$imgCabecera = @imagecreate(612, 50) or die("No Se Puede Crear la imagen");
//estableciendo los colores de la paleta:
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);

$backgroundColor = imagecolorallocate($img2, 255, 255, 255);
$textColor = imagecolorallocate($img2, 0, 0, 0);

$backgroundColor = imagecolorallocate($imgCabecera, 255, 255, 255);
$textColor = imagecolorallocate($imgCabecera, 0, 0, 0);

//NOMBRE EMPRESA
imagestring($imgCabecera,1,135,10,$rowEmpresa['nombre_empresa'],$textColor);

$queryConfig403 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig403 = mysql_query($queryConfig403);
if (!$rsConfig403) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$totalRowsConfig403 = mysql_num_rows($rsConfig403);
$rowConfig403 = mysql_fetch_assoc($rsConfig403);

if($rowConfig403['valor'] == NULL){ die("No se ha configurado formato de cheque."); }

if($rowConfig403['valor'] == "3"){//puerto rico
   
}else{//sino es puerto rico
    //RIF EMPRESA
    imagestring($imgCabecera,1,135,20,$spanRIF.": ".$rowEmpresa['rif'],$textColor);
}

$queryTipoOrden = sprintf("SELECT nombre_tipo_orden, sa_filtro_orden.tot_accesorio
                           FROM sa_tipo_orden 
                           INNER JOIN sa_filtro_orden ON sa_tipo_orden.id_filtro_orden = sa_filtro_orden.id_filtro_orden
                           WHERE id_tipo_orden = %s",valTpDato($rowCliente['id_tipo_orden'],"int"));
$rsTipoOrden = mysql_query($queryTipoOrden) or die(mysql_error()."<br><br>Line: ".__LINE__."<br><br>SQL: ".$queryTipoOrden);
$rowTipoOrden = mysql_fetch_array($rsTipoOrden);

$totAccesorio = $rowTipoOrden['tot_accesorio'];

//TEXTO "PRESUPUESTOS o COTIZACION o ORDEN DE SERVICIO"
imagestring($imgCabecera,2,250,15,$texto_presupuesto_cotizacion." (".$rowTipoOrden['nombre_tipo_orden'].")",$textColor);

//TEXTO "DATOS DEL CLIENTE"
imageline($img,10,50,602,50,$textColor);
imagestring($img,1,250,52,"DATOS DEL CLIENTE",$textColor);
imageline($img,10,60,602,60,$textColor);
imageline($img,10,50,10,150,$textColor);
imageline($img,602,50,602,150,$textColor);

//TEXTO "NOMBRE"
imageline($img,10,60,10,70,$textColor);
imageline($img,70,60,70,140,$textColor);
imagestring($img,1,10,62,str_pad("NOMBRE", 12, " ", STR_PAD_BOTH),$textColor);
//NOMBRE
imagestring($img,1,80,62,$rowCliente['nombre_cliente']." ".$rowCliente['apellido_cliente'],$textColor);
imageline($img,10,70,602,70,$textColor);

//NRO DE DOCUMENTO
imageline($img,360,50,360,60,$textColor);
imagestring($img,1,362,52,$descDcto.": ".$rowCliente["nro_documento"],$textColor);
//imagestring($img,1,445,52,1081567,$textColor);

//NRO DE LLAVES
imageline($img,482,50,482,60,$textColor);
imagestring($img,1,485,52,"NRO LLAVES:",$textColor);
imagestring($img,1,545,52,$rowCliente["nro_llaves"],$textColor);

//TEXTO "DIRECCION"
imagestring($img,1,10,72,str_pad("DIRECCION", 12, " ", STR_PAD_BOTH),$textColor);
//DIRECCION
imagestring($img,1,80,72,$rowCliente['direccion_cliente'],$textColor);
imageline($img,10,80,602,80,$textColor);

//TEXTO "TLFS"
imagestring($img,1,10,82,str_pad("TLFS", 12, " ", STR_PAD_BOTH),$textColor);
//TLFS
imagestring($img,1,80,82,$rowCliente['telf_cliente'],$textColor);
//TEXTO "RIF/CIV"
imageline($img,167,80,167,90,$textColor);
imagestring($img,1,170,82,str_pad($spanCI."/".$spanRIF, 12, " ", STR_PAD_BOTH),$textColor);
//RIF/CEDULA
imageline($img,236,80,236,90,$textColor);
imagestring($img,1,240,82,$rowCliente['cedula_cliente'],$textColor);
//TEXTO "O.R."
imageline($img,306,80,306,90,$textColor);
//if($valCadBusq[2] == 3)
	//imagestring($img,1,310,82,str_pad("NRO.VALE", 12, " ", STR_PAD_BOTH),$textColor);
//else
	imagestring($img,1,310,82,str_pad("O.S.", 12, " ", STR_PAD_BOTH),$textColor);
//ORDEN
imageline($img,371,80,371,90,$textColor);
//if($valCadBusq[2] == 3)
//	imagestring($img,1,375,82,$rowCliente['numero_vale'],$textColor);  // <----- Numero de Orden. -aparte: creo que es vale de salida
//else
	imagestring($img,1,375,82,$rowCliente['numero_orden'],$textColor);
//TEXTO "FECHA"
imageline($img,416,80,416,90,$textColor);
imagestring($img,1,420,82,"FECHA APERTURA",$textColor);
//FECHA
imageline($img,502,80,502,90,$textColor);
if($valCadBusq[2] == 3)
	imagestring($img,1,505,82,fechaTiempo($rowCliente['fecha_vale']),$textColor);
else
	imagestring($img,1,505,82,fechaTiempo($rowCliente['fecha_presupuesto']),$textColor);
imageline($img,10,90,602,90,$textColor);

//TEXTO "ASESOR"
imageline($img,306,80,306,100,$textColor);
imagestring($img,1,10,92,str_pad("ASESOR", 12, " ", STR_PAD_BOTH),$textColor);
//ASESOR
imagestring($img,1,80,92,$rowCliente['nombre_empleado']." ".$rowCliente['apellido_empleado'],$textColor);
imageline($img,10,100,602,100,$textColor);



//TEXTO "VALE"
imageline($img,371,80,371,100,$textColor);
imagestring($img,1,310,92,str_pad("VALE", 12, " ", STR_PAD_BOTH),$textColor);
//VALE
imagestring($img,1,376,92,$rowCliente['numeracion_recepcion'],$textColor); // de id_recepcion se cambio a numeracion_recepcion
imageline($img,10,100,602,100,$textColor);

//FECHA FINALIZADA
imageline($img,416,91,416,100,$textColor);
imagestring($img,1,420,92,"FECHA FINALIZADO",$textColor);
imageline($img,502,91,502,100,$textColor);
imagestring($img,1,505,92,fechaTiempo($rowCliente['tiempo_finalizado']),$textColor);

//FECHA ENTREGA
imageline($img,416,101,416,110,$textColor);
imagestring($img,1,420,102,"FECHA ENTREGADO",$textColor);
imageline($img,502,101,502,110,$textColor);
imagestring($img,1,505,102,fechaTiempo($rowCliente['tiempo_entrega']),$textColor);


//TEXTO "DATOS DEL VEHICULO"
imagestring($img,1,250,102,"DATOS DEL VEHICULO",$textColor);
imageline($img,10,110,602,110,$textColor);

//TEXTO "MARCA"
imagestring($img,1,10,112,str_pad("MARCA", 12, " ", STR_PAD_BOTH),$textColor);
//MARCA
imagestring($img,1,80,112,$rowCliente['nom_marca'],$textColor);
//TEXTO "AÑO"
imageline($img,249,110,249,140,$textColor);
imagestring($img,1,250,112,str_pad("A".utf8_decode("Ñ")."O", 12, " ", STR_PAD_BOTH),$textColor);
//AÑO
imageline($img,307,110,307,140,$textColor);
imagestring($img,1,310,112,$rowCliente['ano_uni_bas'],$textColor);
//TEXTO "MOTOR"
imageline($img,416,110,416,140,$textColor);
imagestring($img,1,420,112,str_pad("UNIDAD", 16, " ", STR_PAD_BOTH),$textColor);
//MOTOR 
imageline($img,506,110,506,140,$textColor);
imagestring($img,1,510,112,substr($rowCliente['nom_uni_bas'], 0, 18),$textColor);
imageline($img,10,120,602,120,$textColor);

//TEXTO "MODELO"
imagestring($img,1,10,122,str_pad("MODELO", 12, " ", STR_PAD_BOTH),$textColor);
//MODELO
imagestring($img,1,80,122,$rowCliente['des_modelo'],$textColor);
//TEXTO "PLACAS"
imagestring($img,1,250,122,str_pad("PLACAS", 12, " ", STR_PAD_BOTH),$textColor);
//PLACA
imagestring($img,1,310,122,$rowCliente['placa'],$textColor);
//TEXTO "FECHA DE VENTA"
imagestring($img,1,420,122,str_pad("FECHA DE VENTA", 12, " ", STR_PAD_BOTH),$textColor);
//FECHA DE VENTA
if($rowCliente['fecha_venta'] != NULL && $rowCliente['fecha_venta'] !=" " && $rowCliente['fecha_venta'] != "0000-00-00") {
   $fechaVenta = date("d-m-Y",strtotime($rowCliente['fecha_venta']));        
}
imagestring($img,1,510,122,$fechaVenta,$textColor);
imageline($img,10,130,602,130,$textColor);

//TEXTO "COLOR"
imagestring($img,1,10,132,str_pad("COLOR", 12, " ", STR_PAD_BOTH),$textColor);
//COLOR
imagestring($img,1,80,132,substr($rowCliente['color'],0,14),$textColor);
imageline($img,10,150,602,150,$textColor);

//TEXTO "KM"
imagestring($img,1,150,132,strtoupper($spanKilometraje),$textColor);
//KILOMETRAJE
imagestring($img,1,208,132,$rowCliente['kilometraje_recepcion'],$textColor);

//TEXTO "CHASIS"
imagestring($img,1,250,132,str_pad("CHASIS", 12, " ", STR_PAD_BOTH),$textColor);
//CHASIS
imagestring($img,1,310,132,$rowCliente['chasis'],$textColor);
//TEXTO "TRANSMISION"
imagestring($img,1,420,132,str_pad("TRANSMISION", 12, " ", STR_PAD_BOTH),$textColor);
//TRANSMISION
imagestring($img,1,510,132,$rowCliente['nom_transmision'],$textColor);
imageline($img,10,140,602,140,$textColor);



//ENCABEZADO LISTA
imagestring($img,1,12,142,str_pad("CODIGO", 18, " ", STR_PAD_RIGHT),$textColor);
imageline($img,98,140,98,150,$textColor);
imagestring($img,1,100,142,str_pad("DESCRIPCI".utf8_decode("Ó")."N", 32, " ", STR_PAD_RIGHT),$textColor);
imageline($img,368,140,368,150,$textColor);
imagestring($img,1,370,142,str_pad("CANTIDAD/MECANICO", 17, "-", STR_PAD_LEFT),$textColor);
imageline($img,458,140,458,150,$textColor);
imagestring($img,1,460,142,str_pad("PRECIO UNITARIO", 12, " ", STR_PAD_LEFT),$textColor);
imageline($img,541,140,541,150,$textColor);
imagestring($img,1,543,142,str_pad("PRECIO TOTAL", 12, " ", STR_PAD_LEFT),$textColor);
imageline($img,10,150,602,150,$textColor);

$i = 1;
$posY = 152;
	

	
$queryFalla = sprintf("SELECT descripcion_falla FROM sa_recepcion_falla WHERE id_recepcion = %s",
				valTpDato($rowCliente['id_recepcion'],"int"));	
$rsFalla = mysql_query($queryFalla, $conex) or die(mysql_error()."<br><br>Line: ".__LINE__);

if (mysql_num_rows($rsFalla) > 0){
	imagestring($img,1,300,$posY,"FALLA",$textColor);
	$posY += 10;
	while ($rowFalla = mysql_fetch_assoc($rsFalla)) {
		
		$anex = (strlen($rowFalla['descripcion_falla']) > 110) ? "..." : "";		

		imagestring($img,1,10,$posY,str_pad(strtoupper(substr($rowFalla['descripcion_falla'],0,107).$anex), 110, " ", STR_PAD_RIGHT),$textColor);
		$posY += 10;
		$i++;
	}	
}

$i = 1;
// DETALLES DE LOS REPUESTOS
if ($valCadBusq[1] == 1){//presupuesto y cotizacion
	$queryRepuestosGenerales = sprintf("SELECT
		sa_det_presup_articulo.cantidad,
		sa_det_presup_articulo.precio_unitario,
		sa_det_presup_articulo.id_paquete,
		sa_det_presup_articulo.id_det_orden_articulo,
		sa_det_presup_articulo.id_articulo,
		sa_det_presup_articulo.id_articulo_costo,
		vw_iv_articulos.codigo_articulo,
		vw_iv_articulos.descripcion,
		sa_det_presup_articulo.iva
	FROM
		sa_presupuesto
		INNER JOIN sa_det_presup_articulo ON (sa_presupuesto.id_presupuesto = sa_det_presup_articulo.id_presupuesto)
		INNER JOIN vw_iv_articulos ON (sa_det_presup_articulo.id_articulo = vw_iv_articulos.id_articulo)
	WHERE
		sa_presupuesto.id_presupuesto = %s 
		AND sa_det_presup_articulo.estado_articulo <> 'DEVUELTO'",
		valTpDato($idDocumento,"int"));

}else{
	if($valCadBusq[2] == 3){ //vale de salida
		
		$queryRepuestosGenerales = sprintf("SELECT
			sa_det_orden_articulo.cantidad,
			sa_det_orden_articulo.precio_unitario,
			sa_det_orden_articulo.id_paquete,
			vw_iv_articulos.codigo_articulo,
			vw_iv_articulos.descripcion,
			sa_det_orden_articulo.iva,
			sa_det_orden_articulo.id_det_orden_articulo,
			sa_det_orden_articulo.id_articulo,
			sa_det_orden_articulo.id_articulo_costo
		FROM
			vw_iv_articulos
			INNER JOIN sa_det_orden_articulo ON (vw_iv_articulos.id_articulo = sa_det_orden_articulo.id_articulo)
			INNER JOIN sa_orden ON (sa_det_orden_articulo.id_orden = sa_orden.id_orden)
			INNER JOIN sa_vale_salida ON (sa_orden.id_orden = sa_vale_salida.id_orden)
		WHERE
			sa_vale_salida.id_vale_salida = %s 
			AND sa_det_orden_articulo.estado_articulo <> 'DEVUELTO'",
			valTpDato($idDocumento,"int"));
	
	}else{ //orden
	
		$queryRepuestosGenerales = sprintf("SELECT
			sa_det_orden_articulo.cantidad,
			sa_det_orden_articulo.precio_unitario,
			sa_det_orden_articulo.id_paquete,
			vw_iv_articulos.codigo_articulo,
			vw_iv_articulos.descripcion,
			sa_det_orden_articulo.iva,
			sa_det_orden_articulo.id_det_orden_articulo,
			sa_det_orden_articulo.id_articulo,
			sa_det_orden_articulo.id_articulo_costo
		FROM
			vw_iv_articulos
			INNER JOIN sa_det_orden_articulo ON (vw_iv_articulos.id_articulo = sa_det_orden_articulo.id_articulo)
		WHERE
			sa_det_orden_articulo.id_orden = %s 
			AND sa_det_orden_articulo.estado_articulo <> 'DEVUELTO' 
			AND sa_det_orden_articulo.aprobado = 1",
		valTpDato($idDocumento,"int"));
	}
}
$rsOrdenDetRep = mysql_query($queryRepuestosGenerales, $conex) or die(mysql_error()."<br><br>Line: ".__LINE__);


if (mysql_num_rows($rsOrdenDetRep) > 0){
	imagestring($img,1,300,$posY,"REPUESTOS",$textColor);
	
	imagestring($img,1,368,$posY,"LOTE/DIS.",$textColor);
	if ($valCadBusq[1] != 1){//presupuesto / cotizacion
		//imagestring($img,1,400,$posY,"CANT.",$textColor);            
		imagestring($img,1,420,$posY,"CANT.",$textColor);
		imagestring($img,1,450,$posY,"SO.",$textColor);
		imagestring($img,1,471,$posY,"RE.",$textColor);
	}
        
	$posY += 10;
	while ($rowOrdenDetRep = mysql_fetch_assoc($rsOrdenDetRep)) {

		if($valCadBusq[1] != 1){
			$solicitudRep= "";
			$sqlSolicitudRep= "SELECT * FROM sa_det_solicitud_repuestos
								WHERE
									id_det_orden_articulo= ".$rowOrdenDetRep['id_det_orden_articulo']."
									AND id_estado_solicitud IN (1,2)";
			$rsSolicitudRep= mysql_query($sqlSolicitudRep, $conex) or die(mysql_error()."<br><br>Line: ".__LINE__.$sqlSolicitudRep);
			$rowSolicitudRep= mysql_fetch_assoc($rsSolicitudRep);

			if($rowSolicitudRep){
				$solicitudRep= "(S)";
			}
		}

		if ($rowOrdenDetRep['iva'] == NULL || $rowOrdenDetRep['iva'] == ''){
			$montoExento += $rowOrdenDetRep['cantidad']*$rowOrdenDetRep['precio_unitario'];
		}
		$anex = (strlen($rowOrdenDetRep['descripcion']) > 53) ? "..." : "";

		//$pdf->Cell($arrayTamCol[0],16,$i,'1',0,'L',false);
		//SI ES UN PRESUPUESTO OCULTA EL CODIGO DE LOS ARTICULOS
		if ($valCadBusq[1] == 1){
			$codigoRepuesto = "";
		}else{
			$codigoRepuesto = elimCaracter($rowOrdenDetRep['codigo_articulo'],";");
		}
			
		 if($rowOrdenDetRep['id_paquete']){
			 $provienePaquete = "(P)";
		 }else{
			 $provienePaquete = "";
		 }

		imagestring($img,1,10,$posY,str_pad($codigoRepuesto, 18, " ", STR_PAD_RIGHT),$textColor);
		imagestring($img,1,100,$posY,str_pad(strtoupper(substr($solicitudRep.$provienePaquete." ".$rowOrdenDetRep['descripcion'],0,50).$anex), 53, " ", STR_PAD_RIGHT),$textColor);
		//imagestring($img,1,298,$posY,str_pad(cantidadDisponibleActual($rowOrdenDetRep['id_articulo'],$idEmpresa), 17, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,325,$posY,str_pad($rowOrdenDetRep['id_articulo_costo']." ".cantidadDisponibleActual($rowOrdenDetRep['id_articulo_costo']), 17, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,350,$posY,str_pad($rowOrdenDetRep['cantidad'], 17, " ", STR_PAD_LEFT),$textColor);                
		
		if ($valCadBusq[1] != 1){//presupuesto
			imagestring($img,1,372,$posY,str_pad(cantidadSolicitada($rowOrdenDetRep['id_det_orden_articulo']), 17, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,392,$posY,str_pad(cantidadDespachada($rowOrdenDetRep['id_det_orden_articulo']), 17, " ", STR_PAD_LEFT),$textColor);
		}
                
		imagestring($img,1,460,$posY,str_pad(number_format($rowOrdenDetRep['precio_unitario'],2,".",","), 15, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,543,$posY,str_pad(number_format(($rowOrdenDetRep['cantidad']*$rowOrdenDetRep['precio_unitario']),2,".",","), 12, " ", STR_PAD_LEFT),$textColor);
		$totalRepuestos += $rowOrdenDetRep['cantidad']*$rowOrdenDetRep['precio_unitario'];
		$posY += 10;
		$i++;
	}
	
	imagestring($img,1,445,$posY,str_pad("SUB-TOTAL REPUESTOS", 12, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,543,$posY,str_pad(number_format($totalRepuestos,2,".",","), 12, " ", STR_PAD_LEFT),$textColor);
	$posY += 10;
}

// DETALLES DE LOS TEMPARIOS
if ($valCadBusq[1] == 1){
	$queryFactDetTemp = sprintf("SELECT
		sa_modo.descripcion_modo,
		sa_tempario.codigo_tempario,
		sa_tempario.descripcion_tempario,
		sa_det_presup_tempario.operador,
		sa_det_presup_tempario.id_tempario,
		sa_det_presup_tempario.id_paquete,
		sa_det_presup_tempario.precio,
		sa_det_presup_tempario.base_ut_precio,
		sa_det_presup_tempario.id_modo,
		(case sa_det_presup_tempario.id_modo when '1' then sa_det_presup_tempario.ut * sa_det_presup_tempario.precio_tempario_tipo_orden / sa_det_presup_tempario.base_ut_precio when '2' then sa_det_presup_tempario.precio when '3' then sa_det_presup_tempario.costo when '4' then '4' end) AS total_por_tipo_orden,
		(case sa_det_presup_tempario.id_modo when '1' then sa_det_presup_tempario.ut when '2' then sa_det_presup_tempario.precio when '3' then sa_det_presup_tempario.costo when '4' then '4' end) AS precio_por_tipo_orden,
		sa_det_presup_tempario.id_det_presup_tempario,
		sa_det_presup_tempario.aprobado,
		sa_det_presup_tempario.origen_tempario,
		sa_det_presup_tempario.origen_tempario + 0 AS idOrigen,
		sa_paquetes.codigo_paquete,
		sa_paquetes.id_paquete,
		sa_det_presup_tempario.precio_tempario_tipo_orden,
		IFNULL(sa_det_presup_tempario.id_mecanico, 0) AS id_mecanico
	FROM
		sa_det_presup_tempario
		INNER JOIN sa_tempario ON (sa_det_presup_tempario.id_tempario = sa_tempario.id_tempario)
		INNER JOIN sa_modo ON (sa_det_presup_tempario.id_modo = sa_modo.id_modo)
		LEFT OUTER JOIN sa_paquetes ON (sa_det_presup_tempario.id_paquete = sa_paquetes.id_paquete)
	WHERE
		sa_det_presup_tempario.id_presupuesto = %s 
		AND sa_det_presup_tempario.estado_tempario <> 'DEVUELTO'
	ORDER BY
		sa_det_presup_tempario.id_paquete",
		valTpDato($idDocumento,"int"));

}else{
	
	if($valCadBusq[2] == 3){
		$queryFactDetTemp = sprintf("SELECT
			sa_modo.descripcion_modo,
			sa_tempario.codigo_tempario,
			sa_tempario.descripcion_tempario,
			sa_det_orden_tempario.operador,
			sa_det_orden_tempario.id_tempario,
			sa_det_orden_tempario.id_paquete,
			sa_det_orden_tempario.precio,
			sa_det_orden_tempario.base_ut_precio,
			sa_det_orden_tempario.id_modo,
			(case sa_det_orden_tempario.id_modo when '1' then sa_det_orden_tempario.ut * sa_det_orden_tempario.precio_tempario_tipo_orden / sa_det_orden_tempario.base_ut_precio when '2' then sa_det_orden_tempario.precio when '3' then sa_det_orden_tempario.costo when '4' then '4' end) AS total_por_tipo_orden,
			(case sa_det_orden_tempario.id_modo when '1' then sa_det_orden_tempario.ut when '2' then sa_det_orden_tempario.precio when '3' then sa_det_orden_tempario.costo when '4' then '4' end) AS precio_por_tipo_orden,
			sa_det_orden_tempario.id_det_orden_tempario,
			sa_det_orden_tempario.aprobado,
			sa_det_orden_tempario.origen_tempario,
			sa_det_orden_tempario.origen_tempario + 0 AS idOrigen,
			sa_paquetes.codigo_paquete,
			sa_paquetes.id_paquete,
			sa_det_orden_tempario.precio_tempario_tipo_orden,
			IFNULL(sa_det_orden_tempario.id_mecanico, 0) AS id_mecanico
		FROM
			sa_det_orden_tempario
			INNER JOIN sa_tempario ON (sa_det_orden_tempario.id_tempario = sa_tempario.id_tempario)
			INNER JOIN sa_modo ON (sa_det_orden_tempario.id_modo = sa_modo.id_modo)
			LEFT OUTER JOIN sa_paquetes ON (sa_det_orden_tempario.id_paquete = sa_paquetes.id_paquete)
			INNER JOIN sa_orden ON (sa_orden.id_orden = sa_det_orden_tempario.id_orden)
			INNER JOIN sa_vale_salida ON (sa_orden.id_orden = sa_vale_salida.id_orden)
		WHERE
			sa_vale_salida.id_vale_salida = %s 
			AND sa_det_orden_tempario.estado_tempario <> 'DEVUELTO'
		ORDER BY
			sa_det_orden_tempario.id_paquete",
			valTpDato($idDocumento,"int"));
	
	}else{
		$queryFactDetTemp = sprintf("SELECT
			sa_modo.descripcion_modo,
			sa_tempario.codigo_tempario,
			sa_tempario.descripcion_tempario,
			sa_det_orden_tempario.operador,
			sa_det_orden_tempario.id_tempario,
			sa_det_orden_tempario.id_paquete,
			sa_det_orden_tempario.precio,
			sa_det_orden_tempario.base_ut_precio,
			sa_det_orden_tempario.id_modo,
			(case sa_det_orden_tempario.id_modo when '1' then sa_det_orden_tempario.ut * sa_det_orden_tempario.precio_tempario_tipo_orden / sa_det_orden_tempario.base_ut_precio when '2' then sa_det_orden_tempario.precio when '3' then sa_det_orden_tempario.costo when '4' then '4' end) AS total_por_tipo_orden,
			(case sa_det_orden_tempario.id_modo when '1' then sa_det_orden_tempario.ut when '2' then sa_det_orden_tempario.precio when '3' then sa_det_orden_tempario.costo when '4' then '4' end) AS precio_por_tipo_orden,
			sa_det_orden_tempario.id_det_orden_tempario,
			sa_det_orden_tempario.aprobado,
			sa_det_orden_tempario.origen_tempario,
			sa_det_orden_tempario.origen_tempario + 0 AS idOrigen,
			sa_paquetes.codigo_paquete,
			sa_paquetes.id_paquete,
			sa_det_orden_tempario.precio_tempario_tipo_orden,
			IFNULL(sa_det_orden_tempario.id_mecanico, 0) AS id_mecanico
		FROM
			sa_det_orden_tempario
			INNER JOIN sa_tempario ON (sa_det_orden_tempario.id_tempario = sa_tempario.id_tempario)
			INNER JOIN sa_modo ON (sa_det_orden_tempario.id_modo = sa_modo.id_modo)
			LEFT OUTER JOIN sa_paquetes ON (sa_det_orden_tempario.id_paquete = sa_paquetes.id_paquete)
		WHERE
			sa_det_orden_tempario.id_orden = %s  
			AND sa_det_orden_tempario.estado_tempario <> 'DEVUELTO'
		ORDER BY
			sa_det_orden_tempario.id_paquete",
			valTpDato($idDocumento,"int"));
	}
}

$rsFactDetTemp = mysql_query($queryFactDetTemp, $conex) or die(mysql_error()."<br><br>Line: ".__LINE__);

if (mysql_num_rows($rsFactDetTemp) > 0){
	imagestring($img,1,300,$posY,"MANOS DE OBRA",$textColor);
	$posY += 10;

	while ($rowFactDetTemp = mysql_fetch_assoc($rsFactDetTemp)) {
		$anex = (strlen($rowFactDetTemp['descripcion_tempario']) > 53) ? "..." : "";

		$caractCantTempario = ($rowFactDetTemp['id_modo'] == 1) ? number_format($rowFactDetTemp['precio_por_tipo_orden']/100,2,".",",") : number_format(1,2,".",",");//Es entre 100 o la base ut? : $rowFactDetTemp['precio_por_tipo_orden']/100

		$caracterPrecioTempario = ($rowFactDetTemp['id_modo'] == 1) ? $rowFactDetTemp['precio_tempario_tipo_orden'] : $rowFactDetTemp['precio_por_tipo_orden'];

		$cantidad = $caractCantTempario."/MEC:".sprintf("%04s",$rowFactDetTemp['id_mecanico']);
		$precioUnit = number_format($caracterPrecioTempario,2,".",",");
		$total = number_format($rowFactDetTemp['total_por_tipo_orden'],2,".",",");

		 if($rowFactDetTemp['id_paquete']){
			 $provienePaqueteTempario = "(P) ";
		 }else{
			 $provienePaqueteTempario = "";
		 }

		imagestring($img,1,10,$posY,str_pad($rowFactDetTemp['codigo_tempario'], 18, " ", STR_PAD_RIGHT),$textColor);
		imagestring($img,1,100,$posY,str_pad(strtoupper(substr($provienePaqueteTempario.$rowFactDetTemp['descripcion_tempario'],0,50).$anex), 53, " ", STR_PAD_RIGHT),$textColor);
		imagestring($img,1,370,$posY,str_pad($cantidad, 17, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,460,$posY,str_pad($precioUnit, 15, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,543,$posY,str_pad($total, 12, " ", STR_PAD_LEFT),$textColor);
		$totalManoDeObra += $rowFactDetTemp['total_por_tipo_orden'];
		$posY += 10;
		$i++;
	}
	imagestring($img,1,445,$posY,str_pad("SUB-TOTAL TEMPARIOS", 12, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,543,$posY,str_pad(number_format($totalManoDeObra,2,".",","), 12, " ", STR_PAD_LEFT),$textColor);
	$posY += 10;
}

// DETALLE DE LOS TOT
if ($valCadBusq[1] == 1){
	$queryDetalleTot = sprintf("SELECT
		sa_orden_tot.monto_subtotal,
		sa_det_presup_tot.id_orden_tot,
		sa_orden_tot.numero_tot,
		sa_orden_tot.observacion_factura,
		sa_det_presup_tot.porcentaje_tot
	FROM
		sa_det_presup_tot
		INNER JOIN sa_orden_tot ON (sa_det_presup_tot.id_orden_tot = sa_orden_tot.id_orden_tot)
	WHERE
		sa_det_presup_tot.id_presupuesto = %s 
		AND sa_orden_tot.monto_subtotal > 0",
		valTpDato($idDocumento,"int"));
		
}else{
	
	if($valCadBusq[2] == 3){
		$queryDetalleTot = sprintf("SELECT
			sa_orden_tot.monto_subtotal,
			sa_det_orden_tot.id_orden_tot,
			sa_orden_tot.numero_tot,
			sa_orden_tot.observacion_factura,
			sa_det_orden_tot.porcentaje_tot		
		FROM
			sa_det_orden_tot
			INNER JOIN sa_orden_tot ON (sa_det_orden_tot.id_orden_tot = sa_orden_tot.id_orden_tot)
			INNER JOIN sa_orden ON (sa_det_orden_tot.id_orden = sa_orden.id_orden)
			INNER JOIN sa_vale_salida ON (sa_orden.id_orden = sa_vale_salida.id_orden)
		WHERE
			sa_vale_salida.id_vale_salida = %s 
			AND sa_orden_tot.monto_subtotal > 0",
		valTpDato($idDocumento,"int"));
	
	}else{
		$queryDetalleTot = sprintf("SELECT
			sa_orden_tot.monto_subtotal,
			sa_det_orden_tot.id_orden_tot,
			sa_orden_tot.numero_tot,
			sa_orden_tot.observacion_factura,
			sa_det_orden_tot.porcentaje_tot
		FROM
			sa_det_orden_tot
			INNER JOIN sa_orden_tot ON (sa_det_orden_tot.id_orden_tot = sa_orden_tot.id_orden_tot)
		WHERE
			sa_det_orden_tot.id_orden = %s 
			AND sa_orden_tot.monto_subtotal > 0",
			valTpDato($idDocumento,"int"));
	}
}

$rsDetalleTot = mysql_query($queryDetalleTot) or die(mysql_error()."<br><br>Line: ".__LINE__);

if (mysql_num_rows($rsDetalleTot) > 0){
	imagestring($img,1,250,$posY,"TRABAJOS OTROS TALLERES (T.O.T)",$textColor);
	$posY += 10;
           
	while ($rowDetalleTot = mysql_fetch_assoc($rsDetalleTot)) {
		$cantidad = "1";
		$precioUnit = number_format($rowDetalleTot['monto_subtotal'] + ($rowDetalleTot['monto_subtotal'] * $rowDetalleTot['porcentaje_tot'] / 100),2,".",",");
		$total = number_format($rowDetalleTot['monto_subtotal'] + ($rowDetalleTot['monto_subtotal'] * $rowDetalleTot['porcentaje_tot'] / 100),2,".",",");
		
		imagestring($img,1,10,$posY,str_pad($rowDetalleTot['numero_tot'], 18, " ", STR_PAD_RIGHT),$textColor);
		imagestring($img,1,100,$posY,str_pad("T.O.T. ".str_replace("\\n"," ",str_replace("\t","",substr($rowDetalleTot['observacion_factura'],0,70))), 53, " ", STR_PAD_RIGHT),$textColor);
		
		if($totAccesorio == 1){
			
		}else{
			imagestring($img,1,370,$posY,str_pad($cantidad, 17, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,460,$posY,str_pad($precioUnit, 15, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,543,$posY,str_pad($total, 12, " ", STR_PAD_LEFT),$textColor);
		}
                    
		if($totAccesorio == 1){
			$queryItemsTot = sprintf("SELECT descripcion_trabajo, monto, cantidad, id_precio_tot FROM sa_orden_tot_detalle WHERE id_orden_tot = %s",
			$rowDetalleTot['id_orden_tot']);
			$rsItemsTot = mysql_query($queryItemsTot) or die(mysql_error()."<br><br>Line: ".__LINE__);
			
			while($rowItemsTot = mysql_fetch_assoc($rsItemsTot)){
				$posY += 10;
			  
				$cantidadDetalle = $rowItemsTot['cantidad'];
				$precioUnitDetalle = $rowItemsTot['monto']+(($rowItemsTot['monto']*$rowDetalleTot['porcentaje_tot']/100));
				
				if($cantidadDetalle == NULL || $cantidadDetalle == "" || $cantidadDetalle == 0 ){
					$cantidadDetalle = 1;
				}
				
				$totalDetalle = number_format($cantidadDetalle*$precioUnitDetalle,2,".",",");
				$precioUnitDetalle = number_format($precioUnitDetalle,2,".",",");
				
				imagestring($img,1,10,$posY,str_pad(" ACC".$rowItemsTot['id_precio_tot'], 18, " ", STR_PAD_RIGHT),$textColor);
				imagestring($img,1,100,$posY,str_pad(substr(" - ".$rowItemsTot['descripcion_trabajo'],0,33), 53, " ", STR_PAD_RIGHT),$textColor);                            
						
				imagestring($img,1,370,$posY,str_pad($cantidadDetalle, 17, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,460,$posY,str_pad($precioUnitDetalle, 15, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,543,$posY,str_pad($totalDetalle, 12, " ", STR_PAD_LEFT),$textColor);
			}			
		}
		
		
		$totalTOT += $rowDetalleTot['monto_subtotal'] + ($rowDetalleTot['monto_subtotal'] * $rowDetalleTot['porcentaje_tot'] / 100);
		$posY += 10;
		$i++;
	}
        
	imagestring($img,1,335,$posY,str_pad("SUB-TOTAL TRABAJOS OTROS TALLERES (T.O.T)", 12, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,543,$posY,str_pad(number_format($totalTOT,2,".",","), 12, " ", STR_PAD_LEFT),$textColor);
	$posY += 10;
}

// DETALLES DE LAS NOTAS
if ($valCadBusq[1] == 1){
	
	$queryDetTipoDocNotas = sprintf("SELECT
		sa_det_presup_notas.descripcion_nota,
		sa_det_presup_notas.precio,
		sa_det_presup_notas.id_det_presup_nota
	FROM
		sa_presupuesto
		INNER JOIN sa_det_presup_notas ON (sa_presupuesto.id_presupuesto = sa_det_presup_notas.id_presupuesto)
	WHERE
		sa_presupuesto.id_presupuesto = %s",
		valTpDato($idDocumento,"int"));

}else{
	
	if($valCadBusq[2] == 3){
		$queryDetTipoDocNotas = sprintf("SELECT
			sa_det_orden_notas.descripcion_nota,
			sa_det_orden_notas.precio,
			sa_det_orden_notas.id_det_orden_nota
		FROM
			sa_orden
			INNER JOIN sa_det_orden_notas ON (sa_orden.id_orden = sa_det_orden_notas.id_orden)
			INNER JOIN sa_vale_salida ON (sa_orden.id_orden = sa_vale_salida.id_orden)
		WHERE
			sa_vale_salida.id_vale_salida = %s",
			valTpDato($idDocumento,"int"));
			
	}else{
		$queryDetTipoDocNotas = sprintf("SELECT
			sa_det_orden_notas.descripcion_nota,
			sa_det_orden_notas.precio,
			sa_det_orden_notas.id_det_orden_nota
		FROM
			sa_det_orden_notas
		WHERE
			sa_det_orden_notas.id_orden = %s",
			valTpDato($idDocumento,"int"));
	}
}

$rsDetTipoDocNotas = mysql_query($queryDetTipoDocNotas) or die(mysql_error()."<br><br>Line: ".__LINE__);
if (mysql_num_rows($rsDetTipoDocNotas) > 0){
	imagestring($img,1,300,$posY,"NOTAS",$textColor);
	$posY += 10;
	while ($rowDetTipoDocNotas = mysql_fetch_assoc($rsDetTipoDocNotas)) {
		$anex = (strlen($rowDetTipoDocNotas['descripcion_nota']) > 53) ? "..." : "";

		$cantidad = "1";
		$precioUnit = number_format($rowDetTipoDocNotas['precio'],2,".",",");

		$total = number_format($rowDetTipoDocNotas['precio'],2,".",",");

		//imagestring($img,1,10,$posY,str_pad("N".$rowDetTipoDocNotas['id_det_presup_nota'], 18, " ", STR_PAD_RIGHT),$textColor);
		imagestring($img,1,10,$posY,$posY,$textColor);
		imagestring($img,1,100,$posY,str_pad(strtoupper(substr($rowDetTipoDocNotas['descripcion_nota'],0,50).$anex), 53, " ", STR_PAD_RIGHT),$textColor);
		imagestring($img,1,370,$posY,str_pad($cantidad, 17, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,460,$posY,str_pad($precioUnit, 15, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,543,$posY,str_pad($total, 12, " ", STR_PAD_LEFT),$textColor);
		$totalNotas += $rowDetTipoDocNotas['precio'];
		$posY += 10;
		$i++;
	}
	imagestring($img,1,450,$posY,str_pad("SUB-TOTAL NOTAS", 12, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,543,$posY,str_pad(number_format($totalNotas,2,".",","), 12, " ", STR_PAD_LEFT),$textColor);
	$posY += 10;
}

// DETALLES DE LOS DESCUENTOS
if ($valCadBusq[1] == 1){
	$queryDetDescuentos = sprintf("SELECT
		sa_det_presup_descuento.porcentaje,
		sa_det_presup_descuento.id_porcentaje_descuento
	FROM
		sa_det_presup_descuento
	WHERE
		sa_det_presup_descuento.id_presupuesto = %s",
		valTpDato($idDocumento,"int"));
		
}else{
	$queryDetDescuentos = sprintf("SELECT
		sa_det_orden_descuento.porcentaje,
		sa_det_orden_descuento.id_porcentaje_descuento
	FROM
		sa_det_orden_descuento
	WHERE
		sa_det_orden_descuento.id_orden = %s",
		valTpDato($idDocumento,"int"));
}

$rsDetDescuentos = mysql_query($queryDetDescuentos) or die(mysql_error()."<br><br>Line: ".__LINE__);
while ($rowDetDescuentos = mysql_fetch_assoc($rsDetDescuentos)) {
	if ($rowDetDescuentos['id_porcentaje_descuento'] == 1){
		$descuentoDetallesManoDeObra += $totalManoDeObra * $rowDetDescuentos['porcentaje'] / 100;
	}else{
		$descuentoDetallesRepuestos += $totalRepuestos * $rowDetDescuentos['porcentaje'] / 100;
	}
}

//for (; $i <= 40; $i++){
//imagestring($img,1,10,$posY,str_pad("-", 18, " ", STR_PAD_BOTH),$textColor);
//imagestring($img,1,100,$posY,str_pad("-", 53, " ", STR_PAD_BOTH),$textColor);
//imagestring($img,1,350,$posY,str_pad("-", 21, " ", STR_PAD_BOTH),$textColor);
//imagestring($img,1,460,$posY,str_pad("-", 15, " ", STR_PAD_BOTH),$textColor);
//imagestring($img,1,543,$posY,str_pad("-", 12, " ", STR_PAD_BOTH),$textColor);
//$posY += 10;
//}


//TOTALES FINAL PIE DE PAGINA
/********************************************************************************/

$limite = ($posY > 552) ? true : false;

$posY = 10;
$auxY1 = $posY;
imageline($img2,375,$posY,602,$posY,$textColor);
$posY += 2;
//TEXTO "SUB-TOTAL"
imagestring($img2,1,390,$posY,str_pad("SUB-TOTAL:", 25, " ", STR_PAD_LEFT),$textColor);
//SUB-TOTAL
if($esGarantia){
	imagestring($img2,1,520,$posY,str_pad("NO CHARGE", 16, " ", STR_PAD_LEFT),$textColor);
}else{
	imagestring($img2,1,520,$posY,str_pad(number_format($rowCliente['subtotal'],2,".",","), 16, " ", STR_PAD_LEFT),$textColor);
}
$posY += 8;
imageline($img2,375,$posY,602,$posY,$textColor);
$posY += 2;

//TEXTO "DESCUENTO"
imagestring($img2,1,390,$posY,str_pad("DESCUENTO:", 25, " ", STR_PAD_LEFT),$textColor);
//DESCUENTO
if($esGarantia){
	imagestring($img2,1,520,$posY,str_pad("NO CHARGE", 16, " ", STR_PAD_LEFT),$textColor);
}else{
	imagestring($img2,1,520,$posY,str_pad(number_format($rowCliente['subtotal_descuento'] + $descuentoDetalles,2,".",","), 16, " ", STR_PAD_LEFT),$textColor);
}
$posY += 8;
imageline($img2,375,$posY,602,$posY,$textColor);
$posY += 2;

$nuevoPorcentaje = round(($rowCliente['subtotal_descuento']*100)/$rowCliente['subtotal'],2);
$nuevoSubtotalDescuento = ($nuevoPorcentaje/100)*$rowCliente['subtotal'];

//TEXTO "MONTO EXENTO"
imagestring($img2,1,390,$posY,str_pad("MONTO EXENTO:", 25, " ", STR_PAD_LEFT),$textColor);
if($esGarantia){
	imagestring($img2,1,520,$posY,str_pad("NO CHARGE", 16, " ", STR_PAD_LEFT),$textColor);
}else{
	imagestring($img2,1,520,$posY,str_pad(number_format($rowCliente["monto_exento"],2,".",","), 16, " ", STR_PAD_LEFT),$textColor);
}
$posY += 8;
imageline($img2,375,$posY,602,$posY,$textColor);
$posY += 2;


//IVAS NUEVOS
$rsIvas = mysql_query($sqlMultiplesIvas);
if(!$rsIvas) { die(mysql_error()."<br><br>Line: ".__LINE__."<br>Query: ".$sqlMultiplesIvas); }

$cotizacion = true;//CONTINGENCIA COTIZACION BORRAR CUANDO PUEDA

while($rowIvas = mysql_fetch_assoc($rsIvas)){
    $cotizacion = false;
    
    if($esGarantia){//si es garantia imprimo sin base y no charge
    	imagestring($img2,1,375,$posY,str_pad($rowIvas["observacion"]." ".$rowIvas["iva"], 27, " ", STR_PAD_LEFT),$textColor);
    	imagestring($img2,1,520,$posY,str_pad("NO CHARGE", 16, " ", STR_PAD_LEFT),$textColor);	
	}else{
		imagestring($img2,1,375,$posY,str_pad($rowIvas["observacion"]." ".$rowIvas["iva"]." ".$rowIvas["base_imponible"], 27, " ", STR_PAD_LEFT),$textColor);
    
    	imagestring($img2,1,520,$posY,str_pad(number_format($rowIvas["subtotal_iva"],2,".",","), 16, " ", STR_PAD_LEFT),$textColor);	
	}
    
    $posY += 8;
    imageline($img2,375,$posY,602,$posY,$textColor);
    $posY += 2;
    
}

if($cotizacion){//CONTINGENCIA COTIZACION BORRAR CUANDO PUEDA
	imagestring($img2,1,390,$posY,str_pad(nombreIva($rowCliente["id_iva"])." ".$rowCliente["iva"]." ".$rowCliente["base_imponible"].":", 25, " ", STR_PAD_LEFT),$textColor);
    
    imagestring($img2,1,520,$posY,str_pad(number_format($rowCliente["subtotal_iva"],2,".",","), 16, " ", STR_PAD_LEFT),$textColor);
    $posY += 8;
    imageline($img2,375,$posY,602,$posY,$textColor);
    $posY += 2;
}

if($rowCliente["total"] == 0){//CONTINGENCIA COTIZACION BORRAR CUANDO PUEDA
	$rowCliente["total"] = $rowCliente["base_imponible"] + $rowCliente["subtotal_iva"];
}

//TEXTO "TOTAL"
imagestring($img2,1,390,$posY,str_pad("TOTAL:", 25, " ", STR_PAD_LEFT),$textColor);
//TOTAL
//$totalPresupuesto = round($rowCliente['subtotal'] - $nuevoSubtotalDescuento - $descuentoDetalles + $iva,2);//antes
if($esGarantia){
	imagestring($img2,1,520,$posY,str_pad("NO CHARGE", 16, " ", STR_PAD_LEFT),$textColor);
}else{
	imagestring($img2,1,520,$posY,str_pad(number_format($rowCliente["total"],2,".",","), 16, " ", STR_PAD_LEFT),$textColor);
}
$posY += 8;
$auxY2 = $posY;
imageline($img2,375,$posY,602,$posY,$textColor); //H -
imageline($img2,375,$auxY1,375,$auxY2,$textColor); //V IZQ |
imageline($img2,515,$auxY1,515,$auxY2,$textColor); //V DER | 
imageline($img2,602,$auxY1,602,$auxY2,$textColor); //H -
$posY += 2;

$posY = 10;
$auxY1 = $posY;
imageline($img2,10,$posY,160,$posY,$textColor);
$posY += 4;
//TEXTO "FECHA APROBACIÓN"
imagestring($img2,1,12,$posY,str_pad("FECHA APROBACI".utf8_decode("Ó")."N", 16, " ", STR_PAD_LEFT),$textColor);
$posY += 16;
imageline($img2,10,$posY,160,$posY,$textColor);
$posY += 4;

//TEXTO MENSAJE DE ADVERTENCIA
imagestring($img2,1,220,14,"PRECIOS DE REPUESTOS SUJETOS",$textColor);
imagestring($img2,1,220,24," A DISPONIBILIDAD POR LOTE",$textColor);
imagestring($img2,1,220,44,utf8_decode("SÓLO SE MANTIENE LOS PRECIOS"),$textColor);
imagestring($img2,1,220,54," AL CONFIRMARSE LA RESERVA",$textColor);
imagestring($img2,1,215,74,"EXCLUYE CAMBIOS Y DEVOLUCIONES",$textColor);

//TEXTO "HORA APROBACIÓN"
imagestring($img2,1,12,$posY,str_pad("HORA APROBACI".utf8_decode("Ó")."N", 16, " ", STR_PAD_LEFT),$textColor);
$posY += 16;
imageline($img2,10,$posY,160,$posY,$textColor);
$posY += 4;

//TEXTO "CLIENTE"
imagestring($img2,1,12,$posY,str_pad("CLIENTE", 16, " ", STR_PAD_LEFT),$textColor);
$posY += 16;
imageline($img2,10,$posY,160,$posY,$textColor);
$posY += 4;

//TEXTO "FIRMA"
imagestring($img2,1,12,$posY,str_pad("FIRMA", 16, " ", STR_PAD_LEFT),$textColor);
$posY += 16;
imageline($img2,10,$posY,160,$posY,$textColor);
$auxY2 = $posY;
imageline($img2,10,$auxY1,10,$auxY2,$textColor);
imageline($img2,160,$auxY1,160,$auxY2,$textColor);
imageline($img2,95,$auxY1,95,$auxY2,$textColor);
$posY += 4;

//TEXTO "SERVICIO"
imagestring($img2,1,100,$posY,str_pad("SERVICIO", 16, " ", STR_PAD_LEFT),$textColor);

//TEXTO "REPUESTO"
imagestring($img2,1,400,$posY,str_pad("REPUESTO", 16, " ", STR_PAD_LEFT),$textColor);
$posY += 10;

//TEXTO "NOMBRE FECHA"
imagestring($img2,1,50,$posY,str_pad("NOMBRE", 16, " ", STR_PAD_LEFT),$textColor);
imagestring($img2,1,150,$posY,str_pad("FECHA", 16, " ", STR_PAD_LEFT),$textColor);

//TEXTO "NOMBRE FECHA"
imagestring($img2,1,350,$posY,str_pad("NOMBRE", 16, " ", STR_PAD_LEFT),$textColor);
imagestring($img2,1,450,$posY,str_pad("FECHA", 16, " ", STR_PAD_LEFT),$textColor);
$posY += 12;

//RECTANGULO
$auxY1 = $posY;
imageline($img2,50,$posY,250,$posY,$textColor);
imageline($img2,350,$posY,550,$posY,$textColor);
$posY += 12;
imageline($img2,50,$posY,250,$posY,$textColor);
imageline($img2,350,$posY,550,$posY,$textColor);
$auxY2 =$posY;
imageline($img2,175,$auxY1,175,$auxY2,$textColor);
imageline($img2,50,$auxY1,50,$auxY2,$textColor);
imageline($img2,250,$auxY1,250,$auxY2,$textColor);

imageline($img2,475,$auxY1,475,$auxY2,$textColor);
imageline($img2,350,$auxY1,350,$auxY2,$textColor);
imageline($img2,550,$auxY1,550,$auxY2,$textColor);
$posY += 4;

//TEXTO "HORA"
imagestring($img2,1,450,$posY,str_pad("HORA", 16, " ", STR_PAD_LEFT),$textColor);

//TEXTO "HORA"
imagestring($img2,1,450,$posY,str_pad("HORA", 16, " ", STR_PAD_LEFT),$textColor);
$posY += 12;

//RECTANGULO
$auxY1 = $posY;
imageline($img2,175,$posY,250,$posY,$textColor);
imageline($img2,475,$posY,550,$posY,$textColor);
$posY += 12;
imageline($img2,50,$posY,250,$posY,$textColor);
imageline($img2,350,$posY,550,$posY,$textColor);
$auxY2 =$posY;
imageline($img2,175,$auxY1,175,$auxY2,$textColor);
imageline($img2,250,$auxY1,250,$auxY2,$textColor);

imageline($img2,475,$auxY1,475,$auxY2,$textColor);
imageline($img2,550,$auxY1,550,$auxY2,$textColor);
$posY += 4;

//TEXTO "FIRMA"
imagestring($img2,1,50,$posY,str_pad("FIRMA", 16, " ", STR_PAD_LEFT),$textColor);

//TEXTO "FIRMA"
imagestring($img2,1,350,$posY,str_pad("FIRMA", 16, " ", STR_PAD_LEFT),$textColor);
$posY += 12;

//NOMBRE EMPRESA DIRECCION EMPRESA TELEFONO FAX
imagestring($img2,1,10,$posY,str_pad($rowEmpresa['nombre_empresa']." ".$rowEmpresa['direccion'], 115, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img2,1,10,$posY,str_pad("TELEFONO. ".$rowEmpresa['telefono1']." FAX. ".$rowEmpresa['fax'], 115, " ", STR_PAD_BOTH),$textColor);
$posY += 10;

//fecha hora impreso:
imagestring($img2,1,450,$posY,"IMPRESO:".date("d-m-Y h:i A"),$textColor);

if ($valCadBusq[1] == 1){
//TEXTO "PRESUPUESTO VÁLIDO POR TANTOS DÍAS"
//$dias = (strtotime($rowCliente['fecha_vencimiento']) - strtotime(date("Y-m-d",strtotime($rowCliente['fecha_presupuesto'])))/ 86400);
//imagestring($img,1,10,$posY,str_pad($texto_presupuesto_cotizacion." V".utf8_decode("Á")."LIDO POR ".($dias / 86400)." D".utf8_decode("Í")."AS", 115, " ", STR_PAD_BOTH),$textColor);

}

$r = imagepng($img,"img/tmp/orden.png");
$p = imagepng($img2,"img/tmp/ordenPie.png");
$s = imagepng($imgCabecera,"img/tmp/ordenCabecera.png");

$pdf = new PDF_AutoPrint('P','cm','LETTER');
//$pdf->AutoPrint(true);
$pdf->AddPage('P',$tamanoPaginaPixel);//$tamanoPaginaPixel

$pdf->Image("img/tmp/orden.png", 0, 0);
$pdf->Image("img/tmp/ordenCabecera.png", 0, 0);

//LOGO EMPRESA
$pdf->Image("../".$ruta_logo, '0.2', '0.2', '4', '1.5', '','');

//CODIGO DE BARRA
$pdf->Image($ruta, 17, 0.3, '', '', '','');

$posPie = 19.5;

if ($limite){
	$pdf->AddPage('P',$tamanoPaginaPixel);//$tamanoPaginaPixel
	$pdf->Image("img/tmp/ordenCabecera.png", 0, 0);
	//LOGO EMPRESA
	$pdf->Image("../".$ruta_logo, '0.2', '0.2', '4', '1.5', '','');

	//CODIGO DE BARRA
	$pdf->Image($ruta, 17, 0.3, '', '', '','');
	$posPie = 2;
}
$pdf->Image("img/tmp/ordenPie.png", 0, $posPie);

unlink($ruta);//elimino la imagen, si el codigo de barras falla, igual incluira la ultima imagen generada.

$pdf->SetDisplayMode("fullwidth");
$pdf->SetDisplayMode(70,'single');
$pdf->AutoPrint(true);

$pdf->Output();

function cantidadDespachada($id_det_orden_articulo_ref){
    // SI ESTAN EN SOLICITUD 3 DESPACHADO 5 FACTURADO
    if($id_det_orden_articulo_ref){
        $queryCont = sprintf("SELECT COUNT(*) AS nro_rpto_desp FROM sa_det_solicitud_repuestos
        WHERE sa_det_solicitud_repuestos.id_det_orden_articulo = %s
                AND (sa_det_solicitud_repuestos.id_estado_solicitud = 3 
                    OR sa_det_solicitud_repuestos.id_estado_solicitud = 5
                    OR sa_det_solicitud_repuestos.id_estado_solicitud = 2) ", 
        $id_det_orden_articulo_ref);
        $rsCont = mysql_query($queryCont);
        $rowCont = mysql_fetch_assoc($rsCont);

		//OJO SE USA TAMBIEN PARA CALCULAR TOTALES
		if (!$rsCont){
			return "Error cargarDcto \n".mysql_error().$queryCont."\n Linea: ".__LINE__;
		}else {
			if ($rowCont['nro_rpto_desp'] != NULL || $rowCont['nro_rpto_desp'] != ""){
					$cantidad_art = $rowCont['nro_rpto_desp'];
			}else{
					$cantidad_art = 0;
			}
		}

        return $cantidad_art;
    }
}

function cantidadSolicitada($id_det_orden_articulo_ref){
    //TOTAL SOLICITADA
    if($id_det_orden_articulo_ref){
        $queryContTotal = sprintf("SELECT COUNT(*) AS nro_rpto_desp 
									FROM sa_det_solicitud_repuestos
       	 							WHERE sa_det_solicitud_repuestos.id_det_orden_articulo = %s", 
                $id_det_orden_articulo_ref);
        $rsContTotal = mysql_query($queryContTotal);

        if (!$rsContTotal){
            return "Error cargarDcto \n".mysql_error().$queryContTotal."\n Linea: ".__LINE__;
        }
        $rowContTotal = mysql_fetch_assoc($rsContTotal);

        $cantidad_art_total = $rowContTotal['nro_rpto_desp'];

        return $cantidad_art_total;
    }
}

function cantidadDisponibleActual($idArticuloCosto){
    if($idArticuloCosto){
        $queryDisponible = sprintf("SELECT cantidad_disponible_logica 
                                    FROM vw_iv_articulos_almacen_costo 
                                    WHERE id_articulo_costo = '%s' 
									AND cantidad_disponible_logica > 0 LIMIT 1",
                                    $idArticuloCosto);
        $rs = mysql_query($queryDisponible);
        if(!$rs){ die("Error cargarDcto \n".mysql_error().$queryDisponible."\n Linea: ".__LINE__); }

        if(mysql_num_rows($rs)){
            return "SI";
        }else{
            return "NO";
        }
        
    }
}


function nombreIva($idIva){
    //cuando se crea no posee iva, por lo tanto deberia ser el primero id 1 itbms-iva
    if($idIva == NULL || $idIva == "0" || $idIva == "" || $idIva == " "){
        $idIva = 1;
    }    
    $query = "SELECT observacion FROM pg_iva WHERE idIva = ".$idIva."";
    $rs = mysql_query($query);
    if(!$rs){ die ("Error cargarDcto \n".mysql_error().$query."\n Linea: ".__LINE__); }
    
    $row = mysql_fetch_assoc($rs);
    
    return $row['observacion'];
    
}

function fechaTiempo($fechaTiempo){
	if($fechaTiempo != ""){
		return date("d-m-Y h:i A",strtotime($fechaTiempo));
	}
}

?>