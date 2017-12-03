<?php
/**

 MOSTRAR PESTAÑA DEL PLUGIN

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
 * Hacer que se vea la pestaña del plugin si el usuario está autorizado.
 * Si no está autorizado la pestaña no aparece.
 */
function serviceTest_show_tab() {
	global $config;
	if (api_user_realm_auth ( basename ( $_SERVER ['PHP_SELF'] ) )) {
		$cp = false;
		if (basename ( $_SERVER ['PHP_SELF'] ) == 'service_test.php' || basename ( $_SERVER ['PHP_SELF'] ) == 'edit_serviceTest.php' || basename ( $_SERVER ['PHP_SELF'] ) == 'serviceTest_settings.php')
			$cp = true;
		
		print '<a href="' . $config ['url_path'] . 'plugins/serviceTest/service_test.php"><img src="' . $config ['url_path'] . 'plugins/serviceTest/images/tab_serviceTest' . ($cp ? '_down' : '') . '.gif" alt="ServiceTest" align="absmiddle" border="0"></a>';
	}
}
?>
