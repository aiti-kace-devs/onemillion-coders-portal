<script setup>
import { ref, computed } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";

const props = defineProps({
    history:        { type: Object, required: true },
    stats:          { type: Object, required: true },
    relatedCourses: { type: Array,  default: () => [] },
    canEnroll:      { type: Boolean, default: false },
});

const page = usePage();
const user = computed(() => page.props.auth?.user ?? {});

// ── Status config ────────────────────────────────────────────────────────────
const STATUS = {
    admitted:  { badge: "bg-blue-50 text-blue-700 ring-1 ring-blue-200",    dot: "bg-blue-500",    label: "Admitted"  },
    confirmed: { badge: "bg-violet-50 text-violet-700 ring-1 ring-violet-200", dot: "bg-violet-500", label: "Enrolled" },
    completed: { badge: "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200", dot: "bg-emerald-500", label: "Completed" },
    revoked:   { badge: "bg-red-50 text-red-700 ring-1 ring-red-200",        dot: "bg-red-400",     label: "Revoked"   },
};
function statusCfg(s) {
    return STATUS[s] ?? { badge: "bg-gray-100 text-gray-500 ring-1 ring-gray-200", dot: "bg-gray-400", label: s ?? "—" };
}

// ── Status filter ────────────────────────────────────────────────────────────
const statusFilter = ref("all");
const filterOptions = [
    { value: "all",       label: "All" },
    { value: "confirmed", label: "Enrolled" },
    { value: "completed", label: "Completed" },
    { value: "admitted",  label: "Admitted" },
    { value: "revoked",   label: "Revoked" },
];

const filteredHistory = computed(() => {
    if (statusFilter.value === "all") return props.history.data;
    return props.history.data.filter(item => item.status === statusFilter.value);
});

// ── Stat cards ───────────────────────────────────────────────────────────────
const total = computed(() => props.stats?.total ?? 0);
function pct(n) { return (!total.value || !n) ? 0 : Math.round((n / total.value) * 100); }

