<!-- Listado de Vendedores -->
<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
<form id="frmSeguimiento" name="frmSeguimiento" style="margin:0" onsubmit="return false;">
    <div id="tblProspecto" class="pane" style="max-height:500px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr>
            <td>
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td height="12px" align="right" colspan="4" id="tituloLogUp" class="tituloPaginaCrm">Lista de Vendedores</td>
                </tr>
                <tr>
                    <td>
                        <div id="tblListVendedor">
                            <table border="0" align="center" width="100%">
                            <tr align="left">
                                <td>
                                    <table align="right" border="0">
                                    <tr align="left">
                                        <td align='right' class='tituloCampo' width='120'>Tipo de Equipo:</td>
                                        <td id='tdTipoEquipos'></td>
                                        <td align="right" class="tituloCampo" width="120">Equipos:</td>
                                        <td id="tdListEquipo">
                                            <select class="inputHabilitado">
                                               <option value="">[ Seleccione ]</option>
                                            </select>
                                        </td>
                                    </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                <fieldset><legend class="legend">Integrante Del Equipo</legend>
                                    <table border="0" width="100%">
                                    <tr align="center" class="tituloColumna">
                                        <td width="4%"></td>
                                        <td></td>
                                        <td width="8%">Id</td>
                                        <td width="32%">Nombre Vendedor</td>
                                        <td width="28%">Cargo</td>
                                        <td width="28%">Departamento</td>
                                        <td></td>
                                    </tr>
                                    <tr id="trItmIntegrante"></tr>
                                    </table>
                                </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6">
                                    <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%"> 
                                    <tr>
                                        <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                                        <td align="center">
                                            <table>
                                            <tr>
                                                <td><img src="../img/iconos/user_suit.png" /></td><td>Jefe de Equipo</td>
                                            </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td align="right" colspan="6"><hr>
                                    <button type="button" id="btnAsigVendedor" name="btnAsigVendedor" onclick="showView('listIngreso', true);"> Siguiente >></button>
                                    <button type="button" id="btnCancelarAsig" name="btnCancelarAsig" class="close">Cancelar</button>
                                </td>
                            </tr>
                            </table>
                        </div>
                        
                        <div id="tblModoIngreso" style="display:none;">
                            <table border="0" width="100%">
                            <tr>
                                <td id="divModoIngreso"></td>
                            </tr>
                            <tr>
                                <td align="right" colspan="6"><hr>
                                    <button type="button" id="btnAtrasListVend" name="btnAtrasListVend" onclick="showView('listVendedor', false);"> << Atras </button>
                                    <button type="button" id="btnAsigDealer" name="btnAsigDealer" onclick="showView('listProspecto', true);"> Siguiente >> </button>
                                    <button type="button" id="btnCancelarAsigDealer" name="btnCancelarAsigDealer" class="close">Cancelar</button>
                                </td>
                            </tr>
                            </table>
                        </div>
                        
                        <div id="tblListProspecto" style="display:none;">
                            <table border="0" width="100%">
                            <tr>
                                <td>
                                <fieldset id="datosProsClien"><legend class="legend">Datos del Prospecto / Cliente</legend>
                                    <table border="0" width="100%">
                                    <tr>
                                        <td colspan="7" align="left"> 
                                            <button type="button" id="btnNuevoProspecto" name="btnNuevoProspecto" onclick="btnOcultarDatos('show'); byId('btnCerrarDivFlotante7').click();"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                                            
                                            <button type="button" id="rdProspecto" name="rdTipo" rel="#divFlotante2" value="1">
                                                <table cellspacing="0" cellpadding="0" align="center">
                                                <tr>
                                                    <td><img title="Editar" src="../img/iconos/people1.png" class="puntero"></td>
                                                    <td>&nbsp;</td>
                                                    <td>Lista Prospecto</td>
                                                </tr>
                                                </table>
                                            </button>
                                            
                                            <button type="button" id="rdCliente" name="rdTipo" rel="#divFlotante2" value="3">
                                                <table cellspacing="0" cellpadding="0" align="center">
                                                <tr>
                                                    <td><img title="Editar" src="../img/iconos/ico_cliente.gif" class="puntero"></td>
                                                    <td>&nbsp;</td>
                                                    <td>Lista Cliente</td>
                                                </tr>
                                                </table>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td height="18px"></td>
                                    </tr>
                                    <tr align="left">
                                        <td align="right" class="tituloCampo" width="100">Nombre:</td>
                                        <td>
                                            <input type="text" id="txtValNombreProspecto" name="txtValNombreProspecto" class="inputHabilitado" maxlength="50"/>
                                        </td>
                                        <td align="right" class="tituloCampo" width="100">Apellido:</td>
                                        <td>
                                            <input type="text" id="txtValApellidoProspecto" name="txtValApellidoProspecto" class="inputHabilitado" maxlength="50"/>
                                        </td>
                                        <td align="right" class="tituloCampo" width="100">Tel&eacute;fono:</td>
                                        <td>
                                            <div style="float:left">
                                                <input type="text" id="txtValTelefonoProspecto" name="txtValTelefonoProspecto" class="inputHabilitado" style="text-align:center"/>
                                            </div>
                                            <div style="float:left">
                                                <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                                            </div>
                                        </td>
                                        <td align="right">
                                            <div id="btnProspClient"><button type="button" id="btnBuscarProspecto" name="btnBuscarProspecto" onclick="xajax_buscarProspectoCliente(xajax.getFormValues('frmSeguimiento'), xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/find.png"/></td><td>&nbsp;</td><td>Buscar</td></tr></table></button></div>
                                            
                                            <div id="btnClient" style="display: none;"><button type="button" id="btnBuscarProspecto" name="btnBuscarProspecto" onclick="xajax_buscarProspectoCliente(xajax.getFormValues('frmSeguimiento'), xajax.getFormValues('frmBuscar'), 'cliente');"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/find.png"/></td><td>&nbsp;</td><td>Buscar</td></tr></table></button></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right" colspan="8"><hr>
                                            <button type="button" id="btnAtrasListIngr" name="btnAtrasListIngr" onclick="showView('listIngreso', false);"> << Atras </button>
                                            <button type="button" id="btnCancelarDatosAdicionales" name="btnCancelarDatosAdicionales" class="close">Cancelar</button>
                                        </td>
                                    </tr>
                                    </table>
                                </fieldset>
                                
                                    <div id="datosProspecto" style="display:none;">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                        <tr>
                                            <td>
                                                <table border="0" width="100%">
                                                <tr align="left">
                                                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Empresa:</td>
                                                    <td width="70%">
                                                        <table cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td><input type="text" class="" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresaUsuario(this.value, 'Empresa', 'ListaEmpresa', '', 'false');" size="6" style="text-align:right;"/></td>
                                                            <td>
                                                                <a class="modalImg" id="aListarEmpresa" rel="#divFlotante4" onclick="abrirFrom(this,'frmBusEmpresa','tdFlotanteTitulo4', '', 'tblListEmpresa')">
                                                                    <button id="btnAsigEmp" name="btnAsigEmp" type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                                                </a>
                                                            </td>
                                                            <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                                                        </tr>
                                                        </table>
                                                    </td>
                                                    <td align="right" width="18%">
                                                        <div id="divBuscarProspecto">
                                                            <button type="button" id="btnBuscarProspecto" name="btnBuscarProspecto" onclick="btnOcultarDatos('hide');"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/find.png"/></td><td>&nbsp;</td><td>Buscar Nuevo</td></tr></table></button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr align="left">
                                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empleado:</td>
                                                    <td>
                                                        <table cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td><input type="text" id="hddIdEmpleado" name="hddIdEmpleado" readonly="readonly" size="6" style="text-align:right"/></td>
                                                            <td>
                                                                <a class="modalImg" id="aListarEmpleado" rel="#divFlotante5" onclick="abrirFrom(this,'frmBuscarEmpleado','tdFlotanteTitulo5', '', 'tblListEmpleado')">
                                                                    <button id="btnLstEmpleado" name="btnLstEmpleado" type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                                                </a>
                                                            </td>
                                                            <td><input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="45"/></td>
                                                        </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr align="left">
                                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo de Control de Trafico:</td>
                                                    <td> 
                                                        <label><input type="radio" id="rdCliente2" value="3" name="rdCliente" disabled="disabled"/> Cliente</label>
                                                        &nbsp;
                                                        <label><input type="radio" id="rdProspecto2" value="1" name="rdProspecto" disabled="disabled"/> Prospecto</label>
                                                    </td>
                                                </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                            <fieldset><legend class="legend">Datos Generales</legend>
                                                <table border="0" width="100%">
                                                <tr>
                                                    <td width="11%"></td>
                                                    <td width="23%"></td>
                                                    <td width="11%"></td>
                                                    <td width="23%"></td>
                                                    <td width="11%"></td>
                                                    <td width="21%"></td>
                                                </tr>
                                                <tr id="trCedulaProspecto" align="left">
                                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo:</td>
                                                    <td>
                                                        <select id="lstTipoProspecto" name="lstTipoProspecto" style="width:99%">
                                                            <option value="-1">[ Seleccione ]</option>
                                                            <option value="1">Natural</option>
                                                            <option value="2">Juridico</option>
                                                        </select>
                                                    </td>
                                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanClienteCxC; ?>:</td>
                                                    <td nowrap="nowrap">
                                                    <div style="float:left">
                                                        <input type="text" id="txtCedulaProspecto" name="txtCedulaProspecto" maxlength="18" size="20" style="text-align:center"/>
                                                    </div>
                                                    <div style="float:left">
                                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoCI; ?>"/>
                                                    </div>
                                                    </td>
                                                    <td align="right" class="tituloCampo">Fecha Nacimiento:</td>
                                                    <td><input type="text" id="txtFechaNacimiento" name="txtFechaNacimiento" size="12" style="text-align:center"/></td>
                                                </tr>
                                                <tr align="left">
                                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nombre(s):</td>
                                                    <td><input type="text"  id="txtNombreProspecto"name="txtNombreProspecto" size="25" maxlength="50"/></td>
                                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Apellido(s):</td>
                                                    <td><input type="text" id="txtApellidoProspecto" name="txtApellidoProspecto" size="25" maxlength="50"/></td>
                                                    <td align="right" class="tituloCampo">Licencia:</td>
                                                    <td><input type="text" id="txtLicenciaProspecto" name="txtLicenciaProspecto" maxlength="18" size="20" style="text-align:center"/></td>
                                                </tr>
                                                </table>
                                            </fieldset>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <table border="0" width="100%">
                                                <tr>
                                                    <td valign="top">
                                                    <fieldset><legend class="legend">Dirección</legend>
                                                        <div class="wrap">
                                                            <!-- the tabs -->
                                                            <ul class="tabs">
                                                                <li><a href="#">Residencial</a></li>
                                                                <li><a href="#">Postal</a></li>
                                                                <li><a href="#">Trabajo</a></li>
                                                            </ul>
                                                            
                                                            <!-- tab "panes" -->
                                                            <div class="pane">
                                                                <table border="0" width="100%">
                                                                <tr align="left">
                                                                    <td align="right" class="tituloCampo" width="12%"><?php echo $spanUrbanizacion; ?>:</td>
                                                                    <td width="21%"><input type="text" name="txtUrbanizacionProspecto" id="txtUrbanizacionProspecto" style="width:99%"/></td>
                                                                    <td align="right" class="tituloCampo" width="12%"><?php echo utf8_encode($spanCalleAv); ?>:</td>
                                                                    <td width="22%"><input type="text" name="txtCalleProspecto" id="txtCalleProspecto" style="width:99%"/></td>
                                                                    <td align="right" class="tituloCampo" width="12%"><?php echo utf8_encode($spanCasaEdif); ?>:</td>
                                                                    <td width="21%"><input type="text" name="txtCasaProspecto" id="txtCasaProspecto" style="width:99%"/></td>
                                                                </tr>
                                                                <tr align="left">
                                                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo utf8_encode($spanMunicipio); ?>:</td>
                                                                    <td><input type="text" name="txtMunicipioProspecto" id="txtMunicipioProspecto" style="width:99%"/></td>
                                                                    <td align="right" class="tituloCampo"><?php echo utf8_encode($spanCiudad); ?>:</td>
                                                                    <td><input type="text" name="txtCiudadProspecto" id="txtCiudadProspecto" style="width:99%"/></td>
                                                                    <td align="right" class="tituloCampo"><?php echo utf8_encode($spanEstado); ?>:</td>
                                                                    <td><input type="text" name="txtEstadoProspecto" id="txtEstadoProspecto" style="width:99%"/></td>
                                                                </tr>
                                                                <tr align="left">
                                                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Teléfono:</td>
                                                                    <td>
                                                                    <div style="float:left">
                                                                        <input type="text" name="txtTelefonoProspecto" id="txtTelefonoProspecto" size="16" style="text-align:center"/>
                                                                    </div>
                                                                    <div style="float:left">
                                                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                                                                    </div>
                                                                    </td>
                                                                    <td align="right" class="tituloCampo">Otro Telf.:</td>
                                                                    <td>
                                                                    <div style="float:left">
                                                                        <input type="text" name="txtOtroTelefonoProspecto" id="txtOtroTelefonoProspecto" size="16" style="text-align:center"/>
                                                                    </div>
                                                                    <div style="float:left">
                                                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                                                                    </div>
                                                                    </td>
                                                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanEmail; ?>:</td>
                                                                    <td>
                                                                    <div style="float:left">
                                                                        <input type="text" name="txtCorreoProspecto" id="txtCorreoProspecto" maxlength="50" style="width:99%"/>
                                                                    </div>
                                                                    <div style="float:left">
                                                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoCorreo; ?>"/>
                                                                    </div>
                                                                    </td>
                                                                </tr>
                                                                </table>
                                                            </div>
                                                            
                                                            <div class="pane">
                                                                <table border="0" width="100%">
                                                                <tr align="left">
                                                                    <td align="right" class="tituloCampo" width="12%"><?php echo $spanUrbanizacion; ?>:</td>
                                                                    <td width="21%"><input type="text" name="txtUrbanizacionPostalProspecto" id="txtUrbanizacionPostalProspecto" style="width:99%"/></td>
                                                                    <td align="right" class="tituloCampo" width="12%"><?php echo utf8_encode($spanCalleAv); ?>:</td>
                                                                    <td width="22%"><input type="text" name="txtCallePostalProspecto" id="txtCallePostalProspecto" style="width:99%"/></td>
                                                                    <td align="right" class="tituloCampo" width="12%"><?php echo utf8_encode($spanCasaEdif); ?>:</td>
                                                                    <td width="21%"><input type="text" name="txtCasaPostalProspecto" id="txtCasaPostalProspecto" style="width:99%"/></td>
                                                                </tr>
                                                                <tr align="left">
                                                                    <td align="right" class="tituloCampo"><?php echo utf8_encode($spanMunicipio); ?>:</td>
                                                                    <td><input type="text" name="txtMunicipioPostalProspecto" id="txtMunicipioPostalProspecto" style="width:99%"/></td>
                                                                    <td align="right" class="tituloCampo"><?php echo utf8_encode($spanCiudad); ?>:</td>
                                                                    <td><input type="text" name="txtCiudadPostalProspecto" id="txtCiudadPostalProspecto" style="width:99%"/></td>
                                                                    <td align="right" class="tituloCampo"><?php echo utf8_encode($spanEstado); ?>:</td>
                                                                    <td><input type="text" name="txtEstadoPostalProspecto" id="txtEstadoPostalProspecto" style="width:99%"/></td>
                                                                </tr>
                                                                </table>
                                                            </div>
                                                            
                                                            <div class="pane">
                                                                <table border="0" width="100%">
                                                                <tr align="left">
                                                                    <td align="right" class="tituloCampo" width="12%"><?php echo $spanUrbanizacion; ?>:</td>
                                                                    <td width="21%"><input type="text" name="txtUrbanizacionComp" id="txtUrbanizacionComp" style="width:99%"/></td>
                                                                    <td align="right" class="tituloCampo" width="12%"><?php echo utf8_encode($spanCalleAv); ?>:</td>
                                                                    <td width="22%"><input type="text" name="txtCalleComp" id="txtCalleComp" style="width:99%"/></td>
                                                                    <td align="right" class="tituloCampo" width="12%"><?php echo utf8_encode($spanCasaEdif); ?>:</td>
                                                                    <td width="21%"><input type="text" name="txtCasaComp" id="txtCasaComp" style="width:99%"/></td>
                                                                </tr>
                                                                <tr align="left">
                                                                    <td align="right" class="tituloCampo"><?php echo utf8_encode($spanMunicipio); ?>:</td>
                                                                    <td><input type="text" name="txtMunicipioComp" id="txtMunicipioComp" style="width:99%"/></td>
                                                                    <td align="right" class="tituloCampo"><?php echo $spanEstado; ?>:</td>
                                                                    <td><input type="text" name="txtEstadoComp" id="txtEstadoComp" style="width:99%"/></td>
                                                                </tr>
                                                                <tr align="left">
                                                                    <td align="right" class="tituloCampo">Teléfono:</td>
                                                                    <td>
                                                                    <div style="float:left">
                                                                        <input type="text" name="txtTelefonoComp" id="txtTelefonoComp" size="16" style="text-align:center"/>
                                                                    </div>
                                                                    <div style="float:left">
                                                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                                                                    </div>
                                                                    </td>
                                                                    <td align="right" class="tituloCampo">Otro Telf.:</td>
                                                                    <td>
                                                                    <div style="float:left">
                                                                        <input type="text" name="txtOtroTelefonoComp" id="txtOtroTelefonoComp" size="16" style="text-align:center"/>
                                                                    </div>
                                                                    <div style="float:left">
                                                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                                                                    </div>
                                                                    </td>
                                                                    <td align="right" class="tituloCampo"><?php echo $spanEmail; ?>:</td>
                                                                    <td>
                                                                    <div style="float:left">
                                                                        <input type="text" name="txtEmailComp" id="txtEmailComp" maxlength="50" style="width:99%"/>
                                                                    </div>
                                                                    <div style="float:left">
                                                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoCorreo; ?>"/>
                                                                    </div>
                                                                    </td>
                                                                </tr>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </fieldset>
                                                    </td>
                                                </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="right" colspan="6"><hr>
                                                <button type="button" id="btnAtrasListIngr" name="btnAtrasListIngr" onclick="showView('listIngreso', false);"> << Atras </button>
                                                <button type="button" id="btnDatosAdicionales" name="btnDatosAdicionales" onclick="showView('listDatosAdicional', true);"> Siguiente >></button>
                                                <button type="button" id="btnCancelarDatosAdicionales" name="btnCancelarDatosAdicionales" class="close">Cancelar</button>
                                            </td>
                                        </tr>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                            </table>
                        </div>
                        
                        <div id="tblListdatosAdicionales" style="display: none;">
                            <table id="tblListModelInteres" border="0" width="100%">
                            <tr>
                                <td valign="top">
                                    <div class="men">
                                        <a href="#menu"></a>
                                    </div>
                                    <nav id="menu" class="mm-menu mm-offcanvas mm-current">
                                        <div class="mm-panels">
                                            <div class="mm-panel mm-opened mm-current" id="mm-0">
                                                <ul class="mm-listview">
                                                    <li id="tipo_contacto" onclick="menuBar(this.id);" class="current"><a href="#">Tipo de Contacto</a></li>
                                                    <li id="interes" onclick="menuBar(this.id);"><a href="#">Modelo Interes</a></li>
                                                    <li id="adicional" onclick="menuBar(this.id);"><a href="#">Datos Adicional</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </nav>
                                </td>
                                <td valign="top" width="800">
                                    <!-- TIPO DE CONTACTO-->
                                    <table id="tipo_contacto" class="log_up" width="100%">
                                    <tr>
                                        <td>
                                        <fieldset>
                                            <div id="divTipoContacto"></div>
                                        </fieldset>
                                        </td>
                                     </tr>
                                    </table>
                                    
                                    <!-- MODELO DE INTERES-->
                                    <table id="interes" class="log_up" style="display:none;" width="100%">
                                    <tr>
                                        <td>
                                        <fieldset>
                                            <table border="0" width="100%">
                                            <tr align="left">
                                                <td colspan="6">
                                                    <a class="modalImg" id="aNuevoModelo" rel="#divFlotante8" onclick="abrirFrom(this,'frmBuscarModelo','tdFlotanteTitulo8', '', 'tblListModelInteres')">
                                                        <button id="btnAgregarModelo" name="btnAgregarModelo" type="button">
                                                            <table align="center" cellpadding="0" cellspacing="0">
                                                            <tr>
                                                                <td><img src="../img/iconos/add.png"/></td>
                                                                <td>&nbsp;</td>
                                                                <td>Agregar</td>
                                                            </tr>
                                                            </table>
                                                        </button>
                                                    </a>
                                                    <a class="modalImg" id="aNuevoModeloGenerico" rel="#divFlotante8" onclick="xajax_insertarModelo('', xajax.getFormValues('frmSeguimiento'));">
                                                        <button id="btnAgregarModeloGenerico" name="btnAgregarModeloGenerico" type="button">
                                                            <table align="center" cellpadding="0" cellspacing="0">
                                                            <tr>
                                                                <td><img src="../img/iconos/car.png"/></td>
                                                                <td>&nbsp;</td>
                                                                <td>Agregar Generico</td>
                                                            </tr>
                                                            </table>
                                                        </button>
                                                    </a>
                                                    <button id="btnEliminarModelo" name="btnEliminarModelo" onclick="xajax_eliminarModelo(getModelChecked());"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                                                </td>
                                            </tr>
                                            <tr align="center" class="tituloColumna">
                                                <td><input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/></td>
                                                <td width="12%">Marca</td>
                                                <td width="12%">Modelo</td>
                                                <td width="12%">Versión</td>
                                                <td width="22%">Unidad Básica</td>
                                                <td width="10%"><?php echo $spanPrecioUnitario; ?> Base</td>
                                                <td width="12%">Medio</td>
                                                <td width="9%">Niv. Interés</td>
                                                <td width="11%">Plan Pago</td>
                                            </tr>
                                            <tr id="trItmPieModeloInteres"></tr>
                                            </table>
                                        </fieldset>
                                        </td>
                                    </tr>
                                    </table>
                                    
                                    <!-- DATOS ADICIONALES-->
                                    <table id="adicional" class="log_up" style="display:none;" width="100%">
                                    <tr>
                                        <td>
                                        <fieldset>
                                            <table border="0" width="100%">
                                            <tr align="left">
                                                <td align="right" class="tituloCampo" width="16%">Ultima Atenci&oacute;n:</td>
                                                <td width="17%"><input type="text" id="txtFechaUltAtencion" name="txtFechaUltAtencion" autocomplete="off" size="14" style="text-align:center"/></td>
                                                <td align="right" class="tituloCampo" width="16%">Ultima Entrevista:</td>
                                                <td width="17%"><input type="text" id="txtFechaUltEntrevista" name="txtFechaUltEntrevista" autocomplete="off" size="14" style="text-align:center"/></td>
                                                <td align="right" class="tituloCampo" width="16%">Pr&oacute;xima Entrevista:</td>
                                                <td width="18%"><input type="text" id="txtFechaProxEntrevista" name="txtFechaProxEntrevista" autocomplete="off" size="14" style="text-align:center"/></td>
                                            </tr>
                                            </table>
                                        </fieldset>
                                        <br>
                                        <fieldset>
                                            <table border="0" width="100%">
                                            <tr align="left">
                                                <td align="right" class="tituloCampo">Compañia:</td>
                                                <td><input type="text" style='width:92%;' name="txtCompania" id="txtCompania" maxlength="50"/></td>
                                                <td align="right" class="tituloCampo">Puesto:</td>
                                                <td id="tdLstPuesto" align="left">
                                                    <select>
                                                        <option value="">[ Seleccione ]</option>
                                                    </select>
                                                </td>
                                                <td align="right" class="tituloCampo">T&iacute;tulo:</td>
                                                <td id="tdLstTitulo" align="left">
                                                    <select>
                                                        <option value="">[ Seleccione ]</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr align="left">
                                                <td align="right" class="tituloCampo">Nivel de Influencia:</td>
                                                <td id="tdLstNivelInfluencia">
                                                    <select>
                                                        <option value="">[ Seleccione ]</option>
                                                    </select>
                                                </td>
                                                <td align="right" class="tituloCampo">Sector:</td>
                                                <td id="tdLstSector">
                                                    <select>
                                                        <option value="">[ Seleccione ]</option>
                                                    </select>
                                                </td>
                                                <td align="right" class="tituloCampo">Estatus:</td>
                                                <td id="td_select_estatus"></td>
                                            </tr>
                                            <tr align="left">
                                                <td align="right" class="tituloCampo" width="14%">Estado Civil:</td>
                                                <td id="tdlstEstadoCivil" width="19%">
                                                    <select size="1" name="lstEstadoCivil" id="lstEstadoCivil">
                                                        <option value="-1">[ Seleccione ]</option>
                                                    </select>
                                                </td>
                                                <td align="right" class="tituloCampo" width="14%">Sexo:</td>
                                                <td width="19%">
                                                    <input type="radio" name="rdbSexo" id="rdbSexoM" class="rdbSexoM" value="M"/>M
                                                    <input type="radio" name="rdbSexo" id="rdbSexoF" class="rdbSexoF" value="F"/>F
                                                </td>
                                            </tr>
                                            <tr align="left">
                                                <td align="right" class="tituloCampo">Clase Social:</td>
                                                <td>
                                                    <select style='width:94%;' name="lstNivelSocial" id="lstNivelSocial" class="inputHabilitado lstNivelSocial">
                                                        <option value="">[ Seleccione ]</option>
                                                        <option value="3">Alta</option>
                                                        <option value="2">Media</option>
                                                        <option value="1">Baja</option>
                                                    </select>
                                                </td>
                                                <td align="right" class="tituloCampo" rowspan="2">Observaci&oacute;n:</td>
                                                <td colspan="4" rowspan="2"><textarea id="txtObservacion" name="txtObservacion" class="inputHabilitado txtObservacion" cols="45" rows="2"></textarea></td>
                                            </tr>
                                            <tr align="left">
                                                <td align="right" class="tituloCampo">Motivo de Rechazo:</td>
                                                <td id="tdLstMotivoRechazo">
                                                    <select>
                                                        <option value="">[ Seleccione ]</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr align="left">
                                                <td align="right" class="tituloCampo">Posibilidad de cierre:</td>
                                                <td colspan="" id="tdLstPosibilidadCierre">
                                                    <select>
                                                        <option value="">[ Seleccione ]</option>
                                                    </select>
                                                </td>
                                                <td colspan="4">
                                                    <img id="imgPosibleCierrePerfil" width="80" height="80"/>
                                                </td>
                                            </tr>
                                            </table>
                                        </fieldset>
                                        </td>
                                    </tr>
                                    </table>
                                </td>
                            </tr>
                            </table>
                        </div>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right">
                <div id="btnsGuardarSeguimiento" style="display:none;">
                    <hr>
                    <input type="hidden" name="hddIdPerfilProspecto" id="hddIdPerfilProspecto" readonly="readonly"/>
                    <input type="hidden" name="hddIdClienteProspecto" id="hddIdClienteProspecto" readonly="readonly"/>
                    <input type="hidden" name="hddIdSeguimiento" id="hddIdSeguimiento" readonly="readonly"/>
                    <div >
                        <button type="button" id="btnAtrasListVend" name="btnAtrasListVend" onclick="showView('listProspecto', false);"> << Atras </button>
                        <button type="button" id="btnGuardarProspecto" name="btnGuardarProspecto" onclick="validarFrmSeguimiento();">Guardar</button>
                        <button type="button" id="btnCancelarProspecto" name="btnCancelarProspecto" class="close">Cancelar</button> 
                    </div>
                </div>
            </td>
        </tr>
        </table>
    </div>
    <input type="hidden" name="lstEquipo" id="lstEquipo" readonly="readonly"/>
    <input type="hidden" name="rdItemIntegrante" id="rdItemIntegrante" readonly="readonly"/>
    <button type="hidden" style="display:none" id="abtnValidarSeguimiento" title="btnValidarSeguimiento" rel="#divFlotante3" name="btnValidarSeguimiento" class="modalImg" onclick="abrirFrom(this, 'frmValidarSeguimiento', 'tdFlotanteTitulo3', '', 'tbValidarSeguimiento');"></button>
    <button type="hidden" style="display:none" id="abtnListaCoincidencia" title="btnListaCoincidencia" rel="#divFlotante6" name="btnListaCoincidencia" class="modalImg" onclick="abrirFrom(this, 'frmListaCoincidencia', 'tdFlotanteTitulo6', '', 'tbListaCoincidencia');"></button>
    <button type="hidden" style="display:none" id="abtnNoHayCoincidencia" title="btnNoHayCoincidencia" rel="#divFlotante7" name="btnNoHayCoincidencia" class="modalImg" onclick="abrirFrom(this, 'frmNoHayCoincidencia', 'tdFlotanteTitulo7', '', 'tbNoHayCoincidencia');"></button>
    <button type="hidden" style="display:none" id="abtnValidarActCierre" title="btnValidarActCierre" rel="#divFlotante15" name="btnValidarActCierre" class="modalImg" onclick="abrirFrom(this, 'frmLstActCierre', 'tdFlotanteTitulo15', '', 'tblLstActCierre');"></button>
    <button type="hidden" style="display:none" id="abtnEditar" title="abtnEditar" name="abtnEditar"></button>
    <div id="divListEquipo"></div>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <div id="tblLstCliente" style="max-height:500px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr>
            <td>
            <form id="frmBusCliente" name="frmBusCliente" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo">Tipo de Pago:</td>
                    <td>
                        <select id="lstTipoPago" name="lstTipoPago" class="inputHabilitado" onchange="byId('btnBuscarCliente').click();" style="width:99%">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="no">Contado</option>
                            <option value="si">Cr&eacute;dito</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo">Estatus:</td>
                    <td>
                        <select id="lstEstatusBuscar" name="lstEstatusBuscar" class="inputHabilitado" onchange="byId('btnBuscarCliente').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option selected="selected" value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120">Paga Impuesto:</td>
                    <td>
                        <select id="lstPagaImpuesto" name="lstPagaImpuesto" class="inputHabilitado" onchange="byId('btnBuscarCliente').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="0">No</option>
                            <option value="1">Si</option>
                        </select>
                    </td>
                     <td align="right" class="tituloCampo" width="120">Ver:</td>
                    <td>
                        <select id="lstTipoCuentaCliente" name="lstTipoCuentaCliente">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="1">Prospecto</option>
                            <option value="3">Prospecto Aprobado</option>
                            <option value="2">Cliente Sin Prospectaci&oacute;n</option>
                        </select>
                    </td>
				</tr>
				<tr>
                	<td></td>
                	<td></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" class="inputHabilitado" onkeyup="$('#btnBuscarCliente').click();"/></td>
                    <td>                       
                        <button type="button" id="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBusCliente'),xajax.getFormValues('frmSeguimiento'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBusCliente'].reset(); byId('btnBuscarCliente').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td><div id="divCliente"></div></td>
        </tr>
        <tr>
            <td align="right"><hr />
            <button type="button" id="btnCerraCliente" name="btnCerraCliente" class="close">Cerrar</button>
            </td>
        </tr>
        </table>
    </div>
