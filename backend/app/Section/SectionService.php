<?php

namespace App\Section;

use App\AcademicClass\AcademicClassRepositoryInterface;
use App\Auth\ValidationException;

class SectionService implements SectionServiceInterface
{
    public function __construct(
        private SectionRepositoryInterface $repository,
        private SectionValidator $validator,
        private ?AcademicClassRepositoryInterface $classRepository = null
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
        $this->validateReferences($payload);

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
        $this->validateReferences($payload);

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

    private function validateReferences(array $payload): void
    {
        if (isset($payload['class_id']) && $this->classRepository instanceof AcademicClassRepositoryInterface) {
            $class = $this->classRepository->findById((int) $payload['class_id']);
            if ($class === null) {
                throw new ValidationException(['class_id' => ['Academic class not found.']]);
            }
        }
    }
}
