<?php

namespace App\AdmissionEnquiry;

use PDO;
use Throwable;

class AdmissionEnquiryRepository implements AdmissionEnquiryRepositoryInterface
{
    public function __construct(private ?PDO $database = null)
    {
    }

    public function paginate(int $page, int $perPage, ?string $status = null, ?string $applyingForClass = null, ?string $parentPhone = null, ?string $search = null): array
    {
        if (!$this->database instanceof PDO) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }

        try {
            $filters = ['deleted_at IS NULL'];
            $params = [];

            if ($status !== null) {
                $filters[] = 'status = :status';
                $params['status'] = $status;
            }

            if ($applyingForClass !== null) {
                $filters[] = 'applying_for_class = :applying_for_class';
                $params['applying_for_class'] = $applyingForClass;
            }

            if ($parentPhone !== null) {
                $filters[] = 'parent_phone = :parent_phone';
                $params['parent_phone'] = $parentPhone;
            }

            if ($search !== null) {
                $filters[] = '(student_name LIKE :search OR parent_name LIKE :search OR parent_phone LIKE :search OR parent_email LIKE :search OR city LIKE :search OR state LIKE :search OR message LIKE :search)';
                $params['search'] = '%' . $search . '%';
            }

            $where = ' WHERE ' . implode(' AND ', $filters);
            $count = $this->database->prepare('SELECT COUNT(*) FROM admission_enquiries' . $where);
            $count->execute($params);
            $total = (int) $count->fetchColumn();

            $statement = $this->database->prepare(
                'SELECT id, enquiry_number, student_name, date_of_birth, gender, parent_name, parent_phone, parent_email, applying_for_class, previous_school, address, city, state, pincode, message, status, source, created_at, updated_at FROM admission_enquiries' . $where . ' ORDER BY id DESC LIMIT :limit OFFSET :offset'
            );

            foreach ($params as $key => $value) {
                $statement->bindValue(':' . $key, $value);
            }
            $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
            $statement->execute();

            $items = array_map(fn (array $row): AdmissionEnquiry => $this->mapAdmissionEnquiry($row), $statement->fetchAll());

            return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
        } catch (Throwable) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }
    }

    public function findById(int $id): ?AdmissionEnquiry
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT id, enquiry_number, student_name, date_of_birth, gender, parent_name, parent_phone, parent_email, applying_for_class, previous_school, address, city, state, pincode, message, status, source, created_at, updated_at FROM admission_enquiries WHERE id = :id AND deleted_at IS NULL LIMIT 1');
            $statement->execute(['id' => $id]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapAdmissionEnquiry($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function findByEnquiryNumber(string $enquiryNumber): ?AdmissionEnquiry
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT id, enquiry_number, student_name, date_of_birth, gender, parent_name, parent_phone, parent_email, applying_for_class, previous_school, address, city, state, pincode, message, status, source, created_at, updated_at FROM admission_enquiries WHERE enquiry_number = :enquiry_number AND deleted_at IS NULL LIMIT 1');
            $statement->execute(['enquiry_number' => $enquiryNumber]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapAdmissionEnquiry($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function create(array $attributes): AdmissionEnquiry
    {
        if (!$this->database instanceof PDO) {
            return $this->mapAdmissionEnquiry($attributes);
        }

        try {
            $statement = $this->database->prepare('INSERT INTO admission_enquiries (enquiry_number, student_name, date_of_birth, gender, parent_name, parent_phone, parent_email, applying_for_class, previous_school, address, city, state, pincode, message, status, source) VALUES (:enquiry_number, :student_name, :date_of_birth, :gender, :parent_name, :parent_phone, :parent_email, :applying_for_class, :previous_school, :address, :city, :state, :pincode, :message, :status, :source)');
            $statement->execute($this->attributes($attributes));
            $attributes['id'] = (int) $this->database->lastInsertId();
        } catch (Throwable) {
        }

        return $this->mapAdmissionEnquiry($attributes);
    }

    public function update(int $id, array $attributes): ?AdmissionEnquiry
    {
        $current = $this->findById($id);
        if (!$this->database instanceof PDO) {
            return $current;
        }

        try {
            $merged = array_merge($current instanceof AdmissionEnquiry ? $this->toArray($current) : [], $attributes);
            $statement = $this->database->prepare('UPDATE admission_enquiries SET enquiry_number = :enquiry_number, student_name = :student_name, date_of_birth = :date_of_birth, gender = :gender, parent_name = :parent_name, parent_phone = :parent_phone, parent_email = :parent_email, applying_for_class = :applying_for_class, previous_school = :previous_school, address = :address, city = :city, state = :state, pincode = :pincode, message = :message, status = :status, source = :source WHERE id = :id AND deleted_at IS NULL');
            $params = $this->attributes($merged);
            $params['id'] = $id;
            $statement->execute($params);
        } catch (Throwable) {
        }

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        if (!$this->database instanceof PDO) {
            return false;
        }

        try {
            $statement = $this->database->prepare('UPDATE admission_enquiries SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
            $statement->execute(['id' => $id]);

            return $statement->rowCount() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function attributes(array $attributes): array
    {
        $keys = [
            'enquiry_number',
            'student_name',
            'date_of_birth',
            'gender',
            'parent_name',
            'parent_phone',
            'parent_email',
            'applying_for_class',
            'previous_school',
            'address',
            'city',
            'state',
            'pincode',
            'message',
            'status',
            'source',
        ];

        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $attributes[$key] ?? null;
        }

        return [
            'enquiry_number' => $values['enquiry_number'],
            'student_name' => $values['student_name'],
            'date_of_birth' => $values['date_of_birth'],
            'gender' => $values['gender'],
            'parent_name' => $values['parent_name'],
            'parent_phone' => $values['parent_phone'],
            'parent_email' => $values['parent_email'],
            'applying_for_class' => $values['applying_for_class'],
            'previous_school' => $values['previous_school'],
            'address' => $values['address'],
            'city' => $values['city'],
            'state' => $values['state'],
            'pincode' => $values['pincode'],
            'message' => $values['message'],
            'status' => $values['status'] ?? 'new',
            'source' => $values['source'] ?? 'website',
        ];
    }

    private function mapAdmissionEnquiry(array $row): AdmissionEnquiry
    {
        return new AdmissionEnquiry(
            isset($row['id']) ? (int) $row['id'] : null,
            $row['enquiry_number'] ?? null,
            $row['student_name'] ?? null,
            $row['date_of_birth'] ?? null,
            $row['gender'] ?? null,
            $row['parent_name'] ?? null,
            $row['parent_phone'] ?? null,
            $row['parent_email'] ?? null,
            $row['applying_for_class'] ?? null,
            $row['previous_school'] ?? null,
            $row['address'] ?? null,
            $row['city'] ?? null,
            $row['state'] ?? null,
            $row['pincode'] ?? null,
            $row['message'] ?? null,
            $row['status'] ?? 'new',
            $row['source'] ?? 'website',
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null,
            null
        );
    }

    private function toArray(AdmissionEnquiry $enquiry): array
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
            'created_at' => $enquiry->createdAt(),
            'updated_at' => $enquiry->updatedAt(),
            'deleted_at' => $enquiry->deletedAt(),
        ];
    }
}
