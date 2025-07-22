"use client";

const CoursesSkeleton = () => {
  return (
    <div className="bg-white rounded-2xl sm:rounded-3xl border border-gray-200 overflow-hidden animate-pulse">
      {/* Image Skeleton */}
      <div className="relative h-48 sm:h-56 bg-gray-200"></div>

      {/* Content */}
      <div className="p-6 sm:p-8">
        {/* Title */}
        <div className="h-6 bg-gray-200 rounded w-3/4 mb-2"></div>
        <div className="h-4 bg-gray-200 rounded w-1/2 mb-6"></div>

        {/* Description */}
        <div className="space-y-2 mb-6">
          <div className="h-4 bg-gray-200 rounded w-full"></div>
          <div className="h-4 bg-gray-200 rounded w-5/6"></div>
          <div className="h-4 bg-gray-200 rounded w-4/6"></div>
        </div>

        {/* Button */}
        <div className="h-10 bg-gray-200 rounded w-full"></div>
      </div>
    </div>
  );
};

export default CoursesSkeleton; 