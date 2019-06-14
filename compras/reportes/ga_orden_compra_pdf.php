<?php 
set_time_limit(0);
ini_set('memory_limit', '-1');
require_once("../../connections/conex.php");
session_start();

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

require_once('../../clases/barcode128.inc.php');

/*error_reporting (E_ALL);
ini_set('display_errors', TRUE);	
ini_set('display_startup_errors', TRUE);*/

function calcularPrecio($cant,$precio){
	$precioT = 0;
	$precioT += $cant * $precio;
			
	return $cant;
}

function cambiaLetras($string){	
	$arrayBusqueda = array("Á","É","Í","Ó","Ú", "Ñ");
	$arrayReemplazo = array("á","é","í","ó","ú", "ñ");
	$caden = utf8_decode(str_replace($arrayBusqueda, $arrayReemplazo, utf8_encode($string)));
	
	return ucwords(strtolower($caden));
}

class NumeroALetras{
    private static $UNIDADES = array('',
        'Un ',
        'Dos ',
        'Tres ',
        'Cuatro ',
        'Cinco ',
        'Seis ',
        'Siete ',
        'Ocho ',
        'Nueve ',
        'Diez ',
        'Once ',
        'Doce ',
        'Trece ',
        'Catorce ',
        'Quince ',
        'Dieciseis ',
        'Diecisiete ',
        'Dieciocho ',
        'Diecinueve ',
        'Veinte '
    );

    private static $DECENAS = array(
        'Veinti',
        'Treinta ',
        'Cuarenta ',
        'Cincuenta ',
        'Ssesenta ',
        'Setenta ',
        'Ochenta ',
        'Noventa ',
        'Cien '
    );

    private static $CENTENAS = array( 'Ciento ',
        'Doscientos ',
        'Trescientos ',
        'Cuatrocientos ',
        'Quinientos ',
        'Seiscientos ',
        'Setecientos ',
        'Ochocientos ',
        'Novecientos '
    );

    public static function convertir($number, $moneda = '', $centimos = '', $forzarCentimos = false){
        $converted = '';
        $decimales = '';

        if (($number < 0) || ($number > 999999999)) {
            return 'No es posible convertir el numero a letras';
        }

        $div_decimales = explode('.',$number);

        if(count($div_decimales) > 1){
            $number = $div_decimales[0];
            $decNumberStr = (string) $div_decimales[1];

            if(strlen($decNumberStr) == 2){
                $decNumberStrFill = str_pad($decNumberStr, 9, '0', STR_PAD_LEFT);
                $decCientos = substr($decNumberStrFill, 6);
                $decimales = self::convertGroup($decCientos);
            }
        }else if (count($div_decimales) == 1 && $forzarCentimos){
            $decimales = 'Cero ';
        }

        $numberStr = (string) $number;
        $numberStrFill = str_pad($numberStr, 9, '0', STR_PAD_LEFT);
        $millones = substr($numberStrFill, 0, 3);
        $miles = substr($numberStrFill, 3, 3);
        $cientos = substr($numberStrFill, 6);

        if (intval($millones) > 0) {
            if ($millones == '001') {
                $converted .= 'Un millon ';
            } else if (intval($millones) > 0) {
                $converted .= sprintf('%sMillones ', self::convertGroup($millones));
            }
        }

        if (intval($miles) > 0) {
            if ($miles == '001') {
                $converted .= 'Mil ';
            } else if (intval($miles) > 0) {
                $converted .= sprintf('%sMil ', self::convertGroup($miles));
            }
        }

        if (intval($cientos) > 0) {
            if ($cientos == '001') {
                $converted .= 'Un ';
            } else if (intval($cientos) > 0) {
                $converted .= sprintf('%s ', self::convertGroup($cientos));
            }
        }

        if(empty($decimales)){
            //$valor_convertido = $converted . strtoupper($moneda);
			$valor_convertido = $converted . ($moneda);
        } else {
            //$valor_convertido = $converted . strtoupper($moneda) . 'CON ' . $decimales . ' ' . strtoupper($centimos);
			$valor_convertido = $converted . ($moneda) . 'Con ' . $decimales . ' ' . ($centimos);
        }

        return $valor_convertido;
    }

