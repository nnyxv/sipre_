<?php session_start();?>
<html>
<head>

<link rel="stylesheet" type="text/css" href="./resources/css/ext-all.css" />

<title>FrmUtilitarios</title>
<meta http-equiv="Content-Type"content="text/html; charset=iso-8859-1">
</head>
<style type="text/css">
<!--
@import url("estilosite.css");
-->
</style>
<style type="Text/css">
</style>
<body>
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--***********************************FUNCIONES JAVASCRIPT**********************************-->
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<script language="JavaScript"src="./GlobalUtility.js">
</script>
<script language= "javascript" >
function Limpiar(){
         if (!confirm('Desea borrar toda la informacion')){
		      return;
		 }
		//document.frmPantallaCierreMes.target='topFrame';
		document.frmPantallaCierreMes.method='post';
        document.frmPantallaCierreMes.action='Limpiar.php';
		document.frmPantallaCierreMes.submit();
}
function Insertar(){
		//document.frmPantallaCierreMes.target='topFrame';
		document.frmPantallaCierreMes.method='post';
        document.frmPantallaCierreMes.action='Insertar.php';
		document.frmPantallaCierreMes.submit();
}
function Migracion(){
		//document.frmPantallaCierreMes.target='topFrame';
		document.frmPantallaCierreMes.method='post';
        document.frmPantallaCierreMes.action='Migracion.php';
		document.frmPantallaCierreMes.submit();
}
function Remplazar(){
		//document.frmPantallaCierreMes.target='topFrame';
		document.frmPantallaCierreMes.method='post';
        document.frmPantallaCierreMes.action='p3.php';
		document.frmPantallaCierreMes.submit();
}
function MigracionDiciembre(){
		//document.frmPantallaCierreMes.target='topFrame';
		document.frmPantallaCierreMes.method='post';
        document.frmPantallaCierreMes.action='MigracionDiciembre.php';
		document.frmPantallaCierreMes.submit();
}
function ActualizarDiciembre(){
		//document.frmPantallaCierreMes.target='topFrame';
		document.frmPantallaCierreMes.method='post';
        document.frmPantallaCierreMes.action='ActualizarDiciembre.php?iActRev=0';
		document.frmPantallaCierreMes.submit();
}
function RevezarDiciembre(){
		//document.frmPantallaCierreMes.target='topFrame';
		document.frmPantallaCierreMes.method='post';
        document.frmPantallaCierreMes.action='ActualizarDiciembre.php?iActRev=1';
		document.frmPantallaCierreMes.submit();
}

function Corregir(){
		//document.frmPantallaCierreMes.target='topFrame';
		document.frmPantallaCierreMes.method='post';
        document.frmPantallaCierreMes.action='CorregirDoc.php';
		document.frmPantallaCierreMes.submit();
}
function RecorrerMovimiento(){
		//document.frmPantallaCierreMes.target='topFrame';
		document.frmPantallaCierreMes.method='post';
        document.frmPantallaCierreMes.action='RecorrerMovimiento.php';
		document.frmPantallaCierreMes.submit();
}
function VerificarMovimientos(){
		//document.frmPantallaCierreMes.target='topFrame';
		document.frmPantallaCierreMes.method='post';
        document.frmPantallaCierreMes.action='VerificarMovimientos.php';
		document.frmPantallaCierreMes.submit();
}
function Reconversion(){
		//document.frmPantallaCierreMes.target='topFrame';
		document.frmPantallaCierreMes.method='post';
        document.frmPantallaCierreMes.action='Reconversion.php';
		document.frmPantallaCierreMes.submit();
}
function Descuadres(){
		//document.frmPantallaCierreMes.target='topFrame';
		document.frmPantallaCierreMes.method='post';
        document.frmPantallaCierreMes.action='Descuadres.php';
		document.frmPantallaCierreMes.submit();
}
function verificarcontabilidad(){
		//document.frmPantallaCierreMes.target='topFrame';
		document.frmPantallaCierreMes.method='post';
        document.frmPantallaCierreMes.action='verificarcontabilidad.php';
		document.frmPantallaCierreMes.submit();
}
function ArreglarContabilidad(){
		//document.frmPantallaCierreMes.target='topFrame';
		document.frmPantallaCierreMes.method='post';
        document.frmPantallaCierreMes.action='ArreglarContabilidad.php';
		document.frmPantallaCierreMes.submit();
}
function IngresarMovimientos(){
		//document.frmPantallaCierreMes.target='topFrame';
		document.frmPantallaCierreMes.method='post';
        document.frmPantallaCierreMes.action='ingresarmovimientos.php';
		document.frmPantallaCierreMes.submit();
}

function regresarmes(){
		//document.frmPantallaCierreMes.target='topFrame';
		document.frmPantallaCierreMes.method='post';
        document.frmPantallaCierreMes.action='regresarmes.php';
		document.frmPantallaCierreMes.submit();
}