</div>

<div id="divFlotante3" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0; max-height:720px; overflow:auto; width:450px;">
    <div id="divFlotanteTitulo3" class="handle"><table><tr><td id="tdFlotanteTitulo3" width="100%"></td></tr></table></div>
    
<form id="frmValidarSeguimiento" name="frmValidarSeguimiento" style="margin:0" onsubmit="return false;">
    <table border="0" width="100%">
    <tr>
    	<td>
    		<table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
            <tr>
                <td width="25"><img src="../img/iconos/information.png" width="25"/></td>
                <td align="center">Desea continuar el seguimiento de <span id="nbCliente"></span> que est&aacute; sin concluir?
                <br><br><br>Al continuar, no se actualiza la fecha de entrada al Seguimiento.</td>
            </tr>
            </table>
		</td>
	</tr>
    <tr>
        <td align="center" colspan="6"><hr>
            <input type="hidden" id="hddIdClienteValidarSeguimiento" name="hddIdClienteValidarSeguimiento"></input>
            <button type="button" id="btnOpcSi" name="btnOpcSi" onclick="xajax_cargarDatos('', $('#hddIdClienteValidarSeguimiento').val(), false, xajax.getFormValues('frmSeguimiento'));">Si</button>
            <button type="button" id="btnOpcNo" name="btnOpcNo" onclick="xajax_cargarDatos('', $('#hddIdClienteValidarSeguimiento').val(), true, xajax.getFormValues('frmSeguimiento'));">No</button>
            <button type="hidden" style="display:none;" id="btnValidarSeguimiento" name="btnValidarSeguimiento" class="close"></button>
        </td>
    </tr>
    </table>
