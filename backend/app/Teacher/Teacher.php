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
        private ?string $status = null,
        private ?int $userId = null,
        private ?string $gender = null,
        private ?string $dateJoined = null,
        private ?string $createdAt = null,
        private ?string $updatedAt = null,
        private ?string $deletedAt = null
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

    public function userId(): ?int
    {
        return $this->userId;
    }

    public function gender(): ?string
    {
        return $this->gender;
    }

    public function dateJoined(): ?string
    {
        return $this->dateJoined;
    }

    public function createdAt(): ?string
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?string
    {
        return $this->deletedAt;
    }
}
