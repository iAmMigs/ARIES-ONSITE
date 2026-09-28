<?php

declare(strict_types=1);

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

    #[ORM\Column(name: 'SchoolType', type: Types::STRING, length: 50, nullable: true)]
    private ?string $schoolType = null;

    #[ORM\Column(name: 'is_international', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isInternational = false;

    #[ORM\Column(name: 'country', type: Types::STRING, length: 100, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(name: 'region', type: Types::STRING, length: 100, nullable: true)]
    private ?string $region = null;

    #[ORM\Column(name: 'province', type: Types::STRING, length: 100, nullable: true)]
    private ?string $province = null;

    #[ORM\Column(name: 'city', type: Types::STRING, length: 100, nullable: true)]
    private ?string $city = null;

    public function getId(): ?int { return $this->id; }

    public function getApplicant(): ?ApplicantBed { return $this->applicant; }
    public function setApplicant(?ApplicantBed $applicant): static { $this->applicant = $applicant; return $this; }
    
    public function getLevel(): ?string { return $this->Level; }
    public function setLevel(string $Level): static { $this->Level = $Level; return $this; }
    
    public function getSchool(): ?string { return $this->School; }
    public function setSchool(string $School): static { $this->School = $School; return $this; }
    
    public function getSchoolYear(): ?string { return $this->schoolYear; }
    public function setSchoolYear(?string $schoolYear): static { $this->schoolYear = $schoolYear; return $this; }

    public function getSchoolType(): ?string { return $this->schoolType; }
    public function setSchoolType(?string $schoolType): static { $this->schoolType = $schoolType; return $this; }

    public function isInternational(): bool { return $this->isInternational; }
    public function setIsInternational(bool $isInternational): static { $this->isInternational = $isInternational; return $this; }

    public function getCountry(): ?string { return $this->country; }
    public function setCountry(?string $country): static { $this->country = $country; return $this; }

    public function getRegion(): ?string { return $this->region; }
    public function setRegion(?string $region): static { $this->region = $region; return $this; }

    public function getProvince(): ?string { return $this->province; }
    public function setProvince(?string $province): static { $this->province = $province; return $this; }

    public function getCity(): ?string { return $this->city; }
    public function setCity(?string $city): static { $this->city = $city; return $this; }
}