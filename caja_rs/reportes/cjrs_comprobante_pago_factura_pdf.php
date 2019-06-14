<?php
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

if ($valCadBusq[0] > 0 && $valCadBusq[1] > 0 && $valCadBusq[2] > 0 && $valCadBusq[3] > 0) {
	$idEmpresa = $valCadBusq[0];
	$nroFactura = $valCadBusq[1];
	$nroRecibo = $valCadBusq[2];
	$idFactura = $valCadBusq[3];
	
	header(sprintf("location: cjrs_recibo_pago_pdf.php?idTpDcto=1&id=%s", $idFactura));
} else if ($valCadBusq[0] > 0 && $valCadBusq[1] > 0 && $valCadBusq[2] > 0) {
	$idEmpresa = $valCadBusq[0];
	$idFactura = $valCadBusq[1];
	$nroRecibo = $valCadBusq[2];
	
	header(sprintf("location: cjrs_recibo_pago_pdf.php?idTpDcto=1&id=%s", $idFactura));
} else {
	$idFactura = $valCadBusq[0];
	$idRecibo = $valCadBusq[1];
	
	header(sprintf("location: cjrs_recibo_pago_pdf.php?idRecibo=%s", $idRecibo));
}

global $spanClienteCxC;
?>