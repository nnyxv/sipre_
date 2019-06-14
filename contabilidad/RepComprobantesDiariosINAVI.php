<?php session_start();
set_time_limit(0);
require('fpdf.php');
include("FuncionesPHP.php");

class PDF extends FPDF{
	var $cConcepto;
	var $cComprobante;
	var $cFecha;
	var $cCC;
	var $cElaborado;
	//Cabecera de página
	function Header(){                                                         
		// llamando a la funcion encabezado del fpdf
		$logo = "LogoINAVI.png";//logo del reporte
		// $titulo='Select '.$sCampos.' from '.$sTabla. " where " . $sCondicion ;
		$titulo ="ASIENTO DE DIARIO";//titulo del reporte
		//$logo variable que contiene la direccion de la imagen
		//$gerencia variable que contiene el nombre de la gerencia
		//$titulo variable que contiene el titulo del reporte
		//Logo
		// $this->Image($logo,10,8,33);
		//Movernos a la derecha
		
		$con = ConectarBD();                                                     
				 
		$SqlDes='Select descrip from parametros';
		$exc = EjecutarExec($con,$SqlDes) or die($SqlDes);
		while ($row = ObtenerFetch($exc)) {
			$descrip = $row[0];
		}
		
		$this->SetFont('Arial','B',12);
		$this->Cell(8,5,strtoupper($descrip),'',0);
		$this->Cell(242,5,'',0,0);
		//colocar fecha
		$fecha = date("d/m/Y");
		//Arial bold 7
		$this->SetFont('Arial','B',7);
		$this->Cell(30,5,'Fecha: '.$fecha,0,0);
		//Salto de línea
		$this->Ln(3);
		//Movernos a la derecha
		$this->Cell(250,5,'',0,0);
		//colocar hora
		$hora = date("g:i:s A");
		//Arial bold 7
		$this->SetFont('Arial','B',7);
		$this->Cell(30,5,'Hora: '.$hora,0,0);
		//Salto de línea
		$this->Ln(3);
		//color del texto
		$this->SetTextColor(0,0,0);
		//Arial bold 10
		$this->SetFont('Arial','B',10);
		//Título del reporte
		$this->Cell(215,5,$titulo,0,0,'C');
		//Salto de línea
		$this->SetFont('Arial','',8);
		$this->Cell(60,5,"Unidad Imputación: ".substr($this->cCC,0,30),0,1,'R');
		$this->SetFont('Arial','',8);
		$this->Cell(273,10,'Pagina '.$this->PageNo().'/{nb}',0,0,'R');
		$this->Ln(7);
		$this->Group();
	}
	
	//Pie de página
	function Footer(){
		$this->SetY(-15);
		$campos = array('','OBSERVACIONES:','PREPARADO POR:','REVISADO Y APROBADO POR:','AUDITADO POR:','TRANSCRITO:','DIARIO POR:');
		$Ancho = array ('1','105','45','50','32','23','23');
		$Tamaño = array ('8','8','8','8','8','8','8');
		$aTipoLetra = array ('B','B','B','B','B','B','B');
		$Alinear = array ('C','L','L','L','L','L','L');
		$Bordes = array ('0','1','1','1','1','1','1');
		$this->enc_detalle($campos,$Ancho,4,$Tamaño,$aTipoLetra,$Alinear,$Bordes);
		$this->Ln(0);
		$campos = array('','',$this->cElaborado,'','','','');
		$Ancho = array ('1','105','45','50','32','23','23');
		$Tamaño = array ('10','10','10','10','10','10','10');
		$aTipoLetra = array ('','B','B','B','B','B','B');
		$Alinear = array ('C','C','L','L','L','L','R');
		$Bordes = array ('0','1','1','1','1','1','1');
		$MaxLon = array ('0','0','0','0','30','0','0');
		$this->enc_detalle($campos,$Ancho,8,$Tamaño,$aTipoLetra,$Alinear,$Bordes,$MaxLon);
	}
	
