<?php session_start(); 
include_once('FuncionesPHP.php');
$T_Codigo= $_REQUEST['T_Codigo'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<title>.: SIPRE 2.0 :. Contabilidad - Acceso a Usuarios</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
<link rel="stylesheet" type="text/css" href="../js/domDragContabilidad.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
</head>

<script language="JavaScript" src="GlobalUtility.js">
</script>
<script language="JavaScript">
function GuardarAcceso(){
document.frmVerAccesos.action = "GuardarAcceso.php";
document.frmVerAccesos.submit();
}
function Asignar(){
	for (i = 0; i < document.frmVerAccesos.elements.length; i++){
		if (document.frmVerAccesos.elements[i].type == "select-one"){
  			document.frmVerAccesos.elements[i].value = "SI";
		}
	}
}
function Quitar(){
	for (i = 0; i < document.frmVerAccesos.elements.length; i++){
		if (document.frmVerAccesos.elements[i].type == "select-one"){
  			document.frmVerAccesos.elements[i].value = "NO";
		}
	}
}
function Regresar(){
	document.frmVerAccesos.StatusOculto.value = "BU"; 
    document.frmVerAccesos.action = "frmencmapaacceso.php";
    document.frmVerAccesos.submit();
}
</script>
<body>
<div id="divGeneralPorcentaje">
<div class="noprint"><?php include("banner_contabilidad2.php"); ?></div> 

<form name="frmVerAccesos"  method="post" action="frmVerAccesos.php">

<table border="0" width="100%">
	<tr>
    	<td class="tituloPaginaContabilidad">Acceso a Usuarios</td>            
    </tr>
</table>

<table width="100%"  align="center">
	<tr>
    	<td>&nbsp;</td>
    </tr>
  	<tr>
    	<td align="right" width="25%" >
        	<button class="inputBoton" name="BtnGuardar" type="button" value="Guardar" onClick="GuardarAcceso();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
        </td>
        <td align="right" width="25%" >
        	<button class="inputBoton" name="BtnRegresar" type="button" value="<< Regresar" onClick="Regresar();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/return.png"/></td><td>&nbsp;</td><td>Regresar</td></tr></table></button>
        </td>
        <td align="right" width="25%">
        	<button class="inputBoton" name="BtnAsignar" type="button" value="Asignar Todos" onClick="Asignar();"><table width="100" height="18" align="center" cellpadding="0" cellspacing="0"><tr><td width="18"><img src="../img/iconos/select.png"/></td><td width="47" align="justify">Asig. Todos</td></tr></table></button>
        </td>
        <td align="right" width="25%" >
        	<button class="inputBoton" name="BtnQuitar" type="button" value="Quitar Todos" onClick="Quitar();"><table  width="100" height="18"align="center" cellpadding="0" cellspacing="0"><tr><td width="18"><img src="../img/iconos/minus.png"/></td><td width="60">Quitar Todos</td></tr></table></button>
         </td>
  </tr>
</table>  
<div class="x-box-tl"><div class="x-box-tr"><div class="x-box-tc"></div></div></div>
<div class="x-box-mr"><div class="x-box-mc">
 
<div class="x-form-bd" id="container">
                    <div class="x-form-item">

<table width=800  border="0"  align="center">
	<tr><td>&nbsp;</td></tr>
  	<tr class="tituloColumna">
    	<td width=200  align="center" height="16">M&oacute;dulos</td>
       	<td width=50  align="center" height="16">Acceso</td>	   
	   	<td width=50  align="center" height="16">Incluir</td>	   
	   	<td width=50  align="center" height="16">Modificar</td>	   	   
       	<td width=50  align="center" height="16">Eliminar</td>	   	   
       	<td width=50  align="center" height="16">Consultar</td>	   
	</tr>
<?php
	$con =  ConectarBDAd();  
	//los numeros del menu no pueden pasar mas de 99 por que se va a tomar solo los dos primeros numeros
	$sCampos= "a.tipo,a.titulo,a.NOP,a.Numero,b.NroOpcion,b.Habilitar,b.CodigoMapa";
	$sql = "Select " .$sCampos. " from menus a left join mapaacceso b on a.Numero = b.NroOpcion and CodigoMapa = '". $T_Codigo ."' order by Orden ";
	
	$exc =  EjecutarExecAd($con,$sql) or die($sql);  
	if (NumeroFilas($exc) > 0){
		$iFila=-1;	
		$cant =-1;
		while($row = ObtenerFetch($exc)){
			$cant++;
			//$clase = (fmod($cant, 2) == 0) ?  "trResaltar4":"colorfondo" ;
			$iFila++;
			
			$Tipo = trim(ObtenerResultado($exc,1,$iFila)); 
			$Descripcion = trim(ObtenerResultado($exc,2,$iFila));
			$NOP = trim(ObtenerResultado($exc,3,$iFila)); 
			$NumeroCorre = trim(ObtenerResultado($exc,4,$iFila)); 
			$NroOpcion = ObtenerResultado($exc,5,$iFila); 
			$sHabilitar = ObtenerResultado($exc,6,$iFila); 
			 
			$bNulo = false; 
			$bIncluir = 0; 
			$bModificar = 0; 
			$bEliminar = 0; 
			$bConsultar = 0; 
			if (is_null($NroOpcion)) {
				$bNulo = true; 
			}else{
				$sCampos= "Habilitar";
			
				$sql = "Select " .$sCampos. " from mapaacceso where NroOpcion= ". $NumeroCorre  ." and  CodigoMapa ='$T_Codigo'";
				$excMapa = EjecutarExecAd($con,$sql) or die($sql);  
					
				if (NumeroFilas($excMapa) > 0){
					$sHabilitar1 = ObtenerResultado($excMapa,1); 			
					
					if (substr($sHabilitar1,0,1) == "1"){
						$bIncluir = 1; 	
					}
					if (substr($sHabilitar1,1,1) == "1"){
						$bModificar = 1; 
					}
					if (substr($sHabilitar1,2,1) == "1"){
						$bEliminar = 1; 	
					}
					if (substr($sHabilitar1,3,1) == "1"){
						$bConsultar = 1; 	
					}
				}	
				
			 } 
					 
			 if ($NumeroCorre <= 9){
				$NumeroT = "E0". trim($NumeroCorre) ."_" ;
			 }else{
				$NumeroT = "E".trim($NumeroCorre) ."_";
			 }  
			if ($Tipo == 'P'){  
				$Descripcion = '.'. $Descripcion;               
			}elseif ($Tipo == 'T' || $Tipo == 'C'){  
			  	$Descripcion = '..'. $Descripcion;                
		}	
?>  
<!--	<tr class="<?=$clase?>" onMouseOut="this.style.color='Black'"  onMouseMove="this.style.color='#FF0000'" >-->
	<tr onMouseOut="this.style.color='Black'"  onMouseMove="this.style.color='#FF0000'" >
<?php   
		if ($Tipo == 'P'){  
?>
    	<td bgcolor='#D7EDFF' colspan="6" width=250 height="16" align="left"><font style="font-weight:bold"><?php print($Descripcion);?> </font></td> 
<?php   
		}else{ 
?>
     	<td width=250 align="left" height="16"><?php print($Descripcion);?></td> 
<?php  
		} 

		if ($Tipo == 'T' ||$Tipo == 'C'){  
?>     	
    	<td width=50 align="center" height="16">
	 		<select name=<?php print(trim($NumeroT)."A");   ?> id=<?php  print($Codigo);?>  onChange="Grabar(this.id,this.value);">
<?php 
			if ($bNulo){  
?>
				<option seleted value=NO>NO</option>
 	    		<option  value=SI>SI</option>
<?php 
			}else{ 
?>	 
	  			<option seleted value=SI>SI</option>
	    		<option  value=NO>NO</option>
<?php 
			} 
?>	 
	 		</select>
           
		</td> 

            
      <!-- <td width=50 align="center" height="16">-->
<?php   
			if ($NOP == 'N'){
?>
     	<td width=50 align="center" height="16">
	 		<select  name="<?php print(trim($NumeroT)."I");?>" id=<?php  print($Codigo);?>  >
<?php 
				if ($bIncluir == 1){ 
?>
	  			<option  value="SI" selected>SI</option>
	  			<option  value=NO>NO</option>
<?php 
				}else{ 
?>	 
	  			<option  value=NO selected >NO</option>
	     		<option  value=SI>SI</option>

<?php 
				}
				
?>	 
	 		</select>
		</td> 



     	<td width=50 align="center" height="16">
	 		<select name=<?php print(trim($NumeroT)."M");?> id=<?php  print($Codigo);?> >
 <?php 
 				if ($bModificar){  
?>
	     		<option seleted value=SI>SI</option>
 	     		<option  value=NO>NO</option>
            </select>
		</td>
<?php 
				}else{ 
?>	 
	  			<option seleted value=NO>NO</option>
	     		<option  value=SI>SI</option>
            </select>
		</td>
<?php 
				} 
?>	 
<!--	 		</select>
		</td> -->
 
     	<td width=50 align="center" height="16">
	 		<select name=<?php print(trim($NumeroT)."E");?> id=<?php  print($Codigo);?>  >
<?php 
				if ($bEliminar){  
?>
	     		<option seleted value=SI>SI</option>
 	     		<option  value=NO>NO</option>
<?php 
				}else{ 
?>	 
	 	 		<option seleted value=NO>NO</option>
	     		<option  value=SI>SI</option>
<?php 
				} 
?>	 
	 		</select>
		</td> 
 
     	<td width=50 align="center" height="16">
     		<select name=<?php print(trim($NumeroT)."C");?> id=<?php  print($Codigo);?>>
            
<?php 
				if ($bConsultar){  
?>
	    		<option seleted value=SI>SI</option>
 	    		<option  value=NO>NO</option>
<?php 
				}else{ 
?>	 
				<option seleted value=NO>NO</option>
	    		<option  value=SI>SI</option>
<?php 
				} 
?>	 
	 		</select>
		</td>         
	
<?php 
			}
		}
?>
	
<!--	</tr>-->
 
 <?php 	
	} 
}
  
?>

	</tr>
</table>
 </div>
               
         </div></div></div>
      
        <div class="x-box-blue"><div class="x-box-bl"><div class="x-box-br"><div class="x-box-bc"></div></div></div></div>
		
<input type="hidden" name="StatusOculto" value="<?php print($sStatusOculto); ?>">
<input type="hidden" name="sNroListaOculta" value="">
<input type="hidden" name="IDAsignar" value="">
<input type="hidden" name="SIoNO" value="">
<input type="hidden" name="sSeccionOculta" value="<?php print($sSeccion); ?>">
<input type="hidden" name="T_Codigo" value="<?php print($T_Codigo); ?>">
</form>
</body>

<div class="noprint">
	<?php include("pie_pagina.php"); ?>
</div>

</html>
