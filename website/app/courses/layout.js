/**
 * Course picker is embedded from the Laravel student portal.
 * Keep an explicit light shell so the iframe never shows a dark UA canvas during load.
 */
export default function CoursesLayout({ children }) {
  return (
    <div className="min-h-dvh bg-gradient-to-b from-gray-50 to-gray-100 [color-scheme:light]">
      {children}
    </div>
  );
}
