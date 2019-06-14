<?php
class ModeloProspecto {
	public $idEmpresa;
	public $idProspecto;
	public $idEmpleado;
	
	function guardarProspecto($frmProspecto) {
		global $spanClienteCxC;
		global $arrayValidarCI;
		global $arrayValidarRIF;
		global $arrayValidarNIT;
		
		if ($frmProspecto['lstTipoProspecto'] > 0) {
			switch ($frmProspecto['lstTipoProspecto']) {
				case 1 :
					$lstTipoProspecto = "Natural";
					$arrayValidar = $arrayValidarCI;
					break;
				case 2 :
					$lstTipoProspecto = "Juridico";
					$arrayValidar = $arrayValidarRIF;
					break;
			}
			
			if (isset($arrayValidar)) {
				$valido = false;
				foreach ($arrayValidar as $indice => $valor) {
					if (preg_match($valor, $frmProspecto['txtCedulaProspecto'])) {
						$valido = true;
					}
				}
				
				if ($valido == false) {
					$objResponse->script("byId('txtCedulaProspecto').className = 'inputErrado'");
					return array(false, "Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				}
			}
			
			$arrayValidar = $arrayValidarNIT;
			if (isset($arrayValidar)) {
				if (strlen($frmProspecto['txtNitProspecto']) > 0) {
					$valido = false;
					foreach ($arrayValidar as $indice => $valor) {
						if (preg_match($valor, $frmProspecto['txtNitProspecto'])) {
							$valido = true;
						}
					}
					
					if ($valido == false) {
						$objResponse->script("byId('txtNitProspecto').className = 'inputErrado'");
						return array(false, ("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido"));
					}
				}
			}
			
			$txtCiCliente = explode("-",$frmProspecto['txtCedulaProspecto']);
			if (is_numeric($txtCiCliente[0]) == true) {
				$txtCiCliente = implode("-",$txtCiCliente);
			} else {
				$txtCiClientePuntos = str_split($txtCiCliente[0]);
				if (in_array(".",$txtCiClientePuntos)) { // VERIFICA SI TIENE PUNTOS
					$txtCiCliente = $txtCiCliente[0];
				} else {
					$txtLciCliente = $txtCiCliente[0];
					array_shift($txtCiCliente);
					$txtCiCliente = implode("-",$txtCiCliente);
				}
			}
			
			// VERIFICA QUE NO EXISTA LA CEDULA
			$query = sprintf("SELECT * FROM cj_cc_cliente
			WHERE ((lci IS NULL AND %s IS NULL AND ci LIKE %s)
					OR (lci IS NOT NULL AND lci LIKE %s AND ci LIKE %s))
				AND (id <> %s OR %s IS NULL);",
				valTpDato($txtLciCliente, "text"),
				valTpDato($txtCiCliente, "text"),
				valTpDato($txtLciCliente, "text"),
				valTpDato($txtCiCliente, "text"),
				valTpDato($this->idProspecto, "int"),
				valTpDato($this->idProspecto, "int"));
			$rs = mysql_query($query);
			if (!$rs) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_array($rs);
			
			if ($totalRows > 0) {
				return array(false, "Ya existe la ".$spanClienteCxC." ingresada");
			}
		} else {
			if (strlen($frmProspecto['txtCedulaProspecto']) > 0) {
				$txtCiCliente = explode("-",$frmProspecto['txtCedulaProspecto']);
			} else {
				// NUMERACION DEL DOCUMENTO
				$queryNumeracion = sprintf("SELECT *
				FROM pg_empresa_numeracion emp_num
					INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
				WHERE emp_num.id_numeracion = %s
					AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																									WHERE suc.id_empresa = %s)))
				ORDER BY aplica_sucursales DESC LIMIT 1;",
					valTpDato(54, "int"), // 54 = Prospecto Sin Identificación
					valTpDato($this->idEmpresa, "int"),
					valTpDato($this->idEmpresa, "int"));
				$rsNumeracion = mysql_query($queryNumeracion);
				if (!$rsNumeracion) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
				
				$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
				$idNumeraciones = $rowNumeracion['id_numeracion'];
				$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
				
				if ($rowNumeracion['numero_actual'] == "") { return array(false, "No se ha configurado numeracion de \"Prospecto Sin Identificación\""); }
				
				// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
				$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
				WHERE id_empresa_numeracion = %s;",
					valTpDato($idEmpresaNumeracion, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				$txtCiCliente = explode("-",$numeroActual);
			}
			
			if (is_numeric($txtCiCliente[0]) == true) {
				$txtCiCliente = implode("-",$txtCiCliente);
			} else {
				$txtCiClientePuntos = str_split($txtCiCliente[0]);
				if (in_array(".",$txtCiClientePuntos)) { // VERIFICA SI TIENE PUNTOS
					$txtCiCliente = $txtCiCliente[0];
				} else {
					$txtLciCliente = $txtCiCliente[0];
					array_shift($txtCiCliente);
					$txtCiCliente = implode("-",$txtCiCliente);
				}
			}
		}
		
		$frmProspecto['txtUrbanizacionProspecto'] = str_replace(",", "", $frmProspecto['txtUrbanizacionProspecto']);
		$frmProspecto['txtCalleProspecto'] = str_replace(",", "", $frmProspecto['txtCalleProspecto']);
		$frmProspecto['txtCasaProspecto'] = str_replace(",", "", $frmProspecto['txtCasaProspecto']);
		$frmProspecto['txtMunicipioProspecto'] = str_replace(",", "", $frmProspecto['txtMunicipioProspecto']);
		$frmProspecto['txtCiudadProspecto'] = str_replace(",", "", $frmProspecto['txtCiudadProspecto']);
		$frmProspecto['txtEstadoProspecto'] = str_replace(",", "", $frmProspecto['txtEstadoProspecto']);
		
		$txtDireccion = implode("; ", array(
			$frmProspecto['txtUrbanizacionProspecto'],
			$frmProspecto['txtCalleProspecto'],
			$frmProspecto['txtCasaProspecto'],
			$frmProspecto['txtMunicipioProspecto'],
			$frmProspecto['txtCiudadProspecto'],
			((strlen($frmProspecto['txtEstadoProspecto']) > 0) ? $spanEstado : "")." ".$frmProspecto['txtEstadoProspecto']));
		
		$txtDireccionCompania = implode("; ", array(
			$frmProspecto['txtUrbanizacionComp'],
			$frmProspecto['txtCalleComp'],
			$frmProspecto['txtCasaComp'],
			$frmProspecto['txtMunicipioComp'],
			((strlen($frmProspecto['txtEstadoComp']) > 0) ? $spanEstado : "")." ".$frmProspecto['txtEstadoComp']));
		
		if ($this->idProspecto > 0) {
			// EDITA LOS DATOS DEL PROSPECTO
			$updateSQL = sprintf("UPDATE cj_cc_cliente SET
				tipo = %s,
				nombre = %s,
				apellido = %s,
				lci = %s,
				ci = %s,
				nit = %s,
				urbanizacion = %s,
				calle = %s,
				casa = %s,
				municipio = %s,
				ciudad = %s,
				estado = %s,
				direccion = %s,
				telf = %s,
				otrotelf = %s,
				correo = %s,
				urbanizacion_postal = %s,
				calle_postal = %s,
				casa_postal = %s,
				municipio_postal = %s,
				ciudad_postal = %s,
				estado_postal = %s,
				urbanizacion_comp = %s,
				calle_comp = %s,
				casa_comp = %s,
				municipio_comp = %s,
				estado_comp = %s,
				direccionCompania = %s,
				telf_comp = %s,
				otro_telf_comp = %s,
				correo_comp = %s,
				licencia = %s,
				status = %s,
				fechaUltimaAtencion = %s,
				fechaUltimaEntrevista = %s,
				fechaProximaEntrevista = %s,
				id_empleado_creador = %s
			WHERE id = %s;",
				valTpDato($lstTipoProspecto, "text"),
				valTpDato($frmProspecto['txtNombreProspecto'], "text"),
				valTpDato($frmProspecto['txtApellidoProspecto'], "text"),
				valTpDato($txtLciCliente, "text"),
				valTpDato($txtCiCliente, "text"),
				valTpDato($frmProspecto['txtNitProspecto'], "text"),
				valTpDato($frmProspecto['txtUrbanizacionProspecto'], "text"),
				valTpDato($frmProspecto['txtCalleProspecto'], "text"),
				valTpDato($frmProspecto['txtCasaProspecto'], "text"),
				valTpDato($frmProspecto['txtMunicipioProspecto'], "text"),
				valTpDato($frmProspecto['txtCiudadProspecto'], "text"),
				valTpDato($frmProspecto['txtEstadoProspecto'], "text"),
				valTpDato($txtDireccion, "text"),
				valTpDato($frmProspecto['txtTelefonoProspecto'], "text"),
				valTpDato($frmProspecto['txtOtroTelefonoProspecto'], "text"),
				valTpDato($frmProspecto['txtCorreoProspecto'], "text"),
				valTpDato($frmProspecto['txtUrbanizacionPostalProspecto'], "text"),
				valTpDato($frmProspecto['txtCallePostalProspecto'], "text"),
				valTpDato($frmProspecto['txtCasaPostalProspecto'], "text"),
				valTpDato($frmProspecto['txtMunicipioPostalProspecto'], "text"),
				valTpDato($frmProspecto['txtCiudadPostalProspecto'], "text"),
				valTpDato($frmProspecto['txtEstadoPostalProspecto'], "text"),
				valTpDato($frmProspecto['txtUrbanizacionComp'], "text"),
				valTpDato($frmProspecto['txtCalleComp'], "text"),
				valTpDato($frmProspecto['txtCasaComp'], "text"),
				valTpDato($frmProspecto['txtMunicipioComp'], "text"),
				valTpDato($frmProspecto['txtEstadoComp'], "text"),
				valTpDato($txtDireccionCompania, "text"),
				valTpDato($frmProspecto['txtTelefonoComp'], "text"),
				valTpDato($frmProspecto['txtOtroTelefonoComp'], "text"),
				valTpDato($frmProspecto['txtEmailComp'], "text"),
				valTpDato($frmProspecto['txtLicenciaProspecto'], "text"),
				valTpDato("Activo", "text"),
				valTpDato((($frmProspecto['txtFechaUltAtencion'] != "") ? date("Y-m-d", strtotime($frmProspecto['txtFechaUltAtencion'])) : ""), "date"),
				valTpDato((($frmProspecto['txtFechaUltEntrevista'] != "") ? date("Y-m-d", strtotime($frmProspecto['txtFechaUltEntrevista'])) : ""), "date"),
				valTpDato((($frmProspecto['txtFechaProxEntrevista'] != "") ? date("Y-m-d", strtotime($frmProspecto['txtFechaProxEntrevista'])) : ""), "date"),
				valTpDato($this->idEmpleado, "int"),
				valTpDato($this->idProspecto, "int")); //este es el valor que tengo que almacenar en el perfil
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) {
				if (mysql_errno() == 1062) {
					return array(false, "Ya Existe un Prospecto ó Cliente con el C.I. / R.I.F que ingresado");
				} else {
					return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				}
			}
			mysql_query("SET NAMES 'latin1';");
			
		} else {
			
			// INSERTA LOS DATOS DEL PROSPECTO
			$insertSQL = sprintf("INSERT INTO cj_cc_cliente (tipo, nombre, apellido, lci, ci, nit, urbanizacion, calle, casa, municipio, ciudad, estado, direccion, telf, otrotelf, correo, urbanizacion_postal, calle_postal, casa_postal, municipio_postal, ciudad_postal, estado_postal, urbanizacion_comp, calle_comp, casa_comp, municipio_comp, estado_comp, direccionCompania, telf_comp, otro_telf_comp, correo_comp, licencia, status, fecha_creacion_prospecto, fechaUltimaAtencion, fechaUltimaEntrevista, fechaProximaEntrevista, id_empleado_creador, tipo_cuenta_cliente, fcreacion)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($lstTipoProspecto, "text"),
				valTpDato($frmProspecto['txtNombreProspecto'], "text"),
				valTpDato($frmProspecto['txtApellidoProspecto'], "text"),
				valTpDato($txtLciCliente, "text"),
				valTpDato($txtCiCliente, "text"),
				valTpDato($frmProspecto['txtNitProspecto'], "text"),
				valTpDato($frmProspecto['txtUrbanizacionProspecto'], "text"),
				valTpDato($frmProspecto['txtCalleProspecto'], "text"),
				valTpDato($frmProspecto['txtCasaProspecto'], "text"),
				valTpDato($frmProspecto['txtMunicipioProspecto'], "text"),
				valTpDato($frmProspecto['txtCiudadProspecto'], "text"),
				valTpDato($frmProspecto['txtEstadoProspecto'], "text"),
				valTpDato($txtDireccion, "text"),
				valTpDato($frmProspecto['txtTelefonoProspecto'], "text"),
				valTpDato($frmProspecto['txtOtroTelefonoProspecto'], "text"),
				valTpDato($frmProspecto['txtCorreoProspecto'], "text"),
				valTpDato($frmProspecto['txtUrbanizacionPostalProspecto'], "text"),
				valTpDato($frmProspecto['txtCallePostalProspecto'], "text"),
				valTpDato($frmProspecto['txtCasaPostalProspecto'], "text"),
				valTpDato($frmProspecto['txtMunicipioPostalProspecto'], "text"),
				valTpDato($frmProspecto['txtCiudadPostalProspecto'], "text"),
				valTpDato($frmProspecto['txtEstadoPostalProspecto'], "text"),
				valTpDato($frmProspecto['txtUrbanizacionComp'], "text"),
				valTpDato($frmProspecto['txtCalleComp'], "text"),
				valTpDato($frmProspecto['txtCasaComp'], "text"),
				valTpDato($frmProspecto['txtMunicipioComp'], "text"),
				valTpDato($frmProspecto['txtEstadoComp'], "text"),
				valTpDato($txtDireccionCompania, "text"),
				valTpDato($frmProspecto['txtTelefonoComp'], "text"),
				valTpDato($frmProspecto['txtOtroTelefonoComp'], "text"),
				valTpDato($frmProspecto['txtEmailComp'], "text"),
				valTpDato($frmProspecto['txtLicenciaProspecto'], "text"),
				valTpDato("Activo", "text"),
				valTpDato("NOW()", "campo"),
				valTpDato((($frmProspecto['txtFechaUltAtencion'] != "") ? date("Y-m-d", strtotime($frmProspecto['txtFechaUltAtencion'])) : ""), "date"),
				valTpDato((($frmProspecto['txtFechaUltEntrevista'] != "") ? date("Y-m-d", strtotime($frmProspecto['txtFechaUltEntrevista'])) : ""), "date"),
				valTpDato((($frmProspecto['txtFechaProxEntrevista'] != "") ? date("Y-m-d", strtotime($frmProspecto['txtFechaProxEntrevista'])) : ""), "date"),
				valTpDato($this->idEmpleado, "int"),
				valTpDato(1, "int"),
				valTpDato("NOW()", "campo")); // 1 = Prospecto, 2 = Cliente
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) {
				if (mysql_errno() == 1062) {
					return array(false, "Ya Existe un Prospecto ó Cliente con el C.I. / R.I.F que ingresado");
				} else {
					return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$insertSQL);
				}
			}
			$this->idProspecto = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
		}
		
		// COSULTO SI EL CLIENTE TIENE UN PERFIL O NO
		$queryPerfil = sprintf("SELECT id FROM crm_perfil_prospecto WHERE id = %s;", valTpDato($this->idProspecto, "int"));
		$rsPerfil = mysql_query($queryPerfil);
		$totalRowsPerfil = mysql_num_rows($rsPerfil);
		$rowPerfil = mysql_fetch_assoc($rsPerfil);
		
		$idPerfilProspecto = $rowPerfil['id_perfil_prospecto'];
		
		if ($totalRowsPerfil > 0) {
			// EDITA LOS DATOS DEL PERFIL DEL PROSPECTO SI EXISTE
			$updatePerfilProspecto = sprintf("UPDATE crm_perfil_prospecto SET
				id_puesto = %s,
				id_titulo = %s,
				id_posibilidad_cierre = %s ,
				id_sector = %s,
				id_nivel_influencia = %s,
				id_estatus = %s,
				fecha_actualizacion = NOW(),
				compania = %s,
				id_estado_civil = %s,
				sexo = %s,
				fecha_nacimiento = %s,
				clase_social = %s,
				observacion = %s,
				id_motivo_rechazo = %s
			WHERE id = %s;",
				valTpDato($frmProspecto['puesto'], "int"),
				valTpDato($frmProspecto['titulo'], "int"),
				valTpDato($frmProspecto['posibilidad_cierre'], "int"),
				valTpDato($frmProspecto['sector'], "int"),
				valTpDato($frmProspecto['nivel_influencia'], "int"),
				valTpDato($frmProspecto['estatus'], "int"),
				valTpDato($frmProspecto['txtCompania'], "text"),
				valTpDato($frmProspecto['lstEstadoCivil'], "int"), 
				valTpDato($frmProspecto['rdbSexo'], "text"),
				valTpDato((($frmProspecto['txtFechaNacimiento'] != "") ? date("Y-m-d",strtotime($frmProspecto['txtFechaNacimiento'])) : ""), "date"),
				valTpDato($frmProspecto['lstNivelSocial'], "text"),
				valTpDato($frmProspecto['txtObservacion'], "text"),
				valTpDato($frmProspecto['lstMotivoRechazo'], "int"),
				valTpDato($this->idProspecto, "int"));
			mysql_query("SET NAME 'utf8'");
			$queryPerfilProspecto = mysql_query($updatePerfilProspecto);
			if (!$queryPerfilProspecto) {
				if (mysql_errno() == 1062) {
					return array(false, "Ya Existe un Prospecto ó Cliente con el C.I. / R.I.F que ingresado");
				} else {
					return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				}
			}
			mysql_query("SET NAMES 'latin1';");
		} else {
			// INSERTA LOS DATOS DEL PERFIL DEL PROSPECTO
			$insertPerfilProspecto = sprintf("INSERT INTO crm_perfil_prospecto (id, id_puesto, id_titulo, id_posibilidad_cierre, id_sector, id_nivel_influencia, id_estatus, Compania, id_estado_civil, sexo, fecha_nacimiento, clase_social, observacion, id_motivo_rechazo)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($this->idProspecto, "int"),
				valTpDato($frmProspecto['puesto'], "int"),
				valTpDato($frmProspecto['titulo'], "int"),
				valTpDato($frmProspecto['posibilidad_cierre'], "int"),
				valTpDato($frmProspecto['sector'], "int"),
				valTpDato($frmProspecto['nivel_influencia'], "int"),
				valTpDato($frmProspecto['estatus'], "int"),
				valTpDato($frmProspecto['txtCompania'], "text"),
				valTpDato($frmProspecto['lstEstadoCivil'], "int"), 
				valTpDato($frmProspecto['rdbSexo'], "text"),
				valTpDato((($frmProspecto['txtFechaNacimiento'] != "") ? date("Y-m-d",strtotime($frmProspecto['txtFechaNacimiento'])) : ""), "date"),
				valTpDato($frmProspecto['lstNivelSocial'], "text"),
				valTpDato($frmProspecto['txtObservacion'], "text"), 
				valTpDato($frmProspecto['lstMotivoRechazo'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertPerfilProspecto);
			if (!$Result1) {
				if (mysql_errno() == 1062) {
					return array(false, "Ya Existe un Prospecto ó Cliente con el C.I. / R.I.F que ingresado");
				} else {
					return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$insertPerfilProspecto);
				}
			}
			$idPerfilProspecto = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
		}
		
		return array(
			0 => true,
			"idProspecto" => $this->idProspecto,
			"idPerfilProspecto" => $idPerfilProspecto);
	}
	
}
?>