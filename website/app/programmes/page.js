import { Suspense } from "react";
import ProgrammesClient from "./ProgrammesClient";

export const revalidate = 60;

export const metadata = {
  title: "Programmes | One Million Coders",
  description: "Explore our comprehensive range of coding and technology programmes designed to empower the next generation of digital talent.",
};

function ProgrammesSkeleton() {
  return (
    <div className="min-h-screen bg-gray-50">
      <section className="bg-gradient-to-br from-gray-900 to-gray-800 text-white py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center">
            <div className="h-16 bg-gray-700 rounded-lg mb-6 animate-pulse"></div>
            <div className="h-6 bg-gray-700 rounded w-2/3 mx-auto mb-8 animate-pulse"></div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mt-12">
              {[...Array(3)].map((_, i) => (
                <div key={i} className="text-center">
                  <div className="h-8 w-16 bg-gray-700 rounded mx-auto mb-2 animate-pulse"></div>
                  <div className="h-4 w-20 bg-gray-700 rounded mx-auto animate-pulse"></div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>
      <section className="py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {[...Array(6)].map((_, i) => (
              <div key={i} className="bg-white p-6 rounded-xl shadow-sm">
                <div className="h-48 bg-gray-200 rounded-lg mb-4 animate-pulse"></div>
                <div className="h-6 bg-gray-200 rounded mb-2 animate-pulse"></div>
                <div className="h-4 bg-gray-200 rounded w-3/4 animate-pulse"></div>
              </div>
            ))}
          </div>
        </div>
      </section>
    </div>
  );
}

export default function Programmes() {
  return (
    <Suspense fallback={<ProgrammesSkeleton />}>
      <ProgrammesClient />
    </Suspense>
  );
}
