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

    #[ORM\Column(name: 'YearEnd', type: Types::INTEGER, nullable: true)]
    private ?int $YearEnd = null;

    public function __set($name, $value) { if (property_exists($this, $name)) { $this->$name = $value; } }
}