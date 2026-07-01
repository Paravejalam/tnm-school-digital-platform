<?php

namespace App\Student;

class Student
{
    public function __construct(
        private ?int $id = null,
        private ?string $admissionNumber = null,
        private ?string $firstName = null,
        private ?string $lastName = null,
        private ?string $email = null,
        private ?string $phone = null,
        private ?string $className = null,
        private ?string $section = null,
        private ?string $status = null
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function admissionNumber(): ?string
    {
        return $this->admissionNumber;
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

    public function className(): ?string
    {
        return $this->className;
    }

    public function section(): ?string
    {
        return $this->section;
    }

    public function status(): ?string
    {
        return $this->status;
    }
}