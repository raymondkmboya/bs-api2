<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\StudentAttendanceController;
use App\Http\Controllers\Api\SchoolEnquiryController;
use App\Http\Controllers\Api\FeeGroupController;
use App\Http\Controllers\Api\AcademicController;
use App\Http\Controllers\Api\HumanResourceController;
use App\Http\Controllers\Api\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/selcom/callback', [StudentController::class, 'handleSeclomCallback'])->name('selcom.callback');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Authentication
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Students Management
    // Route::prefix('students')->group(function () {
    //     Route::get('/', [StudentController::class, 'index']);
    //     Route::post('/register', [StudentController::class, 'register']); // Stage 1: Registration
    //     Route::post('/{id}/admit', [StudentController::class, 'admit']); // Stage 2: Admission
    //     Route::post('/{id}/enroll', [StudentController::class, 'enroll']); // Stage 3: Enrollment
    //     Route::get('/{id}', [StudentController::class, 'show']);
    //     Route::put('/{id}', [StudentController::class, 'update']);
    //     Route::delete('/{id}', [StudentController::class, 'destroy']);
    //     Route::get('/register', [StudentController::class, 'getRegistered']); // Get registered students
    //     Route::get('/admit', [StudentController::class, 'getAdmitted']); // Get admitted students
    //     Route::get('/enroll', [StudentController::class, 'getEnrolled']); // Get enrolled students
    //     Route::get('/by-level-stream', [StudentController::class, 'getByLevelAndStream']);
    // });

    Route::prefix('students')->group(function () {
        // 1. Static GET routes MUST come first
        Route::get('/register', [StudentController::class, 'getRegistered']);
        Route::get('/admit', [StudentController::class, 'getAdmitted']);
        Route::get('/enroll', [StudentController::class, 'getEnrolled']);
        Route::get('/by-level-stream', [StudentController::class, 'getByLevelAndStream']);

        // 2. Standard Resource-like routes
        Route::get('/', [StudentController::class, 'index']);
        Route::post('/register', [StudentController::class, 'register']);

        // 3. Wildcard routes MUST come last
        Route::get('/{id}', [StudentController::class, 'show']); // Laravel won't confuse 'register' for an ID now
        Route::put('/{id}', [StudentController::class, 'update']);
        Route::delete('/{id}', [StudentController::class, 'destroy']);

        // 4. Action routes
        Route::post('/{id}/admit', [StudentController::class, 'admit']);
        Route::post('/{id}/enroll', [StudentController::class, 'enroll']);
    });

    // Student Attendance
    Route::prefix('attendance')->group(function () {
        Route::get('/', [StudentAttendanceController::class, 'index']);
        Route::post('/', [StudentAttendanceController::class, 'store']);
        Route::get('/{id}', [StudentAttendanceController::class, 'show']);
        Route::put('/{id}', [StudentAttendanceController::class, 'update']);
        Route::delete('/{id}', [StudentAttendanceController::class, 'destroy']);
        Route::get('/statistics', [StudentAttendanceController::class, 'statistics']);
        Route::get('/export', [StudentAttendanceController::class, 'export']);
    });

    // School Enquiries (Front Office)
    Route::prefix('enquiries')->group(function () {
        Route::get('/', [SchoolEnquiryController::class, 'index']);
        Route::post('/', [SchoolEnquiryController::class, 'store']);
        Route::get('/{id}', [SchoolEnquiryController::class, 'show']);
        Route::put('/{id}', [SchoolEnquiryController::class, 'update']);
        Route::delete('/{id}', [SchoolEnquiryController::class, 'destroy']);
        Route::get('/statistics', [SchoolEnquiryController::class, 'statistics']);
    });

    // Fee Management
    Route::prefix('fees')->group(function () {
        Route::get('/groups', [FeeGroupController::class, 'index']);
        Route::post('/groups', [FeeGroupController::class, 'store']);
        Route::get('/groups/{id}', [FeeGroupController::class, 'show']);
        Route::put('/groups/{id}', [FeeGroupController::class, 'update']);
        Route::delete('/groups/{id}', [FeeGroupController::class, 'destroy']);
    });

    // Academic Management
    Route::prefix('academics')->group(function () {
        // Classrooms
        Route::prefix('classrooms')->group(function () {
            Route::get('/', [AcademicController::class, 'getClassRooms']);
            Route::post('/', [AcademicController::class, 'storeClassRoom']);
            Route::get('/{id}', [AcademicController::class, 'getClassRoom']);
            Route::put('/{id}', [AcademicController::class, 'updateClassRoom']);
            Route::delete('/{id}', [AcademicController::class, 'destroyClassRoom']);
            Route::get('/type/{type}', [AcademicController::class, 'getByType']);
            Route::get('/available', [AcademicController::class, 'getAvailable']);
        });

        //Classlevels
        Route::prefix('classlevels')->group(function () {
            Route::get('/', [AcademicController::class, 'getClassLevels']);
            Route::post('/', [AcademicController::class, 'storeClassLevel']);
            Route::get('/{id}', [AcademicController::class, 'getClassLevel']);
            Route::put('/{id}', [AcademicController::class, 'updateClassLevel']);
            Route::delete('/{id}', [AcademicController::class, 'destroyClassLevel']);
        });

        // classlevel streams
        Route::prefix('classlevelstreams')->group(function () {
            Route::get('/', [AcademicController::class, 'getClassLevelStreams']);
            Route::post('/', [AcademicController::class, 'storeClassLevelStream']);
            Route::get('/{id}', [AcademicController::class, 'getClassLevelStream']);
            Route::put('/{id}', [AcademicController::class, 'updateClassLevelStream']);
            Route::delete('/{id}', [AcademicController::class, 'destroyClassLevelStream']);
        });

        // compass
        Route::prefix('compass')->group(function () {
            Route::get('/', [AcademicController::class, 'getCompass']);
            Route::post('/', [AcademicController::class, 'storeCompass']);
            Route::get('/{id}', [AcademicController::class, 'getCompassEntry']);
            Route::put('/{id}', [AcademicController::class, 'updateCompass']);
            Route::delete('/{id}', [AcademicController::class, 'destroyCompass']);
        });

    });

    // Human Resources Management
    Route::prefix('hr')->group(function () {
        // Staff Management
        Route::prefix('staff')->group(function () {
            Route::get('/', [HumanResourceController::class, 'getStaffMembers']);
            Route::get('/active', [HumanResourceController::class, 'getActiveStaffMembers']);
            Route::get('/teachers', [HumanResourceController::class, 'getTeachers']);
            Route::get('/administrative', [HumanResourceController::class, 'getAdministrativeStaff']);
            Route::get('/department/{department}', [HumanResourceController::class, 'getStaffByDepartment']);
            Route::get('/statistics', [HumanResourceController::class, 'getStaffStatistics']);
            Route::post('/', [HumanResourceController::class, 'createStaffMember']);
            Route::get('/{id}', [HumanResourceController::class, 'getStaffMember']);
            Route::put('/{id}', [HumanResourceController::class, 'updateStaffMember']);
            Route::delete('/{id}', [HumanResourceController::class, 'deleteStaffMember']);
        });

        // Department Management
        Route::prefix('departments')->group(function () {
            Route::get('/', [HumanResourceController::class, 'getDepartments']);
            Route::get('/active', [HumanResourceController::class, 'getActiveDepartments']);
            Route::get('/statistics', [HumanResourceController::class, 'getDepartmentStatistics']);
            Route::post('/', [HumanResourceController::class, 'createDepartment']);
            Route::get('/{id}', [HumanResourceController::class, 'getDepartment']);
            Route::put('/{id}', [HumanResourceController::class, 'updateDepartment']);
            Route::delete('/{id}', [HumanResourceController::class, 'deleteDepartment']);
        });
    });

    // Payment Management Routes
    Route::prefix('payments')->name('payments.')->group(function () {

        // Payment CRUD


        // Transaction Management
        Route::post('/{paymentId}/transactions', [PaymentController::class, 'addTransaction'])->name('add.transaction');

        // Reports and Analytics
        Route::get('/statistics', [PaymentController::class, 'statistics'])->name('statistics');
        Route::get('/student/{studentId}', [PaymentController::class, 'studentPaymentHistory'])->name('student.history');

        // Fee Structure
        Route::get('/fee-structure', [PaymentController::class, 'feeStructure'])->name('fee.structure');

        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('/{id}', [PaymentController::class, 'show'])->name('show');
        Route::post('/', [PaymentController::class, 'store'])->name('store');

    });

});

// Test route
// Route::get('/test', function () {
//     return response()->json([
//         'message' => 'API is working!',
//         'timestamp' => now()->toDateTimeString(),
//         'version' => '1.0.0'
//     ]);
// });

// Temporary public classroom routes for testing
// Route::prefix('academics')->group(function () {
//     Route::prefix('classrooms')->group(function () {
//         Route::get('/', [AcademicController::class, 'index']);
//         Route::get('/{id}', [AcademicController::class, 'show']);
//     });
// });
