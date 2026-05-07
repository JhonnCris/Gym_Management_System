<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'role',
        'status',
        'last_visit_at',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_visit_at' => 'datetime',
    ];

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    /**
     * Get user overview (replaces user_overview_view)
     * Comprehensive view of users with their member/staff details
     */
    public function scopeWithOverview(Builder $query)
    {
        return $query->with(['member', 'staff'])
            ->select([
                'users.id',
                'users.full_name',
                'users.email',
                'users.phone',
                'users.role as user_role',
                'users.status as user_status',
                'users.created_at as user_created_at',
                'users.last_visit_at',
            ])
            ->leftJoin('members', 'members.user_id', '=', 'users.id')
            ->leftJoin('staff', 'staff.user_id', '=', 'users.id')
            ->addSelect([
                'members.member_id',
                'members.membership_type',
                'members.join_date',
                'members.expiry_date',
                'members.status as member_status',
                'staff.staff_id',
                'staff.role as staff_role',
                'staff.specialization',
            ])
            ->whereNull('users.deleted_at');
    }

    /**
     * Get membership distribution (replaces membership_distribution_view)
     */
    public static function getMembershipDistribution()
    {
        return Member::select('membership_type')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('membership_type')
            ->orderBy('aggregate', 'desc')
            ->get();
    }

    /**
     * Cleanup demo data (replaces cleanup_demo_data procedure)
     */
    public static function cleanupDemoData(): void
    {
        $demoEmails = [
            'admin.demo@wedumbell.test',
            'lazarjhonn@gmail.com',
            'coach.emma@wedumbell.test',
            'coach.james@wedumbell.test',
            'maria.santos@wedumbell.test',
            'kevin.reyes@wedumbell.test',
            'anna.dela-cruz@wedumbell.test'
        ];

        DB::transaction(function () use ($demoEmails) {
            // Delete attendances
            DB::table('attendances')
                ->join('members', 'attendances.member_id', '=', 'members.member_id')
                ->join('users', 'members.user_id', '=', 'users.id')
                ->whereIn('users.email', $demoEmails)
                ->delete();

            // Delete bookings
            DB::table('bookings')
                ->join('members', 'bookings.member_id', '=', 'members.member_id')
                ->join('users', 'members.user_id', '=', 'users.id')
                ->whereIn('users.email', $demoEmails)
                ->delete();

            // Delete payments
            DB::table('payments')
                ->join('members', 'payments.member_id', '=', 'members.member_id')
                ->join('users', 'members.user_id', '=', 'users.id')
                ->whereIn('users.email', $demoEmails)
                ->delete();

            // Delete class trainers
            DB::table('class_trainer')
                ->join('staff', 'class_trainer.staff_id', '=', 'staff.staff_id')
                ->join('users', 'staff.user_id', '=', 'users.id')
                ->whereIn('users.email', $demoEmails)
                ->delete();

            // Delete class equipment for demo equipment
            DB::table('class_equipment')
                ->join('equipments', 'class_equipment.equipment_id', '=', 'equipments.equipment_id')
                ->whereIn('equipments.name', ['Treadmill Pro X1', 'Spin Bike Elite', 'Boxing Pad Set'])
                ->delete();

            // Delete demo classes
            DB::table('classes')
                ->whereIn('class_name', ['Yoga Flow', 'Spin Class', 'Boxing Fundamentals'])
                ->delete();

            // Delete demo equipment
            DB::table('equipments')
                ->whereIn('name', ['Treadmill Pro X1', 'Spin Bike Elite', 'Boxing Pad Set'])
                ->delete();

            // Delete members
            DB::table('members')
                ->join('users', 'members.user_id', '=', 'users.id')
                ->whereIn('users.email', $demoEmails)
                ->delete();

            // Delete staff
            DB::table('staff')
                ->join('users', 'staff.user_id', '=', 'users.id')
                ->whereIn('users.email', $demoEmails)
                ->delete();

            // Delete users
            DB::table('users')
                ->whereIn('email', $demoEmails)
                ->delete();
        });
    }
}
