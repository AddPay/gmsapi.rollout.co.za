<?php

/*===============================================================*/
//------------ API common files                   e   ------------
//-----          Paul Bailey May 2015                        -----
//-----                                                      -----
//----------------------------------------------------------------
/*===============================================================*/


define('APIURL_VALIDATE', 'http://student-account-api.rollout.co.za:7200/api/v1/IValidate/ValidStudent');


function GetGet($field,$default) {
	
  return isset($_GET[$field]) ? $_GET[$field] : $default;	
	
}


function GetPost($field,$default) {
	
  return isset($_POST[$field]) ? $_POST[$field] : GetGet($field,$default);	
	
}

function EchoJson($field,$value) {
	
	$echo[$field] = utf8_encode($value);
	echo json_encode($echo);
	
}


function GetFieldKey($fieldname, $record) {
	
	for($a = 1; $a < 6; $a++){
	
		if (($record[$a]['field_name'] == $fieldname))
			return $a;		
		}
		
		return 0;
	
}

function DisplayMessage($msg) {
	
	$html  = '<div style="text-align:center;"><h2>';
	$html .= $msg;	
	$html  .= '</h2></div>';

	return $html;
	
}

// Helper to send POST requests.
function api_send_post_request($url,$params) {
  
        $postVals = '';
        foreach($params as $key=>$val)
        $postVals .= urlencode($key)."=".urlencode($val)."&";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postVals,0,(strlen($postVals)-1)));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $return_string = curl_exec($ch);
        curl_close($ch);
        
        return $return_string;
}


function ValidateStudent($studentno) {
	
	if ($studentno == '') {
		return 'notvalid';
	}
	
	$parameters = array(
				'student_number' => $studentno, 	
				'language' => 'english',  	
				'format' => 'json', 	
				'service' => 'lookup');

	$jsonresult = api_send_post_request(APIURL_VALIDATE, $parameters);

	$result = json_decode($jsonresult);

	$data['success'] = $result->success;	
	$data['msg'] = $result->result->defined->msg ? $result->result->defined->msg : '';	
	$data['account_type'] = $result->result->defined->account_type;	 
	$data['is_transactionable'] = $result->result->defined->is_transactionable ? $result->result->defined->is_transactionable : 0;	 
	
	
	if ($data['is_transactionable'] == 1) {
		return 'valid';	
	} elseif (cds_Pos($data['msg'],'debt')>=0) {  
		return 'nofunds';	
	} else {
		return 'notregistered';	
	}
	
}

function ClickaTellSMS($cellno, $msg) {
	
  	$headers  = 'From:Rollout Emailer'."\r\n";
  	$headers .= 'Reply-To:emailer@emailer.rollout.co.za'."\r\n";
  	$headers .= 'MIME-Version: 1.0' . "\r\n";
  	$headers .= 'Content-type: text; charset=iso-8859-1' . "\r\n";
  	
  	$message =  'api_id: 3636386'."\r\n".
								'user: MyTimeSlot'."\r\n".
								'password: MOeljuEp'."\r\n";

  	$cell = explode(',',$cellno);	
  
  	for($a = 0; $a < count($cell); $a++){
			$message .= 'to:'.cds_CheckSMSNo($cell[$a])."\r\n";
		}	
	
	$message .= 'text:'.$msg."\r\n".
							'reply:sms@indoormedia.co.za';  	

	//echo $message;
	
 	$result = mail( 'sms@messaging.clickatell.com', 'SMS', $message, $headers);
 	//$result = mail( 'sms@indoormedia.co.za', 'SMS', $message, $headers);
  
  
  return $result;

} 


?>