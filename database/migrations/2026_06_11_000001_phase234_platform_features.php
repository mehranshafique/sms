<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'national_id')) {
                $table->string('national_id', 50)->nullable()->after('admission_number');
            }
            if (!Schema::hasColumn('students', 'origin_province')) {
                $table->string('origin_province', 100)->nullable()->after('place_of_birth');
            }
        });

        Schema::table('institutions', function (Blueprint $table) {
            if (!Schema::hasColumn('institutions', 'epst_school_code')) {
                $table->string('epst_school_code', 50)->nullable()->after('code');
            }
            if (!Schema::hasColumn('institutions', 'secondary_currency')) {
                $table->string('secondary_currency', 3)->nullable()->default('USD')->after('email');
            }
            if (!Schema::hasColumn('institutions', 'exchange_rate')) {
                $table->decimal('exchange_rate', 12, 4)->nullable()->after('secondary_currency');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'mobile_money_provider')) {
                $table->string('mobile_money_provider', 30)->nullable()->after('method');
            }
            if (!Schema::hasColumn('payments', 'mobile_reference')) {
                $table->string('mobile_reference', 80)->nullable()->after('mobile_money_provider');
            }
        });

        if (Schema::hasTable('student_enrollments') && !Schema::hasColumn('student_enrollments', 'enrollment_type')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->enum('enrollment_type', ['academic', 'administrative'])
                    ->default('academic')
                    ->after('status');
            });
        }

        if (!Schema::hasTable('state_exams')) {
            Schema::create('state_exams', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
                $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
                $table->string('name', 150);
                $table->enum('level', ['primary_6', 'secondary_8']);
                $table->date('exam_date')->nullable();
                $table->string('centre', 150)->nullable();
                $table->enum('status', ['draft', 'open', 'closed', 'published'])->default('draft');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('state_exam_candidates')) {
            Schema::create('state_exam_candidates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('state_exam_id')->constrained('state_exams')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->string('candidate_number', 50)->nullable();
                $table->decimal('score', 8, 2)->nullable();
                $table->string('mention', 50)->nullable();
                $table->enum('status', ['registered', 'passed', 'failed', 'absent'])->default('registered');
                $table->timestamps();
                $table->unique(['state_exam_id', 'student_id']);
            });
        }

        if (!Schema::hasTable('lmd_deliberations')) {
            Schema::create('lmd_deliberations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
                $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->unsignedTinyInteger('semester')->default(1);
                $table->decimal('average', 5, 2)->nullable();
                $table->string('mention', 50)->nullable();
                $table->enum('decision', ['admitted', 'adjourned', 'rattrapage'])->default('adjourned');
                $table->text('notes')->nullable();
                $table->enum('status', ['draft', 'validated'])->default('draft');
                $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('validated_at')->nullable();
                $table->timestamps();
                $table->unique(['academic_session_id', 'student_id', 'semester'], 'lmd_delib_unique');
            });
        }

        if (!Schema::hasTable('transport_vehicles')) {
            Schema::create('transport_vehicles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
                $table->string('plate_number', 30);
                $table->unsignedSmallInteger('capacity')->default(30);
                $table->string('driver_name', 100)->nullable();
                $table->string('driver_phone', 30)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transport_routes')) {
            Schema::create('transport_routes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
                $table->foreignId('transport_vehicle_id')->nullable()->constrained('transport_vehicles')->nullOnDelete();
                $table->string('name', 100);
                $table->time('departure_time')->nullable();
                $table->string('zones', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transport_assignments')) {
            Schema::create('transport_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transport_route_id')->constrained('transport_routes')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->string('pickup_point', 150)->nullable();
                $table->enum('status', ['active', 'suspended'])->default('active');
                $table->timestamps();
                $table->unique(['transport_route_id', 'student_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_assignments');
        Schema::dropIfExists('transport_routes');
        Schema::dropIfExists('transport_vehicles');
        Schema::dropIfExists('lmd_deliberations');
        Schema::dropIfExists('state_exam_candidates');
        Schema::dropIfExists('state_exams');

        if (Schema::hasTable('student_enrollments') && Schema::hasColumn('student_enrollments', 'enrollment_type')) {
            Schema::table('student_enrollments', fn (Blueprint $t) => $t->dropColumn('enrollment_type'));
        }

        Schema::table('payments', function (Blueprint $table) {
            foreach (['mobile_reference', 'mobile_money_provider'] as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('institutions', function (Blueprint $table) {
            foreach (['exchange_rate', 'secondary_currency', 'epst_school_code'] as $col) {
                if (Schema::hasColumn('institutions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('students', function (Blueprint $table) {
            foreach (['origin_province', 'national_id'] as $col) {
                if (Schema::hasColumn('students', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
