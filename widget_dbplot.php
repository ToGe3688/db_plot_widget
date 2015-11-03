<?php

// ----------------------------------------------------------
// 
// SmartVISU widget for database plots with highcharts
// (c) Tobias Geier 2015
// 
// Version: 0.3
// License: GNU General Public License v2.0
// 
// Manual: https://github.com/ToGe3688/db_plot_widget
// 
// ----------------------------------------------------------


/* * ****************************************
 * *****************CONFIG*******************
 * **************************************** */

// Edit the dbConnect to match your DB Log settings of fhem
// For SQLite define the path to your db in your filesystem
// DB Type, use 'sqlite' or 'mysql'
$dbType = 'sqlite';

// SQLite
$dbPath = '/opt/fhem/fhem.db';

// MySQL
$host = 'localhost';
$mysql_username = '';
$mysql_password = '';
$database = '';

/* * ****************************************
  It is not needed to change any settings below if you use this widget with FHEM DbLog
 * **************************************** */

$timestampColumn = 'TIMESTAMP';
$valueColumn = 'VALUE';
$unitColumn = 'UNIT';
$readingColumn = 'READING';
$deviceColumn = 'DEVICE';
$logTable = 'history';

/* * ****************************************
 * ***************ENDCONFIG******************
 * **************************************** */

// Set the JSON header
header("content-type: application/json");

// If one of POST values is missing return Error
if (!isset($_POST['query']) || $_POST['query'] == "") {
    returnError("Missing or empty query value in POST request");
} else {
    $query = $_POST['query'];
}
if (!isset($_POST['timeRangeStart']) || $_POST['timeRangeStart'] == "" || !is_numeric($_POST['timeRangeStart'])) {
    returnError("Missing, wrong or empty time range start value in POST request");
} else {
    $timestampStart = $_POST['timeRangeStart'];
}
if (!isset($_POST['timeRangeEnd']) || $_POST['timeRangeEnd'] == "" || !is_numeric($_POST['timeRangeEnd'])) {
    returnError("Missing, wrong or empty time range end value in POST request");
} else {
    $timestampEnd = $_POST['timeRangeEnd'];
}

$maxCount = (isset($_POST['maxRows'])) ? $_POST['maxRows'] : 300;

// Decode JSON for query from POST Request and basic validation for request query
$requestedDeviceReadings = json_decode($query);
if (!is_array($requestedDeviceReadings) && count($requestedDeviceReadings) == 0) {
    returnError("plotOptions is no array, wrong formed or empty");
}

// Create new PDO Object for DB Connection
if ($dbType == 'sqlite') {
    $db = new PDO('sqlite:' . $dbPath);
} elseif ($dbType == 'mysql') {
    $db = new PDO('mysql:host=' . $host . ';' . $database, $mysql_username, $mysql_password);
    $db->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
}


// Get requested series for the plot
foreach ($requestedDeviceReadings as $i => $deviceReading) {

    // Prepare plot array
    $plotArray = array();

    // Execute DB Query for device reading
    $resultArray = getData($deviceReading->device, $deviceReading->reading, $timestampStart, $timestampEnd, $maxCount, $db);
    $stmt = $resultArray;

    // Loop through fetched data from db and push values to plot array
    for ($a = 0; $row = $stmt->fetch(PDO::FETCH_ASSOC); $a++) {

        // Convert datetime from timestamp column to unix timestamp or set to current time if update request 
        $timestamp = strtotime($row['TIMESTAMP']) * 1000;
        $item = array($timestamp, floatval($row['VALUE']));
        array_push($plotArray, $item);
    }

    // Fill return array with Options for plot
    foreach ($deviceReading->config as $key => $value) {
        $returnArray[$i][$key] = $value;
        // Check if unit for readings is allready set
        $unitSet = ($key == 'unit') ? true : false;
    }
    // If unit is not set use unit value from db
    $returnArray[$i]['unit'] = (!$unitSet) ? $row['UNIT'] : $returnArray[$i]['unit'];

    // Reverse and set plot array 
    $returnArray[$i]['data'] = array_reverse($plotArray);
    $i++;
}

// Close DB Connection
unset($db);

// Return the array and encode to JSON
echo json_encode($returnArray);


/* * ****************************************
 * ***************FUNCTIONS******************
 * **************************************** */

// Query function to get data from FHEM dbLog Database
function getData($device, $reading, $timestampStart, $timestampEnd, $maxCount, $db) {

    global $timestampColumn;
    global $valueColumn;
    global $unitColumn;
    global $readingColumn;
    global $deviceColumn;
    global $logTable;

    $datetimeStart = date('Y-m-d H:i:s', $timestampStart);
    $datetimeEnd = date('Y-m-d H:i:s', $timestampEnd);

    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbQuery = 'SELECT ' . $timestampColumn . ', ' . $valueColumn . ', ' . $unitColumn . ' FROM ' . $logTable . ' WHERE ' . $deviceColumn . '=:device AND ' . $readingColumn . '=:reading AND ' . $timestampColumn . ' BETWEEN :timeRangeStart AND :timeRangeEnd ORDER BY ' . $timestampColumn . ' DESC LIMIT 0,:count';

    // Execute query and return fetched rows
    $fetchedRows = executeDbQuery($db, $dbQuery, $device, $reading, $datetimeStart, $datetimeEnd, $maxCount);
    return $fetchedRows;
}

// Execute DB query 
function executeDbQuery($db, $query, $device, $reading, $timestampStart, $timestampEnd, $maxCount) {

    try {
        $stmt = $db->prepare($query);
        $stmt->bindValue(':device', $device, PDO::PARAM_STR);
        $stmt->bindValue(':reading', $reading, PDO::PARAM_STR);
        $stmt->bindValue(':timeRangeStart', $timestampStart);
        $stmt->bindValue(':timeRangeEnd', $timestampEnd);
        $stmt->bindValue(':count', $maxCount);
        $stmt->execute();
    } catch (PDOException $pe) {
        returnError($pe->getMessage());
    }
    return $stmt;
}

// Return script errors as JSON Data to display them in SmartVISU
function returnError($error) {
    $errorReturn = array('error' => '[dbPlot.widget]: ' . $error);
    echo json_encode($errorReturn);
    exit;
}
