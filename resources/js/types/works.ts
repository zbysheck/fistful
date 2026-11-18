export type WorkSummary = {
    slug: string;
    title: string;
    year: number | null;
    description: string | null;
    medium: string | null;
};

export type Connection = {
    id: number;
    kind: string | null;
    work: WorkSummary;
    note: string | null;
    source_url: string | null;
    origin: 'wikidata' | 'ai' | 'manual';
    verified: boolean;
    note_ok: boolean;
};

export type SearchResult = {
    title: string;
    description: string | null;
    url: string;
};
