<?php
/**

 FUNCIONES PARA EL ENVÍO DE CORREO MEDIANTE SOCKET.

 +-------------------------------------------------------------------------+
 | (C) 2016-2017 Jorge Balanz                                              |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by Jorge Balanz.  	   |
 +-------------------------------------------------------------------------+
 | More information about Cacti: http://www.cacti.net/                     |
 +-------------------------------------------------------------------------+
*/

/**
 * Enviar un correo a través de una conexión SMTP
 *
 * @param string $to
 *        	Lista de destinatarios separados por comas
 * @param string $from
 *        	Remitente del mensaje
 * @param string $subject
 *        	Asunto del mensaje
 * @param string $message
 *        	Cuerpo del mensaje
 * @param array $st_settings
 *        	(Opcional) Configuración del plugin para enviar mensajes.
 *        	Debe contar con los siguientes campos:
 *        	['mail_list'] -> Lista de destinatarios separada por comas o 'default' si se desea usar la lista de Thold
 *        	['mailoption'] -> Opción para el envío de correos: 'serviceTest' para usar función de este plugin o 'settings' para utilizar la función de ese plugin.
 *        	['mail_server'] -> Nombre o IP del servidor de correo SMTP.
 *        	['mail_port'] -> Puerto del servidor de correo.
 *        	['mail_auth_required'] (Opcional) Indica si el servidor requiere autenticación. Puede ser 'SSL/TLS' o 'none' (por defecto 'none').
 *        	['mail_user'] (Opcional) Nombre de usuario para la autenticación contra el servidor de correo.
 *        	['mail_pwd'] (Opcional) Contraseña para la autenticación contra el servidor de correo.
 *        	['from'] (Opcional) Remitente.
 *        	['cc'] (Opcional) Lista de destinatarios en copia.
 *        	['bcc'] (Opcional) Lista de destinatarios en copia oculta.
 *        	['reply_to'] (Opcional) Dirección de respuesta.
 * @return string Un mensaje de error en caso de existir o una cadena vacía en caso de no existir errores.
 */
