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
		- target temperature reached at
		- eco temperature
		- target mode
		- heating Yes/No
		- away mode
	- set  the mahor settings
		- target emperature
		- eco temperature
		- away mode

This development is based on the PHP class developped by gboudreau :
https://github.com/gboudreau/nest-api

Version history :
-----------------
v01.1 by Jojo 		(15/02/2020)	: initial version
v01.2 by Jojo 		(17/02/2020)	: heating info
v01.3 by Jojo		(20/02/2020)	: optimisation of .ini file upload
v01.4 by Jojo		(24/02/2020)	: display temperatures with one digit
									  auto-refresh the page
									  target temperature reached at
v02.1 by Jojo		(07/03/2020)	: send read info to domotic box
									  remove useless read parameter
									  cancel refresh if action
									  code optimisation
v02.2 by sud-domotique-expert		: send target mode to box
v02.3 by Jojo		(13/03/2020)	: isEco / leaf ?
v02.4 by sud-domotique-expert		: send Nest name & where to box
v02.5 by Jojo		(14/03/2020)	: autorefresh box if setTmp or setAway
v03.1 by Jojo		(15/03/2020)	: set Eco temperature
									  display Safety/Emergency/anti-freeze status
									  display Safety/Emergency/anti-freeze temperature
									  set Safety/Emergency/anti-freeze temperature
v03.2 by Jojo		(16/03/2020)	: to comply <nest.class.php> changes
v03.3 by Jojo		(22/03/2020)	: add setTmp, ecoTmp & safetyTmp to refresh()
v03.4 by Jojo		(29/03/2020)	: enforce decimal point for temprature sets
v03.5 by Jojo		(29/03/2020)	: correct bug in refreshBox()
v03.6 by Jojo		(13/04/2020)	: catch token error

Syntax :
--------
http://xxxxxxx/Nest.php
	if no parameter 				- read all possible values
	?setTmp=xx.x 					- set target temperature to xx.x °C
	?setAway=On|Off 				- set Away mode On or Off
	?setEco=xx.x 					- set Eco temperature to xx.x °C
	?setSafety=xx.x 				- set Safety/Emergency/anti-freeze temperature to xx.x °C
	?debug=1 						- display debug mode

Initial setup :
---------------
Install this .php, together with the .ini and the nest.class.php, in the same sub-directory of your web folder of your web server
The name of the .ini file must be the same as the one of this .php file.
Look into the .ini file how to enter your credentials
*/
$CodeVersion = "v03.6";

// INITIALISATION
// ---------------

// from .ini file (.ini file mut have the same name as the running script)
$ini_array = parse_ini_file(substr(basename($_SERVER['SCRIPT_NAME']).PHP_EOL, 0, -4)."ini");
$issue_token = $ini_array['issue_token'];
$cookies = $ini_array['cookies'];
$refresh = $ini_array['refresh'];
$Box_IP = $ini_array['Box_IP'];
$Box_Port = $ini_array['Box_Port'];
$Box_Protocole = $ini_array['Box_Protocole'];
$Box_Cmd = $ini_array['Box_Cmd'];
$Box_url = $Box_Protocole."://".$Box_IP.":".$Box_Port."/".$Box_Cmd."?";

// auto configuration
$ip = $_SERVER['SERVER_ADDR']; 					// IP-Adress of your Web server hosting this script
$file = $_SERVER['PHP_SELF'];  					// path & file name of this running php script
//$dirname = pathinfo($file, PATHINFO_DIRNAME);	// relative path
//	if ($dirname == "/") {$dirname = "";}
//$dirnamefull = getcwd();						// full path : expl /volume1/web/...

// URL parameters
$setTmp = $_GET['setTmp'];
$setEco = $_GET['setEco'];
$setSafety = $_GET['setSafety'];
$setAway = $_GET['setAway'];
// if parameter specified, use the specified one
if ($_GET["debug"] != NULL) {$debug = 1;}
if ($_GET["refresh"] != NULL) {$refresh = $_GET["refresh"];}

// ActionUrl
$ActionUrl = "&refresh=999";
if ($debug) {
	$ActionUrl = $ActionUrl."&debug=1";
}

