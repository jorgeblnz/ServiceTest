<?php
/**

 CONFIGURACIONES GLOBALES DEL SERVICIO DEL PLUGIN

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
 * Configurar los mapeos de las rutas de navegación.
 * 
 * @param array $nav Array asociativo con los mapeos        	
 * @return array El array de entrada ampliado con los mapeos del plugin
 */
function serviceTest_draw_navigation_text($nav) {
/*
 * PÁGINA PRINCIPAL
 */
	$nav ['service_test.php:'] = array (
			'title' => 'Service Tester',
			'mapping' => 'index.php:',
			'url' => 'serviceTest.php',
			'level' => '1' 
	);
/*
 * EDITAR SERVICIOS
 */
	//EDIT
	$nav ['edit_serviceTest.php:edit'] = array (
			'title' => 'Edit Service...',
			'mapping' => 'index.php:',
			'url' => 'edit_serviceTest.php',
			'level' => '1' 
	);
	//CONFIRM
	$nav ['edit_serviceTest.php:confirm'] = array (
			'title' => 'Edit Service: Confirm',
			'mapping' => 'index.php:',
			'url' => 'edit_serviceTest.php',
			'level' => '1' 
	);
	//SAVE
	$nav ['edit_serviceTest.php:save'] = array (
			'title' => 'Edit Service: Save!',
			'mapping' => 'index.php:',
			'url' => 'edit_serviceTest.php',
			'level' => '2' 
	);
	//ENABLE
	$nav ['edit_serviceTest.php:enable'] = array (
			'title' => 'Enable/Disable Service',
			'mapping' => 'index.php:',
			'url' => 'edit_serviceTest.php',
			'level' => '1' 
	);
	//DELETE
	$nav ['edit_serviceTest.php:delete'] = array (
			'title' => 'Remove Service...',
			'mapping' => 'index.php:',
			'url' => 'edit_serviceTest.php',
			'level' => '1' 
	);
	//DELETE_CONFIRMED
	$nav ['edit_serviceTest.php:delete_confirmed'] = array (
			'title' => 'Remove Service: Delete!',
			'mapping' => 'index.php:',
			'url' => 'edit_serviceTest.php',
			'level' => '1' 
	);
/*
 * CONFIGURACIÓN DEL PLUGIN
 */
	//EDIT
	$nav ['serviceTest_settings.php:edit'] = array (
			'title' => 'ServiceTest Plugin Settings: Edit...',
			'mapping' => 'index.php:',
			'url' => 'serviceTest_settings.php',
			'level' => '1' 
	);
	//CONFIRM
	$nav ['serviceTest_settings.php:confirm'] = array (
			'title' => 'ServiceTest Plugin Settings: Confirm',
			'mapping' => 'index.php:',
			'url' => 'serviceTest_settings.php',
			'level' => '1' 
	);
	//SAVE
	$nav ['serviceTest_settings.php:save'] = array (
			'title' => 'ServiceTest Plugin Settings: Save!',
			'mapping' => 'index.php:',
			'url' => 'serviceTest_settings.php',
			'level' => '1' 
	);
	//CHECKMAIL
	$nav ['serviceTest_settings.php:checkmail'] = array (
			'title' => 'ServiceTest Plugin Settings: Check Mail Config...',
			'mapping' => 'index.php:',
			'url' => 'serviceTest_settings.php',
			'level' => '1' 
	);
	return $nav;
}
?>