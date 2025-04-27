<?php
$logFile = 'logs/test.log';
$result = file_put_contents($logFile, "Test " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
var_dump($result);
echo "Error: " . print_r(error_get_last(), true);
?>