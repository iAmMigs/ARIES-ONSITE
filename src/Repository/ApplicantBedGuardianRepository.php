<?php

declare(strict_types=1);

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

    public function findByStudentNumber(string $studentNumber): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.applicant = :studentNumber')
            ->setParameter('studentNumber', $studentNumber)
            ->getQuery()
            ->getResult();
    }
}