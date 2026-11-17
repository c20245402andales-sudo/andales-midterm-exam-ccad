
import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import {
    Search,
    Plus,
    Stethoscope,
    Pencil,
    Trash2,
    Eye,
    Users,
} from 'lucide-react';
import { type FormEvent } from 'react';

interface Dentist {
    id: number;
    dentist_name: string;
    dentist_license: string;
    appointments_count: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedDentists {
    data: Dentist[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
}

interface Props {
    dentists: PaginatedDentists;
    search: string;
    flash: { success?: string | null };
    errors?: Record<string, string>;
}

export default function DentistIndex({
    dentists,
    search: initialSearch,
    flash,
    errors = {},
}: Props) {
    const [search, setSearch] = useState(initialSearch);

    function submitSearch(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        router.get('/dentists', { search }, { preserveState: true, preserveScroll: true, replace: true });
    }

    function paginationLabel(label: string): string {
        return label.replace('&laquo;', '‹').replace('&raquo;', '›');
    }

    const deleteDentist = (id: number) => {
        if (confirm('Are you sure you want to delete this dentist?')) {
            router.delete(`/dentists/${id}`);
        }
    };

    return (
        <AppLayout>
            <Head title="Dentists" />

            <div className="flex flex-col gap-6 p-4 md:p-8">
                {errors.dentist && (
                    <p role="alert" className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {errors.dentist}
                    </p>
                )}
                {flash.success && (
                    <p role="status" className="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </p>
                )}

                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Dentists
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Manage your dental clinic's dentists and their information.
                        </p>
                    </div>

                    <Link
                        href="/dentists/create"
                        className="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground transition hover:opacity-90"
                    >
                        <Plus size={18} />
                        Add Dentist
                    </Link>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="rounded-xl border bg-card p-5">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Total Dentists
                                </p>
                                <h2 className="mt-2 text-3xl font-bold">
                                    {dentists.total}
                                </h2>
                            </div>

                            <div className="rounded-lg bg-primary/10 p-3 text-primary">
                                <Stethoscope size={24} />
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border bg-card p-5">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Dentists on This Page
                                </p>
                                <h2 className="mt-2 text-3xl font-bold">
                                    {dentists.data.length}
                                </h2>
                            </div>

                            <div className="rounded-lg bg-primary/10 p-3 text-primary">
                                <Users size={24} />
                            </div>
                        </div>
                    </div>
                </div>

                <div className="rounded-xl border bg-card">
                    <div className="flex flex-col gap-4 border-b p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 className="font-semibold">Dentist Records</h2>
                            <p className="text-sm text-muted-foreground">
                                View and manage registered dentists.
                            </p>
                        </div>

                        <form onSubmit={submitSearch} className="flex w-full gap-2 sm:max-w-xs">
                            <input
                                type="search"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search dentist..."
                                aria-label="Search dentists"
                                className="min-w-0 flex-1 rounded-lg border bg-background px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-primary"
                            />
                            <button type="submit" aria-label="Search" className="rounded-lg border px-3 text-muted-foreground hover:bg-muted">
                                <Search size={18} />
                            </button>
                        </form>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted/50 text-muted-foreground">
                                <tr>
                                    <th className="px-5 py-4 font-medium">#</th>
                                    <th className="px-5 py-4 font-medium">Dentist Name</th>
                                    <th className="px-5 py-4 font-medium">License Number</th>
                                    <th className="px-5 py-4 font-medium">Appointments</th>
                                    <th className="px-5 py-4 text-right font-medium">Actions</th>
                                </tr>
                            </thead>

                            <tbody className="divide-y">
                                {dentists.data.length > 0 ? (
                                    dentists.data.map((dentist, index) => (
                                        <tr
                                            key={dentist.id}
                                            className="transition hover:bg-muted/30"
                                        >
                                            <td className="px-5 py-4 text-muted-foreground">
                                                {(dentists.current_page - 1) * 10 + index + 1}
                                            </td>

                                            <td className="px-5 py-4 font-medium">
                                                {dentist.dentist_name}
                                            </td>

                                            <td className="px-5 py-4">
                                                {dentist.dentist_license}
                                            </td>

                                            <td className="px-5 py-4">
                                                <span className="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                                                    {dentist.appointments_count}
                                                </span>
                                            </td>

                                            <td className="px-5 py-4">
                                                <div className="flex justify-end gap-2">
                                                    <Link
                                                        href={`/dentists/${dentist.id}`}
                                                        title="View dentist"
                                                        aria-label={`View ${dentist.dentist_name}`}
                                                        className="rounded-md p-2 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                                                    >
                                                        <Eye size={17} />
                                                    </Link>

                                                    <Link
                                                        href={`/dentists/${dentist.id}/edit`}
                                                        title="Edit dentist"
                                                        aria-label={`Edit ${dentist.dentist_name}`}
                                                        className="rounded-md p-2 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                                                    >
                                                        <Pencil size={17} />
                                                    </Link>

                                                    <button
                                                        type="button"
                                                        onClick={() => deleteDentist(dentist.id)}
                                                        title="Delete dentist"
                                                        aria-label={`Delete ${dentist.dentist_name}`}
                                                        className="rounded-md p-2 text-red-500 transition hover:bg-red-500/10"
                                                    >
                                                        <Trash2 size={17} />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-5 py-12 text-center text-muted-foreground"
                                        >
                                            {initialSearch
                                                ? 'No dentists match your search.'
                                                : 'No dentists registered yet. Click Add Dentist to get started.'}
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="flex flex-col gap-3 border-t p-5 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {dentists.data.length} of {dentists.total}{initialSearch ? ' matching' : ''} dentists
                        </p>

                        <div className="flex flex-wrap gap-1">
                            {dentists.links.map((link, index) => (
                                <button
                                    key={index}
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