// load specific PHP class
require_once('nest.class.php');
// initialize Nest API & check if access ok
try {
    $nest = new Nest(NULL, NULL, $issue_token, $cookies);
    // Execute all Nest-related code here
} catch (UnexpectedValueException $ex) {
    // Happens when the issue_token or cookie is not working, for whatever reason
    $error_message = $ex->getMessage();
	echo "<hr>error tokens : ".$error_message."<hr><br>";
	if ($Box_IP) {		// transfert to domotic box
		$http = $Box_url."currentTmp=Tokens";
		curl ($http);
		exit();
	}
} catch (RuntimeException $ex) {
    // Probably a temporary server-error
    echo "RuntimeException : ".$ex;
    if ($Box_IP) {		// transfert to domotic box
		$http = $Box_url."currentTmp=RunTime";
		curl ($http);
		exit();
	}
} catch (Exception $ex) {
    // Other errors; should not happen if it worked in the past
    echo "Other error : ".$ex;
    if ($Box_IP) {		// transfert to domotic box
		$http = $Box_url."currentTmp=Exception";
		curl ($http);
		exit();
	}
}

// Debug Alert
if ($debug) {
	echo "<hr>DEBUG ENABLED<br>!!!!do not use debug parameter WHEN CODE IS IN PRODUCTION !!!!<hr>";
	echo "read = -".$read."-<br>";
	echo "setTmp = -".$setTmp."-<br>";
	echo "setAway = -".$setAway."-<br>";
	echo "<hr>";
	echo "App Version : ".$CodeVersion."<br>";
	echo "PHP Version : ".phpversion()."<br>";
	echo "<hr>";
	if ($Box_IP) {
		echo "Domotic box info : <br>";
		echo "Box_IP = ".$Box_IP."<br>";
		echo "Box_Port = ".$Box_Port."<br>";
		echo "Box_Protocole = ".$Box_Protocole."<br>";
		echo "Box_Cmd = ".$Box_Cmd."<br>";
		echo "Box_url = ".$Box_url."<br>";
		echo "<hr>";
	}
}
// --------------------------------------------------------------------------

// actions
if ($setTmp != NULL) {
	$setTmp = str_replace (",", ".", $setTmp);
	$success = $nest->setTargetTemperature((float) $setTmp);
	if ($debug) {
		// Get the device information:
		$infos = $nest->getDeviceInfo();
		echo "setTmp to ".$setTmp."°".$infos->scale." - success : ".$success."<br>";
	}
	refreshBox($nest, $Box_url);
}
if ($setEco != NULL) {
	$setEco = str_replace (",", ".", $setEco);
	$success = $nest->setEcoTemperatures((float) $setEco, 0);
	if ($debug) {
		// Get the device information:
		$infos = $nest->getDeviceInfo();
		echo "setEco to ".$setEco."°".$infos->scale." - success : ".$success."<br>";
	}
	refreshBox($nest, $Box_url);
}
if ($setSafety != NULL) {
	$setSafety = str_replace (",", ".", $setSafety);
	$success = $nest->setSafetyTemperatures((float) $setSafety, 0);
	if ($debug) {
		// Get the device information:
		$infos = $nest->getDeviceInfo();
		echo "setEco to ".$setEco."°".$infos->scale." - success : ".$success."<br>";
	}
	refreshBox($nest, $Box_url);
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
	refreshBox($nest, $Box_url);
}
// Get the device information:
$infos = $nest->getDeviceInfo();
// display $infos raw content for dev purpose
if ($debug) {
	print_r($infos);
	echo "<br>";
	echo "<br>";
	var_dump($infos);
	echo "<br>";
}

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
	[leaf] =>   | 1 (""|1 if eco leaf)
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
	[safety_temperatures] => stdClass Object
		[low] => 4.6118565
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
if ($refresh <= 998) {
	echo("<meta http-equiv='refresh' content='".$refresh."'>"); //Refresh by HTTP META
}
echo "<br><hr>".$infos->name." - ".$infos->where."</b><hr><br>";
	if ($Box_IP) {        // transfert to domotic box
        $http = $Box_url."name=".$infos->name;
        curl ($http);
        $http = $Box_url."where=".$infos->where;
        curl ($http);
    }
  
// echo "emergencyTemperature : ".$infos->current_state->emergencyTemperature."-<br>";