</form>
</div>

<div id="divFlotante4" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo4" class="handle"><table><tr><td id="tdFlotanteTitulo4" width="100%"></td></tr></table></div>
    <table width="760" id="tblListEmpresa" >
    <tr>
        <td align="right">
            <form id="frmBusEmpresa" name="frmBusEmpresa" style="margin:0" onsubmit="return false;">
                <table>
                <tr>
                    <td class="tituloCampo" width="120" align="right">Criterio</td>
                    <td><input id="textCriterio" name="textCriterio" class="inputHabilitado" onkeyup="$('#btnBuscarEmpresa').click();"/></td>
                </tr>
                <tr align="right">
                    <td colspan="2">
                        <button id="btnBuscarEmpresa" name="btnBuscarEmpresa">Buscar</button>
                        <button id="btnLimpiarEmpresa" name="btnLimpiarEmpresa" onclick="document.forms['frmBusEmpresa'].reset(); byId('btnBuscarEmpresa').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
        </td>
    </tr>
    <tr><td id="tdListEmpresa"></td></tr>
    <tr>
        <td align="right"><hr />
            <button id="btnCerrarEmp" name="btnCerrarEmp" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<div id="divFlotante5" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo5" class="handle"><table><tr><td id="tdFlotanteTitulo5" width="100%"></td></tr></table></div>
    
    <table id="tblListEmpleado" width="760">
    <tr>
        <td>
        <form id="frmBuscarEmpleado" name="frmBuscarEmpleado" style="margin:0" onsubmit="return false;">
            <table align="right">
            <tr>
                <td align="right" class="tituloCampo" width="120">Criterio</td>
                <td><input id="txtCriterioBuscarEmpleado" name="txtCriterioBuscarEmpleado" class="inputHabilitado" onkeyup="$('#btnBuscarEmpleado').click();"/></td>
                <td>
                    <button id="btnBuscarEmpleado" name="btnBuscarEmpleado" onclick="xajax_buscarEmpleado(xajax.getFormValues('frmBuscarEmpleado'), xajax.getFormValues('frmSeguimiento'));">Buscar</button>
                    <button id="btnLimpiarEmpleado" name="btnLimpiarEmpleado" onclick="document.forms['frmBuscarEmpleado'].reset(); byId('btnBuscarEmpleado').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr><td id="tdListEmpleado"></td></tr>
    <tr>
        <td align="right"><hr />
            <button id="btnCerrarEmpleado" name="btnCerrarEmpleado" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<div id="divFlotante6" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo6" class="handle"><table><tr><td id="tdFlotanteTitulo6" width="100%"></td></tr></table></div>
    
	<div id="tbListaCoincidencia" style="max-height:500px; overflow:auto; width:960px;">
    	<table border="0" width="100%">
    	<tr>
        	<td>
            <form id="frmListaCoincidencia" name="frmListaCoincidencia" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Criterio</td>
                    <td><input id="textCriterioEmpleado" name="textCriterioEmpleado" class="inputHabilitado" width="50%" /></td>
                    <td align="right">
                        <button id="btnBuscarEmpleadoList" name="btnBuscarEmpleadoList" onclick="xajax_listaProspectoCliente('','','','|||||' + $('#textCriterioEmpleado').val() + '|'+byId('btnListIdArray').value + '');">Buscar</button>
                        <button id="btnLimpiarEmpleado" name="btnLimpiarEmpleado" onclick="byId('btnBuscarEmpleadoList').click(); document.forms['frmListaCoincidencia'].reset();">Limpiar</button>
                        <button style="display:none;" id="btnListIdArray" name="btnListIdArray"></button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr><td><div id="tdListaCoincidencia"></div></td></tr>
        <tr>
            <td align="right"><hr/>
            	<button id="btnCerrarListaCoincidencia" name="btnCerrarListaCoincidencia" class="close">Cerrar</button>
            </td>
        </tr>
    	</table>
	</div>
