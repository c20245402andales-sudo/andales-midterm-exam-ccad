import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ClipboardList, Save } from 'lucide-react';
import { type FormEvent } from 'react';

interface Procedure {
    id: number;
    procedure_name: string;
    procedure_cost: number | string;
}

interface EditProcedureProps {
    procedure: Procedure;
}

export default function EditProcedure({ procedure }: EditProcedureProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Procedures', href: '/procedures' },
        { title: 'Edit Procedure', href: `/procedures/${procedure.id}/edit` },
    ];

    const { data, setData, put, processing, errors } = useForm({
        procedure_name: procedure.procedure_name,
        procedure_cost: String(procedure.procedure_cost),
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        put(`/procedures/${procedure.id}`);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Procedure" />

            <div className="flex flex-col gap-6 p-4 md:p-8">
                <div>
                    <Link href="/procedures" className="mb-4 inline-flex items-center gap-2 text-sm text-muted-foreground transition hover:text-foreground">
                        <ArrowLeft size={16} />
                        Back to Procedures
                    </Link>
                    <h1 className="text-2xl font-bold tracking-tight">Edit Procedure</h1>
                    <p className="mt-1 text-sm text-muted-foreground">Update the procedure name or cost.</p>
                </div>

                <div className="w-full max-w-3xl rounded-xl border bg-card">
                    <div className="flex items-center gap-3 border-b p-5">
                        <div className="rounded-lg bg-primary/10 p-3 text-primary">
                            <ClipboardList size={22} />
                        </div>
                        <div>
                            <h2 className="font-semibold">Procedure Information</h2>
                            <p className="text-sm text-muted-foreground">Procedure ID: {procedure.id}</p>
                        </div>
                    </div>

                    <form onSubmit={submit} className="space-y-5 p-5">
                        <div className="space-y-2">
                            <label htmlFor="procedure_name" className="text-sm font-medium">Procedure Name</label>
                            <input
                                id="procedure_name"
                                type="text"
                                maxLength={150}
                                required
                                value={data.procedure_name}
                                onChange={(event) => setData('procedure_name', event.target.value)}
                                className="w-full rounded-lg border bg-background px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-primary"
                            />
                            {errors.procedure_name && <p className="text-sm text-red-500">{errors.procedure_name}</p>}
                        </div>

                        <div className="space-y-2">
                            <label htmlFor="procedure_cost" className="text-sm font-medium">Cost</label>
                            <input
                                id="procedure_cost"
                                type="number"
                                inputMode="decimal"
                                min="0"
                                step="0.01"
                                required
                                value={data.procedure_cost}
                                onChange={(event) => setData('procedure_cost', event.target.value)}
                                className="w-full rounded-lg border bg-background px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-primary"
                            />
                            {errors.procedure_cost && <p className="text-sm text-red-500">{errors.procedure_cost}</p>}
                        </div>

                        <div className="flex flex-col-reverse justify-end gap-3 border-t pt-5 sm:flex-row">
                            <Link href="/procedures" className="rounded-lg border px-5 py-2.5 text-center text-sm font-medium transition hover:bg-muted">
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Save size={17} />
                                {processing ? 'Updating...' : 'Update Procedure'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}