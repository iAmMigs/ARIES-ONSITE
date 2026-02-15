<?php

namespace App\Entity;

use App\Repository\ApplicantBedSchoolRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicantBedSchoolRepository::class)]
#[ORM\Table(name: 'bed_schools')]
class ApplicantBedSchool
{
    public const LEVEL_ELEMENTARY = 'P';
    public const LEVEL_JUNIOR_HIGH = 'I';
    public const LEVEL_SENIOR_HIGH = 'S';

    // Composite Primary Key: Applicant (ad_con) + Level
    
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: ApplicantBed::class, inversedBy: 'schools')]
    #[ORM\JoinColumn(name: 'ad_con', referencedColumnName: 'ad_con', nullable: false, onDelete: 'CASCADE')]
    private ?ApplicantBed $applicant = null;

    #[ORM\Id]
    #[ORM\Column(name: 'Level', type: Types::STRING, length: 1)]
    private ?string $Level = null;

    #[ORM\Column(name: 'School', type: Types::STRING, length: 255)]
    private ?string $School = null;

    #[ORM\Column(name: 'YearEnd', type: Types::INTEGER, nullable: true)]
    private ?int $YearEnd = null;

    // --- GETTERS & SETTERS ---

    public function getApplicant(): ?ApplicantBed { return $this->applicant; }
    public function setApplicant(?ApplicantBed $applicant): static { $this->applicant = $applicant; return $this; }
    
    public function getLevel(): ?string { return $this->Level; }
    public function setLevel(string $Level): static { $this->Level = $Level; return $this; }
    
    public function getSchool(): ?string { return $this->School; }
    public function setSchool(string $School): static { $this->School = $School; return $this; }
    
    public function getYearEnd(): ?int { return $this->YearEnd; }
    public function setYearEnd(?int $YearEnd): static { $this->YearEnd = $YearEnd; return $this; }
}