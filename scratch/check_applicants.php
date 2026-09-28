<?php

use App\Entity\ApplicantBed;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

require __DIR__.'/../vendor/autoload.php';

(new \Symfony\Component\Dotenv\Dotenv())->load(__DIR__.'/../.env');

$kernel = new \App\Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();
$repo = $em->getRepository(ApplicantBed::class);

$applicants = $repo->findBy(['studentNumber' => ['202550001', '202550002', '202550003', '202650001', '202650002']]);

echo "RECENT APPLICANTS:\n";
foreach ($applicants as $a) {
    echo sprintf(
        "ID: %s | Name: %s %s | Campus: %s | Level: %s | Type: %s | SY: %s | Created: %s\n",
        $a->getStudentNumber(),
        $a->getFirstName(),
        $a->getLastName(),
        $a->getCampus(),
        $a->getGradeLevel(),
        $a->getAdmissionType(),
        $a->getSchoolYearOfEntry(),
        $a->getCreatedAt()->format('Y-m-d H:i:s')
    );
}
