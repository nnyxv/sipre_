<?php session_start();
include_once('FuncionesPHP.php');
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE 2.0 :. Contabilidad - Elegir Fecha</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css" />
    
    <link rel="stylesheet" type="text/css" href="../js/domDragContabilidad.css" />
    
	<script language="JavaScript"src="./GlobalUtility.js"></script>
	<script language= "javascript" >
    <!--*****************************************************************************************-->
    <!--************************VER CONFIGURACION DE REPORTE*************************************-->
    <!--*****************************************************************************************-->
    function Entrar(){
        document.ElegirFecha.target='_self';
        document.ElegirFecha.method='post';
        document.ElegirFecha.action='GrabarFecha.php';
        document.ElegirFecha.submit();
    }
    
    function SelTexto(obj){
		if (obj.length != 0){
			obj.select();
		}
    }
    </script>
<body>
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_contabilidad.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
        <span class="textoAzulCelesteNegrita_24px">SISTEMA DE CONTABILIDAD</span>
        <br>
        <span class="textoGrisNegrita_13px">Versión 3.0</span>
    	
        <br><br>
        
        <table align="center">
        <tr>
        	<td>
            <form id="frmEmpresa" name="frmEmpresa" style="margin:0" action="GrabarFecha.php">
            <fieldset><legend class="legend">Fecha de Proceso</legend>
                <?php
				$_SESSION["sBasedeDatos"] = $TexEmpresa;
				$con = ConectarBDAd();
				$sTabla='company';
				$sCondicion=" codigo='". $TexEmpresa ."'"; 
				$sCampos='descripcion';
				$sCampos.=',servidor';
				$SqlStr='SELECT '.$sCampos.' FROM '.$sTabla. " WHERE ". $sCondicion;
				$exc = EjecutarExecAd($con,$SqlStr) or die($SqlStr);
				if (NumeroFilas($exc)>0){
					$_SESSION["sDesBasedeDatos"]=ObtenerResultado($exc,1);
					$_SESSION["sServidor"]=ObtenerResultado($exc,2);
				}
				$con = ConectarBD();
				$sTabla='parametros';
				$sCondicion='';
				$sCampos='fec_proceso,rif';
				$SqlStr='SELECT '.$sCampos.' FROM '.$sTabla;
				$exc = EjecutarExec($con,$SqlStr) or die(mysql_error());
				if (NumeroFilas($exc)>0) {
					$xDFecha=obFecha(ObtenerResultado($exc,1),'D');
					$xMFecha=obFecha(ObtenerResultado($exc,1),'M');
					$xAFecha=obFecha(ObtenerResultado($exc,1),'A');
					$_SESSION["rifEmpresa"]=ObtenerResultado($exc,2); ?>
                <table width="360">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="30%">Fecha:</td>
                    <td id="tdlstFecha" width="70%">
                        <input type="text" name="xDFecha" class="cNum" maxlength="2" onFocus="SelTexto(this);" onKeyPress="CheckNumericJEnter(this.form,this,event,'BtnAceptar')" size="1" style="text-align:center" value="<?=$xDFecha?>">
                        <input type="text" name="xMFecha" class="cNumdisabled" maxlength="2" onFocus="SelTexto(this);" onKeyPress="CheckNumericJEnter(this.form,this,event,'')" readonly="readonly" size="1" style="text-align:center" value="<?=$xMFecha?>">
                        <input type="text" name="xAFecha" class="cNumdisabled" maxlength="4" onFocus="SelTexto(this);" onKeyPress="CheckNumericJEnter(this.form,this,event,'')" readonly="readonly" size="4" style="text-align:center" value="<?=$xAFecha?>">                           
                    </td>
                </tr>
				<?php
					$con = ConectarBD();
					if ($_SESSION["CCSistema"] != "") {
						$sTabla='sipre_contabilidad.centrocosto ';
					} else {
						$sTabla='centrocosto ';
					}
					
					$sCondicion='';
					$sCampos='descripcion';
					$sCondicion='codigo= '."'".trim($_SESSION["CCSistema"])."'";
					$SqlStr='SELECT '.$sCampos.' FROM '.$sTabla. ' WHERE ' .$sCondicion;
					$exc = EjecutarExec($con,$SqlStr) or die(mysql_error());
					if ( NumeroFilas($exc)>0){
					   $_SESSION["DesCCSistema"] =trim(ObtenerResultado($exc,1));								
					}
				} ?>
                <tr>
                    <td align="right" colspan="2">
                        <button type="submit"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/accept.png"/></td><td>&nbsp;</td><td>Aceptar</td></tr></table></button>
                    </td>
                </tr>
                </table>
            </fieldset>
            </form>
            </td>
        </tr>
        </table>
	</div>
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>