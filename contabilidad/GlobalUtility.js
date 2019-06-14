function Format(num,Decimal) {
num = num.toString().replace(/\$|\,/g,'');
if (num.indexOf('.') != -1){ 
     cents2 = Alltrim(num.substring(num.indexOf('.')+1,(num.indexOf('.') +1) + Decimal));
	 iRestar = cents2.length;
	 }
else{	 
     iRestar = 0;
     cents2 =''
}

if (iRestar != Decimal){
   sumarcero = ''
   iRestar =  Decimal - iRestar;
   for (f= 1; f <= iRestar ; f++){
       sumarcero += '0';
   } 
   cents2 = cents2 + sumarcero
}

if(isNaN(num))
num = "0";
sign = (num == (num = Math.abs(num)));
num = Math.floor(num*100+0.50000000001);
cents = num%100;
num = Math.floor(num/100).toString();
if(cents<10)
cents = "0" + cents;
for (var i = 0; i < Math.floor((num.length-(1+i))/3); i++)
num = num.substring(0,num.length-(4*i+3))+','+
num.substring(num.length-(4*i+3));
return (((sign)?'':'-') +  num + '.' + cents2);

}
//  End -->
function addCommas(nStr)
{
	nStr += '';
	x = nStr.split('.');
	x1 = x[0];
	x2 = x.length > 1 ? '.' + x[1] : '';
	var rgx = /(\d+)(\d{3})/;
	while (rgx.test(x1)) {
		x1 = x1.replace(rgx, '$1' + ',' + '$2');
	}
	return x1 + x2;
}



<!--
var nav4 = window.Event ? true : false;
function CheckNumericJ(evt){	
// NOTE: Backspace = 8, Enter = 13, '0' = 48, '9' = 57 && key => 44 && key <= 46	
var key = nav4 ? evt.which : evt.keyCode;	
return ((key >= 48 && key <= 57) || key == 8 || key == 44 || key == 45 || key == 46);
}
//-->
<!--
var nav4 = window.Event ? true : false;
function CheckNumericJEnter(form,field,evt,NextOb){	
if (evt.keyCode == 46 || (evt.keyCode >= 37 && evt.keyCode <= 40)){
	return true; 
}
if (evt.keyCode == 13 || evt.keyCode == 9){
				var next=0, found=false
				var f=eval("document."+form.name);
				if(evt.keyCode!=13) return;
				if (NextOb!=""){
					eval("document."+form.name+"."+NextOb+".focus()");
				return;
				}

				for(var i=0;i<f.length;i++)	{
					if(field.name==f.elements[i].name){
						next=i+1;
						found=true
						break;
					}
				}
				
				while(found){
					if( f.elements[next].disabled==false &&  f.elements[next].type!='hidden' && !f.elements[next].readonly){
						f.elements[next].focus();
						break;
					}
					else{
						if(next<f.length-1)
							next=next+1;
						else
							break;
					}
				}
		return true;
}
// NOTE: Backspace = 8, Enter = 13, '0' = 48, '9' = 57 && key => 44 && key <= 46	
var key = nav4 ? evt.which : evt.keyCode;	
return ((key >= 48 && key <= 57) || key == 8 || key == 44 || key == 45 || key == 46);
}
//-->

<!--
function Alltrim(str){
	var resultStr = "";
	resultStr = TrimLeft(str);
	resultStr = TrimRight(resultStr);
	return resultStr;
}
//-->
<!--
function TrimRight( str ) {
	var resultStr = "";
	var i = 0;

	// Return immediately if an invalid value was passed in
	if (str+"" == "undefined" || str == null)	
		return null;

	// Make sure the argument is a string
	str += "";
	
	if (str.length == 0) 
		resultStr = "";
	else {
  		// Loop through string starting at the end as long as there
  		// are spaces.
  		i = str.length - 1;
  		while ((i >= 0) && (str.charAt(i) == " "))
 			i--;
 			
 		// When the loop is done, we're sitting at the last non-space char,
 		// so return that char plus all previous chars of the string.
  		resultStr = str.substring(0, i + 1);
  	}
  	
  	return resultStr;  	
}
//-->
function TrimLeft( str ) {
	var resultStr = "";
	var i = len = 0;

	// Return immediately if an invalid value was passed in
	if (str+"" == "undefined" || str == null)	
		return null;

	// Make sure the argument is a string
	str += "";

	if (str.length == 0) 
		resultStr = "";
	else {	
  		// Loop through string starting at the beginning as long as there
  		// are spaces.
//	  	len = str.length - 1;
		len = str.length;
		
  		while ((i <= len) && (str.charAt(i) == " "))
			i++;

   	// When the loop is done, we're sitting at the first non-space char,
 		// so return that char plus the remaining chars of the string.
  		resultStr = str.substring(i, len);
  	}

  	return resultStr;
}
// JavaScript Document
// LIBARDO DÍAZ FLÓREZ Bucaramanga(Colombia)

