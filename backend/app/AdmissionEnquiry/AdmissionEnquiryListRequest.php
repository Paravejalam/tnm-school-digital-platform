<?php

namespace App\AdmissionEnquiry;

class AdmissionEnquiryListRequest
{
    public function __construct(private array $query = [])
    {
    }

    public function page(): int
    {
        return max(1, (int) ($this->query['page'] ?? 1));
    }

    public function perPage(): int
    {
        return min(100, max(1, (int) ($this->query['per_page'] ?? 15)));
    }

    public function status(): ?string
    {
        $status = trim((string) ($this->query['status'] ?? ''));

        return $status !== '' ? $status : null;
    }

    public function applyingForClass(): ?string
    {
        $class = trim((string) ($this->query['applying_for_class'] ?? ''));

        return $class !== '' ? $class : null;
    }

    public function parentPhone(): ?string
    {
        $phone = trim((string) ($this->query['parent_phone'] ?? ''));

        return $phone !== '' ? $phone : null;
    }

    public function search(): ?string
    {
        $search = trim((string) ($this->query['search'] ?? ''));

        return $search !== '' ? $search : null;
    }
}
