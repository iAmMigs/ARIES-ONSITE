<?php

namespace App\Command;

use App\Entity\ApplicantBed;
use App\Entity\ApplicantBedGuardian;
use App\Entity\ApplicantBedSchool;
use App\Entity\ApplicantBedSibling;
// use App\Entity\ApplicantBedRequirement; // Not used as requested
use App\Service\StudentIdGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:populate-alabang',
    description: 'Creates 3 complete dummy applicants for FEU Alabang',
)]
class PopulateAlabangCommand extends Command
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
        $campus = ApplicantBed::CAMPUS_ALABANG;
        $yearStart = date('Y');

        $firstNames = ['James', 'Mary', 'John', 'Patricia', 'Robert', 'Jennifer', 'Michael', 'Linda'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis'];
        $strands = ['STEM', 'ABM', 'HUMSS', 'GAS', 'ICT'];

        for ($i = 0; $i < 3; $i++) {
            $applicant = new ApplicantBed();
            $studentNo = $this->idGenerator->generateStudentNumber('feu_alabang', $yearStart);
            
            $applicant->setStudentNumber($studentNo);
            $applicant->setCampus($campus);
            $applicant->setAdmissionStatus(ApplicantBed::STATUS_PENDING);
            $applicant->setAdmissionDate(new \DateTime());
            $applicant->setCreatedAt(new \DateTimeImmutable());

            $applicant->setEducationType('Secondary');
            $applicant->setGradeLevel($i % 2 == 0 ? 'grade_11' : 'grade_12');
            $applicant->setTrackStrand($strands[array_rand($strands)]);
            $applicant->setLrn('100' . rand(100000000, 999999999));
            $applicant->setSchoolYearOfEntry($yearStart . '-' . ($yearStart + 1));

            $fName = $firstNames[array_rand($firstNames)];
            $lName = $lastNames[array_rand($lastNames)];
            $applicant->setFirstName($fName);
            $applicant->setLastName($lName);
            $applicant->setMiddleName('A.');
            $applicant->setGender($i % 2 == 0 ? 'Male' : 'Female');
            $applicant->setBirthDate(new \DateTime('-17 years'));
            $applicant->setBirthPlace('Muntinlupa City');
            $applicant->setReligion('Catholic');
            
            // --- Random Metronic Avatar (300-1.png to 300-34.png) ---
            $avatarId = rand(1, 34);
            $applicant->setPhotoSlug('metronic/media/avatars/300-' . $avatarId . '.png');
            
            // --- Logic: Citizenship and Visa Fields ---
            if ($i === 2) {
                // Make the 3rd applicant a Foreigner
                $applicant->setCitizenship('Foreign');
                $applicant->setPassportNumber('P' . rand(1000000, 9999999));
                $applicant->setVisaType('student');
                $applicant->setVisaStatus('Active');
            } else {
                // Make others Filipino
                $applicant->setCitizenship('Filipino');
                $applicant->setIndigenousGroup($i === 1 ? 'Aeta' : null);
            }
            
            $applicant->setMobileNumber('09' . rand(100000000, 999999999));
            $applicant->setPersonalEmail(strtolower($fName . '.' . $lName . '@test.com'));

            $applicant->setCurrentRegion('National Capital Region (NCR)');
            $applicant->setCurrentProvince('Metro Manila');
            $applicant->setCurrentCity('Muntinlupa City');
            $applicant->setCurrentBarangay('Alabang');
            $applicant->setCurrentAddress('123 Commerce Avenue');
            $applicant->setCurrentZip('1781');
            
            $applicant->setPermanentRegion($applicant->getCurrentRegion());
            $applicant->setPermanentProvince($applicant->getCurrentProvince());
            $applicant->setPermanentCity($applicant->getCurrentCity());
            $applicant->setPermanentBarangay($applicant->getCurrentBarangay());
            $applicant->setPermanentAddress($applicant->getCurrentAddress());
            $applicant->setPermanentZip($applicant->getCurrentZip());
            $applicant->setAdmissionType(rand(0, 1) ? 'Freshman' : 'Transferee');

            $this->addGuardian($applicant, 'Father', 'Smith, Pedro', 'Engineer');
            $this->addGuardian($applicant, 'Mother', 'Santos, Maria', 'Accountant');

            $numSiblings = rand(1, 2);
            for ($s = 0; $s < $numSiblings; $s++) {
                $sibling = new ApplicantBedSibling();
                $sibling->setApplicant($applicant);
                $sibling->setSiblingName($lName . ', Sibling ' . ($s + 1));
                $sibling->setSchool('FEU Alabang');
                $sibling->setFeuStudentNo('2024000' . $s);
                $sibling->setIsFeuStudent(true);
                $this->em->persist($sibling);
                $applicant->addSibling($sibling);
            }

            // --- DOCUMENTS: SKIPPED (As requested, using NULL for now) ---
            // The dynamic DocumentSetup will handle requirements on the edit page.

            $school = new ApplicantBedSchool();
            $school->setApplicant($applicant);
            $school->setSchool('De La Salle Zobel');
            $school->setYearEnd($yearStart - 1);
            $school->setLevel('S'); 
            $this->em->persist($school);
            $applicant->addSchool($school);

            $this->em->persist($applicant);
            $this->em->flush(); 
            
            $io->writeln("Created: $fName $lName ($studentNo)");
        }

        $io->success('3 Alabang Applicants Created with Random Avatars.');
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