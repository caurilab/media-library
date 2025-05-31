<?php

namespace Cauri\MediaLibrary\Exceptions;

use Exception;

class InvalidConversion extends Exception
{
    public static function unknownConversion(string $conversionName): self
    {
        return new static("Unknown conversion: {$conversionName}");
    }

    public static function conversionFailed(string $conversionName, string $reason): self
    {
        return new static("Conversion '{$conversionName}' failed: {$reason}");
    }
}