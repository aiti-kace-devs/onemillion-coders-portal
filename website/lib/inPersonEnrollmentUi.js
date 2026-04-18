/**
 * Shared ordering for in-person enrolment modals (ProgrammeCard + choose-course flow).
 * Cohort/session order should match the in-person batches API (InPersonAvailabilityController::batches).
 */

/** Minutes from midnight for sorting session blocks (supports 24h and simple AM/PM strings). */
export function sessionTimeSortKey(session) {
  const raw = String(session?.time ?? session?.course_time ?? "").trim();
  if (!raw) {
    return Number.MAX_SAFE_INTEGER;
  }
  let m = raw.match(/(\d{1,2}):(\d{2})/);
  if (m) {
    return parseInt(m[1], 10) * 60 + parseInt(m[2], 10);
  }
  m = raw.match(/(\d{1,2})\s*(?::(\d{2}))?\s*am\b/i);
  if (m) {
    let h = parseInt(m[1], 10);
    if (h === 12) {
      h = 0;
    }
    const min = m[2] ? parseInt(m[2], 10) : 0;

    return h * 60 + min;
  }
  m = raw.match(/(\d{1,2})\s*(?::(\d{2}))?\s*pm\b/i);
  if (m) {
    let h = parseInt(m[1], 10);
    if (h !== 12) {
      h += 12;
    }
    const min = m[2] ? parseInt(m[2], 10) : 0;

    return h * 60 + min;
  }

  return Number.MAX_SAFE_INTEGER;
}

/** Centre session rows: earliest wall-clock block first; unknown times last. */
export function sortSessionsChronologically(sessions) {
  if (!Array.isArray(sessions)) {
    return [];
  }
  return [...sessions].sort((a, b) => {
    const ka = sessionTimeSortKey(a);
    const kb = sessionTimeSortKey(b);
    if (ka !== kb) {
      return ka - kb;
    }
    return String(a.session_name || "").localeCompare(String(b.session_name || ""));
  });
}

/**
 * Cohorts: by start_date ascending; same start_date then end_date descending (longer window first), then id.
 * Sessions within each cohort by time.
 */
export function normalizeInPersonBatches(batches) {
  if (!Array.isArray(batches)) {
    return [];
  }
  return [...batches]
    .sort((a, b) => {
      const sd = String(a.start_date || "").localeCompare(String(b.start_date || ""));
      if (sd !== 0) {
        return sd;
      }
      const ed = String(b.end_date || "").localeCompare(String(a.end_date || ""));
      if (ed !== 0) {
        return ed;
      }
      return Number(a.id || 0) - Number(b.id || 0);
    })
    .map((b) => ({
      ...b,
      sessions: sortSessionsChronologically(b.sessions),
    }));
}

/** Absolute student dashboard path for the Laravel app (fallback when API omits redirect_url). */
export function getStudentDashboardUrl() {
  const base = String(process.env.NEXT_PUBLIC_PORTAL_URL || "").replace(/\/$/, "");
  return `${base}/student/dashboard`;
}

/**
 * After in-person enrolment, go to the Laravel student dashboard.
 * When the Next app runs inside Laravel's choose-course iframe, assigning `window.location`
 * only replaces the iframe — use top navigation or postMessage (ChangeCourse.vue listener).
 */
export function redirectToStudentDashboard(url) {
  const target = typeof url === "string" && url ? url : getStudentDashboardUrl();
  if (typeof window === "undefined") {
    return;
  }

  if (window.self !== window.top) {
    try {
      window.top.location.href = target;
      return;
    } catch {
      // Cross-origin iframe cannot set top.location; parent listens for omcp-in-person-enrolled.
    }
    try {
      window.parent.postMessage({ type: "omcp-in-person-enrolled", redirectUrl: target }, "*");
    } catch {
      // ignore
    }
    try {
      window.open(target, "_top");
    } catch {
      // ignore
    }
    return;
  }

  window.location.href = target;
}
