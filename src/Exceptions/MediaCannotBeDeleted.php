<?php

namespace Cauri\MediaLibrary\Exceptions;

use Exception;

class MediaCannotBeDeleted extends Exception
{
    public static function mediaNotFound(int $mediaId): self
    {
        return new static("Media with ID {$mediaId} not found");
    }

    public static function stillHasConversions(): self
    {
        return new static("Media cannot be deleted because it still has active conversions");
    }
}