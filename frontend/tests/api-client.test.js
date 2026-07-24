import test from 'node:test';
import assert from 'node:assert/strict';
import { ApiError, requestJson } from '../public/api-client.js';

test('正常なJSONレスポンスを返す', async (testContext) => {
  // テスト中だけfetchを正常レスポンスへ差し替えます。
  testContext.mock.method(globalThis, 'fetch', async () => new Response(
    JSON.stringify({data: {status: 'ok'}, error: null, meta: {}}),
    {status: 200, headers: {'content-type': 'application/json'}},
  ));

  // APIクライアントが返した本文です。
  const responseBody = await requestJson('/api/health');

  assert.equal(responseBody.data.status, 'ok');
});

test('JSON以外の応答を拒否する', async (testContext) => {
  // テスト中だけfetchをHTMLレスポンスへ差し替えます。
  testContext.mock.method(globalThis, 'fetch', async () => new Response(
    '<html></html>',
    {status: 200, headers: {'content-type': 'text/html'}},
  ));

  await assert.rejects(
    requestJson('/api/health'),
    (error) => error instanceof ApiError && error.code === 'INVALID_RESPONSE',
  );
});

