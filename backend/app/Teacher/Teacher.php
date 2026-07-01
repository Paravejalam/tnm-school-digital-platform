<?php

namespace App\Teacher;

class Teacher
{
    public function __construct(
        private ?int $id = null,
        private ?string $employeeId = null,
        private ?string $firstName = null,
        private ?string $lastName = null,
        private ?string $email = null,
        private ?string $phone = null,
        private ?string $department = null,
        private ?string $designation = null,
        private ?string $status = null
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function employeeId(): ?string
    {
        return $this->employeeId;
    }

    public function firstName(): ?string
    {
        return $this->firstName;
    }

    public function lastName(): ?string
    {
        return $this->lastName;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function phone(): ?string
    {
        return $this->phone;
    }

    public function department(): ?string
    {
        return $this->department;
    }

    public function designation(): ?string
    {
        return $this->designation;
    }

    public function status(): ?string
    {
        return $this->status;
    }
}