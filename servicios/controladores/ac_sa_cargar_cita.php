<?php

/**
 * Toma el archivo del formulario lo lee desglosa y envia a guardar
 * @param string $nombreInputArchivo Nombre del input tipo file
 * @return script javascript con alert del error
 */
function guardarArchivo($nombreInputArchivo){
    
    $nombreArchivo = $_FILES[$nombreInputArchivo]["name"];
    $tipoArchivo = $_FILES[$nombreInputArchivo]["type"];
    $nombreTemporal = $_FILES[$nombreInputArchivo]["tmp_name"];
    $error = $_FILES[$nombreInputArchivo]["error"];
    $size = $_FILES[$nombreInputArchivo]["size"];
    
    if($error != 0 || $size == 0){
        return errorJs("Error no se pudo cargar el archivo");
    }
    
    if($tipoArchivo != "text/plain"){
        return errorJs("Error el archivo no es texto plano");
    }
    
    $archivo = fopen($nombreTemporal,"r");
    
    $arrayLineas = array();
    
    if($archivo){
        while(!feof($archivo)){
            $linea = fgets($archivo);
            $arrayLineas[] = $linea;        
        }
        fclose($archivo);
    }else{
        return errorJs("Error el archivo no se pudo leer");
    }    
    
    $arrayLineasRespuesta = array();
    
    
    if(count($arrayLineas) > 0){
        foreach($arrayLineas as $numeroLinea => $textoLinea){
            $cantidadLetras = strlen($textoLinea);
            if($cantidadLetras == 1807){//1805 + \n = 1807
               
                //var_dump(trimArray(distribucionTexto($textoLinea)));
                $lineaDistribuida = trimArray(distribucionTexto($textoLinea));
                $distribucionRespuesta = guardarCitaAgenda($lineaDistribuida);
                $arrayLineasRespuesta[] = $distribucionRespuesta;
                                
            }elseif($cantidadLetras == 0){//salta al siguiente aunque no exista
                echo "Fin de archivo, última Linea Escrita: ".$numeroLinea." finalización de linea: ".($numeroLinea+1);
            }else{
                echo "Error, Caracteres (".$cantidadLetras.") no es la cantidad de caracteres esperados (1807) en linea: ".($numeroLinea+1)."<br>";//."<b>".$textoLinea."</b><br>";
            }
        }
    }
        
    echo listadoCargaCita($arrayLineasRespuesta, true);
    
}

/**
 * Convierte una linea del archivo conteniendo toda la informacion de la cita, en un array
 * @param string $texto Linea del archivo
 * @return array Las lineas separadas en dicha informacion
 */
function distribucionTexto($texto){
    $arrayDivision = array();//* LO POSEE EL DOCUMENTO **LO POSEE Y USO
    
    $arrayDivision["fecha_agenda"] = substr($texto, 1-1, 8);//* inicio 0 y finaliza en cantidad de carctares
    $arrayDivision["hora_agenda"] = substr($texto, 9-1, 6);//*
    $arrayDivision["tipo_registro"] = substr($texto, 15-1, 1);//**
    $arrayDivision["hora_cita"] = substr($texto, 16-1, 4);//**
    $arrayDivision["fecha_cita"] = substr($texto, 20-1, 8);//**
    $arrayDivision["codigo_concesionario"] = substr($texto, 28-1, 5);//*
    $arrayDivision["codigo_asesor"] = substr($texto, 33-1, 13);//**
    $arrayDivision["estatus_agendamiento"] = substr($texto, 46-1, 2);//**
    $arrayDivision["adicional_estatus"] = substr($texto, 48-1, 100);
    $arrayDivision["placa"] = substr($texto, 148-1, 15);//**
    $arrayDivision["codigo_modelo"] = substr($texto, 163-1, 10);
    $arrayDivision["descripcion_modelo"] = substr($texto, 173-1, 15);
    $arrayDivision["chasis"] = substr($texto, 188-1, 17);//**
    $arrayDivision["ci_cliente"] = substr($texto, 205-1, 25);//**
    $arrayDivision["nombre_cliente"] = substr($texto, 230-1, 40);//**
    $arrayDivision["apellido_cliente"] = substr($texto, 270-1, 40);//**
    $arrayDivision["correo_cliente"] = substr($texto, 310-1, 80);//**
    $arrayDivision["codigo_ciudad_cliente_1"] = substr($texto, 390-1, 7);
    $arrayDivision["telefono_cliente_1"] = substr($texto, 397-1, 15);
    $arrayDivision["codigo_ciudad_cliente_2"] = substr($texto, 412-1, 7);
    $arrayDivision["telefono_cliente_2"] = substr($texto, 419-1, 15);
    $arrayDivision["anexo_cliente"] = substr($texto, 434-1, 10);
    $arrayDivision["prefijo_celular_cliente"] = substr($texto, 444-1, 7);//**
    $arrayDivision["celular_cliente"] = substr($texto, 451-1, 15);//**
    $arrayDivision["verbalizacion_cliente"] = substr($texto, 466-1, 500);
    $arrayDivision["codigo_servicio_realizado"] = substr($texto, 966-1, 3);//*
    $arrayDivision["descripcion_servicio_realizado"] = substr($texto, 969-1, 250);//**
    $arrayDivision["direccion_cliente"] = substr($texto, 1219-1, 100);//**
    $arrayDivision["numero_direccion_cliente"] = substr($texto, 1319-1, 10);//*
    $arrayDivision["complemento_direcion_cliente"] = substr($texto, 1329-1, 30);
    $arrayDivision["barrio_comuna_cliente"] = substr($texto, 1359-1, 40);
    $arrayDivision["ciudad_cliente"] = substr($texto, 1399-1, 40);//**
    $arrayDivision["codigo_postal_cliente"] = substr($texto, 1439-1, 20);//*
    $arrayDivision["nombre_asesor_servicio"] = substr($texto, 1459-1, 60);//*
    $arrayDivision["comentarios_asesor_servicio"] = substr($texto, 1519-1, 200);
    $arrayDivision["cliente_sin_cita"] = substr($texto, 1719-1, 1);
    $arrayDivision["retorno_de_servicio"] = substr($texto, 1720-1, 1);
    $arrayDivision["reagendamiento"] = substr($texto, 1721-1, 1);
    $arrayDivision["hora_llegada_cliente"] = substr($texto, 1722-1, 4);
    $arrayDivision["fecha_salida_efectiva"] = substr($texto, 1726-1, 8);
    $arrayDivision["hora_salida_efectiva"] = substr($texto, 1734-1, 4);
    $arrayDivision["usuario_efectuo_cita"] = substr($texto, 1738-1, 20);//*
    $arrayDivision["ultima_fecha_download"] = substr($texto, 1758-1, 8);
    $arrayDivision["fecha_cancelacion"] = substr($texto, 1766-1, 8);
    $arrayDivision["hora_cancelacion"] = substr($texto, 1774-1, 4);
    $arrayDivision["usuario_efectuo_cancelacion"] = substr($texto, 1778-1, 20);
    $arrayDivision["ultima_fecha_download_cancelacion"] = substr($texto, 1798-1, 8);

    return $arrayDivision;
}

/**
 * Traductor de cita, genera formato agenda
 * @return array Array asociativo con toda la cita en resumen
 */
function uploadAgendamiento($arrayCitaTraducir){
    $arrayAgendamiento = array();
    
    $arrayAgendamiento["tipo_registro"] = "1";//no cambiar 1 = cita resumen
    $arrayAgendamiento["hora_cita"] = horaAgenda($arrayCitaTraducir["hora_cita"]); //"0935"
    $arrayAgendamiento["fecha_cita"] = fechaAgenda($arrayCitaTraducir["fecha_cita"]);//"28092014"
    $arrayAgendamiento["codigo_concesionario"] = rellenarNumero($arrayCitaTraducir["codigo_concesionario"],"5");//"00047"
    $arrayAgendamiento["codigo_asesor"] = rellenarNumero($arrayCitaTraducir["codigo_asesor"],"13");//"100076"
    $arrayAgendamiento["hora_llegada_cliente"] = horaAgenda($arrayCitaTraducir["hora_llegada_cliente"]);//"0955"
    $arrayAgendamiento["fecha_salida_efectiva"] = fechaAgenda($arrayCitaTraducir["fecha_salida_efectiva"]);//"30092014"
    $arrayAgendamiento["hora_salida_efectiva"] = horaAgenda($arrayCitaTraducir["hora_salida_efectiva"]);//"1112"
    $arrayAgendamiento["kilometraje"] = rellenarNumero($arrayCitaTraducir["kilometraje"],"9");
    $arrayAgendamiento["comentarios_asesor_servicio"] = rellenar(substr($arrayCitaTraducir["comentarios_asesor_servicio"],0,200),"200");//no requerido
    $arrayAgendamiento["valor_total_piezas"] = rellenarFlotante($arrayCitaTraducir["valor_total_piezas"],"15");
    $arrayAgendamiento["valor_total_manodeobra"] = rellenarFlotante($arrayCitaTraducir["valor_total_manodeobra"],"15");
    $arrayAgendamiento["valor_total_servicio"] = rellenarFlotante($arrayCitaTraducir["valor_total_servicio"],"15");
    $arrayAgendamiento["inspeccionado_elevador"] = rellenar($arrayCitaTraducir["inspeccionado_elevador"],"1");//no requerido
    $arrayAgendamiento["sobre_turno"] = rellenar($arrayCitaTraducir["sobre_turno"],"1");//no requerido
    $arrayAgendamiento["cita_retorno"] = rellenar($arrayCitaTraducir["cita_retorno"],"1");//no requerido
    $arrayAgendamiento["presupuesto_previo"] = rellenar($arrayCitaTraducir["presupuesto_previo"],"1");//no requerido
    $arrayAgendamiento["vehiculo_listo_revisado"] = rellenar($arrayCitaTraducir["vehiculo_listo_revisado"],"1");//no requerido
    $arrayAgendamiento["explicacion_trabajo"] = rellenar($arrayCitaTraducir["explicacion_trabajo"],"1");//no requerido
    $arrayAgendamiento["valor_total_terceros"] = rellenarFlotante($arrayCitaTraducir["valor_total_terceros"],"15");//no requerido
    $arrayAgendamiento["valor_total_otros"] = rellenarFlotante($arrayCitaTraducir["valor_total_otros"],"15");//no requerido
    
    return $arrayAgendamiento;
}

