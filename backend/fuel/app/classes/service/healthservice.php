<?php

/**
 * アプリケーション全体の稼働状態を判定するサービスです。
 */
final class Service_HealthService
{
    /** @var Repository_HealthStatusRepositoryInterface DB状態を取得する契約です。 */
    private Repository_HealthStatusRepositoryInterface $healthRepository;

    /**
     * DB実装を外部から受け取り、単体テストで差し替え可能にします。
     */
    public function __construct(Repository_HealthStatusRepositoryInterface $healthRepository)
    {
        // 状態確認に利用するRepositoryを保持します。
        $this->healthRepository = $healthRepository;
    }

    /**
     * 外部へ公開してよい最小限の稼働情報を返します。
     *
     * @return array<string, mixed>
     */
    public function getStatus(): array
    {
        // DBが応答するかをRepository経由で確認します。
        $databaseIsAvailable = $this->healthRepository->isDatabaseAvailable();

        return [
            'status' => $databaseIsAvailable ? 'ok' : 'degraded',
            'dependencies' => [
                'database' => $databaseIsAvailable ? 'ok' : 'unavailable',
            ],
        ];
    }
}

