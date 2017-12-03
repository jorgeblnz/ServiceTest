<?php
/**

 FORMULARIOS PARA EDITAR Y CREAR LA CONFIGURACIÓN DEL TEST DE ALGÚN SERVICIO

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

// Estilos de tabla
include_once 'includes/styles.php';

// Funciones varias
include_once 'includes/utils.php';

chdir ( '../../' ); // Nos situamos en el directorio "cacti"
require_once './include/auth.php'; // Desde el auth.php de Cacti se cargan las variables globales ($config, por ejemplo)

                                   
// RECOGEMOS EL ID DE HOST DE LA LLAMADA (PETICIÓN HTTP) de la forma que venga.
$hostid = get_request_var_request('id', false);
if (!$hostid)
	$hostid = get_request_var_post('id', false);
if (!$hostid)
	$hostid = get_request_var('id', false);
//Controlamos que el identificador sea un número:
if (!is_numeric($hostid))
	$hostid = -1;

// RECOGEMOS EL TIPO DE ACCIÓN A LLEVAR A CABO
$action = get_request_var_request('action', '');

// Adecuamos los parámetros de entrada que vienen por el método POST
$post_values = st_transformPostParams ();

// Generamos los estilos:
$estilos = generateStyles ( array (
		"dato",
		"valor",
		"separador",
		"tabla",
		"pie",
		"infobox",
		"alertbox"
), array (
		$formcell,
		$formdata,
		$tableheaders,
		$table,
		$footstyle,
		$infobox,
		$alertbox
) );

// Establecemos la página de retorno
$pageback = 'service_test.php';

// Preparamos el entorno de Cacti:
include_once ($config ['include_path'] . '/top_header.php');

// Se incluyen los estilos propios del plugin:
echo $estilos;

// ADECUAMOS LA RESPUESTA SEGÚN EL TIPO DE ACCIÓN
switch (strtolower($action)) {
	case 'edit' : // Editar
		service_test_edit ( $hostid );
		break;
	case 'confirm' : // CONFIRMAR CAMBIOS
		service_test_confirm ( $hostid );
		break;
	case 'save' : // GUARDAR CAMBIOS
		service_test_save ( $hostid );
		break;
	case 'enable' :
		// DESHABILITAR/HABILITAR
		service_test_enable ( $hostid );
		break;
	case 'delete' :
		// ELIMINACIÓN POR CONFIRMAR
		service_test_confirm_delete ( $hostid );
		break;
	case 'delete_confirmed' :
		// ELIMINAR DEFINITIVAMENTE
		service_test_delete ( $hostid );
		break;
	default :
		// Por defecto, se vuelve atrás
		header ( 'Location: ' . $pageback );
		exit ();
}

// Pie del entrono Cacti
include_once ($config ['include_path'] . '/bottom_footer.php');

/**
 * Presentar un formulario para editar (o añadir) un servicio
 */
