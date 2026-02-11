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

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    private ?string $relationship = null; // Mother, Father, Guardian

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $occupation = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $contactNumber = null;

    public function getId(): ?int { return $this->id; }

    public function setStudentProfile(?StudentProfile $studentProfile): static { $this->studentProfile = $studentProfile; return $this; }
    public function getStudentProfile(): ?StudentProfile { return $this->studentProfile; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getRelationship(): ?string { return $this->relationship; }
    public function setRelationship(string $relationship): static { $this->relationship = $relationship; return $this; }
}