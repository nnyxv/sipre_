<?php
require_once("../connections/conex.php");

session_start();
define('PAGE_PRIV','sa_historico_recepcion');

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso(PAGE_PRIV))) {
	echo "
	<script>
		alert('Acceso Denegado');
		window.location.href = 'index.php';
	</script>";
}
/* Fin Validación del Módulo */

require('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_iv_general.php");

function guardar_incidencias($form){
		$objResponse = new xajaxResponse();
		
		mysql_query("START TRANSACTION");
		
		if(isset($form['id_recepcion_incidencia'])){
			foreach($form['id_recepcion_incidencia'] as $k => $v){
				$query='';
				//verificando por accion
				if($form['rgaccion'][$k]=='a'){
					if($form['id_recepcion_incidencia'][$k]==''){
						$query=sprintf("INSERT INTO sa_recepcion_incidencia(id_cita,tipo_incidencia,url_foto,x,y) 
						VALUES (%s, '%s', '%s', '%s', '%s');",
						$form['id_cita'],
						$form['tipo'][$k],
						utf8_decode($form['url'][$k]),
						$form['x'][$k],
						$form['y'][$k]
						);
					}else{
						$query=sprintf("UPDATE sa_recepcion_incidencia SET tipo_incidencia='%s',url_foto='%s' where id_recepcion_incidencia=%s;",
						$form['tipo'][$k],
						utf8_decode($form['url'][$k]),
						$form['id_recepcion_incidencia'][$k]
						);
					}
				}else{
					if($form['id_recepcion_incidencia'][$k]!=''){
						$query=sprintf("DELETE FROM sa_recepcion_incidencia where id_recepcion_incidencia=%s;",
						$form['id_recepcion_incidencia'][$k]
						);
					}
				}
				if($query!=''){					
					$rs = mysql_query($query); 
					if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__."\n\nQuery:".$query); }					
				}
			}
		}
		
		mysql_query("COMMIT");
		$objResponse->alert("Actualizado Correctamente");
		$objResponse->script("location.reload();");
		
		
		return $objResponse;
		
	}

$xajax->register(XAJAX_FUNCTION,"guardar_incidencias");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	
        <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Historico de Recepcion</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
                
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    <link rel="stylesheet" type="text/css" href="css/sa_general.css" />
    
    <link rel="stylesheet" type="text/css" href="../js/domDragServicios.css">
    
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
    <script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    
    <script type="text/javascript" language="javascript" src="../control/lib/mouse_touch.inc.js"></script>
    <script type="text/javascript" language="javascript" src="../control/lib/main.inc.js"></script>
    

</head>

<body>


<form id="form_inventario" name="form_inventario" onsubmit="return false;" >
<input type="hidden" name="id_cita" id="id_cita" value="" />
    <div id="inv_vehiculo" style="width:360px;height:400px;float:right;">
        <img border="0" style="cursor:crosshair;" src="../img/vehiculo_vectorial.png" />
    </div>
    <div style="height:400px;overflow:none;">
        <div style="width:220px;height:400px;overflow:auto;">
            <table class="insert_table" style="width:200px;">
                <thead><tr><td class="caption" colspan="4" >Agregar Incidencias</td></tr></thead>
                <tbody id="tablag" class="tbodycenter">
                    <tr class="tdsubcaption">
                        <td colspan="2">
                            <select id="addrg" title="Haga click en el &aacute;rea de la im&aacute;gen para agregar">
                                <option value="RAYA">RAYA</option>
                                <option value="GOLPE">GOLPE</option>
                            </select>
                            <img border="0" style="margin-right:1px;" src="../img/iconos/golpe.png" />Golpe <img border="0" style="margin-right:1px;" src="../img/iconos/raya.png" />Raya
                        </td>
                    </tr>
                </tbody>
            
            </table>
        </div>
    </div>
</form>

<br />
<center>
<button id="btnGuardar" name="btnGuardar" onclick="xajax_guardar_incidencias(xajax.getFormValues('form_inventario'));" class="puntero" type="button">Guardar</button>
</center>

