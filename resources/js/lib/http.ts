function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export async function postJson<T>(url: string): Promise<T> {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-XSRF-TOKEN': xsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }

    return (await response.json()) as T;
}

/** Notatki zapisują tytuły w *gwiazdkach*: dzielimy je na kawałki do kursywy. */
export function noteParts(note: string): { text: string; em: boolean }[] {
    return note
        .split(/(\*[^*]+\*)/)
        .filter((part) => part !== '')
        .map((part) =>
            part.startsWith('*') && part.endsWith('*') && part.length > 2
                ? { text: part.slice(1, -1), em: true }
                : { text: part, em: false },
        );
}