	function Group(){                    
		$this->Ln(3);                                     
		$this->SetFont('Arial','B',8);
		$this->Cell(237,10,'  Concepto.','LTR',0);
		$sCadena = 'Comprobante: '. $this->cComprobante;
		$this->Cell(36,5,$sCadena,1,1);
		$this->SetX(247);
		$this->Cell(36,5,' Fecha: '.$this->cFecha,1,0);
		
		$this->Ln(5);        
		$this->SetFont('Arial','',8);
		$MaxLong =114+1;
		$Cont =0;
		$s=str_replace("\r",' ',substr($this->cConcepto,0,255));
		$s= '  '.$s;
		$this->MultiCell(237,5,$s,'LR','L');
		//  $this->SetX(247);	
		$this->SetX(0);
		
		/*
		$this->Cell(36,5,$this->cComprobante,1,0);
		$this->Ln(5); 
		
		$this->Cell(237,5,'','LR',0);
		$this->Cell(36,5,'Fecha:',1,0);
		$this->Ln(5);        
		//$this->Cell(8,5,'','',0);
		$this->Cell(237,5,'','LBR',0);
		$this->Cell(36,5,$this->cFecha,1,0);
		*/	
		$this->Ln(1);
		$campos = array('NRO','IMPUTACIÓN','CUENTA NÚMERO','DG','REFERENCIA','DESC. DE LA CUENTA','CT','DESC. DEL MOVIMIENTO','DEBE','HABER');
		$Ancho = array ('10','20','27','8','25','82','8','47','23','23');
		$Tamaño = array ('8','8','8','8','8','8','8','8','8','8');
		$aTipoLetra = array ('B','B','B','B','B','B','B','B','B','B');
		$Alinear = array ('C','C','C','L','C','C','L','C','R','R');
		$Bordes = array ('1','1','1','1','1','1','1','1','1','1');
		$MaxLon = array ('0','0','0','0','0','0','0','30','0','0');
		$this->enc_detalle($campos,$Ancho,8,$Tamaño,$aTipoLetra,$Alinear,$Bordes,$MaxLon);			
	}
}

//Creación del objeto de la clase heredada
if ($cDesde1== ""){
	$cDesde1= '0';
	$cHasta1= '9999999';
}

if ($cDesde3 != ""){
	if ($cDesde3 == "Diarios"){
		$oTablaSelec = 'D';
	}
	if ($cDesde3 == "Posteriores"){
		$oTablaSelec = 'P';
	}
	if ($cDesde3 == "Historicos"){
		$oTablaSelec = 'H';
	}
}

if ($oTablaSelec == 'D'){
	$sTablaEncabeza = "enc_diario"; 
	$sTablaMovimien = "movimien";
}
if ($oTablaSelec == 'P'){
	$sTablaEncabeza = "enc_dif"; 
	$sTablaMovimien = "movimiendif";
}
if ($oTablaSelec == 'H'){
	$sTablaEncabeza = "enc_historico"; 
	$sAno = trim(intval(obFecha($cHasta2,'M')));
	$sTablaMovimien = "movhistorico".$sAno;
}
//if($_SESSION["CCSistema"] != ""){
$EstadoCuenta = "sipre_contabilidad.cuenta";
$EstadoCT = "sipre_contabilidad.transacciones";
$EstadoDT = "sipre_contabilidad.documentos";
$EstadoCC = "sipre_contabilidad.centrocosto";
$EstadoIM = "sipre_contabilidad.centrocosto";
/*}else{
$EstadoCuenta = "cuenta";
$EstadoCT = "transacciones";
$EstadoDT = "documentos";
$EstadoCC = "centrocosto";
$EstadoIM = "centrocosto";
}*/

$con = ConectarBD();
$sCampos= " 
	a.comprobant
	,a.concepto
	,a.fecha
	,c.codigo
	,b.documento
	,c.descripcion
	,b.descripcion
	,b.debe
	,b.haber
	,d.codigo as CT
	,e.codigo as DT
	,f.codigo
	,d.descripcion as CTDES
	,e.descripcion as DTDES
	,g.codigo
	,a.Usuario_i";
		
$sTabla=" 
	$sTablaEncabeza a
	,$sTablaMovimien b
	,$EstadoCuenta c
	,$EstadoCT d
	,$EstadoDT e
	,$EstadoCC f
	,$EstadoIM g";
		
if ($cDesde2 != ''){
	$sCondicion=" f.codigo = a.cc and  a.comprobant = b.comprobant and c.codigo = b.codigo and a.comprobant between '$cDesde1' and '$cHasta1'";
	$sCondicion.= " and b.CT = d.codigo and b.DT = e.codigo"; 
	$sCondicion.= " and a.fecha = b.fecha";
	$sCondicion.= " and a.fecha between '$cDesde2' and '$cHasta2'"; 

	
	if($cDesde4 != ''){ 
		$sCondicion.= " and a.cc = '$cDesde4'"; 
		$sCondicion.= " and b.cc = '$cDesde4'"; 
	}	else{
		$sCondicion.= " and a.cc is not null";
		$sCondicion.= " and b.cc is not null";
	}
	$sCondicion.= " and b.im = g.codigo"; 
	$sCondicion.= " order by a.cc,a.comprobant,a.fecha,OrdenRen"; 
}else{
	$sCondicion=" f.codigo = a.cc and a.comprobant = b.comprobant and c.codigo = b.codigo and a.comprobant between '$cDesde1' and '$cHasta1'"; 
	if($cDesde4 != ''){ 
		$sCondicion.=" and a.cc = '$cDesde4'"; 
		$sCondicion.= " and b.cc = '$cDesde4'";
	}else{
		$sCondicion.= " and a.cc is not null";
		$sCondicion.= " and b.cc is not null";
	
	}
    $sCondicion.= " order by a.cc,a.comprobant,a.fecha,OrdenRen"; 
}
$SqlStr='Select '.$sCampos.' from '.$sTabla. " where " . $sCondicion ;
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