function st_send_mail($to, $from, $subject, $message, $st_settings) {
	$dialogo = array ();
	// Cargamos las configuraciones del plugin si no se han recibido:
	if (empty ( $st_settings )) {
		$data = db_fetch_assoc ( "SELECT * FROM serviceTest_settings WHERE id=1" );
		if (! $data) {
			return "There is not information available for sending mails.";
		} else {
			$data = $data [0];
		}
	} else {
		$data = $st_settings;
	}
	// Vemos si hay datos para continuar:
	if (empty ( $data )) {
		return "Unable to load plugin settings before send mail.";
	}
	//var_dump($data);
	//Preparamos el FROM:
	$mydomain = "servicetest.cacti.net";
	$myfrom = "<noreply@$mydomain>";
	if(!empty($from)){
		$from = "[$from] $myfrom";
	}
	elseif (!empty ( $data ['from'] )){
		$from = "[".$data['from']."] $myfrom";
	}
	else {
		$from = $myfrom;
	}
	
	// Preparamos las cabeceras (datos del correo)
	$cabeceras = "";
	// Las cabeceras empiezan con el From!
	$cabeceras .= "From: " . $from . "\r\n";
	$cabeceras .= 'To: ' . $to . "\r\n"; // "To" es el primer parámetro
	$cabeceras .= "Subject: $subject\r\n"; // Subject: Cacti Test Message
	$cabeceras .= (empty ( $data ['cc'] ) ? "" : "Cc: " . $data ['cc'] . "\r\n");
	$cabeceras .= (empty ( $data ['bcc'] ) ? "" : "Bcc: " . $data ['bcc'] . "\r\n");
	$cabeceras .= (empty ( $data ['reply_to'] ) ? "" : "Reply-To: " . $data ['reply_to'] . "\r\n");
	$cabeceras .= "X-Mailer: Cacti/ServiceTest-Plugin\r\n";
	$cabeceras .= "Return-Path: $myfrom\r\n";
	$cabeceras .= "Reply-To: $myfrom\r\n";
	$cabeceras .= 'MIME-Version: 1.0' . "\r\n";
	$cabeceras .= 'Content-type: text/html; charset="utf-8"' . "\r\n"; // Formato HTML!!
	$cabeceras .= "Sensitivity: company\n"; // personal, private, company, confidential
	$cabeceras .= 'Message-ID: <' . $_SERVER ['REQUEST_TIME'] . '_' . md5 ( $_SERVER ['REQUEST_TIME'] ) . '@' . $_SERVER ['SERVER_NAME'] . '>' . "\r\n";
	$cabeceras .= "\r\n"; // Terminamos cabecera con línea en blanco!
	                      
	// Empezamos a procesar el correo contra servidor de SMTP:
	                      
	// Vemos si se puede abrir un socket
	if (! function_exists ( "fsockopen" )) {
		return "Required function 'fsockopen' is not available.";
	}
	
	/* Abrir la conexión SMTP: el servidor puede llevar ssl:// o tls:// según el tipo servicio */
	switch ($data ['mail_auth_required']) {
		case 'SSL/TLS' :
			$srv = "ssl://" . $data ['mail_server'];
			break;
		default :
			$srv = $data ['mail_server'];
	}
	if (($smtp_sock = @fsockopen ( $srv, $data ['mail_port'], $errno, $errstr, 1 )) === false) {
		return "Unable to connect to SMTP Host '" . $srv . ":" . $data ["mail_port"] . "': (" . $errno . ") " . $errstr;
	}
	
	/* Esperamos respuesta del servidor... */
	$smtp_response = fgets ( $smtp_sock, 4096 );
	if (substr ( $smtp_response, 0, 3 ) != "220") {
		fclose ( $smtp_sock );
		return "SMTP host: " . htmlentities($smtp_response);
	}
	
	/* Comenzamos el diálogo SMTP */
	fputs ( $smtp_sock, "HELO " . $mydomain . "\r\n" );
	// fputs($smtp_sock, "EHLO " . $data["mail_server"] . "\r\n"); //Opción con EHLO
	$smtp_response = fgets ( $smtp_sock, 4096 );
	if (substr ( $smtp_response, 0, 3 ) != "250") {
		fclose ( $smtp_sock );
		return "SMTP host: " . htmlentities($smtp_response);
	}
	
	/* Llevar a cabo la autenticación (si se precisa) */
	if ($data ['mail_auth_required'] === 'SSL/TLS') {
		if (! $data ['mail_user']) {
			return "User name required for authentication against SMTP host.";
		}
		if (! $data ['mail_pwd']) {
			return "Password required for authentication against SMTP host.";
		}
		
		fputs ( $smtp_sock, "AUTH LOGIN\r\n" ); // Decimos al servidor que vamos a autenticarnos...
		$smtp_response = fgets ( $smtp_sock, 4096 );
		if (substr ( $smtp_response, 0, 3 ) != "334") {
			fclose ( $smtp_sock );
			return "SMTP Host does not appear to support authenication: " . htmlentities($smtp_response);
		}
		
		fputs ( $smtp_sock, base64_encode ( $data ['mail_user'] ) . "\r\n" );
		$smtp_response = fgets ( $smtp_sock, 4096 );
		if (substr ( $smtp_response, 0, 3 ) != "334") {
			fclose ( $smtp_sock );
			return "SMTP Authenication failure: " . htmlentities($smtp_response);
		}
		
		fputs ( $smtp_sock, base64_encode ( $data ['mail_pwd'] ) . "\r\n" );
		$smtp_response = fgets ( $smtp_sock, 4096 );
		if (substr ( $smtp_response, 0, 3 ) != "235") {
			fclose ( $smtp_sock );
			return "SMTP Authenication failure: " . htmlentities($smtp_response);
		}
	}
	
	/* Indicar origen del mensaje (En las cabeceras) */
	fputs ( $smtp_sock, "MAIL FROM: $myfrom\r\n" );
	$smtp_response = fgets ( $smtp_sock, 4096 );
	if (substr ( $smtp_response, 0, 3 ) != "250") {
		fclose ( $smtp_sock );
		return "SMTP Host rejected from address '".htmlentities($from)."': " . htmlentities($smtp_response);
	}
	
	/* Enviar la lista de destinatarios */
	$to = explode ( ",", $to );
	//Se hace un 'RCPT TO' por cada destinatario...
	foreach ( $to as $item ) {
		$item = trim($item);
		if (! empty ( $item )) {
			if (! strpos ( $item, "<" )) {
				$item = "<" . $item . ">"; // Se mete la dirección entre signos < >
			}
			fputs ( $smtp_sock, "RCPT TO: " . $item . "\r\n" );
			$smtp_response = fgets ( $smtp_sock, 4096 );
			if (substr ( $smtp_response, 0, 3 ) != "250") {
				fclose ( $smtp_sock );
				return "SMTP Host rejected to address '".htmlentities($item)."': " . htmlentities($smtp_response);
			}
		}
	}
	
	/* Enviar la señal DATA para dar comienzo al envío del mensaje */
	fputs ( $smtp_sock, "DATA\r\n" );
	$smtp_response = fgets ( $smtp_sock, 4096 );
	if (substr ( $smtp_response, 0, 3 ) != "354") {
		fclose ( $smtp_sock );
		return "SMTP host rejected '<i>DATA</i>' command: " . htmlentities($smtp_response);
	}
	
	/* Enviar las cabeceras junto con el mensaje */
	$message = str_replace ( "\n", "\r\n", $message ); // Se añade un retorno de carro a los saltos de línea
	$message = $cabeceras . "\r\n" . $message; // Se insertan cabeceras
	$message .= "\r\n.\r\n"; // se agrega "Fin del mensaje" (Un punto solo en una línea)
	fputs ( $smtp_sock, $message ); // Se escribe todo en el socket!
	$smtp_response = fgets ( $smtp_sock, 4096 ); // Esperamos respuesta...
	if (substr ( $smtp_response, 0, 3 ) != "250") {
		fclose ( $smtp_sock );
		return "SMTP error while sending email: <br/>\n".htmlentities($message)."<br/>" . htmlentities($smtp_response);
		;
	}
	
	/* Cerrar la sesión */
	fputs ( $smtp_sock, "QUIT\r\n" );
	$smtp_response = fgets ( $smtp_sock, 4096 );
	if (substr ( $smtp_response, 0, 3 ) != "221") {
		fclose ( $smtp_sock );
		return "SMTP Host rejected quit command: " . htmlentities($smtp_response);
	}
	
	/* Cerrar la conexión */
	fclose ( $smtp_sock );
	unset ( $smtp_sock );
	return "";
}

?>