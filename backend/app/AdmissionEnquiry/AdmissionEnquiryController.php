<?php

namespace App\AdmissionEnquiry;

use App\Auth\ValidationException;
use App\Controllers\BaseController;
use App\Http\RequestHelper;
use Throwable;

class AdmissionEnquiryController extends BaseController
{
    public function __construct(private ?AdmissionEnquiryServiceInterface $service = null)
    {
        parent::__construct();
    }

    public function index(RequestHelper $request): array
    {
        if (!$this->service instanceof AdmissionEnquiryServiceInterface) {
            return $this->error('Admission enquiry service unavailable.', 503);
        }

        try {
            $result = $this->service->list(new AdmissionEnquiryListRequest($this->query($request)));

            return $this->json([
                'items' => AdmissionEnquiryResponse::collection($result['items'] ?? []),
                'pagination' => [
                    'total' => $result['total'] ?? 0,
                    'page' => $result['page'] ?? 1,
                    'per_page' => $result['per_page'] ?? 15,
                ],
            ]);
        } catch (Throwable) {
            return $this->error('Admission enquiry list request failed.', 500);
        }
    }

    public function show(int $id): array
    {
        if (!$this->service instanceof AdmissionEnquiryServiceInterface) {
            return $this->error('Admission enquiry service unavailable.', 503);
        }

        $enquiry = $this->service->find($id);
        if (!$enquiry instanceof AdmissionEnquiry) {
            return $this->error('Admission enquiry not found.', 404);
        }

        return $this->json(AdmissionEnquiryResponse::fromAdmissionEnquiry($enquiry));
    }

    public function store(RequestHelper $request): array
    {
        if (!$this->service instanceof AdmissionEnquiryServiceInterface) {
            return $this->error('Admission enquiry service unavailable.', 503);
        }

        try {
            $enquiry = $this->service->create(new CreateAdmissionEnquiryRequest($this->payload($request)));

            return $this->json(AdmissionEnquiryResponse::fromAdmissionEnquiry($enquiry), 201);
        } catch (ValidationException $exception) {
            return $this->error($exception->getMessage(), 422, ['validation' => $exception->errors()]);
        } catch (AdmissionEnquiryException $exception) {
            return $this->error($exception->getMessage(), 409);
        } catch (Throwable) {
            return $this->error('Admission enquiry create request failed.', 500);
        }
    }

    public function update(int $id, RequestHelper $request): array
    {
        if (!$this->service instanceof AdmissionEnquiryServiceInterface) {
            return $this->error('Admission enquiry service unavailable.', 503);
        }

        try {
            $enquiry = $this->service->update(new UpdateAdmissionEnquiryRequest($id, $this->payload($request)));
            if (!$enquiry instanceof AdmissionEnquiry) {
                return $this->error('Admission enquiry not found.', 404);
            }

            return $this->json(AdmissionEnquiryResponse::fromAdmissionEnquiry($enquiry));
        } catch (ValidationException $exception) {
            return $this->error($exception->getMessage(), 422, ['validation' => $exception->errors()]);
        } catch (AdmissionEnquiryException $exception) {
            return $this->error($exception->getMessage(), 409);
        } catch (Throwable) {
            return $this->error('Admission enquiry update request failed.', 500);
        }
    }

    public function destroy(int $id): array
    {
        if (!$this->service instanceof AdmissionEnquiryServiceInterface) {
            return $this->error('Admission enquiry service unavailable.', 503);
        }

        return $this->service->delete($id)
            ? $this->json(['message' => 'Admission enquiry deleted successfully.'])
            : $this->error('Admission enquiry not found.', 404);
    }

    private function payload(RequestHelper $request): array
    {
        $json = $request->json();
        if (is_array($json)) {
            return $json;
        }

        $requestData = $request->all();

        return is_array($requestData['body'] ?? null) ? $requestData['body'] : [];
    }

    private function query(RequestHelper $request): array
    {
        $requestData = $request->all();

        return is_array($requestData['query'] ?? null) ? $requestData['query'] : [];
    }
}
