<?php session_start(); 
require('FuncionesPHP.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <!--<link rel="stylesheet" type="text/css" href="./resources/css/ext-all.css" />-->
    <title>.: SIPRE 2.0 :. Contabilidad - Configuracion de Reportes</title>
    <meta http-equiv="Content-Type"content="text/html; charset=iso-8859-1">
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

<script language="JavaScript" src="./GlobalUtility.js">
</script>
<script language="JavaScript">
	function SobreFila(sDesCuen,obj){
		if (document.ConfiguracionReportes.sDesactivarColor.value != "SI"){
			// if (parent.document.frmDiarios.oNumero.value != ""){
			soCon = obj.id;
			var objFila = document.all(soCon);
			objFila.bgColor='white';
			objFila.color='Black';
			//	}					  
			obj.style.color='white';
			obj.bgColor='#000066';
			// parent.document.frmDiarios.oDesCuenta.value=sDesCuen;
		}
	}

	function FueraFila(sDesCuen,obj){
		if (document.ConfiguracionReportes.sDesactivarColor.value != "SI"){
			obj.style.color='Black';
			obj.bgColor='white';
		// parent.document.frmDiarios.oDesCuenta.value=sDesCuen;
		}		
	}

	function Regresar(){
		document.ConfiguracionReportes.target = "mainFrame";
		document.ConfiguracionReportes.action = "frmformatos.php";
		document.ConfiguracionReportes.method = "post"; 
		document.ConfiguracionReportes.StatusOculto.value = 'BU';
		document.ConfiguracionReportes.submit(); 
	}

	function Guardar(){
		if(document.ConfiguracionReportes.StatusOcultoReng.value == ""){
			document.ConfiguracionReportes.StatusOcultoReng.value = 'IN';
		}
		document.ConfiguracionReportes.target = "mainFrame";
		document.ConfiguracionReportes.action = "ConfiguracionReportes.php";
		document.ConfiguracionReportes.method = "post"; 
		document.ConfiguracionReportes.submit(); 
	}

	function EditarRenglon(orden,cod_ins,titulo,codigo,ubicacion,subrayado,numero){
		document.ConfiguracionReportes.T_orden.value = orden;		
		document.ConfiguracionReportes.T_CodInstitucion.value = cod_ins;
		document.ConfiguracionReportes.T_titulo.value = titulo; 
		document.ConfiguracionReportes.Tubicacion.value = ubicacion;
		document.ConfiguracionReportes.Tsubrayado.value = subrayado; 
		document.ConfiguracionReportes.StatusOcultoReng.value = 'UP';
		document.ConfiguracionReportes.Tnumero.value = numero;
		document.ConfiguracionReportes.T_orden.focus();
	}

	function EliminarRenglon(numero){
		if (confirm('Desea eliminar el registro')){
			document.ConfiguracionReportes.Tnumero.value = numero;
			document.ConfiguracionReportes.StatusOcultoReng.value = "DE";
			document.ConfiguracionReportes.submit();
		}
	}

<!--**********************************SELECCIONAR TEXTO**************************************-->
	function SelTexto(obj){
		if (obj.length != 0){
			obj.select();
		}
	}// function SelTexto(obj){

<!--*************************PANTALLA CONFIGURAR CUENTA**************************************-->
	function PantallaConfigurar(sCNumero,sCFormato){
		msg=open("","Cuentas","toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,copyhistory=no,width=950,height=450");
		msg.location = "ConfiguracionCuentas.php?Tnumero=" + sCNumero + "&T_formato=" + sCFormato;
	}// function PantallaConfigurar(){
</script>

<body>
<div id="divGeneralPorcentaje">
<div class="noprint"><?php include("banner_contabilidad2.php"); ?></div>
<table border="0" width="100%">
	<tr>
      	<td class="tituloPaginaContabilidad">Configuraci&oacute;n de Reportes</td>            
    </tr>
</table>

<form name="ConfiguracionReportes" action="ConfiguracionReportes.php" method="post">
<table width="800"  align="center" >
	<tr>
    	<td align="right" width="800" ><button name="BtnRegresar" type="button" value="<< Regresar" onClick="Regresar();">Regresar</button></td>
  	</tr>
</table>  

<table width="800"  align="center">
	<tr>
    	<td width="700" height="16" valign="top" class="texto_12px"><strong>Dependencia del Reporte</strong></td>
	</tr>
</table>

<table width="800"  border="0"  align="center" class="texto_9px">
	<tr class="tituloColumna">
    	<td class="cabecera" width="5"  align="center" height="16"><a>Formato</a></td>
      	<td class="cabecera" width="140"  align="center" height="16"><a>Descripci&oacute;n</a></td>	   
	</tr>	
	<tr>
    	<td class="Renglones" width="5"  align="left" height="16" valign="top"><a><?php print(strtoupper($T_formato)); ?></a></td>
		<td class="Renglones" width="140"  align="left" height="16" valign="top"><a><?php print(strtoupper($T_descripcion)); ?></a></td>	   
	</tr>		   
</table>

<table width="800"  align="center">
	<tr>
    	<td>&nbsp;</td>
	<tr>
    	<td width="800" height="16" valign="top" class="texto_12px"><strong>Configuraci&oacute;n de Reportes</strong></td>
	</tr>
</table>

<table width="800"  border="0"  align="center" class="texto_9px">
	<tr class="tituloColumna">
    	<td class="cabecera" width="70"  align="center" height="16"><a>Orden</a></td>
		<td class="cabecera" width="96"  align="center" height="16"><a>Cod. Instituci&oacute;n</a></td>	   
		<td class="cabecera" width="216" align="center" height="16"><a>T&iacute;tulo</a></td>
   		<td class="cabecera" width="142" align="center" height="16"><a>Ubicaci&oacute;n</a></td>  
		<td class="cabecera" width="140" align="center" height="16"><a>Subrayado</a></td>
   		<td class="cabecera" width="53" align="center" height="16" colspan="3"><a></a></td>
	</tr>	
  	<tr>
    	<td height=20 class=cabecera >
        	<input name="T_orden"type="text"maxlength=4  size=5  onFocus="SelTexto(this);" onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" value="" class="cNum"> </td>  
     	<td height=20 class=cabecera >
        	<input name="T_CodInstitucion"type="text"maxlength=80 size="15" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" value="" class="cTexBox"> </td>
     	<td height=20 class=cabecera >
        	<input name="T_titulo"type="text"maxlength=80 size="35" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" value="" class="cTexBox"> </td>
	 	<td height=20 class=cabecera >
        	<select  name="Tubicacion" size "3" onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'')" class="cTexBox">
	    		<option value=SOTI>SOTI</option>
	      		<option value=TITU>TITU</option>
		  		<option value=TITU2>TITU2</option>
		  		<option value=TITU3>TITU3</option>
          		<option value=DETA>DETA</option>
		  		<option value=DETA2>DETA2</option>
		  		<option value=DETA3>DETA3</option>
          		<option value=LINE>LINE</option>
          		<option value=SI>SI</option>
          		<option value=NO>NO</option>
          	</select></td>
	 	<td height=20 class=cabecera >
        	<select  name="Tsubrayado" size "3" onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'BtnAgregar')" class="cTexBox">
		  		<option value=NO>NO</option>
  		  		<option value=SI>SI</option>
		  		<option value=DB>DB</option>
          	</select></td>
      	<td height=10 class=cabecera colspan="4">
        	<button class="inputBoton" name="BtnAgregar" type="button" onClick="Guardar();" title="Agregar Renglon" >...</button></td>
