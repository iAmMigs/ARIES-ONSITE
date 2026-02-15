<?php

namespace App\Repository;

use App\Entity\ApplicantBedSibling;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApplicantBedSibling>
 */
class ApplicantBedSiblingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApplicantBedSibling::class);
    }

    public function findByAdCon(string $adCon): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.applicant = :adCon')
            ->setParameter('adCon', $adCon)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count siblings by applicant
     */
    public function countByApplicant(string $adCon): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.SiblingName)') // FIXED: Count by part of the Composite Key
            ->where('s.applicant = :adCon')
            ->setParameter('adCon', $adCon)
            ->getQuery()
            ->getSingleScalarResult();
    }
}