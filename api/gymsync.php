<?php

/*===============================================================*/
//------------ API to add position data to database   ------------
//-----          Paul Bailey May 2015                        -----
//-----                                                      -----
//----------------------------------------------------------------
/*===============================================================*/

require_once('config.gymsync.php');
require_once('api.common.php');
require_once('cdslib/cdsutils.php');
require_once('cdslib/cds.mysqli.class.php');


define('SESSIONKEY','56HJ7UI927DFPT12');


function SyncTable($cnx, $data, $wherecolumns) {
	
	if (strlen($data)>0) {
		
		$data = json_decode($data, true);
		
		foreach ($data as $tablename=>$rows) {
			
			
			foreach ($rows as $row) {
				foreach ($row as $key=>$value) {
					$row[$key] = AddQuotes($value);
					
				}
				$datarows[] = $row;
				
			}
			
			$result = $cnx->AutoInsertOrUpdateAllRecords($tablename, $datarows, $wherecolumns);
			
			if ($result) {				
			  //cds_LogFile('sync', $tablename.' r='.$result.' '.print_r($data,true));			
			} else {
				echo $cnx->Error();		
				cds_LogFile('syncerror', 'WHERE:'.$wherecolumns.' Data:'.$data);			
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


function SendUserData($cnx, $database, $host, $username, $password) {

	// get latest
	$result = file_get_contents('https://matiesgym.rollout.co.za/api/cron_synccontracts.php');		
 	
	$sql = "select * from v_getuserupdates where v_getuserupdates.update = 1 "; 
	//$sql = 'select * from v_getuserupdates';	
	
	$rows = $cnx->Query($sql);
	//$result =  cds_sql2xml($host, $username, $password, $database, $sql);
	
	$result = $cnx->GetCSV(true);
	
	return $result;			
	
}

function UpdateUserData($notificationsdb,$ids) {
	
	$sql = 'update suspi_users set `update` = 0 where id in ('.$ids.')';
	$updatedok = $notificationsdb->Query($sql);
	
	if ($updatedok) {		
       		$updatedok = 'Ok';
		} else {
			$updatedok = 'No';
	}	
	
	return $updatedok;				
	
}



/*============================================================*/

$_GET['sk'] = '56HJ7UI927DFPT12';


$sessionkey = GetPost('sk','not found dude');		
$syncaction = GetPost('sa','down');		
$data = GetPost('da','');
$ids = GetPost('ids','0');
$wherecolumns = GetPost('wc','');


//$syncaction = 'update';

//$wherecolumns = 'TransactionID';
/*$data = '
{  "Transactions": [
    {
      "TransactionID": "1",
      "tDateTime": "2015-11-02 11:16:50",
      "PersonID": "1",
      "ReaderID": "1",
      "tDirection": "OUT",
      "tReaderDescription": "Maties Gym Entrance",
      "tManual": "0",
      "tDeleted": "0",
      "tTAProcessed": "0",
      "TimesheetDayID": "0",
      "tExtProcessed": "0",
      "tLogical": "0"
    },
    {
      "TransactionID": "2",
      "tDateTime": "2015-11-02 11:17:59",
      "PersonID": "1",
      "ReaderID": "1",
      "tDirection": "IN        ",
      "tReaderDescription": "Maties Gym Entrance",
      "tManual": "0",
      "tDeleted": "0",
      "tTAProcessed": "0",
      "TimesheetDayID": "0",
      "tExtProcessed": "0",
      "tLogical": "0"
    },  
    {
      "TransactionID": "28",
      "tDateTime": "2015-11-03 03:39:17",
      "PersonID": "7",
      "ReaderID": "1",
      "tDirection": "IN        ",
      "tReaderDescription": "Maties Gym Entrance",
      "tManual": "0",
      "tDeleted": "0",
      "tTAProcessed": "0",
      "TimesheetDayID": "0",
      "tExtProcessed": "0",
      "tLogical": "0"
    }
  ]}'; */
		

//cds_SaveFile('sk.txt', $sessionkey);

$sessionkey = '56HJ7UI927DFPT12';

if ($sessionkey == SESSIONKEY) {
	
	$notificationsdb = new MySQL(true, $database, $host, $username, $password);
	if ($notificationsdb->Error()) {
		EchoJson('result',$notificationsdb->Error());
	} else {
	
		if ($syncaction == 'down') {
			$result = SendUserData($notificationsdb, $database, $host, $username, $password);
			echo $result;
			if (strlen($result) > 10) {
			  cds_SaveFile(date('Ymd_His').'_userdata.txt', $result);	
			}					
		} 	else 		
		if ($syncaction == 'update') {
			$result = UpdateUserData($notificationsdb,$ids);		
			cds_SaveFile(date('Ymd_His').'_update.txt', $ids);			
			EchoJson('result',$result);	
		} else {

			$result = SyncTable($notificationsdb, $data, $wherecolumns);
			EchoJson('result',$result);
			if (($wherecolumns != 'ReaderID') && ($wherecolumns != 'TransactionID')) {
				cds_LogFile('synctable', 'WHERE:'.$wherecolumns.' Data:'.$data);	
			}	
			
		}
		
	}	

	$notificationsdb->Close();
} else {
		EchoJson('result',"Invalid Key");
}	

?>



