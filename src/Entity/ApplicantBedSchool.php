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

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ApplicantBed::class, inversedBy: 'schools')]
    #[ORM\JoinColumn(name: 'applicant_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?ApplicantBed $applicant = null;

    #[ORM\Column(name: 'ad_con', type: 'string', length: 15, nullable: true)]
    private ?string $adCon = null;

    #[ORM\Column(name: 'Level', type: 'string', length: 1)]
    private ?string $Level = null;

    #[ORM\Column(name: 'School', type: 'string', length: 500)]
    private ?string $School = null;

    #[ORM\Column(name: 'YearEnd', type: 'integer', nullable: true)]
    private ?int $YearEnd = null;

    public function setApplicant(?ApplicantBed $a): static { $this->applicant = $a; return $this; }
    public function setAdCon(?string $v): static { $this->adCon = $v; return $this; }
    public function setLevel(string $v): static { $this->Level = $v; return $this; }
    public function setSchool(string $v): static { $this->School = $v; return $this; }
    public function setYearEnd(?int $v): static { $this->YearEnd = $v; return $this; }
}