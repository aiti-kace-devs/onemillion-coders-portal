<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { Head } from "@inertiajs/vue3";
import StudentLayout from "@/Layouts/Student/AuthenticatedLayout.vue";
import Chart from "chart.js/auto";

const props = defineProps({
  progressState: {
    type: Object,
    required: true,
  },
  progressData: {
    type: Object,
    required: true,
  },
});

const range = ref("all");
const histogramCanvas = ref(null);
const trendCanvas = ref(null);
let histogramChart = null;
let trendChart = null;

const allHistory = computed(() => props.progressData?.history ?? []);
const activities = computed(() => props.progressData?.activities ?? []);
const snapshot = computed(() => props.progressData?.snapshot ?? null);

const filteredHistory = computed(() => {
  const rows = allHistory.value || [];
  if (range.value === "all" || rows.length === 0) return rows;
  const days = Number(range.value);
  if (!Number.isFinite(days) || days <= 0) return rows;
  const latestRaw = rows[rows.length - 1]?.captured_at;
  const latest = latestRaw ? new Date(latestRaw) : null;
  if (!latest || Number.isNaN(latest.getTime())) return rows;
  const cutoff = new Date(latest.getTime() - days * 24 * 60 * 60 * 1000);
  const inRange = rows.filter((row) => {
    const dt = row?.captured_at ? new Date(row.captured_at) : null;
    return dt && !Number.isNaN(dt.getTime()) && dt >= cutoff;
  });
  return inRange.length ? inRange : rows;
});

const labels = computed(() =>
  filteredHistory.value.map((r) =>
    String(r.captured_at || "").replace("T", " ").substring(0, 16)
  )
);

const delta = computed(() => {
  const rows = filteredHistory.value;
  if (rows.length < 2) return null;
  const start = Number(rows[0]?.overall_progress_percent || 0);
  const end = Number(rows[rows.length - 1]?.overall_progress_percent || 0);
  return Number((end - start).toFixed(1));
});

const indicatorWindowText = computed(() => {
  const rows = filteredHistory.value;
  if (rows.length < 2) return "Need at least 2 sync points";
  const first = String(rows[0]?.captured_at || "").replace("T", " ").substring(0, 16);
  const last = String(rows[rows.length - 1]?.captured_at || "").replace("T", " ").substring(0, 16);
  return `${first} -> ${last}`;
});

function activityValue(row, key) {
  const metrics = row?.selected_metrics || {};
  return Number(metrics[key] ?? 0);
}

function classifyTrend(start, end) {
  const s = Number(start || 0);
  const e = Number(end || 0);
  const d = Number((e - s).toFixed(1));
  if (d > 0.5) return { text: `Increasing (+${d})`, cls: "bg-green-100 text-green-700" };
  if (d < -0.5) return { text: `Decreasing (${d})`, cls: "bg-red-100 text-red-700" };
  if (e <= 0.5) return { text: "No progress", cls: "bg-gray-100 text-gray-700" };
  return { text: "Stable / stale", cls: "bg-yellow-100 text-yellow-700" };
}

const indicators = computed(() => {
  const rows = filteredHistory.value;
  if (rows.length < 2) return [];
  const first = rows[0];
  const last = rows[rows.length - 1];
  return activities.value.map((a) => ({
    label: a.label,
    window: indicatorWindowText.value,
    ...classifyTrend(activityValue(first, a.key), activityValue(last, a.key)),
  }));
});

function renderCharts() {
  const rows = filteredHistory.value;
  if (!histogramCanvas.value || !trendCanvas.value || rows.length < 2) return;

  if (histogramChart) histogramChart.destroy();
  if (trendChart) trendChart.destroy();

  const barDatasets = activities.value.map((a) => ({
    label: a.label,
    type: "bar",
    data: rows.map((r) => activityValue(r, a.key)),
    backgroundColor: a.color.replace(", 1)", ", 0.55)"),
    borderColor: a.color,
    borderWidth: 1,
  }));

  histogramChart = new Chart(histogramCanvas.value, {
    type: "bar",
    data: { labels: labels.value, datasets: barDatasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { y: { beginAtZero: true, max: 100 } },
    },
  });

  const lineDatasets = activities.value.map((a) => ({
    label: `${a.label} (%)`,
    data: rows.map((r) => activityValue(r, a.key)),
    borderColor: a.color,
    backgroundColor: a.color.replace(", 1)", ", 0.1)"),
    tension: 0.25,
    pointRadius: 3,
    fill: false,
  }));

  trendChart = new Chart(trendCanvas.value, {
    type: "line",
    data: { labels: labels.value, datasets: lineDatasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { y: { beginAtZero: true, max: 100 } },
    },
  });
}