function verificarUploadAgendamiento($texto){
    if(is_array($texto)){
        $texto = implode("",$texto);
    }
    
    if(strlen($texto) == 337){//337 por array, 337 en linea + \n deberia = 339
        return true;
    }else{
 		$myfile = fopen("log/descarga_agenda.txt", "a") or die("Unable to open file!");
		fwrite($myfile, $texto."\n");
		fclose($myfile);
        return false;
    }
    //338 base 339 archivo con enter (342) por el punto en flotante
}

/**
 * Traductor de ordenes, genera formato agenda
 * @return array Array asociativo con toda la orden en resumen
 */
function uploadOrdenServicio($arrayOrdenTraducir){
    $arrayOrden = array();
    
    $arrayOrden["tipo_registro"] = "2";//no cambiar 2 = orden resumen
    $arrayOrden["hora_cita"] = horaAgenda($arrayOrdenTraducir["hora_cita"]);
    $arrayOrden["fecha_cita"] = fechaAgenda($arrayOrdenTraducir["fecha_cita"]);
    $arrayOrden["codigo_concesionario"] = rellenarNumero($arrayOrdenTraducir["codigo_concesionario"],"5");
    $arrayOrden["codigo_asesor"] = rellenarNumero($arrayOrdenTraducir["codigo_asesor"],"13");    
    $arrayOrden["numero_orden"] = rellenarNumero($arrayOrdenTraducir["numero_orden"],"9");
    $arrayOrden["fecha_apertura"] = fechaAgenda($arrayOrdenTraducir["fecha_apertura"]);
    $arrayOrden["hora_apertura"] = horaAgenda($arrayOrdenTraducir["hora_apertura"]);
    $arrayOrden["fecha_cierre"] = fechaAgenda($arrayOrdenTraducir["fecha_cierre"]);
    $arrayOrden["hora_cierre"] = horaAgenda($arrayOrdenTraducir["hora_cierre"]);
    $arrayOrden["codigo_asesor_dms"] = rellenarNumero($arrayOrdenTraducir["codigo_asesor_dms"],"11");//no requerido
    $arrayOrden["nombre_completo_tecnico"] = rellenar($arrayOrdenTraducir["nombre_completo_tecnico"],"100");//no requerido
    $arrayOrden["tipo_orden"] = tipoOrdenAgenda($arrayOrdenTraducir["tipo_orden"]);//"W"
    $arrayOrden["minutos_trabajados"] = rellenarNumero($arrayOrdenTraducir["minutos_trabajados"],"6");
    $arrayOrden["total_repuestos"] = rellenarFlotante($arrayOrdenTraducir["total_repuestos"],"15");
    $arrayOrden["total_manodeobra"] = rellenarFlotante($arrayOrdenTraducir["total_manodeobra"],"15");
    $arrayOrden["total_total"] = rellenarFlotante($arrayOrdenTraducir["total_total"],"15");
    $arrayOrden["codigo_grupo_servicio"] = rellenar("","4");//no modificar, vacio. //no requerido
    $arrayOrden["total_terceros_tot"] = rellenarFlotante($arrayOrdenTraducir["total_terceros_tot"],"15");//no requerido
    $arrayOrden["total_otros_notas"] = rellenarFlotante($arrayOrdenTraducir["total_otros_notas"],"15");//no requerido
    
    return $arrayOrden;
}

function verificarUploadServicio($texto){
    if(is_array($texto)){
        $texto = implode("",$texto);
    }
    
    if(strlen($texto) == 261){
        return true;
    }else{
        return false;
    }
}

/**
 * Traductor de repuestos, genera formato agenda
 * @return array Array asociativo con el repuesto
 */
function uploadRepuestos($arrayRepuestoTraducir){
    $arrayRepuestos = array();
    
    $arrayRepuestos["tipo_registro"] = "3";//no cambiar 3 = repuestos individual
    $arrayRepuestos["hora_cita"] = horaAgenda($arrayRepuestoTraducir["hora_cita"]);
    $arrayRepuestos["fecha_cita"] = fechaAgenda($arrayRepuestoTraducir["fecha_cita"]);
    $arrayRepuestos["codigo_concesionario"] = rellenarNumero($arrayRepuestoTraducir["codigo_concesionario"],"5");
    $arrayRepuestos["codigo_asesor"] = rellenarNumero($arrayRepuestoTraducir["codigo_asesor"],"13");    
    $arrayRepuestos["numero_orden"] = rellenarNumero($arrayRepuestoTraducir["numero_orden"],"9");
    $arrayRepuestos["codigo"] = rellenar($arrayRepuestoTraducir["codigo"],"25");
    $arrayRepuestos["cantidad"] = rellenarNumero($arrayRepuestoTraducir["cantidad"],"4");
    $arrayRepuestos["precio_unitario"] = rellenarFlotante($arrayRepuestoTraducir["precio_unitario"],"15");
        
    return $arrayRepuestos;
}

function verificarUploadRepuestos($texto){
    if(is_array($texto)){
        $texto = implode("",$texto);
    }
    
    if(strlen($texto) == 84){
        return true;
    }else{
        return false;
    }
}

/**
 * Guarda la cita partiendo de la linea leida en el archivo
 * @param Array $arrayDivision Contiene la linea distribuida
 */