    private static function convertGroup($n){
        $output = '';

        if ($n == '100') {
            $output = "Cien ";
        } else if ($n[0] !== '0') {
            $output = self::$CENTENAS[$n[0] - 1];
        }

        $k = intval(substr($n,1));

        if ($k <= 20) {
            $output .= self::$UNIDADES[$k];
        } else {
            if(($k > 30) && ($n[2] !== '0')) {
                $output .= sprintf('%sY %s', self::$DECENAS[intval($n[1]) - 2], self::$UNIDADES[intval($n[2])]);
            } else {
                $output .= sprintf('%s%s', self::$DECENAS[intval($n[1]) - 2], self::$UNIDADES[intval($n[2])]);
            }
        }

        return $output;
    }
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class PDF extends PDF_AutoPrint{
// Cabecera de página

	function Header(){		
		$queryEmpresa = "SELECT nombre_empresa,rif,logo_familia,web,nit FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '".$_GET['session']."'"; //ide
		$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error());
		$rowEmpresa = mysql_fetch_array($rsEmpresa);
		$nombreEmp = $rowEmpresa['nombre_empresa'];
		$rifEmp = $rowEmpresa['rif'];
		$web = $rowEmpresa['web'];
		$ruta_logo = "../../".$rowEmpresa['logo_familia']; // Logo
		$titulo = utf8_decode("Solicitud De Compras");
		
		if($rowEmpresa['nit'] != 0){
			$rifDvEmp = $rowEmpresa['nit'];
		} else {
			$rifDvEmp = $rowEmpresa['rif'];
		}
		$this->Image($ruta_logo,10,8,33);
		
		$ruta = "tmp/img_codigo.png";
		$aux = getBarcode($_GET['idOrdenComp'],'tmp/img_codigo');
		$this->Image($ruta, 175,10,20);
		  
		$this->SetFont('Arial','B',9);// Arial bold 15
		$this->Cell(30);//Movernos a la derecha
		$this->Cell(20,5,$nombreEmp,0,0,'L'); // Título
		$this->Ln(5);// Salto de línea
		$this->Cell(30);//Movernos a la derecha
		$this->Cell(20,5,$rowEmpresa['rif'].' D.V: '.$rowEmpresa['nit'],0,0,'L'); // Título
		$this->SetFont('Arial','B',12);// Arial bold 15
		$this->Cell(110,5,"ORDEN DE COMPRA",0,0,'C'); // Título
		$this->Ln(5);// Salto de línea
		$this->Cell(30);//Movernos a la derecha
		$this->SetFont('Arial','B',9);// Arial bold 15
		$this->Write(5,$web,'http://'.$web);
		$this->Ln(20);// Salto de línea
		$this->SetFont('Arial','B',15);// Arial bold 15
		$this->SetY(25);// Posición: a 1,5 cm del final
		
		if(file_exists($ruta)){ 
			unlink($ruta); 
		}
	}
	
	//Tabla Cabecera
	function headerTable($cabecera){
		$ancho = array("5","10","30","81","40","24");//ancho por cada celda de la cabecera
		$posiscion = array("C","C","C","C","C","C");
		foreach($cabecera as $clave => $valor){
			$this->Cell($ancho[$clave],5,$valor,1,0,$posiscion[$clave]);
		}
	}	
	
	function bodyTable($data){
		$ancho = array("5","10","30","81","40","24");//ancho por cada celda de la cabecera
		$posiscion = array("C","L","L","L","C","C");
		$this->Ln();
		foreach($data as $clave => $valor){
			$this->Cell($ancho[$clave],5,$valor,1,0,$posiscion[$clave]);	
		}
	}
}


//CONSULTA 	DATOS DEL PROVEEDOR
$sqlProve = sprintf("SELECT 
	id_orden_compra, 
	ga_orden_compra.id_proveedor, 
	cp_proveedor.nombre AS nombre_proveedor, 
	cp_proveedor.rif AS rif_proveedor, 
	nit,
	cp_proveedor.correo AS correo_proveedor,
	contacto AS persona_contacto,
	cp_proveedor.telefono AS telf_proveedor,
	CONCAT_WS(' ',cp_proveedor.telfcontacto, otrotelf) AS telf_contacto_proveedor,
	cp_proveedor.direccion AS direccion_proveedor,
	cp_proveedor.fax AS fax_proveedor
FROM ga_orden_compra 
	LEFT JOIN cp_proveedor ON cp_proveedor.id_proveedor = ga_orden_compra.id_proveedor 
WHERE id_orden_compra = %s", 
valTpDato($_GET['idOrdenComp'],"int"));
$rsProve = mysql_query($sqlProve) or die(mysql_error()."\n\nLine: ".__LINE__);
$rowProve = mysql_fetch_array($rsProve);

