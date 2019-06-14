<?php
class lista{ 
	var $varMaxReg;
	var $varNumPag;
	var $varCampOrd;
	var $varTipOrd;
	var $varCurrentPage;
	
	var $varAdj;
	
	var $varTotalRows;
	var $varTotalPages;
	
	var $varQueryString;
	
	function iniciar($maxReg, $numPag, $campOrd, $tipOrd, $currentPage, $adj) {
		$this->varMaxReg = $maxReg;
		$this->varNumPag = $numPag;
		$this->varCampOrd = $campOrd;
		$this->varTipOrd = $tipOrd;
		$this->varAdj = $adj;
		
		$this->varCurrentPage = $currentPage;
	}
	
	function consulta($database_conex, $conex, $query) {
		if (isset($_GET['numPag'.$this->varAdj])) {
			$this->varNumPag = $_GET['numPag'.$this->varAdj];
		}
		$startRow = $this->varNumPag * $this->varMaxReg;
		
		if (isset($_GET['txtCampo'.$this->varAdj]) && isset($_GET['txtOrden'.$this->varAdj])) {
			$query .= sprintf(" ORDER BY %s %s", $_GET['txtCampo'.$this->varAdj], $_GET['txtOrden'.$this->varAdj]);
			$this->varCampOrd = $_GET['txtCampo'.$this->varAdj];
			$this->varTipOrd = $_GET['txtOrden'.$this->varAdj];
		} else {
			$query .= sprintf(" ORDER BY %s %s", $this->varCampOrd, $this->varTipOrd);
		}
		$query_limit = sprintf("%s LIMIT %d, %d", $query, $startRow, $this->varMaxReg);
		
		$rs = mysql_query($query_limit, $conex) or die(mysql_error());
		
		if (isset($_GET['totalRows'.$this->varAdj])) {
			$this->varTotalRows = $_GET['totalRows'.$this->varAdj];
		} else { 
			$all = mysql_query($query);
			$this->varTotalRows = mysql_num_rows($all);
		}
		$this->varTotalPages = ceil($this->varTotalRows/$this->varMaxReg)-1;
		
		$this->varQueryString = "";
		if (!empty($_SERVER['QUERY_STRING'])) {
			$params = explode("&", $_SERVER['QUERY_STRING']);
			$newParams = array();
			foreach ($params as $param) {
				if (stristr($param, "numPag".$this->varAdj) == false && stristr($param, "totalRows".$this->varAdj) == false
				 && stristr($param, "txtCampo".$this->varAdj) == false && stristr($param, "txtOrden".$this->varAdj) == false) {
					array_push($newParams, $param);
				}
			}
			if (count($newParams) != 0) {
				$this->varQueryString = "&" . utf8_encode(implode("&", $newParams));							
			}
		}
		$this->varQueryString = sprintf("&totalRows".$this->varAdj."=%d%s", $this->varTotalRows, $this->varQueryString);
		
		return array($rs);
	}
	
	function tabla($campos, $rs, $acciones) {
		$html = "<table border=\"0\" width=\"100%\">";
		$html .= "<tr class=\"tituloCol\">";
		
		$contCampos = 0;
		foreach($campos as $indice=>$valor) {
			$contCampos ++;
			if ($this->varCampOrd == $campos[$indice][2])
				$imgOrden = ($this->varTipOrd == "ASC") ? "<img src=\"../img/iconos/ico_ordArriba.gif\">" : "<img src=\"../img/iconos/ico_ordAbajo.gif\">";
			else
				$imgOrden = "";
				
			$html .= "<td width=\"".$campos[$indice][1]."\"><table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\"><tr><td align=\"left\">".$this->ordTitulo($campos[$indice][2], $campos[$indice][0])."</td><td width=\"9\">".$imgOrden."</td></tr></table></td>";
		}
		
		$contAcciones = 0;
		foreach($acciones as $indiceAcc=>$valorAcc) {
			$contAcciones ++;
		}
		$html .= "<td colspan=\"".$contAcciones."\"></td>";
			
		$html .= "</tr>";
		
		$contFila = 0;
		$styleFila = "colorFila1";
		while ($row = mysql_fetch_assoc($rs)) {
			$contFila ++;
			
			$html .= "<tr class=\"".$styleFila."\" onmouseover=\"this.className='colorFilaSobre';\" onmouseout=\"this.className='".$styleFila."';\">";
				
				foreach($campos as $indice=>$valor) {
					if ($campos[$indice][4] == "checkbox")
						$html .= "<td><input id='".$campos[$indice][5]."' name='".$campos[$indice][5]."[]' type='checkbox' value='".$row[$campos[$indice][2]]."' /></td>";
					else
						$html .= "<td align=\"".$campos[$indice][3]."\">".utf8_encode($row[$campos[$indice][2]])."</td>";
				}
				
				foreach($acciones as $indiceAcc=>$valorAcc) {
					$contCampos ++;
					$cadena = "";
					for($contPos = 0; $contPos <= strlen($acciones[$indiceAcc][1]); $contPos++) {
						$letra = substr($acciones[$indiceAcc][1],$contPos,1);
						if ($letra == "|") {
							if ($prim == 1) {
								$cadena .= $row[$campo];
								$prim = "";
								$campo = "";
							} else
								$prim = 1;
						}
						
						if ($prim != 1 && $letra != "|")
							$cadena .= $letra;
						else if ($letra != "|")
							$campo .= $letra;
					}
					
					$html .= sprintf("<td><a %s=\"%s\"><img border=\"0\" class=\"puntero\" src=\"%s\"></a></td>", $acciones[$indiceAcc][2], $cadena, $acciones[$indiceAcc][0]);
				}
			$html .= "</tr>";
			
			$styleFila = ($contFila % 2 == 0) ? "colorFila1" : "colorFila2";
		}
		
		$html .= "<tr class=\"pieCol\">";
	    	$html .= "<td align=\"center\" colspan=\"".($contCampos+$contAcciones)."\">";
				$html .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$html .= "<tr>";
					$html .= "<td align=\"center\" width=\"50%\">
								<input type=\"hidden\" id=\"txtCampo".$this->varAdj."\" name=\"txtCampo".$this->varAdj."\" size=\"6\" value=\"".$this->varCampOrd."\">
								<input type=\"hidden\" id=\"txtOrden".$this->varAdj."\" name=\"txtOrden".$this->varAdj."\" size=\"6\" value=\"".$this->varTipOrd."\">
								
								<input type=\"hidden\" id=\"txtPagina".$this->varAdj."\" name=\"txtPagina".$this->varAdj."\" size=\"6\" value=\"".$this->varNumPag."\">
								<input type=\"hidden\" id=\"txtRegistros".$this->varAdj."\" name=\"txtRegistros".$this->varAdj."\" size=\"6\" value=\"".$this->varMaxReg."\">
							</td>";
					$html .= "<td align=\"center\">";
						$html .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"152px\">";
						$html .= "<tr align=\"center\">";
							$this->varTipOrd = ($this->varTipOrd == "ASC")? "DESC" : "ASC";
							$html .= "<td width=\"30px\">".$this->pagPri()."</td>";
							$html .= "<td width=\"24px\">".$this->pagAnt()."</td>";
							$html .= "<td width=\"44px\">".$this->cboPag()."</td>";
							$html .= "<td width=\"24px\">".$this->pagSig()."</td>";
							$html .= "<td width=\"30px\">".$this->pagUlt()."</td>";
						$html .= "</tr>";
						$html .= "</table>";
					$html .= "</td>";
					$html .= "<td align=\"center\" width=\"50%\">";
						$html .= "Mostrando <b>".$contFila."</b> Registros de un total de <b>".$this->varTotalRows."</b>";
					$html .= "</td>";
				$html .= "</tr>";
				$html .= "</table>";
			$html .= "</td>";
		$html .= "</tr>";
		$html .= "</table>";
		
		mysql_free_result($rs);
		
		return $html;
	}
	
