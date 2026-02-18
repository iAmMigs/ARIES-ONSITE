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

    public function findByStudentNumber(string $studentNumber): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.applicant = :studentNumber')
            ->setParameter('studentNumber', $studentNumber)
            ->getQuery()
            ->getResult();
    }

    public function countByApplicant(string $studentNumber): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.SiblingName)')
            ->where('s.applicant = :studentNumber')
            ->setParameter('studentNumber', $studentNumber)
            ->getQuery()
            ->getSingleScalarResult();
    }
}