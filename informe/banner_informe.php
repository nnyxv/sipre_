<script language="JavaScript1.2" type="text/javascript">
function MM_findObj(n, d) { //v4.01
	var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
	d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
	if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
	for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
	if(!x && d.getElementById) x=d.getElementById(n); return x;
}

function MM_swapImage() { //v3.0
	var i,j=0,x,a=MM_swapImage.arguments; document.MM_sr=new Array; for(i=0;i<(a.length-2);i+=3)
	if ((x=MM_findObj(a[i]))!=null){document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
}

function MM_swapImgRestore() { //v3.0
	var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
}

function MM_preloadImages() { //v3.0
	var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
	var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
	if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}
</script>
<script type="text/javascript" language="JavaScript" src="mm_menu.js"></script>
<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>

<?php
require_once("../connections/conex.php");

$idUsuario = $_SESSION['idUsuarioSysGts'];
$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];

$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
	valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
$rsEmp = mysql_query($queryEmp, $conex) or die(mysql_error());
$rowEmp = mysql_fetch_assoc($rsEmp);

(strlen($rowEmp['telefono1']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono1'] : "";
(strlen($rowEmp['telefono2']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono2'] : "";
(strlen($rowEmp['telefono_taller1']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono_taller1'] : "";
(strlen($rowEmp['telefono_taller2']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono_taller2'] : "";
?>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
	<td>
    <form class="form-3">
    	<table width="100%">
        <tr>
        	<td>
            	<table>
                <tr>
                	<td>
                    	<table style="text-align:center; background:#FFF; border-radius:0.4em;">
                        <tr>
                            <td><img id="imgLogoEmpresa" name="imgLogoEmpresa" src="../<?php echo $rowEmp['logo_familia']; ?>" width="180"></td>
                        </tr>
                        </table>
                    </td>
                    <td style="padding:4px">
						<p style="font-size:11px;">
							<?php echo utf8_encode($rowEmp['nombre_empresa']); ?>
                            <br>
                            <?php echo utf8_encode($rowEmp['rif']); ?>
                            <br>
                            <?php echo (count($arrayTelefonos) > 0) ? "Telf.: ".implode(" / ", $arrayTelefonos): ""; ?>
                            <br>
                            <?php echo utf8_encode($rowEmp['web']); ?>
                        </p>
                    </td>
                </tr>
                </table>
            </td>
            <td align="right" width="300"><p class="textoAzul" style="font-size:20px; padding-left:2px;">Informe Gerencial</p></td>
        </tr>
        </table>
        </form>
    </td>
</tr>
<tr class="noprint">
	<td align="right">
		<a href="if_absorcion_financiera_list.php" onmouseout="MM_swapImgRestore();" onmouseover="MM_swapImage('menu_informe8','','../img/informe_gerencial/inf_absorcion_financiera.gif',1);"><img name="menu_informe8" src="../img/informe_gerencial/inf_absorcion_financiera.gif" border="0" id="menu_informe8"/></a>
    	<a href="if_reporte_mor_list.php" onmouseout="MM_swapImgRestore();" onmouseover="MM_swapImage('menu_informe6','','../img/informe_gerencial/inf_reporte_mor_f2.gif',1);"><img name="menu_informe6" src="../img/informe_gerencial/inf_reporte_mor.gif" border="0" id="menu_informe6"/></a>
        <a href="if_reporte_pvr_list.php" onmouseout="MM_swapImgRestore();" onmouseover="MM_swapImage('menu_informe7','','../img/informe_gerencial/inf_reporte_pvr_f2.gif',1);"><img name="menu_informe7" src="../img/informe_gerencial/inf_reporte_pvr.gif" border="0" id="menu_informe7"/></a>
    	
        <a href="if_reporte_venta_list.php" onmouseout="MM_swapImgRestore();" onmouseover="MM_swapImage('menu_informe1','','../img/informe_gerencial/inf_reporte_venta_f2.gif',1);"><img name="menu_informe1" src="../img/informe_gerencial/inf_reporte_venta.gif" border="0" id="menu_informe1"/></a>
        <a href="if_resumen_postventa_list.php" onmouseout="MM_swapImgRestore();" onmouseover="MM_swapImage('menu_informe2','','../img/informe_gerencial/inf_resumen_post_venta_f2.gif',1);"><img name="menu_informe2" src="../img/informe_gerencial/inf_resumen_post_venta.gif" border="0" id="menu_informe2"/></a>
        <a href="if_reporte_postventa_list.php" onmouseout="MM_swapImgRestore();" onmouseover="MM_swapImage('menu_informe3','','../img/informe_gerencial/inf_reporte_post_venta_f2.gif',1);"><img name="menu_informe3" src="../img/informe_gerencial/inf_reporte_post_venta.gif" border="0" id="menu_informe3"/></a>
        <a href="if_comisiones_list.php" onmouseout="MM_swapImgRestore();" onmouseover="MM_swapImage('menu_informe4','','../img/informe_gerencial/inf_comisiones_f2.gif',1);"><img name="menu_informe4" src="../img/informe_gerencial/inf_comisiones.gif" border="0" id="menu_informe4"/></a>
        <a href="../index2.php" onmouseout="MM_swapImgRestore();" onmouseover="MM_swapImage('menu_informe5','','../img/informe_gerencial/inf_salir_f2.gif',1);"><img name="menu_informe5" src="../img/informe_gerencial/inf_salir.gif" border="0" id="menu_informe5"/></a>
    </td>
</tr>
</table>