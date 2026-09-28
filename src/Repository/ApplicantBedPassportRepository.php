<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ApplicantBedPassport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApplicantBedPassport>
 */
class ApplicantBedPassportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApplicantBedPassport::class);
    }
}
