<?php
//error_reporting(E_ALL);
//ini_set("display_errors", 1); 
 @session_start();
 
include("../connections/conex.php");

/* Validación del Módulo */
include('../inc_sesion.php');

/*if(!(validaAcceso("sa_informe_call_center"))) {
	echo "
	<script>
		alert('Acceso Denegado');
		window.location.href = 'index.php';
	</script>";
*/

/* Fin Validación del Módulo */

@require('../controladores/xajax/xajax_core/xajax.inc.php'); //servidor
			   $xajax = new xajax();                           
                           $xajax->registerFunction("buscar");
                           $xajax->registerFunction("buscar_ordenes");
			$xajax->processRequest();  

function buscar($valForm) {
    $objResponse = new xajaxResponse();
    
    $fecha1 = $valForm['txtFechaDesde'];
    $fecha2 = $valForm['txtFechaHasta'];
    
    $anio1 = date("Y",strtotime($fecha1));
    $anio2 = date("Y",strtotime($fecha2));
    
    $fecha_desde = date("Y/m/d",strtotime($fecha1));
    $fecha_hasta = date("Y/m/d",strtotime($fecha2));
    
    $tipo_ordenamiento = $valForm["tipo_ordenamiento"];//1 = fecha ingreso, 2 = fecha defecto, 3 = nº de orden
    
    
    if ($fecha_desde > $fecha_hasta){
        $objResponse->script('alert("La primera fecha no debe ser mayor a la segunda")');
    }elseif($anio1 != $anio2){
        $objResponse->script('alert("Debe estar en el mismo periodo de año")');
    }else{
        
       $objResponse->loadCommands(buscar_ordenes($fecha_desde,$fecha_hasta,$tipo_ordenamiento));
    }
    
    return $objResponse;
}

