<?php

namespace App\AdmissionEnquiry;

class UpdateAdmissionEnquiryRequest
{
    public function __construct(private int $id, private array $payload = [])
    {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function payload(): array
    {
        return $this->payload;
    }
}
