<?php

namespace App\Repository;

use App\Entity\LookupCity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LookupCity>
 */
class LookupCityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LookupCity::class);
    }

    public function findByProvince(int $provinceCode): array
    {
        $results = $this->createQueryBuilder('c')
            ->where('c.provinceCode = :provinceCode')
            ->setParameter('provinceCode', $provinceCode)
            ->getQuery()
            ->getResult();
        
        usort($results, fn($a, $b) => strnatcasecmp($a->getCityDesc() ?? '', $b->getCityDesc() ?? ''));
        return $results;
    }
}