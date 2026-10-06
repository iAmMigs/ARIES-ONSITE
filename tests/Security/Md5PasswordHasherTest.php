<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\Md5PasswordHasher;
use PHPUnit\Framework\TestCase;

class Md5PasswordHasherTest extends TestCase
{
    private Md5PasswordHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new Md5PasswordHasher();
    }

    public function testHashGeneratesCorrectMd5(): void
    {
        $hash = $this->hasher->hash('test');
        $this->assertSame('098f6bcd4621d373cade4e832627b4f6', $hash);
    }

    public function testVerifyReturnsTrueForCorrectPassword(): void
    {
        $storedHash = '098f6bcd4621d373cade4e832627b4f6';
        $this->assertTrue($this->hasher->verify($storedHash, 'test'));
    }

    public function testVerifyReturnsFalseForIncorrectPassword(): void
    {
        $storedHash = '098f6bcd4621d373cade4e832627b4f6';
        $this->assertFalse($this->hasher->verify($storedHash, 'wrong_password'));
    }

    public function testNeedsRehashReturnsFalse(): void
    {
        $this->assertFalse($this->hasher->needsRehash('098f6bcd4621d373cade4e832627b4f6'));
    }
}
