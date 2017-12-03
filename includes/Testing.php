<?php
/**

 Testing: CLASE QUE REPRESENTA CUALQUIERA DE LOS SERVICIOS QUE SE PUEDEN CHEQUEAR.

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
 * Testing: clase que representa cualquiera de los servicios que el plugin serviceTest puede chequear.
 * Los métodos de clase que representan los diferentes chequeos soportados, retornan un vector con los siguientes elementos:
 * 'ok' => Resultado del test, que puede ser Testing::STATUS_UP, Testing::STATUS_DOWN o Testing::STATUS_UNKNOWN
 * 'code' => Código (de error o no) devuelto por la función que intenta la conexión con el servidor o por el servidor mismo.
 * 'response' => Mensaje devuelto por la función que intenta realizar la petición al servidor o por el servidor mismo.
 */
class Testing {
	
	/**
	 * Estado del servicio OK!
	 */
	const STATUS_UP = "UP";
	/**
	 * Estado del servicio CAÍDO!
	 */
	const STATUS_DOWN = "DOWN";
	/**
	 * Estado del servicio DESCONOCIDO!
	 */
	const STATUS_UNKNOWN = "UNKNOWN";
	
	/**
	 * Guardar todos los logs
	 */
	const LOG_LEVEL_ALL = "ALL";
	/**
	 * Guardar sólo los logs de error
	 */
	const LOG_LEVEL_ERROR = "ERROR";
	
	/**
	 * Cadena a buscar en la respuesta de petición HTTP
	 */
	const HTTP_CAD_CODE = "HTTP/";
	
	/**
	 * Identificador del servicio
	 */
	private $id = '-none-';
	/**
	 * Servicio a chequear
	 */
	private $service = '';
	/**
	 * Servidor que ofrece el servicio
	 */
	private $server = '';
	/**
	 * Puerto en el que el servicio está a la escucha
	 */
	private $port = 0;
	/**
	 * Código que se considera correcto para el servicio chequeado
	 */
	private $ok_code = 0;
	/**
	 * Usuario para la conexión (Opcional)
	 */
	private $user = '';
	/**
	 * Contraseña para la conexión (Opcional)
	 */
	private $pwd = '';
	/**
	 * Estado del servicio antes de hacer el test
	 */
	private $last_check = Testing::STATUS_UNKNOWN;
	/**
	 * Considerar Unknown las respuestas diferentes a OK y a ERROR y lanzar Warning
	 */
	private $notify_unknown = '';
	/**
	 * Ver si es preciso realizar un test de este servicio
	 */
	private $enabled = false;
	
	/**
	 * Array de campos de los que se cogen datos
	 */
	private $campos = array (
			'id',
			'service',
			'server',
			'port',
			'ok_code',
			'user',
			'pwd',
			'last_check',
			'enabled' 
	);
	
	/**
	 * Respuesta del servidor o la aplicación.
	 * Vacío, al menos, hasta que se pasa el test.
	 */
	private $response = '';
	/**
	 * Código devuelto por el servidor o la aplicación (puede estar vacío).
	 * Tiene el valor booleano false hasta que se pasa el test.
	 */
	private $code = false;
	/**
	 * Valor que indica el estado del servicio, al comparar la respuesta con el código buscado.
	 * Puede adoptar uno de los siguientes valores:
	 * Testing::STATUS_UP = Servicio Correcto;
	 * Testing::STATUS_DOWN = Servicio Caído;
	 * Testing::STATUS_UNKNOWN = Servicio en estado indeterminado;
	 * Tiene el valor booleano 'False', por defecto (inicialmente, antes de chequear el estado).
	 */
	private $status = false;
	
	/**
	 * Datos de configuración del plugin
	 */
	private static $settings = null;
	
	/**
	 * Constructor de la clase.
	 * Recibe como parámetro un array con los datos para el Test. *
	 * 
	 * @param $data Vector
	 *        	con los siguientes elementos:
	 *        	['id'] Identificador del servicio a chequear;
	 *        	['service'] Servicio a chequear;
	 *        	['server'] Servidor que ofrece el servicio
	 *        	['port'] Puerto en el que el servicio está a la escucha
	 *        	['ok_code'] Código que se considera correcto para el servicio chequeado
	 *        	['user'] Usuario para la conexión (Opcional)
	 *        	['pwd'] Contraseña para la conexión (Opcional)
	 *        	['last_check'] Estado del servicio antes de crear el objeto
	 *        	['notify_unknown'] Considerar Unknown las respuestas diferentes a OK y lanzar Warning
	 */
	function __construct($data) {
		// Si es un entero, recuperamos sus datos de la BD
		if (is_int ( $data )) {
			// Recuperamos el elemento a chequear:
			$query = "SELECT id, name as server, ST.service as service, user, pwd, port, default_port, ";
			$query .= "ST.enabled as enabled, ok_code, last_check ";
			$query .= "FROM serviceTest ST INNER JOIN serviceTest_services STS on ST.service=STS.service ";
			$query .= "where id=$data";
			$data = db_fetch_assoc ( $query );
			// Comprobamos que se haya recuperado algo...
			if (! empty ( $data )) {
				$data = $data [0];
			}
		}
		// Si es un array, cargamos los datos:
		if (is_array ( $data )) {
			foreach ( $this->campos as $k ) {
				if (isset ( $data [$k] )) {
					$this->{$k} = $data [$k];
				}
			}
		}
		// Cargamos los datos de configuración en la variable e clase
		Testing::loadSettings ();
	}
	
