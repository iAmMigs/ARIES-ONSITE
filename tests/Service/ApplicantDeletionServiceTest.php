<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\ApplicantBed;
use App\Entity\ApplicantBedRequirement;
use App\Repository\ApplicantBedRepository;
use App\Service\ApplicantDeletionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ApplicantDeletionServiceTest extends TestCase
{
    private EntityManagerInterface $emMock;
    private ApplicantBedRepository $repositoryMock;
    private string $projectDir;
    private ApplicantDeletionService $service;

    protected function setUp(): void
    {
        $this->emMock = $this->createMock(EntityManagerInterface::class);
        $this->repositoryMock = $this->createMock(ApplicantBedRepository::class);
        $this->projectDir = sys_get_temp_dir() . '/aries_test';
        
        // Mock directory structure for file tests
        @mkdir($this->projectDir . '/public/uploads/onsite-id-pics', 0777, true);
        
        $this->service = new ApplicantDeletionService(
            $this->emMock,
            $this->repositoryMock,
            $this->projectDir
        );
    }

    protected function tearDown(): void
    {
        // Clean up temp dir
        if (is_dir($this->projectDir)) {
            $this->deleteDirectory($this->projectDir);
        }
    }

    private function deleteDirectory(string $dir): void 
    {
        if (!file_exists($dir)) return;
        $files = array_diff(scandir($dir), ['.','..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->deleteDirectory("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }

    public function testDeleteApplicantRemovesFilesAndFlushesEm(): void
    {
        $applicant = new ApplicantBed();
        $applicant->setStudentNumber('TEST1234');
        
        $photoSlug = 'uploads/onsite-id-pics/TEST1234.jpg';
        $applicant->setPhotoSlug($photoSlug);
        
        file_put_contents($this->projectDir . '/public/' . $photoSlug, 'dummy content');
        
        $req = new ApplicantBedRequirement();
        $reqDocSlug = 'uploads/requirements/REQ123.pdf';
        @mkdir($this->projectDir . '/public/uploads/requirements', 0777, true);
        file_put_contents($this->projectDir . '/public/' . $reqDocSlug, 'dummy pdf');
        $req->setStoredFileName($reqDocSlug);
        
        $applicant->addRequirement($req);

        // Expect EM methods to be called
        $this->emMock->expects($this->once())
            ->method('remove')
            ->with($applicant);
            
        $this->emMock->expects($this->once())
            ->method('flush');

        $this->service->deleteApplicant($applicant);

        // Verify files are deleted
        $this->assertFileDoesNotExist($this->projectDir . '/public/' . $photoSlug);
        $this->assertFileDoesNotExist($this->projectDir . '/public/' . $reqDocSlug);
    }
}