// Esta función permitirá validar la fecha
// En el objeto text hacemos lo Siguiente
/*
   <input type='text' name=cajaFecha onChange='fechas(this.value); this.value=borrar'>
*/
function IsDate(caja)
{ 
   if (caja)
   {  
      borrar = caja;
      if ((caja.substr(2,1) == "/") && (caja.substr(5,1) == "/"))
      {      
         for (i=0; i<10; i++)
	     {	
            if (((caja.substr(i,1)<"0") || (caja.substr(i,1)>"9")) && (i != 2) && (i != 5))
			{
               borrar = '';
               break;  
			}  
         }
	     if (borrar)
	     { 
	        a = caja.substr(6,4);
		    m = caja.substr(3,2);
		    d = caja.substr(0,2);
		    if((a < 1900) || (a > 2050) || (m < 1) || (m > 12) || (d < 1) || (d > 31))
		       borrar = '';
		    else
		    {
		       if((a%4 != 0) && (m == 2) && (d > 28))	   
		          borrar = ''; // Año no viciesto y es febrero y el dia es mayor a 28
			   else	
			   {
		          if ((((m == 4) || (m == 6) || (m == 9) || (m==11)) && (d>30)) || ((m==2) && (d>29)))
			         borrar = '';	      				  	 
			   }  // else
		    } // fin else
         } // if (error)
      } // if ((caja.substr(2,1) == "/") && (caja.substr(5,1) == "/"))			    			
	  else
	     borrar = '';
		 retornaisDate = true;
	  if (borrar == '')
    		 retornaisDate = false;
   } // if (caja)   
   return retornaisDate 
} // FUNCION



<!--
    function VerificarFechasJ(jForma){
   	   retornaFecha = false;
	     for (L = 0; L < jForma.elements.length; L++){
        sNombreCampo = Alltrim(jForma.elements[L].name);
		if (sNombreCampo!=null){
       	if (sNombreCampo.substring(0,2) == 'xD'){
 	       sDia = jForma.elements[L].value;  
		   sMes = ''
		   sAno = ''
		   len = sNombreCampo.length;
		      sNombre = sNombreCampo.substring(2,len);
  	          for (j = 0; j < jForma.elements.length; j++){
      			  sNombreCampo2 = Alltrim(jForma.elements[j].name);
 		  		  if (sNombreCampo2!=null){
			  		  len = sNombreCampo2.length;
     	      		  if (Alltrim(sNombreCampo2.substring(2,len)) == Alltrim(sNombre) && (Alltrim(sNombreCampo2.substring(2,1)) == 'M' || Alltrim(sNombreCampo2.substring(2,1)) == 'A')){ 				  
           				if (Alltrim(sNombreCampo2.substring(2,1))  == 'M'){ 
								  sMes = jForma.elements[j].value;
							}//if Alltrim(sNombreCampo2.substring(2,1))  == 'M'{ 
							else{//if Alltrim(sNombreCampo2.substring(2,1))  == 'M'{ 
								  sAno = jForma.elements[j].value;
							}//if Alltrim(sNombreCampo2.substring(2,1))  == 'M'{ 
			       	  }//if (Alltrim(sNombreCampo2.substring(3,len)) == Alltrim(sNombre) && (Alltrim(sNombreCampo2.substring(2,1)) == 'M' || trim(sNombreCampo2.substring(2,1)) == 'A')){
					  }//if (sNombreCampo2!=null){
				  } //for (j = 0; j < Plantilla.elements.length; j++){
					 sFecha = sDia + "/" + sMes + "/" + sAno;  
    				if  (IsDate(sFecha) != true){
							alert("La Fecha asignada esta errada.",16)
							jForma.elements[L].focus();
							retornaFecha = true;
							break
					}//if  (IsDate(sFecha)){	  
	    } //if (sNombreCampo.substring(0,2) == 'xD'){
		}//if (sNombreCampo!=null){
       } //  for (i = 0; i < Plantilla.elements.length; i++){
	   	return retornaFecha;
 }  // FUNCION
