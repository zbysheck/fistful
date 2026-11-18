<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import ConnectionList from '@/components/ConnectionList.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { postJson } from '@/lib/http';
import type { Connection, WorkSummary } from '@/types';

const props = defineProps<{
    work: WorkSummary & { status: string; wikidata_id: string | null };
    inspiredBy: Connection[];
    inspired: Connection[];
    canGenerate: boolean;
    limitReached: boolean;
    isAdmin: boolean;
}>();

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
</script>

<template>
    <Head :title="work.title" />
    <SiteHeader />
    <main class="mx-auto max-w-4xl px-5 py-10">
        <h1 class="font-display text-4xl leading-tight sm:text-5xl">
            {{ work.title }}
            <span v-if="work.year" class="text-ink/50">({{ work.year }})</span>
        </h1>
        <p v-if="work.description" class="mt-2 text-lg text-ink/70">
            {{ work.description }}
        </p>

        <div
            v-if="generating || work.status === 'generating'"
            class="mt-10 border-2 border-dashed border-ink/40 p-8 text-center"
        >
            <p class="font-logo text-2xl">Riding out to find its sources…</p>
            <p class="mt-2 text-ink/60">
                This page doesn't exist yet. We're writing it now, it takes
                about half a minute.
            </p>
        </div>
        <div
            v-else-if="work.status !== 'ready'"
            class="mt-10 border-2 border-dashed border-ink/40 p-8 text-center"
        >
            <p v-if="limitReached" class="text-ink/70">
                This page hasn't been written yet and we've hit today's limit.
                Come back tomorrow.
            </p>
            <template v-else>
                <p class="text-ink/70">
                    {{
                        failed || work.status === 'failed'
                            ? 'Writing this page failed.'
                            : "This page hasn't been written yet."
                    }}
                </p>
                <button
                    class="mt-4 border-2 border-ink bg-ink px-5 py-2 text-paper hover:bg-blood"
                    @click="generate"
                >
                    Write it now
                </button>
            </template>
        </div>

        <div v-else class="mt-10 grid gap-12 md:grid-cols-2">
            <ConnectionList
                title="Inspired by"
                :items="inspiredBy"
                :is-admin="isAdmin"
                empty="No documented sources yet."
            />
            <ConnectionList
                title="Inspired"
                :items="inspired"
                :is-admin="isAdmin"
                empty="Nothing documented yet."
            />
        </div>

        <footer
            class="mt-16 flex flex-wrap gap-4 border-t border-ink/20 pt-4 text-xs text-ink/50"
        >
            <a
                v-if="work.wikidata_id"
                :href="`https://www.wikidata.org/wiki/${work.wikidata_id}`"
                target="_blank"
                rel="noopener"
                class="underline"
                >Wikidata {{ work.wikidata_id }}</a
            >
            <template v-if="isAdmin">
                <button class="underline" @click="regenerate">
                    Regenerate with AI
                </button>
                <Link href="/admin" class="underline">Review queue</Link>
            </template>
        </footer>
    </main>
</template>
