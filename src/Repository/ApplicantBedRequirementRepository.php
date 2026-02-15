<?php

namespace App\Repository;

use App\Entity\ApplicantBedRequirement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApplicantBedRequirement>
 */
class ApplicantBedRequirementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApplicantBedRequirement::class);
    }

    public function findByAdCon(string $adCon): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.applicant = :adCon')
            ->setParameter('adCon', $adCon)
            ->getQuery()
            ->getResult();
    }

    public function getStatusStats(string $adCon): array
    {
        $results = $this->createQueryBuilder('r')
            ->select('r.Status, COUNT(r.Slug) as count') // FIXED: Count by Slug
            ->where('r.applicant = :adCon')
            ->setParameter('adCon', $adCon)
            ->groupBy('r.Status')
            ->getQuery()
            ->getResult();

        $stats = [
            'total' => 0,
            'pending' => 0,
            'submitted' => 0,
            'verified' => 0,
            'rejected' => 0,
            'waived' => 0
        ];

        foreach ($results as $row) {
            $count = (int) $row['count'];
            $stats['total'] += $count;
            
            match($row['Status']) {
                'P' => $stats['pending'] = $count,
                'S' => $stats['submitted'] = $count,
                'V' => $stats['verified'] = $count,
                'R' => $stats['rejected'] = $count,
                'W' => $stats['waived'] = $count,
                default => null
            };
        }

        return $stats;
    }

    public function areAllRequiredVerified(string $adCon): bool
    {
        $unverified = $this->createQueryBuilder('r')
            ->select('COUNT(r.Slug)') // FIXED: Count by Slug
            ->where('r.applicant = :adCon')
            ->andWhere('r.IsRequired = :required')
            ->andWhere('r.Status NOT IN (:verified)')
            ->setParameter('adCon', $adCon)
            ->setParameter('required', true)
            ->setParameter('verified', ['V', 'W'])
            ->getQuery()
            ->getSingleScalarResult();

        return $unverified == 0;
    }
}