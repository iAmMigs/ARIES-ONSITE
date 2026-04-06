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
}