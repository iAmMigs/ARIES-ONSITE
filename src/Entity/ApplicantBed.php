<?php

namespace App\Entity;

use App\Repository\ApplicantBedRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ApplicantBedRepository::class)]
#[ORM\Table(name: 'bed_applicants')]
#[UniqueEntity(fields: ['adCon'], message: 'This Admission Control Number already exists.')]
class ApplicantBed
{
    public const CAMPUS_DILIMAN = 'FDIL';
    public const CAMPUS_ALABANG = 'FALAB';
    public const STATUS_PENDING = 'P';
    public const GENDER_MALE = 'M';
    public const GENDER_FEMALE = 'F';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(name: 'ad_con', length: 15, unique: true)]
    private ?string $adCon = null;

    #[ORM\Column(name: 'student_number', length: 15, unique: true, nullable: true)]
    private ?string $studentNumber = null;

    #[ORM\Column(name: 'campus', length: 10)]
    private ?string $campus = null;

    #[ORM\Column(name: 'grade_level', length: 15, nullable: true)]
    private ?string $gradeLevel = null;

    #[ORM\Column(name: 'track_strand', length: 50, nullable: true)]
    private ?string $trackStrand = null;

    // --- Personal Info ---
    #[ORM\Column(name: 'last_name', length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(name: 'first_name', length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(name: 'middle_name', length: 100, nullable: true)]
    private ?string $middleName = null;

    #[ORM\Column(name: 'extension_name', length: 10, nullable: true)]
    private ?string $extensionName = null;

    #[ORM\Column(name: 'birth_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $birthDate = null;

    #[ORM\Column(name: 'birth_place', length: 255, nullable: true)]
    private ?string $birthPlace = null;

    #[ORM\Column(name: 'gender', length: 10, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(name: 'religion', length: 50, nullable: true)]
    private ?string $religion = null;

    #[ORM\Column(name: 'citizenship', length: 50, nullable: true)]
    private ?string $citizenship = null;

    #[ORM\Column(name: 'indigenous_group', length: 255, nullable: true)]
    private ?string $indigenousGroup = null;

    #[ORM\Column(name: 'lrn', length: 50, nullable: true)]
    private ?string $lrn = null;

    // --- Admission Info ---
    #[ORM\Column(name: 'admission_status', length: 1)]
    private string $admissionStatus = self::STATUS_PENDING;

    #[ORM\Column(name: 'admission_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $admissionDate = null;

    #[ORM\Column(name: 'school_year_of_entry', length: 15, nullable: true)]
    private ?string $schoolYearOfEntry = null;

    #[ORM\Column(name: 'enrollment_step', type: Types::SMALLINT, options: ['default' => 1])]
    private int $enrollmentStep = 1;

    // --- Contact Info ---
    #[ORM\Column(name: 'mobile_number', length: 50)]
    private ?string $mobileNumber = null;

    #[ORM\Column(name: 'land_line_number', length: 50, nullable: true)]
    private ?string $landLineNumber = null;

    #[ORM\Column(name: 'personal_email', length: 255)]
    private ?string $personalEmail = null;

    // --- Foreign Info ---
    #[ORM\Column(name: 'visa_type', length: 50, nullable: true)]
    private ?string $visaType = null;

    #[ORM\Column(name: 'passport_number', length: 50, nullable: true)]
    private ?string $passportNumber = null;

    // --- Current Address ---
    #[ORM\Column(name: 'current_region', length: 255, nullable: true)]
    private ?string $currentRegion = null;

    #[ORM\Column(name: 'current_province', length: 255, nullable: true)]
    private ?string $currentProvince = null;

    #[ORM\Column(name: 'current_city', length: 255, nullable: true)]
    private ?string $currentCity = null;

    #[ORM\Column(name: 'current_barangay', length: 255, nullable: true)]
    private ?string $currentBarangay = null;

    #[ORM\Column(name: 'current_address', type: Types::TEXT, nullable: true)]
    private ?string $currentAddress = null;

    #[ORM\Column(name: 'current_zip', length: 50, nullable: true)]
    private ?string $currentZip = null;

    // --- Permanent Address ---
    #[ORM\Column(name: 'permanent_region', length: 255, nullable: true)]
    private ?string $permanentRegion = null;

    #[ORM\Column(name: 'permanent_province', length: 255, nullable: true)]
    private ?string $permanentProvince = null;

    #[ORM\Column(name: 'permanent_city', length: 255, nullable: true)]
    private ?string $permanentCity = null;

    #[ORM\Column(name: 'permanent_barangay', length: 255, nullable: true)]
    private ?string $permanentBarangay = null;

    #[ORM\Column(name: 'permanent_address', type: Types::TEXT, nullable: true)]
    private ?string $permanentAddress = null;

    #[ORM\Column(name: 'permanent_zip', length: 50, nullable: true)]
    private ?string $permanentZip = null;

    #[ORM\Column(name: 'photo_slug', type: Types::TEXT, nullable: true)]
    private ?string $photoSlug = null;

    // --- Relationships ---
    #[ORM\OneToMany(mappedBy: 'applicant', targetEntity: ApplicantBedGuardian::class, cascade: ['persist', 'remove'])]
    private Collection $guardians;

    #[ORM\OneToMany(mappedBy: 'applicant', targetEntity: ApplicantBedSibling::class, cascade: ['persist', 'remove'])]
    private Collection $siblings;

    #[ORM\OneToMany(mappedBy: 'applicant', targetEntity: ApplicantBedSchool::class, cascade: ['persist', 'remove'])]
    private Collection $schools;

    #[ORM\OneToMany(mappedBy: 'applicant', targetEntity: ApplicantBedRequirement::class, cascade: ['persist', 'remove'])]
    private Collection $requirements;

    public function __construct()
    {
        $this->guardians = new ArrayCollection();
        $this->siblings = new ArrayCollection();
        $this->schools = new ArrayCollection();
        $this->requirements = new ArrayCollection();
    }

    // --- Getters & Setters ---
    public function getId(): ?int { return $this->id; }
    public function getAdCon(): ?string { return $this->adCon; }
    public function setAdCon(string $adCon): static { $this->adCon = $adCon; return $this; }
    public function getStudentNumber(): ?string { return $this->studentNumber; }
    public function setStudentNumber(?string $num): static { $this->studentNumber = $num; return $this; }
    public function setCampus(string $c): static { $this->campus = $c; return $this; }
    public function setGradeLevel(?string $l): static { $this->gradeLevel = $l; return $this; }
    public function setTrackStrand(?string $s): static { $this->trackStrand = $s; return $this; }
    public function setLastName(string $n): static { $this->lastName = $n; return $this; }
    public function getLastName(): ?string { return $this->lastName; }
    public function setFirstName(string $n): static { $this->firstName = $n; return $this; }
    public function getFirstName(): ?string { return $this->firstName; }
    public function setMiddleName(?string $n): static { $this->middleName = $n; return $this; }
    public function setExtensionName(?string $n): static { $this->extensionName = $n; return $this; }
    public function setBirthDate(?\DateTimeInterface $d): static { $this->birthDate = $d; return $this; }
    public function setBirthPlace(?string $p): static { $this->birthPlace = $p; return $this; }
    public function setGender(?string $g): static { $this->gender = $g; return $this; }
    public function setReligion(?string $r): static { $this->religion = $r; return $this; }
    public function setCitizenship(?string $c): static { $this->citizenship = $c; return $this; }
    public function setIndigenousGroup(?string $i): static { $this->indigenousGroup = $i; return $this; }
    public function setLrn(?string $l): static { $this->lrn = $l; return $this; }
    public function setAdmissionStatus(string $s): static { $this->admissionStatus = $s; return $this; }
    public function setAdmissionDate(?\DateTimeInterface $d): static { $this->admissionDate = $d; return $this; }
    public function setSchoolYearOfEntry(?string $y): static { $this->schoolYearOfEntry = $y; return $this; }
    public function setEnrollmentStep(int $s): static { $this->enrollmentStep = $s; return $this; }
    public function setMobileNumber(string $n): static { $this->mobileNumber = $n; return $this; }
    public function setLandLineNumber(?string $n): static { $this->landLineNumber = $n; return $this; }
    public function setPersonalEmail(string $e): static { $this->personalEmail = $e; return $this; }
    public function setVisaType(?string $v): static { $this->visaType = $v; return $this; }
    public function setPassportNumber(?string $p): static { $this->passportNumber = $p; return $this; }
    public function setPhotoSlug(?string $p): static { $this->photoSlug = $p; return $this; }

    // Address Setters
    public function setCurrentRegion(?string $v): static { $this->currentRegion = $v; return $this; }
    public function getCurrentRegion(): ?string { return $this->currentRegion; }
    public function setCurrentProvince(?string $v): static { $this->currentProvince = $v; return $this; }
    public function getCurrentProvince(): ?string { return $this->currentProvince; }
    public function setCurrentCity(?string $v): static { $this->currentCity = $v; return $this; }
    public function getCurrentCity(): ?string { return $this->currentCity; }
    public function setCurrentBarangay(?string $v): static { $this->currentBarangay = $v; return $this; }
    public function getCurrentBarangay(): ?string { return $this->currentBarangay; }
    public function setCurrentAddress(?string $v): static { $this->currentAddress = $v; return $this; }
    public function getCurrentAddress(): ?string { return $this->currentAddress; }
    public function setCurrentZip(?string $v): static { $this->currentZip = $v; return $this; }
    public function getCurrentZip(): ?string { return $this->currentZip; }

    public function setPermanentRegion(?string $v): static { $this->permanentRegion = $v; return $this; }
    public function setPermanentProvince(?string $v): static { $this->permanentProvince = $v; return $this; }
    public function setPermanentCity(?string $v): static { $this->permanentCity = $v; return $this; }
    public function setPermanentBarangay(?string $v): static { $this->permanentBarangay = $v; return $this; }
    public function setPermanentAddress(?string $v): static { $this->permanentAddress = $v; return $this; }
    public function setPermanentZip(?string $v): static { $this->permanentZip = $v; return $this; }

    // Collections
    public function addGuardian(ApplicantBedGuardian $g): static {
        if (!$this->guardians->contains($g)) { $this->guardians->add($g); $g->setApplicant($this); }
        return $this;
    }
    public function addSibling(ApplicantBedSibling $s): static {
        if (!$this->siblings->contains($s)) { $this->siblings->add($s); $s->setApplicant($this); }
        return $this;
    }
    public function addSchool(ApplicantBedSchool $s): static {
        if (!$this->schools->contains($s)) { $this->schools->add($s); $s->setApplicant($this); }
        return $this;
    }
    public function addRequirement(ApplicantBedRequirement $r): static {
        if (!$this->requirements->contains($r)) { $this->requirements->add($r); $r->setApplicant($this); }
        return $this;
    }
}