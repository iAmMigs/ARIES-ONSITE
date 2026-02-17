<?php

namespace App\Entity;

use App\Repository\ApplicantBedGuardianRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicantBedGuardianRepository::class)]
#[ORM\Table(name: 'bed_guardians')]
class ApplicantBedGuardian
{
    // Composite Primary Key: Applicant (student_number) + Relationship
    
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: ApplicantBed::class, inversedBy: 'guardians')]
    #[ORM\JoinColumn(name: 'student_number', referencedColumnName: 'student_number', nullable: false, onDelete: 'CASCADE')]
    private ?ApplicantBed $applicant = null;

    #[ORM\Id]
    #[ORM\Column(name: 'Relationship', type: Types::STRING, length: 20)]
    private ?string $Relationship = null;

    #[ORM\Column(name: 'ParentName', type: Types::STRING, length: 255, nullable: true)]
    private ?string $ParentName = null;

    #[ORM\Column(name: 'Occupation', type: Types::STRING, length: 100, nullable: true)]
    private ?string $Occupation = null;

    #[ORM\Column(name: 'ContactNo', type: Types::STRING, length: 50, nullable: true)]
    private ?string $ContactNo = null;

    #[ORM\Column(name: 'IsDeceased', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $Deceased = false;

    #[ORM\Column(name: 'IsOFW', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $OFW = false;

    public function getApplicant(): ?ApplicantBed { return $this->applicant; }
    public function setApplicant(?ApplicantBed $applicant): static { $this->applicant = $applicant; return $this; }
    
    public function getRelationship(): ?string { return $this->Relationship; }
    public function setRelationship(string $Relationship): static { $this->Relationship = $Relationship; return $this; }
    
    public function getParentName(): ?string { return $this->ParentName; }
    public function setParentName(?string $ParentName): static { $this->ParentName = $ParentName; return $this; }
    
    public function getOccupation(): ?string { return $this->Occupation; }
    public function setOccupation(?string $Occupation): static { $this->Occupation = $Occupation; return $this; }
    
    public function getContactNo(): ?string { return $this->ContactNo; }
    public function setContactNo(?string $ContactNo): static { $this->ContactNo = $ContactNo; return $this; }
    
    public function isDeceased(): bool { return $this->Deceased; }
    public function setDeceased(bool $Deceased): static { $this->Deceased = $Deceased; return $this; }
    
    public function isOFW(): bool { return $this->OFW; }
    public function setOFW(bool $OFW): static { $this->OFW = $OFW; return $this; }
}