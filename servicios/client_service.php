<?php
//SERVICIOS AL CLIENTE
$cancelXajax=true;
require_once("control/main_control.inc.php");
/*require_once("control/main_lib.inc.php");
require_once("control/iforms.inc.php");
require_once("control/model/main.inc.php");
require_once("control/adodb-time.inc.php");*/
//require_once("control/cmailer.php");
require_once("clases/barcode128.inc.php");
require_once("control/funciones.inc.php");

require("PHPMailer/class.phpmailer.php");
//revisando los GETS

/*if(isset($_GET['try'])){
	echo sendHtmlMail($_GET['try'],'prueba','<html><body>esto es una prueba del correo</body></html>');
}*/

function parseEncode($var){
	return htmlentities(utf8_decode($var));
}

if(isset($_GET['code_cita']) && is_numeric($_GET['code_cita'])){
	$id_cita= intval($_GET['code_cita']);
	$c = new connection();
	$c->open();
	//verificando la existencia de la cita
	$rec=$c->sa_v_datos_cita->doSelect($c,new criteria(sqlEQUAL,'id_cita',$id_cita));
	if($rec->getNumRows()==0){
		echo 'Invalid Number';
	}else{
		getBarcode(strval($rec->ci),'',1,2,30,"c",1);//AddEmbeddedImage
		//exit;
	}
}

