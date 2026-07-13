<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

$kernel = new App\Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();
$conn = $em->getConnection();

$rows = $conn->fetchAllAssociative("SELECT marketing_source, COUNT(*) as count FROM bed_applicants GROUP BY marketing_source");

$result = [
    'time' => date('Y-m-d H:i:s'),
    'data' => $rows
];

file_put_contents(dirname(__DIR__) . '/inspect_results.json', json_encode($result, JSON_PRETTY_PRINT));

echo "<h1>Marketing Sources in bed_applicants</h1>";
echo "<table border='1'><tr><th>Marketing Source</th><th>Count</th></tr>";
foreach ($rows as $row) {
    echo "<tr><td>" . htmlspecialchars($row['marketing_source'] ?? 'NULL') . "</td><td>" . $row['count'] . "</td></tr>";
}
echo "</table>";
