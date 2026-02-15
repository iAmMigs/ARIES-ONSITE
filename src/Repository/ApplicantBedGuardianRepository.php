<?php

namespace App\Repository;

use App\Entity\ApplicantBedGuardian;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApplicantBedGuardian>
 */
class ApplicantBedGuardianRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApplicantBedGuardian::class);
    }

    public function findByApplicant(string $adCon): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.applicant = :adCon')
            ->setParameter('adCon', $adCon)
            ->getQuery()
            ->getResult();
    }
}