function getMailCita(recordset $rec,$print=false,$footer=true){
	//construyendo el email
	if($print){
		$print='<script type="text/javascript">print();</script>';
	}
	if($footer){
		$foot='<div class="noprint">
<a target="_blank" href="http://'.$_SERVER["HTTP_HOST"].dirname($_SERVER["REQUEST_URI"]).'/client_service.php?print_cita='.$rec->id_cita.'">&lt; Imprimir Confirmaci&oacute;n &gt;</a>
<hr />
<p>
<strong>Nota:</strong> Este correo fue enviado por un servicio autom&aacute;tico, no responda a este mensaje<br /><br />

<span style="color:#FF0000;"><strong>ATENCI&Oacute;N:</strong></span> puede que su visor de correo est&eacute; configurado para no descargar imagenes autom&aacute;ticamente, seleccione <strong>"Descargar Imagenes"</strong> en su visor para mostrar el contenido completo del mensaje, o haga clic en "&lt; Imprimir Confirmaci&oacute;n &gt;"
si su visor no dispone de un medio de impresi&oacute;n directo como Outlook.</p></div>';
		$img_source=' src="cid:citacodebar" xsrc="cid:citacodebar" ';
	}else{
		$img_source=' src="http://'.$_SERVER["HTTP_HOST"].dirname($_SERVER["REQUEST_URI"]).'/client_service.php?code_cita='.$rec->id_cita.'" ';
	}
        
        if($rec->id_empresa){
            $query = "SELECT host 
                          FROM crm_correo WHERE id_empresa = ".$rec->id_empresa." LIMIT 1";
            $rs = mysql_query($query);
            $datosSMTP = mysql_fetch_assoc($rs);    
            
            $dominioCorreo = str_replace("mail.","@",$datosSMTP['host']);
            $tieneTwitter = $datosSMTP['twitter'];
        }
        
        if($tieneTwitter){
            //$mensajeTwitter = 'Ahora tambi&eacute;n puede seguirnos en twitter: <a target="_blank" href="https://twitter.com/@distlumosa">@distlumosa.</a>';
            $mensajeTwitter = 'Ahora tambi&eacute;n puede seguirnos en twitter: <a target="_blank" href="https://twitter.com/'.$tieneTwitter.'">'.$tieneTwitter.'.</a>';
        }
        
	$c= new connection();
	$c->open();
	
	//volcando la lista
	$rlista=$c->pg_listados->doQuery($c,new criteria(sqlEQUAL,'n_listado',1));
	$rlista->where(new criteria(sqlEQUAL,'id_empresa',$rec->id_empresa));
	$rlista->where(new criteria(sqlEQUAL,'id_modulo',1));
	$rlista->orderBy('orden');
	$reclista=$rlista->doSelect();
	foreach($reclista as $vl){
		$lista.='<li>'.parseEncode($vl->texto).'</li>';
	}
	
	$rec_empresa=$c->sa_v_empresa_sucursal->doSelect($c, new criteria(sqlEQUAL,'id_empresa',$rec->id_empresa));
	$html='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<style type="text/css" media="print">
			@media print{
				.noprint{
					display:none;
				}
			}
		</style>
		<title>'.parseEncode($rec_empresa->nombre_empresa_sucursal).' Confirmaci&oacute;n Cita</title>
	</head>
	<body>
		<table style="width:100%;">
			<tbody>
				<tr>
					<td>
						<strong>'.parseEncode($rec_empresa->nombre_empresa_sucursal).'</strong><br />
						Rif: '.$rec_empresa->rif.'
					</td>
					<td align="right">
						<img alt="CI Cliente-'.$rec->ci.'"/>
					</td>
				</tr>
				<tr>
					<td colspan="2" align="justify">
<p>
Estimado cliente, '.$rec->cedula_cliente.'-'.parseEncode($rec->apellido).', '.parseEncode($rec->nombre).'.
</p>
<p>&nbsp;</p>
<p>
Le damos una cordial bienvenida a nuestro Concesionario '.parseEncode($rec_empresa->nombre_empresa_sucursal).', gracias por preferirnos, nuestro equipo trabaja d&iacute;a a d&iacute;a para brindarle un mejor servicio bajo los m&aacute;s altos est&aacute;ndares de calidad y tecnolog&iacute;a
</p>
<p>
El d&iacute;a '.$rec->fecha_confirmacion_formato.' hemos recibido Confirmaci&oacute;n para su cita acordada el d&iacute;a '.parseDateToSql($rec->fecha_cita).' le recordamos que la hora pautada para la misma es a las '.parseTimeToSql($rec->hora_inicio_cita).', su asesor de servicios ser&aacute; '.parseEncode($rec->asesor).'; al momento de ingresar su veh&iacute;culo a nuestro Taller debe consignar los siguientes documentos:
</p>
<ul>
'.$lista.'
</ul>
<p>
Adem&aacute;s, recuerde imprimir esta confirmaci&oacute;n que deber&aacute; ser presentada con los documentos mencionados anteriormente.
</p>
<p>
Reiteramos nuestra disposici&oacute;n para atender sus necesidades y ofrecerle una orientaci&oacute;n adecuada, si desea comunicarse con nosotros puede hacerlo a trav&eacute;s de los siguientes n&uacute;meros telef&oacute;nicos: '.parseEncode($rec_empresa->contactos_taller).' o a nuestra direcci&oacute;n de correo electr&oacute;nico: asesorservicio1'.$dominioCorreo.' y asesorservicio2'.$dominioCorreo.' respectivamente.
</p>
<p>
'.$mensajeTwitter.'
</p>
<p>&nbsp;</p>
<p>
	'.parseEncode($rec_empresa->nombre_empresa_sucursal).' - MUY SATISFECHO.
</p>

'.$foot.' 
					</td>
				</tr>
			</tbody>
		</table>
		'.$print.'
	</body>
</html>';
	return $html;
}

if(isset($_GET['print_cita']) && is_numeric($_GET['print_cita'])){
	$id_cita= intval($_GET['print_cita']);
	$c = new connection();
	$c->open();
	//verificando la existencia de la cita
	$rec=$c->sa_v_datos_cita->doSelect($c,new criteria(sqlEQUAL,'id_cita',$id_cita));
	if($rec->getNumRows()==0){
		echo 'Invalid Number';
	}else{
		//construyendo el email
		echo getMailCita($rec,true,false);
	}
}

