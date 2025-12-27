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
use App\Models\Package;      
use App\Models\Subscription; 
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
        
        $this->command->info('🌱 Seeding Data with Updated Student & Institution Logic (Preserving Modules)...');

        // ---------------------------------------------------------
        // 0. Locations (NEW LOGIC: Required for Institute)
        // ---------------------------------------------------------
        // We need IDs for Country, State, and City for the Institution
        
        // Clean up locations to avoid duplicates if re-running (Optional, helps avoid FK errors)
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
            'name' => 'Lemba', 'state_id' => $stateId, 'created_at' => now(), 'updated_at' => now()
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Text names for Student address fields
        $countryName = 'Democratic Republic of the Congo';
        $stateName = 'Kinshasa';
        $cityName = 'Lemba';

        // ---------------------------------------------------------
        // 1. Packages (Platform Level)
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

        // ---------------------------------------------------------
        // 2. Institution (UPDATED LOGIC)
        // ---------------------------------------------------------
        // Generate Code using Service
        $instCode = IdGeneratorService::generateInstitutionCode((string)$stateId, (string)$cityId);

        $institution = Institution::firstOrCreate(
            ['email' => 'info@montamba.cd'], 
            [
                'code' => $instCode,
                'name' => 'Complexe Scolaire Mont Amba',
                'acronym' => 'CSMA',
                'type' => 'mixed',
                'country' => $countryId, // ID
                'state' => $stateId,     // ID (Was City)
                'city' => $cityId,       // ID (Was Commune)
                // 'commune' => 'Lemba', // REMOVED as requested
                'address' => 'Avenue de l\'Université, Lemba',
                'phone' => '+243815550000',
                'is_active' => true,
            ]
        );

        // 2.1 Create Default Roles
        $rolesToCreate = [
            RoleEnum::HEAD_OFFICER->value,
            RoleEnum::TEACHER->value,
            RoleEnum::STUDENT->value,
            'Staff',        
            'Branch Admin'  
        ];

        foreach ($rolesToCreate as $roleName) {
            Role::firstOrCreate(
                ['name' => $roleName, 'institution_id' => $institution->id],
                ['guard_name' => 'web']
            );
        }

        // 2.2 Create Subscription
        $duration = (int) $premiumPackage->duration_days;

        Subscription::firstOrCreate(
            ['institution_id' => $institution->id],
            [
                'package_id' => $premiumPackage->id,
                'start_date' => now(),
                'end_date' => now()->addDays($duration),
                'status' => 'active',
                'price_paid' => $premiumPackage->price,
                'payment_method' => 'bank_transfer',
                'transaction_reference' => 'TXN-' . strtoupper(uniqid()),
                'notes' => 'Seeded Premium Subscription'
            ]
        );

        // ---------------------------------------------------------
        // 3. Campus
        // ---------------------------------------------------------
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
            ['role' => RoleEnum::TEACHER->value, 'dept' => 'Sciences'],
            ['role' => RoleEnum::TEACHER->value, 'dept' => 'Lettres'],
            ['role' => RoleEnum::TEACHER->value, 'dept' => 'Math'],
            ['role' => 'Staff', 'dept' => 'Finance'],
            ['role' => RoleEnum::HEAD_OFFICER->value, 'dept' => 'Administration']
        ];
        
        $teacherIds = [];
        $adminUserId = null; 

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
            
            if ($roleName == RoleEnum::HEAD_OFFICER->value) {
                $adminUserId = $user->id;
            }

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

        if (!$adminUserId && $teacherIds) $adminUserId = User::find(1)->id ?? $teacherIds[0];

        // ---------------------------------------------------------
        // 7. Class Sections & Subjects
        // ---------------------------------------------------------
        $sections = [];
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

        // ---------------------------------------------------------
        // 8. Students (UPDATED LOGIC)
        // ---------------------------------------------------------
        $students = [];
        $studentRole = Role::where('name', RoleEnum::STUDENT->value)
                           ->where('institution_id', $institution->id)
                           ->first();

        for ($i = 1; $i <= 20; $i++) {
            $firstName = $faker->firstName;
            $lastName = $faker->lastName;
            
            // A. Create User First (Updated)
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

            // B. Generate ID using User ID seed (Updated)
            $admissionNumber = IdGeneratorService::generateStudentId($institution, $session, $sUser->id);

            // C. Create Student (Updated with Location Strings and New ID)
            $student = Student::firstOrCreate(
                ['user_id' => $sUser->id],
                [
                    'institution_id' => $institution->id,
                    'campus_id' => $campus->id,
                    'admission_number' => $admissionNumber,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'gender' => $faker->randomElement(['male', 'female']),
                    'dob' => $faker->dateTimeBetween('-18 years', '-6 years'),
                    'admission_date' => '2024-09-04',
                    'father_name' => $faker->name('male'),
                    'father_phone' => '+24381' . $faker->numerify('#######'),
                    // Using text strings for student details as per controller logic
                    'country' => $countryName,
                    'state' => $stateName,
                    'city' => $cityName,
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

        // ---------------------------------------------------------
        // 9. Finance (Fees)
        // ---------------------------------------------------------
        $tuitionType = FeeType::firstOrCreate(['institution_id' => $institution->id, 'name' => 'Frais Scolaires']);
        $fee = FeeStructure::firstOrCreate(
            ['institution_id' => $institution->id, 'name' => 'Minerval T1'],
            [
                'academic_session_id' => $session->id,
                'fee_type_id' => $tuitionType->id,
                'amount' => 150.00,
                'frequency' => 'termly'
            ]
        );

        // ---------------------------------------------------------
        // 10. Communication (Notices)
        // ---------------------------------------------------------
        $noticesData = [
            [
                'title' => 'Rentrée Scolaire 2024',
                'content' => 'La rentrée scolaire est fixée au 4 Septembre à 7h30. Soyez à l\'heure.',
                'type' => 'info',
                'audience' => 'all',
                'published_at' => now()->subDays(10),
            ],
            [
                'title' => 'Réunion des Parents',
                'content' => 'Une réunion importante aura lieu ce Samedi pour discuter des frais.',
                'type' => 'event',
                'audience' => 'parent',
                'published_at' => now()->subDays(2),
            ],
            [
                'title' => 'Urgent: Paiement Frais',
                'content' => 'Veuillez régulariser vos frais avant le début des examens.',
                'type' => 'urgent',
                'audience' => 'student',
                'published_at' => now(),
            ]
        ];

        foreach ($noticesData as $n) {
            Notice::firstOrCreate(
                ['title' => $n['title'], 'institution_id' => $institution->id],
                [
                    'content' => $n['content'],
                    'type' => $n['type'],
                    'audience' => $n['audience'],
                    'published_at' => $n['published_at'], 
                    'is_published' => true,
                    'created_by' => $adminUserId 
                ]
            );
        }

        // ---------------------------------------------------------
        // 11. Voting System (Elections)
        // ---------------------------------------------------------
        $election = Election::firstOrCreate(
            ['title' => 'Élections du Comité des Élèves ' . $session->name, 'institution_id' => $institution->id],
            [
                'academic_session_id' => $session->id,
                'description' => 'Élection pour choisir le Doyen et les chefs de classe.',
                'start_date' => now()->subDays(1),
                'end_date' => now()->addDays(2),
                'status' => 'published',
            ]
        );

        // Positions
        $posPresident = ElectionPosition::firstOrCreate(['election_id' => $election->id, 'name' => 'Doyen (Président)'], ['sequence' => 1]);
        $posVice = ElectionPosition::firstOrCreate(['election_id' => $election->id, 'name' => 'Vice-Doyen'], ['sequence' => 2]);

        // Candidates (Pick random students)
        // Ensure we have enough students seeded
        if (count($students) >= 4) {
            $candidateStudents = $faker->randomElements($students, 4);
            
            $cand1 = Candidate::firstOrCreate(['election_id' => $election->id, 'student_id' => $candidateStudents[0]->id], ['election_position_id' => $posPresident->id, 'status' => 'approved']);
            $cand2 = Candidate::firstOrCreate(['election_id' => $election->id, 'student_id' => $candidateStudents[1]->id], ['election_position_id' => $posPresident->id, 'status' => 'approved']);
            
            $cand3 = Candidate::firstOrCreate(['election_id' => $election->id, 'student_id' => $candidateStudents[2]->id], ['election_position_id' => $posVice->id, 'status' => 'approved']);
            $cand4 = Candidate::firstOrCreate(['election_id' => $election->id, 'student_id' => $candidateStudents[3]->id], ['election_position_id' => $posVice->id, 'status' => 'approved']);

            // Votes (Simulate some voting)
            $voters = $faker->randomElements($students, 10);
            foreach($voters as $voter) {
                // Check if already voted
                $exists = DB::table('votes')->where('election_id', $election->id)->where('voter_id', $voter->id)->exists();
                if (!$exists) {
                    $chosenCandidate = $faker->randomElement([$cand1, $cand2]);
                    
                    DB::table('votes')->insert([
                        'election_id' => $election->id,
                        'election_position_id' => $posPresident->id,
                        'candidate_id' => $chosenCandidate->id, 
                        'voter_id' => $voter->id,
                        'voted_at' => now(),
                    ]);
                }
            }
        }

        // Re-enable mass assignment protection
        Model::reguard();

        $this->command->info('✅ Bulk Dummy Data Seeded Successfully!');
    }
}