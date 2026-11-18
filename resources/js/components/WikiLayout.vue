<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import SearchBox from '@/components/SearchBox.vue';

defineProps<{ wikidataId?: string | null }>();

const page = usePage();
</script>

<template>
    <div class="min-h-screen bg-soft">
        <div class="mx-auto flex max-w-[1400px]">
            <!-- Lewa kolumna: logo i nawigacja, jak w MediaWiki -->
            <aside class="hidden w-44 shrink-0 px-4 pt-4 text-sm lg:block">
                <Link href="/" class="mb-6 block text-center no-underline">
                    <svg
                        viewBox="0 0 64 64"
                        class="mx-auto h-16 w-16"
                        aria-hidden="true"
                    >
                        <circle
                            cx="32"
                            cy="32"
                            r="30"
                            fill="#fff"
                            stroke="#a2a9b1"
                            stroke-width="2"
                        />
                        <path
                            d="M18 44 L32 20 L46 44"
                            fill="none"
                            stroke="#202122"
                            stroke-width="3"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                        <circle cx="32" cy="20" r="4" fill="#3366cc" />
                        <circle cx="18" cy="44" r="4" fill="#202122" />
                        <circle cx="46" cy="44" r="4" fill="#202122" />
                    </svg>
                    <div
                        class="mt-1 font-serif text-lg leading-tight text-text"
                    >
                        Fistful of Ideas
                    </div>
                    <div class="text-xs text-muted">the wiki of influence</div>
                </Link>

                <nav class="space-y-4">
                    <div>
                        <h3
                            class="mb-1 border-b border-rule pb-0.5 text-xs text-muted"
                        >
                            Navigation
                        </h3>
                        <ul class="space-y-1">
                            <li>
                                <Link href="/" class="wiki-link"
                                    >Main page</Link
                                >
                            </li>
                            <li>
                                <Link href="/random" class="wiki-link"
                                    >Random article</Link
                                >
                            </li>
                            <li>
                                <Link href="/recent" class="wiki-link"
                                    >Recent changes</Link
                                >
                            </li>
                            <li>
                                <Link href="/about" class="wiki-link"
                                    >About</Link
                                >
                            </li>
                        </ul>
                    </div>
                    <div v-if="wikidataId || page.props.isAdmin">
                        <h3
                            class="mb-1 border-b border-rule pb-0.5 text-xs text-muted"
                        >
                            Tools
                        </h3>
                        <ul class="space-y-1">
                            <li v-if="wikidataId">
                                <a
                                    :href="`https://www.wikidata.org/wiki/${wikidataId}`"
                                    target="_blank"
                                    rel="noopener"
                                    class="wiki-link"
                                    >Wikidata item</a
                                >
                            </li>
                            <li v-if="page.props.isAdmin">
                                <Link href="/admin" class="wiki-link"
                                    >Review queue</Link
                                >
                            </li>
                        </ul>
                    </div>
                </nav>
            </aside>

            <div class="min-w-0 flex-1">
                <!-- Górny pasek: logo na wąskich ekranach + wyszukiwarka -->
                <header
                    class="flex items-center gap-4 px-4 py-3 lg:justify-end"
                >
                    <Link
                        href="/"
                        class="font-serif text-xl text-text no-underline lg:hidden"
                    >
                        Fistful of Ideas
                    </Link>
                    <div class="ml-auto w-full max-w-md lg:ml-0">
                        <SearchBox />
                    </div>
                </header>

                <div
                    class="border border-rule bg-page px-5 pt-3 pb-10 sm:px-8 lg:mr-4 lg:border-r-0"
                >
                    <slot name="tabs" />
                    <slot />
                </div>

                <footer class="px-4 py-6 text-xs text-muted sm:px-8">
                    <slot name="footer" />
                    <p class="mt-2">
                        Connections come from
                        <a href="https://www.wikidata.org" class="wiki-link"
                            >Wikidata</a
                        >
                        (CC0) and from AI research with cited sources. Items
                        marked <i>[unverified]</i> haven't been checked by a
                        human yet.
                    </p>
                    <p class="mt-2 space-x-3">
                        <Link href="/about" class="wiki-link"
                            >About Fistful of Ideas</Link
                        >
                        <Link
                            v-if="!page.props.isAdmin"
                            href="/admin/login"
                            class="wiki-link"
                            >Editor login</Link
                        >
                    </p>
                </footer>
            </div>
        </div>
    </div>
</template>
