<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\ApplicantBedRequirement;
use PHPUnit\Framework\TestCase;

class ApplicantBedRequirementTest extends TestCase
{
    public function testGetFilePathMatchesStoredFileName(): void
    {
        $requirement = new ApplicantBedRequirement();
        $this->assertNull($requirement->getStoredFileName());
        $this->assertNull($requirement->getFilePath());

        $requirement->setStoredFileName('uploads/requirements/psa_birth_certificate.pdf');
        $this->assertSame('uploads/requirements/psa_birth_certificate.pdf', $requirement->getStoredFileName());
        $this->assertSame('uploads/requirements/psa_birth_certificate.pdf', $requirement->getFilePath());
    }

    public function testSlugAndRequirementName(): void
    {
        $requirement = new ApplicantBedRequirement();
        $requirement->setSlug('psa-birth-cert');
        $requirement->setRequirement('PSA Birth Certificate');

        $this->assertSame('psa-birth-cert', $requirement->getSlug());
        $this->assertSame('PSA Birth Certificate', $requirement->getRequirement());
    }
}
