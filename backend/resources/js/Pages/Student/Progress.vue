<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
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
/** Observed wrapper so charts reflow on orientation / split-screen / font scaling */
const chartSurfaceRef = ref(null);
const isNarrow = ref(false);
let histogramChart = null;
let trendChart = null;
let chartResizeObserver = null;
let mediaNarrow = null;

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

/** Shorter tick labels on phones; full sync timestamps on larger viewports */
const labels = computed(() => {
  const rows = filteredHistory.value;
  return rows.map((r) => {
    const raw = r?.captured_at;
    if (!raw) return "";
    if (isNarrow.value) {
      const d = new Date(raw);
      if (Number.isNaN(d.getTime())) return String(raw).replace("T", " ").substring(0, 13);
      return d.toLocaleDateString(undefined, { month: "short", day: "numeric" });
    }
    return String(raw).replace("T", " ").substring(0, 16);
  });
});

const delta = computed(() => {
  const rows = filteredHistory.value;
  if (rows.length < 2) return null;
  const start = Number(rows[0]?.overall_progress_percent || 0);
  const end = Number(rows[rows.length - 1]?.overall_progress_percent || 0);
  return Number((end - start).toFixed(1));
});

/**
 * High-contrast ink for charts drawn on an explicit white plot surface (see canvas wrapper).
 * Slightly darker than gray-900 so axis ticks and legend stay readable on white and in exports.
 */
const PARTNER_CHART_INK = {
  text: "#020617",
  muted: "#334155",
  grid: "rgba(15, 23, 42, 0.14)",
  chipText: "#020617",
  fontFamily: "system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif",
  tooltipBg: "rgba(15, 23, 42, 0.96)",
  tooltipTitle: "#f8fafc",
  tooltipBody: "#e2e8f0",
};

const indicatorWindowText = computed(() => {
  const rows = filteredHistory.value;
  if (rows.length < 2) return "Need at least 2 sync points";
  const first = String(rows[0]?.captured_at || "").replace("T", " ").substring(0, 16);
  const last = String(rows[rows.length - 1]?.captured_at || "").replace("T", " ").substring(0, 16);
  return `${first} -> ${last}`;
});

/** Same as admin getActivityValue: prefer payload_json.selected_metrics, else top-level selected_metrics. */
function activityValue(row, key) {
  const fromPayload = row?.payload_json?.selected_metrics;
  if (fromPayload && fromPayload[key] !== undefined && fromPayload[key] !== null) {
    return Number(fromPayload[key] || 0);
  }
  const metrics = row?.selected_metrics || {};
  if (metrics[key] !== undefined && metrics[key] !== null) {
    return Number(metrics[key] || 0);
  }
  return 0;
}

/** Mirrors admin getTrendMeta(start, end) — first vs last row only. */
function getTrendMeta(start, end) {
  const startNum = Number(start || 0);
  const endNum = Number(end || 0);
  const d = Number((endNum - startNum).toFixed(1));

  if (d > 0.5) {
    return {
      text: `Increasing (+${d})`,
      cls: "rounded-md px-2.5 py-1.5 text-sm font-bold leading-snug shadow-sm ring-1 ring-black/10 bg-emerald-600 text-white",
    };
  }
  if (d < -0.5) {
    return {
      text: `Decreasing (${d})`,
      cls: "rounded-md px-2.5 py-1.5 text-sm font-bold leading-snug shadow-sm ring-1 ring-black/10 bg-red-600 text-white",
    };
  }
  if (endNum <= 0.5) {
    return {
      text: "No progress",
      cls: "rounded-md px-2.5 py-1.5 text-sm font-bold leading-snug shadow-sm ring-1 ring-black/10 bg-slate-600 text-white",
    };
  }
  return {
    text: "Stable / stale",
    cls: "rounded-md px-2.5 py-1.5 text-sm font-bold leading-snug shadow-sm ring-1 ring-amber-500/80 bg-amber-400 text-gray-950",
  };
}

const indicators = computed(() => {
  const rows = filteredHistory.value;
  if (rows.length < 2) return [];
  const first = rows[0];
  const last = rows[rows.length - 1];
  return activities.value.map((a) => ({
    label: a.label,
    window: indicatorWindowText.value,
    ...getTrendMeta(activityValue(first, a.key), activityValue(last, a.key)),
  }));
});