	/**
	 * *****************************************************************************
	 * SECCIÓN LANZAR TESTS
	 * *****************************************************************************
	 */
	
	/**
	 * Lanzar un test sobre el servicio que representa este objeto.
	 * 
	 * @return Un vector con los siguientes elementos:
	 *         'ok' => Resultado del test, que puede ser Testing::STATUS_UP, Testing::STATUS_DOWN o Testing::STATUS_UNKNOWN
	 *         'code' => Código (de error o no) devuelto por la función que intenta la conexión con el servidor o por el servidor mismo.
	 *         'response' => Mensaje devuelto por la función que intenta realizar la petición al servidor o por el servidor mismo.
	 *         Si el test no puede ser realizado (por ejemplo, por falta de datos requeridos) el estado del servicio pasará a ser Testing::STATUS_UNKNOWN
	 */
	function test() {
		// Controlamos que estén todos los datos necesarios:
		if (! $this->enabled) {
			Testing::catch_log ( " - Warning: You are trying to check a service [#" . $this->id . "] which is not enabled for checking." );
			$this->setStatus ( false, false, '' );
			return null;
		}
		if (! $this->server) {
			Testing::catch_log ( " - ERROR: The required server name is not set for service #" . $this->id . "." );
			$this->setStatus ( false, false, '' );
			return null;
		}
		if (! $this->port) {
			Testing::catch_log ( " - ERROR: The required port number is not set for service #" . $this->id . " (" . $this->server . ")." );
			$this->setStatus ( false, false, '' );
			return null;
		}
		if (! $this->service) {
			Testing::catch_log ( " - ERROR: The required service type is not set for service #" . $this->id . " (" . $this->server . ")." );
			$this->setStatus ( false, false, '' );
			return null;
		}
		
		// Empezamos el chequeo
		$servidor = $this->server . ":" . $this->port;
		$resultado = null;
		switch (strtoupper ( $this->service )) { // HAGO UN SWITCH-CASE para futuras ampliaciones
			case 'HTTP' :
			case 'HTTPS' :
				$resultado = Testing::http_check ( $this->server, $this->port, $this->service, $this->ok_code );
				break;
			case 'MYSQL' :
				$resultado = Testing::mysql_check ( $this->server, $this->port, $this->user, $this->pwd );
				break;
			case 'ORACLE' :
				$resultado = Testing::oracle_check ( $this->server, $this->port, $this->user, $this->pwd );
				break;
			default:
				Testing::catch_log("[ID#{$this->id}; {$this->service}@{$this->server}] ERROR: Checking for service '{$this->service}' is not implemented.");
				$resultado = array('ok' => Testing::STATUS_UNKNOWN, 'code' => 1001, 'response' => "Checking for service '{$this->service}' is not implemented.");
		} // End switch-case
		
		//Eliminamos posibles saltos de línea de la respuesta
		$resultado['response'] = str_replace(array("\r\n","\n","\t"), "; ", $resultado['response']);
		//Recortamos la longitud de la respuesta a 90 caracteres (añadimos '...'):
		if(strlen($resultado['response'])>90)
			$resultado['response'] = substr($resultado['response'], 0, 90)."...";
		
		//Almacenamos el resultado:
		$this->setStatus ( $resultado ['ok'], $resultado ['code'], $resultado ['response'] );
		
		// Ahora lo retornamos.
		return $this->getStatus ();
	}
	
	/**
	 * Lanzar un test sobre el servicio que representa este objeto (alias de test()).
	 * 
	 * @return Un vector con los siguientes elementos:
	 *         'ok' => Resultado del test, que puede ser Testing::STATUS_UP, Testing::STATUS_DOWN o Testing::STATUS_UNKNOWN
	 *         'code' => Código (de error o no) devuelto por la función que intenta la conexión con el servidor o por el servidor mismo.
	 *         'response' => Mensaje devuelto por la función que intenta realizar la petición al servidor o por el servidor mismo.
	 */
	function check() {
		return $this->test ();
	}
	/**
	 * Lanzar un test sobre el servicio que representa este objeto (alias de test()).
	 * 
	 * @return Un vector con los siguientes elementos:
	 *         'ok' => Resultado del test, que puede ser Testing::STATUS_UP, Testing::STATUS_DOWN o Testing::STATUS_UNKNOWN
	 *         'code' => Código (de error o no) devuelto por la función que intenta la conexión con el servidor o por el servidor mismo.
	 *         'response' => Mensaje devuelto por la función que intenta realizar la petición al servidor o por el servidor mismo.
	 */
	function checkService() {
		return $this->test ();
	}
	
