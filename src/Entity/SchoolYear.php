<?php

namespace App\Entity;

use App\Repository\SchoolYearRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents an academic school year for a specific campus.
 *
 * Each campus (Alabang, Diliman) manages its own school years independently.
 * At most one SchoolYear per campus may be active at any given time.
 * The enrollment form is only accessible to students when the active school year
 * also has enrollmentOpen set to true.
 */
#[ORM\Entity(repositoryClass: SchoolYearRepository::class)]
#[ORM\Table(name: 'school_year')]
#[ORM\HasLifecycleCallbacks]
class SchoolYear
{
    public const CAMPUS_DILIMAN = 'FDIL';
    public const CAMPUS_ALABANG = 'FALAB';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Human-readable label for the school year, e.g. "SY2526".
     * Derived from yearStart and yearEnd during creation.
     */
    #[ORM\Column(length: 10)]
    private ?string $label = null;

    /**
     * The starting calendar year of this school year (e.g. 2025 for SY2526).
     * Used as the first 4 digits of generated student IDs.
     */
    #[ORM\Column]
    private ?int $yearStart = null;

    /**
     * The ending calendar year of this school year (e.g. 2026 for SY2526).
     */
    #[ORM\Column]
    private ?int $yearEnd = null;

    /**
     * The campus this school year belongs to. Uses the same campus codes
     * as ApplicantBed: FALAB for Alabang, FDIL for Diliman.
     */
    #[ORM\Column(length: 10)]
    private ?string $campus = null;

    /**
     * Whether this is the currently active school year for its campus.
     * Only one school year per campus can be active at a time.
     * Activating a new school year automatically deactivates all others for that campus.
     */
    #[ORM\Column(options: ['default' => false])]
    private bool $isActive = false;

    /**
     * Whether the public enrollment form is currently open for this school year.
     * Enrollment can only be opened for the active school year.
     */
    #[ORM\Column(options: ['default' => false])]
    private bool $enrollmentOpen = false;
    
    /**
     * The deadline for submitting missing documents for this school year.
     * Only relevant for Alabang campus for now, but available for both.
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $promissoryDeadline = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getLabel(): ?string { return $this->label; }
    public function setLabel(string $label): static { $this->label = $label; return $this; }

    public function getYearStart(): ?int { return $this->yearStart; }
    public function setYearStart(int $yearStart): static { $this->yearStart = $yearStart; return $this; }

    public function getYearEnd(): ?int { return $this->yearEnd; }
    public function setYearEnd(int $yearEnd): static { $this->yearEnd = $yearEnd; return $this; }

    public function getCampus(): ?string { return $this->campus; }
    public function setCampus(string $campus): static { $this->campus = $campus; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }

    public function isEnrollmentOpen(): bool { return $this->enrollmentOpen; }
    public function setEnrollmentOpen(bool $enrollmentOpen): static { $this->enrollmentOpen = $enrollmentOpen; return $this; }

    public function getPromissoryDeadline(): ?\DateTimeInterface { return $this->promissoryDeadline; }
    public function setPromissoryDeadline(?\DateTimeInterface $promissoryDeadline): static { $this->promissoryDeadline = $promissoryDeadline; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    /**
     * Returns the full display label for the school year, e.g. "SY2526 (2025–2026)".
     */
    public function getDisplayLabel(): string
    {
        return $this->label . ' (' . $this->yearStart . '–' . $this->yearEnd . ')';
    }
}