onMounted(() => renderCharts());
watch([filteredHistory, activities], () => renderCharts(), { deep: true });
watch(range, () => renderCharts());

onBeforeUnmount(() => {
  if (histogramChart) histogramChart.destroy();
  if (trendChart) trendChart.destroy();
});
</script>

<template>
  <Head title="Progress" />
  <StudentLayout>
    <template #header>Partner Progress</template>

    <div class="space-y-4">
      <div v-if="!progressState.eligible" class="rounded-lg bg-white p-6 text-gray-600 shadow-sm">
        Progress is not available for your current course.
      </div>

      <div v-else class="rounded-lg bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold">Learning Progress</h2>
            <p class="text-sm text-gray-500">
              Snapshot-first view with background refresh.
            </p>
          </div>
          <div v-if="snapshot" class="text-right text-sm">
            <div class="font-medium">{{ snapshot.overall_progress_percent.toFixed(1) }}%</div>
            <div class="text-gray-500">Last synced: {{ (snapshot.last_synced_at || '').replace('T', ' ').substring(0, 16) }}</div>
          </div>
        </div>

        <div class="mb-4 flex gap-2">
          <button class="rounded border px-2 py-1 text-sm" :class="{ 'bg-gray-900 text-white': range==='7' }" @click="range='7'">7d</button>
          <button class="rounded border px-2 py-1 text-sm" :class="{ 'bg-gray-900 text-white': range==='14' }" @click="range='14'">14d</button>
          <button class="rounded border px-2 py-1 text-sm" :class="{ 'bg-gray-900 text-white': range==='30' }" @click="range='30'">30d</button>
          <button class="rounded border px-2 py-1 text-sm" :class="{ 'bg-gray-900 text-white': range==='all' }" @click="range='all'">All</button>
          <span v-if="delta !== null" class="ml-auto rounded px-2 py-1 text-sm" :class="delta >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
            {{ delta >= 0 ? '+' : '' }}{{ delta }} pts
          </span>
        </div>
        <p class="mb-3 text-xs text-gray-500">
          Range is day-based: <strong>7d</strong> = last 7 days, <strong>14d</strong> = last 14 days,
          <strong>30d</strong> = last 30 days, <strong>All</strong> = full available history to date.
        </p>

        <div v-if="filteredHistory.length > 1" class="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <div>
            <div class="mb-2 text-sm text-gray-500">Activity histogram over time</div>
            <div class="mb-2 flex flex-wrap gap-2">
              <span
                v-for="activity in activities"
                :key="`legend-${activity.key}`"
                class="rounded border px-2 py-1 text-xs"
                :style="{
                  backgroundColor: activity.color.replace(', 1)', ', 0.14)'),
                  borderColor: activity.color.replace(', 1)', ', 0.45)'),
                  color: '#1f2937'
                }"
              >
                <span
                  class="mr-1 inline-block h-2.5 w-2.5 rounded-full align-middle"
                  :style="{ backgroundColor: activity.color }"
                />
                {{ activity.label }}
              </span>
            </div>
            <div class="h-72"><canvas ref="histogramCanvas"></canvas></div>
            <div class="mt-3 flex flex-wrap gap-2">
              <span
                v-for="item in indicators"
                :key="item.label"
                class="rounded px-2 py-1 text-xs font-medium"
                :class="item.cls"
                :title="item.window"
              >
                {{ item.label }}: {{ item.text }}
              </span>
            </div>
          </div>
          <div>
            <div class="mb-2 text-sm text-gray-500">Activity line trends over time</div>
            <div class="h-72"><canvas ref="trendCanvas"></canvas></div>
          </div>
        </div>
        <div v-else class="rounded bg-gray-50 p-4 text-sm text-gray-600">
          Need at least 2 sync points to render progress charts. Current status: {{ progressState.status }}.
        </div>
      </div>
    </div>
  </StudentLayout>
</template>
