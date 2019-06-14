<?php 
/*
error_reporting(E_ALL);
 ini_set("display_errors", 1);
*/
require_once("../connections/conex.php");
include("PHPMailer/class.phpmailer.php");

comprobar();
buscarcumpleano();

/**
* Se encarga de calcular el tiempo entre dos fechas
* @param date $fecha_principal -Cualquier fecha formato Y-m-d H:i:s implementa strtotime
* @param date $fecha_secundaria -Segunda fecha no importa el orden lo reeordena
* @param string $obtener -Lo que deseas obtener, horas, minutos, segundos
* @param bolean $redondear -Si deseas redondear la cifra para obtener un entero
* @return int -varia si redondear es true devulve int sino float
*/

function diferenciaEntreFechas($fecha_principal, $fecha_secundaria, $obtener = 'SEGUNDOS', $redondearAbajo = false, $redondear = false){
	  $f0 = strtotime($fecha_principal);
	  $f1 = strtotime($fecha_secundaria);
	  if ($f0 < $f1) { $tmp = $f1; $f1 = $f0; $f0 = $tmp; }
	  $resultado = ($f0 - $f1);
	  switch ($obtener) {
		  default: break;
		  case "MINUTOS"   :   $resultado = $resultado / 60;   break;
		  case "HORAS"     :   $resultado = $resultado / 60 / 60;   break;
		  case "DIAS"      :   $resultado = $resultado / 60 / 60 / 24;   break;
		  case "SEMANAS"   :   $resultado = $resultado / 60 / 60 / 24 / 7;   break;
		  case "MESES"     :   $resultado = $resultado / 60 / 60 / 24 / 30;   break;
	  }
	  if($redondear) $resultado = round($resultado);
	  if($redondearAbajo) $resultado = floor($resultado);
	  return $resultado;
   }
   
