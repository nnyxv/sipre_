
      function confirmarAgregacionDePago(pago)
	  {
	      var monto=unformatNumber(document.getElementById("monto").value); 
	      if(monto >0)
		  {
			  if (confirm("Esta seguro de agregar el pago?"))
			  { 
				 return true
			  }
			  else
			  {
				 return false
			  }
		  }
		  else
			{
			  alert("El Monto debe ser mayor a Cero (0)");
			  document.getElementById("monto").focus();
			  return false
			
			}
	  }
	  function confirmarEliminarPago(pago) {
	      if (confirm("Esta seguro de Eliminar el pago?")) {
			 return true
		  } else {
			 return false
		  }
	  
	  
	  }
      
     

function abreVentana() {
	var miPopup
	miPopup = window.open("crearBanco.php","miwin","width=400,height=300,scrollbars=no")
	miPopup.focus()
}

function cambiar() {
	var index=document.getElementById("cmbFormaPago").selectedIndex
	if (index==0)efectivo()
	if (index==1)cheque()
	if (index==2)deposito()
	if (index==3)transferenciaBancaria()
	if (index==4)tarjetaCredito()
	if (index==5)tarjetaDebito()
	if (index==6)anticipo()
	if (index==7)notaCredito()
}

function efectivo() {
	document.getElementById("bancoCliente").value="";
	document.getElementById("bancoCompania").value="";
	document.getElementById("numeroCuenta").value="";
	document.getElementById("monto").value="";
	document.getElementById("numeroDocumento").value="";
	document.getElementById("txtFechaDeposito").value="";
  
	//document.getElementById("ocultoOpcionCargarCombo").value=1;
	
	document.getElementById("bancoCliente").style.backgroundColor="#EEEEEE";
	document.getElementById("bancoCompania").style.backgroundColor="#EEEEEE"; 
	document.getElementById("numeroCuenta").style.backgroundColor="#EEEEEE";
	document.getElementById("monto").style.backgroundColor="#FFFFFF";
	document.getElementById("numeroDocumento").style.backgroundColor="#EEEEEE";
	
	document.getElementById("crearBanco").style.visibility = "hidden"; 
	//document.getElementById("btnTransaccionesBancarias").style.visibility = "hidden";
	document.getElementById("btnNotaCredito").style.visibility = "hidden";
	//document.getElementById("btnBuscarCheques").style.visibility = "hidden";
	document.getElementById("lblPorcentajeRetencion").style.visibility = "hidden";
	document.getElementById("lblTarjeta").style.visibility = "hidden";
	document.getElementById("lblMontoTotal").style.visibility = "hidden";
	document.getElementById("anticipo").style.visibility = "hidden";
	document.getElementById("porcentajeRetencion").style.visibility = "hidden";
	document.getElementById("lblMontoRetencion").style.visibility = "hidden";
	
	document.getElementById("montoTotal").style.visibility = "hidden";
	document.getElementById("tarjeta").style.visibility = "hidden";
	document.getElementById("tablaDeOpcionesTipoDeCheques").style.visibility = "hidden";
	
	
	document.getElementById("bancoCliente").disabled = true;
	document.getElementById("bancoCompania").disabled = true;
	document.getElementById("numeroCuenta").readOnly = true;
	document.getElementById("monto").readOnly=false;
	document.getElementById("numeroDocumento").readOnly= true;
	
	document.getElementById("Accion").style.visibility = "";
	
	$('bancoCliente').style.display = '';
	$('tblFechaDeposito').style.display = 'none';
	$('tdEtiquetaBancoOFechaDep').innerHTML = 'Banco Cliente:';
	
	$('btnAgregarDetDeposito').style.display = "none";
	
	document.getElementById("txtNombreEtiquetaFormaPagoEscogida").value="Numero:";
	
	document.getElementById("numeroDocumento").style.display = '';
	document.getElementById("txtNumeroDocumento").style.display = 'none';

        document.getElementById("lblPorcentajeComision").style.visibility = "hidden";
        document.getElementById("porcentajeComision").style.visibility = "hidden";
        document.getElementById("lblMontoComision").style.visibility = "hidden";
        document.getElementById("montoTotalComision").style.visibility = "hidden";
	
	document.getElementById("monto").focus();
}

