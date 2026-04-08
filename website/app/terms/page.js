import { getConsentData } from "../../services";
import TermsClient from "./TermsClient";

export const dynamic = "force-dynamic";

export default async function TermsPage() {
  let data = null;

  try {
    data = await getConsentData();
  } catch (error) {
    console.error("Failed to fetch terms data:", error);
  }

  if (!data) {
    return null;
  }

  return <TermsClient data={data} />;
}
