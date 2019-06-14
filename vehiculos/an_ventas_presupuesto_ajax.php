<?php
require_once "../connections/conex.php";

@session_start();

// procesando ajax:
cache_expires();//reputacionCliente
//Recargas XML
if (isset($_GET['ajax_getcliente'])) {
	conectar();
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	$sql = sprintf("SELECT
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		cliente.nombre,
		cliente.apellido,
		CONCAT_WS(': ', CONCAT_WS('-', cliente.lci, cliente.ci), CONCAT_WS(' ', cliente.nombre, cliente.apellido)) AS nombre_cliente,
		cliente.telf,
		cliente.direccion,
		cliente.correo,
		cliente.ciudad,
		cliente.otrotelf,
		IF(cliente.tipo = 'Natural', IF(perfil_prospecto.sexo = 'M', 'Masculino', 'Femenino'),'') AS sexo_cliente,
		cliente.reputacionCliente + 0 AS id_reputacion_cliente,
		cliente.reputacionCliente,
		cliente.tipo_cuenta_cliente,
		cliente.tipo,
		cliente.paga_impuesto
	FROM cj_cc_cliente cliente
		LEFT JOIN crm_perfil_prospecto perfil_prospecto ON (cliente.id = perfil_prospecto.id)
		LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cliente.id_empleado_creador = vw_pg_empleado.id_empleado)
	WHERE cliente.id = %s;",
		valTpDato(getmysqlnum($_GET['ajax_getcliente']), "int"));
	$r = mysql_query($sql, $conex);
	if (!$r) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$row = mysql_fetch_assoc($r);
	
	$idConfiguracion = ($row['tipo'] == "Natural") ? 200 : 201;
	
	// VERIFICA VALORES DE CONFIGURACION
	$queryConfigRecaudos = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = %s
		AND config_emp.status = 1
		AND config_emp.id_empresa = %s;",
		valTpDato($idConfiguracion,"int"),
		valTpDato($idEmpresa,"int"));
	$rsConfigRecaudos = mysql_query($queryConfigRecaudos, $conex);
	if (!$rsConfigRecaudos) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowConfigRecaudos = mysql_fetch_assoc($rsConfigRecaudos);
	
	xmlstart();
	echo '<datos>';
		echo '<texto>';
			tagxml('txtIdCliente',$row['id']);
			tagxml('clientec',(str_replace("&","&#038;",$row['ci_cliente'])));
			tagxml('cedula',(str_replace("&","&#038;",$row['ci_cliente'])));
			tagxml('hddPagaImpuesto',($row['paga_impuesto']));
		echo '</texto>';
		echo '<capa>';
			tagxml('nombre',(str_replace("&","&#038;",$row['nombre'])));
			tagxml('apellido',(str_replace("&","&#038;",$row['apellido'])));
			tagxml('thab',(str_replace("&","&#038;",$row['telf'])));
			tagxml('celular',(str_replace("&","&#038;",$row['otrotelf'])));
			tagxml('direccion',(str_replace("&","&#038;",$row['direccion'])));
			tagxml('ciudad',(str_replace("&","&#038;",$row['ciudad'])));
			tagxml('sexo',(str_replace("&","&#038;",$row['sexo_cliente'])));
			tagxml('email',(str_replace("&","&#038;",$row['correo'])));
			tagxml('tdMsjCliente',"<![CDATA[ ".(($row['paga_impuesto'] == 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente Exento y/o Exonerado</div>" : "")." ]]>");
			
			tagxml('tdRecaudosProforma',preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/",htmlentities("<br>"),$rowConfigRecaudos['valor'])));
		echo '</capa>';
		echo '<function>';
			tagxml("activa","'cedula'");
			tagxml("enfoca","'modelo'");
			switch ($row['id_reputacion_cliente']) {
				case 1 : tagxml("reputacion","'#FF5F5F','".$row['reputacionCliente']."',true,'".$row['tipo_cuenta_cliente']."'"); break;
				case 2 : tagxml("reputacion","'#5AEF59','".$row['reputacionCliente']."','".$row['tipo_cuenta_cliente']."'"); break;
				default : tagxml("reputacion","'#FFFFFF','','','".$row['tipo_cuenta_cliente']."'");
			}
			tagxml("cargarXML","'vehiculo', objeto('txtIdUnidadBasica').value, objeto('txtIdEmpresa').value, objeto('txtIdCliente').value");
		echo '</function>';
		tagxml('closelist','listacliente');
	echo '</datos>';
}

if (isset($_GET['ajax_getvehiculo'])) {
	conectar();
	
	$queryUnidadBasica = sprintf("SELECT *,
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
		(SELECT vers.des_version FROM an_version vers WHERE vers.id_version = uni_bas.ver_uni_bas) AS desc_version
	FROM sa_unidad_empresa unidad_emp
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (unidad_emp.id_unidad_basica = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_uni_bas uni_bas ON (unidad_emp.id_unidad_basica = uni_bas.id_uni_bas)
		INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
	WHERE uni_bas.id_uni_bas = %s
		AND unidad_emp.id_empresa = %s
		AND uni_bas.catalogo = 1;",
		valTpDato(getmysqlnum($_GET['ajax_getvehiculo']), "int"),
		valTpDato(getmysqlnum($_GET['idEmpresa']), "int"));
	$rsUnidadBasica = mysql_query($queryUnidadBasica, $conex);
	if (!$rsUnidadBasica) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowUnidadBasica = mysql_fetch_assoc($rsUnidadBasica);
	
	if ($rowUnidadBasica['isan_uni_bas'] == 1) {
		$query = sprintf("SELECT
			iva.iva,
			iva.observacion
		FROM an_unidad_basica_impuesto uni_bas_impuesto
			INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
		WHERE uni_bas_impuesto.id_unidad_basica = %s
			AND iva.tipo IN (6)
			AND uni_bas_impuesto.id_impuesto NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
													WHERE cliente_imp_exento.id_cliente = %s);",
			valTpDato($rowUnidadBasica['id_unidad_basica'], "int"),
			valTpDato($_GET['idCliente'], "int"));
		$rs = mysql_query($query);
		if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$porcIva = $row['iva'];
		$condIva = (strlen($descIva) > 0) ? " e " : " Incluye ";
		$descIva .= $condIva.$row['observacion'];
	} else {
		$porcIva = "0";
		$descIva = "Exento";
	}
	
	if ($rowUnidadBasica['impuesto_lujo'] == 1) {
		$query = sprintf("SELECT
			iva.iva,
			iva.observacion
		FROM an_unidad_basica_impuesto uni_bas_impuesto
			INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
		WHERE uni_bas_impuesto.id_unidad_basica = %s
			AND iva.tipo IN (2)
			AND uni_bas_impuesto.id_impuesto NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
													WHERE cliente_imp_exento.id_cliente = %s);",
			valTpDato($rowUnidadBasica['id_unidad_basica'], "int"),
			valTpDato($_GET['idCliente'], "int"));
		$rs = mysql_query($query);
		if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$porcIvalujo = $row['iva'];
		$condIva = (strlen($descIva) > 0) ? " e " : " Incluye ";
		$descIva .= $condIva.$row['observacion'];
	} else {
		$porcIvalujo = "0";
	}
	
	if (file_exists($rowUnidadBasica['imagen_auto'])) {
		$img = "<img src=\"".$rowUnidadBasica['imagen_auto']."\" alt=\"Foto Referencial\" border=\"0\" height=\"200\"/>";
	} else {
		$img = "<img src=\"img/nodisponible.jpg\" alt=\"Foto Referencial\" border=\"0\" height=\"200\"/>";
	}
	
	xmlstart();
	echo "<datos>";
		echo "<texto>";
			tagxml('txtIdUnidadBasica',$rowUnidadBasica['id_uni_bas']);
			tagxml('modelo',($rowUnidadBasica['vehiculo']));
			tagxml('modeloc',($rowUnidadBasica['vehiculo']));
			tagxml('txtAno',($rowUnidadBasica['nom_ano']));
			tagxml('porcentaje_iva',$porcIva);
			tagxml('porcentaje_impuesto_lujo',$porcIvalujo);
		echo '</texto>';
		echo '<capa>';
			tagxml('precio1',numformat(floatval($rowUnidadBasica['pvp_venta1']),2,'.',','));
			tagxml('precio2',numformat(floatval($rowUnidadBasica['pvp_venta2']),2,'.',','));
			tagxml('precio3',numformat(floatval($rowUnidadBasica['pvp_venta3']),2,'.',','));
			tagxml('foto',"<![CDATA[ ".$img." ]]>");
			tagxml('eviva',$descIva);
			tagxml('descripcion',"<![CDATA[ ".($rowUnidadBasica['desc_version'])." ]]>");
		echo '</capa>';
		echo '<function>';
			tagxml("activa","'modelo'");
			tagxml("activa","'txtPrecioBase'");
			tagxml("percent","");
			tagxml("enfoca","'txtPrecioBase'");
			tagxml("asignarPrecio","");
		echo '</function>';
		tagxml('closelist','listavehiculo');
	echo '</datos>';
}

