<?php

namespace App\Entity;

use App\Repository\LookupBarangayRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LookupBarangayRepository::class)]
#[ORM\Table(name: 'lookup_barangay')]
#[ORM\Index(columns: ['cityCode'], name: 'idx_barangay_city')]
#[ORM\Index(columns: ['provinceCode'], name: 'idx_barangay_province')]
#[ORM\Index(columns: ['regionCode'], name: 'idx_barangay_region')]
class LookupBarangay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'barangayCode', type: 'integer')]
    private ?int $barangayCode = null;

    #[ORM\Column(name: 'cityCode', type: 'integer')]
    private int $cityCode;

    #[ORM\Column(name: 'provinceCode', type: 'integer', nullable: true)]
    private ?int $provinceCode = null;

    #[ORM\Column(name: 'regionCode', type: 'integer', nullable: true)]
    private ?int $regionCode = null;

    #[ORM\Column(name: 'barangayDesc', type: 'string', length: 100, nullable: true)]
    private ?string $barangayDesc = null;

    #[ORM\Column(name: 'zipcode', type: 'integer', nullable: true)]
    private ?int $zipcode = null;

    public function getBarangayCode(): ?int { return $this->barangayCode; }
    public function getCityCode(): int { return $this->cityCode; }
    public function setCityCode(int $cityCode): self { $this->cityCode = $cityCode; return $this; }
    public function getProvinceCode(): ?int { return $this->provinceCode; }
    public function setProvinceCode(?int $provinceCode): self { $this->provinceCode = $provinceCode; return $this; }
    public function getRegionCode(): ?int { return $this->regionCode; }
    public function setRegionCode(?int $regionCode): self { $this->regionCode = $regionCode; return $this; }
    public function getBarangayDesc(): ?string { return $this->barangayDesc; }
    public function setBarangayDesc(?string $barangayDesc): self { $this->barangayDesc = $barangayDesc; return $this; }
    public function getZipcode(): ?int { return $this->zipcode; }
    public function setZipcode(?int $zipcode): self { $this->zipcode = $zipcode; return $this; }
}