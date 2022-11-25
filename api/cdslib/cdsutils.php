<?php
/***********************************************************************************
 * CDS Utils
 *
 * @version 2.00
 * @author Paul Bailey
 * @copyright Cirrlus Corp
 * @link http://www.cirrlus.com
 *
 **********************************************************************************/

// endpoint = google, positionstack
function cds_getGeoAddress($endpoint = 'postionstack',$apikey = 'ad799c12714ae001664bb99dbeaeb9f4',$lat = '',$lng = '')
{
	
	if ($endpoint == 'google') {
	  $url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng='.trim($lat).','.trim($lng).'&sensor=false&key='.$apikey;	
	} else {
    $url = 'http://api.positionstack.com/v1/reverse?query='.trim($lat).','.trim($lng).'&access_key='.$apikey;			  
	}
		
	$json = @file_get_contents($url);
	$data=json_decode($json);
	
	if (is_object($data)) {
			
		if ($endpoint == 'google') {
				$status = $data->status;
				if($status=="OK") { 
			    $result = trim($data->results[0]->formatted_address);
				} else {
					$result = 'No Address';
				}
		  } else { 
		    if (is_array($data->data)) {
		  	  $result = trim($data->data[0]->label);	    	
		    } else {
		    	$result = 'No Address';
		    }
	    }  			
			
  } else {
	  	$result = 'No Address';
	}
  
  return $result;
}

/**
 * cds_apiLog - takes any info and logs to api_logs and to file
 *
 * @param array   $db                 db connection
 * @param string  $script             calling script name
 * @param string  $details            any content
 * @param string  $elapsedtime        if zero then auto time is used, assuming $db->TimerStart has been activated
 * @return array  $savetofile  				if set, will add to local log file
 */
function cds_apiLog($db,$script,$details = '', $recordcount = 0, $elapsedtime = 0,$savetofile = true) {

	$db->TimerStop();

	if ($elapsedtime == 0) {

		$elapsedtime = $db->TimerDuration(4);

	}

	$result = $db->InsertRow('api_logs',array('script' => dbquote($script),
	                                          'elapsed_time' => dbquote($elapsedtime),
	                                          'details' => dbquote($db->SQLFix($details)),
	                                          'record_count' => dbquote($recordcount)
	                                           ));

	if ($savetofile) {
	 cds_LogFile($script,$details);
	}

}

/**
 * cds_pivotTable - takes an array and swicngs to columns
 *
 * @param array   $data               array to translate
 * @param string  $rowField           primary row field
 * @param string  $colField           column field to swing
 * @param string  $valueField         value field
 * @return array  $additionalrowFields  comma delimite field list of additional row fields to display
 */
 function cds_pivotTable($data,$rowField = '', $colField = '', $valueField = '', $additionalRowFields = '') {

		$pivot = array();
		if ($additionalRowFields != '') {
		  $additionalRowFields = explode(',',$additionalRowFields);
		} else {
			$additionalRowFields = null;
		}

		$pivotadditionalfields = null;
		foreach ($data as $row) {
			$pivot[$row[$rowField]][$row[$colField]] = $row;
			if ($additionalRowFields) {
				foreach ($additionalRowFields as $additionalRowField) {
					$pivotadditionalfields[$row[$rowField]][$additionalRowField] = $row[$additionalRowField];
				}
			}
		}

		$pivotrows = array();
		foreach ($pivot as $rowfieldid => $pivotvalues) {

			$row = array();
			$row[$rowField] = $rowfieldid;

			// get additional row fields
			if ($pivotadditionalfields) {
				$additionalRowFields = $pivotadditionalfields[$rowfieldid];
				foreach ($additionalRowFields as $key => $value) {
					$row[$key] = $value;
				}
			}

			foreach ($pivotvalues as $pivotfield => $pivotvalue) {
				$row[$pivotfield] = $pivotvalue[$valueField];
			}
			$pivotrows[] = $row;

		}

		return 	$pivotrows;

}


/**
 * cds_mergePdf - Merge PDF Files using the Api2Pdf api (https://www.api2pdf.com/)
 * @author Paul Bailey <paul@cirrlus.co.za>
 *
 * @param string  $api_key            Api2Pdf API key
 * @param string  $urls               Array of pdf URLS
 * @param string  $file_name          if supplied then the resultant pdf will be saved to this location
 * @param MySQL   $db                 cds.mysqli.class object
 * @param string  $db_table_name      the pdf logs table name with these columns: id, date_time, success (INT), pdf, mb_in, mb_out, cost, response_id, error
 * @param boolean $inline_pdf         Open the PDF in a browser window. Default to false.
 * @return array  on success          [ success(true), pdf, mb_in, mb_out, cost, response_id ]
 *                on error            [ success(false), error ]
 */
function cds_mergePdfUrls($api_key, $urls = [], $file_name = '', $db = null, $db_logs_table_name = 'pdf_logs', $inline_pdf = false)
{

  $api_url = "https://v2.api2pdf.com/pdfsharp/merge";

  $data = [
    'inline' => $inline_pdf
  ];

  $data['urls'] = $urls;

  $dataString = json_encode($data);

  $ch = curl_init($api_url);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
  curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: ' . $api_key
  ]);

  $response = curl_exec($ch);
  $err = curl_error($ch);

  curl_close($ch);

  $values = [];

  if ($err) {
      $values = [
          "success" => false,
          "error" => $err
      ];
  } else {
      $response_array = json_decode($response, true);
      if (isset($response_array["Success"])) {
          if ($response_array["Success"] === true) {
              $values = [
                  "success" => true,
                  "pdf" => $response_array["FileUrl"],
                  "mb_in" => 0,
                  "mb_out" => $response_array["MbOut"],
                  "cost" => $response_array["Cost"],
                  "response_id" => $response_array["ResponseId"]
              ];

              if ($file_name != "") {
                $contents = file_get_contents($response_array["FileUrl"]);
                cds_SaveFile($file_name, $contents);
              }
          } else {
              $values = [
                  "success" => false,
                  "error" => $response_array["error"]
              ];
          }
      } else {
          $values = [
              "success" => false,
              "error" => "Unknown error occurred"
          ];
      }
  }

  if ($db && ($db_logs_table_name != '')) {
      $values_array = $values;
      $values_array["date_time"] = date("Y-m-d H:i:s");
      $values_array["success"] = $values_array["success"] ? 1 : 0;
      $db_values = cds_QuoteArrayValues($values_array, $db);
      $db->InsertRow($db_table_name, $db_values);
  }

  return $values;
}



// merges PDF docs
//file array is arraye of local file locations
//https://www.geeksforgeeks.org/gs-command-in-linux-with-examples/
function cds_mergePDFs($fileArray, $datadir, $outputName) {

	//$fileArray= array("name1.pdf","name2.pdf","name3.pdf","name4.pdf");

	//$datadir = "save_path/";
	//$outputName = $datadir."merged.pdf";

	$cmd = "gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile={$outputName} ";
	//Add each pdf file to the end of the command
	foreach($fileArray as $file) {
	    $cmd .= $file." ";
	}
	$result = shell_exec($cmd);

	return $result;

}

// will convert PDF to formats
//https://www.geeksforgeeks.org/gs-command-in-linux-with-examples/
// ouutput options : png16m txtwrite jpeg
function cds_convertPDF($filename, $datadir, $outputName, $output = 'txtwrite') {

  // -dGraphicsAlphaBits=4
	$cmd = "gs -q -dNOPAUSE -dBATCH -sDEVICE={$output} -r300 -sOutputFile={$outputName} ";
  $cmd .= $filename." ";
	$result = shell_exec($cmd);

	return $result;

}


// generates json and human text of all changes between 2 arrays of field values of cds fabrik logs
function cds_getDataComparison($origDataRow, $newDataRow, $action) {
	$dataArray = [];
	$dataText = '';

	foreach ($newDataRow as $key => $value) {

		$dataArray["status"] = $action;

		if (!$origDataRow) {
			$dataArray["data"][] = ["element" => $key, "value" => $value];
			$dataText .= "Changed `{$key}` to '{$value}'".PHP_EOL;
		} else  {

			$dataText .= "Changed `{$key}` from '{$origDataRow[$key]}' to '{$value}'".PHP_EOL;
			$dataArray["data"][] = [
				"element" => $key,
				"from"    => $origDataRow[$key],
				"to"      =>$value
			];
		}
	}

	return [
		"arrayFormat" => json_encode($dataArray),
		"textFormat" => $dataText
	];
}

// sends logs to the audit trail db, compatible with cdslogs
// if the name is supplied, then the "name" column must exist in teh table
function cds_logToAuditTrail($db,$table,$rowid,$listid,$formid,$userid,$name,$action,$todata, $fromdata = null) {

	$row['rowid']   = $rowid;
	$row['userid']  = $userid;
	$row['tableid'] = $listid;
	$row['formid']  = $formid;
	$row['date']    = ' now() ';
	$row['ip']    	=  "'".(cds_GetIP())."'";

	if ($name != '') {
		$row['name']   	= "'".$name."'";
	}

	$comparison = cds_getDataComparison($fromdata,$todata, $action);

	$row['data_comparison']	      = "'".$comparison['arrayFormat']."'";
	$row['data_comparison_text']	= "'".$comparison['textFormat']."'";

	$ok = $db->InsertRow($table, $row);

	return $ok;

}

//this checks if key supplied is valid session key. Good for autho of scripts
function cds_checkJoomlaSessionKey($db,$tableprefix, $userid, $sk = '') {

  if ($sk == SESSION_KEY_DEFAULT) {
		return true;
	}	else {
		$sql = "SELECT
							{$tableprefix}_session.session_id
						FROM
							{$tableprefix}_users
						INNER JOIN {$tableprefix}_session ON ons_users.id = {$tableprefix}_session.userid
						WHERE id = {$userid} ";
		$sessionkey = $db->QuerySingleValue($sql);
		//echo $sql;
		return ($sessionkey == $sk);
	}

}


// creates url params from supplied key=>value pairs
function cds_getURLParams($params) {

	$getdata = http_build_query($params);

	return $getdata;

}


// creates url params from supplied key=>value pairs and retursn file get conents
function cds_getURL($url, $params) {

	$getdata = http_build_query($params);

	$url = $url.'?'.$getdata;

	$result = file_get_contents($url);

	return $result;

}


