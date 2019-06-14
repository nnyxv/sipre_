<?php

session_start();

if(isset($_SESSION["listadoAgenda"]) && $_SESSION["listadoAgenda"] != ""){
        
    headerTxt();
    
    $lineasDescarga = $_SESSION["listadoAgenda"];
    
    foreach($lineasDescarga as $arraylinea){
        echo implode("",$arraylinea)."\r\n";
    }
        
    $_SESSION["listadoAgenda"] = "";
    
}else{
    $_SESSION["listadoAgenda"] = "";
    echo "<script>";
    echo "alert('no se ha enviado datos a descargar');";
    echo "window.history.back();";
    echo "</script>";
}


function headerTxt(){
    header("Content-Type:text/plain");
    header("Content-Disposition: attachment; filename=archivo_upload_agenda.txt");
    header("Pragma: no-cache");
    header("Expires: 0");
}