<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ApplicantBedPassportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicantBedPassportRepository::class)]
#[ORM\Table(name: 'bed_passports')]
class ApplicantBedPassport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'passport', targetEntity: ApplicantBed::class)]
    #[ORM\JoinColumn(name: 'student_number', referencedColumnName: 'student_number', nullable: false, onDelete: 'CASCADE')]
    private ?ApplicantBed $applicant = null;

    #[ORM\Column(name: 'passport_number', length: 50)]
    private ?string $passportNumber = null;

    #[ORM\Column(name: 'country_of_issue', length: 100)]
    private ?string $countryOfIssue = null;

    #[ORM\Column(name: 'date_issued', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateIssued = null;

    #[ORM\Column(name: 'expiration_date', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $expirationDate = null;

    public function getId(): ?int { return $this->id; }

    public function getApplicant(): ?ApplicantBed { return $this->applicant; }
    public function setApplicant(?ApplicantBed $applicant): static { $this->applicant = $applicant; return $this; }

    public function getPassportNumber(): ?string { return $this->passportNumber; }
    public function setPassportNumber(string $passportNumber): static { $this->passportNumber = $passportNumber; return $this; }

    public function getCountryOfIssue(): ?string { return $this->countryOfIssue; }
    public function setCountryOfIssue(string $countryOfIssue): static { $this->countryOfIssue = $countryOfIssue; return $this; }

    public function getDateIssued(): ?\DateTimeInterface { return $this->dateIssued; }
    public function setDateIssued(\DateTimeInterface $dateIssued): static { $this->dateIssued = $dateIssued; return $this; }

    public function getExpirationDate(): ?\DateTimeInterface { return $this->expirationDate; }
    public function setExpirationDate(\DateTimeInterface $expirationDate): static { $this->expirationDate = $expirationDate; return $this; }
}
