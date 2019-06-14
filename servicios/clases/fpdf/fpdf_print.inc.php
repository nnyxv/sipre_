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
            //COMENTADO PARA QUE NO HAGA AUTOPRINT
//		$param=($dialog ? 'true' : 'false');
//		$script="print(".$param.");";
//		$this->IncludeJS($script);
	}
}
?>