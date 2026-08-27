import http from 'node:http';
import https from 'node:https';
import { performance } from 'node:perf_hooks';

const [
  ,
  ,
  rawUrl,
  rawRequests = '1000',
  rawConcurrency = '1000',
  rawCookie = '',
  rawTimeout = '30000',
] = process.argv;

if (!rawUrl) {
  console.error('Usage: node scripts/load-test.mjs <url> [requests] [concurrency] [cookie] [timeoutMs]');
  process.exit(1);
}

const target = new URL(rawUrl);
const client = target.protocol === 'https:' ? https : http;
const totalRequests = Math.max(Number.parseInt(rawRequests, 10) || 1, 1);
const concurrency = Math.min(Math.max(Number.parseInt(rawConcurrency, 10) || 1, 1), totalRequests);
const timeoutMs = Math.max(Number.parseInt(rawTimeout, 10) || 30000, 1000);
const cookie = rawCookie.trim();

const latencies = [];
const statuses = new Map();
let launched = 0;
let completed = 0;
let errors = 0;
let bytes = 0;

const startedAt = performance.now();

function percentile(values, p) {
  if (!values.length) {
    return 0;
  }

  const sorted = [...values].sort((a, b) => a - b);
  const index = Math.min(sorted.length - 1, Math.ceil((p / 100) * sorted.length) - 1);

  return sorted[index];
}

function runOne() {
  if (launched >= totalRequests) {
    return;
  }

  launched += 1;
  const requestStartedAt = performance.now();

  const req = client.request(
    {
      hostname: target.hostname,
      port: target.port || (target.protocol === 'https:' ? 443 : 80),
      path: `${target.pathname}${target.search}`,
      method: 'GET',
      headers: {
        Accept: 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        Connection: 'keep-alive',
        ...(cookie ? { Cookie: cookie } : {}),
      },
      timeout: timeoutMs,
    },
    (res) => {
      statuses.set(res.statusCode, (statuses.get(res.statusCode) || 0) + 1);

      res.on('data', (chunk) => {
        bytes += chunk.length;
      });

      res.on('end', () => finish(requestStartedAt));
    },
  );

  req.on('timeout', () => {
    req.destroy(new Error('timeout'));
  });

  req.on('error', () => {
    errors += 1;
    finish(requestStartedAt);
  });

  req.end();
}

function finish(requestStartedAt) {
  latencies.push(performance.now() - requestStartedAt);
  completed += 1;

  if (launched < totalRequests) {
    runOne();
  }

  if (completed === totalRequests) {
    const durationMs = performance.now() - startedAt;
    const successful = totalRequests - errors;
    const statusSummary = [...statuses.entries()]
      .sort(([a], [b]) => a - b)
      .map(([status, count]) => `${status}:${count}`)
      .join(',');

    console.log(JSON.stringify({
      url: rawUrl,
      requests: totalRequests,
      concurrency,
      timeoutMs,
      durationSeconds: +(durationMs / 1000).toFixed(3),
      requestsPerSecond: +(successful / (durationMs / 1000)).toFixed(2),
      errors,
      statuses: statusSummary,
      bytes,
      latencyMs: {
        min: +Math.min(...latencies).toFixed(1),
        avg: +(latencies.reduce((sum, value) => sum + value, 0) / latencies.length).toFixed(1),
        p50: +percentile(latencies, 50).toFixed(1),
        p95: +percentile(latencies, 95).toFixed(1),
        p99: +percentile(latencies, 99).toFixed(1),
        max: +Math.max(...latencies).toFixed(1),
      },
    }, null, 2));
  }
}

for (let i = 0; i < concurrency; i += 1) {
  runOne();
}
