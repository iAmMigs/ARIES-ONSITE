<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class LookupProvince
{
    #[ORM\Id]
    #[ORM\Column]
    private ?string $provinceCode = null;

    #[ORM\Column(length: 255)]
    private ?string $provinceDesc = null;

    #[ORM\Column(length: 10)]
    private ?string $regionCode = null;

    public function getProvinceCode(): ?string { return $this->provinceCode; }
    public function getProvinceDesc(): ?string { return $this->provinceDesc; }
    public function getRegionCode(): ?string { return $this->regionCode; }
}