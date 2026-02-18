<?php

namespace App\Entity;

use App\Repository\LookupCityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LookupCityRepository::class)]
#[ORM\Table(name: 'lookup_city')]
#[ORM\Index(columns: ['provinceCode'], name: 'idx_city_province')]
#[ORM\Index(columns: ['regionCode'], name: 'idx_city_region')]
class LookupCity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'cityCode', type: 'integer')]
    private ?int $cityCode = null;

    #[ORM\Column(name: 'provinceCode', type: 'integer', nullable: true)]
    private ?int $provinceCode = null;

    #[ORM\Column(name: 'regionCode', type: 'integer', nullable: true)]
    private ?int $regionCode = null;

    #[ORM\Column(name: 'cityDesc', type: 'string', length: 100, nullable: true)]
    private ?string $cityDesc = null;

    #[ORM\Column(name: 'zipcode', type: 'integer', nullable: true)]
    private ?int $zipcode = null;

    public function getCityCode(): ?int { return $this->cityCode; }
    public function getProvinceCode(): ?int { return $this->provinceCode; }
    public function setProvinceCode(?int $provinceCode): self { $this->provinceCode = $provinceCode; return $this; }
    public function getRegionCode(): ?int { return $this->regionCode; }
    public function setRegionCode(?int $regionCode): self { $this->regionCode = $regionCode; return $this; }
    public function getCityDesc(): ?string { return $this->cityDesc; }
    public function setCityDesc(?string $cityDesc): self { $this->cityDesc = $cityDesc; return $this; }
    public function getZipcode(): ?int { return $this->zipcode; }
    public function setZipcode(?int $zipcode): self { $this->zipcode = $zipcode; return $this; }
}