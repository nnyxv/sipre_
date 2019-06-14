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
function jElegir(sCodigoBuscar,sDesBuscar,sObjeto1,sObjeto2,sPlantillaBus){
    eval("parent.opener.document."+sPlantillaBus+"."+sObjeto1+".value='" +sCodigoBuscar+"'");
	eval("parent.opener.document."+sPlantillaBus+"."+sObjeto1+".focus()");
   	eval("parent.opener.document."+sPlantillaBus+"."+sObjeto2+".value='" +sDesBuscar+"'");
   parent.close();
}
function SobreFila(sDesCuen,obj){
//		if (document.ConfiguracionReportes.sDesactivarColor.value != "SI"){
			//	if (parent.document.frmDiarios.oNumero.value != ""){
	soCon = obj.id;
	//alert('soCon');
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
include('FuncionesPHP.php');
$con = ConectarBDAd(); 
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

$sCampos= substr($sCampos,0,strlen(trim($sCampos))-1);

if ($TexCodigoBus == '' && $TexDescripcionBus == ''){
	exit; 
 }

if ($TexCodigoBus != ''){
	$sCondicion.=$pClave . " LIKE  '" . Trim($TexCodigoBus) . "%'";
}//else{
   //}
   
if ($TexDescripcionBus != ''){
	if ($TexCodigoBus != ''){
    	$sCondicion.= ' and ';
    }
    $sCondicion.= $pDescripcion . " LIKE '%" . Trim($TexDescripcionBus) . "%'";
}

$sql = "Select " .$sCampos. " from ". $sTabla . " where " . $sCondicion  ;
$rs3 = EjecutarExecAd($con,$sql); 

?>
<form name="RenglonesBuscar" style="margin:0">
<table width="100%" name="mitabla" border="0" align="center" class="texto_9px">
	<tr class="tituloColumna">
    	<td align="center">C&oacute;digo</td>
        <td align="center">Descripci&oacute;n</td>
    </tr>

<?php
$iFila=-1;
while ($row =  ObtenerFetch($rs3)) {
	$iFila++;
	$CodigoBuscar = trim(ObtenerResultado($rs3,1,$iFila));
	$DesBuscar = trim(ObtenerResultado($rs3,2,$iFila));
 ?>
	<tr bgColor='white' id=<?php print($iFila);?> onClick="<?php print("jElegir('$CodigoBuscar','$DesBuscar','$sObjeto1','$sObjeto2','$sPlantillaBus')");?>" style="cursor:pointer"   onMouseOut="FueraFila('',this);" onMouseMove="SobreFila('',this);" >
		<td width="20%"  class="RenglonesMov"> <?php print($CodigoBuscar);?></td>
		<td width="80%"  class="RenglonesMov"><?php print($DesBuscar);?></td>
	</tr>
<?php
}
?>
</table>
</form>
