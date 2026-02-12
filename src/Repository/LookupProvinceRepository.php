<?php

namespace App\Repository;

use App\Entity\LookupProvince;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LookupProvince>
 */
class LookupProvinceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LookupProvince::class);
    }

    public function findByRegion(int $regionCode): array
    {
        $results = $this->createQueryBuilder('p')
            ->where('p.regionCode = :regionCode')
            ->setParameter('regionCode', $regionCode)
            ->getQuery()
            ->getResult();
        
        usort($results, fn($a, $b) => strnatcasecmp($a->getProvinceDesc() ?? '', $b->getProvinceDesc() ?? ''));
        return $results;
    }
}