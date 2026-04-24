import ActivationClient from "./ActivationClient";

export const metadata = {
  title: "Account Activation | One Million Coders",
};

export default async function ActivateAccountPage({ params }) {
  return <ActivationClient token={decodeURIComponent(params.token)} />;
}
