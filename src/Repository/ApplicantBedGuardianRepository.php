<?php
namespace App\Repository;
use App\Entity\ApplicantBedGuardian;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class ApplicantBedGuardianRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, ApplicantBedGuardian::class); }
}