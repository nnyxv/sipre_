<?php

require_once ("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("24","20","24");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"20");

$pdf->SetFillColor(204,204,204);
$pdf->SetDrawColor(153,153,153);
$pdf->SetLineWidth(1);
/**************************** ARCHIVO PDF ****************************/
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);
$idEmpresa = $valCadBusq[0];
$idCuenta = $valCadBusq[1];
$idConciliacion = $valCadBusq[2];

$idEmpresa = ($idEmpresa > 0) ? $idEmpresa : 100 ;

$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) { die(mysql_error()."<br><br>Line: ".__LINE__); }
$rowEmp = mysql_fetch_assoc($rsEmp);

$fecha= time();



/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// RESUMEN CONCILIACION ///////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$query = sprintf("SELECT 
  bancos.nombreBanco,
  te_estado_cuenta.id_cuenta,
  te_estado_cuenta.fecha_registro,
  te_estado_cuenta.tipo_documento,
  te_estado_cuenta.numero_documento,
  te_estado_cuenta.monto,
  cuentas.numeroCuentaCompania
FROM
  te_estado_cuenta
  INNER JOIN cuentas ON (te_estado_cuenta.id_cuenta = cuentas.idCuentas)
  INNER JOIN bancos ON (cuentas.idBanco = bancos.idBanco)
WHERE
  te_estado_cuenta.id_conciliacion=".$idConciliacion."");
  

$rs = mysql_query($query);
if (!$rs) { die(mysql_error()."\n\nLine: ".__LINE__); }
$row = mysql_fetch_assoc($rs);


