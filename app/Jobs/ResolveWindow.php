<?php

namespace App\Jobs;

use App\Models\Window;
use App\Services\GammaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ResolveWindow implements ShouldQueue
{
    use Queueable;

    public int $tries = 10;

    public int $backoff = 300; // 5 minutes between retries

    public function __construct(public string $windowId)
    {
        $this->onQueue('resolution');
    }

    public function handle(GammaService $gamma): void
    {
        $window = Window::find($this->windowId);
        if (!$window || $window->outcome !== null) {
            return; // already resolved or window missing
        }

        $result = $gamma->fetchResolution($window->id);

        if ($result === null) {
            // Not resolved yet — release back to queue with delay
            if ($this->attempts() < $this->tries) {
                $this->release(300);
            }
            return;
        }

        DB::transaction(function () use ($window, $result) {
            DB::table('windows')
                ->where('id', $window->id)
                ->update([
                    'outcome'      => $result['outcome'],
                    'condition_id' => $window->condition_id ?? $result['condition_id'],
                ]);

            ComputeWindowFeatures::dispatch($window->id);
        });
    }
}
