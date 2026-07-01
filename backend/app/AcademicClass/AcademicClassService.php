<?php

namespace App\AcademicClass;

class AcademicClassService implements AcademicClassServiceInterface
{
    public function __construct(
        private AcademicClassRepositoryInterface $repository,
        private AcademicClassValidator $validator
    ) {
    }

    public function list(AcademicClassListRequest $request): array
    {
        return $this->repository->paginate($request->page(), $request->perPage(), $request->search());
    }

    public function find(int $id): ?AcademicClass
    {
        return $this->repository->findById($id);
    }

    public function create(CreateAcademicClassRequest $request): AcademicClass
    {
        $payload = $request->payload();
        $this->validator->validateCreate($payload);

        $existing = $this->repository->findByName((string) $payload['class_name']);
        if ($existing instanceof AcademicClass) {
            throw new AcademicClassException('Class already exists.');
        }

        return $this->repository->create($payload);
    }

    public function update(UpdateAcademicClassRequest $request): ?AcademicClass
    {
        $payload = $request->payload();
        $this->validator->validateUpdate($payload);

        if (isset($payload['class_name'])) {
            $existing = $this->repository->findByName((string) $payload['class_name']);
            if ($existing instanceof AcademicClass && $existing->id() !== $request->id()) {
                throw new AcademicClassException('Class already exists.');
            }
        }

        return $this->repository->update($request->id(), $payload);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
