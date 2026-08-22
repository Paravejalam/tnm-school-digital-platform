<?php

namespace App\AdmissionEnquiry;

class AdmissionEnquiryResponse
{
    public static function fromAdmissionEnquiry(AdmissionEnquiry $enquiry): array
    {
        return [
            'id' => $enquiry->id(),
            'enquiry_number' => $enquiry->enquiryNumber(),
            'student_name' => $enquiry->studentName(),
            'date_of_birth' => $enquiry->dateOfBirth(),
            'gender' => $enquiry->gender(),
            'parent_name' => $enquiry->parentName(),
            'parent_phone' => $enquiry->parentPhone(),
            'parent_email' => $enquiry->parentEmail(),
            'applying_for_class' => $enquiry->applyingForClass(),
            'previous_school' => $enquiry->previousSchool(),
            'address' => $enquiry->address(),
            'city' => $enquiry->city(),
            'state' => $enquiry->state(),
            'pincode' => $enquiry->pincode(),
            'message' => $enquiry->message(),
            'status' => $enquiry->status(),
            'source' => $enquiry->source(),
        ];
    }

    public static function collection(array $items): array
    {
        return array_map(fn (AdmissionEnquiry $item): array => self::fromAdmissionEnquiry($item), $items);
    }
}