//will validate an email address or comma list of emails
function cds_isValidEmail($email) {

	$emails = explode(',', $email);
	foreach ($emails as $email) {

	 if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
	    return false;  // any one fails then pop out here
	  }

	}

	return true;

}

// this will take a fabrik json array and convert it to a sql filter statement, handy for queries
function cds_convertFabrikArrayToSqlFilter($fieldname, $items, $operand = '=', $joiner = 'or') {

	$fields = str_replace('"', '', $items);
	$fields = str_replace('[', '', $fields);
	$fields = str_replace(']', '', $fields);

	$items = explode(',',$fields);
	$sql = '';
	for($a = 0; $a < count($items)-1; $a++){
	  $sql .= '('.$fieldname.$operand.$items[$a].') '.$joiner.' ';
	}

	if (count($items) > 0) {
		$sql .= '('.$fieldname.' '.$operand.' '.$items[$a].') ';
	}

	return $sql;

}

// this will ensuure a micro stamped filename in format YYYYMMDD_HHMMSS_UUUUUU where u is microsecond
// you can add pre and post fix test as well, which helps to create a full filename
function cds_getTimeStampForFilename($prefix="",$postfix="") {

  $date = new DateTime( "NOW" );
	$fdate = $date->format( "Ymd_His_u" );

	return $prefix.$fdate.$postfix;

}

// takes associatev array and concats the field values using the glue, optional quote enclosesure
function cds_groupConcat($rows, $fieldname, $glue = ',', $enclosequotes = false) {

	$concats = array();

			foreach ($rows as $row) {

				$concats[] = $enclosequotes ? "'".$row[$fieldname]."'" : $row[$fieldname];

			}

	return implode($glue, $concats);

}

/**
 * cds_translateColumnToRowArray - takes a 2D array and swicngs column data to row data, handy to display column data as rows
 *
 * @param array   $rows               array to translate
 * @param string  $fieldheader        name of the header title to hold fieldnames
 * @param string  $rowheader          name of the header title to hold data
 * @return array  none
 */
 function cds_translateColumnToRowArray($rows,$fieldheader = 'Fields', $rowheader = 'Data') {

	if (!isset($rows[0])) {   // if not array then make one first
		$datarows[] = $rows;
	} else {
		$datarows = $rows;
	}

  foreach ($datarows as $row)	{

		$translated = array();
	  foreach($row as $key => $value) {

	  	 $translated[$fieldheader] = trim($key);
	  	 $translated[$rowheader]   = $value;
		   $translatedrows[] = $translated;

		}

	}

	return $translatedrows;

}


/**
 * cds_Api2Pdf - Generate a PDF using the Api2Pdf api (https://www.api2pdf.com/)
 * @author Richard Slabbert <richard@cirrlus.co.za>
 *
 * @param string  $api_key            Api2Pdf API key
 * @param string  $url                you must specify either a url or direct html to convert (if a doc, then you must supply an online accessible url)
 * @param string  $html
 * @param string  $file_name          if supplied then the resultant pdf will be saved to this location
 * @param array   $options            Api2Pdf options - only available for chrome and wkhtmltopdf types (see https://app.swaggerhub.com/apis-docs/api2pdf/api2pdf/1.0.0)
 * @param MySQL   $db                 cds.mysqli.class object
 * @param string  $db_table_name      the pdf logs table name with these columns: id, date_time, success (INT), pdf, mb_in, mb_out, cost, response_id, error
 * @param boolean $inline_pdf         Open the PDF in a browser window. Default to false.
 * @param string  $conversion_method  "chrome", "wkhtmltopdf", or "libreoffice" (see https://app.swaggerhub.com/apis-docs/api2pdf/api2pdf/1.0.0 for more info)
 * @return array  on success          [ success(true), pdf, mb_in, mb_out, cost, response_id ]
 *                on error            [ success(false), error ]
 */
function cds_Api2Pdf($api_key, $url = '', $html = '', $file_name = '', $options = [], $db = null, $db_logs_table_name = 'pdf_logs', $inline_pdf = false, $conversion_method = "wkhtmltopdf")
{
  if ($options == []) {
    $options = ['orientation' => 'portrait', 'pageSize'=> 'A4'];
  }
  switch ($conversion_method) {
    case "chrome":
      $api_url = ($url != '') ? "https://v2.api2pdf.com/chrome/pdf/url" : "https://v2.api2pdf.com/chrome/pdf/html";
    break;
    case "wkhtmltopdf":
      $api_url = ($url != '') ? "https://v2.api2pdf.com/wkhtml/pdf/url" : "https://v2.api2pdf.com/wkhtml/pdf/html";
    break;
    case "libreoffice":
      $api_url = "https://v2018.api2pdf.com/libreoffice/convert";
    break;
    default:
      $api_url = ($url != '') ? "https://v2.api2pdf.com/wkhtml/pdf/url" : "https://v2.api2pdf.com/wkhtml/pdf/html";
  }

  $data = [
    'options' => $options,
    'inline' => $inline_pdf ? 'true' : 'false'
  ];

  if ($url) {
      $data['url'] = $url;
  } elseif ($html) {
      $data['html'] = $html;
  } else {
      $data['html'] = 'No URL or data';
  }

  $dataString = cds_json_encode_safe($data);

  //echo $dataString;
  //echo $api_url;

  $ch = curl_init($api_url);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
  curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: ' . $api_key
  ]);

  $response = curl_exec($ch);
  $err = curl_error($ch);

  curl_close($ch);

  $values = [];

  if ($err) {
      $values = [
          "success" => false,
          "error" => $err
      ];
  } else {
      $response_array = json_decode($response, true);
      if (isset($response_array["Success"])) {
          if ($response_array["Success"] === true) {
              $values = [
                  "success" => true,
                  "pdf" => $response_array["FileUrl"],
                  "mb_in" => 0,
                  "mb_out" => $response_array["MbOut"],
                  "cost" => $response_array["Cost"],
                  "response_id" => $response_array["ResponseId"]
              ];

              if ($file_name != "") {
                $contents = file_get_contents($response_array["FileUrl"]);
                $filesize = cds_SaveFile($file_name, $contents);
                $values['filesize'] = $filesize;
              }
          } else {
              $values = [
                  "success" => false,
                  "error" => $response_array["error"]
              ];
          }
      } else {
          $values = [
              "success" => false,
              "error" => "Unknown error occurred"
          ];
      }
  }

  if ($db && ($db_logs_table_name != '')) {
      $values_array = $values;
      $values_array["date_time"] = date("Y-m-d H:i:s");
      $values_array["success"] = $values_array["success"] ? 1 : 0;
      $db_values = cds_QuoteArrayValues($values_array, $db);
      $result = $db->InsertRow($db_logs_table_name, $db_values);
  }

   return $values;
}

// Function: _deg2rad_multi
// Desc: A quick helper function.  Many of these functions have to convert
//   a value from degrees to radians in order to perform math on them.
function _deg2rad_multi() {
    // Grab all the arguments as an array & apply deg2rad to each element
    $arguments = func_get_args();
    return array_map('deg2rad', $arguments);
}

// Function: latlon_bearing_great_circle
// Desc:  This function calculates the initial bearing you need to travel
//   from Point A to Point B, along a great arc.  Repeated calls to this
//   could calculate the bearing at each step of the way.
function cds_latlonBearing($lat_a, $lon_a, $lat_b, $lon_b) {

	// Convert our degrees to radians:
	list($lat1, $lon1, $lat2, $lon2) =   _deg2rad_multi($lat_a, $lon_a, $lat_b, $lon_b);

	// Run the formula and store the answer (in radians)
	$rads = atan2(
	        sin($lon2 - $lon1) * cos($lat2),
	        (cos($lat1) * sin($lat2)) -
	              (sin($lat1) * cos($lat2) * cos($lon2 - $lon1)) );

	// Convert this back to degrees to use with a compass
	$degrees = rad2deg($rads);

	// If negative subtract it from 360 to get the bearing we are used to.
	$degrees = ($degrees < 0) ? 360 + $degrees : $degrees;

	return $degrees;
}


//calculates distance given 2 coords
function cds_getGpsDistance($lat1,$lon1,$lat2,$lon2,$unit='K') {
    try{

	    $radius = 3959;  //approximate mean radius of the earth in miles, can change to any unit of measurement, will get results back in that unit

			if (is_numeric($lat1) &&
					is_numeric($lat2) &&
					is_numeric($lon1) &&
					is_numeric($lon2)) {

					$delta_Rad_Lat = deg2rad($lat2 - $lat1);  //Latitude delta in radians
			    $delta_Rad_Lon = deg2rad($lon2 - $lon1);  //Longitude delta in radians
			    $rad_Lat1 = deg2rad($lat1);  //Latitude 1 in radians
			    $rad_Lat2 = deg2rad($lat2);  //Latitude 2 in radians

			    $sq_Half_Chord = sin($delta_Rad_Lat / 2) * sin($delta_Rad_Lat / 2) + cos($rad_Lat1) * cos($rad_Lat2) * sin($delta_Rad_Lon / 2) * sin($delta_Rad_Lon / 2);  //Square of half the chord length

					if (!is_nan(asin(sqrt($sq_Half_Chord)))) {
						$ang_Dist_Rad = 2 * asin(sqrt($sq_Half_Chord));  //Angular distance in radians
					} else {
						$ang_Dist_Rad = 0;
					}

			    $m = $radius * $ang_Dist_Rad;

			} else {

				$m = 0;

			}

    } catch (Exception $e) {
        $m = 0;
    }

    switch(strtoupper($unit))
    {
        case 'K':
            // kilometers
            return $m * 1.609344;
            break;
        case 'N':
            // nautical miles
            return $m * 0.868976242;
            break;
        case 'F':
            // feet
            return $m * 5280;
            break;
        case 'I':
            // inches
            return $m * 63360;
            break;
        case 'M':
        default:
            // miles
            return $m;
            break;
    }
}


/**
 * Transform an associative array into a csv string
 *
 * @param array $data The associative array to transform into a csv string
 * @param string $delimiter The optional delimiter parameter sets the field delimiter (one character only).
 * @param string $enclosequotes The optional enclosure parameter sets the field enclosure (no more than one character).
 *
 * @return string|false — the CSV string or false on failure.
 */