//CONSULTA DETALLES DE LA ORDEN
$sqlOrdeComp = sprintf("SELECT 
	id_orden_compra,
	fecha_entrega,
	fecha_cotizacion,
	ga_orden_compra.observaciones,
	segun_cotizacion,
	tipo_transporte, 
	ga_orden_compra.id_solicitud_compra, 
	CONCAT_WS('-',codigo_empresa,numero_solicitud) AS num_solicitud, 
	fecha_solicitud, 
	ga_orden_compra.id_empresa, 
	nombre_empresa, 
	pg_empresa.rif AS rif_empresa, 
	pg_empresa.nit AS nit_empresa, 
	pg_empresa.direccion AS direccion_empresa, 
	telefono1,
	telefono2,
	telefono3,
	telefono3, 
	id_empleado_contacto,
	CONCAT_WS(' ',contacto.nombre_empleado, contacto.apellido) AS nombre_contacto,
	contacto.email AS email_contacto,
	contacto.id_cargo_departamento,
	pg_cargo_departamento.id_cargo,
	nombre_cargo AS nombre_cargo_contacto,
	id_empleado_recepcion,
	CONCAT_WS(' ',contacto.nombre_empleado, contacto.apellido) AS nombre_recepcion,
	monto_letras,
	subtotal_descuento
FROM ga_orden_compra
	LEFT JOIN ga_solicitud_compra ON ga_solicitud_compra.id_solicitud_compra = ga_orden_compra.id_solicitud_compra
	LEFT JOIN pg_empresa ON pg_empresa.id_empresa = ga_orden_compra.id_empresa
	LEFT JOIN pg_empleado contacto ON contacto.id_empleado = ga_orden_compra.id_empleado_contacto
	LEFT JOIN pg_cargo_departamento ON pg_cargo_departamento.id_cargo_departamento = contacto.id_cargo_departamento
	LEFT JOIN pg_cargo ON pg_cargo.id_cargo = pg_cargo_departamento.id_cargo
	LEFT JOIN pg_empleado recepcion ON recepcion.id_empleado = ga_orden_compra.id_empleado_recepcion
WHERE id_orden_compra = %s", 
valTpDato($_GET['idOrdenComp'],"int"));
$rsOrdeComp = mysql_query($sqlOrdeComp)or die(mysql_error()."\n\nLine: ".__LINE__);
$rowOrdeComp = mysql_fetch_array($rsOrdeComp);

if($rowOrdeComp['fecha_cotizacion'] != NULL){
	$fechCotizacion = date(spanDateFormat,strtotime($rowOrdeComp['fecha_cotizacion']));
}
if($rowOrdeComp['subtotal_descuento'] != 0){
	$descuento = $rowOrdeComp['subtotal_descuento'];
} else {
	$descuento = "";
}
if($rowOrdeComp['nit_empresa'] != 0){
	$rifDvEmp = $rowOrdeComp['nit_empresa'];
} else {
	$rifDvEmp = $rowOrdeComp['rif_empresa'];
}

$sqlDetallOrdeComp = sprintf("SELECT 
	ga_orden_compra_detalle.id_orden_compra_detalle,
	ga_orden_compra_detalle.id_orden_compra,
	ga_orden_compra_detalle.id_articulo,
	descripcion,
	codigo_articulo,
	ga_orden_compra_detalle.cantidad,
	ga_orden_compra_detalle.pendiente,
	ga_orden_compra_detalle.precio_unitario,
	ga_orden_compra_detalle.id_iva,
	ga_orden_compra_detalle.iva,
	ga_orden_compra_detalle.tipo,
	ga_orden_compra_detalle.id_cliente,
	(precio_unitario * cantidad) AS subtotal,
	ga_articulos.codigo_articulo,
	ga_articulos.descripcion,
	ga_tipos_unidad.id_tipo_unidad,
	ga_tipos_unidad.unidad
FROM ga_orden_compra_detalle
	INNER JOIN ga_articulos ON (ga_orden_compra_detalle.id_articulo = ga_articulos.id_articulo)
	INNER JOIN ga_tipos_unidad ON (ga_articulos.id_tipo_unidad = ga_tipos_unidad.id_tipo_unidad)
WHERE id_orden_compra = %s GROUP BY ga_orden_compra_detalle.id_articulo",
valTpDato($_GET['idOrdenComp'],"int"));
$rsDetallOrdeComp = mysql_query($sqlDetallOrdeComp)or die(mysql_error()."\n\nLine: ".__LINE__);
$numrowsDetall = mysql_num_rows($rsDetallOrdeComp);

