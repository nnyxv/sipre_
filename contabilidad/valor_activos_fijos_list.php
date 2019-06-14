<?php session_start();
include_once('FuncionesPHP.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">


<script>
function BuscarJ(sP,parExceloPdf){
day = new Date();
id = day.getTime();

if(document.getElementById('LisActivoFijo').checked == true ){
	orientacion = "P";
} else {
	orientacion = "L";
}

window.open(sP + "?orientacion="+orientacion);


//eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=0,scrollbars=0,location=0,statusbar=1,menubar=0,resizable=0,"+result+"');");
//eval("page" + id + "= open('','" + id + "','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=yes,copyhistory=no');");
//sPar = sPar + "&ExceloPdf="+parExceloPdf;
//eval("page" + id + ".location ='"+sP+"?orientacion="+orientacion+"'");

//eval("page" + id + ".location ='"+sP+"?ubicacion="+Ubicacion+"&departamento="+Departamento+"&responsable="+Responsable+"&desubicacion="+DesUbicacion+"
//&desdepartamento="+DesDepartamento+"&desresponsable="+DesResponsable+"'");
//msg=open("","Busqueda","toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=yes,copyhistory=no");
//msg.location = sP+'?'+sPar;
}
</script>
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
<link rel="stylesheet" type="text/css" href="../js/domDragContabilidad.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
<title>.: SIPRE 2.0 :. Contabilidad - Reporte Activos Fijos</title>
</head>
<body>
<div id="divGeneralPorcentaje">
<div class="noprint"><?php include("banner_contabilidad2.php"); ?></div> 
<table border="0" width="100%">
    <tr>
        <td class="tituloPaginaContabilidad">Reporte Activos Fijos            
        </td>            
    </tr>
</table>

<form action="PlantillaBuscarSiguiente.php" method="post"  name="PlantillaBuscarParametros">

<?php
  	$con = ConectarBD();
?>   

<table width="100%">		 
	<tr>
		<td>&nbsp;</td>
	</tr>
   	<tr>    
		<td><fieldset>
		  	<legend class="legend">Generar Reporte</legend>
		  	<table border="0" align="center">
        		<tr>
                    <td width="23" align="left">
                        <input type="radio" id="LisActivoFijo" name="activoFijo" value="0"/>
                    </td>
                    <td width="792" align="left">
                        Listado Activo Fijo
                    </td>
                </tr>
                <tr>
                    <td align="left">
                        <input type="radio" id="DepreActivoFijo" name="activoFijo" value="1" checked ="checked"/>
                    </td>
                    <td align="left">
                        Depreciaci&oacute;n Activo fijo
                    </td>
               </tr>
			</table>
            </fieldset>
		</td>
	</tr>    
</table>

<table width="100%">    
    <tr> 
        <td align="right"><hr/> 
    	    <button id="BtnAceptar1" type="Button"  align="middle" onKeyUp="fn(this.form,this,event,'')" name="BtnAceptar1"
                        onClick = "<?php print("BuscarJ('reporteTotalActivoPDF.php','P');")?>"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table>              
			</button>
            <button id="BtnAceptar2" type="Button"  align="middle" onKeyUp="fn(this.form,this,event,'')" name="BtnAceptar2"
                        onClick = "<?php print("BuscarJ('reporteTotalActivoExcel.php','E');")?>"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table>
               
			</button>
		</td>
	</tr>
     		  <!--Fin solo para un espacio en blanco--> 
</table>

<input type="hidden" name="TexOcultoStatus">
  		  <!--solo para un espacio en blanco-->

 			  <!--*************************************************************************************************-->  
				  <!--*************************************************************************************************-->  				 
				  <!--*********************************esto se coloco para las busuqedas de tablas*********************-->  
 				  <!--*************************************************************************************************-->  				 
  				  <!--*************************************************************************************************-->  				 
    <input type="hidden" name="TAValores"> 
    <input type="hidden" name="TACondicion"> 
			
</form>
</div>
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


<div class="noprint">
        <?php include("pie_pagina.php"); ?>
</div>


</body>
</html>