function partnerChartTooltipPlugin(theme) {
  return {
    backgroundColor: theme.tooltipBg,
    titleColor: theme.tooltipTitle,
    bodyColor: theme.tooltipBody,
    borderColor: "rgba(148, 163, 184, 0.45)",
    borderWidth: 1,
    cornerRadius: 6,
    padding: 12,
    titleFont: { size: 13, weight: "700", family: theme.fontFamily },
    bodyFont: { size: 13, weight: "500", family: theme.fontFamily },
  };
}

function partnerScaleY(theme, withStep, narrow) {
  const tickFs = narrow ? 12 : 13;
  const titleFs = narrow ? 12 : 13;
  return {
    border: { display: true, color: theme.muted, width: 1 },
    beginAtZero: true,
    max: 100,
    title: {
      display: true,
      text: "Progress (%)",
      color: theme.text,
      font: { size: titleFs, weight: "700", family: theme.fontFamily },
      padding: { top: 6, bottom: 4 },
    },
    ticks: {
      color: theme.text,
      font: { size: tickFs, weight: "600", family: theme.fontFamily },
      ...(withStep ? { stepSize: 10 } : {}),
      callback: (value) => (typeof value === "number" ? `${value}%` : value),
    },
    grid: { color: theme.grid, lineWidth: 1 },
  };
}

/** Paints an opaque white backing so axis/legend ink stays high-contrast in dark UI. */
const studentChartPlotBackgroundPlugin = {
  id: "studentChartPlotBackground",
  beforeDraw(chart) {
    const { ctx } = chart;
    ctx.save();
    ctx.fillStyle = "#ffffff";
    ctx.fillRect(0, 0, chart.width, chart.height);
    ctx.restore();
  },
};

function partnerScaleX(theme, narrow) {
  const tickFs = narrow ? 11 : 12;
  return {
    border: { display: true, color: theme.muted, width: 1 },
    title: {
      display: true,
      text: "Sync time",
      color: theme.text,
      font: { size: narrow ? 11 : 13, weight: "700", family: theme.fontFamily },
      padding: { top: 4, bottom: narrow ? 8 : 6 },
    },
    ticks: {
      maxTicksLimit: narrow ? 6 : 10,
      color: theme.text,
      font: { size: tickFs, weight: "600", family: theme.fontFamily },
      maxRotation: narrow ? 55 : 50,
      minRotation: narrow ? 30 : 20,
      autoSkip: true,
      autoSkipPadding: 8,
    },
    grid: { color: theme.grid, lineWidth: 1 },
  };
}

function renderCharts() {
  const rows = filteredHistory.value;
  if (!histogramCanvas.value || !trendCanvas.value || rows.length < 2) return;
  const theme = PARTNER_CHART_INK;
  const narrow = isNarrow.value;

  if (histogramChart) histogramChart.destroy();
  if (trendChart) trendChart.destroy();

  const legendLabels = {
    color: theme.text,
    font: { size: narrow ? 12 : 14, weight: "600", family: theme.fontFamily },
    boxWidth: narrow ? 16 : 20,
    padding: narrow ? 12 : 18,
    usePointStyle: false,
  };

  const chartLayoutPadding = narrow
    ? { top: 6, right: 4, bottom: 8, left: 4 }
    : { top: 8, right: 8, bottom: 12, left: 8 };

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
    plugins: [studentChartPlotBackgroundPlugin],
    data: { labels: labels.value, datasets: barDatasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      color: theme.text,
      font: { family: theme.fontFamily },
      interaction: { mode: "index", intersect: false, axis: "x" },
      layout: { padding: chartLayoutPadding },
      animation: { duration: narrow ? 400 : 600 },
      plugins: {
        legend: {
          display: true,
          position: "bottom",
          align: "center",
          labels: legendLabels,
        },
        tooltip: partnerChartTooltipPlugin(theme),
      },
      scales: {
        y: partnerScaleY(theme, false, narrow),
        x: partnerScaleX(theme, narrow),
      },
    },
  });

  const lineDatasets = activities.value.map((a, idx) => ({
    label: `${a.label} (%)`,
    data: rows.map((r) => activityValue(r, a.key)),
    borderColor: a.color,
    backgroundColor: a.color.replace(", 1)", ", 0.08)"),
    borderWidth: idx === 0 ? (narrow ? 2.5 : 3) : narrow ? 1.5 : 2,
    tension: 0.25,
    pointRadius: narrow ? 2 : 3,
    pointHoverRadius: narrow ? 4 : 5,
    fill: false,
  }));

  trendChart = new Chart(trendCanvas.value, {
    type: "line",
    plugins: [studentChartPlotBackgroundPlugin],
    data: { labels: labels.value, datasets: lineDatasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      color: theme.text,
      font: { family: theme.fontFamily },
      interaction: { mode: "index", intersect: false, axis: "x" },
      layout: { padding: chartLayoutPadding },
      animation: { duration: narrow ? 400 : 600 },
      plugins: {
        legend: {
          display: true,
          position: "bottom",
          align: "center",
          labels: legendLabels,
        },
        tooltip: partnerChartTooltipPlugin(theme),
      },
      scales: {
        y: partnerScaleY(theme, true, narrow),
        x: partnerScaleX(theme, narrow),
      },
    },
  });
}