</div>

<div id="divFlotante7" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0; max-height:720px; overflow:auto; width:450px;">
	<div id="divFlotanteTitulo7" class="handle"><table><tr><td id="tdFlotanteTitulo7" width="100%"></td></tr></table></div>
    
<form id="frmNoHayCoincidencia" name="frmNoHayCoincidencia" style="margin:0" onsubmit="return false;">
    <table border="0" width="100%">
    <tr>
        <td>
            <table cellpadding="0" cellspacing="0" class="divMsjError" width="100%">
            <tr>
                <td width="25"><img src="../img/iconos/ico_fallido.gif" width="25"/></td>
                <td align="center">No se encontraron registros.<br>Desea crear un nuevo Prospecto?</td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="center"><hr>
            <button class='spanCrearNuevo' type="button" id="btnOpcSi" name="btnOpcSi" onclick="btnOcultarDatos('show');byId('btnCerrarDivFlotante7').click();">Si</button>
            <button class='spanCrearNuevo' type="button" id="btnOpcNo" name="btnOpcNo" onclick="byId('btnCerrarDivFlotante7').click();">No</button>
            <button class='spanNoCrearNuevo' type="button" id="btnOpcOk" name="btnOpcOk" onclick="byId('btnCerrarDivFlotante7').click();">Ok</button>
            <button type="hidden" style="display:none;" id="btnCerrarDivFlotante7" name="btnCerrarDivFlotante7" class="close"></button>
        </td>
    </tr>
    </table>
