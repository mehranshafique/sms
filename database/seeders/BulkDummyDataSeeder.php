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
// New Modules
use App\Models\Assignment; 

use App\Enums\UserType;
use App\Enums\RoleEnum;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;
use App\Services\IdGeneratorService;

class BulkDummyDataSeeder extends Seeder
{
    public function run()
    {
        // Disable mass assignment protection for seeding
        Model::unguard();

        $faker = Faker::create('fr_FR'); // French Locale for DRC context
        
        $this->command->info('🌱 Seeding Data for E-Digitex System (Multi-Tenant)...');

        // ---------------------------------------------------------
        // 0. Locations
        // ---------------------------------------------------------
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('countries')->truncate();
        DB::table('states')->truncate();
        DB::table('cities')->truncate();
        
        $countryId = DB::table('countries')->insertGetId([
            'sortname' => 'CD', 'name' => 'Democratic Republic of the Congo', 'phonecode' => 243, 'created_at' => now(), 'updated_at' => now()
        ]);
        $stateId = DB::table('states')->insertGetId([
            'name' => 'Kinshasa', 'country_id' => $countryId, 'created_at' => now(), 'updated_at' => now()
        ]);
        $cityId = DB::table('cities')->insertGetId([
            'name' => 'Gombe', 'state_id' => $stateId, 'created_at' => now(), 'updated_at' => now()
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $countryName = 'Democratic Republic of the Congo';
        $stateName = 'Kinshasa';
        $cityName = 'Gombe';

        // ---------------------------------------------------------
        // 1. Packages
        // ---------------------------------------------------------
        $packages = [
            [
                'name' => 'Basic Plan',
                'price' => 500.00,
                'duration_days' => 365,
                'modules' => ['academics', 'students', 'staff'],
                'student_limit' => 100,
                'staff_limit' => 10,
                'is_active' => true
            ],
            [
                'name' => 'Standard Plan',
                'price' => 1000.00,
                'duration_days' => 365,
                'modules' => ['academics', 'students', 'staff', 'finance', 'examinations'],
                'student_limit' => 500,
                'staff_limit' => 50,
                'is_active' => true
            ],
            [
                'name' => 'Premium Plan',
                'price' => 2000.00,
                'duration_days' => 365,
                'modules' => [
                    'academics', 'students', 'staff', 'finance', 'examinations', 
                    'communication', 'voting', 'exam_schedules', 'payrolls', 'budgets',
                    'assignments', 'library', 'transport'
                ],
                'student_limit' => 2000,
                'staff_limit' => 200,
                'is_active' => true
            ]
        ];

        foreach ($packages as $pkg) {
            Package::firstOrCreate(['name' => $pkg['name']], $pkg);
        }
        
        $premiumPackage = Package::where('name', 'Premium Plan')->first();

        // ---------------------------------------------------------
        // 2. Institution
        // ---------------------------------------------------------
        $instCode = IdGeneratorService::generateInstitutionCode((string)$stateId, (string)$cityId);

        $institution = Institution::firstOrCreate(
            ['email' => 'admin@e-digitex.com'], 
            [
                'code' => $instCode,
                'name' => 'E-Digitex International School',
                'acronym' => 'E-DIGITEX',
                'type' => 'mixed',
                'country' => $countryId,
                'state' => $stateId,
                'city' => $cityId,
                'address' => '123 Tech Avenue, Gombe',
                'phone' => '+243815550000',
                'is_active' => true,
            ]
        );

        // 2.1 Create Roles
        $rolesToCreate = [
            RoleEnum::HEAD_OFFICER->value,
            RoleEnum::SCHOOL_ADMIN->value,
            RoleEnum::TEACHER->value,
            RoleEnum::STUDENT->value,
            'Accountant',        
            'Librarian',
            'Transport Manager'
        ];

        foreach ($rolesToCreate as $roleName) {
            Role::firstOrCreate(
                ['name' => $roleName, 'institution_id' => $institution->id],
                ['guard_name' => 'web']
            );
        }

        // 2.2 Subscription
        Subscription::firstOrCreate(
            ['institution_id' => $institution->id],
            [
                'package_id' => $premiumPackage->id,
                'start_date' => now(),
                'end_date' => now()->addDays(365),
                'status' => 'active',
                'price_paid' => $premiumPackage->price,
                'payment_method' => 'bank_transfer',
                'transaction_reference' => 'TXN-' . strtoupper(uniqid()),
                'notes' => 'Seeded Premium Subscription'
            ]
        );

        // 2.3 Institution Settings
        InstitutionSetting::updateOrCreate(
            ['institution_id' => $institution->id, 'key' => 'exams_locked'],
            ['value' => 0]
        );
        InstitutionSetting::updateOrCreate(
            ['institution_id' => $institution->id, 'key' => 'active_periods'],
            ['value' => json_encode(['p1', 'p2', 'trimester_1'])]
        );

        // ---------------------------------------------------------
        // 3. Campus
        // ---------------------------------------------------------
        $campus = Campus::firstOrCreate(
            ['code' => 'CMP-MAIN', 'institution_id' => $institution->id],
            [
                'name' => 'Main Campus',
                'address' => 'Innovation Park',
                'city' => 'Kinshasa',
                'phone' => '+243825551111',
                'is_active' => true,
            ]
        );

        // ---------------------------------------------------------
        // 4. Academic Session
        // ---------------------------------------------------------
        $session = AcademicSession::firstOrCreate(
            ['institution_id' => $institution->id, 'name' => '2024-2025'],
            [
                'start_date' => '2024-09-04',
                'end_date' => '2025-07-02',
                'status' => 'active',
                'is_current' => true,
            ]
        );

        // ---------------------------------------------------------
        // 5. Grade Levels
        // ---------------------------------------------------------
        $gradesList = [
            ['name' => '1ère Primaire', 'code' => '1P', 'cycle' => 'primary', 'order' => 1],
            ['name' => '2ème Primaire', 'code' => '2P', 'cycle' => 'primary', 'order' => 2],
            ['name' => '6ème Primaire', 'code' => '6P', 'cycle' => 'primary', 'order' => 6],
            ['name' => '7ème EB', 'code' => '7EB', 'cycle' => 'secondary', 'order' => 7],
            ['name' => '4ème Humanités', 'code' => '4HS', 'cycle' => 'secondary', 'order' => 10],
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

        // ---------------------------------------------------------
        // 6. Staff (Teachers & Admin)
        // ---------------------------------------------------------
        $staffData = [
            ['role' => RoleEnum::TEACHER->value, 'dept' => 'Sciences', 'first_name' => 'John', 'last_name' => 'Doe'],
            ['role' => RoleEnum::TEACHER->value, 'dept' => 'Lettres', 'first_name' => 'Jane', 'last_name' => 'Smith'],
            ['role' => RoleEnum::TEACHER->value, 'dept' => 'Math', 'first_name' => 'Alan', 'last_name' => 'Turing'],
            ['role' => RoleEnum::SCHOOL_ADMIN->value, 'dept' => 'Admin', 'first_name' => 'Admin', 'last_name' => 'User'],
            ['role' => 'Accountant', 'dept' => 'Finance', 'first_name' => 'Alice', 'last_name' => 'Accountant'],
        ];
        
        $teacherIds = [];
        $adminUserId = null; 

        foreach ($staffData as $idx => $data) {
            $roleName = $data['role'];
            $firstName = $data['first_name'];
            $lastName = $data['last_name'];
            $email = strtolower($firstName . '.' . $lastName . '@e-digitex.com');
            
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => "$firstName $lastName",
                    'password' => Hash::make('password'),
                    'user_type' => $roleName == RoleEnum::TEACHER->value ? UserType::STAFF->value : UserType::SCHOOL_ADMIN->value,
                    'phone' => '+2439' . $faker->numerify('########'),
                    'institute_id' => $institution->id,
                    'is_active' => true,
                ]
            );
            
            $role = Role::where('name', $roleName)->where('institution_id', $institution->id)->first();
            if ($role) $user->assignRole($role);

            if ($roleName == RoleEnum::SCHOOL_ADMIN->value) {
                $adminUserId = $user->id;
            }

            $staff = Staff::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'institution_id' => $institution->id,
                    'campus_id' => $campus->id,
                    'employee_id' => 'EMP-' . str_pad($idx + 1, 3, '0', STR_PAD_LEFT),
                    'designation' => $roleName,
                    'department' => $data['dept'],
                    'joining_date' => $faker->date(),
                    'gender' => $faker->randomElement(['male', 'female']),
                    'address' => $faker->address,
                    'status' => 'active',
                ]
            );
            
            if ($roleName == RoleEnum::TEACHER->value) {
                $teacherIds[] = $staff->id;
            }
        }

