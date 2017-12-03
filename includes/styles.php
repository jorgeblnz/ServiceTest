<?php
/**

 PÁGINA DE DEFINICIONES DE ESTILOS PARA TABLAS DE FORMULARIOS:

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

// /////////COLORES PARA FILAS DE LA PÁGINA PRINCIPAL
define ( "UP_COLOR", "#A0EE83" );
define ( "DOWN_COLOR", "#F67B6D" );
define ( "UNKNOWN_COLOR", "#FDBC67" );
define ( "UNAVAILABLE_COLOR", "#BBBBBB" );
define ( "TABLE_HEAD_COLOR", "00438C" );
define ( "TABLE_FOOT_COLOR", "00438C" );
define ( "TABLE_SEPARATOR_COLOR", "334499" );
// /////////ESTILOS DE FILAS DE PÁG. PRINCIPAL
define ( "STYLE_UP", "background:" . UP_COLOR . ";border:1px solid #F73131;padding:4px;" );
define ( "STYLE_DOWN", "background:" . DOWN_COLOR . ";border:1px solid #F73131;padding:4px;" );
define ( "STYLE_UNKNOWN", "background:" . UNKNOWN_COLOR . ";border:1px solid #F73131;padding:4px;" );
define ( "STYLE_UNAVAILABLE", "background:" . UNAVAILABLE_COLOR . ";border:1px solid #F73131;padding:4px;" );
// ////////COMENTARIOS DE ESTADOS DE SERVICIOS DE PÁG. PRINCIPAL
define ( "UP_TITLE", "title='Service status is OK!'" );
define ( "DOWN_TITLE", "title='Service status FAILURE!!'" );
define ( "UNKNOWN_TITLE", "title='Unknown Service Status...'" );
define ( "UNAVAILABLE_TITLE", "title='This Service is not checked.'" );
//Acción inhabilitada para un servicio
define ( "INACTIVE_OPTION", "border:1px solid black;filter:blur(1px);" );

// ///////////TABLA:
function tablaStyle($fontcolor = "FFFFFF", $background = '', $textalign = "left", $fontsize = "12px", $width = "100%", $align = "center", $border = 'solid 2px black', $radius = "5px") {
	$table = cabeceraStyle ( $fontcolor, $background, $textalign, $width, $radius, "2px", $border, $fontsize, '', '' );
	$table .= ($align ? " align: $align;" : "");
	return $table;
}
/**
 * Tablas de Formularios
 */
$table = tablaStyle ();
/**
 * Tabla de página Principal
 */
$tablestyle = tablaStyle ( $border = "2px solid black", $background = TABLE_HEAD_COLOR, $radius = 4 );

// ////////////CABECERAS DE TABLA
/**
 * Crear un estilo CSS para celdas de tipo "cabecera de tabla"
 * @param string $fontcolor Color del texto
 * @param string $backgroundcolor Color de fondo
 * @param string $textalign Alineación
 * @param string $width Ancho
 * @param string $radius Radio de redondeo de esquinas
 * @param string $padding Distancias al borde de celda
 * @param string $border Ancho de los bordes de celdas
 * @param string $fontsize Tamaño de letra
 * @param string $fontweight Peso del texto (bold, por ejemplo)
 * @param string $fontname Tipo de letra
 * @return string Una cadena con el formato/estilo en CSS
 */
function cabeceraStyle($fontcolor = 'ffffff', $backgroundcolor = '00438C', $textalign = "left", $width = "100%", $radius = "6px", $padding = "4px", $border = 'solid 1px #000080', $fontsize = "12px", $fontweight = 'bold', $fontname = "Arial,Times New Roman") {
	$stl = cellStyle ( $fontcolor, $fontname, $backgroundcolor, $textalign, $radius, $padding, $fontsize, $fontweight );
	$stl .= ($border ? " border: $border;" : "");
	$stl .= ($width ? " width: $width;" : "");
	;
	return $stl;
}
/**
 * Cabecera de apartados de formularios
 */
