<?php session_start();
include_once('FuncionesPHP.php');
$conAd = ConectarBDAd();
$SqlStr = "Select a.codigo,a.descripcion from sipre_co_config.company 
a left join sipre_co_config.comcerrada b 
on a.codigo = b.codigo and mes = $Mes and ano = $Ano
where b.codigo is null and a.codigo <> 'sipre_contabilidad' and a.codigo <> 'BASEPRUEBA'";
$exc = EjecutarExecAd($conAd,$SqlStr) or die($SqlStr);

if ($_REQUEST['Mes'] == ""){
echo" No Cerradas  <select class='x-combo-list-hd' name='T_Company'>
 <option value='' selected>Seleccione</option>
 </select>";
}else{
	echo"No Cerradas  <select class='x-combo-list-hd' name='T_Company' onclick='ExtraerIn();'>";
	 echo" <option value='' selected>Seleccione</option>";
		 while ($row=ObtenerFetch($exc)){ 
		echo " <option value=$row[0]>$row[1]</option>";
		}	 
		echo "</select> 
		<input name='BtnCerrar' type='button' maxlength=23 size=10 onClick='CerrarEstado();' value='Cerrar'> 		
		";
}


?>