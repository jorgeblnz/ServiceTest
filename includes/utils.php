<?php
/**

 FUNCIONES VARIAS DE UTILIDAD PARA EL PLUGIN:
 1.- VALIDACIONES Y DEPURACIÓN DE DATOS DE ENTRADA DE FORMULARIOS.
 2.- VALIDACIONES Y DEPURACIÓN DE DATOS PARA CONSULTAS SQL.
 3.- INSERTAR UN INPUT DE UN FORMULARIO, USANDO HTMLSPECIALCHARS

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
 * Realizar una limpieza de los datos de entrada de formularios.
 * @param boolean 
 * @return array Array con los elementos de $_POST en un nuevo array y, en su caso, 
 * 			eliminando caracteres potencialmente peligrosos.
 */
function st_transformPostParams($change_cad = true){
	$result = array();
	$k = array_keys($_POST);
	if($change_cad){
		foreach($k as $key){
			$result[$key] = st_prepare_string(get_request_var_post($key));
		}
	}
	else{
		foreach($k as $key){
			$result[$key] = get_request_var_post($key);
		}
	}
	return $result;
}
/**
 * Ajustar los valores de entrada para los formularios.
 *
 * @param $cad Cadena a modificar
 * @return Cadena adecuada para consulta MySQL
 */
function st_prepare_string($input) {
	$cad = trim ( $input ); // Eliminamos espacios.
	if (! empty( $cad ) && ! is_numeric( $cad )) {
		// Eliminamos tabulador, saltos de línea y retornos de carro
		$cad = str_replace ( array ("\r\n","\n","\r","\t"), ' ', $cad );
		// También eliminamos símbolos '<' y '>'
		$cad = str_replace ( array ("<",">"), '*', $cad );
		// Eliminamos carácter nulo y backslash:
		$cad = str_replace ( array("\0","\\"), '', $cad );
		// Eliminamos comilla simple
		$cad = str_replace ( "'", '`', $cad );
		// Eliminamos comilla doble
		$cad = str_replace ( '"', '`', $cad );
		// Eliminamos punto-y-coma
		$cad = str_replace ( ";", ':', $cad );
	}
	return $cad;
}

	/**
 * Adecuar una URL para evitar que una URL de destino de formulario contenga código HTML o Javascript.
 *
 * @param $url URL
 *        	de destino de un formulario.
 * @return La URL de entrada pero sin código HTML o Javascript
 */
function st_correctActionURL($url) {
	// Evitar la inyección de código HTML o javascript (Evitar Cross-Site Scripting)
	// sustituyendo caracteres especiales por códigos HTML en la url de destino:
	return htmlspecialchars ( trim ( $url ), ENT_QUOTES ); // Codifica caracteres especiales en códigos HTML (&gt; , &quot; , ...)
}

/**
 * Imprimir un input de un formulario, haciendo uso de htmlspecialchars en valores y nombres.
 * 
 * @param $type String
 *        	Tipo de input: text, password, hidden, option, radio o checkbox.
 * @param $name String
 *        	Nombre del campo (del input)
 * @param $value Mixed
 *        	Valor que tendrá el input.
 * @param $attribs (Optional)
 *        	Otros atributos a agregar (size, rows,...). Este valor se incluye tal cual en el input.
 * @param $text (Optional)
 *        	Texto a presentar (por ejemplo, para checkboxes o radio).
 * @param $id (Optional)
 *        	Indicar un ID expresamente (igual o diferente al nombre). Por defecto, igual al nombre.
 * @return String con el input adecuado.
 */
function st_printInput($type, $name, $value, $attribs = '', $text = '', $id = '') {
	// '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($value).'" />';
	$tipos = array (
			'text',
			'hidden',
			'radio',
			'checkbox',
			'option',
			'password',
			'submit', 
			'number',
			'search',
			'color',
			'email',
			'date'
	);
	$type = strtolower ( $type );
	$name2 = trim ( $name );
	if ($name2 && in_array ( $type, $tipos )) {
		$input = "<input type=\"$type\" ";
		$input .= "name=\"" . $name2 . "\" ";
		$input .= "value=\"" . $value . "\" ";
		$input .= "id=\"" . ($id ? $id : $name2) . "\" ";
		$input .= $attribs;
		$input .= " /> ";
		if ($text)
			$input .= $text;
		$input .= "\n";
	} else {
		$input = "<!-- Unable to generate INPUT: [type '$type']:[name '$name']:[value '$value'] -->\n";
	}
	return $input;
}

/**
 * Crear una celda de una tabla con un contenido específico y de una clase concreta.
 * 
 * @param string $content
 *        	Contenido de la celda de la tabla (se agrega tal cual se reciba)
 * @param string $class
 *        	(Optional) Clase CSS que se aplica a la celda. Por defecto, vacío (sin clase).
 * @param number $tabs
 *        	(Optional) Número de tabuladores agregados en el código (sangrar código). Por defecto, 1.
 * @return string Celda de tabla con el contenido especificado y de la clase CSS especificada.
 */
