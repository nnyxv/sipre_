<?php
//coordina la paginacion de una consulta

//forma
//function funcion($page,$maxrows,$order,$ordertype,$capa,$args=''){
//09 diciembre de 2009 fastpaginator 1 sólo parametro

function if_nulled($val){
	if(strtolower($val)=='null'){
		return '';
	}else{
		return $val;
	}
}
class paginator{
	public $maxrows;
	public $order;
	public $ordertype;
	public $totalrows;
	public $page;
	public $pages;
	public $callback;
	public $q;
	public $c;
	public $rec;
	public $classasc='_asc';
	public $classdesc='_desc';
	public $classcolumn='ipaginator';
	public $layer;
	public $args;
	public static $titleasc='Ordenar ascendente';
	public static $titledesc='Ordenar descendente';
	public static $titlepage='Cambiar P&aacute;gina';
	public $count=0;
	public $stringget="<a href=\"#\" title=\"%s\" onclick=\"%s(%s,%s,'%s','%s','%s','%s');\" class=\"%s\"> %s</a>";
	public $stringpages="%s(this.value,%s,'%s','%s','%s','%s');";
	
	public function __construct($callback,$layer,query $query,$max){
		//para obtener los resultados
		$this->q=$query;
		$this->q->optional=' SQL_CALC_FOUND_ROWS ';
		$this->c=$query->con;
		$this->callback=$callback;
		$this->maxrows=$max;
		$this->layer=$layer;
	}
	
	public function getPages($form=true){
		for($i=0;$i<$this->pages;$i++){
			$r.='<option value="'.$i.'"';
			if($i==$this->page){
				$r.=' selected="selected"';
				$onlyprint='<span class="onlyprint">P&aacute;gina '.($i+1).' de '.$this->pages.'</span>';
			}
			$r.='>'.($i+1).' - '.$this->pages.'</option>';
		}
		$action=sprintf($this->stringpages,
		$this->callback,
		$this->maxrows,
		$this->order,
		$this->ordertype,
		$this->layer,
		$this->args
		);
		$r= '<span class="noprint"><select title="'.paginator::$titlepage.'" onchange="'.$action.'">'.$r.'</select></span>';
		if($form){
			$r='<form onsubmit="return false;" class="ipaginator_form">'.$r.'</form>';
		}
		return $onlyprint.$r;
	}
	
	public function getChangePage(){
		
	}

	public function get($field,$title){
		if($field==$this->order){
			if ($this->ordertype==sqlASC){
				$order=sqlDESC;
				$ttitle=paginator::$titledesc;
				$class=$this->classasc;
			}else{
				$class=$this->classdesc;
				$ttitle=paginator::$titleasc;
				$order=sqlASC;
			}
		}else{
			$order=sqlASC;
			$ttitle=paginator::$titleasc;
			$class='';			
		}
		return sprintf($this->stringget,
		$ttitle,
		$this->callback,
		$this->page,
		$this->maxrows,
		$field,
		$order,
		$this->layer,
		$this->args,
		$this->classcolumn.$class,
		$title);
	}
		
	public function run($page,$order,$ordertype=sqlASC,$args=''){
		if(!is_numeric($page)){
			//$page=0;
			$this->page=0;
		}else{
			if($page<0){
				$page=0;
			}
			$this->page=$page;
		}
		$offset= ($this->page)*$this->maxrows;
		$this->q->
				setLimit($this->maxrows,$offset);
		if($order!=''){
			if($ordertype==''){	
				$ordertype=sqlASC;
			}
			$this->q->orderBy($order,$ordertype);
			$this->order=$order;
			$this->ordertype=$ordertype;
		}
		$this->args=$args;
		//return $this->q->GetSelect();
		$rec= $this->q->doSelect();
		$this->rec=$rec;
		$tr=$this->c->execute('SELECT FOUND_ROWS() as total;');
		$this->totalrows=$tr['total'];
		$this->pages=ceil($this->totalrows/$this->maxrows);
		$this->count=$rec->getNumRows();
		return $rec;
	}
	
	public static function getArgs($array){
		foreach ($array as $k=>$v){
			$r.=$k.'='.$v.',';
		}
		$r=substr($r,0,strlen($r)-1);
		return $r;
	}
	
