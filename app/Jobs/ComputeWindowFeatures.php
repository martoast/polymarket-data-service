<?php

namespace App\Jobs;

use App\Services\WindowFeatureService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ComputeWindowFeatures implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $windowId)
    {
        $this->onQueue('default');
    }

    public function handle(WindowFeatureService $service): void
    {
        $service->computeFeatures($this->windowId);
    }
}