if(isset($_GET['tomail'])){
	if(!isset($_SESSION['idEmpresaUsuarioSysGts'])){
		exit;
	}
	/*$c = new connection();
	$c->open();
	$rec=$c->sa_v_datos_cita->doSelect($c,new criteria(sqlEQUAL,'id_cita','3'));
	$mail=getMailCita($rec);
	$mail = new cmailer($_GET['tomail'],'prueba',$mail);
	
	$r= $mail->send();
	if(!r){
		echo '<p>NO enviado</p>';
	}else{
		echo "enviado";
	}*/
	$cita=$_GET['id_cita'];
	if($cita==''){
		$cita=3;
	}
	echo sendMailCita($cita,$_GET['tomail']);
}

if(isset($_GET['tohtml'])){
	if(!isset($_SESSION['idEmpresaUsuarioSysGts'])){
		exit;
	}
	
	echo sendHtmlMail($_GET['tohtml'],'prueba','prueba',false);
}


function sendHtmlMail($address,$title,$html,$totry=false){
	$c = new connection();
	$c->open();
	if($totry || $address==''){
		$address='maycolalvarez@gmail.com';
	}
	$rec_empresa=$c->sa_v_empresa_sucursal->doSelect($c, new criteria(sqlEQUAL,'id_empresa',$_SESSION['idEmpresaUsuarioSysGts']));
	//$mail = new cmailer($address,$rec_empresa->nombre_empresa_sucursal.' - '.$title,$html);
	//$mail->AddEmbeddedImage('servicios/clases/temp_codigo/img_cita.png','citacodebar','citacodebar');
	//usando php mailer
	$mail = new PHPMailer();	
	$mail->Host = 'localhost';
	$mail->IsSMTP();
	$mail->Host = 'mail.cantv.net';			
	$mail->SMTPAuth = false;
	$mail->Subject = $rec_empresa->nombre_empresa_sucursal.' - '.$title;	
	$mail->AddAddress($address);	
	$mail->AltBody = "no soporta html";	
	//$mail->AddEmbeddedImage('servicios/clases/temp_codigo/img_cita.png','citacodebar');//,'citacodebar'	
	$mail->Body = $html;	
	
	$gpv=$c->execute("select email from pg_v_empleado where activo=1 and id_empresa=".$rec->id_empresa." and clave_filtro=4;");
	$recmm=$c->pg_parametros_empresas->doSelect($c, new criteria(sqlAND,array(
				new criteria(sqlEQUAL,$c->pg_parametros_empresas->id_empresa,$_SESSION['idEmpresaUsuarioSysGts']),
				new criteria(sqlEQUAL,$c->pg_parametros_empresas->descripcion_parametro,15)
			)));
	$rmail=$recmm->valor_parametro;
	if($rmail==''){
		$rmail=$gpv['email'];
		//echo '<br />rmail:(from): '.$rmail;
	}
	
	//establece origen del correo
	$mail->From=$rmail;;
	$mail->FromName=$rec_empresa->nombre_empresa_sucursal;
		
	$respuesta= $mail->send();
	return $respuesta;
}

