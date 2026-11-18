export const MEDIA: Record<string, string> = {
    book: 'Books',
    film: 'Films',
    tv: 'TV',
    game: 'Games',
    comic: 'Comics',
    music: 'Music',
    art: 'Art',
    theatre: 'Theatre',
    myth: 'Myth and scripture',
    other: 'Other',
};

export const MEDIUM_TAG: Record<string, string> = {
    book: 'book',
    film: 'film',
    tv: 'TV',
    game: 'game',
    comic: 'comic',
    music: 'music',
    art: 'art',
    theatre: 'theatre',
    myth: 'myth',
    other: 'other',
};

export const KINDS = [
    'adaptation',
    'remake',
    'story',
    'style',
    'reference',
    'parody',
    'background',
] as const;

/** Nazwy podsekcji zależą od kierunku, żeby czytały się naturalnie z obu stron. */
export const KIND_LABELS: Record<'by' | 'to', Record<string, string>> = {
    by: {
        adaptation: 'Adapted from',
        remake: 'Remake or continuation of',
        story: 'Story and structure',
        style: 'Look and feel',
        reference: 'Title and references',
        parody: 'Parody of',
        background: 'Background',
        other: 'Other influences',
    },
    to: {
        adaptation: 'Adapted as',
        remake: 'Remakes and continuations',
        story: 'Borrowed its story',
        style: 'Borrowed its look and feel',
        reference: 'Referenced in',
        parody: 'Parodied in',
        background: 'Used it as background',
        other: 'Other works it inspired',
    },
};

/** Porównanie tytułów "po ludzku": bez wielkości liter, interpunkcji i początkowego "The". */
export function sameTitle(a: string, b: string): boolean {
    const norm = (s: string) =>
        s
            .toLowerCase()
            .replace(/[^\p{L}\p{N} ]+/gu, ' ')
            .replace(/^\s*(the|a|an)\s+/, '')
            .replace(/\s+/g, ' ')
            .trim();

    return norm(a) === norm(b);
}

export const MEDIUM_NAME: Record<string, string> = {
    book: 'Literature',
    film: 'Film',
    tv: 'Television',
    game: 'Video game',
    comic: 'Comics',
    music: 'Music',
    art: 'Visual art',
    theatre: 'Theatre',
    myth: 'Myth or scripture',
    other: 'Other',
};
