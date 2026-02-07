<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Faker\Factory as Faker;
use App\Models\User;
use App\Models\Institution;
use App\Models\Campus;
use App\Models\AcademicSession;
use App\Models\GradeLevel;
use App\Models\ClassSection;
use App\Models\Subject;
use App\Models\ClassSubject;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentParent;
use App\Models\StudentEnrollment;
use App\Models\FeeType;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Timetable;
use App\Models\Exam;
use App\Models\ExamRecord;
use App\Models\ExamSchedule;
use App\Models\StudentAttendance;
use App\Models\Notice;
use App\Models\Election;
use App\Models\ElectionPosition;
use App\Models\Candidate;
use App\Models\Vote;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\InstitutionSetting;
use App\Models\BudgetCategory;
use App\Models\Budget;
use App\Models\FundRequest;
use App\Models\SalaryStructure;
use App\Models\Payroll;
use App\Models\Assignment;
use App\Models\Department;
use App\Models\Program;
use App\Models\AcademicUnit;
use App\Enums\UserType;
use App\Enums\RoleEnum;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;
use App\Services\IdGeneratorService;

class BulkDummyDataSeeder extends Seeder
{
    public function run()
    {
        // Disable mass assignment protection
        Model::unguard();
        $faker = Faker::create('en_US'); 

        $this->command->info('🌱 Initializing Comprehensive Seeding Process for E-Digitex...');

        // ---------------------------------------------------------
        // 0. Global Setup & Cleanup
        // ---------------------------------------------------------
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        $tables = [
            'countries', 'states', 'cities', 'institutions', 'users', 'staff', 'students', 
            'parents', 'academic_sessions', 'grade_levels', 'class_sections', 'subjects', 'class_subjects',
            'programs', 'academic_units', 'departments', 'student_enrollments', 'invoices', 'invoice_items',
            'payments', 'exam_records', 'timetables', 'student_attendances', 'fee_structures', 'fee_types',
            'budgets', 'budget_categories', 'fund_requests', 'salary_structures', 'payrolls', 'assignments',
            'exams', 'exam_schedules', 'notices', 'elections', 'election_positions', 'candidates', 'votes'
        ];

        foreach($tables as $table) {
             if(\Illuminate\Support\Facades\Schema::hasTable($table)) {
                 DB::table($table)->truncate();
             }
        }
        
        // Locations
        $countryId = DB::table('countries')->insertGetId(['sortname' => 'CD', 'name' => 'Congo (DRC)', 'phonecode' => 243, 'created_at' => now(), 'updated_at' => now()]);
        $stateId = DB::table('states')->insertGetId(['name' => 'Kinshasa', 'country_id' => $countryId, 'created_at' => now(), 'updated_at' => now()]);
        $cityId = DB::table('cities')->insertGetId(['name' => 'Gombe', 'state_id' => $stateId, 'created_at' => now(), 'updated_at' => now()]);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Packages
        $packages = [
            ['name' => 'Basic Plan', 'price' => 500.00, 'modules' => ['academics', 'students'], 'student_limit' => 100],
            ['name' => 'Standard Plan', 'price' => 1000.00, 'modules' => ['academics', 'students', 'finance'], 'student_limit' => 500],
            ['name' => 'Premium Plan', 'price' => 2000.00, 'modules' => ['academics', 'students', 'finance', 'examinations', 'communication', 'voting', 'budgets', 'payrolls', 'university_enrollments', 'departments', 'class_subjects'], 'student_limit' => 5000]
        ];
        foreach ($packages as $pkg) Package::firstOrCreate(['name' => $pkg['name']], $pkg + ['duration_days' => 365, 'staff_limit' => 200, 'is_active' => true]);
        $premiumPkg = Package::where('name', 'Premium Plan')->first();

        // ---------------------------------------------------------
        // DEFINING 4 DISTINCT INSTITUTION TYPES
        // ---------------------------------------------------------
        $institutionsData = [
            [
                'name' => 'Sunshine Primary School',
                'type' => 'primary',
                'acronym' => 'SPS',
                'grades' => ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6']
            ],
            [
                'name' => 'City High School',
                'type' => 'secondary',
                'acronym' => 'CHS',
                'grades' => ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12']
            ],
            [
                'name' => 'Tech University of Congo',
                'type' => 'university', // Triggers LMD Logic
                'acronym' => 'TUC',
                'grades' => [] // Will be generated via Programs
            ],
            [
                'name' => 'Skill Vocational Center',
                'type' => 'vocational',
                'acronym' => 'SVC',
                'grades' => ['Level 1 Certified', 'Level 2 Certified', 'Diploma Year']
            ]
        ];

        foreach ($institutionsData as $instData) {
            $this->seedInstitution($instData, $faker, $countryId, $stateId, $cityId, $premiumPkg);
        }

        Model::reguard();
        $this->command->info('✅ Bulk Seeding Completed Successfully! 4 Institutions created with comprehensive data.');
    }