	function ordTitulo($campo, $texto) {
		$this->varTipOrd = "ASC";
		if ($_GET['txtCampo'.$this->varAdj] == $this->varCampOrd) {
			if ($_GET['txtOrden'.$this->varAdj] == "ASC")
				$this->varTipOrd = "DESC";
		}
		return sprintf("<a href=\"%s?numPag".$this->varAdj."=%d%s&txtCampo".$this->varAdj."=%s&txtOrden".$this->varAdj."=%s\"> %s </a>", $this->varCurrentPage, $this->varNumPag, $this->varQueryString, $campo, $this->varTipOrd, $texto);
	}
	
	function pagPri() {
		if ($this->varNumPag > 0)
			return sprintf("<a href=\"%s?numPag".$this->varAdj."=%d%s&txtCampo".$this->varAdj."=%s&txtOrden".$this->varAdj."=%s\"> << </a>", $this->varCurrentPage, 0, $this->varQueryString, $this->varCampOrd, $this->varTipOrd);
	}
	
	function pagAnt() {
		if ($this->varNumPag > 0)
			return sprintf("<a href=\"%s?numPag".$this->varAdj."=%d%s&txtCampo".$this->varAdj."=%s&txtOrden".$this->varAdj."=%s\"> < </a>", $this->varCurrentPage, max(0, $this->varNumPag - 1), $this->varQueryString, $this->varCampOrd, $this->varTipOrd);
	}
	
	function cboPag() {
		$htmlTf .= sprintf("<select id=\"numPag".$this->varAdj."\" name=\"numPag".$this->varAdj."\" onchange=\"window.open('%s?numPag".$this->varAdj."='+this.value+'%s&txtCampo".$this->varAdj."=%s&txtOrden".$this->varAdj."=%s', '_self')\">",
			$this->varCurrentPage, $this->varQueryString, $this->varCampOrd, $this->varTipOrd);
		for ($nroPag = 0; $nroPag <= $this->varTotalPages; $nroPag++) {
				$htmlTf.="<option value=\"".$nroPag."\"";
				if ($this->varNumPag == $nroPag)
					$htmlTf.="selected=\"selected\"";
				$htmlTf.= ">".($nroPag + 1)." / ".($this->varTotalPages + 1)."</option>";
		}
		$htmlTf .= "</select>";
		
		return $htmlTf;
	}
	
	function pagSig() {
		if ($this->varNumPag < $this->varTotalPages)
			return sprintf("<a href=\"%s?numPag".$this->varAdj."=%d%s&txtCampo".$this->varAdj."=%s&txtOrden".$this->varAdj."=%s\"> > </a>", $this->varCurrentPage, min($this->varTotalPages, $this->varNumPag + 1), $this->varQueryString, $this->varCampOrd, $this->varTipOrd);
	}
	
	function pagUlt() {
		if ($this->varNumPag < $this->varTotalPages)
			return sprintf("<a href=\"%s?numPag".$this->varAdj."=%d%s&txtCampo".$this->varAdj."=%s&txtOrden".$this->varAdj."=%s\"> >> </a>", $this->varCurrentPage, $this->varTotalPages, $this->varQueryString, $this->varCampOrd, $this->varTipOrd);
	}
}
?>