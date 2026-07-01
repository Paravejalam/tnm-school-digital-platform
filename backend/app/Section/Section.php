<?php

namespace App\Section;

class Section
{
    public function __construct(
        private ?int $id = null,
        private ?string $sectionName = null,
        private ?string $code = null,
        private ?int $classId = null,
        private ?string $status = null
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function sectionName(): ?string
    {
        return $this->sectionName;
    }

    public function code(): ?string
    {
        return $this->code;
    }

    public function classId(): ?int
    {
        return $this->classId;
    }

    public function status(): ?string
    {
        return $this->status;
    }
}