function guardarCitaAgenda($arrayDivision){

    //Variables usadas:
//    $arrayDivision["tipo_registro"]
//    $arrayDivision["hora_cita"] 
//    $arrayDivision["fecha_cita"] 
//    $arrayDivision["codigo_asesor"]  
//    $arrayDivision["estatus_agendamiento"]  
//    $arrayDivision["placa"]  
//    $arrayDivision["chasis"]  
//    $arrayDivision["ci_cliente"]  
//    $arrayDivision["nombre_cliente"]  
//    $arrayDivision["apellido_cliente"]  
//    $arrayDivision["correo_cliente"]  
//    $arrayDivision["prefijo_celular_cliente"] 
//    $arrayDivision["celular_cliente"] 
//    $arrayDivision["descripcion_servicio_realizado"]  
//    $arrayDivision["direccion_cliente"]  
//    $arrayDivision["ciudad_cliente"]
    //$arrayDivision['estado_carga'] agrego el estado del proceso luego de carga
    //$arrayDivision['error'] para devolver errores sql
    
    $arrayDivision['error'] = "";
    
    mysql_query("START TRANSACTION");
    
    $resultadoCodigoAsesor = idCodigoAsesor($arrayDivision["codigo_asesor"]);
    if($resultadoCodigoAsesor[0] === false){ $arrayDivision['error'] = $resultadoCodigoAsesor[1]; mysql_query("ROLLBACK"); return $arrayDivision;}
    
    $idAsesor = $resultadoCodigoAsesor[1];
    
    if($arrayDivision["tipo_registro"] == "1"){
    
        
        //compruebo los valores:
        $validacion = validarCargaCita($arrayDivision,$idAsesor);       
        if($validacion[0] == false){ $arrayDivision['error'] = $validacion[1]; mysql_query("ROLLBACK"); return $arrayDivision;  }
            
            //compruebo que la cita exista
            $query = sprintf("SELECT * FROM sa_cita WHERE fecha_cita = '%s' AND hora_inicio_cita = '%s' 
                                AND estado_cita != 'CANCELADA' AND estado_cita != 'POSPUESTA' 
                                AND id_empleado_servicio = %s AND id_empresa = %s
                                LIMIT 1",
                            fechaBd($arrayDivision["fecha_cita"]),
                            horaBd($arrayDivision["hora_cita"]),
                            $idAsesor,
                            valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int"));
            $rs = mysql_query($query);
            if(!$rs){ $arrayDivision['error'] = "Error: ".mysql_error()." Linea: ".__LINE__; mysql_query("ROLLBACK"); return $arrayDivision;}

            if(mysql_num_rows($rs) == 0){//cita nueva

                $resultadoCliente = registroCliente($arrayDivision);                
                if($resultadoCliente[0] === false){ $arrayDivision['error'] = $resultadoCliente[1]; mysql_query("ROLLBACK"); return $arrayDivision;}
                
                if($resultadoCliente[0]){//verifico registro cliente
                    
                    $idCliente = $resultadoCliente[1];
                    
                    $resultadoEmpresa = registroEmpresaCliente($idCliente);//si posee empresa o no
                    if($resultadoEmpresa[0] === false){ $arrayDivision['error'] = $resultadoEmpresa[1]; mysql_query("ROLLBACK"); return $arrayDivision;}                    
                    
                    $resultadoVehiculo = registroVehiculo($arrayDivision,$idCliente);
                    if($resultadoVehiculo[0] === false){ $arrayDivision['error'] = $resultadoVehiculo[1]; mysql_query("ROLLBACK"); return $arrayDivision;}
                    
                    $idRegistroPlacas = $resultadoVehiculo[1];
                    
                    $resultadoCita = registroCita($arrayDivision,$idCliente,$idRegistroPlacas);
                    if($resultadoCita[0] === false){ $arrayDivision['error'] = $resultadoCita[1]; mysql_query("ROLLBACK"); return $arrayDivision; }
                    //$arrayDivision['error'] = "MALL"; mysql_query("ROLLBACK"); return $arrayDivision;//para detener en pruebas
                    $arrayDivision['estado_carga'] = "<span class='verde'>AGENDADA NUEVA</span>";
                }
                
            }else{//actualizar o cambiar
                
                //NO ES NECESARIA SI SIEMPRE LA VAN A ENVIAR CON ESTADO 30 CONFIRMADA
//                $row = mysql_fetch_assoc($rs);
//                if ($row[""]){
//                    
//                    $query = sprintf("UPDATE sa_cita SET = '' ",
//                            );
//                    $rs = mysql_query($query);
//                    if(!$rs){ return $objResponse->alert(mysql_error()); }
//                    
//                    if(mysql_affected_rows()){
//                        $arrayDivision['estado_carga'] = "CONFIRMADA";
//                    }else{
//                        $arrayDivision['estado_carga'] = "YA CONFIRMADA ANTERIORMENTE";
//                    }
//                }else{
//                    $arrayDivision['estado_carga'] = "YA AGENDADA ANTERIORMENTE";
//                }
                
                $arrayDivision['estado_carga'] = "<span class='azul'>YA AGENDADA ANTERIORMENTE</span>";

            }
        
    
    }elseif($arrayDivision["tipo_registro"] == "2"){//cancelacion de cita
        
        //compruebo los valores:
        $validacion = validarCargaCita($arrayDivision,$idAsesor);       
        if($validacion[0] == false){ $arrayDivision['error'] = $validacion[1]; mysql_query("ROLLBACK"); return $arrayDivision;  }
        
        $query = sprintf("UPDATE sa_cita SET estado_cita = 'CANCELADA' WHERE fecha_cita = '%s' AND hora_inicio_cita = '%s' 
                           AND id_empleado_servicio = %s LIMIT 1",
                        fechaBd($arrayDivision["fecha_cita"]));
        $rs = mysql_query($query);
        if(!$rs){ $arrayDivision['error'] = "Error: ".mysql_error()." Linea: ".__LINE__; mysql_query("ROLLBACK"); return $arrayDivision; }
        
        if(mysql_affected_rows()){
            $arrayDivision['estado_carga'] = "<span class='verde'>CANCELADA</span>";
        }else{
            $arrayDivision['estado_carga'] = "<span class='azul'>CANCELADA ANTERIORMENTE</span>";
        }
    }

    mysql_query("COMMIT");
    
    return $arrayDivision;
    // return $objResponse;
}

/**
 * Verifica que los datos importantes de la cita no esten vacios
 * @param array $arrayDivision Datos de la linea para la cita
 * @param int $idAsesor id del asesor, si existe el codigo mas no esta asociado al id
 * @return array (true,"") o (false, mensaje)
 */
function validarCargaCita($arrayDivision,$idAsesor){
    $mensaje = array();
           
    if($arrayDivision["tipo_registro"] == ""){
        $mensaje[] = "Tipo de Registro";
    } 
    if($arrayDivision["hora_cita"] == ""){
        $mensaje[] = "Hora de cita";
    }
    if($arrayDivision["fecha_cita"] == ""){
        $mensaje[] = "Fecha de cita";
    }
    if($arrayDivision["codigo_asesor"]  == ""){
        $mensaje[] = "Código asesor";
    }
    if($arrayDivision["estatus_agendamiento"] == ""){
        $mensaje[] = "Estatus agendamiento";
    }
    if($arrayDivision["placa"] == ""){
        $mensaje[] = "Placa";
    }
    if($arrayDivision["chasis"] == ""){
        $mensaje[] = "Chasis";
    }
    if($arrayDivision["ci_cliente"] == ""){
        $mensaje[] = "CI cliente";
    }
    if($arrayDivision["nombre_cliente"] == ""){
        $mensaje[] = "Nombre cliente";
    }
//    if($arrayDivision["apellido_cliente"] == ""){
//        $mensaje[] = "Apellido cliente";
//    }
//    if($arrayDivision["correo_cliente"] == ""){
//        $mensaje[] = "Correo cliente";
//    }
//    if($arrayDivision["prefijo_celular_cliente"] == ""){
//        $mensaje[] = "Prefijo celular cliente";
//    }
//    if($arrayDivision["celular_cliente"] == ""){
//        $mensaje[] = "Celular cliente";
//    }
    
    if($idAsesor == "" && $arrayDivision["codigo_asesor"]  != ""){
        $mensaje[] = "No se encuentra asociado el código del asesor";
    }
    
    if(empty($mensaje)){
        return array(true,"");
    }else{
        $mensajeCompleto = "Falta Datos: ".implode(", ",$mensaje);
        return array(false, $mensajeCompleto);
    }
}

/**
 * Se encarga de buscar el id del cliente de la cita,
 * sino existe lo crea y devuelve el nuevo id
 * @param Arrray $arrayDivision Informacion de la linea leida
 * @return Array (false,mensaje error) o (true, id del cliente)
 */
function registroCliente($arrayDivision){
    
    //Variables usadas
//    $arrayDivision["ci_cliente"]
//    $arrayDivision["nombre_cliente"],
//    $arrayDivision["apellido_cliente"],
//    $arrayDivision["ci_cliente"],
//    $arrayDivision["direccion_cliente"],
//    $arrayDivision["ciudad_cliente"],
//    $arrayDivision["prefijo_celular_cliente"]
//    $arrayDivision["celular_cliente"]
//    $arrayDivision["correo_cliente"]
    
    //NUEVO FORMATO CI FORD = "J0001234567" 
    
    $lci = strtoupper(substr($arrayDivision["ci_cliente"], 0, 1));//tomo la letra
    $ci = ltrim(substr($arrayDivision["ci_cliente"], 1, strlen($arrayDivision["ci_cliente"])),"0");//Quito letra y quito ceros solo delante
    
    if(!preg_match('/[A-Za-z]/', $lci)){//verifico si tiene lci sino no enviar
        return array(false, "Falta prefijo cedula Ej: V,J"); 
    }
    
    //test prueba
    //return array(false, $lci." ci: ".$ci); 
    
    $query = sprintf("SELECT id
                      FROM cj_cc_cliente 
                      WHERE
                      lci = '%s'                      
                      AND REPLACE(TRIM(LEADING '0' FROM ci ), '-', '' ) = '%s' LIMIT 1",//quito guion y ceros a la izquierda porque asi lo guardan a veces la gente
                      $lci,
                      $ci);//sin cliente "Activo" sino puede diplicar si esta inactivo
    $rs = mysql_query($query);
    if(!$rs){ return array(false, mysql_error()."\n\nLinea:".__LINE__); }
    $row = mysql_fetch_assoc($rs);
           
    if($row['id'] == ""){//inserto el cliente
        
        if($lci == "J" || $lci == "G"){
            $tipoCliente = "Juridico";
        }else{
            $tipoCliente = "Natural";
        }
        
        $query = sprintf("INSERT INTO cj_cc_cliente(tipo, nombre, apellido, lci, ci, direccion, fcreacion, status, tipocliente, ciudad, telf, correo)
                            VALUES ('%s', '%s', '%s', '%s', '%s', '%s', now(), 'Inactivo', 'Servicios', '%s', '%s', '%s')",
                            $tipoCliente,
                            $arrayDivision["nombre_cliente"],
                            $arrayDivision["apellido_cliente"],
                            $lci,
                            $ci,
                            $arrayDivision["direccion_cliente"],
                            $arrayDivision["ciudad_cliente"],
                            $arrayDivision["prefijo_celular_cliente"]."-".$arrayDivision["celular_cliente"],
                            $arrayDivision["correo_cliente"]);
        $rs = mysql_query($query);
        if(!$rs){ return array(false, mysql_error()."\n\nLinea:".__LINE__); }
 
        $id = mysql_insert_id();
//    }elseif($row['id'] == 1){//ya existe el cliente
//        
//        $id = $row['id'];
    }else{//hay mas de un cliente con el mismo ci, tomar por prioridad
//        $query = sprintf("SELECT COALESCE(
//                                    (SELECT id as cantidad_clientes FROM cj_cc_cliente WHERE ci = '%s' AND lci = 'V' LIMIT 1),
//                                    (SELECT id as cantidad_clientes FROM cj_cc_cliente WHERE ci = '%s' AND tipo = 'Natural' LIMIT 1),
//                                    (SELECT id as cantidad_clientes FROM cj_cc_cliente WHERE ci = '%s' AND lci IS NULL LIMIT 1)
//                                ) as id",
//                                $arrayDivision["ci_cliente"],
//                                $arrayDivision["ci_cliente"],
//                                $arrayDivision["ci_cliente"]);
//        $rs = mysql_query($query);
//        if(!$rs){ return array(false, mysql_error()."\n\nLinea:".__LINE__); }
//        $row = mysql_fetch_assoc($rs);
//        

        $id = $row['id'];
    }
    
    if($id == '' || $id == NULL){
        return array(false, "No se pudo encontrar o crear el cliente");
    }
    
    return array(true, $id);
}

/**
 * Si el cliente es nuevo ingresa la empresa, sino no
 * @param int $idCliente id del cliente
 * @return Array (true, mensaje) o (false, mensaje)
 */
function registroEmpresaCliente($idCliente){
    
    $idEmpresa = valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int");
    
    $query = sprintf("SELECT * FROM cj_cc_cliente_empresa WHERE id_cliente = '%s' AND id_empresa = '%s' LIMIT 1",
                                $idCliente,
                                $idEmpresa);
    $rs = mysql_query($query);
    if(!$rs){ return array(false, mysql_error()."\n\nLinea:".__LINE__); }
    
    if(mysql_num_rows($rs) == 0){
        $query = sprintf("INSERT INTO cj_cc_cliente_empresa (id_cliente, id_empresa) VALUES (%s,%s)",
                                $idCliente,
                                $idEmpresa);
        $rs = mysql_query($query);
        if(!$rs){ return array(false, mysql_error()."\n\nLinea:".__LINE__); }
    }
    
    return array(true,"correcto");
}

/**
 * Si es nuevo se registra, si ya existia devuelve el id,
 * si ya existia pero no era el cliente actualiza el cliente al vehiculo
 * @param Array $arrayDivision Informacion de la linea leida
 * @param int $idCliente id del cliente
 * @return Array (true, id vehiculo registro) o (false, mensaje)
 */
function registroVehiculo($arrayDivision,$idCliente){
    
    //Variables Utilizadas
//    $arrayDivision["chasis"]
//    $arrayDivision["placa"]
    
    $idEmpresa = valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int");
    
    $query = sprintf("SELECT id_registro_placas, id_cliente_registro FROM en_registro_placas WHERE placa = '%s' LIMIT 1",
                                $arrayDivision["placa"]);    
    $rs = mysql_query($query);
    if(!$rs){ return array(false, mysql_error()."\n\nLinea:".__LINE__); }
    
    if(mysql_num_rows($rs) == 0){//guardo nuevo
                 
        $query = sprintf("INSERT INTO en_registro_placas (id_cliente_registro, placa, chasis, parcial, id_empresa) 
                            VALUES (%s, %s, %s, 1, %s)",
                            $idCliente,
                            valTpDato($arrayDivision["placa"],"text"),
                            valTpDato($arrayDivision["chasis"],"text"),
                            $idEmpresa);
        $rs = mysql_query($query);
        if(!$rs){ return array(false, mysql_error()."\n\nLinea:".__LINE__); }
        
        $idRegistroPlacas = mysql_insert_id();
            
    }else{//existe
        
        $row = mysql_fetch_assoc($rs);        
        $idRegistroPlacas = $row["id_registro_placas"];
    }
    
    if($idRegistroPlacas == ""){ return array(false,"No se pudo guardar el vehiculo"); }
    
    return array(true, $idRegistroPlacas);
    
}

/**
 * Se encarga de guardar finalmente la cita segun los datos pasados
 * @param Array $arrayDivision Informacion de la linea leida
 * @param int $idCliente id del cliente
 * @param int $idRegistroPlacas id del vehiculo del cliente
 * @return Array (true, mensaje) o (false, mensaje)
 */
function registroCita($arrayDivision,$idCliente,$idRegistroPlacas){
    
    //variables usadas
//    $arrayDivision["fecha_cita"]
//    $arrayDivision["hora_cita"]
//    $arrayDivision["descripcion_servicio_realizado"]
//    $arrayDivision["codigo_asesor"]
            
    $idEmpresa = valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int");
    
    $resultadoNumeroCita = generarNumeroCita();
    if($resultadoNumeroCita[0] === false){ return $resultadoNumeroCita; }
    
    $numeroCita = $resultadoNumeroCita[1];
    
    
    $resultadoHoraFin = horaFinCita($arrayDivision["hora_cita"]);
    if($resultadoHoraFin[0] === false){ return $resultadoHoraFin; }
    
    $horaFinCita = $resultadoHoraFin[1];
        
    
    $resultadoCodigoAsesor = idCodigoAsesor($arrayDivision["codigo_asesor"]);
    if($resultadoCodigoAsesor[0] === false){ return $resultadoCodigoAsesor; }
    
    $idAsesor = $resultadoCodigoAsesor[1];
    
    $query = sprintf("INSERT INTO sa_cita (numero_cita, fecha_cita, hora_inicio_cita, hora_fin_cita, id_registro_placas, id_empleado_servicio, origen_cita, estado_cita, motivo_detalle, selecciono_fecha, id_cliente_contacto, id_empresa, fecha_solicitud, carga_agenda) 
                            VALUES (%s, %s, %s, %s,  %s, %s, '%s',  '%s',  %s, %s, %s, %s, NOW(), %s)",
                            $numeroCita,
                            valTpDato(fechaBd($arrayDivision["fecha_cita"]),"text"),
                            valTpDato(horaBd($arrayDivision["hora_cita"]),"text"),
                            valTpDato($horaFinCita,"text"),
                            $idRegistroPlacas,
                            $idAsesor,
                            "PROGRAMADA",
                            "PENDIENTE",
                            valTpDato($arrayDivision["descripcion_servicio_realizado"],"text"),
                            1,
                            $idCliente,
                            $idEmpresa,
                            1);
    $rs = mysql_query($query);
    if(!$rs){ return array(false, mysql_error()."\n\nLinea:".__LINE__.$query); }
    
    return array(true,"Guardado Correctamente");
}

/**
 * Genera el numero de cita para la empresa en sesion actual
 * @return Array (true, numero cita) o (false, mensaje)
 */
function generarNumeroCita(){
    
    $idEmpresa = valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int");
    
    $sqlNumeroCita= sprintf("SELECT * FROM pg_empresa_numeracion
                            WHERE id_numeracion = 31
                                    AND (id_empresa = %s OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
                                                                                                        WHERE suc.id_empresa = %s)))
                            ORDER BY aplica_sucursales DESC
                            LIMIT 1",
                            $idEmpresa,
                            $idEmpresa);
    $rsSql = mysql_query($sqlNumeroCita);
    if (!$rsSql) { return array(false,mysql_error()." \n\nLine: ".__LINE__); }
    $dtSql = mysql_fetch_assoc($rsSql);

    $idEmpresaNumeroCita = $dtSql["id_empresa_numeracion"];
    $numeroCita = $dtSql["numero_actual"];		
    if($numeroCita == NULL){ return array(false,"No se pudo crear el numero de cita, compruebe que la empresa tenga numeracion de citas"); }
    
    $updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
			WHERE id_empresa_numeracion = %s;",
				valTpDato($idEmpresaNumeroCita, "int"));
    $Result1 = mysql_query($updateSQL);
    if (!$Result1) { return array(false,mysql_error()."\n\nLine: ".__LINE__); }
    
    return array(true,$numeroCita);    
}

