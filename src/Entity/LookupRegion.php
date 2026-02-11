<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class LookupRegion
{
    #[ORM\Id]
    #[ORM\Column]
    private ?string $regionCode = null; // e.g. "01"

    #[ORM\Column(length: 255)]
    private ?string $regionDesc = null;

    public function getRegionCode(): ?string { return $this->regionCode; }
    public function getRegionDesc(): ?string { return $this->regionDesc; }
}