<?php
session_start();
include_once('FuncionesPHP.php');

$oTablaSelec = str_replace(".php","",$oTablaSelec);

?>
  
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type"content="text/html; charset=iso-8859-1">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE 2.0 :. Contabilidad - <?php echo ($oTablaSelec == "D") ? "Seleccionar Diarios" : "Seleccionar Posteriores"; ?></title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css" />
    
    <link rel="stylesheet" type="text/css" href="../js/domDragContabilidad.css" />

	<script language="JavaScript"src="./GlobalUtility.js"></script>
	<script language= "javascript" >
	<!--*****************************************************************************************-->
	<!--**********************************Buscar Descripcion General*************************************-->
	<!--*****************************************************************************************-->
	function BuscarDescripGeneral(sValor,sCampoBuscar,oArreglo){
		if (Alltrim(sValor) != ""){
			//substring(codigo,1,len(rtrim(" & "'" & Trim(Texcodigo.Text) & "'))) = '" & Trim(Texcodigo.Text) & "'")
			document.ElegirMovimientos.TACondicion.value=sCampoBuscar + "= '" + sValor + "'";
			document.ElegirMovimientos.TAValores.value=oArreglo;
			document.ElegirMovimientos.method='post';
			document.ElegirMovimientos.target='topFrame';
			document.ElegirMovimientos.action='BusTablaParametros.php';
			document.ElegirMovimientos.submit();
		}// if (Alltrim(sValor) != ""){
	}//function BuscarDescrip(sValor,sCampoBuscar,oArreglo){
	
	<!--*****************************************************************************************-->
	<!--**********************************Abrir Ventana de Busqueda******************************-->
	<!--*****************************************************************************************-->
	function AbrirBus(sObjeto,oArreglo){
		if (sObjeto == "oCC" && document.ElegirMovimientos.oCC.readOnly== true){
			return;
		}
		
		winOpen('PantallaBuscar.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,'');
	}// function AbrirBus(sObjeto,oArreglo){
	
    <!--*****************************************************************************************-->
    <!--************************VER CONFIGURACION DE REPORTE*************************************-->
    <!--*****************************************************************************************-->
    function Entrar(){
		if (document.ElegirMovimientos.oCC.value == ""){
			alert("Introduzca el centro de costo");
			document.ElegirMovimientos.oCC.focus();
			return;
		}
		sDia = document.ElegirMovimientos.xDFecha.value
		sMes = document.ElegirMovimientos.xMFecha.value
		sAno  = document.ElegirMovimientos.xAFecha.value
		sFecha = sDia + "/" + sMes + "/" + sAno;
		caja = document.ElegirMovimientos.TFec_Proceso.value 
		a = caja.substr(6,4);
		m = caja.substr(3,2);
		d = caja.substr(0,2);
		sFechaAnoPro = a+m;
		sFechaAnoInt =  sAno+sMes;  
		if (IsDate(sFecha) != true) {
			alert("La Fecha asignada esta errada.",16)
			document.ElegirMovimientos.xDFecha.focus();
			return;
		}//if  (IsDate(sFecha)){	  
		if (sFechaAnoInt <= sFechaAnoPro && document.ElegirMovimientos.oTablaSelec.value == "P"){
			alert("la Fecha debe ser superior a la fecha de proceso")
			return;
		}
		
		cod=document.ElegirMovimientos.oCC.value;
		
		document.ElegirMovimientos.xDia.value = document.ElegirMovimientos.xDFecha.value
		document.ElegirMovimientos.xMes.value = document.ElegirMovimientos.xMFecha.value
		document.ElegirMovimientos.xAno.value = document.ElegirMovimientos.xAFecha.value
		
		document.ElegirMovimientos.target='_self';
		document.ElegirMovimientos.method='post';
		document.ElegirMovimientos.action='frmDiarios.php?oTablaSelec='+document.ElegirMovimientos.oTablaSelec.value+'&oCC='+cod;
		document.ElegirMovimientos.submit();
    }
    
    function SelTexto(obj){
		if (obj.length != 0){
			obj.select();
		}
    }
    </script>
</head>
<body>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_contabilidad2.php"); ?></div>
    
    <div id="divInfo" class="print">
    <form name="ElegirMovimientos" action="ElegirEmpresa.php" method="post">
    <table width="100%">
        <tr>
        	<td class="tituloPaginaContabilidad"><?php echo ($oTablaSelec=="D") ? "Seleccionar Diarios" : "Seleccionar Posteriores"; ?></td>
        </tr>
	</table>
    <table width="100%">
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>    
		<td><fieldset>
		  	<legend class="legend">Datos del Movimiento</legend>
        	<table border="0" align="center">
        		<tr> 
            		<td>
						<table align="center" border="0" width="560">            
							<tr align="left">  
    							<td align="right" class="tituloCampo" width="22%">
                                	<span class="textoRojoNegrita">*</span>Centro de Costo:
                        		</td>                   
    							<td width="78%">
<?php //para el centro de costo
	$Arretabla[0][0] = "centrocosto"; //Tabla
	$Arretabla[0][1] = 'T';
	$Arretabla[1][0] = "codigo"; //Campo1
	$Arretabla[1][1] = 'C';
	$Arretabla[2][0] = "descripcion"; //Campo2
	$Arretabla[2][1] = 'C';
	$Arretabla[3][0] = "oCC"; //Objeto del Campo1
	$Arretabla[3][1] = 'O';
	$Arretabla[4][0] = "oDesCC"; //Objeto del Campo2
	$Arretabla[4][1] = 'O';
	$Arretabla[5][0] = 'ElegirMovimientos';// Pantalla donde estamos ubicados
	$Arretabla[5][1] = 'P';
	$sClaveCon = "codigo"; // Campo Clave para buscar
	$ArreCC = array_envia($Arretabla); // Serializar Array
	
	$sReadonly = ""; 
	if ($_SESSION["CCSistema"] != ""){
		$oCC = $_SESSION["CCSistema"];
		$oDesCC = $_SESSION["DesCCSistema"];			
		$sReadonly = " readonly ";
	} 
?>
                                    <input type="text" id="oCC" name="oCC" <?=$sReadonly?> class='cTexBox' maxlength="8" onBlur="<?php print("BuscarDescripGeneral(this.value,'$sClaveCon','$ArreCC')");?>" onDblClick="<?php print("AbrirBus(this.name,'$ArreCC')");?>" onKeyPress="fn(this.form,this,event,'xDFecha')" size="10" style="text-align:right" value="<?=$oCC?>" align="baseline">
                                    <button type="button" title="Listar" onclick="<?php print("AbrirBus(this.name,'$ArreCC')");?>"><img src="../img/iconos/help.png"/></button><!--<img src="../img/iconos/information.png" title="doble clic para listar centro" align="absbottom"></img>-->
                                    <input type="text" name="oDesCC" class="cTexBoxdisabled" maxlength="60" readonly="readonly" size="40" value="<?= $oDesCC ?>">
                                </td>
                            </tr>
                            <tr align="left">  
                                <td align="right" class="tituloCampo">Fecha:</td>
                                <td>
<?php
	if ($oTablaSelec == 'D'){
		$sDesa = "readonly class='cNumdisabled'";
		$TablaSelec = "enc_diario";
		$xDia = obFecha($_SESSION["sFec_Proceso"],'D');
		$xMes = obFecha($_SESSION["sFec_Proceso"],'M');
		$xAno = obFecha($_SESSION["sFec_Proceso"],'A');
	} 
?>			
                                    
<?php
	if ($oTablaSelec == 'P'){
		$sDesa = "class='cNum'";
		$xDia = obFecha($_SESSION["sFec_Proceso"],'D');
		$xMes = obFecha($_SESSION["sFec_Proceso"],'M');
		$xAno = obFecha($_SESSION["sFec_Proceso"],'A');
		$sFechaSumar = date("Y-m-d",mktime(0,0,0,$xMes+1,1,$xAno)); 
		$xDia = obFecha($sFechaSumar,'D');
		$xMes = obFecha($sFechaSumar,'M');
		$xAno = obFecha($sFechaSumar,'A');
		$TablaSelec = "enc_dif";
	} 
?>			
                                    
<?php
	if ($oTablaSelec == 'H'){ 
		$xDia = obFecha($_SESSION["sFec_Proceso"],'D');
		$xMes = obFecha($_SESSION["sFec_Proceso"],'M');
		$xAno = obFecha($_SESSION["sFec_Proceso"],'A');
		$sFechaSumar = date("Y-m-d",mktime(0,0,0,$xMes-1,1,$xAno)); 
		$xDia = obFecha($sFechaSumar,'D');
		$xMes = obFecha($sFechaSumar,'M');
		$xAno = obFecha($sFechaSumar,'A');
		$TablaSelec = "enc_historico";
	} 
?>
                                    
<?php
	if ($oTablaSelec == 'I') { 
		$xDia = obFecha($_SESSION["sFec_Proceso"],'D');
		$xMes = obFecha($_SESSION["sFec_Proceso"],'M');
		$xAno = obFecha($_SESSION["sFec_Proceso"],'A');
		$sFechaSumar = date("Y-m-d",mktime(0,0,0,$xMes-1,1,$xAno)); 
		$xDia = obFecha($sFechaSumar,'D');
		$xMes = obFecha($sFechaSumar,'M');
		$xAno = obFecha($sFechaSumar,'A');
		$TablaSelec = "enc_importados";
	} 
?>			
                                    
                                    <input type="text" id="xDFecha" name="xDFecha" class="cNum" maxlength="2" onfocus="SelTexto(this);" onKeyPress="return CheckNumericJEnter(this.form,this,event,'oConcepto')" size="1" style="text-align:center" value="<?php  print($xDia); ?>">
                                    <input type="text" name="xMFecha" <?=$sDesa?> maxlength="2" onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" size="1" style="text-align:center" value="<?php print($xMes); ?>">
                                    <input type="text" name="xAFecha" <?=$sDesa?> maxlength="4" onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" size="4" style="text-align:center" value="<?php print($xAno); ?>">
                                </td>
                            </tr>
                            </table>
                            </td>
                            </tr>
                            </table>
                            </fieldset>
                            
                            <table width="100%">	  
                            <tr>
                                <td align="right"><hr/>
                                    <button type="button" name="BtnAceptar" maxlength="23" onClick="Entrar();" size="10">Aceptar</button>
                                </td>
                            </tr>
                            </table>
                            </td>
                            </tr>
                           
                        
                    
                
                

            </td>
        </tr>	
        </table>
        <input type="hidden" name="oTablaSelec" value="<?=$_REQUEST["oTablaSelec"]; ?>">
        <input type="hidden" name="xDia" value="">
        <input type="hidden" name="xMes" value="">
        <input type="hidden" name="xAno" value="">
        <input type="hidden" name="TAValores"> 
        <input type="hidden" name="TFec_Proceso" value="<?=obFecha($_SESSION["sFec_Proceso"]);?>" > 
        <input type="hidden" name="TACondicion"> 
    </form>
    </div>
	</div>	
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>

</body>
</html>