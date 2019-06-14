<?php

/* 
 * PDF modificado en base FPDF
 * 
 * Grégor González
 * gregorh_@hotmail.com @gregorgonzalez
 * 
 * 2014 (c) Gotosystem
 * 
 */

//error_reporting(E_ALL);
//ini_set("display_errors", 1);

require_once ("../../connections/conex.php");
require('../clases/fpdf/fpdf.php');
require('../clases/barcode128.inc.php');

class PDF extends FPDF{
    
    var $espacioCentrado = 95;
    var $fuenteComun = 11;
    var $bordeTodo = 0;
    var $tipoLetra = "Arial";
    
    public function __construct($orientation='P', $unit='mm', $format='A4'){
        parent::__construct($orientation, $unit, $format);
        $this->AliasNbPages();
        $this->AddPage();
        $this->SetFont($this->tipoLetra,'B',11);
    }
    
    public function __destruct(){
       $this->Output();
    }
    
    public function centrado($texto, $x='', $y='5', $borde = ''){        
        $this->Cell($this->_calcularCentrado($texto));
        $this->Cell($x,$y,$texto,$borde);
    }
    
    public function comun($texto, $x='', $y='5', $size=''){
        $this->SetFont($this->tipoLetra,'',$this->_tamanoLetra($size));
        $this->Cell($x,$y,$texto,$this->bordeTodo);
    }
    
    public function comunNegrita($texto, $x='', $y='5', $size=''){
        $this->SetFont($this->tipoLetra,'B',$this->_tamanoLetra($size));
        $this->Cell($x,$y,$texto,$this->bordeTodo);
    }
    
    public function letraNegrita($size=''){
        $this->SetFont($this->tipoLetra,'B',$this->_tamanoLetra($size));
    }
    
    public function letraNormal($size='',$estilo = ''){
        $this->SetFont($this->tipoLetra,$estilo,$this->_tamanoLetra($size));
    }
    
    private function _calcularCentrado($texto){
        $cantidadLetras = strlen($texto);
        $multiplo = 0.9;
        
        if(ctype_upper($texto) || $cantidadLetras > 35){
            $multiplo = 1.2;
        }
        
        if($cantidadLetras > 1){
            return $this->espacioCentrado-($cantidadLetras*$multiplo);
        }else{            
            return 0;
        }
    }
    
    public function header(){
        //$this->SetFont('Arial','B',12);
        //$this->Cell(0,10,"asdf",0,0,'C');
    }
    
    public function footer(){
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,utf8_decode('Pág '.$this->PageNo().'/{nb}'),0,0,'C');
    }
    
    public function tabla($datos, $ancho, $alineacion, $borde = 1){
        foreach($datos as $clave => $valor){
                $this->Cell($ancho[$clave],5,$valor,$borde,0,$alineacion[$clave]);	
        }
    }
    
    public function textoCentrado($texto,$borde,$x='',$y=5, $aliniacion = 'C'){
        $this->Cell($x,$y,$texto,$borde,0,$aliniacion);
    }
    
    public function espacio($cantidad){
        $this->Cell($cantidad);
    }
    
    private function _tamanoLetra($numero){
        if($numero != '' && $numero != NULL){
            return $numero;
        }else{
            return $this->fuenteComun;
        }
    }
    
}

$idTot = filter_input(INPUT_GET,"id",FILTER_VALIDATE_INT);

$rowTot = informacionTot($idTot);
if($rowTot == false){
    die("no existe el tot");
}

$rutaCodigo = '../clases/temp_codigo/img_codigo_tot';
getBarcode($rowTot['numero_tot'],$rutaCodigo);

$rowEmpresa = informacionEmpresa($rowTot['id_empresa']);
$rutaLogo = "../../".$rowEmpresa['logo_familia']; // Logo

$rowProveedor = informacionProveedor($rowTot['id_proveedor']);

$pdf = new PDF();

