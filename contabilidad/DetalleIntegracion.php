<?php session_start();?>

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
    
<?php
include_once('FuncionesPHP.php');
$conAd = ConectarBD();
$idModulo = $_REQUEST['pidModulo'];
$sucursal = $_REQUEST['sucursal'];
 
$SqlStr = "SELECT a.tabla,a.campo1,a.campo2,a.cuentageneral,a.sentencia,a.condicion FROM encabezadointegracion a
WHERE a.id = $idModulo";
$exc = EjecutarExec($conAd,$SqlStr) or die($SqlStr);
$row=ObtenerFetch($exc);
$ipos = strpos("x".$row[0],"sipre_automotriz");
if ($ipos != 0){
	$tablaDes = $_SESSION["bdEmpresa"].".".substr(trim($row[0]),17);
}else{
    $tablaDes = $row[0];
}
if($row[5] != '' && !is_null($row[5])){
   $tablaDes.= " ".$row[5];
}


$Campo1 = $row[1];
$Campo2 = $row[2];
$Cuenta = $row[3];
$etiqueta = $row[4];

$SqlStr = "SELECT cuentaGeneral FROM encintegracionsucursal a
WHERE a.sucursal = $sucursal AND id_enc_integracion = $idModulo";
$exc = EjecutarExec($conAd,$SqlStr) or die($SqlStr);
if (NumeroFilas($exc)>0){
	$row = ObtenerFetch($exc);
	$Cuenta = $row[0];
} else {
	$Cuenta = '';
}

$SqlStr = "SELECT descripcion FROM cuenta WHERE codigo = '$Cuenta'";
$exc = EjecutarExec($conAd,$SqlStr) or die($SqlStr);
$row=ObtenerFetch($exc);
$DesCuenta = $row[0];



$sClaveCon = 'codigo'; // Campo Clave para buscar
$Arretabla1[0][0]= 'cuenta'; //Tabla
$Arretabla1[0][1]= 'T';
$Arretabla1[1][0]= 'codigo'; //Campo1
$Arretabla1[1][1]= 'C';
$Arretabla1[2][0]= 'descripcion'; //Campo2
$Arretabla1[2][1]= 'C';
$Arretabla1[3][0]= 'TcodigoG'; //objeto Campo1
$Arretabla1[3][1]= 'O';
$Arretabla1[4][0]= 'TDescripcionG'; //objeto Campo2
$Arretabla1[4][1]= 'O';
$Arretabla1[5][0]= 'frmIntegracionContable'; // Pantalla donde estamos ubicados
$Arretabla1[5][1]= 'P';
$Arre1 = array_envia($Arretabla1); // Serializar Array

$sClaveCon = 'codigo'; // Campo Clave para buscar
$Arretabla1[0][0]= 'cuenta'; //Tabla
$Arretabla1[0][1]= 'T';
$Arretabla1[1][0]= 'codigo'; //Campo1
$Arretabla1[1][1]= 'C';
$Arretabla1[2][0]= 'descripcion'; //Campo2
$Arretabla1[2][1]= 'C';
$Arretabla1[3][0]= 'TcodigoD'; //objeto Campo1
$Arretabla1[3][1]= 'O';
$Arretabla1[4][0]= 'TDescripcionD'; //objeto Campo2
$Arretabla1[4][1]= 'O';
$Arretabla1[5][0]= 'frmIntegracionContable'; // Pantalla donde estamos ubicados
$Arretabla1[5][1]= 'P';
$Arre2 = array_envia($Arretabla1); // Serializar Array