function cds_ArrayToCSV($data, $delimiter = ',', $enclosequotes = '') {
        # Generate CSV data from array
        $fh = fopen('php://temp', 'rw'); # don't create a file, attempt
                                         # to use memory instead
        # write out the headers
        fputcsv($fh, array_keys(current($data)));
        # write out the data
        foreach ( $data as $row ) {
          if ($enclosequotes === '') {
            // An enclosure character is required for fputcsv in PHP8
            // If you don't want to enclose, use fputs
            fputs($fh, implode($delimiter, $row) . "\n");
          } else {
            fputcsv($fh, $row, $delimiter, $enclosequotes);
          }
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        return $csv;
}

function cds_subtractDaysFromDate($date, $days) {

	$datetime = new DateTime($date);
	$datetime->modify('-'.$days.' day');

	return $datetime->format('Y-m-d H:i:s');

}

// will convert any url or html code to a pdf doc. Either use $url or $html.
// NB : url must NOT be URLENCODED
function cds_getPDF($url = '',$html = '', $landscape = true, $format = 'A4',
                    $scale = 1, $marginTop = 0, $marginBottom = 0, $marginLeft = 0, $marginRight = 0) {

	define('PDF_API_KEY','951278a9bab4638a2fed58a1bab1859a75d9bd1e30a15eef42a348e0c596419c');
	define('API_URL','https://api.html2pdf.app/v1/generate');

	$data = [
	  'apiKey' => PDF_API_KEY,
	  'options' => ['landscape' => $landscape,
	                'format' => $format,
	                'scale' => $scale,
	                'marginTop' => $marginTop,
	                'marginBottom' => $marginBottom,
	                'marginLeft' => $marginLeft,
	                'marginRight' => $marginRight
	                ]
	];

	if ($url) {

	  $data['url'] = urldecode($url);   // just to make sure someone did urlencode it

	} elseif ($html) {

		$data['html'] = $html;

	} else {

		$data['html'] = 'No URL or data';

	}

	$dataString = json_encode($data);

	$ch = curl_init(API_URL);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
	    'Content-Type: application/json',
	]);

	$response = curl_exec($ch);
	$err = curl_error($ch);

	curl_close($ch);

	if ($err) {
	  return 'Error #:' . $err;
	} else {
	  return $response;
	}

}

// does a PHP redirect
function cds_Redirect($url, $permanent = false, $exit = true)
{
    if (headers_sent() === false)
    {
        header('Location: ' . $url, true, ($permanent === true) ? 301 : 302);
    }

    if ($exit) exit();
}



/**
	 * This function returns the associated array as a Styled HTML table
	 *
	 * @param array $data, can be object array or keyvalue array or JSON string or CSV string (header row and comma delimted) or XML string
	 * @param string $cssContent provide css if needed,
	 * @param boolean $showHeader (Optional) TRUE if you want to show the header,
	 * @param boolean $showFooter (Optional) TRUE if you want to show the footer,*
	 * @param string $cssTable (Optional) Style information for the table
	 * @param string $cssHeader (Optional) Style information for the header row
	 * @param string $cssCells (Optional) Style information for the cells
	 * @param string $cssFooter (Optional) Style information for the header row*
	 * @return string HTML containing a table with all records listed
	 *
	 * Use this URL to easily create CSS styling for the tables out teh box
	 * https://divtable.com/table-styler/
	 *
	 */

	function cds_DataToHTMLTable($data,
	                            $cssContent = '',
												 		  $cssTableClassName = 'default',
															$cssHeaderClassName = '',
															$cssCellsClassName = '',
															$cssRowClassName = '',
															$cssFooterClassName = '',
															$showHeader = true,
															$showFooter = true,
															$footertext = '',
															$escapehtml = true,
															$cssTableId = 'tableid')   {

		if ($cssTableClassName == 'default') {
			$cssContent = 'table.blueTable{font-family:Arial,Helvetica,sans-serif;border:1px solid #1c6ea4;background-color:#fff;width:100%;text-align:left;border-collapse:collapse}table.blueTable td,table.blueTable th{border:1px solid #c8c8c8;padding:0 2px}table.blueTable tbody td{font-size:12px}table.blueTable tr:nth-child(even){background:#d0e4f5}table.blueTable thead{background:#1c6ea4;background:-moz-linear-gradient(top,#5592bb 0,#327cad 66%,#1c6ea4 100%);background:-webkit-linear-gradient(top,#5592bb 0,#327cad 66%,#1c6ea4 100%);background:linear-gradient(to bottom,#5592bb 0,#327cad 66%,#1c6ea4 100%);border-bottom:2px solid #444}table.blueTable thead th{font-size:12px;font-weight:700;color:#fff;border-left:1px solid #d0e4f5}table.blueTable thead th:first-child{border-left:none}table.blueTable tfoot{font-size:12px;font-weight:700;color:#000;background:#d0e4f5;background:-moz-linear-gradient(top,#dcebf7 0,#d4e6f6 66%,#d0e4f5 100%);background:-webkit-linear-gradient(top,#dcebf7 0,#d4e6f6 66%,#d0e4f5 100%);background:linear-gradient(to bottom,#dcebf7 0,#d4e6f6 66%,#d0e4f5 100%);border-top:2px solid #444}table.blueTable tfoot td{font-size:12px}table.blueTable tfoot .links{text-align:right}table.blueTable tfoot .links a{display:inline-block;background:#1c6ea4;color:#fff;padding:2px 8px;border-radius:5px}';
			$cssTableClassName = 'blueTable';
		}

		// if CSV, JSON, XML then handle it here first into an array
		if (is_string($data)) {
			$newdata = cds_json_decode($data);                 // try Json
			if (is_string($newdata)) {   // not json, so try XML
				$ob = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOWARNING | LIBXML_NOERROR);
				if ($ob) {
					$json  = cds_json_encode($ob);
					$newdata = cds_json_decode($json, true);
					if (is_string($newdata)) {   // not json, so try XML
						$newdata = array();
					}
				} else {
					$newdata = cds_CSVtoArray($data);
				}
			}
			$data = $newdata;
		}

		$cssTableClassName = $cssTableClassName ? 'class="'.$cssTableClassName.'"' : '';
		$cssTableId = $cssTableId ? 'id="'.$cssTableId.'"' : '';

		$html = '';
		if (is_array($data)) {

				if ($cssContent) {
					$html = '<style>'.PHP_EOL.$cssContent.PHP_EOL.'</style>'.PHP_EOL;
				}

				$html .= "<table {$cssTableId} {$cssTableClassName}>".PHP_EOL;
				$headerdone = !$showHeader;
				$noofcols = 0;
				foreach ($data as $row) {
					if (!$headerdone) {
						$html .= "\t<thead>\t<tr>\n";
						foreach ($row as $key => $value) {
							$html .= "\t\t<th>" . ($escapehtml ? htmlspecialchars($key) : $key) . "</th>\n";
							$noofcols++;
						}
						$html .= "\t</tr>\t</thead>\n";
						$headerdone = true;
					}
					$html .= "\t<tr>\n";
					foreach ($row as $key => $value) {
						$html .= "\t\t<td>" . ($escapehtml ? htmlspecialchars($value) : $value) . "</td>\n";
					}
					$html .= "\t</tr>\n";
				}

				if ($showFooter) {
					$footertext = $footertext ? $footertext : 'Record Count: '.count($data);
					$html .= '<tfoot><tr><td colspan="'.$noofcols.'">'.$footertext.'</td></tr></tfoot>'.PHP_EOL;
				}

				$html .= "</table>";

		} else {
			$html = false;
		}
		return $html;
	}


/**
 * Wrapper for json_decode that throws when an error occurs.
 *
 * @param string $json    JSON data to parse
 * @param bool $assoc     When true, returned objects will be converted
 *                        into associative arrays.
 * @param int    $depth   User specified recursion depth.
 * @param int    $options Bitmask of JSON decode options.
 *
 * @return mixed
 * @throws error if the JSON cannot be decoded.
 * @link http://www.php.net/manual/en/function.json-decode.php
 */
function cds_json_decode($json, $assoc = false, $depth = 512, $options = 0)
{
  $data = json_decode($json, $assoc, $depth, $options);
  if (JSON_ERROR_NONE !== json_last_error()) {
    return 'json_decode error: ' . json_last_error_msg();
  }
  return $data;
}

/**
 * Wrapper for JSON encoding that throws when an error occurs after safe encode
 *
 * @param mixed $value   The value being encoded
 * @param int    $options JSON encode option bitmask
 * @param int    $depth   Set the maximum depth. Must be greater than zero.
 *
 * @return string
 * @throws errror  if the JSON cannot be encoded.
 * @link http://www.php.net/manual/en/function.json-encode.php
 */
function cds_json_encode($value, $depth = 512, $options = 0)
{
  $json = json_encode($value, $options, $depth);
  if (JSON_ERROR_NONE !== json_last_error()) {
    // if error, then try and safe encode it first
    $json = json_encode(utf8_encode_mix($value), $options, $depth);
  	if (JSON_ERROR_NONE !== json_last_error()) {
       return 'json_encode error: ' . json_last_error_msg();
		}
  }
  return $json;
}

//renames an array key, eg: ['id'] = 22 to ['newid'] = 22
//return true or false
function cds_renameArrayKey($oldkey, $newkey, array &$dataarray) {
    if (array_key_exists($oldkey, $dataarray)) {
        $dataarray[$newkey] = $dataarray[$oldkey];
        unset($dataarray[$oldkey]);
        return TRUE;
    } else {
        return FALSE;
    }
}

// adds an echo of the message, with html linefeed, or php linefeed
 function cds_echo($message,$htmllinefeed = true, $phpeol = true) {

 		$mess = $htmllinefeed ? $message.'<br>' : $message;

 		echo $phpeol ? $mess.PHP_EOL : $mess;

 }

