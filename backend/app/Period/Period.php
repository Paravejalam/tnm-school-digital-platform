<?php

namespace App\Period;

class Period
{
    public function __construct(
        private ?int $id = null,
        private ?string $periodName = null,
        private ?int $timetableId = null,
        private ?string $status = null
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function periodName(): ?string
    {
        return $this->periodName;
    }

    public function timetableId(): ?int
    {
        return $this->timetableId;
    }

    public function status(): ?string
    {
        return $this->status;
    }
}