</form>
</div>

<!-- LISTA DE MODELOS-->
<div id="divFlotante8" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo8" class="handle"><table><tr><td id="tdFlotanteTitulo8" width="100%"></td></tr></table></div>
    
	<div id="tblModelo" style="max-height:500px; overflow:auto; width:960px;">
		<table id="ListModelInteres" border="0" width="100%" style="display:none; overflow:auto;">
        <tr>
            <td>
            <form id="frmBuscarModelo" name="frmBuscarModelo" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="100">Empresa:</td>
                    <td>
                        <input type="text" id="txtIdEmpresaBuscarModelo" name="txtIdEmpresaBuscarModelo" size="5" readonly="readonly"/>
                        <input type="text" id="txtEmpresaBuscarModelo" name="txtEmpresaBuscarModelo" size="45" readonly="readonly"/>
                    </td>
                </tr>
                <tr align="left"> 
                    <td align="right" class="tituloCampo" width="100">Criterio:</td>
                    <td><input type="text" id="txtCriterioBuscarModelo" name="txtCriterioBuscarModelo" class="inputHabilitado" onkeyup="$('#btnBuscarModelo').click();"/></td>
                </tr>
                <tr align="right">   
                    <td colspan="2">
                        <button type="button" id="btnBuscarModelo" name="btnBuscarModelo" onclick="xajax_buscarModelo(xajax.getFormValues('frmBuscarModelo'), xajax.getFormValues('frmSeguimiento'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscarModelo'].reset(); byId('btnBuscarModelo').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td><div id="divListaModelo" style="width:100%"></div></td>
        </tr>
        <tr>
            <td align="right" colspan="6"><hr>
                <button type="button" id="btnCancelarModelo" name="btnCancelarModelo" class="close">Cancelar</button>
            </td>
        </tr>
		</table>
	</div>
</div>