	/**
	 * Evaluar el resultado del test, si se ha realizado.
	 * Si se encuentran errores o resultados diferentes a los esperados, se guardará
	 * el resultado en los logs y si hay cambio de estado se enviará un correo a los
	 * destinatarios configuracos en el plugin.
	 *
	 * @return Un vector con los siguientes elementos:
	 *         'ok' => Resultado del test, que puede ser Testing::STATUS_UP, Testing::STATUS_DOWN o Testing::STATUS_UNKNOWN
	 *         'code' => Código (de error o no) devuelto por la función que intenta la conexión con el servidor o por el servidor mismo.
	 *         'response' => Mensaje devuelto por la función que intenta realizar la petición al servidor o por el servidor mismo.
	 */
	function evaluate() {
		// Comprobamos si ya ha sido evaluado:
		if ($this->isNotTested ()) {
			return array (
					'ok' => false,
					'code' => false,
					'response' => 'ERROR: The service has not been tested yet.' 
			);
		}
		$log = ""; // Mensaje a enviar al log (en su caso)
		           // Comprobamos los resultados contenidos en el vector con 3 elementos:
		           // 'ok' => Resultado del test, que puede ser Testing::STATUS_UP, Testing::STATUS_DOWN o Testing::STATUS_UNKNOWN
		           // 'code' => Código (de error o no) devuelto por la función que intenta la conexión con el servidor o por el servidor mismo.
		           // 'response' => Mensaje devuelto por la función que intenta realizar la petición al servidor o por el servidor mismo.
		switch ($this->status) {
			case Testing::STATUS_UP :
				if ($this->last_check !== Testing::STATUS_UP) { // Si el estado anterior no era bueno, es que se ha levantado...
				                                              // Lanzamos alerta (log_cacti + mail)
					$this->launch_alert ( $this->server, $this->service, $this->response, Testing::STATUS_UP );
					// Actualizamos el servicio en la BD
					db_execute ( "UPDATE serviceTest SET last_check='" . Testing::STATUS_UP . "' WHERE id=" . $this->id );
				}
				$log = " [" . $this->service . " @ " . $this->server . ":" . $this->port . "] - STATS: " . $this->response;
				break;
			case Testing::STATUS_DOWN :
				if ($this->last_check !== Testing::STATUS_DOWN) { // Si el estado anterior no era malo, es que ha caído...
					$this->launch_alert ( $this->server, $this->service, $this->response, Testing::STATUS_DOWN );
					db_execute ( "UPDATE serviceTest SET last_check='" . Testing::STATUS_DOWN . "' WHERE id=" . $this->id );
				}
				$log = " [" . $this->service . " @ " . $this->server . ":" . $this->port . "] - ERROR [#" . $this->code . "]: " . $this->response;
				break;
			case Testing::STATUS_UNKNOWN :
			default :
				if ($this->last_check !== "UNKNOWN") { // Si el estado anterior no era desconocido, es que ha cambiado...
					$this->launch_alert ( $this->server, $this->service, $this->response, Testing::STATUS_UNKNOWN );
					db_execute ( "UPDATE serviceTest SET last_check='" . Testing::STATUS_UNKNOWN . "' WHERE id=" . $this->id );
				}
				$log = " [" . $this->service . " @ " . $this->server . ":" . $this->port . "] - WARNING [#" . $this->code . "]: " . $this->response;
		}
		// Guardarlo en los Log si el resultado no es OK o si es OK y se debe registrar todo (por defecto, todo).
		if ($this->status !== Testing::STATUS_UP || empty ( Testing::$settings ['log_level'] ) || Testing::$settings ['log_level'] === Testing::LOG_LEVEL_ALL) {
			// Guardar en el log de Cacti
			Testing::catch_log ( $log );
		}
		// Retornamos los resultados, por si acaso:
		return $this->getStatus ();
	}
	
	/**
	 * **************************************************************************************************
	 * REALIZAR TESTS
	 * *************************************************************************************************
	 */

	// ////////////////////////////////////////////////////////////////////////
	// CHECK HTTP, HTTPS //
	// ////////////////////////////////////////////////////////////////////////
	/**
	 * Lanzar un test del servicio "HTTP" o "HTTPS" utilizando WGET
	 * 
	 * @param $server Nombre
	 *        	o IP del servidor contra el que se lanza la petición
	 * @param $port (Opcional)
	 *        	Puerto del servidor contra el que se lanza la petición. Por defecto, 80.
	 * @param $service (Opcional)
	 *        	Tipo de servicio: "HTTP", "HTTPS" o "JBOSS". Por defecto, "HTTP"
	 * @param $ok_code (Opcional)
	 *        	Código que se considera correcto. Por defecto, 200.
	 * @return Un array de tres componentes:
	 *         ['response'] -> Respuesta del servidor o la aplicación.
	 *         ['code'] -> Código devuelto por el servidor o la aplicación (puede estar vacío).
	 *         ['ok'] -> String que indica si es resultado es positivo (Testing::STATUS_UP: la respuesta es la esperada), negativo (Testing::STATUS_DOWN: no hay respuesta) o desconocido (Testing::STATUS_UNKNOWN: hay respuesta pero no la esperada).
	 */
	static function http_check($server, $port = 80, $service = "HTTP", $ok_code = 200) {
		if (extension_loaded ( 'curl' ))
			$response = Testing::HTTP_curl ( $server, $port, $service, $ok_code );
		else {
			$response = Testing::HTTP_cmd ( $server, $port, $service, $ok_code );
		}
		return $response;
	}
	
