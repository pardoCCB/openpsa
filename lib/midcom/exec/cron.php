<?php
$ip_sudo = midcom::get()->auth->require_admin_or_ip('midcom.services.indexer');
midcom::get()->header('Content-Type: text/plain');

// Ensure cron doesn't timeout
@ini_set('max_execution_time', 0);

// Determine recurrence
$recurrence = MIDCOM_CRON_MINUTE;
if (isset($_GET['type'])) {
    if ($_GET['type'] == 'hour') {
        $recurrence = MIDCOM_CRON_HOUR;
    } elseif ($_GET['type'] == 'day') {
        $recurrence = MIDCOM_CRON_DAY;
    }
}

// Instantiate cron service and run
$cron = new midcom_services_cron($recurrence);
$cron->execute();

if ($ip_sudo) {
    midcom::get()->auth->drop_sudo();
}