        if (!$adminUserId) $adminUserId = User::where('institute_id', $institution->id)->first()->id ?? 1;

        // ---------------------------------------------------------
        // 7. Class Sections & Subjects
        // ---------------------------------------------------------
        $sections = [];
        $subjectsCollection = [];

        foreach ($gradeModels as $code => $grade) {
            $sections[$code] = ClassSection::firstOrCreate(
                ['institution_id' => $institution->id, 'grade_level_id' => $grade->id, 'name' => 'Section A'],
                [
                    'campus_id' => $campus->id,
                    'room_number' => 'Salle ' . $grade->order_index,
                    'capacity' => 40,
                    'staff_id' => !empty($teacherIds) ? $faker->randomElement($teacherIds) : null, 
                    'is_active' => true
                ]
            );

            $subjNames = ['Mathématiques', 'Français', 'Anglais', 'Informatique'];
            foreach($subjNames as $subName) {
                $sub = Subject::firstOrCreate(
                    ['institution_id' => $institution->id, 'grade_level_id' => $grade->id, 'name' => $subName],
                    [
                        'code' => strtoupper(substr($subName, 0, 3)) . '-' . $code,
                        'type' => 'theory',
                        'total_marks' => 20,
                        'passing_marks' => 10,
                        'is_active' => true
                    ]
                );
                $subjectsCollection[$grade->id][] = $sub;
            }
        }

