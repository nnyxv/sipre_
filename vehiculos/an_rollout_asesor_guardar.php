<?php
require_once("../connections/conex.php");

require_once('../inc_sesion.php');
validaModulo('an_rollout_asesor',editar);

//echo var_dump($_POST);
//conectando
conectar();
$id_uni_bas = getmysqlnum($_POST['id_uni_bas']);
$ano = getmysqlnum($_POST['ano']);
$asesor = $_POST['asesor'];
$objetivo = $_POST['objetivo'];
$id_rollout_asesor = $_POST['id_rollout_asesor'];
iniciotransaccion();

//recorre por asesores
foreach($asesor as $iasesor) {
	//recorre por meses
	for($contMes = 1; $contMes <= 12; $contMes++) {
		//verifica si tiene id
		$id = $id_rollout_asesor[$iasesor][$contMes];
		$vobjetivo = getmysqlnum($objetivo[$iasesor][$contMes]);
		if ($id == "") {
			//insertar
			$sql = sprintf("INSERT INTO an_rollout_asesor(id_uni_bas, id_empleado, ano, mes, objetivo)
			VALUES(%s, %s, %s, %s, %s);",
				$id_uni_bas,
				$iasesor,
				$ano,
				$contMes,
				$vobjetivo);
		} else {
			//actualizar
			$sql = sprintf("UPDATE an_rollout_asesor SET
				objetivo = %s
			WHERE id_rollout_asesor = %s;",
				$vobjetivo,
				$id);
		}
		$result = @mysql_query($sql, $conex);
		if (!$result) { rollback(); die(mysql_error($conex)."<br>Error Nro: ".mysql_errno($conex)."<br>Line: ".__LINE__); }
	}	
}	
fintransaccion();
cerrar();
?>
<script type="text/javascript" src="vehiculos.inc.js"></script>
<script type="text/javascript" language="javascript">
//window.location.href="an_rollout_asesor.php?unidad=<?php echo $id_uni_bas ?>&a=<?php echo $ano ?>";
window.parent.recarga();
utf8alert("Roll-out almacenado con &Eacute;xito");
</script>