$pdf->SetAutoPageBreak(false);
$pdf->image($rutaLogo,10,8,28);

$pdf->espacio(26);
$pdf->comunNegrita($rowEmpresa['nombre_empresa'],1,5,9);
$pdf->ln();
$pdf->espacio(26);
$pdf->comunNegrita($rowEmpresa['rif_nit'],1,5,9);
$pdf->espacio(45);
$pdf->comunNegrita("SOLICITUD DE COMPRA T.O.T",1);
$pdf->ln();
$pdf->espacio(26);
$pdf->comunNegrita($rowEmpresa['web'],1,5,9);

$pdf->image($rutaCodigo.".png",170,10,17);
$pdf->ln(20);

$pdf->comunNegrita("Nro Compra T.O.T:",35);
$pdf->comun("58");
$pdf->ln();
$pdf->comunNegrita("Fecha:",15);
$pdf->comun(date("d-m-Y"));
$pdf->ln();
$pdf->comunNegrita(utf8_decode("Código Solicitud:"),33);
$pdf->comun("15");
$pdf->ln();
$pdf->ln();

$pdf->letraNegrita();
$pdf->textoCentrado("Datos del Proveedor",1);
$pdf->ln();


$pdf->comunNegrita("Proveedor:",22);
$pdf->comun($rowProveedor['nombre']);
$pdf->ln();
$pdf->comunNegrita($spanRIF.":",12);
$pdf->comun($rowProveedor['lrif_rif_nit']);
$pdf->ln();
$pdf->comunNegrita("Persona de Contacto:",42);
$pdf->comun($rowProveedor['contacto']);
$pdf->ln();
$pdf->comunNegrita(utf8_decode("Teléfono:"),18);
$pdf->comun($rowProveedor['telefono']);
$pdf->ln();
$pdf->comunNegrita("Correo:",15);
$pdf->comun($rowProveedor['correococtacto']);
$pdf->ln();
$pdf->comunNegrita(utf8_decode("Dirección:"),20);

if(strlen($rowProveedor['direccion']) > 70){
    dividirDireccion($rowProveedor['direccion'],$pdf);
}else{
    $pdf->comun($rowProveedor['direccion']);
    $pdf->ln();
}

$pdf->ln();
$pdf->letraNegrita();
$pdf->textoCentrado(utf8_decode("Descripción del Material o Servicio"),1);

$rowDetalle = detalleTot($idTot);
$ancho = array(7,20);
$alineacion = array("C","C","C");

$pdf->ln();
$pdf->ln();
$pdf->tabla(array("Nro",utf8_decode("Código"),utf8_decode("Descripción")),$ancho,$alineacion);
$pdf->ln();
$pdf->letraNormal();

$i = 0;
foreach($rowDetalle as $row){
    $pdf->tabla(array($i = $i+1,$row['codigo'], $row['descripcion_trabajo']),$ancho,array("C","C"));
    $pdf->ln();
}

//$pdf->tabla(array("Total: ".$i));
$pdf->ln();



//$pdf->setY(-26);//limite linea final (relativo) por cada linea 5mm segun el tipo de letra y = 5 serian 5 por linea
$pdf->setY(250);
firmaEmpleado();
$pdf->ln();
$pdf->textoCentrado($rowEmpresa["direccion"]);
$pdf->ln();
$pdf->textoCentrado("Telf: ".$rowEmpresa["telefonos"]);
$pdf->ln();



///////FUNCIONES

function informacionTot($idTot){
    $query = sprintf("SELECT id_orden_tot, numero_tot, id_empresa, id_proveedor, fecha_orden_tot
                        FROM sa_orden_tot
                        WHERE id_orden_tot = %s LIMIT 1",
                     valTpDato($idTot,"int"));
    $rs = mysql_query($query);
    if(!$rs){ die("<br>Error: ".mysql_error()."<br>Linea: ".__LINE__."<br>Query: ". $query); }

    return mysql_fetch_assoc($rs);
}

