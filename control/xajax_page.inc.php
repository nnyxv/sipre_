<?php
//Paginacion y filtros 2.0
//2008, Maycol Alvarez
//POST DEFAULT



//variables:
//$pagetable;
$page;$nrows;$order;$ordertype;$nrc;

function startloadpage($form,$dest,$first,$nr,$pordert= 'ASC'){
	global $nrows,$nrc,$page,$order,$ordertype,$pagetable;
	$nrc=$nr;
	//$pagetable=$dest;
	$page=getnum(getemp('page','',$form));
	//if ($page<0) $page=1;
	$nrows=getnum(getemp('nrows',$nrc,$form));
	$order=getemp('order',$first,$form);
	$ordertype=getemp('ordertype',$pordert,$form);
	return '
	<script type="text/javascript" language="javascript">
	function pageorder'.$dest.'(field,order,page,nrows){
		var f = document.getElementById("paginator'.$dest.'");
		f.elements["order"].value=field;
		f.elements["ordertype"].value=order;
		if (page!=null){
			f.elements["page"].value=page;
		}
		if (nrows!=null){
			f.elements["nrows"].value=nrows;
		}
		//f.submit();
		xajax_'.$dest.'(xajax_getFormValues("paginator'.$dest.'"));
	}
	function changenrows(nr){
		var f = document.getElementById("paginator'.$dest.'");
		f.elements["nrows"].value=nr;
		f.elements["page"].value=1;
		f.submit();
	}
	</script>
	<form action="" method="post" id="paginator'.$dest.'" onsubmit="return false;">
		<input type="hidden" value="'.$page.'"  id="page" name="page" />
		<input type="hidden" value="'.$nrows.'"  id="nrows" name="nrows" />
		<input type="hidden" value="'.$order.'"  id="order" name="order" />		
		<input type="hidden" value="'.$ordertype.'"  id="ordertype" name="ordertype" />';
}//<input type="submit" />

function endloadpage(){
	return '</form>';
}
	
//funciones
function ordertable($field,$title,$dest){
	global $nrows,$page,$order,$ordertype,$nrc;
	$r='<a class="ord" href="javascript:pageorder'.$dest.'(\''.$field.'\'';
	//$r='<a href="'.$pagetable.'?order='.$field;
	if ($field==$order) {
		if ($ordertype=='ASC'){
			$r.=',\'DESC\'';
			$im="images/sortup.gif";
		}else{
			$r.=',\'ASC\'';
			$im="images/sortdown.gif";
		}
	}else{
		$r.=',\'ASC\'';
		//$im="images/sortup.gif";		
	}
	if ($page!=""){
		$r.=','.$page;
	}else{
		$r.=',null';
	}
	if ($nrows!=""){// && $nrows!=$nrc){
		$r.=','.$nrows;
	}else{
		$r.=',null';
	}
	$r.=');">'.$title;
	if ($im!=""){
		$r.='<img border="0" style="padding-left:2px;" src="'.$im.'" />';
	}
	return $r.'</a>';
}


function getlimit(){
	global $page,$nrows;
	if (($page!='') && ($page>0) && ($nrows>0)) {
		return " limit ".(($page-1)*$nrows).",".$nrows;
	}elseif($nrows>0){
		$page=1;
		return " limit ".(($page-1)*$nrows).",".$nrows;
	}else{
		return '';
	}
}

function setLimit(query $query){
	global $page,$nrows;
	if (($page!='') && ($page>0) && ($nrows>0)) {
		//return " limit "..",".;
		$query->setLimit($nrows,(($page-1)*$nrows));
	}elseif($nrows>0){
		$page=1;
		//return " limit ".(($page-1)*$nrows).",".$nrows;
		
		$query->setLimit($nrows,(($page-1)*$nrows));
	}else{
		//return '';
	}
}

function getorder(){
	global $order,$ordertype;
	return " order by ".$order." ".$ordertype;
}
function setOrder(query $query){
	global $order,$ordertype;
	//return " order by ".$order." ".$ordertype;
	$query->setOrder($order,$ordertype);
}