echo "<u>Current setting : </u><br>";
	// Current temperature
	echo "<i>Current temperature : </i>".number_format($infos->current_state->temperature,1)."°".$infos->scale."<br>";
	if ($Box_IP) {		// transfert to domotic box
		$http = $Box_url."currentTmp=".number_format($infos->current_state->temperature,1);
		curl ($http);
	}
	// Is Heating ?
	echo "<i>Is heating : </i>";
	if ($infos->current_state->heat == "") {
		echo "No<br>";
	} else {
		echo "Yes<br>";
	}
	if ($Box_IP) {		// transfert to domotic box
		$http = $Box_url."isHeating=".$infos->current_state->heat;
		curl ($http);
	}
	// Current humidity
	echo "<I>Current humidity : </i>".$infos->current_state->humidity."%<br>";
	if ($Box_IP) {		// transfert to domotic box
		$http = $Box_url."currentHum=".$infos->current_state->humidity;
		curl ($http);
	}

	// Target temperature
	echo "<br>";
	echo "<i>Target temperature : </i>".number_format($infos->target->temperature,1)."°".$infos->scale."<br>";
	if ($Box_IP) {		// transfert to domotic box
		$http = $Box_url."setTmp=".number_format($infos->target->temperature,1);
		curl ($http);
	}
	// Time & duration to target
	$date_target = "";
	if ($infos->current_state->heat == 1) {
		if ($infos->target->time_to_target == 0) {
			$date_target = "Unknown";
			$duration_target = "Unknown";
		} else {
			$date = date_create();
			date_timestamp_set ($date, $infos->target->time_to_target);
			$date_target = date_format ($date, 'H:i');

			$durationUnix = ($infos->target->time_to_target - time());
			$duration = date_create();
			date_timestamp_set ($duration, ($infos->target->time_to_target - time()));
			$duration_target = date_format ($duration, 'H:i');
		}
		echo "<i>Target temperature reached at : </i>".$date_target."<br>";
		echo "<i>Duration to target temperature : </i>".$duration_target."<br>";
	} 
	if ($Box_IP) {		// transfert to domotic box
		$http = $Box_url."hourTarget=".$date_target;
		curl ($http);
		$http = $Box_url."durationTarget=".$duration_target;
		curl ($http);
    }    

	// Target mode
	echo "<i>Target mode : </i>".$infos->target->mode."<br>";
 	if ($Box_IP) {        // transfert to domotic box
        $http = $Box_url."targetMode=".$infos->target->mode;
        curl ($http);
    }

	// Away mode
	echo "<br>";
	echo "<i>Away mode : </i>";
	if ($infos->current_state->manual_away == "") {
		echo "Present<br>";
	} else { 
		echo "Away<br>";
	}
	if ($Box_IP) {		// transfert to domotic box
		$http = $Box_url."setAway=".$infos->current_state->manual_away;
		curl ($http);
	}
     
	// Eco temperature
	// possible values in app : 5 to 21 °C
	echo "<br>";
	echo "<i>Eco temperature : </i>".number_format($infos->current_state->eco_temperatures->low,1)."°".$infos->scale."<br>";
	if ($Box_IP) {		// transfert to domotic box
		$http = $Box_url."ecoTmp=".number_format($infos->current_state->eco_temperatures->low,1);
		curl ($http);
	}
 
 	// Eco mode / leaf
	echo "<i>Is Eco : </i>";
	if ($infos->current_state->leaf == "") {
		echo "No<br>";
	} else {
		echo "Yes<br>";
	}
	if ($Box_IP) {		// transfert to domotic box
		$http = $Box_url."isEco=".$infos->current_state->leaf;
		curl ($http);
	}

	// Safety temperature
	// possible values in app : 2 to 7 °C
	echo "<br>";
	echo "<i>Safety temperature : </i>".number_format($infos->current_state->safety_temperatures->low,1)."°".$infos->scale."<br>";
	if ($Box_IP) {		// transfert to domotic box
		$http = $Box_url."safetyTmp=".number_format($infos->current_state->safety_temperatures->low,1);
		curl ($http);
	}
 	// Safety mode
	echo "<i>Is Safety : </i>";
	if ($infos->current_state->active_stages->emergency == "") {
		echo "No<br>";
	} else {
		echo "Yes<br>";
	}
	if ($Box_IP) {		// transfert to domotic box
		$http = $Box_url."isSafety=".$infos->current_state->active_stages->emergency ;
		curl ($http);
	}


