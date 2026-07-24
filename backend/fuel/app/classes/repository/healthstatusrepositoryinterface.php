<?php

/**
 * 稼働確認に必要な永続化層の契約です。
 */
interface Repository_HealthStatusRepositoryInterface
{
    /**
     * データベースへ安全な読み取り問い合わせを行い、接続可否を返します。
     */
    public function isDatabaseAvailable(): bool;
}

