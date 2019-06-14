<?php
require_once "../connections/conex.php";

@session_start();

// procesando ajax:
cache_expires();//reputacionCliente

//////////////// LISTADO CONCEPTOS ////////////////
if (isset($_GET['ajax_textConcepto'])) {
	$nom_concepto = trim(excape($_GET['ajax_textConcepto']));
	$nom_concepto = str_replace("#","",$nom_concepto);
	$nom_concepto = str_replace("--","",$nom_concepto);
	
	if ($nom_concepto != "") {
		conectar();
		$sql = sprintf("SELECT * FROM if_absorcion_conceptos
							WHERE nom_concepto LIKE %s;",
					valTpDato($nom_concepto."%", "text"));
		$r = mysql_query($sql, $conex);
		if (!$r) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRows = mysql_num_rows($r);
		
		if ($totalRows > 0) {
			echo "<table border=\"0\" class='form-4' style=\"border-collapse:collapse;\" width=\"96%\">";
			echo "<tr class=\"tituloCampo\">";
				echo "<td align=\"right\" class=\"textoNegrita_10px\" width=\"100%\">Mostrando ".$totalRows." de ".$totalRows." Registros&nbsp;</td>";
				echo "<td><a href=\"javascript:cancelarCliente();\"><img border=\"0\" src=\"../img/iconos/cross.png\" alt=\"Cerrar\"/></a></td>";
			echo "</tr>";
			echo "<tr class=\"tituloCampo\">";
				echo "<td colspan='2' align='center'><h3>Lista de Conceptos ya Registrados</h3></td>";
			echo "</tr>";
			echo "</table>";
			
			echo "<div id=\"overConceptos\" class=\"overflowlist\">";
			
				echo "<table border=\"0\" class=\"form-4\" width=\"96%\">";
				echo "<tr align=\"center\" class=\"tituloColumna\">";
					echo "<td width=\"10%\">"."Id"."</td>";
					echo "<td width=\"55%\">"."Nombre Concepto"."</td>";
					echo "<td width=\"15%\">"."Tipo de Gasto"."</td>";
				echo "</tr>";
				while ($row = mysql_fetch_array($r)) {
					$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
					$contFila ++;
					
					if($row['tipo'] == 1){
						$tipoVenta = "VENTAS";
					} elseif ($row['tipo'] == 2){
						$tipoVenta = "POSTVENTA";
					}else{
						$tipoVenta = "GENERALES";
					}
					
					echo "<tr class=\"".$clase."\" height=\"24\">";
						echo "<td align=\"right\">".$row['id_concepto']."</td>";
						echo "<td align=\"left\">".utf8_encode($row['nom_concepto'])."</td>";
						echo "<td align=\"left\">".utf8_encode($tipoVenta)."</td>";
					echo "</tr>";
				}
				echo "</table>";
			
			echo "</div>";
		}
		cerrar();
	}
	exit;
}

//////////////// LISTADO TIPO DE CUENTA ////////////////
if (isset($_GET['ajax_textTipoCuenta'])) {
	$nom_cuenta = trim(excape($_GET['ajax_textTipoCuenta']));
	$nom_cuenta = str_replace("#","",$nom_cuenta);
	$nom_cuenta = str_replace("--","",$nom_cuenta);

	if ($nom_cuenta != "") {
		conectar();
		$sql = sprintf("SELECT * FROM if_absorcion_tipos_cuenta
							WHERE nombre_cuenta LIKE %s;",
				valTpDato($nom_cuenta."%", "text"));
	
		$r = mysql_query($sql, $conex);
		if (!$r) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRows = mysql_num_rows($r);

		if ($totalRows > 0) {
			echo "<table border=\"0\" class='form-4' style=\"border-collapse:collapse;\" width=\"50%\">";
				echo "<tr class=\"tituloCampo\">";
					echo "<td align=\"right\" class=\"textoNegrita_10px\" width=\"100%\">Mostrando ".$totalRows." de ".$totalRows." Registros&nbsp;</td>";
					echo "<td><a href=\"javascript:cancelarTipoCuenta();\"><img border=\"0\" src=\"../img/iconos/cross.png\" alt=\"Cerrar\"/></a></td>";
				echo "</tr>";
				echo "<tr class=\"tituloCampo\">";
					echo "<td colspan='2' align='center'><h3>Lista de Tipos de Cuenta Registrados</h3></td>";
				echo "</tr>";
			echo "</table>";
				
			echo "<div id=\"overTipoCuenta\" class=\"overflowlistTipoCuenta\">";
				echo "<table border=\"0\" class=\"form-4\" width=\"50%\">";
					echo "<tr align=\"center\" class=\"tituloColumna\">";
						echo "<td width=\"15%\">"."Código"."</td>";
						echo "<td width=\"40%\">"."Nombre de Cuenta"."</td>";
					echo "</tr>";
					while ($row = mysql_fetch_array($r)) {
						$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
						$contFila ++;
							
						echo "<tr class=\"".$clase."\" height=\"24\">";
							echo "<td align=\"right\">".utf8_encode($row['numero_identificador'])."</td>";
							echo "<td align=\"left\">&nbsp;&nbsp;".utf8_encode($row['nombre_cuenta'])."</td>";
						echo "</tr>";
					}
				echo "</table>";
			echo "</div>";
		}
		cerrar();
	}
	exit;
}
?>