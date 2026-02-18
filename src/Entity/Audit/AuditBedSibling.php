<?php

namespace App\Entity\Audit;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'audit_bed_siblings')]
class AuditBedSibling
{
    use AuditFieldsTrait;

    #[ORM\Column(name: 'student_number', type: Types::STRING, length: 20, nullable: true)]
    private ?string $studentNumber = null;

    #[ORM\Column(name: 'SiblingName', type: Types::STRING, length: 255, nullable: true)]
    private ?string $SiblingName = null;

    #[ORM\Column(name: 'School', type: Types::STRING, length: 255, nullable: true)]
    private ?string $School = null;

    #[ORM\Column(name: 'IsFeuStudent', type: Types::BOOLEAN, nullable: true)]
    private ?bool $IsFeuStudent = null;

    #[ORM\Column(name: 'FeuStudentNo', type: Types::STRING, length: 50, nullable: true)]
    private ?string $FeuStudentNo = null;

    public function __set($name, $value) { if (property_exists($this, $name)) { $this->$name = $value; } }
}