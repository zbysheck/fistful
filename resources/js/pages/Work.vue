<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
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
</script>

<template>
    <Head :title="work.title" />
    <SiteHeader />
    <main class="mx-auto max-w-6xl px-5 py-6">
        <h1 class="border-b border-rule pb-1 font-serif text-3xl sm:text-4xl">
            {{ work.title }}
        </h1>
        <p class="mt-1 text-sm text-muted">
            From Fistful of Ideas, the wiki of influence
        </p>

        <div class="mt-4 flex flex-col-reverse gap-6 md:flex-row">
            <div class="min-w-0 flex-1">
                <p class="leading-relaxed">
                    <b>{{ work.title }}</b>
                    <template v-if="work.description">
                        is {{ article }} {{ work.description }}.</template
                    >
                    <template v-else-if="work.year">
                        ({{ work.year }}).</template
                    >
                    This article lists the works it drew on and the works that
                    drew on it.
                </p>

                <div
                    v-if="generating || work.status === 'generating'"
                    class="mt-6 border border-l-8 border-notice-edge bg-notice px-4 py-3"
                >
                    <b>This article is being written.</b>
                    It doesn't exist yet, so we're researching its sources right
                    now. This takes about half a minute.
                </div>
                <div
                    v-else-if="work.status !== 'ready'"
                    class="mt-6 border border-l-8 border-notice-edge bg-notice px-4 py-3"
                >
                    <template v-if="limitReached">
                        <b>This article doesn't exist yet.</b> Today's limit for
                        new articles has been reached, please come back
                        tomorrow.
                    </template>
                    <template v-else>
                        <b>{{
                            failed || work.status === 'failed'
                                ? 'Writing this article failed.'
                                : "This article doesn't exist yet."
                        }}</b>
                        <button class="wiki-link ml-1" @click="generate">
                            Write it now
                        </button>
                    </template>
                </div>

                <template v-else>
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
                </template>

                <div
                    v-if="isAdmin"
                    class="mt-10 flex gap-4 border-t border-rule pt-3 text-sm"
                >
                    <button class="wiki-link" @click="regenerate">
                        Regenerate with AI
                    </button>
                    <Link href="/admin" class="wiki-link">Review queue</Link>
                </div>
            </div>

            <aside
                class="w-full shrink-0 self-start border border-rule bg-soft text-sm md:w-72"
            >
                <div
                    class="px-3 py-2 text-center font-serif text-lg font-semibold"
                >
                    {{ work.title }}
                </div>
                <table class="w-full">
                    <tbody>
                        <tr v-if="work.year" class="border-t border-rule">
                            <th class="px-3 py-1.5 text-left align-top">
                                Released
                            </th>
                            <td class="px-3 py-1.5">{{ work.year }}</td>
                        </tr>
                        <tr
                            v-if="work.description"
                            class="border-t border-rule"
                        >
                            <th class="px-3 py-1.5 text-left align-top">
                                Type
                            </th>
                            <td class="px-3 py-1.5">{{ work.description }}</td>
                        </tr>
                        <tr class="border-t border-rule">
                            <th class="px-3 py-1.5 text-left align-top">
                                Inspired by
                            </th>
                            <td class="px-3 py-1.5">
                                {{ inspiredBy.length }} works
                            </td>
                        </tr>
                        <tr class="border-t border-rule">
                            <th class="px-3 py-1.5 text-left align-top">
                                Inspired
                            </th>
                            <td class="px-3 py-1.5">
                                {{ inspired.length }} works
                            </td>
                        </tr>
                        <tr
                            v-if="work.wikidata_id"
                            class="border-t border-rule"
                        >
                            <th class="px-3 py-1.5 text-left align-top">
                                Wikidata
                            </th>
                            <td class="px-3 py-1.5">
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
        </div>
    </main>
</template>
