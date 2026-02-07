import { getTermsAndPrivacyData } from "../../services";
import TermsAndPrivacyClient from "./TermsAndPrivacyClient";

export const dynamic = "force-dynamic";

export default async function TermsAndPrivacyPage() {
  let data = null;

  try {
    data = await getTermsAndPrivacyData();
  } catch (error) {
    console.error("Failed to fetch terms and privacy data:", error);
  }

  if (!data) {
    return null;
  }

  return <TermsAndPrivacyClient data={data} />;
}
