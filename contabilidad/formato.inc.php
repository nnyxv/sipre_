<?function restar($valor1, $valor2, $decimales){
	$resta=bcsub($valor1,$valor2, $decimales);	
	return $resta;
}//restar

function sumar($valor1, $valor2, $decimales)
{
	$suma=bcadd($valor1,$valor2,$decimales);	
	return $suma;
}//sumar

function dividir($valor1, $valor2, $decimales)
{
	$dividir=bcdiv($valor1,$valor2, $decimales);	
	return $dividir;
}




function multiplicar($valor1, $valor2, $decimales)
{

	$multiplicar=bcmul($valor1,$valor2, $decimales);	
	return $multiplicar;

}


function cvMoneda($monto, $redondeo, $decimales, $tipo, $formatear)
{

	$factor1=0; $factor2=0; $factor_tmp=0; $factor=0; $valor_redondeado=0; $valor=0; 
	$monto_miles=0; $monto_decimales=0; $cadena_monto=0; $negativo = 0;

//	$monto=to_moneda_bd($monto);
	
	if ($tipo=="bs")
	{
		$valor=multiplicar($monto, 1000, 6);
	}
	else if ($tipo=="bsf")
	{
		$valor=dividir($monto, 1000, 6);
	}
	
	if(preg_match("/^\-/",$valor))
	{
		$negativo = 1;
		$valor = preg_replace("|\-|","",$valor);
	}
	
	return $valor;
	
 	if ($redondeo==true)
	{	
	
		$cadena_monto=split("[\.]",$valor);
		$monto_miles=$cadena_monto[0];
		$monto_decimales=$cadena_monto[1];
		
		$factor1=$monto_miles.".".substr($monto_decimales,0, ($decimales + 1));
		$factor2=$monto_miles.".".substr($monto_decimales, 0, $decimales);	
		$factor_tmp=restar($factor1, $factor2, $decimales+1);
		$factor=multiplicar($factor_tmp, 100, 1);			
		
		if ($factor>=0.5)
		{
			$valor_redondeado=sumar($factor2, 0.01, 2);
			if ($negativo==1) $valor_redondeado=multiplicar($valor_redondeado, -1, 2);
			if ($formatear==true) return to_moneda($valor_redondeado);
			else return $valor_redondeado;
		}
		else
		{
			
			if ($factor<=-0.5)
			{
				$valor_redondeado=sumar($factor2, 0.01, 2);
				$valor_redondeado=multiplicar($valor_redondeado, -1, 2);
				if ($formatear==true) return to_moneda($valor_redondeado);
				else return $valor_redondeado;	
			}
			else
			{
				$valor_redondeado=$factor2;
				if ($formatear==true) return to_moneda($valor_redondeado);
				else return $valor_redondeado;	
			}
							
		}		
	
	}
	else
	{
 		if ($formatear==true) return to_moneda($valor);
		else return $valor;
 	}
 
}//

