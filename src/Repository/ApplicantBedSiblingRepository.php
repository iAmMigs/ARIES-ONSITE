<?php
namespace App\Repository;
use App\Entity\ApplicantBedSibling;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class ApplicantBedSiblingRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, ApplicantBedSibling::class); }
}