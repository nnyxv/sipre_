<?php session_start();
include_once('FuncionesPHP.php');
$con = ConectarBD();

  $sCampos= "comprobant";
   $sCampos.= ",codigo";
  $sCampos.= ",descripcion";
  $sCampos.= ",fecha";
    
/* $SqlStr=" SELECT  $sCampos  FROM movhistorico1 b where substring(trim(b.codigo),1,1)='8'";
$SqlStr.=" union all SELECT  $sCampos  FROM movhistorico2 b where substring(trim(b.codigo),1,1)='8'";
$SqlStr.=" union all SELECT  $sCampos  FROM movhistorico3 b where substring(trim(b.codigo),1,1)='8'";
$SqlStr.=" union all SELECT  $sCampos  FROM movhistorico4 b where substring(trim(b.codigo),1,1)='8'";
$SqlStr.=" union all SELECT  $sCampos  FROM movhistorico5 b where substring(trim(b.codigo),1,1)='8'";
$SqlStr.=" union all SELECT  $sCampos  FROM movhistorico6 b where substring(trim(b.codigo),1,1)='8'";
$SqlStr.=" union all SELECT  $sCampos  FROM movhistorico7 b where substring(trim(b.codigo),1,1)='8'";
$SqlStr.=" union all SELECT  $sCampos  FROM movhistorico8 b where substring(trim(b.codigo),1,1)='8'";
$SqlStr.=" union all SELECT  $sCampos  FROM movhistorico9 b where substring(trim(b.codigo),1,1)='8'";
$SqlStr.=" union all SELECT  $sCampos  FROM movhistorico10 b where substring(trim(b.codigo),1,1)='8'";
$SqlStr.=" union all SELECT  $sCampos  FROM movhistorico11 b where substring(trim(b.codigo),1,1)='8'";
$SqlStr.=" union all SELECT  $sCampos  FROM movhistorico12 b where substring(trim(b.codigo),1,1)='8'";
            $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
			   if ( NumeroFilas($exc2)>0){
			          while ($row = mysql_fetch_row($exc2)){
					      $comprobant=$row[0];
						  $codigo= $row[1];
						  $descripcion= $row[2];
						  $fecha= $row[3];
					$SqlStr = "select count(*) from cuenta where substring(trim(codigo),1,length(trim('$codigo'))) = '$codigo'";
				    $exec1 =  EjecutarExec($con,$SqlStr) or die($SqlStr); 
					$ContAux = 0;
					 if ( NumeroFilas($exec1)>0){
  					     $ContAux  = trim(ObtenerResultado($exec1,1));
					 }
                         if($ContAux > 1){
						    echo "$comprobant  codigo:$codigo fecha:$fecha<br>";   
						 }
					}        
                }
			 */
 echo "Proceso Finalizado Satisfactoriamente Historico <br>";
 
  $sCampos= "comprobant";
   $sCampos.= ",codigo";
  $sCampos.= ",descripcion";
  $sCampos.= ",fecha";
    
$SqlStr=" SELECT  $sCampos  FROM movimien b where substring(trim(b.codigo),1,1)='4' or substring(trim(b.codigo),1,1)='5' or substring(trim(b.codigo),1,1)='6' or substring(trim(b.codigo),1,1)='7' or substring(trim(b.codigo),1,1)='8' or substring(trim(b.codigo),1,1)='9' ";
$SqlStr.=" union all SELECT  $sCampos  FROM movimiendif b where substring(trim(b.codigo),1,1)='4' or substring(trim(b.codigo),1,1)='5' or substring(trim(b.codigo),1,1)='6' or substring(trim(b.codigo),1,1)='7' or substring(trim(b.codigo),1,1)='8' or substring(trim(b.codigo),1,1)='9'";
            $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
			   if ( NumeroFilas($exc2)>0){
			          $iFila = 1;
			          while ($row = mysql_fetch_row($exc2)){
					       $comprobant=$row[0];
						  $codigo= $row[1];
						  $descripcion= $row[2];
						  $fecha= $row[3];
					$SqlStr = "select count(*) from cuenta where substring(trim(codigo),1,length(trim('$codigo'))) = '$codigo'";
				    $exec1 =  EjecutarExec($con,$SqlStr) or die($SqlStr); 
					$ContAux = 0;
					 if ( NumeroFilas($exec1)>0){
  					     $ContAux  = trim(ObtenerResultado($exec1,1));
					 }
                         if($ContAux > 1){
						    echo "$comprobant  codigo:$codigo fecha:$fecha<br>";   
						 }
					}        
                }
 echo "Proceso Finalizado Satisfactoriamente Diario y Posteriores <br>"; 
	
  
  
  
?>