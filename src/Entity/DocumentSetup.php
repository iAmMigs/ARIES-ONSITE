<?php

declare(strict_types=1);

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

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $studentType = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $nationalityType = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $gradeLevels = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $campus = null;

    /**
     * Defines the acceptable file formats for the document requirement.
     * Utilized to validate uploads and restrict file pickers dynamically.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $allowedFileTypes = null;

    public function getId(): ?int { return $this->id; }
    
    public function getDocumentName(): ?string { return $this->documentName; }
    
    public function setDocumentName(string $documentName): static { 
        $this->documentName = $documentName; 
        return $this; 
    }
    
    public function getSlug(): ?string { return $this->slug; }
    
    public function setSlug(string $slug): static { 
        $this->slug = $slug; 
        return $this; 
    }
    
    public function getFolderName(): ?string { return $this->folderName; }
    
    public function setFolderName(string $folderName): static { 
        $this->folderName = $folderName; 
        return $this; 
    }
    
    public function getStudentType(): ?string { return $this->studentType; }
    
    public function setStudentType(?string $studentType): static { 
        $this->studentType = $studentType; 
        return $this; 
    }

    public function getNationalityType(): ?string { return $this->nationalityType; }
    
    public function setNationalityType(?string $nationalityType): static { 
        $this->nationalityType = $nationalityType; 
        return $this; 
    }

    public function getGradeLevels(): ?array { return $this->gradeLevels; }
    
    public function setGradeLevels(?array $gradeLevels): static { 
        $this->gradeLevels = $gradeLevels; 
        return $this; 
    }
    
    public function getCampus(): ?string { return $this->campus; }
    
    public function setCampus(?string $campus): static { 
        $this->campus = $campus; 
        return $this; 
    }

    public function getAllowedFileTypes(): ?string { return $this->allowedFileTypes; }

    public function setAllowedFileTypes(?string $allowedFileTypes): static {
        $this->allowedFileTypes = $allowedFileTypes;
        return $this;
    }
}