// adds no of months to a datetime
function cds_addMonthsToDate($numMonths = 1, $timeStamp = null){



/*    $timeStamp === null and $timeStamp = time();//Default to the present
    $newMonthNumDays =  date('d',strtotime('last day of '.$numMonths.' months', $timeStamp));//Number of days in the new month
    $currentDayOfMonth = date('d',$timeStamp);

    if($currentDayOfMonth > $newMonthNumDays){
      $newTimeStamp = strtotime('-'.($currentDayOfMonth - $newMonthNumDays).' days '.$numMonths.' months', $timeStamp);
    } else {
    $newTimeStamp = strtotime($numMonths.' months', $timeStamp);
    }

    return   date('Y-m-d', $newTimeStamp); */

    $numdays = date('d',strtotime($timeStamp))-1;

    $time = strtotime('first day of +'.$numMonths.' month', strtotime($timeStamp));

    return date('Y-m-d H:i:s', strtotime('+'.$numdays.' day', $time));

}
// takes url params and converts to key value pair array
// eg: index.php?foo=bar&foo1=bar1  params['foo'] = 'bar'  params['foo1'] = 'bar1'
function cds_parseRawParams($rawparams) {

  $rawparams = explode('&',$rawparams);
    $params = array();
    foreach ($rawparams as $param) {
        $d = explode('=',$param);
        $params[$d[0]] = $d[1];
    }

    return $params;

}


// Helper to send POST requests.
function cds_sendPostRequest($url,$params) {

				$postVals = http_build_query($params);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postVals);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $return_string = curl_exec($ch);
        curl_close($ch);

        return $return_string;
}


function cds_Now($format = 'Y-m-d H:i:s') {

	return date($format);

}


function cds_CheckSMSNo($smsno) {

	$no = cds_SearchAndReplace($smsno,'+','',false);

	if (strlen($no) > 0) {
		if ($no[0] == '0') {
			$no = '27'.substr($no, 1, strlen($no));
		}
	}
	return $no;

}


//appends message to a logfile with datetime stamp , with optional root folder
function cds_LogFile($fname,$message,$root='') {

	$time = date('Y-m-d H:i:s').','.$message.PHP_EOL;
	$dir = $root.'logs/';
	cds_ForceDirectories($dir);
	cds_AppendFile($dir.date('Y-m-d').'_'.$fname.'.txt', $time);

}


function cds_timestr2minutes($timestr) {

	$time = explode(':', $timestr);
	return round($time[0]*60) + ($time[1]) + round($time[2]/60);

}

// only encodes if string is NOT utf8
function cds_utf8Encode($in_str)
{
	$in_str = $in_str == null ? '' : $in_str;    // make sure valid string
  $cur_encoding = mb_detect_encoding($in_str) ;
  if($cur_encoding == "UTF-8" && mb_check_encoding($in_str,"UTF-8"))
    return $in_str;
  else
    return utf8_encode($in_str);
}

// safer use of utf8_encode using cds_utf8Encode
function cds_utf8EncodeArray($input, $encode_keys=false)
    {
        if(is_array($input))
        {
            $result = array();
            foreach($input as $k => $v)
            {
                $key = ($encode_keys)? cds_utf8Encode($k) : $k;
                $result[$key] = cds_utf8EncodeArray( $v, $encode_keys);
            }
        }
        else
        {
            $result = cds_utf8Encode($input);
        }

        return $result;
}

// not used anymore
function utf8_encode_mix($input, $encode_keys=false)
    {
        if(is_array($input))
        {
            $result = array();
            foreach($input as $k => $v)
            {
                $key = ($encode_keys)? utf8_encode($k) : $k;
                $result[$key] = utf8_encode_mix( $v, $encode_keys);
            }
        }
        else
        {
            $result = utf8_encode($input);
        }

        return $result;
}


// note this function will convert all objects to string
function cds_json_encode_safe($value) {

    $encoded = json_encode(cds_utf8EncodeArray($value));
    return $encoded;
}


function newLatLongPoint($lat, $lng, $brng, $dist) {
      $meters = $dist; // dist in meters
      $dist =  $meters/1000; // dist in km
      $rad = 6371; // earths mean radius
      $dist = $dist/$rad;  // convert dist to angular distance in radians
      $brng = deg2rad($brng);  // conver to radians
      $lat1 = deg2rad($lat);
      $lon1 = deg2rad($lng);

      $lat2 = asin(sin($lat1)*cos($dist) + cos($lat1)*sin($dist)*cos($brng) );
      $lon2 = $lon1 + atan2(sin($brng)*sin($dist)*cos($lat1),cos($dist)-sin($lat1)*sin($lat2));
      $lon2 = fmod($lon2 + 3*M_PI, 2*M_PI) - M_PI;  // normalise to -180..+180�
      $lat2 = rad2deg($lat2);
      $lon2 = rad2deg($lon2);

      $result['lat'] = $lat2;
      $result['long'] = $lon2;

      return $result;

}

function cds_convertCircleToPolygon($lat,$long,$radius,$points){

    $bearing = 0;
    $deg = 360/$points;

    for($i=0; $i<$points; $i++){
        $result[] = newLatLongPoint($lat, $long, $bearing, $radius);
        $bearing += $deg;
    }

return $result;

}

function cds_DateTimeDiffInMinutes($from_time,$to_time)  {

  return round(abs($to_time - $from_time) / 60,0);

}

// quotes set of array values, use din mySQL statements mostly
// Paul 12/02/2017
// if you add a dblink, it will saniztize the values
// it also picks up if there is an array within an array()
// so now you can pass the full array of keyvalues
// you can also ignore enclosing null values

function cds_QuoteArrayValues($valuearay, $dblink = null, $ignorenullvalues = false) {

	$poparray = array_values($valuearay);
  if (!is_array(array_pop($poparray))) {
		foreach	($valuearay as $key => $value) {
		  $value = $dblink != null ? $dblink->SQLFix($value) : $value;
			$valuesarray[$key] = $ignorenullvalues && (strtoupper($value) == 'NULL') ? $value : "'".$value."'";
		}
	} else {

		foreach ($valuearay as $row) {

			foreach	($row as $key => $value) {
			  $value = $dblink != null ? $dblink->SQLFix($value) : $value;
				$data[$key] = $ignorenullvalues && (strtoupper($value) == 'NULL') ? $value : "'".$value."'";
			}
			$valuesarray[] = $data;
		}
	}

	return $valuesarray;

}

// quotes array of comma delimted values used in mySQL statements mostly
// Paul 12/02/2016
function cds_QuoteArray($ids) {

	$ids = explode(',', $ids);
	$ids = "'".implode($ids,"','")."'";
	return $ids;

}

// converts an array of key objects to array of key/values
// converts test=>1234 to result['test'] = 1234
// Paul
// if you add a dblink, it will saniztize the values
function cds_ObjectToArray($data, $addquotes = false, $dblink = null)
{
    foreach ($data as $key => $value)
    {
		    $value = $dblink != null ? $dblink->SQLFix($value) : $value;
				$value = $addquotes ? "'".$value."'" : $value;

        $result[$key] = $value;
    }
    return $result;
}



function cds_GetMinutes($startdatetime, $enddatetime) {

	$mins = round(abs(strtotime($enddatetime) - strtotime($startdatetime))/60);

  return $mins ;

}


function cds_StripFabrikArray($value) {

	$fields = str_replace('"', '', $value);
	$fields = str_replace('[', '', $fields);
	$fields = str_replace(']', '', $fields);

	return $fields;

}


function cds_EmailTOBulkSMS($cellno, $msg, $replymail='', $password='') {

  	$headers = "From: ".$replymail.'\r\n';
  	$headers .= "Reply-To: '.$replymail.'\r\n";
  	$headers .= 'MIME-Version: 1.0' . "\r\n";
  	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

  	$cell = explode(',',$cellno);

  	for($a = 0; $a < count($cell); $a++){
		$cell[$a] = $cell[$a].'@2way.co.za';
	}

	$cellno = implode(',',$cell);

	echo $cellno;

  	$result = mail($cellno, $password, $msg, $headers);

  return $result;

}

function cds_AddMinutesToDate($dte, $minutes) {

	$dateTime = new DateTime($dte);
	$sign = ($minutes >= 0 ? '+' : '-');
	$dateTime->modify($sign.abs($minutes).' minutes');

	return $dateTime;

}

function cds_AddDaysToDate($dte, $days, $dateformat = 'Y-m-d H:i:s') {

	$datetime = new DateTime($dte);
	$datetime->modify('+'.$days.' day');

	return $datetime->format($dateformat);

}

 	function cds_flush() {
		//print str_pad(' ', intval(ini_get('output_buffering')))."\n";
		echo str_repeat(' ',1024*64);
		//ob_end_flush();
		flush();
	}

function cds_DebugTime($message, $starttime) {

	$endtime = microtime(true);
	$timediff = $endtime - $starttime;
	echo $timediff.'-'.$message.'<br>';
	cds_flush();

}

 function cds_GetFullURL() {

	return sprintf('%s://%s/%s',isset($_SERVER['HTTPS']) ? 'https' : 'http',    $_SERVER['HTTP_HOST'],    $_SERVER['REQUEST_URI']);

 }

		function cds_removeJsonTrailingCommas($json)
    {
        $json=preg_replace('/,\s*([\]}])/m', '$1', $json);
        return $json;
    }

    function cds_JsonDecode($json)
    {
        return json_decode(cds_removeJsonTrailingCommas(utf8_encode($json)));
    }


function cds_InBetween($value, $min, $max) {

	return (($value >= $min) && ($value <= $max));

}

function cds_CheckIfDOW($date, $dow) {

	$dowarray = explode(',', $dow);
	$day_number = (int) date('N', $date);

	return in_array($day_number,$dowarray);

}

function cds_PostCurlURL($url, $parameters, $https = false) {
	// where are we posting to?

	// what post fields?
	$fields = $parameters;

	// build the urlencoded data
	$postvars = http_build_query($fields);

	// open connection
	$ch = curl_init();

	// set the url, number of POST vars, POST data
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, count($fields));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	// execute post
	$result = curl_exec($ch);

	// close connection
	curl_close($ch);

	return $result;
}


function cds_PostURL($url, $parameters = null) {

	// use key 'http' even if you send the request to https://...
	$options = array(
  	  	'http' => array(
    	    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
      	  	'method'  => 'POST',
      	  	'timeout' => 60,
			"ignore_errors" => 1,
        	'content' => http_build_query($parameters),
	    ),
	);
	$context  = stream_context_create($options);
	$result = file_get_contents($url, false, $context);

	return $result;
}




