<?php


function formTipoPedidoCompra() {
	$objResponse = new xajaxResponse();
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Agregar Sección");
	
	$objResponse->script("
		document.forms['frmTipoPedidoCompra'].reset();
		
		$('txtTipoPedidoCompra').className = 'inputInicial';
		$('txtClave').className = 'inputInicial';
		$('txtNumeracion').className = 'inputInicial';");
	
	$objResponse->script("		
		$('divFlotante').style.display='';
		centrarDiv($('divFlotante'));
	");
	
	return $objResponse;
}

function cargarTipoPedidoCompra($idTipoPedidoCompra) {
	$objResponse = new xajaxResponse();
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Editar Tipo de Pedido de Compra");
	
	$query = sprintf("SELECT * FROM iv_tipo_pedido_compra WHERE id_tipo_pedido_compra = %s", $idTipoPedidoCompra);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("hddIdTipoPedidoCompra","value",$idTipoPedidoCompra);
	$objResponse->assign("txtTipoPedidoCompra","value",utf8_encode($row['tipo_pedido_compra']));
	$objResponse->assign("txtClave","value",$row['clave']);
	$objResponse->assign("txtNumeracion","value",$row['numeracion']);
	
	$objResponse->script("		
		$('divFlotante').style.display='';
		centrarDiv($('divFlotante'));
	");
	
	return $objResponse;
}

function guardarTipoPedidoCompra($valForm) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($valForm['hddIdTipoPedidoCompra'] > 0) {
		$updateSQL = sprintf("UPDATE iv_tipo_pedido_compra SET
			clave = %s,
			tipo_pedido_compra = %s,
			numeracion = %s
		WHERE id_tipo_pedido_compra = %s",
			valTpDato($valForm['txtClave'], "text"),
			valTpDato($valForm['txtTipoPedidoCompra'], "text"),
			valTpDato($valForm['txtNumeracion'], "int"),
			valTpDato($valForm['hddIdTipoPedidoCompra'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	} else {
		$insertSQL = sprintf("INSERT INTO iv_tipo_pedido_compra (clave, tipo_pedido_compra, numeracion) VALUE (%s, %s, %s)",
			valTpDato($valForm['txtClave'], "text"),
			valTpDato($valForm['txtTipoPedidoCompra'], "text"),
			valTpDato($valForm['txtNumeracion'], "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$idTipoPedidoCompra = mysql_insert_id();
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Tipo de Pedido de Compra Guardado con Éxito");
	
	$objResponse->script("
		window.location.reload();");
		
	return $objResponse;
}

function eliminarTipoPedidoCompra($valForm) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"iv_tipo_pedido_compra_list","eliminar")) {
		if (isset($valForm['cbxTipoPedidoCompra'])) {
			mysql_query("START TRANSACTION;");
			
			foreach ($valForm['cbxTipoPedidoCompra'] as $indiceItm=>$valorItm) {
				$deleteSQL = sprintf("DELETE FROM iv_tipo_pedido_compra WHERE id_tipo_pedido_compra = %s",
					valTpDato($valorItm, "int"));
				
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			}
			
			mysql_query("COMMIT;");
		
			$objResponse->script("
				window.location.reload();");
		}
	}
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"formTipoPedidoCompra");
$xajax->register(XAJAX_FUNCTION,"cargarTipoPedidoCompra");
$xajax->register(XAJAX_FUNCTION,"guardarTipoPedidoCompra");
$xajax->register(XAJAX_FUNCTION,"eliminarTipoPedidoCompra");
?>