function cheque() {
	document.getElementById("bancoCliente").value="";
	document.getElementById("bancoCompania").value="";
	document.getElementById("numeroCuenta").value="";
	document.getElementById("monto").value="";
	document.getElementById("numeroDocumento").value="";
	document.getElementById("txtFechaDeposito").value="";

	//document.getElementById("ocultoOpcionCargarCombo").value=1;
	
	document.getElementById("bancoCliente").style.backgroundColor="#FFFFFF";
	document.getElementById("bancoCompania").style.backgroundColor="#EEEEEE"; 
	document.getElementById("numeroCuenta").style.backgroundColor="#FFFFFF";
	document.getElementById("monto").style.backgroundColor="#FFFFFF";
	document.getElementById("numeroDocumento").style.backgroundColor="#FFFFFF";
	
	document.getElementById("crearBanco").style.visibility = "hidden"; 
	//document.getElementById("btnTransaccionesBancarias").style.visibility = "hidden";
	document.getElementById("btnNotaCredito").style.visibility = "hidden";
	//document.getElementById("btnBuscarCheques").style.visibility = "visible";
	document.getElementById("lblPorcentajeRetencion").style.visibility = "hidden";
	document.getElementById("lblTarjeta").style.visibility = "hidden";
	document.getElementById("lblMontoTotal").style.visibility = "hidden";
	document.getElementById("anticipo").style.visibility = "hidden";
	document.getElementById("porcentajeRetencion").style.visibility = "hidden";
	document.getElementById("montoTotal").style.visibility = "hidden";
	document.getElementById("tarjeta").style.visibility = "hidden";
	document.getElementById("tablaDeOpcionesTipoDeCheques").style.visibility = "hidden";
	document.getElementById("lblMontoRetencion").style.visibility = "hidden";
	
	
	document.getElementById("bancoCliente").disabled = false;
	document.getElementById("bancoCompania").disabled = true;
	document.getElementById("numeroCuenta").readOnly = false;
	document.getElementById("monto").readOnly=false;
	document.getElementById("numeroDocumento").readOnly=false;
	
	 
	document.getElementById("Accion").style.visibility = "";
	
	$('bancoCliente').style.display = '';
	$('tblFechaDeposito').style.display = 'none';
	$('tdEtiquetaBancoOFechaDep').innerHTML = 'Banco Cliente:';
	
	$('btnAgregarDetDeposito').style.display = "none";
	
	//alert(document.getElementById("ocultoOpcionCargarCombo").value);
	
	document.getElementById("txtNombreEtiquetaFormaPagoEscogida").value="No. Cheque:";
	
	document.getElementById("numeroDocumento").style.display = '';
	document.getElementById("txtNumeroDocumento").style.display = 'none';

        document.getElementById("lblPorcentajeComision").style.visibility = "hidden";
        document.getElementById("porcentajeComision").style.visibility = "hidden";
        document.getElementById("lblMontoComision").style.visibility = "hidden";
        document.getElementById("montoTotalComision").style.visibility = "hidden";
	
	document.getElementById("bancoCliente").focus();
}

