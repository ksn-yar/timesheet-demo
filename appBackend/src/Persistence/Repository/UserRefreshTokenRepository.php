<?php

declare(strict_types=1);

namespace App\Persistence\Repository;

use App\Persistence\Entity\UserRefreshToken;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshTokenRepository as BaseRefreshTokenRepository;

/**
 * Репозиторий refresh token пользователя.
 *
 * @extends BaseRefreshTokenRepository<UserRefreshToken>
 */
class UserRefreshTokenRepository extends BaseRefreshTokenRepository {}
