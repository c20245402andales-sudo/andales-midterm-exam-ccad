<?php

namespace App\Http\Controllers;

use App\Models\Procedure;
use App\Http\Requests\StoreProcedureRequest;
use App\Http\Requests\UpdateProcedureRequest;
use App\Services\ProcedureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProcedureController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        return Inertia::render('procedures/index', [
            'procedures' => Procedure::query()
                ->withCount('appointmentEntries')
                ->when($search !== '', fn ($query) => $query->where('procedure_name', 'like', "%{$search}%"))
                ->orderBy('procedure_name')
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
        return Inertia::render('procedures/create');
    }

    public function store(
        StoreProcedureRequest $request,
        ProcedureService $service
    ): RedirectResponse {
        $service->create($request->validated());

        return redirect()
            ->route('procedures.index')
            ->with('success', 'Procedure created successfully.');
    }

    public function show(Procedure $procedure)
    {
        return $procedure->load('appointmentEntries.appointment');
    }

    public function edit(Procedure $procedure): Response
    {
        return Inertia::render('procedures/edit', [
            'procedure' => $procedure,
        ]);
    }

    public function update(
        UpdateProcedureRequest $request,
        Procedure $procedure,
        ProcedureService $service
    ): RedirectResponse {
        $service->update(
            $procedure,
            $request->validated()
        );

        return redirect()
            ->route('procedures.index')
            ->with('success', 'Procedure updated successfully.');
    }

    public function destroy(
        Procedure $procedure,
        ProcedureService $service
    ): RedirectResponse {
        $service->delete($procedure);

        return redirect()
            ->route('procedures.index')
            ->with('success', 'Procedure deleted successfully.');
    }
}
