<?php

namespace App\Entity;

use App\Repository\ApplicantBedRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ApplicantBedRepository::class)]
#[ORM\Table(name: 'bed_applicants')]
#[ORM\Index(name: 'idx_last_name', columns: ['last_name'])]
#[ORM\Index(name: 'idx_first_name', columns: ['first_name'])]
#[ORM\Index(name: 'idx_campus', columns: ['campus'])]
#[ORM\Index(name: 'idx_admission_status', columns: ['admission_status'])]
#[ORM\Index(name: 'idx_school_year', columns: ['school_year_of_entry'])]
#[ORM\Index(name: 'idx_grade_level', columns: ['grade_level'])]
#[ORM\Index(name: 'idx_admission_type', columns: ['admission_type'])]
#[ORM\Index(name: 'idx_created_at', columns: ['created_at'])]
#[UniqueEntity(fields: ['studentNumber'], message: 'This Student Number already exists.')]
#[ORM\HasLifecycleCallbacks]
class ApplicantBed
{
    public const CAMPUS_DILIMAN = 'FDIL';
    public const CAMPUS_ALABANG = 'FALAB';
   
    public const STATUS_PENDING = 'Pending';
    public const STATUS_COMPLETED = 'Completed';
    
    public const GENDER_MALE = 'M';
    public const GENDER_FEMALE = 'F';

    public const TYPE_NEW_STUDENT = 'New Student';
    public const TYPE_TRANSFEREE = 'Transferee';
    

    #[ORM\Id]
    #[ORM\Column(name: 'student_number', length: 20, unique: true)]
    private ?string $studentNumber = null;

