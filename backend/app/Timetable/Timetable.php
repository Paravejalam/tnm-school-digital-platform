<?php

namespace App\Timetable;

class Timetable
{
    public function __construct(
        private ?int $id = null,
        private ?string $timetableName = null,
        private ?int $academicSessionId = null,
        private ?int $classId = null,
        private ?int $sectionId = null,
        private ?int $subjectId = null,
        private ?int $teacherId = null,
        private ?string $status = null
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function timetableName(): ?string
    {
        return $this->timetableName;
    }

    public function academicSessionId(): ?int
    {
        return $this->academicSessionId;
    }

    public function classId(): ?int
    {
        return $this->classId;
    }

    public function sectionId(): ?int
    {
        return $this->sectionId;
    }

    public function subjectId(): ?int
    {
        return $this->subjectId;
    }

    public function teacherId(): ?int
    {
        return $this->teacherId;
    }

    public function status(): ?string
    {
        return $this->status;
    }
}