function service_test_edit($id = -1) {
	// Editar servicio existente
	
	//Recuperamos los datos:
	$cur = db_fetch_row ( "SELECT * FROM serviceTest WHERE id=$id" );
	
	html_start_box ( "<strong>" . (($id == - 1 || ! $id) ? "NEW SERVICE" : "EDIT SERVICE") . "</strong>", "60%", TABLE_HEAD_COLOR, "2", "center", "" );
	if ($cur || $id == - 1) {
?>
<tr>
	<td>
	<!-- INICIO DE FORMULARIO -->
	<form action="edit_serviceTest.php" method="post" autocomplete="off">
		<?php echo st_printInput ( 'hidden', 'action', 'confirm' ); ?>
		<?php echo st_printInput ( 'hidden', 'id', $id ); ?>
		<table class='tabla'>
		<tr title="Service ID">
			<td class="dato"> ID</td>
			<td class="valor">
				<?php echo st_printInput ( 'text', 'id_altern', ($id == - 1 ? "New" : $id), "size=\"5\" disabled" ); ?>
			</td>
		</tr>
		<tr title="DNS Name or IP for the Server">
			<td class="dato"> IP/Name</td>
			<td class="valor">
				<?php echo st_printInput ('text', 'name', ($id == - 1 ? '' : $cur ["name"]), "size=\"20\" maxlength=\"50\" required"); ?>
			</td>
		</tr>
		<tr title="Description of the service (50 cars.)">
			<td class="dato"> Description</td>
			<td class="valor">
				<?php echo st_printInput ( 'text', 'description', ($id == - 1 ? '' : $cur ["description"]), "size=\"30\" maxlength=\"50\" required" ); ?>
			</td>
		</tr>
		<tr title="Service to Check in the Server">
			<td class="dato"> Service</td>
			<td class="valor">
				<select name="service">
<?php 
				$s = db_fetch_assoc ( "select service from serviceTest_services" );
				foreach ( $s as $actual ) {
					if ($id == - 1 || ! $id)
						echo "\t\t\t\t\t<option value=\"" . $actual ["service"] . "\" " . ($actual ["service"] == "HTTP" ? "selected" : "") . ">" . $actual ["service"] . "</option>\n";
					else
						echo "\t\t\t\t\t<option value=\"" . $actual ["service"] . "\" " . ($actual ["service"] == $cur ["service"] ? "selected" : "") . ">" . $actual ["service"] . "</option>\n";
				}
?>
				</select>
			</td>
		</tr>
		<tr title="Port used by the service">
			<td class="dato"> Port</td>
			<td class="valor">
				<?php echo st_printInput ( 'number', 'port', ($id == - 1 ? '' : $cur ["port"]), "size=\"5\" maxlength=\"5\" min=\"0\" max=\"65535\"" ); ?>
			</td>
		</tr>
		<tr title="User (if the service needs authentication)">
			<td class="dato"> User</td>
			<td class="valor">
				<?php echo st_printInput ( 'text', 'user', ($id == - 1 ? '' : $cur ["user"]), "size=\"20\" maxlength=\"50\"" ); ?>
			</td>
		</tr>
		<tr title="Password (if the service needs authentication)">
			<td class="dato"> Password</td>
			<td class="valor">
				<?php echo st_printInput ( 'password', 'pwd', ($id == - 1 ? '' : $cur ["pwd"]), "size=\"20\" maxlength=\"50\"" ); ?>
			</td>
		</tr>
		<tr title="Enable/Disable Checking for this Service">
			<td class="dato"> Enabled</td>
			<td class="valor">
				<?php echo st_printInput ( 'checkbox', 'enabled', 'true', (($id == - 1 || $cur ["enabled"]) ? 'checked' : '') ); ?>
			</td>
		</tr>
		<tr class='cactiTableTitle'>
			<td colspan=2 class='pie' style="align=center">
				<input type=submit name="save" value="CONTINUE"/>
				<input type=button name="cancel" value="CANCEL" onClick="window.location.href='<?php echo dirname($_SERVER['PHP_SELF']); ?>/service_test.php'" />
			</td>
		</tr>
		</table>
	</form>
	<!-- FIN DEL FRMULARIO -->
	</td>
</tr>
<?php 
	} else {
		echo "<tr><td>\n<div class='infobox'>\n";
		echo "\t<span style=\"color:red; font-size:14pt\">ERROR: No service found for ID #$id...</span>\n";
		echo "<hr><br>\n<a href=\"service_test.php\" title=\"Return to Service Test\">Return to Service Test</a>\n";
		echo "\n</div>\n</td></tr>\n";
	}
	html_end_box ();
}

/**
 * Presentar formulario para confirmar cambios en un servicio
 * 
 * @param $id Identificador
 *        	del servicio a modificar
 */