	public static function getExplodeArgs($vstring){
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
}

//Optimiza las operaciones de paginación
class fastpaginator extends paginator{
	public $class_filter="fast_filter";
	public $class_filter_print="fast_filter_print";
	public $argsarr=array();
	public function __construct($callback,$argumentos,query $query){
		//para obtener los resultados
		$this->q=$query;
		$this->q->optional=' SQL_CALC_FOUND_ROWS ';
		$this->c=$query->con;
		$this->callback=$callback;
		/*$this->maxrows=$max;
		$this->layer=$layer;*/
		//procesando args:
		$args=fastpaginator::getExplodeArgs($argumentos);
		$page=if_nulled($args['page']);
		$this->maxrows=if_nulled($args['maxrows']);
		$order=if_nulled($args['order']);
		$ordertype=if_nulled($args['ordertype']);
		$this->layer=if_nulled($args['layer']);		
		unset($args['page']);
		unset($args['maxrows']);
		unset($args['order']);
		unset($args['ordertype']);
		unset($args['layer']);
		foreach($args as $k=>$v){
			$args2.=','.$k.'='.parsePaginator($v);
			$argsarr[$k]=$v;
		}
		if(!is_numeric($page)){
			//$page=0;
			$this->page=0;
		}else{
			if($page<0){
				$page=0;
			}
			$this->page=$page;
		}
		if($order!=''){
			if($ordertype==''){	
				$ordertype=sqlASC;
			}
			$this->order=$order;
			$this->ordertype=$ordertype;
		}
		$this->args=substr($args2,1);
		$this->argsarr=$argsarr;
		$this->stringget="<a href=\"#\" title=\"%s\" onclick=\"%s('page=%s,maxrows=%s,order=%s,ordertype=%s,layer=%s,%s');\" class=\"%s\"> %s</a>";
		$this->stringpages="%s('page='+this.value+',maxrows=%s,order=%s,ordertype=%s,layer=%s,%s');";
	}
	
	public function run(){
		return parent::run($this->page,$this->order,$this->ordertype,$this->args);
	}
	
	public function getArrayArgs(){
		return fastpaginator::getExplodeArgs($this->args);
	}
	
	//NUEVO: devuelve el rellenador de JS
	public function fillJS($nameJSobj){
		$nargs= $this->getArrayArgs();
		$nargs['layer']=$this->layer;
		$nargs['page']=$this->page;
		$nargs['order']=$this->order;
		$nargs['ordertype']=$this->ordertype;
		$nargs['maxrows']=$this->maxrows;
		//volcando las variables principales:
		foreach($nargs as $k=>$v){
			if($k!=''){
				$volc.=",".$k.":'".$v."'";
			}
		}
		return $nameJSobj."={".substr($volc,1)."};";
	}
	
	//NUEVO: devuelve los botones para deshacer filtros aplicados
	public function getRemoveFilters($nameJSobj,$arrDef=null){
		$nargs= $this->getArrayArgs();
		foreach($nargs as $k=>$v){
			$v=if_nulled($v);
			$kt=$k;
			$ke=$k;
			if($v!=''){
				//autohidden
				if(strpos($k,'h_')!==false){
					continue;
				}
				if($arrDef!=null){
					if(isset($arrDef[$k]['change'])){
						if($this->argsarr[$arrDef[$k]['change']]==''){
							continue;
						}
						$ke=$arrDef[$k]['change'];
					}
				}
				$event=$nameJSobj.'.'.$ke.'=\'\';'.$this->callback.'(toPaginator('.$nameJSobj.'));';
				if($arrDef!=null){
					if(isset($arrDef[$k]['hidden'])){
						continue;
					}
					if(isset($arrDef[$k]['event'])){
						$event=$arrDef[$k]['event'];			
					}
					if(isset($arrDef[$k]['addevent'])){
						$event.=$arrDef[$k]['addevent'];
					}
					if(isset($arrDef[$k]['title'])){
						$kt=$arrDef[$k]['title'];
					}
				}
				$volc.='<span class="'.$this->class_filter.'" onclick="'.$event.'" title="Eliminar filtro '.$kt.'"><span class="'.$this->class_filter_print.'"> Filtrado por '.$kt.':</span> '.$v.'</span>';
			}
		}
		return $volc;
	}

	public static function getExplodeArgs($vstring){		
		//excapar las comas y el =
		$vs=str_replace("/,","{1}",$vstring);	
		$vs=str_replace("/=","{2}",$vs);
		$ar=explode(",",$vs);
		foreach($ar as $value){
			$v=explode("=",$value);
			$rv=str_replace("{1}",",",$v[1]);
			$rv=str_replace("{2}","=",$rv);
			$r[$v[0]]=if_nulled($rv);
		}
		return $r;
	}
}
function parsePaginator($vstring){	
	$vs=str_replace(",","{1}",$vstring);	
	$vs=str_replace("=","{2}",$vs);
	return $vs;
}
?>
