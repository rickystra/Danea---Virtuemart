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
}
?>
