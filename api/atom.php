<?php

/*===============================================================*/
//------------ API to handle fingerprint enrollment   ------------
//-----          Paul Bailey May 2015                        -----
//-----                                                      -----
//----------------------------------------------------------------
/*===============================================================*/

require_once('config.gymsync.php');
require_once('api.common.php');
require_once('cdslib/cdsutils.php');
require_once('cdslib/cds.mysqli.class.php');

define('SESSIONKEY','56HJ7UI927DFPT12');
define('DEBUG', true);

/**
 * @param string $message Log message
 * 
 * @return void
 */
function DoLogFile($message) {
	
	$time = date('Y-m-d H:i:s').','.$message.PHP_EOL;
	cds_AppendFile('logs/'.date('Y-m-d').'_atom.txt', $time); 		

}

/**
 * @param MySQL $cnx
 * @param string $str Comma-separated list
 * 
 * @return string Escaped comma-separated list
 */
function escapeStringList($cnx, $str)
{
	$unescaped_arr = explode(',', $str);

	$escaped_arr = [];

	foreach ($unescaped_arr as $item) {
		$escaped_arr[] = $cnx->quote($item, true);
	}

	$escaped = implode(',', $escaped_arr);

	return $escaped;
}

/*============================================================*/

/**
 * A small security measure.
 * If the request sends the correct sk, the request will be processed
 */
$session_key = cds_GetPost('sk','');

/**
 * Can be:
 * - getenrollmentrequests: get enrollment requests (one per serving PC)
 * - isactioned: check whether an enrollment request has been process
 * - updateusers: Set users on GMS as having been successfully updated on ATOM
 * - resenduser: Set users needing to be updated on ATOM (retrieve those users via geteditusers)
 * - geteditusers: get users needing to be updated on ATOM
 * - setactioned: set an enrollment request as being actioned
 */
$syncaction = cds_GetPost('action','geteditusers');

/**
 * This is a string of comma-separated ids to apply the "syncaction" to
 * Not applicable to all sync actions
 */
$ids_string = cds_GetPost('actiontype','');

if ($session_key !== SESSIONKEY && !DEBUG) {
	die;
}

$cnx = new MySQL(true, $database, $host, $username, $password);

if ($cnx->Error()) {
	$response = [
		"status" => "fail",
		"message" => $cnx->Error(),
		"data" => [],
	];
	echo json_encode($response);
} else {

	/**
	 * Be very careful with values that are passed to the script!
	 * Escape them to make sure it is safe to use in queries.
	 */
	$ids = escapeStringList($cnx, $ids_string);

	if ($syncaction == 'getenrollmentrequests') {

		/**
		 * This returns non-actioned enrollment requests
		 * One request per requesting IP.
		 * GymSync uses the IP and PersonNumber to trigger the enrollment
		 * for the correct person on the correct PC
		 * 
		 * Only computers with the ATOM enrollment helper will be able to enroll users.
		 * Currently Maties has 2 front desk computers in Stellies and a Tygerberg computer
		 * that can do enrollments.
		 * 
		 * Server PC: 	146.232.34.44
		 * PC2:			146.232.33.87
		 * Tyg:			146.232.173.73
		 */

		$response = [];

		$sql = "SELECT id, pPersonNumber, ip_address FROM enrollment_requests WHERE actioned = 0 GROUP BY ip_address ORDER BY id"; 
		$requests = $cnx->QueryArray($sql);

		if ($requests) {

			$response = [
				"status" => "success",
				"message" => "enrollment requests found",
				"data" => $requests,
			];

		} else {

			$response = [
				"status" => "success",
				"message" => "no enrollments found",
				"data" => [],
			];

		}
		
		$json = json_encode($response);

		echo $json;

		DoLogFile('GETENROLLREQ,'.$json);

	} else if ($syncaction == 'isactioned') {
		/**
		 * Checks to see whether the a specific enrollment request
		 * has been actioned.
		 */
		$sql = "SELECT actioned FROM enrollment_requests WHERE id = $ids";
		$actioned = $cnx->QuerySingleValue($sql);

		if ($actioned === '1') {
			$response = [
				"status" => "success",
				"message" => "actioned",
				"data" => [],
			];
		} else if ($actioned === '0') {
			$response = [
				"status" => "success",
				"message" => "not actioned",
				"data" => [],
			];
		} else {
			$response = [
				"status" => "fail",
				"message" => "Could not determine actioned state of id=$ids",
				"data" => [],
			];
		}

		$json = json_encode($response);
		echo $json;

		DoLogFile('ISACTIONED,'.$json);
	} else if ($syncaction == 'updateusers') {

		// Set users as having been successfully updated on ATOM
		$sql = "update suspi_users set useredited = 0 where id in (".$ids.")"; 
		$addedusers = $cnx->Query($sql);
		DoLogFile('CLEARUSEREDIT,'.$ids_string);

	} else if ($syncaction == 'resenduser') {

		// Set users needing to be updated on ATOM (retrieve those users via geteditusers)		
		$sql = "update suspi_users set useredited = 1 where PersonID in (".$ids.")"; 
		$addedusers = $cnx->Query($sql);
		DoLogFile('RESENDUSER,'.$ids_string);

	} else if ($syncaction == 'geteditusers') {

		// Get the users needing to be updated on ATOM		
		$sql = "select * from v_atomusers where useredited = 1 limit 1"; 
		$addedusers = $cnx->QueryArray($sql);

		$json =  $cnx->GetJSON();
		DoLogFile("USER,".$json);
		
		echo $json;
		
		$json = cds_SearchAndReplace($json, "\\", '', false);
		DoLogFile("JSON,".$json);	

	} else if ($syncaction == 'setactioned') {

		$sql = "SELECT COUNT(id) FROM enrollment_requests WHERE id = $ids";

		$count = $cnx->QuerySingleValue($sql);
		$response = [];

		if (intval($count) > 0) {
			/**
			 * Set a specific enrollment request as actioned
			 */
			$values_array = [
				"actioned" => 1,
			];

			$where_array = [
				"id" => $ids,
			];

			$result = $cnx->UpdateRows('enrollment_requests', $values_array, $where_array);

			$response = [
				"status" => $result ? 'success' : 'fail',
				"message" => $cnx->Error(),
				"data" => [],
			];
		} else {
			$response = [
				"status" => 'fail',
				"message" => "Enrollment record not found.",
				"data" => [],
			];
		}

		$json = json_encode($response);
		echo $json;
				
		DoLogFile('SETACTIONED,'.$json);

	}
}	

$cnx->Query('update system_settings set last_gymsync = now() ');