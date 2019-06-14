<?php 
function montoescrito($num) { 
# Recibe numero real positivo de la forma: 
# 12345.5678 
# y lo devuelve en palabras: 
# doce mil trescientos cuarenta y cinco coma cinco seis siete ocho. 
# Los decimales los transforma a la palabra correspondiente de cada digito y no usa unidades de mil, centenas ni decenas. 


//$num_aux, $pal, $unid, $dec, $cent, $resul, $i, $decimas 
//$dig, $entero, $centavos, $num_bloques, $primer_dig, $segundo_dig, $tercer_dig, $bloque, $bloque_cero 

$unid = array('cero', 'uno', 'dos','tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez', 'once', 'doce', 'trece', 'catorce' , 'quince' , 'dieciseis' , 'diecisiete' , 'dieciocho' , 'diecinueve', 'veinte', 'ventiun', 'veintidos', 'veintitres', 'veinticuatro', 'veinticinco', 'veintiseis', 'veintisiete', 'veintiocho', 'veintinueve'); 
$dec = array('', 'diez', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'); 
$cent = array('', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'); 

$num_aux = $num; 

$iPosDeci = strpos($num,".");
if ($iPosDeci > 0){  
   $centavos = substr($num,$iPosDeci+1);
}else{
  $centavos = "";
}	
$entero = intval($num_aux); 

if ($entero == 0){
   $resul = 'cero' ; 
}
$num_bloques = 1; 

while ($entero > 0) { 
$primer_dig = 0; 
$segundo_dig = 0; 
$tercer_dig = 0; 
$bloque = ''; 
$bloque_cero = 0; 
$i = 0; 
for ($w = 1;$w <= 3; $w++) { 
			$i++; 
				$dig = $entero - (intval($entero / 10) * 10); 
		
					if ($dig != 0) { 
							if ($i == 1) { 
								if ($dig == 1 ){
									if (strlen(strval(intval($num))) == 1){
										$bloque = ' ' . $unid[$dig]; 
										$primer_dig = $dig; 
									}else{
										$bloque = ' ' ; 
										$primer_dig = $dig; 
									}	
								}else{
										$bloque = ' ' . $unid[$dig]; 
										$primer_dig = $dig; 
								}	
							}elseif ($i == 2) { //if ($i == 1) { 
								if ($dig <= 2) { 
									$bloque = ' ' . $unid[$dig * 10 + $primer_dig]; 
								} else { //if ($dig <= 2) {
									$bloque = ' ' . $dec[$dig] . ($primer_dig != 0 ? ' y' : '') . $bloque; 
								}; // else { 
								$segundo_dig = $dig; 
							}elseif ($i == 3) { 
								$bloque = ' ' . (($dig == 1 and $primer_dig == 0 and $segundo_dig == 0) ? 'cien' : $cent[$dig]) . $bloque; 
				$tercer_dig = $dig; 
							};//elseif ($i == 3) {  
					} else { //if ($dig != 0) { 
						$bloque_cero++; 
					}; 

			$entero = intval($entero / 10); 

if ($entero == 0){
    break; 
}
}; 

if ($num_bloques == 1) { 
   $resul = $bloque; 
}elseif ($num_bloques == 2) { 
	$resul = $bloque. ($bloque_cero != 3 ? ' mil' : '') . $resul; 
}elseif ($num_bloques == 3) { 
	$resul = $bloque. (($primer_dig == 1 and $segundo_dig == 0 and $tercer_dig == 0) ? ' millón' : ' millones'). $resul; 
}elseif ($num_bloques == 4) { 
	$resul = $bloque. ($bloque_cero != 3 ? ' mil' : '') . $resul; 
}elseif ($num_bloques == 5) { 
	$resul = $bloque. (($primer_dig == 1 and $segundo_dig == 0 and $tercer_dig == 0) ? ' billón' : ' billones'). $resul; 
}; 
	$num_bloques++; 
}; 


if ($centavos != '') { 
	$resul.= ' con '. $centavos .'/100'  ; 
}	

for ($i = 0; $decimas >= $i; $i++) { 
	$resul.= $unid[$decimas[$i]] . ' '; 
	};
//}; 
return $resul; 
}; 


