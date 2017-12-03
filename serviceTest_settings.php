<?php
/**

 PÁGINA PARA EDITAR LAS CONFIGURACIONES GLOBALES DEL SERVICIO DE TESTS

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

// Estilos de tablas
include_once 'includes/styles.php';

// Funciones varias
include_once 'includes/utils.php';

chdir ( '../../' ); // Nos situamos en el directorio "cacti"
require_once './include/auth.php'; // Desde el auth.php de Cacti se cargan las variables globales ($config, por ejemplo)
                                   
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
		"infobox"
), array (
		$formcell,
		$formdata,
		$tableheaders,
		$table,
		$footstyle, 
		$infobox
) );

// Establecemos página de retorno
$pageback = $config ['base_path'] . '/plugins/serviceTest/service_test.php'; // Página anterior...
                                                                          
// Preparamos el entorno de Cacti:
include_once ($config ['include_path'] . '/top_header.php');

// Se incluyen los estilos propios del plugin:
echo $estilos;

// ADECUAMOS LA RESPUESTA SEGÚN EL TIPO DE ACCIÓN
switch (strtolower($action)) {
	case 'edit' : // Editar
		st_service_settings_edit ();
		break;
	case 'save' : // GUARDAR CAMBIOS
		st_service_settings_save ();
		break;
	case 'confirm' : // CONFIRMAR CAMBIOS
		st_service_settings_confirm ();
		break;
	case 'checkmail' :
		st_check_mail ();
		break;
	default :
		// Por defecto, se vuelve atrás
		header ( 'Location: ' . $pageback );
		exit ();
}

// Pie del entrono Cacti
include_once ($config ['include_path'] . '/bottom_footer.php');

/**
 * Presentar el formulario para editar variables de configuración de ServiceTest
 */
function st_service_settings_edit($mailcheck = "") {
	// Editar los parámetros de configuración del plugin:

	$cur = db_fetch_assoc ( "SELECT * FROM serviceTest_settings WHERE id=1" );
	$cur = $cur [0];
	
	//Contenedor de Cacti:
	html_start_box ( "<strong>EDIT SETTINGS</strong>", "80%", TABLE_HEAD_COLOR, "2", "center", "" );
	// Todo va dentro de una casilla:
	print "<tr>\n<td>\n";
	?>
<form action="serviceTest_settings.php" method="post" autocomplete="off">
<?php echo st_printInput('hidden', 'action', 'confirm');?> 
<table class="tabla">
		<tr>
			<th colspan=2 class="separador">General Options</th>
		</tr>
		<tr title="Severity for saved logs">
			<td class="dato">Log Level</td>
			<td class="valor">
				<select name="log_level">
					<option value="ALL" <?php echo ($cur["log_level"]=='ALL'?"SELECTED":""); ?>>ALL</option>
					<option value="ERROR" <?php echo ($cur["log_level"]=='ERROR'?"SELECTED":""); ?>>ERROR</option>
				</select>
			</td>
		</tr>
		<tr>
			<td class="separador">Mailing Options</td>
			<td class="separador">
				<a href="<?php echo st_correctActionURL(basename($_SERVER['PHP_SELF'])); ?>?action=checkmail" title="Check mail configuration" style="color: yellow">Test Mail</a><?php if($mailcheck) echo "<span style=\"color: yellow\">$mailcheck</span>"; ?>
			</td>
		</tr>
		<tr title="Select a configuration: you can select between using the 'settings' plugin mail configuration (if available) or the 'serviceTest' plugin specific mailing configuration.">
			<td class="dato">Select an Option</td>
			<td class="valor">
				<fieldset>
				 <?php echo st_printInput('radio', 'mailoption', "settings", ((empty($cur["mailoption"]) || $cur["mailoption"]=="settings")? "checked" : ""),"Use 'settings' Plugin Configuration");?>
				 <br />
				 <?php echo st_printInput('radio', 'mailoption', "serviceTest", ((isset($cur["mailoption"]) && $cur["mailoption"]=="serviceTest")? "checked" : ""),"Use 'serviceTest' Plugin Configuration");?>
				</fieldset>
			</td>
		</tr>
		<tr title="Mail addresses to send error notices (a commas separated list); 'default' value means that it will try to get the 'notification email' address list from Thold plugin.">
			<td class="dato">Alert Mail List</td>
			<td class="valor">
				<?php echo st_printInput('text','mail_list',(empty($cur["mail_list"])? "default" : $cur["mail_list"]),'maxlength="100" size="30" required'); ?>
			</td>
		</tr>
		<tr title="Mail server name or IP: A valid SMTP Host.">
			<td class="dato">SMTP Host Name</td>
			<td class="valor">
				<?php echo st_printInput('text','mail_server',$cur["mail_server"], 'maxlength="50" size="20"'); ?>
			</td>
		</tr>
		<tr title="Mail server port.">
			<td class="dato">SMTP Host Port</td>
			<td class="valor">
				<?php echo st_printInput('number','mail_port',$cur["mail_port"], 'min="0" max="65535" size="5"'); ?>
			</td>
		</tr>
		<tr title="SMTP Host authentication required.">
			<td class="dato">SMTP Authentication</td>
			<td class="valor"><select name="mail_auth_required">
					<option value="none"
						<?php echo ($cur["mail_auth_required"]=='none'?"SELECTED":""); ?>>None</option>
					<option value="SSL/TLS"
						<?php echo ($cur["mail_auth_required"]=='SSL/TLS'?"SELECTED":""); ?>>SSL/TLS</option>
			</select></td>
		</tr>
		<tr title="Mail user for authentication against the SMTP Host.">
			<td class="dato">User Name</td>
			<td class="valor">
				<?php echo st_printInput('text','mail_user',$cur["mail_user"], 'maxlength="50" size="20"'); ?>
			</td>
		</tr>
		<tr title="Mail password for authentication against the SMTP Host.">
			<td class="dato">User Password</td>
			<td class="valor">
				<?php echo st_printInput('password','mail_pwd',$cur["mail_pwd"], 'maxlength="50" size="20"'); ?>
			</td>
		</tr>
		<tr class='cactiTableTitle'>
			<td colspan=2 class='pie'>
				<input type=submit name="confirm" value="CONTINUE" /> 
				<input type=button name="cancel" value="CANCEL" onClick="window.location.href='<?php echo dirname($_SERVER['PHP_SELF']); ?>/service_test.php'" />
			</td>
		</tr>
	</table>
</form>
<?php
	// Cerramos la casilla:
	print "</td>\n</tr>\n";
	html_end_box ();
}

