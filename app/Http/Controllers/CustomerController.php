<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index()
    {
        $user = $this->user();
        $query = Customer::with(['owner', 'lead'])->latest();

        if (! $user?->isAdmin()) {
            $query->where('owner_id', $user?->id);
        }

        return view('customers.index', [
            'customers' => $query->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->user();
        $data = $this->validatedData($request);

        if (! $user?->isAdmin()) {
            $data['owner_id'] = $user?->id;
        }

        Customer::create($data);

        return redirect()->route('customers.index')->with('success', 'הלקוח נוצר בהצלחה.');
    }

    public function update(Request $request, Customer $customer)
    {
        $user = $this->authorizeCustomer($customer);
        $data = $this->validatedData($request, $customer);

        if (! $user->isAdmin()) {
            $data['owner_id'] = $customer->owner_id;
        }

        $customer->update($data);

        return redirect()->route('customers.index')->with('success', 'הלקוח עודכן בהצלחה.');
    }

    public function destroy(Customer $customer)
    {
        $this->authorizeCustomer($customer);
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'הלקוח נמחק בהצלחה.');
    }

    private function authorizeCustomer(Customer $customer): User
    {
        $user = $this->user();

        abort_unless(
            $user?->isAdmin() || $customer->owner_id === $user?->id,
            403
        );

        return $user;
    }

    private function validatedData(Request $request, ?Customer $customer = null): array
    {
        return $request->validate([
            'lead_id' => [
                'nullable',
                'exists:leads,id',
                Rule::unique('customers', 'lead_id')->ignore($customer?->id),
            ],
            'owner_id' => ['nullable', 'exists:users,id'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:150'],
            'job_title' => ['nullable', 'string', 'max:150'],
            'website' => ['nullable', 'url', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