/**
 * Se encarga de calcular la hora fin de la cita segun el intervalo para 
 * ser agregada correctamente desde agenda ford
 * @param string $horaInicioCita La hora fin de la cita
 * @return Array (true, hora fin cita) o (false, mensaje)
 */
function horaFinCita($horaInicioCita){
   
    $idEmpresa = valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int");
    $hora = horaBd($horaInicioCita);
    
    $query = sprintf("SELECT intervalo FROM sa_intervalo WHERE id_empresa = %s AND fecha_fin IS NULL LIMIT 1",
            $idEmpresa);
    $rs = mysql_query($query);
    if(!$rs){ return array(false, mysql_error()."\n\nLinea:".__LINE__); }
    
    $row = mysql_fetch_assoc($rs);
    
    $minutos = $row["intervalo"];
    
    $horaFinCita = date("H:i:s",strtotime($hora."+".$minutos."MINUTES"));
    
    if($horaFinCita == "" || $horaFinCita == "00:00:00"){
        return array(false,"Error al generar hora fin cita".$horaFinCita);
    }
    
    return array(true,$horaFinCita);
}

/**
 * Buscar el id del asesor a asignar la cita, segun el codigo de agenda
 * @param Numerico $codigoAgenda numerico con cero 0000000100076
 */
function idCodigoAsesor($codigoAgenda){
    
    if(strlen($codigoAgenda) != 13){
        $codigoAgenda = rellenarNumero($codigoAgenda,"13");
    }
    
    $query = sprintf("SELECT id_empleado FROM sa_codigo_asesor WHERE codigo_asesor = '%s' LIMIT 1",
                    $codigoAgenda);
    $rs = mysql_query($query);
    if(!$rs){ return array(false, mysql_error()."\n\nLinea:".__LINE__); }
    
    $row = mysql_fetch_assoc($rs);
    
    $idEmpleado = $row["id_empleado"];
    if($idEmpleado == ""){
        return array(false,"no se encontro el codigo del asesor ".$codigoAgenda);
    }
    return array(true,$idEmpleado);
}



/*******************************************************************/
//FUNCIONES AUXILIARES

/**
 * Realiza un trim() a todos los valores contenidos en el array
 * @param array $array El array a ser limpiado
 * @return array El array limpio
 */
function trimArray($array){
    foreach ($array as $indice => $valor){
        $array[$indice] = trim($valor);
    }
    return $array;
}

