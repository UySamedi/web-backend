<?php 

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    // ✅ Create Course
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'schedule' => 'required|string',
        ]);

        $course = Course::create($validated);

        return response()->json($course, 201);
    }

    // ✅ List Courses
    public function index()
    {
        return response()->json(Course::all());
    }

    // ✅ Update Course
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'schedule' => 'required|string',
        ]);

        $course = Course::findOrFail($id);
        $course->update($validated);

        return response()->json($course);
    }

    // ✅ Delete Course
    public function destroy($id)
    {
        $course = Course::findOrFail($id);
        $course->delete();

        return response()->json(['message' => 'Course deleted successfully']);
    }
}