//CONSULTA GASTO
$sqlGastos= sprintf("SELECT 
	ga_orden_compra_gasto.porcentaje_monto AS porcentaje_monto, 
	ga_orden_compra_gasto.monto AS monto, 
	pg_gastos.nombre as nombre_gasto, 
	if(pg_gastos.estatus_iva = 1,'*','') AS iva
FROM ga_orden_compra_gasto
	INNER JOIN pg_gastos ON (ga_orden_compra_gasto.id_gasto = pg_gastos.id_gasto)
WHERE id_orden_compra = %s;",
valTpDato($_GET['idOrdenComp'],"int"));
$queryGasto = mysql_query($sqlGastos)or die(mysql_error()."\n\nLine: ".__LINE__);;
//$objResponse->alert($sqlIva);
while($rowsGastos = mysql_fetch_array($queryGasto)){
	$gastos[]=$rowsGastos;
	$subtotalGastos += $rowsGastos['monto'];
}

//CONSULTA EL IVA DE LA FACTURA
$sqlIva = sprintf("SELECT 
	ga_orden_compra_iva.iva as iva, 
	sum(ga_orden_compra_iva.`base_imponible`) AS base, 
	SUM(ga_orden_compra_iva.`subtotal_iva`) as subtotal, 
	observacion
FROM ga_orden_compra_iva 
	INNER JOIN pg_iva on (pg_iva.idIva = ga_orden_compra_iva.id_iva) 
WHERE id_orden_compra = %s GROUP BY ga_orden_compra_iva.id_iva ORDER BY ga_orden_compra_iva.iva;",
valTpDato($_GET['idOrdenComp'],"int"));
$queryIva = mysql_query($sqlIva)or die(mysql_error()."\n\nLine: ".__LINE__);
//echo $sqlIva;
while($rowsIva = mysql_fetch_array($queryIva)){
	$iva[] = $rowsIva;
	$subtotalIva += $rowsIva['subtotal'];
}
//Creación del objeto pdf de la clase heredada
$pdf = new PDF('P','mm','A4');
$pdf->mostrarFooter = 1;
$pdf->nombreImpreso = $_SESSION['nombreEmpleadoSysGts'];
$pdf->AliasNbPages();

$pdf->AddPage();

$pdf->Ln(5);// Salto de línea
$pdf->Cell(140);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(25,5,utf8_decode("N° Orden:"),0,0,'R'); // Título
$pdf->SetFont('Arial','',11);
$pdf->Cell(25,5,$rowOrdeComp['id_orden_compra'],0,0,'L'); // Título
$pdf->Ln();// Salto de línea
$pdf->Cell(140);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(25,5,"Fecha:",0,0,'R'); // Título
$pdf->SetFont('Arial','',11);
$pdf->Cell(25,5,date(spanDateFormat, strtotime($rowOrdeComp['fecha_entrega'])),0,0,'L'); // Título  
$pdf->Ln();
$pdf->Cell(140);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(25,5,utf8_decode("Solicitud Compras N°:"),0,0,'R'); // Título
$pdf->SetFont('Arial','',11);
$pdf->Cell(25,5,$rowOrdeComp['num_solicitud'],0,0,'L'); // Título 
 
