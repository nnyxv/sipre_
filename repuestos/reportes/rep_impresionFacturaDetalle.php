<?php
session_start();
include_once(dirname(dirname(dirname((__FILE__)))) . '/script/fpdf.php');
include_once(dirname(dirname(dirname((__FILE__)))) . '/script/FuncionesPHP.php');
include_once(dirname(dirname(dirname((__FILE__)))) . '/script/Utilidades.php');
include_once(dirname(dirname(dirname((__FILE__)))) . '/model/md_cli_solicitudesdetalles.php');
include_once(dirname(dirname(dirname((__FILE__)))) . '/model/md_cli_facturas.php');
require_once(dirname(dirname(dirname((__FILE__)))) . '/model/md_cli_parametros.php');
include_once(dirname(dirname(dirname((__FILE__)))) . '/model/md_publiccli_honorariosmedicos.php');


$cDesde1 = $_REQUEST['cDesde1'];
$cHasta1 = $_REQUEST['cHasta1'];

$objTablafacturas = new cli_facturas();
$condicionfacturas = " WHERE f.id = $cDesde1";
$retornofacturas = $objTablafacturas->buscar_datosreportes_reporte($condicionfacturas);
$rowfacturas = pg_fetch_object($retornofacturas);

$objTablaParametros = new cli_parametros();
$retornoParametros = $objTablaParametros->buscar();
$rowParametros = pg_fetch_object($retornoParametros);


$objTablasolicitudesdetalles = new cli_solicitudesdetalles();
$retornosolicitudesdetalles = $objTablasolicitudesdetalles->buscarRenglonesSolicitudesExpedientes($rowfacturas->idexpediente, " con.orden, reng.descripcion");

