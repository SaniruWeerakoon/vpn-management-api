<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\VpnClient;
use Arr;
use Cache;

class VpnClientObserver
{

    private function requestMeta(): array
    {
        return [
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'path' => request()?->path(),
            'method' => request()?->method(),
        ];
    }

    private function safeAttributes(VpnClient $vpnClient): array
    {
        // Avoid logging sensitive data if needed it later
        return $vpnClient->only([
            'id',
            'user_id',
            'client_name',
            'status',
            'notes',
            'created_at',
            'updated_at',
        ]);
    }

    /**
     * Handle the VpnClient "created" event.
     */
    public function created(VpnClient $vpnClient): void
    {
        Cache::forget('vpn_clients:list:admin:v1');
        Cache::forget('vpn_clients:list:user:' . $vpnClient->user_id . ':v1');

        AuditLog::create([
            'actor_id' => auth()->id(), // null if no auth context
            'action' => 'vpn_client.created',
            'target_type' => VpnClient::class,
            'target_id' => $vpnClient->id,
            'metadata' => [
                'attributes' => $this->safeAttributes($vpnClient),
                'request' => $this->requestMeta(),
            ],
        ]);
    }

    /**
     * Handle the VpnClient "updated" event.
     */
    public function updated(VpnClient $vpnClient): void
    {
        Cache::forget('vpn_clients:list:admin:v1');
        Cache::forget('vpn_clients:list:user:' . $vpnClient->user_id . ':v1');

        $changes = $vpnClient->getChanges(); // only changed fields + updated_at
        unset($changes['updated_at']);

        if (empty($changes)) {
            return;
        }

        $before = Arr::only($vpnClient->getOriginal(), array_keys($changes));

        AuditLog::create([
            'actor_id' => auth()->id(),
            'action' => 'vpn_client.updated',
            'target_type' => VpnClient::class,
            'target_id' => $vpnClient->id,
            'metadata' => [
                'after' => $changes,
                'before' => $before,
                'request' => $this->requestMeta(),
            ],
        ]);
    }

    /**
     * Handle the VpnClient "deleted" event.
     */
    public function deleted(VpnClient $vpnClient): void
    {
        Cache::forget('vpn_clients:list:admin:v1');
        Cache::forget('vpn_clients:list:user:' . $vpnClient->user_id . ':v1');

        AuditLog::create([
            'actor_id' => auth()->id(),
            'action' => 'vpn_client.deleted',
            'target_type' => VpnClient::class,
            'target_id' => $vpnClient->id,
            'metadata' => [
                'attributes' => $this->safeAttributes($vpnClient),
                'request' => $this->requestMeta(),
            ],
        ]);
    }
}