/**
 * Se encarga de llenar con espacios en blanco los textos necesarios para cumplir
 * con los parametros de cantidad de caracteres para el archivo
 * @param string $texto El texto a ser completado con relleno
 * @param int $cantidad La cantidad a ser rellenada
 * @return string El texto ya rellenado
 */
function rellenar($texto,$cantidad){
    return str_pad($texto,$cantidad, " ",STR_PAD_LEFT);
}

/**
 * Se encarga de llenar los campos con decimales o flotantes, con el respectivo
 * formato para el archivo, siendo 3 decimales, 12 enteros y total 15 la cantidad
 * @param number $decimal Entero o flotante
 * @param int $cantidad Cantidad a rellenar comumente 15
 * @return float El valor en decimal ya con el formato
 */
function rellenarFlotante($decimal,$cantidad){//el punto cuenta como string
    
    $decimal = str_replace(".","",number_format((float)$decimal, 3, '.', ''));//forza a 3 decimales
    return str_pad($decimal,$cantidad, "0",STR_PAD_LEFT);
}

/**
 * Se encarga de llenar los numeros enteros
 * @param int $numero Numeros enteros
 * @param int $cantidad La cantidad a rellenar
 * @return int Entero con ceros a la izquierda
 */
function rellenarNumero($numero,$cantidad){
    return str_pad($numero,$cantidad, "0",STR_PAD_LEFT);
}

/**
 * Envia un alert y regresa a la pagina index
 * @param string $mensaje Mensaje que muestra por alert
 */
function errorJs($mensaje){
    echo "<script>";
    echo "alert('".$mensaje."');";
    echo "window.location.href=window.location.href";
    //echo "window.location.href = 'index.php'";
    echo "</script>";
}




/*******************************************************************/
//FUNCIONES DE FORMATO

/**
 * Transforma la hora de bd a hora de agenda
 * Ejemplo: 09:35:00 a 0935
 * @param string $hora La hora en formato 09:35:00
 * @return string La hora en formato 0935
 */
function horaAgenda($hora){
    if($hora != '' && $hora != NULL){
        $separado = explode(":",$hora);
        $nuevaHora = $separado[0].$separado[1];
                
        return $nuevaHora;
    }
}

/**
 * Transforma la hora de Agenda a hora de bd
 * Ejemplo: 0935 a 09:35:00
 * @param string $hora La hora en formato 0935
 * @return string La hora en formato 09:35:00
 */
function horaBd($hora){
    if($hora != '' && $hora != NULL){
        $parteHora = substr($hora,0,2);
        $parteMinuto = substr($hora,2,2);
        
        return $parteHora.":".$parteMinuto.":00";
    }
}

/**
 * Transforma la fecha de bd a fecha de Agenda
 * Ejemplo: 2014-05-31 a 31052014
 * @param string $fecha La fecha en formato 2014-05-31
 * @return string La fecha en formato 31052014
 */
function fechaAgenda($fecha){
    if($fecha != '' && $fecha != NULL){
        $separado = explode("-",$fecha);
        $nuevaFecha = $separado[2].$separado[1].$separado[0];
        
        return $nuevaFecha;
    }
}

/**
 * Transforma la fecha de Agenda a hora de bd
 * Ejemplo: 31052014 a 2014-05-31
 * @param string $fecha La fecha en formato 31052014
 * @return string La fecha en formato 2014-05-31
 */
function fechaBd($fecha){
    if($fecha != '' && $fecha != NULL){
        $parteDia = substr($fecha,0,2);
        $parteMes = substr($fecha,2,2);
        $parteAno = substr($fecha,4,4);
        
        return $parteAno."-".$parteMes."-".$parteDia;
    }
}

/**
 * Transforma el id filtro de la orden a la letra
 * usada en Agenda W M B A
 * @param Int $idFiltroOrden El id filtro del tipo de orden
 * @return string La letra asociada
 */
function tipoOrdenAgenda($idFiltroOrden){
    $tipoAgenda = array(1 => "M",//contado
                        2 => "M",//credito
                        3 => "W",//garantia
                        4 => "",//activos
                        5 => "",//sin asignar
                        6 => "",//retrabajo
                        7 => "B",//lat pint contado
                        8 => "B",//lat pint credito
                        9 => "",//blindaje y otros
                        10 => "A",//accesorios
                        11 => "",//cav. furg. plat.
                        );
    
    return $tipoAgenda[$idFiltroOrden];
}

/**
 * Se encarga de generar las lineas traducidas para el archivo de agenda
 * recibe los array con la informacion de citas ordenes y repuestos
 * traduce a datos de agenda y devuelve un nuevo array
 * @param array $arrayCitas Citas a traducir
 * @param array $arrayOrdenes Ordenes a traducir, depende de citas
 * @param array $arrayRepuestos Repuestos a traducir, depende de ordenes
 * @return array Lineas ya traducidas para archivo de descarga
 */
function listadoAgenda($arrayCitas, $arrayOrdenes, $arrayRepuestos){
    $arrayLineas = array();
    
    foreach($arrayCitas as $keyIdCita => $arrayCitaTraducir){//recorro las citas
        
        $citasTraducidas = uploadAgendamiento($arrayCitaTraducir);//traduzco
        if(verificarUploadAgendamiento($citasTraducidas) === false){//verifico
            return array(false,"No se pudo convertir los datos de Citas");
        }
        $arrayLineas[] = $citasTraducidas;//agrego
        
        foreach($arrayOrdenes[$keyIdCita] as $arrayOrdenTraducir){//recorro las ordenes (depende de cita)
            $idOrden = $arrayOrdenTraducir["id_orden"];//separo el id de la orden
            
            $ordenesTraducidas = uploadOrdenServicio($arrayOrdenTraducir);//traduzco            
            if(verificarUploadServicio($ordenesTraducidas) === false){//verifico
                return array(false,"No se pudo convertir los datos de las Ordenes");
            }
            $arrayLineas[] = $ordenesTraducidas;//agrego
            
            foreach($arrayRepuestos[$idOrden] as $arrayRepuestoTraducir){//recorro los repuestos (depende de ordenes)
               
                $repuestosTraducidos = uploadRepuestos($arrayRepuestoTraducir);//traduzco
                if(verificarUploadRepuestos($repuestosTraducidos) === false){//verifico
                    return array(false,"No se pudo convertir los datos de los Repuestos");
                }
                $arrayLineas[] = $repuestosTraducidos;//agrego
            }
        }
        
    }
    
    return $arrayLineas;
}


/*******************************************************************/
//FUNCIONES XAJAX

function listadoCargaCita($arrayLineasRespuesta,$sinXajax = false) {

	$objResponse = new xajaxResponse();
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
        
	$htmlTh .= "<tr class=\"tituloCampo\">";
            $htmlTh .= "<td align=\"center\" class=\"textoNegrita_10px\" colspan=\"14\">Listado Archivo Cargado</td>";
	$htmlTh .= "</tr>";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
            $htmlTh .= "<td width='2%'>Nro Linea</td>";
            $htmlTh .= "<td>Fecha Cita</td>";
            $htmlTh .= "<td>Hora Cita</td>";
            $htmlTh .= "<td>Nombre</td>";
            $htmlTh .= "<td>Apellido</td>";
            $htmlTh .= "<td>Telef</td>";
            $htmlTh .= "<td>Correo</td>";
            $htmlTh .= "<td>Placa</td>";
            $htmlTh .= "<td>Chasis</td>";
            $htmlTh .= "<td>Estado Carga</td>"; 
	$htmlTh .= "</tr>";
                
        $totalRows = count($arrayLineasRespuesta);
        $cantidad = 0;
        
	foreach ($arrayLineasRespuesta as $indice => $valor) {
            $clase = (fmod($cantidad, 2) == 0) ? "trResaltar4" : "trResaltar5";
            $cantidad++;

            $htmlTb.= "<tr class=\"".$clase."\" height=\"22\">";
            
            $htmlTb .= "<td align=\"center\">".$cantidad."</td>";
            $htmlTb .= "<td align=\"center\">".implode("-",array_reverse(explode("-",fechaBd($valor["fecha_cita"]))))."</td>";
            $htmlTb .= "<td align=\"center\">".date("h:i a",strtotime(horaBd($valor["hora_cita"])))."</td>";
            $htmlTb .= "<td align=\"center\">".$valor["nombre_cliente"]."</td>";
            $htmlTb .= "<td align=\"center\">".$valor["apellido_cliente"]."</td>";
            $htmlTb .= "<td align=\"center\">".$valor["prefijo_celular_cliente"]."-".$valor["celular_cliente"]."</td>";
            $htmlTb .= "<td align=\"center\">".$valor["correo_cliente"]."</td>";
            $htmlTb .= "<td align=\"center\">".$valor["placa"]."</td>";
            $htmlTb .= "<td align=\"center\">".$valor["chasis"]."</td>";
            $htmlTb .= "<td align=\"center\"><b>".$valor["estado_carga"].$valor['error']."</b></td>";
            
            $htmlTb.= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"18\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$cantidad,
						$totalRows);
				$htmlTf .= "</td>";
				
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"18\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se ha cargado archivo</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListadoCitas","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
        if($sinXajax){            
            return $htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin;
        }
	return $objResponse;
}