function setupChartResizeObserver() {
  chartResizeObserver?.disconnect();
  if (!chartSurfaceRef.value) return;
  chartResizeObserver = new ResizeObserver(() => {
    requestAnimationFrame(() => {
      histogramChart?.resize();
      trendChart?.resize();
    });
  });
  chartResizeObserver.observe(chartSurfaceRef.value);
}

function onNarrowMediaChange(e) {
  isNarrow.value = e.matches;
}

onMounted(() => {
  if (typeof window !== "undefined") {
    mediaNarrow = window.matchMedia("(max-width: 639px)");
    isNarrow.value = mediaNarrow.matches;
    mediaNarrow.addEventListener("change", onNarrowMediaChange);
  }
  renderCharts();
  nextTick(() => setupChartResizeObserver());
});

watch([filteredHistory, activities, isNarrow, range], () => {
  renderCharts();
  nextTick(() => setupChartResizeObserver());
}, { deep: true });

onBeforeUnmount(() => {
  mediaNarrow?.removeEventListener("change", onNarrowMediaChange);
  chartResizeObserver?.disconnect();
  chartResizeObserver = null;
  if (histogramChart) histogramChart.destroy();
  if (trendChart) trendChart.destroy();
});
</script>

<template>
  <Head title="Learning Progress" />
  <StudentLayout>
    <template #header>Learning Progress</template>

    <div class="mx-auto max-w-[1600px] space-y-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
      <div v-if="!progressState.eligible" class="rounded-xl bg-white p-5 text-gray-600 shadow-sm sm:p-6 dark:bg-slate-900 dark:text-slate-300">
        Progress is not available for your current course.
      </div>

      <div v-else class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-900/5 sm:p-6 dark:bg-slate-900 dark:ring-white/10">
        <div class="mb-5 flex flex-col gap-4 sm:mb-6 sm:flex-row sm:items-start sm:justify-between sm:gap-6">
          <div class="min-w-0 flex-1">
            <h2 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100 sm:text-xl">Learning Progress</h2>
            <p class="mt-1 text-sm leading-relaxed text-gray-500 dark:text-slate-400">
              Snapshot-first view with background refresh. Tap or hover the charts to see values at each sync.
            </p>
          </div>
          <div
            v-if="snapshot"
            class="shrink-0 rounded-xl border border-slate-200/80 bg-gradient-to-br from-slate-50 to-white px-4 py-3 text-slate-800 shadow-sm dark:border-slate-700 dark:from-slate-800/80 dark:to-slate-900 dark:text-slate-100 sm:text-right"
          >
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Overall</div>
            <div class="text-2xl font-bold tabular-nums text-slate-900 dark:text-slate-50 sm:text-3xl">
              {{ snapshot.overall_progress_percent.toFixed(1) }}%
            </div>
            <div class="mt-1 max-w-[min(100%,20rem)] text-xs leading-snug text-gray-600 dark:text-slate-400">
              Last synced:
              <time :datetime="snapshot.last_synced_at || undefined">{{
                (snapshot.last_synced_at || "").replace("T", " ").substring(0, 16)
              }}</time>
            </div>
          </div>
        </div>

        <div
          v-if="filteredHistory.length > 1"
          ref="chartSurfaceRef"
          class="partner-progress-chart-surface rounded-xl border border-slate-900/10 bg-white p-3 text-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.75)] sm:p-4 dark:border-slate-700/60 dark:bg-slate-950/30"
        >
          <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:gap-8">
            <div class="min-w-0">
              <div class="mb-2 text-sm font-bold text-slate-800 dark:text-slate-100">Activity histogram over time</div>
              <div class="mb-3 flex flex-wrap gap-2">
                <span
                  v-for="activity in activities"
                  :key="`legend-${activity.key}`"
                  class="touch-manipulation inline-flex max-w-full items-center rounded-lg border px-2.5 py-1.5 text-sm font-bold leading-tight shadow-sm ring-1 ring-black/5 sm:px-2.5 sm:py-1.5"
                  :style="{
                    backgroundColor: activity.color.replace(', 1)', ', 0.16)'),
                    borderColor: activity.color.replace(', 1)', ', 0.55)'),
                    color: PARTNER_CHART_INK.chipText,
                  }"
                >
                  <span
                    class="mr-1.5 inline-block h-2.5 w-2.5 flex-shrink-0 rounded-full ring-1 ring-black/10"
                    :style="{ backgroundColor: activity.color }"
                  />
                  <span class="truncate">{{ activity.label }}</span>
                </span>
              </div>
              <div
                class="relative w-full touch-pan-y lg:touch-auto h-[clamp(12.5rem,42vh,17rem)] lg:h-[260px] overflow-hidden rounded-lg border border-slate-200 bg-white shadow-inner dark:border-slate-600"
              >
                <canvas ref="histogramCanvas" aria-label="Partner activity histogram" role="img"></canvas>
              </div>
              <div class="mt-4 text-sm font-bold text-slate-800 dark:text-slate-100">Component trend indicators (latest window)</div>

              <details class="mb-3 mt-2 rounded-lg border border-slate-200/80 bg-slate-50/80 p-3 lg:hidden dark:border-slate-700 dark:bg-slate-900/50">
                <summary class="cursor-pointer list-none text-sm font-semibold text-slate-800 outline-none ring-offset-2 focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-slate-200 [&::-webkit-details-marker]:hidden">
                  <span class="flex items-center justify-between gap-2">
                    How indicators work
                    <span class="text-xs font-normal text-slate-500">Tap to expand</span>
                  </span>
                </summary>
                <div class="mt-3 text-sm leading-relaxed text-slate-800 dark:text-slate-200">
                  <span class="inline-flex items-center rounded-md bg-emerald-600 px-2 py-0.5 text-xs font-bold text-white shadow-sm">Increasing</span>
                  (&gt; +0.5 pts),
                  <span class="inline-flex items-center rounded-md bg-red-600 px-2 py-0.5 text-xs font-bold text-white shadow-sm">Decreasing</span>
                  (&lt; -0.5 pts),
                  <span class="inline-flex items-center rounded-md bg-amber-400 px-2 py-0.5 text-xs font-bold text-gray-950 shadow-sm">Stable / stale</span>
                  (between -0.5 and +0.5 pts),
                  <span class="inline-flex items-center rounded-md bg-slate-600 px-2 py-0.5 text-xs font-bold text-white shadow-sm">No progress</span>
                  (current value near 0).
                </div>
              </details>
              <div class="mb-2 hidden text-sm leading-relaxed text-slate-800 lg:block dark:text-slate-200">
                Indicator logic:
                <span class="inline-flex items-center rounded-md bg-emerald-600 px-2 py-0.5 text-xs font-bold text-white shadow-sm">Increasing</span>
                (&gt; +0.5 pts),
                <span class="inline-flex items-center rounded-md bg-red-600 px-2 py-0.5 text-xs font-bold text-white shadow-sm">Decreasing</span>
                (&lt; -0.5 pts),
                <span class="inline-flex items-center rounded-md bg-amber-400 px-2 py-0.5 text-xs font-bold text-gray-950 shadow-sm">Stable / stale</span>
                (between -0.5 and +0.5 pts),
                <span class="inline-flex items-center rounded-md bg-slate-600 px-2 py-0.5 text-xs font-bold text-white shadow-sm">No progress</span>
                (current value near 0).
              </div>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="item in indicators"
                  :key="item.label"
                  :class="[item.cls, 'touch-manipulation max-w-full break-words text-left']"
                  :title="item.window"
                >
                  {{ item.label }}: {{ item.text }}
                </span>
              </div>
            </div>
            <div class="min-w-0 border-t border-slate-200/80 pt-6 lg:border-t-0 lg:border-l lg:pl-8 lg:pt-0 dark:border-slate-700">
              <div class="mb-3 flex flex-col gap-3 sm:mb-2 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                <div class="text-sm font-bold text-slate-800 dark:text-slate-100">Activity line trends over time</div>
                <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                  <div
                    class="grid w-full grid-cols-4 overflow-hidden rounded-xl border border-slate-300 shadow-sm touch-manipulation sm:inline-flex sm:w-auto sm:grid-cols-none dark:border-slate-600"
                    role="group"
                    aria-label="Trend date range"
                  >
                    <button
                      type="button"
                      class="min-h-[44px] border-r border-slate-300 px-2 text-xs font-semibold transition active:scale-[0.98] sm:min-h-0 sm:px-2.5 sm:py-2 dark:border-slate-600"
                      :class="
                        range === '7'
                          ? 'bg-blue-600 text-white dark:bg-blue-500'
                          : 'bg-white text-slate-800 dark:bg-slate-800 dark:text-slate-100'
                      "
                      :aria-pressed="range === '7'"
                      @click="range = '7'"
                    >
                      7d
                    </button>
                    <button
                      type="button"
                      class="min-h-[44px] border-r border-slate-300 px-2 text-xs font-semibold transition active:scale-[0.98] sm:min-h-0 sm:px-2.5 sm:py-2 dark:border-slate-600"
                      :class="
                        range === '14'
                          ? 'bg-blue-600 text-white dark:bg-blue-500'
                          : 'bg-white text-slate-800 dark:bg-slate-800 dark:text-slate-100'
                      "
                      :aria-pressed="range === '14'"
                      @click="range = '14'"
                    >
                      14d
                    </button>
                    <button
                      type="button"
                      class="min-h-[44px] border-r border-slate-300 px-2 text-xs font-semibold transition active:scale-[0.98] sm:min-h-0 sm:px-2.5 sm:py-2 dark:border-slate-600"
                      :class="
                        range === '30'
                          ? 'bg-blue-600 text-white dark:bg-blue-500'
                          : 'bg-white text-slate-800 dark:bg-slate-800 dark:text-slate-100'
                      "
                      :aria-pressed="range === '30'"
                      @click="range = '30'"
                    >
                      30d
                    </button>
                    <button
                      type="button"
                      class="min-h-[44px] px-2 text-xs font-semibold transition active:scale-[0.98] sm:min-h-0 sm:px-2.5 sm:py-2"
                      :class="
                        range === 'all'
                          ? 'bg-blue-600 text-white dark:bg-blue-500'
                          : 'bg-white text-slate-800 dark:bg-slate-800 dark:text-slate-100'
                      "
                      :aria-pressed="range === 'all'"
                      @click="range = 'all'"
                    >
                      All
                    </button>
                  </div>
                  <span
                    v-if="delta !== null"
                    class="inline-flex min-h-[44px] items-center justify-center self-start rounded-lg px-3 text-xs font-semibold text-white touch-manipulation sm:min-h-0 sm:rounded sm:px-2 sm:py-1"
                    :class="delta >= 0 ? 'bg-emerald-600' : 'bg-red-600'"
                  >
                    {{ delta >= 0 ? "+" : "" }}{{ delta }} pts
                  </span>
                </div>
              </div>
              <p class="mb-3 text-xs leading-relaxed text-slate-700 dark:text-slate-300">
                Range is day-based:
                <strong class="font-semibold text-slate-900 dark:text-slate-100">7d</strong> = last 7 days,
                <strong class="font-semibold text-slate-900 dark:text-slate-100">14d</strong> = last 14 days,
                <strong class="font-semibold text-slate-900 dark:text-slate-100">30d</strong> = last 30 days,
                <strong class="font-semibold text-slate-900 dark:text-slate-100">All</strong> = full available history to date.
              </p>
              <div
                class="relative w-full touch-pan-y lg:touch-auto h-[clamp(12.5rem,42vh,17rem)] lg:h-[260px] overflow-hidden rounded-lg border border-slate-200 bg-white shadow-inner dark:border-slate-600"
              >
                <canvas ref="trendCanvas" aria-label="Partner activity line trends" role="img"></canvas>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="rounded-xl bg-gray-50 p-4 text-sm leading-relaxed text-gray-600 dark:bg-slate-800 dark:text-slate-300">
          Need at least 2 sync points to render progress charts. Current status: {{ progressState.status }}.
          <span v-if="progressState.message" class="mt-2 block text-xs text-red-600 dark:text-red-400">
            {{ progressState.message }}
          </span>
        </div>
      </div>
    </div>
  </StudentLayout>
</template>
