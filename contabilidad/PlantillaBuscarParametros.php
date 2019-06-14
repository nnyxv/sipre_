<?php 
	session_start();
	include_once('FuncionesPHP.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<!--<link rel="stylesheet" type="text/css" href="./resources/css/ext-all.css" />-->

<script language="JavaScript" src="./GlobalUtility.js"></script>
<script language="JavaScript">

	function BuscarJ(sP,iCa,parExceloPdf){
		//sP: reporte a consultar
		//iCa: campos de busqueda (criterios)
		//parExceloPdf: si es en PDF o en Excel el reporte	
		sPar = "";
		bAmper = "";	
		
		for(i=1;i<=iCa;i++){
			if (typeof(eval('document.PlantillaBuscarParametros.xFeccDesde'+Alltrim(i.toString()))) != 'undefined'){				
				sValorFecha = eval('document.PlantillaBuscarParametros.xFeccDesde'+Alltrim(i.toString())+'.value');						
				var anioD = String(sValorFecha).substring(6,10); 
				var mesD = String(sValorFecha).substring(3,5);
				var diaD = String(sValorFecha).substring(0,2);	
					
				sValorFecha = anioD+ "-" +mesD+ "-" +diaD;
				
				//para comparar las fechas, fecha desde
				fdes = anioD+ "-" +mesD+ "-" +diaD;
				///////////////////////////////////////
												
				sPar = sPar + bAmper + 'cDesde' + Alltrim(i.toString()) + "=" + sValorFecha;				
				bAmper = "&";
				
				if(typeof(eval('document.PlantillaBuscarParametros.xFeccHasta'+Alltrim(i.toString()))) != 'undefined'){	
					//fecha hasta, si aplica											
						
					sValorFecha = eval('document.PlantillaBuscarParametros.xFeccHasta'+Alltrim(i.toString())+'.value');	
					
					var anioH = String(sValorFecha).substring(6,10); 
					var mesH = String(sValorFecha).substring(3,5);
					var diaH = String(sValorFecha).substring(0,2);	
				
					sValorFecha = anioH+ "-" +mesH+ "-" +diaH;		
					
					//para comparar las fechas, fecha hasta
					fhas = anioH+ "-" +mesH+ "-" +diaH;
					///////////////////////////////////////
								
					sPar = sPar + bAmper + 'cHasta' + Alltrim(i.toString()) + "=" + sValorFecha;
					
					//filtro para descartar que la fecha desde sea mayor que la fecha hasta
					if(fdes>fhas){
						alert("La fecha Desde no puede ser mayor a la fecha Hasta");
						return;
					}
					///////////////////////////////////////////////////////////////////////
				}				
			}else{
				//campos que no son fecha			
				if (typeof(eval('document.PlantillaBuscarParametros.cDesde'+Alltrim(i.toString()))) != 'undefined'){
					sPar = sPar + bAmper + 'cDesde' + Alltrim(i.toString()) + "=" + eval('document.PlantillaBuscarParametros.cDesde'+Alltrim(i.toString())+'.value');
				}
				bAmper = "&";		
				if (typeof(eval('document.PlantillaBuscarParametros.cHasta'+Alltrim(i.toString()))) != 'undefined'){
					sPar = sPar + bAmper + 'cHasta' + Alltrim(i.toString()) + "=" + eval('document.PlantillaBuscarParametros.cHasta'+Alltrim(i.toString())+'.value');
				}
			}
		}
		day = new Date();
		id = day.getTime();
	//eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=0,scrollbars=0,location=0,statusbar=1,menubar=0,resizable=0,"+result+"');");
		eval("page" + id + "= open('','" + id + "','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no');");
		sPar = sPar + "&ExceloPdf="+parExceloPdf;
		eval("page" + id + ".location ='"+sP+'?'+sPar+"'");
	}

	function JJ(){
		document.PlantillaBuscarParametros.method='post';
		document.PlantillaBuscarParametros.target='topFrame';
		document.PlantillaBuscarParametros.action='FrmArriba.php';
		document.PlantillaBuscarParametros.submit();
	}

	function Titu(obj){
		obj.title = obj.value; 
	}
	
	function BuscarDescrip(sValor,sCampoBuscar,oArreglo){
		document.PlantillaBuscarParametros.TACondicion.value=sCampoBuscar + "= '" + sValor + "'";   
		document.PlantillaBuscarParametros.TAValores.value=oArreglo;   
		document.PlantillaBuscarParametros.method='post';
		document.PlantillaBuscarParametros.target='topFrame';
		document.PlantillaBuscarParametros.action='BusTablaParametros.php';
		document.PlantillaBuscarParametros.submit();
	}

	function AbrirBus(sObjeto,oArreglo){
		winOpen('PantallaBuscar.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,'');
	}
</script>

<?php 
	$_SESSION["sGPosterior"] = $_GET["spPosterior"];
?>

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
<form method="post"  name="PlantillaBuscarParametros">

<?php
    function FixSqlstring($sValor){
    	return str_replace("'","''",$sValor);
    }
	
    $sCampos = "Plantilla"; //1
    $sCampos.= ",Longitud";//2
    $sCampos.= ",TitulodelCampo";//3
    $sCampos.= ",Objeto";//4
    $sCampos.= ",CamposRelacionados";//5
    $sCampos.= ",NombreTabla";//6 se escojera este campo como el nombre de  la pagina que sera llamda despues de esta pantalla
    $sCampos.= ",TipoLinea";//7
	$sCampos.= ",TipoLogico";//8
	
    $sCampos.= ",Busqueda";//9
    $con = ConectarBDAd();
    //$_GET["sPlan"]
    if ( trim($sPlan) == ''){
    	$sPlantilla = $_GET["TexsPlantillaB"];
    }
    else{
    	$sPlanti = trim($_GET["sPlan"]);
		$sPlantilla = substr($sPlanti,0,3);	
    }
    //OJO variables de session
    //session("sConPara")= ""
    //session("sPlantillaSe")= request.QueryString("sPlan")
    //.$sPlantilla.

    $sql = "SELECT " .$sCampos. " FROM plantillas WHERE rtrim(Codigo) = '$sPlantilla' order by orden";	    
	$rs = EjecutarExecAd($con,$sql);

    $sEof = "Si";
    if ($NrodeRegistro != 0){
    	$iNrodeRegistro = $NrodeRegistro;
    }else{
    	$iNrodeRegistro = 20;
    }

    $sNombreTabla = trim($NombreTabla);
?>

<title><?php print($Plantilla) ?></title>
<?php 
	$bprimeratabla = true; 
	//$row = ObtenerFetch($rs);
	
	$Plantilla  = ObtenerResultado($rs,1);
?>
<div id="divInfo" class="print">
<table border="0" width="100%">
  	<tr> 
        <td width="730" align="center" height="15" class="tituloPaginaContabilidad">
        	<?php  print("REPORTE DE ". strtoupper(trim($Plantilla))); ?>
        </td>
  	</tr>
</table>

<table width="100%">
	<tr><br/>
        <td>
        	<fieldset>
            <legend class="legend">Generar Reporte
            	</legend>
            <table border="0" align="center">
            
<?php
	$iFila = -1;
	
	while($row = ObtenerFetch($rs)){
		$iFila++;
		$Plantilla  = ObtenerResultado($rs,1,$iFila);
		$Longitud = ObtenerResultado($rs,2,$iFila);
		$TitulodelCampo = ObtenerResultado($rs,3,$iFila);
		$Objeto= ObtenerResultado($rs,4,$iFila);
		$CamposRelacionados= ObtenerResultado($rs,5,$iFila);
		$NombreTabla= ObtenerResultado($rs,6,$iFila);
		$TipoLinea= ObtenerResultado($rs,7,$iFila);
		$TipoLogico= ObtenerResultado($rs,8,$iFila);
		$Busqueda= trim(ObtenerResultado($rs,9,$iFila));
	
		if($Busqueda == 'SI') { 
			$sNombreCampo =  trim($Nombrecolumna);
			$sNombreCampo1 = 'cDesde' .$iFila;
			$sNombreCampo2 = 'cHasta' .$iFila;
			$sAcom = '';
			$sAste = '';
	
			if (trim($TipoLinea) == "Normal"){
?>
                    <tr> 
<?php
				if((trim($TitulodelCampo))<> '') {
?>
                        <td class="tituloCampo" width="140" align="right">  
							<?php print(trim($TitulodelCampo));?>:
                        </td>
<?php
				}else{
?>
						<td width="140" align="right" bgcolor="#FFFFFF" bordercolor="#FFFFFF">&nbsp;
                        	
                        </td>
<?php 
				/*****************************************************************************
				*****************************************************************************
				/* solo para los numericos y enteros
				*****************************************************************************
				******************************************************************************/
				}
?>
                
	
<?php 	
				if (trim($Objeto) == 'Numerico' or trim($Objeto) == 'Entero'){ 
?>
						<td align="left"> 
        
<?php 	
					if (trim($Objeto) == 'Numerico'){ 
?>
<?php 		
						if (trim($sNombreCampo1 == '')) { 
?> 
							<input  type="text" size="24" maxlength=<?php print(trim(strval($Longitud))); ?> align="right"  size=<?php print(trim(strval($Longitud + 10))); ?> onkeyup=fn(this.form,this,event,'') name=<?php print(trim($sNombreCampo1)); ?> onKeyPress="return CheckNumericJ(event)" class="cNum" onblur=<?php print("this.value=Format(this.value," .trim($NumeroDecimales))?> value="0">

<?php 
						}else {
?>
        					<input  type="text" size="24" maxlength=<?php print(trim(strval($Longitud))); ?> align="right" size=<?php print(trim(strval($Longitud + 10))); ?> onkeyup=fn(this.form,this,event,'') name=<?php print(trim($sNombreCampo1)); ?> onKeyPress="return CheckNumericJ(event)" class="cNum" onblur=<?php print("this.value=Format(this.value," .trim($NumeroDecimales)) ?> value="0">

<?php 		
						}
					}else{ 
						if (trim($NombreCampo1) == '') {
?> 
							<input  type="text" size="24" maxlength=<?php print(trim(strval($Longitud))); ?> align="right" size=<?php print(trim(strval($Longitud + 10))); ?> onkeyup=fn(this.form,this,event,'') name=<?php print(trim($sNombreCampo1)); ?> onKeyPress="return CheckNumericJ(event)" class="cNum" value="0">

<?php   	
						}else{	
?>
							<input  type="text" size="24" maxlength=<?php print(trim(strval($Longitud))); ?> align="right" size=<?php print(trim(strval($Longitud + 10))); ?> onkeyup=fn(this.form,this,event,'') name=<?php print(trim($sNombreCampo1)); ?> onKeyPress="return CheckNumericJ(event)" class="cNum" value="0">                   

<?php
						}
					} 
?>
						</td>	  

<?php 	
					if (trim($TipoLogico) == 'Between'){ 
?>	  
						<td align="left" > 

<?php
						if (trim($Objeto) == 'Numerico'){ 
							if (trim($sNombreCampo2) == '') {
?> 
	    					<input  type="text" size="24" maxlength=<?php print(trim(strval($Longitud))); ?> align="right" size=<?php print(trim(strval($Longitud + 10))); ?> onkeyup=fn(this.form,this,event,'') name=<?php print(trim($sNombreCampo2)); ?> onKeyPress="return CheckNumericJ(event)" class="cNum" onblur=<?php print("this.value=Format(this.value," .trim($NumeroDecimales). ");"); ?> value="0">
   			
<?php
				    		}else{
?>
         					<input  type="text" size="24" maxlength=<?php print(trim(strval($Longitud))); ?> align="right" size=<?php print(trim(strval($Longitud + 10))); ?> onkeyup=fn(this.form,this,event,'') name=<?php print(trim($sNombreCampo2)); ?> onKeyPress="return CheckNumericJ(event)" class="cNum" onblur=<?php print("this.value=Format(this.value," .trim($NumeroDecimales)); ?> value="0">

<?php 	 		
							} 
						}else{ 
							if (trim($sNombreCampo2) == ''){ 
?> 
 	        				<input  type="text" size="24" maxlength=<?php print(trim(strval($Longitud))); ?> align="right" size=<?php print(trim(strval($Longitud + 10))); ?> onkeyup=fn(this.form,this,event,'') name=<?php print(trim($sNombreCampo2)); ?> onKeyPress="return CheckNumericJ(event)" class="cNum" value="0">

<?php 		
							}else{ 
?> 		  									  
			<input  type="text" size="24" maxlength=<?php print(trim(strval($Longitud))); ?> align="right" size=<?php print(trim(strval($Longitud + 10))); ?> onkeyup=fn(this.form,this,event,'') name=<?php print(trim($sNombreCampo2)); ?> onKeyPress="return CheckNumericJ(event)" class="cNum" value="0">

<?php   	
							}
						} 
?>
						</td>	  	  
<?php 
					}
				} 
  				/*****************************************************************************
  				*****************************************************************************
  				/* solo para combo con valores
  				*****************************************************************************
  				*****************************************************************************
  				*/
 
				if (trim($Objeto) == 'ComboValores' ){ 
?>
						<td align="left"> 
        					<select onkeyup=fn(this.form,this,event,'') name="<?php print(trim($sNombreCampo1)) ?>"  class="cTexBox">

<?	
					$sMonto = trim($CamposRelacionados);
					$Monto = '';
					$Num = strlen(trim($CamposRelacionados));
					for ($L = 0; $L <= $Num; $L++){		
						if (substr($sMonto, $L, 1) == ','){ 
?>
								<option value="<?php print($Monto); ?>">
									<?php print($Monto); ?>
                                </option>
				
<?php 
							$Monto = ''; 
						}else{
							$Monto.= substr($sMonto, $L, 1);
						}
					}
?>

                            </select>
                        </td>				  
<?php 
					if($TipoLogico == 'Between'){ 
?>	  
                        <td align="left"> 
                        	<select onkeyup=fn(this.form,this,event,'') name="<?php print(trim($sNombreCampo2)); ?>"  class="cTexBox">

<?php 
						$sMonto = trim($CamposRelacionados);
						$Monto = '';
						$Num = strlen(trim($CamposRelacionados));
						
						for ($L = 0; $L <= $Num; $L++) {
							if (substr($sMonto, $L, 1) == ','){
?>
								<option value=" <?php print($Monto); ?>">
									<?php print($Monto); ?>
                                </option>

<?php  				
								$Monto = '';
							}else{
								$Monto.= substr($sMonto, $L, 1);
							}
						}
?>
                            </select>
                        </td>				  
<?php 
					} 
				} 
			if(trim($Objeto) == 'Combo'){  
?>
                        <td align="left"> 
                            <select  onkeyup=fn(this.form,this,event,'') name="<?php print(trim($sNombreCampo1)); ?>" class="cTexBox">

<?php  
				$sCampos = trim($CamposRelacionados);
				$sTablaRelacionada = trim($TablaRelacionada);
				
				$sql = "SELECT "  .$sCampos. " FROM " .$sTablaRelacionada;
				$rs2 = EjecutarExecAd($con,$sql);
				
				exit;
				while($row = ObtenerFetch($rs2)){
?>
								<option value=<?php print(ObtenerResultado($rs2,1)); ?>><?php print(ObtenerResultado($rs2,2)); ?>
                                </option>

<?php 		
    			} 
?>
							</select>
                       	</td>
      		
<?php 
				if (trim($TipoLogico) == 'Between'){ 
?>	  
                        <td align="left"> 
                        	<select onkeyup=fn(this.form,this,event,'') name="<?php trim($sNombreCampo2) ?>" class="cTexBox">
					
<?php 
					while ($row = ObtenerFetch($rs2)){ 
?>
								<option value=<?php print(ObtenerResultado($rs2,2)); ?>><?php print(ObtenerResultado($rs2,1)); ?></option>

<?php 
					}
?>
                            </select>
                        </td>

<?php 
				} 
			} 
			/*****************************************************************************
			/* solo para los Textos
			*****************************************************************************
			*/
			
			if(trim($Objeto) == "Texto"){ 
?>
                        <td align="left"> 
                            <input type="text" maxlength=<?php print(trim(strval($Longitud))); ?> class="cTexBox" size="42" onkeyup=fn(this.form,this,event,'') name=<?PHP print(trim($sNombreCampo1)); ?> value=""> 
                        </td>
     	
<?php 
				if(trim($TipoLogico) == "Between"){ 
?>	  
                        <td align="left"> 
                            <input type="text" maxlength=<?php print(trim(strval($Longitud))); ?> class="cTexBox" size="42" onkeyup=fn(this.form,this,event,'') name=<?php print(trim($sNombreCampo2)); ?> value=""> 
                        </td>
<?php 
				} 
			} 
?>

<!--***************************************************solo para las Fechas*********************************************************-->

<?php 
			if (trim($Objeto) == "Fecha"){ 
				$xDia =obFecha($_SESSION["sFec_Proceso"],'D');
				$xMes =obFecha($_SESSION["sFec_Proceso"],'M');
				$xAno =obFecha($_SESSION["sFec_Proceso"],'A');
				$Dia1 = '01';
				$Mes1 = $xMes;
				$Year1 = $xAno;
				$Dia2 = $xDia;
				$Mes2 = $xMes;
				$Year2 = $xAno;					 
?>
                        <td align="left">Desde:
                            <input type="text" id=<?php print("xFec". trim($sNombreCampo1))?> name=<?php print("xFec". trim($sNombreCampo1))?> autocomplete="off" size="10" style="text-align:center"/>
						</td>
		
<?php 
				if(trim($TipoLogico) == "Between"){ 
?>	  
                        <td align="left">Hasta:                            
                            <input type="text" id=<?php print("xFec". trim($sNombreCampo2))?> name=<?php print("xFec". trim($sNombreCampo2))?> autocomplete="off" size="10" style="text-align:center"/>
                        </td>

<?php 
				} 
			}
?>

<!--***********************************************solo para Tablas con valores*****************************************************-->

<?php 
			if(trim($Objeto) == 'TablaValores' ){ 
?>
						<td align="left"> 

<? 	
				$sMonto = trim($CamposRelacionados);
				$Monto = "";
				$Num = strlen(trim($CamposRelacionados));
				$bPrimera = true;
				$bPrimeraCam = true;
				$sClaveCon = "";
				
				for ($L = 0; $L <= $Num; $L++){
					if (substr($sMonto, $L, 1) == ','){
						if ($bPrimera){
							$Arretabla[0][0]= $Monto;
							$Arretabla[0][1]= 'T';
							$IArr = 0;
							$bPrimera = false;
							$sTabla = $Monto;
						}else{
							$IArr++;
							$Arretabla[$IArr][0]= $Monto;
							$Arretabla[$IArr][1]= 'C';
							if ($bPrimeraCam){
								$bPrimeraCam = false;
								$sClaveCon = $Monto; 
							}
						}
						$Monto = '';    
					}else{
		  				$Monto.= substr($sMonto, $L, 1);
					}
				}
				
				//para el ultimo del campo
				$IArr++;
				$Arretabla[$IArr][0]= $Monto;
				$Arretabla[$IArr][1]= 'C';
				//fin para el ultimo del campo
				$IArr++;
				$Arretabla[$IArr][0]= $sNombreCampo1;
				$Arretabla[$IArr][1]= 'O';
				$IArr++;
				$Arretabla[$IArr][0]= 'DescD'. $sTabla;
				$Arretabla[$IArr][1]= 'O';
				$IArr++;
				$Arretabla[$IArr][0]= 'PlantillaBuscarParametros';
				$Arretabla[$IArr][1]= 'P';
				$Arre = array_envia($Arretabla);
?>
															
                            <input type="text"  onDblClick="<?php print("AbrirBus(this.name,'$Arre')");?>"  class="cTexBox" size="5" onkeyup=fn(this.form,this,event,'')  onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre')");?>" name=<?PHP print(trim($sNombreCampo1)); ?> value=""> 	
                            <input type="text"  disabled class="cTexBox" size="28" onkeyup=fn(this.form,this,event,'') name=<?php print(trim('DescD'.$sTabla)); ?> value=""> 
                                            
                        </td>				  

<?php  
				if($TipoLogico == 'Between'){ 
?>	
						<td align="left">   
		
<? 			
					$sMonto = trim($CamposRelacionados);
					$Monto = "";
					$Num = strlen(trim($CamposRelacionados));
					$bPrimera = true;
					$bPrimeraCam = true;
					$sClaveCon = "";
					for ($L = 0; $L <= $Num; $L++){
						if (substr($sMonto, $L, 1) == ','){
							if ($bPrimera){
								$Arretabla[0][0]= $Monto;
								$Arretabla[0][1]= 'T';
								$IArr = 0;
								$bPrimera = false;
								$sTabla = $Monto;
							}else{
								$IArr++;
								$Arretabla[$IArr][0]= $Monto;
								$Arretabla[$IArr][1]= 'C';
								if ($bPrimeraCam){
									$bPrimeraCam = false;
									$sClaveCon = $Monto; 
								}
							}
							$Monto = ''; 
						}else{
							$Monto.= substr($sMonto, $L, 1);
	        			}
					}
			//para el ultimo del campo
					$IArr++;
					$Arretabla[$IArr][0]= $Monto;
					$Arretabla[$IArr][1]= 'C';
			//fin para el ultimo del campo
					$IArr++;
					$Arretabla[$IArr][0]= $sNombreCampo2;
					$Arretabla[$IArr][1]= 'O';
					$IArr++;
					$Arretabla[$IArr][0]= 'DescH'. $sTabla;
					$Arretabla[$IArr][1]= 'O';
					$IArr++;
					$Arretabla[$IArr][0]= 'PlantillaBuscarParametros';
					$Arretabla[$IArr][1]= 'P';
					$Arre = array_envia($Arretabla);
?>															
                            <input type="text" onChange="Titu(this);" onDblClick="<?php print("AbrirBus(this.name,'$Arre')");?>"  class="cTexBox" size="5" onkeyup=fn(this.form,this,event,'')  onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre')");?>" name=<?PHP print(trim($sNombreCampo2)); ?> value=""> 	
                            <input type="text"  disabled class="cTexBox" size="28" onkeyup=fn(this.form,this,event,'') name=<?PHP print(trim('DescH'.$sTabla)); ?> value=""> 	  
                        </td>					     
<?php 
				} 
?>	  				  
					</tr>
<?php          
			}
		}
	}
}
?>

<!--solo para un espacio en blanco-->
                    <tr> 
                        <td width="101" height="15">
                            <p> </p>
                        </td>
                    </tr>
<!--Fin solo para un espacio en blanco--> 
				</table>
             </fieldset>
             </td>
		</tr>
</table>

<table width="100%">			  
<!--solo para un espacio en blanco-->
	<tr> 
		<td align="right"><hr/> 
			 <button id="BtnAceptar1" type="submit"  align="middle" onkeyup=fn(this.form,this,event,'') name="BtnAceptar1" value="PDF" onClick = "<?php print("BuscarJ('$NombreTabla','$iFila','P');")?>"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button>
             
<?php 
 	if($NombreTabla <> 'RepCatalogodeCuentas.php'){
?>
			 <button id="BtnAceptar2" type="submit"  align="middle" onkeyup=fn(this.form,this,event,'') name="BtnAceptar2" value="EXCEL" onClick = "<?php print("BuscarJ('$NombreTabla','$iFila','E');")?>"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
             
<?php
	}else{
?>
             <button id="BtnAceptar2" type="submit"  align="middle" onkeyup=fn(this.form,this,event,'') name="BtnAceptar2" value="EXCEL" onClick = "<?php print("BuscarJ('RepCatalogodeCuentasXLS.php','$iFila','E');")?>"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
             
<?php
	}
?>
             
		</td>
	</tr>
<!--Fin solo para un espacio en blanco--> 
</table>

<input type="hidden" name="TexOcultoStatus">
<!--solo para un espacio en blanco-->

<table>
	<tr> 
		<td width="101" height="15">
			<p> </p>
		</td>
	</tr>
<!--Fin solo para un espacio en blanco--> 
</table>
              <!--*************************************************************************************************-->              <!--*********************************esto se coloco para las busuqedas de tablas*********************-->              <!--*************************************************************************************************-->  				 
	<input type="hidden" name="TAValores"> 
	<input type="hidden" name="TACondicion"> 
			
</form>
</div>




<div class="noprint">
<?php include("pie_pagina.php"); ?>
</div>

</body>
</html>

<script>

<?php //
	if(($NombreTabla <> 'RepMayorAnalitico.php')&&($NombreTabla <> 'RepGananciaNiveles.php')&&($NombreTabla <> 'RepBalanceNiveles.php')&&($NombreTabla <> 'RepBalanceGeneral.php')&&($NombreTabla <> 'RepComprobantesDiariosINAVI.php')){					
?>

byId('xFeccDesde1').value = "<?php echo date(str_replace("d","01",spanDateFormat)); ?>";
byId('xFeccHasta1').value = "<?php echo date(spanDateFormat)?>";

window.onload = function(){
	jQuery(function($){
	   $("#xFeccDesde1").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	   	   
	   $("#xFeccDesde1").val("<?php echo $Dia1."-".$Mes1."-".$Year1 ?>");	   	   
	   
	   $("#xFeccHasta1").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	   
	   $("#xFeccHasta1").val("<?php echo $Dia2."-".$Mes2."-".$Year2 ?>");
	});

 	
	new JsDatePick({
		useMode:2,
		target:"xFeccDesde1",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"aqua"
	});
	
	new JsDatePick({
		useMode:2,
		target:"xFeccHasta1",		
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"aqua"
	});
};

<?php 
	}
	if(($NombreTabla == 'RepMayorAnalitico.php')||(($NombreTabla == 'RepComprobantesDiariosINAVI.php'))){
?>
byId('xFeccDesde2').value = "<?php echo date(str_replace("d","01",spanDateFormat)); ?>";
byId('xFeccHasta2').value = "<?php echo date(spanDateFormat)?>";

window.onload = function(){
	jQuery(function($){
	   $("#xFeccDesde2").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	   	   
	   $("#xFeccDesde2").val("<?php echo $Dia1."-".$Mes1."-".$Year1 ?>");	   	   
	 	  
	   $("#xFeccHasta2").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	   
	   $("#xFeccHasta2").val("<?php echo $Dia2."-".$Mes2."-".$Year2 ?>");
	});

 	
	new JsDatePick({
		useMode:2,
		target:"xFeccDesde2",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"aqua"
	});
	
	new JsDatePick({
		useMode:2,
		target:"xFeccHasta2",		
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"aqua"
	});
};

<?php	
	}
	if(($NombreTabla == 'RepGananciaNiveles.php')||($NombreTabla == 'RepBalanceNiveles.php')||($NombreTabla == 'RepBalanceGeneral.php')){
	
?>

byId('xFeccDesde1').value = "<?php echo date(str_replace("d","01",spanDateFormat)); ?>";

window.onload = function(){
	jQuery(function($){
	   $("#xFeccDesde1").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	   	   
	   $("#xFeccDesde1").val("<?php echo $Dia1."-".$Mes1."-".$Year1 ?>");	   	   
	   	  
	});

 	
	new JsDatePick({
		useMode:2,
		target:"xFeccDesde1",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"aqua"
	});
	
};

<?php
	}
?>

</script>
