<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolEnquiry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SchoolEnquiryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SchoolEnquiry::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by source
        if ($request->has('source')) {
            $query->where('source', $request->source);
        }

        // Filter by level interested
        if ($request->has('level_interested')) {
            $query->where('level_interested', $request->level_interested);
        }

        // Search by name, email, or phone
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Order by created date descending
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 10);
        $enquiries = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $enquiries->items(),
            'pagination' => [
                'current_page' => $enquiries->currentPage(),
                'per_page' => $enquiries->perPage(),
                'total' => $enquiries->total(),
                'last_page' => $enquiries->lastPage()
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'level_interested' => 'required|in:QT,CSEE,ASCEE,English Course,ECDE,Pre Form One',
            'source' => 'required|in:phone_call,walk_in,whatsapp,facebook,email,website',
            'message' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000'
        ]);

        $enquiry = SchoolEnquiry::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Enquiry created successfully',
            'data' => $enquiry
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $enquiry = SchoolEnquiry::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $enquiry
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $enquiry = SchoolEnquiry::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'phone' => 'sometimes|string|max:20',
            'level_interested' => 'sometimes|in:QT,CSEE,ASCEE,English Course,ECDE,Pre Form One',
            'source' => 'sometimes|in:phone_call,walk_in,whatsapp,facebook,email,website',
            'status' => 'sometimes|in:new,contacted,followed_up,converted,lost',
            'message' => 'sometimes|string|max:1000',
            'follow_up_date' => 'sometimes|date',
            'notes' => 'sometimes|string|max:1000'
        ]);

        $enquiry->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Enquiry updated successfully',
            'data' => $enquiry
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $enquiry = SchoolEnquiry::findOrFail($id);
        $enquiry->delete();

        return response()->json([
            'success' => true,
            'message' => 'Enquiry deleted successfully'
        ]);
    }

    public function statistics(): JsonResponse
    {
        $total = SchoolEnquiry::count();
        $new = SchoolEnquiry::where('status', 'new')->count();
        $contacted = SchoolEnquiry::where('status', 'contacted')->count();
        $followedUp = SchoolEnquiry::where('status', 'followed_up')->count();
        $converted = SchoolEnquiry::where('status', 'converted')->count();
        $lost = SchoolEnquiry::where('status', 'lost')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'new' => $new,
                'contacted' => $contacted,
                'followed_up' => $followedUp,
                'converted' => $converted,
                'lost' => $lost,
                'conversion_rate' => $total > 0 ? round(($converted / $total) * 100, 1) : 0
            ]
        ]);
    }
}
