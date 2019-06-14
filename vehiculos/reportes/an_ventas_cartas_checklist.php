<?php
require_once("../../connections/conex.php");

require_once("../../inc_sesion.php");

function check_image($val){
	if($val=='' || $val==0){
		return 'nocheck2.gif';
	}else{
		return 'check2.gif';
	}
}

$redirect="nobody.htm";
validaModulo("an_checklist");
if (!isset($_GET['view'])){
	validaModulo("an_checklist",editar);
}
cache_expires();
conectar();
$id_pedido=$_GET['id'];
if($id_pedido==''){
	echo 'No ha especificado el pedido';
	exit;
}
$sqlp = "select
	id_cliente,
	id_unidad_fisica,
	".mysqlfecha('fecha_entrega')." as cfecha_entrega,
	".mysqlfecha('check_fecha_cita')." as ccheck_fecha_cita,
	if(porcentaje_inicial=100,'contado','Cr&eacute;dito') as credito,id_empresa,
	concat(gerentev.apellido,' ',gerentev.nombre_empleado) as gerente,
	concat(asesorv.apellido,' ',asesorv.nombre_empleado) as asesor,
	gerentev.telefono as telefonogerente,
	asesorv.telefono as telefonoasesor,
	an_pedido.id_empresa as empresa
FROM an_pedido
	inner join pg_empleado as gerentev on(gerentev.id_empleado=an_pedido.gerente_ventas)
	inner join pg_empleado as asesorv on(asesorv.id_empleado=an_pedido.asesor_ventas)
