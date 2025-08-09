<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\EnrollmentStatusNotification;
use Illuminate\Validation\ValidationException;

class EnrollmentController extends Controller
{
    /**
     * Student enroll in a course (max 3 allowed)
     */
    public function enroll(Request $request)
    {
        try {
            $user = Auth::user();

            if ($user->role !== 'student') {
                return response()->json(['message' => 'Only students can enroll'], 403);
            }

            // Validate course_id
            $request->validate([
                'course_id' => 'required|exists:courses,id',
            ]);

            // Check max enrollments (3 max)
            $count = Enrollment::where('user_id', $user->id)->count();
            if ($count >= 3) {
                return response()->json(['message' => 'Max 3 courses allowed'], 400);
            }

            // Prevent duplicate enrollment
            $exists = Enrollment::where('user_id', $user->id)
                ->where('course_id', $request->course_id)
                ->exists();

            if ($exists) {
                return response()->json(['message' => 'Already enrolled in this course'], 400);
            }

            // Create new enrollment with pending status
            $enrollment = Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $request->course_id,
                'status' => 'pending',
            ]);

            return response()->json([
                'message' => 'Enrollment request submitted successfully.',
                'enrollment' => $enrollment->load('course'),
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Admin approves enrollment
     */
    public function approve(Enrollment $enrollment)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Only admin can approve'], 403);
        }

        if ($enrollment->status === 'approved') {
            return response()->json(['message' => 'Already approved'], 400);
        }

        $enrollment->update(['status' => 'approved']);

        // Notify the student
        $enrollment->user->notify(
            new EnrollmentStatusNotification('approved', $enrollment->course->title)
        );

        return response()->json([
            'message' => 'Enrollment approved successfully and student notified.',
            'enrollment' => $enrollment->load('user', 'course'),
        ]);
    }

    /**
     * Admin rejects enrollment
     */
    public function reject(Enrollment $enrollment)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Only admin can reject'], 403);
        }

        if ($enrollment->status === 'rejected') {
            return response()->json(['message' => 'Already rejected'], 400);
        }

        $enrollment->update(['status' => 'rejected']);

        // Notify the student
        $enrollment->user->notify(
            new EnrollmentStatusNotification('rejected', $enrollment->course->title)
        );

        return response()->json([
            'message' => 'Enrollment rejected and student notified.',
            'enrollment' => $enrollment->load('user', 'course'),
        ]);
    }

    /**
     * Admin view all enrollments
     */
    public function index()
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Only admin can view all enrollments'], 403);
        }

        $enrollments = Enrollment::with('user', 'course')->get();

        return response()->json($enrollments);
    }

    /**
     * Student view their enrollments
     */
    public function myEnrollments()
    {
        $enrollments = Enrollment::with('course')
            ->where('user_id', Auth::id())
            ->get();

        return response()->json($enrollments);
    }

    /**
     * Admin view enrollments by user
     */
    public function getEnrollmentsByUser(Request $request)
    {
        try {
            $user = Auth::user();
            if ($user->role !== 'admin') {
                return response()->json(['message' => 'Only admin can view user enrollments'], 403);
            }

            $request->validate([
                'user_id' => 'required|exists:users,id',
            ]);

            $enrollments = Enrollment::with('course')
                ->where('user_id', $request->user_id)
                ->get();

            if ($enrollments->isEmpty()) {
                return response()->json([
                    'message' => 'No enrollments found for this user.',
                    'enrollments' => [],
                ], 200);
            }

            return response()->json([
                'message' => 'Enrollments retrieved successfully.',
                'enrollments' => $enrollments,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