function listadoDescargaCita($arrayCitas, $arrayOrdenes, $arrayRepuestos) {
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
        $htmlTh .= "<tr class=\"tituloCampo\">";
            $htmlTh .= "<td align=\"center\" class=\"textoNegrita_10px\" colspan=\"50\">Listado Archivo Descarga</td>";
	$htmlTh .= "</tr>";
	
        $totalRows = 0;
        
        foreach($arrayCitas as $keyIdCita => $arrayCitaTraducir){//recorro las citas
            $totalRows++;                   
            
            $htmlTh .= "<tr align=\"center\" class=\"tituloCampo\">";
                $htmlTh .= "<td class=\"tituloColumna\">CITA</td>";         
                $htmlTh .= "<td>Hora Cita</td>";         
                $htmlTh .= "<td>Fecha Cita</td>";         
                $htmlTh .= "<td>C&oacute;digo Concesionario</td>";         
                $htmlTh .= "<td>C&oacute;digo Asesor</td>";         
                $htmlTh .= "<td>Hora Llegada Cliente</td>";         
                $htmlTh .= "<td>Fecha Salida Efectiva</td>";         
                $htmlTh .= "<td>Hora Salida Efectiva</td>";         
                $htmlTh .= "<td>Kilometraje</td>";         
                $htmlTh .= "<td>Comentarios Asesor</td>";         
                $htmlTh .= "<td>Valor Total Piezas</td>";         
                $htmlTh .= "<td>Valor Total M.O</td>";         
                $htmlTh .= "<td>Valor Total Servicio</td>";         
                $htmlTh .= "<td>Inspeccionado Elevador</td>";         
                $htmlTh .= "<td>Sobre Turno</td>";         
                $htmlTh .= "<td>Cita Retorno</td>";         
                $htmlTh .= "<td>Presupuesto Previo</td>";         
                $htmlTh .= "<td>Veh&iacute;culo Revisado</td>";         
                $htmlTh .= "<td>Explicaci&oacute;n del trabajo</td>";         
                $htmlTh .= "<td>Valor Total Terceros</td>";         
                $htmlTh .= "<td>Valor Total Otros</td>";         
            $htmlTh .= "</tr>";
            
            $htmlTh.= "<tr>";            
            $htmlTh .= "<td></td>";
            $htmlTh .= "<td align=\"center\">".$arrayCitaTraducir["hora_cita"]."</td>";
            $htmlTh .= "<td align=\"center\">".$arrayCitaTraducir["fecha_cita"]."</td>";
            $htmlTh .= "<td align=\"center\">".$arrayCitaTraducir["codigo_concesionario"]."</td>";
            $htmlTh .= "<td align=\"center\">".$arrayCitaTraducir["codigo_asesor"]."</td>";
            $htmlTh .= "<td align=\"center\">".$arrayCitaTraducir["hora_llegada_cliente"]."</td>";
            $htmlTh .= "<td align=\"center\">".$arrayCitaTraducir["fecha_salida_efectiva"]."</td>";
            $htmlTh .= "<td align=\"center\">".$arrayCitaTraducir["hora_salida_efectiva"]."</td>";
            $htmlTh .= "<td align=\"center\">".$arrayCitaTraducir["kilometraje"]."</td>";
            $htmlTh .= "<td align=\"center\">".utf8_encode($arrayCitaTraducir["comentarios_asesor_servicio"])."</td>";
            $htmlTh .= "<td align=\"center\">".$arrayCitaTraducir["valor_total_piezas"]."</td>";
            $htmlTh .= "<td align=\"center\">".$arrayCitaTraducir["valor_total_manodeobra"]."</td>";
            $htmlTh .= "<td align=\"center\">".$arrayCitaTraducir["valor_total_servicio"]."</td>";
            $htmlTh .= "<td align=\"center\">".$arrayCitaTraducir["inspeccionado_elevador"]."</td>";
            $htmlTh .= "<td align=\"center\">".$arrayCitaTraducir["sobre_turno"]."</td>";
            $htmlTh .= "<td align=\"center\">".$arrayCitaTraducir["cita_retorno"]."</td>";
            $htmlTh .= "<td align=\"center\">".$arrayCitaTraducir["presupuesto_previo"]."</td>";
            $htmlTh .= "<td align=\"center\">".$arrayCitaTraducir["vehiculo_listo_revisado"]."</td>";
            $htmlTh .= "<td align=\"center\">".$arrayCitaTraducir["explicacion_trabajo"]."</td>";
            $htmlTh .= "<td align=\"center\">".$arrayCitaTraducir["valor_total_terceros"]."</td>";
            $htmlTh .= "<td align=\"center\">".$arrayCitaTraducir["valor_total_otros"]."</td>";            
            $htmlTh.= "</tr>";
            
            foreach($arrayOrdenes[$keyIdCita] as $arrayOrdenTraducir){//recorro las ordenes (depende de cita)
                $totalRows++;
                
                $htmlTh .= "<tr align=\"center\" class=\"tituloCampo\">";
                    $htmlTh .= "<td style=\"background-color:#ffffff\"></td>";         
                    $htmlTh .= "<td class=\"tituloColumna\">ORDENES</td>";         
                    $htmlTh .= "<td>N&uacute;mero de Orden</td>";         
                    $htmlTh .= "<td>Fecha Apertura</td>";         
                    $htmlTh .= "<td>Hora Apertura</td>";         
                    $htmlTh .= "<td>Fecha Cierre</td>";         
                    $htmlTh .= "<td>Hora Cierre</td>";         
                    $htmlTh .= "<td>C&oacute;digo Asesor DMS</td>";         
                    $htmlTh .= "<td>Nombre Completo T&eacute;cnico</td>";         
                    $htmlTh .= "<td>Tipo Orden</td>";         
                    $htmlTh .= "<td>Minutos Trabajados</td>";         
                    $htmlTh .= "<td>Total Repuestos</td>";         
                    $htmlTh .= "<td>Total M.O</td>";         
                    $htmlTh .= "<td>Total Orden</td>";         
                    $htmlTh .= "<td>C&oacute;digo Grupo Servicio</td>";         
                    $htmlTh .= "<td>Total Terceros</td>";         
                    $htmlTh .= "<td>Total Otros</td>";
                $htmlTh .= "</tr>";
                
                $idOrden = $arrayOrdenTraducir["id_orden"];
                
                $htmlTh.= "<tr>";
                $htmlTh .= "<td style=\"background-color:#ffffff\"></td>";
                $htmlTh .= "<td style=\"background-color:#ffffff\"></td>";
                $htmlTh .= "<td align=\"center\">".$arrayOrdenTraducir["numero_orden"]."</td>";
                $htmlTh .= "<td align=\"center\">".$arrayOrdenTraducir["fecha_apertura"]."</td>";
                $htmlTh .= "<td align=\"center\">".$arrayOrdenTraducir["hora_apertura"]."</td>";
                $htmlTh .= "<td align=\"center\">".$arrayOrdenTraducir["fecha_cierre"]."</td>";
                $htmlTh .= "<td align=\"center\">".$arrayOrdenTraducir["hora_cierre"]."</td>";
                $htmlTh .= "<td align=\"center\">".$arrayOrdenTraducir["codigo_asesor_dms"]."</td>";
                $htmlTh .= "<td align=\"center\">".$arrayOrdenTraducir["nombre_completo_tecnico"]."</td>";
                $htmlTh .= "<td align=\"center\">".tipoOrdenAgenda($arrayOrdenTraducir["tipo_orden"])."</td>";
                $htmlTh .= "<td align=\"center\">".$arrayOrdenTraducir["minutos_trabajados"]."</td>";
                $htmlTh .= "<td align=\"center\">".$arrayOrdenTraducir["total_repuestos"]."</td>";
                $htmlTh .= "<td align=\"center\">".number_format($arrayOrdenTraducir["total_manodeobra"],2,'.','')."</td>";
                $htmlTh .= "<td align=\"center\">".$arrayOrdenTraducir["total_total"]."</td>";
                $htmlTh .= "<td align=\"center\">".$arrayOrdenTraducir["codigo_grupo_servicio"]."</td>";
                $htmlTh .= "<td align=\"center\">".$arrayOrdenTraducir["total_terceros_tot"]."</td>";
                $htmlTh .= "<td align=\"center\">".$arrayOrdenTraducir["total_otros_notas"]."</td>";
                $htmlTh.= "</tr>";
                
                $auxRep = 1;
                foreach($arrayRepuestos[$idOrden] as $arrayRepuestoTraducir){//recorro los repuestos (depende de ordenes)
                    $totalRows++;
                    
                    if($auxRep){
                        $htmlTh .= "<tr align=\"center\" class=\"tituloCampo\">";
                            $htmlTh .= "<td style=\"background-color:#ffffff\"></td>";        
                            $htmlTh .= "<td style=\"background-color:#ffffff\"></td>";        
                            $htmlTh .= "<td class=\"tituloColumna\">REPUESTOS</td>";        
                            $htmlTh .= "<td>C&oacute;digo de Art&iacute;culo</td>";         
                            $htmlTh .= "<td>Cantidad</td>";         
                            $htmlTh .= "<td>Precio Unitario</td>";
                        $htmlTh .= "</tr>";
                    }
                    $auxRep = 0;
                    
                    $htmlTh.= "<tr>";
                    $htmlTh .= "<td></td>";
                    $htmlTh .= "<td></td>";
                    $htmlTh .= "<td></td>";
                    $htmlTh .= "<td align=\"center\">".$arrayRepuestoTraducir["codigo"]."</td>";
                    $htmlTh .= "<td align=\"center\">".$arrayRepuestoTraducir["cantidad"]."</td>";
                    $htmlTh .= "<td align=\"center\">".$arrayRepuestoTraducir["precio_unitario"]."</td>";
                    $htmlTh.= "</tr>";
                }
            }

        }
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"50\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$totalRows,
						$totalRows);
				$htmlTf .= "</td>";
				
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se ha descargado archivo</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	
		
	return $htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin;
}