function deposito() { 
	document.getElementById("bancoCliente").value="";
	document.getElementById("bancoCompania").value="";
	document.getElementById("numeroCuenta").value="";
	document.getElementById("monto").value="";
	document.getElementById("numeroDocumento").value="";
	document.getElementById("txtFechaDeposito").value="";
	
	//document.getElementById("ocultoOpcionCargarCombo").value="1";
	
	document.getElementById("bancoCliente").style.backgroundColor="#EEEEEE";
	document.getElementById("bancoCompania").style.backgroundColor="#FFFFFF"; 
	document.getElementById("numeroCuenta").style.backgroundColor="#EEEEEE";
	document.getElementById("monto").style.backgroundColor="#FFFFFF";
	document.getElementById("numeroDocumento").style.backgroundColor="#FFFFFF";
	
	document.getElementById("crearBanco").style.visibility = "hidden"; 
	//document.getElementById("btnTransaccionesBancarias").style.visibility = "hidden";
	document.getElementById("btnNotaCredito").style.visibility = "hidden";
	//document.getElementById("btnBuscarCheques").style.visibility = "hidden";
	document.getElementById("lblPorcentajeRetencion").style.visibility = "hidden";
	document.getElementById("lblTarjeta").style.visibility = "hidden";
	document.getElementById("lblMontoTotal").style.visibility = "hidden";
	document.getElementById("anticipo").style.visibility = "hidden";
	document.getElementById("porcentajeRetencion").style.visibility = "hidden";
	document.getElementById("montoTotal").style.visibility = "hidden";
	document.getElementById("tarjeta").style.visibility = "hidden";
	document.getElementById("tablaDeOpcionesTipoDeCheques").style.visibility = "hidden";
	document.getElementById("lblMontoRetencion").style.visibility = "hidden";
	
	
	document.getElementById("bancoCliente").disabled = true;
	document.getElementById("bancoCompania").disabled = false;
	document.getElementById("numeroCuenta").readOnly = true;
	document.getElementById("monto").readOnly=false;
	document.getElementById("numeroDocumento").readOnly= false;
	
	document.getElementById("txtNombreEtiquetaFormaPagoEscogida").value="No. Deposito:";
	
	//alert(document.getElementById("ocultoOpcionCargarCombo").value);
	
	document.getElementById("Accion").style.visibility = "hidden";
	
	
	
	$('bancoCliente').style.display = 'none';
	$('tblFechaDeposito').style.display = '';
	$('tdEtiquetaBancoOFechaDep').innerHTML = 'Fecha Deposito:';
	
	$('btnAgregarDetDeposito').style.display = "";
	
	document.getElementById("numeroDocumento").style.display = '';
	document.getElementById("txtNumeroDocumento").style.display = 'none';

        document.getElementById("lblPorcentajeComision").style.visibility = "hidden";
        document.getElementById("porcentajeComision").style.visibility = "hidden";
        document.getElementById("lblMontoComision").style.visibility = "hidden";
        document.getElementById("montoTotalComision").style.visibility = "hidden";
	
	document.getElementById("bancoCompania").focus();
}

function transferenciaBancaria() {
	document.getElementById("bancoCliente").value="";
	document.getElementById("bancoCompania").value="";
	document.getElementById("numeroCuenta").value="";
	document.getElementById("monto").value="";
	document.getElementById("numeroDocumento").value="";
	document.getElementById("txtFechaDeposito").value="";
	
	//document.getElementById("ocultoOpcionCargarCombo").value=1;
	
	document.getElementById("bancoCliente").style.backgroundColor="#FFFFFF";
	document.getElementById("bancoCompania").style.backgroundColor="#FFFFFF"; 
	document.getElementById("numeroCuenta").style.backgroundColor="#EEEEEE";
	document.getElementById("monto").style.backgroundColor="#FFFFFF";
	document.getElementById("numeroDocumento").style.backgroundColor="#FFFFFF";
	
	document.getElementById("crearBanco").style.visibility = "hidden"; 
	//document.getElementById("btnTransaccionesBancarias").style.visibility = "hidden";
	document.getElementById("btnNotaCredito").style.visibility = "hidden";
	//document.getElementById("btnBuscarCheques").style.visibility = "hidden";
	document.getElementById("lblPorcentajeRetencion").style.visibility = "hidden";
	document.getElementById("lblMontoRetencion").style.visibility = "hidden";
	
	document.getElementById("lblTarjeta").style.visibility = "hidden";
	document.getElementById("lblMontoTotal").style.visibility = "hidden";
	document.getElementById("anticipo").style.visibility = "hidden";
	document.getElementById("porcentajeRetencion").style.visibility = "hidden";
	document.getElementById("montoTotal").style.visibility = "hidden";
	document.getElementById("tarjeta").style.visibility = "hidden";
	document.getElementById("tablaDeOpcionesTipoDeCheques").style.visibility = "hidden";
	
	document.getElementById("bancoCliente").disabled = false;
	document.getElementById("bancoCompania").disabled = false;
	document.getElementById("numeroCuenta").readOnly = true;
	document.getElementById("monto").readOnly=false;
	document.getElementById("numeroDocumento").readOnly= false;
	
	document.getElementById("txtNombreEtiquetaFormaPagoEscogida").value="No. Transferencia:";
	
	
	
	document.getElementById("Accion").style.visibility = "";
	
	$('bancoCliente').style.display = '';
	$('tblFechaDeposito').style.display = 'none';
	$('tdEtiquetaBancoOFechaDep').innerHTML = 'Banco Cliente:';
	
	$('btnAgregarDetDeposito').style.display = "none";
	
	document.getElementById("numeroDocumento").style.display = '';
	document.getElementById("txtNumeroDocumento").style.display = 'none';

        document.getElementById("lblPorcentajeComision").style.visibility = "hidden";
        document.getElementById("porcentajeComision").style.visibility = "hidden";
        document.getElementById("lblMontoComision").style.visibility = "hidden";
        document.getElementById("montoTotalComision").style.visibility = "hidden";
	
	document.getElementById("bancoCliente").focus();
}