//DATOS DEL PROVEEDOR
$pdf->Ln(5);// Salto de línea
$pdf->SetFont('Arial','B',11);
$pdf->Cell(190,5,"Datos Del Proveedor",1,0,'C'); // Título	
$pdf->Ln(5);// Salto de línea
$pdf->SetFont('Arial','B',11);
$pdf->Cell(23,5,utf8_decode("Proveedor:"),0,0,'L'); // Título
$pdf->SetFont('Arial','',11); 
$pdf->Cell(112,5,cambiaLetras($rowProve['nombre_proveedor']),0,0,'L'); // Título  
$pdf->SetFont('Arial','B',11);
$pdf->Cell(23,5,$spanProvCxP,0,0,'R'); // Título //$spanProvCxP"Rif:"
$pdf->SetFont('Arial','',11); 
$pdf->Cell(32,5,$rowProve['rif_proveedor'],0,0,'L'); // Título  
$pdf->Ln();
$pdf->SetFont('Arial','B',11);
$pdf->Cell(42,5,"Persona de Contacto:",0,0,'L'); // Título
$pdf->SetFont('Arial','',11); 
$pdf->Cell(123,5,cambiaLetras($rowProve['persona_contacto']),0,0,'L'); // Título  .
$pdf->SetFont('Arial','B',11);
$pdf->Cell(10,5,"D.V:",0,0,'L'); // Título
$pdf->SetFont('Arial','',11); 
$pdf->Cell(15,5,$rowProve['nit'],0,0,'L'); // Título  .
$pdf->SetFont('Arial','B',11);
$pdf->Ln();
$pdf->Cell(13,5,"Email:",0,0,'R'); // Título
$pdf->SetFont('Arial','',11); 
$pdf->Cell(80,5,$rowProve['correo_proveedor'],0,0,'L'); // Título 
$pdf->SetFont('Arial','B',11);
$pdf->Cell(14,5,"Cargo:",0,0,'R'); // Título
$pdf->SetFont('Arial','',11); 
$pdf->Cell(83,5,$rowProve[''],0,0,'L'); // Título  
$pdf->Ln();
$pdf->SetFont('Arial','B',11);
$pdf->Cell(20,5,utf8_decode("Dirección:"),0,0,'L'); // Título
$pdf->SetFont('Arial','',11); 
$pdf->MultiCell(170,5,cambiaLetras($rowProve['direccion_proveedor']),0,'L', false); // Multilinea
$pdf->Ln(); 
$pdf->SetFont('Arial','B',11);
$pdf->Cell(10,5,"Telf:",0,0,'R'); // Título
$pdf->SetFont('Arial','',11); 
$pdf->Cell(70,5,$rowProve['telf_contacto_proveedor'],0,0,'L'); // Título 
$pdf->SetFont('Arial','B',11);
$pdf->Cell(15,5,"Fax:",0,0,'R'); // Título
$pdf->SetFont('Arial','',11); 
$pdf->Cell(50,5,$rowProve['fax_proveedor'],0,0,'L'); // Título  
$pdf->Ln(); 

//DATOS DE LA COMPRA
$pdf->SetFont('Arial','B',11);
$pdf->Cell(190,5,"Datos De La Compras",1,0,'C'); // Título	
$pdf->Ln(5);// Salto de línea
$pdf->SetFont('Arial','B',11);
$pdf->Cell(42,5,"Factura a Nombre de:",0,0,'L'); // Título
$pdf->SetFont('Arial','',11); 
$pdf->Cell(85,5,utf8_decode($rowOrdeComp['nombre_empresa']),0,0,'L'); // Título  
$pdf->SetFont('Arial','B',11);
$pdf->Cell(10,5,$spanProvCxP,0,0,'R'); // Título //$spanProvCxP"Rif:"
$pdf->SetFont('Arial','',11); 
$pdf->Cell(33,5,$rowOrdeComp['rif_empresa'].' D.V: '.$rowOrdeComp['nit_empresa'],0,0,'L'); // Título  
$pdf->Ln();
$pdf->SetFont('Arial','B',11);
$pdf->Cell(42,5,"Persona de Contacto:",0,0,'L'); // Título
$pdf->SetFont('Arial','',11); 
$pdf->Cell(64,5,cambiaLetras($rowOrdeComp['nombre_contacto']),0,0,'L'); // Título  .
$pdf->SetFont('Arial','B',11);
$pdf->Cell(15,5,"Cargo:",0,0,'R'); // Título
$pdf->SetFont('Arial','',11); 
$pdf->Cell(69,5,cambiaLetras($rowOrdeComp['nombre_cargo_contacto']),0,0,'L'); // Título 
$pdf->Ln();
$pdf->SetFont('Arial','B',11);
$pdf->Cell(14,5,"Email:",0,0,'L'); // Título
$pdf->SetFont('Arial','',11); 
$pdf->Cell(176,5,utf8_decode($rowOrdeComp['email_contacto']),0,0,'L'); // Título  
$pdf->Ln();
$pdf->SetFont('Arial','B',11);
$pdf->Cell(46,5,utf8_decode("Resp De La Recepcción:"),0,0,'L'); // Título
$pdf->SetFont('Arial','',11); 
$pdf->Cell(60,5,cambiaLetras($rowOrdeComp['nombre_recepcion']),0,0,'L'); // Título 
$pdf->SetFont('Arial','B',11);
$pdf->Cell(40,5,"Transporte a Cargo:",0,0,'R'); // Título
$pdf->SetFont('Arial','',11); 

