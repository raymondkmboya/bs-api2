<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolEnquiry;
use App\Models\SchoolEnquiryFollowUp;
use App\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FrontOfficeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $enquiry = SchoolEnquiry::with(['followUps'])->get();

         return response()->json([
            'success' => true,
            'message' => '',
            'data' => $enquiry
        ], 200);



        // // Filter by status
        // if ($request->has('status')) {
        //     $query->where('status', $request->status);
        // }

        // // Filter by source
        // if ($request->has('source')) {
        //     $query->where('source', $request->source);
        // }

        // // Filter by level interested
        // if ($request->has('level_interested')) {
        //     $query->where('level_interested', $request->level_interested);
        // }

        // // Search by name, email, or phone
        // if ($request->has('search')) {
        //     $search = $request->search;
        //     $query->where(function ($q) use ($search) {
        //         $q->where('first_name', 'like', "%{$search}%")
        //           ->orWhere('last_name', 'like', "%{$search}%")
        //           ->orWhere('email', 'like', "%{$search}%")
        //           ->orWhere('phone', 'like', "%{$search}%");
        //     });
        // }

        // // Order by created date descending
        // $query->orderBy('created_at', 'desc');

        // // Pagination
        // $perPage = $request->get('per_page', 10);
        // $enquiries = $query->paginate($perPage);

        // return response()->json([
        //     'success' => true,
        //     'data' => $enquiries->items(),
        //     'pagination' => [
        //         'current_page' => $enquiries->currentPage(),
        //         'per_page' => $enquiries->perPage(),
        //         'total' => $enquiries->total(),
        //         'last_page' => $enquiries->lastPage()
        //     ]
        // ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:10',
            'level_interested' => 'required|in:QT,CSEE,ASCEE,English Course,ECDE,Pre Form One',
            'source' => 'required|in:Phone,Walk in,Whatsapp,Facebook,Email,Website',
            'message' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'follow_up_date' => 'nullable|date'
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
        // $contacted = SchoolEnquiry::where('status', 'contacted')->count();
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

    // School Enquiry Follow Up Methods
    public function getEnquiryFollowUps(): JsonResponse
    {
        $followUps = SchoolEnquiryFollowUp::with(['createdBy.staff' => function($query) {
                $query->select('id', 'first_name', 'last_name');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $followUps
        ]);
    }

    public function getEnquiryFollowUpsById($enquiryId): JsonResponse
    {
        $followUps = SchoolEnquiryFollowUp::where('school_enquiry_id', $enquiryId)
            ->with(['createdBy.staff' => function($query) {
                $query->select('id', 'first_name', 'last_name');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $followUps
        ]);
    }

    public function storeEnquiryFollowUp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'school_enquiry_id' => 'required|exists:school_enquiries,id',
            'follow_up_date' => 'required|date',
            'medium_used' => 'required|in:phone,email,sms,whatsapp,in_person,social_media',
            'message_content' => 'nullable|string|max:1000',
            'next_follow_up_date' => 'nullable|date|after_or_equal:follow_up_date',
            'status' => 'required|in:pending,contacted,interested,not_interested,enrolled,stop_follow_up',
            'notes' => 'nullable|string|max:500'
        ]);

        // Add created_by from authenticated user
        $validated['created_by'] = auth()->id();

        $followUp = SchoolEnquiryFollowUp::create($validated);

        // Update enquiry status if needed
        $enquiry = SchoolEnquiry::find($validated['school_enquiry_id']);
        if ($validated['status'] === 'enrolled') {
            $enquiry->update(['status' => 'converted']);
        } elseif ($validated['status'] === 'contacted') {
            $enquiry->update(['status' => 'followed_up']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Follow up created successfully',
            'data' => $followUp->load(['createdBy.staff' => function($query) {
                $query->select('id', 'first_name', 'last_name');
            }])
        ], 201);
    }

    public function updateEnquiryFollowUp(Request $request, $id): JsonResponse
    {
        $followUp = SchoolEnquiryFollowUp::findOrFail($id);

        $validated = $request->validate([
            'follow_up_date' => 'sometimes|date',
            'medium_used' => 'sometimes|in:phone,email,sms,whatsapp,in_person,social_media',
            'message_content' => 'nullable|string|max:1000',
            'next_follow_up_date' => 'nullable|date|after_or_equal:follow_up_date',
            'status' => 'sometimes|in:pending,contacted,interested,not_interested,enrolled,stop_follow_up',
            'notes' => 'nullable|string|max:500'
        ]);

        $followUp->update($validated);

        // Update enquiry status if needed
        if (isset($validated['status'])) {
            $enquiry = SchoolEnquiry::find($followUp->school_enquiry_id);
            if ($validated['status'] === 'enrolled') {
                $enquiry->update(['status' => 'converted']);
            } elseif ($validated['status'] === 'contacted') {
                $enquiry->update(['status' => 'followed_up']);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Follow up updated successfully',
            'data' => $followUp->load(['createdBy.staff' => function($query) {
                $query->select('id', 'first_name', 'last_name');
            }])
        ]);
    }

    public function deleteEnquiryFollowUp($id): JsonResponse
    {
        $followUp = SchoolEnquiryFollowUp::findOrFail($id);
        $followUp->delete();

        return response()->json([
            'success' => true,
            'message' => 'Follow up deleted successfully'
        ]);
    }

    public function getEnquiryFollowUpStats(): JsonResponse
    {
        $total = SchoolEnquiryFollowUp::count();
        $pending = SchoolEnquiryFollowUp::pending()->count();
        $contacted = SchoolEnquiryFollowUp::where('status', 'contacted')->count();
        $interested = SchoolEnquiryFollowUp::where('status', 'interested')->count();
        $enrolled = SchoolEnquiryFollowUp::where('status', 'enrolled')->count();
        $notInterested = SchoolEnquiryFollowUp::where('status', 'not_interested')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'pending' => $pending,
                'contacted' => $contacted,
                'interested' => $interested,
                'enrolled' => $enrolled,
                'not_interested' => $notInterested,
                'conversion_rate' => $total > 0 ? round(($enrolled / $total) * 100, 1) : 0
            ]
        ]);
    }

    // Advertisement Management
    public function getAdverts(): JsonResponse
    {
        $adverts = Advertisement::with(['user.staff'])
        ->orderBy('advert_date', 'desc')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $adverts
        ]);
    }

    public function storeAdvert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cost' => 'required|numeric|min:0',
            'medium' => 'required|string|max:255',
            'advert_date' => 'required|date',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500'
        ]);

        $validated['created_by'] = auth()->id();
        $validated['status'] = $validated['status'] ?? 'active';

        $advert = Advertisement::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Advertisement created successfully',
            'data' => []
        ], 201);
    }

    public function showAdvert($id): JsonResponse
    {
        $advert = Advertisement::with('staff')
        ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $advert
        ]);
    }

    public function updateAdvert(Request $request, $id): JsonResponse
    {
        $advert = Advertisement::findOrFail($id);

        $validated = $request->validate([
            'cost' => 'sometimes|required|numeric|min:0',
            'medium' => 'sometimes|required|string|max:255',
            'advert_date' => 'sometimes|required|date',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500'
        ]);

        $advert->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Advertisement updated successfully',
            'data' => $advert->load('staff')
        ]);
    }

    public function destroyAdvert($id): JsonResponse
    {
        $advert = Advertisement::findOrFail($id);
        $advert->delete();

        return response()->json([
            'success' => true,
            'message' => 'Advertisement deleted successfully'
        ]);
    }

    public function getAdvertStats(): JsonResponse
    {
        $total = Advertisement::count();
        $active = Advertisement::where('status', 'active')->count();
        $inactive = Advertisement::where('status', 'inactive')->count();
        $totalCost = Advertisement::sum('cost');
        $avgCost = $total > 0 ? $totalCost / $total : 0;

        // Performance tracking - students per advertisement medium
        $studentCounts = [];
        $mediums = ['Phone Call', 'Road Posters', 'Car Posters', 'Radio', 'TV', 'SMS'];

        foreach ($mediums as $medium) {
            $studentCount = \App\Models\Student::where('hear_from_source', $medium)->count();
            $studentCounts[$medium] = $studentCount;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'active' => $active,
                'inactive' => $inactive,
                'total_cost' => $totalCost,
                'average_cost' => round($avgCost, 2),
                'performance' => $studentCounts
            ]
        ]);
    }


}