<!--Agregar Notas-->
<div id="divFlotante9" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo9" class="handle"><table><tr><td id="tdFlotanteTitulo9" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante1" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
<form id="frmBusNotas" name="frmBusNotas" style="margin:0" onsubmit="return false;"> 
    <div id="tblLstNotas" style="max-height:500px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr>
            <td>
            <fieldset><legend class="legend">Nueva Nota <span style="color: #d30;">(El campo Notas tiene un límite de 300 caracteres)</span></legend>
                <table width="100%">
                <tr>
                    <td><div id="divfrmNotas"></div></td>
                </tr>
                <tr>
                    <td align="right"><hr/>
                        <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFrmNotas();">Guardar</button>
                    </td>
                </tr>
                </table>
            </fieldset>
            </td>
        </tr>
        <tr>
            <td><div id="divListNotas"></div></td>
        </tr>
        <tr>
            <td align="right"><hr />
                <button type="button" id="btnCerrarNotas" name="btnCerrarNotas" class="close">Cerrar</button>
            </td>
        </tr>
        </table>
	</div>
</form>
</div>


<!-- ASIGNAR ACTIVIDAD -->
<div id="divFlotante10" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo10" class="handle"><table><tr><td id="tdFlotanteTitulo10" width="100%"></td></tr></table></div>
    
<form id="formAsignarActividadSeg"  name="formAsignarActividadSeg" onsubmit="return false;">
    <table border="0" width="760">
    <tr align="left">
        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empleado:</td>
        <td colspan="3">
            <table cellpadding="0" cellspacing="0">
            <tr>
                <td><input type="text" id="textIdEmpVendedor" name="textIdEmpVendedor" readonly="readonly" size="6" style="text-align:right"/></td>
                <td></td>
                <td><input type="text" id="nombreVendedor" name="nombreVendedor" readonly="readonly" size="45"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr id="trAsignarActTipo" align="left">
        <td align="right" class="tituloCampo" width="20%">Tipo de Actividad:</td>
        <td width="30%"><input name="txtTipoActividad" id="txtTipoActividad" type="text" readonly="readonly"/></td>
        <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Actividad:</td>
        <td id="tdListActividad" width="30%"></td>
    </tr>
    <tr align="left">
        <td align="right" class="tituloCampo">Asignada para:</td>
        <td colspan="3">
            <table cellpadding="0" cellspacing="0">
            <tr>
                <td>&nbsp;Fecha:&nbsp;</td>
                <td><input type="text" id="textFechAsignacion" name="textFechAsignacion" autocomplete="off" class="inputHabilitado" placeholder="<?php echo spanDateFormat; ?>" readonly="readonly" size="10" style="text-align:center"/></td>
                <td>&nbsp;Hora:&nbsp;</td>
                <td id="tdSelectHora">
                    <select id="listHora" name="listHora">
                        <option value="">[ Seleccione ]</option>
                    </select>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr align="left">
        <td id="tdNombreCliente" align="right" class="tituloCampo"></td> 
        <td colspan="3">
            <table cellpadding="0" cellspacing="0">
            <tr>
                <td><input type="text" id="hddIdClienteActividad" name="hddIdClienteActividad" readonly="readonly" size="6" style="text-align:right"/></td>
                <td></td>
                <td><input type="text" id="textNombreCliente" name="textNombreCliente" readonly="readonly" size="45"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr id="trTipoFinalizacion" align="left">
        <td align="right" class="tituloCampo">Tipo de Finalizacion</td>
        <td colspan="3">
            <select id='comboxEstadoActAgenda' name='comboxEstadoActAgenda' class="inputHabilitado"> 
                <option value=''>[ Seleccione ]</option>
                <option value='0'>No Efectiva</option>
                <option value='1'>Efectiva</option>
             </select>
        </td>
    </tr>
    <tr align="left">
        <td align="right" class="tituloCampo">Nota:</td>
        <td colspan="3"><textarea id="textNotaCliente" name="textNotaCliente" class="inputCompletoHabilitado" rows="2"></textarea></td>
    </tr>
    <tr>
        <td align="right" colspan="4"><hr>
            <input name="textHoraAsignacion" id="textHoraAsignacion" type="hidden" readonly="readonly"/>
            <input name="hddIdSeguimientoAct" id="hddIdSeguimientoAct" type="hidden" value=""/>
            <input name="hddIdEquipo" id="hddIdEquipo" type="hidden" value=""/>
            <input name="hddIdIntegrante" id="hddIdIntegrante" type="hidden" value=""/>
            <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFrmActSeguimiento();">Guardar</button>
            <button type="button" id="butCancelarAsignacion" name="butCancelarAsignacion" onclick="byId('btnBuscar').click();" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>


<div id="divFlotante11" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo11" class="handle"><table><tr><td id="tdFlotanteTitulo11" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante1" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
    <table border="0" id="tblLista" style="display:none" width="960">
	    <tr>
	    	<td>
            <form id="frmBuscarLista" name="frmBuscarLista" onsubmit="return false;" style="margin:0">
                <table align="right">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterioBuscarLista" name="txtCriterioBuscarLista" onkeyup="$('#btnBuscarLista').click();"/></td>
                    <td>
                        <button type="submit" id="btnBuscarLista" name="btnBuscarLista">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscarLista'].reset(); byId('btnBuscarLista').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
	        </td>
	    </tr>
	    <tr>
	    	<td><div id="divLista" style="width:100%"></div></td>
	    </tr>
	    <tr>
	    	<td align="right"><hr>
	            <button type="button" id="btnCancelarLista" name="btnCancelarLista" class="close">Cerrar</button>
	        </td>
	    </tr>
    </table>
	
