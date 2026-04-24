<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProtocolList;
use App\Services\ProtocolListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProtocolListController extends Controller
{
    public function __construct(
        private readonly ProtocolListService $protocolListService
    ) {
    }

    public function index()
    {
        $this->authorizeRead();

        return view('admin.protocol_list.index', [
            'rows' => $this->protocolListService->pendingRows(),
            'importBatches' => $this->protocolListService->recentImportBatches(),
            'activationHistory' => $this->protocolListService->recentActivationHistory(),
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $this->authorizeWrite();

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xls,xlsx'],
        ]);

        $result = $this->protocolListService->parseSpreadsheet($validated['file'], $this->actorContext());

        return response()->json([
            'success' => true,
            'message' => 'Spreadsheet parsed successfully.',
            'rows' => $result['rows'],
            'batch' => $result['batch'],
            'import_batches' => $this->protocolListService->recentImportBatches(),
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $this->authorizeWrite();

        $validated = $request->validate([
            'rows' => ['required', 'array'],
        ]);

        $result = $this->protocolListService->saveRows($validated['rows'], $this->actorContext());
        if (! $result['saved']) {
            return response()->json([
                'success' => false,
                'message' => 'Some rows need attention before the table can be saved.',
                'errors' => $result['errors'],
                'import_batches' => $result['import_batches'] ?? $this->protocolListService->recentImportBatches(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Protocol list saved successfully.',
            'rows' => $result['rows'],
            'import_batches' => $result['import_batches'] ?? $this->protocolListService->recentImportBatches(),
        ]);
    }

    public function snapshot(): JsonResponse
    {
        $this->authorizeRead();

        return response()->json([
            'success' => true,
            'rows' => $this->protocolListService->pendingRows(),
            'import_batches' => $this->protocolListService->recentImportBatches(),
            'activation_history' => $this->protocolListService->recentActivationHistory(),
        ]);
    }

    public function destroy(ProtocolList $protocolList): JsonResponse
    {
        $this->authorizeWrite();

        $this->protocolListService->deleteRow($protocolList);

        return response()->json([
            'success' => true,
            'message' => 'Participant removed from the protocol list.',
        ]);
    }

    private function authorizeRead(): void
    {
        $user = backpack_user();

        if (! $user || ! $user->can('student.read.all')) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function authorizeWrite(): void
    {
        $user = backpack_user();

        if (! $user || ! $user->can('student.update.all')) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function actorContext(): array
    {
        $user = backpack_user();

        return [
            'id' => $user?->getKey(),
            'name' => $user?->name ?: $user?->email,
        ];
    }
}