function tarjetaCredito() {
	document.getElementById("bancoCliente").value="";
	document.getElementById("bancoCompania").value="";
	document.getElementById("numeroCuenta").value="";
	document.getElementById("monto").value="";
	document.getElementById("numeroDocumento").value="";
	document.getElementById("txtFechaDeposito").value="";
	// document.getElementById("ocultoOpcionCargarCombo").value=2;
	
	document.getElementById("bancoCliente").style.backgroundColor="#FFFFFF";
	document.getElementById("bancoCompania").style.backgroundColor="#FFFFFF"; 
	document.getElementById("numeroCuenta").style.backgroundColor="#EEEEEE";
	document.getElementById("monto").style.backgroundColor="#FFFFFF";
	document.getElementById("numeroDocumento").style.backgroundColor="#FFFFFF";
	
	document.getElementById("crearBanco").style.visibility = "hidden"; 
	// document.getElementById("btnTransaccionesBancarias").style.visibility = "hidden";
	document.getElementById("btnNotaCredito").style.visibility = "hidden";
	//document.getElementById("btnBuscarCheques").style.visibility = "hidden";
	document.getElementById("lblPorcentajeRetencion").style.visibility = "visible";
	document.getElementById("lblTarjeta").style.visibility = "visible";
	document.getElementById("lblMontoTotal").style.visibility = "visible";
	document.getElementById("anticipo").style.visibility = "hidden";
	document.getElementById("porcentajeRetencion").style.visibility = "visible";
	document.getElementById("lblMontoRetencion").style.visibility = "visible";
	
	document.getElementById("montoTotal").style.visibility = "visible";
	document.getElementById("tarjeta").style.visibility = "visible";
	document.getElementById("tablaDeOpcionesTipoDeCheques").style.visibility = "hidden";
	
	document.getElementById("bancoCliente").disabled = false;
	document.getElementById("bancoCompania").disabled = false;
	document.getElementById("numeroCuenta").readOnly = true;
	document.getElementById("monto").readOnly=false;
	document.getElementById("numeroDocumento").readOnly= false;
	
	document.getElementById("txtNombreEtiquetaFormaPagoEscogida").value="No. Tarjeta Credito:";
	
	document.getElementById("Accion").style.visibility = "";
	
	$('bancoCliente').style.display = '';
	$('tblFechaDeposito').style.display = 'none';
	$('tdEtiquetaBancoOFechaDep').innerHTML = 'Banco Cliente:';
	
	$('btnAgregarDetDeposito').style.display = "none";
	
	document.getElementById("numeroDocumento").style.display = '';
	document.getElementById("txtNumeroDocumento").style.display = 'none';

        document.getElementById("lblPorcentajeComision").style.visibility = "visible";
        document.getElementById("porcentajeComision").style.visibility = "visible";
        document.getElementById("lblMontoComision").style.visibility = "visible";
        document.getElementById("montoTotalComision").style.visibility = "visible";
	
	document.getElementById("bancoCliente").focus();
}