function service_test_confirm($id = -1) {
	// Editar servicio existente: confirmar cambios

	// Preparamos el entorno de Cacti:
	global $post_values;
	
	html_start_box ( "<strong>SAVE CHANGES</strong>", "80%", TABLE_HEAD_COLOR, "2", "center", "" );
	print "<tr>\n<td>\n";
	print "<form action=\"edit_serviceTest.php\" method=\"post\" autocomplete=\"off\">\n";
	print st_printInput ( 'hidden', 'action', 'save' );
	print st_printInput ( 'hidden', 'id', $id );
	print st_printInput ( 'hidden', 'name', $post_values["name"] );
	print st_printInput ( 'hidden', 'description', $post_values["description"] );
	print st_printInput ( 'hidden', 'service', $post_values["service"] );
	print st_printInput ( 'hidden', 'port', $post_values["port"] );
	print st_printInput ( 'hidden', 'user', $post_values["user"] );
	print st_printInput ( 'hidden', 'pwd', $post_values["pwd"] );
	$ena = false;
	if (empty($post_values["enabled"])) {
		$ena = " No ";
	} else {
		$ena = " Yes ";
		print st_printInput ( 'hidden', 'enabled', 'true' );
	}
	
	// PINTAR LOS DATOS:
	print "<table class='tabla'>\n";
	print "<tr title=\"DNS Name or IP for the Server.\">\n";
	print "\t<td class=\"dato\"> Name</td>\n";
	print "\t<td class=\"valor\">" . htmlentities ( $post_values["name"] ) . "</td>\n";
	print "</tr><tr title=\"Description of the service (50 cars.)\">\n";
	print "\t<td class=\"dato\"> Description</td>\n";
	print "\t<td class=\"valor\">" . htmlentities ( $post_values["description"] ) . "</td>\n";
	print "</tr><tr title=\"Service to Check in the Server\">\n";
	print "\t<td class=\"dato\"> Service</td>\n";
	print "\t<td class=\"valor\">" . htmlentities ( $post_values["service"] ) . "</td>\n";
	print "</tr><tr title=\"Port used by the service.\">\n";
	print "\t<td class=\"dato\"> Port</td>\n";
	print "\t<td class=\"valor\">" . htmlentities ( $post_values["port"] ) . "</td>\n";
	print "</tr><tr title=\"User (if the service needs authentication)\">\n";
	print "\t<td class=\"dato\"> User</td>\n";
	print "\t<td class=\"valor\">" . htmlentities ( $post_values["user"] ) . "</td>\n";
	print "</tr><tr title=\"Password (if the service needs authentication)\">\n";
	print "\t<td class=\"dato\"> Password</td>\n";
	print "\t<td class=\"valor\">".(empty($post_values["pwd"])? '' : '******')."</td>\n";
	print "</tr><tr title=\"Enable/Disable Checking for this Service\">\n";
	print "\t<td class=\"dato\"> Enabled</td>";
	print "\t<td class=\"valor\">" . $ena . "</td>\n";
	print "</tr>\n";
	print "<tr class='cactiTableTitle'>\n\t<td colspan=2 class='pie'>\n";
	print "\t<center><input type=submit name=\"save\" value=\"SAVE\"/>\n";
	print "\t<input type=button name=\"cancel\" value=\"CANCEL\" onClick=\"window.location.href='" . dirname ( $_SERVER ['PHP_SELF'] ) . "/service_test.php'\"/></center>\n";
	print "\t</td>\n";
	print "</tr>\n";
	print "</table>\n";
	print "</form>\n";
	print "</td>\n</tr>\n";
	html_end_box ();
}

/**
 * Habilitar un servicio cuando está deshabilitado y viceversa
 * 
 * @param $id Identificador
 *        	del servicio a modificar
 */
function service_test_enable($id = -1) {
	// Preparamos el entorno de Cacti:
	
	html_start_box ( "<strong>ENABLE/DISABLE SERVICE</strong>", "80%", TABLE_HEAD_COLOR, "2", "center", "" );
	echo "<tr>\n\t<td>\n";
	echo "\t\t<div class='infobox'>\n";
	$ok = 0;
	$cur = db_fetch_row ( "SELECT enabled,name,port,service FROM serviceTest WHERE id=$id" );
	if ($cur) {
		if ($cur ['enabled'] == 1) {
			$ok = db_execute ( "UPDATE serviceTest SET enabled=0, last_check='UNKNOWN' WHERE id=$id" );
			$en = "<span style=\"color:brown; font-size:14pt\">deactivated</span>";
		} else {
			$ok = db_execute ( "UPDATE serviceTest SET enabled=1 WHERE id=$id" );
			$en = "activated";
		}
		if ($ok)
			print "\t\t\t<span style=\"color:green; font-size:14pt\">Service <i>'" . htmlentities ( $cur ['service'] ) . "'@'" . htmlentities ( $cur ["name"]) . (empty ( $cur ["port"] ) ? "" : ':' . $cur ["port"]) . "'</i> (ID= $id) has been <b>$en</b>.</span><hr>";
		else
			print "\t\t\t<span style=\"color:red; font-size:14pt\">Unable to change Service <i>'" . htmlentities ( $cur ['service'] ) . "'@'" . htmlentities ( $cur ["name"]) . (empty ( $cur ["port"] ) ? "" : ':' . $cur ["port"]) . "'</i> (ID= $id): See <a href=\"/cacti/utilities.php?action=view_logfile\">cacti log</a> for further information.</span><hr>";
	} else {
		print "<span style=\"color:red; font-size:14pt; text-align:center\">ERROR: No service found with ID=$id</span><hr>";
	}
	echo "\n\t\t\t<br>";
	echo "\n\t\t\t<a href=\"service_test.php\" title=\"Return to Service Test\">Return to Service Test</a>";
	echo "\n\t\t</div>\n\t</td>\n</tr>\n";
	html_end_box ();
}

