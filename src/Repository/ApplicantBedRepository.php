<?php

namespace App\Repository;

use App\Entity\ApplicantBed;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApplicantBed>
 */
class ApplicantBedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApplicantBed::class);
    }

    public function findLatestForGeneration(string $campusCode, string $prefix): ?ApplicantBed
    {
        return $this->createQueryBuilder('a')
            ->where('a.studentNumber LIKE :prefix')
            ->andWhere('a.campus = :campus')
            ->setParameter('prefix', $prefix . '%')
            ->setParameter('campus', $campusCode)
            ->orderBy('a.studentNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}