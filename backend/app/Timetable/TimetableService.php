<?php

namespace App\Timetable;

class TimetableService implements TimetableServiceInterface
{
    public function __construct(
        private TimetableRepositoryInterface $repository,
        private TimetableValidator $validator
    ) {
    }

    public function list(TimetableListRequest $request): array
    {
        return $this->repository->paginate($request->page(), $request->perPage(), $request->search());
    }

    public function find(int $id): ?Timetable
    {
        return $this->repository->findById($id);
    }

    public function create(CreateTimetableRequest $request): Timetable
    {
        $payload = $request->payload();
        $this->validator->validateCreate($payload);

        $existing = $this->repository->findByName((string) $payload['timetable_name']);
        if ($existing instanceof Timetable) {
            throw new TimetableException('Timetable already exists.');
        }

        return $this->repository->create($payload);
    }

    public function update(UpdateTimetableRequest $request): ?Timetable
    {
        $payload = $request->payload();
        $this->validator->validateUpdate($payload);

        if (isset($payload['timetable_name'])) {
            $existing = $this->repository->findByName((string) $payload['timetable_name']);
            if ($existing instanceof Timetable && $existing->id() !== $request->id()) {
                throw new TimetableException('Timetable already exists.');
            }
        }

        return $this->repository->update($request->id(), $payload);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
