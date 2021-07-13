<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Mapper\Hydrator;

use Cycle\ORM\Mapper\Hydrator\ClassPropertiesExtractor;
use PHPUnit\Framework\TestCase;

class ClassPropertiesExtractorTest extends TestCase
{
    private ClassPropertiesExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extractor = new ClassPropertiesExtractor();
    }

    function testPropertyFromBaseClassShouldBeExtracted()
    {
        $class = User::class;

        $this->assertEquals([
            'hidden' => [
                'Cycle\ORM\Tests\Mapper\Hydrator\User' => [
                    'username' => 'username',
                    'email' => 'email',
                ]
            ],
            'visible' => [
                'id' => 'id'
            ]
        ], $this->extractor->extract($class));
    }

    function testPropertyFromExtendedClassShouldBeExtracted()
    {
        $class = SuperUser::class;

        $this->assertEquals([
            'hidden' => [
                'Cycle\ORM\Tests\Mapper\Hydrator\User' => [
                    'username' => 'username',
                    'email' => 'email',
                ],
                'Cycle\ORM\Tests\Mapper\Hydrator\ExtendedUser' => [
                    'isVerified' => 'isVerified',
                    'profileId' => 'profileId',
                ],
                'Cycle\ORM\Tests\Mapper\Hydrator\SuperUser' => [
                    'isAdmin' => 'isAdmin',
                ]
            ],
            'visible' => [
                'id' => 'id',
                'age' => 'age',
                'totalLogin' => 'totalLogin'
            ]
        ], $this->extractor->extract($class));
    }
}


class User
{
    public int $id;
    protected string $username;
    private string $email;
}

class ExtendedUser extends User
{
    protected bool $isVerified;
    private int $profileId;
    public int $age;
}

class SuperUser extends ExtendedUser
{
    private int $isAdmin;
    public int $totalLogin;
}