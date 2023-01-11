<?php

	require_once('config.gymsync.php');
	require_once('api.common.php');
	require_once('cdslib/cdsutils.php');
	require_once('cdslib/cds.mysqli.class.php');
	
	$pid = GetPost('pid','0');		
	$cnx = new MySQL(true, $database, $host, $username, $password);
	
	$sql = "update suspi_users set useredited = ".dbquote(1)." where pPersonNumber = ".$cnx->quote($pid, true); 
	$result = $cnx->Query($sql);			
	
	/**
	 * I don't really want to use IPs just because maties IT is unpredicable with their network
	 * changes and I am afraid they might block the ability to get the IP
	 */

	$get_ip = cds_GetIP();

	$values_array = [
		"date_time" => cds_CurrentDatetime("Y-m-d H:i:s"),
		"pPersonNumber" => $pid,
		"actioned" => 0,
		"ip_address" => $get_ip == "::1" ? "localhost" : $get_ip,
	];

	$values = cds_QuoteArrayValues($values_array, $cnx);

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

function CheckEnrollment() {
	

	var url = 'atom.php?sk=56HJ7UI927DFPT12&action=isenrolled&actiontype=' + enrollmentRequestID;
	console.log( url);	
	
	$.ajax({ 
		url : url,
		success : function( json ) {
			console.log(json)
			try {
				const obj = JSON.parse(json)
				if (typeof obj == "object") {
					if (obj.message == "is enrolled") {
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