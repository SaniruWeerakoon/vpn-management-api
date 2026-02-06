<?php

namespace App\Services\OpenVpn;

use http\Exception\InvalidArgumentException;
use RuntimeException;
use Str;

class OpenVpnService
{
    public function __construct(
        private readonly DockerOpenVpnExecutorService $exec
    )
    {
    }

    /** Validate client name to avoid weird cert names + command issues */
    private function sanitizeClientName(string $name): string
    {
        $name = trim($name);

        // allow letters, numbers, underscore, dash only (tight on purpose)
        if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $name)) {
            throw new InvalidArgumentException('Invalid client name. Use A-Z, 0-9, _ or - (max 64).');
        }

        return $name;
    }

    public function createClient(string $clientName): void
    {
        $clientName = $this->sanitizeClientName($clientName);

        $res = $this->exec->exec(['easyrsa', 'build-client-full', $clientName, 'nopass'], 180);

        if (!$res['ok']) {
            throw new RuntimeException("OpenVPN create failed: {$res['stderr']}");
        }
    }

    public function getClientOvpn(string $clientName): string
    {
        $clientName = $this->sanitizeClientName($clientName);

        $res = $this->exec->exec(['ovpn_getclient', $clientName], 60);

        if (!$res['ok'] || !Str::contains($res['stdout'], 'BEGIN CERTIFICATE')) {
            throw new RuntimeException("OpenVPN getclient failed: {$res['stderr']}");
        }

        return $res['stdout']; // return raw text; store in DB or file later
    }

    public function revokeClient(string $clientName): void
    {
        $clientName = $this->sanitizeClientName($clientName);
        $res = $this->exec->exec(['ovpn_revokeclient', $clientName], 120, 'yes');
        if (!$res['ok']) {
            throw new RuntimeException("OpenVPN revoke failed: {$res['stderr']}");
        }
    }
}
