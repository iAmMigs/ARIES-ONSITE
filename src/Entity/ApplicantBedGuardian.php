<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ApplicantBedGuardianRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicantBedGuardianRepository::class)]
#[ORM\Table(name: 'bed_guardians')]
class ApplicantBedGuardian
{
    // --- PRIMARY KEY: guardian_id ---
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'guardian_id')]
    private ?int $id = null;

    // --- FOREIGN KEY: student_number ---
    // Removed #[ORM\Id] -> This is now a standard ManyToOne relation
    #[ORM\ManyToOne(targetEntity: ApplicantBed::class, inversedBy: 'guardians')]
    #[ORM\JoinColumn(name: 'student_number', referencedColumnName: 'student_number', nullable: false, onDelete: 'CASCADE')]
    private ?ApplicantBed $applicant = null;

    // --- COLUMNS ---
    // Removed #[ORM\Id] -> This is now a standard column
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

    #[ORM\Column(name: 'guardian_type', type: 'string', length: 20, nullable: true)]
    private ?string $guardianType = null;

    #[ORM\Column(name: 'OfwCountry', type: Types::STRING, length: 100, nullable: true)]
    private ?string $ofwCountry = null;

    #[ORM\Column(name: 'Email', type: Types::STRING, length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(name: 'Address', type: Types::TEXT, nullable: true)]
    private ?string $address = null;

    // --- Guardian Current Address ---
    #[ORM\Column(name: 'current_region', type: Types::STRING, length: 100, nullable: true)]
    private ?string $currentRegion = null;

    #[ORM\Column(name: 'current_province', type: Types::STRING, length: 100, nullable: true)]
    private ?string $currentProvince = null;

    #[ORM\Column(name: 'current_city', type: Types::STRING, length: 100, nullable: true)]
    private ?string $currentCity = null;

    #[ORM\Column(name: 'current_barangay', type: Types::STRING, length: 100, nullable: true)]
    private ?string $currentBarangay = null;

    #[ORM\Column(name: 'current_address', type: Types::TEXT, nullable: true)]
    private ?string $currentAddress = null;

    #[ORM\Column(name: 'current_zip', type: Types::STRING, length: 10, nullable: true)]
    private ?string $currentZip = null;

    // --- Guardian Permanent Address ---
    #[ORM\Column(name: 'permanent_region', type: Types::STRING, length: 100, nullable: true)]
    private ?string $permanentRegion = null;

    #[ORM\Column(name: 'permanent_province', type: Types::STRING, length: 100, nullable: true)]
    private ?string $permanentProvince = null;

    #[ORM\Column(name: 'permanent_city', type: Types::STRING, length: 100, nullable: true)]
    private ?string $permanentCity = null;

    #[ORM\Column(name: 'permanent_barangay', type: Types::STRING, length: 100, nullable: true)]
    private ?string $permanentBarangay = null;

    #[ORM\Column(name: 'permanent_address', type: Types::TEXT, nullable: true)]
    private ?string $permanentAddress = null;

    #[ORM\Column(name: 'permanent_zip', type: Types::STRING, length: 10, nullable: true)]
    private ?string $permanentZip = null;

    // --- Same as Applicant Flag (Guardian slot only) ---
    #[ORM\Column(name: 'is_same_as_applicant', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isSameAsApplicant = false;

    // --- GETTERS & SETTERS ---

    public function getId(): ?int { return $this->id; }

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

    public function getGuardianType(): ?string
    {
        return $this->guardianType;
    }
    public function setGuardianType(?string $guardianType): static
    {
        $this->guardianType = $guardianType;
        return $this;
    }

    public function getOfwCountry(): ?string { return $this->ofwCountry; }
    public function setOfwCountry(?string $ofwCountry): static { $this->ofwCountry = $ofwCountry; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }

    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): static { $this->address = $address; return $this; }

    // --- Current Address ---
    public function getCurrentRegion(): ?string { return $this->currentRegion; }
    public function setCurrentRegion(?string $v): static { $this->currentRegion = $v; return $this; }
    public function getCurrentProvince(): ?string { return $this->currentProvince; }
    public function setCurrentProvince(?string $v): static { $this->currentProvince = $v; return $this; }
    public function getCurrentCity(): ?string { return $this->currentCity; }
    public function setCurrentCity(?string $v): static { $this->currentCity = $v; return $this; }
    public function getCurrentBarangay(): ?string { return $this->currentBarangay; }
    public function setCurrentBarangay(?string $v): static { $this->currentBarangay = $v; return $this; }
    public function getCurrentAddress(): ?string { return $this->currentAddress; }
    public function setCurrentAddress(?string $v): static { $this->currentAddress = $v; return $this; }
    public function getCurrentZip(): ?string { return $this->currentZip; }
    public function setCurrentZip(?string $v): static { $this->currentZip = $v; return $this; }

    // --- Permanent Address ---
    public function getPermanentRegion(): ?string { return $this->permanentRegion; }
    public function setPermanentRegion(?string $v): static { $this->permanentRegion = $v; return $this; }
    public function getPermanentProvince(): ?string { return $this->permanentProvince; }
    public function setPermanentProvince(?string $v): static { $this->permanentProvince = $v; return $this; }
    public function getPermanentCity(): ?string { return $this->permanentCity; }
    public function setPermanentCity(?string $v): static { $this->permanentCity = $v; return $this; }
    public function getPermanentBarangay(): ?string { return $this->permanentBarangay; }
    public function setPermanentBarangay(?string $v): static { $this->permanentBarangay = $v; return $this; }
    public function getPermanentAddress(): ?string { return $this->permanentAddress; }
    public function setPermanentAddress(?string $v): static { $this->permanentAddress = $v; return $this; }
    public function getPermanentZip(): ?string { return $this->permanentZip; }
    public function setPermanentZip(?string $v): static { $this->permanentZip = $v; return $this; }

    // --- Same as Applicant ---
    public function isSameAsApplicant(): bool { return $this->isSameAsApplicant; }
    public function setSameAsApplicant(bool $v): static { $this->isSameAsApplicant = $v; return $this; }
}