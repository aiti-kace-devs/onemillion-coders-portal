"use client";

export default function PathwaySkeleton() {
  return (
    <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden">
      {/* Image Skeleton */}
      <div className="relative h-48 bg-gray-200 animate-pulse">
        {/* Icon skeleton */}
        <div className="absolute top-6 left-6">
          <div className="w-12 h-12 bg-gray-300 rounded-lg animate-pulse"></div>
        </div>
        
        {/* Duration & Focus skeleton */}
        <div className="absolute bottom-6 left-6 right-6">
          <div className="flex items-center justify-between">
            <div className="h-4 bg-gray-300 rounded w-20 animate-pulse"></div>
            <div className="h-4 bg-gray-300 rounded w-24 animate-pulse"></div>
          </div>
        </div>
      </div>

      {/* Content Skeleton */}
      <div className="p-8">
        {/* Title skeleton */}
        <div className="h-6 bg-gray-200 rounded mb-3 animate-pulse"></div>
        
        {/* Subtitle skeleton */}
        <div className="h-4 bg-gray-200 rounded w-4/5 mb-4 animate-pulse"></div>

        {/* Description skeleton */}
        <div className="space-y-2 mb-6">
          <div className="h-4 bg-gray-200 rounded animate-pulse"></div>
          <div className="h-4 bg-gray-200 rounded w-3/4 animate-pulse"></div>
          <div className="h-4 bg-gray-200 rounded w-2/3 animate-pulse"></div>
        </div>

        {/* Benefits skeleton */}
        <div className="mb-6">
          <div className="h-4 bg-gray-200 rounded w-24 mb-2 animate-pulse"></div>
          <div className="space-y-1">
            <div className="flex items-start space-x-2">
              <div className="w-1 h-1 bg-gray-300 rounded-full mt-2"></div>
              <div className="h-3 bg-gray-200 rounded w-full animate-pulse"></div>
            </div>
            <div className="flex items-start space-x-2">
              <div className="w-1 h-1 bg-gray-300 rounded-full mt-2"></div>
              <div className="h-3 bg-gray-200 rounded w-4/5 animate-pulse"></div>
            </div>
          </div>
        </div>

        {/* Button skeleton */}
        <div className="h-10 bg-gray-200 rounded-lg animate-pulse"></div>
      </div>
    </div>
  );
}

export function PathwaysPageSkeleton() {
  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Section Skeleton */}
      <section className="bg-gradient-to-br from-gray-900 to-gray-800 text-white py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center">
            {/* Title skeleton */}
            <div className="h-16 bg-gray-700 rounded w-2/3 mx-auto mb-6 animate-pulse"></div>
            
            {/* Description skeleton */}
            <div className="space-y-2 max-w-3xl mx-auto mb-8">
              <div className="h-6 bg-gray-700 rounded animate-pulse"></div>
              <div className="h-6 bg-gray-700 rounded w-4/5 mx-auto animate-pulse"></div>
            </div>
            
            {/* Stats skeleton */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mt-12">
              {[1, 2, 3].map((index) => (
                <div key={index} className="text-center">
                  <div className="h-12 bg-gray-700 rounded w-20 mx-auto mb-2 animate-pulse"></div>
                  <div className="h-4 bg-gray-700 rounded w-32 mx-auto animate-pulse"></div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* Pathways Grid Skeleton */}
      <section className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {[1, 2, 3, 4, 5, 6].map((index) => (
              <PathwaySkeleton key={index} />
            ))}
          </div>
        </div>
      </section>
    </div>
  );
} 