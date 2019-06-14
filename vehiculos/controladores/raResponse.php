<?php
class raResponse{
	var $response;
	function asignar($id, $atributo, $valor){
		$this->response[] = array("accion" => "asignar", "id" => $id, "atributo" => $atributo,"valor" => $valor);
	}
	
	function alert($texto){
		$this->response[] = array("accion" =>"alerta", "texto" => $texto);
	}
	
	function script($codigo){
		$this->response[] = array("accion" =>"script", "codigo" => $codigo);
	}
	
	function selected($id, $valor){
		$this->response[] = array("accion" => "selected", "id" => $id,"valor" => $valor);
	}
	
	function checked($id, $valor){
		$this->response[] = array("accion" => "checked", "id" => $id,"valor" => $valor);
	}
	
	/*function llamar($funcion){
	$argumentos = func_get_args();;
	for(i=1; )
		$this->response[] = array("accion" => "llamar", "funcion" => $funcion, "parametro" => $parametros);
	}*/
	
	function enviar(){
		echo json_encode($this->response); 
	}
}

function receptor_raRequest(){
	$funcion = $_POST['funcion'];
	$paran = 0;
	$arg ="";
	foreach( $_POST as $post){
		if($paran++){
			if($paran > 2)
				$arg .=',';
			if(substr_count($post, '{') && substr_count($post, '{')){	
				$post = str_replace('\"','"',$post);	
				$json = json_decode($post , true);
				$arg .= "\$json";
			}else{
				$arg .= "\$_POST[".$paran."]";
			}
		}
	}
	eval($funcion."(".$arg.");");
}
?>