</body>
</html>
<script>

			var counter=0;
			var artablag = Array();
			var canceladd=false;
			var clicked = function(x,y,ox,oy,id_rg,itype,url_foto){
				if (canceladd) return;
				counter++;
				//$('#info').html('down '+x+' '+y);
				var cap=byId('inv_vehiculo');
				if(id_rg==null){
					id_rg='';
				}
				if(itype==null){
					itype=byId('addrg').value;
				}
				if(url_foto==null){
					url_foto='';
				}
				ox= mouseTouch._getOffsetLeft(byId('inv_vehiculo'));
				oy= mouseTouch._getOffsetTop(byId('inv_vehiculo'));
				
				var dx=x+ox;//mouseTouch._getOffsetLeft(cap);
				var dy=y+oy;//+mouseTouch.getVScrollWindow();//mouseTouch._getOffsetTop(cap);
				
				
				
				var newo= document.createElement('div');
				newo.setAttribute('id','idn'+counter);
				newo.setAttribute('title','Incidencia: '+counter);
				newo.style.position='absolute';
				newo.style.display='block';
				newo.style.left=(dx-5)+'px';
				newo.style.top=(dy-5)+'px';
				newo.style.width='10px';
				newo.style.height='10px';
				newo.style.cursor='pointer';
				newo.style.margin='0px';
				newo.style.border='0px';
				cap.appendChild(newo);
				
				//agregar la fila
				var tablag=byId("tablag");
				var nt = new tableRow("tablag");
				artablag[counter]=nt;
				nt.setAttribute('id','idr'+counter);
				$('#idr'+counter).bind('mouseover',function (){
					mouseoverRG(this);});
				$('#idn'+counter).bind('mouseover',function (){
					mouseoverRG(this);});
				
				$('#idr'+counter).bind('mouseout',function (){
					mouseoutRG(this);});
				$('#idn'+counter).bind('mouseout',function (){
					mouseoutRG(this);});
				$('#idn'+counter).bind('click',function (){
					viewPhotoP(this);});
					
				//celdas:
				var c1= nt.addCell();
					c1.$.className='field';
					var selectr,selectg;
					if(itype=='RAYA'){
						selectr='selected="selected"';
						newo.innerHTML='<img border="0" src="../img/iconos/raya.png"/>';
					}else{						
						selectg='selected="selected"';
						newo.innerHTML='<img border="0" src="../img/iconos/golpe.png"/>';
					}
					c1.$.innerHTML=counter+': <input type="hidden" id="x'+counter+'" name="x['+counter+']" value="'+x+'" /><input type="hidden" id="y'+counter+'" name="y['+counter+']" value="'+y+'" /><input type="hidden" id="url'+counter+'" name="url['+counter+']" value="'+url_foto+'" /><input type="hidden" id="rgaccion'+counter+'" name="rgaccion['+counter+']" value="a" /><input type="hidden" id="id_recepcion_incidencia'+counter+'" name="id_recepcion_incidencia['+counter+']" value="'+id_rg+'" /><select id="tipo'+counter+'" name="tipo['+counter+']" onchange="changeRG(this.value,'+counter+')" ><option '+selectr+' value="RAYA">RAYA</option><option '+selectg+' value="GOLPE" >GOLPE</option></select>';
				var c0= nt.addCell();
					
					c0.$.innerHTML='<button type="button" title="Ver/Agregar Foto" onclick="viewPhoto('+counter+');" ><img border="0" src="../img/iconos/photo.png" style="padding-left:2px;" /></button><button title="Quitar" onclick="removeRG('+counter+');" ><img border="0" src="../img/iconos/minus.png" style="padding-left:2px;" /></button>';
				
			}
			
			function removeRG(c){
				if (_confirm('&iquest;Desea eliminar la incidencia "'+c+'"?')){
					var row= byId('idr'+c);
					row.style.display='none';
					var punto= byId('idn'+c);
					punto.style.display='none';
					var accion= byId('rgaccion'+c);
					accion.value='d';
				}
			}
			
			function viewPhotoP(c){
				viewPhoto(c.id.substring(3));
			}
			function viewPhoto(id){
				//modalWindow('#cuadro_foto_inicidencia',300,300);
				//mejor un POPup
				setPopup('sa_inventario_foto.php?fields=url&id='+id+'&url='+byId('url'+id).value,'popup_art',{
					width:700,
					height:600,
					center:'v',
					dialog:'t',
					scrollbars:1,
					toolbar:1,
					left:0
				});
			}
			
			function mouseoverRG(c){
				canceladd=true;
				c = c.id.substring(3);
				//alert(c);
				var row= byId('idr'+c);
				var punto= byId('idn'+c);
				$('#idr'+c).addClass('mouseover');
				$('#idn'+c).addClass('immouseover');
			}
			function mouseoutRG(c){
				canceladd=false;
				c = c.id.substring(3);
				var row= byId('idr'+c);
				var punto= byId('idn'+c);
				$('#idr'+c).removeClass('mouseover');
				$('#idn'+c).removeClass('immouseover');
			}
			function changeRG(val,c){
				if(val=='RAYA'){
					byId('idn'+c).innerHTML='<img border="0" src="../img/iconos/raya.png"/>';
				}else{						
					byId('idn'+c).innerHTML='<img border="0" src="../img/iconos/golpe.png"/>';
				}
			}			
			

mouseTouch.init(byId('inv_vehiculo'),clicked,null);

</script>

<?php 

$idRecepcion = valTpDato($_GET["idRecepcion"],"int");

$query = sprintf("SELECT id_cita FROM sa_recepcion WHERE id_recepcion = %s LIMIT 1",
					$idRecepcion);
								  
$rs = mysql_query($query); 
if(!$rs) { return die(mysql_error()."\n\nLine:".__LINE__."\n\nQuery:".$query); }

$row = mysql_fetch_assoc($rs);	
$idCita = $row["id_cita"];

$query = sprintf("SELECT id_recepcion_incidencia, 
						 tipo_incidencia, 
						 url_foto, 
						 x, 
						 y 
				  FROM sa_recepcion_incidencia 
				  WHERE id_cita = %s",
				$idCita);
								  
$rs = mysql_query($query); 
if(!$rs) { return die(mysql_error()."\n\nLine:".__LINE__."\n\nQuery:".$query); }

	while($row = mysql_fetch_assoc($rs)){
                                    
		$script.=sprintf("clicked(%s,%s,null,null,%s,'%s','%s');",
		$row["x"],
		$row["y"],
		$row["id_recepcion_incidencia"],
		$row["tipo_incidencia"],
		utf8_encode($row["url_foto"])
		);
	}

$script2 = "byId('id_cita').value = ".$idCita.";";	
	
echo "<script type='text/javascript'>".$script.$script2."</script>";
?>