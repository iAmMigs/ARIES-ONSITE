<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\ApplicantBed;
use App\Entity\SchoolYear;
use App\Repository\ApplicantBedRepository;
use App\Service\StudentIdGenerator;
use PHPUnit\Framework\TestCase;

class StudentIdGeneratorTest extends TestCase
{
    private ApplicantBedRepository $repositoryMock;
    private StudentIdGenerator $generator;

    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(ApplicantBedRepository::class);
        $this->generator = new StudentIdGenerator($this->repositoryMock);
    }

    public function testGenerateStudentNumberForAlabangNoPrevious(): void
    {
        $schoolYear = new SchoolYear();
        $schoolYear->setYearStart('2025');

        $this->repositoryMock->expects($this->once())
            ->method('findLatestForGeneration')
            ->with(ApplicantBed::CAMPUS_ALABANG, '20255')
            ->willReturn(null);

        // Alabang uses 6-digit series -> 20255000001
        $result = $this->generator->generateStudentNumber('feu_alabang', $schoolYear);
        
        $this->assertSame('20255000001', $result);
    }

    public function testGenerateStudentNumberForDilimanWithPrevious(): void
    {
        $schoolYear = new SchoolYear();
        $schoolYear->setYearStart('2025');

        $previousApplicant = new ApplicantBed();
        $previousApplicant->setStudentNumber('202550042');

        $this->repositoryMock->expects($this->once())
            ->method('findLatestForGeneration')
            ->with(ApplicantBed::CAMPUS_DILIMAN, '20255')
            ->willReturn($previousApplicant);

        // Diliman uses 4-digit series -> 202550043
        $result = $this->generator->generateStudentNumber('feu_diliman', $schoolYear);
        
        $this->assertSame('202550043', $result);
    }

    public function testGenerateStudentNumberForDilimanInternationalNoPrevious(): void
    {
        $schoolYear = new SchoolYear();
        $schoolYear->setYearStart('2026');

        $this->repositoryMock->expects($this->once())
            ->method('findLatestForGeneration')
            ->with(ApplicantBed::CAMPUS_DILIMAN, '2026-')
            ->willReturn(null);

        // International Diliman uses YYYY-XXXXXX -> 2026-000001
        $result = $this->generator->generateStudentNumber('feu_diliman', $schoolYear, true);
        
        $this->assertSame('2026-000001', $result);
    }

    public function testGenerateStudentNumberForDilimanInternationalWithPrevious(): void
    {
        $schoolYear = new SchoolYear();
        $schoolYear->setYearStart('2026');

        $previousApplicant = new ApplicantBed();
        $previousApplicant->setStudentNumber('2026-000123');

        $this->repositoryMock->expects($this->once())
            ->method('findLatestForGeneration')
            ->with(ApplicantBed::CAMPUS_DILIMAN, '2026-')
            ->willReturn($previousApplicant);

        // International Diliman incremented -> 2026-000124
        $result = $this->generator->generateStudentNumber('feu_diliman', $schoolYear, true);
        
        $this->assertSame('2026-000124', $result);
    }
}