function cds_EncryptJoomlaPassword($password) {

	$salt = md5(rand(1000));

	return md5($password.$salt).':'.$salt;

}

function cds_linefeed($value) {

	return $value.'<br>';

}

//gets offset to our timesone in SA
function cds_getTimezoneOffset() {

	return timezone_offset_get( new DateTimeZone( 'Africa/Johannesburg' ), new DateTime() );

}

// return time value, else a format
function cds_CurrentDatetime($formatdate = '') {

		$offset = cds_getTimezoneOffset();
                $now = time()+$offset;
	       //$now = time() - date('Z') + $offset;
               if ($formatdate !== '') {
			return date($formatdate,$now);
		} else
			return $now;
}

function cds_SanitizeFilename($str = '')
{
    $str = strip_tags($str);
    $str = preg_replace('/[\r\n\t ]+/', ' ', $str);
    $str = preg_replace('/[\"\*\/\:\<\>\?\'\|]+/', ' ', $str);
    $str = strtolower($str);
    $str = html_entity_decode( $str, ENT_QUOTES, "utf-8" );
    $str = htmlentities($str, ENT_QUOTES, "utf-8");
    $str = preg_replace("/(&)([a-z])([a-z]+;)/i", '$2', $str);
    $str = str_replace(' ', '-', $str);
    $str = rawurlencode($str);
    $str = str_replace('%', '-', $str);
    return $str;
}

function cds_dec2hex($number)
{
    $hexvalues = array('0','1','2','3','4','5','6','7',
               '8','9','A','B','C','D','E','F');
    $hexval = '';
     while($number != '0')
     {
        $hexval = $hexvalues[bcmod($number,'16')].$hexval;
        $number = bcdiv($number,'16',0);
    }
    return $hexval;
}

// Input: A hexadecimal number as a String.
// Output: The equivalent decimal number as a String.
function cds_hex2dec($number)
{
    $decvalues = array('0' => '0', '1' => '1', '2' => '2',
               '3' => '3', '4' => '4', '5' => '5',
               '6' => '6', '7' => '7', '8' => '8',
               '9' => '9', 'A' => '10', 'B' => '11',
               'C' => '12', 'D' => '13', 'E' => '14',
               'F' => '15');
    $decval = '0';
    $number = strrev($number);
    for($i = 0; $i < strlen($number); $i++)
    {
        $decval = bcadd(bcmul(bcpow('16',$i,0),$decvalues[$number[$i]]), $decval);
    }
    return $decval;
}

function cds_RGBstr2Hex($string) {

	$string = cds_SearchAndReplace($string,'(','');  // strip out brackets if supplied
	$string = cds_SearchAndReplace($string,')','');  // strip out brackets if supplied

	$colors = explode(',',$string);

	$r = dechex($colors[0]);
	$g = dechex($colors[1]);
	$b = dechex($colors[2]);

	If (strlen($r)<2) {$r='0'.$r;}
	If (strlen($g)<2) {$g='0'.$g;}
	If (strlen($b)<2) {$b='0'.$b;}

 	return $r . $g . $b;

}

function cds_GetIP()
{
  if (!empty($_SERVER['HTTP_CLIENT_IP']))
  //check ip from share internet
  {
    $ip=$_SERVER['HTTP_CLIENT_IP'];
  }
  elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
  //to check ip is pass from proxy
  {
    $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
  }
  else
  {
    $ip=$_SERVER['REMOTE_ADDR'];
  }
  return $ip;
}

function cds_CountOccurance($SearchString,$SearchWord) {

  return substr_count($SearchString,$SearchWord);

}

//copies a substring from start to end
function cds_Copy($CopyString,$StartPos, $Length) {

  $str = substr($CopyString, $StartPos, $Length);
  return $str;

}

// copies from right
function cds_CopyRight($string,$noofchars)
{
    $vright = substr($string, strlen($string)-$noofchars,$noofchars);
    return $vright;

}

function cds_CopyCharRight($string,$char)
{

		$lastpos = $string[strlen($string)-1];

		if ($lastpos == $char)
			return substr($string, 0, strlen($string)-1);
		else
			return $string;

}
// copies from right
function cds_CopyLeftNoCharFromRight($string,$noofchars)
{
    $vright = substr($string, 0, strlen($string)-$noofchars);
    return $vright;

}

// copies from right
function cds_CopyLeft($string,$noofchars)
{
    $vright = substr($string, 0,$noofchars);
    return $vright;

}

function cds_CopyLeftString($string,$searchstring)
{
    $pos = cds_Pos($string,$searchstring);

		if ($pos >= 0) {
			return substr($string,0, $pos);
		} else {
			return $string;
		}

}

function cds_CopyRightString($string,$searchstring)
{
    $pos = cds_Pos($string,$searchstring);

		if ($pos >= 0) {
			return substr($string,$pos);
		} else {
			return '';  // not found so return nothing
		}

}


// eg: $string = "123456789"; $a = "12"; $b = "9"; echo get_between($string, $a, $b); //Output: //345678
function cds_GetStrBetween($input, $startstr, $endstr, $include = TRUE)
{

	if ($include == TRUE)
	{
		$substr = substr($input, strpos($input, $startstr), strpos($input, $endstr)+strlen($endstr));
	}
	else
	{
		$substr = substr($input, strlen($startstr)+strpos($input, $startstr), (strlen($input) - strpos($input, $endstr))*(-1));
	}


  return $substr;
}

// eg: $string = "123456789"; $a = "12"; $b = "9"; echo get_between($string, $a, $b); //Output: //345678
function cds_GetStr($input, $startpos, $endpos)
{

		$substr = substr($input, $startpos, $endpos);


  return $substr;
}

function CDS_ParseItemsToList($TokenList, $Token)
{
  if (trim($TokenList) != '')
  {
	  return explode($Token, $TokenList);
	}
}


//converts an array to text lines and saves in file
function cds_SaveArrayToFile($filename, $arraycontents) {

	$contents = '';

	foreach ($arraycontents as $line) {
			$contents .= $line."\n";
	}

	cds_SaveFile($filename, $contents);

}


function CDS_GetURLValuesArray($url) {

	$MyUrlArray = array();
  $myArray = CDS_ParseItemsToList($url,',');

	foreach ($myArray as $key => $value) {
	  $splitarray = CDS_ParseItemsToList($value,'=');
		$MyUrlArray[$splitarray[0]] = $splitarray[1];
    //echo "$key = $value\n";
	}

	return $MyUrlArray;
}

//function CDS_GetURLValuesArray($url)
//{
//	return cds_GetURLValuesArray($url);
//}

function cds_Fieldbyname($dataset,$rowno,$fieldname){

	return $dataset[$rowno][$fieldname];

}

function cds_GetFileContents($filename) {

	$ch = curl_init();
	$timeout = 5; // set to zero for no timeout
	curl_setopt ($ch, CURLOPT_URL, $filename);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$file_contents = curl_exec($ch);
	curl_close($ch);

return $file_contents;

}

function cds_GetFileContentsFromURL($filename) {
  return cds_GetFileContents($filename);
}

function cds_NewRow($class = "") {

	return '<tr '.$class.'>'."\n";

}

function cds_NewCol($class = "",$value = "", $closecol = TRUE) {

  $html = '  <td '.$class.'>'.$value;
	return ($closecol === TRUE ? $html.'</td>' : $html);

}

function cds_NewHeader($class = "",$value = "", $closecol = TRUE) {

  $html = '  <th '.$class.'>'.$value;
	return ($closecol === TRUE ? $html.'</th>' : $html);

}

function cds_CloseRow() {

	return '</tr>'."\n";

}

function AddRowCell($rowclass = "", $colclass = "",$value = "") {

	$html = cds_NewRow($rowclass);
	$html .= cds_NewCol($colclass,$value, true);
	$html .= cds_CloseRow();

	return $html;

}

function cds_CloseCol($value="") {

	return '  '.$value.'</td>';

}

function cds_CloseHeader($value="") {

	return '  '.$value.'</th>';

}

function cds_NewTable($class = "", $newrow = TRUE, $newrowclass = "") {

  $html = '  <table '.$class.'>'."\n";
	return ($newrow === TRUE ? $html.cds_NewRow($newrowclass) : $html);

}

function cds_CloseTable($closerow = TRUE) {

  $html = '  </table>'."\n";
	return ($closerow === TRUE ? cds_CloseRow(). $html : $html);

}


function cds_SaveFile($filename, $contents) {

	$fp = fopen($filename, 'wb+');

	if ($fp === false) {
			echo "ERROR:".$filename;
	}

	fwrite($fp, $contents);
	fclose($fp);

	return filesize($filename);
}


function cds_AppendFile($filename, $contents, $appendtobottom = false) {

	$filecontents = cds_ReadFile($filename);
	$contents  = $appendtobottom ? $filecontents.$contents : $contents.$filecontents ;

	cds_SaveFile($filename, $contents);

}

function cds_ReadFile($filename) {
	
	if (($filename !='') && file_exists($filename)) {
		$fsize = filesize($filename);
	} else {
		$fsize = 0;
	}
	
	if ($fsize > 0) {
		
		try{		
			$handle = fopen($filename, "rb");	
			if ($handle) {
				clearstatcache();			

				$contents = fread($handle, $fsize);
				fclose($handle);

			} else {
				$contents = '';
			}
		} catch (Exception $e) {
			$contents = '';
		}
	} else {
		$contents = '';
	}	
	
	return $contents;
	
}	


function cds_DeleteFile ( $filename ) {
    // try to force symlinks
    if ( is_link ($filename) ) {
        $sym = @readlink ($filename);
        if ( $sym ) {
            return is_writable ($filename) && @unlink ($filename);
        }
    }

    // try to use real path
    if ( realpath ($filename) && realpath ($filename) !== $filename ) {
        return is_writable ($filename) && @unlink (realpath ($filename));
    }

    // default unlink
    return is_writable ($filename) && @unlink ($filename);
}


function cds_GetCurrentDateTimeStamp() {

	return date('ymd_hms',time());


}

function cds_GetFileDir($filename) {
	return dirname($filename);
}

