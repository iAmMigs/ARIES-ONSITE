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
}