echo "<br><u>Possible actions : </u><br>";
	// setAway
	echo "<i>Set Away mode to : </i>";
	if ($infos->current_state->manual_away == "") {
		echo "<a href=http://".$ip.$file."?setAway=On".$ActionUrl." target='_blank'>Away</a>";
	} else {
		echo "<a href=http://".$ip.$file."?setAway=Off".$ActionUrl." target='_blank'>Present</a>";
	}
	echo "<br>";

	// setTmp
	echo "<i>Set the target temperature to : </i><br>";
	echo "&nbsp&nbsp&nbsp&nbsp&nbsp";
	echo "<a href=http://".$ip.$file."?setTmp=15".$ActionUrl." target='_blank'>15</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=15.5".$ActionUrl." target='_blank'>15.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=16".$ActionUrl." target='_blank'>16</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=16.5".$ActionUrl." target='_blank'>16.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=17".$ActionUrl." target='_blank'>17</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=17.5".$ActionUrl." target='_blank'>17.5</a><br>";
	echo "&nbsp&nbsp&nbsp&nbsp&nbsp";
	echo "<a href=http://".$ip.$file."?setTmp=18".$ActionUrl." target='_blank'>18</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=18.5".$ActionUrl." target='_blank'>18.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=19".$ActionUrl." target='_blank'>19</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=19.5".$ActionUrl." target='_blank'>19.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=20".$ActionUrl." target='_blank'>20</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=20.5".$ActionUrl." target='_blank'>20.5</a><br>";
	echo "&nbsp&nbsp&nbsp&nbsp&nbsp";
	echo "<a href=http://".$ip.$file."?setTmp=21".$ActionUrl." target='_blank'>21</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=21.5".$ActionUrl." target='_blank'>21.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=22".$ActionUrl." target='_blank'>22</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=22.5".$ActionUrl." target='_blank'>22.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=23".$ActionUrl." target='_blank'>23</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=23.5".$ActionUrl." target='_blank'>23.5</a><br>";
	echo "&nbsp&nbsp&nbsp&nbsp&nbsp";
	echo "<a href=http://".$ip.$file."?setTmp=24".$ActionUrl." target='_blank'>24</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=24.5".$ActionUrl." target='_blank'>24.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=25".$ActionUrl." target='_blank'>25</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=25.5".$ActionUrl." target='_blank'>25.5</a> - ";
	echo "<a href=http://".$ip.$file."?setTmp=26".$ActionUrl." target='_blank'>26</a> -  ";
	echo "<a href=http://".$ip.$file."?setTmp=26.5".$ActionUrl." target='_blank'>26.5</a> ";
	echo "°".$infos->scale."<br>";

	// setEco
	echo "<i>Set the Eco temperature to : </i><br>";
	echo "&nbsp&nbsp&nbsp&nbsp&nbsp";
	echo "<a href=http://".$ip.$file."?setEco=5".$ActionUrl." target='_blank'>5</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=5.5".$ActionUrl." target='_blank'>5.5</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=6".$ActionUrl." target='_blank'>6</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=6.5".$ActionUrl." target='_blank'>6.5</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=7".$ActionUrl." target='_blank'>7</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=7.5".$ActionUrl." target='_blank'>7.5</a><br>";
	echo "&nbsp&nbsp&nbsp&nbsp&nbsp";
	echo "<a href=http://".$ip.$file."?setEco=8".$ActionUrl." target='_blank'>8</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=8.5".$ActionUrl." target='_blank'>8.5</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=9".$ActionUrl." target='_blank'>9</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=9.5".$ActionUrl." target='_blank'>9.5</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=10".$ActionUrl." target='_blank'>10</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=10.5".$ActionUrl." target='_blank'>10.5</a><br>";
	echo "&nbsp&nbsp&nbsp&nbsp&nbsp";
	echo "<a href=http://".$ip.$file."?setEco=11".$ActionUrl." target='_blank'>11</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=11.5".$ActionUrl." target='_blank'>11.5</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=12".$ActionUrl." target='_blank'>12</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=12.5".$ActionUrl." target='_blank'>12.5</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=13".$ActionUrl." target='_blank'>13</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=13.5".$ActionUrl." target='_blank'>13.5</a><br>";
	echo "&nbsp&nbsp&nbsp&nbsp&nbsp";
	echo "<a href=http://".$ip.$file."?setEco=14".$ActionUrl." target='_blank'>14</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=14.5".$ActionUrl." target='_blank'>14.5</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=15".$ActionUrl." target='_blank'>15</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=15.5".$ActionUrl." target='_blank'>15.5</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=16".$ActionUrl." target='_blank'>16</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=16.5".$ActionUrl." target='_blank'>16.5</a><br>";
	echo "&nbsp&nbsp&nbsp&nbsp&nbsp";
	echo "<a href=http://".$ip.$file."?setEco=17".$ActionUrl." target='_blank'>17</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=17.5".$ActionUrl." target='_blank'>17.5</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=18".$ActionUrl." target='_blank'>18</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=18.5".$ActionUrl." target='_blank'>18.5</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=19".$ActionUrl." target='_blank'>19</a> - ";
	echo "<a href=http://".$ip.$file."?setEco=19.5".$ActionUrl." target='_blank'>19.5</a> ";
	echo "°".$infos->scale."<br>";

	// setSafety
	echo "<i>Set the Safety temperature to : </i><br>";
	echo "&nbsp&nbsp&nbsp&nbsp&nbsp";
	echo "<a href=http://".$ip.$file."?setSafety=2".$ActionUrl." target='_blank'>2</a> - ";
	echo "<a href=http://".$ip.$file."?setSafety=2.5".$ActionUrl." target='_blank'>2.5</a> - ";
	echo "<a href=http://".$ip.$file."?setSafety=3".$ActionUrl." target='_blank'>3</a> - ";
	echo "<a href=http://".$ip.$file."?setSafety=3.5".$ActionUrl." target='_blank'>3.5</a> - ";
	echo "<a href=http://".$ip.$file."?setSafety=4".$ActionUrl." target='_blank'>4</a> - ";
	echo "<a href=http://".$ip.$file."?setSafety=4.55".$ActionUrl." target='_blank'>4.5</a><br>";
	echo "&nbsp&nbsp&nbsp&nbsp&nbsp";
	echo "<a href=http://".$ip.$file."?setSafety=5".$ActionUrl." target='_blank'>5</a> - ";
	echo "<a href=http://".$ip.$file."?setSafety=5.5".$ActionUrl." target='_blank'>5.5</a> - ";
	echo "<a href=http://".$ip.$file."?setSafety=6".$ActionUrl." target='_blank'>6</a> - ";
	echo "<a href=http://".$ip.$file."?setSafety=6.5".$ActionUrl." target='_blank'>6.5</a> - ";
	echo "<a href=http://".$ip.$file."?setSafety=7".$ActionUrl." target='_blank'>7</a> ";
	echo "&nbsp&nbsp&nbsp&nbsp&nbsp";
	echo "&nbsp&nbsp&nbsp&nbsp&nbsp";
	echo "&nbsp&nbsp&nbsp&nbsp&nbsp";
	echo "&nbsp&nbsp&nbsp&nbsp&nbsp";
	echo "°".$infos->scale."<br>";

