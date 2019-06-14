<?php
//validaciones por regx PHP
//ereg case sense
//eregi no case sense
//constantes
define ('_CHARMILES',',');
define ('_CHARDECIMAL','.');
$NULLValue= "NULL";



$errors = array();

function utf8parse($value){
		return utf8_encode($value);
}
function utf8to($value){
		return utf8_decode($value);
}

function _excape($var){
	return mysql_real_escape_string($var);
}

function parseNumber($num){
	$re=str_replace(_CHARMILES,"",$num);
	$re=str_replace(_CHARDECIMAL,".",$re);
	if(!is_numeric($re)){
		$re=0;
	}
	//echo "aqui: ".$re;
	return $re;
}
function parseOnlyNumber($num){
	$re=str_replace(_CHARMILES,"",$num);
	$re=str_replace(_CHARDECIMAL,".",$re);
	//echo "PARSE: ".$re;
	return $re;
}

function _numformat($num,$dec=2,$ar1="",$ar2=""){//ar1 y ar2 se ignoran
	$r=number_format($num, $dec,_CHARDECIMAL,_CHARMILES);
	if ($r=="0.00"){
		return "";
	}else{
		return $r;
	}
}

class validator {
	//retorna dicho valor de la entrada especificada
	public $errors = array();
	public $patrones=array(
	"email" => "^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$",
	"phone" => "",
	"" => ""
	);
	
	public function validate(){
		return (count($this->errors)==0);
	}
	public function getInput($name,$type,$errortext=" inv&aacute;lido.",$method="",$req=true){
		//global $errors,$patrones;
		switch ($method){
			case "post":
				if (!isset($_POST[$name])){
					$r=$NULLValue;
				}else{
					$r=$_POST[$name];
				}
				break;
			case "get":
				if (!isset($_GET[$name])){
					$r=$NULLValue;
				}else{
					$r=$_GET[$name];
				}
				break;
			case "session":
				if (!isset($_SESSION[$name])){
					$r=$NULLValue;
				}else{
					$r=$_SESSION[$name];
				}
				break;
			case "":
				$r=$name;
		}
		//echo "<br />valor: ".$r."<br />";
		if (($r==$NULLValue && $req)){
			$this->errors[]=array("m" => $r." no definido","o" => $name);
		}else{
			switch($type){
				case "t":
					break;
				case "i" :
					//trasformar previomente:
					$r=parseOnlyNumber($r);
					if (!is_integer($r)){
						$this->errors[]=array("m" => $r.' '.$errortext,"o" => $name);
						return $NULLValue;				
					}
					break;
				case "f" :
					//trasformar previomente:
					$r=parseOnlyNumber($r);
					if (!is_numeric($r)){
						$this->errors[]=array("m" => $r.' '.$errortext,"o" => $name);
						return $NULLValue;				
					}
					breaK;
				default:
					$p = $this->patrones[$type];
					if ($p!=""){
						if(!ereg($p,$r)){
					//echo "<br />tipo: ".$p."<br />";
							$this->errors[]=array("m" => $r.' '." ".$errortext,"o" => $name);
							return $NULLValue;
						}
					}
			}
		}
		return _excape($r);
	}
}

?>