<?php

namespace App\Entity;

use App\Repository\StudentProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudentProfileRepository::class)]
class StudentProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true, nullable: true)]
    private ?string $adConNumber = null;

    #[ORM\Column(length: 20, unique: true, nullable: true)]
    private ?string $studentNumber = null;

    #[ORM\Column(length: 50)]
    private ?string $campus = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicture = null;

    // --- Personal Information ---
    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $middleName = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $extensionName = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $birthDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $birthPlace = null; // Added from dataF

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $birthCountry = null; // Added from dataF

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $birthProvince = null; // Added from dataF

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $religion = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $citizenship = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $civilStatus = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $lrn = null; // Learner Reference Number

    // --- Contact Information ---
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $personalEmail = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $mobileNumber = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $landlineNumber = null;

    // --- Current Address ---
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $addressStreet = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $addressBarangay = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $addressCity = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $addressProvince = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $addressZip = null;

    // --- Application Details ---
    #[ORM\Column(length: 20)]
    private ?string $gradeLevel = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $strand = null;

    #[ORM\Column(length: 10)]
    private ?string $schoolYearStart = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $term = null; // 1st Term, 2nd Term

    #[ORM\Column(length: 20)]
    private ?string $status = 'Pending';

    // --- Relationships ---
    #[ORM\OneToMany(mappedBy: 'studentProfile', targetEntity: StudentSchool::class, cascade: ['persist', 'remove'])]
    private Collection $previousSchools;

    #[ORM\OneToMany(mappedBy: 'studentProfile', targetEntity: StudentParent::class, cascade: ['persist', 'remove'])]
    private Collection $parents;

    #[ORM\OneToMany(mappedBy: 'studentProfile', targetEntity: StudentSibling::class, cascade: ['persist', 'remove'])]
    private Collection $siblings;

    #[ORM\OneToMany(mappedBy: 'studentProfile', targetEntity: AdmissionRequirement::class, cascade: ['persist', 'remove'])]
    private Collection $requirements;

    public function __construct()
    {
        $this->previousSchools = new ArrayCollection();
        $this->parents = new ArrayCollection();
        $this->siblings = new ArrayCollection();
        $this->requirements = new ArrayCollection();
    }

    // --- GETTERS AND SETTERS ---
    public function getId(): ?int { return $this->id; }
    
    // Identifiers
    public function getAdConNumber(): ?string { return $this->adConNumber; }
    public function setAdConNumber(?string $adConNumber): static { $this->adConNumber = $adConNumber; return $this; }
    public function getStudentNumber(): ?string { return $this->studentNumber; }
    public function setStudentNumber(?string $studentNumber): static { $this->studentNumber = $studentNumber; return $this; }
    public function getCampus(): ?string { return $this->campus; }
    public function setCampus(string $campus): static { $this->campus = $campus; return $this; }
    public function getProfilePicture(): ?string { return $this->profilePicture; }
    public function setProfilePicture(?string $profilePicture): static { $this->profilePicture = $profilePicture; return $this; }

    // Personal
    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(string $lastName): static { $this->lastName = $lastName; return $this; }
    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(string $firstName): static { $this->firstName = $firstName; return $this; }
    public function getMiddleName(): ?string { return $this->middleName; }
    public function setMiddleName(?string $middleName): static { $this->middleName = $middleName; return $this; }
    public function getExtensionName(): ?string { return $this->extensionName; }
    public function setExtensionName(?string $extensionName): static { $this->extensionName = $extensionName; return $this; }
    public function getBirthDate(): ?\DateTimeInterface { return $this->birthDate; }
    public function setBirthDate(?\DateTimeInterface $birthDate): static { $this->birthDate = $birthDate; return $this; }
    public function getBirthPlace(): ?string { return $this->birthPlace; }
    public function setBirthPlace(?string $birthPlace): static { $this->birthPlace = $birthPlace; return $this; }
    public function getBirthCountry(): ?string { return $this->birthCountry; }
    public function setBirthCountry(?string $birthCountry): static { $this->birthCountry = $birthCountry; return $this; }
    public function getBirthProvince(): ?string { return $this->birthProvince; }
    public function setBirthProvince(?string $birthProvince): static { $this->birthProvince = $birthProvince; return $this; }
    public function getGender(): ?string { return $this->gender; }
    public function setGender(?string $gender): static { $this->gender = $gender; return $this; }
    public function getReligion(): ?string { return $this->religion; }
    public function setReligion(?string $religion): static { $this->religion = $religion; return $this; }
    public function getCitizenship(): ?string { return $this->citizenship; }
    public function setCitizenship(?string $citizenship): static { $this->citizenship = $citizenship; return $this; }
    public function getCivilStatus(): ?string { return $this->civilStatus; }
    public function setCivilStatus(?string $civilStatus): static { $this->civilStatus = $civilStatus; return $this; }
    public function getLrn(): ?string { return $this->lrn; }
    public function setLrn(?string $lrn): static { $this->lrn = $lrn; return $this; }

    // Contact
    public function getPersonalEmail(): ?string { return $this->personalEmail; }
    public function setPersonalEmail(?string $personalEmail): static { $this->personalEmail = $personalEmail; return $this; }
    public function getMobileNumber(): ?string { return $this->mobileNumber; }
    public function setMobileNumber(?string $mobileNumber): static { $this->mobileNumber = $mobileNumber; return $this; }
    public function getLandlineNumber(): ?string { return $this->landlineNumber; }
    public function setLandlineNumber(?string $landlineNumber): static { $this->landlineNumber = $landlineNumber; return $this; }

    // Address
    public function getAddressStreet(): ?string { return $this->addressStreet; }
    public function setAddressStreet(?string $addressStreet): static { $this->addressStreet = $addressStreet; return $this; }
    public function getAddressBarangay(): ?string { return $this->addressBarangay; }
    public function setAddressBarangay(?string $addressBarangay): static { $this->addressBarangay = $addressBarangay; return $this; }
    public function getAddressCity(): ?string { return $this->addressCity; }
    public function setAddressCity(?string $addressCity): static { $this->addressCity = $addressCity; return $this; }
    public function getAddressProvince(): ?string { return $this->addressProvince; }
    public function setAddressProvince(?string $addressProvince): static { $this->addressProvince = $addressProvince; return $this; }
    public function getAddressZip(): ?string { return $this->addressZip; }
    public function setAddressZip(?string $addressZip): static { $this->addressZip = $addressZip; return $this; }

    // Academic
    public function getGradeLevel(): ?string { return $this->gradeLevel; }
    public function setGradeLevel(string $gradeLevel): static { $this->gradeLevel = $gradeLevel; return $this; }
    public function getStrand(): ?string { return $this->strand; }
    public function setStrand(?string $strand): static { $this->strand = $strand; return $this; }
    public function getSchoolYearStart(): ?string { return $this->schoolYearStart; }
    public function setSchoolYearStart(string $schoolYearStart): static { $this->schoolYearStart = $schoolYearStart; return $this; }
    public function getTerm(): ?string { return $this->term; }
    public function setTerm(?string $term): static { $this->term = $term; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    // Relationships
    public function getPreviousSchools(): Collection { return $this->previousSchools; }
    public function addPreviousSchool(StudentSchool $previousSchool): static {
        if (!$this->previousSchools->contains($previousSchool)) {
            $this->previousSchools->add($previousSchool);
            $previousSchool->setStudentProfile($this);
        }
        return $this;
    }

    public function getParents(): Collection { return $this->parents; }
    public function addParent(StudentParent $parent): static {
        if (!$this->parents->contains($parent)) {
            $this->parents->add($parent);
            $parent->setStudentProfile($this);
        }
        return $this;
    }

    public function getSiblings(): Collection { return $this->siblings; }
    public function addSibling(StudentSibling $sibling): static {
        if (!$this->siblings->contains($sibling)) {
            $this->siblings->add($sibling);
            $sibling->setStudentProfile($this);
        }
        return $this;
    }

    public function getRequirements(): Collection { return $this->requirements; }
    public function addRequirement(AdmissionRequirement $requirement): static {
        if (!$this->requirements->contains($requirement)) {
            $this->requirements->add($requirement);
            $requirement->setStudentProfile($this);
        }
        return $this;
    }
}