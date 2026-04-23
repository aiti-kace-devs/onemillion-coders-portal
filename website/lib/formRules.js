// Frontend evaluator for the Laravel-style `rules` string the backend sends
// on each registration form field (see `GET /api/form`).
//
// Supported rule kinds: required, nullable, min, max, email, regex.
// Unknown rules are ignored here and left for the server to enforce.

/**
 * Parse a Laravel rules string into a list of { kind, arg } objects.
 *
 * Laravel pipe-separates rules (e.g. "required|min:2|max:255") but the value of
 * `regex:/.../flags` may itself contain characters that look like separators,
 * so we walk the string and treat a regex rule as atomic: it consumes from its
 * opening `/` through the closing `/flags` until the next top-level `|`.
 */
export function parseLaravelRules(rulesStr) {
  if (!rulesStr || typeof rulesStr !== "string") return [];
  const s = rulesStr;
  const rules = [];
  let i = 0;

  while (i < s.length) {
    while (i < s.length && s[i] === "|") i++;
    if (i >= s.length) break;

    // Read rule name up to ':' or '|'.
    let j = i;
    while (j < s.length && s[j] !== ":" && s[j] !== "|") j++;
    const name = s.slice(i, j);

    if (name === "regex" && s[j] === ":" && s[j + 1] === "/") {
      // Walk past the regex body (honouring backslash escapes) until its closing '/'.
      let k = j + 2;
      while (k < s.length) {
        if (s[k] === "\\") {
          k += 2;
          continue;
        }
        if (s[k] === "/") {
          k++;
          break;
        }
        k++;
      }
      // Continue past flags until the next pipe.
      while (k < s.length && s[k] !== "|") k++;
      rules.push({ kind: "regex", arg: s.slice(j + 1, k) });
      i = k;
    } else if (s[j] === ":") {
      // Key:value rule.
      let k = j + 1;
      while (k < s.length && s[k] !== "|") k++;
      rules.push({ kind: name, arg: s.slice(j + 1, k) });
      i = k;
    } else {
      // Flag rule, e.g. "required".
      rules.push({ kind: name, arg: null });
      i = j;
    }
  }

  return rules;
}

/**
 * Convert a Laravel-flavoured regex literal (e.g. `/^[\pL\s\-\']+$/u`) into a
 * native JavaScript RegExp. Returns null if the pattern can't be compiled.
 */
export function compileLaravelRegex(pattern) {
  if (typeof pattern !== "string") return null;
  const match = /^\/(.*)\/([gimsuy]*)$/s.exec(pattern);
  if (!match) return null;

  let body = match[1];
  let flags = match[2] || "";

  // Translate shorthand Unicode property escapes (\pL, \pN, ...) to the
  // JS equivalent (\p{L}, \p{N}, ...). Only needed for `\p<letter>`.
  body = body.replace(/\\p([LNMZPSC])/g, "\\p{$1}");

  // \p{...} requires the `u` flag in JS.
  if (body.includes("\\p{") && !flags.includes("u")) flags += "u";

  // With the `u` flag, JS rejects identity escapes that aren't one of the
  // listed syntax characters. Laravel-authored patterns sometimes contain
  // \' or \" meaning "literal quote", which are valid in Laravel/PCRE but
  // throw SyntaxError in JS under /u. Strip those backslashes — the
  // character stays the same and the regex compiles.
  if (flags.includes("u")) {
    body = body.replace(/\\(['"])/g, "$1");
  }

  try {
    return new RegExp(body, flags);
  } catch (err) {
    if (typeof console !== "undefined") {
      console.warn("[formRules] Could not compile regex:", pattern, err);
    }
    return null;
  }
}

/**
 * Run the parsed rules for a field against its current value and return the
 * first human-readable error message, or null if the value is acceptable.
 *
 * The returned copy matches the tone of the hand-rolled messages already in
 * the register page so inline errors read consistently.
 */
export function runFieldRules(field, value) {
  if (!field || !field.rules) return null;
  const rules = parseLaravelRules(field.rules);
  if (rules.length === 0) return null;

  const str = value == null ? "" : String(value);
  const isEmpty = str.trim() === "";
  const hasNullable = rules.some((r) => r.kind === "nullable");
  const title = field.title || "This field";

  for (const rule of rules) {
    if (rule.kind === "required" && isEmpty) {
      return `${title} is required`;
    }
    if (isEmpty) {
      // Non-required empty values: skip remaining rules either way.
      if (hasNullable) return null;
      continue;
    }

    switch (rule.kind) {
      case "min": {
        const n = Number(rule.arg);
        if (!Number.isNaN(n) && str.length < n) {
          return `${title} must be at least ${n} characters`;
        }
        break;
      }
      case "max": {
        const n = Number(rule.arg);
        if (!Number.isNaN(n) && str.length > n) {
          return `${title} must not exceed ${n} characters`;
        }
        break;
      }
      case "email": {
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(str)) {
          return "Please enter a valid email address";
        }
        break;
      }
      case "regex": {
        const re = compileLaravelRegex(rule.arg);
        if (re && !re.test(str)) {
          return `Please enter a valid ${title.toLowerCase()}`;
        }
        break;
      }
      default:
        // Unknown rule — let the server decide.
        break;
    }
  }

  return null;
}
