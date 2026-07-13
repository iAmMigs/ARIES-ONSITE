<?php

namespace App\Entity\Audit;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'audit_bed_passports')]
class AuditBedPassport
{
    use AuditFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'original_passport_id', type: Types::INTEGER, nullable: true)]
    private ?int $originalId = null;

    #[ORM\Column(name: 'student_number', type: Types::STRING, length: 20, nullable: true)]
    private ?string $studentNumber = null;

    #[ORM\Column(name: 'passport_number', type: Types::STRING, length: 50, nullable: true)]
    private ?string $passportNumber = null;

    #[ORM\Column(name: 'country_of_issue', type: Types::STRING, length: 100, nullable: true)]
    private ?string $countryOfIssue = null;

    #[ORM\Column(name: 'date_issued', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateIssued = null;

    #[ORM\Column(name: 'expiration_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expirationDate = null;

    public function getId(): ?int { return $this->id; }

    public function __set($name, $value) {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        }
    }
}