WHERE id_pedido = ".$id_pedido.";";
$rp=mysql_query($sqlp,$conex);
if($rp){
	if (mysql_num_rows($rp) != 0) {
		$rowp = mysql_fetch_assoc($rp);
		
		$id_cliente = $rowp['id_cliente'];
		
		$sqlc = "SELECT
			CONCAT_WS(' ', nombre, apellido),
			telf
		FROM cj_cc_cliente
		WHERE id = ".$id_cliente.";";
		$rc = mysql_query($sqlc);
		if($rc){
			$rowc=mysql_fetch_row($rc);
		}
		
		$sql = "SELECT
			nombre_empresa,
			contribuyente_especial,
			logo_empresa,
			rif,
			direccion,
			telefono1,
			telefono2,
			telefono3,
			telefono4,
			correo,
			web,
			familia_empresa,
			telefono_asistencia,
			telefono_servicio,
			nombre_taller,
			direccion_taller,
			telefono_taller1,
			telefono_taller2,
			telefono_taller3,
			telefono_taller4,
			contactos_taller,
			nit,
			sucursal,
			fax,
			fax_taller,
			ciudad_empresa,
			nombre_asistencia,
			logo_familia
		FROM pg_empresa
		WHERE id_empresa = ".$rowp['id_empresa'].";";
		$r=mysql_query($sql,$conex);
		if($r){
			$rowa=mysql_fetch_assoc($r);
		}else{
			echo mysql_error($conex).' '.$sql;
			exit;
		}
			
		$sqlgspv = "SELECT
			CONCAT_WS(' ', nombre_empleado, apellido) AS nombres,
			telefono
		FROM pg_cargo
			INNER JOIN pg_cargo_departamento ON (pg_cargo.id_cargo = pg_cargo_departamento.id_cargo)
			INNER JOIN pg_departamento ON (pg_cargo_departamento.id_departamento = pg_departamento.id_departamento)
			INNER JOIN pg_empleado ON (pg_cargo_departamento.id_cargo_departamento = pg_empleado.id_cargo_departamento)
		WHERE clave_filtro = 4
			AND pg_empleado.activo = 1
			AND pg_departamento.id_empresa = ".$rowp['id_empresa'].";";
		$rpv=mysql_query($sqlgspv);
		if($rpv){
			$rowpv=mysql_fetch_assoc($rpv);
		}

		$sqlv = "SELECT
			serial_chasis,
			SUBSTRING(registro_legalizacion,-6) AS cert,
			".mysqlfecha('fecha_pago_venta')." AS vfecha_venta
		FROM an_unidad_fisica
		WHERE id_unidad_fisica = ".$rowp['id_unidad_fisica'].";";
		$rv=mysql_query($sqlv);
		if($rv){
			$rowv=mysql_fetch_assoc($rv);
		}
		
		$queryEmp = sprintf("SELECT empresa.*,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM vw_iv_empresas_sucursales vw_iv_emp_suc
			INNER JOIN pg_empresa empresa ON (vw_iv_emp_suc.id_empresa_reg = empresa.id_empresa)
		WHERE vw_iv_emp_suc.id_empresa_reg = %s",
			valTpDato($rowp['empresa'], "int"));
		$rsEmp = mysql_query($queryEmp, $conex) or die(mysql_error());
		$rowEmp = mysql_fetch_assoc($rsEmp);
	} else {
		echo 'No existe el registro';
		exit;
	}
} ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Formato de Entrega</title>
    
    <link rel="stylesheet" type="text/css" href="../../style/styleRafk.css">
    <link rel="stylesheet" href="../an_checklist_compra_style.css" />
	
	<style type="text/css">
    body{
        font-size:10px;
    }
    @media print{
        
        input{
            display: none;
        }
        .checkb{
            visibility:hidden;
        }
        
    }
    .firma{
        border-top:1px solid #000000;
        margin-top:50px;
    }
    .label,
    .label2{
        text-align:justify;
    }
    .check_titulo_hidden{
        display:none;
    }
    .check_titulo{
        border:1px solid #000000;
        padding:2px;
        text-align:center;
        margin-top:5px;
        margin-bottom:5px;
    }
    
    .check_data,
    .check_data_label_time{
        padding:0px;
        padding-left:30px;
        text-indent:-20px;
        text-align:justify;
    }
    .check_data input[type=checkbox]{
        /*float:left;*/
        display:inline;
        vertical-align:middle;
    }
    .check_data_label_time .checkimg{
        display:none;
    }
    
    .check_data_textarea{
        padding-left:10px;
        padding-right:10px;
        text-align:justify;
        text-decoration:underline;
        min-height:20px;
    }
    
    .check_data_textarea .checkimg{
        display:none;
    }
    
    .check_data:hover,
    .check_data_date:hover,
    .check_data_label_time:hover,
    .check_data_textarea:hover{
        background:#DFDFDF;
        cursor:pointer;
    }
    .check_data_date{
        padding:0px;
        padding-left:30px;	
        text-align:justify;
    }
    .checkimg{
        margin-right:10px;
        vertical-align:middle;
    }
    .datep{
        vertical-align:middle;
    }
    .hidden_data{
    }
    
    <?php //aplicando el estilo segun la situación
	if($_GET['view']!=''){
    	echo '
        .checkimg,
        .hidden_data{
            display: inline;
        }
        .checkb{
            visibility:hidden;
        }
        input{
            display:none;
        }
        .datep{
            display:none;		
        }
        .check_data,
        .check_data_label_time
        {
            padding:0px;
            padding-left:33px;
            text-indent:-33px;
        }
        .checkb{
            width:10px;
            height:10px;
            margin: 1px;
        }
        .check_data_label_time{
            font-size:8px;
            text-align:right;
        }
        .check_data_label_time span.hidden_data{
            text-decoration:underline;
        }';
	} else {
        echo '
        .checkimg,
        .hidden_data{
            display: none;
        }
        .checkb{
            visibility:visible;
        }';
	} ?>
    .td_infotitle{
        border-top:1px solid #000000;
        padding:1px;
        padding-bottom:5px;
    }
	.image_logo{
		max-width:135px;
		margin-bottom:10px;
	}
	.check_titulo2{
		padding:10px;
		text-align:center;
		font-weight:bold;
		font-size:12px;
	}
	.td_principal0{
		padding-right:10px;
	}
	.td_principal1{
		padding-left:10px;
	}
    </style>

    <link rel="stylesheet" href="../../js/calendar-green.css" />
    <script type="text/javascript" src="../vehiculos.inc.js"></script>
    <script type="text/javascript" src="../../js/calendar.js"></script>
    <script type="text/javascript" src="../../js/calendar-es.js"></script>
    <script type="text/javascript" src="../../js/calendar-setup.js"></script>
