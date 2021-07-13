<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Mapper\ProxyEntityMapper;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Tests\Mapper\BaseMapperTest;

class EntityHydrationTest extends BaseMapperTest
{
    public const DRIVER = 'sqlite';

    public function setUp(): void
    {
        parent::setUp();

        $this->orm = $this->withSchema(
            new Schema(
                [
                    User::class => [
                        Schema::MAPPER => Mapper::class,
                        Schema::DATABASE => 'default',
                        Schema::TABLE => 'user',
                        Schema::PRIMARY_KEY => 'uuid',
                        Schema::COLUMNS => ['id', 'username', 'email'],
                        Schema::TYPECAST => [],
                        Schema::SCHEMA => [],
                        Schema::RELATIONS => []
                    ],
                    ExtendedUser::class => [
                        Schema::MAPPER => Mapper::class,
                        Schema::DATABASE => 'default',
                        Schema::TABLE => 'user',
                        Schema::PRIMARY_KEY => 'uuid',
                        Schema::COLUMNS => ['id', 'username', 'email', 'isVerified', 'profileId'],
                        Schema::TYPECAST => [],
                        Schema::SCHEMA => [],
                        Schema::RELATIONS => []
                    ]
                ]
            )
        );
    }

    function testDataForBaseClassShouldBeExtracted()
    {
        $user = new User(123, 'guest', 'guest@site.com');

        $mapper = $this->orm->getMapper($user);

        $this->assertEquals([
            'id' => 123,
            'username' => 'guest',
            'email' => 'guest@site.com'
        ], $mapper->extract($user));
    }

    function testDataForExtendedClassShouldBeExtracted()
    {
        $user = new ExtendedUser(123, 'guest', 'guest@site.com', true, 234);

        $mapper = $this->orm->getMapper($user);

        $this->assertEquals([
            'id' => 123,
            'username' => 'guest',
            'email' => 'guest@site.com',
            'isVerified' => true,
            'profileId' => 234
        ], $mapper->extract($user));
    }

    function testDataShouldBeHydratedToBaseClass()
    {
        $mapper = $this->orm->getMapper(User::class);

        $emptyObject = $mapper->init([]);
        $this->assertInstanceOf(User::class, $emptyObject);

        $user = $mapper->hydrate($emptyObject, [
            'id' => 123,
            'username' => 'guest',
            'email' => 'guest@site.com'
        ]);

        $this->assertSame(123, $user->getId());
        $this->assertSame('guest', $user->getUsername());
        $this->assertSame('guest@site.com', $user->getEmail());
    }

    function testDataShouldBeHydratedToExtendedClass()
    {
        $mapper = $this->orm->getMapper(ExtendedUser::class);

        $emptyObject = $mapper->init([]);
        $this->assertInstanceOf(ExtendedUser::class, $emptyObject);

        $user = $mapper->hydrate($emptyObject, [
            'id' => 123,
            'username' => 'guest',
            'email' => 'guest@site.com',
            'isVerified' => true,
            'profileId' => 234
        ]);

        $this->assertSame(123, $user->getId());
        $this->assertSame('guest', $user->getUsername());
        $this->assertSame('guest@site.com', $user->getEmail());
        $this->assertSame(true, $user->isVerified());
        $this->assertSame(234, $user->getProfileId());
    }
}

class User
{
    public int $id;
    protected string $username;
    private string $email;

    public function __construct(int $id, string $username, string $email)
    {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}

class ExtendedUser extends User
{
    protected bool $isVerified;
    private int $profileId;

    public function __construct(int $id, string $username, string $email, bool $isVerified, int $profileId)
    {
        parent::__construct($id, $username, $email);

        $this->isVerified = $isVerified;
        $this->profileId = $profileId;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function getProfileId(): int
    {
        return $this->profileId;
    }
}