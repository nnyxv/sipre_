<?php session_start();
include_once('FuncionesPHP.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<title>.: SIPRE 2.0 :. Contabilidad - Integracion Contable</title>
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
<!--***********************************FUNCIONES JAVASCRIPT**********************************-->
<!--*****************************************************************************************-->
<script language="JavaScript"src="./GlobalUtility.js">
</script>
<script language= "javascript" >
<!--*****************************************************************************************-->
<!--**********************************SELECCIONAR TEXTO**************************************-->
<!--*****************************************************************************************-->
function SelTexto(obj){
if (obj.length != 0){
obj.select();
}
}//  function SelTexto(obj){

  function PantallaBuscar(sObjeto,oArreglo){
    winOpen('PantallaBuscarFormularios.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,'');
  }// function AbrirBus(sObjeto,oArreglo){
function BuscarDescrip(sValor,sCampoBuscar,oArreglo){
	document.frmIntegracionContable.TACondicion.value=sCampoBuscar +"= '" + sValor + "'"
	document.frmIntegracionContable.TAValores.value=oArreglo;
	document.frmIntegracionContable.method='post';
	document.frmIntegracionContable.target='topFrame';
	document.frmIntegracionContable.action='BusTablaParametros.php';
	document.frmIntegracionContable.submit();
}//function BuscarDescrip(sValor,sCampoBuscar,oArreglo){

<!--*****************************************************************************************-->
<!--**********************************Eliminar Renglones Detalle*****************************-->
<!--*****************************************************************************************-->
    function EliminarRenglon(id){
	if (confirm('Desea Eliminar el registro')){
		t1 = document.frmIntegracionContable.hdntabla.value
		c1 = document.frmIntegracionContable.hdnc1.value
		c2 = document.frmIntegracionContable.hdnc2.value
  		document.frmIntegracionContable.method='post';
  		document.frmIntegracionContable.target='FrameDetalle';
  		document.frmIntegracionContable.action='RenglonesIntegracion.php?pidModulo='+document.frmIntegracionContable.idModulo.value+'&paccion=E'+'&idRenglon='+id+'&snom_tablaobjeto='+t1+'&snom_idobjeto='+c1+'&snom_desobjeto='+c2+'&sucursal='+document.frmIntegracionContable.TSucursal.value;
  		document.frmIntegracionContable.submit();
	}	
  }//function BuscarDescrip(sValor,sCampoBuscar,oArreglo){

<!--*****************************************************************************************-->
<!--**********************************Buscar Renglones Detalle*******************************-->
<!--*****************************************************************************************-->
    function BuscarIntegraciones(snom_tablaobjeto,snom_idobjeto,snom_desobjeto){
  		document.frmIntegracionContable.method='post';
  		document.frmIntegracionContable.target='FrameDetalle';
  		document.frmIntegracionContable.action='RenglonesIntegracion.php?pidModulo='+document.frmIntegracionContable.idModulo.value+'&paccion=B'+'&snom_tablaobjeto='+snom_tablaobjeto+'&snom_idobjeto='+snom_idobjeto+'&snom_desobjeto='+snom_desobjeto+'&sucursal='+document.frmIntegracionContable.TSucursal.value;
  		document.frmIntegracionContable.submit();
  }//function BuscarDescrip(sValor,sCampoBuscar,oArreglo){

<!--*****************************************************************************************-->
<!--********************************** Renglones Detalle*************************************-->
<!--*****************************************************************************************-->
    function InsertarRenglones(idModulo,tcodigoO,tcodigoD,snom_tablaobjeto,snom_idobjeto,snom_desobjeto,sucursal){
	    if(tcodigoO == ""){
		   alert("Debe Seleccionar el registro del Objeto")
		   return;
		}
		if(tcodigoD == ""){
		   alert("Debe Seleccionar la cuenta contable")
		   return;
		}
		//+"&sucursal="+document.frmIntegracionContable.sucursal.value
  		document.frmIntegracionContable.method='post';
  		document.frmIntegracionContable.target='FrameDetalle';
  		document.frmIntegracionContable.action='RenglonesIntegracion.php?pidModulo='+idModulo+'&ptcodigoO='+tcodigoO+'&ptcodigoD='+tcodigoD+'&paccion=I'+'&snom_tablaobjeto='+snom_tablaobjeto+'&snom_idobjeto='+snom_idobjeto+'&snom_desobjeto='+snom_desobjeto+'&sucursal='+document.frmIntegracionContable.TSucursal.value;
  		document.frmIntegracionContable.submit();
  }//function BuscarDescrip(sValor,sCampoBuscar,oArreglo){

<!--*****************************************************************************************-->
<!--********************************** Guardar General***************************************-->
<!--*****************************************************************************************-->
    function GuardarGeneral(){
	  	idModulo=document.frmIntegracionContable.idModulo.value;
		tcodigoG=document.frmIntegracionContable.TcodigoG.value;
  		document.frmIntegracionContable.method='post';
  		document.frmIntegracionContable.target='topFrame';
  		document.frmIntegracionContable.action='GuardarIntegracion.php?pidModulo='+idModulo+'&ptcodigoG='+tcodigoG+'&sucursal='+document.frmIntegracionContable.TSucursal.value;
  		document.frmIntegracionContable.submit();
  }//function BuscarDescrip(sValor,sCampoBuscar,oArreglo){

  
  function clickBoton(snom_tablaobjeto,snom_idobjeto,snom_desobjeto){
  idModulo=document.frmIntegracionContable.idModulo.value;
  tcodigoO=document.frmIntegracionContable.TcodigoO.value;
  tcodigoD=document.frmIntegracionContable.TcodigoD.value;
  sucursal=document.frmIntegracionContable.TSucursal.value;
  InsertarRenglones(idModulo,tcodigoO,tcodigoD,snom_tablaobjeto,snom_idobjeto,snom_desobjeto,sucursal)
  }
<!--*****************************************************************************************-->
<!--**********************************BUSCAR*************************************************-->
<!--*****************************************************************************************-->
function Buscar(){
document.frmparametros.target='mainFrame';
document.frmparametros.method='post';
document.frmparametros.action='frmIntegracionContable.php';
document.frmparametros.StatusOculto.value='BU';
document.frmparametros.submit();
}// function AbrirBus(sObjeto,oArreglo){


function Recargar(){
document.frmIntegracionContable.target='_self';
document.frmIntegracionContable.method='post';
document.frmIntegracionContable.action='frmIntegracionContable.php';
document.frmIntegracionContable.submit();
}// function AbrirBus(sObjeto,oArreglo){

<!--*****************************************************************************************-->
<!--**********************************Abrir Ventana de Busqueda******************************-->
<!--*****************************************************************************************-->
  function AbrirBus(sObjeto,oArreglo){
      winOpen('PantallaBuscar.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,'');
  }// function AbrirBus(sObjeto,oArreglo){

function objetoAjax(){
		var xmlhttp=false;
	 	try{
   			xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
  		}catch(e){
   			try {
    			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	   		}catch(E){
    			xmlhttp = false;
   			}
  		}
  		if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
   			xmlhttp = new XMLHttpRequest();
  		}
  		return xmlhttp;
	}
	
function CargarDiv(){	
MostrarDetalle();
}	
function MostrarDetalle(){
		MiDiv = document.getElementById("DivComboNo");
		ajax=objetoAjax();
 		ajax.open("GET","DetalleIntegracion.php?pidModulo="+document.frmIntegracionContable.idModulo.value+"&sucursal="+document.frmIntegracionContable.TSucursal.value);
 		ajax.onreadystatechange=function() {
  			if (ajax.readyState==4) {
   				MiDiv.innerHTML  = ajax.responseText
				t1 = document.frmIntegracionContable.hdntabla.value
				c1 = document.frmIntegracionContable.hdnc1.value
				c2 = document.frmIntegracionContable.hdnc2.value
				BuscarIntegraciones(t1,c1,c2);
		}
 		}
 		ajax.send(null)
}
</script>

<!--*****************************************************************************************-->
<!--************************************FORMULARIO HTML**************************************-->
<!--*****************************************************************************************-->

<table border="0" width="100%">
        <tr>
          	<td class="tituloPaginaContabilidad">Integraci&oacute;n Contable</td>            
        </tr>
</table>

<form name="frmIntegracionContable" action="frmIntegracionContable.php" method="post">
<!--  <div style="width:870px;">
        <div class="x-box-tl"><div class="x-box-tr"><div class="x-box-tc"></div></div></div>
        <div class="x-box-ml"><div class="x-box-mr"><div class="x-box-mc">
            <div class="x-form-bd" id="container">
                <fieldset>-->
<table width="100%">
	<tr>
        <td>
        	<fieldset>
            <legend class="legend">Integraci&oacute;n Contable</legend>
            <table border="0" align="center">
                <tr>    
                    <td class="tituloCampo" width="140" align="right"> 
                        Sucursal:
                    </td>
                    <td align="left">
                        <select name="TSucursal" onChange="Recargar();">
                            <option value="">Seleccione...</option>
								<?php 
									$conAd = ConectarBD();
									$sqlOpt = sprintf("SELECT * FROM company");
									$rs =EjecutarExecAd($conAd,$sqlOpt);
										if($rs != ""){
										$id = 1;
											while ($row=ObtenerFetch($rs)){ 
												$des = $row[1];
												echo sprintf("<option value=%s>%s</option>",$id,$des);
												$id ++;
											}	
										}
                                ?>
                        </select>
                    </td>
                    <td class="tituloCampo" width="140" align="right"> 
                        M&oacute;dulo: 
                    </td>
                    <td align="left">
                        <select name="TGrupo" onChange="Recargar();">
                            <option value="">Seleccione...</option>
                            <option value="A">Administraci&oacute;n</option>
                            <option value="R">Repuestos</option>
                            <option value="V">Veh&iacute;culos</option>
                            <option value="S">Servicios</option>
                            <option value="T">Tesorer&iacute;a</option>
                        </select>
                    </td>
	  					<script language='javascript'>
                        document.frmIntegracionContable.TGrupo.value =  '<?=$TGrupo?>'
                        document.frmIntegracionContable.TSucursal.value =  '<?=$TSucursal?>'
                  </script>
					<td class="tituloCampo" width="140" align="right"> 
<?php 
	$conAd = ConectarBD();
	if ($TGrupo != ""){
		$SqlStr = "Select a.id,a.descripcion from encabezadointegracion a where grupo ='$TGrupo' AND sucursal = '$TSucursal'";
		//$SqlStr = "SELECT a.id,a.descripcion FROM encabezadointegracion a WHERE grupo ='$TGrupo'";
		$exc = EjecutarExec($conAd,$SqlStr) or die($SqlStr);
	}
?>
						Acci&oacute;n: 
					</td>
        			<td align="left">
                        <select name="idModulo" onChange="CargarDiv();">
                            <option value=$id>Seleccione...</option>
<?php 
	if ($TGrupo != ""){
		while ($row=ObtenerFetch($exc)){ 
			$id  = $row[0];
			$des = $row[1];
	 		echo "<option value=$id>$des</option>";
		}
	}
?>   						
						</select>
					</td>		
				</tr>
                <tr>
                	<td>&nbsp;</td>
                </tr>
			</table>
            </fieldset>
		</td>
	</tr>     
    <tr>
        <td  height=20 colspan="4" align="center" valign=top ></font></td> 		
    </tr>
    <tr>
        <td align="center" colspan="4"> <div id="DivComboNo"> </div> </td> 	
        <!--<br>-->	   
    </tr>
    <!--<p>&nbsp;</p>-->
    <tr>
        <td align="center" colspan="4"> <div id="DivInformacion"> </div> </td> 		
       
    </tr>
    <tr>
        <td align="center"colspan="4"> <div id="DivCombo"></div> </td> 		
        <br>
    </tr>  
 </table>
 </fieldset>
<!--       </div>
         </div></div></div>-->
        <div class="x-box-bl"><div class="x-box-br"><div class="x-box-bc"></div></div></div>
</table> 
<input name=TACondicion type=hidden value=''>
<input name=TAValores type=hidden value=''>
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
</div>
</body>
</html>