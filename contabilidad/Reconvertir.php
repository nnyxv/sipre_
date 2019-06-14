<?php require "formato.inc.php";
   	$tipo_conv="bsf";	
	$_REQUEST["dValor"] = str_replace(",","",$_REQUEST["dValor"] );
	$monto_Rec_bf=cvMoneda($_REQUEST["dValor"], true, 2, $tipo_conv, false);
	echo $_REQUEST["sNombreObj"];
	
if ($_REQUEST["sNombreObj"] == "oDebe"){
?>
<script language="javascript">
 parent.mainFrame.document.frmDiarios.oDebe.value = '<?= number_format($monto_Rec_bf,2) ?>' 
 parent.mainFrame.document.frmDiarios.oDT.focus();
</script>
<?php
}else{
?>
<script language="javascript">
 parent.mainFrame.document.frmDiarios.oHaber.value = '<?=  number_format($monto_Rec_bf,2) ?>'
 parent.mainFrame.document.frmDiarios.oDT.focus();
</script>
<?php
}
?>
