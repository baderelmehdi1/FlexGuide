/**
 * Client-side convenience only -- a starting point an admin can edit or
 * accept as-is when provisioning an account. Real validation (min length)
 * happens server-side regardless.
 */
export function generatePassword(length = 14) {
    const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    const bytes = new Uint32Array(length);
    crypto.getRandomValues(bytes);

    return Array.from(bytes, (byte) => alphabet[byte % alphabet.length]).join('');
}
