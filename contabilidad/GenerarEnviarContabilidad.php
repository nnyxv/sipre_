<?php session_start();
include("FuncionesPHP.php");
//generarVentasVe(0,'01-01-2011','31-01-2011');
//Genera las Compras Administrativas

/*F 1*/
//**************************COMPRAS ADMINISTRATIVAS**********************************
function generarComprasAd($idFactura=0,$Desde="",$Hasta=""){
	$con = ConectarBD();
	$SqlStr = "update parametros set MensajeRet = ''";
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__);

	$SqlStr = "SELECT
		a.id_factura as factura
		,a.fecha_origen as fecha
		,'01' as centrocosto
		,'' as cuentacontable
		,d.descripcion as descripcion
		,sum(b.cantidad*precio_unitario) AS debe 
		,0 AS haber 
		,a.numero_factura_proveedor AS documento
		,(SELECT SUM(fact_comp_iva.subtotal_iva)
			FROM ".$_SESSION['bdEmpresa'].".cp_factura_iva fact_comp_iva
			WHERE fact_comp_iva.id_factura = a.id_factura) AS iva
		,(SELECT cuenta FROM detalleintegracion x1 WHERE x1.idencabezado = 6 AND x1.idobjeto = 3 AND x1.sucursal = 1) AS cuentaiva
		,a.id_proveedor AS proveedor
		,(SELECT concat(nombre) FROM ".$_SESSION['bdEmpresa'].".cp_proveedor x3 WHERE x3.id_proveedor = a.id_proveedor) AS Proveedor
		,a.subtotal_descuento 
		,c.id_subseccion
		,a.id_empresa
		,(SELECT SUM((fact_comp_siva.base_imponible*fact_comp_siva.iva)/100) FROM ".$_SESSION['bdEmpresa'].".cp_factura_iva fact_comp_siva WHERE fact_comp_siva.id_factura = b.id_factura) AS siva
		,(SELECT SUM(cantidad*precio_unitario)  FROM  ".$_SESSION['bdEmpresa'].".cp_factura_detalle WHERE  id_factura=a.id_factura) as debecxp
	FROM
		".$_SESSION['bdEmpresa'].".ga_subsecciones d
  	     INNER JOIN ".$_SESSION['bdEmpresa'].".ga_articulos c ON (d.id_subseccion = c.id_subseccion)
         INNER JOIN ".$_SESSION['bdEmpresa'].".cp_factura_detalle b ON (b.id_articulo = c.id_articulo)
         INNER JOIN ".$_SESSION['bdEmpresa'].".cp_factura a ON (a.id_factura = b.id_factura)
		 LEFT OUTER JOIN ".$_SESSION['bdEmpresa'].".ga_factura_detalle_iva e ON (b.id_factura_detalle = e.id_factura_detalle)
	WHERE		
		a.id_modulo = 3";
	if($idFactura != 0){
		$SqlStr.=" AND a.id_factura = ".$idFactura;
	}else{
		$SqlStr.=" AND a.fecha_origen between '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$SqlStr.=" group by a.id_factura,c.id_subseccion
	order by a.fecha_origen,a.id_factura,c.id_subseccion";
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__);
	//,round(ifnull(sum((b.cantidad*b.precio_unitario)*(b.iva/100)),0),2) AS iva 
	$facturadecx = 0;
	$fcxp = 0;
	$fechaAnt = "";
	$icomprobant = 0;
	$facturaAnt = "";
	$arrayIdFactura = array();//para evitar ivas duplicados
	
	while ($row = ObtenerFetch($exec)) {
		$id = $row[0];
		$fecha = $row[1];
		$cc = $row[2];
		$id_subsecion = $row[13];
		$sucursal = $row[14];
		$cuentacontable = buscarContable(1,$id_subsecion,$sucursal);
		$descripcion = $row[4];
		$Debe = $row[5];
		$Haber = $row[6];
		$documento = $row[7];
		//$montoiva = $row[8];
		//$cuentaiva = $row[9];
		$idproveedor = $row[10];
		$Desproveedor = $row[11];
		$Descuento = $row[12];
		$montoiva = $row[15];
		$debecxp = ($row[16]);

		$descripcion = $descripcion . " " .$Desproveedor;
		
		$cuentaiva = buscarContable(6,3,$sucursal);
		
		$ct='01';
		$dt='01';
		$cc='01';
		
		$cuentaCXP = buscarContable(16,$idproveedor,$sucursal);
		
		// VERIFICA VALORES DE CONFIGURACION (Consulta el Pais del sistema)
		$queryConfig403 = "SELECT valor FROM ".$_SESSION['bdEmpresa'].".pg_configuracion_empresa config_emp
			INNER JOIN ".$_SESSION['bdEmpresa'].".pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
		WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = 1";
		$rsConfig403 =  EjecutarExec($con,$queryConfig403) or die($queryConfig403." " .mysql_error()); 
		$rowConfig403 = ObtenerFetch($rsConfig403);
		$valor = $rowConfig403['0'];// 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
		
		$Deber = $row[5];
		
		$sql = sprintf("SELECT SUM(monto_retenido) AS monto
						FROM ".$_SESSION['bdEmpresa'].".te_retencion_cheque
						WHERE id_factura = %s
						AND tipo = %s
						AND tipo_documento = %s
						AND anulado IS NULL",
					$id, // id de la factura
					0, //0 = Factura 1 = Nota de cargo
					2); // 0 = Cheque, 1 = Transferencia, 2 = Sin Documento
		$execISLR =  EjecutarExec($con,$sql);
		$rowISLR = ObtenerFetch($execISLR);
		$montoRetencionISLR = $rowISLR[0];
		
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			if ($fechaAnt != $fecha){
				$icomprobant++;
				$fechaAnt = $fecha;
			}
			
			$MontoRetenidoIVA = 0; 
			
			if ($facturaAnt != $id){
				$SqlStr="SELECT sum(ivaRetenido) FROM ".$_SESSION['bdEmpresa'].".cp_retenciondetalle WHERE idfactura = $id AND id_nota_credito IS NULL";	//echo $SqlStr;							
				$exec3 = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
				$row = ObtenerFetch($exec3);
					if ($row[0] > 0){
						$MontoRetenidoIVA = $row[0];
						$cuentaRetenido = buscarContable(6,5,$sucursal);// 6 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
					}
				$facturaAnt = $id;
			}
			
			//GASTO SIN IMPUESTO
			$SqlStr="SELECT sum(monto) FROM ".$_SESSION['bdEmpresa'].".cp_factura_gasto WHERE id_factura = $id";
			$exec4 = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
			$row = ObtenerFetch($exec4);
			if ($row[0] > 0){
				$GastoSinImp = $row[0];
				$montoiva = bcadd($montoiva,$GastoSinImp,2); //SUMA
			}
					
			if($Descuento == 0){
			// Compras sin descuentos
				ingresarRenglon($cuentacontable,$descripcion,$Deber,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				//NO VA - 28/07/2014
				/* Para insertar los terceros */
					/*ingresarEnlacesTerceros($cuentacontable,$fecha,$Debe,$Haber,$id_subsecion
					,"ga_subsecciones","id_subseccion|descripcion",$descripcion);*/
				/* Fin Para insertar los terceros */
				
				//para el iva.
				
				if(!in_array($id, $arrayIdFactura)){					
					ingresarRenglon($cuentaiva,$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				}
				
				//NO VA - 28/07/2014
				/* Para insertar los terceros */
					/*ingresarEnlacesTerceros($cuentaiva,$fecha,$montoiva,0,$idproveedor
					,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
				/* Fin Para insertar los terceros */
				
				//para la cxp.
				if ($MontoRetenidoIVA == 0){
					if ($facturadecx != $id){
						$montoCXP = bcadd($Debe,$montoiva,2);
						$facturadecx = $id;
					}else{
						$montoCXP = $Debe;					
					}
					
					
				}else{
					//$montoCXP = bcadd($Debe,$montoiva,2);
					$montoCXP = bcadd($Debe,$montoiva,2);
					$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2);		
					ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
				}
				if(!in_array($id, $arrayIdFactura)){//POR FACTURA
					if($montoRetencionISLR > 0){
						// Administracion
						$cuentaMovIslr =  buscarContable(6,4,$sucursal);  
						ingresarRenglon($cuentaMovIslr,"ISLR ".$descripcion,0,$montoRetencionISLR,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						if ($valor==1){ //Venezuela
							$debecxp = bcsub($debecxp,$montoRetencionISLR,2);
						}else if($valor==2|| $valor==3) {
							$montoCXP = bcsub($montoCXP,$montoRetencionISLR,2);
						}
					}
				}
				
				if ($valor==1){ //Venezuela
					if ($fcxp != $id){
						//$montoCXP = bcadd($Debe,$montoiva,2);
					$debecxpi = bcadd($debecxp,$montoiva,2);
					$debecxpi = bcsub($debecxpi,$MontoRetenidoIVA,2);
					ingresarRenglon($cuentaCXP,$descripcion,0,$debecxpi,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);				
						$fcxp = $id;
					}
				}else if($valor==2){ //Panamá 			
					ingresarRenglon($cuentaCXP,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);					
					if(!in_array($id, $arrayIdFactura)){//
						ingresarRenglon($cuentaCXP,$descripcion,0,$montoiva,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);						
					}
				}else if ($valor==3){ //Puerto Rico

					if ($fcxp != $id){
					$montoCXP = bcadd($Debe,$montoiva,2);										
					
					$debecxpi = bcadd($debecxp,$montoiva,2);
					$debecxpi = bcsub($debecxpi,$MontoRetenidoIVA,2);
					
					//echo $id."-".$montoCXP."-".$debecxpi."<br>";
					
					ingresarRenglon($cuentaCXP,$descripcion,0,$debecxpi,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);				
					//ingresarRenglon($cuentaCXP,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);					
						$fcxp = $id;
					}
				}
				/* Para insertar los terceros */
					ingresarEnlacesTerceros($cuentaCXP,$fecha,0,$montoCXP,$idproveedor
					,"cp_proveedor","id_proveedor|nombre",$descripcion);
				/* Fin Para insertar los terceros */
			// Fin Compras con descuentos
			}else{
			// Compras con descuentos
				ingresarRenglon($cuentacontable,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				//NO VA - 28/07/2014
				/* Para insertar los terceros */
					/*ingresarEnlacesTerceros($cuentacontable,$fecha,$Debe,$Haber,$idproveedor
					,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
				/* Fin Para insertar los terceros */
	
				//para el iva.
				if(!in_array($id, $arrayIdFactura)){
					ingresarRenglon($cuentaiva,$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				}
				//NO VA - 28/07/2014
			   /* Para insertar los terceros */
					/*ingresarEnlacesTerceros($cuentaiva,$fecha,$montoiva,0,$idproveedor
					,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
				/* Fin Para insertar los terceros */
			   
			   $cuentaDescuento = buscarContable(6,2,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
			   //$montoCXP = bcadd($Debe,$montoiva,2);
   			   $montoCXP = $Debe;
			   $montoCXP = bcsub($montoCXP,$Descuento,2);	
				if ($MontoRetenidoIVA != 0){	   
					$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2);	
					ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
				}
				if(!in_array($id, $arrayIdFactura)){//POR FACTURA
					if($montoRetencionISLR > 0){
						// Administracion
						$cuentaMovIslr =  buscarContable(6,4,$sucursal);  
						ingresarRenglon($cuentaMovIslr,"ISLR ".$descripcion,0,$montoRetencionISLR,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						$montoCXP = bcsub($montoCXP,$montoRetencionISLR,2);
					}
				}
				
			   //para el  descuento.
			   ingresarRenglon($cuentaDescuento,$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					//NO VA - 28/07/2014			   
					/* Para insertar los terceros */
					/*	ingresarEnlacesTerceros($cuentaDescuento,$fecha,0,$Descuento,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
			   
				//para la cxp.
				
				if($valor==1){//Venezuela
					ingresarRenglon($cuentaCXP,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				
				
				}else if(($valor==2)||($valor==3)){ //Panamá o Puerto Rico
					ingresarRenglon($cuentaCXP,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					if(!in_array($id, $arrayIdFactura)){
						ingresarRenglon($cuentaCXP,$descripcion,0,$montoiva,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					}
				}
				/* Para insertar los terceros */
					ingresarEnlacesTerceros($cuentaCXP,$fecha,0,$montoCXP,$idproveedor
					,"cp_proveedor","id_proveedor|nombre",$descripcion);
				/* Fin Para insertar los terceros */
			// Fin Compras con descuentos
			}  
		}//if(buscarDoc($id,$cc,$ct,$dt)==0){
			
		$arrayIdFactura[] = $id;
			
	}//	while ($row = ObtenerFetch($exec)) {

}

/*F 2*/
//*****************************COMPRAS VEHICULOS*************************************
function generarComprasVe($idFactura=0,$Desde="",$Hasta=""){
	$con = ConectarBD();
	$SqlStr = "SELECT
		a.id_factura as factura
		,a.fecha_origen as fecha
		,'02' as centrocosto
		,b.id_condicion_unidad as condicionunidad
		,d.nom_modelo as descripcion
		,sum(b.costo_compra) as debe 
		,0 as haber 
		,a.numero_factura_proveedor as documento
		,round(sum(iva_compra)+sum(impuesto_lujo_compra),2) as iva 
		,(SELECT cuenta FROM detalleintegracion x1 WHERE x1.idencabezado = 22 AND x1.idobjeto = 3 AND x1.sucursal = 1) as cuentaiva
		,a.id_proveedor as proveedor
		,(SELECT concat(nombre) FROM ".$_SESSION['bdEmpresa'].".cp_proveedor x3 WHERE x3.id_proveedor = a.id_proveedor) as Proveedor
		,a.subtotal_descuento 
		,d.id_modelo
		,b.id_unidad_fisica
		,b.porcentaje_iva_compra
		,a.id_empresa
		,a.id_modo_compra
	FROM
		".$_SESSION['bdEmpresa'].".cp_factura a 
		,".$_SESSION['bdEmpresa'].".an_unidad_fisica b
		, ".$_SESSION['bdEmpresa'].".an_uni_bas c 
		,".$_SESSION['bdEmpresa'].".an_modelo  d
		,".$_SESSION['bdEmpresa'].".cp_factura_detalle_unidad  e
	WHERE c.mod_uni_bas = d.id_modelo
		AND e.id_factura = a.id_factura
		AND e.id_factura_detalle_unidad = b.id_factura_compra_detalle_unidad
		AND b.id_uni_bas = c.id_uni_bas
		AND a.id_modulo = 2";
	
	if($idFactura != 0){
		$SqlStr.=" AND a.id_factura = ".$idFactura;
	}else{
		$SqlStr.=" AND a.fecha_origen BETWEEN '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$SqlStr.=" GROUP BY a.id_factura
	ORDER BY a.fecha_origen, a.id_factura";
	
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__);
		$fechaAnt = "";
		$icomprobant = 0;
		while ($row = ObtenerFetch($exec)) {
			$id = $row[0]; //id_factura
			$fecha = $row[1]; //fecha_origen
			$cc = $row[2]; //centrocosto
			$condicionUnidad = $row[3]; //condicionunidad
			$descripcion = $row[4]; //nom_modelo
			$Debe = $row[5]; //costo_compra
			$Haber = $row[6]; //haber = 0
			$documento = $row[7]; //numero_factura_proveedor
			$montoiva = $row[8];
			//$cuentaiva = $row[9]; 
			$idproveedor = $row[10]; // id_proveedor
			$Desproveedor = $row[11]; //Proveedor
			$Descuento = $row[12]; //subtotal_descuento
			$id_modelo = $row[13]; //id_modelo
			$id_unidad_fisica = $row[14]; //id_unidad_fisica
			$porcentajeIVA = $row[15]; //porcentaje_iva_compra
			$sucursal = $row[16]; //id_empresa
			$idModoCompra = $row[17]; //id_modo_compra
			
			if ($condicionUnidad != 1){ // 1 = Nuevo ; 2 = Usado ; 3 = Usado Particular
				$cuentacontable = buscarContable(3,$id_modelo,$sucursal); // 3 = Inventario Vehiculos Usados
			}else{
				$cuentacontable = buscarContable(2,$id_modelo,$sucursal); // 2 = Inventario Vehiculos Nuevo 
			}
			
			$descripcion  = $descripcion . " " .$Desproveedor;
			
			$cuentaiva = buscarContable(22,3,$sucursal);
			
			$ct='01';
			$dt='01';
			$cc='02';
			
			$sql = sprintf("SELECT SUM(monto_retenido) AS monto
							FROM ".$_SESSION['bdEmpresa'].".te_retencion_cheque
							WHERE id_factura = %s
							AND tipo = %s
							AND tipo_documento = %s
							AND anulado IS NULL",
						$id, // id de la factura
						0, //0 = Factura 1 = Nota de cargo
						2); // 0 = Cheque, 1 = Transferencia, 2 = Sin Documento
			$execISLR =  EjecutarExec($con,$sql);
			$rowISLR = ObtenerFetch($execISLR);
			$montoRetencionISLR = $rowISLR[0];
			
			if($idModoCompra == 1){ //1 = Nacional, 2 = Importacion
				if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
					if ($fechaAnt != $fecha){
						$icomprobant++;
						$fechaAnt = $fecha;
					}
					
					$MontoRetenidoIVA = 0;  
					$SqlStr="SELECT sum(ivaRetenido) FROM ".$_SESSION['bdEmpresa'].".cp_retenciondetalle WHERE idfactura = $id";
					$exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
					$row = ObtenerFetch($exec3);
					if ($row[0] > 0){
						$MontoRetenidoIVA = $row[0];     	   
						$cuentaRetenido = buscarContable(22,5,$sucursal);// 6 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
					}
					$cuentaCXP = buscarContable(13,$idproveedor,$sucursal);
					
					// INICIO para los accesorios	  
					$SqlStr="SELECT
						a.id_accesorio
						,a.costo_partida
						,b.iva_accesorio
						,b.nom_accesorio
					FROM
						".$_SESSION['bdEmpresa'].".an_partida a
						,".$_SESSION['bdEmpresa'].".an_accesorio b
						,".$_SESSION['bdEmpresa'].".an_unidad_fisica c
					WHERE
						a.id_accesorio = b.id_accesorio
						AND c.id_unidad_fisica = a.id_unidad_fisica 
						AND c.id_unidad_fisica = $id_unidad_fisica
						AND tipo_partida = 'COMPRA'";
					$exec5 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
					$montoIvaAccesorio = 0;
					$costo_partidaSuma = 0;
					while($row3 = ObtenerFetch($exec5)){
						$id_accesorio = $row3[0];
						$costo_partida = $row3[1];
						$aplicaIVA = $row3[2];
						$nom_accesorio = $row3[3];
						$cuentaAccesorio = buscarContable(21,$id_accesorio,$sucursal);// 21 es compras adminstriativas en el encabezado .
						$cuentaAccesorio = $cuentacontable;
						// ingresarRenglon($cuentaAccesorio,$nom_accesorio,$costo_partida,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D');
						// $Debe = bcadd($Debe,$costo_partida,2);
						if ($aplicaIVA == 1){
							$calIVA = bcmul($costo_partida,$porcentajeIVA,2);
							$calIVA =  bcdiv($calIVA,100,2);
							$montoIvaAccesorio = bcadd($montoIvaAccesorio,$calIVA,2);
						}
						$costo_partidaSuma = bcadd($costo_partidaSuma,$costo_partida,2);
					}// FIN para los accesorios
					
					$Debe = bcadd($Debe,$costo_partidaSuma,2); //suma
					ingresarRenglon($cuentacontable,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
								//NO VA - 28/07/2014
								 /* Para insertar los terceros */
									/*ingresarEnlacesTerceros($cuentacontable,$fecha,$Debe,$Haber,$id_modelo
									,"an_modelo","id_modelo|des_modelo",$descripcion);*/
								/* Fin Para insertar los terceros */
								
					$montoiva = bcadd($montoiva,$montoIvaAccesorio,2);
					//para el iva.
					ingresarRenglon($cuentaiva,$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				   //para la cxp.	
					//NO VA - 28/07/2014  
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros($cuentaiva,$fecha,$montoiva,0,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					
					// Compras sin descuentos			   
					if($Descuento == 0){
						if ($MontoRetenidoIVA == 0){
							$montoCXP = bcadd($Debe,$montoiva,2); //SUMA
						}else{
							$montoCXP = bcadd($Debe,$montoiva,2); //SUMA
							$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2); // RESTA
							ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
							/* Para insertar los terceros */
								/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
								,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
							/* Fin Para insertar los terceros */			
						}
						
						if($montoRetencionISLR > 0){
							// vehiculo
							$cuentaMovIslr =  buscarContable(22,4,$sucursal);  
							ingresarRenglon($cuentaMovIslr,"ISLR ".$descripcion,0,$montoRetencionISLR,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							$montoCXP = bcsub($montoCXP,$montoRetencionISLR,2);
						}
						
						ingresarRenglon($cuentaCXP,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						/* Para insertar los terceros */
							ingresarEnlacesTerceros($cuentaCXP,$fecha,0,$montoCXP,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);
						/* Fin Para insertar los terceros */
					}else{// Fin Compras sin descuentos
						$cuentaDescuento = buscarContable(22,2,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
						$montoCXP = bcadd($Debe,$montoiva,2); //SUMA
						$montoCXP = bcsub($montoCXP,$Descuento,2); // RESTA
						if ($MontoRetenidoIVA != 0){	   
						$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2); // RESTA	
						ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
						}
						
						if($montoRetencionISLR > 0){
							// vehiculo
							$cuentaMovIslr =  buscarContable(22,4,$sucursal);  
							ingresarRenglon($cuentaMovIslr,"ISLR ".$descripcion,0,$montoRetencionISLR,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							$montoCXP = bcsub($montoCXP,$montoRetencionISLR,2);
						}
						
						//para el  descuento.
						ingresarRenglon($cuentaDescuento,$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						//NO VA - 28/07/2014			   
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros($cuentaDescuento,$fecha,0,$Descuento,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
						
						//para la cxp.
						ingresarRenglon($cuentaCXP,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						/* Para insertar los terceros */
							ingresarEnlacesTerceros($cuentaCXP,$fecha,0,$montoCXP,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);
						/* Fin Para insertar los terceros */
					}//   Fin Compras con descuentos
				}//if(buscarDoc($id,$cc)==0){
										
			}else if($idModoCompra == 2){//1 = Nacional, 2 = Importacion
				$cuentaImpor = buscarContable(77,5,$sucursal); //GASTOS DE IMPORTACION: PANAMA = 1.1.04.03.002 ; VENEZUELA = 8.2.01.01.054
				$cuentaRever = buscarContable(78,5,$sucursal); //DERECHOS ARANCELARIOS: son los Impuestos que se deben pagar en la aduana en el momento de importar o exportar mercancías. VENEZUELA = 8.2.01.01.057
				$cuentaOtrosCargos = buscarContable(80,5,$sucursal); //DERECHOS ARANCELARIOS: son los Impuestos que se deben pagar en la aduana en el momento de importar o exportar mercancías. VENEZUELA = 8.2.01.01.057
				$cuentaDif = buscarContable(79,5,$sucursal); //GANANCIA O PERDIDA POR DIFERENCIA ADUANAS // SOLO APLICA PARA VZLA: 9.1.01.01.016
				if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
					if ($fechaAnt != $fecha){
						$icomprobant++;
						$fechaAnt = $fecha;
					}
					$MontoRetenidoIVA = 0;
					
					$SqlStr="SELECT sum(ivaRetenido) FROM ".$_SESSION['bdEmpresa'].".cp_retenciondetalle WHERE idfactura = $id";
					$exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
					$row = ObtenerFetch($exec3);
					if ($row[0] > 0){
						$MontoRetenidoIVA = $row[0];     	   
						$cuentaRetenido = buscarContable(22,5,$sucursal);// 22 Fijos Entradas Vehiculos en el encabezado y 5 es en el detalle de los fijos.
					}
					$cuentaCXP = buscarContable(13,$idproveedor,$sucursal); // 13 CXP Vehiculos
					
					$SqlStr="SELECT
						a.monto,
						a.iva,
						a.id_modo_gasto,
						a.afecta_documento,
						b.id_gasto
					from
					  ".$_SESSION['bdEmpresa'].".cp_factura_gasto a
						,".$_SESSION['bdEmpresa'].".pg_gastos b  
					WHERE
						a.id_gasto = b.id_gasto
						AND a.id_factura = $id";
					$exec5 = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
					
					$costo_partidaSuma = 0;
					$gastosImportacion = 0;
					$otrosCargos = 0;
					$gastos2 = 0;
					$montoIvaAccesorio = 0;
					$calIVA = 0;
					$reverso = 0;
					$diferencia = 0;
					while($row3 = ObtenerFetch($exec5)){
						
						$costo_partida = $row3[0];
						$porcentajeIVA = $row3[1];
						$modoGasto = $row3[2];
						$afectaDoc = $row3[3];
						$modo = $row3[4];
						
						if($modoGasto==1 && $afectaDoc==1)
							$costo_partidaSuma = bcadd($costo_partidaSuma,$costo_partida,2);
						
						if($modoGasto==2 || $afectaDoc<>1)
							$gastosImportacion = bcadd($gastosImportacion,$costo_partida,2);
							
						if($modoGasto==3 || $afectaDoc<>1)
							$otrosCargos = bcadd($otrosCargos,$costo_partida,2);
								
						// VERIFICA VALORES DE CONFIGURACION (Consulta el Pais del sistema)
						$queryConfig403 = "SELECT valor FROM ".$_SESSION['bdEmpresa'].".pg_configuracion_empresa config_emp
							INNER JOIN ".$_SESSION['bdEmpresa'].".pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
						WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = 1";
						$rsConfig403 =  EjecutarExec($con,$queryConfig403) or die($queryConfig403." " .mysql_error()); 
						$rowConfig403 = ObtenerFetch($rsConfig403);
						$valor = $rowConfig403['0'];// 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
						
						if ($valor == 1) { // CONFIGURACIÓN SOLO PARA VENEZUELA
							if($modo==12 || $modo==13 || $modo==17) //12 = USO DEL SISTEMA
								$reverso = bcadd($reverso,$costo_partida,2);
								
							if($modo==21 || $modo==22 || $modo==16) // 22 = SEGURO ADUANA
								$diferencia = bcadd($diferencia,$costo_partida,2);
						}
						
						$calIVA = bcmul($costo_partida,$porcentajeIVA,6); // Multiplica
						$calIVA =  bcdiv($calIVA,100,6); // Divide
						$montoIvaAccesorio = bcadd($montoIvaAccesorio,$calIVA,6);
					  }
					
					$montoiva = round(bcadd($montoiva,$montoIvaAccesorio,6),2);	//Suma: $montoiva + $montoIvaAccesorio
					
					$SqlStrDet = "SELECT 
						SUM(a.cantidad * (((b.costo_unitario + b.gasto_unitario) * c.tasa_cambio) * b.porcentaje_grupo) / 100) AS total_tarifa_adv
					FROM
						".$_SESSION['bdEmpresa'].".cp_factura_detalle_importacion b
						INNER JOIN ".$_SESSION['bdEmpresa'].".cp_factura_detalle a ON (b.id_factura_detalle = a.id_factura_detalle)
						INNER JOIN ".$_SESSION['bdEmpresa'].".cp_factura_importacion c ON (a.id_factura = c.id_factura)
					WHERE
						a.id_factura = $id";
						
					$execDet =  EjecutarExec($con,$SqlStrDet) or die($SqlStrDet);
					while($rowDet = ObtenerFetch($execDet)){
						$total_tarifa_adv = $rowDet[0];	
						$Debe = bcsub($Debe,$total_tarifa_adv,2);// RESTA
						$Debe = bcadd($Debe,$costo_partidaSuma,2); //SUMA
						$gastos2=$gastosImportacion-$reverso-$diferencia;
						$gastosImportacion = bcadd($gastosImportacion,$total_tarifa_adv,2);
						$gastosImp = bcadd($gastosImportacion,$otrosCargos,2);
					}
							
					if($Descuento == 0){// Compras sin descuentos
						ingresarRenglon($cuentacontable,$descripcion.' / C. IMPORT.',$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
								/*ingresarEnlacesTerceros($cuentacontable,$fecha,$Debe,$Haber,$id_modelo
								,"an_modelo","id_modelo|des_modelo",$descripcion);*/
						/* Fin Para insertar los terceros */
						
						//*+*+2do renglon gastosImportacion = Otros cargos + Gastos Importacion(Sistema) + ADV // AFECTAN AL INVENTARIO Y SON TODOS O GASTOS(POR REQUERIMIENTO)
						ingresarRenglon($cuentacontable,$descripcion.' / C. IMPORT.',$gastosImp,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros($cuentacontable,$fecha,$gastosImportacion,$Haber,$id_modelo
							,"an_modelo","id_modelo|des_modelo",$descripcion);*/
						/* Fin Para insertar los terceros */
						
						//para el iva.
						ingresarRenglon($cuentaiva,$descripcion.' / C. IMPORT.',$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros($cuentaiva,$fecha,$montoiva,0,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
						
						//para la cxp.
						if ($MontoRetenidoIVA == 0){
							$montoCXP = bcadd($Debe,$montoiva,2);
						}else{
							$montoCXP = bcadd($Debe,$montoiva,2);
							$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2);	 
							ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
							/* Para insertar los terceros */
								/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
								,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
							/* Fin Para insertar los terceros */
						}
						
						if($montoRetencionISLR > 0){
							// vehiculo
							$cuentaMovIslr =  buscarContable(22,4,$sucursal);  
							ingresarRenglon($cuentaMovIslr,"ISLR ".$descripcion,0,$montoRetencionISLR,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							$montoCXP = bcsub($montoCXP,$montoRetencionISLR,2);
						}
						
						$haber2 = $reverso + $montoiva + $total_tarifa_adv;
						
						//CONSULTO EL ADV_VALOREM Y SE LO RESTO AL TOTAL DE LA FACTURA PARA CALCULAR LA CUENTA POR PAGAR(PANAMA)
						$SqlStrDetAdv = "SELECT 
							total_advalorem
						FROM
							".$_SESSION['bdEmpresa'].".cp_factura_importacion a
						WHERE
							a.id_factura = $id";
						$execDetAdv =  EjecutarExec($con,$SqlStrDetAdv) or die($SqlStrDetAdv);
						$rowDetAdv = ObtenerFetch($execDetAdv);
						$adv_general = $rowDetAdv[0];
						
						$advCargos = bcadd($adv_general,$otrosCargos,2);
						
						if ($adv_general > 0){
							$Debe = bcsub($Debe,$adv_general,2); //RESTA
						}
						
						ingresarRenglon($cuentaCXP,$descripcion.' / C. IMPORT.',0,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						/* Para insertar los terceros */
							ingresarEnlacesTerceros($cuentaCXP,$fecha,0,$Debe,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);
						/* Fin Para insertar los terceros */
						
						
						//*+*+5do renglon  Otros cargos + Gastos Importacion(Sistema)
						ingresarRenglon($cuentaImpor,$descripcion.' / C. IMPORT.',0,$gastos2,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros($cuentaImpor,$fecha,0,$gastos2,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
						
						
						//*+*+6do renglon ITBM + ADV
						ingresarRenglon($cuentaRever,$descripcion.' / C. IMPORT.',0,$haber2,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros($cuentaRever,$fecha,0,$haber2,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
						
						//*+*+7mo ADV
						
						if ($adv_general > 0){
							ingresarRenglon($cuentaOtrosCargos,$descripcion.' / C. IMPORT.',0,$advCargos,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						}
						
						if ($diferencia > 0){
							ingresarRenglon($cuentaDif,$descripcion,0,$diferencia,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
							/* Para insertar los terceros */
								/*ingresarEnlacesTerceros($cuentaDif,$fecha,0,$diferencia,$idproveedor
								,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
							/* Fin Para insertar los terceros */
						}else{
							$diferencia=$diferencia*-1;
							ingresarRenglon($cuentaDif,$descripcion,$diferencia,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
							//NO VA - 28/07/2014
							/* Para insertar los terceros */
								/*ingresarEnlacesTerceros($cuentaDif,$fecha,$diferencia,$Haber,$idproveedor
								,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
							/* Fin Para insertar los terceros */
						}// Fin Compras sin descuentos
						
					}else{// Compras con descuentos
						ingresarRenglon($cuentacontable,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						/* Para insertar los terceros */
						//NO VA - 28/07/2014
							/*ingresarEnlacesTerceros($cuentacontable,$fecha,$Debe,$Haber,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
						//para el iva.
						ingresarRenglon($cuentaiva,$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros($cuentaiva,$fecha,$montoiva,0,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
						$cuentaDescuento = buscarContable(22,2,$sucursal);// 22 Fijos Entradas Vehiculos y 2 es en el detalle de los fijos.
						$montoCXP = bcadd($Debe,$montoiva,2);
						$montoCXP = bcsub($montoCXP,$Descuento,2);
						
						if ($MontoRetenidoIVA != 0){	   
							$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2);	
							ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
							/* Para insertar los terceros */
								/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
								,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
							/* Fin Para insertar los terceros */
						}
						
						if($montoRetencionISLR > 0){
							// vehiculo
							$cuentaMovIslr =  buscarContable(22,4,$sucursal);  
							ingresarRenglon($cuentaMovIslr,"ISLR ".$descripcion,0,$montoRetencionISLR,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							$montoCXP = bcsub($montoCXP,$montoRetencionISLR,2);
						}
												
						//para el  descuento.
						ingresarRenglon($cuentaDescuento,$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros($cuentaDescuento,$fecha,0,$Descuento,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
						//para la cxp.
						ingresarRenglon($cuentaCXP,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						/* Para insertar los terceros */
							ingresarEnlacesTerceros($cuentaCXP,$fecha,0,$montoCXP,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);
						/* Fin Para insertar los terceros */
					}//Fin Compras con descuentos 
				}//if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0)
			}//else if($idModoCompra == 2){
		}//while ($row = ObtenerFetch($exec)) {
}

/*F 3*/
//**************************COMPRAS REPUESTOS****************************************
function generarComprasRe($idFactura=0,$Desde="",$Hasta=""){
$con = ConectarBD();

$SqlStr = "update parametros set MensajeRet = ''";
$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
	$SqlStr = "SELECT
		a.id_factura as factura
		,a.fecha_origen as fecha
		,'04' as centrocosto
		,'' as cuentacontable
		,d.descripcion as descripcion
		,sum(b.cantidad*b.precio_unitario) as debe 
		,0 as haber 
		,a.numero_factura_proveedor as documento
		,(SELECT SUM(fact_comp_iva.subtotal_iva)
			FROM ".$_SESSION['bdEmpresa'].".cp_factura_iva fact_comp_iva
			WHERE fact_comp_iva.id_factura = a.id_factura) AS iva
		,(SELECT cuenta FROM detalleintegracion x1 WHERE x1.idencabezado = 23 AND x1.idobjeto = 3 AND x1.sucursal = 1) as cuentaiva
		,a.id_proveedor as proveedor
		,(SELECT concat(nombre) FROM ".$_SESSION['bdEmpresa'].".cp_proveedor x3 WHERE x3.id_proveedor = a.id_proveedor) as Proveedor
		,a.subtotal_descuento 
		,a.subtotal_factura 
		,a.id_empresa 
		,a.id_modo_compra
	FROM
		".$_SESSION['bdEmpresa'].".cp_factura a 
		,".$_SESSION['bdEmpresa'].".cp_factura_detalle b
		,".$_SESSION['bdEmpresa'].".iv_articulos c 
		,".$_SESSION['bdEmpresa'].".iv_tipos_articulos  d 
	WHERE
		c.id_tipo_articulo = d.id_tipo_articulo
		AND a.id_factura = b.id_factura
		AND b.id_articulo = c.id_articulo
		AND a.id_modulo = 0";
	if($idFactura != 0){
		$SqlStr.=" AND a.id_factura= ".$idFactura;
	}else{
		$SqlStr.=" AND a.fecha_origen between '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$SqlStr.=" GROUP BY a.id_factura
	ORDER BY a.fecha_origen,a.id_factura";
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
	
    $fechaAnt = "";
	$icomprobant = 0;
	while ($row = ObtenerFetch($exec)) {
		$id = $row[0];
		$fecha = $row[1];
		$cc = $row[2];
		$cuentacontable = $row[3];
		$sucursal = $row[14];
		if (is_null($cuentacontable) || $cuentacontable == ''){
			$cuentacontable =  buscarContable(4,0,$sucursal);
		}
		$descripcion = $row[4];
		$Debe = $row[5];
		$Haber = $row[6];
		$documento = $row[7];
		$porciva = $row[8]; // iva
		//$cuentaiva = $row[9];
		$idproveedor = $row[10];
		$Desproveedor = $row[11];
		$Descuento = $row[12];
		$MontoFactura = $row[13];
		$sucursal = $row[14];
		//$Debe = bcsub($MontoFactura,$Descuento,2);
		$Debe = $MontoFactura;
		$Debe1 = bcsub($MontoFactura,$Descuento,2); //Resta: $MontoFactura - $Descuento
		$montoiva = $row[8]; // iva
		
		$cuentaiva = buscarcontable(23,3,$sucursal);
		
		$descripcion  = $descripcion . " " .$Desproveedor;
		$ct='01';
		$dt='01';
		$cc='04';
		$tipo = $row[15];
		
		// VERIFICA VALORES DE CONFIGURACION (Consulta el Pais del sistema)
		$queryConfig403 = "SELECT valor FROM ".$_SESSION['bdEmpresa'].".pg_configuracion_empresa config_emp
			INNER JOIN ".$_SESSION['bdEmpresa'].".pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
		WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = 1";
		$rsConfig403 =  EjecutarExec($con,$queryConfig403) or die($queryConfig403." " .mysql_error()); 
		$rowConfig403 = ObtenerFetch($rsConfig403);
		$valor = $rowConfig403['0'];// 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
		
		$sql = sprintf("SELECT SUM(monto_retenido) AS monto
						FROM ".$_SESSION['bdEmpresa'].".te_retencion_cheque
						WHERE id_factura = %s
						AND tipo = %s
						AND tipo_documento = %s
						AND anulado IS NULL",
					$id, // id de la factura
					0, //0 = Factura 1 = Nota de cargo
					2); // 0 = Cheque, 1 = Transferencia, 2 = Sin Documento
		$execISLR =  EjecutarExec($con,$sql);
		$rowISLR = ObtenerFetch($execISLR);
		$montoRetencionISLR = $rowISLR[0];
				
		if ($tipo == 1){ /// 1 = COMPRAS NACIONALES
			if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
				if ($fechaAnt != $fecha){
					$icomprobant++;
					$fechaAnt = $fecha;
				}
				$MontoRetenidoIVA = 0;  
				
				$SqlStr = "SELECT sum(ivaRetenido) FROM ".$_SESSION['bdEmpresa'].".cp_retenciondetalle WHERE idfactura = $id";
				$exec3 = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
				$row = ObtenerFetch($exec3);
				if ($row[0] > 0){
					$MontoRetenidoIVA = $row[0];     	   
					$cuentaRetenido = buscarContable(23,5,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
				}			
				

				
				$cuentaCXP = buscarContable(14,$idproveedor,$sucursal);
				
				$SqlStr = "SELECT
					a.monto
					,a.iva
					,b.id_gasto
					,b.nombre
				FROM
					".$_SESSION['bdEmpresa'].".cp_factura_gasto a
					,".$_SESSION['bdEmpresa'].".pg_gastos b  
				WHERE
					a.id_gasto = b.id_gasto
					AND id_factura = $id";
				$exec5 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
				$montoIvaAccesorio = 0;
				$costo_partidaSuma = 0;
				while($row3 = ObtenerFetch($exec5)){
					$costo_partida = $row3[0];
					$porcentajeIVA = $row3[1];
					if(is_null($porcentajeIVA)){
						$porcentajeIVA = 0;
					}
					
					if ($valor == 2 || $valor == 3) { // 2 = PANAMA ; 3 = PUERTO RICO
						$id_accesorio = $row3[2];
						$nom_accesorio = $row3[3];
					$cuentaAccesorio = buscarContable(85,$id_accesorio,$sucursal);// 85 Repuestos-->Gastos compras
						ingresarRenglon($cuentaAccesorio,$nom_accesorio,$costo_partida,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						// $Debe = bcadd($Debe,$costo_partida,2);
						// if ($aplicaIVA == 1){
					}
					if ($porcentajeIVA != 0){
						$calIVA = bcmul($costo_partida,$porcentajeIVA,6);
						$calIVA =  bcdiv($calIVA,100,6);
						$montoIvaAccesorio = bcadd($montoIvaAccesorio,$calIVA,6);
					}
					//}
					$costo_partidaSuma = bcadd($costo_partidaSuma,$costo_partida,2);
				}
				// para  los accesorios
				// Compras sin descuentos
				
				if ($valor == 1) { // 1 = VENEZUELA
					$Debe = bcadd($Debe,$costo_partidaSuma,2); 
				}
				//$montoiva = round(bcadd($montoiva,$montoIvaAccesorio,6),2);	//SUMA   
				$montoiva = $montoiva;	//SUMA   
				
				if($Descuento == 0){
				// Compras sin descuentos
					ingresarRenglon($cuentacontable,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros($cuentacontable,$fecha,$Debe,$Haber,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					//para el iva.
					//ingresarRenglon($cuentaiva,$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					ingresarRenglon($cuentaiva,$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros($cuentaiva,$fecha,$montoiva,0,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					
					//para la cxp.
					
					
					if ($MontoRetenidoIVA == 0){
						$montoCXP = bcadd($Debe,$montoiva,2);
					}else{
						$montoCXP = bcadd($Debe,$montoiva,2);
						$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2);	 
							
						ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*	ingresarEnlacesTerceros($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
					}
					
					if($montoRetencionISLR > 0){
						// repuestos
						$cuentaMovIslr =  buscarContable(23,4,$sucursal);
						ingresarRenglon($cuentaMovIslr,"ISLR ".$descripcion,0,$montoRetencionISLR,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						$montoCXP = bcsub($montoCXP,$montoRetencionISLR,2);
					}
					
					if ($valor == 2 || $valor == 3) { // 2 = PANAMA ; 3 = PUERTO RICO
						$montoCXP = bcadd($montoCXP,$costo_partidaSuma,2);
					}
					ingresarRenglon($cuentaCXP,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					/* Para insertar los terceros */
						ingresarEnlacesTerceros($cuentaCXP,$fecha,0,$montoCXP,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);
					/* Fin Para insertar los terceros */
			 	//Fin Compras sin descuentos
				}else{// Compras con descuentos
					ingresarRenglon($cuentacontable,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros($cuentacontable,$fecha,$Debe,$Haber,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					//para el iva.
					ingresarRenglon($cuentaiva,$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros($cuentaiva,$fecha,$montoiva,0,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					$cuentaDescuento = buscarContable(23,2,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
					$montoCXP = bcadd($Debe,$montoiva,2);
					$montoCXP = bcsub($montoCXP,$Descuento,2);
					if ($MontoRetenidoIVA != 0){	   
						$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2);	
						ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
					}
					
					if($montoRetencionISLR > 0){
						// repuestos
						$cuentaMovIslr =  buscarContable(23,4,$sucursal);
						ingresarRenglon($cuentaMovIslr,"ISLR ".$descripcion,0,$montoRetencionISLR,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						$montoCXP = bcsub($montoCXP,$montoRetencionISLR,2);
					}
					
					//para el  descuento.
					ingresarRenglon($cuentaDescuento,$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros($cuentaDescuento,$fecha,0,$Descuento,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					//para la cxp.
					
					if ($valor == 2 || $valor == 3) { // 2 = PANAMA ; 3 = PUERTO RICO
						$montoCXP = bcadd($montoCXP,$costo_partidaSuma,2);//suma
					}
					ingresarRenglon($cuentaCXP,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					/* Para insertar los terceros */
						ingresarEnlacesTerceros($cuentaCXP,$fecha,0,$montoCXP,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);
					/* Fin Para insertar los terceros */
				}// Fin Compras con descuentos
			}// if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
		}else{//FIN COMPRA NACIONAL //INICIO COMPRA IMPORTACION
		
			$cuentaImpor = buscarContable(68,5,$sucursal); //GASTOS DE IMPORTACION: PANAMA = 1.1.04.03.002 ; VENEZUELA = 8.2.01.01.054
			$cuentaRever = buscarContable(71,5,$sucursal); //DERECHOS ARANCELARIOS: son los Impuestos que se deben pagar en la aduana en el momento de importar o exportar mercancías. VENEZUELA = 8.2.01.01.057
			$cuentaDif = buscarContable(72,5,$sucursal); //GANANCIA O PERDIDA POR DIFERENCIA ADUANAS // SOLO APLICA PARA VZLA: 9.1.01.01.016
			if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
				if ($fechaAnt != $fecha){
					$icomprobant++;
					$fechaAnt = $fecha;
				}
				$MontoRetenidoIVA = 0;
				
				$SqlStr="SELECT sum(ivaRetenido) FROM ".$_SESSION['bdEmpresa'].".cp_retenciondetalle WHERE idfactura = $id";
				$exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
				$row = ObtenerFetch($exec3);
				if ($row[0] > 0){
					$MontoRetenidoIVA = $row[0];     	   
					$cuentaRetenido = buscarContable(23,5,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
				}
				
				$cuentaCXP = buscarContable(14,$idproveedor,$sucursal);
				
				$SqlStr="SELECT
					a.monto,
					a.iva,
					a.id_modo_gasto,
					a.afecta_documento,
					b.id_gasto
				from
					".$_SESSION['bdEmpresa'].".cp_factura_gasto a
					,".$_SESSION['bdEmpresa'].".pg_gastos b  
				WHERE
					a.id_gasto = b.id_gasto
					AND a.id_factura = $id";
				$exec5 = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
				
				$costo_partidaSuma = 0;
				$gastosImportacion = 0;
				$gastos2 = 0;
				$montoIvaAccesorio = 0;
				$calIVA = 0;
				$reverso = 0;
				$diferencia = 0;
				$usoSistema = 0;
				$seguroAduana = 0;
				while($row3 = ObtenerFetch($exec5)){
					
					$costo_partida = $row3[0];
					$porcentajeIVA = $row3[1];
					$modoGasto = $row3[2];
					$afectaDoc = $row3[3];
					$modo = $row3[4];
					
								
					// VERIFICA VALORES DE CONFIGURACION (Consulta el Pais del sistema)
					$queryConfig403 = "SELECT valor FROM ".$_SESSION['bdEmpresa'].".pg_configuracion_empresa config_emp
						INNER JOIN ".$_SESSION['bdEmpresa'].".pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
					WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = 1";
					$rsConfig403 =  EjecutarExec($con,$queryConfig403) or die($queryConfig403." " .mysql_error()); 
					$rowConfig403 = ObtenerFetch($rsConfig403);
					$valor = $rowConfig403['0'];// 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
					
					if($modoGasto==1 && $afectaDoc==1)
						$costo_partidaSuma = bcadd($costo_partidaSuma,$costo_partida,2);
					
					if($modoGasto<>1 || $afectaDoc<>1)
						$gastosImportacion = bcadd($gastosImportacion,$costo_partida,2);
					
					if ($valor == 2 || $valor == 3) { // CONFIGURACIÓN PARA PANAMA Y PUERTO RICO
						if($modo==12 && $afectaDoc==1)
							$usoSistema = $row3[0];
							
						if($modo==22 && $afectaDoc==0)
							$seguroAduana = $row3[0];
					}
					
					if ($valor == 1) { // CONFIGURACIÓN PARA VENEZUELA
						if($modo==12 || $modo==13 || $modo==17) //12 = USO DEL SISTEMA
							$reverso = bcadd($reverso,$costo_partida,2);
							
						if($modo==21 || $modo==22 || $modo==16) // 22 = SEGURO ADUANA
							$diferencia = bcadd($diferencia,$costo_partida,2);
					}	
					
					$calIVA = bcmul($costo_partida,$porcentajeIVA,6); // Multiplica
					$calIVA =  bcdiv($calIVA,100,6); // Divide
					$montoIvaAccesorio = bcadd($montoIvaAccesorio,$calIVA,6);
				  }
				
				$montoiva = round(bcadd($montoiva,$montoIvaAccesorio,6),2);	//Suma: $montoiva + $montoIvaAccesorio
				
				$SqlStrDet = "SELECT 
					SUM(a.cantidad * (((b.costo_unitario + b.gasto_unitario) * c.tasa_cambio) * b.porcentaje_grupo) / 100) AS total_tarifa_adv
				FROM
					".$_SESSION['bdEmpresa'].".cp_factura_detalle_importacion b
					INNER JOIN ".$_SESSION['bdEmpresa'].".cp_factura_detalle a ON (b.id_factura_detalle = a.id_factura_detalle)
					INNER JOIN ".$_SESSION['bdEmpresa'].".cp_factura_importacion c ON (a.id_factura = c.id_factura)
				WHERE
					a.id_factura = $id";
				$execDet =  EjecutarExec($con,$SqlStrDet) or die($SqlStrDet);
				while($rowDet = ObtenerFetch($execDet)){
					$total_tarifa_adv = $rowDet[0];	
					$Debe = bcsub($Debe,$total_tarifa_adv,2);// RESTA
					$Debe = bcadd($Debe,$costo_partidaSuma,2); //SUMA
					$gastos2=$gastosImportacion-$reverso-$diferencia;
					$gastosImportacion = bcadd($gastosImportacion,$total_tarifa_adv,2);
				}
				
				if($Descuento == 0){// Compras sin descuentos
					ingresarRenglon($cuentacontable,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros($cuentacontable,$fecha,$Debe,$Haber,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					
					if ($valor == 2 || $valor == 3) { // CONFIGURACIÓN PARA PANAMA Y PUERTO RICO
						$gastosImportacion = $total_tarifa_adv + $usoSistema + $seguroAduana;
					}
					//*+*+2do renglon gastosImportacion = Otros cargos + Gastos Importacion(Sistema) + ADV // AFECTAN AL INVENTARIO Y SON TODOS O GASTOS(POR REQUERIMIENTO)

					ingresarRenglon($cuentacontable,$descripcion,$gastosImportacion,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros($cuentacontable,$fecha,$gastosImportacion,$Haber,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					
					//para el iva.
					ingresarRenglon($cuentaiva,$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros($cuentaiva,$fecha,$montoiva,0,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					
					//para la cxp.
					if ($MontoRetenidoIVA == 0){
						$montoCXP = bcadd($Debe,$montoiva,2);
					}else{
						$montoCXP = bcadd($Debe,$montoiva,2);
						$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2);	 
						ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
					}
					
					if($montoRetencionISLR > 0){
						// repuestos
						$cuentaMovIslr =  buscarContable(23,4,$sucursal);
						ingresarRenglon($cuentaMovIslr,"ISLR ".$descripcion,0,$montoRetencionISLR,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						$montoCXP = bcsub($montoCXP,$montoRetencionISLR,2);
					}
					
					$haber2 = $reverso + $montoiva + $total_tarifa_adv;
					
					ingresarRenglon($cuentaCXP,$descripcion,0,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					/* Para insertar los terceros */
						ingresarEnlacesTerceros($cuentaCXP,$fecha,0,$Debe,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);
					/* Fin Para insertar los terceros */
					
					if ($valor == 2 || $valor == 3) { // CONFIGURACIÓN PARA PANAMA Y PUERTO RICO
						$gastos2 = $seguroAduana;
					}
					//*+*+5do renglon  Otros cargos + Gastos Importacion(Sistema)
					ingresarRenglon($cuentaImpor,$descripcion,0,$gastos2,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros($cuentaImpor,$fecha,0,$gastos2,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					
					if ($haber2 > 0){
					
						if ($valor == 2 || $valor == 3) { // CONFIGURACIÓN PARA PANAMA Y PUERTO RICO
							$haber2 = $montoiva;
							$haber3 = $total_tarifa_adv + $usoSistema;
						}
						//*+*+6do renglon ITBM + ADV
						ingresarRenglon($cuentaRever,$descripcion,0,$haber2,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros($cuentaRever,$fecha,0,$haber2,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
					
						if ($valor == 2 || $valor == 3) { // CONFIGURACIÓN PARA PANAMA Y PUERTO RICO
							//*+*+6do renglon ITBM + ADV
							ingresarRenglon($cuentaRever,$descripcion,0,$haber3,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
							/* Para insertar los terceros */
								/*ingresarEnlacesTerceros($cuentaRever,$fecha,0,$haber2,$idproveedor
								,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
							/* Fin Para insertar los terceros */
						}	
					
					}
					
					if ($diferencia > 0){
						ingresarRenglon($cuentaDif,$descripcion,0,$diferencia,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros($cuentaDif,$fecha,0,$diferencia,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
					}else{
						$diferencia=$diferencia*-1;
						ingresarRenglon($cuentaDif,$descripcion,$diferencia,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros($cuentaDif,$fecha,$diferencia,$Haber,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
					}// Fin Compras sin descuentos
					
				}else{// Compras con descuentos
					ingresarRenglon($cuentacontable,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					/* Para insertar los terceros */
					//NO VA - 28/07/2014
						/*ingresarEnlacesTerceros($cuentacontable,$fecha,$Debe,$Haber,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					//para el iva.
					ingresarRenglon($cuentaiva,$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros($cuentaiva,$fecha,$montoiva,0,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					$cuentaDescuento = buscarContable(23,2,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
					$montoCXP = bcadd($Debe,$montoiva,2);
					$montoCXP = bcsub($montoCXP,$Descuento,2);
					
					if ($MontoRetenidoIVA != 0){	   
						$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2);	
						ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
					}
					
					if($montoRetencionISLR > 0){
						// repuestos
						$cuentaMovIslr =  buscarContable(23,4,$sucursal);
						ingresarRenglon($cuentaMovIslr,"ISLR ".$descripcion,0,$montoRetencionISLR,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						$montoCXP = bcsub($montoCXP,$montoRetencionISLR,2);
					}
					
					//para el  descuento.
					ingresarRenglon($cuentaDescuento,$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros($cuentaDescuento,$fecha,0,$Descuento,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					//para la cxp.
					ingresarRenglon($cuentaCXP,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					/* Para insertar los terceros */
						ingresarEnlacesTerceros($cuentaCXP,$fecha,0,$montoCXP,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);
					/* Fin Para insertar los terceros */
				}//Fin Compras con descuentos 
			}
		}//FIN COMPRA INTERNACIONAL
	}
}

/*F 4*/
//**************************COMPRAS SERVICIO*****************************************
function generarComprasSe($idFactura=0,$Desde="",$Hasta=""){
	$con = ConectarBD();
	$SqlStr = "update parametros set MensajeRet = ''";
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
	$SqlStr = "SELECT
		a.id_factura AS factura
		,a.fecha_origen AS fecha
		,'03' AS centrocosto
		,(SELECT cuentageneral FROM encabezadointegracion e WHERE e.id = 5) AS cuentacontable
		,'SERVICIOS' AS descripcion
		,b.base_imponible AS debe 
		,0 AS haber 
		,a.numero_factura_proveedor AS documento
		,b.subtotal_iva AS iva 
		,(SELECT cuenta FROM detalleintegracion x1 WHERE x1.idencabezado = 24 AND x1.idobjeto = 3 AND x1.sucursal = 1) AS cuentaiva
		,a.id_proveedor AS proveedor
		,(SELECT concat(nombre) FROM ".$_SESSION['bdEmpresa'].".cp_proveedor x3 WHERE x3.id_proveedor = a.id_proveedor) AS Proveedor
		,a.subtotal_descuento
		,a.id_empresa
		,(SELECT sa_tipo_orden.id_filtro_orden FROM ".$_SESSION['bdEmpresa'].".sa_orden_tot LEFT JOIN ".$_SESSION['bdEmpresa'].".sa_orden orden ON sa_orden_tot.id_orden_servicio = orden.id_orden LEFT JOIN ".$_SESSION['bdEmpresa'].".sa_tipo_orden ON sa_tipo_orden.id_tipo_orden = orden.id_tipo_orden WHERE sa_orden_tot.id_factura = a.id_factura) AS id_filtro_orden
		FROM
		".$_SESSION['bdEmpresa'].".cp_factura a 
		,".$_SESSION['bdEmpresa'].".cp_factura_iva b
		WHERE
		a.id_factura = b.id_factura
		AND a.id_modulo = 1";
		if($idFactura != 0){
			$SqlStr.=" AND a.id_factura = ".$idFactura;
		}else{
			$SqlStr.=" AND a.fecha_origen between '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
		}
		$SqlStr.=" GROUP BY a.id_factura
		ORDER BY a.fecha_origen, a.id_factura";
	
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__);
	
	$fechaAnt = "";
	$icomprobant = 0;
	while ($row = ObtenerFetch($exec)) {
		$id = $row[0];
		$fecha = $row[1];
		$cc = $row[2];
		$cuentacontable = $row[3];
		$descripcion = $row[4];
		$Debe = $row[5];
		$Haber = $row[6];
		$documento = $row[7];
		$montoiva = $row[8];
		//$cuentaiva = $row[9];
		$idproveedor = $row[10];
		$Desproveedor = $row[11];
		$Descuento = $row[12];
		$sucursal = $row[13];
		$tipo_orden = $row[14];
		
		if ($tipo_orden == 9){
			$cuentacontable = buscarContable(5,9,$sucursal);
		}else if($tipo_orden == 10){
			$cuentacontable = buscarContable(5,10,$sucursal);
		}else{
			$cuentacontable = buscarContable(5,$tipo_orden,$sucursal);
		}
		
		if (is_null($cuentacontable)){
			$cuentacontable =  buscarContable(5,0,$sucursal);
		}
		
		$descripcion  = $descripcion . " " .$Desproveedor;
		
		$cuentaiva = buscarContable(24,3,$sucursal);
		
		$ct='01';
		$dt='01';
		$cc='03';
		
		$sql = sprintf("SELECT SUM(monto_retenido) AS monto
						FROM ".$_SESSION['bdEmpresa'].".te_retencion_cheque
						WHERE id_factura = %s
						AND tipo = %s
						AND tipo_documento = %s
						AND anulado IS NULL",
					$id, // id de la factura
					0, //0 = Factura 1 = Nota de cargo
					2); // 0 = Cheque, 1 = Transferencia, 2 = Sin Documento
		$execISLR =  EjecutarExec($con,$sql);
		$rowISLR = ObtenerFetch($execISLR);
		$montoRetencionISLR = $rowISLR[0];
		
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			if ($fechaAnt != $fecha){
				$icomprobant++;
				$fechaAnt = $fecha;
			}
			$MontoRetenidoIVA = 0;  
			$SqlStr = "SELECT sum(ivaRetenido) FROM ".$_SESSION['bdEmpresa'].".cp_retenciondetalle WHERE idfactura = $id";
			$exec3 = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__);
			$row = ObtenerFetch($exec3);
			if ($row[0] > 0){
				$MontoRetenidoIVA = $row[0];     	   
				$cuentaRetenido = buscarContable(24,5,$sucursal);// 24 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
			}
			
			$cuentaCXP = buscarContable(15,$idproveedor,$sucursal);	   
						
			if($Descuento == 0){
				// Compras sin descuentos
				ingresarRenglon($cuentacontable,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				//NO VA - 28/07/2014
				/* Para insertar los terceros */
				/*ingresarEnlacesTerceros($cuentacontable,$fecha,$Debe,$Haber,$idproveedor
				,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
				/* Fin Para insertar los terceros */
				
				//para el iva.
				ingresarRenglon($cuentaiva,$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				//NO VA - 28/07/2014
				/* Para insertar los terceros */
				/*ingresarEnlacesTerceros($cuentaiva,$fecha,$montoiva,0,$idproveedor
				,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
				/* Fin Para insertar los terceros */
				
				//para la cxp.
				if ($MontoRetenidoIVA == 0){
					$montoCXP = bcadd($Debe,$montoiva,2);
				}else{
					$montoCXP = bcadd($Debe,$montoiva,2);
					$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2);	 
					ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
					/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
					,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */					
				}
				
				if($montoRetencionISLR > 0){
					// servicios
					$cuentaMovIslr =  buscarContable(24,4,$sucursal);
					ingresarRenglon($cuentaMovIslr,"ISLR ".$descripcion,0,$montoRetencionISLR,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					$montoCXP = bcsub($montoCXP,$montoRetencionISLR,2);
				}				
				
				ingresarRenglon($cuentaCXP,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				
				/* Para insertar los terceros */
				ingresarEnlacesTerceros($cuentaCXP,$fecha,0,$montoCXP,$idproveedor
				,"cp_proveedor","id_proveedor|nombre",$descripcion);
				/* Fin Para insertar los terceros */
				//   Fin Compras sin descuentos
			}else{
				// Comprascon descuentos
				ingresarRenglon($cuentacontable,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				/* Para insertar los terceros */
				//NO VA - 28/07/2014
				/* ingresarEnlacesTerceros($cuentacontable,$fecha,$Debe,$Haber,$idproveedor
				,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
				/* Fin Para insertar los terceros */
				//para el iva.
				ingresarRenglon($cuentaiva,$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				/* Para insertar los terceros */
				//NO VA - 28/07/2014
				/*ingresarEnlacesTerceros($cuentaiva,$fecha,$montoiva,0,$idproveedor
				,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
				/* Fin Para insertar los terceros */
				
				$cuentaDescuento = buscarContable(24,2,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
				$montoCXP = bcadd($Debe,$montoiva,2);
				$montoCXP = bcsub($montoCXP,$Descuento,2);	
				if ($MontoRetenidoIVA != 0){	   
					$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2);	
					ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
					/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
					,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
				}
				
				if($montoRetencionISLR > 0){
					// servicios
					$cuentaMovIslr =  buscarContable(24,4,$sucursal);
					ingresarRenglon($cuentaMovIslr,"ISLR ".$descripcion,0,$montoRetencionISLR,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					$montoCXP = bcsub($montoCXP,$montoRetencionISLR,2);
				}
				
				//para el  descuento.
				ingresarRenglon($cuentaDescuento,$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				
				//NO VA - 28/07/2014
				/* Para insertar los terceros */
				/*ingresarEnlacesTerceros($cuentaDescuento,$fecha,0,$Descuento,$idproveedor
				,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
				/* Fin Para insertar los terceros */
				//para la cxp.
				ingresarRenglon($cuentaCXP,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				
				/* Para insertar los terceros */
				ingresarEnlacesTerceros($cuentaCXP,$fecha,0,$montoCXP,$idproveedor
				,"cp_proveedor","id_proveedor|nombre",$descripcion);
				/* Fin Para insertar los terceros */
				//   Fin Compras con descuentos
			}  
		}	
	}
}

/*F 4.1*/
//**************************VENTAS ADMINISTRATIVAS***********************************
function generarVentasAd($idFactura=0,$Desde="",$Hasta=""){
$con = ConectarBD();
	$SqlStr = "SELECT
		a.idfactura
		,a.fecharegistrofactura
		,'01' as centrocosto
		,'' as cuentacontable
		,0 as Debe 
		,(b.cantidad*b.precio_unitario) as haber 
		,a.numerofactura as documento
		,a.calculoIvaFactura 
		,(SELECT cuenta FROM detalleintegracion x1 WHERE x1.idencabezado = 76 AND x1.idobjeto = 15 AND x1.sucursal = 1) as cuentaiva
		,a.idcliente as idcliente
		,(SELECT concat(nombre,' ',apellido) FROM ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 WHERE x3.id = a.idcliente) as descliente
		,a.descuentofactura 
		,(b.cantidad*b.precio_unitario) as costo 
		,a.id_empresa
		,c.descripcion
		,c.id_tipo_concepto
		,b.descripcion
	FROM
		".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
		,".$_SESSION['bdEmpresa'].".cj_cc_factura_detalle_adm b 
		INNER JOIN ".$_SESSION['bdEmpresa'].".cj_cc_concepto c ON c.id_concepto = b.id_concepto
	WHERE
		a.idfactura = b.id_factura
		AND a.iddepartamentoorigenfactura = 3";
		
	if($idFactura != 0){
		$SqlStr.=" AND a.idfactura = ".$idFactura;
	}else{
		$SqlStr.=" AND a.fecharegistrofactura BETWEEN '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$SqlStr.=" ORDER BY a.fecharegistrofactura,a.idfactura";
	$exec =  EjecutarExec($con,$SqlStr) or die("Error Sql: ".mysql_error()." Query: ".$SqlStr." Linea: ".__LINE__." Archivo: ".__FILE__); 
	
	$fechaAnt = "";
	$icomprobant = 0;
	$arrayIdFactura = array();//para evitar ivas duplicados

	while ($row = ObtenerFetch($exec)) {
		$id = $row[0];
		$fecha = $row[1];
		$cc = $row[2];
		$cuentacontable = $row[3];
		$sucursal = $row[13];
		if (is_null($cuentacontable) || $cuentacontable == ''){
			$cuentacontable = buscarContable(1,0,$sucursal);//Inventario Administrativo
		}
		$descripcion = $row[14];
		$Debe = $row[4];
		$Haber = $row[5];
		$documento = $row[6];
		$montoiva = $row[7];
		//$cuentaiva = $row[8];
		$idcliente = $row[9];
		$Descliente = $row[10];
		$Descuento = $row[11];
		$Costo = $row[12];
		$idTipo = $row[15];
		$sucursal = $row[13];
		$descripcion  = $descripcion . " " .$Descliente;
		$descripcionConcepto = $row[16];
		
		$cuentaiva = buscarContable(26,15,$sucursal);
		
		$ct='02';
		$dt='01';
		$cc='01';		
		
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			if ($fechaAnt != $fecha){
				$icomprobant++;
				$fechaAnt = $fecha;
			}
								
			//$cuentaVenta = buscarContable(70,$idTipo,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
			$cuentaVenta = buscarContable(83,$idTipo,$sucursal);
			$MontoRetenidoIVA = 0;  
									
			$SqlStr=" SELECT monto,iva,b.id_gasto,b.nombre 
			FROM ".$_SESSION['bdEmpresa'].".cj_cc_factura_gasto a
				,".$_SESSION['bdEmpresa'].".pg_gastos b  
			WHERE a.id_gasto = b.id_gasto
				AND id_factura = $id";
			$exec5 =  EjecutarExec($con,$SqlStr) or die("Error Sql: ".mysql_error()." Query: ".$SqlStr." Linea: ".__LINE__." Archivo: ".__FILE__);
			
			$montoIvaAccesorio = 0;
			$costo_partidaSuma = 0;
			while($row3 = ObtenerFetch($exec5)){
				$costo_partida = $row3[0];
				$porcentajeIVA = $row3[1];
				if(is_null($porcentajeIVA)){
					$porcentajeIVA = 0;
				}
				$id_accesorio =  $row3[2];
				$nom_accesorio = $row3[3];
				   $cuentaAccesorio = buscarContable(67,$id_accesorio,$sucursal);// 67 Administracion-->Gastos compras
				ingresarRenglon($cuentaAccesorio,$nom_accesorio,0,$costo_partida,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				// $Debe = bcadd($Debe,$costo_partida,2);
				// if ($aplicaIVA == 1){
				if ($porcentajeIVA != 0){
				   $calIVA = bcmul($costo_partida,$porcentajeIVA,6);
				   $calIVA =  bcdiv($calIVA,100,6);
				   $montoIvaAccesorio = bcadd($montoIvaAccesorio,$calIVA,6);
				}
				//}
				$costo_partidaSuma = bcadd($costo_partidaSuma,$costo_partida,2);
			}// para  los accesorios
			
			$Haber = bcadd($Haber,$costo_partidaSuma,2); 
			$montoiva = round(bcadd($montoiva,$montoIvaAccesorio,6),2);	   
			
			$cuentaCXC = buscarContable(70,$idTipo,$sucursal);	   
			
			if($Descuento == 0){// Compras sin descuentos	
				if ($MontoRetenidoIVA == 0){
					if(!in_array($id, $arrayIdFactura)){
						$montoCXC = bcadd($Haber,$montoiva,2);
					}else{
						$montoCXC = $Haber;
					}
				}else{
					$montoCXC = bcadd($Haber,$montoiva,2);
					$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	 
					ingresarRenglon($cuentaRetenido,$descripcion,$MontoRetenidoIVA,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				}// Fin Compras sin descuentos
			}else{ //Con descuento
				$cuentaDescuento = buscarContable(48,$idTipo,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
				$montoCXC = bcadd($Haber,$montoiva,2);
				$montoCXC = bcsub($montoCXC,$Descuento,2);	
				if ($MontoRetenidoIVA != 0){	   
					$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	
					ingresarRenglon($cuentaRetenido,$descripcion,$MontoRetenidoIVA,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				}
				
				ingresarRenglon($cuentaDescuento,$descripcion,$Descuento,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			}// Fin Compras con descuentos
		
//ingresarRenglon($cuentaCXC,$descripcionConcepto.'CXC',$montoCXC,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);		
				ingresarRenglon($cuentaCXC,$descripcion,$montoCXC,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);		

			
			/* Para insertar los terceros */
			ingresarEnlacesTerceros($cuentaCXC,$fecha,$montoCXC,0,$idcliente
			,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
			/* Fin Para insertar los terceros */	
			//para  el costo 
			
			// VERIFICA VALORES DE CONFIGURACION (Consulta el Pais del sistema)
			$queryConfig403 = "SELECT valor FROM ".$_SESSION['bdEmpresa'].".pg_configuracion_empresa config_emp
				INNER JOIN ".$_SESSION['bdEmpresa'].".pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
			WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = 1";
			$rsConfig403 =  EjecutarExec($con,$queryConfig403) or die($queryConfig403." " .mysql_error()); 
			$rowConfig403 = ObtenerFetch($rsConfig403);
			$valor = $rowConfig403['0'];// 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
			
			if ($valor == 1) { // CONFIGURACIÓN PARA VENEZUELA
				$impuesto = 'IVA ';
			}
			
			if ($valor == 2) { // CONFIGURACIÓN PARA PANAMA
				$impuesto = 'ITBMS ';
			}
			
			if ( $valor == 3) { // CONFIGURACIÓN PARA  PUERTO RICO
				$impuesto = 'IVU ';
			}
			
			if(!in_array($id, $arrayIdFactura)){
				ingresarRenglon($cuentaiva,$impuesto.' '.$Descliente,0,$montoiva,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				ingresarRenglon($cuentaVenta,$descripcionConcepto,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			}else{
			
				//ingresarRenglon($cuentaVenta,$descripcionConcepto,$Debe,$Haber+$montoiva,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				ingresarRenglon($cuentaVenta,$descripcionConcepto,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			}
			
			$cuentaCosto = buscarContable(32,$idTipo,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
		}//if(buscarDoc($id,$cc)==0){		
		$arrayIdFactura[] = $id;
	}
}

/*F 5*/
//**************************VENTAS REPUESTOS*****************************************
function generarVentasRe($idFactura=0,$Desde="",$Hasta=""){
	$con = ConectarBD();
	$SqlStr = "select a.idfactura
		   ,a.fecharegistrofactura
		   ,'04' as centrocosto
		   ,'' as cuentacontable
		   ,c.descripcion as descripcion
		   ,0 as Debe 
		   ,sum(b.cantidad*b.precio_unitario) as haber 
		   ,a.numerofactura as documento
		   ,a.calculoIvaFactura 
		   ,(select cuenta from detalleintegracion x1 where x1.idencabezado = 26 and x1.idobjeto = 15 and x1.sucursal = 1) as cuentaiva
		   ,a.idcliente as idcliente
		   ,(select concat(nombre,' ',apellido) from ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 where x3.id = a.idcliente) as descliente
		   ,a.descuentofactura 
		   ,sum(b.cantidad*b.costo_compra) as costo 
		   ,c.id_clave_movimiento as clave_movimiento
		   ,a.id_empresa
	from ".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
				,".$_SESSION['bdEmpresa'].".cj_cc_factura_detalle b
				,".$_SESSION['bdEmpresa'].".v_clavemovimiento c 
	where  a.idfactura = b.id_factura
	and a.iddepartamentoorigenfactura = 0
	and c.id_documento = a.idfactura";
	
	if($idFactura != 0){
		$SqlStr.=" and a.idfactura= ".$idFactura;
	}else{
		$SqlStr.=" and a.fecharegistrofactura between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$SqlStr.=" group by a.idfactura,c.id_clave_movimiento
	order by a.fecharegistrofactura,a.idfactura,c.id_clave_movimiento";	
	
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 	
	
	$fechaAnt = "";
	$icomprobant = 0;
	
	while ($row = ObtenerFetch($exec)) {
		$id = $row[0];
		$fecha = $row[1];
		$cc = $row[2];
		$cuentacontable = $row[3];
		$sucursal = $row[15];
			
		if (is_null($cuentacontable) || $cuentacontable == ''){
			$cuentacontable = buscarContable(4,0,$sucursal);
		}
			
		$descripcion = $row[4];
		$Debe = $row[5];
		$Haber = $row[6];
		$documento = $row[7];
		$montoiva = $row[8];
		//$cuentaiva = $row[9];
		$idcliente = $row[10];
		$Descliente = $row[11];
		$Descuento = $row[12];
		$Costo = $row[13];
		$idTipo = $row[14];
		$sucursal = $row[15];
		$descripcion  = $descripcion . " " .$Descliente;
			
		$cuentaiva = buscarContable(26,15,$sucursal);
			
		$ct='02';
		$dt='01';
		$cc='04';			
		
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			if ($fechaAnt != $fecha){
				$icomprobant++;
				$fechaAnt = $fecha;
			}
				
			$cuentaVenta = buscarContable(28,$idTipo,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
			$MontoRetenidoIVA = 0;  
				
			/*$SqlStr="select sum(ivaRetenido) from ".$_SESSION['bdEmpresa'].".cj_cc_retenciondetalle where idfactura = $id";
			$exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
			$row = ObtenerFetch($exec3);
			if ($row[0] > 0){
				$MontoRetenidoIVA = $row[0];     	   
				$cuentaRetenido = buscarContable(26,17,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
			}
				*/
				
    		  	$SqlStr=" select monto,iva,b.id_gasto,b.nombre from ".$_SESSION['bdEmpresa'].".cj_cc_factura_gasto a
						,".$_SESSION['bdEmpresa'].".pg_gastos b  
						where a.id_gasto = b.id_gasto
						and id_factura = $id";

			$exec5 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
			$montoIvaAccesorio = 0;
			$costo_partidaSuma = 0;
			
	        while($row3 = ObtenerFetch($exec5)){
				$costo_partida = $row3[0];
				$porcentajeIVA = $row3[1];

				if(is_null($porcentajeIVA)){
					$porcentajeIVA = 0;
				}
				   
				$id_accesorio =  $row3[2];
				$nom_accesorio = $row3[3];
				   
				$cuentaAccesorio = buscarContable(84,$id_accesorio,$sucursal);// 84 Repuestos-->Gastos compras
				   
			       // ------- prueba costo del flete ya se agrega abajo ----------
			       //ingresarRenglon($cuentaAccesorio,$nom_accesorio,0,$costo_partida,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			       //--------------------------
				  // $Debe = bcadd($Debe,$costo_partida,2);
				// if ($aplicaIVA == 1){
				if ($porcentajeIVA != 0){
					$calIVA = bcmul($costo_partida,$porcentajeIVA,6);
					$calIVA =  bcdiv($calIVA,100,6);
     				$montoIvaAccesorio = bcadd($montoIvaAccesorio,$calIVA,6);
				}
				//}
				$costo_partidaSuma = bcadd($costo_partidaSuma,$costo_partida,2);
			}
       		// para  los accesorios
	   		// Compras sin descuentos
			$Haber = bcadd($Haber,$costo_partidaSuma,2); 		
			$montoiva = round(bcadd($montoiva,$montoIvaAccesorio,6),2);	   
			$cuentaCXC = buscarContable(17,$idcliente,$sucursal);	   
			// Compras sin descuentos				
				
			if($Descuento == 0){
				//para la cxc.
				if ($MontoRetenidoIVA == 0){						
					$montoCXC = bcadd($Haber,$montoiva,2);
				}else{						
					$montoCXC = bcadd($Haber,$montoiva,2);
					$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	 
						
					ingresarRenglon($cuentaRetenido,$descripcion,$MontoRetenidoIVA,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,$MontoRetenidoIVA,0,$idcliente
							,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
						/* Fin Para insertar los terceros */		
				}
										
				//   Fin Compras sin descuentos
			}else{
				$cuentaDescuento = buscarContable(48,$idTipo,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
					
				$montoCXC = bcadd($Haber,$montoiva,2);
				$montoCXC = bcsub($montoCXC,$Descuento,2);										
				
				if ($MontoRetenidoIVA != 0){	   
					$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	
					ingresarRenglon($cuentaRetenido,$descripcion,$MontoRetenidoIVA,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,$MontoRetenidoIVA,0,$idcliente
							,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
						/* Fin Para insertar los terceros */	
				}					
			
					
					//para el  descuento.
				ingresarRenglon($cuentaDescuento,$descripcion,$Descuento,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros($cuentaDescuento,$fecha,$Descuento,0,$idTipo
						,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
					/* Fin Para insertar los terceros */	
					//para la cxp.
					//   Fin Compras con descuentos
			}
				
			ingresarRenglon($cuentaCXC,$descripcion,$montoCXC,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);		
				/* Para insertar los terceros */
			ingresarEnlacesTerceros($cuentaCXC,$fecha,$montoCXC,0,$idcliente
					,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
				/* Fin Para insertar los terceros */	
				//para  el costo 
				
			ingresarRenglon($cuentaVenta,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				//NO VA - 28/07/2014
				/* Para insertar los terceros */
					/*ingresarEnlacesTerceros($cuentaVenta,$fecha,$Debe,$Haber,$idTipo
					,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
				/* Fin Para insertar los terceros */			   
				
				//para el iva.
				ingresarRenglon($cuentaiva,$descripcion,0,$montoiva,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				//NO VA - 28/07/2014
				/* Para insertar los terceros */
					/*ingresarEnlacesTerceros($cuentaiva,$fecha,0,$montoiva,$idcliente
					,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
				/* Fin Para insertar los terceros */
								
			$cuentaCosto = buscarContable(32,$idTipo,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
				
			ingresarRenglon($cuentaCosto,$descripcion,$Costo,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				//NO VA - 28/07/2014
				/* Para insertar los terceros */
					/*ingresarEnlacesTerceros($cuentaCosto,$fecha,$Costo,0,$idTipo
					,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
				/* Fin Para insertar los terceros */	
			ingresarRenglon($cuentacontable,$descripcion,0,$Costo,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				//NO VA - 28/07/2014		  
				/* Para insertar los terceros */
					/*ingresarEnlacesTerceros($cuentacontable,$fecha,0,$Costo,$idTipo
					,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
				/* Fin Para insertar los terceros */
		}//if(buscarDoc($id,$cc)==0){		
	}
}

/*F 6*/
//*****************************VENTAS VEHICULOS*************************************
function generarVentasVe($idFactura=0,$Desde="",$Hasta="",$Nota=0){
	$con = ConectarBD();
	$SqlStr = "SELECT a.idfactura
		,a.fecharegistrofactura 
		,'02' as centrocosto
		,a.numerofactura as documento
		,(SELECT cuenta FROM detalleintegracion x1 WHERE x1.idencabezado = 25 AND x1.idobjeto = 15 AND x1.sucursal = 1) AS cuentaiva
		,a.idcliente as idcliente
		,(SELECT concat(nombre,' ',apellido) FROM ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 WHERE x3.id = a.idcliente) AS descliente
		,a.descuentofactura
		,a.id_empresa
	FROM ".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a 
	WHERE a.iddepartamentoorigenfactura = 2 ";
	if($idFactura != 0){ 
		$SqlStr.=" AND a.idfactura= ".$idFactura;
	}else{
		$SqlStr.=" AND a.fecharegistrofactura BETWEEN '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$SqlStr.=" ORDER BY a.idfactura";
	$execFact =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__);
    
	$fechaAnt = "";
	$icomprobant = 0; 
	
	while ($rowFact = ObtenerFetch($execFact)){
		$idFactura = $rowFact[0];
		$id = $rowFact[0];
		$fecha = $rowFact[1];
		$cc = $rowFact[2];
		$NC="";
									
		if ($Nota==0){
			$id = $rowFact[0];
		}else{
			$id = $Nota; 			
		}
		
		if ($Nota==0){
			$documento = $rowFact[3];
			$ct='02';
			$dt='02';
			$cc='02';	
		}else{
			$NC=" N/C ";
			$ct='02';// venta compra,pago,cobro
			$dt='01';// factura ,recibo, cheque
			$cc='02';
			
			$SqlStr = "SELECT
				numeracion_nota_credito
				,fechaNotaCredito
			FROM ".$_SESSION['bdEmpresa'].".cj_cc_notacredito
			WHERE idNotaCredito = $Nota";
			$execDoc = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
			while ($rowDoc = ObtenerFetch($execDoc)) {
			  $documento = $rowDoc[0];
			  $fecha = $rowDoc[1];
			}
		}		
		//$cuentaiva = $rowFact[4];
		$idcliente = $rowFact[5];
		$Descliente = $rowFact[6];
		$Descuento = $rowFact[7];
		$sucursal = $rowFact[8];
		
		$cuentaiva = buscarContable(25,15,$sucursal);
		
		$cuentaDescuento = buscarContable(25,7,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
		$Costo = 0;
		
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			if ($fechaAnt != $fecha){
				$icomprobant++;
				$fechaAnt = $fecha;
			}
			$MontoRetenidoIVA = 0;  
			/* $SqlStr="select sum(ivaRetenido) from ".$_SESSION['bdEmpresa'].".cj_cc_retenciondetalle where idfactura = $id";
			$exec3 = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
			$row = ObtenerFetch($exec3);
			if ($row[0] > 0){
				$MontoRetenidoIVA = $row[0];     	   
				$cuentaRetenido = buscarContable(25,17,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
			}*/
			
			$SqlStr = "SELECT
				f.id_condicion_unidad AS condicionunidad
				,d.nom_modelo as descripcion
				,0 as Debe 
				,sum(b.precio_unitario) as haber 
				,a.calculoIvaFactura+a.calculoivadelujofactura
				,b.costo_compra as costo 
				,d.id_modelo
				,b.id_unidad_fisica
				,b.iva as porcentaje_iva
				,a.numeroFactura
				,a.id_empresa
				,f.id_unidad_fisica
				,f.serial_carroceria
				,f.costo_trade_in
			FROM
				".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
				,".$_SESSION['bdEmpresa'].".cj_cc_factura_detalle_vehiculo b
				,".$_SESSION['bdEmpresa'].".an_uni_bas c 
				,".$_SESSION['bdEmpresa'].".an_unidad_fisica f
				,".$_SESSION['bdEmpresa'].".an_modelo  d
			WHERE
				a.idfactura = b.id_factura
				AND f.id_uni_bas = c.id_uni_bas
				AND a.iddepartamentoorigenfactura = 2
				AND c.mod_uni_bas = d.id_modelo
				AND f.id_unidad_fisica = b.id_unidad_fisica";
			if($idFactura != 0){
				$SqlStr.=" AND a.idfactura= ".$idFactura;
			}else{
				$SqlStr.=" AND a.fecharegistrofactura BETWEEN '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
			}
			$SqlStr.=" GROUP BY a.idfactura  
			ORDER BY a.fecharegistrofactura,a.idfactura";
			$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
			
			$cuentacontable =  "";
			$cuentaVenta = "";
			$cuentaCosto = "";
			$id_unidad_fisica="";
			$DebeV = 0;
			$HaberV = 0;
			$montoivaV = 0;
			$EntroVehiculo =""; 
			$Costo = 0;
			$montoiva =0;
			
			while ($row = ObtenerFetch($exec)) {
				$EntroVehiculo = "S"; 
				$condicion = $row[0];
				$descripcion = $row[1];
				$DebeV = $row[2];
				$HaberV = $row[3];
				$montoiva = $row[4];
				$CostoV = $row[5];
				$id_modelo = $row[6];
				$id_unidad_fisica = $row[7];
				$porcentajeIVA = $row[8];
				$NroCompra = $row[9];
				$sucursal = $row[10];
				$serial = $row[12];
				$costo_td = $row[13];
				$descripcion = $descripcion . " " .$Descliente. " Nro Compra ".$NroCompra;
				
				if ($row[0] != 1){
					$cuentacontable =  buscarContable(3,$id_modelo,$sucursal);
					
					if($Nota == 0){  
						$cuentaVenta = buscarContable(30,$id_modelo,$sucursal);// para los vehiculos usados
					}else{	 
						$cuentaVenta = buscarContable(52,$id_modelo,$sucursal);// devoluciones vehiculos usados 
					}
					
					$cuentaCosto = buscarContable(34,$id_modelo,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
					$cuentaDescuento = buscarContable(50,$id_modelo,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
				}else{
					$cuentacontable =  buscarContable(2,$id_modelo,$sucursal);
					if($Nota == 0){  
						$cuentaVenta = buscarContable(29,$id_modelo,$sucursal);// para los vehiculos usados
					}else{	 
						$cuentaVenta = buscarContable(51,$id_modelo,$sucursal);// devoluciones vehiculos usados 
					}
					
					$cuentaCosto = buscarContable(33,$id_modelo,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
					$cuentaDescuento = buscarContable(49,$id_modelo,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
				}
			}//while ($row = ObtenerFetch($exec)) {	
		 	
			$Costo = $CostoV - $costo_td;			
	 
			$SqlStr = "select
					  e.id_accesorio as condicionunidad
					 ,e.nom_accesorio as descripcion
					 ,0 as Debe 
					 ,sum(b.precio_unitario) as haber 
					 ,a.calculoIvaFactura+a.calculoivadelujofactura
					 ,sum(b.costo_compra) as costo 
					 ,b.iva as porcentaje_iva
					 ,a.id_empresa
					  from ".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
						  ,".$_SESSION['bdEmpresa'].".cj_cc_factura_detalle_accesorios b
						  ,".$_SESSION['bdEmpresa'].".an_accesorio e
					  where a.idfactura = b.id_factura
						  and a.iddepartamentoorigenfactura = 2
						  and e.id_accesorio = b.id_accesorio";
			$SqlStr.=" and a.idfactura= ".$idFactura;
			if ($EntroVehiculo == "S"){
				$SqlStr.=" group by a.idfactura  
						order by a.fecharegistrofactura,a.idfactura";
			}else{
				$SqlStr.=" group by a.idfactura,e.id_accesorio  
						order by a.fecharegistrofactura,a.idfactura,e.id_accesorio";
			}
			
			
			$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
			$DebeA = 0;
			$HaberA = 0;
			$montoivaA = 0;
			$DebeSuma = 0;
			$HaberSuma = 0;
			
			while ($row = ObtenerFetch($exec)) {
				$id_accesorio = $row[0];
				$DebeA = $row[2];
				$HaberA = $row[3];
				$montoivaA = $row[4];
				$CostoA = $row[5]; //este costo no es el del accesorio como tal
				$sucursal = $row[7];

				if ($EntroVehiculo == ""){
					$descripcion = $row[1]. " " .$Descliente;
					 //$descripcion  = "ACCESORIOS " . " " .$Descliente;
					$cuentacontable =  buscarContable(37,$id_accesorio,$sucursal);
					 //$cuentaVenta = buscarContable(36,$id_accesorio,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
					if($Nota == 0){  
						$cuentaVenta = buscarContable(29,$id_accesorio,$sucursal);// para los vehiculos usados
					}else{	 
						$cuentaVenta = buscarContable(51,$id_accesorio,$sucursal);// devoluciones vehiculos usados 
					}
					 
					$cuentaCosto = buscarContable(38,$id_accesorio,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
					
					$Costo =$CostoA;
					$Debe = $DebeA;
					$Haber = $HaberA;
					$DebeSuma = bcadd($DebeSuma,$DebeA,2);
					$HaberSuma = bcadd($HaberSuma,$HaberA,2);
					$montoiva =$montoivaA;
					  
					 $cuentaCXC = buscarContable(19,$idcliente,$sucursal);	   
					 
				   // Compras sin descuentos
				   if ($Nota ==0){ 
						ingresarRenglon($cuentaVenta,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
						/* Para insertar los terceros */
						/*ingresarEnlacesTerceros($cuentaVenta,$fecha,$Debe,$Haber,$id_accesorio
						,"an_accesorio","id_accesorio|nom_accesorio",$descripcion);*/
						/* Fin Para insertar los terceros */	
					 
				   }else{
						ingresarRenglon($cuentaVenta,$NC.$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
							//NO VA - 28/07/2014
					   /* Para insertar los terceros */
						/*ingresarEnlacesTerceros($cuentaVenta,$fecha,$Haber,$Debe,$id_accesorio
						,"an_accesorio","id_accesorio|nom_accesorio",$descripcion);*/
						/* Fin Para insertar los terceros */	
				   }
				} //if($EntroVehiculo == ""){
		  
			 }//while ($row = ObtenerFetch($exec)) {
							 
			if ($EntroVehiculo == ""){
				$Debe = $DebeSuma;
				$Haber = $HaberSuma;	
			  //para el iva.
				if ($Nota ==0){
					ingresarRenglon($cuentaiva,$descripcion,0,$montoiva,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
					  /* Para insertar los terceros */
						  /* ingresarEnlacesTerceros($cuentaiva,$fecha,0,$montoiva,$idcliente
						   ,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
						/* Fin Para insertar los terceros */	
				}else{
					ingresarRenglon($cuentaiva,$NC.$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
					  /* Para insertar los terceros */
						  /* ingresarEnlacesTerceros($cuentaiva,$fecha,$montoiva,0,$idcliente
						   ,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
						/* Fin Para insertar los terceros */	
				}				 
				
				if($Descuento == 0){
				//para la cxc.
					if ($MontoRetenidoIVA == 0){
						$montoCXC = bcadd($Haber,$montoiva,2);
					}else{
						$montoCXC = bcadd($Haber,$montoiva,2);
						$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	
						
						if ($Nota ==0){	
							ingresarRenglon($cuentaRetenido,$descripcion,$MontoRetenidoIVA,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
									//NO VA - 28/07/2014
									/* Para insertar los terceros */
										/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,$MontoRetenidoIVA,0,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
									/* Fin Para insertar los terceros */										
						}else{
							ingresarRenglon($cuentaRetenido,$NC.$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
									//NO VA - 28/07/2014
									/* Para insertar los terceros */
										/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
									/* Fin Para insertar los terceros */	
						}							   
					}//   Fin Compras sin descuentos
				}else{				 		     
					$montoCXC = bcadd($Haber,$montoiva,2);
					$montoCXC = bcsub($montoCXC,$Descuento,2);	
					
					if ($MontoRetenidoIVA != 0){	   
						$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);
						if ($Nota ==0){							   
							ingresarRenglon($cuentaRetenido,$descripcion,$MontoRetenidoIVA,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
									//NO VA - 28/07/2014
								  /* Para insertar los terceros */
										/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,$MontoRetenidoIVA,0,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
								 /* Fin Para insertar los terceros */	
						}else{
							ingresarRenglon($cuentaRetenido,$NC.$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
									//NO VA - 28/07/2014
								  /* Para insertar los terceros */
										/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
								  /* Fin Para insertar los terceros */	
								  
						}							
					}
					
					//para el  descuento.
					if ($Nota ==0){	
						ingresarRenglon($cuentaDescuento,$descripcion,$Descuento,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
								//NO VA - 28/07/2014
								/* Para insertar los terceros */
									/* ingresarEnlacesTerceros($cuentaDescuento,$fecha,$Descuento,0,$idTipo
									 ,"an_modelo","id_modelo|nom_modelo",$descripcion);*/
								/* Fin Para insertar los terceros */	
					}else{
						ingresarRenglon($cuentaDescuento,$NC.$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
								//NO VA - 28/07/2014
								/* Para insertar los terceros */
									 /*ingresarEnlacesTerceros($cuentaDescuento,$fecha,0,$Descuento,$idTipo
									 ,"an_modelo","id_modelo|nom_modelo",$descripcion);*/
								/* Fin Para insertar los terceros */	
					}
						//para la cxp.
							//   Fin Compras con descuentos
				}
				
				if ($Nota ==0){	
					ingresarRenglon($cuentaCXC,$descripcion,$montoCXC,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);		
						/* Para insertar los terceros */
						ingresarEnlacesTerceros($cuentaCXC,$fecha,$montoCXC,0,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
							/* Fin Para insertar los terceros */
						
					  //ingresarRenglon($cuentaCosto,$descripcion,$Costo,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					  //ingresarRenglon($cuentacontable,$descripcion,0,$Costo,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);		  						
				}else{
					ingresarRenglon($cuentaCXC,$NC.$descripcion,0,$montoCXC,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);		
							  /* Para insertar los terceros */
							ingresarEnlacesTerceros($cuentaCXC,$fecha,0,$montoCXC,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
								  /* Fin Para insertar los terceros */
					  //ingresarRenglon($cuentaCosto,$NC.$descripcion,0,$Costo,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					  //ingresarRenglon($cuentacontable,$NC.$descripcion,$Costo,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);		  						
				}					
			}// fin compras con descuentos
		 
			if ($EntroVehiculo == "S"){
				$Debe = bcadd($DebeA,$DebeV,2); 				  
					
				// VERIFICA VALORES DE CONFIGURACION (Consulta el Pais del sistema)
				$queryConfig403 = "SELECT valor FROM ".$_SESSION['bdEmpresa'].".pg_configuracion_empresa config_emp
						INNER JOIN ".$_SESSION['bdEmpresa'].".pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
					WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = 1";
				
				$rsConfig403 =  EjecutarExec($con,$queryConfig403) or die($queryConfig403." " .mysql_error()); 
				
				$rowConfig403 = ObtenerFetch($rsConfig403);
				$valor = $rowConfig403['0'];// 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
				
				if ($valor == 1 || $valor == 2) { //1 = Venezuela, 2 = Panama			  
					$Haber = bcadd($HaberA,$HaberV,2);
				}
					
				if ($valor == 3) { //1 = Venezuela, 2 = Panama			  
					$Haber = $HaberV;
				}
					
				  //$montoiva = bcadd($montoivaA,$montoivaV,2);16-04-2011
					
				  $cuentaCXC = buscarContable(19,$idcliente,$sucursal);	   
				   // Compras sin descuentos
				if ($Nota ==0){	
					ingresarRenglon($cuentaVenta,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
							 /* Para insertar los terceros */
								/*ingresarEnlacesTerceros($cuentaVenta,$fecha,$Debe,$Haber,$id_accesorio
								,"an_accesorio","id_accesorio|nom_accesorio",$descripcion);*/
							/* Fin Para insertar los terceros */	
						   
					//para el iva.
					ingresarRenglon($cuentaiva,$descripcion,0,$montoiva,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
					   /* Para insertar los terceros */
						  /* ingresarEnlacesTerceros($cuentaiva,$fecha,0,$montoiva,$idcliente
						   ,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
						/* Fin Para insertar los terceros */	
				   
				 }else{
					 ingresarRenglon($cuentaVenta,$NC.$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
						/* Para insertar los terceros */
						/*ingresarEnlacesTerceros($cuentaVenta,$fecha,$Haber,$Debe,$id_accesorio
						,"an_accesorio","id_accesorio|nom_accesorio",$descripcion);*/
						/* Fin Para insertar los terceros */	
					//para el iva.
					ingresarRenglon($cuentaiva,$NC.$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
					/* Para insertar los terceros */
						   /*ingresarEnlacesTerceros($cuentaiva,$fecha,$montoiva,0,$idcliente
						   ,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
						/* Fin Para insertar los terceros */	
				}
								 
				if($Descuento == 0){
				  //para la cxc.
					if ($MontoRetenidoIVA == 0){
						$montoCXC = bcadd($Haber,$montoiva,2);
					}else{
						$montoCXC = bcadd($Haber,$montoiva,2);
						$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	 
						
						if ($Nota ==0){		
							ingresarRenglon($cuentaRetenido,$descripcion,$MontoRetenidoIVA,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
									//NO VA - 28/07/2014
									/* Para insertar los terceros */
										/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,$MontoRetenidoIVA,0,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
									/* Fin Para insertar los terceros */
								
						}else{
							ingresarRenglon($cuentaRetenido,$NC.$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
									//NO VA - 28/07/2014
									/* Para insertar los terceros */
									/*	ingresarEnlacesTerceros($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
									/* Fin Para insertar los terceros */
						}		
					}//   Fin Compras sin descuentos
				}else{				 		     
					$montoCXC = bcadd($Haber,$montoiva,2);
					$montoCXC = bcsub($montoCXC,$Descuento,2);	
					    	
					if ($MontoRetenidoIVA != 0){	   
						$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	
						
						if ($Nota ==0){		 
							ingresarRenglon($cuentaRetenido,$descripcion,$MontoRetenidoIVA,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
									//NO VA - 28/07/2014
									/* Para insertar los terceros */
										/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,$MontoRetenidoIVA,0,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
									/* Fin Para insertar los terceros */	
								  
						}else{
							ingresarRenglon($cuentaRetenido,$NC.$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
										//NO VA - 28/07/2014
										/* Para insertar los terceros */
										/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
										/* Fin Para insertar los terceros */
						}							  
					}
						   //para el  descuento.
					if ($Nota ==0){		 
						ingresarRenglon($cuentaDescuento,$descripcion,$Descuento,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
									//NO VA - 28/07/2014
								  /* Para insertar los terceros */
										/*ingresarEnlacesTerceros($cuentaDescuento,$fecha,$Descuento,0,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
									/* Fin Para insertar los terceros */	
								  
					}else{
						ingresarRenglon($cuentaDescuento,$NC.$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
									//NO VA - 28/07/2014
									/* Para insertar los terceros */
										/*ingresarEnlacesTerceros($cuentaDescuento,$fecha,0,$Descuento,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
									/* Fin Para insertar los terceros */	
								  
					}							
							//para la cxp.
							//   Fin Compras con descuentos
				} 
				
				if ($Nota ==0){		 
					ingresarRenglon($cuentaCXC,$descripcion,$montoCXC,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);		
									/* Para insertar los terceros */
										ingresarEnlacesTerceros($cuentaCXC,$fecha,$montoCXC,0,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
									/* Fin Para insertar los terceros */
							
				}else{
					ingresarRenglon($cuentaCXC,$NC.$descripcion,0,$montoCXC,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);		
									/* Para insertar los terceros */
						ingresarEnlacesTerceros($cuentaCXC,$fecha,0,$montoCXC,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
									/* Fin Para insertar los terceros */
				}						
						//para  el costo 
			}	 
						// para  los accesorios	  
			if ($id_unidad_fisica!=""){
				$SqlStr=" select a.id_accesorio,a.costo_partida,b.iva_accesorio,b.nom_accesorio
						  from ".$_SESSION['bdEmpresa'].".an_partida a
						  ,".$_SESSION['bdEmpresa'].".an_accesorio b
						  ,".$_SESSION['bdEmpresa'].".an_unidad_fisica c
						  where a.id_accesorio = b.id_accesorio
						  and c.id_unidad_fisica = a.id_unidad_fisica 
						  and c.id_unidad_fisica = $id_unidad_fisica and tipo_partida= 'COMPRA'";
				
				$exec5 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
				
						  $montoIvaAccesorio = 0;
						  $costo_partidaSuma = 0;
				          while($row3 = ObtenerFetch($exec5)){
						       $id_accesorio = $row3[0];
							   $costo_partida = $row3[1];
							   $aplicaIVA = $row3[2];
							   $nom_accesorio = $row3[3];
							 //  $cuentaAccesorio =buscarContable(21,$id_accesorio,$sucursal);// 21 es compras adminstriativas en el encabezado .
							   $cuentaAccesorio = $cuentacontable;
						      // ingresarRenglon($cuentaAccesorio,$nom_accesorio,$costo_partida,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D');
							  if ($aplicaIVA == 1){
							   		   $calIVA = bcmul($costo_partida,$porcentajeIVA,2);
									   $calIVA =  bcdiv($calIVA,100,2);
									   $montoIvaAccesorio = bcadd($montoIvaAccesorio,$calIVA,2);
							  }
							   $costo_partidaSuma = bcadd($costo_partidaSuma,$costo_partida,2);
						  }
					// para  los accesorios
						$Costo = bcadd($Costo,$costo_partidaSuma,2); 
						$descripcion = $row[1]. " ". $Descliente. " Nro Venta: ". $documento;
					if ($Nota ==0){		 	
						ingresarRenglon($cuentaCosto,$descripcion,$Costo,0,$NroCompra,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
							//NO VA - 28/07/2014
						    	/* Para insertar los terceros */
								/*ingresarEnlacesTerceros($cuentaCosto,$fecha,$Costo,0,$id_accesorio
								,"an_accesorio","id_accesorio|nom_accesorio",$descripcion);*/
								/* Fin Para insertar los terceros */
						ingresarRenglon($cuentacontable,$descripcion,0,$Costo,$NroCompra,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);	
							//NO VA - 28/07/2014	  						
							/* Para insertar los terceros */
								/*ingresarEnlacesTerceros($cuentacontable,$fecha,0,$Costo,$id_accesorio
								,"an_accesorio","id_accesorio|nom_accesorio",$descripcion);*/
								/* Fin Para insertar los terceros */
					}else{
						ingresarRenglon($cuentaCosto,$NC.$descripcion,0,$Costo,$NroCompra,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
							//NO VA - 28/07/2014
							   /* Para insertar los terceros */
								/*ingresarEnlacesTerceros($cuentaCosto,$fecha,0,$Costo,$id_accesorio
								,"an_accesorio","id_accesorio|nom_accesorio",$descripcion);*/
								/* Fin Para insertar los terceros */
						ingresarRenglon($cuentacontable,$NC.$descripcion,$Costo,0,$NroCompra,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);	
							//NO VA - 28/07/2014	  						
						        /* Para insertar los terceros */
								/*ingresarEnlacesTerceros($cuentacontable,$fecha,$Costo,0,$id_accesorio
								,"an_accesorio","id_accesorio|nom_accesorio",$descripcion);*/
								/* Fin Para insertar los terceros */
					}					
			      }
		}//if(buscarDoc($id,$cc)==0){ 
  }//while $rowFact = ObtenerFetch($execFact)
}

/*F 7*/
//**************************VENTAS SERVICIOS****************************************
function generarVentasSe($idFactura=0,$Desde="",$Hasta="",$Nota=0){
	$con = ConectarBD();
	$SqlStr = "select a.idfactura
		,a.idcliente
		,a.fecharegistrofactura
		,a.descuentofactura  
		 ,a.numerofactura as documento		
		 ,a.calculoIvaFactura+a.calculoivadelujofactura
		 ,a.id_empresa
		 from ".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
		 where a.iddepartamentoorigenfactura = 1";
		 if($idFactura != 0){
		    $SqlStr.=" and a.idfactura= ".$idFactura;
		}else{
		    $SqlStr.=" and a.fecharegistrofactura between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
		}
		
	$execFact =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
	$icomprobant=0;
	$fechaAnt = "";
	
	while ($rowFact = ObtenerFetch($execFact)) {
		$idFactura=$rowFact[0];
		$MontoIVATotal = $rowFact[5];
		$SqlStr ="select count(*) from 
			".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
			,".$_SESSION['bdEmpresa'].".sa_orden b
			,".$_SESSION['bdEmpresa'].".sa_enlace_garantia c
			,".$_SESSION['bdEmpresa'].".sa_det_enlace_garantia d
			,".$_SESSION['bdEmpresa'].".sa_vale_salida e
			where 
			a.numeropedido = b.id_orden
			and c.id_orden = b.id_orden
			and c.id_enlace_garantia =  d.id_enlace_garantia
			and d.id_vale_salida = e.id_vale_salida
			and a.idDepartamentoOrigenFactura= 1
			and a.idfactura =  $idFactura";
		$execCont =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
		$rowCont  =  ObtenerFetch($execCont);
		$Entra = true;
		if($rowCont[0] >= 1){
		  $Entra = false;  
		}

		//if($Entra == true){
		if ($Nota==0){
			$id=$rowFact[0];
		}else{
			//$id =$Nota; //se cambia por la siguiente linea ya que al pasar $Nota, no se esta enviando el ID de la misma: (ver funcion generarNotasVentasSe
			$id =$idFactura; 			
		}
		$idcliente=$rowFact[1];
		$fecha=$rowFact[2];
		$Descuento=$rowFact[3];
		$sucursal=$rowFact[6];
		$NC="";
		
		if ($Nota==0){
			$documento=$rowFact[4];
			$ct='02';
			$dt='02';
			$cc='03';			
		}else{
			$NC=" N/C ";
			$ct='02';
			$dt='01';
			$cc='03';			
			$SqlStr = "select numeracion_nota_credito,fechaNotaCredito
				  from ".$_SESSION['bdEmpresa'].".cj_cc_notacredito where idNotaCredito = $Nota";
			$execDoc =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
			
			while ($rowDoc = ObtenerFetch($execDoc)) {
				$documento=$rowDoc[0];
				$fecha=$rowDoc[1];
					   
			}
		}					
				   
		$cuentaCXC = buscarContable(18,$idcliente,$sucursal);	
//		$cuentaCXC = buscarContable(5,$idcliente,$sucursal);	
		$MontoRetenidoIVA = 0; 
							   
		if ($fechaAnt != $fecha){
		   $icomprobant++;
		   $fechaAnt = $fecha;
		}
	 	
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){	
				 /*  $SqlStr="select sum(ivaRetenido) from ".$_SESSION['bdEmpresa'].".cj_cc_retenciondetalle where idfactura = $idFactura";
				   $exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
				   $row = ObtenerFetch($exec3);
				   if ($row[0] > 0){
					   $MontoRetenidoIVA = $row[0];     	   
					   $cuentaRetenido = buscarContable(27,17,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
				   }*/
				   
			$SqlStr = "select a.idfactura
			       ,a.fecharegistrofactura
			       ,'03' as centrocosto
			       ,'' as cuentacontable
			       ,c.descripcion as descripcion
				   ,0 as Debe 
				   ,sum(b.cantidad*b.precio_unitario) as haber 
				   ,a.numerofactura as documento
				   ,sum(round(((b.cantidad*b.precio_unitario)/100)*b.iva,2))
				   ,(select cuenta from detalleintegracion x1 where x1.idencabezado = 26 and x1.idobjeto = 15 and x1.sucursal = 1) as cuentaiva
				   ,a.idcliente as idcliente
				   ,(select concat(nombre,' ',apellido) from ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 where x3.id = a.idcliente) as descliente
				   ,a.descuentofactura 
				   ,sum(b.cantidad*b.costo_compra) as costo 
				   ,c.id_clave_movimiento as clave_movimiento
				   ,a.id_empresa
					from ".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
						,".$_SESSION['bdEmpresa'].".cj_cc_factura_detalle b
						,".$_SESSION['bdEmpresa'].".v_clavemovimiento c 
					where a.idfactura = b.id_factura
						and a.iddepartamentoorigenfactura = 1
						and c.id_documento = a.idfactura";
						$SqlStr.=" and a.idfactura= ".$idFactura;
						$SqlStr.="  group by a.idfactura,c.id_clave_movimiento
								order by a.fecharegistrofactura,a.idfactura,c.id_clave_movimiento";
								
			$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__);
			
			$montoCXCRepuestos= 0;
			$montoIVARepuestos= 0;
			while ($row = ObtenerFetch($exec)) {
				$id = $row[0];
				$cc = $row[2];
				$cuentacontable = $row[3];
				$sucursal = $row[15];
				if (is_null($cuentacontable) || $cuentacontable == '' ){
				   $cuentacontable =  buscarContable(4,0,$sucursal);
				}
				$descripcion = $row[4];
				$Debe = $row[5];
				$Haber = $row[6];
			  //$documento = $row[7];
				$montoiva = $row[8];
				//$cuentaiva = $row[9];
				$idcliente = $row[10];
				$Descliente = $row[11];
				$Costo = $row[13];
				$idTipo = $row[14];
				$descripcion  = $descripcion . " " .$Descliente;
				
				$cuentaiva = buscarContable(26,15,$sucursal);
							 
				if($Nota == 0){
					if($idTipo <> 27){
				  		$cuentaVenta = buscarContable(28,$idTipo,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
					}else{
						$cuentaVenta = buscarContable(87,3,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
					}
				}else{
					$cuentaVenta = buscarContable(53,$idTipo,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.	
				}									
				// Compras sin descuentos
				if($Nota == 0){					
					ingresarRenglon($cuentaVenta,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
							/* Para insertar los terceros */
								/*ingresarEnlacesTerceros($cuentaVenta,$fecha,$Debe,$Haber,$idTipo
								,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
							/* Fin Para insertar los terceros */	
				}else{
					ingresarRenglon($cuentaVenta,$NC.$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
							/* Para insertar los terceros */
								/*ingresarEnlacesTerceros($cuentaVenta,$fecha,$Haber,$Debe,$idTipo
								,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
							/* Fin Para insertar los terceros */	
				}		
				$montoCXCRepuestos= bcadd($montoCXCRepuestos,$Haber,2);
				$montoIVARepuestos= bcadd($montoIVARepuestos,$montoiva,2);
					
				//para  el costo 
				$cuentaCosto = buscarContable(32,$idTipo,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
				
				if($Nota == 0){	  
					ingresarRenglon($cuentaCosto,$descripcion,$Costo,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
													//NO VA - 28/07/2014
													/* Para insertar los terceros */
													/*ingresarEnlacesTerceros($cuentaCosto,$fecha,$Costo,0,$idTipo
													,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
													/* Fin Para insertar los terceros */
					ingresarRenglon($cuentacontable,$descripcion,0,$Costo,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);		  
													//NO VA - 28/07/2014
													/* Para insertar los terceros */
													/*ingresarEnlacesTerceros($cuentacontable,$fecha,0,$Costo,$idTipo
													,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
													/* Fin Para insertar los terceros */
				}else{
					ingresarRenglon($cuentaCosto,$NC.$descripcion,0,$Costo,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
													//NO VA - 28/07/2014
										  			/* Para insertar los terceros */
													/*ingresarEnlacesTerceros($cuentaCosto,$fecha,0,$Costo,$idTipo
													,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
													/* Fin Para insertar los terceros */
					ingresarRenglon($cuentacontable,$NC.$descripcion,$Costo,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);		  
													//NO VA - 28/07/2014
													/* Para insertar los terceros */
													/*ingresarEnlacesTerceros($cuentacontable,$fecha,$Costo,0,$idTipo
													,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
													/* Fin Para insertar los terceros */
				}
			}
							
			// *******************************************************************************************
			// **********************************Notas Adicionales****************************************
			// *******************************************************************************************
			$SqlStr = "select a.idfactura
			       ,a.fecharegistrofactura
			       ,'03' as centrocosto
			       ,(select cuenta from detalleintegracion x1 where x1.idencabezado = 27 and x1.idobjeto = 40 and x1.sucursal = 1) as cuentacontable
			       ,b.descripcion_nota as descripcion
			       ,0 as Debe 
			       ,sum(b.precio) as haber 
			       ,a.numerofactura as documento
			       ,sum(round(((b.precio)/100)*a.porcentajeivafactura,2))
			       ,(select cuenta from detalleintegracion x1 where x1.idencabezado = 27 and x1.idobjeto = 15 and x1.sucursal = 1) as cuentaiva
			       ,a.idcliente as idcliente
			       ,(select concat(nombre,' ',apellido) from ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 where x3.id = a.idcliente) as descliente
			       ,a.descuentofactura 
			       ,a.id_empresa
					from ".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
						,".$_SESSION['bdEmpresa'].".sa_det_fact_notas b
					where a.idfactura = b.idfactura
						and a.iddepartamentoorigenfactura = 1";
					    $SqlStr.=" and a.idfactura= ".$idFactura;
						$SqlStr.=" group by a.idfactura
						order by a.fecharegistrofactura,a.idfactura";
			$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
			
			$montoCXCNotas= 0;
			$montoIVANotas= 0;
			
			while ($row = ObtenerFetch($exec)) {
				$cuentacontable = $row[3];
				$sucursal = $row[13];
				if (is_null($cuentacontable)){
					$cuentacontable =  buscarContable(4,0,$sucursal);
				}
				
				$descripcion = $row[4];
				$Debe = $row[5];
				$Haber = $row[6];
				//$documento = $row[7];
				$montoiva = $row[8];
				$cuentaiva = $row[9];
				$idcliente = $row[10];
				$Descliente = $row[11];
				$descripcion  = $descripcion . " " .$Descliente;
				$cuentaVenta = buscarContable(27,14,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
				
				if($Nota == 0){		
					ingresarRenglon($cuentaVenta,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
											//NO VA - 28/07/2014
											/* Para insertar los terceros */
												/*ingresarEnlacesTerceros($cuentaVenta,$fecha,$Debe,$Haber,$idTipo
												,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
											/* Fin Para insertar los terceros */			   
				}else{
					ingresarRenglon($cuentaVenta,$NC.$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
											//NO VA - 28/07/2014
											/* Para insertar los terceros */
												/*ingresarEnlacesTerceros($cuentaVenta,$fecha,$Haber,$Debe,$idTipo
												,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
											/* Fin Para insertar los terceros */	
				}									
				$montoCXCNotas= bcadd($montoCXCNotas,$Haber,2);
									
				$montoIVANotas= bcadd($montoiva,$montoIVANotas,2);
			}
							 
			// *******************************************************************************************
			// **********************************TOT*******************************************************
			// ********************************************************************************************
			$SqlStr = "select DISTINCT
					a.idfactura
					,a.fecharegistrofactura
					,'03' as centrocosto
					,(select cuenta from detalleintegracion x1 where x1.idencabezado = 27 and x1.idobjeto = 46 and x1.sucursal = 1) as cuentacontable
					,'TOT ' as descripcion
					,0 as Debe 
					,round(sum(d.monto_subtotal+(d.monto_subtotal*(b.porcentaje_tot/100))),2) as haber 
					,a.numerofactura as documento
					,sum(round(((d.monto_subtotal+(d.monto_subtotal*(b.porcentaje_tot/100)))/100)*a.porcentajeivafactura,2))
					,(select cuenta from detalleintegracion x1 where x1.idencabezado = 27 and x1.idobjeto = 15 and x1.sucursal = 1) as cuentaiva
					,a.idcliente as idcliente
					,(select concat(nombre,' ',apellido) from ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 where x3.id = a.idcliente) as descliente
					,a.descuentofactura
					,a.id_empresa
					,e.id_filtro_orden
					from
						".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
						,".$_SESSION['bdEmpresa'].".sa_det_orden_tot b
						,".$_SESSION['bdEmpresa'].".sa_orden c
						,".$_SESSION['bdEmpresa'].".sa_orden_tot d
						,".$_SESSION['bdEmpresa'].".sa_tipo_orden e
					where
						d.id_orden_servicio = c.id_orden
						and b.id_orden = c.id_orden
						and a.numeropedido = c.id_orden 
						and e.id_tipo_orden = c.id_tipo_orden
						and a.iddepartamentoorigenfactura = 1";
					    $SqlStr.=" and a.idfactura= ".$idFactura;
						$SqlStr.=" group by a.idfactura, b.id_orden_tot
						order by a.fecharegistrofactura,a.idfactura";
			$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
			
			$montoCXCTOT= 0;
			$montoIVATOT= 0;
			
			while ($row = ObtenerFetch($exec)) {
				$cuentacontable = $row[3];
				$sucursal = $row[13];
				if (is_null($cuentacontable)){
					$cuentacontable =  buscarContable(4,0,$sucursal);
				}
				$descripcion = $row[4];
				$Debe = $row[5];
				$Haber = $row[6];
				//$documento = $row[7];
				$montoiva = $row[8];
				$cuentaiva = $row[9];
				$idcliente = $row[10];
				$Descliente = $row[11];
				$descripcion  = $descripcion . " " .$Descliente;
				$idfiltro = $row[14];
				
				$cuentaVenta = buscarContable(27,46,$sucursal);
				
				if(($idfiltro==9)||($idfiltro==10)||($idfiltro==11)){
					if($Nota == 0){	
					if($idfiltro==9){					
						$cuentaBlinAcc = buscarContable(86,9,$sucursal);
						ingresarRenglon($cuentaBlinAcc,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					}elseif($idfiltro==10){
						$cuentaBlinAcc = buscarContable(86,10,$sucursal); 
						ingresarRenglon($cuentaBlinAcc,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					}elseif($idfiltro==11){
						$cuentaBlinAcc = buscarContable(86,11,$sucursal);
						ingresarRenglon($cuentaBlinAcc,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						}
					}else{
						if($idfiltro==9){					
							$cuentaBlinAcc = buscarContable(86,9,$sucursal);
							ingresarRenglon($cuentaBlinAcc,$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						}elseif($idfiltro==10){
							$cuentaBlinAcc = buscarContable(86,10,$sucursal);
							ingresarRenglon($cuentaBlinAcc,$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						}elseif($idfiltro==11){
							$cuentaBlinAcc = buscarContable(86,11,$sucursal);
							ingresarRenglon($cuentaBlinAcc,$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						}
					}
				}else{				
					if($Nota == 0){		
						ingresarRenglon($cuentaVenta,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
											//NO VA - 28/07/2014
											/* Para insertar los terceros */
												/*ingresarEnlacesTerceros($cuentaVenta,$fecha,$Debe,$Haber,$idcliente
												,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
											/* Fin Para insertar los terceros */		
					}else{
						ingresarRenglon($cuentaVenta,$NC.$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
											//NO VA - 28/07/2014
											/* Para insertar los terceros */
												/*ingresarEnlacesTerceros($cuentaVenta,$fecha,$Haber,$Debe,$idcliente
												,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
											/* Fin Para insertar los terceros */		
					}
				}
				
				$montoCXCTOT= bcadd($montoCXCTOT,$Haber,2);
				$montoIVATOT= bcadd($montoiva,$montoIVATOT,2);																			
			}
							 														 							 
			// *******************************************************************************************
			// **********************************tempario*************************************************
			// *******************************************************************************************
			$SqlStr = "select a.idfactura
				       ,a.fecharegistrofactura
				       ,'03' as centrocosto
				       ,'' as cuentacontable
				       ,'' as descripcion
				       ,0 as Debe 
				       ,sum(b.precio) as haber 
				       ,a.numerofactura as documento
				       ,a.porcentajeivafactura
				       ,(select cuenta from detalleintegracion x1 where x1.idencabezado = 27 and x1.idobjeto = 15 and x1.sucursal = 1) as cuentaiva
				       ,a.idcliente as idcliente
				       ,(select concat(nombre,' ',apellido) from ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 where x3.id = a.idcliente) as descliente
				       ,a.descuentofactura 
				       ,b.id_modo 
					   ,b.operador
					   ,sum(round(round(ut*(b.precio_tempario_tipo_orden)/base_ut_precio,2),2)) as preciomodo2
					   ,a.id_empresa
					    from ".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
								,".$_SESSION['bdEmpresa'].".sa_det_fact_tempario b
						where a.idfactura = b.idfactura
								and a.iddepartamentoorigenfactura = 1";
							    $SqlStr.=" and a.idfactura= ".$idFactura;
								$SqlStr.="	group by a.idfactura,b.operador,b.id_tempario
									order by a.fecharegistrofactura,a.idfactura";
									
			$exec = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
			
			$montoCXCTemp= 0;
			$montoIVATemp= 0;
			
			while ($row = ObtenerFetch($exec)){				
				$sucursal = $row[16];
				if ($row[14]==1){
					$cuentacontable =  buscarContable(27,37,$sucursal);
					$cuentaVenta = buscarContable(27,37,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
					$descripcion = "MANO DE OBRA MECANICA";								 
				}else{
				    $cuentacontable =  buscarContable(27,39,$sucursal);
					$cuentaVenta = buscarContable(27,39,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
					$descripcion = "MANO DE OBRA LATONERIA";
				}
				
				$Debe = $row[5];
				if ($row[13]==1){
					$Haber = $row[15];
				}else{
					$Haber = $row[6];
				}
                              							   
				// $documento = $row[7];
				$montoiva = $Haber * $row[8]/100;
				//$cuentaiva = $row[9];
				$idcliente = $row[10];
				$Descliente = $row[11];
				$descripcion  = $descripcion . " " .$Descliente;
				
				$cuentaiva = buscarContable(27,15,$sucursal);
				
 				//$cuentaVenta = buscarContable(27,40,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
				
				if($Nota == 0){		
					ingresarRenglon($cuentaVenta,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
									//NO VA - 28/07/2014
												/* Para insertar los terceros */
													/*ingresarEnlacesTerceros($cuentaVenta,$fecha,$Debe,$Haber,$idcliente
													,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
												/* Fin Para insertar los terceros */		
				}else{
					ingresarRenglon($cuentaVenta,$NC.$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
											//NO VA - 28/07/2014
												/* Para insertar los terceros */
													/*ingresarEnlacesTerceros($cuentaVenta,$fecha,$Haber,$Debe,$idcliente
													,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
												/* Fin Para insertar los terceros */		
				}
						
				$montoCXCTemp= bcadd($montoCXCTemp,$Haber,2);
				$montoIVATemp= bcadd($montoIVATemp,$montoiva,2);
			}
					
			$montoCXCGen = bcadd($montoCXCTemp,$montoCXCNotas,2);
			$montoCXCGen = bcadd($montoCXCGen,$montoCXCRepuestos,2);										
			$montoIVAGen = bcadd($montoIVATemp,$montoIVANotas,2);
			$montoIVAGen = bcadd($montoIVAGen,$montoIVARepuestos,2);																				
			$montoCXCGen = bcadd($montoCXCGen,$montoCXCTOT,2);
			$montoIVAGen = bcadd($montoIVAGen,$montoIVATOT,2);										
																														
			//$montoiva=$montoIVAGen; // modificado por que el descuento no resta el iva
			$montoiva=$MontoIVATotal;
			$Haber=$montoCXCGen;
							
			//para el iva.
			if($Nota == 0){											
				// VERIFICA VALORES DE CONFIGURACION (Consulta el Pais del sistema)
				$queryConfig403 = "SELECT valor FROM ".$_SESSION['bdEmpresa'].".pg_configuracion_empresa config_emp
											INNER JOIN ".$_SESSION['bdEmpresa'].".pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
								WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = 1";
				$rsConfig403 =  EjecutarExec($con,$queryConfig403) or die($queryConfig403." " .mysql_error()); 
				$rowConfig403 = ObtenerFetch($rsConfig403);
				$valor = $rowConfig403['0'];// 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
										
				if ($valor == 1) { // CONFIGURACIÓN SOLO PARA PUERTO RICO
					$impuesto = 'IVA ';
				}
										
				if ($valor == 2) { // CONFIGURACIÓN SOLO PARA PUERTO RICO
					$impuesto = 'IVA ';									}
									
				if ( $valor == 3) { // CONFIGURACIÓN SOLO PARA PUERTO RICO
					$impuesto = 'IVU ';
				}
				
				ingresarRenglon($cuentaiva,$impuesto.$Descliente,0,$montoiva,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
											//NO VA - 28/07/2014
									   	/* Para insertar los terceros */
											/*ingresarEnlacesTerceros($cuentaiva,$fecha,0,$montoiva,$idcliente
											,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
										/* Fin Para insertar los terceros */		
			}else{
				ingresarRenglon($cuentaiva,$NC." IVA ".$Descliente,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
											//NO VA - 28/07/2014
										/* Para insertar los terceros */
											/*ingresarEnlacesTerceros($cuentaiva,$fecha,$montoiva,0,$idcliente
											,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
										/* Fin Para insertar los terceros */		
			}									
			
			if($Descuento == 0){
				//para la cxc.
				if ($MontoRetenidoIVA == 0){
					$montoCXC = bcadd($Haber,$montoiva,2);
				}else{
					$montoCXC = bcadd($Haber,$montoiva,2);
					$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	 
					
					if($Nota == 0){		
						ingresarRenglon($cuentaRetenido,$descripcion,$MontoRetenidoIVA,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
													//NO VA - 28/07/2014
														 /* Para insertar los terceros */
																/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,$MontoRetenidoIVA,0,$idcliente
																,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
														/* Fin Para insertar los terceros */	
					}else{
						ingresarRenglon($cuentaRetenido,$NC.$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
														//NO VA - 28/07/2014
														  /* Para insertar los terceros */
																/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idcliente
																,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
														 /* Fin Para insertar los terceros */	
														 
					}
				}
				//   Fin Compras sin descuentos
			}else{
				$cuentaDescuento = buscarContable(26,7,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
				$montoCXC = bcadd($Haber,$montoiva,2);
				$montoCXC = bcsub($montoCXC,$Descuento,2);	
				
				if ($MontoRetenidoIVA != 0){	   
					$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	
					
					if($Nota == 0){	
						ingresarRenglon($cuentaRetenido,$descripcion,$MontoRetenidoIVA,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
														//NO VA - 28/07/2014
													    /* Para insertar los terceros */
																/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,$MontoRetenidoIVA,0,$idcliente
																,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
														/* Fin Para insertar los terceros */	
					}else{
						ingresarRenglon($cuentaRetenido,$NC.$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
														//NO VA - 28/07/2014
													   /* Para insertar los terceros */
																/*ingresarEnlacesTerceros($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idcliente
																,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
														/* Fin Para insertar los terceros */
					}
				}
				
				//para el  descuento.
				if($Nota == 0){	
					ingresarRenglon($cuentaDescuento,$descripcion,$Descuento,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
														//NO VA - 28/07/2014
												        /* Para insertar los terceros */
																/*ingresarEnlacesTerceros($cuentaDescuento,$fecha,$Descuento,0,$idcliente
																,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
														/* Fin Para insertar los terceros */
												   
				}else{
					ingresarRenglon($cuentaDescuento,$NC.$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
														//NO VA - 28/07/2014
														/* Para insertar los terceros */
																/*ingresarEnlacesTerceros($cuentaDescuento,$fecha,0,$Descuento,$idcliente
																,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
														/* Fin Para insertar los terceros */
				}												
											    //   Fin Compras con descuentos												
			} 
		
			if($Nota == 0){	
				ingresarRenglon($cuentaCXC," CxC ".$Descliente,$montoCXC,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);									
				/* Para insertar los terceros */
				ingresarEnlacesTerceros($cuentaCXC,$fecha,$montoCXC,0,$idcliente
														,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
														/* Fin Para insertar los terceros */
			}else{
			   ingresarRenglon($cuentaCXC,$NC." CxC ".$Descliente,0,$montoCXC,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);									
  				/* Para insertar los terceros */
																ingresarEnlacesTerceros($cuentaCXC,$fecha,0,$montoCXC,$idcliente
																,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
														/* Fin Para insertar los terceros */  
			}
			//}	
		}
	}//while ($rowFact = ObtenerFetch($exec)) {
}

/*F 8*/
//**************************NOTAS DE CREDITO SERVICIOS******************************
function generarNotasVentasSe($idNota,$Desde="",$Hasta=""){
		$con = ConectarBD();
		if($idNota != 0){
				$SqlStr = "select idNotaCredito,numeracion_nota_credito,fechaNotaCredito,idDocumento  
					from ".$_SESSION['bdEmpresa'].".cj_cc_notacredito where idNotaCredito = $idNota and tipodocumento = 'FA' and iddepartamentonotacredito=1";
        }else{
				$SqlStr = "select idNotaCredito,numeracion_nota_credito,fechaNotaCredito,idDocumento  
					from ".$_SESSION['bdEmpresa'].".cj_cc_notacredito where fechaNotaCredito between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."' and tipodocumento = 'FA' and iddepartamentonotacredito=1";
        }
		
		$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 

		 while ($row = ObtenerFetch($exec)) {
				 $idNotaCredito = $row[0]; 
				 $numeracion_nota_credito= $row[1];
				 $fechaNotaCredito= $row[2];
				 $idDocumento= $row[3];				 
				 generarVentasSe($idDocumento,$xFechaD,$xFechaH,$idNotaCredito);
		 }
}

/*F 9*/
//**************************NOTAS DE CREDITO VEHICULOS******************************
function generarNotasVentasVe($idNota,$Desde="",$Hasta=""){
		$con = ConectarBD();
		if($idNota != 0){
				$SqlStr = "select idNotaCredito,numeracion_nota_credito,fechaNotaCredito,idDocumento  
					from ".$_SESSION['bdEmpresa'].".cj_cc_notacredito where idNotaCredito = $idNota and tipodocumento = 'FA' and iddepartamentonotacredito=2";
        }else{
				$SqlStr = "select idNotaCredito,numeracion_nota_credito,fechaNotaCredito,idDocumento  
					from ".$_SESSION['bdEmpresa'].".cj_cc_notacredito where fechaNotaCredito between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."' and tipodocumento = 'FA' and iddepartamentonotacredito=2";
        }		
		
		$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
		 while ($row = ObtenerFetch($exec)) {
				 $idNotaCredito = $row[0]; 
				 $numeracion_nota_credito= $row[1];
				 $fechaNotaCredito= $row[2];
				 $idDocumento= $row[3];  
				 generarVentasVe($idDocumento,$xFechaD,$xFechaH,$idNotaCredito);
		 }
}

/*F 10*/
//**************************NOTAS DE CREDITO REPUESTOS******************************
function generarNotasRe($idFactura=0,$Desde="",$Hasta=""){
$con = ConectarBD();
$SqlStr = "select a.idnotacredito
	,a.fechanotacredito
      	,'04' as centrocosto
      	,'' as cuentacontable
      	,d.descripcion as descripcion
      	,0 as debe
      	,sum(b.cantidad*b.precio_unitario) as haber 
      	,a.numeracion_nota_credito as documento
      	,sum(round(((b.cantidad*b.precio_unitario)/100)*b.iva,2))
      	,(select cuenta from detalleintegracion x1 where x1.idencabezado = 26 and x1.idobjeto = 15 and x1.sucursal = 1) as cuentaiva
      	,a.idcliente as idcliente
      	,(select concat(nombre,' ',apellido) from ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 where x3.id = a.idcliente) as descliente
      	,a.subtotal_descuento
      	,sum(b.costo_compra*b.cantidad) as costo 
      	,d.id_tipo_articulo 
      	,iddocumento
	,a.id_empresa
 from ".$_SESSION['bdEmpresa'].".cj_cc_notacredito a 
	,".$_SESSION['bdEmpresa'].".cj_cc_nota_credito_detalle b
	,".$_SESSION['bdEmpresa'].".iv_articulos c 
	,".$_SESSION['bdEmpresa'].".iv_tipos_articulos  d 
where a.idnotacredito = b.id_nota_credito
and c.id_tipo_articulo = d.id_tipo_articulo
and b.id_articulo = c.id_articulo
and iddepartamentonotacredito =0";

if($idFactura != 0){
    $SqlStr.=" and a.idnotacredito= ".$idFactura;
}else{
    $SqlStr.=" and a.fechanotacredito between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
}
$SqlStr.=" group by a.idnotacredito,c.id_tipo_articulo
order by a.fechanotacredito,a.idnotacredito,c.id_tipo_articulo";


$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
$fechaAnt = "";
$icomprobant = 0;
	
while ($row = ObtenerFetch($exec)) {
	$id = $row[0];
	$fecha = $row[1];
	$cc = $row[2];
	$sucursal = $row[16];
	$cuentacontable = $row[3]; 
	if (is_null($cuentacontable) || $cuentacontable=='' ){
	   $cuentacontable = buscarContable(4,0,$sucursal); 		
	}

	$descripcion = $row[4];
	$Debe = $row[5];
	$Haber = $row[6];
	$documento = $row[7];
	$montoiva = $row[8];
	$cuentaiva = $row[9];
	$idcliente = $row[10];
	$Descliente = $row[11];
	$Descuento = $row[12];
	$Costo = $row[13];
	$idTipo = $row[14];
	$iddocumento =$row[15];
	$descripcion  = $descripcion . " " .$Descliente;
	$ct='02';
	$dt='01';
	$cc='04'; 
		
	if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
		if($fechaAnt != $fecha){
			$icomprobant++;
			$fechaAnt = $fecha;
		}
		
		$cuentaVenta = buscarContable(53,$idTipo,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
		$MontoRetenidoIVA = 0;  
		
		$SqlStr="select sum(ivaRetenido) from ".$_SESSION['bdEmpresa'].".cj_cc_retenciondetalle where idfactura = $iddocumento";
		$exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
		
		$row = ObtenerFetch($exec3);
		if ($row[0] > 0){
			$MontoRetenidoIVA = $row[0];     	   
			$cuentaRetenido = buscarContable(26,17,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
		}
		
		$cuentaCXC = buscarContable(17,$idcliente,$sucursal);	   
		// Compras sin descuentos
		ingresarRenglon($cuentaVenta,"N/C ".$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
		
		/* Para insertar los terceros */
		ingresarEnlacesTerceros($cuentaVenta,$fecha,$Haber,$Debe,$idcliente
		,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
		/* Fin Para insertar los terceros */
			   
			   
			    //para el iva.
			   ingresarRenglon($cuentaiva,"N/C ".$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
		
			/* Para insertar los terceros */
			ingresarEnlacesTerceros($cuentaiva,$fecha,$montoiva,0,$idcliente
			,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
			/* Fin Para insertar los terceros */
			   

			   			   
		if($Descuento == 0){
		//para la cxc.
			if($MontoRetenidoIVA == 0){
				$montoCXC = bcadd($Haber,$montoiva,2);
			}else{
				$montoCXC = bcadd($Haber,$montoiva,2);
				$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	 
				ingresarRenglon($cuentaRetenido,"N/C ".$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				
				/* Para insertar los terceros */
				ingresarEnlacesTerceros($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idcliente
				,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
				/* Fin Para insertar los terceros */

							
			}
		//   Fin Compras sin descuentos
		}else{
			$cuentaDescuento = buscarContable(26,7,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
			$montoCXC = bcadd($Haber,$montoiva,2);
			$montoCXC = bcsub($montoCXC,$Descuento,2);	
			
			if($MontoRetenidoIVA != 0){	   
				$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	
				ingresarRenglon($cuentaRetenido,"N/C ".$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
   				
				/* Para insertar los terceros */
				ingresarEnlacesTerceros($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idcliente
				,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
				/* Fin Para insertar los terceros */						   
			}
			
			//para el  descuento.
			ingresarRenglon($cuentaDescuento,"N/C ".$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
	   		
		   	/* Para insertar los terceros */
			ingresarEnlacesTerceros($cuentaDescuento,$fecha,0,$Descuento,$idcliente
			,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
			/* Fin Para insertar los terceros */

			//para la cxp.
			//   Fin Compras con descuentos
		}
		
		ingresarRenglon($cuentaCXC,"N/C ".$descripcion,0,$montoCXC,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);		
		
		/* Para insertar los terceros */
		ingresarEnlacesTerceros($cuentaCXC,$fecha,0,$montoCXC,$idcliente
		,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
		/* Fin Para insertar los terceros */
						
		//para  el costo 
		$cuentaCosto = buscarContable(32,$idTipo,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
		ingresarRenglon($cuentaCosto,"N/C ".$descripcion,0,$Costo,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
  		
		/* Para insertar los terceros */
		ingresarEnlacesTerceros($cuentaCXC,$fecha,0,$montoCXC,$idTipo
		,"iv_tipos_articulos","id_tipo_articulo|descripcion",$descripcion);
		/* Fin Para insertar los terceros */
				  
		ingresarRenglon($cuentacontable,"N/C ".$descripcion,$Costo,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);		  

		/* Para insertar los terceros */
		ingresarEnlacesTerceros($cuentacontable,$fecha,$Costo,0,$idTipo
		,"iv_tipos_articulos","id_tipo_articulo|descripcion",$descripcion);
		/* Fin Para insertar los terceros */
				  
		}//if(buscarDoc($id,$cc)==0){		
 	}
}

/*F 10.1*/
//*******************NOTAS DE CREDITO VEHICULOS SIN DETALLE*************************
function generarNotasVeSinDetalle($idFactura=0,$Desde="",$Hasta=""){
$con = ConectarBD();
$SqlStr = "SELECT a.idnotacredito
	,a.fechanotacredito
	,'02' AS centrocosto
	,id_motivo
	,'N/C' AS descripcion
	,0 AS debe
	,subtotalNotaCredito AS haber 
	,a.numeracion_nota_credito AS documento
	,ivaNotaCredito
	,(SELECT cuenta FROM detalleintegracion x1 WHERE x1.idencabezado = 25 AND x1.idobjeto = 15 AND x1.sucursal = 1) AS cuentaiva
	,a.idcliente AS idcliente
	,(SELECT concat_ws(' ',nombre,apellido) FROM ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 WHERE x3.id = a.idcliente) AS descliente
	,a.subtotal_descuento
	,montoNetoNotaCredito AS costo 
	,'04' AS id_tipo_articulo 
	,iddocumento
	,a.id_empresa
FROM
	".$_SESSION['bdEmpresa'].".cj_cc_notacredito a 
WHERE
	iddepartamentonotacredito = 2
	AND idnotacredito NOT IN (SELECT id_nota_credito FROM ".$_SESSION['bdEmpresa'].".cj_cc_nota_credito_detalle)
	AND a.id_motivo IS NOT NULL";
if($idFactura != 0){
    $SqlStr.=" AND a.idnotacredito = ".$idFactura;
}else{
    $SqlStr.=" AND a.fechanotacredito between '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
}
$SqlStr.=" GROUP BY a.idnotacredito
	ORDER BY a.fechanotacredito, a.idnotacredito";
	
$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
	$fechaAnt = "";
	$icomprobant = 0;
	
    while ($row = ObtenerFetch($exec)) {
		$id = $row[0];
		$fecha = $row[1];
		$cc = $row[2];
		$sucursal = $row[16];
		//$id_motivo = $row[3];
		$descripcion = $row[4];
		$Debe = $row[5];
		$Haber = $row[6];
		$documento = $row[7];
		$montoiva = $row[8];
		//$cuentaiva = $row[9];
		$idcliente = $row[10];
		$Descliente = $row[11];
		$Descuento = $row[12];
		$Costo = $row[13];
		$idTipo = $row[14];
		$iddocumento = $row[15];
		$descripcion  = $descripcion . " " .$Descliente;
		
		$cuentaiva = buscarContable(25,15,$sucursal);
		
		$ct='02';
		$dt='01';//FACTURAS
		$cc='02';
	  
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			if ($fechaAnt != $fecha){
				$icomprobant++;
				$fechaAnt = $fecha;
			}
			
			//$cuentaMov =  buscarContable(61,$id_motivo,$sucursal);
			//$cuentaVenta = buscarContable(55,$id_motivo,$sucursal);// 74 Motivos CXC
			
			$MontoRetenidoIVA = 0;  
			$SqlStr="SELECT SUM(ivaRetenido) FROM ".$_SESSION['bdEmpresa'].".cj_cc_retenciondetalle WHERE idfactura = $iddocumento";
			$exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
			$row = ObtenerFetch($exec3);
			
			if ($row[0] > 0){
				$MontoRetenidoIVA = $row[0];     	   
				$cuentaRetenido = buscarContable(25,19,$sucursal);// 25 Fijos Salidas Vehiculos - 19 CXC Vehículos
			}
			
			$cuentaCXC = buscarContable(19,$idcliente,$sucursal); // 75 CXC Vehiculos
			// Compras sin descuentos
			
			//aqui multimotivo
			
			$SqlMulti = "SELECT a.id_motivo
					,a.cantidad
					,a.precio_unitario
					,(a.cantidad*a.precio_unitario) as precio
					FROM ".$_SESSION['bdEmpresa'].".cj_cc_nota_credito_detalle_motivo a
					WHERE a.id_nota_credito = ".$id;
			
			$execM =  EjecutarExec($con,$SqlMulti) or die($SqlMulti." " .mysql_error()); 
			
			$montoH = 0;
			
			while ($rowM = ObtenerFetch($execM)) {
				$id_motivo = $rowM[0];
				$precio = $rowM[3];
				
				$cuentaVenta = buscarContable(74,$id_motivo,$sucursal);// 74 Motivos CXC
				
				$montoH = $montoH+$precio;
				
				//ingresarRenglon($cuentaCXC,$descripcion,$precio,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);	
				ingresarRenglon($cuentaVenta,"N/C ".$descripcion,$precio,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			}
			
			
			
			//ingresarRenglon($cuentaVenta,"N/C ".$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			//para el iva
			ingresarRenglon($cuentaiva,"N/C ".$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			
			if($Descuento == 0){
				//para la cxc
				if ($MontoRetenidoIVA == 0){
					$montoCXC = bcadd($Haber,$montoiva,2);
				}else{
					$montoCXC = bcadd($Haber,$montoiva,2);
					$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	 
					ingresarRenglon($cuentaRetenido,"N/C ".$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				}
			}else{// Fin Compras sin descuentos
				$cuentaDescuento = buscarContable(25,7,$sucursal);// 76 Fijos Salidas Vehiculos
				$montoCXC = bcadd($Haber,$montoiva,2);
				$montoCXC = bcsub($montoCXC,$Descuento,2);	
				
				if ($MontoRetenidoIVA != 0){	   
					$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	
					ingresarRenglon($cuentaRetenido,"N/C ".$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				}
				
				//para el  descuento.
				ingresarRenglon($cuentaDescuento,"N/C ".$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				//para la cxp.
			} // Fin Compras con descuentos
			
			//ingresarRenglon($cuentaCXC,"N/C ".$descripcion,0,$montoCXC,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);		
			ingresarRenglon($cuentaCXC,"N/C ".$descripcion,0,$montoH,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);		
			
		}//if(buscarDoc($id,$cc)==0){		
	}
}

/*F 11*/
//**********************NOTAS DE CREDITO REPUESTOS SIN DETALLE**********************
function generarNotasReSinDetalle($idFactura=0,$Desde="",$Hasta=""){
$con = ConectarBD();
$SqlStr = "SELECT a.idnotacredito
	,a.fechanotacredito
	,'04' AS centrocosto
	,id_motivo
	,'N/C' AS descripcion
	,0 AS debe
	,subtotalNotaCredito AS haber 
	,a.numeracion_nota_credito AS documento
	,ivaNotaCredito
	,(SELECT cuenta FROM detalleintegracion x1 WHERE x1.idencabezado = 26 AND x1.idobjeto = 15 AND x1.sucursal = 1) AS cuentaiva
	,a.idcliente AS idcliente
	,(SELECT concat_ws(' ',nombre,apellido) FROM ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 WHERE x3.id = a.idcliente) AS descliente
	,a.subtotal_descuento
	,montoNetoNotaCredito AS costo 
	,'04' AS id_tipo_articulo 
	,iddocumento
	,a.id_empresa
FROM
	".$_SESSION['bdEmpresa'].".cj_cc_notacredito a 
WHERE
	iddepartamentonotacredito = 0
	AND idnotacredito NOT IN (SELECT id_nota_credito FROM ".$_SESSION['bdEmpresa'].".cj_cc_nota_credito_detalle)
	AND a.id_motivo IS NOT NULL"; 
if($idFactura != 0){
    $SqlStr.=" AND a.idnotacredito = ".$idFactura;
}else{
    $SqlStr.=" AND a.fechanotacredito between '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
}
$SqlStr.=" GROUP BY a.idnotacredito
	ORDER BY a.fechanotacredito, a.idnotacredito";

$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
	$fechaAnt = "";
	$icomprobant = 0;
	
    while ($row = ObtenerFetch($exec)) {
		$id = $row[0];
		$fecha = $row[1];
		$cc = $row[2];
		$sucursal = $row[16];
		$id_motivo = $row[3];
		$descripcion = $row[4];
		$Debe = $row[5];
		$Haber = $row[6];
		$documento = $row[7];
		$montoiva = $row[8];
		//$cuentaiva = $row[9];
		$idcliente = $row[10];
		$Descliente = $row[11];
		$Descuento = $row[12];
		$Costo = $row[13];
		$idTipo = $row[14];
		$iddocumento = $row[15];
		$descripcion  = $descripcion . " " .$Descliente;
		
		$cuentaiva = buscarContable(26,15,$sucursal);
		
		$ct='02';
		$dt='01';
		$cc='04';
	  
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			if ($fechaAnt != $fecha){
				$icomprobant++;
				$fechaAnt = $fecha;
			}
			//$cuentaMov =  buscarContable(61,$id_motivo,$sucursal);
			
			//$cuentaVenta = buscarContable(57,$id_motivo,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
			$MontoRetenidoIVA = 0;  
			$SqlStr="SELECT SUM(ivaRetenido) FROM ".$_SESSION['bdEmpresa'].".cj_cc_retenciondetalle WHERE idfactura = $iddocumento";
			$exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
			$row = ObtenerFetch($exec3);
			if ($row[0] > 0){
				$MontoRetenidoIVA = $row[0];     	   
				$cuentaRetenido = buscarContable(26,17,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
			}
			$cuentaCXC = buscarContable(17,$idcliente,$sucursal);	   
			// Compras sin descuentos
			
			
			//aqui multimotivo
			
			$SqlMulti = "SELECT a.id_motivo
					,a.cantidad
					,a.precio_unitario
					,(a.cantidad*a.precio_unitario) as precio
					FROM ".$_SESSION['bdEmpresa'].".cj_cc_nota_credito_detalle_motivo a
					WHERE a.id_nota_credito = ".$id;
			
			$execM =  EjecutarExec($con,$SqlMulti) or die($SqlMulti." " .mysql_error()); 
			
			$montoH = 0;
			
			while ($rowM = ObtenerFetch($execM)) {
				$id_motivo = $rowM[0];
				$precio = $rowM[3];
				
				$cuentaVenta = buscarContable(74,$id_motivo,$sucursal);// 74 Motivos CXC
				
				$montoH = $montoH+$precio;
				
				//ingresarRenglon($cuentaCXC,$descripcion,$precio,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);	
				ingresarRenglon($cuentaVenta,"N/C ".$descripcion,$precio,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			}
			
			
			//ingresarRenglon($cuentaVenta,"N/C ".$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			//para el iva
			ingresarRenglon($cuentaiva,"N/C ".$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			if($Descuento == 0){
				//para la cxc
				if ($MontoRetenidoIVA == 0){
					$montoCXC = bcadd($Haber,$montoiva,2);
				}else{
					$montoCXC = bcadd($Haber,$montoiva,2);
					$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	 
					ingresarRenglon($cuentaRetenido,"N/C ".$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				}
			}else{// Fin Compras sin descuentos
				$cuentaDescuento = buscarContable(26,7,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
				$montoCXC = bcadd($Haber,$montoiva,2);
				$montoCXC = bcsub($montoCXC,$Descuento,2);	
				if ($MontoRetenidoIVA != 0){	   
					$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	
					ingresarRenglon($cuentaRetenido,"N/C ".$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				}
				//para el  descuento.
				ingresarRenglon($cuentaDescuento,"N/C ".$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				//para la cxp.
			} // Fin Compras con descuentos
			
			//ingresarRenglon($cuentaCXC,"N/C ".$descripcion,0,$montoCXC,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			ingresarRenglon($cuentaCXC,"N/C ".$descripcion,0,$montoH,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);		
			
		}//if(buscarDoc($id,$cc)==0){		
	}
}

//**********************NOTAS DE CREDITO SERVICIOS SIN DETALLE**********************
function generarNotasSeSinDetalle($idFactura=0,$Desde="",$Hasta=""){
$con = ConectarBD();
$SqlStr = "SELECT a.idnotacredito
	,a.fechanotacredito
	,'04' AS centrocosto
	,id_motivo
	,'N/C' AS descripcion
	,0 AS debe
	,subtotalNotaCredito AS haber 
	,a.numeracion_nota_credito AS documento
	,ivaNotaCredito
	,(SELECT cuenta FROM detalleintegracion x1 WHERE x1.idencabezado = 27 AND x1.idobjeto = 15 AND x1.sucursal = 1) AS cuentaiva
	,a.idcliente AS idcliente
	,(SELECT concat_ws(' ',nombre,apellido) FROM ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 WHERE x3.id = a.idcliente) AS descliente
	,a.subtotal_descuento
	,montoNetoNotaCredito AS costo 
	,'04' AS id_tipo_articulo 
	,iddocumento
	,a.id_empresa
FROM
	".$_SESSION['bdEmpresa'].".cj_cc_notacredito a 
WHERE
	iddepartamentonotacredito = 1
	AND idnotacredito NOT IN (SELECT id_nota_credito FROM ".$_SESSION['bdEmpresa'].".cj_cc_nota_credito_detalle)
	AND a.id_motivo IS NOT NULL"; 
if($idFactura != 0){
    $SqlStr.=" AND a.idnotacredito = ".$idFactura;
}else{
    $SqlStr.=" AND a.fechanotacredito between '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
}
$SqlStr.=" GROUP BY a.idnotacredito
	ORDER BY a.fechanotacredito, a.idnotacredito";

$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
	$fechaAnt = "";
	$icomprobant = 0;
	
    while ($row = ObtenerFetch($exec)) {
		$id = $row[0];
		$fecha = $row[1];
		$cc = $row[2];
		$sucursal = $row[16];
		$id_motivo = $row[3];
		$descripcion = $row[4];
		$Debe = $row[5];
		$Haber = $row[6];
		$documento = $row[7];
		$montoiva = $row[8];
		$cuentaiva = $row[9];
		$idcliente = $row[10];
		$Descliente = $row[11];
		$Descuento = $row[12];
		$Costo = $row[13];
		$idTipo = $row[14];
		$iddocumento = $row[15];
		$descripcion  = $descripcion . " " .$Descliente;
		
		$ct='22';
		$dt='01';
		$cc='03';
	  
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			if ($fechaAnt != $fecha){
				$icomprobant++;
				$fechaAnt = $fecha;
			}
			//$cuentaMov =  buscarContable(61,$id_motivo,$sucursal);
			
			//$cuentaVenta = buscarContable(57,$id_motivo,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
			$MontoRetenidoIVA = 0;  
			$SqlStr="SELECT SUM(ivaRetenido) FROM ".$_SESSION['bdEmpresa'].".cj_cc_retenciondetalle WHERE idfactura = $iddocumento";
			$exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
			$row = ObtenerFetch($exec3);
			if ($row[0] > 0){
				$MontoRetenidoIVA = $row[0];     	   
				$cuentaRetenido = buscarContable(27,17,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
			}
			$cuentaCXC = buscarContable(18,$idcliente,$sucursal);	   
			// Compras sin descuentos
						
			//aqui multimotivo
			$SqlMulti = "SELECT a.id_motivo
					,a.cantidad
					,a.precio_unitario
					,(a.cantidad*a.precio_unitario) as precio
					FROM ".$_SESSION['bdEmpresa'].".cj_cc_nota_credito_detalle_motivo a
					WHERE a.id_nota_credito = ".$id;
			
			$execM =  EjecutarExec($con,$SqlMulti) or die($SqlMulti." " .mysql_error()); 
			
			$montoH = 0;
			
			while ($rowM = ObtenerFetch($execM)) {
				$id_motivo = $rowM[0];
				$precio = $rowM[3];
				
				$cuentaVenta = buscarContable(74,$id_motivo,$sucursal);// 74 Motivos CXC
				
				$montoH = $montoH+$precio;
				
				//ingresarRenglon($cuentaCXC,$descripcion,$precio,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);	
				ingresarRenglon($cuentaVenta,"N/C ".$descripcion,$precio,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			}
			
			
			//ingresarRenglon($cuentaVenta,"N/C ".$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			//para el iva
			ingresarRenglon($cuentaiva,"N/C ".$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			if($Descuento == 0){
				//para la cxc
				if ($MontoRetenidoIVA == 0){
					$montoCXC = bcadd($Haber,$montoiva,2);
				}else{
					$montoCXC = bcadd($Haber,$montoiva,2);
					$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	 
					ingresarRenglon($cuentaRetenido,"N/C ".$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				}
			}else{// Fin Compras sin descuentos
				$cuentaDescuento = buscarContable(27,7,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
				$montoCXC = bcadd($Haber,$montoiva,2);
				$montoCXC = bcsub($montoCXC,$Descuento,2);	
				if ($MontoRetenidoIVA != 0){	   
					$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	
					ingresarRenglon($cuentaRetenido,"N/C ".$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				}
				//para el  descuento.
				ingresarRenglon($cuentaDescuento,"N/C ".$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				//para la cxp.
			} // Fin Compras con descuentos
			
			//ingresarRenglon($cuentaCXC,"N/C ".$descripcion,0,$montoCXC,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			ingresarRenglon($cuentaCXC,"N/C ".$descripcion,0,$montoH,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);		
			
		}//if(buscarDoc($id,$cc)==0){		
	}
}

/*F 11.1*/
//*********************NOTAS DE CREDITO ADMINISTRACION SIN DETALLE******************
function generarNotasAdSinDetalle($idFactura=0,$Desde="",$Hasta=""){
$con = ConectarBD();
$SqlStr = "SELECT a.idnotacredito
	,a.fechanotacredito
	,'01' AS centrocosto
	,id_motivo
	,'N/C' AS descripcion
	,0 AS debe
	,subtotalNotaCredito AS haber 
	,a.numeracion_nota_credito AS documento
	,ivaNotaCredito
	,(SELECT cuenta FROM detalleintegracion x1 WHERE x1.idencabezado = 76 AND x1.idobjeto = 15 AND x1.sucursal = 1) AS cuentaiva
	,a.idcliente AS idcliente
	,(SELECT concat_ws(' ',nombre,apellido) FROM ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 WHERE x3.id = a.idcliente) AS descliente
	,a.subtotal_descuento
	,montoNetoNotaCredito AS costo 
	,'04' AS id_tipo_articulo 
	,iddocumento
	,a.id_empresa
FROM
	".$_SESSION['bdEmpresa'].".cj_cc_notacredito a 
WHERE
	iddepartamentonotacredito = 3
	AND idnotacredito NOT IN (SELECT id_nota_credito FROM ".$_SESSION['bdEmpresa'].".cj_cc_nota_credito_detalle)
	AND a.id_motivo IS NOT NULL"; 
if($idFactura != 0){
    $SqlStr.=" AND a.idnotacredito = ".$idFactura;
}else{
    $SqlStr.=" AND a.fechanotacredito between '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
}
$SqlStr.=" GROUP BY a.idnotacredito
	ORDER BY a.fechanotacredito, a.idnotacredito";

$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
	$fechaAnt = "";
	$icomprobant = 0;
	
    while ($row = ObtenerFetch($exec)) {
		$id = $row[0];
		$fecha = $row[1];
		$cc = $row[2];
		$sucursal = $row[16];
		//$id_motivo = $row[3];
		$descripcion = $row[4];
		$Debe = $row[5];
		$Haber = $row[6];
		$documento = $row[7];
		$montoiva = $row[8];
		//$cuentaiva = $row[9];
		$idcliente = $row[10];
		$Descliente = $row[11];
		$Descuento = $row[12];
		$Costo = $row[13];
		$idTipo = $row[14];
		$iddocumento = $row[15];
		$descripcion  = $descripcion . " " .$Descliente;
		
		$cuentaiva = buscarContable(76,15,$sucursal);
		
		$ct='02';
		$dt='01';
		$cc='01';
	  
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			if ($fechaAnt != $fecha){
				$icomprobant++;
				$fechaAnt = $fecha;
			}
			////$cuentaMov =  buscarContable(61,$id_motivo,$sucursal);
			//$cuentaVenta = buscarContable(74,$id_motivo,$sucursal);// 74 Motivos CXC

			
			
			$MontoRetenidoIVA = 0;  
			$SqlStr="SELECT SUM(ivaRetenido) FROM ".$_SESSION['bdEmpresa'].".cj_cc_retenciondetalle WHERE idfactura = $iddocumento";
			$exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
			$row = ObtenerFetch($exec3);
			if ($row[0] > 0){
				$MontoRetenidoIVA = $row[0];     	   
				$cuentaRetenido = buscarContable(76,75,$sucursal);// 76 Fijos Administracion - 75 CXC Administracion
			}
			$cuentaCXC = buscarContable(75,$idcliente,$sucursal); // 75 CXC Administracion
			// Compras sin descuentos
			
						
			//aqui multimotivo
			
			$SqlMulti = "SELECT a.id_motivo
					,a.cantidad
					,a.precio_unitario
					,(a.cantidad*a.precio_unitario) as precio
					FROM ".$_SESSION['bdEmpresa'].".cj_cc_nota_credito_detalle_motivo a
					WHERE a.id_nota_credito = ".$id;
			
			$execM =  EjecutarExec($con,$SqlMulti) or die($SqlMulti." " .mysql_error()); 
			
			$montoH = 0;
			
			while ($rowM = ObtenerFetch($execM)) {
				$id_motivo = $rowM[0];
				$precio = $rowM[3];
				
				$cuentaVenta = buscarContable(74,$id_motivo,$sucursal);// 74 Motivos CXC
				
				$montoH = $montoH+$precio;
				
				//ingresarRenglon($cuentaCXC,$descripcion,$precio,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);	
				ingresarRenglon($cuentaVenta,"N/C ".$descripcion,$precio,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			}
			
			//
			//ingresarRenglon($cuentaVenta,"N/C ".$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			
			
			//para el iva
			ingresarRenglon($cuentaiva,"N/C ".$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			if($Descuento == 0){
				//para la cxc
				if ($MontoRetenidoIVA == 0){
					$montoCXC = bcadd($Haber,$montoiva,2);
				}else{
					$montoCXC = bcadd($Haber,$montoiva,2);
					$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	 
					ingresarRenglon($cuentaRetenido,"N/C ".$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				}
			}else{// Fin Compras sin descuentos
				$cuentaDescuento = buscarContable(76,7,$sucursal);// 76 Fijos Administracion
				$montoCXC = bcadd($Haber,$montoiva,2);
				$montoCXC = bcsub($montoCXC,$Descuento,2);	
				if ($MontoRetenidoIVA != 0){	   
					$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	
					ingresarRenglon($cuentaRetenido,"N/C ".$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				}
				//para el  descuento.
				ingresarRenglon($cuentaDescuento,"N/C ".$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				//para la cxp.
			} // Fin Compras con descuentos
			//ingresarRenglon($cuentaCXC,"N/C ".$descripcion,0,$montoCXC,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);		
			ingresarRenglon($cuentaCXC,"N/C ".$descripcion,0,$montoH,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);		
			
		}//if(buscarDoc($id,$cc)==0){		
	}
}


//*********************NOTAS DE CREDITO ADMINISTRACION CON DETALLE*********30/10/2015 -- LA--*********
function generarNotasAdConDetalle($idFactura=0,$Desde="",$Hasta=""){
$con = ConectarBD();
$SqlStr = "SELECT a.idnotacredito
	,a.fechanotacredito
	,'01' AS centrocosto
	,id_motivo
	,'N/C' AS descripcion
	,0 AS debe
	,subtotalNotaCredito AS haber 
	,a.numeracion_nota_credito AS documento
	,ivaNotaCredito
	,(SELECT cuenta FROM detalleintegracion x1 WHERE x1.idencabezado = 76 AND x1.idobjeto = 15 AND x1.sucursal = 1) AS cuentaiva
	,a.idcliente AS idcliente
	,(SELECT concat_ws(' ',nombre,apellido) FROM ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 WHERE x3.id = a.idcliente) AS descliente
	,a.subtotal_descuento
	,montoNetoNotaCredito AS costo 
	,'01' AS id_tipo_articulo 
	,iddocumento
	,a.id_empresa
FROM
	".$_SESSION['bdEmpresa'].".cj_cc_notacredito a 
	 INNER JOIN ".$_SESSION['bdEmpresa'].".cj_cc_nota_credito_detalle_adm b ON (a.idnotacredito = b.id_nota_credito)

WHERE
	iddepartamentonotacredito = 3
	AND id_nota_credito IN (SELECT id_nota_credito FROM ".$_SESSION['bdEmpresa'].".cj_cc_nota_credito_detalle_adm)
	AND a.id_motivo IS NULL"; 
if($idFactura != 0){
    $SqlStr.=" AND a.idnotacredito = ".$idFactura;
}else{
    $SqlStr.=" AND a.fechanotacredito between '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
}
$SqlStr.=" GROUP BY a.idnotacredito
	ORDER BY a.fechanotacredito, a.idnotacredito";

$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
	$fechaAnt = "";
	$icomprobant = 0;
	
    while ($row = ObtenerFetch($exec)) {
		$id = $row[0];
		$fecha = $row[1];
		$cc = $row[2];
		$sucursal = $row[16];
		$id_motivo = $row[3];
		$descripcion = $row[4];
		$Debe = $row[5];
		$Haber = $row[6];
		$documento = $row[7];
		$montoiva = $row[8];
		$cuentaiva = $row[9];
		$idcliente = $row[10];
		$Descliente = $row[11];
		$Descuento = $row[12];
		$Costo = $row[13];
		$idTipo = $row[14];
		$iddocumento = $row[15];
		$descripcion  = $descripcion . " " .$Descliente;
		
		$ct='02';
		$dt='01';
		$cc='01';
	  
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			if ($fechaAnt != $fecha){
				$icomprobant++;
				$fechaAnt = $fecha;
			}
			//$cuentaMov =  buscarContable(61,$id_motivo,$sucursal);
			$cuentaVenta = buscarContable(83,$idTipo,$sucursal);
			$MontoRetenidoIVA = 0;  
			$SqlStr="SELECT SUM(ivaRetenido) FROM ".$_SESSION['bdEmpresa'].".cj_cc_retenciondetalle WHERE idfactura = $iddocumento";
			$exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
			$row = ObtenerFetch($exec3);
			if ($row[0] > 0){
				$MontoRetenidoIVA = $row[0];     	   
				$cuentaRetenido = buscarContable(76,75,$sucursal);// 76 Fijos Administracion - 75 CXC Administracion
			}
			$cuentaCXC = buscarContable(70,$idTipo,$sucursal);	   
			//var_dump($cuentaVenta);
			// Compras sin descuentos
			ingresarRenglon($cuentaVenta,"N/C ".$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			//para el iva
			ingresarRenglon($cuentaiva,"N/C ".$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			if($Descuento == 0){
				//para la cxc
				if ($MontoRetenidoIVA == 0){
					$montoCXC = bcadd($Haber,$montoiva,2);
				}else{
					$montoCXC = bcadd($Haber,$montoiva,2);
					$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	 
					ingresarRenglon($cuentaRetenido,"N/C ".$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				}
			}else{// Fin Compras sin descuentos
				$cuentaDescuento = buscarContable(76,7,$sucursal);// 76 Fijos Administracion
				$montoCXC = bcadd($Haber,$montoiva,2);
				$montoCXC = bcsub($montoCXC,$Descuento,2);	
				if ($MontoRetenidoIVA != 0){	   
					$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	
					ingresarRenglon($cuentaRetenido,"N/C ".$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				}
				//para el  descuento.
				ingresarRenglon($cuentaDescuento,"N/C ".$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				//para la cxp.
			} // Fin Compras con descuentos
			ingresarRenglon($cuentaCXC,"N/C ".$descripcion,0,$montoCXC,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);		
			
		}//if(buscarDoc($id,$cc)==0){		
	}
}

/*F 12*/
//**************************VALE DE SALIDA SERVICIO*********************************
function generarValeSe($idFactura=0,$Desde="",$Hasta="",$Nota=0){
$con = ConectarBD();
	$SqlStr = "select
		a.id_vale_salida
		,0
		,date(a.fecha_vale)
		,a.descuento  
		,a.numero_vale as documento
		,a.id_empresa		
	from
		".$_SESSION['bdEmpresa'].".sa_vale_salida a";
		
	if($idFactura != 0){
		$SqlStr.=" WHERE a.id_vale_salida = ".$idFactura;
	}else{
		$SqlStr.=" WHERE date(a.fecha_vale) between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$execFact =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
	$icomprobant=0;
	$fechaAnt = "";
		while ($rowFact = ObtenerFetch($execFact)) {
			$idFactura=$rowFact[0];
			if ($Nota==0){
				$id=$rowFact[0];
			}else{
				$id =$Nota; 
			}
			
			$idcliente=$rowFact[1];
			$fecha=$rowFact[2];
			$Descuento=$rowFact[3];
			$sucursal=$rowFact[5];
			$NC="";
			
			if ($Nota==0){
				$documento=$rowFact[4];
				$ct='11';
				$dt='06';
				$cc='03';			
			}else{
				$NC=" N/C ";
				$ct='02';
				$dt='01';
				$cc='03';			
				$SqlStr = "select numeracion_nota_credito
				from ".$_SESSION['bdEmpresa'].".cj_cc_notacredito where idNotaCredito = $Nota";
				$execDoc =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
				while ($rowDoc = ObtenerFetch($execDoc)) {
					$documento=$rowDoc[0];
				}
			}					
				   
		   $cuentaCXC = buscarContable(18,$idcliente,$sucursal);	
		   $MontoRetenidoIVA = 0; 
							   
			if ($fechaAnt != $fecha){
				$icomprobant++;
				$fechaAnt = $fecha;
			}
			
			if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){	
				$MontoRetenidoIVA=0;
				/* $SqlStr="select sum(ivaRetenido) from ".$_SESSION['bdEmpresa'].".cj_cc_retenciondetalle where idfactura = $idFactura";
				$exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
				$row = ObtenerFetch($exec3);
				if ($row[0] > 0){
				$MontoRetenidoIVA = $row[0];     	   
				$cuentaRetenido = buscarContable(27,17,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
				}*/
				   
				$SqlStr = "select 
					a.id_vale_salida
					,a.fecha_vale
					,'03' as centrocosto
					,'' as cuentacontable
					,d.descripcion as descripcion
					,0 as Debe 
					,sum(b.cantidad*b.precio_unitario) as haber 
					,a.numero_vale as documento
					,sum(round(((b.cantidad*b.precio_unitario)/100)*b.iva,2))
					,(select cuenta from detalleintegracion x1 where x1.idencabezado = 27 and x1.idobjeto = 15 and x1.sucursal = 1) as cuentaiva
					,0 as idcliente
					,'' as descliente
					,a.descuento
					,sum(b.cantidad*b.costo) as costo 
					,d.id_tipo_articulo
					,a.id_empresa
				from
					".$_SESSION['bdEmpresa'].".sa_vale_salida a
					,".$_SESSION['bdEmpresa'].".sa_det_vale_salida_articulo b
					,".$_SESSION['bdEmpresa'].".iv_articulos c 
					,".$_SESSION['bdEmpresa'].".iv_tipos_articulos  d 
				where
					c.id_tipo_articulo = d.id_tipo_articulo
					and a.id_vale_salida = b.id_vale_salida
					and b.id_articulo = c.id_articulo";
				$SqlStr.=" and a.id_vale_salida= ".$idFactura;
				$SqlStr.=" group by a.id_vale_salida,c.id_tipo_articulo
				order by a.fecha_vale,a.id_vale_salida,c.id_tipo_articulo";
				
				$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__);

				$montoCXCRepuestos= 0;
				$montoIVARepuestos= 0;

				while ($row = ObtenerFetch($exec)) {
					$id = $row[0];
					$cc = $row[2];
					$cuentacontable = $row[3];
					$sucursal = $row[15];
					if (is_null($cuentacontable) || $cuentacontable == ''){
						$cuentacontable =  buscarContable(4,0,$sucursal);
					}
					$descripcion = $row[4];
					$Debe = $row[5];
					$Haber = $row[6];
					//$documento = $row[7];
					$montoiva = $row[8];
					//$cuentaiva = $row[9];
					$idcliente = $row[10];
					$Descliente = $row[11];
					$Costo = $row[13];
					$idTipo = $row[14];
					$descripcion  = $descripcion . " Vale de Salida " .$Descliente;
					
					$cuentaiva = buscarContable(27,15,$sucursal);
					
					$cuentaVenta = buscarContable(40,$idTipo,$sucursal);// AQUI VA A IR ES CUENTA VALE DE SALIDA REPUESTOS
					// Compras sin descuentos
					if($Nota == 0){
						// ingresarRenglon($cuentaVenta,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					}else{
						// ingresarRenglon($cuentaVenta,$NC.$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					}		
					$montoCXCRepuestos= bcadd($montoCXCRepuestos,$Haber,2);
					$montoIVARepuestos= bcadd($montoIVARepuestos,$montoiva,2);
					
					//para  el costo 
					$cuentaCosto = buscarContable(32,$idTipo,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
					if($Nota == 0){
						ingresarRenglon($cuentaCosto,$descripcion,$Costo,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						ingresarRenglon($cuentacontable,$descripcion,0,$Costo,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					}else{
						ingresarRenglon($cuentaCosto,$NC.$descripcion,0,$Costo,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						ingresarRenglon($cuentacontable,$NC.$descripcion,$Costo,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					}
				}
				// *******************************************************************************************
				// **********************************Notas Adicionales****************************************
				// *******************************************************************************************
				$SqlStr = "select a.id_vale_salida
					,a.fecha_vale
					,'03' as centrocosto
					,(select cuenta from detalleintegracion x1 where x1.idencabezado = 27 and x1.idobjeto = 41 and x1.sucursal = 1) as cuentacontable
					,b.descripcion_nota as descripcion
					,0 as Debe 
					,sum(b.precio) as haber 
					,a.numero_vale as documento
					,sum(round(((b.precio)/100)*a.porcentajeiva,2))
					,(select cuenta from detalleintegracion x1 where x1.idencabezado = 27 and x1.idobjeto = 15 and x1.sucursal = 1) as cuentaiva
					,0 as idcliente
					,'' as descliente
					,a.descuento
					,a.id_empresa
				from
					".$_SESSION['bdEmpresa'].".sa_vale_salida a
					,".$_SESSION['bdEmpresa'].".sa_det_vale_salida_notas b
				where
					a.id_vale_salida = b.id_vale_salida
					and a.id_vale_salida= $idFactura		
				group by a.id_vale_salida
				order by a.fecha_vale,a.id_vale_salida";
				
				$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
				
				$montoCXCNotas= 0;
				$montoIVANotas= 0;
				
				while ($row = ObtenerFetch($exec)) {
					$cuentacontable = $row[3];
					$sucursal = $row[13];
					if (is_null($cuentacontable)){
						$cuentacontable =  buscarContable(4,0,$sucursal);
					}
					$descripcion = $row[4];
					$Debe = $row[5];
					$Haber = $row[6];
					//$documento = $row[7];
					$montoiva = $row[8];
					//$cuentaiva = $row[9];
					$idcliente = $row[10];
					$Descliente = $row[11];
					$descripcion  = $descripcion . " " .$Descliente;
					
					$cuentaiva = buscarContable(27,15,$sucursal);
					
					$cuentaVenta = buscarContable(27,43,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
					if($Nota == 0){		
						//ingresarRenglon($cuentaVenta,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					}else{
						//ingresarRenglon($cuentaVenta,$NC.$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					}									
					$montoCXCNotas= bcadd($montoCXCNotas,$Haber,2);
					$montoIVANotas= bcadd($montoiva,$montoIVANotas,2);
				}
				// *******************************************************************************************
				// **********************************tempario*************************************************
				// *******************************************************************************************
				$SqlStr = "select
					a.id_vale_salida
					,a.fecha_vale
					,'03' as centrocosto
					,'' as cuentacontable
					,'' as descripcion
					,0 as Debe 
					,sum(b.precio) as haber 
					,a.numero_vale as documento
					,sum(round(((b.precio)/100)*a.porcentajeiva,2))
					,(select cuenta from detalleintegracion x1 where x1.idencabezado = 27 and x1.idobjeto = 15 and x1.sucursal = 1) as cuentaiva
					,0 as idcliente
					,'' as descliente
					,a.descuento
					,b.id_modo 
					,b.operador
					,round(sum(ut*precio/base_ut_precio),2) as preciomodo2
					,a.id_empresa
				from
					".$_SESSION['bdEmpresa'].".sa_vale_salida a
					,".$_SESSION['bdEmpresa'].".sa_det_vale_salida_tempario b
				where
					a.id_vale_salida = b.id_vale_salida";
				$SqlStr.=" and a.id_vale_salida= ".$idFactura;
				$SqlStr.="	group by a.id_vale_salida,b.operador
				order by a.fecha_vale,a.id_vale_salida";
				
				$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
				
				$montoCXCTemp= 0;
				$montoIVATemp= 0;
				
				while ($row = ObtenerFetch($exec)){
					$sucursal = $row[16];
					if ($row[14]==1){
						//$cuentacontable =  buscarContable(27,42,$sucursal);ojo colocar
						$cuentaVenta = buscarContable(27,41,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
						$descripcion = "MANO DE OBRA";
					}else{
					// $cuentacontable =  buscarContable(27,44,$sucursal);ojo colocar
						$cuentaVenta = buscarContable(27,42,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
						$descripcion = "LATONERIA";
					}
					$Debe = $row[5];
					if ($row[13]==1){
						$Haber = $row[15];
					}else{
						$Haber = $row[6];
					}
					// $documento = $row[7];
					$montoiva = $row[8];
					//$cuentaiva = $row[9];
					$idcliente = $row[10];
					$Descliente = $row[11];
					$descripcion  = $descripcion . " VALE DE SALIDA ";
					
					$cuentaiva = buscarContable(27,15,$sucursal);
						
					if($Nota == 0){		
						//	ingresarRenglon($cuentaVenta,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					}else{
						//	ingresarRenglon($cuentaVenta,$NC.$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					}
					$montoCXCTemp= bcadd($montoCXCTemp,$Haber,2);
					$montoIVATemp= bcadd($montoIVATemp,$montoiva,2);
				}
				$montoCXCGen = bcadd($montoCXCTemp,$montoCXCNotas,2);
				$montoCXCGen = bcadd($montoCXCGen,$montoCXCRepuestos,2);
				
				$montoIVAGen = bcadd($montoIVATemp,$montoIVANotas,2);
				$montoIVAGen = bcadd($montoIVAGen,$montoIVARepuestos,2);
				
				$montoiva=$montoIVAGen;
				$Haber=$montoCXCGen;
				//para el iva.
				if($Nota == 0){		
					//   ingresarRenglon($cuentaiva," IVA ".$Descliente,0,$montoiva,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				}else{
					//	ingresarRenglon($cuentaiva,$NC." IVA ".$Descliente,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				}									
				if($Descuento == 0){
					//para la cxc.
					if ($MontoRetenidoIVA == 0){
						$montoCXC = bcadd($Haber,$montoiva,2);
					}else{
						$montoCXC = bcadd($Haber,$montoiva,2);
						$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	 
						if($Nota == 0){		
					//ingresarRenglon($cuentaRetenido,$descripcion,$MontoRetenidoIVA,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						}else{
				//ingresarRenglon($cuentaRetenido,$NC.$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						}
					}
				//   Fin Compras sin descuentos
				}else{
					$cuentaDescuento = buscarContable(26,7,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
					$montoCXC = bcadd($Haber,$montoiva,2);
					$montoCXC = bcsub($montoCXC,$Descuento,2);	
					if ($MontoRetenidoIVA != 0){	   
						$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	
						if($Nota == 0){	
			//ingresarRenglon($cuentaRetenido,$descripcion,$MontoRetenidoIVA,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						}else{
	
				//ingresarRenglon($cuentaRetenido,$NC.$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						}
					}
					//para el  descuento.
					if($Nota == 0){	
						// ingresarRenglon($cuentaDescuento,$descripcion,$Descuento,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					}else{
						// ingresarRenglon($cuentaDescuento,$NC.$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					}												
					// Fin Compras con descuentos
				} 
				if($Nota == 0){	
					// ingresarRenglon($cuentaCXC," CxC ".$Descliente,$montoCXC,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);									
				}else{
					// ingresarRenglon($cuentaCXC,$NC." CxC ".$Descliente,0,$montoCXC,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);									
				}
			}//if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
		}//while ($rowFact = ObtenerFetch($exec)) {
}

/*F 13*/
//**************************DEPOSITO DE TESORERIA***********************************
function generarDepositoTe($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
	$ct='18';
	$dt='03';
	$cc='05';
	$SqlStr = "SELECT 
		a.id_deposito
		,a.fecha_registro
		,'05' as centrocosto
		,a.observacion as descripcion
		,monto_total_deposito as monto
		,a.numero_deposito_banco as documento
		,a.id_numero_cuenta
		,a.id_motivo
		,a.origen
		,b.descripcion
		,a.id_empresa
	FROM ".$_SESSION['bdEmpresa'].".te_depositos a
		,".$_SESSION['bdEmpresa'].".te_origen b
	WHERE a.origen = b.id
		and a.origen = 0";
	
	if($idTran != 0){
		$SqlStr.=" AND a.id_deposito = ".$idTran;
	}else{
		$SqlStr.=" AND a.fecha_registro BETWEEN '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$icomprobant = 0;
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());		
	
	while ($row = ObtenerFetch($exec)){	
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto=$row[4];
		$documento = $row[5];
		$id_numero_cuenta = $row[6];
		$id_motivo = $row[7];
		$sucursal = $row[10];
		
		// estoy utilizando las configuraciones de nota de credito de tesoreria
		if(buscarDocTe($documento,$cc,$ct,$dt,$fecha)==0){//23042012	
			//$cuentaMov =  buscarContable(60,$id_motivo,$sucursal);        
			$cuentaBanco =  buscarContable(43,$id_numero_cuenta,$sucursal);
			ingresarRenglon($cuentaBanco,$descripcion,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			//ingresarRenglon($cuentaMov,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			
			
			//multimotivos!!
			$SqlMulti = "SELECT a.id_motivo
					,a.cantidad
					,a.precio_unitario
					,(a.cantidad*a.precio_unitario) as precio
					FROM ".$_SESSION['bdEmpresa'].".te_depositos_detalle_motivo a
					WHERE a.id_deposito = ".$id;
			
			$execM =  EjecutarExec($con,$SqlMulti) or die($SqlMulti." " .mysql_error()); 
			
			$montoH = 0;
			
			while ($rowM = ObtenerFetch($execM)) {
				$id_motivo = $rowM[0];
				$precio = $rowM[3];
				
				$cuentaMov =  buscarContable(60,$id_motivo,$sucursal);
				
				$montoH = $montoH+$precio;
								
				ingresarRenglon($cuentaMov,$descripcion,0,$precio,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			}
			//
			
			
		}
	}
}

/*F 14*/
//***************DEPOSITO DE TESORERIA DE REPUESTOS*********************************
function generarDepositosTeRe($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
	// *******************************************************************************************
	// **********************************Generar Depositos REPUESTOS******************************
	// *******************************************************************************************
	$ct='05';
	$dt='03';
	$cc='05';
	
	$SqlStr = "SELECT
		a.id_deposito
		,a.fecha_registro
		,'05' as centrocosto
		,a.observacion as descripcion
		,monto_total_deposito as monto
		,a.numero_deposito_banco as documento
		,a.id_numero_cuenta
		,a.origen
		,b.descripcion
		,a.id_empresa
	FROM ".$_SESSION['bdEmpresa'].".te_depositos a,".$_SESSION['bdEmpresa'].".te_origen b
	WHERE a.origen = b.id
	AND a.origen = 2";
		
	if($idTran != 0){
		$SqlStr.=" AND a.id_deposito = ".$idTran;
	}else{
		$SqlStr.=" AND a.fecha_registro BETWEEN '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	
	$icomprobant = 0;							
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto=$row[4];
		$documento = $row[5];
		$id_numero_cuenta = $row[6];
		$origen = $row[7];
		$desorigen = $row[8];
		$sucursal = $row[9];
		$cuentaBanco =  buscarContable(43,$id_numero_cuenta,$sucursal);
		$cuentaMov =  buscarContable(10,$origen,$sucursal);
		
		if(buscarDocTe($documento,$cc,$ct,$dt,$fecha)==0){
			ingresarRenglon($cuentaBanco,$descripcion. " ".$desorigen,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			ingresarRenglon($cuentaMov,$descripcion. " ".$desorigen,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
		}
	}
	
	$ct='05';
	$dt='03';
	$cc='05';
	$SqlStr = "SELECT
		max(b.idpago) AS id
		,b.fechapago AS fecha
		,'05' AS centrocosto
		,(SELECT nombreFormaPago FROM ".$_SESSION['bdEmpresa'].".formapagos WHERE idformapago = b.formapago)
		,SUM(b.montopagado) AS monto
		, 0 AS documento
		,b.bancodestino	
		,max(b.idcaja) AS idcaja
		,formapago
		,b.cuentaEmpresa
		,x.id_empresa
		,0 as idf
		,b.id_factura
	FROM
		".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura x
		,".$_SESSION['bdEmpresa'].".sa_iv_pagos b
	WHERE
		b.formapago in(5,6)
		AND b.id_factura = x.idFactura
		AND x.iddepartamentoorigenfactura in(0,1,3)";
	$SqlStr.=" AND b.fechapago between '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	$SqlStr.=" group by fecha,formapago,bancodestino
	union all 
	select max(b.idDetalleAnticipo) as id 
		,b.fechaPagoAnticipo as fecha 
		,'05' as centrocosto 
		,(select nombreFormaPago from ".$_SESSION['bdEmpresa'].".formapagos where aliasformapago = b.tipoPagoDetalleAnticipo)
		,SUM(b.montoDetalleAnticipo) as monto
		, numeroAnticipo as documento
		,b.bancoCompaniaDetalleAnticipo as bancodestino
		,b.idcaja as idcaja
		,c.idformapago as formapago
		,b.numeroCuentaCompania
		,a.id_empresa
		,a.idAnticipo
		,0
	from ".$_SESSION['bdEmpresa'].".cj_cc_anticipo a,
	".$_SESSION['bdEmpresa'].".cj_cc_detalleanticipo b
	,".$_SESSION['bdEmpresa'].".formapagos c
	where a.idanticipo= b.idanticipo 
	and a.idDepartamento in(0,1,3)";
	$SqlStr.=" and b.fechaPagoAnticipo between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
	$SqlStr.=" and c.idformapago in(5,6,7) 
	and c.aliasformapago = b.tipopagodetalleanticipo
	group by fecha,formapago,bancodestino
	order by fecha";
		
	//echo("<br><br>".$SqlStr);
			
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
	$montoCa = 0;
	$diaAnt = "";
	
	while ($row = ObtenerFetch($exec)) {
		$sucursal = $row[10];
		
		//id del anticipo:
		$idDoc = $row[11];
		//
		
		$icomprobant++;
		  
		if ($diaAnt == ""){
			$diaAnt = $row[1];
		}
		
		if ($diaAnt != $row[1]){
			//$cuentaCaja =  buscarContable(10,0,$sucursal);
			$cuentaCaja =  buscarContable(10,2,$sucursal);
			if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
				ingresarRenglon($cuentaCaja,"DEPOSITOS EN CAJAS ",0,$montoCa,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);					
				$diaAnt = $row[1];
				$montoCa = 0;
			}
		 }
		 
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto=$row[4];
		$documento = $row[5];
		$id_numero_cuenta = $row[6];
		$numCuenta = $row[9];
		
		$SqlStr = " select id from ".$_SESSION['bdEmpresa'].".v_bancoscuentas where idbanco=$id_numero_cuenta";
		$execBan =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
		
		$rowBan = ObtenerFetch($execBan);							  
		
		$documento ='DEP-RE'.date("dmY",strtotime($fecha));
		$cuentaBanco =  buscarContable(43,$rowBan[0],$sucursal);
		
		if($idDoc<>0){//se agregó este filtro ya que luego de la "Re-Reconversión" realizada en Autoclub, en la transaccion ·Deposito/Repuesto Servicio" habian montos grandes y negativos en el haber ....		
		//se busca número de factura... asociado al anticipo
		$SqlIdFact = "SELECT id_factura, numeroDocumento
					FROM ".$_SESSION['bdEmpresa'].".sa_iv_pagos
					WHERE numeroDocumento = $idDoc";
					
		//echo("<br>execIF".$SqlIdFact);			
		
		$execIF = EjecutarExec($con,$SqlIdFact) or die($SqlIdFact." " .mysql_error()); 
		
		while($rowIF = ObtenerFetch($execIF)){
			$idFact = $rowIF[0];
			
			$SqlBusAnul = "SELECT idPago, id_factura, numeroFactura, fechaPago, numeroDocumento, montoPagado, estatus, fecha_anulado
						FROM ".$_SESSION['bdEmpresa'].".sa_iv_pagos
						WHERE id_factura = $idFact
							AND fecha_anulado <> 'NULL'";
							
			//echo("<br>execBA".$SqlBusAnul);
			
			$execBA = EjecutarExec($con,$SqlBusAnul) or die($SqlBusAnul." " .mysql_error()); 				

			while($rowBA = ObtenerFetch($execBA)){
				$idPago = $rowBA[0];
				$numeroDocumento = $rowBA[4];
				$montoPagado = $rowBA[5];										
			}								
		}
		}
		
		//se comento a ver... 
			
		if(buscarDocTe($documento,$cc,$ct,$dt,$fecha)==0){//23042012	
			if($monto == $montoPagado){
				//ingresarRenglon($cuentaBanco,$descripcion,0,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);				
			}else{
				ingresarRenglon($cuentaBanco,$descripcion,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			}
			$montoCa = bcadd($montoCa,$monto,2);
		}
	}
	
	//echo("<br><br>".$montoCa."----".$montoPagado);
	
	//$cuentaCaja =  buscarContable(10,0,$sucursal);
	$cuentaCaja =  buscarContable(10,2,$sucursal);
	if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
		if($montoCa != 0){
			ingresarRenglon($cuentaCaja,"DEPOSITOS EN CAJAS ",0,$montoCa-$montoPagado,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);				
		}
	}
		 
	// ***************************************************************
	// **************SOLO PARA TRASNFERENCIAS REPUESTOS***************
	// ***************************************************************
	
	$SqlStr = "select  b.idpago  as id
		,b.fechapago as fecha
		,'05' as centrocosto
		,(select nombreFormaPago from ".$_SESSION['bdEmpresa'].".formapagos  where idformapago=b.formapago)
		,sum(b.montopagado) as monto
		,b.numerodocumento as documento
		,b.bancodestino	
		, b.idcaja as idcaja
		,formapago
		,b.cuentaEmpresa
		,x.id_empresa
		from ".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura x,".$_SESSION['bdEmpresa'].".sa_iv_pagos b
		where b.formapago = 4
		and b.id_factura = x.idFactura
		and x.iddepartamentoorigenfactura in(0,1,3)
		";
		$SqlStr.=" and b.fechapago between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
		$SqlStr.="
		group by fecha,documento
		union all 
		select   b.idAnticipo as id 
		,b.fechaPagoAnticipo as fecha 
		,'05' as centrocosto 
		,(select nombreFormaPago  from ".$_SESSION['bdEmpresa'].".formapagos  where aliasformapago=b.tipoPagoDetalleAnticipo)
		,sum(b.montoDetalleAnticipo) as monto
		,numeroAnticipo as documento
		,b.bancoCompaniaDetalleAnticipo as bancodestino
		,b.idcaja as idcaja
		,c.idformapago as formapago
		,b.numeroCuentaCompania
		,a.id_empresa
		from ".$_SESSION['bdEmpresa'].".cj_cc_anticipo a,
		".$_SESSION['bdEmpresa'].".cj_cc_detalleanticipo b										
		,".$_SESSION['bdEmpresa'].".formapagos c
		where a.idanticipo= b.idanticipo 
		and a.idDepartamento in(0,1,3)
		";
		$SqlStr.=" and b.fechaPagoAnticipo between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
		$SqlStr.=" and c.idformapago = 4
		and c.aliasformapago = b.tipopagodetalleanticipo
		group by fecha,documento
		";
					
		$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
		$montoCa = 0;
		$diaAnt = "";
		
		while ($row = ObtenerFetch($exec)) {
			$sucursal = $row[10];
			$icomprobant++;
			if ($diaAnt == ""){ //PENDIENTE COMPORTAMIENTO
				$diaAnt = $row[1];
			}
			if ($diaAnt != $row[1]){
				$cuentaCaja =  buscarContable(10,2,$sucursal);
				if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
					//ingresarRenglon($cuentaCaja,"DEPOSITOS EN CAJAS ",0,$montoCa,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);				
					$diaAnt = $row[1];
					$montoCa = 0;
				}
			} // FIN PENDIENTE COMPORTAMIENTO
			$id = $row[0];
			$fecha = $row[1];
			$descripcion = $row[3];
			$monto=$row[4];
			$documento = $row[5];
			$id_numero_cuenta = $row[6];
			$numCuenta = $row[9];
			//$SqlStr = " select id from ".$_SESSION['bdEmpresa'].".v_bancoscuentas where idbanco=$id_numero_cuenta";//NO SE PUEDE USAR ID BANCO PORQUE PUEDE TENER MULTIPLES CUENTAS
			$SqlStr = " select idCuentas from ".$_SESSION['bdEmpresa'].".cuentas where numeroCuentaCompania like '$numCuenta'";
			$execBan =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
			$rowBan = ObtenerFetch($execBan);							  
			//$documento ='DEP-RE'.date("dmY",strtotime($fecha));
			$cuentaBanco =  buscarContable(43,$rowBan[0],$sucursal);
			//$cuentaCaja =  buscarContable(10,0,$sucursal);
			$cuentaCaja =  buscarContable(10,2,$sucursal);
			if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
				ingresarRenglon($cuentaBanco,$descripcion,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				ingresarRenglon($cuentaCaja,"DEPOSITOS EN CAJAS ",0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				$montoCa = bcadd($montoCa,$monto,2);
			}
		}
		//********************************************************************
		//***************FIN SOLO PARA TRASFERENCIAS**************************
		//********************************************************************
		 
		/*  $cuentaCaja =  buscarContable(10,0,$sucursal);
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			if($montoCa != 0){
				ingresarRenglon($cuentaCaja,"DEPOSITOS EN CAJAS ",0,$montoCa,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			}
		}*/	
		
		//retenciones ISLR IVA
		$ct='05';
		$dt='03';
		$cc='05';	
		$SqlStr = "select 
			max(b.idpago)
			,b.fechapago
			,'05' as centrocosto
			,(select nombreFormaPago from ".$_SESSION['bdEmpresa'].".formapagos  where idformapago=b.formapago)
			,SUM(b.montopagado) as monto
			, 0 as documento
			,b.bancodestino	
			, max(b.idcaja)
			,formapago
			,a.id_empresa
			from ".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a,".$_SESSION['bdEmpresa'].".sa_iv_pagos b
			where b.formapago between 9 and 10  	
			and b.id_factura = a.idFactura
			and a.idDepartamentoorigenfactura in(0,1,3)";
		$SqlStr.=" and b.fechapago between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
		$SqlStr.=" group by b.fechapago,b.formapago
		order by b.fechapago";
		$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
		$montoCa = 0;
		$diaAnt = "";
		while ($row = ObtenerFetch($exec)) {
			$sucursal = $row[9];
			$icomprobant++;
			if ($diaAnt == ""){
				$diaAnt = $row[1];
			}
			if ($diaAnt != $row[1]){
				$documento = "RET-RE".date("dmY",strtotime($fecha));
				//$cuentaCaja =  buscarContable(10,0,$sucursal);
				$cuentaCaja =  buscarContable(10,2,$sucursal);
				if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
					if($montoCa != 0){
						ingresarRenglon($cuentaCaja,"RETENCION DE PAGO ",0,$montoCa,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);						
						}	
				   $montoCa = 0;
				   $diaAnt = $row[1];
				}
			}
			$id = $row[0];
			$fecha = $row[1];
			$descripcion = $row[3];
			$monto=$row[4];
			$documento = $row[5];
			$id_numero_cuenta = $row[6];
			$formapago = $row[8];
			if ($formapago == 9){
				$cuentaI =  buscarContable(25,17,$sucursal);
				$des = "IVA";
			}else{
				$cuentaI =  buscarContable(25,16,$sucursal);
				$des = "ISLR";
			}	
			$documento = date("dmY",strtotime($fecha));
			if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
				ingresarRenglon($cuentaI,"RETENCION ".$des,$monto,0,'RET-RE'.$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				$montoCa = bcadd($montoCa,$monto,2);
			}
		}
		
	//$cuentaCaja =  buscarContable(10,0,$sucursal);
	$cuentaCaja =  buscarContable(10,2,$sucursal);
	$documento = 'RET-RE'.date("dmY",strtotime($fecha));
	if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
		if($montoCa != 0){
		ingresarRenglon($cuentaCaja,"RETENCION DE PAGO ",0,$montoCa,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);						 						
		}  
	}
}

/*F 15*/
//***************DEPOSITO DE TESORERIA DE VEHICULOS*********************************
function generarDepositosTeVe($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
	// *******************************************************************************************
	// **********************************Generar Depositos VEHICULOS******************************
	// *******************************************************************************************
	$ct='13';
	$dt='03';
	$cc='05';
	
	$SqlStr = "select a.id_deposito
		,a.fecha_registro
		,'05' as centrocosto
		,a.observacion as descripcion
		,monto_total_deposito as monto
		,a.numero_deposito_banco as documento
		,a.id_numero_cuenta
		,a.origen
		,b.descripcion
		,a.id_empresa
	from ".$_SESSION['bdEmpresa'].".te_depositos a,".$_SESSION['bdEmpresa'].".te_origen b
	where a.origen = b.id
	and a.origen = 1";
		
	if($idTran != 0){
		$SqlStr.=" and a.id_deposito = ".$idTran;
	}else{
		$SqlStr.=" and a.fecha_registro between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
	}
	
	$icomprobant=0;							
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
	
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto=$row[4];
		$documento = $row[5];
		$id_numero_cuenta = $row[6];
		$origen = $row[7];
		$desorigen = $row[8];
		$sucursal = $row[9];
		$cuentaBanco =  buscarContable(43,$id_numero_cuenta,$sucursal);
		$cuentaMov =  buscarContable(10,$origen,$sucursal);
		//if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
		if(buscarDocTe($documento,$cc,$ct,$dt,$fecha)==0){//23042012			
			ingresarRenglon($cuentaBanco,$descripcion. " ".$desorigen,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			ingresarRenglon($cuentaMov,$descripcion. " ".$desorigen,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
		}
	}
	
	$ct='13';
	$dt='03';
	$cc='05';
	$SqlStr = "select  
		max(b.idpago) as id
		,b.fechapago as fecha
		,'05' as centrocosto
		,(select nombreFormaPago from ".$_SESSION['bdEmpresa'].".formapagos  where idformapago=b.formapago)
		,SUM(b.montopagado) as monto
		, 0 as documento
		,b.bancodestino	
		, max(b.idcaja) as idcaja
		,formapago
		,b.cuentaEmpresa
		,x.id_empresa
		,0 as idf
		,b.id_factura
	from ".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura x,".$_SESSION['bdEmpresa'].".an_pagos b
	where b.formapago in(5,6)
	and b.id_factura = x.idFactura
	and x.iddepartamentoorigenfactura = 2";
	$SqlStr.=" and b.fechapago between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
	$SqlStr.=" group by fecha,formapago,bancodestino
	union all 
	select
		max(b.idAnticipo) as id 
		,b.fechaPagoAnticipo as fecha 
		,'05' as centrocosto 
		,(select nombreFormaPago  from ".$_SESSION['bdEmpresa'].".formapagos  where aliasformapago = b.tipoPagoDetalleAnticipo)
		,SUM(b.montoDetalleAnticipo) as monto
		,numeroAnticipo as documento
		,b.bancoCompaniaDetalleAnticipo as bancodestino
		,b.idcaja as idcaja
		,c.idformapago as formapago
		,b.numeroCuentaCompania
		,a.id_empresa
		,a.idAnticipo
		,0
	from ".$_SESSION['bdEmpresa'].".cj_cc_anticipo a,
	".$_SESSION['bdEmpresa'].".cj_cc_detalleanticipo b
	,".$_SESSION['bdEmpresa'].".formapagos c
	where a.idanticipo= b.idanticipo 
	and a.idDepartamento = 2";
	$SqlStr.=" and b.fechaPagoAnticipo between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
	$SqlStr.=" and c.idformapago in(5,6,7) 
	and c.aliasformapago = b.tipopagodetalleanticipo
	and a.estatus<> 0
	group by fecha,formapago,bancodestino
	order by fecha";
			
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
	$montoCa = 0;
	$diaAnt = "";
	
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$sucursal = $row[10];
		
		if ($diaAnt == ""){
			$diaAnt = $row[1];
		}
		if ($diaAnt != $row[1]){
			$cuentaCaja =  buscarContable(10,1,$sucursal);
			if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
				ingresarRenglon($cuentaCaja,"DEPOSITOS EN CAJAS ",0,$montoCa,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);					
				$diaAnt = $row[1];
				$montoCa = 0;
			}
		 }
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto=$row[4];
		$documento = $row[5];
		$id_numero_cuenta = $row[6];
		$numCuenta = $row[9];
		
		$SqlStr = " select id from ".$_SESSION['bdEmpresa'].".v_bancoscuentas where idbanco=$id_numero_cuenta";
		$execBan =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
		
		$rowBan = ObtenerFetch($execBan);							  
		$documento ='DEP-VE'.date("dmY",strtotime($fecha));
		$cuentaBanco =  buscarContable(43,$rowBan[0],$sucursal);
		//if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){ $idDoc
        
		//
		$SqlIdFact = "SELECT id_factura, numeroDocumento
					FROM ".$_SESSION['bdEmpresa'].".sa_iv_pagos
					WHERE numeroDocumento = '$documento'";
		
		$execIF = EjecutarExec($con,$SqlIdFact) or die($SqlIdFact." " .mysql_error()); 
		
		while($rowIF = ObtenerFetch($execIF)){
			$idFact = $rowIF[0];
			
			$SqlBusAnul = "SELECT idPago, id_factura, numeroFactura, fechaPago, numeroDocumento, montoPagado, estatus, fecha_anulado
						FROM ".$_SESSION['bdEmpresa'].".sa_iv_pagos
						WHERE id_factura = $idFact
							AND fecha_anulado <> 'NULL'";
			
			$execBA = EjecutarExec($con,$SqlBusAnul) or die($SqlBusAnul." " .mysql_error()); 				

			while($rowBA = ObtenerFetch($execBA)){
				$idPago = $rowBA[0];
				$numeroDocumento = $rowBA[4];
				$montoPagado = $rowBA[5];										
			}								
		}

		//
		
		
		if(buscarDocTe($documento,$cc,$ct,$dt,$fecha)==0){//23042012
			if($monto == $montoPagado){
//				ingresarRenglon($cuentaBanco,$descripcion,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);	
			}else{
				ingresarRenglon($cuentaBanco,$descripcion,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			}
			$montoCa = bcadd($montoCa,$monto,2);
		}
	}
	if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){	
		if($montoCa != 0){
			//$cuentaCaja =  buscarContable(10,0,$sucursal);
			$cuentaCaja =  buscarContable(10,1,$sucursal);
			ingresarRenglon($cuentaCaja,"DEPOSITOS EN CAJAS ",0,$montoCa,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);				
		}
	}
		 
	// ***************************************************************
	// **************SOLO PARA TRASNFERENCIAS VEHICULOS***************
	// ***************************************************************
	
	$SqlStr = "select  b.idpago  as id
		,b.fechapago as fecha
		,'05' as centrocosto
		,(select nombreFormaPago from ".$_SESSION['bdEmpresa'].".formapagos  where idformapago=b.formapago)
		,sum(b.montopagado) as monto
		,b.numerodocumento as documento
		,b.bancodestino	
		, b.idcaja as idcaja
		,formapago
		,b.cuentaEmpresa
		,x.id_empresa
		from ".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura x,".$_SESSION['bdEmpresa'].".an_pagos b
		where b.formapago = 4
		and b.id_factura = x.idFactura
		and x.iddepartamentoorigenfactura = 2
		";
		$SqlStr.=" and b.fechapago between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
		$SqlStr.="
		group by fecha,documento
		union all 
		select   b.idAnticipo as id 
		,b.fechaPagoAnticipo as fecha 
		,'05' as centrocosto 
		,(select nombreFormaPago  from ".$_SESSION['bdEmpresa'].".formapagos  where aliasformapago = b.tipoPagoDetalleAnticipo)
		,sum(b.montoDetalleAnticipo) as monto
		,b.numerocontroldetalleanticipo as documento
		,b.bancoCompaniaDetalleAnticipo as bancodestino
		,b.idcaja as idcaja
		,c.idformapago as formapago
		,b.numeroCuentaCompania
		,a.id_empresa
		from ".$_SESSION['bdEmpresa'].".cj_cc_anticipo a,
		".$_SESSION['bdEmpresa'].".cj_cc_detalleanticipo b										
		,".$_SESSION['bdEmpresa'].".formapagos c
		where a.idanticipo= b.idanticipo 
		and a.idDepartamento = 2
		";
		$SqlStr.=" and b.fechaPagoAnticipo between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
		$SqlStr.=" and c.idformapago = 4
		and c.aliasformapago = b.tipopagodetalleanticipo
		group by fecha,documento
		";
					
		$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
		$montoCa = 0;
		$diaAnt = "";
		
		while ($row = ObtenerFetch($exec)) {
			$sucursal = $row[10];
			$icomprobant++;
			if ($diaAnt == ""){ //PENDIENTE COMPORTAMIENTO
				$diaAnt = $row[1];
			}
			if ($diaAnt != $row[1]){
				$cuentaCaja =  buscarContable(10,1,$sucursal);
				if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
					//ingresarRenglon($cuentaCaja,"DEPOSITOS EN CAJAS ",0,$montoCa,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);				
					$diaAnt = $row[1];
					$montoCa = 0;
				}
			} // FIN PENDIENTE COMPORTAMIENTO
			$id = $row[0];
			$fecha = $row[1];
			$descripcion = $row[3];
			$monto=$row[4];
			$documento = $row[5];
			$id_numero_cuenta = $row[6];
			$numCuenta = $row[9];
			$SqlStr = " select id from ".$_SESSION['bdEmpresa'].".v_bancoscuentas where idbanco=$id_numero_cuenta";
			$execBan =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
			$rowBan = ObtenerFetch($execBan);							  
			//$documento ='DEP-RE'.date("dmY",strtotime($fecha));
			$cuentaBanco =  buscarContable(43,$rowBan[0],$sucursal);
			//$cuentaCaja =  buscarContable(10,0,$sucursal);
			$cuentaCaja =  buscarContable(10,1,$sucursal);
			if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
				ingresarRenglon($cuentaBanco,$descripcion,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				ingresarRenglon($cuentaCaja,"DEPOSITOS EN CAJAS ",0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				$montoCa = bcadd($montoCa,$monto,2);
			}
		}
		//********************************************************************
		//***************FIN SOLO PARA TRASFERENCIAS**************************
		//********************************************************************
		 
		/*  $cuentaCaja =  buscarContable(10,0,$sucursal);
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			if($montoCa != 0){
				ingresarRenglon($cuentaCaja,"DEPOSITOS EN CAJAS ",0,$montoCa,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			}
		}*/	
		
		//retenciones ISLR IVA
		$ct='13';
		$dt='03';
		$cc='05';	
		$SqlStr = "select 
			max(b.idpago)
			,b.fechapago
			,'05' as centrocosto
			,(select nombreFormaPago from ".$_SESSION['bdEmpresa'].".formapagos  where idformapago=b.formapago)
			,SUM(b.montopagado) as monto
			, 0 as documento
			,b.bancodestino	
			, max(b.idcaja)
			,formapago
			,a.id_empresa
			from ".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a,".$_SESSION['bdEmpresa'].".an_pagos b
			where b.formapago between 9 and 10  	
			and b.id_factura = a.idFactura
			and a.idDepartamentoorigenfactura = 2";
		$SqlStr.=" and b.fechapago between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
		$SqlStr.=" group by b.fechapago,b.formapago
		order by b.fechapago";
		$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
		$montoCa = 0;
		$diaAnt = "";
		while ($row = ObtenerFetch($exec)) {
			$sucursal = $row[9];
			$icomprobant++;
			if ($diaAnt == ""){
				$diaAnt = $row[1];
			}
			if ($diaAnt != $row[1]){
				$documento = "RET-VE".date("dmY",strtotime($fecha));
				//$cuentaCaja =  buscarContable(10,0,$sucursal);
				$cuentaCaja =  buscarContable(10,1,$sucursal);
				if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
					if($montoCa != 0){
						ingresarRenglon($cuentaCaja,"RETENCION DE PAGO ",0,$montoCa,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);						
						}	
				   $montoCa = 0;
				   $diaAnt = $row[1];
				}
			}
			$id = $row[0];
			$fecha = $row[1];
			$descripcion = $row[3];
			$monto=$row[4];
			$documento = $row[5];
			$id_numero_cuenta = $row[6];
			$formapago = $row[8];
			if ($formapago == 9){
				$cuentaI =  buscarContable(25,17,$sucursal);
				$des = "IVA";
			}else{
				$cuentaI =  buscarContable(25,16,$sucursal);
				$des = "ISLR";
			}	
			$documento = date("dmY",strtotime($fecha));
			if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
				//$cuentaCaja =  buscarContable(10,0,$sucursal);
				$cuentaCaja =  buscarContable(10,1,$sucursal);
				ingresarRenglon($cuentaI,"RETENCION ".$des,$monto,0,'RET-VE'.$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				ingresarRenglon($cuentaCaja,"RETENCION ".$des,0,$monto,'RET-VE'.$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				$montoCa = bcadd($montoCa,$monto,2);
			}
		}
}

/*F 16*/
///***************TRANSFERENCIAS DE TESORERIA***************************************
function generarTransferenciaTe($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
	$ct='14';
	$dt='04';
	$cc='05';
		
	$SqlStr = "select a.id_transferencia
		,a.fecha_registro 
		,'05' as centrocosto
		,a.observacion as descripcion 
		,monto_transferencia as monto
		,a.numero_transferencia as documento
		,a.id_cuenta
		,id_beneficiario_proveedor
		,beneficiario_proveedor
		,a.id_documento
		,a.tipo_documento
		,a.id_empresa
	from ".$_SESSION['bdEmpresa'].".te_transferencia a";
						
	if($idTran != 0){
		$SqlStr.=" where a.id_transferencia = ".$idTran;
	}else{
		$SqlStr.=" where a.fecha_registro between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
	}
	
	$icomprobant=0;							
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
	
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$sucursal = $row[11];
		//$cc = $row[2];
		$descripcion = $row[3];
		$monto=$row[4];
		$documento = $row[5];
		$id_numero_cuenta = $row[6];
		$id_proveedor_beneficiario = $row[7];
		$control_beneficiario_proveedor = $row[8];
		$idfactura = $row[9];
		$tipo_documento = $row[10];
	  
		if($tipo_documento==0){
			if($idfactura == 0){
				$SqlStr = "select id_propuesta_pago
				from ".$_SESSION['bdEmpresa'].".te_propuesta_pago_transferencia
				where id_transfererencia = $id";
				$execPago =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
				
				if (NumeroFilas($execPago)>0){
					$row2 = ObtenerFetch($execPago);
					$id_propuesta_pago = $row2[0];
					$SqlStr ="select id_factura,tipo_documento 
					from ".$_SESSION['bdEmpresa'].".te_propuesta_pago_detalle_transferencia 
					where id_propuesta_pago=$id_propuesta_pago";
					$execPagoDet =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());								
					if (NumeroFilas($execPagoDet)>0){
						$row3 = ObtenerFetch($execPagoDet);
						$idfactura = $row3[0];
						$tipo_documento =$row3[1];
					}		
				}
						 
				$idmodulo = 0;
				if($tipo_documento==0){
					$SqlStr = "select id_modulo
					from ".$_SESSION['bdEmpresa'].".cp_factura where id_factura = $idfactura";
					$execModulo =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
					if (NumeroFilas($execModulo)>0){
						$row5 = ObtenerFetch($execModulo);
						$idmodulo = $row5[0];
					}
				}else{
					$SqlStr = "select id_modulo
					from ".$_SESSION['bdEmpresa'].".cp_notadecargo where id_notacargo = $idfactura";
					$execModulo =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
					if (NumeroFilas($execModulo)>0){
						$row5 = ObtenerFetch($execModulo);
						$idmodulo = $row5[0];
					}
				}
				
			}else{
				$idmodulo = 0;
				$SqlStr = "select id_modulo
				from ".$_SESSION['bdEmpresa'].".cp_factura where id_factura = $idfactura";
				$execModulo =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
				if (NumeroFilas($execModulo)>0){
					$row5 = ObtenerFetch($execModulo);
					$idmodulo = $row5[0];
				}
			}
								 
		}else{
			$SqlStr = "select id_modulo
			from ".$_SESSION['bdEmpresa'].".cp_notadecargo where id_notacargo = $idfactura";
			$execModulo =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
			if (NumeroFilas($execModulo)>0){
				$row5 = ObtenerFetch($execModulo);
				$idmodulo = $row5[0];
			}
		}// hasta aqui copie yo 
		
		if ($control_beneficiario_proveedor == 1){
			if($idmodulo==0){ 
				// repuestos
				$cuentaMov =  buscarContable(14,$id_proveedor_beneficiario,$sucursal);        
			}	   
			if($idmodulo==1){ 
				// servicios
				$cuentaMov =  buscarContable(15,$id_proveedor_beneficiario,$sucursal);        
			}	   
			if($idmodulo==2){ 
				// vehiculo
				$cuentaMov =  buscarContable(13,$id_proveedor_beneficiario,$sucursal);        
			}	   
			if($idmodulo==3){ 
				// Administracion
				$cuentaMov =  buscarContable(16,$id_proveedor_beneficiario,$sucursal);        
			}	   
		}else{
			$cuentaMov =  buscarContable(46,$id_proveedor_beneficiario,$sucursal);
		}
		
		if ($control_beneficiario_proveedor == 1){
			$cuentaMov =  buscarContable(16,$id_proveedor_beneficiario,$sucursal);        
		}else{
			$cuentaMov =  buscarContable(46,$id_proveedor_beneficiario,$sucursal);
		}
		
		//// para las retenciones ISLR 
		$montoRet = 0;
		$SqlStr= " select sum(a.monto_retenido) as monto 
		from ".$_SESSION['bdEmpresa'].".te_retencion_cheque a
		where a.id_cheque = $id and tipo_documento = 1 ";
		$execISLR =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
		$rowISLR = ObtenerFetch($execISLR);
		if(!is_null($rowISLR[0]) && $rowISLR[0] != 0){
	  
			if($idmodulo==0){ 
				// repuestos
				$cuentaMovIslr =  buscarContable(23,4,$sucursal);        
			}	   
			if($idmodulo==1){
				// servicios
				$cuentaMovIslr =  buscarContable(24,4,$sucursal);        
			  }	   
			  if($idmodulo==2){ 
				// vehiculo
				$cuentaMovIslr =  buscarContable(22,4,$sucursal);        
			}	   
			if($idmodulo==3){ 
				// Administracion
				$cuentaMovIslr =  buscarContable(6,4,$sucursal);        
			}	 
			  
			$montoRet = $rowISLR[0];
			$montocxp = bcadd($monto,$montoRet,2);  
			$cuentaBanco =  buscarContable(43,$id_numero_cuenta,$sucursal);
			if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){//23042012		
				ingresarRenglon($cuentaMov,$descripcion,$montocxp,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				ingresarRenglon($cuentaMovIslr,"ISLR ".$descripcion,0,$montoRet,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				ingresarRenglon($cuentaBanco,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			}	
		}else{
			if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){//23042012	
				$cuentaBanco =  buscarContable(43,$id_numero_cuenta,$sucursal);
				ingresarRenglon($cuentaMov,$descripcion,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				ingresarRenglon($cuentaBanco  ,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			}
		}								
	 }
}

/////////////////////////ANULACION DE TRANSFERENCIA//////////////////////////////////////////////////////
///***************TRANSFERENCIAS DE TESORERIA***************************************
function generarAnularTransferenciaTe($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
	$ct='14';
	$dt='04';
	$cc='05';
		
	$SqlStr = "select a.id_transferencia_anulada
			,a.numero_transferencia
			,a.num_cuenta
			,a.beneficiario_proveedor
			,a.id_beneficiario_proveedor
			,a.fecha_registro
			,a.fecha_transferencia
			,a.observacion
			,a.monto_transferencia
			,a.id_cuenta
			,a.id_empresa
			,a.id_usuario
			,a.id_documento
			,a.id_transferencia
			,a.tipo_documento
	from ".$_SESSION['bdEmpresa'].".te_transferencias_anuladas a";
						
	if($idTran != 0){
		$SqlStr.=" where a.id_transferencia_anulada = ".$idTran;
	}else{
		$SqlStr.=" where a.fecha_registro between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
	}
	
	$icomprobant=0;		
								
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 	
	
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		//$numerotranf = $row[1]; 
		$documento = $row[1]; 
		$numcta = $row[2];
		$control_beneficiario_proveedor = $row[3];
		$id_proveedor_beneficiario = $row[4];
		$fechareg = $row[6];
		$fecha = $row[5];
		$descripcion = $row[7];
		$monto=$row[8];
		$id_numero_cuenta = $row[9];
		$sucursal = $row[10];
		//$documento = $row[12];
		$idfactura = $row[12];
		$idtransferencia = $row[13];
		$tipo_documento = $row[14];
	  
		if($tipo_documento==0){			
			
			if($idfactura == 0){	
				
				$SqlStr = "select id_propuesta_pago
				from ".$_SESSION['bdEmpresa'].".te_transferencias_anuladas_detalle
				where id_transferencia = $idtransferencia GROUP BY id_propuesta_pago";
				$execPago =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
				
				if (NumeroFilas($execPago)>0){
					
					$row2 = ObtenerFetch($execPago);
					$id_propuesta_pago = $row2[0];
					
					$SqlStr ="select id_factura,tipo_documento 
					from ".$_SESSION['bdEmpresa'].".te_transferencias_anuladas_detalle 
					where id_propuesta_pago=$id_propuesta_pago";
					
					$execPagoDet =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());								
					
					if (NumeroFilas($execPagoDet)>0){
						$row3 = ObtenerFetch($execPagoDet);
						$idfactura = $row3[0];
						$tipo_documento =$row3[1];
					}		
				}
						 
				$idmodulo = 0;
				if($tipo_documento==0){
					$SqlStr = "select id_modulo
					from ".$_SESSION['bdEmpresa'].".cp_factura where id_factura = $idfactura";
					$execModulo =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
					if (NumeroFilas($execModulo)>0){
						$row5 = ObtenerFetch($execModulo);
						$idmodulo = $row5[0];
					}
				}else{
					$SqlStr = "select id_modulo
					from ".$_SESSION['bdEmpresa'].".cp_notadecargo where id_notacargo = $idfactura";
					$execModulo =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
					if (NumeroFilas($execModulo)>0){
						$row5 = ObtenerFetch($execModulo);
						$idmodulo = $row5[0];
					}
				}
				
			}else{
				//echo("idfactura no 0");
				$idmodulo = 0;
				$SqlStr = "select id_modulo
				from ".$_SESSION['bdEmpresa'].".cp_factura where id_factura = $idfactura";
				$execModulo =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
				if (NumeroFilas($execModulo)>0){
					$row5 = ObtenerFetch($execModulo);
					$idmodulo = $row5[0];
				}
			}
								 
		}else{
			$SqlStr = "select id_modulo
			from ".$_SESSION['bdEmpresa'].".cp_notadecargo where id_notacargo = $idfactura";
			$execModulo =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
			if (NumeroFilas($execModulo)>0){
				$row5 = ObtenerFetch($execModulo);
				$idmodulo = $row5[0];
			}
		}
		

		if ($control_beneficiario_proveedor == 1){
			if($idmodulo==0){ 
				// repuestos
				$cuentaMov =  buscarContable(14,$id_proveedor_beneficiario,$sucursal);        
			}	   
			if($idmodulo==1){ 
				// servicios
				$cuentaMov =  buscarContable(15,$id_proveedor_beneficiario,$sucursal);        
			}	   
			if($idmodulo==2){ 
				// vehiculo
				$cuentaMov =  buscarContable(13,$id_proveedor_beneficiario,$sucursal);        
			}	   
			if($idmodulo==3){ 
				// Administracion

				$cuentaMov =  buscarContable(16,$id_proveedor_beneficiario,$sucursal);        
			}	   
		}else{
			$cuentaMov =  buscarContable(46,$id_proveedor_beneficiario,$sucursal);
		}
		
		if ($control_beneficiario_proveedor == 1){
			$cuentaMov =  buscarContable(16,$id_proveedor_beneficiario,$sucursal);        
		}else{
			$cuentaMov =  buscarContable(46,$id_proveedor_beneficiario,$sucursal);
		}
		
		//// para las retenciones ISLR 
		$montoRet = 0;
		$SqlStr= " select sum(a.monto_retenido) as monto 
		from ".$_SESSION['bdEmpresa'].".te_retencion_cheque a
		where a.id_cheque = $id and tipo_documento = 1 ";
		$execISLR =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
		$rowISLR = ObtenerFetch($execISLR);
		if(!is_null($rowISLR[0]) && $rowISLR[0] != 0){
	  
			if($idmodulo==0){ 
				// repuestos
				$cuentaMovIslr =  buscarContable(23,4,$sucursal);        
			}	   
			if($idmodulo==1){
				// servicios
				$cuentaMovIslr =  buscarContable(24,4,$sucursal);        
			  }	   
			  if($idmodulo==2){ 
				// vehiculo
				$cuentaMovIslr =  buscarContable(22,4,$sucursal);        
			}	   
			if($idmodulo==3){ 
				// Administracion
				$cuentaMovIslr =  buscarContable(6,4,$sucursal);        
			}	 
			  
			$montoRet = $rowISLR[0];
			$montocxp = bcadd($monto,$montoRet,2);  
			$cuentaBanco =  buscarContable(43,$id_numero_cuenta,$sucursal);
			if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){		
				ingresarRenglon($cuentaMov,$descripcion,0,$montocxp,$documento."-A",$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				ingresarRenglon($cuentaMovIslr,"ISLR ".$descripcion,$montoRet,0,$documento."-A",$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);								   ;								   
				ingresarRenglon($cuentaBanco,$descripcion,$monto,0,$documento."-A",$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			}	
		}else{
			if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){	
				$cuentaBanco =  buscarContable(43,$id_numero_cuenta,$sucursal);
				ingresarRenglon($cuentaMov,$descripcion,0,$monto,$documento."-A",$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				ingresarRenglon($cuentaBanco,$descripcion,$monto,0,$documento."-A",$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			}
		}								
	 }
}
////////////////////////////////////////////////////////////////////////////////////////////////////////

/*F 17*/
//******************NOTA DE CREDITO DE TESORERIA************************************
function generarNotaCreditoTe($idTran=0,$Desde="",$Hasta=""){

$con = ConectarBD();
	$ct='07';
	$dt='03';
	$cc='05';	
	$SqlStr = "SELECT
		a.id_nota_credito
		,a.fecha_registro 
		,'05' as centrocosto
		,a.observaciones AS descripcion 
		,monto_nota_credito AS monto
		,a.numero_nota_credito AS documento
		,a.id_numero_cuenta
		,id_motivo
		,a.id_empresa
	FROM ".$_SESSION['bdEmpresa'].".te_nota_credito a
	WHERE origen NOT IN(1,2)";
	
	if($idTran != 0){
		$SqlStr.=" AND a.id_nota_credito = ".$idTran;
	}else{
		$SqlStr.=" AND a.fecha_registro BETWEEN '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
		
	$icomprobant = 0;							
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
	
	while ($row = ObtenerFetch($exec)) {
		
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto = $row[4];
		$documento = $row[5];
		$id_numero_cuenta = $row[6];
		$id_motivo = $row[7];
		$sucursal = $row[8];
	  
		if(buscarDocTe($documento,$cc,$ct,$dt,$fecha)==0){	
			//$cuentaMov =  buscarContable(60,$id_motivo,$sucursal);        
			$cuentaBanco =  buscarContable(43,$id_numero_cuenta,$sucursal);
			ingresarRenglon($cuentaBanco,$descripcion,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			//ingresarRenglon($cuentaMov,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			
			//multimotivos!!
			$SqlMulti = "SELECT a.id_motivo
					,a.cantidad
					,a.precio_unitario
					,(a.cantidad*a.precio_unitario) as precio
					FROM ".$_SESSION['bdEmpresa'].".te_nota_credito_detalle_motivo a
					WHERE a.id_nota_credito = ".$id;
			
			$execM =  EjecutarExec($con,$SqlMulti) or die($SqlMulti." " .mysql_error()); 
			
			$montoH = 0;
			
			while ($rowM = ObtenerFetch($execM)) {
				$id_motivo = $rowM[0];
				$precio = $rowM[3];
				
				$cuentaMov =  buscarContable(60,$id_motivo,$sucursal);
				
				$montoH = $montoH+$precio;
								
				ingresarRenglon($cuentaMov,$descripcion,0,$precio,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			}
			//
			
			
		}
	}
}

/*F 18*/
//******************NOTA DE DEBITO DE TESORERIA*************************************
function generarNotaDebitoTe($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
	$ct='08';
	$dt='03';
	$cc='05';
	
	$SqlStr = "SELECT
		a.id_nota_debito
		,a.fecha_registro 
		,'05' as centrocosto
		,a.observaciones as descripcion 
		,monto_nota_debito as monto
		,a.numero_nota_debito as documento
		,a.id_numero_cuenta
		,id_motivo
		,a.id_empresa
	FROM ".$_SESSION['bdEmpresa'].".te_nota_debito a";
	if($idTran != 0){
	$SqlStr.=" WHERE a.id_nota_debito = ".$idTran;
	}else{
	$SqlStr.=" WHERE a.fecha_registro BETWEEN '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$icomprobant = 0;
						
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
	
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto=$row[4];
		$documento = $row[5];
		$id_numero_cuenta = $row[6];
		$id_motivo = $row[7];
		$sucursal = $row[8];
		
		//if(buscarDocTe($documento,$cc,$ct,$dt,$fecha)==0){
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			//$cuentaMov = buscarContable(45,$id_motivo,$sucursal);        
			$cuentaBanco = buscarContable(43,$id_numero_cuenta,$sucursal);
			//ingresarRenglon($cuentaMov,$descripcion,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);						
			
			//multimotivos!!
			$SqlMulti = "SELECT a.id_motivo
					,a.cantidad
					,a.precio_unitario
					,(a.cantidad*a.precio_unitario) as precio
					FROM ".$_SESSION['bdEmpresa'].".te_nota_debito_detalle_motivo a
					WHERE a.id_nota_debito = ".$id;
			
			$execM =  EjecutarExec($con,$SqlMulti) or die($SqlMulti." " .mysql_error()); 
			
			$montoH = 0;
			
			while ($rowM = ObtenerFetch($execM)) {
				$id_motivo = $rowM[0];
				$precio = $rowM[3];
				
				$cuentaMov = buscarContable(45,$id_motivo,$sucursal);
				
				$montoH = $montoH+$precio;
								
				ingresarRenglon($cuentaMov,$descripcion,$precio,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			}
			//
			
			ingresarRenglon($cuentaBanco,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			
		}
	}	
}

/*F 19*/
//************************CHEQUES DE TESORERIA**************************************
function generarChequesTe($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
		$ct='14';
		$dt='05';
		$cc='05';	
		
		$SqlStr = "select a.id_cheque
			,a.fecha_cheque
			,'05' as centrocosto
			,a.observacion as descripcion 
			,a.monto_cheque as monto
			,a.numero_cheque as documento
			,b.id_cuenta
			,'' as origen
			,id_beneficiario_proveedor
			,beneficiario_proveedor
			,a.id_factura
			,tipo_documento
			,a.id_empresa
		from ".$_SESSION['bdEmpresa'].".te_cheques_anulados a
			,".$_SESSION['bdEmpresa'].".te_chequeras b
		where a.id_chequera = b.id_chq";
												
		if($idTran != 0){
			$SqlStr.=" and a.id_cheque= ".$idTran;
		}else{
			$SqlStr.=" and a.fecha_cheque between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
		}  
							  
		$SqlStr.= " union all select a.id_cheque
			,a.fecha_registro 
			,'05' as centrocosto
			,a.observacion as descripcion 
			,a.monto_cheque as monto
			,a.numero_cheque as documento
			,b.id_cuenta
			,'' as origen
			,id_beneficiario_proveedor
			,beneficiario_proveedor
			,a.id_factura
			,tipo_documento
			,a.id_empresa
		from ".$_SESSION['bdEmpresa'].".te_cheques a
			,".$_SESSION['bdEmpresa'].".te_chequeras b
		where a.id_chequera = b.id_chq";
												
		if($idTran != 0){
			$SqlStr.=" and a.id_cheque= ".$idTran;
		}else{
			$SqlStr.=" and a.fecha_registro between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
	}
		$SqlStr.=" ORDER BY 7,6 ";
	$icomprobant=0;							
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
	
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto=$row[4];
		$documento = $row[5];
		$id_numero_cuenta = $row[6];
		$origen = $row[7];
		$id_proveedor_beneficiario = $row[8];
		$control_beneficiario_proveedor = $row[9];
		$idfactura = $row[10];
		$tipo_documento= $row[11];
		$sucursal= $row[12];
	  
		if($tipo_documento==0){
			if($idfactura == 0){
				$SqlStr = "select id_propuesta_pago
				from ".$_SESSION['bdEmpresa'].".te_propuesta_pago
				where id_cheque = $id";
				$execPago =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
				
				if (NumeroFilas($execPago)>0){
					$row2 = ObtenerFetch($execPago);
					$id_propuesta_pago = $row2[0];
					$SqlStr ="select id_factura,tipo_documento 
					from ".$_SESSION['bdEmpresa'].".te_propuesta_pago_detalle
					where id_propuesta_pago=$id_propuesta_pago";
					$execPagoDet =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());								
					if (NumeroFilas($execPagoDet)>0){
						$row3 = ObtenerFetch($execPagoDet);
						$idfactura = $row3[0];
						$tipo_documento =$row3[1];
					}		
				}
						 
				$idmodulo = 0;
				if($tipo_documento==0){
					$SqlStr = "select id_modulo
					from ".$_SESSION['bdEmpresa'].".cp_factura where id_factura = $idfactura";
					$execModulo =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
					if (NumeroFilas($execModulo)>0){
						$row5 = ObtenerFetch($execModulo);
						$idmodulo = $row5[0];
					}
				}else{
					$SqlStr = "select id_modulo
					from ".$_SESSION['bdEmpresa'].".cp_notadecargo where id_notacargo = $idfactura";
					$execModulo =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
					if (NumeroFilas($execModulo)>0){
						$row5 = ObtenerFetch($execModulo);
						$idmodulo = $row5[0];
					}
				}
				
			}else{
				$idmodulo = 0;
				$SqlStr = "select id_modulo
				from ".$_SESSION['bdEmpresa'].".cp_factura where id_factura = $idfactura";
				$execModulo =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
				if (NumeroFilas($execModulo)>0){
					$row5 = ObtenerFetch($execModulo);
					$idmodulo = $row5[0];
				}
			}
								 
		}else{
			$SqlStr = "select id_modulo
			from ".$_SESSION['bdEmpresa'].".cp_notadecargo where id_notacargo = $idfactura";
			$execModulo =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
			if (NumeroFilas($execModulo)>0){
				$row5 = ObtenerFetch($execModulo);
				$idmodulo = $row5[0];
			}
		}
						      
		if ($control_beneficiario_proveedor == 1){
			if($idmodulo==0){//REPUESTOS
				$cuentaMov = buscarContable(14,$id_proveedor_beneficiario,$sucursal);
			}
			if($idmodulo==1){ //SERVICIOS
				$cuentaMov = buscarContable(15,$id_proveedor_beneficiario,$sucursal);    
			}
			if($idmodulo==2){//VEHICULOS
				$cuentaMov = buscarContable(13,$id_proveedor_beneficiario,$sucursal);
			}
			if($idmodulo==3){//ADMINISTRACION
				$cuentaMov = buscarContable(16,$id_proveedor_beneficiario,$sucursal);
			}
		}else{
			$cuentaMov = buscarContable(46,$id_proveedor_beneficiario,$sucursal);
		}
		
		// para las retenciones ISLR 
		$montoRet = 0;
		$SqlStr= " select sum(a.monto_retenido) as monto 
		from ".$_SESSION['bdEmpresa'].".te_retencion_cheque a
		where a.id_cheque = $id and tipo_documento = 0 ";
		$execISLR =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
		$rowISLR = ObtenerFetch($execISLR);
		if(!is_null($rowISLR[0]) && $rowISLR[0] != 0){
	  
			if($idmodulo==0){//REPUESTOS
				$cuentaMovIslr = buscarContable(23,4,$sucursal);        
			}	   
			if($idmodulo==1){//SERVICIOS
				$cuentaMovIslr = buscarContable(24,4,$sucursal);        
			  }	   
			  if($idmodulo==2){//VEHICULOS
				$cuentaMovIslr = buscarContable(22,4,$sucursal);        
			}	   
			if($idmodulo==3){ //ADMINISTRACION
				$cuentaMovIslr = buscarContable(6,4,$sucursal);        
			}	 
			  
			$montoRet = $rowISLR[0];
			$montocxp = bcadd($monto,$montoRet,2);  
			$cuentaBanco =  buscarContable(43,$id_numero_cuenta,$sucursal);
			
			if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
				ingresarRenglon($cuentaMov,$descripcion,$montocxp,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				ingresarRenglon($cuentaMovIslr,"ISLR ".$descripcion,0,$montoRet,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);								   ;								   
				ingresarRenglon($cuentaBanco,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			}	
		}else{
			if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
				$cuentaBanco =  buscarContable(43,$id_numero_cuenta,$sucursal);
				ingresarRenglon($cuentaMov,$descripcion,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				ingresarRenglon($cuentaBanco,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			}
		}								
	 }
}

/*F 20*/
//************************CHEQUES ANULADOS DE TESORERIA*****************************
function generarChequesAnuladoTe($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
	$ct='09';
	$dt='05';
	$cc='05';
	$SqlStr = "select
		a.id_cheque
		,a.fecha_registro
		,'05' as centrocosto
		,a.observacion as descripcion
		,a.monto_cheque as monto
		,a.numero_cheque as documento
		,b.id_cuenta
		,'' as origen
		,id_beneficiario_proveedor
		,beneficiario_proveedor
		,a.id_empresa
	from
		".$_SESSION['bdEmpresa'].".te_cheques_anulados a
		,".$_SESSION['bdEmpresa'].".te_chequeras b
	where
		a.id_chequera = b.id_chq";
		
	if($idTran != 0){
		$SqlStr.=" and a.id_cheque_anulado = ".$idTran;
	}else{
		$SqlStr.=" and a.fecha_registro between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$icomprobant=0;
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
	
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto=$row[4];
		$documento = $row[5];
		$id_numero_cuenta = $row[6];
		$origen = $row[7];
		$id_proveedor_beneficiario = $row[8];
		$control_beneficiario_proveedor = $row[9];
		$sucursal = $row[10];
		
		if ($control_beneficiario_proveedor == 1){
			$cuentaMov =  buscarContable(16,$id_proveedor_beneficiario,$sucursal);
		}else{
			$cuentaMov =  buscarContable(46,$id_proveedor_beneficiario,$sucursal);
		}
		
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){//23042012
			$cuentaBanco =  buscarContable(43,$id_numero_cuenta,$sucursal);
			ingresarRenglon($cuentaBanco,$descripcion,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			ingresarRenglon($cuentaMov,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
		}
	}
}

/*F 21*/
//******************************CAJAS TESORERIA************************************
function generarCajasTe($idTran=0,$Desde="",$Hasta=""){
return;
$con = ConectarBD();
							  $ct='09';
							  $dt='05';
							  $cc='05';	
							$SqlStr = "select a.id_cheque
										,a.fecha_registro 
										,'05' as centrocosto
										,a.observacion as descripcion 
										,a.monto_cheque as monto
										,a.numero_cheque as documento
										,b.id_cuenta
										,'' as origen
										,id_beneficiario_proveedor
										,beneficiario_proveedor
										,a.id_empresa
										from ".$_SESSION['bdEmpresa'].".te_cheques_anulados a
										,".$_SESSION['bdEmpresa'].".te_chequeras b
										where a.id_chequera = b.id_chq";
												
							 if($idTran != 0){
									$SqlStr.=" and a.id_cheque= ".$idTran;
							}else{
									$SqlStr.=" and a.fecha_registro between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
							}
						$icomprobant=0;							
						$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
							
							 while ($row = ObtenerFetch($exec)) {
							  $icomprobant++;
							  $id = $row[0];
							  $fecha = $row[1];
							  $descripcion = $row[3];
							  $monto=$row[4];
							  $documento = $row[5];
							  $id_numero_cuenta = $row[6];
							  $origen = $row[7];
							  $id_proveedor_beneficiario = $row[8];
							  $control_beneficiario_proveedor = $row[9];
							  $sucursal = $row[10];
							  
							  if ($control_beneficiario_proveedor == 1){
							       $cuentaMov =  buscarContable(16,$id_proveedor_beneficiario,$sucursal);        
							  }else{
							       $cuentaMov =  buscarContable(46,$id_proveedor_beneficiario,$sucursal);
							  }
							  
							   if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){//23042012	
							      $cuentaBanco =  buscarContable(43,$id_numero_cuenta,$sucursal);
							      ingresarRenglon($cuentaBanco,$descripcion,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
							      ingresarRenglon($cuentaMov  ,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							  }			
							 }
}

/*F 22*/
//************GENERAR ENTRADAS A CAJA RS - NOTAS DE CARGO REPUESTOS****************
function generarCajasEntradaNotasCargoRe($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
	$ct='03';
	$dt='07';
	$cc='05';
	
	$SqlStr = "SELECT
		c.id_encabezado_nc_rs
		,c.fecha_pago
		,'02' AS centrocosto
		,'ENTRADA R/S DE CAJA GENERAL NOTA CARGO ' AS descripcion
		,SUM(b.monto_pago) AS monto
		,a.numeroNotaCargo AS documento
		,a.idCliente
		,a.id_motivo
		,a.id_empresa
		,a.idDepartamentoOrigenNotaCargo
	FROM
		".$_SESSION['bdEmpresa'].".cj_cc_notadecargo a
		,".$_SESSION['bdEmpresa'].".cj_det_nota_cargo b
		,".$_SESSION['bdEmpresa'].".cj_cc_encabezado_pago_nc_rs c
	WHERE
		b.idNotaCargo = a.idNotaCargo
		AND b.id_encabezado_nc = c.id_encabezado_nc_rs
		AND a.idDepartamentoOrigenNotaCargo IN(0,1)";
	if($idTran != 0){
		$SqlStr.=" AND c.id_encabezado_nc_rs = ".$idTran;
	}else{
		$SqlStr.=" AND c.fecha_pago between '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$SqlStr.=" GROUP BY c.fecha_pago,a.idDepartamentoOrigenNotaCargo, a.id_empresa";
	
	$icomprobant = 0;
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto=$row[4];
		$documento = $row[5];
		$idcliente = $row[6];
		$id_motivo = $row[7];
		$sucursal = $row[8];
		$iddepartamentoorigenfactura = $row[9];
		
		if($iddepartamentoorigenfactura == 0){//REPUESTOS
			$cuentaCXC = buscarContable(17,$idcliente,$sucursal);
		}
		if($iddepartamentoorigenfactura == 1){//SERVCIOS
			$cuentaCXC = buscarContable(18,$idcliente,$sucursal);
		}
		if($iddepartamentoorigenfactura == 2){//VEHICULOS
			$cuentaCXC = buscarContable(19,$idcliente,$sucursal);
		}
		
		$cuentaCaja = buscarContable(10,0,$sucursal);
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			ingresarRenglon($cuentaCaja,$descripcion,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			ingresarRenglon($cuentaCXC,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
		}
	}
}

/*F 23*/
//************GENERAR ENTRADAS A CAJA RS - NOTAS DE CARGO VEHICULOS***************
function generarCajasEntradaNotasCargoVe($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
	$ct='12';
	$dt='07';
	$cc='05';
	
	$SqlStr = "SELECT
		c.id_encabezado_nc_v
		,c.fecha_pago
		,'02' AS centrocosto
		,'ENTRADA VEHICULOS DE CAJA GENERAL NOTA CARGO ' AS descripcion
		,SUM(b.monto_pago) AS monto
		,a.numeroNotaCargo AS documento
		,a.idCliente
		,a.id_motivo
		,a.id_empresa
		,a.idDepartamentoOrigenNotaCargo
	FROM
		".$_SESSION['bdEmpresa'].".cj_cc_notadecargo a
		,".$_SESSION['bdEmpresa'].".cj_det_nota_cargo b
		,".$_SESSION['bdEmpresa'].".cj_cc_encabezado_pago_nc_v c
	WHERE
		b.idNotaCargo = a.idNotaCargo
		AND b.id_encabezado_nc = c.id_encabezado_nc_v
		AND a.idDepartamentoOrigenNotaCargo IN(2)";
	if($idTran != 0){
		$SqlStr.=" AND c.id_encabezado_nc_v = ".$idTran;
	}else{
		$SqlStr.=" AND c.fecha_pago between '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$SqlStr.=" GROUP BY c.fecha_pago,a.idDepartamentoOrigenNotaCargo, a.id_empresa";
	
	$icomprobant = 0;
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto=$row[4];
		$documento = $row[5];
		$idcliente = $row[6];
		$id_motivo = $row[7];
		$sucursal = $row[8];
		$iddepartamentoorigenfactura = $row[9];
		
		if($iddepartamentoorigenfactura == 0){//REPUESTOS
			$cuentaCXC = buscarContable(17,$idcliente,$sucursal);
		}
		if($iddepartamentoorigenfactura == 1){//SERVICIOS
			$cuentaCXC = buscarContable(18,$idcliente,$sucursal);
		}
		if($iddepartamentoorigenfactura == 2){//VEHICULOS
			$cuentaCXC = buscarContable(19,$idcliente,$sucursal);
		}
		
		$cuentaCaja = buscarContable(10,0,$sucursal);
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			ingresarRenglon($cuentaCaja,$descripcion,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			ingresarRenglon($cuentaCXC,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
		}
	}
}
							  
/*F 24*/
//***********************GENERAR ENTRADAS A CAJA RS*******************************
function generarCajasEntradaRe($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
	$ct='03';
	$dt='07';
	$cc='05';
	
	$SqlStr = "SELECT
		c.id_encabezado_rs
		,c.fecha_pago
		,'05' as centrocosto
		,'ENTRADA R/S DE CAJA GENERAL ' as descripcion
		,SUM(b.montopagado) as monto
		,0 as documento
		,a.iddepartamentoorigenfactura
		,a.idcliente
		,a.numerofactura
		,a.id_empresa
	FROM
		".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
		,".$_SESSION['bdEmpresa'].".sa_iv_pagos b
		,".$_SESSION['bdEmpresa'].".cj_cc_encabezado_pago_rs c
	WHERE
		b.id_factura = a.idFactura
		AND b.id_encabezado_rs = c.id_encabezado_rs
		AND a.iddepartamentoorigenfactura in(0,1,3)
		AND (formapago BETWEEN 1 AND 6 OR formapago BETWEEN 9 AND 10)";

	if($idTran != 0){
		$SqlStr.=" AND c.id_encabezado_rs = ".$idTran;
	}else{
		$SqlStr.=" AND c.fecha_pago BETWEEN '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$SqlStr.=" GROUP BY c.fecha_pago,a.iddepartamentoorigenfactura, a.id_empresa";
	$icomprobant = 0;
	
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
	
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$nrofactura = $row[8];
		$descripcion = $row[3]. " Nro Fact:". $nrofactura;
		$monto = $row[4];
		$iddepartamentoorigenfactura = $row[6];
		$idcliente = $row[7];
		$sucursal = $row[9];
		$documento = 'Caja:'.$id;// "CAJARE".$sucursal.date("dmY",strtotime($fecha));
		
		if($iddepartamentoorigenfactura == 0){//REPUESTOS
			$cuentaCXC = buscarContable(17,$idcliente,$sucursal);
		}
		if($iddepartamentoorigenfactura == 1){//SERVICIOS
			$cuentaCXC = buscarContable(18,$idcliente,$sucursal);
		}
		if($iddepartamentoorigenfactura == 2){//VEHICULOS
			$cuentaCXC = buscarContable(19,$idcliente,$sucursal);
		}
		if($iddepartamentoorigenfactura == 3){//ADMINISTRACION
			$cuentaCXC = buscarContable(75,$idcliente,$sucursal);
		}
		
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){ 
			$cuentaCaja = buscarContable(10,0,$sucursal); 
			ingresarRenglon($cuentaCaja,$descripcion. " ".$desorigen,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			ingresarRenglon($cuentaCXC,$descripcion. " ".$desorigen,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			/* Para insertar los terceros */
				ingresarEnlacesTerceros($cuentaCXC,$fecha,0,$monto,$idcliente
				,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
			/* Fin Para insertar los terceros */
		}
	}
	
// *******************************************************************************************
// **********************************ENTRADA Notas de Cargo***********************************
// *******************************************************************************************
	/*$SqlStr = "SELECT
		a.idNotaCargo
		,a.fechaRegistroNotaCargo 
		,'02' as centrocosto
		,a.observacionnotacargo as descripcion 
		,a.montoTotalNotaCargo as monto
		,a.numeroNotaCargo as documento
		,a.idCliente
		,a.id_motivo
		,a.id_empresa
	FROM
		".$_SESSION['bdEmpresa'].".cj_cc_notadecargo a
	WHERE
		idDepartamentoOrigenNotaCargo in(0,1)";
		
	if($idTran != 0){
		$SqlStr.=" AND a.idNotaCargo= ".$idTran;
	}else{
		$SqlStr.=" AND a.fechaRegistroNotaCargo BETWEEN '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	
	$icomprobant=0;							
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
	
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto = $row[4];
		$documento = $row[5];
		$idcliente = $row[6];
		$id_motivo = $row[7];
		$sucursal = $row[8];
		
		if($iddepartamentoorigenfactura == 0){//REPUESTOS
			$cuentaCXC = buscarContable(17,$idcliente,$sucursal);
		}
		if($iddepartamentoorigenfactura == 1){//SERVICIOS
			$cuentaCXC = buscarContable(18,$idcliente,$sucursal);
		}
		if($iddepartamentoorigenfactura == 2){//VEHICULOS
			$cuentaCXC = buscarContable(19,$idcliente,$sucursal);
		}
		
		//$cuentaMov = buscarContable(55,$id_motivo,$sucursal);
		$cuentaCaja = buscarContable(10,0,$sucursal); 
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			ingresarRenglon($cuentaCaja,$descripcion,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			ingresarRenglon($cuentaCXC,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
		}
	}*/
	 
// ********************************************************************************************
// **********************************ENTRADA ANTICIPOS*****************************************
// ********************************************************************************************
// solo para PAGOS CON anticipos
 
	$SqlStr = "SELECT
		c.id_encabezado_rs
		,max(c.fecha_pago) as fecha_pago
		,'05' as centrocosto
		,'ENTRADA R/S DE CAJA ANTICIPOS ' as descripcion
		,SUM(b.montopagado) as monto
		,0 as documento
		,max(a.iddepartamentoorigenfactura)
		,max(a.idcliente)
		,max(a.numerofactura)
		,max(a.id_empresa)
	FROM
		".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
		,".$_SESSION['bdEmpresa'].".sa_iv_pagos b
		,".$_SESSION['bdEmpresa'].".cj_cc_encabezado_pago_rs c
	WHERE
		b.id_factura = a.idFactura
		AND b.id_encabezado_rs = c.id_encabezado_rs
		AND a.iddepartamentoorigenfactura IN(0,1,3)
		AND formapago = 7";
	
	if($idTran != 0){
		$SqlStr.=" AND c.id_encabezado_rs = ".$idTran;
	}else{
	$SqlStr.=" AND c.fecha_pago BETWEEN '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$SqlStr.=" GROUP BY c.id_encabezado_rs";
	
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 

	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3]. " Nro Fact:". $nrofactura;
		$monto = $row[4];
		$iddepartamentoorigenfactura = $row[6];
		$idcliente = $row[7];
		$nrofactura = $row[8];
		$sucursal = $row[9];
		$documento = 'Adel:'.$id;// "CAJARE".$sucursal.date("dmY",strtotime($fecha));
		
		if($iddepartamentoorigenfactura == 0){//REPUESTOS
			$cuentaCXC = buscarContable(17,$idcliente,$sucursal);
			$cuentaAnticipo = buscarContable(26,47,$sucursal);							  
		}
		if($iddepartamentoorigenfactura == 1){//SERVICIOS
			$cuentaCXC = buscarContable(18,$idcliente,$sucursal);
			$cuentaAnticipo = buscarContable(27,47,$sucursal);
		}
		if($iddepartamentoorigenfactura == 2){//VEHICULOS
			$cuentaCXC = buscarContable(19,$idcliente,$sucursal);
			$cuentaAnticipo = buscarContable(25,47,$sucursal);
		}
		
		if($iddepartamentoorigenfactura == 3){//ADMINISTRACIÓN
			$cuentaCXC = buscarContable(75,$idcliente,$sucursal);
			$cuentaAnticipo = buscarContable(76,47,$sucursal);
		}
		
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){ 
			ingresarRenglon($cuentaAnticipo,$descripcion. " ".$desorigen,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			
			/* Para insertar los terceros */
				ingresarEnlacesTerceros($cuentaAnticipo,$fecha,$monto,0,$idcliente
				,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
			/* Fin Para insertar los terceros */
			
			ingresarRenglon($cuentaCXC,$descripcion. " ".$desorigen,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			
			/* Para insertar los terceros */
				ingresarEnlacesTerceros($cuentaCXC,$fecha,0,$monto,$idcliente
			,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
			/* Fin Para insertar los terceros */
		}
	}
}

/*F 25*/
//**********************GENERAR ENTRADAS A CAJA VEHICULOS**************************
function generarCajasEntradaVe($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
	$ct='12';
	$dt='07';
	$cc='05';
	
	//c.id_encabezado_v se cambió por idPago, debido a que se repetian los id encabezado y no pasaban a contabilidad, generando error//
	$SqlStr = "SELECT
		b.idPago
		,c.fecha_pago
		,'05' as centrocosto
		,'ENTRADA V DE CAJA GENERAL ' as descripcion
		,b.montopagado as monto
		,0 as documento
		,a.iddepartamentoorigenfactura
		,a.idcliente
		,a.numerofactura
		,a.id_empresa
	FROM
		".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a,
		".$_SESSION['bdEmpresa'].".an_pagos b,
		".$_SESSION['bdEmpresa'].".cj_cc_encabezado_pago_v c
	WHERE
		b.id_factura = a.idFactura
		AND b.id_encabezado_v = c.id_encabezado_v
		AND a.iddepartamentoorigenfactura = 2
		AND (formapago BETWEEN 1 AND 6 OR formapago BETWEEN 9 AND 10)";

	if($idTran != 0){
		$SqlStr.=" AND c.id_encabezado_v = ".$idTran;
	}else{
		$SqlStr.=" AND c.fecha_pago BETWEEN '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	//$SqlStr.=" GROUP BY b.fechapago, a.iddepartamentoorigenfactura";
	$icomprobant = 0;
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
	
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3]." Nro Fact:".$row[8];
		$monto = $row[4];
		$sucursal = $row[9];
		$documento = 'Caja:'.$id;
		$iddepartamentoorigenfactura = $row[6];
		$idcliente = $row[7];
		$documento = "Liq".$row[8];
		
		if($iddepartamentoorigenfactura == 0){//REPUESTOS
			$cuentaCXC = buscarContable(17,$idcliente,$sucursal);
		}
		if($iddepartamentoorigenfactura == 1){//SERVICIOS
			$cuentaCXC = buscarContable(18,$idcliente,$sucursal);
		}
		if($iddepartamentoorigenfactura == 2){//VEHICULOS
			$cuentaCXC = buscarContable(19,$idcliente,$sucursal);
		}
		
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){ 
			$cuentaCaja = buscarContable(10,0,$sucursal); 
			ingresarRenglon($cuentaCaja,$descripcion. " ".$desorigen,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			ingresarRenglon($cuentaCXC,$descripcion. " ".$desorigen,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			/* Para insertar los terceros */
				ingresarEnlacesTerceros($cuentaCXC,$fecha,0,$monto,$idcliente
				,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
			/* Fin Para insertar los terceros */
		}
	}
	
// *******************************************************************************************
// **********************************ENTRADA Notas de Cargo***********************************
// *******************************************************************************************
	/*$SqlStr = "SELECT
		a.idNotaCargo
		,a.fechaRegistroNotaCargo 
		,'02' as centrocosto
		,a.observacionnotacargo as descripcion 
		,a.montoTotalNotaCargo as monto
		,a.numeroNotaCargo as documento
		,a.idCliente
		,a.id_motivo
		,a.id_empresa
	FROM
		".$_SESSION['bdEmpresa'].".cj_cc_notadecargo a
	WHERE
		idDepartamentoOrigenNotaCargo = 2";
		
	if($idTran != 0){
		$SqlStr.=" AND a.idNotaCargo= ".$idTran;
	}else{
		$SqlStr.=" AND a.fechaRegistroNotaCargo BETWEEN '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	
	$icomprobant=0;							
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
	
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto = $row[4];
		$documento = $row[5];
		$idcliente = $row[6];
		$id_motivo = $row[7];
		$sucursal = $row[8];
		
		if($iddepartamentoorigenfactura == 0){//REPUESTOS
			$cuentaCXC = buscarContable(17,$idcliente,$sucursal);
		}
		if($iddepartamentoorigenfactura == 1){//SERVICIOS
			$cuentaCXC = buscarContable(18,$idcliente,$sucursal);
		}
		if($iddepartamentoorigenfactura == 2){//VEHICULOS
			$cuentaCXC = buscarContable(19,$idcliente,$sucursal);
		}
		
		//$cuentaMov = buscarContable(55,$id_motivo,$sucursal);
		$cuentaCaja = buscarContable(10,0,$sucursal); 
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			ingresarRenglon($cuentaCaja,$descripcion,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			ingresarRenglon($cuentaCXC,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
		}
	}*/
	 
// ********************************************************************************************
// **********************************ENTRADA ANTICIPOS*****************************************
// ********************************************************************************************
// solo para PAGOS CON anticipos
 
	$SqlStr = "SELECT
		c.id_encabezado_v
		,max(c.fecha_pago) as fecha_pago
		,'05' as centrocosto
		,'ENTRADA VEHICULOS DE CAJA ANTICIPOS ' as descripcion
		,sum(b.montopagado) as monto
		,0 as documento
		,max(a.iddepartamentoorigenfactura)
		,max(a.idcliente)
		,max(a.numerofactura)
		,max(a.id_empresa)
	FROM
		".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a,
		".$_SESSION['bdEmpresa'].".an_pagos b,
		".$_SESSION['bdEmpresa'].".cj_cc_encabezado_pago_v c
	WHERE
		b.id_factura = a.idFactura
		AND b.id_encabezado_v = c.id_encabezado_v
		AND a.iddepartamentoorigenfactura = 2
		AND formapago = 7";
	
	if($idTran != 0){
	$SqlStr.=" AND c.id_encabezado_v = ".$idTran;
	}else{
	$SqlStr.=" AND c.fecha_pago BETWEEN '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$SqlStr.=" GROUP BY c.id_encabezado_v";
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 

	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3]. " Nro Fact:". $row[8] ;
		$monto = $row[4];
		$sucursal=$row[9];  
		$documento = 'Adel:'.$id;//"CAJAVE".$sucursal.date("dmY",strtotime($fecha));
		$iddepartamentoorigenfactura = $row[6];
		$idcliente = $row[7];
		$documento = $row[8];
		
		if($iddepartamentoorigenfactura == 0){//REPUESTOS
			$cuentaCXC = buscarContable(17,$idcliente,$sucursal);
			$cuentaAnticipo = buscarContable(25,47,$sucursal);
		}
		if($iddepartamentoorigenfactura == 1){//SERVICIOS
			$cuentaCXC = buscarContable(18,$idcliente,$sucursal);
			$cuentaAnticipo = buscarContable(27,47,$sucursal);
		}
		if($iddepartamentoorigenfactura == 2){//VEHICULOS
			$cuentaCXC = buscarContable(19,$idcliente,$sucursal);
			$cuentaAnticipo = buscarContable(25,47,$sucursal);
		}
		
		if($iddepartamentoorigenfactura == 3){//ADMINISTRACIÓN
			$cuentaCXC = buscarContable(75,$idcliente,$sucursal);
			$cuentaAnticipo = buscarContable(76,47,$sucursal);
		}
		
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){ 
			ingresarRenglon($cuentaAnticipo,$descripcion. " ".$desorigen,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			
			/* Para insertar los terceros */
				ingresarEnlacesTerceros($cuentaAnticipo,$fecha,$monto,0,$idcliente
				,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion. " ".$desorigen);
			/* Fin Para insertar los terceros */
			
			ingresarRenglon($cuentaCXC,$descripcion. " ".$desorigen,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			
			/* Para insertar los terceros */
				ingresarEnlacesTerceros($cuentaCXC,$fecha,0,$monto,$idcliente
				,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion. " ".$desorigen);
			/* Fin Para insertar los terceros */
		}
	}
}

/*F 26*/
//*************************ANTICIPOS REPUESTOS Y SERVICIOS*************************
function generarAnticiposRe($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
	$ct='03';
	$dt='07';
	$cc='05';
		
	 $SqlStr = "SELECT
		max(b.idAnticipo)
		,max(b.fechaPagoAnticipo)
		,'05' AS centrocosto
		,(SELECT nombreFormaPago FROM ".$_SESSION['bdEmpresa'].".formapagos WHERE aliasformapago = b.tipoPagoDetalleAnticipo)
		,SUM(b.montoDetalleAnticipo) AS monto
		,numeroAnticipo AS documento
		,a.idDepartamento
		,b.bancoCompaniaDetalleAnticipo
		,b.idcaja
		,(SELECT concat(IFNULL(nombre,''),' ',IFNULL(apellido,'')) FROM ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 WHERE x3.id = a.idcliente) AS descliente
		,a.id_empresa
		,a.idCliente
	FROM
		".$_SESSION['bdEmpresa'].".cj_cc_anticipo a
		,".$_SESSION['bdEmpresa'].".cj_cc_detalleanticipo b
	WHERE
		a.idanticipo = b.idanticipo
		AND a.idDepartamento in(0,1,3)";
		
	if($idTran != 0){
		$SqlStr.=" AND b.idAnticipo = ".$idTran;
	}else{
		$SqlStr.=" AND b.fechaPagoAnticipo BETWEEN '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
							
	$SqlStr.=" GROUP BY a.fechaAnticipo, a.idcliente";
	$icomprobant = 0;
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
	
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto = $row[4];
		$documento = $row[5] ;
		$iddepartamentoorigenfactura = $row[6];
		$id_numero_cuenta = $row[7];
		$idcaja = $row[8];
		$Descliente = $row[9];
		$sucursal = $row[10];
		$idcliente =$row[11];
		
		if($iddepartamentoorigenfactura == 0){//REPUESTOS
			$cuentaAnticipo = buscarContable(26,47,$sucursal);
		}
		if($iddepartamentoorigenfactura == 1){//SERVICIOS
			$cuentaAnticipo = buscarContable(27,47,$sucursal);
		}
		if($iddepartamentoorigenfactura == 2){//VEHICULOS
			$cuentaAnticipo = buscarContable(25,47,$sucursal);
		}
		if($iddepartamentoorigenfactura == 3){//ADMINISTRACIÓN
			$cuentaAnticipo = buscarContable(76,47,$sucursal);		
		}
		
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			$cuentaCaja = buscarContable(10,0,$sucursal); 
			ingresarRenglon($cuentaCaja," Anticipo RS / ".$descripcion." / ".$Descliente,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			ingresarRenglon($cuentaAnticipo,"Anticipo RS / ".$descripcion." / ".$Descliente,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			/* Para insertar los terceros */
				ingresarEnlacesTerceros($cuentaAnticipo,$fecha,0,$monto,$idcliente
				,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
			/* Fin Para insertar los terceros */
		}
	}
}

/*F 27*/
//*******************************ANTICIPOS VEHICULOS*******************************
function generarAnticiposVe($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
	$ct='12';
	$dt='07';
	$cc='05';
		
	 $SqlStr = "SELECT
		max(b.idAnticipo)
		,max(b.fechaPagoAnticipo)
		,'05' AS centrocosto
		,(SELECT nombreFormaPago FROM ".$_SESSION['bdEmpresa'].".formapagos WHERE aliasformapago = b.tipoPagoDetalleAnticipo)
		,SUM(b.montoDetalleAnticipo) AS monto
		,numeroAnticipo AS documento
		,a.idDepartamento
		,b.bancoCompaniaDetalleAnticipo
		,b.idcaja
		,(SELECT concat(IFNULL(nombre,''),' ',IFNULL(apellido,'')) FROM ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 WHERE x3.id = a.idcliente) AS descliente
		,a.id_empresa
		,a.idCliente
		,a.estatus
	FROM
		".$_SESSION['bdEmpresa'].".cj_cc_anticipo a
		,".$_SESSION['bdEmpresa'].".cj_cc_detalleanticipo b
	WHERE
		a.idanticipo = b.idanticipo
		AND a.idDepartamento = 2
		AND a.estatus = 1";
	if($idTran != 0){
		$SqlStr.=" AND b.idAnticipo = ".$idTran;
	}else{
		$SqlStr.=" AND b.fechaPagoAnticipo BETWEEN '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
							
	$SqlStr.=" GROUP BY a.fechaAnticipo, a.idcliente";
	$icomprobant = 0;
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
	
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto = $row[4];
		$documento = $row[5] ;
		$iddepartamentoorigenfactura = $row[6];
		$id_numero_cuenta = $row[7];
		$idcaja = $row[8];
		$Descliente = $row[9];
		$sucursal = $row[10];
		$idcliente =$row[11];
		$estatus = $row[12];
		
		if($iddepartamentoorigenfactura == 0){//REPUESTOS
			$cuentaAnticipo = buscarContable(26,47,$sucursal);
		}
		if($iddepartamentoorigenfactura == 1){//SERVICIOS
			$cuentaAnticipo = buscarContable(27,47,$sucursal);
		}
		if($iddepartamentoorigenfactura == 2){//VEHICULOS
			$cuentaAnticipo = buscarContable(25,47,$sucursal);
		}
		
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			$cuentaCaja = buscarContable(10,0,$sucursal); 
			ingresarRenglon($cuentaCaja," Anticipo Vehiculos / ".$descripcion." / ".$Descliente,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			ingresarRenglon($cuentaAnticipo,"Anticipo Vehiculos / ".$descripcion." / ".$Descliente,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			/* Para insertar los terceros */
				ingresarEnlacesTerceros($cuentaAnticipo,$fecha,0,$monto,$idcliente
				,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
			/* Fin Para insertar los terceros */
		}
	}
}

/*F 27.1*/
//*************************ANTICIPOS POR CONCEPTOS DE PAGO*************************
function generarConceptosAnticiposVe($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
	$ct='19';
	$dt='07';
	$cc='05';
		
	$SqlStr = "SELECT
		a.id_anticipo 
		,a.numero_anticipo
		,a.idCliente
		,(SELECT concat_ws(' ',nombre,apellido) FROM ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 WHERE x3.id = a.idCliente) AS descliente
		,'05' as centrocosto
		,a.fecha_registro
		,a.caja
		,a.monto_total_anticipo AS monto
		,a.id_concepto
		,a.observacion AS descripcion
		,a.id_empresa
	FROM
		".$_SESSION['bdEmpresa'].".cj_cc_anticipo_concepto a
		,".$_SESSION['bdEmpresa'].".caja b
	WHERE a.caja = b.idCaja
		AND a.caja = 1";
	
	if($idTran != 0){
		$SqlStr.=" AND a.id_anticipo = ".$idTran;
	}else{
		$SqlStr.=" AND a.fecha_registro BETWEEN '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	
	$icomprobant = 0;	
							
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
	
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		
		$id = $row[0];
		$documento = $row[1];
		$idCliente = $row[2];
		$descliente = $row[3];
		$fecha = $row[5];
		$caja = $row[6];
		$monto = $row[7]; // SI MONTO = 0.00 EL CONCEPTO NO SE VERA REFLEJADO EN CONTABILIDAD YA QUE NO AFECTA A CUENTAS
		$id_concepto = $row[8];
		$descripcion = $row[9];
		$sucursal = $row[10];
		
		$cuentaConcepto = buscarContable(81,$id_concepto,$sucursal); // 81 = Conceptos de Pago
		$cuentaMov = buscarContable(82,$id_concepto,$sucursal); // 82 = Movimiento del Concepto
		
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			ingresarRenglon($cuentaConcepto,$descripcion.' / '.$descliente,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			ingresarRenglon($cuentaMov,$descripcion.' / '.$descliente,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			/* Para insertar los terceros */
				ingresarEnlacesTerceros($cuentaMov,$fecha,0,$monto,$idCliente
				,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
			/* Fin Para insertar los terceros */
		}
	}
}

/*F 28*/
//**************************VALE DE ENTRADA REPUESTOS*******************************
function generarValeEntradaRe($idFactura=0,$Desde="",$Hasta=""){
	$con = ConectarBD();
	$SqlStr = "update parametros set MensajeRet = ''";
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 

$SqlStr = "
select a.id_vale_entrada
       ,a.fecha
       ,'04' as centrocosto
       ,'' as cuentacontable
       ,x1.descripcion as descripcion
       ,sum(b.cantidad*b.precio_venta) as Debe 
       ,0 as haber 
       ,a.numeracion_vale_entrada as documento
       ,0
       ,'' as cuentaiva
       ,a.id_cliente as idcliente
       ,'' as descliente
       ,0 as descuento
	   ,0 as costo 
	   ,x1.id_clave_movimiento
	   ,a.id_empresa
	   from ".$_SESSION['bdEmpresa'].".iv_vale_entrada a
,".$_SESSION['bdEmpresa'].".iv_vale_entrada_detalle  b
,".$_SESSION['bdEmpresa'].".v_clave_movimientoreen x1
,".$_SESSION['bdEmpresa'].".iv_movimiento x 
,".$_SESSION['bdEmpresa'].".iv_articulos c
where a.id_vale_entrada = b.id_vale_entrada
and x.id_documento = a.id_vale_entrada
and x.id_clave_movimiento = x1.id_clave_movimiento
and b.id_articulo = c.id_articulo
and x.tipo_documento_movimiento = 1 
and x1.id_modulo =0
and x1.tipo = 2
";
// and x.tipo_documento_movimiento = 1  es para entradas y salidas
// and x1.tipo = 2 es para entradas y 4 es para salidas

if($idFactura != 0){
    $SqlStr.=" and a.id_vale_entrada= ".$idFactura;
}else{
    $SqlStr.=" and a.fecha between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
}
$SqlStr.=" group by a.id_vale_entrada, x.id_clave_movimiento
order by a.fecha,a.id_vale_entrada, x.id_clave_movimiento"; 
$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 

    $fechaAnt = "";
	$icomprobant = 0;
    while ($row = ObtenerFetch($exec)) {
	  $id = $row[0];
	  $fecha = $row[1];
	  $cc = $row[2];
	  $cuentacontable = $row[3];
	  $sucursal = $row[15];
	  
	  if (is_null($cuentacontable) || $cuentacontable== ''){
	     $cuentacontable =  buscarContable(4,0,$sucursal);
	  }
	  
	  $descripcion = $row[4];
	  $Debe = $row[5];
	  $Haber = $row[6];
	  $documento = $row[7];
	  $montoiva = $row[8];
	  $cuentaiva = $row[9];
	  $idproveedor = $row[10];
	  $Desproveedor = $row[11];
	  $Descuento = $row[12];
	  $id_clave_movimiento = $row[14]; 
	  $descripcion  = $descripcion . " " .$Desproveedor;
	  $ct='10';
	  $dt='06';
	  $cc='04';
	  if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			  if ($fechaAnt != $fecha){
			     $icomprobant++;
				 $fechaAnt = $fecha;
			  }
			$cuentaCXP = buscarContable(47,$id_clave_movimiento,$sucursal); // esta ya no es una cuenta por pagar si no una cuenta de vale de entrada 			   
			ingresarRenglon($cuentacontable,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
		    $montoCXP = $Debe;
			ingresarRenglon($cuentaCXP,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
		}		
 	}
}

/*F 29*/
//**************************VALE DE SALIDA REPUESTOS********************************
function generarValeSalidaRe($idFactura=0,$Desde="",$Hasta=""){
	$con = ConectarBD();
	$SqlStr = "update parametros set MensajeRet = ''";
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
	$SqlStr = "select
		a.id_vale_salida
		,a.fecha
		,'04' as centrocosto
		,'' as cuentacontable
		,c.descripcion as descripcion
		,sum(b.cantidad*b.precio_venta) as Debe 
		,0  as haber 
		,a.numeracion_vale_salida as documento
		,0
		,'' as cuentaiva
		,a.id_cliente as idcliente
		,'' as descliente
		,0 as descuento
		,0 as costo 
		,x1.id_clave_movimiento
		,a.id_empresa
	from
		".$_SESSION['bdEmpresa'].".iv_vale_salida a
		,".$_SESSION['bdEmpresa'].".iv_vale_salida_detalle  b
		,".$_SESSION['bdEmpresa'].".pg_clave_movimiento x1
		,".$_SESSION['bdEmpresa'].".iv_movimiento x 
		,".$_SESSION['bdEmpresa'].".iv_articulos c
	where
		a.id_vale_salida = b.id_vale_salida
		and x.id_documento = a.id_vale_salida
		and x.id_clave_movimiento = x1.id_clave_movimiento
		and b.id_articulo = c.id_articulo
		and x.tipo_documento_movimiento = 1 
		and x1.id_modulo = 0
		and x1.tipo = 4";
		// and x.tipo_documento_movimiento = 1  es para entradas y salidas
		// and x1.tipo = 2 es para entradas y 4 es para salidas

	if($idFactura != 0){
		$SqlStr.=" and a.id_vale_salida = ".$idFactura;
	}else{
		$SqlStr.=" and a.fecha between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
		// $SqlStr.=" and x.fecha_movimiento between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
	}
	
	$SqlStr.=" group by a.id_vale_salida,x.id_clave_movimiento
		order by a.fecha,a.id_vale_salida, x.id_clave_movimiento";
	
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 

	$fechaAnt = "";
	$icomprobant = 0;
	while ($row = ObtenerFetch($exec)) {
		$id = $row[0];
		$fecha = $row[1];
		$cc = $row[2];
		$cuentacontable = $row[3];
		$sucursal = $row[15];
		
		if (is_null($cuentacontable) or  $cuentacontable == ""){
			$cuentacontable =  buscarContable(4,0,$sucursal);
		}
		
		$descripcion = $row[4];
		$Debe = $row[5];
		$Haber = $row[6];
		$documento = $row[7];
		$montoiva = $row[8];
		$cuentaiva = $row[9];
		$idproveedor = $row[10];
		$Desproveedor = $row[11];
		$Descuento = $row[12];
		$id_clave_movimiento = $row[14]; 
		$descripcion  = $descripcion . " " .$Desproveedor;
		$ct='11';
		$dt='06';
		$cc='04';
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			if ($fechaAnt != $fecha){
				$icomprobant++;
				$fechaAnt = $fecha;
			}
			$cuentaCXP = buscarContable(40,$id_clave_movimiento,$sucursal);//esta ya no es una cuenta por pagar si no una cuenta de vale de entrada 			   
			ingresarRenglon($cuentaCXP,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			$montoCXP = $Debe;
			ingresarRenglon($cuentacontable,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
		}		
	}
}

/*F 30*/
//**************************VALE DE SALIDA VEHICULO*********************************
function generarValeSalidaVe($idFactura=0,$Desde="",$Hasta=""){
$con = ConectarBD();

$SqlStr = "update parametros set MensajeRet = ''";
$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
	$SqlStr = "SELECT
		a.id_vale_salida
		,a.fecha
		,'04' as centrocosto
		,'' as cuentacontable
		,a.observacion as descripcion
		,sum(a.subtotal_factura) AS Debe 
		,0  as haber 
		,a.numeracion_vale_salida AS documento
		,0
		,'' as cuentaiva
		,a.id_cliente AS idcliente
		,'' as descliente
		,0 as descuento
		,0 as costo 
		,'' as idmov
		,a.id_empresa
		,a.id_unidad_fisica 
	FROM
		".$_SESSION['bdEmpresa'].".an_vale_salida a";
		// and x.tipo_documento_movimiento = 1  es para entradas y salidas
		// and x1.tipo = 2 es para entradas y 4 es para salidas

	if($idFactura != 0){
		$SqlStr.=" WHERE a.id_vale_salida = ".$idFactura;
	}else{
		$SqlStr.=" WHERE a.fecha BETWEEN '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	// $SqlStr.=" and x.fecha_movimiento between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$SqlStr.=" GROUP BY a.id_vale_salida
	ORDER BY a.fecha,a.id_vale_salida";
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 

	$fechaAnt = "";
	$icomprobant = 0;
	while ($row = ObtenerFetch($exec)) {
		$id = $row[0];
		$fecha = $row[1];
		$cc = $row[2];
		$cuentacontable = $row[3];
		$sucursal = $row[15];
		
		if (is_null($cuentacontable) or  $cuentacontable == ""){
			$cuentacontable =  buscarContable(2,0,$sucursal);
		}
		
		$descripcion = $row[4];
		$Debe = $row[5];
		$Haber = $row[6];
		$documento = $row[7];
		$montoiva = $row[8];
		$cuentaiva = $row[9];
		$idproveedor = $row[10];
		$Desproveedor = $row[11];
		$Descuento = $row[12];
		$id_clave_movimiento = $row[14]; 
		$descripcion  = $descripcion . " " .$Desproveedor;
		$id_uni_fis = $row[16]; 
		
		$ct='11';
		$dt='06';
		$cc='02';
		
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			if ($fechaAnt != $fecha){
				$icomprobant++;
				$fechaAnt = $fecha;
			}	
			
			//query para hallar el id_modelo, y el traspaso a contabilidad tenga en cuenta el modelo integrado
			
			$SqlModelo = "select d.id_modelo from ".$_SESSION['bdEmpresa'].".an_vale_salida a 
							inner join ".$_SESSION['bdEmpresa'].".an_unidad_fisica b on a.id_unidad_fisica=b.id_unidad_fisica 
							inner join ".$_SESSION['bdEmpresa'].".an_uni_bas c on b.id_uni_bas=c.id_uni_bas 
							inner join ".$_SESSION['bdEmpresa'].".an_modelo d on c.mod_uni_bas=d.id_modelo 
						where a.id_unidad_fisica = ".$id_uni_fis;
						
			$execM =  EjecutarExec($con,$SqlModelo) or die($SqlModelo."<br>Line: ".__LINE__); 
			
			while ($rowM = ObtenerFetch($execM)) {
				$id_modelo = $rowM[0];
			
				$cuentaCXP = buscarContable(41,$id_modelo,$sucursal); // esta ya no es una cuenta por pagar si no una cuenta de vale de entrada 			   			
				ingresarRenglon($cuentaCXP,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				$montoCXP = $Debe;
				ingresarRenglon($cuentacontable,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			}
		}		
	}
}

/*F 30.1*/
//**************************VALE DE ENTRADA VEHICULO*********************************
function generarValeEntradaVe($idFactura=0,$Desde="",$Hasta=""){
	$con = ConectarBD();
	$SqlStr = "update parametros set MensajeRet = ''";
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 

	$SqlStr = "SELECT
		a.id_vale_entrada
		,a.fecha
		,'04' as centrocosto
		,'' as cuentacontable
		,a.observacion as descripcion
		,sum(a.subtotal_factura) as Debe 
		,0  as haber 
		,a.numeracion_vale_entrada as documento
		,0
		,'' as cuentaiva
		,a.id_cliente as idcliente
		,'' as descliente
		,0 as descuento
		,0 as costo 
		,'' as idmov
		,a.id_empresa
		,(SELECT serial_carroceria FROM ".$_SESSION['bdEmpresa'].".an_unidad_fisica b WHERE a.id_unidad_fisica = b.id_unidad_fisica) AS serial
	FROM ".$_SESSION['bdEmpresa'].".an_vale_entrada a";
	// and x.tipo_documento_movimiento = 1  es para entradas y salidas
	// and x1.tipo = 2 es para entradas y 4 es para salidas
	
	if($idFactura != 0){
		$SqlStr.=" WHERE a.id_vale_entrada = ".$idFactura;
	}else{
		$SqlStr.=" WHERE a.fecha BETWEEN '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	// $SqlStr.=" and x.fecha_movimiento between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$SqlStr.=" GROUP BY a.id_vale_entrada
	ORDER BY a.fecha,a.id_vale_entrada";
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
	
	$fechaAnt = "";
	$icomprobant = 0;
	while ($row = ObtenerFetch($exec)) {
		$id = $row[0];
		$fecha = $row[1];
		$cc = $row[2];
		$cuentacontable = $row[3];
		$sucursal = $row[15];
		
		if (is_null($cuentacontable) or $cuentacontable == ""){
			$cuentacontable = buscarContable(2,0,$sucursal);
		}
		
		$descripcion = $row[4];
		$Debe = $row[5];
		$Haber = $row[6];
		$documento = $row[7];
		$montoiva = $row[8];
		$cuentaiva = $row[9];
		$idproveedor = $row[10];
		$Desproveedor = $row[11];
		$Descuento = $row[12];
		$id_clave_movimiento = $row[14]; 
		$descripcion = $descripcion. " " .$Desproveedor;
		$serial = $row[16]; 
				
		$ct='10';
		$dt='06';
		$cc='02';
		
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			if ($fechaAnt != $fecha){
				$icomprobant++;
				$fechaAnt = $fecha;
			}
			
			$cuentaCXP = buscarContable(65,0,$sucursal); // esta ya no es una cuenta por pagar si no una cuenta de vale de entrada
			$montoCXP = $Debe;
			
			ingresarRenglon($cuentacontable,$descripcion.' / '.$serial,$montoCXP,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id); 			
			ingresarRenglon($cuentaCXP,$descripcion.' / '.$serial,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
		}
	}
}

/*F 31*/
//******************NOTAS DE CARGO CXC REPUESTOS Y SERVICIOS*************************
function generarNotasCargoRe($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
$ct='15';
$dt='08';
$cc='04';	
	$SqlStr = "select
		a.idNotaCargo
		,a.fechaRegistroNotaCargo 
		,'02' as centrocosto
		,a.observacionnotacargo as descripcion 
		,a.montoTotalNotaCargo as monto
		,a.numeroNotaCargo as documento
		,a.idCliente		
		,a.id_empresa
	from ".$_SESSION['bdEmpresa'].".cj_cc_notadecargo a
	where idDepartamentoOrigenNotaCargo in(0,1,3)";
	if($idTran != 0){
		$SqlStr.=" and a.idNotaCargo= ".$idTran;
	}else{
		$SqlStr.=" and a.fechaRegistroNotaCargo between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$icomprobant=0;							
	
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto=$row[4];
		$documento = $row[5];
		$idcliente = $row[6];
		//$id_motivo = $row[7];
		$sucursal = $row[7];
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){  
			//$cuentaCXC = buscarContable(17,$idcliente,$sucursal);	   
			$cuentaMov =  buscarContable(57,$id_motivo,$sucursal);
			
			$SqlMulti = "SELECT a.id_motivo
					,a.cantidad
					,a.precio_unitario
					,(a.cantidad*a.precio_unitario) as precio
					FROM ".$_SESSION['bdEmpresa'].".cj_cc_nota_cargo_detalle_motivo a
					WHERE a.id_nota_cargo = ".$id;
			
			$execM =  EjecutarExec($con,$SqlMulti) or die($SqlMulti." " .mysql_error()); 
			
			$montoH = 0;
			
			while ($rowM = ObtenerFetch($execM)) {
				$id_motivo = $rowM[0];
				$precio = $rowM[3];
				
				$cuentaCXC = buscarContable(17,$idcliente,$sucursal);
				
				$montoH = $montoH+$precio;
				
				ingresarRenglon($cuentaCXC,$descripcion,$precio,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			
			//ingresarRenglon($cuentaCXC,$descripcion,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			//ingresarRenglon($cuentaMov  ,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			}
			ingresarRenglon($cuentaMov  ,$descripcion,0,$montoH,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
		}					
	}
}

/*F 32*/
//************************NOTAS DE CARGO CXC VEHICULOS*******************************
function generarNotasCargoVe($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
$ct='15';
$dt='08';
$cc='02';
	
	$SqlStr = "SELECT
		a.idNotaCargo
		,a.fechaRegistroNotaCargo 
		,'02' as centrocosto
		,a.observacionnotacargo as descripcion 
		,a.montoTotalNotaCargo as monto
		,a.numeroNotaCargo as documento
		,a.idCliente		
		,a.id_empresa
	FROM ".$_SESSION['bdEmpresa'].".cj_cc_notadecargo a
	WHERE idDepartamentoOrigenNotaCargo = 2";
	if($idTran != 0){
		$SqlStr.=" AND a.idNotaCargo = ".$idTran;
	}else{
		$SqlStr.=" AND a.fechaRegistroNotaCargo BETWEEN '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	
	$icomprobant = 0;	
	
							
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto = $row[4];
		$documento = $row[5];
		$idcliente = $row[6];
		//$id_motivo = $row[7];
		$sucursal = $row[7];
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){    
			//$cuentaCXC = buscarContable(19,$idcliente,$sucursal);	   
			$cuentaMov =  buscarContable(55,$id_motivo,$sucursal);
			
			$SqlMulti = "SELECT a.id_motivo
					,a.cantidad
					,a.precio_unitario
					,(a.cantidad*a.precio_unitario) as precio
					FROM ".$_SESSION['bdEmpresa'].".cj_cc_nota_cargo_detalle_motivo a
					WHERE a.id_nota_cargo = ".$id;
			
			$execM =  EjecutarExec($con,$SqlMulti) or die($SqlMulti." " .mysql_error()); 
			
			$montoH = 0;
			
			while ($rowM = ObtenerFetch($execM)) {
				$id_motivo = $rowM[0];
				$precio = $rowM[3];
				
				$cuentaCXC = buscarContable(19,$idcliente,$sucursal);
				
				$montoH = $montoH+$precio;
				
				ingresarRenglon($cuentaCXC,$descripcion,$precio,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			
			//ingresarRenglon($cuentaCXC,$descripcion,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			//ingresarRenglon($cuentaMov,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			}
			ingresarRenglon($cuentaMov,$descripcion,0,$montoH,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
		}					
	}
}

/*F 33*/
//****************NOTAS DE CARGO CP REPUESTOS Y SERVICIOS****************************
function generarNotasCargoCpRe($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();

$ct='16';
$dt='09';
$cc='04';	
	$SqlStr = "select a.id_NotaCargo
		,a.fecha_origen_notacargo 
		,'02' as centrocosto
		,a.observacion_notacargo as descripcion 
		,a.subtotal_NotaCargo as monto
		,a.numero_NotaCargo as documento
		,a.id_proveedor
		,a.monto_exento_notacargo
		,a.id_empresa
	from ".$_SESSION['bdEmpresa'].".cp_notadecargo a
	where id_Modulo in(0,1)";
	if($idTran != 0){
		$SqlStr.=" and a.id_NotaCargo= ".$idTran;
	}else{
		$SqlStr.=" and a.fecha_origen_notacargo  between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$icomprobant=0;							
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
	
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto=$row[4];
		$documento = $row[5];
		$idproveedor = $row[6];
		//$id_motivo = $row[7];
		$monto_exento_notacargo= $row[7];
		$sucursal = $row[8];
		if($monto==0){
			$monto = $monto_exento_notacargo;
		}		
		
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){    
//			$cuentaMov =  buscarContable(58,$id_motivo,$sucursal);// vehiculo 56
			$cuentaCXP = buscarContable(14,$idproveedor,$sucursal); // vehiculo 13
			
			$SqlMulti = "SELECT a.id_motivo
					,a.cantidad
					,a.precio_unitario
					,(a.cantidad*a.precio_unitario) as precio
					FROM ".$_SESSION['bdEmpresa'].".cp_notacargo_detalle_motivo a
					WHERE a.id_notacargo = ".$id;
			
			$execM =  EjecutarExec($con,$SqlMulti) or die($SqlMulti." " .mysql_error()); 
			
			$montoH = 0;
			
			while ($rowM = ObtenerFetch($execM)) {
				$id_motivo = $rowM[0];
				$precio = $rowM[3];
				
				$cuentaMov =  buscarContable(58,$id_motivo,$sucursal);
				ingresarRenglon($cuentaMov,$descripcion,$precio,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				
				
				$montoH = $montoH+$precio;
			}
			
			//ingresarRenglon($cuentaMov,$descripcion."-".$id_motivo,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			//ingresarRenglon($cuentaCXP,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			
			
			ingresarRenglon($cuentaCXP,$descripcion,0,$montoH,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			
		}			
	}
}

/*F 34*/
//***********************NOTAS DE CARGO CP VEHICULOS*********************************
function generarNotasCargoCpVe($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();

$ct='16';
$dt='09';
$cc='02';	

	$SqlStr = "SELECT
		a.id_NotaCargo
		,a.fecha_origen_notacargo 
		,'02' as centrocosto
		,a.observacion_notacargo as descripcion 
		,a.subtotal_NotaCargo as monto
		,a.numero_NotaCargo as documento
		,a.id_proveedor		
		,a.monto_exento_notacargo
		,a.id_empresa
		,(SELECT nombre FROM ".$_SESSION['bdEmpresa'].".cp_proveedor x3 WHERE x3.id_proveedor = a.id_proveedor) AS desproveedor
	FROM ".$_SESSION['bdEmpresa'].".cp_notadecargo a
	WHERE id_modulo = 2";
	if($idTran != 0){
		$SqlStr.=" AND a.id_NotaCargo = ".$idTran;
	}else{
		$SqlStr.=" AND a.fecha_origen_notacargo BETWEEN '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	
	$icomprobant = 0;
	
	
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto = $row[4];
		$documento = $row[5];
		$idproveedor = $row[6];
		//$id_motivo = $row[7];
		$monto_exento_notacargo = $row[7];
		$sucursal = $row[8];
		$desproveedor = $row[9];
		
		if($monto == 0){
			$monto = $monto_exento_notacargo;
		}	
	
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){  
			//$cuentaMov = buscarContable(56,$id_motivo,$sucursal); // 56 = Motivos  CXP
			$cuentaCXP = buscarContable(13,$idproveedor,$sucursal); // vehiculo 13
			
			$SqlMulti = "SELECT a.id_motivo
					,a.cantidad
					,a.precio_unitario
					,(a.cantidad*a.precio_unitario) as precio
					FROM ".$_SESSION['bdEmpresa'].".cp_notacargo_detalle_motivo a
					WHERE a.id_notacargo = ".$id;
			
			$execM =  EjecutarExec($con,$SqlMulti) or die($SqlMulti." " .mysql_error()); 
			
			$montoH = 0;
			
			while ($rowM = ObtenerFetch($execM)) {
				$id_motivo = $rowM[0];
				$precio = $rowM[3];
				
				$cuentaMov = buscarContable(56,$id_motivo,$sucursal);
				
				ingresarRenglon($cuentaMov,$descripcion,$precio,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
								
				$montoH = $montoH+$precio;
			}
			
			//ingresarRenglon($cuentaMov,$descripcion.' / '.$desproveedor,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			//ingresarRenglon($cuentaCXP,$descripcion.' / '.$desproveedor,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			
			ingresarRenglon($cuentaCXP,$descripcion,0,$montoH,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
		}
	}
}

/*F 35*/
//***********************NOTAS DE CARGO CP ADMINISTRACION***************************
function generarNotasCargoCpAd($idTran=0,$Desde="",$Hasta=""){
	$con = ConectarBD();
	$ct='16';
	$dt='09';
	$cc='01';
	
	$SqlStr = "select
		a.id_NotaCargo
		,a.fecha_origen_notacargo 
		,'01' as centrocosto
		,a.observacion_notacargo as descripcion 
		,a.subtotal_NotaCargo as monto
		,a.numero_NotaCargo as documento
		,a.id_proveedor		
		,a.monto_exento_notacargo
		,a.id_empresa
	from ".$_SESSION['bdEmpresa'].".cp_notadecargo a
	where id_Modulo = 3";
	if($idTran != 0){
		$SqlStr.=" and a.id_NotaCargo= ".$idTran;
	}else{
		$SqlStr.=" and a.fecha_origen_notacargo  between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$icomprobant=0;				
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto=$row[4];
		$documento = $row[5];
		$idproveedor = $row[6];
		//$id_motivo = $row[7];
		$monto_exento_notacargo= $row[7];
		$sucursal = $row[8];
		if($monto==0){
			$monto = $monto_exento_notacargo;
		}
		
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){   
			//$cuentaMov =  buscarContable(59,$id_motivo,$sucursal);
			$cuentaCXP = buscarContable(16,$idproveedor,$sucursal);	
			
			$SqlMulti = "SELECT a.id_motivo
					,a.cantidad
					,a.precio_unitario
					,(a.cantidad*a.precio_unitario) as precio
					FROM ".$_SESSION['bdEmpresa'].".cp_notacargo_detalle_motivo a
					WHERE a.id_notacargo = ".$id;
			
			$execM =  EjecutarExec($con,$SqlMulti) or die($SqlMulti." " .mysql_error()); 
			
			$montoH = 0;
			
			while ($rowM = ObtenerFetch($execM)) {   
				$id_motivo = $rowM[0];
				$precio = $rowM[3];
			
				$cuentaMov =  buscarContable(59,$id_motivo,$sucursal);
				
				ingresarRenglon($cuentaMov,$descripcion,$precio,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
								
				$montoH = $montoH+$precio;
			}
							
			//ingresarRenglon($cuentaMov,$descripcion,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			//ingresarRenglon($cuentaCXP,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			
			ingresarRenglon($cuentaCXP,$descripcion,0,$montoH,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
		}			
	}
}

/*F 36*/

//***********************NOTAS DE CREDITO CP ADMINISTRACION**************************
function generarNotasCreditoCpAd($idTran=0,$Desde="",$Hasta=""){
	$con = ConectarBD();
	$ct='17';
	$dt='10';
	$cc='01';

	$SqlStr = "SELECT
		a.id_notacredito
		,a.fecha_registro_notacredito 
		,'01' as centrocosto
		,a.observacion_notacredito as descripcion
		,a.subtotal_notacredito as monto
		,a.numero_nota_credito as documento
		,a.id_proveedor
		,a.id_motivo
		,(SELECT cuenta FROM detalleintegracion x1 WHERE x1.idencabezado = 6 AND x1.idobjeto = 3 AND x1.sucursal = 1) AS cuentaiva
		,(SELECT SUM(b.subtotal_iva_notacredito)
			FROM ".$_SESSION['bdEmpresa'].".cp_notacredito_iva b
			WHERE b.id_notacredito = a.id_notacredito) AS subtotal_iva_notacredito
		,a.id_empresa
	FROM
		".$_SESSION['bdEmpresa'].".cp_notacredito a
	WHERE
		a.id_departamento_notacredito = 3";
		//,IFNULL((SELECT subtotal_iva_notacredito FROM ".$_SESSION['bdEmpresa'].".cp_notacredito_iva b WHERE a.id_notacredito = b.id_notacredito),0) AS subtotal_iva_notacredito
	if($idTran != 0){
		$SqlStr.=" AND a.id_notacredito = ".$idTran;
	}else{
		$SqlStr.=" AND a.fecha_registro_notacredito between '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}

	$icomprobant = 0;
	
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
	
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0]; //id_notacredito
		$fecha = $row[1]; //fecha_registro_notacredito
		$descripcion = $row[3]; //descripcion
		$monto = $row[4]; //monto
		$documento = $row[5]; //documento
		$idproveedor = $row[6]; //id_proveedor
		$id_motivo = $row[7]; //id_motivo
		//$cuentaiva = $row[8]; //cuentaiva
		$iva = $row[9]; //subtotal_iva_notacredito
		$sucursal = $row[10]; //id_empresa
				
		$cuentaiva = buscarContable(6,3,$sucursal);
		
		$montoTotal = $monto + $iva;
		
		if(buscarDoc($id,$cc,$ct,$dt,$fecha) == 0){
			//$cuentaMov = buscarContable(61,$id_motivo,$sucursal); // 61 = Notas de credito CxP
			$cuentaCXP = buscarContable(16,$idproveedor,$sucursal);	 // 16 = CXP Administrativo  
			
				$SqlMulti = "SELECT a.id_motivo, a. cantidad, a.precio_unitario,(a.cantidad*a.precio_unitario) as tp 
					FROM ".$_SESSION['bdEmpresa'].".cp_notacredito_detalle_motivo a where id_notacredito = ".$id;
			
				$execM =  EjecutarExec($con,$SqlMulti) or die($SqlMulti." " .mysql_error()); 
				
				$montoH = 0;
				
				if (NumeroFilas($execM)>0){//tiene motivo							
					while($rowM = ObtenerFetch($execM)){ 					
						$id_motivo = $rowM[0];
						$precio = $rowM[3];
						
						$cuentaMov = buscarContable(61,$id_motivo,$sucursal);
							
						ingresarRenglon($cuentaMov,$descripcion,0,$precio,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);									
						$montoH = $montoH+$precio;					
					}
					ingresarRenglon($cuentaCXP,$descripcion,$montoH,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				}else{ //no tiene motivo
						$cuentaMov = buscarContable(61,$id_motivo,$sucursal);
						ingresarRenglon($cuentaMov,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						ingresarRenglon($cuentaCXP,$descripcion,$montoTotal+$montoH,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				}														
				//ingresarRenglon($cuentaCXP,$descripcion."2.1",$montoTotal,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
		/*	ingresarRenglon($cuentaiva,$descripcion,0,$iva,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			ingresarRenglon($cuentaMov,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);		
		}
	}*/
			ingresarRenglon($cuentaiva,$descripcion,0,$iva,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			//ingresarRenglon($cuentaMov,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);							
			
		}
	}
}
/*F 37*/
//********************NOTAS DE CREDITO CP REPUESTOS*********************************
function generarNotasCreditoCpRe($idTran=0,$Desde="",$Hasta=""){
	$con = ConectarBD();
	$ct='17';
	$dt='10';
	$cc='04';

	$SqlStr = "SELECT
		a.id_notacredito
		,a.fecha_registro_notacredito
		,'04' as centrocosto
		,a.observacion_notacredito AS descripcion
		,a.subtotal_notacredito AS monto
		,a.numero_nota_credito AS documento
		,a.id_proveedor
		,a.id_motivo
		,(SELECT cuenta FROM detalleintegracion x1 WHERE x1.idencabezado = 23 AND x1.idobjeto = 3 AND x1.sucursal = 1) AS cuentaiva
		,IFNULL((SELECT SUM(subtotal_iva_notacredito) FROM ".$_SESSION['bdEmpresa'].".cp_notacredito_iva b WHERE a.id_notacredito = b.id_notacredito),0) AS subtotal_iva_notacredito
		,a.id_empresa
		,'' AS cuentacontable
		,a.id_documento
	FROM
		".$_SESSION['bdEmpresa'].".cp_notacredito a
	WHERE
		a.id_departamento_notacredito = 0";
	if($idTran != 0){
		$SqlStr.=" AND a.id_notacredito = ".$idTran;
	}else{
		$SqlStr.=" AND a.fecha_registro_notacredito between '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$icomprobant = 0;
	$montoGasto = 0;			
	
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto = $row[4];
		$documento = $row[5];
		$idproveedor = $row[6];
		$id_motivo = $row[7];
		//$cuentaiva = $row[8];
		$iva = $row[9];
		$sucursal = $row[10];
		$idDocumento = $row[12];
		
		$cuentaiva = buscarContable(23,3,$sucursal);
		
		if(!is_null($idDocumento)){ // SI POSEE FACTURA ASOCIADA		
			$SqlStr = "SELECT monto, id_gasto FROM ".$_SESSION['bdEmpresa'].".cp_factura_gasto WHERE id_factura = ".$idDocumento;
			$icomprobant = 0;
			$exec2 = EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
			
			$montoGasto = 0;
						
			while ($row = ObtenerFetch($exec2)) {
				
				if (is_null($cuentacontable) || $cuentacontable == ''){
					$cuentacontable = buscarContable(73,$idGasto,$sucursal);
				}
				
				if (NumeroFilas($exec2)>0){
					$montoGasto = $montoGasto + $row[0];
				}
			}
			
			if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
				ingresarRenglon($cuentacontable,$descripcion,0,$montoGasto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			}
			
			$SqlStr = "SELECT
				SUM(b.monto) as suma_monto_gasto
			FROM
				".$_SESSION['bdEmpresa'].".cp_notacredito a
				INNER JOIN ".$_SESSION['bdEmpresa'].".cp_factura_gasto b ON (a.id_documento = b.id_factura)
			WHERE id_departamento_notacredito = 0";
			if($idTran != 0){
				$SqlStr.=" AND a.id_notacredito = ".$idTran." AND a.id_documento = ".$idDocumento;
			}else{
				$SqlStr.=" AND a.fecha_registro_notacredito between '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."' AND a.id_documento = ".$idDocumento;
			}
			$icomprobant = 0;
			$exec1 = EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
		
			while ($row = ObtenerFetch($exec1)) {
				$sumaMontoGasto = $row[0];
				$montoTotal = $monto + $iva + $sumaMontoGasto;
			}
		}else{ // SI NO POSEE FACTURA ASOCIADA
			$montoTotal = $row[4] + $iva;
		}
		
		if(buscarDoc($id,$cc,$ct,$dt,$fecha) == 0){
			//$cuentaMov = buscarContable(62,$id_motivo,$sucursal);
			$cuentaCXP = buscarContable(14,$idproveedor,$sucursal);
			
			//ingresarRenglon($cuentaCXP,$descripcion,$montoTotal,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			ingresarRenglon($cuentaiva,$descripcion,0,$iva,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			//ingresarRenglon($cuentaMov,$descripcion."_cuentaMov",0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			
			//multimotivos
			
			//if(!is_null($id_motivo)){			
			$SqlMulti = "SELECT a.id_motivo, a. cantidad, a.precio_unitario,(a.cantidad*a.precio_unitario) as tp 
					FROM ".$_SESSION['bdEmpresa'].".cp_notacredito_detalle_motivo a where id_notacredito = ".$id;
			
			$execM =  EjecutarExec($con,$SqlMulti) or die($SqlMulti." " .mysql_error()); 
			
			$montoH = 0;
			
			if (NumeroFilas($execM)>0){//tiene motivo							
				while($rowM = ObtenerFetch($execM)){ 
					$id_motivo = $rowM[0];
					$precio = $rowM[3];
				
					$cuentaMov = buscarContable(62,$id_motivo,$sucursal);
					
					//a $precio se le resta el $montoGasto porque el asiento quedaba descuadrado...
					//ingresarRenglon($cuentaMov,$descripcion.$id_motivo,0,$precio-$montoGasto-$iva,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					
					//prueba...
					$preciot = $precio - $montoGasto;
					$preciot = $preciot - $iva;
					
					ingresarRenglon($cuentaMov,$descripcion.$id_motivo,0,$preciot,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					$montoH = $montoH+$precio;					
				}
				ingresarRenglon($cuentaCXP,$descripcion,$montoH,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			}else{ //no tiene motivo
				$cuentaMov = buscarContable(62,$id_motivo,$sucursal);
				ingresarRenglon($cuentaMov,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				ingresarRenglon($cuentaCXP,$descripcion,$montoTotal+$montoH,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			}
			//ingresarRenglon($cuentaCXP,$descripcion."_CXP",$montoTotal+$montoH,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		}						
	}
}

/*F 38*/
//********************NOTAS DE CREDITO CP SERVICIOS*********************************
function generarNotasCreditoCpSe($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
	$ct='17';
	$dt='10';
	$cc='03';	
	$SqlStr = "select a.id_notacredito
		,a.fecha_registro_notacredito 
		,'03' as centrocosto
		,a.observacion_notacredito as descripcion 
		,a.subtotal_notacredito as monto
		,a.numero_nota_credito as documento
		,a.id_proveedor
		,a.id_motivo
		,a.id_empresa
		,(select cuenta from detalleintegracion x1 where x1.idencabezado = 24 and x1.idobjeto = 3 and x1.sucursal = 1) as cuentaiva
		,IFNULL((select SUM(subtotal_iva_notacredito) from ".$_SESSION['bdEmpresa'].".cp_notacredito_iva b where a.id_notacredito = b.id_notacredito),0) AS subtotal_iva_notacredito
	from ".$_SESSION['bdEmpresa'].".cp_notacredito a
	where id_departamento_notacredito = 1";
	if($idTran != 0){
		$SqlStr.=" and a.id_notacredito= ".$idTran;
	}else{
		$SqlStr.=" and a.fecha_registro_notacredito  between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$icomprobant=0;							
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto=$row[4];
		$documento = $row[5];
		$idproveedor = $row[6];
		//$id_motivo = $row[7];
		$sucursal = $row[8];
		//$cuentaiva = $row[9];
		$iva=$row[10];
		$montoTotal=$row[4]+$iva;
		
		$cuentaiva = buscarContable(24,3,$sucursal);
		
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){  
			$cuentaMov =  buscarContable(63,$id_motivo,$sucursal);
			//$cuentaCXP = buscarContable(15,$idproveedor,$sucursal);	   
			
			//ingresarRenglon($cuentaCXP,$descripcion,$montoTotal,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			
			$SqlMulti = "SELECT a.id_motivo, a. cantidad, a.precio_unitario,(a.cantidad*a.precio_unitario) as tp 
					FROM ".$_SESSION['bdEmpresa'].".cp_notacredito_detalle_motivo a where id_notacredito = ".$id;
			
			$execM =  EjecutarExec($con,$SqlMulti) or die($SqlMulti." " .mysql_error()); 
			
			$montoH = 0;
			
			while ($rowM = ObtenerFetch($execM)) { 
				$id_motivo = $rowM[0];
				$precio = $rowM[3];
			
				$cuentaCXP = buscarContable(15,$idproveedor,$sucursal);
				
				ingresarRenglon($cuentaCXP,$descripcion,$precio,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
								
				$montoH = $montoH+$precio;
			}			
			
			ingresarRenglon($cuentaiva,$descripcion,0,$iva,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			//ingresarRenglon($cuentaMov,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			ingresarRenglon($cuentaMov,$descripcion,0,$montoH,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
		}				
	}
}

/*F 39*/
//********************NOTAS DE CREDITO CP VEHICULOS*********************************
function generarNotasCreditoCpVe($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
	$ct='17';
	$dt='10';
	$cc='02';	
	$SqlStr = "select
		a.id_notacredito
		,a.fecha_registro_notacredito 
		,'02' as centrocosto
		,a.observacion_notacredito as descripcion 
		,a.subtotal_notacredito as monto
		,a.numero_nota_credito as documento
		,a.id_proveedor
		,a.id_motivo
		,a.id_empresa
		,(select cuenta from detalleintegracion x1 where x1.idencabezado = 22 and x1.idobjeto = 3 and x1.sucursal = 1) as cuentaiva
		,IFNULL((select SUM(subtotal_iva_notacredito) from ".$_SESSION['bdEmpresa'].".cp_notacredito_iva b where a.id_notacredito = b.id_notacredito),0) AS subtotal_iva_notacredito
	from ".$_SESSION['bdEmpresa'].".cp_notacredito a
	where id_departamento_notacredito = 2";
	if($idTran != 0){
		$SqlStr.=" and a.id_notacredito= ".$idTran;
	}else{
		$SqlStr.=" and a.fecha_registro_notacredito  between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$icomprobant=0;	
							
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto=$row[4];
		$documento = $row[5];
		$idproveedor = $row[6];
		//$id_motivo = $row[7];
		$sucursal = $row[8];
		//$cuentaiva = $row[9];
		$iva=$row[10];
		$montoTotal=$row[4]+$iva;
		
		$cuentaiva = buscarContable(22,3,$sucursal);
		
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){ 
			$cuentaMov =  buscarContable(64,$id_motivo,$sucursal);
			//$cuentaCXP = buscarContable(13,$idproveedor,$sucursal);	   
			//ingresarRenglon($cuentaCXP,$descripcion."hvhhhvvh",$montoTotal,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			
			$SqlMulti = "SELECT a.id_motivo, a. cantidad, a.precio_unitario,(a.cantidad*a.precio_unitario) as tp 
					FROM ".$_SESSION['bdEmpresa'].".cp_notacredito_detalle_motivo a where id_notacredito = ".$id;
			
			$execM =  EjecutarExec($con,$SqlMulti) or die($SqlMulti." " .mysql_error()); 
			
			$montoH = 0;
			
			while ($rowM = ObtenerFetch($execM)) { 
				$id_motivo = $rowM[0];
				$precio = $rowM[3];
			
				$cuentaCXP = buscarContable(13,$idproveedor,$sucursal);
				
				ingresarRenglon($cuentaCXP,$descripcion,$precio,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
								
				$montoH = $montoH+$precio;
			}
			

			ingresarRenglon($cuentaiva,$descripcion,0,$iva,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			//ingresarRenglon($cuentaMov,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			ingresarRenglon($cuentaMov,$descripcion,0,$montoH,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
		}				
	}
}

/*F 40*/
//***********************COMISIONES BANCARIAS***************************************
function generarComisionesBancarias($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
							$ct='14';
							$dt='05';
							$cc='05';	
							$Desde1=date('Y-m-d',strtotime($Desde));
							$Hasta1=date('Y-m-d',strtotime($Hasta));
							
							// VERIFICA VALORES DE CONFIGURACION (Consulta el Pais del sistema)
							$queryConfig403 = "SELECT valor FROM ".$_SESSION['bdEmpresa'].".pg_configuracion_empresa config_emp
								INNER JOIN ".$_SESSION['bdEmpresa'].".pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
							WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = 1";
							$rsConfig403 =  EjecutarExec($con,$queryConfig403) or die($queryConfig403." " .mysql_error()); 
							$rowConfig403 = ObtenerFetch($rsConfig403);
							$valor = $rowConfig403['0'];// 1 = Venezuela, 2 = Panama, 3 = Puero Rico
							
							if ($valor == 1) { // VENEZUELA
								$SqlStr = "SELECT
									FechaPago,
									idFormaPago,
									tipo_pago, 
									id_cuenta,
									banco_destino, 
									SUM(monto) as monto,
									comision,
									islr
								FROM
									(
										SELECT   
												p.fechapago as FechaPago,
												p.formaPago AS idFormaPago,
												(SELECT f.nombreFormaPago FROM ".$_SESSION['bdEmpresa'].".formapagos f WHERE f.idFormaPago= p.formaPago) AS tipo_pago,
												(SELECT c.idCuentas FROM ".$_SESSION['bdEmpresa'].".cuentas c WHERE c.idBanco= p.bancoDestino and p.cuentaEmpresa = c.numeroCuentaCompania LIMIT 1) AS id_cuenta,
												(SELECT b.nombreBanco FROM ".$_SESSION['bdEmpresa'].".bancos b WHERE b.idBanco= p.bancoDestino LIMIT 1) AS banco_destino,
												SUM(montopagado) AS monto,
												IFNULL(SUM(montopagado * 
													(SELECT rp.porcentaje_comision FROM ".$_SESSION['bdEmpresa'].".te_retencion_punto rp WHERE rp.id_retencion_punto= 
														(SELECT rpp.id_retencion_punto FROM ".$_SESSION['bdEmpresa'].".cj_cc_retencion_punto_pago rpp WHERE rpp.id_pago= p.idPago
														AND rpp.id_tipo_documento= 1)
													)
												) / 100, 0.00) AS comision,
												IFNULL(
													SUM(
														(montopagado / (1 + (SELECT iva FROM ".$_SESSION['bdEmpresa'].".pg_iva WHERE tipo= 6 AND activo= 1) /100)) *
															(SELECT rp.porcentaje_islr FROM ".$_SESSION['bdEmpresa'].".te_retencion_punto rp WHERE rp.id_retencion_punto= 
																(SELECT rpp.id_retencion_punto FROM ".$_SESSION['bdEmpresa'].".cj_cc_retencion_punto_pago rpp WHERE rpp.id_pago= p.idPago
																AND rpp.id_tipo_documento= 1)
															)
														) / 100, 0.00) AS islr
										FROM 
												".$_SESSION['bdEmpresa'].".sa_iv_pagos p, ".$_SESSION['bdEmpresa'].".cj_cc_retencion_punto_pago pp
										WHERE
												p.fechapago between '$Desde1' and '$Hasta1'
												AND p.formaPago IN (5, 6)
												AND p.idPago= pp.id_pago
												AND pp.id_caja= 2
										GROUP BY
												p.fechapago,
												formaPago,
												bancoDestino 
	
									UNION ALL
										SELECT   a.fechaPagoAnticipo as FechaPago,
												(SELECT f.idFormaPago FROM ".$_SESSION['bdEmpresa'].".formapagos f WHERE f.aliasFormaPago= a.tipoPagoDetalleAnticipo) AS idFormaPago,
												(SELECT f.nombreFormaPago FROM ".$_SESSION['bdEmpresa'].".formapagos f WHERE f.aliasFormaPago= a.tipoPagoDetalleAnticipo) AS tipo_pago,
												(SELECT c.idCuentas FROM ".$_SESSION['bdEmpresa'].".cuentas c WHERE c.idBanco= a.bancoCompaniaDetalleAnticipo and a.numeroCuentaCompania = c.numeroCuentaCompania LIMIT 1) AS id_cuenta,
												(SELECT b.nombreBanco FROM ".$_SESSION['bdEmpresa'].".bancos b WHERE b.idBanco= a.bancoCompaniaDetalleAnticipo LIMIT 1) AS banco_destino,
												SUM(montoDetalleAnticipo) AS monto,
												IFNULL(SUM(montoDetalleAnticipo * 
													(SELECT rp.porcentaje_comision FROM ".$_SESSION['bdEmpresa'].".te_retencion_punto rp WHERE rp.id_retencion_punto= 
														(SELECT rpp.id_retencion_punto FROM ".$_SESSION['bdEmpresa'].".cj_cc_retencion_punto_pago rpp WHERE rpp.id_pago= a.idDetalleAnticipo
														AND rpp.id_tipo_documento= 4)
													)
												) / 100, 0.00) AS comision,
												IFNULL(
													SUM(
														(montoDetalleAnticipo / (1 + (SELECT iva FROM ".$_SESSION['bdEmpresa'].".pg_iva WHERE tipo= 6 AND activo= 1) /100)) *
														(SELECT rp.porcentaje_islr FROM ".$_SESSION['bdEmpresa'].".te_retencion_punto rp WHERE rp.id_retencion_punto= 
															(SELECT rpp.id_retencion_punto FROM ".$_SESSION['bdEmpresa'].".cj_cc_retencion_punto_pago rpp WHERE rpp.id_pago= a.idDetalleAnticipo
															AND rpp.id_tipo_documento= 4)
													)
												) / 100, 0.00) AS islr
										FROM 
												".$_SESSION['bdEmpresa'].".cj_cc_detalleanticipo a
										WHERE
												a.fechaPagoAnticipo between '$Desde1' and '$Hasta1'
												AND a.tipoPagoDetalleAnticipo IN ('TC', 'TD')
												AND a.idCaja= 2
										GROUP BY
												a.fechaPagoAnticipo,
												a.tipoPagoDetalleAnticipo,
												a.bancoCompaniaDetalleAnticipo
	
									UNION ALL
	
										SELECT  c.fechapago as FechaPago,
												c.idFormaPago AS idFormaPago,
												(SELECT f.nombreFormaPago FROM ".$_SESSION['bdEmpresa'].".formapagos f WHERE f.idFormaPago= c.idFormaPago) AS tipo_pago,
												(SELECT cu.idCuentas FROM ".$_SESSION['bdEmpresa'].".cuentas cu WHERE cu.idBanco= c.bancoDestino and c.cuentaEmpresa= cu.numeroCuentaCompania LIMIT 1) AS id_cuenta,    
												(SELECT b.nombreBanco FROM ".$_SESSION['bdEmpresa'].".bancos b WHERE b.idBanco= c.bancoDestino LIMIT 1) AS banco_destino,
												SUM(monto_pago) AS monto,
												IFNULL(SUM(monto_pago * 
													(SELECT rp.porcentaje_comision FROM ".$_SESSION['bdEmpresa'].".te_retencion_punto rp WHERE rp.id_retencion_punto= 
														(SELECT rpp.id_retencion_punto FROM ".$_SESSION['bdEmpresa'].".cj_cc_retencion_punto_pago rpp WHERE rpp.id_pago= c.id_det_nota_cargo
														AND rpp.id_tipo_documento= 2)
													)
												) / 100, 0.00) AS comision,
												IFNULL(
													SUM(
														(monto_pago / (1 + (SELECT iva FROM ".$_SESSION['bdEmpresa'].".pg_iva WHERE tipo= 6 AND activo= 1) /100)) *
														(SELECT rp.porcentaje_islr FROM ".$_SESSION['bdEmpresa'].".te_retencion_punto rp WHERE rp.id_retencion_punto= 
															(SELECT rpp.id_retencion_punto FROM ".$_SESSION['bdEmpresa'].".cj_cc_retencion_punto_pago rpp WHERE rpp.id_pago= c.id_det_nota_cargo
															AND rpp.id_tipo_documento= 2)
													)
												) / 100, 0.00) AS islr
										FROM 
												".$_SESSION['bdEmpresa'].".cj_det_nota_cargo c
										WHERE
												c.fechapago between '$Desde1' and '$Hasta1'
												AND c.idFormaPago IN (5, 6)
												AND c.idCaja= 2
										GROUP BY
												c.fechapago,

												c.idFormaPago,
												c.bancoDestino
									) AS tabla
							GROUP BY
									FechaPago,
									tipo_pago, 
									banco_destino
							ORDER BY
									FechaPago,idFormaPago, id_cuenta";
									
							$descC = 'CISLR';
							$descD = 'DISLR';
							$descI = 'ISLR ';
							
						} else if ($valor == 2 || $valor == 3) { // 2 = PANAMA; 3 = PUERTO RICO
							$SqlStr = "SELECT
								fechaPago,
								idFormaPago,
								tipo_pago, 
								id_cuenta,
								banco_destino, 
								SUM(monto) as monto,
								SUM(comision) as comision,
								islr as islr
							FROM
								(SELECT 
									a.fechapago as fechaPago,
									a.formaPago AS idFormaPago,
									(SELECT e.nombreFormaPago FROM ".$_SESSION['bdEmpresa'].".formapagos e 
										WHERE e.idFormaPago = a.formaPago) AS tipo_pago,
									(SELECT f.idCuentas FROM ".$_SESSION['bdEmpresa'].".cuentas f 
										WHERE f.idBanco = a.bancoDestino AND a.cuentaEmpresa = f.numeroCuentaCompania LIMIT 1) AS id_cuenta,
									(SELECT g.nombreBanco FROM ".$_SESSION['bdEmpresa'].".bancos g 
										WHERE g.idBanco = a.bancoDestino LIMIT 1) AS banco_destino,
									SUM(montopagado) AS monto,
									IFNULL(SUM(montopagado * 
										(SELECT rp.porcentaje_comision FROM ".$_SESSION['bdEmpresa'].".te_retencion_punto rp WHERE rp.id_retencion_punto= 
											(SELECT rpp.id_retencion_punto FROM ".$_SESSION['bdEmpresa'].".cj_cc_retencion_punto_pago rpp WHERE rpp.id_pago= a.idPago
											AND rpp.id_tipo_documento = 1
											AND rpp.id_caja = 2)
										)
									) / 100, 0.00) AS comision,
									'1' AS islr
								FROM
									".$_SESSION['bdEmpresa'].".sa_iv_pagos a
								WHERE
									a.fechapago between '$Desde1' and '$Hasta1'
									AND a.formaPago IN (5, 6)
									AND a.idCaja = 2
								GROUP BY
									a.fechapago,
									a.formaPago,
									a.bancoDestino
								
								UNION ALL
									
								SELECT 
									x.fechapago as fechaPago,
									x.formaPago AS idFormaPago,
									(SELECT e.nombreFormaPago FROM ".$_SESSION['bdEmpresa'].".formapagos e 
										WHERE e.idFormaPago = x.formaPago) AS tipo_pago,
									(SELECT f.idCuentas FROM ".$_SESSION['bdEmpresa'].".cuentas f 
										WHERE f.idBanco = x.bancoDestino AND x.cuentaEmpresa = f.numeroCuentaCompania LIMIT 1) AS id_cuenta,
									(SELECT g.nombreBanco FROM ".$_SESSION['bdEmpresa'].".bancos g 
										WHERE g.idBanco = x.bancoDestino LIMIT 1) AS banco_destino,
									SUM(montopagado) AS monto,
									IFNULL(SUM(montopagado * 
										(SELECT rp.porcentaje_comision FROM ".$_SESSION['bdEmpresa'].".te_retencion_punto rp WHERE rp.id_retencion_punto= 
											(SELECT rpp.id_retencion_punto FROM ".$_SESSION['bdEmpresa'].".cj_cc_retencion_punto_pago rpp WHERE rpp.id_pago= x.idPago
											AND rpp.id_tipo_documento = 1
											AND rpp.id_caja = 1)
										)
									) / 100, 0.00) AS comision,
									'1' AS islr
								FROM
									".$_SESSION['bdEmpresa'].".an_pagos x
								WHERE
									x.fechapago between '$Desde1' and '$Hasta1'
									AND x.formaPago IN (5, 6)
									AND x.idCaja = 1
								GROUP BY
									x.fechapago,
									x.formaPago,
									x.bancoDestino
								
								UNION ALL
								
								SELECT 
									b.fechaPagoAnticipo as fechaPago,
									(SELECT d.idFormaPago FROM ".$_SESSION['bdEmpresa'].".formapagos d WHERE d.aliasFormaPago = b.tipoPagoDetalleAnticipo) AS idFormaPago,
									(SELECT e.nombreFormaPago FROM ".$_SESSION['bdEmpresa'].".formapagos e 
										WHERE e.aliasFormaPago = b.tipoPagoDetalleAnticipo) AS tipo_pago,
									(SELECT f.idCuentas FROM ".$_SESSION['bdEmpresa'].".cuentas f 
										WHERE f.idBanco= b.bancoCompaniaDetalleAnticipo AND b.numeroCuentaCompania = f.numeroCuentaCompania LIMIT 1) AS id_cuenta,
									(SELECT g.nombreBanco FROM ".$_SESSION['bdEmpresa'].".bancos g 
										WHERE g.idBanco = b.bancoCompaniaDetalleAnticipo LIMIT 1) AS banco_destino,
									SUM(montoDetalleAnticipo) AS monto,
									IFNULL(SUM(montoDetalleAnticipo * 
										(SELECT rp.porcentaje_comision FROM ".$_SESSION['bdEmpresa'].".te_retencion_punto rp WHERE rp.id_retencion_punto= 
											(SELECT rpp.id_retencion_punto FROM ".$_SESSION['bdEmpresa'].".cj_cc_retencion_punto_pago rpp WHERE rpp.id_pago= b.idDetalleAnticipo
											AND rpp.id_tipo_documento = 4)
										)
									) / 100, 0.00) AS comision,
									'1' AS islr
								FROM
									".$_SESSION['bdEmpresa'].".cj_cc_detalleanticipo b
								WHERE
									b.fechaPagoAnticipo between '$Desde1' and '$Hasta1'
									AND b.tipoPagoDetalleAnticipo IN ('TC', 'TD')
									AND b.idCaja IN (1,2)
								GROUP BY
									b.fechaPagoAnticipo,
									b.tipoPagoDetalleAnticipo,
									b.bancoCompaniaDetalleAnticipo
								
								UNION ALL
								
								SELECT 
									c.fechapago as fechaPago,
									c.idFormaPago AS idFormaPago,
									(SELECT e.nombreFormaPago FROM ".$_SESSION['bdEmpresa'].".formapagos e 
										WHERE e.idFormaPago = c.idFormaPago) AS tipo_pago,
									(SELECT f.idCuentas FROM ".$_SESSION['bdEmpresa'].".cuentas f 
										WHERE f.idBanco = c.bancoDestino and c.cuentaEmpresa = f.numeroCuentaCompania LIMIT 1) AS id_cuenta,    
									(SELECT g.nombreBanco FROM ".$_SESSION['bdEmpresa'].".bancos g 
										WHERE g.idBanco = c.bancoDestino LIMIT 1) AS banco_destino, 
									SUM(monto_pago) AS monto,
									IFNULL(SUM(monto_pago * 
										(SELECT rp.porcentaje_comision FROM ".$_SESSION['bdEmpresa'].".te_retencion_punto rp WHERE rp.id_retencion_punto= 
											(SELECT rpp.id_retencion_punto FROM ".$_SESSION['bdEmpresa'].".cj_cc_retencion_punto_pago rpp WHERE rpp.id_pago= c.id_det_nota_cargo
											AND rpp.id_tipo_documento = 2)
										)
									) / 100, 0.00) AS comision,
									'1' AS islr
								FROM
									".$_SESSION['bdEmpresa'].".cj_det_nota_cargo c
								WHERE
									c.fechapago between '$Desde1' and '$Hasta1'
									AND c.idFormaPago IN (5, 6)
									AND c.idCaja IN (1,2)
								GROUP BY
									c.fechapago,
									c.idFormaPago,
									c.bancoDestino) AS tabla
							GROUP BY
									fechaPago,
									tipo_pago, 
									banco_destino
							ORDER BY
									fechaPago,idFormaPago,id_cuenta";
									
							$descC = 'CITBMS';
							$descD = 'DITBMS';
							$descI = 'ITBMS ';
						}
						$icomprobant = 0;					

						$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
							while ($row = ObtenerFetch($exec)) {
								$icomprobant++;
								$fecha = $row[0];
								$id = $row[1];// id forma de pago
								$tipo_pago = $row[2];
								$idcuenta = $row[3];
								
								$fechaC = explode("-", $fecha);
								
								$codig = $fechaC['2']."-".$fechaC['1']."-".substr($fechaC['0'],2,4);
							  
							  	//$codig = array_reverse(explode("-", $fecha));
								//$codig[2] = substr($codig[2],2,2);
								//$codig = implode("-", $codig);
								
								if($id == 5){
									$descripcion = "Tarjeta de Credito ";
									$documento="TC-C".$idcuenta."-".$codig;
								}else{
									$descripcion = "Tarjeta de Debito ";
									$documento="TD-C".$idcuenta."-".$codig;
								}
								
								$id_cuenta = $row[3];
								
								if ($valor == 1) { // 1 = VENEZUELA		
									$montoComision = $row[6];
									$islr = $row[7];
								} else if ($valor == 2 || $valor == 3) { // 2 = PANAMA ; 3 = PUERTO RICO
									$montoComision = $row[6];
									$islr = $montoComision * 0.07;
								}
									
								$cuentaMov =  buscarContable(25,49,$sucursal);
								$cuentaBanco = buscarContable(43,$id_cuenta,$sucursal);
								if(buscarDocTe($documento,$cc,$ct,$dt,$fecha)==0){
									ingresarRenglon($cuentaMov,$descripcion,$montoComision,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
									ingresarRenglon($cuentaBanco,$descripcion,0,$montoComision,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
								}
								
								if($islr != 0){
									if($id == 5){//Tarjeta de Credito
										$documento=$descC.$idcuenta."-".$codig;
									}else{//Tarjeta de Debito
										$documento=$descD.$idcuenta."-".$codig;
									}
							
									if(buscarDocTe($documento,$cc,$ct,$dt,$fecha)==0){
										$cuentaMov =  buscarContable(25,16,$sucursal);										
										$descripcion = $descI.$descripcion;
										if($id<>6){
											ingresarRenglon($cuentaMov ,$descripcion,$islr,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
											ingresarRenglon($cuentaBanco,$descripcion,0,$islr,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
										}
									}
								}	
							}
}





//**********************************************************************************
//********************************FUNCIONES CONTABLES*******************************
//**********************************************************************************
/*F A*/
function ingresarRenglon($cuentacontable,$descripcion,$Debe,$Haber,$documento
						,$ct,$dt,$cc,$comprobant,$fecha,$tipo,$im='',$idobject){
$con = ConectarBD();

$descripcion = addslashes(strtoupper($descripcion));
	
    if($Debe != 0 || $Haber !=0){
	    if ($Debe != 0){
		   $tipo = "D";
		}else{
		   $tipo = "H";
		}

		$bguion = strpos($cuentacontable, "-");

		if ($bguion === false ) {
			$cuentacontable = $cuentacontable;
		}else{
			// Tipo accion : creado para identificar la accion de integracion en cada cuenta contable //---L/A----
			$tipo_accion = strstr($cuentacontable, '-',true);
			// ----FIN------------------------------------------------------------------------------------------
			// ----- Modificacion 12/11 para la accion de integracion -----------------------------------L/A----
			$cuentacontable = strstr($cuentacontable, '-');
			$cuentacontable = substr($cuentacontable, 1);
			// ---FIN-------------------------------------------------------------------------------------------
		}

		$SqlStr = "insert into movenviarcontabilidad( 
			  codigo,
			  desripcion,
			  debe,
			  haber,
			  documento,
			  ct,
			  dt,
			  cc,
			  comprobant,
			  fecha,
			  tipo,
			  im,
			  idobject,
			  tipo_accion
		  )
			values 
			('$cuentacontable'
			,'$descripcion'
			,$Debe
			,$Haber
			,'$documento'
			,'$ct'
			,'$dt'
			,'$cc'
			,$comprobant
			,'$fecha'
			,'$tipo'
			,'$im'
			,$idobject
			,'$tipo_accion'
		   )	 
		";
		$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
    }		
}

/*F B*/
function buscarContable($idencabezado,$idobjeto,$sucursal){
	$con = ConectarBD();
	
	//if(is_null($sucursal))
	$sucursal = 1;

/*
if ($sucursal == 2){
	if ($idencabezado<=19)
	$idencabezado = $idencabezado+312; 
	else if ($idencabezado<=43 AND $idencabezado>=21)
	$idencabezado = $idencabezado+311;
	else if ($idencabezado>=45)
	$idencabezado = $idencabezado+310;
}
if ($sucursal == 3){
	if ($idencabezado<=19)
	$idencabezado = $idencabezado+250;
	else if ($idencabezado<=43 AND $idencabezado>=21)
	$idencabezado = $idencabezado+249;
	else if ($idencabezado>=45)
	$idencabezado = $idencabezado+248;
}  
if ($sucursal == 4){
	if ($idencabezado<=19)
	$idencabezado = $idencabezado+64;
	else if ($idencabezado<=43 AND $idencabezado>=21)
	$idencabezado = $idencabezado+63;
	else if ($idencabezado>=45)
	$idencabezado = $idencabezado+62;
}
else if ($sucursal == 5){
	if ($idencabezado<=19)
	$idencabezado = $idencabezado+126;
	else if ($idencabezado<=43 AND $idencabezado>=21)
	$idencabezado = $idencabezado+125;
	else if ($idencabezado>=45)
	$idencabezado = $idencabezado+124;
}
else if ($sucursal == 6){
	if ($idencabezado<=19)
  	$idencabezado = $idencabezado+188;
	else if ($idencabezado<=43 AND $idencabezado>=21)
	$idencabezado = $idencabezado+187;
	else if ($idencabezado>=45)
	$idencabezado = $idencabezado+186;
}
else if ($sucursal == 7){
	if ($idencabezado<=19)
  	$idencabezado = $idencabezado+374;
	else if ($idencabezado<=43 AND $idencabezado>=21)
	$idencabezado = $idencabezado+373;
	else if ($idencabezado>=45)
	$idencabezado = $idencabezado+372;
}*/
	   
	if(! is_null($idobjeto)){  
		$SqlStr = " select cuenta from detalleintegracion x1
		where x1.idencabezado = $idencabezado and x1.idobjeto = $idobjeto and x1.sucursal = $sucursal";	
		$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 	
		if (NumeroFilas($exec)>0){
			$row = ObtenerFetch($exec);
			$retornar = $idencabezado."-".$row[0];
		}else{
			$SqlStr = " select cuentageneral from encintegracionsucursal x1 where x1.id_enc_integracion = $idencabezado and x1.sucursal = $sucursal";
			$exec1 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 	
			$row = ObtenerFetch($exec1);
			$retornar = $idencabezado."-".$row[0]; 
		}
	}else{
		$SqlStr = " select cuentageneral from encintegracionsucursal x1 where x1.id_enc_integracion = $idencabezado and x1.sucursal = $sucursal";		
		$exec1 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 	
		$row = ObtenerFetch($exec1);
		$retornar = $idencabezado."-".$row[0]; 
	} 
   return $retornar;
}

/*F C*/
function buscarDoc($idobject,$cc,$ct,$dt,$fecha){
	$con = ConectarBD();
	$SqlStr = " select count(*) from enviadosacontabilidad where idobject = '$idobject' and cc = '$cc' and ct = '$ct' and dt = '$dt' and fecha = '$fecha'";
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__);
	if (NumeroFilas($exec)>0){
		$row = ObtenerFetch($exec);
		if($row[0] >0){
			$retornar = 1; 
		}else{
			$retornar = 0; 
		}	
	}
	return $retornar;
}

/*F D*/
function buscarDocTe($idobject,$cc,$ct,$dt,$fecha){
	$con = ConectarBD();
	$SqlStr = " select count(*) from enviadosacontabilidad  where documento = '$idobject' and cc = '$cc' and ct = '$ct' and dt = '$dt' and fecha = '$fecha'";
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__);
	if (NumeroFilas($exec)>0){
		$row = ObtenerFetch($exec);
		if($row[0] >0){
			$retornar = 1; 
		}else{
			$retornar = 0; 
		}	
	}
	return $retornar;
}

/*F E*/
function eliminarRenglones(){
	$con = ConectarBD();
	$SqlStr = " delete from movenviarcontabilidad";
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 							
}

/*F F*/
function ingresarEnlacesTerceros($codigo,$fecha,$debe,$haber,$idobjeto
						,$objeto,$camposobjeto,$descripcion){
				return;
			    $con = ConectarBD();//die('prueba');
				$combinado = trim($codigo). "-". trim($idobjeto) ."-". trim($objeto);  
				$SqlStr = "select count(*) from cuentaterceros where combinado = '$combinado'";
 				$exec6 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 						
				$inumero = 0;
				if (NumeroFilas($exec6)>0){
				   $inumero = ObtenerResultado($exec6,1);
				}
	            if ($inumero == 0){
					$SqlStr ="insert into cuentaterceros(
						combinado
						,cuenta
						,idobjeto
						,tabla
						,campos
						,saldo_ant
						,debe
						,haber
						,debe_cierr
						,haber_cierr
						) values (
						'$combinado'
						,'$codigo'
						, $idobjeto 
						,'$objeto'
						,'$camposobjeto' 
						,0
						,0	
						,0 	
						,0
						,0
						)";
					  $exec6 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 	
						
					  $SqlStr= " update cuenta set terceros = 'SI' 
					  where codigo = '$codigo'";		
					  $excTerce = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__);
				}
				
				$SqlStr ="insert into enlaceterceros(
				combinado
                ,cuenta
				,idobjeto 
				,tabla 
			    ,campos
				,fecha 
				,debe 	
				,haber 	
				,descripcion
			) values (
			    '$combinado'
			    ,'$codigo'
				, $idobjeto 
				,'$objeto'
				,'$camposobjeto' 
				,'$fecha' 
				, $debe 	
				, $haber 	
				,'$descripcion'
			)";  
		$exec6 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 						
}

/*F G*/
function ingresarEnlacesTerceros_ter($codigo,$fecha,$debe,$haber,$idobjeto
						,$objeto,$camposobjeto,$descripcion){
			    $con = ConectarBD();
				$combinado = trim($codigo). "-". trim($idobjeto) ."-". trim($objeto);  
				$SqlStr = "select count(*) from cuentaterceros where combinado = '$combinado'";
 				$exec6 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 						
				$inumero = 0;
				if (NumeroFilas($exec6)>0){
				   $inumero = ObtenerResultado($exec6,1);
				}
	            if ($inumero == 0){
					$SqlStr ="insert into cuentaterceros(
						combinado
						,cuenta
						,idobjeto
						,tabla
						,campos
						,saldo_ant
						,debe
						,haber
						,debe_cierr
						,haber_cierr
						) values (
						'$combinado'
						,'$codigo'
						, $idobjeto 
						,'$objeto'
						,'$camposobjeto' 
						,0
						,0	
						,0 	
						,0
						,0
						)";
					  $exec6 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 	
						
					  $SqlStr= " update cuenta set terceros = 'SI' 
					  where codigo = '$codigo'";		
					  $excTerce = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__);
				}
				
				$SqlStr ="insert into enlaceterceros(
				combinado
                ,cuenta
				,idobjeto 
				,tabla 
			    ,campos
				,fecha 
				,debe 	
				,haber 	
				,descripcion
			) values (
			    '$combinado'
			    ,'$codigo'
				, $idobjeto 
				,'$objeto'
				,'$camposobjeto' 
				,'$fecha' 
				, $debe 	
				, $haber 	
				,'$descripcion'
			)";  
		$exec6 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 						
}




//**********************************************************************************
//******************************FUNCIONES PARA TERCEROS*****************************
//**********************************************************************************

//**************************COMPRAS ADMINISTRATIVA****************************************
function generarComprasAd_Ter($idFactura=0,$Desde="",$Hasta=""){
$con = ConectarBD();
$SqlStr = "update parametros set MensajeRet = ''";
$exec = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__);

	$SqlStr = "SELECT
		a.id_factura as factura
		,a.fecha_origen as fecha
		,'01' as centrocosto
		,'' as cuentacontable
		,d.descripcion as descripcion
		,sum(b.cantidad*precio_unitario) AS debe 
		,0 AS haber 
		,a.numero_factura_proveedor AS documento
		,round(ifnull(sum((b.cantidad*b.precio_unitario)*(b.iva/100)),0),2) AS iva 
		,(SELECT cuenta FROM detalleintegracion x1 WHERE x1.idencabezado = 6 AND x1.idobjeto = 3 AND x1.sucursal = 1) AS cuentaiva
		,a.id_proveedor AS proveedor
		,(SELECT concat(nombre) FROM ".$_SESSION['bdEmpresa'].".cp_proveedor x3 WHERE x3.id_proveedor = a.id_proveedor) AS Proveedor
		,a.subtotal_descuento 
		,c.id_subseccion
		,a.id_empresa
	FROM
		".$_SESSION['bdEmpresa'].".cp_factura a
		,".$_SESSION['bdEmpresa'].".cp_factura_detalle b
		,".$_SESSION['bdEmpresa'].".ga_articulos c
		,".$_SESSION['bdEmpresa'].".ga_subsecciones d
	WHERE
		c.id_subseccion = d.id_subseccion
		AND a.id_factura = b.id_factura
		AND b.id_articulo = c.id_articulo
		AND a.id_modulo = 3";
	if($idFactura != 0){
		$SqlStr.=" AND a.id_factura= ".$idFactura;
	}else{
		$SqlStr.=" AND a.fecha_origen between '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$SqlStr.=" group by a.id_factura,c.id_subseccion
	order by a.fecha_origen,a.id_factura,c.id_subseccion";
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__);

	$fechaAnt = "";
	$icomprobant = 0;
	$facturaAnt = "";
	while ($row = ObtenerFetch($exec)) {
		$id = $row[0];
		$fecha = $row[1];
		$cc = $row[2];
		$id_subsecion = $row[13];
		$sucursal = $row[14];
		$cuentacontable = buscarContable(1,$id_subsecion,$sucursal);
		$descripcion = $row[4];
		$Debe = $row[5];
		$Haber = $row[6];
		$documento = $row[7];
		$montoiva = $row[8];
		//$cuentaiva = $row[9];
		$idproveedor = $row[10];
		$Desproveedor = $row[11];
		$Descuento = $row[12];
		
		$descripcion = $documento;//$descripcion . " " .$Desproveedor;
		
		$cuentaiva = buscarContable(6,3,$sucursal);
		
		$ct='01';
		$dt='01';
		$cc='01';
		
		//& if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			if ($fechaAnt != $fecha){
				$icomprobant++;
				$fechaAnt = $fecha;
			}
		
			$MontoRetenidoIVA = 0; 
			
			if ($facturaAnt != $id){
				$SqlStr="SELECT sum(ivaRetenido) FROM ".$_SESSION['bdEmpresa'].".cp_retenciondetalle WHERE idfactura = $id";
				$exec3 = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
				$row = ObtenerFetch($exec3);
					if ($row[0] > 0){
						$MontoRetenidoIVA = $row[0];
						$cuentaRetenido = buscarContable(6,5,$sucursal);// 6 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
					}
				$facturaAnt = $id;
			}
			
			//GASTO SIN IMPUESTO
			$SqlStr="SELECT sum(monto) FROM ".$_SESSION['bdEmpresa'].".cp_factura_gasto WHERE id_factura = $id";
			$exec4 = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
			$row = ObtenerFetch($exec4);
				if ($row[0] > 0){
					$GastoSinImp = $row[0];
					$montoiva = bcadd($montoiva,$GastoSinImp,2); //SUMA
				}
					
			$cuentaCXP = buscarContable(16,$idproveedor,$sucursal);
			
			if($Descuento == 0){
			// Compras sin descuentos
				//& ingresarRenglon($cuentacontable,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				//NO VA - 28/07/2014
				/* Para insertar los terceros */
					ingresarEnlacesTerceros_ter($cuentacontable,$fecha,$Debe,$Haber,$idproveedor
					,"cp_proveedor","id_proveedor|nombre",$descripcion);
				/* Fin Para insertar los terceros */
				
				//para el iva.
				//& ingresarRenglon($cuentaiva,$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				//NO VA - 28/07/2014
				/* Para insertar los terceros */
					/*ingresarEnlacesTerceros_ter($cuentaiva,$fecha,$montoiva,0,$idproveedor
					,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
				/* Fin Para insertar los terceros */
	
				//para la cxp.
				if ($MontoRetenidoIVA == 0){
					$montoCXP = bcadd($Debe,$montoiva,2);
				}else{
					$montoCXP = bcadd($Debe,$montoiva,2);
					$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2);	 
					//& ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
				}
				
				//& ingresarRenglon($cuentaCXP,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				/* Para insertar los terceros */
					ingresarEnlacesTerceros_ter($cuentaCXP,$fecha,0,$montoCXP,$idproveedor
					,"cp_proveedor","id_proveedor|nombre",$descripcion);
				/* Fin Para insertar los terceros */
			// Fin Compras con descuentos
			}else{
			// Compras con descuentos
				//& ingresarRenglon($cuentacontable,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				//NO VA - 28/07/2014
				/* Para insertar los terceros */
					ingresarEnlacesTerceros_ter($cuentacontable,$fecha,$Debe,$Haber,$idproveedor
					,"cp_proveedor","id_proveedor|nombre",$descripcion);
				/* Fin Para insertar los terceros */
	
				//para el iva.
				//& ingresarRenglon($cuentaiva,$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				//NO VA - 28/07/2014
			   /* Para insertar los terceros */
					/*ingresarEnlacesTerceros_ter($cuentaiva,$fecha,$montoiva,0,$idproveedor
					,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
				/* Fin Para insertar los terceros */
			   
			   $cuentaDescuento = buscarContable(6,2,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos. 
			   $montoCXP = bcadd($Debe,$montoiva,2);
			   $montoCXP = bcsub($montoCXP,$Descuento,2);	
				if ($MontoRetenidoIVA != 0){	   
					$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2);	
				//&	ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);
					/* Fin Para insertar los terceros */
				}
				
			   //para el  descuento.
			   //& ingresarRenglon($cuentaDescuento,$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					//NO VA - 28/07/2014			   
					/* Para insertar los terceros */
						ingresarEnlacesTerceros_ter($cuentaDescuento,$fecha,0,$Descuento,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);
					/* Fin Para insertar los terceros */
			   
				//para la cxp.
				//& ingresarRenglon($cuentaCXP,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				/* Para insertar los terceros */
					ingresarEnlacesTerceros_ter($cuentaCXP,$fecha,0,$montoCXP,$idproveedor
					,"cp_proveedor","id_proveedor|nombre",$descripcion."x2");
				/* Fin Para insertar los terceros */
			// Fin Compras con descuentos
			}  
		//}//if(buscarDoc($id,$cc,$ct,$dt)==0){
	}
}

//**************************COMPRAS VEHICULO****************************************
function generarComprasVe_Ter($idFactura=0,$Desde="",$Hasta=""){
	$con = ConectarBD();
	$SqlStr = "SELECT
		a.id_factura as factura
		,a.fecha_origen as fecha
		,'02' as centrocosto
		,b.id_condicion_unidad as condicionunidad
		,d.nom_modelo as descripcion
		,sum(b.costo_compra) as debe 
		,0 as haber 
		,a.numero_factura_proveedor as documento
		,round(sum(iva_compra)+sum(impuesto_lujo_compra),2) as iva 
		,(SELECT cuenta FROM detalleintegracion x1 WHERE x1.idencabezado = 22 AND x1.idobjeto = 3 AND x1.sucursal = 1) as cuentaiva
		,a.id_proveedor as proveedor
		,(SELECT concat(nombre) FROM ".$_SESSION['bdEmpresa'].".cp_proveedor x3 WHERE x3.id_proveedor = a.id_proveedor) as Proveedor
		,a.subtotal_descuento 
		,d.id_modelo
		,b.id_unidad_fisica
		,b.porcentaje_iva_compra
		,a.id_empresa
		,a.id_modo_compra
	FROM
		".$_SESSION['bdEmpresa'].".cp_factura a 
		,".$_SESSION['bdEmpresa'].".an_unidad_fisica b
		, ".$_SESSION['bdEmpresa'].".an_uni_bas c 
		,".$_SESSION['bdEmpresa'].".an_modelo  d
		,".$_SESSION['bdEmpresa'].".cp_factura_detalle_unidad  e
	WHERE c.mod_uni_bas = d.id_modelo
		AND e.id_factura = a.id_factura
		AND e.id_factura_detalle_unidad = b.id_factura_compra_detalle_unidad
		AND b.id_uni_bas= c.id_uni_bas
		AND a.id_modulo = 2";
	
	if($idFactura != 0){
		$SqlStr.=" AND a.id_factura = ".$idFactura;
	}else{
		$SqlStr.=" AND a.fecha_origen between '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$SqlStr.=" GROUP BY a.id_factura
	ORDER BY a.fecha_origen, a.id_factura";
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__);
		$fechaAnt = "";
		$icomprobant = 0;
		while ($row = ObtenerFetch($exec)) {
			$id = $row[0];
			$fecha = $row[1];
			$cc = $row[2];
			$descripcion = $row[4];
			$Debe = $row[5];
			$Haber = $row[6];
			$documento = $row[7];
			$montoiva = $row[8];
			//$cuentaiva = $row[9];
			$idproveedor = $row[10];
			$Desproveedor = $row[11];
			$Descuento = $row[12];
			$id_modelo = $row[13];
			$id_unidad_fisica = $row[14];
			$porcentajeIVA = $row[15];
			$sucursal = $row[16];
			$idModoCompra = $row[17];
			
			if ($row[3] != 1){
				$cuentacontable = buscarContable(3,$id_modelo,$sucursal);
			}else{
				$cuentacontable = buscarContable(2,$id_modelo,$sucursal);
			}
			$descripcion  = $descripcion . " " .$Desproveedor;
			
			$cuentaiva = buscarContable(22,3,$sucursal);
			
			$ct='01';
			$dt='01';
			$cc='02';
			
			if($idModoCompra == 1){ //1 = Nacional, 2 = Importacion
				//& if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
					if ($fechaAnt != $fecha){
						$icomprobant++;
						$fechaAnt = $fecha;
					}
					
					$MontoRetenidoIVA = 0;  
					$SqlStr="SELECT sum(ivaRetenido) FROM ".$_SESSION['bdEmpresa'].".cp_retenciondetalle WHERE idfactura = $id";
					$exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
					$row = ObtenerFetch($exec3);
					if ($row[0] > 0){
						$MontoRetenidoIVA = $row[0];     	   
						$cuentaRetenido = buscarContable(22,5,$sucursal);// 6 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
					}
					$cuentaCXP = buscarContable(13,$idproveedor,$sucursal);
					
					// INICIO para los accesorios	  
					$SqlStr="SELECT
						a.id_accesorio
						,a.costo_partida
						,b.iva_accesorio
						,b.nom_accesorio
					FROM
						".$_SESSION['bdEmpresa'].".an_partida a
						,".$_SESSION['bdEmpresa'].".an_accesorio b
						,".$_SESSION['bdEmpresa'].".an_unidad_fisica c
					WHERE
						a.id_accesorio = b.id_accesorio
						and c.id_unidad_fisica = a.id_unidad_fisica 
						and c.id_unidad_fisica = $id_unidad_fisica and tipo_partida = 'COMPRA'";
					$exec5 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
					$montoIvaAccesorio = 0;
					$costo_partidaSuma = 0;
					while($row3 = ObtenerFetch($exec5)){
						$id_accesorio = $row3[0];
						$costo_partida = $row3[1];
						$aplicaIVA = $row3[2];
						$nom_accesorio = $row3[3];
						$cuentaAccesorio =buscarContable(21,$id_accesorio,$sucursal);// 21 es compras adminstriativas en el encabezado .
						$cuentaAccesorio = $cuentacontable;
						//& ingresarRenglon($cuentaAccesorio,$nom_accesorio,$costo_partida,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D');
						// $Debe = bcadd($Debe,$costo_partida,2);
						if ($aplicaIVA == 1){
							$calIVA = bcmul($costo_partida,$porcentajeIVA,2);
							$calIVA =  bcdiv($calIVA,100,2);
							$montoIvaAccesorio = bcadd($montoIvaAccesorio,$calIVA,2);
						}
						$costo_partidaSuma = bcadd($costo_partidaSuma,$costo_partida,2);
					}// FIN para los accesorios
					
					$Debe = bcadd($Debe,$costo_partidaSuma,2); 
					//& ingresarRenglon($cuentacontable,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
								//NO VA - 28/07/2014
								 /* Para insertar los terceros */
									/*ingresarEnlacesTerceros_ter($cuentacontable,$fecha,$Debe,$Haber,$id_modelo
									,"an_modelo","id_modelo|des_modelo",$descripcion);*/
								/* Fin Para insertar los terceros */
								
					$montoiva = bcadd($montoiva,$montoIvaAccesorio,2);
					//para el iva.
					//& ingresarRenglon($cuentaiva,$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				   //para la cxp.	
					//NO VA - 28/07/2014  
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros_ter($cuentaiva,$fecha,$montoiva,0,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					
					// Compras sin descuentos			   
					if($Descuento == 0){
						if ($MontoRetenidoIVA == 0){
							$montoCXP = bcadd($Debe,$montoiva,2);
						}else{
							$montoCXP = bcadd($Debe,$montoiva,2);
							$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2);	 
							//& ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
							/* Para insertar los terceros */
								/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
								,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
							/* Fin Para insertar los terceros */			
						}
						//& ingresarRenglon($cuentaCXP,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						/* Para insertar los terceros */
							ingresarEnlacesTerceros_ter($cuentaCXP,$fecha,0,$montoCXP,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);
						/* Fin Para insertar los terceros */
					}else{
					// Fin Compras sin descuentos
						$cuentaDescuento = buscarContable(22,2,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
						$montoCXP = bcadd($Debe,$montoiva,2);
						$montoCXP = bcsub($montoCXP,$Descuento,2);	
						if ($MontoRetenidoIVA != 0){	   
						$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2);	
						//& ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
						}
						//para el  descuento.
						//& ingresarRenglon($cuentaDescuento,$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						//NO VA - 28/07/2014			   
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros_ter($cuentaDescuento,$fecha,0,$Descuento,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
						
						//para la cxp.
						//& ingresarRenglon($cuentaCXP,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						/* Para insertar los terceros */
							ingresarEnlacesTerceros_ter($cuentaCXP,$fecha,0,$montoCXP,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);
						/* Fin Para insertar los terceros */
					}//   Fin Compras con descuentos
				//& }//if(buscarDoc($id,$cc)==0){
										
			}else if($idModoCompra == 2){//1 = Nacional, 2 = Importacion
				$cuentaImpor = buscarContable(77,5,$sucursal); //GASTOS DE IMPORTACION: PANAMA = 1.1.04.03.002 ; VENEZUELA = 8.2.01.01.054
				$cuentaRever = buscarContable(78,5,$sucursal); //DERECHOS ARANCELARIOS: son los Impuestos que se deben pagar en la aduana en el momento de importar o exportar mercancías. VENEZUELA = 8.2.01.01.057
				$cuentaOtrosCargos = buscarContable(80,5,$sucursal); //DERECHOS ARANCELARIOS: son los Impuestos que se deben pagar en la aduana en el momento de importar o exportar mercancías. VENEZUELA = 8.2.01.01.057
				$cuentaDif = buscarContable(79,5,$sucursal); //GANANCIA O PERDIDA POR DIFERENCIA ADUANAS // SOLO APLICA PARA VZLA: 9.1.01.01.016
				//& if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
					if ($fechaAnt != $fecha){
						$icomprobant++;
						$fechaAnt = $fecha;
					}
					$MontoRetenidoIVA = 0;
					
					$SqlStr="SELECT sum(ivaRetenido) FROM ".$_SESSION['bdEmpresa'].".cp_retenciondetalle WHERE idfactura = $id";
					$exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
					$row = ObtenerFetch($exec3);
					if ($row[0] > 0){
						$MontoRetenidoIVA = $row[0];     	   
						$cuentaRetenido = buscarContable(22,5,$sucursal);// 22 Fijos Entradas Vehiculos en el encabezado y 5 es en el detalle de los fijos.
					}
					$cuentaCXP = buscarContable(13,$idproveedor,$sucursal); // 13 CXP Vehiculos
					
					$SqlStr="SELECT
						a.monto,
						a.iva,
						a.id_modo_gasto,
						a.afecta_documento,
						b.id_gasto
					from
					  ".$_SESSION['bdEmpresa'].".cp_factura_gasto a
						,".$_SESSION['bdEmpresa'].".pg_gastos b  
					WHERE
						a.id_gasto = b.id_gasto
						AND a.id_factura = $id";
					$exec5 = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
					
					$costo_partidaSuma = 0;
					$gastosImportacion = 0;
					$otrosCargos = 0;
					$gastos2 = 0;
					$montoIvaAccesorio = 0;
					$calIVA = 0;
					$reverso = 0;
					$diferencia = 0;
					while($row3 = ObtenerFetch($exec5)){
						
						$costo_partida = $row3[0];
						$porcentajeIVA = $row3[1];
						$modoGasto = $row3[2];
						$afectaDoc = $row3[3];
						$modo = $row3[4];
						
						if($modoGasto==1 && $afectaDoc==1)
							$costo_partidaSuma = bcadd($costo_partidaSuma,$costo_partida,2);
						
						if($modoGasto==2 || $afectaDoc<>1)
							$gastosImportacion = bcadd($gastosImportacion,$costo_partida,2);
							
						if($modoGasto==3 || $afectaDoc<>1)
							$otrosCargos = bcadd($otrosCargos,$costo_partida,2);
								
						// VERIFICA VALORES DE CONFIGURACION (Consulta el Pais del sistema)
						$queryConfig403 = "SELECT valor FROM ".$_SESSION['bdEmpresa'].".pg_configuracion_empresa config_emp
							INNER JOIN ".$_SESSION['bdEmpresa'].".pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
						WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = 1";
						$rsConfig403 =  EjecutarExec($con,$queryConfig403) or die($queryConfig403." " .mysql_error()); 
						$rowConfig403 = ObtenerFetch($rsConfig403);
						$valor = $rowConfig403['0'];// 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
						
						if ($valor == 1) { // CONFIGURACIÓN SOLO PARA VENEZUELA
							if($modo==12 || $modo==13 || $modo==17) //12 = USO DEL SISTEMA
								$reverso = bcadd($reverso,$costo_partida,2);
								
							if($modo==21 || $modo==22 || $modo==16) // 22 = SEGURO ADUANA
								$diferencia = bcadd($diferencia,$costo_partida,2);
						}	
						
						$calIVA = bcmul($costo_partida,$porcentajeIVA,6); // Multiplica
						$calIVA =  bcdiv($calIVA,100,6); // Divide
						$montoIvaAccesorio = bcadd($montoIvaAccesorio,$calIVA,6);
					  }
					
					$montoiva = round(bcadd($montoiva,$montoIvaAccesorio,6),2);	//Suma: $montoiva + $montoIvaAccesorio
					
					$SqlStrDet = "SELECT 
						SUM(a.cantidad * (((b.costo_unitario + b.gasto_unitario) * c.tasa_cambio) * b.porcentaje_grupo) / 100) AS total_tarifa_adv
					FROM
						".$_SESSION['bdEmpresa'].".cp_factura_detalle_importacion b
						INNER JOIN ".$_SESSION['bdEmpresa'].".cp_factura_detalle a ON (b.id_factura_detalle = a.id_factura_detalle)
						INNER JOIN ".$_SESSION['bdEmpresa'].".cp_factura_importacion c ON (a.id_factura = c.id_factura)
					WHERE
						a.id_factura = $id";
						
					$execDet =  EjecutarExec($con,$SqlStrDet) or die($SqlStrDet);
					while($rowDet = ObtenerFetch($execDet)){
						$total_tarifa_adv = $rowDet[0];	
						$Debe = bcsub($Debe,$total_tarifa_adv,2);// RESTA
						$Debe = bcadd($Debe,$costo_partidaSuma,2); //SUMA
						$gastos2=$gastosImportacion-$reverso-$diferencia;
						$gastosImportacion = bcadd($gastosImportacion,$total_tarifa_adv,2);
						$gastosImp = bcadd($gastosImportacion,$otrosCargos,2);
					}
							
					if($Descuento == 0){// Compras sin descuentos
						//& ingresarRenglon($cuentacontable,$descripcion.' / C. IMPORT.',$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
								/*ingresarEnlacesTerceros_ter($cuentacontable,$fecha,$Debe,$Haber,$id_modelo
								,"an_modelo","id_modelo|des_modelo",$descripcion);*/
						/* Fin Para insertar los terceros */
						
						//*+*+2do renglon gastosImportacion = Otros cargos + Gastos Importacion(Sistema) + ADV // AFECTAN AL INVENTARIO Y SON TODOS O GASTOS(POR REQUERIMIENTO)
						//& ingresarRenglon($cuentacontable,$descripcion.' / C. IMPORT.',$gastosImp,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros_ter($cuentacontable,$fecha,$gastosImportacion,$Haber,$id_modelo
							,"an_modelo","id_modelo|des_modelo",$descripcion);*/
						/* Fin Para insertar los terceros */
						
						//para el iva.
						//& ingresarRenglon($cuentaiva,$descripcion.' / C. IMPORT.',$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros_ter($cuentaiva,$fecha,$montoiva,0,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
						
						//para la cxp.
						if ($MontoRetenidoIVA == 0){
							$montoCXP = bcadd($Debe,$montoiva,2);
						}else{
							$montoCXP = bcadd($Debe,$montoiva,2);
							$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2);	 
							//& ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
							/* Para insertar los terceros */
								/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
								,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
							/* Fin Para insertar los terceros */
						}
						
						$haber2 = $reverso + $montoiva + $total_tarifa_adv;
						
						//CONSULTO EL ADV_VALOREM Y SE LO RESTO AL TOTAL DE LA FACTURA PARA CALCULAR LA CUENTA POR PAGAR(PANAMA)
						$SqlStrDetAdv = "SELECT 
							total_advalorem
						FROM
							".$_SESSION['bdEmpresa'].".cp_factura_importacion a
						WHERE
							a.id_factura = $id";
						$execDetAdv =  EjecutarExec($con,$SqlStrDetAdv) or die($SqlStrDetAdv);
						$rowDetAdv = ObtenerFetch($execDetAdv);
						$adv_general = $rowDetAdv[0];
						
						$advCargos = bcadd($adv_general,$otrosCargos,2);
						
						if ($adv_general > 0){
							$Debe = bcsub($Debe,$adv_general,2); //RESTA
						}
						
						//& ingresarRenglon($cuentaCXP,$descripcion.' / C. IMPORT.',0,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						/* Para insertar los terceros */
							ingresarEnlacesTerceros_ter($cuentaCXP,$fecha,0,$montoCXP,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion."x5");
						/* Fin Para insertar los terceros */
						
						
						//*+*+5do renglon  Otros cargos + Gastos Importacion(Sistema)
						//& ingresarRenglon($cuentaImpor,$descripcion.' / C. IMPORT.',0,$gastos2,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros_ter($cuentaImpor,$fecha,0,$gastos2,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
						
						
						//*+*+6do renglon ITBM + ADV
						//& ingresarRenglon($cuentaRever,$descripcion.' / C. IMPORT.',0,$haber2,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros_ter($cuentaRever,$fecha,0,$haber2,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
						
						//*+*+7mo ADV
						
						if ($adv_general > 0){
							//& ingresarRenglon($cuentaOtrosCargos,$descripcion.' / C. IMPORT.',0,$advCargos,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						}
						
						if ($diferencia > 0){
							//& ingresarRenglon($cuentaDif,$descripcion,0,$diferencia,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
							/* Para insertar los terceros */
								/*ingresarEnlacesTerceros_ter($cuentaDif,$fecha,0,$diferencia,$idproveedor
								,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
							/* Fin Para insertar los terceros */
						}else{
							$diferencia=$diferencia*-1;
							//& ingresarRenglon($cuentaDif,$descripcion,$diferencia,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
							//NO VA - 28/07/2014
							/* Para insertar los terceros */
								/*ingresarEnlacesTerceros_ter($cuentaDif,$fecha,$diferencia,$Haber,$idproveedor
								,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
							/* Fin Para insertar los terceros */
						}// Fin Compras sin descuentos
						
					}else{// Compras con descuentos
						//& ingresarRenglon($cuentacontable,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						/* Para insertar los terceros */
						//NO VA - 28/07/2014
							/*ingresarEnlacesTerceros_ter($cuentacontable,$fecha,$Debe,$Haber,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
						//para el iva.
						//& ingresarRenglon($cuentaiva,$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros_ter($cuentaiva,$fecha,$montoiva,0,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
						$cuentaDescuento = buscarContable(22,2,$sucursal);// 22 Fijos Entradas Vehiculos y 2 es en el detalle de los fijos.
						$montoCXP = bcadd($Debe,$montoiva,2);
						$montoCXP = bcsub($montoCXP,$Descuento,2);
						
						if ($MontoRetenidoIVA != 0){	   
							$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2);	
							//& ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
							/* Para insertar los terceros */
								/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
								,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
							/* Fin Para insertar los terceros */
						}
						//para el  descuento.
						//& ingresarRenglon($cuentaDescuento,$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros_ter($cuentaDescuento,$fecha,0,$Descuento,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
						//para la cxp.
						//& ingresarRenglon($cuentaCXP,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						/* Para insertar los terceros */
							ingresarEnlacesTerceros_ter($cuentaCXP,$fecha,0,$montoCXP,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);
						/* Fin Para insertar los terceros */
					}//Fin Compras con descuentos 
				//& }//if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0)
			}//else if($idModoCompra == 2){
		}//while ($row = ObtenerFetch($exec)) {
}

//**************************COMPRAS REPUESTOS****************************************
function generarComprasRe_Ter($idFactura=0,$Desde="",$Hasta=""){
$con = ConectarBD();

$SqlStr = "update parametros set MensajeRet = ''";
$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
	$SqlStr = "SELECT
		a.id_factura as factura
		,a.fecha_origen as fecha
		,'04' as centrocosto
		,'' as cuentacontable
		,d.descripcion as descripcion
		,sum(b.cantidad*precio_unitario) as debe 
		,0 as haber 
		,a.numero_factura_proveedor as documento
		,b.iva as iva 
		,(SELECT cuenta FROM detalleintegracion x1 WHERE x1.idencabezado = 23 AND x1.idobjeto = 3 AND x1.sucursal = 1) as cuentaiva
		,a.id_proveedor as proveedor
		,(SELECT concat(nombre) FROM ".$_SESSION['bdEmpresa'].".cp_proveedor x3 WHERE x3.id_proveedor = a.id_proveedor) as Proveedor
		,a.subtotal_descuento 
		,a.subtotal_factura 
		,a.id_empresa 
		,a.id_modo_compra
	FROM
		".$_SESSION['bdEmpresa'].".cp_factura a 
		,".$_SESSION['bdEmpresa'].".cp_factura_detalle b
		,".$_SESSION['bdEmpresa'].".iv_articulos c 
		,".$_SESSION['bdEmpresa'].".iv_tipos_articulos  d 
	WHERE
		c.id_tipo_articulo = d.id_tipo_articulo
		AND a.id_factura = b.id_factura
		AND b.id_articulo = c.id_articulo
		AND a.id_modulo = 0";
	if($idFactura != 0){
		$SqlStr.=" AND a.id_factura= ".$idFactura;
	}else{
		$SqlStr.=" AND a.fecha_origen between '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$SqlStr.=" GROUP BY a.id_factura
	ORDER BY a.fecha_origen,a.id_factura";
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
	
    $fechaAnt = "";
	$icomprobant = 0;
	while ($row = ObtenerFetch($exec)) {
		$id = $row[0];
		$fecha = $row[1];
		$cc = $row[2];
		$cuentacontable = $row[3];
		$sucursal = $row[14];
		if (is_null($cuentacontable) || $cuentacontable == ''){
			$cuentacontable =  buscarContable(4,0,$sucursal);
		}
		$descripcion = $row[4];
		$Debe = $row[5];
		$Haber = $row[6];
		$documento = $row[7];
		$porciva = $row[8];
		//$cuentaiva = $row[9];
		$idproveedor = $row[10];
		$Desproveedor = $row[11];
		$Descuento = $row[12];
		$MontoFactura = $row[13];
		$sucursal = $row[14];
		//$Debe = bcsub($MontoFactura,$Descuento,2);
		$Debe = $MontoFactura;
		$Debe1 = bcsub($MontoFactura,$Descuento,2); //Resta: $MontoFactura - $Descuento
		$montoiva = bcmul($Debe1,$porciva,2); //Multiplica: $debe * $pociva
		$montoiva = bcdiv($montoiva,100,3); //Divide: $montoiva / 100
		$descripcion  = $descripcion . " " .$Desproveedor;
		
		$cuentaiva = buscarContable(23,3,$sucursal);
		
		$ct='01';
		$dt='01';
		$cc='04';
		$tipo = $row[15];
		
		if ($tipo == 1){ /// 1 = COMPRAS NACIONALES
			//& if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
				if ($fechaAnt != $fecha){
					$icomprobant++;
					$fechaAnt = $fecha;
				}
				$MontoRetenidoIVA = 0;  
				
				$SqlStr = "SELECT sum(ivaRetenido) FROM ".$_SESSION['bdEmpresa'].".cp_retenciondetalle WHERE idfactura = $id";
				$exec3 = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
				$row = ObtenerFetch($exec3);
				if ($row[0] > 0){
					$MontoRetenidoIVA = $row[0];     	   
					$cuentaRetenido = buscarContable(23,5,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
				}
				$cuentaCXP = buscarContable(14,$idproveedor,$sucursal);
				
				$SqlStr = "SELECT
					monto
					,iva
					,b.id_gasto
					,b.nombre
				FROM
					".$_SESSION['bdEmpresa'].".cp_factura_gasto a
					,".$_SESSION['bdEmpresa'].".pg_gastos b  
				WHERE
					a.id_gasto = b.id_gasto
					AND id_factura = $id";
				$exec5 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
				$montoIvaAccesorio = 0;
				$costo_partidaSuma = 0;
				while($row3 = ObtenerFetch($exec5)){
					$costo_partida = $row3[0];
					$porcentajeIVA = $row3[1];
					if(is_null($porcentajeIVA)){
						$porcentajeIVA = 0;
					}
					$id_accesorio = $row3[2];
					$nom_accesorio = $row3[3];
					$cuentaAccesorio = buscarContable(66,$id_accesorio,$sucursal);// 66 Administracion-->Gastos compras.
					//& ingresarRenglon($cuentaAccesorio,$nom_accesorio,$costo_partida,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					// $Debe = bcadd($Debe,$costo_partida,2);
					// if ($aplicaIVA == 1){
					if ($porcentajeIVA != 0){
						$calIVA = bcmul($costo_partida,$porcentajeIVA,6);
						$calIVA =  bcdiv($calIVA,100,6);
						$montoIvaAccesorio = bcadd($montoIvaAccesorio,$calIVA,6);
					}
					//}
					$costo_partidaSuma = bcadd($costo_partidaSuma,$costo_partida,2);
				}
				// para  los accesorios
				// Compras sin descuentos
				//$Debe = bcadd($Debe,$costo_partidaSuma,2); 
				$montoiva = round(bcadd($montoiva,$montoIvaAccesorio,6),2);	   
				
				if($Descuento == 0){
				// Compras sin descuentos
					//& ingresarRenglon($cuentacontable,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros_ter($cuentacontable,$fecha,$Debe,$Haber,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					//para el iva.
					//ingresarRenglon($cuentaiva,$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					//& ingresarRenglon($cuentaiva,$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros_ter($cuentaiva,$fecha,$montoiva,0,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					
					//para la cxp.
					if ($MontoRetenidoIVA == 0){
						$montoCXP = bcadd($Debe,$montoiva,2);
					}else{
						$montoCXP = bcadd($Debe,$montoiva,2);
						$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2);	 
							
					//&	ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*	ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
					}
						
					$montoCXP = bcadd($montoCXP,$costo_partidaSuma,2);
					//& ingresarRenglon($cuentaCXP,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					/* Para insertar los terceros */
						ingresarEnlacesTerceros_ter($cuentaCXP,$fecha,0,$montoCXP,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion."x7");
					/* Fin Para insertar los terceros */
			 	//Fin Compras sin descuentos
				}else{// Compras con descuentos
					//& ingresarRenglon($cuentacontable,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros_ter($cuentacontable,$fecha,$Debe,$Haber,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					//para el iva.
					//& ingresarRenglon($cuentaiva,$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros_ter($cuentaiva,$fecha,$montoiva,0,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					$cuentaDescuento = buscarContable(23,2,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
					$montoCXP = bcadd($Debe,$montoiva,2);
					$montoCXP = bcsub($montoCXP,$Descuento,2);	
					if ($MontoRetenidoIVA != 0){	   
						$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2);	
					//&	ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */

							/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
					}
					//para el  descuento.
					//& ingresarRenglon($cuentaDescuento,$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros_ter($cuentaDescuento,$fecha,0,$Descuento,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					//para la cxp.
					$montoCXP = bcadd($montoCXP,$costo_partidaSuma,2);
					//& ingresarRenglon($cuentaCXP,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					/* Para insertar los terceros */
						ingresarEnlacesTerceros_ter($cuentaCXP,$fecha,0,$montoCXP,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion."x8");
					/* Fin Para insertar los terceros */
				}// Fin Compras con descuentos
			//& }// if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
				
		}else{//FIN COMPRA NACIONAL //INICIO COMPRA IMPORTACION
		
			$cuentaImpor = buscarContable(68,5,$sucursal); //GASTOS DE IMPORTACION: PANAMA = 1.1.04.03.002 ; VENEZUELA = 8.2.01.01.054
			$cuentaRever = buscarContable(71,5,$sucursal); //DERECHOS ARANCELARIOS: son los Impuestos que se deben pagar en la aduana en el momento de importar o exportar mercancías. VENEZUELA = 8.2.01.01.057
			$cuentaDif = buscarContable(72,5,$sucursal); //GANANCIA O PERDIDA POR DIFERENCIA ADUANAS // SOLO APLICA PARA VZLA: 9.1.01.01.016
			//& if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
				if ($fechaAnt != $fecha){
					$icomprobant++;
					$fechaAnt = $fecha;
				}
				$MontoRetenidoIVA = 0;
				
				$SqlStr="SELECT sum(ivaRetenido) FROM ".$_SESSION['bdEmpresa'].".cp_retenciondetalle WHERE idfactura = $id";
				$exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
				$row = ObtenerFetch($exec3);
				if ($row[0] > 0){
					$MontoRetenidoIVA = $row[0];     	   
					$cuentaRetenido = buscarContable(23,5,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
				}
				$cuentaCXP = buscarContable(14,$idproveedor,$sucursal);
				
				$SqlStr="SELECT
					a.monto,
					a.iva,
					a.id_modo_gasto,
					a.afecta_documento,
					b.id_gasto
				from
					".$_SESSION['bdEmpresa'].".cp_factura_gasto a
					,".$_SESSION['bdEmpresa'].".pg_gastos b  
				WHERE
					a.id_gasto = b.id_gasto
					AND a.id_factura = $id";
				$exec5 = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
				
				$costo_partidaSuma = 0;
				$gastosImportacion = 0;
				$gastos2 = 0;
				$montoIvaAccesorio = 0;
				$calIVA = 0;
				$reverso = 0;
				$diferencia = 0;
				$usoSistema = 0;
				$seguroAduana = 0;
				while($row3 = ObtenerFetch($exec5)){
					
					$costo_partida = $row3[0];
					$porcentajeIVA = $row3[1];
					$modoGasto = $row3[2];
					$afectaDoc = $row3[3];
					$modo = $row3[4];
					
					// VERIFICA VALORES DE CONFIGURACION (Consulta el Pais del sistema)
					$queryConfig403 = "SELECT valor FROM ".$_SESSION['bdEmpresa'].".pg_configuracion_empresa config_emp
						INNER JOIN ".$_SESSION['bdEmpresa'].".pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
					WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = 1";
					$rsConfig403 =  EjecutarExec($con,$queryConfig403) or die($queryConfig403." " .mysql_error()); 
					$rowConfig403 = ObtenerFetch($rsConfig403);
					$valor = $rowConfig403['0'];// 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
					
					if($modoGasto==1 && $afectaDoc==1)
						$costo_partidaSuma = bcadd($costo_partidaSuma,$costo_partida,2);
					
					if($modoGasto<>1 || $afectaDoc<>1)
						$gastosImportacion = bcadd($gastosImportacion,$costo_partida,2);
					
					if ($valor == 2 || $valor == 3) { // CONFIGURACIÓN PARA PANAMA Y PUERTO RICO
						if($modo==12 && $afectaDoc==1)
							$usoSistema = $row3[0];
							
						if($modo==22 && $afectaDoc==0)
							$seguroAduana = $row3[0];
					}
					
					if ($valor == 1) { // CONFIGURACIÓN PARA VENEZUELA
						if($modo==12 || $modo==13 || $modo==17) //12 = USO DEL SISTEMA
							$reverso = bcadd($reverso,$costo_partida,2);
							
						if($modo==21 || $modo==22 || $modo==16) // 22 = SEGURO ADUANA
							$diferencia = bcadd($diferencia,$costo_partida,2);
					}	
					
					$calIVA = bcmul($costo_partida,$porcentajeIVA,6); // Multiplica
					$calIVA =  bcdiv($calIVA,100,6); // Divide
					$montoIvaAccesorio = bcadd($montoIvaAccesorio,$calIVA,6);
				  }
				
				$montoiva = round(bcadd($montoiva,$montoIvaAccesorio,6),2);	//Suma: $montoiva + $montoIvaAccesorio
				
				$SqlStrDet = "SELECT 
					SUM(a.cantidad * (((b.costo_unitario + b.gasto_unitario) * c.tasa_cambio) * b.porcentaje_grupo) / 100) AS total_tarifa_adv
				FROM
					".$_SESSION['bdEmpresa'].".cp_factura_detalle_importacion b
					INNER JOIN ".$_SESSION['bdEmpresa'].".cp_factura_detalle a ON (b.id_factura_detalle = a.id_factura_detalle)
					INNER JOIN ".$_SESSION['bdEmpresa'].".cp_factura_importacion c ON (a.id_factura = c.id_factura)
				WHERE
					a.id_factura = $id";
				$execDet =  EjecutarExec($con,$SqlStrDet) or die($SqlStrDet);
				while($rowDet = ObtenerFetch($execDet)){
					$total_tarifa_adv = $rowDet[0];	
					$Debe = bcsub($Debe,$total_tarifa_adv,2);// RESTA
					$Debe = bcadd($Debe,$costo_partidaSuma,2); //SUMA
					$gastos2=$gastosImportacion-$reverso-$diferencia;
					$gastosImportacion = bcadd($gastosImportacion,$total_tarifa_adv,2);
				}
				
				if($Descuento == 0){// Compras sin descuentos
					//& ingresarRenglon($cuentacontable,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros_ter($cuentacontable,$fecha,$Debe,$Haber,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					
					if ($valor == 2 || $valor == 3) { // CONFIGURACIÓN PARA PANAMA Y PUERTO RICO
						$gastosImportacion = $total_tarifa_adv + $usoSistema + $seguroAduana;
					}
					
					//*+*+2do renglon gastosImportacion = Otros cargos + Gastos Importacion(Sistema) + ADV // AFECTAN AL INVENTARIO Y SON TODOS O GASTOS(POR REQUERIMIENTO)
					//& ingresarRenglon($cuentacontable,$descripcion,$gastosImportacion,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros_ter($cuentacontable,$fecha,$gastosImportacion,$Haber,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					
					//para el iva.
					//& ingresarRenglon($cuentaiva,$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros_ter($cuentaiva,$fecha,$montoiva,0,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					
					//para la cxp.
					if ($MontoRetenidoIVA == 0){
						$montoCXP = bcadd($Debe,$montoiva,2);
					}else{
						$montoCXP = bcadd($Debe,$montoiva,2);
						$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2);	 
					//& ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
					}
					
					$haber2 = $reverso + $montoiva + $total_tarifa_adv;
					
					//& ingresarRenglon($cuentaCXP,$descripcion,0,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					/* Para insertar los terceros */
						ingresarEnlacesTerceros_ter($cuentaCXP,$fecha,0,$Debe,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion."x9");
					/* Fin Para insertar los terceros */
					
					if ($valor == 2 || $valor == 3) { // CONFIGURACIÓN PARA PANAMA Y PEURTO RICO
						$gastos2 = $seguroAduana;
					}
					//*+*+5do renglon  Otros cargos + Gastos Importacion(Sistema)
					//& ingresarRenglon($cuentaImpor,$descripcion,0,$gastos2,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros_ter($cuentaImpor,$fecha,0,$gastos2,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					
					if ($haber2 > 0){
					
						if ($valor == 2 || $valor == 3) { // CONFIGURACIÓN PARA PANAMA Y PUERTO RICO
							$haber2 = $montoiva;
							$haber3 = $total_tarifa_adv + $usoSistema;
						}
					//*+*+6do renglon ITBM + ADV
					//& ingresarRenglon($cuentaRever,$descripcion,0,$haber2,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros_ter($cuentaRever,$fecha,0,$haber2,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					
						if ($valor == 2 || $valor == 3) { // CONFIGURACIÓN PARA PANAMA Y PUERTO RICO
							//*+*+6do renglon ITBM + ADV
							ingresarRenglon($cuentaRever,$descripcion,0,$haber3,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
							/* Para insertar los terceros */
								/*ingresarEnlacesTerceros($cuentaRever,$fecha,0,$haber2,$idproveedor
								,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
							/* Fin Para insertar los terceros */
						}	
					
					}
					
					if ($diferencia > 0){
					//& ingresarRenglon($cuentaDif,$descripcion,0,$diferencia,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros_ter($cuentaDif,$fecha,0,$diferencia,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
					}else{
						$diferencia=$diferencia*-1;
					//&	ingresarRenglon($cuentaDif,$descripcion,$diferencia,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros_ter($cuentaDif,$fecha,$diferencia,$Haber,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
					}// Fin Compras sin descuentos
					
				}else{// Compras con descuentos
					//& ingresarRenglon($cuentacontable,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					/* Para insertar los terceros */
					//NO VA - 28/07/2014
						/*ingresarEnlacesTerceros_ter($cuentacontable,$fecha,$Debe,$Haber,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					//para el iva.
					//& ingresarRenglon($cuentaiva,$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros_ter($cuentaiva,$fecha,$montoiva,0,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					$cuentaDescuento = buscarContable(23,2,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
					$montoCXP = bcadd($Debe,$montoiva,2);
					$montoCXP = bcsub($montoCXP,$Descuento,2);
					
					if ($MontoRetenidoIVA != 0){	   
						$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2);	
					//& 	ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
							,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
						/* Fin Para insertar los terceros */
					}
					//para el  descuento.
					//& ingresarRenglon($cuentaDescuento,$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros_ter($cuentaDescuento,$fecha,0,$Descuento,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
					//para la cxp.
					//& ingresarRenglon($cuentaCXP,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					/* Para insertar los terceros */
						ingresarEnlacesTerceros_ter($cuentaCXP,$fecha,0,$montoCXP,$idproveedor
						,"cp_proveedor","id_proveedor|nombre",$descripcion."x10");
					/* Fin Para insertar los terceros */
				}//Fin Compras con descuentos 
			//& }
		}//FIN COMPRA INTERNACIONAL
	}
}

//**************************COMPRAS SERVICIO****************************************
function generarComprasSe_Ter($idFactura=0,$Desde="",$Hasta=""){
$con = ConectarBD();
$SqlStr = "update parametros set MensajeRet = ''";
$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
	$SqlStr = "SELECT
		a.id_factura AS factura
		,a.fecha_origen AS fecha
		,'03' AS centrocosto
		,(SELECT cuentageneral FROM encabezadointegracion e WHERE e.id = 5) AS cuentacontable
		,'SERVICIOS' AS descripcion
		,b.base_imponible AS debe 
		,0 AS haber 
		,a.numero_factura_proveedor AS documento
		,b.subtotal_iva AS iva 
		,(SELECT cuenta FROM detalleintegracion x1 WHERE x1.idencabezado = 24 AND x1.idobjeto = 3 AND x1.sucursal = 1) AS cuentaiva
		,a.id_proveedor AS proveedor
		,(SELECT concat(nombre) FROM ".$_SESSION['bdEmpresa'].".cp_proveedor x3 WHERE x3.id_proveedor = a.id_proveedor) AS Proveedor
		,a.subtotal_descuento
		,a.id_empresa
		,(SELECT sa_tipo_orden.id_filtro_orden FROM ".$_SESSION['bdEmpresa'].".sa_orden_tot LEFT JOIN ".$_SESSION['bdEmpresa'].".sa_orden orden ON sa_orden_tot.id_orden_servicio = orden.id_orden LEFT JOIN ".$_SESSION['bdEmpresa'].".sa_tipo_orden ON sa_tipo_orden.id_tipo_orden = orden.id_tipo_orden WHERE sa_orden_tot.id_factura = a.id_factura) AS id_filtro_orden
	FROM
		".$_SESSION['bdEmpresa'].".cp_factura a 
		,".$_SESSION['bdEmpresa'].".cp_factura_iva b
	WHERE
		a.id_factura = b.id_factura
		AND a.id_modulo = 1";
	if($idFactura != 0){
		$SqlStr.=" AND a.id_factura = ".$idFactura;
	}else{
		$SqlStr.=" AND a.fecha_origen between '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$SqlStr.=" GROUP BY a.id_factura
	ORDER BY a.fecha_origen, a.id_factura";
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__);
	
	$fechaAnt = "";
	$icomprobant = 0;
	while ($row = ObtenerFetch($exec)) {
		$id = $row[0];
		$fecha = $row[1];
		$cc = $row[2];
		$cuentacontable = $row[3];
		$descripcion = $row[4];
		$Debe = $row[5];
		$Haber = $row[6];
		$documento = $row[7];
		$montoiva = $row[8];
		//$cuentaiva = $row[9];
		$idproveedor = $row[10];
		$Desproveedor = $row[11];
		$Descuento = $row[12];
		$sucursal = $row[13];
		$tipo_orden = $row[14];
		
		if ($tipo_orden == 9){
			$cuentacontable = buscarContable(5,9,$sucursal);
		} else if ($tipo_orden == 10){
			$cuentacontable = buscarContable(5,10,$sucursal);
		}
		
		if (is_null($cuentacontable)){
			$cuentacontable =  buscarContable(5,0,$sucursal);
		}
		
		$descripcion  = $descripcion . " " .$Desproveedor;
		
		$cuentaiva = buscarContable(24,3,$sucursal);
		
		$ct='01';
		$dt='01';
		$cc='03';
		
		//& if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			if ($fechaAnt != $fecha){
				$icomprobant++;
				$fechaAnt = $fecha;
			}
			$MontoRetenidoIVA = 0;  
			$SqlStr = "SELECT sum(ivaRetenido) FROM ".$_SESSION['bdEmpresa'].".cp_retenciondetalle WHERE idfactura = $id";
			$exec3 = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__);
			$row = ObtenerFetch($exec3);
			if ($row[0] > 0){
				$MontoRetenidoIVA = $row[0];     	   
				$cuentaRetenido = buscarContable(24,5,$sucursal);// 24 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
			}
			$cuentaCXP = buscarContable(15,$idproveedor,$sucursal);	   
			if($Descuento == 0){
				// Compras sin descuentos
			//&	ingresarRenglon($cuentacontable,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				//NO VA - 28/07/2014
				/* Para insertar los terceros */
				/*ingresarEnlacesTerceros_ter($cuentacontable,$fecha,$Debe,$Haber,$idproveedor
				,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
				/* Fin Para insertar los terceros */
				
				//para el iva.
			//&	ingresarRenglon($cuentaiva,$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				//NO VA - 28/07/2014
				/* Para insertar los terceros */
				/*ingresarEnlacesTerceros_ter($cuentaiva,$fecha,$montoiva,0,$idproveedor
				,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
				/* Fin Para insertar los terceros */
				
				//para la cxp.
				if ($MontoRetenidoIVA == 0){
					$montoCXP = bcadd($Debe,$montoiva,2);
				}else{
					$montoCXP = bcadd($Debe,$montoiva,2);
					$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2);	 
			//&		ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
					/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
					,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */					
				}
			//&	ingresarRenglon($cuentaCXP,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				/* Para insertar los terceros */
				ingresarEnlacesTerceros_ter($cuentaCXP,$fecha,0,$montoCXP,$idproveedor
				,"cp_proveedor","id_proveedor|nombre",$descripcion);
				/* Fin Para insertar los terceros */
				//   Fin Compras sin descuentos
			}else{
				// Comprascon descuentos
			//&	ingresarRenglon($cuentacontable,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				/* Para insertar los terceros */
				//NO VA - 28/07/2014
				/* ingresarEnlacesTerceros_ter($cuentacontable,$fecha,$Debe,$Haber,$idproveedor
				,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
				/* Fin Para insertar los terceros */
				//para el iva.
			//&	ingresarRenglon($cuentaiva,$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				/* Para insertar los terceros */
				//NO VA - 28/07/2014
				/*ingresarEnlacesTerceros_ter($cuentaiva,$fecha,$montoiva,0,$idproveedor
				,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
				/* Fin Para insertar los terceros */
				
				$cuentaDescuento = buscarContable(24,2,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
				$montoCXP = bcadd($Debe,$montoiva,2);
				$montoCXP = bcsub($montoCXP,$Descuento,2);	
				if ($MontoRetenidoIVA != 0){	   
					$montoCXP = bcsub($montoCXP,$MontoRetenidoIVA,2);	
			//&		ingresarRenglon($cuentaRetenido,$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
					/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idproveedor
					,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
					/* Fin Para insertar los terceros */
				}
				//para el  descuento.
			//&	ingresarRenglon($cuentaDescuento,$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				
				//NO VA - 28/07/2014
				/* Para insertar los terceros */
				/*ingresarEnlacesTerceros_ter($cuentaDescuento,$fecha,0,$Descuento,$idproveedor
				,"cp_proveedor","id_proveedor|nombre",$descripcion);*/
				/* Fin Para insertar los terceros */
				//para la cxp.
			//&	ingresarRenglon($cuentaCXP,$descripcion,0,$montoCXP,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				
				/* Para insertar los terceros */
				ingresarEnlacesTerceros_ter($cuentaCXP,$fecha,0,$montoCXP,$idproveedor
				,"cp_proveedor","id_proveedor|nombre",$descripcion);
				/* Fin Para insertar los terceros */
				//   Fin Compras con descuentos
			}  
		//&}	
	}
}

//**************************VENTAS ADMINISTRATIVAS****************************************
//************************aplica para las facturas challenge******************************
function generarVentasAd_Ter($idFactura=0,$Desde="",$Hasta=""){
$con = ConectarBD();
	$SqlStr = "select a.idfactura
		   ,a.fecharegistrofactura
		   ,'01' as centrocosto
		   ,'' as cuentacontable
		   ,0 as Debe 
		   ,(b.cantidad*b.precio_unitario) as haber 
		   ,a.numerofactura as documento
		   ,a.calculoIvaFactura 
		   ,a.idcliente as idcliente
		   ,(select concat_ws(' ',nombre,apellido) from ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 where x3.id = a.idcliente) as descliente
		   ,a.descuentofactura 
		   ,(b.cantidad*b.precio_unitario) as costo 
		   ,a.id_empresa
		   ,c.descripcion
		  ,c.id_tipo_concepto
	from ".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
	,".$_SESSION['bdEmpresa'].".cj_cc_factura_detalle_adm b inner join ".$_SESSION['bdEmpresa'].".cj_cc_tipo_concepto c on c.id_tipo_concepto = 			b.id_concepto
	where  a.idfactura = b.id_factura
	and a.iddepartamentoorigenfactura = 3"; 

	
	if($idFactura != 0){
		$SqlStr.=" and a.idfactura= ".$idFactura;
	}else{
		$SqlStr.=" and a.fecharegistrofactura between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$SqlStr.=" 	order by a.fecharegistrofactura,a.idfactura";

//se quito esto de la clausula where: 	and c.id_documento = a.idfactura";
	
	$exec =  EjecutarExec($con,$SqlStr) or die("Error Sql: ".mysql_error()." Query: ".$SqlStr." Linea: ".__LINE__." Archivo: ".__FILE__); 
		//return array($SqlStr);
		$fechaAnt = "";
		$icomprobant = 0;
		while ($row = ObtenerFetch($exec)) {
			$id = $row[0];
			$fecha = $row[1];
			$cc = $row[2];
			$cuentacontable = $row[3];
			$sucursal = $row[12];
			if (is_null($cuentacontable) || $cuentacontable == ''){
				$cuentacontable = buscarContable(1,0,$sucursal);//inventario... 
			}
			$descripcion = $row[13];
			$Debe = $row[4];
			$Haber = $row[5];
			$documento = $row[6];
			//$montoiva = $row[8];
			$montoiva = 0.00;
			//$cuentaiva = $row[9];
			$idcliente = $row[8];
			$Descliente = $row[9];
			$Descuento = $row[10];
			$Costo = $row[11];
			$idTipo = $row[14];
			$sucursal = $row[12];
			$descripcion  = $descripcion . " " .$Descliente;
			$ct='02';
			$dt='01';
			$cc='01';
			
			//& if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
				if ($fechaAnt != $fecha){
					$icomprobant++;
					$fechaAnt = $fecha;
				}
												
				$cuentaVenta = buscarContable(70,$idTipo,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
				$MontoRetenidoIVA = 0;  
				
				/*$SqlStr="select sum(ivaRetenido) from ".$_SESSION['bdEmpresa'].".cj_cc_retenciondetalle where idfactura = $id";
				$exec3 =  EjecutarExec($con,$SqlStr) or die("Error Sql: ".mysql_error()." Query: ".$SqlStr." Linea: ".__LINE__." Archivo: ".__FILE__); 
				$row = ObtenerFetch($exec3);
				if ($row[0] > 0){
					$MontoRetenidoIVA = $row[0];     	   
					$cuentaRetenido = buscarContable(26,17,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
				}
				*/
								
    		  $SqlStr=" select monto,iva,b.id_gasto,b.nombre from ".$_SESSION['bdEmpresa'].".cj_cc_factura_gasto a
						,".$_SESSION['bdEmpresa'].".pg_gastos b  
						where a.id_gasto = b.id_gasto
						and id_factura = $id";
			  $exec5 =  EjecutarExec($con,$SqlStr) or die("Error Sql: ".mysql_error()." Query: ".$SqlStr." Linea: ".__LINE__." Archivo: ".__FILE__);
			   
			  $montoIvaAccesorio = 0;
			  $costo_partidaSuma = 0;
	          while($row3 = ObtenerFetch($exec5)){
			       $costo_partida = $row3[0];
				   $porcentajeIVA = $row3[1];
				   if(is_null($porcentajeIVA)){
					   $porcentajeIVA = 0;
				   }
				   $id_accesorio =  $row3[2];
				   $nom_accesorio = $row3[3];
				   $cuentaAccesorio = buscarContable(67,$id_accesorio,$sucursal);// 66 adminstriativas Gastos compras.
			 //&      ingresarRenglon($cuentaAccesorio,$nom_accesorio,0,$costo_partida,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				  // $Debe = bcadd($Debe,$costo_partida,2);
				  // if ($aplicaIVA == 1){
					   if ($porcentajeIVA != 0){
				   		   $calIVA = bcmul($costo_partida,$porcentajeIVA,6);
						   $calIVA =  bcdiv($calIVA,100,6);
     					   $montoIvaAccesorio = bcadd($montoIvaAccesorio,$calIVA,6);
					   }
				   //}
				   $costo_partidaSuma = bcadd($costo_partidaSuma,$costo_partida,2);
			  }
       // para  los accesorios
	   // Compras sin descuentos
	   $Haber = bcadd($Haber,$costo_partidaSuma,2); 
		 $montoiva = round(bcadd($montoiva,$montoIvaAccesorio,6),2);	   
				
				$cuentaCXC = buscarContable(70,$idcliente,$sucursal);	   
				// Compras sin descuentos				
				
				if($Descuento == 0){
				//para la cxc.
					if ($MontoRetenidoIVA == 0){
						$montoCXC = bcadd($Haber,$montoiva,2);
					}else{
						$montoCXC = bcadd($Haber,$montoiva,2);
						$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	 
				//&		ingresarRenglon($cuentaRetenido,$descripcion,$MontoRetenidoIVA,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,$MontoRetenidoIVA,0,$idcliente
							,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
						/* Fin Para insertar los terceros */		
					}
				//   Fin Compras sin descuentos
				}else{
					$cuentaDescuento = buscarContable(48,$idTipo,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
					$montoCXC = bcadd($Haber,$montoiva,2);
					$montoCXC = bcsub($montoCXC,$Descuento,2);	
					if ($MontoRetenidoIVA != 0){	   
						$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	
				//&		ingresarRenglon($cuentaRetenido,$descripcion,$MontoRetenidoIVA,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,$MontoRetenidoIVA,0,$idcliente
							,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
						/* Fin Para insertar los terceros */	
					}
					//para el  descuento.
				//&	ingresarRenglon($cuentaDescuento,$descripcion,$Descuento,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros_ter($cuentaDescuento,$fecha,$Descuento,0,$idTipo
						,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
					/* Fin Para insertar los terceros */	
					//para la cxp.
					//   Fin Compras con descuentos
				}
				
				//return array($cuentaCXC);
				//$cuentacxc es el primer renglon que aparece...

				//$cuentaCXC = "1.1.02.01.006";
				//$descripcion1 = "GARANTIA Y APOYO DE FABRICA";
				
				
				$SqlStr = "SELECT descripcion as descripcion FROM cuenta where codigo = '".$cuentaCXC."'";
				//return array($SqlStr);
				 $exec6 =  EjecutarExec($con,$SqlStr) or die("Error Sql: ".mysql_error()." Query: ".$SqlStr." Linea: ".__LINE__." Archivo: ".__FILE__);
				while($row6 = ObtenerFetch($exec6)){
			       $descripcion1 = $row6[0];
				//& ingresarRenglon($cuentaCXC,$descripcion1,$montoCXC,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);		}
				
				/* Para insertar los terceros */
				ingresarEnlacesTerceros_ter($cuentaCXC,$fecha,$montoCXC,0,$idcliente
					,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
				/* Fin Para insertar los terceros */	
				//para  el costo 
			
				//return array($cuentaVenta);
				//$cuentaVenta es el segundo renglon que aparece.... en el haber	
				//& ingresarRenglon($cuentaVenta,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				
				
				//NO VA - 28/07/2014
				/* Para insertar los terceros */
					/*ingresarEnlacesTerceros_ter($cuentaVenta,$fecha,$Debe,$Haber,$idTipo
					,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
				/* Fin Para insertar los terceros */			   
				
				//para el iva.
				
				//ingresarRenglon($cuentaiva,$descripcion,0,$montoiva,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				
				//NO VA - 28/07/2014
				/* Para insertar los terceros */
					/*ingresarEnlacesTerceros_ter($cuentaiva,$fecha,0,$montoiva,$idcliente
					,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
				/* Fin Para insertar los terceros */
				
				
				$cuentaCosto = buscarContable(32,$idTipo,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
				
				//ingresarRenglon($cuentaCosto,$descripcion,$Costo,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				
				//NO VA - 28/07/2014
				/* Para insertar los terceros */
					/*ingresarEnlacesTerceros_ter($cuentaCosto,$fecha,$Costo,0,$idTipo
					,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
				/* Fin Para insertar los terceros */	
				
				//ingresarRenglon($cuentacontable,$descripcion,0,$Costo,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				
				//NO VA - 28/07/2014		  
				/* Para insertar los terceros */
					/*ingresarEnlacesTerceros_ter($cuentacontable,$fecha,0,$Costo,$idTipo
					,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
				/* Fin Para insertar los terceros */
			}	
		//& }//if(buscarDoc($id,$cc)==0){	
} // while ($row = ObtenerFetch($exec)) {
} //function generarVentasAd_Ter($idFactura=0,$Desde="",$Hasta=""){


//**************************VENTAS REPUESTOS****************************************
function generarVentasRe_Ter($idFactura=0,$Desde="",$Hasta=""){
$con = ConectarBD();
	$SqlStr = "select a.idfactura
		   ,a.fecharegistrofactura
		   ,'04' as centrocosto
		   ,'' as cuentacontable
		   ,c.descripcion as descripcion
		   ,0 as Debe 
		   ,sum(b.cantidad*b.precio_unitario) as haber 
		   ,a.numerofactura as documento
		   ,a.calculoIvaFactura 
		   ,(select cuenta from detalleintegracion x1 where x1.idencabezado = 26 and x1.idobjeto = 15 and x1.sucursal = 1) as cuentaiva
		   ,a.idcliente as idcliente
		   ,(select concat(nombre,' ',apellido) from ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 where x3.id = a.idcliente) as descliente
		   ,a.descuentofactura 
		   ,sum(b.cantidad*b.costo_compra) as costo 
		   ,c.id_clave_movimiento as clave_movimiento
		   ,a.id_empresa
	from ".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
				,".$_SESSION['bdEmpresa'].".cj_cc_factura_detalle b
				,".$_SESSION['bdEmpresa'].".v_clavemovimiento c 
	where  a.idfactura = b.id_factura
	and a.iddepartamentoorigenfactura = 0
	and c.id_documento = a.idfactura";
	
	if($idFactura != 0){
		$SqlStr.=" and a.idfactura= ".$idFactura;
	}else{
		$SqlStr.=" and a.fecharegistrofactura between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$SqlStr.=" group by a.idfactura,c.id_clave_movimiento
	order by a.fecharegistrofactura,a.idfactura,c.id_clave_movimiento";
	
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
		$fechaAnt = "";
		$icomprobant = 0;
		while ($row = ObtenerFetch($exec)) {
			$id = $row[0];
			$fecha = $row[1];
			$cc = $row[2];
			$cuentacontable = $row[3];
			$sucursal = $row[15];
			if (is_null($cuentacontable) || $cuentacontable == ''){
				$cuentacontable =  buscarContable(4,0,$sucursal);
			}
			$descripcion = $row[4];
			$Debe = $row[5];
			$Haber = $row[6];
			$documento = $row[7];
			$montoiva = $row[8];
			//$cuentaiva = $row[9];
			$idcliente = $row[10];
			$Descliente = $row[11];
			$Descuento = $row[12];
			$Costo = $row[13];
			$idTipo = $row[14];
			$sucursal = $row[15];
			$descripcion  = $descripcion . " " .$Descliente;
			
			$cuentaiva = buscarContable(26,15,$sucursal);
			
			$ct='02';
			$dt='01';
			$cc='04';
			
			//& if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
				if ($fechaAnt != $fecha){
					$icomprobant++;
					$fechaAnt = $fecha;
				}
				
				$cuentaVenta = buscarContable(28,$idTipo,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
				$MontoRetenidoIVA = 0;  
				
				/*$SqlStr="select sum(ivaRetenido) from ".$_SESSION['bdEmpresa'].".cj_cc_retenciondetalle where idfactura = $id";
				$exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
				$row = ObtenerFetch($exec3);
				if ($row[0] > 0){
					$MontoRetenidoIVA = $row[0];     	   
					$cuentaRetenido = buscarContable(26,17,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
				}
				*/
				
				
    		  $SqlStr=" select monto,iva,b.id_gasto,b.nombre from ".$_SESSION['bdEmpresa'].".cj_cc_factura_gasto a
						,".$_SESSION['bdEmpresa'].".pg_gastos b  
						where a.id_gasto = b.id_gasto
						and id_factura = $id";
			  $exec5 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
			  $montoIvaAccesorio = 0;
			  $costo_partidaSuma = 0;
	          while($row3 = ObtenerFetch($exec5)){
			       $costo_partida = $row3[0];
				   $porcentajeIVA = $row3[1];
				   if(is_null($porcentajeIVA)){
					   $porcentajeIVA = 0;
				   }
				   $id_accesorio =  $row3[2];
				   $nom_accesorio = $row3[3];
				   $cuentaAccesorio =buscarContable(84,$id_accesorio,$sucursal);// 66 adminstriativas Gastos compras.
			//&    ingresarRenglon($cuentaAccesorio,$nom_accesorio,0,$costo_partida,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				  // $Debe = bcadd($Debe,$costo_partida,2);
				  // if ($aplicaIVA == 1){
					   if ($porcentajeIVA != 0){
				   		   $calIVA = bcmul($costo_partida,$porcentajeIVA,6);
						   $calIVA =  bcdiv($calIVA,100,6);
     					   $montoIvaAccesorio = bcadd($montoIvaAccesorio,$calIVA,6);
					   }
				   //}
				   $costo_partidaSuma = bcadd($costo_partidaSuma,$costo_partida,2);
			  }
       // para  los accesorios
	   // Compras sin descuentos
	   $Haber = bcadd($Haber,$costo_partidaSuma,2); 
		 $montoiva = round(bcadd($montoiva,$montoIvaAccesorio,6),2);	   
				
				$cuentaCXC = buscarContable(17,$idcliente,$sucursal);	   
				// Compras sin descuentos
				
				
				if($Descuento == 0){
				//para la cxc.
					if ($MontoRetenidoIVA == 0){
						$montoCXC = bcadd($Haber,$montoiva,2);
					}else{
						$montoCXC = bcadd($Haber,$montoiva,2);
						$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	 
				//&		ingresarRenglon($cuentaRetenido,$descripcion,$MontoRetenidoIVA,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,$MontoRetenidoIVA,0,$idcliente
							,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
						/* Fin Para insertar los terceros */		
					}
				//   Fin Compras sin descuentos
				}else{
					$cuentaDescuento = buscarContable(48,$idTipo,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
					$montoCXC = bcadd($Haber,$montoiva,2);
					$montoCXC = bcsub($montoCXC,$Descuento,2);	
					if ($MontoRetenidoIVA != 0){	   
						$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	
				//&		ingresarRenglon($cuentaRetenido,$descripcion,$MontoRetenidoIVA,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
						//NO VA - 28/07/2014
						/* Para insertar los terceros */
							/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,$MontoRetenidoIVA,0,$idcliente
							,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
						/* Fin Para insertar los terceros */	
					}
					//para el  descuento.
				//&	ingresarRenglon($cuentaDescuento,$descripcion,$Descuento,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					//NO VA - 28/07/2014
					/* Para insertar los terceros */
						/*ingresarEnlacesTerceros_ter($cuentaDescuento,$fecha,$Descuento,0,$idTipo
						,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
					/* Fin Para insertar los terceros */	
					//para la cxp.
					//   Fin Compras con descuentos
				}
				
			//&	ingresarRenglon($cuentaCXC,$descripcion,$montoCXC,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);		
				/* Para insertar los terceros */
					ingresarEnlacesTerceros_ter($cuentaCXC,$fecha,$montoCXC,0,$idcliente
					,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
				/* Fin Para insertar los terceros */	
				//para  el costo 
				
			//&	ingresarRenglon($cuentaVenta,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				//NO VA - 28/07/2014
				/* Para insertar los terceros */
					/*ingresarEnlacesTerceros_ter($cuentaVenta,$fecha,$Debe,$Haber,$idTipo
					,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
				/* Fin Para insertar los terceros */			   
				
				//para el iva.
			//&	ingresarRenglon($cuentaiva,$descripcion,0,$montoiva,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				//NO VA - 28/07/2014
				/* Para insertar los terceros */
					/*ingresarEnlacesTerceros_ter($cuentaiva,$fecha,0,$montoiva,$idcliente
					,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
				/* Fin Para insertar los terceros */
				
				
				$cuentaCosto = buscarContable(32,$idTipo,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
				
			//&	ingresarRenglon($cuentaCosto,$descripcion,$Costo,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
				//NO VA - 28/07/2014
				/* Para insertar los terceros */
					/*ingresarEnlacesTerceros_ter($cuentaCosto,$fecha,$Costo,0,$idTipo

					,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
				/* Fin Para insertar los terceros */	
			//&	ingresarRenglon($cuentacontable,$descripcion,0,$Costo,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
				//NO VA - 28/07/2014		  
				/* Para insertar los terceros */
					/*ingresarEnlacesTerceros_ter($cuentacontable,$fecha,0,$Costo,$idTipo
					,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
				/* Fin Para insertar los terceros */
			//& }//if(buscarDoc($id,$cc)==0){		
		}
}

//*****************************VENTAS VEHICULOS*************************************
function generarVentasVe_Ter($idFactura=0,$Desde="",$Hasta="",$Nota=0){
$con = ConectarBD();
$SqlStr = "select a.idfactura
	,a.fecharegistrofactura 
	,'02' as centrocosto
	,a.numerofactura as documento
	,(select cuenta from detalleintegracion x1 where x1.idencabezado = 25 and x1.idobjeto = 15 and x1.sucursal = 1) as cuentaiva
	,a.idcliente as idcliente
	,(select concat(nombre,' ',apellido) from ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 where x3.id = a.idcliente) as descliente
	,a.descuentofactura
	,a.id_empresa
from ".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a 
where a.iddepartamentoorigenfactura=2 ";
if($idFactura != 0){ 
    $SqlStr.=" and a.idfactura= ".$idFactura;
}else{
    $SqlStr.=" and a.fecharegistrofactura between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
}
    $SqlStr.=" order by a.idfactura";
$execFact =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__);
    $fechaAnt = "";
	$icomprobant = 0; 
while ($rowFact = ObtenerFetch($execFact)){
$idFactura = $rowFact[0];
$id = $rowFact[0];
$fecha = $rowFact[1];
$cc = $rowFact[2];
					$NC="";
					if ($Nota==0){
					  $id=$rowFact[0];
				   }else{
					  $id =$Nota; 
				   }
				   if ($Nota==0){
				          $documento=$rowFact[3];
						  	  $ct='02';
							  $dt='02';
							  $cc='02';			
					}else{
					        $NC=" N/C ";
						    $ct='02';// venta compra,pago,cobro
							$dt='01';// factura ,recibo, cheque
							$cc='02';		
							$SqlStr = "select numeracion_nota_credito,fechaNotaCredito
							from ".$_SESSION['bdEmpresa'].".cj_cc_notacredito where idNotaCredito = $Nota";
							$execDoc =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
							while ($rowDoc = ObtenerFetch($execDoc)) {
								  $documento=$rowDoc[0];
								  $fecha = $rowDoc[1];
								
							}
					}		
$cuentaiva = $rowFact[4];
$idcliente = $rowFact[5];
$Descliente = $rowFact[6];
$Descuento = $rowFact[7];
$sucursal = $rowFact[8];

     $cuentaDescuento = buscarContable(25,7,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
$Costo =0 ;	
//& if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){  

				if ($fechaAnt != $fecha){
				     $icomprobant++;
					 $fechaAnt = $fecha;
				  }
			    $MontoRetenidoIVA = 0;  
				 /* $SqlStr="select sum(ivaRetenido) from ".$_SESSION['bdEmpresa'].".cj_cc_retenciondetalle where idfactura = $id";
				   $exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
				   $row = ObtenerFetch($exec3);
				   if ($row[0] > 0){
			           $MontoRetenidoIVA = $row[0];     	   
					   $cuentaRetenido = buscarContable(25,17,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
				   }
						*/
$SqlStr = "select f.id_condicion_unidad as condicionunidad
       ,d.nom_modelo as descripcion
       ,0 as Debe 
       ,sum(b.precio_unitario) as haber 
       ,a.calculoIvaFactura+a.calculoivadelujofactura
       ,b.costo_compra as costo 
       ,d.id_modelo
       ,b.id_unidad_fisica
       ,b.iva as porcentaje_iva
	   ,a.numeroFactura
	   ,a.id_empresa
		from ".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
			,".$_SESSION['bdEmpresa'].".cj_cc_factura_detalle_vehiculo b
			,".$_SESSION['bdEmpresa'].".an_uni_bas c 
			,".$_SESSION['bdEmpresa'].".an_unidad_fisica f
			,".$_SESSION['bdEmpresa'].".an_modelo  d
		where a.idfactura = b.id_factura
			and f.id_uni_bas= c.id_uni_bas
			and a.iddepartamentoorigenfactura = 2
			and c.mod_uni_bas = d.id_modelo
			and f.id_unidad_fisica = b.id_unidad_fisica";
if($idFactura != 0){
    $SqlStr.=" and a.idfactura= ".$idFactura;
}else{
    $SqlStr.=" and a.fecharegistrofactura between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
}
$SqlStr.=" group by a.idfactura  
order by a.fecharegistrofactura,a.idfactura";
$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
					$cuentacontable =  "";
					 $cuentaVenta = "";
					 $cuentaCosto = "";
					  $id_unidad_fisica="";
				  $DebeV = 0;
				  $HaberV = 0;
				  $montoivaV = 0;
				  $EntroVehiculo =""; 
				  $Costo = 0;
				   $montoiva =0;
			while ($row = ObtenerFetch($exec)) {
			      $EntroVehiculo ="S"; 
				  $descripcion = $row[1];
				  $DebeV = $row[2];
				  $HaberV = $row[3];
				  $montoiva = $row[4];
				  $CostoV = $row[5];
				  $id_modelo = $row[6];
				  $id_unidad_fisica = $row[7];
				  $porcentajeIVA = $row[8];
				  $NroCompra = $row[9];
				  $sucursal = $row[10];
				  
				  $descripcion  = $descripcion . " " .$Descliente. " Nro Compra ".$NroCompra;
					
 
 
 
				  if ($row[0] != 1){
				     $cuentacontable =  buscarContable(3,$id_modelo,$sucursal);
							  if($Nota == 0){  
								 $cuentaVenta = buscarContable(30,$id_modelo,$sucursal);// para los vehiculos usados
							  }else{	 
							     $cuentaVenta = buscarContable(52,$id_modelo,$sucursal);// devoluciones vehiculos usados 
							  }
    				 $cuentaCosto = buscarContable(34,$id_modelo,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
					 $cuentaDescuento = buscarContable(50,$id_modelo,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
				  }else{
				     $cuentacontable =  buscarContable(2,$id_modelo,$sucursal);
						   if($Nota == 0){  
								$cuentaVenta = buscarContable(29,$id_modelo,$sucursal);// para los vehiculos usados
							}else{	 
								$cuentaVenta = buscarContable(51,$id_modelo,$sucursal);// devoluciones vehiculos usados 
						    }
					  $cuentaCosto = buscarContable(33,$id_modelo,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
					  $cuentaDescuento = buscarContable(49,$id_modelo,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
				  }
			}//while ($row = ObtenerFetch($exec)) {	
		 $Costo = $CostoV;
	 
	
$SqlStr = "select  e.id_accesorio as condicionunidad
       ,e.nom_accesorio as descripcion
       ,0 as Debe 
       ,sum(b.precio_unitario) as haber 
       ,a.calculoIvaFactura+a.calculoivadelujofactura
       ,sum(b.costo_compra) as costo 
       ,b.iva as porcentaje_iva
	   ,a.id_empresa
		from ".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
			,".$_SESSION['bdEmpresa'].".cj_cc_factura_detalle_accesorios b
			,".$_SESSION['bdEmpresa'].".an_accesorio e
		where a.idfactura = b.id_factura
			and a.iddepartamentoorigenfactura = 2
			and e.id_accesorio = b.id_accesorio";
$SqlStr.=" and a.idfactura= ".$idFactura;
if ($EntroVehiculo == "S"){
		$SqlStr.=" group by a.idfactura  
		order by a.fecharegistrofactura,a.idfactura";
}else{
		$SqlStr.=" group by a.idfactura,e.id_accesorio  
		order by a.fecharegistrofactura,a.idfactura,e.id_accesorio";
}
$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
				  $DebeA = 0;
				  $HaberA = 0;
				  $montoivaA = 0;
				  $DebeSuma = 0;
				  $HaberSuma = 0;
			while ($row = ObtenerFetch($exec)) {
				  $id_accesorio = $row[0];
				  $DebeA = $row[2];
				  $HaberA = $row[3];
				  $montoivaA = $row[4];
                  $CostoA = $row[5];
				  $sucursal = $row[7];

				  if ($EntroVehiculo == ""){
				     $descripcion = $row[1]. " " .$Descliente;
					 //$descripcion  = "ACCESORIOS " . " " .$Descliente;
				     $cuentacontable =  buscarContable(37,$id_accesorio,$sucursal);
					 //$cuentaVenta = buscarContable(36,$id_accesorio,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
					  if($Nota == 0){  
						 $cuentaVenta = buscarContable(29,$id_accesorio,$sucursal);// para los vehiculos usados
					  }else{	 
					     $cuentaVenta = buscarContable(51,$id_accesorio,$sucursal);// devoluciones vehiculos usados 
					}
					 
					 $cuentaCosto = buscarContable(38,$id_accesorio,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
					
					  $Costo =$CostoA;
					  $Debe = $DebeA;
					  $Haber = $HaberA;
					  $DebeSuma = bcadd($DebeSuma,$DebeA,2);
					  $HaberSuma = bcadd($HaberSuma,$HaberA,2);
				      $montoiva =$montoivaA;
					 $cuentaCXC = buscarContable(19,$idcliente,$sucursal);	   
				   // Compras sin descuentos
				   if ($Nota ==0){ 
				//&    ingresarRenglon($cuentaVenta,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
					 	/* Para insertar los terceros */
						/*ingresarEnlacesTerceros_ter($cuentaVenta,$fecha,$Debe,$Haber,$id_accesorio
						,"an_accesorio","id_accesorio|nom_accesorio",$descripcion);*/
						/* Fin Para insertar los terceros */	
					 
				   }else{
				//&    ingresarRenglon($cuentaVenta,$NC.$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
							//NO VA - 28/07/2014
					   /* Para insertar los terceros */
						/*ingresarEnlacesTerceros_ter($cuentaVenta,$fecha,$Haber,$Debe,$id_accesorio
						,"an_accesorio","id_accesorio|nom_accesorio",$descripcion);*/
						/* Fin Para insertar los terceros */	
				   }
				  } 
		  
			 }//while ($row = ObtenerFetch($exec)) {
							 
             if ($EntroVehiculo == ""){
			          $Debe = $DebeSuma;
					  $Haber = $HaberSuma;	
			  //para el iva.
			     if ($Nota ==0){
				//&   ingresarRenglon($cuentaiva,$descripcion,0,$montoiva,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
					  /* Para insertar los terceros */
						  /* ingresarEnlacesTerceros_ter($cuentaiva,$fecha,0,$montoiva,$idcliente
						   ,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
						/* Fin Para insertar los terceros */	
				 }else{
				//&	ingresarRenglon($cuentaiva,$NC.$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
					  /* Para insertar los terceros */
						  /* ingresarEnlacesTerceros_ter($cuentaiva,$fecha,$montoiva,0,$idcliente
						   ,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
						/* Fin Para insertar los terceros */	
				}				 
				  if($Descuento == 0){
				  //para la cxc.
						   if ($MontoRetenidoIVA == 0){
								$montoCXC = bcadd($Haber,$montoiva,2);
						   }else{
								$montoCXC = bcadd($Haber,$montoiva,2);
								$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	
							   if ($Nota ==0){	
								//&	ingresarRenglon($cuentaRetenido,$descripcion,$MontoRetenidoIVA,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
									//NO VA - 28/07/2014
									/* Para insertar los terceros */
										/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,$MontoRetenidoIVA,0,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
									/* Fin Para insertar los terceros */	
									
							   }else{
								//&	ingresarRenglon($cuentaRetenido,$NC.$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
									//NO VA - 28/07/2014
									/* Para insertar los terceros */
										/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
									/* Fin Para insertar los terceros */	
								}							   
						   }
				 //   Fin Compras sin descuentos
				    }else{
				 		     
							   $montoCXC = bcadd($Haber,$montoiva,2);
						       $montoCXC = bcsub($montoCXC,$Descuento,2);	
					         if ($MontoRetenidoIVA != 0){	   
							   $montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);
							if ($Nota ==0){							   
							//& ingresarRenglon($cuentaRetenido,$descripcion,$MontoRetenidoIVA,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
									//NO VA - 28/07/2014
								  /* Para insertar los terceros */
										/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,$MontoRetenidoIVA,0,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
								 /* Fin Para insertar los terceros */	
							}else{
							//&     ingresarRenglon($cuentaRetenido,$NC.$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
									//NO VA - 28/07/2014
								  /* Para insertar los terceros */
										/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
								  /* Fin Para insertar los terceros */	
								  
							}							
							 }
						   //para el  descuento.
						   if ($Nota ==0){	
							//&	ingresarRenglon($cuentaDescuento,$descripcion,$Descuento,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
								//NO VA - 28/07/2014
								/* Para insertar los terceros */
								    /* ingresarEnlacesTerceros_ter($cuentaDescuento,$fecha,$Descuento,0,$idTipo
								     ,"an_modelo","id_modelo|nom_modelo",$descripcion);*/
								/* Fin Para insertar los terceros */	
						   }else{
							//&	ingresarRenglon($cuentaDescuento,$NC.$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
								//NO VA - 28/07/2014
								/* Para insertar los terceros */
								     /*ingresarEnlacesTerceros_ter($cuentaDescuento,$fecha,0,$Descuento,$idTipo
								     ,"an_modelo","id_modelo|nom_modelo",$descripcion);*/
								/* Fin Para insertar los terceros */	
						   }
							//para la cxp.
						    //   Fin Compras con descuentos
					}
					if ($Nota ==0){	
					//&	ingresarRenglon($cuentaCXC,$descripcion,$montoCXC,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);		
						/* Para insertar los terceros */
										ingresarEnlacesTerceros_ter($cuentaCXC,$fecha,$montoCXC,0,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
							/* Fin Para insertar los terceros */
						
					  //ingresarRenglon($cuentaCosto,$descripcion,$Costo,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					  //ingresarRenglon($cuentacontable,$descripcion,0,$Costo,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);		  						
					}else{
					//&  ingresarRenglon($cuentaCXC,$NC.$descripcion,0,$montoCXC,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);		
					          /* Para insertar los terceros */
										ingresarEnlacesTerceros_ter($cuentaCXC,$fecha,0,$montoCXC,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
							      /* Fin Para insertar los terceros */
					  //ingresarRenglon($cuentaCosto,$NC.$descripcion,0,$Costo,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
					  //ingresarRenglon($cuentacontable,$NC.$descripcion,$Costo,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);		  						
					}					
			 }
		 
			  if ($EntroVehiculo == "S"){
			 	  $Debe = bcadd($DebeA,$DebeV,2);
				  $Haber = bcadd($HaberA,$HaberV,2);
				  //$montoiva = bcadd($montoivaA,$montoivaV,2);16-04-2011
							  
				  $cuentaCXC = buscarContable(19,$idcliente,$sucursal);	   
				   // Compras sin descuentos
				if ($Nota ==0){	
						//&   ingresarRenglon($cuentaVenta,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
						     /* Para insertar los terceros */
								/*ingresarEnlacesTerceros_ter($cuentaVenta,$fecha,$Debe,$Haber,$id_accesorio
								,"an_accesorio","id_accesorio|nom_accesorio",$descripcion);*/
							/* Fin Para insertar los terceros */	
						   
				    //para el iva.
				   //& ingresarRenglon($cuentaiva,$descripcion,0,$montoiva,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
     				   /* Para insertar los terceros */
						  /* ingresarEnlacesTerceros_ter($cuentaiva,$fecha,0,$montoiva,$idcliente
						   ,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
						/* Fin Para insertar los terceros */	
				   
				 }else{
					//& ingresarRenglon($cuentaVenta,$NC.$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
						/* Para insertar los terceros */
						/*ingresarEnlacesTerceros_ter($cuentaVenta,$fecha,$Haber,$Debe,$id_accesorio
						,"an_accesorio","id_accesorio|nom_accesorio",$descripcion);*/
						/* Fin Para insertar los terceros */	
				    //para el iva.
				    //& ingresarRenglon($cuentaiva,$NC.$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							//NO VA - 28/07/2014
					/* Para insertar los terceros */
						   /*ingresarEnlacesTerceros_ter($cuentaiva,$fecha,$montoiva,0,$idcliente
						   ,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
						/* Fin Para insertar los terceros */	
				}				 
				  if($Descuento == 0){
				  //para la cxc.
						   if ($MontoRetenidoIVA == 0){
								$montoCXC = bcadd($Haber,$montoiva,2);
						   }else{
								$montoCXC = bcadd($Haber,$montoiva,2);
								$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	 
							if ($Nota ==0){		
							//&	ingresarRenglon($cuentaRetenido,$descripcion,$MontoRetenidoIVA,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
									//NO VA - 28/07/2014
									/* Para insertar los terceros */
										/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,$MontoRetenidoIVA,0,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
									/* Fin Para insertar los terceros */
								
							}else{
							//&	ingresarRenglon($cuentaRetenido,$NC.$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
									//NO VA - 28/07/2014
									/* Para insertar los terceros */
									/*	ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
									/* Fin Para insertar los terceros */
							}		
						   }
				 //   Fin Compras sin descuentos
				    }else{
				 		     
							   $montoCXC = bcadd($Haber,$montoiva,2);
						       $montoCXC = bcsub($montoCXC,$Descuento,2);	
					         if ($MontoRetenidoIVA != 0){	   
							   $montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	
							  if ($Nota ==0){		 
							//&   ingresarRenglon($cuentaRetenido,$descripcion,$MontoRetenidoIVA,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
									//NO VA - 28/07/2014
								   	/* Para insertar los terceros */
										/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,$MontoRetenidoIVA,0,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
									/* Fin Para insertar los terceros */	
								  
							  }else{
							//&	  ingresarRenglon($cuentaRetenido,$NC.$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
										//NO VA - 28/07/2014
										/* Para insertar los terceros */
										/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
									    /* Fin Para insertar los terceros */
								}							  
							 }
						   //para el  descuento.
						   if ($Nota ==0){		 
						    //&      ingresarRenglon($cuentaDescuento,$descripcion,$Descuento,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
									//NO VA - 28/07/2014
								  /* Para insertar los terceros */
										/*ingresarEnlacesTerceros_ter($cuentaDescuento,$fecha,$Descuento,0,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
									/* Fin Para insertar los terceros */	
								  
							}else{
							//&	  ingresarRenglon($cuentaDescuento,$NC.$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
									//NO VA - 28/07/2014
								   	/* Para insertar los terceros */
										/*ingresarEnlacesTerceros_ter($cuentaDescuento,$fecha,0,$Descuento,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
									/* Fin Para insertar los terceros */	
								  
							}							
							//para la cxp.
						    //   Fin Compras con descuentos
					} 
						if ($Nota ==0){		 
							//& ingresarRenglon($cuentaCXC,$descripcion,$montoCXC,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);		
								    /* Para insertar los terceros */
										ingresarEnlacesTerceros_ter($cuentaCXC,$fecha,$montoCXC,0,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
									/* Fin Para insertar los terceros */
							
						}else{
							//& ingresarRenglon($cuentaCXC,$NC.$descripcion,0,$montoCXC,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);		
							        /* Para insertar los terceros */
										ingresarEnlacesTerceros_ter($cuentaCXC,$fecha,0,$montoCXC,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
									/* Fin Para insertar los terceros */
						}						
						//para  el costo 
				}	 
					    // para  los accesorios	  
					if ($id_unidad_fisica!=""){
			            $SqlStr=" select a.id_accesorio,a.costo_partida,b.iva_accesorio,b.nom_accesorio
			              from ".$_SESSION['bdEmpresa'].".an_partida a
			              ,".$_SESSION['bdEmpresa'].".an_accesorio b
			              ,".$_SESSION['bdEmpresa'].".an_unidad_fisica c
			              where a.id_accesorio = b.id_accesorio
			              and c.id_unidad_fisica = a.id_unidad_fisica 
						  and c.id_unidad_fisica = $id_unidad_fisica and tipo_partida= 'COMPRA'";
						  $exec5 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
						  $montoIvaAccesorio = 0;
						  $costo_partidaSuma = 0;
				          while($row3 = ObtenerFetch($exec5)){
						       $id_accesorio = $row3[0];
							   $costo_partida = $row3[1];
							   $aplicaIVA = $row3[2];
							   $nom_accesorio = $row3[3];
							 //  $cuentaAccesorio =buscarContable(21,$id_accesorio,$sucursal);// 21 es compras adminstriativas en el encabezado .
							   $cuentaAccesorio = $cuentacontable;
						      // ingresarRenglon($cuentaAccesorio,$nom_accesorio,$costo_partida,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D');
							  if ($aplicaIVA == 1){
							   		   $calIVA = bcmul($costo_partida,$porcentajeIVA,2);
									   $calIVA =  bcdiv($calIVA,100,2);
									   $montoIvaAccesorio = bcadd($montoIvaAccesorio,$calIVA,2);
							  }
							   $costo_partidaSuma = bcadd($costo_partidaSuma,$costo_partida,2);
						  }
					// para  los accesorios
						$Costo = bcadd($Costo,$costo_partidaSuma,2); 
						$descripcion = $row[1]. " ". $Descliente. " Nro Venta: ". $documento;
					if ($Nota ==0){		 	
					//&	ingresarRenglon($cuentaCosto,$descripcion,$Costo,0,$NroCompra,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
							//NO VA - 28/07/2014
						    	/* Para insertar los terceros */
								/*ingresarEnlacesTerceros_ter($cuentaCosto,$fecha,$Costo,0,$id_accesorio
								,"an_accesorio","id_accesorio|nom_accesorio",$descripcion);*/
								/* Fin Para insertar los terceros */
					//&	ingresarRenglon($cuentacontable,$descripcion,0,$Costo,$NroCompra,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);	
							//NO VA - 28/07/2014	  						
							/* Para insertar los terceros */
								/*ingresarEnlacesTerceros_ter($cuentacontable,$fecha,0,$Costo,$id_accesorio
								,"an_accesorio","id_accesorio|nom_accesorio",$descripcion);*/
								/* Fin Para insertar los terceros */
					}else{
					//&	ingresarRenglon($cuentaCosto,$NC.$descripcion,0,$Costo,$NroCompra,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
							//NO VA - 28/07/2014
							   /* Para insertar los terceros */
								/*ingresarEnlacesTerceros_ter($cuentaCosto,$fecha,0,$Costo,$id_accesorio
								,"an_accesorio","id_accesorio|nom_accesorio",$descripcion);*/
								/* Fin Para insertar los terceros */
					//&	ingresarRenglon($cuentacontable,$NC.$descripcion,$Costo,0,$NroCompra,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);	
							//NO VA - 28/07/2014	  						
						        /* Para insertar los terceros */
								/*ingresarEnlacesTerceros_ter($cuentacontable,$fecha,$Costo,0,$id_accesorio
								,"an_accesorio","id_accesorio|nom_accesorio",$descripcion);*/
								/* Fin Para insertar los terceros */
					}					
			      }
	//&	}//if(buscarDoc($id,$cc)==0){ 
  }//while $rowFact = ObtenerFetch($execFact)
  

}

//**************************VENTAS SERVICIOS****************************************
function generarVentasSe_Ter($idFactura=0,$Desde="",$Hasta="",$Nota=0){
$con = ConectarBD();
		$SqlStr = "select a.idfactura
		,a.idcliente
		,a.fecharegistrofactura
		,a.descuentofactura  
		 ,a.numerofactura as documento		
		 ,a.calculoIvaFactura+a.calculoivadelujofactura
		 ,a.id_empresa
		 from ".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
		 where a.iddepartamentoorigenfactura = 1";
		 if($idFactura != 0){
		    $SqlStr.=" and a.idfactura= ".$idFactura;
		}else{
		    $SqlStr.=" and a.fecharegistrofactura between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
		}
		$execFact =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
		      $icomprobant=0;
			  $fechaAnt = "";
while ($rowFact = ObtenerFetch($execFact)) {
$idFactura=$rowFact[0];
$MontoIVATotal = $rowFact[5];
$SqlStr ="select count(*) from 
	".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
	,".$_SESSION['bdEmpresa'].".sa_orden b
	,".$_SESSION['bdEmpresa'].".sa_enlace_garantia c
	,".$_SESSION['bdEmpresa'].".sa_det_enlace_garantia d
	,".$_SESSION['bdEmpresa'].".sa_vale_salida e
where 
	a.numeropedido = b.id_orden
	and c.id_orden = b.id_orden
	and c.id_enlace_garantia =  d.id_enlace_garantia
    and d.id_vale_salida = e.id_vale_salida
	and a.idDepartamentoOrigenFactura= 1
    and a.idfactura =  $idFactura";
	$execCont =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
	$rowCont  =  ObtenerFetch($execCont);
	$Entra = true;
	if($rowCont[0] >= 1){
	  $Entra = false;  
	}

//if($Entra == true){
				   if ($Nota==0){
					  $id=$rowFact[0];
				   }else{
					  $id =$Nota; 
				   }
				   $idcliente=$rowFact[1];
				   $fecha=$rowFact[2];
				   $Descuento=$rowFact[3];
				   $sucursal=$rowFact[6];
				     $NC="";
				   if ($Nota==0){
				          $documento=$rowFact[4];
						  	  $ct='02';
							  $dt='02';
							  $cc='03';			
					}else{
					          $NC=" N/C ";
						      $ct='02';
							  $dt='01';
							  $cc='03';			
							$SqlStr = "select numeracion_nota_credito,fechaNotaCredito
							from ".$_SESSION['bdEmpresa'].".cj_cc_notacredito where idNotaCredito = $Nota";
							$execDoc =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
							while ($rowDoc = ObtenerFetch($execDoc)) {
								  $documento=$rowDoc[0];
								  $fecha=$rowDoc[1];
								 
							}
					}					
				   
				   $cuentaCXC = buscarContable(18,$idcliente,$sucursal);	
				   $MontoRetenidoIVA = 0; 
							   
		  if ($fechaAnt != $fecha){
			 $icomprobant++;
			 $fechaAnt = $fecha;
			}
	//& if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){	
				 /*  $SqlStr="select sum(ivaRetenido) from ".$_SESSION['bdEmpresa'].".cj_cc_retenciondetalle where idfactura = $idFactura";
				   $exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
				   $row = ObtenerFetch($exec3);
				   if ($row[0] > 0){
					   $MontoRetenidoIVA = $row[0];     	   
					   $cuentaRetenido = buscarContable(27,17,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
				   }*/
				   
						$SqlStr = "select a.idfactura
							       ,a.fecharegistrofactura
							       ,'03' as centrocosto
							       ,'' as cuentacontable
							       ,c.descripcion as descripcion
							       ,0 as Debe 
							       ,sum(b.cantidad*b.precio_unitario) as haber 
							       ,a.numerofactura as documento
							       ,sum(round(((b.cantidad*b.precio_unitario)/100)*b.iva,2))
							       ,(select cuenta from detalleintegracion x1 where x1.idencabezado = 26 and x1.idobjeto = 15 and x1.sucursal = 1) as cuentaiva
							       ,a.idcliente as idcliente
							       ,(select concat(nombre,' ',apellido) from ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 where x3.id = a.idcliente) as descliente
							       ,a.descuentofactura 
								   ,sum(b.cantidad*b.costo_compra) as costo 
								   ,c.id_clave_movimiento as clave_movimiento
								   ,a.id_empresa
									from ".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
									,".$_SESSION['bdEmpresa'].".cj_cc_factura_detalle b
									,".$_SESSION['bdEmpresa'].".v_clavemovimiento c 
									where a.idfactura = b.id_factura
									and a.iddepartamentoorigenfactura = 1
									and c.id_documento = a.idfactura";
						$SqlStr.=" and a.idfactura= ".$idFactura;
						$SqlStr.="  group by a.idfactura,c.id_clave_movimiento
								order by a.fecharegistrofactura,a.idfactura,c.id_clave_movimiento";
						$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__);
						 	$montoCXCRepuestos= 0;
							$montoIVARepuestos= 0;
						    while ($row = ObtenerFetch($exec)) {
							  $id = $row[0];
							  $cc = $row[2];
							  $cuentacontable = $row[3];
							  $sucursal = $row[15];
							  if (is_null($cuentacontable) || $cuentacontable == '' ){
							     $cuentacontable =  buscarContable(4,0,$sucursal);
							  }
							  $descripcion = $row[4];
							  $Debe = $row[5];
							  $Haber = $row[6];
							//$documento = $row[7];
							  $montoiva = $row[8];
							  //$cuentaiva = $row[9];
							  $idcliente = $row[10];
							  $Descliente = $row[11];
							  $Costo = $row[13];
							  $idTipo = $row[14];
							  $descripcion  = $descripcion . " " .$Descliente;
							  
							  $cuentaiva = buscarContable(26,15,$sucursal);
							 
									if($Nota == 0){
									  $cuentaVenta = buscarContable(28,$idTipo,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
									}else{
									  $cuentaVenta = buscarContable(53,$idTipo,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.	
                                    }									
									 // Compras sin descuentos
									 if($Nota == 0){
									//&     ingresarRenglon($cuentaVenta,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
												//NO VA - 28/07/2014
												/* Para insertar los terceros */
													/*ingresarEnlacesTerceros_ter($cuentaVenta,$fecha,$Debe,$Haber,$idTipo
													,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
												/* Fin Para insertar los terceros */	
									 }else{
									//&     ingresarRenglon($cuentaVenta,$NC.$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
												//NO VA - 28/07/2014
 												/* Para insertar los terceros */
													/*ingresarEnlacesTerceros_ter($cuentaVenta,$fecha,$Haber,$Debe,$idTipo
													,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
												/* Fin Para insertar los terceros */	
									 }		
									  			$montoCXCRepuestos= bcadd($montoCXCRepuestos,$Haber,2);
												$montoIVARepuestos= bcadd($montoIVARepuestos,$montoiva,2);
										//para  el costo 
								          $cuentaCosto = buscarContable(32,$idTipo,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
									if($Nota == 0){	  
								    //&    ingresarRenglon($cuentaCosto,$descripcion,$Costo,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
													//NO VA - 28/07/2014
													/* Para insertar los terceros */
													/*ingresarEnlacesTerceros_ter($cuentaCosto,$fecha,$Costo,0,$idTipo
													,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
													/* Fin Para insertar los terceros */
									//&	  ingresarRenglon($cuentacontable,$descripcion,0,$Costo,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);		  
													//NO VA - 28/07/2014
													/* Para insertar los terceros */
													/*ingresarEnlacesTerceros_ter($cuentacontable,$fecha,0,$Costo,$idTipo
													,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
													/* Fin Para insertar los terceros */
									}else{
									//&	  ingresarRenglon($cuentaCosto,$NC.$descripcion,0,$Costo,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
													//NO VA - 28/07/2014
										  			/* Para insertar los terceros */
													/*ingresarEnlacesTerceros_ter($cuentaCosto,$fecha,0,$Costo,$idTipo
													,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
													/* Fin Para insertar los terceros */
									//&	  ingresarRenglon($cuentacontable,$NC.$descripcion,$Costo,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);		  
													//NO VA - 28/07/2014
													/* Para insertar los terceros */
													/*ingresarEnlacesTerceros_ter($cuentacontable,$fecha,$Costo,0,$idTipo
													,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
													/* Fin Para insertar los terceros */
									}
						 	}
							
							// *******************************************************************************************
							// **********************************Notas Adicionales****************************************
							// *******************************************************************************************
							$SqlStr = "select a.idfactura
						       ,a.fecharegistrofactura
						       ,'03' as centrocosto
						       ,(select cuenta from detalleintegracion x1 where x1.idencabezado = 27 and x1.idobjeto = 40 and x1.sucursal = 1) as cuentacontable
						       ,b.descripcion_nota as descripcion
						       ,0 as Debe 
						       ,sum(b.precio) as haber 
						       ,a.numerofactura as documento
						       ,sum(round(((b.precio)/100)*a.porcentajeivafactura,2))
						       ,(select cuenta from detalleintegracion x1 where x1.idencabezado = 27 and x1.idobjeto = 15 and x1.sucursal = 1) as cuentaiva
						       ,a.idcliente as idcliente
						       ,(select concat(nombre,' ',apellido) from ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 where x3.id = a.idcliente) as descliente
						       ,a.descuentofactura 
							   ,a.id_empresa
						from ".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
						,".$_SESSION['bdEmpresa'].".sa_det_fact_notas b
						where a.idfactura = b.idfactura
						and a.iddepartamentoorigenfactura = 1";
						    $SqlStr.=" and a.idfactura= ".$idFactura;
							$SqlStr.=" group by a.idfactura
						order by a.fecharegistrofactura,a.idfactura";
						$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
								$montoCXCNotas= 0;
								$montoIVANotas= 0;
							 while ($row = ObtenerFetch($exec)) {
							  $cuentacontable = $row[3];
							  $sucursal = $row[13];
							  if (is_null($cuentacontable)){
							     $cuentacontable =  buscarContable(4,0,$sucursal);
							  }
							  $descripcion = $row[4];
							  $Debe = $row[5];
							  $Haber = $row[6];
							  //$documento = $row[7];
							  $montoiva = $row[8];
							  //$cuentaiva = $row[9];
							  $idcliente = $row[10];
							  $Descliente = $row[11];
							  $descripcion  = $descripcion . " " .$Descliente;
							  
							  $cuentaiva = buscarContable(27,15,$sucursal);
							  
									$cuentaVenta = buscarContable(27,14,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
									if($Nota == 0){		
								//&		ingresarRenglon($cuentaVenta,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
											//NO VA - 28/07/2014
											/* Para insertar los terceros */
												/*ingresarEnlacesTerceros_ter($cuentaVenta,$fecha,$Debe,$Haber,$idTipo
												,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
											/* Fin Para insertar los terceros */			   
									}else{
								//&		ingresarRenglon($cuentaVenta,$NC.$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
											//NO VA - 28/07/2014
											/* Para insertar los terceros */
												/*ingresarEnlacesTerceros_ter($cuentaVenta,$fecha,$Haber,$Debe,$idTipo
												,"v_clavemovimiento","id_clave_movimiento|descripcion",$descripcion);*/
											/* Fin Para insertar los terceros */	
									}									
										$montoCXCNotas= bcadd($montoCXCNotas,$Haber,2);
									
										$montoIVANotas= bcadd($montoiva,$montoIVANotas,2);
							 }
							 
							 // *******************************************************************************************
							// **********************************TOT*******************************************************
							// ********************************************************************************************
							$SqlStr = "select
								a.idfactura
								,a.fecharegistrofactura
								,'03' as centrocosto
								,(select cuenta from detalleintegracion x1 where x1.idencabezado = 27 and x1.idobjeto = 46 and x1.sucursal = 1) as cuentacontable
								,'TOT ' as descripcion
								,0 as Debe 
								,round(sum(d.monto_subtotal+(d.monto_subtotal*(b.porcentaje_tot/100))),2) as haber 
								,a.numerofactura as documento
								,sum(round(((d.monto_subtotal+(d.monto_subtotal*(b.porcentaje_tot/100)))/100)*a.porcentajeivafactura,2))
								,(select cuenta from detalleintegracion x1 where x1.idencabezado = 27 and x1.idobjeto = 15 and x1.sucursal = 1) as cuentaiva
								,a.idcliente as idcliente
								,(select concat(nombre,' ',apellido) from ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 where x3.id = a.idcliente) as descliente
								,a.descuentofactura
								,a.id_empresa  
							from
								".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
								,".$_SESSION['bdEmpresa'].".sa_det_orden_tot b
								,".$_SESSION['bdEmpresa'].".sa_orden c
								,".$_SESSION['bdEmpresa'].".sa_orden_tot d
								,".$_SESSION['bdEmpresa'].".sa_tipo_orden e
							where
								d.id_orden_servicio = c.id_orden
								and b.id_orden = c.id_orden
								and a.numeropedido = c.id_orden 
								and e.id_tipo_orden = c.id_tipo_orden
								and a.iddepartamentoorigenfactura = 1";
						    $SqlStr.=" and a.idfactura= ".$idFactura;
							$SqlStr.=" group by a.idfactura
						order by a.fecharegistrofactura,a.idfactura";
						$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
								$montoCXCTOT= 0;
								$montoIVATOT= 0;
							 while ($row = ObtenerFetch($exec)) {
							  $cuentacontable = $row[3];
							  $sucursal = $row[13];
							  if (is_null($cuentacontable)){
							     $cuentacontable =  buscarContable(4,0,$sucursal);
							  }
							  $descripcion = $row[4];
							  $Debe = $row[5];
							  $Haber = $row[6];
							  //$documento = $row[7];
							  $montoiva = $row[8];
							  //$cuentaiva = $row[9];
							  $idcliente = $row[10];
							  $Descliente = $row[11];
							  $descripcion  = $descripcion . " " .$Descliente;
							  
							  $cuentaiva = buscarContable(27,15,$sucursal);
							  
										$cuentaVenta = buscarContable(27,46,$sucursal);
									if($Nota == 0){		
								//&		ingresarRenglon($cuentaVenta,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
											//NO VA - 28/07/2014
											/* Para insertar los terceros */
												/*ingresarEnlacesTerceros_ter($cuentaVenta,$fecha,$Debe,$Haber,$idcliente
												,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
											/* Fin Para insertar los terceros */		
									}else{
								//&		ingresarRenglon($cuentaVenta,$NC.$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
											//NO VA - 28/07/2014
											/* Para insertar los terceros */
												/*ingresarEnlacesTerceros_ter($cuentaVenta,$fecha,$Haber,$Debe,$idcliente
												,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
											/* Fin Para insertar los terceros */		
									}									
										$montoCXCTOT= bcadd($montoCXCTOT,$Haber,2);
										$montoIVATOT= bcadd($montoiva,$montoIVATOT,2);
									
										
							 }
							 
							 
							 
							 
							// *******************************************************************************************
							// **********************************tempario*************************************************
							// *******************************************************************************************
							$SqlStr = "select a.idfactura
						       ,a.fecharegistrofactura
						       ,'03' as centrocosto
						       ,'' as cuentacontable
						       ,'' as descripcion
						       ,0 as Debe 
						       ,sum(b.precio) as haber 
						       ,a.numerofactura as documento
						       ,a.porcentajeivafactura
						       ,(select cuenta from detalleintegracion x1 where x1.idencabezado = 27 and x1.idobjeto = 15 and x1.sucursal = 1) as cuentaiva
						       ,a.idcliente as idcliente
						       ,(select concat(nombre,' ',apellido) from ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 where x3.id = a.idcliente) as descliente
						       ,a.descuentofactura 
						       ,b.id_modo 
							   ,b.operador
							   ,sum(round(round(ut*(b.precio_tempario_tipo_orden)/base_ut_precio,2),2)) as preciomodo2
							   ,a.id_empresa
							    from ".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
								,".$_SESSION['bdEmpresa'].".sa_det_fact_tempario b
								where a.idfactura = b.idfactura
								and a.iddepartamentoorigenfactura = 1";
							    $SqlStr.=" and a.idfactura= ".$idFactura;
								$SqlStr.="	group by a.idfactura,b.operador,b.id_tempario
									order by a.fecharegistrofactura,a.idfactura";
						$exec = EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
								$montoCXCTemp= 0;
								$montoIVATemp= 0;
							 while ($row = ObtenerFetch($exec)) {
							   $sucursal = $row[16];
							  if ($row[14]==1){
							    $cuentacontable =  buscarContable(27,37,$sucursal);
								$cuentaVenta = buscarContable(27,37,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
								$descripcion = "MANO DE OBRA MECANICA";
								 
							  }else{
							    $cuentacontable =  buscarContable(27,39,$sucursal);
								$cuentaVenta = buscarContable(27,39,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
								$descripcion = "MANO DE OBRA LATONERIA";
							  }
							  $Debe = $row[5];
							   if ($row[13]==1){
							     $Haber = $row[15];
							  }else{
							     $Haber = $row[6];
							  }
                              							   
							 // $documento = $row[7];
							  $montoiva = $Haber * $row[8]/100;
							 // $cuentaiva = $row[9];
							  $idcliente = $row[10];
							  $Descliente = $row[11];
							  $descripcion  = $descripcion . " " .$Descliente;
							  
							  $cuentaiva = buscarContable(27,15,$sucursal);
							  
 										//$cuentaVenta = buscarContable(27,40,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
										if($Nota == 0){		
									//&     	ingresarRenglon($cuentaVenta,$descripcion,$Debe,$Haber,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
											//NO VA - 28/07/2014
												/* Para insertar los terceros */
													/*ingresarEnlacesTerceros_ter($cuentaVenta,$fecha,$Debe,$Haber,$idcliente
													,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
												/* Fin Para insertar los terceros */		
										}else{
									//&		ingresarRenglon($cuentaVenta,$NC.$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
											//NO VA - 28/07/2014
												/* Para insertar los terceros */
													/*ingresarEnlacesTerceros_ter($cuentaVenta,$fecha,$Haber,$Debe,$idcliente
													,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
												/* Fin Para insertar los terceros */		
										}
										
										$montoCXCTemp= bcadd($montoCXCTemp,$Haber,2);
										$montoIVATemp= bcadd($montoIVATemp,$montoiva,2);
							}
					
							           	$montoCXCGen = bcadd($montoCXCTemp,$montoCXCNotas,2);
										$montoCXCGen = bcadd($montoCXCGen,$montoCXCRepuestos,2);
										
										$montoIVAGen = bcadd($montoIVATemp,$montoIVANotas,2);
										$montoIVAGen = bcadd($montoIVAGen,$montoIVARepuestos,2);
										
										
										$montoCXCGen = bcadd($montoCXCGen,$montoCXCTOT,2);
										$montoIVAGen = bcadd($montoIVAGen,$montoIVATOT,2);
										
										
										
										
										//$montoiva=$montoIVAGen; // modificado por que el descuento no resta el iva
										$montoiva=$MontoIVATotal;
										$Haber=$montoCXCGen;
							
									//para el iva.
									if($Nota == 0){		
								//&	   ingresarRenglon($cuentaiva," IVA ".$Descliente,0,$montoiva,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
											//NO VA - 28/07/2014
									   	/* Para insertar los terceros */
											/*ingresarEnlacesTerceros_ter($cuentaiva,$fecha,0,$montoiva,$idcliente
											,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
										/* Fin Para insertar los terceros */		
									   
									}else{
								//&		ingresarRenglon($cuentaiva,$NC." IVA ".$Descliente,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
											//NO VA - 28/07/2014
										/* Para insertar los terceros */
											/*ingresarEnlacesTerceros_ter($cuentaiva,$fecha,$montoiva,0,$idcliente
											,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
										/* Fin Para insertar los terceros */		
										}									
									  if($Descuento == 0){
									  //para la cxc.
											   if ($MontoRetenidoIVA == 0){
													$montoCXC = bcadd($Haber,$montoiva,2);
											   }else{
													$montoCXC = bcadd($Haber,$montoiva,2);
													$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	 
													if($Nota == 0){		
												//&	     ingresarRenglon($cuentaRetenido,$descripcion,$MontoRetenidoIVA,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
														//NO VA - 28/07/2014
														 /* Para insertar los terceros */
																/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,$MontoRetenidoIVA,0,$idcliente
																,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
														/* Fin Para insertar los terceros */	
													}else{
												//&	     ingresarRenglon($cuentaRetenido,$NC.$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
														//NO VA - 28/07/2014
														  /* Para insertar los terceros */
																/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idcliente
																,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
														 /* Fin Para insertar los terceros */	
														 
													}
											   }
									 //   Fin Compras sin descuentos
									    }else{
									 		       $cuentaDescuento = buscarContable(26,7,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
												   $montoCXC = bcadd($Haber,$montoiva,2);
											       $montoCXC = bcsub($montoCXC,$Descuento,2);	
										         if ($MontoRetenidoIVA != 0){	   
												   $montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	
												   if($Nota == 0){	
												    //&   ingresarRenglon($cuentaRetenido,$descripcion,$MontoRetenidoIVA,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
														//NO VA - 28/07/2014
													    /* Para insertar los terceros */
																/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,$MontoRetenidoIVA,0,$idcliente
																,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
														/* Fin Para insertar los terceros */	
												   }else{
												    //&   ingresarRenglon($cuentaRetenido,$NC.$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
														//NO VA - 28/07/2014
													   /* Para insertar los terceros */
																/*ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idcliente
																,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
														/* Fin Para insertar los terceros */
												   }
												 }
											   //para el  descuento.
											    if($Nota == 0){	
											    //&   ingresarRenglon($cuentaDescuento,$descripcion,$Descuento,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
														//NO VA - 28/07/2014
												        /* Para insertar los terceros */
																/*ingresarEnlacesTerceros_ter($cuentaDescuento,$fecha,$Descuento,0,$idcliente
																,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
														/* Fin Para insertar los terceros */
												   
												}else{
												//&   ingresarRenglon($cuentaDescuento,$NC.$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
														//NO VA - 28/07/2014
														/* Para insertar los terceros */
																/*ingresarEnlacesTerceros_ter($cuentaDescuento,$fecha,0,$Descuento,$idcliente
																,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);*/
														/* Fin Para insertar los terceros */
												}												
											    //   Fin Compras con descuentos
												
										} 
								
										if($Nota == 0){	
										//&	ingresarRenglon($cuentaCXC," CxC ".$Descliente,$montoCXC,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);									
														/* Para insertar los terceros */
																ingresarEnlacesTerceros_ter($cuentaCXC,$fecha,$montoCXC,0,$idcliente
																,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
														/* Fin Para insertar los terceros */
										}else{
										//&   ingresarRenglon($cuentaCXC,$NC." CxC ".$Descliente,0,$montoCXC,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);									
  														/* Para insertar los terceros */
																ingresarEnlacesTerceros_ter($cuentaCXC,$fecha,0,$montoCXC,$idcliente
																,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
														/* Fin Para insertar los terceros */  
										}
			//}	
	//& } //if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){	
	}//while ($rowFact = ObtenerFetch($exec)) {
}

//**************************NOTAS DE CREDITO REPUESTOS******************************
function generarNotasRe_Ter($idFactura=0,$Desde="",$Hasta=""){
$con = ConectarBD();
$SqlStr = "select a.idnotacredito
      ,a.fechanotacredito
      ,'04' as centrocosto
      ,'' as cuentacontable
      ,d.descripcion as descripcion
      ,0 as debe
      ,sum(b.cantidad*b.precio_unitario) as haber 
      ,a.numeracion_nota_credito as documento
      ,sum(round(((b.cantidad*b.precio_unitario)/100)*b.iva,2))
      ,(select cuenta from detalleintegracion x1 where x1.idencabezado = 26 and x1.idobjeto = 15 and x1.sucursal = 1) as cuentaiva
      ,a.idcliente as idcliente
      ,(select concat(nombre,' ',apellido) from ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 where x3.id = a.idcliente) as descliente
      ,a.subtotal_descuento
      ,sum(b.costo_compra*b.cantidad) as costo 
      ,d.id_tipo_articulo 
	  ,iddocumento
	  ,a.id_empresa
 from ".$_SESSION['bdEmpresa'].".cj_cc_notacredito a 
	,".$_SESSION['bdEmpresa'].".cj_cc_nota_credito_detalle b
	,".$_SESSION['bdEmpresa'].".iv_articulos c 
	,".$_SESSION['bdEmpresa'].".iv_tipos_articulos  d 
where a.idnotacredito = b.id_nota_credito
and c.id_tipo_articulo = d.id_tipo_articulo
and b.id_articulo = c.id_articulo
and iddepartamentonotacredito =0";




if($idFactura != 0){
    $SqlStr.=" and a.idnotacredito= ".$idFactura;
}else{
    $SqlStr.=" and a.fechanotacredito between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
}
$SqlStr.=" group by a.idnotacredito,c.id_tipo_articulo
order by a.fechanotacredito,a.idnotacredito,c.id_tipo_articulo";


$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
    $fechaAnt = "";
	$icomprobant = 0;
	
    while ($row = ObtenerFetch($exec)) {
	  $id = $row[0];
	  $fecha = $row[1];
	  $cc = $row[2];
	  $sucursal = $row[16];
	  $cuentacontable = $row[3]; 
	  if (is_null($cuentacontable) || $cuentacontable=='' ){
	     $cuentacontable =  buscarContable(4,0,$sucursal); 
		
	  }

	  $descripcion = $row[4];
	  $Debe = $row[5];
	  $Haber = $row[6];
	  $documento = $row[7];
	  $montoiva = $row[8];
	  //$cuentaiva = $row[9];
	  $idcliente = $row[10];
	  $Descliente = $row[11];
	  $Descuento = $row[12];
	  $Costo = $row[13];
	  $idTipo = $row[14];
	  $iddocumento =$row[15];
	  $descripcion  = $descripcion . " " .$Descliente;
	  
	  $cuentaiva = buscarContable(26,15,$sucursal);
	  
	  $ct='02';
	  $dt='01';
	  $cc='04'; 
	//&  if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			  if ($fechaAnt != $fecha){
			     $icomprobant++;
				 $fechaAnt = $fecha;
			  }
			  $cuentaVenta = buscarContable(53,$idTipo,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
			  $MontoRetenidoIVA = 0;  
			  $SqlStr="select sum(ivaRetenido) from ".$_SESSION['bdEmpresa'].".cj_cc_retenciondetalle where idfactura = $iddocumento";
			   $exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
			   $row = ObtenerFetch($exec3);
			   if ($row[0] > 0){
		           $MontoRetenidoIVA = $row[0];     	   
				   $cuentaRetenido = buscarContable(26,17,$sucursal);// 23 es compras adminstriativas en el encabezado y 5 es en el detalle de los fijos.
			   }
			   $cuentaCXC = buscarContable(17,$idcliente,$sucursal);	   
			    // Compras sin descuentos
			//&   ingresarRenglon($cuentaVenta,"N/C ".$descripcion,$Haber,$Debe,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
					/* Para insertar los terceros */
							ingresarEnlacesTerceros_ter($cuentaVenta,$fecha,$Haber,$Debe,$idcliente
							,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
					/* Fin Para insertar los terceros */
			   
			   
			    //para el iva.
			//&  ingresarRenglon($cuentaiva,"N/C ".$descripcion,$montoiva,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
   					/* Para insertar los terceros */
							ingresarEnlacesTerceros_ter($cuentaiva,$fecha,$montoiva,0,$idcliente
							,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
					/* Fin Para insertar los terceros */
			   

			   
			  if($Descuento == 0){
			  //para la cxc.
					   if ($MontoRetenidoIVA == 0){
							$montoCXC = bcadd($Haber,$montoiva,2);
					   }else{
							$montoCXC = bcadd($Haber,$montoiva,2);
							$montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	 
						//&	ingresarRenglon($cuentaRetenido,"N/C ".$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
							   	/* Para insertar los terceros */
										ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
								/* Fin Para insertar los terceros */

							
					   }
			 //   Fin Compras sin descuentos
			    }else{
			 		       $cuentaDescuento = buscarContable(26,7,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
						   $montoCXC = bcadd($Haber,$montoiva,2);
					       $montoCXC = bcsub($montoCXC,$Descuento,2);	
				         if ($MontoRetenidoIVA != 0){	   
						   $montoCXC = bcsub($montoCXC,$MontoRetenidoIVA,2);	
						//&   ingresarRenglon($cuentaRetenido,"N/C ".$descripcion,0,$MontoRetenidoIVA,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
   							   	/* Para insertar los terceros */
										ingresarEnlacesTerceros_ter($cuentaRetenido,$fecha,0,$MontoRetenidoIVA,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
								/* Fin Para insertar los terceros */

						   
						 }
					   //para el  descuento.
					//&   ingresarRenglon($cuentaDescuento,"N/C ".$descripcion,0,$Descuento,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
	   						   	/* Para insertar los terceros */
										ingresarEnlacesTerceros_ter($cuentaDescuento,$fecha,0,$Descuento,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
								/* Fin Para insertar los terceros */

					   
						//para la cxp.
					    //   Fin Compras con descuentos
				} 
					//&	ingresarRenglon($cuentaCXC,"N/C ".$descripcion,0,$montoCXC,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);		
						        /* Para insertar los terceros */
										ingresarEnlacesTerceros_ter($cuentaCXC,$fecha,0,$montoCXC,$idcliente
										,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
								/* Fin Para insertar los terceros */
						
						
				//para  el costo 
		          $cuentaCosto = buscarContable(32,$idTipo,$sucursal);// 6 es compras adminstriativas en el encabezado y 2 es en el detalle de los fijos.
		        //&  ingresarRenglon($cuentaCosto,"N/C ".$descripcion,0,$Costo,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
  						        /* Para insertar los terceros */
									/*	ingresarEnlacesTerceros_ter($cuentaCXC,$fecha,0,$montoCXC,$idTipo
										,"iv_tipos_articulos","id_tipo_articulo|descripcion",$descripcion);*/
								/* Fin Para insertar los terceros */

				  
				//&  ingresarRenglon($cuentacontable,"N/C ".$descripcion,$Costo,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);		  
								/* Para insertar los terceros */
										/*ingresarEnlacesTerceros_ter($cuentacontable,$fecha,$Costo,0,$idTipo
										,"iv_tipos_articulos","id_tipo_articulo|descripcion",$descripcion);  */
								/* Fin Para insertar los terceros */
				  
	//&	}//if(buscarDoc($id,$cc)==0){		
 	}
	

}

//**********************************************************************************
function generarCajasEntradaRe_Ter($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
// *******************************************************************************************
// *********************************GENERAR ENTRADAS A CAJAS RS*******************************
// *******************************************************************************************
$ct='03';
$dt='07';
$cc='05';
	$SqlStr = " SELECT
		c.id_encabezado_rs
		,c.fecha_pago
		,'05' as centrocosto
		,'ENTRADA R/S DE CAJA GENERAL ' as descripcion
		,SUM(b.montopagado) as monto
		,0 as documento
		,a.iddepartamentoorigenfactura
		,a.idcliente
		,a.id_empresa
    	,a.numerofactura
	FROM
		".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
		,".$_SESSION['bdEmpresa'].".sa_iv_pagos b
		,".$_SESSION['bdEmpresa'].".cj_cc_encabezado_pago_rs c
	WHERE
		b.id_factura = a.idFactura
		AND b.id_encabezado_rs = c.id_encabezado_rs
		AND a.iddepartamentoorigenfactura in(0,1)
		AND (formapago between 1 and 6 or formapago between 9 and 10)";
		
		if($idTran != 0){
			$SqlStr.=" AND c.id_encabezado_rs = ".$idTran;
		}else{
			$SqlStr.=" AND c.fecha_pago between '".date('Y-m-d',strtotime($Desde))."' and '". date('Y-m-d',strtotime($Hasta))."'";
		}
							
	$SqlStr.=" group by c.fecha_pago,a.iddepartamentoorigenfactura, a.id_empresa";
	
	$icomprobant=0;
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
	
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$nrofactura = $row[9];
		$descripcion = $row[3]. " Nro Fact:". $nrofactura;
		$monto=$row[4];
		$sucursal=$row[8];
		$documento = 'Caja:'.$id;// "CAJARE".$sucursal.date("dmY",strtotime($fecha));
		$iddepartamentoorigenfactura = $row[6];
		$idcliente = $row[7];
		if($iddepartamentoorigenfactura == 0){//REPUESTOS
			$cuentaCXC = buscarContable(17,$idcliente,$sucursal);	   
		}
		if($iddepartamentoorigenfactura == 1){//SERVICIOS
			$cuentaCXC = buscarContable(18,$idcliente,$sucursal);		   
		}
		if($iddepartamentoorigenfactura == 2){//VEHICULOS
			 $cuentaCXC = buscarContable(19,$idcliente,$sucursal);
		}
		//if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
		//& if(buscarDocTe($documento,$cc,$ct,$dt,$fecha)==0){
			$cuentaCaja =  buscarContable(10,0,$sucursal); 
			//& ingresarRenglon($cuentaCaja,$descripcion. " ".$desorigen,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			//& ingresarRenglon($cuentaCXC,$descripcion. " ".$desorigen,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			/* Para insertar los terceros */
				ingresarEnlacesTerceros_ter($cuentaCXC,$fecha,0,$monto,$idcliente
				,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
			/* Fin Para insertar los terceros */		 
		//& }
	}
								 
	
	// *******************************************************************************************
	// **********************************ENTRADA Notas de Cargo***********************************
	// *******************************************************************************************
	/*
	$SqlStr = "SELECT
		a.idNotaCargo
		,a.fechaRegistroNotaCargo 
		,'02' as centrocosto
		,a.observacionnotacargo as descripcion 
		,a.montoTotalNotaCargo as monto
		,a.numeroNotaCargo as documento
		,a.idCliente
		,a.id_motivo
		,a.id_empresa
	FROM
		".$_SESSION['bdEmpresa'].".cj_cc_notadecargo a
	WHERE
		idDepartamentoOrigenNotaCargo in(0,1)";
		
	if($idTran != 0){
		$SqlStr.=" AND a.idNotaCargo= ".$idTran;
	}else{
		$SqlStr.=" AND a.fechaRegistroNotaCargo between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
	}
	$icomprobant=0;							
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto=$row[4];
		$documento = $row[5];
		$idcliente = $row[6];
		$id_motivo = $row[7];
		$sucursal = $row[8];
		if($iddepartamentoorigenfactura == 0){//repuesto
			$cuentaCXC = buscarContable(17,$idcliente,$sucursal);	   
		}
		if($iddepartamentoorigenfactura == 1){//servicio
			$cuentaCXC = buscarContable(18,$idcliente,$sucursal);		   
		}
		if($iddepartamentoorigenfactura == 2){//vehiculo
			 $cuentaCXC = buscarContable(19,$idcliente,$sucursal);
		}
		
		//$cuentaMov = buscarContable(55,$id_motivo,$sucursal);
		$cuentaCaja = buscarContable(10,0,$sucursal); 
		if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			ingresarRenglon($cuentaCaja  ,$descripcion,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			ingresarRenglon($cuentaCXC,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
		}
	}
	*/
	
	// ********************************************************************************************
	// **********************************ENTRADA ANTICIPOS*****************************************
	// ********************************************************************************************
	// solo para PAGOS CON anticipos
	
	$SqlStr = "SELECT
		c.id_encabezado_rs
		,max(c.fecha_pago) as fecha_pago
		,'05' as centrocosto
		,'ENTRADA R/S DE CAJA ANTICIPOS ' as descripcion
		,SUM(b.montopagado) as monto
		,0 as documento
		,max(a.iddepartamentoorigenfactura)
		,max(a.idcliente)
		,max(a.id_empresa)
		,max(a.numerofactura)
	FROM
		".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a
		,".$_SESSION['bdEmpresa'].".sa_iv_pagos b
		,".$_SESSION['bdEmpresa'].".cj_cc_encabezado_pago_rs c
	WHERE
		b.id_factura = a.idFactura
		AND b.id_encabezado_rs = c.id_encabezado_rs
		AND a.iddepartamentoorigenfactura IN(0,1)
		AND formapago = 7";
	
	if($idTran != 0){
		$SqlStr.=" AND c.id_encabezado_rs = ".$idTran;
	}else{
		$SqlStr.=" AND c.fecha_pago between '".date('Y-m-d',strtotime($Desde))."' AND  '". date('Y-m-d',strtotime($Hasta))."'";
	}
	
	//& $SqlStr.=" GROUP BY c.fecha_pago, a.iddepartamentoorigenfactura, a.id_empresa";
	$SqlStr.=" group by c.id_encabezado_rs";
	$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
	
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		$id = $row[0];
		$fecha = $row[1];
		$nrofactura = $row[9];
		$descripcion = $row[3]. " Nro Fact:". $nrofactura;
		$monto = $row[4];
		$sucursal = $row[8];
		$documento = 'Adel:'.$id;// "CAJARE".$sucursal.date("dmY",strtotime($fecha));
		$iddepartamentoorigenfactura = $row[6];
		$idcliente = $row[7];
		
		
		if($iddepartamentoorigenfactura == 0){//REPUESTOS
			$cuentaCXC = buscarContable(17,$idcliente,$sucursal);
			$cuentaAnticipo = buscarContable(26,47,$sucursal);							  
		}
		if($iddepartamentoorigenfactura == 1){//SERVICIOS
			$cuentaCXC = buscarContable(18,$idcliente,$sucursal);
			$cuentaAnticipo = buscarContable(27,47,$sucursal);								  
		}
		if($iddepartamentoorigenfactura == 2){//VEHICULOS
			$cuentaCXC = buscarContable(19,$idcliente,$sucursal);
			$cuentaAnticipo = buscarContable(25,47,$sucursal);
		}
		//if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){ 
		//& if(buscarDocTe($documento,$cc,$ct,$dt,$fecha)==0){
		//&	ingresarRenglon($cuentaAnticipo,$descripcion. " ".$desorigen,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			/* Para insertar los terceros */
				ingresarEnlacesTerceros_ter($cuentaAnticipo,$fecha,$monto,0,$idcliente
				,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
			/* Fin Para insertar los terceros */
		//&	ingresarRenglon($cuentaCXC,$descripcion. " ".$desorigen,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
		
		/* Para insertar los terceros */
				ingresarEnlacesTerceros_ter($cuentaCXC,$fecha,0,$monto,$idcliente
				,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
			/* Fin Para insertar los terceros */
		//& }
	}
}

//**********************************************************************************
function generarAnticiposRe_Ter($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
	// *******************************************************************************************
	// **********************************Generar anticipos RS*************************************
	// *******************************************************************************************
	$ct='03';
	$dt='07';
	$cc='05';
		
	 $SqlStr = "SELECT
		max(b.idAnticipo)
		,max(b.fechaPagoAnticipo)
		,'05' AS centrocosto
		,(SELECT nombreFormaPago FROM ".$_SESSION['bdEmpresa'].".formapagos WHERE aliasformapago = b.tipoPagoDetalleAnticipo)
		,SUM(b.montoDetalleAnticipo) AS monto
		,numeroAnticipo AS documento
		,a.idDepartamento
		,b.bancoCompaniaDetalleAnticipo
		,b.idcaja
		,(SELECT concat(IFNULL(nombre,''),' ',IFNULL(apellido,'')) FROM ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 WHERE x3.id = a.idcliente) AS descliente
		,a.id_empresa
		,a.idCliente
	FROM
		".$_SESSION['bdEmpresa'].".cj_cc_anticipo a,
		".$_SESSION['bdEmpresa'].".cj_cc_detalleanticipo b
	WHERE
		a.idanticipo = b.idanticipo
		AND a.idDepartamento in(0,1)";
		
	if($idTran != 0){
		$SqlStr.=" AND b.idAnticipo = ".$idTran;
	}else{
		$SqlStr.=" AND b.fechaPagoAnticipo BETWEEN '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
							
	$SqlStr.=" GROUP BY a.fechaAnticipo, a.idcliente";
	$icomprobant = 0;
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
	
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto = $row[4];
		$documento = $row[5] ;
		$iddepartamentoorigenfactura = $row[6];
		$id_numero_cuenta = $row[7];
		$idcaja = $row[8];
		$Descliente = $row[9];
		$sucursal = $row[10];
		$idcliente =$row[11];
		
		if($iddepartamentoorigenfactura == 0){//REPUESTOS
			$cuentaAnticipo = buscarContable(26,47,$sucursal);
		}
		if($iddepartamentoorigenfactura == 1){//SERVICIOS
			$cuentaAnticipo = buscarContable(27,47,$sucursal);
		}
		if($iddepartamentoorigenfactura == 2){//VEHICULOS
			$cuentaAnticipo = buscarContable(25,47,$sucursal);
		}
		
		//& if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			$cuentaCaja = buscarContable(10,0,$sucursal); 
			//& ingresarRenglon($cuentaCaja," Anticipo RS de " .$Descliente,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			//& ingresarRenglon($cuentaAnticipo,"Anticipo RS de " .$Descliente,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			/* Para insertar los terceros */
				ingresarEnlacesTerceros_ter($cuentaAnticipo,$fecha,0,$monto,$idcliente
				,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
			/* Fin Para insertar los terceros */
		//&}
	}
}

//**********************************************************************************
function generarAnticiposVe_Ter($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
	// *******************************************************************************************
	// **********************************Generar anticipos****************************************
	// *******************************************************************************************
	$ct='12';
	$dt='07';
	$cc='05';
	
	$SqlStr = "SELECT
		max(b.idAnticipo)
		,max(b.fechaPagoAnticipo)
		,'05' AS centrocosto
		,(SELECT nombreFormaPago FROM ".$_SESSION['bdEmpresa'].".formapagos WHERE aliasformapago = b.tipoPagoDetalleAnticipo)
		,SUM(b.montoDetalleAnticipo) AS monto
		,numeroAnticipo AS documento
		,a.idDepartamento
		,b.bancoCompaniaDetalleAnticipo
		,b.idcaja
		,(SELECT concat(IFNULL(nombre,''),' ',IFNULL(apellido,'')) FROM ".$_SESSION['bdEmpresa'].".cj_cc_cliente x3 WHERE x3.id = a.idcliente) AS descliente
		,a.id_empresa
		,a.idCliente
	FROM
		".$_SESSION['bdEmpresa'].".cj_cc_anticipo a
		,".$_SESSION['bdEmpresa'].".cj_cc_detalleanticipo b
	WHERE
		a.idanticipo = b.idanticipo
		AND a.idDepartamento = 2";
		
	if($idTran != 0){
		$SqlStr.=" AND b.idAnticipo = ".$idTran;
	}else{
		$SqlStr.=" AND b.fechaPagoAnticipo BETWEEN '".date('Y-m-d',strtotime($Desde))."' AND '". date('Y-m-d',strtotime($Hasta))."'";
	}
	
	$SqlStr.=" GROUP BY a.fechaAnticipo, a.idcliente";
	$icomprobant = 0;
	$exec = EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error());
	
	while ($row = ObtenerFetch($exec)) {
		$icomprobant++;
		
		$id = $row[0];
		$fecha = $row[1];
		$descripcion = $row[3];
		$monto = $row[4];
		$documento = $row[5] ;
		$iddepartamentoorigenfactura = $row[6];
		$id_numero_cuenta = $row[7];
		$idcaja = $row[8];
		$Descliente = $row[9];
		$sucursal = $row[10]; 
		$idcliente = $row[11];
		
		if($iddepartamentoorigenfactura == 0){//REPUESTOS
			$cuentaAnticipo = buscarContable(26,47,$sucursal);
		}
		if($iddepartamentoorigenfactura == 1){//SERVICIOS
			$cuentaAnticipo = buscarContable(27,47,$sucursal);
		}
		if($iddepartamentoorigenfactura == 2){//VEHICULOS
			$cuentaAnticipo = buscarContable(25,47,$sucursal);
		}
		
		//& if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
			$cuentaCaja = buscarContable(10,0,$sucursal); 
			//& ingresarRenglon($cuentaCaja,"Anticipo Vehiculos de " .$Descliente,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
			//& ingresarRenglon($cuentaAnticipo,"Anticipo Vehiculos de " .$Descliente,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
			/* Para insertar los terceros */
				ingresarEnlacesTerceros_ter($cuentaAnticipo,$fecha,0,$monto,$idcliente
				,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
			/* Fin Para insertar los terceros */
		//&}
	}
}

/*F 25*/
//**********************************************************************************
function generarCajasEntradaVe_Ter($idTran=0,$Desde="",$Hasta=""){
$con = ConectarBD();
							// *******************************************************************************************
							// *****************************GENERAR ENTRADAS A CAJAS VEHICULOS****************************
							// *******************************************************************************************
							  $ct='12';
							  $dt='07';
							  $cc='05';
							     $SqlStr = "select c.id_encabezado_v
												,c.fecha_pago
												,'05' as centrocosto
												,'ENTRADA V DE CAJA GENERAL ' as descripcion
												,b.montopagado as monto
												,0 as documento
												,a.iddepartamentoorigenfactura
												,a.idcliente
												,a.numerofactura
												,a.id_empresa
												from ".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a,".$_SESSION['bdEmpresa'].".an_pagos b,
												".$_SESSION['bdEmpresa'].".cj_cc_encabezado_pago_v c
												where b.id_factura = a.idFactura
												and b.id_encabezado_v = c.id_encabezado_v
												and a.iddepartamentoorigenfactura =2
												and (formapago between 1 and 6 or formapago between 9 and 10)
												";
												
							 if($idTran != 0){
									$SqlStr.=" and c.id_encabezado_v= ".$idTran;
									
							}else{
									$SqlStr.=" and c.fecha_pago between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
							}
							
							//$SqlStr.=" group by b.fechapago, a.iddepartamentoorigenfactura";
						$icomprobant=0;					
						
						$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
							
							 while ($row = ObtenerFetch($exec)) {
							  $icomprobant++;
							  $id = $row[0];
							  $fecha = $row[1];
							  $descripcion = $row[3]. " Nro Fact:". $row[8] ;
							  $monto=$row[4];
							  $sucursal=$row[9];
							  $documento = 'Caja:'.$id;
							  $iddepartamentoorigenfactura = $row[6];
							  $idcliente = $row[7];
							  $documento = "Liq".$row[8];
							  
							  if($iddepartamentoorigenfactura == 0){
							  //repuesto
							  $cuentaCXC = buscarContable(17,$idcliente,$sucursal);	   
							  }
							  if($iddepartamentoorigenfactura == 1){
							  //servicio
							  $cuentaCXC = buscarContable(18,$idcliente,$sucursal);		   
							  }
							  if($iddepartamentoorigenfactura == 2){
							  //vehiculo
							  	 $cuentaCXC = buscarContable(19,$idcliente,$sucursal);
							  }
							 //if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){ 
							  $cuentaCaja =  buscarContable(10,0,$sucursal); 
							 // ingresarRenglon($cuentaCaja,$descripcion. " ".$desorigen,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
							  //ingresarRenglon($cuentaCXC,$descripcion. " ".$desorigen,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							  		/* Para insertar los terceros */
											ingresarEnlacesTerceros_ter($cuentaCXC,$fecha,0,$monto,$idcliente
											,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion);
									/* Fin Para insertar los terceros */

							  
							
							  
							// }//if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){ 
							 }
							 
							 
							 // *******************************************************************************************
							// **********************************ENTRADA Notas de Cargo***********************************
							// *******************************************************************************************
							/*
							$SqlStr = "select a.idNotaCargo
										,a.fechaRegistroNotaCargo 
										,'02' as centrocosto
										,a.observacionnotacargo as descripcion 
										,a.montoTotalNotaCargo as monto
										,a.numeroNotaCargo as documento
										,a.idCliente
										,a.id_motivo
										,a.id_empresa
										from ".$_SESSION['bdEmpresa'].".cj_cc_notadecargo a
										where idDepartamentoOrigenNotaCargo =2";
							 if($idTran != 0){
									$SqlStr.=" and a.idNotaCargo= ".$idTran;
							}else{
									$SqlStr.=" and a.fechaRegistroNotaCargo between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
							}
							$icomprobant=0;							
							$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
							 while ($row = ObtenerFetch($exec)) {
									  $icomprobant++;
									  $id = $row[0];
									  $fecha = $row[1];
									  $descripcion = $row[3];
									  $monto=$row[4];
									  $documento = $row[5];
									  $idcliente = $row[6];
									  $id_motivo = $row[7];
									  $sucursal = $row[8];
									    if($iddepartamentoorigenfactura == 0){
										  //repuesto
										  $cuentaCXC = buscarContable(17,$idcliente,$sucursal);	   
										  }
										  if($iddepartamentoorigenfactura == 1){
										  //servicio
										  $cuentaCXC = buscarContable(18,$idcliente,$sucursal);		   
										  }
										  if($iddepartamentoorigenfactura == 2){
										  //vehiculo
											 $cuentaCXC = buscarContable(19,$idcliente,$sucursal);
										  }
									  
									  //$cuentaMov =  buscarContable(55,$id_motivo,$sucursal);
									  $cuentaCaja =  buscarContable(10,0,$sucursal); 
									  if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){
									  ingresarRenglon($cuentaCaja  ,$descripcion,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
									  ingresarRenglon($cuentaCXC,$descripcion,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
								}}
							*/
							 
							// ********************************************************************************************
							// **********************************ENTRADA ANTICIPOS*****************************************
							// ********************************************************************************************
							// solo para  PAGOS CON anticipos
							 
							  $SqlStr = "    select c.id_encabezado_v
												,max(c.fecha_pago) as fecha_pago
												,'05' as centrocosto
												,'ENTRADA VEHICULOS DE CAJA ANTICIPOS ' as descripcion
												,sum(b.montopagado) as monto
												,0 as documento
												,max(a.iddepartamentoorigenfactura)
												,max(a.idcliente)
												,max(a.numerofactura)
												,max(a.id_empresa)
												from ".$_SESSION['bdEmpresa'].".cj_cc_encabezadofactura a,".$_SESSION['bdEmpresa'].".an_pagos b,
												".$_SESSION['bdEmpresa'].".cj_cc_encabezado_pago_v c
												where b.id_factura = a.idFactura
												and b.id_encabezado_v = c.id_encabezado_v
												and a.iddepartamentoorigenfactura =2
												and formapago=7
												";
												
							 if($idTran != 0){
									$SqlStr.=" and c.id_encabezado_v= ".$idTran;
							}else{
									$SqlStr.=" and c.fecha_pago between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."'";
							}
							        $SqlStr.=" group by c.id_encabezado_v";
							//$SqlStr.=" ";
										
									
						$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr." " .mysql_error()); 
							
							 while ($row = ObtenerFetch($exec)) {
							  $icomprobant++;
							  $id = $row[0];
							  $fecha = $row[1];
							  $descripcion = $row[3]. " Nro Fact:". $row[8] ;
							  $monto=$row[4];
							  $sucursal=$row[9];   
							  $documento = 'Adel:'.$id;//"CAJAVE".$sucursal.date("dmY",strtotime($fecha));
							  $iddepartamentoorigenfactura = $row[6];
							  $idcliente = $row[7];
							  $documento = $row[8];
							  
							  if($iddepartamentoorigenfactura == 0){
							  //repuesto
							  $cuentaCXC = buscarContable(17,$idcliente,$sucursal);
							  $cuentaAnticipo = buscarContable(25,47,$sucursal);							  
							  }
							  if($iddepartamentoorigenfactura == 1){
							  //servicio
							    $cuentaCXC = buscarContable(18,$idcliente,$sucursal);
								$cuentaAnticipo = buscarContable(27,47,$sucursal);								  
							  }
							  if($iddepartamentoorigenfactura == 2){
							  //vehiculo
							  	 $cuentaCXC = buscarContable(19,$idcliente,$sucursal);
								 $cuentaAnticipo = buscarContable(25,47,$sucursal);
							  }
							//if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){ 
							  //ingresarRenglon($cuentaAnticipo,$descripcion. " ".$desorigen,$monto,0,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'D',$cc,$id);
							  
						  	  		/* Para insertar los terceros */
											ingresarEnlacesTerceros_ter($cuentaAnticipo,$fecha,$monto,0,$idcliente
											,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion. " ".$desorigen );
									/* Fin Para insertar los terceros */
							  
							  //ingresarRenglon($cuentaCXC,$descripcion. " ".$desorigen,0,$monto,$documento,$ct,$dt,$cc,$icomprobant,$fecha,'H',$cc,$id);
							  
							  	  		/* Para insertar los terceros */
											ingresarEnlacesTerceros_ter($cuentaCXC,$fecha,0,$monto,$idcliente
											,"cj_cc_cliente","id|concat(nombre,' ',apellido)",$descripcion. " ".$desorigen);
									/* Fin Para insertar los terceros */
							 //} // if(buscarDoc($id,$cc,$ct,$dt,$fecha)==0){ 
							}
			

}

function generarNotasVentasSe_Ter($idNota,$Desde="",$Hasta=""){
		$con = ConectarBD();
		if($idNota != 0){
				$SqlStr = "select idNotaCredito,numeracion_nota_credito,fechaNotaCredito,idDocumento  
					from ".$_SESSION['bdEmpresa'].".cj_cc_notacredito where idNotaCredito = $idNota and tipodocumento = 'FA' and iddepartamentonotacredito=1";
        }else{
				$SqlStr = "select idNotaCredito,numeracion_nota_credito,fechaNotaCredito,idDocumento  
					from ".$_SESSION['bdEmpresa'].".cj_cc_notacredito where fechaNotaCredito between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."' and tipodocumento = 'FA' and iddepartamentonotacredito=1";
        }		
		$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
		 while ($row = ObtenerFetch($exec)) {
				 $idNotaCredito = $row[0]; 
				 $numeracion_nota_credito= $row[1];
				 $fechaNotaCredito= $row[2];
				 $idDocumento= $row[3];  
				 generarVentasSe_Ter($idDocumento,$xFechaD,$xFechaH,$idNotaCredito);
		 }
}

//**************************NOTAS DE CREDITO VEHICULOS******************************
function generarNotasVentasVe_Ter($idNota,$Desde="",$Hasta=""){
		$con = ConectarBD();
		if($idNota != 0){
				$SqlStr = "select idNotaCredito,numeracion_nota_credito,fechaNotaCredito,idDocumento  
					from ".$_SESSION['bdEmpresa'].".cj_cc_notacredito where idNotaCredito = $idNota and tipodocumento = 'FA' and iddepartamentonotacredito=2";
        }else{
				$SqlStr = "select idNotaCredito,numeracion_nota_credito,fechaNotaCredito,idDocumento  
					from ".$_SESSION['bdEmpresa'].".cj_cc_notacredito where fechaNotaCredito between '".date('Y-m-d',strtotime($Desde))."' and  '". date('Y-m-d',strtotime($Hasta))."' and tipodocumento = 'FA' and iddepartamentonotacredito=2";
        }
		$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr."<br>Line: ".__LINE__); 
		 while ($row = ObtenerFetch($exec)) {
				 $idNotaCredito = $row[0]; 
				 $numeracion_nota_credito= $row[1];
				 $fechaNotaCredito= $row[2];
				 $idDocumento= $row[3];  
				 generarVentasVe_Ter($idDocumento,$xFechaD,$xFechaH,$idNotaCredito);
		 }
}

?>