<html>
<head>
<link rel="stylesheet" type="text/css" href="./resources/css/ext-all.css" />


<style type="text/css">
<!--
.SinBorde {
	font-family: Arial, Helvetica, sans-serif;
	font-size: xx-small;
	color: #0033CC;
	border: none;
}
-->
</style>
<style type="text/css">
<!--
@import url("estilosite.css");
-->
</style>
<style type="text/css">
<!--
-->
</style>
<script language="JavaScript"src="./GlobalUtility.js">
</script>
<script language= "javascript" >
<!--*****************************************************************************************-->
<!--************************VER CONFIGURACION DE REPORTE*************************************-->
<!--*****************************************************************************************-->
function Entrar(){
document.FrmIzquerda.target='_self';
document.FrmIzquerda.method='post';
//document.FrmIzquerda.action='VerificarAccesoSistema.php';
document.FrmIzquerda.action='VerificarBrowse.php';
document.FrmIzquerda.submit();

}

function SelTexto(obj){
if (obj.length != 0){
obj.select();
}
}
</script>
<title>Untitled Document</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<body>
<form name="FrmIzquerda" method="post" action="VerificarAccesoSistema.php">
	 <div style="width:220px;">
        <div class="x-box-tl"><div class="x-box-tr"><div class="x-box-tc"></div></div></div>
        <div class="x-box-ml"><div class="x-box-mr"><div class="x-box-mc">
            <h3 style="margin-bottom:5px;">Acceso al Sistema</h3>  
				<div class="x-form-bd" id="container">
                	<fieldset>
				    	 <br> 
						<div class="x-form-item">
                        <label for="combo-local">Usuario           <input name="TexUsuario" size="25" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" type="text" class="cTexBox"></label>
                    	</div>
						<div class="x-form-item">
                        <label for="combo-local">Clave	          <input name="TexClave" size = "25" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" witdh = "15" type="password" class="cTexBox"></label>
                    	</div>
                </fieldset>
            </div>
         </div></div></div>
        <div class="x-box-bl"><div class="x-box-br"><div class="x-box-bc"></div></div></div>
    </div>
   	<input  name="BtnAceptar" type="button" maxlength=23 size=10 onclick="Entrar();" value="Aceptar" ></font>
	
<?php if ($TMensaje	!= ""){ ?>

   <div style="width:220px;">
        <div class="x-box-tl"><div class="x-box-tr"><div class="x-box-tc"></div></div></div>
        <div class="x-box-ml"><div class="x-box-mr"><div class="x-box-mc">
            <div class="x-form-bd" id="container">
      <P align="center"><font size="-1" color="#CC0000"><?php print($TMensaje); ?> </font></p>
            </div>
         </div></div></div>
        <div class="x-box-bl"><div class="x-box-br"><div class="x-box-bc"></div></div></div>
    </div>


<?php } ?>	


</form>
<font size="-1"></font> 
</body>
</html>
