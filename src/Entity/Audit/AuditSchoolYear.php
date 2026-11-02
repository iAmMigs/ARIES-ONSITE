<?php

declare(strict_types=1);

namespace App\Entity\Audit;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'audit_school_year')]
class AuditSchoolYear
{
    use AuditFieldsTrait;

    #[ORM\Column(name: 'school_year_id', type: Types::INTEGER, nullable: false)]
    private ?int $schoolYearId = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(name: 'year_start', type: Types::INTEGER, nullable: true)]
    private ?int $yearStart = null;

    #[ORM\Column(name: 'year_end', type: Types::INTEGER, nullable: true)]
    private ?int $yearEnd = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $campus = null;

    #[ORM\Column(name: 'old_is_active', type: Types::BOOLEAN, nullable: true)]
    private ?bool $oldIsActive = null;

    #[ORM\Column(name: 'is_active', type: Types::BOOLEAN, nullable: true)]
    private ?bool $isActive = null;

    #[ORM\Column(name: 'old_enrollment_open', type: Types::BOOLEAN, nullable: true)]
    private ?bool $oldEnrollmentOpen = null;

    #[ORM\Column(name: 'enrollment_open', type: Types::BOOLEAN, nullable: true)]
    private ?bool $enrollmentOpen = null;

    #[ORM\Column(name: 'old_promissory_deadline', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $oldPromissoryDeadline = null;

    #[ORM\Column(name: 'promissory_deadline', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $promissoryDeadline = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    public function getSchoolYearId(): ?int { return $this->schoolYearId; }
    public function getLabel(): ?string { return $this->label; }
    public function getYearStart(): ?int { return $this->yearStart; }
    public function getYearEnd(): ?int { return $this->yearEnd; }
    public function getCampus(): ?string { return $this->campus; }
    public function getOldIsActive(): ?bool { return $this->oldIsActive; }
    public function isActive(): ?bool { return $this->isActive; }
    public function getOldEnrollmentOpen(): ?bool { return $this->oldEnrollmentOpen; }
    public function isEnrollmentOpen(): ?bool { return $this->enrollmentOpen; }
    public function getOldPromissoryDeadline(): ?\DateTimeInterface { return $this->oldPromissoryDeadline; }
    public function getPromissoryDeadline(): ?\DateTimeInterface { return $this->promissoryDeadline; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
}