function buscar_ordenes($fecha_ini, $fecha_fin, $tipo_ordenamiento){
    
    $objResponse = new xajaxResponse();
    
    if($tipo_ordenamiento == "1"){
        $ordenar = "tiempo_orden";
    }elseif($tipo_ordenamiento == "2"){
        $ordenar = "sa_orden.fecha_factura";
    }elseif($tipo_ordenamiento == "3"){
        $ordenar = "sa_orden.id_orden";
    }
    
    $query_ordenes_facturadas = "SELECT 
                                 sa_orden.id_orden, sa_orden.numero_orden, DATE(sa_orden.tiempo_orden) as tiempo_orden, sa_orden.fecha_factura,
                                 sa_tipo_orden.nombre_tipo_orden,
                                 CONCAT_WS(' ',sa_v_orden_recepcion.nombre,sa_v_orden_recepcion.apellido) as nombre_cliente, sa_v_orden_recepcion.nom_modelo, sa_v_orden_recepcion.placa, sa_v_orden_recepcion.asesor, sa_v_orden_recepcion.motivo, sa_v_orden_recepcion.telf,
                                 cj_cc_cliente.sexo, 
                                 #DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(cj_cc_cliente.fecha_nacimiento, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(cj_cc_cliente.fecha_nacimiento, '00-%m-%d')) AS edad,
				 sa_v_orden_recepcion.origen_cita,
                                 sa_v_mecanicos.nombre_completo,
                                 sa_presupuesto.numero_presupuesto,
                                 sa_recepcion.puente,
                                 sa_det_orden_tempario.id_mecanico, ROUND(SUM(sa_det_orden_tempario.ut)/100,1) as total_ut, sa_det_orden_tempario.estado_tempario,
                                 ROUND(SUM(((sa_det_orden_articulo.precio_unitario * sa_det_orden_articulo.cantidad)*((sa_det_orden_articulo.iva /100)+1))),2) as total_repuesto, sa_det_orden_articulo.estado_articulo
                                 
                                 FROM sa_orden
                                 LEFT JOIN sa_tipo_orden ON sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden
                                 LEFT JOIN sa_presupuesto ON sa_orden.id_orden = sa_presupuesto.id_orden
                                 LEFT JOIN sa_v_orden_recepcion ON sa_v_orden_recepcion.id_orden = sa_orden.id_orden
                                 LEFT JOIN cj_cc_cliente ON sa_v_orden_recepcion.id_cliente_contacto = cj_cc_cliente.id
                                 LEFT JOIN sa_det_orden_tempario ON sa_orden.id_orden = sa_det_orden_tempario.id_orden
                                 LEFT JOIN sa_v_mecanicos ON sa_det_orden_tempario.id_mecanico = sa_v_mecanicos.id_mecanico
                                 LEFT JOIN sa_det_orden_articulo ON sa_orden.id_orden = sa_det_orden_articulo.id_orden
                                 LEFT JOIN sa_recepcion ON sa_orden.id_recepcion = sa_recepcion.id_recepcion
                                 
                                 
                                 WHERE sa_orden.fecha_factura BETWEEN '$fecha_ini' AND '$fecha_fin'                                
                                 GROUP BY sa_orden.id_recepcion                                 
                                 ORDER BY sa_v_orden_recepcion.asesor, $ordenar";
    
    $ordenes_facturadas = mysql_query($query_ordenes_facturadas);
    if (!$ordenes_facturadas){return $objResponse->alert("Error en selección DB: " .mysql_error()."\n Nº Error:".mysql_errno()."\n Linea: ".__LINE__);}
    
    if(!mysql_num_rows($ordenes_facturadas)){
        //$objResponse->clear("contenedor_estadistico", "innerHTML");    
        $objResponse->assign("contenedor_estadistico","innerHTML","<div class='noprint'><center><b>No se encontraron registros</b></center></div>");
    }else{
		
        
    }
	
	
		$array_titulos;
		$array_contenido;
		
		//cabecera tabla
		$tabla = "<table class='tabla_informe'><thead><tr>";
		
		$tabla.= "<th>#</th>";
		$tabla.= "<th>TÉCNICO DE LA O/R</th>";
		$tabla.= "<th>NOMBRE Y APELLIDO DEL CLIENTE</th>";
		$tabla.= "<th>EDAD</th>";
		$tabla.= "<th>GENERO</th>";
		$tabla.= "<th>NÚMERO DE TELEFONO</th>";
		$tabla.= "<th># O/R</th>";
		$tabla.= "<th>MODELO DE VEHÍCULO</th>";
		$tabla.= "<th>PLACA</th>";		
		$tabla.= "<th>DESCRIPCIÓN DEL TRABAJO</th>";
		$tabla.= "<th>INGRESO CITA</th>";
		$tabla.= "<th>C<br>I<br>T.<br><br> E<br>F<br>E<br>C<br>T.</th>";
		$tabla.= "<th >T<br>I<br>P<br>O<br><br> O<br>R</th>";
		$tabla.= "<th >I<br>N<br>S<br>P.<br><br> P<br>U<br>E<br>N.</th>";
		$tabla.= "<th >P<br>R<br>E<br>S<br>U<br>P.</th>";
		$tabla.= "<th >A<br><br> T<br>I<br>E<br>M<br>P<br>O</th>";
		$tabla.= "<th >E<br>X<br>P.<br><br> T<br>R<br>A<br>B.</th>";
		$tabla.= "<th >R<br>P<br>S<br>T.<br><br> F<br>A<br>C<br>T.</th>";
		$tabla.= "<th >H. F<br>A<br>C<br>T.<br><br> O<br>/<br>R</th>";
		/*$tabla.= "<th class = 'rot-neg-90' >TIPO DE O/R</th>";
		$tabla.= "<th class = 'rot-neg-90' >INSPECCIÓN PUENTE</th>";
		$tabla.= "<th class = 'rot-neg-90' >PRESUPUESTO</th>";
		$tabla.= "<th class = 'rot-neg-90' >LISTO A TIEMPO</th>";
		$tabla.= "<th class = 'rot-neg-90' >EXPLICACIÓN TRABAJO</th>";
		$tabla.= "<th class = 'rot-neg-90' >RPSTOS FACTURADOS BsF.</th>";
		$tabla.= "<th class = 'rot-neg-90' >HORAS FACT. O/R</th>";*/
		$tabla.= "<th>FECHA DE INGRESO</th>";
		$tabla.= "<th>FECHA DE EGRESO</th>";
		$tabla.= "<th>ASESOR</th>";
		
		$tabla.= "</tr>";
		$tabla.= "</thead>";
		$tabla.= "<tbody>";
		
		$aux_contador = 1;
		while ($row = mysql_fetch_array($ordenes_facturadas)){
			
			if($row['origen_cita'] == 'PROGRAMADA'){
				$origen_cita = "X";
			}else{
				$origen_cita = "";
			}
			
			if($row['puente'] == "0"){//0 si
				$puente = "X";
			//}elseif($row['puente'] == "1"){//1 = no
				//$puente = "NO";
			}else{//null
				$puente = "";
			}
			
			if($row['numero_presupuesto']){
				$presupuesto = "X";
			}else{
				$presupuesto = "";
			}
			
			$tabla.= "<tr>";
			
			$tabla.= "<td>".$aux_contador."</td>";
			$tabla.= "<td>".utf8_encode($row['nombre_completo'])."</td>";
			$tabla.= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$tabla.= "<td>".utf8_encode($row['edad'])."</td>";
			$tabla.= "<td>".$row['sexo']."</td>";
			$tabla.= "<td>".$row['telf']."</td>";
			$tabla.= "<td>".$row['numero_orden']."</td>";
			$tabla.= "<td>".utf8_encode($row['nom_modelo'])."</td>";
			$tabla.= "<td>".utf8_encode($row['placa'])."</td>";
			$tabla.= "<td>".utf8_encode($row['motivo'])."</td>";
			$tabla.= "<td>".$origen_cita."</td>";
			$tabla.= "<td>".$origen_cita."</td>";
			$tabla.= "<td>".utf8_encode($row['nombre_tipo_orden'])."</td>";
			$tabla.= "<td>".$puente."</td>";
			$tabla.= "<td>".$presupuesto."</td>";			
			$tabla.= "<td>X</td>";
			$tabla.= "<td>X</td>";
			$tabla.= "<td>".$row['total_repuesto']."</td>";
			$tabla.= "<td>".$row['total_ut']."</td>";
			$tabla.= "<td>".$row['tiempo_orden']."</td>";
			$tabla.= "<td>".$row['fecha_factura']."</td>";
			$tabla.= "<td>".utf8_encode($row['asesor'])."</td>";
			
			$tabla.= "</tr>";
			$aux_contador++;
				
		}
		
		$tabla.= "</tbody><table>";
		
		$objResponse->assign("contenedor_estadistico","innerHTML","$tabla");
    //$objResponse->alert(mysql_num_rows($ordenes_facturadas)."$fecha_ini x " . "$fecha_fin $ordenar");
    return $objResponse;
    
}

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Informe de Call Center</title>
    <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<!--<script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>-->
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/> 
    <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
    
		


		<script type="text/javascript" src="../js/jquery.js"></script>
		<script type="text/javascript">

    $(document).ready(function() {
      $("#load_animate").hide();
    }       
     );
 
                </script>
      
      
                <?php  $xajax->printJavascript("../controladores/xajax/"); ?>
                
         <style type="text/css">
         	
			.tabla_informe{			
				font-size:9px;
				text-align:center;
				border-style:solid;
				border-width:thin;
                border-collapse: collapse;
			}
			
			.tabla_informe thead{
			}
			.tabla_informe th{
				padding:5px;
				border-style:solid;
				border-width:thin;
                border-collapse: collapse;
				background-color: #DFDFDF;
			}
			
			.tabla_informe td{
				
				border-style:solid;
				border-width:thin;
                border-collapse: collapse;
			}
			
			tr:hover{
				background:#E7FBE4;
			}
			
			.rot-neg-90 {
/*
			  -moz-transform: rotate(270deg);
			  -moz-transform-origin: 50% 50%;
			  -webkit-transform: rotate(270deg);
			  -webkit-transform-origin: 50% 50%;			  
			  
			  padding: 0px 0px 0px 0px;
			  margin: 0px 0px 0px 0px;
			  white-space:nowrap;
			  right: -1245px;
			    left: -1245px;
				*/
			  }
			
         </style>
	</head>
	<body>
            <?php 
            //$_SESSION['idEmpresaUsuarioSysGts']="1";
            //$_SESSION['nombreUsuarioSysGts']="gregor xD";
           
            ?>
