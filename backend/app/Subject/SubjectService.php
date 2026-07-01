<?php

namespace App\Subject;

class SubjectService implements SubjectServiceInterface
{
    public function __construct(
        private SubjectRepositoryInterface $repository,
        private SubjectValidator $validator
    ) {
    }

    public function list(SubjectListRequest $request): array
    {
        return $this->repository->paginate($request->page(), $request->perPage(), $request->search());
    }

    public function find(int $id): ?Subject
    {
        return $this->repository->findById($id);
    }

    public function create(CreateSubjectRequest $request): Subject
    {
        $payload = $request->payload();
        $this->validator->validateCreate($payload);

        $existing = $this->repository->findByName((string) $payload['subject_name']);
        if ($existing instanceof Subject) {
            throw new SubjectException('Subject already exists.');
        }

        return $this->repository->create($payload);
    }

    public function update(UpdateSubjectRequest $request): ?Subject
    {
        $payload = $request->payload();
        $this->validator->validateUpdate($payload);

        if (isset($payload['subject_name'])) {
            $existing = $this->repository->findByName((string) $payload['subject_name']);
            if ($existing instanceof Subject && $existing->id() !== $request->id()) {
                throw new SubjectException('Subject already exists.');
            }
        }

        return $this->repository->update($request->id(), $payload);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
