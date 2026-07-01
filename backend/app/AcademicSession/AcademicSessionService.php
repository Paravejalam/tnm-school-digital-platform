<?php

namespace App\AcademicSession;

class AcademicSessionService implements AcademicSessionServiceInterface
{
    public function __construct(
        private AcademicSessionRepositoryInterface $repository,
        private AcademicSessionValidator $validator
    ) {
    }

    public function list(AcademicSessionListRequest $request): array
    {
        return $this->repository->paginate($request->page(), $request->perPage(), $request->search());
    }

    public function find(int $id): ?AcademicSession
    {
        return $this->repository->findById($id);
    }

    public function create(CreateAcademicSessionRequest $request): AcademicSession
    {
        $payload = $request->payload();
        $this->validator->validateCreate($payload);

        $existing = $this->repository->findByName((string) $payload['session_name']);
        if ($existing instanceof AcademicSession) {
            throw new AcademicSessionException('Academic session already exists.');
        }

        return $this->repository->create($payload);
    }

    public function update(UpdateAcademicSessionRequest $request): ?AcademicSession
    {
        $payload = $request->payload();
        $this->validator->validateUpdate($payload);

        if (isset($payload['session_name'])) {
            $existing = $this->repository->findByName((string) $payload['session_name']);
            if ($existing instanceof AcademicSession && $existing->id() !== $request->id()) {
                throw new AcademicSessionException('Academic session already exists.');
            }
        }

        return $this->repository->update($request->id(), $payload);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