//-->
<!--
function CamposBlancosJ(jForma) {
sMensaje = ""; 
retornaBlancos = false;
for (i = 0; i < jForma.elements.length; i++){
    sNombreCampo = jForma.elements[i].name;
 if (sNombreCampo!=null){
	if (sNombreCampo.substring(2,1) == '_'){
	          sValorCampo = Alltrim(jForma.elements[i].value);
	           if (sNombreCampo.substring(0,1) == 'N'){
       			  if (parseFloat(sValorCampo) == 0){ 
		             sMensaje ='Todos los campos numericos que contengan un asterisco (*) son de caracter obligatorio';
					 jForma.elements[i].focus();
                     i = jForma.elements.length;
                     retornaBlancos = true 
				  }
			  }
			  else{
     			  if (sValorCampo == ''){ 
		             sMensaje ='Todos los campos Textos que contengan un asterisco (*) son de caracter obligatorio';
					 jForma.elements[i].focus();
                     i = jForma.elements.length;
                     retornaBlancos = true 
				  }
			  }
	}
 }
}		
       if (sMensaje != ""){
	       alert(sMensaje,16)
       }
	    			 
		return retornaBlancos; 
}
//-->
<!--
function Maximo(sTexto,longitud){
        sTexto = sTexto.substring(0,longitud);
		return sTexto;
    } 



function fn(form,field,evt,NextOb)
{
//alert(evt.keyCode);
var next=0, found=false
var f=eval("document."+form.name);
if(evt.keyCode!=13) return;
if (NextOb!=""){
	eval("document."+form.name+"."+NextOb+".focus()");
	return;
}
for(var i=0;i<f.length;i++)	{
	if(field.name==f.elements[i].name){
		next=i+1;
		found=true
		break;
	}
}

while(found){
	if( f.elements[next].disabled==false &&  f.elements[next].type!='hidden'){
		f.elements[next].focus();
		break;
	}
	else{
		if(next<f.length-1)
			next=next+1;
		else
			break;
	}
}
}

function fnCuenta(form,field,evt,NextOb)
{

fCampoActual = eval("document."+form.name+"."+field.name);
var next=0, found=false
var f=eval("document."+form.name);
if(evt.keyCode!=13) return;

if (fCampoActual.value == ''){
   fCampoActual.ondblclick();
}
if (NextOb!=""){
	eval("document."+form.name+"."+NextOb+".focus()");
	return;
}
for(var i=0;i<f.length;i++)	{
	if(field.name==f.elements[i].name){
		next=i+1;
		found=true
		break;
	}
}

while(found){
	if( f.elements[next].disabled==false &&  f.elements[next].type!='hidden'){
		f.elements[next].focus();
		break;
	}
	else{
		if(next<f.length-1)
			next=next+1;
		else
			break;
	}
}
}


function fnPress(form,field)
{
var next=0, found=false
var f=form
if(event.keyCode!=13) return;
for(var i=0;i<f.length;i++)	{
	if(field.name==f.item(i).name){
			next=i+1;
		found=true
		break;
	}
}
while(found){
	if( f.item(next).disabled==false &&  f.item(next).type!='hidden'){
		f.item(next).focus();
		break;
	}
	else{
		if(next<f.length-1)
			next=next+1;
		else
			break;
	}
}
}
function winOpen(URL, windowName){
day = new Date();
id = day.getTime();
//eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=0,scrollbars=0,location=0,statusbar=1,menubar=0,resizable=0,"+result+"');");
eval("page" + id + " = open('','" + id + "','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,copyhistory=no,width=700,height=400');");
eval("page" + id + ".location =  URL;");
}

	
	
//-->

/****************************************************************************
*****************************************************************************
*****************************************************************************
************F O R M A T O  D E   N U M E R O*********************************
*****************************************************************************
*****************************************************************************
******************************************************************************/

