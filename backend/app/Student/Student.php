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
        private ?string $status = null,
        private ?int $userId = null,
        private ?string $rollNumber = null,
        private ?string $dateOfBirth = null,
        private ?string $gender = null,
        private ?int $academicSessionId = null,
        private ?int $classId = null,
        private ?int $sectionId = null,
        private ?string $createdAt = null,
        private ?string $updatedAt = null,
        private ?string $deletedAt = null
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

    public function userId(): ?int
    {
        return $this->userId;
    }

    public function rollNumber(): ?string
    {
        return $this->rollNumber;
    }

    public function dateOfBirth(): ?string
    {
        return $this->dateOfBirth;
    }

    public function gender(): ?string
    {
        return $this->gender;
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
