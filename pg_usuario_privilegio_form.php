<?php
@session_start();

/* Validación del Módulo */
include('inc_sesion.php');
if(!(validaAcceso("pg_privilegio"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

//implementando xajax;
require_once("control/main_control.inc.php");
//require_once("servicios/control/funciones.inc.php");

$id_usuario = intval($_GET['id_usuario']);

function load_page($id_usuario, $id_empresa = NULL){
	$r = getResponse();
	
	if (!xvalidaAcceso($r,'pg_privilegio',"",true,NULL,NULL,NULL,false,"index2.php")){
		$r->assign('capa',inner,'Acceso denegado');
		$r->assign('boton_guardar','disabled',true);
		return $r;
	}
	
	$c = new connection();
	$c->open();
	
	// verificando la existencia de empresa, si no cargar la predeterminada de dicho usuario:
	$recuser = $c->pg_usuario->doSelect($c,new criteria(sqlEQUAL,$c->pg_usuario->id_usuario,$id_usuario));
	
	if ($id_empresa == NULL) {
		// BUSCA LA EMPRESA POR DEFECTO DEL USUARIO
		$queryUsuarioEmp = sprintf("SELECT * FROM pg_usuario_empresa usu_emp
		WHERE usu_emp.id_usuario = %s
			AND usu_emp.predeterminada = 1",
			valTpDato($id_usuario, "int"));
		$rsUsuarioEmp = mysql_query($queryUsuarioEmp);
		if (!$rsUsuarioEmp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowUsuarioEmp = mysql_fetch_assoc($rsUsuarioEmp);
		
		$id_empresa = $rowUsuarioEmp['id_empresa'];
	}
	$selId = $id_empresa;
	
	$r->assign('label_user',inner,'Usuario:');
	$r->assign('field_id_usuario',"value",$recuser->nombre_usuario);
	$r->assign('id_usuario','value',$id_usuario);
	$r->assign('id_empresa','value',$id_empresa);
	
	$queryUsuarioSuc = sprintf("SELECT DISTINCT
		id_empresa_reg,
		nombre_empresa
	FROM vw_iv_usuario_empresa
	WHERE id_usuario = %s
		AND id_empresa_padre_suc IS NULL
	ORDER BY nombre_empresa_suc ASC",
		valTpDato($id_usuario, "int"));
	$rsUsuarioSuc = mysql_query($queryUsuarioSuc);
	if (!$rsUsuarioSuc) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowUsuarioSuc = mysql_fetch_assoc($rsUsuarioSuc)) {
		$selected = ($selId == $rowUsuarioSuc['id_empresa_reg']) ? "selected=\"selected\"" : "";
	
		$htmlOption .= "<option ".$selected." value=\"".$rowUsuarioSuc['id_empresa_reg']."\">".htmlentities($rowUsuarioSuc['nombre_empresa'])."</option>";
	}
	
	$query = sprintf("SELECT DISTINCT
		id_empresa,
		nombre_empresa
	FROM vw_iv_usuario_empresa
	WHERE id_usuario = %s
		AND id_empresa_padre_suc IS NOT NULL
	ORDER BY nombre_empresa",
		valTpDato($id_usuario, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$htmlOption .= "<optgroup label=\"".$row['nombre_empresa']."\">";
		
		$queryUsuarioSuc = sprintf("SELECT DISTINCT
			id_empresa_reg,
			nombre_empresa_suc,
			sucursal
		FROM vw_iv_usuario_empresa
		WHERE id_usuario = %s
			AND id_empresa_padre_suc = %s
		ORDER BY nombre_empresa_suc ASC",
			valTpDato($id_usuario, "int"),
			valTpDato($row['id_empresa'], "int"));
		$rsUsuarioSuc = mysql_query($queryUsuarioSuc);
		if (!$rsUsuarioSuc) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowUsuarioSuc = mysql_fetch_assoc($rsUsuarioSuc)) {
			$selected = ($selId == $rowUsuarioSuc['id_empresa_reg']) ? "selected=\"selected\"" : "";
		
			$htmlOption .= "<option ".$selected." value=\"".$rowUsuarioSuc['id_empresa_reg']."\">".htmlentities($rowUsuarioSuc['nombre_empresa_suc'])."</option>";	
		}
	
		$htmlOption .= "</optgroup>";
	}
	
	$html = "<select id=\"id_empresa\" name=\"id_empresa\" class=\"inputHabilitado\" ".$disabled." onchange=\"cargar_priv(this.value);\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		$html .= $htmlOption;
	$html .= "</select>";
	
	$r->assign("field_id_empresa","innerHTML",$html);
	
	// datos
	/*$empresas = $c->sa_v_empresa_sucursal->doSelect($c,new criteria(sqlAND,array(
		new criteria(' <> ','id_empresa','100'),
		new criteria(' = ','sucursales','0')
	)))->getAssoc('id_empresa','nombre_empresa_sucursal');
	$estilo['onchange'] = 'cargar_priv(this.value);';
	// verificando si es usuario o perfil
	if ($recuser->perfil == 1) {
		$r->assign('label_user',inner,'Perfil:');
		$id_empresa = 0;
		$estilo['disabled'] = 'disabled';
	} else {
		$r->assign('label_user',inner,'Usuario:');
	}
	$r->assign('field_id_empresa',inner,inputSelect('id_empresa',$empresas,$id_empresa,$estilo,0));*/
	
	//OBTENIENDO LOS PRIVILEGIOS MAESTROS
	$rece = $c->pg_elemento_menu->doQuery($c, new criteria(sqlIS,$c->pg_elemento_menu->id_padre,sqlNULL))->orderBy($c->pg_elemento_menu->def_order)->doSelect();
	
	if ($rece) {
		foreach ($rece as $e) {
			$htmle .= getPrivilegios($c,$e,20,$id_usuario,$id_empresa);
		}
	}
	$htmle = "
	<div class=\"rowpriv_title\">
		<div class=\"privilegio\">Privilegios:<span2 style=\"\"><button type=\"button\" value=\"1\" onClick=\"seleccionarCheckbox(this,this.value);\" style=\"width:20px; height:20px; cursor:pointer; z-index:5000;\"><img  style=\"margin-top:-1px; margin-left:-1px;\" src=\"img/minselect.png\"/></button></span2></div>
		<div class=\"accion\">Acceso</div>
		<div class=\"accion\">Insertar</div>
		<div class=\"accion\">Editar</div>
		<div class=\"accion\">Eliminar</div>
		<div class=\"accion\">Desincorpor.</div>
	</div>
	".$htmle;
	
	$r->assign('capa',inner,$htmle);
	
	//iniciando recursión de privilegios principales:
	
	return $r;
}

//RECURSIVO:
function getPrivilegios(connection $c, recordset $priv, $level, $id_usuario, $id_empresa) {
	$event = '';
	$class = 'privilegio';
	//verificando existencia de nodos:
	$recnode = $c->pg_elemento_menu->doQuery($c, new criteria(sqlEQUAL,$c->pg_elemento_menu->id_padre,$priv->id_elemento_menu))->orderBy($c->pg_elemento_menu->def_order)->orderBy($c->pg_elemento_menu->nombre)->doSelect();
	if ($recnode) {
		if ($recnode->getNumRows() != 0) {
			if ($level == 20 || $level == 40) {
				$display = "display:none;";
				
				$sl = ($level == 20) ? '_p' : '_p2';
				$class = "menuprivilegio_close";
			} else {
				$class = "menuprivilegio";
			}
			$htmlFin .= '<div style="'.$display.'" id="container'.$priv->id_elemento_menu.'">';
			$event = 'onclick="switch_priv(this,'.$priv->id_elemento_menu.');"';
			//$class="menuprivilegio";
			foreach ($recnode as $e) {
				$htmlFin .= getPrivilegios($c,$e,$level+20,$id_usuario,$id_empresa);
			}
			$htmlFin .= '</div>';
		}
	}
	//leer los privilegiso del usuario por la empresa:
	$recu=$c->pg_menu_usuario->doSelect($c,new criteria(sqlAND,array(
		new criteria(sqlEQUAL,$c->pg_menu_usuario->id_usuario,$id_usuario),
		new criteria(sqlEQUAL,$c->pg_menu_usuario->id_empresa,"'".$id_empresa."'"),			
		new criteria(sqlEQUAL,$c->pg_menu_usuario->id_elemento_menu,$priv->id_elemento_menu)
	)));
	
	if ($recu) {
		if ($recu->getNumRows() != 0) {
			//leyendo data:
			$acceso = ($recu->acceso == 1) ? 'checked="checked"': '';
			$insertar = ($recu->insertar == 1) ? 'checked="checked"': '';
			$editar = ($recu->editar == 1) ? 'checked="checked"': '';
			$eliminar = ($recu->eliminar == 1) ? 'checked="checked"': '';
			$desincorporar = ($recu->desincorporar == 1) ? 'checked="checked"': '';
			$id_menu_usuario = $recu->id_menu_usuario;
		}
	}
	
	//contruyendo las acciones (si las lleva)
	$acciones .= '<input type="hidden" id="id_menu_usuario'.$priv->id_elemento_menu.'" name="id_menu_usuario['.$priv->id_elemento_menu.']" value="'.$id_menu_usuario.'"/>';
	
	$acciones .= "<div class=\"accion\">";
	if ($priv->acceso == 1) {
		$acciones .= '<label title="Acceso" for="acceso'.$priv->id_elemento_menu.'"><input id="acceso'.$priv->id_elemento_menu.'" name="acceso['.$priv->id_elemento_menu.']" type="checkbox" '.$acceso.' value="1"/></label>';
	} else {
		$acciones .= '&nbsp;';
	}
	$acciones .= "</div>";
	
	$acciones .= "<div class=\"accion\">";
	if ($priv->insercion == 1) {
		$acciones .= '<label title="Insertar" for="insertar'.$priv->id_elemento_menu.'"><input id="insertar'.$priv->id_elemento_menu.'" name="insertar['.$priv->id_elemento_menu.']" type="checkbox" '.$insertar.' value="1"/></label>';
	} else {
		$acciones .= '&nbsp;';
	}
	$acciones .= "</div>";
	
	$acciones .= "<div class=\"accion\">";
	if ($priv->edicion == 1) {
		$acciones .= '<label title="Editar" for="editar'.$priv->id_elemento_menu.'"><input id="editar'.$priv->id_elemento_menu.'" name="editar['.$priv->id_elemento_menu.']" type="checkbox" '.$editar.' value="1"/></label>';
	} else {
		$acciones .= '&nbsp;';
	}
	$acciones .= "</div>";
	
	$acciones .= "<div class=\"accion\">";
	if ($priv->eliminacion == 1) {
		$acciones .= '<label title="Eliminar" for="eliminar'.$priv->id_elemento_menu.'"><input id="eliminar'.$priv->id_elemento_menu.'" name="eliminar['.$priv->id_elemento_menu.']" type="checkbox" '.$eliminar.' value="1"/></label>';
	} else {			
		$acciones .= '&nbsp;';
	}
	$acciones .= "</div>";
	
	$acciones .= "<div class=\"accion\">";
	if ($priv->desincorporacion == 1) {
		$acciones .= '<label title="Desincorporar / Desautorizar" for="desincorporar'.$priv->id_elemento_menu.'"><input id="desincorporar'.$priv->id_elemento_menu.'" name="desincorporar['.$priv->id_elemento_menu.']" type="checkbox" '.$desincorporar.' value="1"/></label>';
	} else {
		$acciones .= '&nbsp;';
	}
	$acciones .= "</div>";
	
	// leyendo informacuión y creando contenedor:
	$html = "<div class=\"rowpriv".$sl."\">";
		$html .= "<div class=\"".$class."\" ".$event." style=\"background-position: ".($level-18)."px 50%;\">";
			$html .= "<span style=\"margin-left:".$level."px;\" title=\"".$priv->id_elemento_menu.") ".$priv->modulo."\">".$priv->nombre."</span>";
		$html .= "</div>";
		$html .= $acciones;
		$html .= "<span2 style=\"float:left; position:absolute; margin-left:-480px;\">";
			$html .= "<button type=\"button\" value=\"1\" onClick=\"seleccionarCheckbox(this,this.value);\" style=\"width:20px; height:20px; cursor:pointer; z-index:5000;\"><img style=\" margin-top:-1px; margin-left:-1px;\" src=\"img/minselect.png\"/></button>";
		$html .= "</span2>";
	$html .= "</div>".$htmlFin;
	
	return $html;
}

function guardar_privilegios($form){
	$r = getResponse();
	
	if ($form['id_empresa'] == "-1") {
		return $r->alert("Debe seleccionar alguna Empresa");
	}
	
	if (!xvalidaAcceso($r,'pg_privilegio',editar)) {
		$r->assign('capa',inner,'Acceso denegado');
		$r->assign('boton_guardar','disabled',true);
		return $r;
	}
	
	$id_usuario = $form['id_usuario'];
	
	//$r->alert(utf_export($form));
	$c = new connection();
	$c->open();
	$id_menu_usuario = $form['id_menu_usuario'];
	$c->begin();
	$error = false;
	foreach($id_menu_usuario as $idElementoMenu => $item) { // recorriendo la lista de privilegios
		// BUSCA LOS DATOS DEL ELEMENTO DEL MENU
		$query = sprintf("SELECT * FROM pg_elemento_menu
		WHERE id_elemento_menu = %s;",
			valTpDato($idElementoMenu, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $r->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_array($rs);
		
		// BUSCA LAS EMPRESAS ASIGNADAS AL USUARIO
		$queryUsuarioEmpresa = sprintf("SELECT * FROM pg_usuario_empresa WHERE id_usuario = %s;",
			valTpDato($id_usuario, "int"));
		$rsUsuarioEmpresa = mysql_query($queryUsuarioEmpresa);
		if (!$rsUsuarioEmpresa) return $r->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowUsuarioEmpresa = mysql_fetch_assoc($rsUsuarioEmpresa)) {
			$id_empresa = $rowUsuarioEmpresa['id_empresa'];
			
			// BUSCA A VER SI TIENE ASIGNADO LOS VALORES DEL ELEMENTO EN LAS EMPRESAS DEL USUARIO
			$queryMenuUsuario = sprintf("SELECT * FROM pg_menu_usuario
			WHERE id_usuario = %s
				AND id_empresa = %s
				AND id_elemento_menu = %s;",
				valTpDato($id_usuario, "int"),
				valTpDato($id_empresa, "int"),
				valTpDato($idElementoMenu, "int"));
			$rsMenuUsuario = mysql_query($queryMenuUsuario);
			if (!$rsMenuUsuario) return $r->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsMenuUsuario = mysql_num_rows($rsMenuUsuario);
			$rowMenuUsuario = mysql_fetch_array($rsMenuUsuario);
			
			$idMenuUsuario = $rowMenuUsuario['id_menu_usuario'];
			
			if ((intval($form['acceso'][$idElementoMenu]) == 1
			|| intval($form['insertar'][$idElementoMenu]) == 1 || intval($form['editar'][$idElementoMenu]) == 1
			|| intval($form['eliminar'][$idElementoMenu]) == 1 || intval($form['desincorporar'][$idElementoMenu]) == 1)
			|| $row['modulo'] == "") {
				if ($totalRowsMenuUsuario > 0) {
					$sql = "UPDATE pg_menu_usuario SET 
						id_usuario = %s,
						id_empresa = %s,
						id_elemento_menu = %s,
						acceso = %s,
						insertar = %s,
						editar = %s,
						eliminar = %s,
						desincorporar = %s
					WHERE id_menu_usuario = %s;";
				} else {
					$sql = "INSERT INTO pg_menu_usuario (id_usuario, id_empresa, id_elemento_menu, acceso, insertar, editar, eliminar, desincorporar, id_menu_usuario)
					VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s);";				
					$idMenuUsuario = "DEFAULT";
				}
				$csql = sprintf($sql,
					valTpDato($id_usuario, "int"),
					valTpDato($id_empresa, "int"),
					valTpDato($idElementoMenu, "int"),
					valTpDato($form['acceso'][$idElementoMenu], "boolean"),
					valTpDato($form['insertar'][$idElementoMenu], "boolean"),
					valTpDato($form['editar'][$idElementoMenu], "boolean"),
					valTpDato($form['eliminar'][$idElementoMenu], "boolean"),
					valTpDato($form['desincorporar'][$idElementoMenu], "boolean"),
					valTpDato($idMenuUsuario, "int"));
			} else {
				$csql = sprintf("DELETE FROM pg_menu_usuario 
				WHERE id_menu_usuario = %s;",
					valTpDato($idMenuUsuario, "int"));
			}
			$Result1 = mysql_query($csql);
			if (!$Result1) return $r->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
	}
	
	$c->commit();
	$r->script("
	obj('capa').innerHTML = 'Cargando...';
	alert('".utf8_encode("Se han guardado los privilegios del usuario con Éxito")."');
	xajax_load_page(".$id_usuario.",".$form['id_empresa'].");");
	$c->close();
	
	return $r;
}

xajaxRegister('load_page');
xajaxRegister('guardar_privilegios');
xajaxProcess();

includeDoctype();
?>

<html>
<head>
	<?php 
	includeMeta();
	includeScripts('control/');
	getXajaxJavascript('');
	//includeModalBox(); ?>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Privilegios del Usuario</title>

    <link rel="stylesheet" type="text/css" href="servicios/css/sa_general.css"/>
    <link rel="stylesheet" type="text/css" href="style/styleRafk.css">

    <link rel="stylesheet" type="text/css" href="js/domDragErp.css">
    
    <script>
	function switch_priv(content,id_priv){
		var objp=document.getElementById('container'+id_priv);
		//alert(objp);
		
		if(objp.style.display=='none'){
			objp.style.display='';
			content.className='menuprivilegio';
		}else{
			objp.style.display='none';
			content.className='menuprivilegio_close';
		}
	}
	
	function cargar_priv(empresa){
		document.getElementById('capa').innerHTML='Cargando...';
		if(empresa==0){
			alert('Especifique Empresa');
			empresa=null;
		}
		var user=document.getElementById('id_usuario').value;
		
		xajax_load_page(user,empresa);
	}
	
	function seleccionarCheckbox(referencia, auxiliar) {
		clase = $(referencia).parent().parent().attr('class');
	
		if(clase == "rowpriv"){//SOLO FILAS HORIZONTAL
			$(referencia).parent().parent().find(':checkbox').each(function () {//checkbox
			  if(auxiliar == 1) { $(this).attr('checked', 'checked'); } else { $(this).removeAttr('checked'); }
			});
			$(referencia).parent().parent().find(':button').each(function () {//boton
			  if(auxiliar == 1) { $(this).val(0) } else { $(this).val(1) }
			});	
		}else if(clase == "rowpriv_p2"){//SECCION MANTENIMIENTO-COMPRAS-VENTAS
			$(referencia).parent().parent().next("div").find(':checkbox').each(function () {//checkbox
			  if(auxiliar == 1) { $(this).attr('checked', 'checked'); } else { $(this).removeAttr('checked'); }
			});
			$(referencia).parent().parent().next("div").find(':button').each(function () {//boton
			  if(auxiliar == 1) { $(this).val(0) } else { $(this).val(1) }
			});	
		}else if(clase == "rowpriv_p"){//MODULO SERVICIOS-REPUESTOS-CAJA-CONTABILIDAD
			$(referencia).parent().parent().next("div").andSelf().find(':checkbox').each(function () {//checkbox
			  if(auxiliar == 1) { $(this).attr('checked', 'checked'); } else { $(this).removeAttr('checked'); }
			});	
			$(referencia).parent().parent().next("div").find(':button').each(function () {//boton
			  if(auxiliar == 1) { $(this).val(0) } else { $(this).val(1) }
			});	
		}else if(clase == "privilegio"){//TODOS
			$(referencia).parent().parent().parent().parent().find(':checkbox').each(function () {//checkbox
			  if(auxiliar == 1) { $(this).attr('checked', 'checked'); } else { $(this).removeAttr('checked'); }
			});	
			$(referencia).parent().parent().parent().parent().find(':button').each(function () {//boton
			  if(auxiliar == 1) { $(this).val(0) } else { $(this).val(1) }
			});	
		}
		
		if(auxiliar == 1){ $(referencia).val(0) } else { $(referencia).val(1) }
	}
    </script>
    
	<style type="text/css">
    .capa_priv {
        border-right:1px solid #4F5B67;
        border-bottom:1px solid #4F5B67;
        border-left:1px solid #4F5B67;
        margin:auto;
        width:990px;
    }
    
    .rowpriv, .rowpriv_p, .rowpriv_p2 {
        margin:auto;
        overflow:hidden;
        background:#F2F2F2;
        width:990px;
    }
    
    .rowpriv_p {
        background:#4F5B67;
        color:#FFFFFF;
    }
    
    .rowpriv_p2 {
        background:#2E3D56;/*#1D3356;*/
        color:#FFFFFF;
    }
    
    .rowpriv_title {
        margin:auto;
        overflow: auto;
        width:990px;
    }
    
    .rowpriv_title .accion, .rowpriv_title .privilegio {
        padding:1px;
        padding-top:5px;
        font-weight:bold;
        border-top:1px solid #4F5B67;
        height:auto;
    }
    
    .rowpriv_title .accion {
        background:#FE9900;
        color:#FFFFFF;
        font-size:10px;
        width:87px;
    }
    
    .rowpriv:hover, .rowpriv_p:hover, .rowpriv_p2:hover {
        background:#FFCC66;
        color:#000000;/*#FFFFFF;*/
    }
    
    .privilegio, .menuprivilegio, .menuprivilegio_close {
        float:left;
        padding:1px;
        text-align:left;
        padding-top:4px;
        border-top:1px solid #999999;
        min-height:18px;
        width:538px;
        /*background:lightblue;*/
    }
    
    .menuprivilegio {
        background-repeat:no-repeat;
        background-position: 0% 50%;
        background-image: url(img/iconos/minus.png);
    }
    
    .menuprivilegio_close {
        background-repeat:no-repeat;
        background-position: 0% 50%;
        background-image: url(img/iconos/plus.png);
    }
    
    .accion {
        float:left;
        text-align:center;
        border-top:1px solid #999999;
        border-left:1px solid #999999;
        min-height:22px;
        width:89px;
        /*background:yellow;*/
    }
    
    .accion label:hover {
        background:#FE9900;
    }
    
    .accion label, .accion label input[type=checkbox] {
        cursor:pointer;
    }
    
    .accion label {
        display:block;
        height:100%;
        padding:2px;
        width:100%;
    }
    
    .menuprivilegio, .menuprivilegio_close {
        cursor:pointer;
    }
    
    .menuprivilegio:hover, .menuprivilegio_close:hover {
        font-weight:bold;
        color:#000000;
    }
    </style>
</head>
<body class="bodyVehiculos">
<div id="divGeneralVehiculos">
    <div class="noprint"><?php include("sysgts_menu.inc.php"); ?></div>
    
	<div id="divInfo" class="print">
		<table align="center" border="0" width="100%">
		<tr>
			<td align="right" class="tituloPaginaErp">Privilegios del Usuario</td>
		</tr>
        <tr>
        	<td>
            <form id="formulario" name="formulario" onSubmit="return false;">
                <input type="hidden" name="id_usuario" id="id_usuario"/>
                <input type="hidden" name="id_empresa" id="id_empresa"/>
                <table>
                <tr align="left">
                    <td align="right" class="tituloCampo" id="label_user" width="120"></td>
                    <td class="field"><input type="text" id="field_id_usuario" name="field_id_usuario" readonly size="30" maxlength="50"/></td>
                    <td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td class="field" id="field_id_empresa"></td>
                    <td>
                        <button id="boton_guardar" onClick="xajax_guardar_privilegios(xajax.getFormValues('formulario'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
                        <button type="button" id="btnCancelar" name="btnCancelar" onClick="window.open('pg_usuario_list.php','_self');" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/ico_error.gif"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
                    </td>
                </tr>
                </table>
                
                <br/>
                
                <div id="capa" class="capa_priv">Cargando...</div>
            </form>
            </td>
        </tr>
		</table>
	</div>
    
	<div class="noprint"><?php include("pie_pagina_sin_xajax.php"); ?></div>
    
    <div class="aloader" id="load_animate">&nbsp;</div>
</div>
</body>
</html>

<script type="text/javascript" language="javascript">
xajax_load_page(<?php echo $id_usuario; ?>);

xajax.callback.global.onRequest = function() {
	//xajax.$('loading').style.display = 'block';
	obj('load_animate').style.display='';
}
xajax.callback.global.beforeResponseProcessing = function() {
	//xajax.$('loading').style.display='none';
	obj('load_animate').style.display='none';
}
</script>