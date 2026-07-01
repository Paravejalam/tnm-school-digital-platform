<?php

namespace App\AcademicSession;

class AcademicSession
{
    public function __construct(
        private ?int $id = null,
        private ?string $sessionName = null,
        private ?string $status = null
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function sessionName(): ?string
    {
        return $this->sessionName;
    }

    public function status(): ?string
    {
        return $this->status;
    }
}
