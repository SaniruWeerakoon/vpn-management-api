<?php

namespace App\Jobs;

use App\Models\VpnClient;
use App\Services\OpenVpn\OpenVpnService;
use Cache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Throwable;

class ProvisionVpnClientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [5, 10, 20, 40, 80];

    public function __construct(public int $vpnClientId)
    {
    }

    /**
     * @throws Throwable
     */
    public function handle(OpenVpnService $vpn): void
    {
        $lockKey = "vpn-client:{$this->vpnClientId}:provision";
        $lock = Cache::lock($lockKey, 300); // TTL > worst-case provisioning

        if (!$lock->get()) {
            // Someone else is provisioning this same client. Try again soon.
            $this->release(5);
            return;
        }

        try {
            $client = VpnClient::query()->findOrFail($this->vpnClientId);

            // Idempotency: if already provisioned, do nothing
            if ($client->status === 'provisioned') {
                return;
            }

            // mark in-progress
            $client->update([
                'status' => 'provisioning',
                'last_error' => null,
            ]);

            // Choose a safe OpenVPN CN/client name
            $openvpnName = "client_$client->id";
            $vpn->createClient($openvpnName);
            $ovpn = $vpn->getClientOvpn($openvpnName);

            // Persist results
            $client->update([
                'status' => 'provisioned',
                'config' => $ovpn,
                'provisioned_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error("Provision failed for vpnClientId=$this->vpnClientId: {$e->getMessage()}");

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
