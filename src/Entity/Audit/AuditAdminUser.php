<?php

declare(strict_types=1);

namespace App\Entity\Audit;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'audit_admin_users')]
class AuditAdminUser
{
    use AuditFieldsTrait;

    #[ORM\Column(name: 'target_emp_num', type: Types::INTEGER, nullable: false)]
    private ?int $targetEmpNum = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $roles = null;

    #[ORM\Column(name: 'first_name', length: 100, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(name: 'last_name', length: 100, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $campus = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $tier = null;

    #[ORM\Column(name: 'old_modules', type: Types::TEXT, nullable: true)]
    private ?string $oldModules = null;

    #[ORM\Column(name: 'new_modules', type: Types::TEXT, nullable: true)]
    private ?string $newModules = null;

    #[ORM\Column(name: 'can_manage_admins', type: Types::BOOLEAN, nullable: true)]
    private ?bool $canManageAdmins = null;

    #[ORM\Column(name: 'old_is_active', type: Types::BOOLEAN, nullable: true)]
    private ?bool $oldIsActive = null;

    #[ORM\Column(name: 'is_active', type: Types::BOOLEAN, nullable: true)]
    private ?bool $isActive = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getTargetEmpNum(): ?int { return $this->targetEmpNum; }
    public function getEmail(): ?string { return $this->email; }
    public function getFirstName(): ?string { return $this->firstName; }
    public function getLastName(): ?string { return $this->lastName; }
    public function getCampus(): ?string { return $this->campus; }
    public function getTier(): ?string { return $this->tier; }
    public function getOldModules(): ?string { return $this->oldModules; }
    public function getNewModules(): ?string { return $this->newModules; }
    public function canManageAdmins(): ?bool { return $this->canManageAdmins; }
    public function getOldIsActive(): ?bool { return $this->oldIsActive; }
    public function isActive(): ?bool { return $this->isActive; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
}

