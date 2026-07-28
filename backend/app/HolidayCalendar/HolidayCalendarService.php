<?php

namespace App\HolidayCalendar;

use App\AcademicSession\AcademicSessionRepositoryInterface;
use App\Auth\ValidationException;

class HolidayCalendarService implements HolidayCalendarServiceInterface
{
    public function __construct(
        private HolidayCalendarRepositoryInterface $repository,
        private HolidayCalendarValidator $validator,
        private ?AcademicSessionRepositoryInterface $sessionRepository = null
    ) {
    }

    public function list(HolidayCalendarListRequest $request): array
    {
        return $this->repository->paginate($request->page(), $request->perPage(), $request->search());
    }

    public function find(int $id): ?HolidayCalendar
    {
        return $this->repository->findById($id);
    }

    public function create(CreateHolidayCalendarRequest $request): HolidayCalendar
    {
        $payload = $request->payload();
        $this->validator->validateCreate($payload);
        $this->validateReferences($payload);

        $existing = $this->repository->findByName((string) $payload['holiday_name']);
        if ($existing instanceof HolidayCalendar) {
            throw new HolidayCalendarException('Holiday calendar already exists.');
        }

        return $this->repository->create($payload);
    }

    public function update(UpdateHolidayCalendarRequest $request): ?HolidayCalendar
    {
        $payload = $request->payload();
        $this->validator->validateUpdate($payload);
        $this->validateReferences($payload);

        if (isset($payload['holiday_name'])) {
            $existing = $this->repository->findByName((string) $payload['holiday_name']);
            if ($existing instanceof HolidayCalendar && $existing->id() !== $request->id()) {
                throw new HolidayCalendarException('Holiday calendar already exists.');
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
        if (isset($payload['academic_session_id']) && $this->sessionRepository instanceof AcademicSessionRepositoryInterface) {
            $session = $this->sessionRepository->findById((int) $payload['academic_session_id']);
            if ($session === null) {
                throw new ValidationException(['academic_session_id' => ['Academic session not found.']]);
            }
        }
    }
}
