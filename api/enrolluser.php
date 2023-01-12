<?php

	require_once('config.gymsync.php');
	require_once('api.common.php');
	require_once('cdslib/cdsutils.php');
	require_once('cdslib/cds.mysqli.class.php');
	
	$pid = GetPost('pid','0');		
	$cnx = new MySQL(true, $database, $host, $username, $password);
	
	/**
	 * Trigger user table sync down from GMS to Persons table on ATOM.
	 * 
	 * This will make sure that if the user is not on ATOM yet, GymSync
	 * will add them so that enrollment can occur.
	 */
	$sql = "update suspi_users set useredited = ".dbquote(1)." where pPersonNumber = ".$cnx->quote($pid, true); 
	$result = $cnx->Query($sql);			
	
	/**
	 * I don't really want to use IPs just because maties IT is unpredicable with their network
	 * changes and I am afraid they might block the ability to get the IP, but for now it's fine
	 * 
	 * If they do block the ability to get the IP, I will update the enroll popup (from GMS)
	 * so that the user can manually choose which PC they want to trigger the ATOM enrollment on.
	 */

	$get_ip = cds_GetIP();

	/**
	 * These values are used by ATOM's enroll API to trigger enrollment.
	 * 
	 * The enroll API need the person number and the IP of the computer
	 * to trigger the ATOM Enrollment Popup on.
	 * 
	 * `actioned` is just a flag to monitor whether Gymsync has processed 
	 * the request or not
	 */
	$values_array = [
		"date_time" => cds_CurrentDatetime("Y-m-d H:i:s"),
		"pPersonNumber" => $pid,
		"actioned" => 0,
		"ip_address" => $get_ip == "::1" ? "localhost" : $get_ip,
	];

	$values = cds_QuoteArrayValues($values_array, $cnx);

	/**
	 * GymSync polls this table for new enrollment requests from GMS and
	 * actions them.
	 */
	$id = $cnx->InsertRow("enrollment_requests", $values);

?>


<!DOCTYPE html>
<html>
<head>

<script type="text/javascript" language="javascript" src="//code.jquery.com/jquery-1.11.3.min.js"></script>
<script type="text/javascript">

const enrollmentRequestID = '<?php echo $id; ?>'
const myInterval = setInterval(CheckEnrollment, 10000)

function DeletePopup() {
	
	window.location.href = 'index.php/members/members-list';
	
}

/**
 * Check whether the enrollment request has been processed by GymSync or not
 * 
 * It will display a spinner until the request is actioned after which it will
 * display "Enrollment Loaded"
 */
function CheckEnrollment() {
	

	var url = 'atom.php?sk=56HJ7UI927DFPT12&action=isactioned&actiontype=' + enrollmentRequestID;
	console.log( url);	
	
	$.ajax({ 
		url : url,
		success : function( json ) {
			try {
				const obj = JSON.parse(json)
				if (typeof obj == "object") {
					if (obj.message == "is actioned") {
						document.getElementById("message").innerHTML = '<br><br>Enrollment Loaded';
						clearInterval(myInterval)
					}
				}
			} catch (error) {
				console.error(error)
			}
		}
	});	
	
}

</script>



</head>


<body>

<div id="message" style="text-align: center">	
	
	<div>
	<img src="images/loadingindicator.gif"/><br>
	Loading enrollment....
	</div>
	
	
</div>

</body>
</html>