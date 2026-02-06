<?php

namespace App\Services\OpenVpn;

use Symfony\Component\Process\Process;

class DockerOpenVpnExecutorService
{
    public function __construct(
        private readonly string $containerName
    ) {}

    /**
     * Run a command inside the OpenVPN container.
     * $args must be a list of tokens (no shell string).
     */
    public function exec(array $args, int $timeoutSeconds = 120, ?string $input = null): array
    {
        // docker exec -i <container> <args...>
        $cmd = array_merge(['docker', 'exec', '-i', $this->containerName], $args);

        $p = new Process($cmd);
        $p->setTimeout($timeoutSeconds);
        if ($input !== null) {
            $p->setInput($input);
        }
        $p->run();

        return [
            'ok' => $p->isSuccessful(),
            'exit_code' => $p->getExitCode(),
            'stdout' => $p->getOutput(),
            'stderr' => $p->getErrorOutput(),
            'command' => $cmd,
        ];
    }
}