<form id="frmAjusteInventario" name="frmAjusteInventario" onsubmit="return false;" style="margin:0">
    <div id="tblAjusteInventario" style="max-height:500px; overflow:auto; width:960px;">
    	<table border="0" width="100%">
        <tr>
        	<td>
            	<table width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdEmpresaTrade" name="txtIdEmpresaTrade" size="6" readonly="readonly" style="text-align:right;"/></td>
                            <td></td>
                            <td><input type="text" id="txtEmpresaTrade" name="txtEmpresaTrade" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
		</tr>
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" width="100%">
	                <tr>
	                    <td valign="top" width="65%">
		                    <fieldset><legend class="legend">Datos Personales</legend>
		                        <table border="0" width="100%">
		                        <tr>
		                            <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Cliente:</td>
		                            <td width="85%">
		                                <table cellpadding="0" cellspacing="0">
		                                <tr>
		                                    <td><input type="text" id="txtIdCliente" name="txtIdCliente" readonly="readonly" size="6" style="text-align:right;"/></td>
		                                    <td style="display:none;">
												<input type="hidden" id="hddIdSeguimientoTrade" name="hddIdSeguimientoTrade" size="6" style="text-align:right;"/>
		                                   		<input type="hidden" id="hddIdCliente" name="hddIdCliente"  size="6" style="text-align:right;"/>
		                                    </td>
		                                    <td><input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="45"/></td>
		                                </tr>
		                                </table>
		                            </td>
		                        </tr>
		                        </table>
		                    </fieldset>
	                    </td>
	                </tr>
                </table>
            </td>
        </tr>
        <tr id="trUnidadFisica">
        	<td>
	            <fieldset><legend class="legend">Unidad Física</legend>
	            	<table width="100%">
	                <tr>
	                	<td valign="top" width="68%">
		                    <fieldset><legend class="legend">Datos de la Unidad</legend>
		                        <table width="100%">
		                        <tr align="left">
		                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Nombre:</td>
		                            <td id="tdlstUnidadBasica" width="30%"></td>
		                            <td align="right" class="tituloCampo" width="20%">Clave:</td>
		                            <td width="30%"><input type="text" id="txtClaveUnidadBasicaAjuste" name="txtClaveUnidadBasicaAjuste" readonly="readonly" size="24"/></td>
		                        </tr>
		                        <tr align="left">
		                            <td align="right" class="tituloCampo" rowspan="3">Descripción:</td>
		                            <td rowspan="3"><textarea id="txtDescripcionAjuste" name="txtDescripcionAjuste" cols="20" rows="3"></textarea></td>
		                            <td align="right" class="tituloCampo">Marca:</td>
		                            <td><input type="text" id="txtMarcaUnidadBasicaAjuste" name="txtMarcaUnidadBasicaAjuste" readonly="readonly" size="24"/></td>
		                        </tr>
		                        <tr align="left">
		                            <td align="right" class="tituloCampo">Modelo:</td>
		                            <td><input type="text" id="txtModeloUnidadBasicaAjuste" name="txtModeloUnidadBasicaAjuste" readonly="readonly" size="24"/></td>
		                        </tr>
		                        <tr align="left">
		                            <td align="right" class="tituloCampo">Versión:</td>
		                            <td><input type="text" id="txtVersionUnidadBasicaAjuste" name="txtVersionUnidadBasicaAjuste" readonly="readonly" size="24"/></td>
		                        </tr>
		                        <tr align="left">
		                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Año:</td>
		                            <td id="tdlstAno"></td>
		                            <td align="right" class="tituloCampo"><?php echo $spanPlaca; ?>:</td>
		                            <td><input type="text" id="txtPlacaAjuste" name="txtPlacaAjuste" size="24"/></td>
		                        </tr>
		                        <tr align="left">
		                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Condición:</td>
		                            <td id="tdlstCondicion"></td>
		                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fabricación:</td>
		                            <td><input type="text" id="txtFechaFabricacionAjuste" name="txtFechaFabricacionAjuste" autocomplete="off" size="10" style="text-align:center"/></td>
		                        </tr>
		                        <tr align="left">
		                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanKilometraje; ?>:</td>
		                            <td><input type="text" id="txtKilometrajeAjuste" name="txtKilometrajeAjuste" onkeypress="return validarSoloNumeros(event);" size="24" style="text-align:right"/></td>
		                            <td align="right" class="tituloCampo">Expiración Marbete:</td>
		                            <td><input type="text" id="txtFechaExpiracionMarbeteAjuste" name="txtFechaExpiracionMarbeteAjuste" readonly="readonly" size="10" style="text-align:center"/></td>
		                        </tr>
		                        </table>
		                    </fieldset>
	                    </td>
	                    <td valign="top">
		                    <fieldset><legend class="legend">Colores</legend>
		                        <table border="0" width="100%">
			                        <tr align="left">
			                            <td align="right" class="tituloCampo" width="10%"><span class="textoRojoNegrita">*</span>Color Externo 1:</td>
			                            <td id="tdlstColorExterno1" width="16%"></td>
		                          	</tr>
		                          	<tr align="left">
			                            <td align="right" class="tituloCampo" width="10%">Color Externo 2:</td>
			                            <td id="tdlstColorExterno2" width="16%"></td>
			                        </tr>
			                        <tr align="left">
			                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Color Interno 1:</td>
			                            <td id="tdlstColorInterno1"></td>
			                        </tr>
		                          	<tr align="left">
			                            <td align="right" class="tituloCampo">Color Interno 2:</td>
			                            <td id="tdlstColorInterno2"></td>
			                        </tr>
		                        </table>
		                    </fieldset>
	                    </td>
					</tr>
	                <tr>
	                	<td>
		                    <fieldset><legend class="legend">Seriales</legend>
		                        <table border="0" width="100%">
		                        <tr align="left">
		                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span><?php echo $spanSerialCarroceria; ?></td>
		                            <td width="30%">
		                            	<table cellpadding="0" cellspacing="0">
		                                <tr>
		                                	<td>
		                                    <div style="float:left">
		                                        <input type="text" id="txtSerialCarroceriaAjuste" name="txtSerialCarroceriaAjuste" maxlength="<?php echo substr($arrayValidarCarroceria[0], -6,2); ?>"/>
		                                    </div>
		                                    <div style="float:left">
		                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoCarroceria; ?>"/>
		                                    </div>
		                                    </td>
		                                </tr>
		                                <tr id="trAsignarUnidadFisica">
			                                <td><label><input type="checkbox" id="cbxAsignarUnidadFisica" name="cbxAsignarUnidadFisica" onclick="xajax_buscarCarroceria(xajax.getFormValues('frmAjusteInventario'));" value="1"/>Asignar unidad fí­sica anteriormente vendida</label></td>
		                                </tr>
		                                </table>
		                            </td>
		                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span><?php echo $spanSerialMotor; ?>:</td>
		                            <td width="30%"><input type="text" id="txtSerialMotorAjuste" name="txtSerialMotorAjuste"/></td>
		                        </tr>
		                        <tr align="left">
		                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Vehí­culo:</td>
		                            <td><input type="text" id="txtNumeroVehiculoAjuste" name="txtNumeroVehiculoAjuste"/></td>
		                        </tr>
		                        <tr align="left">
		                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Registro Legalización:</td>
		                            <td><input type="text" id="txtRegistroLegalizacionAjuste" name="txtRegistroLegalizacionAjuste"/></td>
		                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Registro Federal:</td>
		                            <td><input type="text" id="txtRegistroFederalAjuste" name="txtRegistroFederalAjuste"/></td>
		                        </tr>
		                        </table>
		                    </fieldset>
	                	</td>
	                    <td rowspan="2" valign="top">
		                    <fieldset><legend class="legend">Trade-In</legend>
		                        <table border="0" width="100%">
		                        <tr align="right">
		                            <td class="tituloCampo" width="45%"><span class="textoRojoNegrita">*</span>Allowance:</td>
		                            <td width="55%">
		                            	<table cellpadding="0" cellspacing="0" width="100%">
		                                <tr>
		                                	<td colspan="2"><input type="text" id="txtAllowance" name="txtAllowance" maxlength="12" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
		                                	<td><img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="Monto por el cual serí¡ recibido" /></td>
		                                </tr>
		                                <tr id="trtxtAllowanceAnt">
		                                	<td class="textoNegrita_10px">Anterior:</td>
		                                	<td><input type="text" id="txtAllowanceAnt" name="txtAllowanceAnt" class="inputSinFondo textoNegrita_10px" readonly="readonly" style="text-align:right"/></td>
		                                </tr>
		                                </table>
		                            </td>
		                        </tr>
		                        <tr align="right">
		                            <td class="tituloCampo"><span class="textoRojoNegrita">*</span>ACV:</td>
		                            <td>
		                            	<table cellpadding="0" cellspacing="0" width="100%">
		                                <tr>
		                                	<td colspan="2"><input type="text" id="txtAcv" name="txtAcv" maxlength="12" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
		                                	<td><img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="Valor en el inventario" /></td>
										</tr>
		                                <tr id="trtxtAcvAnt">
		                                	<td class="textoNegrita_10px">Anterior:</td>
		                                	<td><input type="text" id="txtAcvAnt" name="txtAcvAnt" class="inputSinFondo textoNegrita_10px" readonly="readonly" style="text-align:right"/></td>
										</tr>
		                                </table>
		                            </td>
		                        </tr>
		                        <tr align="right">
		                            <td class="tituloCampo"><span class="textoRojoNegrita">*</span>Payoff:</td>
		                            <td>
		                            	<table cellpadding="0" cellspacing="0" width="100%">
		                                <tr>
		                                	<td colspan="2"><input type="text" id="txtPayoff" name="txtPayoff" class="inputHabilitado" onblur="setFormatoRafk(this,2); calcularMonto();" maxlength="12" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
		                                	<td><img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="Monto total adeudado" /></td>
										</tr>
		                                <tr id="trtxtPayoffAnt">
		                                	<td class="textoNegrita_10px">Anterior:</td>
		                                	<td><input type="text" id="txtPayoffAnt" name="txtPayoffAnt" class="inputSinFondo textoNegrita_10px" readonly="readonly" style="text-align:right"/></td>
										</tr>
		                                </table>
		                            </td>
		                        </tr>
		                        <tr align="right" class="trResaltarTotal">
		                            <td class="tituloCampo"><span class="textoRojoNegrita">*</span>Crédito Neto:</td>
		                            <td>
		                            	<table cellpadding="0" cellspacing="0" width="100%">
		                                <tr>
		                                	<td colspan="2"><input type="text" id="txtCreditoNeto" name="txtCreditoNeto" maxlength="12" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" style="text-align:right"/></td>
		                                	<td><img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="Crédito Neto" /></td>
										</tr>
		                                <tr id="trtxtCreditoNetoAnt">
		                                	<td class="textoNegrita_10px">Anterior:</td>
		                                	<td><input type="text" id="txtCreditoNetoAnt" name="txtCreditoNetoAnt" class="inputSinFondo textoNegrita_10px" readonly="readonly" style="text-align:right"/></td>
										</tr>
		                                </table>
		                            </td>
		                        </tr>
		                        </table>
		                    </fieldset>
	                    </td>
	                </tr>
	                <tr>
						<td>
		                    <fieldset><legend class="legend">Observaci&oacute;n</legend>
		                        <table border="0" width="100%">
			                        <tr align="left">
			                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Observaci&oacute;n:</td>
			                            <td colspan="4" rowspan="2"><textarea id="txtObservacionTrade" name="txtObservacionTrade" class="inputHabilitado" cols="60" rows="2"></textarea></td>
			                        </tr>
		                        </table>
		                    </fieldset>
	                    </td>
	                </tr>
                </table>
			</fieldset>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
            	<input type="hidden" id="hddIdTradeInAjusteInventario" name="hddIdTradeInAjusteInventario"/>
                <button type="button" id="btnGuardarAjusteInventario" name="btnGuardarAjusteInventario" onclick="validarFrmAjusteInventario(0);" style="display:none;">Guardar</button>
                <button type="button" id="btnEditatTradein" name="btnEditatTradein" onclick="validarFrmAjusteInventario(1);" style="display:none;">Guardar</button>
                <button type="button" id="btnCancelarAjusteInventario" name="btnCancelarAjusteInventario" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
