<?php 
/*
##Nest connector##

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
v01.03 by Jojo		(20/02/2020)	: optimisation of .ini file upload

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
---------------
Install this .php, together with the .ini and the nest.class.php, in the same sub-directory of your web folder of your web server
The name of the .ini file must be the same as the one of this .php file.
Look into the .ini file how to enter your credentials
Validated with PHP 7.0 (if PHP >= 7.2, then errors) on a NAS Synology.
*/
$CodeVersion = "v01.03";

// INITIALISATION
// ---------------

// from .ini file (.ini file mut have the same name as the running script)
$ini_array = parse_ini_file(substr(basename($_SERVER['SCRIPT_NAME']).PHP_EOL, 0, -4)."ini");
$issue_token = $ini_array['issue_token'];
$cookies = $ini_array['cookies'];

// auto configuration
$ip = $_SERVER['SERVER_ADDR']; 					// IP-Adress of your Web server hosting this script
$file = $_SERVER['PHP_SELF'];  					// path & file name of this running php script
//$dirname = pathinfo($file, PATHINFO_DIRNAME);	// relative path
//	if ($dirname == "/") {$dirname = "";}
//$dirnamefull = getcwd();						// full path : expl /volume1/web/...

// URL parameters
$read = $_GET['read'];
$setTmp = $_GET['setTmp'];
$setAway = $_GET['setAway'];
// if parameter specified, use the specified one
if ($_GET["debug"] != NULL) {$debug = 1;}
if ($_GET["refresh"] != NULL) {$refresh = $_GET["refresh"];}

// load specific PHP class
require_once('nest.class.php');

// initialize Nest API
$nest = new Nest(NULL, NULL, $issue_token, $cookies);

// Debug Alert
if ($debug) {echo "<hr>DEBUG ENABLED<br>!!!!do not use debug parameter WHEN CODE IS IN PRODUCTION !!!!<hr>";}
if ($debug) {
	echo "read = -".$read."-<br>";
	echo "setTmp = -".$setTmp."-<br>";
	echo "setAway = -".$setAway."-<br>";
	echo "<hr>";
	echo "App Version : ".$CodeVersion."<br>";
	echo "PHP Version : ".phpversion()."<br>";
	echo "<hr>";
}
// --------------------------------------------------------------------------

// actions
if ($setTmp != NULL) {
	$success = $nest->setTargetTemperature((float) $setTmp);
	if ($debug) {
		echo "setTmp to ".$setTmp."°".$infos->scale." - success : ".$success."<br>";
	}
}
if ($setAway != NULL) {
	if ($setAway == "On") {
		$success = $nest->setAway(AWAY_MODE_ON);
	} else {
		$success = $nest->setAway(AWAY_MODE_OFF);
	}
	if ($debug) {
		echo "setAway to ".$setAway." - success : ".$success."<br>";
	}
}
// Get the device information:
$infos = $nest->getDeviceInfo();
// display $infos raw content for dev purpose
if ($debug) {print_r($infos);}
if ($debug) {echo "<br>";}
if ($debug) {echo "<br>";}
if ($debug) {var_dump($infos);}
if ($debug) {echo "<br>";}

