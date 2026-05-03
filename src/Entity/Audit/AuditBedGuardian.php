<?php

namespace App\Entity\Audit;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'audit_bed_guardians')]
class AuditBedGuardian
{
    use AuditFieldsTrait;

    #[ORM\Column(name: 'original_guardian_id', type: Types::INTEGER, nullable: true)]
    private ?int $originalId = null;

    #[ORM\Column(name: 'student_number', type: Types::STRING, length: 20, nullable: true)]
    private ?string $studentNumber = null;

    #[ORM\Column(name: 'Relationship', type: Types::STRING, length: 20, nullable: true)]
    private ?string $Relationship = null;

    #[ORM\Column(name: 'ParentName', type: Types::STRING, length: 255, nullable: true)]
    private ?string $ParentName = null;

    #[ORM\Column(name: 'Occupation', type: Types::STRING, length: 100, nullable: true)]
    private ?string $Occupation = null;

    #[ORM\Column(name: 'ContactNo', type: Types::STRING, length: 50, nullable: true)]
    private ?string $ContactNo = null;

    #[ORM\Column(name: 'IsDeceased', type: Types::BOOLEAN, nullable: true)]
    private ?bool $Deceased = null;

    #[ORM\Column(name: 'IsOFW', type: Types::BOOLEAN, nullable: true)]
    private ?bool $OFW = null;

    #[ORM\Column(length: 20, nullable: true)] private ?string $guardian_type = null;
    #[ORM\Column(length: 100, nullable: true)] private ?string $ofw_country = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $email = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $address = null;

    public function __set($name, $value) { if (property_exists($this, $name)) { $this->$name = $value; } }
}