function sendMailCita($id_cita,$address=''){

	$c = new connection();
	$c->open();
	$rec=$c->sa_v_datos_cita->doSelect($c,new criteria(sqlEQUAL,'id_cita',$id_cita));
	if($address==''){
		$correo=$c->execute("select correo, nombre, apellido from cj_cc_cliente where id=".$rec->id_cliente_contacto.";");
		if($correo['correo']==''){
			return false;
		}else{
			$address=$correo['correo'];
		}
	}
	//creando la imagen
	/*return "resultado: ".getBarcode(strval($rec->ci),'servicios/clases/temp_codigo/img_cita',1,2,30,"c",1);
	return;*/
	//getBarcode(strval($rec->ci),'servicios/clases/temp_codigo/img_cita',1,2,30,"c",1);//){//AddEmbeddedImage
	
	$tmail=getMailCita($rec);
	$rec_empresa=$c->sa_v_empresa_sucursal->doSelect($c, new criteria(sqlEQUAL,'id_empresa',$rec->id_empresa));	
        
        $gpv=$c->execute("select email from pg_v_empleado where activo=1 and id_empresa=".$rec->id_empresa." and clave_filtro=4;");
	$recmm=$c->pg_parametros_empresas->doSelect($c, new criteria(sqlAND,array(
				new criteria(sqlEQUAL,$c->pg_parametros_empresas->id_empresa,$_SESSION['idEmpresaUsuarioSysGts']),
				new criteria(sqlEQUAL,$c->pg_parametros_empresas->descripcion_parametro,15)
			)));
	$rmail=$recmm->valor_parametro;
	if($rmail==''){
		$rmail=$gpv['email'];
	}
        
        date_default_timezone_set('America/Caracas');
        
        if($rec->id_empresa){
            $query = "SELECT direccion_correo, usuario, password, host, puerto 
                          FROM crm_correo WHERE id_empresa = ".$rec->id_empresa." LIMIT 1";
            $rs = mysql_query($query);
            if(!$rs){
                return false;
            }else{
                $datosSMTP = mysql_fetch_assoc($rs);                
            }
        }else{
            return false;
        }

        $mail = new PHPMailer();
	
		//Deshabilitar mensajes de error que interrumpen con echo
		//$mail->SMTPDebug = false;
		//$mail->do_debug = 0;	
	
		//$mail->MailerDebug = false; //si los de arriba no funciona usar este
	
        $mail->IsSMTP();
	
		//try { 
//        $mail->SMTPAuth= true;
//        $mail->Host= "mail.lumosaford.com.ve";
//        $mail->Port= 25;
//
//        $mail->Username= 'cvillarroell';
//        $mail->Password= "cecilia2008";
//	 	$rmail = "citas@lumosaford.com.ve";
        $mail->Host= $datosSMTP['host'];
        $mail->Port= $datosSMTP['puerto'];

        $mail->Username= $datosSMTP['usuario'];
        $mail->Password= $datosSMTP['password'];
        $rmail = $datosSMTP['direccion_correo'];
		
        $mail->SetFrom($rmail, $rec_empresa->nombre_empresa_sucursal);
        
        $mail->Subject= utf8_decode($rec_empresa->nombre_empresa_sucursal.' - Confirmación Cita');
        
        $mail->AltBody = "no soporta html";
	
	$mail->AddAddress($address, strtoupper($correo['nombre']." ".$correo['apellido']));
	
	//$mail->AddEmbeddedImage('servicios/clases/temp_codigo/img_cita.png','citacodebar');//,'citacodebar'
	
	$mail->MsgHTML($tmail);
	/*
		if($mail->ErrorInfo){
			echo "antes1";
			}else{
			echo "antes2";	
			}
		*/
	$r= $mail->Send(); // devuelve true si lo envio y false sino
	/*
	var_dump($r);
			if($mail->ErrorInfo){
					echo "despues1";
				}else{
					echo "despues2";			
				}*/
		
			//echo "Despues Message Sent OK<p></p>\n";
		//} catch (phpmailerException $e) {
			//echo $e->errorMessage(); //Pretty error messages from PHPMailer
		//} catch (Exception $e) {
			//echo $e->getMessage(); //Boring error messages from anything else!
		//}
		
	if(!$r){
            //return $mail->ErrorInfo; //Este devuelve el error el mas comun = "SMTP Error: Could not authenticate." sin comillas
			$mail->ErrorInfo;//Devuelve un msj de error si lo hubiese, sino devuelve en blanco ""
			//return false;
        }
	
	return $r;
}

//prueba de correo gregor
//sendMailCita(1,'analistaprogramador3@gotosys.com');
?>