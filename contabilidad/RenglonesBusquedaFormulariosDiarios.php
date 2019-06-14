<?php session_start(); ?>
<script language="JavaScript" src="./GlobalUtility.js">
</script>

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
<link rel="stylesheet" type="text/css" href="../js/domDragContabilidad.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src=a
	"../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>

<script language="JavaScript">

function jElegir(sCodigoBuscar,sDesBuscar,sObjeto1,sObjeto2,sPlantillaBus,sFecha,sCC){

   ss= "parent.opener.document."+ sPlantillaBus +".Buscar";
   eval("parent.opener.document."+sPlantillaBus+"."+sObjeto1+".value='" +sCodigoBuscar+"'");
   eval("parent.opener.document."+sPlantillaBus+".xDFecha.value='" +sFecha.substring(0,2)+"'");
   eval("parent.opener.document."+sPlantillaBus+".xMFecha.value='" +sFecha.substring(5,3)+"'");
   eval("parent.opener.document."+sPlantillaBus+".xAFecha.value='" +sFecha.substring(10,6)+"'");
   eval("parent.opener.document."+sPlantillaBus+".oCC.value='" +sCC+"'");

   
   eval("parent.opener.document."+sPlantillaBus+"."+sObjeto1+".focus()");
   eval("parent.opener.document."+sPlantillaBus+"."+sObjeto2+".value='" +sDesBuscar+"'");
   parent.opener.Buscar();

   parent.close();
}
function SobreFila(sDesCuen,obj){
//		if (document.ConfiguracionReportes.sDesactivarColor.value != "SI"){
			//	if (parent.document.frmDiarios.oNumero.value != ""){
	soCon = obj.id;
	soCon = soCon.toString();
	var objFila = document.all(soCon);
	objFila.bgColor='white';
	objFila.color='Black';
			//	}	
				  
	obj.style.color='white';
	obj.bgColor='#000066';
			//		parent.document.frmDiarios.oDesCuenta.value=sDesCuen;
		//}
}

function FueraFila(sDesCuen,obj){
		//if (document.ConfiguracionReportes.sDesactivarColor.value != "SI"){
	obj.style.color='Black';
	obj.bgColor='white';
         //	    parent.document.frmDiarios.oDesCuenta.value=sDesCuen;
		//}		
}
</script>

<?php
include_once('FuncionesPHP.php');
$con = ConectarBD(); 
$sTabla = "";
$sCampos= "";
$sPlantillaBus="";
$ArrRec =array_recibe($TAValores);
$bprimera=true;
$bprimeraOb=true; 
$NumEle = count($ArrRec);
for ($i = 0; $i <= $NumEle; $i++){
    if ($ArrRec[$i][1] == 'T'){
       $sTabla = $ArrRec[$i][0];	
	}
	if ($ArrRec[$i][1] == 'P'){
       $sPlantillaBus = $ArrRec[$i][0];	
	}
	if ($ArrRec[$i][1] == 'O'){
    	if ($bprimeraOb){
        	$sObjeto1 = $ArrRec[$i][0];	
			$bprimeraOb=false;
	  	}else{
    		$sObjeto2 = $ArrRec[$i][0];	
	  	} 
   	}
	if ($ArrRec[$i][1] == 'C'){
		if ($bprimera){
			 $pClave = $ArrRec[$i][0];
			 $bprimera = false;
		}else{
			 $pDescripcion = $ArrRec[$i][0];
		}
   		$sCampos.= $ArrRec[$i][0].',';	
	}
}

$sCampos= "comprobant,concepto,fecha,cc";
$sTablaSelec = "enc_diario";
$sCondicion = " ";

if ($TexCodigoBus != ''){
	$sCondicion.="comprobant " . " LIKE  '" . Trim($TexCodigoBus) . "%'";
}//else{
   //}
if ($TexDescripcionBus != ''){
	if ($sCondicion != ""){
           $sCondicion.= ' and ';
    }
    $sCondicion.= " concepto " . " LIKE '%" . Trim($TexDescripcionBus) . "%'";
}

if ($TexFecha != ''){
	if ($sCondicion != ""){
		$sCondicion.= ' and ';
    }
    $sCondicion.= " fecha = '" . RobFecha($TexFecha) ."'";
}

if(substr(trim($_SESSION["CCSistema"]),0,1) == "E"){
	if ($sCondicion != ""){
	   $sCondicion.= ' and ';
	}
	$sCondicion.= "   cc = '" . Trim($_SESSION["CCSistema"]) . "'"; //*1*
}else {              
	if ($TexCC != ''){
		if ($sCondicion != ""){
			$sCondicion.= ' and ';
		}
		$sCondicion.= "  cc LIKE '%" . Trim($TexCC) . "%'";
	}                
}

$sql = "Select " .$sCampos. " from ". $sTabla . " where " . $sCondicion  ;
$rs3 = EjecutarExec($con,$sql) or die($sql); 

?>
<form name="RenglonesBuscarFormularios" style="margin:0">
<table width="100%"  name="mitabla"  border="0"  align="center" class="texto_9px">
	<tr class="tituloColumna">
    	<td align="center">Comprobante</td>
        <td align="center">Concepto</td>
        <td align="center">Fecha</td>
        <td align="center">Centro de Costo</td>
    </tr>
<?php
$iFila=-1;
while ($row =  ObtenerFetch($rs3)) {
	$iFila++;
	$CodigoBuscar = trim(ObtenerResultado($rs3,1,$iFila));
	$DesBuscar = trim(ObtenerResultado($rs3,2,$iFila));
	$Fecha = obFecha(trim(ObtenerResultado($rs3,3,$iFila)));
	$CC = trim(ObtenerResultado($rs3,4,$iFila));
?>
	<tr bgColor='white' id=<?php print($iFila);?> onClick="<?php print("jElegir('$CodigoBuscar','$DesBuscar','$sObjeto1','$sObjeto2','$sPlantillaBus','$Fecha','$CC')");?>" style="cursor:pointer"   onMouseOut="FueraFila('',this);" onMouseMove="SobreFila('',this);">
		<td width="20%"  class="RenglonesMov"><?php print($CodigoBuscar);?></font></td>
		<td width="50%"  class="RenglonesMov"><?php print($DesBuscar);?></font></td>
		<td width="15%"  class="RenglonesMov"><?php print($Fecha);?></font></td>
		<td width="15%"  class="RenglonesMov"><?php print($CC);?></font></td>
	</tr>
<?php
}
?>
</table>
</form>
