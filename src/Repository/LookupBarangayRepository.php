<?php

namespace App\Repository;

use App\Entity\LookupBarangay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LookupBarangay>
 */
class LookupBarangayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LookupBarangay::class);
    }

    public function findByCityCode(int $cityCode): array
    {
        $results = $this->createQueryBuilder('b')
            ->where('b.cityCode = :cityCode')
            ->setParameter('cityCode', $cityCode)
            ->getQuery()
            ->getResult();
        
        usort($results, fn($a, $b) => strnatcasecmp($a->getBarangayDesc() ?? '', $b->getBarangayDesc() ?? ''));
        return $results;
    }
}