<?php
	$con = ConectarBD();
	$sTabla = 'formatos';
	$sCondicion = '';
	$sCampos = 'formato';
	$sCampos.= ',descripcion';
	$sCampos.= ',titulo_cen';
	$sCondicion.= 'formato = '."'".$T_formato."'";
	$SqlStr='SELECT '.$sCampos.' FROM '.$sTabla. ' WHERE ' .$sCondicion;
	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	if (NumeroFilas($exc)>0){
		$StatusOculto = 'UP';
		$T_formato=trim(ObtenerResultado($exc,1));
		$T_descripcion=trim(ObtenerResultado($exc,2));
		$Ttitulo_cen=trim(ObtenerResultado($exc,3));
	}else{ // if ( NumeroFilas($exc)>0){
		$StatusOculto ='LI';
		$T_descripcion='';
		$Ttitulo_cen='';
	} // if ( NumeroFilas($exc)>0){

	if($StatusOcultoReng == 'IN'){
		/*C&oacute;digo PHP Para Realizar el INSERT*/
		$con = ConectarBD();
		$sCampos=" MAX(numero)";
		$sTabla="balance_a";
		$sCondicion = "formato = '$T_formato'";
		$SqlStr="Select ".$sCampos. " from " .$sTabla;
		$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		$iNroConsenumero = 0;
		if (NumeroFilas($exc)>0){
			if(is_null(ObtenerResultado($exc,1))){
				$iNroConsenumero = 1;
			}else{
				$iNroConsenumero = ObtenerResultado($exc,1) + 1;
			}
		}
	
		$sTabla="balance_a";
		$sCampos= "formato";
		$sCampos.= ",orden";   
		$sCampos.= ",cod_ins"; 
		$sCampos.= ",titulo";  
		$sCampos.= ",codigo";  
		$sCampos.= ",ubicacion"; 
		$sCampos.= ",subrayado"; 
		$sCampos.= ",numero";    
		$sValores = "'".  $T_formato ."'"; 	
		$sValores.= ",'". $T_orden ."'"; 		
		$sValores.= ",'". $T_CodInstitucion ."'";
		$sValores.= ",'". $T_titulo ."'";
		$sValores.= ",''";
		$sValores.= ",'". $Tubicacion ."'"; 		
		$sValores.= ",'". $Tsubrayado ."'"; 		
		$sValores.= ",".  $iNroConsenumero; 
		
		$SqlStr="INSERT INTO ". $sTabla ." (". $sCampos .")  values (". $sValores .")";
		$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);	
	}

	if($StatusOcultoReng == 'UP'){
		$con = ConectarBD();
		$sTabla="balance_a";
		$sValores= "formato ='". $T_formato ."'"; 	
		$sValores.= ",orden ='". $T_orden ."'"; 		
		$sValores.= ",cod_ins ='". $T_CodInstitucion ."'";
		$sValores.= ",titulo ='". $T_titulo ."'";
		$sValores.= ",codigo =''";
		$sValores.= ",ubicacion ='". $Tubicacion ."'"; 		
		$sValores.= ",subrayado ='". $Tsubrayado ."'"; 		
	
		$sCondicion = "formato ='".$T_formato."'";
		$sCondicion.= " and numero =".$Tnumero;
		$sCampos = $sValores;  
		$SqlStr='';
		$SqlStr="UPDATE ".$sTabla." SET ".$sCampos." WHERE ".$sCondicion."";
		$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	}
	
	if($StatusOcultoReng == 'DE'){
		$con = ConectarBD();
		$sTabla="balance_a";
		$sCondicion = "formato ='".$T_formato."'";
		$sCondicion.= " and numero =".$Tnumero;
		$SqlStr='';
		$SqlStr="DELETE FROM ".$sTabla ." WHERE ".$sCondicion;
		$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	}
