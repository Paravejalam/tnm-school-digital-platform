<?php

namespace App\SystemSetting;

use App\Auth\ValidationException;
use App\Controllers\BaseController;
use App\Http\RequestHelper;
use Throwable;

class SystemSettingController extends BaseController
{
    public function __construct(private ?SystemSettingServiceInterface $settings = null)
    {
        parent::__construct();
    }

    public function index(RequestHelper $request): array
    {
        if (!$this->settings instanceof SystemSettingServiceInterface) {
            return $this->error('System settings service unavailable.', 503);
        }

        try {
            $result = $this->settings->list(new SystemSettingListRequest($this->query($request)));

            return $this->json([
                'items' => SystemSettingResponse::collection($result['items'] ?? []),
                'pagination' => [
                    'total' => $result['total'] ?? 0,
                    'page' => $result['page'] ?? 1,
                    'per_page' => $result['per_page'] ?? 15,
                ],
            ]);
        } catch (Throwable) {
            return $this->error('System settings list request failed.', 500);
        }
    }

    public function show(int $id): array
    {
        if (!$this->settings instanceof SystemSettingServiceInterface) {
            return $this->error('System settings service unavailable.', 503);
        }

        $setting = $this->settings->find($id);
        if (!$setting instanceof SystemSetting) {
            return $this->error('System setting not found.', 404);
        }

        return $this->json(SystemSettingResponse::fromEntity($setting));
    }

    public function update(int $id, RequestHelper $request): array
    {
        if (!$this->settings instanceof SystemSettingServiceInterface) {
            return $this->error('System settings service unavailable.', 503);
        }

        try {
            $setting = $this->settings->update(new UpdateSystemSettingRequest($id, $this->payload($request)));
            if (!$setting instanceof SystemSetting) {
                return $this->error('System setting not found.', 404);
            }

            return $this->json(SystemSettingResponse::fromEntity($setting));
        } catch (ValidationException $exception) {
            return $this->error($exception->getMessage(), 422, ['validation' => $exception->errors()]);
        } catch (Throwable) {
            return $this->error('System setting update request failed.', 500);
        }
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
