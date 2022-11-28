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
			} else {
				echo $cnx->Error();				
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

function DoLogFile($message) {
	
	$time = date('Y-m-d H:i:s').','.$message.PHP_EOL;
	cds_AppendFile('logs/'.date('Y-m-d').'_atom.txt', $time); 		

}

/*============================================================*/

$_GET['sk'] = '56HJ7UI927DFPT12';

//$sessionkey = cds_GetPost('sk','not found dude');		
$syncaction = cds_GetPost('action','geteditusers');		
$actiontype = cds_GetPost('actiontype','');		

$sessionkey = '56HJ7UI927DFPT12';

//cds_SaveFile('sk.txt', $sessionkey);

if ($sessionkey == SESSIONKEY) {
	
	$cnx = new MySQL(true, $database, $host, $username, $password);
	if ($cnx->Error()) {
		EchoJson('result',$cnx->Error());
	} else {
	
		if ($syncaction == 'getstatus') {
			//$sql = "select get_enroll_".$siteid." from system_settings"; 
			$sql = "select get_enroll from system_settings"; 
			$getenroll = $cnx->QuerySingleValue($sql);
					
			$sql = "select useredited, allowable_sites from v_atomusers where useredited = 1 limit 1"; 
			
			//if ($siteid == $actiontype) {

			$getedits = $cnx->QuerySingleRow($sql);	 
			$getedit = $getedits->useredited;
			$siteid = $getedits->allowable_sites ? $getedits->allowable_sites : 0;			
				
				//DoLogFile('SITEID,'.$siteid.' - '.$getedit);	
			//}			
						
			$getedit = $getedit ? $getedit : '0';			
			//DoLogFile('ACTIONTYPE,'.$actiontype);	
			
			echo $getedit.','.$getenroll.','.$siteid;
			//DoLogFile('REQUEST,'.$getedit.','.$getenroll.','.$siteid.','.$actiontype);	
			
			if ($getenroll !== '0') {
				DoLogFile('REQUESTENROLL,'.$getenroll.' site='.$siteid);	
			}			
			
		} 	else 		
		if ($syncaction == 'updateusers') {			
			$sql = "update suspi_users set useredited = 0 where id in (".$actiontype.")"; 
			$addedusers = $cnx->Query($sql);
			DoLogFile('CLEARUSEREDIT,'.$actiontype);			
		} else 
		if ($syncaction == 'resenduser') {			
			$sql = "update suspi_users set useredited = 1 where PersonID in (".$actiontype.")"; 
			$addedusers = $cnx->Query($sql);
			DoLogFile('RESENDUSER,'.$actiontype);			
		} else 
		if ($syncaction == 'geteditusers') {		
			$sql = "select * from v_atomusers where useredited = 1 limit 1"; 
			$addedusers = $cnx->QueryArray($sql);
			$json =  $cnx->GetJSON();	
			
			$s = "\\";
			//$json = cds_SearchAndReplace($json,$s,'',false);	
			
			echo $json;
			
			DoLogFile("USER,".$json);			
								
			
			$json = cds_SearchAndReplace($json,$s,'',false);	
			DoLogFile("JSON,".$json);			
					
		} else 
		if (($syncaction == 'clearstatus') && ($actiontype)) {
			$sql = "update system_settings set ".$actiontype." = '0'"; 
			$result = $cnx->Query($sql);			
			DoLogFile('CLEARSTATUS,'.$actiontype.'='.$result);
		}			
	}	

	$cnx->Query('update system_settings set last_gymsync = now() ');
	$cnx->Close();
} else {
		EchoJson('result',"Invalid Key");
		DoLogFile("Invalid Key");
}	

?>