</script>
</p>
<p>&nbsp;</p>
<p>
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--************************************FORMULARIO HTML**************************************-->
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->

<?php
/*
<form name="frmPantallaCierreMes"action=""method="post">

  <div style="width:600px;">
        <div class="x-box-tl"><div class="x-box-tr"><div class="x-box-tc"></div></div></div>
        <div class="x-box-ml"><div class="x-box-mr"><div class="x-box-mc">
            <h3 style="margin-bottom:10px;">Utilitarios</h3>
            <div class="x-form-bd" id="container">
                <fieldset>

<table width=500 border=0 align=center height=0 cellpadding=0 cellspacing=0 class=cabecera>
	   <tr>
  			 <td  height=20 valign=top> 
			   <!--  <input type="button" name="BtnLimpiar" onclick="Limpiar()" value="Limpiar Ojo borra todo" >
				 <input type="text" name="TFecha" value= "2007-12-01">Fecha Proceso-->
			</td> 
 			 
     </tr>
	   <tr>
  			 <td  height=20 valign=top> 
			    <!--  <input type="button" name="BtnInsertar" onclick="Insertar()" value="Insertar">-->
			</td> 
     </tr>
	 <tr>
  			 <td  height=20 valign=top> 
			  <!--    <input type="button" name="BtnMigracion" onclick="Migracion()" value="Migracion">-->
			</td> 
     </tr>
	 <tr>
	 <td  height=20 valign=top> 
			     <!-- <input type="button" name="BtnRemplazar" onclick="Remplazar()" value="Remplazar">-->
			</td> 
	</tr>		
	 <tr>
	 <td  height=20 valign=top> 
			   <!--   <input type="button" name="BtnMigracionDiciembre" onclick="MigracionDiciembre()" value="Migración Diciembre"> -->
			</td> 
	  <td  height=20 valign=top> 
			    <!--  <input type="button" name="BtnActulizarDiciembre" onclick="ActualizarDiciembre()" value="Actulizar Diciembre"> -->
			</td> 
     <td  height=20 valign=top> 
			     <!--  i<nput type="button" name="BtnReverzarDiciembre" onclick="ReverzarDiciembre()" value="Reverzar Diciembre inofensivo"> -->
			</td> 		
         <td  height=20 valign=top> 
			      <input type="button" name="BtnCorregir" onclick="Corregir()" value="Corregir Documentos"> 
			</td> 		
		
	</tr>		
	<tr>
		<td  height=20 valign=top> 
			     <input type="button" name="BtnRecorrerMovimientos" onclick="RecorrerMovimiento()" value="Recorrer Movimientos">
			</td> 		
			
	</tr>
	
		<tr>
		<td  height=20 valign=top> 
			     <input type="button" name="BtnVerificarMovimientos" onclick="VerificarMovimientos()" value="Verificar Movimientos">
			</td> 		
			
	</tr>
	<tr>
		<td  height=20 valign=top> 
			     <input type="button" name="BtnReconversion" onclick="Reconversion()" value="Reconversion Monetaria">
			</td> 		
			
	</tr>
	<tr>
		<td  height=20 valign=top> 
			     <input type="button" name="BtnBuscarDescuadres" onclick="Descuadres()" value="Descuadres">
			</td> 		
			
	</tr>
	<tr>
		<td  height=20 valign=top> 
			     <input type="button" name="BtnBuscarverificarcontabilidad" onclick="verificarcontabilidad()" value="Verificar Contabilidad">
			</td> 		
			
	</tr>
		<tr>
		<td  height=20 valign=top> 
			     <input type="button" name="BtnBuscarArreglar" onclick="ArreglarContabilidad()" value="Arreglar Contabilidad">
			</td> 		
			
	</tr>
		<tr>
		<td  height=20 valign=top> 
			     <input type="button" name="BtnIngresarMovimientos" onclick="IngresarMovimientos()" value="Ingresar Movimientos">
			</td> 		
			
	</tr>
	<tr>
		<td  height=20 valign=top> 
			     <input type="button" name="Btnregresarmes" onclick="regresarmes()" value="Regresar Mes">
			</td> 		
			
	</tr>
 </table>
                 </fieldset>
            </div>
         </div></div></div>
        <div class="x-box-bl"><div class="x-box-br"><div class="x-box-bc"></div></div></div>
    </div>

  <p>&nbsp;</p>
 <table width="500" align="center" border="0"cellpadding=0 cellspacing=0 >
    <tr>
  		<td  height=20 colspan="4" align="center" valign=top ><input name="BtnAceptar" type="button" maxlength=23 size=10 onClick="Aceptar();" value="Aceptar"></font></td> 
   </tr>
 <tr>
</table> 
<input type='hidden' name='oEstado' value="<?=$Estado?>">
</form>
</body>
</html>*/
?>