<?php


function num2letras($num, $fem = false, $dec = true, $moneda = "", $usarCentimos = 1) { 
//if (strlen($num) > 14) die("El n?mero introducido es demasiado grande"); 
	$matuni[2]  = "DOS"; 
	$matuni[3]  = "TRES"; 
	$matuni[4]  = "CUATRO"; 
	$matuni[5]  = "CINCO"; 
	$matuni[6]  = "SEIS"; 
	$matuni[7]  = "SIETE"; 
	$matuni[8]  = "OCHO"; 
	$matuni[9]  = "NUEVE"; 
	$matuni[10] = "DIEZ"; 
	$matuni[11] = "ONCE"; 
	$matuni[12] = "DOCE"; 
	$matuni[13] = "TRECE"; 
	$matuni[14] = "CATORCE"; 
	$matuni[15] = "QUINCE"; 
	$matuni[16] = "DIECISEIS"; 
	$matuni[17] = "DIECISIETE"; 
	$matuni[18] = "DIECIOCHO"; 
	$matuni[19] = "DIECINUEVE"; 
	$matuni[20] = "VEINTE"; 
	$matunisub[2] = "DOS"; 
	$matunisub[3] = "TRES"; 
	$matunisub[4] = "CUATRO"; 
	$matunisub[5] = "QUIN"; 
	$matunisub[6] = "SEIS"; 
	$matunisub[7] = "SETE"; 
	$matunisub[8] = "OCHO"; 
	$matunisub[9] = "NOVE"; 
	$matdec[2] = "VEINT"; 
	$matdec[3] = "TREINTA"; 
	$matdec[4] = "CUARENTA"; 
	$matdec[5] = "CINCUENTA"; 
	$matdec[6] = "SESENTA"; 
	$matdec[7] = "SETENTA"; 
	$matdec[8] = "OCHENTA"; 
	$matdec[9] = "NOVENTA"; 
	$matsub[3]  = 'MILL'; 
	$matsub[5]  = 'BILL'; 
	$matsub[7]  = 'MILL'; 
	$matsub[9]  = 'TRILL'; 
	$matsub[11] = 'MILL'; 
	$matsub[13] = 'BILL'; 
	$matsub[15] = 'MILL'; 
	$matmil[4]  = 'MILLONES'; 
	$matmil[6]  = 'BILLONES'; 
	$matmil[7]  = 'DE BILLONES'; 
	$matmil[8]  = 'MILLONES DE BILLONES'; 
	$matmil[10] = 'TRILLONES'; 
	$matmil[11] = 'DE TRILLONES'; 
	$matmil[12] = 'MILLONES DE TRILLONES'; 
	$matmil[13] = 'DE TRILLONES'; 
	$matmil[14] = 'BILLONES DE TRILLONES'; 
	$matmil[15] = 'DE BILLONES DE TRILLONES'; 
	$matmil[16] = 'MILLONES DE BILLONES DE TRILLONES'; 

	$num = trim((string)@$num); 
	$neg = ''; 
	while ($num[0] == '0') $num = substr($num, 1); 
	if ($num[0] < '1' or $num[0] > 9) $num = '0' . $num; 
	$zeros = true; 
	$punt = false; 
	$ent = ''; 
	$fra = ''; 
	for ($c = 0; $c < strlen($num); $c++) { 
		$n = $num[$c]; 
		if (! (strpos(".,'''", $n) === false)) { 
			if ($punt) break; 
			else{ 
				$punt = true; 
				continue; 
			} 
		}elseif (! (strpos('0123456789', $n) === false)) { 
			if ($punt) { 
				if ($n != '0') $zeros = false; 
				$fra .= $n; 
			}else 
				$ent .= $n; 
		}else 
			break; 
	} 
	$ent = '     ' . $ent; 
	
	if ($fra > 0)
		$fin = ' '.$moneda.' CON '. num2letrasdec($fra,false,false,$usarCentimos);   
	else	   
		$fin = ' '.$moneda.' ';   
	if ((int)$ent === 0) return 'CERO ' . $fin; 
	$tex = ''; 
	$sub = 0; 
	$mils = 0; 
	$neutro = false; 
   while ( ($num = substr($ent, -3)) != '   ') { 
      $ent = substr($ent, 0, -3); 
      if (++$sub < 3 and $fem) { 
         $matuni[1] = 'UNA'; 
         $subcent = 'AS'; 
      }else{ 
         $matuni[1] = $neutro ? 'UN' : 'UNO'; 
         $subcent = 'OS'; 
      } 
      $t = ''; 
      $n2 = substr($num, 1); 
      if ($n2 == '00') { 
      }elseif ($n2 < 21) 
         $t = ' ' . $matuni[(int)$n2]; 
      elseif ($n2 < 30) { 
         $n3 = $num[2]; 
         if ($n3 != 0) $t = 'I' . $matuni[$n3]; 
         $n2 = $num[1]; 
         $t = ' ' . $matdec[$n2] . $t; 
      }else{ 
         $n3 = $num[2]; 
         if ($n3 != 0) $t = ' Y ' . $matuni[$n3]; 
         $n2 = $num[1]; 
         $t = ' ' . $matdec[$n2] . $t; 
      } 
      $n = $num[0]; 

		if ($n == 1) {
			if ($t ==''){
				$t=' CIEN';
			} else {
				$t = ' CIENTO' . $t;
			}
		} else {  
		  if ($n == 5){ 
			 $t = ' ' . $matunisub[$n] . 'IENT' . $subcent . $t; 
		  }elseif ($n != 0){ 
			 $t = ' ' . $matunisub[$n] . 'CIENT' . $subcent . $t; 
	      } 
	  }	  
      if ($sub == 1) { 
      }elseif (! isset($matsub[$sub])) { 
         if ($num == 1) { 
            $t = ' UN MIL'; 
         }elseif ($num > 1){ 
            $t .= ' MIL'; 
         } 
      }elseif ($num == 1) { 
         $t .= ' ' . $matsub[$sub] . 'ON'; 
      }elseif ($num > 1){ 
         $t .= ' ' . $matsub[$sub] . 'ONES'; 
      }   
      if ($num == '000') $mils ++; 
      elseif ($mils != 0) { 
         if (isset($matmil[$sub])) $t .= ' ' . $matmil[$sub]; 
         $mils = 0; 
      } 
      $neutro = true; 
      $tex = $t . $tex; 
   } 
   $tex = $neg . substr($tex, 1) . $fin; 
   return ucfirst(eliminarUnMil($tex)); 
} 


