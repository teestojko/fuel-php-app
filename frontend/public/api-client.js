/**
 * API通信で利用者へ表示可能な一般化済みエラーです。
 */
export class ApiError extends Error {
  /** APIエラーコードと安全なメッセージを保持します。 */
  constructor(code, message) {
    super(message);
    // 画面側で分岐するための公開エラーコードです。
    this.code = code;
    this.name = 'ApiError';
  }
}

/**
 * タイムアウトとJSON形式を検証しながらAPIを呼び出します。
 */
export async function requestJson(path, options = {}) {
  // API呼び出しを中断するためのコントローラーです。
  const abortController = new AbortController();
  // 呼び出し時間の上限をミリ秒で指定します。
  const timeoutMilliseconds = options.timeoutMilliseconds ?? 5000;
  // 上限時間を超えた通信を停止するタイマーです。
  const timeoutId = setTimeout(() => abortController.abort(), timeoutMilliseconds);

  try {
    // 同一オリジンのAPIだけを認証情報なしで呼び出します。
    const response = await fetch(path, {
      method: 'GET',
      headers: {'Accept': 'application/json'},
      credentials: 'same-origin',
      signal: abortController.signal,
    });
    // Content-Typeを検査してHTMLなどの誤応答を拒否します。
    const contentType = response.headers.get('content-type') ?? '';
    if (!contentType.includes('application/json')) {
      throw new ApiError('INVALID_RESPONSE', 'APIから不正な応答を受信しました。');
    }
    // 検証対象のJSONレスポンスを読み取ります。
    const responseBody = await response.json();

    if (!response.ok) {
      throw new ApiError(
        responseBody?.error?.code ?? 'REQUEST_FAILED',
        responseBody?.error?.message ?? 'APIの呼び出しに失敗しました。',
      );
    }

    return responseBody;
  } catch (error) {
    if (error instanceof ApiError) {
      throw error;
    }
    throw new ApiError('NETWORK_ERROR', 'APIへ接続できませんでした。');
  } finally {
    clearTimeout(timeoutId);
  }
}
