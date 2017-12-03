<?php
/**
 
 FUNCIONES DEL PLUGIN PARA EJECUTAR EN CADA LLAMADA (POLLING) DE CACTI
 
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

//Importamos la clase que hace los tests:
include_once 'Testing.php';

/**
 * Lanzar la comprobación de todos los servicios listados en la tabla serviceTest
 */
function serviceTest_CheckAll(){
	$test = null;
	$result = null;
	$services = null;
	
	//Recuperamos el listado de elementos a chequear:
	$query="SELECT id, name as server, ST.service as service, user, pwd, port, default_port, ";
	$query.="ST.enabled as enabled, ok_code, last_check ";
	$query.="FROM serviceTest ST INNER JOIN serviceTest_services STS on ST.service=STS.service";
	$query.=" ORDER BY ST.service, id";
	$services = db_fetch_assoc($query);
	//Comprobamos que se haya recuperado algo...
	if(!empty($services)){
		//Realizamos comprobación para cada servicio
		foreach($services as $srv){
			//Compruebo si hay que chequearlo antes de hacerlo.
			if($srv['enabled']==1){
				//Me aseguro de enviar un puerto válido.
				if(!$srv['port']){
					$srv['port'] = $srv['default_port'];
				}
				elseif (!is_numeric($srv['port']) || intval($srv['port'])<1 || intval($srv['port'])>65535){
					//Si es un puerto no válido, guardo una reseña en el log...
					cacti_log("[ID #{$srv['id']}; {$srv['service']}@{$srv['server']}] WARNING: Wrong port '{$srv['port']}'. Using default port '{$srv['default_port']}' instead...", false, 'Service Test');
					$srv['port'] = $srv['default_port'];
				}
				$test = new Testing($srv); //Creamos un objeto Testing
				$test->check(); //Realizamos el test
				$test->evaluate(); //Realizamos la evaluación del test
				unset($test); //Libero recursos.
			}
		}
	}
	else{
		cacti_log("WARNING: Unable to get the services list to check!", false, 'Service Test');
	}
}
?>
