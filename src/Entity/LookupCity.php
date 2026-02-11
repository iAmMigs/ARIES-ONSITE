<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class LookupCity
{
    #[ORM\Id]
    #[ORM\Column]
    private ?string $cityCode = null;

    #[ORM\Column(length: 255)]
    private ?string $cityDesc = null;

    #[ORM\Column(length: 10)]
    private ?string $provinceCode = null;

    #[ORM\Column(length: 10)]
    private ?string $regionCode = null;

    public function getCityCode(): ?string { return $this->cityCode; }
    public function getCityDesc(): ?string { return $this->cityDesc; }
    public function getProvinceCode(): ?string { return $this->provinceCode; }
}