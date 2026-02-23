<?php
session_start();
session_destroy();
// Redirect to login.php in the same directory as this file
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$thisDir = rtrim(str_replace('\\', '/', dirname(__FILE__)), '/');
$basePath = str_replace($docRoot, '', $thisDir);
header("Location: " . $basePath . "/login.php");
exit;
