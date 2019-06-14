<?php session_start();?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<title>.: SIPRE 2.0 :. Contabilidad - Integracion Contable</title>
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


<?php
	include_once('FuncionesPHP.php');
	$conAd = ConectarBD();
	$idEncabezado = $_REQUEST['pidModulo'];
	$idObjeto = $_REQUEST['ptcodigoO'];
	$cuenta = trim($_REQUEST['ptcodigoD']);
	$paccion= $_REQUEST['paccion'];
	$snom_tablaobjeto= $_REQUEST['snom_tablaobjeto'];
	$snom_idobjeto= $_REQUEST['snom_idobjeto'];
	$snom_desobjeto= $_REQUEST['snom_desobjeto'];
	$idRenglon= $_REQUEST['idRenglon'];
	$sucursal= $_REQUEST['sucursal'];
	if($paccion == 'I'){
//**********************************************************************
/*Código PHP Para Realizar el INSERT*/
//**********************************************************************
     	$con = ConectarBD();
		$sTabla="detalleintegracion a ";
		$sCampos = "count(*)";
		$sCondicion=" a.idobjeto = $idObjeto";
		$sCondicion.=" and a.idencabezado = $idEncabezado";
		$sCondicion.=" and a.sucursal = $sucursal";
		
		$SqlStr="Select $sCampos from ".$sTabla. " where $sCondicion" ;
		
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		$row=ObtenerFetch($exc);
		if($row[0]>0){
			echo "<script language='javascript'> alert('Ya existe una asignacion contable')</script>";
		}else{
			$sTabla='detalleintegracion';
			$sValores='';
			$sCampos='';
			$sCampos.='idencabezado';
			$sValores.=$idEncabezado;
			$sCampos.=',idobjeto';
			$sValores.=",$idObjeto";
			$sCampos.=',cuenta';
			$sValores.=",'$cuenta'";
			$sCampos.=',sucursal';
			$sValores.=",'$sucursal'";
			$SqlStr='';
			$SqlStr="INSERT INTO ".$sTabla." (".$sCampos.")  values (".$sValores.")";
        	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
			
			//auditoria
			auditoria('insert',$sTabla,$sCampos,'insert integracion: '.$idEncabezado."/".$idObjeto."/".$cuenta."-".$idRenglon);//accion/modulo/cuenta/id de renglon (tabla detalleintegracion	
			//fin auditoria
		}
		$paccion = 'B';
	}

	if($paccion == 'E'){
//**********************************************************************
/*Código PHP Para Realizar el INSERT*/
//**********************************************************************
    	$con = ConectarBD();
		$SqlStr="delete from detalleintegracion where id = $idRenglon";
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		
		//auditoria
		auditoria('delete',$sTabla,$sCampos,'delete integracion: '.$idRenglon);	
		//fin auditoria
		
		$paccion = 'B';
	}
	
	if ($snom_tablaobjeto == ""){
		return; 
	} 

	if($paccion == 'B'){
//**********************************************************************
/*Código PHP Para Realizar el INSERT*/
//**********************************************************************
        $con = ConectarBD();
        $sTabla="detalleintegracion a,$snom_tablaobjeto b,cuenta c ";
        $sCampos='a.id';
		$sCampos.=",b.$snom_idobjeto";
		$sCampos.=",b.$snom_desobjeto";
        $sCampos.=',a.idencabezado';
        $sCampos.=',a.idobjeto';
		$sCampos.=',a.cuenta';
		$sCampos.=', c.descripcion';
		$sCondicion=" a.idobjeto = b.$snom_idobjeto";
		$sCondicion.=" and a.idencabezado = $idEncabezado";
		$sCondicion.=" and a.cuenta = c.codigo";
		$sCondicion.=" and a.sucursal = $sucursal";
		
        $SqlStr='';
		$SqlStr="Select $sCampos from ".$sTabla. "where $sCondicion" ;				
		
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		echo "<table width='100%' name='mitabla' border='0' align='left'>";
		while ($row=ObtenerFetch($exc)){
		        $id = $row[0];	
				$idob = $row[1];	
				$desob = $row[2];
				$cuenta = $row[5];
				$DesCuenta = $row[6];
				echo "
					<tr>
						<td width='6%' align='center'>
							$idob
						</td>
						<td width='25%' align='left'>
							$desob
						</td>
						<td width='18%' align='left'>
							$cuenta
						</td>
						<td width='40%' align='left'>
							  $DesCuenta
						</td>
						<td width='14%' align='center'>
							<button type='button' value='Eliminar' onclick='parent.EliminarRenglon($id)'>Eliminar</button>
						</td>
					</tr>";
		}
		echo "</table>";
	}
?>
</div>
</body>
</html>