$tableheaders = cabeceraStyle ( 'e7e5e5', TABLE_SEPARATOR_COLOR, "left", "", "6px", "1px" );
/**
 * Cabecera de Página Principal
 */
$titlestyle = cabeceraStyle ( 'ffffff', TABLE_HEAD_COLOR, "left", "100%", "4px", "6px" );
/**
 * Pies de tabla (para botones de formularios o similar)
 */
$footstyle = cabeceraStyle ( 'ffffff', TABLE_HEAD_COLOR, "center", '' );

// Celdas
/**
 * Crear un estilo CSS para celdas en general
 * @param string $fontcolor Color del texto
 * @param string $backgroundcolor Color de fondo
 * @param string $textalign Alineación
 * @param string $radius Radio de redondeo de esquinas
 * @param string $padding Distancias al borde de celda
 * @param string $fontsize Tamaño de letra
 * @param string $fontweight Peso del texto (bold, por ejemplo)
 * @param string $fontname Tipo de letra
 * @return string Una cadena con el formato/estilo en CSS
 */
function cellStyle($fontcolor = '', $fontname = '', $backgroundcolor = '', $textalign = "", $radius = "2px", $padding = "2px 4px 2px 4px", $fontsize = '', $fontweight = '') {
	$stl = ($fontcolor ? " color: #$fontcolor;" : "");
	$stl .= ($backgroundcolor ? " background: #$backgroundcolor;" : "");
	$stl .= ($fontsize ? " font-size: $fontsize;" : "");
	$stl .= ($fontname ? " font-family: $fontname;" : "");
	$stl .= ($fontweight ? " font-weight: $fontweight;" : "");
	$stl .= ($padding ? " padding: $padding;" : "");
	;
	$stl .= ($textalign ? " text-align: $textalign;" : "");
	if ($radius) {
		$stl .= " border-radius: $radius;";
		$stl .= " -webkit-border-radius: $radius;";
		$stl .= " -moz-border-radius: $radius;";
	}
	return $stl;
}
$cellstyle = cellStyle ();

$formcell = cellStyle ( '000000', '', 'c5c5c5', 'left', "5px", '4px', '10px', 'bold' );
$formdata = cellStyle ( '001122', '', 'd5d5d5', 'left', "5px", '4px', '10px' );

// Recuadros:
$alertbox = 'background:white;color:red;border: solid 2px red; font-size:12px; padding: 3px 4px 3px 4px;';
$infobox = 'background:#fff5e6;color:black;border: solid 2px black; font-size:12px; padding: 3px 4px 3px 4px;';
$infobox .= 'border-radius:6px; -moz-border-radius:4px; -webkit-border-radius:4px;';

/**
 * Generar un estilo o un conjunto de estilos
 * 
 * @param $classnames Nombre
 *        	de las clases para los estilos
 * @param $styles Cuerpo
 *        	de los estilos (código CSS)
 */
function generateStyles($classnames, $styles) {
	$stl = "<!-- Estilos de Service Test -->\n";
	$stl .= "<style type=\"text/css\">\n";
	// Vemos si ambos parámetros son arrays
	if (is_array ( $classnames ) && is_array ( $styles )) {
		$tope = max ( count ( $classnames ), count ( $styles ) );
		for($i = 0; $i < $tope; $i ++) {
			// Debe salir: .classname{<style>}
			$stl .= "\t." . $classnames [$i] . "{" . $styles [$i] . "}\n";
		}
	}	// Vemos si ambos NO son arrays => lo considero un estilo "suelto"
	elseif (is_array ( $classnames ) && is_array ( $styles )) {
		$stl .= "." . $classnames . "{" . $styles . "}\n";
	} 	// Si uno lo es y otro no => nada
	else {
		$stl .= "<!-- No se cargan estilos para el plugin serviceTest -->\n";
	}
	$stl .= "</style>\n";
	$stl .= "<!-- Fin de los estilos -->\n";
	return $stl;
}
?>