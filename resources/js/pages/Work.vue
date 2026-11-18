<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import ConnectionList from '@/components/ConnectionList.vue';
import WikiLayout from '@/components/WikiLayout.vue';
import { postJson } from '@/lib/http';
import { MEDIUM_NAME } from '@/lib/taxonomy';
import { CdxButton, CdxMessage, CdxProgressBar } from '@wikimedia/codex';
import type { Connection, WorkSummary } from '@/types';

const props = defineProps<{
    work: WorkSummary & {
        status: string;
        wikidata_id: string | null;
        generated_at: string | null;
    };
    inspiredBy: Connection[];
    inspired: Connection[];
    canGenerate: boolean;
    limitReached: boolean;
    isAdmin: boolean;
}>();

const article = computed(() =>
    /^[aeiou]/i.test(props.work.description ?? '') ? 'an' : 'a',
);

const generating = ref(false);
const failed = ref(false);

async function generate() {
    generating.value = true;
    failed.value = false;

    try {
        await postJson<{ status: string }>(`/work/${props.work.slug}/generate`);
    } catch {
        failed.value = true;
    }

    generating.value = false;
    router.reload();
}

onMounted(() => {
    if (props.canGenerate && props.work.status === 'stub') {
        void generate();
    }
});

function regenerate() {
    generating.value = true;
    router.post(
        `/admin/works/${props.work.slug}/regenerate`,
        {},
        { onFinish: () => (generating.value = false) },
    );
}

// Przypisy: każde unikalne źródło dostaje numer, jak w Wikipedii.
const refs = computed(() => {
    const map: Record<string, number> = {};
    let n = 0;
    for (const item of [...props.inspiredBy, ...props.inspired]) {
        if (item.source_url && !(item.source_url in map)) {
            map[item.source_url] = ++n;
        }
    }
    return map;
});

const refList = computed(() =>
    Object.entries(refs.value).map(([url, n]) => ({
        n,
        url,
        host: url.replace(/^https?:\/\/(www\.)?/, '').split('/')[0],
        title: decodeURIComponent(url.split('/').pop() ?? url).replace(
            /_/g,
            ' ',
        ),
    })),
);

const hasWikidata = computed(() =>
    [...props.inspiredBy, ...props.inspired].some(
        (i) => i.origin === 'wikidata',
    ),
);

const unverified = computed(
    () =>
        [...props.inspiredBy, ...props.inspired].filter((i) => !i.verified)
            .length,
);

const updated = computed(() =>
    props.work.generated_at
        ? new Date(props.work.generated_at).toLocaleDateString('en-GB', {
              day: 'numeric',
              month: 'long',
              year: 'numeric',
          })
        : null,
);
</script>

