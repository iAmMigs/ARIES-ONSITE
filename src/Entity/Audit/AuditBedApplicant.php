<?php

namespace App\Entity\Audit;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'audit_bed_applicants')]
class AuditBedApplicant
{
    use AuditFieldsTrait;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $studentNumber = null;

    #[ORM\Column(length: 10, nullable: true)] private ?string $campus = null;
    
    // --- THIS WAS MISSING ---
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;
    // ------------------------

    #[ORM\Column(length: 20, nullable: true)] private ?string $educationType = null;
    #[ORM\Column(length: 20, nullable: true)] private ?string $gradeLevel = null;
    #[ORM\Column(length: 50, nullable: true)] private ?string $trackStrand = null;
    #[ORM\Column(length: 20, nullable: true)] private ?string $lrn = null;
    #[ORM\Column(length: 20, nullable: true)] private ?string $admissionStatus = null;
    #[ORM\Column(length: 15, nullable: true)] private ?string $schoolYearOfEntry = null;
    #[ORM\Column(length: 20, nullable: true)] private ?string $admissionType = null;
    #[ORM\Column(type: Types::FLOAT, nullable: true)] private ?float $examinationScore = null;
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)] private ?\DateTimeInterface $examinationDate = null;

    // Personal Info
    #[ORM\Column(length: 100, nullable: true)] private ?string $lastName = null;
    #[ORM\Column(length: 100, nullable: true)] private ?string $firstName = null;
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

    // Contact
    #[ORM\Column(length: 50, nullable: true)] private ?string $mobileNumber = null;
    #[ORM\Column(length: 50, nullable: true)] private ?string $landLineNumber = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $personalEmail = null;

    // Address
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

    // Other
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $photoSlug = null;
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)] private ?\DateTimeInterface $admissionDate = null;
    #[ORM\Column(length: 50, nullable: true)] private ?string $schoolType = null;
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)] private ?\DateTimeInterface $documentsAgreedDate = null;

    public function __set($name, $value) {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        }
    }
}