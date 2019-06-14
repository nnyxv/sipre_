<?php
//Archivo de base de datos
//funciones para la conexion
/*$basename="sysgts";
$host="localhost";
$user="root";
$password="";*/
include_once("model.conf.php");
define("ACTIVE_ERRORS",true);//false predeterminado para no mostrar errores
define("sqlAS",' AS ');
define("sqlSELECT",'SELECT ');
define("sqlAST"," * ");
define("sqlEND",";");
define("sqlINSERTINTO",'INSERT INTO ');
define("sqlVALUES",' VALUES ');
define("sqlUPDATE",'UPDATE ');
define("sqlSET",' SET ');
define("sqlWHERE",' WHERE ');
define("sqlHAVING",' HAVING ');
define("sqlEQUAL",' = ');
define("sqlNOTEQUAL",' <> ');
define("sqlIS",' IS ');
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
define("sqlNULL","NULL");
define("sqlDELETEFROM","DELETE FROM ");
define("DEFINED_DATE","'%d-%m-%Y'");
define("DEFINED_TIME","'%h:%i %p'");
define("DEFINED_DATETIME","'%d-%m-%Y %H:%i'");
define("DEFINED_DATETIME12","'%d-%m-%Y %h:%i %p'");
define("DEFINEDphp_TIME","h:i A");
define("DEFINEDphp_DATE","d-m-Y");
define("DEFINEDphp_DATETIME","d-m-Y H:i");
define("DEFINEDphp_DATETIME12","d-m-Y h:i A");
define("DEFINEDphp_DATETIMESQL","d-m-Y H:i:s");
define("sqlOFFSET"," OFFSET ");
define("sqlOR"," OR ");
//define("sqlIN"," IN ");//gregor

function dbNull($value){
	if($value==''){
		return 'NULL';
	}else{
		return $value;
	}
}

function utf_export($object){
	return utf8_encode(var_export($object,true));
}

class connection{
	
	public $con;
	public $basename;
	public $host;
	public $user;
	public $password;
	public $opened=false;
	public $transaction=false;
	public $tables=array();
	public $utf8mode=true;
	
	const errorUnikeKey= 1062;
	
	public function __construct ($basename=connectBASENAME,$host=connectHOST,$user=connectUSER,$password=connectPASSWORD){
		$this->basename=$basename;
		$this->host=$host;
		$this->user=$user;
		$this->password=$password;
	}
	
	public function connect(){
		$con= $this->soConnect($this->host,$this->user,$this->password);		
		if(!$this->soSelectDataBase($this->basename,$con)){
			echo $this->soDataError($con);
		}
		$this->opened=true;
		$this->con=$con;
		//@mysql_query("SET NAMES 'utf8'",$this->con);
	}
	
