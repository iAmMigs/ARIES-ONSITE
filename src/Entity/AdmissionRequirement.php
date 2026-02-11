<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class AdmissionRequirement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'requirements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StudentProfile $studentProfile = null;

    #[ORM\Column(length: 50)]
    private ?string $documentType = null;

    #[ORM\Column(length: 255)]
    private ?string $filePath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null; // Matches CSV "slug"

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateSubmitted = null;

    public function getId(): ?int { return $this->id; }

    // THIS IS THE MISSING METHOD CAUSING THE ERROR
    public function getStudentProfile(): ?StudentProfile { return $this->studentProfile; }

    public function setStudentProfile(?StudentProfile $studentProfile): static { $this->studentProfile = $studentProfile; return $this; }
    
    public function getDocumentType(): ?string { return $this->documentType; }
    public function setDocumentType(string $documentType): static { $this->documentType = $documentType; return $this; }
    
    public function getFilePath(): ?string { return $this->filePath; }
    public function setFilePath(string $filePath): static { $this->filePath = $filePath; return $this; }

    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(?string $slug): static { $this->slug = $slug; return $this; }

    public function getDateSubmitted(): ?\DateTimeInterface { return $this->dateSubmitted; }
    public function setDateSubmitted(?\DateTimeInterface $d): static { $this->dateSubmitted = $d; return $this; }
}