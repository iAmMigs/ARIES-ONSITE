<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class StudentSibling
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'siblings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StudentProfile $studentProfile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $occupation = null; 

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $schoolName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $studentNumber = null;

    // Legacy field to prevent errors if controller sets it
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $age = null;

    public function getId(): ?int { return $this->id; }

    // THIS IS THE MISSING METHOD CAUSING THE ERROR
    public function getStudentProfile(): ?StudentProfile { return $this->studentProfile; }

    public function setStudentProfile(?StudentProfile $p): static { $this->studentProfile = $p; return $this; }
    
    public function getName(): ?string { return $this->name; }
    public function setName(?string $v): static { $this->name = $v; return $this; }
    
    public function setOccupation(?string $v): static { $this->occupation = $v; return $this; }
    public function getOccupation(): ?string { return $this->occupation; }

    public function setSchoolName(?string $v): static { $this->schoolName = $v; return $this; }
    public function getSchoolName(): ?string { return $this->schoolName; }
    
    // Alias for legacy controller usage
    public function setSchoolOrCompany(?string $v): static { $this->schoolName = $v; return $this; }
    public function getSchoolOrCompany(): ?string { return $this->schoolName; }

    public function setStudentNumber(?string $v): static { $this->studentNumber = $v; return $this; }
    public function getStudentNumber(): ?string { return $this->studentNumber; }

    public function setAge(?int $v): static { $this->age = $v; return $this; }
    public function getAge(): ?int { return $this->age; }
}