        // ---------------------------------------------------------
        // 8. Timetables
        // ---------------------------------------------------------
        $this->command->info('📅 Seeding Timetables...');
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $times = ['08:00', '09:00', '10:00', '11:00'];

        foreach ($sections as $section) {
            if (isset($subjectsCollection[$section->grade_level_id])) {
                foreach ($days as $dayIndex => $day) {
                    for($i=0; $i<2; $i++) {
                        $subject = $faker->randomElement($subjectsCollection[$section->grade_level_id]);
                        $teacherId = !empty($teacherIds) ? $faker->randomElement($teacherIds) : null;
                        
                        if($teacherId) {
                            Timetable::firstOrCreate(
                                [
                                    'institution_id' => $institution->id,
                                    'academic_session_id' => $session->id,
                                    'class_section_id' => $section->id,
                                    'day_of_week' => $day,
                                    'start_time' => $times[$i],
                                ],
                                [
                                    'end_time' => Carbon::parse($times[$i])->addHour()->format('H:i'),
                                    'subject_id' => $subject->id,
                                    'teacher_id' => $teacherId,
                                    'room_number' => $section->room_number
                                ]
                            );
                        }
                    }
                }
            }
        }

        // ---------------------------------------------------------
        // 9. Parents & Students
        // ---------------------------------------------------------
        $this->command->info('👥 Seeding Parents and Students...');
        $students = [];
        $studentRole = Role::where('name', RoleEnum::STUDENT->value)->where('institution_id', $institution->id)->first();

        for ($p = 1; $p <= 10; $p++) {
            $parent = StudentParent::updateOrCreate(
                ['institution_id' => $institution->id, 'father_phone' => '+24381' . str_pad($p, 7, '0', STR_PAD_LEFT)],
                [
                    'father_name' => $faker->name('male'),
                    'mother_name' => $faker->name('female'),
                    'mother_phone' => '+24382' . str_pad($p, 7, '0', STR_PAD_LEFT),
                    'family_address' => $faker->address,
                ]
            );

            $childCount = rand(1, 2);
            for ($c = 1; $c <= $childCount; $c++) {
                $firstName = $faker->firstName;
                $lastName = $faker->lastName;
                
                $sUser = User::firstOrCreate(
                    ['email' => strtolower($firstName . "." . $lastName . $p . $c . "@e-digitex.com")],
                    [
                        'name' => "$firstName $lastName",
                        'password' => Hash::make('password'),
                        'user_type' => UserType::STUDENT->value,
                        'institute_id' => $institution->id,
                        'is_active' => true,
                    ]
                );
                
                if($studentRole) $sUser->assignRole($studentRole);

                $admissionNumber = IdGeneratorService::generateStudentId($institution, $session, $sUser->id);

                $student = Student::firstOrCreate(
                    ['user_id' => $sUser->id],
                    [
                        'parent_id' => $parent->id,
                        'institution_id' => $institution->id,
                        'campus_id' => $campus->id,
                        'admission_number' => $admissionNumber,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'gender' => $faker->randomElement(['male', 'female']),
                        'dob' => $faker->dateTimeBetween('-15 years', '-6 years'),
                        'admission_date' => '2024-09-04',
                        'country' => $countryName,
                        'state' => $stateName,
                        'city' => $cityName,
                        'status' => 'active',
                        'payment_mode' => 'installment'
                    ]
                );

                $students[] = $student;

                $randomGradeCode = $faker->randomElement(array_keys($gradeModels));
                $section = $sections[$randomGradeCode];
                
                StudentEnrollment::firstOrCreate(
                    ['academic_session_id' => $session->id, 'student_id' => $student->id],
                    [
                        'institution_id' => $institution->id,
                        'grade_level_id' => $section->grade_level_id,
                        'class_section_id' => $section->id,
                        'status' => 'active',
                        'enrolled_at' => now(),
                    ]
                );
            }
        }