</form>
</div>

<!--posible cierre-->
<div id="divFlotante12" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo12" class="handle"><table><tr><td id="tdFlotanteTitulo12" width="100%"></td></tr></table></div>
    
    <div id="tblLstPosibleCierre" style="max-height:500px; overflow:auto; width:960px;">
        <table border="0" width="100%"> 
        <tr align="right">
            <td>
            <form id="frmBusPosibleCierre" name="frmBusPosibleCierre" style="margin:0" onsubmit="return false;"> 
                <table border="0">
                <tr>
                    <td class="tituloCampo" align="right" width="120">Empresa</td>
                    <td>
                        <table cellspacing="0" cellpadding="0">
                        <tr>
                            <td><input type="text" id="textIdEmpresaPosibleCierre" name="textIdEmpresaPosibleCierre" size="6" style="text-align:center;"></td>
                            <td><input type="text" id="textEmpresaPosibleCierre" name="textEmpresaPosibleCierre" readonly="readonly"></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo">Criterio</td>
                    <td>
                        <input id="textCriterioPosibleCierre" name="textCriterioPosibleCierre" class="inputHabilitado" onkeyup="$('#btnBusPosibleCierre').click();">
                    </td>
                    <td>
                        <input id="textHddIdEmpresa" name="textHddIdEmpresa" type="hidden">
                        <button id="btnBusPosibleCierre" name="btnBusPosibleCierre" onclick="xajax_buscarPosibleCierre(xajax.getFormValues('frmBusPosibleCierre'))">Buscar</button>
                        <button id="btnLimPosibleCierre" name="btnLimPosibleCierre" onclick="document.forms['frmBusPosibleCierre'].reset(); byId('btnBusPosibleCierre').click(); xajax_asignarEmpresa(byId('lstEmpresa').value, 'divFlotante6');">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td>
            <form id="frmPosibleCierre" name="frmPosibleCierre" style="margin:0" onsubmit="return false;"> 
                <div id="divfrmPosibleCierre"></div>
                <input id="hddSeguimientoPosibleCierre" name="hddSeguimientoPosibleCierre" type="hidden" />
            </form>
            </td>
        </tr>
        <tr>
            <td align="right"><hr />
            	<button type="button" id="btnGuargarObservacion" style="display: none;" onclick="validarFrmObservacion();" name="btnGuargarObservacion"> Guardar</button>
                <button type="button" id="btnCerrafrmPosibleCierre" name="btnCerrafrmPosibleCierre" class="close">Cerrar</button>
            </td>
        </tr>
        </table>
	</div>
</div>

<!--posible cierre-->
<div id="divFlotante13" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo13" class="handle"><table><tr><td id="tdFlotanteTitulo13" width="100%"></td></tr></table></div>
    <table id="tblLstPosibleCierreObsv"  width="760"> 
        <tr align="right">
            <td>
                <form id="frmBusPosibleCierreObsv" name="frmBusPosibleCierreObsv" style="margin:0" onsubmit="return false;"> 
                	<div id="divfrmPosibleCierreObsv"></div>
                    <input id="hddSeguimientoPosibleCierre" name="hddSeguimientoPosibleCierre" type="hidden" />
                </form>
            </td>
        </tr>
        <tr><td>&nbsp;</td></tr>
        <tr>
            <td align="right"><hr />
                <button type="button" id="btnCerrafrmPosibleCierre" name="btnCerrafrmPosibleCierre" class="close">Cerrar</button>
            </td>
        </tr>
    </table>
</div>

<!--Lista de Citas-->
<div id="divFlotante14" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo14" class="handle"><table><tr><td id="tdFlotanteTitulo14" width="100%"></td></tr></table></div>
    
	<div class="pane" style="max-height:500px; overflow:auto; width:960px;">
    	<table width="100%" id="tblLstCitas"> 
        <tr>
            <td>
            <form id="frmBusCitas" name="frmBusCitas" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">			
                <tr align="left">
                    <td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresaCitas"></td>
                </tr>			
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Vendedor:</td>
                    <td id="tdLstVendedorCitas"></td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="textCriterioCitas" name="textCriterioCitas" class="inputHabilitado"/></td>
                    <td>
                        <button type="button" id="btnBuscarCitas" onclick="xajax_buscarCitas(xajax.getFormValues('frmBusCitas'), xajax.getFormValues('frmBuscar'));">Buscar</button>
                        <button type="button" onclick="this.form.reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
        	<td><div id="divfrmCitas"></div></td>
        </tr>
        <tr>
            <td>
                <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                <tr>
                    <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                    <td align="center">
                        <table>
                        <tr>
                            <td><img src="../img/iconos/cita_entrada.png"/></td><td>Pendiente</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/cita_programada.png"/></td><td>Finalizada</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/cita_entrada_retrazada.png"/></td><td>Finalizada Tarde</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/arrow_rotate_clockwise.png"/></td><td>Finalizada Automáticamente</td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                <tr>
                    <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                    <td align="center">
                        <table>
                        <tr>
                            <td><img src="../img/iconos/time_go.png"/></td><td>Retrasada</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/exclamation.png"/></td><td>No Efectiva</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/tick.png"/></td><td>Efectiva</td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
        	<td align="right"><hr />
            	<button type="button" id="btnCerraCliente" name="btnCerraCliente" class="close">Cerrar</button>
            </td>
        </tr>
    </table>
    </div>
</div>

<!--Actividad cierre-->
<div id="divFlotante15" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo15" class="handle"><table><tr><td id="tdFlotanteTitulo15" width="100%"></td></tr></table></div>
    <table id="tblLstActCierre" width="760"> 
        <tr align="right">
            <td>
                <form id="frmLstActCierre" name="frmLstActCierre" style="margin:0" onsubmit="return false;"> 
                	<div id="divfrmActividadCierre"></div>
                    <input id="hddSeguimientoPosibleCierre" name="hddSeguimientoPosibleCierre" type="hidden" />
                </form>
            </td>
        </tr>
        <tr><td>&nbsp;</td></tr>
        <tr>
            <td align="right"><hr />
                <button type="button" id="btnCerrafrmPosibleCierre" name="btnCerrafrmPosibleCierre" class="close">Cerrar</button>
           		<button type="hidden" style="display:none" id="hddSeguimiento2" name="hddSeguimiento2"></button>
            </td>
        </tr>
    </table>
</div>

<!-- CONSULTAR MODELOS DE INTERES -->
<div id="divFlotante16" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo16" class="handle"><table><tr><td id="tdFlotanteTitulo16" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante16" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
    <div id="tblModelo" style="max-height:500px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr>
            <td><div id="divListaModelosInteres"></div></td>
        </tr>
        <tr>
            <td align="right"><hr />
                <button type="button" id="btnCerrarListModelos" name="btnCerrarListModelos" class="close">Cerrar</button>
            </td>
        </tr>
        </table>
	</div>
</div>