function cds_GetWebsiteRoot() {
	return $_SERVER['DOCUMENT_ROOT'];
}

function cds_GetWebsiteRootURL() {

	return  (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/';

}

function cds_GetCurrentLocation() {
	return $_SERVER['PATH_INFO'];
}

function cds_ForceDirectories($dir) {

	if (!file_exists($dir)) {
  	  mkdir($dir, 0755, true);
	}

}


// copies a file, and also allows a new filename if provided
function cds_CopyFile($filenameurl,$dirname,$createdir = true, $overwrite = true, $newfilename = ''){

    @$file = fopen ($filenameurl, "rb");
    if (!$file) {
        return false;
    }else {
    	  if ($newfilename != '') {
					$filename = $newfilename;
				} else {
					$filename = basename($filenameurl);
				}

				if (!file_exists($dirname) && $createdir) {
					mkdir($dirname, 0755, true);
				}

				$copyto = $dirname."$filename";

				if (file_exists($copyto) && !$overwrite) {
					return false;
				}

        $fc = fopen($copyto, "wb");
				if ($fc) {
					while (!feof ($file)) {
          	 $line = fread ($file, 1028);
           	fwrite($fc,$line);
        	}
					fclose($fc);
        	return true;
				} else {
					return false;
				}
    }
}



function cds_EscapeStr($str, $dblink = null) {
	return mysqli_real_escape_string($dblink, $str);
}

function cds_SearchAndReplace($SearchString,$SearchWord,$ReplaceWord, $CaseSensitive = false){

  if ($CaseSensitive)
  	return str_replace($SearchWord,$ReplaceWord,$SearchString);
  else
	return str_ireplace($SearchWord,$ReplaceWord,$SearchString);


}

// It works as such:
//$haystack = "The quick brown fox jumps over the lazy dog.";
//$needle = array("fox", "dog", ".", "duck")
//var_dump(multineedle_stripos($haystack, $needle));
/* Output:
   array(3) {
     ["fox"]=>
     int(16)
     ["dog"]=>
     int(40)
     ["."]=>
     int(43)
     ["duck"]=>
     bool(false)
   }
*/
function cds_PosInArray($needles, $haystack, $offset=0) {
    foreach($needles as $needle) {
        $found[$needle] = stripos($haystack, $needle, $offset);
				if ($found[$needle] === false) $found[$needle] = -1;
    }
    return $found;
}


function cds_Pos($haystack, $needle, $offset = 0) {

  $pos = stripos($haystack, $needle, $offset);

  if ($pos === false) return -1;
    else return $pos;

}

function cds_CopyBetweenString($string,$leftsearchstring,$rightsearchstring, $includesearchstring = true) {

	$left  = cds_Pos($string,$leftsearchstring);
	$right = cds_Pos($string,$rightsearchstring);

	if (!$includesearchstring) {
		$left  = $left + strlen($leftsearchstring);
		$right = $right - strlen($rightsearchstring);
	}

	$result = substr($string, $left, $right-$left);

	return $result;

}

function cds_GetDaysInMonth($Month, $Year)
{
   return cal_days_in_month(CAL_GREGORIAN, $Month, $Year);// 31
}

function cds_ShowMessage($message)
{
   echo '<script type="text/javascript">alert("' . $message . '");</script>';
}

/*function to downlaod local or remote files via browser*/
function cds_DownloadFile($file, $name = '', $mime_type='')
{	
	  $isRemoteFile = stripos($file, "http") !== false;	
	  if (!$isRemoteFile) {
		  if(!is_readable($file)) die('File not found or inaccessible!');	
      $size = filesize($file);		  
		}    

    $name = rawurldecode($name);
    $known_mime_types=array(
        "htm" => "text/html",
        "html" => "text/html",
        "exe" => "application/octet-stream",
        "zip" => "application/zip",
        "doc" => "application/msword",
        "docx" => "application/msword",
        "jpg" => "image/jpg",
        "php" => "text/plain",
        "xls" => "application/vnd.ms-excel",
        "xlsx" => "application/vnd.ms-excel",
        "ppt" => "application/vnd.ms-powerpoint",
        "pptx" => "application/vnd.ms-powerpoint",
        "gif" => "image/gif",
        "pdf" => "application/pdf",
        "txt" => "text/plain",
        "html"=> "text/html",
        "png" => "image/png",
        "jpeg"=> "image/jpg"
    );

    if($mime_type==''){
        $file_extension = strtolower(substr(strrchr($file,"."),1));
        if(array_key_exists($file_extension, $known_mime_types)){
            $mime_type=$known_mime_types[$file_extension];
        } else {
            $mime_type="application/force-download";
        };
    };

		if ($name == '') {
		  $name = basename( $file );	  	
		} else {
			$file = $name;
		}

		if ($isRemoteFile) {
			//echo $file;	exit;
		  header("Location: $file");	
		} else {			
			header('Content-Description: File Transfer');
			header('Content-Type: ' . $mime_type);
			header('Content-Disposition: attachment; filename=' . $name);
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . $size);
			ob_clean();
			flush();
			readfile($file);
		}	
		
		exit;	 

}



function GetFileBase64($filename) {

 		$file = $filename;
 		if (($file != '') && (file_exists($file))) {
      $file_size = filesize($file);
      $handle = fopen($file, "r");
      $content = fread($handle, $file_size);
      fclose($handle);
      $content = chunk_split(base64_encode($content));
			$fname = basename($filename);
			$f['name'] = $filename;
			$f['basename'] = $fname;
			$f['content'] = $content;
		} else {
			$f['name'] = '';
			$f['basename'] = '';
			$f['content'] = '';
		}

	return $f;

}

function cds_Email($mailto, $subject, $message, $replyto, $from_mail, $from_name, $filename = '')
{

	$filenames = array();

		if (!is_array($filename)) {
			$filenames[] = $filename;
		} else {
			$filenames = $filename;
		}


    $uid = md5(uniqid(time()));


    $header  = "From: " . $from_name . " <" . $from_mail . ">\r\n";
    $header .= "Reply-To: " . $replyto . "\r\n";
    $header .= "MIME-Version: 1.0\r\n";
    $header .= "Content-type:text/html; charset=iso-8859-1\r\n";

		for($a = 0; $a < count($filenames); $a++){
	 		$file = GetFileBase64($filenames[$a]);
			if ($file['name'] != '') {
	      $header .= "Content-Type: application/octet-stream; name=\"" . $file['basename'] . "\"\r\n";// use different content types here
		    $header .= "Content-Transfer-Encoding: base64\r\n";
	    	$header .= "Content-Disposition: attachment; filename=\"" . $file['basename'] . "\"\r\n\r\n";
	    	$header .= $file['content'] . "\r\n\r\n";
	    	$header .= "--" . $uid . "--";
			}
		}

		if (strpos($email,'@') >= 0) {
			$result = mail($mailto, $subject, $message, $header);
			return $result;
		}	else {
       return false;
    }

}



function cds_EmailAttachment($filename, $path, $mailto, $from_mail, $from_name, $replyto, $subject, $message)
{

   $file = $path . $filename;
   if(file_exists($file))
   {
      $file_size = filesize($file);
      $handle = fopen($file, "r");
      $content = fread($handle, $file_size);
      fclose($handle);
      $content = chunk_split(base64_encode($content));
      $uid = md5(uniqid(time()));
      $name = basename($file);
      $header = "From: " . $from_name . " <" . $from_mail . ">\r\n";
      $header .= "Reply-To: " . $replyto . "\r\n";
      $header .= "MIME-Version: 1.0\r\n";
      $header .= "Content-Type: multipart/mixed; boundary=\"" . $uid . "\"\r\n\r\n";
      $header .= "This is a multi-part message in MIME format.\r\n";
      $header .= "--" . $uid . "\r\n";
      $header .= "Content-type:text/plain; charset=iso-8859-1\r\n";
      $header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
      $header .= $message . "\r\n\r\n";
      $header .= "--" . $uid . "\r\n";
      $header .= "Content-Type: application/octet-stream; name=\"" . $filename . "\"\r\n";// use different content types here
      $header .= "Content-Transfer-Encoding: base64\r\n";
      $header .= "Content-Disposition: attachment; filename=\"" . $filename . "\"\r\n\r\n";
      $header .= $content . "\r\n\r\n";
      $header .= "--" . $uid . "--";
      if(mail($mailto, $subject, "", $header))
      {
         return true;// or use booleans here
      }
      else
      {
         return false;
      }
   }
   else
      return false;
}

function forceDownload($archiveName) {

  if( ! empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS']))
   {
      $uri = 'https://';
   }
   else
   {
      $uri = 'http://';
   }
   $uri .= $_SERVER['HTTP_HOST'];

	$archiveName = $uri."/".$archiveName;

        if(ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }

        // Security checks
        if( $archiveName == "" ) {
            echo "<html><title>Public Photo Directory - Download </title><body><BR><B>ERROR:</B> The download file was NOT SPECIFIED.</body></html>";
            exit;
        }
        elseif ( ! file_exists( $archiveName ) ) {
            echo "<html><title>Public Photo Directory - Download </title><body><BR><B>ERROR:</B> File not found.</body></html>";
						echo $archiveName;
            exit;
        }

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private",false);
        header("Content-Type: application/excel");
        header("Content-Disposition: attachment; filename=".basename($archiveName).";" );
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".filesize($archiveName));
        readfile("$archiveName");
    }



function copyFile($url,$dirname){
    @$file = fopen ($url, "rb");
    if (!$file) {
        echo"<font color=red>Failed to copy $url!</font><br>";
        return false;
    }else {
        $filename = basename($url);
        $fc = fopen($dirname."$filename", "wb");
        while (!feof ($file)) {
           $line = fread ($file, 1028);
           fwrite($fc,$line);
        }
        fclose($fc);
        echo "<font color=blue>File $url saved to PC!</font><br>";
        return true;
    }
}

function force_download($file)
{
    $dir      = "";
    if ((isset($file))&&(file_exists($dir.$file))) {
       header("Content-type: application/force-download");
       header('Content-Disposition: inline; filename="' . $dir.$file . '"');
       header("Content-Transfer-Encoding: Binary");
       header("Content-length: ".filesize($dir.$file));
       header('Content-Type: application/octet-stream');
       header('Content-Disposition: attachment; filename="' . $file . '"');
       readfile("$dir$file");
    } else {
       echo "No file selected";
    } //end if

}//end function

function force_download1($file)
{

// fix for IE catching or PHP bug issue
header("Pragma: public");
header("Expires: 0"); // set expiration time
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
// browser must download file from server instead of cache

// force download dialog
header("Content-Type: application/force-download");
header("Content-Type: application/octet-stream");
header("Content-Type: application/download");

// use the Content-Disposition header to supply a recommended filename and
// force the browser to display the save dialog.
header("Content-Disposition: attachment; filename=".basename($file).";");

/*
The Content-transfer-encoding header should be binary, since the file will be read
directly from the disk and the raw bytes passed to the downloading computer.
The Content-length header is useful to set for downloads. The browser will be able to
show a progress meter as a file downloads. The content-lenght can be determines by
filesize function returns the size of a file.
*/
header("Content-Transfer-Encoding: binary");
header("Content-Length: ".filesize($file));

readfile($file);
exit(0);



}

function cds_GetCurPageURL() {
 $pageURL = 'http';
 if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
 $pageURL .= "://";
 if ($_SERVER["SERVER_PORT"] != "80") {
  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
 } else {
  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
 }
 return $pageURL;
}

function cds_GetCurPageName() {
 return substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1);
}