if (isset($_GET['ajax_getpoliza'])) {
	conectar();
	$sql = "SELECT
		contado_poliza,
		inicial_poliza,
		cuotas_poliza,
		cheque_poliza,
		financiada,
		meses_poliza
	FROM an_poliza
	WHERE id_poliza = ".getmysqlnum($_GET['ajax_getpoliza']).";";
	$r = mysql_query($sql, $conex);
	if (!$r) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$row = mysql_fetch_array($r);
		
	xmlstart();
	echo "<datos>";
		echo "<texto>";
			tagxml('monto_seguro',numformat(floatval($row[0]),2,'.',','));
			tagxml('inicial_poliza',numformat(floatval($row[1]),2,'.',','));
			tagxml('cuotas_poliza',numformat(floatval($row[2]),2,'.',','));
			tagxml('meses_poliza',$row[5]);
		echo "</texto>";
		echo "<capa>";
			tagxml('cheque_poliza',$row[3]);
			tagxml('financiada',$row[4]);
		echo "</capa>";
		echo "<function>";
			tagxml('activa','\'monto_seguro\'');
			tagxml('activa','\'inicial_poliza\'');
			tagxml('activa','\'cuotas_poliza\'');
			tagxml('activa','\'meses_poliza\'');
			tagxml('percent','');
			tagxml('enfoca','\'id_poliza\'');
		echo '</function>';
	echo '</datos>';
}

