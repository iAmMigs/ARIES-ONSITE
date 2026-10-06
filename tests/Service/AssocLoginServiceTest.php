<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\AssocLoginService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\TestCase;

class AssocLoginServiceTest extends TestCase
{
    public function testVerifyPasswordValid(): void
    {
        $connection = $this->createMock(Connection::class);
        $service = new AssocLoginService($connection);

        $this->assertTrue($service->verifyPassword('test', '098f6bcd4621d373cade4e832627b4f6'));
    }

    public function testVerifyPasswordInvalid(): void
    {
        $connection = $this->createMock(Connection::class);
        $service = new AssocLoginService($connection);

        $this->assertFalse($service->verifyPassword('wrong', '098f6bcd4621d373cade4e832627b4f6'));
    }

    public function testFindByEmployeeIdReturnsRowWhenFound(): void
    {
        $connection = $this->createMock(Connection::class);
        $result = $this->createMock(Result::class);

        $expectedRow = [
            'employee_id' => '123456789',
            'user_name' => 'test',
            'pass_word' => '098f6bcd4621d373cade4e832627b4f6',
        ];

        $result->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn($expectedRow);

        $connection->expects($this->once())
            ->method('executeQuery')
            ->with(
                $this->stringContains('WHERE employee_id = :id'),
                ['id' => '123456789']
            )
            ->willReturn($result);

        $service = new AssocLoginService($connection);
        $data = $service->findByEmployeeId('123456789');

        $this->assertSame($expectedRow, $data);
    }

    public function testFindByEmployeeIdReturnsNullWhenNotFound(): void
    {
        $connection = $this->createMock(Connection::class);
        $result = $this->createMock(Result::class);

        $result->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn(false);

        $connection->expects($this->once())
            ->method('executeQuery')
            ->willReturn($result);

        $service = new AssocLoginService($connection);
        $this->assertNull($service->findByEmployeeId('999999999'));
    }

    public function testFindByIdentifierQueriesUsernameOrEmployeeId(): void
    {
        $connection = $this->createMock(Connection::class);
        $result = $this->createMock(Result::class);

        $expectedRow = [
            'employee_id' => '123456789',
            'user_name' => 'test',
            'pass_word' => '098f6bcd4621d373cade4e832627b4f6',
        ];

        $result->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn($expectedRow);

        $connection->expects($this->once())
            ->method('executeQuery')
            ->with(
                $this->stringContains('WHERE user_name = :identifier OR employee_id = :identifier'),
                ['identifier' => 'test']
            )
            ->willReturn($result);

        $service = new AssocLoginService($connection);
        $data = $service->findByIdentifier('test');

        $this->assertSame($expectedRow, $data);
    }
}
