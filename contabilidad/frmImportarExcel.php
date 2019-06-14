<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<link rel="stylesheet" type="text/css" href="./resources/css/ext-all.css" />

<title>Carga de Archivo Excel con activos</title>
</head>
<style type="text/css">
<!--
@import url("estilosite.css");
-->
</style>
<style type="Text/css">
</style>
<body >
<br /><br /><br />
<form action="cargarExcel.php" method="post" enctype="multipart/form-data">
<div style="width:auto;">
	<div class="x-box-tl">
    	<div class="x-box-tr">
        	<div class="x-box-tc"></div> <!--imagen para el border superior-->
        </div>
    </div>
    <div class="x-box-ml">
    	<div class="x-box-mr">
        	<div class="x-box-mc"> <!--contine el sub-titulo dela formulario-->
            	<h3> - Seleccione un archivo para la carga -</h3>
                	<br />
                <div class="x-form-bd">
                	<fieldset>
                    	<br />
                        <table width="100%" align="center" cellpadding="0" cellspacing="0"> 
                            <tr>
                                <td class="cabecera" width="100"><label for="archivo">Seleccione un Archivo:</label></td>
                                <td  width="140"valign="top"><input type="file" name="archivo" id="archivo" /></td>
                            </tr>
                        </table>
                        <br />
                    </fieldset>
                </div>
            </div>
        </div>
        <div class="x-box-bl">
        	<div class="x-box-br">
            	<div class="x-box-bc"></div>
            </div>
        </div>
   	</div>
</div>
    <table width="50%">
        <tr align="center">
            <td  colspan="2" width="10"> 
                <button type="submit" id="btuCArgar" name="btuCArgar"> 
                	 <table align="center" cellpadding="0" cellspacing="0"> 
                            <tr>
                                <td align="center">Cargar</td>
                                <td align="center"><img src="../img/iconos/return.png" width="16" height="16" title="Cargar Excel"></td>
                            </tr>
                        </table>
                </button> 
            </td>
        </tr>
    </table>
</form>
</body>
</html>