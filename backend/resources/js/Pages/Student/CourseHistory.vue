<script setup>
import { ref, computed } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";

const props = defineProps({
    history:        { type: Object, required: true },
    stats:          { type: Object, required: true },
    relatedCourses: { type: Array,  default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth?.user ?? {});
const viewMode = ref("list");

// ── Status config (single source of truth) ──────────────────────────────────
const STATUS = {
    admitted:  { badge: "bg-blue-50 text-blue-700 ring-1 ring-blue-200",    dot: "bg-blue-500",    label: "Admitted"  },
    confirmed: { badge: "bg-violet-50 text-violet-700 ring-1 ring-violet-200", dot: "bg-violet-500", label: "Confirmed" },
    completed: { badge: "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200", dot: "bg-emerald-500", label: "Completed" },
    revoked:   { badge: "bg-red-50 text-red-700 ring-1 ring-red-200",        dot: "bg-red-400",     label: "Revoked"   },
};
function statusCfg(s) {
    return STATUS[s] ?? { badge: "bg-gray-100 text-gray-500 ring-1 ring-gray-200", dot: "bg-gray-400", label: s ?? "—" };
}

// ── Stat cards — 4 in one row ────────────────────────────────────────────────
const total = computed(() => props.stats?.total ?? 0);
function pct(n) { return (!total.value || !n) ? 0 : Math.round((n / total.value) * 100); }

const statCards = computed(() => [
    { label: "Total Enrolled", count: total.value,               bar: "bg-gray-500",   pct: 100,                           sub: "All cohorts",          left: "border-l-gray-400"   },
    { label: "Admitted",       count: props.stats?.admitted ?? 0, bar: "bg-blue-500",   pct: pct(props.stats?.admitted),    sub: "Awaiting confirmation", left: "border-l-blue-500"   },
    { label: "Active Courses",  count: props.stats?.confirmed ?? 0,bar: "bg-violet-500", pct: pct(props.stats?.confirmed),   sub: "Currently enrolled",   left: "border-l-violet-500" },
    { label: "Revoked",        count: props.stats?.revoked ?? 0,  bar: "bg-red-400",    pct: pct(props.stats?.revoked),     sub: "Access removed",       left: "border-l-red-400"    },
]);

// ── Dates ────────────────────────────────────────────────────────────────────
function shortDate(d) {
    return d ? new Date(d).toLocaleDateString("en-GB", { month: "short", year: "numeric" }) : "";
}
function formatDateRange(item) {
    if (!item.started_at) return "—";
    const s = shortDate(item.started_at);
    return item.ended_at ? `${s} – ${shortDate(item.ended_at)}` : `${s} – present`;
}

// ── Course avatar ─────────────────────────────────────────────────────────────
const STATUS_AVATAR = {
    admitted: "bg-blue-100 text-blue-700", confirmed: "bg-violet-100 text-violet-700",
    completed: "bg-emerald-100 text-emerald-700", revoked: "bg-red-100 text-red-500",
};
const HASH_COLORS = ["bg-cyan-100 text-cyan-700","bg-amber-100 text-amber-700","bg-rose-100 text-rose-700","bg-indigo-100 text-indigo-700","bg-teal-100 text-teal-700","bg-sky-100 text-sky-700"];
function hashColor(name) {
    if (!name) return HASH_COLORS[0];
    let h = 0; for (let i = 0; i < name.length; i++) h = name.charCodeAt(i) + ((h << 5) - h);
    return HASH_COLORS[Math.abs(h) % HASH_COLORS.length];
}
function avatarClass(item) { return STATUS_AVATAR[item.status] ?? hashColor(item.course_name); }
function courseInitial(name) { return name ? name.charAt(0).toUpperCase() : "?"; }

</script>

<template>
    <Head title="Course History" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-baseline gap-3">
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">My Learning History</h2>
                <span class="text-sm text-gray-400 font-normal">OMCP · GI-KACE</span>
            </div>
        </template>

        <div class="w-full max-w-[1400px] mx-auto px-4 sm:px-6 py-8 space-y-6">

            <!-- ══════════════════════════════════════════════════════
                 STAT CARDS — 4 cards in one horizontal row
                 Uses flex so it never wraps regardless of container
            ══════════════════════════════════════════════════════ -->
            <div class="flex flex-row gap-4">
                <div
                    v-for="card in statCards"
                    :key="card.label"
                    class="flex-1 min-w-0 bg-white rounded-xl border border-gray-100 border-l-4 px-5 py-5"
                    :class="card.left"
                >
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-2">
                        {{ card.label }}
                    </p>
                    <p class="text-4xl font-bold text-gray-900 tabular-nums leading-none">
                        {{ card.count.toLocaleString() }}
                    </p>
                    <div class="h-1 bg-gray-100 rounded-full overflow-hidden mt-3 mb-2">
                        <div
                            class="h-full rounded-full transition-all duration-700"
                            :class="card.bar"
                            :style="{ width: card.pct + '%' }"
                        />
                    </div>
                    <p class="text-[11px] text-gray-400 uppercase tracking-wide font-medium">
                        {{ card.sub }}
                    </p>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════
                 MAIN LAYOUT: courses + related (8) | timeline (4)
            ══════════════════════════════════════════════════════ -->
            <div class="grid grid-cols-12 gap-5 items-start">

                <!-- ▌Left column — Course list + Related courses ▌ -->
                <div class="col-span-12 lg:col-span-8 space-y-5">

                    <!-- All courses card -->
                    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">

                        <!-- header -->
                        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-semibold text-gray-800">All courses</h3>
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-500">
                                    {{ history.data.length }}
                                </span>
                            </div>
                            <div class="flex rounded-lg border border-gray-200 overflow-hidden">
                                <button
                                    @click="viewMode = 'list'"
                                    class="px-2.5 py-1.5 transition-colors"
                                    :class="viewMode==='list' ? 'bg-gray-100 text-gray-800' : 'text-gray-400 hover:bg-gray-50'"
                                    title="List view"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                    </svg>
                                </button>
                                <button
                                    @click="viewMode = 'grid'"
                                    class="px-2.5 py-1.5 border-l border-gray-200 transition-colors"
                                    :class="viewMode==='grid' ? 'bg-gray-100 text-gray-800' : 'text-gray-400 hover:bg-gray-50'"
                                    title="Grid view"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <rect x="3" y="3" width="7" height="7" rx="1"/>
                                        <rect x="14" y="3" width="7" height="7" rx="1"/>
                                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                                        <rect x="14" y="14" width="7" height="7" rx="1"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- List view -->
                        <template v-if="viewMode === 'list'">
                            <div v-if="history.data.length > 0" class="divide-y divide-gray-50">
                                <div
                                    v-for="item in history.data"
                                    :key="item.id"
                                    class="flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50/60 transition-colors"
                                >
                                    <div class="shrink-0 w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold" :class="avatarClass(item)">
                                        {{ courseInitial(item.course_name) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h4 class="text-sm font-semibold text-gray-900 truncate">{{ item.course_name ?? "—" }}</h4>
                                            <span class="shrink-0 inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase" :class="statusCfg(item.status).badge">
                                                {{ statusCfg(item.status).label }}
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-gray-400 mt-0.5 truncate">
                                            <span v-if="item.centre">{{ item.centre }}</span>
                                            <span v-if="item.centre && item.instructor"> · </span>
                                            <span v-if="item.instructor">{{ item.instructor }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center py-20 text-center">
                                <div class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-medium text-gray-400">No course history yet</p>
                                <p class="text-xs text-gray-300 mt-1">Courses you enrol in will appear here</p>
                            </div>
                        </template>

                        <!-- Grid view -->
                        <template v-else>
                            <div v-if="history.data.length > 0" class="p-5 grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <div
                                    v-for="item in history.data"
                                    :key="item.id"
                                    class="rounded-xl border border-gray-100 p-4 hover:border-gray-200 hover:shadow-sm transition-all"
                                >
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold" :class="avatarClass(item)">
                                            {{ courseInitial(item.course_name) }}
                                        </div>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase" :class="statusCfg(item.status).badge">
                                            {{ statusCfg(item.status).label }}
                                        </span>
                                    </div>
                                    <h4 class="text-sm font-semibold text-gray-900 truncate mb-0.5">{{ item.course_name ?? "—" }}</h4>
                                    <p class="text-[11px] text-gray-400 truncate">{{ item.centre ?? "—" }}</p>
                                    <p class="text-[11px] text-gray-400">{{ item.instructor ?? "—" }}</p>
                                </div>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center py-20 text-center">
                                <div class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                                        <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-medium text-gray-400">No course history yet</p>
                            </div>
                        </template>

                        <!-- Pagination -->
                        <div v-if="history.last_page > 1" class="flex items-center justify-center gap-1 px-5 py-3 border-t border-gray-50">
                            <template v-for="link in history.links" :key="link.label">
                                <span v-if="!link.url" class="px-2.5 py-1 text-xs text-gray-300 select-none" v-html="link.label"/>
                                <Link
                                    v-else :href="link.url" v-html="link.label"
                                    class="px-2.5 py-1 text-xs rounded-lg transition-colors"
                                    :class="link.active ? 'bg-gray-900 text-white font-medium' : 'text-gray-500 hover:bg-gray-100'"
                                />
                            </template>
                        </div>
                    </div>

                    <!-- Related courses (below All courses) -->
                    <div v-if="relatedCourses.length > 0" class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-semibold text-gray-800">Other Courses at Your Centre</h3>
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-600">
                                    Same centre
                                </span>
                            </div>
                        </div>
                        <div class="p-4 grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div
                                v-for="course in relatedCourses"
                                :key="course.id"
                                class="rounded-lg border border-gray-100 overflow-hidden hover:border-gray-200 hover:shadow-sm transition-all flex flex-col"
                            >
                                <!-- Programme image -->
                                <div class="h-24 bg-gray-50 overflow-hidden">
                                    <img
                                        v-if="course.image"
                                        :src="course.image"
                                        :alt="course.course_name"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold" :class="hashColor(course.course_name)">
                                            {{ courseInitial(course.course_name) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="p-3 flex flex-col flex-1">
                                    <h4 class="text-xs font-semibold text-gray-900 truncate">{{ course.course_name }}</h4>
                                    <p class="text-[10px] text-gray-400 truncate mt-0.5">{{ course.programme }}</p>
                                    <div class="flex items-center gap-2 text-[10px] text-gray-400 mt-1">
                                        <span v-if="course.centre" class="truncate">{{ course.centre }}</span>
                                        <span v-if="course.duration" class="shrink-0">{{ course.duration }}</span>
                                    </div>
                                    <Link
                                        :href="route('student.application-status')"
                                        class="mt-2 flex items-center justify-center gap-1 w-full py-1.5 px-2 rounded-md bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-semibold transition-colors"
                                    >
                                        Apply
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                        </svg>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ▌Right column — Timeline (4 cols) ▌ -->
                <div class="col-span-12 lg:col-span-4 bg-white rounded-xl border border-gray-100 p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-5">Timeline</h3>

                    <div v-if="history.data.length > 0" class="relative">
                        <div class="absolute left-[5px] top-2 bottom-2 w-px bg-gray-100"/>
                        <div class="space-y-5">
                            <div v-for="item in history.data" :key="'tl-'+item.id" class="relative flex items-start gap-3 pl-5">
                                <div class="absolute left-0 top-[5px] w-[11px] h-[11px] rounded-full ring-2 ring-white shrink-0" :class="statusCfg(item.status).dot"/>
                                <div class="min-w-0">
                                    <p class="text-[12px] font-semibold text-gray-800 leading-snug truncate">{{ item.course_name ?? "—" }}</p>
                                    <p class="text-[11px] text-gray-400 mt-0.5">
                                        {{ statusCfg(item.status).label }} &middot; {{ formatDateRange(item) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center justify-center py-12 text-center">
                        <div class="w-10 h-10 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                            <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                            </svg>
                        </div>
                        <p class="text-xs font-medium text-gray-400">No events yet</p>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
