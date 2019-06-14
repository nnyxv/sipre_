<?php

require_once("phpmailer/class.phpmailer.php");
class cmailer{
	
	public $body;
	public $headers=array();
	public $address=array();
	public $images=array();
	public $title;
	public $from='';
	public $fromname='';
	public $host='localhost';
	public $phpmailer;
	
	public $joinAddress=', ';
	public $joinOS="\r\n";
	
	public function __construct($to,$title,$message,$html=true,$usephpmailer=true){
		//procesando mensaje
		if(!$usephpmailer){
			$this->body = wordwrap($message,70);
		}else{
			$this->body =$message;		
		}
		$this->body='<html><body>prueba</body></html>';
		//añadiendo destinatario:
		echo '<br />TO:'.$to;
		$this->address[]= $to;
		//procesando titulo
		$this->title=str_replace("\n",'',$title);
		echo '<br />title:'.$this->title;
		if($html){
			//imprime las cabeceras HTML básicas
			//$this->headers[]='MIME-Version: 1.0';
			$this->headers[]='Content-type: text/html';
		}
		$this->phpmailer=$usephpmailer;
	}
	
	public function getHeaders(){
		//devolviendo los headers:
		return implode($this->joinOS,$this->headers);
	}
	public function getAddress(){
		//devolviendo los headers:
		return implode($this->joinAddress,$this->address);
	}
	
	public function send(){
		if(!$this->phpmailer){
			$res=mail($this->getAddress(),$this->title,$this->body,$this->getHeaders());
		}else{
			//usando php mailer
			$pmail = new PHPMailer();			   
			$pmail->Host = $this->host;
			$pmail->From = $this->from;
			$pmail->FromName = $this->fromname;
			if(count($this->images)!=0){
				foreach($this->images as $im){
					$pmail->AddEmbeddedImage($im['path'],$im['cid']);
				}
			}
			//$pmail->SetFrom($this->$from, $this->$from_name);

			$pmail->IsSMTP();
			$pmail->Host = 'mail.cantv.net';			
			$pmail->SMTPAuth = false;
			$pmail->Subject = $this->title;
			foreach ($this->address as $addr){
				$pmail->AddAddress($addr);
			}
			//$mail->AddCC("usuariocopia@correo.com");
			//$mail->AddBCC("usuariocopiaoculta@correo.com");
			
			$pmail->Body = $this->body;
			$pmail->AltBody = "no soporta html";
			
		//	echo 'phpmailer:<br />';
			//var_dump($mail);
			
			$res=$pmail->send();
			
			//echo 'VALOR: ['.$res.']';

		}
		return $res;
	}
	
	public function AddEmbeddedImage($path,$cid,$name){
		$this->images[]=array(
			'path'=>$path,
			'cid'=>$cid,
			'name'=>$name
		);
	}	
}