if (isset($_GET['ajax_getcombo'])) {
	conectar();
	$sql = "SELECT total_con_iva FROM an_combo
	WHERE id_combo = ".getmysqlnum($_GET['ajax_getcombo']).";";
	$r = mysql_query($sql, $conex);
	if (!$r) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$row = mysql_fetch_array($r);
	
	xmlstart();
	echo "<datos>";
		echo "<texto>";
			tagxml('vexacc1',numformat(floatval($row[0]),2,'.',','));
		echo "</texto>";
		echo "<capa>";
		echo "</capa>";
		echo "<function>";
			tagxml('percent','');
			tagxml('enfoca','\'id_combo\'');
		echo '</function>';
	echo '</datos>';
}

// SELECT BANCOS
if (isset($_GET['ajax_getbanco'])) {
	conectar();
	$valores = explode("|",$_GET['valores']);
	if (isset($valores)) {
		foreach ($valores as $indice => $valor) {
			$valor = explode("*",$valor);
			$arrayFinal[$valor[0]] = $valor[1];
		}
	}
	
	$queryBanco = "SELECT porcentaje_flat FROM bancos WHERE idBanco = ".getmysqlnum($_GET['ajax_getbanco']).";";
	$rsBanco = mysql_query($queryBanco, $conex);
	if (!$rsBanco) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsBanco = mysql_num_rows($rsBanco);
	$rowBanco = mysql_fetch_array($rsBanco);
	
	$queryFactor = "SELECT
		mes,
		factor,
		CONCAT(mes,' Meses / ',tasa,'%') AS financiamento
	FROM an_banco_factor
	WHERE id_banco = ".getmysqlnum($_GET['ajax_getbanco'])."
	ORDER BY tasa;";
	$rsFactor = @mysql_query($queryFactor);
	if (!$rsFactor) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsFactor = mysql_num_rows($rsFactor);
	
	if ($totalRowsBanco > 0) {
		$select = "<![CDATA[ <select id=\"lstMesesFinanciar\" name=\"lstMesesFinanciar\" class=\"inputHabilitado\" onchange=\"percent();\">";
			$select .= "<option value=\"\">-</option>";
		while($rowFactor = @mysql_fetch_assoc($rsFactor)) {
			$factores .= ",".$rowFactor['mes'].":".$rowFactor['factor'];
			$select .= "<option value=\"".$rowFactor['mes']."\">".$rowFactor['financiamento']."</option>";
		}
		$select .= "</select> ]]>";
		$factores[0] = " ";
		
		$select = ($totalRowsFactor > 0) ? $select : "<![CDATA[".
			"<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">".
			"<tr>".
				"<td><input type=\"text\" id=\"lstMesesFinanciar\" name=\"lstMesesFinanciar\" class=\"inputHabilitado\" onchange=\"percent(); asignarPrecio(); setformato(this);\" onkeypress=\"return inputnum(event);\" style=\"text-align:right; width:40px;\" value=\"".($arrayFinal['lstMesesFinanciar'])."\"/></td>".
				"<td>"." Meses"."</td>".
				"<td>"."&nbsp;/&nbsp;"."</td>".
				"<td><input type=\"text\" id=\"txtInteresCuotaFinanciar\" name=\"txtInteresCuotaFinanciar\" class=\"inputHabilitado\" onchange=\"percent(); asignarPrecio(); setformato(this);\" onkeypress=\"return inputnum(event);\" style=\"text-align:right; width:60px;\" value=\"".($arrayFinal['txtInteresCuotaFinanciar'])."\"/></td>".
				"<td>"."%"."</td>".
			"</tr>".
			"</table>".
		"]]>";
	}
	
	xmlstart();
	echo "<datos>";
		echo "<texto>";
			/*tagxml('monto_seguro',numformat(floatval($rowBanco['porcentaje_flat']),2,'.',','));
			tagxml('inicial_poliza',numformat(floatval($rowBanco[1]),2,'.',','));*/
			tagxml('porcentaje_flat',$rowBanco['porcentaje_flat']);
		echo "</texto>";
		echo "<capa>";
			tagxml('capaporcentaje_flat',$rowBanco['porcentaje_flat']);
			tagxml('capameses_financiar',$select);
			if ($totalRowsBanco > 0) {
				if ($totalRowsFactor > 0) {
					tagxml('tdtxtCuotasFinanciar',"<![CDATA[ <input type=\"text\" id=\"txtCuotasFinanciar\" name=\"txtCuotasFinanciar\" class=\"inputSinFondo\" onchange=\"setformato(this);\" readonly=\"readonly\" style=\"border:0px; text-align:right;\"/> ]]>");
				} else  {
					tagxml('tdtxtCuotasFinanciar',"<![CDATA[ <input type=\"text\" id=\"txtCuotasFinanciar\" name=\"txtCuotasFinanciar\" class=\"inputHabilitado\" onchange=\"percent(); asignarPrecio(); setformato(this);\" onkeypress=\"return inputnum(event);\" style=\"text-align:right;\" value=\"".($arrayFinal['txtCuotasFinanciar'])."\"/> ]]>");
				}
			} else {
				tagxml('tdtxtCuotasFinanciar',"<![CDATA[ <input type=\"text\" id=\"txtCuotasFinanciar\" name=\"txtCuotasFinanciar\" class=\"inputInicial\" onchange=\"setformato(this);\" readonly=\"readonly\" style=\"text-align:right;\" value=\"0.00\"/> ]]>");
			}
			//tagxml('financiada',$rowBanco[4]);
		echo "</capa>";
		echo "<function>";
			/*tagxml('activa','\'monto_seguro\'');
			tagxml('activa','\'inicial_poliza\'');*/
			tagxml('eval','\'factor={'.$factores.'};\'');
			tagxml('percent','');
			//tagxml('enfoca','\'id_poliza\'');
		echo "</function>";
	echo "</datos>";
}

