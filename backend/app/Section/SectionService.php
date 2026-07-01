<?php

namespace App\Section;

class SectionService implements SectionServiceInterface
{
    public function __construct(
        private SectionRepositoryInterface $repository,
        private SectionValidator $validator
    ) {
    }

    public function list(SectionListRequest $request): array
    {
        return $this->repository->paginate($request->page(), $request->perPage(), $request->search());
    }

    public function find(int $id): ?Section
    {
        return $this->repository->findById($id);
    }

    public function create(CreateSectionRequest $request): Section
    {
        $payload = $request->payload();
        $this->validator->validateCreate($payload);

        $existing = $this->repository->findByName((string) $payload['section_name']);
        if ($existing instanceof Section) {
            throw new SectionException('Section already exists.');
        }

        return $this->repository->create($payload);
    }

    public function update(UpdateSectionRequest $request): ?Section
    {
        $payload = $request->payload();
        $this->validator->validateUpdate($payload);

        if (isset($payload['section_name'])) {
            $existing = $this->repository->findByName((string) $payload['section_name']);
            if ($existing instanceof Section && $existing->id() !== $request->id()) {
                throw new SectionException('Section already exists.');
            }
        }

        return $this->repository->update($request->id(), $payload);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
