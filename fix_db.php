<?php
require_once __DIR__ . '/vendor/autoload.php';

$kernel = new App\Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

$conn = $em->getConnection();
$conn->executeStatement("UPDATE school_year SET enrollment_open = 1 WHERE campus = 'FALAB'");

echo "Updated enrollment_open for FALAB.\n";
