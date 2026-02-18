<?php

namespace App\Entity\Audit;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'audit_bed_requirements')]
class AuditBedRequirement
{
    use AuditFieldsTrait;

    #[ORM\Column(name: 'student_number', type: Types::STRING, length: 20, nullable: true)]
    private ?string $studentNumber = null;

    #[ORM\Column(name: 'Slug', type: Types::STRING, length: 100, nullable: true)]
    private ?string $Slug = null;

    #[ORM\Column(name: 'Requirement', type: Types::STRING, length: 100, nullable: true)]
    private ?string $Requirement = null;

    #[ORM\Column(name: 'StoredFileName', type: Types::STRING, length: 255, nullable: true)]
    private ?string $StoredFileName = null;

    #[ORM\Column(name: 'Status', type: Types::STRING, length: 1, nullable: true)]
    private ?string $Status = null;

    #[ORM\Column(name: 'IsRequired', type: Types::BOOLEAN, nullable: true)]
    private ?bool $IsRequired = null;

    #[ORM\Column(name: 'DateSubmitted', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $DateSubmitted = null;

    public function __set($name, $value) { if (property_exists($this, $name)) { $this->$name = $value; } }
}