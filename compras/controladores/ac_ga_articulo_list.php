<?php

function buscarArticulo($valForm) {
	$objResponse = new xajaxResponse();
	
	$codArticulo = "";
	for ($cont = 0; $cont <= $valForm['hddCantCodigo']; $cont++) {
		$codArticulo .= $valForm['txtCodigoArticulo'.$cont]."-";
	}
	
	$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
	$codArticulo = codArticuloExpReg($codArticulo);
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['lstEstatus'],
		$codArticulo,
		$valForm['txtCriterio'],
		$valForm['lstTipoArticuloArtBus2']);
	
	$objResponse->loadCommands(listadoArticulos(0, "", "", $valBusq));
		
	return $objResponse;
}

function buscarArtSustAlte($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$valForm['textCriterioHidIdArtBus'],
		$valForm['textCriterio']
		);

	$objResponse->loadCommands(listArtSustitutoAlerteno(0, "", "", $valBusq));
		
	return $objResponse;
}

function exportarExcel($valForm) {
	$objResponse = new xajaxResponse();
	
	$codArticulo = "";
	for ($cont = 0; $cont <= $valForm['hddCantCodigo']; $cont++) {
		$codArticulo .= $valForm['txtCodigoArticulo'.$cont]."-";
	}
	
	$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
	$codArticulo = codArticuloExpReg($codArticulo);
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['lstEstatus'],
		$codArticulo,
		$valForm['txtCriterio'],
		$valForm['lstTipoArticuloArtBus2']);

	$objResponse->script("window.open('reportes/ga_articulo_excel.php?valBusq=".$valBusq."','_self');");

	return $objResponse;
}

