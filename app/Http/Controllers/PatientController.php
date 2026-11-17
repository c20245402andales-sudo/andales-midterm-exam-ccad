<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Patient;
use App\Services\PatientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    /**
     * Display all patients.
     */
    public function index(Request $request): Response
    {
        $querySearch = $request->query('search');
        $search = is_string($querySearch) ? trim($querySearch) : '';

        return Inertia::render('patients/index', [
            'patients' => Patient::query()
                ->withCount('appointments')
                ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                    $query->where('patient_code', 'like', "%{$search}%")
                        ->orWhere('patient_name', 'like', "%{$search}%")
                        ->orWhere('patient_contact', 'like', "%{$search}%");
                }))
                ->orderBy('patient_name')
                ->paginate(10)
                ->withQueryString(),
            'search' => $search,
            'flash' => [
                'success' => session('success'),
            ],
        ]);
    }

    /**
     * Show the create patient page.
     */
    public function create(): Response
    {
        return Inertia::render('patients/create');
    }

    /**
     * Store a new patient.
     */
    public function store(
        StorePatientRequest $request,
        PatientService $service
    ): RedirectResponse {
        $service->create($request->validated());

        return redirect()
            ->route('patients.index')
            ->with('success', 'Patient created successfully.');
    }

    /**
     * Display a specific patient.
     */
    public function show(Patient $patient): Response
    {
        $patient->load([
            'appointments.dentist',
            'appointments.procedureEntries.procedure',
        ]);

        return Inertia::render('patients/show', [
            'patient' => $patient,
        ]);
    }

    /**
     * Show the edit patient page.
     */
    public function edit(Patient $patient): Response
    {
        return Inertia::render('patients/edit', [
            'patient' => $patient,
        ]);
    }

    /**
     * Update an existing patient.
     */
    public function update(
        UpdatePatientRequest $request,
        Patient $patient,
        PatientService $service
    ): RedirectResponse {
        $service->update(
            $patient,
            $request->validated()
        );

        return redirect()
            ->route('patients.index')
            ->with('success', 'Patient updated successfully.');
    }

    /**
     * Delete a patient.
     */
    public function destroy(
        Patient $patient,
        PatientService $service
    ): RedirectResponse {
        $service->delete($patient);

        return redirect()
            ->route('patients.index')
            ->with('success', 'Patient deleted successfully.');
    }
}
