<?php

namespace Cauri\MediaLibrary\Support;

class MediaCollectionDefinition
{
    protected string $name;
    protected array $acceptedMimeTypes = [];
    protected bool $singleFile = false;
    protected ?int $diskQuota = null;
    protected array $validationRules = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function acceptsMimeTypes(array $mimeTypes): self
    {
        $this->acceptedMimeTypes = $mimeTypes;
        return $this;
    }

    public function singleFile(bool $singleFile = true): self
    {
        $this->singleFile = $singleFile;
        return $this;
    }

    public function useDiskQuota(int $quota): self
    {
        $this->diskQuota = $quota;
        return $this;
    }

    public function addValidationRule(string $rule): self
    {
        $this->validationRules[] = $rule;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAcceptedMimeTypes(): array
    {
        return $this->acceptedMimeTypes;
    }

    public function isSingleFile(): bool
    {
        return $this->singleFile;
    }

    public function getDiskQuota(): ?int
    {
        return $this->diskQuota;
    }

    public function getValidationRules(): array
    {
        return $this->validationRules;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'accepted_mime_types' => $this->acceptedMimeTypes,
            'single_file' => $this->singleFile,
            'disk_quota' => $this->diskQuota,
            'validation_rules' => $this->validationRules,
        ];
    }
}