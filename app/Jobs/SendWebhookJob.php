<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * POSTs one attendance batch to a device's configured webhook URL.
 *
 * Delivery is deliberately fire-and-forget. The terminal has already been
 * answered by the time this runs, so there is nobody left to react to a
 * failure - the only useful thing to do is record the status code and how long
 * the receiver took, and let a human notice.
 *
 * For the same reason nothing is rethrown: an exception escaping the
 * terminate phase of an /iclock request would surface as an uncaught error in
 * the PHP error log, which is strictly worse than a clear log line.
 *
 * The job holds only the URL and the payload - no Eloquent model - so it stays
 * valid whatever happens to the device row afterwards.
 */
class SendWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Seconds the worker allows the whole job. Always larger than the HTTP
     * timeout so a slow receiver fails as a logged timeout rather than a
     * killed worker.
     */
    public int $timeout;

    public function __construct(
        public string $url,
        public array $attLog,
        public ?string $sn = null,
    ) {
        $this->timeout = (int) config('adms.webhook_timeout', 5) + 10;
    }

    public function handle(): void
    {
        $started = microtime(true);
        $timeout = (int) config('adms.webhook_timeout', 5);

        try {
            $response = Http::timeout($timeout)->post($this->url, ['data' => $this->attLog]);
        } catch (Throwable $e) {
            $this->log('error', 'webhook request failed', [
                'error' => $e->getMessage(),
                'duration_ms' => $this->elapsed($started),
            ]);

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

        // 4xx means the receiver refused the payload and will refuse it again;
        // 5xx may be transient. Neither can be retried usefully from here -
        // the terminal moved on long ago - so both are just made visible, at a
        // severity that tells them apart.
        $this->log(
            $response->clientError() ? 'warning' : 'error',
            'webhook rejected',
            $context
        );
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
        } catch (Throwable $e) {
            // A cached config that predates the "webhook" channel must not turn
            // a delivery failure into an uncaught error.
            Log::log($level, '[webhook] ' . $message, $context);
        }
    }
}