function getpaginator($tr,$nr,$imagepath = "images/",$dest){
	global $page,$nrows,$order,$ordertype;
	$imf='<img border="0" src="'.$imagepath;
	$iml=$imf;
	$imn=$imf;
	$imp=$imf;
	if ($page<1) $page=1;
	if ($nr==$tr){
		$imf.='firstdisab';
		$imp.='prevdisab';
		$imn.='nextdisab';
		$iml.='lastdisab';
	}
	elseif ($page==1){
		$imf.='firstdisab';
		$imp.='prevdisab';
		$imn.='next';
		$iml.='last';
	}
	elseif($page==getpages($tr,$nrows)){
		$imf.='first';
		$imp.='prev';
		$imn.='nextdisab';
		$iml.='lastdisab';
	}
	else{
		$imf.='first';
		$imp.='prev';
		$imn.='next';
		$iml.='last';
	}
	
	$imf.='.gif" alt="Primera" />';
	$iml.='.gif" alt="&Uacute;ltima" />';
	$imn.='.gif" alt="Siguiente" />';
	$imp.='.gif" alt="Anterior" />';//<a href="javascript:pageorder('.$order.','.$ordertype.',1,'.$nrows.');">'
	if ($nr!=$tr) {
		if ($page==1){
			$imn='<a href="javascript:pageorder'.$dest.'(\''.$order.'\',\''.$ordertype.'\','.($page+1).','.$nrows.');">'.$imn.'</a>';
			$iml='<a href="javascript:pageorder'.$dest.'(\''.$order.'\',\''.$ordertype.'\','.getpages($tr,$nrows).','.$nrows.');">'.$iml.'</a>';
		}
		elseif($page==getpages($tr,$nrows)){
			$imf='<a href="javascript:pageorder'.$dest.'(\''.$order.'\',\''.$ordertype.'\',1,'.$nrows.');">'.$imf.'</a>';
			$imp='<a href="javascript:pageorder'.$dest.'(\''.$order.'\',\''.$ordertype.'\','.($page-1).','.$nrows.');">'.$imp.'</a>';
		}
		else{
			$imf='<a href="javascript:pageorder'.$dest.'(\''.$order.'\',\''.$ordertype.'\',1,'.$nrows.');">'.$imf.'</a>';
			$imp='<a href="javascript:pageorder'.$dest.'(\''.$order.'\',\''.$ordertype.'\','.($page-1).','.$nrows.');">'.$imp.'</a>';
			$imn='<a href="javascript:pageorder'.$dest.'(\''.$order.'\',\''.$ordertype.'\','.($page+1).','.$nrows.');">'.$imn.'</a>';
			$iml='<a href="javascript:pageorder'.$dest.'(\''.$order.'\',\''.$ordertype.'\','.getpages($tr,$nrows).','.$nrows.');">'.$iml.'</a>';
		}
	}
	$r= '<div> '.$imf.$imp.$imn.$iml.' P&aacute;gina '.$page.' de '.getpages($tr,$nrows).'</div></div> Mostrando '.$nr.' registros ('.((($page-1)*$nrows)+1).' a '.((($page-1)*$nrows)+$nr).') de un total: '.$tr.'</div>';
	
	return $r;
}

function getpaginator2($tr,$nr){
	global $page,$nrows,$order,$ordertype;
	/*$imf='<img border="0" src="'.$imagepath;
	$iml=$imf;
	$imn=$imf;
	$imp=$imf;*/
	if ($page<1) $page=1;
	/*if ($nr==$tr){
		$imf.='firstdisab';
		$imp.='prevdisab';
		$imn.='nextdisab';
		$iml.='lastdisab';
	}
	elseif ($page==1){
		$imf.='firstdisab';
		$imp.='prevdisab';
		$imn.='next';
		$iml.='last';
	}
	elseif($page==getpages($tr,$nrows)){
		$imf.='first';
		$imp.='prev';
		$imn.='nextdisab';
		$iml.='lastdisab';
	}
	else{
		$imf.='first';
		$imp.='prev';
		$imn.='next';
		$iml.='last';
	}
	
	$imf.='.gif" alt="Primera" />';
	$iml.='.gif" alt="&Uacute;ltima" />';
	$imn.='.gif" alt="Siguiente" />';
	$imp.='.gif" alt="Anterior" />';//<a href="javascript:pageorder('.$order.','.$ordertype.',1,'.$nrows.');">'*/
	$total=getpages($tr,$nrows);
	if ($nr!=$tr) {
		$vin='P&aacute;ginas: ';
		for($i=1;$i<=$total;$i++){
			if ($i!=$page) {
				$vin.='<a href="javascript:pageorder'.$dest.'(\''.$order.'\',\''.$ordertype.'\','.$i.','.$nrows.');">'.$i.'</a>';
			}else{
				$vin.=$i;
			}
			if ($i!=$total){
				$vin.=" - ";
			}
		}
		/*if ($page==1){
			$imn='<a href="javascript:pageorder(\''.$order.'\',\''.$ordertype.'\','.($page+1).','.$nrows.');">'.$imn.'</a>';
			$iml='<a href="javascript:pageorder(\''.$order.'\',\''.$ordertype.'\','.getpages($tr,$nrows).','.$nrows.');">'.$iml.'</a>';
		}
		elseif($page==getpages($tr,$nrows)){
			$imf='<a href="javascript:pageorder(\''.$order.'\',\''.$ordertype.'\',1,'.$nrows.');">'.$imf.'</a>';
			$imp='<a href="javascript:pageorder(\''.$order.'\',\''.$ordertype.'\','.($page-1).','.$nrows.');">'.$imp.'</a>';
		}
		else{
			$imf='<a href="javascript:pageorder(\''.$order.'\',\''.$ordertype.'\',1,'.$nrows.');">'.$imf.'</a>';
			$imp='<a href="javascript:pageorder(\''.$order.'\',\''.$ordertype.'\','.($page-1).','.$nrows.');">'.$imp.'</a>';
			$imn='<a href="javascript:pageorder(\''.$order.'\',\''.$ordertype.'\','.($page+1).','.$nrows.');">'.$imn.'</a>';
			$iml='<a href="javascript:pageorder(\''.$order.'\',\''.$ordertype.'\','.getpages($tr,$nrows).','.$nrows.');">'.$iml.'</a>';
		}*/
	}
	$r= '<p>'.$vin.'<br />P&aacute;gina '.$page.' de '.getpages($tr,$nrows).'</p></p> Mostrando '.$nr.' registros ('.((($page-1)*$nrows)+1).' a '.((($page-1)*$nrows)+$nr).') de un total: '.$tr.'</p>';
	
	return $r;
}

function getpages($tr,$nr){
	return ceil($tr/$nr);
}

function getemp($val,$ret,$form){
	//if ($post) {
		$val=$form[$val];
	/*}else{
		$val=$_GET[$val];		
	}*/
	if ($val=="") {
		return $ret;
	}
	return $val;
}

function getnum($val){
	if(is_numeric($val)){
		return $val;
	}else{
		return 0;
	}
}
?>