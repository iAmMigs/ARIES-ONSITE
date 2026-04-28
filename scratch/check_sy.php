<?php

use App\Entity\SchoolYear;
require __DIR__.'/../vendor/autoload.php';
(new \Symfony\Component\Dotenv\Dotenv())->load(__DIR__.'/../.env');

$kernel = new \App\Kernel('dev', true);
$kernel->boot();

$em = $kernel->getContainer()->get('doctrine')->getManager();
$syRepo = $em->getRepository(SchoolYear::class);

$sys = $syRepo->findAll();
echo "SCHOOL YEARS:\n";
foreach ($sys as $sy) {
    echo sprintf(
        "ID: %d | Label: %s | Campus: %s | Active: %s | Open: %s\n",
        $sy->getId(),
        $sy->getLabel(),
        $sy->getCampus(),
        $sy->isActive() ? 'YES' : 'NO',
        $sy->isEnrollmentOpen() ? 'YES' : 'NO'
    );
}