function st_printCell($content, $class = "", $tabs = 1) {
	$t = "\t";
	if (is_numeric ( $tab )) {
		for($i = 1; $i < $tabs;) {
			$t .= "\t";
		}
	}
	$cell = $t . "<td" . ($class ? " class=\"$class\"" : "") . ">\n";
	$cell .= $t . "\t$content\n";
	$cell .= $t . "</td>";
	return $cell;
}

/**
 * Obtener una conexión a la base de datos de Cacti, en forma de objeto mysqli.
 * Se aprovecha para establecer el charset en UTF-8
 * @return string|mysqli : Una objeto mysqli que conecta a la base de datos o un mensaje de error, en caso de ocurrir.
 */
function st_get_db_connection(){
	global $database_type; // Tipo BD
	global $database_default; // Nombre de BD
	global $database_hostname; // Servidor
	global $database_username; // Usuario
	global $database_password; // Contraseña
	global $database_port; // Puerto
	global $database_ssl; // Uso de SSL
	$port = $database_port;
	$err = false;
	$change = false;
	
	//Comprobamos que existe el módulo adecuado:
	if (!extension_loaded('mysqli')){ 
		$err = "[ServiceTest@get_db_connection] ERROR: Unable to find required 'mysqli' extension for PHP.";
		cacti_log ($err, false, 'Service Test');
		return $err;
	}
	// Conectamos a la base de datos
	if (! $port) {
		$port = 3306;
	}
	if (is_string ( $port )) {
		$port = intval ( $port );
	}
	$cnn = new mysqli ( $database_hostname, $database_username, $database_password, $database_default, $port );
	// Comprobamos la conexión:
	if (mysqli_connect_error ()) {
		$err = "ERROR [#" . mysqli_connect_errno () . "]: " . mysqli_connect_error ();
		cacti_log ( "[ServiceTest@get_db_connection] " . $err, false, 'Service Test' );
		return $err;
	} else {
		// Establecemos el charset a UTF-8
		$cnn->query ( "set names 'utf8'" );
	}
	return $cnn;
}

/**
 * Ejecutar una consulta sobre la base de datos de Cacti.
 * Antes de lanzar la consulta, establece el STRICT MODE.
 * Está pensada para realizar inserciones y actualizaciones de datos respetando
 * las restricciones definidas en las tablas (not null, por ejemplo).
 * 
 * @param string $query
 *        	Consulta a ejecutar contra la base de datos.
 * @return array Retorna un array con 2 elementos:
 * 	"error" => el boolean false si no hay error o un mensaje de error (un string), en caso contrario.
 *  "result" => El resultado devuelto por la base de datos tras la consulta (si lo hay).
 */
function st_execute_query($query) {
	$cnn = st_get_db_connection();
	// Comprobamos la conexión:
	if (is_string($cnn)) {
		global $config;
		include_once($config['library_path'] . '/database.php');
		$r = db_execute($query); //Retorna 1 si es correcto o 0 si hay error.
		return ($r? "" : "Error executing the SQL query! See Cacti log for further information...");
	} else {
		// Recogemos el modo de operación SQL de MySQL (global):
		$result = $cnn->query ( "SELECT @@sql_mode" );
		$mode = $result->fetch_row ();
		if (is_array ( $mode ))
			$mode = $mode [0]; // Primer registro, primera columna
		if (empty ( $mode ))
			$mode = ''; // Si ha quedado vacío, aseguramos que sea una cadena vacía.
				            // Establecemos el modo estricto:
		$result = $cnn->query ( "SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL'" );
		if (! $result) {
			$err = "WARNING [#" . $cnn->errno . "]: " . $cnn->error;
			cacti_log ( "[ServiceTest@execute_query] " . $err, false, 'Service Test' );
			// Guardamos error y continuamos
		}
		
		// Limpiamos el error
		$err = false;
		// EJECUTAMOS LA CONSULTA:
		$result = $cnn->query( $query );
		if(!is_bool($result)){
			$aux = array();
			while($x = $result->fetch_assoc())
				$aux[] = $x;
			$result = $aux;
		}
		
		// Comprobamos resultado:
		if (! $result) {
			$err = "ERROR [#" . $cnn->errno . "]: " . $cnn->error;
			cacti_log ( "[ServiceTest@execute_query] " . $err, false, 'Service Test' );
			// Guardamos error y continuamos
		}
		
		// Restablecemos el modo de operación:
		$cnn->query ( "SET SQL_MODE=@OLD_SQL_MODE" );
		
		// Cerramos la conexión:
		$cnn->close ();
		unset ( $cnn );
	}
	// Retornamos el error o false (si $err no ha cambiado tras la ejecución)
	return array("error" => $err, "result" => $result);
}

