//(C) 2009, Maycol Alvarez


var mouseTouch = {

	
	
	//cancelled:false,

	init: function(obj,up_callback,down_callback,move_callback){
		obj.root=obj;
		if(up_callback!=null){
			obj.up_callback=up_callback;
			obj.onmouseup=mouseTouch.mouseUpCatch;
		}
		if(down_callback!=null){
			obj.down_callback=down_callback;
			obj.onmousedown=mouseTouch.mouseDownCatch;
		}
		if(move_callback!=null){
			obj.move_callback=move_callback;
			obj.onmousemove=mouseTouch.mouseMoveCatch;
		}
		//alert(obj.onmousemove);
	},
	
	prevCanceled: function(c){
		mouseTouch.cancelled=c;
	},
	
	mouseUpCatch:function(e){
		var obj = mouseTouch.obj = this;
		var coord = mouseTouch.mouseCatch(e,obj);
		//if(!mouseTouch.cancelled){
			obj.root.up_callback(coord[0],coord[1],coord[2],coord[3]);
		//}
	},
	
	
	mouseDownCatch:function(e){
		var obj = mouseTouch.obj = this;
		var coord = mouseTouch.mouseCatch(e,obj);
		//if(!mouseTouch.cancelled){
			obj.root.down_callback(coord[0],coord[1],coord[2],coord[3]);
		//}
	},
	
	
	mouseMoveCatch:function(e){
		var obj = mouseTouch.obj = this;
		var coord = mouseTouch.mouseCatch(e,obj);
		//if(!mouseTouch.cancelled){
			obj.root.move_callback(coord[0],coord[1],coord[2],coord[3]);
		//}
	},

	mouseCatch: function (e,obj){
		if(e==null){
			e=event;
		}
		if(e==null){
			e=window.event;
		}
		
		//var obj = mouseTouch._obj = this;
	
		var cX= e.clientX;
		var cY= e.clientY;
		
		
		var sX= document.body.scrollLeft;
		var sY= document.body.scrollTop;
		
		
		var oX= mouseTouch._getOffsetLeft(obj.root);
		var oY= mouseTouch._getOffsetTop(obj.root);
		var _x=(cX+sX)-oX;
		var _y=(cY+sY)-oY;
		return Array(_x,_y,oX,oY);
	},
			
	_getOffsetTop: function  (el)	{
		var ot = el.offsetTop;
		while ( ( el = el.offsetParent ) != null )
		{
			ot += el.offsetTop;
		}
		return ot;
	},
	_getOffsetLeft: function  (el)	{
		var ot = el.offsetLeft;
		while ( ( el = el.offsetParent ) != null )
		{
			ot += el.offsetLeft;
		}
		return ot;
	},
	getVScrollWindow: function(){
		var scrOfY = 0;
		if( typeof( window.pageYOffset ) == 'number' ) {
		//Netscape compliant
		scrOfY = window.pageYOffset;
		
		} else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
		//DOM compliant
		scrOfY = document.body.scrollTop;
		
		} else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
		//IE6 standards compliant mode
		scrOfY = document.documentElement.scrollTop;
		
		}
		return scrOfY;

	},
	getHScrollWindow: function(){
		var scrOfX = 0;
		if( typeof( window.pageYOffset ) == 'number' ) {
		//Netscape compliant
	
		scrOfX = window.pageXOffset;
		} else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
		//DOM compliant
		
		scrOfX = document.body.scrollLeft;
		} else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
		//IE6 standards compliant mode
		
		scrOfX = document.documentElement.scrollLeft;
		}
		return scrOfX;
	}

}