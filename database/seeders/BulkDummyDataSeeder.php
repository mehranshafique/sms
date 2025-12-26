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
use App\Models\Notice;     
use App\Models\Election;   
use App\Models\ElectionPosition; 
use App\Models\Candidate;  
use App\Models\Vote;       
use App\Models\Package;      // NEW
use App\Models\Subscription; // NEW
use App\Enums\UserType;
use App\Enums\RoleEnum;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class BulkDummyDataSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('fr_FR'); // French Locale for DRC context
        
        $this->command->info('🌱 Seeding DR Congo School Data with Full Modules (Packages, Subscriptions, Communication, Voting)...');

        // 0. Create Packages (Platform Level)
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
                'modules' => ['academics', 'students', 'staff', 'finance', 'examinations', 'communication', 'voting', 'library', 'transport'],
                'student_limit' => 2000,
                'staff_limit' => 200,
                'is_active' => true
            ]
        ];

        foreach ($packages as $pkg) {
            Package::firstOrCreate(['name' => $pkg['name']], $pkg);
        }
        
        $premiumPackage = Package::where('name', 'Premium Plan')->first();

        // 1. Institution (Updated with Acronym and Commune)
        // Code format: KI (Kinshasa) + LE (Lemba) + 0001 = KILE0001
        $institution = Institution::firstOrCreate(
            ['code' => 'KILE0001'], 
            [
                'name' => 'Complexe Scolaire Mont Amba',
                'acronym' => 'CSMA',
                'type' => 'mixed',
                'country' => 'DR Congo',
                'city' => 'Kinshasa',
                'commune' => 'Lemba',
                'address' => 'Avenue de l\'Université, Lemba',
                'phone' => '+243815550000',
                'email' => 'info@montamba.cd',
                'is_active' => true,
            ]
        );

        // 1.1 Create Default Roles for this Institution
        // Roles MUST be scoped to the institution_id
        $rolesToCreate = [
            RoleEnum::HEAD_OFFICER->value,
            RoleEnum::TEACHER->value,
            RoleEnum::STUDENT->value,
            'Staff',        // Additional role
            'Branch Admin'  // Additional role
        ];

        foreach ($rolesToCreate as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'institution_id' => $institution->id
            ]);
        }

        // 1.2 Create Subscription for the Institution
        Subscription::firstOrCreate(
            ['institution_id' => $institution->id],
            [
                'package_id' => $premiumPackage->id,
                'start_date' => now(),
                'end_date' => now()->addDays($premiumPackage->duration_days),
                'status' => 'active',
                'price_paid' => $premiumPackage->price,
                'payment_method' => 'bank_transfer',
                'transaction_reference' => 'TXN-' . strtoupper(uniqid()),
                'notes' => 'Seeded Premium Subscription'
            ]
        );

        // 2. Campus
        $campus = Campus::firstOrCreate(
            ['code' => 'CMP-LEMBA', 'institution_id' => $institution->id],
            [
                'name' => 'Campus Principal Lemba',
                'address' => 'Lemba Super, Kinshasa',
                'city' => 'Kinshasa',
                'phone' => '+243825551111',
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

        // 5. Staff (Teachers & Admin)
        $staffData = [
            ['role' => RoleEnum::TEACHER->value, 'dept' => 'Sciences'],
            ['role' => RoleEnum::TEACHER->value, 'dept' => 'Lettres'],
            ['role' => RoleEnum::TEACHER->value, 'dept' => 'Math'],
            ['role' => 'Staff', 'dept' => 'Finance'],
            ['role' => RoleEnum::HEAD_OFFICER->value, 'dept' => 'Administration']
        ];
        
        $teacherIds = [];

        foreach ($staffData as $idx => $data) {
            $roleName = $data['role'];
            $firstName = $faker->firstName;
            $lastName = $faker->lastName;
            $email = strtolower($firstName . '.' . $lastName . '@montamba.cd');
            
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => "$firstName $lastName",
                    'password' => Hash::make('password'),
                    'user_type' => $roleName == RoleEnum::TEACHER->value ? UserType::STAFF->value : UserType::HEAD_OFFICER->value,
                    'phone' => '+2439' . $faker->numerify('########'),
                    'institute_id' => $institution->id,
                    'is_active' => true,
                ]
            );
            
            // Assign Institution-Specific Role
            $role = Role::where('name', $roleName)
                        ->where('institution_id', $institution->id)
                        ->first();
            
            if ($role) {
                $user->assignRole($role);
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

        // 6. Class Sections & Subjects
        $sections = [];
        foreach ($gradeModels as $code => $grade) {
            // Section A
            $sections[$code] = ClassSection::firstOrCreate(
                ['institution_id' => $institution->id, 'grade_level_id' => $grade->id, 'name' => 'Section A'],
                [
                    'campus_id' => $campus->id,
                    'room_number' => 'Salle ' . $grade->order_index,
                    'capacity' => 40,
                    'staff_id' => $faker->randomElement($teacherIds), 
                    'is_active' => true
                ]
            );

            // Subjects
            $subjNames = ['Mathématiques', 'Français', 'Anglais', 'Informatique'];
            foreach($subjNames as $subName) {
                Subject::firstOrCreate(
                    ['institution_id' => $institution->id, 'grade_level_id' => $grade->id, 'name' => $subName],
                    [
                        'code' => strtoupper(substr($subName, 0, 3)) . '-' . $code,
                        'type' => 'theory',
                        'total_marks' => 20,
                        'passing_marks' => 10,
                        'is_active' => true
                    ]
                );
            }
        }

        // 7. Students (20 records)
        $students = [];
        $studentRole = Role::where('name', RoleEnum::STUDENT->value)
                           ->where('institution_id', $institution->id)
                           ->first();

        for ($i = 1; $i <= 20; $i++) {
            $firstName = $faker->firstName;
            $lastName = $faker->lastName;
            
            $sUser = User::firstOrCreate(
                ['email' => "student$i@montamba.cd"],
                [
                    'name' => "$firstName $lastName",
                    'password' => Hash::make('password'),
                    'user_type' => UserType::STUDENT->value,
                    'institute_id' => $institution->id,
                    'is_active' => true,
                ]
            );
            
            if($studentRole) $sUser->assignRole($studentRole);

            $student = Student::firstOrCreate(
                ['user_id' => $sUser->id],
                [
                    'institution_id' => $institution->id,
                    'campus_id' => $campus->id,
                    'admission_number' => 'MAT-' . (2024000 + $i),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'gender' => $faker->randomElement(['male', 'female']),
                    'dob' => $faker->dateTimeBetween('-18 years', '-6 years'),
                    'admission_date' => '2024-09-04',
                    'father_name' => $faker->name('male'),
                    'father_phone' => '+24381' . $faker->numerify('#######'),
                    'status' => 'active'
                ]
            );
            
            $students[] = $student;

            // Enroll Student
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

        // 8. Finance (Fees)
        $tuitionType = FeeType::firstOrCreate(['institution_id' => $institution->id, 'name' => 'Frais Scolaires']);
        $fee = FeeStructure::create([
            'institution_id' => $institution->id,
            'academic_session_id' => $session->id,
            'fee_type_id' => $tuitionType->id,
            'name' => 'Minerval T1',
            'amount' => 150.00,
            'frequency' => 'termly'
        ]);

        // 9. Communication (Notices) - NEW
        $noticesData = [
            [
                'title' => 'Rentrée Scolaire 2024',
                'content' => 'La rentrée scolaire est fixée au 4 Septembre à 7h30. Soyez à l\'heure.',
                'type' => 'info',
                'audience' => 'all',
                'publish_date' => now()->subDays(10),
            ],
            [
                'title' => 'Réunion des Parents',
                'content' => 'Une réunion importante aura lieu ce Samedi pour discuter des frais.',
                'type' => 'event',
                'audience' => 'parent',
                'publish_date' => now()->subDays(2),
            ],
            [
                'title' => 'Urgent: Paiement Frais',
                'content' => 'Veuillez régulariser vos frais avant le début des examens.',
                'type' => 'urgent',
                'audience' => 'student',
                'publish_date' => now(),
            ]
        ];

        foreach ($noticesData as $n) {
            Notice::create([
                'institution_id' => $institution->id,
                'title' => $n['title'],
                'content' => $n['content'],
                'type' => $n['type'],
                'audience' => $n['audience'],
                'publish_date' => $n['publish_date'],
                'is_published' => true,
                'created_by' => 1 // Assuming Super Admin ID 1
            ]);
        }

        // 10. Voting System (Elections) - NEW
        $election = Election::create([
            'institution_id' => $institution->id,
            'academic_session_id' => $session->id,
            'title' => 'Élections du Comité des Élèves ' . $session->name,
            'description' => 'Élection pour choisir le Doyen et les chefs de classe.',
            'start_date' => now()->subDays(1), // Started yesterday
            'end_date' => now()->addDays(2),   // Ends in 2 days
            'status' => 'published', // Active
        ]);

        // Positions
        $posPresident = ElectionPosition::create(['election_id' => $election->id, 'name' => 'Doyen (Président)', 'sequence' => 1]);
        $posVice = ElectionPosition::create(['election_id' => $election->id, 'name' => 'Vice-Doyen', 'sequence' => 2]);

        // Candidates (Pick random students)
        $candidateStudents = $faker->randomElements($students, 4);
        
        // President Candidates
        Candidate::create(['election_id' => $election->id, 'election_position_id' => $posPresident->id, 'student_id' => $candidateStudents[0]->id, 'status' => 'approved']);
        Candidate::create(['election_id' => $election->id, 'election_position_id' => $posPresident->id, 'student_id' => $candidateStudents[1]->id, 'status' => 'approved']);
        
        // Vice Candidates
        Candidate::create(['election_id' => $election->id, 'election_position_id' => $posVice->id, 'student_id' => $candidateStudents[2]->id, 'status' => 'approved']);
        Candidate::create(['election_id' => $election->id, 'election_position_id' => $posVice->id, 'student_id' => $candidateStudents[3]->id, 'status' => 'approved']);

        // Votes (Simulate some voting)
        $voters = $faker->randomElements($students, 10);
        foreach($voters as $voter) {
            // Vote for President
            Vote::create([
                'election_id' => $election->id,
                'election_position_id' => $posPresident->id,
                'candidate_id' => $faker->randomElement([1, 2]), // Assuming IDs 1,2 are candidates
                'voter_id' => $voter->id,
                'voted_at' => now(),
            ]);
        }

        $this->command->info('✅ Bulk Dummy Data (Institutes, Packages, Subscriptions, Staff, Students, Notices, Elections) Seeded Successfully!');
    }
}