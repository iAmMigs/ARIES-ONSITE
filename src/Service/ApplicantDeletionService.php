<?php

namespace App\Service;

use App\Entity\ApplicantBed;
use App\Repository\ApplicantBedRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

class ApplicantDeletionService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ApplicantBedRepository $repository,
        #[Autowire('%kernel.project_dir%')] private string $projectDir
    ) {}

    /**
     * Deletes an applicant by AdCon (Admission Control Number)
     */
    public function deleteByAdCon(string $adCon): void
    {
        $applicant = $this->repository->findOneBy(['adCon' => $adCon]);
        
        if ($applicant) {
            $this->deleteApplicant($applicant);
        }
    }

    /**
     * Main function: Deletes files and database records for an applicant
     */
    public function deleteApplicant(ApplicantBed $applicant): void
    {
        $fs = new Filesystem();
        $publicDir = $this->projectDir . '/public/';

        // 1. Delete ID Picture
        if ($applicant->getPhotoSlug()) {
            $photoPath = $publicDir . $applicant->getPhotoSlug();
            if ($fs->exists($photoPath)) {
                $fs->remove($photoPath);
            }
        }

        // 2. Delete Requirement Documents
        // This relies on the 'requirements' relationship in ApplicantBed
        foreach ($applicant->getRequirements() as $req) {
            if ($req->getStoredFileName()) {
                $docPath = $publicDir . $req->getStoredFileName();
                if ($fs->exists($docPath)) {
                    $fs->remove($docPath);
                }
            }
        }

        // 3. Delete Database Record
        // Doctrine cascade=['remove'] will automatically delete:
        // - Guardians
        // - Siblings
        // - Schools
        // - Requirements (Database rows)
        $this->em->remove($applicant);
        $this->em->flush();
    }
}