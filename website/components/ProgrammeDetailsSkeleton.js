const ProgrammeDetailsSkeleton = () => {
  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Section Skeleton */}
      <section className="relative py-20 bg-gradient-to-br from-gray-400 to-gray-500 overflow-hidden">
        <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          {/* Back Button Skeleton */}
          <div className="mb-8">
            <div className="w-40 h-10 bg-white/20 rounded-lg animate-pulse"></div>
          </div>

          <div className="grid lg:grid-cols-2 gap-12 items-center">
            {/* Programme Info Skeleton */}
            <div className="text-white">
              {/* Badges Skeleton */}
              <div className="flex items-center space-x-3 mb-6">
                <div className="w-24 h-6 bg-white/20 rounded-full animate-pulse"></div>
                <div className="w-32 h-6 bg-white/20 rounded-full animate-pulse"></div>
              </div>

              {/* Title Skeleton */}
              <div className="mb-6">
                <div className="w-full h-12 bg-white/20 rounded-lg animate-pulse mb-3"></div>
                <div className="w-3/4 h-12 bg-white/20 rounded-lg animate-pulse"></div>
              </div>

              {/* Subtitle Skeleton */}
              <div className="w-2/3 h-6 bg-white/20 rounded-lg animate-pulse mb-6"></div>

              {/* Description Skeleton */}
              <div className="mb-8">
                <div className="w-full h-4 bg-white/20 rounded animate-pulse mb-2"></div>
                <div className="w-full h-4 bg-white/20 rounded animate-pulse mb-2"></div>
                <div className="w-2/3 h-4 bg-white/20 rounded animate-pulse"></div>
              </div>

              {/* Quick Stats Skeleton */}
              <div className="grid grid-cols-2 gap-6 mb-8">
                <div className="flex items-center space-x-3">
                  <div className="w-10 h-10 bg-white/20 rounded-lg animate-pulse"></div>
                  <div>
                    <div className="w-16 h-4 bg-white/20 rounded animate-pulse mb-1"></div>
                    <div className="w-12 h-3 bg-white/20 rounded animate-pulse"></div>
                  </div>
                </div>
                <div className="flex items-center space-x-3">
                  <div className="w-10 h-10 bg-white/20 rounded-lg animate-pulse"></div>
                  <div>
                    <div className="w-20 h-4 bg-white/20 rounded animate-pulse mb-1"></div>
                    <div className="w-16 h-3 bg-white/20 rounded animate-pulse"></div>
                  </div>
                </div>
              </div>

              {/* CTA Button Skeleton */}
              <div className="w-32 h-12 bg-white/20 rounded-lg animate-pulse"></div>
            </div>

            {/* Image Skeleton */}
            <div className="relative">
              <div className="h-96 lg:h-[500px] bg-white/20 rounded-2xl animate-pulse"></div>
            </div>
          </div>
        </div>
      </section>

      {/* Content Section Skeleton */}
      <section className="py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          {/* Tab Navigation Skeleton */}
          <div className="mb-12">
            <div className="border-b border-gray-200">
              <nav className="flex space-x-8">
                {[...Array(4)].map((_, index) => (
                  <div
                    key={index}
                    className="flex items-center space-x-2 py-4 px-1"
                  >
                    <div className="w-4 h-4 bg-gray-300 rounded animate-pulse"></div>
                    <div className="w-20 h-4 bg-gray-300 rounded animate-pulse"></div>
                  </div>
                ))}
              </nav>
            </div>
          </div>

          {/* Tab Content Skeleton */}
          <div className="grid lg:grid-cols-3 gap-12">
            <div className="lg:col-span-2">
              <div className="w-48 h-8 bg-gray-300 rounded animate-pulse mb-6"></div>
              <div className="prose prose-lg max-w-none">
                <div className="mb-6">
                  <div className="w-full h-4 bg-gray-300 rounded animate-pulse mb-2"></div>
                  <div className="w-full h-4 bg-gray-300 rounded animate-pulse mb-2"></div>
                  <div className="w-3/4 h-4 bg-gray-300 rounded animate-pulse"></div>
                </div>
                
                <div className="w-40 h-6 bg-gray-300 rounded animate-pulse mb-4"></div>
                <ul className="space-y-3">
                  {[...Array(4)].map((_, index) => (
                    <li key={index} className="flex items-start space-x-3">
                      <div className="w-5 h-5 bg-gray-300 rounded-full animate-pulse mt-0.5 flex-shrink-0"></div>
                      <div className="w-full h-4 bg-gray-300 rounded animate-pulse"></div>
                    </li>
                  ))}
                </ul>
              </div>
            </div>

            <div className="space-y-6">
              <div className="bg-white rounded-2xl p-6 shadow-lg border border-gray-200">
                <div className="w-32 h-5 bg-gray-300 rounded animate-pulse mb-4"></div>
                <div className="space-y-4">
                  {[...Array(4)].map((_, index) => (
                    <div key={index} className="flex justify-between">
                      <div className="w-16 h-4 bg-gray-300 rounded animate-pulse"></div>
                      <div className="w-20 h-4 bg-gray-300 rounded animate-pulse"></div>
                    </div>
                  ))}
                </div>
              </div>

              <div className="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-2xl p-6 border border-yellow-200">
                <div className="flex items-center space-x-2 mb-3">
                  <div className="w-5 h-5 bg-yellow-300 rounded animate-pulse"></div>
                  <div className="w-44 h-5 bg-yellow-300 rounded animate-pulse"></div>
                </div>
                <ul className="space-y-2">
                  {[...Array(4)].map((_, index) => (
                    <li key={index}>
                      <div className="w-full h-3 bg-yellow-300 rounded animate-pulse"></div>
                    </li>
                  ))}
                </ul>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
};

export default ProgrammeDetailsSkeleton; 