switch($rowOrdeComp['tipo_transporte']){
	case 1:
		$transporte = "Propio";
		break;
	case 2:
		$transporte = "Terceros";
		break; 	
}
$pdf->Cell(56,5,$transporte,0,0,'L'); // Título
$pdf->Ln();
$pdf->SetFont('Arial','B',11);
$pdf->Cell(20,5,utf8_decode("Dirección:"),0,0,'L'); // Título
$pdf->SetFont('Arial','',11); 
$pdf->MultiCell(170,5,cambiaLetras($rowOrdeComp['direccion_empresa']),0,'L', false); // Multilinea
$pdf->Ln(); 
$pdf->SetFont('Arial','B',12);
$pdf->Cell(39,5,"Fecha de Entrega:",0,0,'L'); // Título
$pdf->SetFont('Arial','',12); 
$pdf->Cell(151,5,date(spanDateFormat, strtotime($rowOrdeComp['fecha_entrega'])),0,0,'L'); // Título  
$pdf->Ln(); 	
$header = array(utf8_decode("N°"), "Cant.", utf8_decode("Código"), utf8_decode("Descripción"), "Precio Por Unidad","SubTotal");
$pdf->SetFont('Arial','B',10);
$pdf->headerTable($header);

$total = array();
$num = 1;
while($rowDetallOrdeComp = mysql_fetch_array($rsDetallOrdeComp)){
			
	$SubTotal = $rowDetallOrdeComp["cantidad"] * $rowDetallOrdeComp["precio_unitario"];
	$total[] = $SubTotal;
	$tabDatos = array($num++,
					$rowDetallOrdeComp["cantidad"],
					cambiaLetras(substr($rowDetallOrdeComp["codigo_articulo"],0,14)), 
					cambiaLetras(substr($rowDetallOrdeComp["descripcion"],0,43)), 
					number_format($rowDetallOrdeComp["precio_unitario"], 2, ".", ","), 
					number_format($SubTotal, 2, ".", ","));
	$pdf->SetFont('Arial','',10);
	$pdf->bodyTable($tabDatos);
}
if($numrowsDetall < 20){
	for($td = $numrowsDetall; $td <= 19; $td++){
		$tabDatos = array($num++,"","","","","-");
		$pdf->SetFont('Arial','',10);
		$pdf->bodyTable($tabDatos);
	}
}
$pdf->Ln();
$pdf->SetFont('Arial','B',10);
$pdf->Cell(45,5,utf8_decode("Cotizacion N°: ".$rowOrdeComp['segun_cotizacion']),1,0,'L');
$pdf->Cell(81,5,"Gastos",1,0,'C'); // Título
$pdf->Cell(40,5,"Sub-Total:",1,0,'R'); // Título
$pdf->Cell(24,5,number_format(array_sum($total), 2, ".", ","),1,0,'R'); // Título
$pdf->Ln();

if($gastos != NULL){
	foreach($gastos as $key => $value){
		$pdf->SetFont('Arial','',10);
		$pdf->Cell(45,5,utf8_decode(""),1,0,'L');
		$pdf->Cell(40,5,htmlentities($value['nombre_gasto']).$value['iva'],1,0,'R'); // Título
		$pdf->Cell(41,5,$value['porcentaje_monto'],1,0,'L'); // Título
		$pdf->Cell(40,5,"",1,0,'R'); // Título
		$pdf->Cell(24,5,"",1,0,'R'); // Título
		$pdf->Ln();
	}
}