// other infos
if ($debug) {
	echo "<br><u>Other infos : </u><br>";

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
function curl($http) {
	$url = curl_init();
	curl_setopt ($url, CURLOPT_URL, $http);
	curl_setopt ($url, CURLOPT_HEADER, 0);
	curl_exec($url);
	curl_close($url);
	if ($debug) {echo $http."<br>";}
}
// function refreshBpx($nest, $Box_url) {
function refreshBox($nest, $Box_url) {
	echo "<br>start function refreshBox<br>";
	if ($Box_url) {		// refresh if box
		echo "Box_url : ".$Box_url."<br>";
		sleep (5);      // in sec
		$infos = $nest->getDeviceInfo();
		$http = $Box_url."isHeating=".$infos->current_state->heat;
		curl ($http);
		$http = $Box_url."isEco=".$infos->current_state->leaf;
		curl ($http);
		$http = $Box_url."setTmp=".number_format($infos->target->temperature,1);
		curl ($http);
		$http = $Box_url."ecoTmp=".number_format($infos->current_state->eco_temperatures->low,1);
		curl ($http);
		$http = $Box_url."safetyTmp=".number_format($infos->current_state->safety_temperatures->low,1);
		curl ($http);
		// Time & duration to target
			$date_target = "";
			if ($infos->current_state->heat == 1) {
				if ($infos->target->time_to_target == 0) {
					$date_target = "Unknown";
					$duration_target = "Unknown";
				} else {
					$date = date_create();
					date_timestamp_set ($date, $infos->target->time_to_target);
					$date_target = date_format ($date, 'H:i');

					$durationUnix = ($infos->target->time_to_target - time());
					$duration = date_create();
					date_timestamp_set ($duration, ($infos->target->time_to_target - time()));
					$duration_target = date_format ($duration, 'H:i');
				}
				$http = $Box_url."hourTarget=".$date_target;
				curl ($http);
				$http = $Box_url."durationTarget=".$duration_target;
				curl ($http);
		    } 
	}
	exit();
}
?>