<template>
    <Head :title="work.title" />
    <WikiLayout :wikidata-id="work.wikidata_id">
        <template #tabs>
            <div class="-mt-3 mb-4 flex border-b border-rule text-sm">
                <span
                    class="-mb-px border-b-2 border-text px-3 py-2 font-semibold"
                >
                    Article
                </span>
                <a
                    v-if="work.wikidata_id"
                    :href="`https://www.wikidata.org/wiki/${work.wikidata_id}`"
                    target="_blank"
                    rel="noopener"
                    class="wiki-link px-3 py-2"
                    >Wikidata</a
                >
                <span class="grow" />
                <button
                    v-if="isAdmin"
                    class="wiki-link px-3 py-2"
                    @click="regenerate"
                >
                    Regenerate
                </button>
            </div>
        </template>

        <h1
            class="border-b border-rule pb-1 font-serif text-[1.9rem] leading-tight"
        >
            {{ work.title }}
        </h1>
        <p class="mt-1 mb-3 text-sm text-muted">
            From Fistful of Ideas, the wiki of influence
        </p>

        <CdxMessage
            v-if="work.status === 'ready' && unverified > 0"
            type="warning"
            class="mb-4"
        >
            {{ unverified }} of the connections in this article were researched
            by AI and <b>have not been verified</b> by an editor. Check the
            cited sources before relying on them.
        </CdxMessage>

        <aside
            class="mb-4 w-full border border-rule bg-soft text-sm md:float-right md:ml-5 md:w-72"
        >
            <div
                class="bg-neutral px-3 py-1.5 text-center font-serif text-base font-semibold"
            >
                <i>{{ work.title }}</i>
            </div>
            <table class="w-full">
                <tbody>
                    <tr v-if="work.medium" class="align-top">
                        <th class="w-24 px-3 py-1 text-left">Medium</th>
                        <td class="px-3 py-1">
                            {{ MEDIUM_NAME[work.medium] ?? work.medium }}
                        </td>
                    </tr>
                    <tr v-if="work.description" class="align-top">
                        <th class="w-24 px-3 py-1 text-left">Type</th>
                        <td class="px-3 py-1">{{ work.description }}</td>
                    </tr>
                    <tr v-if="work.year" class="align-top">
                        <th class="px-3 py-1 text-left">Released</th>
                        <td class="px-3 py-1">{{ work.year }}</td>
                    </tr>
                    <tr class="align-top">
                        <th class="px-3 py-1 text-left">Inspired by</th>
                        <td class="px-3 py-1">
                            <a href="#inspired-by" class="wiki-link"
                                >{{ inspiredBy.length }} works</a
                            >
                        </td>
                    </tr>
                    <tr class="align-top">
                        <th class="px-3 py-1 text-left">Inspired</th>
                        <td class="px-3 py-1">
                            <a href="#inspired" class="wiki-link"
                                >{{ inspired.length }} works</a
                            >
                        </td>
                    </tr>
                    <tr v-if="work.wikidata_id" class="align-top">
                        <th class="px-3 py-1 text-left">Wikidata</th>
                        <td class="px-3 py-1">
                            <a
                                :href="`https://www.wikidata.org/wiki/${work.wikidata_id}`"
                                target="_blank"
                                rel="noopener"
                                class="wiki-link"
                                >{{ work.wikidata_id }}</a
                            >
                        </td>
                    </tr>
                </tbody>
            </table>
        </aside>

        <p class="leading-relaxed">
            <b>{{ work.title }}</b>
            <template v-if="work.description">
                is {{ article }} {{ work.description }}.</template
            >
            <template v-else-if="work.year"> ({{ work.year }}).</template>
            This article traces where its ideas came from and where they went
            next.
        </p>

        <CdxMessage
            v-if="generating || work.status === 'generating'"
            type="notice"
            class="mt-5"
        >
            <p>
                <b>This article is being written.</b> It didn't exist yet, so
                its sources are being researched right now. This takes about
                half a minute.
            </p>
            <CdxProgressBar
                inline
                aria-label="Writing the article"
                class="mt-2"
            />
        </CdxMessage>
        <CdxMessage
            v-else-if="work.status !== 'ready'"
            :type="failed || work.status === 'failed' ? 'error' : 'notice'"
            class="mt-5"
        >
            <template v-if="limitReached">
                <b
                    >Fistful of Ideas does not have an article with this exact
                    name yet.</b
                >
                Today's limit for new articles has been reached; please come
                back tomorrow.
            </template>
            <template v-else>
                <p>
                    <b>{{
                        failed || work.status === 'failed'
                            ? 'Writing this article failed.'
                            : 'Fistful of Ideas does not have an article with this exact name yet.'
                    }}</b>
                </p>
                <CdxButton
                    action="progressive"
                    weight="primary"
                    class="mt-2"
                    @click="generate"
                >
                    Write it now
                </CdxButton>
            </template>
        </CdxMessage>

        <template v-else>
            <div
                class="mt-5 inline-block border border-rule bg-soft px-4 py-2 text-sm"
            >
                <div class="mb-1 text-center font-bold">Contents</div>
                <ol class="list-decimal pl-5">
                    <li>
                        <a href="#inspired-by" class="wiki-link">Inspired by</a>
                    </li>
                    <li><a href="#inspired" class="wiki-link">Inspired</a></li>
                    <li>
                        <a href="#references" class="wiki-link">References</a>
                    </li>
                </ol>
            </div>

            <ConnectionList
                id="inspired-by"
                title="Inspired by"
                direction="by"
                :current-title="work.title"
                :items="inspiredBy"
                :refs="refs"
                :is-admin="isAdmin"
                empty="No documented sources yet."
            />
            <ConnectionList
                id="inspired"
                title="Inspired"
                direction="to"
                :current-title="work.title"
                :items="inspired"
                :refs="refs"
                :is-admin="isAdmin"
                empty="Nothing documented yet."
            />

            <section id="references" class="mt-6 scroll-mt-4">
                <h2
                    class="mb-2 border-b border-rule pb-0.5 font-serif text-[1.6rem]"
                >
                    References
                </h2>
                <ol class="list-decimal space-y-1 pl-6 text-sm">
                    <li
                        v-for="ref in refList"
                        :id="`cite-${ref.n}`"
                        :key="ref.n"
                        class="target:bg-highlight"
                    >
                        <a
                            :href="ref.url"
                            target="_blank"
                            rel="noopener nofollow"
                            class="wiki-link"
                            >"{{ ref.title }}"</a
                        >. <i>{{ ref.host }}</i
                        >.
                    </li>
                </ol>
                <p
                    v-if="hasWikidata"
                    id="cite-wikidata"
                    class="mt-2 text-sm target:bg-highlight"
                >
                    Connections marked <i>[Wikidata]</i> come from the "inspired
                    by" and "based on" statements on
                    <a
                        v-if="work.wikidata_id"
                        :href="`https://www.wikidata.org/wiki/${work.wikidata_id}`"
                        target="_blank"
                        rel="noopener"
                        class="wiki-link"
                        >Wikidata</a
                    ><template v-else>Wikidata</template>.
                </p>
                <p
                    v-if="refList.length === 0 && !hasWikidata"
                    class="text-sm text-muted italic"
                >
                    No references yet.
                </p>
            </section>
        </template>

        <template #footer>
            <p v-if="updated">
                This article was last written on {{ updated }}.
            </p>
        </template>
    </WikiLayout>
</template>