$pdf->SetFont('Arial','',10);
$pdf->Cell(45,5,"De Fecha: ".$fechCotizacion,1,0,'L');
$pdf->Cell(40,5,"Sub-Total Gastos:",1,0,'R'); // Título
$pdf->Cell(41,5,$subtotalGastos,1,0,'L'); // Título
$pdf->Cell(40,5,"Descuento:",1,0,'R'); // Título
$pdf->Cell(24,5,number_format($descuento, 2, ".", ","),1,0,'R'); // Título
$pdf->Ln();
$pdf->SetFont('Arial','',10);
switch($rowsOrdCompa['tipo_pago']){
	case 0:
		$tipoPago = "Crédito"; 
	break;
	case 1:
		$tipoPago = "Contado";
	break;
}
$pdf->Cell(45,5,"Tipo Pago: ".utf8_decode($tipoPago) ,1,0,'L');
$pdf->Cell(81,5,"*Incluye ITBMS:",1,0,'L'); // Título

//var_dump($iva);
if($iva != NULL){
	$num = 1;
	foreach($iva as $key => $value){//RECORRE LO IVAS
		if($num < 2){
			$pdf->Cell(40,5,$value['observacion'].' '.$value['iva'].'%',1,0,'R'); // Título
			$pdf->Cell(24,5,number_format($value['subtotal'], 2, ".", ","),1,0,'R'); // Título
			$pdf->Ln();
		}else {
			//$pdf->Cell(45,5,"",1,0,'L');
			$pdf->Cell(126,5,"",1,0,'L'); // Título
			$pdf->Cell(40,5,$value['observacion'].' '.$value['iva'].'%',1,0,'R'); // Título
			$pdf->Cell(24,5,number_format($value['subtotal'], 2, ".", ","),1,0,'R'); // Título
			$pdf->Ln();	
		}
		$num++;
	}
}else{
	$pdf->Cell(40,5,"",1,0,'R');  // Título
	$pdf->Cell(24,5,"",1,0,'R'); 
	$pdf->Ln();
}							

//Se agrega informción de Bolívares Soberanos - Reconversión Monetaria 2018, quitar cuando sea requerido/////////////////// 
		if($rowOrdeComp['fecha_solicitud'] >= '2018-08-01' and $rowOrdeComp['fecha_solicitud'] < '2018-08-20'){
		    $totalOrdenCompra = (array_sum($total) - $descuento) + $subtotalIva +$subtotalGastos; /*total final de la orden*/
			$pdf->SetFont('Arial','',10);
			$pdf->Cell(126,5,"Son: ".cambiaLetras($rowOrdeComp['monto_letras']),1,0,'L'); // Título 
			$pdf->SetFont('Arial','B',9);
			$pdf->Cell(40,5,"Total (Bs):",1,0,'R'); // Título 
			$pdf->Cell(24,5,number_format($totalOrdenCompra, 2, ".", ","),1,0,'R'); // Título
			$totalOrdenReconv = $totalOrdenCompra/100000;
			$pdf->Ln(5);//salto de linea
			$pdf->SetFont('Arial','',10);			
			$letras = NumeroALetras::convertir(number_format($totalOrdenReconv,2, ".", ""), '', utf8_decode('Céntimos'));			
			$pdf->Cell(126,5,"Son: ".$letras,1,0,'L'); // Título 			
			$pdf->SetFont('Arial','B',10);
			$pdf->Cell(40,5,"Total (Bs.S):",1,0,'R'); // Título 
			$pdf->Cell(24,5,number_format($totalOrdenReconv, 2, ".", ","),1,0,'R'); // Título
		}else if($rowOrdeComp['fecha_solicitud'] >= '2018-08-20'){
			$totalOrdenCompra = (array_sum($total) - $descuento) + $subtotalIva +$subtotalGastos; /*total final de la orden*/
			$totalOrdenReconv = $totalOrdenCompra;
			$pdf->SetFont('Arial','',10);
			$letras = NumeroALetras::convertir(number_format($totalOrdenReconv,2, ".", ""), '', utf8_decode('Céntimos'));
			$pdf->Cell(126,5,"Son: ".$letras,1,0,'L'); // Título 
			$pdf->SetFont('Arial','B',10);
			$pdf->Cell(40,5,"Total (Bs.S):",1,0,'R'); // Título 
			$pdf->Cell(24,5,number_format($totalOrdenReconv, 2, ".", ","),1,0,'R'); // Título
			$pdf->Ln(5);
           	$pdf->SetFont('Arial','',10);
			$pdf->Cell(126,5,"Son: ".cambiaLetras($rowOrdeComp['monto_letras']),1,0,'L'); // Título 
			$pdf->SetFont('Arial','B',10);
			$pdf->Cell(40,5,"Total (Bs):",1,0,'R'); // Título 
			$pdf->Cell(24,5,number_format($totalOrdenCompra*100000, 2, ".", ","),1,0,'R'); // Título
		}else{
			$totalOrdenCompra = (array_sum($total) - $descuento) + $subtotalIva +$subtotalGastos; /*total final de la orden*/
			$pdf->SetFont('Arial','',10);
			$pdf->Cell(126,5,"Son: ".cambiaLetras($rowOrdeComp['monto_letras']),1,0,'L'); // Título 
			$pdf->SetFont('Arial','B',9);
			$pdf->Cell(40,5,"Total (Bs):",1,0,'R'); // Título 
			$pdf->Cell(24,5,number_format($totalOrdenCompra, 2, ".", ","),1,0,'R'); // Título
		}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$pdf->Ln(10);
