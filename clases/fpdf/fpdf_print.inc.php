<?php
class PDF_Javascript extends FPDF {

    var $javascript;
    var $n_js;
	
	function __construct($orientation='P',$uni='mm',$format='Letter') {
		parent::__construct($orientation,$uni,$format);
	}
	function IncludeJS($script) {
        $this->javascript=$script;
    }
    function _putjavascript() {
        $this->_newobj();
        $this->n_js=$this->n;
        $this->_out('<<');
        $this->_out('/Names [(EmbeddedJS) '.($this->n+1).' 0 R ]');
        $this->_out('>>');
        $this->_out('endobj');
        $this->_newobj();
        $this->_out('<<');
        $this->_out('/S /JavaScript');
        $this->_out('/JS '.$this->_textstring($this->javascript));
        $this->_out('>>');
        $this->_out('endobj');
    }
    function _putresources() {
        parent::_putresources();
        if (!empty($this->javascript)) {
            $this->_putjavascript();
        }
    }
    function _putcatalog() {
        parent::_putcatalog();
        if (isset($this->javascript)) {
            $this->_out('/Names <</JavaScript '.($this->n_js).' 0 R>>');
        }
    }
}

class PDF_AutoPrint extends PDF_Javascript
{
	function __construct($orientation='P',$uni='mm',$format='Letter') {
		parent::__construct($orientation,$uni,$format);
	}
	function AutoPrint($dialog=false)
	{    
		$param=($dialog ? 'true' : 'false');
		$script="print(".$param.");";
		$this->IncludeJS($script);
	}
	
	function Header() {
		if ($this->mostrarHeader == 1) {
			if (file_exists($this->logo_familia) && strlen($this->logo_familia) > 4) {
				$this->Image($this->logo_familia,15,17,70);
			}
			
			$this->SetY(15);
			
			$this->SetTextColor(0,0,0);
			$this->SetFont('Arial','',5);
			$this->SetX(88);
			$this->Cell(200,9,$this->nombre_empresa,0,2,'L');
			
			if (strlen($this->rif) > 1) {
				$this->SetX(88);
				$this->Cell(200,9,$this->rif,0,2,'L');
			}
			
			if (strlen($this->direccion) > 1) {
				$this->SetX(88);
				$this->Cell(100,9,$this->direccion,0,2,'L');
			}
			
			(strlen($this->telefono1) > 1) ? $arrayTelefono[] = $this->telefono1 : "";
			(strlen($this->telefono2) > 1) ? $arrayTelefono[] = $this->telefono2 : "";
			if (isset($arrayTelefono)) {
				$this->SetX(88);
				$this->Cell(100,9,"Telf.: ".implode(" / ", $arrayTelefono),0,2,'L');
			}
			
			if (strlen($this->web) > 1) {
				$this->SetX(88);
				$this->Cell(200,9,utf8_encode($this->web),0,0,'L');
				$this->Ln();
			}
			$this->Ln(20);
		}
	}
	
	//Page footer
	function Footer() {
		if ($this->mostrarFooter == 1) {
			if (strlen($this->nombreRegistrado) > 0) {
				$this->SetY(-22);
				$this->SetTextColor(0,0,0);
				$this->SetFont('Arial','I',6);
				$this->Cell(0,8,"Registrado por: ".$this->nombreRegistrado,0,0,'L');
			}
			
			$this->SetY(-22);
			$this->SetTextColor(0,0,0);
			$this->SetFont('Arial','I',6);
			$this->Cell(0,8,"Impreso".((strlen($this->nombreImpreso) > 0) ? " por: ".$this->nombreImpreso." el " : ": ").date(spanDateFormat." h:i a"),0,0,'R');
			
			$this->SetY(-22);
			$this->SetTextColor(0,0,0);
			$this->SetFont('Arial','I',8);
			$this->Cell(0,10,utf8_decode("Página ").$this->PageNo()."/{nb}",0,0,'C');
		}
		
		if ($this->mostrarMarcaAgua == 1) {
			$this->SetTextColor(205,201,201);
			$this->SetFont('Arial','',80);
			$this->RotatedText($this->posX, $this->posY, $this->texto, $this->angulo);
		}
	}
	
	var $widths;
	var $aligns;
	var $border;
	
	function SetWidths($w) {
		//Set the array of column widths
		$this->widths = $w;
	}
	
	function SetAligns($a) {
		//Set the array of column alignments
		$this->aligns = $a;
	}
	
	function SetBorder($a) {
		//Set the array of column alignments
		$this->border = $a;
	}
	
	function Row($data, $fill) {
		//Calculate the height of the row
		$nb = 0;
		for($i = 0; $i < count($data); $i++) {
			$nb = max($nb,$this->NbLines($this->widths[$i],$data[$i]));
		}
		$h = ($this->FontSize + 5) * $nb;
		//Issue a page break first if needed
		$this->CheckPageBreak($h);
		//Draw the cells of the row
		for($i = 0; $i < count($data); $i++) {
			($fill) ? $this->SetFillColor(234,244,255) : $this->SetFillColor(255,255,255);
			
			$w = $this->widths[$i];
			$a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
			//Save the current position
			$x = $this->GetX();
			$y = $this->GetY();
			//Draw the backgrounf
			(!isset($this->border[$i])) ? $this->Rect($x,$y,$w,$h,(($fill) ? "F" : "")) : "";
			//Print the text
			$this->MultiCell($w,($this->FontSize + 5),$data[$i],0,$a);
			//Draw the border
			(!isset($this->border[$i])) ? $this->Rect($x,$y,$w,$h) : "";
			//Put the position to the right of the cell
			$this->SetXY($x + $w,$y);
		}
		//Go to the next line
		$this->Ln($h);
	}
	
	function CheckPageBreak($h) {
		//If the height h would cause an overflow, add a new page immediately
		if($this->GetY()+$h>$this->PageBreakTrigger)
			$this->AddPage($this->CurOrientation);
	}
	
	function NbLines($w,$txt) {
		//Computes the number of lines a MultiCell of width w will take
		$cw = &$this->CurrentFont['cw'];
		if ($w == 0) {
			$w = $this->w-$this->rMargin-$this->x;
		}
		$wmax = ($w-2 * $this->cMargin) * 1000 / $this->FontSize;
		$s = str_replace("\r",'',$txt);
		$nb = strlen($s);
		if ($nb > 0 and $s[$nb-1] == "\n") {
			$nb--;
		}
		$sep = -1;
		$i = 0;
		$j = 0;
		$l = 0;
		$nl = 1;
		while ($i < $nb) {
			$c = $s[$i];
			if ($c == "\n") {
				$i++;
				$sep = -1;
				$j = $i;
				$l = 0;
				$nl++;
				continue;
			}
			if ($c == ' ') {
				$sep = $i;
			}
			$l += $cw[$c];
			if ($l > $wmax) {
				if ($sep == -1) {
					if ($i == $j)
						$i++;
				} else
					$i = $sep+1;
				$sep = -1;
				$j = $i;
				$l = 0;
				$nl++;
			} else {
				$i++;
			}
		}
		return $nl;
	}
}
?>