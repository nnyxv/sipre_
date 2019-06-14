<?php session_start();
include_once('FuncionesPHP.php');
?>
<html>
<head>

<!--DATOS DE LA FORMA-->

<!--Título: frmcuenta -->

<!--Descripción: Formulario individual-->

<!--Copyright: Copyright (c) Corporación Oriomka, C.A. 2006-->

<!--Empresa: Corporación Oriomka, C.A. www.oriomka.net Telf:(0212)7618494-7627666-->

<!--Autor: Corporación Oriomka, C.A.-->

<!--Autor: Desarrollado por Ernesto Garcia 0416-4197573 / 0414-0106485-->

<!--@version 1.0-->

<title>frmcuenta</title>
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
<!--*****************************************************************************************-->
<!--*************************PANTALLA BUSCAR*************************************************-->
<!--*****************************************************************************************-->
  function PantallaBuscar(sObjeto,oArreglo){
    winOpen('PantallaBuscarFormularios.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,'');
  }// function AbrirBus(sObjeto,oArreglo){


<!--*****************************************************************************************-->
<!--**********************************BUSCAR*************************************************-->
<!--*****************************************************************************************-->
function Buscar(){
document.frmcuenta.target='mainFrame';
document.frmcuenta.method='post';
document.frmcuenta.action='frmcuenta.php';
document.frmcuenta.StatusOculto.value='BU';
document.frmcuenta.submit();
}// function AbrirBus(sObjeto,oArreglo){


<!--*****************************************************************************************-->
<!--**********************************Abrir Ventana de Busqueda******************************-->
<!--*****************************************************************************************-->
  function AbrirBus(sObjeto,oArreglo){
      winOpen('PantallaBuscar.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,'');
  }// function AbrirBus(sObjeto,oArreglo){
function BuscarDescrip(sValor,sCampoBuscar,oArreglo){
    if (Alltrim(sValor) != ''){
document.frmcuenta.TACondicion.value=sCampoBuscar +"= '" + sValor + "'"
document.frmcuenta.TAValores.value=oArreglo;
document.frmcuenta.method='post';
document.frmcuenta.target='topFrame';
document.frmcuenta.action='BusTablaParametros.php';
document.frmcuenta.submit();
 }// if (Alltrim(sValor) != ''){
 }//function BuscarDescrip(sValor,sCampoBuscar,oArreglo){


<!--*****************************************************************************************-->
<!--**********************************SELECCIONAR TEXTO**************************************-->
<!--*****************************************************************************************-->
function SelTexto(obj){
if (obj.length != 0){
obj.select();
}
}//  function SelTexto(obj){
<!--*****************************************************************************************-->
<!--**********************************VALIDAR NUMERICOS**************************************-->
<!--*****************************************************************************************-->
  function validar(obj){
     obj.value = new NumberFormat(obj.value).toFormatted();
  }
<!--*****************************************************************************************-->
<!--**********************************EJECUTAR**************************************-->
<!--*****************************************************************************************-->


function Ejecutar(sStatus){
       document.frmcuenta.target='mainFrame';
       document.frmcuenta.method='post';
       document.frmcuenta.action='frmcuenta.php';
      if (sStatus == "LI"){
        document.frmcuenta.StatusOculto.value = "LI"
        document.frmcuenta.submit();
      }
      if (sStatus == "IN"){
        if (VerificarFechasJ(document.frmcuenta))
        {
           return false;
        }
        if (CamposBlancosJ(document.frmcuenta))
        {
           return false;
        }
        document.frmcuenta.StatusOculto.value = "IN"
        document.frmcuenta.submit();
      }
      else if (sStatus == "UP")
      {
         if (VerificarFechasJ(document.frmcuenta))
         {
            return false;
         }
         if (CamposBlancosJ(document.frmcuenta))
         {
            return false;
         }
         document.frmcuenta.StatusOculto.value = "UP"
         document.frmcuenta.submit();
      }
      else if (sStatus == "DE"){
        if (confirm('Desea Eliminar el registro')){
         document.frmcuenta.StatusOculto.value = "DE"
         document.frmcuenta.submit();
        }
      }
     else if (sStatus == 'BU'){
         if (document.frmcuenta.T_codigo.value== '' || document.frmcuenta.Desha.value == 'readonly'){
           return false;
        }
         document.frmcuenta.StatusOculto.value = "BU"
         document.frmcuenta.submit();
      }
}
</script>
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--*****************************************CODIGO PHP**************************************-->
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<?php
   $Desha = ' class=cTexBox ';


                                   //L I M P I A R
if ($StatusOculto =='LI'){
                $T_codigo='';
                $T_descripcion='';
                $xDult_mov=date('d');
                $xMult_mov=date('m');
                $xAult_mov=date('Y');
                $Nsaldo_ant='0.00';
                $Ndebe='0.00';
                $Nhaber='0.00';
                $Ndebe_cierr='0.00';
                $Nhaber_cierr='0.00';
                $TDeshabilitar='';
                $xDFechaDes=date('d');
                $xMFechaDes=date('m');
                $xAFechaDes=date('Y');
}


                                   //I N S E R T
if ($StatusOculto =='IN')
{
//**********************************************************************
/*Código PHP Para Realizar el INSERT*/
//**********************************************************************
        $con = ConectarBD();
        $sTabla='cuenta';
        $sValores='';
        $sCampos='';
        $sCampos.='codigo';
        $sValores.="'".$T_codigo."'";
        $sCampos.=',descripcion';
        $sValores.=",'".$T_descripcion."'";
        $sCampos.=',ult_mov';
        $sValores.=",'".$xAult_mov. '-' .$xMult_mov. '-' .$xDult_mov."'";
        $sCampos.=',saldo_ant';
        $sValores.=",'".str_replace(',','',$Nsaldo_ant)."'";
        $sCampos.=',debe';
        $sValores.=",'".str_replace(',','',$Ndebe)."'";
        $sCampos.=',haber';
        $sValores.=",'".str_replace(',','',$Nhaber)."'";
        $sCampos.=',debe_cierr';
        $sValores.=",'".str_replace(',','',$Ndebe_cierr)."'";
        $sCampos.=',haber_cierr';
        $sValores.=",'".str_replace(',','',$Nhaber_cierr)."'";
        $sCampos.=',Deshabilitar';
        $sValores.=",'".$TDeshabilitar."'";
        $sCampos.=',FechaDes';
        $sValores.=",'".$xAFechaDes. '-' .$xMFechaDes. '-' .$xDFechaDes."'";
        $SqlStr='';
        $SqlStr="INSERT INTO ".$sTabla." (".$sCampos.")  values (".$sValores.")";
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
        echo "<script language='javascript'> alert('Operación Realizada con éxito')</script>";
}


                                   //U P D A T E
if ($StatusOculto =='UP')
{
//**********************************************************************
/*Código PHP Para Realizar el UPDATE*/
//**********************************************************************
        $con = ConectarBD();
        $sTabla='cuenta';
        $sCampos='';
        $sCondicion='';
        $sCampos.='codigo= '."'".$T_codigo."'";
        $sCondicion.='codigo= '."'".$T_codigo."'";
        $sCampos.=',descripcion= '."'".$T_descripcion."'";
        $sCampos.=',ult_mov= '."'".$xAult_mov. '-' .$xMult_mov. '-' .$xDult_mov."'";
        $sCampos.=',saldo_ant= '."'".str_replace(',','',$Nsaldo_ant)."'";
        $sCampos.=',debe= '."'".str_replace(',','',$Ndebe)."'";
        $sCampos.=',haber= '."'".str_replace(',','',$Nhaber)."'";
        $sCampos.=',debe_cierr= '."'".str_replace(',','',$Ndebe_cierr)."'";
        $sCampos.=',haber_cierr= '."'".str_replace(',','',$Nhaber_cierr)."'";
        $sCampos.=',Deshabilitar= '."'".$TDeshabilitar."'";
        $sCampos.=',FechaDes= '."'".$xAFechaDes. '-' .$xMFechaDes. '-' .$xDFechaDes."'";
        $SqlStr='';
        $SqlStr="UPDATE ".$sTabla." SET ".$sCampos." WHERE ".$sCondicion."";
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
  $Desha = ' readonly class=cTexBoxdisabled';
        echo "<script language='javascript'> alert('Operación Realizada con éxito')</script>";
}


                                   //D E L E T E
if ($StatusOculto =="DE")
{
//**********************************************************************
/*Código PHP Para Realizar el DELETE*/
//**********************************************************************
        $con = ConectarBD();
        $sTabla='cuenta';
        $sCondicion='';
        $sCondicion.='codigo= '."'".$T_codigo."'";
        $SqlStr="DELETE FROM ".$sTabla." WHERE ".$sCondicion."";
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
                $StatusOculto ='LI';
                $T_codigo='';
                $T_descripcion='';
                $xDult_mov=date('d');
                $xMult_mov=date('m');
                $xAult_mov=date('Y');
                $Nsaldo_ant='0.00';
                $Ndebe='0.00';
                $Nhaber='0.00';
                $Ndebe_cierr='0.00';
                $Nhaber_cierr='0.00';
                $TDeshabilitar='';
                $xDFechaDes=date('d');
                $xMFechaDes=date('m');
                $xAFechaDes=date('Y');
}


                                   //B U S C A R
if ($StatusOculto =='BU'){
	    $con = ConectarBD();
        $sTabla='cuenta';
        $sCondicion='';
        $sCampos.='codigo';
        $sCampos.=',descripcion';
        $sCampos.=',ult_mov';
        $sCampos.=',saldo_ant';
        $sCampos.=',debe';
        $sCampos.=',haber';
        $sCampos.=',debe_cierr';
        $sCampos.=',haber_cierr';
        $sCampos.=',Deshabilitar';
        $sCampos.=',FechaDes';
        $sCondicion.='codigo= '."'".$T_codigo."'";
        $SqlStr='Select '.$sCampos.' from '.$sTabla. ' Where ' .$sCondicion;
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
        if ( NumeroFilas($exc)>0){
              $StatusOculto = 'UP';
                $T_codigo=trim(ObtenerResultado($exc,1));
                $T_descripcion=trim(ObtenerResultado($exc,2));
                $xDult_mov=obFecha(ObtenerResultado($exc,3),'D');
                $xMult_mov=obFecha(ObtenerResultado($exc,3),'M');
                $xAult_mov=obFecha(ObtenerResultado($exc,3),'A');
                $Nsaldo_ant=trim(ObtenerResultado($exc,4));
                $Ndebe=trim(ObtenerResultado($exc,5));
                $Nhaber=trim(ObtenerResultado($exc,6));
                $Ndebe_cierr=trim(ObtenerResultado($exc,7));
                $Nhaber_cierr=trim(ObtenerResultado($exc,8));
                $TDeshabilitar=trim(ObtenerResultado($exc,9));
                $xDFechaDes=obFecha(ObtenerResultado($exc,10),'D');
                $xMFechaDes=obFecha(ObtenerResultado($exc,10),'M');
                $xAFechaDes=obFecha(ObtenerResultado($exc,10),'A');
   $Desha = ' readonly  class=cTexBoxdisabled';
       }else{ // if ( NumeroFilas($exc)>0){
                $StatusOculto ='LI';
                $T_descripcion='';
                $xDult_mov=date('d');
                $xMult_mov=date('m');
                $xAult_mov=date('Y');
                $Nsaldo_ant='0.00';
                $Ndebe='0.00';
                $Nhaber='0.00';
                $Ndebe_cierr='0.00';
                $Nhaber_cierr='0.00';
                $TDeshabilitar='';
                $xDFechaDes=date('d');
                $xMFechaDes=date('m');
                $xAFechaDes=date('Y');
       } // if ( NumeroFilas($exc)>0){
}


?>
</p>
<p>&nbsp;</p>
<p>
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--************************************FORMULARIO HTML**************************************-->
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<form name="frmcuenta"action="frmcuenta.php"method="post">
<table width=700 border=1 align=center height=0 cellpadding=0 cellspacing=0 class=Acceso>
   <tr>
   <?php
   if ($StatusOculto =='LI' || $StatusOculto =='' || $StatusOculto =='DE'){
    ?>
   <td  height=20 valign=top  class=cabeceraNegra>Catalogo de Cuenta  (Inclusión)</font></td> 
       <?php }else{
   $Desha = 'readonly class=cTexBoxdisabled';
       ?>
           <td  height=20 valign=top class=cabeceraNegra>Catalogo de Cuenta  (Modificación)</font></td>
    <?php } ?>
    </tr>
 </table>
<table width="700" align="center"cellpadding=0 cellspacing=0 class="Acesso">
   <tr>
       <td  height=20 class=cabecera width="140"valign="top"> <p>*Código</p></td>
       <td height=20 class=cabecera ><input <?= $Desha ?>  onBlur="Ejecutar('BU');" name="T_codigo"type="text"maxlength=80 size="35" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')"" value="<?=$T_codigo?>"> </td>
   </tr>
   <tr>
       <td  height=20 class=cabecera width="140"valign="top"> <p>*Descripción</p></td>
       <td height=20 class=cabecera ><input  name="T_descripcion"type="text"maxlength=100 size="55" onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'TDeshabilitar')" value="<?=$T_descripcion?>" class="cTexBox"> </td>
   </tr>
   <tr>
       <td height=20 class=cabecera width="140"valign="top"> <p>Ult Movimiento</p></td>
   <td height=20 class=cabecera width="100"valign="top">
       <input  name="xDult_mov"type="text"maxlength=2 size=1 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xDult_mov?>" readonly class=" cNumdisabled ">
       <input  name="xMult_mov"type="text"maxlength=2 size=1 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xMult_mov?>" readonly class=" cNumdisabled ">
       <input  name="xAult_mov"type="text"maxlength=4 size=4 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xAult_mov?>" readonly class=" cNumdisabled ">
   </td>
   <tr>
       <td  height=20 class=cabecera width="140" height="3"valign="top"> <p>Saldo Anterior</p></td>
       <td height=20 class=cabecera ><input  name="Nsaldo_ant"type="text"maxlength=20  size=20  onFocus="SelTexto(this);"onblur="validar(this);"onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" value="<?=$Nsaldo_ant?>" readonly class=" cNumdisabled "> </td>
   </tr>
  <?php
    echo"<script language='Javascript'>
          document.frmcuenta.Nsaldo_ant.value=new NumberFormat($Nsaldo_ant).toFormatted();
    </script>"    ?>
    </td>
</tr>
   <tr>
       <td  height=20 class=cabecera width="140" height="3"valign="top"> <p>Debe</p></td>
       <td height=20 class=cabecera ><input  name="Ndebe"type="text"maxlength=20  size=20  onFocus="SelTexto(this);"onblur="validar(this);"onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" value="<?=$Ndebe?>" readonly class=" cNumdisabled "> </td>
   </tr>
  <?php
    echo"<script language='Javascript'>
          document.frmcuenta.Ndebe.value=new NumberFormat($Ndebe).toFormatted();
    </script>"    ?>
    </td>
</tr>
   <tr>
       <td  height=20 class=cabecera width="140" height="3"valign="top"> <p>Haber</p></td>
       <td height=20 class=cabecera ><input  name="Nhaber"type="text"maxlength=20  size=20  onFocus="SelTexto(this);"onblur="validar(this);"onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" value="<?=$Nhaber?>" readonly class=" cNumdisabled "> </td>
   </tr>
  <?php
    echo"<script language='Javascript'>
          document.frmcuenta.Nhaber.value=new NumberFormat($Nhaber).toFormatted();
    </script>"    ?>
    </td>
</tr>
   <tr>
       <td  height=20 class=cabecera width="140" height="3"valign="top"> <p>Debe Cierre</p></td>
       <td height=20 class=cabecera ><input  name="Ndebe_cierr"type="text"maxlength=20  size=20  onFocus="SelTexto(this);"onblur="validar(this);"onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" value="<?=$Ndebe_cierr?>" readonly class=" cNumdisabled "> </td>
   </tr>
  <?php
    echo"<script language='Javascript'>
          document.frmcuenta.Ndebe_cierr.value=new NumberFormat($Ndebe_cierr).toFormatted();
    </script>"    ?>
    </td>
</tr>
   <tr>
       <td  height=20 class=cabecera width="140" height="3"valign="top"> <p>Haber Cierre</p></td>
       <td height=20 class=cabecera ><input  name="Nhaber_cierr"type="text"maxlength=20  size=20  onFocus="SelTexto(this);"onblur="validar(this);"onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" value="<?=$Nhaber_cierr?>" readonly class=" cNumdisabled "> </td>
   </tr>
  <?php
    echo"<script language='Javascript'>
          document.frmcuenta.Nhaber_cierr.value=new NumberFormat($Nhaber_cierr).toFormatted();
    </script>"    ?>
    </td>
</tr>
<tr>
    <td height=20 class=cabecera width="140"valign="top"> <p>Deshabilitar</p></td>
    <td height=20 class=cabecera ><select " name="TDeshabilitar" size "3" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" class="cTexBox">
  <option value=NO>NO</option>
  <option value=SI>SI</option>
    </select>
  <?php
    echo"<script language='Javascript'>
          document.frmcuenta.TDeshabilitar.value='$TDeshabilitar';
    </script>"    ?>
    </td>
</tr>
   <tr>
       <td height=20 class=cabecera width="140"valign="top"> <p>Fecha Deshabilitado</p></td>
   <td height=20 class=cabecera width="100"valign="top">
       <input  name="xDFechaDes"type="text"maxlength=2 size=1 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xDFechaDes?>" class="cNum">
       <input  name="xMFechaDes"type="text"maxlength=2 size=1 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xMFechaDes?>" class="cNum">
       <input  name="xAFechaDes"type="text"maxlength=4 size=4 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xAFechaDes?>" class="cNum">
   </td>
  </table>
      <table  align=center width=700 align=left class=Acceso>
       <tr>
        <?PHP
                $sEjecut= '';
              if ($StatusOculto =='LI' || $StatusOculto =='' || $StatusOculto =='DE'){
                     $sEjecut='IN';
              }else{
              $sEjecut='UP';
                            }?>
           <td align=left width=700 class=cabecera><input name=BtnGuardar type=button value=Guardar onClick="<?php print("Ejecutar('$sEjecut')");?>" class=inputBoton>
<?php $Arretabla2[0][0]= 'cuenta';//Tabla
$Arretabla2[0][1]= 'T';
 $Arretabla2[1][0]= 'codigo'; //Campo1
 $Arretabla2[1][1]= 'C';
 $Arretabla2[2][0]= 'descripcion'; //Campo2
 $Arretabla2[2][1]= 'C';
$Arretabla2[3][0]= 'T_codigo';
$Arretabla2[3][1]= 'O';
$Arretabla2[4][0]= 'T_descripcion';
$Arretabla2[4][1]= 'O';
$Arretabla2[5][0]= 'frmcuenta'; // Pantalla donde estamos ubicados
$Arretabla2[5][1]= 'P';
$ArreGeneral = array_envia($Arretabla2); // Serializar Array
?>
        <input name=BtnBuscar type=button value=Buscar onClick="<?php print("PantallaBuscar('T_Codigo','$ArreGeneral')");?>"Class = inputBoton > 

<?php
 if (!($StatusOculto =='LI' || $StatusOculto =='' || $StatusOculto =='DE')){ ?>
  <input name=BtnIncluir type=button value=Incluir onClick="Ejecutar('LI');"class=inputBoton>
  <input name=BtnEliminar type=button value=Eliminar onClick="Ejecutar('DE');" class=inputBoton>
        <script language='javascript'>
             document.frmcuenta.T_descripcion.focus();
         </script>
              <?php }else{ ?>
			 <?php if ($T_codigo !=  ''){?>
					 <script language='javascript'>
        		               document.frmcuenta.T_descripcion.focus();
                	       </script>
			 <?php }else{?>
                      <script language='javascript'>
                            document.frmcuenta.T_codigo.focus();
                          </script>
              <?php } ?>						  
           <?php } ?>
           </td>
       </tr>
  </table>
  <td class=cabecera><input name=StatusOculto type=hidden value=''>
                      <input name=TACondicion type=hidden value=''>
                      <input name=TAValores type=hidden value=''>
                      <input name=Desha type=hidden value="<?= $Desha ?>">
  </td>
</form>
</body>
</html>
