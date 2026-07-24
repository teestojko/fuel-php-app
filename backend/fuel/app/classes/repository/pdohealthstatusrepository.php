<?php

/**
 * PDOを使用してデータベースの稼働状態を確認します。
 */
final class Repository_PdoHealthStatusRepository implements Repository_HealthStatusRepositoryInterface
{
    /** @var PDO データベースへの安全な接続を保持します。 */
    private PDO $connection;

    /**
     * テスト時に差し替えられるPDO接続を受け取ります。
     */
    public function __construct(PDO $connection)
    {
        // 外部で生成された接続を保持し、接続生成と問い合わせを分離します。
        $this->connection = $connection;
    }

    /**
     * 固定SQLだけを実行し、利用者の入力をSQLへ含めません。
     */
    public function isDatabaseAvailable(): bool
    {
        // 固定値の問い合わせ結果を取得し、DB接続が応答することを確認します。
        $statement = $this->connection->query('SELECT 1');

        return $statement !== false && (int) $statement->fetchColumn() === 1;
    }
}