	/**
	 * Preparar una cadena para lanzar una petición WEB usando PHP-CURL o WGet
	 * 
	 * @param $server Nombre
	 *        	o IP del servidor contra el que se lanza la petición
	 * @param $port (Opcional)
	 *        	Puerto del servidor contra el que se lanza la petición. Por defecto, 80.
	 * @param $service (Opcional)
	 *        	Tipo de servicio: "HTTP" o "HTTPS". Por defecto, "HTTP"
	 * @param $curl (Opcional)
	 *        	Boolean que indica si se debe emplear CURL o WGET (por defecto, CURL)
	 * @return String con la cadena para lanzar la petición.
	 */
	static function prepare_url($server, $port = 80, $service = "HTTP", $curl = true) {
		$url = $server . ":" . $port;
		if ($curl) {
			switch ($service) {
				case 'HTTP' :
					return "http://" . $url;
				case 'HTTPS' :
					return "https://" . $url;
				default :
					return "'$service' Service Not Supported";
			} // End switch-case
		} else {
			switch ($service) {
				// WGET: --spider: no descarga la página; -S: recupera la respuesta del servidor
				case 'HTTP' :
					return "wget http://" . $url . " --spider -S 2>&1";
				case 'HTTPS' :
					//--no-check-certificate: No comprobar el peer del certificado (el CA que lo hace confiable)
					return "wget https://" . $url . " --spider -S --no-check-certificate 2>&1";
				default :
					return "'$service' Service Not Supported";
			} // End switch-case
		}
	}
	
	/**
	 * Comprobar servicio Web con WGet
	 * 
	 * @param $server Nombre
	 *        	o IP del servidor contra el que se lanza la petición
	 * @param $port (Opcional)
	 *        	Puerto del servidor contra el que se lanza la petición. Por defecto, 80.
	 * @param $service (Opcional)
	 *        	Tipo de servicio: "HTTP", "HTTPS" o "JBOSS". Por defecto, "HTTP"
	 * @param $ok_code (Opcional)
	 *        	Código que se considera correcto. Por defecto, 200.
	 * @return Un array de tres componentes:
	 *         ['response'] -> Respuesta del servidor o la aplicación.
	 *         ['code'] -> Código devuelto por el servidor o la aplicación (puede estar vacío).
	 *         ['ok'] -> String que indica si es resultado es positivo (Testing::STATUS_UP: la respuesta es la esperada), negativo (Testing::STATUS_DOWN: no hay respuesta) o desconocido (Testing::STATUS_UNKNOWN: hay respuesta pero no la esperada).
	 *        
	 */
	static function HTTP_cmd($server, $port = 80, $service = "HTTP", $ok_code = 200) {
		// De entrada creo un resultado negativo:
		$response = array (
				"code" => 0,
				"ok" => Testing::STATUS_DOWN,
				"response" => '' 
		);
		
		// Lanzo la llamada al servicio
		exec ( Testing::prepare_url ( $server, $port, $service, FALSE ), $output, $status );
		
		// Compruebo si se ha devuelto algo...
		if (isset ( $output )) {
			if (is_array ( $output )) {
				// Buscamos en la cadena donde está la respuesta que nos interesa del servidor:
				for($i = 0; $i < count ( $output ); $i ++)
					if (strpos ( $output [$i], Testing::HTTP_CAD_CODE ) !== false) {
						$response ['response'] = $output [$i]; // nos quedamos con el último resultado que concuerda.
					}
				if (empty ( $response ['response'] )) { // Si no se ha encontrado...
					// Cojo la última línea que es donde se suele indicar el error.
					$response ['response'] = $output [count ( $output ) - 1]; 
				}
			} else {
				// Si no es un array, cogemos lo que sea...
				$response ['response'] = $output;
			}
		}
		
		// Analizamos la respuesta:
		if (empty ( $response ['response'] )) { // Esto es que no hay respuesta
			//'code' ya es 0 y 'status' es DOWN
			$response ['response'] = "The server does not respond.";
		} elseif (strpos ( $response ['response'], $ok_code ) !== false) { // Si contiene el código acertado, funciona bien
			$response ['ok'] = Testing::STATUS_UP;
			$response ['code'] = $ok_code;
		} else { // Cualquier código diferente, es "DOWN"
			$response ['ok'] = Testing::STATUS_DOWN;
			$response ['code'] = substr ( $response ['response'], 8, 3 );
			if (!is_numeric($response ['code']))
				$response['code'] = 0;
		}
		return $response;
	}
	