</head>

<body <?php if($_GET['view']=='print'){ echo ' onload="print();" '; }?>>
<form method="post" action="../an_ventas_checklist_guardar.php">
    <table border="0" width="100%">
    <tr>
        <td colspan="2">
            <table>
            <tr>
                <td><img src="../../<?php echo $rowEmp['logo_familia'];?>" height="90"></td>
                <td class="textoNegroNegrita_10px">
                    <table width="100%">
                    <tr align="left">
                        <td><?php echo htmlentities($rowEmp['nombre_empresa']); ?></td>
                    </tr>
                    <tr align="left">
                        <td><?php echo $spanRIF; ?>: <?php echo htmlentities($rowEmp['rif']); ?></td>
                    </tr>
                <?php if (strlen($rowEmp['direccion']) > 1) { ?>
                    <tr align="left">
                        <td>
                            <?php 
                            $direcEmpresa = $rowEmp['direccion'].".";
                            $telfEmpresa = "";
                            if (strlen($rowEmp['telefono1']) > 1) {
                                $telfEmpresa .= "Telf.: ".$rowEmp['telefono1'];
                            }
                            if (strlen($rowEmp['telefono2']) > 1) {
                                $telfEmpresa .= (strlen($telfEmpresa) > 0) ? " / " : "Telf.: ";
                                $telfEmpresa .= $rowEmp['telefono2'];
                            }
                            if (strlen($rowEmp['telefono3']) > 1) {
                                $telfEmpresa .= (strlen($telfEmpresa) > 0) ? " / " : "Telf.: ";
                                $telfEmpresa .= $rowEmp['telefono3'];
                            }
                            if (strlen($rowEmp['telefono4']) > 1) {
                                $telfEmpresa .= (strlen($telfEmpresa) > 0) ? " / " : "Telf.: ";
                                $telfEmpresa .= $rowEmp['telefono4'];
                            }
                            
                            echo htmlentities($direcEmpresa." ".$telfEmpresa); ?>
                         </td>
                    </tr>
                <?php } ?>
                    <tr align="left">
                        <td><?php echo htmlentities($rowEmp['web']); ?></td>
                    </tr>
                    </table>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td width="50%" valign="top" class="td_principal0">
            <table cellspacing="0" width="100%">
            <tr>
                <td colspan="2" valign="top">
                    <div class="check_titulo2">FORMATO DE ENTREGA</div>
                </td>
            </tr>
            <tr>
                <td class="td_infotitle" valign="top" nowrap="nowrap">Nombre del Cliente:</td>
                <td class="td_infotitle" valign="top" width="80%"><?php echo htmlentities($rowc[0]);?></td>
            </tr>
            <tr>
                <td class="td_infotitle" valign="top" nowrap="nowrap">Responsable de la Entrega:</td>
                <td class="td_infotitle" valign="top"><?php echo htmlentities($rowp['asesor']); ?></td>
            </tr>
            <tr>
                <td class="td_infotitle" valign="top" nowrap="nowrap" style="padding-bottom:20px;">VIN del Veh&iacute;culo:</td>
                <td class="td_infotitle" valign="top"><?php echo htmlentities($rowv['cert']);?></td>
            </tr>
            </table>
    <?php
    //obteniendo datos para la impresión del checklist de COMPRAS
    //primer nodo:
    
    //obtener la unidad fisica
    //$id_pedido=1;
    if ($id_pedido != "") {
        //valores para el check:
        $checkvalue = array("0"=>"","1"=>'checked="checked"',""=>"");
        //$checkimage=array("0"=>"nocheck.gif","1"=>'check.gif');
        @session_start();
        //echo $_SESSION['session_empresa'];
        $sqlcheck = sprintf("SELECT * FROM an_checklist
        WHERE ISNULL(parent)
            AND tipo = 1
            AND n_checklist = 1
            AND (id_empresa = %s
				OR %s IN (SELECT suc.id_empresa FROM pg_empresa suc
						WHERE suc.id_empresa_padre = an_checklist.id_empresa))
        ORDER BY columna ASC, order_level ASC;",
			valTpDato(getempresa(), "int"),
			valTpDato(getempresa(), "int"));
        //echo $sqlcheck;
        $rcheck = @mysql_query($sqlcheck,$conex);
        if ($rcheck) {
            echo '<input type="hidden" name="id_pedido" value="'.$id_pedido.'" />';
            $lastcolumn = 0;
            while ($rowcheck = mysql_fetch_assoc($rcheck)) {
            
                //imprimiendo:
                if ($lastcolumn != $rowcheck['columna']) {
                    echo '</td><td width="50%" valign="top" class="td_principal'.$rowcheck['columna'].'">';
                    $lastcolumn=$rowcheck['columna'];
                }
                
                echo sprintf('<div class="%s">%s</div>',
                    $rowcheck['clasecss'],
                    $rowcheck['texto']);
                
                //extrayendo las subordinadas
                /*$sqlsc="select 
                    an_checklist.*,
                    an_pedido_checklist. id_pedido_checklist as id_pedido_checklist,
                    an_pedido_checklist.valor as valor,
                    DATE_FORMAT(an_pedido_checklist.valor,'".spanDatePick."') as valor_date,
                    DATE_FORMAT(an_pedido_checklist.valor,'".spanDatePick." %h:%i %p') as valor_datetime
                FROM an_checklist left join an_pedido_checklist on(an_checklist.id_checklist=an_pedido_checklist.id_dato_lista) 
                WHERE parent=".$rowcheck['id_checklist']." and n_checklist=1 and
                    (an_pedido_checklist.id_pedido=".$id_pedido." or isnull(an_pedido_checklist.id_pedido))
                    order by an_checklist.order_level ASC;";*/
                $sqlsc = "SELECT 
                    an_checklist.*,
                    (SELECT id_pedido_checklist FROM an_pedido_checklist
					WHERE id_pedido = ".valTpDato($id_pedido, "int")."
						AND id_dato_lista = an_checklist.id_checklist) AS id_pedido_checklist,
                    @valorf := (select valor FROM an_pedido_checklist
								WHERE id_pedido = ".valTpDato($id_pedido, "int")."
									AND id_dato_lista = an_checklist.id_checklist) AS valor,
                    DATE_FORMAT(@valorf,'".spanDatePick."') AS valor_date,
                    DATE_FORMAT(@valorf,'".spanDatePick." %h:%i %p') AS valor_datetime
                FROM an_checklist 
                WHERE parent = ".valTpDato($rowcheck['id_checklist'], "int")."
                    AND n_checklist = 1
                    AND (id_empresa = ".valTpDato(getempresa(), "int")."
						OR ".valTpDato(getempresa(), "int")." IN (SELECT suc.id_empresa FROM pg_empresa suc
								WHERE suc.id_empresa_padre = an_checklist.id_empresa))
                ORDER BY an_checklist.order_level ASC;";
                //echo $sqlsc;
                $rsc = mysql_query($sqlsc,$conex);
                if ($rsc) {
                    while ($rowdata = mysql_fetch_assoc($rsc)) {
                        $idcheck = $rowdata['id_checklist'];
                        if ($rowdata['tipo'] == 0) { // CHECK
                            echo sprintf('
                                <label for="check%s"><div class="%s">
                                    
                                    <input class="checkb" type="checkbox" name="check[%s]" value="1" id="check%s" %s style="vertical-align:middle;" /><img class="checkimg" border="0"  src="../../img/%s" title="" alt="" />%s
                                    
                                </div>
                                <input type="hidden" name="checkid[%s]" value="%s" />
                                <input type="hidden" name="typeid[%s]" value="0" />
                                </label>',
                                $idcheck,$rowdata['clasecss'],
                                $idcheck,$idcheck,$checkvalue[$rowdata['valor']],check_image($rowdata['valor']),
                                $rowdata['texto'],
                                $idcheck,$rowdata['id_pedido_checklist'],$idcheck);
                        } elseif ($rowdata['tipo'] == 2) { // DATE
                            //$rowdata['valor_date'];
                            if ($rowdata['valor'] != '') {
                                $realcheck = $checkvalue[1];
                            } else {
                                $realcheck = '';
                            }
                            echo sprintf('
                                <label for="checke%s" title="Haga click en el calendario para cambiar" ><div class="%s">
                                
                                    <input class="checkb" onclick="var ob=document.getElementById(\'check%s\');if(!this.checked){ob.value=\'\';} if(ob.value==\'\'){this.checked=false;};" type="checkbox" id="checke%s" %s style="vertical-align:middle;" /><img class="checkimg" border="0"  src="../../img/%s" title="" alt="" />%s
                                    
                                    
                                    <input type="text" title="Haga click en el calendario para cambiar" readonly="readonly" name="check[%s]" id="check%s" value="%s" style="vertical-align:middle;" /><span class="hidden_data">%s</span>
                                    <img class="datep" id="date%s" src="../../img/select_date.png" border="0" />
                                    <script type="text/Javascript" language="javascript">
                                    Calendar.setup({
                                        inputField : "check%s", // id del campo de texto
                                        ifFormat : "%s", // formato de la fecha que se escriba en el campo de texto
                                        button : "date%s", // el id del botón que lanzará el calendario,
                                        position: Array(0,800),
                                        onSelect: function(calendar,date){
                                            if(calendar.dateClicked) {
                                                document.getElementById("checke%s").checked=true;
                                                document.getElementById("check%s").value=date;
                                                calendar.hide();
                                            }
                                        }
                                    });
                                    </script>
                                    
                                </div>
                                <input type="hidden" name="checkid[%s]" value="%s" />
                                <input type="hidden" name="typeid[%s]" value="2" />
                                </label>',
                                $idcheck,$rowdata['clasecss'],
                                $idcheck,
                                $idcheck,$realcheck,check_image($rowdata['valor']),$rowdata['texto'],
                                $idcheck,$idcheck,$rowdata['valor_date'],$rowdata['valor_date'],
                                $idcheck,
                                $idcheck,spanDatePick,$idcheck,$idcheck,$idcheck,
                                $idcheck,$rowdata['id_pedido_checklist'],$idcheck);
                        } elseif ($rowdata['tipo'] == 3) { // DATETIME
                            //$rowdata['valor_date'];
                            if ($rowdata['valor'] != '') {
                                $realcheck=$checkvalue[1];
                            } else {
                                $realcheck='';
                            }
                            echo sprintf('
                                <label for="checke%s" title="Haga click en el calendario para cambiar"><div class="%s">
                                
                                    <input class="checkb" onclick="var ob=document.getElementById(\'check%s\');if(!this.checked){ob.value=\'\';} if(ob.value==\'\'){this.checked=false;};" type="checkbox" id="checke%s" %s style="vertical-align:middle;" /><img class="checkimg" border="0"  src="../../img/%s" title="" alt="" />%s
                                    
                                    
                                    <input type="text" title="Haga click en el calendario para cambiar" readonly="readonly" name="check[%s]" id="check%s" value="%s" style="vertical-align:middle;" /><span class="hidden_data">%s</span>
                                    <img class="datep" id="date%s" src="../../img/select_date.png" border="0" />
                                    <script type="text/Javascript" language="javascript">
                                    Calendar.setup({
                                        inputField : "check%s", // id del campo de texto
                                        ifFormat : "%s", // formato de la fecha que se escriba en el campo de texto
                                        button : "date%s", // el id del botón que lanzará el calendario,
                                        onSelect: function(calendar,date){
                                            if(calendar.dateClicked) {
                                                document.getElementById("checke%s").checked=true;
                                                document.getElementById("check%s").value=date;
                                                calendar.hide();
                                            }
                                        },
                                        showsTime : true,
                                        timeFormat : "12"
                                    });
                                    </script>
                                    
                                </div>
                                <input type="hidden" name="checkid[%s]" value="%s" />
                                <input type="hidden" name="typeid[%s]" value="3" />
                                </label>',
                                $idcheck,$rowdata['clasecss'],
                                $idcheck,
                                $idcheck,$realcheck,check_image($rowdata['valor']),$rowdata['texto'],
                                $idcheck,$idcheck,$rowdata['valor_datetime'],$rowdata['valor_datetime'],
                                $idcheck,
                                $idcheck,spanDatePick.' %I:%M %p',$idcheck,$idcheck,$idcheck,
                                $idcheck,$rowdata['id_pedido_checklist'],$idcheck);
                        } elseif ($rowdata['tipo'] == 4) { // TEXT
                            //$rowdata['valor_date'];
                            if ($rowdata['valor'] != '') {
                                $realcheck = $checkvalue[1];
                            } else {
                                $realcheck = '';
                            }
                            echo sprintf('
                                <label for="checke%s" title="Escriba en el campo de texto para cambiar" ><div class="%s">
                                
                                    <input class="checkb" onclick="var ob=document.getElementById(\'check%s\');if(!this.checked){ob.value=\'\';} if(ob.value==\'\'){this.checked=false;};" type="checkbox" id="checke%s" %s style="vertical-align:middle;" /><img class="checkimg" border="0"  src="../../img/%s" title="" alt="" />%s
                                    
                                    
                                    <input onchange="if(this.value!=\'\'){document.getElementById(\'checke%s\').checked=true;};" type="text" name="check[%s]" id="check%s" value="%s" style="vertical-align:middle;" /><span class="hidden_data">%s</span>
                                    
                                    
                                                                        
                                </div>
                                <input type="hidden" name="checkid[%s]" value="%s" />
                                <input type="hidden" name="typeid[%s]" value="4" />
                                </label>',
                                $idcheck, $rowdata['clasecss'],
                                $idcheck,
                                $idcheck, $realcheck, check_image($rowdata['valor']), $rowdata['texto'],
                                $idcheck, $idcheck, $idcheck, utf8_encode($rowdata['valor']), utf8_encode($rowdata['valor']),
                                //$idcheck,
                                //$idcheck, spanDatePick." %I:%M %p", $idcheck, $idcheck, $idcheck,
                                $idcheck, $rowdata['id_pedido_checklist'], $idcheck);
                        }
                    }
                }
            }
        } else {
            echo 'error: '.mysql_error($rcheck);
            exit;
        }
    } ?>
            <table cellspacing="0"  width="100%">
            <tr>
                <td colspan="3" style="padding-top:50px;">&nbsp;</td>
            </tr>
            <tr>
                <td class="td_infotitle">Firma del Cliente</td>
                <td class="td_infotitle">Fecha:</td>
                <td class="td_infotitle"><?php echo htmlentities($rowp['ccheck_fecha_cita']); ?></td>
            </tr>
            </table>
        </td>
    </tr>
    </table>
<input type="submit" value="Guardar" />
</form>
</body>
</html>