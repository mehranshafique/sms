<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Institute;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
class StudentController extends BaseController
{
    public function __construct(){
        $this->middleware('auth');
        $this->setPageTitle('Students');
    }
    public function index()
    {
        $students = Student::with('institute')->where('institute_id', institute()->id)->get();
        return view('students.index', compact('students'));
    }

    public function create()
    {
        $this->setPageTitle('Add Student');

        return view('students.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'required|date',
            'status' => 'required|in:active,transferred,withdrawn,graduated',
        ]);

        $lastStudent = Student::where('institute_id', institute()->id)
            ->latest('id')
            ->first();

        if ($lastStudent) {
            $number = intval(substr($lastStudent->registration_no, -4)) + 1;
        } else {
            $number = 1;
        }

        // Format registration number: INSTITUTEID-0001
        $registration_no = institute()->id . str_pad($number, 4, '0', STR_PAD_LEFT);

        $student = new Student();

        $student->first_name = $request->input('first_name');
        $student->last_name = $request->input('last_name');
        $student->gender = $request->input('gender');
        $student->date_of_birth = $request->input('date_of_birth');
        $student->status = $request->input('status');
        $student->national_id = $request->input('national_id');
        $student->nfc_tag_uid = $request->input('nfc_tag_uid');
        $student->qr_code_token = $request->input('qr_code_token');
        $student->institute_id  = institute()->id;
        $student->registration_no  = $registration_no;
        $student->save();

        return response()->json([
            'message' => 'Student created successfully',
            'redirect' => route('students.index')
        ]);
    }

    public function edit(Student $student)
    {
        $institutes = Institute::where('is_active',1)->get();
        return view('students.edit', compact('student','institutes'));
    }

//    public function update(Request $request, Student $student)
//    {
//        $request->validate([
//            'institute_id' => 'required|exists:institutes,id',
//            'registration_no' => 'required|max:50',
//            'first_name' => 'required|max:100',
//            'last_name' => 'required|max:100',
//            'gender' => 'required|in:male,female,other',
//            'date_of_birth' => 'required|date',
//            'status' => 'required|in:active,transferred,withdrawn,graduated',
//        ]);
//
//        $student->update($request->all());
//
//        return response()->json([
//            'message' => 'Student updated successfully',
//            'redirect' => route('students.index')
//        ]);
//    }

    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'required|date',
            'status' => 'required|in:active,transferred,withdrawn,graduated',
            'national_id' => 'nullable|max:255',
            'nfc_tag_uid' => 'nullable|max:255',
            'qr_code_token' => 'nullable|max:255',
        ]);

        // Update fields manually for safety
        $student->first_name = $request->first_name;
        $student->last_name = $request->last_name;
        $student->gender = $request->gender;
        $student->date_of_birth = $request->date_of_birth;
        $student->status = $request->status;
        $student->national_id = $request->national_id;
        $student->nfc_tag_uid = $request->nfc_tag_uid;
        $student->qr_code_token = $request->qr_code_token;

        $student->save();

        return response()->json([
            'message' => 'Student updated successfully',
            'redirect' => route('students.index')
        ]);
    }

    public function destroy(Student $student)
    {
        $student->delete();
        return back()->with('success','Student deleted successfully');
    }
}
