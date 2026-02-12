<?php
namespace App\Repository;
use App\Entity\ApplicantBedRequirement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class ApplicantBedRequirementRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, ApplicantBedRequirement::class); }
}