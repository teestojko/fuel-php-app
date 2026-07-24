import { requestJson } from './api-client.js';

// 稼働状態を表示する要素です。
const statusElement = document.querySelector('#status');
// APIを再確認するボタンです。
const retryButton = document.querySelector('#retry');

/**
 * ヘルスチェックAPIを呼び、結果を安全にテキスト表示します。
 */
async function refreshHealthStatus() {
  statusElement.textContent = '確認しています…';
  retryButton.disabled = true;

  try {
    // バックエンドの稼働情報を取得します。
    const responseBody = await requestJson('/api/health');
    // innerHTMLを使わず、API由来の値をテキストとして表示します。
    statusElement.textContent = responseBody.data?.status === 'ok'
      ? 'APIとデータベースは正常です。'
      : '一部のサービスを利用できません。';
  } catch {
    // 内部エラー情報を画面へ出さず、一般化した案内だけを表示します。
    statusElement.textContent = 'APIへ接続できません。時間をおいて再確認してください。';
  } finally {
    retryButton.disabled = false;
  }
}

retryButton.addEventListener('click', refreshHealthStatus);
refreshHealthStatus();

