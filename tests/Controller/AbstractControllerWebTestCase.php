<?php

namespace App\Tests\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractControllerWebTestCase extends WebTestCase
{
    /**
     * @var array<string, bool>
     */
    private static array $preparedModes = [];

    /**
     * @var array<string, string>
     */
    private static array $prepareErrors = [];

    protected function setUp(): void
    {
        parent::setUp();

        $mode = static::seedMode();

        if (!isset(self::$preparedModes[$mode]) && !isset(self::$prepareErrors[$mode])) {
            try {
                static::rebuildDatabase();
                self::$preparedModes[$mode] = true;
            } catch (\Throwable $exception) {
                self::$prepareErrors[$mode] = $exception->getMessage();
            }
        }

        if (isset(self::$prepareErrors[$mode])) {
            self::fail(sprintf(
                'Unable to prepare test database for mode "%s": %s',
                $mode,
                self::$prepareErrors[$mode]
            ));
        }
    }

    abstract protected static function seedMode(): string;

    protected static function rebuildDatabase(): void
    {
        self::ensureKernelShutdown();
        self::assertSafeTestDatabase();
        self::bootKernel();

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        self::assertSafeTestDatabase((string) $entityManager->getConnection()->getDatabase());

        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

        if ($metadata === []) {
            throw new \RuntimeException('No Doctrine metadata found for test database bootstrap.');
        }

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);

        $entityManager->clear();
        self::ensureKernelShutdown();
    }

    private static function assertSafeTestDatabase(?string $databaseName = null): void
    {
        if ($databaseName === null) {
            $databaseUrl = (string) ($_SERVER['DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL'));

            if ($databaseUrl === '') {
                throw new \RuntimeException('DATABASE_URL is empty in test environment.');
            }

            if (str_starts_with($databaseUrl, 'sqlite:')) {
                $databaseName = $databaseUrl;
            } else {
                $parsed = parse_url($databaseUrl);
                $databaseName = isset($parsed['path']) ? ltrim((string) $parsed['path'], '/') : '';
            }
        }

        if ($databaseName === '' || !preg_match('/(^|[_-])test($|[_-])/i', $databaseName)) {
            throw new \RuntimeException(sprintf(
                'Refusing to run destructive test DB bootstrap on "%s". Use a dedicated *_test database in .env.test.local.',
                $databaseName === '' ? '<unknown>' : $databaseName
            ));
        }
    }

}
