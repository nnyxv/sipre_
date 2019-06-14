//intentando trabajar con clases

var Drag = {
	//shadow : null,
	obj : null, 
	
	init : function(o, oRoot, oShadow, minX, maxX, minY, maxY, bSwapHorzRef, bSwapVertRef, fXMapper, fYMapper)
	{
		//la siguiente linea fue agregada por rafaias, si no va a hcer uso de esta funcion la puede eliminar
		//Drag.shadow = oShadow;
		o.onmousedown	= Drag.start;

		o.hmode			= bSwapHorzRef ? false : true ;
		o.vmode			= bSwapVertRef ? false : true ;

		o.root = oRoot && oRoot != null ? oRoot : o ;
		//linea agregada por rafaias
		//o.shadow = oShadow && oShadow != null ? oShadow : o ;

		if (o.hmode  && isNaN(parseInt(o.root.style.left  ))) o.root.style.left   = "0px";
		if (o.vmode  && isNaN(parseInt(o.root.style.top   ))) o.root.style.top    = "0px";
		if (!o.hmode && isNaN(parseInt(o.root.style.right ))) o.root.style.right  = "0px";
		if (!o.vmode && isNaN(parseInt(o.root.style.bottom))) o.root.style.bottom = "0px";
		
		//las siguientes 4 lineas son agregadas por rafaias
		/*if (o.hmode  && isNaN(parseInt(o.shadow.style.left  ))) o.shadow.style.left   = "0px";
		if (o.vmode  && isNaN(parseInt(o.shadow.style.top   ))) o.shadow.style.top    = "0px";
		if (!o.hmode && isNaN(parseInt(o.shadow.style.right ))) o.shadow.style.right  = "0px";
		if (!o.vmode && isNaN(parseInt(o.shadow.style.bottom))) o.shadow.style.bottom = "0px";*/

		o.minX	= typeof minX != 'undefined' ? minX : null;
		o.minY	= typeof minY != 'undefined' ? minY : null;
		o.maxX	= typeof maxX != 'undefined' ? maxX : null;
		o.maxY	= typeof maxY != 'undefined' ? maxY : null;

		o.xMapper = fXMapper ? fXMapper : null;
		o.yMapper = fYMapper ? fYMapper : null;

		o.root.onDragStart	= new Function();
		o.root.onDragEnd	= new Function();
		o.root.onDrag		= new Function();
		
		//las siguientes 3 lineas son agregadas por rafaias
		/*o.shadow.onDragStart	= new Function();
		o.shadow.onDragEnd	= new Function();
		o.shadow.onDrag		= new Function();*/
	},

	start : function(e)
	{
		var o = Drag.obj = this;
		e = Drag.fixE(e);
		var y = parseInt(o.vmode ? o.root.style.top  : o.root.style.bottom);
		var x = parseInt(o.hmode ? o.root.style.left : o.root.style.right );
		o.root.onDragStart(x, y);

		o.lastMouseX	= e.clientX;
		o.lastMouseY	= e.clientY;

		if (o.hmode) {
			if (o.minX != null)	o.minMouseX	= e.clientX - x + o.minX;
			if (o.maxX != null)	o.maxMouseX	= o.minMouseX + o.maxX - o.minX;
		} else {
			if (o.minX != null) o.maxMouseX = -o.minX + e.clientX + x;
			if (o.maxX != null) o.minMouseX = -o.maxX + e.clientX + x;
		}

		if (o.vmode) {
			if (o.minY != null)	o.minMouseY	= e.clientY - y + o.minY;
			if (o.maxY != null)	o.maxMouseY	= o.minMouseY + o.maxY - o.minY;
		} else {
			if (o.minY != null) o.maxMouseY = -o.minY + e.clientY + y;
			if (o.maxY != null) o.minMouseY = -o.maxY + e.clientY + y;
		}

		document.onmousemove	= Drag.drag;
		document.onmouseup		= Drag.end;

		return false;
	},

	drag : function(e)
	{
		e = Drag.fixE(e);
		var o = Drag.obj;

		var ey	= e.clientY;
		var ex	= e.clientX;
		var y = parseInt(o.vmode ? o.root.style.top  : o.root.style.bottom);
		var x = parseInt(o.hmode ? o.root.style.left : o.root.style.right );
		var nx, ny;

		if (o.minX != null) ex = o.hmode ? Math.max(ex, o.minMouseX) : Math.min(ex, o.maxMouseX);
		if (o.maxX != null) ex = o.hmode ? Math.min(ex, o.maxMouseX) : Math.max(ex, o.minMouseX);
		if (o.minY != null) ey = o.vmode ? Math.max(ey, o.minMouseY) : Math.min(ey, o.maxMouseY);
		if (o.maxY != null) ey = o.vmode ? Math.min(ey, o.maxMouseY) : Math.max(ey, o.minMouseY);

		nx = x + ((ex - o.lastMouseX) * (o.hmode ? 1 : -1));
		ny = y + ((ey - o.lastMouseY) * (o.vmode ? 1 : -1));

		if (o.xMapper)		nx = o.xMapper(y)
		else if (o.yMapper)	ny = o.yMapper(x)

		Drag.obj.root.style[o.hmode ? "left" : "right"] = nx + "px";
		Drag.obj.root.style[o.vmode ? "top" : "bottom"] = ny + "px";
		//las 2 siguientes lineas fueron agregadas por Rafaias
		/*Drag.obj.shadow.style[o.hmode ? "left" : "right"] = nx-10 + "px";
		Drag.obj.shadow.style[o.vmode ? "top" : "bottom"] = ny-10 + "px";*/
		Drag.obj.lastMouseX	= ex;
		Drag.obj.lastMouseY	= ey;

		Drag.obj.root.onDrag(nx, ny);
		//la siguiente linea fue agregada por Rafaias
		/*Drag.obj.shadow.onDrag(nx-10, ny-10);*/
		return false;
	},

	end : function()
	{
		document.onmousemove = null;
		document.onmouseup   = null;
		Drag.obj.root.onDragEnd(	parseInt(Drag.obj.root.style[Drag.obj.hmode ? "left" : "right"]), 
									parseInt(Drag.obj.root.style[Drag.obj.vmode ? "top" : "bottom"]));
		Drag.obj = null;
	},

	fixE : function(e)
	{
		if (typeof e == 'undefined') e = window.event;
		if (typeof e.layerX == 'undefined') e.layerX = e.offsetX;
		if (typeof e.layerY == 'undefined') e.layerY = e.offsetY;
		return e;
	}
};