/**
 * Presentar el formulario para confirmar cambios
 */
function st_service_settings_confirm() {
	// Confirmar cambios:
	global $post_values;
	
	//Contenedor de Cacti:
	html_start_box ( "<strong>SAVE SETTINGS</strong>", "80%", TABLE_HEAD_COLOR, "2", "center", "" );
	// Todo va dentro de una casilla:
	print "<tr>\n<td>\n";
	
	$curl = (isset ( $post_values["use_web_CURL"] ) ? "true" : "false");
	?>
<form action="serviceTest_settings.php" method="post" autocomplete="off">
<?php echo st_printInput('hidden', 'action', 'save')?> 
<?php echo st_printInput('hidden', 'log_level', $post_values["log_level"])?> 
<?php echo st_printInput('hidden', 'mail_list', $post_values["mail_list"])?> 
<?php echo st_printInput('hidden', 'mailoption', $post_values["mailoption"])?> 
<?php echo st_printInput('hidden', 'mail_server', $post_values["mail_server"])?> 
<?php echo st_printInput('hidden', 'mail_port', $post_values["mail_port"])?> 
<?php echo st_printInput('hidden', 'mail_auth_required', $post_values["mail_auth_required"])?> 
<?php echo st_printInput('hidden', 'mail_user', $post_values["mail_user"])?> 
<?php echo st_printInput('hidden', 'mail_pwd', $post_values["mail_pwd"])?> 

<table class="tabla">
		<tr>
			<th colspan=2 class="separador">General Options</th>
		</tr>
		<tr>
			<td class="dato">Log Level</td>
			<td class="valor"><?php echo htmlentities($post_values["log_level"]); ?></td>
		</tr>
		<tr>
			<th colspan=2 class="separador">Mailing Options</th>
		</tr>
		<tr>
			<td class="dato">Plugin Mail Configuration</td>
			<td class="valor"><?php echo htmlentities($post_values["mailoption"]); ?></td>
		</tr>
		<tr>
			<td class="dato">Alert Mail List</td>
			<td class="valor"><?php echo htmlentities($post_values["mail_list"]); ?></td>
		</tr>
		<tr>
			<td class="dato">SMTP Host</td>
			<td class="valor"><?php echo htmlentities($post_values["mail_server"]); ?></td>
		</tr>
		<tr>
			<td class="dato">SMTP Port</td>
			<td class="valor"><?php echo htmlentities($post_values["mail_port"]); ?></td>
		</tr>
		<tr>
			<td class="dato">SMTP Authentication</td>
			<td class="valor"><?php echo htmlentities($post_values["mail_auth_required"]); ?></td>
		</tr>
		<tr>
			<td class="dato">Mail User</td>
			<td class="valor"><?php echo htmlentities($post_values["mail_user"]); ?></td>
		</tr>
		<tr>
			<td class="dato">Mail Password</td>
			<td class="valor"><?php echo (empty($post_values["mail_pwd"])? '' : '********'); ?></td>
		</tr>
		<tr class='cactiTableTitle'>
			<td colspan=2 class='pie'>
				<input type=submit name="save" value="SAVE" /> 
				<input type=button name="cancel" value="CANCEL"
					onClick="window.location.href='<?php echo dirname($_SERVER['PHP_SELF']); ?>/service_test.php'" />
			</td>
		</tr>
	</table>
</form>
<?php
	// Cerramos la casilla:
	print "</td>\n</tr>\n";
	html_end_box ();
}

