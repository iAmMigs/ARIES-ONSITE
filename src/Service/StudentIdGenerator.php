<?php

namespace App\Service;

use App\Entity\ApplicantBed;
use App\Repository\ApplicantBedRepository;

class StudentIdGenerator
{
    private ApplicantBedRepository $applicantBedRepository;

    public function __construct(ApplicantBedRepository $applicantBedRepository)
    {
        $this->applicantBedRepository = $applicantBedRepository;
    }

    public function generateStudentNumber(string $campus, string $yearStart): string
    {
        // 1. Determine Type Code (5 for BED as per instructions)
        $typeCode = '5'; 

        // 2. Build Prefix (Year + Type)
        // Example: 2025 + 5 = "20255"
        $prefix = $yearStart . $typeCode;

        // 3. Map Campus String to Entity Code
        // We must convert 'feu_alabang' to 'FALAB' (or FDIL) to query the database correctly
        $campusCode = ($campus === 'feu_alabang') ? ApplicantBed::CAMPUS_ALABANG : ApplicantBed::CAMPUS_DILIMAN;

        // 4. Find the latest student for this specific Campus, Year, and Type to increment series
        $latestStudent = $this->applicantBedRepository->findLatestForGeneration($campusCode, $prefix);

        $nextSeries = 1;

        if ($latestStudent) {
            $latestId = $latestStudent->getStudentNumber();
            // Remove prefix to get the raw series number
            // Prefix length is 5 (4 for year + 1 for type)
            // Ensure the ID actually has the length to avoid errors
            if (strlen($latestId) > strlen($prefix)) {
                $rawSeries = substr($latestId, strlen($prefix)); 
                $nextSeries = (int)$rawSeries + 1;
            }
        }

        // 5. Format based on Campus Rules
        if ($campus === 'feu_alabang') {
            // 11 Digits total. Prefix is 5 digits. Series needs to be 6 digits.
            // 20255 + 000001
            return $prefix . str_pad((string)$nextSeries, 6, '0', STR_PAD_LEFT);
        } elseif ($campus === 'feu_diliman') {
            // 9 Digits total. Prefix is 5 digits. Series needs to be 4 digits.
            // 20255 + 0001
            return $prefix . str_pad((string)$nextSeries, 4, '0', STR_PAD_LEFT);
        }

        // Fallback
        return $prefix . str_pad((string)$nextSeries, 6, '0', STR_PAD_LEFT);
    }
}