function tarjetaDebito() {
	document.getElementById("bancoCliente").value="";
	document.getElementById("bancoCompania").value="";
	document.getElementById("numeroCuenta").value="";
	document.getElementById("monto").value="";
	document.getElementById("numeroDocumento").value="";
	document.getElementById("txtFechaDeposito").value="";
	//document.getElementById("ocultoOpcionCargarCombo").value=1;
	
	document.getElementById("bancoCliente").style.backgroundColor="#FFFFFF";
	document.getElementById("bancoCompania").style.backgroundColor="#FFFFFF"; 
	document.getElementById("numeroCuenta").style.backgroundColor="#EEEEEE";
	document.getElementById("monto").style.backgroundColor="#FFFFFF";
	document.getElementById("numeroDocumento").style.backgroundColor="#FFFFFF";
	
	document.getElementById("crearBanco").style.visibility = "hidden"; 
	//document.getElementById("btnTransaccionesBancarias").style.visibility = "hidden";
	document.getElementById("btnNotaCredito").style.visibility = "hidden";
	//document.getElementById("btnBuscarCheques").style.visibility = "hidden";
	document.getElementById("lblPorcentajeRetencion").style.visibility = "hidden";
	document.getElementById("lblTarjeta").style.visibility = "hidden";
	
	//Ocultado porq supuestamente las tarjetas de debito es como el pago en efectivo.
	document.getElementById("lblMontoTotal").style.visibility = "hidden";
	document.getElementById("anticipo").style.visibility = "hidden";
	document.getElementById("porcentajeRetencion").style.visibility = "hidden";
	document.getElementById("montoTotal").style.visibility = "hidden";
	document.getElementById("tarjeta").style.visibility = "hidden";
	document.getElementById("tablaDeOpcionesTipoDeCheques").style.visibility = "hidden";
	document.getElementById("lblMontoRetencion").style.visibility = "hidden";
	
	
	document.getElementById("bancoCliente").disabled = false;
	document.getElementById("bancoCompania").disabled = false;
	document.getElementById("numeroCuenta").readOnly = true;
	document.getElementById("monto").readOnly=false;
	document.getElementById("numeroDocumento").readOnly= false;
	 
	document.getElementById("Accion").style.visibility = "";
	
	$('bancoCliente').style.display = '';
	$('tblFechaDeposito').style.display = 'none';
	$('tdEtiquetaBancoOFechaDep').innerHTML = 'Banco Cliente:';
	
	$('btnAgregarDetDeposito').style.display = "none";
	
	document.getElementById("txtNombreEtiquetaFormaPagoEscogida").value="No. Tarjeta Debito:";
	
	document.getElementById("numeroDocumento").style.display = '';
	document.getElementById("txtNumeroDocumento").style.display = 'none';

        document.getElementById("lblPorcentajeComision").style.visibility = "visible";
        document.getElementById("porcentajeComision").style.visibility = "visible";
        document.getElementById("lblMontoComision").style.visibility = "visible";
        document.getElementById("montoTotalComision").style.visibility = "visible";
	
	document.getElementById("bancoCliente").focus();
}

function anticipo() {
	document.getElementById("bancoCliente").value="";
	document.getElementById("bancoCompania").value="";
	document.getElementById("numeroCuenta").value="";
	document.getElementById("monto").value="";
	document.getElementById("numeroDocumento").value="";
	document.getElementById("txtFechaDeposito").value="";
	//document.getElementById("ocultoOpcionCargarCombo").value=1;
	
	document.getElementById("bancoCliente").style.backgroundColor="#EEEEEE";
	document.getElementById("bancoCompania").style.backgroundColor="#EEEEEE"; 
	document.getElementById("numeroCuenta").style.backgroundColor="#EEEEEE";
	document.getElementById("monto").style.backgroundColor="#EEEEEE";
	document.getElementById("numeroDocumento").style.backgroundColor="#EEEEEE";
	
	document.getElementById("crearBanco").style.visibility = "hidden"; 
	//document.getElementById("btnTransaccionesBancarias").style.visibility = "hidden";
	document.getElementById("btnNotaCredito").style.visibility = "hidden";
	//document.getElementById("btnBuscarCheques").style.visibility = "hidden";
	document.getElementById("lblPorcentajeRetencion").style.visibility = "hidden";
	document.getElementById("lblTarjeta").style.visibility = "hidden";
	document.getElementById("lblMontoTotal").style.visibility = "hidden";
	document.getElementById("anticipo").style.visibility = "visible";
	document.getElementById("porcentajeRetencion").style.visibility = "hidden";
	document.getElementById("montoTotal").style.visibility = "hidden";
	document.getElementById("tarjeta").style.visibility = "hidden";
	document.getElementById("tablaDeOpcionesTipoDeCheques").style.visibility = "hidden";
	document.getElementById("lblMontoRetencion").style.visibility = "hidden";
	
	
	document.getElementById("bancoCliente").disabled = true;
	document.getElementById("bancoCompania").disabled = true;
	document.getElementById("numeroCuenta").readOnly = true;
	document.getElementById("monto").readOnly=true;
	document.getElementById("numeroDocumento").readOnly=true;
	
	document.getElementById("Accion").style.visibility = "";
	
	$('bancoCliente').style.display = '';
	$('tblFechaDeposito').style.display = 'none';
	$('tdEtiquetaBancoOFechaDep').innerHTML = 'Banco Cliente:';
	
	$('btnAgregarDetDeposito').style.display = "none";
	
	document.getElementById("txtNombreEtiquetaFormaPagoEscogida").value="No. Anticipo:";
	
	document.getElementById("numeroDocumento").style.display = '';
	document.getElementById("txtNumeroDocumento").style.display = 'none';

        document.getElementById("lblPorcentajeComision").style.visibility = "hidden";
        document.getElementById("porcentajeComision").style.visibility = "hidden";
        document.getElementById("lblMontoComision").style.visibility = "hidden";
        document.getElementById("montoTotalComision").style.visibility = "hidden";
	
	document.getElementById("anticipo").focus();
}

