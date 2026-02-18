<?php

namespace App\Entity;

use App\Repository\ApplicantBedGuardianRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicantBedGuardianRepository::class)]
#[ORM\Table(name: 'bed_applicant_guardians')]
class ApplicantBedGuardian
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'guardians')]
    #[ORM\JoinColumn(name: 'student_number', referencedColumnName: 'student_number', nullable: false, onDelete: 'CASCADE')]
    private ?ApplicantBed $applicant = null;

    // --- OLD FIELD (Deprecating, but kept for safety if needed temporarily) ---
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $parentName = null;

    // --- NEW SPLIT FIELDS ---
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 50)]
    private ?string $relationship = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $occupation = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $contactNo = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $deceased = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $OFW = false;

    public function getId(): ?int { return $this->id; }

    public function getApplicant(): ?ApplicantBed { return $this->applicant; }
    public function setApplicant(?ApplicantBed $applicant): static { $this->applicant = $applicant; return $this; }

    // --- Modified Getter to Combine Names ---
    public function getParentName(): ?string 
    { 
        if ($this->firstName || $this->lastName) {
            return trim($this->firstName . ' ' . $this->lastName);
        }
        return $this->parentName; 
    }
    
    // Kept for backward compatibility if needed, but we will use specific setters primarily
    public function setParentName(?string $name): static { 
        $this->parentName = $name; 
        return $this; 
    }

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(?string $n): static { $this->firstName = $n; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(?string $n): static { $this->lastName = $n; return $this; }

    public function getRelationship(): ?string { return $this->relationship; }
    public function setRelationship(string $r): static { $this->relationship = $r; return $this; }

    public function getOccupation(): ?string { return $this->occupation; }
    public function setOccupation(?string $o): static { $this->occupation = $o; return $this; }

    public function getContactNo(): ?string { return $this->contactNo; }
    public function setContactNo(?string $c): static { $this->contactNo = $c; return $this; }

    public function isDeceased(): bool { return $this->deceased; }
    public function setDeceased(bool $d): static { $this->deceased = $d; return $this; }

    public function isOFW(): bool { return $this->OFW; }
    public function setOFW(bool $o): static { $this->OFW = $o; return $this; }
}