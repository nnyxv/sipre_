<?php session_start();
include_once('FuncionesPHP.php');
?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="./resources/css/ext-all.css" />

<title>Cerrar los Estados</title>
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
CargarNoCerradas();
}	
function CargarNoCerradas(){
		MiDiv = document.getElementById("DivComboNo");
		ajax=objetoAjax();
 		ajax.open("GET", "CargarNoCerradas.php?Mes="+document.frmPantallaCerrarTodas.Mes.value + "&Ano="+document.frmPantallaCerrarTodas.Ano.value);
 		ajax.onreadystatechange=function() {
  			if (ajax.readyState==4) {
   				MiDiv.innerHTML  = ajax.responseText
				CargarCerradas();	
  			}
 		}
 		ajax.send(null)
}


function CargarCerradas(){
		MiDiv = document.getElementById("DivCombo");
		ajax=objetoAjax();
 		ajax.open("GET", "CargarCerradas.php?Mes="+document.frmPantallaCerrarTodas.Mes.value + "&Ano="+document.frmPantallaCerrarTodas.Ano.value);
 		ajax.onreadystatechange=function() {
  			if (ajax.readyState==4) {
   				MiDiv.innerHTML  = ajax.responseText
  			}
 		}
 		ajax.send(null)
}
function ExtraerIn(){
		MiDiv = document.getElementById("DivInformacion");
		ajax=objetoAjax();
 		ajax.open("GET", "ExtraerInformacion.php?Mes="+document.frmPantallaCerrarTodas.Mes.value + "&Ano="+document.frmPantallaCerrarTodas.Ano.value+"&Cod="+document.frmPantallaCerrarTodas.T_Company.value);
 		ajax.onreadystatechange=function() {
  			if (ajax.readyState==4) {
   				MiDiv.innerHTML  = ajax.responseText
  			}
 		}
 		ajax.send(null)
}

function CerrarEstado(){
	if (document.frmPantallaCerrarTodas.T_Company.value == ""){
	    alert("Seleccione el Estado");
		return;
    }	
		//MiDiv = document.getElementById("DivInformacion");
		document.frmPantallaCerrarTodas.target='mainFrame';
		document.frmPantallaCerrarTodas.method='post';
		document.frmPantallaCerrarTodas.action="CerrarMesEstado.php";
		document.frmPantallaCerrarTodas.submit();
}


</script>
</p>
<p>
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--************************************FORMULARIO HTML**************************************-->
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<form name="frmPantallaCerrarTodas"action="frmPantallaCerrarTodas.php"method="post">

  <div style="width:600px;">
        <div class="x-box-tl"><div class="x-box-tr"><div class="x-box-tc"></div></div></div>
        <div class="x-box-ml"><div class="x-box-mr"><div class="x-box-mc">
            <h3 style="margin-bottom:10px;">Cerrar Estados</h3>
            <div class="x-form-bd" id="container">
                <fieldset>

<table width=500 border=0 align=center height=0 cellpadding=0 cellspacing=0 class=cabecera>
	  <tr>
  			 <td  height=20 align="center" valign=top> 
         	    Mes: <select name="Mes">
				<?php for ($i=1;$i <= 12; $i++){
					 echo "<option value=$i>$i</option>";
				}?>   		
				</select>
				Año: <select name="Ano">
				<?php for ($i=2007;$i <= 2050; $i++){
					 echo "<option value=$i>$i</option>";
				}?>   		
				</select>
			 </td> 
 
 </tr>
	 <tr>
	   <td  height=20 colspan="4" align="center" valign=top><button name="BtnVer" type="submit" onClick="CargarDiv();" value="Ver Mes">Ver Mes</button></td> 		
	 </tr>
	 <tr>
	   <td align="center"> <div id="DivComboNo"> </div> </td> 	
       <br>	   
	 </tr>
	  <p>&nbsp;</p>
	 <tr>
	   <td align="center"> <div id="DivInformacion"> </div> </td> 		
	   <br>
	 </tr>
	 <tr>
	   <td align="center"> <div id="DivCombo"></div> </td> 		
	   <br>
	 </tr>
 </table>
 </fieldset>
       </div>
         </div></div></div>
        <div class="x-box-bl"><div class="x-box-br"><div class="x-box-bc"></div></div></div>
    </div>
 
 
</table> 
</form>
</body>
</html>