<?php
class ModeloCliente {
	public $idEmpresa;
	public $idProspecto;
	public $idEmpleado;
	
	function guardarCliente($frmCliente) {
		
		return array(
			0 => true,
			"idCliente" => $this->idCliente);
	}
	
}
?>