function notaCredito() {
	document.getElementById("bancoCliente").value="";
	document.getElementById("bancoCompania").value="";
	document.getElementById("numeroCuenta").value="";
	document.getElementById("monto").value="";
	
	document.getElementById("numeroDocumento").value="";
	
	document.getElementById("txtFechaDeposito").value="";
	//document.getElementById("ocultoOpcionCargarCombo").value=1;
	
	document.getElementById("bancoCliente").style.backgroundColor="#EEEEEE";
	document.getElementById("bancoCompania").style.backgroundColor="#EEEEEE"; 
	document.getElementById("numeroCuenta").style.backgroundColor="#EEEEEE";
	document.getElementById("monto").style.backgroundColor="#EEEEEE";
	document.getElementById("numeroDocumento").style.backgroundColor="#EEEEEE";
	
	document.getElementById("crearBanco").style.visibility = "hidden"; 
	//document.getElementById("btnTransaccionesBancarias").style.visibility = "hidden";
	document.getElementById("btnNotaCredito").style.visibility = "visible";
	//document.getElementById("btnBuscarCheques").style.visibility = "hidden";
	document.getElementById("lblPorcentajeRetencion").style.visibility = "hidden";
	document.getElementById("lblTarjeta").style.visibility = "hidden";
	document.getElementById("lblMontoTotal").style.visibility = "hidden";
	document.getElementById("anticipo").style.visibility = "hidden";
	document.getElementById("porcentajeRetencion").style.visibility = "hidden";
	document.getElementById("montoTotal").style.visibility = "hidden";
	document.getElementById("tarjeta").style.visibility = "hidden";
	document.getElementById("tablaDeOpcionesTipoDeCheques").style.visibility = "hidden";
	document.getElementById("lblMontoRetencion").style.visibility = "hidden";
	
	
	document.getElementById("bancoCliente").disabled = true;
	document.getElementById("bancoCompania").disabled = true;
	document.getElementById("numeroCuenta").readOnly = true;
	document.getElementById("monto").readOnly=true;
	document.getElementById("numeroDocumento").readOnly=true;
	
	document.getElementById("Accion").style.visibility = "";
	
	$('bancoCliente').style.display = '';
	$('tblFechaDeposito').style.display = 'none';
	$('tdEtiquetaBancoOFechaDep').innerHTML = 'Banco Cliente:';
	
	$('btnAgregarDetDeposito').style.display = "none";
	
	document.getElementById("txtNombreEtiquetaFormaPagoEscogida").value="No. Nota Credito:";
	
	document.getElementById("numeroDocumento").style.display = 'none';
	document.getElementById("txtNumeroDocumento").style.display = '';

        document.getElementById("lblPorcentajeComision").style.visibility = "hidden";
        document.getElementById("porcentajeComision").style.visibility = "hidden";
        document.getElementById("lblMontoComision").style.visibility = "hidden";
        document.getElementById("montoTotalComision").style.visibility = "hidden";
	
	document.getElementById("btnNotaCredito").focus();
}
        
