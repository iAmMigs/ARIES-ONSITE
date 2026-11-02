<?php

declare(strict_types=1);

namespace App\Entity\Audit;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'audit_document_setup')]
class AuditDocumentSetup
{
    use AuditFieldsTrait;

    #[ORM\Column(name: 'document_setup_id', type: Types::INTEGER, nullable: false)]
    private ?int $documentSetupId = null;

    #[ORM\Column(name: 'document_name', length: 255, nullable: true)]
    private ?string $documentName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(name: 'folder_name', length: 100, nullable: true)]
    private ?string $folderName = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $campus = null;

    #[ORM\Column(name: 'allowed_file_types', length: 255, nullable: true)]
    private ?string $allowedFileTypes = null;

    #[ORM\Column(name: 'student_type', length: 20, nullable: true)]
    private ?string $studentType = null;

    #[ORM\Column(name: 'nationality_type', length: 20, nullable: true)]
    private ?string $nationalityType = null;

    #[ORM\Column(name: 'grade_levels', type: Types::TEXT, nullable: true)]
    private ?string $gradeLevels = null;

    public function getDocumentSetupId(): ?int { return $this->documentSetupId; }
    public function getDocumentName(): ?string { return $this->documentName; }
    public function getSlug(): ?string { return $this->slug; }
    public function getFolderName(): ?string { return $this->folderName; }
    public function getCampus(): ?string { return $this->campus; }
    public function getAllowedFileTypes(): ?string { return $this->allowedFileTypes; }
    public function getStudentType(): ?string { return $this->studentType; }
    public function getNationalityType(): ?string { return $this->nationalityType; }
    public function getGradeLevels(): ?string { return $this->gradeLevels; }
}

