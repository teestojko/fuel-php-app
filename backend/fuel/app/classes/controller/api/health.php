<?php

/**
 * 外部監視とフロントエンド向けの稼働確認APIです。
 */
final class Controller_Api_Health extends Controller_Rest
{
    /** @var string APIレスポンス形式をJSONに固定します。 */
    protected $format = 'json';

    /**
     * GET /api/health の稼働情報を返します。
     */
    public function get_index(): Response
    {
        try {
            // Factoryを通して依存関係が設定されたServiceを取得します。
            $healthService = Factory_HealthServiceFactory::create();
            // Serviceが判定した公開可能な稼働状態を取得します。
            $healthStatus = $healthService->getStatus();
            // 正常時のHTTPステータスを決定します。
            $httpStatus = $healthStatus['status'] === 'ok' ? 200 : 503;

            return $this->response([
                'data' => $healthStatus,
                'error' => null,
                'meta' => [],
            ], $httpStatus);
        } catch (Throwable $exception) {
            // 例外本文や接続情報はレスポンスにもログにも出しません。
            logger(Fuel::L_ERROR, 'Health check failed.', __METHOD__);

            return $this->response([
                'data' => null,
                'error' => [
                    'code' => 'SERVICE_UNAVAILABLE',
                    'message' => 'Service is temporarily unavailable.',
                ],
                'meta' => [],
            ], 503);
        }
    }
}

