import { createReadStream } from 'node:fs';
import { stat } from 'node:fs/promises';
import { createServer, request as createProxyRequest } from 'node:http';
import { extname, resolve, sep } from 'node:path';

// 静的ファイルを公開するディレクトリをpublicだけに固定します。
const publicDirectory = resolve('public');
// 開発サーバーが待ち受ける固定ポートです。
const serverPort = 5173;
// APIの転送先はDocker内部の既定値を使用します。
const proxyTarget = new URL(process.env.API_PROXY_TARGET ?? 'http://web');
// 許可する静的ファイル形式とContent-Typeの対応です。
const contentTypes = new Map([
  ['.html', 'text/html; charset=utf-8'],
  ['.js', 'text/javascript; charset=utf-8'],
  ['.css', 'text/css; charset=utf-8'],
  ['.svg', 'image/svg+xml'],
]);

/**
 * ブラウザ向けの共通セキュリティヘッダーを設定します。
 */
function setSecurityHeaders(response) {
  response.setHeader('X-Content-Type-Options', 'nosniff');
  response.setHeader('X-Frame-Options', 'DENY');
  response.setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
  response.setHeader('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self'; connect-src 'self'");
}

/**
 * /api以下のリクエストだけをバックエンドへ転送します。
 */
function proxyApiRequest(request, response) {
  // 転送先URLを環境変数の固定ホストとリクエストパスから組み立てます。
  const proxyUrl = new URL(request.url, proxyTarget);
  // Hostと転送系ヘッダーを利用者入力から引き継がないよう再構築します。
  const proxyHeaders = {
    accept: request.headers.accept ?? 'application/json',
    'content-type': request.headers['content-type'] ?? 'application/json',
    host: proxyTarget.host,
  };
  // Node標準HTTPクライアントでAPIへ転送します。
  const proxyRequest = createProxyRequest(proxyUrl, {
    method: request.method,
    headers: proxyHeaders,
    timeout: 5000,
  }, (proxyResponse) => {
    response.writeHead(proxyResponse.statusCode ?? 502, {
      'content-type': proxyResponse.headers['content-type'] ?? 'application/json; charset=utf-8',
      'cache-control': 'no-store',
    });
    proxyResponse.pipe(response);
  });

  proxyRequest.on('timeout', () => proxyRequest.destroy(new Error('API request timed out.')));
  proxyRequest.on('error', () => {
    if (!response.headersSent) {
      response.writeHead(502, {'content-type': 'application/json; charset=utf-8'});
    }
    response.end(JSON.stringify({data: null, error: {code: 'BAD_GATEWAY', message: 'API is unavailable.'}, meta: {}}));
  });
  request.pipe(proxyRequest);
}

/**
 * public配下の許可された静的ファイルだけを返します。
 */
async function serveStaticFile(request, response) {
  // クエリ文字列を除いたURLパスを安全にデコードします。
  const requestedPath = decodeURIComponent(new URL(request.url, 'http://localhost').pathname);
  // ルートアクセスでは画面の入口を返します。
  const relativePath = requestedPath === '/' ? 'index.html' : requestedPath.replace(/^\/+/, '');
  // 解決したファイルパスがpublicの外へ出ないことを確認します。
  const filePath = resolve(publicDirectory, relativePath);
  // 許可対象ディレクトリの接頭辞です。
  const allowedPrefix = `${publicDirectory}${sep}`;

  if (!filePath.startsWith(allowedPrefix) || !contentTypes.has(extname(filePath))) {
    response.writeHead(404).end('Not Found');
    return;
  }

  try {
    // 通常ファイルだけを配信し、ディレクトリ一覧は公開しません。
    const fileStatus = await stat(filePath);
    if (!fileStatus.isFile()) {
      response.writeHead(404).end('Not Found');
      return;
    }
    response.writeHead(200, {'content-type': contentTypes.get(extname(filePath))});
    createReadStream(filePath).pipe(response);
  } catch {
    response.writeHead(404).end('Not Found');
  }
}

// APIと静的ファイルだけを処理する開発用HTTPサーバーです。
const developmentServer = createServer(async (request, response) => {
  setSecurityHeaders(response);

  if (request.url?.startsWith('/api/')) {
    proxyApiRequest(request, response);
    return;
  }

  if (request.method !== 'GET' && request.method !== 'HEAD') {
    response.writeHead(405, {'allow': 'GET, HEAD'}).end('Method Not Allowed');
    return;
  }

  await serveStaticFile(request, response);
});

// Dockerからアクセスできる全インターフェイスで待ち受けます。
developmentServer.listen(serverPort, '0.0.0.0');

