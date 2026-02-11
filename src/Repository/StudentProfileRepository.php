<?php

namespace App\Repository;

use App\Entity\StudentProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentProfile>
 */
class StudentProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentProfile::class);
    }

    /**
     * Find the latest student to help determine the next ID series.
     * Filter by Campus, Year, and Type (BED = 5).
     */
    public function findLatestForGeneration(string $campus, string $yearStart, string $typeCode): ?StudentProfile
    {
        return $this->createQueryBuilder('s')
            ->where('s.campus = :campus')
            ->andWhere('s.studentNumber LIKE :prefix')
            ->setParameter('campus', $campus)
            ->setParameter('prefix', $yearStart . $typeCode . '%')
            ->orderBy('s.studentNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}