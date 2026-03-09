<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'tblcitizenship')]
class LookupCitizenship
{
    #[ORM\Id]
    #[ORM\Column(name: 'CitizenshipCode', type: 'string', length: 50)]
    private ?string $citizenshipCode = null;

    #[ORM\Column(name: 'CitizenshipName', type: 'string', length: 150)]
    private ?string $citizenshipName = null;

    #[ORM\Column(name: 'Country', type: 'string', length: 150)]
    private ?string $country = null;

    #[ORM\Column(name: 'country_sf_id', type: 'integer', nullable: true)]
    private ?int $countrySfId = null;

    public function getCitizenshipCode(): ?string
    {
        return $this->citizenshipCode;
    }

    public function getCitizenshipName(): ?string
    {
        return $this->citizenshipName;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }
}