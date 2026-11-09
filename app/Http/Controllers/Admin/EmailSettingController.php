<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Settings\Services\EmailSettingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmailSettingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class EmailSettingController extends Controller
{
    public function __construct(
        protected EmailSettingService $service
    ) {}

    public function index(): View
    {
        $setting = $this->service->get();

        return view(
            'super.settings.email',
            compact('setting')
        );
    }

    public function update(
        EmailSettingRequest $request
    ): JsonResponse {
        $this->service->update(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Email settings berhasil diperbarui.',
        ]);
    }

    public function test(Request $request): JsonResponse
    {
        $request->validate([
            'email' => [
                'required',
                'email',
            ],
        ]);

        $setting = $this->service->get();

        if (!$setting->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Email setting belum diaktifkan.',
            ], 422);
        }

        try {
            config([
                'mail.default' => $setting->mail_mailer,

                'mail.mailers.smtp.host' =>
                $setting->mail_host,

                'mail.mailers.smtp.port' =>
                $setting->mail_port,

                'mail.mailers.smtp.username' =>
                $setting->mail_username,

                'mail.mailers.smtp.password' =>
                $setting->mail_password,

                'mail.mailers.smtp.encryption' =>
                $setting->mail_encryption,

                'mail.from.address' =>
                $setting->from_email,

                'mail.from.name' =>
                $setting->from_name,
            ]);

            Mail::raw(
                'Email configuration ERP berhasil digunakan.',
                function ($message) use ($request, $setting) {
                    $message
                        ->to($request->email)
                        ->subject(
                            'Test Email - ' .
                                ($setting->from_name ?? 'ERP')
                        );
                }
            );

            return response()->json([
                'success' => true,
                'message' => 'Test email berhasil dikirim.',
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
