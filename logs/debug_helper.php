<?php
function debug_log($message) {
    $file = __DIR__ . '/debug.txt';
    $time = date("Y-m-d H:i:s");

    $log = "[" . $time . "] " . $message . PHP_EOL;
    file_put_contents($file, $log, FILE_APPEND);
}
