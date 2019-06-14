<?php
require_once("../connections/conex.php");

require_once("../inc_sesion.php");
validaModulo("an_pedido_venta_list");
	
conectar();

$queryCliente = sprintf("SELECT
	cliente_emp.id_cliente_empresa,
	cliente_emp.id_empresa,
	cliente.id AS id_cliente,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	nit AS nit_cliente,
	cliente.direccion,
	cliente.ciudad,
	cliente.estado,
	cliente.telf,
	cliente.otrotelf,
	cliente.correo,
	cliente.descuento,
	cliente.credito,
	cliente.id_clave_movimiento_predeterminado,
	cliente.paga_impuesto,
	cliente.reputacionCliente + 0 AS id_reputacion_cliente,
	cliente.reputacionCliente,
	cliente.status,
	cliente.tipo_cuenta_cliente,
	cliente.cargo,
	cliente.ocupacion,
	itm_estado_civil.item AS estado_civil,
	perfil_prospecto.compania,
	perfil_prospecto.fecha_nacimiento
FROM cj_cc_cliente cliente
	LEFT JOIN crm_perfil_prospecto perfil_prospecto ON (cliente.id = perfil_prospecto.id)
	LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cliente.id_empleado_creador = vw_pg_empleado.id_empleado)
	LEFT JOIN grupositems itm_estado_civil ON (perfil_prospecto.id_estado_civil = itm_estado_civil.idItem)
	INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
WHERE cliente.id = %s;",
	valTpDato($idCliente, "int"));
$rsCliente = mysql_query($queryCliente, $conex);
if (!$rsCliente) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowCliente = mysql_fetch_array($rsCliente, MYSQL_ASSOC);

$cedula = utf8_encode($rowCliente['ci_cliente']);
$nombreCliente = utf8_encode($rowCliente['nombre_cliente']);
$fechaNacimiento = (($rowCliente['fecha_nacimiento'] != "") ? date(spanDateFormat,strtotime($rowCliente['fecha_nacimiento'])) : "");
$estado = utf8_encode($rowCliente['estado']);
$estadoCivil = utf8_encode($rowCliente['estado_civil']);
$telf = $rowCliente['telf'];
$otrotelf = $rowCliente['otrotelf'];
$correo = utf8_encode($rowCliente['correo']);
$ocupacion = utf8_encode($rowCliente['ocupacion']);
$compania = utf8_encode($rowCliente['compania']);
$cargo = utf8_encode($rowCliente['cargo']);
$ciudad = utf8_encode($rowCliente['ciudad']);
$direccion = elimCaracter(utf8_encode($rowCliente['direccion']),";");

$sqlvehiculo = sprintf("SELECT
	uni_fis.id_condicion_unidad,
	marca.nom_marca,
	modelo.nom_modelo,
	vers.nom_version,
	trans.nom_transmision,
	combustible.nom_combustible,
	ano.nom_ano,
	uni_fis.placa,
	uni_fis.registro_legalizacion,
	uni_fis.serial_carroceria,
	uni_fis.serial_motor,
	(SELECT an_color.nom_color FROM an_color WHERE id_color = uni_fis.id_color_externo1) AS color1,
	(SELECT an_color.nom_color FROM an_color WHERE id_color = uni_fis.id_color_externo2) AS color2,
	(SELECT an_color.nom_color FROM an_color WHERE id_color = uni_fis.id_color_interno1) AS colorinterno1,
	(SELECT an_color.nom_color FROM an_color WHERE id_color = uni_fis.id_color_interno2)
FROM an_unidad_fisica uni_fis
	INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
		INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
		INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
		INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
	INNER JOIN an_transmision trans ON (uni_bas.trs_uni_bas = trans.id_transmision)
	INNER JOIN an_combustible combustible ON (uni_bas.com_uni_bas = combustible.id_combustible)
	INNER JOIN an_almacen almacen ON (uni_fis.id_almacen = almacen.id_almacen)
WHERE uni_fis.id_unidad_fisica = %s;",
	valTpDato($idUnidadFisica, "int"));
$r = mysql_query($sqlvehiculo, $conex);
if (!$r) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$row = mysql_fetch_array($r, MYSQL_ASSOC);

$condicionUnidad = ($row['id_condicion_unidad'] == 1) ? "VN" : "VU";
$marca = utf8_encode($row['nom_marca']);
$modelo = utf8_encode($row['nom_modelo']);
$version = utf8_encode($row['nom_version']);
$transmision = utf8_encode($row['nom_transmision']);
$ano = $row['nom_ano'];
$placa = utf8_encode($row['placa']);
$certificado = utf8_encode($row['registro_legalizacion']);
$serial_carroceria = utf8_encode($row['serial_carroceria']);
$serial_motor = utf8_encode($row['serial_motor']);
$color = utf8_encode($row['color1']);

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT *,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM vw_iv_empresas_sucursales vw_iv_emp_suc
WHERE vw_iv_emp_suc.id_empresa_reg = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp, $conex) or die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

