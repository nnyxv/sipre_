<?php
//Archivo de base de datos
//funciones para la conexion
/*$basename="sysgts";
$host="localhost";
$user="root";
$password="";*/
$con;

define("sqlAS",' AS ');
define("sqlSELECT",'SELECT ');
define("sqlINSERTINTO",'INSERT INTO ');
define("sqlVALUES",' VALUES ');
define("sqlUPDATE",'UPDATE ');
define("sqlSET",' SET ');
define("sqlWHERE",' WHERE ');
define("sqlHAVING",' HAVING ');
define("sqlEQUAL",' = ');
define("sqlPS",'(');
define("sqlPE",')');
define("sqlQUOTE","'");
define("sqlREF",".");
define("sqlCOMA",",");
define("sqlFROM"," FROM ");
define("sqlINNERJOIN"," INNER JOIN ");
define("sqlON"," ON ");
define("sqlSPACE"," ");
define("sqlAND"," AND ");
define("sqlLIMIT"," LIMIT ");
define("sqlASC"," ASC");
define("sqlDESC"," DESC");
define("sqlORDERBY"," ORDER BY ");
define("sqlGROUPBY"," GROUP BY ");


class connection{
	
	public $con;
	public $basename="sysgts";
	public $host="localhost";
	public $user="root";
	public $password="";
	
	public function connect(){
		$this->con= mysql_connect($this->host,$this->user,$this->password);
		mysql_select_db($basename,$this->con);
		@mysql_query("SET NAMES 'utf8'",$this->con);
	}
	
	public function close(){
		mysql_close($this->con);
	}
	
	public function begin(){
		return mysql_query("START TRANSACTION;",$this->con);
	}
	
	public function commit(){
		return mysql_query("COMMIT;",$this->con);
	}
	
	public function rollback(){
		return mysql_query("ROLLBACK;",$this->con);
	}
	
	public function getQuery($table){
		return new query($this,$table);
	}
}

class errorMessage{
	public $obj;
	public $errorMsq;
	
	public function __construct($obj,$msg){
		$this->obj=$obj;
		$this->errorMsq=$msg;
	}
	
	public function getObject(){
		return $this->obj;
	}
	
	public function getMessage(){
		$m=$this->errorMsg;
		if($m==""){
			$m=" no v&aacute;lido ";
		}
		return utf8_decode($m);
	}
}

interface iAttribute{
	public function getName();
	public function getAlias();
	public function getValue();
	public function getEntityName(); //nombre+alias
	public function getRefName(); //referencia+nombre o alias
	public function getParent();
	static function setRefNameMode($mode);
	public function applyFunction();
	public function setFunction(_function $func);
	public function getFunction($sfield);
	public function validate();
}

interface iEntity{
	public function getName();
	public function getAlias();
	public function getEntityName(); //nombre+alias
	public function getRefName(); //nombre o alias
	public function getAttributes();
}

class _function {
	
	public $func;
	public $args=array();
	
	public function __construct ($namefunc,$args="%s"){
		$this->func=$namefunc;
		if(is_array($args)){
			$this->args=$args;
		}else{
			$this->args[]=$args;
		}
	}
	
	public function __toString(){
		$args=implode(sqlCOMA,$this->args);
		return $this->func.sqlPS.$args.sqlPE;
	}
}

class field implements iAttribute{
	public $name;
	public $alias;
	public $objparent;
	public $type;
	public $value;
	static $nameMode=false;
	public $func;
	public $req=true;
	public $errorMsg="";
	
	const tInt="int";
	const tFloat="float";
	
	static $patrones=array(
	"e" => "^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$",
	"phone" => "",
	"" => ""
	);
	
	public function __construct($fieldName,$alias="",$type="text",$value="",$req=true){
		$this->name=$fieldName;
		$this->alias=$alias;
		$this->type=$type;
		$this->value=$value;
		$this->req=$req;
	}
	
	
	public function getName(){
		return $this->name;
	}
	public function getAlias(){
		return $this->alias;
	}
	public function getValue(){
		$value=$this->value;
		if($type==self::tInt || $type==self::tFloat){
			$value=parseOnlyNumber($value);
		}
		if (is_object($value)){
			return $value;
		}
		return sqlQUOTE.$value.sqlQUOTE;
	}
	public function getEntityName(){ //nombre+alias
		$alias=$this->getAlias();
		if($alias!=""){
			$alias=sqlAS.$alias;
		}
		return $this->getName().$alias;
	}
	
	public function getRefName(){//referencia +nombre o alias{
		$p=$this->getParent();
		if($p!=null){
			$prefer=$p->getRefName().sqlREF;
		}
		$ref=$this->getAlias();
		if($ref==""){
			$ref=$this->getName();
		}
		return $prefer.$ref;
	}
	
