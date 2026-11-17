import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { CalendarDays, Pencil, Plus, Trash2 } from 'lucide-react';

interface AppointmentEntry {
    id: number;
    tooth_number: number;
    unit_cost: number | string;
    procedure?: {
        procedure_name: string;
    } | null;
}

interface Appointment {
    id: number;
    scheduled_datetime: string;
    payment_status: 'unpaid' | 'paid' | 'partial' | string;
    patient?: { patient_name: string } | null;
    dentist?: { dentist_name: string } | null;
    procedure_entries: AppointmentEntry[];
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface AppointmentsPageProps {
    appointments: {
        data: Appointment[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
        total: number;
    };
    flash: { success?: string | null };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Appointments', href: '/dental-appointments' },
];

function formatCost(value: number | string): string {
    return Number(value).toFixed(2);
}

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString();
}

function paginationLabel(label: string): string {
    return label.replace('&laquo;', '‹').replace('&raquo;', '›');
}

function appointmentTotal(entries: AppointmentEntry[]): number {
    return entries.reduce((total, entry) => total + Number(entry.unit_cost), 0);
}

export default function AppointmentsIndex({ appointments, flash }: AppointmentsPageProps) {
    function deleteAppointment(appointment: Appointment) {
        const patientName = appointment.patient?.patient_name ?? 'this appointment';

        if (confirm(`Are you sure you want to delete the appointment for ${patientName}?`)) {
            router.delete(`/dental-appointments/${appointment.id}`);
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Appointments" />

            <div className="flex flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Appointments</h1>
                        <p className="text-muted-foreground mt-1 text-sm">Schedule and manage dental clinic appointments.</p>
                    </div>
                    <Link
                        href="/dental-appointments/create"
                        className="bg-primary text-primary-foreground inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition hover:opacity-90"
                    >
                        <Plus size={18} />
                        Add Appointment
                    </Link>
                </div>

                {flash.success && (
                    <p role="status" className="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </p>
                )}

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="bg-card rounded-xl border p-5">
                        <p className="text-muted-foreground text-sm">Total Appointments</p>
                        <h2 className="mt-2 text-3xl font-bold">{appointments.total}</h2>
                    </div>
                    <div className="bg-card rounded-xl border p-5">
                        <p className="text-muted-foreground text-sm">Appointments on This Page</p>
                        <h2 className="mt-2 text-3xl font-bold">{appointments.data.length}</h2>
                    </div>
                </div>

                <div className="bg-card overflow-hidden rounded-xl border">
                    <div className="border-b p-5">
                        <h2 className="font-semibold">Appointment Records</h2>
                        <p className="text-muted-foreground text-sm">Procedure costs are shown from each appointment's saved unit cost.</p>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted/50 text-muted-foreground">
                                <tr>
                                    <th className="px-5 py-4 font-medium">Patient</th>
                                    <th className="px-5 py-4 font-medium">Dentist</th>
                                    <th className="px-5 py-4 font-medium">Scheduled</th>
                                    <th className="px-5 py-4 font-medium">Procedures</th>
                                    <th className="px-5 py-4 font-medium">Total</th>
                                    <th className="px-5 py-4 font-medium">Payment</th>
                                    <th className="px-5 py-4 text-right font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {appointments.data.length > 0 ? (
                                    appointments.data.map((appointment) => (
                                        <tr key={appointment.id} className="hover:bg-muted/30 transition">
                                            <td className="px-5 py-4 font-medium">{appointment.patient?.patient_name ?? 'Unknown patient'}</td>
                                            <td className="px-5 py-4">{appointment.dentist?.dentist_name ?? 'Unknown dentist'}</td>
                                            <td className="px-5 py-4 whitespace-nowrap">{formatDateTime(appointment.scheduled_datetime)}</td>
                                            <td className="min-w-48 px-5 py-4">
                                                {appointment.procedure_entries.length > 0 ? (
                                                    <ul className="space-y-1">
                                                        {appointment.procedure_entries.map((entry) => (
                                                            <li key={entry.id}>
                                                                {entry.procedure?.procedure_name ?? 'Procedure'}
                                                                {' · Tooth '}
                                                                {entry.tooth_number}
                                                                {' · '}
                                                                {formatCost(entry.unit_cost)}
                                                            </li>
                                                        ))}
                                                    </ul>
                                                ) : (
                                                    'No procedures'
                                                )}
                                            </td>
                                            <td className="px-5 py-4 font-medium whitespace-nowrap">
                                                {formatCost(appointmentTotal(appointment.procedure_entries))}
                                            </td>
                                            <td className="px-5 py-4">
                                                <span
                                                    className={`rounded-full px-3 py-1 text-xs font-medium capitalize ${
                                                        appointment.payment_status === 'paid'
                                                            ? 'bg-green-100 text-green-700'
                                                            : appointment.payment_status === 'partial'
                                                              ? 'bg-amber-100 text-amber-800'
                                                              : 'bg-muted text-muted-foreground'
                                                    }`}
                                                >
                                                    {appointment.payment_status}
                                                </span>
                                            </td>
                                            <td className="px-5 py-4">
                                                <div className="flex justify-end gap-2">
                                                    <Link
                                                        href={`/dental-appointments/${appointment.id}/edit`}
                                                        title="Edit appointment"
                                                        aria-label={`Edit appointment ${appointment.id}`}
                                                        className="text-muted-foreground hover:bg-muted hover:text-foreground rounded-md p-2 transition"
                                                    >
                                                        <Pencil size={17} />
                                                    </Link>
                                                    <button
                                                        type="button"
                                                        onClick={() => deleteAppointment(appointment)}
                                                        title="Delete appointment"
                                                        aria-label={`Delete appointment ${appointment.id}`}
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
                                        <td colSpan={7} className="text-muted-foreground px-5 py-12 text-center">
                                            <CalendarDays size={32} className="mx-auto mb-3" />
                                            No appointments have been scheduled yet.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="flex flex-col gap-3 border-t p-5 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-muted-foreground text-sm">
                            Showing {appointments.data.length} of {appointments.total} appointments
                        </p>
                        <div className="flex flex-wrap gap-1">
                            {appointments.links.map((link, index) => (
                                <button
                                    key={`${link.label}-${index}`}
                                    type="button"
                                    disabled={!link.url}
                                    onClick={() => link.url && router.visit(link.url)}
                                    className={`rounded-md border px-3 py-2 text-xs ${
                                        link.active ? 'border-primary bg-primary text-primary-foreground' : 'bg-background hover:bg-muted'
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
