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
	
$syncaction = cds_GetPost('action','geteditusers');		
$ids_string = cds_GetPost('actiontype','');

$cnx = new MySQL(true, $database, $host, $username, $password);

if ($cnx->Error()) {
	EchoJson('result',$cnx->Error());
} else {

	/**
	 * Be very careful with values that are passed to the script!
	 * Escape them to make sure it is safe to use in queries.
	 */
	$ids = escapeStringList($cnx, $ids_string);

	if ($syncaction == 'getstatus') {

		// get the user that must be enrolled
		$sql = "select get_enroll from system_settings"; 
		$getenroll = $cnx->QuerySingleValue($sql);
		
		// check whether there are users that have been edited on ATOM - not sure??
		$sql = "select useredited, allowable_sites from v_atomusers where useredited = 1 limit 1"; 
		$getedits = $cnx->QuerySingleRow($sql);

		$getedit = $getedits->useredited;
		$siteid = $getedits->allowable_sites ? $getedits->allowable_sites : 0;	
		$getedit = $getedit ? $getedit : '0';
		
		echo $getedit.','.$getenroll.','.$siteid;
		
		if ($getenroll !== '0') {
			DoLogFile('REQUESTENROLL,'.$getenroll.' site='.$siteid);	
		}

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

	} else if ($syncaction == 'clearstatus') {

		// Clear the user set to be enrolled on ATOM. Should happen ONLY after being enrolled on ATOM.
		$sql = "update system_settings set get_enroll = '0'"; 
		$result = $cnx->Query($sql);			
		DoLogFile('CLEARSTATUS,get_enroll='.$result);

	}
}	

$cnx->Query('update system_settings set last_gymsync = now() ');