$pdf->SetFont('Arial','',10);
$pdf->MultiCell(190,5,"Observaciones: ".cambiaLetras($rowOrdeComp['observaciones']),1,'L', false); // Multilinea

$pdf->AddPage();//SALTO DE PAG
$pdf->Ln();
//DETALLES DE LA SOLICITUD
$pdf->Cell(190,5,utf8_decode("Aprobación"),1,0,'C'); // Título
$pdf->Ln();
$pdf->Cell(95,5,utf8_decode("Preparado Por"),1,0,'C'); // Título
$pdf->Cell(95,5,utf8_decode("Aprobado Por"),1,0,'C'); // Título
$pdf->Ln();
$pdf->SetFont('Arial','B',11);
$pdf->Cell(95,15,"Nombre y Firma:",1,0,'L'); // Título
$pdf->Cell(95,15,"Nombre y Firma:",1,0,'L'); // Título
$pdf->Ln();
$pdf->Cell(95,5,"Fecha:",1,0,'L'); // Título
$pdf->Cell(95,5,"Fecha:",1,0,'L'); // Título
$pdf->Ln();
$pdf->Cell(190,5,utf8_decode("Condición de la Compra, Precio, Calidad y Oportunidades de Entrega "),0,0,'L'); // Título
$pdf->Ln();
$pdf->SetFont('Arial','',10);

$pdf->MultiCell(190,5,utf8_decode("
1.- Despachar el pedido con nota de entrega y enviar la factura a la dirección que aparece al pie de página del presente documento.
2.- Las condiciones de compra que aparecen en el presente documento, tales como precio, especificaciones de calidad, plazos de entrega, lugar de despacho, etc., no son modificables por el proveedor, en caso de prever algún incumplimiento,o de requerirse alguna modificación,el proveedor notificará a la empresa de manera oportuna cualquier ajuste necesario antes de la fecha de entrega prevista a fin de autorizar su cambio.
3.- Los bienes sujetos a la presente deden ser de la calidad reconocida y regida por aquellas normas que por antelación se hayan aceptado por las partes.
4.- Los bienes para ser cancelado deben ser verificados y aprobados por el responsable de la recepción según los requisitos de calidad acordada.
5.-Las devoluciones que surjan por modificaciones realizadas por el proveedor de la presente Orden de Compra, sin previa autorización del cliente, , ya sea por el incumplimiento de los precios acordados, por rechazos de calidad o por materiales que no cumplan a las condiciones acordadas, serán buscadas por el proveedor en las instalaciones a donde fueron despachadas, asumiendo el proveedor el costo total del transporte."),
0,'J',false); // Título	

$pdf->Ln(5);
$pdf->Cell(190,5,$rowOrdeComp['direccion_empresa'],0,0,'C'); // Título , telefono1,telefono2,telefono3,telefono3,
$pdf->Ln();
$pdf->Cell(190,5,"telfs: ".$rowOrdeComp['telefono1']." ".$rowOrdeComp['telefono2']." ".$rowOrdeComp['telefono3']." ".$rowOrdeComp['telefono4'],0,0,'C'); // Título
$pdf->Ln();
$pdf->Cell(190,5,utf8_decode("Original: Proveedor / Duplicado: Gerencia de Administración"),0,0,'C'); // Título

$pdf->Output();	
?>