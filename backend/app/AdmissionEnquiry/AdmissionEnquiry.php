<?php

namespace App\AdmissionEnquiry;

class AdmissionEnquiry
{
    public function __construct(
        private ?int $id = null,
        private ?string $enquiryNumber = null,
        private ?string $studentName = null,
        private ?string $dateOfBirth = null,
        private ?string $gender = null,
        private ?string $parentName = null,
        private ?string $parentPhone = null,
        private ?string $parentEmail = null,
        private ?string $applyingForClass = null,
        private ?string $previousSchool = null,
        private ?string $address = null,
        private ?string $city = null,
        private ?string $state = null,
        private ?string $pincode = null,
        private ?string $message = null,
        private ?string $status = null,
        private ?string $source = null,
        private ?string $createdAt = null,
        private ?string $updatedAt = null,
        private ?string $deletedAt = null
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function enquiryNumber(): ?string
    {
        return $this->enquiryNumber;
    }

    public function studentName(): ?string
    {
        return $this->studentName;
    }

    public function dateOfBirth(): ?string
    {
        return $this->dateOfBirth;
    }

    public function gender(): ?string
    {
        return $this->gender;
    }

    public function parentName(): ?string
    {
        return $this->parentName;
    }

    public function parentPhone(): ?string
    {
        return $this->parentPhone;
    }

    public function parentEmail(): ?string
    {
        return $this->parentEmail;
    }

    public function applyingForClass(): ?string
    {
        return $this->applyingForClass;
    }

    public function previousSchool(): ?string
    {
        return $this->previousSchool;
    }

    public function address(): ?string
    {
        return $this->address;
    }

    public function city(): ?string
    {
        return $this->city;
    }

    public function state(): ?string
    {
        return $this->state;
    }

    public function pincode(): ?string
    {
        return $this->pincode;
    }

    public function message(): ?string
    {
        return $this->message;
    }

    public function status(): ?string
    {
        return $this->status;
    }

    public function source(): ?string
    {
        return $this->source;
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
