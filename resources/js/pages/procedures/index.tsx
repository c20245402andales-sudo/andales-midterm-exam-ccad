import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ClipboardList, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface Procedure {
    id: number;
    procedure_name: string;
    procedure_cost: number | string;
    appointment_entries_count: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface ProceduresPageProps {
    procedures: {
        data: Procedure[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
        total: number;
    };
    flash: {
        success?: string | null;
    };
    search: string;
    errors?: Record<string, string>;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Procedures', href: '/procedures' },
];

export default function ProceduresIndex({ procedures, search: initialSearch, flash, errors = {} }: ProceduresPageProps) {
    const [search, setSearch] = useState(initialSearch);

    function submitSearch(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();
        router.get('/procedures', { search }, { preserveState: true, preserveScroll: true, replace: true });
    }

    function paginationLabel(label: string): string {
        return label.replace('&laquo;', '‹').replace('&raquo;', '›');
    }

    function deleteProcedure(procedure: Procedure) {
        if (confirm(`Are you sure you want to delete ${procedure.procedure_name}?`)) {
            router.delete(`/procedures/${procedure.id}`);
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Procedures" />

            <div className="flex flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Procedures</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Manage clinic procedures and their costs.
                        </p>
                    </div>

                    <Link
                        href="/procedures/create"
                        className="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground transition hover:opacity-90"
                    >
                        <Plus size={18} />
                        Add Procedure
                    </Link>
                </div>

                {flash.success && (
                    <p role="status" className="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </p>
                )}

                {errors.procedure && (
                    <p role="alert" className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {errors.procedure}
                    </p>
                )}

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="rounded-xl border bg-card p-5">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-muted-foreground">Total Procedures</p>
                                <h2 className="mt-2 text-3xl font-bold">{procedures.total}</h2>
                            </div>
                            <ClipboardList size={24} className="text-primary" />
                        </div>
                    </div>

                    <div className="rounded-xl border bg-card p-5">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-muted-foreground">Procedures on This Page</p>
                                <h2 className="mt-2 text-3xl font-bold">{procedures.data.length}</h2>
                            </div>
                            <ClipboardList size={24} className="text-primary" />
                        </div>
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border bg-card">
                    <div className="flex flex-col gap-4 border-b p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 className="font-semibold">Procedure Records</h2>
                            <p className="text-sm text-muted-foreground">View and manage clinic procedures.</p>
                        </div>

                        <form onSubmit={submitSearch} className="flex w-full gap-2 sm:max-w-xs">
                            <input
                                type="search"
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Search procedures..."
                                aria-label="Search procedures"
                                className="min-w-0 flex-1 rounded-lg border bg-background px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-primary"
                            />
                            <button
                                type="submit"
                                aria-label="Search"
                                className="inline-flex items-center justify-center rounded-lg border px-3 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                            >
                                <Search size={18} />
                            </button>
                        </form>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted/50 text-muted-foreground">
                                <tr>
                                    <th className="px-5 py-4 font-medium">Procedure Name</th>
                                    <th className="px-5 py-4 font-medium">Cost</th>
                                    <th className="px-5 py-4 font-medium">Procedure Entries</th>
                                    <th className="px-5 py-4 text-right font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {procedures.data.length > 0 ? procedures.data.map((procedure) => (
                                    <tr key={procedure.id} className="transition hover:bg-muted/30">
                                        <td className="px-5 py-4 font-medium">{procedure.procedure_name}</td>
                                        <td className="px-5 py-4">{Number(procedure.procedure_cost).toFixed(2)}</td>
                                        <td className="px-5 py-4">{procedure.appointment_entries_count}</td>
                                        <td className="px-5 py-4">
                                            <div className="flex justify-end gap-2">
                                                <Link
                                                    href={`/procedures/${procedure.id}/edit`}
                                                    title="Edit procedure"
                                                    aria-label={`Edit ${procedure.procedure_name}`}
                                                    className="rounded-md p-2 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                                                >
                                                    <Pencil size={17} />
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() => deleteProcedure(procedure)}
                                                    title="Delete procedure"
                                                    aria-label={`Delete ${procedure.procedure_name}`}
                                                    className="rounded-md p-2 text-red-500 transition hover:bg-red-500/10"
                                                >
                                                    <Trash2 size={17} />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                )) : (
                                    <tr>
                                        <td colSpan={4} className="px-5 py-12 text-center text-muted-foreground">
                                            {initialSearch ? 'No procedures match your search.' : 'No procedures have been added yet.'}
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="flex flex-col gap-3 border-t p-5 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {procedures.data.length} of {procedures.total}{initialSearch ? ' matching' : ''} procedures
                        </p>
                        <div className="flex flex-wrap gap-1">
                            {procedures.links.map((link, index) => (
                                <button
                                    key={`${link.label}-${index}`}
                                    type="button"
                                    disabled={!link.url}
                                    onClick={() => link.url && router.visit(link.url)}
                                    className={`rounded-md border px-3 py-2 text-xs ${
                                        link.active
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'bg-background hover:bg-muted'
                                    } disabled:cursor-not-allowed disabled:opacity-40`}
                                >
                                    {paginationLabel(link.label)}
                                </button>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}