<?php

namespace App\Period;

interface PeriodServiceInterface
{
    public function list(PeriodListRequest $request): array;

    public function find(int $id): ?Period;

    public function create(CreatePeriodRequest $request): Period;

    public function update(UpdatePeriodRequest $request): ?Period;

    public function delete(int $id): bool;
}
