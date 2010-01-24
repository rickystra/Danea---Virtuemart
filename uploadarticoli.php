<?php
if (base64_decode($_SERVER["HTTP_X_AUTHORIZATION"]) == ("username:password")) { 
	if (move_uploaded_file ($_FILES['file']['tmp_name'], "articoli.xml")){
		include('full.php');
		echo "OK";
	} else {
		echo "Error";
	}
} else {
	echo "Utente o Password non valida";
}/*
$mysqli = new mysqli('localhost', 'web832u1', 'antico2603', 'web832db1');
$mysqli->query("INSERT INTO jos_vm_product VALUES('','1','1','0003','','','','','','','','','','','','','','','','','','','','','pantalone','','','','','','','','','','')");
if(file_exists('articoli.xml'))
$xml = simplexml_load_file('articoli.xml');*/
?>
