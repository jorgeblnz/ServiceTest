<?php
/**
 
  PÁGINA PRINCIPAL DEL PLUGIN
 
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

chdir ( '../../' ); // Nos situamos en el directorio "cacti"
require_once './include/auth.php'; // Desde el auth.php de Cacti se cargan las variables globales ($config, por ejemplo)
                                   
// Establecemos tiempo y página de refresco
$refresh = 30;
// Se puede indicar por separado tiempo y página de refresco, pero se presentan errores de código HTML...
// $refresh["seconds"] = 180;
// $refresh["page"] = "plugins/serviceTest/service_test.php";

// Cabecera del entorno Cacti
include_once ($config ['include_path'] . '/top_header.php');

// Caja/Tabla con los datos:
html_start_box ( "<strong>SERVICES</strong>", "100%", TABLE_HEAD_COLOR, "6", "center", "edit_serviceTest.php?action=edit&id=-1" );
?>
<tr>
	<td>
<?php

echo generateStyles ( array (
		'tabla',
		'titulo',
		'celda',
		'up',
		'down',
		'unknown',
		'unavailable',
		'inactiveoption' 
), array (
		$tablestyle,
		$titlestyle,
		$cellstyle,
		STYLE_UP,
		STYLE_DOWN,
		STYLE_UNKNOWN,
		STYLE_UNAVAILABLE,
		INACTIVE_OPTION 
) );
?>
<table class="tabla">
			<!--CABECERA-->
			<tr class="titulo">
				<th width="75">Actions</th>
				<th width="25">ID</th>
				<th>Description</th>
				<th>Server (Name or IP)</th>
				<th>Service</th>
				<th>Status</th>
			</tr>
<!-- RELLENO de tabla de servicios -->
<?php
//Recuperamos todos los servicios de la base de datos:
$services = db_fetch_assoc ( 'SELECT description, service, id, name, last_check as status, enabled FROM serviceTest' );
//Si hay alguno, lo presentamos
if (sizeof ( $services ) > 0) {
	$loganchor = "";
	$count = 1;
	foreach ( $services as $fila ) {
		$estilo = "";
		$titulo = "";
		if (! $fila ['enabled']) { // No se debe chequear => Lo presento como inhabilitado:
			$estilo = 'unavailable';
			$titulo = UNAVAILABLE_TITLE;
		} else {
			switch ($fila ['status']) {
				case 'UP' :
					$estilo = 'up';
					$titulo = UP_TITLE;
					break;
				case 'DOWN' :
					$estilo = 'down';
					$titulo = DOWN_TITLE;
					break;
				default :
					$estilo = 'unknown';
					$titulo = UNKNOWN_TITLE;
					break;
			}
		}
		echo "<!-- Servicio $count -->\n";
		echo "<tr class='$estilo' $titulo>\n";
		echo "\t<td class='celda'>\n";
		// Se comprueba si el usuario tiene permiso para editar configuraciones del test de servicio
		if (api_user_realm_auth ( 'edit_serviceTest.php' )) {
			echo "\t<table width=100%>\n\t <tr bgcolor=\"white\" align=\"center\">\n";
			$enabletitle = ($fila ['enabled'] ? "src=\"images/disable.png\" title=\"Disable Test\" alt=\"Disable Test\"" : "src=\"images/enable.png\" title=\"Enable Test\" alt=\"Enable Test\"");
			echo "\t\t<td><a href=\"" . htmlspecialchars ( 'edit_serviceTest.php?action=enable&id=' . $fila ["id"] ) . "\"><img $enabletitle border=\"0\" /></a></td>\n";
			echo "\t\t<td><a href=\"" . htmlspecialchars ( 'edit_serviceTest.php?action=edit&id=' . $fila ["id"] ) . "\"><img src=\"images/edit_object.gif\" border=\"0\" alt=\"Edit Service\" title=\"Edit Service\"/></a></td>\n";
			echo "\t\t<td><a href=\"" . htmlspecialchars ( 'edit_serviceTest.php?action=delete&id=' . $fila ["id"] ) . "\"><img src=\"images/delete.gif\" border=\"0\" alt=\"Edit Service\" title=\"Delete Service\"/></a></td>\n";
			echo "\t </tr>\n\t</table>\n";
		} else { // Si no tiene permiso, se presentan las opciones deshabilitadas:
			echo "\t<table width=\"100%\">\n\t <tr>\n";
			echo "\t\t<td><img class='inactiveoption' src=\"" . ($fila ['enabled'] ? "images/disable.png\" title=\"You Cannot Edit Services\"" : "images/enable.png\" title=\"You Cannot Edit Services\"") . "/></td>\n";
			echo "\t\t<td><img class='inactiveoption' src=\"images/edit_object.gif\" title=\"You Cannot Edit Services\"/></td>\n";
			echo "\t\t<td><img class='inactiveoption' src=\"images/delete.gif\" title=\"You Cannot Delete Services\"/></td>\n";
			echo "\t </tr>\n\t</table>\n";
		}
		echo "\t</td>\n";
		echo "\t<td class='celda'>" . htmlentities($fila ['id']) . "</td>\n";
		echo "\t<td class='celda'>" . htmlentities($fila ['description'], ENT_QUOTES) . "</td>\n";
		echo "\t<td class='celda'>" . htmlentities($fila ['name'], ENT_QUOTES) . "</td>\n";
		echo "\t<td class='celda'>" . htmlentities($fila ['service']) . "</td>\n";
		echo "\t<td class='celda'>" . htmlentities($fila ['status']) . "</td>\n";
		echo "</tr>\n";
		echo "<!-- Fin del Servicio $count -->\n";
		$count++;
	}
	echo "<tr><td colspan=6 style=\"color: #cccccc\">".sizeof($services)." Services Found.</td></tr>\n";
} else {
	echo "<td colspan=6 class='unknown'>No services found...</td>";
}
?>
<!-- FIN del RELLENO (tabla de servicios) -->
		</table>
	</td>
</tr>
<?php
// Cerramos la "caja estándar"
html_end_box ();

// Se comprueba si el usuario tiene permiso para editar configuraciones de tests o del servicio (globales) o acceso a los logs.
$st_edit = api_user_realm_auth ( 'edit_serviceTest.php' );
$st_sets = api_user_realm_auth ( 'serviceTest_settings.php' );
$st_log = api_user_realm_auth ( '/cacti/utilities.php' );
if ($st_edit || $st_sets) {
	//Creamos la tabla de opciones
	html_start_box ( "<strong>OPTIONS</strong>", "70%", TABLE_HEAD_COLOR, "2", "center", "" );
	
	print "<tr><td>\n";
	print "<!-- TABLA DE OPCIONES -->\n";
	print "<table width=\"100%\" align=\"center\" bgcolor=\"#6d88ad\">\n";
	print "\t<tr><td>\n";
	// Si se permite la opción de editar servicios a chequear, se presenta la opción:
	if ($st_edit){
		$st_edit = "\t\t<a href=\"" . htmlspecialchars ( 'edit_serviceTest.php?action=edit&id=-1' ) . '" title="Add New Service">'."\n";
		$st_edit .= "\t\t\t".'<img src="images/new_object.png" border="0" alt="Add Service" title="Add New Service"> Add a New Service'."\n";
		$st_edit .= "\t\t</a>\n";
	}
	else
		$st_edit = '<img class="inactiveoption" src="images/new_object.png" alt="Add Service" title="Not Authorized to add">' . "\n";
	print $st_edit;
	print "\t</td></tr>\n\t<tr><td>\n";
	// Si se permite la opción de editar configuración del plugin, se presenta la opción:
	if ($st_sets){
		$st_sets = "\t\t<a href=\"" . htmlspecialchars ( 'serviceTest_settings.php?action=edit' ) . '" title="Edit ServiceTest Settings">'. "\n";
		$st_sets .= "\t\t\t".'<img src="images/edit_object.gif" border="0" alt="Edit ServiceTest Settings" title="Edit ServiceTest Settings"> Edit Service Test General Settings' . "\n";
		$st_sets .= "\t\t</a>\n";
	}
	else
		$st_sets = '<img class="inactiveoption" src="images/edit_object.gif" alt="Edit ServiceTest Settings" title="Not Authorized to Edit ServiceTest Settings">' . "\n";
	print $st_sets;
	print "\t</td></tr>\n";
	print "\t<tr><td>\n";
	if ($st_log){
		$st_log = "\t\t<a href=\"/cacti/utilities.php?action=view_logfile\" title=\"View Cacti Log\">\n";
		$st_log .= "\t\t\t".'<img src="images/log.png" width=16 height=16 border="0" alt="View Cacti Logs" title="View Cacti Log"> View Cacti Log' . "\n";
		$st_log .= "\t\t</a>\n";
	}
	else
		$st_log = '<img class="inactiveoption" src="images/log.png" width=16 height=16 alt="Edit ServiceTest Settings" title="Not Authorized to See Cacti Logs">' . "\n";
	print $st_log;
	print "\t</td></tr>\n";
	print "</table>\n";
	print "<!-- FIN DE LA TABLA DE OPCIONES -->\n";
	print "</td></tr>\n";
	html_end_box ();
}

// Ponemos el pie de la página
include_once ($config ["include_path"] . "/bottom_footer.php");
?>