//PARA COMPRAR A QUE USUARIO SE LE VA ENVIAR EL CORREO PARA OFRECERLES UN SERVICIO
function comprobar(){

	$tipo = "ofreserServicio";
	
	//CONSULTA POR MARCA RANGO Y MESES 
	$sqlMeseRango = sprintf("SELECT id_marca, mese, rango_km_i, rango_km_f  FROM crm_revision WHERE activo = 1");
						
	$queryMeseRango = mysql_query($sqlMeseRango);
	if (!$queryMeseRango) return die(mysql_error()."\n\nLine: ".__LINE__);
	$num = mysql_num_rows($queryMeseRango);
	
	if(!$num){
		return NULL;
	}
	
	while($rows2 = mysql_fetch_array($queryMeseRango)){
		
		$aux = $rows2["rango_km_i"];
		while($aux <= $rows2["rango_km_f"]){
			$arrayRangos[$aux] = $aux;
			$aux = $aux + $rows2["rango_km_i"];
		}
		$arrayRevisiones[$rows2['id_marca']] = array("mese" => $rows2["mese"],
											   "rango_km_i" => $rows2["rango_km_i"],
											   "rango_km_f" => $rows2["rango_km_f"],
											   "rangos" => $arrayRangos);
		$arrayRangos = NULL;
	}
	
	//CONSULTA DE TODODS LOS CLIENTE CON CORREO DATOS DEL VEHICO ULTIMO KM DE ENTRADA Y FECHA
	$sqlCliente = sprintf("SELECT id_ofrecer_servicio, MAX(id_recepcion), MAX(crm_ofrecer_servicio.servicio_ofrecido) AS Km_ofrecido, 
		MAX(DATE(fecha_envio)) AS fecha_envio,
		sa_recepcion.id_cita, MAX(sa_recepcion.fecha_entrada) AS fecha_entrada, MAX(sa_recepcion.kilometraje) AS Km,
		sa_cita.id_cita, sa_cita.id_registro_placas, sa_cita.id_cliente_contacto, id_motivo_cita,
		CONCAT_WS(' ',nombre,apellido) AS nombre_apellido_cliente,sexo, correo,
		en_registro_placas.id_registro_placas, id_cliente_registro, id_unidad_basica, en_registro_placas.placa,
		an_uni_bas.id_uni_bas, mar_uni_bas, an_marca.id_marca, nom_marca, mod_uni_bas, nom_modelo,
		sa_recepcion.id_empresa
	FROM sa_recepcion
		LEFT JOIN sa_cita ON sa_cita.id_cita = sa_recepcion.id_cita
		LEFT JOIN en_registro_placas ON en_registro_placas.id_registro_placas = sa_cita.id_registro_placas
		LEFT JOIN cj_cc_cliente ON cj_cc_cliente.id = sa_cita.id_cliente_contacto
		LEFT JOIN an_uni_bas ON an_uni_bas.id_uni_bas = en_registro_placas.id_unidad_basica
		LEFT JOIN an_marca ON id_marca = mar_uni_bas
		LEFT JOIN an_modelo ON id_modelo = mod_uni_bas
		LEFT JOIN crm_no_enviar_correo ON sa_cita.id_cliente_contacto = crm_no_enviar_correo.id_cliente_contacto
		LEFT JOIN crm_ofrecer_servicio ON en_registro_placas.placa = crm_ofrecer_servicio.placa
	WHERE lci != 'j' AND correo !='' AND correo IS NOT NULL AND (crm_no_enviar_correo.enviar_correo = 'si' OR crm_no_enviar_correo.enviar_correo IS NULL)
	# AND sa_cita.id_cliente_contacto = 4539
	GROUP BY en_registro_placas.placa");
	
	$queryCliente = mysql_query($sqlCliente);
	if (!$queryCliente) return die(mysql_error()."\n\nLine: ".__LINE__);
		
	while($rows = mysql_fetch_array($queryCliente)){
		$idClienteContacto = $rows['id_cliente_contacto'];
		$nombreApellidoCliente = utf8_encode($rows['nombre_apellido_cliente']);
		$sexo = $rows['sexo'];
		$correo = $rows['correo'];
		$fechaEntrda = $rows['fecha_entrada'];
		$placa = $rows['placa'];
		$idMarca = $rows['id_marca'];
		$nombreMarca = $rows['nom_marca'];
		$nomrbeModelo = $rows['nom_modelo'];
		$idRegistroPlaca = $rows['id_registro_placas'];
		$idEmpresa = $rows['id_empresa'];
			
		if($rows['id_ofrecer_servicio']){ //tiene servicio ofrecido
			$fechaEvaluar = $rows['fecha_envio']; //envio del ultimo servicio ofrecido
			$kmIngreso = $rows['Km_ofrecido'];//ultimo km ofrecido
		}else{ //se le ofrese por 1er ves
			$fechaEvaluar = $rows['fecha_entrada'];//fecha de entrada a servicio
			$kmIngreso = $rows['Km'];//km entrada por servicio
		}
		//$fechaIni = new DateTime($fechaEvaluar);
		//$fechaFin = new date();
		//$diferencia = $fechaIni->diff($fechaFin);
		//$meses = ($diferencia->y * 12) + $diferencia->m;
	
		$meses = diferenciaEntreFechas($fechaEvaluar,date("Y-m-d"),"MESES", true);
		//var_dump($fechaEvaluar." meses: ".$meses);
		
		if(!array_key_exists($idMarca,$arrayRevisiones)){
			return NULL;	
		}
	
		if($meses >= $arrayRevisiones[$idMarca]["mese"]){//SOLO SI CUMPLE EL TIEMPO, envio correo, insercion			
			foreach ($arrayRevisiones[$idMarca]["rangos"] as $valor){
				if($valor > $kmIngreso){
					$siguienteKmOfrecer = $valor;
					break;
				}
			}
	//PARA SABER CUAL ES LA EMPRESA	
			$sqlEmpresa = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;",$idEmpresa);
			$queryEmpresa = mysql_query($sqlEmpresa);
			
			if (!$queryEmpresa) return die(mysql_error()."\n\nLine: ".__LINE__);
			
			$rows3 = mysql_fetch_array($queryEmpresa);
			$nombreEmpresa = utf8_encode($rows3['nombre_taller']); 
			$logoEmpresa = $rows3['logo_empresa'];
			$rif = $rows3['rif'];
			$direcion = utf8_encode($rows3['direccion']);
			$correoEmpresa = $rows3['correo'];
			$web = $rows3['web'];
			$telefonoServicio = $rows3['telefono_servicio'];
			$nombreTaller =  utf8_encode($rows3['nombre_taller']);
			$direccionTaller = $rows3['direccion_taller'];
			$telefonoTaller1 = $rows3['telefono_taller1'];
			$telefonoTaller2 = $rows3['telefono_taller2'];
			$telefonoTaller3 = $rows3['telefono_taller3'];
			$telefonoTaller4 = $rows3['telefono_taller4'];
			$faxTaller = $rows3['fax_taller'];
					
			//PARA MOSRTRA LAS MARCA QUE SE MANEJAN	EN LA EMPRESA
			$sqlMarca = "SELECT * FROM an_marca WHERE nom_marca NOT LIKE '%OTRA%' AND nom_marca NOT LIKE '%MARCA%' ORDER BY nom_marca ASC";
			$queryMarca = mysql_query($sqlMarca);
			$num = mysql_num_rows($queryMarca);
			
			if(!$queryMarca) return die(mysql_error()."\n\nLine: ".__LINE__);
			$nomMarca = "";
			
			while ($rows4 = mysql_fetch_array($queryMarca)){
				$nomMarca .=  utf8_encode($rows4['nom_marca']).", ";
			}
			
			$datosCliente = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
			$nombreApellidoCliente,
			$sexo,
			$correo,
			$placa,
			$nombreMarca,
			$nomrbeModelo,
			$siguienteKmOfrecer,	
			$arrayRevisiones[$idMarca]["mese"]);
			
			$datosEmpresa = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
			$nombreEmpresa,
			$nombreTaller,
			$direcion,
			$direccionTaller,
			$rif,
			$web,
			$telefonoTaller1,
			$telefonoTaller2,
			$telefonoTaller3,
			$telefonoTaller4,
			$logoEmpresa,
			
			$correoEmpresa,
			$telefonoServicio,
			$faxTaller);
			
			$nomMarca = sprintf("%s|%s", $nomMarca, $num);

			//SE OFRESE POR 1ER VES UN SERVICIO
			//SI SE EJECUTA LA FUNCION
			if(@enviarCorreoOfrecimiento($datosCliente,$datosEmpresa,$nomMarca,$tipo)){
				//2.2 INSERTA EN TABLA EN SERVICIO OFRECIDO Y SI SE ENVIO EL CORREO
				$sqlOfreServicio = sprintf("INSERT INTO  crm_ofrecer_servicio (id_registro_placas, placa, id_cliente_contacto, servicio_ofrecido, enviar_email,
											fecha_envio) 
											VALUE(%s,%s,%s,%s,%s,%s)",
											valTpDato($idRegistroPlaca, "int"), 
											valTpDato($placa, "text"),
											valTpDato($idClienteContacto, "int"),
											valTpDato($siguienteKmOfrecer, "int"),
											valTpDato($siguienteKmOfrecer, "int"),
											'NOW()');
				$queryOfreServicio = mysql_query($sqlOfreServicio);
				if (!$queryOfreServicio) return die(mysql_error()."\n\nLine: ".__LINE__);
	
			}else{
				//var_dump("no se pudo enviar Meses = ".$meses);	
			}
		}
	}
}

