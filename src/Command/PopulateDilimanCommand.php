<?php

namespace App\Command;

use App\Entity\ApplicantBed;
use App\Entity\ApplicantBedGuardian;
use App\Entity\ApplicantBedSchool;
use App\Entity\ApplicantBedSibling;
use App\Entity\ApplicantBedRequirement;
use App\Service\StudentIdGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:populate-diliman',
    description: 'Creates 3 complete dummy applicants for FEU Diliman',
)]
class PopulateDilimanCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private StudentIdGenerator $idGenerator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $campus = ApplicantBed::CAMPUS_DILIMAN;
        $yearStart = date('Y');

        $firstNames = ['David', 'Sarah', 'Joseph', 'Karen', 'Thomas', 'Nancy'];
        $lastNames = ['Anderson', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee'];
        
        $levels = [
            ['type' => 'Primary', 'grade' => 'kinder', 'strand' => null],
            ['type' => 'Secondary', 'grade' => 'grade_7', 'strand' => null],
            ['type' => 'Secondary', 'grade' => 'grade_12', 'strand' => 'STEM'],
        ];

        for ($i = 0; $i < 3; $i++) {
            $applicant = new ApplicantBed();
            $studentNo = $this->idGenerator->generateStudentNumber('feu_diliman', $yearStart);
            
            $applicant->setStudentNumber($studentNo);
            $applicant->setCampus($campus);
            $applicant->setAdmissionStatus('Pending');
            $applicant->setEnrollmentStep(1);
            $applicant->setAdmissionDate(new \DateTime());
            $applicant->setCreatedAt(new \DateTimeImmutable());

            $levelData = $levels[$i];
            $applicant->setEducationType($levelData['type']);
            $applicant->setGradeLevel($levelData['grade']);
            $applicant->setTrackStrand($levelData['strand']);
            $applicant->setLrn('100' . rand(100000000, 999999999));
            $applicant->setSchoolYearOfEntry($yearStart . '-' . ($yearStart + 1));

            $fName = $firstNames[array_rand($firstNames)];
            $lName = $lastNames[array_rand($lastNames)];
            $applicant->setFirstName($fName);
            $applicant->setLastName($lName);
            $applicant->setMiddleName('B.');
            $applicant->setGender($i % 2 == 0 ? 'Male' : 'Female');
            $applicant->setBirthDate(new \DateTime('-12 years'));
            $applicant->setBirthPlace('Quezon City');
            $applicant->setReligion('Christian');
            $applicant->setCitizenship('Filipino');
            
            $applicant->setMobileNumber('09' . rand(100000000, 999999999));
            $applicant->setPersonalEmail(strtolower($fName . '.' . $lName . '@test.com'));

            $applicant->setCurrentRegion('National Capital Region (NCR)');
            $applicant->setCurrentProvince('Metro Manila');
            $applicant->setCurrentCity('Quezon City');
            $applicant->setCurrentBarangay('Diliman');
            $applicant->setCurrentAddress('45 Commonwealth Ave');
            $applicant->setCurrentZip('1101');
            
            $applicant->setPermanentRegion($applicant->getCurrentRegion());
            $applicant->setPermanentProvince($applicant->getCurrentProvince());
            $applicant->setPermanentCity($applicant->getCurrentCity());
            $applicant->setPermanentBarangay($applicant->getCurrentBarangay());
            $applicant->setPermanentAddress($applicant->getCurrentAddress());
            $applicant->setPermanentZip($applicant->getCurrentZip());
            $applicant->setAdmissionType(rand(0, 1) ? 'Freshman' : 'Transferee');

            $this->addGuardian($applicant, 'Mother', 'Reyes, Anna', 'Nurse');
            $this->addGuardian($applicant, 'Father', 'Smith, Pedro', 'Engineer');

            $sibling = new ApplicantBedSibling();
            $sibling->setApplicant($applicant);
            $sibling->setSiblingName($lName . ', Brother');
            $sibling->setSchool('UP Diliman');
            $this->em->persist($sibling);
            $applicant->addSibling($sibling);

            $docs = ['PSA Birth Certificate', 'Report Card'];
            foreach ($docs as $docName) {
                $req = new ApplicantBedRequirement();
                $req->setApplicant($applicant);
                $req->setRequirement($docName);
                // FIXED: Setting the Slug
                $req->setSlug(strtolower(str_replace(' ', '-', $docName)));
                $req->setStoredFileName('uploads/dummy.pdf');
                $req->setStatus('S');
                $req->setDateSubmitted(new \DateTime());
                $req->setIsRequired(true);
                $this->em->persist($req);
                $applicant->addRequirement($req);
            }

            $school = new ApplicantBedSchool();
            $school->setApplicant($applicant);
            $school->setSchool('Ateneo Grade School');
            $school->setYearEnd($yearStart - 1);
            $school->setLevel('P');
            $this->em->persist($school);
            $applicant->addSchool($school);

            $this->em->persist($applicant);
            $this->em->flush();
            
            $io->writeln("Created: $fName $lName ($studentNo)");
        }

        $io->success('3 Diliman Applicants Created.');
        return Command::SUCCESS;
    }

    private function addGuardian($applicant, $rel, $name, $job) {
        $g = new ApplicantBedGuardian();
        $g->setApplicant($applicant);
        $g->setRelationship($rel);
        $g->setParentName($name);
        $g->setOccupation($job);
        $g->setContactNo('09' . rand(100000000, 999999999));
        $this->em->persist($g);
        $applicant->addGuardian($g);
    }
}