// mredkj.com
function NumberFormat(num, inputDecimal)
{
this.VERSION = 'Number Format v1.5.4';
this.COMMA = ',';
this.PERIOD = '.';
this.DASH = '-'; 
this.LEFT_PAREN = '('; 
this.RIGHT_PAREN = ')'; 
this.LEFT_OUTSIDE = 0; 
this.LEFT_INSIDE = 1;  
this.RIGHT_INSIDE = 2;  
this.RIGHT_OUTSIDE = 3;  
this.LEFT_DASH = 0; 
this.RIGHT_DASH = 1; 
this.PARENTHESIS = 2; 
this.NO_ROUNDING = -1 
this.num;
this.numOriginal;
this.hasSeparators = false;  
this.separatorValue;  
this.inputDecimalValue; 
this.decimalValue;  
this.negativeFormat; 
this.negativeRed; 
this.hasCurrency;  
this.currencyPosition;  
this.currencyValue;  
this.places;
this.roundToPlaces; 
this.truncate; 
this.setNumber = setNumberNF;
this.toUnformatted = toUnformattedNF;
this.setInputDecimal = setInputDecimalNF; 
this.setSeparators = setSeparatorsNF; 
this.setCommas = setCommasNF;
this.setNegativeFormat = setNegativeFormatNF; 
this.setNegativeRed = setNegativeRedNF; 
this.setCurrency = setCurrencyNF;
this.setCurrencyPrefix = setCurrencyPrefixNF;
this.setCurrencyValue = setCurrencyValueNF; 
this.setCurrencyPosition = setCurrencyPositionNF; 
this.setPlaces = setPlacesNF;
this.toFormatted = toFormattedNF;
this.toPercentage = toPercentageNF;
this.getOriginal = getOriginalNF;
this.moveDecimalRight = moveDecimalRightNF;
this.moveDecimalLeft = moveDecimalLeftNF;
this.getRounded = getRoundedNF;
this.preserveZeros = preserveZerosNF;
this.justNumber = justNumberNF;
this.expandExponential = expandExponentialNF;
this.getZeros = getZerosNF;
this.moveDecimalAsString = moveDecimalAsStringNF;
this.moveDecimal = moveDecimalNF;
this.addSeparators = addSeparatorsNF;
if (inputDecimal == null) {
this.setNumber(num, this.PERIOD);
} else {
this.setNumber(num, inputDecimal); 
}
this.setCommas(true);
this.setNegativeFormat(this.LEFT_DASH); 
this.setNegativeRed(false); 
this.setCurrency(false); 
this.setCurrencyPrefix('$');
this.setPlaces(2);
}
function setInputDecimalNF(val)
{
this.inputDecimalValue = val;
}
function setNumberNF(num, inputDecimal)
{
if (inputDecimal != null) {
this.setInputDecimal(inputDecimal); 
}
this.numOriginal = num;
this.num = this.justNumber(num);
}
function toUnformattedNF()
{
return (this.num);
}
function getOriginalNF()
{
return (this.numOriginal);
}
function setNegativeFormatNF(format)
{
this.negativeFormat = format;
}
function setNegativeRedNF(isRed)
{
this.negativeRed = isRed;
}
function setSeparatorsNF(isC, separator, decimal)
{
this.hasSeparators = isC;
if (separator == null) separator = this.COMMA;
if (decimal == null) decimal = this.PERIOD;
if (separator == decimal) {
this.decimalValue = (decimal == this.PERIOD) ? this.COMMA : this.PERIOD;
} else {
this.decimalValue = decimal;
}
this.separatorValue = separator;
}
function setCommasNF(isC)
{
this.setSeparators(isC, this.COMMA, this.PERIOD);
}
function setCurrencyNF(isC)
{
this.hasCurrency = isC;
}
function setCurrencyValueNF(val)
{
this.currencyValue = val;
}
function setCurrencyPrefixNF(cp)
{
this.setCurrencyValue(cp);
this.setCurrencyPosition(this.LEFT_OUTSIDE);
}
function setCurrencyPositionNF(cp)
{
this.currencyPosition = cp
}
function setPlacesNF(p, tr)
{
this.roundToPlaces = !(p == this.NO_ROUNDING); 
this.truncate = (tr != null && tr); 
this.places = (p < 0) ? 0 : p; 
}
function addSeparatorsNF(nStr, inD, outD, sep)
{
nStr += '';
var dpos = nStr.indexOf(inD);
var nStrEnd = '';
if (dpos != -1) {
nStrEnd = outD + nStr.substring(dpos + 1, nStr.length);
nStr = nStr.substring(0, dpos);
}
var rgx = /(\d+)(\d{3})/;
while (rgx.test(nStr)) {
nStr = nStr.replace(rgx, '$1' + sep + '$2');
}
return nStr + nStrEnd;
}
function toFormattedNF()
{	
var pos;
var nNum = this.num; 
var nStr;            
var splitString = new Array(2);   
if (this.roundToPlaces) {
nNum = this.getRounded(nNum);
nStr = this.preserveZeros(Math.abs(nNum)); 
} else {
nStr = this.expandExponential(Math.abs(nNum)); 
}
if (this.hasSeparators) {
nStr = this.addSeparators(nStr, this.PERIOD, this.decimalValue, this.separatorValue);
} else {
nStr = nStr.replace(new RegExp('\\' + this.PERIOD), this.decimalValue); 
}
var c0 = '';
var n0 = '';
var c1 = '';
var n1 = '';
var n2 = '';
var c2 = '';
var n3 = '';
var c3 = '';
var negSignL = (this.negativeFormat == this.PARENTHESIS) ? this.LEFT_PAREN : this.DASH;
var negSignR = (this.negativeFormat == this.PARENTHESIS) ? this.RIGHT_PAREN : this.DASH;
if (this.currencyPosition == this.LEFT_OUTSIDE) {
if (nNum < 0) {
if (this.negativeFormat == this.LEFT_DASH || this.negativeFormat == this.PARENTHESIS) n1 = negSignL;
if (this.negativeFormat == this.RIGHT_DASH || this.negativeFormat == this.PARENTHESIS) n2 = negSignR;
}
if (this.hasCurrency) c0 = this.currencyValue;
} else if (this.currencyPosition == this.LEFT_INSIDE) {
if (nNum < 0) {
if (this.negativeFormat == this.LEFT_DASH || this.negativeFormat == this.PARENTHESIS) n0 = negSignL;
if (this.negativeFormat == this.RIGHT_DASH || this.negativeFormat == this.PARENTHESIS) n3 = negSignR;
}
if (this.hasCurrency) c1 = this.currencyValue;
}
else if (this.currencyPosition == this.RIGHT_INSIDE) {
if (nNum < 0) {
if (this.negativeFormat == this.LEFT_DASH || this.negativeFormat == this.PARENTHESIS) n0 = negSignL;
if (this.negativeFormat == this.RIGHT_DASH || this.negativeFormat == this.PARENTHESIS) n3 = negSignR;
}
if (this.hasCurrency) c2 = this.currencyValue;
}
else if (this.currencyPosition == this.RIGHT_OUTSIDE) {
if (nNum < 0) {
if (this.negativeFormat == this.LEFT_DASH || this.negativeFormat == this.PARENTHESIS) n1 = negSignL;
if (this.negativeFormat == this.RIGHT_DASH || this.negativeFormat == this.PARENTHESIS) n2 = negSignR;
}
if (this.hasCurrency) c3 = this.currencyValue;
}
nStr = c0 + n0 + c1 + n1 + nStr + n2 + c2 + n3 + c3;
if (this.negativeRed && nNum < 0) {
nStr = '<font color="red">' + nStr + '</font>';
}
return (nStr);
}
function toPercentageNF()
{
nNum = this.num * 100;
nNum = this.getRounded(nNum);
return nNum + '%';
}
function getZerosNF(places)
{
var extraZ = '';
var i;
for (i=0; i<places; i++) {
extraZ += '0';
}
return extraZ;
}
function expandExponentialNF(origVal)
{
if (isNaN(origVal)) return origVal;
var newVal = parseFloat(origVal) + ''; 
var eLoc = newVal.toLowerCase().indexOf('e');
if (eLoc != -1) {
var plusLoc = newVal.toLowerCase().indexOf('+');
var negLoc = newVal.toLowerCase().indexOf('-', eLoc); 
var justNumber = newVal.substring(0, eLoc);
if (negLoc != -1) {
var places = newVal.substring(negLoc + 1, newVal.length);
justNumber = this.moveDecimalAsString(justNumber, true, parseInt(places));
} else {
if (plusLoc == -1) plusLoc = eLoc;
var places = newVal.substring(plusLoc + 1, newVal.length);
justNumber = this.moveDecimalAsString(justNumber, false, parseInt(places));
}
newVal = justNumber;
}
return newVal;
} 
function moveDecimalRightNF(val, places)
{
var newVal = '';
if (places == null) {
newVal = this.moveDecimal(val, false);
} else {
newVal = this.moveDecimal(val, false, places);
}
return newVal;
}
function moveDecimalLeftNF(val, places)
{
var newVal = '';
if (places == null) {
newVal = this.moveDecimal(val, true);
} else {
newVal = this.moveDecimal(val, true, places);
}
return newVal;
}
function moveDecimalAsStringNF(val, left, places)
{
var spaces = (arguments.length < 3) ? this.places : places;
if (spaces <= 0) return val; 
var newVal = val + '';
var extraZ = this.getZeros(spaces);
var re1 = new RegExp('([0-9.]+)');
if (left) {
newVal = newVal.replace(re1, extraZ + '$1');
var re2 = new RegExp('(-?)([0-9]*)([0-9]{' + spaces + '})(\\.?)');		
newVal = newVal.replace(re2, '$1$2.$3');
} else {
var reArray = re1.exec(newVal); 
if (reArray != null) {
newVal = newVal.substring(0,reArray.index) + reArray[1] + extraZ + newVal.substring(reArray.index + reArray[0].length); 
}
var re2 = new RegExp('(-?)([0-9]*)(\\.?)([0-9]{' + spaces + '})');
newVal = newVal.replace(re2, '$1$2$4.');
}
newVal = newVal.replace(/\.$/, ''); 
return newVal;
}
function moveDecimalNF(val, left, places)
{
var newVal = '';
if (places == null) {
newVal = this.moveDecimalAsString(val, left);
} else {
newVal = this.moveDecimalAsString(val, left, places);
}
return parseFloat(newVal);
}
function getRoundedNF(val)
{
val = this.moveDecimalRight(val);
if (this.truncate) {
val = val >= 0 ? Math.floor(val) : Math.ceil(val); 
} else {
val = Math.round(val);
}
val = this.moveDecimalLeft(val);
return val;
}
function preserveZerosNF(val)
{
var i;
val = this.expandExponential(val);
if (this.places <= 0) return val; 
var decimalPos = val.indexOf('.');
if (decimalPos == -1) {
val += '.';
for (i=0; i<this.places; i++) {
val += '0';
}
} else {
var actualDecimals = (val.length - 1) - decimalPos;
var difference = this.places - actualDecimals;
for (i=0; i<difference; i++) {
val += '0';
}
}
return val;
}
function justNumberNF(val)
{
newVal = val + '';
var isPercentage = false;
if (newVal.indexOf('%') != -1) {
newVal = newVal.replace(/\%/g, '');
isPercentage = true; 
}
var re = new RegExp('[^\\' + this.inputDecimalValue + '\\d\\-\\+\\(\\)eE]', 'g');	
newVal = newVal.replace(re, '');
var tempRe = new RegExp('[' + this.inputDecimalValue + ']', 'g');
var treArray = tempRe.exec(newVal); 
if (treArray != null) {
var tempRight = newVal.substring(treArray.index + treArray[0].length); 
newVal = newVal.substring(0,treArray.index) + this.PERIOD + tempRight.replace(tempRe, ''); 
}
if (newVal.charAt(newVal.length - 1) == this.DASH ) {
newVal = newVal.substring(0, newVal.length - 1);
newVal = '-' + newVal;
}
else if (newVal.charAt(0) == this.LEFT_PAREN
&& newVal.charAt(newVal.length - 1) == this.RIGHT_PAREN) {
newVal = newVal.substring(1, newVal.length - 1);
newVal = '-' + newVal;
}
newVal = parseFloat(newVal);
if (!isFinite(newVal)) {
newVal = 0;
}
if (isPercentage) {
newVal = this.moveDecimalLeft(newVal, 2);
}
return newVal;
}

/****************************************************************************
*****************************************************************************
*****************************************************************************
****************F I N  F O R M A T O  D E   N U M E R O**********************
*****************************************************************************
*****************************************************************************
******************************************************************************/
