<?php
/**
 
 FUNCIONES PARA CREAR Y ELIMINAR LAS TABLAS NECESARIAS DEL PLUGIN
 
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
 * Actualizar las tablas para el plugin
 */
function serviceTest_upgrade_database () {
	return;
} //FIN UPGRADE

/**
 * Crear las tablas para el Plugin serviceTest
 */
function serviceTest_setup_database () {
	global $config;
	include_once($config['library_path'] . '/database.php');

//Tabla de Tipos de Servicios
	$data = "CREATE TABLE IF NOT EXISTS serviceTest_services (";
	$data.= "service varchar(15) NOT NULL COMMENT 'Nombre común de un servicio',";
	$data.= "default_port int(5) NOT NULL COMMENT 'Puerto de escucha, por defecto, del servicio',";
	$data.= "ok_code varchar(10) COMMENT 'Código devuelto por el servicio que se considera correcto',";
	$data.= "comments varchar(100) default '' COMMENT 'Aclaraciones sobre el servicio',";
	$data.= "CONSTRAINT PK_SERVICETEST_SERVICE PRIMARY KEY (service),";
	$data.= "CONSTRAINT CHK_SERVICETEST_DEFAULTPORT CHECK (default_port BETWEEN 0 AND 65535)";
	$data.= ") ENGINE='InnoDB' COMMENT 'Table of Services which could be tested'";
	db_execute($data);
	cacti_log(" SQL : Table 'serviceTest_services' for serviceTest plugin was created (1/3).", false, 'Service Test');
	//Meto los servicios implementados
	db_execute("INSERT INTO serviceTest_services VALUES ('HTTP',80,'200','HTTP Service (Web Server)')");
	db_execute("INSERT INTO serviceTest_services VALUES ('HTTPS',443,'200','Secure HTTP Service (Secure Web Server)')");
	db_execute("INSERT INTO serviceTest_services VALUES ('MySQL',3306,NULL,'MySQL Database')");
	db_execute("INSERT INTO serviceTest_services VALUES ('Oracle',1521,NULL,'Oracle Database')");
		
//Tabla de Servidores (Servicios en Servidores)
	$data = "CREATE TABLE IF NOT EXISTS serviceTest (";
	$data.= "id int(11) NOT NULL auto_increment,";
	$data.= "name varchar(50) NOT NULL COMMENT 'Nombre o IP servidor',";
	$data.= "description varchar(50) NOT NULL COMMENT 'Nombre identificativo de un servicio en un servidor',";
	$data.= "service varchar(15) NOT NULL COMMENT 'Debe coincidir con un servicio de la tabla de servicios',";
	$data.= "port int(5) default 0 COMMENT 'Puerto por el que el servicio escucha',";
	$data.= "user varchar(50) COMMENT 'Usuario, si requiere autenticación',";
	$data.= "pwd varchar(50) COMMENT 'Password, si requiere autenticación',";
	$data.= "last_check varchar(7) default 'UNKNOWN' COMMENT 'Último estado detectado: UP, DOWN o UNKNOWN',";
	$data.= "enabled BOOLEAN NOT NULL default true COMMENT 'Indica si se chequea',";
	$data.= "CONSTRAINT PK_SERVICETEST PRIMARY KEY (id),";
	$data.= "CONSTRAINT FK_SERVER_SERVICE FOREIGN KEY (service) REFERENCES serviceTest_services(service) ON DELETE CASCADE ON UPDATE CASCADE,";
	$data.= "CONSTRAINT UNQ_SERVICETEST UNIQUE (name,service),";
	$data.= "CONSTRAINT CHK_SERVICETEST_PORT CHECK (port BETWEEN 0 AND 65535),";
	$data.= "CONSTRAINT CHK_SERVICETEST_OK CHECK (last_log IN ('UP','DOWN','UNKNOWN'))";
	$data.= ") ENGINE='InnoDB' COMMENT 'Services to Test on Servers'";
	db_execute($data);
	cacti_log(" SQL : Table 'serviceTest' for serviceTest plugin was created (2/3).", false, 'Service Test');
	//Por defecto meto el localhost
	$data = "INSERT INTO `serviceTest` (`id`, `name`, `description`, `service`, `port`, `user`, `pwd`, `last_check`, `enabled`) VALUES ";
	$data .= "(1, 'localhost', 'Local Web Server', 'HTTP', 80, NULL, NULL, 'UNKNOWN', true), ";
	$data .= "(2, 'localhost', 'Local MySQL Service', 'MySQL', 3306, NULL, NULL, 'UNKNOWN', true)";
	// Si se quiere usar ususario y contraseña de Cacti, usar lo siguiente:
	//$data .= "(2, 'localhost', 'Local MySQL Service', 'MySQL', 3306, '$database_username', '$database_password', 'UNKNOWN', true)";
	db_execute($data);

//Tabla de Configuraciones Globales
	$data = "CREATE TABLE IF NOT EXISTS serviceTest_settings (";
	$data.= "id int(2) NOT NULL,";
	$data.= "log_level varchar(5) NOT NULL DEFAULT 'ALL' COMMENT 'Nivel de evento que se guarda',";
	$data.= "mail_list varchar(100) DEFAULT 'default' COMMENT 'Lista de destinatarios de Correo para eventos',";
	$data.= "mail_server varchar(50) DEFAULT NULL, ";
	$data.= "mail_port int(5) DEFAULT NULL, ";
	$data.= "mail_user varchar(50) DEFAULT NULL, ";
	$data.= "mail_pwd varchar(50) DEFAULT NULL, ";
	$data.= "mail_auth_required varchar(10) NOT NULL DEFAULT 'none', ";
	$data.= "mailoption varchar(15) NOT NULL DEFAULT 'settings', ";
	$data.= "CONSTRAINT PK_SERVICETEST_SETTINGS PRIMARY KEY (id),";
	$data.= "CONSTRAINT CHK_SERVICETEST_MAILPORT CHECK (mail_port BETWEEN 0 AND 65535),";
	$data.= "CONSTRAINT CHK_SERVICETEST_LOGLEVEL CHECK (log_level IN ('ERROR','ALL','NONE')),";
	$data.= "CONSTRAINT CHK_SERVICETEST_MAILOPTION CHECK (mailoption IN ('settings','serviceTest')),";
	$data.= "CONSTRAINT CHK_SERVICETEST_MAIL_AUTH CHECK (mail_auth_required IN ('none','SSL/TLS'))";
	$data.= ") ENGINE='InnoDB' MAX_ROWS=1 COMMENT 'Table of Settings for serviceTest'";
	db_execute($data);
	//Insertar valores por defecto:
	$data = "INSERT INTO `serviceTest_settings` (`id`, `log_level`, `mail_list`, `mail_server`, `mail_port`, `mail_user`, `mail_pwd`, `mail_auth_required`, `mailoption`) VALUES ";
	$data .= "(1, 'ALL', 'default', null, null, null, null, 'none', 'settings')";
	db_execute($data);
	cacti_log(" SQL : Table 'serviceTest_settings' for serviceTest plugin was created (3/3).", false, 'Service Test');
	
}//Fin función setup_database

/**
 * Eliminar las tablas relacionadas con este plugin
 */
function serviceTest_uninstall_database () {
	global $config;
	include_once($config['library_path'] . '/database.php');

//Eliminamos las tablas que habíamos creado (en orden inverso a como se crearon):
//Tabla de Servidores
	$data = "DROP TABLE IF EXISTS serviceTest";
	db_execute($data);
	cacti_log(" SQL : Table 'serviceTest' of serviceTest plugin was removed (1/3).", false, 'Service Test');
//Tabla de Servicios
	$data = "DROP TABLE IF EXISTS serviceTest_services";
	cacti_log(" SQL : Table 'serviceTest_services' of serviceTest plugin was removed (2/3).", false, 'Service Test');
	db_execute($data);
//Tabla de Configuraciones
	$data = "DROP TABLE IF EXISTS serviceTest_settings";
	cacti_log(" SQL : Table 'serviceTest_settings' of serviceTest plugin was removed (3/3).", false, 'Service Test');
	db_execute($data);
}//Fin función uninstall_database
