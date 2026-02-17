<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeGroup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FeeGroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = FeeGroup::query();

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Order by name
        $query->orderBy('name');

        // Pagination
        $perPage = $request->get('per_page', 10);
        $feeGroups = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $feeGroups->items(),
            'pagination' => [
                'current_page' => $feeGroups->currentPage(),
                'per_page' => $feeGroups->perPage(),
                'total' => $feeGroups->total(),
                'last_page' => $feeGroups->lastPage()
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:fee_groups,name',
            'type' => 'required|in:mandatory,optional,one_time',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0',
            'status' => 'sometimes|in:active,inactive'
        ]);

        $feeGroup = FeeGroup::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Fee group created successfully',
            'data' => $feeGroup
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $feeGroup = FeeGroup::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $feeGroup
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $feeGroup = FeeGroup::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:fee_groups,name,' . $id,
            'type' => 'sometimes|in:mandatory,optional,one_time',
            'description' => 'sometimes|string|max:1000',
            'amount' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:active,inactive'
        ]);

        $feeGroup->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Fee group updated successfully',
            'data' => $feeGroup
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $feeGroup = FeeGroup::findOrFail($id);
        $feeGroup->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fee group deleted successfully'
        ]);
    }
}
