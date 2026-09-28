<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\ApplicantBedPassport;
use PHPUnit\Framework\TestCase;

class ApplicantBedPassportTest extends TestCase
{
    public function testNullableDates(): void
    {
        $passport = new ApplicantBedPassport();
        $this->assertNull($passport->getDateIssued());
        $this->assertNull($passport->getExpirationDate());

        // Should allow explicitly setting null
        $passport->setDateIssued(null);
        $passport->setExpirationDate(null);
        $this->assertNull($passport->getDateIssued());
        $this->assertNull($passport->getExpirationDate());

        // Should allow setting DateTime objects
        $issue = new \DateTime('2024-01-15');
        $expiry = new \DateTime('2034-01-15');
        $passport->setDateIssued($issue);
        $passport->setExpirationDate($expiry);
        $this->assertSame($issue, $passport->getDateIssued());
        $this->assertSame($expiry, $passport->getExpirationDate());
    }

    public function testPassportFields(): void
    {
        $passport = new ApplicantBedPassport();
        $passport->setPassportNumber('P1234567A');
        $passport->setCountryOfIssue('Philippines');

        $this->assertSame('P1234567A', $passport->getPassportNumber());
        $this->assertSame('Philippines', $passport->getCountryOfIssue());
    }
}