        // ---------------------------------------------------------
        // 10. Student Attendance
        // ---------------------------------------------------------
        $this->command->info('✅ Seeding Student Attendance...');
        foreach ($students as $student) {
            $enrollment = StudentEnrollment::where('student_id', $student->id)->first();
            if ($enrollment) {
                for ($d = 0; $d < 5; $d++) {
                    $date = now()->subDays($d)->format('Y-m-d');
                    if (now()->subDays($d)->isWeekend()) continue;

                    StudentAttendance::firstOrCreate(
                        ['student_id' => $student->id, 'attendance_date' => $date],
                        [
                            'institution_id' => $institution->id,
                            'academic_session_id' => $session->id,
                            'class_section_id' => $enrollment->class_section_id,
                            'status' => $faker->randomElement(['present', 'present', 'present', 'absent', 'late']),
                            'remarks' => null
                        ]
                    );
                }
            }
        }

        // ---------------------------------------------------------
        // 11. Finance (Fees, Payroll, Budget)
        // ---------------------------------------------------------
        $this->command->info('💰 Seeding Finance Modules...');
        
        $tuitionType = FeeType::firstOrCreate(['institution_id' => $institution->id, 'name' => 'Frais Scolaires']);
        $primaryGrade = $gradeModels['1P'];
        
        FeeStructure::firstOrCreate(
            ['institution_id' => $institution->id, 'name' => 'Minerval Annuel 1P', 'grade_level_id' => $primaryGrade->id],
            [
                'academic_session_id' => $session->id,
                'fee_type_id' => $tuitionType->id,
                'amount' => 500.00,
                'frequency' => 'yearly',
                'payment_mode' => 'global'
            ]
        );

        FeeStructure::firstOrCreate(
            ['institution_id' => $institution->id, 'name' => 'Tranche 1', 'grade_level_id' => $primaryGrade->id],
            [
                'academic_session_id' => $session->id,
                'fee_type_id' => $tuitionType->id,
                'amount' => 150.00,
                'frequency' => 'termly',
                'payment_mode' => 'installment',
                'installment_order' => 1
            ]
        );

        // Budget Category
        $catOps = BudgetCategory::firstOrCreate(
            ['institution_id' => $institution->id, 'name' => 'Operations'],
            ['description' => 'Daily operational costs']
        );

        $budget = Budget::firstOrCreate(
            ['institution_id' => $institution->id, 'budget_category_id' => $catOps->id, 'academic_session_id' => $session->id],
            ['allocated_amount' => 50000.00, 'spent_amount' => 0, 'notes' => 'Annual Operations Budget']
        );

        if ($adminUserId) {
            FundRequest::firstOrCreate(
                ['institution_id' => $institution->id, 'title' => 'Purchase of Whiteboards'],
                [
                    'budget_id' => $budget->id, 
                    'requested_by' => $adminUserId,
                    'amount' => 500.00,
                    'status' => 'pending',
                    'description' => 'Need 5 new whiteboards for Primary block.'
                ]
            );
        }

        if (!empty($teacherIds)) {
            $staffMember = Staff::find($teacherIds[0]);
            if ($staffMember) {
                // Remove keys that don't match database columns
                SalaryStructure::firstOrCreate(
                    ['staff_id' => $staffMember->id],
                    [
                        'institution_id' => $institution->id,
                        'base_salary' => 800.00,
                        'allowances' => json_encode([['name' => 'Transport', 'amount' => 50]]),
                        'deductions' => json_encode([['name' => 'Tax', 'amount' => 40]]),
                    ]
                );

                // Corrected keys to match Payroll Model
                Payroll::firstOrCreate(
                    [
                        'staff_id' => $staffMember->id,
                        'month_year' => now()->startOfMonth() 
                    ],
                    [
                        'institution_id' => $institution->id,
                        'basic_pay' => 800.00, // Corrected from basic_salary
                        'total_allowance' => 50.00,
                        'total_deduction' => 40.00,
                        'net_salary' => 810.00,
                        'status' => 'paid',
                        'paid_at' => now() // Use paid_at instead of payment_method
                    ]
                );
            }
        }