function unformatNumber(num) {
	return num.replace(/([^0-9\.\-])/g,'')*1;
} 

function verificarCamposVacios(pagos) {
	var index=document.getElementById("cmbFormaPago").selectedIndex
	var monto=unformatNumber(document.getElementById("monto").value); 
	var saldo=unformatNumber(document.getElementById("saldo").value);
	if (index == 0) { //efectivo
		if(document.getElementById("monto").value!="") {
			if(monto<=saldo) {			  
				return confirmarAgregacionDePago(this)
				return true
			} else {
				alert("El Monto En Efectivo debe ser Menor o Igual al Saldo");
				document.getElementById("monto").focus();
				return false
			}   
		} else {
			alert("Digite el Monto a pagar");
			document.getElementById("monto").focus();
			return false
		}   
	}
	if (index==1) { //cheque
		if (document.getElementById("monto").value!=""
		&& document.getElementById("bancoCliente").value!=""
		&& document.getElementById("numeroCuenta").value!=""
		&& document.getElementById("numeroDocumento").value!=""
		&& document.getElementById("bancoCliente").value!="otros") { //uno de los dos tiene q ser chequeado && document.getElementById("ocultoTipoDeCheque").value!=""
		//true esta chequeado.
			
			if(monto<=saldo) {
				return confirmarAgregacionDePago(this)
				return true
			} else {
				alert("El Monto del cheque debe ser Menor o Igual al Saldo");
				document.getElementById("monto").focus();
				return false
			}   
		} else {
			if (document.getElementById("bancoCliente").value=="otros") {
				alert("Escogi� la opcion Otros en Banco Cliente, si ya creo el Banco escojalo de la lista");
				document.getElementById("bancoCliente").focus();
				return false
			} else {
				alert("Los siguientes campos No pueden ser vacios: \n  - Banco Cliente \n  - Nro. Cuenta \n  - Nro. Cheque \n  - Monto a Pagar");//- Tipo Cheque \n
				//document.getElementById("monto").focus();
				return false
			}
		}   
	}
	if (index==2) { //Deposito
		if((document.getElementById("monto").value!="")
		&& (document.getElementById("numeroDocumento").value!="")
		&& (document.getElementById("bancoCompania").value!="")) {
			if(monto <= saldo) {
				return confirmarAgregacionDePago(this)
				return true
			} else {
				alert("El Monto a depositar debe ser Menor o Igual al Saldo");
				document.getElementById("monto").focus();
				return false
			}  
		} else {
			alert("Los siguientes campos No pueden ser vacios: \n  - Banco Compa�ia \n  - No Dep�sito \n  - Monto a Pagar");
			//document.getElementById("monto").focus();
			return false
		}   
	}
	if (index==3) {//Transferencia bancaria
		if((document.getElementById("monto").value!="")
		&& (document.getElementById("numeroDocumento").value!="")
		&& (document.getElementById("bancoCompania").value!="")
		&& (document.getElementById("bancoCliente").value!=""
		&& document.getElementById("bancoCliente").value!="otros")) {
			if(monto<=saldo) {
				return confirmarAgregacionDePago(this)
				return true
			} else {
				alert("El Monto a Transferir debe ser Menor o Igual al Saldo");
				document.getElementById("monto").focus();
				return false
			}  
		} else {
			if(document.getElementById("bancoCliente").value=="otros") {
				alert("Escogi� la opcion Otros en Banco Cliente, si ya creo el Banco escojalo de la lista");
				document.getElementById("bancoCliente").focus();
				return false				 
			} else {
				alert("Los siguientes campos No pueden ser vacios: \n  - Banco Cliente \n  - Banco Compa�ia \n  - No Transferencia \n  - Monto a Pagar");
				//document.getElementById("monto").focus();
				return false
			}			
		}   		  
	}
	if (index==4) {//Tarjeta Credito		  
		if((document.getElementById("monto").value!="")
		&& (document.getElementById("numeroDocumento").value!="")
		&& (document.getElementById("bancoCompania").value!="")
		&& (document.getElementById("bancoCliente").value!="")
		&& (document.getElementById("tarjeta").value!="")
		&& (document.getElementById("porcentajeRetencion").value!="")) {
			if(monto<=saldo) {
				return confirmarAgregacionDePago(this)
				return true
			} else {
				alert("El Monto debe ser Menor o Igual al Saldo");
				document.getElementById("monto").focus();
				return false
			}  
		} else {
			if (document.getElementById("bancoCliente").value=="otros") {
				alert("Escogio la opcion Otros en Banco Cliente, si ya creo el Banco escojalo de la lista");
				document.getElementById("bancoCliente").focus();
				return false
			} else {
				alert("Los siguientes campos No pueden ser vacios: \n  - Banco Cliente \n  - Banco Compa�ia \n  - No Tarjeta Credito \n  - Tarjeta \n  - Monto a Pagar \n  - Tipo Tarjeta Credito");
				//document.getElementById("monto").focus();
				return false
			}
		}   
		  
		  }
		  if (index==4)//Tarjeta Debito
		  {
		    if((document.getElementById("monto").value!="") && (document.getElementById("numeroDocumento").value!="") && (document.getElementById("bancoCompania").value!="") && (document.getElementById("bancoCliente").value!="" && document.getElementById("bancoCliente").value!="otros"))
			{
			    if(monto<=saldo)
			    {
				   return confirmarAgregacionDePago(this)
				   return true
				}
				else
				{
				  alert("El Monto debe ser Menor o Igual al Saldo");
				  document.getElementById("monto").focus();
				  return false
				}  
			}
			else
			{
			        if(document.getElementById("bancoCliente").value=="otros")
				 {
				     alert("Escogi� la opcion Otros en Banco Cliente, si ya creo el Banco escojalo de la lista");
					 document.getElementById("bancoCliente").focus();
					 return false
				 
				 }
			     else
				 {
					  alert("Los siguientes campos No pueden ser vacios: \n  - Banco Cliente \n  - Banco Compa�ia \n  - No Tarjeta Debito \n  - Monto a Pagar");
					  //document.getElementById("monto").focus();
					  return false
				 }
			
			}   
		  
		  }
		  if (index==5)//Anticipo
		  {
		    if((document.getElementById("monto").value!=""))
			{
			   return confirmarAgregacionDePago(this)
			   return true
			}
			else
			{
			      
				  alert("Busque el anticipo que desea asignar");
				  document.getElementById("anticipo").focus();
				  return false
			
			}   
		  
		  }
		   if (index==6)//Nota Credito
		  {
		    if((document.getElementById("monto").value!=""))
			{
			   return confirmarAgregacionDePago(this)
			   return true
			}
			else
			{
			      
				  alert("Busque la Nota Credito que desea asignar");
				  document.getElementById("btnNotaCredito").focus();
				  return false
			
			}   
		  
		  }
		
		
		  
	  }
      
