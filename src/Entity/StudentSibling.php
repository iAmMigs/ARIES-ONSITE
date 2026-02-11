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

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $schoolOrCompany = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $age = null;

    public function getId(): ?int { return $this->id; }

    public function setStudentProfile(?StudentProfile $studentProfile): static { $this->studentProfile = $studentProfile; return $this; }
    public function getStudentProfile(): ?StudentProfile { return $this->studentProfile; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getSchoolOrCompany(): ?string { return $this->schoolOrCompany; }
    public function setSchoolOrCompany(?string $schoolOrCompany): static { $this->schoolOrCompany = $schoolOrCompany; return $this; }

    public function getAge(): ?int { return $this->age; }
    public function setAge(?int $age): static { $this->age = $age; return $this; }
}