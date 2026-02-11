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
    private ?string $documentType = null; // PSA, CARD, MORAL

    #[ORM\Column(length: 255)]
    private ?string $filePath = null;

    public function getId(): ?int { return $this->id; }
    public function setStudentProfile(?StudentProfile $studentProfile): static { $this->studentProfile = $studentProfile; return $this; }
    
    public function getDocumentType(): ?string { return $this->documentType; }
    public function setDocumentType(string $documentType): static { $this->documentType = $documentType; return $this; }
    
    public function getFilePath(): ?string { return $this->filePath; }
    public function setFilePath(string $filePath): static { $this->filePath = $filePath; return $this; }
}