var Ventana = new Class
({
	base: null,
	idContent: '',
	//class: null,
	boton: '',
	initialize : function(e,content){
		this.boton=e.target.id;
		titulo=($(this.boton).getProperty("title"));
		$(this.boton).setProperties({
					'disabled':'true'			   
				})
		//alert($(boton).getProperty('disabled'));
		//this.idContent=$(this.boton).getProperty("alt");
		this.idContent=content;
		tabla= new Element('div', {
			   		'class':'divFondoAzulOscuro',
			   		'styles':{
						'cursor':'move',
						'padding':'0px',
						'height':'22px'
					}
			   }).adopt([
					new Element('div',{
						'class':'textoBlancoNegrita_12px',
						'styles':{
							'float':'left',
							'padding-top':'3px',
							'padding-left':'3px'
						}
					}).setText(titulo),
					new Element('div',{
						'align':'right',
						'styles':{
							'float':'right'
						}
					}).adopt(new Element('img',{
							'src'  :'img/img_x.jpg',
							'styles':{
								'cursor':'pointer'
							},
							'events':{
								'click': function(e){
									e = new Event(e);
									clase.cerrar();
									/*base = this.getParent().getParent().getParent().getParent();
									new Fx.Slide(this.getParent().getParent().getParent().getNext(),{duration: 200,onComplete: function(){
									$(idContent).setStyle('display','none');
									$(idContent).inject(document.body);
									base.remove();
									}}).toggle();
									//this.getParent().getParent().getParent().getParent().remove();
									$(boton).setProperties({
										'disabled':''			   
									})*/
									e.stop();
								}
							}
						})
					)
			]);
		this.base = new Element('div',{
		'id'   : "base" + this.idContent,
		'class':'divForm'
	   }).adopt(
	   		new Element('div', {
				'class':'divFondoAzulOscuro',
				'styles':{
					'cursor':'move'	
				}
			}).adopt(tabla));
		
		panel= new Element('div',{
					}).inject(this.base);
		
		imgLoad =	new Element('div',{
						'align':'center',
						'styles':{
							'padding':'15px'
						}
					}).adopt(
						new Element('img',{
							'src':'img/img_procesando.gif'
						})
					);
								
		imgLoad.inject(panel);
		this.base.inject(document.body);
		centrarDiv(document.getElementById(this.base.id));
		Drag.init(this.base.getFirst(), this.base);
		clase = this;
		this.cargado(this.idContent);
	},
	
	cargado : function(form){
		panel = $(this.base).getLast();
		panel.getFirst().setStyle('display','none');
		$(form).setStyle('display','').inject(panel);
	},
	
	cerrar : function(){
	//	alert(this.idContent)
		//base = this.getParent().getParent().getParent().getParent();
		a=this; //antes era variable class pero class es una palabra reservada
		
									new Fx.Slide($(this.base.id).getLast(),{duration: 200,onComplete: function(){
									a.finalizar();
									}}).toggle();
								
	},
	
	finalizar : function(){
		$(this.idContent).setStyle('display','none');
		$(this.idContent).inject(document.body);
		$(this.base.id).remove();
		$(this.boton).setProperties({
		'disabled':''			   
		})
	}/*,
	
	cancelar : function(){
		
	}*/
	
});