	/**
	 * Comprobar servicio Web con CURL (módulo PHP)
	 * 
	 * @param $server Nombre
	 *        	o IP del servidor contra el que se lanza la petición
	 * @param $port (Opcional)
	 *        	Puerto del servidor contra el que se lanza la petición. Por defecto, 80.
	 * @param $service (Opcional)
	 *        	Tipo de servicio: "HTTP", "HTTPS" o "JBOSS". Por defecto, "HTTP"
	 * @param $ok_code (Opcional)
	 *        	Código que se considera correcto. Por defecto, 200.
	 * @return Un array de tres componentes:
	 *         ['response'] -> Respuesta del servidor o la aplicación.
	 *         ['code'] -> Código devuelto por el servidor o la aplicación (puede estar vacío).
	 *         ['ok'] -> String que indica si es resultado es positivo (Testing::STATUS_UP: la respuesta es la esperada), negativo (Testing::STATUS_DOWN: no hay respuesta) o desconocido (Testing::STATUS_UNKNOWN: hay respuesta pero no la esperada).
	 */
	static function HTTP_curl($server, $port = 80, $service = "HTTP", $ok_code = 200) {
		// OBTENER HEADER
		$response = array ();
		$aux = array ();
		$ch = curl_init ();
		
		// Preparamos la petición para el servidor
		curl_setopt ( $ch, CURLOPT_URL, Testing::prepare_url ($server, $port, $service) );
		//Incluir el Header en la salida
		curl_setopt ( $ch, CURLOPT_HEADER, true );
		//Devolver el resultado como string, sin mostrarlo directamente
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		// No comprobar el peer del certificado (el CA que lo hace confiable)
		//Soluciona: ERROR [#0]: 60 - SSL certificate problem: self signed certificate 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		//0 para no comprobar nombres.
		//2 para comprobar que existe un nombre común y también para verificar que el hostname coinicide con el proporcionado.
		//Soluciona: ERROR [#0]: 51 - SSL: unable to obtain common name from peer certificate
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		
		// Hacemos la llamada y cogemos la respuesta:
		$response ['response'] = curl_exec ( $ch );
		
		// Hacemos diferentes llamadas por si acaso está redirigido...
		$aux = curl_getinfo ( $ch );
		while ( ! empty ( $aux ['redirect_url'] ) ) {
			curl_setopt ( $ch, CURLOPT_URL, $aux ['redirect_url'] );
			$response ['response'] = curl_exec ( $ch );
			$aux = curl_getinfo ( $ch );
		}
		// Comprobamos el resultado:
		if (empty ( $aux ['http_code'] )) { // Esto es que no hay respuesta...
			$response ['ok'] = Testing::STATUS_DOWN;
			$response ['code'] = 0;
			$response ['response'] = curl_errno ( $ch ) . " - " . curl_error ( $ch );
		} elseif ($ok_code == $aux ['http_code']) {
			$response ['ok'] = Testing::STATUS_UP;
			$response ['code'] = $ok_code;
		} else { // Códigos que nos son 200
			$response ['ok'] = Testing::STATUS_DOWN; //Podría ser STATUS_UNKNOWN
			$response ['code'] = $aux ['http_code'];
		}
		// Cerramos la conexión:
		curl_close ( $ch );
		return $response;
	}
	
	// ////////////////////////////////////////////////////////////////////////
	// CHECK MYSQL //
	// ////////////////////////////////////////////////////////////////////////
	/**
	 * Lanzar un test del servicio "MySQL" utilizando métodos/funciones de MySQLi
	 * 
	 * @param $host Servidor
	 *        	contra el que se lanza la petición (IP o DNS)
	 * @param $port (Opcional)
	 *        	Puerto de escucha de Mysql. Por defecto 3306
	 * @param $user (Opcional)
	 *        	Usuario para la conexión a la base de datos. Por defecto, null.
	 * @param $pass (Opcional)
	 *        	Contraseña para la conexión a la base de datos. Por defecto, null.
	 * @return Un array de tres componentes:
	 *         ['response'] -> Respuesta del servidor o la aplicación.
	 *         ['code'] -> Código devuelto por el servidor o la aplicación (puede estar vacío).
	 *         ['ok'] -> String que indica si es resultado es positivo (Testing::STATUS_UP: la respuesta es la esperada), negativo (Testing::STATUS_DOWN: no hay respuesta) o desconocido (Testing::STATUS_UNKNOWN: hay respuesta pero no la esperada).
	 */
	static function mysql_check($host, $port = 3306, $user = null, $pass = null) {
		// Creamos el array para el resultado.
		$response = array ('response' => "Required extension 'mysqli' is not loaded",
							'code' => 1001,
							'ok' => Testing::STATUS_UNKNOWN,
		);
		//Comprobamos que disponemos de mysqli:
		if (!extension_loaded ( 'mysqli' ))
			return $response;
		
		// Creamos un objeto de conexión a la base de datos:
		//Con usuario vacío puede aparecer "www-data": usuario por defecto que usa PHP/MySQL en Unix
		$conn = new mysqli ( $host, $user, $pass, null, $port );
		// Se utiliza mysqli_connect_error() en lugar de $conn->connect_error para asegurar
		// la compatibilidad con versiones de PHP anteriores a 5.2.9 y 5.3.0.
		$err = mysqli_connect_errno ();
		if ($err) { // Los errores de 2001 a 2009 son relativos a la conexión con un servidor MySQL (no se alcanza, no responde, driver erróneo,...)
			if ($err < 2001 || $err > 2009) {
				$response ['response'] = "Connection failed: " . mysqli_connect_error ();
				$response ['code'] = $err;
				$response ['ok'] = Testing::STATUS_DOWN; //Podría ser STATUS_UNKNOWN...
			} else {
				$response ['response'] = "Connection failed: " . mysqli_connect_error ();
				$response ['code'] = $err;
				$response ['ok'] = Testing::STATUS_DOWN;
			}
		} else {
			$response ['response'] = "Connection success: " . $conn->host_info;
			$response ['code'] = $err;
			$response ['ok'] = Testing::STATUS_UP;
			// Cerramos la conexión:
			$conn->close ();
		}
		unset ( $conn );
		return $response;
	}
	
