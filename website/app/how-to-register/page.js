"use client";

import { useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import Image from "next/image";
import { motion, AnimatePresence } from "framer-motion";
import {
  FiArrowRight,
  FiCheck,
  FiAlertCircle,
  FiClock,
  FiList,
  FiArrowUp,
} from "react-icons/fi";
import Button from "../../components/Button";
import { getPageData } from "../../services/pages";

export default function HowToRegisterPage() {
  const router = useRouter();
  const [page, setPage] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [activeStep, setActiveStep] = useState(1);
  const [showBackToTop, setShowBackToTop] = useState(false);

  // Show back-to-top once the user scrolls past the hero.
  useEffect(() => {
    const onScroll = () => setShowBackToTop(window.scrollY > 420);
    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  useEffect(() => {
    let cancelled = false;
    const fetchPage = async () => {
      try {
        setLoading(true);
        setError(null);
        const data = await getPageData("user-guide");
        if (!cancelled) setPage(data);
      } catch (err) {
        console.error("Error fetching user guide:", err);
        if (!cancelled) setError("We couldn't load the guide right now.");
      } finally {
        if (!cancelled) setLoading(false);
      }
    };
    fetchPage();
    return () => {
      cancelled = true;
    };
  }, []);

  const sections = useMemo(
    () => (page?.sections || []).filter((s) => s.enabled !== false),
    [page]
  );
  const totalSteps = useMemo(
    () =>
      sections.reduce(
        (sum, s) => sum + ((s.section_items || []).length || 0),
        0
      ),
    [sections]
  );
  const readMinutes = Math.max(2, Math.ceil(totalSteps * 1.5));

  return (
    <main className="min-h-screen bg-gray-50">
      {/* Hero */}
      <section className="relative bg-gray-900 overflow-hidden">
        <div className="absolute inset-0 opacity-[0.05]">
          <div
            className="absolute inset-0"
            style={{
              backgroundImage:
                "radial-gradient(circle at 1px 1px, white 1px, transparent 0)",
              backgroundSize: "28px 28px",
            }}
          />
        </div>
        <div className="absolute top-0 left-0 right-0 h-[3px] bg-gradient-to-r from-red-500 via-yellow-400 to-green-500" />
        <div
          aria-hidden="true"
          className="pointer-events-none absolute -top-40 -right-40 w-[520px] h-[520px] rounded-full bg-yellow-400/15 blur-3xl"
        />

        <div className="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-12 sm:pt-16 pb-14 sm:pb-20">
          <div className="grid lg:grid-cols-[1fr_auto] gap-8 lg:gap-12 items-end">
            <motion.div
              initial={{ opacity: 0, y: 12 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4, ease: "easeOut" }}
            >
              <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-yellow-400/10 border border-yellow-400/20 mb-5">
                <FiList className="w-3.5 h-3.5 text-yellow-400" />
                <span className="text-[11px] tracking-[0.18em] uppercase text-yellow-400 font-semibold">
                  User guide
                </span>
              </div>
              <h1 className="text-3xl sm:text-4xl md:text-[48px] font-bold text-white tracking-tight leading-[1.05] mb-4">
                How to register
              </h1>
              <p className="text-base sm:text-lg text-gray-400 max-w-2xl leading-relaxed">
                A clear, step-by-step walkthrough of the One Million Coders
                registration process — from starting your application to
                finishing your details.
              </p>
            </motion.div>

            {!loading && totalSteps > 0 && (
              <motion.div
                initial={{ opacity: 0, y: 12 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.4, delay: 0.1, ease: "easeOut" }}
                className="flex gap-2 sm:gap-3"
              >
                <MetaPill
                  label="Steps"
                  value={totalSteps}
                  icon={FiList}
                />
                <MetaPill
                  label="Time"
                  value={`~${readMinutes} min`}
                  icon={FiClock}
                />
              </motion.div>
            )}
          </div>
        </div>
      </section>

      {/* Content */}
      <section className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        {loading && <GuideSkeleton />}

        {error && !loading && (
          <div className="flex flex-col items-center justify-center py-16 text-center">
            <div className="w-12 h-12 rounded-full bg-red-50 border border-red-100 flex items-center justify-center mb-4">
              <FiAlertCircle className="w-5 h-5 text-red-500" />
            </div>
            <p className="text-sm font-medium text-gray-700 mb-1">{error}</p>
            <p className="text-sm text-gray-500">
              Please refresh the page to try again.
            </p>
          </div>
        )}

        {!loading && !error && sections.length === 0 && (
          <p className="text-sm text-gray-500 text-center py-12">
            The guide is being prepared. Please check back soon.
          </p>
        )}

        {!loading && !error && sections.length > 0 && (
          <GuideBody
            sections={sections}
            activeStep={activeStep}
            onActiveStepChange={setActiveStep}
          />
        )}
      </section>

      {/* CTA */}
      <section className="bg-gradient-to-r from-yellow-400 to-yellow-500 py-14 sm:py-16">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6 }}
          >
            <h2 className="text-2xl sm:text-3xl md:text-[32px] font-bold text-gray-900 mb-3 tracking-tight">
              Ready to register?
            </h2>
            <p className="text-base sm:text-lg text-gray-800 mb-7 max-w-2xl mx-auto">
              Follow the steps above and you&apos;ll be done in just a few minutes.
            </p>
            <div className="flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center">
              <Button
                onClick={() => router.push("/register")}
                variant="outline"
                size="large"
                icon={FiArrowRight}
                iconPosition="right"
                className="!border-gray-900 !text-gray-900 hover:!bg-gray-900 hover:!text-white"
              >
                Start registration
              </Button>
              <Button
                onClick={() => router.push("/programmes")}
                variant="outline"
                size="large"
                icon={FiArrowRight}
                className="!border-gray-900 !text-gray-900 hover:!bg-gray-900 hover:!text-white"
              >
                Browse programmes
              </Button>
            </div>
          </motion.div>
        </div>
      </section>

      {/* Back to top */}
      <AnimatePresence>
        {showBackToTop && (
          <motion.button
            type="button"
            aria-label="Back to top"
            initial={{ opacity: 0, y: 10, scale: 0.9 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: 10, scale: 0.9 }}
            transition={{ duration: 0.2, ease: "easeOut" }}
            onClick={() =>
              window.scrollTo({ top: 0, behavior: "smooth" })
            }
            className="fixed bottom-5 right-5 sm:bottom-6 sm:right-6 z-40 w-11 h-11 rounded-full bg-gray-900 hover:bg-black text-white shadow-[0_10px_30px_-10px_rgba(0,0,0,0.5)] flex items-center justify-center transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-yellow-400 focus-visible:ring-offset-2"
          >
            <FiArrowUp className="w-4 h-4" strokeWidth={2.5} />
          </motion.button>
        )}
      </AnimatePresence>
    </main>
  );
}

function MetaPill({ label, value, icon: Icon }) {
  return (
    <div className="flex items-center gap-2.5 px-3.5 py-2 rounded-xl bg-white/5 border border-white/10 backdrop-blur-sm">
      <Icon className="w-3.5 h-3.5 text-yellow-400" />
      <div className="flex items-baseline gap-1.5">
        <span className="text-sm font-semibold text-white">{value}</span>
        <span className="text-[11px] uppercase tracking-wider text-gray-400">
          {label}
        </span>
      </div>
    </div>
  );
}

function GuideBody({ sections, activeStep, onActiveStepChange }) {
  // Flatten items across enabled sections so numbering is continuous.
  const flatSteps = useMemo(() => {
    const arr = [];
    let n = 1;
    sections.forEach((section) => {
      (section.section_items || []).forEach((item) => {
        arr.push({ section, item, number: n++ });
      });
    });
    return arr;
  }, [sections]);

  // Scroll-spy: highlight the TOC entry for the step currently in view.
  useEffect(() => {
    if (typeof window === "undefined" || flatSteps.length === 0) return;
    const targets = flatSteps
      .map(({ number }) => document.getElementById(`step-${number}`))
      .filter(Boolean);
    if (targets.length === 0) return;

    const observer = new IntersectionObserver(
      (entries) => {
        // Pick the entry closest to the top of the active band.
        const visible = entries
          .filter((e) => e.isIntersecting)
          .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
        if (visible.length === 0) return;
        const id = visible[0].target.id;
        const match = id.match(/^step-(\d+)$/);
        if (match) onActiveStepChange?.(Number(match[1]));
      },
      { rootMargin: "-25% 0px -55% 0px", threshold: 0 }
    );

    targets.forEach((t) => observer.observe(t));
    return () => observer.disconnect();
  }, [flatSteps, onActiveStepChange]);

  return (
    <div className="grid lg:grid-cols-[240px_1fr] gap-8 lg:gap-12">
      {/* Desktop sticky TOC */}
      <aside className="hidden lg:block">
        <div className="sticky top-24">
          <p className="text-[11px] tracking-[0.18em] uppercase text-gray-400 font-semibold mb-4">
            On this page
          </p>
          <nav>
            <ol className="relative space-y-0.5">
              <span
                aria-hidden="true"
                className="absolute left-[11px] top-1 bottom-1 w-px bg-gray-200"
              />
              {flatSteps.map(({ item, number }) => {
                const isActive = number === activeStep;
                return (
                  <li key={item.id ?? number}>
                    <a
                      href={`#step-${number}`}
                      aria-current={isActive ? "location" : undefined}
                      className={`group relative flex items-start gap-3 py-2 pl-0 text-[13px] transition-colors rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-yellow-400 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-50 ${
                        isActive
                          ? "text-gray-900"
                          : "text-gray-500 hover:text-gray-900"
                      }`}
                    >
                      <span
                        className={`relative z-10 inline-flex w-6 h-6 rounded-md text-[11px] font-semibold items-center justify-center flex-shrink-0 transition-colors ${
                          isActive
                            ? "bg-yellow-400 text-gray-900 shadow-sm"
                            : "bg-gray-100 text-gray-600 group-hover:bg-yellow-100 group-hover:text-yellow-700"
                        }`}
                      >
                        {String(number).padStart(2, "0")}
                      </span>
                      <span
                        className={`leading-snug pt-[3px] transition-colors ${
                          isActive ? "font-semibold" : ""
                        }`}
                      >
                        {item.title}
                      </span>
                    </a>
                  </li>
                );
              })}
            </ol>
          </nav>
        </div>
      </aside>

      {/* Steps */}
      <div className="min-w-0">
        {sections.map((section, si) => {
          const items = section.section_items || [];
          const startNumber = flatSteps.find(
            (s) => s.section.id === section.id
          )?.number;
          return (
            <div key={section.id ?? si} className="mb-12 last:mb-0">
              <div className="mb-6 sm:mb-8">
                <p className="text-[11px] tracking-[0.2em] uppercase text-yellow-600 font-semibold mb-2">
                  {section.name}
                </p>
                <h2 className="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight">
                  {section.caption || section.name}
                </h2>
              </div>

              <div className="space-y-4 sm:space-y-5">
                {items.map((item, i) => (
                  <GuideStepCard
                    key={item.id ?? i}
                    item={item}
                    stepNumber={(startNumber ?? 1) + i}
                  />
                ))}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}

function GuideStepCard({ item, stepNumber }) {
  const images = Array.isArray(item.images) ? item.images.filter(Boolean) : [];
  const parsed = parseDescription(item.description || "");

  return (
    <motion.article
      id={`step-${stepNumber}`}
      initial={{ opacity: 0, y: 16 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true, margin: "-80px" }}
      transition={{ duration: 0.35, ease: "easeOut" }}
      className="group relative scroll-mt-24 bg-white border border-gray-200 rounded-2xl overflow-hidden hover:border-yellow-200 hover:shadow-[0_12px_32px_-16px_rgba(0,0,0,0.18)] transition-all duration-300"
    >
      <div className="absolute top-0 left-0 w-[3px] h-full bg-gradient-to-b from-yellow-400 to-yellow-500 opacity-0 group-hover:opacity-100 transition-opacity" />

      <div className="grid sm:grid-cols-[auto_1fr] gap-5 sm:gap-7 p-5 sm:p-7">
        <div className="flex sm:flex-col items-center sm:items-start gap-3 sm:gap-0">
          <div className="relative">
            <span
              aria-hidden="true"
              className="hidden sm:block text-[56px] leading-none font-bold text-gray-100 tracking-tighter select-none"
            >
              {String(stepNumber).padStart(2, "0")}
            </span>
            <span className="sm:hidden inline-flex items-center justify-center w-10 h-10 rounded-full bg-yellow-400 text-gray-900 text-sm font-bold">
              {stepNumber}
            </span>
          </div>
          <span className="sm:mt-1 text-[11px] tracking-[0.18em] uppercase text-gray-400 font-semibold">
            Step {stepNumber}
          </span>
        </div>

        <div className="min-w-0">
          <h3 className="text-[17px] sm:text-xl font-bold text-gray-900 mb-3 tracking-tight">
            {item.title}
          </h3>

          <DescriptionBlocks blocks={parsed} />

          {images.length > 0 && (
            <div className="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
              {images.map((src, i) => (
                <div
                  key={i}
                  className="relative aspect-video rounded-xl overflow-hidden bg-gray-100 border border-gray-200"
                >
                  <Image
                    src={src}
                    alt={`${item.title} — illustration ${i + 1}`}
                    fill
                    className="object-cover"
                  />
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </motion.article>
  );
}

// Parse a guide description into a structured list of blocks.
// Lines starting with a number + "." become steps; tab-indented lines become
// sub-items under the previous step; everything else becomes a paragraph.
function parseDescription(text) {
  if (!text) return [];
  const blocks = [];
  const lines = text.split(/\r?\n/);

  for (const raw of lines) {
    if (!raw.trim()) continue;
    const indented = /^[\t ]/.test(raw);
    const trimmed = raw.trim();
    const numMatch = trimmed.match(/^(\d+)\.\s*(.+)$/);

    if (numMatch) {
      blocks.push({ type: "step", text: numMatch[2] });
    } else if (
      indented &&
      blocks.length > 0 &&
      blocks[blocks.length - 1].type === "step"
    ) {
      const last = blocks[blocks.length - 1];
      last.subs = last.subs || [];
      last.subs.push(trimmed);
    } else {
      blocks.push({ type: "text", text: trimmed });
    }
  }

  return blocks;
}

function DescriptionBlocks({ blocks }) {
  if (!blocks || blocks.length === 0) return null;

  const hasSteps = blocks.some((b) => b.type === "step");

  if (!hasSteps) {
    return (
      <div className="space-y-2.5">
        {blocks.map((b, i) => (
          <p key={i} className="text-sm sm:text-[15px] text-gray-600 leading-relaxed">
            {b.text}
          </p>
        ))}
      </div>
    );
  }

  return (
    <ul className="space-y-3">
      {blocks.map((b, i) => {
        if (b.type === "text") {
          return (
            <li
              key={i}
              className="text-sm sm:text-[15px] text-gray-600 leading-relaxed"
            >
              {b.text}
            </li>
          );
        }
        return (
          <li
            key={i}
            className="flex items-start gap-3 text-sm sm:text-[15px] text-gray-700 leading-relaxed"
          >
            <span className="mt-[3px] inline-flex w-5 h-5 rounded-full bg-yellow-100 items-center justify-center flex-shrink-0">
              <FiCheck className="w-3 h-3 text-yellow-700" strokeWidth={3} />
            </span>
            <div className="flex-1 min-w-0">
              <p>{b.text}</p>
              {b.subs && b.subs.length > 0 && (
                <ul className="mt-2 space-y-1.5 pl-0.5">
                  {b.subs.map((s, j) => (
                    <li
                      key={j}
                      className="text-sm text-gray-500 leading-relaxed flex items-start gap-2.5"
                    >
                      <span className="mt-[9px] w-1 h-1 rounded-full bg-gray-300 flex-shrink-0" />
                      <span>{s}</span>
                    </li>
                  ))}
                </ul>
              )}
            </div>
          </li>
        );
      })}
    </ul>
  );
}

function GuideSkeleton() {
  return (
    <div className="grid lg:grid-cols-[240px_1fr] gap-8 lg:gap-12 animate-pulse">
      <aside className="hidden lg:block">
        <div className="h-3 w-24 bg-gray-200 rounded mb-4" />
        <div className="space-y-3">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="flex items-start gap-3">
              <div className="w-6 h-6 bg-gray-200 rounded-md flex-shrink-0" />
              <div className="h-3 w-32 bg-gray-200 rounded mt-1.5" />
            </div>
          ))}
        </div>
      </aside>
      <div>
        <div className="h-3 w-20 bg-gray-200 rounded mb-3" />
        <div className="h-6 w-64 bg-gray-200 rounded mb-8" />
        <div className="space-y-5">
          {[1, 2, 3, 4].map((i) => (
            <div
              key={i}
              className="bg-white border border-gray-100 rounded-2xl p-5 sm:p-7"
            >
              <div className="grid sm:grid-cols-[auto_1fr] gap-5 sm:gap-7">
                <div className="h-12 w-14 bg-gray-100 rounded" />
                <div className="space-y-3">
                  <div className="h-5 w-52 bg-gray-200 rounded" />
                  <div className="h-3 w-full bg-gray-200 rounded" />
                  <div className="h-3 w-5/6 bg-gray-200 rounded" />
                  <div className="h-3 w-3/4 bg-gray-200 rounded" />
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
