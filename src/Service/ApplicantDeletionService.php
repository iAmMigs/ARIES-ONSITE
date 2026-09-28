<?php

declare(strict_types=1);

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

        // Remove the applicant's profile ID picture from the public filesystem if it exists.
        if ($applicant->getPhotoSlug()) {
            $photoPath = $publicDir . $applicant->getPhotoSlug();
            if ($fs->exists($photoPath)) {
                $fs->remove($photoPath);
            }
        }

        /*
         * Delete Requirement Documents
         * This operation relies on the mapped 'requirements' collection inside ApplicantBed
         * to traverse and safely remove physical files from the storage path.
         */
        foreach ($applicant->getRequirements() as $req) {
            if ($req->getStoredFileName()) {
                $docPath = $publicDir . $req->getStoredFileName();
                if ($fs->exists($docPath)) {
                    $fs->remove($docPath);
                }
            }
        }

        /*
         * Delete Database Record
         * The Doctrine ORM's cascade=['remove'] mapping automatically executes deletion for:
         * Guardians, Siblings, Schools, and Database Requirement Tracking rows.
         */
        $this->em->remove($applicant);
        $this->em->flush();
    }
}