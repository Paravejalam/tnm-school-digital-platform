<?php

namespace App\AdmissionEnquiry;

interface AdmissionEnquiryRepositoryInterface
{
    public function paginate(int $page, int $perPage, ?string $status = null, ?string $applyingForClass = null, ?string $parentPhone = null, ?string $search = null): array;

    public function findById(int $id): ?AdmissionEnquiry;

    public function findByEnquiryNumber(string $enquiryNumber): ?AdmissionEnquiry;

    public function create(array $attributes): AdmissionEnquiry;

    public function update(int $id, array $attributes): ?AdmissionEnquiry;

    public function delete(int $id): bool;
}
