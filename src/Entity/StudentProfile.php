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

    // --- Identifiers ---
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $campus = null;

    #[ORM\Column(length: 20, unique: true, nullable: true)]
    private ?string $adConNumber = null;

    #[ORM\Column(length: 20, unique: true, nullable: true)]
    private ?string $studentNumber = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lrn = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicture = null; // CSV: Photo Slug

    // --- Personal Information ---
    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $middleName = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $extensionName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $gradeLevel = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $birthDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $birthCountry = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $birthProvince = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $birthPlace = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $religion = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $citizenship = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $civilStatus = null;

    // --- Admission Details ---
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $admissionStatus = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $admissionDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $entranceExamScore = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $entranceExamDate = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $termOfEntry = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $schoolYearOfEntry = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $curriculum = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $yearOfEntry = null;

    // --- Contact & Visa ---
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $landlineNumber = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $mobileNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $personalEmail = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $visaType = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $passportNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $visa = null;

    // --- Socio-Economic ---
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $familyIncome = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $birthOrder = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isWorkingStudent = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fbAccount = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $indigenousGroup = null;

    // --- Permanent Address ---
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $permanentCountry = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $permanentRegion = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $permanentProvince = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $permanentCity = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $permanentBarangay = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $permanentAddress = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $permanentZip = null;

    // --- Current Address ---
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $currentCountry = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $currentRegion = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $currentProvince = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $currentCity = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $currentBarangay = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $currentAddress = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $currentZip = null;

    // --- Application Status ---
    #[ORM\Column(length: 20)]
    private ?string $status = 'Pending';

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $strand = null;

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

    // --- GETTERS AND SETTERS (The Missing Piece) ---

    public function getId(): ?int { return $this->id; }

    public function getCampus(): ?string { return $this->campus; }
    public function setCampus(?string $campus): static { $this->campus = $campus; return $this; }

    public function getAdConNumber(): ?string { return $this->adConNumber; }
    public function setAdConNumber(?string $adConNumber): static { $this->adConNumber = $adConNumber; return $this; }

    public function getStudentNumber(): ?string { return $this->studentNumber; }
    public function setStudentNumber(?string $studentNumber): static { $this->studentNumber = $studentNumber; return $this; }

    public function getLrn(): ?string { return $this->lrn; }
    public function setLrn(?string $lrn): static { $this->lrn = $lrn; return $this; }

    public function getProfilePicture(): ?string { return $this->profilePicture; }
    public function setProfilePicture(?string $profilePicture): static { $this->profilePicture = $profilePicture; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(string $lastName): static { $this->lastName = $lastName; return $this; }

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(string $firstName): static { $this->firstName = $firstName; return $this; }

    public function getMiddleName(): ?string { return $this->middleName; }
    public function setMiddleName(?string $middleName): static { $this->middleName = $middleName; return $this; }

    public function getExtensionName(): ?string { return $this->extensionName; }
    public function setExtensionName(?string $extensionName): static { $this->extensionName = $extensionName; return $this; }

    public function getGradeLevel(): ?string { return $this->gradeLevel; }
    public function setGradeLevel(?string $gradeLevel): static { $this->gradeLevel = $gradeLevel; return $this; }

    public function getBirthDate(): ?\DateTimeInterface { return $this->birthDate; }
    public function setBirthDate(?\DateTimeInterface $birthDate): static { $this->birthDate = $birthDate; return $this; }

    public function getBirthCountry(): ?string { return $this->birthCountry; }
    public function setBirthCountry(?string $birthCountry): static { $this->birthCountry = $birthCountry; return $this; }

    public function getBirthProvince(): ?string { return $this->birthProvince; }
    public function setBirthProvince(?string $birthProvince): static { $this->birthProvince = $birthProvince; return $this; }

    public function getBirthPlace(): ?string { return $this->birthPlace; }
    public function setBirthPlace(?string $birthPlace): static { $this->birthPlace = $birthPlace; return $this; }

    public function getGender(): ?string { return $this->gender; }
    public function setGender(?string $gender): static { $this->gender = $gender; return $this; }

    public function getReligion(): ?string { return $this->religion; }
    public function setReligion(?string $religion): static { $this->religion = $religion; return $this; }

    public function getCitizenship(): ?string { return $this->citizenship; }
    public function setCitizenship(?string $citizenship): static { $this->citizenship = $citizenship; return $this; }

    public function getCivilStatus(): ?string { return $this->civilStatus; }
    public function setCivilStatus(?string $civilStatus): static { $this->civilStatus = $civilStatus; return $this; }

    public function getAdmissionStatus(): ?string { return $this->admissionStatus; }
    public function setAdmissionStatus(?string $admissionStatus): static { $this->admissionStatus = $admissionStatus; return $this; }

    public function getAdmissionDate(): ?\DateTimeInterface { return $this->admissionDate; }
    public function setAdmissionDate(?\DateTimeInterface $admissionDate): static { $this->admissionDate = $admissionDate; return $this; }

    public function getEntranceExamScore(): ?string { return $this->entranceExamScore; }
    public function setEntranceExamScore(?string $entranceExamScore): static { $this->entranceExamScore = $entranceExamScore; return $this; }

    public function getEntranceExamDate(): ?string { return $this->entranceExamDate; }
    public function setEntranceExamDate(?string $entranceExamDate): static { $this->entranceExamDate = $entranceExamDate; return $this; }

    public function getTermOfEntry(): ?string { return $this->termOfEntry; }
    public function setTermOfEntry(?string $termOfEntry): static { $this->termOfEntry = $termOfEntry; return $this; }

    public function getSchoolYearOfEntry(): ?string { return $this->schoolYearOfEntry; }
    public function setSchoolYearOfEntry(?string $schoolYearOfEntry): static { $this->schoolYearOfEntry = $schoolYearOfEntry; return $this; }
    // Alias for compatibility if templates use schoolYearStart
    public function getSchoolYearStart(): ?string { return $this->schoolYearOfEntry; }
    public function setSchoolYearStart(?string $v): static { $this->schoolYearOfEntry = $v; return $this; }

    public function getCurriculum(): ?string { return $this->curriculum; }
    public function setCurriculum(?string $curriculum): static { $this->curriculum = $curriculum; return $this; }

    public function getYearOfEntry(): ?string { return $this->yearOfEntry; }
    public function setYearOfEntry(?string $yearOfEntry): static { $this->yearOfEntry = $yearOfEntry; return $this; }

    public function getLandlineNumber(): ?string { return $this->landlineNumber; }
    public function setLandlineNumber(?string $landlineNumber): static { $this->landlineNumber = $landlineNumber; return $this; }

    public function getMobileNumber(): ?string { return $this->mobileNumber; }
    public function setMobileNumber(?string $mobileNumber): static { $this->mobileNumber = $mobileNumber; return $this; }

    public function getPersonalEmail(): ?string { return $this->personalEmail; }
    public function setPersonalEmail(?string $personalEmail): static { $this->personalEmail = $personalEmail; return $this; }

    public function getVisaType(): ?string { return $this->visaType; }
    public function setVisaType(?string $visaType): static { $this->visaType = $visaType; return $this; }

    public function getPassportNumber(): ?string { return $this->passportNumber; }
    public function setPassportNumber(?string $passportNumber): static { $this->passportNumber = $passportNumber; return $this; }

    public function getVisa(): ?string { return $this->visa; }
    public function setVisa(?string $visa): static { $this->visa = $visa; return $this; }

    public function getFamilyIncome(): ?string { return $this->familyIncome; }
    public function setFamilyIncome(?string $familyIncome): static { $this->familyIncome = $familyIncome; return $this; }

    public function getBirthOrder(): ?string { return $this->birthOrder; }
    public function setBirthOrder(?string $birthOrder): static { $this->birthOrder = $birthOrder; return $this; }

    public function isWorkingStudent(): ?bool { return $this->isWorkingStudent; }
    public function setIsWorkingStudent(?bool $isWorkingStudent): static { $this->isWorkingStudent = $isWorkingStudent; return $this; }

    public function getFbAccount(): ?string { return $this->fbAccount; }
    public function setFbAccount(?string $fbAccount): static { $this->fbAccount = $fbAccount; return $this; }

    public function getIndigenousGroup(): ?string { return $this->indigenousGroup; }
    public function setIndigenousGroup(?string $indigenousGroup): static { $this->indigenousGroup = $indigenousGroup; return $this; }

    // Permanent Address
    public function getPermanentCountry(): ?string { return $this->permanentCountry; }
    public function setPermanentCountry(?string $permanentCountry): static { $this->permanentCountry = $permanentCountry; return $this; }

    public function getPermanentRegion(): ?string { return $this->permanentRegion; }
    public function setPermanentRegion(?string $permanentRegion): static { $this->permanentRegion = $permanentRegion; return $this; }

    public function getPermanentProvince(): ?string { return $this->permanentProvince; }
    public function setPermanentProvince(?string $permanentProvince): static { $this->permanentProvince = $permanentProvince; return $this; }

    public function getPermanentCity(): ?string { return $this->permanentCity; }
    public function setPermanentCity(?string $permanentCity): static { $this->permanentCity = $permanentCity; return $this; }

    public function getPermanentBarangay(): ?string { return $this->permanentBarangay; }
    public function setPermanentBarangay(?string $permanentBarangay): static { $this->permanentBarangay = $permanentBarangay; return $this; }

    public function getPermanentAddress(): ?string { return $this->permanentAddress; }
    public function setPermanentAddress(?string $permanentAddress): static { $this->permanentAddress = $permanentAddress; return $this; }

    public function getPermanentZip(): ?string { return $this->permanentZip; }
    public function setPermanentZip(?string $permanentZip): static { $this->permanentZip = $permanentZip; return $this; }

    // Current Address
    public function getCurrentCountry(): ?string { return $this->currentCountry; }
    public function setCurrentCountry(?string $currentCountry): static { $this->currentCountry = $currentCountry; return $this; }

    public function getCurrentRegion(): ?string { return $this->currentRegion; }
    public function setCurrentRegion(?string $currentRegion): static { $this->currentRegion = $currentRegion; return $this; }

    public function getCurrentProvince(): ?string { return $this->currentProvince; }
    public function setCurrentProvince(?string $currentProvince): static { $this->currentProvince = $currentProvince; return $this; }

    public function getCurrentCity(): ?string { return $this->currentCity; }
    public function setCurrentCity(?string $currentCity): static { $this->currentCity = $currentCity; return $this; }

    public function getCurrentBarangay(): ?string { return $this->currentBarangay; }
    public function setCurrentBarangay(?string $currentBarangay): static { $this->currentBarangay = $currentBarangay; return $this; }

    public function getCurrentAddress(): ?string { return $this->currentAddress; }
    public function setCurrentAddress(?string $currentAddress): static { $this->currentAddress = $currentAddress; return $this; }

    public function getCurrentZip(): ?string { return $this->currentZip; }
    public function setCurrentZip(?string $currentZip): static { $this->currentZip = $currentZip; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getStrand(): ?string { return $this->strand; }
    public function setStrand(?string $strand): static { $this->strand = $strand; return $this; }

    /**
     * @return Collection<int, StudentSchool>
     */
    public function getPreviousSchools(): Collection
    {
        return $this->previousSchools;
    }

    public function addPreviousSchool(StudentSchool $previousSchool): static
    {
        if (!$this->previousSchools->contains($previousSchool)) {
            $this->previousSchools->add($previousSchool);
            $previousSchool->setStudentProfile($this);
        }

        return $this;
    }

    public function removePreviousSchool(StudentSchool $previousSchool): static
    {
        if ($this->previousSchools->removeElement($previousSchool)) {
            // set the owning side to null (unless already changed)
            if ($previousSchool->getStudentProfile() === $this) {
                $previousSchool->setStudentProfile(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StudentParent>
     */
    public function getParents(): Collection
    {
        return $this->parents;
    }

    public function addParent(StudentParent $parent): static
    {
        if (!$this->parents->contains($parent)) {
            $this->parents->add($parent);
            $parent->setStudentProfile($this);
        }

        return $this;
    }

    public function removeParent(StudentParent $parent): static
    {
        if ($this->parents->removeElement($parent)) {
            if ($parent->getStudentProfile() === $this) {
                $parent->setStudentProfile(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StudentSibling>
     */
    public function getSiblings(): Collection
    {
        return $this->siblings;
    }

    public function addSibling(StudentSibling $sibling): static
    {
        if (!$this->siblings->contains($sibling)) {
            $this->siblings->add($sibling);
            $sibling->setStudentProfile($this);
        }

        return $this;
    }

    public function removeSibling(StudentSibling $sibling): static
    {
        if ($this->siblings->removeElement($sibling)) {
            if ($sibling->getStudentProfile() === $this) {
                $sibling->setStudentProfile(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AdmissionRequirement>
     */
    public function getRequirements(): Collection
    {
        return $this->requirements;
    }

    public function addRequirement(AdmissionRequirement $requirement): static
    {
        if (!$this->requirements->contains($requirement)) {
            $this->requirements->add($requirement);
            $requirement->setStudentProfile($this);
        }

        return $this;
    }

    public function removeRequirement(AdmissionRequirement $requirement): static
    {
        if ($this->requirements->removeElement($requirement)) {
            if ($requirement->getStudentProfile() === $this) {
                $requirement->setStudentProfile(null);
            }
        }

        return $this;
    }
}