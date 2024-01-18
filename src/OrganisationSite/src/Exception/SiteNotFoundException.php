<?php

declare(strict_types=1);

namespace OrganisationSite\Exception;

use RuntimeException;

use function sprintf;

class SiteNotFoundException extends RuntimeException implements ExceptionInterface
{
    public static function whenSearchingByName(string $name): self
    {
        return new self(sprintf(
            'An site could with the name "%s" could not be found',
            $name
        ));
    }

    public static function whenSearchingByUuid(string $uuid): self
    {
        return new self(sprintf(
            'An site could with the uuid "%s" could not be found',
            $uuid
        ));
    }

    public static function whenSearchingById(int $id): self
    {
        return new self(sprintf(
            'An site could with the id "%s" could not be found',
            $id
        ));
    }
}