if (NumeroFilas($exc)>0){
	$AddPfd= false;
	$iFila = -1;
	$iNro = 0;
	$iFilaTemp = 0;
	$sComprobanteAnt = '';
	$fechaAnt= '';
	$CCAnt = '';
	$ToMontoDebe = 0;
	$ToMontoHaber = 0;
	while ($row = ObtenerFetch($exc)) {
		$iNro++;
		$iFila++;
		$iFilaTemp++;
		$comprobant = trim(ObtenerResultado($exc,1,$iFila));
		$concepto= trim(ObtenerResultado($exc,2,$iFila));
		$fecha = obFecha(trim(ObtenerResultado($exc,3,$iFila)));
		$CC = trim(ObtenerResultado($exc,12,$iFila));
		$cElaborado = trim(ObtenerResultado($exc,16,$iFila));
		if ($sComprobanteAnt != $comprobant || $fechaAnt != $fecha || $CCAnt != $CC){
			if ($AddPfd){
				$campos = array('TOTALES:',number_format($ToMontoDebe,2),number_format($ToMontoHaber,2));
				$Ancho = array ('232','23','23');
				$Tamaño = array ('12','8','8');
				$TipoLetra = array ('B','B','B');
				$Alinear = array ('R','R','R');
				$Bordes = array ('1','1','1');
				$pdf->enc_detalle($campos,$Ancho,6,$Tamaño,$TipoLetra,$Alinear,$Bordes);
				$ToMontoDebe = 0;
				$ToMontoHaber = 0;
			}
			
			if (!$AddPfd){
				$pdf=new PDF();				
			}
			$pdf->cComprobante=$comprobant;
			$pdf->cFecha=$fecha;
			$pdf->cConcepto=$concepto;
			$pdf->cCC=$CC;
			$pdf->cElaborado=$cElaborado;
			
			if (!$AddPfd){
				$pdf->AliasNbPages();
				$pdf->AddPage('L');
				$pdf->SetFont('Arial','',12);
				$AddPfd=true; 
			}else{
				$pdf->AddPage($pdf->CurOrientation);
			} 
			
			$sComprobanteAnt = $comprobant;
			$fechaAnt = $fecha; 
			$CCAnt = $CC;
		}
				   
		$codigo = trim(ObtenerResultado($exc,4,$iFila));
		$documento = trim(ObtenerResultado($exc,5,$iFila));
		$descripcionCuen = trim(ObtenerResultado($exc,6,$iFila));
		$descripcionMov = trim(ObtenerResultado($exc,7,$iFila));
		$debe = number_format(trim(ObtenerResultado($exc,8,$iFila)),2);
		$haber = number_format(trim(ObtenerResultado($exc,9,$iFila)),2);
		$ToMontoDebe = $ToMontoDebe + ObtenerResultado($exc,8,$iFila);
		$ToMontoHaber = $ToMontoHaber + ObtenerResultado($exc,9,$iFila);
		$CT = ObtenerResultado($exc,10,$iFila);
		$DG = ObtenerResultado($exc,11,$iFila);
		$DescripcionIM = ObtenerResultado($exc,15,$iFila);
		
		$campos = array('  '.$iNro,$DescripcionIM,$codigo,$DG,$documento,$descripcionCuen,$CT,$descripcionMov,$debe,$haber);
		$Ancho = array ('10','20','27','8','25','82','8','47','23','23');
		$Tamaño = array ('8','8','8','8','8','7','8','7','8','8');
		$Alinear = array ('C','C','C','L','L','L','L','L','R','R');
		$Bordes = array ('BRL','BRL','BRL','BRL','BRL','BRL','BRL','BRL','BRL','BRL');
		$MaxLon = array (0,0,23,23,0,54,0,28,0,0);
		$pdf->enc_detalle($campos,$Ancho,4,$Tamaño,'',$Alinear,$Bordes,$MaxLon);	
	}
	
	if ($AddPfd){
		$campos = array('TOTALES:',number_format($ToMontoDebe,2),number_format($ToMontoHaber,2));
		$Ancho = array ('227','23','23');
		$Tamaño = array ('12','8','8');
		$TipoLetra = array ('B','B','B');
		$Alinear = array ('R','R','R');
		$Bordes = array ('1','1','1');
		$pdf->enc_detalle($campos,$Ancho,6,$Tamaño,$TipoLetra,$Alinear,$Bordes);
		$ToMontoDebe = 0;
		$ToMontoHaber = 0;
	}
}else{
	$pdf=new PDF();
	$pdf->AliasNbPages();
	$pdf->AddPage('L');
	$pdf->SetFont('Arial','',12);
}
$pdf->Output();
?>