function num2letrasdec($num, $fem = false, $dec = false, $usarCentimos = 1) { 
//if (strlen($num) > 14) die("El n?mero introducido es demasiado grande"); 
   $matuni[2]  = "DOS"; 
   $matuni[3]  = "TRES"; 
   $matuni[4]  = "CUATRO"; 
   $matuni[5]  = "CINCO"; 
   $matuni[6]  = "SEIS"; 
   $matuni[7]  = "SIETE"; 
   $matuni[8]  = "OCHO"; 
   $matuni[9]  = "NUEVE"; 
   $matuni[10] = "DIEZ"; 
   $matuni[11] = "ONCE"; 
   $matuni[12] = "DOCE"; 
   $matuni[13] = "TRECE"; 
   $matuni[14] = "CATORCE"; 
   $matuni[15] = "QUINCE"; 
   $matuni[16] = "DIECISEIS"; 
   $matuni[17] = "DIECISIETE"; 
   $matuni[18] = "DIECIOCHO"; 
   $matuni[19] = "DIECINUEVE"; 
   $matuni[20] = "VEINTE"; 
   $matunisub[2] = "DOS"; 
   $matunisub[3] = "TRES"; 
   $matunisub[4] = "CUATRO"; 
   $matunisub[5] = "QUIN"; 
   $matunisub[6] = "SEIS"; 
   $matunisub[7] = "SETE"; 
   $matunisub[8] = "OCHO"; 
   $matunisub[9] = "NOVE"; 
   $matdec[2] = "VEINT"; 
   $matdec[3] = "TREINTA"; 
   $matdec[4] = "CUARENTA"; 
   $matdec[5] = "CINCUENTA"; 
   $matdec[6] = "SESENTA"; 
   $matdec[7] = "SETENTA"; 
   $matdec[8] = "OCHENTA"; 
   $matdec[9] = "NOVENTA"; 
   $matsub[3]  = 'MILL'; 
   $matsub[5]  = 'BILL'; 
   $matsub[7]  = 'MILL'; 
   $matsub[9]  = 'TRILL'; 
   $matsub[11] = 'MILL'; 
   $matsub[13] = 'BILL'; 
   $matsub[15] = 'MILL'; 
   $matmil[4]  = 'MILLONES'; 
   $matmil[6]  = 'BILLONES'; 
   $matmil[7]  = 'DE BILLONES'; 
   $matmil[8]  = 'MILLONES DE BILLONES'; 
   $matmil[10] = 'TRILLONES'; 
   $matmil[11] = 'DE TRILLONES'; 
   $matmil[12] = 'MILLONES DE TRILLONES'; 
   $matmil[13] = 'DE TRILLONES'; 
   $matmil[14] = 'BILLONES DE TRILLONES'; 
   $matmil[15] = 'DE BILLONES DE TRILLONES'; 
   $matmil[16] = 'MILLONES DE BILLONES DE TRILLONES'; 
   $num = trim((string)@$num); 
   while ($num[0] == '0') $num = substr($num, 1); 
   if ($num[0] < '1' or $num[0] > 9) $num = '0' . $num; 
   $zeros = true; 
   $punt = false; 
   $ent = $num  ;
   $ent = '     ' . $ent; 
   if ((int)$ent === 0) return ' ' . $fin; 
   $tex = ''; 
   $sub = 0; 
   $mils = 0; 
   $neutro = false; 
   while ( ($num = substr($ent, -3)) != '   ') { 
      $ent = substr($ent, 0, -3); 
      if (++$sub < 3 and $fem) { 
         $matuni[1] = 'UNA'; 
         $subcent = 'AS'; 
      }else{ 
         $matuni[1] = $neutro ? 'UN' : 'UN'; 
         $subcent = 'OS'; 
      } 
      $t = ''; 
      $n2 = substr($num, 1); 
      if ($n2 == '00') { 
      }elseif ($n2 < 21) 
         $t = ' ' . $matuni[(int)$n2]; 
      elseif ($n2 < 30) { 
         $n3 = $num[2]; 
         if ($n3 != 0) $t = 'I' . $matuni[$n3]; 
         $n2 = $num[1]; 
         $t = ' ' . $matdec[$n2] . $t; 
      }else{ 
         $n3 = $num[2]; 
         if ($n3 != 0) $t = ' Y ' . $matuni[$n3]; 
         $n2 = $num[1]; 
         $t = ' ' . $matdec[$n2] . $t; 
      } 
		$n = $num[0]; 
		if ($n == 1) {
			if ($t ==''){
				$t=' CIEN';
			} else {
				$t = ' CIENTO' . $t;
			}
		}
	  if ($sub == 1) { 
      }elseif (! isset($matsub[$sub])) { 
         if ($num > 1){ 
            $t .= ' MIL'; 
         } 
      }elseif ($num == 1) { 
         $t .= ' ' . $matsub[$sub] . 'ON'; 
      }elseif ($num > 1){ 
         $t .= ' ' . $matsub[$sub] . 'ONES'; 
      }   
      if ($num == '000') $mils ++; 
      elseif ($mils != 0) { 
         if (isset($matmil[$sub])) $t .= ' ' . $matmil[$sub]; 
         $mils = 0; 
      } 
      $neutro = true; 
      $tex = $t . $tex; 
   } 
  
   if($usarCentimos == 1){
       $formatoCentimo = " CÉNTIMO";
       $formatoCentimos = " CÉNTIMOS";
   }elseif($usarCentimos == 2){
       $formatoCentimo = " CENTAVO";
       $formatoCentimos = " CENTAVOS";
   }
   
  if ($num==1) 
	   $tex =  $fin . substr($tex, 1) . $formatoCentimo  ; 
  else 
	   $tex =  $fin . substr($tex, 1) . $formatoCentimos  ; 
  	   
   return ucfirst($tex); 
}

function eliminarUnMil($string){
    $palabras = explode(" ", $string);
    if($palabras[0] == "UN" && $palabras[1] == "MIL"){
         array_shift($palabras);
         $string = implode(" ", $palabras);
         return $string;
    }else{
        return $string;
    }
}

?>