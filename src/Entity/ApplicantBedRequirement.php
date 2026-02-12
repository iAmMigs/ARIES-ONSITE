<?php

namespace App\Entity;

use App\Repository\ApplicantBedRequirementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicantBedRequirementRepository::class)]
#[ORM\Table(name: 'bed_applicant_requirements')]
class ApplicantBedRequirement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'requirements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ApplicantBed $applicant = null;

    #[ORM\Column(length: 255)]
    private ?string $adCon = null;

    #[ORM\Column(length: 255)]
    private ?string $requirement = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $storedFileName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateSubmitted = null;

    #[ORM\Column]
    private ?bool $isRequired = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplicant(): ?ApplicantBed
    {
        return $this->applicant;
    }

    public function setApplicant(?ApplicantBed $applicant): static
    {
        $this->applicant = $applicant;

        return $this;
    }

    public function getAdCon(): ?string
    {
        return $this->adCon;
    }

    public function setAdCon(string $adCon): static
    {
        $this->adCon = $adCon;

        return $this;
    }

    public function getRequirement(): ?string
    {
        return $this->requirement;
    }

    public function setRequirement(string $requirement): static
    {
        $this->requirement = $requirement;

        return $this;
    }

    // --- FIX: Added Missing Getter ---
    public function getStoredFileName(): ?string
    {
        return $this->storedFileName;
    }

    public function setStoredFileName(?string $storedFileName): static
    {
        $this->storedFileName = $storedFileName;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getDateSubmitted(): ?\DateTimeInterface
    {
        return $this->dateSubmitted;
    }

    public function setDateSubmitted(?\DateTimeInterface $dateSubmitted): static
    {
        $this->dateSubmitted = $dateSubmitted;

        return $this;
    }

    public function isIsRequired(): ?bool
    {
        return $this->isRequired;
    }

    public function setIsRequired(bool $isRequired): static
    {
        $this->isRequired = $isRequired;

        return $this;
    }
}