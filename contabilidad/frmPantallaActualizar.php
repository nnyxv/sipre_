<?php session_start();
	include_once('FuncionesPHP.php');
	$_SESSION["pag"] = 2;
	$conectadosNormal= verificarConectados("N");
	if(count($conectadosNormal) > 0){
		echo "<script language='javascript'>
				alert('Existen usuarios conectados debe esperar a que se desconecten');
				location.href='ListadoConectados.php';
			  </script>";
			  
	}else{
		registrar("E");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<!--DATOS DE LA FORMA-->

<!--Título: frmformatos -->

<!--Descripción: Formulario individual-->

<!--Copyright: Copyright (c) Corporación Oriomka, C.A. 2006-->

<!--Empresa: Corporación Oriomka, C.A. www.oriomka.net Telf:(0212)7618494-7627666-->

<!--Autor: Corporación Oriomka, C.A.-->

<!--Autor: Desarrollado por Ernesto Garcia 0416-4197573 / 0414-0106485-->

<!--@version 1.0-->

<title>.: SIPRE 2.0 :. Contabilidad - Actualizar Movimientos</title>
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

<body>
<div id="divGeneralPorcentaje">
<div class="noprint"><?php include("banner_contabilidad2.php"); ?></div> 
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--***********************************FUNCIONES JAVASCRIPT**********************************-->
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<script language="JavaScript"src="./GlobalUtility.js">
</script>
<script language= "javascript" >
function Buscar(){

}
function PantallaBuscar(sObjeto,oArreglo){
    URL = 'PantallaBuscarFormularioDiarios.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo;
  	//winOpen('PantallaBuscarFormulariosDiarios.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,'');
	msg=open("","Busqueda","toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,copyhistory=no,width=830,height=440");
    msg.location = URL;
  }// function AbrirBus(sObjeto,oArreglo){
function Elegir(){
		document.frmPantallaActualizar.target='mainFrame';
		document.frmPantallaActualizar.method='post';
		document.frmPantallaActualizar.action='frmPantallaActualizar.php';
		document.frmPantallaActualizar.submit();
}
function SelTexto(obj){
if (obj.length != 0){
obj.select();
}
}
function Aceptar(){

      if (document.frmPantallaActualizar.Option[0].checked ||document.frmPantallaActualizar.Option[1].checked){
			document.frmPantallaActualizar.cDesde.value  = document.frmPantallaActualizar.xAFecha.value + '-' + document.frmPantallaActualizar.xMFecha.value +'-'+document.frmPantallaActualizar.xDFecha.value
			document.frmPantallaActualizar.cHasta.value  = document.frmPantallaActualizar.xAFecha2.value + '-' + document.frmPantallaActualizar.xMFecha2.value +'-'+document.frmPantallaActualizar.xDFecha2.value
		}

	  /*  if (document.frmPantallaActualizar.Option[1].checked ||document.frmPantallaActualizar.Option[3].checked){
			document.frmPantallaActualizar.cDesde.value  = document.frmPantallaActualizar.xAFecha.value + '-' + document.frmPantallaActualizar.xMFecha.value +'-'+document.frmPantallaActualizar.xDFecha.value
		}*/
		
		//|| document.frmPantallaActualizar.Option[1].checked
       if (document.frmPantallaActualizar.Option[0].checked){
		document.frmPantallaActualizar.iActRev.value = 0;
	   }else{
  		document.frmPantallaActualizar.iActRev.value = 1;
	   }  
		document.frmPantallaActualizar.target='topFrame';
		document.frmPantallaActualizar.method='post';
		document.frmPantallaActualizar.action='Actualizar.php';
		document.frmPantallaActualizar.submit();
}
</script>

<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--************************************FORMULARIO HTML**************************************-->
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaContabilidad">
            	Actualizar Movimientos        
            </td>            
        </tr>
</table>

<form name="frmPantallaActualizar"action="frmPantallaActualizar.php"method="post">

<table width="100%">

	<tr>
		<td>&nbsp;</td>
	</tr>
   	<tr>    
		<td><fieldset>
		  	<legend class="legend">Actualizar o Reversar</legend>
        	<table border="0" align="center">
        		<tr>
   				  <td width="247" align="left">
<?php
	$sCh = '';	    
	if (!isset($Option)){
		$Option = 1;
	}
	
	if ($Option == 1){
		$sCh = 'checked';	    
	}
?>
                        <table>
                            <tr>
                                <td align="left" width="20">
                                    <input name="Option"  <?php print($sCh); ?> type="radio" onClick="Elegir();" value="1"> 
                                </td>
                                <td align="left" width="214">
                                    Actualizar Movimientos por Fecha
                                </td>
                            </tr>
<?php
	$sCh = '';	    
	if ($Option == 2){
		$sCh = 'checked';	    
	}
?>

<?php
	$sCh = '';	    
	if ($Option == 3){
		$sCh = 'checked';	    
	}
?>
                            <tr>
                                <td align="left" width="20">        
                                    <input name="Option"  <?php print($sCh); ?> type="radio" onClick="Elegir();" value="3"> 
                                </td>
                                <td align="left">
                                    Reversar Movimientos por Fecha
                               </td>
                            </tr>
    	   
<?php
	$sCh = '';	    
	if ($Option == 4){
		$sCh = 'checked';	    
	}
?>
	  <!-- <input name="Option"  <?php print($sCh); ?> type="radio" onClick="Elegir();" value="4"> Reverzar Movimientos por Comprobantes
	   <br></br> -->
<?php
	$sCh = '';	    
	if ($Option == 5){
		$sCh = 'checked';	    
	}
?>
                            <tr>
                                <td align="left" width="20">
                                    <input name="Option" <?php print($sCh); ?> type="radio" onClick="Elegir();" value="5"> 
                                </td>
                                <td align="left">
                                    Reversar Movimientos del Mes
                                </td>
                            </tr>
                        </table>
                  </td>
                  <td width="10">&nbsp;        	
                  </td>

<?php
	$xDFecha='01';
	$xMFecha=obFecha($_SESSION["sFec_Proceso"],'M');
	$xAFecha=obFecha($_SESSION["sFec_Proceso"],'A');
	//$sFechaSumar = date("d-m-Y",mktime(0,0,0,$xMFecha+1,0,$xAFecha)); 
	$xDFecha2=date("d",mktime(0,0,0,$xMFecha+1,0,$xAFecha)); 
	$xMFecha2=date("m",mktime(0,0,0,$xMFecha+1,0,$xAFecha)); 
	$xAFecha2=date("Y",mktime(0,0,0,$xMFecha+1,0,$xAFecha)); 
	
	if ($Option == "1" || $Option == "3"){
?>
                    <td width="581" align="left">
                        <table width="100%">
                            <tr>
                                <td class="tituloCampo" width="153" align="right">
                                    Fecha:
                                </td>                            
                                <td width="83" align="right">
                                    Desde:
                                </td>
                                <td width="138" align="left">
                                    <input  name="xDFecha"type="text"maxlength=2 size=1 onFocus="SelTexto(this);"onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" value="<?=$xDFecha?>" class="cNum">
                                    <input  readonly  name="xMFecha"type="text"maxlength=2 size=1 onFocus="SelTexto(this);"onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" value="<?=$xMFecha?>" class="cNum">
                                    <input  readonly  name="xAFecha"type="text"maxlength=4 size=4 onFocus="SelTexto(this);"onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" value="<?=$xAFecha?>" class="cNum">
                                </td>  
                                <td width="47" align="right">
                                    Hasta:
                                </td>
                                <td width="136" align="left"> 	
                                    <input  name="xDFecha2"type="text"maxlength=2 size=1 onFocus="SelTexto(this);"onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" value="<?=$xDFecha2?>" class="cNum">
                                    <input  readonly name="xMFecha2"type="text"maxlength=2 size=1 onFocus="SelTexto(this);"onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" value="<?=$xMFecha2?>" class="cNum">
                                    <input  readonly name="xAFecha2"type="text"maxlength=4 size=4 onFocus="SelTexto(this);"onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" value="<?=$xAFecha2?>" class="cNum">
                                </td>                                                               
                            </tr>
                        </table>
<?php  
	}
?>  
					</td>
				</tr>
			</table>
			</fieldset>
		</td>
	</tr>
</table>

<?php
	if ($Option == "2" || $Option == "4"){
		$Arretabla2[0][0]= "enc_diario"; //Tabla
		$Arretabla2[0][1]= 'T';
		$Arretabla2[1][0]= "Comprobant"; //Campo1
		$Arretabla2[1][1]= 'C';
		$Arretabla2[2][0]= "Concepto"; //Campo2
		$Arretabla2[2][1]= 'C';
		$Arretabla2[3][0]= "cComprobantD"; //Objeto del Campo2
		$Arretabla2[3][1]= 'O';
		$Arretabla2[4][0]= "oConcepto"; //Objeto del Campo2
		$Arretabla2[4][1]= 'O';
		$Arretabla2[5][0]= 'frmPantallaActualizar';// Pantalla donde estamos ubicados
		$Arretabla2[5][1]= 'P';
		$Arre2 = array_envia($Arretabla2); // Serializar Array
?> 
  <p>&nbsp;</p>
  
<table width="100%">
	<tr>    
		<td><fieldset>
		  	<legend class="legend">Comprobantes</legend>
        	<table border="0" width="100%">
                <tr>
                    <td class="tituloCampo" width="140" align="right">
                        Comprobante:
                    </td>
                    <td align="left">
                        <input class="cTexBoxdisabled" readonly name="cComprobantD" type="text"
                 maxlength="23" size="10" onKeyPress="CheckNumericJEnter(this.form,this,event,'')">
                        <img style="cursor:pointer" height="20" src="./Imagenes/BuscarXP.ico" onClick="<?php print("PantallaBuscar('oComprobante','$Arre2')");?>"></img>
                 <!--<input  disabled name="TFecha1"type="text"maxlength=23 size=10 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" class="cNum">-->
                    </td>   
                </tr>  
                <tr>
                    <td class="tituloCampo" width="140" align="right">
                        Fecha:
                    </td>
                    <td align="left">
                        <input readonly  name="xDFecha"type="text"maxlength=2 size=1 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xDFecha?>" class="cNumDisabled">
                        <input readonly name="xMFecha"type="text"maxlength=2 size=1 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xMFecha?>" class="cNumDisabled">
                        <input readonly name="xAFecha"type="text"maxlength=4 size=4 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xAFecha?>" class="cNumDisabled">
                    </td>   
                </tr>  
            </table>
            </fieldset>
 
<?php  
	}   
?>  
		</td>
	</tr>
</table>



<table width="100%">
    <tr>
  		<td align="right"><hr/>
        	<button name="BtnAceptar" type="submit" onClick="Aceptar();" value="Aceptar">Aceptar</button>
        </td> 
   </tr>
 <tr>
</table>
</div>
<?php
  if ($Option != "5" && isset($Option)){
?>
         </div></div></div>
        <div class="x-box-bl"><div class="x-box-br"><div class="x-box-bc"></div></div></div>
   </div>
<?php
}
?>
<input type="hidden" name="iActRev">
<input type="hidden" name="cDesde">
<input type="hidden" name="cHasta">


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



<div class="noprint">
        <?php include("pie_pagina.php"); ?>
</div>

</body>
</html>
<?php } ?>