    /**
     * Core Seeding Logic per Institution
     */
    private function seedInstitution($data, $faker, $countryId, $stateId, $cityId, $package)
    {
        $this->command->info(">> Seeding: {$data['name']} ({$data['type']})...");

        // 1. Create Institution
        $instCode = IdGeneratorService::generateInstitutionCode((string)$stateId, (string)$cityId);
        $institution = Institution::create([
            'name' => $data['name'],
            'acronym' => $data['acronym'],
            'code' => $instCode,
            'type' => $data['type'],
            'country' => $countryId,
            'state' => $stateId,
            'city' => $cityId,
            'address' => $faker->address,
            'phone' => '+243' . $faker->numerify('#########'),
            'email' => strtolower($data['acronym']) . '@digitex.com',
            'is_active' => true,
        ]);

        // 2. Settings & Subscriptions
        Subscription::create([
            'institution_id' => $institution->id,
            'package_id' => $package->id,
            'start_date' => now(),
            'end_date' => now()->addDays(365),
            'status' => 'active',
            'price_paid' => $package->price
        ]);

        InstitutionSetting::updateOrCreate(
            ['institution_id' => $institution->id, 'key' => 'exams_locked'],
            ['value' => 0]
        );
        InstitutionSetting::updateOrCreate(
            ['institution_id' => $institution->id, 'key' => 'active_periods'],
            ['value' => json_encode(['p1', 'p2', 'trimester_1'])]
        );
        
        // 3. Roles
        $roles = [RoleEnum::HEAD_OFFICER->value, RoleEnum::SCHOOL_ADMIN->value, RoleEnum::TEACHER->value, RoleEnum::STUDENT->value, RoleEnum::GUARDIAN->value, 'Accountant', 'Librarian'];
        foreach ($roles as $r) Role::firstOrCreate(['name' => $r, 'institution_id' => $institution->id, 'guard_name' => 'web']);

        // 4. Admin User
        $adminUser = User::create([
            'name' => 'Admin ' . $data['acronym'],
            'email' => 'admin.' . strtolower($data['acronym']) . '@digitex.com',
            'password' => Hash::make('password'),
            'user_type' => UserType::SCHOOL_ADMIN->value,
            'institute_id' => $institution->id,
            'is_active' => true,
            'username' => 'admin_'.strtolower($data['acronym']),
            'shortcode' => 'ADM-'.$instCode
        ]);
        $adminUser->assignRole(RoleEnum::SCHOOL_ADMIN->value);

        // 5. Campus & Session
        $campus = Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Main Campus',
            'code' => 'MAIN',
            'is_active' => true
        ]);
        $session = AcademicSession::create([
            'institution_id' => $institution->id,
            'name' => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-07-01',
            'status' => 'active',
            'is_current' => true
        ]);

        // 6. Staff (Min 10)
        $teachers = [];
        $deptHeads = []; 
        for ($i = 0; $i < 12; $i++) {
            $tUser = User::create([
                'name' => $faker->name,
                'email' => $faker->unique()->userName . '@' . strtolower($data['acronym']) . '.com',
                'password' => Hash::make('password'),
                'user_type' => UserType::STAFF->value,
                'institute_id' => $institution->id,
                'is_active' => true,
                'username' => 'stf_'.$i.'_'.strtolower($data['acronym'])
            ]);
            $tUser->assignRole(RoleEnum::TEACHER->value);
            
            $staff = Staff::create([
                'user_id' => $tUser->id,
                'institution_id' => $institution->id,
                'campus_id' => $campus->id,
                'employee_id' => 'EMP' . $institution->id . str_pad($i, 3, '0', STR_PAD_LEFT),
                'designation' => 'Teacher',
                'joining_date' => now()->subMonths(rand(1, 24)),
                'status' => 'active'
            ]);
            $teachers[] = $staff->id;
            
            if($i < 4) $deptHeads[] = $staff->id;
        }

        // 7. Academics (Grades, Classes, Subjects)
        $gradeIds = [];
        
        if ($data['type'] === 'university') {
            // --- UNIVERSITY LOGIC (LMD) ---
            
            // Create Departments
            $deptNames = ['Computer Science', 'Business Admin', 'Economics', 'Law'];
            foreach($deptNames as $idx => $dName) {
                $dept = Department::create([
                    'institution_id' => $institution->id,
                    'name' => $dName,
                    'code' => strtoupper(substr($dName, 0, 3)),
                    'head_of_department_id' => $deptHeads[$idx] ?? null
                ]);

                // Create Program
                $prog = Program::create([
                    'institution_id' => $institution->id,
                    'department_id' => $dept->id,
                    'name' => "Bachelor in $dName",
                    'code' => "B" . strtoupper(substr($dName, 0, 3)),
                    'total_semesters' => 6,
                    'duration_years' => 3
                ]);

                // Create Grades (L1, L2, L3)
                for ($y = 1; $y <= 3; $y++) {
                    $grade = GradeLevel::create([
                        'institution_id' => $institution->id,
                        'program_id' => $prog->id,
                        'name' => "Licence $y (" . $prog->code . ")",
                        'code' => $prog->code . "-L$y",
                        'education_cycle' => 'university',
                        'order_index' => $y
                    ]);
                    $gradeIds[] = $grade->id;

                    // Create Academic Units (UE) for Sem 1 & 2
                    foreach([1, 2] as $sem) {
                        $ue = AcademicUnit::create([
                            'institution_id' => $institution->id,
                            'grade_level_id' => $grade->id,
                            'program_id' => $prog->id,
                            'name' => "UE Fundamental Sem $sem",
                            'code' => "UE-F$sem",
                            'type' => 'fundamental',
                            'semester' => $sem,
                            'total_credits' => 15
                        ]);

                        // Create Subjects linked to UE
                        $subjectsList = ['Intro to ' . $dName, 'Advanced ' . $dName, 'Research Methods'];
                        foreach ($subjectsList as $sIdx => $subName) {
                            
                            // FIX: Ensure Unique Name per Grade Level by appending Semester
                            $uniqueSubjectName = "$subName " . ($sIdx+1);
                            if($sem == 1) $uniqueSubjectName .= " I";
                            else $uniqueSubjectName .= " II";

                            Subject::firstOrCreate(
                                [
                                    'institution_id' => $institution->id,
                                    'grade_level_id' => $grade->id,
                                    'name' => $uniqueSubjectName
                                ],
                                [
                                    'department_id' => $dept->id,
                                    'academic_unit_id' => $ue->id,
                                    'code' => strtoupper(substr($subName, 0, 3)) . $sem . $sIdx,
                                    'type' => 'theory',
                                    'credit_hours' => 5.0, // Specific for Uni
                                    'coefficient' => 2,
                                    'semester' => $sem,
                                    'total_marks' => 20,
                                    'passing_marks' => 10,
                                    'is_active' => true
                                ]
                            );
                        }
                    }
                }
            }

        } else {
            // --- SCHOOL LOGIC ---
            foreach ($data['grades'] as $idx => $gName) {
                $grade = GradeLevel::create([
                    'institution_id' => $institution->id,
                    'name' => $gName,
                    'code' => strtoupper(substr($gName, 0, 3)) . ($idx+1),
                    'education_cycle' => $data['type'],
                    'order_index' => $idx + 1
                ]);
                $gradeIds[] = $grade->id;

                // Subjects per grade
                $subjects = ['Mathematics', 'English', 'Science', 'History', 'Geography'];
                foreach ($subjects as $subName) {
                    Subject::firstOrCreate(
                        [
                            'institution_id' => $institution->id,
                            'grade_level_id' => $grade->id,
                            'name' => $subName
                        ],
                        [
                            'code' => strtoupper(substr($subName, 0, 3)) . '-' . $grade->code,
                            'type' => 'theory',
                            'credit_hours' => 0, // FIX: Default for School to avoid error 1364
                            'total_marks' => 100,
                            'passing_marks' => 40,
                            'is_active' => true
                        ]
                    );
                }
            }
        }

        // 8. Class Sections & Allocations
        $classSections = [];
        foreach ($gradeIds as $gId) {
            // Create Section A and B for variety
            foreach(['A', 'B'] as $secSuffix) {
                $sec = ClassSection::create([
                    'institution_id' => $institution->id,
                    'campus_id' => $campus->id,
                    'grade_level_id' => $gId,
                    'name' => 'Section ' . $secSuffix,
                    'capacity' => 40,
                    'staff_id' => $teachers[array_rand($teachers)], // Class Teacher
                    'is_active' => true
                ]);
                $classSections[] = $sec->id;
                
                // Assign Subject Teachers (ClassSubject)
                $subjects = Subject::where('grade_level_id', $gId)->get();
                foreach($subjects as $sub) {
                    ClassSubject::firstOrCreate(
                        [
                            'institution_id' => $institution->id,
                            'academic_session_id' => $session->id,
                            'class_section_id' => $sec->id,
                            'subject_id' => $sub->id,
                        ],
                        [
                            'teacher_id' => $teachers[array_rand($teachers)],
                            'weekly_periods' => 4,
                            'exam_weight' => 100
                        ]
                    );
                }
            }
        }

        // 9. Students & Enrollments (Min 15)
        $students = [];
        $studentRole = Role::where('name', RoleEnum::STUDENT->value)->where('institution_id', $institution->id)->first();
        
        for ($s = 0; $s < 20; $s++) {
            $sUser = User::create([
                'name' => $faker->firstName . ' ' . $faker->lastName,
                'email' => $faker->unique()->userName . '@student.' . strtolower($data['acronym']) . '.com',
                'password' => Hash::make('password'),
                'user_type' => UserType::STUDENT->value,
                'institute_id' => $institution->id,
                'is_active' => true,
                'username' => 'std_'.$s.'_'.strtolower($data['acronym'])
            ]);
            if($studentRole) $sUser->assignRole($studentRole);

            $parent = StudentParent::firstOrCreate(
                ['institution_id' => $institution->id, 'father_phone' => '+24399' . $faker->numerify('#######')],
                ['father_name' => $faker->name('male'), 'guardian_email' => $faker->safeEmail]
            );

            $admissionNo = IdGeneratorService::generateStudentId($institution, $session);
            $sUser->update(['shortcode' => $admissionNo]);

            $student = Student::create([
                'institution_id' => $institution->id,
                'user_id' => $sUser->id,
                'parent_id' => $parent->id,
                'campus_id' => $campus->id,
                'admission_number' => $admissionNo,
                'first_name' => explode(' ', $sUser->name)[0],
                'last_name' => explode(' ', $sUser->name)[1] ?? '',
                'dob' => $faker->date('Y-m-d', '-10 years'),
                'gender' => $faker->randomElement(['male', 'female']),
                'admission_date' => now(),
                'status' => 'active',
                'payment_mode' => $faker->randomElement(['global', 'installment'])
            ]);
            $students[] = $student->id;

            // Enroll
            $randClassId = $classSections[array_rand($classSections)];
            $randClass = ClassSection::find($randClassId);
            
            StudentEnrollment::create([
                'institution_id' => $institution->id,
                'academic_session_id' => $session->id,
                'student_id' => $student->id,
                'class_section_id' => $randClass->id,
                'grade_level_id' => $randClass->grade_level_id,
                'status' => 'active',
                'enrolled_at' => now()
            ]);
        }

        // 10. Finance (Fees)
        $ft = FeeType::create(['institution_id' => $institution->id, 'name' => 'Tuition Fee']);
        $otherFt = FeeType::create(['institution_id' => $institution->id, 'name' => 'Sports Fee']);
        
        $targetGradeId = $gradeIds[0];
        
        // Fee 1: Global
        $globalFee = FeeStructure::create([
            'institution_id' => $institution->id,
            'academic_session_id' => $session->id,
            'name' => 'Annual Tuition',
            'fee_type_id' => $ft->id,
            'grade_level_id' => $targetGradeId,
            'amount' => 1200.00,
            'frequency' => 'yearly',
            'payment_mode' => 'global'
        ]);

        // Fee 2: Installment
        FeeStructure::create([
            'institution_id' => $institution->id,
            'academic_session_id' => $session->id,
            'name' => 'Term 1',
            'fee_type_id' => $ft->id,
            'grade_level_id' => $targetGradeId,
            'amount' => 400.00,
            'frequency' => 'termly',
            'payment_mode' => 'installment',
            'installment_order' => 1
        ]);
        
        // Fee 3: One Time
        FeeStructure::create([
            'institution_id' => $institution->id,
            'academic_session_id' => $session->id,
            'name' => 'Sports Uniform',
            'fee_type_id' => $otherFt->id,
            'grade_level_id' => $targetGradeId,
            'amount' => 50.00,
            'frequency' => 'one_time',
            'payment_mode' => 'global'
        ]);

        // Invoice generation for enrolled students in target grade
        $enrolledInTarget = StudentEnrollment::where('grade_level_id', $targetGradeId)->get();
        foreach($enrolledInTarget as $enr) {
            $inv = Invoice::create([
                'institution_id' => $institution->id,
                'academic_session_id' => $session->id,
                'student_id' => $enr->student_id,
                'invoice_number' => 'INV-' . rand(1000,9999),
                'total_amount' => 400.00,
                'paid_amount' => 0,
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
                'status' => 'unpaid'
            ]);
            
            InvoiceItem::create([
                'invoice_id' => $inv->id,
                'fee_structure_id' => $globalFee->id, 
                'amount' => 400.00,
                'description' => 'Term 1 Fee'
            ]);
            
            // Random Partial Payment
            if(rand(0,1)) {
                Payment::create([
                    'institution_id' => $institution->id,
                    'invoice_id' => $inv->id,
                    'amount' => 100.00,
                    'payment_date' => now(),
                    'method' => 'cash',
                    'transaction_id' => 'TXN-'.rand(100,999),
                    'received_by' => $adminUser->id
                ]);
                $inv->update(['paid_amount' => 100.00, 'status' => 'partial']);
            }
        }
        
        // 11. Exams & Marks
        $exam = Exam::create([
            'institution_id' => $institution->id,
            'academic_session_id' => $session->id,
            'name' => 'Mid Term Exam',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10),
            'status' => 'scheduled'
        ]);

        $classId = $classSections[0];
        $subjects = Subject::where('grade_level_id', ClassSection::find($classId)->grade_level_id)->get();
        
        foreach($subjects as $sub) {
            ExamSchedule::create([
                'institution_id' => $institution->id,
                'exam_id' => $exam->id,
                'class_section_id' => $classId,
                'subject_id' => $sub->id,
                'exam_date' => now()->addDays(rand(5,10)),
                'start_time' => '09:00',
                'end_time' => '11:00',
                'max_marks' => $sub->total_marks
            ]);

            // Add Marks for enrolled students
            $studentsInClass = StudentEnrollment::where('class_section_id', $classId)->get();
            foreach($studentsInClass as $stdEnr) {
                ExamRecord::create([
                    'exam_id' => $exam->id,
                    'class_section_id' => $classId,
                    'subject_id' => $sub->id,
                    'student_id' => $stdEnr->student_id,
                    'marks_obtained' => rand($sub->total_marks/2, $sub->total_marks),
                    'is_absent' => false
                ]);
            }
        }

        // 12. Budgets (Min 10 categories + records)
        $bc = BudgetCategory::create(['institution_id' => $institution->id, 'name' => 'IT Equipment']);
        Budget::create([
            'institution_id' => $institution->id,
            'academic_session_id' => $session->id,
            'budget_category_id' => $bc->id,
            'allocated_amount' => 10000,
            'spent_amount' => 2000,
            'period_name' => 'Q1',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth()
        ]);

        // 13. Payrolls
        if (!empty($teachers)) {
            $staffMember = Staff::find($teachers[0]);
            SalaryStructure::create([
                'institution_id' => $institution->id,
                'staff_id' => $staffMember->id,
                'base_salary' => 800,
                'payment_basis' => 'monthly'
            ]);
            Payroll::create([
                'institution_id' => $institution->id,
                'staff_id' => $staffMember->id,
                'month_year' => now()->subMonth(),
                'basic_pay' => 800,
                'net_salary' => 800,
                'status' => 'paid'
            ]);
        }

        // 14. Communication (Notices)
        for($k=0; $k<5; $k++) {
            Notice::create([
                'institution_id' => $institution->id,
                'title' => 'Notice ' . ($k+1),
                'content' => 'This is a test notice content.',
                'type' => 'info',
                'audience' => 'all',
                'published_at' => now(),
                'is_published' => true,
                'created_by' => $adminUser->id
            ]);
        }

        // 15. Timetables
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $times = ['08:00', '09:00', '10:00', '11:00'];
        foreach($classSections as $secId) {
            $sec = ClassSection::find($secId);
            $gradeSubjects = Subject::where('grade_level_id', $sec->grade_level_id)->get();
            if($gradeSubjects->isEmpty()) continue;

            foreach($days as $day) {
                 $sub = $gradeSubjects->random();
                 $teacher = $sec->staff_id ?? $teachers[0];
                 Timetable::firstOrCreate(
                    ['institution_id' => $institution->id, 'class_section_id' => $secId, 'day_of_week' => $day, 'start_time' => '08:00'],
                    [
                        'academic_session_id' => $session->id,
                        'end_time' => '09:00',
                        'subject_id' => $sub->id,
                        'teacher_id' => $teacher,
                        'room_number' => $sec->room_number
                    ]
                 );
            }
        }
        
        // 16. Assignments
        if (!empty($teachers) && isset($classSections[0])) {
            $classId = $classSections[0];
            $subject = Subject::where('grade_level_id', ClassSection::find($classId)->grade_level_id)->first();
            $teacher = $teachers[0];

            if ($subject && $teacher) {
                Assignment::create([
                    'institution_id' => $institution->id,
                    'academic_session_id' => $session->id,
                    'class_section_id' => $classId,
                    'subject_id' => $subject->id,
                    'teacher_id' => $teacher,
                    'title' => 'Homework 1: Introduction',
                    'description' => 'Complete exercises on page 10.',
                    'deadline' => now()->addDays(7),
                ]);
            }
        }
    }
}