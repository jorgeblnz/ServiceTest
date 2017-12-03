# serviceTest
## [Cacti plugin]

#### Author .........Jorge Balanz
#### Contact ........jibalanz@gmail.com
#### Home Site ......https://github.com/jorgeblnz
#### Program ........serviceTest
#### Version ........0.0.1
#### Purpose ........Cacti plugin for testing some kind of services

--------------------------------------------
## Purpose

    Test the status of some services (HTTP, HTTPS, MySQL, ORACLE,...) running on servers.

--------------------------------------------
## Features

	API for sending mails
	Check HTTP and HTTPS services
	Check MySQL service
	Check Oracle service

--------------------------------------------
## Installation

	You need to install a tool for checking the HTTP service: may be the CURL module for PHP or WGET (installed by default in most Linux systems).
	
	You need to load mysqli extension for checking MySQL service.
	
	You need to install a tool for checking Oracle service. You must install the OCI-8 module for PHP.

	If you have not already done so, install the Plugin Architecture
	http://cactiusers.org/wiki/PluginArchitectureInstall

	Next install this Plugin using these directions
	http://cactiusers.org/wiki/PluginsInstall
	
	For more information on this plugin
	http://cactiusers.org/wiki/
	
    
--------------------------------------------
## Changelog

	--- 0.0.1 ---
		First release!