	public function getParent(){
		if($this->objparent!=null){
			return $this->objparent;
		}else{
			return null;
		}
	}
	static function setRefNameMode($mode){
		self::$nameMode=$mode;
	}
	
	public function __toString(){
		if (self::$nameMode){
			return $this->getParent()->getRefName().sqlREF.$this->getName();
		}
		return $this->getName();
	}
	
	/*public function getField(){
		if ($this->table->insertMode){
			$r= $this->getInsertValue();
		}else{
			$r= $this->table.'.'.$this->name;
			if($this->table->updateMode){
				$r.='='.$this->getInsertValue();
			}
		}
		return $r;
	}
	
	public function getInsertValue(){
		switch($this->type){
			case "text":
				return "'".$this->value."'";
		}
	}*/
	
	
	public function applyFunction(){
		$ref="";
		if($this->getParent()!=null){
			$ref=$this->getParent()->getRefName().sqlREF;
		}
		$s=$ref.$this->getName();
		$alias=$this->getAlias();
		if($alias!=""){
			$alias=sqlAS.$alias;
		}
		if($this->func!=null){
			return $this->getFunction($s).$alias;
		}else{
			return $s.$alias;
		}
	}
	public function setFunction(_function $func){
		$this->func=$func;
		return $func;
	}
	public function getFunction($sfield){
		$func=$this->func;
		$r=sprintf($func,$sfield);
		return $r;
	}
		
	public function validate(){
		$type=$this->type;
		$value=$this->getValue();
		if (($this->req) && ($value=="")){
			return new errorMessage($this,"No definido");
		}
		if($type==self::tInt){
			if (!is_integer($value)){
				return new errorMessage($this,$this->errorMsg);
			}
		}else if ($type==self::tFloat){
			if (!is_numeric($value)){
				return new errorMessage($this,$this->errorMsg);
			}
		}else{
			$p = self::$patrones[$type];
			if ($p!=""){
				if(!ereg($p,$value)){
			//echo "<br />tipo: ".$p."<br />";
					return new errorMessage($this,$this->errorMsg);
				}
			}
		}
	}
		
}

class table implements iEntity{
	public $name;
	public $alias;
	
	public $attributes=array();
	
	public function __construct($tname,$alias=""){
		$this->name=$tname;
		$this->alias=$alias;
	}
	
	public function add(iAttribute $attr){
		$attr->objparent=$this;
		$this->attributes[$attr->getName()]=$attr;
		return $attr;
	}
	
	public function getName(){
		return $this->name;
	}
	public function getAlias(){
		return $this->alias;
	}
	public function getEntityName(){ //nombre+alias
		$alias=$this->getAlias();
		if($alias!=""){
			$alias=sqlAS.$alias;
		}
		return $this->getName().$alias;		
	}
	public function getAttributes(){
		return $this->attributes;
	}
	
	public function getRefName(){ //referencia +nombre o alias{
		$ref=$this->getAlias();
		if($ref==""){
			$ref=$this->getName();
		}
		return $ref;
	}
	
	public function validate(){
		$vobj=array()		;
		$attr=$this->getAttributes();
		foreach($attr as $value){
			$vobj[] = $value->validate();
		}
		return $vobj;
	}
}

class criteria{
	public $criterias=array();
	public $operator=sqlEQUAL;
	
	public function __construct($operator,$c1,$c2=null){
		$this->operator=$operator;
		if(is_array($c1)){
			$this->criterias = $c1;
		}else{
			$this->criterias[]=$c1;
		}
		if($c2!=null){
			$this->criterias[]=$c2;			
		}
	}
	
	public function  __toString(){
		return sqlPS.implode($this->operator,$this->criterias).sqlPE;
	}
}

class join implements iEntity{
	public $name;
	public $alias;
	public $join=sqlINNERJOIN;
	public $criterias=array();
	
	public $entities=array();
	
	public function __construct($tname,$alias=""){
		$this->name=$tname;
		$this->alias=$alias;
	}
	
	public function add(iEntity $ent){
		$this->entities[]=$ent;
		return $ent;
	}
	
	public function addCriteria(criteria $c){
		$this->criterias[]=$c;
		return $c;
	}
	
	public function getCriteria(){
		if(count($this->criterias)!=0){
			field::setRefNameMode(true);
			return sqlON.sqlPS.implode(sqlON,$this->criterias).sqlPE;
			field::setRefNameMode(false);
		}
	}
	