function cds_GetBaseURL() {

$base = str_replace(
    $_SERVER['DOCUMENT_ROOT'],
    '',
    realpath(dirname(__FILE__)));

	$base = ($base == '') ? '/' : '';

	return $base;

}

function cds_ExtractFilePath($filename) {
	return ($filename !== "" ? dirname($_SERVER['PHP_SELF']) : dirname($filename)) ;
}

// cds_extractfilepath returns path of script, or Returns a parent directory's path, not a filename, hence this function
function cds_ExtractLocalFilePath($filename) {
    $info = pathinfo($filename);
    return $info['dirname'];
}


function cds_ExtractFileName($filename) {
	return basename($filename) ;
}

function cds_ExtractFileExtension($filename) {
	return pathinfo($filename, PATHINFO_EXTENSION);
}

function cds_ChangeFileExtension($filename,$ext) {
    $info = pathinfo($filename);
    return $info['filename'] . '.' . $ext;
}

function cds_CSVtoArray($csvcontents, $headerfirstline = true, $delimiter = ',', $enclosure = '"', $escape = '\\', $terminator = "\n") {

    $r = array();

    if (!is_array($csvcontents)) {
			$rows = explode($terminator,trim($csvcontents));
		} else {
			$rows = $csvcontents;
		}

    // lets deal with the headers
    if ($headerfirstline) {
			$names = array_shift($rows);
    	$names = str_getcsv($names,$delimiter,$enclosure,$escape);
		} else {
			// if it gets here, means there is no first line, so insert column index number instead
			$firstlinecols =  str_getcsv($rows[0],$delimiter,$enclosure,$escape);
			$a = 1;
			$names = array();
			foreach ($firstlinecols as $index) {
				$names[] = $a;
				$a++;
			}
		}

  	$nc = count($names);
    foreach ($rows as $row) {
        if (trim($row)) {
            $values = str_getcsv($row,$delimiter,$enclosure,$escape);
            if (!$values) $values = array_fill(0,$nc,null);
            $r[] = array_combine($names,$values);
        }
    }
    return $r;
}

function cds_SQLResultTable($host, $user, $pass, $db, $Query)
{
    $link = mysqli_connect($host, $user, $pass) or die('Could not connect: ' . mysqli_error());      //build MySQL Link
    mysqli_select_db($db) or die('Could not select database');        //select database
    $Table = "";  //initialize table variable

    $Table.= "<table border='1' style=\"border-collapse: collapse;\">"; //Open HTML Table

    $Result = mysqli_query($Query); //Execute the query
    if(mysqli_error())
    {
        $Table.= "<tr><td>MySQL ERROR: " . mysqli_error() . "</td></tr>";
    }
    else
    {
        //Header Row with Field Names
        $NumFields = mysqli_num_fields($Result);
        $Table.= "<tr style=\"background-color: #ff0000; color: #FFFFFF;\">";
        for ($i=0; $i < $NumFields; $i++)
        {
            $Table.= "<th>" . mysqli_field_name($Result, $i) . "</th>";
        }
        $Table.= "</tr>";

        //Loop thru results
        $RowCt = 0; //Row Counter
        while($Row = mysqli_fetch_assoc($Result))
        {
            //Alternate colors for rows
            if($RowCt++ % 2 == 0) $Style = "background-color: #ffffff;";
            else $Style = "background-color: #ffffff;";

            $Table.= "<tr style=\"$Style\">";
            //Loop thru each field
            foreach($Row as $field => $value)
            {
                $Table.= "<td>$value</td>";
            }
            $Table.= "</tr>";
        }
        $Table.= "<tr style=\"background-color: #ff0000; color: #FFFFFF;\"><td colspan='$NumFields'>Query Returned " . mysqli_num_rows($Result) . " records</td></tr>";
    }
    $Table.= "</table>";

    return $Table;
}


function cds_GetListOfTables($host, $user, $pass, $db)
{
    $link = mysqli_connect($host, $user, $pass) or die('Could not connect: ' . mysqli_error());      //build MySQL Link
    mysqli_select_db($db) or die('Could not select database');        //select database
    $Table = "";  //initialize table variable

    $Result = mysqli_query('show tables from '.$db); //Execute the query
    if(mysqli_error())
    {
        $Table.= "<tr><td>MySQL ERROR: " . mysqli_error() . "</td></tr>";
    }
    else
    {
        while($Row = mysqli_fetch_assoc($Result))
        {
            //Loop thru each field
            foreach($Row as $field => $value)
            {
                $Table.= '<option value="'.$value.'">'.$value.'</option>';
            }
        }
    }

    return $Table;
}

function cds_GetListOfFieldnames($host, $user, $pass, $db, $table)
{
    $link = mysqli_connect($host, $user, $pass) or die('Could not connect: ' . mysqli_error());      //build MySQL Link
    mysqli_select_db($db) or die('Could not select database');        //select database
    $Table = "";  //initialize table variable

    $Result = mysqli_query('show columns from '.$table); //Execute the query
    if(mysqli_error())
    {
        $Table.= "<tr><td>MySQL ERROR: " . mysqli_error() . "</td></tr>";
    }
    else
    {
        while($Row = mysqli_fetch_assoc($Result))
        {
          $Table.= $Row['Field'].'&#10;';
        }
    }

    return $Table;
}


//#ff0000

function cds_UploadFile($file_id, $folder="", $types="", $uniquefilename = false) {

    if(!$_FILES[$file_id]['name']) return array('','No file specified');

    $file_title = $_FILES[$file_id]['name'];
    //Get file extension
    //$ext_arr = preg_split("\.",basename($file_title));
    //$ext = strtolower($ext_arr[count($ext_arr)-1]); //Get the last extension
    
    $ext = cds_ExtractFileExtension($file_title);

    //Not really uniqe - but for all practical reasons, it is
		if ($uniquefilename) {
			$uniqer = substr(md5(uniqid(rand(),1)),0,5). '_';
		} else {
			$uniqer = '';
		}

    $file_name = $uniqer.$file_title;//Get Unique Name

    $all_types = explode(",",strtolower($types));
    if($types) {
        if(in_array($ext,$all_types)) {

				}
        else {
            $result = "'".$_FILES[$file_id]['name']."' is not a valid file."; //Show error if any.
            return array('',$result);
        }
    }

    //Where the file must be uploaded to
    if($folder) $folder .= '/';//Add a '/' at the end of the folder
    $uploadfile = $folder . $file_name;

    $result = '';
    //Move the file from the stored location to the new location
    if (!move_uploaded_file($_FILES[$file_id]['tmp_name'], $uploadfile)) {
        $result = "Cannot upload the file '".$_FILES[$file_id]['name']."'"; //Show error if any.
        if(!file_exists($folder)) {
            $result .= " : Folder don't exist.";
        } elseif(!is_writable($folder)) {
            $result .= " : Folder not writable.";
        } elseif(!is_writable($uploadfile)) {
            $result .= " : File not writable.";
        }
        $file_name = '';

    } else {
        if(!$_FILES[$file_id]['size']) { //Check if the file is made
            @unlink($uploadfile);//Delete the Empty file
            $file_name = '';
            $result = "Empty file found - please use a valid file."; //Show the error message
        } else {
            chmod($uploadfile,0777);//Make it universally writable.
        }
    }

    return array($uploadfile,$result);
}

function cds_DeleteAllFiles($path, $fileext = '', $fileolderthandate = null) {

	if ($fileolderthandate == null) {
		$fileolderthandate = time();
	}

	foreach (new DirectoryIterator($path) as $fileInfo) {
	    if (!$fileInfo->isDot() && ($fileInfo->getMTime() <= $fileolderthandate)) {

				$ext = $fileInfo->getExtension();
				if (($fileext == '') || (strtoupper($ext) == strtoupper($fileext))) {
					unlink($fileInfo->getPathname());
				}

	    }
	}

}

// returns list of files in dir specified, NB: No recursion in subdirs
function cds_GetListOfAllFilesInDir($dir , $mask){

	$files = array();
	$dirfiles = glob($dir.$mask);
	foreach ($dirfiles as $file) {
		if (is_file($file)) {
			$files[] = $file;
		}
	}

	return $files;

}

//moves a file to destination with option of file rename. dest path must have trailing backslash
function cds_MoveFile($sourcefilename, $destinationpath, $forcedirectory = true, $renamefileto ='')	{

	$fname = pathinfo($sourcefilename, PATHINFO_BASENAME);
	$dest = $renamefileto ==  '' ? $destinationpath . $fname : $destinationpath . $renamefileto;

	if ($forcedirectory) {
		cds_ForceDirectories($destinationpath);
	}
	$result = rename($sourcefilename, $dest);

	return $result;

}



