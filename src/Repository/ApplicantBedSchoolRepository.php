<?php
namespace App\Repository;
use App\Entity\ApplicantBedSchool;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class ApplicantBedSchoolRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, ApplicantBedSchool::class); }
}