	// ////////////////////////////////////////////////////////////////////////
	// CHECK ORACLE //
	// ////////////////////////////////////////////////////////////////////////
	/**
	 * Lanzar un test del servicio "ORACLE"
	 * 
	 * @param $host Servidor
	 *        	contra el que se lanza la petición (IP o DNS)
	 * @param $port (Opcional)
	 *        	Puerto de escucha de Oracle. Por defecto 1521.
	 * @param $user (Opcional)
	 *        	Usuario para la conexión a la base de datos. Por defecto, null.
	 * @param $pass (Opcional)
	 *        	Contraseña para la conexión a la base de datos. Por defecto, null.
	 * @return Un array de tres componentes:
	 *         ['response'] -> Respuesta del servidor o la aplicación.
	 *         ['code'] -> Código devuelto por el servidor o la aplicación (puede estar vacío).
	 *         ['ok'] -> String que indica si es resultado es positivo (Testing::STATUS_UP: la respuesta es la esperada), negativo (Testing::STATUS_DOWN: no hay respuesta) o desconocido (Testing::STATUS_UNKNOWN: hay respuesta pero no la esperada).
	 */
	static function oracle_check($host, $port = 1521, $user = null, $pass = null) {
		$response = array (); // Creamos el array para el resultado.
		                     // Vemos si está cargada la extensión adecuada:
		if (extension_loaded ( 'oci-8' ) || extension_loaded ( 'oci8' )) {
			$tns = "	
			(DESCRIPTION =
			    (ADDRESS_LIST =
			      (ADDRESS = (PROTOCOL = TCP)(HOST = $host)(PORT = $port))
			    )
			    (CONNECT_DATA =
			      (SERVICE_NAME = orcl)
			    )
			  )
			       ";
			$msg = (extension_loaded ( 'oci-8' ) ? 'oci-8' : 'oci8');
			$tns = "//" . $host . ":" . $port;
			// Intentamos crear un objeto de conexión a la base de datos:
			$conn = null;
			$conn = oci_new_connect ( $user, $pass, $tns );
			if ($conn === false) {
				$err = oci_error ();
				if ($err) {
					$response ['response'] = "Connection failed: " . $err ['message'];
					$response ['code'] = $err ['code'];
				} else {
					$response ['response'] = "Connection failed: Could not connect to Oracle server [$tns].";
					$response ['code'] = - 1;
				}
				$response ['ok'] = Testing::STATUS_DOWN;
			} else {
				$response ['response'] = "Connection success! [using $msg driver]";
				$response ['code'] = 0;
				$response ['ok'] = Testing::STATUS_UP;
				oci_close ( $conn );
			}
			unset ( $conn );
		} else {
			$response ['response'] = "Required extension OCI-8 is not loaded";
			$response ['code'] = 1001;
			$response ['ok'] = Testing::STATUS_UNKNOWN;
		}
		return $response;
	}
	
	/**
	 * **************************************************************************************************
	 * ALERTAS Y LOGS
	 * *************************************************************************************************
	 */
	
	/**
	 * Lanzar un mensaje a los correos predeterminados, informando del estado de un servicio en un servidor.
	 * Útil para informar cuando un servicio cambia su estado.
	 *
	 * @param $server Nombre
	 *        	del servidor/host que no responde.
	 * @param $type Nombre
	 *        	del servicio que no responde.
	 * @param $message Mensaje
	 *        	que se envía.
	 * @param $new_stat Estado
	 *        	actual del servicio (UP, DOWN o UNKNOWN)
	 */
	function launch_alert() {
		// Mandar un mensaje de Alerta
		$errors = "";
		$message2 = "";
		
		$sub = "Service '" . $this->service . "' ";
		
		$message2 .= "\n<br>*************************************************************************<br>\n";
		$message2 .= "Server: $this->server;<br>\n";
		$message2 .= "Service: $this->service;<br>\n";
		$message2 .= "Time: " . date ( "Y/m/d H:i:s" ) . ";<br>\n";
		$message2 .= "Detected Status: $this->status<br>\n";
		$message2 .= "Previous Status: $this->last_check<br>\n<br>\n";
		
		$response = htmlentities($this->response);
		if ($this->status === Testing::STATUS_DOWN) {
			$message2 .= "<b>ALERT</b>: <font color=red>ERROR detected in a Service</font><br>\n";
			$sub .= "is Not Accessible";
			$message2 .= "ERROR: <font color=red>$response</font><br>\n";
		} else if ($this->status === Testing::STATUS_UP) {
			$message2 .= "MESSSAGE:<font color=green> One Service got Up</font><br>\n";
			$sub .= "is now Accessible";
			$message2 .= "INFORMATION: <font color=green>$response</font><br>\n";
		} else {
			$message2 .= "<font color=orange>ALERT: One Service got Unknown</font><br>\n";
			$sub .= "status is Unknown";
			$message2 .= "INFORMATION: <font color=orange>$response</font><br>\n";
		}
		$sub .= " on '" . $this->server . "'";
		
		$message2 .= "*************************************************************************<br>\n";
		// Ponemos el asunto encima:
		$message2 = "<b>$sub</b><br>\n" . $message2;
		// Agregamos cabecera HTML
		$message2 = "<html><head><title></title><meta http-equiv=Content-Type content=text/html; charset=UTF-8></head><body>\n" . $message2;
		// Cerramos el cuerpo del mensaje:
		$message2 .= '</body></html>';
		// YA HEMOS PREPARADO EL MENSAJE Y EL ASUNTO!!
		
		// Mandamos el mensaje:
		return Testing::sendMail ( $sub, $message2, '', "Cacti - Service Test Plugin", Testing::$settings );
	}
	
