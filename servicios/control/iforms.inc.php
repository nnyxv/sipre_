<?php
//funciones tipo helpers
// (C) 2009, Maycol Alvarez
define("texto","text");
define("oculto","hidden");
define("radio","radio");
define("enviar","submit");
define("reset","reset");
define("check","checkbox");
function getExplodeArray($vstring){
	//excapar las comas y el =
	$vs=str_replace("/,","{1}",$vstring);	
	$vs=str_replace("/=","{2}",$vs);
	$ar=explode(",",$vs);
	foreach($ar as $value){
		$v=explode("=",$value);
		$rv=str_replace("{1}",",",$v[1]);
		$rv=str_replace("{2}","=",$rv);
		$r[$v[0]]=$rv;
	}
	return $r;
}
function setExplodeArray($vstring){
	echo getExplodeArray($vstring);
}

function getAttributes($attr){
	if($attr!=NULL && is_array($attr)){
		$claves=array_keys($attr);
		foreach($claves as $clave){
			if(!is_array($attr[$clave])){
				$Attributes.=sprintf(' %s="%s" ',$clave,$attr[$clave]);
			}else{
				$newa=getAttributes($attr[$clave]);
				$Attributes.=$newa;
			}
		}
		return $Attributes;
	}elseif($attr!=NULL){
		return getAttributes(getExplodeArray($attr));
	}
	return "";
}
function setAttributes($attr){
	echo getAttributes($attr);
}

function getOptionValues($values,$default=""){
	if(is_array($values)){
		$claves=array_keys($values);
		foreach($claves as $clave){
			if ($clave==$default){
				$def=' selected="selected"';
			}else{
				$def="";
			}
			$options.=sprintf('<option value="%s" %s class="%s">%s</option>',$clave,$def,$class,($values[$clave]));
			if($class==''){
				$class='impar';
			}else{
				$class='';
			}
		}
		return $options;
	}else if ($values!=NULL){
		return getOptionValues(getExplodeArray($values),$default);
	}
	return "";
}
function setOptionValues($values,$default=""){
	echo getOptionValues($values,$default);
}

function inputSelect($name,$values,$default="",$attr=NULL,$nullvalue='',$title=' - '){
	//construir los valores
	if($nullvalue!==false)$options='<option value="'.$nullvalue.'">'.$title.'</option>';
	$options.=getOptionValues($values,$default);
	$Attributes=getAttributes($attr);
	return sprintf('<select name="%s" id="%s" %s>%s</select>',$name,$name,$Attributes,$options);
}
function setInputSelect($name,$values,$default="",$attr=NULL,$nullvalue=''){
	echo inputSelect($name,$values,$default,$attr,$nullvalue);
}


function inputTag($type, $name,$value=NULL,$attr=NULL){
	$Attributes=getAttributes($attr);
	return sprintf('<input type="%s" name="%s" id="%s" value="%s" %s />',$type,$name,$name,$value,$Attributes);
}
function setInputTag($type, $name,$value=NULL,$attr=NULL){
	echo inputTag($type, $name,$value,$attr);
}

function getTag($nametag,$attr=NULL){
	$Attributes=getAttributes($attr);
	return sprintf('<%s %s />',$nametag,$Attributes);
}
function setTag($nametag,$attr=NULL){
	echo getTag($type, $name,$value,$attr);
}

//maxlength se aplica desde funciones javascript:
function inputArea($name,$value,$attr=NULL,$maxlength=NULL){
	$Attributes=getAttributes($attr);
	if ($maxlength!=""){
		$smaxlength=sprintf(' onkeypress="if(this.value.length>=%s){if (navigator.appName == \'Netscape\') {tkey = event.which;} else { tkey=event.keyCode;} if(tkey!=8){return false;}else{return true;}}" ',$maxlength);
		$smaxlength.=sprintf(' onchange="if(this.value.length>%s){this.value=this.value.substring(0,%s)}" ',$maxlength,$maxlength);
		if (strlen($value)>$maxlength){
			$value=substr($value,0,$maxlength);
		}
		
	}
	return sprintf('<textarea name="%s" id="%s" %s %s >%s</textarea>',$name,$name,$smaxlength,$Attributes,$value);
}
function setInputArea($name,$value,$attr=NULL,$maxlength=NULL){
	echo inputArea($name,$value,$attr,$maxlength);
}

