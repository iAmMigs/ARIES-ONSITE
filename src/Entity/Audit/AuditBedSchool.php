<?php

namespace App\Entity\Audit;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'audit_bed_schools')]
class AuditBedSchool
{
    use AuditFieldsTrait;

    #[ORM\Column(name: 'student_number', type: Types::STRING, length: 20, nullable: true)]
    private ?string $studentNumber = null;

    #[ORM\Column(name: 'Level', type: Types::STRING, length: 1, nullable: true)]
    private ?string $Level = null;

    #[ORM\Column(name: 'School', type: Types::STRING, length: 255, nullable: true)]
    private ?string $School = null;

    #[ORM\Column(name: 'SchoolYear', type: Types::STRING, length: 20, nullable: true)]
    private ?string $schoolYear = null;

    #[ORM\Column(name: 'SchoolType', type: Types::STRING, length: 50, nullable: true)]
    private ?string $schoolType = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)] private ?bool $is_international = null;
    #[ORM\Column(length: 100, nullable: true)] private ?string $country = null;
    #[ORM\Column(length: 100, nullable: true)] private ?string $region = null;
    #[ORM\Column(length: 100, nullable: true)] private ?string $province = null;
    #[ORM\Column(length: 100, nullable: true)] private ?string $city = null;

    public function __set($name, $value) { if (property_exists($this, $name)) { $this->$name = $value; } }
}