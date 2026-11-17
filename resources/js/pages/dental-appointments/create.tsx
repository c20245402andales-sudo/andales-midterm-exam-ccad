import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, Plus, Save, Trash2 } from 'lucide-react';
import { type FormEvent } from 'react';

interface PatientOption {
    id: number;
    patient_name: string;
}

interface DentistOption {
    id: number;
    dentist_name: string;
}

interface ProcedureOption {
    id: number;
    procedure_name: string;
    procedure_cost: number | string;
}

interface AppointmentFormOptions {
    patients: PatientOption[];
    dentists: DentistOption[];
    procedures: ProcedureOption[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Appointments', href: '/dental-appointments' },
    { title: 'Add Appointment', href: '/dental-appointments/create' },
];

export default function CreateAppointment({ patients, dentists, procedures }: AppointmentFormOptions) {
    const { data, setData, post, processing, errors, transform } = useForm({
        patient_id: '',
        dentist_id: '',
        scheduled_datetime: '',
        payment_status: 'unpaid',
        procedures: [{ procedure_id: '', tooth_number: '', unit_cost: 0 }],
    });
    const procedureErrors = errors as Record<string, string | undefined>;

    function updateProcedure(index: number, procedureId: string) {
        const selected = procedures.find((procedure) => String(procedure.id) === procedureId);
        setData('procedures', data.procedures.map((entry, entryIndex) => entryIndex === index
            ? { ...entry, procedure_id: procedureId, unit_cost: selected ? Number(selected.procedure_cost) : 0 }
            : entry));
    }

    function updateTooth(index: number, toothNumber: string) {
        setData('procedures', data.procedures.map((entry, entryIndex) => entryIndex === index
            ? { ...entry, tooth_number: toothNumber }
            : entry));
    }

    function addProcedure() {
        setData('procedures', [...data.procedures, { procedure_id: '', tooth_number: '', unit_cost: 0 }]);
    }

    function removeProcedure(index: number) {
        if (data.procedures.length > 1) {
            setData('procedures', data.procedures.filter((_, entryIndex) => entryIndex !== index));
        }
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        transform((formData) => ({
            ...formData,
            procedures: formData.procedures.map(({ procedure_id, tooth_number }) => ({
                procedure_id,
                tooth_number,
            })),
        }));
        post('/dental-appointments');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add Appointment" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <div>
                    <Link href="/dental-appointments" className="mb-4 inline-flex items-center gap-2 text-sm text-muted-foreground transition hover:text-foreground">
                        <ArrowLeft size={16} />
                        Back to Appointments
                    </Link>
                    <h1 className="text-2xl font-bold tracking-tight">Add Appointment</h1>
                    <p className="mt-1 text-sm text-muted-foreground">Select the patient, dentist, schedule, and procedures.</p>
                </div>

                <form onSubmit={submit} className="flex flex-col gap-6">
                    <section className="rounded-xl border bg-card p-5 md:p-6">
                        <div className="mb-5 flex items-center gap-3 border-b pb-4">
                            <CalendarDays size={22} className="text-primary" />
                            <h2 className="font-semibold">Appointment Details</h2>
                        </div>
                        <div className="grid gap-5 md:grid-cols-2">
                            <div className="space-y-2">
                                <label htmlFor="patient_id" className="text-sm font-medium">Patient</label>
                                <select id="patient_id" required value={data.patient_id} onChange={(event) => setData('patient_id', event.target.value)} className="w-full rounded-lg border bg-background px-4 py-3 text-sm">
                                    <option value="">Select a patient</option>
                                    {patients.map((patient) => <option key={patient.id} value={patient.id}>{patient.patient_name}</option>)}
                                </select>
                                {errors.patient_id && <p className="text-sm text-red-500">{errors.patient_id}</p>}
                            </div>
                            <div className="space-y-2">
                                <label htmlFor="dentist_id" className="text-sm font-medium">Dentist</label>
                                <select id="dentist_id" required value={data.dentist_id} onChange={(event) => setData('dentist_id', event.target.value)} className="w-full rounded-lg border bg-background px-4 py-3 text-sm">
                                    <option value="">Select a dentist</option>
                                    {dentists.map((dentist) => <option key={dentist.id} value={dentist.id}>{dentist.dentist_name}</option>)}
                                </select>
                                {errors.dentist_id && <p className="text-sm text-red-500">{errors.dentist_id}</p>}
                            </div>
                            <div className="space-y-2">
                                <label htmlFor="scheduled_datetime" className="text-sm font-medium">Date and Time</label>
                                <input id="scheduled_datetime" type="datetime-local" step="1" required value={data.scheduled_datetime} onChange={(event) => setData('scheduled_datetime', event.target.value)} className="w-full rounded-lg border bg-background px-4 py-3 text-sm" />
                                {errors.scheduled_datetime && <p className="text-sm text-red-500">{errors.scheduled_datetime}</p>}
                            </div>
                            <div className="space-y-2">
                                <label htmlFor="payment_status" className="text-sm font-medium">Payment Status</label>
                                <select id="payment_status" required value={data.payment_status} onChange={(event) => setData('payment_status', event.target.value)} className="w-full rounded-lg border bg-background px-4 py-3 text-sm">
                                    <option value="unpaid">Unpaid</option>
                                    <option value="paid">Paid</option>
                                    <option value="partial">Partial</option>
                                </select>
                                {errors.payment_status && <p className="text-sm text-red-500">{errors.payment_status}</p>}
                            </div>
                        </div>
                    </section>

                    <section className="rounded-xl border bg-card p-5 md:p-6">
                        <div className="mb-5 flex flex-col justify-between gap-3 border-b pb-4 sm:flex-row sm:items-center">
                            <div>
                                <h2 className="font-semibold">Procedures</h2>
                                <p className="text-sm text-muted-foreground">Assign a tooth and procedure for each entry.</p>
                            </div>
                            <button type="button" onClick={addProcedure} className="inline-flex items-center justify-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium hover:bg-muted">
                                <Plus size={16} />
                                Add Procedure
                            </button>
                        </div>

                        {errors.procedures && <p className="mb-4 text-sm text-red-500">{errors.procedures}</p>}
                        <div className="space-y-4">
                            {data.procedures.map((entry, index) => (
                                <div key={index} className="grid gap-4 rounded-lg border p-4 md:grid-cols-[minmax(0,1fr)_10rem_9rem_auto] md:items-end">
                                    <div className="space-y-2">
                                        <label htmlFor={`procedure-${index}`} className="text-sm font-medium">Procedure</label>
                                        <select id={`procedure-${index}`} required value={entry.procedure_id} onChange={(event) => updateProcedure(index, event.target.value)} className="w-full rounded-lg border bg-background px-3 py-2.5 text-sm">
                                            <option value="">Select a procedure</option>
                                            {procedures.map((procedure) => <option key={procedure.id} value={procedure.id}>{procedure.procedure_name} · {Number(procedure.procedure_cost).toFixed(2)}</option>)}
                                        </select>
                                        {procedureErrors[`procedures.${index}.procedure_id`] && <p className="text-sm text-red-500">{procedureErrors[`procedures.${index}.procedure_id`]}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <label htmlFor={`tooth-${index}`} className="text-sm font-medium">Tooth</label>
                                        <input id={`tooth-${index}`} type="number" min="1" max="32" step="1" required value={entry.tooth_number} onChange={(event) => updateTooth(index, event.target.value)} className="w-full rounded-lg border bg-background px-3 py-2.5 text-sm" />
                                        {procedureErrors[`procedures.${index}.tooth_number`] && <p className="text-sm text-red-500">{procedureErrors[`procedures.${index}.tooth_number`]}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <span className="block text-sm font-medium">Unit Cost</span>
                                        <p className="rounded-lg bg-muted/50 px-3 py-2.5 text-sm">{Number(entry.unit_cost).toFixed(2)}</p>
                                    </div>
                                    <button type="button" onClick={() => removeProcedure(index)} disabled={data.procedures.length === 1} aria-label={`Remove procedure ${index + 1}`} className="inline-flex h-10 items-center justify-center rounded-lg border px-3 text-red-500 hover:bg-red-500/10 disabled:cursor-not-allowed disabled:opacity-40">
                                        <Trash2 size={17} />
                                    </button>
                                </div>
                            ))}
                        </div>
                    </section>

                    <div className="flex flex-col-reverse justify-end gap-3 sm:flex-row">
                        <Link href="/dental-appointments" className="rounded-lg border px-5 py-2.5 text-center text-sm font-medium transition hover:bg-muted">Cancel</Link>
                        <button type="submit" disabled={processing} className="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50">
                            <Save size={17} />
                            {processing ? 'Saving...' : 'Save Appointment'}
                        </button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}