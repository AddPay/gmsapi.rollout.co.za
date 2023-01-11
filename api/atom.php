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

$session_key = cds_GetPost('sk','');	
$syncaction = cds_GetPost('action','geteditusers');		
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

		// get the user that must be enrolled

		$response = [];

		$sql = "SELECT ip_address, id, pPersonNumber FROM enrollment_requests WHERE actioned = 0 GROUP BY ip_address ORDER BY id"; 
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

	} else if ($syncaction == 'isenrolled') {
		$sql = "SELECT actioned FROM enrollment_requests WHERE id = $ids";
		$actioned = $cnx->QuerySingleValue($sql);

		if ($actioned === '1') {
			$response = [
				"status" => "success",
				"message" => "is enrolled",
				"data" => [],
			];
		} else if ($actioned === '0') {
			$response = [
				"status" => "success",
				"message" => "not enrolled",
				"data" => [],
			];
		} else {
			$response = [
				"status" => "fail",
				"message" => "Could not determine enrollment state of id=$ids",
				"data" => [],
			];
		}

		$json = json_encode($response);
		echo $json;

		DoLogFile('ISENROLLED,'.$json);
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

		$json = json_encode($response);
		echo $json;
				
		DoLogFile('SETACTIONED,'.$json);

	}
}	

$cnx->Query('update system_settings set last_gymsync = now() ');