// 2008, Maycol Alvarez
//VERSION 2 visibility="visible"
// clase efecto de apareición y desaparición como degradado automático


var _thread=[],_itera=[],_obj=[],_mode=[];
var _max=[],_speed=[],_alphaoffset;
var onendalpha = function fin(){}
var defaultvisibility = "visible";//block
function alphaswitch(objeto,maxalpha,velocidad){
	_obj[objeto] = evalobj(objeto);
	_max[objeto]=maxalpha;
	_speed[objeto]=velocidad;
	_mode[objeto]=(_obj[objeto].style.visibility=="hidden" || _obj[objeto].style.visibility=="");
	_itera[objeto]=0;
	if (_mode[objeto]){
		//_obj[objeto].style.opacity="0.0";
		_obj[objeto].style.filter="alpha(opacity=0)";
		_obj[objeto].style.visibility=defaultvisibility;
	} else {
		if (_max[objeto]!=10){_itera[objeto]=(10-_max[objeto]+1);_max[objeto]=10;}
	}
	restrict(objeto);
	_thread[objeto] = setTimeout("thread_fn('"+objeto+"')",_speed[objeto]);
}
function alphashow(objeto,maxalpha,velocidad){
	_obj[objeto] = evalobj(objeto);
	_max[objeto]=maxalpha;
	_speed[objeto]=velocidad;
	
	if (_obj[objeto].style.visibility==defaultvisibility){
		return;
	}
	_mode[objeto]=true;
	_itera[objeto]=0;
	//_obj[objeto].style.opacity="0.0";
	_obj[objeto].style.filter="alpha(opacity=0)";
	_obj[objeto].style.visibility=defaultvisibility;
	restrict(objeto);
	_thread[objeto] = setTimeout("thread_fn('"+objeto+"')",_speed[objeto]);
}
function alphahide(objeto,maxalpha,velocidad){
	_obj[objeto] = evalobj(objeto);
	_max[objeto]=maxalpha;
	_speed[objeto]=velocidad;
	_mode[objeto]=false;
	_itera[objeto]=0;
	if (_max[objeto]!=10){_itera[objeto]=(10-_max[objeto]+1);_max[objeto]=10;}
	restrict(objeto);
	_thread[objeto] = setTimeout("thread_fn('"+objeto+"')",_speed[objeto]);
}
function restrict(objeto){
	_alphaoffset=0;
	if (_max[objeto]>10){_max[objeto]=10;}
	if (_max[objeto]<1){_max[objeto]=1;}
	if (_speed[objeto]>100){_speed[objeto]=100;}
	if (_speed[objeto]<1){_speed[objeto]=1;}
}
function thread_fn(objeto){
	//_itera[objeto]++;
	//_itera[objeto]=_itera[objeto]+_alphaoffset;
	_itera[objeto]=_itera[objeto]+1;
	if (_mode[objeto]){
		//_alphaoffset=_alphaoffset+0.035;
		//_obj[objeto].style.opacity=_itera[objeto]/10;
		_obj[objeto].style.filter="alpha(opacity="+(_itera[objeto]*10)+")";
	} else {
		//_alphaoffset=_alphaoffset+0.05;
		//_obj[objeto].style.opacity=(10-_itera[objeto]+0.02)/10;//reverse
		_obj[objeto].style.filter="alpha(opacity="+((10-_itera[objeto]+1)*10)+")";
	}
	clearTimeout(_thread[objeto]);
	if(_itera[objeto]<_max[objeto]){
		//_speed[objeto]=_speed[objeto]+_itera[objeto];
		_thread[objeto] = setTimeout("thread_fn('"+objeto+"')",_speed[objeto]);
	} else {
		if (!_mode[objeto]){
			//_obj[objeto].style.opacity="0.0";
			_obj[objeto].style.filter="alpha(opacity=0)";
			_obj[objeto].style.visibility="hidden";
		}
		//clearInterval(_thread[objeto]);
		onendalpha(_obj[objeto]);
	}
}

function evalobj(obj){
	if (typeof obj == 'string') {
		return document.getElementById(obj);
	} else {
		return obj;
	}
}
	