	/**
	 * Enviar un correo con un asunto y un cuerpo de mensaje.
	 * Si no se especifica destinatario, se toma el especificado en las configuraciones del plugin.
	 * Si no se especifica configuración del servidor, se toma la especificada en las configuraciones del plugin
	 * 
	 * @param $sub Asunto
	 *        	del correo.
	 * @param $message2 Cuerpo
	 *        	del mensaje
	 * @param $to (Opcional)
	 *        	Destinatario (o lista de destinatarios separada por comas). Por defecto, se toma lo especificado para el plugin.
	 * @param $from (Opcional)
	 *        	Remitente. Por defecto, se toma el texto "Cacti - Service Test Plugin".
	 * @param $config (Opcional)
	 *        	Configuración del servidor de correo SMTP. Por defecto, se toma lo especificado para el plugin.
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
	 * @param $debug (Opcional)
	 *        	Indica si se debe guardar en el log una reseña del envío de correos. Por defecto, no.
	 * @return El valor booleano false en caso de no haberse dado ningún error. En caso contrario, retorna el mensaje de error.
	 */
	static function sendMail($sub, $message2, $to = '', $from = '', $config = null, $debug = false) {
		$errors = false;
		if (empty ( $config )) {
			// Me aseguro de que estén cargadas las configuraciones
			Testing::loadSettings ();
			// Cojo los datos de la configuración del plugin
			$config = Testing::$settings;
		}
		// Me aseguro de que hay configuración:
		if (empty ( $config ) || ! is_array ( $config )) {
			return "ERROR: Unable to send mail. There is not an available SMTP configuration.";
		}
		// Remitente por defecto
		if (! $from) {
			$from = "Cacti - Service Test Plugin";
		}
		if (! $to) {
			// Intento coger la lista de destinatarios de la configuración del plugin
			$to = (isset ( $config ['mail_list'] ) ? $config ['mail_list'] : 'default');
		}
		// Si se deja como default, se intenta cargar la lista de destinatarios de Thold
		if ($to == "default") {
			// Comprobamos si thold está instalado
			if (! Testing::check_installed_plugin ( "thold" )) {
				$errors = "ERROR: Unable to send mail. Unable to load the default mail address list from 'thold' plugin.";
			} else {
				// Usamos la función de Cacti para cargar un parámetro de la configuración:
				// $global_alert_address = read_config_option("alert_email");
				$to = read_config_option ( "alert_email" );
			}
		}
		
		// Si no hay una lista de destinatarios, se lanza error
		if (empty ( $to )) {
			$errors = "ERROR: Unable to send mail. The mail address list is empty.";
		}
		
		// Si hasta aquí no se han encontrado errores => intentamos realizar el envío!
		if (! $errors) {
			// Usaremos los parámetros de este plugin si no existe 'settings' o no se ha configurado esa opción
			if (empty ( $config ['mailoption'] ) || $config ['mailoption'] !== 'settings' || ! Testing::check_installed_plugin ( "settings" )) {
				include_once "stMailing.php";
				$errors = st_send_mail ( $to, $from, $sub, $message2, $config );
				if ($debug)
					Testing::catch_log ( "DEBUG: Mail sent with ServiceTest mailing function." );
			} else { // Usaremos los parámetros del plugin "settings" si existen y se ha configurado esa opción
			      // * Se referencia el archivo donde está la función para mandar mails. Las configuraciones de mail se tomarán de las puestas por defecto.*/
			      // Cabecera: send_mail($to, $from, $subject, $message, $filename = '', $headers = '')
				include_once "./plugins/settings/include/functions.php";
				$errors = send_mail ( $to, $from, $sub, $message2, '', '' );
				if ($debug)
					Testing::catch_log ( "DEBUG: Mail sent with Settings mailing function." );
			}
		}
		
		if ($errors) { // Si hay errores en el envío de mensajes, se guarda en el log de cacti
			Testing::catch_log ( "[serviceTest@send_alert_mail] - ERROR: " . $errors );
		}
		return $errors;
	}
	
	/**
	 * Recoger un evento en los logs de cacti.
	 * 
	 * @param $text Texto
	 *        	que se escribirá en el log de Cacti
	 *        	Si el texto contiene "STATS", aparecerá en verde;
	 *        	Si el texto contiene "ERROR" o "FATAL", aparecerá en rojo;
	 *        	Si el texto contiene "WARNING|DEBUG", aparecerá en ámbar;
	 */
	static function catch_log($text) {
		// Guardarlo en el registro de cacti:
		/*
		 * Cabecera de la función cacti_log:
		 * /** cacti_log - logs a string to Cacti's log file or optionally to the browser * /
		 * function cacti_log($string, $output = false, $environ = "CMDPHP")
		 * @arg $string - the string to append to the log file
		 * @arg $output - (bool) whether to output the log line to the browser using print() or not
		 * @arg $environ - (string) tell's from where the script was called from
		 */
		cacti_log ( ( string ) $text, false, 'Service Test' );
	}
	
	/**
	 * *****************************************************************************************
	 * OTRAS FUNCIONES
	 * *****************************************************************************************
	 */
	/**
	 * Ver si el servicio representado por el objeto ya ha sido testado
	 * 
	 * @return boolean True si ya ha sido testado o false en caso contrario.
	 */
	function isTested() {
		return ($this->code !== false);
	}
	/**
	 * Ver si el servicio representado por el objeto no ha sido testado aún.
	 * 
	 * @return boolean True si no ha sido testado o false en caso contrario.
	 */
	function isNotTested() {
		return ($this->code === false);
	}
	