	public function getName(){
		return $this->alias;
	}
	public function getAlias(){
		return $this->alias;
	}
	public function getEntityName(){ //nombre+alias
		$alias=$this->getAlias();
		if($alias!=""){
			$alias=sqlAS.$alias;
		}
		/*return $this->getName().$alias;*/
		foreach($this->entities as $ent){
			$entidades[]=$ent->getEntityName();
		}
		return sqlPS.implode($this->join,$entidades).sqlSPACE.$this->getCriteria().sqlPE;
	}
	public function getAttributes(){
		//return $this->attributes;
		$merge=array();
		foreach($this->entities as $ent){
			$merge=array_merge($merge,$ent->getAttributes());
		}
		return $merge;
	}
	
	public function getRefName(){ //referencia +nombre o alias{
		$ref=$this->getAlias();
		if($ref==""){
			$ref=$this->getName();
		}
		return $ref;
	}
}

class query implements iAttribute{

	public $alias;
	public $limitMax=0;
	public $limitStart=0;
	public $func;
	
	public $entities=array();
	public $orders=array();
	public $groups=array();
	
	public $criterias=array();
	public $havings=array();
	
	public function __construct ($alias=""){
		//$this->name=$name;
		$this->alias=$alias;
	}
	
	public function add(iEntity $ent){
		$this->entities[]=$ent;
		return $ent;
	}
	
	public function groupBy($fields){
		if (is_array($fields)){
			$this->groups=array_merge($this->groups,$fields);
		}else{
			$this->groups[]=$fields;
		}
		return $this;
	}
	
	public function where(criteria $criteria){
		$this->addCriteria($criteria);
		return $this;
	}	
	
	public function having(criteria $criteria){
		$this->addHaving($criteria);
		return $this;
	}
	
	public function addHaving(criteria $c){
		$this->havings[]=$c;
		return $c;
	}
	
	public function getHaving($mode=true){
		if(count($this->havings)!=0){
			field::setRefNameMode($mode);
			return sqlHAVING.sqlPS.implode(sqlAND,$this->havings).sqlPE;
			field::setRefNameMode(false);
		}
	}
	
	
	
	public function getSelect(){
		$r=sqlSELECT;
		$ent=$this->entities;
		foreach($ent as $entidad){
			$attr=$entidad->getAttributes();
			foreach ($attr as $atributo){
				/*$ref="";
				if($atributo->getParent()!=null){
					$ref=$atributo->getParent()->getRefName().sqlREF;
				}$ref.$atributo->getEntityName();*/
				$lista[]=$atributo->applyFunction();
			}
			$entidades[]=$entidad->getEntityName();
		}
		return $r.implode(sqlCOMA,$lista).sqlFROM.implode(sqlCOMA,$entidades).$this->getCriteria().$this->getGroups().$this->getOrder().$this->getLimit();
	}
	
	public function getGroups(){
		$attr = $this->groups;
		if(count($attr)!=0){
			foreach($attr as $value){
				/*$p=$value->getParent();
				$np="";
				if($p!=null){
					$np=$p->getRefName().sqlREF;
				}
				$grupos[]= $np.$value->getName();*/
				$alias="";
				if($value instanceof iAttribute){
					$alias=$value->getAlias();
					if($alias==""){
						$alias=$value->getName();		
					}
				}else{
					$alias=$value;
				}
				$grupos[]= $alias;
			}
			return sqlGROUPBY.implode(sqlCOMA,$grupos).$this->getHaving();
		}
		return "";
	}
	
	public function getInsert(){
		$entidad=$this->entities[0];//solo aplica a la primera entidad
		//foreach($ent as $entidad){
			$attr=$entidad->getAttributes();
			foreach ($attr as $atributo){
				if($atributo instanceof field){
					$lista[]=$atributo->getName();
					$valor[]=$atributo->getValue();
				}
			}
			//$entidades[]=$entidad->getEntityName();
		//}
		return sqlINSERTINTO.$entidad->getName().sqlSPACE.sqlPS.implode(sqlCOMA,$lista).sqlPE.sqlVALUES.sqlPS.implode(sqlCOMA,$valor).sqlPE;
		
	}
	public function getUpdate(){
		$entidad=$this->entities[0];//solo aplica a la primera entidad
		//foreach($ent as $entidad){
			$attr=$entidad->getAttributes();
			foreach ($attr as $atributo){
				if($atributo instanceof field){
					$lista[]=$atributo->getName().sqlEQUAL.$atributo->getValue();
				}
			}
			//$entidades[]=$entidad->getEntityName();
		//}
		return sqlUPDATE.$entidad->getName().sqlSET.implode(sqlCOMA,$lista).$this->getCriteria(false);
		
	}
	
