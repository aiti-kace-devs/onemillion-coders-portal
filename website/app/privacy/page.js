import { getTermsAndPrivacyData } from "../../services";
import PrivacyClient from "./PrivacyClient";

export const dynamic = "force-dynamic";

export default async function PrivacyPage() {
  let data = null;

  try {
    data = await getTermsAndPrivacyData();
  } catch (error) {
    console.error("Failed to fetch privacy data:", error);
  }

  if (!data) {
    return null;
  }

  return <PrivacyClient data={data} />;
}