function validar_vacio($valor)
{
	if (isset($valor))
	{
		if ($valor=='')
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	else
	{
		return false;
	}
}





function to_moneda($string)
{
$Negative = 0;

//check to see if number is negative
if(preg_match("/^\-/",$string))
{
//setflag
$Negative = 1;
//remove negative sign
$string = preg_replace("|\-|","",$string);
}


//look for commas in the string and remove them.
$string = preg_replace("|\,|","",$string);

//split the string into two First and Second
// First is before decimal, second is after First.Second
$Full = split("[\.]",$string);

$Count = count($Full);

if($Count > 1)
{
$First = $Full[0];
$Second = $Full[1];
$NumCents = strlen($Second);
if($NumCents == 2)
{
//do nothing already at correct length
}
else if($NumCents < 2)
{
//add an extra zero to the end
$Second = $Second . "0";
}
else if($NumCents > 2)
{
//either string off the end digits or round up
// I say string everything but the first 3 digits and then round
// since it is rare that anything after 3 digits effects the round
// you can change if you need greater accurcy, I don't so I didn't
// write that into the code.
$Temp = substr($Second,0,3);
//$Rounded = round($Temp,-1);
//$Second = substr($Rounded,0,2);
$Second = substr($Temp,0,2);

}

}
else
{
//there was no decimal on the end so add to zeros
$First = $Full[0];
$Second = "00";
}

$length = strlen($First);

if( $length <= 3 )
{
//To Short to add a comma
$string = $First . "," . $Second;

// if negative flag is set, add negative to number
if($Negative == 1)
{
$string = "-" . $string;
}

return $string;
}
else
{
$loop_count = intval( ( $length / 3 ) );
$section_length = -3;
for( $i = 0; $i < $loop_count; $i++ )
{
$sections[$i] = substr( $First, $section_length, 3 );
$section_length = $section_length - 3;
}

$stub = ( $length % 3 );
if( $stub != 0 )
{
$sections[$i] = substr( $First, 0, $stub );
}
$Done = implode( ".", array_reverse( $sections ) );
$Done = $Done . "," . $Second;

// if negative flag is set, add negative to number
if($Negative == 1)
{
$Done = "-" . $Done;
}

return $Done;

}
}
/***********************************************************************/

function to_moneda_bd($string)
{
$Negative = 0;

//check to see if number is negative
if(preg_match("/^\-/",$string))
{
//setflag
$Negative = 1;
//remove negative sign
$string = preg_replace("|\-|","",$string);
}


//look for commas in the string and remove them.         
$string = preg_replace("|\.|","",$string);

//split the string into two First and Second
// First is before decimal, second is after First.Second
$Full = split("[\,]",$string);

$Count = count($Full);

if($Count > 1)
{
$First = $Full[0];
$Second = $Full[1];
$NumCents = strlen($Second);
if($NumCents == 2)
{
//do nothing already at correct length
}
else if($NumCents < 2)
{
//add an extra zero to the end
$Second = $Second . "0";
}
else if($NumCents > 2)
{
//either string off the end digits or round up
// I say string everything but the first 3 digits and then round
// since it is rare that anything after 3 digits effects the round
// you can change if you need greater accurcy, I don't so I didn't
// write that into the code.
$Temp = substr($Second,0,3);
//$Rounded = round($Temp,-1);
//$Second = substr($Rounded,0,2);
$Second = substr($Temp,0,2);

}

}
else
{
//there was no decimal on the end so add to zeros
$First = $Full[0];
$Second = "00";
}

$length = strlen($First);

if( $length <= 3 )
{
//To Short to add a comma
$string = $First . "." . $Second;

// if negative flag is set, add negative to number
if($Negative == 1)
{
$string = "-" . $string;
}

return $string;
}
else
{
$loop_count = intval( ( $length / 3 ) );
$section_length = -3;
for( $i = 0; $i < $loop_count; $i++ )
{
$sections[$i] = substr( $First, $section_length, 3 );
$section_length = $section_length - 3;
}

$stub = ( $length % 3 );
if( $stub != 0 )
{
$sections[$i] = substr( $First, 0, $stub );
}
$Done = implode( "", array_reverse( $sections ) );
$Done = $Done . "." . $Second;

// if negative flag is set, add negative to number
if($Negative == 1)
{
$Done = "-" . $Done;
}

return $Done;

}
}
/***********************************************************************/
function moneda($moneda)
{
$valor=''; 
$a=1;
$i=0; 
$str_valor=$moneda;
while ($a<>0)

{ 
	if (isset($str_valor[$i]))
	{
	if ($str_valor[$i]<>'$')
	{
	$valor.=$str_valor[$i]; 
	}
	$i=$i+1;
	} 
	else 
	{
	 $a=0;
	 }
  } 
 $moneda=$valor;
return ($moneda);

}


function punto($moneda)
{
$valor=''; 
$a=1;
$i=0; 
$str_valor=$moneda;
while ($a<>0)

{ 
	if (isset($str_valor[$i]))
	{
	if ($str_valor[$i]<>'.')
	{
	$valor.=$str_valor[$i]; 
	}
	$i=$i+1;
	} 
	else 
	{
	 $a=0;
	 }
  } 
 $moneda=$valor;
return ($moneda);

}


function fecha_bd($fec)// dd/mm/yyyy to yyyy/mm/dd
{

$day='';
$mes='';
$ano='';

for ($i=0;$i<10;$i++)
{
	if ($i<2)
	{
	$day.=$fec[$i];
	}

	if (($i>2) and ($i<5))
	{
	$mes.=$fec[$i];
	
	}

	if (($i>5) and ($i<10))
	{
	$ano.=$fec[$i];
	}
}
$fecha=$ano.'-'.$mes.'-'.$day;

return ($fecha);
}

function fecha($fec)// yyyy/mm/dd to dd/mm/yyyy
{

$day='';
$mes='';
$ano='';

for ($i=0;$i<10;$i++)
{
	if ($i<4)
	{
	$ano.=$fec[$i];
	}

	if (($i>4) and ($i<7))
	{
	$mes.=$fec[$i];
	
	}

	if (($i>7) and ($i<10))
	{
	$day.=$fec[$i];
	}
}
$fecha=$day.'/'.$mes.'/'.$ano;
return ($fecha);
}


function gettime()//fecha_actual
{
$ano=strftime("%Y");
$mes=strftime("%m");
$dia=strftime("%d");

$fe=$ano.'-'.$mes.'-'.$dia;
return($fe);
}



function to_moneda_Txt($moneda)
{
	
	if (isset($moneda))
	{
		$valor=moneda(to_moneda_bd(to_moneda($moneda)));
	}
	else
	{
		$valor="0.00";
	}	
	
	return $valor;
	
}//moneda


/*************/
function to_moneda_sp($string)
{
$Negative = 0;

//check to see if number is negative
if(preg_match("/^\-/",$string))
{
//setflag
$Negative = 1;
//remove negative sign
$string = preg_replace("|\-|","",$string);
}


//look for commas in the string and remove them.
$string = preg_replace("|\,|","",$string);

//split the string into two First and Second
// First is before decimal, second is after First.Second
$Full = split("[\.]",$string);

$Count = count($Full);

if($Count > 1)
{
$First = $Full[0];
$Second = $Full[1];
$NumCents = strlen($Second);
if($NumCents == 2)
{
//do nothing already at correct length
}
else if($NumCents < 2)
{
//add an extra zero to the end
$Second = $Second . "0";
}
else if($NumCents > 2)
{
//either string off the end digits or round up
// I say string everything but the first 3 digits and then round
// since it is rare that anything after 3 digits effects the round
// you can change if you need greater accurcy, I don't so I didn't
// write that into the code.
$Temp = substr($Second,0,3);
//$Rounded = round($Temp,-1);
//$Second = substr($Rounded,0,2);
$Second = substr($Temp,0,2);

}

}
else
{
//there was no decimal on the end so add to zeros
$First = $Full[0];
$Second = "00";
}

$length = strlen($First);

if( $length <= 3 )
{
//To Short to add a comma
$string = $First . "," . $Second;

// if negative flag is set, add negative to number
if($Negative == 1)
{
$string = "-" . $string;
}

return $string;
}
else
{
$loop_count = intval( ( $length / 3 ) );
$section_length = -3;
for( $i = 0; $i < $loop_count; $i++ )
{
$sections[$i] = substr( $First, $section_length, 3 );
$section_length = $section_length - 3;
}

$stub = ( $length % 3 );
if( $stub != 0 )
{
$sections[$i] = substr( $First, 0, $stub );
}
$Done = implode( "", array_reverse( $sections ) );
$Done = $Done . "," . $Second;

// if negative flag is set, add negative to number
if($Negative == 1)
{
$Done = "-" . $Done;
}

return $Done;

}
}

function getMes($nMes)
{
	switch($nMes)       
	{
		case "01":
			return "Enero";
			break;
			
		case "02":
			return "Febrero";
			break;	
			
		case "03":
			return "Marzo";
			break;
			
		case "04":
			return "Abril";
			break;
			
		case "05":
			return "Mayo";
			break;
			
		case "06":
			return "Junio";
			break;
			
		case "07":
			return "Julio";
			break;
			
		case "08":
			return "Agosto";
			break;
			
		case "09":
			return "Septiembre";
			break;
			
		case "10":
			return "Octubre";
			break;
			
		case "11":
			return "Noviembre";
			break;
			
		case "12":
			return "Diciembre";
			break;
			
		default: 
			echo("Mes No Válido"); 
			return "Mes no Válido"; 
			break;
			
	}//switch
	
}//getMes
/***********************************************************************/

function mensaje($mensaje)
{
echo"<script languaje=\"javascript\">
alert('".$mensaje."')
</script>";
}

function mensaje_pregunta($mensaje)
{

echo"
<script languaje=\"javascript\">
var resp

resp=confirm('".$mensaje."');

</script>";		
					
}



function javascript($javascript)
{
echo"<script languaje=\"javascript\">
".$javascript."
</script>";
}

function open_pag($javascript,$target)
{
echo"<script languaje=\"javascript\">
window.open('".$javascript."','".$target."');
</script>";
}


?>