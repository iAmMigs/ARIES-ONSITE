<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AdminUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: AdminUserRepository::class)]
#[ORM\Table(name: 'admin_users')]
class AdminUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(name: 'emp_num', options: ['unsigned' => true])]
    private ?int $empNum = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicture = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $campus = null;

    private ?string $username = null;

    public const TIER_MASTER = 'master_admin';
    public const TIER_SENIOR = 'senior_admin';
    public const TIER_STAFF  = 'staff_admin';

    public const TIERS = [
        self::TIER_MASTER => 'Master Admin',
        self::TIER_SENIOR => 'Senior Admin',
        self::TIER_STAFF  => 'Staff Admin',
    ];

    #[ORM\Column(length: 30, options: ['default' => 'staff_admin'])]
    private string $tier = self::TIER_STAFF;

    #[ORM\Column(options: ['default' => false])]
    private bool $canManageAdmins = false;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getEmpNum(): ?int { return $this->empNum; }
    public function setEmpNum(int $empNum): static { $this->empNum = $empNum; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getUserIdentifier(): string { return (string) $this->email; }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_ADMIN';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function eraseCredentials(): void { }

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(string $firstName): static { $this->firstName = $firstName; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(string $lastName): static { $this->lastName = $lastName; return $this; }

    public function getUsername(): ?string { return $this->username; }
    public function setUsername(?string $username): static { $this->username = $username; return $this; }

    public function getProfilePicture(): ?string { return $this->profilePicture; }
    public function setProfilePicture(?string $profilePicture): static { $this->profilePicture = $profilePicture; return $this; }

    public function getCampus(): ?string { return $this->campus; }
    public function setCampus(?string $campus): static { $this->campus = $campus; return $this; }

    public function getTier(): string { return $this->tier; }
    public function setTier(string $tier): static
    {
        if (!array_key_exists($tier, self::TIERS)) {
            throw new \InvalidArgumentException(sprintf('Invalid tier: "%s"', $tier));
        }
        $this->tier = $tier;
        return $this;
    }

    public function getTierLabel(): string
    {
        return self::TIERS[$this->tier] ?? 'Staff Admin';
    }

    public function getTierLevel(): int
    {
        return match ($this->tier) {
            self::TIER_MASTER => 1,
            self::TIER_SENIOR => 2,
            self::TIER_STAFF  => 3,
            default           => 4,
        };
    }

    public function isMasterAdmin(): bool { return $this->tier === self::TIER_MASTER; }
    public function isSeniorAdmin(): bool { return $this->tier === self::TIER_SENIOR; }
    public function isStaffAdmin(): bool  { return $this->tier === self::TIER_STAFF; }

    public function canManageAdmins(): bool
    {
        return $this->isMasterAdmin() || $this->canManageAdmins;
    }

    public function setCanManageAdmins(bool $canManageAdmins): static
    {
        $this->canManageAdmins = $canManageAdmins;
        return $this;
    }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function canCreateTier(string $targetTier): bool
    {
        if ($this->isMasterAdmin()) {
            return $targetTier === self::TIER_SENIOR || $targetTier === self::TIER_STAFF;
        }

        if ($this->isSeniorAdmin() && $this->canManageAdmins()) {
            return $targetTier === self::TIER_STAFF;
        }

        return false;
    }

    public function canDeleteAdmin(self $target): bool
    {
        if ($target === $this) {
            return false;
        }

        if ($target->getEmpNum() !== null && $this->getEmpNum() !== null && $target->getEmpNum() === $this->getEmpNum()) {
            return false;
        }

        if ($target->isMasterAdmin()) {
            return false;
        }

        if ($this->getCampus() !== null && $target->getCampus() !== null && $this->getCampus() !== $target->getCampus()) {
            return false;
        }

        if ($this->isMasterAdmin()) {
            return true;
        }

        if ($this->isSeniorAdmin() && $this->canManageAdmins()) {
            return $target->isStaffAdmin();
        }

        return false;
    }

    public function getFullName(): string {
        return $this->firstName . ' ' . $this->lastName;
    }
}