const statCards = computed(() => [
    { label: "Total Enrolled", count: total.value,               icon: "school",        sub: "All cohorts"           },
    { label: "Admitted",       count: props.stats?.admitted ?? 0, icon: "pending",       sub: "Awaiting confirmation" },
    { label: "Active Courses", count: props.stats?.confirmed ?? 0,icon: "check_circle",  sub: "Currently enrolled"    },
    { label: "Revoked",        count: props.stats?.revoked ?? 0,  icon: "block",         sub: "Access removed"        },
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

// ── Course avatar ────────────────────────────────────────────────────────────
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
            <h2 class="font-semibold text-xl text-gray-900 leading-tight">My Learning History</h2>
        </template>

        <div class="w-full max-w-[1400px] mx-auto px-4 sm:px-6 py-8 space-y-8">

            <!-- ══════════════════════════════════════════════════════
                 STAT CARDS
            ══════════════════════════════════════════════════════ -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    v-for="card in statCards"
                    :key="card.label"
                    class="relative group bg-white rounded-2xl shadow-sm hover:shadow-xl hover:shadow-orange-500/5 transition-all duration-300 p-7 flex flex-col border border-gray-100/80 overflow-hidden"
                >
                    <!-- Hover Accent Line -->
                    <div class="absolute top-0 left-0 w-full h-1 bg-[#f9a825] transform -translate-x-full group-hover:translate-x-0 transition-transform duration-500"></div>
                    <!-- Icon and Title -->
                    <div class="flex items-center gap-3 mb-2">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[#f9a825]/10 text-[#f9a825] transition-colors duration-300 group-hover:bg-[#f9a825] group-hover:text-gray-900">
                            <span class="material-symbols-outlined">{{ card.icon }}</span>
                        </span>
                        <div class="flex-1 text-left">
                            <h3 class="text-lg font-bold text-gray-800">{{ card.label }}</h3>
                        </div>
                    </div>
                    <!-- Count and subtitle -->
                    <div class="mt-2 space-y-1 text-left">
                        <p class="text-3xl font-bold text-gray-900 tabular-nums">{{ card.count.toLocaleString() }}</p>
                        <p class="text-sm text-gray-500">{{ card.sub }}</p>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════
                 MAIN LAYOUT: courses grid (8) | timeline (4)
            ══════════════════════════════════════════════════════ -->
            <div class="grid grid-cols-12 gap-6 items-start">

                <!-- ▌Left column ▌ -->
                <div class="col-span-12 lg:col-span-8 space-y-8">

                    <!-- All courses — list view with filter -->
                    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-semibold text-gray-800">All courses</h3>
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-500">
                                    {{ filteredHistory.length }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1">
                                <button
                                    v-for="opt in filterOptions"
                                    :key="opt.value"
                                    @click="statusFilter = opt.value"
                                    class="px-2.5 py-1 text-[11px] font-medium rounded-md transition-colors"
                                    :class="statusFilter === opt.value
                                        ? 'bg-gray-900 text-white'
                                        : 'text-gray-400 hover:bg-gray-50 hover:text-gray-600'"
                                >
                                    {{ opt.label }}
                                </button>
                            </div>
                        </div>

                        <div v-if="filteredHistory.length > 0" class="divide-y divide-gray-50">
                            <div
                                v-for="item in filteredHistory"
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
                                    <p class="text-[11px] text-gray-400 mt-0.5 truncate">{{ item.centre ?? "—" }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center py-20 text-center">
                            <div class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-400">
                                {{ statusFilter === 'all' ? 'No course history yet' : 'No ' + filterOptions.find(o => o.value === statusFilter)?.label.toLowerCase() + ' courses' }}
                            </p>
                            <p v-if="statusFilter === 'all'" class="text-xs text-gray-300 mt-1">Courses you enrol in will appear here</p>
                        </div>

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

                </div>

                <!-- ▌Right column — Timeline ▌ -->
                <div class="col-span-12 lg:col-span-4 bg-white rounded-xl border border-gray-100 p-5 lg:sticky lg:top-6">
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
                                    <p v-if="item.session || item.session_time" class="text-[10px] text-gray-400 mt-0.5 flex items-center gap-1">
                                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span v-if="item.session">{{ item.session }}</span>
                                        <span v-if="item.session && item.session_time"> · </span>
                                        <span v-if="item.session_time">{{ item.session_time }}</span>
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

            <!-- ══════════════════════════════════════════════════════
                 OTHER COURSES — full width, outside the 2-col grid
            ══════════════════════════════════════════════════════ -->
            <div v-if="relatedCourses.length > 0">
                <div class="flex items-center gap-2 mb-4">
                    <h3 class="text-base font-bold text-gray-900">Other Courses at Your Centre</h3>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-600">
                        Same centre
                    </span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div
                        v-for="course in relatedCourses"
                        :key="course.id"
                        class="bg-white rounded-lg border border-gray-100 overflow-hidden hover:shadow-md transition-shadow flex flex-col"
                    >
                        <div class="aspect-video bg-gray-100 overflow-hidden">
                            <img
                                v-if="course.image"
                                :src="course.image"
                                :alt="course.course_name"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center" :class="hashColor(course.course_name)">
                                <span class="text-2xl font-bold opacity-60">{{ courseInitial(course.course_name) }}</span>
                            </div>
                        </div>
                        <div class="p-3.5 flex-1 flex flex-col">
                            <h4 class="text-sm font-bold text-gray-900 leading-snug line-clamp-2">{{ course.course_name }}</h4>
                            <p class="text-[11px] text-gray-400 mt-1 truncate">{{ course.programme }}</p>
                            <div class="flex items-center gap-2 text-[11px] text-gray-400 mt-0.5">
                                <span v-if="course.centre" class="truncate">{{ course.centre }}</span>
                                <span v-if="course.centre && course.duration" class="text-gray-200">·</span>
                                <span v-if="course.duration" class="shrink-0">{{ course.duration }}</span>
                            </div>
                            <Link
                                v-if="canEnroll"
                                :href="route('student.application-status')"
                                class="mt-3 flex items-center justify-center gap-1.5 w-full py-2 px-3 rounded-md bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition-colors"
                            >
                                Apply
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                </svg>
                            </Link>
                            <span
                                v-else
                                class="mt-3 flex items-center justify-center w-full py-2 px-3 rounded-md bg-gray-100 text-gray-400 text-[10px] font-medium cursor-not-allowed"
                            >
                                Complete current course to apply
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
