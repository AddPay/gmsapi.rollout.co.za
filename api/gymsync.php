<?php

/*===============================================================*/
//------------   API to sync atom and gms databases   ------------
//-----          Paul Bailey May 2015                        -----
//-----                                                      -----
//----------------------------------------------------------------
/*===============================================================*/

require_once('config.gymsync.php');
require_once('api.common.php');
require_once('cdslib/cdsutils.php');
require_once('cdslib/cds.mysqli.class.php');

cds_LogFile('synctable', 'script is running');
define('SESSIONKEY','56HJ7UI927DFPT12');

/**
 * @param MySQL $cnx
 * @param string $data A JSON object containing all the data to update/insert
 * @param string $wherecolumns A comma separated list of columns that form part of the where statement
 * 
 * @return string success/error message
 */
function SyncTable($cnx, $data, $wherecolumns) {

	if (is_string($data) && strlen($data)>0) {
		try {
			$data = json_decode($data, true);
		} catch (\Throwable $th) { }
	}
	
	if (is_array($data)) {
		
		foreach ($data as $tablename=>$rows) {
			
			foreach ($rows as $row) {
				foreach ($row as $key=>$value) {
					$row[$key] = AddQuotes($value);
					
				}
				$datarows[] = $row;
				
			}
			
			$result = $cnx->AutoInsertOrUpdateAllRecords($tablename, $datarows, $wherecolumns);
			
			if ($cnx->Error()) {
				echo $cnx->Error();		
				cds_LogFile('syncerror', $cnx->Error());			
			}			
			
		}

	
		$updatedok = true;
		$error = '';
	
		
		if ($updatedok) {		
       		$updatedok = 'Ok';
		} else {
			$updatedok = $error;			
		}
	} else {
		$updatedok = 'No Data';
	}	

	return $updatedok;			
	
}

/**
 * @param MySQL $cnx
 * 
 * @return string CSV
 */
function SendUserData($cnx) {

	// get latest
	$result = file_get_contents('https://matiesgym.rollout.co.za/api/cron_synccontracts.php');		
 	
	$sql = "select * from v_getuserupdates where v_getuserupdates.update = 1 "; 
	//$sql = 'select * from v_getuserupdates';	
	
	$rows = $cnx->Query($sql);
	//$result =  cds_sql2xml($host, $username, $password, $database, $sql);
	
	$result = $cnx->GetCSV();
	
	return $result;			
	
}

/**
 * @param MySQL $cnx
 * @param string $ids IDs to set to updated
 * 
 * @return string success/error message
 */
function UpdateUserData($cnx,$ids) {
	
	$sql = 'update suspi_users set `update` = 0 where id in ('.$ids.')';
	$updatedok = $cnx->Query($sql);
	
	if ($updatedok) {		
       		$updatedok = 'Ok';
		} else {
			$updatedok = 'No';
	}	
	
	return $updatedok;				
	
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
		
$syncaction = cds_GetPost('sa','down');		
$data = cds_GetPost('da','');
$ids = cds_GetPost('ids','0');
$wherecolumns = cds_GetPost('wc','');

// $syncaction = 'up';
// $ids = '2,3';

// $wherecolumns = 'TransactionID';
// $data = '
// {  "Transactions": [
//     {
//       "TransactionID": "1",
//       "tDateTime": "2015-11-02 11:16:50",
//       "PersonID": "1",
//       "ReaderID": "1",
//       "tDirection": "OUT",
//       "tReaderDescription": "Maties Gym Entrance",
//       "tManual": "0",
//       "tDeleted": "0",
//       "tTAProcessed": "0",
//       "TimesheetDayID": "0",
//       "tExtProcessed": "0",
//       "tLogical": "0"
//     },
//     {
//       "TransactionID": "2",
//       "tDateTime": "2015-11-02 11:17:59",
//       "PersonID": "1",
//       "ReaderID": "1",
//       "tDirection": "IN        ",
//       "tReaderDescription": "Maties Gym Entrance",
//       "tManual": "0",
//       "tDeleted": "0",
//       "tTAProcessed": "0",
//       "TimesheetDayID": "0",
//       "tExtProcessed": "0",
//       "tLogical": "0"
//     },  
//     {
//       "TransactionID": "28",
//       "tDateTime": "2015-11-03 03:39:17",
//       "PersonID": "7",
//       "ReaderID": "1",
//       "tDirection": "IN        ",
//       "tReaderDescription": "Maties Gym Entrance",
//       "tManual": "0",
//       "tDeleted": "0",
//       "tTAProcessed": "0",
//       "TimesheetDayID": "0",
//       "tExtProcessed": "0",
//       "tLogical": "0"
//     }
//   ]}';
// cds_LogFile('synctable', $syncaction . $wherecolumns . print_r($data, true));	
	
$cnx = new MySQL(true, $database, $host, $username, $password);
if ($cnx->Error()) {
	EchoJson('result',$cnx->Error());
} else {

	if ($syncaction == 'down') {

		// Get all updated GMS users
		$result = SendUserData($cnx);
		echo $result;
		if (strlen($result) > 10) {
			cds_SaveFile(date('Ymd_His').'_userdata.txt', $result);	
		}	

	} else if ($syncaction == 'update') {

		// Set GMS users specified by $ids as being updated on ATOM
		$escaped_ids = escapeStringList($cnx, $ids);
		$result = UpdateUserData($cnx, $escaped_ids);		
		cds_SaveFile(date('Ymd_His').'_update.txt', $ids);			
		EchoJson('result',$result);	

	} else {

		// Update GMS with data from ATOM.
		$result = SyncTable($cnx, $data, $wherecolumns);
		EchoJson('result',$result);
		if (($wherecolumns != 'ReaderID') && ($wherecolumns != 'TransactionID')) {
			cds_LogFile('synctable', 'WHERE:'.$wherecolumns.' Data:'.$data);	
		}

	}
	
}