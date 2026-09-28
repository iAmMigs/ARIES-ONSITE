<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=aries_db;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$res = $pdo->query("DESCRIBE bed_requirements")->fetchAll(PDO::FETCH_ASSOC);
echo "bed_requirements:\n";
foreach($res as $r) echo $r['Field'] . "\n";

echo "\naudit_bed_requirements:\n";
$res2 = $pdo->query("DESCRIBE audit_bed_requirements")->fetchAll(PDO::FETCH_ASSOC);
foreach($res2 as $r) echo $r['Field'] . "\n";

echo "\nTriggers:\n";
$res3 = $pdo->query("SHOW TRIGGERS LIKE 'bed_requirements'")->fetchAll(PDO::FETCH_ASSOC);
foreach($res3 as $r) {
    echo $r['Trigger'] . "\n" . $r['Statement'] . "\n\n";
}
unlink(__FILE__);