/* strucutre de $infos
[current_state] => stdClass Object 
	[mode] => heat | off | heat,auto-eco,away
	[temperature] => 20.73999
	[backplate_temperature] => 20.73999 
	[humidity] => 42 
	[ac] => 
	[heat] =>   | 1 (""|1 if is heating) 
	[alt_heat] => 
	[fan] => 
	[hot_water] => 
	[auto_away] => -1 
	[manual_away] =>  | 1
	[structure_away] =>   | 1
	[leaf] => 
	[battery_level] => 3.954 
	[active_stages] => stdClass Object 
		[heat] => stdClass Object 
			[stage1] => 1 
			[stage2] => 
			[stage3] => 
			[alt] => 
			[alt_stage2] => 
			[aux] => 
			[emergency] =>
		[cool] => stdClass Object
			[stage1] => 
			[stage2] => 
			[stage3] => 
	[eco_mode] => schedule 
	[eco_temperatures_assist_enabled] => 1 
	[eco_temperatures] => stdClass Object
		[low] => 16.15352 
		[high] => 
[target] => stdClass Object
	[mode] => heat | off
	[temperature] => 21 
	[time_to_target] => 0
[sensors] => stdClass Object
	[all] => Array ( ) 
	[active] => Array ( ) 
	[active_temperatures] => Array ( )
[serial_number] => xxxxxxxxxxxxxxxx 
[scale] => C 
[location] => df915920-c2a3-11e4-9221-22000b4b8cc7 
[network] => stdClass Object
	[online] => 1 
	[last_connection] => 2020-02-15 18:39:32 
	[last_connection_UTC] => 2020-02-15 17:39:32 
	[wan_ip] => /xxx.xxx.xxx.xxx 
	[local_ip] => xxx.xxx.xxx.xxx
	[mac_address] => xxxxxxxxxxxx
[name] => Nest Living 
[auto_cool] => 
[auto_heat] => 19 
[where] => Séjour ) 
*/
echo "<br><b><hr>".$infos->name." - ".$infos->where."</b><hr><br>";
echo "<u>Current setting : </u><br>";
if ($read == NULL or $read == 'temperature') {
	// Current temperature
	echo "<i>Current temperature : </i>".$infos->current_state->temperature."°".$infos->scale."<br>";
	echo "<i>Is heating : </i>";
	if ($infos->current_state->heat == "") {
		echo "No<br>";
	} else {
		echo "Yes<br>";
	}
}
if ($read == NULL or $read == 'humidity') {
	// Current humidity
	echo "<I>Current humidity : </i>".$infos->current_state->humidity."%<br>";
}
if ($read == NULL or $read == 'target') {
	// Target temperature
	echo "<i>Target temperature : </i>".$infos->target->temperature."°".$infos->scale."<br>";
//	echo "<i>Time to target temperature : </i>".$infos->target->time_to_target."<br>";
}
if ($read == NULL or $read == 'eco') {
	// Eco temperature
	echo "<i>Eco temperature : </i>".$infos->current_state->eco_temperatures->low."°".$infos->scale."<br>";
}
if ($read == NULL or $read == 'mode') {
	// Target mode
	echo "<i>Target mode : </i>".$infos->target->mode."<br>";
}
if ($read == NULL or $read == 'away') {
	// Away mode
	echo "<i>Away mode : </i>";
	if ($infos->current_state->manual_away == "") {
		echo "Present<br>";
	} else { 
		echo "Away<br>";
	}
}

echo "<br><u>Possible actions : </u><br>";
// setTmp
echo "<i>Set the target temperature to : </i><br>";
if ($debug) {
	echo "&nbsp&nbsp&nbsp&nbsp&nbsp<a href=http://".$ip.$file."?setTmp=15&debug=1 target='_blank'>15</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=15.5&debug=1 target='_blank'>15.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=16&debug=1 target='_blank'>16</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=16.5&debug=1 target='_blank'>16.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=17&debug=1 target='_blank'>17</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=17.5&debug=1 target='_blank'>17.5</a><br>";
	echo "&nbsp&nbsp&nbsp&nbsp&nbsp<a href=http://".$ip.$file."?setTmp=18&debug=1 target='_blank'>18</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=18.5&debug=1 target='_blank'>18.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=19&debug=1 target='_blank'>19</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=19.5&debug=1 target='_blank'>19.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=20&debug=1 target='_blank'>20</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=20.5&debug=1 target='_blank'>20.5</a><br>";
	echo "&nbsp&nbsp&nbsp&nbsp&nbsp<a href=http://".$ip.$file."?setTmp=21&debug=1 target='_blank'>21</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=21.5&debug=1 target='_blank'>21.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=22&debug=1 target='_blank'>22</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=22.5&debug=1 target='_blank'>22.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=23&debug=1 target='_blank'>23</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=23.5&debug=1 target='_blank'>23.5</a><br>";
	echo "&nbsp&nbsp&nbsp&nbsp&nbsp<a href=http://".$ip.$file."?setTmp=24&debug=1 target='_blank'>24</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=24.5&debug=1 target='_blank'>24.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=25&debug=1 target='_blank'>25</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=25.5&debug=1 target='_blank'>25.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=26&debug=1 target='_blank'>26</a> -  ";
	echo "<a href=http://".$ip.$file."?setTmp=26.5&debug=1 target='_blank'>26.5</a> ";

} else {
	echo "&nbsp&nbsp&nbsp&nbsp&nbsp<a href=http://".$ip.$file."?setTmp=15 target='_blank'>15</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=15.5 target='_blank'>15.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=16 target='_blank'>16</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=16.5 target='_blank'>16.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=17 target='_blank'>17</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=17.5 target='_blank'>17.5</a><br>";
	echo "&nbsp&nbsp&nbsp&nbsp&nbsp<a href=http://".$ip.$file."?setTmp=18 target='_blank'>18</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=18.5 target='_blank'>18.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=19 target='_blank'>19</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=19.5 target='_blank'>19.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=20 target='_blank'>20</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=20.5 target='_blank'>20.5</a><br>";
	echo "&nbsp&nbsp&nbsp&nbsp&nbsp<a href=http://".$ip.$file."?setTmp=21 target='_blank'>21</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=21.5 target='_blank'>21.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=22 target='_blank'>22</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=22.5 target='_blank'>22.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=23 target='_blank'>23</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=23.5 target='_blank'>23.5</a><br>";
	echo "&nbsp&nbsp&nbsp&nbsp&nbsp<a href=http://".$ip.$file."?setTmp=24 target='_blank'>24</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=24.5 target='_blank'>24.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=25 target='_blank'>25</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=25.5 target='_blank'>25.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=26 target='_blank'>26</a> -  ";
	echo "<a href=http://".$ip.$file."?setTmp=26.5 target='_blank'>26.5</a> ";
}
echo "°".$infos->scale."<br>";

