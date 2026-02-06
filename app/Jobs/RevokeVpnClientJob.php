<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\VpnClient;
use App\Services\OpenVpn\OpenVpnService;
use Cache;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Throwable;

class RevokeVpnClientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [5, 15, 30];
    public function __construct(public int $vpnClientId)
    {
    }

    /**
     * @throws Throwable
     */
    public function handle(OpenVpnService $vpn): void
    {
        $lockKey = "vpn-client:$this->vpnClientId:revoke";
        $lock = Cache::lock($lockKey, 300);

        if (! $lock->get()) {
            $this->release(5);
            return;
        }

        try {
            $client = VpnClient::query()->findOrFail($this->vpnClientId);
            if ($client->status === 'revoked') {
                return;
            }

            $client->update([
                'status' => 'revoking',
                'last_error' => null,
            ]);

            $openvpnName = "client_{$client->id}";

            $vpn->revokeClient($openvpnName);
                $client->update([
                    'status' => 'revoked',
                    'revoked_at' => now(),
                ]);
        } catch (Throwable $e) {
            VpnClient::whereKey($this->vpnClientId)->update([
                'status' => 'failed',
                'last_error' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            optional($lock)->release();
        }
    }
}
