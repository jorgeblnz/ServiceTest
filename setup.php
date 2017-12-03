<?php
/**

COLECCIÓN DE FUNCIONES QUE SE DEFINEN PARA INSTALAR Y DESINSTALAR UN PLUGIN EN CACTI
Información sobre cómo crear un Plugin Cacti:
http://docs.cacti.net/plugins:development.create_new#creating_a_plugin

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

//Si se hace una llamada por URL, evitamos cualquier salida por el navegador...
if($_SERVER['PHP_SELF'] === "/cacti/plugins/serviceTest/setup.php")
	exit;

//Estamos situados en el directorio raíz de instalación de CACTI...
include_once './include/global.php'; //Incluimos el archivo php con las configuraciones globales.
include_once './include/config.php'; //Variables la base de datos, si no se han cargado antes.

/**
 * Script de Instalación del plugin serviceTest.
 * Esta función no retorna nada.
 */
function plugin_serviceTest_install () {
	//La variable global de CACTI '$config' ya está accesible
	global $config;
	// Indicamos en el log que se va a instalar el plugin:
	cacti_log("INFO: Installing the 'serviceTest' plugin...", false, 'Service Test');
	
	$msg = "DEBUG: Registering 'serviceTest' plugin hooks... ";
	/**
	 * Cabecera de api_plugin_register_hook:
	 * api_plugin_register_hook(<nombre plugin actual>,<tipo de hook>,<función que se invoca>,<fichero donde está la función>);
	 */
	//AÑADIR TAREA A CACTI: Hacer que el poller de cacti ejecute un script después de realizar sus propias operaciones:
	api_plugin_register_hook('serviceTest', 'poller_bottom', 'serviceTest_CheckAll', 'includes/functions.php');
	
	//AÑADIR PESTAÑA: Pestañas en la vista Console
	api_plugin_register_hook('serviceTest', 'top_header_tabs', 'serviceTest_show_tab', 'includes/tab.php');
	api_plugin_register_hook('serviceTest', 'top_graph_header_tabs', 'serviceTest_show_tab', 'includes/tab.php');
	
	//Personalizar la ruta de navegación que está debajo de las pestañas "Console" y "Graphs"
	api_plugin_register_hook('serviceTest', 'draw_navigation_text', 'serviceTest_draw_navigation_text', 'includes/settings.php');
	
	cacti_log($msg."Done!", false, 'Service Test');
	
	$msg = "DEBUG: Registering 'serviceTest' options for user permissions... ";
	/**
	 * Cabecera de la función function api_plugin_register_realm:
	 * function api_plugin_register_realm (<nombre plugin actual>, <archivo que precisa permisos>, <texto al editar permisos de usuario>, <permiso para admin> = false)
	 */
	//AÑADIR PERMISOS (crear permisos configurables):
	api_plugin_register_realm('serviceTest', 'service_test.php', 'Plugin ServiceTest -> View Services', true);
	api_plugin_register_realm('serviceTest', 'edit_serviceTest.php', 'Plugin ServiceTest -> Add/Configure Services', true);
	api_plugin_register_realm('serviceTest', 'serviceTest_settings.php', 'Plugin ServiceTest -> Edit Plugin Settings', true);
	
	cacti_log($msg."Done!", false, 'Service Test');
	
	include_once($config['base_path'] . '/plugins/serviceTest/includes/database.php');
	serviceTest_setup_database (); //Creamos las tablas necesarias en la BD
	
	cacti_log("INFO: The 'serviceTest' plugin has been installed !!", false, 'Service Test');
} //FIN SETUP

/**
 * Script de Desinstalación del plugin serviceTest
 */
function plugin_serviceTest_uninstall () {
	global $config;
	include_once($config['base_path'] . '/plugins/serviceTest/includes/database.php');
	// Eliminamos los hooks
	api_plugin_remove_hooks ('serviceTest');
	cacti_log("DEBUG: The 'serviceTest' plugin hooks have been removed.", false, 'Service Test');
	// Eliminamos los controles de permisos
	api_plugin_remove_realms ('serviceTest');
	cacti_log("DEBUG: The 'serviceTest' plugin options for user permission have been removed.", false, 'Service Test');
	// Eliminamos lo que hemos metido en la base de datos
	serviceTest_uninstall_database();
	cacti_log("DEBUG: The 'serviceTest' plugin tables have been removed from database.", false, 'Service Test');
	
	//Desisnstalación completa!
	cacti_log("INFO: The 'serviceTest' plugin has been uninstalled.", false, 'Service Test');
}

/**
 * Comprobar si se ha configurado todo lo que debe configurarse antes de instalar el plugin.
 * @return True si la configuración previa es correcta o false en caso contrario.
 */
