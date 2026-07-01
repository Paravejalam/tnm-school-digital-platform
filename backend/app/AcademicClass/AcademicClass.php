<?php

namespace App\AcademicClass;

class AcademicClass
{
    public function __construct(
        private ?int $id = null,
        private ?string $className = null,
        private ?string $code = null,
        private ?int $academicSessionId = null,
        private ?string $status = null
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function className(): ?string
    {
        return $this->className;
    }

    public function code(): ?string
    {
        return $this->code;
    }

    public function academicSessionId(): ?int
    {
        return $this->academicSessionId;
    }

    public function status(): ?string
    {
        return $this->status;
    }
}
