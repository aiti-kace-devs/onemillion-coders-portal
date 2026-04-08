import { Suspense } from "react";
import CourseMatchClient from "./CourseMatchClient";

export default function CourseMatchPage() {
  return (
    <Suspense>
      <CourseMatchClient />
    </Suspense>
  );
}