(strlen($rowEmp['telefono1']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono1'] : "";
(strlen($rowEmp['telefono2']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono2'] : "";
(strlen($rowEmp['telefono_taller1']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono_taller1'] : "";
(strlen($rowEmp['telefono_taller2']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono_taller2'] : "";

$tgerente_ventas = getmysql("SELECT CONCAT_WS(' ', nombre_empleado, apellido) FROM pg_empleado WHERE id_empleado = ".$idGerenteVenta.";");
$tadministracion = getmysql("SELECT CONCAT_WS(' ', nombre_empleado, apellido) FROM pg_empleado WHERE id_empleado = ".$idGerenteAdministracion.";");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Pedido de Venta</title>
    
    <link rel="stylesheet" href="an_ventas_pedido_formato_style.css"/>
    <script type="text/javascript" language="javascript" src="vehiculos.inc.js"></script>
    
    <script language="javascript" type="text/javascript">
    function reputacion(valor, tipo, most){
		if(valor == null) return true;
		
		var m = most || false;
		/*var obj=document.getElementById('capadatoscliente');
		if (obj==null){
		  obj=document.getElementById(ci_cliente);
		  obj.style.background=valor;
		}else{
		  obj.style.color=valor;	
		}*/
		//rep_val=valor; 
		//rep_tipo=tipo;
		if (tipo != '' && most) {
			utf8alert("ATENCI&Oacute;N el cliente tiene una reputaci&oacute;n de: "+tipo);
		}
    }
    
    function percent(){}
    var acc=[];
    
    //objeto accesotrio para la colección
    function accesorio(aid,apq,avalue,aname,vaccion){
        this.iddet = "null";
        this.id = aid;
        this.pq = apq;
        this.nombre = aname; // Descripcion Adicional
        this.civa = "";
        this.piva = "";
        this.iva = "";
        this.value = avalue; // Precio Adicional + Iva
		this.hddTipoAccesorio = "";
        this.accion = vaccion; // 1 = Agregar, 2 = Eliminar, 3 = Modificar
        this.capa = null;
        this.inbase = false;
    }
    
    function newacc(aid, apq, avalue, aname, vaccion, iva, civa, piva, hddTipoAccesorio, cbxCondicion, iddet){
        var na = new accesorio(aid, apq, avalue, aname, vaccion);
        na.iddet = iddet;
        na.civa = civa;
        na.piva = piva;
        na.iva = iva;
		na.hddTipoAccesorio = hddTipoAccesorio;
		na.cbxCondicion = cbxCondicion;
        na.inbase = true;
    }
    
    function imprimir(){
    	setpopup("an_ventas_pedido_editar.php?view=print&id=<?php echo $idPedido; ?>","wiewp", 1050, 600);
    }
    </script>
</head>

<body <?php echo $loadscript; ?>>
<div class="marcoprincipal">
	<?php if ($_GET['view'] != "view" && $_GET['view'] != "print") { include('banner_vehiculos.php'); } ?>
  
	<div class="marco" style="text-align:right;">
		<?php 
		if (isset($_GET['view'])){
			echo "<center>";
			if ($_GET['view'] != "view" && $_GET['view'] != "print"){
				echo "<strong>(PEDIDO A CAJA - MODO VISTA)</strong><br/>";
			}
			if($_GET['view'] != "view" && $_GET['view'] != "print"){
				echo "<button type=\"button\" value=\"imprimir\" onclick=\"imprimir();\"><img border=\"0\" src=\"../img/iconos/ico_print.png\" style=\"padding:3px 2px 2px 2px; vertical-align:middle;\"/>Imprimir</button>
					&nbsp;
					<button title=\"Imprimir Carta de Bienvenida\" type=\"button\" value=\"Carta de Bienvenida\" onclick=\"setpopup('reportes/an_ventas_cartas_bienvenida.php?view=print&id=".$idPedido."','viewp',800,600);\"><img border=\"0\" src=\"../img/iconos/ico_print.png\" alt=\"Imprimir carta de Bienvenida\" style=\"padding:3px 2px 2px 2px; vertical-align:middle;\"/>Bienvenida</button>
					&nbsp;
					<button type=\"button\" value=\"editar\" onclick=\"window.location.href='an_pedido_venta_form.php?id=".$idPedido."';\"><img border=\"0\" src=\"../img/iconos/pencil.png\" style=\"padding:3px 2px 2px 2px; vertical-align:middle;\"/>Editar</button>
					&nbsp;
					<button type=\"button\" value=\"Regresar\" onclick=\"window.location.href='an_pedido_venta_list.php';\"><img border=\"0\" src=\"../img/iconos/return.png\" style=\"padding:3px 2px 2px 2px; vertical-align:middle;\"/>Regresar</button>";
			}			
			echo "</center> ";
		} ?>
		
        <img border="0" src="../clases/barcode128.php?codigo=<?php echo $idPedido; ?>&type=B&bw=2&pc=0"/>
    </div>
    
	<div class="marco">
        <table border="0" class="tabla">
        <tr>
            <td align="center" width="31%" rowspan="3"><img border="0" src="../<?php echo htmlentities($rowEmp['logo_familia']); ?>" width="200"/></td>
            <td width="38%" rowspan="3" class="tddata">
            	<p style="font-size:12px; text-align:left">
					<?php echo utf8_encode($rowEmp['nombre_empresa']); ?><br/>
                    <?php echo $spanRIF.": ".utf8_encode($rowEmp['rif']); ?> <?php echo $spanNIT.": ".utf8_encode($rowEmp['nit']); ?><br/>
					<?php echo utf8_encode($rowEmp['direccion']); ?><br/>
                    <?php echo (count($arrayTelefonos) > 0) ? "Telf.: ".implode(" / ", $arrayTelefonos) : ""; ?><br/>
                    Fax: <?php echo utf8_encode($rowEmp['fax']); ?><br/>
                    E-mail: <?php echo htmlentities($rowEmp['correo']); ?>
				</p>
            </td>
            <td colspan="3" class="tdetiqueta"><strong>Ref. Anexo 12 - Fundamentales VN / VU</strong></td>
        </tr>
        <tr>
            <td width="7%" class="tdetiqueta">Fecha de Solicitud:</td>
            <td colspan="2" class="tddata"><?php echo $fecha; ?></td>
            </tr>
            <tr>
            <!--<table class="intable"><tr><td >Orden de pedido Nro.</td><td class="tddata">##</td></tr></table>-->
            <td colspan="2" class="tdetiqueta"><font style="text-decoration:underline; font-weight:800;">Orden de Pedido Nro.</font></td>
            <td width="12%" class="tddata"><?php echo $numeroPedido; ?></td>
        </tr>
        </table>
	</div>
    
	<div class="marco">
		<table border="0" class="tabla">
        <tbody>
        <tr>
            <td class="tdetiqueta">Presupuesto Nro.</td>
            <td class="tddata"><?php echo $numeroPresupuesto; ?></td>
            <td class="tdetiqueta">Asesor Ventas:</td>
            <td class="tddata"><?php $asesor = htmlentities(getmysql("SELECT CONCAT_WS(' ', nombre_empleado, apellido) FROM pg_empleado WHERE id_empleado = ".$idAsesorVentas.";")); echo $asesor; ?></td>
            <td class="tdetiqueta">Tel&eacute;fono(s):</td>
            <td class="tddata"><?php echo htmlentities(getmysql("SELECT celular FROM pg_empleado WHERE id_empleado = ".$idAsesorVentas.";")); ?></td>
        </tr>
        </tbody>
		</table>
	</div>
    
	<div class="marco">
        <table class="tabla">
        <tr>
            <td class="tdetiqueta" colspan="2" nowrap="nowrap"><p style="text-align:left;">Cliente:<br/>(Nombre o Raz&oacute;n Social)</p></td>
            <td class="tddata" colspan="2"><?php echo $nombreCliente; ?></td>
            <td class="tdetiqueta" nowrap="nowrap">Fecha de Nacimiento:</td>
            <td class="tddata"><?php echo $fechaNacimiento; ?></td>
            <td class="tdetiqueta">Direcci&oacute;n:</td>
            <td colspan="2" class="tddata" rowspan="2"><?php echo $direccion; ?></td>
        </tr>
        <tr>
            <td class="tdetiquetaleft"><?php echo $spanClienteCxC; ?>:</td>
            <td align="center" class="tddata" colspan="2" style="border-bottom:1px solid #000000;"><?php echo $cedula; ?></td>
            <td class="tdetiqueta">Edo. Civil:</td>
            <td class="tddata" colspan="2"><?php echo $estadoCivil; ?></td>
            <td class="tdetiqueta" style="border-right:1px solid #000000; border-bottom:0px;"><?php echo $spanEstado; ?>:</td>
        </tr>
        <tr>
            <td class="tdetiquetaleft" nowrap="nowrap">Telf:</td>
            <td class="tddata" colspan="2"><?php echo $telf; ?></td>
            <td class="tdetiqueta">E-Mail:</td>
            <td class="tddata" colspan="2"><?php echo $correo; ?></td>
            <td class="tddata" style="border-top:0px;"><?php echo $estado; ?></td>
            <td class="tdetiqueta">Ciudad:</td>
            <td class="tddata"><?php echo $ciudad; ?></td>
        </tr>
        <tr>
            <td class="tdetiquetaleft" nowrap="nowrap">Otro Telf:</td>
            <td class="tddata" colspan="2"><?php echo $otrotelf; ?></td>
            <td class="tdetiqueta">Profesi&oacute;n:</td>
            <td class="tddata"><?php echo $ocupacion; ?></td>
            <td class="tdetiqueta">Empresa:</td>
            <td class="tddata"><?php echo $compania; ?></td>
            <td class="tdetiqueta">Cargo:</td>
            <td class="tddata"><?php echo $cargo; ?></td>
        </tr>
        <tr>
            <td width="6%"></td>
            <td width="4%"></td>
            <td width="10%"></td>
            <td width="12%"></td>
            <td width="16%"></td>
            <td width="12%"></td>
            <td width="14%"></td>
            <td width="12%"></td>
            <td width="14%"></td>
        </tr>
        </table>
	</div>
    
    <div class="marco">
		<div style="width:396px; float:left; padding-right:4px;">
            <table class="tabla">
            <tr>
                <td colspan="4" width="25%" class="tdetiqueta"><strong>Datos del Autom&oacute;vil (VN/VU): </strong><?php echo $condicionUnidad; ?></td>
            </tr>
            <tr>
                <td class="tdetiquetaleft" width="25%">Marca:</td>
                <td colspan="3" class="tddata" width="25%"><?php echo $marca; ?></td>
            </tr>
            <tr>
                <td class="tdetiquetaleft">Modelo:</td>
                <td colspan="3" class="tddata"><?php echo $modelo; ?></td>
            </tr>
            <tr>
                <td class="tdetiquetaleft">Version:</td>
                <td colspan="3" class="tddata"><?php echo $version; ?></td>
            </tr>
            <tr>
                <td class="tdetiquetaleft">Color:</td>
                <td colspan="3" class="tddata"><?php echo $color; ?></td>
            </tr>
            <tr>
                <td class="tdetiquetaleft">A&ntilde;o:</td>
                <td class="tddata"><?php echo $ano; ?></td>
                <td colspan="2" class="tdetiqueta">*Oferta</td>
            </tr>
            <tr>
                <td colspan="4" width="25%" class="tdetiqueta"><font style="text-decoration:underline; font-weight:800;">Validaci&oacute;n y/o Aprobaci&oacute;n del Pedido:</font></td>
            </tr>
            <tr>
                <td colspan="2" class="tdetiquetaleft">Gerente de Ventas:</td>
                <td colspan="2" class="tddata" width="75%"><?php echo htmlentities($tgerente_ventas); ?></td>
            </tr>
            <tr>
                <td class="tdetiquetaleft" width="25%">Firma:</td>
                <td colspan="3" width="75%">&nbsp;</td>
            </tr>
            <tr>
                <td class="tdetiquetaleft" width="25%">Fecha:</td>
                <td colspan="3" class="tddata" width="75%"><?php echo $txtFechaVenta; ?></td>
            </tr>
            <tr>
                <td colspan="2" class="tdetiquetaleft">Por Administraci&oacute;n:</td>
                <td colspan="2" class="tddata" width="75%"><?php echo htmlentities($tadministracion); ?></td>
            </tr>
            <tr>
                <td class="tdetiquetaleft" width="25%">Firma:</td>
                <td colspan="3" width="75%">&nbsp;</td>
            </tr>
            <tr>
                <td class="tdetiquetaleft" width="25%">Fecha:</td>
                <td colspan="3" class="tddata" width="75%"><?php echo $txtFechaAdministracion; ?></td>
            </tr>
            </table>
		</div>
        
        <div style="width:396px; float:left; padding-right:4px;">
            <table class="tabla" cellpadding="2">
            <tr>
                <td colspan="3" class="tdetiqueta"><strong>Datos de la Operaci&oacute;n de Ventas:</strong></td>
            </tr>
            <tr>
                <td align="center"><strong>Forma de Pago:</strong></td>
                <td colspan="2" align="center"><?php echo ($txtPorcInicial == 100) ? "Contado" : "Cr&eacute;dito"; ?></td>
            </tr>
            <tr>
                <td width="50%" class="tdeventa"><strong>Precio Base:<br/><span id="eviva" class="textoNegrita_10px"><?php echo ($txtMontoImpuesto > 0) ? "Sin ".$eviva : ""; ?></span></strong></td>
                <td width="10%" class="tdebs"><?php echo $abrevMonedaLocal; ?></td>
                <td class="tddventa"><?php echo numformat($txtPrecioBase,2); ?></td>
            </tr>
            <tr <?php echo ($txtMontoImpuesto > 0) ? "" : "style=\"display:none\""; ?>>
                <td class="tdeventa"><strong><?php echo ($txtMontoImpuesto > 0) ? $eviva : ""; ?>:</strong></td>
                <td class="tdebs"><?php echo $abrevMonedaLocal; ?></td>
                <td class="tddventa"><?php echo numformat($txtMontoImpuesto,2); ?></td>
            </tr>
            <tr>
                <td class="tdeventa"><strong>Precio Venta:<br/><span id="eviva" class="textoNegrita_10px"><?php echo ($txtMontoImpuesto > 0) ? "Incluye ".$eviva : $eviva; ?></span></strong></td>
                <td class="tdebs"><?php echo $abrevMonedaLocal; ?></td>
                <td class="tddventa"><?php echo numformat($txtPrecioVenta,2); ?></td>
            </tr>
		<?php
        // PARTIDAS
		$iva = intval(getmysql("SELECT iva FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1 ORDER BY iva;"));
		$totalExento = 0;
		$totalGasto = 0;
		$subTotalA = floatval($txtPrecioVenta);
		$queryPedidoDet = sprintf("SELECT q.*
		FROM (
			SELECT
				acc.nom_accesorio,
				paq_ped.precio_accesorio,
				paq_ped.costo_accesorio,
				paq_ped.porcentaje_iva_accesorio,
				paq_ped.iva_accesorio
			FROM an_paquete_pedido paq_ped
				INNER JOIN an_acc_paq acc_paq ON (paq_ped.id_acc_paq = acc_paq.Id_acc_paq)
				INNER JOIN an_accesorio acc ON (acc_paq.id_accesorio = acc.id_accesorio)
			WHERE paq_ped.id_pedido = %s
			
			UNION
			
			SELECT
				acc.nom_accesorio,
				acc_ped.precio_accesorio,
				acc_ped.costo_accesorio,
				acc_ped.porcentaje_iva_accesorio,
				acc_ped.iva_accesorio
			FROM an_accesorio_pedido acc_ped
				INNER JOIN an_accesorio acc ON (acc_ped.id_accesorio = acc.id_accesorio)
			WHERE acc_ped.id_pedido = %s) AS q
		WHERE q.iva_accesorio = 0;",
			valTpDato($idPedido, "int"),
			valTpDato($idPedido, "int"));
		$rsPedidoDet = @mysql_query($queryPedidoDet, $conex);
		if (!$rsPedidoDet) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
			$subTotalA += floatval($rowPedidoDet['precio_accesorio']);
			$totalExento += floatval($rowPedidoDet['precio_accesorio']); ?>
            <tr>
                <td class="tdeventa"><?php echo utf8_encode($rowPedidoDet['nom_accesorio'])." (E)"; ?></td>
                <td class="tdebs"><?php echo $abrevMonedaLocal; ?></td>
                <td class="tddventa"><?php echo numformat($rowPedidoDet['precio_accesorio'],2); ?></td>
            </tr>
		<?php
        } ?>
            <tr>
                <td class="tdeventa"><strong>Sub Total A:</strong></td>
                <td class="tdebs"><?php echo $abrevMonedaLocal; ?></td>
                <td class="tddventa"><?php echo numformat($subTotalA,2); ?></td>
            </tr>
		<?php
		$subTotalB = 0;
		$queryPedidoDet = sprintf("SELECT q.*
		FROM (
			SELECT
				acc.nom_accesorio,
				paq_ped.precio_accesorio,
				paq_ped.costo_accesorio,
				paq_ped.porcentaje_iva_accesorio,
				paq_ped.iva_accesorio
			FROM an_paquete_pedido paq_ped
				INNER JOIN an_acc_paq acc_paq ON (paq_ped.id_acc_paq = acc_paq.Id_acc_paq)
				INNER JOIN an_accesorio acc ON (acc_paq.id_accesorio = acc.id_accesorio)
			WHERE paq_ped.id_pedido = %s
			
			UNION
			
			SELECT
				acc.nom_accesorio,
				acc_ped.precio_accesorio,
				acc_ped.costo_accesorio,
				acc_ped.porcentaje_iva_accesorio,
				acc_ped.iva_accesorio
			FROM an_accesorio_pedido acc_ped
				INNER JOIN an_accesorio acc ON (acc_ped.id_accesorio = acc.id_accesorio)
			WHERE acc_ped.id_pedido = %s) AS q
		WHERE q.iva_accesorio = 1;",
			valTpDato($idPedido, "int"),
			valTpDato($idPedido, "int"));
		$rsPedidoDet = @mysql_query($queryPedidoDet, $conex);
		if (!$rsPedidoDet) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
			$precio = $rowPedidoDet['precio_accesorio'] + ($rowPedidoDet['precio_accesorio'] * $iva / 100);
			$subTotalB += floatval($precio);
			$totalGasto += floatval($precio); ?>
            <tr>
                <td class="tdeventa"><?php echo utf8_encode($rowPedidoDet['nom_accesorio']); ?><sup>1</sup></td>
                <td class="tdebs"><?php echo $abrevMonedaLocal; ?></td>
                <td class="tddventa"><?php echo numformat($precio,2); ?></td>
            </tr>
		<?php
        }
		
		$queryPedidoDet = sprintf("SELECT *,
			(SELECT SUM(an_ped_vent_det_impsto.impuesto) FROM an_pedido_venta_detalle_impuesto an_ped_vent_det_impsto
			WHERE an_ped_vent_det_impsto.id_pedido_venta_detalle = an_ped_vent_det.id_pedido_venta_detalle) AS porc_impuesto
		FROM an_pedido_venta_detalle an_ped_vent_det
			INNER JOIN iv_articulos art ON (an_ped_vent_det.id_articulo = art.id_articulo)
		WHERE an_ped_vent_det.id_pedido_venta = %s
		ORDER BY an_ped_vent_det.id_pedido_venta_detalle ASC;",
			valTpDato($idPedido, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
			$arrayAccesorio[] = $rowPedidoDet['descripcion'];
			
			$totalPrecio = $rowPedidoDet['cantidad'] * $rowPedidoDet['precio_unitario'];
			$totalPrecio = $totalPrecio + ($totalPrecio * $rowPedidoDet['porc_impuesto'] / 100);
			$txtTotalAccesorio += $totalPrecio;
			
			$subTotalB += floatval($totalPrecio);
		}
        
        $totalPagar = floatval($subTotalA) + floatval($subTotalB); ?>
            <tr>
                <td class="tdeventa">Accesorios<sup>2</sup></td>
                <td class="tdebs"><?php echo $abrevMonedaLocal; ?></td>
                <td class="tddventa"><?php echo numformat($txtTotalAccesorio,2); ?></td>
            </tr>
            <tr>
                <td class="tdeventa"><strong>Sub Total B:</strong></td>
                <td class="tdebs"><?php echo $abrevMonedaLocal; ?></td>
                <td class="tddventa"><?php echo numformat($subTotalB,2); ?></td></tr>

           <?php 
            $fecha = date_create_from_format('d-m-Y', $txtFechaReserva);
            $fechaReconversion = date_create_from_format('Y-m-d','2018-08-01');
            $fechaAjusten = date_create_from_format('Y-m-d','2018-08-20');
 
           if ($fecha >=$fechaReconversion and $fecha<$fechaAjusten) {?>
                  <tr>
                    <td class="tdeventa"><strong>Total a Pagar:</strong></td>
                    <td class="tdebs"><?php echo $abrevMonedaLocal; ?></td>
                    <td class="tddventa"><?php echo numformat($totalPagar,2); ?></td>
                  </tr>
                  <tr>
                    <td class="tdeventa"><strong>Total a Pagar :</strong></td>
                    <td class="tdebs"><?php echo "Bs.S"; ?></td>
                    <td class="tddventa"><?php echo numformat($totalPagar/100000); ?></td>
                  </tr>

            <?php  }else if ($fecha >=$fechaAjusten) {?>
                 <tr>
                    <td class="tdeventa"><strong>Total a Pagar :</strong></td>
                    <td class="tdebs"><?php echo "Bs.S"; ?></td>
                    <td class="tddventa"><?php echo numformat($totalPagar); ?></td>
                  </tr>
                 <tr>
                    <td class="tdeventa"><strong>Total a Pagar:</strong></td>
                    <td class="tdebs"><?php echo $abrevMonedaLocal; ?></td>
                    <td class="tddventa"><?php echo numformat($totalPagar*100000,2); ?></td>
                 </tr>
            <?php  }else{?>
                <tr>
                    <td class="tdeventa"><strong>Total a Pagar:</strong></td>
                    <td class="tdebs"><?php echo $abrevMonedaLocal; ?></td>
                    <td class="tddventa"><?php echo numformat($totalPagar,2); ?></td>
                </tr>
            <?php } ?>
            
            <tr>
                <td class="tdeventa"><?php echo $spanInicial; ?>:</td>
                <td class="tdebs"><?php echo $abrevMonedaLocal; ?></td>
                <td class="tddventa"><?php echo numformat(($txtMontoAnticipo + $txtMontoInicial),2); ?></td>
            </tr>
            <tr>
                <td class="tdeventa">Cash Back:</td>
                <td class="tdebs"><?php echo $abrevMonedaLocal; ?></td>
                <td class="tddventa"><?php echo numformat($txtMontoCashBack,2); ?></td>
            </tr>
            <tr>
                <td class="tdeventa">Saldo a Financiar:</td>
                <td class="tdebs"><?php echo $abrevMonedaLocal; ?></td>
                <td class="tddventa"><?php echo numformat($txtSaldoFinanciar,2); ?></td>
            </tr>
			</table>
		</div>
        
		<div style="width:200px; float:left;">
            <table class="tabla">
            <tr>
                <td style="border-bottom:1px solid #000000;">
                    <p align="center" style=" font-size:10px;"><font style="text-decoration:underline; font-weight:800;">OBSERVACIONES:</font><br/><br/>El presente documento u ORDEN DE PEDIDO podr&aacute; ir acompañado de Anexos, donde se establezcan las condiciones o cl&aacute;usulas relacionadas a la compra - venta del autom&oacute;vil, de acuerdo a las leyes, a fin de garantizar el cabal cumplimiento de las obligaciones asumidas y convenidas por ambas partes.<br/><br/>El cliente o comprador declara haber realizado el pedido del VN o VU mencionado en este formato, y haber sido informado con antelaci&oacute;n y haber firmado las condiciones m&iacute;nimas establecidas en este formulario.</p>
                </td>
            </tr>
            <tr>
                <td>
                    <p align="center" style=" font-size:10px;"><font style="font-weight:800;">SE DEFINE COMO:</font><br/>VN: Veh&iacute;culo Nuevo, Autom&oacute;vil.<br/>VU: Veh&iacute;culo Usado, Autom&oacute;vil.</p>
                </td>
            </tr>
            </table>
		</div>
	</div>
    
	<div class="marco">
		<div style="width:396px; padding-right:4px; float:left;">
            <table class="tabla">
            <tr>
                <td colspan="2" width="25%" class="tdetiquetaleft"><strong>Retoma VU</strong> Fecha:</td>
                <td colspan="2" class="tddata"><?php echo $txtFechaRetoma; ?></td>
            </tr>
            <tr>
                <td colspan="2" class="tdetiquetaleft" width="25%">Precio de Retoma Bs:</td>
                <td colspan="2" class="tddata"><?php echo numformat($txtPrecioRetoma,2); ?></td>
            </tr>
            <tr>
                <td class="tdetiquetaleft" width="25%">Marca:</td>
                <td class="tddata" width="25%"></td>
            </tr>
            <tr>
                <td class="tdetiquetaleft">Modelo:</td>
                <td class="tddata"></td>
                <td colspan="2" class="tddata" width="50%"></td>
            </tr>
            <tr>
                <td class="tdetiquetaleft">Version:</td>
                <td colspan="3" class="tddata"></td>
            </tr>
            <tr>
                <td class="tdetiquetaleft">Color:</td>
                <td colspan="3" class="tddata"></td>
            </tr>
            <tr>
                <td class="tdetiquetaleft"><?php echo $spanPlaca; ?>:</td>
                <td class="tddata"></td>
                <td class="tdetiquetaleft">Cert. Origen:</td>
                <td class="tddata"></td>
            </tr>
            </table>
		</div>
        
		<div style="width:600px; float:left;">
            <div style="width:600px; float:left;">
                <sup>1</sup> Gastos Administrativos
                <br/>
                <sup>2</sup> <?php echo implode(", ", $arrayAccesorio); ?>
            </div>
      		
			<div style="width:600px; float:left;">
                <table class="tabla">
                <tr>
                    <td class="tdetiqueta"><p style="font-size:12px;"><font style="text-decoration:underline; font-weight:800;">Observaciones:</font><br/>El valor de la oferta de retoma ser&aacute; el realizado en el &uacute;ltimo avalúo del VU a la fecha de la negociaci&oacute;n y la entrega del VN estipulado en este formato, bajo reserva de que el cliente entregue el VU, libre de todo compromiso y de toda reserva de propiedad dentro de un estado conforme al peritaje firmado por el cliente en la fecha del documento.</p></td>
                </tr>
                <tr>
                    <td><p style="font-size:12px;"><font style="text-decoration:underline; font-weight:800;">Otras Observaciones:</font>&nbsp;<?php echo ($txtObservacion); ?></p></td>
                </tr>
                </table>
			</div>
		</div>
	</div>
    
    <div class="marco" style="border-top:1px solid #000000;">
		<div style="width:396px; padding-right:4px; float:left;">
            <table class="tabla" style="border:0px;">
            <tr>
                <td colspan="4" class="tdetiqueta" style="border-right:1px solid #000000;"><strong>Caracter&iacute;sticas del Autom&oacute;vil asignado:<br/>(Espacio para uso exclusivo del distribuidor)</strong></td>
            </tr>
            <tr>
                <td class="tdetiquetaleft">Color:</td>
                <td colspan="3" class="tddata"><?php echo $color; ?></td>
            </tr>
            <tr>
                <td class="tdetiquetaleft"><?php echo $spanPlaca; ?>:</td>
                <td width="25%" class="tddata"><?php echo $placa; ?></td>
                <td width="25%" class="tdetiquetaleft">Cert. Origen:</td>
                <td width="25%" class="tddata"><?php echo $certificado; ?></td>
            </tr>
            <tr>
                <td class="tdetiquetaleft"><?php echo $spanSerialCarroceria; ?>:</td>
                <td colspan="3" class="tddata"><?php echo $serial_carroceria; ?><td>
            </tr>
            <tr>
                <td class="tdetiquetaleft"><?php echo $spanSerialMotor; ?>:</td>
                <td colspan="3" class="tddata"><?php echo $serial_motor; ?><td>
            </tr>
            <tr>
                <td class="tdetiquetaleft">Fecha de Venta:</td>
                <td class="tddata"><?php echo $txtFechaReserva; ?></td>
                <td class="tdetiquetaleft">Fecha de Entrega:</td>
                <td class="tddata"><?php echo $txtFechaEntrega; ?><td>
            </tr>
            <tr>
                <td colspan="4" class="tdetiqueta" style="border-right:1px solid #000000;"><strong>SEGURO</strong></td>
            </tr>
            <tr>
                <td colspan="4" class="tddata" style="border-left:1px solid #000000;"><?php echo ($idPoliza > 0) ? utf8_encode(getmysql("SELECT nombre_poliza FROM an_poliza WHERE id_poliza = ".$idPoliza.";")) : "&nbsp;"; ?></td>						
            </tr>
            </table>
		</div>
        
		<div style="width:296px; padding-right:4px; float:left;">
            <table class="tabla">
            <tr>
                <td colspan="3" class="tdetiqueta"><font style="text-decoration:underline; font-weight:800;">Forma de Pago:</font></td>
            </tr>
            <tr>
                <td width="55%" class="tdetiquetaleft"><?php echo $spanAnticipo; ?>:</td>
                <td width="5%" class="postetiqueta"><?php echo $abrevMonedaLocal; ?></td>
                <td width="40%" class="tddataright"><?php echo numformat($txtMontoAnticipo,2); ?></td>
            </tr>
            <tr>
                <td class="tdetiquetaleft">Cuota <?php echo $spanInicial; ?>:</td>
                <td class="postetiqueta"><?php echo $abrevMonedaLocal; ?></td>
                <td class="tddataright"><?php echo numformat($txtMontoInicial,2); ?></td>
            </tr>
            <tr>
                <td class="tdetiquetaleft">Cash Back:</td>
                <td class="postetiqueta"><?php echo $abrevMonedaLocal; ?></td>
                <td class="tddataright"><?php echo numformat($txtMontoCashBack,2); ?></td>
            </tr>
            <tr>
                <td class="tdetiquetaleft">Complemento <?php echo $spanInicial; ?>:</td>
                <td class="postetiqueta"><?php echo $abrevMonedaLocal; ?></td>
                <td class="tddataright"><?php echo numformat($txtMontoComplementoInicial,2); ?></td>
            </tr>
            <tr>
                <td class="tdetiquetaleft">Comisi&oacute;n Financiera:</td>
                <td class="postetiqueta"><?php echo $abrevMonedaLocal; ?></td>
                <td class="tddataright"><?php echo numformat($txtMontoFLAT,2); ?></td>
            </tr>
            <tr>
                <td class="tdetiquetaleft">Exentos:</td>
                <td class="postetiqueta"><?php echo $abrevMonedaLocal; ?></td>
                <td class="tddataright"><?php echo numformat($totalExento,2); ?></td>
            </tr>
            <tr>
                <td class="tdetiquetaleft">Gastos Administrativos:</td>
                <td class="postetiqueta"><?php echo $abrevMonedaLocal; ?></td>
                <td class="tddataright"><?php echo numformat($totalGasto,2); ?></td>
            </tr>


            <?php 

 
           if ($fecha >=$fechaReconversion and $fecha<$fechaAjusten) {?>
                <tr>
                    <td class="tdetiquetaleft">Precio Total:</td>
                    <td class="postetiqueta"><?php echo $abrevMonedaLocal; ?></td> 
                    <td class="tddataright"><?php echo numformat($txtPrecioTotal,2); ?></td>
                </tr>
                <tr>
                    <td class="tdetiquetaleft">Precio Total:</td>
                    <td class="postetiqueta"><?php echo "Bs.S"; ?></td>
                    <td class="tddataright"><?php echo numformat($txtPrecioTotal/100000); ?></td>
                </tr>

            <?php  }else if ($fecha >=$fechaAjusten) {?>
                 <tr>
                    <td class="tdetiquetaleft">Precio Total:</td>
                    <td class="postetiqueta"><?php echo "Bs.S"; ?></td>
                    <td class="tddataright"><?php echo numformat($txtPrecioTotal); ?></td>
                </tr>
                <tr>
                    <td class="tdetiquetaleft">Precio Total:</td>
                    <td class="postetiqueta"><?php echo $abrevMonedaLocal; ?></td> 
                    <td class="tddataright"><?php echo numformat($txtPrecioTotal*100000,2); ?></td>
                </tr>
            <?php  }else{?>
                <tr>
                    <td class="tdetiquetaleft">Precio Total:</td>
                    <td class="postetiqueta"><?php echo $abrevMonedaLocal; ?></td> 
                    <td class="tddataright"><?php echo numformat($txtPrecioTotal,2); ?></td>
                </tr>
            <?php } ?>
           
            </table>
        </div>
        
		<div style="width:300px; float:left;">
            <table class="tabla">
            <tr>
                <td colspan="3" class="tdetiqueta"><strong>Prestamo Financiero:</strong></td>
            </tr>
            <tr>
                <td width="55%" class="tdetiquetaleft">Entidad:</td>
                <td width="5%" class="postetiqueta"></td>
                <td width="40%" class="tddataright"><?php if ($lstBancoFinanciar > 0) echo htmlentities(getmysql("select nombreBanco from bancos where idBanco=".$lstBancoFinanciar.";")); ?></td>
            </tr>
            <tr>
                <td class="tdetiquetaleft">Plazo (Meses):</td>
                <td></td>
                <td class="tddataright"><?php echo $lstMesesFinanciar; ?></td>
            </tr>

            <?php if ($fecha >=$fechaReconversion and $fecha<$fechaAjusten) {?>
                 <tr>
                    <td class="tdetiquetaleft">Total:</td>
                    <td class="postetiqueta"><?php echo $abrevMonedaLocal; ?></td>
                    <td class="tddataright"><?php echo numformat($txtSaldoFinanciar,2); ?></td>
                </tr>
                <tr>
                    <td class="tdetiquetaleft">Total:</td>
                    <td class="postetiqueta"><?php echo "Bs.S"; ?></td>
                    <td class="tddataright"><?php echo numformat($txtSaldoFinanciar/100000); ?></td>
                </tr>

            <?php  }else if ($fecha >=$fechaAjusten) {?>
                 <tr>
                    <td class="tdetiquetaleft">Total:</td>
                    <td class="postetiqueta"><?php echo "Bs.S"; ?></td>
                    <td class="tddataright"><?php echo numformat($txtSaldoFinanciar); ?></td>
                </tr>
                <tr>
                    <td class="tdetiquetaleft">Total:</td>
                    <td class="postetiqueta"><?php echo $abrevMonedaLocal; ?></td>
                    <td class="tddataright"><?php echo numformat($txtSaldoFinanciar*100000,2); ?></td>
                </tr>
            <?php  }else{?>
                <tr>
                    <td class="tdetiquetaleft">Total:</td>
                    <td class="postetiqueta"><?php echo $abrevMonedaLocal; ?></td>
                    <td class="tddataright"><?php echo numformat($txtSaldoFinanciar,2); ?></td>
                </tr>
            <?php } ?>
           
            <tr>
                <td class="tdetiquetaleft">Cuotas (estimadas):</td>
                <td class="postetiqueta"><?php echo $abrevMonedaLocal; ?></td>
                <td class="tddataright"><?php echo $txtCuotasFinanciar; ?></td>
            </tr>
            <tr>
                <td class="tdetiquetaleft">Comisi&oacute;n:</td>
                <td class="postetiqueta"><?php echo $abrevMonedaLocal; ?></td>
                <td class="tddataright">&nbsp;</td>
            </tr>
            <tr>
                <td class="tdetiquetaleft">Seguro:</td>
                <td class="postetiqueta"><?php echo $abrevMonedaLocal; ?></td>
                <td class="tddataright"><?php echo numformat($txtMontoSeguro,2); ?></td>
            </tr>
            </table>
      
            <table style="width:100%;">
            <tr>
                <td>Fecha</td>
                <td><?php echo date(spanDateFormat); ?></td>
            </tr>
            </table>
        </div>
    </div>
    
    <div class="marco">
        <table style="width:100%;">
        <tbody>
        <tr>
            <td>Ejecutivo de Ventas:</td>
            <td><?php echo $asesor; ?></td>
            <td>Gte. de Ventas:</td>
            <td><?php echo htmlentities($tgerente_ventas); ?></td>
            <td>Firma del Cliente o Comprador:</td>
            <td>&nbsp;</td>
        </tr>
        </tbody>
        </table>
    </div>
	
	<?php 
	if ($_GET['view'] != "view" && $_GET['view'] != "print") {
		include('pie_pagina.php');
	} ?>
</div>
</body>
</html>