	//inplementar otro que devuelve el recordset
	public function execute($sql){
		if($this->opened){
			$r=@$this->soQuery($sql,$this->con);
			if($r){
				$c=$this->soFetch($r);
				return $c;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	public function open(){
		$this->connect();
	}
	
	public function close(){
		$this->soClose($this->con);
		$this->opened=false;
	}
	
	public function begin(){
		$this->transaction=true;
		return $this->soQuery("START TRANSACTION;",$this->con);
	}
	
	public function commit(){
		$this->transaction=false;
		return $this->soQuery("COMMIT;",$this->con);
	}
	
	public function rollback(){
		$this->transaction=false;
		return $this->soQuery("ROLLBACK;",$this->con);
	}
	
	public function getQuery(iEntity $table,criteria $criteria=null){
		$r=new query($this);
		$r->add($table);
		if($criteria!=null){
			$r->addCriteria($criteria);
		}
		return $r;
	}
	
	public function getConnectionId(){
		return $this->con;
	}
	
	public function isOpen(){
		return $this->opened;
	}
	
	public function getFields($tname){
		$r= new recordset($this,sqlSELECT.sqlAST.sqlFROM.$tname.sqlEND);
		return $r->getFields();
	}
	
	public  function soFetch($idq){
		//para mysql;
		return @mysql_fetch_assoc($idq);
	}
	
	
	public  function soFetchfield($idq,$i){
		//para mysql;mysql_fetch_field
		return @mysql_fetch_field($idq,$i);
	}
	
	public function soConnect($host,$user,$password){
		return @mysql_connect ($host,$user,$password);
	}
	
	public function soClose($idc=null){
		if($idc==null){
			$idc=$this->con;
		}
		return @mysql_close($idc);
	}
	
	public function soSelectDataBase($basename,$idc=null){
		if($idc==null){
			$idc=$this->con;
		}
		return @mysql_select_db($basename,$idc);
	}
	
	public function soDataError($idc=null){
		if($idc==null){
			$idc=$this->con;
		}
		return @mysql_error($idc);
	}
	
	public function soLastInsertId($idc=null){
		if($idc==null){
			$idc=$this->con;
		}
		return @mysql_insert_id($idc);
	}
	
	public  function soQuery($sql,$idc=null){
		//para mysql
		//var_dump($idc);
		if($idc==null){
			$idc=$this->con;
		}
		$r=@mysql_query($sql,$idc);
		if(!$r){
			return array(new errorMessage($idc,mysql_error($idc),errorMessage::errorSO,mysql_errno($idc)));
		}
		return $r;
		
	}
	public  function soNumRowsQuery($idq){
		//para mysql:
		$r=mysql_num_rows($idq);
		/*if(!$r){
			echo 'ERROR '.$query;
		}*/
		return $r;
	}
	public  function soNumFieldsQuery($idq){
		//para mysql:
		return @mysql_num_fields($idq);
	}
	public  function soSeekQuery($idq,$pos){
		//para mysql:
		return @mysql_data_seek($idq,$pos);
	}
	
	public function __get($name){
		if (isset($this->tables[$name])){
			return $this->tables[$name];
		}
		$t = new table($name,"",$this);
		$this->tables[$name]=$t;
		return $t;
	}
	
	public function __toString(){
		return var_export($this->con,true);
	}
	
	public function parseUTF8($text){
		if($this->utf8mode){
			return utf8_decode($text);
		}else{
			return $text;
		}
	}
	
	public function excape($val){
		return mysql_real_escape_string($val);
	}
}

class recordset implements Iterator{
	public $mquery;
	private $nrows;
	private $nfields;
	private $current=-1;
	public $mfetch;
	public $eof=false;
	public $parseutf8=true;
	public $con;
	
	public function __construct(connection $con, $query){
		$this->con=$con;
		if ($con->opened){
			$this->con=$con;
			//$this->parseutf8=$con->utf8mode;
			$this->mquery=$con->soQuery($query);
			if($this->mquery){
				$this->nrows=$con->soNumRowsQuery($this->mquery);
				if(($this->nrows===false) && ACTIVE_ERRORS){
					echo "ERROR: ".mysql_error($con->con)." SQL:".$query;
				}
				$this->nfields=$con->soNumFieldsQuery($this->mquery);
			}/*else{
				echo 'ERROR: '.$query;
			}*/
			$this->next();
		}else{
			return false;
		}
	}
	
	public function getNumRows(){//$compare=null
		/*if($compare!=null){
			return ($this->nrows==$compare);
		}*/
		return $this->nrows;
	}
	public function getNumFields(){//$compare=null
		/*if($compare!=null){
			return ($this->nfields==$compare);
		}*/
		return $this->nfields;
	}
	
	public function next(){
		//echo "next <br />";
		$this->mfetch=$this->con->soFetch($this->mquery);
		if($this->mfetch==false){
			$this->eof=true;
			$this->current=-1;
		}else{
			$this->current++;			
		}
		return $this->mfetch;
	}
		
	public function parse($val){
		if($this->parseutf8){
			return utf8_encode($val);
		}else{
			return ($val);
		}
	}
	
	public function __get($name){
		if(isset($this->mfetch[$name])){
			return $this->parse($this->mfetch[$name]);
		}
	}

    public function rewind() {
		$this->con->soSeekQuery($this->mquery,0);
		$this->current=0;
		$this->eof=false;
		$this->next();
    }

    public function current() {
	   return $this;
    }

    public function key() {
	   return $this->current;
    }


    public function valid() {
        $var = $this->eof == false;
        return $var;
    }
	
	public function getRow(){
		//return $this->mfetch;
		foreach ($this->mfetch as $k=>$v){
			$ret[$k]=$this->parse($v);
		}
		return $ret;
	}
	
	public function getFields(){
		if(!$this->con->isOpen()){
			return false;
		}
		$i = 0;
		while ($i < $this->nfields) {
		    $meta = $this->con->soFetchfield($this->mquery, $i);
		    if ($meta) {
		       $r[]= $meta;
		    }
		    $i++;
		}
		return $r;
	}
	
	public function field($name){
		if(isset($this->mfetch[$name])){
			return $this->parse($this->mfetch[$name]);
		}
	}
	
	public function getAssoc($fieldk,$fieldv){
		if($fieldk instanceof iAttribute){
			$fieldk=$fieldk->name;
		}
		if($fieldv instanceof iAttribute){
			$fieldv=$fieldv->name;
		}
		$obj=$this;
		foreach($this as $row){
			$dev[$row->field($fieldk)]=$row->field($fieldv);
			//echo 'x:'.$row->field($fieldv);
		}
		return $dev;
	}
	public function getAssocPlus($fieldk,$arrfieldv,$sep=' '){
		if($fieldk instanceof iAttribute){
			$fieldk=$fieldk->name;
		}
		$obj=$this;
		if(is_array($arrfieldv)){
			foreach($this as $row){
				$dr=null;
				foreach($arrfieldv as $fieldv){
					if($fieldv instanceof iAttribute){
						$fieldv=$fieldv->name;
					}
					$dr[]=$row->field($fieldv);
				}
				$dev[$row->field($fieldk)]=implode($sep,$dr);
				//echo 'x:'.$row->field($fieldv);
			}			
			return $dev;
		}else{
			return $this->getAssoc($fieldk,$arrfieldv);
		}		
	}


}

class errorMessage{
	public $obj;
	public $errorMsq;
	public $numero;
	public $type;
	
	const tField=1;
	const tObject=2;
	const tOther=3;
	
	const errorType=1;
	const errorNOTNULL=2;
	const errorSO=3;
	
	public function __construct($obj,$msg,$tError=self::errorNOTNULL,$numero=null){
		$this->obj=$obj;
		$this->errorMsq=$msg;
		$this->numero=$numero;
		$this->type=$tError;
		//echo "<br />MENSAJE: ".$obj->getName().' m: '.$msg."<br />";
	}
	
	public function getType(){
		if($this->obj instanceof field){
			return self::tField;
		}elseif(is_object($this->obj)){
			return self::tObject;
		}else{
			return self::tOther;
		}
	}

	
	public function getObject(){
		return $this->obj;
	}
	
	public function getMessage(){
		$m=$this->errorMsq;
		if($m==""){
			$m=" no v&aacute;lido ";
		}
		return utf8_encode($m);
	}
	
	public function __toString(){
		return $this->getMessage().' TT: '.$this->type;
		//return $this->getMessage().' N:'.$this->numero;
	}
	
	/*public function export(){
		return utf8_encode(var_export($this,true));
	}*/
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
	
	public function __construct ($namefunc,$args="{f}"){
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
	
	public static function dateFormat($field="{f}"){
		return new _function("DATE_FORMAT",array($field,DEFINED_DATE));
	}
	public static function dateDayFormat($field="{f}"){
		return new _function("DATE_FORMAT",array($field,"'%e'"));
	}
	public static function dateMonthFormat($field="{f}"){
		return new _function("DATE_FORMAT",array($field,"'%m'"));
	}
	public static function dateYearFormat($field="{f}"){
		return new _function("DATE_FORMAT",array($field,"'%Y'"));
	}
	
	
	public static function timeFormat($field="{f}"){
		return new _function("DATE_FORMAT",array($field,DEFINED_TIME));
	}
	public static function timeHourFormat($field="{f}"){
		return new _function("DATE_FORMAT",array($field,"'%h'"));
	}
	public static function timeMinuteFormat($field="{f}"){
		return new _function("DATE_FORMAT",array($field,"'%i'"));
	}
	public static function timeAPmFormat($field="{f}"){
		return new _function("DATE_FORMAT",array($field,"'%p'"));
	}
	public static function timeHour24Format($field="{f}"){
		return new _function("DATE_FORMAT",array($field,"'%H'"));
	}
}

class aliasfunction extends _function{
	public $alias;
	public function __construct ($namefunc,$alias,$args="{f}"){
		$this->alias=$alias;
		parent::__construct($namefunc,$args="{f}");
	}
	public function __toString(){
		return parent::__toString().sqlAS.$this->alias;
	}
}

class field implements iAttribute{
	public $name;
	public $alias;
	public $objparent;
	public $type;
	public $originalType;
	public $value;
	public $transformPattern;
	static $nameMode=false;
	public $func;
	public $req=true;
	public $errorMsg="";
	public $maxLength;
	public $primary=false;
	
	const tInt="int";
	const tFloat="float";
	const tUnixDate="unixdate";
	const tDate="date";
	const tTime="time";
	const tDatetime="datetime";
	const tDateTime="datetime";
	const tString="string";
	const tEmail="email";
	const tPhone="phone";
	const tFunction='function';
	const tBool='bool';
	
	static $patrones=array(
	"email" => "^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$",
	"phone2" => "^[0-9]{4}-[0-9]{3}-[0-9]{4}$",
	"phone" => "^([0-9]{4}-)?[0-9]{3}-[0-9]{4}$",
	"" => ""
	);
	
	public function __construct($fieldName,$alias="",$type="string",$value="",$req=true){
		$this->name=$fieldName;
		$this->alias=$alias;
		if($value===null){
			$value='';
		}
		$this->value=$value;
		$this->req=$req;
		$this->setType($type);
	}
			
	function __clone(){
		$r= new field($this->name,$this->alias,$this->type,$this->value,$this->req);
		$r->primary=$this->primary;
		$r->maxLength=$this->maxLength;
		$r->func=$this->func;
		$r->originalType=$this->originalType;
		$r->errorMsg=$this->errorMsg;
		return $r;
	}
	
	public function setType($type){
		$this->originalType=$type;
		if($type=="real"){
			$type=self::tFloat;
		}
		$this->type=$type;
	}
	
	
	public function getName(){
		return $this->name;
	}
	public function getAlias(){
		return $this->alias;
	}
	public function setName($name){
		$this->name=$name;
	}
	public function setAlias($alias){
		$this->alias=$alias;
	}
	public function getValue(){
		$r=$this->getTransformValue($non_quote);
		if(!$non_quote){
			$r=sqlQUOTE.escapar($r).sqlQUOTE;
		}
		return $r;
	}
	public function getTransformValue(&$non_quote=false){
		$value=$this->value;
		$type=$this->type;
		/*if($type==self::tInt || $type==self::tFloat){
			$value=parseOnlyNumber($value);
		}elseif($type==tDate){
			//fecha
			$non_quote=true;
			$value="STR_TO_DATE('".$value."',".DEFINED_DATE.")";
		}elseif($type==tTime){
			//hora
			$non_quote=true;
			$value="STR_TO_DATE('".$value."',".DEFINED_TIME.")";
		}
		if (is_object($value)){
			$non_quote=true;
			return $value;
		}*/
		$value= self::getTransformType($value,$type,$non_quote);
		return $value;
	}
	
	public static function getTransformType($value,$type,&$non_quote=false){
		if(strtoupper($value)===sqlNULL){//$value==sqlNULL
			$non_quote=true;
			return sqlNULL;
		}
		if($type==self::tInt || $type==self::tFloat){
			//if(is_numeric($value)){
				$value=parseOnlyNumber($value);
			//}else{
			//	return null;
			//}
		}elseif($type==self::tDate){
			//fecha
			$non_quote=true;
			$value="STR_TO_DATE('".$value."',".DEFINED_DATE.")";
		}elseif($type==self::tTime){
			//hora
			$non_quote=true;
			$value="STR_TO_DATE('".$value."',".DEFINED_TIME.")";
		}elseif($type==self::tDatetime){
			//hora
			$non_quote=true;
			$value="STR_TO_DATE('".$value."',".DEFINED_DATETIME.")";
		}elseif($type==self::tUnixDate){
			//hora
			$non_quote=true;
			$value="FROM_UNIXTIME('".$value."')";
		}elseif($type==self::tFunction){
			$non_quote=true;
		}elseif($type==self::tBool){
			if($value==''){	
				$value=0;
			}else{
				$value=1;
			}
		}
		if (is_object($value)){
			$non_quote=true;
			return $value;
		}
		return $value;
	}
	public function getType(){
		return $this->type;
	}
	public function requiered(){
		return $this->req;
	}
	public function setRequiered($req){
		$this->req=$req;
	}
	public function setValue($value){
		$this->value=$value;
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
	public function setFunction(_function $func,$forceAlias=true){
		$this->func=$func;
		if($forceAlias){
			if($this->alias==""){
				$this->alias=$this->name;
			}
		}
		return $func;
	}
	public function getFunction($sfield){
		$func=$this->func;
		//$r=sprintf($func,$sfield);
		$r=str_replace('{f}',$sfield,$func);
		return $r;
	}
	
	public function setTemporalPrimary(){
		$this->primary=true;
		$this->value=sqlNULL;
	}
		
	public function validate(){
		$type=$this->type;
		$value=$this->getTransformValue();
		//echo "aqui:".$this->name. ' '.$value;
		if($type==self::tBool || $type==self::tFunction){
			return null;
		}
		if (($this->req) && ($value=="")){
			return new errorMessage($this,"No definido",errorMessage::errorNOTNULL);
		}
		if (($this->req) && ($type==field::tDate || $type==field::tTime || $type==field::tDatetime) && ($this->value=="")){
			return new errorMessage($this,"No definido",errorMessage::errorNOTNULL);
		}
		if ($value==sqlNULL && $this->primary){
			$this->primary=false;
			return null;
		}
		if($type==self::tInt){
			if ((!$this->req) && ($value==sqlNULL || $value=='')){
				return null;
			}
			if (!is_numeric($value)){				
				return new errorMessage($this,$this->errorMsg,errorMessage::errorType);
			}else{
				//evaluando los resultados
				/*$r = (integer) $value;
				if(strval($r)!=strval($value)){
					return new errorMessage($this,$this->errorMsg,errorMessage::errorType);
				}*/
				//si encuentra un decimal
				if(strpos($value,_CHARDECIMAL)!==false){
					return new errorMessage($this,$this->errorMsg,errorMessage::errorType);
				}
			}
		}else if ($type==self::tFloat){
			if ((!$this->req) && ($value==sqlNULL || $value=='')){
				return null;
			}
			if (!is_numeric($value)){
				return new errorMessage($this,$this->errorMsg,errorMessage::errorType);
			}
		
		}else{
			if ((!$this->req) && ($value==sqlNULL || $value=='')){
				return null;
			}
			$p = self::$patrones[$type];
			if ($p!=""){
				if(!eregi($p,$value)){
			//echo "<br />tipo: ".$p."<br />";
					return new errorMessage($this,$this->errorMsg,errorMessage::errorType);
					
				}
			}
		}
		return null;
	}
		
}

class table implements iEntity, IteratorAggregate{
	public $name;
	public $alias;
	
	public $attributes=array();
	
	public function __construct($tname,$alias="",connection $fillConnection=null){
		if($tname!=""){
			$this->name=$tname;
			$this->alias=$alias;
			$this->fillFromConnection($fillConnection);
		}else{
			return null;
		}
	}
	
	function __clone(){
		$r = new table($this->name,$this->alias);
		$attr= $this->getAttributes();
		foreach($attr as $k => $v){
			$nv = clone ($v);
			$nv->objparent=$r;
			$na[$k] = $nv;
		}
		$r->attributes=$na;
		return $r;
	}
	
	public function fillFromConnection($fillConnection){
		if($fillConnection!=null){
			if($fillConnection->isOpen()){
				$fields = $fillConnection->getFields($this->name);
				foreach($fields as $value){
					$name=$value->name;
					$type=strtolower($value->type);
					$req=!($value->not_null==0);
					$ml=$value->max_length;
					$r=new field($name,"",$type,"",$req);
					$r->maxLength=$ml;
					$this->add($r);
				}
			}else{
				return new errorMessage($fillConnection," La Coneccion no ha sido abierta",errorMessage::errorSO);
			}
		}
	}
	
	public function add(iAttribute $attr){
		$attr->objparent=$this;
		$this->attributes[$attr->getName()]=$attr;
		return $attr;
	}
	
	public function insert($name,$value,$type=field::tInt,$required=true,$alias=''){
		$attr=new field($name,$alias,$type,$value,$required);
		$this->add($attr);
		return $attr;
	}
	
	public function forceAdd(iAttribute $attr){
		$attr->objparent=$this;
		$this->attributes[$attr->getAlias()]=$attr;
		return $attr;
	}
	public function getIterator() {
        return new ArrayIterator($this->attributes);
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
		$vobj=array();
		$attr=$this->getAttributes();
		foreach($attr as $value){
			$r=$value->validate();
			if($r!=null){
				$vobj[] = $r;
			}
		}		
		return $vobj;
	}
	
	public function doInsert(connection $c,field $primary=null){
		if(!$c->isOpen()){
			return new errorMessage($c," La coneccion no ha sido abierta",errorMessage::errorSO);
		}
		$q= new query($c);
		$q->add($this);
		return $q->doInsert($primary);
		//return $q->getInsert();
	}
	
	public function getInsert(connection $c,field $primary=null){
		if(!$c->isOpen()){
			return new errorMessage($c," La coneccion no ha sido abierta",errorMessage::errorSO);
		}
		$q= new query($c);
		$q->add($this);
		return $q->getInsertRev($primary);
		//return $q->getInsert();
	}
	
	public function doUpdate(connection $c,$Criteria=null){
		if(!$c->isOpen()){
			return new errorMessage($c," La coneccion no ha sido abierta",errorMessage::errorSO);
		}
		$q= new query($c);
		$q->add($this);
		if($Criteria!=null){
			if($Criteria instanceof field){
				//para no afectar a las llaves en s�
				$q->addCriteria(new criteria(sqlEQUAL,$Criteria,$Criteria->getValue()));
			}elseif($Criteria instanceof criteria){
				$q->addCriteria($Criteria);
			}
		}
		return $q->doUpdate();
	}
	
	public function getUpdate(connection $c,$Criteria=null){
		if(!$c->isOpen()){
			return new errorMessage($c," La coneccion no ha sido abierta",errorMessage::errorSO);
		}
		$q= new query($c);
		$q->add($this);
		if($Criteria!=null){
			if($Criteria instanceof field){
				//para no afectar a las llaves en s�
				$q->addCriteria(new criteria(sqlEQUAL,$Criteria,$Criteria->getValue()));
			}elseif($Criteria instanceof criteria){
				$q->addCriteria($Criteria);
			}
		}
		return $q->getUpdate();
	}
	
	public function doDelete(connection $c,$Criteria=null){
		if(!$c->isOpen()){
			return new errorMessage($c," La coneccion no ha sido abierta",errorMessage::errorSO);
		}
		$q= new query($c);
		$q->add($this);
		if($Criteria!=null){
			if($Criteria instanceof field){
				//para no afectar a las llaves en s�
				$q->addCriteria(new criteria(sqlEQUAL,$Criteria,$Criteria->getValue()));
			}elseif($Criteria instanceof criteria){
				$q->addCriteria($Criteria);
			}
		}
		return $q->doDelete();
	}
	
	public function doQuery(connection $c,$Criteria=null){
		if(!$c->isOpen()){
			return new errorMessage($c," La coneccion no ha sido abierta",errorMessage::errorSO);
		}
		$q= new query($c);
		$q->add($this);
		if($Criteria!=null){
			if($Criteria instanceof field){
				//para facilitar las cosas
				$q->addCriteria(new criteria(sqlEQUAL,$Criteria,$Criteria->getValue()));
			}elseif($Criteria instanceof criteria){
				$q->addCriteria($Criteria);
			}
		}
		return $q;
	}
	
	public function doSelect(connection $c,$Criteria=null){
		if(!$c->isOpen()){
			return new errorMessage($c," La coneccion no ha sido abierta",errorMessage::errorSO);
		}
		$q= new query($c);
		$q->add($this);
		if($Criteria!=null){
			if($Criteria instanceof field){
				//para facilitar las cosas
				$q->addCriteria(new criteria(sqlEQUAL,$Criteria,$Criteria->getValue()));
			}elseif($Criteria instanceof criteria){
				$q->addCriteria($Criteria);
			}
		}
		return $q->doSelect();
	}
	
	public function getSelect(connection $c,$Criteria=null){
		if(!$c->isOpen()){
			return new errorMessage($c," La coneccion no ha sido abierta",errorMessage::errorSO);
		}
		$q= new query($c);
		$q->add($this);
		if($Criteria!=null){
			if($Criteria instanceof field){
				//para facilitar las cosas
				$q->addCriteria(new criteria(sqlEQUAL,$Criteria,$Criteria->getValue()));
			}elseif($Criteria instanceof criteria){
				$q->addCriteria($Criteria);
			}
		}
		return $q->getSelect();
	}
	
	public function __get($name){
		if(isset($this->attributes[$name])){
			return $this->attributes[$name];//->getValue();
		}else{
			$r=new field($name);
			$this->add($r);
			return $r;//->getValue();
		}
	}
	public function __set($name,$value){
		if(isset($this->attributes[$name])){
			$this->attributes[$name]->setValue($value);
		}else{
			$r=new field($name);
			$this->add($r);
			$r->setValue($value);
		}
	}
	
	public function join(iEntity $entidad=null, iAttribute $field1=null, iAttribute $field2=null, $type=sqlINNERJOIN) {
	
		if($entidad!=null && $field1!=null && $field2!=null){
			$union = new join('');
			$union->add($this);
			$union->join=$type;
			$union->add($entidad);
			$union->addCriteria(new criteria(sqlEQUAL,$field1,$field2));
			return $union;
		}
	}
	
}
function escapar($s){
	//validar uso de magicquotes#
	//if (!get_magic_quotes_gpc()){
		return addslashes($s);
	/*}else{
		return $s;
	}*/
}
//$cadenas;
function dbexcape($cad){
	//global $cadenas;
	$cadena=$cad;
	if(is_string($cadena)){
		//busca si esta entecomillas
		if($cadena[0]=="'" && $cadena[strlen($cadena)-1]="'"){
			//$quote=true;
			$cadena=substr($cadena,1,strlen($cadena)-2);
			$cadena = escapar($cadena);
			$cadena="'".$cadena."'";
		}
		//$cadenas.=mysql_real_escape_string($cadena).' - ';
		//if($quote){
		//}
		//echo 'AQUI:('.$cadena.');';
	}
	//echo "aqui: ".$cad;
	return $cadena;
}

class criteria{
	public $criterias=array();
	public $operator=sqlEQUAL;
	
	public function __construct($operator,$c1,$c2=NULL){
		if($operator==""){
			$operator=sqlEQUAL;
		}
		
			$c1=dbexcape($c1);
		
			$c2=dbexcape($c2);
		
		$this->operator=$operator;
		if(is_array($c1)){
			$this->criterias = $c1;
		}else{
			$this->criterias[]=$c1;
		}
		//original :$c2!=null----------revision 002
		if($c2!==NULL){// hay que comparar si no se defini� (=== / !==), isset podria aplicarse
			$this->criterias[]=$c2;			
		}
		/*echo "c1".$ci."<br />";
		echo "c2".$c2."<br />";
		//$this->criterias[]=0;
		echo "x".utf_export($this->criterias)."<br />";*/
	}
	
	public function  __toString(){
		//echo 'citeria:'.sqlPS.implode($this->operator,$this->criterias).sqlPE;
		return sqlPS.implode($this->operator,$this->criterias).sqlPE;
	}
}

class betweenCriteria{
	public $f1;
	public $f2;
	public $d;
	
	public function __construct($d,$f1,$f2){
		$this->d=$d;
		$this->f1=$f1;
		$this->f2=$f2;
	}
	
	public function __toString(){
		return sqlPS.$this->d.' BETWEEN '.$this->f1.' AND '.$this->f2.sqlPE;
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
	
	public function join(iEntity $entidad, iAttribute $field1, iAttribute $field2, $type=sqlINNERJOIN) {
		if($entidad!=null && $field1!=null && $field2!=null){
			$union = new join('');
			$union->add($this);
			$union->add($entidad);
			$union->addCriteria(new criteria(sqlEQUAL,$field1,$field2));
			return $union;
		}
	}
}

class query implements iAttribute{

	public $con;
	public $alias;
	public $limitMax=0;
	public $limitStart=0;
	public $func;
	public $optional='';
	
	public $entities=array();
	public $orders=array();
	public $groups=array();
	
	public $criterias=array();
	public $havings=array();
	
	public function __construct (connection $con, $alias=""){
		//$this->name=$name;
		$this->con=$con;
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
	
	
	public function doSelect(){
	
		if(!$this->con->isOpen()){
			return new errorMessage($c," La coneccion no ha sido abierta",errorMessage::errorSO);
		}
		//echo "consulta:".$cadenas;
		return new recordset($this->con,$this->getSelect());
	}
	
	public function doInsert(field $primary = null){
		if(!$this->con->isOpen()){
			return new errorMessage($c," La coneccion no ha sido abierta",errorMessage::errorSO);
		}
		if($primary!=null){
			$primary->setTemporalPrimary();
		}
		//aplica solo para la primera tabla:
		$entidad=$this->entities[0];
		//tiene que ser tabla
		if($entidad instanceof table){
			$err=$entidad->validate();
			if(count($err)==0){
				//ejecuta la consulta
				$q=$this->getInsert();
				//echo 'AQUIII: '.$q;
				//echo "consulta:[ ".$q.' ]';
				$r=$this->con->soQuery($q);
				return $r;
			}else{
				return $err;
			}
		}else{
			return array(new errorMessage($entidad,"no es una tabla",errorMessage::errorSO));
		}
	}
	
	public function getSelect(){
		$r=sqlSELECT.$this->optional;
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
	
	public function getDelete(){
		$entidad=$this->entities[0];//solo aplica a la primera entidad
		return sqlDELETEFROM.$entidad->getName().$this->getCriteria().";";		
	}
	
	public function doDelete(){
		if(!$this->con->isOpen()){
			return new errorMessage($c," La coneccion no ha sido abierta",errorMessage::errorSO);
		}
		//aplica solo para la primera tabla:
		$entidad=$this->entities[0];
		//tiene que ser tabla
		if($entidad instanceof table){
			//$err=$entidad->validate();
			//if(count($err)==0){
				//ejecuta la consulta
				$q=$this->getDelete();
				//echo "consulta:[ ".$q.' ]';
				$r=$this->con->soQuery($q);
				return $r;
			//}else{
			//	return $err;
			//}
		}else{
			return array(new errorMessage($entidad,"no es una tabla",errorMessage::errorSO));
		}
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
	public function getInsertRev($primary=null){
		if($primary!=null){
			$primary->setTemporalPrimary();
		}
		$entidad=$this->entities[0];//solo aplica a la primera entidad
		//foreach($ent as $entidad){
			$attr=$entidad->getAttributes();
			foreach ($attr as $atributo){
				if($atributo instanceof field){
					$lista[]=$atributo->getName();
					$valor[]=$this->con->parseUTF8($atributo->getValue());
				}
			}
			//$entidades[]=$entidad->getEntityName();
		//}
		return sqlINSERTINTO.$entidad->getName().sqlSPACE.sqlPS.implode(sqlCOMA,$lista).sqlPE.sqlVALUES.sqlPS.implode(sqlCOMA,$valor).sqlPE;
		
	}
	public function getInsert(){
		$entidad=$this->entities[0];//solo aplica a la primera entidad
		//foreach($ent as $entidad){
			$attr=$entidad->getAttributes();
			foreach ($attr as $atributo){
				if($atributo instanceof field){
					$lista[]=$atributo->getName();
					$valor[]=$this->con->parseUTF8($atributo->getValue());
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
					$lista[]=$atributo->getName().sqlEQUAL.$this->con->parseUTF8($atributo->getValue());
				}
			}
			//$entidades[]=$entidad->getEntityName();
		//}
		return sqlUPDATE.$entidad->getName().sqlSET.implode(sqlCOMA,$lista).$this->getCriteria(false);
		
	}	
	
	public function doUpdate(){
		if(!$this->con->isOpen()){
			return new errorMessage($c," La coneccion no ha sido abierta",errorMessage::errorSO);
		}
		//aplica solo para la primera tabla:
		$entidad=$this->entities[0];
		//tiene que ser tabla
		if($entidad instanceof table){
			$err=$entidad->validate();
			if(count($err)==0){
				//ejecuta la consulta
				$q=$this->getUpdate();
				//echo "consulta:[ ".$q.' ]';
				$r=$this->con->soQuery($q);
				return $r;
			}else{
				return $err;
			}
		}else{
			return array(new errorMessage($entidad,"no es una tabla",errorMessage::errorSO));
		}
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
	
	public function orderBy($fieldname,$ord=sqlASC){
		return $this->setOrder($fieldname,$ord);
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
			$r=sqlLIMIT.intval($this->limitMax).sqlOFFSET.sqlSPACE.intval($this->limitStart);
			return $r;
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
?>