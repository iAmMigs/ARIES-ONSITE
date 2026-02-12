<?php

namespace App\Entity;

use App\Repository\ApplicantBedRequirementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicantBedRequirementRepository::class)]
#[ORM\Table(name: 'bed_requirements')]
class ApplicantBedRequirement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ApplicantBed::class, inversedBy: 'requirements')]
    #[ORM\JoinColumn(name: 'applicant_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?ApplicantBed $applicant = null;

    #[ORM\Column(name: 'ad_con', type: 'string', length: 15, nullable: true)]
    private ?string $adCon = null;

    #[ORM\Column(name: 'Requirement', type: 'string', length: 255)]
    private ?string $Requirement = null;

    #[ORM\Column(name: 'StoredFileName', type: 'string', length: 255, nullable: true)]
    private ?string $StoredFileName = null;

    #[ORM\Column(name: 'Slug', type: 'string', length: 255, nullable: true)]
    private ?string $Slug = null;

    #[ORM\Column(name: 'Status', type: 'string', length: 1)]
    private string $Status = 'P';

    #[ORM\Column(name: 'DateSubmitted', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $DateSubmitted = null;

    #[ORM\Column(name: 'IsRequired', type: 'boolean', options: ['default' => false])]
    private bool $IsRequired = false;

    public function setApplicant(?ApplicantBed $a): static { $this->applicant = $a; return $this; }
    public function setAdCon(?string $v): static { $this->adCon = $v; return $this; }
    public function setRequirement(string $v): static { $this->Requirement = $v; return $this; }
    public function setStoredFileName(?string $v): static { $this->StoredFileName = $v; return $this; }
    public function setSlug(?string $v): static { $this->Slug = $v; return $this; }
    public function setStatus(string $v): static { $this->Status = $v; return $this; }
    public function setDateSubmitted(?\DateTimeInterface $v): static { $this->DateSubmitted = $v; return $this; }
    public function setIsRequired(bool $v): static { $this->IsRequired = $v; return $this; }
}