#This is a function that will sort an array...
function cds_sortby($array,  $keyname = null, $sortby = 'asc') {
   $myarray = $inarray = array();
   # First store the keyvalues in a seperate array
    foreach ($array as $i => $befree) {
        $myarray[$i] = $array[$i][$keyname];
    }
   # Sort the new array by
    switch ($sortby) {
    case 'asc':
    # Sort an array and maintain index association...
    asort($myarray);
    break;
    case 'arsort':
    # Sort an array in reverse order and maintain index association
    arsort($myarray);
    break;
    case 'natcasesor':
    # Sort an array using a case insensitive "natural order" algorithm
    natcasesort($myarray);
    break;
    }
    # Rebuild the old array
    foreach ( $myarray as $key=> $befree) {
       $inarray[$key] = $array[$key];
    }
    return $inarray;
}


function TextToImage1($text, $fname, $fontsize = 12, $rotation=90, $fontcol = 'black' , $bgcol = 'white' )
{

	// Path to our font file
	$font = 'arial.ttf';
	$fontsize = 12;

	// create a bounding box for the text
	$dims = imagettfbbox($fontsize, 0, $font, $text);
	$height  = 120;
	$width = $fontsize;
	$image = imagecreatetruecolor($width,$height);
	$bgcolor = imagecolorallocate($image, 0, 0, 0);
	$fontcolor = imagecolorallocate($image, 255, 255, 255);
	imagefilledrectangle($image, 0, 0, $width,$height, $bgcolor);
	$x = $fontsize;
	$y = $height;
	imagettftext($image, $fontsize, $rotation, $x, $y, $fontcolor, $font, $text);

	//header('Content-type: image/png');
	imagepng($image,$fname);
	imagedestroy($image);

	return $image;

}

/**
 * Convert a string to a PNG
 *
 * @param string $text The text you want to convert to an image
 * @param string $fname The file name where you want to save the PNG
 * @param int $fontsize [optional] Defaults to size 12
 * @param int $fontangle [optional] Defaults to normal angle (0)
 * @param string $fontcol [optional] The RGB string of the desired font color. Defaults to #000000 (black).
 * @param string $bgcol [optional] The RGB string of the desired background color. Defaults to #FFFFFF (white).
 * @param bool $transparent [optional] Choose to set the background to transparent. Defaults to true.
 *
 * @return void
 */
function cds_TextToImage($text, $fname, $fontsize = 12, $fontangle = 90, $fontcol = '#000000', $bgcol = '#FFFFFF', $transparent = true ) {

	// These hardcoded values will need to be fixed for this function to work
  $imagewidth = 16;
	$imageheight = 150;
	$font = "arial.ttf";
  ////////////////

	$backgroundcolor = $bgcol;
	$textcolor = $fontcol;

  // default these values to prevent issues with undeclared variables.
  $bgred = 255;
  $bggreen = 255;
  $bgblue = 255;
  $textred = 0;
  $textgreen = 0;
  $textblue = 0;

	### Convert HTML backgound color to RGB
	if( preg_match('/([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})/i', $backgroundcolor, $bgrgb) ) {
    $bgred = hexdec( $bgrgb[1] );
    $bggreen = hexdec( $bgrgb[2] );
    $bgblue = hexdec( $bgrgb[3] );
  }

	### Convert HTML text color to RGB
	if( preg_match('/([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})/i', $textcolor, $textrgb) ) {
    $textred = hexdec( $textrgb[1] );
    $textgreen = hexdec( $textrgb[2] );
    $textblue = hexdec( $textrgb[3] );
  }

	### Get exact dimensions of text string
	$box = @imagettfbbox($fontsize, $fontangle, $font, $text);

	### Get width of text from dimensions
	$textwidth = abs($box[4] - $box[0]);

	### Get height of text from dimensions
	$textheight = abs($box[5] - $box[1]);

	### Get x-coordinate of centered text horizontally using length of the image and length of the text
	$xcord = ($imagewidth/2)-($textwidth/2)-2;

	### Get y-coordinate of centered text vertically using height of the image and height of the text
	$ycord = ($imageheight/2)+($textheight/2);

	### Create image
	$im = imagecreate( $imagewidth, $imageheight );

	### Declare image's background color
	$bgcolor = imagecolorallocate($im, $bgred,$bggreen,$bgblue);

	### Declare image's text color
	$fontcolor = imagecolorallocate($im, $textred,$textgreen,$textblue);

	if ($transparent) {
		// transaprent
		$color = imagecolorallocatealpha($im, 0, 0, 0, 127); //fill transparent back
		imagefill($im, 0, 0, $color);
		imagesavealpha($im, true);
	}

	### Declare completed image with colors, font, text, and text location
	imagettftext ( $im, $fontsize, $fontangle, $imagewidth, $imageheight, $fontcolor, $font, $text );

	### Display completed image as PNG
	imagepng($im, $fname);

	### Close the image
	imagedestroy($im);
}

// Helper to send POST requests.
function send_post_request($url,$params) {

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


function cds_GetGet($field,$default) {

  return isset($_GET[$field]) ? $_GET[$field] : $default;

}


function cds_GetPost($field,$default) {

  return isset($_POST[$field]) ? $_POST[$field] : cds_GetGet($field,$default);

}

// Cudos Teoman Soygul http://stackoverflow.com/a/5695202
function cds_SafeFileRewrite($fname, $data)
{
    if ($fp = fopen($fname, 'w'))
    {
        $startTime = microtime(TRUE);
        do {
            $canWrite = flock($fp, LOCK_EX);
            // If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
            if(!$canWrite) usleep(round(rand(0, 100)*1000));
        } while ((!$canWrite)and((microtime(TRUE)-$startTime) < 5));

        //file was locked so now we can store information
        if ($canWrite)
        {
            fwrite($fp, $data);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }
}
/*
 * $fname string ini filename to write to
 * $array associative array to convert to ini format
 */
function cds_WriteIni($fname, $array)
{
    $res = array();
    foreach($array as $key => $val)
    {
        if(is_array($val))
        {
            $res[] = "[$key]";
            foreach($val as $skey => $sval) $res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
        }
        else $res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
    }
    cds_SafeFileRewrite($fname, implode("\r\n", $res));
}

// update single value in ini file
function cds_UpdateIni($fname, $psection, $pkey, $pvalue)
{
	$array = parse_ini_file($fname, true);
	$array[$psection][$pkey] = $pvalue;
	cds_WriteIni($fname, $array);
}

function cds_sendIcalEvent($from_name, $from_address, $to_name, $to_address, $startTime, $endTime, $subject, $description, $location, $uid)
{
    $domain = $uid;

    //Create Email Headers
    $mime_boundary = "----Meeting Booking----".MD5(TIME());

    $headers = "From: ".$from_name." <".$from_address.">\n";
    $headers .= "Reply-To: ".$from_name." <".$from_address.">\n";
    $headers .= "MIME-Version: 1.0\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$mime_boundary\"\n";
    $headers .= "Content-class: urn:content-classes:calendarmessage\n";

    //Create Email Body (HTML)
    $message = "--$mime_boundary\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\n";
    $message .= "Content-Transfer-Encoding: 8bit\n\n";
    $message .= "<html>\n";
    $message .= "<body>\n";
    $message .= '<p>Dear '.$to_name.',</p>';
    $message .= '<p>'.$description.'</p>';
    $message .= "</body>\n";
    $message .= "</html>\n";
    $message .= "--$mime_boundary\r\n";

    $ical = 'BEGIN:VCALENDAR' . "\r\n" .
    'PRODID:-//Microsoft Corporation//Outlook 10.0 MIMEDIR//EN' . "\r\n" .
    'VERSION:2.0' . "\r\n" .
    'METHOD:REQUEST' . "\r\n" .
    'BEGIN:VTIMEZONE' . "\r\n" .
    'TZID:Eastern Time' . "\r\n" .
    'BEGIN:STANDARD' . "\r\n" .
    'DTSTART:20091101T020000' . "\r\n" .
    'RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=1SU;BYMONTH=11' . "\r\n" .
    'TZOFFSETFROM:-0400' . "\r\n" .
    'TZOFFSETTO:-0500' . "\r\n" .
    'TZNAME:EST' . "\r\n" .
    'END:STANDARD' . "\r\n" .
    'BEGIN:DAYLIGHT' . "\r\n" .
    'DTSTART:20090301T020000' . "\r\n" .
    'RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=2SU;BYMONTH=3' . "\r\n" .
    'TZOFFSETFROM:-0500' . "\r\n" .
    'TZOFFSETTO:-0400' . "\r\n" .
    'TZNAME:EDST' . "\r\n" .
    'END:DAYLIGHT' . "\r\n" .
    'END:VTIMEZONE' . "\r\n" .
    'BEGIN:VEVENT' . "\r\n" .
    'ORGANIZER;CN="'.$from_name.'":MAILTO:'.$from_address. "\r\n" .
    'ATTENDEE;CN="'.$to_name.'";ROLE=REQ-PARTICIPANT;RSVP=TRUE:MAILTO:'.$to_address. "\r\n" .
    'LAST-MODIFIED:' . date("Ymd\TGis") . "\r\n" .
    'UID:'.$domain."\r\n" .
    'DTSTAMP:'.date("Ymd\TGis"). "\r\n" .
    'DTSTART;TZID="Eastern Time":'.date("Ymd\THis", strtotime($startTime)). "\r\n" .
    'DTEND;TZID="Eastern Time":'.date("Ymd\THis", strtotime($endTime)). "\r\n" .
    'TRANSP:OPAQUE'. "\r\n" .
    'SEQUENCE:1'. "\r\n" .
    'SUMMARY:' . $subject . "\r\n" .
    'LOCATION:' . $location . "\r\n" .
    'CLASS:PUBLIC'. "\r\n" .
    'PRIORITY:5'. "\r\n" .
    'BEGIN:VALARM' . "\r\n" .
    'TRIGGER:-PT15M' . "\r\n" .
    'ACTION:DISPLAY' . "\r\n" .
    'DESCRIPTION:Reminder' . "\r\n" .
    'END:VALARM' . "\r\n" .
    'END:VEVENT'. "\r\n" .
    'END:VCALENDAR'. "\r\n";
    $message .= 'Content-Type: text/calendar;name="meeting.ics";method=REQUEST'."\n";
    $message .= "Content-Transfer-Encoding: 8bit\n\n";
    $message .= $ical;

    $mailsent = mail($to_address, $subject, $message, $headers);

    return ($mailsent)?(true):(false);
}

?>
