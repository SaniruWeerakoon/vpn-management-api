<?php

namespace App\Jobs;

use Cache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class TestRedisQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lock = Cache::lock('test-provision-lock', 30);
        if (!$lock->get()) {
            Log::warning('⛔ Lock held, rescheduling job at ' . now());
            $this->release(5);
            return;
        }
        try {
            Log::info('🔐 LOCK ACQUIRED at ' . now());
            sleep(10);
            Log::info('✅ WORK DONE at ' . now());
        } finally {
            $lock->release();
            Log::info('🔓 LOCK RELEASED at ' . now());
        }
    }
}
