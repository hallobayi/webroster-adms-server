<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * POSTs one attendance batch to a device's configured webhook URL.
 *
 * Two things constrain the design:
 *
 * 1. The terminal has already been answered by the time this runs, so nobody is
 *    left to react to a failure. The outcome therefore has to be recorded -
 *    status code and how long the receiver took - or it is lost.
 *
 * 2. On the sync/after-response path the job runs in the terminate phase of the
 *    request that answered the terminal. An exception escaping that phase
 *    surfaces as an uncaught error in the PHP error log, which is strictly
 *    worse than a missing delivery. So failures throw only when the job is
 *    genuinely being processed off a queue, where a retry means something.
 *
 * The job holds only primitives - no Eloquent model - so it stays valid
 * whatever happens to the device row afterwards.
 */
class SendWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    /**
     * Seconds between attempts. Short first - most 5xx blips clear quickly -
     * then longer, then the job is marked failed.
     */
    public array $backoff = [30, 120];

    /**
     * Seconds the worker allows the whole job. Always larger than the HTTP
     * timeout so a slow receiver fails as a logged timeout rather than a killed
     * worker.
     */
    public int $timeout;

    public function __construct(
        public string $url,
        public array $attLog,
        public ?string $sn = null,
        public ?string $secret = null,
    ) {
        $this->timeout = (int) config('adms.webhook_timeout', 5) + 10;
    }

    public function handle(): void
    {
        $timeout = (int) config('adms.webhook_timeout', 5);
        $timestamp = now()->getTimestamp();

        // Encoded here rather than handed to Http::post() as an array so the
        // signature covers exactly the bytes the receiver will read.
        $body = json_encode(['data' => $this->attLog], JSON_UNESCAPED_SLASHES);

        $headers = [
            'Content-Type' => 'application/json',
            'X-Webhook-Timestamp' => (string) $timestamp,
        ];

        if (!empty($this->secret)) {
            $headers['X-Webhook-Signature'] = $this->signatureFor($body, $timestamp);
        }

        $started = microtime(true);

        try {
            $response = Http::timeout($timeout)
                ->withHeaders($headers)
                ->withBody($body, 'application/json')
                ->post($this->url);
        } catch (Throwable $e) {
            $this->log('error', 'webhook request failed', [
                'error' => $e->getMessage(),
                'duration_ms' => $this->elapsed($started),
            ]);

            $this->retryAfter($e);

            return;
        }

        $context = [
            'status' => $response->status(),
            'duration_ms' => $this->elapsed($started),
        ];

        if ($response->successful()) {
            $this->log('info', 'webhook delivered', $context);

            return;
        }

        // 4xx is the receiver refusing the payload and it will refuse the same
        // payload again, so retrying only adds duplicate deliveries. 5xx may be
        // transient.
        $this->log(
            $response->clientError() ? 'warning' : 'error',
            'webhook rejected',
            $context
        );

        if (!$response->clientError()) {
            $this->retryAfter(new RuntimeException(
                'webhook receiver answered ' . $response->status()
            ));
        }
    }

    /**
     * Called by the worker once the last attempt has failed. Every attempt
     * already has its own log line; this one says "and that was the end".
     */
    public function failed(?Throwable $exception = null): void
    {
        $this->log('error', 'webhook delivery failed', [
            'attempts' => $this->attempts(),
            'error' => $exception?->getMessage(),
        ]);
    }

    /**
     * HMAC-SHA256 over "timestamp.body".
     *
     * The timestamp is inside the signed material so a captured request cannot
     * simply be replayed later: a receiver that rejects old timestamps rejects
     * the replay too, even though the signature itself still verifies.
     */
    public function signatureFor(string $body, int $timestamp): string
    {
        return 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, (string) $this->secret);
    }

    /**
     * Rethrow so the queue retries - but only where there is a queue.
     *
     * On the sync/after-response path the job is handed a SyncJob, whose
     * attempts() is always 1 and which never re-dispatches. Throwing there
     * would not retry anything; it would just produce an uncaught error in the
     * PHP log long after the terminal had gone.
     */
    private function retryAfter(Throwable $e): void
    {
        if ($this->job === null || $this->job->getConnectionName() === 'sync') {
            return;
        }

        throw $e;
    }

    private function elapsed(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }

    private function log(string $level, string $message, array $context = []): void
    {
        $context = array_merge([
            'url' => $this->url,
            'sn' => $this->sn,
            'records' => count($this->attLog),
        ], $context);

        try {
            Log::channel('webhook')->log($level, $message, $context);
        } catch (Throwable) {
            // A cached config that predates the "webhook" channel must not turn
            // a delivery failure into an uncaught error.
            Log::log($level, '[webhook] ' . $message, $context);
        }
    }
}