/**
 * Eliminar un servicio a testear
 * 
 * @param $id Identificador
 *        	del servicio a modificar
 */
function service_test_delete($id = -1) {
	//Eliminar servicios
	
	html_start_box ( "<strong>DELETE SERVICE!</strong>", "80%", TABLE_HEAD_COLOR, "2", "center", "" );
	print "<tr>\n<td>\n";
	print "<div class='infobox'>\n";
	if ($id != - 1) {
		if (db_execute ( "DELETE FROM serviceTest WHERE id=" . $id )) {
			print '<span style="color:green; font-size:14pt">Service ID #' . $id . ' has been <b>deleted</b>.</span>';
		} else
			print '<span style="color:red; font-size:14pt">Service ID #' . $id . ' could not be deleted. <a href="/cacti/utilities.php?action=view_logfile" title="View Cacti Log">See Cacti log</a> for further information.</span>';
	} else {
		print "<span style=\"color:red; font-size:14pt\">ERROR: No service found with ID #$id.</span>";
	}
	echo "\n<hr><br>\n<a href=\"service_test.php\" title=\"Return to Service Test\">Return to Service Test</a>";
	print "\n</div>\n</td>\n</tr>\n";
	html_end_box ();
}

/**
 * Formulario para confirmar eliminación de un servicio
 * 
 * @param $id Identificador
 *        	del servicio a modificar
 */
function service_test_confirm_delete($id = -1) {
	
	// Eliminar servicio existente
	html_start_box ( "<strong>DELETE SERVICE!</strong>", "80%", TABLE_HEAD_COLOR, "2", "center", "" );
	$cur = db_fetch_row ( "SELECT * FROM serviceTest WHERE id=$id" );
	print "<tr>\n<td>\n";
	if ($id != - 1 && $cur) {
		print "<form action=\"edit_serviceTest.php\" method=\"post\" autocomplete=\"off\">\n";
		print st_printInput ( 'hidden', 'action', 'delete_confirmed' );
		print st_printInput ( 'hidden', 'id', $id );
		// PINTAR LOS DATOS:
		print "<table class='tabla'>\n";
		print "<tr title=\"DNS Name or IP for the Server.\">\n";
		print "\t<td class=\"dato\"> Name</td>\n";
		print "\t<td class=\"valor\">" . htmlentities ( $cur ["name"] ) . "</td>\n";
		print "</tr><tr title=\"Description of the service (50 cars.)\">\n";
		print "\t<td class=\"dato\"> Description</td>\n";
		print "\t<td class=\"valor\">" . htmlentities ( $cur ["description"] ) . "</td>\n";
		print "</tr><tr title=\"Service to Check in the Server\">\n";
		print "\t<td class=\"dato\"> Service</td>\n";
		print "\t<td class=\"valor\">" . htmlentities ( $cur ["service"] ) . "</td>\n";
		print "</tr><tr title=\"Port used by the service.\">\n";
		print "\t<td class=\"dato\"> Port</td>\n";
		print "\t<td class=\"valor\">" . htmlentities ( $cur ["port"] ) . "</td>\n";
		print "</tr>\n";
		print "<tr class='cactiTableTitle'>\n";
		print "\t<td colspan=2 class='pie'>\n";
		print "\t<center><p><span class='alertbox'>Service <b>ID #" . $id . "</b> will be deleted. Are you sure?</span></p>\n";
		print "\t<input type=submit name=\"save\" value=\"DELETE\"/>\n";
		print "\t<input type=button name=\"cancel\" value=\"CANCEL\" onClick=\"window.location.href='" . dirname ( $_SERVER ['PHP_SELF'] ) . "/service_test.php'\"/></center>\n";
		print "\t</td>\n";
		print "</tr>\n";
		print "</table>\n</form>\n";
	} else {
		print "<div class='infobox'>\n";
		print "<span style=\"color:red; font-size:14pt; text-align:center\">Service ID #$id Not Found !!</span>\n<hr>\n";
		echo "<br>\n<a href=\"service_test.php\" title=\"Return to Service Test\">Return to Service Test</a>\n</div>";
	}
	print "</td>\n</tr>\n";
	html_end_box ();
}

