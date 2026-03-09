<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sf_lookup_religion')]
class LookupReligion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'religion_id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'religion_name', type: 'string', length: 200)]
    private ?string $religionName = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReligionName(): ?string
    {
        return $this->religionName;
    }

    public function setReligionName(string $religionName): static
    {
        $this->religionName = $religionName;
        return $this;
    }
}