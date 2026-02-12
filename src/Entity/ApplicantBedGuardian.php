<?php

namespace App\Entity;

use App\Repository\ApplicantBedGuardianRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicantBedGuardianRepository::class)]
#[ORM\Table(name: 'bed_guardians')]
class ApplicantBedGuardian
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ApplicantBed::class, inversedBy: 'guardians')]
    #[ORM\JoinColumn(name: 'applicant_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?ApplicantBed $applicant = null;

    #[ORM\Column(name: 'ad_con', type: 'string', length: 15, nullable: true)]
    private ?string $adCon = null;

    #[ORM\Column(name: 'Relationship', type: 'string', length: 20, nullable: false)]
    private ?string $Relationship = null;

    #[ORM\Column(name: 'ParentName', type: 'string', length: 255, nullable: false)]
    private ?string $ParentName = null;

    #[ORM\Column(name: 'Occupation', type: 'string', length: 255, nullable: true)]
    private ?string $Occupation = null;

    #[ORM\Column(name: 'ContactNo', type: 'string', length: 20, nullable: true)]
    private ?string $ContactNo = null;

    #[ORM\Column(name: 'Deceased', type: 'boolean', options: ['default' => false])]
    private bool $Deceased = false;

    #[ORM\Column(name: 'OFW', type: 'boolean', options: ['default' => false])]
    private bool $OFW = false;
    
    #[ORM\Column(name: 'IsPrimaryContact', type: 'boolean', options: ['default' => false])]
    private bool $IsPrimaryContact = false;

    public function setApplicant(?ApplicantBed $a): static { $this->applicant = $a; return $this; }
    public function setAdCon(?string $v): static { $this->adCon = $v; return $this; }
    public function setRelationship(string $v): static { $this->Relationship = $v; return $this; }
    public function getRelationship(): ?string { return $this->Relationship; }
    public function setParentName(string $v): static { $this->ParentName = $v; return $this; }
    public function setOccupation(?string $v): static { $this->Occupation = $v; return $this; }
    public function setContactNo(?string $v): static { $this->ContactNo = $v; return $this; }
    public function setDeceased(bool $v): static { $this->Deceased = $v; return $this; }
    public function setOFW(bool $v): static { $this->OFW = $v; return $this; }
    public function setIsPrimaryContact(bool $v): static { $this->IsPrimaryContact = $v; return $this; }
}