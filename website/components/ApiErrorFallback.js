"use client";

import { FiRefreshCw, FiWifi, FiArrowRight } from "react-icons/fi";
import Button from "./Button";
import { useRouter } from "next/navigation";

const ApiErrorFallback = ({
  title = "We're experiencing some difficulties",
  message = "Our servers are taking longer than expected to respond. Please try again in a moment.",
  showRetry = true
}) => {
  const router = useRouter();

  const handleRetry = () => {
    router.refresh();
  };

  return (
    <section className="relative min-h-[60vh] flex items-center justify-center bg-gradient-to-br from-gray-50 via-white to-gray-100 overflow-hidden">
      {/* Background Pattern */}
      <div className="absolute inset-0 opacity-5">
        <div
          className="w-full h-full"
          style={{
            background: `radial-gradient(circle, #000 1px, transparent 1px)`,
            backgroundSize: "20px 20px",
          }}
        />
      </div>

      {/* Gradient Accents */}
      <div className="absolute top-1/4 left-1/4 w-64 h-64 bg-yellow-500/10 rounded-full blur-3xl" />
      <div className="absolute bottom-1/4 right-1/4 w-48 h-48 bg-green-500/10 rounded-full blur-3xl" />

      <div className="relative z-10 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        {/* Icon */}
        <div className="mb-8">
          <div className="inline-flex items-center justify-center w-20 h-20 bg-yellow-100 rounded-full">
            <FiWifi className="w-10 h-10 text-yellow-600" />
          </div>
        </div>

        {/* Content */}
        <h1 className="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
          {title}
        </h1>
        <p className="text-lg text-gray-600 mb-8 max-w-md mx-auto">
          {message}
        </p>

        {/* Actions */}
        {showRetry && (
          <div className="flex flex-col sm:flex-row gap-4 justify-center items-center">
            <Button
              onClick={handleRetry}
              icon={FiRefreshCw}
              variant="primary"
              size="large"
              iconPosition="left"
            >
              Try Again
            </Button>
            <Button
              onClick={() => router.push("/programmes")}
              icon={FiArrowRight}
              variant="outline"
              size="large"
              iconPosition="right"
            >
              Browse Programmes
            </Button>
          </div>
        )}

        {/* Helper Text */}
        <p className="mt-8 text-sm text-gray-500">
          If this problem persists, please check back later or contact support.
        </p>
      </div>
    </section>
  );
};

export default ApiErrorFallback;