// SELECT POLIZAS
if (isset($_GET['ajax_getpolizas'])) {
	$actual = getmysqlnum($_GET['ajax_getpolizas']);
	
	conectar();//
	$sql = "SELECT id_poliza, nombre_poliza FROM an_poliza;";
	$r = mysql_query($sql, $conex);
	if (!$r) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	
	xmlstart();
	echo "<datos>";
		echo "<texto>";
			$select = '<![CDATA[ <select name="id_poliza" id="id_poliza" style="width:128px;" onchange="asignarPoliza(this.value);"><option value="">-</option>';
			while($row = mysql_fetch_array($r)){
				$select .= '<option value="'.$row[0].'" ';
				if ($row[0] == $actual){
					$select .= ' selected="selected" ';
				}
				$select .= '>'.$row[1].'</option>';
			}
			$select .= '</select> ]]>';
		echo "</texto>";
		echo "<capa>";
			tagxml('capapoliza',$select);
			//tagxml('financiada',$row[4]);
		echo "</capa>";
		echo "<function>";
			if($actual != "") {
				tagxml('asignarPoliza',$actual);
			}
			tagxml('percent','');
		echo "</function>";
	echo "</datos>";
}

//////////////// LISTADOS ////////////////
if (isset($_GET['ajax_cedula'])) {
	$cadena = trim(excape($_GET['ajax_cedula']));
	$cadena = str_replace("#","",$cadena);
	$cadena = str_replace("--","",$cadena);
	
	if ($cadena != "") {
		conectar();
		$sql = sprintf("SELECT
			cliente.id,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			(reputacionCliente + 0) AS id_reputacion_cliente,
			cliente.reputacionCliente,
			cliente.tipo_cuenta_cliente
		FROM cj_cc_cliente cliente
		WHERE cliente.status = 'Activo'
			AND (cliente.nombre LIKE %s
				OR cliente.apellido LIKE %s
				OR CONCAT_WS('', cliente.lci, cliente.ci) LIKE %s
				OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
				OR CONCAT_WS(': ', CONCAT_WS('-', cliente.lci, cliente.ci), CONCAT_WS(' ', cliente.nombre, cliente.apellido)) LIKE %s)
		ORDER BY cliente.id;",
			valTpDato("%".$cadena."%", "text"),
			valTpDato("%".$cadena."%", "text"),
			valTpDato("%".$cadena."%", "text"),
			valTpDato("%".$cadena."%", "text"),
			valTpDato("%".$cadena."%", "text"));
		$r = mysql_query($sql, $conex);
		if (!$r) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRows = mysql_num_rows($r);
		
		echo "<table border=\"0\" style=\"border-collapse:collapse;\" width=\"100%\">";
		echo "<tr class=\"tituloCampo\">";
			echo "<td align=\"right\" class=\"textoNegrita_10px\" width=\"100%\">Mostrando ".$totalRows." de ".$totalRows." Registros&nbsp;</td>";
			echo "<td><a href=\"javascript:cancelarCliente();\"><img border=\"0\" src=\"../img/iconos/cross.png\" alt=\"Cerrar\"/></a></td>";
		echo "</tr>";
		echo "</table>";
		
		echo "<div id=\"overclientes\" class=\"overflowlist\">";
		if ($totalRows > 0) {
			echo "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
			echo "<tr align=\"center\" class=\"tituloColumna\">";
				echo "<td width=\"10%\">"."Id"."</td>";
				echo "<td width=\"20%\">".$spanClienteCxC."</td>";
				echo "<td width=\"55%\">"."Cliente"."</td>";
				echo "<td width=\"15%\">"."Tipo Cliente"."</td>";
			echo "</tr>";
			while ($row = mysql_fetch_array($r)) {
				$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$contFila ++;
				
				echo "<tr class=\"".$clase."\" onclick=\"javascript:cargarXML('cliente', '".$row['id']."', byId('txtIdEmpresa').value, '".$row['id']."');\" style=\"cursor:pointer\" height=\"24\">";
					echo "<td align=\"right\">".$row['id']."</td>";
					echo "<td align=\"right\">".utf8_encode($row['ci_cliente'])."</td>";
					echo "<td>".utf8_encode($row['nombre_cliente'])."</td>";
					echo "<td align=\"center\">";
					if ($row['tipo_cuenta_cliente'] == 2) {
						switch ($row['id_reputacion_cliente']) {
							case 1 : echo "<span class=\"divMsjError\">".$row['reputacionCliente']."</span>"; break; // C
							case 2 : echo "<span class=\"divMsjInfo2\">".$row['reputacionCliente']."</span>"; break; // B
							case 3 : echo "<span class=\"divMsjInfo\">".$row['reputacionCliente']."</span>"; break; // A
						}
					} else if ($row['tipo_cuenta_cliente'] == 1) {
						echo "<span class=\"divMsjAlerta\">"."Prospecto"."</span>";
					}
					echo "</td>";
				echo "</tr>";
			}
			echo "</table>";
		} else {
			echo "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			echo "<tr>";
				echo "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				echo "<td align=\"center\">No se encontraron registros</td>";
			echo "</tr>";
			echo "</table>";
		}
		echo "</div>";
		
		cerrar();
	}
	exit;
	
} elseif (isset($_GET['ajax_vehiculo'])) {
	$cadena = trim(excape($_GET['ajax_vehiculo']));
	$cadena = str_replace("#","",$cadena);
	$cadena = str_replace("--","",$cadena);
	
	conectar();
	//NOTA: la estructura de esta consulta debe adaptarse a las actualizaciones:  ------------
	
	$sql = sprintf("SELECT
		vw_iv_modelo.id_uni_bas,
		unidad_emp.id_empresa,
		vw_iv_modelo.nom_marca,
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
		
		(SELECT COUNT(*) FROM an_unidad_fisica uni_fis
		WHERE uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas
			AND uni_fis.estado_venta IN ('TRANSITO', 'POR REGISTRAR', 'SINIESTRADO', 'DISPONIBLE', 'RESERVADO')) AS cantidad_disponible
		
	FROM vw_iv_modelos vw_iv_modelo
		INNER JOIN sa_unidad_empresa unidad_emp ON (vw_iv_modelo.id_uni_bas = unidad_emp.id_unidad_basica)
	WHERE unidad_emp.id_empresa = %s
		AND vw_iv_modelo.catalogo = 1
		AND (TRIM(nom_uni_bas) LIKE TRIM(%s)
			OR TRIM(nom_marca) LIKE TRIM(%s)
			OR TRIM(nom_modelo) LIKE TRIM(%s)
			OR TRIM(nom_version) LIKE TRIM(%s)
			OR CONCAT(vw_iv_modelo.nom_uni_bas, ': ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) LIKE TRIM(%s))
	ORDER BY CONCAT(vw_iv_modelo.nom_uni_bas,': ', vw_iv_modelo.nom_modelo,' - ', vw_iv_modelo.nom_version);",
		valTpDato($_GET['idEmpresa'], "int"),
		valTpDato("%".$cadena."%", "text"),
		valTpDato("%".$cadena."%", "text"),
		valTpDato("%".$cadena."%", "text"),
		valTpDato("%".$cadena."%", "text"),
		valTpDato("%".$cadena."%", "text"));
	$r = mysql_query($sql, $conex);
	if (!$r) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRows = mysql_num_rows($r);
	
	echo "<table border=\"0\" style=\"border-collapse:collapse;\" width=\"100%\">".
	"<tr class=\"tituloCampo\">".
		"<td align=\"right\" class=\"textoNegrita_10px\" width=\"100%\">Mostrando ".$totalRows." de ".$totalRows." Registros&nbsp;</td>".
		"<td><a href=\"javascript:cancelarVehiculo();\"><img border=\"0\" src=\"../img/iconos/cross.png\" alt=\"Cerrar\"/></a></td>".
	"</tr>".
	"</table>";
	
	echo "<div id=\"overclientes\" class=\"overflowlist\">";
	if ($totalRows > 0) {
		echo "<table border=\"0\" class=\"texto_9px\" width=\"100%\">".
		"<tr align=\"center\" class=\"tituloColumna\">".
			"<td width=\"85%\">Vehículo</td>".
			"<td width=\"15%\">Cant. Disponible</td>".
		"</tr>";
		while ($row = mysql_fetch_assoc($r)) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila ++;
			
			$classDisponible = ($row['cantidad_disponible'] > 0) ? "class=\"divMsjInfo\"" : "class=\"divMsjError\"";
			
			echo "<tr class=\"".$clase."\" onclick=\"javascript:cargarXML('vehiculo', '".$row['id_uni_bas']."', '".$row['id_empresa']."', '".$_GET['idCliente']."');\" style=\"cursor:pointer\" height=\"24\">";
				echo "<td>".utf8_encode($row['vehiculo'])."</td>";
				echo "<td align=\"right\" ".$classDisponible.">".number_format($row['cantidad_disponible'], 2, ".", ",")."</td>";
			echo "</tr>";
		}
		echo "</table>";
	} else {
		echo "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">".
		"<tr>".
			"<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>".
			"<td align=\"center\">No se encontraron registros</td>".
		"</tr>".
		"</table>";
	}
	echo "</div>";
	
	cerrar();
	exit;
	
} else if (isset($_GET['ajax_acc'])) {
	$cadena = trim(excape($_GET['ajax_acc']));
	$cadena = str_replace("#","",$cadena);
	$cadena = str_replace("--","",$cadena);
	
	conectar();
	$iva = doubleval(getmysql("SELECT iva FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1 ORDER BY iva;"));
	
	$sql = sprintf("SELECT
		paq.id_paquete,
		paq.nom_paquete,
		paq.des_paquete
	FROM an_paquete paq
	WHERE (TRIM(nom_paquete) LIKE TRIM(%s)
		OR des_paquete LIKE %s)
		AND (SELECT COUNT(*) FROM an_acc_paq acc_paq
			WHERE acc_paq.id_paquete = paq.id_paquete) > 0;",
		valTpDato("%".$cadena."%", "text"),
		valTpDato("%".$cadena."%", "text"));
	$r = mysql_query($sql, $conex);
	if (!$r) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRows = mysql_num_rows($r);
	
	echo "<table border=\"0\" style=\"border-collapse:collapse;\" width=\"100%\">";
	echo "<tr class=\"tituloCampo\">";
		echo "<td align=\"right\" class=\"textoNegrita_10px\" width=\"100%\"></td>";
		echo "<td><a href=\"javascript:cancelarAdicional();\"><img border=\"0\" src=\"../img/iconos/cross.png\" alt=\"Cerrar\"/></a></td>";
	echo "</tr>";
	echo "</table>";
	
	echo "<div class=\"overflowlist\">";
	if ($totalRows > 0) {
		echo "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
		echo "<tr align=\"left\" class=\"divMsjInfo6\">";
			echo "<td><strong><em>Paquetes:</em></strong></td>";
		echo "</tr>";
		while ($row = mysql_fetch_row($r)) {
			$sql2 = sprintf("SELECT
				acc.id_accesorio,
				CONCAT(acc.nom_accesorio, IF(acc.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
				acc.des_accesorio,
				acc.id_tipo_accesorio,
				(CASE acc.id_tipo_accesorio
					WHEN 1 THEN	'Adicional'
					WHEN 2 THEN 'Accesorio'
					WHEN 3 THEN 'Contrato'
				END) AS descripcion_tipo_accesorio,
				acc.des_accesorio,
				acc.iva_accesorio,
				IF(acc.iva_accesorio = 1,(acc.precio_accesorio + (acc.precio_accesorio * ".$iva." / 100)), acc.precio_accesorio) AS precio_con_iva,
				acc.costo_accesorio,
				acc_paq.id_acc_paq
			FROM an_accesorio acc
				INNER JOIN an_acc_paq acc_paq ON (acc.id_accesorio = acc_paq.id_accesorio)
			WHERE acc_paq.id_paquete = %s;",
				valTpDato($row[0], "int"));
			$r2 = mysql_query($sql2, $conex);
			if (!$r2) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$totalRows2 = mysql_num_rows($r2);
			
			echo "<tr>".
				"<td>".
				"<form id=\"paq".$row[0]."\" style=\"margin:0px;\">".
					"<fieldset><legend class=\"legend\">".utf8_encode($row[1])." (".utf8_encode($row[2]).")</legend>".
						"<table border=\"0\" width=\"100%\">";
						if ($totalRows2 > 0) {
							$contFila = 0;
							while ($row2 = mysql_fetch_array($r2)) {
								$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
								$contFila++;
								
								$ivaAcc = ($row2['iva_accesorio'] == '1') ? getmysql("SELECT iva FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1 ORDER BY iva;"): '0';
								
								echo (fmod($contFila, 2) == 1) ? "<tr align=\"left\" class=\"".$clase."\" height=\"24\">" : "";
								
									echo "<td><input type=\"checkbox\" id=\"acc".$row2['id_accesorio']."\" name=\"".utf8_encode($row2['nom_accesorio'])."\" checked=\"checked\" value=\"".$row2['precio_con_iva']."\"/></td>".
									"<td width=\"50%\">".
										utf8_encode($row2['nom_accesorio']).
										"<input type=\"hidden\" id=\"pacc".$row2['id_accesorio']."\" value=\"".$row2['id_acc_paq']."\"/>".
										"<input type=\"hidden\" id=\"ivaacc".$row2['id_accesorio']."\" value=\"".$row2['iva_accesorio']."\"/>".
										"<input type=\"hidden\" id=\"civaacc".$row2['id_accesorio']."\" value=\"".$row2['costo_accesorio']."\"/>".
										"<input type=\"hidden\" id=\"pivaacc".$row2['id_accesorio']."\" value=\"".$ivaAcc."\"/>".
										"<input type=\"hidden\" id=\"hddTipoAccesorioacc".$row2['id_accesorio']."\" value=\"".$row2['id_tipo_accesorio']."\"/>".
										"<input type=\"hidden\" id=\"cbxCondicionacc".$row2['id_accesorio']."\" value=\"\"/>".
									"</td>";
								
								echo (fmod($contFila, 2) == 0) ? "</tr>" : "";
							}
							echo "<tr>".
								"<td align=\"center\" colspan=\"4\"><button type=\"button\" onclick=\"insertarPaquete(".$row[0].");\" style=\"cursor:default\" value=\"Agregar Paquete\"><table align=\"center\" cellpadding=\"0\" cellspacing=\"0\"><tr><td>&nbsp;</td><td><img src=\"../img/iconos/plus.png\"/></td><td>&nbsp;</td><td>Agregar Paquete</td></tr></table></button></td>".
							"</tr>";
						} else {
							echo "<td>".
								"<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">".
								"<tr>".
									"<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>".
									"<td align=\"center\">No existen accesorios en este paquete</td>".
								"</tr>".
								"</table>".
							"</td>";
						}
						echo "</table>".
					"</fieldset>".
				"</form>".
				"</td>".
			"</tr>";
		}
		echo "</table>";
	}
		
	$sql3 = sprintf("SELECT
		acc.id_accesorio,
		CONCAT(acc.nom_accesorio, IF (acc.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
		acc.des_accesorio,
		acc.id_tipo_accesorio,
		(CASE acc.id_tipo_accesorio
			WHEN 1 THEN	'Adicional'
			WHEN 2 THEN 'Accesorio'
			WHEN 3 THEN 'Contrato'
		END) AS descripcion_tipo_accesorio,
		acc.iva_accesorio,
		IF(acc.iva_accesorio = 1, (acc.precio_accesorio + (acc.precio_accesorio * ".$iva." / 100)), acc.precio_accesorio) AS precio_con_iva,
		acc.costo_accesorio
	FROM an_accesorio acc
	WHERE acc.id_tipo_accesorio IN (1,3)
		AND acc.id_modulo IN (2)
		AND (TRIM(acc.nom_accesorio) LIKE TRIM(%s)
			OR acc.des_accesorio LIKE %s)
	ORDER BY acc.id_tipo_accesorio ASC, acc.nom_accesorio ASC;",
		valTpDato("%".$cadena."%", "text"),
		valTpDato("%".$cadena."%", "text"));
	$r3 = mysql_query($sql3, $conex);
	if (!$r3) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRows3 = mysql_num_rows($r3);
	
	if ($totalRows3 > 0) {
		echo "<table border=\"0\" class=\"texto_9px\" width=\"100%\">".
		"<tr align=\"left\" class=\"trResaltar7\">".
			"<td colspan=\"4\"><strong><em>Adicionales:</em></strong></td>".
		"</tr>".
		"<tr align=\"center\" class=\"tituloColumna\">".
			"<td></td>".
			"<td width=\"42%\">Nombre</td>".
			"<td width=\"44%\">Descripción</td>".
			"<td width=\"14%\">Tipo de Adicional</td>".
		"</tr>";
		while ($row3 = mysql_fetch_array($r3)) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila ++;
			
			$ivaAcc = ($row3['iva_accesorio'] == '1') ? getmysql("SELECT SUM(iva) AS iva FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1 ORDER BY iva;") : '0';
			
			echo sprintf("<tr class=\"".$clase."\" height=\"24\" style=\"cursor:pointer\" onclick=\"insertarAdicional('acc%s','%s','%s','%s','%s','%s','%s');\">".
				"<td align=\"left\"><img src=\"../img/iconos/plus.png\"/></td>",
					$row3['id_accesorio'],
					$row3['precio_con_iva'],
					utf8_encode($row3['nom_accesorio']),
					$row3['iva_accesorio'],
					$row3['costo_accesorio'],
					$ivaAcc,
					$row3['id_tipo_accesorio']);
				echo "<td>".utf8_encode($row3['nom_accesorio'])."</td>".
					"<td>".utf8_encode($row3['des_accesorio'])."</td>".
					"<td align=\"center\">".utf8_encode($row3['descripcion_tipo_accesorio'])."</td>".
			"</tr>";
		}
		echo "</table>";
	}
		
	if (!($totalRows > 0) && !($totalRows3 > 0)) {
		echo "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">".
		"<tr>".
			"<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>".
			"<td align=\"center\">No se encontraron registros</td>".
		"</tr>".
		"</table>";
	}
	echo "</div>";
	
	cerrar();
	exit;
}
?>