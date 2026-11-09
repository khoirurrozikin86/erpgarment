<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domain\Settings\Services\GeneralSettingService;
use App\Http\Requests\Admin\GeneralSettingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class GeneralSettingController extends Controller
{
    public function __construct(
        protected GeneralSettingService $service
    ) {}

    public function index(): View
    {
        $setting = $this->service->get();

        return view('super.settings.general', compact('setting'));
    }

    public function update(
        GeneralSettingRequest $request
    ): JsonResponse {
        $this->service->update(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'General settings berhasil diperbarui.',
        ]);
    }
}