/**
 * Guardar cambios y mostrar resultado
 * 
 * @param $id Identificador
 *        	del servicio a modificar
 */
function service_test_save($id = -1) {
	global $post_values;
	
	html_start_box ( "<strong>SAVE SERVICE</strong>", "80%", TABLE_HEAD_COLOR, "2", "center", "" );
	print "<tr>\n<td>\n";
	//Creamos una caja para mostrar el resultado:
	print "<div class='infobox'>\n";
	
	//Transformamos los valores para la sentencia en cuestión:
	if(empty($post_values["enabled"]))
		$post_values["enabled"] = false;
	$values = array($post_values["name"], 
					$post_values["description"], 
					$post_values["service"], 
					$post_values["port"], 
					$post_values["user"], 
					$post_values["pwd"], 
					$post_values["enabled"]);
	$types = array("", "", "", "numeric", "", "", "boolean");
	$values = st_mysql_values($values, $types);
	
	//Hacemos la operación, según sea INSERT o UPDATE...
	if ($id == - 1) // Agregar nuevo servicio a chequear
	{
		$query = "insert into serviceTest (name,description,service,port,user,pwd,enabled) values (";
		$query .= $values[0]. ","; //NAME
		$query .= $values[1]. ","; //DESCR.
		$query .= $values[2]. ","; //SERVICE
		$query .= $values[3]. ","; //PORT
		$query .= $values[4]. ","; //USER
		$query .= $values[5]. ","; //PWD
		$query .= $values[6]. ")"; //ENABLED
	} else { // Se trata de actualizar un registro existente
		$query = "update serviceTest set ";
		$query .= "name=" . $values[0]. ",";
		$query .= "description=" . $values[1]. ",";
		$query .= "service=" . $values[2] . ",";
		$query .= "port=" . $values[3]. ",";
		$query .= "user=" . $values[4]. ",";
		$query .= "pwd=" . $values[5]. ",";
		$query .= "enabled=" . $values[6];
		$query .= " where id=" . $id;
	}
	$result = st_execute_query ( $query ); 
	//$result = db_execute ( $query ); $result = ($result===0); //db_execute retorna 0 si hay error y 1 si es correcto.
	if (! $result['error']) {
		print '<span style="color:green; font-size:14pt">Service ' . $values [2] . '@' . htmlentities($values [0]) . ($values[3]==="null" ? "" : ':' . $values[3]) . ' correctly saved.</span>';
	} else {
		print '<span style="color:red; font-size:14pt">Service ' . $values [2]. '@' . htmlentities($values [0]) . ($values[3]==="null" ? "" : ':' . $values[3]) . ' could not be saved: <br/>&nbsp;&nbsp;&nbsp;&nbsp;<span style="font-size:12pt"><i>' . $result['error'] . '</i></span></span><p> <a href="/cacti/utilities.php?action=view_logfile" title="View Cacti Log">See Cacti log</a> for further information.</p>';
	}
	echo "\n<br><hr>\n<a href=\"service_test.php\" title=\"Return to Service Test\">Return to Service Test</a>";
	print "\n</div>\n</td>\n</tr>\n";
	html_end_box ();
}
?>