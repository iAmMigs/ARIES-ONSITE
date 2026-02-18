<?php

namespace App\Repository;

use App\Entity\ApplicantBedSchool;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApplicantBedSchool>
 */
class ApplicantBedSchoolRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApplicantBedSchool::class);
    }

    public function findByStudentNumber(string $studentNumber): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.applicant = :studentNumber')
            ->setParameter('studentNumber', $studentNumber)
            ->getQuery()
            ->getResult();
    }
}