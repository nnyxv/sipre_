<?php
set_time_limit(0);

$ruta = explode("↓",str_replace(array("/","\\"),"↓",getcwd()));
$ruta = array_reverse($ruta);
$raizPpal = false;
foreach ($ruta as $indice => $valor) {
	$valor2 = explode("_",$valor);
	if ($valor2[0] != "sipre" && $raizPpal == false) {
		$raiz .= "../";
		break;
	} else if ($valor2[0] == "sipre") {
		$raizPpal = true;
	}
}

class Documento {
	public $raizDir;
	public $tipoMovimiento;
	public $tipoDocumento;
	public $tipoDocumentoMovimiento;
	public $idModulo;
	public $idDocumento;
	public $idEmpresa;
	public $idCliente;
	public $mostrarDocumento;
	public $mostrarDocumentoRecibo;
	
	private $idTpDcto;
	
	function verDocumento() {
		if (in_array($this->tipoMovimiento,array(1))) { // 1 = COMPRA
			if (in_array($this->tipoDocumento,array(1,"FA"))) { // 1 = Factura
				$rutaDetalle = sprintf("cxp/cp_factura_form.php?id=%s&vw=v", $this->idDocumento);
				$aVerDetalle = (strlen($rutaDetalle) > 0) ? "<a id=\"aVerDcto\" href=\"".$this->raizDir.$rutaDetalle."\" target=\"_blank\"><img src=\"".$this->raizDir."img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Registro Compra")."\"/></a>" : "";
				
				switch ($this->idModulo) {
					case 0 : $rutaPDF = sprintf("repuestos/reportes/iv_registro_compra_pdf.php?valBusq=%s", $this->idDocumento); break;
					case 1 : $rutaPDF = sprintf("servicios/reportes/sa_imprimir_registro_compra_pdf.php?valBusq=%s", $this->idDocumento); break;
					case 2 : $rutaPDF = sprintf("vehiculos/reportes/an_registro_compra_pdf.php?valBusq=%s", $this->idDocumento); break;
					case 3 : $rutaPDF = sprintf("compras/reportes/ga_registro_compra_pdf.php?valBusq=%s", $this->idDocumento); break;
				}
				$aVerPDF = (strlen($rutaPDF) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$this->raizDir.$rutaPDF."', 960, 550);\"><img src=\"".$this->raizDir."img/iconos/page_white_acrobat.png\" title=\"".("Registro Compra PDF")."\"/></a>" : "";
				
			} else if (in_array($this->tipoDocumento,array(2,"ND"))) { // 2 = Nota de Débito
				$rutaDetalle = sprintf("cxp/cp_nota_cargo_form.php?id=%s&vw=v", $this->idDocumento);
				$aVerDetalle = (strlen($rutaDetalle) > 0) ? "<a id=\"aVerDcto\" href=\"".$this->raizDir.$rutaDetalle."\" target=\"_blank\"><img src=\"".$this->raizDir."img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota de Débito")."\"/></a>" : "";
				
				$rutaPDF = sprintf("cxp/reportes/cp_nota_cargo_pdf.php?valBusq=%s", $this->idDocumento);
				$aVerPDF = (strlen($rutaPDF) > 0) ? sprintf("<a id=\"aVerDcto\" href=\"javascript:verVentana('".$this->raizDir.$rutaPDF."', 960, 550);\"><img src=\"".$this->raizDir."img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Débito PDF")."\"/></a>") : "";
			}
			
		} else if (in_array($this->tipoMovimiento,array(2))) { // 2 = ENTRADA
			switch ($this->tipoDocumentoMovimiento) {
				case 1 : // VALE ENTRADA
					switch ($this->idModulo) {
						case 0 : $rutaPDF = sprintf("repuestos/reportes/iv_ajuste_inventario_pdf.php?valBusq=%s|2", $this->idDocumento); break;
						case 1 : $rutaPDF = sprintf("servicios/sa_devolucion_vale_salida_pdf.php?valBusq=1|%s", $this->idDocumento); break;
						case 2 : $rutaPDF = sprintf("vehiculos/reportes/an_ajuste_inventario_vale_entrada_imp.php?id=%s", $this->idDocumento); break;
						default : $rutaPDF = "";
					}
					$aVerPDF = (strlen($rutaPDF) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$this->raizDir.$rutaPDF."', 960, 550);\"><img src=\"".$this->raizDir."img/iconos/page_white_acrobat.png\" title=\"".("Vale Entrada PDF")."\"/></a>" : "";
					break;
				case 2 : // NOTA DE CREDITO
					$rutaDetalle = sprintf("cxc/cc_nota_credito_form.php?id=%s&acc=0", $this->idDocumento);
					$aVerDetalle = (strlen($rutaDetalle) > 0) ? "<a id=\"aVerDcto\" href=\"".$this->raizDir.$rutaDetalle."\" target=\"_blank\"><img src=\"".$this->raizDir."img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota de Crédito")."\"/></a>" : "";
					
					switch ($this->idModulo) {
						case 0 : $rutaPDF = sprintf("repuestos/reportes/iv_devolucion_venta_pdf.php?valBusq=%s", $this->idDocumento); break;
						case 1 : $rutaPDF = sprintf("servicios/reportes/sa_devolucion_venta_pdf.php?valBusq=%s", $this->idDocumento); break;
						case 2 : $rutaPDF = sprintf("vehiculos/reportes/an_devolucion_venta_pdf.php?valBusq=%s", $this->idDocumento); break;
						case 3 : $rutaPDF = sprintf("repuestos/reportes/ga_devolucion_venta_pdf.php?valBusq=%s", $this->idDocumento); break;
						case 4 : $rutaPDF = sprintf("alquiler/reportes/al_devolucion_venta_pdf.php?valBusq=%s", $this->idDocumento); break;
						case 5 : $rutaPDF = sprintf("repuestos/reportes/ga_devolucion_venta_pdf.php?valBusq=%s", $this->idDocumento); break;
						default : $rutaPDF = "";
					}
					$aVerPDF = (strlen($rutaPDF) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$this->raizDir.$rutaPDF."', 960, 550);\"><img src=\"".$this->raizDir."img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Crédito PDF")."\"/></a>" : "";
					break;
				default : $aVerDcto = "";
			}
			
		} else if (in_array($this->tipoMovimiento,array(3))) { // 3 = VENTA
			if (in_array($this->tipoDocumento,array(1,"FA"))) { // 1 = Factura
				$rutaDetalle = sprintf("cxc/cc_factura_form.php?id=%s&acc=0", $this->idDocumento);
				$aVerDetalle = (strlen($rutaDetalle) > 0) ? "<a id=\"aVerDcto\" href=\"".$this->raizDir.$rutaDetalle."\" target=\"_blank\"><img src=\"".$this->raizDir."img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Factura Venta")."\"/></a>" : "";
				
				switch ($this->idModulo) {
					case 0 : $rutaPDF = sprintf("repuestos/reportes/iv_factura_venta_pdf.php?valBusq=%s", $this->idDocumento); break;
					case 1 : $rutaPDF = sprintf("servicios/reportes/sa_factura_venta_pdf.php?valBusq=%s", $this->idDocumento); break;
					case 2 : $rutaPDF = sprintf("vehiculos/reportes/an_factura_venta_pdf.php?valBusq=%s", $this->idDocumento); break;
					case 3 : $rutaPDF = sprintf("repuestos/reportes/ga_factura_venta_pdf.php?valBusq=%s", $this->idDocumento); break;
					case 4 : $rutaPDF = sprintf("alquiler/reportes/al_factura_venta_pdf.php?valBusq=%s", $this->idDocumento); break;
					case 5 : $rutaPDF = sprintf("repuestos/reportes/ga_factura_venta_pdf.php?valBusq=%s", $this->idDocumento); break;
					default : $rutaPDF = "";
				}
				$aVerPDF = (strlen($rutaPDF) > 0) ? "<a href=\"javascript:verVentana('".$this->raizDir.$rutaPDF."', 960, 550);\"><img src=\"".$this->raizDir."img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Factura Venta PDF")."\"/></a>" : "";
				
				$this->idTpDcto = 1;
				$aVerReciboPDF = $this->verRecibo();
				
			} else if (in_array($this->tipoDocumento,array(2,"ND"))) { // 2 = Nota de Débito
				$rutaDetalle = sprintf("cxc/cc_nota_debito_form.php?id=%s&acc=0", $this->idDocumento);
				$aVerDetalle = (strlen($rutaDetalle) > 0) ? "<a id=\"aVerDcto\" href=\"".$this->raizDir.$rutaDetalle."\" target=\"_blank\"><img src=\"".$this->raizDir."img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota de Débito")."\"/></a>" : "";
				
				$aVerPDF = sprintf("<a href=\"javascript:verVentana('".$this->raizDir."cxc/reportes/cc_nota_cargo_pdf.php?valBusq=%s', 960, 550);\"><img src=\"".$this->raizDir."img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Débito PDF")."\"/></a>",
					$this->idDocumento);
				
				$this->idTpDcto = 2;
				$aVerReciboPDF = $this->verRecibo();
				
			} else if (in_array($this->tipoDocumento,array(3,"NC"))) { // 3 = Nota de Crédito
				$rutaDetalle = sprintf("cxc/cc_nota_credito_form.php?id=%s&acc=0", $this->idDocumento);
				$aVerDetalle = (strlen($rutaDetalle) > 0) ? "<a id=\"aVerDcto\" href=\"".$this->raizDir.$rutaDetalle."\" target=\"_blank\"><img src=\"".$this->raizDir."img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota de Crédito")."\"/></a>" : "";
				
				switch ($this->idModulo) {
					case 0 : $rutaPDF = sprintf("repuestos/reportes/iv_devolucion_venta_pdf.php?valBusq=%s", $this->idDocumento); break;
					case 1 : $rutaPDF = sprintf("servicios/reportes/sa_devolucion_venta_pdf.php?valBusq=%s", $this->idDocumento); break;
					case 2 : $rutaPDF = sprintf("vehiculos/reportes/an_devolucion_venta_pdf.php?valBusq=%s", $this->idDocumento); break;
					case 3 : $rutaPDF = sprintf("repuestos/reportes/ga_devolucion_venta_pdf.php?valBusq=%s", $this->idDocumento); break;
					case 4 : $rutaPDF = sprintf("alquiler/reportes/al_devolucion_venta_pdf.php?valBusq=%s", $this->idDocumento); break;
					case 5 : $rutaPDF = sprintf("repuestos/reportes/ga_devolucion_venta_pdf.php?valBusq=%s", $this->idDocumento); break;
					default : $rutaPDF = "";
				}
				$aVerPDF = (strlen($rutaPDF) > 0) ? "<a href=\"javascript:verVentana('".$this->raizDir.$rutaPDF."', 960, 550);\"><img src=\"".$this->raizDir."img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Crédito PDF")."\"/></a>" : "";
				
			} else if (in_array($this->tipoDocumento,array(4,"AN"))) { // 4 = Anticipo
				$rutaDetalle = sprintf("cxc/cc_anticipo_form.php?id=%s&acc=0", $this->idDocumento);
				$aVerDetalle = (strlen($rutaDetalle) > 0) ? "<a id=\"aVerDcto\" href=\"".$this->raizDir.$rutaDetalle."\" target=\"_blank\"><img src=\"".$this->raizDir."img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Anticipo")."\"/></a>" : "";
				
				$this->mostrarDocumentoRecibo = "verRutaPDF";
				$this->idTpDcto = 4;
				$rutaPDF = str_replace("../", "", $this->verRecibo());
				$aVerPDF = (strlen($rutaPDF) > 0) ? "<a href=\"javascript:verVentana('".$this->raizDir.$rutaPDF."', 960, 550);\"><img src=\"".$this->raizDir."img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Anticipo PDF")."\"/></a>" : "";
				
				$this->mostrarDocumentoRecibo = "";
				$this->idTpDcto = 4;
				$aVerReciboPDF = $this->verRecibo();
				
			} else if (in_array($this->tipoDocumento,array(5,"CH"))) { // 5 = Cheque
				$rutaDetalle = sprintf("cxc/cc_cheque_form.php?id=%s&acc=0", $this->idDocumento);
				$aVerDetalle = (strlen($rutaDetalle) > 0) ? "<a id=\"aVerDcto\" href=\"".$this->raizDir.$rutaDetalle."\" target=\"_blank\"><img src=\"".$this->raizDir."img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Cheque")."\"/></a>" : "";
				
				$this->mostrarDocumentoRecibo = "verRutaPDF";
				$this->idTpDcto = 5;
				$rutaPDF = str_replace("../", "", $this->verRecibo());
				$aVerPDF = (strlen($rutaPDF) > 0) ? "<a href=\"javascript:verVentana('".$this->raizDir.$rutaPDF."', 960, 550);\"><img src=\"".$this->raizDir."img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Cheque PDF")."\"/></a>" : "";
				
				$this->mostrarDocumentoRecibo = "";
				$this->idTpDcto = 5;
				$aVerReciboPDF = $this->verRecibo();
				
			} else if (in_array($this->tipoDocumento,array(6,"TB"))) { // 6 = Transferencia
				$rutaDetalle = sprintf("cxc/cc_transferencia_form.php?id=%s&acc=0", $this->idDocumento);
				$aVerDetalle = (strlen($rutaDetalle) > 0) ? "<a id=\"aVerDcto\" href=\"".$this->raizDir.$rutaDetalle."\" target=\"_blank\"><img src=\"".$this->raizDir."img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Transferencia")."\"/></a>" : "";
				
				$this->mostrarDocumentoRecibo = "verRutaPDF";
				$this->idTpDcto = 6;
				$rutaPDF = str_replace("../", "", $this->verRecibo());
				$aVerPDF = (strlen($rutaPDF) > 0) ? "<a href=\"javascript:verVentana('".$this->raizDir.$rutaPDF."', 960, 550);\"><img src=\"".$this->raizDir."img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Transferencia PDF")."\"/></a>" : "";
				
				$this->mostrarDocumentoRecibo = "";
				$this->idTpDcto = 6;
				$aVerReciboPDF = $this->verRecibo();
			}
			
		} else if (in_array($this->tipoMovimiento,array(4))) { // 4 = SALIDA
			switch ($this->tipoDocumentoMovimiento) {
				case 1 : // VALE SALIDA
					switch ($this->idModulo) {
						case 0 : $rutaPDF = sprintf("repuestos/reportes/iv_ajuste_inventario_pdf.php?valBusq=%s|4", $this->idDocumento); break;
						case 1 : $rutaPDF = sprintf("servicios/reportes/sa_imprimir_presupuesto_pdf.php?valBusq=%s|2|3", $this->idDocumento); break;
						case 2 : $rutaPDF = sprintf("vehiculos/reportes/an_ajuste_inventario_vale_salida_imp.php?id=%s", $this->idDocumento); break;
						default : $rutaPDF = "";
					}
					$aVerPDF = (strlen($rutaPDF) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$this->raizDir.$rutaPDF."', 960, 550);\"><img src=\"".$this->raizDir."img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Vale Salida PDF")."\"/></a>" : "";
					break;
				case 2 : // NOTA DE CREDITO
					$rutaDetalle = sprintf("cxp/cp_nota_credito_form.php?id=%s&vw=v", $this->idDocumento);
					$aVerDetalle = (strlen($rutaDetalle) > 0) ? "<a id=\"aVerDcto\" href=\"".$this->raizDir.$rutaDetalle."\" target=\"_blank\"><img src=\"".$this->raizDir."img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota de Crédito")."\"/></a>" : "";
					
					$rutaPDF = sprintf("cxp/reportes/cp_nota_credito_pdf.php?valBusq=%s", $this->idDocumento);
					$aVerPDF = (strlen($rutaPDF) > 0) ? sprintf("<a id=\"aVerDcto\" href=\"javascript:verVentana('".$this->raizDir.$rutaPDF."', 960, 550);\"><img src=\"".$this->raizDir."img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Crédito PDF")."\"/></a>") : "";
					break;
				default : $aVerDcto = "";
			}
		}
		
		if ($this->idDocumento > 0) {
			if ($this->mostrarDocumento == "verDetalle") {
				return $aVerDetalle;
			} else if ($this->mostrarDocumento == "verRutaDetalle") {
				return $this->raizDir.$rutaDetalle;
			} else if ($this->mostrarDocumento == "verPDF") {
				return $aVerPDF;
			} else if ($this->mostrarDocumento == "verRutaPDF") {
				return $this->raizDir.$rutaPDF;
			} else if ($this->mostrarDocumento == "verVentanaPDF") {
				return "verVentana('".$this->raizDir.$rutaPDF."', 960, 550);";
			} else if ($this->mostrarDocumento == "verReciboPDF") {
				return $aVerReciboPDF;
			} else {
				return $aVerDetalle.$aVerPDF;
			}
		}
	}
	
	function verPedido() {
		if (in_array($this->tipoMovimiento,array(1))) { // 1 = COMPRA
		} else if (in_array($this->tipoMovimiento,array(3))) { // 3 = VENTA
			if (in_array($this->tipoDocumento,array(8,"PR"))) { // 8 = Presupuesto
			} else if (in_array($this->tipoDocumento,array(9,"PD"))) { // 9 = Pedido
				switch ($this->idModulo) {
					case 0 : $rutaPDF = sprintf("repuestos/reportes/iv_pedido_venta_pdf.php?valBusq=%s", $this->idDocumento); break;
					case 1 : $rutaPDF = sprintf("servicios/reportes/sa_imprimir_presupuesto_pdf.php?valBusq=%s|2|0", $this->idDocumento); break;
					case 2 : $rutaPDF = sprintf("vehiculos/an_ventas_pedido_editar.php?view=view&id=%s", $this->idDocumento); break;
					case 3 : $rutaPDF = sprintf("cxc/reportes/cc_pedido_venta_pdf.php?valBusq=%s", $this->idDocumento); break;
					case 4 : $rutaPDF = sprintf("alquiler/reportes/al_contrato_venta_pdf.php?valBusq=%s", $this->idDocumento); break;
					case 5 : $rutaPDF = sprintf("cxc/reportes/cc_pedido_venta_pdf.php?valBusq=%s", $this->idDocumento); break;
					default : $rutaPDF = "";
				}
				$aVerPDF = (strlen($rutaPDF) > 0) ? "<a href=\"javascript:verVentana('".$this->raizDir.$rutaPDF."', 960, 550);\"><img src=\"".$this->raizDir."img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Pedido Venta PDF")."\"/></a>" : "";
			} else  if (in_array($this->tipoDocumento,array(10,"OD"))) { // 10 = Orden
				switch ($this->idModulo) {
					case 1 : $rutaPDF = sprintf("servicios/reportes/sa_imprimir_presupuesto_pdf.php?valBusq=%s|2|0", $this->idDocumento); break;
					default : $rutaPDF = "";
				}
				$aVerPDF = (strlen($rutaPDF) > 0) ? "<a href=\"javascript:verVentana('".$this->raizDir.$rutaPDF."', 960, 550);\"><img src=\"".$this->raizDir."img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Orden Venta PDF")."\"/></a>" : "";
			} else  if (in_array($this->tipoDocumento,array(11,"VL"))) { // 11 = Vale
			}
		}
		
		if ($this->idDocumento > 0) {
			if ($this->mostrarDocumento == "verPDF") {
				return $aVerPDF;
			} else if ($this->mostrarDocumento == "verRutaPDF") {
				return $this->raizDir.$rutaPDF;
			} else if ($this->mostrarDocumento == "verVentanaPDF") {
				return "verVentana('".$this->raizDir.$rutaPDF."', 960, 550);";
			}
		}
	}
	
	function verRecibo() {
		// 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Segun Tabla tipodedocumentos)
		if (in_array($this->tipoDocumento,array(1,2,"FA","ND"))) {
			if (in_array($this->idModulo,array(2,4,5))) {
				if ($this->idTpDcto > 0) {
					$aVerDctoAux = sprintf("caja_vh/reportes/cjvh_recibo_pago_pdf.php?idTpDcto=%s&id=%s", $this->idTpDcto, $this->idDocumento);
				} else {
					$aVerDctoAux = sprintf("caja_vh/reportes/cjvh_recibo_pago_pdf.php?idRecibo=%s", $this->idDocumento);
				}
			} else if (in_array($this->idModulo,array(0,1,3))) {
				if ($this->idTpDcto > 0) {
					$aVerDctoAux = sprintf("caja_rs/reportes/cjrs_recibo_pago_pdf.php?idTpDcto=%s&id=%s", $this->idTpDcto, $this->idDocumento);
				} else {
					$aVerDctoAux = sprintf("caja_rs/reportes/cjrs_recibo_pago_pdf.php?idRecibo=%s", $this->idDocumento);
				}
			}
			$aVerReciboPDF = (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$this->raizDir.$aVerDctoAux."', 960, 550);\"><img src=\"".$this->raizDir."img/iconos/print.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
			
		} else if (in_array($this->tipoDocumento,array(4,5,6,"AN","CH","TB"))) {
			if (in_array($this->idModulo,array(2,4,5))) {
				if ($this->idTpDcto > 0) {
					$aVerDctoAux = sprintf("caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=%s&id=%s", $this->idTpDcto, $this->idDocumento);
				} else {
					$aVerDctoAux = sprintf("caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idRecibo=%s", $this->idDocumento);
				}
			} else if (in_array($this->idModulo,array(0,1,3))) {
				if ($this->idTpDcto > 0) {
					$aVerDctoAux = sprintf("caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=%s&id=%s", $this->idTpDcto, $this->idDocumento);
				} else {
					$aVerDctoAux = sprintf("caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idRecibo=%s", $this->idDocumento);
				}
			}
			$aVerReciboPDF = (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$this->raizDir.$aVerDctoAux."', 960, 550);\"><img src=\"".$this->raizDir."img/iconos/print.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
		}
		
		if ($this->idDocumento > 0) {
			if ($this->mostrarDocumentoRecibo == "verRutaPDF") {
				return $this->raizDir.$aVerDctoAux;
			} else {
				return $aVerReciboPDF;
			}
		}
	}
	
	function actualizarFactura($idFactura) {
		
		// ACTUALIZA EL SALDO DE LA FACTURA
		$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
			saldoFactura = IFNULL(cxc_fact.subtotalFactura, 0)
								- IFNULL(cxc_fact.descuentoFactura, 0)
								+ IFNULL((SELECT SUM(cxc_fact_gasto.monto) FROM cj_cc_factura_gasto cxc_fact_gasto
										WHERE cxc_fact_gasto.id_factura = cxc_fact.idFactura), 0)
								+ IFNULL(IF(cxc_fact.idDepartamentoOrigenFactura = 1
										AND (SELECT COUNT(cxc_fact_impuesto.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_impuesto
											WHERE cxc_fact_impuesto.id_factura = cxc_fact.idFactura) = 0,
												(cxc_fact.calculoIvaFactura + cxc_fact.calculoIvaDeLujoFactura),
												(SELECT SUM(cxc_fact_impuesto.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_impuesto
												WHERE cxc_fact_impuesto.id_factura = cxc_fact.idFactura)), 0)
		WHERE idFactura = %s;",
			valTpDato($idFactura, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ACTUALIZA EL SALDO DE LA FACTURA DEPENDIENDO DE SUS PAGOS
		$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
			saldoFactura = IFNULL(saldoFactura, 0)
								- (IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
											WHERE cxc_pago.id_factura = cxc_fact.idFactura
												AND cxc_pago.estatus IN (1)), 0)
									+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
											WHERE cxc_pago.id_factura = cxc_fact.idFactura
												AND cxc_pago.estatus IN (1)), 0))
		WHERE idFactura = %s;",
			valTpDato($idFactura, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ACTUALIZA EL ESTATUS DE LA FACTURA (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
		$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
			estadoFactura = (CASE
								WHEN (ROUND(saldoFactura, 2) <= 0) THEN
									1
								WHEN (ROUND(saldoFactura, 2) > 0 AND ROUND(saldoFactura, 2) < ROUND(montoTotalFactura, 2)) THEN
									2
								ELSE
									0
							END),
			fecha_pagada = (CASE
								WHEN (ROUND(cxc_fact.saldoFactura, 2) <= 0 AND cxc_fact.fecha_pagada IS NULL) THEN
									(CASE
										WHEN (cxc_fact.idDepartamentoOrigenFactura IN (2,4,5)) THEN
											CONCAT(
												(SELECT MAX(cxc_pago.fechaPago) FROM an_pagos cxc_pago
												WHERE cxc_pago.id_factura = cxc_fact.idFactura),
												' ',
												DATE_FORMAT(NOW(), %s))
										WHEN (cxc_fact.idDepartamentoOrigenFactura IN (0,1,3)) THEN
											CONCAT(
												(SELECT MAX(cxc_pago.fechaPago) FROM sa_iv_pagos cxc_pago
												WHERE cxc_pago.id_factura = cxc_fact.idFactura),
												' ',
												DATE_FORMAT(NOW(), %s))
									END)
								WHEN (ROUND(cxc_fact.saldoFactura, 2) <= 0 AND cxc_fact.fecha_pagada IS NOT NULL) THEN
									cxc_fact.fecha_pagada
							END)
		WHERE idFactura = %s;",
			valTpDato("%H:%i:%s", "date"),
			valTpDato("%H:%i:%s", "date"),
			valTpDato($idFactura, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// VERIFICA EL SALDO DE LA FACTURA A VER SI ESTA NEGATIVO
		$querySaldoDcto = sprintf("SELECT cxc_fact.*,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			
			IFNULL((SELECT COUNT(q.id_factura)
					FROM (SELECT cxc_pago.idPago, cxc_pago.id_factura, cxc_pago.montoPagado FROM an_pagos cxc_pago
						WHERE cxc_pago.estatus IN (2)
						
						UNION
						
						SELECT cxc_pago.idPago, cxc_pago.id_factura, cxc_pago.montoPagado FROM sa_iv_pagos cxc_pago
						WHERE cxc_pago.estatus IN (2)) AS q
					WHERE q.id_factura = cxc_fact.idFactura),0) AS cant_pagos_pendientes,
			
			IFNULL((SELECT SUM(q.montoPagado)
					FROM (SELECT cxc_pago.idPago, cxc_pago.id_factura, cxc_pago.montoPagado FROM an_pagos cxc_pago
						WHERE cxc_pago.estatus IN (2)
						
						UNION
						
						SELECT cxc_pago.idPago, cxc_pago.id_factura, cxc_pago.montoPagado FROM sa_iv_pagos cxc_pago
						WHERE cxc_pago.estatus IN (2)) AS q
					WHERE q.id_factura = cxc_fact.idFactura),0) AS monto_pagos_pendientes
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
		WHERE cxc_fact.idFactura = %s
			AND (cxc_fact.saldoFactura < 0
				OR (cxc_fact.saldoFactura < (SELECT SUM(q.montoPagado)
												FROM (SELECT cxc_pago.idPago, cxc_pago.id_factura, cxc_pago.montoPagado FROM an_pagos cxc_pago
													WHERE cxc_pago.estatus IN (2)
													
													UNION
													
													SELECT cxc_pago.idPago, cxc_pago.id_factura, cxc_pago.montoPagado FROM sa_iv_pagos cxc_pago
													WHERE cxc_pago.estatus IN (2)) AS q
												WHERE q.id_factura = cxc_fact.idFactura)));",
			valTpDato($idFactura, "int"));
		$rsSaldoDcto = mysql_query($querySaldoDcto);
		if (!$rsSaldoDcto) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
		$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
		$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
		if ($totalRowsSaldoDcto > 0) {
			if ($rowSaldoDcto['saldoFactura'] < 0) {
				return array(false, "La Factura Nro. ".$rowSaldoDcto['numeroFactura']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo");
			} else if ($rowSaldoDcto['cant_pagos_pendientes'] > 0) {
				return array(false, "La Factura Nro. ".$rowSaldoDcto['numeroFactura']." del Cliente ".$rowSaldoDcto['nombre_cliente']." no puede ser pagada en su totalidad debido a que posee ".$rowSaldoDcto['cant_pagos_pendientes']." pagos pendientes por un total de ".number_format($rowSaldoDcto['monto_pagos_pendientes'], 2, ".", ",").". Por favor termine de registrar o anular dichos pagos.");
			}
		}
		
		return array(
			0 => true,
			"idFactura" => $idFactura);
	}
	
	function actualizarNotaDebito($idNotaDebito) {
		
		// ACTUALIZA EL SALDO DE LA NOTA DE DEBITO
		$updateSQL = sprintf("UPDATE cj_cc_notadecargo cxc_nd SET
			saldoNotaCargo = montoTotalNotaCargo
		WHERE idNotaCargo = %s;",
			valTpDato($idNotaDebito, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ACTUALIZA EL SALDO DE LA NOTA DE DEBITO DEPENDIENDO DE SUS PAGOS
		$updateSQL = sprintf("UPDATE cj_cc_notadecargo cxc_nd SET
			saldoNotaCargo = saldoNotaCargo - IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
														WHERE cxc_pago.idNotaCargo = cxc_nd.idNotaCargo
															AND cxc_pago.estatus IN (1)), 0)
		WHERE idNotaCargo = %s;",
			valTpDato($idNotaDebito, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ACTUALIZA EL ESTATUS DE LA NOTA DE DEBITO (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
		$updateSQL = sprintf("UPDATE cj_cc_notadecargo cxc_nd SET
			estadoNotaCargo = (CASE
								WHEN (ROUND(saldoNotaCargo, 2) <= 0) THEN
									1
								WHEN (ROUND(saldoNotaCargo, 2) > 0 AND ROUND(saldoNotaCargo, 2) < ROUND(montoTotalNotaCargo, 2)) THEN
									2
								ELSE
									0
							END),
			fecha_pagada = (CASE
								WHEN (ROUND(cxc_nd.saldoNotaCargo, 2) <= 0 AND cxc_nd.fecha_pagada IS NULL) THEN
									CONCAT(
										(SELECT MAX(cxc_pago.fechaPago) FROM cj_det_nota_cargo cxc_pago
										WHERE cxc_pago.idNotaCargo = cxc_nd.idNotaCargo),
										' ',
										DATE_FORMAT(NOW(), %s))
								WHEN (ROUND(cxc_nd.saldoNotaCargo, 2) <= 0 AND cxc_nd.fecha_pagada IS NOT NULL) THEN
									cxc_nd.fecha_pagada
							END)
		WHERE idNotaCargo = %s;",
			valTpDato("%H:%i:%s", "date"),
			valTpDato($idNotaDebito, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// VERIFICA EL SALDO DE LA NOTA DE DEBITO A VER SI ESTA NEGATIVO
		$querySaldoDcto = sprintf("SELECT cxc_nd.*,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			
			IFNULL((SELECT COUNT(q.idNotaCargo)
					FROM (SELECT cxc_pago.id_det_nota_cargo, cxc_pago.idNotaCargo FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.estatus IN (2)) AS q
					WHERE q.idNotaCargo = cxc_nd.idNotaCargo),0) AS cant_pagos_pendientes,
			
			IFNULL((SELECT SUM(q.monto_pago)
					FROM (SELECT cxc_pago.id_det_nota_cargo, cxc_pago.idNotaCargo, cxc_pago.monto_pago FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.estatus IN (2)) AS q
					WHERE q.idNotaCargo = cxc_nd.idNotaCargo),0) AS monto_pagos_pendientes
		FROM cj_cc_notadecargo cxc_nd
			INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
		WHERE cxc_nd.idNotaCargo = %s
			AND (cxc_nd.saldoNotaCargo < 0
				OR (cxc_nd.saldoNotaCargo < (SELECT SUM(q.monto_pago)
												FROM (SELECT cxc_pago.id_det_nota_cargo, cxc_pago.idNotaCargo, cxc_pago.monto_pago FROM cj_det_nota_cargo cxc_pago
													WHERE cxc_pago.estatus IN (2)) AS q
												WHERE q.idNotaCargo = cxc_nd.idNotaCargo)));",
			valTpDato($idNotaDebito, "int"));
		$rsSaldoDcto = mysql_query($querySaldoDcto);
		if (!$rsSaldoDcto) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
		$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
		$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
		if ($totalRowsSaldoDcto > 0) {
			if ($rowSaldoDcto['saldoNotaCargo'] < 0) {
				return array(false, "La Nota de Débito Nro. ".$rowSaldoDcto['numeroNotaCargo']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo");
			} else if ($rowSaldoDcto['cant_pagos_pendientes'] > 0) {
				return array(false, "La Nota de Débito Nro. ".$rowSaldoDcto['numeroNotaCargo']." del Cliente ".$rowSaldoDcto['nombre_cliente']." no puede ser pagada en su totalidad debido a que posee ".$rowSaldoDcto['cant_pagos_pendientes']." pagos pendientes por un total de ".number_format($rowSaldoDcto['monto_pagos_pendientes'], 2, ".", ",").". Por favor termine de registrar o anular dichos pagos.");
			}
		}
		
		return array(
			0 => true,
			"idNotaDebito" => $idNotaDebito);
	}
	
	function actualizarCheque($idCheque) {
		
		// ACTUALIZA EL SALDO DEL CHEQUE (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
		$updateSQL = sprintf("UPDATE cj_cc_cheque cxc_ch SET
			saldo_cheque = monto_neto_cheque,
			total_pagado_cheque = monto_neto_cheque
		WHERE cxc_ch.id_cheque = %s;",
			valTpDato($idCheque, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ACTUALIZA EL SALDO DEL CHEQUE SEGUN LOS PAGOS QUE HA REALIZADO CON ESTE
		$updateSQL = sprintf("UPDATE cj_cc_cheque cxc_ch SET
			saldo_cheque = saldo_cheque
							- (IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
									WHERE cxc_pago.id_cheque = cxc_ch.id_cheque
										AND cxc_pago.formaPago IN (2)
										AND cxc_pago.estatus IN (1,2)), 0)
								+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
										WHERE cxc_pago.id_cheque = cxc_ch.id_cheque
											AND cxc_pago.formaPago IN (2)
											AND cxc_pago.estatus IN (1,2)), 0)
								+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
										WHERE cxc_pago.id_cheque = cxc_ch.id_cheque
											AND cxc_pago.idFormaPago IN (2)
											AND cxc_pago.estatus IN (1,2)), 0)
								+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
										WHERE cxc_pago.id_cheque = cxc_ch.id_cheque
											AND cxc_pago.id_forma_pago IN (2)
											AND cxc_pago.estatus IN (1,2)), 0))
		WHERE cxc_ch.id_cheque = %s;",
			valTpDato($idCheque, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ACTUALIZA EL ESTATUS DEL CHEQUE (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
		$updateSQL = sprintf("UPDATE cj_cc_cheque cxc_ch SET
			estado_cheque = (CASE
								WHEN (ROUND(monto_neto_cheque, 2) > ROUND(total_pagado_cheque, 2)
									AND ROUND(saldo_cheque, 2) > 0) THEN
									0
								WHEN (ROUND(monto_neto_cheque, 2) = ROUND(total_pagado_cheque, 2)
									AND ROUND(saldo_cheque, 2) <= 0
									AND cxc_ch.id_cheque IN (SELECT * 
																FROM (SELECT cxc_pago.id_cheque FROM an_pagos cxc_pago
																	WHERE cxc_pago.formaPago IN (2)
																		AND cxc_pago.estatus IN (1)
																	
																	UNION
																	
																	SELECT cxc_pago.id_cheque FROM sa_iv_pagos cxc_pago
																	WHERE cxc_pago.formaPago IN (2)
																		AND cxc_pago.estatus IN (1)
																	
																	UNION
																	
																	SELECT cxc_pago.id_cheque FROM cj_det_nota_cargo cxc_pago
																	WHERE cxc_pago.idFormaPago IN (2)
																		AND cxc_pago.estatus IN (1)
																	
																	UNION
																	
																	SELECT cxc_pago.id_cheque FROM cj_cc_detalleanticipo cxc_pago
																	WHERE cxc_pago.id_forma_pago IN (2)
																		AND cxc_pago.estatus IN (1)) AS q)) THEN
									3
								WHEN (ROUND(monto_neto_cheque, 2) = ROUND(total_pagado_cheque, 2)
									AND ROUND(monto_neto_cheque, 2) = ROUND(saldo_cheque, 2)) THEN
									1
								WHEN (ROUND(monto_neto_cheque, 2) = ROUND(total_pagado_cheque, 2)
									AND ROUND(monto_neto_cheque, 2) > ROUND(saldo_cheque, 2)
									AND ROUND(saldo_cheque, 2) > 0) THEN
									2
								WHEN (ROUND(monto_neto_cheque, 2) = ROUND(total_pagado_cheque, 2)
									AND ROUND(saldo_cheque, 2) <= 0) THEN
									3
								WHEN (ROUND(monto_neto_cheque, 2) > ROUND(total_pagado_cheque, 2)
									AND ROUND(saldo_cheque, 2) <= 0) THEN
									4
							END)
		WHERE cxc_ch.id_cheque = %s;",
			valTpDato($idCheque, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// VERIFICA EL SALDO DEL CHEQUE A VER SI ESTA NEGATIVO
		$querySaldoDcto = sprintf("SELECT cxc_ch.*,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
		FROM cj_cc_cheque cxc_ch
			INNER JOIN cj_cc_cliente cliente ON (cxc_ch.id_cliente = cliente.id)
		WHERE id_cheque = %s
			AND saldo_cheque < 0;",
			valTpDato($idCheque, "int"));
		$rsSaldoDcto = mysql_query($querySaldoDcto);
		if (!$rsSaldoDcto) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
		$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
		$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
		if ($totalRowsSaldoDcto > 0) { return array(false, "El Cheque Nro. ".$rowSaldoDcto['numero_cheque']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo"); }
		
		return array(
			0 => true,
			"idCheque" => $idCheque);
	}
	
	function actualizarTransferencia($idTransferencia) {
		
		// ACTUALIZA EL SALDO DE LA TRANSFERENCIA (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
		$updateSQL = sprintf("UPDATE cj_cc_transferencia cxc_tb SET
			saldo_transferencia = monto_neto_transferencia,
			total_pagado_transferencia = monto_neto_transferencia
		WHERE cxc_tb.id_transferencia = %s;",
			valTpDato($idTransferencia, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ACTUALIZA EL SALDO DE LA TRANSFERENCIA SEGUN LOS PAGOS QUE HA REALIZADO CON ESTE
		$updateSQL = sprintf("UPDATE cj_cc_transferencia cxc_tb SET
			saldo_transferencia = saldo_transferencia
							- (IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
									WHERE cxc_pago.id_transferencia = cxc_tb.id_transferencia
										AND cxc_pago.formaPago IN (4)
										AND cxc_pago.estatus IN (1,2)), 0)
								+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
										WHERE cxc_pago.id_transferencia = cxc_tb.id_transferencia
											AND cxc_pago.formaPago IN (4)
											AND cxc_pago.estatus IN (1,2)), 0)
								+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
										WHERE cxc_pago.id_transferencia = cxc_tb.id_transferencia
											AND cxc_pago.idFormaPago IN (4)
											AND cxc_pago.estatus IN (1,2)), 0)
								+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
										WHERE cxc_pago.id_transferencia = cxc_tb.id_transferencia
											AND cxc_pago.id_forma_pago IN (4)
											AND cxc_pago.estatus IN (1,2)), 0))
		WHERE cxc_tb.id_transferencia = %s;",
			valTpDato($idTransferencia, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ACTUALIZA EL ESTATUS DE LA TRANSFERENCIA (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
		$updateSQL = sprintf("UPDATE cj_cc_transferencia cxc_tb SET
			estado_transferencia = (CASE
								WHEN (ROUND(monto_neto_transferencia, 2) > ROUND(total_pagado_transferencia, 2)
									AND ROUND(saldo_transferencia, 2) > 0) THEN
									0
								WHEN (ROUND(monto_neto_transferencia, 2) = ROUND(total_pagado_transferencia, 2)
									AND ROUND(saldo_transferencia, 2) <= 0
									AND cxc_tb.id_transferencia IN (SELECT * 
																FROM (SELECT cxc_pago.id_transferencia FROM an_pagos cxc_pago
																	WHERE cxc_pago.formaPago IN (4)
																		AND cxc_pago.estatus IN (1)
																	
																	UNION
																	
																	SELECT cxc_pago.id_transferencia FROM sa_iv_pagos cxc_pago
																	WHERE cxc_pago.formaPago IN (4)
																		AND cxc_pago.estatus IN (1)
																	
																	UNION
																	
																	SELECT cxc_pago.id_transferencia FROM cj_det_nota_cargo cxc_pago
																	WHERE cxc_pago.idFormaPago IN (4)
																		AND cxc_pago.estatus IN (1)
																	
																	UNION
																	
																	SELECT cxc_pago.id_transferencia FROM cj_cc_detalleanticipo cxc_pago
																	WHERE cxc_pago.id_forma_pago IN (4)
																		AND cxc_pago.estatus IN (1)) AS q)) THEN
									3
								WHEN (ROUND(monto_neto_transferencia, 2) = ROUND(total_pagado_transferencia, 2)
									AND ROUND(monto_neto_transferencia, 2) = ROUND(saldo_transferencia, 2)) THEN
									1
								WHEN (ROUND(monto_neto_transferencia, 2) = ROUND(total_pagado_transferencia, 2)
									AND ROUND(monto_neto_transferencia, 2) > ROUND(saldo_transferencia, 2)
									AND ROUND(saldo_transferencia, 2) > 0) THEN
									2

								WHEN (ROUND(monto_neto_transferencia, 2) = ROUND(total_pagado_transferencia, 2)
									AND ROUND(saldo_transferencia, 2) <= 0) THEN
									3
								WHEN (ROUND(monto_neto_transferencia, 2) > ROUND(total_pagado_transferencia, 2)
									AND ROUND(saldo_transferencia, 2) <= 0) THEN
									4
							END)
		WHERE cxc_tb.id_transferencia = %s;",
			valTpDato($idTransferencia, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// VERIFICA EL SALDO DE LA TRANSFERENCIA A VER SI ESTA NEGATIVO
		$querySaldoDcto = sprintf("SELECT cxc_tb.*,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
		FROM cj_cc_transferencia cxc_tb
			INNER JOIN cj_cc_cliente cliente ON (cxc_tb.id_cliente = cliente.id)
		WHERE id_transferencia = %s
			AND saldo_transferencia < 0;",
			valTpDato($idTransferencia, "int"));
		$rsSaldoDcto = mysql_query($querySaldoDcto);
		if (!$rsSaldoDcto) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
		$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
		$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
		if ($totalRowsSaldoDcto > 0) { return array(false, "La Transferencia Nro. ".$rowSaldoDcto['numero_transferencia']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo"); }
		
		return array(
			0 => true,
			"idTransferencia" => $idTransferencia);
	}
	
	function actualizarAnticipo($idAnticipo) {
		
		// ACTUALIZA EL SALDO Y EL MONTO PAGADO DEL ANTICIPO (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
		$updateSQL = sprintf("UPDATE cj_cc_anticipo cxc_ant SET
			saldoAnticipo = montoNetoAnticipo,
			totalPagadoAnticipo = IF(DATE_FORMAT(cxc_ant.fechaAnticipo, %s) >= DATE_FORMAT('2016-09-01', %s),
				IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
						WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
							AND (cxc_pago.id_forma_pago NOT IN (11)
								OR (cxc_pago.id_forma_pago IN (11) AND cxc_pago.id_concepto NOT IN (7,8,9)))
							AND cxc_pago.id_empleado_anulado IS NULL
							AND cxc_pago.estatus IN (1,2)), 0),
				IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
						WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
							AND (cxc_pago.id_forma_pago NOT IN (11)
								OR (cxc_pago.id_forma_pago IN (11)
									AND (cxc_pago.id_concepto NOT IN (6,7,8,9)
										OR (cxc_pago.id_concepto IN (6) AND cxc_ant.idAnticipo IN (SELECT cxc_nd.id_anticipo_bono
																									FROM cj_cc_notadecargo cxc_nd
																									WHERE cxc_nd.id_anticipo_bono IS NOT NULL)))))
							AND cxc_pago.id_empleado_anulado IS NULL
							AND cxc_pago.estatus IN (1,2)), 0))
		WHERE cxc_ant.idAnticipo = %s;",
			valTpDato("%Y-%m-%d", "date"),
			valTpDato("%Y-%m-%d", "date"),
			valTpDato($idAnticipo, "int")); // AND (cxc_pago.id_concepto IS NULL OR cxc_pago.id_concepto NOT IN (6))
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		$updateSQL = sprintf("UPDATE cj_cc_anticipo cxc_ant SET
			totalPagadoAnticipo = montoNetoAnticipo
		WHERE cxc_ant.totalPagadoAnticipo > cxc_ant.montoNetoAnticipo
			AND cxc_ant.idAnticipo = %s;",
			valTpDato($idAnticipo, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ACTUALIZA EL SALDO DEL ANTICIPO SEGUN LOS PAGOS QUE HA REALIZADO CON ESTE
		$updateSQL = sprintf("UPDATE cj_cc_anticipo cxc_ant SET
			saldoAnticipo = saldoAnticipo
								- (IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
											WHERE cxc_pago.numeroDocumento = cxc_ant.idAnticipo
												AND cxc_pago.formaPago IN (7)
												AND cxc_pago.estatus IN (1,2)), 0)
									+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
												WHERE cxc_pago.numeroDocumento = cxc_ant.idAnticipo
													AND cxc_pago.formaPago IN (7)
													AND cxc_pago.estatus IN (1,2)), 0)
									+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
												WHERE cxc_pago.numeroDocumento = cxc_ant.idAnticipo
													AND cxc_pago.idFormaPago IN (7)
													AND cxc_pago.estatus IN (1,2)), 0))
		WHERE cxc_ant.idAnticipo = %s;",
			valTpDato($idAnticipo, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ACTUALIZA EL ESTATUS DEL ANTICIPO (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
		$updateSQL = sprintf("UPDATE cj_cc_anticipo cxc_ant SET
			estadoAnticipo = (CASE
								WHEN (ROUND(montoNetoAnticipo, 2) > ROUND(totalPagadoAnticipo, 2)
									AND ROUND(saldoAnticipo, 2) > 0) THEN
									0
								WHEN (ROUND(montoNetoAnticipo, 2) = ROUND(totalPagadoAnticipo, 2)
									AND ROUND(saldoAnticipo, 2) <= 0
									AND cxc_ant.idAnticipo IN (SELECT * 
																FROM (SELECT cxc_pago.numeroDocumento FROM an_pagos cxc_pago
																	WHERE cxc_pago.formaPago IN (7)
																		AND cxc_pago.estatus IN (1)
																	
																	UNION
																	
																	SELECT cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
																	WHERE cxc_pago.formaPago IN (7)
																		AND cxc_pago.estatus IN (1)
																	
																	UNION
																	
																	SELECT cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
																	WHERE cxc_pago.idFormaPago IN (7)
																		AND cxc_pago.estatus IN (1)) AS q)) THEN
									3
								WHEN (ROUND(montoNetoAnticipo, 2) = ROUND(totalPagadoAnticipo, 2)
									AND ROUND(montoNetoAnticipo, 2) = ROUND(saldoAnticipo, 2)) THEN
									1
								WHEN (ROUND(montoNetoAnticipo, 2) = ROUND(totalPagadoAnticipo, 2)
									AND ROUND(montoNetoAnticipo, 2) > ROUND(saldoAnticipo, 2)
									AND ROUND(saldoAnticipo, 2) > 0) THEN
									2
								WHEN (ROUND(montoNetoAnticipo, 2) = ROUND(totalPagadoAnticipo, 2)
									AND ROUND(saldoAnticipo, 2) <= 0) THEN
									3
								WHEN (ROUND(montoNetoAnticipo, 2) > ROUND(totalPagadoAnticipo, 2)
									AND ROUND(saldoAnticipo, 2) <= 0) THEN
									4
							END),
			fecha_pagada = (CASE
								WHEN (ROUND(cxc_ant.totalPagadoAnticipo, 2) = cxc_ant.montoNetoAnticipo AND cxc_ant.fecha_pagada IS NULL) THEN
									CONCAT(
										(SELECT MAX(cxc_pago.fechaPagoAnticipo) FROM cj_cc_detalleanticipo cxc_pago
										WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo),
										' ',
										DATE_FORMAT(NOW(), %s))
								WHEN (ROUND(cxc_ant.totalPagadoAnticipo, 2) = cxc_ant.montoNetoAnticipo AND cxc_ant.fecha_pagada IS NOT NULL) THEN
									cxc_ant.fecha_pagada
							END)
		WHERE cxc_ant.idAnticipo = %s;",
			valTpDato("%H:%i:%s", "date"),
			valTpDato($idAnticipo, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// VERIFICA EL SALDO DEL ANTICIPO A VER SI ESTA NEGATIVO
		$querySaldoDcto = sprintf("SELECT cxc_ant.*,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
		FROM cj_cc_anticipo cxc_ant
			INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
		WHERE idAnticipo = %s
			AND saldoAnticipo < 0;",
			valTpDato($idAnticipo, "int"));
		$rsSaldoDcto = mysql_query($querySaldoDcto);
		if (!$rsSaldoDcto) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
		$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
		$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
		if ($totalRowsSaldoDcto > 0) { return array(false, "El Anticipo Nro. ".$rowSaldoDcto['numeroAnticipo']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo"); }
		
		return array(
			0 => true,
			"idAnticipo" => $idAnticipo);
	}
	
	function actualizarNotaCredito($idNotaCredito) {
			
		// ACTUALIZA EL SALDO DEL NOTA CREDITO DEPENDIENDO DE SUS PAGOS (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
		$updateSQL = sprintf("UPDATE cj_cc_notacredito cxc_nc SET
			saldoNotaCredito = montoNetoNotaCredito
		WHERE idNotaCredito = %s;",
			valTpDato($idNotaCredito, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		// ACTUALIZA EL SALDO DEL NOTA CREDITO SEGUN LOS PAGOS QUE HA REALIZADO CON ESTE
		$updateSQL = sprintf("UPDATE cj_cc_notacredito cxc_nc SET
			saldoNotaCredito = saldoNotaCredito
								- (IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
										WHERE cxc_pago.numeroDocumento = cxc_nc.idNotaCredito
											AND cxc_pago.formaPago IN (8)
											AND cxc_pago.estatus IN (1,2)), 0)
									+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
											WHERE cxc_pago.numeroDocumento = cxc_nc.idNotaCredito
												AND cxc_pago.formaPago IN (8)
												AND cxc_pago.estatus IN (1,2)), 0)
									+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
											WHERE cxc_pago.numeroDocumento = cxc_nc.idNotaCredito
												AND cxc_pago.idFormaPago IN (8)
												AND cxc_pago.estatus IN (1,2)), 0)
									+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
											WHERE cxc_pago.numeroControlDetalleAnticipo = cxc_nc.idNotaCredito
												AND cxc_pago.id_forma_pago IN (8)
												AND cxc_pago.estatus IN (1,2)), 0))
		WHERE cxc_nc.idNotaCredito = %s;",
			valTpDato($idNotaCredito, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		// ACTUALIZA EL ESTATUS DEL NOTA CREDITO (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
		$updateSQL = sprintf("UPDATE cj_cc_notacredito cxc_nc SET
			estadoNotaCredito = (CASE
								WHEN (ROUND(montoNetoNotaCredito, 2) > ROUND(montoNetoNotaCredito, 2)
									AND ROUND(saldoNotaCredito, 2) > 0) THEN
									0
								WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
									AND ROUND(saldoNotaCredito, 2) <= 0
									AND cxc_nc.idNotaCredito IN (SELECT * 
																FROM (SELECT cxc_pago.numeroDocumento FROM an_pagos cxc_pago
																	WHERE cxc_pago.formaPago IN (8)
																		AND cxc_pago.estatus = 1
																	
																	UNION
																	
																	SELECT cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
																	WHERE cxc_pago.formaPago IN (8)
																		AND cxc_pago.estatus = 1
																	
																	UNION
																	
																	SELECT cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
																	WHERE cxc_pago.idFormaPago IN (8)
																		AND cxc_pago.estatus = 1
																	
																	UNION
																	
																	SELECT cxc_pago.numeroControlDetalleAnticipo FROM cj_cc_detalleanticipo cxc_pago
																	WHERE cxc_pago.id_forma_pago IN (8)
																		AND cxc_pago.estatus = 1) AS q)) THEN
									3
								WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
									AND ROUND(montoNetoNotaCredito, 2) = ROUND(saldoNotaCredito, 2)) THEN
									1
								WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
									AND ROUND(montoNetoNotaCredito, 2) > ROUND(saldoNotaCredito, 2)
									AND ROUND(saldoNotaCredito, 2) > 0) THEN
									2
								WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
									AND ROUND(saldoNotaCredito, 2) <= 0) THEN
									3
								WHEN (ROUND(montoNetoNotaCredito, 2) > ROUND(montoNetoNotaCredito, 2)
									AND ROUND(saldoNotaCredito, 2) <= 0) THEN
									4
							END)
		WHERE cxc_nc.idNotaCredito = %s;",
			valTpDato($idNotaCredito, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		// VERIFICA EL SALDO DEL NOTA CREDITO A VER SI ESTA NEGATIVO
		$querySaldoDcto = sprintf("SELECT cxc_nc.*,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
		FROM cj_cc_notacredito cxc_nc
			INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
		WHERE idNotaCredito = %s
			AND saldoNotaCredito < 0;",
			valTpDato($idNotaCredito, "int"));
		$rsSaldoDcto = mysql_query($querySaldoDcto);
		if (!$rsSaldoDcto) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
		$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
		$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
		if ($totalRowsSaldoDcto > 0) { return array(false, "La Nota de Crédito Nro. ".$rowSaldoDcto['numeracion_nota_credito']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo"); }
		
		return array(
			0 => true,
			"idNotaCredito" => $idNotaCredito);
	}
	
	function guardarReciboPagoCxCFA($idCajaPpal, $apertCajaPpal, $idApertura, $fechaRegistroPago, $arrayObjPago) {
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = %s
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato(((in_array($idCajaPpal,array(1))) ? 45 : 44), "int"), // 44 = Recibo de Pago Repuestos y Servicios, 45 = Recibo de Pago Vehículos
			valTpDato($this->idEmpresa, "int"),
			valTpDato($this->idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$idNumeraciones = $rowNumeracion['id_numeracion'];
		$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
		if ($rowNumeracion['numero_actual'] == "") { return array(false, "No se ha configurado numeracion de comprobantes de pago"); }
		
		// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		$numeroActualPago = $numeroActual;
		
		// INSERTA EL RECIBO DE PAGO
		$insertSQL = sprintf("INSERT INTO cj_encabezadorecibopago (numeroComprobante, fechaComprobante, idTipoDeDocumento, idConcepto, numero_tipo_documento, id_departamento, id_empleado_creador)
		VALUES (%s, %s, %s, %s, %s, %s, %s)",
			valTpDato($numeroActualPago, "int"),
			valTpDato($fechaRegistroPago, "date"),
			valTpDato(1, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
			valTpDato(0, "int"),		
			valTpDato($this->idDocumento, "int"),
			valTpDato($this->idModulo, "int"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idEncabezadoReciboPago = mysql_insert_id();
		
		// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
		$insertSQL = sprintf("INSERT INTO %s (id_factura, fecha_pago)
		VALUES (%s, %s)",
			valTpDato(((in_array($idCajaPpal,array(1))) ? "cj_cc_encabezado_pago_v" : "cj_cc_encabezado_pago_rs"), "campo"),
			valTpDato($this->idDocumento, "int"),
			valTpDato($fechaRegistroPago, "date"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idEncabezadoPago = mysql_insert_id();
		
		foreach($arrayObjPago as $indicePago => $valorPago) {
			$txtIdFormaPago = $valorPago['txtIdFormaPago'];
			$hddIdPago = $valorPago['hddIdPago'];
			
			if (!($hddIdPago > 0)) {
				if (isset($txtIdFormaPago)) {
					$arrayDetallePago = array(
						"idCajaPpal" => $idCajaPpal,
						"apertCajaPpal" => $apertCajaPpal,
						"idApertura" => $idApertura,
						"numeroActualFactura" => $valorPago['numeroActualFactura'],
						"fechaRegistroPago" => $fechaRegistroPago,
						"idEncabezadoReciboPago" => $idEncabezadoReciboPago,
						"idEncabezadoPago" => $idEncabezadoPago,
						"cbxPosicionPago" => $valorPago['cbxPosicionPago'],
						"hddIdPago" => $hddIdPago,
						"txtIdFormaPago" => $txtIdFormaPago,
						"txtIdNumeroDctoPago" => $valorPago['txtIdNumeroDctoPago'],
						"txtNumeroDctoPago" => $valorPago['txtNumeroDctoPago'],
						"txtIdBancoCliente" => $valorPago['txtIdBancoCliente'],
						"txtCuentaClientePago" => $valorPago['txtCuentaClientePago'],
						"txtIdBancoCompania" => $valorPago['txtIdBancoCompania'],
						"txtIdCuentaCompaniaPago" => $valorPago['txtIdCuentaCompaniaPago'],
						"txtCuentaCompaniaPago" => $valorPago['txtCuentaCompaniaPago'],
						"txtFechaDeposito" => $valorPago['txtFechaDeposito'],
						"txtTipoTarjeta" => $valorPago['txtTipoTarjeta'],
						"hddObjDetalleDeposito" => $valorPago['hddObjDetalleDeposito'],
						"hddObjDetalleDepositoFormaPago" => $valorPago['hddObjDetalleDepositoFormaPago'],
						"hddObjDetalleDepositoBanco" => $valorPago['hddObjDetalleDepositoBanco'],
						"hddObjDetalleDepositoNroCuenta" => $valorPago['hddObjDetalleDepositoNroCuenta'],
						"hddObjDetalleDepositoNroCheque" => $valorPago['hddObjDetalleDepositoNroCheque'],
						"hddObjDetalleDepositoMonto" => $valorPago['hddObjDetalleDepositoMonto'],
						"txtMonto" => $valorPago['txtMonto'],
						"cbxCondicionMostrar" => $valorPago['cbxCondicionMostrar'],
						"lstSumarA" => $valorPago['lstSumarA']
					);
					
					$Result1 = $this->guardarPagoCxCFA(
						$arrayDetallePago['idCajaPpal'],
						$arrayDetallePago['apertCajaPpal'],
						$arrayDetallePago['idApertura'],
						$arrayDetallePago['numeroActualFactura'],
						$arrayDetallePago['fechaRegistroPago'],
						$arrayDetallePago['idEncabezadoReciboPago'],
						$arrayDetallePago['idEncabezadoPago'],
						$arrayDetallePago['cbxPosicionPago'],
						$arrayDetallePago['hddIdPago'],
						$arrayDetallePago['txtIdFormaPago'],
						$arrayDetallePago['txtIdNumeroDctoPago'],
						$arrayDetallePago['txtNumeroDctoPago'],
						$arrayDetallePago['txtIdBancoCliente'],
						$arrayDetallePago['txtCuentaClientePago'],
						$arrayDetallePago['txtIdBancoCompania'],
						$arrayDetallePago['txtIdCuentaCompaniaPago'],
						$arrayDetallePago['txtCuentaCompaniaPago'],
						$arrayDetallePago['txtFechaDeposito'],
						$arrayDetallePago['txtTipoTarjeta'],
						$arrayDetallePago['hddObjDetalleDeposito'],
						$arrayDetallePago['hddObjDetalleDepositoFormaPago'],
						$arrayDetallePago['hddObjDetalleDepositoBanco'],
						$arrayDetallePago['hddObjDetalleDepositoNroCuenta'],
						$arrayDetallePago['hddObjDetalleDepositoNroCheque'],
						$arrayDetallePago['hddObjDetalleDepositoMonto'],
						$arrayDetallePago['txtMonto'],
						$arrayDetallePago['cbxCondicionMostrar'],
						$arrayDetallePago['lstSumarA']);
					if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
					$idPago = $Result1['idPago'];
					
					// INSERTA EL DETALLE DEL RECIBO DE PAGO
					$insertSQL = sprintf("INSERT INTO cj_detallerecibopago (idComprobantePagoFactura, idPago)
					VALUES (%s, %s)",
						valTpDato($idEncabezadoReciboPago, "int"),
						valTpDato($idPago, "int"));
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				}
			}
		}
		
		$Result1 = $this->actualizarFactura($this->idDocumento);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
		
		return array(
			0 => true,
			"idEncabezadoReciboPago" => $idEncabezadoReciboPago);
	}
	
	function guardarReciboPagoCxCND($idCajaPpal, $apertCajaPpal, $idApertura, $fechaRegistroPago, $arrayObjPago) {
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = %s
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato(((in_array($idCajaPpal,array(1))) ? 45 : 44), "int"), // 44 = Recibo de Pago Repuestos y Servicios, 45 = Recibo de Pago Vehículos
			valTpDato($this->idEmpresa, "int"),
			valTpDato($this->idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$idNumeraciones = $rowNumeracion['id_numeracion'];
		$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
		if ($rowNumeracion['numero_actual'] == "") { return array(false, "No se ha configurado numeracion de comprobantes de pago"); }
		
		// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		$numeroActualPago = $numeroActual;
		
		// INSERTA EL RECIBO DE PAGO
		$insertSQL = sprintf("INSERT INTO cj_encabezadorecibopago (numeroComprobante, fechaComprobante, idTipoDeDocumento, idConcepto, numero_tipo_documento, id_departamento, id_empleado_creador)
		VALUES (%s, %s, %s, %s, %s, %s, %s)",
			valTpDato($numeroActualPago, "int"),
			valTpDato($fechaRegistroPago, "date"),
			valTpDato(2, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
			valTpDato(0, "int"),		
			valTpDato($this->idDocumento, "int"),
			valTpDato($this->idModulo, "int"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idEncabezadoReciboPago = mysql_insert_id();
		
		// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
		$insertSQL = sprintf("INSERT INTO %s (id_nota_cargo, fecha_pago)
		VALUES (%s, %s)",
			valTpDato(((in_array($idCajaPpal,array(1))) ? "cj_cc_encabezado_pago_nc_v" : "cj_cc_encabezado_pago_nc_rs"), "campo"),
			valTpDato($this->idDocumento, "int"),
			valTpDato($fechaRegistroPago, "date"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idEncabezadoPago = mysql_insert_id();
		
		foreach($arrayObjPago as $indicePago => $valorPago) {
			$txtIdFormaPago = $valorPago['txtIdFormaPago'];
			$hddIdPago = $valorPago['hddIdPago'];
			
			if (!($hddIdPago > 0)) {
				if (isset($txtIdFormaPago)) {
					$arrayDetallePago = array(
						"idCajaPpal" => $idCajaPpal,
						"apertCajaPpal" => $apertCajaPpal,
						"idApertura" => $idApertura,
						"fechaRegistroPago" => $fechaRegistroPago,
						"idEncabezadoReciboPago" => $idEncabezadoReciboPago,
						"idEncabezadoPago" => $idEncabezadoPago,
						"cbxPosicionPago" => $valorPago['cbxPosicionPago'],
						"hddIdPago" => $hddIdPago,
						"txtIdFormaPago" => $txtIdFormaPago,
						"txtIdNumeroDctoPago" => $valorPago['txtIdNumeroDctoPago'],
						"txtNumeroDctoPago" => $valorPago['txtNumeroDctoPago'],
						"txtIdBancoCliente" => $valorPago['txtIdBancoCliente'],
						"txtCuentaClientePago" => $valorPago['txtCuentaClientePago'],
						"txtIdBancoCompania" => $valorPago['txtIdBancoCompania'],
						"txtIdCuentaCompaniaPago" => $valorPago['txtIdCuentaCompaniaPago'],
						"txtCuentaCompaniaPago" => $valorPago['txtCuentaCompaniaPago'],
						"txtFechaDeposito" => $valorPago['txtFechaDeposito'],
						"txtTipoTarjeta" => $valorPago['txtTipoTarjeta'],
						"hddObjDetalleDeposito" => $valorPago['hddObjDetalleDeposito'],
						"hddObjDetalleDepositoFormaPago" => $valorPago['hddObjDetalleDepositoFormaPago'],
						"hddObjDetalleDepositoBanco" => $valorPago['hddObjDetalleDepositoBanco'],
						"hddObjDetalleDepositoNroCuenta" => $valorPago['hddObjDetalleDepositoNroCuenta'],
						"hddObjDetalleDepositoNroCheque" => $valorPago['hddObjDetalleDepositoNroCheque'],
						"hddObjDetalleDepositoMonto" => $valorPago['hddObjDetalleDepositoMonto'],
						"txtMonto" => $valorPago['txtMonto']
					);
					
					$Result1 = $this->guardarPagoCxCND(
						$arrayDetallePago['idCajaPpal'],
						$arrayDetallePago['apertCajaPpal'],
						$arrayDetallePago['idApertura'],
						$arrayDetallePago['fechaRegistroPago'],
						$arrayDetallePago['idEncabezadoReciboPago'],
						$arrayDetallePago['idEncabezadoPago'],
						$arrayDetallePago['cbxPosicionPago'],
						$arrayDetallePago['hddIdPago'],
						$arrayDetallePago['txtIdFormaPago'],
						$arrayDetallePago['txtIdNumeroDctoPago'],
						$arrayDetallePago['txtNumeroDctoPago'],
						$arrayDetallePago['txtIdBancoCliente'],
						$arrayDetallePago['txtCuentaClientePago'],
						$arrayDetallePago['txtIdBancoCompania'],
						$arrayDetallePago['txtIdCuentaCompaniaPago'],
						$arrayDetallePago['txtCuentaCompaniaPago'],
						$arrayDetallePago['txtFechaDeposito'],
						$arrayDetallePago['txtTipoTarjeta'],
						$arrayDetallePago['hddObjDetalleDeposito'],
						$arrayDetallePago['hddObjDetalleDepositoFormaPago'],
						$arrayDetallePago['hddObjDetalleDepositoBanco'],
						$arrayDetallePago['hddObjDetalleDepositoNroCuenta'],
						$arrayDetallePago['hddObjDetalleDepositoNroCheque'],
						$arrayDetallePago['hddObjDetalleDepositoMonto'],
						$arrayDetallePago['txtMonto']);
					if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
					$idPago = $Result1['idPago'];
					
					// INSERTA EL DETALLE DEL RECIBO DE PAGO
					$insertSQL = sprintf("INSERT INTO cj_detallerecibopago (idComprobantePagoFactura, idPago)
					VALUES (%s, %s)",
						valTpDato($idEncabezadoReciboPago, "int"),
						valTpDato($idPago, "int"));
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				}
			}
		}
		
		$Result1 = $this->actualizarNotaDebito($this->idDocumento);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
		
		return array(
			0 => true,
			"idEncabezadoReciboPago" => $idEncabezadoReciboPago);
	}
	
	function guardarReciboPagoCxCAN($idCajaPpal, $apertCajaPpal, $idApertura, $fechaRegistroPago, $arrayObjPago) {
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = %s
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato(((in_array($idCajaPpal,array(1))) ? 45 : 44), "int"), // 44 = Recibo de Pago Repuestos y Servicios, 45 = Recibo de Pago Vehículos
			valTpDato($this->idEmpresa, "int"),
			valTpDato($this->idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$idNumeraciones = $rowNumeracion['id_numeracion'];
		$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
		if ($rowNumeracion['numero_actual'] == "") { return array(false, "No se ha configurado numeracion de comprobantes de pago"); }
		
		// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		$numeroActualPago = $numeroActual;
		
		// INSERTA EL RECIBO DE PAGO
		$insertSQL = sprintf("INSERT INTO pg_reportesimpresion (fechaDocumento, numeroReporteImpresion, tipoDocumento, idDocumento, idCliente, id_departamento, id_empleado_creador)
		VALUES(%s, %s, %s, %s, %s, %s, %s)",
			valTpDato($fechaRegistroPago, "date"),
			valTpDato($numeroActualPago, "int"),
			valTpDato("AN", "text"),
			valTpDato($this->idDocumento, "int"),
			valTpDato($this->idCliente, "int"),
			valTpDato($this->idModulo, "int"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idReporteImpresion = mysql_insert_id();
		
		foreach($arrayObjPago as $indicePago => $valorPago) {
			$txtIdFormaPago = $valorPago['txtIdFormaPago'];
			$hddIdPago = $valorPago['hddIdPago'];
			
			if (!($hddIdPago > 0)) {
				if (isset($txtIdFormaPago)) {
					$arrayDetallePago = array(
						"idCajaPpal" => $idCajaPpal,
						"apertCajaPpal" => $apertCajaPpal,
						"idApertura" => $idApertura,
						"fechaRegistroPago" => $fechaRegistroPago,
						"idReporteImpresion" => $idReporteImpresion,
						"cbxPosicionPago" => $valorPago['cbxPosicionPago'],
						"hddIdPago" => $hddIdPago,
						"txtIdFormaPago" => $txtIdFormaPago,
						"txtIdConceptoPago" => $valorPago['txtIdConceptoPago'],
						"txtIdNumeroDctoPago" => $valorPago['txtIdNumeroDctoPago'],
						"txtNumeroDctoPago" => $valorPago['txtNumeroDctoPago'],
						"txtIdBancoCliente" => $valorPago['txtIdBancoCliente'],
						"txtCuentaClientePago" => $valorPago['txtCuentaClientePago'],
						"txtIdBancoCompania" => $valorPago['txtIdBancoCompania'],
						"txtIdCuentaCompaniaPago" => $valorPago['txtIdCuentaCompaniaPago'],
						"txtCuentaCompaniaPago" => $valorPago['txtCuentaCompaniaPago'],
						"txtFechaDeposito" => $valorPago['txtFechaDeposito'],
						"txtTipoTarjeta" => $valorPago['txtTipoTarjeta'],
						"hddObjDetalleDeposito" => $valorPago['hddObjDetalleDeposito'],
						"hddObjDetalleDepositoFormaPago" => $valorPago['hddObjDetalleDepositoFormaPago'],
						"hddObjDetalleDepositoBanco" => $valorPago['hddObjDetalleDepositoBanco'],
						"hddObjDetalleDepositoNroCuenta" => $valorPago['hddObjDetalleDepositoNroCuenta'],
						"hddObjDetalleDepositoNroCheque" => $valorPago['hddObjDetalleDepositoNroCheque'],
						"hddObjDetalleDepositoMonto" => $valorPago['hddObjDetalleDepositoMonto'],
						"txtMonto" => $valorPago['txtMonto'],
						"cbxCondicionMostrar" => $valorPago['cbxCondicionMostrar'],
						"lstSumarA" => $valorPago['lstSumarA']
					);
					
					$Result1 = $this->guardarPagoCxCAN(
						$arrayDetallePago['idCajaPpal'],
						$arrayDetallePago['apertCajaPpal'],
						$arrayDetallePago['idApertura'],
						$arrayDetallePago['fechaRegistroPago'],
						$arrayDetallePago['idReporteImpresion'],
						$arrayDetallePago['cbxPosicionPago'],
						$arrayDetallePago['hddIdPago'],
						$arrayDetallePago['txtIdFormaPago'],
						$arrayDetallePago['txtIdConceptoPago'],
						$arrayDetallePago['txtIdNumeroDctoPago'],
						$arrayDetallePago['txtNumeroDctoPago'],
						$arrayDetallePago['txtIdBancoCliente'],
						$arrayDetallePago['txtCuentaClientePago'],
						$arrayDetallePago['txtIdBancoCompania'],
						$arrayDetallePago['txtIdCuentaCompaniaPago'],
						$arrayDetallePago['txtCuentaCompaniaPago'],
						$arrayDetallePago['txtFechaDeposito'],
						$arrayDetallePago['txtTipoTarjeta'],
						$arrayDetallePago['hddObjDetalleDeposito'],
						$arrayDetallePago['hddObjDetalleDepositoFormaPago'],
						$arrayDetallePago['hddObjDetalleDepositoBanco'],
						$arrayDetallePago['hddObjDetalleDepositoNroCuenta'],
						$arrayDetallePago['hddObjDetalleDepositoNroCheque'],
						$arrayDetallePago['hddObjDetalleDepositoMonto'],
						$arrayDetallePago['txtMonto'],
						$arrayDetallePago['cbxCondicionMostrar'],
						$arrayDetallePago['lstSumarA']);
					if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
					$idPago = $Result1['idPago'];
				}
			}
		}
		
		$Result1 = $this->actualizarAnticipo($this->idDocumento);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
		
		return array(
			0 => true,
			"idReporteImpresion" => $idReporteImpresion);
	}
	
	function guardarPagoCxCFA($idCajaPpal, $apertCajaPpal, $idApertura, $numeroActualFactura, $fechaRegistroPago, $idEncabezadoReciboPago, $idEncabezadoPago, $cbxPosicionPago, $hddIdPago, $txtIdFormaPago, $txtIdNumeroDctoPago, $txtNumeroDctoPago, $txtIdBancoCliente, $txtCuentaClientePago, $txtIdBancoCompania, $txtIdCuentaCompaniaPago, $txtCuentaCompaniaPago, $txtFechaDeposito, $txtTipoTarjeta, $hddObjDetalleDeposito, $hddObjDetalleDepositoFormaPago, $hddObjDetalleDepositoBanco, $hddObjDetalleDepositoNroCuenta, $hddObjDetalleDepositoNroCheque, $hddObjDetalleDepositoMonto, $txtMonto, $cbxCondicionMostrar, $lstSumarA) {
		
		$idCheque = "";
		$tipoCheque = "-";
		$idTransferencia = "";
		$tipoTransferencia = "-";
		$estatusPago = 1;
		if ($txtIdFormaPago == 1) { // 1 = Efectivo
			$idBancoCliente = 1;
			$txtCuentaClientePago = "-";
			$idBancoCompania = 1;
			$txtCuentaCompaniaPago = "-";
			$txtIdNumeroDctoPago = "-";
			$campo = "saldoEfectivo";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = str_replace(",", "", $txtMonto);
			$txtMontoSaldoCaja = $txtMontoSaldo;
		} else if ($txtIdFormaPago == 2) { // 2 = Cheque
			$idCheque = $txtIdNumeroDctoPago;
			$idBancoCliente = $txtIdBancoCliente;
			$txtCuentaClientePago = $txtCuentaClientePago;
			$idBancoCompania = 1;
			$txtCuentaCompaniaPago = "-";
			$txtIdNumeroDctoPago = $txtNumeroDctoPago;
			$tipoCheque = "0";
			$campo = "saldoCheques";
			if ($idCheque > 0) { // NO SUMA 2 = Cheque EN EL SALDO DE LA CAJA
				$tomadoEnCierre = 2; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
				$txtMontoSaldo = 0;
				$txtMontoSaldoCaja = 0;
			} else {
				$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
				$txtMontoSaldo = str_replace(",", "", $txtMonto);
				$txtMontoSaldoCaja = $txtMontoSaldo;
			}
		} else if ($txtIdFormaPago == 3) { // 3 = Deposito
			$idBancoCliente = 1;
			$txtCuentaClientePago = "-";
			$idBancoCompania = $txtIdBancoCompania;
			$txtCuentaCompaniaPago = $txtCuentaCompaniaPago;
			$txtIdNumeroDctoPago = $txtNumeroDctoPago;
			$campo = "saldoDepositos";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = str_replace(",", "", $txtMonto);
			$txtMontoSaldoCaja = $txtMontoSaldo;
		} else if ($txtIdFormaPago == 4) { // 4 = Transferencia Bancaria
			$idTransferencia = $txtIdNumeroDctoPago;
			$idBancoCliente = $txtIdBancoCliente;
			$txtCuentaClientePago = "-";
			$idBancoCompania = $txtIdBancoCompania;
			$txtCuentaCompaniaPago = $txtCuentaCompaniaPago;
			$txtIdNumeroDctoPago = $txtNumeroDctoPago;
			$tipoTransferencia = "0";
			$campo = "saldoTransferencia";
			if ($idTransferencia > 0) { // NO SUMA 4 = Transferencia Bancaria EN EL SALDO DE LA CAJA
				$tomadoEnCierre = 2; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
				$txtMontoSaldo = 0;
				$txtMontoSaldoCaja = 0;
			} else {
				$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
				$txtMontoSaldo = str_replace(",", "", $txtMonto);
				$txtMontoSaldoCaja = $txtMontoSaldo;
			}
		} else if ($txtIdFormaPago == 5) { // 5 = Tarjeta de Crédito
			$idBancoCliente = $txtIdBancoCliente;
			$txtCuentaClientePago = "-";
			$idBancoCompania = $txtIdBancoCompania;
			$txtCuentaCompaniaPago = $txtCuentaCompaniaPago;
			$txtIdNumeroDctoPago = $txtNumeroDctoPago;
			$campo = "saldoTarjetaCredito";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = str_replace(",", "", $txtMonto);
			$txtMontoSaldoCaja = $txtMontoSaldo;
		} else if ($txtIdFormaPago == 6) { // 6 = Tarjeta de Debito
			$idBancoCliente = $txtIdBancoCliente;
			$txtCuentaClientePago = "-";
			$idBancoCompania = $txtIdBancoCompania;
			$txtCuentaCompaniaPago = $txtCuentaCompaniaPago;
			$txtIdNumeroDctoPago = $txtNumeroDctoPago;
			$campo = "saldoTarjetaDebito";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = str_replace(",", "", $txtMonto);
			$txtMontoSaldoCaja = $txtMontoSaldo;
		} else if ($txtIdFormaPago == 7) { // 7 = Anticipo
			$idBancoCliente = 1;
			$txtCuentaClientePago = "-";
			$idBancoCompania = 1;
			$txtCuentaCompaniaPago = "-";
			$txtIdNumeroDctoPago = $txtIdNumeroDctoPago;
			$campo = "saldoAnticipo";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = str_replace(",", "", $txtMonto);
			$txtMontoSaldoCaja = 0;
			
			// BUSCA LOS DATOS DEL ANTICIPO (0 = Anulado; 1 = Activo)
			$queryAnticipo = sprintf("SELECT * FROM cj_cc_anticipo cxc_ant
			WHERE cxc_ant.idAnticipo = %s
				AND cxc_ant.estatus = 1;",
				valTpDato($txtIdNumeroDctoPago, "int"));
			$rsAnticipo = mysql_query($queryAnticipo);
			if (!$rsAnticipo) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowAnticipo = mysql_fetch_array($rsAnticipo);
			
			// (0 = No Cancelado, 1 = Cancelado/No Asignado, 2 = Parcialmente Asignado, 3 = Asignado)
			$estatusPago = (in_array($rowAnticipo['estadoAnticipo'], array(0))) ? 2 : $estatusPago;
		} else if ($txtIdFormaPago == 8) { // 8 = Nota de Crédito
			$idBancoCliente = 1;
			$txtCuentaClientePago = "-";
			$idBancoCompania = 1;
			$txtCuentaCompaniaPago = "-";
			$txtIdNumeroDctoPago = $txtIdNumeroDctoPago;
			$campo = "saldoNotaCredito";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = str_replace(",", "", $txtMonto);
			$txtMontoSaldoCaja = $txtMontoSaldo;
		} else if ($txtIdFormaPago == 9) { // 9 = Retención
			$idBancoCliente = 1;
			$txtCuentaClientePago = "-";
			$idBancoCompania = 1;
			$txtCuentaCompaniaPago = "-";
			$txtIdNumeroDctoPago = $txtNumeroDctoPago;
			$campo = "saldoRetencion";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = str_replace(",", "", $txtMonto);
			$txtMontoSaldoCaja = $txtMontoSaldo;
		} else if ($txtIdFormaPago == 10) { // 10 = Retencion I.S.L.R.
			$idBancoCliente = 1;
			$txtCuentaClientePago = "-";
			$idBancoCompania = 1;
			$txtCuentaCompaniaPago = "-";
			$txtIdNumeroDctoPago = $txtNumeroDctoPago;
			$campo = "saldoRetencion";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = str_replace(",", "", $txtMonto);
			$txtMontoSaldoCaja = $txtMontoSaldo;
		} else if ($txtIdFormaPago == 11) { // 11 = Otro
		}
		
		// ACTUALIZA LOS SALDOS EN LA APERTURA (NO TOMA EN CUENTA 7 = Anticipo EN EL SALDO DE LA CAJA)
		$updateSQL = sprintf("UPDATE ".$apertCajaPpal." SET
			%s = %s + %s,
			saldoCaja = saldoCaja + %s
		WHERE id = %s;",
			$campo, $campo, valTpDato($txtMontoSaldo, "real_inglesa"),
			valTpDato($txtMontoSaldoCaja, "real_inglesa"),
			valTpDato($idApertura, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// INSERTA LOS PAGOS DEL DOCUMENTO
		$insertSQL = sprintf("INSERT INTO %s (id_factura, fechaPago, formaPago, numeroDocumento, bancoOrigen, numero_cuenta_cliente, bancoDestino, cuentaEmpresa, montoPagado, numeroFactura, tipoCheque, id_cheque, tipo_transferencia, id_transferencia, tomadoEnComprobante, tomadoEnCierre, idCaja, id_apertura, estatus, id_empleado_creador, id_condicion_mostrar, id_mostrar_contado, %s)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato(((in_array($idCajaPpal,array(1))) ? "an_pagos" : "sa_iv_pagos"), "campo"),
			valTpDato(((in_array($idCajaPpal,array(1))) ? "id_encabezado_v" : "id_encabezado_rs"), "campo"),
			valTpDato($this->idDocumento, "int"),
			valTpDato(date("Y-m-d", strtotime($fechaRegistroPago)), "date"),
			valTpDato($txtIdFormaPago, "int"),
			valTpDato($txtIdNumeroDctoPago, "text"),
			valTpDato($idBancoCliente, "int"),
			valTpDato($txtCuentaClientePago, "text"),
			valTpDato($idBancoCompania, "int"),
			valTpDato($txtCuentaCompaniaPago, "text"),
			valTpDato($txtMonto, "real_inglesa"),
			valTpDato($numeroActualFactura, "text"),
			valTpDato($tipoCheque, "text"),
			valTpDato($idCheque, "int"),
			valTpDato($tipoTransferencia, "text"),
			valTpDato($idTransferencia, "int"),
			valTpDato(1, "int"),
			valTpDato($tomadoEnCierre, "int"), // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			valTpDato($idApertura, "int"),
			valTpDato($estatusPago, "int"), // Null = Anulado, 1 = Activo, 2 = Pendiente
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
			valTpDato($cbxCondicionMostrar, "int"), // Null = No, 1 = Si
			valTpDato($lstSumarA, "int"), // Null = No, 1 = Si
			valTpDato($idEncabezadoPago, "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$insertSQL); }
		$idPago = mysql_insert_id();
		
		$arrayIdDctoContabilidad[] = array(
			$idPago,
			$this->idModulo,
			"CAJAENTRADA");
		
		if ($txtIdFormaPago == 2) { // 2 = Cheque
			$Result1 = $this->actualizarCheque($idCheque);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
			
		} else if ($txtIdFormaPago == 3) { // 3 = Deposito
			$arrayPosiciones = explode("|",$hddObjDetalleDeposito);
			$arrayFormaPago = explode("|",$hddObjDetalleDepositoFormaPago);
			$arrayBanco = explode("|",$hddObjDetalleDepositoBanco);
			$arrayNroCuenta = explode("|",$hddObjDetalleDepositoNroCuenta);
			$arrayNroCheque = explode("|",$hddObjDetalleDepositoNroCheque);
			$arrayMonto = explode("|",$hddObjDetalleDepositoMonto);
			
			foreach($arrayPosiciones as $indiceDeposito => $valorDeposito) {
				if ($valorDeposito == $cbxPosicionPago) {
					if ($arrayFormaPago[$indiceDeposito] == 1) {
						$bancoDetalleDeposito = "";
						$nroCuentaDetalleDeposito = "";
						$nroChequeDetalleDeposito = "";
					} else {
						$bancoDetalleDeposito = $arrayBanco[$indiceDeposito];
						$nroCuentaDetalleDeposito = $arrayNroCuenta[$indiceDeposito];
						$nroChequeDetalleDeposito = $arrayNroCheque[$indiceDeposito];
					}
					
					$insertSQL = sprintf("INSERT INTO an_det_pagos_deposito_factura (idPago, fecha_deposito, idFormaPago, idBanco, numero_cuenta, numero_cheque, monto, id_tipo_documento, idCaja)
					VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)",
						valTpDato($idPago, "int"),
						valTpDato(date("Y-m-d", strtotime($txtFechaDeposito)), "date"),
						valTpDato($arrayFormaPago[$indiceDeposito], "int"),
						valTpDato($bancoDetalleDeposito, "int"),
						valTpDato($nroCuentaDetalleDeposito, "text"),
						valTpDato($nroChequeDetalleDeposito, "text"),
						valTpDato($arrayMonto[$indiceDeposito], "real_inglesa"),
						valTpDato(1, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
						valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$insertSQL); }
				}
			}
			
		} else if ($txtIdFormaPago == 4) { // 4 = Transferencia Bancaria
			$Result1 = $this->actualizarTransferencia($idTransferencia);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
			
		} else if (in_array($txtIdFormaPago, array(5,6))) { // 5 = Tarjeta de Crédito, 6 = Tarjeta de Debito
			$queryRetencionPunto = sprintf("SELECT id_retencion_punto FROM te_retencion_punto
			WHERE id_cuenta = %s
				AND id_tipo_tarjeta = %s;",
				valTpDato($txtIdCuentaCompaniaPago, "int"),
				valTpDato($txtTipoTarjeta, "int"));
			$rsRetencionPunto = mysql_query($queryRetencionPunto);
			if (!$rsRetencionPunto) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowRetencionPunto = mysql_fetch_array($rsRetencionPunto);
			
			$insertSQL = sprintf("INSERT INTO cj_cc_retencion_punto_pago (id_caja, id_pago, id_tipo_documento, id_retencion_punto)
			VALUES (%s, %s, %s, %s)",
				valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
				valTpDato($idPago, "int"),
				valTpDato(1, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
				valTpDato($rowRetencionPunto['id_retencion_punto'], "int"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$insertSQL); }
			
		} else if ($txtIdFormaPago == 7) { // 7 = Anticipo
			$idAnticipo = $txtIdNumeroDctoPago;
			
			$Result1 = $this->actualizarAnticipo($idAnticipo);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
			
		} else if ($txtIdFormaPago == 8) { // 8 = Nota de Crédito
			$idNotaCredito = $txtIdNumeroDctoPago;
			
			$Result1 = $this->actualizarNotaCredito($idNotaCredito);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
			
		} else if ($txtIdFormaPago == 9) { // 9 = Retención
			$queryFactura = sprintf("SELECT * FROM cj_cc_encabezadofactura WHERE idFactura = %s;",
				valTpDato($this->idDocumento, "int"));
			$rsFactura = mysql_query($queryFactura);
			if (!$rsFactura) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowFactura = mysql_fetch_array($rsFactura);
			
			$porcImpuesto = $rowFactura['porcentajeIvaFactura'] + $rowFactura['porcentajeIvaDeLujoFactura'];
			$subTotalImpuesto = $rowFactura['calculoIvaFactura'] + $rowFactura['calculoIvaDeLujoFactura'];
			$porcRetenido = ($subTotalImpuesto > 0) ? $txtMontoSaldo * 100 / $subTotalImpuesto : 0;
			
			// BUSCA SI YA EXISTE UN COMPROBANTE CON LA INFORMACION INGRESADA
			$queryRetencionCabecera = sprintf("SELECT * FROM cj_cc_retencioncabezera retencion
			WHERE retencion.numeroComprobante LIKE %s
				AND retencion.fechaComprobante = %s
				AND retencion.anoPeriodoFiscal = %s
				AND retencion.mesPeriodoFiscal = %s
				AND retencion.idCliente = %s;",
				valTpDato($txtNumeroDctoPago, "text"),
				valTpDato(date("Y-m-d", strtotime($fechaRegistroPago)), "date"),
				valTpDato(date("Y", strtotime($fechaRegistroPago)), "text"),
				valTpDato(date("m", strtotime($fechaRegistroPago)), "text"),
				valTpDato($this->idCliente, "int"));
			$rsRetencionCabecera = mysql_query($queryRetencionCabecera);
			if (!$rsRetencionCabecera) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsRetenciones = mysql_num_rows($rsRetencionCabecera);
			$rowRetencionCabecera = mysql_fetch_array($rsRetencionCabecera);
			
			if ($totalRowsRetenciones > 0) {
				$idRetencionCabecera = $rowRetencionCabecera['idRetencionCabezera'];
			} else {
				$insertSQL = sprintf("INSERT INTO cj_cc_retencioncabezera (numeroComprobante, fechaComprobante, anoPeriodoFiscal, mesPeriodoFiscal, idCliente, idRegistrosUnidadesFisicas)
				VALUES (%s, %s, %s, %s, %s, %s)",
					valTpDato($txtNumeroDctoPago, "text"),
					valTpDato(date("Y-m-d", strtotime($fechaRegistroPago)), "date"),
					valTpDato(date("Y", strtotime($fechaRegistroPago)), "text"),
					valTpDato(date("m", strtotime($fechaRegistroPago)), "text"),
					valTpDato($this->idCliente, "int"),
					valTpDato(0, "int"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idRetencionCabecera = mysql_insert_id();
			}
			
			$insertSQL = sprintf("INSERT INTO cj_cc_retenciondetalle (idRetencionCabezera, fechaFactura, idFactura, numeroControlFactura, numeroNotaDebito, numeroNotaCredito, tipoDeTransaccion, numeroFacturaAfectada, totalCompraIncluyendoIva, comprasSinIva, baseImponible, porcentajeAlicuota, impuestoIva, IvaRetenido, porcentajeRetencion)
			VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
				valTpDato($idRetencionCabecera, "int"),
				valTpDato($rowFactura['fechaRegistroFactura'], "date"),
				valTpDato($this->idDocumento, "int"),
				valTpDato($rowFactura['numeroControl'], "text"),
				valTpDato(" ", "text"),
				valTpDato(" ", "text"),
				valTpDato(" ", "text"),
				valTpDato(" ", "text"),
				valTpDato($rowFactura['montoTotalFactura'], "real_inglesa"),
				valTpDato($rowFactura['subtotalFactura'], "real_inglesa"),
				valTpDato($rowFactura['baseImponible'], "real_inglesa"),
				valTpDato($porcImpuesto, "real_inglesa"),
				valTpDato($subTotalImpuesto, "real_inglesa"),
				valTpDato($txtMontoSaldo, "real_inglesa"),
				valTpDato($porcRetenido, "int"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
		} else if ($txtIdFormaPago == 10) { // 10 = Retencion I.S.L.R.
			
		} else if ($txtIdFormaPago == 11) { // 11 = Otro
			
		}
		
		return array(
			0 => true,
			"idPago" => $idPago);
	}
	
	function guardarPagoCxCND($idCajaPpal, $apertCajaPpal, $idApertura, $fechaRegistroPago, $idEncabezadoReciboPago, $idEncabezadoPago, $cbxPosicionPago, $hddIdPago, $txtIdFormaPago, $txtIdNumeroDctoPago, $txtNumeroDctoPago, $txtIdBancoCliente, $txtCuentaClientePago, $txtIdBancoCompania, $txtIdCuentaCompaniaPago, $txtCuentaCompaniaPago, $txtFechaDeposito, $txtTipoTarjeta, $hddObjDetalleDeposito, $hddObjDetalleDepositoFormaPago, $hddObjDetalleDepositoBanco, $hddObjDetalleDepositoNroCuenta, $hddObjDetalleDepositoNroCheque, $hddObjDetalleDepositoMonto, $txtMonto) {
		
		$idCheque = "";
		$tipoCheque = "-";
		$idTransferencia = "";
		$tipoTransferencia = "-";
		$estatusPago = 1;
		if ($txtIdFormaPago == 1) { // 1 = Efectivo
			$idBancoCliente = 1;
			$txtCuentaClientePago = "-";
			$idBancoCompania = 1;
			$txtCuentaCompaniaPago = "-";
			$txtIdNumeroDctoPago = "-";
			$campo = "saldoEfectivo";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = str_replace(",", "", $txtMonto);
			$txtMontoSaldoCaja = $txtMontoSaldo;
		} else if ($txtIdFormaPago == 2) { // 2 = Cheque
			$idCheque = $txtIdNumeroDctoPago;
			$idBancoCliente = $txtIdBancoCliente;
			$txtCuentaClientePago = $txtCuentaClientePago;
			$idBancoCompania = 1;
			$txtCuentaCompaniaPago = "-";
			$txtIdNumeroDctoPago = $txtNumeroDctoPago;
			$tipoCheque = "0";
			$campo = "saldoCheques";
			if ($idCheque > 0) { // NO SUMA 2 = Cheque EN EL SALDO DE LA CAJA
				$tomadoEnCierre = 2; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
				$txtMontoSaldo = 0;
				$txtMontoSaldoCaja = 0;
			} else {
				$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
				$txtMontoSaldo = str_replace(",", "", $txtMonto);
				$txtMontoSaldoCaja = $txtMontoSaldo;
			}
		} else if ($txtIdFormaPago == 3) { // 3 = Deposito
			$idBancoCliente = 1;
			$txtCuentaClientePago = "-";
			$idBancoCompania = $txtIdBancoCompania;
			$txtCuentaCompaniaPago = $txtCuentaCompaniaPago;
			$txtIdNumeroDctoPago = $txtNumeroDctoPago;
			$campo = "saldoDepositos";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = str_replace(",", "", $txtMonto);
			$txtMontoSaldoCaja = $txtMontoSaldo;
		} else if ($txtIdFormaPago == 4) { // 4 = Transferencia Bancaria
			$idTransferencia = $txtIdNumeroDctoPago;
			$idBancoCliente = $txtIdBancoCliente;
			$txtCuentaClientePago = "-";
			$idBancoCompania = $txtIdBancoCompania;
			$txtCuentaCompaniaPago = $txtCuentaCompaniaPago;
			$txtIdNumeroDctoPago = $txtNumeroDctoPago;
			$tipoTransferencia = "0";
			$campo = "saldoTransferencia";
			if ($idTransferencia > 0) { // NO SUMA 4 = Transferencia Bancaria EN EL SALDO DE LA CAJA
				$tomadoEnCierre = 2; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
				$txtMontoSaldo = 0;
				$txtMontoSaldoCaja = 0;
			} else {
				$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
				$txtMontoSaldo = str_replace(",", "", $txtMonto);
				$txtMontoSaldoCaja = $txtMontoSaldo;
			}
		} else if ($txtIdFormaPago == 5) { // 5 = Tarjeta de Crédito
			$idBancoCliente = $txtIdBancoCliente;
			$txtCuentaClientePago = "-";
			$idBancoCompania = $txtIdBancoCompania;
			$txtCuentaCompaniaPago = $txtCuentaCompaniaPago;
			$txtIdNumeroDctoPago = $txtNumeroDctoPago;
			$campo = "saldoTarjetaCredito";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = str_replace(",", "", $txtMonto);
			$txtMontoSaldoCaja = $txtMontoSaldo;
		} else if ($txtIdFormaPago == 6) { // 6 = Tarjeta de Debito
			$idBancoCliente = $txtIdBancoCliente;
			$txtCuentaClientePago = "-";
			$idBancoCompania = $txtIdBancoCompania;
			$txtCuentaCompaniaPago = $txtCuentaCompaniaPago;
			$txtIdNumeroDctoPago = $txtNumeroDctoPago;
			$campo = "saldoTarjetaDebito";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = str_replace(",", "", $txtMonto);
			$txtMontoSaldoCaja = $txtMontoSaldo;
		} else if ($txtIdFormaPago == 7) { // 7 = Anticipo
			$idBancoCliente = 1;
			$txtCuentaClientePago = "-";
			$idBancoCompania = 1;
			$txtCuentaCompaniaPago = "-";
			$txtIdNumeroDctoPago = $txtIdNumeroDctoPago;
			$campo = "saldoAnticipo";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = str_replace(",", "", $txtMonto);
			$txtMontoSaldoCaja = 0;
			
			// BUSCA LOS DATOS DEL ANTICIPO (0 = Anulado; 1 = Activo)
			$queryAnticipo = sprintf("SELECT * FROM cj_cc_anticipo cxc_ant
			WHERE cxc_ant.idAnticipo = %s
				AND cxc_ant.estatus = 1;",
				valTpDato($txtIdNumeroDctoPago, "int"));
			$rsAnticipo = mysql_query($queryAnticipo);
			if (!$rsAnticipo) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowAnticipo = mysql_fetch_array($rsAnticipo);
			
			// (0 = No Cancelado, 1 = Cancelado/No Asignado, 2 = Parcialmente Asignado, 3 = Asignado)
			$estatusPago = (in_array($rowAnticipo['estadoAnticipo'], array(0))) ? 2 : $estatusPago;
		} else if ($txtIdFormaPago == 8) { // 8 = Nota de Crédito
			$idBancoCliente = 1;
			$txtCuentaClientePago = "-";
			$idBancoCompania = 1;
			$txtCuentaCompaniaPago = "-";
			$txtIdNumeroDctoPago = $txtIdNumeroDctoPago;
			$campo = "saldoNotaCredito";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = str_replace(",", "", $txtMonto);
			$txtMontoSaldoCaja = $txtMontoSaldo;
		} else if ($txtIdFormaPago == 9) { // 9 = Retención
			$idBancoCliente = 1;
			$txtCuentaClientePago = "-";
			$idBancoCompania = 1;
			$txtCuentaCompaniaPago = "-";
			$txtIdNumeroDctoPago = $txtNumeroDctoPago;
			$campo = "saldoRetencion";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = str_replace(",", "", $txtMonto);
			$txtMontoSaldoCaja = $txtMontoSaldo;
		} else if ($txtIdFormaPago == 10) { // 10 = Retencion I.S.L.R.
			$idBancoCliente = 1;
			$txtCuentaClientePago = "-";
			$idBancoCompania = 1;
			$txtCuentaCompaniaPago = "-";
			$txtIdNumeroDctoPago = $txtNumeroDctoPago;
			$campo = "saldoRetencion";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = str_replace(",", "", $txtMonto);
			$txtMontoSaldoCaja = $txtMontoSaldo;
		} else if ($txtIdFormaPago == 11) { // 11 = Otro
		}
		
		// ACTUALIZA LOS SALDOS EN LA APERTURA (NO TOMA EN CUENTA 7 = Anticipo EN EL SALDO DE LA CAJA)
		$updateSQL = sprintf("UPDATE ".$apertCajaPpal." SET
			%s = %s + %s,
			saldoCaja = saldoCaja + %s
		WHERE id = %s;",
			$campo, $campo, valTpDato($txtMontoSaldo, "real_inglesa"),
			valTpDato($txtMontoSaldoCaja, "real_inglesa"),
			valTpDato($idApertura, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// INSERTA LOS PAGOS DEL DOCUMENTO
		$insertSQL = sprintf("INSERT INTO cj_det_nota_cargo (idNotaCargo, fechaPago, idFormaPago, numeroDocumento, bancoOrigen, numero_cuenta_cliente, bancoDestino, cuentaEmpresa, monto_pago, tipoCheque, id_cheque, tipo_transferencia, id_transferencia, tomadoEnComprobante, tomadoEnCierre, idCaja, id_apertura, estatus, id_empleado_creador, id_encabezado_nc)
		VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($this->idDocumento, "int"),
			valTpDato(date("Y-m-d", strtotime($fechaRegistroPago)), "date"),
			valTpDato($txtIdFormaPago, "int"),
			valTpDato($txtIdNumeroDctoPago, "text"),
			valTpDato($idBancoCliente, "int"),
			valTpDato($txtCuentaClientePago, "text"),
			valTpDato($idBancoCompania, "int"),
			valTpDato($txtCuentaCompaniaPago, "text"),
			valTpDato($txtMonto, "real_inglesa"),
			valTpDato($tipoCheque, "text"),
			valTpDato($idCheque, "int"),
			valTpDato($tipoTransferencia, "text"),
			valTpDato($idTransferencia, "int"),
			valTpDato(1, "int"),
			valTpDato($tomadoEnCierre, "int"), // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			valTpDato($idApertura, "int"),
			valTpDato($estatusPago, "int"), // Null = Anulado, 1 = Activo, 2 = Pendiente
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
			valTpDato($idEncabezadoPago, "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idPago = mysql_insert_id();
		
		$arrayIdDctoContabilidad[] = array(
			$idPago,
			$this->idModulo,
			"CAJAENTRADA");
		
		if ($txtIdFormaPago == 2) { // 2 = Cheque
			$Result1 = $this->actualizarCheque($idCheque);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
			
		} else if ($txtIdFormaPago == 3) { // 3 = Deposito
			$arrayPosiciones = explode("|",$hddObjDetalleDeposito);
			$arrayFormaPago = explode("|",$hddObjDetalleDepositoFormaPago);
			$arrayBanco = explode("|",$hddObjDetalleDepositoBanco);
			$arrayNroCuenta = explode("|",$hddObjDetalleDepositoNroCuenta);
			$arrayNroCheque = explode("|",$hddObjDetalleDepositoNroCheque);
			$arrayMonto = explode("|",$hddObjDetalleDepositoMonto);
			
			foreach($arrayPosiciones as $indiceDeposito => $valorDeposito) {
				if ($valorDeposito == $cbxPosicionPago) {
					if ($arrayFormaPago[$indiceDeposito] == 1) {
						$bancoDetalleDeposito = "";
						$nroCuentaDetalleDeposito = "";
						$nroChequeDetalleDeposito = "";
					} else {
						$bancoDetalleDeposito = $arrayBanco[$indiceDeposito];
						$nroCuentaDetalleDeposito = $arrayNroCuenta[$indiceDeposito];
						$nroChequeDetalleDeposito = $arrayNroCheque[$indiceDeposito];
					}
					
					$insertSQL = sprintf("INSERT INTO cj_det_pagos_deposito_nota_cargo (id_det_nota_cargo, fecha_deposito, idFormaPago, idBanco, numero_cuenta, numero_cheque, monto, id_tipo_documento, idCaja)
					VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)",
						valTpDato($idPago, "int"),
						valTpDato(date("Y-m-d", strtotime($txtFechaDeposito)), "date"),
						valTpDato($arrayFormaPago[$indiceDeposito], "int"),
						valTpDato($bancoDetalleDeposito, "int"),
						valTpDato($nroCuentaDetalleDeposito, "text"),
						valTpDato($nroChequeDetalleDeposito, "text"),
						valTpDato($arrayMonto[$indiceDeposito], "real_inglesa"),
						valTpDato(2, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
						valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$insertSQL); }
				}
			}
			
		} else if ($txtIdFormaPago == 4) { // 4 = Transferencia Bancaria
			$Result1 = $this->actualizarTransferencia($idTransferencia);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
			
		} else if (in_array($txtIdFormaPago, array(5,6))) { // 5 = Tarjeta de Crédito, 6 = Tarjeta de Debito
			$queryRetencionPunto = sprintf("SELECT id_retencion_punto FROM te_retencion_punto
			WHERE id_cuenta = %s
				AND id_tipo_tarjeta = %s;",
				valTpDato($txtIdCuentaCompaniaPago, "int"),
				valTpDato($txtTipoTarjeta, "int"));
			$rsRetencionPunto = mysql_query($queryRetencionPunto);
			if (!$rsRetencionPunto) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowRetencionPunto = mysql_fetch_array($rsRetencionPunto);
			
			$insertSQL = sprintf("INSERT INTO cj_cc_retencion_punto_pago (id_caja, id_pago, id_tipo_documento, id_retencion_punto)
			VALUES (%s, %s, %s, %s)",
				valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
				valTpDato($idPago, "int"),
				valTpDato(2, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
				valTpDato($rowRetencionPunto['id_retencion_punto'], "int"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$insertSQL); }
			
		} else if ($txtIdFormaPago == 7) { // 7 = Anticipo
			$idAnticipo = $txtIdNumeroDctoPago;
			
			$Result1 = $this->actualizarAnticipo($idAnticipo);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
			
		} else if ($txtIdFormaPago == 8) { // 8 = Nota de Crédito
			$idNotaCredito = $txtIdNumeroDctoPago;
			
			$Result1 = $this->actualizarNotaCredito($idNotaCredito);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
			
		} else if ($txtIdFormaPago == 9) { // 9 = Retención
			$queryFactura = sprintf("SELECT * FROM cj_cc_encabezadofactura WHERE idFactura = %s;",
				valTpDato($this->idDocumento, "int"));
			$rsFactura = mysql_query($queryFactura);
			if (!$rsFactura) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowFactura = mysql_fetch_array($rsFactura);
			
			$porcImpuesto = $rowFactura['porcentajeIvaFactura'] + $rowFactura['porcentajeIvaDeLujoFactura'];
			$subTotalImpuesto = $rowFactura['calculoIvaFactura'] + $rowFactura['calculoIvaDeLujoFactura'];
			$porcRetenido = ($subTotalImpuesto > 0) ? $txtMontoSaldo * 100 / $subTotalImpuesto : 0;
			
			// BUSCA SI YA EXISTE UN COMPROBANTE CON LA INFORMACION INGRESADA
			$queryRetencionCabecera = sprintf("SELECT * FROM cj_cc_retencioncabezera retencion
			WHERE retencion.numeroComprobante LIKE %s
				AND retencion.fechaComprobante = %s
				AND retencion.anoPeriodoFiscal = %s
				AND retencion.mesPeriodoFiscal = %s
				AND retencion.idCliente = %s;",
				valTpDato($txtNumeroDctoPago, "text"),
				valTpDato(date("Y-m-d", strtotime($fechaRegistroPago)), "date"),
				valTpDato(date("Y", strtotime($fechaRegistroPago)), "text"),
				valTpDato(date("m", strtotime($fechaRegistroPago)), "text"),
				valTpDato($this->idCliente, "int"));
			$rsRetencionCabecera = mysql_query($queryRetencionCabecera);
			if (!$rsRetencionCabecera) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsRetenciones = mysql_num_rows($rsRetencionCabecera);
			$rowRetencionCabecera = mysql_fetch_array($rsRetencionCabecera);
			
			if ($totalRowsRetenciones > 0) {
				$idRetencionCabecera = $rowRetencionCabecera['idRetencionCabezera'];
			} else {
				$insertSQL = sprintf("INSERT INTO cj_cc_retencioncabezera (numeroComprobante, fechaComprobante, anoPeriodoFiscal, mesPeriodoFiscal, idCliente, idRegistrosUnidadesFisicas)
				VALUES (%s, %s, %s, %s, %s, %s)",
					valTpDato($txtNumeroDctoPago, "text"),
					valTpDato(date("Y-m-d", strtotime($fechaRegistroPago)), "date"),
					valTpDato(date("Y", strtotime($fechaRegistroPago)), "text"),
					valTpDato(date("m", strtotime($fechaRegistroPago)), "text"),
					valTpDato($this->idCliente, "int"),
					valTpDato(0, "int"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idRetencionCabecera = mysql_insert_id();
			}
			
			$insertSQL = sprintf("INSERT INTO cj_cc_retenciondetalle (idRetencionCabezera, fechaFactura, idFactura, numeroControlFactura, numeroNotaDebito, numeroNotaCredito, tipoDeTransaccion, numeroFacturaAfectada, totalCompraIncluyendoIva, comprasSinIva, baseImponible, porcentajeAlicuota, impuestoIva, IvaRetenido, porcentajeRetencion)
			VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
				valTpDato($idRetencionCabecera, "int"),
				valTpDato($rowFactura['fechaRegistroFactura'], "date"),
				valTpDato($this->idDocumento, "int"),
				valTpDato($rowFactura['numeroControl'], "text"),
				valTpDato(" ", "text"),
				valTpDato(" ", "text"),
				valTpDato(" ", "text"),
				valTpDato(" ", "text"),
				valTpDato($rowFactura['montoTotalFactura'], "real_inglesa"),
				valTpDato($rowFactura['subtotalFactura'], "real_inglesa"),
				valTpDato($rowFactura['baseImponible'], "real_inglesa"),
				valTpDato($porcImpuesto, "real_inglesa"),
				valTpDato($subTotalImpuesto, "real_inglesa"),
				valTpDato($txtMontoSaldo, "real_inglesa"),
				valTpDato($porcRetenido, "int"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
		} else if ($txtIdFormaPago == 10) { // 10 = Retencion I.S.L.R.
			
		} else if ($txtIdFormaPago == 11) { // 11 = Otro
			
		}
		
		return array(
			0 => true,
			"idPago" => $idPago);
	}
	
	function guardarPagoCxCAN($idCajaPpal, $apertCajaPpal, $idApertura, $fechaRegistroPago, $idReporteImpresion, $cbxPosicionPago, $hddIdPago, $txtIdFormaPago, $txtIdConceptoPago, $txtIdNumeroDctoPago, $txtNumeroDctoPago, $txtIdBancoCliente, $txtCuentaClientePago, $txtIdBancoCompania, $txtIdCuentaCompaniaPago, $txtCuentaCompaniaPago, $txtFechaDeposito, $txtTipoTarjeta, $hddObjDetalleDeposito, $hddObjDetalleDepositoFormaPago, $hddObjDetalleDepositoBanco, $hddObjDetalleDepositoNroCuenta, $hddObjDetalleDepositoNroCheque, $hddObjDetalleDepositoMonto, $txtMonto) {
		
		$idCheque = "";
		$tipoCheque = "-";
		$idTransferencia = "";
		$tipoTransferencia = "-";
		$idConcepto = "";
		$estatusPago = 1;
		if ($txtIdFormaPago == 1) { // 1 = Efectivo
			$idBancoCliente = 1;
			$txtCuentaClientePago = "-";
			$idBancoCompania = 1;
			$txtCuentaCompaniaPago = "-";
			$txtIdNumeroDctoPago = "-";
			$campo = "saldoEfectivo";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = str_replace(",", "", $txtMonto);
			$txtMontoSaldoCaja = $txtMontoSaldo;
		} else if ($txtIdFormaPago == 2) { // 2 = Cheque
			$idCheque = $txtIdNumeroDctoPago;
			$idBancoCliente = $txtIdBancoCliente;
			$txtCuentaClientePago = $txtCuentaClientePago;
			$idBancoCompania = 1;
			$txtCuentaCompaniaPago = "-";
			$txtIdNumeroDctoPago = $txtNumeroDctoPago;
			$tipoCheque = "0";
			$campo = "saldoCheques";
			if ($idCheque > 0) { // NO SUMA 2 = Cheque EN EL SALDO DE LA CAJA
				$tomadoEnCierre = 2; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
				$txtMontoSaldo = 0;
				$txtMontoSaldoCaja = 0;
			} else {
				$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
				$txtMontoSaldo = str_replace(",", "", $txtMonto);
				$txtMontoSaldoCaja = $txtMontoSaldo;
			}
		} else if ($txtIdFormaPago == 3) { // 3 = Deposito
			$idBancoCliente = 1;
			$txtCuentaClientePago = "-";
			$idBancoCompania = $txtIdBancoCompania;
			$txtCuentaCompaniaPago = $txtCuentaCompaniaPago;
			$txtIdNumeroDctoPago = $txtNumeroDctoPago;
			$campo = "saldoDepositos";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = str_replace(",", "", $txtMonto);
			$txtMontoSaldoCaja = $txtMontoSaldo;
		} else if ($txtIdFormaPago == 4) { // 4 = Transferencia Bancaria
			$idTransferencia = $txtIdNumeroDctoPago;
			$idBancoCliente = $txtIdBancoCliente;
			$txtCuentaClientePago = "-";
			$idBancoCompania = $txtIdBancoCompania;
			$txtCuentaCompaniaPago = $txtCuentaCompaniaPago;
			$txtIdNumeroDctoPago = $txtNumeroDctoPago;
			$tipoTransferencia = "0";
			$campo = "saldoTransferencia";
			if ($idTransferencia > 0) { // NO SUMA 4 = Transferencia Bancaria EN EL SALDO DE LA CAJA
				$tomadoEnCierre = 2; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
				$txtMontoSaldo = 0;
				$txtMontoSaldoCaja = 0;
			} else {
				$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
				$txtMontoSaldo = str_replace(",", "", $txtMonto);
				$txtMontoSaldoCaja = $txtMontoSaldo;
			}
		} else if ($txtIdFormaPago == 5) { // 5 = Tarjeta de Crédito
			$idBancoCliente = $txtIdBancoCliente;
			$txtCuentaClientePago = "-";
			$idBancoCompania = $txtIdBancoCompania;
			$txtCuentaCompaniaPago = $txtCuentaCompaniaPago;
			$txtIdNumeroDctoPago = $txtNumeroDctoPago;
			$campo = "saldoTarjetaCredito";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = str_replace(",", "", $txtMonto);
			$txtMontoSaldoCaja = $txtMontoSaldo;
		} else if ($txtIdFormaPago == 6) { // 6 = Tarjeta de Debito
			$idBancoCliente = $txtIdBancoCliente;
			$txtCuentaClientePago = "-";
			$idBancoCompania = $txtIdBancoCompania;
			$txtCuentaCompaniaPago = $txtCuentaCompaniaPago;
			$txtIdNumeroDctoPago = $txtNumeroDctoPago;
			$campo = "saldoTarjetaDebito";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = str_replace(",", "", $txtMonto);
			$txtMontoSaldoCaja = $txtMontoSaldo;
		} else if ($txtIdFormaPago == 8) { // 8 = Nota de Crédito
			$idBancoCliente = 1;
			$txtCuentaClientePago = "-";
			$idBancoCompania = 1;
			$txtCuentaCompaniaPago = "-";
			$txtIdNumeroDctoPago = $txtIdNumeroDctoPago;
			$campo = "saldoNotaCredito";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = str_replace(",", "", $txtMonto);
			$txtMontoSaldoCaja = $txtMontoSaldo;
		} else if ($txtIdFormaPago == 9) { // 9 = Retención
			$idBancoCliente = 1;
			$txtCuentaClientePago = "-";
			$idBancoCompania = 1;
			$txtCuentaCompaniaPago = "-";
			$txtIdNumeroDctoPago = $txtNumeroDctoPago;
			$campo = "saldoRetencion";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = str_replace(",", "", $txtMonto);
			$txtMontoSaldoCaja = $txtMontoSaldo;
		} else if ($txtIdFormaPago == 10) { // 10 = Retencion I.S.L.R.
			$idBancoCliente = 1;
			$txtCuentaClientePago = "-";
			$idBancoCompania = 1;
			$txtCuentaCompaniaPago = "-";
			$txtIdNumeroDctoPago = $txtNumeroDctoPago;
			$campo = "saldoRetencion";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = str_replace(",", "", $txtMonto);
			$txtMontoSaldoCaja = $txtMontoSaldo;
		} else if ($txtIdFormaPago == 11) { // 11 = Otro
			$idConcepto = $txtIdConceptoPago;
			$idBancoCliente = 1;
			$txtCuentaClientePago = "-";
			$idBancoCompania = 1;
			$txtCuentaCompaniaPago = "-";
			$txtIdNumeroDctoPago = "-";
			$campo = "saldoOtro";
			$tomadoEnCierre = 2; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMontoSaldo = $txtMonto;
			// 6 = Bono Suplidor, 7 = PND Seguro, 8 = PND Garantia Extendida, 9 = PND GAP
			$txtMontoSaldoCaja = (in_array($idConcepto, array(7,8,9))) ? 0 : $txtMonto;
		}
		
		// ACTUALIZA LOS SALDOS EN LA APERTURA (NO TOMA EN CUENTA 7 = Anticipo EN EL SALDO DE LA CAJA)
		$updateSQL = sprintf("UPDATE ".$apertCajaPpal." SET
			%s = %s + %s,
			saldoCaja = saldoCaja + %s
		WHERE id = %s;",
			$campo, $campo, valTpDato($txtMontoSaldo, "real_inglesa"),
			valTpDato($txtMontoSaldoCaja, "real_inglesa"),
			valTpDato($idApertura, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// INSERTA LOS PAGOS DEL DOCUMENTO
		$insertSQL = sprintf("INSERT INTO cj_cc_detalleanticipo (idAnticipo, fechaPagoAnticipo, tipoPagoDetalleAnticipo, id_forma_pago, id_concepto, numeroControlDetalleAnticipo, bancoClienteDetalleAnticipo, numeroCuentaCliente, bancoCompaniaDetalleAnticipo, numeroCuentaCompania, montoDetalleAnticipo, id_cheque, tipo_transferencia, id_transferencia, tomadoEnCierre, idCaja, id_apertura, estatus, id_empleado_creador, id_reporte_impresion)
		VALUES(%s, %s, (SELECT aliasFormaPago FROM formapagos WHERE idFormaPago = %s), %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($this->idDocumento, "int"),
			valTpDato(date("Y-m-d", strtotime($fechaRegistroPago)), "date"),
			valTpDato($txtIdFormaPago, "text"),
			valTpDato($txtIdFormaPago, "int"),
			valTpDato($idConcepto, "int"),
			valTpDato($txtIdNumeroDctoPago, "text"),
			valTpDato($idBancoCliente, "int"),
			valTpDato($txtCuentaClientePago, "text"),
			valTpDato($idBancoCompania, "int"),
			valTpDato($txtCuentaCompaniaPago, "text"),
			valTpDato($txtMonto, "real_inglesa"),
			valTpDato($idCheque, "int"),
			valTpDato($tipoTransferencia, "text"),
			valTpDato($idTransferencia, "int"),
			valTpDato($tomadoEnCierre, "int"), // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			valTpDato($idApertura, "int"),
			valTpDato($estatusPago, "int"), // Null = Anulado, 1 = Activo, 2 = Pendiente
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
			valTpDato($idReporteImpresion, "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idPago = mysql_insert_id();
		
		$arrayIdDctoContabilidad[] = array(
			$idPago,
			$this->idModulo,
			"CAJAENTRADA");
		
		if ($txtIdFormaPago == 2) { // 2 = Cheque
			$Result1 = $this->actualizarCheque($idCheque);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
			
		} else if ($txtIdFormaPago == 3) { // 3 = Deposito
			$arrayPosiciones = explode("|",$hddObjDetalleDeposito);
			$arrayFormaPago = explode("|",$hddObjDetalleDepositoFormaPago);
			$arrayBanco = explode("|",$hddObjDetalleDepositoBanco);
			$arrayNroCuenta = explode("|",$hddObjDetalleDepositoNroCuenta);
			$arrayNroCheque = explode("|",$hddObjDetalleDepositoNroCheque);
			$arrayMonto = explode("|",$hddObjDetalleDepositoMonto);
			
			foreach($arrayPosiciones as $indiceDeposito => $valorDeposito) {
				if ($valorDeposito == $cbxPosicionPago) {
					if ($arrayFormaPago[$indiceDeposito] == 1) {
						$bancoDetalleDeposito = "";
						$nroCuentaDetalleDeposito = "";
						$nroChequeDetalleDeposito = "";
					} else {
						$bancoDetalleDeposito = $arrayBanco[$indiceDeposito];
						$nroCuentaDetalleDeposito = $arrayNroCuenta[$indiceDeposito];
						$nroChequeDetalleDeposito = $arrayNroCheque[$indiceDeposito];
					}
					
					$insertSQL = sprintf("INSERT INTO cj_cc_det_pagos_deposito_anticipos (idDetalleAnticipo, fecha_deposito, idFormaPago, idBanco, numero_cuenta, numero_cheque, monto, id_tipo_documento, idCaja)
					VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)",
						valTpDato($idPago, "int"),
						valTpDato(date("Y-m-d", strtotime($txtFechaDeposito)), "date"),
						valTpDato($arrayFormaPago[$indiceDeposito], "int"),
						valTpDato($bancoDetalleDeposito, "int"),
						valTpDato($nroCuentaDetalleDeposito, "text"),
						valTpDato($nroChequeDetalleDeposito, "text"),
						valTpDato($arrayMonto[$indiceDeposito], "real_inglesa"),
						valTpDato(4, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
						valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$insertSQL); }
				}
			}
			
		} else if ($txtIdFormaPago == 4) { // 4 = Transferencia Bancaria
			$Result1 = $this->actualizarTransferencia($idTransferencia);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
			
		} else if (in_array($txtIdFormaPago, array(5,6))) { // 5 = Tarjeta de Crédito, 6 = Tarjeta de Debito
			$queryRetencionPunto = sprintf("SELECT id_retencion_punto FROM te_retencion_punto
			WHERE id_cuenta = %s
				AND id_tipo_tarjeta = %s;",
				valTpDato($txtIdCuentaCompaniaPago, "int"),
				valTpDato($txtTipoTarjeta, "int"));
			$rsRetencionPunto = mysql_query($queryRetencionPunto);
			if (!$rsRetencionPunto) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowRetencionPunto = mysql_fetch_array($rsRetencionPunto);
			
			$insertSQL = sprintf("INSERT INTO cj_cc_retencion_punto_pago (id_caja, id_pago, id_tipo_documento, id_retencion_punto)
			VALUES (%s, %s, %s, %s)",
				valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
				valTpDato($idPago, "int"),
				valTpDato(4, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
				valTpDato($rowRetencionPunto['id_retencion_punto'], "int"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$insertSQL); }
			
		} else if ($txtIdFormaPago == 7) { // 7 = Anticipo
			$idAnticipo = $txtIdNumeroDctoPago;
			
			$Result1 = $this->actualizarAnticipo($idAnticipo);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
			
		} else if ($txtIdFormaPago == 8) { // 8 = Nota de Crédito
			$idNotaCredito = $txtIdNumeroDctoPago;
			
			$Result1 = $this->actualizarNotaCredito($idNotaCredito);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
			
		} else if ($txtIdFormaPago == 9) { // 9 = Retención
			$queryFactura = sprintf("SELECT * FROM cj_cc_encabezadofactura WHERE idFactura = %s;",
				valTpDato($this->idDocumento, "int"));
			$rsFactura = mysql_query($queryFactura);
			if (!$rsFactura) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowFactura = mysql_fetch_array($rsFactura);
			
			$porcImpuesto = $rowFactura['porcentajeIvaFactura'] + $rowFactura['porcentajeIvaDeLujoFactura'];
			$subTotalImpuesto = $rowFactura['calculoIvaFactura'] + $rowFactura['calculoIvaDeLujoFactura'];
			$porcRetenido = ($subTotalImpuesto > 0) ? $txtMontoSaldo * 100 / $subTotalImpuesto : 0;
			
			// BUSCA SI YA EXISTE UN COMPROBANTE CON LA INFORMACION INGRESADA
			$queryRetencionCabecera = sprintf("SELECT * FROM cj_cc_retencioncabezera retencion
			WHERE retencion.numeroComprobante LIKE %s
				AND retencion.fechaComprobante = %s
				AND retencion.anoPeriodoFiscal = %s
				AND retencion.mesPeriodoFiscal = %s
				AND retencion.idCliente = %s;",
				valTpDato($txtNumeroDctoPago, "text"),
				valTpDato(date("Y-m-d", strtotime($fechaRegistroPago)), "date"),
				valTpDato(date("Y", strtotime($fechaRegistroPago)), "text"),
				valTpDato(date("m", strtotime($fechaRegistroPago)), "text"),
				valTpDato($this->idCliente, "int"));
			$rsRetencionCabecera = mysql_query($queryRetencionCabecera);
			if (!$rsRetencionCabecera) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsRetenciones = mysql_num_rows($rsRetencionCabecera);
			$rowRetencionCabecera = mysql_fetch_array($rsRetencionCabecera);
			
			if ($totalRowsRetenciones > 0) {
				$idRetencionCabecera = $rowRetencionCabecera['idRetencionCabezera'];
			} else {
				$insertSQL = sprintf("INSERT INTO cj_cc_retencioncabezera (numeroComprobante, fechaComprobante, anoPeriodoFiscal, mesPeriodoFiscal, idCliente, idRegistrosUnidadesFisicas)
				VALUES (%s, %s, %s, %s, %s, %s)",
					valTpDato($txtNumeroDctoPago, "text"),
					valTpDato(date("Y-m-d", strtotime($fechaRegistroPago)), "date"),
					valTpDato(date("Y", strtotime($fechaRegistroPago)), "text"),
					valTpDato(date("m", strtotime($fechaRegistroPago)), "text"),
					valTpDato($this->idCliente, "int"),
					valTpDato(0, "int"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idRetencionCabecera = mysql_insert_id();
			}
			
			$insertSQL = sprintf("INSERT INTO cj_cc_retenciondetalle (idRetencionCabezera, fechaFactura, idFactura, numeroControlFactura, numeroNotaDebito, numeroNotaCredito, tipoDeTransaccion, numeroFacturaAfectada, totalCompraIncluyendoIva, comprasSinIva, baseImponible, porcentajeAlicuota, impuestoIva, IvaRetenido, porcentajeRetencion)
			VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
				valTpDato($idRetencionCabecera, "int"),
				valTpDato($rowFactura['fechaRegistroFactura'], "date"),
				valTpDato($this->idDocumento, "int"),
				valTpDato($rowFactura['numeroControl'], "text"),
				valTpDato(" ", "text"),
				valTpDato(" ", "text"),
				valTpDato(" ", "text"),
				valTpDato(" ", "text"),
				valTpDato($rowFactura['montoTotalFactura'], "real_inglesa"),
				valTpDato($rowFactura['subtotalFactura'], "real_inglesa"),
				valTpDato($rowFactura['baseImponible'], "real_inglesa"),
				valTpDato($porcImpuesto, "real_inglesa"),
				valTpDato($subTotalImpuesto, "real_inglesa"),
				valTpDato($txtMontoSaldo, "real_inglesa"),
				valTpDato($porcRetenido, "int"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
		} else if ($txtIdFormaPago == 10) { // 10 = Retencion I.S.L.R.
			
		} else if ($txtIdFormaPago == 11) { // 11 = Otro
			// INSERTA EL CONCEPTO DE PAGO PARA HISTORICO
			$insertSQL = sprintf("INSERT INTO cj_cc_anticipo_concepto (id_anticipo, numero_anticipo, idCliente, fecha_registro, caja, id_usuario, monto_total_anticipo, id_empresa, observacion, id_concepto)
			VALUES (%s, (SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = %s), %s, %s, %s, %s, %s, %s, %s, %s)",
				valTpDato($this->idDocumento, "int"),
				valTpDato($this->idDocumento, "int"),
				valTpDato($this->idCliente, "int"),
				valTpDato("NOW()", "campo"),
				valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
				valTpDato($_SESSION['idUsuarioSysGts'], "int"),
				valTpDato($txtMonto, "real_inglesa"),
				valTpDato($this->idEmpresa, "int"),
				valTpDato("Anticipo Por Concepto / Vehiculos", "text"),
				valTpDato($idConcepto, "int"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
		
		return array(
			0 => true,
			"idPago" => $idPago);
	}
}

function actualizarEstatusSistemaSolicitud($numReferencia, $estatusPedidoVenta) {
	global $conex;
	
	$arrayRef = explode("-", $numReferencia);
	if ($arrayRef[0] == "SOL") {
		// ESTATUS DE ERP VS SOLICITUDES
		$arrayEstatus = array(
			NULL 	=> 1, 	// -						Vs	Proceso
			NULL 	=> 2,	// -						Vs	Cerrado
			0 		=> 3,	// Pendiente por Terminar	Vs	Espera Confirmacion
			1 		=> 4,	// Convertido a Pedido		Vs	Autorizado
			2 		=> 5,	// Pedido Aprobado			Vs	Confirmado
			3 		=> 6,	// Facturado				Vs	Facturado
			NULL 	=> 7,	// -						Vs	Despachado
			5 		=> 8);	// Anulado					Vs	Anulado
		
		//mysql_query("USE ".DBASE_SIGSO.";");
		
		$updateSQL = sprintf("UPDATE ".DBASE_SIGSO.".encabezado_pedido SET
			total_inicial = (SELECT SUM(det_ped.precio_cantidad) FROM ".DBASE_SIGSO.".detalle_pedido det_ped
							WHERE det_ped.id_pedido = encabezado_pedido.id_pedido),
			total_con_iva = (SELECT SUM(det_ped.precio_cantidad_iva) FROM ".DBASE_SIGSO.".detalle_pedido det_ped
							WHERE det_ped.id_pedido = encabezado_pedido.id_pedido),
			estatus = %s
		WHERE id_pedido = %s;",
			valTpDato($arrayEstatus[$estatusPedidoVenta], "int"),
			valTpDato($arrayRef[1], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		mysql_query("SET NAMES 'latin1';");
		
		return array(true, "La Solicitud ".$arrayRef[1]." Creada por el Sistema de Solicitudes ha sido Actualizado Correctamente");
		
		//mysql_query("USE ".DBASE_SIPRE_AUT.";");
	}
	
	return array(NULL, "");
}

function actualizarCantidadSistemaSolicitud($numReferencia, $idArticulo, $idArticuloCosto, $cantPendiente, $precioUnitario = 0, $gastoUnitario = 0, $porcIva = 0) {
	global $conex;
	
	$arrayRef = explode("-", $numReferencia);
	if ($arrayRef[0] == "SOL") {
		//mysql_query("USE ".DBASE_SIGSO.";");
		
		$cantPendiente = str_replace(",","",$cantPendiente);
		$precioUnitario = str_replace(",","",$precioUnitario);
		$gastoUnitario = str_replace(",","",$gastoUnitario);
		$porcIva = str_replace(",","",$porcIva);
		
		// BUSCAR PEDIDO DEPENDIENDO DEL NUMERO DE REFERENCIA
		$queryPedido = sprintf("SELECT * FROM ".DBASE_SIGSO.".encabezado_pedido
		WHERE id_pedido = %s;",
			valTpDato($arrayRef[1], "int"));
		$rsPedido = mysql_query($queryPedido);
		if (!$rsPedido) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$rowPedido = mysql_fetch_assoc($rsPedido);
		
		$idSolicitudPedido = $rowPedido['id_pedido'];
		
		// BUSCA EL ARTICULO EN EL PEDIDO
		$queryPedidoDet = sprintf("SELECT * FROM ".DBASE_SIGSO.".detalle_pedido
		WHERE id_pedido = %s
			AND id_articulo = %s;",
			valTpDato($idSolicitudPedido, "int"),
			valTpDato($idArticulo, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		while($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
			// SI EL RESULTADO TIENE UN LOTE ASIGNADO Y ES DISTINTO AL PASADO POR REFERENCIA CREA UNO NUEVO
			if ($rowPedidoDet['id_articulo_costo'] > 0 && $rowPedidoDet['id_articulo_costo'] != $idArticuloCosto) {
				// VERIFICA QUE NO EXISTA ALGUNO CON EL NUMERO DE LOTE ASIGNADO POR REFERENCIA
				$queryPedidoDet2 = sprintf("SELECT * FROM ".DBASE_SIGSO.".detalle_pedido
				WHERE id_pedido = %s
					AND id_articulo = %s
					AND id_articulo_costo = %s;",
					valTpDato($idSolicitudPedido, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($idArticuloCosto, "int"));
				$rsPedidoDet2 = mysql_query($queryPedidoDet2);
				if (!$rsPedidoDet2) return array(false , mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
				$totalRowsPedidoDet2 = mysql_num_rows($rsPedidoDet2);
				
				if (!($totalRowsPedidoDet2 > 0)) {
					$insertSQL = sprintf("INSERT INTO ".DBASE_SIGSO.".detalle_pedido (id_pedido, id_articulo, id_articulo_costo, cantidad, cantidad_despachada, precio_unitario, precio_cantidad, precio_cantidad_iva)
					VALUES (%s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idSolicitudPedido, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($idArticuloCosto, "int"),
						valTpDato($cantPendiente, "real_inglesa"),
						valTpDato($cantPendiente, "real_inglesa"),
						valTpDato($precioUnitario, "real_inglesa"),
						valTpDato(($cantPendiente * $precioUnitario), "real_inglesa"),
						valTpDato(($cantPendiente * ($precioUnitario + ($precioUnitario * $porcIva / 100))), "real_inglesa"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
					mysql_query("SET NAMES 'latin1';");
				}
			} else {
				$updateSQL = sprintf("UPDATE ".DBASE_SIGSO.".detalle_pedido SET 
					id_articulo_costo = %s,
					cantidad_despachada = %s
				WHERE id_detalle_pedido = %s;",
					valTpDato($idArticuloCosto, "int"),
					valTpDato($cantPendiente, "real_inglesa"),
					valTpDato($rowPedidoDet['id_detalle_pedido'], "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
				mysql_query("SET NAMES 'latin1';");
			}
		}
		
		if ($precioUnitario > 0) {
			// ACTUALIZA EL PRECIO DEL ARTICULO
			$updateSQL = sprintf("UPDATE ".DBASE_SIGSO.".detalle_pedido SET 
				precio_unitario = %s,
				precio_cantidad = cantidad_despachada * precio_unitario,
				precio_cantidad_iva = cantidad_despachada * (precio_unitario + (precio_unitario * %s / 100))
			WHERE id_pedido = %s
				AND id_articulo = %s
				AND id_articulo_costo = %s;",
				valTpDato(($precioUnitario + $gastoUnitario), "real_inglesa"),
				valTpDato($porcIva, "real_inglesa"),
				valTpDato($idSolicitudPedido, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idArticuloCosto, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
			mysql_query("SET NAMES 'latin1';");
		}
		
		// ACTUALIZA EL PRECIO DE LOS ARTICULOS QUE NO TIENEN CANTIDADES DESPACHADAS
		$updateSQL = sprintf("UPDATE ".DBASE_SIGSO.".detalle_pedido SET
			precio_cantidad = cantidad_despachada * precio_unitario,
			precio_cantidad_iva = cantidad_despachada * (precio_unitario + (precio_unitario * %s / 100))
		WHERE id_pedido = %s
			AND cantidad_despachada = 0;",
			valTpDato($porcIva, "real_inglesa"),
			valTpDato($idSolicitudPedido, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		mysql_query("SET NAMES 'latin1';");
		
		// ACTUALIZA EL TOTAL DEL PEDIDO
		$updateSQL = sprintf("UPDATE ".DBASE_SIGSO.".encabezado_pedido SET 
			total_inicial = (SELECT SUM(ped_det.precio_cantidad) FROM ".DBASE_SIGSO.".detalle_pedido ped_det
							WHERE ped_det.id_pedido = encabezado_pedido.id_pedido),
			total_con_iva = (SELECT SUM(ped_det.precio_cantidad_iva) FROM ".DBASE_SIGSO.".detalle_pedido ped_det
							WHERE ped_det.id_pedido = encabezado_pedido.id_pedido)
		WHERE id_pedido = %s;",
			valTpDato($idSolicitudPedido, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		mysql_query("SET NAMES 'latin1';");
		
		return array(true, "");
		
		//mysql_query("USE ".DBASE_SIPRE_AUT.";");
	}
	
	return array(NULL, "");
}

function calculoPorcentaje($frmTotalDcto, $tpCalculo, $valor, $objDestino) {
	$objResponse = new xajaxResponse();
	
	$subTotal = str_replace(",","",$frmTotalDcto['txtSubTotal']);
	$valor = str_replace(",","",$valor);
	
	if ($subTotal > 0) {
		$monto = ($tpCalculo == "Porc") ? $valor * ($subTotal / 100) : $valor * (100 / $subTotal);
	} else {
		$monto = 0;
	}
	
	$objResponse->assign($objDestino,"value",number_format($monto, 2, ".", ","));
		
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

if (!function_exists("cargaLstEmpresaFinal")) {
	function cargaLstEmpresaFinal($selId = "", $accion = "onchange=\"xajax_objetoCodigoDinamico('tdCodigoArt',this.value); byId('btnBuscar').click();\"", $nombreObj = "lstEmpresa") {
		$objResponse = new xajaxResponse();
		
		// EMPRESAS PRINCIPALES
		$queryUsuarioSuc = sprintf("SELECT DISTINCT
			id_empresa_reg,
			nombre_empresa
		FROM vw_iv_usuario_empresa
		WHERE id_usuario = %s
			AND id_empresa_padre_suc IS NULL
		ORDER BY nombre_empresa_suc ASC",
			valTpDato($_SESSION['idUsuarioSysGts'], "int"));
		$rsUsuarioSuc = mysql_query($queryUsuarioSuc);
		if (!$rsUsuarioSuc) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		while ($rowUsuarioSuc = mysql_fetch_assoc($rsUsuarioSuc)) {
			$selected = ($selId == $rowUsuarioSuc['id_empresa_reg']) ? "selected=\"selected\"" : "";
		
			$htmlOption .= "<option ".$selected." value=\"".$rowUsuarioSuc['id_empresa_reg']."\">".utf8_encode($rowUsuarioSuc['id_empresa_reg'].".- ".$rowUsuarioSuc['nombre_empresa'])."</option>";	
		}
		
		// EMPRESAS CON SUCURSALES
		$query = sprintf("SELECT DISTINCT
			id_empresa,
			nombre_empresa
		FROM vw_iv_usuario_empresa
		WHERE id_usuario = %s
			AND id_empresa_padre_suc IS NOT NULL
		ORDER BY nombre_empresa",
			valTpDato($_SESSION['idUsuarioSysGts'], "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		while ($row = mysql_fetch_assoc($rs)) {
			$htmlOption .= "<optgroup label=\"".$row['nombre_empresa']."\">";
			
			$queryUsuarioSuc = sprintf("SELECT DISTINCT
				id_empresa_reg,
				nombre_empresa_suc,
				sucursal
			FROM vw_iv_usuario_empresa
			WHERE id_usuario = %s
				AND id_empresa_padre_suc = %s
			ORDER BY nombre_empresa_suc ASC",
				valTpDato($_SESSION['idUsuarioSysGts'], "int"),
				valTpDato($row['id_empresa'], "int"));
			$rsUsuarioSuc = mysql_query($queryUsuarioSuc);
			if (!$rsUsuarioSuc) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
			while ($rowUsuarioSuc = mysql_fetch_assoc($rsUsuarioSuc)) {
				$selected = ($selId == $rowUsuarioSuc['id_empresa_reg']) ? "selected=\"selected\"" : "";
			
				$htmlOption .= "<option ".$selected." value=\"".$rowUsuarioSuc['id_empresa_reg']."\">".utf8_encode($rowUsuarioSuc['id_empresa_reg'].".- ".$rowUsuarioSuc['nombre_empresa_suc'])."</option>";	
			}
		
			$htmlOption .= "</optgroup>";
		}
		
		$html = "<select id=\"".$nombreObj."\" name=\"".$nombreObj."\" class=\"inputHabilitado\" ".$accion." style=\"width:200px\">";
			$html .= "<option value=\"\">[ Todos ]</option>";
			$html .= $htmlOption;
		$html .= "</select>";
		
		$objResponse->assign("td".$nombreObj,"innerHTML",$html);
		
		return $objResponse;
	}
}

function controlSession($modo){
	$objResponse = new xajaxResponse();

	if ($modo == 1) {
		session_start();
	} else {
		session_destroy();
		$objResponse->alert("Su sesión ha sido cerrada.");
	}
	
	$objResponse->script("window.close();");

	return $objResponse;
}

function asignarEmpresaUsuario($idEmpresa, $objDestino, $nomVentana, $scriptCalculo = "", $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$queryEmpresa = sprintf("SELECT
		id_empresa_reg,
		IF (vw_iv_usu_emp.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_usu_emp.nombre_empresa, vw_iv_usu_emp.nombre_empresa_suc), vw_iv_usu_emp.nombre_empresa) AS nombre_empresa
	FROM vw_iv_usuario_empresa vw_iv_usu_emp
	WHERE id_empresa_reg = %s",
		valTpDato($idEmpresa, "text"));
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
	$objResponse->assign("txtId".$objDestino,"value",$rowEmpresa['id_empresa_reg']);
	$objResponse->assign("txt".$objDestino,"value",utf8_encode($rowEmpresa['nombre_empresa']));
	
	$objResponse->loadCommands(objetoCodigoDinamico("tdCodigoArt",$rowEmpresa['id_empresa_reg']));
	
	// VERIFICA VALORES DE CONFIGURACION (Días de Vencimiento del Presupuesto)
	$queryConfig8 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 8
		AND config_emp.status = 1
		AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig8 = mysql_query($queryConfig8);
	if (!$rsConfig8) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowConfig8 = mysql_fetch_assoc($rsConfig8);
	
	$objResponse->assign("txtFechaVencimientoPresupuesto","value",date(spanDateFormat,dateAddLab(time(),$rowConfig8['valor'],true)));
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("
		if (byId('imgCerrarDivFlotante2') != undefined) {
			byId('imgCerrarDivFlotante2').click();
		} else if (byId('imgCerrarDivFlotante1') != undefined) {
			byId('imgCerrarDivFlotante1').click();
		} else if (byId('btnCancelar".$nomVentana."') != undefined) {
			byId('btnCancelar".$nomVentana."').click();
		}");
	}
	
	if (strlen($scriptCalculo) > 0) {
		$objResponse->script($scriptCalculo);
	}
	
	return $objResponse;
}

function listadoEmpresasUsuario($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $spanRIF;
	global $raiz;
	
	$objDestino = $valCadBusq[0];
	$nomVentana = $valCadBusq[1];
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq = $cond.sprintf("id_usuario = %s",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"));
	
	if (strlen($valCadBusq[2]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre_empresa LIKE %s
		OR nombre_empresa_suc LIKE %s)",
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM vw_iv_usuario_empresa %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoEmpresasUsuario", "8%", $pageNum, "id_empresa_reg", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listadoEmpresasUsuario", "20%", $pageNum, "rif", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanRIF));
		$htmlTh .= ordenarCampo("xajax_listadoEmpresasUsuario", "36%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, ("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listadoEmpresasUsuario", "36%", $pageNum, "nombre_empresa_suc", $campOrd, $tpOrd, $valBusq, $maxRows, ("Sucursal"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$nombreSucursal = ($row['id_empresa_padre_suc'] > 0) ? $row['nombre_empresa_suc']." (".$row['sucursal'].")" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarEmpresaUsuario('".$row['id_empresa_reg']."','".$objDestino."','".$nomVentana."');\" title=\"Seleccionar\"><img src=\"".$raiz."img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_empresa_reg']."</td>";
			$htmlTb .= "<td align=\"right\">".htmlentities($row['rif'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".htmlentities($nombreSucursal)."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"50\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresasUsuario(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresasUsuario(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoEmpresasUsuario(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf .= "<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf .= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresasUsuario(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresasUsuario(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("div".$nomVentana,"innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function formularioGastos($bloquea = false, $idPedido = "", $tipoPedido = "", $modoCompra = 1, $frm = NULL) {
	$objResponse = new xajaxResponse();
	
	if ($modoCompra == 1) { // 1 = Nacional
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("gasto.id_modo_gasto IN (1)");
	} else if ($modoCompra == 2) { // 2 = Importacion
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("gasto.id_modo_gasto IN (1,3)");
	} else if ($modoCompra == 3) { // 3 = Nacional por Importacion
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("gasto.id_modo_gasto IN (3)");
	}
	
	$queryGastos = sprintf("SELECT
		gasto.id_gasto,
		gasto.nombre,
		gasto.id_modo_gasto,
		gasto.afecta_documento,
		gasto.id_iva,
		iva_comp.iva AS iva_compra,
		iva_comp.observacion AS observacion_iva_compra,
		iva_comp.tipo AS tipo_iva_compra,
		iva_comp.activo AS activo_iva_compra,
		iva_comp.estado AS estado_iva_compra,
		gasto.id_iva_venta,
		iva_vent.iva AS iva_venta,
		iva_vent.observacion AS observacion_iva_venta,
		iva_vent.tipo AS tipo_iva_venta,
		iva_vent.activo AS activo_iva_venta,
		iva_vent.estado AS estado_iva_venta,
		gasto.estatus_iva
	FROM pg_gastos gasto
		LEFT JOIN pg_iva iva_comp ON (gasto.id_iva = iva_comp.idIva)
		LEFT JOIN pg_iva iva_vent ON (gasto.id_iva_venta = iva_vent.idIva) %s
	ORDER BY gasto.id_modo_gasto, gasto.nombre ASC;", $sqlBusq);
	$rsGastos = mysql_query($queryGastos);
	if (!$rsGastos) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	$html = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$contFila = 0;
	while ($rowGastos = mysql_fetch_assoc($rsGastos)) {
		$contFila++;
		
		$classPorc = ($checkPorc == "checked=\"checked\"") ? "class=\"inputInicial\"" : "";
		$classMonto = ($checkMonto == "checked=\"checked\"") ? "class=\"inputInicial\"" : "";
		
		$tipoGasto = 0;
			
		if ($frm != NULL) {
			$valueMontoPorc = number_format(str_replace(",","",$frm['txtPorcGasto'.$contFila]), 3, ".", ",");
			$valueMonto = number_format(str_replace(",","",$frm['txtMontoGasto'.$contFila]), 3, ".", ",");
			$checkPorc = ($frm['rbtGasto'.$contFila] == 1) ? "checked=\"checked\"" : "";
			$checkMonto = ($frm['rbtGasto'.$contFila] == 2) ? "checked=\"checked\"" : "";
			if ($checkPorc == "" && $checkMonto == "")
				$checkMonto = "checked=\"checked\"";
			$readOnlyMonto = ($checkPorc == "checked=\"checked\"") ? "readonly=\"readonly\"" : "";
		} else {
			$valueMontoPorc = number_format(0, 3, ".", ",");
			$valueMonto = number_format(0, 3, ".", ",");
			$checkPorc = "";
			$checkMonto = "checked=\"checked\"";
			$readOnlyMonto = ($checkPorc == "checked=\"checked\"") ? "readonly=\"readonly\"" : "";
		}
		
		if (in_array($tipoPedido, array("PRESUPUESTO","PEDIDO_VENTA","FACTURA_VENTA","NOTA_CREDITO"))) {
			if ($tipoPedido == "NOTA_CREDITO") {// Si es NC buscar el de la factura (Lo usa NC de repuestos)
				$queryGastoImpuesto = sprintf("SELECT iva.*, iva.idIva AS id_impuesto, IF (iva.tipo IN (3,2), 1, NULL) AS lujo
					FROM pg_iva iva 
					WHERE iva.idIva IN (SELECT cxc_fact_gasto_imp.id_impuesto
										FROM cj_cc_notacredito cxc_nc
											INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento = 'FA')
											INNER JOIN cj_cc_factura_gasto cxc_fact_gasto ON (cxc_fact.idFactura = cxc_fact_gasto.id_factura)
											INNER JOIN cj_cc_factura_gasto_impuesto cxc_fact_gasto_imp ON (cxc_fact_gasto.id_factura_gasto = cxc_fact_gasto_imp.id_factura_gasto)
										WHERE cxc_nc.idNotaCredito = %s
										AND cxc_fact_gasto.id_gasto = %s)",
					valTpDato($idPedido, "int"),
					valTpDato($rowGastos['id_gasto'], "int"));					
			} else if ($tipoPedido == "FACTURA_VENTA") {// Si es FA buscar el de la factura (Devolucion de factura varios modulos)
				$queryGastoImpuesto = sprintf("SELECT iva.*, iva.idIva AS id_impuesto, IF (iva.tipo IN (3,2), 1, NULL) AS lujo
					FROM pg_iva iva 
					WHERE iva.idIva IN (SELECT cxc_fact_gasto_imp.id_impuesto
											FROM cj_cc_factura_gasto cxc_fact_gasto
											INNER JOIN cj_cc_factura_gasto_impuesto cxc_fact_gasto_imp ON (cxc_fact_gasto.id_factura_gasto = cxc_fact_gasto_imp.id_factura_gasto)
										WHERE cxc_fact_gasto.id_factura = %s
										AND cxc_fact_gasto.id_gasto = %s)",
					valTpDato($idPedido, "int"),
					valTpDato($rowGastos['id_gasto'], "int"));		
			} else {
				// BUSCA LOS IMPUESTOS DEL GASTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
				$queryGastoImpuesto = sprintf("SELECT gasto_impuesto.*, iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo
				FROM pg_iva iva
					INNER JOIN pg_gastos_impuesto gasto_impuesto ON (iva.idIva = gasto_impuesto.id_impuesto)
				WHERE iva.tipo IN (6,9,2) AND iva.estado = 1
					AND gasto_impuesto.id_gasto = %s
				ORDER BY iva;",
					valTpDato($rowGastos['id_gasto'], "int"));
			}

			$rsGastoImpuesto = mysql_query($queryGastoImpuesto);
			if (!$rsGastoImpuesto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__.$queryGastoImpuesto, $contFila);
			$totalRowsGastoImpuesto = mysql_num_rows($rsGastoImpuesto);
			$arrayIdIvaItm = array(-1);
			while ($rowGastoImpuesto = mysql_fetch_assoc($rsGastoImpuesto)) {
				$arrayIdIvaItm[] = $rowGastoImpuesto['id_impuesto'];
			}
			$hddIdIvaItm = implode(",",$arrayIdIvaItm);
			
			// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
			WHERE iva.tipo IN (6,9,2)
				AND iva.idIva IN (%s);",
				valTpDato($hddIdIvaItm, "campo"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__.$queryIva, $contFila);
		} else {
			// BUSCA LOS IMPUESTOS DEL GASTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$queryGastoImpuesto = sprintf("SELECT gasto_impuesto.*, iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo
			FROM pg_iva iva
				INNER JOIN pg_gastos_impuesto gasto_impuesto ON (iva.idIva = gasto_impuesto.id_impuesto)
			WHERE iva.tipo IN (1,8,3) AND iva.estado = 1
				AND gasto_impuesto.id_gasto = %s
			ORDER BY iva;",
				valTpDato($rowGastos['id_gasto'], "int"));
			$rsGastoImpuesto = mysql_query($queryGastoImpuesto);
			if (!$rsGastoImpuesto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
			$totalRowsGastoImpuesto = mysql_num_rows($rsGastoImpuesto);
			$arrayIdIvaItm = array(-1);
			while ($rowGastoImpuesto = mysql_fetch_assoc($rsGastoImpuesto)) {
				$arrayIdIvaItm[] = $rowGastoImpuesto['id_impuesto'];
			}
			$hddIdIvaItm = implode(",",$arrayIdIvaItm);
			
			// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
			WHERE iva.tipo IN (1,8,3)
				AND iva.idIva IN (%s);",
				valTpDato($hddIdIvaItm, "campo"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		}
		
		if ($idPedido > 0) {
			switch ($tipoPedido) {
				case "COMPRA" : 
					$queryPedidoGastos = "SELECT * FROM iv_pedido_compra_gasto
					WHERE id_pedido_compra = %s
						AND id_gasto = %s;";
					break;
				case "COMPRA_GA" :
					$queryPedidoGastos = "SELECT * FROM ga_orden_compra_gasto
					WHERE id_orden_compra = %s
						AND id_gasto = %s;";
					break;
			 	case "PREREGISTRO" :
					$queryPedidoGastos = "SELECT * FROM iv_factura_compra_gasto
					WHERE id_factura_compra = %s
						AND id_gasto = %s;";
					break;
			 	case "REGISTRO" :
					$queryPedidoGastos = "SELECT * FROM cp_factura_gasto
					WHERE id_factura = %s
						AND id_gasto = %s;";
					break;
			 	case "PRESUPUESTO" :
					$queryPedidoGastos = "SELECT * FROM iv_presupuesto_venta_gasto
					WHERE id_presupuesto_venta = %s
						AND id_gasto = %s;";
					break;
			 	case "PEDIDO_VENTA" :
					$queryPedidoGastos = "SELECT * FROM iv_pedido_venta_gasto
					WHERE id_pedido_venta = %s
						AND id_gasto = %s;";
					break;
			 	case "FACTURA_VENTA" :
					$queryPedidoGastos = "SELECT * FROM cj_cc_factura_gasto
					WHERE id_factura = %s
						AND id_gasto = %s;";
					break;
			 	case "NOTA_CREDITO" :
					$queryPedidoGastos = "SELECT * FROM cj_cc_nota_credito_gasto
					WHERE id_nota_credito = %s
						AND id_gasto = %s;";
					break;
			}
			$queryPedidoGastos = sprintf($queryPedidoGastos,
				valTpDato($idPedido, "int"),
				valTpDato($rowGastos['id_gasto'], "int"));
			$rsPedidoGastos = mysql_query($queryPedidoGastos);
			if (!$rsPedidoGastos) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
			$rowPedidoGastos = mysql_fetch_assoc($rsPedidoGastos);
			
			if ($rowPedidoGastos['tipo_iva_compra'] == "0" || $rowPedidoGastos['tipo'] == "0") { // PORCENTAJE
				$valueMontoPorc = number_format($rowPedidoGastos['porcentaje_monto'], 3, ".", ",");
				$valueMonto = number_format($rowPedidoGastos['monto'], 3, ".", ",");
				$checkPorc = "checked=\"checked\"";
				$checkMonto = "";
				$readOnlyPorc = "";
				$readOnlyMonto = "readonly=\"readonly\"";
				$tipoGasto = 0; // 0 = Porcentaje
			} else { // MONTO
				$valueMontoPorc = number_format($rowPedidoGastos['porcentaje_monto'], 3, ".", ",");
				$valueMonto = number_format($rowPedidoGastos['monto'], 3, ".", ",");
				$checkPorc = "";
				$checkMonto = "checked=\"checked\"";
				$readOnlyPorc = "readonly=\"readonly\"";
				$readOnlyMonto = "";
				$tipoGasto = 1; // 1 = Monto Fijo
			}
		}
		
		if ($bloquea == false) {
			$classPorc = ($checkPorc == "checked=\"checked\"") ? "class=\"inputHabilitado\"" : "";
			$classMonto = ($checkMonto == "checked=\"checked\"") ? "class=\"inputHabilitado\"" : "";
			
			$tipoGasto = ($checkPorc == "checked=\"checked\"") ? 0 : 1;
		} else {
			$disabled = "disabled=\"disabled\"";
			$readOnlyPorc = "readonly=\"readonly\"";
			$readOnlyMonto = "readonly=\"readonly\"";
			$classPorc = "class=\"inputInicial\"";
			$classMonto = "class=\"inputInicial\"";
		}
	
	$html .= "<tr align=\"right\" id=\"trGasto:".$contFila."\">";
		$html .= "<td class=\"tituloCampo\" title=\"trGasto:".$contFila."\" width=\"35%\">".utf8_encode($rowGastos['nombre']).":";
			$html .= "<input id=\"cbxGasto\" name=\"cbxGasto[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"".$contFila."\">";
			$html .= "<input type=\"hidden\" id=\"hddIdGasto".$contFila."\" name=\"hddIdGasto".$contFila."\" value=\"".$rowGastos['id_gasto']."\">";
			$html .= "<input type=\"hidden\" id=\"hddTipoGasto".$contFila."\" name=\"hddTipoGasto".$contFila."\" value=\"".$tipoGasto."\">";
		$html .= "</td>";
		$html .= "<td nowrap=\"nowrap\" width=\"20%\">";
			$html .= "<input type=\"radio\" id=\"rbtGastoPorc".$contFila."\" name=\"rbtGasto".$contFila."\" ".$checkPorc." ".$disabled." onclick=\"
			byId('hddTipoGasto".$contFila."').value = 0;
			byId('txtPorcGasto".$contFila."').readOnly = false;
			byId('txtPorcGasto".$contFila."').className = 'inputHabilitado';
			byId('txtMontoGasto".$contFila."').readOnly = true;
			byId('txtMontoGasto".$contFila."').className = 'inputInicial';\" value=\"1\"/>";
			
			$html .= sprintf("<input type=\"text\" id=\"txtPorcGasto%s\" name=\"txtPorcGasto%s\" maxlength=\"8\" size=\"6\" style=\"text-align:right\"
			onblur=\"
			setFormatoRafk(this,2);
			if (byId('rbtGastoPorc%s').checked == true) {
				xajax_calculoPorcentaje(xajax.getFormValues('frmTotalDcto'), 'Porc', this.value, 'txtMontoGasto%s');
			}\"
			onfocus=\"if (byId('txtPorcGasto%s').value <= 0){ byId('txtPorcGasto%s').select(); }\"
			onkeypress=\"return validarSoloNumerosReales(event);\" value=\"%s\" %s %s/>",
				$contFila, $contFila,
				$contFila,
				$contFila,
				$contFila, $contFila,
				$valueMontoPorc, $classPorc, $readOnlyPorc);
		$html .= "%</td>";
		$html .= "<td nowrap=\"nowrap\" width=\"30%\">";
			$html .= "<input type=\"radio\" id=\"rbtGastoMonto".$contFila."\" name=\"rbtGasto".$contFila."\" ".$checkMonto." ".$disabled." onclick=\"
			byId('hddTipoGasto".$contFila."').value = 1;
			byId('txtPorcGasto".$contFila."').readOnly = true;
			byId('txtPorcGasto".$contFila."').className = 'inputInicial';
			byId('txtMontoGasto".$contFila."').readOnly = false;
			byId('txtMontoGasto".$contFila."').className = 'inputHabilitado';\" value=\"2\"/>";
				
			$html .= "<span id=\"spnGastoMoneda".$contFila."\"></span>&nbsp;";
			
			$html .= sprintf("<input type=\"text\" id=\"txtMontoGasto%s\" name=\"txtMontoGasto%s\" maxlength=\"12\" size=\"16\" style=\"text-align:right\"
			onblur=\"
			setFormatoRafk(this,2);
			if (byId('rbtGastoMonto%s').checked == true) {
				xajax_calculoPorcentaje(xajax.getFormValues('frmTotalDcto'), 'Cant', this.value, 'txtPorcGasto%s');
			}\"
			onfocus=\"if (byId('txtMontoGasto%s').value <= 0){ byId('txtMontoGasto%s').select(); }\"
			onkeypress=\"return validarSoloNumerosReales(event);\" value=\"%s\" %s %s/>",
				$contFila, $contFila,
				$contFila,
				$contFila,
				$contFila, $contFila,
				$valueMonto, $classMonto, $readOnlyMonto);
		$html .= "</td>";
		$html .= "<td width=\"15%\">";
			$contIva = 0;
			$ivaUnidad = "";
			while ($rowIva = mysql_fetch_assoc($rsIva)) {
				$contIva++;
				
				$ivaUnidad .= sprintf("<table cellpadding=\"0\" cellspacing=\"0\" width=\"%s\"><tr><td><img id=\"imgIvaGasto%s:%s\" src=\"../img/iconos/accept.png\" title=\"Aplica impuesto\"/></td><td width=\"%s\">".
				"<input type=\"text\" id=\"hddIvaGasto%s:%s\" name=\"hddIvaGasto%s:%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdIvaGasto%s:%s\" name=\"hddIdIvaGasto%s:%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddLujoIvaGasto%s:%s\" name=\"hddLujoIvaGasto%s:%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddEstatusIvaGasto%s:%s\" name=\"hddEstatusIvaGasto%s:%s\" value=\"%s\">".
				"<input id=\"cbxIvaGasto\" name=\"cbxIvaGasto[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td></tr></table>", 
					"100%", $contFila, $contIva, "100%",
					$contFila, $contIva, $contFila, $contIva, $rowIva['iva'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['idIva'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['lujo'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['estado'], 
					$contFila.":".$contIva);
			}
			$html .= $ivaUnidad;
			if ($rowGastos['id_modo_gasto'] == 1 && $rowGastos['afecta_documento'] == 0) {
				$html .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$html .= "<tr>";
					$html .= "<td>"."<img src=\"../img/iconos/stop.png\" title=\"No afecta cuenta por pagar\"/>"."</td>";
				$html .= "</tr>";
				$html .= "</table>";
			}
		$html .= "</td>";
	$html .= "</tr>";
	}
	$html .= "<tr>";
		$html .= "<td colspan=\"4\" class=\"divMsjInfo2\">";
			$html .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$html .= "<tr>";
				$html .= "<td width=\"25\"><img src=\"../img/iconos/ico_info.gif\" width=\"25\"/></td>";
				$html .= "<td align=\"center\">";
					$html .= "<table>";
					$html .= "<tr>";
						$html .= "<td><img src=\"../img/iconos/accept.png\" /></td>";
						$html .= "<td>Gastos que llevan impuesto</td>";
						$html .= "<td>&nbsp;</td>";
						$html .= "<td><img src=\"../img/iconos/stop.png\" /></td>";
						$html .= "<td>No afecta cuenta por pagar</td>";
					$html .= "</tr>";
					$html .= "</table>";
				$html .= "</td>";
			$html .= "</tr>";
			$html .= "</table>";
		$html .= "</td>";
	$html .= "</tr>";
	$html .= "</table>";
	
	return $html;
}


function encabezadoEmpresa($idEmpresa) {
	$objResponse = new xajaxResponse();
	
	if (!($idEmpresa > 0)) {
		$idEmpresa = 100;
	}
	
	$query = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$row = mysql_fetch_assoc($rs);
	
	if ($row['id_empresa'] != "") {
		$html .= "<table class=\"textoNegrita_8px\">";
		$html .= "<tr align=\"center\">";
			$html .= "<td>";
				$html .= "<img src=\"../".htmlentities($row['logo_familia'])."\" width=\"100\"/>";
			$html .= "</td>";
			$html .= "<td>";
				$html .= "<table width=\"250\">";
				$html .= "<tr align=\"center\">";
					$html .= "<td>";
						$html .= htmlentities($row['nombre_empresa']);
					$html .= "</td>";
				$html .= "</tr>";
			if (strlen($row['rif']) > 1) {
				$html .= "<tr align=\"center\">";
					$html .= "<td>R.I.F.: ";
						$html .= $row['rif'];
					$html .= "</td>";
				$html .= "</tr>";
			}
			if (strlen($row['direccion']) > 1) {
				$html .= "<tr align=\"center\">";
					$html .= "<td>";
						$html .= htmlentities($row['direccion']);
					$html .= "</td>";
				$html .= "</tr>";
			}
			if (strlen($row['web']) > 1) {
				$html .= "<tr align=\"center\">";
					$html .= "<td>";
						$html .= htmlentities($row['web']);
					$html .= "</td>";
				$html .= "</tr>";
			}
				$html .= "<table>";
			$html .= "</td>";
		$html .= "</tr>";
		$html .= "<table>";
		
		$objResponse->assign("tdEncabezadoImprimir","innerHTML",$html);
	}
	
	return $objResponse;
}


function objetoCodigoDinamico($tdUbicacion, $idEmpresa, $idEmpArticulo = "", $valor = "", $formato = "", $bloquearObj = false, $nombreObjeto = "") {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("-", $formato);
	
	$valorCad = explode(";", $valor);
	
	if ($idEmpresa == "" && $formato == "")
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	// SI NO SE PASA UN FORMATO, SE BUSCA EL FORMATO PREDEFINIDO DE LA EMPRESA
	if ($formato == "") {
		$query = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = %s",
			valTpDato($idEmpresa, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$row = mysql_fetch_assoc($rs);
		
		if ($row['id_empresa_reg'] != "")
			$valCadBusq = explode("-", $row['formato_codigo_repuestos']);
		else {
			$query = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = %s",
				valTpDato($idEmpArticulo, "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
			$row = mysql_fetch_assoc($rs);
			
			$valCadBusq = explode("-", $row['formato_codigo_repuestos']);
		}
		
		$contTamano = 0;
		foreach ($valCadBusq as $indice => $valor) {
			$contTamano += $valor;
		}
	}
	
	// SI LA CANTIDAD DE SUBDIVISIONES DEL FORMATO NO ES IGUAL A EL DE LA DATA, SE CONTRUIRA UN SOLO OBJETO CON TODO EL
	// CODIGO SIN SUBDIVISIONES
	if (count($valCadBusq) != count($valorCad) && $valor != "" && count($valorCad) > 1) {
		$contTamanoFormato = 0;
		foreach ($valCadBusq as $indice => $valor) {
			$contTamanoFormato += $valor;
		}
		$contTamanoCaracter = 0;
		foreach ($valorCad as $indice => $valor) {
			$contTamanoCaracter += strlen($valor);
		}
		
		$contTamano = $contTamanoFormato;
		if ($contTamanoFormato <= $contTamanoCaracter)
			$contTamano = $contTamanoCaracter;
		
		$valCadBusq = NULL;
		$valCadBusq[] = $contTamano;
		
		$value = "";
		foreach ($valorCad as $indice => $valor)
			$value .= $valor;
		
		$valorCad = NULL;
		$valorCad[] = $value;
	}
	
	$readonly = "";
	if ($bloquearObj == true) {
		$readonly = "readonly=\"readonly\"";
	}
	
	$html = "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
	$html .= "<tr>";
	foreach ($valCadBusq as $indice => $valor) {
		$value = "";
		if ($valor != "")
			$value = $valorCad[$indice];
		
		$tamanoObjeto = $valor+2;
		if (count($valCadBusq) == 1) {
			$tamanoObjeto = intval($contTamano) + 4;
		}
		
		$html .= sprintf("<td><input type=\"text\" id=\"%s\" name=\"%s\" onkeyup=\"letrasMayusculas(event, this.id);\" onkeypress=\"return validarCodigoArticulo(event);\" ".$readonly." size=\"%s\" maxlength=\"%s\" value=\"%s\"/></td><td>&nbsp;</td>",
			"txtCodigoArticulo".$nombreObjeto.$indice,
			"txtCodigoArticulo".$nombreObjeto.$indice,
			$tamanoObjeto,
			$valor,
			$value);
		
		$cantObjetos = strval($indice);
	}
	$html .= "</tr>";
	$html .= "</table>";
	$html .= sprintf("<input type=\"hidden\" id=\"%s\" name=\"%s\" readonly=\"readonly\" size=\"2\" value=\"%s\"/>",
		"hddCantCodigo".$nombreObjeto,
		"hddCantCodigo".$nombreObjeto,
		$cantObjetos);
	
	$objResponse->assign($tdUbicacion,"innerHTML",$html);

	return $objResponse;
}

function objetoCodigoDinamicoCompras($tdUbicacion, $idEmpresa, $idEmpArticulo = "", $valor = "", $formato = "", $bloquearObj = "false") {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("-", $formato);
	
	$valorCad = explode("-", $valor);
	
	if ($idEmpresa == "" && $formato == "")
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	/* SI NO SE PASA UN FORMATO, SE BUSCA EL FORMATO PREDEFINIDO DE LA EMPRESA*/
	if ($formato == "") {
		$query = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = %s",
			valTpDato($idEmpresa,"int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$row = mysql_fetch_assoc($rs);
		
		if ($row['id_empresa_reg'] != "")
			$valCadBusq = explode("-", $row['formato_codigo_compras']);
		else {
			$query = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = %s",
				valTpDato($idEmpArticulo,"int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
			$row = mysql_fetch_assoc($rs);
			
			$valCadBusq = explode("-", $row['formato_codigo_compras']);
		}
		
		$contTamano = 0;
		foreach ($valCadBusq as $indice => $valor) {
			$contTamano += $valor;
		}
	}
	
	/* SI LA CANTIDAD DE SUBDIVISIONES DEL FORMATO NO ES IGUAL A EL DE LA DATA, SE CONTRUIRA UN SOLO OBJETO CON TODO EL
	CODIGO SIN SUBDIVISIONES */
	if (count($valCadBusq) != count($valorCad) && $valor != "" && count($valorCad) > 1) {
		$contTamanoFormato = 0;
		foreach ($valCadBusq as $indice => $valor) {
			$contTamanoFormato += $valor;
		}
		$contTamanoCaracter = 0;
		foreach ($valorCad as $indice => $valor) {
			$contTamanoCaracter += strlen($valor);
		}
		
		$contTamano = $contTamanoFormato;
		if ($contTamanoFormato <= $contTamanoCaracter)
			$contTamano = $contTamanoCaracter;
		
		$valCadBusq = NULL;
		$valCadBusq[] = $contTamano;
		
		$value = "";
		foreach ($valorCad as $indice => $valor)
			$value .= $valor;
		
		$valorCad = NULL;
		$valorCad[] = $value;
	}
	
	$readonly = "";
	if ($bloquearObj == "true") {
		$readonly = "readonly=\"readonly\"";
	}
	
	$html = "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
	$html .= "<tr>";
	foreach ($valCadBusq as $indice => $valor) {
		$value = "";
		if ($valor != "")
			$value = $valorCad[$indice];
		
		$tamanoObjeto = $valor+2;
		if (count($valCadBusq) == 1) {
			$tamanoObjeto = intval($contTamano) + 4;
		}
		
		$html .= sprintf("<td><input type=\"text\" id=\"txtCodigoArticulo%s\" name=\"txtCodigoArticulo%s\" onkeyup=\"letrasMayusculas(event, this.id);\" ".$readonly." size=\"%s\" maxlength=\"%s\" value=\"%s\"/></td><td>&nbsp;</td>",
			$indice,
			$indice,
			$tamanoObjeto,
			$valor,
			$value);
		
		$cantObjetos = strval($indice);
	}
	$html .= "</tr>";
	$html .= "</table>";
	$html .= sprintf("<input type=\"hidden\" id=\"hddCantCodigo\" name=\"hddCantCodigo\" readonly=\"readonly\" size=\"2\" value=\"%s\"/>",
		$cantObjetos);
	
	$objResponse->assign($tdUbicacion,"innerHTML",$html);

	return $objResponse;
}


$xajax->register(XAJAX_FUNCTION,"calculoPorcentaje");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresaFinal");
$xajax->register(XAJAX_FUNCTION,"controlSession");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresaUsuario");
$xajax->register(XAJAX_FUNCTION,"listadoEmpresasUsuario");
$xajax->register(XAJAX_FUNCTION,"formularioGastos");
$xajax->register(XAJAX_FUNCTION,"encabezadoEmpresa");
$xajax->register(XAJAX_FUNCTION,"objetoCodigoDinamico");
$xajax->register(XAJAX_FUNCTION,"objetoCodigoDinamicoCompras");

function actualizarEstatusSolicitudRepuestos($idSolicitud = "") {
	global $conex;
	
	//if ($idSolicitud != "-1" && $idSolicitud != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusq .= $cond.sprintf("id_solicitud = %s",
			valTpDato($idSolicitud, "int"));
	//}
	
	// ACTUALIZA EL ESTATUS DE LOS ITEMS QUE DEJARON EN EL AIRE MIENTRAS LA SOLICITUD NO ESTE BLOQUEDA (REPUESTOS QUE NO FUERON APROBADOS)
	$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos SET
		id_estado_solicitud = 1
	WHERE id_estado_solicitud = 2
		AND (tiempo_aprobacion = 0 OR tiempo_aprobacion IS NULL)
		AND (SELECT COUNT(sol_rep.id_solicitud) FROM sa_solicitud_repuestos sol_rep
			WHERE sol_rep.id_solicitud = sa_det_solicitud_repuestos.id_solicitud
				AND sol_rep.id_usuario_bloqueo > 0) = 0 %s;", $sqlBusq);
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA EL ESTATUS DE LOS ITEMS QUE DEJARON EN EL AIRE (REPUESTOS QUE NO FUERON DESAPROBADOS)
	$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos SET
		id_estado_solicitud = 2
	WHERE id_estado_solicitud = 1
		AND tiempo_aprobacion > 0
		AND (SELECT COUNT(sol_rep.id_solicitud) FROM sa_solicitud_repuestos sol_rep
			WHERE sol_rep.id_solicitud = sa_det_solicitud_repuestos.id_solicitud
				AND sol_rep.id_usuario_bloqueo > 0) = 0 %s;", $sqlBusq);
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA EL ESTATUS DE LOS ITEMS QUE DEJARON EN EL AIRE (REPUESTOS QUE NO FUERON DESPACHADOS)
	$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos SET
		id_estado_solicitud = 2
	WHERE id_estado_solicitud = 3
		AND (tiempo_despacho = 0 OR tiempo_despacho IS NULL)
		AND (SELECT COUNT(sol_rep.id_solicitud) FROM sa_solicitud_repuestos sol_rep
			WHERE sol_rep.id_solicitud = sa_det_solicitud_repuestos.id_solicitud
				AND sol_rep.id_usuario_bloqueo > 0) = 0 %s;", $sqlBusq);
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA EL ESTATUS DE LOS ITEMS QUE DEJARON EN EL AIRE (REPUESTOS QUE NO FUERON DEVUELTOS)
	$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos SET
		id_estado_solicitud = 3
	WHERE id_estado_solicitud = 4
		AND (tiempo_devolucion = 0 OR tiempo_devolucion IS NULL)
		AND (SELECT COUNT(sol_rep.id_solicitud) FROM sa_solicitud_repuestos sol_rep
			WHERE sol_rep.id_solicitud = sa_det_solicitud_repuestos.id_solicitud
				AND sol_rep.id_usuario_bloqueo > 0) = 0 %s;", $sqlBusq);
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA EL ESTATUS DE LOS ITEMS QUE DEJARON EN EL AIRE (REPUESTOS QUE NO FUERON ANULADOS)
	$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos SET
		id_estado_solicitud = 1
	WHERE id_estado_solicitud = 6
		AND (tiempo_anulacion = 0 OR tiempo_anulacion IS NULL)
		AND (SELECT COUNT(sol_rep.id_solicitud) FROM sa_solicitud_repuestos sol_rep
			WHERE sol_rep.id_solicitud = sa_det_solicitud_repuestos.id_solicitud
				AND sol_rep.id_usuario_bloqueo > 0) = 0 %s;", $sqlBusq);
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	mysql_query("SET NAMES 'latin1';");
	
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("sol_rep.id_usuario_bloqueo > 0 OR sol_rep.id_usuario_bloqueo IS NOT NULL OR sol_rep.id_usuario_bloqueo IS NULL");
		
	//if ($idSolicitud != "-1" && $idSolicitud != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("sol_rep.id_solicitud = %s",
			valTpDato($idSolicitud, "int"));
	//}
	
	// BUSCA LOS DATOS DE LA SOLICITUD
	$query = sprintf("SELECT
		id_solicitud,
		
		(SELECT COUNT(det_solicitud_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_solicitud_rep
		WHERE det_solicitud_rep.id_solicitud = sol_rep.id_solicitud) AS cantidad,
		
		(SELECT COUNT(det_solicitud_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_solicitud_rep
		WHERE det_solicitud_rep.id_solicitud = sol_rep.id_solicitud
			AND det_solicitud_rep.id_estado_solicitud = 1) AS cantidad_solicitada,
			
		(SELECT COUNT(det_solicitud_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_solicitud_rep
		WHERE det_solicitud_rep.id_solicitud = sol_rep.id_solicitud
			AND det_solicitud_rep.id_estado_solicitud = 2
			AND tiempo_aprobacion > 0) AS cantidad_aprobada,
			
		(SELECT COUNT(det_solicitud_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_solicitud_rep
		WHERE det_solicitud_rep.id_solicitud = sol_rep.id_solicitud
			AND det_solicitud_rep.id_estado_solicitud = 3
			AND tiempo_despacho > 0) AS cantidad_despachada,
			
		(SELECT COUNT(det_solicitud_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_solicitud_rep
		WHERE det_solicitud_rep.id_solicitud = sol_rep.id_solicitud
			AND det_solicitud_rep.id_estado_solicitud = 4
			AND tiempo_devolucion > 0) AS cantidad_devuelta,
			
		(SELECT COUNT(det_solicitud_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_solicitud_rep
		WHERE det_solicitud_rep.id_solicitud = sol_rep.id_solicitud
			AND det_solicitud_rep.id_estado_solicitud = 5) AS cantidad_facturada,
			
			(SELECT COUNT(det_solicitud_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_solicitud_rep
		WHERE det_solicitud_rep.id_solicitud = sol_rep.id_solicitud
			AND det_solicitud_rep.id_estado_solicitud = 6) AS cantidad_anulada,
			
		(SELECT COUNT(det_solicitud_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_solicitud_rep
		WHERE det_solicitud_rep.id_solicitud = sol_rep.id_solicitud
			AND det_solicitud_rep.id_estado_solicitud = 10) AS cantidad_no_despachada
		
	FROM sa_solicitud_repuestos sol_rep %s;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	while ($row = mysql_fetch_assoc($rs)) {
		if ($row['cantidad_facturada'] > 0) {
			$estatusSolicitud = 5; // FACTURADO
		} else if ($row['cantidad_devuelta'] == $row['cantidad']) {
			$estatusSolicitud = 4; // DEVUELTO
		} else if ($row['cantidad_devuelta'] > 0 && $row['cantidad_devuelta'] >= $row['cantidad_despachada']) {
			$estatusSolicitud = 9; // DEVUELTA PARCIAL
		} else if ($row['cantidad_despachada'] == $row['cantidad']) {
			$estatusSolicitud = 3; // DESPACHADO
		} else if ($row['cantidad_despachada'] > 0 && $row['cantidad_despachada'] >= $row['cantidad_devuelta']) {
			$estatusSolicitud = 8; // DESPACHADA PARCIAL
		} else if ($row['cantidad_aprobada'] == $row['cantidad']) {
			$estatusSolicitud = 2; // APROBADO
		} else if ($row['cantidad_aprobada'] > 0 && $row['cantidad_aprobada'] < $row['cantidad']) {
			$estatusSolicitud = 7; // APROBADA PARCIAL
		} else if ($row['cantidad_anulada'] == $row['cantidad']) {
			$estatusSolicitud = 6; // SOLICITADO
		} else if ($row['cantidad_solicitada'] == $row['cantidad'] || $row['cantidad_solicitada'] > 0) {
			$estatusSolicitud = 1; // SOLICITADO
			
			$updateSQL = sprintf("UPDATE sa_solicitud_repuestos SET 
				id_jefe_taller = NULL,
				id_jefe_repuesto = NULL,
				id_gerente_postventa = NULL,
				id_empleado_recibo = NULL,
				id_empleado_entrega = NULL,
				id_empleado_devuelto = NULL
			WHERE id_solicitud = %s;",
				valTpDato($row['id_solicitud'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
			mysql_query("SET NAMES 'latin1';");
		}
		
		// ACTUALIZA EL ESTADO DE LA SOLICITUD
		$updateSQL = sprintf("UPDATE sa_solicitud_repuestos SET 
			estado_solicitud = %s
		WHERE id_solicitud = %s;",
			valTpDato($estatusSolicitud, "int"),
			valTpDato($row['id_solicitud'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		mysql_query("SET NAMES 'latin1';");
	}
	
	return array(true, "");
}

function actualizarSaldos($idArticulo = "", $idCasilla = "", $idCasillaAnt = "") {
	global $conex;
	
	($idCasilla != "-1" && $idCasilla != "") ? $arrayIdCasilla[] = $idCasilla : "";
	($idCasillaAnt != "-1" && $idCasillaAnt != "") ? $arrayIdCasilla[] = $idCasillaAnt : "";
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADA)
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("iv_articulos_almacen.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	if (count($arrayIdCasilla) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("iv_articulos_almacen.id_casilla IN (%s)",
			valTpDato(implode(",",$arrayIdCasilla), "campo"));
	}
	
	$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
		cantidad_entrada = (SELECT SUM(kardex.cantidad)
							FROM iv_kardex kardex
							WHERE kardex.id_articulo = iv_articulos_almacen.id_articulo
								AND kardex.id_casilla = iv_articulos_almacen.id_casilla
								AND kardex.tipo_movimiento IN (1,2)),
		cantidad_salida = (SELECT SUM(kardex.cantidad)
							FROM iv_kardex kardex
							WHERE kardex.id_articulo = iv_articulos_almacen.id_articulo
								AND kardex.id_casilla = iv_articulos_almacen.id_casilla
								AND kardex.tipo_movimiento IN (3,4)),
		cantidad_reservada = (SELECT COUNT(det_orden_art.id_articulo) AS cantidad_reservada
							FROM sa_det_solicitud_repuestos det_solicitud_rep
								JOIN sa_det_orden_articulo det_orden_art ON (det_solicitud_rep.id_det_orden_articulo = det_orden_art.id_det_orden_articulo)
							WHERE det_orden_art.id_articulo = iv_articulos_almacen.id_articulo
								AND det_solicitud_rep.id_casilla = iv_articulos_almacen.id_casilla
								AND det_solicitud_rep.id_estado_solicitud IN (2,3)),
		cantidad_espera = (IFNULL((SELECT SUM(iv_ped_vent_det.pendiente)
									FROM iv_pedido_venta_detalle iv_ped_vent_det
										JOIN iv_pedido_venta iv_ped_vent ON (iv_ped_vent_det.id_pedido_venta = iv_ped_vent.id_pedido_venta)
									WHERE iv_ped_vent_det.id_articulo = iv_articulos_almacen.id_articulo
										AND iv_ped_vent_det.id_casilla = iv_articulos_almacen.id_casilla
										AND iv_ped_vent_det.estatus IN (0,1)
										AND iv_ped_vent.estatus_pedido_venta IN (0,1,2)), 0)),
		cantidad_bloqueada = (SELECT SUM(bloqueo_vent_det.cantidad)
								FROM iv_bloqueo_venta_detalle bloqueo_vent_det
								WHERE bloqueo_vent_det.id_articulo = iv_articulos_almacen.id_articulo
									AND bloqueo_vent_det.id_casilla = iv_articulos_almacen.id_casilla
									AND bloqueo_vent_det.estatus IN (1,3)) %s;", $sqlBusq);
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	
	$sqlBusq = " "; // ESPACIO EN BLANCO PORQUE LA PRIMERA CONDICION ESTA EN LA CONSULTA
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_alm.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	// VERIFICA LAS UBICACIONES DEL ARTICULO TENGAN LOS SALDOS INCORRECTOS
	$queryArtAlm = sprintf("SELECT * FROM iv_articulos_almacen art_alm
	WHERE ((art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_espera) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_bloqueada) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada - art_alm.cantidad_espera - art_alm.cantidad_bloqueada) < 0)
		AND (art_alm.estatus = 1
			OR art_alm.cantidad_reservada > 0) %s", $sqlBusq);
	$rsArtAlm = mysql_query($queryArtAlm);
	if (!$rsArtAlm) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
	while ($rowArtAlm = mysql_fetch_assoc($rsArtAlm)) {
		$queryArticulo = sprintf("SELECT *
		FROM vw_iv_articulos_almacen vw_iv_art_alm
			INNER JOIN iv_articulos art ON (vw_iv_art_alm.id_articulo = art.id_articulo)
		WHERE vw_iv_art_alm.id_articulo_almacen = %s;",
			valTpDato($rowArtAlm['id_articulo_almacen'], "int"));
		$rsArticulo = mysql_query($queryArticulo);
		if (!$rsArticulo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$totalRowsArticulo = mysql_num_rows($rsArticulo);
		$rowArticulo = mysql_fetch_assoc($rsArticulo);
		
		$arrayAlmacenInvalido[] = "Id Articulo Almacen: ".$rowArtAlm['id_articulo_almacen']." =".
			" Id Articulo: ".$rowArticulo['id_articulo'].
			" Id Casilla: ".$rowArtAlm['id_casilla']." -".
			" ".elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";")." (".$rowArticulo['descripcion_almacen']." ".$rowArticulo['ubicacion'].")";
	}
	if ($totalRowsArtAlm > 0) { return array(false, "La(s) ubicacion(es) Id.\n ".implode("\n ", $arrayAlmacenInvalido)." posee saldos inválidos.\nLine: ".__LINE__); }
	
	
	
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADA)
	$sqlBusq = "";
	/*$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("iv_articulos_costos.estatus = 1");*/
		
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("iv_articulos_costos.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	$updateSQL = sprintf("UPDATE iv_articulos_costos SET
		cantidad_entrada = (SELECT SUM(kardex.cantidad)
							FROM iv_kardex kardex
							WHERE kardex.id_articulo_costo = iv_articulos_costos.id_articulo_costo
								AND kardex.tipo_movimiento IN (1,2)),
		cantidad_salida = (SELECT SUM(kardex.cantidad)
							FROM iv_kardex kardex
							WHERE kardex.id_articulo_costo = iv_articulos_costos.id_articulo_costo
								AND kardex.tipo_movimiento IN (3,4)),
		cantidad_reservada = (SELECT COUNT(det_orden_art.id_articulo) AS cantidad_reservada
							FROM sa_det_solicitud_repuestos det_solicitud_rep
								INNER JOIN sa_det_orden_articulo det_orden_art ON (det_solicitud_rep.id_det_orden_articulo = det_orden_art.id_det_orden_articulo)
							WHERE det_orden_art.id_articulo_costo = iv_articulos_costos.id_articulo_costo 
								AND det_solicitud_rep.id_estado_solicitud IN (2,3)),
		cantidad_espera = (IFNULL((SELECT SUM(iv_ped_vent_det.pendiente)
									FROM iv_pedido_venta_detalle iv_ped_vent_det
										JOIN iv_pedido_venta iv_ped_vent ON (iv_ped_vent_det.id_pedido_venta = iv_ped_vent.id_pedido_venta)
									WHERE iv_ped_vent_det.id_articulo_costo = iv_articulos_costos.id_articulo_costo
										AND iv_ped_vent_det.estatus IN (0,1)
										AND iv_ped_vent.estatus_pedido_venta IN (0,1,2)), 0)),
		cantidad_bloqueada = (SELECT SUM(bloqueo_vent_det.cantidad)
								FROM iv_bloqueo_venta_detalle bloqueo_vent_det
								WHERE bloqueo_vent_det.id_articulo_costo = iv_articulos_costos.id_articulo_costo
									AND bloqueo_vent_det.estatus IN (1,3)) %s;", $sqlBusq);
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	
	$sqlBusq = " "; // ESPACIO EN BLANCO PORQUE LA PRIMERA CONDICION ESTA EN LA CONSULTA
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_costo.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	// VERIFICA LOS LOTES DEL ARTICULO TENGAN LOS SALDOS INCORRECTOS
	$queryArtCosto = sprintf("SELECT * FROM iv_articulos_costos art_costo
	WHERE ((art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida) < 0
			OR (art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida - art_costo.cantidad_reservada) < 0
			OR (art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida - art_costo.cantidad_espera) < 0
			OR (art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida - art_costo.cantidad_bloqueada) < 0
			OR (art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida - art_costo.cantidad_reservada - art_costo.cantidad_espera - art_costo.cantidad_bloqueada) < 0)
		AND (art_costo.estatus = 1
			OR art_costo.cantidad_reservada > 0) %s", $sqlBusq);
	$rsArtCosto = mysql_query($queryArtCosto);
	if (!$rsArtCosto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
	while ($rowArtCosto = mysql_fetch_assoc($rsArtCosto)) {
		$arrayLoteInvalido[] = $rowArtCosto['id_articulo_costo'];
	}
	if ($totalRowsArtCosto > 0) { return array(false, "El(Los) lote(s) Nro. ".implode(", ", $arrayLoteInvalido)." posee saldos inválidos.\nLine: ".__LINE__); }
	
	
	
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADA)
	$sqlBusq = "";
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_almacen.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	$updateSQL = sprintf("UPDATE iv_articulos_almacen art_almacen
		INNER JOIN iv_articulos_almacen_costo art_almacen_costo ON (art_almacen.id_articulo_almacen = art_almacen_costo.id_articulo_almacen) SET
		art_almacen_costo.cantidad_entrada = (SELECT SUM(kardex.cantidad)
										FROM iv_kardex kardex
										WHERE kardex.id_articulo = art_almacen.id_articulo
											AND kardex.id_casilla = art_almacen.id_casilla
											AND kardex.id_articulo_costo = art_almacen_costo.id_articulo_costo
											AND kardex.tipo_movimiento IN (1,2)),
		art_almacen_costo.cantidad_salida = (SELECT SUM(kardex.cantidad)
										FROM iv_kardex kardex
										WHERE kardex.id_articulo = art_almacen.id_articulo
											AND kardex.id_casilla = art_almacen.id_casilla
											AND kardex.id_articulo_costo = art_almacen_costo.id_articulo_costo
											AND kardex.tipo_movimiento IN (3,4)),
		art_almacen_costo.cantidad_reservada = (SELECT COUNT(det_orden_art.id_articulo) AS cantidad_reservada
										FROM sa_det_solicitud_repuestos det_solicitud_rep
											INNER JOIN sa_det_orden_articulo det_orden_art ON (det_solicitud_rep.id_det_orden_articulo = det_orden_art.id_det_orden_articulo)
										WHERE det_orden_art.id_articulo = art_almacen.id_articulo
											AND det_solicitud_rep.id_casilla = art_almacen.id_casilla
											AND det_orden_art.id_articulo_costo = art_almacen_costo.id_articulo_costo
											AND det_solicitud_rep.id_estado_solicitud IN (2,3)),
		art_almacen_costo.cantidad_espera = (IFNULL((SELECT SUM(iv_ped_vent_det.pendiente)
													FROM iv_pedido_venta_detalle iv_ped_vent_det
														JOIN iv_pedido_venta iv_ped_vent ON (iv_ped_vent_det.id_pedido_venta = iv_ped_vent.id_pedido_venta)
													WHERE iv_ped_vent_det.id_articulo = art_almacen.id_articulo
														AND iv_ped_vent_det.id_casilla = art_almacen.id_casilla
														AND iv_ped_vent_det.id_articulo_costo = art_almacen_costo.id_articulo_costo
														AND iv_ped_vent_det.estatus IN (0,1)
														AND iv_ped_vent.estatus_pedido_venta IN (0,1,2)), 0)),
		art_almacen_costo.cantidad_bloqueada = (SELECT SUM(bloqueo_vent_det.cantidad)
										FROM iv_bloqueo_venta_detalle bloqueo_vent_det
										WHERE bloqueo_vent_det.id_articulo = art_almacen.id_articulo
											AND bloqueo_vent_det.id_casilla = art_almacen.id_casilla
											AND bloqueo_vent_det.id_articulo_costo = art_almacen_costo.id_articulo_costo
											AND bloqueo_vent_det.estatus IN (1,3)) %s;", $sqlBusq);
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	
	$sqlBusq = " "; // ESPACIO EN BLANCO PORQUE LA PRIMERA CONDICION ESTA EN LA CONSULTA
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_costo.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	// VERIFICA LA UBICACION DE LOS LOTES DEL ARTICULO TENGAN LOS SALDOS INCORRECTOS
	$queryArtCosto = sprintf("SELECT *
	FROM iv_articulos_almacen_costo art_almacen_costo
		INNER JOIN iv_articulos_costos art_costo ON (art_almacen_costo.id_articulo_costo = art_costo.id_articulo_costo)
	WHERE ((art_almacen_costo.cantidad_inicio + art_almacen_costo.cantidad_entrada - art_almacen_costo.cantidad_salida) < 0
			OR (art_almacen_costo.cantidad_inicio + art_almacen_costo.cantidad_entrada - art_almacen_costo.cantidad_salida - art_almacen_costo.cantidad_reservada) < 0
			OR (art_almacen_costo.cantidad_inicio + art_almacen_costo.cantidad_entrada - art_almacen_costo.cantidad_salida - art_almacen_costo.cantidad_espera) < 0
			OR (art_almacen_costo.cantidad_inicio + art_almacen_costo.cantidad_entrada - art_almacen_costo.cantidad_salida - art_almacen_costo.cantidad_bloqueada) < 0
			OR (art_almacen_costo.cantidad_inicio + art_almacen_costo.cantidad_entrada - art_almacen_costo.cantidad_salida - art_almacen_costo.cantidad_reservada - art_almacen_costo.cantidad_espera - art_almacen_costo.cantidad_bloqueada) < 0)
		AND (art_almacen_costo.estatus = 1
			OR art_almacen_costo.cantidad_reservada > 0) %s", $sqlBusq);
	$rsArtCosto = mysql_query($queryArtCosto);
	if (!$rsArtCosto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
	while ($rowArtCosto = mysql_fetch_assoc($rsArtCosto)) {
		$arrayLoteInvalido[] = $rowArtCosto['id_articulo_costo'];
	}
	if ($totalRowsArtCosto > 0) { return array(false, "La(Las) relacion(es) ubicación/lote(s) Id. ".implode(", ", $arrayLoteInvalido)." posee saldos inválidos.\nLine: ".__LINE__); }
	
	
	
	
	// INACTIVO TODOS LOS LOTES EN CERO
	$sqlBusq = " "; // ESPACIO EN BLANCO PORQUE LA PRIMERA CONDICION ESTA EN LA CONSULTA
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_costo.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	$updateSQL = sprintf("UPDATE iv_articulos_costos art_costo SET
		art_costo.estatus = NULL
	WHERE (art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida) = 0
		AND art_costo.id_empleado_creador IS NULL %s;", $sqlBusq);
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	
	// ACTIVO LOS LOTES QUE TENGAN SALDO Y EL ULTIMO LOTE DE CADA ARTICULO CON EL QUE SE TRABAJO
	$sqlBusq = " "; // ESPACIO EN BLANCO PORQUE LA PRIMERA CONDICION ESTA EN LA CONSULTA
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_costo.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	$updateSQL = sprintf("UPDATE iv_articulos_costos art_costo
		LEFT JOIN (SELECT art_costo2.id_empresa, MAX(art_costo2.id_articulo_costo) AS id_articulo_costo, art_costo2.id_articulo
					FROM iv_articulos_costos art_costo2
					WHERE (art_costo2.cantidad_inicio + art_costo2.cantidad_entrada - art_costo2.cantidad_salida) = 0
						AND (SELECT COUNT(*) FROM iv_articulos_costos art_costo3
							WHERE art_costo3.id_empresa = art_costo2.id_empresa
								AND art_costo3.id_articulo = art_costo2.id_articulo
								AND art_costo3.estatus = 1) = 0
						AND art_costo2.id_empresa IS NOT NULL
					GROUP BY art_costo2.id_articulo) AS q ON (art_costo.id_articulo = q.id_articulo
						AND art_costo.id_articulo_costo = q.id_articulo_costo) SET
		art_costo.estatus = 1
	WHERE (((art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida) > 0 AND q.id_articulo IS NULL)
		OR ((art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida) = 0
			AND (art_costo.id_articulo = q.id_articulo
				AND art_costo.id_articulo_costo = q.id_articulo_costo))) %s;", $sqlBusq);
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	
	// INACTIVA LA UBICACION DE LOS LOTES INACTIVOS
	$updateSQL = sprintf("UPDATE iv_articulos_almacen_costo art_almacen_costo SET
		art_almacen_costo.estatus = NULL
	WHERE art_almacen_costo.id_articulo_costo IN (SELECT id_articulo_costo FROM iv_articulos_costos art_costo WHERE estatus IS NULL);", $sqlBusq);
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	return array(true, "");
}

function actualizarPedidas($idArticulo) {
	global $conex;
	
	// INICIALIZA LAS PEDIDAS EN LOS ARTICULOS QUE TENGAN ALGUNA UBICACION
	$updateSQL = sprintf("UPDATE iv_articulos_almacen SET cantidad_pedida = 0
	WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	// ACTUALIZA LA PEDIDAS DE LOS ARTICULOS QUE TIENEN UBICACION
	$updateSQL = sprintf("UPDATE iv_articulos_almacen art_alm, iv_almacenes alm, iv_calles calle, iv_estantes estante, iv_tramos tramo, iv_casillas casilla SET
		cantidad_pedida = IFNULL((SELECT SUM(pendiente)
							FROM iv_pedido_compra ped_comp
							 INNER JOIN iv_pedido_compra_detalle ped_comp_det ON (ped_comp.id_pedido_compra = ped_comp_det.id_pedido_compra)
							WHERE ped_comp_det.id_articulo = art_alm.id_articulo
								AND ped_comp_det.estatus IN (0)
								AND ped_comp.id_empresa = alm.id_empresa
								AND ped_comp.estatus_pedido_compra IN (0,1,2)), 0)
	WHERE alm.id_almacen = calle.id_almacen
		AND calle.id_calle = estante.id_calle
		AND estante.id_estante = tramo.id_estante
		AND tramo.id_tramo = casilla.id_tramo
		AND casilla.id_casilla = art_alm.id_casilla
		AND art_alm.id_articulo = %s
		AND IF (((SELECT count(art_emp.id_articulo) AS casilla_predeterminada FROM iv_articulos_empresa art_emp
			WHERE ((art_emp.id_empresa = alm.id_empresa)
				AND (art_emp.id_articulo = art_alm.id_articulo)
				AND (art_emp.id_casilla_predeterminada_compra = casilla.id_casilla))) > 0), 1, NULL) = 1
		AND (art_alm.estatus = 1 AND alm.estatus_almacen_compra = 1);",
		valTpDato($idArticulo, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	// INICIALIZA LAS PEDIDAS EN LOS ARTICULOS QUE NO TIENEN UBICACION
	$updateSQL = sprintf("UPDATE iv_articulos_empresa SET cantidad_pedida = 0
	WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	// ACTUALIZA LA PEDIDAS DE LOS ARTICULOS QUE NO TIENEN UBICACION
	$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
		cantidad_pedida = (SELECT SUM(pendiente)
							FROM iv_pedido_compra ped_comp
							 INNER JOIN iv_pedido_compra_detalle ped_comp_det ON (ped_comp.id_pedido_compra = ped_comp_det.id_pedido_compra)
							WHERE ped_comp_det.id_articulo = iv_articulos_empresa.id_articulo
								AND ped_comp_det.estatus IN (0)
								AND ped_comp.id_empresa = iv_articulos_empresa.id_empresa
								AND ped_comp.estatus_pedido_compra IN (0,1,2))
	WHERE id_articulo = %s
		AND ((id_casilla_predeterminada IS NULL
				OR (SELECT COUNT(art_alm.id_articulo)
					FROM iv_articulos_almacen art_alm
					WHERE art_alm.id_casilla = iv_articulos_empresa.id_casilla_predeterminada
						AND art_alm.id_articulo = iv_articulos_empresa.id_articulo
						AND art_alm.estatus = 1) = 0)
			AND (id_casilla_predeterminada_compra IS NULL
				OR (SELECT COUNT(art_alm.id_articulo)
					FROM iv_articulos_almacen art_alm
					WHERE art_alm.id_casilla = iv_articulos_empresa.id_casilla_predeterminada_compra
						AND art_alm.id_articulo = iv_articulos_empresa.id_articulo
						AND art_alm.estatus = 1) = 0));",
		valTpDato($idArticulo, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	return array(true, "");
}

function actualizarReservada($idArticulo = "", $idCasilla = "", $idCasillaAnt = "") {
	global $conex;
	
	($idCasilla != "-1" && $idCasilla != "") ? $arrayIdCasilla[] = $idCasilla : "";
	($idCasillaAnt != "-1" && $idCasillaAnt != "") ? $arrayIdCasilla[] = $idCasillaAnt : "";
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (RESERVADAS)
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("iv_articulos_almacen.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	if (count($arrayIdCasilla) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("iv_articulos_almacen.id_casilla IN (%s)",
			valTpDato(implode(",",$arrayIdCasilla), "campo"));
	}
	
	$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
		cantidad_reservada = (SELECT COUNT(det_orden_art.id_articulo) AS cantidad_reservada
							FROM sa_det_solicitud_repuestos det_solicitud_rep
								JOIN sa_det_orden_articulo det_orden_art ON (det_solicitud_rep.id_det_orden_articulo = det_orden_art.id_det_orden_articulo)
							WHERE det_orden_art.id_articulo = iv_articulos_almacen.id_articulo
								AND det_solicitud_rep.id_casilla = iv_articulos_almacen.id_casilla
								AND det_solicitud_rep.id_estado_solicitud IN (2,3)) %s;", $sqlBusq);
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	
	$sqlBusq = " "; // ESPACIO EN BLANCO PORQUE LA PRIMERA CONDICION ESTA EN LA CONSULTA
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_alm.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	// VERIFICA LAS UBICACIONES DEL ARTICULO TENGAN LOS SALDOS INCORRECTOS
	$queryArtAlm = sprintf("SELECT * FROM iv_articulos_almacen art_alm
	WHERE ((art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_espera) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_bloqueada) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada - art_alm.cantidad_espera - art_alm.cantidad_bloqueada) < 0)
		AND (art_alm.estatus = 1
			OR art_alm.cantidad_reservada > 0) %s", $sqlBusq);
	$rsArtAlm = mysql_query($queryArtAlm);
	if (!$rsArtAlm) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
	while ($rowArtAlm = mysql_fetch_assoc($rsArtAlm)) {
		$queryArticulo = sprintf("SELECT *
		FROM vw_iv_articulos_almacen vw_iv_art_alm
			INNER JOIN iv_articulos art ON (vw_iv_art_alm.id_articulo = art.id_articulo)
		WHERE vw_iv_art_alm.id_articulo_almacen = %s;",
			valTpDato($rowArtAlm['id_articulo_almacen'], "int"));
		$rsArticulo = mysql_query($queryArticulo);
		if (!$rsArticulo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$totalRowsArticulo = mysql_num_rows($rsArticulo);
		$rowArticulo = mysql_fetch_assoc($rsArticulo);
		
		$arrayAlmacenInvalido[] = "Id Articulo Almacen: ".$rowArtAlm['id_articulo_almacen']." =".
			" Id Articulo: ".$rowArticulo['id_articulo'].
			" Id Casilla: ".$rowArtAlm['id_casilla']." -".
			" ".elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";")." (".$rowArticulo['descripcion_almacen']." ".$rowArticulo['ubicacion'].")";
	}
	if ($totalRowsArtAlm > 0) { return array(false, "La(s) ubicacion(es) Id.\n ".implode("\n ", $arrayAlmacenInvalido)." posee saldos inválidos.\nLine: ".__LINE__); }
	
	
	
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (RESERVADAS)
	$sqlBusq = "";
	/*$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("iv_articulos_costos.estatus = 1");*/
		
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("iv_articulos_costos.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	$updateSQL = sprintf("UPDATE iv_articulos_costos SET
		cantidad_reservada = (SELECT COUNT(det_orden_art.id_articulo) AS cantidad_reservada
							FROM sa_det_solicitud_repuestos det_solicitud_rep
								INNER JOIN sa_det_orden_articulo det_orden_art ON (det_solicitud_rep.id_det_orden_articulo = det_orden_art.id_det_orden_articulo)
							WHERE det_orden_art.id_articulo_costo = iv_articulos_costos.id_articulo_costo 
								AND det_solicitud_rep.id_estado_solicitud IN (2,3)) %s;", $sqlBusq);
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	
	$sqlBusq = " "; // ESPACIO EN BLANCO PORQUE LA PRIMERA CONDICION ESTA EN LA CONSULTA
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_costo.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	// VERIFICA LOS LOTES DEL ARTICULO TENGAN LOS SALDOS INCORRECTOS
	$queryArtCosto = sprintf("SELECT * FROM iv_articulos_costos art_costo
	WHERE ((art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida) < 0
			OR (art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida - art_costo.cantidad_reservada) < 0
			OR (art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida - art_costo.cantidad_espera) < 0
			OR (art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida - art_costo.cantidad_bloqueada) < 0
			OR (art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida - art_costo.cantidad_reservada - art_costo.cantidad_espera - art_costo.cantidad_bloqueada) < 0)
		AND (art_costo.estatus = 1
			OR art_costo.cantidad_reservada > 0) %s", $sqlBusq);
	$rsArtCosto = mysql_query($queryArtCosto);
	if (!$rsArtCosto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
	while ($rowArtCosto = mysql_fetch_assoc($rsArtCosto)) {
		$arrayLoteInvalido[] = $rowArtCosto['id_articulo_costo'];
	}
	if ($totalRowsArtCosto > 0) { return array(false, "El(Los) lote(s) Nro. ".implode(", ", $arrayLoteInvalido)." posee saldos inválidos.\nLine: ".__LINE__); }
	
	
	
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (RESERVADAS)
	$sqlBusq = "";
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_almacen.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	$updateSQL = sprintf("UPDATE iv_articulos_almacen art_almacen
		INNER JOIN iv_articulos_almacen_costo art_almacen_costo ON (art_almacen.id_articulo_almacen = art_almacen_costo.id_articulo_almacen) SET
		art_almacen_costo.cantidad_reservada = (SELECT COUNT(det_orden_art.id_articulo) AS cantidad_reservada
										FROM sa_det_solicitud_repuestos det_solicitud_rep
											INNER JOIN sa_det_orden_articulo det_orden_art ON (det_solicitud_rep.id_det_orden_articulo = det_orden_art.id_det_orden_articulo)
										WHERE det_orden_art.id_articulo = art_almacen.id_articulo
											AND det_solicitud_rep.id_casilla = art_almacen.id_casilla
											AND det_orden_art.id_articulo_costo = art_almacen_costo.id_articulo_costo
											AND det_solicitud_rep.id_estado_solicitud IN (2,3)) %s;", $sqlBusq);
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	
	$sqlBusq = " "; // ESPACIO EN BLANCO PORQUE LA PRIMERA CONDICION ESTA EN LA CONSULTA
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_costo.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	// VERIFICA LA UBICACION DE LOS LOTES DEL ARTICULO TENGAN LOS SALDOS INCORRECTOS
	$queryArtCosto = sprintf("SELECT *
	FROM iv_articulos_almacen_costo art_almacen_costo
		INNER JOIN iv_articulos_costos art_costo ON (art_almacen_costo.id_articulo_costo = art_costo.id_articulo_costo)
	WHERE ((art_almacen_costo.cantidad_inicio + art_almacen_costo.cantidad_entrada - art_almacen_costo.cantidad_salida) < 0
			OR (art_almacen_costo.cantidad_inicio + art_almacen_costo.cantidad_entrada - art_almacen_costo.cantidad_salida - art_almacen_costo.cantidad_reservada) < 0
			OR (art_almacen_costo.cantidad_inicio + art_almacen_costo.cantidad_entrada - art_almacen_costo.cantidad_salida - art_almacen_costo.cantidad_espera) < 0
			OR (art_almacen_costo.cantidad_inicio + art_almacen_costo.cantidad_entrada - art_almacen_costo.cantidad_salida - art_almacen_costo.cantidad_bloqueada) < 0
			OR (art_almacen_costo.cantidad_inicio + art_almacen_costo.cantidad_entrada - art_almacen_costo.cantidad_salida - art_almacen_costo.cantidad_reservada - art_almacen_costo.cantidad_espera - art_almacen_costo.cantidad_bloqueada) < 0)
		AND (art_almacen_costo.estatus = 1
			OR art_almacen_costo.cantidad_reservada > 0) %s", $sqlBusq);
	$rsArtCosto = mysql_query($queryArtCosto);
	if (!$rsArtCosto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
	while ($rowArtCosto = mysql_fetch_assoc($rsArtCosto)) {
		$arrayLoteInvalido[] = $rowArtCosto['id_articulo_costo'];
	}
	if ($totalRowsArtCosto > 0) { return array(false, "La(Las) relacion(es) ubicación/lote(s) Id. ".implode(", ", $arrayLoteInvalido)." posee saldos inválidos.\nLine: ".__LINE__); }
	
	return array(true, "");
}

function actualizacionEsperaPorFacturar($idArticulo = "", $idCasilla = "", $idCasillaAnt = "") {
	global $conex;
	
	($idCasilla != "-1" && $idCasilla != "") ? $arrayIdCasilla[] = $idCasilla : "";
	($idCasillaAnt != "-1" && $idCasillaAnt != "") ? $arrayIdCasilla[] = $idCasillaAnt : "";
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (ESPERA)
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("iv_articulos_almacen.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	if (count($arrayIdCasilla) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("iv_articulos_almacen.id_casilla IN (%s)",
			valTpDato(implode(",",$arrayIdCasilla), "campo"));
	}
	
	$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
		cantidad_espera = (IFNULL((SELECT SUM(iv_ped_vent_det.pendiente)
									FROM iv_pedido_venta_detalle iv_ped_vent_det
										JOIN iv_pedido_venta iv_ped_vent ON (iv_ped_vent_det.id_pedido_venta = iv_ped_vent.id_pedido_venta)
									WHERE iv_ped_vent_det.id_articulo = iv_articulos_almacen.id_articulo
										AND iv_ped_vent_det.id_casilla = iv_articulos_almacen.id_casilla
										AND iv_ped_vent_det.estatus IN (0,1)
										AND iv_ped_vent.estatus_pedido_venta IN (0,1,2)), 0)) %s;", $sqlBusq);
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	
	$sqlBusq = " "; // ESPACIO EN BLANCO PORQUE LA PRIMERA CONDICION ESTA EN LA CONSULTA
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_alm.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	// VERIFICA LAS UBICACIONES DEL ARTICULO TENGAN LOS SALDOS INCORRECTOS
	$queryArtAlm = sprintf("SELECT * FROM iv_articulos_almacen art_alm
	WHERE ((art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_espera) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_bloqueada) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada - art_alm.cantidad_espera - art_alm.cantidad_bloqueada) < 0)
		AND (art_alm.estatus = 1
			OR art_alm.cantidad_reservada > 0) %s", $sqlBusq);
	$rsArtAlm = mysql_query($queryArtAlm);
	if (!$rsArtAlm) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
	while ($rowArtAlm = mysql_fetch_assoc($rsArtAlm)) {
		$queryArticulo = sprintf("SELECT *
		FROM vw_iv_articulos_almacen vw_iv_art_alm
			INNER JOIN iv_articulos art ON (vw_iv_art_alm.id_articulo = art.id_articulo)
		WHERE vw_iv_art_alm.id_articulo_almacen = %s;",
			valTpDato($rowArtAlm['id_articulo_almacen'], "int"));
		$rsArticulo = mysql_query($queryArticulo);
		if (!$rsArticulo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$totalRowsArticulo = mysql_num_rows($rsArticulo);
		$rowArticulo = mysql_fetch_assoc($rsArticulo);
		
		$arrayAlmacenInvalido[] = "Id Articulo Almacen: ".$rowArtAlm['id_articulo_almacen']." =".
			" Id Articulo: ".$rowArticulo['id_articulo'].
			" Id Casilla: ".$rowArtAlm['id_casilla']." -".
			" ".elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";")." (".$rowArticulo['descripcion_almacen']." ".$rowArticulo['ubicacion'].")";
	}
	if ($totalRowsArtAlm > 0) { return array(false, "La(s) ubicacion(es) Id.\n ".implode("\n ", $arrayAlmacenInvalido)." posee saldos inválidos.\nLine: ".__LINE__); }
	
	
	
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (ESPERA)
	$sqlBusq = "";
	/*$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("iv_articulos_costos.estatus = 1");*/
		
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("iv_articulos_costos.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	$updateSQL = sprintf("UPDATE iv_articulos_costos SET
		cantidad_espera = (IFNULL((SELECT SUM(iv_ped_vent_det.pendiente)
									FROM iv_pedido_venta_detalle iv_ped_vent_det
										JOIN iv_pedido_venta iv_ped_vent ON (iv_ped_vent_det.id_pedido_venta = iv_ped_vent.id_pedido_venta)
									WHERE iv_ped_vent_det.id_articulo_costo = iv_articulos_costos.id_articulo_costo
										AND iv_ped_vent_det.estatus IN (0,1)
										AND iv_ped_vent.estatus_pedido_venta IN (0,1,2)), 0)) %s;", $sqlBusq);
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	
	$sqlBusq = " "; // ESPACIO EN BLANCO PORQUE LA PRIMERA CONDICION ESTA EN LA CONSULTA
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_costo.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	// VERIFICA LOS LOTES DEL ARTICULO TENGAN LOS SALDOS INCORRECTOS
	$queryArtCosto = sprintf("SELECT * FROM iv_articulos_costos art_costo
	WHERE ((art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida) < 0
			OR (art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida - art_costo.cantidad_reservada) < 0
			OR (art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida - art_costo.cantidad_espera) < 0
			OR (art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida - art_costo.cantidad_bloqueada) < 0
			OR (art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida - art_costo.cantidad_reservada - art_costo.cantidad_espera - art_costo.cantidad_bloqueada) < 0)
		AND (art_costo.estatus = 1
			OR art_costo.cantidad_reservada > 0) %s", $sqlBusq);
	$rsArtCosto = mysql_query($queryArtCosto);
	if (!$rsArtCosto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
	while ($rowArtCosto = mysql_fetch_assoc($rsArtCosto)) {
		$arrayLoteInvalido[] = $rowArtCosto['id_articulo_costo'];
	}
	if ($totalRowsArtCosto > 0) { return array(false, "El(Los) lote(s) Nro. ".implode(", ", $arrayLoteInvalido)." posee saldos inválidos.\nLine: ".__LINE__); }
	
	
	
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (ESPERA)
	$sqlBusq = "";
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_almacen.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	$updateSQL = sprintf("UPDATE iv_articulos_almacen art_almacen
		INNER JOIN iv_articulos_almacen_costo art_almacen_costo ON (art_almacen.id_articulo_almacen = art_almacen_costo.id_articulo_almacen) SET
		art_almacen_costo.cantidad_espera = (IFNULL((SELECT SUM(iv_ped_vent_det.pendiente)
													FROM iv_pedido_venta_detalle iv_ped_vent_det
														JOIN iv_pedido_venta iv_ped_vent ON (iv_ped_vent_det.id_pedido_venta = iv_ped_vent.id_pedido_venta)
													WHERE iv_ped_vent_det.id_articulo = art_almacen.id_articulo
														AND iv_ped_vent_det.id_casilla = art_almacen.id_casilla
														AND iv_ped_vent_det.id_articulo_costo = art_almacen_costo.id_articulo_costo
														AND iv_ped_vent_det.estatus IN (0,1)
														AND iv_ped_vent.estatus_pedido_venta IN (0,1,2)), 0)) %s;", $sqlBusq);
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	
	$sqlBusq = " "; // ESPACIO EN BLANCO PORQUE LA PRIMERA CONDICION ESTA EN LA CONSULTA
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_costo.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	// VERIFICA LA UBICACION DE LOS LOTES DEL ARTICULO TENGAN LOS SALDOS INCORRECTOS
	$queryArtCosto = sprintf("SELECT *
	FROM iv_articulos_almacen_costo art_almacen_costo
		INNER JOIN iv_articulos_costos art_costo ON (art_almacen_costo.id_articulo_costo = art_costo.id_articulo_costo)
	WHERE ((art_almacen_costo.cantidad_inicio + art_almacen_costo.cantidad_entrada - art_almacen_costo.cantidad_salida) < 0
			OR (art_almacen_costo.cantidad_inicio + art_almacen_costo.cantidad_entrada - art_almacen_costo.cantidad_salida - art_almacen_costo.cantidad_reservada) < 0
			OR (art_almacen_costo.cantidad_inicio + art_almacen_costo.cantidad_entrada - art_almacen_costo.cantidad_salida - art_almacen_costo.cantidad_espera) < 0
			OR (art_almacen_costo.cantidad_inicio + art_almacen_costo.cantidad_entrada - art_almacen_costo.cantidad_salida - art_almacen_costo.cantidad_bloqueada) < 0
			OR (art_almacen_costo.cantidad_inicio + art_almacen_costo.cantidad_entrada - art_almacen_costo.cantidad_salida - art_almacen_costo.cantidad_reservada - art_almacen_costo.cantidad_espera - art_almacen_costo.cantidad_bloqueada) < 0)
		AND (art_almacen_costo.estatus = 1
			OR art_almacen_costo.cantidad_reservada > 0) %s", $sqlBusq);
	$rsArtCosto = mysql_query($queryArtCosto);
	if (!$rsArtCosto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
	while ($rowArtCosto = mysql_fetch_assoc($rsArtCosto)) {
		$arrayLoteInvalido[] = $rowArtCosto['id_articulo_costo'];
	}
	if ($totalRowsArtCosto > 0) { return array(false, "La(Las) relacion(es) ubicación/lote(s) Id. ".implode(", ", $arrayLoteInvalido)." posee saldos inválidos.\nLine: ".__LINE__); }
	
	return array(true, "");
}

function actualizarBloqueada($idArticulo = "", $idCasilla = "", $idCasillaAnt = "") {
	global $conex;
	
	($idCasilla != "-1" && $idCasilla != "") ? $arrayIdCasilla[] = $idCasilla : "";
	($idCasillaAnt != "-1" && $idCasillaAnt != "") ? $arrayIdCasilla[] = $idCasillaAnt : "";
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (BLOQUEADA)
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("iv_articulos_almacen.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	if (count($arrayIdCasilla) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("iv_articulos_almacen.id_casilla IN (%s)",
			valTpDato(implode(",",$arrayIdCasilla), "campo"));
	}
	
	$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
		cantidad_bloqueada = (SELECT SUM(bloqueo_vent_det.cantidad)
								FROM iv_bloqueo_venta_detalle bloqueo_vent_det
								WHERE bloqueo_vent_det.id_articulo = iv_articulos_almacen.id_articulo
									AND bloqueo_vent_det.id_casilla = iv_articulos_almacen.id_casilla
									AND bloqueo_vent_det.estatus IN (1,3)) %s;", $sqlBusq);
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	
	$sqlBusq = " "; // ESPACIO EN BLANCO PORQUE LA PRIMERA CONDICION ESTA EN LA CONSULTA
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_alm.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	// VERIFICA LAS UBICACIONES DEL ARTICULO TENGAN LOS SALDOS INCORRECTOS
	$queryArtAlm = sprintf("SELECT * FROM iv_articulos_almacen art_alm
	WHERE ((art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_espera) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_bloqueada) < 0
			OR (art_alm.cantidad_inicio + art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada - art_alm.cantidad_espera - art_alm.cantidad_bloqueada) < 0)
		AND (art_alm.estatus = 1
			OR art_alm.cantidad_reservada > 0) %s", $sqlBusq);
	$rsArtAlm = mysql_query($queryArtAlm);
	if (!$rsArtAlm) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
	while ($rowArtAlm = mysql_fetch_assoc($rsArtAlm)) {
		$queryArticulo = sprintf("SELECT *
		FROM vw_iv_articulos_almacen vw_iv_art_alm
			INNER JOIN iv_articulos art ON (vw_iv_art_alm.id_articulo = art.id_articulo)
		WHERE vw_iv_art_alm.id_articulo_almacen = %s;",
			valTpDato($rowArtAlm['id_articulo_almacen'], "int"));
		$rsArticulo = mysql_query($queryArticulo);
		if (!$rsArticulo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$totalRowsArticulo = mysql_num_rows($rsArticulo);
		$rowArticulo = mysql_fetch_assoc($rsArticulo);
		
		$arrayAlmacenInvalido[] = "Id Articulo Almacen: ".$rowArtAlm['id_articulo_almacen']." =".
			" Id Articulo: ".$rowArticulo['id_articulo'].
			" Id Casilla: ".$rowArtAlm['id_casilla']." -".
			" ".elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";")." (".$rowArticulo['descripcion_almacen']." ".$rowArticulo['ubicacion'].")";
	}
	if ($totalRowsArtAlm > 0) { return array(false, "La(s) ubicacion(es) Id.\n ".implode("\n ", $arrayAlmacenInvalido)." posee saldos inválidos.\nLine: ".__LINE__); }
	
	
	
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (BLOQUEADA)
	$sqlBusq = "";
	/*$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("iv_articulos_costos.estatus = 1");*/
		
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("iv_articulos_costos.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	$updateSQL = sprintf("UPDATE iv_articulos_costos SET
		cantidad_bloqueada = (SELECT SUM(bloqueo_vent_det.cantidad)
								FROM iv_bloqueo_venta_detalle bloqueo_vent_det
								WHERE bloqueo_vent_det.id_articulo_costo = iv_articulos_costos.id_articulo_costo
									AND bloqueo_vent_det.estatus IN (1,3)) %s;", $sqlBusq);
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	
	$sqlBusq = " "; // ESPACIO EN BLANCO PORQUE LA PRIMERA CONDICION ESTA EN LA CONSULTA
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_costo.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	// VERIFICA LOS LOTES DEL ARTICULO TENGAN LOS SALDOS INCORRECTOS
	$queryArtCosto = sprintf("SELECT * FROM iv_articulos_costos art_costo
	WHERE ((art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida) < 0
			OR (art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida - art_costo.cantidad_reservada) < 0
			OR (art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida - art_costo.cantidad_espera) < 0
			OR (art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida - art_costo.cantidad_bloqueada) < 0
			OR (art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida - art_costo.cantidad_reservada - art_costo.cantidad_espera - art_costo.cantidad_bloqueada) < 0)
		AND (art_costo.estatus = 1
			OR art_costo.cantidad_reservada > 0) %s", $sqlBusq);
	$rsArtCosto = mysql_query($queryArtCosto);
	if (!$rsArtCosto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
	while ($rowArtCosto = mysql_fetch_assoc($rsArtCosto)) {
		$arrayLoteInvalido[] = $rowArtCosto['id_articulo_costo'];
	}
	if ($totalRowsArtCosto > 0) { return array(false, "El(Los) lote(s) Nro. ".implode(", ", $arrayLoteInvalido)." posee saldos inválidos.\nLine: ".__LINE__); }
	
	
	
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (BLOQUEADA)
	$sqlBusq = "";
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_almacen.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	$updateSQL = sprintf("UPDATE iv_articulos_almacen art_almacen
		INNER JOIN iv_articulos_almacen_costo art_almacen_costo ON (art_almacen.id_articulo_almacen = art_almacen_costo.id_articulo_almacen) SET
		art_almacen_costo.cantidad_bloqueada = (SELECT SUM(bloqueo_vent_det.cantidad)
										FROM iv_bloqueo_venta_detalle bloqueo_vent_det
										WHERE bloqueo_vent_det.id_articulo = art_almacen.id_articulo
											AND bloqueo_vent_det.id_casilla = art_almacen.id_casilla
											AND bloqueo_vent_det.id_articulo_costo = art_almacen_costo.id_articulo_costo
											AND bloqueo_vent_det.estatus IN (1,3)) %s;", $sqlBusq);
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	
	$sqlBusq = " "; // ESPACIO EN BLANCO PORQUE LA PRIMERA CONDICION ESTA EN LA CONSULTA
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_costo.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	// VERIFICA LA UBICACION DE LOS LOTES DEL ARTICULO TENGAN LOS SALDOS INCORRECTOS
	$queryArtCosto = sprintf("SELECT *
	FROM iv_articulos_almacen_costo art_almacen_costo
		INNER JOIN iv_articulos_costos art_costo ON (art_almacen_costo.id_articulo_costo = art_costo.id_articulo_costo)
	WHERE ((art_almacen_costo.cantidad_inicio + art_almacen_costo.cantidad_entrada - art_almacen_costo.cantidad_salida) < 0
			OR (art_almacen_costo.cantidad_inicio + art_almacen_costo.cantidad_entrada - art_almacen_costo.cantidad_salida - art_almacen_costo.cantidad_reservada) < 0
			OR (art_almacen_costo.cantidad_inicio + art_almacen_costo.cantidad_entrada - art_almacen_costo.cantidad_salida - art_almacen_costo.cantidad_espera) < 0
			OR (art_almacen_costo.cantidad_inicio + art_almacen_costo.cantidad_entrada - art_almacen_costo.cantidad_salida - art_almacen_costo.cantidad_bloqueada) < 0
			OR (art_almacen_costo.cantidad_inicio + art_almacen_costo.cantidad_entrada - art_almacen_costo.cantidad_salida - art_almacen_costo.cantidad_reservada - art_almacen_costo.cantidad_espera - art_almacen_costo.cantidad_bloqueada) < 0)
		AND (art_almacen_costo.estatus = 1
			OR art_almacen_costo.cantidad_reservada > 0) %s", $sqlBusq);
	$rsArtCosto = mysql_query($queryArtCosto);
	if (!$rsArtCosto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
	while ($rowArtCosto = mysql_fetch_assoc($rsArtCosto)) {
		$arrayLoteInvalido[] = $rowArtCosto['id_articulo_costo'];
	}
	if ($totalRowsArtCosto > 0) { return array(false, "La(Las) relacion(es) ubicación/lote(s) Id. ".implode(", ", $arrayLoteInvalido)." posee saldos inválidos.\nLine: ".__LINE__); }
	
	return array(true, "");
}

function actualizarLote($idArticulo = "", $idEmpresa = "", $idCasilla = "", $tipoActualizacion = "COMPRA") {
	global $conex;
	
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	// BUSCA QUE EMPRESAS TIENE DICHO ARTICULO
	$queryArtEmp = sprintf("SELECT * FROM iv_articulos_empresa %s;", $sqlBusq);
	$rsArtEmp = mysql_query($queryArtEmp);
	if (!$rsArtEmp) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	while ($rowArtEmp = mysql_fetch_assoc($rsArtEmp)) {
		$idEmpresa = $rowArtEmp['id_empresa'];
		$idArticulo = $rowArtEmp['id_articulo'];
		
		// BUSCA EL ULTIMO COSTO DE LA PIEZA, EMPRESA Y LA FECHA DE LA ULTIMA COMPRA
		$queryArticuloCosto = sprintf("SELECT art_costo.*,
			(SELECT DATE(kardex.fecha_movimiento) FROM iv_kardex kardex
			WHERE kardex.tipo_movimiento IN (1)
				AND kardex.id_articulo = art_costo.id_articulo
			LIMIT 1) AS fecha_movimiento
		FROM iv_articulos_costos art_costo
		WHERE art_costo.id_articulo = %s
			AND art_costo.id_empresa = %s
		ORDER BY art_costo.id_articulo_costo DESC
		LIMIT 1;",
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"));
		$rsArticuloCosto = mysql_query($queryArticuloCosto);
		if (!$rsArticuloCosto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$rowArticuloCosto = mysql_fetch_assoc($rsArticuloCosto);
		
		$idArticuloCosto = $rowArticuloCosto['id_articulo_costo'];
		
		// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
		$ResultConfig12 = valorConfiguracion(12, $idEmpresa);
		if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
			return array(false, $ResultConfig12[1]);
		} else if ($ResultConfig12[0] == true) {
			$ResultConfig12 = $ResultConfig12[1];
		}
		
		if (in_array($ResultConfig12, array(1,2))) { // 1 = Reposicion, 2 = Promedio, 3 = FIFO
			// IDENTIFICO EL ULTIMO LOTE DEL ARTICULO EN LA EMPRESA
			$queryArticuloAlmacenCosto = sprintf("SELECT
				art_almacen_costo.id_articulo_almacen_costo,
				art_almacen_costo.id_articulo_almacen,
				art_almacen_costo.id_articulo_costo
			FROM iv_articulos_almacen art_almacen
				INNER JOIN iv_articulos_almacen_costo art_almacen_costo ON (art_almacen.id_articulo_almacen = art_almacen_costo.id_articulo_almacen)
				INNER JOIN iv_casillas casilla ON (art_almacen.id_casilla = casilla.id_casilla)
				INNER JOIN iv_tramos tramo ON (tramo.id_tramo = casilla.id_tramo)
				INNER JOIN iv_estantes estante ON (estante.id_estante = tramo.id_estante)
				INNER JOIN iv_calles calle ON (calle.id_calle = estante.id_calle)
				INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
			WHERE art_almacen.id_articulo = %s
				AND almacen.id_empresa  = %s
				AND art_almacen_costo.estatus = 1
			ORDER BY id_articulo_costo DESC;",
				valTpDato($idArticulo, "int"),
				valTpDato($idEmpresa, "int"));
			$rsArticuloAlmacenCosto = mysql_query($queryArticuloAlmacenCosto);
			if (!$rsArticuloAlmacenCosto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
			$rowArticuloAlmacenCosto = mysql_fetch_assoc($rsArticuloAlmacenCosto);
			
			$idArticuloAlmacenCostoLoteInvalido = $rowArticuloAlmacenCosto['id_articulo_almacen_costo'];
			$idArticuloAlmacen = $rowArticuloAlmacenCosto['id_articulo_almacen'];
			$idArticuloCostoLoteInvalido = $rowArticuloAlmacenCosto['id_articulo_costo'];
			
			// VERIFICA QUE SEA UN LOTE NUEVO (QUE EN ESTATUS SEA NULL)
			if (($tipoActualizacion == "COMPRA" && $rowArticuloCosto['estatus'] != 1)
			|| ($tipoActualizacion != "COMPRA")) {
				// IDENTIFICO LAS UBICACIONES DE LOS LOTES ANTERIORES
				$queryArticuloAlmacenCosto = sprintf("SELECT
					art_almacen_costo.id_articulo_almacen_costo,
					art_almacen_costo.id_articulo_almacen,
					art_almacen_costo.id_articulo_costo
				FROM iv_articulos_almacen art_almacen
					INNER JOIN iv_articulos_almacen_costo art_almacen_costo ON (art_almacen.id_articulo_almacen = art_almacen_costo.id_articulo_almacen)
					INNER JOIN iv_casillas casilla ON (art_almacen.id_casilla = casilla.id_casilla)
					INNER JOIN iv_tramos tramo ON (tramo.id_tramo = casilla.id_tramo)
					INNER JOIN iv_estantes estante ON (estante.id_estante = tramo.id_estante)
					INNER JOIN iv_calles calle ON (calle.id_calle = estante.id_calle)
					INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
				WHERE art_almacen.id_articulo = %s
					AND almacen.id_empresa  = %s
					AND art_almacen_costo.id_articulo_costo <> %s
					AND art_almacen_costo.estatus = 1;",
					valTpDato($idArticulo, "int"),
					valTpDato($idEmpresa, "int"),
					valTpDato($idArticuloCostoLoteInvalido, "int"));
				$rsArticuloAlmacenCosto = mysql_query($queryArticuloAlmacenCosto);
				if (!$rsArticuloAlmacenCosto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
				$totalRowsArticuloAlmacenCosto = mysql_num_rows($rsArticuloAlmacenCosto);
				while ($rowArticuloAlmacenCosto = mysql_fetch_assoc($rsArticuloAlmacenCosto)) {
					// LE ACTUALIZA EL LOTE A MANEJAR A LAS UBICACIONES
					$updateSQL = sprintf("UPDATE iv_articulos_almacen_costo SET
						id_articulo_costo = %s
					WHERE id_articulo_almacen_costo = %s;",
						valTpDato($idArticuloCostoLoteInvalido, "int"),
						valTpDato($rowArticuloAlmacenCosto['id_articulo_almacen_costo'], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
					mysql_query("SET NAMES 'latin1';");
					
					// ACTUALIZA EL LOTE A MANEJAR AL DETALLE DE LOS PRESUPUESTOS PENDIENTES
					$updateSQL = sprintf("UPDATE iv_presupuesto_venta_detalle SET
						id_articulo_costo  = %s
					WHERE id_articulo_costo = %s
						AND id_presupuesto_venta IN (SELECT id_presupuesto_venta FROM iv_presupuesto_venta
												WHERE estatus_presupuesto_venta IN (0));",
						valTpDato($idArticuloCostoLoteInvalido, "int"),
						valTpDato($rowArticuloAlmacenCosto['id_articulo_costo'], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
					mysql_query("SET NAMES 'latin1';");
					
					// ACTUALIZA EL LOTE A MANEJAR AL DETALLE DE LOS PEDIDOS PENDIENTES
					$updateSQL = sprintf("UPDATE iv_pedido_venta_detalle SET
						id_articulo_costo  = %s
					WHERE id_articulo_almacen_costo = %s
						AND id_articulo_costo = %s
						AND id_pedido_venta IN (SELECT id_pedido_venta FROM iv_pedido_venta
												WHERE estatus_pedido_venta IN (0,1,2));",
						valTpDato($idArticuloCostoLoteInvalido, "int"),
						valTpDato($rowArticuloAlmacenCosto['id_articulo_almacen_costo'], "int"),
						valTpDato($rowArticuloAlmacenCosto['id_articulo_costo'], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
					mysql_query("SET NAMES 'latin1';");
					
					// ACTUALIZA EL LOTE A MANEJAR AL DETALLE DE LAS ORDENES PENDIENTES
					$updateSQL = sprintf("UPDATE sa_det_orden_articulo SET
						id_articulo_costo  = %s
					WHERE id_articulo_almacen_costo = %s
						AND id_articulo_costo = %s
						AND id_orden IN (SELECT id_orden FROM sa_orden
										WHERE id_estado_orden NOT IN (18,24));",
						valTpDato($idArticuloCostoLoteInvalido, "int"),
						valTpDato($rowArticuloAlmacenCosto['id_articulo_almacen_costo'], "int"),
						valTpDato($rowArticuloAlmacenCosto['id_articulo_costo'], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
					mysql_query("SET NAMES 'latin1';");
					
					// ACTUALIZA EL LOTE A MANEJAR AL DETALLE DE LAS SOLICITUDES PENDIENTES
					$updateSQL = sprintf("UPDATE ".DBASE_SIGSO.".detalle_pedido SET
						id_articulo_costo  = %s
					WHERE id_articulo_costo = %s
						AND id_pedido IN (SELECT id_pedido FROM ".DBASE_SIGSO.".encabezado_pedido
										WHERE estatus IN (1,2,3,4,5));",
						valTpDato($idArticuloCostoLoteInvalido, "int"),
						valTpDato($rowArticuloAlmacenCosto['id_articulo_costo'], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) {
						if (mysql_errno() != 1146) { // SI EL ERROR ES DISTINTO (QUE NO EXISTE LA TABLA)
							return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
						}
					}
					mysql_query("SET NAMES 'latin1';");
					
					if ($idArticuloAlmacen == $rowArticuloAlmacenCosto['id_articulo_almacen']) {
						// ACTUALIZA LA UBICACION DEL LOTE EN EL KARDEX
						$updateSQL = sprintf("UPDATE iv_kardex SET
							id_articulo_almacen_costo = %s
						WHERE id_articulo_almacen_costo = %s
							AND id_articulo_costo = %s;",
							valTpDato($rowArticuloAlmacenCosto['id_articulo_almacen_costo'], "int"),
							valTpDato($idArticuloAlmacenCostoLoteInvalido, "int"),
							valTpDato($idArticuloCostoLoteInvalido, "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
						mysql_query("SET NAMES 'latin1';");
						
						// ACTUALIZA LA UBICACION DEL LOTE EN EL DETALLE DEL MOVIMIENTO
						$updateSQL = sprintf("UPDATE iv_movimiento_detalle SET
							id_articulo_almacen_costo = %s
						WHERE id_articulo_almacen_costo = %s
							AND id_articulo_costo = %s;",
							valTpDato($rowArticuloAlmacenCosto['id_articulo_almacen_costo'], "int"),
							valTpDato($idArticuloAlmacenCostoLoteInvalido, "int"),
							valTpDato($idArticuloCostoLoteInvalido, "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
						mysql_query("SET NAMES 'latin1';");
						
						if ($tipoActualizacion == "COMPRA") {
							// ELIMINA LA UBICACION DEL LOTE INVALIDO YA QUE LA RELACION EXISTE PORQUE SE MOFICO ANTERIORMENTE
							$deleteSQL = sprintf("DELETE FROM iv_articulos_almacen_costo
							WHERE id_articulo_almacen_costo = %s
								AND id_articulo_costo = %s;",
								valTpDato($idArticuloAlmacenCostoLoteInvalido, "int"),
								valTpDato($idArticuloCostoLoteInvalido, "int"));
							$Result1 = mysql_query($deleteSQL);
							if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__)."\n".$deleteSQL);
						}
					}
				}
				
				// RECOGE LA EXISTENCIA DISPONIBLE DE LOS ULTIMOS LOTES QUE ESTAN ACTIVOS
				$queryArtCosto = sprintf("SELECT
					SUM(IFNULL(art_costo.cantidad_inicio, 0)) AS cantidad_inicio,
					SUM(IFNULL(art_costo.cantidad_inicio, 0) + IFNULL(art_costo.cantidad_entrada, 0) - IFNULL(art_costo.cantidad_salida, 0)) AS cant_existencia
				FROM iv_articulos_costos art_costo
				WHERE art_costo.id_articulo = %s
					AND art_costo.id_empresa = %s
					AND art_costo.estatus = 1
					AND art_costo.id_articulo_costo <> %s
				GROUP BY art_costo.id_articulo, art_costo.id_empresa
				ORDER BY id_articulo_costo DESC;",
					valTpDato($idArticulo, "int"),
					valTpDato($idEmpresa, "int"),
					valTpDato($idArticuloCosto, "int"));
				$rsArtCosto = mysql_query($queryArtCosto);
				if (!$rsArtCosto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
				$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
				$rowArtCosto = mysql_fetch_assoc($rsArtCosto);
				
				// LE ASIGNA LA EXISTENCIA AL ULTIMO LOTE
				$updateSQL = sprintf("UPDATE iv_articulos_costos SET
					cantidad_inicio = %s
				WHERE id_articulo_costo = %s;",
					valTpDato($rowArtCosto['cant_existencia'], "real_inglesa"),
					valTpDato($idArticuloCosto, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
				mysql_query("SET NAMES 'latin1';");
				
				// INACTIVA LOS LOTES ANTERIORES DEL ARTICULO EN LA EMPRESA
				$updateSQL = sprintf("UPDATE iv_articulos_costos SET
					estatus = NULL
				WHERE id_articulo = %s
					AND id_empresa = %s
					AND id_articulo_costo <> %s;",
					valTpDato($idArticulo, "int"),
					valTpDato($idEmpresa, "int"),
					valTpDato($idArticuloCosto, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
				mysql_query("SET NAMES 'latin1';");
			}
		}
		
		// ACTUALIZA EL ESTATUS DEL ULTIMO LOTE
		$updateSQL = sprintf("UPDATE iv_articulos_costos SET
			estatus = 1
		WHERE id_articulo_costo = %s;",
			valTpDato($idArticuloCosto, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		mysql_query("SET NAMES 'latin1';");
	}
	
	return array(true, "");
}

function actualizarMovimientoTotal($idArticulo = "", $idEmpresa = "") {
	global $conex;
	
	////////////////////////////////////////////////////////////////////////////////////////////////////
	$sqlBusq = " "; // ESPACIO EN BLANCO PORQUE LA PRIMERA CONDICION ESTA EN LA CONSULTA
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_emp.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_emp.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	// INSERTA LA RELACION DEL ARTICULO CON LA EMPRESA PRINCIPAL DE ESTA
	$insertSQL = sprintf("INSERT INTO iv_articulos_empresa (id_empresa, id_articulo)
	SELECT
		emp.id_empresa_padre,
		art_emp.id_articulo
	FROM pg_empresa emp
		INNER JOIN iv_articulos_empresa art_emp ON (emp.id_empresa = art_emp.id_empresa)
	WHERE emp.id_empresa_padre NOT IN (SELECT id_empresa FROM iv_articulos_empresa art_emp2
										WHERE art_emp2.id_articulo = art_emp.id_articulo) %s;", $sqlBusq);
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	mysql_query("SET NAMES 'latin1';");
	////////////////////////////////////////////////////////////////////////////////////////////////////
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((SELECT SUM(IF(estado = 0, cantidad, (-1) * cantidad)) AS cantidad FROM iv_kardex kardex
		WHERE kardex.id_articulo = art_emp.id_articulo
			AND ((SELECT almacen.id_empresa
					FROM iv_calles calle
						INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
						INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
						INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
						INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
					WHERE casilla.id_casilla = kardex.id_casilla) = art_emp.id_empresa
				OR (SELECT almacen.id_empresa
					FROM iv_calles calle
						INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
						INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
						INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
						INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
					WHERE casilla.id_casilla = kardex.id_casilla) IN (SELECT suc.id_empresa FROM pg_empresa suc
																		WHERE suc.id_empresa_padre = art_emp.id_empresa))) = 0
	OR art_emp.id_kardex_corte IS NULL)");
		
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT DATE(kardex.fecha_movimiento) FROM iv_kardex kardex
	WHERE kardex.id_articulo = art_emp.id_articulo
		AND ((SELECT almacen.id_empresa
				FROM iv_calles calle
					INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
					INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
					INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
					INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
				WHERE casilla.id_casilla = kardex.id_casilla) = art_emp.id_empresa
			OR (SELECT almacen.id_empresa
				FROM iv_calles calle
					INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
					INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
					INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
					INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
				WHERE casilla.id_casilla = kardex.id_casilla) IN (SELECT suc.id_empresa FROM pg_empresa suc
																	WHERE suc.id_empresa_padre = art_emp.id_empresa))
	ORDER BY CONCAT_WS(' ', kardex.fecha_movimiento, kardex.hora_movimiento) DESC
	LIMIT 1) = DATE(NOW())");
	
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_emp.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		// PARA TOMAR EN CUENTA LA EMPRESA MISMA Y LA PRINCIPAL DE ESTA
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(art_emp.id_empresa = %s
		OR art_emp.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
									WHERE suc.id_empresa = %s))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	$query = sprintf("SELECT * FROM iv_articulos_empresa art_emp %s", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$totalRows = mysql_num_rows($rs);
	while ($row = mysql_fetch_array($rs)) {
		$idTipoCorte = ($row['id_kardex_corte'] > 0) ? 1 : 2; // 1 = Saldo en Cero, 2 = Unica Compra
		
		// ULTIMO MOVIMIENTO PARA CUANDO SE PUSO EN CERO
		// PARA TOMAR EN CUENTA LA EMPRESA MISMA Y LAS HIJAS DE ESTA
		$queryKardex = sprintf("SELECT * FROM iv_kardex kardex
		WHERE kardex.id_articulo = %s
			AND ((SELECT almacen.id_empresa
					FROM iv_calles calle
						INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
						INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
						INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
						INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
					WHERE casilla.id_casilla = kardex.id_casilla) = %s
				OR (SELECT almacen.id_empresa
					FROM iv_calles calle
						INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
						INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
						INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
						INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
					WHERE casilla.id_casilla = kardex.id_casilla) IN (SELECT suc.id_empresa FROM pg_empresa suc
																		WHERE suc.id_empresa_padre = %s))
		ORDER BY CONCAT_WS(' ', DATE(kardex.fecha_movimiento), kardex.hora_movimiento) DESC, kardex.id_kardex DESC
		LIMIT 1;",
			valTpDato($row['id_articulo'], "int"),
			valTpDato($row['id_empresa'], "int"),
			valTpDato($row['id_empresa'], "int"));
		$rsKardex = mysql_query($queryKardex);
		if (!$rsKardex) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$totalRowsKardex = mysql_num_rows($rsKardex);
		$rowKardex = mysql_fetch_array($rsKardex);
		
		$fechaKardexCorte = $rowKardex['fecha_movimiento'];
		
		$updateSQL = sprintf("UPDATE iv_articulos_empresa art_emp SET
			id_kardex_corte = %s,
			fecha_kardex_corte = %s,
			id_tipo_corte = %s
		WHERE id_articulo_empresa = %s;",
			valTpDato($rowKardex['id_kardex'], "int"),
			valTpDato($fechaKardexCorte, "date"),
			valTpDato($idTipoCorte, "int"),
			valTpDato($row['id_articulo_empresa'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		mysql_query("SET NAMES 'latin1';");
	}
	
	
	$sqlBusq = "";
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_emp.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		// PARA TOMAR EN CUENTA LA EMPRESA MISMA Y LA PRINCIPAL DE ESTA
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(art_emp.id_empresa = %s
		OR art_emp.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
									WHERE suc.id_empresa = %s))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	$updateSQL = sprintf("UPDATE iv_articulos_empresa art_emp SET
		cantidad_compra = (SELECT SUM(kardex.cantidad)
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (1)
							AND kardex.id_articulo = art_emp.id_articulo
							AND ((SELECT almacen.id_empresa
									FROM iv_calles calle
										INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
										INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
										INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
										INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
									WHERE casilla.id_casilla = kardex.id_casilla) = art_emp.id_empresa
								OR (SELECT almacen.id_empresa
									FROM iv_calles calle
										INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
										INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
										INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
										INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
									WHERE casilla.id_casilla = kardex.id_casilla) IN (SELECT suc.id_empresa FROM pg_empresa suc
																						WHERE suc.id_empresa_padre = art_emp.id_empresa))
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte AND kardex.id_kardex >= art_emp.id_kardex_corte)
									OR art_emp.id_tipo_corte IN (2)))),
		
		valor_compra = (SELECT SUM(kardex.cantidad * (kardex.costo + IFNULL(kardex.costo_cargo, 0) - IFNULL(kardex.subtotal_descuento, 0)))
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (1)
							AND kardex.id_articulo = art_emp.id_articulo
							AND ((SELECT almacen.id_empresa
									FROM iv_calles calle
										INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
										INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
										INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
										INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
									WHERE casilla.id_casilla = kardex.id_casilla) = art_emp.id_empresa
								OR (SELECT almacen.id_empresa
									FROM iv_calles calle
										INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
										INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
										INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
										INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
									WHERE casilla.id_casilla = kardex.id_casilla) IN (SELECT suc.id_empresa FROM pg_empresa suc
																						WHERE suc.id_empresa_padre = art_emp.id_empresa))
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte AND kardex.id_kardex >= art_emp.id_kardex_corte)
									OR art_emp.id_tipo_corte IN (2)))),
		
		cantidad_entrada = (SELECT SUM(kardex.cantidad)
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (2)
							AND kardex.id_articulo = art_emp.id_articulo
							AND ((SELECT almacen.id_empresa
									FROM iv_calles calle
										INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
										INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
										INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
										INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
									WHERE casilla.id_casilla = kardex.id_casilla) = art_emp.id_empresa
								OR (SELECT almacen.id_empresa
									FROM iv_calles calle
										INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
										INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
										INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
										INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
									WHERE casilla.id_casilla = kardex.id_casilla) IN (SELECT suc.id_empresa FROM pg_empresa suc
																						WHERE suc.id_empresa_padre = art_emp.id_empresa))
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte AND kardex.id_kardex >= art_emp.id_kardex_corte)
									OR art_emp.id_tipo_corte IN (2)))),
		
		valor_entrada = (SELECT
							SUM(CASE tipo_documento_movimiento
								WHEN 1 THEN
									(kardex.cantidad * (kardex.costo + IFNULL(kardex.costo_cargo, 0) - IFNULL(kardex.subtotal_descuento, 0)))
								WHEN 2 THEN
									(kardex.cantidad * kardex.costo)
							END)
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (2)
							AND kardex.id_articulo = art_emp.id_articulo
							AND ((SELECT almacen.id_empresa
									FROM iv_calles calle
										INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
										INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
										INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
										INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
									WHERE casilla.id_casilla = kardex.id_casilla) = art_emp.id_empresa
								OR (SELECT almacen.id_empresa
									FROM iv_calles calle
										INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
										INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
										INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
										INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
									WHERE casilla.id_casilla = kardex.id_casilla) IN (SELECT suc.id_empresa FROM pg_empresa suc
																						WHERE suc.id_empresa_padre = art_emp.id_empresa))
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte AND kardex.id_kardex >= art_emp.id_kardex_corte)
									OR art_emp.id_tipo_corte IN (2)))),
		
		cantidad_venta = (SELECT SUM(kardex.cantidad)
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (3)
							AND kardex.id_articulo = art_emp.id_articulo
							AND ((SELECT almacen.id_empresa
									FROM iv_calles calle
										INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
										INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
										INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
										INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
									WHERE casilla.id_casilla = kardex.id_casilla) = art_emp.id_empresa
								OR (SELECT almacen.id_empresa
									FROM iv_calles calle
										INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
										INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
										INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
										INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
									WHERE casilla.id_casilla = kardex.id_casilla) IN (SELECT suc.id_empresa FROM pg_empresa suc
																						WHERE suc.id_empresa_padre = art_emp.id_empresa))
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte AND kardex.id_kardex >= art_emp.id_kardex_corte)
									OR art_emp.id_tipo_corte IN (2)))),
		
		valor_venta = (SELECT SUM(kardex.cantidad * kardex.costo)
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (3)
							AND kardex.id_articulo = art_emp.id_articulo
							AND ((SELECT almacen.id_empresa
									FROM iv_calles calle
										INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
										INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
										INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
										INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
									WHERE casilla.id_casilla = kardex.id_casilla) = art_emp.id_empresa
								OR (SELECT almacen.id_empresa
									FROM iv_calles calle
										INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
										INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
										INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
										INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
									WHERE casilla.id_casilla = kardex.id_casilla) IN (SELECT suc.id_empresa FROM pg_empresa suc
																						WHERE suc.id_empresa_padre = art_emp.id_empresa))
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte AND kardex.id_kardex >= art_emp.id_kardex_corte)
									OR art_emp.id_tipo_corte IN (2)))),
		
		cantidad_salida = (SELECT SUM(kardex.cantidad)
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (4)
							AND kardex.id_articulo = art_emp.id_articulo
							AND ((SELECT almacen.id_empresa
									FROM iv_calles calle
										INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
										INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
										INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
										INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
									WHERE casilla.id_casilla = kardex.id_casilla) = art_emp.id_empresa
								OR (SELECT almacen.id_empresa
									FROM iv_calles calle
										INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
										INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
										INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
										INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
									WHERE casilla.id_casilla = kardex.id_casilla) IN (SELECT suc.id_empresa FROM pg_empresa suc
																						WHERE suc.id_empresa_padre = art_emp.id_empresa))
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte AND kardex.id_kardex >= art_emp.id_kardex_corte)
									OR art_emp.id_tipo_corte IN (2)))),
		
		valor_salida = (SELECT
							SUM(CASE tipo_documento_movimiento
								WHEN 1 THEN
									(kardex.cantidad * kardex.costo)
								WHEN 2 THEN
									(kardex.cantidad * (kardex.costo + IFNULL(kardex.costo_cargo, 0) - IFNULL(kardex.subtotal_descuento, 0)))
							END)
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (4)
							AND kardex.id_articulo = art_emp.id_articulo
							AND ((SELECT almacen.id_empresa
									FROM iv_calles calle
										INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
										INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
										INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
										INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
									WHERE casilla.id_casilla = kardex.id_casilla) = art_emp.id_empresa
								OR (SELECT almacen.id_empresa
									FROM iv_calles calle
										INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
										INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
										INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
										INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
									WHERE casilla.id_casilla = kardex.id_casilla) IN (SELECT suc.id_empresa FROM pg_empresa suc
																						WHERE suc.id_empresa_padre = art_emp.id_empresa))
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte AND kardex.id_kardex >= art_emp.id_kardex_corte)
									OR art_emp.id_tipo_corte IN (2)))) %s;", $sqlBusq);
	/*$updateSQL = sprintf("UPDATE iv_articulos_empresa art_emp SET
		cantidad_compra = (SELECT SUM(kardex.cantidad)
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (1)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte) OR art_emp.id_tipo_corte IN (2)))),
		
		valor_compra = (SELECT SUM(mov_det.cantidad * (mov_det.costo - mov_det.subtotal_descuento))
						FROM iv_movimiento_detalle mov_det
							INNER JOIN iv_kardex kardex ON (mov_det.id_kardex = kardex.id_kardex)
						WHERE kardex.tipo_movimiento IN (1)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte) OR art_emp.id_tipo_corte IN (2)))),
		
		cantidad_entrada = (SELECT SUM(kardex.cantidad)
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (2)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte) OR art_emp.id_tipo_corte IN (2)))),
		
		valor_entrada = (SELECT SUM(mov_det.cantidad * (mov_det.costo - mov_det.subtotal_descuento))
						FROM iv_movimiento_detalle mov_det
							INNER JOIN iv_kardex kardex ON (mov_det.id_kardex = kardex.id_kardex)
						WHERE kardex.tipo_movimiento IN (2)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte) OR art_emp.id_tipo_corte IN (2)))),
		
		cantidad_venta = (SELECT SUM(kardex.cantidad)
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (3)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte) OR art_emp.id_tipo_corte IN (2)))),
		
		valor_venta = (SELECT SUM(mov_det.cantidad * mov_det.costo)
						FROM iv_movimiento_detalle mov_det
							INNER JOIN iv_kardex kardex ON (mov_det.id_kardex = kardex.id_kardex)
						WHERE kardex.tipo_movimiento IN (3)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte) OR art_emp.id_tipo_corte IN (2)))),
		
		cantidad_salida = (SELECT SUM(kardex.cantidad)
						FROM iv_kardex kardex
						WHERE kardex.tipo_movimiento IN (4)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte) OR art_emp.id_tipo_corte IN (2)))),
		
		valor_salida = (SELECT SUM(mov_det.cantidad * mov_det.costo)
						FROM iv_movimiento_detalle mov_det
							INNER JOIN iv_kardex kardex ON (mov_det.id_kardex = kardex.id_kardex)
						WHERE kardex.tipo_movimiento IN (4)
							AND kardex.id_articulo = art_emp.id_articulo
							AND (kardex.fecha_movimiento >= art_emp.fecha_kardex_corte
								AND ((art_emp.id_tipo_corte IN (1) AND kardex.id_kardex <> art_emp.id_kardex_corte) OR art_emp.id_tipo_corte IN (2)))) %s;", $sqlBusq);*/
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	mysql_query("SET NAMES 'latin1';");
		
	return array(true, "");
}

function actualizarOrdenServicio($idOrden) {
	$queryConfig403 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
			INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = (SELECT id_empresa FROM sa_orden WHERE id_orden = %s LIMIT 1);",
			valTpDato($idOrden, "int"));
	$rsConfig403 = mysql_query($queryConfig403);
	if (!$rsConfig403) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowConfig403 = mysql_fetch_assoc($rsConfig403);
	
	if($rowConfig403['valor'] == "3"){//puerto rico
		$arrayIvaBaseImponible = array();
		$arrayIvaSubTotal = array();
		$arrayPorcIva = array();
		$totalExento = 0;
		$subtotal = 0;	
		
		$query = sprintf("SELECT porcentaje_descuento FROM sa_orden WHERE id_orden = %s;",
					valTpDato($idOrden, "int"));
		$rs = mysql_query($query);
		if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$row = mysql_fetch_assoc($rs);
		
		$porcDesc = $row["porcentaje_descuento"];
		
		$query = sprintf("SELECT id_det_orden_articulo, (pmu_unitario * cantidad) AS monto_pmu, (precio_unitario * cantidad) AS valor 
							FROM sa_det_orden_articulo
							WHERE id_orden = %s
							AND aprobado = 1
							AND estado_articulo <> 'DEVUELTO'",
					valTpDato($idOrden, "int"));
		$rs = mysql_query($query);
		if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		
		while($row = mysql_fetch_assoc($rs)){
			$query = sprintf("SELECT id_iva, iva FROM sa_det_orden_articulo_iva WHERE id_det_orden_articulo = %s",
				valTpDato($row["id_det_orden_articulo"], "int"));
			$rsIva = mysql_query($query);
			if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
			
			//PMU
			$subtotal += $row["monto_pmu"];
			$totalExento += $row["monto_pmu"];
			
			$subtotal += $row["valor"];
			
			if(mysql_num_rows($rsIva) == 0){// ES EXENTO
				$totalExento += $row["valor"];
			}
			
			while($rowIva = mysql_fetch_assoc($rsIva)){
				$descItm = ($porcDesc * $row["valor"])/100;
				$subTotalItm = $row["valor"] - $descItm;
				$subTotalIvaItm = ($subTotalItm * $rowIva["iva"])/100;
				
				$arrayIvaBaseImponible[$rowIva["id_iva"]] += $subTotalItm;
				$arrayIvaSubTotal[$rowIva["id_iva"]] += $subTotalIvaItm;
				$arrayPorcIva[$rowIva["id_iva"]] = $rowIva["iva"];
			}
		}
		
		$query = sprintf("SELECT
								id_det_orden_tempario,
								(CASE id_modo
									WHEN 1 THEN
										ROUND((precio_tempario_tipo_orden * ut) / base_ut_precio,2)
									WHEN 2 THEN
										precio
								END) AS valor
							FROM sa_det_orden_tempario
							WHERE id_orden = %s 
							AND estado_tempario <> 'DEVUELTO';",
					valTpDato($idOrden, "int"));
		$rs = mysql_query($query);
		if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		
		while($row = mysql_fetch_assoc($rs)){
			$query = sprintf("SELECT id_iva, iva FROM sa_det_orden_tempario_iva WHERE id_det_orden_tempario = %s",
				valTpDato($row["id_det_orden_tempario"], "int"));
			$rsIva = mysql_query($query);
			if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
			
			$subtotal += $row["valor"];
			
			if(mysql_num_rows($rsIva) == 0){// ES EXENTO
				$totalExento += $row["valor"];
			}
			
			while($rowIva = mysql_fetch_assoc($rsIva)){
				$descItm = ($porcDesc * $row["valor"])/100;
				$subTotalItm = $row["valor"] - $descItm;
				$subTotalIvaItm = ($subTotalItm * $rowIva["iva"])/100;
				
				$arrayIvaBaseImponible[$rowIva["id_iva"]] += $subTotalItm;
				$arrayIvaSubTotal[$rowIva["id_iva"]] += $subTotalIvaItm;
				$arrayPorcIva[$rowIva["id_iva"]] = $rowIva["iva"];
			}
		}
		
		$query = sprintf("SELECT
							det_tot.id_det_orden_tot,
							tot.monto_subtotal + ROUND((tot.monto_subtotal * det_tot.porcentaje_tot) / 100, 2) AS valor
						FROM sa_det_orden_tot det_tot
						INNER JOIN sa_orden_tot tot ON (tot.id_orden_tot = det_tot.id_orden_tot)
						WHERE det_tot.id_orden = %s",
					valTpDato($idOrden, "int"));
		$rs = mysql_query($query);
		if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
		while($row = mysql_fetch_assoc($rs)){
			$query = sprintf("SELECT id_iva, iva FROM sa_det_orden_tot_iva WHERE id_det_orden_tot = %s",
				valTpDato($row["id_det_orden_tot"], "int"));
			$rsIva = mysql_query($query);
			if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
			
			$subtotal += $row["valor"];
	
			
			if(mysql_num_rows($rsIva) == 0){// ES EXENTO
				$totalExento += $row["valor"];
			}
			
			while($rowIva = mysql_fetch_assoc($rsIva)){
				$descItm = ($porcDesc * $row["valor"])/100;
				$subTotalItm = $row["valor"] - $descItm;
				$subTotalIvaItm = ($subTotalItm * $rowIva["iva"])/100;
				
				$arrayIvaBaseImponible[$rowIva["id_iva"]] += $subTotalItm;
				$arrayIvaSubTotal[$rowIva["id_iva"]] += $subTotalIvaItm;
				$arrayPorcIva[$rowIva["id_iva"]] = $rowIva["iva"];
			}
		}
	
		$query = sprintf("SELECT 
							id_det_orden_nota,
							precio AS valor 
						FROM sa_det_orden_notas 
						WHERE id_orden = %s 
						AND aprobado = 1;",
			valTpDato($idOrden, "int"));
		$rs = mysql_query($query);
		if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		
		while($row = mysql_fetch_assoc($rs)){
			$query = sprintf("SELECT id_iva, iva FROM sa_det_orden_notas_iva WHERE id_det_orden_nota = %s",
				valTpDato($row["id_det_orden_nota"], "int"));
			$rsIva = mysql_query($query);
			if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
			
			$subtotal += $row["valor"];
			
			if(mysql_num_rows($rsIva) == 0){// ES EXENTO
				$totalExento += $row["valor"];
			}
			
			while($rowIva = mysql_fetch_assoc($rsIva)){
				$descItm = ($porcDesc * $row["valor"])/100;
				$subTotalItm = $row["valor"] - $descItm;
				$subTotalIvaItm = ($subTotalItm * $rowIva["iva"])/100;
				
				$arrayIvaBaseImponible[$rowIva["id_iva"]] += $subTotalItm;
				$arrayIvaSubTotal[$rowIva["id_iva"]] += $subTotalIvaItm;
				$arrayPorcIva[$rowIva["id_iva"]] = $rowIva["iva"];
			}
		}
	
		$query = sprintf("DELETE FROM sa_orden_iva WHERE id_orden = %s;",
			valTpDato($idOrden, "int"));
		$rs = mysql_query($query);
		if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
		//al dividir entre 100 el (item * impuesto)/100 da 4 decimales, recalcula para redondear el total, no el item
		$recalculoIvas = array();
		$baseImponibleSimple = 0;
		foreach($arrayPorcIva as $idIva => $porcIva){
			$recalculoIvas[$idIva] += round(($arrayIvaBaseImponible[$idIva]*$porcIva)/100,2);
			
			$baseImponibleSimple = $arrayIvaBaseImponible[$idIva];
			$idIvaSimple = $idIva;
			$porcIvaSimple = $porcIva;
		}
	
		foreach($arrayPorcIva as $idIva => $porcIva){
			$query = sprintf("INSERT INTO sa_orden_iva (id_orden, base_imponible, subtotal_iva, id_iva, iva)
								VALUES (%s, %s, %s, %s, %s)",
				valTpDato($idOrden, "int"),
				valTpDato($arrayIvaBaseImponible[$idIva], "double"),
				valTpDato($recalculoIvas[$idIva], "double"),
				valTpDato($idIva, "int"),
				valTpDato($porcIva, "double"));
			$rs = mysql_query($query);
			if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		}
		
		$totalIva = array_sum($recalculoIvas);
		
		$totalDesc = round($subtotal * ($porcDesc/100),2);
		$totalExento = round($totalExento - ($totalExento*($porcDesc/100)),2);
		$totalOrden = round($subtotal - $totalDesc + $totalIva,2);
		
		$query = sprintf("UPDATE sa_orden SET
							subtotal = %s,
							idIva = %s,
							iva = %s,
							base_imponible = %s,
							monto_exento = %s,
							subtotal_iva = %s,
							subtotal_descuento = %s,
							total_orden = %s
						WHERE id_orden = %s",
					valTpDato($subtotal, "double"),
					valTpDato($idIvaSimple, "int"),
					valTpDato($porcIvaSimple, "double"),
					valTpDato($baseImponibleSimple, "double"),
					valTpDato($totalExento, "double"),
					valTpDato($totalIva, "double"),
					valTpDato($totalDesc, "double"),
					valTpDato($totalOrden, "double"),
					valTpDato($idOrden, "int"));
		$rs = mysql_query($query);
		if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));

	} else if($rowConfig403['valor'] == "2") {//SOLO PANAMA
		// RECALCULA LOS MONTOS DE LA ORDEN
		$sqlDetOrden = sprintf("SELECT SUM(precio_unitario * cantidad) AS valor FROM sa_det_orden_articulo
		WHERE id_orden = %s
				AND aprobado = 1
				AND estado_articulo <> 'DEVUELTO'
				AND (iva > 0 OR iva IS NOT NULL);",
				$idOrden);
		$rsDetOrden = mysql_query($sqlDetOrden);
		if (!$rsDetOrden) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$rowDetOrden = mysql_fetch_array($rsDetOrden);
		$valorBaseimponibleRep = $rowDetOrden['valor'];

		$sqlDetOrdenExento = sprintf("SELECT SUM(precio_unitario * cantidad) AS valorExento FROM sa_det_orden_articulo
		WHERE id_orden = %s
				AND aprobado = 1
				AND estado_articulo <> 'DEVUELTO'
				AND (iva = 0 OR iva IS NULL);",
				$idOrden);
		$rsDetOrdenExento = mysql_query($sqlDetOrdenExento);
		if (!$rsDetOrdenExento) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$rowDetOrdenExento = mysql_fetch_array($rsDetOrdenExento);
		$valorExentoRep = $rowDetOrdenExento['valorExento'];
	


		$sqlTemp = sprintf("SELECT
		SUM((CASE id_modo
				WHEN 1 THEN
						ROUND((precio_tempario_tipo_orden * ut) / base_ut_precio,2)
				WHEN 2 THEN
						precio
		END)) AS valorTemp
		FROM sa_det_orden_tempario
		WHERE id_orden = %s AND estado_tempario <> 'DEVUELTO';",
				$idOrden);
		$rsTemp = mysql_query($sqlTemp);
		if (!$rsTemp) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$rowTemp = mysql_fetch_array($rsTemp);
		$valorTemp = $rowTemp['valorTemp'];

		$sqlTOT = sprintf("SELECT
				orden.id_orden,
				orden.id_tipo_orden,
				SUM(orden_tot.monto_subtotal) AS monto_subtotalTot
		FROM sa_orden_tot orden_tot
				INNER JOIN sa_orden orden ON (orden_tot.id_orden_servicio = orden.id_orden)
		WHERE orden.id_orden = %s
		GROUP BY orden.id_orden, orden.id_tipo_orden;",
				$idOrden);
		$rsTOT = mysql_query($sqlTOT);
		if (!$rsTOT) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$rowTOT = mysql_fetch_array($rsTOT);

		$queryPorcentajeTot = sprintf("SELECT *
		FROM sa_det_orden_tot
		WHERE id_orden = %s;",
				valTpDato($idOrden, "int"));
		$rsPorcentajeTot = mysql_query($queryPorcentajeTot);
		if (!$rsPorcentajeTot) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$rowPorcentajeTot = mysql_fetch_assoc($rsPorcentajeTot);

		$valorDetTOT = $rowTOT['monto_subtotalTot'] + (($rowPorcentajeTot['porcentaje_tot'] * $rowTOT['monto_subtotalTot']) / 100);

		$sqlNota = sprintf("SELECT SUM(precio) AS precio FROM sa_det_orden_notas
		WHERE id_orden = %s 
		AND aprobado = 1;",
				$idOrden);
		$rsNota = mysql_query($sqlNota);
		if (!$rsNota) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$rowNota = mysql_fetch_array($rsNota);

		$sqlDesc = sprintf("SELECT * FROM sa_orden
		WHERE id_orden = %s;",
				$idOrden);
		$rsDesc = mysql_query($sqlDesc);
		if (!$rsDesc) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$rowDesc = mysql_fetch_array($rsDesc);


		///$Desc = ($rowDesc['porcentaje_descuento'] * $valor) / 100;
		///$valorConDesc = $valor - $Desc;
		///$totalConDesc = $rowNota['precio'] + $valorDetTOT + $valorTemp + $valorConDesc;
		///$totalSinDesc = $rowNota['precio'] + $valorDetTOT + $valorTemp + $valor;
		///$totalIva = ($totalConDesc * $rowDesc['iva'])/100;

		$totalBaseImponibleItems = $valorTemp + $valorDetTOT + $rowNota['precio'];
		$subtotal = $totalBaseImponibleItems + $valorExentoRep + $valorBaseimponibleRep;

		$Desc = round($subtotal * ($rowDesc['porcentaje_descuento']/100),2);
		$baseImponible = ($totalBaseImponibleItems + $valorBaseimponibleRep);
		$baseImponible = round($baseImponible - ($baseImponible*($rowDesc['porcentaje_descuento']/100)),2);
		$totalIva = 0;
		
		if($rowDesc['iva'] > 0){//si tiene iva calcular con base imponible sin exento
			$tieneIva = 1;
			$totalIva = ($baseImponible * $rowDesc['iva'])/100;
		}else{//si no tiene iva, todo se va a la base imponible
			$baseImponible = $subtotal - $Desc;
		}
	
		$updateSQL = "UPDATE sa_orden SET
				subtotal = ".valTpDato($subtotal, "double").",
				base_imponible = ".valTpDato($baseImponible, "double").",
				idIva = ".valTpDato($rowDesc['idIva'], "int").",
				iva = ".valTpDato($rowDesc['iva'], "double").",
				subtotal_iva = ".valTpDato($totalIva, "double").",
				subtotal_descuento = ".valTpDato($Desc, "double")."
		WHERE id_orden = ".$idOrden;
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));		

	} else if($rowConfig403['valor'] == "1") {//SOLO VENEZUELA
		// RECALCULA LOS MONTOS DE LA ORDEN
		$sqlDetOrden = sprintf("SELECT SUM(precio_unitario * cantidad) AS valor FROM sa_det_orden_articulo
		WHERE id_orden = %s
				AND aprobado = 1
				AND estado_articulo <> 'DEVUELTO'
				AND (SELECT COUNT(*) FROM sa_det_orden_articulo_iva 
						WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = sa_det_orden_articulo.id_det_orden_articulo
					) > 0;",
				$idOrden);
		$rsDetOrden = mysql_query($sqlDetOrden);
		if (!$rsDetOrden) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$rowDetOrden = mysql_fetch_array($rsDetOrden);
		$valorBaseimponibleRep = $rowDetOrden['valor'];
	
		$sqlDetRepuestosIvas = sprintf("SELECT SUM(precio_unitario * cantidad) AS base_imponible, sa_det_orden_articulo_iva.id_iva
		FROM sa_det_orden_articulo
		INNER JOIN sa_det_orden_articulo_iva ON sa_det_orden_articulo.id_det_orden_articulo = sa_det_orden_articulo_iva.id_det_orden_articulo
		WHERE id_orden = %s
				AND aprobado = 1
				AND estado_articulo <> 'DEVUELTO'
				GROUP BY sa_det_orden_articulo_iva.id_iva",
				$idOrden);
		$rsDetRepuestosIvas = mysql_query($sqlDetRepuestosIvas);
		if (!$rsDetRepuestosIvas) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
		while($rowDetRepuestosIvas = mysql_fetch_assoc($rsDetRepuestosIvas)){
			$arrayIvasArticulos[$rowDetRepuestosIvas["id_iva"]] = $rowDetRepuestosIvas["base_imponible"];
		}
	
	
		$sqlDetOrdenExento = sprintf("SELECT SUM(precio_unitario * cantidad) AS valorExento FROM sa_det_orden_articulo
		WHERE id_orden = %s
				AND aprobado = 1
				AND estado_articulo <> 'DEVUELTO'
				AND (SELECT COUNT(*) FROM sa_det_orden_articulo_iva 
						WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = sa_det_orden_articulo.id_det_orden_articulo
					) = 0;",
				$idOrden);
		$rsDetOrdenExento = mysql_query($sqlDetOrdenExento);
		if (!$rsDetOrdenExento) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$rowDetOrdenExento = mysql_fetch_array($rsDetOrdenExento);
		$valorExentoRep = $rowDetOrdenExento['valorExento'];
	
	
		$sqlTemp = sprintf("SELECT
		SUM((CASE id_modo
				WHEN 1 THEN
						ROUND((precio_tempario_tipo_orden * ut) / base_ut_precio,2)
				WHEN 2 THEN
						precio
		END)) AS valorTemp
		FROM sa_det_orden_tempario
		WHERE id_orden = %s AND estado_tempario <> 'DEVUELTO';",
				$idOrden);
		$rsTemp = mysql_query($sqlTemp);
		if (!$rsTemp) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$rowTemp = mysql_fetch_array($rsTemp);
		$valorTemp = $rowTemp['valorTemp'];
	
		$sqlTOT = sprintf("SELECT
				orden.id_orden,
				orden.id_tipo_orden,
				SUM(orden_tot.monto_subtotal) AS monto_subtotalTot
		FROM sa_orden_tot orden_tot
				INNER JOIN sa_orden orden ON (orden_tot.id_orden_servicio = orden.id_orden)
		WHERE orden.id_orden = %s
		GROUP BY orden.id_orden, orden.id_tipo_orden;",
				$idOrden);
		$rsTOT = mysql_query($sqlTOT);
		if (!$rsTOT) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$rowTOT = mysql_fetch_array($rsTOT);
	
		$queryPorcentajeTot = sprintf("SELECT *
		FROM sa_det_orden_tot
		WHERE id_orden = %s;",
				valTpDato($idOrden, "int"));
		$rsPorcentajeTot = mysql_query($queryPorcentajeTot);
		if (!$rsPorcentajeTot) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$rowPorcentajeTot = mysql_fetch_assoc($rsPorcentajeTot);
	
		$valorDetTOT = $rowTOT['monto_subtotalTot'] + (($rowPorcentajeTot['porcentaje_tot'] * $rowTOT['monto_subtotalTot']) / 100);
	
		$sqlNota = sprintf("SELECT SUM(precio) AS precio FROM sa_det_orden_notas
		WHERE id_orden = %s 
		AND aprobado = 1;",
				$idOrden);
		$rsNota = mysql_query($sqlNota);
		if (!$rsNota) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$rowNota = mysql_fetch_array($rsNota);
	
		$sqlDesc = sprintf("SELECT porcentaje_descuento FROM sa_orden
		WHERE id_orden = %s;",
				$idOrden);
		$rsDesc = mysql_query($sqlDesc);
		if (!$rsDesc) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$rowDesc = mysql_fetch_array($rsDesc);
	
		$sqlIvas = sprintf("SELECT base_imponible, subtotal_iva, id_iva, iva
		FROM sa_orden_iva
		WHERE id_orden = %s;",
				$idOrden);
		$rsIvas = mysql_query($sqlIvas);
		if (!$rsIvas) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
		$tieneIva = mysql_num_rows($rsIvas);
	
		$totalExento = 0;//total exento de la orden
		$totalBaseImponibleItems = 0;//total base imponible de items que no sean repuestos
	
		if($tieneIva){//si tiene iva, separar exentos de repuestos
			$totalExento += $valorExentoRep;
			$totalBaseImponibleItems += $rowNota['precio'] + $valorDetTOT + $valorTemp;
		}else{//si no, todo se va al exento
			$totalExento += $rowNota['precio'] + $valorDetTOT + $valorTemp + $valorExentoRep;
		}
	
		while($rowIvas = mysql_fetch_assoc($rsIvas)){//busco ivas y porcentajes de la orden
			$arrayIvasOrden[$rowIvas["id_iva"]] = $rowIvas["iva"];
		}
	
		$totalIva = 0;
		foreach($arrayIvasOrden as $idIva => $porcIva){//recorro ivas de la orden e ivas de los repuestos
			$baseIva = $totalBaseImponibleItems + $arrayIvasArticulos[$idIva];//sumo base items + base articulos que aplican            
			$baseIvaDesc = round($baseIva - ($baseIva*($rowDesc['porcentaje_descuento']/100)),2);            
			$ivaSubTotal = round($baseIvaDesc*($porcIva/100),2);            
			$totalIva += $ivaSubTotal;
	
			$sqlUpdateIva = sprintf("UPDATE sa_orden_iva SET base_imponible = %s, subtotal_iva = %s
									WHERE id_orden = %s 
									AND id_iva = %s;",
							valTpDato($baseIvaDesc, "double"),
							valTpDato($ivaSubTotal, "double"),
							$idOrden,
							$idIva);
			$rsUpdateIva = mysql_query($sqlUpdateIva);
			if (!$rsUpdateIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));            
		}
	
		$subtotal = $totalBaseImponibleItems + $totalExento + $valorBaseimponibleRep;
	
		$Desc = round($subtotal * ($rowDesc['porcentaje_descuento']/100),2);
		$totalExento = round($totalExento - ($totalExento*($rowDesc['porcentaje_descuento']/100)),2);
		$totalOrden = round($subtotal - $Desc + $totalIva,2);
		$updateSQL = "UPDATE sa_orden SET
				subtotal = ".valTpDato($subtotal, "double").",
				monto_exento = ".valTpDato($totalExento, "double").",
				subtotal_iva = ".valTpDato($totalIva, "double").",
				subtotal_descuento = ".valTpDato($Desc, "double").",
				total_orden = ".valTpDato($totalOrden, "double")."
		WHERE id_orden = ".$idOrden;
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	}
	
	return array(true, "");
}

function actualizarCostoPromedio($idArticulo = "", $idEmpresa = "") {
	global $conex;
	
	////////////////////////////////////////////////////////////////////////////////////////////////////
	$sqlBusq = " "; // ESPACIO EN BLANCO PORQUE LA PRIMERA CONDICION ESTA EN LA CONSULTA
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_costo.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_costo.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	// INSERTA EL ULTIMO COSTO DEL ARTICULO CON LA EMPRESA PRINCIPAL DE ESTA
	$insertSQL = sprintf("INSERT INTO iv_articulos_costos (id_empresa, id_proveedor, id_articulo, fecha, costo, costo_promedio, id_moneda, fecha_registro)
	SELECT
		emp.id_empresa_padre,
		art_costo.id_proveedor,
		art_costo.id_articulo,
		art_costo.fecha,
		art_costo.costo,
		art_costo.costo_promedio,
		art_costo.id_moneda,
		art_costo.fecha_registro
	FROM pg_empresa emp
		INNER JOIN iv_articulos_costos art_costo ON (emp.id_empresa = art_costo.id_empresa)
	WHERE emp.id_empresa_padre NOT IN (SELECT id_empresa FROM iv_articulos_costos art_costo2
										WHERE art_costo2.id_articulo = art_costo.id_articulo)
		AND art_costo.id_articulo_costo IN (SELECT MAX(id_articulo_costo) FROM iv_articulos_costos art_costo2
											WHERE art_costo2.id_articulo = art_costo.id_articulo) %s", $sqlBusq);
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	mysql_query("SET NAMES 'latin1';");
	////////////////////////////////////////////////////////////////////////////////////////////////////
	
	$sqlBusq = "";
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_emp.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		// PARA TOMAR EN CUENTA LA EMPRESA MISMA Y LA PRINCIPAL DE ESTA
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(art_emp.id_empresa = %s
		OR art_emp.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
									WHERE suc.id_empresa = %s)
		OR (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = art_emp.id_empresa) IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
															WHERE suc.id_empresa = %s))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	// BUSCA QUE EMPRESAS TIENE DICHO ARTICULO
	$queryArtEmp = sprintf("SELECT * FROM iv_articulos_empresa art_emp %s;", $sqlBusq);
	$rsArtEmp = mysql_query($queryArtEmp);
	if (!$rsArtEmp) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	while ($rowArtEmp = mysql_fetch_assoc($rsArtEmp)) {
		$idArticulo = $rowArtEmp['id_articulo'];
		$idEmpresa = $rowArtEmp['id_empresa'];
		
		// VERIFICA VALORES DE CONFIGURACION (Cálculo de Costo de Repuesto)
		$queryConfig18 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
			INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
		WHERE config.id_configuracion = 18
			AND config_emp.status = 1
			AND config_emp.id_empresa = %s;",
			valTpDato($idEmpresa, "int"));
		$rsConfig18 = mysql_query($queryConfig18);
		if (!$rsConfig18) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowConfig18 = mysql_fetch_assoc($rsConfig18);
		
		////////////////////////////////////////////////////////////////////////////////////////////////////
		// INSERTA EL ULTIMO COSTO DEL ARTICULO DEL LA EMPRESA PRINCIPAL SI ESTA NO TIENE NINGUNO
		$insertSQL = sprintf("INSERT INTO iv_articulos_costos (id_empresa, id_proveedor, id_articulo, fecha, costo, costo_promedio, id_moneda, fecha_registro)
		SELECT %s, art_costo.id_proveedor, art_costo.id_articulo, art_costo.fecha, art_costo.costo, art_costo.costo_promedio, art_costo.id_moneda, art_costo.fecha_registro
		FROM iv_articulos_costos art_costo
			INNER JOIN iv_articulos art ON (art_costo.id_articulo = art.id_articulo)
		WHERE art_costo.id_articulo = %s
			AND art_costo.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
										WHERE suc.id_empresa = %s)
			AND art_costo.id_articulo NOT IN (SELECT art_costo2.id_articulo FROM iv_articulos_costos art_costo2
												WHERE art_costo2.id_empresa IN (%s))
			AND art_costo.id_articulo_costo = (SELECT MAX(art_costo3.id_articulo_costo) FROM iv_articulos_costos art_costo3
												WHERE art_costo3.id_articulo = art_costo.id_articulo
													AND art_costo3.id_empresa = art_costo.id_empresa)
		ORDER BY art_costo.id_articulo ASC",
			valTpDato($idEmpresa, "int"),
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		mysql_query("SET NAMES 'latin1';");
		////////////////////////////////////////////////////////////////////////////////////////////////////
		
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_emp.id_articulo = %s",
			valTpDato($idArticulo, "int"));
			
		if ($rowConfig18['valor'] == 1) { // 1 = Costo Independiente, 2 = Costo Único por la Empresa Principal
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("art_emp.id_empresa = %s",
				valTpDato($idEmpresa, "int"));
		} else {
			// PARA TOMAR EN CUENTA LA EMPRESA PRINCIPAL DE ESTA
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(art_emp.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
																WHERE suc.id_empresa = %s))",
				valTpDato($idEmpresa, "int"));
		}
		
		$queryArtEmp2 = sprintf("SELECT * FROM iv_articulos_empresa art_emp %s;", $sqlBusq);
		$rsArtEmp2 = mysql_query($queryArtEmp2);
		if (!$rsArtEmp2) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		while ($rowArtEmp2 = mysql_fetch_assoc($rsArtEmp2)) {
			$idEmpresa = $rowArtEmp['id_empresa'];
			
			// BUSCA EL ULTIMO COSTO DE LA PIEZA Y LA FECHA DE LA ULTIMA COMPRA
			$queryArticuloCosto = sprintf("SELECT art_costo.*,
				(SELECT DATE(kardex.fecha_movimiento) FROM iv_kardex kardex
				WHERE kardex.tipo_movimiento IN (1)
					AND kardex.id_articulo = art_costo.id_articulo
					AND ((SELECT almacen.id_empresa
							FROM iv_calles calle
								INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
								INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
								INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
								INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
							WHERE casilla.id_casilla = kardex.id_casilla) = art_costo.id_empresa
						OR (SELECT almacen.id_empresa
							FROM iv_calles calle
								INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
								INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
								INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
								INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
							WHERE casilla.id_casilla = kardex.id_casilla) IN (SELECT suc.id_empresa FROM pg_empresa suc
																				WHERE suc.id_empresa_padre = %s))
				LIMIT 1) AS fecha_movimiento
			FROM iv_articulos_costos art_costo
			WHERE art_costo.id_articulo = %s
				AND art_costo.id_empresa = %s
			ORDER BY art_costo.id_articulo_costo DESC
			LIMIT 1;",
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idEmpresa, "int"));
			$rsArticuloCosto = mysql_query($queryArticuloCosto);
			if (!$rsArticuloCosto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
			$totalRowsArticuloCosto = mysql_num_rows($rsArticuloCosto);
			$rowArticuloCosto = mysql_fetch_assoc($rsArticuloCosto);
			
			$idArticuloCosto = $rowArticuloCosto['id_articulo_costo'];
			
			
			$sqlBusq = " "; // ESPACIO EN BLANCO PORQUE LA PRIMERA CONDICION ESTA EN LA CONSULTA
			$sqlBusq2 = " "; // ESPACIO EN BLANCO PORQUE LA PRIMERA CONDICION ESTA EN LA CONSULTA
			if ($rowConfig18['valor'] == 1) { // 1 = Costo Independiente, 2 = Costo Único por la Empresa Principal
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("(SELECT almacen.id_empresa
					FROM iv_calles calle
						INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
						INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
						INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
						INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
					WHERE casilla.id_casilla = kardex.id_casilla) = %s",
					valTpDato($idEmpresa, "int"));
				
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("art_emp.id_empresa = %s",
					valTpDato($idEmpresa, "int"));
			} else {
				// BUSCA LOS MOVIMIENTOS DE LAS SUCURSALES DE LA PRINCIPAL
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("(SELECT almacen.id_empresa
					FROM iv_calles calle
						INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
						INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
						INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
						INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
					WHERE casilla.id_casilla = kardex.id_casilla) IN (SELECT suc.id_empresa FROM pg_empresa suc
																		WHERE suc.id_empresa_padre = (SELECT suc2.id_empresa_padre FROM pg_empresa suc2
																										WHERE suc2.id_empresa = %s))",
					valTpDato($idEmpresa, "int"));
				
				// PARA TOMAR EN CUENTA LA EMPRESA PRINCIPAL DE ESTA
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("art_emp.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
																	WHERE suc.id_empresa = %s)",
					valTpDato($idEmpresa, "int"));
			}
			
			// BUSCA EL COSTO DE LA ULTIMA VENTA O SALIDA
			$queryMov = sprintf("SELECT
				kardex.id_kardex,
				kardex.fecha_movimiento,
				mov_det.costo
			FROM iv_movimiento_detalle mov_det
				INNER JOIN iv_kardex kardex ON (mov_det.id_kardex = kardex.id_kardex)
			WHERE kardex.id_articulo = %s %s
				AND kardex.tipo_movimiento IN (3,4)
				AND kardex.fecha_movimiento >= (SELECT art_emp.fecha_kardex_corte FROM iv_articulos_empresa art_emp
												WHERE art_emp.id_articulo = kardex.id_articulo %s)
			ORDER BY kardex.fecha_movimiento DESC;",
				valTpDato($idArticulo, "int"),
				$sqlBusq, $sqlBusq2);
			$rsMov = mysql_query($queryMov);
			if (!$rsMov) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
			$rowMov = mysql_fetch_assoc($rsMov);
			
			$cantidad = $rowArtEmp2['cantidad_compra'] + $rowArtEmp2['cantidad_entrada'] - $rowArtEmp2['cantidad_venta'] - $rowArtEmp2['cantidad_salida'];
			
			if ($cantidad > 0) {
				$valor = $rowArtEmp2['valor_compra'] + $rowArtEmp2['valor_entrada'] - $rowArtEmp2['valor_venta'] - $rowArtEmp2['valor_salida'];
				
				$costoPromedio = round($valor,2) / round($cantidad,2);
				$costoPromedio = ($costoPromedio < 0) ? (-1) * $costoPromedio : $costoPromedio;
			} else if ($rowMov['costo'] > 0) {
				$costoPromedio = $rowMov['costo'];
			} else {
				$costoPromedio = $rowArticuloCosto['costo'];
			}
			
			$costoPromedio = ($cantidad > 0 && $costoPromedio > 0) ? $costoPromedio : (($rowMov['costo'] > 0) ? $rowMov['costo'] : $rowArticuloCosto['costo']);
			
			// ACTUALIZA EL COSTO PROMEDIO
			$updateSQL = sprintf("UPDATE iv_articulos_costos SET
				costo_promedio = %s
			WHERE id_articulo_costo = %s;",
				valTpDato($costoPromedio, "real_inglesa"),
				valTpDato($idArticuloCosto, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
			mysql_query("SET NAMES 'latin1';");
			
			$a .= $updateSQL."\n";
			//return array(false, $queryMov."<br><br>");
			//return array(false, $queryMov."<br><br>"."Cantidad: ".$cantidad.", Valor: ".$valor.", Costo Promedio: ".$costoPromedio."<br><br><br>");
		}
	}
	//return array(false, $a."\n".__LINE__);
	return array(true, "");
}

function actualizarPrecioVenta($idArticulo = "", $idEmpresa = "", $idPrecio = "", $ejecutarAumento = false) {
	global $conex;
	
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		// PARA TOMAR EN CUENTA LA EMPRESA MISMA Y LA PRINCIPAL DE ESTA
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(art_emp.id_empresa = %s
		OR art_emp.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
									WHERE suc.id_empresa = %s)
		OR (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = art_emp.id_empresa) IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
															WHERE suc.id_empresa = %s))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	// ACTUALIZA EL PRECIO DE LOS ARTICULOS DE LA EMPRESA
	$queryArticuloEmpresa = sprintf("SELECT * FROM iv_articulos_empresa art_emp %s;", $sqlBusq);
	$rsArticuloEmpresa = mysql_query($queryArticuloEmpresa);
	if (!$rsArticuloEmpresa) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	while ($rowArticuloEmpresa = mysql_fetch_assoc($rsArticuloEmpresa)) {
		$idArticulo = $rowArticuloEmpresa['id_articulo'];
		$idEmpresa = $rowArticuloEmpresa['id_empresa'];
		
		// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
		$ResultConfig12 = valorConfiguracion(12, $idEmpresa);
		if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
			return array(false, $ResultConfig12[1]);
		} else if ($ResultConfig12[0] == true) {
			$ResultConfig12 = $ResultConfig12[1];
		}
		
		$sqlLimit = (in_array($ResultConfig12, array(1,2))) ? "LIMIT 1" : "";
		
		// BUSCA EL ULTIMO COSTO DEL ARTICULO
		$queryCostoArt = sprintf("SELECT * FROM iv_articulos_costos
		WHERE id_articulo = %s
			AND id_empresa = %s
		ORDER BY id_articulo_costo DESC %s;",
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"),
			$sqlLimit);
		$rsCostoArt = mysql_query($queryCostoArt);
		if (!$rsCostoArt) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		while ($rowCostoArt = mysql_fetch_assoc($rsCostoArt)) {
			$sqlBusq2 = "";
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("porcentaje <> 0 AND estatus IN (1,2)");
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("emp_precio.actualizar_con_costo = 1");
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("emp_precio.id_empresa = %s",
				valTpDato($idEmpresa, "int"));
			
			if ($idPrecio != "-1" && $idPrecio != "") {
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("precio.id_precio = %s",
					valTpDato($idPrecio, "int"));
			}
			
			$queryPrecios = sprintf("SELECT *
			FROM pg_empresa_precios emp_precio
				INNER JOIN pg_precios precio ON (emp_precio.id_precio = precio.id_precio) %s;", $sqlBusq2);
			$rsPrecios = mysql_query($queryPrecios);
			if (!$rsPrecios) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
			while ($rowPrecio = mysql_fetch_assoc($rsPrecios)) {
				$idPrecioAct = $rowPrecio['id_precio'];
				
				switch ($rowPrecio['tipo_costo']) {
					case 1 : $costoUnitario = $rowCostoArt['costo']; break;
					case 2 : $costoUnitario = $rowCostoArt['costo_promedio']; break;
				}
				
				switch ($ejecutarAumento) {
					case false : $porcMarkUp = $rowPrecio['porcentaje']; break;
					case true : $porcMarkUp = $rowPrecio['porcentaje_aumento']; break;
				}
					
				if ($costoUnitario > 0) {
					if (in_array($ResultConfig12, array(1,2))) { // 1 = Reposición, 2 = Promedio, 3 = FIFO
						$queryArtPrecio = sprintf("SELECT * FROM iv_articulos_precios
						WHERE id_articulo = %s
							AND id_empresa = %s
							AND (id_articulo_costo IS NULL OR id_articulo_costo = %s)
							AND id_precio = %s
						ORDER BY id_articulo_precio DESC;",
							valTpDato($idArticulo, "int"),
							valTpDato($idEmpresa, "int"),
							valTpDato($rowCostoArt['id_articulo_costo'], "int"),
							valTpDato($idPrecioAct, "int"));
						$rsArtPrecio = mysql_query($queryArtPrecio);
						if (!$rsArtPrecio) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
						$totalRowsArtPrecio = mysql_num_rows($rsArtPrecio);
						$rowArtPrecio = mysql_fetch_assoc($rsArtPrecio);
					} else {
						$queryArtPrecio = sprintf("SELECT * FROM iv_articulos_precios
						WHERE id_articulo = %s
							AND id_empresa = %s
							AND id_articulo_costo = %s
							AND id_precio = %s
						ORDER BY id_articulo_precio DESC;",
							valTpDato($idArticulo, "int"),
							valTpDato($idEmpresa, "int"),
							valTpDato($rowCostoArt['id_articulo_costo'], "int"),
							valTpDato($idPrecioAct, "int"));
						$rsArtPrecio = mysql_query($queryArtPrecio);
						if (!$rsArtPrecio) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
						$totalRowsArtPrecio = mysql_num_rows($rsArtPrecio);
						$rowArtPrecio = mysql_fetch_assoc($rsArtPrecio);
					}
					
					if ($rowPrecio['tipo'] == 0) { // PRECIO SOBRE COSTO
						$montoGanancia = (doubleval($costoUnitario) * doubleval($porcMarkUp / 100));
						$montoGanancia += ($porcMarkUp >= 200) ? 0 : doubleval($costoUnitario);
					} else if ($rowPrecio['tipo'] == 1) { // PRECIO SOBRE VENTA
						$montoGanancia = (doubleval($costoUnitario) * 100) / (100 - doubleval($porcMarkUp));
					}
					
					if ($totalRowsArtPrecio > 0) {
						// VERIFICA QUE NO EXISTAN PRECIOS CON EL MISMO NUMERO DE LOTE
						$queryArtPrecio2 = sprintf("SELECT * FROM iv_articulos_precios
						WHERE id_articulo = %s
							AND id_empresa = %s
							AND id_articulo_costo = %s
							AND id_precio = %s
						ORDER BY id_articulo_precio DESC;",
							valTpDato($idArticulo, "int"),
							valTpDato($idEmpresa, "int"),
							valTpDato($rowCostoArt['id_articulo_costo'], "int"),
							valTpDato($idPrecioAct, "int"));
						$rsArtPrecio2 = mysql_query($queryArtPrecio2);
						if (!$rsArtPrecio2) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
						$totalRowsArtPrecio2 = mysql_num_rows($rsArtPrecio2);
						$rowArtPrecio2 = mysql_fetch_assoc($rsArtPrecio2);
						
						if ($totalRowsArtPrecio2 > 0) {
							$updateSQL = sprintf("UPDATE iv_articulos_precios SET
								precio = %s
							WHERE id_articulo_precio = %s;",
								valTpDato($montoGanancia, "real_inglesa"),
								valTpDato($rowArtPrecio['id_articulo_precio'], "int"));
						} else {
							$updateSQL = sprintf("UPDATE iv_articulos_precios SET
								id_articulo_costo = %s,
								precio = %s
							WHERE id_articulo_precio = %s;",
								valTpDato($rowCostoArt['id_articulo_costo'], "int"),
								valTpDato($montoGanancia, "real_inglesa"),
								valTpDato($rowArtPrecio['id_articulo_precio'], "int"));
						}
						$Result1 = mysql_query($updateSQL);//return array(false, $updateSQL);
						if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__)."<br>".$queryCostoArt."<br>".$queryArtPrecio."<br>".$updateSQL);
					} else {
						$insertSQL = sprintf("INSERT INTO iv_articulos_precios (id_empresa, id_articulo, id_articulo_costo, id_precio, precio)
						VALUE (%s, %s, %s, %s, %s);",
							valTpDato($idEmpresa, "int"),
							valTpDato($idArticulo, "int"),
							valTpDato($rowCostoArt['id_articulo_costo'], "int"),
							valTpDato($idPrecioAct, "int"),
							valTpDato($montoGanancia, "real_inglesa"));
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
					}
				}
			}
		}
	}
		
	return array(true, "");
}

function valorConfiguracion($idConfiguracion, $idEmpresa, $idUsuario = "") {
	global $conex;
	
	$idEmpresa = preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/"," ",utf8_encode(str_replace("\"","",$idEmpresa))));
	$idEmpresa = (strlen($idEmpresa) > 0) ? $idEmpresa : "-1";
	
	// VERIFICA VALORES DE CONFIGURACION
	if ($idUsuario > 0) {
		$queryConfig = sprintf("SELECT config_usu.*
		FROM pg_configuracion_usuario config_usu
			INNER JOIN pg_configuracion config ON (config_usu.id_configuracion = config.id_configuracion)
		WHERE config_usu.id_configuracion = %s AND config_usu.id_usuario = %s AND config_usu.id_empresa IN (%s);",
			valTpDato($idConfiguracion, "int"),
			valTpDato($idUsuario, "int"),
			valTpDato($idEmpresa, "campo"));
	} else {
		$queryConfig = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
			INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
		WHERE config.id_configuracion = %s AND config_emp.status = 1 AND config_emp.id_empresa IN (%s);",
			valTpDato($idConfiguracion, "int"),
			valTpDato($idEmpresa, "campo"));
	}
	$rsConfig = mysql_query($queryConfig);
	if (!$rsConfig) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$totalRowsConfig = mysql_num_rows($rsConfig);
	$rowConfig = mysql_fetch_assoc($rsConfig);
	
	return array(true, $rowConfig['valor']);
}

/*
* fi: fecha de partida
* intervalo_dias: cantidad de dias a sumar
* sabado_no_lab: incluye al sábado como dia no laborable
*/

include($raiz."clases/adodb-time.inc.php");
if (!function_exists("dateAddLab")) {
	function dateAddLab($fi, $intervalo_dias, $sabado_no_lab = false) {
		$total_dias_nolab = 1;
		
		if ($sabado_no_lab) {
			$total_dias_nolab = 2;
		}
		
		$di = adodb_date('j',$fi);
		$mi = adodb_date('n',$fi);
		$yi = adodb_date('Y',$fi);
		$fecha_inicial = adodb_mktime(0,0,0,$mi,$di,$yi);		
		$fecha_final = $fecha_inicial + ((60*60*24) * $intervalo_dias);		
		$fechai = $fecha_inicial;
		$nolab = 0;
		for ($i = 1; $i <= $intervalo_dias; $i++) {
			$fechai = $fechai + ((60*60*24)); // 1 dia
			$dow = adodb_date('w',$fechai);
			if(($dow == 6 && $total_dias_nolab == 2) || ($dow == 0)){ // domingo
				if($dow == 6){
					$fechai = $fechai + ((60*60*24)*2); // 2 dia
				}else{
					$fechai = $fechai + ((60*60*24)); // 1 dia				
				}
			}
		}
		$fecha_final = $fechai;		
		if(adodb_date('w',$fecha_final) == 6 && $total_dias_nolab == 2){ // domingo
			$fecha_final = $fecha_final + ((60*60*24));
		}
		if(adodb_date('w',$fecha_final) == 0){ // domingo
			$fecha_final = $fecha_final + ((60*60*24));
		}
		
		return $fecha_final;
	}
}
?>