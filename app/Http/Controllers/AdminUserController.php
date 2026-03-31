<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminUserController extends Controller
{
    public function index()
    {
        $this->requireAdmin();

        return view('users.index', [
            'users' => User::withCount(['ownedLeads', 'customers'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->requireAdmin();

        User::create($this->validatedData($request));

        return redirect()->route('admin.users.index')->with('success', 'המשתמש נוצר בהצלחה.');
    }

    public function update(Request $request, User $user)
    {
        $this->requireAdmin();

        $data = $this->validatedData($request, $user);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'המשתמש עודכן בהצלחה.');
    }

    public function destroy(User $user)
    {
        $admin = $this->requireAdmin();

        abort_if($admin->is($user), 422, 'לא ניתן למחוק את המשתמש המחובר.');

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'המשתמש נמחק בהצלחה.');
    }

    private function validatedData(Request $request, ?User $user = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', 'in:admin,editor,viewer'],
            'password' => [
                $user ? 'nullable' : 'required',
                'confirmed',
                Password::min(6),
            ],
        ];

        return $request->validate($rules);
    }
}
