<?php

namespace App\Entity;

use App\Repository\LookupRegionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LookupRegionRepository::class)]
#[ORM\Table(name: 'lookup_region')]
class LookupRegion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'regionCode', type: 'integer')]
    private ?int $regionCode = null;

    #[ORM\Column(name: 'regionDesc', type: 'string', length: 100, nullable: true)]
    private ?string $regionDesc = null;

    #[ORM\Column(name: 'ocatRegion', type: 'string', length: 10, nullable: true)]
    private ?string $ocatRegion = null;

    public function getRegionCode(): ?int { return $this->regionCode; }
    public function getRegionDesc(): ?string { return $this->regionDesc; }
    public function setRegionDesc(?string $regionDesc): self { $this->regionDesc = $regionDesc; return $this; }
    public function getOcatRegion(): ?string { return $this->ocatRegion; }
    public function setOcatRegion(?string $ocatRegion): self { $this->ocatRegion = $ocatRegion; return $this; }
}