function tick()
{
	var hora, minuto, segundo, meridiano, hoy
	hoy=new Date()
	hora1=hoy.getHours()
	minuto1=hoy.getMinutes()
	segundo1=hoy.getSeconds()
	
	switch(hora1)
	{//SWITCH significa Segun sea hora....
		case 0://12 de la madrugada en hora militar
		hora1=12
		hora=hora1+":"
		meridiano="a.m"
		break
		
		case 12:
		hora=hora1+":"
		meridiano="p.m"
		break
		
		case 24:
		hora=hora1+":"
		meridiano="a.m"
		break
		
		default:
		if(hora1>12)
		{
		  hora1=hora1-12// por ejemplo hora1 es 13 que s la 1 entonces 13-12=1
		  hora=hora1+":"
		  meridiano="p.m"
		  break
		}
		if(hora1<12)
		{
		  hora=hora1+":"
		  meridiano="a.m"
		  break
		}
    }//Final del Switch
	
if(minuto1<10){
minuto="0"+minuto1+":"
}
else
{
minuto=minuto1+":"
}
if(segundo1<10){
segundo="0"+segundo1+" "
}
else
{
segundo=segundo1+" "
}
cadenaTiempo=hora+minuto+segundo+meridiano
document.getElementById("Clock").innerHTML=cadenaTiempo
window.setTimeout("tick()",100)}//Actualizar la ventana cxon el objeto window y que la actualice cada 100 milisegundos
//si coloco 5000 para que actualice cada 5 segundos
//Va actualizar la funcion solamente
//document.getElementById("txtHoraActual").value=tick();