	/**
	 * Cargar las configuraciones del plugin serviceTest, si no lo están ya.
	 * 
	 * @param $force (Opcional)
	 *        	Indicar si se debe forzar el refresco de los datos de configuración del plugin.
	 *        	Por defecto, sólo se actualizan si no están cargados.
	 */
	static private function loadSettings($force = false) {
		// Si ya se han cargado los datos => no se cargan de nuevo
		if (! $force && ! empty ( Testing::$settings ))
			return;
		Testing::$settings = db_fetch_assoc ( "SELECT * FROM serviceTest_settings WHERE id=1" );
		if (Testing::$settings & is_array ( Testing::$settings )) {
			Testing::$settings = Testing::$settings [0];
		} else {
			Testing::$settings = null;
			Testing::catch_log ( "[Testing Class@serviceTest] - WARNING: Unable to load plugin settings." );
		}
	}
	
	/**
	 * Comprobar si un plugin está instalado y, en su caso, en servicio.
	 * 
	 * @param string $pluginname
	 *        	Nombre del plugin que se desea comprobar.
	 * @param boolean $running
	 *        	[Opcional] Indicar si el plugin debe estar o es indiferente (por defecto, indiferente).
	 * @return True en caso de que el plugin se encuentre instalado y, en su caso, activo. False en caso contrario.
	 */
	static function check_installed_plugin($pluginname, $running = false) {
		if ($running)
			return ! empty ( db_fetch_cell ( "SELECT directory FROM plugin_config WHERE directory='$pluginname' AND status=1" ) );
		else
			return ! empty ( db_fetch_cell ( "SELECT directory FROM plugin_config WHERE directory='$pluginname'" ) );
	}
	
	/**
	 * Comprobar si un plugin está instalado y, en su caso, en servicio.
	 * 
	 * @param string $pluginname
	 *        	Nombre del plugin que se desea comprobar.
	 * @return True en caso de que el plugin se encuentre en un estado no funcional. False si está funcional.
	 */
	static function check_unestable_plugin($pluginname = "serviceTest") {
		return ! empty ( db_fetch_cell ( "SELECT directory FROM plugin_config WHERE directory='$pluginname' AND status<>1" ) );
	}
	
	/**
	 * Obtener un array que representa el estado del servicio, actualmente (evaluado o no).
	 * Si el servicio no ha sido aún chequeado, el elemento 'code' tiene el valor booleano false.
	 * 
	 * @return Un vector con los siguientes elementos:
	 *         'ok' => Resultado del test, que puede ser Testing::STATUS_UP, Testing::STATUS_DOWN o Testing::STATUS_UNKNOWN
	 *         'code' => Código (de error o no) devuelto por la función que intenta la conexión con el servidor o por el servidor mismo.
	 *         'response' => Mensaje devuelto por la función que intenta realizar la petición al servidor o por el servidor mismo.
	 */
	function getStatus() {
		// Comprobamos si ya ha sido evaluado:
		if ($this->isNotTested ()) {
			return array (
					'ok' => false,
					'code' => false,
					'response' => 'ERROR: The service has not been tested yet.' 
			);
		}
		return array (
				'ok' => $this->status,
				'code' => $this->code,
				'response' => $this->response 
		);
	}
	
	/**
	 * Establecer el estado del servicio.
	 * Método Privado!
	 * 
	 * @param $status Resultado
	 *        	del test, que puede ser Testing::STATUS_UP, Testing::STATUS_DOWN o Testing::STATUS_UNKNOWN
	 * @param $code Código
	 *        	(de error o no) devuelto por la función que intenta la conexión con el servidor o por el servidor mismo.
	 * @param $response Mensaje
	 *        	devuelto por la función que intenta realizar la petición al servidor o por el servidor mismo.
	 */
	private function setStatus($status, $code, $response) {
		$this->response = $response;
		$this->status = $status;
		$this->code = $code;
	}
	
	/**
	 * Obtener un array con las configuraciones del plugin.
	 */
	static function getSettings() {
		// Nos aseguramos de que los datos están cargados y actualizados:
		Testing::loadSettings ( true );
		// Retornamos los datos de configuración:
		return Testing::$settings;
	}
	
	/**
	 * Devolver el estado actual del objeto en forma de texto.
	 */
	function toString() {
		$cad = "ID: " . $this->id . "; \n";
		$cad .= "Service: " . $this->service . "; \n";
		$cad .= "Server: " . $this->server . "; \n";
		$cad .= "Port: " . $this->port . "; \n";
		$cad .= "User: " . $this->user . "; \n";
		$cad .= "Pwd: " . $this->pwd . "; \n";
		$cad .= "Previous Check: " . $this->last_check . "; \n";
		$cad .= "Notify Unknown: " . $this->notify_unknown . "; \n";
		$cad .= "OK Code: " . $this->ok_code . "; \n";
		if ($this->isNotTested ()) {
			$cad .= "This Service Has Not Been Checked!\n";
		} else {
			$cad .= "This Service was Checked:\n";
			$cad .= " # Response Status: " . $this->status . " \n";
			$cad .= " # Response Code: " . $this->code . " \n";
			$cad .= " # Response Text: " . $this->response . " \n";
		}
	}
} // FIN DE LA CLASE
?>