/**
 * Guardar los cambios y presentar el resultado
 */
function st_service_settings_save() {
	// Guardar cambios
	global $post_values;
	
	//Transformamos los valores para la sentencia en cuestión:
	$values = array($post_values["log_level"],
					$post_values["mail_list"],
					$post_values["mail_server"],
					$post_values["mail_port"],
					$post_values["mailoption"],
					$post_values["mail_auth_required"],
					$post_values["mail_user"],
					$post_values["mail_pwd"]);
	$types = array("", "", "", "numeric", "", "", "", "");
	$values = st_mysql_values($values, $types);
	//Contenedor de Cacti
	html_start_box ( "<strong>SAVE PLUGIN SETTINGS</strong>", "80%", TABLE_HEAD_COLOR, "2", "center", "" );
	// Todo va dentro de una casilla:
	print "<tr>\n<td>\n";
	
	print "<div class='infobox'>\n";
	$query = "update serviceTest_settings set ";
	$query .= "log_level=" . $values [0] . ",";
	$query .= "mail_list=" . $values [1] . ",";
	$query .= "mail_server=" . $values [2] . ",";
	$query .= "mail_port=" . $values [3] . ",";
	$query .= "mailoption=" . $values [4] . ",";
	$query .= "mail_auth_required=" . $values [5] . ",";
	$query .= "mail_user=" . $values [6] . ",";
	$query .= "mail_pwd=" . $values [7];
	//Ejecutamos la consulta:
	$result = st_execute_query ( $query );
	//Presentamos el resultado
	if (! $result['error']) {
		print '<span style="color:green; font-size:14pt">Settings correctly saved.</span>';
	} else {
		print '<span style="color:red; font-size:14pt">Unable to save the new settings: <br/>&nbsp;&nbsp;&nbsp;&nbsp;<span style="font-size:12pt"><i>' . $result['error'] . '</i></span></span><p> See <a href="/cacti/utilities.php?action=view_logfile">cacti log</a> for further information.</p>';
	}
	echo "\n<br><hr>\n<a href=\"service_test.php\" title=\"Return to Service Test\">Return to Service Test</a>";
	print "\n</div>\n";
	// Cerramos la casilla:
	print "</td>\n</tr>\n";
	html_end_box ();
}

/**
 * Enviar un correo con la configuración guardada actualmente y presentar la pantalla de edición con el resultado del envío.
 */
function st_check_mail() {
	include_once 'includes/Testing.php';
	$params = Testing::getSettings ();
	// Creamos el asunto:
	$sub = "Test Message generated from Cacti [Service Test plugin].";
	// Creamos el mensaje:
	$msg = "<html><head><title></title><meta http-equiv=Content-Type content=text/html; charset=UTF-8></head><body>\n";
	$msg .= "This is a test message generated from <i>Cacti</i>.  This message was sent to test the configuration of your <b>Service Test</b> mailing function.<br/>\n";
	$msg .= "<p>*************************************************************<br/>\n";
	if ($params ['mail_list'] == "default") {
		$msg .= "<b>Mailing List</b>: Thold mailing list.<br/>\n";
	} else {
		$msg .= "<b>Mailing List</b>: Service Test mailing list.<br/>\n";
	}
	if (empty ( $params ['mailoption'] ) || $params ['mailoption'] !== 'settings' || ! Testing::check_installed_plugin ( "settings" )) {
		$msg .= "<b>Mailing Engine</b>: Service Test plugin.<br/>\n";
	} else {
		$msg .= "<b>Mailing Engine</b>: Settings plugin.<br/>\n";
	}
	$msg .= "<br/><i>Configuration</i>:<br/>\n";
	$msg .= "<b>Server</b>: {$params['mail_server']}<br/>\n";
	$msg .= "<b>Port</b>: {$params['mail_port']}<br/>\n";
	$msg .= "<b>Authentication</b>: {$params['mail_auth_required']}<br/>\n";
	$msg .= "<b>User</b>: {$params['mail_user']}<br/>\n";
	$msg .= "<b>Password</b>: (Not Shown for Security Reasons)<br/>\n";
	$msg .= "*************************************************************</p>\n";
	$msg .= "</body></html>";
	
	// Intentamos el envío:
	$errors = Testing::sendMail ( $sub, $msg, '', "Service Test Plugin - Check Mail Config." );

	//Comprobamos el resultado del envío:
	if(!$errors){
		st_service_settings_edit("... Test successful!");
	}
	else{
		st_service_settings_edit("... 'ERROR: $errors'");
	}
}

?>
