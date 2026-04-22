/**
 * When batch APIs return no bookable session, infer why (for honest UI copy).
 * @param {Array<{ sessions?: Array<{ remaining?: number }> }>} batches
 * @returns {"no_batches"|"no_sessions"|"sold_out"|null}
 */
export function deriveAvailabilityIssueFromBatches(batches) {
  const list = Array.isArray(batches) ? batches : [];
  if (list.length === 0) {
    return "no_batches";
  }
  const hasAnySession = list.some((b) => ((b.sessions?.length ?? 0) + (b.standard_sessions?.length ?? 0)) > 0);
  if (!hasAnySession) {
    return "no_sessions";
  }
  const hasSeat = list.some((b) =>
    [...(b.sessions || []), ...(b.standard_sessions || [])].some((s) => Number(s.remaining) > 0),
  );
  if (!hasSeat) {
    return "sold_out";
  }
  return null;
}

/**
 * Headline + explanation for the former "Centre is full" modal.
 * @param {"no_batches"|"no_sessions"|"sold_out"|null} issue
 */
export function courseFullModalCopy(issue) {
  switch (issue) {
    case "sold_out":
      return {
        title: "No seats left",
        detail:
          "Every listed session is at capacity for the current cohorts at this centre.",
      };
    case "no_sessions":
      return {
        title: "Sessions not available to book",
        detail:
          "There are cohorts on the calendar, but no centre sessions are set up (or they are turned off) for this course.",
      };
    case "no_batches":
    default:
      return {
        title: "No open enrolment slots",
        detail:
          "There is no active intake window or programme cohort linked for this course right now. That is usually an admissions schedule or data issue, not because the building is “full”.",
      };
  }
}
