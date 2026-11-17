<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDentalAppointmentRequest;
use App\Http\Requests\UpdateDentalAppointmentRequest;
use App\Models\DentalAppointment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\Procedure;
use App\Services\DentalAppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DentalAppointmentController extends Controller
{
    /**
     * Display all dental appointments.
     */
    public function index(): Response
    {
        return Inertia::render('dental-appointments/index', [
            'appointments' => DentalAppointment::with([
                'patient',
                'dentist',
                'procedureEntries.procedure',
            ])
                ->withSum('procedureEntries as total_cost', 'unit_cost')
                ->latest('scheduled_datetime')
                ->paginate(10),
            'flash' => [
                'success' => session('success'),
            ],
        ]);
    }

    /**
     * Show the appointment creation form data.
     */
    public function create(): Response
    {
        return Inertia::render('dental-appointments/create', [
            ...$this->formOptions(),
        ]);
    }

    /**
     * Store a new dental appointment.
     */
    public function store(
        StoreDentalAppointmentRequest $request,
        DentalAppointmentService $service
    ): RedirectResponse {
        $service->create($request->validated());

        return redirect()
            ->route('dental-appointments.index')
            ->with('success', 'Appointment created successfully.');
    }

    /**
     * Display a specific dental appointment.
     */
    public function show(
        DentalAppointment $dentalAppointment
    ): JsonResponse {
        return response()->json(
            $dentalAppointment->load([
                'patient',
                'dentist',
                'procedureEntries.procedure',
            ])
        );
    }

    /**
     * Show the appointment editing form data.
     */
    public function edit(
        DentalAppointment $dentalAppointment
    ): Response {
        $dentalAppointment->load([
            'patient',
            'dentist',
            'procedureEntries.procedure',
        ]);

        return Inertia::render('dental-appointments/edit', [
            'appointment' => [
                'id' => $dentalAppointment->id,
                'patient_id' => $dentalAppointment->patient_id,
                'dentist_id' => $dentalAppointment->dentist_id,
                'scheduled_datetime' => $dentalAppointment->scheduled_datetime->format('Y-m-d\\TH:i:s'),
                'payment_status' => $dentalAppointment->payment_status,
                'procedure_entries' => $dentalAppointment->procedureEntries->map(fn ($entry) => [
                    'id' => $entry->id,
                    'procedure_id' => $entry->procedure_id,
                    'tooth_number' => $entry->tooth_number,
                    'unit_cost' => $entry->unit_cost,
                    'procedure' => $entry->procedure,
                ])->values(),
            ],
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update an existing dental appointment.
     */
    public function update(
        UpdateDentalAppointmentRequest $request,
        DentalAppointment $dentalAppointment,
        DentalAppointmentService $service
    ): RedirectResponse {
        $service->update(
            $dentalAppointment,
            $request->validated()
        );

        return redirect()
            ->route('dental-appointments.index')
            ->with('success', 'Appointment updated successfully.');
    }

    /**
     * Delete an existing dental appointment.
     */
    public function destroy(
        DentalAppointment $dentalAppointment,
        DentalAppointmentService $service
    ): RedirectResponse {
        $service->delete($dentalAppointment);

        return redirect()
            ->route('dental-appointments.index')
            ->with('success', 'Appointment deleted successfully.');
    }

    private function formOptions(): array
    {
        return [
            'patients' => Patient::orderBy('patient_name')->get(['id', 'patient_name']),
            'dentists' => Dentist::orderBy('dentist_name')->get(['id', 'dentist_name']),
            'procedures' => Procedure::orderBy('procedure_name')->get([
                'id',
                'procedure_name',
                'procedure_cost',
            ]),
        ];
    }
}