?>
		<td width="3"></td>
</tr> 
<?php
$con = ConectarBD();
$sCampos= "formato ";
$sCampos.= ",orden";   
$sCampos.= ",cod_ins"; 
$sCampos.= ",titulo";  
$sCampos.= ",codigo";  
$sCampos.= ",ubicacion"; 
$sCampos.= ",subrayado"; 
$sCampos.= ",numero";    
$SqlStr = "Select " .$sCampos. " from balance_a where formato= '". $T_formato ."' Order by orden";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
if (NumeroFilas($exc)>0){
	$iFila = -1;
	while ($row = ObtenerFetch($exc)) {
		$iFila++;
		$formato = trim(ObtenerResultado($exc,1,$iFila));  
		$orden = trim(ObtenerResultado($exc,2,$iFila));  
		$cod_ins = trim(ObtenerResultado($exc,3,$iFila));  
		$titulo = trim(ObtenerResultado($exc,4,$iFila));  
		$codigo = trim(ObtenerResultado($exc,5,$iFila));   
		$ubicacion = trim(ObtenerResultado($exc,6,$iFila));  
		$subrayado = trim(ObtenerResultado($exc,7,$iFila));   
		$numero = trim(ObtenerResultado($exc,8,$iFila));      
?>	

	<tr  id="<?php print('FilaRenglon'.trim($numero)); ?>"  bgColor='white' onMouseOut="FueraFila('',this);" onMouseMove="SobreFila('<?=trim($DesCuenta)?>',this);" >
    	<td class="RenglonesMov" width="70"  align="left" height="16" valign="top"><a><?php print($orden);  ?></a></td> 
    	<td class="RenglonesMov" width="96"  align="left" height="16" valign="top"><a><?php print($cod_ins);  ?></a></td> 
    	<td class="RenglonesMov" width="216"  align="left" height="16" valign="top"><a><?php print($titulo);  ?></a></td> 
    	<td class="RenglonesMov" width="142"  align="left" height="16" valign="top"><a><?php print($ubicacion);  ?></a></td> 	
    	<td class="RenglonesMov" width="140"  align="left" height="16" valign="top"><a><?php print($subrayado);  ?></a></td> 	
		<td width=53 height="12" class="RenglonesMov" align="center"><input class="inputBoton" name="BtnCuentas" type="button" value="..." onClick="<?php  print("PantallaConfigurar('$numero','$T_formato')"); ?>" title="Configuraci&oacute;n de Cuentas"></td>
    	<td width=24 height="12" class="RenglonesMov"><a href="<?php print("javascript:EditarRenglon('$orden','$cod_ins','$titulo','$codigo','$ubicacion','$subrayado','$numero')")?>"><img  title="Editar Renglón" src="./Imagenes/EditarRenglon.bmp"></td>
    	<td width=16   height="12" class="RenglonesMov"><a href="<?php print("javascript:EliminarRenglon('$numero')")?>"><img  title="Eliminar Renglón" src="./Imagenes/EliminarRenglon.bmp"></td>
 	</tr>
    
<?php 	
	}   //while($row = mysql_fetch_row($exc))  
} //if (mysql_num_rows($exc) > 0)	

echo  "<script language='JavaScript'>
       document.ConfiguracionReportes.T_orden.focus();
</script>"
?>
  
</table>

<input type="hidden" name="StatusOculto" value="<?php print($sStatusOculto); ?>">
<input type="hidden" name="sNroListaOculta" value="">
<input type="hidden" name="sSeccionOculta" value="<?php print($sSeccion); ?>">
<input type="hidden" name="T_formato" value="<?php print($T_formato); ?>">
<input type="hidden" name="sDesactivarColor" value="">
<input type="hidden" name="StatusOcultoReng" value="">
<input type="hidden" name="Tnumero" value="">

</form>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
</div>

<div class="noprint">
	<?php include("pie_pagina.php"); ?>
</div>
</body>
</html>