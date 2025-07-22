export default function ProgrammeSkeleton() {
  return (
    <div className="bg-white rounded-xl overflow-hidden shadow-lg">
      {/* Image Skeleton */}
      <div className="relative w-full h-48 bg-gray-200 animate-pulse">
        {/* Category Tag Skeleton */}
        <div className="absolute top-4 left-4">
          <div className="h-6 bg-gray-300 rounded-full w-20 animate-pulse"></div>
        </div>
      </div>

      {/* Content Skeleton */}
      <div className="p-6">
        {/* Title skeleton */}
        <div className="h-6 bg-gray-200 rounded mb-2 animate-pulse"></div>
        
        {/* Subtitle skeleton */}
        <div className="h-4 bg-gray-200 rounded w-4/5 mb-4 animate-pulse"></div>

        {/* Stats skeleton */}
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center space-x-2">
            <div className="w-4 h-4 bg-gray-300 rounded animate-pulse"></div>
            <div className="h-4 bg-gray-200 rounded w-16 animate-pulse"></div>
          </div>
          <div className="h-6 bg-gray-200 rounded w-16 animate-pulse"></div>
        </div>

        {/* Description skeleton */}
        <div className="space-y-2 mb-4">
          <div className="h-4 bg-gray-200 rounded animate-pulse"></div>
          <div className="h-4 bg-gray-200 rounded w-3/4 animate-pulse"></div>
        </div>

        {/* Button skeleton */}
        <div className="h-10 bg-gray-200 rounded-lg animate-pulse"></div>
      </div>
    </div>
  );
}

export function ProgrammesPageSkeleton() {
  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header Skeleton */}
      <section className="py-16 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-8">
            <div className="h-12 bg-gray-200 rounded w-1/2 mx-auto mb-4 animate-pulse"></div>
            <div className="h-6 bg-gray-200 rounded w-3/4 mx-auto animate-pulse"></div>
          </div>
        </div>
      </section>

      {/* Filters Skeleton */}
      <section className="py-8 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex flex-col md:flex-row gap-4 mb-8">
            {/* Search skeleton */}
            <div className="flex-1">
              <div className="h-12 bg-gray-200 rounded-lg animate-pulse"></div>
            </div>
            
            {/* Filter skeletons */}
            <div className="flex gap-4">
              <div className="h-12 bg-gray-200 rounded-lg w-32 animate-pulse"></div>
              <div className="h-12 bg-gray-200 rounded-lg w-32 animate-pulse"></div>
            </div>
          </div>

          {/* Programme cards grid skeleton */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {[1, 2, 3, 4, 5, 6].map((index) => (
              <ProgrammeSkeleton key={index} />
            ))}
          </div>
        </div>
      </section>
    </div>
  );
} 