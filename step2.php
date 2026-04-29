<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "PHP: " . phpversion() . "<br>";
echo "date: " . date('Y-m-d H:i:s') . "<br>";

// PDO 확장 확인
echo "PDO: " . (extension_loaded('pdo') ? 'OK' : 'MISSING') . "<br>";
echo "PDO_MySQL: " . (extension_loaded('pdo_mysql') ? 'OK' : 'MISSING') . "<br>";
echo "mbstring: " . (extension_loaded('mbstring') ? 'OK' : 'MISSING') . "<br>";
echo "session: " . (extension_loaded('session') ? 'OK' : 'MISSING') . "<br>";

echo "<br>모든 확장 OK이면 step3.php 로 이동하세요";
