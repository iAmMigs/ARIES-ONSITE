<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class StudentSchool
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'previousSchools')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StudentProfile $studentProfile = null;

    #[ORM\Column(length: 1, nullable: true)]
    private ?string $levelType = null; // P, I, S, T, V

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $schoolName = null;

    // --- Address ---
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

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $yearStart = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $yearEnd = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $degree = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $strand = null;

    // --- Getters & Setters ---
    public function getId(): ?int { return $this->id; }
    
    // THIS IS THE MISSING METHOD CAUSING THE ERROR
    public function getStudentProfile(): ?StudentProfile { return $this->studentProfile; }
    
    public function setStudentProfile(?StudentProfile $p): static { $this->studentProfile = $p; return $this; }
    
    public function setSchoolName(?string $v): static { $this->schoolName = $v; return $this; }
    public function getSchoolName(): ?string { return $this->schoolName; }
    
    public function setLevelType(?string $v): static { $this->levelType = $v; return $this; }
    public function getLevelType(): ?string { return $this->levelType; }
    
    public function setYearStart(?string $v): static { $this->yearStart = $v; return $this; }
    public function getYearStart(): ?string { return $this->yearStart; }
    
    public function setYearEnd(?string $v): static { $this->yearEnd = $v; return $this; }
    public function getYearEnd(): ?string { return $this->yearEnd; }
    
    // ... add getters for address/degree if needed later, setters usually enough for input
    public function setDegree(?string $v): static { $this->degree = $v; return $this; }
    public function setStrand(?string $v): static { $this->strand = $v; return $this; }
    
    // Simplified setter for "level" mapped to levelType for simplicity
    public function setLevel(?string $v): static { $this->levelType = $v; return $this; }
    public function getLevel(): ?string { return $this->levelType; }
}