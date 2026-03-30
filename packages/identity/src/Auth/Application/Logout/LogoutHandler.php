<?php
declare(strict_types=1);

namespace Identity\Auth\Application\Logout;

use Identity\Auth\Domain\RefreshTokenRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class LogoutHandler
{
    public function __construct(
        private readonly RefreshTokenRepository $refreshTokenRepository,
    ) {}

    public function __invoke(LogoutCommand $command): void
    {
        $tokenHash = hash('sha256', $command->refreshToken);
        $token = $this->refreshTokenRepository->findByTokenHash($tokenHash);

        if ($token !== null && $token->isValid()) {
            $token->revoke();
            $this->refreshTokenRepository->save($token);
        }
    }
}
