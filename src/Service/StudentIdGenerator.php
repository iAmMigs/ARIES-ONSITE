<?php

namespace App\Service;

use App\Repository\StudentProfileRepository;

class StudentIdGenerator
{
    private StudentProfileRepository $studentRepository;

    public function __construct(StudentProfileRepository $studentRepository)
    {
        $this->studentRepository = $studentRepository;
    }

    public function generateStudentNumber(string $campus, string $yearStart): string
    {
        // 1. Determine Type Code (5 for BED as per instructions)
        $typeCode = '5'; 

        // 2. Build Prefix (Year + Type)
        // Example: 2025 + 5 = "20255"
        $prefix = $yearStart . $typeCode;

        // 3. Find the latest student for this specific Campus, Year, and Type to increment series
        // We use the repository method created earlier
        $latestStudent = $this->studentRepository->findLatestForGeneration($campus, $yearStart, $typeCode);

        $nextSeries = 1;

        if ($latestStudent) {
            $latestId = $latestStudent->getStudentNumber();
            // Remove prefix to get the raw series number
            // Prefix length is 5 (4 for year + 1 for type)
            $rawSeries = substr($latestId, 5); 
            $nextSeries = (int)$rawSeries + 1;
        }

        // 4. Format based on Campus Rules
        if ($campus === 'feu_alabang') {
            // 11 Digits total. Prefix is 5 digits. Series needs to be 6 digits.
            // 20255 + 000001
            return $prefix . str_pad($nextSeries, 6, '0', STR_PAD_LEFT);
        } elseif ($campus === 'feu_diliman') {
            // 9 Digits total. Prefix is 5 digits. Series needs to be 4 digits.
            // 20255 + 0001
            return $prefix . str_pad($nextSeries, 4, '0', STR_PAD_LEFT);
        }

        // Fallback (Should not happen if validation is strict)
        return $prefix . str_pad($nextSeries, 6, '0', STR_PAD_LEFT);
    }
}