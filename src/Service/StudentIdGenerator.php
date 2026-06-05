<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ApplicantBed;
use App\Entity\SchoolYear;
use App\Repository\ApplicantBedRepository;

/**
 * Generates unique, sequential student ID numbers for BED applicants.
 *
 * ID format is campus-specific and tied to the active school year:
 *
 *   FEU Alabang: {yearStart}{typeCode}{6-digit series}  — e.g. 202550000001
 *   FEU Diliman: {yearStart}{typeCode}{4-digit series}  — e.g. 2025500001
 *
 * The type code is always '5' for BED programs.
 * The series counter resets for every new school year per campus.
 */
class StudentIdGenerator
{
    /** BED type code embedded in all generated student IDs. */
    private const TYPE_CODE = '5';

    public function __construct(
        private readonly ApplicantBedRepository $applicantBedRepository
    ) {}

    /**
     * Generates the next available student number for the given campus and school year.
     *
     * @param string     $campus     Raw campus form value ('feu_alabang' or 'feu_diliman')
     * @param SchoolYear $schoolYear The currently active school year for that campus
     *
     * @return string The generated student number
     */
    public function generateStudentNumber(string $campus, SchoolYear $schoolYear): string
    {
        // Build the 5-character prefix: 4-digit year + type code '5'
        // e.g. SY2526 (yearStart=2025) → prefix "20255"
        $prefix = $schoolYear->getYearStart() . self::TYPE_CODE;

        // Map the form campus value to the entity campus code for querying
        $campusCode = ($campus === 'feu_alabang')
            ? ApplicantBed::CAMPUS_ALABANG
            : ApplicantBed::CAMPUS_DILIMAN;

        // Find the highest existing student number for this campus + SY prefix
        // to determine the next sequential series number
        $latestStudent = $this->applicantBedRepository->findLatestForGeneration($campusCode, $prefix);

        $nextSeries = 1;

        if ($latestStudent) {
            $latestId = $latestStudent->getStudentNumber();
            // Strip the prefix to isolate the raw series digits, then increment
            if (strlen($latestId) > strlen($prefix)) {
                $nextSeries = (int) substr($latestId, strlen($prefix)) + 1;
            }
        }

        // Pad the series based on campus-specific ID length requirements:
        // Alabang → 6-digit series (total 11 chars with prefix)
        // Diliman → 4-digit series (total 9 chars with prefix)
        $seriesPadLength = ($campus === 'feu_alabang') ? 6 : 4;

        $generatedId = $prefix . str_pad((string) $nextSeries, $seriesPadLength, '0', STR_PAD_LEFT);
        error_log("StudentIdGenerator: Generated $generatedId for campus $campus");
        return $generatedId;
    }
}