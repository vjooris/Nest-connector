# Nest-connector
Purpose :
---------
The purpose of this php is to interract with the the Nest Learning thermostat
	- read the main informations 
		- current temperature
		- current humidity
		- target temperature
		- eco temperature
		- target mode
		- heating Yes/No
		- heating Yes/No
		- away mode
	- set  the mahor settings
		- target emperature
		- away mode

This development is based on the PHP class developped by gboudreau :
https://github.com/gboudreau/nest-api

Version history :
-----------------
v01.01 by Jojo 		(15/02/2020)	: initial version
v01.02 by Jojo 		(17/02/2020)	: heating info

Syntax :
--------
http://xxxxxxx/Nest.php?
	read=temperature 			 	- display/return current temperature
	read=humidity					- display/return current humidity
	read=target 					- display/return target temperature
	read=eco 						- display/return Eco-temperature
	read=mode 						- display/return target mode
	read=away 						- display/return away mode
	setTmp=xx.x 					- set target temperature to xx.x °C
	setAway=On|Off 					- set Away mode On or Off
	debug=1 						- display debug mode

Initial setup :
----------------
Install this .php, together with the .ini and the nest.class.php, in the same sub-directory of your web folder of your web server
The name of the .ini file must be the same as the one of this .php file.
Look into the .ini file how to enter your credentials
