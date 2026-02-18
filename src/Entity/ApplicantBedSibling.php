<?php

namespace App\Entity;

use App\Repository\ApplicantBedSiblingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicantBedSiblingRepository::class)]
#[ORM\Table(name: 'bed_siblings')]
class ApplicantBedSibling
{
    // Composite Primary Key: Applicant (student_number) + SiblingName
    
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: ApplicantBed::class, inversedBy: 'siblings')]
    #[ORM\JoinColumn(name: 'student_number', referencedColumnName: 'student_number', nullable: false, onDelete: 'CASCADE')]
    private ?ApplicantBed $applicant = null;

    #[ORM\Id]
    #[ORM\Column(name: 'SiblingName', type: Types::STRING, length: 255)]
    private ?string $SiblingName = null;

    #[ORM\Column(name: 'School', type: Types::STRING, length: 255, nullable: true)]
    private ?string $School = null;

    #[ORM\Column(name: 'IsFeuStudent', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $IsFeuStudent = false;

    #[ORM\Column(name: 'FeuStudentNo', type: Types::STRING, length: 50, nullable: true)]
    private ?string $FeuStudentNo = null;

    public function getApplicant(): ?ApplicantBed { return $this->applicant; }
    public function setApplicant(?ApplicantBed $applicant): static { $this->applicant = $applicant; return $this; }
    
    public function getSiblingName(): ?string { return $this->SiblingName; }
    public function setSiblingName(string $SiblingName): static { $this->SiblingName = $SiblingName; return $this; }
    
    public function getSchool(): ?string { return $this->School; }
    public function setSchool(?string $School): static { $this->School = $School; return $this; }
    
    public function isFeuStudent(): bool { return $this->IsFeuStudent; }
    public function setIsFeuStudent(bool $IsFeuStudent): static { $this->IsFeuStudent = $IsFeuStudent; return $this; }
    
    public function getFeuStudentNo(): ?string { return $this->FeuStudentNo; }
    public function setFeuStudentNo(?string $FeuStudentNo): static { $this->FeuStudentNo = $FeuStudentNo; return $this; }
}