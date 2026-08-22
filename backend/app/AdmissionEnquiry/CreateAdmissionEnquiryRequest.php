<?php

namespace App\AdmissionEnquiry;

class CreateAdmissionEnquiryRequest
{
    public function __construct(private array $payload = [])
    {
    }

    public function payload(): array
    {
        return $this->payload;
    }
}
