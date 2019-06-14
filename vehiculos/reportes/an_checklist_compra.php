<?php
include "../../connections/conex.php";

@session_start();
$empresa = mysql_fetch_assoc(mysql_query("SELECT * FROM pg_empresa
WHERE id_empresa = ".$_SESSION['session_empresa'].";",$conex));

$id_unidad_fisica = $_GET['id_unidad_fisica'];
if($id_unidad_fisica == "") {
	echo 'no ha especificado unidad';
	exit;
}
//cargar los datos de la unidad fisica
	
$unidad_fisica = mysql_fetch_assoc(mysql_query("SELECT 
	uni_fis.id_unidad_fisica,
	uni_bas.nom_uni_bas,
	marca.nom_marca,
	modelo.nom_modelo,
	vers.nom_version,
	uni_fis.placa,
	ano.nom_ano,
	uni_fis.serial_chasis,
	uni_fis.serial_carroceria,
	uni_fis.serial_motor,
	color.nom_color,
	uni_bas.com_uni_bas,
	uni_fis.codigo_unico_conversion,
	uni_fis.marca_kit,
	uni_fis.marca_cilindro,
	uni_fis.modelo_regulador,
	uni_fis.serial1,
	uni_fis.serial_regulador,
	uni_fis.capacidad_cilindro,
	uni_fis.fecha_elaboracion_cilindro,
	uni_fis.costo_compra,
	uni_bas.isan_uni_bas,
	uni_fis.estado_venta
FROM an_unidad_fisica uni_fis
	INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
		INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
		INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
		INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
	INNER JOIN an_color color ON (uni_fis.id_color_externo1 = color.id_color)
WHERE uni_fis.id_unidad_fisica = ".$id_unidad_fisica.";",$conex));

//cargar los datos de la factura
$factura = @mysql_fetch_assoc(@mysql_query("SELECT *, DATE_FORMAT(fecha_registro_pdi,'".spanDatePick."') AS fecha_checklist FROM an_solicitud_factura
WHERE id_unidad_fisica = ".$id_unidad_fisica.";",$conex));
	
	
//cargar los datos del cliente	
/*$ridpedido=@mysql_query("select id_cliente from an_pedido where id_unidad_fisica=".$id_unidad_fisica.";",$conex);
if($ridpedido){
	$pedido=@mysql_fetch_assoc($ridpedido);
	$cliente=@mysql_fetch_assoc(@mysql_query("select * from cj_cc_cliente where id=".$pedido['id_cliente'].";",$conex));
}else{
}*/
	
if($_GET['view'] == 'print') {
	$loadprint='print();';
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Inspecci&oacute;n Pre-Entrega</title>
    
    <link rel="stylesheet" href="an_checklist_compra_style.css" />
	
    <script type="text/javascript" src="vehiculos.inc.js"></script>
    
    <link rel="stylesheet" href="../../js/calendar-green.css" />
    <script type="text/javascript" src="../../js/calendar.js"></script>
    <script type="text/javascript" src="../../js/calendar-es.js"></script>
    <script type="text/javascript" src="../../js/calendar-setup.js"></script>
	
	<style type="text/css">
	body{
		font-family:Verdana, Arial, Helvetica, sans-serif;
	}
	.image_logo{
		max-width:135px;
	}
	.check_titulo{
		border:1px solid #000000;
		padding:2px;
		text-align:center;
		margin-top:5px;
		margin-bottom:5px;
	}
	.check_titulo2{
		border:1px solid #000000;
		padding:10px;
		text-align:center;
		font-weight:bold;
	}
	.check_data input[type=checkbox]{
		float:left;
	}
	.check_data{
		padding:2px;
		padding-right:10px;
		padding-left:36px;
		text-indent: -20px;
	}
	.check_data img{
		border: 0px;
		margin-right: 4px;
		padding: 0px;
		vertical-align: middle ;
	}
	.check_data:hover{
		background:#DFDFDF;
		cursor:pointer;
	}
	.hidden_table{
		width:100%;
	}
	.label{
		padding:2px;
	}
	.label1{
		padding:2px;
		padding-top:10px;
		padding-bottom:10px;
	}
    </style>
</head>
<body onload="<?php echo $loadprint; ?>" >
	<table class="">
    <tbody>
        <tr>
            <td>
                <img class="image_logo" src="../../<?php echo $empresa['logo_empresa']; ?>" />
            </td>
            <td >
                <div class="check_titulo2">INSPECCI&Oacute;N PRE-ENTREGA</div>
                
            </td>
        </tr>		
    </tbody>	
	</table>
    
	<table class="hidden_table">
    <tbody>
        <tr>
            <td width="50%" valign="top">
                <table class="hidden_table">
                <tbody>
                    <tr>
                        <td>Cliente:</td>
                        <td><?php echo htmlentities($empresa['nombre_empresa'].' ('.$empresa['rif'].')'); ?> </td>
                    </tr>
                    <tr>
                        <td>Modelo:</td>
                        <td><?php echo htmlentities($unidad_fisica['nom_marca'].' '.$unidad_fisica['nom_modelo']); ?>
                        </td>
                    </tr>
                    <tr>
                        <td>A&ntilde;o:</td>
                        <td><?php echo htmlentities($unidad_fisica['nom_ano']); ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo $spanSerialCarroceria; ?>:</td>
                        <td><?php echo htmlentities($unidad_fisica['serial_carroceria']); ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Cat&aacute;logo:</td>
                        <td><?php echo htmlentities($unidad_fisica['nom_uni_bas']); ?>
                        </td>
                    </tr>
                </tbody>
                </table>
            
<?php
//obteniendo datos para la impresi�n del checklist de COMPRAS
//primer nodo:

if ($id_unidad_fisica != "") {
    //valores para el check:
    $checkvalue = array("0"=>"nocheck.gif",""=>"nocheck.gif","1"=>'check.gif');
    @session_start();
    //echo $_SESSION['session_empresa'];
    $sqlcheck = sprintf("SELECT * FROM an_checklist
    WHERE ISNULL(parent)
        AND tipo = 1
        AND n_checklist = 0
        AND (id_empresa = %s
			OR %s IN (SELECT suc.id_empresa FROM pg_empresa suc
					WHERE suc.id_empresa_padre = an_checklist.id_empresa))
    ORDER BY columna ASC, order_level ASC;",
		valTpDato($_SESSION['session_empresa'], "int"),
		valTpDato($_SESSION['session_empresa'], "int"));
    //echo $sqlcheck;
    $rcheck = @mysql_query($sqlcheck,$conex);
    if($rcheck) {
        
        echo '<input type="hidden" name="id_unidad_fisica" value="'.$id_unidad_fisica.'" />';
        $lastcolumn=0;
        while($rowcheck = mysql_fetch_assoc($rcheck)) {
            //imprimiendo:
            
            if($lastcolumn != $rowcheck['columna']) {
                echo '</td><td width="50%" valign="top">';
                $lastcolumn = $rowcheck['columna'];
            }
			
            echo sprintf('<div class="%s">%s</div>',
                $rowcheck['clasecss'],
                $rowcheck['texto']);
            
            //extrayendo las subordinadas
            /*$sqlsc="select 
                an_checklist.*,
                an_compra_unidad_checklist.id_compra_unidad_checklist as id_compra_unidad_checklist,
                an_compra_unidad_checklist.valor as valor
            from an_checklist left join an_compra_unidad_checklist on(an_checklist.id_checklist=an_compra_unidad_checklist.id_checklist) 
            where 
                parent=".$rowcheck['id_checklist']." and n_checklist=0 and
                (an_compra_unidad_checklist.id_unidad_fisica=".$id_unidad_fisica." or isnull(an_compra_unidad_checklist.id_unidad_fisica))
            ;";*/
            $sqlsc = "select 
                an_checklist.*,
                (SELECT an_compra_unidad_checklist.id_compra_unidad_checklist 
                FROM an_compra_unidad_checklist
                WHERE an_compra_unidad_checklist.id_unidad_fisica = ".$id_unidad_fisica."
                    AND an_compra_unidad_checklist.id_checklist = an_checklist.id_checklist) as id_compra_unidad_checklist,
                
                (SELECT an_compra_unidad_checklist.valor from an_compra_unidad_checklist
                WHERE an_compra_unidad_checklist.id_unidad_fisica = ".$id_unidad_fisica."
                    AND an_compra_unidad_checklist.id_checklist = an_checklist.id_checklist) as valor
            
                from an_checklist
            WHERE parent = ".$rowcheck['id_checklist']."
                AND n_checklist = 0;";
            //echo $sqlsc;
            $rsc = mysql_query($sqlsc,$conex);
            if($rsc) {
                while($rowdata=mysql_fetch_assoc($rsc)) {
                    $idcheck=$rowdata['id_checklist'];
                    if($rowdata['tipo']==0){
                        echo sprintf('
                            <label for="check%s">
                                <div class="%s">
                                    <img border="0"  src="../../img/%s" title="" alt="" />%s
                                </div>
                            <input type="hidden" name="checkid[%s]" value="%s" /></label>',
                            $idcheck,$rowdata['clasecss'],
                            $checkvalue[$rowdata['valor']],$rowdata['texto'],
                            $idcheck,$rowdata['id_compra_unidad_checklist']
                        );
                    }
                }
            }
        }
        
        echo '</td></tr></tbody></table>';
    } else {
        echo 'error: '.mysql_error($rcheck);
        exit;
    }
} ?>
            
            </td>				
        </tr>
    </tbody>
	</table>
    
	<table class="hidden_table" cellspacing="0" style="margin-top:15px;">
    <tbody>
        <tr>
            <td width="50%" rowspan="2"><img border="0" src="../../img/texto_checklist_compra.jpg" style="height:100px;" /></td>
            <td width="30%" style="text-align:right; padding:3px;border-bottom:2px solid #000000;">&nbsp;
            </td>
            <td width="20%" style="vertical-align:bottom;text-align:center; padding:3px;border-bottom:2px solid #000000;">
            <?php echo htmlentities($factura['fecha_checklist']);?>
            </td>
        </tr>
        <tr>
            <td width="35%" nowrap="nowrap" style="text-align:center; padding:3px;vertical-align:top;">Nombre y Firma del Gerente de Servicios</td>
            <td width="15%" nowrap="nowrap" style="text-align:center; padding:3px;vertical-align:top;">Fecha</td>
        </tr>
    </tbody>
	</table>
</body>
</html>