	public function addCriteria(criteria $c){
		$this->criterias[]=$c;
		return $c;
	}
	
	public function getCriteria($mode=true){
		if(count($this->criterias)!=0){
			field::setRefNameMode($mode);
			return sqlWHERE.sqlPS.implode(sqlAND,$this->criterias).sqlPE;
			field::setRefNameMode(false);
		}
	}
	
	public function getName(){
		return $this->alias;
	}
	public function getAlias(){
		return $this->alias;
	}
	public function getEntityName(){ //nombre+alias
		$alias = $this->getAlias();
		if($alias!=""){
			$alias=sqlAS.$alias;
		}
		return $this->selectThis().$alias;
	}

	
	public function getRefName(){ //referencia +nombre o alias{
		$ref=$this->getAlias();
		if($ref==""){
			$ref=$this->getName();
		}
		return $ref;
	}
	
	public function applyFunction(){
		$ref="";
		if($this->getParent()!=null){
			$ref=$this->getParent()->getRefName().sqlREF;
		}
		$s=$this->selectThis();
		$alias=$this->getAlias();
		if($alias!=""){
			$alias=sqlAS.$alias;
		}
		if($this->func!=null){
			return $this->getFunction($s).$alias;
		}else{
			return $s.$alias;
		}
	}
	public function setFunction(_function $func){
		$this->func=$func;
	}
	public function getFunction($sfield){
		$func=$this->func;
		$r=sprintf($func,$sfield);
		return $r;
	}
	
	public function selectThis(){
		return sqlPS.$this->getSelect().sqlPE;
	}
	public function __toString(){
		return $this->selectThis();
	}
	
	public function setOrder($fieldname,$ord=sqlASC){
		$this->orders[]=array($fieldname,$ord);
		return $this;
	}
	
	public function getOrder(){
		if(count($this->orders)!=0){
			foreach($this->orders as $value){
				$order[]=$value[0].sqlSPACE.$value[1];
			}
			return sqlORDERBY.implode(sqlCOMA,$order);
		}else{
			return "";
		}
	}
	
	public function setLimit($max,$start=0){
		$this->limitMax=intval($max);
		$this->limitStart=intval($start);
	}
	
	public function getLimit(){
		if($this->limitMax!=0){
			return sqlLIMIT.intval($this->limitStart).sqlCOMA.intval($this->limitMax);
		}
	}
	public function getParent(){
		return null;
	}
	static function setRefNameMode($mode){
	}
	public function getValue(){
		return "";
	}
	
	
	public function validate(){
	}

}
/*

$tabla1 = new table("tabla1","aliasT1");
$campo1t1 = new field("campo1t1","aliasc1t1","","valor1");
$campo2t1 = new field("campo2t1","","","valor2");
$campo3t1 = new field("campo3t1","aliasc3t1","","valor3");
$tabla2 = new table("tabla2","aliasT2");
$campo1t2 = new field("campo1t2","aliasc1t2","","valor4");
$campo2t2 = new field("campo2t2","","","valor5");
$tabla3 = new table("tabla3","aliasT3");
$campo1t3 = new field("campo1t3","aliasc1t3","","valor6");
//$campo2t3 = new field("campo2t3","","","valor7");

$tabla1->add($campo1t1);
$tabla1->add($campo2t1);
$tabla1->add($campo3t1);
$tabla2->add($campo1t2);
$tabla2->add($campo2t2);
$tabla3->add($campo1t3);
//$tabla3->add($campo2t3);

$join = new join("aliasjoin1");

$criteria = new criteria(sqlEQUAL,$campo1t1,$campo1t2);

$join->add($tabla1);
$join->add($tabla2);
$join->addCriteria($criteria);

$join2= new join("j2");
$join2->add($join);
$join2->add($tabla3);
$criteria2 = new criteria(sqlEQUAL,array($campo1t1,$campo1t2,$campo1t3));
$join2->addCriteria($criteria2);

$q3=new query("subconsulta3");
$q3->add($tabla3);
$q3->addCriteria($criteria2);
$q3->setLimit(1);

$q = new query("consulta1");
$tabla1->add($q3);
$q->add($join2);//join2
//$q->add($join);//join2
$q->addCriteria($criteria);
$q->setLimit(5);
$q->setOrder($tabla1->attributes['campo1t1'])->setOrder($campo2t1,sqlDESC);



$q2 = new query("consulta1");
$q2->add($tabla1);
$q2->addCriteria($criteria);


echo $q->getSelect().'<br />';
//echo $q2->getInsert().'<br />';
//echo $q2->getUpdate().'<br />';*/



?>