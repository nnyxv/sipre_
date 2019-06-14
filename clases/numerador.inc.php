<?php

/*(c))2004, Maycol Alvarez
readaptación a php, Febrero de 2009
USO:

GetNum(valor entero);
*///constantes
define ('_CHARMILES',',');
define ('_CHARDECIMAL','.');

        $unidad = array
            (
                array (null,"uno","dos","tres","cuatro","cinco","seis","siete","ocho","nueve","diez","once","doce","trece","catorce","quince","dieciséis","diecisiete","dieciocho","diecinueve","veinte"),
                array(null,"primero","segundo","tercero","cuarto","quinto","sexto","séptimo","octavo","noveno","décimo","undécimo","duodécimo","decimotercero","decimocuarto","decimoquinto","decimosexto","decimoséptimo","decimoctavo","decimonoveno","vigésimo"),
                array(null,"one","two","three","four","five","six","seven","eight","nine","ten","eleven","twelve","thirteen","fourteen","fifteen","sixteen","seventeen","eighteen","nineteen","twenty")
            );
        $decena = array
            (
                array(null,null,"venti","treinta","cuarenta","cincuenta","sesenta","setenta","ochenta","noventa"),
                array(null,null,"vigésimo ","trigésimo","cuadragésimo","quincuagésimo","sexagésimo","septuagésimo","octogésimo","nonagésimo"),
                array(null,null,"twenty-","thirty","forty","fifty","sixty","seventy","eighty","ninety")    
            );
        $centena  = array
            (
                array(null,"ciento","doscientos","trescientos","cuatrocientos","quinientos","seiscientos","setecientos","ochocientos","novecientos"),
                array(null,"centésimo","duocentésimo","tricentésimo","cuadringentésimo","quingentésimo","sexcentésimo","septingentésimo","octingentésimo","noningentésimo"),
                array(null,"hundred","two hundred","three hundred","four hundred","five hundred","six hundred","seven hundred","eight hundred","nine hundred")
            );
        $miles = array
            (
                array("mil ","un millón"," millones"),
                array("milésimo ","millonésimo"," millonésimo"),
                array("thousand ","one million"," millions")
            );
        $contract = array
            (
                array(" y ","cien"),
                array(" ","centésimo"),
                array(" ","hundred")
            );
        $money = array
            (
                array("cero"," con "," centimos "),
                array("zero","",""),
                array(" ","","")
            );
        #endregion
        #region "Código principal"
        $id;
        //private Numerador(){}
        /// <summary>
        /// Devuelve el número en letras de acuerdo al $Idioma
        /// </summary>
        /// <param name="$n">número entero máximo 999999999</param>
        /// <param name="$i">$Idioma</param>
        /// <returns></returns>
        function GetNum( $n, $i=0)
        {
            global $id,$contract,$miles,$unidad,$decena,$centena,$money;
            /*string*/ $cn="";
            $id=intval($i);
         /*string*/ $num = number_format($n,0,'','');
            if (strlen($num)  > 6) //999999999
            {
                $cn=getcentena(substr($num,0,strlen($num)-6));
       
                if (intval(substr($num,0,strlen($num)-6))==1)
                    $cn=$miles[$id][1];
                else
                    $cn.=$miles[$id][2];
                    $cn=trim($cn)." ";
                if (intval(substr($num,strlen($num)-6,3))==1)
                    $cn.=$miles[$id][0];
                else
                    $cn.=getcentena(substr($num,strlen($num)-6,3));
                if (intval(substr($num,strlen($num)-6,3))>0) $cn.=" ". $miles[$id][0];
                $cn=trim($cn)." ";   
                $cn.=getcentena(substr($num,strlen($num)-3,3));
            }
            else if (strlen($num) > 3) //999999
            {
                if (intval(substr($num,0,strlen($num)-3))==1)
                    $cn.=$miles[$id][0];
                else
                    $cn=getcentena(substr($num,0,strlen($num)-3))." ".$miles[$id][0];
                $cn.=getcentena(substr($num,strlen($num)-3,3));
            }
            else if (strlen($num) > 0) //999
            {
                $cn=getcentena(substr($num,0,strlen($num)));
            }
            $pn=trim($cn);
            if($pn==""){
                return $money[$i][0];
            }else{
                return $pn;
            }
        }
        function getcentena($n)
        {
            global $id,$contract,$miles,$unidad,$decena,$centena;
            /*string*/ $r="";
           
            if (($n == "100")&&($id<2))
            {
                return $contract[$id][1];
            }
            else
            {
                $i=0;$j=0;
                if (strlen($n)==3)
                {
                    $i=intval($n[0]/*.ToString()*/);
                    $r=$centena[$id][$i];
                }
                $j=strlen($n)-2;
                if (strlen($n)>1)
                {
                    $i=intval(substr($n,$j,2));
                    if ($i<=20)
                        $r.=" ".$unidad[$id][$i];
                    else
                    {
                        $r.=" ".$decena[$id][intval($n[$j]/*.ToString()*/)];
                        if ($i>29)
                        {
                            $i=intval($n[$j+1]/*.ToString()*/);
                            if ($i>0) $r.= $contract[$id][0];
                        }
                        $r.=$unidad[$id][intval($n[$j+1]/*.ToString()*/)];
                    }
                }
                else
                {
                    $r.=" ".$unidad[$id][intval($n[0]/*.ToString()*/)];
                }
            }
            return trim($r);
        }
       
        function force_number($num,$decimal,$miles){
            $r=str_replace($miles,"",$num);
            $r=str_replace($decimal,".",$r);
            if(!is_numeric($r)){
                $r=0;
            }
            return number_format(floatval($r),2,'.','');
        }
       
        function getMoneyNum($number,$decimal=_CHARDECIMAL,$miles=_CHARMILES,$i=0){
            global $money;
            //fuerza la conversion del numero
            $numero=explode('.',force_number($number,$decimal,$miles));
            return GetNum($numero[0]). $money[$i][1].GetNum($numero[1]).$money[$i][2];
           
            //echo $numero;
        }
/*
        echo GetNum('100100100'),'<br />';
        echo GetNum('999999999'),'<br />';
        echo GetNum('1001',0),'<br />';
        echo GetNum('101',0),'<br />';
        echo GetNum('15',0),'<br />';*/
       
        //echo var_dump(getMoneyNum('100,100,100.20')).'<br />';
      //  echo getMoneyNum('947856720.356').'<br />';
        //echo getMoneyNum('1001.15').'<br />';
        //echo getMoneyNum('1,002.20').'<br />';
        //echo getMoneyNum('0.00').'<br />';
       
       
?>