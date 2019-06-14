<?php
@session_start();

/* Validación del Módulo */
include('inc_sesion.php');
if(!(validaAcceso("pg_perfil_privilegio_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

//Implementando xajax;
require_once("control/main_control.inc.php");
//require_once("servicios/control/funciones.inc.php");

$idPerfil = intval($_GET['idPerfil']);

function load_page($idPerfil) {
	$r = getResponse();
	if (!xvalidaAcceso($r,'pg_perfil_privilegio_list',"",true,NULL,NULL,NULL,false,"index2.php")){
		//$r->alert('acceso denegado');
		$r->assign('capa',inner,'Acceso denegado');
		$r->assign('boton_guardar','disabled',true);
		return $r;
	}
	
	$c = new connection();
	$c->open();
	//verificando la existencia de empresa, si no cargar la predeterminada de dicho usuario:
	$recuser = $c->pg_perfil->doSelect($c,new criteria(sqlEQUAL,$c->pg_perfil->id_perfil,$idPerfil));
	
	//datos:
	$r->assign('tdNombrePerfil',"value",$recuser->nombre_perfil);
	$r->assign('hddIdPerfil','value',$idPerfil);
	
	$estilo['onchange'] = 'cargar_priv(this.value);';
	$estilo['disabled'] = 'disabled';
	
	//OBTENIENDO LOS PRIVILEGIOS MAESTROS
	$rece=$c->pg_elemento_menu->doQuery($c, new criteria(sqlIS,$c->pg_elemento_menu->id_padre,sqlNULL))->orderBy($c->pg_elemento_menu->def_order)->doSelect();
	
	if ($rece) {
		foreach($rece as $e){
			$htmle.=getPrivilegios($c,$e,20,$idPerfil);
		}
	}
	$htmle = "
	<div class=\"rowpriv_title\">
		<div class=\"privilegio\">Privilegios:<span2 style=\"\"><button type=\"button\" value=\"1\" onClick=\"seleccionarCheckbox(this,this.value);\" style=\"width:20px; height:20px; cursor:pointer; z-index:5000;\"><img  style=\"margin-top:-1px; margin-left:-1px;\" src=\"img/minselect.png\"/></button></div>
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
function getPrivilegios(connection $c, recordset $priv, $level, $idPerfil) {
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
				$htmlFin .= getPrivilegios($c,$e,$level+20,$idPerfil);
			}
			$htmlFin .= '</div>';
		}
	}
	
	//leer los privilegiso del perfil
	$recu=$c->pg_perfil_menu->doSelect($c,new criteria(sqlAND,array(
		new criteria(sqlEQUAL,$c->pg_perfil_menu->id_perfil,$idPerfil),	
		new criteria(sqlEQUAL,$c->pg_perfil_menu->id_elemento_menu,$priv->id_elemento_menu)
	)));
	
	
	if ($recu) {
		if ($recu->getNumRows() != 0) {
			// leyendo data:
			$acceso = ($recu->acceso == 1) ? 'checked="checked"' : '';
			$insertar = ($recu->insertar == 1) ? 'checked="checked"' : '';
			$editar = ($recu->editar == 1) ? 'checked="checked"' : '';
			$eliminar = ($recu->eliminar == 1) ? 'checked="checked"' : '';
			$desincorporar = ($recu->desincorporar == 1) ? 'checked="checked"' : '';
			$idPerfilMenu = $recu->id_perfil_menu;
		}
	}
	
	// contruyendo las acciones (si las lleva)
	$acciones .= '<input type="hidden" id="hddIdPerfilMenu'.$priv->id_elemento_menu.'" name="hddIdPerfilMenu['.$priv->id_elemento_menu.']" value="'.$idPerfilMenu.'" />';
	
	$acciones .= "<div class=\"accion\">";
	if ($priv->acceso == 1) {
		$acciones .= '<label title="Acceso" for="acceso'.$priv->id_elemento_menu.'"><input id="acceso'.$priv->id_elemento_menu.'" name="acceso['.$priv->id_elemento_menu.']" type="checkbox" '.$acceso.' value="1" /></label>';
	} else {
		$acciones .= '&nbsp;';
	}
	$acciones .= "</div>";
	
	$acciones .= "<div class=\"accion\">";
	if ($priv->insercion == 1) {
		$acciones .= '<label title="Insertar" for="insertar'.$priv->id_elemento_menu.'"><input id="insertar'.$priv->id_elemento_menu.'" name="insertar['.$priv->id_elemento_menu.']" type="checkbox" '.$insertar.' value="1" /></label>';
	} else {
		$acciones .= '&nbsp;';
	}
	$acciones .= "</div>";
	
	$acciones .= "<div class=\"accion\">";
	if ($priv->edicion == 1) {
		$acciones .= '<label title="Editar" for="editar'.$priv->id_elemento_menu.'"><input id="editar'.$priv->id_elemento_menu.'" name="editar['.$priv->id_elemento_menu.']" type="checkbox" '.$editar.' value="1" /></label>';
	} else {
		$acciones .= '&nbsp;';
	}
	$acciones .= "</div>";
	
	$acciones .= "<div class=\"accion\">";
	if ($priv->eliminacion == 1) {
		$acciones .= '<label title="Eliminar" for="eliminar'.$priv->id_elemento_menu.'"><input id="eliminar'.$priv->id_elemento_menu.'" name="eliminar['.$priv->id_elemento_menu.']" type="checkbox" '.$eliminar.' value="1" /></label>';
	} else {			
		$acciones .= '&nbsp;';
	}
	$acciones .= "</div>";
	
	$acciones .= "<div class=\"accion\">";
	if ($priv->desincorporacion == 1) {
		$acciones .= '<label title="Desincorporar / Desautorizar" for="desincorporar'.$priv->id_elemento_menu.'"><input id="desincorporar'.$priv->id_elemento_menu.'" name="desincorporar['.$priv->id_elemento_menu.']" type="checkbox" '.$desincorporar.' value="1" /></label>';
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
	
	if (!xvalidaAcceso($r,'pg_perfil_privilegio_list',editar)){
		//$r->alert('acceso denegado');
		$r->assign('capa',inner,'Acceso denegado');
		$r->assign('boton_guardar','disabled',true);
		return $r;
	}
	
	$id_usuario = $form['hddIdPerfil'];
	
	$c= new connection();
	$c->open();
	//$r->alert(utf_export($form));
	//creocrriendo la lista de privilegios
	$id_menu_usuario = $form['hddIdPerfilMenu'];
	$c->begin();
	$error = false;
	foreach($id_menu_usuario as $idElementoMenu => $item) {
		// BUSCA LOS DATOS DEL ELEMENTO DEL MENU
		$query = sprintf("SELECT * FROM pg_elemento_menu
		WHERE id_elemento_menu = %s;",
			valTpDato($idElementoMenu, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $r->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_array($rs);
			
		// BUSCA A VER SI TIENE ASIGNADO LOS VALORES DEL ELEMENTO
		$queryMenuUsuario = sprintf("SELECT * FROM pg_perfil_menu
		WHERE id_perfil = %s
			AND id_elemento_menu = %s;",
			valTpDato($id_usuario, "int"),
			valTpDato($idElementoMenu, "int"));
		$rsMenuUsuario = mysql_query($queryMenuUsuario);
		if (!$rsMenuUsuario) return $r->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRowsMenuUsuario = mysql_num_rows($rsMenuUsuario);
		$rowMenuUsuario = mysql_fetch_array($rsMenuUsuario);
		
		if ((intval($form['acceso'][$idElementoMenu]) == 1
		|| intval($form['insertar'][$idElementoMenu]) == 1 || intval($form['editar'][$idElementoMenu]) == 1
		|| intval($form['eliminar'][$idElementoMenu]) == 1 || intval($form['desincorporar'][$idElementoMenu]) == 1)
		|| $row['modulo'] == "") {
			if ($totalRowsMenuUsuario == 0 || $item == '') {
				$sql = "INSERT INTO pg_perfil_menu (id_perfil, id_elemento_menu, acceso, insertar, editar, eliminar, desincorporar, id_perfil_menu)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s);";				
				$sitem = 'default';
				if ($optional_script == '') {
					$optional_script = 'obj("capa").innerHTML = "Cargando...";';
					$optional_script1 = "xajax_load_page(".$id_usuario.");";
				}
			} else {
				$sql = "UPDATE pg_perfil_menu SET 
					id_perfil = %s,
					id_elemento_menu = %s,
					acceso = %s,
					insertar = %s,
					editar = %s,
					eliminar = %s,
					desincorporar = %s
				WHERE id_perfil_menu = %s;";
				$sitem = $item;
			}
			$csql = sprintf($sql,
				valTpDato($id_usuario, "int"),
				valTpDato($idElementoMenu, "int"),
				valTpDato($form['acceso'][$idElementoMenu], "boolean"),
				valTpDato($form['insertar'][$idElementoMenu], "boolean"),
				valTpDato($form['editar'][$idElementoMenu], "boolean"),
				valTpDato($form['eliminar'][$idElementoMenu], "boolean"),
				valTpDato($form['desincorporar'][$idElementoMenu], "boolean"),
				valTpDato($sitem, "int"));
		} else {
			$csql = sprintf("DELETE FROM pg_perfil_menu 
			WHERE id_perfil_menu = %s;",
				valTpDato($rowMenuUsuario['id_perfil_menu'], "int"));
		}
		
		if ($csql != '' && !$error){
			//$r->alert($sql);
			$resultd = $c->soQuery($csql);
			if(!$resultd) {
				//$r->alert('error');
				$error=true;
			}
		}			
	}
	
	if ($error) {
		$r->alert('ERROR');
	} else {
		$c->commit();
		$r->script($optional_script.'
		_alert("Se han guardado los privilegios del usuario con &Eacute;xito");
		'.$optional_script1);
		$c->close();
	}
	
	return $r;
}


xajaxRegister('load_page');
xajaxRegister('guardar_privilegios');
/*xajaxRegister('listar_citas');
xajaxRegister('cargar_cita');
xajaxRegister('cargar_cliente_pago');
xajaxRegister('guardar_vale');*/

xajaxProcess();

///	$c= new connection();
//$c->open();

/*$tipos_orden=$c->sa_tipo_orden->doSelect($c)->getAssoc('id_tipo_orden','descripcion_tipo_orden');
$prioridades=array(
	1=>'ALTA',
	2=>'MEDIA',
	3=>'BAJA'
);*/
includeDoctype();
?>

<html>
<head>
    <?php 
	includeMeta();
	includeScripts('control/');
	getXajaxJavascript('');
	//includeModalBox(); ?>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Privilegios del Perfil</title>

    <link rel="stylesheet" type="text/css" href="servicios/css/sa_general.css" />
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
	
	function cargar_priv(empresa) {
		document.getElementById('capa').innerHTML='Cargando...';
		if (empresa == 0) {
			alert('Especifique Empresa');
			empresa = NULL;
		}
		var user = document.getElementById('hddIdPerfil').value;
		
		xajax_load_page(user);
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
			<td align="right" class="tituloPaginaErp">Privilegios del Perfil</td>
		</tr>
        <tr>
        	<td>
            <form id="formulario" name="formulario" onSubmit="return false;">
                <input type="hidden" name="hddIdPerfil" id="hddIdPerfil" />
                <table>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Perfil:</td>
                    <td><input type="text" id="tdNombrePerfil" name="tdNombrePerfil" readonly="readonly" size="30" maxlength="50"/></td>
                    <td>
                    	<button id="boton_guardar" onClick="xajax_guardar_privilegios(xajax.getFormValues('formulario'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
                        <button type="button" id="btnCancelar" name="btnCancelar" onClick="window.open('pg_perfil_list.php','_self');" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/ico_error.gif"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
					</td>
                </tr>
                </table>
                
                <br />
                
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
xajax_load_page(<?php echo $idPerfil; ?>);

xajax.callback.global.onRequest = function() {
	//xajax.$('loading').style.display = 'block';
	obj('load_animate').style.display='';
}
xajax.callback.global.beforeResponseProcessing = function() {
	//xajax.$('loading').style.display='none';
	obj('load_animate').style.display='none';
}
</script>