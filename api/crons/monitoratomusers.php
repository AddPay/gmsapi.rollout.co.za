<?php

/**
 * I wrote this script to monitor whether there is an issue with
 * syncing with the ATOM Persons table.
 *
 * Usually if the sync is stuck, it means that there is some issue with
 * the payload it is trying to insert into the ATOM server - especialy due to
 * validation errors.
 *
 * Two examples of validation errors are
 * - Access_Group_List must have at least one value in the array
 * - PersonStateID, PersonTypeID, and DepartmentID must be valid foreign keys
 *
 */

require_once('../config.gymsync.php');
require_once('../cdslib/cdsutils.php');
require_once('../cdslib/cds.mysqli.class.php');

// the max number of users that are allowed to be in an edited state
$limit = 3;

$db = new MySQL(true, $database, $host, $username, $password);

$sql = "select * from v_atomusers WHERE useredited = 1";

$users = $db->QueryArray($sql);

if (is_array($users) && count($users) > $limit) {

    $count = count($users);
    $guilty_user = $users[0];

    $guilt_user_string = print_r($guilty_user, true);

    $message = "
        $count users are stuck in useredited state. Please run <pre>select * from v_atomusers WHERE useredited = 1</pre> to check. Check for potential ATOM API validation errors. <br><br>
        Guilty user:<br>
        <pre>$guilt_user_string</pre>
    ";

    $values = [
        "date_time" => date("Y-m-d H:i:s"),
        "to" => "richard.slabbert@addpay.africa",
        "subject" => "ATOM Persons Sync Stuck",
        "message" => $message,
        "fromname" => "Maties Gym",
        "fromemail" => "emailer@gms.matiesgym.co.za",
        "message_type" => "email",
    ];

    $insert_values = cds_QuoteArrayValues($values, $db);

    $db->InsertRow("email_outbox", $insert_values);
}
