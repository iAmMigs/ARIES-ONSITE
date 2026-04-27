<?php

namespace App\Entity;

use App\Repository\ApplicantBedSchoolRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicantBedSchoolRepository::class)]
#[ORM\Table(name: 'bed_schools')]
class ApplicantBedSchool
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ApplicantBed::class, inversedBy: 'schools')]
    #[ORM\JoinColumn(name: 'student_number', referencedColumnName: 'student_number', nullable: false, onDelete: 'CASCADE')]
    private ?ApplicantBed $applicant = null;

    #[ORM\Column(name: 'Level', type: Types::STRING, length: 50)]
    private ?string $Level = null;

    #[ORM\Column(name: 'School', type: Types::STRING, length: 255)]
    private ?string $School = null;

    #[ORM\Column(name: 'SchoolYear', type: Types::STRING, length: 20, nullable: true)]
    private ?string $schoolYear = null;

    public function getId(): ?int { return $this->id; }

    public function getApplicant(): ?ApplicantBed { return $this->applicant; }
    public function setApplicant(?ApplicantBed $applicant): static { $this->applicant = $applicant; return $this; }
    
    public function getLevel(): ?string { return $this->Level; }
    public function setLevel(string $Level): static { $this->Level = $Level; return $this; }
    
    public function getSchool(): ?string { return $this->School; }
    public function setSchool(string $School): static { $this->School = $School; return $this; }
    
    public function getSchoolYear(): ?string { return $this->schoolYear; }
    public function setSchoolYear(?string $schoolYear): static { $this->schoolYear = $schoolYear; return $this; }
}