//FUNCION PARA BUSCAR LOS CLIENTE QUE CUMPLE AÃƒ'O EN EL MES
function buscarcumpleano(){
	$tipo = "correoCumpleano";
	
	//BUSCA LOS CLIENTE QUE CUMPLE AÃƒ'O EN EL MES ACTUAL Y CACULA LA EDAD ACTUAL
	$sqlCliente = sprintf("SELECT id, CONCAT_WS(' ',nombre,apellido) AS nombre_apellido_cliente,
								(YEAR(CURDATE())-YEAR(fecha_nacimiento)) - (RIGHT(CURDATE(),5)<RIGHT(fecha_nacimiento,5)) AS edad, fecha_nacimiento, telf, correo, sexo
						   FROM cj_cc_cliente
							  WHERE MONTH(fecha_nacimiento) = MONTH(CURDATE()) AND DAY(fecha_nacimiento) = DAY(CURDATE()) AND tipo = 'Natural' 
							   #AND id = 4539
							   ");
	
	$queryCliente = mysql_query($sqlCliente);
	if (!$queryCliente) return die (mysql_error()."\n\nLine: ".__LINE__);
	$num = mysql_num_rows($queryCliente);
	
	while($rows = mysql_fetch_array($queryCliente)){
		$idCliente = $rows['id'];
		$nombreApellidoCliente = $rows['nombre_apellido_cliente'];
		$edad = $rows['edad'];
		$correo = $rows['correo'];
		$sexo = $rows['sexo'];
	}
		
	$datosCliente = sprintf("%s|%s|%s|%s",
	$nombreApellidoCliente,
	$sexo,
	$correo,
	$edad);
	
	if ($num){// SI EXISTE
	//PARA SABER A QUE EMPRESA PERTENECE ESTE CLIENTE
		$sqlEmpresa = sprintf("SELECT id_cliente_empresa, id_cliente, cj_cc_cliente_empresa.id_empresa,
								  nombre_empresa, nombre_taller, direccion, direccion_taller, rif, web, telefono_taller1,telefono_taller2,telefono_taller3,telefono_taller4,
								  logo_empresa
							   FROM cj_cc_cliente_empresa
								  LEFT JOIN pg_empresa ON cj_cc_cliente_empresa.id_empresa = pg_empresa.id_empresa
							   WHERE id_cliente = %s",$idCliente);
		$queryEmpresa = mysql_query($sqlEmpresa);
		if(!$queryEmpresa) return die(mysql_error()."\n\nLine: ".__LINE__);
	
		while ($rows2 = mysql_fetch_array($queryEmpresa)){
			$nombreEmpresa = $rows2['nombre_empresa'];
			$nombreTaller = $rows2['nombre_taller'];
			$direccion = $rows2['direccion'];
			$direccionTaller = $rows2['direccion_taller'];
			$rif = $rows2['rif'];
			$web = $rows2['web'];
			$telfTaller1 = $rows2['telefono_taller1'];
			$telfTaller2 = $rows2['telefono_taller2'];
			$telfTaller3 = $rows2['telefono_taller3'];
			$telfTaller4 = $rows2['telefono_taller4'];
			$logoEmpresa = $rows2['logo_empresa'];
		}
		
		$datosEmpresa = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$nombreEmpresa,
		$nombreTaller,
		$direccion,
		$direccionTaller,
		$rif,
		$web,
		$telfTaller1,
		$telfTaller2,
		$telfTaller3,
		$telfTaller4,
		$logoEmpresa);
			
		@enviarCorreoOfrecimiento($datosCliente,$datosEmpresa,"",$tipo); //envio correo
		
		//echo "envia correo</br>";
	}else{
			//echo "no envia";
	} 
}

//PARA ENVIAR EL CORREO DE SERVICIO (OFRECER UN SERVICIO NUEVO)			   
function enviarCorreoOfrecimiento($datosCliente ="", $datosEmpresa= "",$nomMarca = "", $tipo){

	//DATOS DEL CLIENTE
	$Datos = explode("|", $datosCliente);
	$nombreApellidoCliente = $Datos[0];
	$sexo = $Datos[1];
	$correoCliente = $Datos[2];
	
	//DATOS DE LA EMPRESA
	$datosEmpresa = explode("|", $datosEmpresa);
	$nombreEmpresa = $datosEmpresa[0];
	$nombreTaller = $datosEmpresa[1];
	$direcion = $datosEmpresa[2];
	$direccionTaller = $datosEmpresa[3];
	$rif = $datosEmpresa[4];
	$web = $datosEmpresa[5];
	$telefonoTaller1 = $datosEmpresa[6];
	$telefonoTaller2 = $datosEmpresa[7];
	$telefonoTaller3 = $datosEmpresa[8];
	$telefonoTaller4 = $datosEmpresa[9];
	$logoEmpresa = $datosEmpresa[10];
	
	//DATOS DE LA MARCA
	$datosMarca = explode("|", $nomMarca);
	$nomMarca = $datosMarca[0];	
	$cantMarca = $datosMarca[1];

	//PARA MOSTAR LAS FECHA COMPLETA EN ESPAÑOL
	$dias = array("Domingo","Lunes","Martes","Miercoles","Jueves","Viernes","SÃƒÂ¡bado");
	$meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
	$fechaActual = $dias[date('w')]." ".date('d')." de ".$meses[date('n')-1]. " del ".date('Y') ;
	
	//PARA SABER SI ES AM Y PM
	$hora = date("a");	
	switch($hora){
		case "am":
			$diaTarde = "Buen Dia";
			break;
		case "pm";
			$diaTarde = "Buenas Tardes";
			break;
	}
	//PARA SABER SI ES SR SRA datosCliente[]
	switch($sexo){
		case "F":
			/*echo*/ $srSra = "Estimada Sra.:";
			break;
		case "M":
			/*echo*/ $srSra = "Estimado Sr.:";
			break; 
	}
	
	if($cantMarca == 1){
		$textCatnMarca = "la marca ";
	}else{
		$textCatnMarca = "las marcas ";
	}

	//PARA TOMAR LOS DATOS EL CORREO
	$sql = "SELECT * FROM crm_correo";
	$query = mysql_query($sql);
	$rows = mysql_fetch_array($query);
	$direcioEmail = $rows['direccion_correo'];
	$usuario = $rows['usuario'];
	$password = $rows['password'];
	$host = $rows['host'];
	$puerto = $rows['puerto'];
		
	//PARA ENVIAR EL CORREO
	switch($tipo){
		case "ofreserServicio":
			$Subject = "Tenemos la amabilidad de recordarle el servicio correpondiente a su vehiculo";
			$placa = $Datos[3];
			$nombreMarca = $Datos[4];
			$nomrbeModelo = $Datos[5];
			$siguienteKmOfrecer = $Datos[6];
		
			//SABER SI ES UN MES O VARIOS
			if($Datos[7] > 1){
		 		$mese = $mese." meses";
			}else{
				$mese = $mese." mes";
			}
			$textMail = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
				<html xmlns=\"http://www.w3.org/1999/xhtml\">
				<head>
				<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
				<title>.: Sistema ERP :. .: M&oacute;dulo de CRM :. - Ofrecer Nuevo Servicio</title>
				</head>
				
				<body>
				 <table width='40%' border='0' rules='none' align='right' bordercolor='#D0B562' bgcolor='#D0B562'>
					<tr>
						<th align='right'>Empresa:</th>
						<td ><strong>".ucwords(strtolower(utf8_encode($nombreEmpresa)))."</strong></td>
						<td rowspan='3'><a href='".$web."'><img width='190' height='114' src='cid:logoempresa' xsrc='cid:logoempresa' /></a></td>
					</tr>
					<tr>
						<th align='right'>Rif:</th>
						<td >".$rif."</td>
					<tr>
						<th align='right'>Direccion:</th>
						<td >".ucwords(strtolower(utf8_encode($direccionTaller)))."</td>
					</tr>
				</table>

				<br/><br/><br/><br/><br/><br/><br/>
				<h3 align='right'>Caracas. " .$fechaActual."</h3>
				<p> ".$diaTarde. " ".$srSra." <b>".$nombreApellidoCliente.".</b> </p>
				
				<p>Por medio de la presente tenemos el agrado de comunicarnos con usted.<strong> " .ucwords(strtolower($nombreEmpresa)). "</strong> lo saluda amablemente 
				y le informamos que:
				</p>
				
				<p><strong>" . ucwords(strtolower($nombreEmpresa)). "</strong> Representante exclusivo de " .$textCatnMarca. " <b>".ucwords(strtolower($nomMarca)). "</b>
				contamos con un personal altamente calificado en los Departamentos de Venta, Post-Venta y Repuestos, para ofrecerle el Servicio y la Calidad Superior que 
				solo la Gente Premier como usted merece.
				Desde nuestra apertura, hemos tenido una gran receptividad, contando con una cartera de clientes excepcionales, los cuales confian en la Seriedad y 
				Responsabilidad que nos caracteriza.</p>
				
				<p>De igual manera contamos con el agrado de informarle que su veh&iacute;culo <b>". $Datos[4]."</b> modelo <b>".$Datos[5]."</b> de la placa <b>".$Datos[3]."</b>  ya ha cumplido ".$Datos[7]." ".$mese. " desde el  &uacute;ltimo servicio que realiz&oacute; en nuestro taller. Queremos 
				ofrecerle una revisi&oacute;n de <strong>" .$siguienteKmOfrecer. " Km</strong>; si est&aacute; interesado en recibir mayor informaci&oacute;n sobre los servicios que podamos 
				ofrecerle, no dude en comunicarse o vis&iacute;tenos personalmente que lo atenderemos de manera gustosa y muy cordialmente.</p>
				
				<p>Sin m&aacute;s, y esperando que haya sido de su inter&eacutes esta informaci&oacuten, le deseamos un feliz d&iacute;a.</p> <br/>
				
				<p align='center'>
					<b>".ucwords(strtolower($direccionTaller))." <br/>
					Tel&eacute;fono:" .$telefonoTaller1."/ ".$telefonoTaller2."/ ".$telefonoTaller3."/ " .$telefonoTaller3." <br/>
					Web: ".$web."</b>
				</p>
				</body>
				</html>";
			break;
						
		case "correoCumpleano":
			$Subject = "Felicidades en su Cumplea&ntildeo";
			
			$textMail = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
				<html xmlns=\"http://www.w3.org/1999/xhtml\">
				<head>
				<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
				<title>.: Sistema ERP :. .: M&oacute;dulo de CRM :. -Ofrecer Nuevo Servicio</title>
				</head>
				
				<body>
				 <table width='40%' border='0' rules='none' align='right' bordercolor='#D0B562' bgcolor='#D0B562'>
					<tr>
						<th align='right'>Emopresa:</th>
						<td ><strong>".ucwords(strtolower(utf8_encode($nombreEmpresa)))."</strong></td>
						<td rowspan='3'><a href='".$web."'><img width='190' height='114' src='cid:logoempresa' xsrc='cid:logoempresa' /></a></td>
					</tr>
					<tr>
						<th align='right'>Rif:</th>
						<td >".$rif."</td>
					<tr>
						<th align='right'>Direccion:</th>
						<td >".ucwords(strtolower(utf8_encode($direccionTaller)))."</td>
					</tr>
				</table>
				</br></br></br></br></br></br></br>
<h3 align='right'>Caracas. " .$fechaActual."</h3>
				<p> ".$diaTarde. " ".$srSra." <b>".$nombreApellidoCliente.".</b> </p>
				
				<p> De parte de <b>" .ucwords(strtolower($nombreEmpresa)). "</b>; es muy grato para nosotros felicitarlo por su cuimple&ntilde;o n&uacute;mero <b>".$Datos[3]."</b>, y 
				desearle que en este d&iacute;a tan especial, este lleno de muchas felicidades, &eacute;xito, oportunidades y logros en su vida personal y en todo aquello que se proponga,
				ahnele y desee. Son nuestros m&aacute;s sinceros deseos.
					<p align='center'><img src='cid:imgTorota' xsrc='cid:imgTorota' /> <h3>&iexcl;FELIZ CUMPLEA&Ntilde;OS! le desea " .ucwords(strtolower($nombreEmpresa)). "</h3> </p>
				
				</p>				</body>
				</html>";			
		break; 
	}

//echo $textMail;
		
	$mail = new PHPMailer();
	$mail->IsSMTP();
	$mail->SMTPAuth= true;
	$mail->Host= $host;
	$mail->Port= $puerto;
	$mail->Username= $usuario;
	$mail->Password= $password;
	$mail->setFrom($direcioEmail,$nombreEmpresa);
	//$mail->IsHTML(1);
	$mail->Subject= utf8_decode($Subject);
	$mail->AltBody = "no soporta html";
	$mail->AddAddress($correoCliente, $nombreApellidoCliente);
	
	//PARA CARGAS LA IMG EN EL CORREO SEGUN EL TIPO DE CORREO
	if($tipo == "correoCumpleano"){
		$mail->AddEmbeddedImage("../img/crm/imgTorta.jpg", "imgTorota");
	} 

	$mail->AddEmbeddedImage("../".$logoEmpresa, "logoempresa");

	$mail->MsgHTML(utf8_decode($textMail));

	$r= $mail->Send();
	
	if(!$r){
    	echo $mail->ErrorInfo;
	}else{
		echo "ENVIADO </br>";
	}

	return $r;
}


?>