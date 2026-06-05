<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ApplicantBedRequirementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ApplicantBedRequirementRepository::class)]
#[ORM\Table(name: 'bed_requirements')]
#[ORM\HasLifecycleCallbacks]
class ApplicantBedRequirement
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: ApplicantBed::class, inversedBy: 'requirements')]
    #[ORM\JoinColumn(name: 'student_number', referencedColumnName: 'student_number', nullable: false, onDelete: 'CASCADE')]
    private ?ApplicantBed $applicant = null;

    #[ORM\Id]
    #[ORM\Column(name: 'Slug', type: Types::STRING, length: 100)]
    private ?string $Slug = null;

    #[ORM\Column(name: 'Requirement', type: Types::STRING, length: 100)]
    private ?string $Requirement = null;

    #[ORM\Column(name: 'StoredFileName', type: Types::STRING, length: 255, nullable: true)]
    private ?string $StoredFileName = null;

    /**
     * Virtual property to validate form upload data.
     * Enforces the maximum upload limit prior to checking dynamic extensions.
     */
    #[Assert\File(
        maxSize: '10M',
        maxSizeMessage: 'The document file size exceeds the maximum allowed limit of 10MB.'
    )]
    private ?File $documentFile = null;

    #[ORM\Column(name: 'Status', type: Types::STRING, length: 1, options: ['default' => 'P'])]
    private string $Status = 'P';

    #[ORM\Column(name: 'IsDeleted', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $IsDeleted = false;

    #[ORM\Column(name: 'DateSubmitted', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $DateSubmitted = null;

    public function getApplicant(): ?ApplicantBed { return $this->applicant; }
    public function setApplicant(?ApplicantBed $applicant): static { $this->applicant = $applicant; return $this; }
    
    public function getSlug(): ?string { return $this->Slug; }
    public function setSlug(string $Slug): static { $this->Slug = $Slug; return $this; }
    
    public function getRequirement(): ?string { return $this->Requirement; }
    public function setRequirement(string $Requirement): static { $this->Requirement = $Requirement; return $this; }
    public function getStoredFileName(): ?string { return $this->StoredFileName; }
    public function setStoredFileName(?string $StoredFileName): static { $this->StoredFileName = $StoredFileName; return $this; }
    
    public function getDocumentFile(): ?File { return $this->documentFile; }
    public function setDocumentFile(?File $documentFile): static { $this->documentFile = $documentFile; return $this; }

    public function getStatus(): string { return $this->Status; }
    public function setStatus(string $Status): static { $this->Status = $Status; return $this; }
    public function getDateSubmitted(): ?\DateTimeInterface { return $this->DateSubmitted; }
    public function setDateSubmitted(?\DateTimeInterface $DateSubmitted): static { $this->DateSubmitted = $DateSubmitted; return $this; }
    public function isDeleted(): bool { return $this->IsDeleted; }
    public function setIsDeleted(bool $IsDeleted): static { $this->IsDeleted = $IsDeleted; return $this; }
}