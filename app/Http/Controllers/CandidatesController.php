<?php

namespace App\Http\Controllers;

use App\Models\CandidateDevice;
use App\Models\District;
use App\Models\Tehsil;
use App\Models\UC;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CandidatesController extends Controller
{
    public function index(Request $request)
    {
        $ucs = UC::with('tehsil.district')->orderBy('name')->get();

        $candidates = User::where('role', 'candidate')
            ->with(['uc.tehsil.district', 'devices'])
            ->withCount(['devices as active_devices_count' => fn ($q) => $q->where('is_revoked', false)])
            ->when($request->query('uc_id'), fn ($q, $ucId) => $q->where('uc_id', $ucId))
            ->when($request->query('status'), fn ($q, $st) => $q->where('status', $st))
            ->when($request->query('search'), function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhere('phone', 'like', "%{$s}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('candidates.index', compact('candidates', 'ucs'));
    }

    public function create()
    {
        $tehsils = Tehsil::with('district')->orderBy('name')->get();
        $ucs = UC::with(['tehsil.district', 'provincialAssembly', 'nationalAssembly'])
            ->orderBy('tehsil_id')
            ->orderByRaw('CAST(uc_no AS UNSIGNED) ASC')
            ->orderBy('name')
            ->get();

        return view('candidates.create', compact('tehsils', 'ucs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', Password::defaults()],
            'phone' => 'nullable|string|max:30',
            'uc_id' => 'required|exists:ucs,id',
            'max_devices' => 'nullable|integer|min:1|max:5000',
            'status' => 'required|in:active,suspended',
            'expires_at' => 'nullable|date',
            'party_name' => 'nullable|string|max:255',
            'is_independent' => 'nullable|boolean',
            'candidate_symbol' => 'nullable|string|max:255',
            'party_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
            'candidate_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
            'candidate_symbol_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
        ]);

        $validated['role'] = 'candidate';
        $validated['password'] = Hash::make($validated['password']);
        $validated['max_devices'] = !empty($validated['max_devices']) ? (int) $validated['max_devices'] : 20;
        $validated['is_independent'] = $request->has('is_independent') || ($request->party_name === 'Independent' || $request->party_name === 'Azad');

        if ($request->hasFile('party_logo')) {
            $file = $request->file('party_logo');
            $name = 'party_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/branding'), $name);
            $validated['party_logo'] = 'uploads/branding/' . $name;
        }

        if ($request->hasFile('candidate_image')) {
            $file = $request->file('candidate_image');
            $name = 'candidate_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/branding'), $name);
            $validated['candidate_image'] = 'uploads/branding/' . $name;
        }

        if ($request->hasFile('candidate_symbol_image')) {
            $file = $request->file('candidate_symbol_image');
            $name = 'symbol_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/branding'), $name);
            $validated['candidate_symbol_image'] = 'uploads/branding/' . $name;
        }

        User::create($validated);

        return redirect()->route('candidates.index')
            ->with('toast', ['type' => 'success', 'message' => 'Candidate account created successfully with party branding.']);
    }

    public function edit(User $candidate)
    {
        if ($candidate->role !== 'candidate') {
            abort(404);
        }

        $tehsils = Tehsil::with('district')->orderBy('name')->get();
        $ucs = UC::with(['tehsil.district', 'provincialAssembly', 'nationalAssembly'])
            ->orderBy('tehsil_id')
            ->orderByRaw('CAST(uc_no AS UNSIGNED) ASC')
            ->orderBy('name')
            ->get();

        return view('candidates.edit', compact('candidate', 'tehsils', 'ucs'));
    }

    public function update(Request $request, User $candidate)
    {
        if ($candidate->role !== 'candidate') {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $candidate->id,
            'password' => ['nullable', Password::defaults()],
            'phone' => 'nullable|string|max:30',
            'uc_id' => 'required|exists:ucs,id',
            'max_devices' => 'nullable|integer|min:1|max:5000',
            'status' => 'required|in:active,suspended',
            'expires_at' => 'nullable|date',
            'party_name' => 'nullable|string|max:255',
            'is_independent' => 'nullable|boolean',
            'candidate_symbol' => 'nullable|string|max:255',
            'party_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
            'candidate_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
            'candidate_symbol_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['max_devices'] = !empty($validated['max_devices']) ? (int) $validated['max_devices'] : ($candidate->max_devices ?: 20);
        $validated['is_independent'] = $request->has('is_independent') || ($request->party_name === 'Independent' || $request->party_name === 'Azad');

        if ($request->hasFile('party_logo')) {
            $file = $request->file('party_logo');
            $name = 'party_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/branding'), $name);
            $validated['party_logo'] = 'uploads/branding/' . $name;
        }

        if ($request->hasFile('candidate_image')) {
            $file = $request->file('candidate_image');
            $name = 'candidate_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/branding'), $name);
            $validated['candidate_image'] = 'uploads/branding/' . $name;
        }

        if ($request->hasFile('candidate_symbol_image')) {
            $file = $request->file('candidate_symbol_image');
            $name = 'symbol_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/branding'), $name);
            $validated['candidate_symbol_image'] = 'uploads/branding/' . $name;
        }

        $candidate->update($validated);

        return redirect()->route('candidates.index')
            ->with('toast', ['type' => 'success', 'message' => 'Candidate branding and account updated successfully.']);
    }

    public function destroy(User $candidate)
    {
        if ($candidate->role !== 'candidate') {
            abort(404);
        }

        $candidate->delete();

        return redirect()->route('candidates.index')
            ->with('toast', ['type' => 'success', 'message' => 'Candidate deleted.']);
    }

    /**
     * Show devices assigned to a candidate and allow revoking.
     */
    public function devices(User $candidate)
    {
        if ($candidate->role !== 'candidate') {
            abort(404);
        }

        $candidate->load(['uc', 'devices' => fn ($q) => $q->orderBy('last_active_at', 'desc')]);

        return view('candidates.devices', compact('candidate'));
    }

    /**
     * Toggle revoke status of a device.
     */
    public function toggleDeviceRevoke(CandidateDevice $device)
    {
        $device->is_revoked = !$device->is_revoked;
        $device->save();

        $msg = $device->is_revoked ? 'Device has been revoked and blocked.' : 'Device has been unblocked.';

        return back()->with('toast', ['type' => 'info', 'message' => $msg]);
    }

    /**
     * Delete a device record.
     */
    public function destroyDevice(CandidateDevice $device)
    {
        $device->delete();

        return back()->with('toast', ['type' => 'success', 'message' => 'Device record removed.']);
    }
}
