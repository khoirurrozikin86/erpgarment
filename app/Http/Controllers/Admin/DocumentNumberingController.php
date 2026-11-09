<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Settings\Services\DocumentNumberingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DocumentNumberingRequest;
use App\Models\DocumentNumbering;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DocumentNumberingController extends Controller
{
    public function __construct(
        protected DocumentNumberingService $service
    ) {}

    public function index(): View
    {
        $numberings = $this->service->all();

        return view(
            'super.settings.document-numbering',
            compact('numberings')
        );
    }

    public function update(
        DocumentNumberingRequest $request,
        DocumentNumbering $numbering
    ): JsonResponse {
        $this->service->update(
            $numbering,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Document numbering berhasil diperbarui.',
        ]);
    }
}