/**
 * Retornar un valor con formato adecuado para introducirlo en una sentencia Mysql.
 * Dependiendo de si es nulo, numérico o una cadena lo retorna entrecomillado,
 *
 * @param $value Valor
 *        	que debe transformarse.
 * @param $type (Optional)
 *        	Indica el tipo de dato según el cual se debe tratar, expresamente (para que retorne true, false, null, un número, una cadena... en texto). Por defecto se trata como una cadena.
 *        	Los tipos pueden ser: Bool, boolean, string, text, number, numeric, int, integer, float, date o time.
 * @return Cadena transformada para que pueda ser insertado como un valor en una sentencia SQL
 */
function st_mysql_value($value, $type = 'string') {
	if (is_null ( $value ) || $value === '')
		return "null"; // Si es null o cadena vacía => retornamos null (en texto, para consulta SQL)
	switch (strtolower ( $type )) {
		case 'bool' :
		case 'boolean' :
			// Comprobamos valor como si fuese booleano
			if (empty ( $value ) || strtolower ( $value ) === 'false')
				return "false";
				else
					return "true";
					break;
		case 'int' :
		case 'integer' :
		case 'float' :
		case 'number' :
		case 'numeric' :
			if (is_numeric ( $value ))
				return ( string ) $value; // Los valores numéricos van tal cual en consultas MySQL
				return $value;
				break;
		// El resto de opciones funcionan como por defecto, pero se ponen por claridad...
		case 'string' :
		case 'date' :
		case 'time' :
		case 'text' :
		default :
			$cnn = st_get_db_connection();
			if(is_string($cnn))
				return "'$value'"; // Entrecomillado
			else {
				return "'".$cnn->real_escape_string($value)."'";
				// Cerramos la conexión:
				$cnn->close ();
				unset ( $cnn );
			}
		}
	return $value;
}

/**
 * Retornar los valores de un array con formatos adecuados para introducirlos en una sentencia Mysql.
 * Dependiendo de si son nulos, numéricos o cadenas los retorna entrecomillados o no.
 *
 * @param $value Array de valores que se deben transformar.
 * @param $type Array con los tipos de datos según los cuales se debe tratar, expresamente cada valor (para que retorne true, false, null, un número, una cadena... en texto). Por defecto se trata como una cadena.
 *        	Los tipos pueden ser: Bool, boolean, string, text, number, numeric, int, integer, float, date o time.
 * @param $real_scape_string (Opcional) Boolean que indica si se debe realizar la transformación de datos que facilita mysqli
 * 			para el filtrado de los datos de entrada de tipo string.
 * @return Cadena transformada para que pueda ser insertado como un valor en una sentencia SQL
 */
function st_mysql_values($values, $types, $real_scape_string = true) {
	// Si no son arrays, reenvío llamada para elemento individual...
	if(!is_array($values) || !is_array($types))
		return st_mysql_value($values, $types);
	
	$cnn = st_get_db_connection();
	$max = count($values);
	if (count($types) < $max)
		$max = count($types);
	
	for($i=0; $i<$max; $i++){
		if (is_null ( $values[$i] ) || $values[$i] === '')
			$values[$i] = "null"; // Si es null o cadena vacía => retornamos null (en texto, para consulta SQL)
		else
			switch (strtolower ( $types[$i] )) {
				case 'bool' :
				case 'boolean' :
				// Comprobamos valor como si fuese booleano
					if (empty ( $values[$i] ) || (is_string($values[$i]) && strtolower ( $values[$i] ) === 'false'))
						$values[$i] = "false";
					else
						$values[$i] = "true";
					break;
				case 'int' :
				case 'integer' :
				case 'float' :
				case 'number' :
				case 'numeric' :
					if (is_numeric ( $values[$i] ))
						$values[$i] = ( string ) $values[$i]; // Los valores numéricos van tal cual en consultas MySQL
					break;
				// El resto de opciones funcionan como por defecto, pero se ponen por claridad...
				case 'string' :
				case 'date' :
				case 'time' :
				case 'text' :
				default :
					if($real_scape_string){
						$t = gettype($cnn);
						if(gettype($cnn)==='object' && get_class($cnn)==='mysqli')
							$values[$i] = "'".$cnn->real_escape_string($values[$i])."'"; // Entrecomillado
						else{
							$err = "[ServiceTest@get_db_connection] DEBUG: connection ".($t==='object'? "class is '".get_class($cnn) : "type is '".$t)."'.";
							cacti_log ($err, false, 'Service Test' );
							$values[$i] = "'".$values[$i]."'"; // Entrecomillado
						}
					}
					else{
						$values[$i] = "'".$values[$i]."'"; // Entrecomillado
					}
				}
	}
	// Cerramos la conexión:
	$cnn->close ();
	unset ( $cnn );
	//Retornamos los resultados:
	return $values;	
}
?>