function plugin_serviceTest_check_config ($debug = false) {
	// Se comprueba si se ha configurado todo lo que debe configurarse antes de instalar el plugin.
	//Comprobar si es una actualización
	serviceTest_check_upgrade ($debug);
	//Comprobar si están instalados los módulos necesarios:
	if (!serviceTest_check_dependencies($debug)){
		if($debug)
			cacti_log("WARNING: The 'serviceTest' plugin 'check_config' result is wrong.", false, 'Service Test');
		return false;
	}else{
		if($debug)
			cacti_log("DEBUG: The 'serviceTest' plugin 'check_config' result is OK!", false, 'Service Test');
		return true;
	}
}

/**
 * Función que realiza una actualización del plugin
 */
function plugin_serviceTest_upgrade () {
	// Here we will upgrade to the newest version
	include_once($config['base_path'] . '/plugins/serviceTest/includes/database.php');
	serviceTest_upgrade_database ();
	return;
}

/**
 * Comprobar si la instalación se trata de una actualización.
 * @return True si se trata de una actualización o false en caso contrario.
 */
function serviceTest_check_upgrade ($debug = false) {
	//Comprobar versión a instalar:
	$current = plugin_serviceTest_version();
	//Recogemos la instalada:
	$installed = db_fetch_cell("SELECT version FROM plugin_config WHERE directory='".$current['name']."'");
	//Vemos si la versión a instalar es mayor que la instalada (si existe):
	if (!empty($installed) && version_compare($current['version'], $installed)>0) {
		if($debug)
			cacti_log("DEBUG: The serviceTest plugin will be upgraded form version '$installed' to version '{$current['version']}'", false, 'Service Test');
		plugin_serviceTest_upgrade ();
		return true;
	}
	if($debug)
		cacti_log("DEBUG: The 'serviceTest' plugin (v{$current['version']}) will be installed (this is not an upgrade operation).", false, 'Service Test');
	//Si no es actualización, retornamos false:
	return false;
}

/**
 * Comprobar si están instalados los módulos necesarios:
 * Actualmente este plugin puede funcionar de forma autónoma.
 * Registra en el log las comprobaciones de diferentes módulos, pero no impide la instalación.
 * @return True si las dependencias están satisfechas o false en caso contrario.
 */
function serviceTest_check_dependencies($debug = false) {
	//Puede funcionar de forma independiente...
	if($debug){
		$current = plugin_serviceTest_version();
		cacti_log("DEBUG: Checking dependencies for 'serviceTest' plugin (v{$current['version']})...", false, 'Service Test');
		// Módulo CURL y ejecutable WGET
		if (extension_loaded('curl'))
			cacti_log("DEBUG: CURL module is loaded... OK!", false, 'Service Test');
		else{
			$aux = system("wget --version");
			if ($aux===false)
				cacti_log("WARNING: WGET function is not present and CURL module is not loaded: Beware of HTTP and HTTPS services...", false, 'Service Test');
			else{
				cacti_log("DEBUG: WGET function is present...", false, 'Service Test');
				cacti_log("WARNING: CURL module is not loaded: WGET will be used to check HTTP and HTTPS services...", false, 'Service Test');
			}
		}
		
		// Módulo OCI-8 para chequeo de Oracle
		if (extension_loaded('oci8') || extension_loaded('oci-8'))
			cacti_log("DEBUG: OCI-8 module is loaded... OK!", false, 'Service Test');
		else
			cacti_log("WARNING: OCI-8 module is not loaded: Beware of Oracle service...", false, 'Service Test');
		
		// Extensión mysqli para chequeo de MySQL
		if (extension_loaded('mysqli'))
			cacti_log("DEBUG: mysqli module is loaded... OK!", false, 'Service Test');
		else
			cacti_log("WARNING: mysqli module is not loaded: Beware of MySQL service...", false, 'Service Test');
	}
	return true;
}

/**
 * Obtener los datos del plugin.
 * @return Array con los datos del plugin (autor, versión, etc...)
 */
function plugin_serviceTest_version () {
	return array(
			'name'		=> 'serviceTest',
			'version' 	=> '0.0.1',
			'longname'	=> 'Service Tester',
			'author'	=> 'Jorge Balanz',
			'homepage'	=> 'https://github.com/jorgeblnz/',
			'email'		=> 'jibalanz@gmail.com',
			'url'		=> 'https://github.com/jorgeblnz/serviceTest'
	);
}

/**
 * Obtener los datos del plugin.
 * @return Array con los datos del plugin (autor, versión, etc...)
 */
function serviceTest_version () {
	return plugin_serviceTest_version();
}
?>