    #[ORM\Column(name: 'campus', length: 10)]
    private ?string $campus = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'applicant', targetEntity: ApplicantBedGuardian::class, cascade: ['persist', 'remove'])]
    private Collection $guardians;

    #[ORM\OneToMany(mappedBy: 'applicant', targetEntity: ApplicantBedSibling::class, cascade: ['persist', 'remove'])]
    private Collection $siblings;

    #[ORM\OneToMany(mappedBy: 'applicant', targetEntity: ApplicantBedSchool::class, cascade: ['persist', 'remove'])]
    private Collection $schools;

    #[ORM\OneToMany(mappedBy: 'applicant', targetEntity: ApplicantBedRequirement::class, cascade: ['persist', 'remove'])]
    private Collection $requirements;

    #[ORM\Column(length: 20)] private ?string $educationType = null;
    #[ORM\Column(length: 20, nullable: true)] private ?string $gradeLevel = null;
    #[ORM\Column(length: 50, nullable: true)] private ?string $trackStrand = null;
    #[ORM\Column(length: 20, nullable: true)] private ?string $lrn = null;
    #[ORM\Column(length: 20)] private string $admissionStatus = self::STATUS_PENDING;
    #[ORM\Column(length: 15, nullable: true)] private ?string $schoolYearOfEntry = null;

    #[ORM\Column(length: 20)] private ?string $admissionType = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $examinationScore = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $examinationDate = null;

    #[ORM\Column(length: 100)] private ?string $lastName = null;
    #[ORM\Column(length: 100)] private ?string $firstName = null;
    #[ORM\Column(length: 100, nullable: true)] private ?string $middleName = null;
    #[ORM\Column(length: 10, nullable: true)] private ?string $extensionName = null;
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)] private ?\DateTimeInterface $birthDate = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $birthPlace = null;
    #[ORM\Column(length: 10, nullable: true)] private ?string $gender = null;
    #[ORM\Column(length: 50, nullable: true)] private ?string $religion = null;

    #[ORM\Column(length: 50, nullable: true)] private ?string $citizenship = null;
    #[ORM\Column(length: 50, nullable: true)] private ?string $passportNumber = null;
    #[ORM\Column(length: 50, nullable: true)] private ?string $visaType = null;
    #[ORM\Column(length: 50, nullable: true)] private ?string $visaStatus = null;
    #[ORM\Column(length: 100, nullable: true)] private ?string $indigenousGroup = null;


    #[ORM\Column(length: 50)] private ?string $mobileNumber = null;
    #[ORM\Column(length: 50, nullable: true)] private ?string $landLineNumber = null;
    #[ORM\Column(length: 255)] private ?string $personalEmail = null;

    #[ORM\Column(length: 255, nullable: true)] private ?string $currentRegion = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $currentProvince = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $currentCity = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $currentBarangay = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $currentAddress = null;
    #[ORM\Column(length: 50, nullable: true)] private ?string $currentZip = null;

    #[ORM\Column(length: 255, nullable: true)] private ?string $permanentRegion = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $permanentProvince = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $permanentCity = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $permanentBarangay = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $permanentAddress = null;
    #[ORM\Column(length: 50, nullable: true)] private ?string $permanentZip = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $photoSlug = null;
    #[ORM\Column(length: 50, nullable: true)] private ?string $schoolType = null;
    
    #[ORM\Column(name: 'is_documents_agreed', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isDocumentsAgreed = false;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $documentsAgreedDate = null;

    /**
     * Virtual property for handling the photo upload securely before persisting.
     */
    #[Assert\Image(
        maxSize: '5M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
        mimeTypesMessage: 'Please upload a valid image file format (JPEG, PNG, WEBP).'
    )]
    private ?File $photoFile = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)] private ?\DateTimeInterface $admissionDate = null;

    public function __construct()
    {
        $this->guardians = new ArrayCollection();
        $this->siblings = new ArrayCollection();
        $this->schools = new ArrayCollection();
        $this->requirements = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getStudentNumber(): ?string { return $this->studentNumber; }
    public function setStudentNumber(?string $v): static { $this->studentNumber = $v; return $this; }

    public function getCampus(): ?string { return $this->campus; }
    public function setCampus(string $c): static { $this->campus = $c; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $c): static { $this->createdAt = $c; return $this; }

    public function getGuardians(): Collection { return $this->guardians; }
    public function addGuardian(ApplicantBedGuardian $g): static {
        if (!$this->guardians->contains($g)) {
            $this->guardians->add($g);
            $g->setApplicant($this);
        }
        return $this;
    }

    public function getSiblings(): Collection { return $this->siblings; }
    public function addSibling(ApplicantBedSibling $s): static {
        if (!$this->siblings->contains($s)) {
            $this->siblings->add($s);
            $s->setApplicant($this);
        }
        return $this;
    }

    public function getSchools(): Collection { return $this->schools; }
    public function addSchool(ApplicantBedSchool $s): static {
        if (!$this->schools->contains($s)) {
            $this->schools->add($s);
            $s->setApplicant($this);
        }
        return $this;
    }

    public function getRequirements(): Collection { return $this->requirements; }
    public function addRequirement(ApplicantBedRequirement $r): static {
        if (!$this->requirements->contains($r)) {
            $this->requirements->add($r);
            $r->setApplicant($this);
        }
        return $this;
    }

    public function getEducationType(): ?string { return $this->educationType; }
    public function setEducationType(?string $t): static { $this->educationType = $t; return $this; }
    public function getGradeLevel(): ?string { return $this->gradeLevel; }
    public function setGradeLevel(?string $l): static { $this->gradeLevel = $l; return $this; }
    public function getTrackStrand(): ?string { return $this->trackStrand; }
    public function setTrackStrand(?string $s): static { $this->trackStrand = $s; return $this; }
    public function getLrn(): ?string { return $this->lrn; }
    public function setLrn(?string $l): static { $this->lrn = $l; return $this; }
    public function getAdmissionStatus(): string { return $this->admissionStatus; }
    public function setAdmissionStatus(string $s): static { $this->admissionStatus = $s; return $this; }
    public function getSchoolYearOfEntry(): ?string { return $this->schoolYearOfEntry; }
    public function setSchoolYearOfEntry(?string $y): static { $this->schoolYearOfEntry = $y; return $this; }

    public function getExaminationScore(): ?float { return $this->examinationScore; }
    public function setExaminationScore(?float $score): static { $this->examinationScore = $score; return $this; }

    public function getExaminationDate(): ?\DateTimeInterface { return $this->examinationDate; }
    public function setExaminationDate(?\DateTimeInterface $d): static { $this->examinationDate = $d; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(string $n): static { $this->lastName = $n; return $this; }
    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(string $n): static { $this->firstName = $n; return $this; }
    public function getMiddleName(): ?string { return $this->middleName; }
    public function setMiddleName(?string $n): static { $this->middleName = $n; return $this; }
    public function getExtensionName(): ?string { return $this->extensionName; }
    public function setExtensionName(?string $n): static { $this->extensionName = $n; return $this; }
    
    public function getBirthDate(): ?\DateTimeInterface { return $this->birthDate; }
    public function setBirthDate(?\DateTimeInterface $d): static { $this->birthDate = $d; return $this; }
    public function getBirthPlace(): ?string { return $this->birthPlace; }
    public function setBirthPlace(?string $p): static { $this->birthPlace = $p; return $this; }
    
    public function getGender(): ?string { return $this->gender; }
    public function setGender(?string $g): static { $this->gender = $g; return $this; }
    public function getReligion(): ?string { return $this->religion; }
    public function setReligion(?string $r): static { $this->religion = $r; return $this; }
    public function getCitizenship(): ?string { return $this->citizenship; }
    public function setCitizenship(?string $c): static { $this->citizenship = $c; return $this; }
    public function getPassportNumber(): ?string { return $this->passportNumber; }
    public function setPassportNumber(?string $p): static { $this->passportNumber = $p; return $this; }
    public function getVisaType(): ?string { return $this->visaType; }
    public function setVisaType(?string $v): static { $this->visaType = $v; return $this; }
    public function getVisaStatus(): ?string { return $this->visaStatus; }
    public function setVisaStatus(?string $v): static { $this->visaStatus = $v; return $this; }



    public function getMobileNumber(): ?string { return $this->mobileNumber; }
    public function setMobileNumber(string $n): static { $this->mobileNumber = $n; return $this; }
    public function getLandLineNumber(): ?string { return $this->landLineNumber; }
    public function setLandLineNumber(?string $n): static { $this->landLineNumber = $n; return $this; }
    public function getPersonalEmail(): ?string { return $this->personalEmail; }
    public function setPersonalEmail(string $e): static { $this->personalEmail = $e; return $this; }
    
    public function getPhotoSlug(): ?string { return $this->photoSlug; }
    public function setPhotoSlug(?string $p): static { $this->photoSlug = $p; return $this; }

    public function getPhotoFile(): ?File { return $this->photoFile; }
    public function setPhotoFile(?File $photoFile): static { $this->photoFile = $photoFile; return $this; }

    public function getAdmissionDate(): ?\DateTimeInterface { return $this->admissionDate; }
    public function setAdmissionDate(?\DateTimeInterface $d): static { $this->admissionDate = $d; return $this; }

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

    public function getAdmissionType(): ?string { return $this->admissionType; }
    public function setAdmissionType(?string $t): static { $this->admissionType = $t; return $this; }

    public function getSchoolType(): ?string { return $this->schoolType; }
    public function setSchoolType(?string $t): static { $this->schoolType = $t; return $this; }

    public function getDocumentsAgreedDate(): ?\DateTimeInterface { return $this->documentsAgreedDate; }
    public function setDocumentsAgreedDate(?\DateTimeInterface $d): static { $this->documentsAgreedDate = $d; return $this; }

    public function isDocumentsAgreed(): bool { return $this->isDocumentsAgreed; }
    public function setDocumentsAgreed(bool $agreed): static { $this->isDocumentsAgreed = $agreed; return $this; }

    public function getIndigenousGroup(): ?string { return $this->indigenousGroup; }
    public function setIndigenousGroup(?string $v): static { $this->indigenousGroup = $v; return $this; }
}