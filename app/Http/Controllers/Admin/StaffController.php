<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Staff accounts and role assignment. Guards against privilege escalation:
 *  - staff can only grant roles whose permissions they already hold;
 *  - staff cannot change their own roles or deactivate themselves;
 *  - the last active Super Admin cannot be removed or deactivated.
 */
class StaffController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request)
    {
        $staff = User::staff()->with('roles')->search($request->string('q')->limit(100)->toString() ?: null)
            ->orderByDesc('is_active')->orderBy('first_name')->paginate(25)->withQueryString();

        return view('admin.staff.index', ['staff' => $staff, 'roles' => Role::orderByDesc('rank')->get()]);
    }

    public function create(Request $request)
    {
        return view('admin.staff.form', ['member' => new User, 'roles' => $this->assignableRoles($request->user()), 'selected' => []]);
    }

    public function store(Request $request)
    {
        $actor = $request->user();
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', Rule::in($this->assignableRoles($actor)->pluck('id')->all())],
        ], ['roles.*.in' => 'You cannot assign a role with permissions you do not hold.']);

        $member = DB::transaction(function () use ($data) {
            $member = new User(collect($data)->only(['first_name', 'last_name', 'email', 'phone', 'password'])->all());
            $member->account_type = User::TYPE_STAFF;
            $member->is_active = true;
            $member->save();
            $member->roles()->sync($data['roles']);

            return $member;
        });
        $this->audit->log('staff.created', $member, ['roles' => $member->roles()->pluck('slug')->all()]);

        return redirect()->route('admin.staff.index')->with('success', "Staff account for {$member->name} created. Ask them to change their password after first sign-in.");
    }

    public function edit(Request $request, User $staff)
    {
        $this->ensureManageable($request->user(), $staff);

        return view('admin.staff.form', [
            'member' => $staff,
            'roles' => $this->assignableRoles($request->user()),
            'selected' => $staff->roles->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, User $staff)
    {
        $actor = $request->user();
        $this->ensureManageable($actor, $staff);
        $isSelf = $actor->is($staff);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($staff->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'roles' => [$isSelf ? 'prohibited' : 'required', 'array', 'min:1'],
            'roles.*' => ['integer', Rule::in($this->assignableRoles($actor)->pluck('id')->all())],
        ], [
            'roles.prohibited' => 'You cannot change your own roles.',
            'roles.*.in' => 'You cannot assign a role with permissions you do not hold.',
        ]);

        DB::transaction(function () use ($staff, $data, $isSelf) {
            $staff->fill(collect($data)->only(['first_name', 'last_name', 'email', 'phone'])->all());
            if (! empty($data['password'])) {
                $staff->password = $data['password'];
            }
            $staff->save();

            if (! $isSelf) {
                $superAdminId = Role::where('slug', 'super-admin')->value('id');
                if ($staff->hasRole('super-admin') && ! in_array($superAdminId, $data['roles']) && $this->activeSuperAdmins()->count() <= 1) {
                    throw ValidationException::withMessages(['roles' => 'There must be at least one active Super Admin.']);
                }
                $staff->roles()->sync($data['roles']);
            }
        });
        $this->audit->log('staff.updated', $staff, ['roles' => $staff->roles()->pluck('slug')->all(), 'password_changed' => ! empty($data['password'])]);

        return redirect()->route('admin.staff.index')->with('success', "Staff account for {$staff->name} saved.");
    }

    public function toggle(Request $request, User $staff)
    {
        $actor = $request->user();
        $this->ensureManageable($actor, $staff);
        if ($actor->is($staff)) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }
        if ($staff->is_active && $staff->hasRole('super-admin') && $this->activeSuperAdmins()->count() <= 1) {
            return back()->with('error', 'There must be at least one active Super Admin.');
        }
        $staff->is_active = ! $staff->is_active;
        $staff->save();
        $this->audit->log($staff->is_active ? 'staff.activated' : 'staff.deactivated', $staff);

        return back()->with('success', $staff->is_active ? "{$staff->name} can sign in again." : "{$staff->name} has been deactivated and can no longer sign in.");
    }

    /** Roles whose permissions are a subset of the actor's own permissions. */
    private function assignableRoles(User $actor): Collection
    {
        $own = $actor->permissions();

        return Role::orderByDesc('rank')->get()->filter(function (Role $role) use ($actor, $own) {
            if ($actor->isSuperAdmin()) {
                return true;
            }
            $perms = $role->permissions();

            return ! in_array('*', $perms, true) && array_diff($perms, $own) === [] && $role->rank <= $actor->highestRoleRank();
        })->values();
    }

    private function ensureManageable(User $actor, User $target): void
    {
        abort_unless($target->isStaff(), 404);
        // A non-super-admin may not edit someone more privileged than themselves.
        if (! $actor->isSuperAdmin() && $target->highestRoleRank() > $actor->highestRoleRank()) {
            abort(403);
        }
    }

    private function activeSuperAdmins(): Collection
    {
        return User::staff()->where('is_active', true)->whereHas('roles', fn ($q) => $q->where('slug', 'super-admin'))->get();
    }
}
