<?php

namespace App\Entity;

use App\Repository\StudentParentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudentParentRepository::class)]
class StudentParent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'parents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StudentProfile $studentProfile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    private ?string $relationship = null; 

    // --- Address & Contact ---
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $country = null;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $region = null;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $province = null;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $city = null;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $barangay = null;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $zip = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $landlineNumber = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $mobileNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $occupation = null;

    // --- Status Flags ---
    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isDeceased = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isOFW = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isEmergencyContact = null;

    public function getId(): ?int { return $this->id; }
    
    // THIS IS THE MISSING METHOD CAUSING THE ERROR
    public function getStudentProfile(): ?StudentProfile { return $this->studentProfile; }

    public function setStudentProfile(?StudentProfile $p): static { $this->studentProfile = $p; return $this; }
    
    public function getName(): ?string { return $this->name; }
    public function setName(?string $v): static { $this->name = $v; return $this; }
    
    public function getRelationship(): ?string { return $this->relationship; }
    public function setRelationship(string $v): static { $this->relationship = $v; return $this; }
    
    public function getOccupation(): ?string { return $this->occupation; }
    public function setOccupation(?string $v): static { $this->occupation = $v; return $this; }
    
    public function getMobileNumber(): ?string { return $this->mobileNumber; }
    public function setMobileNumber(?string $v): static { $this->mobileNumber = $v; return $this; }
    // Alias for legacy controller calls
    public function setContactNumber(?string $v): static { $this->mobileNumber = $v; return $this; }
    public function getContactNumber(): ?string { return $this->mobileNumber; }

    public function setLandlineNumber(?string $v): static { $this->landlineNumber = $v; return $this; }
    public function setEmail(?string $v): static { $this->email = $v; return $this; }
    
    public function setIsDeceased(?bool $v): static { $this->isDeceased = $v; return $this; }
    public function setIsOFW(?bool $v): static { $this->isOFW = $v; return $this; }
    public function setIsEmergencyContact(?bool $v): static { $this->isEmergencyContact = $v; return $this; }
}