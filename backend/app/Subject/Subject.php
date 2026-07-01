<?php

namespace App\Subject;

class Subject
{
    public function __construct(
        private ?int $id = null,
        private ?string $subjectName = null,
        private ?string $code = null,
        private ?int $sectionId = null,
        private ?string $status = null
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function subjectName(): ?string
    {
        return $this->subjectName;
    }

    public function code(): ?string
    {
        return $this->code;
    }

    public function sectionId(): ?int
    {
        return $this->sectionId;
    }

    public function status(): ?string
    {
        return $this->status;
    }
}