        // ---------------------------------------------------------
        // 12. Exams & Schedules
        // ---------------------------------------------------------
        $this->command->info('📝 Seeding Exams and Schedules...');
        
        $exam = Exam::firstOrCreate(
            ['institution_id' => $institution->id, 'name' => 'P1 Assessment', 'academic_session_id' => $session->id],
            ['category' => 'p1', 'start_date' => now(), 'end_date' => now()->addDays(7), 'status' => 'ongoing']
        );

        $subjects = Subject::where('institution_id', $institution->id)->get();
        if ($subjects->count() > 0) {
            $sub = $subjects->first();
            $sec = ClassSection::where('grade_level_id', $sub->grade_level_id)->first();
            
            if ($sec) {
                ExamSchedule::firstOrCreate(
                    ['exam_id' => $exam->id, 'subject_id' => $sub->id, 'class_section_id' => $sec->id],
                    [
                        'institution_id' => $institution->id,
                        'exam_date' => now()->addDay()->format('Y-m-d'),
                        'start_time' => '09:00',
                        'end_time' => '11:00',
                        'room_number' => 'Hall A',
                        'max_marks' => $sub->total_marks,
                        'pass_marks' => $sub->passing_marks
                    ]
                );
            }
        }

        if(count($students) > 0) {
            $student = $students[0];
            $enrollment = StudentEnrollment::where('student_id', $student->id)->first();
            
            if($enrollment) {
                $gradeSubjects = Subject::where('grade_level_id', $enrollment->grade_level_id)->get();
                
                foreach($gradeSubjects as $sub) {
                    ExamRecord::firstOrCreate(
                        ['exam_id' => $exam->id, 'student_id' => $student->id, 'subject_id' => $sub->id],
                        [
                            'class_section_id' => $enrollment->class_section_id,
                            'marks_obtained' => rand(10, 20),
                            'is_absent' => false
                        ]
                    );
                }
            }
        }

        // ---------------------------------------------------------
        // 13. Assignments
        // ---------------------------------------------------------
        $this->command->info('📚 Seeding Assignments...');
        if (!empty($teacherIds) && isset($sections['1P'])) {
            $teacherId = $teacherIds[0]; // Get first teacher
            $staffMember = Staff::find($teacherId);
            $section = $sections['1P'];
            $subject = Subject::where('grade_level_id', $section->grade_level_id)->first();

            if ($staffMember && $section && $subject) {
                Assignment::firstOrCreate(
                    ['title' => 'Math Homework 1', 'institution_id' => $institution->id],
                    [
                        'academic_session_id' => $session->id,
                        'class_section_id' => $section->id,
                        'subject_id' => $subject->id,
                        'teacher_id' => $staffMember->id,
                        'description' => 'Complete exercises 1-10 on page 20.',
                        'deadline' => now()->addDays(3),
                        'file_path' => null // Optional attachment
                    ]
                );
            }
        }

        // ---------------------------------------------------------
        // 14. Communication & Voting
        // ---------------------------------------------------------
        Notice::firstOrCreate(
            ['title' => 'Rentrée Scolaire 2024', 'institution_id' => $institution->id],
            [
                'content' => 'La rentrée scolaire est fixée au 4 Septembre à 7h30. Soyez à l\'heure.',
                'type' => 'info',
                'audience' => 'all',
                'published_at' => now()->subDays(10),
                'is_published' => true,
                'created_by' => $adminUserId 
            ]
        );

        $election = Election::firstOrCreate(
            ['title' => 'Élections du Comité', 'institution_id' => $institution->id],
            [
                'academic_session_id' => $session->id,
                'description' => 'Élection pour choisir le Doyen.',
                'start_date' => now()->subDays(1),
                'end_date' => now()->addDays(2),
                'status' => 'published',
            ]
        );

        $posPresident = ElectionPosition::firstOrCreate(['election_id' => $election->id, 'name' => 'Doyen'], ['sequence' => 1]);

        if (count($students) >= 2) {
            $cand1 = Candidate::firstOrCreate(['election_id' => $election->id, 'student_id' => $students[0]->id], ['election_position_id' => $posPresident->id, 'status' => 'approved']);
            
            Vote::insertOrIgnore([
                'election_id' => $election->id,
                'election_position_id' => $posPresident->id,
                'candidate_id' => $cand1->id, 
                'voter_id' => $students[1]->id,
                'voted_at' => now(),
            ]);
        }

        Model::reguard();

        $this->command->info('✅ Bulk Dummy Data Seeded Successfully with ALL Modules!');
    }
}