function startForm($name,$action=NULL,$attr=NULL,$method="POST"){
	$Attributes=getAttributes($attr);
	return sprintf('<form name="%s" id="%s" action="%s" method="%s" %s >',$name,$name,$action,$method,$Attributes);
}
function setStartForm($name,$action=NULL,$attr=NULL,$method="POST"){
	echo startForm($name,$action,$attr,$method);
}

function endForm(){
	return '</form>';
}
function setEndForm(){
	echo endForm();
}

function imageTag($src,$name="",$attr=NULL,$border=0,$alt="image"){
	$Attributes=getAttributes($attr);
	return sprintf('<image id="%s" src="%s" border="%s" alt="%s" %s />',$name,$src,$border,$alt,$Attributes);
}
function setImageTag($src,$name="",$attr=NULL,$border=0,$alt="image"){
	echo imageTag($src,$name,$attr,$border,$alt);
}

function multipleInputTag($name,$value,$title,$attr=NULL,$multiple=true){
	$Attributes=getAttributes($attr);
	if($multiple){
		$type="checkbox";
	}else{
		$type="radio";
	}
	$r=sprintf('<input type="%s" name="%s" id="%s" value="%s" %s />',$type,$name,$name.$value,$value,$Attributes);
	return sprintf('<label for="%s">%s %s</label>',$name.$value,$r,$title);
}
function multipleInputTag2($name,$value,$title,$attr=NULL,$multiple=true){
	$Attributes=getAttributes($attr);
	if($multiple){
		$type="checkbox";
	}else{
		$type="radio";
	}
	$r=sprintf('<input type="%s" name="%s" id="%s" value="%s" %s />',$type,$name,$name,$value,$Attributes);
	return sprintf('<label for="%s">%s %s</label>',$name,$r,$title);
}
function setMultipleInputTag($name,$value,$title,$attr=NULL,$multiple=true){
	echo multipleInputTag($name,$value,$title,$attr,$multiple);
}

function radioInputTag($name,$value,$title,$attr=NULL){
	return multipleInputTag($name,$value,$title,$attr,false);
}
function radioInputTag2($name,$value,$title,$attr=NULL){
	return multipleInputTag2($name,$value,$title,$attr,false);
}
function setRadioInputTag($name,$value,$title,$attr=NULL){
	echo radioInputTag($name,$value,$title,$attr);
}

function getButton($type,$text,$attr=NULL,$name=NULL,$value=NULL){
	$Attributes=getAttributes($attr);
	return sprintf('<button type="%s" name="%s" id="%s" value="%s" %s >%s</button>',$type,$name,$name,$value,$Attributes,$text);
}
function setButton($type,$text,$attr=NULL,$name=NULL,$value=NULL){
	echo getButton($type,$text,$attr,$name,$value);
}

//extras:
//devuelve un array asosiativo de clave=valor (columna1=columna2) dede u origen mysql
function getMysqlAssoc($query,$conect){
	$r = mysql_query($query,$conect);
	if($r){
		while($row = mysql_fetch_array($r)){
			$dev[$row[0]]=$row[1];
		}
		return $dev;
	}else{
		return false;
	}
}

//Pruebas:
/*
echo startForm("form1");
	echo inputArea("loco1","jkhf askdjfh askldjfh aklsdjfhaksjf","style=background:lightblue;width:300px;height:400px;",50);
	echo inputSelect("loco2",array("demente"=>"DEMENTE","loco"=>"Crazy","par"=>"PAR","menos"=>"MINUS"),"par",array("title"=>"ello aqui"));
	echo inputSelect("loco3","demente=DEMENTE,loco=Crazy,par=PAR,menos=MINUS",null,array("title"=>"ello aqui"));
	echo inputTag(texto,"uno1","jdfksj",array("style"=>"background:blue;color:red;","title"=>"ello aqui","class"=>"checo","onclick"=>"alert('ponch , io = nlo');"));
	echo inputTag(texto,"uno2","jdfksj","style=background:blue;color:red;,title=ello aqui,class=checo,onclick=alert('ponch / \\ /, io /= nlo');");

	echo inputTag(enviar,"enviar","enviar");
echo endForm();

if(isset($_POST)){
	echo var_dump($_POST);
}*/

?>