function detalleTot($idTot){
    $query = sprintf("SELECT id_orden_tot_detalle,  descripcion_trabajo, id_precio_tot,
                        IFNULL(CONCAT('ACC',id_precio_tot),id_orden_tot_detalle) as codigo
                        FROM sa_orden_tot_detalle 
                        WHERE id_orden_tot = %s",
                     valTpDato($idTot,"int"));
    $rs = mysql_query($query);
    if(!$rs){ die("<br>Error: ".mysql_error()."<br>Linea: ".__LINE__."<br>Query: ". $query); }

    $variosRegistros = array();
    
    while ($row = mysql_fetch_assoc($rs)){
        $variosRegistros[] = $row;
    }
    
    return $variosRegistros;
}

function informacionEmpresa($idEmpresa){
    $query = sprintf("SELECT  IF(nombre_empresa_suc != '-', CONCAT_WS(' - ',nombre_empresa,nombre_empresa_suc), nombre_empresa) as nombre_empresa,
                              IF(nit = 0 OR nit = NULL OR nit = '',rif,CONCAT_WS(' D.V ',rif,nit)) as rif_nit,
                              rif,
                              web,
                              logo_familia,
                              direccion,
                              CONCAT_WS(' - ',telefono1,telefono2,telefono3,telefono4) as telefonos
                      FROM vw_iv_empresas_sucursales
                      WHERE id_empresa_reg = %s LIMIT 1",
                     valTpDato($idEmpresa,"int"));
    $rs = mysql_query($query);
    if(!$rs){ die("<br>Error: ".mysql_error()."<br>Linea: ".__LINE__."<br>Query: ". $query); }
    
    return mysql_fetch_assoc($rs);
}

function informacionProveedor($idProveedor){
    $query = sprintf("SELECT nombre,  contacto, direccion, telefono, correococtacto, fax,
                        CONCAT_WS('-',lrif, rif) as lrif_rif,
                        IF(nit = 0 OR nit = NULL OR nit = '',rif,CONCAT_WS(' D.V ',rif,nit)) as rif_nit,
                        IF(nit = 0 OR nit = NULL OR nit = '',rif,CONCAT_WS(' D.V ',CONCAT_WS('-',lrif,rif),nit)) as lrif_rif_nit
                        FROM cp_proveedor WHERE id_proveedor = %s LIMIT 1",
                     valTpDato($idProveedor,"int"));
    $rs = mysql_query($query);
    if(!$rs){ die("<br>Error: ".mysql_error()."<br>Linea: ".__LINE__."<br>Query: ". $query); }

    return mysql_fetch_assoc($rs);
}

function dividirDireccion($texto, $pdf){
    $array[] = substr($texto,0,70);
    $array[] = substr($texto,70,70);
    $array[] = substr($texto,140,70);
    $array[] = substr($texto,210,70);
    $array[] = substr($texto,280,70);
    
    foreach($array as $texto){
        if($texto != NULL){
            $pdf->comun($texto);
            $pdf->ln();        
        }
    }
}

function firmaEmpleado(){
    global $pdf;
    session_start();
    $idUsuario = $_SESSION['idUsuarioSysGts'];
    
    if($idUsuario != NULL && $idUsuario != ''){
        $query = sprintf("SELECT nombre_empleado, nombre_cargo FROM vw_iv_usuarios WHERE id_usuario = %s LIMIT 1",
                     valTpDato($idUsuario,"int"));
        $rs = mysql_query($query);
        if(!$rs){ die("<br>Error: ".mysql_error()."<br>Linea: ".__LINE__."<br>Query: ". $query); }

        $row = mysql_fetch_assoc($rs);
        
        $pdf->comun($row['nombre_empleado'],55,5,9);
        $pdf->comun($row['nombre_cargo'],90,5,9);
        $pdf->comun("________________",70,5,9);
        $pdf->ln();
        $pdf->espacio(155);
        $pdf->comun("Firma",1,5,9);
    }    
}