$sClaveCon1 = $Campo1; // Campo Clave para buscar
$Arretabla1[0][0]= $tablaDes; //Tabla
$Arretabla1[0][1]= 'T';
$Arretabla1[1][0]= $Campo1; //Campo1
$Arretabla1[1][1]= 'C';
$Arretabla1[2][0]= $Campo2; //Campo2
$Arretabla1[2][1]= 'C';
$Arretabla1[3][0]= 'TcodigoO'; //objeto Campo1
$Arretabla1[3][1]= 'O';
$Arretabla1[4][0]= 'TDescripcionO'; //objeto Campo2
$Arretabla1[4][1]= 'O';
$Arretabla1[5][0]= 'frmIntegracionContable'; // Pantalla donde estamos ubicados
$Arretabla1[5][1]= 'P';
$Arre3 = array_envia($Arretabla1); // Serializar Array?>


<table  width='100%'  name='mitabla'  border='0'  align='center'>
	<tr>
		<td class="tituloCampo" width="140" align="right">Cuenta General:</td>
        <td align="left"><input  onDblClick="<?php print("AbrirBus(this.name,'$Arre1')");?>"  onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre1')");?>" name="TcodigoG" type="text" maxlength=80 size=10  onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'TcodigoG')" value="<?=$Cuenta?>" class="cTexBox">
        <input readonly name="TDescripcionG" type="text" size=45 class="cTexBoxdisabled" value="<?=$DesCuenta?>">
		<button type="button" name="btnGuardar" value="Guardar" onclick="GuardarGeneral();">Guardar</button>
      	</td>
	</tr>
 	<tr>
  		<td colspan=3></td>
	</tr>
</table>

<table  width='100%'  name='mitabla'  border='0'  align='center'>
	<tr>
		<td class="tituloCampo" width="140" align="right"><?=$etiqueta?>:</td>
        <td align="left">
        	<input  onDblClick="<?php print("AbrirBus(this.name,'$Arre3')");?>"  onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon1','$Arre3')");?>" name="TcodigoO" type="text" maxlength=80 size=5  onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'TcodigoO')" value="<?=$TcodigoO?>" class="cTexBox">
        	<input readonly name="TDescripcionO" type="text" size=30 class="cTexBoxdisabled" value="<?=$TDescripcionG?>">
      	</td>
      	<td  class="tituloCampo" width="140" align="right">Cuenta Contable:</td>
        <td align="left">
        	<input  onDblClick="<?php print("AbrirBus(this.name,'$Arre2')");?>"  onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre2')");?>" name="TcodigoD" type="text" maxlength=80 size=10  onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'TcodigoD')" value="<?=$TcodigoD?>" class="cTexBox">
        	<input readonly name="TDescripcionD" type="text" size=40 class="cTexBoxdisabled" value="<?=$TDescripcionG?>">
			<button type="button" name="btnAdd" value="+" onclick="clickBoton('<?=$tablaDes?>','<?=$Campo1?>','<?=$Campo2?>');">+</button>
      	</td>	  
	</tr>
</table>
<p>
<input type="hidden" name="hdntabla" value=<?=$tablaDes?>>
<input type="hidden" name="hdnc1" value=<?=$Campo1?>>
<input type="hidden" name="hdnc2" value=<?=$Campo2?>>

<table width='100%' name='mitabla' border='0' class="texto_11px">        	
	<tr class="tituloColumna" align="center">
		<td  width='6%'>Id
		</td>
		<td  width='24%'>Descripci&oacute;n
		</td>
		<td  width='17%'>Cuenta Contable
		</td>
		<td  width='40%'>Descripci&oacute;n Cuenta
		 </td>
		<td  width='13%'>Acci&oacute;n</td>
	</tr>
</table>	
<table width='100%' name='mitabla' border='0' class="texto_11px">
	<tr align="center">
		<td>
<iframe name="FrameDetalle" frameborder="0" width="100%" height="350" marginheight="2" marginwidth="2" scrolling="yes" allowtransparency="yes" style="border: #DBE2ED 0px solid;" id="cboxmain1" align="left"></iframe>
		</td>
	</tr>
</table>
<table  width='100%'  name='mitabla'  border='0'  align='left'>
	<tr>
		<td class="tituloCampo">
			<?php
				echo "Acci&oacute;n Seleccionada: ".$idModulo;
			?>
		</td>
	</tr>
</table>