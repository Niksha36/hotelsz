<?php

declare(strict_types=1);

namespace App\Security;

use App\TimeConstants;
use DateInterval;
use DateTimeImmutable;
use DomainException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use UnexpectedValueException;

class JwtService
{
    private string $secret;
    private int $accessTokenMs;
    private int $refreshTokenMs;

    public function __construct(string $jwtSecret, int $accessTokenMinutes, int $refreshTokenDays)
    {
        $decoded = base64_decode($jwtSecret, true);
        $this->secret = $decoded !== false ? $decoded : $jwtSecret;

        $this->accessTokenMs = $accessTokenMinutes * TimeConstants::SECONDS_IN_A_MINUTE * TimeConstants::MILLISECONDS_IN_A_SECOND;
        $this->refreshTokenMs = $refreshTokenDays * TimeConstants::HOURS_IN_A_DAY * TimeConstants::MINUTES_IN_AN_HOUR * TimeConstants::SECONDS_IN_A_MINUTE * TimeConstants::MILLISECONDS_IN_A_SECOND;
    }

    /**
     * @param string $userId  — subject (sub)
     * @param string $type    — 'access' или 'refresh'
     * @param int    $expiryMs — время жизни в миллисекундах
     * @return string JWT
     */
    public function createToken(string $userId, string $type, int $expiryMs): string
    {
        $now = new DateTimeImmutable();
        $iat = $now->getTimestamp();
        $exp = $now->add(new DateInterval('PT' . (int)ceil($expiryMs / 1000) . 'S'))->getTimestamp();

        $payload = [
            'sub' => $userId,
            'type' => $type,
            'iat' => $iat,
            'exp' => $exp,
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function createAccessToken(int $userId): string
    {
        return $this->createToken((string)$userId, 'access', $this->accessTokenMs);
    }

    public function createRefreshToken(int $userId): string
    {
        return $this->createToken((string)$userId, 'refresh', $this->refreshTokenMs);
    }

    /**
     * @param string $jwt Raw token или "Bearer <token>"
     * @return array|null
     */
    public function parseToken(string $jwt): ?array
    {
        $raw = $this->stripBearer($jwt);
        try {
            $decoded = JWT::decode($raw, new Key($this->secret, 'HS256'));
            return json_decode(json_encode($decoded), true);
        } catch (ExpiredException | BeforeValidException | SignatureInvalidException $e) {
            return null;
        } catch (DomainException | UnexpectedValueException $e) {
            return null;
        }
    }

    public function validateAccessToken(string $jwt): bool
    {
        $claims = $this->parseToken($jwt);
        if ($claims === null) {
            return false;
        }

        if (($claims['type'] ?? '') !== 'access') {
            return false;
        }

        if (!isset($claims['exp']) || (int)$claims['exp'] < time()) {
            return false;
        }

        return true;
    }

    public function validateRefreshToken(string $jwt): bool
    {
        $claims = $this->parseToken($jwt);
        if ($claims === null) {
            return false;
        }

        if (($claims['type'] ?? '') !== 'refresh') {
            return false;
        }

        if (!isset($claims['exp']) || (int)$claims['exp'] < time()) {
            return false;
        }
        return true;
    }

    /**
     * Получить subject/userId из токена или выбросить 401
     * @throws HttpException
     */
    public function getIdFromToken(string $jwt): int
    {
        $claims = $this->parseToken($jwt);
        if ($claims === null || empty($claims['sub'])) {
            throw new HttpException(401, 'Invalid token.');
        }

        return (int)$claims['sub'];
    }

    private function stripBearer(string $token): string
    {
        return str_starts_with($token, 'Bearer ') ? substr($token, 7) : $token;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getAccessTokenMs(): int
    {
        return $this->accessTokenMs;
    }
    public function getRefreshTokenMs(): int
    {
        return $this->refreshTokenMs;
    }
}