function descargaFinalizadas($fecha1, $fecha2){
    
    $objResponse = new xajaxResponse();
    $idEmpresa = valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int");
    
    if($fecha1 != "" && $fecha2 != ""){
        $sqlFecha = sprintf(" AND sa_cita.fecha_cita BETWEEN %s AND %s ",
                valTpDato(date("Y-m-d",strtotime($fecha1)),"text"),
                valTpDato(date("Y-m-d",strtotime($fecha2)),"text")
                );
    }
    
    $filtroOrdenes = "1,2,3,7,8";//1 contado, 2 credito, 3 garantia, 7 lat contado, 8 lat credito 
    
    $arrayCitas = array();//contiene array todas las lineas de citas, usado para consultar el resto tambien key id cita
    $arrayOrdenes = array();//contiene array todas las ordenes key id cita
    $arrayRepuestos = array();//contiene array repuestos segun ordenes key id orden
    
    $sqlCita = sprintf("SELECT
					sa_orden.numero_orden,
                    sa_cita.id_cita,
                    sa_cita.hora_inicio_cita,
                    sa_cita.fecha_cita,
                    pg_empresa.codigo_dealer as codigo_concesionario,
                    (SELECT codigo_asesor FROM sa_codigo_asesor WHERE sa_codigo_asesor.id_empleado = sa_cita.id_empleado_servicio) as codigo_asesor,
                    IFNULL(TIME(sa_cita.tiempo_llegada_cliente),TIME(sa_recepcion.hora_entrada)) as hora_llegada_cliente,
                    DATE(sa_orden.tiempo_finalizado) fecha_salida_efectiva,
                    TIME(sa_orden.tiempo_finalizado) hora_salida_efectiva,
                    sa_recepcion.kilometraje,
                    sa_recepcion.observaciones as comentarios_asesor_servicio,
                    0 as valor_total_piezas,
                    0 as valor_total_manodeobra,
                    0 as valor_total_servicio,
                    IF(sa_recepcion.puente = 0, 'Y','N')as inspeccionado_elevador,
                    'N' as sobre_turno,
                    ' ' as cita_retorno,
                    IFNULL((SELECT 'Y' FROM sa_presupuesto WHERE sa_presupuesto.id_orden = sa_orden.id_orden LIMIT 1),'N') as presupuesto_previo,
                    'Y' as vehiculo_listo_revisado,
                    'Y' as explicacion_trabajo,
                    0 as valor_total_terceros,
                    0 as valor_total_otros

                    FROM sa_cita
                        INNER JOIN sa_recepcion ON sa_cita.id_cita = sa_recepcion.id_cita
                        INNER JOIN sa_orden ON sa_recepcion.id_recepcion = sa_orden.id_recepcion
                        INNER JOIN sa_tipo_orden ON sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden
                        INNER JOIN pg_empresa ON sa_orden.id_empresa = pg_empresa.id_empresa
                        LEFT JOIN cj_cc_encabezadofactura ON sa_orden.id_orden = cj_cc_encabezadofactura.numeroPedido AND idDepartamentoOrigenFactura = 1
                        LEFT JOIN sa_vale_salida ON sa_orden.id_orden = sa_vale_salida.id_orden
                    
                    WHERE 
                        sa_cita.id_empresa = %s 
                        AND sa_cita.carga_agenda = 1 
                        AND (cj_cc_encabezadofactura.idFactura IS NOT NULL OR sa_vale_salida.id_vale_salida IS NOT NULL) 
                        AND sa_tipo_orden.id_filtro_orden IN(%s)
                        %s
                    GROUP BY sa_cita.id_cita",
                    $idEmpresa,
                    $filtroOrdenes,
                    $sqlFecha);
    
    $rsCita = mysql_query($sqlCita);
    if(!$rsCita){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__."\n\nQuery: ".$sqlCita); }
    
    if(!mysql_num_rows($rsCita)){
        return $objResponse->alert("No se encontraron citas para descargar");
    }
    
    while($rowCita = mysql_fetch_assoc($rsCita)){
		if($rowCita["fecha_salida_efectiva"] == ""){
			return $objResponse->alert("La orden ".$rowCita["numero_orden"]." no tiene fecha de salida efectiva (tiempo finalizado)");
		}
		if(strlen($rowCita["kilometraje"]) > 9){
			return $objResponse->alert("EL vale de recepcion de la orden ".$rowCita["numero_orden"]." no puede tener un kilometraje mayor a 9 digitos: ".$rowCita["kilometraje"]);
		}
        $arrayCitas[$rowCita["id_cita"]] = array("id_cita" => $rowCita["id_cita"],
                                             "hora_cita" => $rowCita["hora_inicio_cita"],
                                             "fecha_cita" => $rowCita["fecha_cita"],
                                             "codigo_concesionario" => $rowCita["codigo_concesionario"],
                                             "codigo_asesor" => $rowCita["codigo_asesor"],
                                             "hora_llegada_cliente" => $rowCita["hora_llegada_cliente"],
                                             "fecha_salida_efectiva" => $rowCita["fecha_salida_efectiva"],
                                             "hora_salida_efectiva" => $rowCita["hora_salida_efectiva"],
                                             "kilometraje" => $rowCita["kilometraje"],
                                             "comentarios_asesor_servicio" => $rowCita["comentarios_asesor_servicio"],
                                             "valor_total_piezas" => $rowCita["valor_total_piezas"],
                                             "valor_total_manodeobra" => $rowCita["valor_total_manodeobra"],
                                             "valor_total_servicio" => $rowCita["valor_total_servicio"],
                                             "inspeccionado_elevador" => $rowCita["inspeccionado_elevador"],
                                             "sobre_turno" => $rowCita["sobre_turno"],
                                             "cita_retorno" => $rowCita["cita_retorno"],
                                             "presupuesto_previo" => $rowCita["presupuesto_previo"],
                                             "vehiculo_listo_revisado" => $rowCita["vehiculo_listo_revisado"],
                                             "explicacion_trabajo" => $rowCita["explicacion_trabajo"],
                                             "valor_total_terceros" => $rowCita["valor_total_terceros"],
                                             "valor_total_otros" => $rowCita["valor_total_otros"]
                                        );
    }//fin while
    
    foreach($arrayCitas as $keyIdCita => $cita){
        $sqlOrden = sprintf("SELECT 
                    sa_orden.id_orden,
                    IF(cj_cc_encabezadofactura.idFactura IS NULL, 1, 0) as es_vale_salida,
                    IF(cj_cc_encabezadofactura.idFactura IS NULL, sa_vale_salida.id_vale_salida, cj_cc_encabezadofactura.idFactura) as id_vale_factura,
                    sa_orden.numero_orden,
                    DATE(sa_orden.tiempo_orden) as fecha_apertura,
                    TIME(sa_orden.tiempo_orden) as hora_apertura,
                    DATE(sa_orden.tiempo_finalizado) as fecha_cierre,
                    TIME(sa_orden.tiempo_finalizado) as hora_cierre,
                    sa_orden.id_empleado as codigo_asesor_dms,
                        
                        (SELECT CONCAT_WS(' ', pg_empleado.nombre_empleado, pg_empleado.apellido) 
                         FROM sa_det_orden_tempario 
                         INNER JOIN sa_mecanicos ON sa_det_orden_tempario.id_mecanico = sa_mecanicos.id_mecanico
                         INNER JOIN pg_empleado ON sa_mecanicos.id_empleado = pg_empleado.id_empleado
                         WHERE sa_det_orden_tempario.id_orden = sa_orden.id_orden LIMIT 1) as nombre_completo_tecnico,
                    
                    sa_tipo_orden.id_filtro_orden,
                    '' as minutos_trabajados,
                    
                    IF(cj_cc_encabezadofactura.idFactura IS NULL,
                            #VALE
                           (SELECT SUM(sa_det_vale_salida_articulo.cantidad * sa_det_vale_salida_articulo.precio_unitario) 
                            FROM sa_vale_salida 
                            INNER JOIN sa_det_vale_salida_articulo ON sa_vale_salida.id_vale_salida = sa_det_vale_salida_articulo.id_vale_salida 
                            WHERE sa_vale_salida.id_orden = sa_orden.id_orden AND sa_det_vale_salida_articulo.aprobado = 1)
                        ,
                            #FACTURA
                           (SELECT SUM(sa_det_fact_articulo.cantidad * sa_det_fact_articulo.precio_unitario) 
                            FROM sa_det_fact_articulo                                
                            WHERE sa_det_fact_articulo.idFactura = cj_cc_encabezadofactura.idFactura AND sa_det_fact_articulo.aprobado = 1)

                        ) as total_repuestos,
                            
                    IF(cj_cc_encabezadofactura.idFactura IS NULL,
                            #VALE
                           (SELECT SUM(
                                        CASE sa_det_vale_salida_tempario.id_modo
                                                when '1' then sa_det_vale_salida_tempario.ut * sa_det_vale_salida_tempario.precio_tempario_tipo_orden/ sa_det_vale_salida_tempario.base_ut_precio 
                                                when '2' then sa_det_vale_salida_tempario.precio
                                                when '3' then sa_det_vale_salida_tempario.costo 
                                        END
                                    ) 
                            FROM sa_vale_salida 
                            INNER JOIN sa_det_vale_salida_tempario ON sa_vale_salida.id_vale_salida = sa_det_vale_salida_tempario.id_vale_salida 
                            WHERE sa_vale_salida.id_orden = sa_orden.id_orden AND sa_det_vale_salida_tempario.aprobado = 1)
                        ,
                            #FACTURA
                           (SELECT SUM(
                                        CASE sa_det_fact_tempario.id_modo
                                                when '1' then sa_det_fact_tempario.ut * sa_det_fact_tempario.precio_tempario_tipo_orden/ sa_det_fact_tempario.base_ut_precio 
                                                when '2' then sa_det_fact_tempario.precio
                                                when '3' then sa_det_fact_tempario.costo 
                                        END
                                    ) 
                            FROM sa_det_fact_tempario                                
                            WHERE sa_det_fact_tempario.idFactura = cj_cc_encabezadofactura.idFactura AND sa_det_fact_tempario.aprobado = 1)

                        ) as total_manodeobra,
                            
                    0 as total_total,
                    '' as codigo_grupo_servicio,
                    
                    IF(cj_cc_encabezadofactura.idFactura IS NULL,
                            #VALE
                           (SELECT SUM(sa_orden_tot.monto_subtotal * sa_det_vale_salida_tot.porcentaje_tot/100) 
                            FROM sa_vale_salida 
                            INNER JOIN sa_det_vale_salida_tot ON sa_vale_salida.id_vale_salida = sa_det_vale_salida_tot.id_vale_salida 
                            INNER JOIN sa_orden_tot ON sa_det_vale_salida_tot.id_orden_tot = sa_orden_tot.id_orden_tot
                            WHERE sa_vale_salida.id_orden = sa_orden.id_orden AND sa_det_vale_salida_tot.aprobado = 1)
                        ,
                            #FACTURA
                           (SELECT SUM(sa_orden_tot.monto_subtotal * sa_det_fact_tot.porcentaje_tot/100) 
                            FROM sa_det_fact_tot
                            INNER JOIN sa_orden_tot ON sa_det_fact_tot.id_orden_tot = sa_orden_tot.id_orden_tot
                            WHERE sa_det_fact_tot.idFactura = cj_cc_encabezadofactura.idFactura AND sa_det_fact_tot.aprobado = 1)

                        ) as total_terceros_tot,
                    
                    IF(cj_cc_encabezadofactura.idFactura IS NULL,
                            #VALE
                           (SELECT SUM(sa_det_vale_salida_notas.precio) 
                            FROM sa_vale_salida 
                            INNER JOIN sa_det_vale_salida_notas ON sa_vale_salida.id_vale_salida = sa_det_vale_salida_notas.id_vale_salida                             
                            WHERE sa_vale_salida.id_orden = sa_orden.id_orden AND sa_det_vale_salida_notas.aprobado = 1)
                        ,
                            #FACTURA
                           (SELECT SUM(sa_det_fact_notas.precio) 
                            FROM sa_det_fact_notas
                            WHERE sa_det_fact_notas.idFactura = cj_cc_encabezadofactura.idFactura AND sa_det_fact_notas.aprobado = 1)

                        ) as total_otros_notas
                    
                    FROM sa_recepcion                        
                        INNER JOIN sa_orden ON sa_recepcion.id_recepcion = sa_orden.id_recepcion
                        INNER JOIN sa_tipo_orden ON sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden
                        LEFT JOIN cj_cc_encabezadofactura ON sa_orden.id_orden = cj_cc_encabezadofactura.numeroPedido AND idDepartamentoOrigenFactura = 1
                        LEFT JOIN sa_vale_salida ON sa_orden.id_orden = sa_vale_salida.id_orden                    
                    WHERE                         
                        sa_recepcion.id_cita = %s
                        AND (cj_cc_encabezadofactura.idFactura IS NOT NULL OR sa_vale_salida.id_vale_salida IS NOT NULL) 
                        AND sa_tipo_orden.id_filtro_orden IN(%s)",
                    $keyIdCita,
                    $filtroOrdenes);
        $rsOrden = mysql_query($sqlOrden);
        if(!$rsOrden){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__."\n\nQuery: ".$sqlOrden); }
        
        while($rowOrden = mysql_fetch_assoc($rsOrden)){//usar array de arrays porque el id de la cita se repite con cada orden
            $arrayOrdenes[$keyIdCita][] = array("id_orden" => $rowOrden["id_orden"],
                                              "es_vale_salida" => $rowOrden["es_vale_salida"],//1 es vale else o 0 es factura
                
                                              "hora_cita" => $cita["hora_cita"],
                                              "fecha_cita" => $cita["fecha_cita"],
                                              "codigo_concesionario" => $cita["codigo_concesionario"],
                                              "codigo_asesor" => $cita["codigo_asesor"],
                                                
                                              "numero_orden" => $rowOrden["numero_orden"],
                                              "fecha_apertura" => $rowOrden["fecha_apertura"],
                                              "hora_apertura" => $rowOrden["hora_apertura"],                                              
                                              "fecha_cierre" => $rowOrden["fecha_cierre"],
                                              "hora_cierre" => $rowOrden["hora_cierre"],
                                              "codigo_asesor_dms" => $rowOrden["codigo_asesor_dms"],
                                              "nombre_completo_tecnico" => $rowOrden["nombre_completo_tecnico"],
                                              "tipo_orden" => $rowOrden["id_filtro_orden"],
                                              "minutos_trabajados" => $rowOrden["minutos_trabajados"],
                                              "total_repuestos" => $rowOrden["total_repuestos"],
                                              "total_manodeobra" => $rowOrden["total_manodeobra"],
                                              "total_total" => $rowOrden["total_repuestos"] + $rowOrden["total_manodeobra"] + $rowOrden["total_terceros_tot"] + $rowOrden["total_otros_notas"],
                                              "codigo_grupo_servicio" => $rowOrden["codigo_grupo_servicio"],
                                              "total_terceros_tot" => $rowOrden["total_terceros_tot"],
                                              "total_otros_notas" => $rowOrden["total_otros_notas"]
                                        );
            
            if($rowOrden["es_vale_salida"]){//1 vale de salida
                $sqlRepuesto = sprintf("SELECT
                                        sa_det_vale_salida_articulo.cantidad,
                                        sa_det_vale_salida_articulo.precio_unitario,
                                        iv_articulos.codigo_articulo
                                        FROM sa_det_vale_salida_articulo   
                                        INNER JOIN iv_articulos ON sa_det_vale_salida_articulo.id_articulo = iv_articulos.id_articulo
                                        WHERE sa_det_vale_salida_articulo.id_vale_salida = %s AND sa_det_vale_salida_articulo.aprobado = 1",
                               $rowOrden["id_vale_factura"]);
            }else{//else o 0 es factura
                $sqlRepuesto = sprintf("SELECT
                                        sa_det_fact_articulo.cantidad,
                                        sa_det_fact_articulo.precio_unitario,
                                        iv_articulos.codigo_articulo
                                        FROM sa_det_fact_articulo   
                                        INNER JOIN iv_articulos ON sa_det_fact_articulo.id_articulo = iv_articulos.id_articulo
                                        WHERE sa_det_fact_articulo.idFactura = %s AND sa_det_fact_articulo.aprobado = 1",
                               $rowOrden["id_vale_factura"]);
            }
            
            $rsRepuesto = mysql_query($sqlRepuesto);
            if(!$rsRepuesto){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__."\n\nQuery: ".$sqlRepuesto); }
            
            while($rowRepuesto = mysql_fetch_assoc($rsRepuesto)){//usar array de arrays porque el id orden repite con cada repuesto
                $arrayRepuestos[$rowOrden["id_orden"]][] = array("hora_cita" => $cita["hora_cita"],
                                                                "fecha_cita" => $cita["fecha_cita"],
                                                                "codigo_concesionario" => $cita["codigo_concesionario"],
                                                                "codigo_asesor" => $cita["codigo_asesor"],                                                
                                                                "numero_orden" => $rowOrden["numero_orden"],

                                                                "codigo" => $rowRepuesto["codigo_articulo"],
                                                                "cantidad" => $rowRepuesto["cantidad"],
                                                                "precio_unitario" => $rowRepuesto["precio_unitario"]
                                                            );
            }
            
        }
        
    }//fin foreach
    
    $listadoDescargaCita = listadoDescargaCita($arrayCitas, $arrayOrdenes, $arrayRepuestos);
    
    $arrayLineasDescarga = listadoAgenda($arrayCitas, $arrayOrdenes, $arrayRepuestos);
    if($arrayLineasDescarga[0] === false){ return $objResponse->alert($arrayLineasDescarga[1]); }
      
    $_SESSION["listadoAgenda"] = $arrayLineasDescarga; //para no usar GET uri muy largo
    
    $objResponse->assign("divListadoCitas","innerHTML",$listadoDescargaCita);
    $objResponse->script("window.open('reportes/sa_descarga_agenda_txt.php','_self');");
    
    return $objResponse;
    
}

$xajax->register(XAJAX_FUNCTION,"listadoCargaCita");
$xajax->register(XAJAX_FUNCTION,"descargaFinalizadas");