<body class="bodyVehiculos">
<div id="divGeneralPorcentaje" style="text-align:left; ">
	<div> <?php include("banner_servicios.php"); ?> </div>        
<div id="divInfo" class="print">
    <table border="0" width="100%" class="noprint">
        <tr>
            <td class="tituloPaginaServicios" id="titulopag">Informe Call Center</td>
        </tr>
        <tr>
            <td class="noprint">
                <table align="left">
                    <tr>
                        <td>
                            <button type="button" class="noprint" onclick="window.print();return false;" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_print.png"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button>
                        </td>

                    </tr>
                </table>

                <form id="frmBuscarS" name="frmBuscarS" onsubmit="return false;" style="margin:0">
                    <table align="right">
                        <tr align="left">
                            <td align="right" class="tituloCampo">Ordenar Por:</td>
                            <td id="td_tipo_medida">
                                <select id="tipo_medida" name="tipo_ordenamiento">
                                    <option value="1" >Fecha Ingreso</option>
                                    <option value="2" selected="selected" >Fecha de Egreso</option>
                                    <option value="3" >Nº de Orden</option>
                                </select>
                            </td>
<!--                            <td align="right" class="tituloCampo">Empresa:</td>
                            <td id="tdlstEmpresa">
                                <select id="lstEmpresa" name="lstEmpresa">
                                    <option value="-1">[ Todos ]</option>
                                </select>
                            </td>-->
                            <td align="right" class="tituloCampo">Fecha:</td>
                            <td>
                                <div style="float:left">
                                    <input type="text" id="txtFechaDesde" name="txtFechaDesde" readonly="readonly" style="text-align:center" size="15" value="<?php echo date("d-m-Y"); ?>"/>
                                </div>
                                <div style="float:left">
                                    <img src="../img/iconos/ico_date.png" id="imgFechaDesde" name="imgFechaDesde" class="puntero noprint"/>
                                    <script type="text/javascript">
                                    Calendar.setup({
                                    inputField : "txtFechaDesde",
                                    ifFormat : "%d-%m-%Y",
                                    button : "imgFechaDesde"
                                    });
                                    </script>
                                </div>
                            </td>
                            <td>
                                <div style="float:left">
                                    <input type="text" id="txtFechaHasta" name="txtFechaHasta" readonly="readonly" style="text-align:center" size="15" value="<?php echo date("d-m-Y"); ?>"/>
                                </div>
                                <div style="float:left">
                                    <img src="../img/iconos/ico_date.png" id="imgFechaHasta" name="imgFechaHasta" class="puntero noprint"/>
                                    <script type="text/javascript">
                                    Calendar.setup({
                                    inputField : "txtFechaHasta",
                                    ifFormat : "%d-%m-%Y",
                                    button : "imgFechaHasta"
                                    });
                                                                        
                                    </script>
                                </div>
                            </td>
                            <td>
                                <input type="button" id="btnBuscar" class="noprint" onclick="xajax_buscar(xajax.getFormValues('frmBuscarS'));" value="Buscar" />
                                <input type="button" class="noprint" onclick="document.forms['frmBuscarS'].reset(); $('btnBuscar').click();" value="Limpiar" />
                            </td>
                        </tr>
                    </table>
                </form>
            </td>
        </tr>
        <tr>
            <td>
                <table border="0" width="100%">
                    <tr>
                        <td colspan="2" style="padding-right:4px">
                            <div id="divListaResumenServ" style="width:100%">
                                <table cellpadding="0" cellspacing="0" class="divMsjInfo noprint" width="100%">
                                    <tr >
                                        <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                                        <td align="center">Ingrese Los Datos Para Realizar la Busqueda</td>
                                    </tr>
                                </table>                               
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top" width="80%" align="left">
<!--                            <div id="divListaResumen" style="width:100%"></div>-->
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <div id="contenedor_estadistico"> 
    
       
    </div>
</div>
               
<!--<script src="../../js/highcharts.js"></script>
<script src="../../js/modules/exporting.js"></script>-->

<script src="../js/highcharts/js/highcharts.js"></script>
<script src="../js/highcharts/js/modules/exporting.js"></script>


 <div class="noprint">
	<?php include("pie_pagina.php"); ?>
    </div>

</div>
	</body>
</html>