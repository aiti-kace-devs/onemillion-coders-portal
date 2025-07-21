import ProgrammesClient from "../../ProgrammesClient";

export const metadata = {
  title: "Programmes by Category | One Million Coders",
  description: "Explore our comprehensive range of coding and technology programmes filtered by category.",
};

export default function ProgrammesByCategory({ params }) {
  return <ProgrammesClient initialCategory={params.id} />;
} 