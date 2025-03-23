<?php

namespace Modules\Tenant\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Models\Document;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Document::query();

        // Apply filters if provided
        if ($request->has('file_type')) {
            $query->where('file_type', $request->file_type);
        }

        if ($request->has('uploaded_by')) {
            $query->where('uploaded_by', $request->uploaded_by);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Apply sorting
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Paginate results
        $perPage = $request->get('per_page', 15);
        $documents = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $documents
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'file' => 'required|file|max:10240', // 10MB max file size
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $fileType = $file->getClientMimeType();
            $fileSize = $file->getSize();
            
            // Store the file in a tenant-specific directory
            $tenantId = app('tenant.manager')->getTenant()->id;
            $path = $file->storeAs("tenants/{$tenantId}/documents", $fileName, 'public');

            $document = new Document();
            $document->name = $request->name;
            $document->file_path = $path;
            $document->file_type = $fileType;
            $document->file_size = $fileSize;
            $document->uploaded_by = $request->user()->id; // Assuming auth user is a CompanyUser
            $document->save();

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'data' => $document
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $document = Document::with('uploader')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $document
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $document = Document::findOrFail($id);
            $document->name = $request->name;
            $document->save();

            return response()->json([
                'success' => true,
                'message' => 'Document updated successfully',
                'data' => $document
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $document = Document::findOrFail($id);
            
            // Delete the file from storage
            Storage::disk('public')->delete($document->file_path);
            
            // Delete the document record
            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download the specified document.
     *
     * @param string $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function download($id)
    {
        try {
            $document = Document::findOrFail($id);
            $path = storage_path('app/public/' . $document->file_path);

            if (!file_exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            return response()->download($path, $document->name);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download document',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}