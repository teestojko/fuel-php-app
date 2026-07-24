<?php

/**
 * ヘルスチェックで使用する依存オブジェクトを安全に生成します。
 */
final class Factory_HealthServiceFactory
{
    /**
     * 環境変数から接続を生成し、Serviceへ注入します。
     */
    public static function create(): Service_HealthService
    {
        // DB接続先はDocker内部のサービス名を使用します。
        $databaseHost = self::requiredEnvironmentValue('DB_HOST');
        // ポートは数値だけを許可し、DSNへの不正な文字列混入を防ぎます。
        $databasePort = self::requiredPort('DB_PORT');
        // DB名は識別子として安全な文字だけを許可します。
        $databaseName = self::requiredIdentifier('DB_NAME');
        // DBユーザー名は環境変数から取得し、ソースへ固定しません。
        $databaseUser = self::requiredEnvironmentValue('DB_USER');
        // DBパスワードは保持せず、接続生成時だけ利用します。
        $databasePassword = self::requiredEnvironmentValue('DB_PASSWORD');
        // 文字コードを固定したMySQL用DSNを組み立てます。
        $dataSourceName = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $databaseHost,
            $databasePort,
            $databaseName
        );
        // 例外化、ネイティブプレースホルダー、連想配列取得を標準設定にします。
        $connectionOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 3,
        ];
        // 検証済みの設定を使ってPDO接続を生成します。
        $connection = new PDO($dataSourceName, $databaseUser, $databasePassword, $connectionOptions);
        // PDO実装をRepositoryの契約としてServiceへ渡します。
        $repository = new Repository_PdoHealthStatusRepository($connection);

        return new Service_HealthService($repository);
    }

    /**
     * 必須環境変数を取得し、未設定なら秘密値を含まない例外を投げます。
     */
    private static function requiredEnvironmentValue(string $environmentName): string
    {
        // 指定された環境変数の値を取得します。
        $environmentValue = getenv($environmentName);

        if ($environmentValue === false || trim($environmentValue) === '') {
            throw new RuntimeException('Required application configuration is missing.');
        }

        return $environmentValue;
    }

    /**
     * DBポートを有効なTCPポート番号として検証します。
     */
    private static function requiredPort(string $environmentName): int
    {
        // 文字列として取得したポートを整数として検証します。
        $validatedPort = filter_var(
            self::requiredEnvironmentValue($environmentName),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 65535]]
        );

        if ($validatedPort === false) {
            throw new RuntimeException('Application port configuration is invalid.');
        }

        return $validatedPort;
    }

    /**
     * DB識別子を英数字とアンダースコアだけに制限します。
     */
    private static function requiredIdentifier(string $environmentName): string
    {
        // DB名として使用する値を取得します。
        $identifier = self::requiredEnvironmentValue($environmentName);

        if (preg_match('/\A[a-zA-Z0-9_]+\z/', $identifier) !== 1) {
            throw new RuntimeException('Application identifier configuration is invalid.');
        }

        return $identifier;
    }
}

