<?php
//show columns from pg_parametros_empresas where field='descripcion_parametro';
/*
ALTER TABLE `pg_parametros_empresas` ADD `aplica_modulos` VARCHAR( 30 ) NULL COMMENT 'id de modulos separados por coma para saber si el parametro aplica (solo para el manteniemiento';
ALTER TABLE `pg_parametros_empresas` ADD `descripcion_user` LONGTEXT NOT NULL COMMENT 'descripcion para el usuario';
INSERT INTO `erp_cxp_repuestos_alt`.`pg_elemento_menu` (
`id_elemento_menu` ,
`modulo` ,
`nombre` ,
`id_padre` ,
`tipo` ,
`acceso` ,
`insercion` ,
`edicion` ,
`eliminacion` ,
`desincorporacion` ,
`def_order`
)
VALUES (
'15000', 'sa_parametros_generales', 'Parametros Generales', '55', '0', '1', NULL , '1', NULL , NULL , '0'
);
*/
@session_start();
define('PAGE_PRIV','pg_parametros_generales_list');
require_once("inc_sesion.php");

//implementando xajax;
	require_once("control/main_control.inc.php");
	require_once("control/iforms.inc.php");
	require_once("servicios/control/funciones.inc.php");
	include("control/xajax_dialogo_cliente.inc.php"); //incluir la busqueda/registro del cliente
	require_once("inc_sesion.php");
	
	function cargar($form=''){
		$r = getResponse();
		if (!xvalidaAcceso($r,PAGE_PRIV)){
			$r->assign('capa',inner,'Acceso denegado');
			return $r;
		}
		if($form==''){
			return $r;
		}
		$c= new connection();
		$c->open();
		//leyendo los valores
		$crit = new criteria(sqlAND,array(
			new criteria(sqlEQUAL,'id_empresa',$form['id_empresa']),
			new criteria(sqlEQUAL,'id_modulo',$form['id_modulo']),
			new criteria(sqlEQUAL,'(descripcion_parametro-1)',$form['id_parametro'])
		));
		$q=$c->pg_parametros_empresas->doQuery($c,$crit);
		$rec= $q->doSelect();
		//$r->alert($q->getSelect());
		if($rec){
			//revisando si aplica al modulo:
			$rec2=$c->pg_parametros_info->doSelect($c, new criteria(sqlEQUAL,'(id_parametro-1)',$form['id_parametro']));
			$varapl=explode(',',$rec2->aplica_modulos);
			//$r->alert(utf_export($varapl).' d:'.$rec2->aplica_modulos.' '.$form['id_parametro']);
			$aplica=((array_search($form['id_modulo'],$varapl))!==false);
			
			$r->assign('info',inner,$rec2->info);
			$r->assign('id_param',value,ifnull($rec->id_parametro));
			if($aplica){
				$r->assign('valor','value',$rec->valor_parametro);
			}else{
				$r->script('
					var valor= obj("valor");
					valor.disabled=true;
					valor.value="NO APLICA";
					obj("guardar").disabled=true;
				');
			}
		}
		return $r;
	}
	
	function guardar($form){
		$r= getResponse();
		
		if($form['valor']==''){
			$r->script('
				_alert("Especifique el valor a guardar");
				obj("valor").focus();
			');
			return $r;
		}
		$c= new connection();
		$c->open();
		if (!xvalidaAcceso($r,PAGE_PRIV,editar)){
			$c->rollback();
			return $r;
		}
		$rec2=$c->pg_parametros_info->doSelect($c, new criteria(sqlEQUAL,'(id_parametro-1)',$form['id_parametro']));
		//Guardando
		$c->begin();
		//creando al tabla para guardar:
		$tabla = new table("pg_parametros_empresas");
		
		$tabla->insert('id_parametro',$form['id_param']);		
		$tabla->insert('descripcion_parametro',($form['id_parametro']+1));
		$tabla->insert('id_empresa',$form['id_empresa']);
		$tabla->insert('id_modulo',$form['id_modulo']);
		//$r->alert(utf_export($form));
		$tabla->insert('valor_parametro',$form['valor'],$rec2->tipo);
		
		if($form['id_param']==''){
			$result= $tabla->doInsert($c, $tabla->id_parametro);
		}else{
			$result= $tabla->doUpdate($c,$tabla->id_parametro);
		}
		
		if($result===true){
			$r->script('_alert("Guardado con &Eacute;xito");recargar();');
			$c->commit();
		}else{
			$c->rollback();
			foreach ($result as $ex){
				if($ex->type==errorMessage::errorNOTNULL){					
						//$r->alert('obj("'.$ex->getObject()->getName().'").className="inputNOTNULL";');
					//$r->script('$("#field_'.$ex->getObject()->getName().'").addClass("inputNOTNULL");');
				}elseif($ex->type==errorMessage::errorType){
					//$r->script('obj("'.$ex->getObject()->getName().'").className="inputERROR";');
					//$r->script('$("#field_'.$ex->getObject()->getName().'").addClass("inputERROR");');
					$r->alert('Valor Incorrecto');
				}else{
					if($ex->numero==connection::errorUnikeKey){
						$r->script('_alert("Duplicado, no se puede guardar");');
						return $r;
					}else{
						$r->alert($ex->getMessage());
					}
				}
			}
		}
		return $r;
	}
	
	xajaxRegister('cargar');
	xajaxRegister('guardar');
	xajaxProcess();
	
	includeDoctype();
	$c= new connection();
	$c->open();
	//llenando lo necesario
	//$empresas=$c->pg_empresa->doSelect($c)->getAssoc($c->pg_empresa->id_empresa,$c->pg_empresa->nombre_empresa);
	$empresas=getEmpresaList($c);
	$modulos=array(
		'0'=>'Repuestos',
		'1'=>'Servicios',
		'2'=>'veh&iacute;culos',
		'3'=>'Administrativo'
	);
	$params=$c->soQuery("show columns from pg_parametros_empresas where field='descripcion_parametro';");
	$pp= $c->soFetch($params);
	$ps=$pp['Type'];
	$vars= explode(",",str_replace("'","",substr($ps,5,strlen($ps)-6)));
	
	
	$c->close();
?>

<html>
<head>
	<?php 
    includeMeta();
    includeScripts('control/');
    getXajaxJavascript('');
    //includeModalBox();
    ?>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Par&aacute;metros Generales</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
        
    <link rel="stylesheet" type="text/css" href="style/styleRafk.css"/>
    <link rel="stylesheet" type="text/css" href="js/domDragErp.css">
    <link rel="stylesheet" type="text/css" href="servicios/css/sa_general.css" />
    
    <style type="text/css">
	button img{
		padding-right:1px;
		padding-left:1px;
		padding-bottom:1px;
		vertical-align:middle;
	}
	.order_table tbody tr:hover,
	.order_table tbody tr.impar,
	{
		cursor:default;
	}
	.order_table tbody tr:hover img,
	.order_table tbody tr.impar img,
	{
		cursor:pointer;
	}
    </style>
    
    <script type="text/javascript">
	detectEditWindows({parametros:'guardar'},true);
	
	function recargar(){
		var empresa=obj('id_empresa');
		var modulo=obj('id_modulo');
		var parametro = obj('id_parametro');
		var valor= obj('valor');
		if(empresa.value!='' && modulo.value!='' && parametro.value!=''){
			xajax_cargar(xajax.getFormValues('parametros'));
			valor.disabled=false;
		}else{
			valor.disabled=true;
		}
		obj('guardar').disabled=valor.disabled;
	}
    </script>
</head>
<body class="bodyVehiculos">
<div id="divGeneralVehiculos">
    <div class="noprint"><?php include("sysgts_menu.inc.php"); ?></div>

    <div id="divInfo" class="print">
        <table align="center" border="0" width="100%">
        <tr>
            <td class="tituloPaginaErp">Par&aacute;metros Generales</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>
            <form id="parametros" name="parametros" onSubmit="return false;">
                <input type="hidden" id="id_param" name="id_param" />
                <table id="capa">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td><?php echo inputSelect('id_empresa',$empresas,null,'onchange=recargar();'); ?></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">M&oacute;dulo:</td>
                    <td><?php echo inputSelect('id_modulo',$modulos,'1','onchange=recargar();'); ?></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Par&aacute;metro:</td>
                    <td><?php echo inputSelect('id_parametro',$vars,'-1','onchange=recargar();'); ?></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Valor:</td>
                    <td><input type="text" id="valor" name="valor" disabled="disabled" /></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Descripc&oacute;n:</td>
                    <td id="info">&nbsp;</td>
                </tr>
                <tr>
                    <td align="right" colspan="2"><hr>
                        <button disabled="disabled" id="guardar" onClick="xajax_guardar(xajax.getFormValues('parametros'));"><img alt="Guardar" src="img/iconos/save.png" /> Guardar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        </table>
    </div>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<script type="text/javascript" language="javascript">
xajax_cargar(null);
</script>