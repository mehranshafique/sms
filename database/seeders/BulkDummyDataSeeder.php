<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Models\User;
use App\Models\Institution;
use App\Models\Campus;
use App\Models\AcademicSession;
use App\Models\GradeLevel;
use App\Models\ClassSection;
use App\Models\Subject;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\FeeType;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Timetable;
use App\Models\Exam;
use App\Models\ExamRecord;
use App\Models\StudentAttendance;
use App\Enums\UserType;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class BulkDummyDataSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('fr_FR'); // French Locale for DRC context
        
        $this->command->info('🌱 Seeding DR Congo School Data...');

        // 1. Institution
        $institution = Institution::firstOrCreate(
            ['code' => 'CSMA-KIN'],
            [
                'name' => 'Complexe Scolaire Mont Amba',
                'type' => 'mixed',
                'country' => 'DR Congo',
                'city' => 'Kinshasa',
                'address' => 'Avenue de l\'Université, Lemba',
                'phone' => '+243 81 555 0000',
                'email' => 'info@montamba.cd',
                'is_active' => true,
            ]
        );

        // 2. Campus
        $campus = Campus::firstOrCreate(
            ['code' => 'CMP-LEMBA', 'institution_id' => $institution->id],
            [
                'name' => 'Campus Principal Lemba',
                'address' => 'Lemba Super, Kinshasa',
                'city' => 'Kinshasa',
                'phone' => '+243 82 555 1111',
                'is_active' => true,
            ]
        );

        // 3. Academic Session
        $session = AcademicSession::firstOrCreate(
            ['institution_id' => $institution->id, 'name' => '2024-2025'],
            [
                'start_date' => '2024-09-04',
                'end_date' => '2025-07-02',
                'status' => 'active',
                'is_current' => true,
            ]
        );

        // 4. Grade Levels (French System)
        $gradesList = [
            ['name' => '1ère Primaire', 'code' => '1P', 'cycle' => 'primary', 'order' => 1],
            ['name' => '2ème Primaire', 'code' => '2P', 'cycle' => 'primary', 'order' => 2],
            ['name' => '3ème Primaire', 'code' => '3P', 'cycle' => 'primary', 'order' => 3],
            ['name' => '4ème Primaire', 'code' => '4P', 'cycle' => 'primary', 'order' => 4],
            ['name' => '5ème Primaire', 'code' => '5P', 'cycle' => 'primary', 'order' => 5],
            ['name' => '6ème Primaire', 'code' => '6P', 'cycle' => 'primary', 'order' => 6],
            ['name' => '7ème Education de Base', 'code' => '7EB', 'cycle' => 'secondary', 'order' => 7],
            ['name' => '8ème Education de Base', 'code' => '8EB', 'cycle' => 'secondary', 'order' => 8],
            ['name' => '3ème Humanités (Littéraire)', 'code' => '3HL', 'cycle' => 'secondary', 'order' => 9],
            ['name' => '4ème Humanités (Scientifique)', 'code' => '4HS', 'cycle' => 'secondary', 'order' => 10],
        ];

        $gradeModels = [];
        foreach ($gradesList as $g) {
            $gradeModels[$g['code']] = GradeLevel::firstOrCreate(
                ['institution_id' => $institution->id, 'name' => $g['name']],
                [
                    'code' => $g['code'],
                    'education_cycle' => $g['cycle'],
                    'order_index' => $g['order']
                ]
            );
        }

        // 5. Staff (Teachers & Admin)
        $staffRoles = ['Teacher', 'Teacher', 'Teacher', 'Teacher', 'Teacher', 'Teacher', 'Staff', 'Staff', 'Head Officer', 'Branch Admin'];
        $departments = ['Sciences', 'Lettres', 'Administration', 'Finance', 'Sport'];
        $staffIds = [];

        foreach ($staffRoles as $idx => $roleName) {
            $firstName = $faker->firstName;
            $lastName = $faker->lastName;
            $email = strtolower($firstName . '.' . $lastName . '@montamba.cd');
            
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => "$firstName $lastName",
                    'password' => Hash::make('password'),
                    'user_type' => $roleName == 'Teacher' ? 4 : ($roleName == 'Head Officer' ? 2 : 3),
                    'phone' => '+243 9' . $faker->numerify('########'),
                    'institute_id' => $institution->id,
                    'is_active' => true,
                ]
            );
            
            // Assign Role if exists
            if (Role::where('name', $roleName)->exists()) {
                $user->assignRole($roleName);
            }

            $staff = Staff::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'institution_id' => $institution->id,
                    'campus_id' => $campus->id,
                    'employee_id' => 'EMP-' . str_pad($idx + 1, 3, '0', STR_PAD_LEFT),
                    'designation' => $roleName,
                    'department' => $faker->randomElement($departments),
                    'joining_date' => $faker->date(),
                    'gender' => $faker->randomElement(['male', 'female']),
                    'address' => $faker->address,
                    'status' => 'active',
                ]
            );
            
            if ($roleName == 'Teacher') {
                $staffIds[] = $staff->id;
            }
        }

        // 6. Class Sections & Subjects
        $sections = [];
        $subjects = [];
        
        foreach ($gradeModels as $code => $grade) {
            // Create Section A
            $sections[$code] = ClassSection::firstOrCreate(
                ['institution_id' => $institution->id, 'grade_level_id' => $grade->id, 'name' => 'Section A'],
                [
                    'campus_id' => $campus->id,
                    'room_number' => 'Salle ' . $grade->order_index,
                    'capacity' => 40,
                    'staff_id' => $faker->randomElement($staffIds), // Class Teacher
                    'is_active' => true
                ]
            );

            // Create Subjects for this grade
            $subjNames = ['Mathématiques', 'Français', 'Anglais', 'Histoire', 'Géographie', 'Informatique'];
            foreach($subjNames as $subName) {
                $subjects[] = Subject::firstOrCreate(
                    ['institution_id' => $institution->id, 'grade_level_id' => $grade->id, 'name' => $subName],
                    [
                        'code' => strtoupper(substr($subName, 0, 3)) . '-' . $code,
                        'type' => 'theory',
                        'total_marks' => 20, // Sur 20
                        'passing_marks' => 10,
                        'is_active' => true
                    ]
                );
            }
        }

        // 7. Students (20 records)
        $students = [];
        for ($i = 1; $i <= 20; $i++) {
            $firstName = $faker->firstName;
            $lastName = $faker->lastName;
            $postName = $faker->lastName; // Post-nom is common in DRC
            
            // Create User Login for Student
            $sUser = User::firstOrCreate(
                ['email' => "student$i@montamba.cd"],
                [
                    'name' => "$firstName $lastName",
                    'password' => Hash::make('password'),
                    'user_type' => 5, // Student
                    'institute_id' => $institution->id,
                ]
            );
            if(Role::where('name', 'Student')->exists()) $sUser->assignRole('Student');

            $student = Student::firstOrCreate(
                ['user_id' => $sUser->id],
                [
                    'institution_id' => $institution->id,
                    'campus_id' => $campus->id,
                    'admission_number' => 'MAT-' . (2024000 + $i),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'post_name' => $postName,
                    'gender' => $faker->randomElement(['male', 'female']),
                    'dob' => $faker->dateTimeBetween('-18 years', '-6 years'),
                    'place_of_birth' => 'Kinshasa',
                    'admission_date' => '2024-09-04',
                    'father_name' => $faker->name('male'),
                    'father_phone' => '+243 81 ' . $faker->numerify('#######'),
                    'mother_name' => $faker->name('female'),
                    'primary_guardian' => 'father',
                    'province' => 'Kinshasa',
                    'avenue' => 'Av. ' . $faker->streetName,
                    'status' => 'active'
                ]
            );
            
            $students[] = $student;

            // Enroll Student (Randomly assign to a grade/section)
            $randomGradeCode = $faker->randomElement(array_keys($gradeModels));
            $section = $sections[$randomGradeCode];
            
            StudentEnrollment::firstOrCreate(
                ['academic_session_id' => $session->id, 'student_id' => $student->id],
                [
                    'institution_id' => $institution->id,
                    'grade_level_id' => $section->grade_level_id,
                    'class_section_id' => $section->id,
                    'roll_number' => $i,
                    'status' => 'active',
                    'enrolled_at' => now(),
                ]
            );
        }

        // 8. Timetable (For 6ème Primaire Section A)
        $targetSection = $sections['6P'];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $times = ['08:00', '09:00', '10:30', '11:30']; // Break at 10:00

        foreach ($days as $day) {
            foreach ($times as $startTime) {
                $sub = $faker->randomElement(Subject::where('grade_level_id', $targetSection->grade_level_id)->get());
                
                Timetable::create([
                    'institution_id' => $institution->id,
                    'academic_session_id' => $session->id,
                    'class_section_id' => $targetSection->id,
                    'subject_id' => $sub->id,
                    'teacher_id' => $faker->randomElement($staffIds),
                    'day_of_week' => $day,
                    'start_time' => $startTime,
                    'end_time' => Carbon::parse($startTime)->addHour()->format('H:i'),
                    'room_number' => $targetSection->room_number
                ]);
            }
        }

        // 9. Finance (Fees, Invoices, Payments)
        // Fee Types
        $tuitionType = FeeType::firstOrCreate(['institution_id' => $institution->id, 'name' => 'Frais Scolaires']);
        $transportType = FeeType::firstOrCreate(['institution_id' => $institution->id, 'name' => 'Transport Bus']);

        // Fee Structures
        $fees = [];
        $fees[] = FeeStructure::create([
            'institution_id' => $institution->id,
            'academic_session_id' => $session->id,
            'fee_type_id' => $tuitionType->id,
            'name' => 'Minerval 1er Trimestre',
            'amount' => 150.00,
            'frequency' => 'termly'
        ]);
        
        $fees[] = FeeStructure::create([
            'institution_id' => $institution->id,
            'academic_session_id' => $session->id,
            'fee_type_id' => $transportType->id,
            'name' => 'Bus Septembre',
            'amount' => 50.00,
            'frequency' => 'monthly'
        ]);

        // Generate Invoices & Payments for 10 Students
        for ($k = 0; $k < 10; $k++) {
            $student = $students[$k];
            $fee = $fees[0]; // Tuition

            $invoice = Invoice::create([
                'institution_id' => $institution->id,
                'academic_session_id' => $session->id,
                'student_id' => $student->id,
                'invoice_number' => 'INV-' . strtoupper(uniqid()),
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
                'total_amount' => $fee->amount,
                'status' => 'partial',
                'paid_amount' => 50.00 // Partial payment
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'fee_structure_id' => $fee->id,
                'description' => $fee->name,
                'amount' => $fee->amount
            ]);

            Payment::create([
                'invoice_id' => $invoice->id,
                'institution_id' => $institution->id,
                'transaction_id' => 'TRX-' . strtoupper(uniqid()),
                'payment_date' => now(),
                'amount' => 50.00,
                'method' => 'cash',
                'received_by' => 1 // Super Admin ID usually
            ]);
        }

        // 10. Exams & Marks (Premier Trimestre)
        $exam = Exam::create([
            'institution_id' => $institution->id,
            'academic_session_id' => $session->id,
            'name' => 'Examens du 1er Trimestre',
            'start_date' => '2024-12-10',
            'end_date' => '2024-12-20',
            'status' => 'published',
            'finalized_at' => now(),
        ]);

        // Add marks for Class 6P students
        $grade6P = $gradeModels['6P'];
        $class6P = $sections['6P'];
        $subjects6P = Subject::where('grade_level_id', $grade6P->id)->get();
        
        $enrolledStudents = StudentEnrollment::where('class_section_id', $class6P->id)->get();

        foreach ($enrolledStudents as $enrol) {
            foreach ($subjects6P as $subj) {
                ExamRecord::create([
                    'exam_id' => $exam->id,
                    'student_id' => $enrol->student_id,
                    'subject_id' => $subj->id,
                    'class_section_id' => $class6P->id,
                    'marks_obtained' => $faker->numberBetween(10, 20), // Out of 20
                    'is_absent' => false
                ]);
            }
        }

        // 11. Attendance (Last 7 days for Class 6P)
        for ($d = 0; $d < 7; $d++) {
            $date = now()->subDays($d);
            if ($date->isWeekend()) continue;

            foreach ($enrolledStudents as $enrol) {
                StudentAttendance::create([
                    'institution_id' => $institution->id,
                    'academic_session_id' => $session->id,
                    'class_section_id' => $class6P->id,
                    'student_id' => $enrol->student_id,
                    'attendance_date' => $date,
                    'status' => $faker->randomElement(['present', 'present', 'present', 'absent', 'late']),
                    'marked_by' => 1
                ]);
            }
        }

        $this->command->info('✅ Bulk Dummy Data Seeded Successfully!');
    }
}