<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\StudentAttendanceController;
use App\Http\Controllers\Api\FrontOfficeController;
use App\Http\Controllers\Api\FeeGroupController;
use App\Http\Controllers\Api\AcademicController;
use App\Http\Controllers\Api\HumanResourceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\FollowUpController;


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
Route::get('/students/{id}/print-registration-form', [StudentController::class, 'printRegistrationForm']); // Public print route

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Authentication
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Students Management
    Route::prefix('students')->group(function () {
        // 1. Static GET routes MUST come first
        Route::get('/register', [StudentController::class, 'getRegistered']);
        Route::get('/admit', [StudentController::class, 'getAdmitted']);
        Route::get('/enroll', [StudentController::class, 'getEnrolled']);
        Route::get('/by-level-stream', [StudentController::class, 'getByLevelAndStream']);

        Route::get('/registration-stats', [StudentController::class, 'getRegistrationStats']);
        Route::get('/admission-stats', [StudentController::class, 'getAdmissionStats']);

        // Follow-up Management
        Route::get('/{studentId}/follow-ups', [FollowUpController::class, 'getStudentFollowUps'])->name('student.follow-ups');
        Route::post('/follow-ups', [FollowUpController::class, 'store'])->name('follow-ups.store');
        Route::get('/students-needing-follow-up', [FollowUpController::class, 'getStudentsNeedingFollowUp'])->name('follow-ups.students');
        Route::get('/follow-up-stats', [FollowUpController::class, 'getFollowUpStats'])->name('follow-ups.stats');
        Route::get('/followups', [FollowUpController::class, 'index'])->name('follow-ups.index');


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
    Route::prefix('frontoffice')->group(function () {
        Route::get('/enquiries', [FrontOfficeController::class, 'index']);
        Route::post('/enquiries', [FrontOfficeController::class, 'store']);
        Route::get('/enquiries/statistics', [FrontOfficeController::class, 'statistics']);

        // School Enquiry Follow Ups
        Route::get('/enquiries/follow-ups', [FrontOfficeController::class, 'getEnquiryFollowUps']);
        Route::get('/enquiries/{enquiryId}/follow-ups', [FrontOfficeController::class, 'getEnquiryFollowUpsBy']);
        Route::post('/enquiry-follow-ups', [FrontOfficeController::class, 'storeEnquiryFollowUp']);
        Route::put('/enquiry-follow-ups/{id}', [FrontOfficeController::class, 'updateEnquiryFollowUp']);
        Route::delete('/enquiry-follow-ups/{id}', [FrontOfficeController::class, 'deleteEnquiryFollowUp']);
        Route::get('/enquiry-follow-ups/statistics', [FrontOfficeController::class, 'getEnquiryFollowUpStats']);

        // Adverts
        Route::get('/adverts', [FrontOfficeController::class, 'getAdverts']);
        Route::post('/adverts', [FrontOfficeController::class, 'storeAdvert']);
        Route::get('/adverts/statistics', [FrontOfficeController::class, 'getAdvertStats']);

        //move routes with Ids last
        Route::get('/enquiries/{id}', [FrontOfficeController::class, 'show']);
        Route::put('/enquiries/{id}', [FrontOfficeController::class, 'update']);
        Route::delete('/enquiries/{id}', [FrontOfficeController::class, 'destroy']);

        // Adverts with IDs
        Route::get('/adverts/{id}', [FrontOfficeController::class, 'showAdvert']);
        Route::put('/adverts/{id}', [FrontOfficeController::class, 'updateAdvert']);
        Route::delete('/adverts/{id}', [FrontOfficeController::class, 'destroyAdvert']);

    });

    // Fee Management
    // Route::prefix('fees')->group(function () {
    //     Route::get('/groups', [FeeGroupController::class, 'index']);
    //     Route::post('/groups', [FeeGroupController::class, 'store']);
    //     Route::get('/groups/{id}', [FeeGroupController::class, 'show']);
    //     Route::put('/groups/{id}', [FeeGroupController::class, 'update']);
    //     Route::delete('/groups/{id}', [FeeGroupController::class, 'destroy']);
    // });

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
            Route::get('/classlevel/{classLevelId}', [AcademicController::class, 'getStreamsByClassLevel']);
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

        //subjects
        Route::prefix('subjects')->group(function () {
            Route::get('/teachers', [AcademicController::class, 'getTeachersWithSubjects']);
            Route::get('/classlevel/{classLevelId}', [AcademicController::class, 'getSubjectsByClassLevel']);
            Route::get('/', [AcademicController::class, 'getAllSubjects']);
        });

        //exams
        Route::prefix('exams')->group(function () {
            Route::get('/', [AcademicController::class, 'getExams']);
            Route::post('/', [AcademicController::class, 'createExam']);
            Route::get('/{id}', [AcademicController::class, 'getExam']);
            Route::put('/{id}', [AcademicController::class, 'updateExam']);
            Route::delete('/{id}', [AcademicController::class, 'deleteExam']);
            Route::get('/{id}/results', [AcademicController::class, 'getExamResultsByExam']);
            Route::get('/{id}/statistics', [AcademicController::class, 'getExamStatistics']);
        });

        //exam results
        Route::prefix('exam-results')->group(function () {
            Route::get('/', [AcademicController::class, 'getExamResults']);
            Route::post('/', [AcademicController::class, 'createExamResults']);
            Route::put('/{id}', [AcademicController::class, 'updateExamResult']);
            Route::delete('/{id}', [AcademicController::class, 'deleteExamResult']);
        });

        //timetable
        Route::prefix('timetable')->group(function () {
            Route::get('/', [AcademicController::class, 'getTimetable']);
            Route::get('/class', [AcademicController::class, 'getClassTimetable']);
            Route::get('/options', [AcademicController::class, 'getTimetableOptions']);
            Route::get('/statistics', [AcademicController::class, 'getTimetableStatistics']);
            Route::post('/', [AcademicController::class, 'createTimetableEntry']);
            Route::post('/bulk', [AcademicController::class, 'bulkCreateTimetable']);
            Route::put('/{id}', [AcademicController::class, 'updateTimetableEntry']);
            Route::delete('/{id}', [AcademicController::class, 'deleteTimetableEntry']);
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

        // Transaction Management
        Route::post('/transactions', [PaymentController::class, 'addTransaction'])->name('add.transaction');
        Route::get('/transactions/student/{studentId}', [PaymentController::class, 'studentTransactions'])->name('student.transaction');
        Route::get('/transactions', [PaymentController::class, 'studentsTransactions'])->name('students.transaction');
        Route::get('/receipt/{transactionId}', [PaymentController::class, 'generateReceipt'])->name('generate.receipt');
        Route::put('/transactions/{transactionId}/approve', [PaymentController::class, 'approveTransaction'])->name('approve.transaction');
        Route::put('/transactions/{transactionId}/reject', [PaymentController::class, 'rejectTransaction'])->name('reject.transaction');

        // Reports and Analytics
        Route::get('/statistics', [PaymentController::class, 'statistics'])->name('statistics');
        Route::get('/payment-stats', [PaymentController::class, 'getPaymentStats'])->name('payment.stats');
        Route::get('/student/{studentId}', [PaymentController::class, 'studentPayments'])->name('student.payment');
        Route::get('/student/statement/{studentId}', [PaymentController::class, 'generateStudentStatement'])->name('student.statement');
        Route::post('/income-statement', [PaymentController::class, 'generateIncomeStatement'])->name('income.statement');

        // Fee Structure
        Route::get('/fee-structures', [PaymentController::class, 'getFeeStructure'])->name('fee.structure');
        Route::get('/fee-structure/class-level/{classLevelId}', [PaymentController::class, 'classLevelFeeStructure'])->name('fee.structure.class.level');
        Route::get('/fee-groups', [PaymentController::class, 'getFeeGroups'])->name('fee.groups');

        // Generate Control Number
        Route::post('/control-number/{paymentId}', [PaymentController::class, 'generateControlNumber'])->name('control.number');

        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('/{id}', [PaymentController::class, 'show'])->name('show');
        Route::post('/', [PaymentController::class, 'store'])->name('store');

    });

});
