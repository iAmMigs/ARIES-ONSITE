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

    #[ORM\Column(length: 255)]
    private ?string $schoolName = null;

    #[ORM\Column(length: 20)]
    private ?string $level = null; // GRS, JHS

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $yearGraduated = null;

    public function getId(): ?int { return $this->id; }

    public function setStudentProfile(?StudentProfile $studentProfile): static { $this->studentProfile = $studentProfile; return $this; }
    public function getStudentProfile(): ?StudentProfile { return $this->studentProfile; }

    public function getSchoolName(): ?string { return $this->schoolName; }
    public function setSchoolName(string $schoolName): static { $this->schoolName = $schoolName; return $this; }

    public function getLevel(): ?string { return $this->level; }
    public function setLevel(string $level): static { $this->level = $level; return $this; }

    public function getYearGraduated(): ?string { return $this->yearGraduated; }
    public function setYearGraduated(?string $yearGraduated): static { $this->yearGraduated = $yearGraduated; return $this; }
}