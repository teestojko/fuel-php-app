<?php

use PHPUnit\Framework\TestCase;

/**
 * DB実装を差し替えてServiceの判定だけを検証します。
 */
final class HealthServiceTest extends TestCase
{
    /**
     * DBが利用可能な場合に正常判定になることを確認します。
     */
    public function testReturnsOkWhenDatabaseIsAvailable(): void
    {
        // DBが利用可能と返すテスト専用Repositoryです。
        $repository = new class implements Repository_HealthStatusRepositoryInterface {
            /** テスト用にDB利用可能を返します。 */
            public function isDatabaseAvailable(): bool
            {
                return true;
            }
        };
        // テスト用Repositoryを注入したServiceを生成します。
        $service = new Service_HealthService($repository);

        self::assertSame([
            'status' => 'ok',
            'dependencies' => ['database' => 'ok'],
        ], $service->getStatus());
    }

    /**
     * DBが利用不能な場合に縮退判定になることを確認します。
     */
    public function testReturnsDegradedWhenDatabaseIsUnavailable(): void
    {
        // DBが利用不能と返すテスト専用Repositoryです。
        $repository = new class implements Repository_HealthStatusRepositoryInterface {
            /** テスト用にDB利用不能を返します。 */
            public function isDatabaseAvailable(): bool
            {
                return false;
            }
        };
        // テスト用Repositoryを注入したServiceを生成します。
        $service = new Service_HealthService($repository);

        self::assertSame([
            'status' => 'degraded',
            'dependencies' => ['database' => 'unavailable'],
        ], $service->getStatus());
    }
}
