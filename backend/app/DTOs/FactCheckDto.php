<?php

namespace App\DTOs;

class FactCheckDto
{
    public function __construct(
        public readonly string $contentType,
        public readonly string $contentValue,
        public readonly ?string $ipAddress = null,
        public readonly ?int $userId = null
    ) {}

    public static function fromRequest($request): self
    {
        return new self(
            contentType: $request->input('content_type'),
            contentValue: $request->input('content_value'),
            ipAddress: $request->ip(),
            userId: $request->user()?->id
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'content_type' => $this->contentType,
            'content_value' => $this->contentValue,
            'ip_address' => $this->ipAddress,
        ];
    }
}
