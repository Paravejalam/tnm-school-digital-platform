<?php

namespace App\Period;

class PeriodService implements PeriodServiceInterface
{
    public function __construct(
        private PeriodRepositoryInterface $repository,
        private PeriodValidator $validator
    ) {
    }

    public function list(PeriodListRequest $request): array
    {
        return $this->repository->paginate($request->page(), $request->perPage(), $request->search());
    }

    public function find(int $id): ?Period
    {
        return $this->repository->findById($id);
    }

    public function create(CreatePeriodRequest $request): Period
    {
        $payload = $request->payload();
        $this->validator->validateCreate($payload);

        $existing = $this->repository->findByName((string) $payload['period_name']);
        if ($existing instanceof Period) {
            throw new PeriodException('Period already exists.');
        }

        return $this->repository->create($payload);
    }

    public function update(UpdatePeriodRequest $request): ?Period
    {
        $payload = $request->payload();
        $this->validator->validateUpdate($payload);

        if (isset($payload['period_name'])) {
            $existing = $this->repository->findByName((string) $payload['period_name']);
            if ($existing instanceof Period && $existing->id() !== $request->id()) {
                throw new PeriodException('Period already exists.');
            }
        }

        return $this->repository->update($request->id(), $payload);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
