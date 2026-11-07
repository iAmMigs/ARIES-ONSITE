<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'campus_settings')]
#[ORM\UniqueConstraint(name: 'uniq_campus_setting_key', columns: ['campus', 'setting_key'])]
class CampusSetting
{
    public const SETTING_MAX_SURVEY_CHOICES = 'max_survey_choices';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $campus = null;

    #[ORM\Column(name: 'setting_key', length: 100)]
    private ?string $settingKey = null;

    #[ORM\Column(name: 'setting_value', length: 255, nullable: true)]
    private ?string $settingValue = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getCampus(): ?string { return $this->campus; }
    public function setCampus(string $campus): static { $this->campus = $campus; return $this; }

    public function getSettingKey(): ?string { return $this->settingKey; }
    public function setSettingKey(string $settingKey): static { $this->settingKey = $settingKey; return $this; }

    public function getSettingValue(): ?string { return $this->settingValue; }
    public function setSettingValue(?string $settingValue): static
    {
        $this->settingValue = $settingValue;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
}

