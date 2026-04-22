/**
 * Shared ordering for in-person enrolment modals (ProgrammeCard + choose-course flow).
 * Cohort/session order should match the in-person batches API (InPersonAvailabilityController::batches).
 */

/**
 * Minutes from midnight for the start of a session block.
 * Handles ranges like "8:00AM - 10:00AM", "1:00PM - 3:00PM", "08:00 - 10:00".
 * Important: never treat "1:00PM" as bare HH:MM (that was parsed as 01:00 = 60 and sorted before 8:00AM).
 */
export function sessionTimeSortKey(session) {
  const raw = String(session?.time ?? session?.course_time ?? "").trim();
  if (!raw) {
    return Number.MAX_SAFE_INTEGER;
  }
  const startPart = raw.split(/\s*[–—-]\s*/)[0]?.trim() ?? raw;

  // 24h "08:30" or "8:30" without AM/PM (assume same day)
  let m = startPart.match(/^(\d{1,2}):(\d{2})\s*$/);
  if (m) {
    const h = parseInt(m[1], 10);
    const min = parseInt(m[2], 10);
    if (h >= 0 && h <= 23) {
      return h * 60 + min;
    }
  }

  m = startPart.match(/(\d{1,2})\s*(?::(\d{2}))?\s*am\b/i);
  if (m) {
    let h = parseInt(m[1], 10);
    if (h === 12) {
      h = 0;
    }
    const min = m[2] ? parseInt(m[2], 10) : 0;
    return h * 60 + min;
  }
  m = startPart.match(/(\d{1,2})\s*(?::(\d{2}))?\s*pm\b/i);
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
      standard_sessions: sortSessionsChronologically(b.standard_sessions),
    }));
}

export function cohortSessions(batch) {
  return [
    ...((batch?.sessions || [])),
    ...((batch?.standard_sessions || [])),
  ];
}

export function hasBookableSession(batch) {
  return cohortSessions(batch).some((session) => Number(session?.remaining) > 0);
}

export function availableSessionCount(batch) {
  return cohortSessions(batch).filter((session) => Number(session?.remaining) > 0).length;
}

export function totalSessionCount(batch) {
  return cohortSessions(batch).length;
}

export function batchTotalRemaining(batch) {
  return cohortSessions(batch).reduce((sum, session) => sum + Number(session?.remaining || 0), 0);
}

/**
 * Base URL of the Laravel app (student portal / Inertia), no trailing slash.
 * Set NEXT_PUBLIC_PORTAL_URL when the portal host differs from the API origin (e.g. api.* vs app.*).
 * Otherwise the origin is inferred from NEXT_PUBLIC_API_BASE_URL (e.g. http://localhost:8000/api → http://localhost:8000).
 *
 * Production: if NEXT_PUBLIC_PORTAL_URL is unset and API and portal share one host, behaviour matches
 * inferring the dashboard from the API base (no extra redirect hop). Explicit URL only overrides when needed.
 */
export function getPortalBaseUrl() {
  const explicit = String(process.env.NEXT_PUBLIC_PORTAL_URL || "")
    .trim()
    .replace(/\/+$/, "");
  if (explicit) {
    return explicit;
  }

  const apiBase = String(process.env.NEXT_PUBLIC_API_BASE_URL || "").trim();
  if (!apiBase) {
    return "";
  }

  try {
    const normalized = /\/api\/?$/i.test(apiBase)
      ? apiBase
      : `${apiBase.replace(/\/+$/, "")}/api`;

    return new URL(normalized).origin;
  } catch {
    return "";
  }
}

/** Absolute student dashboard URL on the Laravel host (never the Next.js marketing site). */
export function getStudentDashboardUrl() {
  const base = getPortalBaseUrl();
  if (!base && typeof console !== "undefined" && process.env.NODE_ENV === "development") {
    console.warn(
      "[OMCP] Set NEXT_PUBLIC_PORTAL_URL or NEXT_PUBLIC_API_BASE_URL so enrollment \"Close\" opens the Laravel student dashboard, not /student/dashboard on the Next.js dev server.",
    );
  }

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
      window.parent.postMessage(
        { type: "omcp-student-enrollment-complete", redirectUrl: target },
        "*",
      );
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
