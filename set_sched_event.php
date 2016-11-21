<?php
/**
 * PLUGIN NAME: set_sched_event.php
 * DESCRIPTION: Finds the date (and time if available) of scheduled events for a project or for a record in a project 
*               and sets the value of a datetime field with that information
 * VERSION:     1.0
 * AUTHOR:      Sue Lowry - University of Minnesota
 */

// Call the REDCap Connect file in the main "redcap" directory
require_once "../redcap_connect.php";

require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$err_style="<h3 style='color:#800000;padding:20px;'>";

// HTML page content goes here
print '<h3 style="color:#800000;">';
print '        Records for which '.$_GET['field'].' is being updated';
print  " &nbsp; &nbsp; &nbsp; <button class='jqbuttonmed' onclick='window.print();'><img src='".APP_PATH_IMAGES."printer.png' class='imgfix'> Print page</button> <br /><br />";
print '</h3>';

if (!isset($_GET['pid']) ) {
        die( $err_style."The project id is required.</h3>" );
}
if (!isset($_GET['field']) ) {
        die( $err_style."The event datetime field's variable name is required.</h3>" );
}
$fld = $_GET['field'];
$record_where = '';
if (isset($_GET['record']) ) {
        $record_where = ' AND rec.record = ' . $_GET['record'];
}

$sql = "SELECT rm.field_name FROM redcap_metadata rm where rm.field_name = '$fld' and rm.project_id = $project_id";
$q = db_query( $sql );
if ( ! $q ) { die( "Could not execute SQL: <pre>$sql</pre> <br />" .  db_error() ); }
if ( db_num_rows($q) == 0 ) { die( $err_style."There are no field with a variable name of $fld in this project.</h3>" ); }
?>

        <style type="text/css">
                table {border-collapse:collapse;}
                table.ReportTableWithBorder {
                  border-right:none;
                  border-left:none;
                  border-right:1pt solid black;
                  border-bottom:1pt solid black;
                  font-size:17px;
                  font-size:12px;
                  font-family:helvetica,arial,sans-serif;
                }
                .ReportTableWithBorder th,
                .ReportTableWithBorder td {
                  border-top: none;
                  border-left: none;
                  border-top: 1pt solid black;
                  border-left: 1pt solid black;
                  padding: 4px 5px;
                  font-weight:normal;
                }
        </style>
<?php
// build the sql statement to find the data
$sql = "
        SELECT rec.project_id, rec.event_id, rec.record, rec.event_date, rec.event_time, rem.descrip,
               CONCAT(rec.event_date, ' ', CASE WHEN rec.event_time > ' ' THEN rec.event_time ELSE '00:00' END) AS event_date_time
          FROM redcap_events_calendar rec, redcap_events_forms ref, redcap_metadata rm, redcap_events_metadata rem
         WHERE rec.project_id = $project_id $record_where
            AND ref.event_id = rec.event_id
            AND rm.field_name = '$fld'
            AND rm.form_name = ref.form_name
            AND rem.event_id = rec.event_id
            AND not exists (SELECT 'x' 
                              FROM redcap_data rd 
                             WHERE rd.project_id = 1807
                               AND rd.event_id = rec.event_id
                               AND rd.record = rec.record 
                               AND rd.field_name = '$fld'
                               AND rd.value = CONCAT(rec.event_date, ' ', CASE WHEN rec.event_time > ' ' THEN rec.event_time ELSE '00:00' END))
         ORDER BY rec.record, rec.event_id";
#print "sql: $sql<br/>";

// execute the sql statement
$events_result = db_query( $sql );
if ( ! $events_result )  // sql failed
{
        die( "Could not execute SQL: <pre>$sql</pre> <br />" .  db_error() );
}

if ( db_num_rows($events_result) == 0 )
{
        die( $err_style."There are no scheduled events that need to be updated.</h3>" );
}

//Render table headers
print  "<div style='padding:15px 10px 0 5px;color:#000000;'>";
list($date_label, $dow_label) = explode('/',$lang['scheduling_53']);
print  "<table class='form_border' id='edit_sched_table'>
                <tr>
                        <td class='label_header' style='background-color:#eee;padding:8px;width:55px;'>".strip_tags(label_decode($table_pk_label))."</td>
                        <td class='label_header' style='background-color:#eee;padding:8px;width:100px;'>{$lang['global_10']}</td>
                        <td class='label_header' style='background-color:#eee;width:85px;padding:8px 3px;'>".trim($date_label)."<span class='df'>".trim(DateTimeRC::get_user_format_label())."</span></td>
                        <td class='label_header' style='background-color:#eee;padding:8px;'>{$lang['global_13']}</td>
                </tr>";

    $data = array();

while ($row = db_fetch_assoc($events_result)) {
        // Set up variables (date, day of week, etc.)
        $this_event_date = DateTimeRC::format_ts_from_ymd($row['event_date']);
        $this_record = $row['record'];
        $this_event_id = $row['event_id'];
        $this_descrip = $row['descrip'];

        ## Render table rows
        print  "<tr id='row_{$row['cal_id']}' evstat='{$row['event_status']}'>
                                <td class='data' style='padding:3px 8px;'>{$this_record}</td>
                                <td class='data' style='padding:3px 8px;'>{$this_descrip}</td>
                                <td class='data' style='padding:0 0 0 8px;'>$this_event_date</td>
                                <td class='data' style='padding:0px 4px;'>".DateTimeRC::format_ts_from_ymd($row['event_time'])."</td>
                        </tr>";

        $data[$this_record][$this_event_id] = array( $fld => $row['event_date_time'] );
}
print  "</table>";

#foreach ($data as $rec => $arr) { print("For update: rec: $rec:<br/>"); foreach ($arr as $event => $flds) { print("event: $event<br/>"); foreach ($flds as $fld => $val) { print("fld: $fld, val: $val<br/>"); }}}
$response = REDCap::saveData($project_id, 'array', $data);
if (count($response['errors'])   > 0) { print("Errors: ".$response['errors']) ; foreach ($response['errors'] as $msg) { print($msg); } }
if (count($response['warnings']) > 0) { print("Warnings: ".$response['warnings']) ; foreach ($response['warnings'] as $msg) { print($msg); } }
print( "<br/>We just set $fld in {$response['item_count']} records");

// Display the footer
include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
