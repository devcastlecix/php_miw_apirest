<?php
namespace App\Tests\Entity;

use App\Entity\Result;
use App\Entity\User;
use DateTime;
use DateTimeInterface;
use Faker\Factory as FakerFactoryAlias;
use Faker\Generator as FakerGeneratorAlias;
use PHPUnit\Framework\TestCase;

/**
 * Class ResultTest
 * @package App\Tests\Entity
 * @group entities
 */
class ResultTest extends TestCase
{
    private static FakerGeneratorAlias $faker;

    public static function setUpBeforeClass(): void
    {
        self::$faker = FakerFactoryAlias::create('es_ES');
    }

    public function testConstructorDefaults(): void
    {
        $result = new Result();

        self::assertSame(0, $result->getId());
        self::assertSame(0, $result->getResult());
        self::assertNull($result->getTime());
        self::assertNull($result->getUser());
    }

    /**
     * Test del constructor con parámetros.
     */
    public function testConstructorWithParams(): void
    {
        $val = 42;
        $time = new DateTime('2025-12-31 23:59:59');
        $user = new User('someone@example.com', 'fakepassword');

        $result = new Result($val, $time, $user);

        self::assertSame(0, $result->getId()); // se inicia con 0
        self::assertSame($val, $result->getResult());
        self::assertSame($time, $result->getTime());
        self::assertSame($user, $result->getUser());
    }

    /**
     * Prueba de setId() y getId().
     */
    public function testSetGetId(): void
    {
        $result = new Result();
        self::assertSame(0, $result->getId());

        $randId = self::$faker->randomNumber(3);
        $result->setId($randId);
        self::assertSame($randId, $result->getId());
    }

    /**
     * Prueba de setResult() y getResult().
     */
    public function testSetGetResult(): void
    {
        $result = new Result();
        self::assertSame(0, $result->getResult());
        $val = self::$faker->numberBetween(0, 9999);
        $result->setResult($val);
        self::assertSame($val, $result->getResult());
    }

    /**
     * Prueba de setTime() y getTime().
     */
    public function testSetGetTime(): void
    {
        $result = new Result();
        self::assertNull($result->getTime());

        $time = new DateTime('2024-01-10 12:34:56');
        $result->setTime($time);
        self::assertSame($time, $result->getTime());
    }

    /**
     * Prueba de setUser() y getUser().
     */
    public function testSetGetUser(): void
    {
        $result = new Result();
        self::assertNull($result->getUser());

        $user = new User('user@test.com', 'secret');
        $result->setUser($user);

        self::assertSame($user, $result->getUser());
    }

    /**
     * Test de __toString().
     */
    public function testToString(): void
    {
        $time = new DateTime('2030-06-15 10:20:30');
        $user = new User('user@demo.com', '12345', ['ROLE_USER']);
        $result = new Result(50, $time, $user);
        $result->setId(7);

        $expectedString = sprintf(
            '%3d - %3d - %22s - %s',
            7,
            50,
            $user->getId(),
            $time->format('Y-m-d H:i:s')
        );

        self::assertSame($expectedString, (string)$result);
    }

    /**
     * Test de jsonSerialize().
     */
    public function testJsonSerialize(): void
    {
        $time = new DateTime('2040-12-31 23:00:00');
        $user = new User('someone@example.com', 'pass');
        $user->setId(10);  // si tu User::setId existe

        $result = new Result(123, $time, $user);
        $result->setId(99);

        $jsonData = $result->jsonSerialize();

        self::assertArrayHasKey('Id', $jsonData);
        self::assertArrayHasKey('user', $jsonData);
        self::assertArrayHasKey('result', $jsonData);
        self::assertArrayHasKey('time', $jsonData);

        self::assertSame(99, $jsonData['Id']);
        self::assertSame($user, $jsonData['user']);
        self::assertSame(123, $jsonData['result']);
        self::assertSame('2040-12-31 23:00:00', $jsonData['time']);
    }
}

