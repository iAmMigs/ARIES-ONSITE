<?php
$logFile = __DIR__ . '/../var/log/dev.log';
if (!file_exists($logFile)) {
    echo "Log file does not exist at $logFile\n";
    exit(1);
}

$lines = file($logFile);
$count = count($lines);
echo "Total lines in log: $count\n";

$lastLines = array_slice($lines, max(0, $count - 200));
foreach ($lastLines as $line) {
    if (strpos($line, 'ERROR') !== false || strpos($line, 'CRITICAL') !== false || strpos($line, 'Exception') !== false || strpos($line, 'failed') !== false) {
        echo $line;
    } else {
        // Output a shortened version of other logs if needed, or just everything.
        echo $line;
    }
}
