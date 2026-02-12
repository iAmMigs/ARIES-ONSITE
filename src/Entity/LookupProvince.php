<?php

namespace App\Entity;

use App\Repository\LookupProvinceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LookupProvinceRepository::class)]
#[ORM\Table(name: 'lookup_province')]
#[ORM\Index(columns: ['regionCode'], name: 'idx_province_region')]
class LookupProvince
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'provinceCode', type: 'integer')]
    private ?int $provinceCode = null;

    #[ORM\Column(name: 'regionCode', type: 'integer', nullable: true)]
    private ?int $regionCode = null;

    #[ORM\Column(name: 'provinceDesc', type: 'string', length: 100, nullable: true)]
    private ?string $provinceDesc = null;

    public function getProvinceCode(): ?int { return $this->provinceCode; }
    public function getRegionCode(): ?int { return $this->regionCode; }
    public function setRegionCode(?int $regionCode): self { $this->regionCode = $regionCode; return $this; }
    public function getProvinceDesc(): ?string { return $this->provinceDesc; }
    public function setProvinceDesc(?string $provinceDesc): self { $this->provinceDesc = $provinceDesc; return $this; }
}