// setAway
echo "<i>Set Away mode to : </i>";
if ($infos->current_state->manual_away == "") {
	if ($debug) {
		echo "<a href=http://".$ip.$file."?setAway=On&debug=1 target='_blank'>Away</a>";
	} else {
		echo "<a href=http://".$ip.$file."?setAway=On target='_blank'>Away</a>";
	}
} else {
	if ($debug) {
		echo "<a href=http://".$ip.$file."?setAway=Off&debug=1 target='_blank'>Present</a>";
	} else {
		echo "<a href=http://".$ip.$file."?setAway=Off target='_blank'>Present</a>";
	}
}
echo "<br>";

// other infos
if ($debug) {
	echo "<br><u>Other infos : </u><br>";
	/*
	echo "<i>Device schedule : </i><br>";
	// Returns as array, one element for each day of the week for which there has at least one scheduled event.
	// Array keys are a textual representation of a day, three letters, as returned by `date('D')`. Array values are arrays of scheduled temperatures, including a time (in minutes after midnight), and a mode (one of the TARGET_TEMP_MODE_* defines).
	$schedule = $nest->getDeviceSchedule();
	echo print_r($schedule);
	echo "<br>";
	echo "<br>";

	echo "<i>Device next scheduled event :</i><br>";
	$next_event = $nest->getNextScheduledEvent();
	echo print_r($next_event);
	echo "<br>";
	echo "<br>";

	echo "<i>Last 10 days energy report :</i><br>";
	$energy_report = $nest->getEnergyLatest();
	echo print_r($energy_report);
	echo "<br>";
	echo "<br>";
	*/

	echo "<i>Device schedule : </i><br>";
	// Returns as array, one element for each day of the week for which there has at least one scheduled event.
	// Array keys are a textual representation of a day, three letters, as returned by `date('D')`. Array values are arrays of scheduled temperatures, including a time (in minutes after midnight), and a mode (one of the TARGET_TEMP_MODE_* defines).
	$schedule = $nest->getDeviceSchedule();
	jlog($schedule);
	echo "<br>";
	echo "<br>";

	echo "<i>Device next scheduled event :</i><br>";
	$next_event = $nest->getNextScheduledEvent();
	jlog($next_event);
	echo "<br>";
	echo "<br>";

	echo "<i>Last 10 days energy report :</i><br>";
	$energy_report = $nest->getEnergyLatest();
	jlog($energy_report);
	echo "<br>";
	echo "<br>";
}

function jlog($json) {
    echo json_encode($json, JSON_PRETTY_PRINT) . "\n";
}
?>