<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Staff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function suggestions(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        if ($search === '') {
            return response()->json(['suggestions' => []]);
        }

        $users = DB::table('user_overview_view')
            ->where(function ($q) use ($search): void {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            })
            ->orderBy('full_name')
            ->limit(8)
            ->get(['id', 'full_name', 'email']);

        $suggestions = $users->map(function (object $user): array {
            return [
                'value' => "{$user->full_name} ({$user->email})",
                'id' => (string) $user->id,
                'name' => $user->full_name,
                'email' => $user->email,
            ];
        })->values();

        return response()->json(['suggestions' => $suggestions]);
    }

    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $role = $request->query('role');
        $status = $request->query('status');

        $query = DB::table('user_overview_view')
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($inner) use ($search): void {
                    $inner->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%");
                });
            })
            ->when($role && $role !== 'All', fn ($q) => $q->where('user_role', $role))
            ->when($status && $status !== 'All', fn ($q) => $q->where('user_status', $status))
            ->orderByDesc('id');

        $users = $query->paginate(10)->through(function (object $user): array {
            $userCreatedAt = $user->user_created_at ? Carbon::parse($user->user_created_at) : null;
            $lastVisitAt = $user->last_visit_at ? Carbon::parse($user->last_visit_at) : null;

            return [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->user_role,
                'membership_type' => $user->membership_type,
                'status' => $user->user_status,
                'join_date' => $user->join_date ?: $userCreatedAt?->format('Y-m-d'),
                'expiry_date' => $user->expiry_date,
                'last_visit' => $lastVisitAt?->format('Y-m-d H:i'),
            ];
        });

        return response()->json([
            'users' => $users,
            'stats' => $this->stats(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['Admin', 'Staff', 'Member'])],
            'membership_type' => ['nullable', 'string', 'max:50', Rule::requiredIf(fn () => $request->input('role') === 'Member')],
            'status' => ['required', Rule::in(['Active', 'Inactive', 'Suspended'])],
            'join_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:join_date'],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'role' => $validated['role'],
                'status' => $validated['status'],
                'password' => Hash::make($validated['password']),
            ]);

            if ($validated['role'] === 'Member') {
                Member::create([
                    'user_id' => $user->id,
                    'phone' => $validated['phone'] ?? null,
                    'membership_type' => $validated['membership_type'],
                    'join_date' => $validated['join_date'] ?? now()->toDateString(),
                    'expiry_date' => $validated['expiry_date'] ?? null,
                    'status' => $validated['status'] === 'Active' ? 'Active' : 'Cancelled',
                ]);
            } elseif ($validated['role'] === 'Staff') {
                Staff::create([
                    'user_id' => $user->id,
                    'role' => 'Receptionist',
                    'specialization' => 'General Operations',
                ]);
            }

            return $user->load('member');
        });

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $this->formatUser($user),
            'stats' => $this->stats(),
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        $user->load('member');

        return response()->json([
            'user' => $this->formatUser($user),
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(['Admin', 'Staff', 'Member'])],
            'membership_type' => ['nullable', 'string', 'max:50', Rule::requiredIf(fn () => $request->input('role') === 'Member')],
            'status' => ['required', Rule::in(['Active', 'Inactive', 'Suspended'])],
            'join_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:join_date'],
        ]);

        DB::transaction(function () use ($validated, $user): void {
            $user->update([
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'role' => $validated['role'],
                'status' => $validated['status'],
            ]);

            if ($validated['role'] === 'Member') {
                Member::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'phone' => $validated['phone'] ?? null,
                        'membership_type' => $validated['membership_type'],
                        'join_date' => $validated['join_date'] ?? now()->toDateString(),
                        'expiry_date' => $validated['expiry_date'] ?? null,
                        'status' => $validated['status'] === 'Active' ? 'Active' : 'Cancelled',
                    ]
                );
                $user->staff()->delete();
            } elseif ($validated['role'] === 'Staff') {
                Staff::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'role' => 'Receptionist',
                        'specialization' => 'General Operations',
                    ]
                );
                $user->member()->delete();
            } else {
                $user->member()->delete();
                $user->staff()->delete();
            }
        });

        $user->refresh()->load('member');

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $this->formatUser($user),
            'stats' => $this->stats(),
        ]);
    }

    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['Active', 'Inactive', 'Suspended'])],
        ]);

        $user->update([
            'status' => $validated['status'],
        ]);

        if ($user->member) {
            $memberStatus = $validated['status'] === 'Active' ? 'Active' : ($validated['status'] === 'Suspended' ? 'Cancelled' : 'Expired');
            $user->member->update(['status' => $memberStatus]);
        }

        return response()->json([
            'message' => 'User status updated.',
            'user' => $this->formatUser($user->fresh()->load('member')),
            'stats' => $this->stats(),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
            'stats' => $this->stats(),
        ]);
    }

    private function stats(): array
    {
        return [
            'active' => DB::table('user_overview_view')->where('user_status', 'Active')->count(),
            'inactive' => DB::table('user_overview_view')->where('user_status', 'Inactive')->count(),
            'suspended' => DB::table('user_overview_view')->where('user_status', 'Suspended')->count(),
        ];
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'membership_type' => $user->member?->membership_type,
            'status' => $user->status,
            'join_date' => optional($user->member?->join_date)->format('Y-m-d') ?? optional($user->created_at)->format('Y-m-d'),
            'expiry_date' => optional($user->member?->expiry_date)->format('Y-m-d'),
            'last_visit' => optional($user->last_visit_at)->format('Y-m-d H:i'),
        ];
    }
}
