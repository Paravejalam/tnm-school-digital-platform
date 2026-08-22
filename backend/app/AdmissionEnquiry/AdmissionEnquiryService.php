<?php

namespace App\AdmissionEnquiry;

use App\Audit\AuditLoggerInterface;
use App\Auth\ValidationException;
use PDO;
use Throwable;

class AdmissionEnquiryService implements AdmissionEnquiryServiceInterface
{
    public function __construct(
        private AdmissionEnquiryRepositoryInterface $repository,
        private AdmissionEnquiryValidator $validator,
        private ?PDO $database = null,
        private ?AuditLoggerInterface $auditLogger = null
    ) {
    }

    public function list(AdmissionEnquiryListRequest $request): array
    {
        return $this->repository->paginate(
            $request->page(),
            $request->perPage(),
            $request->status(),
            $request->applyingForClass(),
            $request->parentPhone(),
            $request->search()
        );
    }

    public function find(int $id): ?AdmissionEnquiry
    {
        return $this->repository->findById($id);
    }

    public function create(CreateAdmissionEnquiryRequest $request): AdmissionEnquiry
    {
        $payload = $this->sanitizePayload($request->payload());
        $this->validator->validateCreate($payload);

        $payload['status'] = 'new';
        $payload['source'] = 'website';
        $payload['enquiry_number'] = $this->generateEnquiryNumber();

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $result = $this->repository->create($payload);

            if ($this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('CREATE', 'admission_enquiry', $result->id(), null, $payload);
            }

            if ($this->database instanceof PDO) {
                $this->database->commit();
            }

            return $result;
        } catch (Throwable $e) {
            if ($this->database instanceof PDO && $this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $e;
        }
    }

    public function update(UpdateAdmissionEnquiryRequest $request): ?AdmissionEnquiry
    {
        $payload = $this->sanitizePayload($request->payload());
        $this->validator->validateUpdate($payload);

        if (isset($payload['status'])) {
            $status = strtolower(trim((string) $payload['status']));
            if ($status === '') {
                throw new ValidationException(errors: ['status' => ['Status cannot be empty.']]);
            }
            if (!in_array($status, ['new', 'contacted', 'qualified', 'rejected', 'admitted', 'withdrawn'], true)) {
                throw new ValidationException(errors: ['status' => ['Status must be new, contacted, qualified, rejected, admitted or withdrawn.']]);
            }
        }

        $old = $this->repository->findById($request->id());

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $result = $this->repository->update($request->id(), $payload);

            if ($result !== null && $this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('UPDATE', 'admission_enquiry', $request->id(), $this->auditLogger->entityToArray($old), $payload);
            }

            if ($this->database instanceof PDO) {
                $this->database->commit();
            }

            return $result;
        } catch (Throwable $e) {
            if ($this->database instanceof PDO && $this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        $old = $this->repository->findById($id);

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $result = $this->repository->delete($id);

            if ($result && $this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('DELETE', 'admission_enquiry', $id, $this->auditLogger->entityToArray($old));
            }

            if ($this->database instanceof PDO) {
                $this->database->commit();
            }

            return $result;
        } catch (Throwable $e) {
            if ($this->database instanceof PDO && $this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $e;
        }
    }

    private function sanitizePayload(array $payload): array
    {
        $allowed = [
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

        return array_filter(
            $payload,
            fn (string $key): bool => in_array($key, $allowed, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function generateEnquiryNumber(): string
    {
        $prefix = 'AE';
        $timestamp = gmdate('Ymd');

        do {
            $random = strtoupper(bin2hex(random_bytes(4)));
            $number = $prefix . '-' . $timestamp . '-' . $random;
        } while ($this->repository->findByEnquiryNumber($number) instanceof AdmissionEnquiry);

        return $number;
    }
}
