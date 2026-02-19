<?php

namespace App\Entity\Audit;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait AuditFieldsTrait
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'audit_id')]
    private ?int $auditId = null;

    #[ORM\Column(name: 'emp_num', length: 50, nullable: true)]
    private ?string $empNum = null;

    #[ORM\Column(name: 'audit_date_time', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $auditDatetime = null;

    #[ORM\Column(name: 'host', length: 50, nullable: true)]
    private ?string $host = null;

    #[ORM\Column(name: 'audit_action', length: 20)]
    private ?string $auditAction = null;

    #[ORM\Column(name: 'remarks', type: Types::TEXT, nullable: true)]
    private ?string $remarks = null;

    public function getAuditId(): ?int { return $this->auditId; }

    public function setAuditMetadata(string $action, ?string $empNum, ?string $host, ?string $remarks = null): void
    {
        $this->auditAction = $action;
        $this->empNum = $empNum;
        $this->host = $host;
        $this->auditDatetime = new \DateTime();

        // Enforce the logic: if emp_num is null, it's a backdoor action.
        if ($this->empNum === null) {
            $this->remarks = 'BACKDOOR';
        } else {
            $this->remarks = $remarks; // defaults to null
        }
    }
}