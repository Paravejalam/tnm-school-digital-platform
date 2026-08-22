<?php

namespace App\AdmissionEnquiry;

interface AdmissionEnquiryServiceInterface
{
    public function list(AdmissionEnquiryListRequest $request): array;

    public function find(int $id): ?AdmissionEnquiry;

    public function create(CreateAdmissionEnquiryRequest $request): AdmissionEnquiry;

    public function update(UpdateAdmissionEnquiryRequest $request): ?AdmissionEnquiry;

    public function delete(int $id): bool;
}
