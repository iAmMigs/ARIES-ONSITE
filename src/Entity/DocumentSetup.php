<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'document_setup')]
class DocumentSetup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $documentName = null;

    #[ORM\Column(length: 100)]
    private ?string $slug = null;

    #[ORM\Column(length: 100)]
    private ?string $folderName = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isRequired = true;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $campus = null;

    public function getId(): ?int { return $this->id; }
    public function getDocumentName(): ?string { return $this->documentName; }
    public function setDocumentName(string $documentName): static { $this->documentName = $documentName; return $this; }
    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }
    public function getFolderName(): ?string { return $this->folderName; }
    public function setFolderName(string $folderName): static { $this->folderName = $folderName; return $this; }
    public function isRequired(): bool { return $this->isRequired; }
    public function setIsRequired(bool $isRequired): static { $this->isRequired = $isRequired; return $this; }
    public function getCampus(): ?string { return $this->campus; }
    public function setCampus(?string $campus): static { $this->campus = $campus; return $this; }
}