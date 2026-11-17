<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDentistRequest;
use App\Http\Requests\UpdateDentistRequest;
use App\Models\Dentist;
use App\Services\DentistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DentistController extends Controller
{
    public function index(Request $request): Response
    {
        $querySearch = $request->query('search');
        $search = is_string($querySearch) ? trim($querySearch) : '';

        return Inertia::render('dentists/index', [
            'dentists' => Dentist::query()
                ->withCount('appointments')
                ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                    $query->where('dentist_name', 'like', "%{$search}%")
                        ->orWhere('dentist_license', 'like', "%{$search}%");
                }))
                ->orderBy('dentist_name')
                ->paginate(10)
                ->withQueryString(),
            'search' => $search,
            'flash' => [
                'success' => session('success'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('dentists/create');
    }

    public function store(
        StoreDentistRequest $request,
        DentistService $service
    ): RedirectResponse {
        $service->create($request->validated());

        return redirect()
            ->route('dentists.index')
            ->with('success', 'Dentist created successfully.');
    }

    public function show(Dentist $dentist): Response
    {
        $dentist->load([
            'appointments.patient',
        ]);

        return Inertia::render('dentists/show', [
            'dentist' => $dentist,
        ]);
    }

    public function edit(Dentist $dentist): Response
    {
        return Inertia::render('dentists/edit', [
            'dentist' => $dentist,
        ]);
    }

    public function update(
        UpdateDentistRequest $request,
        Dentist $dentist,
        DentistService $service
    ): RedirectResponse {
        $service->update($dentist, $request->validated());

        return redirect()
            ->route('dentists.index')
            ->with('success', 'Dentist updated successfully.');
    }

    public function destroy(
        Dentist $dentist,
        DentistService $service
    ): RedirectResponse {
        $service->delete($dentist);

        return redirect()
            ->route('dentists.index')
            ->with('success', 'Dentist deleted successfully.');
    }
}
