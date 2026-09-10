<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class DataEntryOperatorsController extends Controller
{
    public function index()
    {
        $operators = User::where('role', 'data_entry')
            ->latest()
            ->paginate(20);

        return view('operators.index', compact('operators'));
    }

    public function create()
    {
        return view('operators.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'string', 'min:6'],
            'phone' => 'nullable|string|max:30|unique:users,phone',
            'status' => 'required|in:active,suspended',
        ]);

        $validated['role'] = 'data_entry';
        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('operators.index')
            ->with('toast', ['type' => 'success', 'message' => "Data Entry Operator [{$validated['name']}] created successfully."]);
    }

    public function edit(User $operator)
    {
        if ($operator->role !== 'data_entry') {
            abort(404);
        }

        return view('operators.edit', compact('operator'));
    }

    public function update(Request $request, User $operator)
    {
        if ($operator->role !== 'data_entry') {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $operator->id,
            'password' => ['nullable', 'string', 'min:6'],
            'phone' => 'nullable|string|max:30|unique:users,phone,' . $operator->id,
            'status' => 'required|in:active,suspended',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $operator->update($validated);

        return redirect()->route('operators.index')
            ->with('toast', ['type' => 'success', 'message' => "Operator [{$operator->name}] updated successfully."]);
    }

    public function destroy(User $operator)
    {
        if ($operator->role !== 'data_entry') {
            abort(404);
        }

        $name = $operator->name;
        $operator->delete();

        return redirect()->route('operators.index')
            ->with('toast', ['type' => 'success', 'message' => "Operator [{$name}] deleted successfully."]);
    }
}