function cargaArticulo($idArticulo, $tipo){
	$objResponse = new xajaxResponse();

	if($idArticulo != ""){
		$sqlBus = sprintf("WHERE id_articulo = %s",$idArticulo);
	}
	
	$query = sprintf("SELECT * FROM vw_ga_articulos %s;",$sqlBus);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rows = mysql_fetch_array($rs);
		
	switch($tipo){
		case "nuevo":
			$objResponse->loadCommands(cargaLstMarca("", $tipo));
			$objResponse->loadCommands(cargaLstTipoArticulo("", $tipo));
			$objResponse->loadCommands(cargaLstTipoUnidad());
			$objResponse->loadCommands(cargaLstSeccion("", $tipo));
			$objResponse->assign("tdFlotanteTitulo1","innerHTML","Agregar Articulo");
			
			$objResponse->script("
				$(function() {
					$('ul.tabs').tabs('> .pane', {initialIndex: 0});
				});

				openImg(byId('divFlotante1'));
				eliminarTr();
				remover();
				$('#btnGuardaArt').show();
				$('#btnAgreArtSustituto').show();
				$('#btnAgreArtAlerteno').show();
				$('#btnElimArtSustituto').show();
				$('#btnElimArtAlterno').show();
				$('#btnImprimiArt').hide();
				$('#imgCodigoBarra').hide();
				$('#btnEtiqueta').hide();
			");
				break;	
				
		case "buscar":
			$objResponse->assign("hddItemArticulo","value", $idArtculo);
			$objResponse->loadCommands(cargaLstMarca($rows['id_marca'], $tipo));
			$objResponse->loadCommands(cargaLstTipoArticulo($rows['id_tipo_articulo'], $tipo));
			$objResponse->loadCommands(cargaLstSeccion($rows['id_seccion'],$tipo));
			$objResponse->loadCommands(cargaLstSubSeccion($rows['id_seccion'], $tipo, $rows['id_subseccion']));
			$objResponse->assign("txtBusCodigoArticulo","value",utf8_encode($rows['codigo_articulo']));
			$objResponse->assign("txtBusCodigoArtProv","value",utf8_encode($rows['codigo_articulo_prov']));
			$objResponse->assign("txtBusDescripcion","value",utf8_encode($rows['descripcion']));
			$objResponse->assign("txtBusStockMax","value", $rows['stock_maximo']);
			$objResponse->assign("txtBusStockMin","value", $rows['stock_minimo']);
			$objResponse->assign("hddItemArticulo","value",$rows['id_articulo']);
				$objResponse->script("showHide('show');");
					break;
					
		case "editar":
			$objResponse->loadCommands(cargaLstMarca($rows['id_marca'], "nuevo"));
			$objResponse->loadCommands(cargaLstTipoArticulo($rows['id_tipo_articulo'], "nuevo"));
			$objResponse->assign("txtCodigoArticulo","value",utf8_encode($rows['codigo_articulo']));
			$objResponse->assign("txtCodigoArtPro","value",utf8_encode($rows['codigo_articulo_prov']));
			$objResponse->assign("txtDescripcion","value",utf8_encode($rows['descripcion']));
			$objResponse->loadCommands(cargaLstTipoUnidad($rows['id_tipo_unidad']));
			$objResponse->loadCommands(cargaLstSeccion($rows['id_seccion'],"nuevo"));
			$objResponse->loadCommands(cargaLstSubSeccion($rows['id_seccion'],"nuevo", $rows['id_subseccion']));
			$objResponse->loadCommands(cargarArtSustituto($rows['id_articulo']));
			$objResponse->loadCommands(cargarArtAlterno($rows['id_articulo']));
			$objResponse->assign("hddIdArticulo","value",$rows['id_articulo']);
			$objResponse->assign("hddUrlImagen","value",$rows['foto']);			
			$objResponse->assign("tdFlotanteTitulo1","innerHTML","Editar Articulo");

			$objResponse->script("
				$(function() {
					$('ul.tabs').tabs('> .pane', {initialIndex: 0});
				});
				
				openImg(byId('divFlotante1'));
				$('#btnGuardaArt').show();
				$('#btnAgreArtSustituto').show();
				$('#btnAgreArtAlerteno').show();
				$('#btnElimArtSustituto').show();
				$('#btnElimArtAlterno').show();
				$('#btnImprimiArt').hide();
				$('#btnEtiqueta').show();
				$('#imgCodigoBarra').show();
				");
		
		//SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = $rows['foto'];
		if(!file_exists($rows['foto'])){
			$imgFoto = "../".$_SESSION['logoEmpresaSysGts'];
		}
		$objResponse->assign("imgArticulo","src",$imgFoto);
		
		$imgCode = '../clases/barcode128.php?type=B&bw=2&pc=1&codigo='.$rows['id_articulo'].'';
		$objResponse->assign("imgCodigoBarra","src",$imgCode);
		
		$objResponse->script("$('#btnEtiqueta' ).show();
			$('#btnEtiqueta').unbind();//elimina todos los controladores conectados a los elementos
			$('#btnEtiqueta' ).click(function() {
				verVentana('reportes/ga_articulo_etiqueta_pdf.php?idArt=".$rows['id_articulo']."&session=".$_SESSION['idEmpresaUsuarioSysGts']."');
		});");
				break;	
				
		case "ver1":
			$objResponse->script("eliminarTr();");
			$objResponse->loadCommands(cargaLstMarca($rows['id_marca'], $tipo));
			$objResponse->loadCommands(cargaLstTipoArticulo($rows['id_tipo_articulo'], $tipo));
			$objResponse->assign("txtCodigoArticulo","value",utf8_encode($rows['codigo_articulo']));
			$objResponse->assign("txtCodigoArtPro","value",utf8_encode($rows['codigo_articulo_prov']));
			$objResponse->assign("txtDescripcion","value",utf8_encode($rows['descripcion']));
			$objResponse->loadCommands(cargaLstTipoUnidad($rows['id_tipo_unidad'], $tipo));
			$objResponse->loadCommands(cargaLstSeccion($rows['id_seccion'], $tipo));
			$objResponse->loadCommands(cargaLstSubSeccion($rows['id_seccion'], $tipo, $rows['id_subseccion']));
			$objResponse->loadCommands(cargarArtSustituto($rows['id_articulo']));
			$objResponse->loadCommands(cargarArtAlterno($rows['id_articulo']));
			$objResponse->assign("hddIdArticulo","value",$rows['id_articulo']);
			$objResponse->assign("hddUrlImagen","value",$rows['foto']);			
			$objResponse->assign("tdFlotanteTitulo1","innerHTML","Ver Articulo");
			
			$objResponse->script("
				$(function() {
					$('ul.tabs').tabs('> .pane', {initialIndex: 0});
				});
				
				openImg(byId('divFlotante1')); 
				$('#btnGuardaArt').hide();
				$('#btnAgreArtSustituto').hide();
				$('#btnElimArtSustituto').hide();
				$('#btnAgreArtAlerteno').hide();
				$('#btnElimArtAlterno').hide();
				$('#imgCodigoBarra').show();");
				
			$imgCode = '../clases/barcode128.php?type=B&bw=2&pc=1&codigo='.$rows['id_articulo'].'';
			$objResponse->assign("imgCodigoBarra","src",$imgCode);
			
			$objResponse->script("$('#btnEtiqueta').show();
				$('#btnEtiqueta').unbind();//elimina todos los controladores conectados a los elementos
				$('#btnEtiqueta').click(function(){
					verVentana('reportes/ga_articulo_etiqueta_pdf.php?idArt=".$rows['id_articulo']."&session=".$_SESSION['idEmpresaUsuarioSysGts']."');
			});");
			
			$objResponse->script("$('#btnImprimiArt').show();
								$('#btnImprimiArt').unbind();//elimina todos los controladores conectados a los elementos
				$('#btnImprimiArt').click(function(){
					window.open('reportes/ga_articulo_pdf.php?idArt=".$rows['id_articulo']."&session=".$_SESSION['idEmpresaUsuarioSysGts']."');
			});");
				break;	
				
		case "ver2":
			$objResponse->loadCommands(cargaLstMarca($rows['id_marca'], $tipo));
			$objResponse->loadCommands(cargaLstTipoArticulo($rows['id_tipo_articulo'], $tipo));
			$objResponse->assign("txtCodigoArtVer","value",utf8_encode($rows['codigo_articulo']));
			$objResponse->assign("txtCodigoArtProVer","value",utf8_encode($rows['codigo_articulo_prov']));
			$objResponse->assign("txtDescripcionVer","value",utf8_encode($rows['descripcion']));
			$objResponse->assign("txtStockMaxVer","value", $rows['stock_maximo']);
			$objResponse->assign("txtStockMinVer","value", $rows['stock_minimo']);
			$objResponse->loadCommands(cargaLstSeccion($rows['id_seccion'], $tipo));
			$objResponse->loadCommands(cargaLstSubSeccion($rows['id_seccion'], $tipo, $rows['id_subseccion']));
			$objResponse->script("openImg(byId('divFlotante3'));");
				break;	
	}
	
	if ($tipoCarga == "buscar" ){
		$objResponse->script("$('#trArtSustAlter').show();");
		$objResponse->script("$('#btnGuardaArtSustAlt').show();");
	}
	
	return $objResponse;
}

function cargarArtAlterno($idArtculo){
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT 
		id_articulo_codigo_alterno,
		ga_articulos_codigos_alternos.id_articulo,
		id_articulo_alterno,
		vw_ga_articulos.descripcion,
		codigo_articulo,
		existencia
	FROM ga_articulos_codigos_alternos
	LEFT JOIN vw_ga_articulos ON vw_ga_articulos.id_articulo = ga_articulos_codigos_alternos.id_articulo_alterno
	WHERE ga_articulos_codigos_alternos.id_articulo = %s;", 
	valTpDato($idArtculo,"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);

	$objResponse->assign("itemArtAlterno","value", $nombObj);
	$nombTab = "tabArtAlte";
	$idcheck = "checkArtAlte";
	$namecheck = "checkArtAlte[]";
	$idInputText= "textArtAleterno";
	$nameInputText= "textArtAleterno[]";

	while($rows = mysql_fetch_array($rs)){
		
		$inputnCheck = sprintf("<input type='checkbox' value='%s|%s' id='%s' name='%s'>",
					$rows["id_articulo_alterno"],$rows["id_articulo_codigo_alterno"], $idcheck,$namecheck);
					
		$inputText = sprintf("<input type='hidden' value='%s|%s' id='%s' name='%s'>",
						$rows["id_articulo_alterno"],$rows["id_articulo"],$idInputText, $nameInputText);
					
 		$trTd .= "<tr id='tr".$rows["id_articulo_alterno"]."' class='textoGris_11px'>";
			$trTd .= "<td align='center'>".$inputnCheck.$inputText."</td>";
			$trTd .= "<td align='center'>".utf8_encode($rows["codigo_articulo"])."</td>";
			$trTd .= "<td>".utf8_encode(trim($rows["descripcion"]))."</td>";
			$trTd .= "<td align='center'>".$rows["existencia"]."</td>";
			$trTd .= "<td align='center'><a class='modalImg' id='imgArtAlterSust' name ='imgVerArticulo' rel='#divFlotante3' onclick= 'xajax_formArticulo(this.name, &#39;".$rows["id_articulo"]."|ver2&#39;);'><img src='../img/iconos/ico_view.png'/></a></td>";//
		$trTd .= "</tr>";
	}	
		
	$objResponse->script('$("#'.$nombTab.'").append("'.$trTd.'");'); 
	return $objResponse;
}

function cargarArtSustituto($idArtculo){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT 
		id_articulo_codigo_sustituto,
		ga_articulos_codigos_sustitutos.id_articulo,
		id_articulo_sustituto, codigo_articulo, 
		vw_ga_articulos.descripcion, 
		existencia
	FROM ga_articulos_codigos_sustitutos
	LEFT JOIN vw_ga_articulos ON vw_ga_articulos.id_articulo=ga_articulos_codigos_sustitutos.id_articulo_sustituto
	WHERE ga_articulos_codigos_sustitutos.id_articulo = %s;",
	valTpDato($idArtculo, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$objResponse->assign("itemArtSustituto","value", "artSust");
	$nombTab = "tabArtSust";
	$idcheck = "checkArtSust";
	$namecheck = "checkArtSust[]";
	$idInputText= "textArtSustituto";
	$nameInputText= "textArtSustituto[]";

	while($rows = mysql_fetch_array($rs)){
		
		$inputnCheck = sprintf("<input type='checkbox' value='%s|%s' id='%s' name='%s'>",
					$rows["id_articulo_sustituto"],$rows["id_articulo_codigo_sustituto"], $idcheck,$namecheck);
					
		$inputText = sprintf("<input type='hidden' value='%s|%s' id='%s' name='%s'>",
				$rows["id_articulo_sustituto"],$rows["id_articulo"],$idInputText, $nameInputText);


		$trTd .= "<tr id='tr".$rows["id_articulo_sustituto"]."' class='textoGris_11px'>";
			$trTd .= "<td align='center'>".$inputnCheck.$inputText."</td>";
			$trTd .= "<td align='center'>".utf8_encode($rows["codigo_articulo"])."</td>";
			$trTd .= "<td>".utf8_encode(trim($rows["descripcion"]))."</td>";
			$trTd .= "<td align='center'>".$rows["existencia"]."</td>";
			$trTd .= "<td align='center'><a class='modalImg' id='imgArtAlterSust' name ='imgVerArticulo' rel='#divFlotante3' onclick= 'xajax_formArticulo(this.name, &#39;".$rows["id_articulo"]."|ver2&#39;);'><img src='../img/iconos/ico_view.png'/></a></td>";//
		$trTd .= "</tr>";			
	}		
	
	$objResponse->script('$("#'.$nombTab.'").append("'.$trTd.'");'); 
	return $objResponse;
}

function cargaLstMarca($selId = "", $tipo) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM ga_marcas ORDER BY marca");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	//SEGUN EL TIPO SE CAMBIA EL ID, NAME Y UBICACION DEL OBJETO
	switch($tipo){
		case "nuevo":
			$select = "<select id=\"lstMarcaArt\" name=\"lstMarcaArt\" class=\"inputHabilitado\" >";
			$ubicar = "tdlstMarcaArt";
				break;
		case "buscar";
			$select = "<select id=\"lstMarcaArtBus\" name=\"lstMarcaArtBus\" class=\"inputInicial\">";
			$id = "";
			$name = "";
			$ubicar = "tdlstMarcaArtBus";
			$OnFocus = "OnFocus=\"this.blur()\"";
				break;
		case "ver1":
			$select = "<select id=\"lstMarcaArtVer1\" name=\"lstMarcaArtVer1\" class=\"inputInicial\" OnFocus=\"this.blur()\">";
			$ubicar = "tdlstMarcaArt";
				break;
		case "ver2":
			$select = "<select id=\"lstMarcaArtVer2\" name=\"lstMarcaArtVer2\" class=\"inputInicial\" OnFocus=\"this.blur()\">";
			$ubicar = "tdlstMarcaArtVer";
				break;
	}
	
	$html = $select;
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = "";
		if ($selId == $row['id_marca'])
			$seleccion = "selected='selected'";
		
		$html .= sprintf("<option value=\"%s\" %s>%s</option>",
			$row['id_marca'],$seleccion,utf8_encode($row['marca']));
	}
	$html .= "</select>";
	$objResponse->assign($ubicar,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTipoArticulo($selId = "",$tipo) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM ga_tipos_articulos ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	//SEGUN EL TIPO SE CAMBIA EL ID, NAME Y UBICACION DEL OBJETO
	switch($tipo){
		case "nuevo"://carga en el formulario crear nuevo art
			$select = "<select id=\"lstTipoArticuloArt\" name=\"lstTipoArticuloArt\" class=\"inputHabilitado\">";
			$ubicar = "tdlstTipoArticuloArt";
				break;
		case "buscar"; //carga en el formualrio de art sustituto y alterno
			$select = "<select id=\"lstTipoArticuloArtBus\" name=\"lstTipoArticuloArtBus\" class=\"inputInicial\" OnFocus=\"this.blur()\">";
			$ubicar = "tdlstTipoArticuloArtBus";
				break;
		
		case "buscador"; //carga en el formualrio de art sustituto y alterno
			$select = "<select id=\"lstTipoArticuloArtBus2\" name=\"lstTipoArticuloArtBus2\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\">";
			$ubicar = "tdlstTipoArticuloArtBus2";
				break;
		
		case "ver1": //carga el formulario de ver
			$select = "<select id=\"lstTipoArticuloArtVer1\" name=\"lstTipoArticuloArtVer1\" class=\"inputInicial\" OnFocus=\"this.blur()\">";
			$ubicar = "tdlstTipoArticuloArt";
				break;
		case "ver2":
			$select = "<select id=\"lstTipoArticuloArtVer2\" name=\"lstTipoArticuloArtVer2\" class=\"inputInicial\" OnFocus=\"this.blur()\">";
			$ubicar = "tdlstTipoArticuloArtVer";
				break;
	}

	$html = $select;
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = "";
		if ($selId == $row['id_tipo_articulo'])
			$seleccion = "selected='selected'";
		
		$html .= sprintf("<option value=\"%s\" %s>%s</option>",
			$row['id_tipo_articulo'],$seleccion,utf8_encode($row['descripcion']));
	}
	$html .= "</select>";
	$objResponse->assign($ubicar,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTipoUnidad($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM ga_tipos_unidad ORDER BY unidad");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$html = "<select id=\"lstTipoUnidad\" name=\"lstTipoUnidad\" class=\"inputHabilitado\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = "";
		if ($selId == $row['id_tipo_unidad'])
			$seleccion = "selected='selected'";
		
		$html .= "<option value=\"".$row['id_tipo_unidad']."\" ".$seleccion.">".htmlentities($row['unidad'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoUnidad","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstSeccion($selId = "", $tipo) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM ga_secciones WHERE estatu = 1 ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	//SEGUN EL TIPO SE CAMBIA EL ID, NAME Y UBICACION DEL OBJETO
	switch($tipo){
		case "nuevo":
			$select = "<select id=\"lstSeccionArt\" name=\"lstSeccionArt\" class=\"inputHabilitado\" onchange=\"xajax_cargaLstSubSeccion(this.value, 'nuevo');\">";
			$ubicar = "tdlstSeccionArt";
				break;
				
		case "buscar";
			$select = "<select id=\"lstSeccionArtBus\" name=\"lstSeccionArtBus\" class=\"inputInicial\" onchange=\"xajax_cargaLstSubSeccion(this.value, 'buscar');\" OnFocus=\"this.blur()\">";
			$ubicar = "tdlstSeccionArtBus";
				break;
				
		case "ver1":
			$select = "<select id=\"lstSeccionArtVer1\" name=\"lstSeccionArtVer1\" class=\"inputInicial\" onchange=\"xajax_cargaLstSubSeccion(this.value, 'ver1');\" OnFocus=\"this.blur()\">";
			$ubicar = "tdlstSeccionArt";
				break;
				
		case "ver2":
			$select = "<select id=\"lstSeccionArtVer2\" name=\"lstSeccionArtVer2\" class=\"inputInicial\" onchange=\"xajax_cargaLstSubSeccion(this.value, 'ver2');\" OnFocus=\"this.blur()\">";

			$ubicar = "tdlstSeccionArtVer";
				break;
	}

	$html = $select;
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = "";
		if ($selId == $row['id_seccion'])
			$seleccion = "selected='selected'";
		
		$html .= sprintf("<option value=\"%s\" %s>%s</option>",
			$row['id_seccion'],$seleccion,utf8_encode($row['descripcion']));
	}
	$html .= "</select>";
	$objResponse->assign($ubicar,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstSubSeccion($idSeccion, $tipo,  $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM ga_subsecciones WHERE estatu = 1 AND id_seccion = %s", valTpDato($idSeccion,"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	//SEGUN EL TIPO SE CAMBIA EL ID, NAME Y UBICACION DEL OBJETO
	switch($tipo){
		case "nuevo":
			$select ="<select id=\"lstSubSeccionArt\" name=\"lstSubSeccionArt\" class=\"inputHabilitado\">";
			$ubicar = "tdlstSubSeccionArt";
				break;
				
		case "buscar";
			$select ="<select id=\"lstSubSeccionArtBus\" name=\"lstSubSeccionArtBus\" class=\"inputInicial\" OnFocus=\"this.blur()\">";
			$ubicar = "tdlstSubSeccionBus";
				break;
				
		case "ver1":
			$select ="<select id=\"lstSubSeccionArtVer1\" name=\"lstSubSeccionArtVer1\" class=\"inputInicial\" OnFocus=\"this.blur()\">";
			$ubicar = "tdlstSubSeccionArt";
				break;
				
		case "ver2":
			$select ="<select id=\"lstSubSeccionArtVer2\" name=\"lstSubSeccionArtVer2\" class=\"inputInicial\" OnFocus=\"this.blur()\">";
			$ubicar = "tdlstSubSeccionVer";
				break;
	}

	$html = $select;
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = "";
		if ($selId == $row['id_subseccion'])
			$seleccion = "selected='selected'";
		
		$html .= sprintf("<option value=\"%s\" %s>%s</option>",
			$row['id_subseccion'],$seleccion,utf8_encode($row['descripcion']));
	}
	$html .= "</select>";
	$objResponse->assign($ubicar,"innerHTML",$html);
	
	return $objResponse;
}

function guardarArticulo($valorForm){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
		
	if($valorForm['hddIdArticulo'] > 0){
		if (!xvalidaAcceso($objResponse,"ga_articulo_list","editar")) { return $objResponse; }
		
		$query = sprintf("UPDATE ga_articulos SET
			codigo_articulo = %s,
			id_marca = %s,
			id_tipo_articulo = %s,
			codigo_articulo_prov = %s,
			descripcion = %s,
			foto = %s,
			id_subseccion = %s,
			id_tipo_unidad = %s
		WHERE id_articulo = %s;",
		valTpDato(trim($valorForm['txtCodigoArticulo']), "text"),
		valTpDato($valorForm['lstMarcaArt'], "int"),
		valTpDato($valorForm['lstTipoArticuloArt'], "int"),
		valTpDato(trim($valorForm['txtCodigoArtPro']), "text"),
		valTpDato(trim($valorForm['txtDescripcion']), "text"),
		valTpDato($valorForm['hddUrlImagen'], "text"),
		valTpDato($valorForm['lstSubSeccionArt'],"int"),
		valTpDato($valorForm['lstTipoUnidad'],"int"),
		valTpDato($valorForm['hddIdArticulo'],"int"));
		mysql_query("SET NAMES 'utf8'");
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		$idArticulo = $valorForm['hddIdArticulo'];
	} else {
		if (!xvalidaAcceso($objResponse,"ga_articulo_list","insertar")){ return $objResponse; }
		
		$query = sprintf("INSERT INTO ga_articulos (codigo_articulo, id_marca, id_tipo_articulo, codigo_articulo_prov, descripcion, foto, id_subseccion, id_tipo_unidad, id_empresa_creador) 
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)",
			valTpDato(trim($valorForm['txtCodigoArticulo']), "text"),
			valTpDato($valorForm['lstMarcaArt'], "int"),
			valTpDato($valorForm['lstTipoArticuloArt'], "int"),
			valTpDato(trim($valorForm['txtCodigoArtPro']), "text"),
			valTpDato(trim($valorForm['txtDescripcion']), "text"),
			valTpDato($valorForm['hddUrlImagen'], "text"),
			valTpDato($valorForm['lstSubSeccionArt'],"int"),
			valTpDato($valorForm['lstTipoUnidad'],"int"),
			valTpDato($_SESSION['idEmpresaUsuarioSysGts'],"int"));
		mysql_query("SET NAMES 'utf8'");
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$idArticulo = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	//VERIFICA SI EL ARTICULO YA ESTA REGISTRADO PARA LA EMPRESA
	$queryArtEmp = sprintf("SELECT * FROM ga_articulos_empresa WHERE id_empresa = %s AND id_articulo = %s",
		valTpDato($_SESSION['idEmpresaUsuarioSysGts'],"int"),
		valTpDato($idArticulo,"int"));
	$rsArtEmp = mysql_query($queryArtEmp);
	if (!$rsArtEmp){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
	
	//SI NO ESTA REGISTRADO, LO REGISTRA
	if ($rowArtEmp['id_articulo_empresa'] == "") {
		$insertSQL = sprintf("INSERT INTO ga_articulos_empresa (id_empresa, id_articulo, clasificacion) VALUE (%s, %s, %s)",
			valTpDato($_SESSION['idEmpresaUsuarioSysGts'],"int"),
			valTpDato($idArticulo,"int"),
			valTpDato("F","text"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	}
	
	//PARA INSERTAR LOS ITEMS ALTERNO O SUSTITUTO	
	//RECORRE LOS ARTICULOS SUSTITUTOS AGREGADO
	if($valorForm['itemArtSustituto'] != ""){
		$tipoArtSustAltr = $valorForm['itemArtSustituto'];
		foreach ($valorForm['textArtSustituto'] as $indice => $valor) {
			$valorAsySustAlt = explode("|",$valor);
			if($valorAsySustAlt[1] == 0){
				$objResponse->loadCommands(guardarArtSustAlter($idArticulo, $valorAsySustAlt[0], $tipoArtSustAltr));
			}
		}
	} 
	
	//RECORRE LOS ARTICULOS ALTERNOS AGREGADO
	if($valorForm['itemArtAlterno'] != ""){
		$tipoArtSustAltr = $valorForm['itemArtAlterno'];
		foreach ($valorForm['textArtAleterno'] as $indice => $valor) {
			$valorAsySustAlt = explode("|",$valor);
			if($valorAsySustAlt[1] == 0){
				$objResponse->loadCommands(guardarArtSustAlter($idArticulo, $valorAsySustAlt[0], $tipoArtSustAltr));
			}
		}
	}	
	
	mysql_query("COMMIT;");
	
	$objResponse->script("byId('btnCancelaArt').click();");
	$objResponse->script("byId('btnBuscar').click();");
	$objResponse->alert("Guardado con Éxito");
		
	return $objResponse;
}

function guardarArtSustAlter($idArticulo, $idArtSustAlte, $tipoArtSustAltr){
	$objResponse = new xajaxResponse();
		
	switch($tipoArtSustAltr){
		case "artSust":
			$query = sprintf("INSERT INTO ga_articulos_codigos_sustitutos (id_articulo, id_articulo_sustituto) VALUES (%s,%s);",
				valTpDato($idArticulo, "int"),
				valTpDato($idArtSustAlte, "int"));
				break;
		case "artAlter":
			$query = sprintf("INSERT INTO ga_articulos_codigos_alternos (id_articulo, id_articulo_alterno) VALUES (%s,%s);",
				valTpDato($idArticulo, "int"),
				valTpDato($idArtSustAlte, "int"));
				break;	
	}
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	return $objResponse;
}

function eliminarArtSustAlterno($idObj,$valorFrom){
	$objResponse = new xajaxResponse();	

	switch($idObj){
		case "btnElimArtSustituto":
			$sql = "DELETE FROM ga_articulos_codigos_sustitutos WHERE id_articulo_sustituto = ";
			$remoArtSustAlter = $valorFrom["checkArtSust"];
			$textArtSustAlt = $valorFrom["textArtSustituto"];
			$textAlert = "EL Articulo Sustituto Fue Eliminado con exito";
				break;
		
		case "btnElimArtAlterno":
			$sql = "DELETE FROM ga_articulos_codigos_alternos WHERE id_articulo_alterno = ";
			$remoArtSustAlter = $valorFrom["checkArtAlte"];
			$textArtSustAlt = $valorFrom["textArtAleterno"];
				break;	
	}

	foreach ($remoArtSustAlter as $indice => $valor) {
		$busArt = explode("|",$valor);
			if($busArt[1] == 0){ //ES NUEVO
				$objResponse->script("$('#tr".$busArt[0]."').remove();");
			} else { //VIENE DE BD
				$query = $sql.$busArt[0];
				$rs = mysql_query($query); 
				if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				$objResponse->script("$('#tr".$busArt[0]."').remove();");
			}
	}

	return $objResponse;
}

function formArticulo($nomObjeto, $valForm) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"ga_articulo_list","insertar")) { return $objResponse; }
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	switch($nomObjeto){
		case 'aNuevo':
			$objResponse->script("
			byId('hddIdArticulo').value = '';
			byId('itemArtSustituto').value = '';
			byId('itemArtAlterno').value = '';
			
			byId('txtCodigoArticulo').className = 'inputHabilitado';
			byId('txtCodigoArtPro').className = 'inputHabilitado';
			byId('txtDescripcion').className = 'inputHabilitado';
			");
			$objResponse->loadCommands(cargaArticulo("", "nuevo"));
				break;
				
		case "imgEditarArticulo":
			$objResponse->script("
				eliminarTr();
				byId('txtCodigoArticulo').className = 'inputHabilitado';
				byId('txtCodigoArtPro').className = 'inputHabilitado';
				byId('txtDescripcion').className = 'inputHabilitado';
			");
			$valBusq = explode("|", $valForm);
			$objResponse->loadCommands(cargaArticulo($valBusq[0],$valBusq[1]));
			$objResponse->script("$('#fleUrlImagen').show();" );
				break;
				
		case "imgVerArticulo":
			$valBusq = explode("|", $valForm);
			$objResponse->loadCommands(cargaArticulo($valBusq[0],$valBusq[1]));
			$objResponse->script("
				$('#fleUrlImagen').hide();
				byId('lstTipoUnidad').className = 'inputInicial';
				byId('txtCodigoArticulo').className = 'inputInicial';
				byId('txtCodigoArtPro').className = 'inputInicial';
				byId('txtDescripcion').className = 'inputInicial';");
				break;
				
		default:
			$objResponse->loadCommands(listArtSustitutoAlerteno(0, "descripcion", "ASC", $valForm['hddIdArticulo']));
			$objResponse->script("
				openImg(byId('".$nomObjeto."'));
				//byId('lstTipoUnidad').className = 'inputInicial';
				//byId('txtCodigoArticulo').className = 'inputInicial';
				//byId('txtCodigoArtPro').className = 'inputInicial';
				//byId('txtDescripcion').className = 'inputInicial';");
				$objResponse->assign("nombObjArtAlterSust","value",$nomObjeto);
				$objResponse->assign("textCriterioHidIdArtBus","value",$valForm['hddIdArticulo']);
				
				if($nomObjeto == 'artSust'){
					$objResponse->assign("tdFlotanteTitulo2","innerHTML","Articulos Sustitutos");
				} else {
					$objResponse->assign("tdFlotanteTitulo2","innerHTML","Articulos Alternos");
				}
				break;
	}
	
	return $objResponse;
}

function desactivarArticulo($idArticulo, $hddIdItm, $valFormListaArticulos) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM vw_ga_articulos WHERE id_articulo = %s;",
		valTpDato($idArticulo,"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if ($row['estatus_articulo'] == "") {		
		if (!xvalidaAcceso($objResponse,"ga_articulo_list","insertar")) { return $objResponse; }
		
		mysql_query("START TRANSACTION;");
	
		// ACTUALIZA EL ESTATUS DEL ARTICULO
		$updateSQL = sprintf("UPDATE ga_articulos SET
				estatus = 1
			WHERE id_articulo = %s;",
			valTpDato($idArticulo, "int"));		
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	
		mysql_query("COMMIT;");
		
		$objResponse->alert("Articulo Activado con Éxito");
	} else if ($row['estatus_articulo'] == 1) {
		if (!xvalidaAcceso($objResponse,"ga_articulo_list","eliminar")) { return $objResponse; }
		
		mysql_query("START TRANSACTION;");
	
		// ACTUALIZA EL ESTATUS DEL ARTICULO
		$updateSQL = sprintf("UPDATE ga_articulos SET
				estatus = NULL
			WHERE id_articulo = %s;",
			valTpDato($idArticulo, "int"));		
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Articulo Desactivado con Éxito");
	}
	
	$objResponse->loadCommands(listadoArticulos(
		$valFormListaArticulos['pageNum'],
		$valFormListaArticulos['campOrd'],
		$valFormListaArticulos['tpOrd'],
		$valFormListaArticulos['valBusq']));
	
	return $objResponse;
}

function insertaArtSustAlte($nombObj, $idArticulo){
	$objResponse = new xajaxResponse();
	
	$sql = sprintf("SELECT * FROM vw_ga_articulos WHERE id_articulo = %s",$idArticulo);
	$query = mysql_query($sql);
	if (!$query) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rows = mysql_fetch_array($query);
	
	switch($nombObj){
		case  'artSust':
			$objResponse->assign("itemArtSustituto","value", $nombObj);
			$nombTab = "tabArtSust";
			$idcheck = "checkArtSust";
			$namecheck = "checkArtSust[]";
			$idInputText= "textArtSustituto";
			$nameInputText= "textArtSustituto[]";
				break;
				
		case 'artAlter':
			$objResponse->assign("itemArtAlterno","value", $nombObj);
			$nombTab = "tabArtAlte";
			$idcheck = "checkArtAlter";
			$namecheck = "checkArtAlter[]";
			$idInputText= "textArtAleterno";
			$nameInputText= "textArtAleterno[]";
				break;
	}
	
	$inputnCheck = sprintf("<input type='checkbox' value='%s|0' id='%s' name='%s'>",
				$rows["id_articulo"], $idcheck,$namecheck);
	
	$inputText = sprintf("<input type='hidden' value='%s|0' id='%s' name='%s'>",
					$rows["id_articulo"],$idInputText, $nameInputText);
	
	$trTd .= "<tr id='tr".$rows["id_articulo"]."' class='textoGris_11px'>";
		$trTd .= "<td align='center'>".$inputnCheck.$inputText."</td>";
		$trTd .= "<td align='center'>".utf8_encode($rows["codigo_articulo"])."</td>";
		$trTd .= "<td>".utf8_encode(trim($rows["descripcion"]))."</td>";
		$trTd .= "<td align='center'>".$rows["existencia"]."</td>";
		$trTd .= "<td align='center'><a class='modalImg' id='imgArtAlterSust' name ='imgVerArticulo' rel='#divFlotante3' onclick= 'xajax_formArticulo(this.name, &#39;".$rows["id_articulo"]."|ver2&#39;);'><img src='../img/iconos/ico_view.png'/></a></td>";//
	$trTd .= "</tr>";
	
	$objResponse->script('$("#'.$nombTab.'").append("'.$trTd.'");'); 

	return $objResponse;	
}

function listadoArticulos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
		
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(estatus_articulo = %s
		OR (estatus_articulo IS NULL AND %s IS NULL))",
			valTpDato($valCadBusq[1], "text"),
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[2], "text"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(id_articulo = %s
		OR codigo_articulo LIKE %s
		OR descripcion LIKE %s)",
			valTpDato($valCadBusq[3],"int"),
			valTpDato("%".$valCadBusq[3]."%","text"),
			valTpDato("%".$valCadBusq[3]."%","text"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_tipo_articulo = %s",
			valTpDato($valCadBusq[4], "text"));
	}
	
	$query = ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") ? "SELECT * FROM vw_ga_articulos_empresa" : "SELECT * FROM vw_ga_articulos";
	$query .= $sqlBusq;
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
		
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	//$objResponse->alert($queryLimit);
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "11%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "50%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "13%", $pageNum, "tipo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Articulo");
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "5%", $pageNum, "existencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "7%", $pageNum, "cantidad_disponible_logica", $campOrd, $tpOrd, $valBusq, $maxRows, "Disponible");
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "7%", $pageNum, "cantidad_pedida", $campOrd, $tpOrd, $valBusq, $maxRows, "Pedida a Proveedor");
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "5%", $pageNum, "cantidad_futura", $campOrd, $tpOrd, $valBusq, $maxRows, "Futura");
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "2%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Clasif.");
		$htmlTh .= "<td class=\"noprint\" colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		//if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			switch ($row['estatus_articulo']) {
				case 0 : $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
				case 1 : $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
				default : $imgEstatus = "";
			}
		//}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".$imgEstatus."</td>";
			$htmlTb .= "<td>".utf8_encode(elimCaracter($row['codigo_articulo'],"-"))."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['tipo_articulo']."</td>";
			$htmlTb .= "<td align=\"right\">".valTpDato($row['existencia'],"cero_por_vacio")."</td>";
			$htmlTb .= "<td align=\"right\" class=\"divMsjInfo\">".valTpDato($row['cantidad_disponible_logica'],"cero_por_vacio")."</td>";
			$htmlTb .= "<td align=\"right\">";
				$htmlTb .= "<table>";
				$htmlTb .= "<tr>";
					$htmlTb .= sprintf("<td>%s</td>", valTpDato($row['cantidad_pedida'],"cero_por_vacio"));
				if ($row['cantidad_pedida'] > 0) {
					$htmlTb .= sprintf("<td class=\"noprint\"><img class=\"puntero\" onclick=\"xajax_listadoDctosPedComp(0,0,'','%s|%s');\" src=\"../img/iconos/ico_view.png\" /></td>",
						$row['id_empresa'],
						$row['id_articulo']);
				}
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".valTpDato($row['cantidad_futura'],"cero_por_vacio")."</td>";
			$htmlTb .= "<td align=\"center\">";
				switch($row['clasificacion']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"Clasificación A\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"Clasificación B\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"Clasificación C\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"Clasificación D\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"Clasificación E\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"Clasificación F\"/>"; break;
				}
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" id=\"imgVerArticulo%s\" name = \"imgVerArticulo\" onclick=\"xajax_formArticulo(this.name, '%s|ver1');\" src=\"../img/iconos/ico_view.png\" title=\"Ver Artículo\"/></td>",
				$contFila,
				$row['id_articulo'],
				$row['id_empresa']);
			$htmlTb .= sprintf("<td><img class=\"puntero\" id=\"imgEditarArticulo%s\" name =\"imgEditarArticulo\" onclick=\"xajax_formArticulo(this.name, '%s|editar');\" src=\"../img/iconos/ico_edit.png\" title=\"Editar Artículo\"/></td>",
				$contFila,
				$row['id_articulo'],
				$row['id_empresa']);
			$htmlTb .= "<td>";
			if ($row['estatus_articulo'] == "") {
				$htmlTb .= sprintf("<img class=\"puntero\" id=\"imgDesactivarArticulo%s\" onclick=\"validarDesactivarArticulo('%s', '%s')\" src=\"../img/iconos/ico_aceptar.gif\" title=\"Activar Artículo\"/>",
					$contFila,
					$row['id_articulo'],
					$contFila);
			} else if ($row['estatus_articulo'] == 1) {
				$htmlTb .= sprintf("<img class=\"puntero\" id=\"imgDesactivarArticulo%s\" onclick=\"validarDesactivarArticulo('%s', '%s')\" src=\"../img/iconos/ico_error.gif\" title=\"Desactivar Artículo\"/>",
					$contFila,
					$row['id_articulo'],
					$contFila);
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"12\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf.="selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf.="</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
			
	$objResponse->assign("divListaArticulos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listadoDctosPedComp($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$sqlBusq = " WHERE estatus_orden_compra <> 3";
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_articulo = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	$query = sprintf("SELECT * FROM vw_ga_articulos_orden_compra %s", $sqlBusq);
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">
				<td></td>
            	<td width=\"32%\">"."Nro. Orden"."</td>
                <td width=\"32%\">"."Nro. Solicitud"."</td>
                <td width=\"18%\">"."Fecha"."</td>
                <td width=\"18%\">"."Cantidad"."</td>
            </tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
		
		/*if ($row['estatus_orden_compra'] == 0)
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Pendiente por Terminar\"/>";
		else if ($row['estatus_orden_compra'] == 1)
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Convertido a Pedido\"/>";
		else */if ($row['estatus_orden_compra'] == 2)
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Convertido a Orden\"/>";
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"center\">".$imgEstatusPedido."</td>";
			$htmlTb .= "<td align=\"left\">".$row['id_orden_compra']."</td>";
			$htmlTb .= "<td align=\"left\">".$row['numero_solicitud']."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td align=\"center\">".$row['total_pendiente']."</td>";
			/*$htmlTb .= sprintf("<td align=\"center\"><img class=\"puntero\" onclick=\"verVentana('iv_pedido_compra_imp.php?id=%s', 1000, 500);\" src=\"../img/iconos/ico_view.png\" /></td>",
				$row['id_pedido_compra']);*/
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr class=\"tituloColumna\">";
		$htmlTf .= "<td align=\"center\" colspan=\"12\">";
			$htmlTf .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr>";
				$htmlTf .= "<td align=\"center\">";
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35\">";
				if ($pageNum > 0) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDctosPedComp(%s,'%s','%s','%s',%s);\">%s</a>",
						0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35\">";
				if ($pageNum > 0) { 
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDctosPedComp(%s,'%s','%s','%s',%s);\">%s</a>",
						max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"90\">";
				
					$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoDctosPedComp(%s,'%s','%s','%s',%s)\">",
						"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
					for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
							$htmlTf.="<option value=\"".$nroPag."\"";
							if ($pageNum == $nroPag) {
								$htmlTf.="selected=\"selected\"";
							}
							$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
					}
					$htmlTf.="</select>";
					
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDctosPedComp(%s,'%s','%s','%s',%s);\">%s</a>",
						min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDctosPedComp(%s,'%s','%s','%s',%s);\">%s</a>",
						$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_compras.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\"></td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"6\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("tdListadoDcto","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$sqlBusq = "";
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_articulo = %s",
			valTpDato($valCadBusq[1],"int"));
	}
	$query = ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") ? "SELECT * FROM vw_ga_articulos_empresa" : "SELECT * FROM vw_ga_articulos";
	$query .= $sqlBusq;
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	
	$queryArticulo = sprintf("SELECT * FROM vw_ga_articulos
	WHERE id_articulo = %s",
		$valCadBusq[1]);
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	$objResponse->assign("txtCodigoArticulo","value",utf8_encode(elimCaracter($rowArticulo['codigo_articulo'],"-")));
	$objResponse->assign("txtArticulo","innerHTML",utf8_encode($rowArticulo['descripcion']));
	$objResponse->assign("tdTituloCampoDcto","innerHTML","Pedida a Proveedor:");
	$objResponse->assign("txtCantidad","value",$row['cantidad_pedida']);
	
	$html = "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjInfo2\" width=\"100%\">";
	$html .= "<tr>";
		$html .= "<td width=\"25\"><img src=\"../img/iconos/ico_info.gif\" width=\"25\"></td>";
		$html .= "<td align=\"center\">";
			$html .= "<table>";
			$html .= "<tr>";
				$html .= "<td><img src=\"../img/iconos/ico_verde.gif\"></td>";
				$html .= "<td>Convertido a Orden</td>";
				/*$html .= "<td>&nbsp;</td>";
				$html .= "<td><img src=\"../img/iconos/ico_amarillo.gif\"></td>";
				$html .= "<td>Convertido a Pedido</td>";
				$html .= "<td>&nbsp;</td>";
				$html .= "<td><img src=\"../img/iconos/ico_rojo.gif\"></td>";
				$html .= "<td>Pendiente por Terminar</td>";*/
			$html .= "</tr>";
			$html .= "</table>";
		$html .= "</td>";
	$html .= "</tr>";
	$html .= "</table>";
	
	$objResponse->assign("tdMsj","innerHTML",$html);
	
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Solicitudes de Compra en Espera por Registrar");
	$objResponse->assign("tblDcto","width","650");
	$objResponse->script("
		if ($('divFlotante2').style.display == 'none') {
			$('divFlotante2').style.display='';
			centrarDiv($('divFlotante2'));
		}
	");
	
	return $objResponse;
}

function listArtSustitutoAlerteno($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL){
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
		
		
	$hddIdArticulo = $valCadBusq[0];
	if ($hddIdArticulo != ""){
		$sqlCond = sprintf("WHERE id_articulo <> %s", $hddIdArticulo);
	}
	
	$textCriterioBus = $valCadBusq[1];
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlCond) > 0) ? " AND " : " WHERE ";
		$sqlCond .= $cond.sprintf("(codigo_articulo LIKE %s
									OR descripcion LIKE %s
									OR marca LIKE %s
									OR tipo_articulo LIKE %s)",
								valTpDato("%".$valCadBusq[1]."%","text"),
								valTpDato("%".$valCadBusq[1]."%","text"),
								valTpDato("%".$valCadBusq[1]."%","text"),
								valTpDato("%".$valCadBusq[1]."%","text"));
	}
	
	$query = sprintf("SELECT * FROM vw_ga_articulos %s", $sqlCond);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
		//$objResponse->alert($query);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listArtSustitutoAlerteno", "20%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Codigo del Articulo"));
		$htmlTh .= ordenarCampo("xajax_listArtSustitutoAlerteno", "50%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Descripcion"));
		$htmlTh .= ordenarCampo("xajax_listArtSustitutoAlerteno", "10%", $pageNum, "marca", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Marcar"));
		$htmlTh .= ordenarCampo("xajax_listArtSustitutoAlerteno", "20%", $pageNum, "tipo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Tipo De Art"));
		
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td>".
			"<button  type=\"button\" onclick=\"xajax_cargaArticulo(%s, 'buscar');\" title=\"Seleccionar\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>",
				$row['id_articulo']
				); 
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['codigo_articulo'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['marca'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['tipo_articulo'])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"5\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listArtSustitutoAlerteno(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listArtSustitutoAlerteno(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listArtSustitutoAlerteno(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listArtSustitutoAlerteno(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listArtSustitutoAlerteno(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdartSustitutoAlterno","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function validarArtSustAlt($itemArticulo, $nombObje, $formArti){
	$objResponse = new xajaxResponse();
	
	$arraySust = array();
	$arrayAlt = array();
	
	foreach($formArti['textArtSustituto'] as $indice => $valores){
		$idSus = explode("|", $valores);
		$arraySust[$idSus[0]] = "";
	}
	
	foreach($formArti['textArtAleterno'] as $indice => $valores){
		$idAlt = explode("|", $valores);
		$arrayAlt[$idAlt[0]] = "";
	}
	
	switch($nombObje){
		case "artSust":
		if(array_key_exists($itemArticulo,$arraySust)){
			return $objResponse->alert("Este Items ya esta agregado en los Articulo Sustitutos");
		}
				break;
		case "artAlter":
		if(array_key_exists($itemArticulo,$arrayAlt)){
			return $objResponse->alert("Este Items ya esta agregado en los Articulo Alterno");
		}
				break;
	}
	
	$objResponse->loadCommands(insertaArtSustAlte($nombObje, $itemArticulo));

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarArticulo");
$xajax->register(XAJAX_FUNCTION,"buscarArtSustAlte");
$xajax->register(XAJAX_FUNCTION,"exportarExcel");
$xajax->register(XAJAX_FUNCTION,"cargaArticulo");
$xajax->register(XAJAX_FUNCTION,"cargarArtSustituto");
$xajax->register(XAJAX_FUNCTION,"cargarArtAlterno");
$xajax->register(XAJAX_FUNCTION,"cargaLstMarca");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoArticulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoUnidad");
$xajax->register(XAJAX_FUNCTION,"cargaLstSeccion");
$xajax->register(XAJAX_FUNCTION,"cargaLstSubSeccion");
$xajax->register(XAJAX_FUNCTION,"guardarArticulo");
$xajax->register(XAJAX_FUNCTION,"guardarArtSustAlter");
$xajax->register(XAJAX_FUNCTION,"eliminarArtSustAlterno");
$xajax->register(XAJAX_FUNCTION,"formArticulo");
$xajax->register(XAJAX_FUNCTION,"desactivarArticulo");
$xajax->register(XAJAX_FUNCTION,"insertaArtSustAlte"); 
$xajax->register(XAJAX_FUNCTION,"listadoArticulos");
$xajax->register(XAJAX_FUNCTION,"listadoDctosPedComp");
$xajax->register(XAJAX_FUNCTION,"listArtSustitutoAlerteno");
$xajax->register(XAJAX_FUNCTION,"validarArtSustAlt");

?>