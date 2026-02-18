<?php

namespace App\Entity\Audit;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait AuditFieldsTrait
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $auditId = null;

    #[ORM\Column(length: 20)]
    private ?string $auditAction = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $empNum = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $auditDatetime = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $host = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $remarks = null;

    public function getAuditId(): ?int { return $this->auditId; }

    public function setAuditMetadata(string $action, ?string $user, ?string $ip, ?string $remarks = null): void
    {
        $this->auditAction = $action;
        $this->empNum = $user;
        $this->host = $ip;
        $this->remarks = $remarks;
        $this->auditDatetime = new \DateTime();
    }
}