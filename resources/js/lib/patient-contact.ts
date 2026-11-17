export const PHILIPPINE_MOBILE_NUMBER_PATTERN = /^09[0-9]{9}$/;

export const PHILIPPINE_MOBILE_NUMBER_MESSAGE =
    'Enter an 11-digit Philippine mobile number starting with 09.';

export function normalizePatientContactInput(value: string): string {
    return value.replace(/[^0-9]/g, '').slice(0, 11);
}