$queryDP = sprintf("SELECT COUNT(*) as totalRegistros, sum(monto) as monto
FROM te_estado_cuenta
WHERE te_estado_cuenta.tipo_documento LIKE 'DP'
AND te_estado_cuenta.id_conciliacion =".$idConciliacion."");
$rsDP = mysql_query($queryDP);
if (!$rsDP) { die(mysql_error()."\n\nLine: ".__LINE__); }
$rowDP = mysql_fetch_assoc($rsDP);


$queryNC = sprintf("SELECT COUNT(*) as totalRegistros, sum(monto) as monto
FROM te_estado_cuenta
WHERE te_estado_cuenta.tipo_documento LIKE 'NC'
AND te_estado_cuenta.id_conciliacion =".$idConciliacion."");
$rsNC = mysql_query($queryNC);
if (!$rsNC) { die(mysql_error()."\n\nLine: ".__LINE__); }
$rowNC = mysql_fetch_assoc($rsNC);


$queryND = sprintf("SELECT COUNT(*) as totalRegistros, sum(monto) as monto FROM te_estado_cuenta
WHERE te_estado_cuenta.tipo_documento LIKE 'ND'
AND te_estado_cuenta.id_conciliacion =".$idConciliacion."");
$rsND = mysql_query($queryND);
if (!$rsND) { die(mysql_error()."\n\nLine: ".__LINE__); }
$rowND = mysql_fetch_assoc($rsND);


$queryTR = sprintf("SELECT COUNT(*) as totalRegistros, sum(monto) as monto
FROM te_estado_cuenta
WHERE te_estado_cuenta.tipo_documento LIKE 'TR'
AND te_estado_cuenta.id_conciliacion =".$idConciliacion."");
$rsTR = mysql_query($queryTR);
if (!$rsTR) { die(mysql_error()."\n\nLine: ".__LINE__); }
$rowTR = mysql_fetch_assoc($rsTR);


$queryCH = sprintf("SELECT COUNT(*) as totalRegistros, sum(monto) as monto
FROM te_estado_cuenta
WHERE te_estado_cuenta.tipo_documento LIKE 'CH'
AND te_estado_cuenta.id_conciliacion =".$idConciliacion."");
$rsCH = mysql_query($queryCH);
if (!$rsCH) { die(mysql_error()."\n\nLine: ".__LINE__); }
$rowCH = mysql_fetch_assoc($rsCH);


$queryCHAnuluados = sprintf("SELECT COUNT(*) as totalRegistros, SUM(monto) as monto 
FROM te_estado_cuenta
WHERE tipo_documento LIKE 'CH ANULADO' 
AND id_cuenta = (SELECT id_cuenta FROM te_conciliacion WHERE id_conciliacion = %s)
AND DATE_FORMAT(fecha_registro, '%%Y-%%m') = (SELECT DATE_FORMAT(fecha, '%%Y-%%m') FROM te_conciliacion WHERE id_conciliacion = %s) ",
        $idConciliacion,
        $idConciliacion);
$rsCHAnulados = mysql_query($queryCHAnuluados);
if (!$rsCHAnulados) { die(mysql_error()."\n\nLine: ".__LINE__); }
$rowCHAnulados = mysql_fetch_assoc($rsCHAnulados);


$queryConciliacion = sprintf("SELECT * FROM te_conciliacion WHERE id_conciliacion = '%s'",$idConciliacion);
$rsConciliacion = mysql_query($queryConciliacion);
if (!$rsConciliacion) { die(mysql_error()."\n\nLine: ".__LINE__); }
$rowConciliacion = mysql_fetch_array($rsConciliacion);

$queryConAnt = sprintf("SELECT * FROM te_conciliacion WHERE id_cuenta = ".$idCuenta." AND id_conciliacion < ".$idConciliacion." ORDER BY id_conciliacion DESC LIMIT 0 , 1");
$rsConAnt = mysql_query($queryConAnt);
if (!$rsConAnt) { die(mysql_error()."\n\nLine: ".__LINE__); }
$rowConAnt = mysql_fetch_array($rsConAnt);





//SELECT NO CONCILIADOS:
/******************************************************/
$queryDP2 = sprintf("SELECT COUNT(*) as totalRegistros, sum(monto) as monto
FROM te_estado_cuenta
WHERE te_estado_cuenta.tipo_documento LIKE 'DP'
AND te_estado_cuenta.estados_principales !=1 AND te_estado_cuenta.estados_principales !=0 AND te_estado_cuenta.desincorporado !=0
AND (te_estado_cuenta.id_conciliacion = 0 OR te_estado_cuenta.id_conciliacion > %s)
AND id_cuenta = (SELECT id_cuenta FROM te_conciliacion WHERE id_conciliacion = %s)
AND DATE(fecha_registro) <= (SELECT DATE(fecha) FROM te_conciliacion WHERE id_conciliacion = %s) ",
        $idConciliacion,
        $idConciliacion,
        $idConciliacion);
$rsDP2 = mysql_query($queryDP2);
if (!$rsDP2) { die(mysql_error()."\n\nLine: ".__LINE__); }
$rowDP2 = mysql_fetch_assoc($rsDP2);


$queryNC2 = sprintf("SELECT COUNT(*) as totalRegistros, sum(monto) as monto
FROM te_estado_cuenta
WHERE te_estado_cuenta.tipo_documento LIKE 'NC'
AND te_estado_cuenta.estados_principales !=1 AND te_estado_cuenta.estados_principales !=0 AND te_estado_cuenta.desincorporado !=0
AND (te_estado_cuenta.id_conciliacion = 0 OR te_estado_cuenta.id_conciliacion > %s)
AND id_cuenta = (SELECT id_cuenta FROM te_conciliacion WHERE id_conciliacion = %s)
AND DATE(fecha_registro) <= (SELECT DATE(fecha) FROM te_conciliacion WHERE id_conciliacion = %s) ",
        $idConciliacion,
        $idConciliacion,
        $idConciliacion);
$rsNC2 = mysql_query($queryNC2);
if (!$rsNC2) { die(mysql_error()."\n\nLine: ".__LINE__); }
$rowNC2 = mysql_fetch_assoc($rsNC2);


$queryND2 = sprintf("SELECT COUNT(*) as totalRegistros, sum(monto) as monto FROM te_estado_cuenta
WHERE te_estado_cuenta.tipo_documento LIKE 'ND'
AND te_estado_cuenta.estados_principales !=1 AND te_estado_cuenta.estados_principales !=0 AND te_estado_cuenta.desincorporado !=0
AND (te_estado_cuenta.id_conciliacion = 0 OR te_estado_cuenta.id_conciliacion > %s)
AND id_cuenta = (SELECT id_cuenta FROM te_conciliacion WHERE id_conciliacion = %s)
AND DATE(fecha_registro) <= (SELECT DATE(fecha) FROM te_conciliacion WHERE id_conciliacion = %s) ",
        $idConciliacion,
        $idConciliacion,
        $idConciliacion);
$rsND2 = mysql_query($queryND2);
if (!$rsND2) { die(mysql_error()."\n\nLine: ".__LINE__); }
$rowND2 = mysql_fetch_assoc($rsND2);


$queryTR2 = sprintf("SELECT COUNT(*) as totalRegistros, sum(monto) as monto
FROM te_estado_cuenta
WHERE te_estado_cuenta.tipo_documento LIKE 'TR'
AND te_estado_cuenta.estados_principales !=1 AND te_estado_cuenta.estados_principales !=0 AND te_estado_cuenta.desincorporado !=0
AND (te_estado_cuenta.id_conciliacion = 0 OR te_estado_cuenta.id_conciliacion > %s)
AND id_cuenta = (SELECT id_cuenta FROM te_conciliacion WHERE id_conciliacion = %s)
AND DATE(fecha_registro) <= (SELECT DATE(fecha) FROM te_conciliacion WHERE id_conciliacion = %s) ",
        $idConciliacion,
        $idConciliacion,
        $idConciliacion);
$rsTR2 = mysql_query($queryTR2);
if (!$rsTR2) { die(mysql_error()."\n\nLine: ".__LINE__); }
$rowTR2 = mysql_fetch_assoc($rsTR2);


$queryCH2 = sprintf("SELECT COUNT(*) as totalRegistros, sum(monto) as monto
FROM te_estado_cuenta
WHERE te_estado_cuenta.tipo_documento LIKE 'CH'
AND te_estado_cuenta.estados_principales !=1 AND te_estado_cuenta.estados_principales !=0 AND te_estado_cuenta.desincorporado !=0
AND (te_estado_cuenta.id_conciliacion = 0 OR te_estado_cuenta.id_conciliacion > %s)
AND id_cuenta = (SELECT id_cuenta FROM te_conciliacion WHERE id_conciliacion = %s)
AND DATE(fecha_registro) <= (SELECT DATE(fecha) FROM te_conciliacion WHERE id_conciliacion = %s) ",
        $idConciliacion,
        $idConciliacion,
        $idConciliacion);
$rsCH2 = mysql_query($queryCH2);
if (!$rsCH2) { die(mysql_error()."\n\nLine: ".__LINE__); }
$rowCH2 = mysql_fetch_assoc($rsCH2);

/*
$queryCHAnuluados2 = sprintf("SELECT COUNT(*) as totalRegistros, SUM(monto) as monto 
FROM te_estado_cuenta
WHERE tipo_documento LIKE 'CH ANULADO' 
AND te_estado_cuenta.estados_principales !=1 AND te_estado_cuenta.desincorporado !=0
AND (te_estado_cuenta.id_conciliacion = 0 OR te_estado_cuenta.id_conciliacion > %s)
AND id_cuenta = (SELECT id_cuenta FROM te_conciliacion WHERE id_conciliacion = %s)
AND DATE_FORMAT(fecha_registro, '%%Y-%%m') = (SELECT DATE_FORMAT(fecha, '%%Y-%%m') FROM te_conciliacion WHERE id_conciliacion = %s) ",
        $idConciliacion,
        $idConciliacion,
        $idConciliacion);
$rsCHAnulados2 = mysql_query($queryCHAnuluados2);
if (!$rsCHAnulados2) { die(mysql_error()."\n\nLine: ".__LINE__); }
$rowCHAnulados2 = mysql_fetch_assoc($rsCHAnulados2);
*/

/****************************************************/
//FIN SELECT NO CONCILIADOS







	$pdf->AddPage();
	
	/* CABECERA DEL DOCUMENTO */
	if ($idEmpresa != "") {
		$pdf->Image("../../".$rowEmp['logo_familia'],15,17,70);
		
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',5);
		$pdf->SetX(88);
		$pdf->Cell(200,9,htmlentities($rowEmp['nombre_empresa']),0,2,'L');
		
		if (strlen($rowEmp['rif']) > 1) {
			$pdf->SetX(88);
			$pdf->Cell(200,9,htmlentities("RIF: ".$rowEmp['rif']),0,2,'L');
		}
		if (strlen($rowEmp['direccion']) > 1) {
			$pdf->SetX(88);
			$pdf->Cell(100,9,$rowEmp['direccion'],0,2,'L');
		}
		if (strlen($rowEmp['web']) > 1) {
			$pdf->SetX(88);
			$pdf->Cell(200,9,htmlentities($rowEmp['web']),0,0,'L');
			$pdf->Ln();
		}
	}
                $pdf->Ln();
		$pdf->Cell('',8,'',0,2);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',10);
                $mesTexto = strtoupper(mostrarmes(date("m",strtotime($rowConciliacion['fecha']))));
		$pdf->Cell(562,16,utf8_decode("DETALLES CONCILIACIÓN  ").date(spanDateFormat,strtotime($rowConciliacion['fecha']))." - ".$mesTexto,0,0,'C');
		$pdf->Ln();
		$pdf->Ln();
		
		$pdf->Cell('',8,'',0,2);
                
                /* FECHA ARRIBA DERECHA */
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',7);
                /*
		$pdf->SetX(485);
		$pdf->Cell(60,12,"Fecha: ",0,0,'R');
		$pdf->Cell(45,12,date(spanDateFormat,$fecha),0,0,'C');
		$pdf->Ln();
		$pdf->Cell('',3);
		$pdf->Ln();*/
			
		
		
		/* COLUMNAS */
		
                //cell(w,h = 0, txt = '', border = 0, ln = 0, align=0, fill = false, link = '');
		$pdf->Cell(281,10,"BANCO: ".$row['nombreBanco']."",'0','L',false);
                $pdf->Ln();
		$pdf->Cell(281,10,utf8_decode("NÚMERO DE CUENTA: ").$row['numeroCuentaCompania']."",'0','L',false);
		$pdf->Ln();
                $pdf->Ln();
                
                $fechaTexto = date("d",strtotime($rowConAnt['fecha']))." de ".mostrarmes(date("m",strtotime($rowConAnt['fecha'])))." de ".date("Y",strtotime($rowConAnt['fecha']));
                $pdf->Cell(400,10,utf8_decode("Saldo según Libros al ".$fechaTexto),'0','L',false);
		//$pdf->Cell(150,10,"ant:".formatoNumero($rowConciliacion['saldo_ant'])."-".formatoNumero($rowConciliacion['monto_libro']),'0','L',true);
		$pdf->Cell(150,10,formatoNumero($rowConciliacion['saldo_ant']),'0','L',true);
                $pdf->Ln();
                $pdf->Ln();
                
                $pdf->SetFont('Arial','B');
                $pdf->Cell(400,10,utf8_decode("Más"));
		$pdf->Ln();
                $pdf->SetFont('');
                
                $pdf->Cell(20,10);
		$pdf->Cell(380,10,utf8_decode("Depósitos Efectuados:"),'0','L',false);
		$pdf->Cell(50,10,$rowDP['totalRegistros'],'0','L',true);
		$pdf->Cell(50,10,formatoNumero($rowDP['monto']),'0','L',true);
		$pdf->Ln();
                
                $pdf->Cell(20,10);
                $pdf->Cell(380,10,"Cheques Anulados:",'0','L',false);
                $pdf->Cell(50,10,$rowCHAnulados['totalRegistros'],'0','L',true);
		$pdf->Cell(50,10,formatoNumero($rowCHAnulados['monto']),'0','L',true);
		$pdf->Ln();
                
                $pdf->Cell(20,10);
		$pdf->Cell(380,10,utf8_decode("Notas de Crédito:"),'0','L',false);
                $pdf->Cell(50,10,$rowNC['totalRegistros'],'0','L',true);
		$pdf->Cell(50,10,formatoNumero($rowNC['monto']),'0','L',true);		
                $pdf->Ln();
                
                $pdf->Cell(20,15);
		$pdf->Cell(480,15,utf8_decode("Sub Total:"),'0','L',false);
                $pdf->SetFont('Arial','U');
                $subtotalMas = $rowDP['monto']+$rowNC['monto']+$rowCHAnulados['monto'];
		$pdf->Cell(50,15,formatoNumero($subtotalMas),'0','L',true);
                $pdf->SetFont('');
		$pdf->Ln();
		$pdf->Ln();
		
                
                
                $pdf->SetFont('Arial','B');
                $pdf->Cell(400,10,utf8_decode("Menos"));
		$pdf->Ln();
                $pdf->SetFont('');		
                
                $pdf->Cell(20,10);
		$pdf->Cell(380,10,utf8_decode("Notas de Débito:"),'0','L',false);
                $pdf->Cell(50,10,$rowND['totalRegistros'],'0','L',true);
		$pdf->Cell(50,10,formatoNumero($rowND['monto']),'0','L',true);
		$pdf->Ln();
		
                $pdf->Cell(20,10);
		$pdf->Cell(380,10,"Cheques:",'0','L',false);
                $pdf->Cell(50,10,$rowCH['totalRegistros']+$rowCHAnulados['totalRegistros'],'0','L',true);
		$pdf->Cell(50,10,formatoNumero($rowCH['monto']+$rowCHAnulados['monto']),'0','L',true);
		$pdf->Ln();
		
                $pdf->Cell(20,10);
		$pdf->Cell(380,10,"Transferencias:",'0','L',false);
                $pdf->Cell(50,10,$rowTR['totalRegistros'],'0','L',true);
		$pdf->Cell(50,10,formatoNumero($rowTR['monto']),'0','L',true);
                $pdf->Ln();
                
                $pdf->Cell(20,15);
		$pdf->Cell(480,15,utf8_decode("Sub Total:"),'0','L',false);
                $pdf->SetFont('Arial','U');
                $subtotalMenos = $rowND['monto']+$rowCH['monto']+$rowTR['monto']+$rowCHAnulados['monto'];
		$pdf->Cell(50,15,formatoNumero($subtotalMenos),'0','L',true);
                $pdf->SetFont('');
		$pdf->Ln();
                $pdf->Ln();
                
                //$pdf->Cell(20,15);
                $fechaTextoEste = date("d",strtotime($rowConciliacion['fecha']))." de ".mostrarmes(date("m",strtotime($rowConciliacion['fecha'])))." de ".date("Y",strtotime($rowConciliacion['fecha']));
		$pdf->Cell(500,15,utf8_decode("Saldo según Libros al ".$fechaTextoEste),'0','L',false);
                $totalConciliadoEste = ($subtotalMas - $subtotalMenos) + $rowConciliacion['saldo_ant'];
		$pdf->Cell(50,15,formatoNumero($totalConciliadoEste),'1','L',true);                
                $pdf->Ln();
                $pdf->Ln();
                
                
                
                $subtotalMas2 = $rowDP2['monto']+$rowNC2['monto'];
                $subtotalMenos2 = $rowND2['monto']+$rowCH2['monto']+$rowTR2['monto'];
               
                //validar si tiene, porque 0 - 5 = -5 y daña la operacion
                if($subtotalMas2 == 0){
                    $subtotalResultado = $subtotalMenos2;
                }elseif($subtotalMenos2 == 0){
                    $subtotalResultado = $subtotalMas2;
                }elseif($subtotalMas2 == 0 && $subtotalMenos2 == 0){
                    $subtotalResultado = 0;
                }else{
                    $subtotalResultado = $subtotalMas2 - $subtotalMenos2;
                }
                
                $totalsubtotal = $totalConciliadoEste - $subtotalResultado;
                
               
		$pdf->Cell(500,15,utf8_decode("Saldo según Banco al ".$fechaTextoEste),'0','L',false);
		$pdf->Cell(50,15,formatoNumero($totalsubtotal),'0','L',true); 		
                $pdf->Ln();
                
                //NO CONCILIADOS
                /******************************************/
                    $pdf->SetFont('Arial','B');
                    $pdf->Cell(400,10,utf8_decode("Más"));
                    $pdf->Ln();
                    $pdf->SetFont('');

                    $pdf->Cell(20,10);
                    $pdf->Cell(380,10,utf8_decode("Depósitos Efectuados:"),'0','L',false);
                    $pdf->Cell(50,10,$rowDP2['totalRegistros'],'0','L',true);
                    $pdf->Cell(50,10,formatoNumero($rowDP2['monto']),'0','L',true);
                    $pdf->Ln();

                   /* $pdf->Cell(20,10);
                    $pdf->Cell(380,10,"Cheques Anulados:",'0','L',false);
                    $pdf->Cell(50,10,$rowCHAnulados2['totalRegistros'],'0','L',true);
                    $pdf->Cell(50,10,formatoNumero($rowCHAnulados2['monto']),'0','L',true);
                    $pdf->Ln();*/

                    $pdf->Cell(20,10);
                    $pdf->Cell(380,10,utf8_decode("Notas de Crédito:"),'0','L',false);
                    $pdf->Cell(50,10,$rowNC2['totalRegistros'],'0','L',true);
                    $pdf->Cell(50,10,formatoNumero($rowNC2['monto']),'0','L',true);		
                    $pdf->Ln();

                    $pdf->Cell(20,15);
                    $pdf->Cell(480,15,utf8_decode("Sub Total:"),'0','L',false);
                    $pdf->SetFont('Arial','U');
                    //$subtotalMas2 = $rowDP2['monto']+$rowNC2['monto'];
                    $pdf->Cell(50,15,formatoNumero($subtotalMas2),'0','L',true);
                    $pdf->SetFont('');
                    $pdf->Ln();
                    $pdf->Ln();



                    $pdf->SetFont('Arial','B');
                    $pdf->Cell(400,10,utf8_decode("Menos"));
                    $pdf->Ln();
                    $pdf->SetFont('');		

                    $pdf->Cell(20,10);
                    $pdf->Cell(380,10,utf8_decode("Notas de Débito:"),'0','L',false);
                    $pdf->Cell(50,10,$rowND2['totalRegistros'],'0','L',true);
                    $pdf->Cell(50,10,formatoNumero($rowND2['monto']),'0','L',true);
                    $pdf->Ln();

                    $pdf->Cell(20,10);
                    $pdf->Cell(380,10,"Cheques:",'0','L',false);
                    $pdf->Cell(50,10,$rowCH2['totalRegistros'],'0','L',true);
                    $pdf->Cell(50,10,formatoNumero($rowCH2['monto']),'0','L',true);
                    $pdf->Ln();

                    $pdf->Cell(20,10);
                    $pdf->Cell(380,10,"Transferencias:",'0','L',false);
                    $pdf->Cell(50,10,$rowTR2['totalRegistros'],'0','L',true);
                    $pdf->Cell(50,10,formatoNumero($rowTR2['monto']),'0','L',true);
                    $pdf->Ln();

                    $pdf->Cell(20,15);
                    $pdf->Cell(480,15,utf8_decode("Sub Total:"),'0','L',false);
                    $pdf->SetFont('Arial','U');
                    //$subtotalMenos2 = $rowND2['monto']+$rowCH2['monto']+$rowTR2['monto'];
                    $pdf->Cell(50,15,formatoNumero($subtotalMenos2),'0','L',true);
                    $pdf->SetFont('');
                    $pdf->Ln();
                    $pdf->Ln();
                    
                    
                    $pdf->Cell(500,15,utf8_decode("Saldo Conciliado en Banco al ".$fechaTextoEste),'0','L',false);
                    $totalConciliadoEste2 = $totalConciliadoEste;
                    $pdf->Cell(50,15,formatoNumero($totalConciliadoEste2),'1','L',true);         
                    
                /******************************************/
                //FIN NO CONCILIADOS
                
                    
		$pdf->Ln();
		$pdf->Ln();
		$pdf->Ln();
                
                
                //DETALLE NO CONCILIADO (similar query del total)
                /***************************************************/
                $queryDP3 = sprintf("SELECT DATE(fecha_registro) as fecha_registro, numero_documento, monto
                FROM te_estado_cuenta
                WHERE te_estado_cuenta.tipo_documento LIKE 'DP'
                AND te_estado_cuenta.estados_principales !=1 AND te_estado_cuenta.estados_principales !=0 AND te_estado_cuenta.desincorporado !=0
                AND (te_estado_cuenta.id_conciliacion = 0 OR te_estado_cuenta.id_conciliacion > %s)
                AND id_cuenta = (SELECT id_cuenta FROM te_conciliacion WHERE id_conciliacion = %s)
                AND DATE(fecha_registro) <= (SELECT DATE(fecha) FROM te_conciliacion WHERE id_conciliacion = %s) ",
                        $idConciliacion,
                        $idConciliacion,
                        $idConciliacion);
                $rsDP3 = mysql_query($queryDP3);
                if (!$rsDP3) { die(mysql_error()."\n\nLine: ".__LINE__); }
                
                if(mysql_num_rows($rsDP3)){
                    $pdf->SetFont('','B');
                    $pdf->Cell(150,10,utf8_decode("Depósitos en Tránsito"),'0','L');
                    $pdf->Ln();
                    $pdf->Cell(50,10,"Fecha",'1','0','C');
                    $pdf->Cell(70,10,utf8_decode("Nro. DP"),'1','0','C');
                    $pdf->Cell(100,10,"Monto",'1','0','C');
                    $pdf->SetFont();
                    $pdf->Ln();
                    
                }
                
                $arrayTotalDp = array();
                while($rowDP3 = mysql_fetch_assoc($rsDP3)){
                    $arrayTotalDp[] = $rowDP3['monto'];
                    $pdf->Cell(50,10,fechaBanco($rowDP3['fecha_registro']),'1','0','C');
                    $pdf->Cell(70,10,$rowDP3['numero_documento'],'1','0','C');
                    $pdf->Cell(100,10,formatoNumero($rowDP3['monto']),'1','0','R');
                    $pdf->Ln();
                }
                
                if(mysql_num_rows($rsDP3)){
                    $pdf->SetFont('','B');
                    $pdf->Cell(200,10,formatoNumero(array_sum($arrayTotalDp)),'0','0','R');
                    $pdf->SetFont();
                }
                


                $queryNC3 = sprintf("SELECT DATE(te_estado_cuenta.fecha_registro) as fecha_registro, te_estado_cuenta.numero_documento, te_estado_cuenta.monto, 
                    cp_proveedor.nombre
                FROM te_estado_cuenta
                LEFT JOIN te_nota_credito ON te_nota_credito.id_nota_credito = te_estado_cuenta.id_documento
                LEFT JOIN cp_proveedor ON cp_proveedor.id_proveedor = te_nota_credito.id_beneficiario_proveedor
                WHERE te_estado_cuenta.tipo_documento LIKE 'NC'
                AND te_estado_cuenta.estados_principales !=1 AND te_estado_cuenta.estados_principales !=0 AND te_estado_cuenta.desincorporado !=0
                AND (te_estado_cuenta.id_conciliacion = 0 OR te_estado_cuenta.id_conciliacion > %s)
                AND id_cuenta = (SELECT id_cuenta FROM te_conciliacion WHERE id_conciliacion = %s)
                AND DATE(te_estado_cuenta.fecha_registro) <= (SELECT DATE(fecha) FROM te_conciliacion WHERE id_conciliacion = %s) ",
                        $idConciliacion,
                        $idConciliacion,
                        $idConciliacion);
                $rsNC3 = mysql_query($queryNC3);
                if (!$rsNC3) { die(mysql_error()."\n\nLine: ".__LINE__); }
                
                if(mysql_num_rows($rsNC3)){
                    $pdf->Ln();
                    $pdf->Ln();
                    $pdf->SetFont('','B');
                    $pdf->Cell(150,10,utf8_decode("Notas de Crédito en Tránsito"),'0','L');
                    $pdf->Ln();
                    $pdf->Cell(50,10,"Fecha",'1','0','C');
                    $pdf->Cell(215,10,"Proveedor",'1','0','C');
                    $pdf->Cell(70,10,utf8_decode("Nro. NC"),'1','0','C');
                    $pdf->Cell(100,10,"Monto",'1','0','C');
                    $pdf->SetFont();
                    $pdf->Ln();
                }
                
                $arrayTotalNc = array();
                while($rowNC3 = mysql_fetch_assoc($rsNC3)){
                    $arrayTotalNc[] = $rowNC3['monto'];
                    $pdf->Cell(50,10,fechaBanco($rowNC3['fecha_registro']),'1','0','C');
                    $pdf->Cell(215,10,$rowNC3['nombre'],'1','0','C');
                    $pdf->Cell(70,10,$rowNC3['numero_documento'],'1','0','C');
                    $pdf->Cell(100,10,formatoNumero($rowNC3['monto']),'1','0','R');
                    $pdf->Ln();
                }
                
                if(mysql_num_rows($rsNC3)){
                    $pdf->SetFont('','B');
                    $pdf->Cell(415,10,formatoNumero(array_sum($arrayTotalNc)),'0','0','R');
                    $pdf->SetFont();
                }


                $queryND3 = sprintf("SELECT DATE(te_estado_cuenta.fecha_registro) as fecha_registro, te_estado_cuenta.numero_documento, te_estado_cuenta.monto, 
                    cp_proveedor.nombre
                FROM te_estado_cuenta
                LEFT JOIN te_nota_debito ON te_nota_debito.id_nota_debito = te_estado_cuenta.id_documento
                LEFT JOIN cp_proveedor ON cp_proveedor.id_proveedor = te_nota_debito.id_beneficiario_proveedor
                WHERE te_estado_cuenta.tipo_documento LIKE 'ND'
                AND te_estado_cuenta.estados_principales !=1 AND te_estado_cuenta.estados_principales !=0 AND te_estado_cuenta.desincorporado !=0
                AND (te_estado_cuenta.id_conciliacion = 0 OR te_estado_cuenta.id_conciliacion > %s)
                AND id_cuenta = (SELECT id_cuenta FROM te_conciliacion WHERE id_conciliacion = %s)
                AND DATE(te_estado_cuenta.fecha_registro) <= (SELECT DATE(fecha) FROM te_conciliacion WHERE id_conciliacion = %s) ",
                        $idConciliacion,
                        $idConciliacion,
                        $idConciliacion);
                $rsND3 = mysql_query($queryND3);
                if (!$rsND3) { die(mysql_error()."\n\nLine: ".__LINE__); }
                                
                if(mysql_num_rows($rsND3)){
                    $pdf->Ln();
                    $pdf->Ln();
                    $pdf->SetFont('','B');
                    $pdf->Cell(150,10,utf8_decode("Notas de Débito en Tránsito"),'0','L');
                    $pdf->Ln();
                    $pdf->Cell(50,10,"Fecha",'1','0','C');
                    $pdf->Cell(215,10,"Proveedor",'1','0','C');
                    $pdf->Cell(70,10,utf8_decode("Nro. ND"),'1','0','C');
                    $pdf->Cell(100,10,"Monto",'1','0','C');
                    $pdf->SetFont();
                    $pdf->Ln();
                }
                
                $arrayTotalNd = array();
                while($rowND3 = mysql_fetch_assoc($rsND3)){
                    $arrayTotalNd[] = $rowND3['monto'];
                    $pdf->Cell(50,10,fechaBanco($rowND3['fecha_registro']),'1','0','C');
                    $pdf->Cell(215,10,$rowND3['nombre'],'1','0','C');
                    $pdf->Cell(70,10,$rowND3['numero_documento'],'1','0','C');
                    $pdf->Cell(100,10,formatoNumero($rowND3['monto']),'1','0','R');
                    $pdf->Ln();
                }
                
                if(mysql_num_rows($rsND3)){
                    $pdf->SetFont('','B');
                    $pdf->Cell(415,10,formatoNumero(array_sum($arrayTotalNd)),'0','0','R');
                    $pdf->SetFont();
                }


                $queryCH3 = sprintf("SELECT DATE(te_estado_cuenta.fecha_registro) as fecha_registro, te_estado_cuenta.numero_documento, te_estado_cuenta.monto, 
                    cp_proveedor.nombre
                FROM te_estado_cuenta
                LEFT JOIN te_cheques ON te_cheques.id_cheque = te_estado_cuenta.id_documento
                LEFT JOIN cp_proveedor ON cp_proveedor.id_proveedor = te_cheques.id_beneficiario_proveedor
                WHERE te_estado_cuenta.tipo_documento LIKE 'CH'
                AND te_estado_cuenta.estados_principales !=1 AND te_estado_cuenta.estados_principales !=0 AND te_estado_cuenta.desincorporado !=0
                AND (te_estado_cuenta.id_conciliacion = 0 OR te_estado_cuenta.id_conciliacion > %s)
                AND id_cuenta = (SELECT id_cuenta FROM te_conciliacion WHERE id_conciliacion = %s)
                AND DATE(te_estado_cuenta.fecha_registro) <= (SELECT DATE(fecha) FROM te_conciliacion WHERE id_conciliacion = %s) ",
                        $idConciliacion,
                        $idConciliacion,
                        $idConciliacion);
                $rsCH3 = mysql_query($queryCH3);
                if (!$rsCH3) { die(mysql_error()."\n\nLine: ".__LINE__); }
                
                if(mysql_num_rows($rsCH3)){
                    $pdf->Ln();
                    $pdf->Ln();
                    $pdf->SetFont('','B');
                    $pdf->Cell(150,10,utf8_decode("Cheques en Tránsito"),'0','L');
                    $pdf->Ln();
                    $pdf->Cell(50,10,"Fecha",'1','0','C');
                    $pdf->Cell(215,10,"Proveedor",'1','0','C');
                    $pdf->Cell(70,10,utf8_decode("Nro. CH"),'1','0','C');
                    $pdf->Cell(100,10,"Monto",'1','0','C');
                    $pdf->SetFont();
                    $pdf->Ln();
                }
                
                $arrayTotalCh = array();
                while($rowCH3 = mysql_fetch_assoc($rsCH3)){
                    $arrayTotalCh[] = $rowCH3['monto'];
                    $pdf->Cell(50,10,fechaBanco($rowCH3['fecha_registro']),'1','0','C');
                    $pdf->Cell(215,10,$rowCH3['nombre'],'1','0','C');
                    $pdf->Cell(70,10,$rowCH3['numero_documento'],'1','0','C');
                    $pdf->Cell(100,10,formatoNumero($rowCH3['monto']),'1','0','R');
                    $pdf->Ln();
                }
                
                if(mysql_num_rows($rsCH3)){
                    $pdf->SetFont('','B');
                    $pdf->Cell(415,10,formatoNumero(array_sum($arrayTotalCh)),'0','0','R');
                    $pdf->SetFont();
                }


                /*$queryCHAnuluados3 = sprintf("SELECT *
                FROM te_estado_cuenta
                WHERE tipo_documento LIKE 'CH ANULADO' 
                AND (te_estado_cuenta.id_conciliacion = 0 OR te_estado_cuenta.id_conciliacion > %s)
                AND id_cuenta = (SELECT id_cuenta FROM te_conciliacion WHERE id_conciliacion = %s)
                AND DATE_FORMAT(fecha_registro, '%%Y-%%m') = (SELECT DATE_FORMAT(fecha, '%%Y-%%m') FROM te_conciliacion WHERE id_conciliacion = %s) ",
                        $idConciliacion,
                        $idConciliacion,
                        $idConciliacion);
                $rsCHAnulados3 = mysql_query($queryCHAnuluados3);
                if (!$rsCHAnulados3) { die(mysql_error()."\n\nLine: ".__LINE__); }
                $rowCHAnulados3 = mysql_fetch_assoc($rsCHAnulados3);*/
                
                $queryTR3 = sprintf("SELECT DATE(te_estado_cuenta.fecha_registro) as fecha_registro, te_estado_cuenta.numero_documento, te_estado_cuenta.monto, 
                    cp_proveedor.nombre
                FROM te_estado_cuenta
                LEFT JOIN te_transferencia ON te_transferencia.id_transferencia = te_estado_cuenta.id_documento
                LEFT JOIN cp_proveedor ON cp_proveedor.id_proveedor = te_transferencia.id_beneficiario_proveedor
                WHERE te_estado_cuenta.tipo_documento LIKE 'TR'
                AND te_estado_cuenta.estados_principales !=1 AND te_estado_cuenta.estados_principales !=0 AND te_estado_cuenta.desincorporado !=0
                AND (te_estado_cuenta.id_conciliacion = 0 OR te_estado_cuenta.id_conciliacion > %s)
                AND te_estado_cuenta.id_cuenta = (SELECT id_cuenta FROM te_conciliacion WHERE id_conciliacion = %s)
                AND DATE(te_estado_cuenta.fecha_registro) <= (SELECT DATE(fecha) FROM te_conciliacion WHERE id_conciliacion = %s) ",
                        $idConciliacion,
                        $idConciliacion,
                        $idConciliacion);
                $rsTR3 = mysql_query($queryTR3);
                if (!$rsTR3) { die(mysql_error()."\n\nLine: ".__LINE__); }
                
                if(mysql_num_rows($rsTR3)){
                    $pdf->Ln();
                    $pdf->Ln();
                    $pdf->SetFont('','B');
                    $pdf->Cell(150,10,utf8_decode("Transferencia en Tránsito"),'0','L');
                    $pdf->Ln();
                    $pdf->Cell(50,10,"Fecha",'1','0','C');
                    $pdf->Cell(215,10,"Proveedor",'1','0','C');
                    $pdf->Cell(70,10,utf8_decode("Nro. TR"),'1','0','C');
                    $pdf->Cell(100,10,"Monto",'1','0','C');
                    $pdf->SetFont();
                    $pdf->Ln();
                }
                
                $arrayTotalTr = array();
                while($rowTR3 = mysql_fetch_assoc($rsTR3)){
                    $arrayTotalTr[] = $rowTR3['monto'];
                    $pdf->Cell(50,10,  fechaBanco($rowTR3['fecha_registro']),'1','0','C');
                    $pdf->Cell(215,10,$rowTR3['nombre'],'1','0','C');
                    $pdf->Cell(70,10,$rowTR3['numero_documento'],'1','0','C');
                    $pdf->Cell(100,10,formatoNumero($rowTR3['monto']),'1','0','R');
                    $pdf->Ln();
                }
                
                if(mysql_num_rows($rsTR3)){
                    $pdf->SetFont('','B');
                    $pdf->Cell(415,10,formatoNumero(array_sum($arrayTotalTr)),'0','0','R');
                    $pdf->SetFont();
                }

                
                /***************************************************/
                //FIN DETALLE NO CONCILIADO
                
                /*
		$pdf->Ln();
		$pdf->Ln();
		$pdf->Ln();
		
		
		$pdf->Cell(281,14,"SALDO CONCILIADO:   ",'1','L',false);
		$pdf->Cell(281,14,"".formatoNumero($rowConciliacion['monto_conciliado'])."",'1','L',true);
		$pdf->Ln();
		
		$pdf->Cell(281,14,"DIFERENCIA CONCILIACION:   ",'1','L',false);
		$Diferencia= $rowConciliacion['monto_conciliado']-$rowConciliacion['saldo_banco'];
		$pdf->Cell(281,14,"".formatoNumero($Diferencia)."",'1','L',true);
		$pdf->Ln();
		
		$pdf->Cell(281,14,"SALDO EN LIBROS:   ",'1','L',false);
		$pdf->Cell(281,14,"".formatoNumero($rowConciliacion['monto_libro'])."",'1','L',true);
		$pdf->Ln();
		
			$pdf->SetY(-30);
			$pdf->Cell(0,10,utf8_decode("Página ").$pdf->PageNo()."/{nb}",0,0,'C');
		*/


$pdf->SetY(-30);
$pdf->SetTextColor(0,0,0);
$pdf->SetFont('Arial','I',8);
$pdf->Cell(0,10,utf8_decode("Página ").$pdf->PageNo()."/{nb}",0,0,'C');

                
//GONZALO
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// CONCILIADO ///////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$query = sprintf("SELECT 
  bancos.nombreBanco,
  te_estado_cuenta.id_estado_cuenta,
  te_estado_cuenta.fecha_registro,
  te_estado_cuenta.tipo_documento,
  te_estado_cuenta.id_documento,
  te_estado_cuenta.numero_documento,
  te_estado_cuenta.monto,
  te_estado_cuenta.estados_principales
FROM
  te_estado_cuenta
  INNER JOIN cuentas ON (te_estado_cuenta.id_cuenta = cuentas.idCuentas)
  INNER JOIN bancos ON (cuentas.idBanco = bancos.idBanco)
WHERE
  te_estado_cuenta.desincorporado <> 0 AND te_estado_cuenta.estados_principales = 3 AND te_estado_cuenta.id_cuenta =".$idCuenta." AND te_estado_cuenta.id_conciliacion=".$idConciliacion."");
$rs = mysql_query($query);
if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
$totalRows = mysql_num_rows($rs);

if ($totalRows > 0) {
	
	/* DATA */
	$contFila = 0;
	$fill = false;
	while ($row = mysql_fetch_assoc($rs)) {
		$contFila++;
		
		if ($contFila % 45 == 1) {
			$pdf->AddPage();
			
			/* CABECERA DEL DOCUMENTO */
			if ($idEmpresa != "") {
				$pdf->Image("../../".$rowEmp['logo_familia'],15,17,70);
				
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','',5);
				$pdf->SetX(88);
				$pdf->Cell(200,9,htmlentities($rowEmp['nombre_empresa']),0,2,'L');
				
				if (strlen($rowEmp['rif']) > 1) {
					$pdf->SetX(88);
					$pdf->Cell(200,9,htmlentities("RIF: ".$rowEmp['rif']),0,2,'L');
				}
				if (strlen($rowEmp['direccion']) > 1) {
					$pdf->SetX(88);
					$pdf->Cell(100,9,$rowEmp['direccion'],0,2,'L');
				}
				if (strlen($rowEmp['web']) > 1) {
					$pdf->SetX(88);
					$pdf->Cell(200,9,htmlentities($rowEmp['web']),0,0,'L');
					$pdf->Ln();
				}
			}
	
			$pdf->Cell('',8,'',0,2);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',10);
			$pdf->Cell(562,16,"MOVIMIENTOS DE TESORERIA - CONCILIADO",0,0,'C');
			$pdf->Ln();
			
			$pdf->Cell('',8,'',0,2);
	
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',7);
			$pdf->SetX(485);
			$pdf->Cell(60,12,"Fecha: ",0,0,'R');
			$pdf->Cell(45,12,date(spanDateFormat,$fecha),0,0,'C');
			$pdf->Ln();
					
			
			$pdf->SetTextColor(0);
			$pdf->SetFont('Arial','',9);
			$pdf->SetDrawColor(153);
			$pdf->SetLineWidth(1);
			$pdf->Cell(562,10,"Documentos Conciliados",'B',0,'R');
			$pdf->Ln();
			$pdf->Cell('',3);
			$pdf->Ln();
			
			/* COLUMNAS */
			//Colores, ancho de linea y fuente en negrita
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);	
			$pdf->SetFont('Arial','',6.8);
				
			$arrayTamCol = array("130","52","50","30","190","50","50","10");
			$arrayCol = array("BANCO\n\n",utf8_decode("FOLIO APLICACIÓN"),"FECHA\n\n","TIPO\n\n","BENEFICIARIO\n\n","REF MOV\n\n","IMPORTE\n\n" ,"E");
	
			$posY = $pdf->GetY();
			$posX = $pdf->GetX();
			foreach ($arrayCol as $indice => $valor) {
				$pdf->SetY($posY);
				$pdf->SetX($posX);
								
				$pdf->MultiCell($arrayTamCol[$indice],8,$valor,1,'C',true);
				
				$posX += $arrayTamCol[$indice];
			}
		}
		
		//Restauracion de colores y fuentes
		if ($fill == true)
			$pdf->SetFillColor(234,244,255);
		else
			$pdf->SetFillColor(255,255,255);
		
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('');
		
		
		$pdf->Cell($arrayTamCol[0],14,$row['nombreBanco'],'LR',0,'L',true);
		$pdf->Cell($arrayTamCol[1],14,folio($row['id_documento'],$row['tipo_documento']),'LR',0,'C',true);
		$pdf->Cell($arrayTamCol[2],14,date(spanDateFormat,strtotime($row['fecha_registro'])),'LR',0,'C',true);
		$pdf->Cell($arrayTamCol[3],14,$row['tipo_documento'],'LR',0,'C',true);
		
		if ($row['tipo_documento']=='NC' OR $row['tipo_documento']=='ND' OR $row['tipo_documento']=='DP'){
			$pdf->Cell($arrayTamCol[4],14,$rowEmp['nombre_empresa'],'LR',0,'L',true);
		}else{
			$pdf->Cell($arrayTamCol[4],14,beneficiario($row['id_documento'],$row['tipo_documento']),'LR',0,'L',true);
		}
		
		$pdf->Cell($arrayTamCol[5],14,$row['numero_documento'],'LR',0,'C',true);
		$pdf->Cell($arrayTamCol[6],14,number_format(($row['monto']),2,".",","),'LR',0,'R',true);
		$pdf->Cell($arrayTamCol[7],14,$row['estados_principales'],'LR',0,'C',true);

		$pdf->Ln();
		
		$fill = !$fill;
		
		if (($contFila % 45 == 0) || $contFila == $totalRows) {
			
			
			$pdf->Cell(array_sum($arrayTamCol),0,'','T');
			
			if ($contFila == $totalRows) {
					$pdf->Ln();
					$pdf->SetFillColor(255);
					$pdf->Cell(562,5,"",'T',0,'L',true);
					$pdf->Ln();
					
				$queryConciliacion = sprintf("SELECT * FROM te_conciliacion WHERE id_conciliacion = '%s'",$idConciliacion);
				$rsConciliacion = mysql_query($queryConciliacion) or die(mysql_error());
				$rowConciliacion = mysql_fetch_array($rsConciliacion);
			
					
						$pdf->SetFillColor(204,204,204);
						$pdf->Cell(187,14,"MONTO CONCILIADO:  ".number_format(($rowConciliacion['monto_conciliado']),2,".",","),1,0,'L',true);
						$pdf->Cell(187,14,"TOTAL CREDITOS:  ".number_format(($rowConciliacion['total_credito']),2,".",","),1,0,'L',true);
						$pdf->Cell(187,14,"TOTAL DEBITOS:  ".number_format(($rowConciliacion['total_debito']),2,".",","),1,0,'L',true);
						
					}

			$pdf->SetY(-30);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','I',8);
			$pdf->Cell(0,10,utf8_decode("Página ").$pdf->PageNo()."/{nb}",0,0,'C');
		}
	}
}



                

$pdf->SetDisplayMode("real");
//$pdf->AutoPrint(true);
$pdf->Output("Detalle conciliacion.pdf",'I');







//FUNCIONES COMUNES

function folio($id,$tipo_doc){


	if($tipo_doc == 'NC'){
		$queryNC = sprintf("SELECT folio_tesoreria FROM te_nota_credito WHERE id_nota_credito = '%s'", $id);
		$rsNC = mysql_query($queryNC);
		if (!$rsNC) die(mysql_error()."\n\nLine: ".__LINE__);
		$rowNC = mysql_fetch_array($rsNC);
	
		$respuesta = $rowNC['folio_tesoreria'];
	}
	
	else if($tipo_doc == 'ND'){
		$queryND = sprintf("SELECT folio_tesoreria FROM te_nota_debito WHERE id_nota_debito = '%s'", $id);
		$rsND = mysql_query($queryND);
		if (!$rsND) die(mysql_error()."\n\nLine: ".__LINE__);
		$rowND = mysql_fetch_array($rsND);
	
		$respuesta = $rowND['folio_tesoreria'];
		}
		
	else if($tipo_doc == 'TR'){
		$queryTR = sprintf("SELECT folio_tesoreria FROM te_transferencia WHERE id_transferencia = '%s'", $id);
		$rsTR = mysql_query($queryTR);
		if (!$rsTR) die(mysql_error()."\n\nLine: ".__LINE__);
		$rowTR = mysql_fetch_array($rsTR);
	
		$respuesta = $rowTR['folio_tesoreria'];
		}
	
	else if($tipo_doc == 'CH'){
		$queryCH = sprintf("SELECT folio_tesoreria FROM te_cheques WHERE id_cheque = '%s'", $id);
		$rsCH = mysql_query($queryCH);
		if (!$rsCH) die(mysql_error()."\n\nLine: ".__LINE__);
		$rowCH = mysql_fetch_array($rsCH);
	
		$respuesta = $rowCH['folio_tesoreria'];
		}
	
	else if($tipo_doc == 'DP'){
		$queryDP = sprintf("SELECT folio_deposito FROM te_depositos WHERE id_deposito = '%s'", $id);
		$rsDP = mysql_query($queryDP);
		if (!$rsDP) die(mysql_error()."\n\nLine: ".__LINE__);
		$rowDP = mysql_fetch_array($rsDP);
	
		$respuesta = $rowDP['folio_deposito'];
		}
	
	
	return $respuesta;
}

function beneficiario($id,$tipo_doc){

	if($tipo_doc == 'TR'){
		$queryTR = sprintf("SELECT beneficiario_proveedor, id_beneficiario_proveedor FROM te_transferencia WHERE id_transferencia = '%s'",$id);
		$rsTR = mysql_query($queryTR);
		if (!$rsTR) die(mysql_error()."\n\nLine: ".__LINE__);
		$rowTR = mysql_fetch_array($rsTR);
		
		if ($rowTR['beneficiario_proveedor']==1){
			
				$queryProveedor = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = '%s'",$rowTR['id_beneficiario_proveedor']);
				$rsProveedor = mysql_query($queryProveedor);
				if (!$rsProveedor) die(mysql_error()."\n\nLine: ".__LINE__);
				$rowProveedor = mysql_fetch_array($rsProveedor);
				$respuesta = $rowProveedor['nombre'];
				
			}
		else
			{
				$queryBeneficiario = sprintf("SELECT * FROM te_beneficiarios WHERE id_beneficiario = '%s'",$rowTR['id_beneficiario_proveedor']);
				$rsBeneficiario = mysql_query($queryBeneficiario);
				if (!$rsBeneficiario) die(mysql_error()."\n\nLine: ".__LINE__);
				$rowBeneficiario = mysql_fetch_array($rsBeneficiario);
				$respuesta = $rowBeneficiario['nombre_beneficiario'];
			}
		
		}
	else if($tipo_doc == 'CH'){
		
		$queryCH = sprintf("SELECT beneficiario_proveedor, id_beneficiario_proveedor FROM te_cheques WHERE id_cheque = '%s'",$id);
		$rsCH = mysql_query($queryCH);
		if (!$rsCH) die(mysql_error()."\n\nLine: ".__LINE__);
		$rowCH = mysql_fetch_array($rsCH);
		
		if ($rowCH['beneficiario_proveedor']==1){
			
				$queryProveedor = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = '%s'",$rowCH['id_beneficiario_proveedor']);
				$rsProveedor = mysql_query($queryProveedor);
				if (!$rsProveedor) die(mysql_error()."\n\nLine: ".__LINE__);
				$rowProveedor = mysql_fetch_array($rsProveedor);
				$respuesta = $rowProveedor['nombre'];
				
			}
		else
			{
				$queryBeneficiario = sprintf("SELECT * FROM te_beneficiarios WHERE id_beneficiario = '%s'",$rowCH['id_beneficiario_proveedor']);
				$rsBeneficiario = mysql_query($queryBeneficiario);
				if (!$rsBeneficiario) die(mysql_error()."\n\nLine: ".__LINE__);
				$rowBeneficiario = mysql_fetch_array($rsBeneficiario);
				$respuesta = $rowBeneficiario['nombre_beneficiario'];
			}
		
		}
	
	
	return $respuesta;
}


function mostrarmes($numero_mes){
        switch($numero_mes){
        case "01": $numero_mes="Enero"; break;
        case "02": $numero_mes="Febrero"; break;
        case "03": $numero_mes="Marzo"; break;
        case "04": $numero_mes="Abril"; break;
        case "05": $numero_mes="Mayo"; break;
        case "06": $numero_mes="Junio"; break;
        case "07": $numero_mes="Julio"; break;
        case "08": $numero_mes="Agosto"; break;
        case "09": $numero_mes="Septiembre"; break;
        case "10": $numero_mes="Octubre"; break;
        case "11": $numero_mes="Noviembre"; break;
        case "12": $numero_mes="Diciembre"; break;
       }       
      return $numero_mes;
    }

function fechaBanco($fecha){
    if($fecha != 0 && $fecha != "" && fecha != NULL){
        $dia = date("d", strtotime($fecha));
        $mes = date("m", strtotime($fecha));
        $ano = date("y", strtotime($fecha));
        
        $fechaBanco = $dia."-".mesAbreviado($mes)."-".$ano;
        return $fechaBanco;
    }   
}

function mesAbreviado($numeroMes){
    $mesTexto = mostrarmes($numeroMes);
    $mesCorto = substr($mesTexto, 0, 3);
    return $mesCorto;
}

function formatoNumero($cantidad){
    return number_format($cantidad,2,".",",");
}