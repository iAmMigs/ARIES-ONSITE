<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class LookupBarangay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $barangayCode = null;

    #[ORM\Column(length: 255)]
    private ?string $barangayDesc = null;

    #[ORM\Column(length: 10)]
    private ?string $regionCode = null;

    #[ORM\Column(length: 10)]
    private ?string $provinceCode = null;

    #[ORM\Column(length: 10)]
    private ?string $cityCode = null;
    
    #[ORM\Column(nullable: true)]
    private ?int $zipcode = null;

    public function getBarangayCode(): ?int { return $this->barangayCode; }
    public function getBarangayDesc(): ?string { return $this->barangayDesc; }
    public function getCityCode(): ?string { return $this->cityCode; }
    public function getZipcode(): ?int { return $this->zipcode; }
}