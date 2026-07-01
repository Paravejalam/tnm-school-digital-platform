<?php

namespace App\HolidayCalendar;

class HolidayCalendarService implements HolidayCalendarServiceInterface
{
    public function __construct(
        private HolidayCalendarRepositoryInterface $repository,
        private HolidayCalendarValidator $validator
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
}
