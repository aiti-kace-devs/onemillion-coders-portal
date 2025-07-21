export default function PathwayDetailsSkeleton() {
  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Section Skeleton */}
      <section className="relative py-20 bg-gradient-to-br from-gray-900 to-gray-800 overflow-hidden">
        <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          {/* Back Button Skeleton */}
          <div className="mb-8">
            <div className="h-10 bg-gray-700 rounded w-40 animate-pulse"></div>
          </div>

          <div className="grid lg:grid-cols-2 gap-12 items-center">
            {/* Pathway Info Skeleton */}
            <div className="text-white">
              {/* Title skeleton */}
              <div className="space-y-4 mb-6">
                <div className="h-12 bg-gray-700 rounded animate-pulse"></div>
                <div className="h-12 bg-gray-700 rounded w-4/5 animate-pulse"></div>
              </div>

              {/* Subtitle skeleton */}
              <div className="h-6 bg-gray-700 rounded w-3/4 mb-6 animate-pulse"></div>

              {/* Description skeleton */}
              <div className="space-y-3 mb-8">
                <div className="h-5 bg-gray-700 rounded animate-pulse"></div>
                <div className="h-5 bg-gray-700 rounded w-5/6 animate-pulse"></div>
                <div className="h-5 bg-gray-700 rounded w-4/5 animate-pulse"></div>
              </div>

              {/* Duration & Focus skeleton */}
              <div className="flex flex-wrap gap-6 mb-8">
                <div className="h-5 bg-gray-700 rounded w-24 animate-pulse"></div>
                <div className="h-5 bg-gray-700 rounded w-32 animate-pulse"></div>
              </div>

              {/* CTA Button skeleton */}
              <div className="h-12 bg-gray-700 rounded w-40 animate-pulse"></div>
            </div>

            {/* Pathway Image Skeleton */}
            <div className="relative">
              <div className="relative h-96 lg:h-[500px] rounded-2xl bg-gray-700 animate-pulse"></div>
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
                {[1, 2, 3, 4].map((tab) => (
                  <div key={tab} className="flex items-center space-x-2 py-4 px-1">
                    <div className="w-4 h-4 bg-gray-200 rounded animate-pulse"></div>
                    <div className="h-4 bg-gray-200 rounded w-20 animate-pulse"></div>
                  </div>
                ))}
              </nav>
            </div>
          </div>

          {/* Tab Content Skeleton */}
          <div className="grid lg:grid-cols-3 gap-12">
            <div className="lg:col-span-2">
              {/* Title skeleton */}
              <div className="h-8 bg-gray-200 rounded w-64 mb-6 animate-pulse"></div>
              
              {/* Challenge & Solution skeleton */}
              <div className="space-y-8 mb-12">
                <div className="bg-red-50 border border-red-200 rounded-xl p-6">
                  <div className="h-6 bg-red-200 rounded w-32 mb-3 animate-pulse"></div>
                  <div className="space-y-2">
                    <div className="h-4 bg-red-200 rounded animate-pulse"></div>
                    <div className="h-4 bg-red-200 rounded w-5/6 animate-pulse"></div>
                    <div className="h-4 bg-red-200 rounded w-4/5 animate-pulse"></div>
                  </div>
                </div>
                
                <div className="bg-green-50 border border-green-200 rounded-xl p-6">
                  <div className="h-6 bg-green-200 rounded w-32 mb-3 animate-pulse"></div>
                  <div className="space-y-2">
                    <div className="h-4 bg-green-200 rounded animate-pulse"></div>
                    <div className="h-4 bg-green-200 rounded w-5/6 animate-pulse"></div>
                    <div className="h-4 bg-green-200 rounded w-4/5 animate-pulse"></div>
                  </div>
                </div>
              </div>

              {/* Benefits skeleton */}
              <div>
                <div className="h-6 bg-gray-200 rounded w-48 mb-6 animate-pulse"></div>
                <div className="grid md:grid-cols-2 gap-4">
                  {[1, 2, 3, 4, 5, 6].map((benefit) => (
                    <div key={benefit} className="flex items-start space-x-3">
                      <div className="w-5 h-5 bg-green-200 rounded-full mt-0.5 animate-pulse"></div>
                      <div className="h-4 bg-gray-200 rounded w-full animate-pulse"></div>
                    </div>
                  ))}
                </div>
              </div>
            </div>

            {/* Sidebar skeleton */}
            <div className="space-y-6">
              <div className="bg-white rounded-2xl p-6 shadow-lg border border-gray-200">
                <div className="h-5 bg-gray-200 rounded w-32 mb-4 animate-pulse"></div>
                <div className="space-y-4">
                  {[1, 2, 3, 4].map((detail) => (
                    <div key={detail} className="flex justify-between">
                      <div className="h-4 bg-gray-200 rounded w-20 animate-pulse"></div>
                      <div className="h-4 bg-gray-200 rounded w-24 animate-pulse"></div>
                    </div>
                  ))}
                </div>
              </div>

              <div className="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-2xl p-6 border border-yellow-200">
                <div className="flex items-center space-x-2 mb-3">
                  <div className="w-5 h-5 bg-yellow-300 rounded animate-pulse"></div>
                  <div className="h-5 bg-yellow-300 rounded w-32 animate-pulse"></div>
                </div>
                <div className="space-y-2">
                  <div className="h-3 bg-yellow-300 rounded animate-pulse"></div>
                  <div className="h-3 bg-yellow-300 rounded w-5/6 animate-pulse"></div>
                  <div className="h-3 bg-yellow-300 rounded w-4/5 animate-pulse"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* CTA Section Skeleton */}
      <section className="bg-gradient-to-r from-yellow-400 to-yellow-500 py-16">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <div className="h-8 bg-yellow-600 rounded w-96 mx-auto mb-4 animate-pulse"></div>
          <div className="space-y-2 mb-8">
            <div className="h-5 bg-yellow-600 rounded w-80 mx-auto animate-pulse"></div>
            <div className="h-5 bg-yellow-600 rounded w-72 mx-auto animate-pulse"></div>
          </div>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <div className="h-12 bg-yellow-600 rounded w-40 animate-pulse"></div>
            <div className="h-12 bg-yellow-600 rounded w-40 animate-pulse"></div>
          </div>
        </div>
      </section>
    </div>
  );
} 