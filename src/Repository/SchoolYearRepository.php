<?php

namespace App\Repository;

use App\Entity\SchoolYear;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SchoolYear>
 */
class SchoolYearRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SchoolYear::class);
    }

    /**
     * Returns the currently active school year for a given campus, or null if none is set.
     * Used by enrollment gating and student ID generation to determine the correct school year.
     */
    public function findActiveByCampus(string $campus): ?SchoolYear
    {
        return $this->findOneBy(['campus' => $campus, 'isActive' => true]);
    }

    /**
     * Returns all school years for a given campus, ordered most recent first.
     *
     * @return SchoolYear[]
     */
    public function findByCampusOrdered(string $campus): array
    {
        return $this->createQueryBuilder('sy')
            ->where('sy.campus = :campus')
            ->setParameter('campus', $campus)
            ->orderBy('sy.yearStart', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Deactivates all school years for the given campus.
     * Called before activating a new one to enforce the single-active constraint.
     */
    public function deactivateAllForCampus(string $campus): void
    {
        $this->createQueryBuilder('sy')
            ->update()
            ->set('sy.isActive', ':false')
            ->set('sy.enrollmentOpen', ':false')
            ->where('sy.campus = :campus')
            ->setParameter('false', false)
            ->setParameter('campus', $campus)
            ->getQuery()
            ->execute();
    }
}
