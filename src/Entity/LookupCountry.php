<?php

namespace App\Entity;

use App\Repository\LookupCountryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LookupCountryRepository::class)]
#[ORM\Table(name: 'lookup_country')]
class LookupCountry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'country_id')]
    private ?int $id = null;

    #[ORM\Column(name: 'country_name', type: Types::STRING, length: 255)]
    private ?string $countryName = null;

    #[ORM\Column(name: 'country_sf_id', type: Types::STRING, length: 50, nullable: true)]
    private ?string $countrySfId = null;

    #[ORM\Column(name: 'iso', type: Types::STRING, length: 2, nullable: true)]
    private ?string $iso = null;

    #[ORM\Column(name: 'nicename', type: Types::STRING, length: 80, nullable: true)]
    private ?string $nicename = null;

    #[ORM\Column(name: 'iso3', type: Types::STRING, length: 3, nullable: true)]
    private ?string $iso3 = null;

    #[ORM\Column(name: 'numcode', type: Types::SMALLINT, nullable: true)]
    private ?int $numcode = null;

    #[ORM\Column(name: 'phonecode', type: Types::INTEGER, nullable: true)]
    private ?int $phonecode = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCountryName(): ?string
    {
        return $this->countryName;
    }

    public function setCountryName(string $countryName): static
    {
        $this->countryName = $countryName;
        return $this;
    }

    public function getCountrySfId(): ?string
    {
        return $this->countrySfId;
    }

    public function setCountrySfId(?string $countrySfId): static
    {
        $this->countrySfId = $countrySfId;
        return $this;
    }

    public function getIso(): ?string
    {
        return $this->iso;
    }

    public function setIso(?string $iso): static
    {
        $this->iso = $iso ? strtoupper($iso) : null;
        return $this;
    }

    public function getNicename(): ?string
    {
        return $this->nicename;
    }

    public function setNicename(?string $nicename): static
    {
        $this->nicename = $nicename;
        return $this;
    }

    public function getIso3(): ?string
    {
        return $this->iso3;
    }

    public function setIso3(?string $iso3): static
    {
        $this->iso3 = $iso3;
        return $this;
    }

    public function getNumcode(): ?int
    {
        return $this->numcode;
    }

    public function setNumcode(?int $numcode): static
    {
        $this->numcode = $numcode;
        return $this;
    }

    public function getPhonecode(): ?int
    {
        return $this->phonecode;
    }

    public function setPhonecode(?int $phonecode): static
    {
        $this->phonecode = $phonecode;
        return $this;
    }

    /**
     * Returns the 2-letter ISO flag emoji (e.g. PH -> 🇵🇭, US -> 🇺🇸)
     */
    public function getFlagEmoji(): string
    {
        if (!$this->iso || strlen($this->iso) !== 2) {
            return '🌐';
        }
        $iso = strtoupper($this->iso);
        $ordA = ord('A');
        $ordZ = ord('Z');
        $c1 = ord($iso[0]);
        $c2 = ord($iso[1]);
        if ($c1 < $ordA || $c1 > $ordZ || $c2 < $ordA || $c2 > $ordZ) {
            return '🌐';
        }
        $first = mb_chr(0x1F1E6 + ($c1 - $ordA), 'UTF-8');
        $second = mb_chr(0x1F1E6 + ($c2 - $ordA), 'UTF-8');
        return ($first && $second) ? ($first . $second) : '🌐';
    }

    /**
     * Returns the formatted dial code with + prefix (e.g. +63)
     */
    public function getFormattedPhonecode(): string
    {
        if ($this->phonecode === null || $this->phonecode <= 0) {
            return '';
        }
        return '+' . $this->phonecode;
    }
}