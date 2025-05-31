<?php

namespace Cauri\MediaLibrary\Conversions;

use Illuminate\Support\Collection;

class ConversionCollection extends Collection
{
    public function getByName(string $name): ?Conversion
    {
        return $this->first(fn(Conversion $conversion) => $conversion->getName() === $name);
    }

    public function getQueued(): self
    {
        return $this->filter(fn(Conversion $conversion) => $conversion->isQueued());
    }

    public function getNonQueued(): self
    {
        return $this->filter(fn(Conversion $conversion) => !$conversion->isQueued());
    }

    public function getOptimized(): self
    {
        return $this->filter(fn(Conversion $conversion) => $conversion->isOptimized());
    }
}