$objTablahornorarios = new cli_honorariosmedicos();
$fecha = new DateTime();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>Factura Detalle</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
        <style>			
            @media print{
                .botonImprimir{
                    display: none;
                }
            }
            body{

                font-family:monospace;
                font-size:8pt;

            }
            .texto{
                font-weight: bold;
            }
        </style>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    </head>
    <body style="width: 720px; margin-bottom: 10px; margin-top:0px">
        <table border="0" width="100%" cellpadding="0px">
            <thead>
                <tr>
                    <td align="right" colspan="5">
                        <button class="botonImprimir" type="button" id="btnImprimir" name="btnImprimir" style="cursor:default" onclick="window.print();">
                            <table align="center" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="10">&nbsp;</td>
                                    <td width="17"><img src="../../img/icons/printer.png" alt="print"/></td>
                                    <td width="10">&nbsp;</td>
                                    <td width="36">Imprimir</td>
                                </tr>
                            </table>
                        </button>
                    </td>
                </tr>
                <tr>
                    <td colspan="5" align="right" height='15px'></td>
                </tr>
                <tr>
                    <td colspan="5" align="right" height='15px'>
                        <?php echo " Nro. Factura: " . $rowfacturas->nrofactura; ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="5" align="right" height='15px'></td>
                </tr>
                <tr>
                    <td colspan="5" align="right"><?php echo ('Fecha: ' . $fecha->format(spanDateFormat)); ?></td>
                </tr>
                <tr>
                    <td colspan="5" align="left" height='15px'></td>
                </tr>
                <tr>
                    <td colspan="5" align="left" height='15px'></td>
                </tr>
                <tr>
                    <td colspan="3" align="left">
                        <strong><?php echo 'Paciente: ' . utf8_decode($rowfacturas->nombrepaciente); ?></strong>
                    </td>
                    <td  colspan='2'  align="right">					
                        <strong><?php echo 'CI. ' . $rowfacturas->cedula; ?></strong>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" align="left" >
                        <strong><?php echo 'Responsable: ' . utf8_decode($rowfacturas->nombreresponsable); ?></strong>
                    </td>
                    <td colspan='2' align="right">					
                        <strong><?php echo 'CI. ' . $rowfacturas->cedula_rep; ?></strong>
                    </td>

                </tr>
                <tr>
                    <td colspan="3" align="left" >
                        <strong><?php echo 'Direccion: ' . htmlentities(utf8_decode($rowfacturas->direccion_rep)); ?></strong>
                    </td>
                    <td  colspan='2' align="right">					
                        <strong><?php echo 'Admision: ' . $rowfacturas->fechaadmision; ?></strong>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" align="left" >
                        <strong><?php echo 'Telefonos: ' . utf8_decode($rowfacturas->telefonos_rep); ?></strong>
                    </td>
                    <td colspan="2" align="right">					
                        <strong><?php echo 'Egreso: ' . $rowfacturas->fechaalta; ?></strong>
                    </td>
                </tr>
                <tr>
                    <td align="left" >
                        <strong><?php echo 'Expediente Nro.: ' . $rowfacturas->idexpediente; ?></strong>
                    </td>
                    <td  colspan='4'  align="left">					
                        <strong><?php echo 'Aval/Clave: ' . $rowfacturas->nrocartaaval; ?></strong>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" align="left" >
                        <strong><?php echo 'Empresa de Seguro: ' . $rowfacturas->descseguroempresas; ?></strong>
                    </td>
                    <td colspan="2" align="right" >
                        <strong><?php echo $spanRIF.": " . $rowfacturas->rif; ?></strong>
                    </td>
                </tr>
                <tr>
                    <td colspan="5" align="left" >
                        <strong><?php echo 'Direccion: ' . htmlentities(utf8_decode($rowfacturas->direccion)); ?></strong>
                    </td>
                </tr>
                <tr>
                    <td colspan='5' align="left"  style="border-bottom: 1px dashed;">
                    </td>
                </tr>
                <tr style="height:25px;">
                    <td Class='texto' align="left" style='width:45%'>
                        Descripci&oacute;n Renglon
                    </td>
                    <td Class='texto' colspan="2" align="left" style='width:17%'>
                        Cantidad
                    </td>
                    <td Class='texto' align="right" style='width:19%'>
                        Prec/Unit. Bs.F.
                    </td>
                    <td Class='texto' align="right" style='width:19%'>
                        Total Bs.F.
                    </td>
                </tr>
                <tr>
                    <td colspan='5' align="left" style="border-top: 1px dashed;">
                    </td>
                </tr>
            </thead>	
            <tfoot>
                <tr height='90px'>
                    <td colspan='5'>

                    </td>
                </tr>
            </tfoot>
            <tbody>
                <?php
                if (pg_num_rows($retornosolicitudesdetalles) > 0) {
                    $iFila = 0;
                    $RupturaAnt = "";
                    $RupturaAnt1 = "";
                    $sumaGastos = 0.00;
                    $sumaExoneracion = 0.00;
                    while ($row = pg_fetch_object($retornosolicitudesdetalles)) {
                        $iFila++;
                        $descripcion = htmlentities(utf8_decode(substr($row->descripcion, 0, 60)));
                        if ($row->total < $row->total_agrupacion) {
                            $total = $row->total_agrupacion * $row->cantidad;
                            $precio = number_format($row->total_agrupacion, 2);
                            $sumaExoneracion+= $row->total_exonerado;
                        } else {
                            $total = $row->precio * $row->cantidad;
                            $precio = number_format($row->precio, 2);
                            $sumaExoneracion+= $row->exonerado;
                        }
                        $cantidad = $row->cantidad;
                        $descconcepto = $row->desconceptos;
                        $descunidades = htmlentities(utf8_decode($row->desunidades));
                        $sumatotal+=$total;
                        $sumaiva += $row->montoiva;

                        if ($RupturaAnt != $descconcepto) {
                            $suma = 0.00;
                            $sumaConcepto = 0.00;
                            $retornoTotalConcepto = $objTablafacturas->sumaporconceptos($rowfacturas->id_informesgastos, $row->idconceptos);
                            while ($rowTotalConcepto = pg_fetch_object($retornoTotalConcepto)) {
                                if ($rowTotalConcepto->total < $rowTotalConcepto->total_agrupacion) {
                                    $suma = $rowTotalConcepto->total_agrupacion - $row->total_exonerado;
                                } else {
                                    $suma = $rowTotalConcepto->total;
                                }
                                $sumaConcepto += $suma;
                            }

                            if ($RupturaAnt != "") {
                                
                            }
                            if ($row->idconceptos == 4) {
                                echo "<tr><td><strong>TOTAL GTOS. CLINICA</strong></td><td><strong>" . number_format($sumaGastos, 2) . "</strong></td></tr><tr><td>&nbsp;</td><td>&nbsp;</td></tr>";
                            } else {
                                $sumaGastos+= $sumaConcepto;
                            }
                            ?>		
                            <tr>
                                <td colspan='0' align="left">
                                    <strong> 
                                        <?php
                                        echo($descconcepto);
                                        $RupturaAnt = $descconcepto;
                                        ?>
                                    </strong>
                                </td>
                                <td colspan='' align="left">
                                    <strong>
                                        <?php
                                        echo(number_format($sumaConcepto, 2));
                                        $sumaConcepto = 0.00;
                                        ?>
                                    </strong>
                                </td>
                            </tr>
                            <?php
                        }
                        if ($row->idconceptos == 4) {
                            $nombrepersonal = '';
                            $condicionhonorarios = " WHERE hm.idsolicitudesdetalles=" . $row->id;
                            $resulthonorarios = $objTablahornorarios->buscarPersonalClinica($condicionhonorarios);
                            $rowhonorarios = pg_fetch_object($resulthonorarios);
                            $nombrepersonal = '(' . $rowhonorarios->apellidos . ', ' . $rowhonorarios->nombres . ')';
                            ?>
                            <tr>
                                <td align="left" style='width:45%'>
                                    <?php echo $descripcion; ?>
                                </td>
                                <td align="left" colspan='3'  style='width:36%'>
                                    <?php echo $nombrepersonal; ?>
                                </td>
                                <td align="right" style='width:19%'>
                                    <?php echo number_format($total, 2); ?>
                                </td>
                            </tr>
                        <?php } else { ?>
                            <tr>
                                <td align="left" style='width:45%'>
                                    <?php echo $descripcion; ?>
                                </td>
                                <td align="left" style='width:9%'>
                                    <?php echo $cantidad; ?>
                                </td>
                                <td style='width:8%'>
                                    <?php echo $descunidades; ?>
                                </td>
                                <td align="right" style='width:19%'>
                                    <?php echo $precio; ?>
                                </td>
                                <td align="right" style='width:19%'>
                                    <?php echo number_format($total, 2); ?>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?> 
                    <tr>
                        <td colspan='5' align="left"  style="border-bottom: 1px dashed;"> </td>
                    </tr>
                    <tr>
                        <td class='texto' colspan="4" align="right">
                            Subtotal Factura Bs.F.
                        </td>
                        <td align="right">
                            <strong><?php echo number_format($sumatotal, 2); ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td class='texto' colspan="4" align="right">
                            Menos: Total Exoneraci&oacute;n:
                        </td>
                        <td align="right">
                            <strong><?php echo number_format($sumaExoneracion, 2); ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td class='texto' colspan="4" align="right">
                            Mas: IMPTO Bs.F.
                        </td>
                        <td align="right">
                            <strong><?php echo number_format($sumaiva, 2) ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td class='texto' colspan="4" align="right">
                            Total Factura Bs.F.
                        </td>
                        <td align="right">
                            <strong><?php echo number_format($sumatotal + $sumaiva - $sumaExoneracion, 2); ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='5' align="left"  style="border-top: 1px dashed;"> </td>
                    </tr>
                    <tr>
                        <td class='texto' colspan='2' align="left" style="text-align: justify;">
                            RECIBI CONFORME:
                        </td>
                        <td>
                        </td>
                        <td class='texto' colspan='2' align="left" style="text-align: justify;">
                            ELABORADO POR:
                        </td>
                    </tr>
                    <tr>
                        <td colspan='5' height='20px'></td>
                    </tr>
                    <tr>
                        <td colspan='2' align="left">
                        </td>
                        <td style='width:50px'>
                        </td>
                        <td colspan='2' align="left">
                            <strong> 
                                <?php echo $_SESSION['nombreusuario'] . ' ' . $_SESSION['apellidousuario'] ?>
                            </strong> 
                        </td>
                    </tr>
                    <tr>
                        <td colspan='2'></td>
                        <td style='width:50px'>
                        </td>
                        <td colspan='2' align="left">
                            <strong> 
                                <?php echo $_SESSION['areasession'] ?>
                            </strong> 
                        </td>
                    </tr>
                    <tr>
                        <td colspan='5'  height='20px'></td>
                    </tr>
                    <tr>
                        <td Class='texto' align="left">
                            <?php echo "Clave: " . $rowfacturas->nrocartaaval; ?>
                        </td>
                        <td Class='texto' colspan='2' align="left">
                            <?php echo "Fecha: " . $rowfacturas->fechaadmision; ?>
                        </td>
                        <td Class='texto' colspan='2' align="right">
                            <?php echo "Cobertura: " . number_format($rowfacturas->cobertura, 2); ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>	
            </tbody>
        </table>
    </body>
</html>